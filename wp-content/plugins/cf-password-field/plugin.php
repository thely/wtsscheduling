<?php
/**
 * Plugin Name: Caldera Forms - Password Field
 * Author:      Chris Hunt
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Add field type config to caldera forms fields
// use a function name that is unique of if whithin a class, array($this, 'method_to_use')
add_filter('caldera_forms_get_field_types', 'my_custom_field_register_function');

/**
 * field type register function to add new field to registered fields array
 *
 * @param array $fields all registered field types with key as field type slug
 * @return array $fields
 */
function my_custom_field_register_function($fields){

	//be sure to give you field a unique slug. you are also able to redefine exisitng field by simply redefining it.
	// the only REQUIRED values are name, file, category, description
	$fields['password_field'] = array(
		"field"				=>	"Password Field",
		"file"				=>	plugin_dir_path( __FILE__ ) . 'field.php',
		"category"			=>	"Text Fields, Basic", 									// comma separated list of categories to place the field in
		"description" 		=>	'Password Field', 	// description explains what the field is for
		"setup"				=>	array(															// Setup array are config options used within the form editor
			"template"		=>	plugin_dir_path( __FILE__ ) . 'config.php',						// template is the config tempalte. the file loaded to capture field config options
			"preview"		=>	plugin_dir_path( __FILE__ ) . 'preview.php'					// the preview file is the file used for the preview of the field in the form editor
		),
		"scripts" => array(																		// scripts array outside of setup are scripts that are used in the frontend form
			"jquery"																						// can be a handle to a regstered script or a url
		)
	);

	return $fields; // be sure to return the full fields array.
}
