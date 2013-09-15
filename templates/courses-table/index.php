<?php 
//Template: Course Listings Table
//Description: This template creates a list of events, displayed in a table. It can dipsplay events by category and/or maximum number of days. 
//Shortcode Example: [EVENT_CUSTOM_VIEW max_days="30" category_identifier="concerts"]. 
//Requirements: CSS skills to customize styles, some renaming of the table columns, Espresso WP User Add-on (optional)

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 
add_action('action_hook_espresso_custom_template_courses-table','espresso_custom_template_courses_table');

function espresso_custom_template_courses_table(){
	
	global $this_event_id, $events;
	
	//Check for Multi Event Registration
	$multi_reg = false;
	if (function_exists('event_espresso_multi_reg_init')) {
		$multi_reg = true;
	}
	
	$cart_link 	= '';
	?>
	<table class="espresso-table" width="100%">
  
      <thead class="espresso-table-header-row">
      <tr>
          <th class="th-group"><?php _e('Course','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Location','event_espresso'); ?></th>
          <th class="th-group"><?php _e('City','event_espresso'); ?></th>
          <th class="th-group"><?php _e('State','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Date','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Time','event_espresso'); ?></th>
          <th class="th-group"><?php _e('','event_espresso'); ?></th>
     </tr>
      </thead>
	<tbody>

      <?php
      
      foreach ($events as $event){
		$button_text 		= __('Register', 'event_espresso');
		$alt_button_text	= __('View Details', 'event_espresso');//For alternate registration pages
		$externalURL 		= $event->externalURL;
		$button_text		= !empty($externalURL) ? $alt_button_text : $button_text;
		$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
		$open_spots			= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
		$live_button 		= $open_spots < 1 ? __('Closed', 'event_espresso') : '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'">'.$button_text.'</a>';
		
		if ($multi_reg && event_espresso_get_status($event->id) == 'ACTIVE' && empty($externalURL)) { 
			$params = array(
				//REQUIRED, the id of the event that needs to be added to the cart
				'event_id' => $event->id,
				//REQUIRED, Anchor of the link, can use text or image
				'anchor' => __("Add to Cart", 'event_espresso'), //'anchor' => '<img src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/cart_add.png" />',
				//REQUIRED, if not available at this point, use the next line before this array declaration
				// $event_name = get_event_field('event_name', EVENTS_DETAIL_TABLE, ' WHERE id = ' . $event_id);
				'event_name' => $event->event_name,
				//OPTIONAL, will place this term before the link
				'separator' => __(" or ", 'event_espresso')
			);

			$cart_link = event_espresso_cart_link($params);
		}
		
		
	   ?>
      <tr class="espresso-table-row">
       	<td class="td-group">
            <?php echo stripslashes_deep($event->event_name) ?>
          </td>	
          <td class="td-group">
            <?php echo $event->venue_address ?>
          </td>
          <td class="td-group">
            <?php echo $event->venue_city ?>
          </td>
      	  <td class="td-group">
            <?php echo $event->venue_state ?>
          </td>
          <td class="td-group">
              <?php echo event_date_display($event->start_date, $format = 'l, M d, Y') ?>
          </td>
          <td class="td-group">
              <?php echo espresso_event_time($event->id, 'start_time', get_option('time_format')) ?>
          </td>
         
          <td class="td-group">
              <?php echo $live_button. $cart_link ?>
          </td>
      </tr>
      <?php } //close foreach ?>
</tbody>
</table>

<?php
}