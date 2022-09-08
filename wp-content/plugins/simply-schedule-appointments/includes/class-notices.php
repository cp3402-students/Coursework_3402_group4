<?php
/**
 * Simply Schedule Appointments Notices.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notices.
 *
 * @since 0.1.0
 */
class SSA_Notices {
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

	public function get_dismissed_notices() {
		$dismissed_notices = get_option( 'ssa_dismissed_notices', array() );

		/* is this dismissed for the current user? */
		if ( is_user_logged_in() ) {
			$user_dismissed_notices = get_user_meta( get_current_user_id(), 'ssa_dismissed_notices', true );
			if ( empty( $user_dismissed_notices ) ) {
				$user_dismissed_notices = array();
			}

			$dismissed_notices = array_merge( $dismissed_notices, $user_dismissed_notices );
		}

		return $dismissed_notices;
	}
}
