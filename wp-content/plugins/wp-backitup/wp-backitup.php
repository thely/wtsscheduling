<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP Backitup
 * 
 * @package WP Backitup 
 * 
 * @author cssimmon
 *
 */
/*
Plugin Name: WP Backitup
Plugin URI: http://www.wpbackitup.com
Description: Backup your content, settings, themes, plugins and media in just a few simple clicks.
Version: 1.9
Author: Chris Simmons
Author URI: http://www.wpbackitup.com
License: GPL3

Copyright 2012-2014 WPBackItUp  (email : support@wpbackitup.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define( 'WPBACKITUP__NAMESPACE', 'wp-backitup' );
define( 'WPBACKITUP__VERSION', '1.9');
define( 'WPBACKITUP__DEBUG', false );
define( 'WPBACKITUP__MINIMUM_WP_VERSION', '3.0' );
define( 'WPBACKITUP__ITEM_NAME', 'WP Backitup' ); 
define( 'WPBACKITUP__FRIENDLY_NAME', 'WP BackItUp' );

define( 'WPBACKITUP__CONTENT_PATH', WP_CONTENT_DIR  );

define( 'WPBACKITUP__SITE_URL', 'http://www.wpbackitup.com');
define( 'WPBACKITUP__SECURESITE_URL', 'https://www.wpbackitup.com' );

define( 'WPBACKITUP__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPBACKITUP__PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPBACKITUP__PLUGIN_FOLDER',basename(dirname(__FILE__)));

define( 'WPBACKITUP__BACKUP_FOLDER', 'wpbackitup_backups' );
define( 'WPBACKITUP__BACKUP_URL', content_url() . "/" .WPBACKITUP__BACKUP_FOLDER);
define( 'WPBACKITUP__BACKUP_PATH',WPBACKITUP__CONTENT_PATH  . '/' . WPBACKITUP__BACKUP_FOLDER);

define( 'WPBACKITUP__RESTORE_FOLDER', 'wpbackitup_restore' );
define( 'WPBACKITUP__RESTORE_PATH',WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER);

define( 'WPBACKITUP__PLUGINS_ROOT_PATH',WP_PLUGIN_DIR );
define( 'WPBACKITUP__THEMES_ROOT_PATH',get_theme_root() );

define( 'WPBACKITUP__SQL_DBBACKUP_FILENAME', 'db-backup.sql');

register_activation_hook( __FILE__, array( 'WPBackitup_Admin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPBackitup_Admin', 'deactivate' ) );


function wpbackitup_modify_cron_schedules($schedules) {
    $schedules['weekly'] = array('interval' => 604800, 'display' => 'Once Weekly');
    $schedules['monthly'] = array('interval' => 2592000, 'display' => 'Once Monthly');
    $schedules['every4hours'] = array('interval' => 14400, 'display' => sprintf(__('Every %s hours', 'wpbackitup'), 4));
    $schedules['every8hours'] = array('interval' => 28800, 'display' => sprintf(__('Every %s hours', 'wpbackitup'), 8));
    return $schedules;
}

add_filter('cron_schedules', 'wpbackitup_modify_cron_schedules', 30);


// The checks here before loading are for performance only - unless one of those conditions is met, then none of the hooks will ever be used
if (!is_admin() && (!defined('DOING_CRON') || !DOING_CRON) && (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) && empty($_SERVER['SHELL']) && empty($_SERVER['USER'])) return;

require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-wpbackitup-admin.php' );
require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-logger.php' );

global $WPBackitup;
$WPBackitup = WPBackitup_Admin::get_instance();
$WPBackitup->initialize();



