<?php
/**
 * Plugin Name: Caldera Forms - FullCalendar.io field
 * Plugin URI:  
 * Description: Making FullCalendar.io a custom field selector
 * Version:     0.0.1
 * Author:      Becky Brown
 * Author URI:  
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


// Add field type config to caldera forms fields
// use a function name that is unique of if whithin a class, array($this, 'method_to_use')
add_filter('caldera_forms_get_field_types', 'fullcal_field_register_function');
define('FC_URL', plugin_dir_path(__FILE__));
/**
 * field type register function to add new field to registered fields array
 *
 * @param array $fields all registered field types with key as field type slug
 * @return array $fields
 */
function fullcal_field_register_function($fields){

	//be sure to give you field a unique slug. you are also able to redefine exisitng field by simply redefining it.
	// the only REQUIRED values are name, file, category, description
	$fields['fullcalendar_io'] = array(
		"field"				=>	__("FullCalendar.io", 'fullcalendar_io'),
		"file"				=>	plugin_dir_path( __FILE__ ) . 'field.php',
		"category"			=>	"Basic,Pickers", 									// comma separated list of categories to place the field in
		"description" 		=>	__('Use FullCal to select dates/times','fullcalendar_io'), 	// description explains what the field is for
		"viewer"			=>	'my_viewer_function', // array($this, 'viewer_function')		// viewer function is used to processes the stored value for display purposes. i.e if saving a saving a post ID the viewer whould get the post title to display
		"handler"			=>	'my_handler_function', // array($this, 'handler_function')		// handler function is used to processes the submitted value before storage. Like a file uploader to store the saved URL
		"setup"				=>	array(															// Setup array are config options used within the form editor
			"template"		=>	plugin_dir_path( __FILE__ ) . 'config.php',						// template is the config tempalte. the file loaded to capture field config options
			"preview"		=>	plugin_dir_path( __FILE__ ) . 'preview.php',					// the preview file is the file used for the preview of the field in the form editor
			"not_supported"	=>	array(															// the not_supported setting defines which base config options are not supported by this field
				'hide_label',	// adding hide_label removes the option to hide the lable/ used if a lable is not part of the field
			),					
			"default"		=>	array(															// the default array are the default config options when inserting a new field
				'api_key'	=>	'######',										// config options are stored as option => value,
				'cal_id'	=> 	'calendar id here'
			),
			"scripts" => array(																	// the scripts array are any javascript libraries that the field needs within the form edito
				"jquery",
				"fc-fullcal",
				"fc-gcal",
				"fc-custom-ui",
				"fc-moment-jq"																// can be a handle to a registered script or a url to the file.
			),
			"styles" => array(
				"fc-ui-css",																	// the styles array are stlye sheets that the field needs within the form editor
				"fc-fullcal-css"																				// can be a handle to a regestered style or a url to the file
			)
		),
		"scripts" => array(																		// scripts array outside of setup are scripts that are used in the frontend form
			"jquery",
			"fc-fullcal",
			"fc-gcal",
			"fc-custom-ui",
			"fc-moment-jq",																					// can be a handle to a regstered script or a url
			"fc-jquery-ui-timepicker"
		),
		"styles" => array(	
			"fc-ui-css",																	// styles array outside of setup are style sheets that are used in the frontend form
			"fc-fullcal-css",																					// can be a handle to a regstered style or a url
			"fc-jquery-ui-timepicker-css"
		)
	);

	return $fields; // be sure to return the full fields array.

}

/**
 * field type viewer function to filter the stored value into a human readable format.
 *
 * @param string|array $value the stored value of the captured entry
 * @param array $field the full field config array associated with the entry
 * @param array $form the full form config structure 
 * @return string $value the filtered version of $value
 */
function my_viewer_function($value, $field, $form){
	// do stuff to the value. like add an image url to an <img> tag etc..

	return "THIS IS VIEWER-FAKE $value.";
}


/**
 * field type handler function to handle the submitted value to be stored
 *
 * @param string|array raw submitted $value to be processed for storage
 * @param array $field the full field config array associated with the entry
 * @param array $form the full form config structure 
 * @return string|array $value the filtered version of $value to be stored
 */
function my_handler_function($value, $field, $form){
	// do stuff to the value. like save a file upload and return the stored URL
	// arrays can be returned but a viewer function will be required to convert the array to a viewable string.

	// return a WP_Error to return and trigger an erro. the error will shown to the user
	//return new WP_Error( 'error', 'Nope, Sorry. Try again.');

	//$value = "THIS IS HANDLER-FAKE $value.";
	return $value;
}

function register_fullcal_scripts () {
	wp_enqueue_style("fc-ui-css", plugins_url("js/lib/cupertino/jquery-ui.min.css", __FILE__), array(), time(), 'all');	
	wp_enqueue_style("fc-fullcal-css", plugins_url("js/fullcalendar.css", __FILE__), array(), time(), 'all');
	wp_enqueue_style("fc-jquery-ui-timepicker-css", plugins_url("lib/jquery-ui-timepicker-addon.css", __FILE__), array(), time(), 'all');
	wp_register_script("fc-custom-ui", plugins_url("js/lib/jquery-ui.min.js", __FILE__), array('jquery'), time(), false);
	wp_register_script("fc-moment-jq", plugins_url("js/lib/moment.min.js", __FILE__), array('jquery'), time(), false);
	wp_register_script("fc-fullcal", plugins_url("js/fullcalendar.min.js", __FILE__), array('jquery'), time(), false);
	wp_register_script("fc-gcal", plugins_url("js/gcal.js", __FILE__), array('jquery'), time(), false);
	wp_register_script("fc-jquery-ui-timepicker", plugins_url("lib/jquery-ui-timepicker-addon.js", __FILE__), array('jquery', 'fc-custom-ui'), time(), false);


	wp_enqueue_script('fc-custom-ui');
	wp_enqueue_script('fc-moment-jq');
	wp_enqueue_script('fc-fullcal');
	wp_enqueue_script('fc-gcal');
	wp_enqueue_script('fc-jquery-ui-timepicker');
	wp_enqueue_style('fc-fullcal-css');
}

add_action('wp_enqueue_scripts', 'register_fullcal_scripts', 12);




