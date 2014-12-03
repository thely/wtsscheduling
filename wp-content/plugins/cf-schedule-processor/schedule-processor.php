<?php
/**
 * Plugin Name: Caldera Forms - Schedule Processor
 * Plugin URI:  
 * Description: Processor to handle incoming schedule changed made using fullcalendar field.
 * Version:     1.0.0
 * Author:      Chris Hunt
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
class Schedule_Processor {

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

		$this->plugin_slug = 'schedule_processor';
		$this->version = '1.0.0';

		$this->set_locale();

		// Add filter to regester the form processor
		add_filter( 'caldera_forms_get_form_processors', array( $this, 'register_form_processor') );

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
	public function register_form_processor( $processors ) {

		// Add our processor to the $processors array using our processor_slug as the key.
		// It is possible to replace an existing processor by redefining it and hooking in with a lower priority i.e 100

		$processors['schedule_processor'] 	= array(
			"name"              =>  __("Schedule Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Processor to handle schedule management.", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"author"            =>  'Chris Hunt',											// Optional 	: Author name 
			"processor"     	=>  array( $this, 'form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false														// Optional 	: default false : setting as true will only allow once per form
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
	public function form_processor( $config, $form ) {
		// globalised transient object - can be used for passing data between processor stages ( pre -> post etc.. )
		global $transdata;

		// Get additional form information.
		$data = $this->form_debug_information($form);

		// Get config values.
		$tutor_email = Caldera_forms::do_magic_tags($config['tutor_email']);
		$encoded_event_info = Caldera_forms::do_magic_tags($config['event_details']);

		// Get JSON information from calendar field.
		$event_details = json_decode(urldecode($encoded_event_info), true);
		$edited_events = $event_details['events'];
		$calendar_id = $event_details['calendar_id'];

		// Set GCal information and require necessary files.
		$service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);

		if ($edited_events != null) {
		foreach($edited_events as $event_info) {
			// New event created.
			if ($event_info['mode'] == "insert") {
				$newEvent = new Google_Service_Calendar_Event();
				$newEvent->setSummary("**V2** set by $tutor_email");
				$newEvent->setDescription("Tutor email: $tutor_email, tutor name: {$data['name']}.");
				$newEvent->setStart($this->make_time($event_info['start']));
				$newEvent->setEnd($this->make_time($event_info['end']));
				$newEvent->setAttendees($this->add_attendee($tutor_email, $newEvent));

				$extendedProperties = new Google_Service_Calendar_EventExtendedProperties();
				$extendedProperties->setPrivate(array (
					"tutor_email" 		=> $tutor_email,
					"tutor_name" 		=> $data['name'],
					"is_group" 			=> "false",
					"is_reserved"		=> "false"
				));
				$extendedProperties->setShared(array());
				$newEvent->setExtendedProperties($extendedProperties);

				$createdEvent = $service->events->insert($calendar_id, $newEvent);
			}
			else if ($event_info['mode'] == "update") { // Existing event updated.
				$oldEvent = $service->events->get($calendar_id, $event_info['id']);
				$oldEvent->setStart($this->make_time($event_info['start']));
				$oldEvent->setEnd($this->make_time($event_info['end']));

				$newEvent = $service->events->update($calendar_id, $oldEvent->getId(), $oldEvent);
			}
			else if ($event_info['mode'] == "remove") { // Removing event.
				$service->events->delete($calendar_id, $event_info['id']);
			}
		}
	}
	}

	private function add_attendee($email, &$event) {
		$guest = new Google_Service_Calendar_EventAttendee();
		$guest->setEmail($email);
		if (isset($event)) {
			return array_merge(array($guest), $event->getAttendees());
		}
		else {
			return array($guest);
		}
	}

	private function make_time($time) {
		$newTime = new Google_Service_Calendar_EventDateTime();
		$newTime->setDateTime($time);
		$newTime->setTimeZone("America/New_York");
		return $newTime;
	}

	private function form_debug_information($form) {
		$data = array(); // build a data array of submitted data
		$raw_data = Caldera_Forms::get_submission_data( $form ); // Raw data is an array with field_id as the key

		foreach( $raw_data as $field_id => $field_value ){ // create a new array using the slug as the key
			if( in_array( $field_id, array( '_entry_id', '_entry_token' ) ) )
				continue; // Ignores irrelevant debug fields.
			if( in_array( $form[ 'fields' ][ $field_id ][ 'type' ], array( 'button', 'html' ) ) )
				continue; //ignores buttons

			$data[ $form[ 'fields' ][ $field_id ][ 'slug' ] ] = $field_value; // get the field slug for the key instead
		}
		return $data;
	}

	private function echo_error($text) {
		echo "<pre style='border: 1px solid red; text-align: center;'>";
		echo "Error: $text";
		echo "</pre>";
	}

}

// Create the instance. (can be done however you like)
new Schedule_Processor();
?>
