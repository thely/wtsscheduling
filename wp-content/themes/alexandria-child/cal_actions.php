<?php 

session_start();
require_once realpath(dirname(__FILE__) . './google-api/autoload.php'); 
$client_id = '661144412361-38darqei4feaq1v1nser6uicujaq3afr.apps.googleusercontent.com'; //Client ID
$service_account_name = '661144412361-38darqei4feaq1v1nser6uicujaq3afr@developer.gserviceaccount.com'; //Email Address
$key_file_location = './google-api/examples/My Project-c73e71398c4f.p12'; //key.p12
$calendar_id = "enigma.in.my.soup@gmail.com";

$client = new Google_Client();
$client->setApplicationName("wtsscheduler");
$service = new Google_Service_Calendar($client);

	$event_id = $_GET['id'];

	if (isset($_SESSION['service_token'])) {
		$client->setAccessToken($_SESSION['service_token']);
	}
	$key = file_get_contents($key_file_location);
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

	$event = $service->events->get($calendar_id, $event_id);
	$event->setDescription("or maybe only now reserved by a student????");
	$newEvent = $service->events->update($calendar_id, $event->getId(), $event);
	$theTime = $newEvent->getStart()->getDateTime();
	echo $theTime;

?>
