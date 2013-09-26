<script type="application/javascript">

jQuery(document).ready(function(){
								
	//if javascript enabled disable CSS hover effect.
	jQuery('.dd_js').removeClass('dd_js');
	jQuery('#espresso-select li ul').css('display', 'none');
	
	
	jQuery('#espresso-select').click(function() {									  
		jQuery('#espresso-select ul').toggle();
	});


});


</script>



<?php 
//Template Name: CSS Dropdown
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="css-dropdown"]

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 
add_action('action_hook_espresso_custom_template_css-dropdown','espresso_css_dropdown', 10, 1);
if (!function_exists('espresso_css_dropdown')) {
	function espresso_css_dropdown(){	
		global $events;
	
		
		//Register styles
		wp_register_style( 'espresso_css_dropdown', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."templates/css-dropdown/style.css" );
		wp_enqueue_style( 'espresso_css_dropdown');
		
		wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=Open+Sans:400,700');
		wp_enqueue_style( 'googleFonts');

        wp_register_style('fontAwesome', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."templates/css-dropdown/font-awesome/font-awesome.css" );
		wp_enqueue_style( 'fontAwesome');	
?>

<div>
    <ul id="espresso-select" class="dd_js">
        <li>
            <h3><a href="#">
                <?php _e('Upcoming Events', 'event_espresso'); ?>
                </a></h3>
            <i class="icon-chevron-sign-down"></i>
            <ul>
                <?php 
            foreach ($events as $event){ 
                $externalURL = $event->externalURL; $registration_url = !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);?>
                <li><a class="a_event_title" id="a_event_title-<?php echo $event->id; ?>" href="<?php echo $registration_url; ?>"><?php echo stripslashes_deep($event->event_name)?><br />
                    <?php echo event_date_display($event->start_date, 'M j, Y'); ?></a> </li>
                <?php 
            }
            ?>
            </ul>
        </li>
    </ul>
</div>
<?php
	}
}
