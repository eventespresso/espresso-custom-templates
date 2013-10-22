<?php
//Template: Date Range Table
//Description: This template creates a list of events, displayed in a table - it filters events by a date range
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="date-range"]
//Requirements: CSS skills to customize styles, some renaming of the table columns

//Parameters
// user_select=true/false - default is True. If set to false the user will NOT be able to filter the results by date
// start_date="2013-10-01" - date is in international date format year-month-day so 1st October 1978  = 1978-10-01
// end_date="2013-10-01" - date is in international date format year-month-day so 1st October 1978  = 1978-10-01
// by setting the start date events with a start date before that will not appear at all.
// by setting an end date, events with a start date after that will not appear.


//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default",
add_action('action_hook_espresso_custom_template_date-range','espresso_custom_template_date_range');

function espresso_custom_template_date_range(){

	global $this_event_id, $events, $ee_attributes;

	//Extract shortcode attributes, if any.
	extract($ee_attributes);


	if(isset($ee_attributes['user_select'])) { $user_select = $ee_attributes['user_select']; }
	if(isset($ee_attributes['start_date'])) { $admin_start_date = strtotime($ee_attributes['start_date']); }
	if(isset($ee_attributes['end_date'])) { $admin_end_date = strtotime($ee_attributes['end_date']); }


	//Check for Multi Event Registration
	$multi_reg = false;
	if (function_exists('event_espresso_multi_reg_init')) {
		$multi_reg = true;
	}

	$cart_link 	= '';


wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style( 'jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );

?>

<?php
//var_dump($_POST);
?>

<div id="ee_date_range_wrapper">

<?php
if(isset($user_select) && $user_select != 'false') { ?>
	<form action="" method="POST" id="ee_daterange_datepickers">
	<input class="datepicker" id="ee_date_from" type="text" placeholder="<?php echo _e('Start Date','event_espresso'); ?>" name="ee_date_from">
	<input class="datepicker" id="ee_date_to" type="text" placeholder="<?php echo _e('End Date','event_espresso'); ?>" name="ee_date_to">
	<input id="ee_datesubmit" type="submit" name="datesubmit" value="<?php echo _e('Filter Events','event_espresso'); ?>">
	</form>
<?php } ?>

	<table class="espresso-table" width="100%">

      <thead class="espresso-table-header-row">
      <tr>
          <th class="th-group"><?php _e('Course','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Location','event_espresso'); ?></th>
          <th class="th-group"><?php _e('City','event_espresso'); ?></th>
          <th class="th-group"><?php _e('State','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Date','event_espresso'); ?></th>
          <th class="th-group"><?php _e('Time','event_espresso'); ?></th>
          <th class="th-group"><?php _e('','event_espresso'); ?></th>
     </tr>
      </thead>
	<tbody>

      <?php

      if(isset($_POST['ee_date_from'])) { strtotime($modified_date_from = $_POST['ee_date_from']); }
      if(isset($_POST['ee_date_to'])) { strtotime($modified_date_to = $_POST['ee_date_to']); }

      //$modified_date_from = strtotime($modified_date_from);
      //$modified_date_to = strtotime($modified_date_to);


      foreach ($events as $event){

      	$event_start_date	= strtotime($event->start_date);

      	if(!empty($admin_start_date) && $event_start_date < $admin_start_date) { continue; }
      	if(!empty($admin_end_date) && $event_start_date > $admin_end_date) { continue; }

      	if(isset($modified_date_to) != '' || isset($modified_date_from) != '') {
      	if($event_start_date < $modified_date_from || $event_start_date > $modified_date_to) { continue; }
		}

      	$button_text 		= __('Register', 'event_espresso');
		$alt_button_text	= __('View Details', 'event_espresso');//For alternate registration pages
		$externalURL 		= $event->externalURL;
		$button_text		= !empty($externalURL) ? $alt_button_text : $button_text;
		$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
		//$open_spots			= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
		$open_spots			= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');
		//$live_button 		= $open_spots < 1 || event_espresso_get_status($event->id) == 'NOT_ACTIVE' ? __('Closed', 'event_espresso') : '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'">'.$button_text.'</a>';
		if($open_spots < 1) { $live_button = __('Sold Out.'); } elseif (event_espresso_get_status($event->id) == 'NOT_ACTIVE') { $live_button = __('Closed.'); } else { $live_button = '<a id="a_register_link-'.$event->id.'" href="'.$registration_url.'">'.$button_text.'</a>'; }

		if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
			  $live_button = '<a href="'.espresso_reg_url($event->overflow_event_id).'">'.__('Join Waiting List').'</a>';
		}

		if ($multi_reg && event_espresso_get_status($event->id) == 'ACTIVE' && empty($externalURL)) {
			$params = array(
				//REQUIRED, the id of the event that needs to be added to the cart
				'event_id' => $event->id,
				//REQUIRED, Anchor of the link, can use text or image
				'anchor' => __("Add to Cart", 'event_espresso'), //'anchor' => '<img src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/cart_add.png" />',
				//REQUIRED, if not available at this point, use the next line before this array declaration
				// $event_name = get_event_field('event_name', EVENTS_DETAIL_TABLE, ' WHERE id = ' . $event_id);
				'event_name' => $event->event_name,
				//OPTIONAL, will place this term before the link
				'separator' => __(" or ", 'event_espresso')
			);

			$cart_link = event_espresso_cart_link($params);
		}
		if($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE' || $open_spots < 1 || event_espresso_get_status($event->id) == 'NOT_ACTIVE') {
				$params = array(
				'event_id' => $event->id,
				'anchor' => __("", 'event_espresso'),
				'event_name' => $event->event_name,
				'separator' => __("", 'event_espresso')
			);

			$cart_link = event_espresso_cart_link($params);
		}


	   ?>
      <tr class="espresso-table-row" value="<?php echo $event->start_date; ?>">
       	<td class="td-group">
            <?php echo stripslashes_deep($event->event_name) ?>
          </td>
          <td class="td-group">
            <?php echo $event->venue_address ?>
          </td>
          <td class="td-group">
            <?php echo $event->venue_city ?>
          </td>
      	  <td class="td-group">
            <?php echo $event->venue_state ?>
          </td>
          <td class="td-group tddate">
              <?php echo event_date_display($event->start_date, $format = 'l, M d, Y') ?>
          </td>
          <td class="td-group">
              <?php echo espresso_event_time($event->id, 'start_time', get_option('time_format')) ?>
          </td>

          <td class="td-group">
              <?php echo event_espresso_get_status($event->id) == 'ACTIVE' ? $live_button .  $cart_link : $live_button; ?>
          </td>
      </tr>
      <?php } //close foreach ?>


</tbody>
</table>

</div>

<style>

		#ee_date_from {
		}
		#ee_date_to {
		}
		#ee_datesubmit {
			float:right;
		}

