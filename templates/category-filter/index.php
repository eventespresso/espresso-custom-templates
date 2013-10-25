<?php

//Template: Category Filter
//Description: This template creates a list of events, displayed in a table. It can display events by category and/or maximum number of days.  The table has a javascript filtering system to allow the user to filter the list by category.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="category-filter"]
//Requirements: CSS skills to customize styles, some renaming of the table columns

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default",
add_action('action_hook_espresso_custom_template_category-filter','espresso_custom_template_category_filter');

function espresso_custom_template_category_filter(){

	global $this_event_id, $events, $wpdb;

	//Check for Multi Event Registration
	$multi_reg = false;
	if (function_exists('event_espresso_multi_reg_init')) {
		$multi_reg = true;
	}

	$cart_link 	= '';

	$sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
	$temp_cats = $wpdb->get_results($sql);

?>

<label><?php echo __('Filter by category ', 'event_espresso'); ?></label>
<select class="" id="ee_filter_cat">
	<option class="ee_filter_show_all"><?php echo __('Show All', 'event_espresso'); ?></option>
	<?php
		foreach($temp_cats as $cat) {
			echo '<option class="cat-' . $cat->id . '">'. $cat->category_name . '</option>';
		}
    ?>
</select>
<table id="ee_filter_table" class="espresso-table" width="100%">
	<thead class="espresso-table-header-row">
		<tr>
			<th class="th-group"><?php _e('Course','event_espresso'); ?></th>
			<th class="th-group"><?php _e('Venue','event_espresso'); ?></th>
			<th class="th-group"><?php _e('Date','event_espresso'); ?></th>
			<th class="th-group"></th>
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
		//$open_spots			= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
		$open_spots			= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');
		$live_button = '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'">'.$button_text.'</a>';
		$event_status = event_espresso_get_status($event->id);
		
		if($open_spots < 1 && $event->allow_overflow == 'N') {
			$live_button = __('Sold Out.', 'event_espresso'); 
		} else if ($open_spots < 1 && $event->allow_overflow == 'Y'){
			$live_button = '<a href="'.espresso_reg_url($event->overflow_event_id).'">'.__('Join Waiting List', 'event_espresso').'</a>';
		}
		
		if ($event_status == 'NOT_ACTIVE') { 
			$live_button = __('Closed.', 'event_espresso');
		}

		if ($multi_reg && $event_status == 'ACTIVE' && empty($externalURL)) {
			$params = array(
				//REQUIRED, the id of the event that needs to be added to the cart
				'event_id' => $event->id,
				//REQUIRED, Anchor of the link, can use text or image
				'anchor' => __("Add to Cart", 'event_espresso'), //'anchor' => '<img src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/cart_add.png" />',
				//REQUIRED, if not available at this point, use the next line before this array declaration
				// $event_name = get_event_field('event_name', EVENTS_DETAIL_TABLE, ' WHERE id = ' . $event_id);
				'event_name' => $event->event_name,
				//OPTIONAL, will place this term before the link
				'separator' => ' '.__("or", 'event_espresso').' '
			);

			$cart_link = event_espresso_cart_link($params);
		}
		
		if($event->allow_overflow == 'Y' && $event_status == 'ACTIVE' || $open_spots < 1 || $event_status == 'NOT_ACTIVE') {
				$params = array(
				'event_id' => $event->id,
				'anchor' => __("", 'event_espresso'),
				'event_name' => $event->event_name,
				'separator' => __("", 'event_espresso')
			);

			$cart_link = event_espresso_cart_link($params);
		}

	   ?>
		<tr class="espresso-table-row cat-<?php echo $event->category_id; ?>">
			<td id="event_title-<?php echo $event->id?>" class="event_title"><?php echo stripslashes_deep($event->event_name) ?></td>
			<td id="venue_title-<?php echo $event->id?>" class="venue_title"><?php echo $event->venue_name ?></td>
			<td id="start_date-<?php echo $event->id?>" class="start_date"><?php echo event_date_display($event->start_date.' '.$event->start_time, get_option('date_format').' '.get_option('time_format')) ?></td>
			<td class="td-group"><?php echo $event_status == 'ACTIVE' ? $live_button .  $cart_link : $live_button; ?></td>
		</tr>
		<?php } //close foreach ?>
	</tbody>
</table>
<script type="text/javascript">

jQuery(document).ready(function(){

		jQuery("#ee_filter_cat").change(function() {
				var ee_filter_cat_id = jQuery("option:selected").attr('class');
				console.log(ee_filter_cat_id);
				jQuery("#ee_filter_table .espresso-table-row").show();
				jQuery("#ee_filter_table .espresso-table-row").each(function() {
											   if(!jQuery(this).hasClass(ee_filter_cat_id)) { jQuery(this).hide(); }
											   });
				if( ee_filter_cat_id == 'ee_filter_show_all') { jQuery("#ee_filter_table .espresso-table-row").show(); }
		});


});

</script>
<?php
}
