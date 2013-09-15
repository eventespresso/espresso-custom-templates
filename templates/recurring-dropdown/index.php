<?php
//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "default", 
add_action('action_hook_espresso_custom_template_recurring-dropdown','espresso_recurring_dropdown', 10, 1);
if (!function_exists('espresso_recurring_dropdown')) {
	function espresso_recurring_dropdown(){	
		global $events, $org_options, $events_in_session;
		
		$button_text = 'Select a Date';
		//Check if Multi Event Registration is installed
		$multi_reg = false;
		if (function_exists('event_espresso_multi_reg_init')) {
			$multi_reg = true;
		}
		
		/* group recurring events */
		wp_register_script( 'jquery_dropdown', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/recurring-dropdown/js/jquery.dropdown.js', array('jquery'), '0.1', TRUE );
		wp_enqueue_script( 'jquery_dropdown' );
		wp_register_style( 'espresso_recurring_dropdown_stylesheet', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/recurring-dropdown/css/jquery.dropdown.css');
		wp_enqueue_style( 'espresso_recurring_dropdown_stylesheet' );
		
		/* group recurring events */
		$events_type_index = -1;
		$events_of_same_type = array();
		$last_recurrence_id = NULL;
		
	?>

<table>
	<tr>
		<th><?php _e('Event Name', 'event_espresso') ?></th>
		<th><?php _e('Venue', 'event_espresso') ?></th>
		<th><?php _e('Time', 'event_espresso') ?></th>
		<th><?php _e('Cost', 'event_espresso') ?></th>
		<th><?php _e('Register', 'event_espresso') ?></th>
	</tr>
	<?php
		foreach ($events as $event){
			$this_event_id			= $event->id;
			$member_only			= !empty($event->member_only) ? $event->member_only : '';
			$event_meta				= unserialize($event->event_meta);
			$externalURL 			= $event->externalURL;
			$registration_url 		= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$open_spots 			= apply_filters('filter_hook_espresso_get_num_available_spaces', $event->id);
			
			$recurrence_id			= $event->recurrence_id;
			
			$overflow_event_id		= $event->overflow_event_id;
			$externalURL 			= $event->externalURL;
			$registration_url 		= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			
			/* group recurring events */
			$is_new_event_type = $last_recurrence_id == 0 || $last_recurrence_id != $recurrence_id;
			if ($is_new_event_type){
				$events_type_index++;
				$events_of_same_type[$events_type_index] = array();
			}
			
			$event_data = array(
				'event_id'			=> $event->id,
				'event_name'		=> stripslashes_deep($event->event_name),
				'venue_title'		=> $event->venue_name,
				'start_time'		=> $event->start_time,
				'price'				=> $event->event_cost,
				'event_desc'		=> $event->event_desc,
				'start_date'		=> $event->start_date,
				'end_date'			=> $event->end_date,
				'reg_limit'			=> $event->reg_limit,
				'registration_url'	=> $registration_url,
				'recurrence_id'		=> $recurrence_id,
				'overflow_event_id'	=> $event->overflow_event_id
			);
			
			array_push($events_of_same_type[$events_type_index], $event_data);

			$last_recurrence_id = $recurrence_id;
			
		}
		foreach ($events_of_same_type as $events_group) {
			$first_event_instance	= $events_group[0];
			
			?>
			<tr id="event_data-<?php echo $first_event_instance['event_id']?>" class="event_data subpage_excerpt r <?php echo $css_class; ?> <?php echo $category_identifier; ?> event-data-display event-list-display">
				<td id="event_title-<?php echo $first_event_instance['event_id']?>" class="event_title"><?php echo stripslashes_deep($first_event_instance['event_name'])?></td>
				<td id="venue_title-<?php echo $first_event_instance['venue_title']?>" class="venue_title"><?php echo stripslashes_deep($first_event_instance['venue_title'])?></td>
				<td id="start_time-<?php echo $first_event_instance['start_time']?>" class="start_time"><?php echo stripslashes_deep($first_event_instance['start_time'])?></td>
				<td id="price-<?php echo $first_event_instance['price']?>" class="price"><?php echo $currency_symbol.stripslashes_deep($first_event_instance['price'])?></td>
				<?php 
				//Group the recurring events
				
				if (count($events_group) > 1){
			?>
					<td><input type="button" value="<?php echo $button_text; ?>" data-dropdown="#date_picker_<?php echo $first_event_instance['event_id']?>">
					<div class="dropdown-menu has-tip has-scroll" id="date_picker_<?php echo $first_event_instance['event_id']?>">
						<ul>
						<?php 
							foreach ($events_group as $e){
										
								$num_attendees = apply_filters('filter_hook_espresso_get_num_attendees', $e['event_id']);
								echo '<li>';
								
								if ($num_attendees >= $e['reg_limit']){
									echo '<span class="error">';
								}else{
									echo '<a href="'.$e['registration_url'].'">';
								}
								
								if ($e['start_date'] != $e['end_date']){
									echo event_date_display($e['start_date'], get_option('date_format')).'–'.event_date_display($e['end_date'], get_option('date_format')); 
								}else{
									echo event_date_display($e['start_date'], get_option('date_format'));
								}
								
								if ($num_attendees >= $e['reg_limit']){
									echo ' '.__('Sold Out', 'event_espresso').'</span> <a href="'.get_option('siteurl').'/?page_id='.$e['event_page_id'].'&e_reg=register&event_id='.$e['overflow_event_id'].'&name_of_event='.stripslashes_deep($e['event_name']).'">'.__('(Join Waiting List)').'</a>';
								}else{
									echo '</a>';
								}
								
								echo '</li>';
							}
						?>
						</ul>
					</div></td>
		<?php 
				}else{
					$num_attendees = apply_filters('filter_hook_espresso_get_num_attendees', $first_event_instance['event_id']);
					if ($num_attendees >= $events_group[0]['reg_limit']){ ?>
						<td><p><span class="error"><?php _e('Sold Out', 'event_espresso'); ?></span> <a href="<?php echo espresso_reg_url($first_event_instance['overflow_event_id']);?>">
						<?php _e('Join Waiting List', 'event_espresso'); ?>
						</a></p>
						</td>
		<?php 		
					}else{ ?>
						<td class="event-links"><a href="<?php echo $first_event_instance['registration_url']; ?>" title="<?php echo stripslashes_deep($first_event_instance['event_name'])?>">
						<?php _e('Register', 'event_espresso'); ?>
						</a>
						</td>
		<?php 
					}
						
				}
	?>
			</tr>
	<?php
		}
	?>
</table>
<?php
		echo '<script>jQuery(".dropdown-menu").appendTo("body");</script>';
	}
}