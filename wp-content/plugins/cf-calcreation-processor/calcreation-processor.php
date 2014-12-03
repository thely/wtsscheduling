<?php
/**
 * Plugin Name: Caldera Forms - Calendar Creation Processor
 * Plugin URI:  
 * Description: Processor to create new calendars for new tutors
 * Version:     1.0.0
 * Author:      Becky Brown
 * Author URI:  
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */



/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Schedule_Processor
 */
class Calcreation_Processor {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_slug    The string used to uniquely identify this plugin.
	 */
	protected $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Set the hooks for processing
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_slug = 'calcreation_processor';
		$this->version = '1.0.0';

		$this->set_locale();

		// Add filter to regester the form processor
		add_filter( 'caldera_forms_get_form_processors', array( $this, 'calcreate_register_form_processor') );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		load_plugin_textdomain(
			$this->plugin_slug,
			false,
			plugin_dir_path( __FILE__ ) . '/languages/'
		);

	}

	/**
	 * Register form processor by adding to the processors list
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function calcreate_register_form_processor( $processors ) {

		// Add our processor to the $processors array using our processor_slug as the key.
		// It is possible to replace an existing processor by redefining it and hooking in with a lower priority i.e 100

		$processors['calcreation_processor'] 	= array(
			"name"              =>  __("Calendar Creation Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Processor to handle adding new calendars.", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"author"            =>  'Becky Brown',											// Optional 	: Author name 
			"processor"     	=>  array( $this, 'calcreation_form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false,
			"magic_tags"		=>	array(
				"new_calendar_id"
			)												// Optional 	: default false : setting as true will only allow once per form
		);

		return $processors;
	}

	/**
	 * Define the processor function
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array				$config				The config array of the settings for this processor instance
	 * @var      array				$form				Complete form config array
	 * @return   array				optional			Array data returned is magic_tag translateble and appended to entry as meta data
	 */
	public function calcreation_form_processor( $config, $form ) {
		// globalised transient object - can be used for passing data between processor stages ( pre -> post etc.. )
		global $transdata;

		// Get config values.
		$admin_account = $config['admin_account'];
		$tutor_name = Caldera_forms::do_magic_tags($config['tutor_name']);
		$tutor_email = Caldera_forms::do_magic_tags($config['tutor_email']);

		// Set GCal information and require necessary files.
		$service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);
		echo "Trying things"; // DEBUG

		$calendar = new Google_Service_Calendar_Calendar();
		$calendar->setSummary($tutor_name);
		$calendar->setTimeZone('America/New_York');

		$createdCalendar = $service->calendars->insert($calendar);

		echo "We made a thing! " . $createdCalendar->getId(); // DEBUG

		$owner_rule = new Google_Service_Calendar_AclRule();
		$public_access = new Google_Service_Calendar_AclRule();
		$scope = new Google_Service_Calendar_AclRuleScope();
		$public_scope = new Google_Service_Calendar_AclRuleScope();

		$scope->setType("user");
		$scope->setValue($admin_account);
		$owner_rule->setScope($scope);
		$owner_rule->setRole("owner");

		$createdRule = $service->acl->insert($createdCalendar->getId(), $owner_rule);

		$public_scope->setType("default");
		$public_access->setScope($public_scope);
		$public_access->setRole("reader");

		$nextRule = $service->acl->insert($createdCalendar->getId(), $public_access);

		$return_meta = array(
			"new_calendar_id" => $createdCalendar->getId()
		);

		return $return_meta;
	}
}

// Create the instance. (can be done however you like)
new Calcreation_Processor();
?>
