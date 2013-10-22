<?php
//Template: Calendar Table
//Description: This template creates a list of events, displayed in a table. It can dipsplay events by category and/or maximum number of days.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="calendar-table" max_days="30" category_identifier="concerts"].
//Requirements: CSS skills to customize styles, some renaming of the table columns, Espresso WP User Add-on (optional)

// Parameter: show_featured=true/false. If set to true, the dates will be replaced with the featured images.
// Parameter: change_title="something". If set the Band/Artist default title will be change to the strng provided.

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "calendar-table",
add_action('action_hook_espresso_custom_template_calendar-table','espresso_custom_template_calendar_table');

function espresso_custom_template_calendar_table(){

	global $org_options, $this_event_id, $events, $ee_attributes;

	//Extract shortcode attributes, if any.
	extract($ee_attributes);

	if(isset($ee_attributes['show_featured'])) { $show_featured = $ee_attributes['show_featured']; }

	if(isset($ee_attributes['change_title'])) { $change_title = $ee_attributes['change_title']; }

	//Load the css file
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/calendar-table/style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');

	//Clears the month name
	$temp_month = '';

	//Uncomment to view the data being passed to this file
	//echo '<h4>$events : <pre>' . print_r($events,true) . '</pre> <span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';

	?>

<table class="cal-table-list">
	<?php
		foreach ($events as $event){
			//Debug
			$this_event_id		= $event->id;
			$this_event_desc	= explode('<!--more-->', $event->event_desc);
			$this_event_desc 	= array_shift($this_event_desc);
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$live_button 		= '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/calendar-table/register-now.png" alt="Buy Tickets"></a>';
			$open_spots 		= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
			$featured_image		= isset($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : FALSE;

			//This line changes the button text to display "Closed" if the attendee limit is reached.
			if ( $open_spots < 1 || event_espresso_get_status($event->id) == 'NOT_ACTIVE' ) { $live_button = '<img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/calendar-table/closed.png" alt="Buy Tickets">';  }

			//waitlist
			if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
				$live_button = '<a href="'.espresso_reg_url($event->overflow_event_id).'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/calendar-table/waiting.png" alt="Join Waiting List"></a>';
			}

			//Build the table headers
			$full_month = event_date_display($event->start_date, "F");
			if ($temp_month != $full_month){
				?>
	<tr class="cal-header-month">
		<th class="cal-header-month-name" id="calendar-header-<?php echo $full_month; ?>" colspan="3"><?php echo $full_month; ?></th>
	</tr>
	<tr class="cal-header">
		<th><?php echo !isset($show_featured) || $show_featured === 'false' ? __('Date','event_espresso') :  '' ?></th>
		<th class="th-event-info"><?php if(isset($change_title)) { echo $change_title; } else { _e('Band / Artist','event_espresso'); } ?></th>
		<th class="th-tickets"><?php _e('Tickets','event_espresso'); ?></th>
	</tr>
	<?php
				$temp_month = $full_month;
			}

			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');

			//If enough spaces exist then show the form
			//Check to see if the Members plugin is installed.
			if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ) {
				event_espresso_user_login();
			}else{
				?>
	<tr class="">


		<?php

		if(isset($show_featured ) && $show_featured === 'true') { ?>
			<td class="td-fet-image"><div class="">
				<img src="<?php echo $featured_image; ?>" />
			</div></td>
		<?php } else { ?>
		<td class="td-date-holder"><div class="dater">
				<p class="cal-day-title"><?php echo event_date_display($event->start_date, "l"); ?></p>
				<p class="cal-day-num"><?php echo event_date_display($event->start_date, "j"); ?></p>
				<p><span><?php echo event_date_display($event->start_date, "M"); ?></span></p>
			<?php } ?>
			</div>
		</td>

		<td class="td-event-info"><span class="event-title"><a href="<?php echo $registration_url ?>"><?php echo stripslashes_deep($event->event_name); ?></a></span>
			<p>
				<?php _e('When:', 'event_espresso'); ?>
				<?php echo event_date_display($event->start_date); ?><br />
				<?php _e('Where:', 'event_espresso'); ?>
				<?php echo stripslashes_deep($event->venue_address.', '.$event->venue_city.', '.$event->venue_state); ?><br />
				<?php _e('Price: ', 'event_espresso'); ?>
				<?php echo  $org_options['currency_symbol'].$event->event_cost; ?> </p>
			<?php echo espresso_format_content(array_shift(explode('<!--more-->', $event->event_desc))); //Includes <p> tags ?></td>
		<td class="td-event-register"><?php echo $live_button ?></td>
	</tr>
	<?php
			}// close is_user_logged_in
		 } //close foreach ?>
</table>
<?php
}
