<?php
/**
 * Plugin Overviews.
 * @package Maps
 * @author Flipper Code <flippercode>
 **/

?>

<div class="container wpgmp-docs">
<div class="row flippercode-main">
<div class="col-md-12">
<h4 class="alert alert-info"> <?php _e( 'How to Create your First Map?',WPGMP_TEXT_DOMAIN ); ?> </h4>
<div class="wpgmp-overview">
<ol>
<li><?php
$url = admin_url( 'admin.php?page=wpgmp_manage_settings' );
$link = sprintf( wp_kses( __( 'First create an  <a target="_blank" href="http://bit.ly/29Rlmfc"  target="_blank" >  Google Map API Key</a>, Then go to <a href="%s" target="_blank"> Settings </a> Page and insert your google maps API Key and save it.', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</li>
<li><?php
$url = admin_url( 'admin.php?page=wpgmp_form_location' );
$link = sprintf( wp_kses( __( 'Create a location by using <a href="%s" target="_blank">Add Location</a>. To add bulk locations at once, Go for <a href="https://codecanyon.net/item/advanced-google-maps-plugin-for-wordpress/5211638" target="_blank" > PRO Version</a>.', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</li>
<li><?php
$url = admin_url( 'admin.php?page=wpgmp_form_map' );
$link = sprintf( wp_kses( __( 'Go to <a href="%s" target="_blank">Add Map</a> and insert details as shown in the form. Choose locations which you want to display on the map and save it. ', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</li>

<li><?php
$link = sprintf( wp_kses( __( 'Congratulations, You have created your first map.', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</li>
                
</ol>
</div>

<h4 class="alert alert-info"> <?php _e( 'How to Display Map in Frontend?',WPGMP_TEXT_DOMAIN ); ?> </h4>
<div class="wpgmp-overview">

<p><?php
$url = admin_url( 'admin.php?page=wpgmp_manage_map' );
$link = sprintf( wp_kses( __( 'Go to <a href="%s" target="_blank"> Manage Map</a> and copy the shortcode then paste it to any page/post where you want to display map. ', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</p>

</div>

<h4 class="alert alert-info"> <?php _e( 'Pro Version',WPGMP_TEXT_DOMAIN ); ?> </h4>
<div class="wpgmp-overview">
<ul>		
<li>
You can upgrade to pro version to display posts on google maps, show locations below the map, filters by categories, show clickable shapes on the google maps, routes, directions, custom google maps skins and many more awesome features. 
</li>

<li>
<?php
$link = sprintf( wp_kses( __( ' Click on <a href="http://www.flippercode.com/product/wp-google-map-pro/" target="_blank">Live Demo</a> to view all in actions.', WPGMP_TEXT_DOMAIN ), array( 'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( $url ) );
echo $link;?>
</li>		
</ol>    
</div>	

<h4 class="alert alert-info"> <?php _e( 'Google Map API Troubleshooting',WPGMP_TEXT_DOMAIN ); ?> </h4>
<div class="wpgmp-overview">
<p>If your google maps is not working. Make sure you have checked following things.</p>
<ul>
<li> 1. Make sure you have assigned locations to your map.</li>
<li> 2. You must have google maps api key.</li>
<li> 3. Check HTTP referrers. It must be *.yourwebsite.com/* <br>
<p><img src="<?php echo WPGMP_IMAGES; ?>referrer.png"> </p>
</li>
</ul>
<p>If still any issue, Create your <a target="_blank" href="http://www.flippercode.com/forums">support ticket</a> and we'd be happy to help you asap. </p>
</div>		
</div>
</div>
</div>
