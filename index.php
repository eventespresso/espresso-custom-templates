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

//Events Custom Table Listing - Shows the events on your page in matching table.
function event_cal_list_table($attributes){
	
	define("ESPRESSO_CALTABLE_PLUGINPATH", WP_PLUGIN_URL. "/".plugin_basename(dirname(__FILE__)) . "/");
	
	//Load the css file
	wp_register_style( 'espresso_cal_table_css', ESPRESSO_CALTABLE_PLUGINPATH."style.css" );
	wp_enqueue_style( 'espresso_cal_table_css');
	
	global $wpdb;
	ob_start();
	
	//Defaults
	$org_options = get_option('events_organization_settings');
	$event_page_id = $org_options['event_page_id'];
	$currency_symbol = $org_options['currency_symbol'];
	$cat_sql = '';
	$use_category = false;
	
	$default_attributes = array(
			'category_identifier'		=> NULL,
			'category_identifier'		=> NULL,
			'max_days'					=> 30,
			'allow_override'			=> 0,
			'show_expired'				=> 'false',
			'show_secondary'			=> 'false',
			'show_deleted'				=> 'false',
			'show_recurrence'			=> 'true',
			'limit'						=> '0',
			'order_by'					=> '',
			'sort'						=> '',
			'featured_image'			=> FALSE,
			'button_image'				=> 'register-now.png'
			//'num_page_links_to_display'	=>10,
			//'use_wrapper'				=> true
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
	
	//Change the event_category_id variable to category_identifier
	if (!empty($event_category_id)){
		$category_identifier = $event_category_id;
		$use_category = true;
	}
	
	//If we are using the category id
	if (!empty($category_identifier)){
		$cat_sql = " AND  c.category_identifier = '" . $category_identifier . "' ";
		$use_category = true;
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
	$sql .= $cat_sql;
	//User sql
	$sql .= (isset($user_id)  && !empty($user_id))? " AND wp_user = '" . $user_id . "' ": '';
	$sql .= !empty($order_by) ? $order_by : " ORDER BY date(e.start_date), ese.start_time ";
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
		$reg_url = get_option('siteurl').'/?page_id='.$event_page_id.'&regevent_action=register&event_id='.$event_id;
		$live_button = '<a id="a_register_link-'.$event_id.'" href="'.$reg_url.'"><img class="buytix_button" src="'.ESPRESSO_CALTABLE_PLUGINPATH.$button_image.'" alt="Buy Tickets"></a>';
		$open_spots = get_number_of_attendees_reg_limit($event_id, 'number_available_spaces');
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
				<td class="td-event-info"><span class="event-title"><a href="<?php echo $reg_url ?>"><?php echo stripslashes_deep($event_name) ?></a></span><p><span><?php echo event_date_display($start_date); ?></span></p>
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
	return $buffer;
}
add_shortcode('EVENT_CAL_TABLE_LIST', 'event_cal_list_table');

