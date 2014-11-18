var issueVisualSearch = false; // global instance of visual search for issues

$(document).ready(function() {
	// visual search
	var facetsCache = {};
	var productsCache = {};
	var ajaxRequest = false;
	var vulnerabilityTypeCache = {};

	issueVisualSearch = VS.init({
		container : $('.issue-visualsearch'),
		query     : '',
		callbacks : {
			search: function(query, searchCollection) {
				// send request
				var searchQueryJSON = JSON.stringify(issueVisualSearch.searchQuery.facets());

				$(".loading-overlay").show();

				// abort running ajax request
				if (ajaxRequest) {
					ajaxRequest.abort();
				}
				ajaxRequest = $.ajax({
					type: "GET",
					contentType: "application/json; charset=utf-8",
					url: $('.issue-visualsearch').attr('searchUrl'),
					dataType: "html",
					data: {search: searchQueryJSON},
					success: function(data) {
						var result = $('<div />').append(data).find('.list-of-issues .issues').html();
						$(".list-of-issues .issues").html(result);

						// replace current url
						if(searchQueryJSON) {
							window.history.replaceState(null, null, window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON));
						} else {
							window.history.replaceState(null, null, window.location.pathname);
						}
					},
					complete: function() {
						ajaxRequest = false;
						$(".loading-overlay").hide();
					},
					error: function() {
						$(".list-of-issues .issues").html("<p>Error while loading. Please reload.</p>");
					}
				});

				// TODO: send json to backend and build new result
			},
			facetMatches: function(callback) {
				callback([
					'vulnerability type', 'product type', 'has solution', 'has advisory', 'product'
				]);
			},
			valueMatches: function(facet, searchTerm, callback) {
				switch (facet) {
					case 'vulnerability type':
						if (searchTerm in vulnerabilityTypeCache) {
							callback(vulnerabilityTypeCache[searchTerm]);
						} else {
							if (ajaxRequest) {
								ajaxRequest.abort();
							}

							ajaxRequest = $.ajax({
								type: "GET",
								url: "/issue/getVulnerabilityTypesAsJSON",
								dataType: "json",
								data: {term: searchTerm},
								success: function(vulnerabilityTypes) {
									vulnerabilityTypeCache[searchTerm] = vulnerabilityTypes;
									if (vulnerabilityTypes.length > 0) {
										callback(vulnerabilityTypes);
									} else {
										callback(['no vulnerabilitytypes found']);
									}
								},
								complete: function() {
									ajaxRequest= false;
								}
							});
						}
						break;
					case 'product type':
						productsCache = {}; // clear products cache when changing product type

						if (facetsCache['product type']) {
							callback(facetsCache['product type']);
						} else {
							if (ajaxRequest) {
								ajaxRequest.abort();
							}

							ajaxRequest = $.ajax({
								type: "GET",
								url: "/product/getProductTypesAsJSON",
								dataType: "json",
								success: function(types) {
									facetsCache['product type'] = types;
									callback(types);
								},
								complete: function() {
									ajaxRequest= false;
								}
							});
						}
						break;
					case 'has solution':
						callback(['yes', 'no']);
						break;
					case 'has advisory':
						callback(['yes', 'no']);
						break;
					case 'product':
						if (searchTerm in productsCache) {
							callback(productsCache[searchTerm]);
						} else {
							if (ajaxRequest) {
								ajaxRequest.abort();
							}

							// get current product type if selected and filter products
							var productType = null;
							$.each( issueVisualSearch.searchQuery.facets(), function( key, facet ) {
								if (facet['product type']) {
									productType = facet['product type'];
								}
							});

							ajaxRequest = $.ajax({
								type: "GET",
								url: "/product/getProductsAsJSON",
								dataType: "json",
								data: {term: searchTerm, withIssue: true, productType: productType},
								success: function(products) {
									productsCache[searchTerm] = products;
									if (products.length > 0) {
										callback(products);
									} else {
										callback(['no product found']);
									}
								},
								complete: function() {
									ajaxRequest= false;
								}
							});
						}
						break;
				}
			}
		}
	});

	if ($('.issue-visualsearch').length > 0) {
		handleSavedIssueSearches();
	}

	initIssue();
	$('body').on('dynamicFieldAdded', initIssue);
});

