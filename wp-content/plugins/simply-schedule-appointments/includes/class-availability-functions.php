<?php
/**
 * Simply Schedule Appointments Availability Functions.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Availability Functions.
 *
 * @since 0.0.3
 */
class SSA_Availability_Functions {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
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
	 * @since  0.0.3
	 */
	public function hooks() {
		add_filter( 'ssa/buffer_period/start_date', array( $this, 'get_period_with_buffered_start_date' ), 10, 3 );
		add_filter( 'ssa/buffer_period/end_date', array( $this, 'get_period_with_buffered_end_date' ), 10, 3 );
	}

	public function get_args( $appointment_type, $args = array() ) {
		$default_args = apply_filters( 'ssa/availability/default_args', array(
			'start_date' => '',
			'duration' => '',
			'end_date' => '',

			'start_date_min' => '',
			'start_date_max' => '',
			'end_date_min' => '',
			'end_date_max' => '',
			'buffered' => false,

			'excluded_appointment_ids' => array(),
			'appointment_statuses' => SSA_Appointment_Model::get_unavailable_statuses(),
		), $appointment_type );

		$args = shortcode_atts( $default_args, $args );

		return $args;
	}

	public function get_period_with_buffered_start_date( $period, $original_period, $appointment_type ) {
		ssa_defensive_timezone_fix();
		if ( !empty( $appointment_type['buffer_before'] ) ) {
			$buffer_before = '-' . absint( $appointment_type['buffer_before'] ) . ' MIN';
			$period = $period->moveStartDate( $buffer_before );
		}

		ssa_defensive_timezone_reset();
		return $period;
	}

	public function get_period_with_buffered_end_date( $period, $original_period, $appointment_type ) {
		ssa_defensive_timezone_fix();
		if ( !empty( $appointment_type['buffer_after'] ) ) {
			$buffer_after = '+' . absint( $appointment_type['buffer_after'] ) . ' MIN';
			$period = $period->moveEndDate( $buffer_after );
		}

		ssa_defensive_timezone_reset();
		return $period;
	}

	public function is_period_available( $appointment_type, $args=array() ) {
		ssa_defensive_timezone_fix();
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			ssa_defensive_timezone_reset();
			return new WP_Error();
		}

		$args = $this->get_args( $appointment_type, $args );


		if ( empty( $args['duration'] ) ) {
			$args['duration'] = $appointment_type['duration'];
		}

		if ( empty( $args['duration'] ) ) {
			ssa_defensive_timezone_reset();
			return false;
		}

