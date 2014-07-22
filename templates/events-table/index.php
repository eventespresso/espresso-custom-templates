<?php
//Template: Event Listings Table
//Description: This template creates a list of events, displayed in a table. It can dipsplay events by category and/or maximum number of days.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="events-table"]
//Requirements: CSS skills to customize styles, some renaming of the table columns

//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default",
add_action('action_hook_espresso_custom_template_events-table','espresso_custom_template_events_table');

function espresso_custom_template_events_table() {

	global $this_event_id, $events, $wpdb, $ee_attributes;

	extract($ee_attributes);

	//Get the categories
	$sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
	$temp_cats = $wpdb->get_results($sql);

	//Check for custom templates
	if(function_exists('espresso_custom_template_locate')) {
		$custom_template_path = espresso_custom_template_locate("events-table");
	} else {
		$custom_template_path = '';
	}

	if( !empty($custom_template_path) ) {
		//If custom template found include here
		include( $custom_template_path );
	} else {
		//Otherwise use the default template
		include( 'template.php' );
	}
}
