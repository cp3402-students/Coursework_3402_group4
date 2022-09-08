<?php
/**
 * Simply Schedule Appointments Recipient Customer.
 *
 * @since   3.8.6
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Recipient Customer.
 *
 * @since 3.8.6
 */
class SSA_Recipient_Customer extends SSA_Recipient {
	public static function create() {
		return new self;
	}

	public static function is_business() {
		return false;
	}

	public static function is_customer() {
		return true;
	}
}
