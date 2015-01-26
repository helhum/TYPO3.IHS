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
								url: "/issues/getVulnerabilityTypesAsJSON",
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
								url: "/products/getProductTypesAsJSON",
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
								url: "/products/getProductsAsJSON",
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
			var versions = $(element).closest('div.form-group').first().siblings('.affected-versions').first();

			// get versions for product if none exists
			if (!$(versions).find('select option:first-child').val() && $(versions).find('select option').length == 1) {
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

			$.getJSON($('input.vulnerabilityType').closest('.form-group').attr('data-vulnerabilityTypeUrl'), request, function (data, status, xhr) {
				vulnerabilityTypesCache[term] = data;
				response(data);
			});
		}
	}).click(function () {
		$(this).autocomplete("search", vulnerabilityTypeAutocompletionLastTerm);
	});

	// create new versions when adding solutions
	var currentVersionsField = null;
	$('.add-new-versions').off('click');
	$('.add-new-versions').on('click', function () {
		$('#new-versions-modal .modal-body').html("loading...")
		$.ajax({
			type: "GET",
			url: $(this).attr("data-href"),
			//dataType: "html",
			success: function (data) {
				// TODO: may not work with multiple issue instances
				$('#new-versions-modal .modal-body').html(data);
			}
		});

		currentVersionsField = $(this).closest('div.form-group').find('select#solution-fixedInVersions');
	});
	$('.create-versions').off('click');
	$('.create-versions').on('click', function (e) {
		e.preventDefault();
		$(this).button('loading');
		$('#version-modal .modal-body .status-message').remove();
		var versions = $('#product-new-versions').val().split('\n');
		var versionsJSON = JSON.stringify(versions);

		var productIdentifier = $('select.product').val();

		$.ajax({
			type: "GET",
			url: $(this).attr("href"),
			dataType: "json",
			data: {versions: versionsJSON, product: productIdentifier},
			success: function (data) {
				if (data.status = "success") {
					//todo add productversions to select fields
					var createdVersions = data.createdVersions;
					$(createdVersions).each(function (key, value) {
						$(currentVersionsField).append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentVersionsField).find('option[value="' + value.identifier + '"]').prop('selected', true);

						// add new version to list of created versions
						$(currentVersionsField).closest('.form-group').find('div.created-versions ul').append('<li class="created-version" data-version-id="' + value.identifier + '">' + value.versionAsString + ' <button type="button" class="delete-created-version btn btn-danger btn-sm"><i class="glyphicon glyphicon-trash"></i> delete</button></li>')
						$(currentVersionsField).closest('.form-group').find('div.created-versions-outer').show();

						// add click action for new versions to delete them
						$('div.created-versions li[data-version-id="' + value.identifier + '"] .delete-created-version').on('click', function() {
							var button = $(this);
							console.log (value);
							$.ajax({
								type: "GET",
								url: $('.created-versions').attr('data-delete-url'),
								dataType: "json",
								data: {'productVersion': value.identifier, 'product': productIdentifier},
								success: function (data) {
									$(button).closest('li').remove();
									$(currentVersionsField).find('option[value="' + value.identifier + '"]').remove();
								}
							});
						});
					});
					$('#product-new-versions').val("");
					$('#new-versions-modal .modal-body').append("<p class='status-message'>" + data.message + "</p>");
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
		var versions = $(productSelect).closest('.form-group').siblings('.affected-versions')[0];
		var versionsSelect = $(versions).find('select.affectedVersions');
		var selectedVersionsSelect = $(versions).find('.selected-affected-versions');
		console.log("test", $(selectedVersionsSelect), data);
		$(versionsSelect).html(''); // clear version list from prev request
		$(versions).slideDown(); // show versions selector

		// append versions from ajax request to the selector
		$(data).each(function(key, data) {
			$(versionsSelect).append('<option value="' + data.id + '">' + data.value + '</option>');
		});
		// select options
		if ($(selectedVersionsSelect).html().trim() != '') {
			var selectedVersions = JSON.parse($('.selected-affected-versions').html());
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
		$(".saved-searches").html("");
		var savedSearchesJSON = localStorage.getItem('savedIssueSearches');
		var savedSearches = JSON.parse(savedSearchesJSON);
		if (savedSearches && savedSearches.length > 0) {
			$(".saved-searches-container").show();
		} else {
			$(".saved-searches-container").hide();
		}

		$(savedSearches).each(function(key, savedSearch) {
			var searchQueryJSON = JSON.stringify(savedSearch);
			$(".saved-searches").append("<li><button key='"+key+"' class='remove-saved-search btn btn-xs btn-danger'><i class='glyphicon glyphicon-trash'></i></button><a class='saved-search' href='"+window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON)+"'>"+getSearchStringFromJSON(searchQueryJSON, true)+"</a></li>");
		});

		$('.remove-saved-search').on('click', function() {
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