<?php
/**
 * Plugin Name: Synth-Cal
 * Plugin URI: http://example.com
 * Description: Joins together FullCalendar.io and the Google Calendar API.
 * Version: 0.1
 * Author: Becky Brown
 */

function register_my_session() {
	if( !session_id() ){
 		session_start();
	}
}

add_action('init', 'register_my_session');
define(SYNTH_URL, plugin_dir_path(__FILE__));
require_once(SYNTH_URL . 'google-api/autoload.php'); 


class SynthCal {

	protected $plugin_slug;
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

		$this->plugin_slug = 'synth-cal';
		$this->version = '1.0.0';

		$this->set_locale();

		// Add filter to regester the form processor
		add_filter( 'caldera_forms_get_form_processors', array( $this, 'synthcal_processor_register') );

	}

	private function set_locale() {

		load_plugin_textdomain(
			$this->plugin_slug,
			false,
			SYNTH_URL . '/languages/'
		);

	}

	/**
	 * Register form processor by adding to the processors list
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function synthcal_processor_register( $processors ) {

		// Add our processor to the $processors array using our processor_slug as the key.
		// It is possible to replace an existing processor by redefining it and hooking in with a lower priority i.e 100

		$processors['synth-cal'] 	= array(
			"name"              =>  __("GCal Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Google Calendar API integration", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"processor"     	=>  array( $this, 'synthcal_form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
			"template"          =>  plugin_dir_path(__FILE__) . "includes/config.php",			// Optional 	: Config template for setting up the processor in form builder
			//"meta_template"		=>  plugin_dir_path(__FILE__) . "includes/meta.php",			// Optional 	: template for displaying meta data returned from processor function 
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false,														// Optional 	: default false : setting as true will only allow once per form
			"magic_tags"    	=>  array(														// Optional 	: Array of values processor returns to be used in magic tag autocomplete list
				"api_key",			// Adds {processor_slug:returned_tag} to magic tags
				"service_account",		// Adds {processor_slug:another_returned} to magic tags etc..
				"key_file_location",
				"calendar_id"
			)
			//"scripts"			=>	array(														// Optional 	: Array of WordPress script handle / urls to javascript files used in form builder
			//	'jquery',		// jquery is already included, this is just an example of a handle
			//)
			//"styles"			=>	array(														// Optional 	: Array of WordPress style handle / urls to stylesheet files used in form builder
			//	plugin_dir_url(__FILE__) . "assets/css/style.css",	// doesnt exist, but just an example of a style url
			//)
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
	public function synthcal_form_processor( $config, $form ) {

		global $transdata; // globalised transient object - can be used for passing data between processor stages ( pre -> post etc.. )
		
		/* Example $config content
		$config = array(
    		"processor_id" 		=>	'fp_87742436', 		// Auto asigned ID for the processor
    		"first_option" 		=>	'Hello %name%',		// magic tag contained string
    		"second_option" 	=>	'fld_7930752',		// direct bound field
    		"_required_bounds"	=>	array(				// array of direct bound fields - this sets front form to be "Required" automatically
            	"second_option" 						// slug of the required bound field
        	)
        );
		*/
		
		$data = $this->form_debug_information($form);

		//Google Calendar connection; $config comes from the admin config panel
		$client_id = $config['api_key'];
		$service_account_name = $config['service_account'];
		$key_file_location = $config['key_file_location'];
		$calendar_id = $config['calendar_id'];

		$client = new Google_Client();
		$client->setApplicationName("wtsscheduler");
		$service = new Google_Service_Calendar($client);

		if (isset($_SESSION['service_token'])) {
			$client->setAccessToken($_SESSION['service_token']);
		}
		$key = file_get_contents(SYNTH_URL . $key_file_location);
		$cred = new Google_Auth_AssertionCredentials(
	    	$service_account_name,
		    array('https://www.googleapis.com/auth/calendar',
	   	  		'https://www.googleapis.com/auth/calendar.readonly'),
		    $key
		);

		$client->setAssertionCredentials($cred);
		if($client->getAuth()->isAccessTokenExpired()) {
			$client->getAuth()->refreshTokenWithAssertion($cred);
		}
		$_SESSION['service_token'] = $client->getAccessToken();

		//Get more config information to get data to work with for event creation
		$event_id = Caldera_forms::do_magic_tags($config['events']);
		$event_start = Caldera_forms::do_magic_tags($config['event_start']);
		$event_end = Caldera_forms::do_magic_tags($config['event_end']);				
		$author_email = Caldera_forms::do_magic_tags($config['author_email']);
		
		//JSON information from another field
		$event_changes = Caldera_forms::do_magic_tags($config['events']);
		$events_arr = json_decode(urldecode($event_changes), true);		

		if ($events_arr['mode'] == "select") {
			$oldEvent = $service->events->get($calendar_id, $events_arr['id']);
			$extProps2 = $oldEvent->getExtendedProperties();
			$extProps = $extProps2->getPrivate();
			if ($extProps['is_reserved'] == "true") {
				echo "This time is already reserved!";
				die;
			}
			else {
				$extProps['is_reserved'] = "true";
				$oldSummary = $oldEvent->getSummary();
				$oldDescription = $oldEvent->getDescription();
				
				$oldEvent->setSummary("[RESERVED], " . $oldSummary);
				$oldEvent->setDescription($oldDescription . "Reserved by $author_email.");
				$extProps2->setPrivate(array_merge(array("student_email" => $author_email), $extProps));
				$oldEvent->setExtendedProperties($extProps2);

				$student = new Google_Service_Calendar_EventAttendee();
				$student->setEmail($data['email_address']);
				$attendees = array_merge(array($student), $oldEvent->getAttendees());
				$oldEvent->setAttendees($attendees);
				//$oldEvent->setAttendees($this->add_attendee($author_email, $oldEvent));

				$newEvent = $service->events->update($calendar_id, $oldEvent->getId(), $oldEvent);
			}
		}
		else if ($events_arr['mode'] == "edit") {
			foreach($events_arr['events'] as $event) {
				if ($event['mode'] == "insert") {
					$newEvent = new Google_Service_Calendar_Event();
					$newEvent->setSummary("**V2** set by $author_email");
					$newEvent->setDescription("Tutor email: $author_email, tutor name: {$data['name']}.");
					$newEvent->setStart($this->make_time($event['start']));
					$newEvent->setEnd($this->make_time($event['end']));
					$newEvent->setAttendees($this->add_attendee($author_email, null));

					$extendedProperties = new Google_Service_Calendar_EventExtendedProperties();
					$extendedProperties->setPrivate(array (
						"tutor_email" 		=> $author_email,
						"tutor_name" 		=> $data['name'],
						"is_group" 			=> "false",
						"is_reserved"		=> "false"
					));
					$extendedProperties->setShared(array());
					$newEvent->setExtendedProperties($extendedProperties);

					$createdEvent = $service->events->insert($calendar_id, $newEvent);
				}
				else if ($event['mode'] == "update") {
					$oldEvent = $service->events->get($calendar_id, $event['id']);
					$oldEvent->setStart($this->make_time($event['start']));
					$oldEvent->setEnd($this->make_time($event['end']));

					$newEvent = $service->events->update($calendar_id, $oldEvent->getId(), $oldEvent);
				}
			}
		}
	
		//$results = $service->events->listEvents($calendar_id);

	//If an event_id is provided, we're getting/updating an event

