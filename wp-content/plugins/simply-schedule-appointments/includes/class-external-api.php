<?php
/**
 * Simply Schedule Appointments External Api.
 *
 * @since   4.1.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments External Api.
 *
 * @since 4.1.2
 */
abstract class SSA_External_Api {
	const SERVICE = '';

	protected $api;
	protected $credentials;
	protected $access_token;

	abstract function get_api();

	abstract function api_get_identity();

	abstract function pull_appointment( SSA_Appointment_Object $appointment );
	abstract function push_appointment( SSA_Appointment_Object $appointment );
}
