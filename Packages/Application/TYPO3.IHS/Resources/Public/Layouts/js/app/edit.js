var currentIssue = null,
	currentSolution = null,
	currentVersionsField = null;

$(document).ready(function() {
	var body = $('body');

	// highlight objectCollection when there are some form errors
	$('.f3-form-error').closest('.object').addClass('has-validation-error');

	//
	body.on('dynamicFieldAdded', function() {
		initAutocompletion();
		initDatetimepicker();
		initMarkdownEditor();
		initIssue();
		initSolution();
		initLink();
		initEditPanel();
	});

	body.on('initSorting', function() {
		initSorting();
	});

	// modal bg height fix
	$('.modal').on('shown.bs.modal', function() {
		$(this).find('.modal-backdrop').height($(this).find('.modal-dialog').outerHeight() + 60);
	});

	body.trigger('dynamicFieldAdded');
	initSorting();
});

// method handles sorting of object collections
function initSorting() {
	var currentObject = null;
	var direction = null;

	// init deletion of objects after sorting objects
	// needed because the actions on the links are removed
	initEditPanel();

	// reset ui
	$('.sort-object').attr('disabled', false);

	// write 0 to sortKey when empty
	if ($('input.sort-key').val() == "") {
		$('input.sort-key').val(0);
	}

	$('.object-collection').each(function(index, objectCollection) {
		$(objectCollection).find('.present-fields:first > .object').each(function(index, object) {
			// show or hide sorting buttons
			if (index == 0) {
				$(object).find('.object-actions:first .sort-object:first').attr('disabled', true);
			}
			if (index + 1 == $(objectCollection).find('.present-fields:first > .object').length) {
				$(object).find('.object-actions:first .sort-object:nth-child(2)').attr('disabled', true);
			}
			// write sortKey
			$(object).find('input.sort-key').val(index + 1);
		});
	});

	// sort object
	$('.sort-object').off('click');
	$('.sort-object').on('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		direction = $(this).attr('data-sort-direction');
		currentObject = $(this).closest('.panel');
		var clonedObject = $(currentObject).clone();

		$(clonedObject).hide();

		if (direction == 'up') {
			$(clonedObject).insertBefore($(currentObject).prev());
		} else {
			$(clonedObject).insertAfter($(currentObject).next());
		}

		$(clonedObject).slideDown();
		$(currentObject).slideUp(function() {
			$(this).remove();
			initSorting();
		});
	});
}

// method handles save deletion with callback to the user for objectCollections
function handleSaveDeletionModal(currentObject) {
	var deleteConfirmationModal = $('#delete-confirmation-modal'),
		formFields = '',
		href = '',
		disconnectModeIsActive = '';

	// trigger removing objectCollections
	$('.toggle-save-delete-action').off('click');
	$('.toggle-save-delete-action').on('click', function() {
		if ($(this).attr('data-delete-mode') == 'disconnect') {
			disconnectModeIsActive = true;
		} else {
			disconnectModeIsActive = false;
		}
		$(this).closest('.edit-panel-content').find('.delete-objectCollection:first a').click();
	});

	// show confirmation modal for deletion of objects
	$('.remove-action').off('click');
	$('.remove-action').on('click', function(event) {
		event.preventDefault();
		href = $(this).attr('href');
		formFields = $(this).closest('.form-fields').find('.form-group');
		deleteConfirmationModal.modal('show');
	});

	deleteConfirmationModal.off('show.bs.modal');
	deleteConfirmationModal.on('show.bs.modal', function() {
		if (disconnectModeIsActive) {
			$(deleteConfirmationModal).addClass('disconnect-mode');
		} else {
			$(deleteConfirmationModal).removeClass('disconnect-mode');
		}

		$(formFields).each(function(index, item) {
			var label = $(item).find('label').first().text();
			var value = $(item).find('input[type!="hidden"]').first().val();

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
		deleteConfirmationModal.find('a.remove-object').off('click')
		deleteConfirmationModal.find('a.remove-object').on('click', function(event) {
			event.preventDefault();
			var button = $(this);
			$(button).button('loading');

			$.ajax({
				type: "GET",
				contentType: "application/json; charset=utf-8",
				url: href,
				dataType: "json",
				success: function(data) {
					deleteConfirmationModal.modal('hide');

					var alert = $(alertPrototype).insertAfter($(currentObject)).addClass('alert-success');
					$(alert).find('.alert-message').text(data.message);

					$(currentObject).remove();
					$('body').trigger('initSorting');
					closeEditPanel();
				},
				complete: function() {
					$(button).button('reset')
				},
				error: function() {
					deleteConfirmationModal.modal('hide');

					var alert = $(alertPrototype).insertAfter($(currentObject)).addClass('alert-danger');
					$(alert).find('.alert-message').text('There was an error deleting the object.');
				}
			});
		});
	});

	deleteConfirmationModal.off('hide.bs.modal');
	deleteConfirmationModal.on('hide.bs.modal', function() {
		// write name to the modals body
		// maybe iterate over the form fields and write them to the modal?
		deleteConfirmationModal.find('.modal-body .place-for-fields').html('');
		deleteConfirmationModal.find('a.remove-object').attr('href', '');
	});
}
var autocompletionCache = {},
	autocompletionLastTerm = {};

// method handles autocompletion for fields that support this feature
// TODO: Fix cache problem where different fields use the same cache
function initAutocompletion() {
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
			var propertyName = $(this.element).data('cache'),
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
			var propertyName = $(this.element).data('cache');
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
		timeFormat: 'HH:mm',
		dateFormat: 'dd.mm.yy',
		separator: ' - '
	});
}

