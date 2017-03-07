<?php
	/*	
	*	Goodlayers Reservation Bar File
	*/

	if( !function_exists('gdlrs_get_reservation_bar') ){
		function gdlrs_get_reservation_bar($single_form = false){
			global $hostel_option;
			
			$ret  = '<form class="gdlr-reservation-bar" id="gdlr-reservation-bar" data-action="gdlr_hostel_booking" ';
			$ret .= ($single_form)? 'method="post" action="' . esc_url(add_query_arg(array($hostel_option['booking-slug']=>''), home_url('/'))) . '" ': '';
			$ret .= ' >';
			$ret .= '<div class="gdlr-reservation-bar-title">' . __('Your Reservation', 'gdlr-hotel') . '</div>';
			
			if( !empty($_GET['state']) && $_GET['state'] == 4 && !empty($_GET['invoice']) ){
				global $wpdb;
				$temp_sql  = "SELECT contact_info, booking_data FROM " . $wpdb->prefix . "gdlr_hostel_payment ";
				$temp_sql .= "WHERE id = " . $_GET['invoice'];	
				$result = $wpdb->get_row($temp_sql);
				$data = unserialize($result->booking_data);
				$contact = unserialize($result->contact_info);
				
				$ret .= '<div class="gdlr-reservation-bar-summary-form" id="gdlr-reservation-bar-summary-form" style="display: block;">';
				$ret .= gdlrs_get_summary_form($data, false, $contact['coupon']);
				$ret .= '</div>';
			}else{
				$ret .= '<div class="gdlr-reservation-bar-summary-form" id="gdlr-reservation-bar-summary-form"></div>';
				
				if( !empty($_POST['hotel_data']) ){
					$ret .= '<div class="gdlr-reservation-bar-room-form gdlr-active" id="gdlr-reservation-bar-room-form" style="display: block;">';
					$ret .= gdlrs_get_reservation_room_form($_POST, 0);
					$ret .= '</div>';
				}else{
					$ret .= '<div class="gdlr-reservation-bar-room-form" id="gdlr-reservation-bar-room-form"></div>';
				}
				
				$ret .= '<div class="gdlr-reservation-bar-date-form" id="gdlr-reservation-bar-date-form">';
				$ret .= gdlrs_get_reservation_date_form($single_form);
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-reservation-bar-service-form" id="gdlr-reservation-bar-service-form"></div>';
			}
			
			if( $single_form ){
				$ret .= '<input type="hidden" name="single-room" value="' . get_the_ID() . '" />';
			}else if( !empty($_POST['single-room']) ){
				$ret .= '<input type="hidden" name="single-room" value="' . $_POST['single-room'] . '" />';
			}
			$ret .= '</form>';
			return $ret;
		}
	}	
	
	if( !function_exists('gdlrs_get_summary_form') ){
		function gdlrs_get_summary_form($data, $with_form = true, $coupon = ''){
			global $hostel_option;
			$total_price = 0;

			$ret  = '<div class="gdlr-price-summary-wrapper" >';
			
			// display branches if exists
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'hostel_room_category');
				$ret .= '<div class="gdlr-price-summary-hotel-branches gdlr-title-font">';
				$ret .= $term->name;
				$ret .= '</div>';
			}else{
				$ret .= '<div class="gdlr-price-summary-head">' . __('Price Breakdown', 'gdlr-hotel') . '</div>';
			}
			
			// group the customer by room
			$customer_rooms = array();
			for($i=0; $i<intval($data['gdlr-room-number']); $i++){
				$customer_rooms[$data['gdlr-room-id'][$i]] = empty($customer_rooms[$data['gdlr-room-id'][$i]])? 1: $customer_rooms[$data['gdlr-room-id'][$i]] + 1;
			}
			
			foreach($customer_rooms as $room_id => $guest_num ){
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta($room_id, 'post-option', true)), true);
				$post_option['data'] = array(
					'check-in'=> $data['gdlr-check-in'],
					'check-out'=> $data['gdlr-check-out']
				);
				$price = gdlrs_get_booking_price($post_option);
				if( empty($post_option['room-type']) || $post_option['room-type'] == 'dorm' ){
					$price['total'] = $price['total'] * $guest_num;
				}
				
				$ret .= '<div class="gdlr-price-room-summary">';
				$ret .= '<div class="gdlr-price-room-summary-title">';
				$ret .= __('Room', 'gdlr-hotel') . ' : ' . get_the_title($room_id);
				$ret .= '<span class="gdlr-price-room-summary-price" href="#" >' . gdlr_hostel_money_format($price['total']) . '</span>';
				$ret .= '</div>';		
				
				$ret .= '<div class="gdlr-price-room-summary-info gdlr-title-font" >';
				$ret .= '<span>' . __('Guest', 'gdlr-hotel') . ' : ' . $guest_num . '</span>';
				$ret .= '</div>';		
				$ret .= '</div>';
				
				$total_price += $price['total'];
			}
			
			// service
			if( !empty($data['service']) ){
				$services_price = gdlrs_calculate_service_price($data);
				$ret .= '<div class="gdlr-service-price-summary">';
				$ret .= '<div class="gdlr-service-price-summary-head" >' . __('Additional Services', 'gdlr-hotel') . '</div>';
				
				foreach( $services_price as $key => $service_price ){
					if( $key == 'total' ) continue;
					
					$ret .= '<div class="gdlr-service-price-summary-item">';
					$ret .= '<span class="gdlr-head">' . $service_price['title'] . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($service_price['price']) . '</span>';					
					$ret .= '<div class="clear"></div>';
					$ret .= '</div>';
				}
				$ret .= '</div>';
				
				$total_price += $services_price['total'];
			}
			
			// vat
			if( !empty($hostel_option['booking-vat-amount']) ){
				$ret .= '<div class="gdlr-price-summary-vat" >';
				$ret .= '<div class="gdlr-price-summary-vat-total" >';
				$ret .= '<span class="gdlr-head">' . __('Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($total_price) . '</span>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // vat-total
				
				if( !empty($coupon) ){
					$discount = gdlr_get_coupon_discount($data, $coupon);
					$total_price -= $discount;
					$ret .= '<div class="gdlr-price-summary-vat-discount" >';
					$ret .= '<span class="gdlr-head">' . __('Coupon Discount', 'gdlr-hotel') . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($discount) . '</span>';
					$ret .= '<div class="clear"></div>';
					$ret .= '</div>';
				}

				$vat_amount = ($total_price * floatval($hostel_option['booking-vat-amount'])) / 100;
				$total_price += $vat_amount;
				$ret .= '<div class="gdlr-price-summary-vat-amount" >';
				$ret .= '<span class="gdlr-head">' . __('Vat', 'gdlr-hotel') . ' ' . $hostel_option['booking-vat-amount'] . '%</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($vat_amount) . '</span>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // vat-amount
				$ret .= '</div>';
			}

			// deposit
			if( $with_form && !empty($hostel_option['booking-deposit-amount']) ){
				// grand total
				$ret .= '<div class="gdlr-price-summary-grand-total gdlr-active" >';
				$ret .= '<span class="gdlr-head">' . __('Grand Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($total_price) . '</span>';
				$ret .= '</div>';
				
				$deposit_text = $hostel_option['booking-deposit-amount'] . '% ' . __('Deposit', 'gdlr-hotel');
				$deposit_amount = ($total_price * floatval($hostel_option['booking-deposit-amount'])) / 100;
				
				$ret .= '<div class="gdlr-price-deposit-wrapper">';
				$ret .= '<div class="gdlr-price-deposit-input" >';
				$ret .= '<span class="gdlr-active" ><label class="gdlr-radio-input"><input type="radio" name="pay_deposit" value="false" checked ></label>' . __('Pay Full Amount', 'gdlr-hotel') . '</span>';
				$ret .= '<span><label class="gdlr-radio-input"><input type="radio" name="pay_deposit" value="true" ></label>'  . __('Pay', 'gdlr-hotel') . ' ' . $deposit_text . '</span>';
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-price-deposit-inner-wrapper">';
				$ret .= '<div class="gdlr-price-deposit-title">' . $deposit_text . '</div>';
				$ret .= '<div class="gdlr-price-deposit-caption">' . __('*Pay the rest on arrival', 'gdlr-hotel') . '</div>';
				$ret .= '<div class="gdlr-price-deposit-amount">' . gdlr_hostel_money_format($deposit_amount) . '</div>';
				$ret .= '</div>';
				$ret .= '</div>';
				
				$ret .= '<a id="gdlr-edit-booking-button" class="gdlr-edit-booking-button gdlr-button with-border" href="#">' . __('Edit Booking', 'gdlr-hotel') . '</a>';
			}else{ 
				$ret .= '<div class="gdlr-price-summary-grand-total-wrapper-2" >';
				$ret .= '<div class="gdlr-price-summary-grand-total ';
				$ret .= (empty($data['pay_deposit']) || $data['pay_deposit'] == 'false')? 'gdlr-active': '';
				$ret .= '" >';
				$ret .= '<span class="gdlr-head">' . __('Grand Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($total_price) . '</span>';
				$ret .= '</div>';
				
				if( !empty($data['pay_deposit']) && $data['pay_deposit'] == 'true' ){
					$deposit_text = $hostel_option['booking-deposit-amount'] . '% ' . __('Deposit', 'gdlr-hotel');
					$deposit_amount = ($total_price * floatval($hostel_option['booking-deposit-amount'])) / 100;
					
					$ret .= '<div class="gdlr-price-deposit-wrapper">';
					$ret .= '<div class="gdlr-price-deposit-inner-wrapper">';
					$ret .= '<div class="gdlr-price-deposit-title">' . $deposit_text . '</div>';
					$ret .= '<div class="gdlr-price-deposit-caption">' . __('*Pay the rest on arrival', 'gdlr-hotel') . '</div>';
					$ret .= '<div class="gdlr-price-deposit-amount">' . gdlr_hostel_money_format($deposit_amount) . '</div>';
					$ret .= '</div>';
					$ret .= '</div>';
					
					$ret .= '<div class="gdlr-pay-on-arrival" >';
					$ret .= '<span class="gdlr-head">' . __('Pay on arrival', 'gdlr-hotel') . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hostel_money_format($total_price - $deposit_amount) . '</span>';
					$ret .= '</div>';
				}
				$ret .= '</div>';
				
				
			}

			$ret .= '</div>'; // gdlr-price-summary-wrapper
			
			return $ret;
		}
	}
	
	if( !function_exists('gdlrs_get_reservation_room_form') ){
		function gdlrs_get_reservation_room_form($data, $selected_room){
			$ret  = ''; $active = false;
			
			if( !empty($data['gdlr-room-id']) ){
				for( $i=0; $i<sizeOf($data['gdlr-room-id']) && $i<$data['gdlr-room-number']; $i++ ){
					$options = array(
						'room-number'=>$i + 1, 
						'room-id'=>$data['gdlr-room-id'][$i],
						'already_active'=>$active
					);
					if( $selected_room == $i || empty($data['gdlr-room-id'][$i]) ){
						$active = true;
						$options['room-id'] = '';
					}
					$ret .= gdlrs_get_reservation_room($options);					
				}
			}
			
			if( empty($data['gdlr-room-id']) || 
				(!$active && $selected_room >= sizeOf($data['gdlr-room-id']) && $selected_room < intval($data['gdlr-room-number'])) ){
				$ret .= gdlrs_get_reservation_room(array(
					'room-number'=>intval($selected_room) + 1, 
					'room-id'=>''
				));
			}
			return $ret;
		}
	}

	if( !function_exists('gdlrs_get_reservation_room') ){
		function gdlrs_get_reservation_room($option){
			$option['room-id'] = empty($option['room-id'])? '': $option['room-id'];
			
			$ret  = '<div class="gdlr-reservation-room gdlr-title-font ';
			$ret .= (empty($option['room-id']) && empty($option['already_active']))? 'gdlr-active': ''; 
			$ret .= '">';
			$ret .= '<i class="fa fa-angle-double-right icon-double-angle-right" ></i>';
			
			$ret .= '<div class="gdlr-reservation-room-content" >';
			$ret .= '<div class="gdlr-reservation-room-title">';
			$ret .= __('Guest', 'gdlr-hotel') . ' ' . $option['room-number'] . ' : ';
			$ret .= empty($option['room-id'])? '': get_the_title($option['room-id']);
			$ret .= '</div>';

			$ret .= '<div class="gdlr-reservation-room-info" >';
			$ret .= empty($option['room-id'])? '': '<a data-room="' . $option['room-number'] . '" class="gdlr-reservation-change-room" href="#" >' . __('Change Room', 'gdlr-hotel') . '</a>';
			$ret .= '</div>';
			$ret .= '</div>';
			
			$ret .= '<input type="text" name="gdlr-room-id[]" value="' . $option['room-id'] . '" /></span>';
			$ret .= '</div>';
			
			return $ret;
		}
	}	
	
	if( !function_exists('gdlrs_get_reservation_date_form') ){
		function gdlrs_get_reservation_date_form($single_form = false, $data = array()){
			$ret  = '';
			if( !empty($_POST['hotel_data']) ){
				$value = $_POST;
			}else{
				$current_date = date('Y-m-d');
				$next_date = date('Y-m-d', strtotime($current_date . "+1 days"));
				
				$value = array(
					'gdlr-check-in' => $current_date,
					'gdlr-night' => 1,
					'gdlr-check-out' => $next_date,
					'gdlr-room-number' => 1
				);
			}
			
			// branch (if enable)
			global $hostel_option;
			if( !empty($hostel_option['enable-hotel-branch']) && $hostel_option['enable-hotel-branch'] == 'enable' ){
				if( is_single() ){
					$term = get_the_terms(get_the_ID(), 'hostel_room_category');
					if( !empty($term) ){
						$term = reset($term);
						$value['gdlr-hotel-branches'] = $term->term_id;
					}else{
						$value['gdlr-hotel-branches'] = '';
					}
				}else if( empty($value['gdlr-hotel-branches']) ){ 
					$value['gdlr-hotel-branches'] = ''; 
				}
					
				$ret .= gdlrs_get_reservation_branch_combobox(array(
					'title'=>__('Hotel Branches', 'gdlr-hotel'),
					'slug'=>'gdlr-hotel-branches',
					'id'=>'gdlr-hotel-branches',
					'value'=>$value['gdlr-hotel-branches']
				));
				$ret .= '<div class="clear"></div>';
			}
			
			
			// date
			$ret .= gdlr_get_reservation_datepicker(array(
				'title'=>__('Check In', 'gdlr-hotel'),
				'slug'=>'gdlr-check-in',
				'id'=>'gdlr-check-in',
				'value'=>$value['gdlr-check-in']
			));
			$ret .= gdlr_get_reservation_combobox(array(
				'title'=>__('Night', 'gdlr-hotel'),
				'slug'=>'gdlr-night',
				'id'=>'gdlr-night',
				'value'=>$value['gdlr-night']
			), 1);
			$ret .= '<div class="clear"></div>';

			$ret .= gdlr_get_reservation_datepicker(array(
				'title'=>__('Check Out', 'gdlr-hotel'),
				'slug'=>'gdlr-check-out',
				'id'=>'gdlr-check-out',
				'value'=>$value['gdlr-check-out']
			));
			$ret .= '<div class="clear"></div>';
			
			// room
			$ret .= gdlr_get_reservation_combobox(array(
				'title'=>__('Guests', 'gdlr-hotel'),
				'slug'=>'gdlr-room-number',
				'id'=>'gdlr-room-number',
				'value'=>$value['gdlr-room-number']
			), 1);
			$ret .= '<div class="clear"></div>';
			
			if( $single_form ){
				$ret .= '<input type="hidden" name="hotel_data" value="1" >';
				$ret .= '<input type="submit" class="gdlr-reservation-bar-button gdlr-button with-border" value="' . __('Check Availability', 'gdlr-hotel') . '" >';
			}else if( empty($_POST['hotel_data']) ){
				$ret .= '<a id="gdlr-reservation-bar-button" class="gdlr-reservation-bar-button gdlr-button with-border" href="#" >' . __('Check Availability', 'gdlr-hotel') . '</a>';
			}
			$ret .= '<div class="clear"></div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_datepicker') ){
		function gdlr_get_reservation_datepicker($option){
			global $theme_option;
			
			$ret  = '<div class="gdlr-reservation-field gdlr-resv-datepicker">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title']  . '</span>';
			$ret .= '<div class="gdlr-datepicker-wrapper">';
			$ret .= '<input type="text"  id="' . $option['id'] . '" class="gdlr-datepicker" ';
			$ret .= (empty($theme_option['datepicker-format']))? '': 'data-dfm="' . $theme_option['datepicker-format'] . '" ';
			$ret .= (empty($option['value'])? '': 'value="' . $option['value'] . '" ') . '/>';
			
			$ret .= '<input type="hidden" class="gdlr-datepicker-alt" name="' . $option['slug'] . '" ';
			$ret .= (empty($option['value'])? '': 'value="' . $option['value'] . '" ') . '/>';
			$ret .= '</div>'; // gdlr-datepicker-wrapper
			$ret .= '</div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_combobox') ){
		function gdlr_get_reservation_combobox($option, $min_num = 0, $max_num = 10){
			$ret  = '<div class="gdlr-reservation-field gdlr-resv-combobox">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title'] . '</span>';
			$ret .= '<div class="gdlr-combobox-wrapper">';
			$ret .= '<select name="' . $option['slug'] . (empty($option['multiple'])? '': '[]') . '" ';
			$ret .= !empty($option['id'])? 'id="' . $option['id'] . '" >': '>';
			for( $i=$min_num; $i<$max_num; $i++ ){
				$ret .= '<option value="' . $i . '" ' . ((!empty($option['value']) && $i==$option['value'])? 'selected':'') . ' >' . $i . '</option>';
			}
			if( !empty($option['value']) && $option['value'] > $max_num ){
				$ret .= '<option value="' . $option['value'] . '" >' . $option['value'] . '</option>';
			}
			$ret .= '</select>';
			$ret .= '</div>'; // gdlr-combobox-wrapper
			$ret .= '</div>';			
			return $ret;
		}
	}		
	
	if( !function_exists('gdlrs_get_reservation_branch_combobox') ){
		function gdlrs_get_reservation_branch_combobox($option, $min_num = 0, $max_num = 10){
			$branches = gdlr_get_term_id_list('hostel_room_category');

			$ret  = '<div class="gdlr-reservation-field gdlr-resv-branches-combobox">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title'] . '</span>';
			$ret .= '<div class="gdlr-combobox-wrapper">';
			$ret .= '<select name="' . $option['slug'] . '" ';
			$ret .= !empty($option['id'])? 'id="' . $option['id'] . '" >': '>';
			$ret .= '<option value="" >' . __('Please select hotel branch', 'gdlr-hotel') . '</option>';
			foreach( $branches as $slug => $branch ){
				$ret .= '<option value="' . $slug . '" ' . ((!empty($option['value']) && $slug==$option['value'])? 'selected':'') . ' >' . $branch . '</option>';
			}
			$ret .= '</select>';
			$ret .= '</div>'; // gdlr-combobox-wrapper
			$ret .= '<div id="please-select-branches" >' . __('* Please select branch', 'gdlr-hotel') . '</div>';
			$ret .= '</div>';			
			return $ret;
		}
	}	
	
?>