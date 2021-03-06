<?php if (!defined ('ABSPATH')) die('No direct access allowed (restore)');
@set_time_limit(900);// 15 minutes per image should be PLENTY

/**
 * WP Backitup Restore Functions
 * 
 * @package WP Backitup Pro
 * 
 * @author cssimmon
 *
 */

/*** Includes ***/

// include backup class
if( !class_exists( 'WPBackItUp_Backup' ) ) {
	include_once 'class-backup.php';
}

// include backup class
if( !class_exists( 'WPBackItUp_Restore' ) ) {
    include_once 'class-restore.php';
}

// include file system class
if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
    include_once 'class-filesystem.php';
}

// include SQL class
if( !class_exists( 'WPBackItUp_SQL' ) ) {
    include_once 'class-sql.php';
}

//Include Job class
if( !class_exists( 'WPBackItUp_Job' ) ) {
	include_once 'class-job.php';
}


/*** Globals ***/
global $WPBackitup;
global $table_prefix; //this is from wp-config

global $backup_file_name; //name of the backup file
global $backup_file_path; //full path to zip file on server
global $RestorePoint_SQL; //path to restore point

global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
$inactive=0;
$active=1;
$complete=2;
$failure=-1;
$warning=-2;
$success=99;

//setup the status array
global $status_array;
$status_array = array(
	'preparing' =>$inactive ,
	'unzipping' =>$inactive ,
	'validation'=>$inactive,
	'restore_point'=>$inactive,
	'database'=>$inactive,
	'plugins'=>$inactive,
	'themes'=>$inactive,
	'uploads'=>$inactive,
	'other'=>$inactive,
	'cleanup'=>$inactive
 );

//Get the backup ID
$job_log_name =  get_job_log_name();

global $logger;
$logger = new WPBackItUp_Logger(false,null,$job_log_name,true);

global $wp_restore; //Eventually everything will be migrated to this class
$wp_restore = new WPBackItUp_Restore($logger);


//*****************//
//*** MAIN CODE ***//
//*****************//
$logger->log('***BEGIN RESTORE***');
$logger->log_sysinfo();

//Is backup running
if ( ! WPBackItUp_Backup::start()) {
	$logger->log_info(__METHOD__,'Restore job cant acquire job lock.');
	write_fatal_error_status('error250');
	die();
}else{
	$logger->log_info(__METHOD__,'Restore job lock acquired.');
}

if (!$this->license_active()){
	$logger->log('Restore is not available because license is not active.');
	write_fatal_error_status('error225');
 	die();
 }

//--Get form post values
$backup_file_name = $_POST['selected_file'];//Get the backup file name
if( empty($backup_file_name)) {
	write_fatal_error_status('error201');
	die();
}

//Get user ID
$user_id = $_POST['user_id'];
if( empty($user_id)) {
	write_fatal_error_status('error201');
	die();
}


//TEST

//END TEST

set_status('preparing',$active,true);

//set path to backup file
$backup_file_path = $wp_restore->backup_folder_path .$backup_file_name ;

$logger->log('**DELETE RESTORE FOLDER**');
delete_restore_folder();
$logger->log('** END DELETE RESTORE FOLDER**');

$logger->log('**CREATE RESTORE FOLDER**');
create_restore_folder($wp_restore->restore_folder_path);
set_status('preparing',$complete,false);
$logger->log('**END CREATE RESTORE FOLDER**');

$logger->log('**UNZIP BACKUP**');
set_status('unzipping',$active,true);
unzip_backup($backup_file_path,$wp_restore->restore_folder_path);
set_status('unzipping',$complete,false);
$logger->log('**END UNZIP BACKUP**');

$logger->log('**VALIDATE BACKUP**');
set_status('validation',$active,true);
$restoration_dir_path=validate_restore_folder($wp_restore->restore_folder_path);
$logger->log('**END VALIDATE BACKUP**');


