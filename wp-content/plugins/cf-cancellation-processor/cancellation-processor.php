<?php
/**
 * Plugin Name: Caldera Forms - Cancellation Processor
 * Plugin URI:  
 * Description: Processor to handle reservation cancellation.
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
 * @package    Cancellation_Processor
 */
class Cancellation_Processor {

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

		$this->plugin_slug = 'cancellation_processor';
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

		$processors['cancellation_processor'] 	= array(
			"name"              =>  __("Cancellation Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Processor to handle reservation cancellation.", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"author"            =>  'Chris Hunt',											// Optional 	: Author name 
			"processor"     	=>  array( $this, 'form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false,														// Optional 	: default false : setting as true will only allow once per form
			"magic_tags"	=>	array(
				"tutor_email_output",	// String, email output for tutor.
				"tutor_email",	// String, email address for tutor.
				"tutor_name",	// String, name for tutor.
				"confirmation_message"	// String, confirmation message for form response.
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
		$calendar_id = Caldera_forms::do_magic_tags($config['calendar_id']);
		$event_ids_string = Caldera_forms::do_magic_tags($config['event_ids']);

		$event_ids = explode("|", $event_ids_string);

		// Set GCal information and require necessary files.
		$service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);

		// Get events from GCal.
		$cal_events = array_map(function($event_id) use (&$service, $calendar_id) {
			return $service->events->get($calendar_id, $event_id);
		}, $event_ids);

		// Get information for tutor email. Assumes all events will have
		// the same information for student, student email, tutor, and tutor email.
		$info_event = $cal_events[0];
		$info_event_props = $info_event->getExtendedProperties()->getPrivate();
		$tutor_email = $info_event_props['tutor_email'];
		$tutor_name = $info_event_props['tutor_name'];
		$student_name = $info_event_props['student_name'];
		$student_email = $info_event_props['student_email'];

		// Remove reservation from events.
		foreach($cal_events as $event) {
			$extended_properties = $event->getExtendedProperties();
			if ($extended_properties == null) {
				$this->echo_error("That event can't be selected.");
				return array("error-cause" => "incorrect event type");
			}
			$private_props = $extended_properties->getPrivate();
			if ($private_props['is_reserved'] == "false") {
				$this->echo_error("This time is already cancelled.");
				return array("error-cause" => "selected unreserved time");
			}
			else {
				// Get original summary/description.
				if (isset($private_props['original_summary'])) {
					$original_summary = $private_props['original_summary'];
				} else {
					$original_summary = "";
				}
				if (isset($private_props['original_description'])) {
					$original_description = $private_props['original_description'];
				} else {
					$original_description = "";
				}
				$private_props['is_reserved'] = "false";
				unset($private_props['original_summary']);
				unset($private_props['original_description']);
				unset($private_props['student_email']);
				unset($private_props['student_name']);
				$extended_properties->setPrivate($private_props);
				$event->setExtendedProperties($extended_properties);
				
				$event->setSummary($original_summary);
				$event->setDescription($original_description);
				$event->setAttendees($this->remove_attendee($student_email, $event));

				$service->events->update($calendar_id, $event->getId(), $event);
			}
		}

		// Extract information relevant to confirmation message and tutor email.
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
		$html_newline = "<br>";

		// Create tutor email.
		$tutor_email_header = "The following " . (count($event_info) > 1 ? "sessions have" : "session has") .
		" been cancelled by " . $student_name . " (" . $student_email . "):";
		
		$tutor_email_event_list = array_map(function($info) {
			$time_diff = $info['start']->diff($info['end']);
			// Calculate minutes assuming events don't last more than 24 hours.
			$minutes = $time_diff->h * 60 + $time_diff->i;
			$info_string = $info['start']->format("g:ia") . " - " .
				$info['end']->format("g:ia l, F j, Y") . " (" . $minutes .
				" minutes)";
			return $info_string;
		}, $event_info);
		$tutor_email_body = implode($newline, $tutor_email_event_list);
		$tutor_email_output = implode($newline, array(
			$tutor_email_header,
			$tutor_email_body
		));

		// Create confirmation message.
		$confirmation_message_header = "The following " . (count($event_info) > 1 ? "sessions have" : "session has") .
		" been cancelled:";
		$confirmation_message_event_list = array_map(function($info) {
			$time_diff = $info['start']->diff($info['end']);
			// Calculate minutes assuming events don't last more than 24 hours.
			$minutes = $time_diff->h * 60 + $time_diff->i;
			$info_string = $info['start']->format("g:ia") . " - " .
				$info['end']->format("g:ia l, F j, Y") . " (" . $minutes .
				" minutes) with " . $info['tutor_name'] . " (" . $info['tutor_email'] . ")";
			return $info_string;
		}, $event_info);
		$confirmation_message_body = implode($html_newline, $confirmation_message_event_list);
		$confirmation_message_output = implode($html_newline, array(
			$confirmation_message_header,
			$confirmation_message_body
		));

		// This example will return the users input and the date in the defined tags
		$return_meta = array(
			'tutor_email_output'		=>	$tutor_email_output,
			'tutor_email'		=>	$tutor_email,
			'tutor_name'		=>	$tutor_name,
			'confirmation_message' => $confirmation_message_output
		);

		return $return_meta;
	}

	private function remove_attendee($email, &$event) {
		$attendees = $event->getAttendees();
		$new_attendees = array_filter($attendees, function($attendee) {
			return $attendee->getEmail() != $email;
		});
		return $new_attendees;
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
new Cancellation_Processor();
?>
