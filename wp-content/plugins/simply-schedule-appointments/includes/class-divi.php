<?php
/**
 * Simply Schedule Appointments Divi module.
 *
 * @since   3.7.6
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Divi module.
 *
 * @since 3.7.6
 */
class SSA_Divi {
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
		// Load custom modules.
		add_action( 'divi_extensions_init', array( $this, 'load_modules' ) );
	}

	/**
	 * Loads our custom modules.
	 */
	public function load_modules() {
		// Upcoming Appointments Module
		require_once ( __DIR__ . '/divi/includes/SsaDiviModule.php' );
	}

}