//Set all the path information
$site_data_path=$restoration_dir_path . 'site-data/';
$plugins_path=$restoration_dir_path . 'wp-content-plugins/';
$themes_path=$restoration_dir_path . 'wp-content-themes/';
$other_path=$restoration_dir_path . 'wp-content-other/';
$uploads_path=$restoration_dir_path . 'wp-content-uploads/';

$logger->log('**VALIDATE SQL FILE EXISTS**');
$backupSQLFile = $site_data_path. WPBACKITUP__SQL_DBBACKUP_FILENAME;
validate_SQL_exists($backupSQLFile);
$logger->log('**END VALIDATE SQL FILE EXISTS**');


$logger->log('**GET SITE VALUES FROM DB**');
$siteurl =  get_siteurl();
$homeurl = get_homeurl();
$user_login = get_user_login($user_id);
$user_pass = get_user_pass($user_id);
$user_email = get_user_email($user_id);
$logger->log('**END GET SITE VALUES FROM DB**');


//Collect previous backup site url start
$logger->log('**GET backupsiteinfo.txt VALUES**');
$import_siteinfo_lines = file($site_data_path .'backupsiteinfo.txt');
$import_siteurl = str_replace("\n", '',trim($import_siteinfo_lines[0]));
$current_siteurl = trim($siteurl ,'/');
$import_table_prefix = str_replace("\n", '',$import_siteinfo_lines[1]);
$import_wp_version = str_replace("\n", '',$import_siteinfo_lines[2]);
$logger->log($import_siteinfo_lines);

//Check table prefix values FATAL
if($table_prefix !=$import_table_prefix) {
	$logger->log('Error: Table prefix different from restore.');
	write_warning_status('error221');
}

$logger->log('**END GET backupsiteinfo.txt VALUES**');


$logger->log('**CREATE RESTORE POINT**');
//Create restore point for DB
set_status('validation',$complete,false);
set_status('restore_point',$active,true);
$RestorePoint_SQL = backup_database($wp_restore->backup_folder_path); //Save in backup folder
set_status('restore_point',$complete,false);
$logger->log('**END CREATE RESTORE POINT**');


$logger->log('**RESTORE DATABASE**');
//Import the backed up database
set_status('database',$active,true);
import_backedup_database($backupSQLFile,$RestorePoint_SQL);
$logger->log('**END RESTORE DATABASE**');

$logger->log('**UPDATE DATABASE VALUES**');
//FAILURES AFTER THIS POINT SHOULD REQUIRE ROLLBACK OF DB
WPBackItUp_Job::cancel_all_jobs();
update_user_credentials($import_table_prefix, $user_login, $user_pass, $user_email, $user_id);
update_siteurl($import_table_prefix, $current_siteurl);
update_homeurl($import_table_prefix, $homeurl);
$logger->log('**END UPDATE DATABASE VALUES**');

//Done with DB restore
set_status('database',$complete,false);


//***DEAL WITH WPCONTENT NOW ***

$logger->log('**DELETE PLUGINS**');
$target_plugins_path=WPBACKITUP__PLUGINS_ROOT_PATH;
set_status('plugins',$active,true);
$plugin_ignore = array(WPBACKITUP__PLUGIN_FOLDER);
delete_folder_content($target_plugins_path,$plugin_ignore);
$logger->log('**END DELETE PLUGINS**');

$logger->log('**RESTORE PLUGINS**');
restore_folder($plugins_path,$target_plugins_path);
set_status('plugins',$complete,false);
$logger->log('**END RESTORE PLUGINS**');

$logger->log('**DELETE THEMES**');
$target_themes_path=WPBACKITUP__THEMES_ROOT_PATH;
set_status('themes',$active,true);
delete_folder_content($target_themes_path);
$logger->log('**END DELETE THEMES**');

$logger->log('**RESTORE THEMES**');
restore_folder($themes_path,$target_themes_path);
set_status('themes',$complete,false);
$logger->log('**END RESTORE THEMES**');

$logger->log('**DELETE UPLOADS**');
$upload_array = wp_upload_dir();
$target_uploads_path = $upload_array['basedir'];
set_status('uploads',$active,true);
delete_folder_content($target_uploads_path);
$logger->log('**END DELETE UPLOADS**');

