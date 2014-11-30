<?php
/**
 * Plugin Name: Caldera Forms - Reservation Processor
 * Plugin URI:  
 * Description: Processor to handle incoming reservations made using fullcalendar field.
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
 * @package    Reservation_Processor
 */
class Reservation_Processor {

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

		$this->plugin_slug = 'reservation_processor';
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

		$processors['reservation_processor'] 	= array(
			"name"              =>  __("Reservation Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Processor to handle reservations", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"author"            =>  'Chris Hunt',											// Optional 	: Author name 
			"processor"     	=>  array( $this, 'form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false,														// Optional 	: default false : setting as true will only allow once per form
			"magic_tags"    	=>  array(														// Optional 	: Array of values processor returns to be used in magic tag autocomplete list
				"error",			// Boolean, true if there was an error in any of the reservations to be made/cancelled.
				"student_email_output", // String, text to use in email confirmation to student
				"tutor_email_output" // String, text to use in email confirmation to tutor
			)
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

		// Get config values.
		$calendar_id = $config['calendar_id'];
		$student_email = Caldera_forms::do_magic_tags($config['student_email']);
		$encoded_event_info = Caldera_forms::do_magic_tags($config['event_details']);

		// Get JSON information from calendar field.
		$selected_event_details = json_decode(urldecode($encoded_event_info), true);
		$event_mode = $selected_event_details['mode'];
		$selected_events = $selected_event_details['events'];

		// Set GCal information and require necessary files.
		$service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);

		// Get events from GCal.
		$cal_events = array_map(function($event_info) use (&$service, $calendar_id) {
			return $service->events->get($calendar_id, $event_info['id']);
		}, $selected_events);

		// Set events as reserved.
		foreach($cal_events as $event) { 
			$entended_properties = $event->getExtendedProperties();
			if ($entended_properties == null) {
				$this->echo_error("That event can't be selected.");
				return array("error-cause" => "incorrect event type");
			}
			$private_props = $entended_properties->getPrivate();
			if ($private_props['is_reserved'] == "true") {
				$this->echo_error("This time is already reserved.");
				return array("error-cause" => "selected reserved time");
			}
			else {
				$private_props['is_reserved'] = "true";
				$oldSummary = $event->getSummary();
				$oldDescription = $event->getDescription();
				
				$event->setSummary("[RESERVED], " . $oldSummary);
				$event->setDescription($oldDescription . "Reserved by $student_email.");
				$entended_properties->setPrivate(array_merge(array("student_email" => $student_email), $private_props));
				$event->setExtendedProperties($entended_properties);
				$event->setAttendees($this->add_attendee($student_email, $event));

				$newEvent = $service->events->update($calendar_id, $event->getId(), $event);
			}
		}

		// Extract information relevant to email text formatting.
		$event_info = array_map(function($event) {
			$info = array();
			$private_props = $event->getExtendedProperties()->getPrivate();
			$info['tutor_name'] = $private_props['tutor_name'];
			$info['tutor_email'] = $private_props['tutor_email'];
			$info['start'] = new DateTime($event->getStart()->getDateTime());
			$info['end'] = new DateTime($event->getEnd()->getDateTime());
			return $info;
		}, $cal_events);

		$newline = "\r\n";
		// Generate student email text.
		$student_email_header = "The following " . (count($event_info) > 1 ? "events have" : "event has") ." been confirmed:";
		$student_email_body = "";
		foreach($event_info as $info) {
			$time_diff = $info['start']->diff($info['end']);
			// Calculate minutes assuming events don't last more than 24 hours.
			$minutes = $time_diff->h * 60 + $time_diff->i;
			$info_string = $info['start']->format("g:ia") . " - " .
				$info['end']->format("g:ia l, F j, Y") . " (" . $minutes .
				" minutes) with " . $info['tutor_name'] . "(" . $info['tutor_email'] . ")";
			$student_email_body = $student_email_body . $newline . $info_string;
		}
		$student_email_footer = "";

		// Generate tutor email text.

		// This example will return the users input and the date in the defined tags
		$return_meta = array(
			'student_email_output'		=>	$student_email_header . $student_email_body . $student_email_footer
		);

		return $return_meta;
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

	private function echo_error($text) {
		echo "<pre style='border: 1px solid red; text-align: center;'>";
		echo "Error: $text";
		echo "</pre>";
	}
}

// Create the instance. (can be done however you like)
new Reservation_Processor();
?>
