<?php

if( !empty($_GET['invoice']) && !empty($_GET['response']) && $_GET['response'] == 1 ){
	include_once('../../../../wp-load.php');
	include_once('payment-api/authorize-php/autoload.php');
	
	global $hotel_option, $wpdb;
	
	$response = new AuthorizeNetSIM($hotel_option['authorize-api-id'], $hotel_option['authorize-md5-hash']);
	
	if($response->isAuthorizeNet()){
	
		if($response->approved){
			$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
				array('payment_status'=>'paid', 'payment_info'=>serialize($response), 'payment_date'=>date('Y-m-d H:i:s')), 
				array('id'=>$_GET['invoice']), 
				array('%s', '%s', '%s'), 
				array('%d')
			);	
			
			$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
			$temp_sql .= "WHERE id = " . $_GET['invoice'];	
			$result = $wpdb->get_row($temp_sql);

			$contact_info = unserialize($result->contact_info);
			$data = unserialize($result->booking_data);
			$mail_content = gdlr_hotel_mail_content($contact_info, $data, $response, array(
				'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
			);
			gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
			gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);
			
			$redirect_url = add_query_arg(array($hotel_option['booking-slug']=>'', 'state'=>4, 'invoice'=>$_GET['invoice']), home_url());
		}else{
			$redirect_url = add_query_arg(array($hotel_option['booking-slug']=>'', 'state'=>4, 'invoice'=>$_GET['invoice'],
				'response_code'=>$response->response_code, 'response_reason_text'=>$response->response_reason_text), home_url());
		}
		
		// Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
		echo AuthorizeNetDPM::getRelayResponseSnippet($redirect_url);
	}else{ 
		die("Error. Check your MD5 Setting.");
		$redirect_url = add_query_arg(array($hotel_option['booking-slug']=>'', 'state'=>4, 'invoice'=>$_GET['invoice'],
				'response_code'=>$response->response_code, 'response_reason_text'=>$response->response_reason_text), home_url());
?>
<html>
<head>
	<script type='text/javascript'charset='utf-8'>window.location='<?php echo esc_url($redirect_url); ?>';</script>
	<noscript><meta http-equiv='refresh' content='1;url=<?php echo esc_url($redirect_url); ?>'></noscript>
</head>
<body></body>
</html>		
<?php		
	}
	
	die("");
}

if( !function_exists('gdlr_get_authorize_form') ){
	function gdlr_get_authorize_form($option){
		global $hotel_option;

		$relay_response_url = plugin_dir_url(__FILE__) . '/authorize-payment.php?response=1&invoice=' . $option['invoice']; 
		
		return AuthorizeNetDPM::getCreditCardForm(
			$option['price'], 
			$option['invoice'], 
			$relay_response_url, 
			$hotel_option['authorize-api-id'], 
			$hotel_option['authorize-transaction-key']
		);
	}
}
?>