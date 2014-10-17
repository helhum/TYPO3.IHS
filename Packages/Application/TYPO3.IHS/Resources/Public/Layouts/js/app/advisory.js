var visualSearch = false;

$( document ).ready(function(){
	var runningSearchRequest = false;
	var facets = [];

	// overwriting visualsearch searchbox template
	// added save search button
	window.JST['search_box'] = _.template('<div class="VS-search <% if (readOnly) { %>VS-readonly<% } %>">\n  <div class="VS-search-box-wrapper VS-search-box">\n    <div class="VS-icon VS-icon-search"></div>\n    <div class="VS-placeholder"></div>\n    <div class="VS-search-inner"></div>\n    <div class="VS-icon VS-icon-cancel VS-cancel-search-box" title="clear search"></div> <div class="VS-icon VS-save-search-box" title="save search"><i class="icon-star-empty"></i></div>\n  </div>\n</div>');

	visualSearch = VS.init({
		container : $('.advisory-visualsearch'),
		query     : '',
		callbacks : {
			search: function(query, searchCollection) {
				// output request as raw text
				$(".advisory-searchquery .searchquery-output").text(visualSearch.searchBox.value());

				// send request
				var searchQueryJSON = JSON.stringify(visualSearch.searchQuery.facets());

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
						if(visualSearch.searchBox.value()) {
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
		if (visualSearch.searchQuery.facets().length > 0) {
			var savedSearchesJSON = localStorage.getItem('savedAdvisorySearches');
			var savedSearches = [];
			if (savedSearchesJSON) {
				savedSearches = JSON.parse(savedSearchesJSON);
			}

			savedSearches.push(visualSearch.searchQuery.facets());
			savedSearchesJSON = JSON.stringify(savedSearches);

			localStorage.setItem('savedAdvisorySearches', savedSearchesJSON);
			displaySavedSearches();
		}

	});

	function displaySavedSearches() {
		$(".saved-searches").html("");
		var savedSearchesJSON = localStorage.getItem('savedAdvisorySearches');
		var savedSearches = JSON.parse(savedSearchesJSON);
		if (savedSearches.length > 0) {
			$(".saved-searches-container").show();
			$(".list-of-advisories").addClass("span9");
		} else {
			$(".saved-searches-container").hide();
			$(".list-of-advisories").removeClass("span9");
		}

		$(savedSearches).each(function(key, savedSearch) {
			var searchQueryJSON = JSON.stringify(savedSearch);
			$(".saved-searches").append("<li><button key='"+key+"' class='remove-saved-search btn btn-mini btn-danger'><i class='icon-trash icon-white'></i></button><a class='saved-search' href='"+window.location.pathname + "?search="+encodeURIComponent(searchQueryJSON)+"'>"+getSearchStringFromJSON(searchQueryJSON, true)+"</a></li>");
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

		visualSearch.searchBox.setQuery(searchString);
	}

	function getSearchStringFromJSON(searchStringAsJSON, humanReadable) {
		var searchQuery = JSON.parse(searchStringAsJSON);
		var searchString = "";

		$(searchQuery).each(function(key, searchPart) {
			if (searchPart && searchPart.text) {
				searchString = searchString + searchPart.text;
			} else {
				searchString = searchString + _.keys(searchPart)[0] + ": " + searchPart[_.keys(searchPart)[0]];
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
}

function getURLParameter(name) {
	return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}
