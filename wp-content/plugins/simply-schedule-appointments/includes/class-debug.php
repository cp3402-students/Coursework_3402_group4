<?php
/**
 * Simply Schedule Appointments Debug.
 *
 * @since   4.0.1
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Debug.
 *
 * @since 4.0.1
 */
class SSA_Debug {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.0.1
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.0.1
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
	 * @since  4.0.1
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'debug_settings' ) );
	}

	public function debug_settings() {
		if ( ! isset( $_GET['ssa-debug-settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$settings = $this->plugin->settings->get();
		if ( ! empty( $_GET['ssa-debug-settings'] ) ) {
			if ( empty( $settings[esc_attr( $_GET['ssa-debug-settings'] )] ) ) {
				die( 'setting slug not found' );
			}
			$settings = $settings[esc_attr( $_GET['ssa-debug-settings'] )];
		}
		echo '<pre>'.print_r($settings, true).'</pre>';
		exit;
	}
}
