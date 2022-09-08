<?php
/**
 * Simply Schedule Appointments Settings Global.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Settings Global.
 *
 * @since 0.0.3
 */
class SSA_Settings_Global extends SSA_Settings_Schema {
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

	protected $slug = 'global';

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2021-03-23',
			'fields' => array(
				'enabled' => array(
					'name' => 'enabled',
					'default_value' => true,
				),

				'wizard_completed' => array(
					'name' => 'wizard_completed',
					'default_value' => '',
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'timezone_string' => array(
					'name' => 'timezone_string',
					'default_value' => ( get_option( 'timezone_string', 'UTC' ) ) ? get_option( 'timezone_string', 'UTC' ) : 'UTC',
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'country_code' => array(
					'name' => 'country_code',
					'default_value' => '',
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'start_of_week' => array(
					'name' => 'start_of_week',
					'default_value' => get_option( 'start_of_week', 1 ),
					'validate_callback' => array( 'SSA_Validation', 'validate_weekday' ),
				),

				'date_format' => array(
					'name' => 'date_format',
					'default_value' => get_option( 'date_format' ),
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'time_format' => array(
					'name' => 'time_format',
					'default_value' => get_option( 'time_format' ),
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'admin_email' => array(
					'name' => 'admin_email',
					'default_value' => get_option( 'admin_email' ),
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
					'required_capability' => 'ssa_manage_site_settings',
				),

				'admin_phone' => array(
					'name' => 'admin_phone',
					'default_value' => get_option( 'admin_phone' ),
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
					'required_capability' => 'ssa_manage_site_settings',
				),

				'staff_name' => array(
					'name' => 'staff_name',
					'default_value' => wp_get_current_user()->display_name,
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'company_name' => array(
					'name' => 'company_name',
					'default_value' => get_bloginfo( 'name' ),
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'last_updated' => array(
					'name' => 'last_updated',
					'default_value' => null,
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),

				'booking_post_id' => array(
					'name' => 'booking_post_id',
					'default_value' => '',
					'validate_callback' => array( 'SSA_Validation', 'validate_string' ),
				),
			),
		);

		return $this->schema;
	}

	public function get_computed_schema() {
		if ( !empty( $this->computed_schema ) ) {
			return $this->computed_schema;
		}

		$this->computed_schema = array(
			'version' => '2017-08-08',
			'fields' => array(
				'date_format_moment' => array(
					'name' => 'date_format_moment',

					'get_function' => array( 'SSA_Utils', 'php_to_moment_format' ),
					'get_input_path' => 'date_format',

					// Deprecated, expecting php values only
					// 'set_function' => array( 'SSA_Utils', 'moment_to_php_format' ),
					// 'set_result_path' => 'date_format',
				),
				'time_format_moment' => array(
					'name' => 'time_format_moment',

					'get_function' => array( 'SSA_Utils', 'php_to_moment_format' ),
					'get_input_path' => 'time_format',

					// Deprecated, expecting php values only
					// 'set_function' => array( 'SSA_Utils', 'moment_to_php_format' ),
					// 'set_result_path' => 'time_format',
				),
				// 'booking_post_permalink' => array(
				// 	'name' => 'booking_post_permalink',

				// 	'get_function' => 'get_permalink',
				// 	'get_input_path' => 'booking_post_id',
				// ),


				'locale' => array(
					'name' => 'locale',
					'get_function' => array( 'SSA_Translation', 'get_locale' ),
					'get_input' => true,
				),

			),
		);

		return $this->computed_schema;
	}

	public function get_timezone_string() {
		$settings = $this->plugin->settings->get();
		$timezone_string = $settings['global']['timezone_string'];
		return $timezone_string;
	}

	public function get_datetimezone() {
		$timezone_string = $this->get_timezone_string();
		$datetimezone = new DateTimeZone( $timezone_string );
		return $datetimezone;
	}

	public static function get_date_format_moment( $args ) {
		$args = shortcode_atts( array(
			'time_format' => '',
		), $args );

		return SSA_Utils::moment_format( $args['time_format'] );
	}

	public static function set_date_format_moment( $args ) {
		$args = shortcode_atts( array(
			'time_format' => '',
		), $args );

		SSA_Utils::moment_to_php_format( $args['time_format'] );
	}

}
