<?php

	add_action( 'wp_enqueue_scripts', 'gdlrs_include_stripe_payment_script' );
	if( !function_exists('gdlrs_include_stripe_payment_script') ){
		function gdlrs_include_stripe_payment_script(){
			global $hostel_option;
			if( isset($_GET[$hostel_option['booking-slug']]) ){
				wp_enqueue_script('stripe', 'https://js.stripe.com/v2/');
			}
		}
	}
	
	if( !function_exists('gdlrs_get_stripe_form') ){
		function gdlrs_get_stripe_form($option){
			global $hostel_option;
			
			ob_start();
?>
<form action="" method="POST" class="gdlr-payment-form" id="payment-form" data-ajax="<?php echo AJAX_URL; ?>" data-invoice="<?php echo $option['invoice']; ?>" >
	<p class="gdlr-form-half-left">
		<label><span><?php _e('Card Number', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="20" data-stripe="number"/>
	</p>
	<div class="clear" ></div>
	
	<p class="gdlr-form-half-left">
		<label><span><?php _e('CVC', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="4" data-stripe="cvc"/>
	</p>
	<div class="clear" ></div>

	<p class="gdlr-form-half-left gdlr-form-expiration">
		<label><span><?php _e('Expiration (MM/YYYY)', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="2" data-stripe="exp-month"/>
		<span class="gdlr-separator" >/</span>
		<input type="text" size="4" data-stripe="exp-year"/>
	</p>
	<div class="clear" ></div>
	<div class="gdlr-form-error payment-errors" style="display: none;"></div>
	<div class="gdlr-form-loading gdlr-form-instant-payment-loading"><?php _e('loading', 'gdlr-hotel'); ?></div>
	<div class="gdlr-form-notice gdlr-form-instant-payment-notice"></div>
	<input type="submit" class="gdlr-form-button cyan" value="<?php _e('Submit Payment', 'gdlr-hotel'); ?>" >
</form>
<script type="text/javascript">
Stripe.setPublishableKey('<?php echo $hostel_option['stripe-publishable-key']; ?>');

jQuery(function($){
	function stripeResponseHandler(status, response) {
		var form = $('#payment-form');

		if (response.error) {
			form.find('.payment-errors').text(response.error.message).slideDown();
			form.find('input[type="submit"]').prop('disabled', false);
			form.find('.gdlr-form-loading').slideUp();
		}else{
			// response contains id and card, which contains additional card details
			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax'),
				data: {'action':'gdlr_hostel_stripe_payment','token': response.id, 'invoice': form.attr('data-invoice')},
				dataType: 'json',
				error: function(a, b, c){ 
					console.log(a, b, c); 
					form.find('.gdlr-form-loading').slideUp(); 
				},
				success: function(data){
					if( data.content ){
						$('#gdlr-booking-content-inner').fadeOut(function(){
							$(this).html(data.content).fadeIn();
						});
						$('#gdlr-booking-process-bar').children('[data-process=4]').addClass('gdlr-active').siblings().removeClass('gdlr-active');
					}else{
						form.find('.gdlr-form-loading').slideUp();
						form.find('.gdlr-form-notice').removeClass('success failed')
							.addClass(data.status).html(data.message).slideDown();
						
						if( data.status == 'failed' ){
							form.find('input[type="submit"]').prop('disabled', false);
						}
					}
				}
			});	
		}
	}	

	$('#payment-form').submit(function(event){
		var form = $(this);

		// Disable the submit button to prevent repeated clicks
		form.find('input[type="submit"]').prop('disabled', true);
		form.find('.payment-errors, .gdlr-form-notice').slideUp();
		form.find('.gdlr-form-loading').slideDown();
		
		Stripe.card.createToken(form, stripeResponseHandler);

		// Prevent the form from submitting with the default action
		return false;
	});
});
</script>
<?php	
			$stripe_form = ob_get_contents();
			ob_end_clean();
			return $stripe_form;
		}
	}
	
	add_action( 'wp_ajax_gdlr_hostel_stripe_payment', 'gdlr_hostel_stripe_payment' );
	add_action( 'wp_ajax_nopriv_gdlr_hostel_stripe_payment', 'gdlr_hostel_stripe_payment' );
	if( !function_exists('gdlr_hostel_stripe_payment') ){
		function gdlr_hostel_stripe_payment(){
			global $hostel_option;
		
			$ret = array();
			Stripe::setApiKey($hostel_option['stripe-secret-key']);
			
			if( !empty($_POST['token']) && !empty($_POST['invoice']) ){
				global $wpdb;

				$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hostel_payment ";
				$temp_sql .= "WHERE id = " . $_POST['invoice'];	
				$result = $wpdb->get_row($temp_sql);
				
				$contact_info = unserialize($result->contact_info);
				
				try{
					$charge = Stripe_Charge::create(array(
					  "amount" => (floatval($result->pay_amount) * 100),
					  "currency" => $hostel_option['stripe-currency-code'],
					  "card" => $_POST['token'],
					  "description" => $contact_info['email']
					));
					
					$wpdb->update( $wpdb->prefix . 'gdlr_hostel_payment', 
						array('payment_status'=>'paid', 'payment_info'=>serialize($charge), 'payment_date'=>date('Y-m-d H:i:s')), 
						array('id'=>$_POST['invoice']), 
						array('%s', '%s', '%s'), 
						array('%d')
					);				
					
					$data = unserialize($result->booking_data);
					$mail_content = gdlr_hostel_mail_content($contact_info, $data, $charge, array(
						'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
					);
					gdlr_hostel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
					gdlr_hostel_mail($hostel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);

					$ret['status'] = 'success';
					$ret['message'] = __('Payment complete.', 'gdlr-hotel');
					$ret['content'] = gdlrs_booking_complete_message();
				}catch(Stripe_CardError $e) {
					$ret['status'] = 'failed';
					$ret['message'] = $e->message;
				}
			}else{
				$ret['status'] = 'failed';
				$ret['message'] = __('Failed to proceed, please try again.', 'gdlr-hotel');	
			}
			
			die(json_encode($ret));
		}
	}
	
?>