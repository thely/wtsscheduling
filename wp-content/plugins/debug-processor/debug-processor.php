<?php
/**
 * Plugin Name: CF Debug Processor
 * Plugin URI: http://example.com
 * Description: Just for getting debug text out of the processors!
 * Version: 0.1
 * Author: Becky Brown
 */


class DebugProcessor {

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

		$this->plugin_slug = 'debug-processor';
		$this->version = '1.0.0';

		$this->set_locale();

		// Add filter to regester the form processor
		add_filter( 'caldera_forms_get_form_processors', array( $this, 'debug_processor_register') );

	}

	private function set_locale() {

		load_plugin_textdomain(
			$this->plugin_slug,
			false,
			plugin_dir_path(__FILE__) . '/languages/'
		);

	}

	/**
	 * Register form processor by adding to the processors list
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function debug_processor_register( $processors ) {

		// Add our processor to the $processors array using our processor_slug as the key.
		// It is possible to replace an existing processor by redefining it and hooking in with a lower priority i.e 100

		$processors['debug-processor'] 	= array(
			"name"              =>  __("Debug Processor", $this->plugin_slug),					// Required	 	: Processor name
			"description"       =>  __("Spits out data for debugging purposes", $this->plugin_slug),			// Required 	: Processor description
			"icon"				=>	plugin_dir_url(__FILE__) . "assets/icon.png",				// Optional 	: Icon / Logo displayed in processors picker modal
			"processor"     	=>  array( $this, 'debug_form_processor' ),							// Optional 	: Processor function used to handle data, cannot stop processing. Returned data saved as entry meta
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
	public function debug_form_processor( $config, $form ) {

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
		
		$data = array(); // build a data array of submitted data
		$raw_data = Caldera_Forms::get_submission_data( $form ); // Raw data is an array with field_id as the key

		foreach( $raw_data as $field_id => $field_value ){ // create a new array using the slug as the key
			if( in_array( $field_id, array( '_entry_id', '_entry_token' ) ) )
				continue; // Ignores irrelevant debug fields.
			if( in_array( $form[ 'fields' ][ $field_id ][ 'type' ], array( 'button', 'html' ) ) )
				continue; //ignores buttons

			$data[ $form[ 'fields' ][ $field_id ][ 'slug' ] ] = urldecode($field_value);
		}

		// $data should contain slug:value
		// Heres an output to show on screen.
		echo '<pre>';
		//echo "Raw Data\r\n";
		//print_r( $raw_data );
	 
		echo "\r\nClean Data\r\n";
		print_r( $data );
		
		/*foreach ($results as $item) {
  			echo $item['summary'], ", [", $item['id'], "], ", $item['description'], "<br /> \n";
		}*/

		echo '</pre>';
		die;
		// This example will return the users input and the date in the defined tags

		/*$return_meta = array(
			'api_key'		=>	Caldera_Forms::do_magic_tags( $config['api_key'] ),
			'current_date'	=>	date('Y-m-d H:i:s')
		);

		return $return_meta;*/
	}
}

// Create the instance. (can be done however you like)
new DebugProcessor();
?>
