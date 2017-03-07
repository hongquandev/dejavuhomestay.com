jQuery(document).ready(function($) {
	
	var i;
	for (i = 0; i < fcProductPointers.pointers.length; i++) {
		
		pointer = fcProductPointers.pointers[i];
		console.log(fcProductPointers.pointers);
		options = $.extend( pointer.options, {
			close: function() {
				$.post( fcProductPointers.ajaxurl, {
					pointer: pointer.pointer_id,
					action: 'dismiss-wp-pointer'
				});
			}
		});
 		$(pointer.target).pointer( options ).pointer('open');
		
	}
    
});
