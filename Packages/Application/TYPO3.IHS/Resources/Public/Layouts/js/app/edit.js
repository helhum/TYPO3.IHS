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

$(document).ready(function() {
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
		$(this).closest('.panel').find('.delete-objectCollection:first a').click();
	});

	// modal bg height fix
	$('.modal').on('shown.bs.modal', function () {
		$(this).find('.modal-backdrop').height($(this).find('.modal-dialog').outerHeight() + 60);
	});

	// open collabsable when field inside has error
	$('.f3-form-error').closest('.collapse').collapse('show');

	handleSaveDeletionModal();

	initIssue();
	$('body').on('dynamicFieldAdded', initIssue);
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

function handleSaveDeletionModal() {
	var href = '';
	var formFields = '';

	// show confirmation modal for deletion of objects
	$('.remove-action').on('click', function(event) {
		event.preventDefault();
		href = $(this).attr('href');
		formFields = $(this).closest('.panel').find('.collection-content:first > .form-group');
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
			var currentValue = $(select).val();
			$(select).val(ui.item.id);

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
		if($(element).hasClass('f3-form-error')) {
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