$logger->log('**RESTORE UPLOADS**');
restore_folder($uploads_path,$target_uploads_path);
set_status('uploads',$complete,false);
$logger->log('**END RESTORE UPLOADS**');


$logger->log('**DELETE OTHER**');
$other_ignore = array(WPBACKITUP__BACKUP_FOLDER,WPBACKITUP__RESTORE_FOLDER,basename($target_plugins_path),basename($target_themes_path),basename($target_uploads_path),'debug.log');
set_status('other',$active,true);
delete_folder_content(WPBACKITUP__CONTENT_PATH,$other_ignore);
$logger->log('**END DELETE OTHER**');

$logger->log('**RESTORE OTHER**');
restore_other_folders($other_path,WPBACKITUP__CONTENT_PATH,$other_ignore);
set_status('other',$complete,false);
$logger->log('**END RESTORE OTHER**');

$logger->log('**VALIDATE WP-CONTENT**');
$logger->log('--VALIDATE PLUGINS--');
validate_wpcontent($plugins_path,$target_plugins_path);
$logger->log('--VALIDATE THEMES--');
validate_wpcontent($themes_path,$target_themes_path);
$logger->log('--VALIDATE UPLOADS--');
validate_wpcontent($uploads_path,$target_uploads_path);
$logger->log('--VALIDATE OTHER--');
validate_other_folders($other_path,WPBACKITUP__CONTENT_PATH,$other_ignore);
$logger->log('**END VALIDATE WPCONTENT**');

$logger->log('**CLEANUP**');
set_status('cleanup',$active,true);
cleanup_restore_folder($restoration_dir_path);
set_status('cleanup',$complete,false);
$logger->log('**END CLEANUP**');

set_status_success();
$logger->log('Restore completed successfully');
$logger->log('***END RESTORE***');

$logger->log('**UPDATE PERMALINKS**');
update_permalinks();
$logger->log('**END UPDATE PERMALINKS**');


WPBackItUp_Backup::end(); //release lock

echo('Restore has completed successfully.');
exit;

/******************/
/*** Functions ***/
/******************/
function get_job_log_name(){

	$fileUTCDateTime=current_time( 'timestamp' );
	$localDateTime = date_i18n('Y-m-d-His',$fileUTCDateTime);
	$job_log_name = 'job_restore_' .$localDateTime;

	return $job_log_name;

}

//Get Status Log
function get_restore_Log() {
	global $logger;

	$status_file_path = WPBACKITUP__PLUGIN_PATH .'/logs/restore_status.log';
	$filesystem = new WPBackItUp_FileSystem($logger);
	return $filesystem->get_file_handle($status_file_path);

}

function write_fatal_error_status($status_code) {
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
	
	//Find the active status and set to failure
	foreach ($status_array as $key => $value) {
		if ($value==$active){
			$status_array[$key]=$failure;	
		}
	}

	//Add failure to array
	$status_array[$status_code]=$failure;
	write_restore_status();
}

function write_warning_status($status_code) {
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
		
	//Add warning to array
	$status_array[$status_code]=$warning;
	write_restore_status();
}

function write_restore_status() {
	global $status_array;
	$fh=get_restore_Log();
	
	foreach ($status_array as $key => $value) {
		fwrite($fh, '<div class="' . $key . '">' . $value .'</div>');		
	}
	fclose($fh);
}

function set_status($process,$status,$flush){
	global $status_array;
	$status_array[$process]=$status;
	
	if ($flush) write_restore_status(); 
}

function set_status_success(){
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
	global $active;

	$status_array['finalinfo']=$success;
	write_restore_status();
}

