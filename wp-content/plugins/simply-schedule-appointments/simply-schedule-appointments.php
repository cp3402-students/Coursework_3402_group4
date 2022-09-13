<?php
/**
 * Plugin Name: Simply Schedule Appointments
 * Plugin URI:  https://simplyscheduleappointments.com
 * Description: Easy appointment scheduling
 * Version:     1.5.8.2
 * Requires PHP: 7.0
 * Author:      N Squared
 * Author URI:  http://nsqua.red
 * Donate link: https://simplyscheduleappointments.com
 * License:     GPLv2
 * Text Domain: simply-schedule-appointments
 * Domain Path: /languages
 *
 * @link    https://simplyscheduleappointments.com
 *
 * @package Simply_Schedule_Appointments
 * @version 1.5.8.2
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2017 N Squared (email : support@simplyscheduleappointments.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Autoloads files with classes when needed.
 *
 * @since  0.0.0
 * @param  string $class_name Name of the class being requested.
 */
if ( ! function_exists( 'ssa_autoload_classes' ) ) {
	function ssa_autoload_classes( $class_name ) {

		// If our class doesn't have our prefix, don't load it.
		if ( 0 !== strpos( $class_name, 'SSA_' ) ) {
			return;
		}

		// Set up our filename.
		$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'SSA_' ) ) ) );

		// Include our file.
		Simply_Schedule_Appointments::include_file( 'includes/class-' . $filename );
	}
}

spl_autoload_register( 'ssa_autoload_classes' );
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	if ( version_compare( phpversion(), '7.0', '>=' ) ) {
	 	include_once __DIR__ . '/vendor/autoload.php';
	 }
 } ;