function initMarkdownEditor() {
	var markdownElements = $('.markdown').not('.object-collection .markdown');
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
	// if there is a selected product: populate autocompletion field and get versions for this product
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
	});

	$('input.uri').autocomplete('option', 'source', function(request, response) {
		var propertyName = $(this.element).data('cache');

		if (request.term.indexOf('asset://') == 0) {
			var term = request.term.substring(8, request.term.length);
			if (typeof autocompletionCache[propertyName] === 'undefined') {
				autocompletionCache[propertyName] = {};
			}
			autocompletionLastTerm[propertyName] = term;
			if (term in autocompletionCache[propertyName]) {
				response(autocompletionCache[propertyName][term]);
				return;
			}

			request.term = term;
			$.getJSON($(this.element).data('ajaxurl'), request, function(data) {
				autocompletionCache[propertyName][term] = data;
				response(data);
			});
		}
	}).on('autocompleteselect', function(event, ui) {
		currentLink = $(event.target).parents('.link');
		$(currentLink).find('.uri').attr('readonly', 'readonly');
		var assetValue = $(currentLink).find('.asset').val(),
			uriValue = $(currentLink).find('.uri').val(),
			term = (uriValue.indexOf('asset://') == 0) ? uriValue.substring(8, uriValue.length) : uriValue;

		$(currentLink).find('div.selected-asset ul').append('<li class="selected-asset" data-id="' + assetValue + '">' + term + ' <button type="button" class="delete-created-version btn btn-danger btn-sm"><i class="glyphicon glyphicon-trash"></i> delete</button></li>');
		initLink();
	});

	$('.affectedVersions').off('change');
	$('.affectedVersions').on('change', function(event) {
		currentIssue = $(event.target).parents('.issue');
		updateFixedInVersions();
	});

	$('.issue').each(function(indexInArray, value) {
		currentIssue = value;
		updateFixedInVersions();
	});
}

