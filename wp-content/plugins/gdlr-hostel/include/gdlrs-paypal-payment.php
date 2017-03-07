<?php
	/*	
	*	Goodlayers Payment Option File
	*/	
	
	if( !function_exists('gdlrs_additional_paypal_part') ){
		function gdlrs_additional_paypal_part($option){
			global $hostel_option;
			
			$ret  = '<input type="hidden" name="cmd" value="_xclick">';
			$ret .= '<input type="hidden" name="business" value="' . $hostel_option['paypal-recipient-email'] .'">';
			$ret .= '<input type="hidden" name="currency_code" value="' . $hostel_option['paypal-currency-code'] . '" />';
			$ret .= '<input type="hidden" name="item_name" value="' . $option['title'] . '">';
			$ret .= '<input type="hidden" name="invoice" value="' . $option['invoice'] . '">';
			$ret .= '<input type="hidden" name="amount" value="' . $option['price'] . '">';
			$ret .= '<input type="hidden" name="notify_url" value="' . esc_url(add_query_arg(array('paypals'=>''), home_url('/'))) . '">';  
			$ret .= '<input type="hidden" name="return" value="';
			$ret .= esc_url(add_query_arg(array($hostel_option['booking-slug']=>'', 'state'=>4, 'invoice'=>$option['invoice']), home_url('/')));
			$ret .= '">';
			
			return $ret;
		}
	}
	
	
	add_action('init', 'gdlrs_paypal_ipn');
	if( !function_exists('gdlrs_paypal_ipn') ){
		function gdlrs_paypal_ipn(){
			if( isset($_GET['paypals']) ){
				global $hostel_option;
			
				// STEP 1: read POST data
				$raw_post_data = file_get_contents('php://input');
				$raw_post_array = explode('&', $raw_post_data);
				$myPost = array();
				foreach ($raw_post_array as $keyval) {
				  $keyval = explode ('=', $keyval);
				  if (count($keyval) == 2)
					 $myPost[$keyval[0]] = urldecode($keyval[1]);
				}
				
				// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
				$req = 'cmd=_notify-validate';
				if(function_exists('get_magic_quotes_gpc')) {
				   $get_magic_quotes_exists = true;
				} 
				foreach ($myPost as $key => $value) {        
				   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
						$value = urlencode(stripslashes($value)); 
				   } else {
						$value = urlencode($value);
				   }
				   $req .= "&$key=$value";
				}
				 
				 
				// Step 2: POST IPN data back to PayPal to validate
				$ch = curl_init($hostel_option['paypal-action-url']);
				curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

				if( !($res = curl_exec($ch)) ) {	
					curl_close($ch);
					exit;
				}
				curl_close($ch);
				
				// inspect IPN validation result and act accordingly
				if( strcmp ($res, "VERIFIED") == 0 ) {
					global $wpdb;
					
					$wpdb->update( $wpdb->prefix . 'gdlr_hostel_payment', 
						array('payment_status'=>'paid', 'payment_info'=>serialize($_POST), 'payment_date'=>date('Y-m-d H:i:s')), 
						array('id'=>$_POST['invoice']), 
						array('%s', '%s', '%s'), 
						array('%d')
					);
					
					$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hostel_payment ";
					$temp_sql .= "WHERE id = " . $_POST['invoice'];	
					$result = $wpdb->get_row($temp_sql);

					$contact_info = unserialize($result->contact_info);
					$data = unserialize($result->booking_data);
					$mail_content = gdlr_hostel_mail_content($contact_info, $data, $_POST, array(
						'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
					);
					gdlr_hostel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
					gdlr_hostel_mail($hostel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);
				}
			}			
		}
	}
?>