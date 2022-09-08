<?php
/**
 * Simply Schedule Appointments Constants.
 *
 * @since   3.7.1
 * @package Simply_Schedule_Appointments
 */

use League\Period\Period;

/**
 * Simply Schedule Appointments Constants.
 *
 * @since 3.7.1
 */
class SSA_Constants {
	const EPOCH_START_DATE = '2018-01-01';
	const EPOCH_END_DATE = '2300-01-01';

	const CAPACITY_MAX = 100000;

	public static function EPOCH_PERIOD() {
		return new Period(
			self::EPOCH_START_DATE,
			self::EPOCH_END_DATE
		);
	}
}