function initSolution() {
	// update fixedInVersions list if none is set
	$('.fixed-in-versions').each(function(indexInArray, fixedInVersionsSelectField) {
		var fixedInVersions = $(fixedInVersionsSelectField).find('.fixedInVersions');
		var issueIdentifier = $(fixedInVersionsSelectField).find('input.parent-issue').val();
		var amountOfFixedInVersions = fixedInVersions.children('option').length;
		if (amountOfFixedInVersions == 1 && fixedInVersions.children('option').val() == "" || amountOfFixedInVersions == 0) {
			var issue = "";
			// find the matching issue
			$('input.current-issue').each(function() {
				if ($(this).val() == issueIdentifier) {
					issue = $(this).closest('.form-fields')
				}
			});
			var affectedVersions = $(issue).find('.affectedVersions');

			if ($(affectedVersions).children('option').length > 1 || $(affectedVersions).children('option').val() != "") {
				fixedInVersions.html('');
				$(affectedVersions).children('option').each(function(indexInArray, value) {
					var option = $(value);
					fixedInVersions.append('<option value="' + option.val() + '">' + option.text() + '</option>');
				});
			}
		}
	});

	// create new versions when adding solutions
	var addNewVersionsButton = $('.add-new-versions');
	addNewVersionsButton.off('click');
	addNewVersionsButton.on('click', function() {
		$('#new-versions-modal').children('.modal-body').html('loading...');
		$.ajax({
			type: 'GET',
			url: $(this).data('ajaxurl'),
			success: function(data) {
				// TODO: may not work with multiple issue instances
				$('#new-versions-modal').find('.modal-body').html(data);
			}
		});

		currentSolution = $(this).closest('.form-fields');
		currentVersionsField = $(currentSolution).find('select.fixedInVersions');
	});

	var createVersionsButton = $('.create-versions');
	createVersionsButton.off('click');
	createVersionsButton.on('click', function(event) {
		event.preventDefault();
		$(this).button('loading');
		$('#version-modal').find('.modal-body .status-message').remove();

		var versions = $('#product-new-versions').val().split('\n');
		var versionsJSON = JSON.stringify(versions);
		var productIdentifier = $(currentSolution).find('.product-value').val();

		$.ajax({
			type: 'GET',
			url: $(this).attr('href'),
			dataType: 'json',
			data: {versions: versionsJSON, product: productIdentifier},
			success: function(data) {
				if (data.status = 'success') {
					var createdVersions = data.createdVersions;
					$(createdVersions).each(function(key, value) {
						// add and select new version in the current solution
						$(currentIssue).find('select.affectedVersions').append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentIssue).closest('.issue-outer').find('select.fixedInVersions').append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentVersionsField).append('<option value="' + value.identifier + '">' + value.versionAsString + '</option>');
						$(currentVersionsField).find('option[value="' + value.identifier + '"]').prop('selected', true);

						// add new version to list of created versions
						$(currentSolution).find('div.created-versions ul').append('<li class="created-version" data-version-id="' + value.identifier + '">' + value.versionAsString + ' <button type="button" class="delete-created-version btn btn-danger btn-sm"><i class="glyphicon glyphicon-trash"></i> delete</button></li>')
						$(currentSolution).find('div.created-versions-outer').show();

						// add click action for new versions to delete them
						$('div.created-versions li[data-version-id="' + value.identifier + '"] .delete-created-version').on('click', function() {
							var button = $(this);
							$(button).button('loading');
							$.ajax({
								type: "GET",
								url: $('.created-versions').attr('data-delete-url'),
								dataType: "json",
								data: {'productVersion': value.identifier, 'product': productIdentifier},
								success: function() {
									$(button).closest('.created-version').remove();
									$(currentVersionsField).find('option[value="' + value.identifier + '"]').remove();
									$(currentIssue).find('select.affectedVersions').find('option[value="' + value.identifier + '"]').remove();
									$(currentIssue).closest('.issue-outer').find('select.fixedInVersions').find('option[value="' + value.identifier + '"]').remove();

									if ($(currentSolution).find('.created-versions-outer ul li').length === 0) {
										$(currentSolution).find('div.created-versions-outer').hide();
									}
								}
							});
						});
					});
					$('#product-new-versions').val('');
					$('#new-versions-modal').find('.modal-body').append('<div class="alert alert-success"><p class="status-message">' + data.message + '</p></div>');
				}
			},
			complete: function() {
				$('.create-versions').button('reset')
			}
		});
	});

}

function initLink() {
	$('div.selected-asset ul li button').off('click');
	$('div.selected-asset ul li button').on('click', function(event, ui) {
		var currentLink = $(event.target).parents('.link');
		$(currentLink).find('.asset').val('');
		$(currentLink).find('.uri').removeAttr('readonly').val('');
		$(currentLink).find('div.selected-asset ul li').remove();
	});
}

