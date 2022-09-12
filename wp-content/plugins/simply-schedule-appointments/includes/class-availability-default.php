<?php
/**
 * Simply Schedule Appointments Availability Default.
 *
 * @since   3.6.0
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;
/**
 * Simply Schedule Appointments Availability Default.
 *
 * @since 3.6.0
 */
class SSA_Availability_Default {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.6.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.6.0
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
	 * @since  3.6.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'debug' ) );
	}

	public function debug() {
		if ( empty( $_GET['debug_availability'] ) ) {
			return;
		}

		$sequence = new SSA_Sequence( array(
			SSA_Period::create_from_appointment_id( 306 ),
			SSA_Period::create_from_appointment_id( 260 ),
		));

		$filter = function ( $value ) {
			if ( $value->get_raw_period()->getStartDate() >= new DateTime( '2020-01-01' ) ) {
				return true;
			}

			return false;
		};
	}

	// public function get_args( $appointment_type, $args = array() ) {
	// 	$default_args = apply_filters( 'ssa/availability/default_args', array(
	// 		'start_date' => '',
	// 		'duration' => '',
	// 		'end_date' => '',

	// 		'start_date_min' => '',
	// 		'start_date_max' => '',
	// 		'end_date_min' => '',
	// 		'end_date_max' => '',
	// 		'buffered' => false,

	// 		'excluded_appointment_ids' => array(),
	// 		'appointment_statuses' => SSA_Appointment_Model::get_unavailable_statuses(),
	// 	), $appointment_type );

	// 	$args = shortcode_atts( $default_args, $args );

	// 	return $args;
	// }

	// public function get_available_periods( $appointment_type, $args ) {
	// 	$appointment_type = SSA_Utils::get_appointment_type( $appointment_type );
	// 	if ( false === $appointment_type ) {
	// 		return;
	// 	}

	// 	$args = $this->get_args( $appointment_type, $args );
	// 	$availability_home_timezone = $this->plugin->utils->get_datetimezone( $appointment_type['id'] );

	// 	$available_periods = array();

	// 	$start_date = ssa_datetime( $args['start_date_min'] );
	// 	$end_date = ssa_datetime( $args['start_date_max'] );
	// 	$start_date = $start_date->sub( new DateInterval( 'P1D' ) ); // help avoid timezone issues, our get_bookable_appointments() function will remove ones we don't need				
	// 	$end_date = $end_date->add( new DateInterval( 'P1D' ) ); // help avoid timezone issues, our get_bookable_appointments() function will remove ones we don't need				
	// 	$range_period = new Period( $start_date, $end_date );
	// 	foreach ($range_period->getDatePeriod( '1 DAY' ) as $datetime) {
	// 		$day_of_week = $datetime->format( 'l' );
	// 		if ( empty( $appointment_type['availability'][$day_of_week][0]['time_start'] ) ) {
	// 			continue;
	// 		}

	// 		foreach ($appointment_type['availability'][$day_of_week] as $time_block ) {
	// 			$start_date = SSA_Utils::get_datetime_in_utc( $datetime->format( 'Y-m-d '.$time_block['time_start'] ), $availability_home_timezone );
	// 			$end_date = SSA_Utils::get_datetime_in_utc( $datetime->format( 'Y-m-d '.$time_block['time_end'] ), $availability_home_timezone );
				
	// 			$new_period = new Period(
	// 				$start_date,
	// 				$end_date
	// 			);

	// 			$available_periods[] = $new_period;
	// 		}
	// 	}

	// 	$available_periods = $this->plugin->availability_functions->combine_abutting_periods( $available_periods );
	// 	return $available_periods;
	// }

	// public function cache( $appointment_type, $args ) {
	// 	$appointment_type = SSA_Utils::get_appointment_type( $appointment_type );
	// 	if ( false === $appointment_type ) {
	// 		return;
	// 	}

	// 	$args = array_merge( array(
	// 		'type' => 'default',
	// 		'subtype' => 'hours',
	// 	), $args );

	// 	$availability_period_rows = array();
	// 	$available_periods = $this->get_available_periods( $appointment_type, $args );
	// 	$unavailable_periods = $this->plugin->availability_functions->get_inverse_periods( $available_periods );
	// 	foreach ( $available_periods as $key => $available_period) {
	// 		$period_row = array(
	// 			'appointment_type_id' => $appointment_type['id'],
	// 			'start_date' => $available_period->getStartDate()->format( 'Y-m-d H:i:s' ),
	// 			'end_date' => $available_period->getEndDate()->format( 'Y-m-d H:i:s' ),
	// 			'is_available' => 1,
	// 			'type' => 'default',
	// 			'subtype' => 'hours',
	// 		);

	// 		$availability_period_rows[] = $period_row;
	// 	}

	// 	foreach ( $unavailable_periods as $key => $available_period) {
	// 		$period_row = array(
	// 			'appointment_type_id' => $appointment_type['id'],
	// 			'start_date' => $available_period->getStartDate()->format( 'Y-m-d H:i:s' ),
	// 			'end_date' => $available_period->getEndDate()->format( 'Y-m-d H:i:s' ),
	// 			'is_available' => 0,
	// 			'type' => 'default',
	// 			'subtype' => 'hours',
	// 		);

	// 		$availability_period_rows[] = $period_row;
	// 	}

	// 	$this->plugin->availability_model->update_rows( $availability_period_rows, $args );
	// }
}