/*		if (!empty($event_id)) {
			$event = $service->events->get($calendar_id, $event_id);
			$extProps2 = $event->getExtendedProperties();
			$extProps = $extProps2->getPrivate();
			if ($extProps['is_reserved'] == "true") {
				echo "This time is already reserved!";
				die;
			}
			else {
				$extProps['is_reserved'] = "true";
				$old_title = $event->getSummary();
				$event->setSummary("[RESERVED], $old_title");
				$old_desc = $event->getDescription();
				$event->setDescription("$old_desc Reserved by {$data['email_address']}.");
				$extProps2->setPrivate(array_merge(array("student_email" => $data['email_address']), $extProps));
				$event->setExtendedProperties($extProps2);

				$student = new Google_Service_Calendar_EventAttendee();
				$student->setEmail($data['email_address']);
				$attendees = array_merge(array($student), $event->getAttendees());
				$event->setAttendees($attendees);

				$newEvent = $service->events->update($calendar_id, $event->getId(), $event);
			}
			$theTime = $event->getStart()->getDateTime();

		}
	//If no event_id is provided, we're making a new event
		else {
			$timezone = "America/New_York";

			$event = new Google_Service_Calendar_Event();
			$event->setSummary("set by $author_email");
			$event->setDescription("Tutor email: $author_email, tutor name: {$data['name']} at the {$data['which_center']} Center.");

			$start = new Google_Service_Calendar_EventDateTime();
			$start->setDateTime($event_start);
			$start->setTimeZone($timezone);
			$event->setStart($start);

			$end = new Google_Service_Calendar_EventDateTime();
			$end->setDateTime($event_end);
			$end->setTimeZone($timezone);
			$event->setEnd($end);

			$tutor = new Google_Service_Calendar_EventAttendee();
			$tutor->setEmail($author_email);
			$attendees = array($tutor);
			$event->setAttendees($attendees);

			$extendedProperties = new Google_Service_Calendar_EventExtendedProperties();
			$extendedProperties->setPrivate(array (
				"tutor_email" 		=> $author_email,
				"tutor_name" 		=> $data['name'],
				"tutor_center" 		=> $data['which_center'],
				"tutor_subjects"	=> "BIOL,MUTH",
				"is_group" 			=> "false",
				"is_reserved"		=> "false"
			));
			$extendedProperties->setShared(array());
			$event->setExtendedProperties($extendedProperties);

			$createdEvent = $service->events->insert($calendar_id, $event);
			$theTime = $createdEvent->getStart()->getDateTime();
			$displayProps = $createdEvent->getExtendedProperties()->getPrivate();
			echo $displayProps;
		}
*/		

		// $data should contain slug:value
		// Heres an output to show on screen.
		/*echo '<pre>';
	 
		echo "\r\nClean Data\r\n";
		print_r( $data );

		/*foreach ($results as $item) {
  			echo $item['summary'], ", [", $item['id'], "], ", $item['description'], "<br /> \n";
		}

		echo '</pre>';*/
		// This example will return the users input and the date in the defined tags

		$return_meta = array(
			'first_test' => 'some_value'
		);

		return $return_meta;
	}

	private function make_time($time){
		$newTime = new Google_Service_Calendar_EventDateTime();
		$newTime->setDateTime($time);
		$newTime->setTimeZone("America/New_York");
		return $newTime;
	}

	private function add_attendee($email, $event) {
		$guest = new Google_Service_Calendar_EventAttendee();
		$guest->setEmail($email);
		if (isset($event)) {
			return array_merge(array($event), $event->getAttendees());			
		}
		else {
			return array($guest);
		}
	}

	private function form_debug_information($form) {
		$data = array(); // build a data array of submitted data
		$raw_data = Caldera_Forms::get_submission_data( $form ); // Raw data is an array with field_id as the key

		foreach( $raw_data as $field_id => $field_value ){ // create a new array using the slug as the key
			if( in_array( $form[ 'fields' ][ $field_id ][ 'type' ], array( 'button', 'html' ) ) )
				continue; //ignores buttons

			$data[ $form[ 'fields' ][ $field_id ][ 'slug' ] ] = $field_value; // get the field slug for the key instead
		}
		return $data;
	}
}

// Create the instance. (can be done however you like)
new SynthCal();
?>
