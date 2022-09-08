<?php
/**
 * Simply Schedule Appointments Block Upcoming Appointments.
 *
 * @since   3.2.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Block Upcoming Appointments.
 *
 * @since 3.2.0
 */
class SSA_Block_Upcoming_Appointments {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.2.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.2.0
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
	 * @since  3.2.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_upcoming_appointment_block' ) );
	}

	/**
	 * Register the block
	 *
	 * @since  3.2.0
	 */
	public function register_upcoming_appointment_block() {
		if ( function_exists( 'register_block_type' ) ) {
			wp_register_script(
				'ssa-upcoming-appointments-block-js',
				$this->plugin->url( 'assets/js/block-upcoming-appointments.js' ),
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
			);

			register_block_type( 'ssa/upcoming-appointments', array(
				'editor_script' => 'ssa-upcoming-appointments-block-js',
				'keywords' => array( 'ssa', 'appointments', 'simply' ),
				'attributes' => array (
					'no_results_message' => array (
						'type' => 'string',
						'default' => __( 'No upcoming appointments', 'simply-schedule-appointments' ),
					),
				),

				'render_callback' => array( $this, 'render' ),
			) );
		}
	}

	/**
	 * Render the shortcode
	 *
	 * @since  3.2.0
	 */
	public function render( $atts ) {
		return $this->plugin->shortcodes->ssa_upcoming_appointments( $atts );
	}

}