var advisoryVisualSearch = false; // global instance of visual search for advisories

$( document ).ready(function(){
	var runningSearchRequest = false;
	var facets = [];

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
					data: {searchRequest: searchQueryJSON},
					success: function(data) {
						$(".list-of-advisories .articles").html(data);

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
					'category', 'vulnerability type'
				]);
			},
			valueMatches: function(facet, searchTerm, callback) {
				switch (facet) {
					case 'category':
						if (facets['category']) {
							callback(facets['category']);
						} else {
							$.ajax({
								type: "GET",
								url: "/product/getProductTypesAsJSON",
								dataType: "json",
								success: function(types) {
									callback(types);
									facets['category'] = types;
								}
							});
						}

						break;
					case 'vulnerability type':
						callback(['low', 'medium', 'high']);
						break;
				}
			}
		}
	});

	handleSavedSearches();
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
		$(".advisories .saved-searches").html("");
		var savedSearchesJSON = localStorage.getItem('savedAdvisorySearches');
		var savedSearches = JSON.parse(savedSearchesJSON);
		if (savedSearches && savedSearches.length > 0) {
			$(".advisories .saved-searches-container").show();
			$(".advisories .list-of-advisories").addClass("span9");
		} else {
			$(".advisories .saved-searches-container").hide();
			$(".advisories .list-of-advisories").removeClass("span9");
		}

		$(savedSearches).each(function(key, savedSearch) {
			var searchQueryJSON = JSON.stringify(savedSearch);
			$(".advisories .saved-searches").append("<li><button key='"+key+"' class='remove-saved-search btn btn-mini btn-danger'><i class='icon-trash icon-white'></i></button><a class='saved-search' href='"+window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON)+"'>"+getSearchStringFromJSON(searchQueryJSON, true)+"</a></li>");
		});

		$('.advisories .remove-saved-search').on('click', function() {
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


