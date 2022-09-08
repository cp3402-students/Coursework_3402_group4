<?php
/**
 * Simply Schedule Appointments Missing.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Missing.
 *
 * @since 0.1.0
 */
class SSA_Missing {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
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
	 * @since  0.1.0
	 */
	public function hooks() {

	}

	public function __get( $name ) {
		return $this;
	}

	public function __call( $name, $args ) {
		return null;
	}

	public static function __callStatic( $name, $args ) {
		return null;
	}
}
