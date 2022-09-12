<?php
/*
Plugin Name: WP on AWS
Plugin URI: https://www.seahorse-data.com/
Description: Easily Migrate and Manage sites in AWS without any AWS know-how! Migrate Now for FREE!
Author: Seahorse Data
Author URI: https://seahorse-data.com/
Text Domain: migrate-2-aws
Contributors: wpseahorse, echomedia
Tags: migration, AWS, migrate WordPress, manage AWS
Requires at least: 4.8
Tested up to: 6.0
Stable tag: 4.8
Domain Path: /languages/
Version: 4.1.0
License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

global $wpdb;
if ($wpdb) {
    define('WPM2AWS_ABSPATH', 'You are not authorised to access this file.');
} else {
    wp_die('You are not authorised to access this file.');
}

define('WPM2AWS_VERSION', '4.1.0');

define('WPM2AWS_REQUIRED_WP_VERSION', '4.8');

define('WPM2AWS_PLUGIN', __FILE__);

define('WPM2AWS_PLUGIN_BASENAME', plugin_basename(WPM2AWS_PLUGIN));

define('WPM2AWS_PLUGIN_NAME', trim(dirname(WPM2AWS_PLUGIN_BASENAME), '/'));

define('WPM2AWS_PLUGIN_DIR', untrailingslashit(dirname(WPM2AWS_PLUGIN)));

define('WPM2AWS_PLUGIN_MODULES_DIR', WPM2AWS_PLUGIN_DIR . '/modules');

define('WPM2AWS_PLUGIN_AWS_RESOURCE', 'wp-migrate-2-aws');

define('WPM2AWS_SEAHORSE_WEBSITE_URL', 'https://www.seahorse-data.com');
define('WPM2AWS_SEAHORSE_EMAIL_ADDRESS', 'info@seahorse-data.ie');

// Determine & set the URL to be used for API requests to the Remote Migrations Middleman
if (defined('WPM2AWS_MIGRATIONS_API_URL_EXTERNAL') === true) {
    define('WPM2AWS_MIGRATIONS_API_URL', WPM2AWS_MIGRATIONS_API_URL_EXTERNAL);
} else {
    define('WPM2AWS_MIGRATIONS_API_URL', 'https://wponaws.migration.seahorse-data.com');
}

// Determine & set the URL to be used for API requests to the Remote Console
if (defined('WPM2AWS_CONSOLE_API_URL_EXTERNAL') === true) {
    define('WPM2AWS_CONSOLE_API_URL', WPM2AWS_CONSOLE_API_URL_EXTERNAL);
} else {
    define('WPM2AWS_CONSOLE_API_URL', 'https://wponaws.manage-aws.seahorse-data.com');
}

// default region - editible in future versions
define('WPM2AWS_PLUGIN_AWS_REGION', 'eu-west-1');

// default launch instance size
define('WPM2AWS_PLUGIN_AWS_LIGHTSAIL_SIZE', 'Small');

// default AWS a/c no
define('WPM2AWS_PLUGIN_AWS_NUMBER', '786540766804');

// Overall Page Title
define('WPM2AWS_PAGE_TITLE_MAIN', 'WP on AWS');

// Manage Page Title
define('WPM2AWS_PAGE_TITLE_MANAGE', 'Manage AWS');

// Create Clone Title
define('WPM2AWS_PAGE_TITLE_CLONE', 'Create Clone');

// Create Staging Page Title
define('WPM2AWS_PAGE_TITLE_STAGING', 'Create Staging');

// Upgrade Stack Actions Page Title
define('WPM2AWS_PAGE_TITLE_UPGRADE', 'Upgrade  Stack');



// not used
define('WPM2AWS_MAX_DB_EXPORT', 0);

// Path (parent) where DB tables export to
define('WPM2AWS_DB_EXPORT_PATH', WPM2AWS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR);
// define('WPM2AWS_DB_EXPORT_PATH', WPM2AWS_PLUGIN_DIR . '/libraries/db/');

// Path where DB tables export to
define('WPM2AWS_DB_TABLES_EXPORT_PATH', WPM2AWS_DB_EXPORT_PATH . 'tables');

// New Plugin Directory where Zipped Plugin Directories are stored (temp)
define('WPM2AWS_ZIP_EXPORT_PATH', 'wpm2aws-zips');

// Path to internal Log File
define('WPM2AWS_LOG_FILE_PATH', WPM2AWS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'log.txt');
// define('WPM2AWS_LOG_FILE_PATH', WPM2AWS_PLUGIN_DIR . '/inc/log.txt');

// Path to file where Zipped Dirs are registered
define('WPM2AWS_ZIP_LOG_FILE_PATH', WPM2AWS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'zipLog.txt');

// Path to file where Dowbnloaded Zips are registered
define('WPM2AWS_ZIP_DL_FILES_LOG_FILE_PATH', WPM2AWS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'downloadZipLog.txt');

// Path to key Download
define('WPM2AWS_KEY_DOWNLOAD_PATH', WPM2AWS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'public_access_key.pem');

// User type according to license
$license_user_type = get_option('wpm2aws_valid_licence_type');
if($license_user_type=="self-managed"){
    define('WPM2AWS_USER_TYPE',$license_user_type);
}
else{
    define('WPM2AWS_USER_TYPE','trial-user');
}

define('WPM2AWS_FEATURE_AVAILABLE_AFTER_MIGRATION', 'This features will be available on the destination site after migration');
define('WPM2AWS_FEATURE_UNAVAILABLE', 'This feature is currently unavailable.');
define('WPM2AWS_FEATURE_INACCESSIBLE', 'Your licence type does not allow access to this feature.<br><br></br>Consider <a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL. '/pricing/" target="_blank">upgrading your licence</a> to get full access to <strong>WP on AWS</strong> and experience all the functionality it has to offer.');
// define('WPM2AWS_ZIP_LOG_FILE_PATH', WPM2AWS_PLUGIN_DIR . '/inc/zipLog.txt');

// List of Themes not included in File Upload
// define(
//     'WPM2AWS_EXCLUDE_WP_CORE_THEMES',
//     array(
//         'twentyfifteen',
//         'twentysixteen',
//         'twentyseventeen',
//         'twentyeighteen',
//         'twentynineteen',
//         'twentytwenty'
//     )
// ); // Removed due to non-compatibility with pre-php-7




// List of Bundle Types Allowed // Removed due to non-compatibility with pre-php-7
// define('WPM2AWS_INSTANCE_BUNDLE_TYPES', array('LINUX_UNIX'));

// define('WPM2AWS_TESTING', true);

// define('WPM2AWS_DEBUG', true);

// define('WPM2AWS_DEV', true);

// define('WPM2AWS_TEST_FAILURE', true);

// define('WPM2AWS_TESTING_BACKGROUND_PROCESS', true);

/* Management Console */
define('WPM2AWS_CONSOLE_DEV', true);

