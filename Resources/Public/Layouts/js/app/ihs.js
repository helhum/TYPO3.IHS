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
				self.htmlTemplate = parentEl.html() + self.htmlTemplate;
				parentEl.remove();
			});
		}

		this.$button.on('click', function(event) {
			event.preventDefault();

			self.$additionalLinkFieldsContainer.append(self.getHtmlForIndex(self.iterationIndex));
			self.iterationIndex++;

		});

		this.getHtmlForIndex = function (index) {
			return this.htmlTemplate.replace(/\]\[_placeholder_\]\[/g, "][" + index + "][");
		}
	}

	$(".dynamic-fields").each(
		function(index, element) {
			new DynamicField(element);
		}
	);

})(jQuery);