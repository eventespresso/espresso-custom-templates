<?php
/*
  Plugin Name: Event Espresso - Custom Template Display
  Plugin URI: http://www.eventespresso.com
  Description: This addon for Event Espresso loads a template file from this plugin's directory OR the WP theme directory. Templates that are loaded from the plugin directory, should use the template name as the name of the template directory, and include an index.php file that contains the template display code. Example: If your template name is "my_custom_template". By using a shortcode such as [EVENT_CUSTOM_VIEW template_name="my_custom_template"], this plugin will load the template file located at templates/my_custom_template/index.php.
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
function espresso_custom_template_output($attributes){
	do_action('action_hook_espresso_custom_template_output', $attributes);
}
add_shortcode('EVENT_CUSTOM_VIEW', 'espresso_custom_template_output');

add_action('action_hook_espresso_custom_template_output', 'espresso_custom_template_display', 10, 1 );
//HTML to show the events on your page in matching table. To customize this layout, please copy and paste the following code into your theme/functions.php file.
function espresso_custom_template_display($attributes){
	
	define("ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH", WP_PLUGIN_URL. "/".plugin_basename(dirname(__FILE__)) . "/");
	
	global $wpdb, $org_options, $events;
	
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
			'max_days'					=> 0,					//Maximum amount of days to display events
			'show_expired'				=> 'false',				//Show expired events or not
			'show_secondary'			=> 'false',				//Show wait list events or not
			'show_deleted'				=> 'false',				//Show deleted events or not
			'show_recurrence'			=> 'true',				//Show recurring events or not
			'recurrence_only'			=> 'false',				//Show recurring events or not
			'limit'						=> '0',					//Limit the number of events retrieved from the database
			'order_by'					=> '',					//Order by fields in the database, such as start_date
			'sort'						=> '',					//Sort direction. Example ASC or DESC. Default is ASC
			'user_id'					=> '',					//List events by user id
			'template_name'				=> 'default',
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
	
	//Figure out what category id to use
	if (!empty($event_category_id)){
		$category_identifier = $event_category_id;
		$use_category = true;
	}
	if (!empty($category_identifier)){
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
	$sql  = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
	
	//Venue Fields
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
	
	if ($use_category == true){ 
		$sql	.= ", c.category_name, c.category_desc, c.display_desc ";
	}
	
	$sql	.= "FROM ". EVENTS_DETAIL_TABLE . " e ";
	$sql	.= "LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id = e.id ";
	
	//Prices SQL
	$sql	.= "LEFT JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
	
	//Category SQL
	if ($use_category == true){
		$sql	.= "JOIN " . EVENTS_CATEGORY_REL_TABLE . " r ON r.event_id = e.id ";
		$sql	.= "JOIN " . EVENTS_CATEGORY_TABLE . " c ON  c.id = r.cat_id ";
	}
	
	//Venue SQL
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
	
	$sql	.= "WHERE e.is_active = 'Y' ";
	$sql	.= $show_expired == 'false' ? " AND (e.start_date >= '" . date('Y-m-d') . "' OR e.event_status = 'O' OR e.registration_end >= '" . date('Y-m-d') . "') " : '';
	$sql	.= $show_deleted == 'false' ? " AND e.event_status != 'D' " : "";
	$sql	.= $show_secondary == 'false' ? " AND e.event_status != 'S' " : '';
	$sql	.= $show_recurrence == 'false' ? " AND e.recurrence_id = '0' " : '';
	$sql	.= $recurrence_only == 'true' ? " AND e.recurrence_id > '0' " : '';
	
	$sql	.= $category_sql;
	
	//Max days to display
	$sql 	.= $max_days > 0 ? "AND ADDDATE('".date ( 'Y-m-d' )."', INTERVAL ".$max_days." DAY) >= e.start_date AND e.start_date >= '".date ( 'Y-m-d' )."'" : '';
	
	//User SQL
	$sql	.= (isset($user_id) && !empty($user_id)) ? " AND wp_user = '" . $user_id . "' ": '';
	
	//Group events by ID
	$sql 	.= " GROUP BY e.id ";
	
	//Order events
	$sql	.= !empty($order_by) ? " ORDER BY ".$order_by : " ORDER BY date(e.start_date), ese.start_time";
	
	//Sort order of events
	$sql	.= !empty($sort) ? " ".$sort : " ASC";
	
	//Limit amount of events returned
	$sql	.= $limit > 0 ? " LIMIT ".$limit : "";
	
	//Get the results of the query	
	$events = $wpdb->get_results($sql);
	
	//Locate the template file
	$path = locate_template( $template_name.'.php' );
	if ( empty( $path ) ) {
		$path = 'templates/'.$template_name.'/index.php';
	}
	
	//Output the content
	ob_start();
	include( $path );
	
	//Create an action using the template name
	do_action('action_hook_espresso_custom_template_'.$template_name);
	
	$buffer = ob_get_contents();
	ob_end_clean();
	echo $buffer;
	unset($events); //Unset the $events global variable
}