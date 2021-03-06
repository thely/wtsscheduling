<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Admin Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 * 
 */


class WPBackitup_Admin {

    public $namespace = WPBACKITUP__NAMESPACE;
    public $friendly_name = WPBACKITUP__FRIENDLY_NAME;
    public $version = WPBACKITUP__VERSION;

    private static $instance = false;

    //Use Getters
    private $license_key;//Loaded in getter
    private $license_type; //Loaded in getter
    private $license_expires;
    
    private $license_active;//Loaded in getter
    private $license_status;//Loaded in getter   
    private $license_status_message;//Loaded in getter   
    private $license_type_description; //Getter will load

    private $backup_retained_number; //Getter will load
    private $notification_email;//Getter will load
    private $logging;//Getter will load

    private $backup_count; //getter will load
    private $successful_backup_count;

	public $backup_type;

    
    // Default plugin options
    public $defaults = array(
        'logging' => false,
        'license_key' => "lite",
        'license_last_check_date'=> "1970-01-01 00:00:00",
        'license_status' => "",
        'license_status_message'=> "",
        'license_type' => "0",
        'license_expires'=> "1970-01-01 00:00:00",
        'license_limit'=> "1",
        'license_sitecount'=> "",
        'notification_email' => "",
        'backup_retained_number' => "3",
        'lite_backup_retained_number' => "1",
        'lite_registration_email' => "",
        'lite_registration_first_name' => "",
        'backup_count'=>0,
        'successful_backup_count'=>0,
        'stats_last_check_date'=> "1970-01-01 00:00:00",
        'backup_schedule'=>"",
        'backup_lastrun_date'=>"-2147483648",
        'cleanup_lastrun_date'=>"-2147483648",
	    'backup_batch_size'=>"10",
    );


     /**
     * Retrieve the current WP backItUp instance.
     */
    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Instantiation construction
     * 
     */
    private function __construct() {
        /**
         * Make this plugin available for translation.
         * Translations can be added to the /languages/ directory.
         */

        //TODO: Add multi Language Support back
        //load_theme_textdomain( $this->namespace, WPBACKITUP__PLUGIN_DIR . '/languages' );

        // Add all action, filter and shortcode hooks
        $this->_add_hooks();
    }

    /**
     * Add in various hooks
     */
    private function _add_hooks() {

        // Options page for configuration
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

        // Route requests for form processing
        add_action( 'admin_init', array( &$this, 'route' ) );
        
        // Add a settings link next to the "Deactivate" link on the plugin listing page
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
        
        //Load all the resources
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_resources' ) );

        //Load the backup action
        add_action('wp_ajax_wp-backitup_backup', array( &$this, 'ajax_queue_backup' ));

        //Load the restore action
        add_action('wp_ajax_wp-backitup_restore', array( &$this, 'ajax_restore' ));

        //Load the upload action
        add_action('wp_ajax_wp-backitup_upload', array( &$this, 'ajax_upload' ));

	    //Status reader for UI
	    add_action('wp_ajax_wp-backitup_restore_status_reader', array( &$this,'ajax_get_restore_status'));

	    add_action('wp_ajax_wp-backitup_backup_status_reader', array( &$this,'ajax_get_backup_status'));

        add_action('wp_ajax_wp-backitup_backup_response_reader', array( &$this,'ajax_backup_response_reader'));

        //Delete File Action
        add_action('wp_ajax_wp-backitup_delete_file', array( &$this,'ajax_delete_file'));

        //View Log Action
        add_action('admin_post_viewlog', array( &$this,'admin_viewlog'));

	    //Download Backup
	    add_action('admin_post_download_backup', array( &$this,'admin_download_backup'));

        //List Logs Action
        add_action('admin_post_nopriv_listlogs', array( &$this,'admin_listlogs'));

        //Create Daily backup action
        add_action( 'wpbackitup_queue_scheduled_jobs',  array( &$this,'wpbackitup_queue_scheduled_jobs'));

        add_action( 'wpbackitup_run_backup_tasks',  array( &$this,'wpbackitup_run_backup_tasks'));

	    add_action( 'wpbackitup_run_cleanup_tasks',  array( &$this,'wpbackitup_run_cleanup_tasks'));

    }

    /**
     * 
     * Define the admin menu options for this plugin
     * 
     */
    public  function admin_menu() {
      
       // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        add_menu_page( $this->friendly_name, $this->friendly_name, 'administrator', $this->namespace, array( &$this, 'admin_backup_page' ), WPBACKITUP__PLUGIN_URL .'/images/icon.png', 77);

        //Add Backup Menu Nav
        add_submenu_page( $this->namespace, 'Backup', 'Backup', 'administrator', $this->namespace.'-backup', array( &$this, 'admin_backup_page' ) );
        
        //Add Restore Menu Nav IF licensed
        if ($this->license_type()!=0) {
            add_submenu_page( $this->namespace, 'Restore', 'Restore', 'administrator', $this->namespace.'-restore', array( &$this, 'admin_restore_page' ) );
        }

        //Add Settings Menu Nav
        add_submenu_page( $this->namespace, 'Settings', 'Settings', 'administrator', $this->namespace.'-settings', array( &$this, 'admin_settings_page' ) );

        if (WPBACKITUP__DEBUG===true){
            add_submenu_page( $this->namespace, 'Test', 'Test', 'administrator', $this->namespace.'-test', array( &$this, 'admin_test_page' ) );
        }
        // remove duplicate submenu page. wp limitations // 
        // http://wordpress.stackexchange.com/questions/16401/remove-duplicate-main-submenu-in-admin
        remove_submenu_page($this->namespace,$this->namespace); 

    }

