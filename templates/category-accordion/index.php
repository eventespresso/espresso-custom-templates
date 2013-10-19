<?php
//Template Name: Category Accordion
//Description: Will display the categories in bars, once clicked events associated with that category will appear in an "accordion" style. If category colours are turned on, the block to the left will be that colour, otherwise it will default to grey.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="category-accordion"]
//
//Extra parameter: exclude="1,2,3"
//This uses the category IDs and will exclude them from being listed. Use a single number or a comma separated list of numbers.

add_action('action_hook_espresso_custom_template_category-accordion','espresso_category_accordion', 10, 1);
if (!function_exists('espresso_category_accordion')) {
	function espresso_category_accordion(){
		global $events, $ee_attributes;

		//Extract shortcode attributes
		extract($ee_attributes);

		//Custom shortcode parameter: css_file
		//Example shortcode usage: [EVENT_CUSTOM_VIEW template_name="category-accordion" css_file="brown"]
		$css_file = isset($css_file) && !empty($css_file) ? $css_file : 'style';

		//Register styles
		wp_register_style( 'espresso_category_accordion', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH.'templates/category-accordion/'.$css_file.'.css' );
		wp_enqueue_style( 'espresso_category_accordion');


		global $wpdb;
		$sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
		$temp_cats = $wpdb->get_results($sql);

		//var_dump($temp_cats);
		//var_dump($ee_attributes);
		//var_dump($events);

		$exclude = isset($ee_attributes['exclude']) ? explode(',', $ee_attributes['exclude']) : false;

		if($exclude) {
			foreach($exclude as $exc) {
				foreach($temp_cats as $subKey => $subArray) {
					if($subArray->id == $exc) {
						unset($temp_cats[$subKey]);
					}
				}
			}

		}
?>


<div>
    <ul id="espresso-select" class="dd_js">
    	<?php foreach ($temp_cats as $cats) {
    		$catcode = $cats->id;
    		$catmeta = unserialize($cats->category_meta);
    		$bg = $catmeta['event_background'];
    		$use_bg = $catmeta['use_pickers'];

		?>
		<?php
			if($use_bg == "Y") {
		?>
			<!-- I know, inline styles suck, but only way to add an unknown colour -->
			<span style="display:block; background-color:<?php  echo $bg; ?>">
		<?php
		} else {
		?>
			<span>
		<?php
		}
		?>
        <li>
            <h3><a href="#" class="event_category_name">
                <?php echo $cats->category_name; ?>
                </a></h3>
            <i class="icon-chevron-sign-down"></i>
            <ul>
                <?php
            foreach ($events as $event){
            	$arr=explode(",",$event->category_id);
            	foreach ($arr as $a) {
	            	if ($a == $catcode) {
	                $externalURL = $event->externalURL; $registration_url = !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);?>
	                <li><a class="a_event_title" id="a_event_title-<?php echo $event->id; ?>" href="<?php echo $registration_url; ?>"><?php echo stripslashes_deep($event->event_name)?><br />
	                    <?php echo event_date_display($event->start_date, 'M j, Y'); ?></a> </li>
	                <?php
	            	}
            	}
            }
            ?>
            </ul>
        </li>
    </span>
        <?php } // end foreach ?>
    </ul>
</div>


<script type="application/javascript">

jQuery(document).ready(function(){

	//if javascript enabled disable CSS hover effect.
	jQuery('.dd_js').removeClass('dd_js');
	jQuery('#espresso-select li ul').css('display', 'none');


	jQuery('.event_category_name').click(function() {

		if(jQuery(this).parents().siblings("ul").is(":visible")){
        jQuery(this).parents().siblings("ul").slideUp();
      } else {
        jQuery("#espresso-select ul").slideUp();
        jQuery(this).parents().siblings("ul").slideToggle();
      }

	});


});


</script>
<?php
	}
}