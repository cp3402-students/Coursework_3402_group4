<?php
/**
 * Simply Schedule Appointments Sequence.
 *
 * @since   3.6.3
 * @package Simply_Schedule_Appointments
 */

use League\Period\Period;

/**
 * Simply Schedule Appointments Sequence.
 *
 * @since 3.6.3
 */

class SSA_Sequence_Factory extends SSA_Sequence {
	public static function create( $args ) {
		$sequence = new SSA_Sequence( array(
			SSA_Period::create_from_period( new Period( '2020-01-01 11:00:00', '2020-01-01 13:00:00') ),
			SSA_Period::create_from_period( new Period( '2019-01-01 11:00:00', '2019-01-01 13:00:00') ),
		) );

		return $sequence;
	}

	public static function holidays_fixture() {
		$sequence = new SSA_Sequence( array(
			SSA_Period::create_from_period( new Period( '2040-01-01 00:00:00', '2040-01-02 00:00:00') ),
			SSA_Period::create_from_period( new Period( '2040-12-25 00:00:00', '2040-12-26 00:00:00') ),
			SSA_Period::create_from_period( new Period( '2040-03-17 00:00:00', '2040-03-18 00:00:00') ),
			SSA_Period::create_from_period( new Period( '2040-07-04 00:00:00', '2040-07-05 00:00:00') ),
			SSA_Period::create_from_period( new Period( '2040-02-14 00:00:00', '2040-02-15 00:00:00') ),
		) );

		return $sequence;
	}
}
