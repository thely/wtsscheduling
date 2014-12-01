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

define('SYNTH_URL', plugin_dir_path(__FILE__));
define('GOOGLE_API_URL', SYNTH_URL . 'google-api/autoload.php');
require_once(GOOGLE_API_URL); 


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
		add_filter('caldera_forms_get_form_processors', array($this, 'synthcal_processor_register'));
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
			"conditionals"		=>	true,														// Optional 	: default true  : setting false will disable conditionals for the processor (use always)
			"single"			=>	false,														// Optional 	: default false : setting as true will only allow once per form
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

		// Google Calendar connection information, populated by
		// processor configuration set in form.
		$service_account_name = $config['service_account'];
		$encoded_key_file_contents = substr($config['key_file_contents'],
			strpos($config['key_file_contents'], ","));
		$key_file_contents = base64_decode($encoded_key_file_contents);
		//Briefer service config - mostly in a function now		
		$client = new Google_Client();
		$client->setApplicationName("wtsscheduler");
		$service = $this->start_service($client, $service_account_name, $key_file_contents);

		// Make service available for other processors.
		$transdata['gcal_service'] = $service;
		$transdata['gcal_require'] = GOOGLE_API_URL;

		$return_meta = array( "thing" => "other_thing");
		return $return_meta;
	}

	private function start_service(&$client, $service_account_name, $key_file_contents) {
		$service = new Google_Service_Calendar($client);

		if (isset($_SESSION['service_token'])) {
			$client->setAccessToken($_SESSION['service_token']);
		}
		$cred = new Google_Auth_AssertionCredentials(
	    	$service_account_name,
		    array('https://www.googleapis.com/auth/calendar',
	   	  		'https://www.googleapis.com/auth/calendar.readonly'),
		    $key_file_contents
		);

		$client->setAssertionCredentials($cred);
		if($client->getAuth()->isAccessTokenExpired()) {
			$client->getAuth()->refreshTokenWithAssertion($cred);
		}
		$_SESSION['service_token'] = $client->getAccessToken();
		return $service;
	}
}

// Create the instance. (can be done however you like)
new SynthCal();
?>
