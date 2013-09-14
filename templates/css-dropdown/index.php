<?php 
/*
Shortcode Name: CSS Dropdown
Author: Event Espresso
Contact: support@eventespresso.com
Website: http://eventespresso.com
*/

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 
add_action('action_hook_espresso_custom_template_css-dropdown','espresso_css_dropdown', 10, 1);
if (!function_exists('espresso_css_dropdown')) {
	function espresso_css_dropdown(){	
		global $events;
		
		//Register styles
		wp_register_style( 'espresso_css_dropdown', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."templates/css-dropdown/style.css" );
		wp_enqueue_style( 'espresso_css_dropdown');
		
		//Load JS files
		add_action('wp_footer', 'espresso_load_css_dropdown_files');
		function espresso_load_css_dropdown_files() {	 	
			wp_register_script('yahoo-dom-event', (ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . "templates/css-dropdown/scripts/yahoo-dom-event.js"), false, EVENT_ESPRESSO_VERSION); 
			wp_enqueue_script('yahoo-dom-event');
					
			wp_register_script('animation-min', (ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . "templates/css-dropdown/scripts/animation-min.js"), false, EVENT_ESPRESSO_VERSION); 
			wp_enqueue_script('animation-min');
					
			wp_register_script('main-javascript', (ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . "templates/css-dropdown/scripts/main-javascript.js"), false, EVENT_ESPRESSO_VERSION); 
			wp_enqueue_script('main-javascript');
		}
		
		?>

<div id="lhsHeader6" class="leftBoxHeading_Off" onClick="lhsAction('6',true,'T6_Effective_Behaviour_Change');"><div><?php _e('View Events', 'event_espresso'); ?></div></div>
        <div id="lhsExpander6" class="leftBoxExpander">
          <div id="lhsInner6" class="leftBoxInnerPic"> <img src="<?php echo ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH; ?>templates/css-dropdown/images/left-box-inner-img.png" alt="Left image" height="18" width="486" />
                <ul class="css-dropdown-ul">
				<?php //Debug
					//echo '<h4>$events : <pre>' . print_r($events,true) . '</pre> <span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';?>
				<?php foreach ($events as $event){
						$externalURL 		= $event->externalURL;
						$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);	
				?>
                	<li class="css-dropdown-event"><a title="<?php echo stripslashes_deep($event->event_name); ?>" class="a_event_title" id="a_event_title-<?php echo $event->id; ?>" href="<?php echo $registration_url; ?>"><?php echo stripslashes_deep($event->event_name)?> - <?php echo event_date_display($event->start_date, 'M j, Y'); ?></a></li>
				<?php }?>
                </ul>
          </div>
        </div>
        <div id="lhsFooter6" class="leftBoxFooter_Off" onClick="lhsAction('6',true,'false');"></div>

<?php
	}
}
