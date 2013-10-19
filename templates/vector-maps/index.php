<?php
//Template: Vector Map
//Description: This template displays a jQuery vector (SVG) map of a specific country, and highlights those regions when there is an event in the list that has a venue there. Clicking the state/region will display the events details.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="vector-maps" max_days="30" category_identifier="concerts"].
//Requirements: CSS skills to customize styles. Events must have venues, those venues must have the State filled out in long form or international shortform (e.g New York or NY, Ontario or ON)

//This template only has a couple of maps and is geared to use North America. You can find more maps and details on how to create them here https://github.com/manifestinteractive/jqvmap

// CANADIAN? Use this shortcode parameter: country="canada"
// Please note the shorthand for Northwest Territories is NWT

// BRITISH? Use this shortcode parameter: country="uk"
//There is a basic Regional map based on http://en.wikipedia.org/wiki/Regions_of_England. This requires specific teminology used in the State field of the venue. E.g East of England (East England wont work!) or London as a state. Fiddly but we don't have specifc codes for regions.
//Find us an SVG map of the UK's counties! Everything we can find is geared to just England...

add_action('action_hook_espresso_custom_template_vector-maps','espresso_custom_template_vector_maps');

function espresso_custom_template_vector_maps(){
	global $org_options, $this_event_id, $events, $wpdb, $ee_attributes;

	//Extract shortcode attributes, if any.
	extract($ee_attributes);

	//Custom shortcode parameter: country
	//Defaults to usa. IF you need to load a different country, use the following shortcode.
	//Example shortcode usage: [EVENT_CUSTOM_VIEW template_name="vector-maps" country="canada"]
	$country = isset($country) && !empty($country) ? $country : 'usa';

	//Load the css and script files
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');

	wp_register_script( 'jquery_'.$country, ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.'.$country.'.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_'.$country );

	wp_register_script( 'jquery_vmap', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/jquery.vmap.min.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap' );

	wp_register_script( 'jquery_vmap_location', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/vector-maps/js/maps/jquery.vmap.'.$country.'.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_vmap_location' );

	wp_register_style( 'jquery_vmap_css', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/vector-maps/js/jqvmap.css" );
	wp_enqueue_style( 'jquery_vmap_css');

	//Clears the month name
	$temp_month = '';

	$sql = "SELECT * FROM " . EVENTS_VENUE_REL_TABLE;
	$temp_venues = $wpdb->get_results($sql);

	foreach ($events as $venues) {
		if($venues->venue_state) {
			if(!isset($ven_event_count[$venues->venue_state]))
			{
				$ven_event_count[$venues->venue_state] = 1;
			} else {
				$ven_event_count[$venues->venue_state]++;
			}
		}
	}

	echo "<div id='hidden_states'>";
	if (!empty($ven_event_count)){
		foreach ($ven_event_count as $key => $value) {
			$state = espresso_state_convert($key, $country);
			echo "<input name='" . $state . "' type='hidden' value='" . $value . "' />";
		}
	}
	echo "</div>";

?>

<div id="eemap"></div>
<h2 id="events_in">
	<?php _e('Events in ', 'event_espresso'); ?>
</h2>
<table class="usa-table-list">
	<tr class="cal-header hide">
		<th><?php _e('Date','event_espresso'); ?></th>
		<th class="th-event-info"><?php _e('Event','event_espresso'); ?></th>
		<th><?php _e('Tickets','event_espresso'); ?></th>
	</tr>
	<?php

		foreach ($events as $event){
			$this_event_id		= $event->id;
			$this_event_desc 	= explode('<!--more-->', $event->event_desc);
			$this_event_desc 	= array_shift($this_event_desc);
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$live_button 		= '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/vector-maps/register-now.png" alt="Buy Tickets"></a>';
			$open_spots 		= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);

			//This line changes the button text to display "Closed" if the attendee limit is reached.
			if ( $open_spots < 1 || event_espresso_get_status($event->id) == 'NOT_ACTIVE' ) { $live_button = '<img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/vector-maps/closed.png" alt="Buy Tickets">';  }

			//waitlist
			if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
				$live_button = '<a href="'.espresso_reg_url($event->overflow_event_id).'"><img class="buytix_button" src="'.ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'/templates/vector-maps/waiting.png" alt="Join Waiting List"></a>';
			}

			//Build the table headers

			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');

			//If enough spaces exist then show the form
			//Check to see if the Members plugin is installed.
			if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ) {
				event_espresso_user_login();
			}else{
				?>
	<tr class="usa_map_row <?php echo espresso_state_convert($event->venue_state, $country); ?>">
		<td class="td-date-holder"><div class="dater">
				<p class="cal-day-title"><?php echo event_date_display($event->start_date, "l"); ?></p>
				<p class="cal-day-num"><?php echo event_date_display($event->start_date, "j"); ?></p>
				<p><span><?php echo event_date_display($event->start_date, "M"); ?></span></p>
			</div></td>
		<td class="td-event-info"><span class="event-title"><a href="<?php echo $registration_url ?>"><?php echo stripslashes_deep($event->event_name); ?></a></span>
			<p>
				<?php _e('When:', 'event_espresso'); ?>
				<?php echo event_date_display($event->start_date); ?><br />
				<?php _e('Where:', 'event_espresso'); ?>
				<?php echo stripslashes_deep($event->venue_address.', '.$event->venue_city.', '.$event->venue_state); ?><br />
				<?php _e('Price: ', 'event_espresso'); ?>
				<?php echo  $org_options['currency_symbol'].$event->event_cost; ?> </p>
			<?php echo espresso_format_content(array_shift(explode('<!--more-->', $event->event_desc))); //Includes <p> tags ?>
		</td>
		<td class="td-event-register"><?php echo $live_button ?></td>
	</tr>
	<?php
			}// close is_user_logged_in
		 } //close foreach ?>
	<tr class="usa_map_row noevents">
		<td><?php _e('No Events Available.', 'event_espresso'); ?></td>
	</tr>
</table>
<?php



 } //end of main function!!

 function espresso_state_convert($state, $country) {

	$state = strtolower($state);

	if(strlen($state) >= 2) {
		if ($country == 'usa'){
			switch ($state){
				case 'alabama'		:	$state = "AL";
					break;
				case 'alaska'		: 	$state = "AK";
					break;
				case 'arizona'		:	$state = "AZ";
					break;
				case 'arkansas'		:	$state = "AR";
					break;
				case 'california' 	:	$state = "CA";
					break;
				case 'colorado'		:	$state = "CO";
					break;
				case 'connecticut'	:	$state = "CT";
					break;
				case 'delaware'		:	$state = "DE";
					break;
				case 'florida'		:	$state = "FL";
					break;
				case 'georgia'		:	$state = "GA";
					break;
				case 'hawaii'		:	$state = "HI";
					break;
				case 'idaho'		:	$state = "ID";
					break;
				case 'illinois'		:	$state = "IL";
					break;
				case 'indiana'		:	$state = "IN";
					break;
				case 'iowa'			:	$state = "IA";
					break;
				case 'kansas'		:	$state = "KS";
					break;
				case 'kentucky'		:	$state = "KY";
					break;
				case 'louisiana'	:	$state = "LA";
					break;
				case 'maine'		:	$state = "ME";
					break;
				case 'maryland'		:	$state = "MD";
					break;
				case 'massachusetts':	$state = "MA";
					break;
				case 'michigan'		:	$state = "MI";
					break;
				case 'minnesota'	:	$state = "MN";
					break;
				case 'mississippi'	:	$state = "MS";
					break;
				case 'missouri'		:	$state = "MO";
					break;
				case 'montana'		:	$state = "MT";
					break;
				case 'nebraska'		:	$state = "NE";
					break;
				case 'nevada'		:	$state = "NV";
					break;
				case 'new hampshire':	$state = "NH";
					break;
				case 'new jersey'	:	$state = "NJ";
					break;
				case 'new mexico'	:	$state = "NM";
					break;
				case 'new york'		:	$state = "NY";
					break;
				case 'north carolina':	$state = "NC";
					break;
				case 'north dakota'	:	$state = "ND";
					break;
				case 'ohio'			:	$state = "OH";
					break;
				case 'oklahoma'		:	$state = "OK";
					break;
				case 'oregon'		:	$state = "OR";
					break;
				case 'pennsylvania'	:	$state = "PA";
					break;
				case 'rhode island'	:	$state = "RI";
					break;
				case 'south carolina':	$state = "SC";
					break;
				case 'south dakota'	:	$state = "SD";
					break;
				case 'tennessee'	:	$state = "TN";
					break;
				case 'texas'		:	$state = "TX";
					break;
				case 'utah'			:	$state = "UT";
					break;
				case 'vermont'		:	$state = "VT";
					break;
				case 'virginia'		:	$state = "VA";
					break;
				case 'washington'	:	$state = "WA";
					break;
				case 'west virginia':	$state = "WV";
					break;
				case 'wisconsin'	:	$state = "WI";
					break;
				case 'wyoming'		:	$state = "WY";
					break;
			}
		}
		if ($country == 'canada'){
			switch ($state){
				case 'alberta' 					:	$state = "AB";
					break;
				case 'british columbia'			:	$state = "BC";
					break;
				case 'manitoba'					:	$state = "MB";
					break;
				case 'new brunswick'			:	$state = "NB";
					break;
				case 'newfoundland and labrador':	$state = "NL";
					break;
				case 'northwest territories'	:	$state = "NWT";
					break;
				case 'nova scotia'				:	$state = "NS";
					break;
				case 'nunavut'					:	$state = "NU";
					break;
				case 'ontario'					:	$state = "ON";
					break;
				case 'prince edward island'		:	$state = "PE";
					break;
				case 'quebec'					:	$state = "QC";
					break;
				case 'saskatchewan'				:	$state = "SK";
					break;
				case 'yukon'					:	$state = "YK";
					break;
			}
		}
				if ($country == 'uk'){
			switch ($state){
				case 'london' 					:	$state = "LN";
					break;
				case 'north west' 				:	$state = "NW";
					break;
				case 'scotland' 				:	$state = "SC";
					break;
				case 'northern ireland' 		:	$state = "NI";
					break;
				case 'wales' 					:	$state = "WA";
					break;
				case 'west midlands'			:	$state = "WM";
					break;
				case 'yorkshire and humber' 	:	$state = "YO";
					break;
				case 'east midlands' 			:	$state = "EM";
					break;
				case 'east of england' 			:	$state = "EE";
					break;
				case 'south east' 				:	$state = "SE";
					break;
				case 'south west' 				:	$state = "SW";
					break;
				case 'north east' 				:	$state = "NE";
					break;
			}
		}
				if ($country == 'australia'){
			switch ($state){
				case 'australian capital territory'	:	$state = "ACT";
					break;
				case 'new south wales' 				:	$state = "NSW";
					break;
				case 'northern territory' 			:	$state = "NT";
					break;
				case 'queensland' 					:	$state = "QLD";
					break;
				case 'south australia' 				:	$state = "SA";
					break;
				case 'tasmania'						:	$state = "TAS";
					break;
				case 'victoria' 					:	$state = "VIC";
					break;
				case 'western australia' 			:	$state = "WA";
					break;
			}
		}

		return strtolower($state);
	}
}