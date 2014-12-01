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
				"tutor_email_output", // String, text to use in email confirmation to tutor
				"tutor_email",	// String, email for tutor related to reserved event
				"tutor_name",	// String, name of tutor related to reserved event
				"confirmation_message"	// String, confirmation message to be displayed.
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
		$student_email = Caldera_forms::do_magic_tags($config['student_email']);
		$student_name = Caldera_forms::do_magic_tags($config['student_name']);
		$encoded_event_info = Caldera_forms::do_magic_tags($config['event_details']);
		$site_url = Caldera_forms::do_magic_tags($config['site_url']);
		$cancellation_path = Caldera_forms::do_magic_tags($config['cancellation_path']);

		// Get JSON information from calendar field.
		$selected_event_details = json_decode(urldecode($encoded_event_info), true);
		$calendar_id = $selected_event_details['calendar_id'];
		$event_mode = $selected_event_details['mode'];

		$selected_events = $selected_event_details['events'];
		$event_ids = array_map(function($event_info) {
			return $event_info['id'];
		}, array_values($selected_events));

		// Set GCal information and require necessary files.
		$service = $transdata['gcal_service'];
		require_once($transdata['gcal_require']);

		// Get events from GCal.
		$cal_events = array_map(function($event_id) use (&$service, $calendar_id) {
			return $service->events->get($calendar_id, $event_id);
		}, $event_ids);

		// Set events as reserved.
		foreach($cal_events as $event) { 
			$extended_properties = $event->getExtendedProperties();
			if ($extended_properties == null) {
				$this->echo_error("That event can't be selected.");
				return array("error-cause" => "incorrect event type");
			}
			$private_props = $extended_properties->getPrivate();
			if ($private_props['is_reserved'] == "true") {
				$this->echo_error("This time is already reserved.");
				return array("error-cause" => "selected reserved time");
			}
			else {
				$private_props['is_reserved'] = "true";
				$oldSummary = $event->getSummary();
				$oldDescription = $event->getDescription();
				$private_props['original_summary'] = $oldSummary;
				$private_props['original_description'] = $oldDescription;
				$private_props['student_email'] = $student_email;
				$private_props['student_name'] = $student_name;
				
				$event->setSummary("[RESERVED], " . $oldSummary);
				$event->setDescription($oldDescription . "Reserved by $student_email.");
				$extended_properties->setPrivate(array_merge(array("student_email" => $student_email), $private_props));
				$event->setExtendedProperties($extended_properties);
				$event->setAttendees($this->add_attendee($student_email, $event));

				$newEvent = $service->events->update($calendar_id, $event->getId(), $event);
			}
		}

		/* EMAIL AND CONFIRMATION TEXT FORMATTING */

		$event_info = array_map(function($event) {
			$info = array();
			$private_props = $event->getExtendedProperties()->getPrivate();
			$info['tutor_name'] = $private_props['tutor_name'];
			$info['tutor_email'] = $private_props['tutor_email'];
			$info['start'] = new DateTime($event->getStart()->getDateTime());
			$info['end'] = new DateTime($event->getEnd()->getDateTime());
			return $info;
		}, $cal_events);
		
		$info_event_props = $event_info[0];
		// Assumes the same tutor and tutor email for all events.
		$tutor_email = $info_event_props['tutor_email'];//
		$tutor_name = $info_event_props['tutor_name'];
		
		$newline = "\r\n";
		$html_newline = "<br>";

		// Generate student email text.
		$student_email_header = "The following " .
			(count($event_info) > 1 ? "sessions have" : "session has") .
			" been confirmed:";
		$student_email_event_list = array_map(function($info) {
			$time_diff = $info['start']->diff($info['end']);
			// Calculate minutes assuming events don't last more than 24 hours.
			$minutes = $time_diff->h * 60 + $time_diff->i;
			$info_string = $info['start']->format("g:ia") . " - " .
				$info['end']->format("g:ia l, F j, Y") . " (" . $minutes .
				" minutes) with " . $info['tutor_name'] . " (" .
				$info['tutor_email'] . ")";
			return $info_string;
		}, $event_info);
		$student_email_body = implode($newline, $student_email_event_list);
		// Set cancellation link.
		$event_ids_string = implode("|", $event_ids);
		$cancellation_link = $site_url . "/" . $cancellation_path .
			"/?event=" . $event_ids_string . "&calendar=" . $calendar_id;
		$student_email_footer = "To cancel this reservation, visit:" .
			$newline . $cancellation_link;
		$student_email_output = implode($newline, array(
			$student_email_header,
			$student_email_body,
			$student_email_footer
		));

		// Generate tutor email text.
		$tutor_email_header = "The following " .
			(count($event_info) > 1 ? "sessions have" : "session has") .
			" been reserved by " . $student_name . " (" . $student_email . "):";
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
		$confirmation_message_header = "The following " .
			(count($event_info) > 1 ? "sessions have" : "session has") .
			" been reserved:";
		$confirmation_message_body = implode($html_newline, $student_email_event_list);
		$confirmation_message_footer = "You may cancel your reservation here: " .
			$html_newline . "<a href=\"" . $cancellation_link . "\">" .
			$cancellation_link . "</a>" . $html_newline .
			"You should be receiving an email shortly at " . $student_email .
			" with this information.";
		$confirmation_message_output = implode($html_newline, array(
			$confirmation_message_header,
			$confirmation_message_body,
			$confirmation_message_footer
		));

		// This example will return the users input and the date in the defined tags
		$return_meta = array(
			'student_email_output'		=>	$student_email_output,
			'tutor_email_output'		=>	$tutor_email_output,
			'tutor_email'		=>	$tutor_email,
			'tutor_name'		=>	$tutor_name,
			'confirmation_message'	=>	$confirmation_message_output
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
