(function($) {

	var form          = $("#slgf");
	var notification  = $(".slgf-notification");

	form.submit(function (e) {

		e.preventDefault();

		var name    = $("#slgf_name").val();
		var phone   = $("#slgf_phone").val();
		var email   = $("#slgf_email").val();
		var budget  = $("#slgf_budget").val();
		var message = $("#slgf_message").val();
		var time    = $("#slgf_time").val();

		$.ajax({
			url: slgf_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'slgf_process_form',
				security: slgf_ajax_object.ajax_nonce,
				name: name,
				time: time,
				phone: phone,
				email: email,
				budget: budget,
				message: message,
			},
			success: function( response ) {

				// Show notification
				form.html( response ).addClass('submitted');

				// Scroll to top of form
				$('html, body').animate({
					scrollTop: form.offset().top -300
				}, 1000);
			}
		})
	});

})( jQuery );
