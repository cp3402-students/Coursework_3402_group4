<?php
/**
 * Simply Schedule Appointments Validation.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Validation.
 *
 * @since 0.0.3
 */
class SSA_Validation {
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

	public static function validate_numeric( $value ) {
		return is_numeric( $value );
	}

	public static function validate_string( $value ) {
		return !is_numeric( $value ) && is_string( $value );
	}

	public static function validate_weekday( $value ) {
		return in_array( $value, array(
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
			'Sunday',
		) );
	}
}
