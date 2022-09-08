<?php
/**
 * Simply Schedule Appointments Recipient Admin.
 *
 * @since   3.8.6
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Recipient Admin.
 *
 * @since 3.8.6
 */
class SSA_Recipient_Admin extends SSA_Recipient {
	public static function create() {
		return new self;
	}

	public static function is_business() {
		return true;
	}

	public static function is_customer() {
		return false;
	}
}
