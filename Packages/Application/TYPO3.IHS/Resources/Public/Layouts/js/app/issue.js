$( document ).ready(function(){

	var cache = {};
	var lastTerm = "";
	$("input#productAjax")
		.autocomplete({
			minLength: 1,
			max:10,
			source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
					response( cache[ term ] );
					return;
				} else {
					lastTerm = term;
				}

				$.getJSON( $("input#productAjax").attr("productsUrl"), request, function( data, status, xhr ) {
					cache[ term ] = data;
					response( data );
				});
			},
			select: function( event, ui ) {
				var currentValue = $("select#product").val();
				$("select#product").val(ui.item.id);

				if(ui.item.id != currentValue) {
					$("select#affectedVersions").html(""); // clear version list from prev request
					getVersionsForProduct(ui.item.id);
				}
			}
		})
		.click(function(){
			if($(this).val()) {
				$(this).autocomplete( "search", lastTerm);
			}
		})
		.on("keyup", function() {
			if($(this).val() == "") {
				$("#affected-versions").slideUp();
			}
		});


	if($("select#product").val()) {
		$("input#productAjax").val($("select#product option:selected").text());
		$("#affected-versions").show();
	}
});


function getVersionsForProduct(identifier) {
	$.ajax({
		type: "GET",
		url: $("input#productAjax").attr("versionsUrl"),
		data: { "identifier": identifier},
		dataType: "json"
	})
	.success(function( data ) {
		$("#affected-versions").slideDown();
		$(data).each(function(key, data) {
			$("select#affectedVersions").append("<option value='"+data.id+"'>"+data.value+"</option>")
		});
	});
}