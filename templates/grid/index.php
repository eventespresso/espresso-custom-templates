<?php
//Template: Grid View
//Description: This template creates a grid style view of events.
//Shortcode Example: [EVENT_CUSTOM_VIEW template_name="grid" max_days="30" category_identifier="concerts"].
//Requirements: CSS skills to customize styles, HTML/PHP to restructure.
//The end of the action name (example: "action_hook_espresso_custom_template_") should match the name of the template. In this example, the last part the action name is "grid-view",

// IMPORTANT you may need to tweak the box or title sizes if your events have long titles.


add_action('action_hook_espresso_custom_template_grid','espresso_custom_template_grid');

function espresso_custom_template_grid(){
	//Load the css file
	wp_register_style( 'espresso_custom_template_grid', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH."/templates/grid/style.css" );
	wp_enqueue_style( 'espresso_custom_template_grid');

	wp_register_script( 'jquery_dropdown', ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/grid/js/jquery.grid.js', array('jquery'), '0.1', TRUE );
	wp_enqueue_script( 'jquery_dropdown' );

	//Defaults
	global $org_options, $this_event_id, $events;

	//Uncomment to view the data being passed to this file
	//echo '<h4>$events : <pre>' . print_r($events,true) . '</pre> <span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';

	?>
	<div class="">

		<?php
		foreach ($events as $event){
			//Debug
			$this_event_id		= $event->id;
			$member_only		= !empty($event->member_only) ? $event->member_only : '';
			$event_meta			= unserialize($event->event_meta);
			$externalURL 		= $event->externalURL;
			$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);

			//use the wordpress date format.
			$date_format = get_option('date_format');

			//this will ignore an event if the event is maxed out
			$att_num = get_number_of_attendees_reg_limit($event->id, 'num_attendees');
			if ( $att_num >= $event->reg_limit  ) { continue; $live_button = 'Closed';  }

			//Gets the member options, if the Members add-on is installed.
			$member_options = get_option('events_member_settings');

			$image = $event_meta['event_thumbnail_url'];
			if($image == '') { $image = ESPRESSO_CUSTOM_DISPLAY_PLUGINPATH . 'templates/grid/default.jpg'; }

			//uncomment this and comment out the above line if you want to use the Organisation logo
			//if($image == '') { $image = $org_options['default_logo_url']; }

				?>


            <div class="ee_grid_box">
                <a id="a_register_link-<?php echo $event->id; ?>" href="<?php echo $registration_url; ?>" class="darken">
                    <img src="<?php echo $image; ?>" alt="" />
                    <span>
                        <h2>
                        <span>

                            <?php if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ) {
                            echo "Member Only"; } else { ?>

                            <?php echo stripslashes($event->event_name); ?><br />

                            <?php if($event->event_cost === "0.00") { echo "FREE"; } else { echo $org_options['currency_symbol'] . $event->event_cost;  } ?><br />

                            <?php echo date($date_format, strtotime($event->start_date)) ?><br />

                            Register Now!

                            <?php 			}// close is_user_logged_in	 ?>

                        </span>
                        </h2>
                    </span>
                </a>
            </div>

		<?php
		 } //close foreach ?>
	</div>

<?php } ?>
