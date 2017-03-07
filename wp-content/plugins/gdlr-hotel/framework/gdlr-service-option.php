<?php
	/*	
	*	Goodlayers Services Option file
	*	---------------------------------------------------------------------
	*	This file creates all service options and attached to the theme
	*	---------------------------------------------------------------------
	*/

	// add action to create service post type
	add_action( 'init', 'gdlr_create_service' );
	if( !function_exists('gdlr_create_service') ){
		function gdlr_create_service() {
			register_post_type( 'service',
				array(
					'labels' => array(
						'name'               => __('Services', 'gdlr-hotel'),
						'singular_name'      => __('Service', 'gdlr-hotel'),
						'add_new'            => __('Add New', 'gdlr-hotel'),
						'add_new_item'       => __('Add New Service', 'gdlr-hotel'),
						'edit_item'          => __('Edit Service', 'gdlr-hotel'),
						'new_item'           => __('New Service', 'gdlr-hotel'),
						'all_items'          => __('All Service', 'gdlr-hotel'),
						'view_item'          => __('View Service', 'gdlr-hotel'),
						'search_items'       => __('Search Service', 'gdlr-hotel'),
						'not_found'          => __('No service found', 'gdlr-hotel'),
						'not_found_in_trash' => __('No service found in Trash', 'gdlr-hotel'),
						'parent_item_colon'  => '',
						'menu_name'          => __('Service', 'gdlr-hotel')
					),
					'public'             => true,
					'publicly_queryable' => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
					'query_var'          => true,
					'rewrite'            => false,
					'capability_type'    => 'post',
					'has_archive'        => true,
					'hierarchical'       => false,
					'menu_position'      => 5,
					'supports'           => array( 'title', 'custom-fields' )
				)
			);
			
			// add filter to style single template
			add_filter('single_template', 'gdlr_register_service_template');
		}
	}	
	
	if( !function_exists('gdlr_register_service_template') ){
		function gdlr_register_service_template($template){
			if( get_post_type() == 'service' ){
				$template = GDLR_LOCAL_PATH . '/404.php';
			}
			return $template;
		}
	}
	
	// add a room option to room page
	if( is_admin() ){ add_action('init', 'gdlr_create_service_options', 11); }
	if( !function_exists('gdlr_create_service_options') ){
		function gdlr_create_service_options(){
			if( !class_exists('gdlr_page_options') ) return;
			
			$branches = array();
		
			global $hotel_option;
			if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){ 
				$branches = array_merge($branches, array(
					'branches' => array(
						'title' => __('Assign to Hotel Branches' , 'gdlr-hotel'),
						'type' => 'multi-combobox',
						'options' => gdlr_get_term_id_list('room_category'),
						'custom_field' => 'gdlr-branches'
					),
				));
			}

			global $hostel_option;
			if( !empty($hostel_option['enable-hotel-branch']) && $hostel_option['enable-hotel-branch'] == 'enable' ){ 
				$branches = array_merge($branches, array(
					'hostel-branches' => array(
						'title' => __('Assign to Hostel Branches' , 'gdlr-hotel'),
						'type' => 'multi-combobox',
						'options' => gdlr_get_term_id_list('hostel_room_category'),
						'custom_field' => 'gdlr-hostel-branches'
					),
				));
			}
			
			new gdlr_page_options(
				
				// page option attribute
				array(
					'post_type' => array('service'),
					'meta_title' => __('Goodlayers Service Option', 'gdlr-hotel'),
					'meta_slug' => 'goodlayers-page-option',
					'option_name' => 'post-option',
					'position' => 'normal',
					'priority' => 'high',
				),
				
				array(
					'page-layout' => array(
						'title' => __('Option', 'gdlr-hotel'),
						'options' => array_merge(array(
							'service-type' => array(
								'title' => __('Service Type' , 'gdlr-hotel'),
								'type' => 'combobox',
								'options' => array(
									'regular-service' => __('Regular Service' , 'gdlr-hotel'),
									'parking-service' => __('Parking Service' , 'gdlr-hotel'),
								)
							),
							'price' => array(
								'title' => __('Price (*Only Number)' , 'gdlr-hotel'),
								'type' => 'text',
								'wrapper-class' => 'four columns'
							),
							'per' => array(
								'title' => __('Per' , 'gdlr-hotel'),
								'type' => 'combobox',
								'options' => array(
									'guest' => __('Guest' , 'gdlr-hotel'),
									'room' => __('Room' , 'gdlr-hotel'),
									'group' => __('Group' , 'gdlr-hotel'),
								),
								'wrapper-class' => 'service-type-wrapper regular-service-wrapper four columns no-action'
							),
							'car' => array(
								'title' => __('Per' , 'gdlr-hotel'),
								'type' => 'combobox',
								'options' => array(
									'car' => __('Car' , 'gdlr-hotel'),
									'group' => __('Group' , 'gdlr-hotel'),
								),
								'wrapper-class' => 'service-type-wrapper parking-service-wrapper four columns no-action'
							),
							'unit' => array(
								'title' => __('Per' , 'gdlr-hotel'),
								'type' => 'combobox',
								'options' => array(
									'night' => __('Night' , 'gdlr-hotel'),
									'trip' => __('Trip' , 'gdlr-hotel'),
								),
								'wrapper-class' => 'four columns'
							),
							'clear-1' => array( 'type' => 'clear' )
						), $branches)
					),
				)
			);
		}
	}
	
?>