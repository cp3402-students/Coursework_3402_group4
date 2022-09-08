<?php
/**
 * Simply Schedule Appointments Availability Schedule.
 *
 * @since   3.6.10
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Availability Schedule.
 *
 * @since 3.6.10
 */
use League\Period\Period;

class SSA_Availability_Schedule_Factory extends SSA_Availability_Schedule {
	public static function create( array $data = array() ) {
		$data = array_merge( array(
			'period' => new Period(
				'2020-01-01',
				'2021-01-01'
			),
			'capacity_available' => SSA_Constants::CAPACITY_MAX,
		), $data );

		$instance = new SSA_Availability_Schedule();

		// create a default-available block
		if ( ! empty( $data['period'] ) ) {
			$instance = $instance->add_block( SSA_Availability_Block_Factory::create( array(
				'period' => $data['period'],
				'capacity_available' => $data['capacity_available'],
				'buffer_available' => $data['capacity_available'],
			) ) );
		}
		$instance->is_sorted = true;

		unset( $data['period'], $data['args'], $data['parent_schedule'] );
		foreach ($data as $key => $value) {
			$instance->$key = $value;
		}

		return $instance;
	}


	public static function create_from_appointment( SSA_Appointment_Object $appointment ) {
		
		$raw_block = SSA_Availability_Block_Factory::create( $appointment );
		
		return self::create( $data );
	}

	public static function available_for_eternity() {
		return self::available_for_period( SSA_Constants::EPOCH_PERIOD() );
	}

	public static function available_for_period( Period $period, $args = array() ) {
		$instance = new SSA_Availability_Schedule();
		$instance = $instance->add_block( SSA_Availability_Block_Factory::available_for_period( $period, $args ) );

		return $instance;
	}

	public static function create_random( array $data = array() ) {
		$start_time = time() + rand(1,1000) * 3600;
		$end_time = $start_time + rand( 1, 1000 ) * 3600;

		$instance = self::create( array(
			'period' => new Period(
				gmdate( 'Y-m-d H:00:00', $start_time ),
				gmdate( 'Y-m-d H:00:00', $end_time )
			),
		) );

		return $instance;
	}

	public static function fixture_available_allyear() {
		return self::create( array(
			'capacity_available' => SSA_Constants::CAPACITY_MAX,

			'period' => new Period(
				'2020-01-01',
				'2021-01-01'
			),
		) );
	}

	public static function fixture_blackout_xmas() {
		return self::create( array(
			'type' => 'global',
			'subtype' => 'blackout',

			'capacity_available' => 0,

			'period' => new Period(
				'2020-12-25',
				'2020-12-26'
			),
		) );
	}

	public static function fixture_blackout_newyear() {
		return self::create( array(
			'type' => 'global',
			'subtype' => 'blackout',

			'capacity_available' => 0,

			'period' => new Period(
				'2020-01-01',
				'2020-01-02'
			),
		) );
	}
}
