<?php
	/*	
	*	Goodlayers Booking File
	*/

	add_filter('template_include', 'gdlr_hotel_booking_template');
	if( !function_exists('gdlr_hotel_booking_template') ){
		function gdlr_hotel_booking_template( $template ){
			global $hotel_option;
			if( isset($_GET[$hotel_option['booking-slug']]) ){
				return dirname(dirname(__FILE__)) . '/single-booking.php';
			}
			return $template;
		}
	}
	
	add_filter('body_class', 'gdlr_booking_template_class');
	if( !function_exists('gdlr_booking_template_class') ){
		function gdlr_booking_template_class( $classes ){
			global $hotel_option;
			if( isset($_GET[$hotel_option['booking-slug']]) ){
				$classes[] = 'single-booking';
			}
			return $classes;
		}
	}

?>