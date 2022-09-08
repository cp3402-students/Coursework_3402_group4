<?php
/**
 * Simply Schedule Appointments Forms.
 *
 * @since   3.2.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Forms.
 *
 * @since 3.2.3
 */
class SSA_Forms {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.2.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.2.3
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
	 * @since  3.2.3
	 */
	public function hooks() {
		/* Automatically mark pending_form appointments as abandoned after 60 minutes */
		add_filter( 'ssa/appointment/after_insert', array( $this, 'schedule_pending_form_cleanup' ), 10, 2 );
		add_filter( 'ssa/appointment/after_update', array( $this, 'schedule_pending_form_cleanup' ), 10, 3 );
		add_action( 'ssa_cleanup_pending_forms', array( $this, 'cleanup_pending_forms' ), 10, 2 );

	}

	public function schedule_pending_form_cleanup( $appointment_id, $data, $data_before = array() ) {
		if ( empty( $data['status'] ) || 'pending_form' !== $data['status'] ) {
			return;
		}

		if ( !empty( $data_before['status'] ) && $data_before['status'] === 'pending_form' ) {
			return; // something else changed, status was pending_form before and after this update. No need to do this multiple times
		}

		$payload = array();

		$seconds_to_hold_pending_form_status = HOUR_IN_SECONDS;
		$seconds_to_hold_pending_form_status = apply_filters( 'ssa/forms/pending_form_duration', $seconds_to_hold_pending_form_status, $appointment_id, $data );

		ssa_queue_action( 'appointment_booked_pending_form', 'ssa_cleanup_pending_forms', 10, $payload, 'appointment', $appointment_id, 'forms', array(
			'date_queued' => gmdate( 'Y-m-d H:i:s', time() + $seconds_to_hold_pending_form_status ),
		) );
	}

	public function cleanup_pending_forms( $payload, $async_action ) {
		$appointments_pending_form = $this->plugin->appointment_model->query( array(
			'status' => 'pending_form',
			'id' => $async_action['object_id'],
		) );

		foreach ($appointments_pending_form as $key => $appointment) {
			if ( empty( $appointment['status'] ) || 'pending_form' !== $appointment['status'] ) {
				continue;
			}

			$appointment_update_data = array(
				'status' => 'abandoned',
			);
			$response = $this->plugin->appointment_model->update( $appointment['id'], $appointment_update_data );
			ssa_complete_action( $async_action['id'], $response );
		}
	}
}
