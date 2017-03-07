<?php
	/*	
	*	Goodlayers Booking File
	*/

	add_filter('template_include', 'gdlrs_hostel_booking_template');
	if( !function_exists('gdlrs_hostel_booking_template') ){
		function gdlrs_hostel_booking_template( $template ){
			global $hostel_option;
			if( isset($_GET[$hostel_option['booking-slug']]) ){
				return dirname(dirname(__FILE__)) . '/single-booking.php';
			}
			return $template;
		}
	}
	
	add_filter('body_class', 'gdlrs_booking_template_class');
	if( !function_exists('gdlrs_booking_template_class') ){
		function gdlrs_booking_template_class( $classes ){
			global $hostel_option;
			if( isset($_GET[$hostel_option['booking-slug']]) ){
				$classes[] = 'single-booking';
			}
			return $classes;
		}
	}

?>