<?php
/*
  Plugin Name: Event Espresso - Calendar Table Display for Events
  Plugin URI: http://www.eventespresso.com
  Description: This plugin creates a list of events by category, that are displayed in a table, for a maximum number of days. Shortcode example: [EVENT_CAL_TABLE_LIST max_days="30" category_identifier="concerts"]. Requirements: CSS skills to customize styles, some renaming of the table columns, Espresso WP User Add-on (optional)
  Version: 1.0
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2013 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA

*/

//Create the shortcode
function event_cal_list_table($attributes){
	do_action('action_hook_espresso_cal_list_table_output', $attributes);
}
add_shortcode('EVENT_CAL_TABLE_LIST', 'event_cal_list_table');

add_action('action_hook_espresso_cal_list_table_output', 'espresso_cal_list_table_output', 10, 1 );
//HTML to show the events on your page in matching table. To customize this layout, please copy and paste the following code into your theme/functions.php file.
function espresso_cal_list_table_output($attributes){
	
	define("ESPRESSO_CALTABLE_PLUGINPATH", WP_PLUGIN_URL. "/".plugin_basename(dirname(__FILE__)) . "/");
	
	//Load the css file
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CALTABLE_PLUGINPATH."style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');
	
	global $wpdb;
	ob_start();
	
	//Default variables
	$org_options = get_option('events_organization_settings');
	$event_page_id = $org_options['event_page_id'];
	$currency_symbol = $org_options['currency_symbol'];
	$cat_sql = '';
	$use_category = false;
	
	//Create the default attributes
	$default_attributes = array(
			'category_identifier'		=> NULL,				//The category identifier 
			'event_category_id'			=> NULL,				//Alternate category identifier 
			'max_days'					=> 30,					//Maximum amount of days to display events
			'show_expired'				=> 'false',				//Show expired events or not
			'show_secondary'			=> 'false',				//Show wait list events or not
			'show_deleted'				=> 'false',				//Show deleted events or not
			'show_recurrence'			=> 'true',				//Show recurring events or not
			'limit'						=> '0',					//Limit the number of events retrieved from the database
			'order_by'					=> '',					//Order by fields in the database, such as start_date
			'sort'						=> '',					//Sort direction. Example ASC or DESC. Default is ASC
			'featured_image'			=> FALSE,				//Show the featured image or not
			'button_image'				=> 'register-now.png',	//Button image needs to be in the same directory as these files
			'user_id'					=> '',					//List events by user id
			
	);
	
	// loop thru default atts
	foreach ($default_attributes as $key => $default_attribute) {
		// check if att exists
		if (!isset($attributes[$key])) {
				$attributes[$key] = $default_attribute;
		}
	}
		
	// now extract shortcode attributes
	extract($attributes);
	
	if (!empty($event_category_id)){
		$category_identifier = $event_category_id;
		$use_category = true;
	}
	
	//Categories
		//Let's check if there's one or more categories specified for the events of the event list (based on the use of "," as a separator) and store them in the $cat array.
		if(strstr($category_identifier,',')){
			$array_cat=explode(",",$category_identifier);
			$cat=array_map('trim', $array_cat);
			$category_detail_id = '';
			
			//For every category specified in the shortcode, let's get the corresponding category_id et create a well-formatted string (id,n id)
			foreach($cat as $k=>$v){
				$sql_get_category_detail_id = "SELECT id FROM ". EVENTS_CATEGORY_TABLE . " WHERE category_identifier = '".$v."'";
				$category_detail_id .= $wpdb->get_var( $sql_get_category_detail_id ).",";
			}
	
			$cleaned_string_cat = substr($category_detail_id, 0, -1);
			$tmp=explode(",",$cleaned_string_cat);
			sort($tmp);
			$cleaned_string_cat=implode(",", $tmp);
			trim($cleaned_string_cat);
			$category_id=$cleaned_string_cat;
			
			//We filter the events based on the events_detail_table.category_id instead of the category_identifier
			$category_sql = ($category_id !== NULL  && !empty($category_id))? " AND e.category_id IN (" . $category_id . ") ": '';
		
		} else {
			$category_sql = ($category_identifier !== NULL  && !empty($category_identifier))? " AND c.category_identifier = '" . $category_identifier . "' ": '';
		}
			
	//Build the query
	$sql  = "SELECT e.*, ";
	if ($use_category == true){ 
		$sql  .= "c.category_name, c.category_desc, c.display_desc, ";
	}
	$sql  .= "ese.start_time FROM ". EVENTS_DETAIL_TABLE . " e ";
	$sql  .= "JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id = e.id ";
	if ($use_category == true){
		$sql  .= "JOIN " . EVENTS_CATEGORY_REL_TABLE . " r ON r.event_id = e.id ";
		$sql  .= "JOIN " . EVENTS_CATEGORY_TABLE . " c ON  c.id = r.cat_id ";
	}
	$sql  .= "WHERE e.is_active = 'Y' ";
	$sql .= $show_expired == 'false' ? " AND (e.start_date >= '" . date('Y-m-d') . "' OR e.event_status = 'O' OR e.registration_end >= '" . date('Y-m-d') . "') " : '';
	$sql .= $show_deleted == 'false' ? " AND e.event_status != 'D' " : " AND e.event_status = 'D' ";
	$sql .= $category_sql;
	//User sql
	$sql .= (isset($user_id) && !empty($user_id)) ? " AND wp_user = '" . $user_id . "' ": '';
	$sql .= !empty($order_by) ? $order_by . " ".$sort." " : " ORDER BY date(e.start_date), ese.start_time ASC ";
	$sql .= !empty($limit) ? " LIMIT ".$limit : "";
	
	//Get the results of the query	
	$events = $wpdb->get_results($sql);
	
	?>
	<table class="cal-table-list">
		<?php 
		$temp_month = '';
		foreach ($events as $event){
			$event_id 			= $event->id;
			$event_name 		= $event->event_name;
			$event_desc			= $event->event_desc;
			$event_identifier	= $event->event_identifier;
			$active				= $event->is_active;
			$start_date			= $event->start_date;
			$start_time			= $event->start_time;
			$reg_limit			= $event->reg_limit;
			$event_address		= !empty($event->address) ? $event->address : '';
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$event_desc			= strip_tags(html_entity_decode($event_desc));
			$externalURL 		= $event->externalURL;
			$registration_url 	= $externalURL != '' ? $externalURL : espresso_reg_url($event_id);
			$live_button 		= '<a id="a_register_link-'.$event_id.'" href="'.$registration_url.'"><img class="buytix_button" src="'.ESPRESSO_CALTABLE_PLUGINPATH.$button_image.'" alt="Buy Tickets"></a>';
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
	<?php
	
	$buffer = ob_get_contents();
	ob_end_clean();
	echo $buffer;
	
}