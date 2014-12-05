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
			"post_processor"     	=>  array( $this, 'calcreation_form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false							// Optional 	: default false : setting as true will only allow once per form
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
		$centers = explode(", ", Caldera_forms::do_magic_tags($config['centers']));
		$tutor_id = Caldera_forms::do_magic_tags($config['tutor_id']);

		// Retrieve tutor name and email.
		$result = pods('user', $tutor_id);
		if ($result->exists()) {
			$tutor_email = $result->display('user_email');
			$tutor_name = $result->display('display_name');
		} else {
			// Handle case where existing user was not found.
			$this->echo_error("Tutor not found using id: ".$tutor_id);
			die;
		}
		
		// Set GCal information and require necessary files.
		$this->service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);

		// Create string to get calendars by specific speaking center.
		$calendar_queries = array_map(function($center_id) use ($tutor_id) {
			return array(
				"query" => "tutor.id = $tutor_id and center.id = $center_id",
				"center" => $center_id
			);
		}, array_values($centers));

		$needed_centers = array();

		// Check to see if calendars exist that are associated with the
		// tutor/center combinations.
		foreach($calendar_queries as &$calendar_query) {
			$result = pods('calendar', array(
				'where' => $calendar_query["query"],
				'limit' => 1
			));
			// Existing calendar not found.
			if (!($result->total() == 1)) {
				$needed_centers[] = $calendar_query["center"];
			}
		}

		// Create Google Calendar and Pod to hold information.
		foreach($needed_centers as $needed_center) {
			// TODO: Turn this into a single query and use fetch() with id
			$center_info = pods('center', $needed_center);
			// Create calendar
			if ($center_info->exists()) {
				$calendar_admin = $center_info->display("calendar_admin");
				$new_calendar_id = $this->create_calendar($tutor_name, $calendar_admin);
				$this->create_calendar_pod($tutor_id, $needed_center, $new_calendar_id);
			} else {
				// Handle center not existing.
				$this->echo_error("Center not found with id: ".$needed_center);
				die;
			}
		}
	}

	/**
	 * Takes in a string corresponding to the email of a Google account
	 * and creates a Google Calendar and associates it as a secondary
	 * calendar.
	 *
	 * @param  $name  {string}  the desired name of the Google Calendar.
	 * @param  $admin_account  {string}  the email of the Google Account
	 * to associate the calendar with.
	 * @return  {string}  the Google Calendar id of the newly created
	 * Google Calendar.
	 */
	public function create_calendar($name, $admin_account) {
		$calendar = new Google_Service_Calendar_Calendar();
		$calendar->setSummary($name);
		$calendar->setTimeZone('America/New_York');

		$createdCalendar = $this->service->calendars->insert($calendar);
		$calendar_id = $createdCalendar->getId();

		$owner_rule = new Google_Service_Calendar_AclRule();
		$public_access = new Google_Service_Calendar_AclRule();
		$scope = new Google_Service_Calendar_AclRuleScope();
		$public_scope = new Google_Service_Calendar_AclRuleScope();

		$scope->setType("user");
		$scope->setValue($admin_account);
		$owner_rule->setScope($scope);
		$owner_rule->setRole("owner");

		$createdRule = $this->service->acl->insert($calendar_id, $owner_rule);

		$public_scope->setType("default");
		$public_access->setScope($public_scope);
		$public_access->setRole("reader");

		$nextRule = $this->service->acl->insert($calendar_id, $public_access);

		return $calendar_id;
	}

	/**
	 * Creates a Calendar Pod, associates it with the given tutor and
	 * center, and sets its calendar_id field to the given Google Calendar
	 * id.
	 *
	 * @param  $tutor_id  {integer}  the id of the tutor to associate the
	 * Google Calendar with.
	 * @param  $center_id  {integer}  the id of the center to associate
	 * the calendar with.
	 * @param  $google_calendar_id  {string}  the id, returned by
	 * create_calendar, to store as the calendar_id for the newly created
	 * calendar pod.
	 * @return  {integer}  the id of the newly created calendar pod object
	 */
	public function create_calendar_pod($tutor_id, $center_id, $google_calendar_id) {
		$pod = pods('calendar');
		$data = array(
			'tutor' => $tutor_id,
			'center' => $center_id,
			'calendar_id' => $google_calendar_id
		);

		return $pod->add($data);
	}

	private function echo_error($text) {
		echo "<pre style='border: 1px solid red; text-align: center;'>";
		echo "Error: $text";
		echo "</pre>";
	}
}

// Create the instance. (can be done however you like)
new Calcreation_Processor();
?>