if (defined('WPM2AWS_MIGRATION_DEST') && WPM2AWS_MIGRATION_DEST === $_SERVER['SERVER_ADDR']) {
    define('WPM2AWS_MIGRATED_SITE', true);
}

// Size in MBs
define('WPM2AWS_LIMIT_ZIP_DIR_SIZE', true);
define('WPM2AWS_MAX_DIR_SIZE_ZIP', '128'); // Size of individual file, in MBs
define('WPM2AWS_MAX_DIR_SIZE_UPLOADS', '1000'); // Size of directory contents, in MBs
define('WPM2AWS_MAX_SAFE_DB_SIZE', '500');
define('WPM2AWS_MAX_SAFE_DB_TABLE_SIZE', '128');
define('WPM2AWS_OVERSIZED_DATABASE_ROW_LIMIT', 1000);


// define('WPM2AWS_CONSOLE_LOG_EVENTS', array(
//         'StartInstance',
//         'StopInstance',
//         'PutAlarm',
//         'Created',
//         'RunInstance'
//     )
// ); // Removed due to non-compatibility with pre-php-7

if (! defined('WPM2AWS_LOAD_JS')) {
    define('WPM2AWS_LOAD_JS', true);
}

if (! defined('WPM2AWS_LOAD_CSS')) {
    define('WPM2AWS_LOAD_CSS', true);
}

if (! defined('WPM2AWS_AUTOP')) {
    define('WPM2AWS_AUTOP', true);
}

if (! defined('WPM2AWS_USE_PIPE')) {
    define('WPM2AWS_USE_PIPE', true);
}


// Single Site: Administrator
// Multi-site: Super Admin
if (! defined('WPM2AWS_MIGRATE_CAPABILITY')) {
    define('WPM2AWS_MIGRATE_CAPABILITY', 'update_core');
}

if (! defined('WPM2AWS_VERIFY_NONCE')) {
    define('WPM2AWS_VERIFY_NONCE', false);
}

// Allow for Migrate with DB Prefix
if ('wp_' !== $wpdb->prefix) {
    define('WPM2AWS_ADJUST_PREFIX', $wpdb->prefix);
}


/*
* Multi-Site
*
* Allow For Option
* To Launch As MultiSite
* Pending UI checkbox Interface
*
* 0 = Single/Standard Site
* 1 = Launch from Single to MultiSite
*/
define('WPM2AWS_LAUNCH_AS_MULTI_SITE', 0);


add_action('init', 'wpm2aws_launch_script');
function wpm2aws_launch_script()
{
    // Only run this plugin in Admin Pages
    if (is_admin() && is_user_logged_in() && current_user_can('manage_options')) {
        // Run Permissions Check
        require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/permissions.class.php';
        try {
            $wpm2aws = new WPM2AWS_Permissions();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        try {
            $permitted = $wpm2aws->runPermissionsCheck();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        // At this point the user has passed the Permissions Check
        // Run Plugin
        require_once WPM2AWS_PLUGIN_DIR . '/settings.php';
        try {
            $wpm2aws = new WPM2AWS_Settings();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        try {
            $wpm2aws->run();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }
    } else {
        if (isset($_GET['wpm2aws-logs']) && isset($_GET['wpm2aws-logs-token'])) {
            $logs = sanitize_text_field($_GET['wpm2aws-logs']);
            $token = sanitize_text_field($_GET['wpm2aws-logs-token']);

            if (false === get_option('wpm2aws-iamid')) {
                return;
            }
            if ($logs !== 'true') {
                return;
            }

            if ($token !== get_option('wpm2aws-iamid')) {
                return;
            }
            if (!file_exists(WPM2AWS_LOG_FILE_PATH)) {
                return;
            }
            $logs = file_get_contents(WPM2AWS_LOG_FILE_PATH);
            $logs = str_replace("\r", '<br>', $logs);

            exit($logs);
        }
        return;
    }
}

// Add Class for plugin specifi styles to admin body
add_filter( 'admin_body_class', 'wpm2aws_admin_body_class' );
function wpm2aws_admin_body_class( $classes ) {
    $classes .= 'wpm2aws-body';
    return $classes;
}