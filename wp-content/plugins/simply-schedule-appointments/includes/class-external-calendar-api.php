<?php
/**
 * Simply Schedule Appointments External Calendar Api.
 *
 * @since   4.1.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments External Calendar Api.
 *
 * @since 4.1.2
 */
abstract class SSA_External_Calendar_Api extends SSA_External_Api {
	abstract function pull_availability_calendar( $calendar_id, $args = array() );
	
	/* from external api class */
	// abstract function get_api();
	// abstract function get_identity();
	// abstract function pull_appointment( SSA_Appointment_Object $appointment );
	// abstract function push_appointment( SSA_Appointment_Object $appointment );

	// abstract function get_event( $calendar_event_id, $calendar_id );

	// abstract function insert_event( $event, $calendar_id );
}