		if ( empty( $args['end_date'] ) ) {
			$args['end_date'] = ssa_datetime( $args['start_date'] )->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) )->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $args['start_date_min'] ) ) {
			$args['start_date_min'] = ssa_datetime( $args['start_date'] )->sub( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) )->sub( new DateInterval( 'P7D' ) )->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $args['start_date_max'] ) ) {
			$args['start_date_max'] = ssa_datetime( $args['start_date'] )->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) )->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) )->add( new DateInterval( 'P7D' ) )->format( 'Y-m-d H:i:s' );
		}

		$desired_period = new Period( ssa_datetime( $args['start_date'] ), ssa_datetime( $args['end_date'] ) );

		$bookable_appointments = $this->get_bookable_appointments( $appointment_type, $args );
		foreach ( $bookable_appointments as $bookable_appointment_key => $bookable_appointment ) {
			if ( $bookable_appointment['period']->contains( $desired_period ) ) {
				ssa_defensive_timezone_reset();
				return true;
			}
		}

		ssa_defensive_timezone_reset();
		return false;
	}

	public function get_all_available_periods( $appointment_type, $args=array() ) {
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			return new WP_Error();
		}

		$args = $this->get_args( $appointment_type, $args );

		ssa_defensive_timezone_fix();
		if ( $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' ) ) {
			if ( ! empty( $appointment_type['availability_start_date'] ) && $appointment_type['availability_start_date'] !== '0000-00-00 00:00:00' ) {
				if (  empty( $args['start_date_min'] ) || $args['start_date_min'] < $appointment_type['availability_start_date'] ) {
					$args['start_date_min'] = $appointment_type['availability_start_date'];
				}

				if ( $args['start_date_max'] < $args['start_date_min'] ) {
					$args['start_date_max'] = ssa_datetime( $appointment_type['availability_start_date'] )->add( new DateInterval( 'P30D' ) )->format( 'Y-m-d H:i:s' );
				}
			}
		}

		if ( $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' ) ) {
			if ( ! empty( $appointment_type['availability_end_date'] ) && $appointment_type['availability_end_date'] !== '0000-00-00 00:00:00' && ( empty( $args['start_date_max'] ) || $args['start_date_max'] > $appointment_type['availability_end_date'] ) ) {
				$args['start_date_max'] = $appointment_type['availability_end_date'];
				if ( $args['start_date_min'] > $args['start_date_max'] ) {
					ssa_defensive_timezone_reset();
					return array();
				}
			}
		}

		if ( empty( $appointment_type['availability_type'] ) || $appointment_type['availability_type'] != 'custom') {
			$available_periods = $this->get_default_available_periods( $appointment_type, $args );
		} else {
			// TODO: support custom available periods
			// $available_periods = $this->get_custom_available_periods( $appointment_type['id'], $args );
		}

		ssa_defensive_timezone_reset();
		return $available_periods;
	}

	public function get_custom_available_periods( $appointment_type, $args=array() ) {
		ssa_defensive_timezone_fix();
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			ssa_defensive_timezone_reset();
			return new WP_Error();
		}

		$args = $this->get_args( $appointment_type, $args );

		$custom_available_blocks = $this->plugin->availability_model->query( array_merge( array(
			'appointment_type_id' => $appointment_type['id'],
			'is_available' => 1,
			'orderby' => 'start_date',
			'order' => 'ASC',
		), $args ) );

		$custom_available_periods = array();
		foreach ($custom_available_blocks as $key => $custom_available_block) {
			$custom_available_periods[] = new Period( $custom_available_block['start_date'], $custom_available_block['end_date'] );
		}

		$custom_available_periods = $this->combine_abutting_periods( $custom_available_periods );
		ssa_defensive_timezone_reset();
		return $custom_available_periods;
	}

	public function get_default_available_periods( $appointment_type, $args ) {
		ssa_defensive_timezone_fix();
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			ssa_defensive_timezone_reset();
			return new WP_Error();
		}

		$args = $this->get_args( $appointment_type, $args );

		$availability_home_timezone = $this->plugin->utils->get_datetimezone( $appointment_type['id'] );

		$default_available_periods = array();

		$start_date = ssa_datetime( $args['start_date_min'] );
		$end_date = ssa_datetime( $args['start_date_max'] );
		$start_date = $start_date->sub( new DateInterval( 'P1D' ) ); // help avoid timezone issues, our get_bookable_appointments() function will remove ones we don't need				
		$end_date = $end_date->add( new DateInterval( 'P1D' ) ); // help avoid timezone issues, our get_bookable_appointments() function will remove ones we don't need				
		$range_period = new Period( $start_date, $end_date );
		foreach ($range_period->getDatePeriod( '1 DAY' ) as $datetime) {
			$day_of_week = $datetime->format( 'l' );
			if ( empty( $appointment_type['availability'][$day_of_week][0]['time_start'] ) ) {
				continue;
			}

			foreach ($appointment_type['availability'][$day_of_week] as $time_block ) {
				$start_date = SSA_Utils::get_datetime_in_utc( $datetime->format( 'Y-m-d '.$time_block['time_start'] ), $availability_home_timezone );
				if ( 'start_times' === $appointment_type['availability_type'] ) {
					$end_date = $start_date->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) );
				} else {
					// available_blocks (default behavior)
					$end_date = SSA_Utils::get_datetime_in_utc( $datetime->format( 'Y-m-d '.$time_block['time_end'] ), $availability_home_timezone );
				}
				
				$new_period = new Period(
					$start_date,
					$end_date
				);

				$default_available_periods[] = $new_period;
			}
		}

		$default_available_periods = $this->combine_abutting_periods( $default_available_periods );
		ssa_defensive_timezone_reset();
		return $default_available_periods;
	}

	public function get_booked_periods( $appointment_type, $args ) {
		ssa_defensive_timezone_fix();
		$args = $this->get_args( $appointment_type, $args );
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			ssa_defensive_timezone_reset();
			return new WP_Error();
		}

		if ( apply_filters( 'ssa/get_booked_periods/should_separate_availability_for_appointment_types', false ) ) {
			$args['appointment_type_id'] = $appointment_type['id'];
		}

		if ( empty( $args['appointment_type_id'] ) ) {
			$developer_settings = $this->plugin->developer_settings->get();
			if ( ! empty( $developer_settings['separate_appointment_type_availability'] ) ) {
				$args['appointment_type_id'] = $appointment_type['id'];
			}
		}

		$booked_blocks = $this->plugin->appointment_model->query( array_merge( array(
			'status' => $args['appointment_statuses'],
			'number' => -1,
		), $args ) );

		$booked_periods = array();
		foreach ($booked_blocks as $key => $booked_block) {
			if ( ! empty( $args['excluded_appointment_ids'] ) && is_array( $args['excluded_appointment_ids'] ) ) {
				if ( in_array( $booked_block['id'], $args['excluded_appointment_ids'] ) ) {
					continue;
				}
			}

			if ( $booked_block['end_date'] <= $booked_block['start_date'] ) {
				$booked_block['end_date'] = ssa_datetime( $booked_block['start_date'] )->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) )->format( 'Y-m-d H:i:s' );
			}


			if ( $booked_block['end_date'] < $booked_block['start_date'] ) {
				ssa_debug_log( __CLASS__ . ' ' . __FUNCTION__ . '():' . __LINE__ );
				ssa_debug_log( 'The ending datepoint must be greater or equal to the starting datepoint in appointment ID ' . $this->id, 10 );

				$booked_period = new Period(
					$booked_block['start_date'],
					$booked_block['start_date']
				);
			} else {
				$booked_period = new Period(
					$booked_block['start_date'],
					$booked_block['end_date']
				);
			}


			if ( !empty( $args['buffered'] ) ) {
				$booked_period = apply_filters( 'ssa/buffer_period/start_date', $booked_period, $booked_period, $appointment_type );	
				$booked_period = apply_filters( 'ssa/buffer_period/end_date', $booked_period, $booked_period, $appointment_type );	
				if ( empty( $booked_period ) ) {
					continue;
				}
			}
			$booked_periods[] = $booked_period;
		}

		$booked_periods = apply_filters( 'ssa/get_booked_periods/booked_periods', $booked_periods, $appointment_type );
		ssa_defensive_timezone_reset();
		return $booked_periods;
	}

	public function get_blocked_periods( $appointment_type, $args ) {
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			return new WP_Error();
		}

		$args = $this->get_args( $appointment_type, $args );

		$blocked_periods = array();

		ssa_defensive_timezone_fix();

		$blocked_periods = apply_filters( 'ssa/get_blocked_periods/blocked_periods', $blocked_periods, $appointment_type, $args );

		ssa_defensive_timezone_reset();
		return $blocked_periods;
	}

	public function get_bookable_appointments( $appointment_type, $args=array() ) {
		ssa_defensive_timezone_fix();
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			ssa_defensive_timezone_reset();
			return new WP_Error();
		}


		$appointment_type = apply_filters( 'ssa/get_bookable_appointments/appointment_type', $appointment_type );
		$availability_home_timezone = $this->plugin->utils->get_datetimezone( $appointment_type['id'] );

		$args = $this->get_args( $appointment_type, $args );
		
		if ( !empty( $args['start_date_min'] ) ) {
			$start_date_min_datetime = ssa_datetime( $args['start_date_min'] );
			$start_date_with_min_booking_notice_datetime = ssa_datetime();
			if ( !empty( $appointment_type['min_booking_notice'] ) ) {
				$start_date_with_min_booking_notice_datetime = ssa_datetime()->add( new DateInterval( 'PT'.$appointment_type['min_booking_notice'].'M' ) );
			}
			
			if ( $start_date_with_min_booking_notice_datetime > $start_date_min_datetime ) {
				$start_date_min_datetime = $start_date_with_min_booking_notice_datetime;
			}
		}

		if ( !empty( $args['start_date_max'] ) ) {
			$start_date_max_datetime = ssa_datetime( $args['start_date_max'] );
		}

		if ( $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' ) ) {
			if ( !empty( $appointment_type['max_booking_notice'] ) ) {
				$start_date_with_max_booking_notice_datetime = ssa_datetime()->add( new DateInterval( 'PT'.$appointment_type['max_booking_notice'].'M' ) );
				if ( empty( $start_date_max_datetime ) || $start_date_with_max_booking_notice_datetime < $start_date_max_datetime ) {
					$start_date_max_datetime = $start_date_with_max_booking_notice_datetime;
				}
			}
		}

		if ( empty( $appointment_type['availability_increment'] ) ) {
			$appointment_type['availability_increment'] = 15;
		}
		if ( !empty( $args['end_date_min'] ) ) {
			$end_date_min_datetime = ssa_datetime( $args['end_date_min'] );
		}

		if ( !empty( $args['end_date_max'] ) ) {
			$end_date_max_datetime = ssa_datetime( $args['end_date_max'] );
		}

		if ( $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' ) ) {
			if ( !empty( $appointment_type['max_booking_notice'] ) ) {
				$end_date_with_max_booking_notice_datetime = ssa_datetime()->add( new DateInterval( 'PT'.$appointment_type['max_booking_notice'].'M' ) )->add( new DateInterval( 'PT'.$appointment_type['duration'].'M' ) );
				if ( empty( $end_date_max_datetime ) || $end_date_with_max_booking_notice_datetime < $end_date_max_datetime ) {
					$end_date_max_datetime = $end_date_with_max_booking_notice_datetime;
				}
			}
		}


		$available_periods = $this->get_all_available_periods( $appointment_type, $args );
		$booked_periods = $this->get_booked_periods( $appointment_type, $args );
		$blocked_periods = $this->get_blocked_periods( $appointment_type, $args );
		
		$bookable_periods = array();
		foreach ($available_periods as $key => $available_period) {
			foreach ($available_period->getDatePeriod($appointment_type['availability_increment'].' MIN') as $start_datetime) {
				/* Look at every bookable start time and determine if this slot is available */

				/* Does this potential appointment start within min/max datetime? */
				if ( !empty( $start_date_min_datetime ) && $start_datetime < $start_date_min_datetime ) {
					continue;
				}
				if ( !empty( $start_date_max_datetime ) && $start_datetime > $start_date_max_datetime ) {
					continue;
				}

				/* If this is a "start_times" availability type, is it a valid start time? */				
				if ( 'start_times' === $appointment_type['availability_type'] ) {
					$local_start_datetime = $start_datetime->setTimezone( $availability_home_timezone );
					$day_of_week = $local_start_datetime->format( 'l' );
					if ( empty( $appointment_type['availability'][$day_of_week][0]['time_start'] ) ) {
						continue; // no start times for this day of the week
					}

					$is_valid_start_time = false;
					foreach ($appointment_type['availability'][$day_of_week] as $key => $slot) {
						if ( $is_valid_start_time ) {
							continue;
						}

						if ( $slot['time_start'] == $local_start_datetime->format( 'H:i:s' ) ) {
							$is_valid_start_time = true;
						}

						if ( $slot['time_start'] == $local_start_datetime->format( 'G:i:s' ) ) {
							$is_valid_start_time = true;
						}						
					}

					if ( ! $is_valid_start_time ) {
						continue;
					}
				}

				
				$bookable_period = Period::createFromDuration($start_datetime, new DateInterval('PT'.$appointment_type['duration'].'M'));

				/* Does this potential appointment end within min/max datetime? */
				if ( !empty( $end_date_min_datetime ) && $bookable_period->getEndDate() < $end_date_min_datetime ) {
					continue;
				}
				if ( !empty( $end_date_max_datetime ) && $bookable_period->getEndDate() > $end_date_max_datetime ) {
					continue;
				}

				/* Does this potential appointment fit within the available block? */
				if ( !$available_period->contains( $bookable_period ) ) {
					continue;
				}

				/* Does this potential appointment conflict with a blocked time? */
				$buffered_bookable_period = apply_filters( 'ssa/buffer_period/start_date', $bookable_period, $bookable_period, $appointment_type );
				$buffered_bookable_period = apply_filters( 'ssa/buffer_period/end_date', $buffered_bookable_period, $bookable_period, $appointment_type );

				foreach ($blocked_periods as $key => $blocked_period) {
					$buffered_blocked_period = apply_filters( 'ssa/buffer_period/blocked_period', $blocked_period, $blocked_period, $appointment_type );
					if ( $buffered_bookable_period->overlaps( $buffered_blocked_period ) ) {
						/* They overlap, but let's make sure that our double-buffering didn't exclude a valid bookable period (collapse margins) */
						if ( $bookable_period->overlaps( $blocked_period ) ) {
							// even unbuffered they overlap
							continue 2;
						}
						
						$gap = $bookable_period->gap( $blocked_period );
						$minimum_gap_in_seconds = 60 * max( absint( $appointment_type['buffer_before'] ), absint( $appointment_type['buffer_after'] ) );
						if ( $gap->getTimestampInterval() < $minimum_gap_in_seconds ) {
							continue 2;
						}
					}
				}

				/* Does this potential appointment conflict with a previously-booked time? */
				foreach ($booked_periods as $key => $booked_period) {
					$buffered_booked_period = apply_filters( 'ssa/buffer_period/start_date', $booked_period, $booked_period, $appointment_type );
					$buffered_booked_period = apply_filters( 'ssa/buffer_period/end_date', $buffered_booked_period, $booked_period, $appointment_type );
					if ( $buffered_bookable_period->overlaps( $buffered_booked_period ) ) {
						/* They overlap, but let's make sure that our double-buffering didn't exclude a valid bookable period (collapse margins) */
						if ( $bookable_period->overlaps( $booked_period ) ) {
							// even unbuffered they overlap
							continue 2;
						}
						
						$gap = $bookable_period->gap( $booked_period );
						$minimum_gap_in_seconds = 60 * max( absint( $appointment_type['buffer_before'] ), absint( $appointment_type['buffer_after'] ) );
						if ( $gap->getTimestampInterval() < $minimum_gap_in_seconds ) {
							continue 2;
						}
					}
				}

				$bookable_periods[] = $bookable_period;
			}
		}

		$bookable_appointments = array();
		foreach ($bookable_periods as $key => $bookable_period) {
			$bookable_appointments[] = array(
				'period' => $bookable_period,
			);
		}

		ssa_defensive_timezone_reset();
		return $bookable_appointments;
	}

	/** Utility Functions **/
	public function get_inverse_periods( $periods_array ) {
		$inverse_array = array();
		$last_period = null;
		foreach ($periods_array as $key => $period) {
			if ( ! empty( $last_period ) ) {
				$gap_period = $last_period->gap( $period );
				$inverse_array[] = $gap_period;
			}

			$last_period = $period;
		}

		return $inverse_array;
	}

	public function combine_abutting_periods( $periods_array ) {
		ssa_defensive_timezone_fix();
		if ( !is_array( $periods_array ) ) {
			return $periods_array;
		}

		$combined_periods = array();
		while( count( $periods_array ) ) {
			$next_period = array_shift( $periods_array );
			if ( count( $combined_periods ) === 0 ) {
				$combined_periods[] = $next_period;
				continue;
			}

			$last_period = current(array_slice($combined_periods, -1));
			if ( $last_period->abuts( $next_period ) ) {
				if ( $last_period->isAfter( $next_period ) ) {
					$tmp_last_period = $last_period;
					$last_period = $next_period;
					$next_period = $tmp_last_period;
				}

				array_pop( $combined_periods );
				$combined_periods[] = new Period( $last_period->getStartDate(), $next_period->getEndDate() );
				continue;
			}

			$combined_periods[] = $next_period;
			continue;
		}

		$combined_periods = array_filter($combined_periods, function($value) {
			ssa_defensive_timezone_reset();
			return $value !== '';
		});

		ssa_defensive_timezone_reset();
		return $combined_periods;
	}

}