//Create an empty restore folder
function create_restore_folder($path) {
    global $logger;
	$logger->log('Create restore folder:' .$path);

    $fileSystem = new WPBackItUp_FileSystem($logger);
	if( ! $fileSystem->create_dir($path)) {
		$logger->log('Error: Cant create restore folder :'. $path);
		write_fatal_error_status('error222');
		die();
	}

	//Secure restore folder
	$fileSystem->secure_folder( $path);

	//Check logs folder too
	$logs_dir = WPBACKITUP__PLUGIN_PATH .'/logs/';
	$fileSystem->secure_folder( $logs_dir);

	$logger->log('Restore folder created:' .$path);
}

//Delete restore folder and contents
function delete_restore_folder() {
    global $logger;
	global $wp_restore;
	//Delete the existing restore directory
	$logger->log('Delete existing restore folder:' .$wp_restore->restore_folder_path);
    $fileSystem = new WPBackItUp_FileSystem($logger);
    return $fileSystem->recursive_delete($wp_restore->restore_folder_path);
}

//Unzip the backup to the restore folder
function unzip_backup($backup_file_path,$restore_folder_root){
    global $logger;
	//unzip the upload
	$logger->log('Unzip the backup file source:' .$backup_file_path);
	$logger->log('Unzip the backup file target:' .$restore_folder_root);

	if (!class_exists('ZipArchive')){
		$logger->log('Zip Archive Class is not available.');
		write_fatal_error_status('error235');
		delete_restore_folder();
		die();
	}

	$filesystem = new WPBackItUp_FileSystem($logger);
	$zip_extract_root_path=$restore_folder_root .'/' .basename($backup_file_path,'.zip');
	if (!$filesystem->create_dir($zip_extract_root_path)){
		$logger->log('Unable to create extract root folder:'.$zip_extract_root_path);
		write_fatal_error_status('error203');
		delete_restore_folder();
		die();
	}

	//Unzip to extract folder
	try {
		$zip = new ZipArchive;
		$res = $zip->open($backup_file_path);
		if ($res === TRUE) {
			if (true===$zip->extractTo($zip_extract_root_path)){
				$zip->close();
			} else {
				$zip->close();
				$logger->log('Error: Cant unzip backup:'.$backup_file_path);
				write_fatal_error_status('error203');
				delete_restore_folder();
				die();
			}
		} else {
			$logger->log('Error: Cant open backup archive:'.$backup_file_path);
			write_fatal_error_status('error203');
			delete_restore_folder();
			die();
		}
		$logger->log('Backup file unzipped: ' .$zip_extract_root_path);
	} catch(Exception $e) {
		$logger->log('An Unexpected Error has happened: ' .$e);
		write_fatal_error_status('error203');
		delete_restore_folder();
		die();
	}
}

//Validate the restore folder 
function validate_restore_folder($restore_folder_root){
    global $logger;
	$restoration_dir_path='';

	$logger->log('Identify the restoration directory in restore folder: ' .$restore_folder_root.'*');
	if ( count( glob( $restore_folder_root.'*', GLOB_ONLYDIR ) ) == 1 ) {
		foreach( glob($restore_folder_root .'*', GLOB_ONLYDIR ) as $dir) {
			$restoration_dir_path = $dir .'/';
			$logger->log('Restoration directory Set to: ' .$restoration_dir_path);
		}
	}

    if  (empty($restoration_dir_path)) {
        $logger->log('Error: Restore directory INVALID: ' .$restore_folder_root);
        write_fatal_error_status('error204');
        delete_restore_folder(); //delete the restore folder if bad
        die();
    }

	//Validate the restoration
	$logger->log('Validate restoration directory: ' . $restoration_dir_path .'backupsiteinfo.txt');
	if(!glob($restoration_dir_path .'site-data/backupsiteinfo.txt') ){
		$logger->log('Error: backupsiteinfo.txt missing from restore folder: ' .$restoration_dir_path);
		write_fatal_error_status('error204');		
		delete_restore_folder(); //delete the restore folder if bad
		die();
	}
	$logger->log('Restoration directory validated: ' .$restoration_dir_path);
	return $restoration_dir_path;
}

