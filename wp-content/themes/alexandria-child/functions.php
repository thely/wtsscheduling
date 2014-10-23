<?php

function register_wts_scripts () {
	wp_register_script("custom-ui", get_stylesheet_directory_uri() . "/js/lib/jquery-ui.custom.min.js",
		array('jquery'), time(), false);

	wp_register_script("moment-jq", get_stylesheet_directory_uri() . "/js/lib/moment.min.js",
		array('jquery'), time(), false);

	wp_register_script("fullcal", get_stylesheet_directory_uri() . "/js/fullcalendar.min.js",
		array('jquery'), time(), false);

	wp_register_script("gcal", get_stylesheet_directory_uri() . "/js/gcal.js",
		array('jquery'), time(), false);

	wp_register_style("fullcal_css", get_stylesheet_directory_uri() . "/js/fullcalendar.min.css",
		array(), time(), 'all');

	wp_enqueue_script('custom-ui');
	wp_enqueue_script('moment-jq');
	wp_enqueue_script('fullcal');
	wp_enqueue_script('gcal');
	wp_enqueue_style('fullcal_css');
}

add_action('wp_enqueue_scripts', 'register_wts_scripts');
?>
