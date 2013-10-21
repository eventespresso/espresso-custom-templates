<?php
/**
 *
 * @package    	Custom Template Display
 * @subpackage 	Category Accordion
 * @since      
 * @author     	Event Espresso <support@eventespresso.com>
 * @copyright  	Copyright (c) 2013, Event Espresso
 * @link       	http://eventespresso.com
 * @license    	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Template Name: Category Accordion
 *
 * Description: Will display the categories in bars, once clicked events associated with that category 
 * will appear in an "accordion" style. If category colours are turned on, the block to the left will 
 * be that colour, otherwise it will default to grey.
 * Shortcode Example: [EVENT_CUSTOM_VIEW template_name="category-accordion"]
 *
 * Extra parameter: exclude="1,2,3"
 * This uses the category IDs and will exclude them from being listed. Use a single number or a comma separated list of numbers.
 *
 */

add_action('action_hook_espresso_custom_template_category-accordion','espresso_category_accordion', 10, 1);

if (!function_exists('espresso_category_accordion')) {

	function espresso_category_accordion(){

		global $org_options,$events, $ee_attributes;

		//Extract shortcode attributes
		extract($ee_attributes);

		$css_file = isset($css_file) && !empty($css_file) ? $css_file : 'style';

		//Register styles
		wp_register_style( 'espresso_category_accordion', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'templates/category-accordion/'. $css_file .'.css' );
		wp_enqueue_style( 'espresso_category_accordion');


		global $wpdb;
		$sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
		$categories = $wpdb->get_results($sql);

		$exclude = isset($ee_attributes['exclude']) && !empty($ee_attributes['exclude']) ? explode(',', $ee_attributes['exclude']) : false;

		if($exclude) {
			foreach($exclude as $exc) {
				foreach($temp_cats as $subKey => $subArray) {
					if($subArray->id == $exc) {
						unset($temp_cats[$subKey]);
					}
				}
			}
		}

		//Check for Multi Event Registration
		$multi_reg = false;
		if (function_exists('event_espresso_multi_reg_init')) {
			$multi_reg = true;
		}
		echo '<div id="espresso_accordion"><ul class="espresso-category-accordion">';

				foreach ($categories as $category) {
    			$catcode = $category->id;
    			$catmeta = unserialize($category->category_meta);
    			$bg = $catmeta['event_background'];
    			$fontcolor = $catmeta['event_text_color'];
    			$use_bg = $catmeta['use_pickers'];

				if($use_bg == "Y") {
					echo '<li class="has-sub" style="border-left: 10px solid ' . $bg . '";><a href="#">';
				} else {
					continue; 
				}
		
            echo '<h2 class="ee-category" style="color:' . $bg . '">';
            echo $category->category_name;
            echo '</h2></a>';
            echo '<ul><li>';

            foreach ($events as $event){
				$path_to_thumbnail = '';
				$filename = '';
				$event_meta = unserialize($event->event_meta);
            	if (!empty($event_meta['event_thumbnail_url'])){
					$upload_dir = wp_upload_dir();
					$pathinfo = pathinfo( $event_meta['event_thumbnail_url'] );
					$dirname = $pathinfo['dirname'] . '/';
					$filename = $pathinfo['filename'];
					$ext = $pathinfo['extension'];
					$path_to_thumbnail = $dirname . $filename . '.' . $ext;
	
					if ( $pathinfo['dirname'] == $upload_dir['baseurl'] ) {
						if ( ! file_exists( $uploads['basedir'] . DIRECTORY_SEPARATOR . $filename . '.' . $ext )) {
							$path_to_thumbnail = file_exists( $uploads['basedir'] . DIRECTORY_SEPARATOR . $filename . '.' . $ext ) ? $event_meta['event_thumbnail_url'] : FALSE;
						}
					}
				}
				
				//lets check the staus and attendee count
				$open_spots	= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');

				if($open_spots < 1) {
					$the_status = __('Sold Out.', 'event_espresso');
				} elseif (event_espresso_get_status($event->id) == 'NOT_ACTIVE') {
					$the_status = __('Closed.', 'event_espresso');
				} else {
					$the_status = __('Register Now!', 'event_espresso');
				}

				if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
					$the_status = __('Join Waiting List', 'event_espresso');
					$registration_url = espresso_reg_url($event->overflow_event_id);
				}

				$event_name = stripslashes_deep($event->event_name);

            	$arr=explode(",",$event->category_id);
            	foreach ($arr as $a) {
	            	if ($a == $catcode) {
	                $externalURL = $event->externalURL; $registration_url = !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
	                
	                if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
						$the_status = __('Join Waiting List', 'event_espresso');
						$registration_url = espresso_reg_url($event->overflow_event_id);
					}
	                echo '<li><h3 class="event-title" id="event-title-' . $event->id . '" ><a href="' . $registration_url . '"">' . $event_name . '</a></h3>';
	                echo !empty($filename)?'<img id="ee-event-thumb-' . $event->id . '" class="ee-event-thumb" src="' . $path_to_thumbnail . '" alt="image of ' . $filename . '" />':'';
	                echo '<h4 class="event-date">' . event_date_display($event->start_date, 'M j, Y') . '</h4>';
	                echo '<p class="event-cost">' . $org_options['currency_symbol'];
	                echo $event->event_cost . '</p>'; 
	                echo '<p class="event-status"><a href="' . $registration_url . '"">' . $the_status . '</a></p>';
	            	echo '</li>';
	            	}
            	}
            }
            echo '</ul>';
        }
    echo '</li></ul></div>';
	}
}?>
<script>
jQuery(function ($) {
$('#espresso_accordion > ul > li > a').click(function() {
  $('#espresso_accordion li').removeClass('active');
  $(this).closest('li').addClass('active');	
  var checkElement = $(this).next();
  if((checkElement.is('ul')) && (checkElement.is(':visible'))) {
    $(this).closest('li').removeClass('active');
    checkElement.slideUp('normal');
  }
  if((checkElement.is('ul')) && (!checkElement.is(':visible'))) {
    $('#espresso_accordion ul ul:visible').slideUp('normal');
    checkElement.slideDown('normal');
  }
  if($(this).closest('li').find('ul').children().length == 0) {
    return true;
  } else {
    return false;	
  }		
	});
});
</script>