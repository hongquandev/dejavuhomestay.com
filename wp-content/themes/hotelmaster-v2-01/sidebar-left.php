<?php
/**
 * A template for calling the left sidebar on everypage
 */
 
	global $gdlr_sidebar;
?>

<?php if( $gdlr_sidebar['type'] == 'left-sidebar' || $gdlr_sidebar['type'] == 'both-sidebar' ){ ?>
<div class="gdlr-sidebar gdlr-left-sidebar <?php echo esc_attr($gdlr_sidebar['left']); ?> columns">
	<div class="gdlr-item-start-content sidebar-left-item" >
	<?php dynamic_sidebar($gdlr_sidebar['left-sidebar']); ?>
	</div>
</div>
<?php } ?>