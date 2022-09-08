<?php
/**
 * Simply Schedule Appointments Web Meetings.
 *
 * @since   4.3.3-beta3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Web Meetings.
 *
 * @since 4.3.3-beta3
 */
class SSA_Web_Meetings {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.3.3-beta3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.3.3-beta3
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
	 * @since  4.3.3-beta3
	 */
	public function hooks() {
		add_filter( 'ssa/appointment/before_insert', array( $this, 'set_custom_web_meeting_url' ) );
	}

	/**
	 * Checks if Staff feature is enabled.
	 *
	 * @since 4.9.6
	 *
	 * @return boolean
	 */
	public function should_include_staff_web_meetings() {
		$settings = $this->plugin->settings->get();
		if ( $settings['staff'] && $settings['staff']['enabled'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks what type of web meetings url the Appointment should have, based on it's Appointment Type.
	 *
	 * @since 4.3.3-beta3
	 *
	 * @param array $data The provided data for the appointment.
	 * @return array
	 */
	public function set_custom_web_meeting_url( $data ) {
		if ( empty( $data['appointment_type_id'] ) ) {
			return $data;
		}

		$appointment_type = $this->plugin->appointment_type_model->get( $data['appointment_type_id'] );
		if ( empty( $appointment_type['web_meetings']['provider'] ) ) {
			return $data;
		}

		if ( 'custom' === $appointment_type['web_meetings']['provider'] && ! empty( $appointment_type['web_meetings']['url'] ) ) {
			$data['web_meeting_url'] = trim( $appointment_type['web_meetings']['url'] );
		}

		// If staff feature is disabled, but Appointment type have the provider set to 'staff', just use the custom web meeting url if set.
		if ( ! $this->should_include_staff_web_meetings() && 'staff' === $appointment_type['web_meetings']['provider'] && ! empty( $appointment_type['web_meetings']['url'] ) ) {
			$data['web_meeting_url'] = trim( $appointment_type['web_meetings']['url'] );
			return $data;
		}

		// If staff feature is set and Appointment type have the provider set to 'staff', use the staff web meeting url if set.
		if (
			'staff' === $appointment_type['web_meetings']['provider'] && // if staff is the provider.
			in_array( $appointment_type['staff']['required'], array( 'any', 'all' ), true ) && // if any or all staff members are required.
			! empty( $data['staff_ids'] ) // if the appointment has staff members assigned.
		) {
			// use the first web meetings url found on the list of staff members.
			$selected_web_meeting_url = null;

			foreach ( $data['staff_ids'] as $staff_id ) {
				$staff           = new SSA_Staff_Object( $staff_id );
				$web_meeting_url = $staff->get_web_meetings_url();

				if ( ! empty( $web_meeting_url ) ) {
					$selected_web_meeting_url = $web_meeting_url;
					break;
				}
			}

			// if a staff member web meeting url was found, use it.
			if ( ! empty( $selected_web_meeting_url ) ) {
				$data['web_meeting_url'] = trim( $selected_web_meeting_url );
			} elseif ( ! empty( $appointment_type['web_meetings']['url'] ) ) {
				// If no staff member has a web meeting url AND we have a default staff url set, use it.
				$data['web_meeting_url'] = trim( $appointment_type['web_meetings']['url'] );
			}
		}

		return $data;
	}
}
