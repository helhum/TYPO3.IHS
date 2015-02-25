var currentIssue = null,
	currentSolution = null,
	currentVersionsField = null;

$(document).ready(function() {
	var body = $('body');

	handleSaveDeletionModal();
	// open collabsable when field inside has error
	$('.f3-form-error').closest('.collapse').collapse('show');

	//
	body.on('dynamicFieldAdded', function() {
		initAutocompletion();
		initDatetimepicker();
		initMarkdownEditor();
		initIssue();
	});

	// trigger removing objectCollections
	$('.toggle-delete-action').on('click', function() {
		$(this).closest('.panel').find('.delete-objectCollection:first a').click();
	});

	// modal bg height fix
	$('.modal').on('shown.bs.modal', function() {
		$(this).find('.modal-backdrop').height($(this).find('.modal-dialog').outerHeight() + 60);
	});

	body.trigger('dynamicFieldAdded');
});

function handleSaveDeletionModal() {
	var deleteConfirmationModal = $('#delete-confirmation-modal'),
		formFields = '',
		href = '';

	// show confirmation modal for deletion of objects
	$('.remove-action').on('click', function(event) {
		event.preventDefault();
		href = $(this).attr('href');
		formFields = $(this).closest('.panel').find('.collection-content:first > .form-group');
		deleteConfirmationModal.modal('show');
	});

	deleteConfirmationModal.on('show.bs.modal', function() {
		$(formFields).each(function(index, item) {
			var label = $(item).find('label').text();
			var value = $(item).find('input').val();

			if (typeof value === 'undefined') {
				value = $(item).find('textarea').val();
			}

			if (typeof value === 'undefined') {
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

				deleteConfirmationModal.find('.modal-body .place-for-fields').append('<p>' + label + ': ' + value + '</p>');
			}
		});

		deleteConfirmationModal.find('a.remove-object').attr('href', href);
	});

	deleteConfirmationModal.on('hide.bs.modal', function() {
		// write name to the modals body
		// maybe iterate over the form fields and write them to the modal?
		deleteConfirmationModal.find('.modal-body .place-for-fields').html('');
		deleteConfirmationModal.find('a.remove-object').attr('href', '');
	});
}

function initAutocompletion() {
	var autocompletionCache = {},
		autocompletionLastTerm = {};

	$('input.ajax').autocomplete({
		minLength: 1,
		max: 10,
		select: function(event, ui) {
			var hidden = $(event.target).siblings('input.ajax-value')[0];
			if (typeof hidden !== 'undefined') {
				$(hidden).val(ui.item.id);
			}
		},
		source: function(request, response) {
			var propertyName = $(this.element).attr('id'),
				term = request.term;
			if (typeof autocompletionCache[propertyName] === 'undefined') {
				autocompletionCache[propertyName] = {};
			}
			autocompletionLastTerm[propertyName] = term;
			if (term in autocompletionCache[propertyName]) {
				response(autocompletionCache[propertyName][term]);
				return;
			}

			$.getJSON($(this.element).data('ajaxurl'), request, function(data) {
				autocompletionCache[propertyName][term] = data;
				response(data);
			});
		}
	}).click(function() {
		if ($(this).val()) {
			var propertyName = $(this.element).attr('id');
			$(this).autocomplete('search', autocompletionLastTerm[propertyName]);
		}
	});

	// set form error class to autocompletion field if product field has one
	$('input.ajax-value').each(function(index, element) {
		if($(element).hasClass('f3-form-error')) {
			$(element).siblings('input.ajax').first().addClass('f3-form-error');
		}
	});

	// for every autocomplete field we change the indicator when the search is started and we get a response
	$('.autocomplete-field input').autocomplete({
		search: function() {
			$(this).closest('.autocomplete-field').find('.autocomplete-indicator').removeClass('glyphicon-search').addClass('glyphicon-refresh');
		},
		response: function() {
			$(this).closest('.autocomplete-field').find('.autocomplete-indicator').removeClass('glyphicon-refresh').addClass('glyphicon-search');
		}
	});
}

function initDatetimepicker() {
	$('input.datetimepicker').datetimepicker({
		timeFormat: 'hh:mm',
		dateFormat: 'dd.mm.yy',
		separator: ' - '
	});
}

function initMarkdownEditor() {
	var markdownElements = $('.markdown');
	markdownElements.markdown({
		autofocus:false,
		savable:false,
		onPreview: function(e) {
			// we use a different markdown to html parser here to render html tags
			return marked(e.getContent());
		}
	});
}

