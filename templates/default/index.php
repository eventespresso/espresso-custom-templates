<?php 
//Template: Calendar Table Display
//Description: This template creates a list of events, displayed in a table. It can dipsplay events by category and/or maximum number of days. 
//Shortcode Example: [EVENT_CUSTOM_VIEW max_days="30" category_identifier="concerts"]. 
//Requirements: CSS skills to customize styles, some renaming of the table columns, Espresso WP User Add-on (optional)

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 
add_action('action_hook_espresso_custom_template_default','espresso_default_custom_template');

function espresso_default_custom_template($events){
	//Load the css file
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/default/style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');
	
	//Defaults
	global $this_event_id; //Used to hold the evnet id for Multi Event Registration
	$featured_image = FALSE; //Show the featured image for each event, instead of the date, to the left of the event title.
	$temp_month = ''; //Clears the month name
	
	?>
	<table class="cal-table-list">

		<?php 
		
		foreach ($events as $event){
			$event_id 			= $event->id;
			$this_event_id		= $event->id;
			$event_name 		= $event->event_name;
			$event_desc			= $event->event_desc;
			$event_desc 		= array_shift(explode('<!--more-->', $event_desc));
			$event_identifier	= $event->event_identifier;
			$active				= $event->is_active;
			$start_date			= $event->start_date;
			$start_time			= $event->start_time;
			$reg_limit			= $event->reg_limit;
			$venue_title = $event->venue_title;
			$event_address = $event->address;
			$event_address2 = $event->address2;
			$event_city = $event->city;
			$event_state = $event->state;
			$event_zip = $event->zip;
			$event_country = $event->country;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event_id);
			$live_button 		= '<a id="a_register_link-'.$event_id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/default/register-now.png" alt="Buy Tickets"></a>';
			$open_spots 		= get_number_of_attendees_reg_limit($event_id, 'number_available_spaces');
			
			//This line changes the button text to display "Closed" if the attendee limit is reached.
			if ( $open_spots < 1 ) { $live_button = 'Closed';  }
			
			//Build the table headers
			$full_month = event_date_display($event->start_date, "F");
			if ($temp_month != $full_month){
				?>
				<tr class="cal-header-month">
					<th class="cal-header-month-name" id="calendar-header-<?php echo $full_month; ?>" colspan="3"><?php echo $full_month; ?></th>
				</tr>
				<tr class="cal-header">
					<th><?php echo $featured_image == FALSE ? __('Date','event_espresso') :  __('Image','event_espresso'); ?></th>
					<th class="th-event-info"><?php _e('Band / Artist','event_espresso'); ?></th>
					<th><?php _e('Tickets','event_espresso'); ?></th>
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
					<?php if ($featured_image == FALSE) {?>
					<td class="td-date-holder">
						<div class="dater">
							<p class="cal-day-title"><?php echo event_date_display($start_date, "l"); ?></p>
							<p class="cal-day-num"><?php echo event_date_display($start_date, "j"); ?></p>
							<p><span><?php echo event_date_display($start_date, "M"); ?></span></p>
						</div>
					</td>
					<?php }else{?>
					<td class="td-fet-image">
						<div class="featured-image">
						<?php 
						//Featured image
						echo apply_filters('filter_hook_espresso_display_featured_image', $event_id, !empty($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : '');?>
						</div>
					</td>
					<?php }?>
					<td class="td-event-info"><span class="event-title"><a href="<?php echo $registration_url ?>"><?php echo stripslashes_deep($event_name) ?></a></span><p><span><?php echo event_date_display($start_date); ?></span></p>
						<?php echo espresso_format_content($event_desc) ?></td>
					<td class="td-event-register"><?php echo $live_button ?></td>
				</tr>
		<?php
			}// close is_user_logged_in	
		 } //close foreach ?>
	</table>
	
<?php } ?>