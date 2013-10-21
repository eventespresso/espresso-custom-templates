<?php
/**
 *
 * @package    	Custom Template Display
 * @subpackage 	Masonry Grid
 * @since      
 * @author     	Seth Shoultes <seth@eventespresso.com>
 * @copyright  	Copyright (c) 2013, Seth Shoultes
 * @link       	http://eventespresso.com
 * @license    	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Template: Masonry Grid
 *
 * Description: Masonry is a JavaScript grid layout library. It works by 
 * placing elements in optimal position based on available vertical space, 
 * sort of like a mason fitting stones in a wall. You’ve probably seen it 
 * in use all over the Internet.
 *
 * Shortcode Example: [EVENT_CUSTOM_VIEW template_name="masonry-grid" max_days="30" category_identifier="concerts"].
 *
 * IMPORTANT you may need to tweak the box or title sizes if your events have long titles.
 */

add_action('action_hook_espresso_custom_template_masonry-grid','espresso_masonry_grid', 10, 1 );

if (!function_exists('espresso_masonry_grid')) {
	function espresso_masonry_grid(){

	global $org_options, $this_event_id, $events, $ee_attributes; 
	
		wp_enqueue_script( 'jquery-masonry');
		wp_register_style( 'espresso_masonry_grid', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'templates/masonry-grid/style.css' );
		wp_enqueue_style( 'espresso_masonry_grid');

	if(isset($ee_attributes['default_image'])) { 
		
		$default_image = $ee_attributes['default_image']; 
	
	}

}
	echo '<div id="espresso_masonry" class="masonry js-masonry">';

		foreach ($events as $event){

			$this_event_id		= $event->id;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
			$event_status 		= __('Register Now!', 'event_espresso');

			//use the wordpress date format.
			$date_format = get_option('date_format');


			$att_num = get_number_of_attendees_reg_limit($event->id, 'num_attendees');
			//Uncomment the below line to hide an event if it is maxed out
			//if ( $att_num >= $event->reg_limit  ) { continue; $live_button = 'Closed';  }
			if ( $att_num >= $event->reg_limit ) { $event_status = __('Sold Out', 'event_espresso');  } elseif ( event_espresso_get_status($event->id) == 'NOT_ACTIVE' ) { $event_status = __('Closed', 'event_espresso');}

			//waitlist
			if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
				$registration_url	= espresso_reg_url($event->overflow_event_id);
				$event_status		= __('Sold Out - Join Waiting List', 'event_espresso');
			}
			
			if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ){
				$event_status 		= __('Member Only', 'event_espresso'); 
			}

			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');

			if(!isset($default_image)) { $default_image = ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/masonry-grid/default.jpg';}
			$image = isset($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : $default_image;

			//uncomment this and comment out the above line if you want to use the Organisation logo
			//if($image == '') { $image = $org_options['default_logo_url']; }

			echo '<div class="ee_masonry">';
			echo '<a id="a_register_link-' . $event->id . '" href="' . $registration_url . '" class="darken">';
            echo '<img src="' . $image . '" /><h2>';
			echo stripslashes($event->event_name);
			echo '</a></h2>';
			echo !empty($event->event_desc) ? '<p class="event_desc">'.$event->event_desc.'</p>' : '';
			echo '<p class="event-cost">Cost: ';
			echo $event->event_cost === "0.00" ? __('FREE', 'event_espresso') : $org_options['currency_symbol'] . $event->event_cost;
			echo '</p>';
			echo '<p class="event-date">'.date($date_format, strtotime($event->start_date)).'</p>';
			echo '<p class="event-status"><a id="register_link-' . $event->id . '" href="' . $registration_url . '" class="button darken">' . $event_status. '</a></p>';
			echo '</div>';
		}

}
?>

<script>
jQuery( document ).ready( function( $ ) {
    $( '#espresso_masonry' ).masonry( {
    	columnWidth: 240,
    	itemSelector: '.ee_masonry',
        isAnimated: true
    } );
} );
</script>