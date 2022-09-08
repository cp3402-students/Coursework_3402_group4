<?php
/**
 * Simply Schedule Appointments Settings Installed.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Settings Installed.
 *
 * @since 0.1.0
 */
class SSA_Settings_Installed extends SSA_Settings_Schema {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		parent::__construct();
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks() {
	}

	protected $slug = 'installed';

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2020-09-14',
			'fields' => array(
				
			),
		);

		return $this->schema;
	}

	public function get_computed_schema() {
		if ( !empty( $this->computed_schema ) ) {
			return $this->computed_schema;
		}

		$this->computed_schema = array(
			'fields' => array(
				'blackout_dates' => array(
					'name' => 'blackout_dates',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Blackout_Dates',
				),

				'advanced_scheduling' => array(
					'name' => 'advanced_scheduling',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Advanced_Scheduling_Settings',
				),

				'calendar_events' => array(
					'name' => 'calendar_events',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Calendar_Events_Settings',
				),

				'capacity' => array(
					'name' => 'capacity',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Capacity',
				),

				'developer' => array(
					'name' => 'developer',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Developer_Settings',
				),

				'google_calendar' => array(
					'name' => 'google_calendar',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Google_Calendar',
				),

				'license' => array(
					'name' => 'license',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_License',
				),

				'mailchimp' => array(
					'name' => 'mailchimp',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Mailchimp',
				),

				'notifications' => array(
					'name' => 'notifications',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Notifications',
				),

				'offline_payments' => array(
					'name' => 'offline_payments',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Offline_Payments',
				),

				'payments' => array(
					'name' => 'payments',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Payments',
				),

				'paypal' => array(
					'name' => 'paypal',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Paypal',
				),

				'reminders' => array(
					'name' => 'reminders',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Reminders',
				),

				'sms' => array(
					'name' => 'sms',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Sms',
				),

				'staff' => array(
					'name' => 'staff',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Staff',
				),

				'stripe' => array(
					'name' => 'stripe',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Stripe',
				),

				'styles' => array(
					'name' => 'styles',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Styles',
				),

				'tracking' => array(
					'name' => 'tracking',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Tracking',
				),

				'translation' => array(
					'name' => 'translation',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Translation',
				),

				'webhooks' => array(
					'name' => 'webhooks',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Webhooks',
				),

				'webex' => array(
					'name' => 'webex',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Webex',
				),

				'woocommerce' => array(
					'name' => 'woocommerce',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Woocommerce',
				),

				'zoom' => array(
					'name' => 'zoom',
					'get_function' => 'class_exists',
					'get_input' => 'SSA_Zoom',
				),

			),
		);

		return $this->computed_schema;
	}

	public function is_installed( $slug ) {
		$installed = $this->plugin->settings_installed->get();
		if ( !empty( $installed[$slug] ) ) {
			return true;
		}

		return false;
	}

	public function is_enabled( $slug ) {
		if ( ! $this->is_installed( $slug ) ) {
			return false;
		}

		if ( in_array( $slug, array(
			'global',
			'styles',
			'developer',
			'capacity',
		) ) ) {
			return true;
		}

		if ( ! empty( $this->plugin->$slug->parent_slug ) ) {
			if ( ! $this->is_enabled( $this->plugin->$slug->parent_slug ) ) {
				return false;
			}
		}


		$settings = $this->plugin->settings->get();
		if ( !empty( $settings[$slug]['enabled'] ) ) {
			return true;
		}

		return false;
	}

	public function is_activated( $slug, $force_check = false ) {
		if ( ! $this->is_enabled( $slug ) ) {
			return false;
		}

		if ( ! method_exists( $this->plugin->$slug, 'is_activated' ) ) {
			return true;
		}

		$is_activated = $this->plugin->$slug->is_activated( $force_check );
		if ( empty( $is_activated ) ) {
			return false;
		}

		return true;
	}
}
