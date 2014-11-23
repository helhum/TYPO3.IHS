(function($) {
	function DynamicField(element) {
		var self = this;

		this.$element = $(element);

		this.$presentFields = this.$element.children(".present-fields").first();
		this.$additionalLinkFieldsContainer = this.$element.children(".additional-fields").first();
		this.$button = this.$element.children("button.add-field").first();
		this.htmlTemplate = this.$element.children(".field-template").first().data("html");
		this.iterationIndex = this.$presentFields.children().length;
		this.argumentName = this.htmlTemplate.match(/"([^"]*)\[_placeholder_\]([^"]*)"/)[1];

		// Checkboxes and multi selcet fields may have rendered hidden fields that need to be handled
		this.$hiddenFields = $("input[type='hidden'][name*='" + this.argumentName + "[_placeholder_]']");
		if (this.$hiddenFields.length > 0) {
			this.$hiddenFields.each(function(index, element) {
				// Trick to get the html of the actual element, see http://stackoverflow.com/questions/6459398/jquery-get-html-of-container-including-the-container-itself
				var parentEl = $(element).wrap("<p/>").parent();
				//self.htmlTemplate = parentEl.html() + self.htmlTemplate;
				parentEl.remove();
			});
		}

		this.$button.on('click', function(event) {
			event.preventDefault();

			self.$additionalLinkFieldsContainer.append(self.getHtmlForIndex(self.iterationIndex));
			self.iterationIndex++;
			init();
		});

		this.getHtmlForIndex = function (index) {
			return this.htmlTemplate.replace(/^(.+?)(\[_placeholder_\])(\[.+\])(?:\[_placeholder_\]){0,1}(.+)$/gm, "$1[" + index + "]$3$4");
		};

		this.$element.removeClass('dynamic-fields');

		$('body').trigger('dynamicFieldAdded');
	}

	function init() {
		$(".dynamic-fields").each(
			function(index, element) {
				new DynamicField(element);
			}
		);

		$('.delete-item').on('click', function(event, ui) {
			$(event.target).closest('ul').first().remove();
		});
	}
	init();

	$("input.datetimepicker").datetimepicker({
		timeFormat: "hh:mm",
		dateFormat: "dd.mm.yy",
		separator: ' - '
	});
})(jQuery);

jQuery(document).ready(function() {
	// overwriting visualsearch searchbox template
	// added save search button
	window.JST['search_box'] = _.template('<div class="VS-search <% if (readOnly) { %>VS-readonly<% } %>">\n  <div class="VS-search-box-wrapper VS-search-box">\n    <div class="VS-icon VS-icon-search"></div>\n    <div class="VS-placeholder"></div>\n    <div class="VS-search-inner"></div>\n    <div class="VS-icon VS-icon-cancel VS-cancel-search-box" title="clear search"></div> <div class="VS-icon VS-save-search-box" title="save search"><i class="icon-star-empty"></i></div>\n  </div>\n</div>');

	$(".markdown").markdown({
		autofocus:false,
		savable:false
	});

	// make modal reset remote content every time it is opened
	$('.modal').on('hidden', function() {
		$(this).removeData('modal');
	});

});

function getURLParameter(name) {
	return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}

function getSearchStringFromJSON(searchStringAsJSON, humanReadable) {
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