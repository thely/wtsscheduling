<?php
/***
* Plugin Name: DB Backup
* Description: Plugin for Database Backup.
* Version: 4.5
* Author: Syed Amir Hussain
***/
if ( ! defined('ABSPATH') ) {
	die('Please do not load this file directly.');
}
define('plugin_name', 'DB-Backup');
define('SLASH_N', "--\n");
if(!class_exists('DB_Backup')) {	
	class DB_Backup {
		function __construct() {
			if( is_admin() ) {
				// hook for adding admin menus
				add_action('admin_menu', array( $this, 'am_add_pages' ));
				add_action( 'admin_init', array( $this, 'am_init_css_js' ) );
				// action hook to handle ajax request
				add_action( 'wp_ajax_myAjax', array( $this, 'am_handleRequest' ) );
				// action hook to add option
				register_activation_hook( __FILE__, array( $this, 'am_update_option' ) );
			}
		}
		function am_update_option(){
				$array = wp_upload_dir();
				update_option('am_upload_path', str_replace('\\', '/', $array['basedir']));
		}
		function am_add_pages() {
			// add a new top-level menu
			add_menu_page('DB Backup', 'DB Backup', 'manage_options', 'db-backup', array( &$this, 'am_get_option' ) );
		}
		// action function to include css and js
		function am_init_css_js() {
			wp_register_style( 'style', plugins_url('/css/style.css', __FILE__));
			wp_enqueue_style( 'style' );
			wp_register_script( 'js_', plugins_url('/js/js.js', __FILE__));
			wp_enqueue_script( 'js_' );
		}
		// action function displays the page content for the Make CSV
		function am_get_option() {
			global $wpdb;
			//must check that the user has the required capability 
			if (!current_user_can('manage_options'))
			{
			  wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			$this->am_echo_option();
		}
		function am_get_tables( $exclude_prefix = false ){
			global $wpdb;
			$sql = 'SHOW TABLES LIKE "%"';
			$results = $wpdb->get_results($sql);
			$tables = array();
			foreach($results as $index => $value) {
				foreach($value as $tableName) {
					if( $exclude_prefix ){
						$tableName = str_replace($wpdb->prefix, '', $tableName);
					}
					$tables[] = $tableName;
				}
			}
			if(count( array_filter($tables) )){
				return $tables;
			}
			die('Error! there is no tables in the selected database.');
		}
		// action function to create dropdown of the tables
		function am_echo_option() {
			echo $this->am_get_template('index', $this->am_get_template('donate'));
		}
		function am_handleRequest() {
			$output = "";
			parse_str($_POST['data'], $_POST);
			if( 'comp_bkp' == $_POST['csv_comp_bkp'] ){
				$tables = $this->am_get_tables( $exclude_prefix = true );
				$_POST['am_csv_tbl'] = array_merge( array(), $tables );
			}
			$func = 'am_'.$_POST['am_option'];
			foreach( $_POST['am_csv_tbl'] as $tab ):
				$output .= $this->$func( $tab )."\n\n";
			endforeach;
			if( isset($_POST['am_saveAs_option']) && "save_as" == $_POST['am_saveAs_option'] ):
				$jsResponse = $this->am_make_download( $output, $ext = $_POST['am_option'] );
			else:
				$jsResponse = '<textarea class="am_csv_output_area">'.$output.'</textarea>';
			endif;
			echo $jsResponse;
			die;	
		}
		// action function to make sql query
		function am_export( $tbl ) {
			global $wpdb;
			$result_col = $wpdb->get_results('SHOW COLUMNS FROM '.$wpdb->prefix.$tbl);
			$struct = "";	$data = "";
			if( 'only_structure' == $_POST['ex_struct'] ) {
				$struct .= SLASH_N.'-- Table structure for table `'.$wpdb->prefix.$tbl."`\n".SLASH_N.'CREATE TABLE `'.$wpdb->prefix.$tbl."` (\n";
				foreach ($result_col as $row) {
					$null = ($row->Null == 'NO') ? ' NOT NULL' : '';
					$pri = ($row->Key == 'PRI') ? ' PRIMARY KEY' : '';
					$default = ($row->Default != '') ? ' DEFAULT "'.$row->Default.'"' : '';
					$extra = ($row->Extra != '') ? ' '.$row->Extra.' ' : '';
					$struct .= '`'.$row->Field.'` '.$row->Type.$null.$default.$extra.$pri.",\n";
				}
				$struct = rtrim($struct, ",\n");
				$struct .= "\n) ENGINE = MYISAM;\n\n";
			}
			if( 'only_data' == $_POST['ex_data'] ) {
				$rs_data = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.$tbl, ARRAY_A);
				if( $rs_data ){
					$fields = "";
					foreach ($result_col as $row) {
						$fields .= '`'.$row->Field.'`, ';
					}
					$fields = rtrim($fields, ', ');
					
					$values = "";
					foreach( $rs_data as $val ){
						$values .= "(";
						foreach( $val as $v ):
							$v = htmlentities(mysql_real_escape_string($v));
							$values .= '"'.$v.'", ';
						endforeach;
						$values = rtrim($values, ', ');
						$values .= "),\n";
					}
					$values = rtrim($values, ",\n");
					$data .= SLASH_N.'-- Dumping data for table `'.$wpdb->prefix.$tbl."`\n".SLASH_N.'INSERT INTO `'.$wpdb->prefix.$tbl.'`( '.$fields." ) VALUES\n".$values.';';
				}
			}
			$query = $struct.$data;
			return $query;
		}
		// action function to make the csv
		function am_make_csv( $tbl ) {
			global $wpdb;
			$data = "";
			if( 'include_column' == $_POST['csv_inc_col'] ){
				$result_col = $wpdb->get_results('SHOW COLUMNS FROM '.$wpdb->prefix.$tbl);
				foreach( $result_col as $col ){
					$data .= '"'.$col->Field.'",';
				}
				$data = rtrim($data, ',');
				$data .= "\n";
			}
			$rs_data = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.$tbl, ARRAY_A);
			if( $rs_data ){
				$values = "";
				foreach( $rs_data as $val ){
					foreach( $val as $v ):
						$v = mysql_real_escape_string(htmlentities($v));
						if( empty($v) ){
							$v = 'NULL';
						}
						$values .= '"'.$v.'",';
					endforeach;
					$values = rtrim($values, ',');
					$values .= "\n";
				}
				$data .= $values;
			}
			return $data;
		}
		// action to download export file
		function am_make_download( $content = "", $ext ){
			$fileName = 'am_'.time();
			if( "" != $_POST['am_saveAs_fileName'] ) {
				$fileName = $_POST['am_saveAs_fileName'].'_'.time();
			}
			$ext = ( $ext == 'make_csv' )?'.csv':'.sql';
			$fileName = $this->am_make_file($fileName, $ext, $content);
			$url = plugins_url(plugin_name.'/download.php');
			$url .= '?file='.content_url().'/uploads/'.$fileName;
			$this->prn_js( $url );
		}
		// action to make file
		function am_make_file( $fileName, $ext, $content ){
			$path = get_option('am_upload_path').'/'.$fileName.$ext;
			$fp = fopen($path, 'w');
			fwrite( $fp, $content);
			fclose($fp);
			# make zip
			if( 'zipped' == $_POST['am_saveAs_zip'] ){
				return $this->am_make_zip( $fileName, $ext );
			}
			return $fileName.$ext;
		}
		function am_make_zip( $fileName, $ext ){
			$zip = new ZipArchive();
			$path = get_option('am_upload_path').'/'.$fileName.$ext;
			$zip_name = $fileName.'.zip';
			$zip_path = get_option('am_upload_path').'/'.$zip_name;
			$zip->open($zip_path, ZipArchive::CREATE);
			$zip->addFromString(basename($path),  file_get_contents($path));
			$zip->close();
			unlink( $path );
			return $zip_name;
		}
		function am_get_template( $file, $donate="", $echo = false ){
			global $wpdb;
			ob_start();
			include('templates/'.$file.'.php');
			$content = ob_get_clean();
			if( $echo ) echo $content; else return $content;
		}
		function prn_js( $url ){
			print<<<EOM
				<script>
					window.location.href = "$url";
				</script>
EOM;
		}
	}
	new DB_Backup();
}
?>