// Backup the current database try dump first
function backup_database($restore_folder_root){
    global $logger;
	$date = date_i18n('Y-m-d-Hi',current_time( 'timestamp' ));
	$backup_file = $restore_folder_root . 'db-backup-' . $date .'.cur';
	$logger->log('Backup the current database: ' .$backup_file);

    $dbc = new WPBackItUp_SQL($logger);
	 if(!$dbc->mysqldump_export($backup_file)) {
		//Try a manual restore since dump didnt work
		if(!$dbc->manual_export($backup_file)) {
			$logger->log('Error: Cant backup database:'.$backup_file);
			write_fatal_error_status('error205');
			delete_restore_folder();
			die();
		}
	}
	$logger->log('Current database backed up: ' .$backup_file);
	return $backup_file;
}

//Make sure there IS a backup to restore
function validate_SQL_exists($backupSQLFile){
    global $logger;
	$logger->log('Check for database backup file:' . $backupSQLFile);

	if(!file_exists($backupSQLFile) && !empty($backupSQLFile)) {
		$logger->log('Error: NO Database backups in backup.');
		write_fatal_error_status('error216');
		delete_restore_folder();
		die();	
	}
	$logger->log('Database backup file exist:' . $backupSQLFile);	
}

//Restore DB
function restore_database(){
    global $logger;
	global $RestorePoint_SQL;
	$logger->log('Restore the DB to previous state:' . $RestorePoint_SQL);

    $dbc = new WPBackItUp_SQL($logger);
	if(!$dbc->run_sql_exec($RestorePoint_SQL)) {
        //Do it manually if the import doesnt work
        if(!$dbc->run_sql_manual($RestorePoint_SQL)) {
            $logger->log('Error: Database could not be restored.' .$RestorePoint_SQL);
            write_fatal_error_status('error223');
            delete_restore_folder();
            die();
        }
	}
	write_fatal_error_status('error224');			
	$logger->log('Database restored to previous state.');
}

//Run DB restore
function import_backedup_database($backupSQLFile,$restorePoint_SQL){
    global $logger;

	$logger->log('Import the backed up database.');
	//Try SQL Import first

    $dbc = new WPBackItUp_SQL($logger);
	if(!$dbc->run_sql_exec($backupSQLFile)) {
		//Do it manually if the import doesnt work
		if(!$dbc->run_sql_manual($backupSQLFile)) {
			$logger->log('Error: Database import error.');

            //Restore to checkpoint
            if ($dbc->run_sql_manual($restorePoint_SQL)){
                $logger->log('Database successfully restored to checkpoint.');
                write_fatal_error_status('error230');

            }
            else {
                $logger->log('Database NOT restored to checkpoint.');
                write_fatal_error_status('error212');
            }

            delete_restore_folder();
			die();	
		}
	}
	$logger->log('Backed up database imported.');
}

//get siteurl
function get_siteurl(){
    global $logger;
	global $table_prefix;
	$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='siteurl';";

    $dbc = new WPBackItUp_SQL($logger);
	$siteurl = $dbc->get_sql_scalar($sql);
	if (empty($siteurl)) {
		$logger->log('Error: Siteurl not found');
		write_fatal_error_status('error207');
		delete_restore_folder();
		die();
	}
	$logger->log('Siteurl found:' .$siteurl);
	return $siteurl;
}

//get homeurl
function get_homeurl(){
    global $logger;
	global $table_prefix;
	$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='home';";
    $dbc = new WPBackItUp_SQL($logger);
	$homeurl = $dbc->get_sql_scalar($sql);
	if (empty($homeurl)) {
		$logger->log('Error: Homeurl not found.');
		write_fatal_error_status('error208');
		delete_restore_folder();
		die();	
	}
	$logger->log('homeurl found:' . $homeurl);
	return $homeurl;
}

//get user login
function get_user_login($user_id){
    global $logger;
	global $table_prefix;
	$sql = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";

    $dbc = new WPBackItUp_SQL($logger);
	$user_login = $dbc->get_sql_scalar($sql);
	if (empty($user_login)) {
		$logger->log('Error: user_login not found.');
		write_fatal_error_status('error209');
		delete_restore_folder();
		die();
	}
	$logger->log('user_login found.');
	return $user_login;
}

