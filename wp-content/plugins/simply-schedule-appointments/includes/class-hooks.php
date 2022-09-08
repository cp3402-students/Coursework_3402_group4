<?php
/**
 * Simply Schedule Appointments Hooks.
 *
 * @since   2.6.9
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Hooks.
 *
 * @since 2.6.9
 */
class SSA_Hooks {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.6.9
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.6.9
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
	 * @since  2.6.9
	 */
	public function hooks() {
		// maybe do_action( 'ssa/appointment/booked' ):
		add_action( 'ssa/appointment/after_insert', array( $this, 'maybe_do_appointment_booked_hook' ), 1000, 2 );
		add_action( 'ssa/appointment/after_update', array( $this, 'maybe_do_appointment_booked_hook' ), 1000, 4 );

		// maybe do_action( 'ssa/appointment/customer_information_edited' );
		add_action( 'ssa/appointment/after_update', array( $this, 'maybe_do_appointment_customer_information_edited_hook' ), 10, 4 );
		add_action( 'ssa/appointment/after_update', array( $this, 'maybe_do_appointment_edited_hook' ), 10, 4 );

		// maybe do_action( 'ssa/appointment/canceled' );
		add_action( 'ssa/appointment/after_update', array( $this, 'maybe_do_appointment_canceled_hook' ), 10, 4 );
	}

	public function maybe_do_appointment_booked_hook( $appointment_id, $data, $data_before = array(), $response = null ) {
		if ( empty( $appointment_id ) ) {
			ssa_debug_log( 'ssa/appointment/after_insert called with $appointment_id = 0', 10 );
			ssa_debug_log( $data, 10 );
			return;
		}

		if ( empty( $data['status'] ) || $data['status'] !== 'booked' ) {
			return;
		}

		if ( !empty( $data_before['status'] ) && $data_before['status'] === 'booked' ) {
			return;
		}

		// We have a newly booked appointment 
		// either brand new "booked" appointment
		// or a recently changed status – like "pending_payment" – to "booked"

		do_action( 'ssa/appointment/booked', $appointment_id, $data, $data_before, $response );
	}

	public function maybe_do_appointment_customer_information_edited_hook( $appointment_id, $data_after, $data_before, $response ) {
		if ( empty( $data_before['status'] ) || empty( $data_before['customer_information'] ) || empty( $data_after['customer_information'] ) ) {
			return; // we don't want to send a hook if we don't have the pieces we need
		}

		if ( json_encode( $data_before['customer_information'] ) === json_encode( $data_after['customer_information'] ) ) {
			return; // we don't want to send a customer_information_edited hook if anything besides customer information changed (eg. Google Calendar ID)
		}

		if ( $data_after['status'] === 'canceled' && $data_before['status'] === 'booked' ) {
			return; // we don't want to send a customer_information_edited hook when a canceled one will be sent instead (this should be redundant, but just in case)
		}
		
		do_action( 'ssa/appointment/customer_information_edited', $appointment_id, $data_after, $data_before, $response );
	}

	public function maybe_do_appointment_edited_hook( $appointment_id, $data_after, $data_before, $response ) {
		if ( empty( $data_before['status'] ) || empty( $data_after['status'] ) ) {
			return; // we don't want to send a hook if we don't have the pieces we need
		}

		if ( $data_after['status'] === 'canceled' && $data_before['status'] === 'booked' ) {
			return; // we don't want to send a hook when a canceled one will be sent instead (this should be redundant, but just in case)
		}

		$fields_to_watch = array(
			'appointment_type_id' => '',
			'author_id' => '',
			'customer_id' => '',
			'customer_information' => '',
			'start_date' => '',
			'end_date' => '',
			'title' => '',
			'description' => '',
			'price_full' => '',
			'payment_received' => '',
			'status' => '',
		);

		$before_hash = md5( json_encode( shortcode_atts( $fields_to_watch, $data_before ) ) );
		$after_hash = md5( json_encode( shortcode_atts( $fields_to_watch, $data_after ) ) );

		if ( $before_hash == $after_hash ) {
			return; // nothing changed that we care about (this also eliminates an undesirable firing of "edited" when an appointment is first booked)
		}

		do_action( 'ssa/appointment/edited', $appointment_id, $data_after, $data_before, $response );
	}

	public function maybe_do_appointment_canceled_hook( $appointment_id, $data_after, $data_before, $response ) {
		if ( empty( $data_before['status'] ) || empty( $data_after['status'] ) ) {
			return; // we don't want to send a hook if we don't have the pieces we need
		}

		if ( $data_after['status'] !== 'canceled' || $data_before['status'] !== 'booked' ) {
			return; // we only want to send a webhook when an appointment goes from booked -> canceled
		}
		
		do_action( 'ssa/appointment/canceled', $appointment_id, $data_after, $data_before, $response );
	}

}
