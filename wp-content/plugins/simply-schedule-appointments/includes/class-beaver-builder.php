<?php
/**
 * Simply Schedule Appointments Beaver Builder.
 *
 * @since   3.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Elementor.
 *
 * @since 3.1.0
 */
class SSA_Beaver_Builder {
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
	 * Setup hooks if the builder is installed and activated.
	 */
	public function hooks() {
		if ( ! class_exists( 'FLBuilder' ) ) {
			return;
		}

		// Load custom modules.
		add_action( 'init', array( $this, 'load_modules' ) );
	}

	/**
	 * Loads our custom modules.
	 */
	public function load_modules() {
		// Upcoming Appointments Module
		require_once ( __DIR__ . '/beaver-builder/modules/upcoming-appointments/upcoming-appointments.php' );
		// Booking Form Module
		require_once ( __DIR__ . '/beaver-builder/modules/booking/booking.php' );
	}

}