<?php
	/*	
	*	Goodlayers Booking Item
	*/

	if( !function_exists('gdlr_booking_process_bar') ){
		function gdlr_booking_process_bar( $state = 1 ){
			$ret  = '<div class="gdlr-booking-process-bar" id="gdlr-booking-process-bar" data-state="' . $state . '" >';
			$ret .= '<div data-process="1" class="gdlr-booking-process ' . (($state==1)? 'gdlr-active': '') . '">' . __('1. Choose Date', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="2" class="gdlr-booking-process ' . (($state==2)? 'gdlr-active': '') . '">' . __('2. Choose Room', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="3" class="gdlr-booking-process ' . (($state==3)? 'gdlr-active': '') . '">' . __('3. Make a Reservation', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="4" class="gdlr-booking-process ' . (($state==4)? 'gdlr-active': '') . '">' . __('4. Confirmation', 'gdlr-hotel') . '</div>';
			$ret .= '</div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_booking_date_range') ){
		function gdlr_booking_date_range( $state = 1 ){ 
			global $theme_option;
?>
<div class="gdlr-datepicker-range-wrapper" >
<div class="gdlr-datepicker-range" id="gdlr-datepicker-range" <?php echo (empty($theme_option['datepicker-format']))? '': 'data-dfm="' . $theme_option['datepicker-format'] . '" '; ?> ></div>
</div>
<?php
		}
	}

	// ajax action for booking form
	add_action( 'wp_ajax_gdlr_hostel_booking', 'gdlr_ajax_hostel_booking' );
	add_action( 'wp_ajax_nopriv_gdlr_hostel_booking', 'gdlr_ajax_hostel_booking' );
	if( !function_exists('gdlr_ajax_hostel_booking') ){
		function gdlr_ajax_hostel_booking(){	
			if( !empty($_POST['data']) ){
				parse_str($_POST['data'], $data);
			}
			if( !empty($_POST['contact']) ){
				parse_str($_POST['contact'], $contact);
			}
			if( !empty($_POST['service']) ){
				parse_str($_POST['service'], $service);
				$data['service'] = empty($service['service-select'])? array(): $service['service-select'];
				$data['service-amount'] = empty($service['service-amount'])? array(): $service['service-amount'];
			}else if( empty($data['service']) ){
				$data['service'] = array();
				$data['service-amount'] = array();
			}
			$ret = array();

			// query section
			if( $_POST['state'] == 2 ){
				$data['gdlr-room-id'] = empty($data['gdlr-room-id'])? array(): $data['gdlr-room-id'];
				
				// check and fill people for private room
				if( !empty($_POST['room_id']) ){
					$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta($_POST['room_id'], 'post-option', true)), true);
					if( !empty($post_option['room-type']) && $post_option['room-type'] == 'private' ){
						$guest_pos = intval($post_option['room-amount']) - 1;
						for( $i=0; $i<intval($data['gdlr-room-number']); $i++ ){
							if( $guest_pos <= 0 ) break;
							if( empty($data['gdlr-room-id'][$i]) ){
								$data['gdlr-room-id'][$i] = $_POST['room_id'];
								$guest_pos--;
							}
						}
					}
				}
				
				$room_number = gdlrs_get_edited_room($data['gdlr-room-number'], $data['gdlr-room-id']);
				
				// room form
				$ret['room_form'] = gdlrs_get_reservation_room_form($data, $room_number);
				
				// content area
				if( empty($data['gdlr-check-in']) || empty($data['gdlr-check-out']) || $data['gdlr-check-out'] < $data['gdlr-check-in'] ){
					$ret['content']  = '<div class="gdlr-room-selection-complete">';
					$ret['content'] .= '<div class="gdlr-room-selection-title" >' . __('Date field invalid', 'gdlr-hotel') . '</div>';
					$ret['content'] .= '<div class="gdlr-room-selection-content" >' . __('Please select \'check in\' and \'check out\' date from reservation bar again.', 'gdlr-hotel') . '</div>';
					$ret['content'] .= '</div>';
				}else if( $data['gdlr-room-number'] > $room_number ){
					$ret['content'] = gdlrs_get_booking_room_query($data, $room_number);
				}else{
					$data['gdlr-hotel-branches'] = empty($data['gdlr-hotel-branches'])? '': $data['gdlr-hotel-branches'];
					$ret['content']  = '<div class="gdlr-room-selection-complete">';
					$ret['content'] .= '<div class="gdlr-room-selection-title" >' . __('Room Selection is Complete', 'gdlr-hotel') . '</div>';
					$ret['content'] .= '<div class="gdlr-room-selection-caption" >' . __('You can edit your booking by using the panel on the left', 'gdlr-hotel') . '</div>';
					$ret['content'] .= gdlrs_get_booking_services($data['gdlr-hotel-branches'], $data['service']);
					$ret['content'] .= '<div class="gdlr-room-selection-divider" ></div>';
					$ret['content'] .= '<a class="gdlr-button with-border gdlr-room-selection-next">' . __('Go to next step', 'gdlr-hotel') . '</a>';
					$ret['content'] .= '</div>';
				}
				
				$ret['state'] = 2;
			}else if( $_POST['state'] == 3 ){
				if( !empty($data['service']) ){
					$ret['service'] = '';
					foreach( $data['service'] as $key => $service_id ){
						$ret['service'] .= '<input type="hidden" name="service[]" value="' . $service_id . '" />';
						$ret['service'] .= '<input type="hidden" name="service-amount[]" value="' . $data['service-amount'][$key] . '" />';
					}
				}else{
					$ret['service'] = '<input type="hidden" />';
				}
				
				if( empty($_POST['contact']) ){
					$ret['summary_form'] = gdlrs_get_summary_form($data);
					$ret['content'] = gdlrs_get_booking_contact_form();
					$ret['state'] = 3;
				}else{
					$validate = gdlr_validate_contact_form($contact);
					
					if( !empty($validate) ){
						$ret['state'] = 3;
						$ret['error_message'] = $validate;
					}else{
						$ret['summary_form'] = gdlrs_get_summary_form($data, false);
						
						if( $_POST['contact_type'] == 'contact' ){
							$booking = gdlrs_insert_booking_db(array('data'=>$data, 'contact'=>$contact, 'payment_status'=>'booking'));
							
							global $hostel_option;
							
							$mail_content = gdlr_hostel_mail_content( $contact, $data, array(), array(
								'total_price'=>$booking['total-price'], 'pay_amount'=>0, 'booking_code'=>$booking['code'])
							);
							gdlr_hostel_mail($contact['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
							gdlr_hostel_mail($hostel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);
							
							$ret['content'] = gdlrs_booking_complete_message();
							$ret['state'] = 4;
						}else{
							global $hostel_option;
							$booking = gdlrs_insert_booking_db(array('data'=>$data, 'contact'=>$contact, 'payment_status'=>'pending'));
							
							if( $contact['payment-method'] == 'paypal' ){
								$ret['payment'] = 'paypal';
								$ret['payment_url'] = $hostel_option['paypal-action-url'];
								$ret['addition_part'] = gdlrs_additional_paypal_part(array(
									'title' => __('Room Booking', 'gdlr-hotel'), 
									'invoice' => $booking['invoice'],
									'price' => $booking['pay-amount']
								));
							}else if( $contact['payment-method'] == 'stripe' ){
								$ret['content'] = gdlrs_get_stripe_form(array(
									'invoice' => $booking['invoice']
								));
							}else if( $contact['payment-method'] == 'paymill' ){
								$ret['content'] = gdlrs_get_paymill_form(array(
									'invoice' => $booking['invoice']
								));
							}else if( $contact['payment-method'] == 'authorize' ){
								$ret['content'] = gdlrs_get_authorize_form(array(
									'invoice' => $booking['invoice'],
									'price' => $booking['pay-amount']
								));
							}
							
							// made payment
							$ret['state'] = 3;
						}
					}
				}
			}
			
			if( !empty($data) ){
				$ret['data'] = $data;
			}
			
			die(json_encode($ret));
		}
	}
	
	// check if every room is selected.
	if( !function_exists('gdlrs_get_edited_room') ){
		function gdlrs_get_edited_room($max_room = 0, $rooms = array()){
			for( $i=0; $i<$max_room; $i++ ){
				if( empty($rooms[$i]) ) return $i;
			}
			
			return $max_room;
		}
	}
	
	// booking room style
	if( !function_exists('gdlrs_get_booking_room_query') ){
		function gdlrs_get_booking_room_query($data, $room_number){
			global $wpdb, $hostel_option, $sitepress;

			$hostel_option['preserve-booking-room'] = empty($hostel_option['preserve-booking-room'])? 'paid': $hostel_option['preserve-booking-room'];
	
			// collect the previously selected room
			$rooms = array();
			$room_temp = array();
			if( !empty($data['gdlr-room-id']) ){
				foreach( $data['gdlr-room-id'] as $selected_room_id ){
					if( !empty($sitepress) ){
						$trid = $sitepress->get_element_trid($selected_room_id, 'post_hostel_room');
						$translations = $sitepress->get_element_translations($trid,'post_hostel_room');
						if( empty($translations) ){
							$room_temp[$selected_room_id] = empty($room_temp[$selected_room_id])? 1: $room_temp[$selected_room_id] + 1; 
						}else{
							foreach( $translations as $translation ){
								$room_temp[$translation->element_id] = empty($room_temp[$translation->element_id])? 1: $room_temp[$translation->element_id] + 1; 
							}
						}
					}else{
						$room_temp[$selected_room_id] = empty($room_temp[$selected_room_id])? 1: $room_temp[$selected_room_id] + 1; 
					}
				}
			}

			// select all room id where max people > selected people
			$sql  = "SELECT DISTINCT wpost.ID FROM {$wpdb->posts} wpost ";
			if( !empty($data['gdlr-hotel-branches']) ){
				$sql .= "LEFT JOIN {$wpdb->term_relationships} ON (wpost.ID = {$wpdb->term_relationships}.object_id) ";
				$sql .= "LEFT JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id) ";
			} 			
			$sql .= "WHERE wpost.post_type = 'hostel_room' AND wpost.post_status = 'publish' ";
			if( !empty($data['gdlr-hotel-branches']) ){
				$sql .= "AND {$wpdb->term_taxonomy}.taxonomy = 'hostel_room_category' ";
				$sql .= "AND {$wpdb->term_taxonomy}.term_id = {$data['gdlr-hotel-branches']} ";
			}
			$sql .= "ORDER BY wpost.ID DESC";
			$room_query =  $wpdb->get_results($sql, OBJECT);			

			// get data with false value filled
			$all_date = gdlr_split_date($data['gdlr-check-in'], $data['gdlr-check-out']);
			
			// check if the date is blocked
			$blocked_date = '';
			foreach($all_date as $key => $val){
				if( gdlr_is_ss($key, array('date'=>$hostel_option['block-date'])) ){
					$blocked_date .= empty($blocked_date)? '':', ';
					$blocked_date .= $key;
				}
			}
			
			if( !empty($blocked_date) ){
				$ret  = '<div class="gdlr-hotel-missing-room">';
				$ret .= '<i class="fa fa-frown-o icon-frown"></i>';
				$ret .= __('Sorry, our hostel is closed on these following dates :', 'gdlr-hotel');
				$ret .= '<br><strong>' . $blocked_date . '</strong>'; 		
				$ret .= '</div>';
				return $ret;
			}
			
			// check if each room is available
			foreach($room_query as $room){
				$room_type = get_post_meta($room->ID, 'gdlr_room_type', true);
				$avail_num = intval(get_post_meta($room->ID, 'gdlr_room_amount', true));

				if( !empty($room_temp[$room->ID]) ){ 
					if( $room_type == 'private' ){
						$avail_num = 0;
					}else{
						$avail_num = $avail_num - $room_temp[$room->ID]; 
					}
				}

				$sql  = "SELECT COUNT(*) "; 
				$sql .= "FROM {$wpdb->prefix}gdlr_hostel_booking, {$wpdb->prefix}gdlr_hostel_payment WHERE ";
				
				// for wpml where room_id = $room
				if( !empty($sitepress) ){
					$count = 0;
					$trid = $sitepress->get_element_trid($room->ID, 'post_room');
					$translations = $sitepress->get_element_translations($trid,'post_room');
					if( !empty($translations) ){
						$sql .= "(";
						foreach( $translations as $translation ){ $count++;
							$sql .= ($count > 1)? 'OR ': '';
							$sql .= "{$wpdb->prefix}gdlr_hostel_booking.room_id = {$translation->element_id} ";
						}
						$sql .= ") AND ";
					}else{
						$sql .= "{$wpdb->prefix}gdlr_hostel_booking.room_id = {$room->ID} AND ";
					}
				}else{
					$sql .= "{$wpdb->prefix}gdlr_hostel_booking.room_id = {$room->ID} AND ";
				}
				
				// where payment_status = selected_status
				$sql .= "{$wpdb->prefix}gdlr_hostel_payment.id = {$wpdb->prefix}gdlr_hostel_booking.payment_id AND ";
				if( $hostel_option['preserve-booking-room'] == 'paid' ){ 
					$sql .= "{$wpdb->prefix}gdlr_hostel_payment.payment_status = 'paid' AND ";
				}else{
					$sql .= "{$wpdb->prefix}gdlr_hostel_payment.payment_status != 'pending' AND ";
				}
				
				// where date within
				$room_free = true;
				foreach($all_date as $key => $val){
					$temp_sql = $sql . "(start_date <= '{$key}' AND end_date > '{$key}')"; 
					if($avail_num <= $wpdb->get_var($temp_sql)){
						$room_free = false;
					}else{
						$all_date[$key] = true;
					}
				}
				if( $room_free ){
					$rooms[] = $room->ID;
				}
			}
			
			// query available room to print out
			if( !empty($rooms) ){			
				$paged = empty($_POST['paged'])? 1: $_POST['paged'];
				$args = array(
					'post_type'=>'hostel_room', 
					'post__in' => $rooms, 
					'posts_per_page'=>$hostel_option['booking-num-fetch'],
					'paged' => $paged
				);
				if( !empty($data['gdlr-hotel-branches']) ){
					$args['tax_query'] = array(array(
						'taxonomy' => 'hostel_room_category',
						'field' => 'id',
						'terms' => intval($data['gdlr-hotel-branches'])
					));
				} 
				$query = new WP_Query($args);
					
				return gdlrs_get_booking_room($query, array(
					'check-in'=> $data['gdlr-check-in'],
					'check-out'=> $data['gdlr-check-out']
				)) . gdlr_get_ajax_pagination($query->max_num_pages, $paged);
			
			// room not available
			}else{
				$no_room_text = '';
				foreach($all_date as $key => $val){
					$no_room_text .= empty($no_room_text)? '': ', '; 
					$no_room_text .= (!$val)? $key: ''; 
				}
				
				$ret  = '<div class="gdlr-hotel-missing-room">';
				$ret .= '<i class="fa fa-frown-o icon-frown"></i>';
				if( !empty($no_room_text) ){
					$ret .= __('Sorry, there\'re no room available in these following dates :', 'gdlr-hotel');
					$ret .= '<br><strong>' . $no_room_text . '</strong>'; 
				}else{
					$ret .= __('Sorry, there\'re no room available within selected dates.', 'gdlr-hotel');
				}
				$ret .= '</div>';
				
				return $ret;
			}
		}
	}
	if( !function_exists('gdlrs_get_booking_room') ){
		function gdlrs_get_booking_room($query, $data){
			global $hostel_option;
			global $gdlr_excerpt_length, $gdlr_excerpt_read_more; 
			$gdlr_excerpt_read_more = false;
			$gdlr_excerpt_length = $hostel_option['booking-num-excerpt'];
			add_filter('excerpt_length', 'gdlr_set_excerpt_length');

			$ret  = '<div class="gdlr-booking-room-wrapper" >';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				$post_option['data'] = $data;
				
				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-medium-room">';
				$ret .= '<div class="gdlr-ux gdlr-medium-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $hostel_option['booking-thumbnail-size']) . '</div>';	
				$ret .= '<div class="gdlr-room-content-wrapper">';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				if( !empty($hostel_option['enable-hotel-branch']) && $hostel_option['enable-hotel-branch'] == 'enable' ){
					$terms = get_the_terms(get_the_ID(), 'hostel_room_category');
					$ret .= '<div class="gdlr-room-hotel-branches">';
					foreach( $terms as $term ){
						$ret .= '<span class="gdlr-separator">,</span>' . $term->name;
					}
					$ret .= '</div>';
				}
				$ret .= gdlr_hostel_room_info($post_option, array('bed', 'max-people', 'view'));
				$ret .= '<div class="gdlr-room-content">' . get_the_excerpt() . '</div>';
				$ret .= '<a class="gdlr-room-selection gdlr-button with-border" href="#" ';
				$ret .= 'data-roomid="' . get_the_ID() . '" >' . __('Select this room', 'gdlr-hotel') . '</a>';
				$ret .= gdlr_hostel_room_info($post_option, array('price-break-down'), false);
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
			}
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>';
			wp_reset_postdata();
		
			$gdlr_excerpt_read_more = true;
			remove_filter('excerpt_length', 'gdlr_set_excerpt_length');	
		
			return $ret;
		}
	}

	if( !function_exists('gdlrs_get_booking_services') ){
		function gdlrs_get_booking_services($branches, $selected_service){
			global $hostel_option, $wpdb;
			
			// get every available services
			$services = array();
			if( !empty($branches) ){
				$sql  = "SELECT DISTINCT wpostmeta.post_id FROM {$wpdb->postmeta} wpostmeta ";
				$sql .= "WHERE wpostmeta.meta_key = 'gdlr-hostel-branches' AND wpostmeta.meta_value LIKE '%\"{$branches}\"%' ";
				$sql .= "ORDER BY post_id DESC";
				$service_query =  $wpdb->get_results($sql, OBJECT);
			
				if( empty($service_query) ){
					return;
				}else{
					foreach( $service_query as $service ){
						$services[] = $service->post_id;
					}
				}
			}else{
				$args = array(
					'post_type'=>'service',
					'posts_per_page'=>99
				);
				$query = new WP_Query($args);
				foreach( $query->posts as $post ){
					$services[] = $post->ID;
				}
			}
			
			if( empty($services) ) return;
			
			ob_start();
?>		
<div class="gdlr-room-selection-divider"></div>
<div class="gdlr-booking-service-wrapper">
	<span class="gdlr-booking-service-head" ><?php _e('Please select your preferred additional services. (optional)', 'gdlr-hotel'); ?></span> 
	<form class="gdlr-booking-service-form" method="post" data-ajax="<?php echo AJAX_URL; ?>" >
	<?php
		$words = array(
			'night' => __('Night', 'gdlr-hotel'),
			'trip' => __('Trip', 'gdlr-hotel'),
			'car' => __('Car', 'gdlr-hotel'),
			'group' => __('Group', 'gdlr-hotel'),
			'guest' => __('Guest', 'gdlr-hotel'),
			'room' => __('Room', 'gdlr-hotel'),
		);

		foreach( $services as $service ){
			$active = in_array($service, $selected_service);
			$option = json_decode(gdlr_decode_preventslashes(get_post_meta($service, 'post-option', true)), true);
			
			echo '<div class="gdlr-room-service-option">';
			echo '<label class="gdlr-room-service-checkbox ' . ($active? 'gdlr-active': '') . '">';
			echo '<input type="checkbox" name="service-select[]" value="' . $service . '" ' . ($active? 'checked': '') . ' />';
			echo '</label>';
			echo '<span class="gdlr-room-service-title">' . get_the_title($service) . '</span>';
			
			if( $option['service-type'] == 'parking-service' && $option['car'] == 'car' ){
				echo '<input type="text" name="service-amount[]" value="1" ' . ($active? '': 'disabled') . ' />';
				echo '<span class="gdlr-room-service-title">' . __('cars', 'gdlr-hotel') . '</span>';
			}else{
				echo '<input type="hidden" name="service-amount[]" value="1" ' . ($active? '': 'disabled') . ' />';
			}
			
			echo '<span class="gdlr-room-service-unit">';
			echo gdlr_hostel_money_format($option['price']);
			echo '<span class="sep">/</span>';
			if( $option['service-type'] == 'regular-service' ){
				echo $words[$option['per']];
			}else if( $option['service-type'] == 'parking-service' ){
				echo $words[$option['car']];
			}
			echo '<span class="sep">/</span>';
			echo $words[$option['unit']];
			echo '</span>';
			echo '</div>';
		}
	?>
	</form>
</div>
<?php
			$ret = ob_get_contents();
			ob_end_clean();
			
			return $ret;
		}
	}

	// booking room style
	if( !function_exists('gdlrs_get_booking_contact_form') ){
		function gdlrs_get_booking_contact_form(){
			global $hostel_option;
			
			ob_start(); 
?>
<div class="gdlr-booking-contact-container">
	<form class="gdlr-booking-contact-form" method="post" data-ajax="<?php echo AJAX_URL; ?>">
		<p class="gdlr-form-half-left">
			<span><?php _e('Name *', 'gdlr-hotel'); ?></span>
			<input type="text" name="first_name" value="" />
		</p>
		<p class="gdlr-form-half-right">
			 <span><?php _e('Last Name *', 'gdlr-hotel'); ?></span>
			 <input type="text" name="last_name" value="" />
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-half-left">
			<span><?php _e('Email *', 'gdlr-hotel'); ?></span>
			<input type="text" name="email" value="" />
		</p>
		<p class="gdlr-form-half-right">
			 <span><?php _e('Phone *', 'gdlr-hotel'); ?></span>
			 <input type="text" name="phone" value="" />
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-half-left">
			<span><?php _e('Address', 'gdlr-hotel'); ?></span>
			<textarea name="address" ></textarea>
		</p>
		<p class="gdlr-form-half-right">
			<span><?php _e('Additional Note', 'gdlr-hotel'); ?></span>
			<textarea name="additional-note" ></textarea>
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-coupon">
			<span><?php _e('Coupon Code', 'gdlr-hotel'); ?></span>
			<input type="text" name="coupon" value="" />
		</p>
		<div class="clear"></div>
		<div class="gdlr-error-message"></div>
		
		<a class="gdlr-button with-border gdlr-booking-contact-submit"><?php _e('Book now by email and we will contact you back.', 'gdlr-hotel'); ?></a>
		
		<?php 
			if( $hostel_option['payment-method'] == 'instant' ){ 
				echo '<div class="gdlr-booking-contact-or">' . __('Or', 'gdlr-hotel');
				echo '<div class="gdlr-booking-contact-or-divider gdlr-left"></div>';
				echo '<div class="gdlr-booking-contact-or-divider gdlr-right"></div>';
				echo '</div>';
			
				if( empty($hostel_option['instant-payment-method']) ){
					$hostel_option['instant-payment-method'] = array('paypal', 'stripe', 'paymill', 'authorize');
				}
				
				if( sizeof($hostel_option['instant-payment-method']) > 1 ){
					echo '<div class="gdlr-payment-method" >';
					foreach( $hostel_option['instant-payment-method'] as $key => $payment_method ){
						echo '<label ' . (($key == 0)? 'class="gdlr-active"':'') . ' >';
						echo '<input type="radio" name="payment-method" value="' . $payment_method . '" ' . (($key == 0)? 'checked':'') . ' />';
						echo '<img src="' . plugins_url('../images/' . $payment_method . '.png', __FILE__) . '" alt="" />';
						echo '</label>';
					}
					echo '</div>';
				}else{
					echo '<input type="hidden" name="payment-method" value="' . $hostel_option['instant-payment-method'][0] . '" />';
				}
				echo '<a class="gdlr-button with-border gdlr-booking-payment-submit">' . __('Pay Now', 'gdlr-hotel') . '</a>';
			}
		?>		
	</form>
</div>
<?php	
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		}
	}
	
		// booking room style
	if( !function_exists('gdlrs_booking_complete_message') ){
		function gdlrs_booking_complete_message(){
			global $hostel_option;
			
			if( !empty($_GET['response_code']) && !empty($_GET['response_reason_text']) ){
				$ret  = '<div class="gdlr-booking-failed">';
				$ret .= '<div class="gdlr-booking-failed-title" >';
				$ret .= __('Payment Failed', 'gdlr-hotel');
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-booking-failed-caption" >';
				$ret .= '<span>' . $_GET['response_code'] . '</span> '; 
				$ret .= $_GET['response_reason_text']; 
				$ret .= '</div>';
				$ret .= '</div>';
			}else{
				$ret  = '<div class="gdlr-booking-complete">';
				$ret .= '<div class="gdlr-booking-complete-title" >';
				$ret .= __('Reservation Completed!', 'gdlr-hotel');
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-booking-complete-caption" >';
				$ret .= __('Your reservation details have just been sent to your email. If you have any question, please don\'t hesitate to contact us. Thank you!', 'gdlr-hotel'); 
				$ret .= '</div>';
				
				if( !empty($hostel_option['booking-complete-contact']) ){
					$ret .= '<div class="gdlr-booking-complete-additional" >' . gdlr_escape_string($hostel_option['booking-complete-contact']) . '</div>';
				}
			}
			$ret .= '</div>';
			return $ret;
		}
	}
		
?>