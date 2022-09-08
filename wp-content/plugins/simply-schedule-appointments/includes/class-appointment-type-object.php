<?php
/**
 * Simply Schedule Appointments Appointment Type Object.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Appointment Type Object.
 *
 * @since 0.0.3
 */
class SSA_Appointment_Type_Object {
	protected $id = null;
	protected $model = null;
	protected $data = null;

	protected $status;

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
	public function __construct( $id ) {
		$this->id = $id;
		
		// if ( empty( $this->data['id'] ) || $this->id != $this->data['id'] ) {
		// 	throw new Exception("Unable to create SSA_Appointment_Type_Object from id $id");
		// }
	}

	public static function null() {
		return new self( 0 );
	}

	public static function instance( $appointment_type ) {
		if ( $appointment_type instanceof SSA_Appointment_Type_Object ) {
			return $appointment_type;
		}

		$appointment_type = new SSA_Appointment_Type_Object( $appointment_type );

		return $appointment_type;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		if ( empty( $this->data ) && $field !== 'id' ) {
			$this->get();
		}

		switch ( $field ) {
			case 'id':
			case 'data':
				return $this->$field;
			case 'availability_start_date':
			case 'availability_end_date':
				if ( ! isset( $this->data[$field] ) ) {
					return null;
				}

				if ( $this->data[$field] === '0000-00-00 00:00:00' ) {
					return null;
				}

				return $this->data[$field];
			case 'availability_increment':
				$availability_increment = $this->data[$field];
				if ( empty( $availability_increment ) || $availability_increment < 0 ) {
					return 15; // 15 minute default
				}
				return $availability_increment;
			default:
				if ( isset( $this->data[$field] ) ) {
					return $this->data[$field];
				}

				ssa_debug_log( 10, 'Appointment Type Object (ID '. $this->id .') Invalid field: ' . $field );
				if ( empty( $this->id ) ) {
					return null;
				}

				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	public function get() {
		if ( empty( $this->id ) ) {
			$this->data = array();
		}
		
		$this->data = ssa()->appointment_type_model->get( $this->id );
	}

	/**
	 * Get appointment type title.
	 *
	 * @since 4.8.9
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->__get( 'title' );
	}

	/**
	 * Get appointment type slug.
	 *
	 * @since 4.8.9
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->__get( 'slug' );
	}

	public function get_max_per_day_schedule( Period $period, $args = array() ) {
		if ( empty( $args['appointment_type.max_per_day'] ) ) {
			return new SSA_Availability_Schedule();
		}
		$max_event_count = $this->__get( 'max_event_count' );
		if ( empty( $max_event_count ) ) {
			return new SSA_Availability_Schedule();
		}

		$one_day_interval = new DateInterval( 'P1D');
		$extended_period = new Period( $period->getStartDate()->sub( $one_day_interval ), $period->getEndDate()->add( $one_day_interval ) );
		$appointments = $this->get_appointments( $extended_period, array(
			'status' => SSA_Appointment_Model::get_unavailable_statuses(), // don't count canceled or abandoned appointments against this limit
		) );
		$period_tz = new Period(
			$period->getStartDate()->setTimezone( $this->get_timezone() )->setTime( 0, 0 )->sub( $one_day_interval ),
			$period->getEndDate()->setTimezone( $this->get_timezone() )->setTime( 0, 0 )->add( $one_day_interval )
		);
		$schedule = new SSA_Availability_Schedule();
		foreach ( $period_tz->split( new DateInterval( 'P1D') ) as $today_period_tz ) {
			$today_period = SSA_Utils::get_period_in_utc( $today_period_tz );
			$today_appointment_count = 0;
			$today_block = SSA_Availability_Block_Factory::available_for_period(
				$today_period, array(
					'capacity_available' => min( $this->__get( 'capacity' ), $max_event_count ),
					'buffer_available' => $this->get_buffer_capacity_max(),
				)
			);

			$appointment_count_by_start_date = array();
			$appointment_periods_by_start_date = array();
			foreach ($appointments as $appointment) {
				$appointment = SSA_Appointment_Object::instance( $appointment );
				if ( ! empty( $args['skip_appointment_id'] ) && $args['skip_appointment_id'] === $appointment->id ) {
					continue;
				}

				if ( ! $appointment->get_appointment_period()->overlaps( $today_period_tz ) ) {
					continue;
				}
				
				$today_appointment_count++;
				$start_date = $appointment->start_date_timestamp;
				if ( empty( $appointment_count_by_start_date[$start_date] ) ) {
					$appointment_count_by_start_date[$start_date] = 0;
				}
				$appointment_count_by_start_date[$start_date]++;
				$appointment_periods_by_start_date[$start_date] = $appointment->get_appointment_period();

				$blocked_today_block = SSA_Availability_Block_Factory::available_for_period(
					$today_period, array(
						'capacity_available' => 0,
						'buffer_available' => $this->get_buffer_capacity_max(),
					)
				);
				if ( $appointment->is_individual_appointment() ) {
					if ( $today_appointment_count < $this->__get( 'max_event_count' ) ) {
						continue;
					}

					$schedule = $schedule->pushmerge( $blocked_today_block );
					continue 2;

				} elseif ( $appointment->is_group_event() ) {
					if ( count( $appointment_count_by_start_date ) < $this->__get( 'max_event_count' ) ) {
						continue; // any time is still available
					}

					// the maximum number of group timeslots have been taken for today
					// allow current times to continue being booked
					// we will be limited by capacity limits, but not this max per day schedule
					$blocked_day_schedule = new SSA_Availability_Schedule();
					$blocked_day_schedule = $blocked_day_schedule->push( $blocked_today_block );
					$available_slot_schedule = new SSA_Availability_Schedule();
					foreach ($appointment_periods_by_start_date as $start_date => $appointment_period) {
						$available_slot_schedule = $available_slot_schedule->pushmerge(
							SSA_Availability_Block_Factory::available_for_period(
								$appointment_period, array(
									'capacity_available' => SSA_Constants::CAPACITY_MAX,
									'buffer_available' => $this->get_buffer_capacity_max(),
								)
							)
						);
					}
					$remaining_slot_schedule = $blocked_day_schedule->merge_max( $available_slot_schedule );
					$schedule = $schedule->merge_min( $remaining_slot_schedule );
				} else {
					throw new Exception("Unknown appointment capacity_type");
				}
			}

			// no overlapping appointments for this "today"
			if ( $this->is_individual_appointment() ) {
				$schedule = $schedule->pushmerge( $today_block );
			} elseif ( $this->is_group_event() ) {
				$today_block = $today_block->set_capacity_available( SSA_Constants::CAPACITY_MAX );
				$schedule = $schedule->pushmerge( $today_block );
			}
		}

		$schedule = $schedule->subrange( $period );
		return $schedule;
	}

	public function get_staff_capacity() {
		return $this->__get( 'staff_capacity' );
	}

	public function get_buffer_capacity_multiplier() {
		$buffer_before = $this->__get( 'buffer_before' );
		$buffer_after = $this->__get( 'buffer_after' );

		if ( $buffer_before && $buffer_after ) {
			$buffer_capacity_multiplier = 2;
		} else if ( $buffer_before || $buffer_after ) {
			$buffer_capacity_multiplier = 1;
		} else {
			$buffer_capacity_multiplier = 0;
		}

		return $buffer_capacity_multiplier;
	}

	public function get_buffer_capacity_max() {
		$capacity = $this->__get( 'capacity' );
		$buffer_before = $this->__get( 'buffer_before' );
		$buffer_after = $this->__get( 'buffer_after' );
		$duration = $this->__get( 'duration' );

		if ( $buffer_before && $buffer_after ) {
			$buffer_capacity = 2 * $capacity;
		} else if ( $buffer_before || $buffer_after ) {
			$buffer_capacity = $capacity;
		} else {
			$buffer_capacity = SSA_Constants::CAPACITY_MAX;
		}

		return $buffer_capacity;
	}

	public function get_min_booking_notice_schedule( Period $period = null, $args = array() ) {
		if ( empty( $args['appointment_type.min_booking_notice'] ) ) {
			return new SSA_Availability_Schedule();
		}
		$min_booking_notice = $this->__get( 'min_booking_notice' );
		if ( empty( $min_booking_notice ) ) {
			return new SSA_Availability_Schedule();
		}

		$start_date = SSA_Constants::EPOCH_START_DATE;
		$end_date = SSA_Utils::ceil_datetime( ssa_datetime() )->add(
			new DateInterval( 'PT'.$min_booking_notice.'M' )
		);
		$min_booking_notice_period = new Period( $start_date, $end_date );

		if ( ! empty( $period ) && ! $min_booking_notice_period->overlaps( $period ) ) {
			return new SSA_Availability_Schedule();
		}

		$schedule = SSA_Availability_Schedule_Factory::available_for_period( $min_booking_notice_period, array(
			'capacity_available' => 0,
			'buffer_available' => $this->get_buffer_capacity_max(),
		) );

		return $schedule;
	}

	public function get_max_booking_notice_schedule( Period $period = null, $args = array() ) {
		if ( empty( $args['appointment_type.max_booking_notice'] ) ) {
			return new SSA_Availability_Schedule();
		}

		$max_booking_notice = $this->__get( 'max_booking_notice' );
		if ( empty( $max_booking_notice ) ) {
			return new SSA_Availability_Schedule();
		}

		$max_booking_notice_period = new Period(
			ssa_datetime()->add( new DateInterval( 'PT'.$max_booking_notice.'M' ) )->add( new DateInterval( 'PT'.$this->__get( 'duration' ).'M' ) ),
			SSA_Constants::EPOCH_END_DATE
		);

		if ( ! empty( $period ) && ! $max_booking_notice_period->overlaps( $period ) ) {
			return new SSA_Availability_Schedule();
		}

		$schedule = SSA_Availability_Schedule_Factory::available_for_period( $max_booking_notice_period, array(
			'capacity_available' => 0,
			'buffer_available' => $this->get_buffer_capacity_max(),
		) );

		return $schedule;
	}

	public function get_availability_window_schedule( Period $period = null, $args = array() ) {
		if ( empty( $args['appointment_type.availability_window'] ) ) {
			return new SSA_Availability_Schedule();
		}
		$availability_start_date = $this->__get( 'availability_start_date' );
		$availability_end_date = $this->__get( 'availability_end_date' );
		if ( empty( $availability_start_date ) && empty( $availability_end_date ) ) {
			return new SSA_Availability_Schedule();
		}

		if ( empty( $availability_start_date ) ) {
			$availability_start_date = SSA_Constants::EPOCH_START_DATE;
		}

		if ( empty( $availability_end_date ) ) {
			$availability_end_date = SSA_Constants::EPOCH_END_DATE;
		}

		$blocked_periods = SSA_Constants::EPOCH_PERIOD()->diff( new Period(
			$availability_start_date,
			$availability_end_date
		) );
		$schedule = new SSA_Availability_Schedule();
		if ( empty( $blocked_periods ) ) {
			return $schedule;
		}

		foreach ( $blocked_periods as $blocked_period ) {
			if ( ! $blocked_period->overlaps( $period ) ) {
				continue;
			}

			$schedule = $schedule->merge_min( SSA_Availability_Schedule_Factory::available_for_period( $blocked_period, array(
				'capacity_available' => 0,
				'buffer_available' => $this->get_buffer_capacity_max(),
			) ) );
		}

		return $schedule;
	}

	public function get_capacity_schedule( Period $period, $args = array() ) {
		$capacity_available = $this->__get( 'capacity' );
		$buffer_available = $this->get_buffer_capacity_max();

		$schedule = SSA_Availability_Schedule_Factory::available_for_period( $period, array(
			'capacity_available' => $capacity_available,
			'buffer_available' => $buffer_available,
		) );

		return $schedule;
	}

	public function get_appointment_schedule( Period $period, $args = array() ) {
		$appointment_schedule = ssa()->availability_cache->query( $this, $period, array_merge( $args, array(
			'appointment_type_id' => $this->id,
			'type' => 'appointment_type',
			'subtype' => 'appointments',
		) ) );

		if ( !empty( $appointment_schedule ) && ! $appointment_schedule->is_empty() ) {
			return $appointment_schedule;
		}

		$schedule = $this->get_capacity_schedule( $period );
		$appointments = $this->get_appointments( $period, array(
			'status' => SSA_Appointment_Model::get_unavailable_statuses(),
		) );

		$developer_settings = ssa()->developer_settings->get();
		if ( empty( $developer_settings['display_capacity_available'] ) ) {		
			// if we are showing the exact number of slots left, then we can't use this shortcut
			if ( $this->__get( 'capacity' ) > count( $appointments ) ) {
				// Skip appointment schedule calculation if total appointments are less than capacity.
				// For example, capacity is 100 but we only found 90 appointments in this period range
				return $schedule;
			}
		}

		foreach ($appointments as $key => $appointment) {
			$appointment = SSA_Appointment_Object::instance( $appointment );
			if ( ! empty( $args['skip_appointment_id'] ) && $args['skip_appointment_id'] === $appointment->id ) {
				continue;
			}
			$block = SSA_Availability_Block_Factory::create_from_appointment( $appointment );
			$schedule = $schedule->add_block( $block );
			
			$buffered_block = SSA_Availability_Block_Factory::create_from_buffered_appointment( $appointment );
			$schedule = $schedule->add_block( $buffered_block );
		}

		$schedule = $schedule->subrange( $period ); // cut off the edges
		ssa()->availability_cache->insert_schedule( $schedule, array_merge( $args, array(
			'appointment_type_id' => $this->id,
			'type' => 'appointment_type',
			'subtype' => 'appointments',
		) ) );

		return $schedule;
	}

	public function get_buffer_before_period( Period $appointment_period ) {
		$buffer_before = $this->__get( 'buffer_before' );
		if ( empty( $buffer_before ) ) {
			return false;
		}

		$buffer_before = '-' . absint( $buffer_before ) . ' MIN';
		$calculated_period = new Period( $appointment_period->getStartDate(), $appointment_period->getStartDate() );
		$calculated_period = $calculated_period->moveStartDate( $buffer_before );
		
		return $calculated_period;
	}

	public function get_buffer_after_period( Period $appointment_period ) {
		$buffer_after = $this->__get( 'buffer_after' );
		if ( empty( $buffer_after ) ) {
			return false;
		}

		$buffer_after = '+' . absint( $buffer_after ) . ' MIN';
		$calculated_period = new Period( $appointment_period->getEndDate(), $appointment_period->getEndDate() );
		$calculated_period = $calculated_period->moveEndDate( $buffer_after );
		
		return $calculated_period;
	}

	public function get_buffered_appointment_period( Period $appointment_period ) {
		$buffer_before_period = $this->get_buffer_before_period( $appointment_period );
		$buffer_after_period = $this->get_buffer_after_period( $appointment_period );

		if ( false === $buffer_before_period && false === $buffer_after_period ) {
			return $appointment_period;
		}

		$period = $appointment_period;
		if ( false !== $buffer_before_period ) {
			$period = new Period( $buffer_before_period->getStartDate(), $period->getStartDate() );
		}
		if ( false !== $buffer_after_period ) {
			$period = new Period( $period->getStartDate(), $buffer_after_period->getEndDate() );
		}

		return $period;
	}

	public function get_availability_interval() {
		$availability_increment = $this->__get( 'availability_increment' );
		return new DateInterval( 'PT'.$availability_increment.'M' );
	}

	public function get_duration_interval() {
		$duration = (int)$this->__get( 'duration' );
		return new DateInterval( 'PT'.$duration.'M' );
	}
	public function get_buffered_duration_interval() {
		$buffer_before = (int)$this->__get( 'buffer_before' );
		$duration = (int)$this->__get( 'duration' );
		$buffer_after = (int)$this->__get( 'buffer_after' );
		return new DateInterval( 'PT' . ( $buffer_before + $duration + $buffer_after ) . 'M' );
	}

	public function get_schedule( Period $query_period, $args = array() ) {
		$period = $query_period;
		$schedule = ssa()->availability_cache->query( $this, $period, array_merge( $args, array(
			'type' => 'appointment_type',
			'subtype' => 'schedule',
		) ) );

		if ( !empty( $schedule ) && ! $schedule->is_empty() ) {
			return $schedule;
		}

		// Not found in cache, so let's proceed with generating it
		$schedule = $this->get_business_hours_schedule( $period );

		// Minimum Booking Notice
		$min_booking_notice_schedule = $this->get_min_booking_notice_schedule( $period, $args );
		if ( $schedule->overlaps( $min_booking_notice_schedule ) ) {
			$schedule = $schedule->merge_min( $min_booking_notice_schedule );
			$min_start_date = $min_booking_notice_schedule->boundaries()->getEndDate();
			if ( $min_start_date >= $period->getEndDate() ) {
				$schedule = SSA_Availability_Schedule_Factory::available_for_period( $period, array(
							'capacity_available' => 0,
							'buffer_available' => $this->get_buffer_capacity_max(),
						) );
				return $schedule;
			}
			$period = new Period( $min_start_date, $period->getEndDate() );
		}

		if ( empty( $args['cache_level_write'] ) ) {
			// only process this server side when we aren't caching (we need to for availability detective purposes, but otherwise we don't want to cache because max_booking_notice window is always moving forward. A cached response would be invalid an hour later
			
			// Advance - Maximum Booking Notice
			$max_booking_notice_schedule = $this->get_max_booking_notice_schedule( $period, $args );
			if ( $schedule->overlaps( $max_booking_notice_schedule ) ) {
				$schedule = $schedule->merge_min( $max_booking_notice_schedule );
			}
		}

		// Availability Window
		if ( ssa()->settings_installed->is_enabled( 'advanced_scheduling' ) ) {
			$availability_window_schedule = $this->get_availability_window_schedule( $period, $args );
			if ( $schedule->overlaps( $availability_window_schedule ) ) {
				$schedule = $schedule->merge_min( $availability_window_schedule );
			}
		}


		// Enforce Maximum # of Appointments Per Day
		$max_per_day_schedule = $this->get_max_per_day_schedule( $period, $args );
		$schedule = $schedule->merge_min( $max_per_day_schedule );

		// Get Booked+Reserved Appointment Schedule
		if ( SSA_Constants::CAPACITY_MAX != $this->__get( 'capacity' ) ) {
			// No need to build the appointment schedule if we know that this appointment type is not constrained by capacity
			$appointment_schedule = $this->get_appointment_schedule( $period, $args );
			$schedule = $schedule->merge_min( $appointment_schedule );
		}

		$schedule = $schedule->subrange( $query_period ); // cut off the edges

		ssa()->availability_cache->insert_schedule( $schedule, array_merge( $args, array(
			'appointment_type_id' => $this->id,
			'type' => 'appointment_type',
			'subtype' => 'schedule',
		) ) );

		return $schedule;
	}

	public function get_appointments( Period $period=null, $args = array() ) {
		if ( ! empty( $this->appointments_fixture ) ) {
			return $this->appointments_fixture;
		}

		if ( ! empty( $period ) ) {
			$args = array_merge( $args, array(
				'intersects_period' => $period,
			) );
		}

		$args = array_merge( array(
			'number' => -1,
			'orderby' => 'start_date',
			'appointment_type_id' => $this->id,
		), $args );

		$appointments = ssa()->appointment_model->query( $args );
		$appointments = array_filter( $appointments, function( $appointment ) {
			if ( $appointment['start_date'] === '0000-00-00 00:00:00' ) {
				return false; // corrupted appointment that will block out too much
			}

			return true;
		});

		return $appointments;
	}

	public function get_appointment_objects( Period $period=null, $args = array() ) {
		$appointments = $this->get_appointments( $period, $args );
		$appointment_objects = array();
		foreach ( $appointments as $appointment ) {
			$appointment_objects[] = SSA_Appointment_Object::instance( $appointment['id'] );
		}

		return $appointment_objects;
	}

	public function get_timezone() {
		// TODO replace with appt type specific timezones (or staff or location)
		if ( empty( $this->timezone ) ) {
			$this->timezone = ssa()->utils->get_datetimezone( $this->id );
		}

		return $this->timezone;
	}

	public function get_business_hours_schedule( Period $period, $args = array() ) {
		$schedule = new SSA_Availability_Schedule();
		if ( 'start_times' === $this->__get( 'availability_type' ) ) {
			$schedule = $this->get_capacity_schedule( $period );
			// TODO: test start times
		} else if ( 'available_blocks' === $this->__get( 'availability_type' ) ) {
			$appointment_type_timezone = $this->get_timezone();
			$start_date = $period->getStartDate();

			$start_date = $start_date->setTimezone( $appointment_type_timezone );
			$earliest_possible_start_date_needed_for_schedule = $start_date->sub( new DateInterval( 'P1DT' . ( $this->buffer_before + $this->duration + $this->buffer_after ) . 'M' ) );
			$starting_date_string = $earliest_possible_start_date_needed_for_schedule->format( 'Y-m-d' );


			$availability = $this->__get( 'availability' );
			$capacity = $this->__get( 'capacity' );

			$today_tz = new DateTimeImmutable( $starting_date_string.' 00:00:00', $appointment_type_timezone );
			while ( $today_tz <= $period->getEndDate() ) {
				$blocks = array();
				$day_of_week = $today_tz->format( 'l' );
				$tomorrow_tz = $today_tz->add( new DateInterval( 'P1D' ) );

				if ( empty( $availability[$day_of_week]['0']['time_start'] ) ) {
					// unavailable this entire day
					$blocks[] = SSA_Availability_Block_Factory::available_for_period(
						new Period(
							$today_tz->setTimezone( new DateTimezone( 'UTC' ) ),
							$tomorrow_tz->setTimezone( new DateTimezone( 'UTC' ) )
						), array(
						'capacity_available' => 0,
						'buffer_available' => $this->get_buffer_capacity_max()
					) );

					$today_tz = $tomorrow_tz;
					$schedule = $schedule->pushmerge( $blocks );
					continue;
				}

				$current_hour = 0;
				$current_datetime = $today_tz;

				foreach ( $availability[$day_of_week] as $time_window_key => $time_window ) {
					$time_start_pieces = explode( ':', $time_window['time_start'] );
					$block_start = $current_datetime->setTime( (int)$time_start_pieces[0], (int)$time_start_pieces[1] );

					$time_end_pieces = explode( ':', $time_window['time_end'] );
					$block_end = $current_datetime->setTime( (int)$time_end_pieces[0], (int)$time_end_pieces[1] );

					if ( $current_hour < $time_start_pieces[0] ) {					
						$blocks[] = SSA_Availability_Block_Factory::available_for_period(
							new Period(
								$current_datetime->setTimezone( new DateTimezone( 'UTC' ) ),
								$block_start->setTimezone( new DateTimezone( 'UTC' ) )
							), array(
							'capacity_available' => 0,
							'buffer_available' => $this->get_buffer_capacity_max(),
						) );
					}

					$blocks[] = SSA_Availability_Block_Factory::available_for_period(
						new Period(
							$block_start->setTimezone( new DateTimezone( 'UTC' ) ),
							$block_end->setTimezone( new DateTimezone( 'UTC' ) )
						), array(
						'capacity_available' => $capacity,
						'buffer_available' => $this->get_buffer_capacity_max(),
					) );

					$current_datetime = $block_end;
				}

				if ( $current_datetime < $tomorrow_tz ) {
					$blocks[] = SSA_Availability_Block_Factory::available_for_period(
						new Period(
							$current_datetime->setTimezone( new DateTimezone( 'UTC' ) ),
							$tomorrow_tz->setTimezone( new DateTimezone( 'UTC' ) )
						), array(
						'capacity_available' => 0,
						'buffer_available' => $this->get_buffer_capacity_max(),
					) );
				}

				$today_tz = $tomorrow_tz;
				$schedule = $schedule->pushmerge( $blocks );
			}

			$schedule = $schedule->subrange( $period );

		}

		return $schedule;
	}

	public function is_group_event() {
		$capacity_type = $this->__get( 'capacity_type' );
		return ( $capacity_type === 'group' );
	}

	public function is_individual_appointment() {
		$capacity_type = $this->__get( 'capacity_type' );
		return ( $capacity_type === 'individual' );
	}

	public function get_status() {
		$status = $this->__get( 'status' );
		return $status;
	}

	public function update_status( $status ) {
		$appointment_type_update_data = array(
			'status' => $status,
		);

		$update = ssa()->appointment_type_model->update( $this->id, $appointment_type_update_data );

		return $update;
	}

}
