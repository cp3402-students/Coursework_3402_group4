<?php
/**
 * Simply Schedule Appointments Block Booking.
 *
 * @since   2.4.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Block Booking.
 *
 * @since 2.4.0
 */
class SSA_Block_Booking {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.4.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.4.0
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
	 * @since  2.4.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_booking_block' ) );
	}

	public function register_booking_block() {
		if ( function_exists( 'register_block_type' ) ) {
			wp_register_script(
				'ssa-booking-block-js',
				$this->plugin->url( 'assets/js/block-booking.js' ),
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
			);
			wp_register_style(
				'ssa-booking-block-css',
				$this->plugin->url( 'assets/css/block-booking.css' )
			);

			$appointment_types = $this->plugin->appointment_type_model->query( array(
				'status' => 'publish',
			) );
			$ssa_appointment_key_values = wp_list_pluck( $appointment_types, 'title', 'slug' );
			asort( $ssa_appointment_key_values );

			wp_localize_script( 'ssa-booking-block-js', 'ssaAppointmentTypes', $ssa_appointment_key_values );


			register_block_type( 'ssa/booking', array(
				'editor_script' => 'ssa-booking-block-js',
				'editor_style'  => 'ssa-booking-block-css',
				'keywords' => array( 'ssa', 'appointments', 'simply' ),
				'attributes' => array (
					'type' => array (
						'type' => 'string',
						'default' => '',
					),
					'accent_color' => array (
						'type' => 'string',
						'default' => '',
					),
					'background' => array (
						'type' => 'string',
						'default' => '',
					),
					'padding' => array (
						'type' => 'number',
						'default' => 0,
					),
					'padding_unit' => array (
						'type' => 'string',
						'default' => '',
					),
				),

				'render_callback' => array( $this, 'render' ),
			) );
		}
	}

	public function render( $settings ) {
		$attrs = array();

		if ( $settings['type'] === 'none' || $settings['type'] === 'all' ) {
			$settings['type'] = '';			
		}
		if( $settings['type'] && $settings['type'] !== '' ) {
			$attrs['type'] = $settings['type'];
		}
		if( $settings['accent_color'] && $settings['accent_color'] !== '' ) {
			$attrs['accent_color'] = ltrim( $settings['accent_color'], '#');
		}
		if( $settings['background'] && $settings['background'] !== '' ) {
			$attrs['background'] = ltrim( $settings['background'], '#' );
		}
		if( $settings['padding'] && $settings['padding'] !== '' ) {
			// using '%' on the dropdown value causes a "malformed URI" issue on Gutenberg renderer, so this is
			// necessary
			$settings['padding_unit'] = $settings['padding_unit'] === 'percent'	? '%' : $settings['padding_unit'];

			$attrs['padding'] = $settings['padding'] . $settings['padding_unit'];
		}		
		// return '<pre>'. print_r( $attrs, true ) .'</pre>';
		return $this->plugin->shortcodes->ssa_booking( $attrs );
	}

}
