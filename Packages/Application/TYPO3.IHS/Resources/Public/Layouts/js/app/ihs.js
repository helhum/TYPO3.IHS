(function($) {
	function DynamicField(element) {
		var self = this;

		this.$element = $(element);

		this.$presentFields = this.$element.find(".present-fields").first();
		this.$additionalLinkFieldsContainer = this.$element.find(".additional-fields").first();
		this.$button = this.$element.find("> .fields-header .add-field button").first();
		this.htmlTemplate = this.$element.find(".field-template").first().html();
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

			var newFields = self.getHtmlForIndex(self.iterationIndex);
			newFields = newFields.replace(/__iteratorIndex__/g, self.iterationIndex);
			newFields = self.$additionalLinkFieldsContainer.append(newFields);

			$('html, body').animate({
				scrollTop: $(newFields).offset().top
			}, 500);
			self.iterationIndex++;
			init();
		});

		this.getHtmlForIndex = function (index) {
			return this.htmlTemplate.replace(/^(.+?)(\[_placeholder_\])(\[.+\])(?:\[_placeholder_\]){0,1}(.+)$/gm, "$1[" + index + "]$3$4");
		};

		this.$element.children(".dynamic-fields").removeClass('dynamic-fields');

		$('body').trigger('dynamicFieldAdded');
	}

	function init() {
		$(".dynamic-fields").each(
			function(index, element) {
				new DynamicField($(element).closest('.fields'));
			}
		);

		$('.additional-fields .toggle-delete-action').on('click', function(event, ui) {
			$(event.target).closest('.panel').remove();
		});
	}

	init();

})(jQuery);

jQuery(document).ready(function() {
	// overwriting visualsearch searchbox template
	// added save search button
	window.JST['search_box'] = _.template('<div class="VS-search <% if (readOnly) { %>VS-readonly<% } %>">\n  <div class="VS-search-box-wrapper VS-search-box">\n    <div class="VS-icon VS-icon-search"></div>\n    <div class="VS-placeholder"></div>\n    <div class="VS-search-inner"></div>\n    <div class="VS-icon VS-icon-cancel VS-cancel-search-box" title="clear search"></div> <div class="VS-icon VS-save-search-box" title="save search"><i class="glyphicon glyphicon-star-empty"></i></div>\n  </div>\n</div>');

	// for every autocomplete field we change the indicator when the search is started and we get a response
	$(".autocomplete-field input").autocomplete({
		search: function( event, ui ) {
			$(this).closest('.autocomplete-field').find('.autocomplete-indicator').removeClass('glyphicon-search').addClass('glyphicon-refresh');
		},
		response: function( event, ui ) {
			$(this).closest('.autocomplete-field').find('.autocomplete-indicator').removeClass('glyphicon-refresh').addClass('glyphicon-search');
		}
	});

	$("input.datetimepicker").datetimepicker({
		timeFormat: "hh:mm",
		dateFormat: "dd.mm.yy",
		separator: ' - '
	});

	initializeMarkdownEditor();

	$('body').on('dynamicFieldAdded', function() {
		initializeMarkdownEditor();
	});

	// trigger removing objectCollections
	$('.toggle-delete-action').on('click', function() {
		$(this).closest('.panel').find('.delete-objectCollection a').click();
	});

	// open collabsable when field inside has error
	$('.f3-form-error').closest('.collapse').collapse('show');

	handleSaveDeletionModal();
});

function initializeMarkdownEditor() {
	$('.markdown').markdown({
		autofocus:false,
		savable:false,
		onPreview: function(e) {
			// we use a different markdown to html parser here to render html tags
			return marked(e.getContent());
		}
	});

	$('.markdown').autosize();
}

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

function handleSaveDeletionModal() {
	var href = '';
	var formFields = '';

	// show confirmation modal for deletion of objects
	$('.remove-action').on('click', function(event) {
		event.preventDefault();
		href = $(this).attr('href');
		formFields = $(this).closest('.panel').find('.form-group');
		$('#delete-confirmation-modal').modal('show');
	});

	$('#delete-confirmation-modal').on('show.bs.modal', function () {
		$(formFields).each(function(index, item) {
			var label = $(item).find('label').text();
			var value = $(item).find('input').val();

			if (typeof value == 'undefined') {
				value = $(item).find('textarea').val();
			}

			if (typeof value == 'undefined') {
				var options = $(item).find('select option:selected');
				value = '';
				$(options).each(function(key, option) {
					if (value != '') {
						value = value + ', ' + $(option).text();
					} else {
						value = $(option).text();
					}
				});
			}

			if (typeof label != 'undefined' && label.length > 0) {
				if (typeof value == 'undefined') {
					value = '';
				}

				if (value.length > 50) {
					value = value.substring(0, 50) + '...'
				}

				$('#delete-confirmation-modal .modal-body .place-for-fields').append('<p>' + label + ': ' + value + '</p>');
			}
		});

		$('#delete-confirmation-modal a.remove-object').attr('href', href);
	});

	$('#delete-confirmation-modal').on('hide.bs.modal', function () {
		// write name to the modals body
		// maybe iterate over the form fields and write them to the modal?
		$('#delete-confirmation-modal .modal-body .place-for-fields').html('');
		$('#delete-confirmation-modal a.remove-object').attr('href', '');
	});
}