    public  function load_resources() {

	    //Only load the JS and CSS when plugin is active
	    if( !empty($_REQUEST['page']) && substr($_REQUEST['page'], 0, 11) === 'wp-backitup') {

		    // Admin JavaScript
		    wp_register_script( "{$this->namespace}-admin", WPBACKITUP__PLUGIN_URL . "js/wpbackitup_admin.js", array( 'jquery' ), $this->version, true );
		    wp_enqueue_script( "{$this->namespace}-admin" );

		    // Admin Stylesheet
		    wp_register_style( "{$this->namespace}-admin", WPBACKITUP__PLUGIN_URL . "css/wpbackitup_admin.css", array(), $this->version, 'screen' );
		    wp_enqueue_style( "{$this->namespace}-admin" );

			//Admin fonts
		    wp_register_style( 'google-fonts', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
		    wp_enqueue_style( 'google-fonts' );
	    }
    }

    /**
     * The admin section backup page rendering method
     * 
     */
    public  function admin_backup_page()
    {
      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page' );
      }   

      include WPBACKITUP__PLUGIN_PATH . "/views/backup.php";
    }

    /**
     * The admin section restore page rendering method
     * 
     */
    public  function admin_restore_page()
    {
      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page.' );
      }   

      include WPBACKITUP__PLUGIN_PATH . "/views/restore.php";
    }

    /**
     * The admin section settings page rendering method
     * 
     */
    public  function admin_settings_page()
    {

      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page.' );
      }

      include WPBACKITUP__PLUGIN_PATH . "/views/settings.php";
    }

    /**
     * The admin section backup page rendering method
     *
     */
    public  function admin_test_page()
    {
        if( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page' );
        }

        include WPBACKITUP__PLUGIN_PATH . "/views/test.php";
    }
  
     /**
     * Route the user based off of environment conditions
     * 
     * @uses WPBackitup::_admin_options_update()
     */
    public  function route() {
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http';
        $hostname = $_SERVER['HTTP_HOST'];
        $url = "{$protocol}://{$hostname}{$uri}";
        $is_post = (bool) ( strtoupper( $_SERVER['REQUEST_METHOD'] ) == "POST" );

        // Check if a nonce was passed in the request
        if( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];

            $logger = new WPBackItUp_Logger(false);
            //$logger->log('NONCE:' .$nonce);

            // Handle POST requests
            if( $is_post ) {

                if( wp_verify_nonce( $nonce, "{$this->namespace}-update-options" ) ) {
                    $logger->log('Update Options Form Post');
                    $this->_admin_options_update();
                }

                if( wp_verify_nonce( $nonce, "{$this->namespace}-register-lite" ) ) {
                    $logger->log('Register Lite Form Post');
                    $this->_admin_register_lite();
                }

                if( wp_verify_nonce( $nonce, "{$this->namespace}-update-schedule" ) ) {
                    $logger->log('Update Schedule Form Post');

                    $jsonResponse = new stdClass();
                    if ($this->_admin_save_schedule()){
                        $jsonResponse->message = 'success';
                    }else{
                        $jsonResponse->message = 'error';
                    }

                    exit(json_encode($jsonResponse));

                }

            } 
            // Handle GET requests
            else {

            }
        }
    }

    public function initialize(){
        $this->check_license();
    }

	public function wpbackitup_queue_scheduled_jobs(){

		// Check permissions
		if (! self::is_authorized()) exit('Access denied.');

		//Include Scheduler Class
		if( !class_exists( 'WPBackItUp_Scheduler' ) ) {
			include_once 'class-scheduler.php';
		}

		//Include Job class
		if( !class_exists( 'WPBackItUp_Job' ) ) {
			include_once 'class-job.php';
		}

		//If no backup or cleanup queued already then queue one
		if (!WPBackItUp_Job::is_job_queued('backup')) {

			//Is it time for a backup?
			//Check scheduler and queue tasks that need to be run
			$scheduler = new WPBackItUp_Scheduler();
			if ( $scheduler->isTaskScheduled( 'backup' ) && !WPBackItUp_Job::is_job_queued('cleanup')) {
				$backup_job = WPBackItUp_Job::queue_job( 'backup' );

				//Setup the job run event
				wp_schedule_single_event( time(), 'wpbackitup_run_backup_tasks' );

				exit( 0 ); //success - don't schedule anything else
			}
		} else {
			//backup is queued are tasks scheduled
			if (false===wp_next_scheduled('wpbackitup_run_backup_tasks')){
				wp_schedule_single_event( time(), 'wpbackitup_run_backup_tasks' );
			}
		}

		//If no backup or cleanup queued already then queue one
		if (!WPBackItUp_Job::is_job_queued('cleanup')) {
			//Is it time for a cleanup
			//Check scheduler and queue tasks that need to be run
			$scheduler = new WPBackItUp_Scheduler();
			if ( $scheduler->isTaskScheduled( 'cleanup' ) && !WPBackItUp_Job::is_job_queued('backup')  ) {
				$cleanup_job = WPBackItUp_Job::queue_job( 'cleanup' );

				//Setup the job run event
				wp_schedule_single_event( time(), 'wpbackitup_run_cleanup_tasks' );

				exit( 0 ); //success - don't schedule anything else
			}
		} else{
			//cleanup is queued are tasks scheduled
			if (false===wp_next_scheduled('wpbackitup_run_cleanup_tasks')){
				wp_schedule_single_event( time(), 'wpbackitup_run_cleanup_tasks' );
			}

		}

		exit(0); //success nothing to schedule
	}

	//Run queue manual backup
	public  function ajax_queue_backup() {
		// Check permissions
		if (! self::is_authorized()) exit('Access denied.');

		$logger = new WPBackItUp_Logger(false,null,'debug_events');
		$logger->log_info(__METHOD__,'Begin');

		//Include Job class
		if( !class_exists( 'WPBackItUp_Job' ) ) {
			include_once 'class-job.php';
		}

		$rtnData = new stdClass();
		//If no backup queued already then queue one
		if (!WPBackItUp_Job::is_job_queued('backup')){
			if (WPBackItUp_Job::queue_job('backup')){
				$rtnData->message = 'Backup Queued';
			}else {
				$rtnData->message = 'Backup could not be queued';
			}
		}else{
			$rtnData->message = 'Backup already in queue';
		}

		$logger->log_info(__METHOD__,$rtnData->message);
		$logger->log_info(__METHOD__,'End');
		echo json_encode($rtnData);
		exit;
	}

	//Run scheduled backup tasks
    function wpbackitup_run_backup_tasks(){

	    // Check permissions
	    if (! self::is_authorized()) exit('Access denied.');

	    $process_id = uniqid();

	    $event_logger = new WPBackItUp_Logger(false,null,'debug_events');
	    $event_logger->log_info(__METHOD__ .'(' .$process_id .')', 'Begin');

	    //Try Run Next Backup Tasks
	    $event_logger->log_info(__METHOD__.'(' .$process_id .')','Try Run Backup Task');

	    $this->backup_type='scheduled';
	    include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/job_backup.php' );

	    $event_logger->log_info(__METHOD__.'(' .$process_id .')','End Try Run Backup Task');

        exit(0);
    }

	//Run scheduled backup tasks
	function wpbackitup_run_cleanup_tasks(){
		// Check permissions
		if (! self::is_authorized()) exit('Access denied.');

		$process_id = uniqid();

		$event_logger = new WPBackItUp_Logger(false,null,'debug_events');
		$event_logger->log_info(__METHOD__ .'(' .$process_id .')', 'Begin');

		//Try Run Next Backup Tasks
		$event_logger->log_info(__METHOD__.'(' .$process_id .')','Try Run cleanup Task');

		$this->backup_type='scheduled';
		include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/job_cleanup.php' );

		$event_logger->log_info(__METHOD__.'(' .$process_id .')','End Try Run cleanup Task');

		exit;
	}


	public  function ajax_get_restore_status() {
		// Check permissions
		if (! self::is_authorized()) exit('Access denied.');

		$log = WPBACKITUP__PLUGIN_PATH .'/logs/restore_status.log';
		if(file_exists($log) ) {
			readfile($log);
		}
		exit;
	}


	/**
	 * Return the backup status and try run tasks
	 */
	public  function ajax_get_backup_status() {
		// Check permissions
		if (! self::is_authorized()) exit('Access denied.');

		$event_logger = new WPBackItUp_Logger(false,null,'debug_events');

		$event_logger->log_info(__METHOD__ ,'User Permissions: ' .current_user_can( 'manage_options' ));

		//Check permissions
		if ( current_user_can( 'manage_options' ) ) {
			//echo('RUNNING BACKUP');

			$process_id = uniqid();


			$event_logger->log_info(__METHOD__ .'(' .$process_id .')', 'Begin');

			//Try Run Next Backup Tasks
			$event_logger->log_info(__METHOD__.'(' .$process_id .')','Try Run Backup Task');

			$this->backup_type='manual';
			include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/job_backup.php' );

			$event_logger->log_info(__METHOD__.'(' .$process_id .')','End Try Run Backup Task');

			//return status
			$log = WPBACKITUP__PLUGIN_PATH .'/logs/backup_status.log';
			if(file_exists($log) ) {
				//Probably should use the database instead now.
				readfile($log);
				$event_logger->log_info(__METHOD__.'(' .$process_id .')','Status sent to browser.');
			}
		}

		exit;
	}

    //load restore
    public  function ajax_restore() {
	    // Check permissions
	    if (! self::is_authorized()) exit('Access denied.');

        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/job_restore.php' );
    }

    //load upload
    public  function ajax_upload() {
	    // Check permissions
	    if (! self::is_authorized()) exit('Access denied.');

        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/upload.php' );
    }



    public  function ajax_backup_response_reader() {
	    // Check permissions
	    if (! self::is_authorized()) exit('Access denied.');

        $log = WPBACKITUP__PLUGIN_PATH .'/logs/backup_response.log';
        if(file_exists($log) ) {
            readfile($log);
        }else{
            $rtnData = new stdClass();
            $rtnData->message = 'No response log found.';
            echo json_encode($rtnData);
        }
        exit;
    }

    public  function ajax_delete_file()
    {
	    // Check permissions
	    if (! self::is_authorized()) exit('Access denied.');

        $backup_file_name = str_replace('deleteRow', '', $_POST['filed']);
        $backup_file_path =  WPBACKITUP__BACKUP_PATH .'/' . $backup_file_name;
        $log_file_path = str_replace('.zip','.log',$backup_file_path);

        if (file_exists($backup_file_path)){
            unlink($backup_file_path);
        }

        if (file_exists($log_file_path)) {
            unlink($log_file_path);
        }

        exit('deleted');
    }

    function admin_viewlog(){
	    if (! self::is_authorized()) exit('Access denied.');

        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/viewlog.php' );
    }

	function admin_download_backup(){
		if (! self::is_authorized()) exit('Access denied.');

		include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/download_backup.php' );
	}

    function admin_listlogs(){
	   if (! self::is_authorized()) exit('Access denied.');

       include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/listlogs.php' );
    }



    /**
     * Process update page form submissions and validate license key
     * 
     */
    public  function _admin_options_update() {
        // Verify submission for processing using wp_nonce
        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-update-options" ) ) {

            /**
             * Loop through each POSTed value and sanitize it to protect against malicious code. Please
             * note that rich text (or full HTML fields) should not be processed by this function and 
             * dealt with directly.
             */
           
            $logger = new WPBackItUp_Logger(false);
            $logger->log("Posted Fields");
            $logger->log($_POST['data']); //License will not be in this array

            foreach( $_POST['data'] as $key => $val ) {
                $posted_value = $this->_sanitize($val);
                //If license updated then validate
                if (!empty($key) && $key=='license_key') {
                    $logger->log('License Posted:' .$posted_value);
                    $this->update_license_options($posted_value);
                }
                else {
                    $data[$key] =$posted_value;
                    }
            }

            $license_description = $this->license_type_description();

            //Could have just been a license update
            if(!empty($data)) {


	            //** VALIDATE backup_retained_number **//
                //Set back to original settings if value not changed
                if(!empty($data['backup_retained_number']) && !is_numeric($data['backup_retained_number']))
                {
                  $data['backup_retained_number'] = $this->defaults['backup_retained_number'];
                  set_transient('settings-error-number', __('Please enter a number', $this->namespace), 60);

                }
                else{ //Empty OR not NUMERIC

                    //Empty
                    if ( empty($data['backup_retained_number']) ){
	                    $data['backup_retained_number'] = $this->defaults['backup_retained_number'];
                        set_transient('settings-error-number', __('Please enter a number', $this->namespace), 60);
                    }

                    //exceeds lite threshold
                    if ( !empty($data['backup_retained_number']) && ($this->license_type()==0)  && ($data['backup_retained_number'] > 1) ){
                        $data['backup_retained_number'] = $this->defaults['lite_backup_retained_number'];
                        set_transient('settings-license-error', __(ucfirst($license_description) .' license holders may only save 1 backup archive.', $this->namespace), 60);
                    }

                    //exceeds pro threshold
                    if (!empty($data['backup_retained_number']) && ($this->license_type()==1) && ($data['backup_retained_number'] > 3)){
                        $data['backup_retained_number'] = $this->defaults['backup_retained_number'];
                        set_transient('settings-license-error', __(ucfirst($license_description) .' license holders may only save up to 3 backup archives.', $this->namespace), 60);
                    }

                }

	            //** VALIDATE notification_email **//
                if(!empty($data['notification_email']) && !is_email($data['notification_email']))
                {
                  $data['notification_email'] = $this->defaults['notification_email'];
                  set_transient('settings-error-email', __('Please enter a a valid email', $this->namespace), 60);
                }


	            //** VALIDATE backup_batch_size **//
	            if(empty($data['backup_batch_size']) || !is_numeric($data['backup_batch_size']))
	            {
		            $data['backup_batch_size'] = $this->defaults['backup_batch_size'];
		            set_transient('batch_size_settings-error-number', __('Please enter a number', $this->namespace), 60);
	            }


                // Update the options value with the data submitted
                foreach( $data as $key => $val ) {
                    $this->set_option($key, $val);
                    $logger->log('Updated Option: ' .$key .':' .$val);
                }
            }

            // Redirect back to the options page with the message flag to show the saved message
            wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&update=1' );
            exit;
        }
    }

    /**
     * Save Schedule
     *
     */
    public  function _admin_save_schedule() {
        // Verify submission for processing using wp_nonce
        $logger = new WPBackItUp_Logger(false);

        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-update-schedule" ) ) {

            $logger->log("Save Schedule");
            $logger->log($_POST);

            $val = $_POST['days_selected'];
            $days_selected = $this->_sanitize($val);
            $logger->log('Days Selected:' .     $days_selected);

            //save option to DB even if empty
            $this->set_backup_schedule($days_selected);

            //Add backup scheduled if doesnt exist
            if(!wp_next_scheduled( 'wpbackitup_queue_scheduled_jobs' ) ){
                wp_schedule_event( time()+3600, 'hourly', 'wpbackitup_queue_scheduled_jobs');
            }

            return true;
        }

        return false;
    }

    /**
     * Process registration page form submissions
     *
     */
    public  function _admin_register_lite() {
        // Verify submission for processing using wp_nonce
        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-register-lite" ) ) {

            /**
             * Loop through each POSTed value and sanitize it to protect against malicious code. Please
             * note that rich text (or full HTML fields) should not be processed by this function and
             * dealt with directly.
             */

            $logger = new WPBackItUp_Logger(false,null,'debug_lite_registration');
            $logger->log("Register WP BackItUp Lite");
            $logger->log($_POST);

            $val = $_POST['email'];
            $email = $this->_sanitize($val);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)){
                $urlparts = parse_url(site_url());
                $domain = $urlparts['host'];

                $first_name=$_POST['first_name'];

                //save options to DB
                $this->set_option('lite_registration_email', $email);
	            if(!empty($first_name)){
	                $this->set_option('lite_registration_first_name', $first_name);
	            }

                $form_data =  array(
                        'email' => $email,
                        'site' => $domain,
                        'first_name' => $first_name,
	                    'time_zone'=> get_option('timezone_string'),
                );

	            $url = WPBACKITUP__SECURESITE_URL; //PRD
	            $post_url=$url . '/api/wpbackitup/register_lite';

                $logger->log('Lite User Registration Post URL: ' .$post_url);
                $logger->log('Lite User Registration Post Form Data: ' );
	            $logger->log($form_data);

                $response = wp_remote_post( $post_url, array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'blocking' => true,
                        'headers' => array(),
                        'body' => $form_data,
                        'cookies' => array()
                    )
                );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    $logger->log('Lite User Registration Error: ' .$error_message);
                } else {
                    $logger->log('Lite User Registered Successfully:');
                    $logger->log($response);
                }

            }

            // Redirect back to the options page with the message flag to show the saved message
            wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&update=1' );
            exit;
        }
    }

    /**
     * Hook into plugin_action_links filter
     * 
     * @param object $links An array of the links to show, this will be the modified variable
     * @param string $file The name of the file being processed in the filter
     * 
     */
    public  function plugin_action_links( $links, $file ) {

        // Add links to plugin
        if ( $file == plugin_basename( WPBACKITUP__PLUGIN_PATH . '/wp-backitup.php' ) ) {
            $settings_link = '<a href="' . esc_url( self::get_settings_page_url() ) . '">'.esc_html__( 'Settings' , 'wp-backitup').'</a>';
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    /**
     * 
     *       GETTERS
     * 
     **/   

    /**
    * Generic Getter
    */
    public  function get($property) {

       if (empty($this->$property)) {
         $this->$property = $this->get_option($property);

         //If not set then use the defaults
          if (empty($this->$property)) {
            $this->$property=$this->defaults[$property];
          }
      }

      return $this->$property;
        
    }


    /**
    * Getter - license key
    */
    public function license_key(){
      return $this->get('license_key');
    }

    /**
    * Getter - license status message
    */
    public function license_status_message(){
      return $this->get('license_status_message');
    }

    /**
    * Getter - license expires
    */
    public function license_expires(){
      return $this->get('license_expires');
    }

    /**
    * Getter - notification email
    */
    public function notification_email(){
      return $this->get('notification_email');
    }

    /**
    * Getter - logging
    */
    public function logging(){
      $logging = $this->get('logging');
      return $logging === 'true'? true: false;
    }

    public function backup_schedule(){
        return $this->get('backup_schedule');
    }

    public function backup_lastrun_date(){
        return $this->get('backup_lastrun_date');
    }

    public function cleanup_lastrun_date(){
        return $this->get('cleanup_lastrun_date');
    }

	public function backup_batch_size(){
		return $this->get('backup_batch_size');
	}



    /**
    * Getter - license active - derived property
    */
    public function license_active(){
      //echo('</br>license Active Value1:' .$this->license_active);   
       
      if (empty($this->license_active)) {
        //echo('</br>SET PROP');   

        $this->license_active = false;//default

        $license_key = $this->license_key();
        $license_status = $this->license_status();

        //Allow expired licenses to be active for now
        if(false !== $license_key && false !== $license_status) { 
         if ('valid'== $license_status || 'expired'== $license_status) {
            $this->license_active= true;
          } 
        }        
      }

      //echo('</br>license Active Value2:' .$this->license_active);  
      return $this->license_active;
    }

    /**
    * Getter - license status
    */
    public function license_status(){
      return $this->get('license_status');
    }


    /**
    * Getter: Get license type or default
    */
    public function license_type(){
       return $this->get('license_type');
    }

    /**
    * Getter - license type description - derived property
    */
    public function license_type_description(){

        if (empty($this->license_type_description)) {
            
            switch ($this->license_type()) {
                case 0:
                    $this->license_type_description = 'lite';
                    break;
                case 1:
                    $this->license_type_description = 'personal';
                    break;

                case 2:
                    $this->license_type_description = 'business';
                    break;

                case 3:
                    $this->license_type_description = 'professional';
                    break;
            }
        }

        return $this->license_type_description;
    }

    /**
    * Getter - backup retained number - derived property
    */
    public function backup_retained_number(){
        if (empty($this->backup_retained_number)) {
            $this->backup_retained_number = $this->get_option('backup_retained_number');

            //If not set then use the defaults
            if (empty($this->backup_retained_number)) {

                switch ($this->license_type()) {
                    case 0: //Lite
                        $this->backup_retained_number=1;
                        break;
                    case 1: //Personal
                        $this->backup_retained_number=3;
                        break;

                    case 2: //Business
                        $this->backup_retained_number=3;
                        break;

                    case 3: //Pro
                        $this->backup_retained_number=3;
                        break;
                }

                $this->set_option('backup_retained_number',$this->backup_retained_number); 
            }

        }
        
        return $this->backup_retained_number;
        
    }

    function backup_count(){
       return $this->get('backup_count');
    }

    function successful_backup_count(){
        return $this->get('successful_backup_count');
    }

    function lite_registration_email(){
        return $this->get('lite_registration_email');
    }

	function lite_registration_first_name(){
		return $this->get('lite_registration_first_name');
	}

    function is_lite_registered(){
        $lite_email_registration= $this->lite_registration_email();
        if (!empty($lite_email_registration)) {
            return true;
        } else {
            return false;
        }

    }

    /**---------- END GETTERS --------------- **/

    /**---------- SETTERS --------------- **/

    /**
     * Generic Setter
     */
    private  function set($property,$value) {

        $this->set_option($property, $value);
        $this->$property = $value;

        //If not set then use the defaults
        if (empty($this->$property)) {
            $this->$property=$this->defaults[$property];
        }

    }

    function set_backup_count($value){
        $this->set('backup_count', $value);
    }

    function set_successful_backup_count($value){
        $this->set('successful_backup_count', $value);
    }

    public function set_backup_schedule($value){
        $this->set('backup_schedule', $value);
    }

    public function set_backup_lastrun_date($value){
        $this->set('backup_lastrun_date', $value);
    }

    public function set_cleanup_lastrun_date($value){
        $this->set('cleanup_lastrun_date', $value);
    }

	public function set_backup_batch_size($value){
		$this->set('backup_batch_size', $value);
	}
    /**---------- END SETTERS --------------- **/


    /**-------------- LICENSE FUNCTIONS ---------------**/

    /**
     * Validate License Info Once per day
     */
    public function check_license(){
        $license_key=$this->license_key(); 
        //echo "</br>License Key:" .$license_key;
        
        $license_last_check_date=$this->get_option('license_last_check_date');

        //Validate License once per day
        $license_last_check_date = new DateTime($license_last_check_date);
        //echo($license_last_check_date->format('Y-m-d H:i:s') .'</br>');
          
        $now = new DateTime('now');//Get NOW
        $yesterday = $now->modify('-1 day');//subtract a day
        //$yesterday = $now->sub(new DateInterval('P1D'));//subtract a day PHP 3.0 only
        //echo($yesterday->format('Y-m-d H:i:s') .'</br>');

        //Validate License
        if ($license_last_check_date<$yesterday)
        {
          //echo "Validate License";
          $this->update_license_options($license_key);
          //$this->update_stats($license_key);
        }
    }

    /**
    * Update ALL the license options
    */
    private function update_license_options($license)
    {
        $logger = new WPBackItUp_Logger(false);
        $logger->log('Update License Options:' .$license);

        $license=trim($license);

        //Load the defaults
        $data['license_key'] = $this->defaults['license_key'];
        $dt = new DateTime('now');
        $data['license_last_check_date'] = $dt->format('Y-m-d H:i:s');

        $data['license_status'] = $this->defaults['license_status'];
        $data['license_status_message']= $this->defaults['license_status_message'];
        $data['license_expires']= $this->defaults['license_expires'];
        $data['license_limit']= $this->defaults['license_limit'];
        $data['license_sitecount']= $this->defaults['license_sitecount'];
        $data['license_type']= $this->defaults['license_type'];

        //If no value then default to lite     
        if (empty($license) || 'lite'== $license ){
            $data['license_status'] = 'free';
            $data['license_expires']= $this->defaults['license_expires'];
            $data['license_limit']= 1;
            $data['license_sitecount']= 1;
            $data['license_type']= 0;        
        } else {
            //CALL EDD_ACTIVATE_LICENSE to get activation information
            $api_params = array( 
                'edd_action'=> 'activate_license', 
                'license'   => $license, 
                'item_name' => urlencode( WPBACKITUP__ITEM_NAME ), // the name of product in EDD
                //'url'        => home_url()
            );

	        $logger->log('Activate License Request Info:');
	        $logger->log($api_params);

            $response = wp_remote_get(
	            add_query_arg( $api_params, WPBACKITUP__SECURESITE_URL ),
	            array(
		            'timeout' => 15,
	                'sslverify' => false
	            )
            );
            $logger->log('Validation Response:');
            $logger->log($response);

            if ( is_wp_error( $response ) )
                return false; //Exit and don't update
            
            $license_data = json_decode( wp_remote_retrieve_body( $response ) ); 
            $logger->log('License Object Info');
            $logger->log($license_data);

            $data['license_key'] = $license;
            $data['license_status'] = $license_data->license;
            $data['license_limit'] = $license_data->license_limit;
            $data['license_sitecount'] = $license_data->site_count;
            $data['license_expires'] = $license_data->expires; 

            //This is how we determine the type of license because
            //there is no difference in EDD
            if (is_numeric($license_data->license_limit)){

                //Personal
                if ($license_data->license_limit<5) {
                        $data['license_type'] = 1;
                }

                //Business
                if ($license_data->license_limit>=5  && $license_data->license_limit<20) {
                        $data['license_type'] = 2;
                }

               //Professional
               if ($license_data->license_limit>=20) {
                        $data['license_type'] = 3;
                }

                //EDD sends back expired in the error
                if (($license_data->license=='invalid') && ($license_data->error=='expired')){

                    //Default to valid for now
                    $data['license_status'] ='valid';
                    $data['license_status_message'] ='';

                    //Only expire license in current month
                    $license_expire_date = $license_data->expires;
                    $expire_date_array = date_parse($license_expire_date);
                    $logger->log('Expire Date Array');
                    $logger->log($expire_date_array);
                    $logger->log('Expire Month: ' .$expire_date_array[month]);
                    $logger->log('Current Month: ' .date('m'));

                    //only EXPIRE current month
                    //if ($expire_date_array[month]==date('m')) {
                        $data['license_status'] ='expired';
                        $data['license_status_message'] ='License has expired.';
                        $logger->log('Expire License.');
                    //}
                }

                if (($license_data->license=='invalid') && ($license_data->error=='no_activations_left')){
                    $data['license_status_message'] ='Activation limit has been reached.';
                }
            }  
        }

        $logger->log('Updating License Options');  
        foreach($data as $key => $val ) {
            $this->set_option($key, $val);
            $logger->log('Updated Option: ' .$key .':' .$val);
        }
        return true;
    }
    
    /**-------------- END LICENSE FUNCTIONS ---------------**/

    /**
     * Retrieve the stored plugin option or the default if no user specified value is defined
     * 
     * @param string $option_name
     * 
     * @uses get_option()
     * 
     * @return mixed Returns the option value or false(boolean) if the option is not found
     */
    public function get_option( $option_name ) {
        // Load option values if they haven't been loaded already
        $wp_option_name = $this->namespace .'_' .$option_name;

        //Use this after migration
        //$option_value = get_option($wp_option_name,$this->defaults[$option_name]);   
       
        $option_value = get_option($wp_option_name);  

        //return the value
        if(isset( $option_value ) && !empty( $option_value )) return $option_value;
        
        //Should only happen once
        //Can take this out in next release
        //If looking for license then migrate the old settings
        if ('license_key'==$option_name) {
            $options = get_option('_' . $this->namespace . '--options');
            $license = $options[$option_name];
            if( isset( $license ) || !empty( $license ) ) {
                //migrate to new option setting
                $this->set_option($option_name, $license);
                $this->update_license_options($license);
                
                //Delete the old options  
                delete_option('_' . $this->namespace . '--options');               

                return $license;
            }
        
        }
        //Return the default
        return $this->defaults[$option_name];
    }

    //Prefix options with namespace & save
    public function set_option($option_name, $value) {
        $option_name = $this->namespace .'_' .$option_name;
        update_option($option_name,$value);

        //Check class variables
        if($option_name=='license_type')
            $this->license_type= $value;       
    }

    public function increment_backup_count(){
        $backup_count = $this->backup_count();
        $backup_count=$backup_count+1;
        $this->set_backup_count($backup_count);
    }

    public function increment_successful_backup_count(){
        $successful_backup_count = $this->successful_backup_count();
        $successful_backup_count=$successful_backup_count+1;
        $this->set_successful_backup_count($successful_backup_count);
    }

     /**
     * Sanitize data
     * 
     * @param mixed $str The data to be sanitized
     * 
     * @uses wp_kses()
     * 
     * @return mixed The sanitized version of the data
     */
    private function _sanitize( $str ) {
        if ( !function_exists( 'wp_kses' ) ) {
            include_once ABSPATH . 'wp-includes/kses.php';
        }
        global $allowedposttags;
        global $allowedprotocols;
        
        if ( is_string( $str ) ) {
            $str = wp_kses( $str, $allowedposttags, $allowedprotocols );
        } elseif( is_array( $str ) ) {
            $arr = array();
            foreach( (array) $str as $key => $val ) {
                $arr[$key] = $this->_sanitize( $val );
            }
            $str = $arr;
        }
        
        return $str;
    }

    /**STATIC FUNCTIONS**/

	public static function is_authorized(){

		$permission_logger = new WPBackItUp_Logger(false,null,'debug_permissions');
		$permission_logger->log_info(__METHOD__ ,'Begin');

		$permission_logger->log_info(__METHOD__ ,'User Permissions: ' .current_user_can( 'manage_options' ));

		if (defined('DOING_CRON')) {
			$permission_logger->log_info( __METHOD__, 'Doing CRON Constant: ' . DOING_CRON );
 		} else {
			$permission_logger->log_info(__METHOD__ ,'DOING_CRON - NOT defined');
		}

		if (defined('XMLRPC_REQUEST')) {
			$permission_logger->log_info(__METHOD__ ,'XMLRPC_REQUEST Constant: ' .XMLRPC_REQUEST );
		} else {
			$permission_logger->log_info(__METHOD__ ,'XMLRPC_REQUEST  - NOT defined ');
		}

		//Check User Permissions or CRON
		if (!current_user_can( 'manage_options' )
		    && (!defined('DOING_CRON') || !DOING_CRON)){
			$permission_logger->log_info(__METHOD__ ,'End - NOT AUTHORIZED');
			return false;
		}

		$permission_logger->log_info(__METHOD__ ,'End - SUCCESS');
		return true;
	}

    private static function get_settings_page_url( $page = 'config' ) {

        $args = array( 'page' => 'wp-backitup-settings' );
        $url = add_query_arg( $args, admin_url( 'admin.php' ));

        return $url;
    }

    
    /**
     * Activation action
     */
    public static function activate() {
       try{
	       //Get rid of old job
	       wp_clear_scheduled_hook('wpbackitup_check_scheduled_tasks');

	       //add cron task for once per hour starting in 1 hour
	       if(!wp_next_scheduled( 'wpbackitup_queue_scheduled_jobs' ) ){
		       wp_schedule_event( time()+3600, 'hourly', 'wpbackitup_queue_scheduled_jobs');
	       }

	       require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-filesystem.php' );
	       $file_system = new WPBackItUp_FileSystem();

	       //Check backup folder folders
	       $backup_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__BACKUP_FOLDER;
	       $file_system->secure_folder( $backup_dir);


           //--Check restore folder folders
           $restore_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER;
	       $file_system->secure_folder( $restore_dir);

	       $logs_dir = WPBACKITUP__PLUGIN_PATH .'/logs/';
	       $file_system->secure_folder( $logs_dir);

			//Make sure they exist now
			if( !is_dir($backup_dir) || !is_dir($restore_dir)) {
			   exit ('WP BackItUp was not able to create the required backup and restore folders.');
			}

       } catch (Exception $e) {
           exit ('WP BackItUp encountered an error during activation.</br>' .$e->getMessage());
       }
    }

    /**
     * Deactivation action
     */
    public static function deactivate() {
        // Do deactivation actions

        wp_clear_scheduled_hook( 'wpbackitup_queue_scheduled_jobs');
    }

    /* ---------------------     PRIVATES      -----------------------------------------*/

    /**
     * Update statistics
     */
    private function update_stats($license)
    {
        $logger = new WPBackItUp_Logger(true);
        $logger->log('Update Stats:' .$license);

        $license=trim($license);

        //Get stats here

        //Setup API call
        $api_params = array(
            'wpb_action'=> 'update_stats',
            'license'   => $license
        );

        $url = WPBACKITUP__SECURESITE_URL .'/stats-update-test';
        $response = wp_remote_get( add_query_arg( $api_params, $url ), array( 'timeout' => 15, 'sslverify' => true ) );
        $logger->log('Stats Response:');
        $logger->log($response);

        if ( is_wp_error( $response ) )
            return false; //Exit and don't update

        //$license_data = json_decode( wp_remote_retrieve_body( $response ) );

        return true;
    }

    //Pretty= Pretty version of anchor
    //Page = page to link to
    //content = Widget Name(where)
    //term = pinpoint where in widget
    function get_anchor_with_utm($pretty, $page, $content = null, $term = null){

        $medium='plugin'; //Campaign Medium
        $source=$this->namespace; //plugin name

        $campaign='lite';
        if ($this->license_active()) $campaign='premium';

        $utm_url = WPBACKITUP__SITE_URL .'/' .$page .'/?utm_medium=' .$medium . '&utm_source=' .$source .'&utm_campaign=' .$campaign;

        if (!empty($content)){
            $utm_url .= '&utm_content=' .$content;
        }

        if (!empty($term)){
            $utm_url .= '&utm_term=' .$term;
        }

        $anchor = '<a href="'.$utm_url .'" target="_blank">' .$pretty .'</a>';
        return $anchor;

    }

    /* ---------------------   END PRIVATES   -----------------------------------------*/


}