function initIssue() {
	var productsCache = {};

	// autocompletion for products
	var productAutocompletionLastTerm = "";
	$('input.productAjax').autocomplete({
		minLength: 1,
		max: 10,
		source: function (request, response) {
			var term = request.term;
			productAutocompletionLastTerm = term;
			if (term in productsCache) {
				response(productsCache[term]);
				return;
			}

			$.getJSON($('input.productAjax').attr('productsUrl'), request, function (data, status, xhr) {
				productsCache[term] = data;
				response(data);
			});
		},
		select: function (event, ui) {
			var select = $(event.target).siblings('select.product')[0];
			var currentValue = jQuery(select).val();
			jQuery(select).val(ui.item.id);

			if (ui.item.id != currentValue) {
				getVersionsForProduct(ui.item.id, select);
			}
		}
	}).click(function () {
		if ($(this).val()) {
			$(this).autocomplete('search', productAutocompletionLastTerm);
		}
	}).on('keyup', function (event, ui) {
		if ($(this).val() == "") {
			$("#affected-versions").slideUp();
		}
	});

	// set form error class to autocompletion field if product field has one
	$('select.product').each(function(index, element) {
		if(jQuery(element).hasClass('f3-form-error')) {
			$(element).siblings('input.productAjax').first().addClass('f3-form-error');
		}
	});

	// if there is a selected product populate autocompletion field and get versions for this product
	$('select.product').each(function(index, element) {
		if ($(element).val()) {
			$(element).siblings('input.productAjax').val($(element).children('option:selected')[0].text);
			var versions = $(element).closest('li').first().siblings('.affected-versions').first();

			// get versions for product if none exists
			if (!$(versions).children('option:first-child').first().val() && $(versions).children('option').length == 1) {
				getVersionsForProduct($(element).children('option:selected').first().val(), element)
			} else {
				$(versions).show();
			}
		}
	});

	// autocompletion for vulnerability type
	var vulnerabilityTypeAutocompletionLastTerm = "";
	var vulnerabilityTypesCache = {};
	$('input.vulnerabilityType').autocomplete({
		minLength: 0,
		max: 10,
		source: function (request, response) {
			var term = request.term;
			vulnerabilityTypeAutocompletionLastTerm = term;
			if (term in vulnerabilityTypesCache) {
				response(vulnerabilityTypesCache[term]);
				return;
			}

			$.getJSON($('input.vulnerabilityType').closest('li').attr('vulnerabilityTypeUrl'), request, function (data, status, xhr) {
				vulnerabilityTypesCache[term] = data;
				response(data);
			});
		}
	}).click(function () {
		$(this).autocomplete("search", vulnerabilityTypeAutocompletionLastTerm);
	});

	// create new versions when adding solutions
	var currentVersionsField = null;
	$('#new-versions-modal').on('show', function () {
		$(this).find('.modal-body').html("loading...")
	});
	$('.add-new-versions').on('click', function () {
		currentVersionsField = $(this).closest('li').find('select#solution-fixedInVersions');
	});
	$('.create-versions').on('click', function (e) {
		e.preventDefault();
		$(this).button('loading');
		$('#version-modal .modal-body .status-message').remove();
		var versions = $('#product-new-versions').val().split('\n');
		var versionsJSON = JSON.stringify(versions);

		var productIdentifier = $('select#product').val();

		$.ajax({
			type: "GET",
			url: $(this).attr("href"),
			dataType: "json",
			data: {versions: versionsJSON, productIdentifier: productIdentifier},
			success: function (data) {
				if (data.status = "success") {
					//todo add productversions to select fields
					var createdVersions = data.createdVersions;
					$(createdVersions).each(function (key, value) {
						$(currentVersionsField).append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentVersionsField).find("option[value='" + value.identifier + "']").prop('selected', true);

						currentVersionsField = null;
					});
					$('#product-new-versions').val("");
					$('#version-modal .modal-body').append("<p class='status-message'>" + data.message + "</p>");
				}
			},
			complete: function () {
				$('.create-versions').button('reset')
			}
		});
	});
}