function getVersionsForProduct(identifier) {
	$.ajax({
		type: 'GET',
		url: $(currentIssue).find('.product').data('versionsurl'),
		data: {"identifier": identifier},
		dataType: 'json'
	}).success(function(data) {
		var affectedVersionsContainer = $(currentIssue).find('.affected-versions').first();
		var affectedVersionsSelect = $(affectedVersionsContainer).find('select.affectedVersions');
		var selectedAffectedVersions = $(affectedVersionsContainer).find('.selected-affected-versions');
		var issueIdentifier = $(currentIssue).find('input.current-issue').first().val();
		$(affectedVersionsSelect).html(''); // clear version list from prev request
		$(affectedVersionsContainer).slideDown(); // show versions selector

		// append versions from ajax request to the selector
		$(data).each(function(key, data) {
			$(affectedVersionsSelect).append('<option value="' + data.id + '">' + data.value + '</option>');
		});

		// trigger focusout of the affected versions to trigger syncing in the edit-panel
		$(affectedVersionsSelect).trigger('focusout');

		// select options
		if ($(selectedAffectedVersions).html().trim() != '') {
			var selectedVersions = JSON.parse($('.selected-affected-versions').html());
			$(selectedVersions).each(function(key, value) {
				$($(affectedVersionsSelect).children('option[value="' + value + '"]')[0]).prop('selected', true);
			});
		}

		// update versions for affected solutions
		$('.fixed-in-versions').each(function() {
			if ($(this).find('input.parent-issue').val() == issueIdentifier) {
				var solution = $(this);
				var fixedInVersions = $(solution.find('.fixedInVersions'));

				fixedInVersions.html('');
				$(data).each(function(key, data) {
					fixedInVersions.append('<option value="' + data.id + '">' + data.value + '</option>');
				});
			}
		});
	});
}

// updates the fixed in versions for every solution
// depending on the current selected affected versions
function updateFixedInVersions() {
	var issue = $(currentIssue);
	var affectedVersions = $(issue).find('.affectedVersions option:selected');
	var issueIdentifier = $(issue).find('input.current-issue').first().val();

	// reset by showing all possible versions
	$('.fixed-in-versions').each(function() {
		if ($(this).find('input.parent-issue').val() == issueIdentifier) {
			$(this).find('.fixedInVersions option').css('display', 'block');
		}
	});

	// hide affected versions in the solutions
	affectedVersions.each(function(indexInArray, value) {
		$('.fixed-in-versions').each(function() {
			if ($(this).find('input.parent-issue').val() == issueIdentifier) {
				var version = $(this).find('.fixedInVersions option[value="' + $(value).val() + '"]');
				version.removeAttr('selected');
				version.css('display', 'none');
			}
		});
	});
}

(function($) {
	function DynamicField(element) {
		var self = this;

		this.$element = $(element);

		this.$fields = this.$element.find('.present-fields').first();
		this.$button = this.$element.find('> .fields-header .add-field button').first();
		this.htmlTemplate = this.$element.find('.field-template').first().html();
		this.iterationIndex = 0;
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

			self.setIterationIndex();

			var newFields = self.getHtmlForIndex(self.iterationIndex);
			$(newFields).html(newFields);
			$(newFields).appendTo(self.$fields);

			// replace placeholder for index in various attributes
			self.$fields.find('[id*="__iteratorIndex__"]').each(function() {
				self.replaceIteratorIndex(this, 'id', self.iterationIndex);
			});

			self.$fields.find('[href*="__iteratorIndex__"]').each(function() {
				self.replaceIteratorIndex(this, 'href', self.iterationIndex);
			});

			var title = self.$fields.find('.panel-title:contains("__iteratorIndex__")');
			var newTitle = title.text().replace(/__iteratorIndex__/g, self.iterationIndex);
			if (title != newTitle) {
				title.text(newTitle);
			}

			// solution - clear list of fixed in versions
			// list is added in initSolution()
			$(self.$fields).find('.new-object .fixed-in-versions').each(function() {
				$(this).find('.fixedInVersions').html("");
			});

			// copy fields to edit-panel
			openEditPanel(newTitle, $(self.$fields).find('.new-object'));

			$(self.$fields).find('.new-object .existing-issue').removeClass('existing-issue');
			$(self.$fields).find('.new-object').removeClass('new-object');


			$('body').trigger('dynamicFieldAdded');
			$('body').trigger('initSorting');

			init();
		});

		this.getHtmlForIndex = function(index) {
			return this.htmlTemplate.replace(/^(.+?)(\[_placeholder_\])(\[.+\])(?:\[_placeholder_\]){0,1}(.+)$/gm, "$1[" + index + "]$3$4");
		};

		this.setIterationIndex = function() {
			this.iterationIndex = this.$fields.find('> .object').length;
		};

		this.replaceIteratorIndex = function(element, attr, index) {
			var newAttr = $(element).attr(attr);
			newAttr = newAttr.replace(/__iteratorIndex__/g, index);
			$(element).attr(attr, newAttr);
		};

		this.$element.children(".dynamic-fields").removeClass('dynamic-fields');

		$('body').trigger('dynamicFieldAdded');
	}

	function init() {
		$('.dynamic-fields').each(function(index, element) {
			new DynamicField($(element).closest('.fields'));
		});
	}

	init();
})(jQuery);

