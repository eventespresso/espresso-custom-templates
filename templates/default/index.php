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
	global $org_options, $this_event_id; //Used to hold the evnet id for Multi Event Registration
	$featured_image = FALSE; //Show the featured image for each event, instead of the date, to the left of the event title.
	$temp_month = ''; //Clears the month name
	
	//Uncomment to view the data being passed to this file
	//echo '<h4>$events : <pre>' . print_r($events,true) . '</pre> <span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
	
	?>
	<table class="cal-table-list">

		<?php 
		foreach ($events as $event){
			$this_event_id		= $event->id;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$live_button 		= '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/default/register-now.png" alt="Buy Tickets"></a>';
			$open_spots 		= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');
			
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
							<p class="cal-day-title"><?php echo event_date_display($event->start_date, "l"); ?></p>
							<p class="cal-day-num"><?php echo event_date_display($event->start_date, "j"); ?></p>
							<p><span><?php echo event_date_display($event->start_date, "M"); ?></span></p>
						</div>
					</td>
					<?php }else{?>
					<td class="td-fet-image">
						<div class="featured-image">
						<?php 
						//Featured image
						echo apply_filters('filter_hook_espresso_display_featured_image', $event->id, !empty($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : '');?>
						</div>
					</td>
					<?php }?>
					<td class="td-event-info">
						<span class="event-title"><a href="<?php echo $registration_url ?>"><?php echo stripslashes_deep($event->event_name) ?></a></span>
						<p><?php _e('When:', 'event_espresso'); ?> <?php echo event_date_display($event->start_date); ?><br />
						<?php _e('Where:', 'event_espresso'); ?> <?php echo stripslashes_deep($event->venue_address.', '.$event->venue_city.', '.$event->venue_state); ?><br />
						<?php _e('Price: ', 'event_espresso'); ?> <?php echo  $org_options['currency_symbol'].$event->event_cost; ?></p>
						<?php echo espresso_format_content(array_shift(explode('<!--more-->', $event->event_desc))); //Includes <p> tags ?>
					</td>
					<td class="td-event-register"><?php echo $live_button ?></td>
				</tr>
		<?php
			}// close is_user_logged_in	
		 } //close foreach ?>
	</table>
	
<?php } ?>