//get user pass
function get_user_pass($user_id){
    global $logger;
	global $table_prefix;
	$sql = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";

    $dbc = new WPBackItUp_SQL($logger);
	$user_pass = $dbc->get_sql_scalar($sql);
	if (empty($user_pass)) {
		$logger->log('Error: user_pass not found.');
		write_fatal_error_status('error210');
		delete_restore_folder();
		die();
	}
	$logger->log('user_pass found.');
	return $user_pass;
}

//get user email
function get_user_email($user_id){
    global $logger;
	global $table_prefix;
	$sql = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";

    $dbc = new WPBackItUp_SQL($logger);
	$user_email = $dbc->get_sql_scalar($sql);
	if (empty($user_email)) {
		$logger->log('Error: user_email not found.');
		write_fatal_error_status('error211');
		delete_restore_folder();
		die();
	}
	$logger->log('user_email found.' . $user_email);
	return $user_email;
}

//Update user credentials
function update_user_credentials($table_prefix, $user_login, $user_pass, $user_email, $user_id){
    global $logger;
	$sql = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";

    $dbc = new WPBackItUp_SQL($logger);
	if (!$dbc->run_sql_command($sql)){
		$logger->log('Error: User Credential database update failed..');
		write_warning_status('error215');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('User Credential updated in database.');
}

//update the site URL in the restored database
function update_siteurl($table_prefix, $current_siteurl){
    global $logger;
    $sql = "UPDATE ". $table_prefix ."options SET option_value='" .$current_siteurl ."' WHERE option_name='siteurl'";

    $dbc = new WPBackItUp_SQL($logger);
	if (!$dbc->run_sql_command($sql)){
		$logger->log('Error: SiteURL updated failed.');
		write_warning_status('error213');
		restore_database();
		delete_restore_folder();		
		die();
	}
	$logger->log('SiteURL updated in database.');
}

//Update homeURL
function update_homeurl($table_prefix, $homeurl){
    global $logger;
    $sql = "UPDATE ". $table_prefix ."options SET option_value='" .$homeurl ."' WHERE option_name='home'";
    $dbc = new WPBackItUp_SQL($logger);
	if (!$dbc->run_sql_command($sql)){
		$logger->log('Error: HomeURL database update failed..');
		write_warning_status('error214');
		restore_database();
		delete_restore_folder();	
		die();
	}	
	$logger->log('HomeURL updated in database.');
}

//Delete wp-content content
function delete_wpcontent_content($root_folder){
    global $logger;
    $logger->log('Delete the wp_content contents:' .$root_folder);
	$ignore = array(WPBACKITUP__PLUGIN_FOLDER,WPBACKITUP__RESTORE_FOLDER,WPBACKITUP__BACKUP_FOLDER,'debug.log');
    $filesystem = new WPBackItUp_FileSystem($logger);
	if(!$filesystem->recursive_delete($root_folder,$ignore)) {
		$logger->log('Error: Cant delete WPContent:' .$root_folder);	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('wp-content has been deleted:' .$root_folder);
}

//Delete plugins content
function delete_plugins_content(){
    global $logger;
    $plugins_folder=WPBACKITUP__PLUGINS_ROOT_PATH;
    $logger->log('Delete the plugins contents:' .$plugins_folder);
	$ignore = array(WPBACKITUP__PLUGIN_FOLDER);
    $filesystem = new WPBackItUp_FileSystem($logger);
	if(!$filesystem->recursive_delete($plugins_folder,$ignore)) {
		$logger->log('Error: Cant delete old WPContent:' .$plugins_folder  );	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('Plugins content deleted:' .$plugins_folder);
}


//Delete themes content
function delete_themes_content(){
    global $logger;
    $themes_folder=WPBACKITUP__THEMES_ROOT_PATH ;
    $logger->log('Delete the themes contents:' .$themes_folder);
    $filesystem = new WPBackItUp_FileSystem($logger);
    if(!$filesystem->recursive_delete($themes_folder)) {
		$logger->log('Error: Cant delete old WPContent:' .$themes_folder  );	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('Themes content deleted:' .$themes_folder);
}

//Delete folder content
function delete_folder_content($target_path,$ignore = array('')){
	global $logger;
	//add the / if needed
	//$target_path =rtrim($target_path, '/') . '/';
	$logger->log('Delete the folder contents:' .$target_path);
	$filesystem = new WPBackItUp_FileSystem($logger);
	if(!$filesystem->recursive_delete($target_path,$ignore)) {
		$logger->log('Error: Cant delete old WPContent:' .$target_path  );
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('Folder content deleted:' .$target_path);
}

//Restore all wp content from zip
function restore_wpcontent($restoration_dir_path){
    global $logger;
    $logger->log('Copy content folder from:' .$restoration_dir_path);
	$logger->log('Copy content folder to:' .WPBACKITUP__CONTENT_PATH);
	$ignore =  array(WPBACKITUP__PLUGIN_FOLDER, WPBACKITUP__BACKUP_FOLDER,WPBACKITUP__RESTORE_FOLDER, 'status.log','debug.log', WPBACKITUP__SQL_DBBACKUP_FILENAME, 'backupsiteinfo.txt');
    $filesystem = new WPBackItUp_FileSystem($logger);
    if(!$filesystem->recursive_copy($restoration_dir_path,WPBACKITUP__CONTENT_PATH. '/',$ignore)) {
		$logger->log('Error: Content folder was not copied successfully');
		write_warning_status('error219');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('Content folder copied successfully');
}


//Restore other content folders
function restore_other_folders($source_path,$target_other_root,$ignore) {
	global $logger;
	$logger->log_info(__METHOD__,'Begin');

	$target_other_root =rtrim($target_other_root, '/') . '/';

	$logger->log_info(__METHOD__,'Source Path:' .$source_path);
	$logger->log_info(__METHOD__,'Target Path:' .$target_other_root);
	$logger->log_info(__METHOD__,'Ignore:');
	$logger->log($ignore);

	foreach(glob($source_path. '*',GLOB_ONLYDIR ) as $dir){
		$source_other_folder=$dir .'/';
		$target_other_folder = $target_other_root .basename($dir);

		if( ! in_array(basename($dir), $ignore) ) {
			$logger->log_info(__METHOD__,'Restoring FROM:' .$source_other_folder );
			$logger->log_info(__METHOD__,'Restoring TO:' .$target_other_folder);

			restore_folder($source_other_folder,$target_other_folder);
		}
	}

	//Restore the files in the root
	$logger->log_info(__METHOD__,'Restore other files in wpcontent root');
	$files = array_filter(glob($source_path. '*'), 'is_file');
	$filesystem = new WPBackItUp_FileSystem($logger);
	foreach ($files as $file){
		$target_other_file = $target_other_root .basename($file);

		if( ! in_array(basename($file), $ignore) ) {
			$logger->log_info( __METHOD__, 'Restore file from:' . $file );
			$logger->log_info( __METHOD__, 'Restore file to:' . $target_other_file );

			if (! $filesystem->copy_file($file,$target_other_file) ){
				$logger->log('Error: File was not copied successfully');
				write_warning_status('error219');
				restore_database();
				delete_restore_folder();
				die();
			}
		}
	}

	$logger->log_info(__METHOD__,'All Others restored successfully.');
}

//validate other content folders
function validate_other_folders($source_path,$target_other_root,$ignore) {
	global $logger;
	//$logger->log_info(__METHOD__,'Begin');

	$target_other_root =rtrim($target_other_root, '/') . '/';

//	$logger->log_info(__METHOD__,'Source Path:' .$source_path);
//	$logger->log_info(__METHOD__,'Target Path:' .$target_other_root);
//	$logger->log_info(__METHOD__,'Ignore:');
//	$logger->log($ignore);

	foreach(glob($source_path. '*',GLOB_ONLYDIR ) as $dir){
		$source_other_folder=$dir .'/';
		$target_other_folder = $target_other_root .basename($dir);

		if( ! in_array(basename($dir), $ignore) ) {
//			$logger->log_info(__METHOD__,'Validate FROM:' .$source_other_folder );
//			$logger->log_info(__METHOD__,'Validate TO:' .$target_other_folder);

			validate_wpcontent($source_other_folder,$target_other_folder);
		}
	}

	//$logger->log_info(__METHOD__,'Validate other files in wpcontent root');
	$files = array_filter(glob($source_path. '*'), 'is_file');
	foreach ($files as $file){
		$target_other_file = $target_other_root .basename($file);

		if( ! in_array(basename($file), $ignore) ) {
//			$logger->log_info( __METHOD__, 'Validate file from:' . $file );
//			$logger->log_info( __METHOD__, 'Validate file to:' . $target_other_file );

			if (! file_exists($target_other_file) ){
				$logger->log_info(__METHOD__,'DIFF file doesnt exist: ' .$target_other_file);
			}
		}
	}

}

//Restore content folder
function restore_folder($source_path,$target_path){
	global $logger;
	$logger->log('Copy content folder from:' .$source_path);
	$logger->log('Copy content folder to:' .$target_path);

	//add the / if needed
	$target_path =rtrim($target_path, '/') . '/';

	$filesystem = new WPBackItUp_FileSystem($logger);
	$ignore =  array(WPBACKITUP__PLUGIN_FOLDER, WPBACKITUP__BACKUP_FOLDER,WPBACKITUP__RESTORE_FOLDER, 'status.log','debug.log', WPBACKITUP__SQL_DBBACKUP_FILENAME, 'backupsiteinfo.txt');

	//Make sure the root exists
	if(!$filesystem->create_dir($target_path)) {
		$logger->log('Error: Cant create root folder');
		write_warning_status('error219');
		restore_database();
		delete_restore_folder();
		die();
	}

	if(!$filesystem->recursive_copy($source_path,$target_path,$ignore)) {
		$logger->log('Error: Content folder was not copied successfully');
		write_warning_status('error219');
		restore_database();
		delete_restore_folder();
		die();
	}
	$logger->log('Content folder copied successfully');
}

//Restore all wp content from zip
function validate_wpcontent($source_dir_path,$target_dir_path){
    global $logger;
//    $logger->log('Validate content folder TO:' .$source_dir_path);
//    $logger->log('Validate content folder FROM:' .$target_dir_path);

    $ignore = array(WPBACKITUP__PLUGIN_FOLDER,'debug.log','backupsiteinfo.txt','db-backup.sql');
    $filesystem = new WPBackItUp_FileSystem($logger);
    if(!$filesystem->recursive_validate($source_dir_path. '/', $target_dir_path . '/',$ignore)) {
        $logger->log_error(__METHOD__,'Content folder is not the same as backup.');
    }else{
	    $logger->log_info(__METHOD__,'Success: No differences in content folder:' .$target_dir_path);
    }

    //$logger->log('Content folder validation complete.');
}

//Delete the restoration directory
function cleanup_restore_folder($restoration_dir_path){
    global $logger;
    $logger->log('Cleanup the restore folder: ' .$restoration_dir_path);
	if(!delete_restore_folder()) {
		$logger->log('Error: Cleanup restore folder failed: ' .$restoration_dir_path);
		write_warning_status('error220'); //NOT fatal
	} else {
		$logger->log('Restore folder cleaned successfully: ' .$restoration_dir_path);
	}	
}
function update_permalinks(){
    global $wp_rewrite, $logger;
    try {

	    $wp_rewrite->flush_rules( true );//Update permalinks -  hard flush

    }catch(Exception $e) {
        $logger->log_error(__METHOD__,'Exception: ' .$e);
        return false;
    }
    $logger->log_info(__METHOD__,'Permalinks updated.');
    return true;
}