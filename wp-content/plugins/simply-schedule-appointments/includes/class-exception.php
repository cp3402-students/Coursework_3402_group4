<?php
/**
 * Simply Schedule Appointments Exception.
 *
 * @since   1.0.1
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Exception.
 *
 * @since 1.0.1
 */
class SSA_Exception extends Exception {
	public $feature;
	protected $code;

	public function __construct( string  $message = null, string $code = null ) {

	}
}

class SSA_Mailchimp_Exception extends SSA_Exception {
	public $feature = 'mailchimp';
}