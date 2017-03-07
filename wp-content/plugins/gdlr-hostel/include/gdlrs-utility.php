<?php
	/*	
	*	Goodlayers Utility File
	*/	
	
	// send the mail
	if( !function_exists('gdlr_hostel_mail') ){
		function gdlr_hostel_mail($recipient, $title, $message){
			global $hostel_option;

			$headers = 'From: ' . $hostel_option['recipient-name'] . ' <' . $hostel_option['recipient-mail'] . '>' . "\r\n";
			$headers = $headers . 'Content-Type: text/plain; charset=UTF-8 ' . " \r\n";
			wp_mail($recipient, $title, $message, $headers);		
		}
	}
	
	if( !function_exists('gdlr_hostel_mail_content') ){
		function gdlr_hostel_mail_content($contact, $data, $payment_info, $price){
			$content  = __("Contact Info", "gdlr-hotel") . " \n";
			$content .= __("Name :", "gdlr-hotel") . " {$contact['first_name']}\n";
			$content .= __("Last Name :", "gdlr-hotel") . " {$contact['last_name']}\n";
			$content .= __("Phone :", "gdlr-hotel") . " {$contact['phone']}\n";
			$content .= __("Email :", "gdlr-hotel") . " {$contact['email']}\n";
			$content .= __("Address :", "gdlr-hotel") . " {$contact['address']}\n";
			$content .= __("Additional Note :", "gdlr-hotel") . " {$contact['additional-note']}\n";
			$content .= __("Coupon :", "gdlr-hotel") . " {$contact['coupon']}\n";
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'hostel_room_category');
				$content .= "Branches : {$term->name}\n";
				
				$category_meta = get_option('gdlr_hotel_branch', array());
				if( !empty($category_meta[$term->slug]['content']) ){
					$content .= "Location : {$category_meta[$term->slug]['content']}\n";
				}
			}
			$content .= "\n";
			
			$content .= __("Room Information", "gdlr-hotel") . "\n";
			
			$customer_rooms = array();
			for($i=0; $i<intval($data['gdlr-room-number']); $i++){
				$customer_rooms[$data['gdlr-room-id'][$i]] = empty($customer_rooms[$data['gdlr-room-id'][$i]])? 1: $customer_rooms[$data['gdlr-room-id'][$i]] + 1;
			}
			
			foreach($customer_rooms as $room_id => $guest_num ){
				$content .= __("Room", "gdlr-hotel") . " : " . get_the_title($room_id) . " : {$guest_num} " . __('Guest', 'gdlr-hotel') . " \n";
			}			
			$content .= __("Check In :", "gdlr-hotel") . " {$data['gdlr-check-in']} \n";
			$content .= __("Check Out :", "gdlr-hotel") . " {$data['gdlr-check-out']} \n";
			$content .= "\n";
			
			if( !empty($data['service']) ){
				$content .= __("Additional Services", "gdlr-hotel") . "\n";
				$services_price = gdlrs_calculate_service_price($data);
				foreach( $services_price as $key => $service_price ){
					if( $key == 'total' ) continue;
					$service_title = str_replace('<span class="gdlr-sep">/</span>', ' ', $service_price['title']);
					$content .= $service_title . "\n";
				}

				$content .= "\n";
			}
			
			$content .= __("Payment Information", "gdlr-hotel") . " \n";
			$content .= __("Total Price :", "gdlr-hotel") . " " . gdlr_hostel_money_format($price['total_price']) . " \n";
			$content .= __("Pay Amount :", "gdlr-hotel") . " " . gdlr_hostel_money_format($price['pay_amount']) . " \n";

			if( !empty($price['booking_code']) ){
				$content .= __("Booking Code :", "gdlr-hotel") . " {$price['booking_code']} \n";
			}
			if( !empty($contact['payment-method']) && !empty($payment_info) ){
				if( $contact['payment-method'] == 'stripe' ){
					$content .= __("Payment Method : Stripe", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info['balance_transaction']} \n";
				}else if( $contact['payment-method'] == 'paypal' ){
					$content .= __("Payment Method : Paypal", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info['txn_id']} \n";
				}else if( $contact['payment-method'] == 'paymill' ){
					$content .= __("Payment Method : Paymill", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info->getId()} \n";
				}else if( $contact['payment-method'] == 'authorize' ){
					$content .= __("Payment Method : Authorize", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info->transaction_id} \n";
				}
			}
			
			return $content;
		}
	}	
	
	// format the currency
	if( !function_exists('gdlr_hostel_money_format') ){
		function gdlr_hostel_money_format($amount, $format = ''){
			if( empty($format) ){
				global $hostel_option;
				$format = $hostel_option['booking-money-format'];
			}
			if( strpos($format, 'NUMBER') === false ){
				$format .= 'NUMBER';
			}
			return str_replace('NUMBER', number_format_i18n($amount, 2), $format);
		}
	}
	
	// validate the contact form fields
	if( !function_exists('gdlr_validate_contact_form') ){
		function gdlr_validate_contact_form( $contact ){
			if( empty($contact['first_name']) || empty($contact['last_name']) || 
				empty($contact['email']) || empty($contact['phone']) ){
				return __('Please fill all required fields.', 'gdlr-hotel');
			}
			if( !is_email($contact['email']) ){
				return __('Email is invalid.', 'gdlr-hotel');
			}
			return false;
		}
	}
	
	// save the booking any payment to database
	if( !function_exists('gdlrs_insert_booking_db') ){
		function gdlrs_insert_booking_db($options){
			global $wpdb, $hostel_option;
			$pricing = gdlrs_get_booking_total_price($options['data'], $options['contact']['coupon']);
			
			$customer_code  = $hostel_option['booking-code-prefix'];
			$customer_code .= mb_substr($options['contact']['first_name'], 0, 1);
			$customer_code .= mb_substr($options['contact']['last_name'], 0, 1);
			$code_count = get_option('gdlr-customer-code-count', 0);
			update_option('gdlr-customer-code-count', $code_count+1);
			$customer_code  = strtoupper($customer_code . $code_count);
			
			$result = $wpdb->insert( $wpdb->prefix . 'gdlr_hostel_payment',
				array(
					'total_price'=>$pricing['total_price'], 
					'pay_amount'=>$pricing['pay_amount'], 
					'booking_data'=>serialize($options['data']), 
					'contact_info'=>serialize($options['contact']), 
					'payment_status'=>$options['payment_status'], 
					'payment_date'=>date('Y-m-d H:i:s'),
					'customer_code'=>$customer_code
				),
				array('%f', '%f', '%s', '%s', '%s', '%s', '%s')
			);

			if( $result > 0 ){
				$payment_id = $wpdb->insert_id;
				
				for( $i=0; $i<$options['data']['gdlr-room-number']; $i++ ){
					$wpdb->insert($wpdb->prefix . 'gdlr_hostel_booking',
						array(
							'payment_id'=>$payment_id, 
							'room_id'=>$options['data']['gdlr-room-id'][$i], 
							'start_date'=>$options['data']['gdlr-check-in'], 
							'end_date'=>$options['data']['gdlr-check-out']
						),
						array('%s', '%s', '%s', '%s')
					);
				}
			}
			
			return array(
				'invoice' => $payment_id, 
				'total-price' => $pricing['total_price'],
				'pay-amount' => $pricing['pay_amount'],
				'code' => $customer_code
			);
		}
	}
	
?>