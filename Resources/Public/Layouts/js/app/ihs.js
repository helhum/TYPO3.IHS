(function($) {


	function DynamicField(element) {
		var self = this;

		this.$element = $(element);

		this.$presentFields = this.$element.find(".present-fields");
		this.$additionalLinkFieldsContainer = this.$element.find(".additional-fields");
		this.$button = this.$element.find("button.add-field");
		this.htmlTemplate = this.$element.find(".field-template").data("html");
		this.iterationIndex = this.$presentFields.children().length;

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