function getVersionsForProduct(identifier, productSelect) {
	$.ajax({
		type: 'GET',
		url: $('input.productAjax').attr('versionsUrl'),
		data: {"identifier": identifier},
		dataType: 'json'
	}).success(function(data) {
		var versions = $(productSelect).closest('li').siblings('.affected-versions')[0];
		var versionsSelect = $(versions).children('select.affectedVersions')[0];
		var selectedVersionsSelect = $(versions).children('.selected-affected-versions')[0];
		$(versionsSelect).html(''); // clear version list from prev request
		$(versions).slideDown();
		$(data).each(function(key, data) {
			$(versionsSelect).append('<option value="' + data.id + '">' + data.value + '</option>');
		});
		// select options
		if ($(selectedVersionsSelect).html().trim() != '') {
			selectedVersions = JSON.parse($('#selected-affected-versions').html());
			$(selectedVersions).each(function(key, value) {
				$($(versionsSelect).children('option[value="' + value + '"]')[0]).prop('selected', true);
			});
		}
	});
}

function handleSavedIssueSearches() {
	poplulateSearchBoxFromUrl();
	displaySavedSearches();

	// handle click to save current search
	$(".VS-save-search-box").on("click", function() {
		if (issueVisualSearch.searchQuery.facets().length > 0) {
			var savedSearchesJSON = localStorage.getItem('savedIssueSearches');
			var savedSearches = [];
			if (savedSearchesJSON) {
				savedSearches = JSON.parse(savedSearchesJSON);
			}

			savedSearches.push(issueVisualSearch.searchQuery.facets());
			savedSearchesJSON = JSON.stringify(savedSearches);

			localStorage.setItem('savedIssueSearches', savedSearchesJSON);
			displaySavedSearches();
		}
	});

	function displaySavedSearches() {
		$(".issues .saved-searches").html("");
		var savedSearchesJSON = localStorage.getItem('savedIssueSearches');
		var savedSearches = JSON.parse(savedSearchesJSON);
		if (savedSearches && savedSearches.length > 0) {
			$(".issues .saved-searches-container").show();
			$(".issues .list-of-issues").addClass("span9");
		} else {
			$(".issues .saved-searches-container").hide();
			$(".issues .list-of-issues").removeClass("span9");
		}

		$(savedSearches).each(function(key, savedSearch) {
			var searchQueryJSON = JSON.stringify(savedSearch);
			$(".issues .saved-searches").append("<li><button key='"+key+"' class='remove-saved-search btn btn-mini btn-danger'><i class='icon-trash icon-white'></i></button><a class='saved-search' href='"+window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON)+"'>"+getSearchStringFromJSON(searchQueryJSON, true)+"</a></li>");
		});

		$('.issues .remove-saved-search').on('click', function() {
			var key = $(this).attr('key');
			savedSearches.splice(key, 1);
			savedSearchesJSON = JSON.stringify(savedSearches);
			localStorage.setItem('savedIssueSearches', savedSearchesJSON);
			displaySavedSearches();
		});
	}

	function poplulateSearchBoxFromUrl() {
		var searchString = "";
		if (getURLParameter('search')) {
			searchString = getSearchStringFromJSON(decodeURIComponent(getURLParameter('search')), false);
		}

		issueVisualSearch.searchBox.value(searchString);
	}
}