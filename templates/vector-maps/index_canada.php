<?php 
//Template: Vector Map - CANADIAN VERSION!

//Description: This template displays a jQuery vector (SVG) map of a specific country, and highlights those regions when there is an event in the list that has a venue there. Clicking the state/region will display the events details.

//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="default" max_days="30" category_identifier="concerts"]. 
//Requirements: CSS skills to customize styles. Events must have venues, those venues must have the State filled out in long form or international shortform (e.g New York or NY, Ontario or ON)

//This template only has a couple of maps and is geared to use North America. You can find more maps and details on how to create them here https://github.com/manifestinteractive/jqvmap 

// CANADIAN? Rename the default index.php file and then rename the index_canada.php file to index.php, then you will get a map of the  Great White North.

// BRITISH? Find me an SVG map of the UK's counties! Everything I can find is geared to just England...

add_action('action_hook_espresso_custom_template_vector-maps','espresso_custom_template_vector_maps');

function espresso_custom_template_vector_maps(){
	
	//Load the css and script files
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');

	wp_register_script( 'jquery_canada', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.canada.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_canada' );
	
	wp_register_script( 'jquery_vmap', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.vmap.min.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap' );
	
	wp_register_script( 'jquery_vmap_location', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/maps/jquery.vmap.canada.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap_location' );
	
	wp_register_style( 'jquery_vmap_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/js/jqvmap.css" );
	wp_enqueue_style( 'jquery_vmap_css');


	
	global $org_options, $this_event_id, $events, $wpdb;
	$featured_image = FALSE; //Show the featured image for each event, instead of the date, to the left of the event title.
	$temp_month = ''; //Clears the month name
	
	$sql = "SELECT * FROM " . EVENTS_VENUE_REL_TABLE;
	$temp_venues = $wpdb->get_results($sql);

	foreach ($events as $venues) {
		if($venues->venue_state) { $ven_event_count[$venues->venue_state]++; }
	}

	echo "<div id='hidden_states'>";
	foreach ($ven_event_count as $key => $value) {
		$state = state_convert($key);
			 //echo "<input name='ee_states[]' type='hidden' id='s_" . $state . "' value='" . $value . "' />";
			 echo "<input name='" . $state . "' type='hidden' value='" . $value . "' />";
			 }
	echo "</div>";

?>



<div id="eemap"></div>





<h2 id="events_in">Events in </h2>

	<table class="usa-table-list">
				<tr class="cal-header hide">
					<th><?php echo $featured_image == FALSE ? __('Date','event_espresso') :  __('Image','event_espresso'); ?></th>
					<th class="th-event-info"><?php _e('Event','event_espresso'); ?></th>
					<th><?php _e('Tickets','event_espresso'); ?></th>
				</tr>

		<?php 

		foreach ($events as $event){
			//Debug
			$this_event_id		= $event->id;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$live_button 		= '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/vector-maps/register-now.png" alt="Buy Tickets"></a>';
			$open_spots 		= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
			
			//This line changes the button text to display "Closed" if the attendee limit is reached.
			if ( $open_spots < 1 ) { $live_button = 'Closed';  }
			
			//Build the table headers
			
			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');
	
			//If enough spaces exist then show the form
			//Check to see if the Members plugin is installed.
			if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ) {
				event_espresso_user_login();
			}else{
				?>
				<tr class="usa_map_row <?php echo state_convert($event->venue_state); ?>">
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
						<span class="event-title"><a href="<?php echo $registration_url ?>"><?php echo stripslashes_deep($event->event_name); ?></a></span>
						<p>
							<?php _e('When:', 'event_espresso'); ?> <?php echo event_date_display($event->start_date); ?><br />
							<?php _e('Where:', 'event_espresso'); ?> <?php echo stripslashes_deep($event->venue_address.', '.$event->venue_city.', '.$event->venue_state); ?><br />
							<?php _e('Price: ', 'event_espresso'); ?> <?php echo  $org_options['currency_symbol'].$event->event_cost; ?>
						</p>
						<?php echo espresso_format_content(array_shift(explode('<!--more-->', $event->event_desc))); //Includes <p> tags ?>
					</td>
					<td class="td-event-register"><?php echo $live_button ?></td>
				</tr>
		<?php
			}// close is_user_logged_in	
		 } //close foreach ?>
         
 				<tr class="usa_map_row noevents">
                <td>
                No Events Available.
                </td>
                </tr>        
	</table>
	


<?php



 } //end of main function!!
 
 
 
 
 
 
 function state_convert($x) {
	
	$state = strtolower($x);

	if(strlen($state) > 2) { 
		if($state == 'alberta') { $y = "AB"; }
		if($state == 'british columbia') { $y = "BC"; }
		if($state == 'manitoba') { $y = "MB"; }
		if($state == 'new brunswick') { $y = "NB"; }
		if($state == 'newfoundland and labrador') { $y = "NL"; }
		if($state == 'nova scotia') { $y = "NS"; }
		if($state == 'ontario') { $y = "ON"; }
		if($state == 'prince edward island') { $y = "PE"; }
		if($state == 'quebec') { $y = "QC"; }
		if($state == 'saskatchewan') { $y = "SK"; }
		if($state == 'yukon') { $y = "YK"; }
		
		return strtolower($y);
	
	}
else {
	
//return strtoupper($state); 
return $state;
}
}