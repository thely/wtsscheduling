<?php
/**
 * Plugin Name: CF Filterable Pods Field
 * Plugin URI:  
 * Description: Filter a pod by any other pod
 * Version:     0.0.1
 * Author:      Becky Brown
 * Author URI:  
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


// Add field type config to caldera forms fields
// use a function name that is unique of if whithin a class, array($this, 'method_to_use')
add_filter('caldera_forms_get_field_types', 'cf_pods_filterable_register_function');

/**
 * field type register function to add new field to registered fields array
 *
 * @param array $fields all registered field types with key as field type slug
 * @return array $fields
 */
function cf_pods_filterable_register_function($fields){

	//be sure to give you field a unique slug. you are also able to redefine exisitng field by simply redefining it.
	// the only REQUIRED values are name, file, category, description
	$fields['cf-pods-filterable'] = array(
		"field"				=>	__("Filterable Pods Field", 'cf-pods-filterable'),
		"file"				=>	plugin_dir_path( __FILE__ ) . 'field.php',
		"category"			=>	"Basic,Pickers,Special", 									// comma separated list of categories to place the field in
		"description" 		=>	__('This is a custom field for my needs','cf-pods-filterable'), 	// description explains what the field is for
		/*"viewer"			=>	'my_viewer_function_x', // array($this, 'viewer_function')		// viewer function is used to processes the stored value for display purposes. i.e if saving a saving a post ID the viewer whould get the post title to display
		"handler"			=>	'my_handler_function_x', // array($this, 'handler_function')*/		// handler function is used to processes the submitted value before storage. Like a file uploader to store the saved URL
		"setup"				=>	array(															// Setup array are config options used within the form editor
			"template"		=>	plugin_dir_path( __FILE__ ) . 'config.php',						// template is the config tempalte. the file loaded to capture field config options
			//"preview"		=>	plugin_dir_path( __FILE__ ) . 'preview.php',					// the preview file is the file used for the preview of the field in the form editor
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
function my_viewer_function_x($value, $field ,$form){
	// do stuff to the value. like add an image url to an <img> tag etc..
	return $value;
}


/**
 * field type handler function to handle the submitted value to be stored
 *
 * @param string|array raw submitted $value to be processed for storage
 * @param array $field the full field config array associated with the entry
 * @param array $form the full form config structure 
 * @return string|array $value the filtered version of $value to be stored
 */
function my_handler_function_x($value, $field ,$form){
	// do stuff to the value. like save a file upload and return the stored URL
	// arrays can be returned but a viewer function will be required to convert the array to a viewable string.

	// return a WP_Error to return and trigger an erro. the error will shown to the user
	return new WP_Error( 'error', 'Nope, Sorry. Try again.');

	return $value;
}