function initEditPanel() {
	$('.open-in-edit-panel').off('click');
	$('.open-in-edit-panel').on('click', function() {
		var objectTitle = $(this).text();
		var currentObject = $(this).closest('.object');

		if ($(this).hasClass('is-open')) {
			closeEditPanel();
		} else {
			openEditPanel(objectTitle, currentObject);
		}
	});
}

function syncEditPanelChanges(currentFields) {
	$('.edit-panel-content input, .edit-panel-content textarea').each(function() {
		$(currentFields).find('*[name="' + $(this).attr('name') + '"]').val($(this).val());
	});

	$('.edit-panel-content select').each(function() {
		$(currentFields).find('select[name="' + $(this).attr('name') + '"]').html($(this).html());
		$(currentFields).find('select[name="' + $(this).attr('name') + '"]').val($(this).val());
	});

	// sync the title field to the displayed title in the left panel
	$('input[data-sync-form-field="title"]').each(function() {
		$(currentFields).find('*[data-sync-form-field="title"]').first().text($(this).val());
	});
}

function openEditPanel(objectTitle, currentObject) {
	$('body').addClass('edit-panel-open');
	$('.edit-panel-content').html('');
	$(currentObject).find('.form-fields').first().clone().appendTo('.edit-panel-content');
	$(currentObject).find('.form-fields-footer').first().clone().appendTo('.edit-panel-content');
	$(currentObject).find('textarea').each(function() {
		$('.edit-panel-content').find('*[name="' + $(this).attr('name') + '"]').val($(this).val());
	});
	$(currentObject).find('select').each(function() {
		$('.edit-panel-content').find('select[name="' + $(this).attr('name') + '"]').val($(this).val());
	});
	$('.panel-heading').removeClass('is-open');
	$(currentObject).find('.panel-heading').first().addClass('is-open');
	$('.edit-panel-headline').text(objectTitle);

	initAutocompletion();
	initDatetimepicker();
	initMarkdownEditor();
	initSolution();
	initIssue();
	initLink();

	$('.save-and-close-edit-panel').off('click');
	$('.save-and-close-edit-panel').on('click', function() {
		syncEditPanelChanges($(currentObject));
		closeEditPanel();
	});

	$('.close-edit-panel').off('click');
	$('.close-edit-panel').on('click', function() {
		closeEditPanel();
	});

	// remove newly added objects
	$('.edit-panel-content .toggle-delete-action').off('click');
	$('.edit-panel-content .toggle-delete-action').on('click', function() {
		closeEditPanel();
		var searchStatement = $(currentObject).attr('data-property-prefix');
		// find hidden input fields added by flow
		$(currentObject).closest('form').find('input[name*="' + searchStatement + '"]').remove();
		$(currentObject).slideUp(250, function() {
			$(this).remove();
			$('body').trigger('initSorting');
		});
	});

	// handle deletion of already persisted objects
	handleSaveDeletionModal(currentObject);
}

function closeEditPanel() {
	$('body').removeClass('edit-panel-open');
	$('.edit-panel-headline').html('');
	$('.edit-panel-content').html('');
	$('.panel-heading').removeClass('is-open');
}