</style>



<script>

jQuery(document).ready(function() {


		if( 0 < jQuery('.datepicker').length ) {
		    jQuery('#ee_date_from').datepicker({
		    	altField: "#date_from_converted",
				altFormat: "yy-m-d",
				changeMonth: true,
     			changeYear: true
		    });
		    jQuery('#ee_date_to').datepicker({
		    	altField: "#date_to_converted",
				altFormat: "yy-m-d",
				changeMonth: true,
				changeYear: true
		    });
		} // end if


jQuery('#ee_daterange_datepickers').append('<input type="hidden" id="date_from_converted">');
jQuery('#ee_daterange_datepickers').append('<input type="hidden" id="date_to_converted">');

jQuery("#ee_date_from").change(function(){
  if (!jQuery(this).val()) jQuery("#date_from_converted").val('');
});
jQuery("#ee_date_to").change(function(){
  if (!jQuery(this).val()) jQuery("#date_to_converted").val('');
});

		jQuery("#ee_datesubmit").click(function(e) {
			e.preventDefault();

			date_from = Date.parse(jQuery('#date_from_converted').val());
			date_to = Date.parse(jQuery('#date_to_converted').val());

			console.log(date_from);

			if(date_from == '' && date_to == '') { return; }

			jQuery('.espresso-table-row').each(function() {

				row_date = Date.parse(jQuery(this).attr('value'));

				if(jQuery(date_from).length > 0 && jQuery(date_to).length == 0) {
					if(row_date < date_from ) {
					jQuery(this).hide();
					}
					else { jQuery(this).show(); }
				}
				if(jQuery(date_to).length > 0 && jQuery(date_from).length == 0) {
					if(row_date > date_to ) {
					jQuery(this).hide();
					}
					else { jQuery(this).show(); }
				}

				if(jQuery(date_to).length > 0 && jQuery(date_from).length > 0) {
					if(row_date < date_from || row_date > date_to ) {
					jQuery(this).hide();
					}
					else { jQuery(this).show(); }
				}

			});



		});

jQuery('tr.header').each(function(){
      if (jQuery(this).nextUntil("tbody").filter(function(){
           return jQuery(this).is(':visible');
      }).length == 0){
           jQuery('.espresso-table').apend('sfsdfsdfsdfsdf');
      } else {
           jQuery(this).show()
      }
});


});
</script>

<?php
}