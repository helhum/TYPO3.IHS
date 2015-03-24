var issueVisualSearch = false; // global instance of visual search for issues
var advisoryVisualSearch = false;
var vs_facetsCache = {};
var vs_productsCache = {};
var ajaxRequest = false;
var vs_vulnerabilityTypeCache = {};

jQuery(document).ready(function() {
	// overwriting visualsearch searchbox template
	// added save search button
	window.JST['search_box'] = _.template('<div class="VS-search <% if (readOnly) { %>VS-readonly<% } %>">\n  <div class="VS-search-box-wrapper VS-search-box">\n    <div class="VS-icon VS-icon-search"></div>\n    <div class="VS-placeholder"></div>\n    <div class="VS-search-inner"></div>\n    <div class="VS-icon VS-icon-cancel VS-cancel-search-box" title="clear search"></div> <div class="VS-icon VS-save-search-box" title="save search"><i class="glyphicon glyphicon-star-empty"></i></div>\n  </div>\n</div>');

	// visual search
	issueVisualSearch = VS.init({
		container: $('.issue-visualsearch'),
		query: '',
		callbacks: {
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
				vs_valueMatches(facet, searchTerm, callback);
			}
		}
	});

	advisoryVisualSearch = VS.init({
		container : $('.advisory-visualsearch'),
		query     : '',
		callbacks : {
			search: function(query, searchCollection) {
				// send request
				var searchQueryJSON = JSON.stringify(advisoryVisualSearch.searchQuery.facets());

				$(".loading-overlay").show();

				// abort running ajax request
				if (ajaxRequest) {
					ajaxRequest.abort();
				}
				ajaxRequest = $.ajax({
					type: "GET",
					contentType: "application/json; charset=utf-8",
					url: $('.advisory-visualsearch').attr('searchUrl'),
					dataType: "html",
					data: {search: searchQueryJSON},
					success: function(data) {
						var result = $('<div />').append(data).find('.list-of-advisories .articles').html();
						$(".list-of-advisories .articles").html(result);

						// replace current url
						if(advisoryVisualSearch.searchBox.value()) {
							window.history.replaceState(null, null, window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON));
						} else {
							window.history.replaceState(null, null, window.location.pathname);
						}
					},
					complete: function() {
						$(".loading-overlay").hide();
						ajaxRequest = false;
					},
					error: function() {
						$(".list-of-advisories .articles").html("<p>Error while loading. Please reload.</p>");
					}
				});

				// TODO: send json to backend and build new result
			},
			facetMatches: function(callback) {
				var facets = JSON.parse($('.advisory-visualsearch').attr('data-facets'));
				callback(facets);
			},
			valueMatches: function(facet, searchTerm, callback) {
				vs_valueMatches(facet, searchTerm, callback);
			}
		}
	});

	function vs_valueMatches(facet, searchTerm, callback) {
		switch (facet) {
			case 'vulnerability type':
				if (searchTerm in vs_vulnerabilityTypeCache) {
					callback(vs_vulnerabilityTypeCache[searchTerm]);
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
							vs_vulnerabilityTypeCache[searchTerm] = vulnerabilityTypes;
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
				vs_productsCache = {}; // clear products cache when changing product type

				if (vs_facetsCache['product type']) {
					callback(vs_facetsCache['product type']);
				} else {
					if (ajaxRequest) {
						ajaxRequest.abort();
					}

					ajaxRequest = $.ajax({
						type: "GET",
						url: "/products/getProductTypesAsJSON",
						dataType: "json",
						success: function(types) {
							vs_facetsCache['product type'] = types;
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
			case 'has issue':
				callback(['yes', 'no']);
				break;
			case 'product':
				if (searchTerm in vs_productsCache) {
					callback(vs_productsCache[searchTerm]);
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
							vs_productsCache[searchTerm] = products;
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

	if ($('.issue-visualsearch').length > 0) {
		vs_handleSavedSearches(issueVisualSearch, 'savedIssueSearches');
	}

	if ($('.advisory-visualsearch').length > 0) {
		vs_handleSavedSearches(advisoryVisualSearch, 'savedAdvisorySearches');
	}
});

function vs_handleSavedSearches(visualSearchInstance, localStorageKey) {
	visualSearchInstance.searchBox.value(vs_poplulateSearchBoxFromUrl());
	vs_displaySavedSearches(localStorageKey);

	// handle click to save current search
	$(".VS-save-search-box").on("click", function() {
		vs_saveVisualSearch(visualSearchInstance, localStorageKey);
		vs_displaySavedSearches(localStorageKey);
	});
}

function vs_saveVisualSearch(visualSearchInstance, localStorageKey) {
	if (visualSearchInstance.searchQuery.facets().length > 0) {
		var savedSearchesJSON = localStorage.getItem(localStorageKey);
		var savedSearches = [];
		if (savedSearchesJSON) {
			savedSearches = JSON.parse(savedSearchesJSON);
		}

		savedSearches.push(visualSearchInstance.searchQuery.facets());
		savedSearchesJSON = JSON.stringify(savedSearches);

		localStorage.setItem(localStorageKey, savedSearchesJSON);
	}
}

function vs_displaySavedSearches(localStorageKey) {
	$(".saved-searches").html("");
	var savedSearchesJSON = localStorage.getItem(localStorageKey);
	var savedSearches = JSON.parse(savedSearchesJSON);
	if (savedSearches && savedSearches.length > 0) {
		$(".saved-searches-container").show();
	} else {
		$(".saved-searches-container").hide();
	}

	$(savedSearches).each(function(key, savedSearch) {
		var searchQueryJSON = JSON.stringify(savedSearch);
		$(".saved-searches").append("<li><button key='"+key+"' class='remove-saved-search btn btn-xs btn-danger'><i class='glyphicon glyphicon-trash'></i></button><a class='saved-search' href='"+window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON)+"'>"+vs_getSearchStringFromJSON(searchQueryJSON, true)+"</a></li>");
	});

	$('.remove-saved-search').off('click')
	$('.remove-saved-search').on('click', function() {
		var key = $(this).attr('key');
		savedSearches.splice(key, 1);
		savedSearchesJSON = JSON.stringify(savedSearches);
		localStorage.setItem(localStorageKey, savedSearchesJSON);
		vs_displaySavedSearches();
	});
}

function vs_poplulateSearchBoxFromUrl() {
	var searchString = "";
	if (getURLParameter('search')) {
		searchString = vs_getSearchStringFromJSON(decodeURIComponent(getURLParameter('search')), false);
	}

	return searchString
}

function vs_getSearchStringFromJSON(searchStringAsJSON, humanReadable) {
	var searchQuery = JSON.parse(searchStringAsJSON);
	var searchString = "";

	$(searchQuery).each(function(key, searchPart) {
		if (searchPart && searchPart.text) {
			searchString = searchString + searchPart.text;
		} else {
			searchString = searchString + "\"" + _.keys(searchPart)[0] + "\": \"" + searchPart[_.keys(searchPart)[0]] + "\"";
		}

		if (humanReadable) {
			searchString = searchString + ", ";
		} else {
			searchString = searchString + " ";
		}
	});

	if (humanReadable) {
		searchString = searchString.substring(0, searchString.length - 2); //remove last ", "
	}

	return searchString;
}

