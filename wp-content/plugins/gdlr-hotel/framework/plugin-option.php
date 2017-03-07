<?php
	/*	
	*	Goodlayers Plugin Option File
	*/
	
	// create admin menu
	add_action('admin_menu', 'gdlr_hotel_add_admin_menu', 99);
	if( !function_exists('gdlr_hotel_add_admin_menu') ){
		function gdlr_hotel_add_admin_menu(){
			$page = add_submenu_page('hotel_option', __('Transaction', 'gdlr-hotel'), __('Transaction', 'gdlr-hotel'), 
				'edit_theme_options', 'hotel-transaction' , 'gdlr_hotel_transaction_option');			
			add_action('admin_print_styles-' . $page, 'gdlr_transaction_option_style');	
			add_action('admin_print_scripts-' . $page, 'gdlr_transaction_option_script');	
		}	
	}
	if( !function_exists('gdlr_transaction_option_style') ){
		function gdlr_transaction_option_style(){
			wp_enqueue_style('gdlr-alert-box', plugins_url('transaction-style.css', __FILE__));		
			wp_enqueue_style('font-awesome', GDLR_PATH . '/plugins/font-awesome-new/css/font-awesome.min.css');		
		}
	}
	if( !function_exists('gdlr_transaction_option_script') ){
		function gdlr_transaction_option_script(){
			wp_enqueue_script('gdlr-alert-box', plugins_url('transaction-script.js', __FILE__));
		}
	}
	
	add_action('after_setup_theme', 'gdlr_create_hotel_admin_option', 99);
	if( !function_exists('gdlr_create_hotel_admin_option') ){
	
		function gdlr_create_hotel_admin_option(){
			global $hotel_option, $gdlr_sidebar_controller;
		
			new gdlr_admin_option( 
				
				// admin option attribute
				array(
					'page_title' => __('Hotel Option', 'gdlr-hotel'),
					'menu_title' => __('Hotel Option', 'gdlr-hotel'),
					'menu_slug' => 'hotel_option',
					'save_option' => 'gdlr_hotel_option',
					'role' => 'edit_theme_options',
					'position' => 83,
				),
					  
				// admin option setting
				array(
					// general menu
					'general' => array(
						'title' => __('General', 'gdlr-hotel'),
						'icon' => GDLR_PATH . '/include/images/icon-general.png',
						'options' => array(
							
							'booking-option' => array(
								'title' => __('Booking Option', 'gdlr-hotel'),
								'options' => array(
									'booking-slug' => array(
										'title' => __('Booking Page Slug', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'booking',
										'description' => __('Please only fill lower case character with no special character here.', 'gdlr-hotel')
									),
									'transaction-per-page' => array(
										'title' => __('Transaction Per Page', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '30',
									),
									'booking-money-format' => array(
										'title' => __('Money Display Format', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '$NUMBER',
									),
									'enable-hotel-branch' => array(
										'title' => __('Enable Hotel Branch ( Using Category )', 'gdlr-hotel'),
										'type' => 'checkbox',	
										'default' => 'disable'
									),
									'preserve-booking-room' => array(
										'title' => __('Preserve The Room After', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'paid' => __('Paid for room', 'gdlr-hotel'),
											'booking' => __('Booking for room', 'gdlr-hotel')
										)
									),
									'booking-price-display' => array(
										'title' => __('Booking Price Display', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'start-from' => __('Start From', 'gdlr-hotel'),
											'full-price' => __('Full Price', 'gdlr-hotel')
										)
									),
									'booking-vat-amount' => array(
										'title' => __('Vat Amount', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '8',
										'description' => __('Input only number ( as percent )', 'gdlr-hotel') . 
											__('Filling 0 to disable this option out.', 'gdlr-hotel'),
									),
									'block-date' => array(
										'title' => __('Block Date', 'gdlr-hotel'),
										'type' => 'textarea',	
										'default' => '',
										'description' => __('Fill the date in yyyy-mm-dd format. Use * for recurring date, separated each date using comma, use the word \'to\' for date range. Ex. *-12-25 to *-12-31 means special season is running every Christmas to New Year\'s Eve every year.', 'gdlr-hotel')
									),
									'booking-deposit-amount' => array(
										'title' => __('Deposit Amount', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '20',
										'description' => __('Allow customer to pay part of price for booking the room ( as percent ).', 'gdlr-hotel') . 
											__('Filling 0 to disable this option out.', 'gdlr-hotel'),
									),
									'payment-method' => array(
										'title' => __('Payment Method', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'contact' =>  __('Only Contact Form', 'gdlr-hotel'),
											'instant' =>  __('Include Instant Payment', 'gdlr-hotel'),
										)
									),
									'instant-payment-method' => array(
										'title' => __('Instant Payment Method', 'gdlr-hotel'),
										'type' => 'multi-combobox',	
										'options' => array(
											'paypal' =>  __('Paypal', 'gdlr-hotel'),
											'stripe' =>  __('Stripe', 'gdlr-hotel'),
											'paymill' =>  __('Paymill', 'gdlr-hotel'),
											'authorize' =>  __('Authorize.Net', 'gdlr-hotel'),
										),
										'wrapper-class' => 'payment-method-wrapper instant-wrapper',
										'description' => __('Leaving this field blank will display all available payment method.', 'gdlr-hotel')
									),							
									'booking-thumbnail-size' => array(
										'title' => __('Booking Thumbnail Size', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_get_thumbnail_list(),
										'default'=> 'small-grid-size'
									),
									'booking-num-fetch' => array(
										'title' => __('Booking Num Fetch', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '5',
									),
									'booking-num-excerpt' => array(
										'title' => __('Booking Num Excerpt', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '34',
									),
								)
							),
							
							'booking-mail' => array(
								'title' => __('Booking Mail', 'gdlr-hotel'),
								'options' => array(
									'recipient-name' => array(
										'title' => __('Recipient Name', 'gdlr-hotel'),
										'type' => 'text'
									),
									'recipient-mail' => array(
										'title' => __('Recipient Email', 'gdlr-hotel'),
										'type' => 'text'
									),
									'booking-complete-contact' => array(
										'title' => __('Booking Complete Contact', 'gdlr-hotel'),
										'type' => 'textarea'
									),
									'booking-code-prefix' => array(
										'title' => __('Booking Code Prefix', 'gdlr-hotel'),
										'type' => 'text',
										'default' => 'GDLR'
									),
								)
							),
								
							'room-style' => array(
								'title' => __('Room Style', 'gdlr-hotel'),
								'options' => array(		
									'room-thumbnail-size' => array(
										'title' => __('Single Room Thumbnail Size', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_get_thumbnail_list(),
										'default'=> 'post-thumbnail-size'
									),
								)
							),
							
							'paypal-payment-info' => array(
								'title' => __('Paypal Info', 'gdlr-hotel'),
								'options' => array(	
									'paypal-recipient-email' => array(
										'title' => __('Paypal Recipient Email', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'testmail@test.com'
									),
									'paypal-action-url' => array(
										'title' => __('Paypal Action URL', 'gdlr-hotel'),
										'type' => 'text',
										'default' => 'https://www.paypal.com/cgi-bin/webscr'
									),
									'paypal-currency-code' => array(
										'title' => __('Paypal Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'USD'
									),						
								)
							),
							
							'stripe-payment-info' => array(
								'title' => __('Stripe Info', 'gdlr-hotel'),
								'options' => array(	
									'stripe-secret-key' => array(
										'title' => __('Stripe Secret Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'stripe-publishable-key' => array(
										'title' => __('Stripe Publishable Key', 'gdlr-hotel'),
										'type' => 'text'
									),	
									'stripe-currency-code' => array(
										'title' => __('Stripe Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'usd'
									),	
								)
							),
							
							'paymill-payment-info' => array(
								'title' => __('Paymill Info', 'gdlr-hotel'),
								'options' => array(	
									'paymill-private-key' => array(
										'title' => __('Paymill Private Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'paymill-public-key' => array(
										'title' => __('Paymill Public Key', 'gdlr-hotel'),
										'type' => 'text'
									),	
									'paymill-currency-code' => array(
										'title' => __('Paymill Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'usd'
									),
								)
							),
							
							'authorize-payment-info' => array(
								'title' => __('Authorize Info', 'gdlr-hotel'),
								'options' => array(	
									'authorize-api-id' => array(
										'title' => __('Authorize API Login ID ', 'gdlr-hotel'),
										'type' => 'text'
									),
									'authorize-transaction-key' => array(
										'title' => __('Authorize Transaction Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'authorize-md5-hash' => array(
										'title' => __('Authorize MD5 Hash', 'gdlr-hotel'),
										'type' => 'text'
									),
								)
							),					
						)
					)
				),
				
				$hotel_option
			);
		}
	}
?>