<?php
/**
 *
 * @package    Custom Template Display
 * @subpackage Category Accordion
 * @since      
 * @author     Seth Shoultes <seth@eventespresso.com>
 * @copyright  Copyright (c) 2013, Seth Shoultes
 * @link       http://eventespresso.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Template Name: Category Accordion
 * Description: Will display the categories in bars, once clicked events associated with that category 
 * will appear in an "accordion" style. If category colours are turned on, the block to the left will 
 * be that colour, otherwise it will default to grey.
 * Shortcode Example: [EVENT_CUSTOM_VIEW template_name="category-accordion"]
 *
 * Extra parameter: exclude="1,2,3"
 * This uses the category IDs and will exclude them from being listed. Use a single number or a comma separated list of numbers.
 */

add_action('action_hook_espresso_custom_template_category-accordion','espresso_category_accordion', 10, 1);

if (!function_exists('espresso_category_accordion')) {

	function espresso_category_accordion(){

		global $org_options,$events, $ee_attributes;

		//Extract shortcode attributes
		extract($ee_attributes);

		$css_file = isset($css_file) && !empty($css_file) ? $css_file : 'style';

		//Register styles
		wp_register_style( 'espresso_category_accordion', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'templates/category-accordion/'.$css_file.'.css' );
		wp_enqueue_style( 'espresso_category_accordion');


		global $wpdb;
		$categories = $wpdb->get_results(
			"SELECT * FROM wp_events_category_detail"
			);

		//$temp_cats = $wpdb->get_results($categories);

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
		echo '<div id="espresso_accordion"><ul>';

				foreach ($categories as $category) {
    			$catcode = $category->id;
    			$catmeta = unserialize($category->category_meta);
    			$bg = $catmeta['event_background'];
    			$use_bg = $catmeta['use_pickers'];

				if($use_bg == "Y") {
					echo '<li style="border-left: 10px solid ' . $bg . '";><a href="#">';
				} else {
					continue; 
				}
		
            echo '<h4 class="ee_category" style="color:' .$bg . '">';
            echo $category->category_name;
            echo '</h4></a>';
            echo '<ul></li><li>';

            foreach ($events as $event){

            	//lets check the staus and attendee count
				$open_spots	= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');

				if($open_spots < 1) {
					$the_status = __('Sold Out.');
				} elseif (event_espresso_get_status($event->id) == 'NOT_ACTIVE') {
					$the_status = __('Closed.');
				} else {
					$the_status = 'Register Now!';
				}

				if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
					$the_status = __('Join Waiting List');
					$registration_url = espresso_reg_url($event->overflow_event_id);
				}

            	$arr=explode(",",$event->category_id);
            	foreach ($arr as $a) {
	            	if ($a == $catcode) {
	                $externalURL = $event->externalURL; $registration_url = !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
	                
	                if ($event->allow_overflow == 'Y' && event_espresso_get_status($event->id) == 'ACTIVE'){
						$the_status = __('Join Waiting List');
						$registration_url = espresso_reg_url($event->overflow_event_id);
					}
	                echo '<a class="a_event_title" id="a_event_title-' . $event->id . '" href="' . $registration_url . '"' . stripslashes_deep($event->event_name) . '<span class="event_status">';
	                echo $org_options['currency_symbol'].$event->event_cost; 
	                echo '<br>';
	                echo $the_status;
	                echo '</span><br />';
	                echo event_date_display($event->start_date, 'M j, Y');
	                echo '</a></li>';
	            	}
            	}
            }
            echo '</ul></li></span>';
        } // end foreach 
    echo '</ul></div></ul></div>';
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