<?php
/**
 * Plugin Name: Caldera Forms - Pods Data Field
 * Plugin URI:  
 * Description: Filter a pod by any other pod
 * Version:     0.0.1
 * Author:      Becky Brown
 * Author URI:  
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_filter('caldera_forms_get_field_types', 'cf_pods_data_field_register_function');

/**
 * Add field to fields array.
 */
function cf_pods_data_field_register_function($fields) {

	$fields['cf-pod-data-field'] = array(
		"field" => "Pod Data Field",
		"file" => plugin_dir_path( __FILE__ ) . 'field.php',
		"category" => "Basic,Special",
		"description" => 'Sets value of field equal to extracted field from matched pods.',
		"setup" => array(
			"template" => plugin_dir_path( __FILE__ ) . 'config.php'
		)
	);

	return $fields;
}
