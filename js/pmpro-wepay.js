(function() {
    
	console.log(pmprowepay);
	
	//change sandbox/live to staging/production 
	WePay.set_endpoint(pmprowepay.environment); // change to "production" when live

    // Shortcuts
    var d = document;
        d.id = d.getElementById,
        valueById = function(id) {
            return d.id(id).value;
        };

    // For those not using DOM libraries
    var addEvent = function(e,v,f) {
        if (!!window.attachEvent) { e.attachEvent('on' + v, f); }
        else { e.addEventListener(v, f, false); }
    };

    // Attach the event to the DOM
    jQuery('.pmpro_btn-submit-checkout').click(function() {
		//add first and last name if not blank		TODO: Make sure we avoid issues with users with no names.
		var name;
		if (jQuery('#bfirstname').length && jQuery('#blastname').length)
			name = jQuery.trim(jQuery('#bfirstname').val() + ' ' + jQuery('#blastname').val());
		else
			name = 'John Doe';
		
		var userName = name;
		var args = {
			"client_id":        pmprowepay.client_id,
			"user_name":        name,
			"email":            valueById('bemail'),
			"cc_number":        valueById('AccountNumber'),
			"cvv":              valueById('CVV'),
			"expiration_month": valueById('ExpirationMonth'),
			"expiration_year":  valueById('ExpirationYear'),		
		};
		if(pmprowepay.billingaddress)
			args["address"] = {
				//add other address fields
				"zip": valueById('bzipcode')
			};
					
		response = WePay.credit_card.create(args, function(data) {
			if (data.error) {
				console.log(data);
				// handle error response
				// re-enable the submit button
				jQuery('.pmpro_btn-submit-checkout').removeAttr("disabled");

				//hide processing message
				jQuery('#pmpro_processing_message').css('visibility', 'hidden');

				// show the errors on the form
				alert(data.error);
				jQuery(".payment-errors").text(data.error);
			} else {
				// call your own app's API to save the token inside the data;
				// show a success page
				//console.log(data);
				
				var form$ = jQuery("#pmpro_form, .pmpro_form");
				// token contains id, last4, and card type
				
				var token = response['id'];
				// insert the token into the form so it gets submitted to the server
				form$.append("<input type='hidden' name='WePayToken' value='" + token + "'/>");
				
				//insert fields for other card fields				
				form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXXX" + response['card']['last4'] + "'/>");
				form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + response['card']['exp_month']).slice(-2) + "'/>");
				form$.append("<input type='hidden' name='ExpirationYear' value='" + response['card']['exp_year'] + "'/>");

				// and submit
				form$.get(0).submit();
			}
		});
    });
})();