<?php 
//Template: Vector Map

//Description: This template displays a jQuery vector (SVG) map of a specific country, and highlights those regions when there is an event in the list that has a venue there. Clicking the state/region will display the events details.

//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="vector-maps" max_days="30" category_identifier="concerts"].

//Requirements: CSS skills to customize styles. Events must have venues, those venues must have the State filled out in long form or international shortform (e.g New York or NY, Ontario or ON)

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 

//This template only has a couple of maps and is geared to use North America. You can find more maps and details on how to create them here https://github.com/manifestinteractive/jqvmap 

// CANADIAN? Rename the default index.php file and then rename the index_canada.php file to index.php, then you will get a map of the  Great White North.

// BRITISH? Find me an SVG map of the UK's counties! Everything I can find is geared to just England...

add_action('action_hook_espresso_custom_template_vector-maps','espresso_custom_template_vector_maps');

function espresso_custom_template_vector_maps(){
	
	//Load the css and script files
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');

	wp_register_script( 'jquery_usa', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.usa.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_usa' );
	
	wp_register_script( 'jquery_vmap', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.vmap.min.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap' );
	
	wp_register_script( 'jquery_vmap_location', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/maps/jquery.vmap.usa.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap_location' );
	
	wp_register_style( 'jquery_vmap_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/js/jqvmap.css" );
	wp_enqueue_style( 'jquery_vmap_css');


	
	global $org_options, $this_event_id, $events, $wpdb;
	
	//Show the featured image for each event, instead of the date, to the left of the event title.
	$featured_image = FALSE;
	
	//Clears the month name
	$temp_month = '';
	
	$sql = "SELECT * FROM " . EVENTS_VENUE_REL_TABLE;
	$temp_venues = $wpdb->get_results($sql);

	foreach ($events as $venues) {
		if($venues->venue_state) { $ven_event_count[$venues->venue_state]++; }
	}

	echo "<div id='hidden_states'>";
	if (!empty($ven_event_count)){
		foreach ($ven_event_count as $key => $value) {
			$state = espresso_state_convert($key);
			echo "<input name='" . $state . "' type='hidden' value='" . $value . "' />";
		}
	}
	echo "</div>";

?>

<div id="eemap"></div>
<h2 id="events_in"><?php _e('Events in', 'event_espresso'); ?> </h2>
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
		<td class="td-date-holder"><div class="dater">
				<p class="cal-day-title"><?php echo event_date_display($event->start_date, "l"); ?></p>
				<p class="cal-day-num"><?php echo event_date_display($event->start_date, "j"); ?></p>
				<p><span><?php echo event_date_display($event->start_date, "M"); ?></span></p>
			</div></td>
		<?php }else{?>
		<td class="td-fet-image"><div class="featured-image">
				<?php 
					//Featured image
					echo apply_filters('filter_hook_espresso_display_featured_image', $event->id, !empty($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : '');?>
			</div></td>
		<?php }?>
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
	<tr class="usa_map_row noevents">
		<td> <?php _e('No Events Available.', 'event_espresso'); ?> </td>
	</tr>
</table>
<?php



 } //end of main function!!
 
 
 function espresso_state_convert($x) {
	
	$state = strtolower($x);

	if(strlen($state) > 2) {
		switch ($state){
			case 'alabama'		:	$y = "AL";
				break;
			case 'alaska'		: 	$y = "AK";
				break;
			case 'arizona'		:	$y = "AZ";
				break;
			case 'arkansas'		:	$y = "AR";
				break;
			case 'california' 	:	$y = "CA";
				break;
			case 'colorado'		:	$y = "CO";
				break;
			case 'connecticut'	:	$y = "CT";
				break;
			case 'delaware'		:	$y = "DE";
				break;
			case 'florida'		:	$y = "FL";
				break;
			case 'georgia'		:	$y = "GA";
				break;
			case 'hawaii'		:	$y = "HI";
				break;
			case 'idaho'		:	$y = "ID";
				break;
			case 'illinois'		:	$y = "IL";
				break;
			case 'indiana'		:	$y = "IN";
				break;
			case 'iowa'			:	$y = "IA";
				break;
			case 'kansas'		:	$y = "KS";
				break;
			case 'kentucky'		:	$y = "KY";
				break;
			case 'louisiana'	:	$y = "LA";
				break;
			case 'maine'		:	$y = "ME";
				break;
			case 'maryland'		:	$y = "MD";
				break;
			case 'massachusetts':	$y = "MA";
				break;
			case 'michigan'		:	$y = "MI";
				break;
			case 'minnesota'	:	$y = "MN";
				break;
			case 'mississippi'	:	$y = "MS";
				break;
			case 'missouri'		:	$y = "MO";
				break;
			case 'montana'		:	$y = "MT";
				break;
			case 'nebraska'		:	$y = "NE";
				break;
			case 'nevada'		:	$y = "NV";
				break;
			case 'new hampshire':	$y = "NH";
				break;
			case 'new jersey'	:	$y = "NJ";
				break;
			case 'new mexico'	:	$y = "NM";
				break;
			case 'new york'		:	$y = "NY";
				break;
			case 'north carolina':	$y = "NC";
				break;
			case 'north dakota'	:	$y = "ND";
				break;
			case 'ohio'			:	$y = "OH";
				break;
			case 'oklahoma'		:	$y = "OK";
				break;
			case 'oregon'		:	$y = "OR";
				break;
			case 'pennsylvania'	:	$y = "PA";
				break;
			case 'rhode island'	:	$y = "RI";
				break;
			case 'south carolina':	$y = "SC";
				break;
			case 'south dakota'	:	$y = "SD";
				break;
			case 'tennessee'	:	$y = "TN";
				break;
			case 'texas'		:	$y = "TX";
				break;
			case 'utah'			:	$y = "UT";
				break;
			case 'vermont'		:	$y = "VT";
				break;
			case 'virginia'		:	$y = "VA";
				break;
			case 'washington'	:	$y = "WA";
				break;
			case 'west virginia':	$y = "WV";
				break;
			case 'wisconsin'	:	$y = "WI";
				break;
			case 'wyoming'		:	$y = "WY";
				break;
		}
		
		return strtolower($y);
	
	}else{	
		return $state;
	}
}
