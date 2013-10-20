<?php
/**
 *
 * @package    Event Espresso Category Accordion
 * @subpackage Includes
 * @since      0.1.0
 * @author     
 * @copyright  
 * @link       
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/* Register shortcodes. */
add_action( 'init', 'espresso_register_shortcodes' );


/**
 * Registers the [espresso_category_accordion] shortcode.
 *
 * @since  0.1.0
 * @access public
 * @return void
 */
function espresso_category_accordion_register_shortcodes() {
	add_shortcode( 'espresso_category_accordion', 'espresso_category_accordion_do_shortcode' );
}

class Espresso_Category_Accordion {

	/**	
	 *
	 * @since  0.1.0
	 * @access public
	 * @var    array
	 */
	public $args = array();

	/**
	 *
	 * @since  0.1.0
	 * @access public
	 * @var    array
	 */
	public $ee_cat_accordion = array();

	/**
	 * Formatted output
	 *
	 * @since  0.1.0
	 * @access public
	 * @var    string
	 */
	public $markup = '';

	/**
	 * Constructor method.  Sets up everything.
	 *
	 * @since  0.1.0
	 * @access public
	 * @param  array   $args
	 * @return void
	 */
	public function __construct( $args = array() ) {
		global $wp_embed;

		/* Use same default filters as 'the_content' with a little more flexibility. */
		add_filter( 'ee_cat_accordion_content', array( $wp_embed, 'run_shortcode' ),   5 );
		add_filter( 'ee_cat_accordion_content', array( $wp_embed, 'autoembed'     ),   5 );
		add_filter( 'ee_cat_accordion_content',                   'wptexturize',       10 );
		add_filter( 'ee_cat_accordion_content',                   'convert_smilies',   15 );
		add_filter( 'ee_cat_accordion_content',                   'convert_chars',     20 );
		add_filter( 'ee_cat_accordion_content',                   'wpautop',           25 );
		add_filter( 'ee_cat_accordion_content',                   'do_shortcode',      30 );
		add_filter( 'ee_cat_accordion_content',                   'shortcode_unautop', 35 );


		$this->args = wp_parse_args( $args, $defaults );

		/* Set up. */
		$this->set_ee_cat_accordion();

		/* If there are any, set the HTML them. */
		if ( !empty( $this->ee_cat_accordion ) )
			$this->markup = $this->set_markup( $this->whistles );
	}

	/**
	 * Method for grabbing the array of ee_cat_accordions queried.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return array
	 */
	public function get_ee_cat_accordion() {
		return $this->ee_cat_accordion;
	}

	/**
	 *
	 * @since  0.1.0
	 * @access public
	 * @return void
	 */
	public function set_ee_cat_accordion() {//begin1

		$sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
		$temp_cats = $wpdb->get_results($sql);

		$exclude = isset($ee_attributes['exclude']) && !empty($ee_attributes['exclude']) ? explode(',', $ee_attributes['exclude']) : false;

		if($exclude) {//2
			foreach($exclude as $exc) {//3
				foreach($temp_cats as $subKey => $subArray) {//4
					if($subArray->id == $exc) {
						unset($temp_cats[$subKey]);
					}//2
				}//3
			}//4

		//Check for Multi Event Registration
		$multi_reg = false;
		if (function_exists('event_espresso_multi_reg_init')) {//5
			$multi_reg = true;
		}//5
	}//end1

	/**
	 * Return the HTML markup for display.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return string
	 */
	public function get_markup() {
		return $this->markup;
	}

	/**
	 * Sets the HTML markup for display.
	 *
	 * Important!  This method must be overwritten in a sub-class.  Your sub-class should return an 
	 * HTML-formatted string of the $whistles array.
	 *
	 * @since  0.1.0
	 * @access public
	 * @param  array  $whistles
	 * @return string
	 */
	public function set_markup( $whistles ) {
		wp_die( sprintf( __( 'The %s method must be overwritten in a sub-class.', 'whistles' ), '<code>' . __METHOD__ . '</code>' ) );
	}
}