define( 'SSA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main initiation class.
 *
 * @since  0.0.0
 */
final class Simply_Schedule_Appointments {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	const VERSION = '1.5.8.2';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  0.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    Simply_Schedule_Appointments
	 * @since  0.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of SSA_Notification_Model
	 *
	 * @since3.9.4.beta1
	 * @var SSA_Notification_Model
	 */
	protected $notification_model;

	/**
	 * Instance of SSA_Debug
	 *
	 * @since4.0.1
	 * @var SSA_Debug
	 */
	protected $debug;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.0.0
	 * @return  Simply_Schedule_Appointments A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  0.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

		require $this->dir( 'includes/lib/td-health-check/health-check.php' );
		$this->health_check = new TD_Health_Check();
		require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

		if ( ! defined( 'WP_SENTRY_VERSION' ) ) {
			define( 'WP_SENTRY_VERSION', 'v'.self::VERSION );
		}
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.0.0
	 */
	public function plugin_classes() {
		$classes = array(
			'settings' => 'SSA_Settings',
			'bootstrap' => 'SSA_Bootstrap',
			'missing' => 'SSA_Missing',
			'upgrade' => 'SSA_Upgrade',
			'utils' => 'SSA_Utils',
			'validation' => 'SSA_Validation',
			'capabilities' => 'SSA_Capabilities',
			'hooks' => 'SSA_Hooks',

			'appointment_model' => 'SSA_Appointment_Model',
			'appointment_meta_model' => 'SSA_Appointment_Meta_Model',
			'appointment_type_model' => 'SSA_Appointment_Type_Model',
			'availability_model' => 'SSA_Availability_Model',
			'availability_external_model' => 'SSA_Availability_External_Model',
			'availability_functions' => 'SSA_Availability_Functions',
			'availability_default' => 'SSA_Availability_Default',
			'availability_cache' => 'SSA_Availability_Cache',
			'availability_cache_invalidation' => 'SSA_Availability_Cache_Invalidation',
			
			'capacity_settings' => 'SSA_Capacity_Settings',
			'capacity' => 'SSA_Capacity',
			'cache' => 'SSA_Cache',

			'debug' => 'SSA_Debug',

			'customers' => 'SSA_Customers',
			'customer_information' => 'SSA_Customer_Information',

			'scheduling_max_per_day' => 'SSA_Scheduling_Max_Per_Day',

			'settings_installed' => 'SSA_Settings_Installed',
			'settings_global' => 'SSA_Settings_Global',


			'gcal_exporter' => 'SSA_Gcal_Exporter',
			'notifications' => 'SSA_Notifications',
			'notifications_settings' => 'SSA_Notifications_Settings',
			'notification_model' => 'SSA_Notification_Model',
			'notices' => 'SSA_Notices',
			'gcal_exporter' => 'SSA_Gcal_Exporter',
			'csv_exporter' => 'SSA_CSV_Exporter',

			'calendar_events_settings' => 'SSA_Calendar_Events_Settings',

			'async_action_model' => 'SSA_Async_Action_Model',

			/* Features */
			'developer_settings' => 'SSA_Developer_Settings',

			'license_settings' => 'SSA_License_Settings',
			'license' => 'SSA_License',

			'advanced_scheduling_settings' => 'SSA_Advanced_Scheduling_Settings',
			'advanced_scheduling_availability' => 'SSA_Advanced_Scheduling_Availability',

			'blackout_dates_settings' => 'SSA_Blackout_Dates_Settings',
			'blackout_dates' => 'SSA_Blackout_Dates',

			'elementor' => 'SSA_Elementor',
			'beaver_builder' => 'SSA_Beaver_Builder',
			'divi' => 'SSA_Divi',
			'forms' => 'SSA_Forms',
			'formidable' => 'SSA_Formidable',

			'staff_settings' => 'SSA_Staff_Settings',
			'staff' => 'SSA_Staff',

			'staff_model' => 'SSA_Staff_Model',
			'staff_appointment_model' => 'SSA_Staff_Appointment_Model',
			'staff_appointment_type_model' => 'SSA_Staff_Appointment_Type_Model',
			'staff_availability' => 'SSA_Staff_Availability',

			'google_calendar_settings' => 'SSA_Google_Calendar_Settings',
			'google_calendar' => 'SSA_Google_Calendar',
			'google_calendar_admin' => 'SSA_Google_Calendar_Admin',

			'gravityforms' => 'SSA_Gravityforms',

			'payment_model' => 'SSA_Payment_Model',

			'mailchimp_settings' => 'SSA_Mailchimp_Settings',
			'mailchimp' => 'SSA_Mailchimp',

			'offline_payments_settings' => 'SSA_Offline_Payments_Settings',
			'offline_payments' => 'SSA_Offline_Payments',

			'payments_settings' => 'SSA_Payments_Settings',
			'payments' => 'SSA_Payments',

			'paypal_settings' => 'SSA_Paypal_Settings',
			'paypal' => 'SSA_Paypal',

			'reminders' => 'SSA_Reminders',

			'sms' => 'SSA_Sms',
			'sms_settings' => 'SSA_Sms_Settings',

			'stripe_settings' => 'SSA_Stripe_Settings',
			'stripe' => 'SSA_Stripe',

			'styles_settings' => 'SSA_Styles_Settings',
			'styles' => 'SSA_Styles',

			'support' => 'SSA_Support',
			'support_status' => 'SSA_Support_Status',

			'templates' => 'SSA_Templates',

			'tracking_settings' => 'SSA_Tracking_Settings',
			'tracking' => 'SSA_Tracking',

			'translation' => 'SSA_Translation',
			'translation_settings' => 'SSA_Translation_Settings',

			'users' => 'SSA_Users',

			'webhooks_settings' => 'SSA_Webhooks_Settings',
			'webhooks' => 'SSA_Webhooks',

			'web_meetings' => 'SSA_Web_Meetings',

			'woocommerce_settings' => 'SSA_Woocommerce_Settings',
			'woocommerce' => 'SSA_Woocommerce',

			'webex' => 'SSA_Webex',
			'webex_settings' => 'SSA_Webex_Settings',

			'zoom_settings' => 'SSA_Zoom_Settings',
			'zoom' => 'SSA_Zoom',

			'shortcodes' => 'SSA_Shortcodes',
			'block_booking' => 'SSA_Block_Booking',
			'block_upcoming_appointments' => 'SSA_Block_Upcoming_Appointments',
			'filesystem' => 'SSA_Filesystem',
			'wp_admin' => 'SSA_Wp_Admin',
			'action_scheduler' => 'SSA_Action_Scheduler'

			// NO API CLASSES SHOULD BE HERE (should be defined in rest_api_init hook)
		);

		include __DIR__ . '/includes/class-exception.php';

		foreach ($classes as $variable_name => $class_name) {
			if ( class_exists( $class_name ) ) {
				$this->$variable_name = new $class_name( $this );
			}
		}
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.0.2
	 */
	public function rest_api_init() {
		$classes = array(
			'settings_api' => 'SSA_Settings_Api',
			'notices_api' => 'SSA_Notices_Api',
			'license_api' => 'SSA_License_Api',
			'templates_api' => 'SSA_Templates_Api',

			'google_calendar_api' => 'SSA_Google_Calendar_Api',
			'notifications_api' => 'SSA_Notifications_Api',
			'mailchimp_api' => 'SSA_Mailchimp_Api',
			'sms_api' => 'SSA_Sms_Api',
			'support_status_api' => 'SSA_Support_Status_Api',
		);

		foreach ($classes as $variable_name => $class_name) {
			if ( class_exists( $class_name ) ) {
				$this->$variable_name = new $class_name( $this );
			}
		}
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  0.0.0
	 */
	public function hooks() {
		if ( ! function_exists( 'ray' ) ) {
			function ray() {
				// we need this empty function in case a ray() call mistakenly makes it into a production release
			}
		}
		$this->plugins_loaded();
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 0 );
	}
	/**
	 * Activate the plugin.
	 *
	 * @since  0.0.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  0.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	public function plugins_loaded() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		require $this->dir( 'includes/lib/td-util/td-util-init.php' );

		// Initialize plugin classes.
		$this->plugin_classes();

		// Load translated strings for plugin.
		load_plugin_textdomain( 'simply-schedule-appointments', false, dirname( $this->basename ) . '/languages/' );

		do_action( 'ssa_loaded');
	}

	/**
	 * Init hooks
	 *
	 * @since  0.0.0
	 */
	public function init() {

	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  0.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		if ( version_compare( phpversion(), '7.0', '<' ) ) {
			$this->activation_errors[] = 'Simply Schedule Appointments requires <strong>PHP version 7.0 or higher</strong>. Most WordPress hosts are supporting up to PHP 7, so your web host should easily be able to update you to PHP 7 if you contact them. <br />PHP 7+ is safer, faster, and best of all lets you use Simply Schedule Appointments :) <br /><a href="https://simplyscheduleappointments.com/">Learn More</a>';
			return false;
		}

		if ( !class_exists( '\League\Period\Period' ) ) {
			$this->activation_errors[] = 'Core library <code>Period</code> missing, please <a href="mailto:support@simplyscheduleappointments.com">contact support</a>';
		}

		if ( !empty( $this->activation_errors ) ) {
			return false;
		}

		// Handle edge case - with no permalinks set, all WP REST API calls will fail
		global $wp_rewrite;
		if ( !empty( $wp_rewrite ) && empty( $wp_rewrite->permalink_structure ) ) {
			$wp_rewrite->set_permalink_structure('/%postname%/');
			update_option( "rewrite_rules", FALSE );
			$wp_rewrite->flush_rules( true );
		}

		// Do checks for required classes / functions or similar.
		// Add detailed messages to $this->activation_errors array.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  0.0.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( 'Simply Schedule Appointments detected that your system does not meet the minimum requirements. We\'ve <a href="%s">deactivated</a> Simply Schedule Appointments to make sure nothing breaks.', 'simply-schedule-appointments' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<h4>' . implode( '</h4><br /><h4>', $this->activation_errors ) . '</h4>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<h3><?php echo wp_kses_post( $default_message ); ?></h3>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'mailchimp':
			case 'mailchimp_settings':
			case 'mailchimp_api':
			case 'appointments':
			case 'appointments_api':
			case 'appointments_db':
			case 'db':
			case 'appointment_meta_db':
			case 'appointment_types_db':
			case 'appointment_type_meta_db':
			case 'settings':
			case 'settings_api':
			case 'settings_global':
			case 'settings_installed':
			case 'validation':
			case 'bootstrap':
			case 'capabilities':
			case 'utils':
			case 'db_model':
			case 'appointment_model':
			case 'appointment_meta_model':
			case 'appointment_type_model':
			case 'wp_admin':
			case 'availability_model':
			case 'availability_external_model':
			case 'availability_functions':

			case 'availability_default':
			case 'availability_cache':

			case 'scheduling_max_per_day':
			case 'ics_exporter':
			case 'csv_exporter':
			case 'block_booking':
			case 'shortcodes':
			case 'appointment_object':
			case 'filesystem':
			case 'upgrade':
			case 'gcal_exporter':

			case 'notifications':
			case 'notifications_api':
			case 'notifications_settings':

			case 'license':
			case 'license_api':

			case 'async_action_model':

			case 'advanced_scheduling_settings':
			case 'advanced_scheduling_availability':

			case 'blackout_dates':
			case 'blackout_dates_settings':

			case 'capacity_settings':
			case 'capacity':
			case 'cache':

			case 'customer_information':
			case 'customers':

			case 'debug':

			case 'elementor':
			case 'beaver_builder':
			case 'divi':
			case 'forms':
			case 'formidable':

			case 'health_check':
			case 'hooks':

			case 'staff':
			case 'staff_settings':

			case 'staff_model':
			case 'staff_appointment_model':
			case 'staff_appointment_type_model':
			case 'staff_availability':

			case 'google_calendar':
			case 'google_calendar_admin':
			case 'google_calendar_settings':
			case 'google_calendar_api':

			case 'gravityforms':

			case 'notification_model':

			case 'payment_model':

			case 'payments':
			case 'payments_settings':
			case 'paypal':
			case 'paypal_settings':
			case 'stripe':
			case 'stripe_settings':
			case 'offline_payments':
			case 'offline_payments_settings':

			case 'sms':
			case 'sms_settings':

			case 'styles':
			case 'styles_settings':

			case 'templates':
			case 'templates_api':

			case 'tracking_settings':
			case 'tracking':

			case 'translation':
			case 'translation_settings':

			case 'webhooks':
			case 'webhooks_settings':

			case 'web_meetings':

			case 'woocommerce':
			case 'woocommerce_settings':

			case 'webex':
			case 'webex_settings':

			case 'zoom':
			case 'zoom_settings':

			case 'license_settings':
			case 'notices':
			case 'notices_api':

			case 'reminders':

			case 'support':
			case 'support_status':
			case 'support_status_api':

			case 'users':

			case 'action_scheduler':

			case 'missing':
				if ( property_exists( $this, $field ) && !is_null( $this->$field ) ) {
					return $this->$field;
				} else {
					return $this->missing;
				}
			default:
				return $this->missing;
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's template subdirectory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function template_subdirectory() {
		return apply_filters( 'ssa_template_subdirectory', 'ssa/' );
	}

	/**
	 * This plugin's url.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path.
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

	/**
	 * This plugin's version.
	 *
	 * @since 4.4.9
	 *
	 * @return string
	 */
	public function get_current_version() {
		return $this->__get( 'version' );
	}

	/**
	 * Update current plugin's version on the database.
	 *
	 * @since 4.4.9
	 *
	 * @return void
	 */
	public function store_current_version() {
		$version = $this->get_current_version();
		update_option( 'ssa_plugin_version', $version, false );
	}
}

/**
 * Grab the Simply_Schedule_Appointments object and return it.
 * Wrapper for Simply_Schedule_Appointments::get_instance().
 *
 * @since  0.0.0
 * @return Simply_Schedule_Appointments  Singleton instance of plugin class.
 */
function ssa() {
	return Simply_Schedule_Appointments::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( ssa(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( ssa(), '_activate' ) );
register_deactivation_hook( __FILE__, array( ssa(), '_deactivate' ) );

function ssa_is_debug() {
	if ( defined( 'SSA_DEBUG' ) && SSA_DEBUG ) {
		return true;
	}

	return false;
}

if ( defined( 'WP_CLI' ) && WP_CLI && method_exists( 'WP_CLI', 'add_command' ) ) {
	require_once( ssa()->dir( 'includes/class-cli.php' ) );
}
