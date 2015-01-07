var advisoryVisualSearch = false; // global instance of visual search for advisories

$( document ).ready(function(){
	var runningSearchRequest = false;
	var facetsCache = {};
	var productsCache = {};
	var ajaxRequest = false;
	var vulnerabilityTypeCache = {};

	advisoryVisualSearch = VS.init({
		container : $('.advisory-visualsearch'),
		query     : '',
		callbacks : {
			search: function(query, searchCollection) {
				// send request
				var searchQueryJSON = JSON.stringify(advisoryVisualSearch.searchQuery.facets());

				$(".loading-overlay").show();

				// abort running ajax request
				if (runningSearchRequest) {
					runningSearchRequest.abort();
				}
				runningSearchRequest = $.ajax({
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
					},
					error: function() {
						$(".list-of-advisories .articles").html("<p>Error while loading. Please reload.</p>");
					}
				});

				// TODO: send json to backend and build new result
			},
			facetMatches: function(callback) {
				callback([
					'vulnerability type', 'product', 'product type'
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
					case 'product':
						if (searchTerm in productsCache) {
							callback(productsCache[searchTerm]);
						} else {
							if (ajaxRequest) {
								ajaxRequest.abort();
							}

							// get current product type if selected and filter products
							var productType = null;
							$.each( advisoryVisualSearch.searchQuery.facets(), function( key, facet ) {
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

	if ($('.advisory-visualsearch').length > 0) {
		handleSavedSearches();
	}
});

function handleSavedSearches() {
	poplulateSearchBoxFromUrl();
	displaySavedSearches();

	// handle click to save current search
	$(".VS-save-search-box").on("click", function() {
		if (advisoryVisualSearch.searchQuery.facets().length > 0) {
			var savedSearchesJSON = localStorage.getItem('savedAdvisorySearches');
			var savedSearches = [];
			if (savedSearchesJSON) {
				savedSearches = JSON.parse(savedSearchesJSON);
			}

			savedSearches.push(advisoryVisualSearch.searchQuery.facets());
			savedSearchesJSON = JSON.stringify(savedSearches);

			localStorage.setItem('savedAdvisorySearches', savedSearchesJSON);
			displaySavedSearches();
		}
	});

	function displaySavedSearches() {
		$(".saved-searches").html("");
		var savedSearchesJSON = localStorage.getItem('savedAdvisorySearches');
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
			localStorage.setItem('savedAdvisorySearches', savedSearchesJSON);
			displaySavedSearches();
		});
	}

	function poplulateSearchBoxFromUrl() {
		var searchString = "";
		if (getURLParameter('search')) {
			searchString = getSearchStringFromJSON(decodeURIComponent(getURLParameter('search')), false);
		}

		advisoryVisualSearch.searchBox.value(searchString);
	}
}