function initIssue() {
	// if there is a selected product populate autocompletion field and get versions for this product
	$('input.product-value').each(function(index, element) {
		currentIssue = $(element).parents('.issue');
		if ($(element).val()) {
			var versions = $(currentIssue).find('.affected-versions').first();

			// get versions for product if none exists
			if (!$(versions).find('select option:first-child').val() && $(versions).find('select option').length == 1) {
				getVersionsForProduct($(element).children('option:selected').first().val(), element)
			} else {
				$(versions).show();
			}
		}
	});

	//
	$('input.product').on('autocompleteselect', function(event, ui) {
		currentIssue = $(event.target).parents('.issue');
		getVersionsForProduct(ui.item.id);
	}).on('keyup', function() {
		if ($(this).val() == '') {
			$('#affected-versions').slideUp();
		}
	});

	// create new versions when adding solutions

	var addNewVersionsElement = $('.add-new-versions');
	addNewVersionsElement.off('click');
	addNewVersionsElement.on('click', function() {
		$('#new-versions-modal').children('.modal-body').html('loading...');
		$.ajax({
			type: 'GET',
			url: $(this).data('ajaxurl'),
			success: function(data) {
				console.log($('#new-versions-modal'));
				// TODO: may not work with multiple issue instances
				$('#new-versions-modal').find('.modal-body').html(data);
			}
		});

		currentIssue = $(this).parents('.issue');
		currentSolution = $(this).parents('.solution');
		currentVersionsField = $(currentSolution).find('select.fixedInVersions');
	});

	var createVersionsElement = $('.create-versions');
	createVersionsElement.off('click');
	createVersionsElement.on('click', function(event) {
		event.preventDefault();
		$(this).button('loading');
		$('#version-modal').find('.modal-body .status-message').remove();

		var versions = $('#product-new-versions').val().split('\n');
		var versionsJSON = JSON.stringify(versions);
		var productIdentifier = $(currentIssue).find('.product-value').val();

		$.ajax({
			type: 'GET',
			url: $(this).attr('href'),
			dataType: 'json',
			data: {versions: versionsJSON, product: productIdentifier},
			success: function(data) {
				if (data.status = 'success') {
					//todo add productversions to select fields
					var createdVersions = data.createdVersions;
					$(createdVersions).each(function(key, value) {
						$(currentVersionsField).append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentVersionsField).find('option[value="' + value.identifier + '"]').prop('selected', true);

						// add new version to list of created versions
						$(currentSolution).find('div.created-versions ul').append('<li class="created-version" data-version-id="' + value.identifier + '">' + value.versionAsString + ' <button type="button" class="delete-created-version btn btn-danger btn-sm"><i class="glyphicon glyphicon-trash"></i> delete</button></li>')
						$(currentSolution).find('div.created-versions-outer').show();

						// add click action for new versions to delete them
						$('div.created-versions li[data-version-id="' + value.identifier + '"] .delete-created-version').on('click', function() {
							var button = $(this);
							$.ajax({
								type: "GET",
								url: $('.created-versions').attr('data-delete-url'),
								dataType: "json",
								data: {'productVersion': value.identifier, 'product': productIdentifier},
								success: function() {
									$(button).closest('.created-version').remove();
									$(currentVersionsField).find('option[value="' + value.identifier + '"]').remove();

									if ($(currentSolution).find('.created-versions-outer ul li').length === 0) {
										$(currentSolution).find('div.created-versions-outer').hide();
									}
								}
							});
						});
					});
					$('#product-new-versions').val('');
					$('#new-versions-modal').find('.modal-body').append('<p class="status-message">' + data.message + '</p>');
				}
			},
			complete: function() {
				$('.create-versions').button('reset')
			}
		});
	});
}

function getVersionsForProduct(identifier) {
	$.ajax({
		type: 'GET',
		url: $(currentIssue).find('.product').data('versionsurl'),
		data: {"identifier": identifier},
		dataType: 'json'
	}).success(function(data) {
		var versions = $(currentIssue).children('.affected-versions').first();
		var versionsSelect = $(versions).find('select.affectedVersions');
		var selectedVersionsSelect = $(versions).find('.selected-affected-versions');
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

(function($) {
	function DynamicField(element) {
		var self = this;

		this.$element = $(element);

		this.$presentFields = this.$element.find('.present-fields').last();
		this.$additionalFieldsContainer = this.$element.find('.additional-fields').last();
		this.$button = this.$element.find('> .fields-header .add-field button').first();
		this.htmlTemplate = this.$element.find('.field-template').first().html();
		this.iterationIndex = this.$presentFields.children().length;
		this.argumentName = this.htmlTemplate.match(/"([^"]*)\[_placeholder_\]([^"]*)"/)[1];

		// Checkboxes and multi select fields may have rendered hidden fields that need to be handled
		this.$hiddenFields = $('input[type="hidden"][name*="' + this.argumentName + '[_placeholder_]"]');
		if (this.$hiddenFields.length > 0) {
			this.$hiddenFields.each(function(index, element) {
				// Trick to get the html of the actual element, see http://stackoverflow.com/questions/6459398/jquery-get-html-of-container-including-the-container-itself
				var parentEl = $(element).wrap('<p/>').parent();
				parentEl.remove();
			});
		}

		this.$button.on('click', function(event) {
			event.preventDefault();

			var newFields = self.getHtmlForIndex(self.iterationIndex);
			newFields = newFields.replace(/__iteratorIndex__/g, self.iterationIndex);
			newFields = self.$additionalFieldsContainer.append(newFields);

			$('html, body').animate({
				scrollTop: $(newFields).offset().top
			}, 500);
			self.iterationIndex++;
			init();
		});

		this.getHtmlForIndex = function(index) {
			return this.htmlTemplate.replace(/^(.+?)(\[_placeholder_\])(\[.+\])(?:\[_placeholder_\]){0,1}(.+)$/gm, "$1[" + index + "]$3$4");
		};

		this.$element.children(".dynamic-fields").removeClass('dynamic-fields');

		$('body').trigger('dynamicFieldAdded');
	}

	function init() {
		$('.dynamic-fields').each(function(index, element) {
			new DynamicField($(element).closest('.fields'));
		});

		$('.additional-fields .toggle-delete-action').on('click', function(event) {
			$(event.target).closest('.panel').remove();
		});
	}

	init();
})(jQuery);