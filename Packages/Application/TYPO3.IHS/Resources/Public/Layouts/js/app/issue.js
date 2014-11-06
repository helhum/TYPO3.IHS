var issueVisualSearch = false; // global instance of visual search for issues

$( document ).ready(function(){
	// visual search
	var runningSearchRequest = false;
	var facetsCache = {};
	var productsCache = {};

	issueVisualSearch = VS.init({
		container : $('.issue-visualsearch'),
		query     : '',
		callbacks : {
			search: function(query, searchCollection) {
				// send request
				var searchQueryJSON = JSON.stringify(issueVisualSearch.searchQuery.facets());

				$(".loading-overlay").show();

				// abort running ajax request
				if (runningSearchRequest) {
					runningSearchRequest.abort();
				}
				runningSearchRequest = $.ajax({
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
					'product type', 'has solution', 'has advisory', 'product'
				]);
			},
			valueMatches: function(facet, searchTerm, callback) {
				switch (facet) {
					case 'product type':
						if (facetsCache['product type']) {
							callback(facetsCache['product type']);
						} else {
							$.ajax({
								type: "GET",
								url: "/product/getProductTypesAsJSON",
								dataType: "json",
								success: function(types) {
									callback(types);
									facetsCache['product type'] = types;
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
							$.ajax({
								type: "GET",
								url: "/product/getProductsAsJSON",
								dataType: "json",
								data: {term: searchTerm},
								success: function(products) {
									productsCache[searchTerm] = products;
									callback(products);
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

	// autocompletion for products
	var cache = {};
	var lastTerm = "";
	$("input#productAjax")
		.autocomplete({
			minLength: 1,
			max:10,
			source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
					response( cache[ term ] );
					return;
				} else {
					lastTerm = term;
				}

				$.getJSON( $("input#productAjax").attr("productsUrl"), request, function( data, status, xhr ) {
					cache[ term ] = data;
					response( data );
				});
			},
			select: function( event, ui ) {
				var currentValue = $("select#product").val();
				$("select#product").val(ui.item.id);

				if(ui.item.id != currentValue) {
					$("select#affectedVersions").html(""); // clear version list from prev request
					getVersionsForProduct(ui.item.id);
				}
			}
		})
		.click(function(){
			if($(this).val()) {
				$(this).autocomplete( "search", lastTerm);
			}
		})
		.on("keyup", function() {
			if($(this).val() == "") {
				$("#affected-versions").slideUp();
			}
		});


	if($("select#product").val()) {
		$("input#productAjax").val($("select#product option:selected").text());
		$("#affected-versions").show();
	}
});


function getVersionsForProduct(identifier) {
	$.ajax({
		type: "GET",
		url: $("input#productAjax").attr("versionsUrl"),
		data: { "identifier": identifier},
		dataType: "json"
	})
	.success(function( data ) {
		$("#affected-versions").slideDown();
		$(data).each(function(key, data) {
			$("select#affectedVersions").append("<option value='"+data.id+"'>"+data.value+"</option>")
		});
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