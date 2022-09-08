<?php
/**
 * Simply Schedule Appointments Availability Query.
 *
 * @since   3.6.2
 * @package Simply_Schedule_Appointments
 */

use League\Period\Period;

/**
 * Simply Schedule Appointments Availability Query.
 *
 * @since 3.6.2
 */
class SSA_Availability_Query {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.6.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */

	protected $period;

	protected $appointment_type;

	protected $args = array(
		// 'staff_ids_all_required' => array(),
		// 'staff_ids_some_required' => array(),
		// 'staff_required_count' => 1,

		// 'location_ids_all_required' => -1,
		// 'location_ids_some_required' => -1,
		// 'any_location_count' => -1,

		// 'resource_ids_all_required' => -1,
		// 'resource_ids_some_required' => -1,
		// 'any_resource_count' => -1,

		'cache_level_read' => 1,
		'cache_level_write' => 1,

		'type' => '',
		'subtype' => '',

		'appointment_type' => true,
		'appointment_type.min_booking_notice' => true,
		'appointment_type.max_booking_notice' => true,
        'appointment_type.availability_window' => true,
        'appointment_type.max_per_day' => true,
        // 'appointment_type.booking_window' => true,
        // 'appointment_type.buffers' => true,
        // 'appointment_type.capacity' => true,
		
		'blackout_dates' => true,
		'google_calendar' => true,
		'staff' => true,
	);

	protected $schedule;
	protected $staff_schedules;

	protected $_queried_appointments;
	protected $_booked_group_appointments;

	public static function create( SSA_Appointment_Type_Object $appointment_type, Period $period, $args = array() ) {
		$instance = new self( $appointment_type, $period, $args );
		return $instance;
	}

	/**
	 * Constructor.
	 *
	 * @since  3.6.2
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( 
		SSA_Appointment_Type_Object $appointment_type, Period $period, $args = array()
	) {
		$this->period = $period;
		$this->args = array_merge( $this->args, $args );

		$this->appointment_type = $appointment_type;
	}

	public function get_query_args() {
		return array(
			'period' => $this->period,
			'args' => $this->args,
			'appointment_type_id' => $this->appointment_type->id,
			'query_hash' => $this->get_query_hash(),
		);
	}

	public function get_query_hash() {
		$args = $this->args;
		unset( $args['cache_level_write'] );
		unset( $args['cache_level_read'] );

		$json = json_encode( array(
			'period' => $this->period,
			'args' => $args,
			'appointment_type' => $this->appointment_type->id,
		) );

		return ssa_int_hash( $json );
	}

	public function get_schedule_for_staff_id( int $staff_id, $args = array() ) {
		if ( ! empty( $this->staff_schedules[$staff_id] ) ) {
			$this->staff_schedules[$staff_id];
		}

		$staff = new SSA_Staff_Object( $staff_id );
		$this->staff_schedules[$staff_id] = $staff->get_schedule( $this->appointment_type, $this->period, $args );
		return $this->staff_schedules[$staff_id];
	}

	public function get_schedule( $args = array() ) {
		if ( null !== $this->schedule ) {
			return $this->schedule;
		}

		$args = array_merge( $this->args, $args );

		$chunk_interval = new DateInterval( 'P7D' );
		$maximum_chunk_period = $this->period->withDuration( $chunk_interval );
		if ( $this->period->durationGreaterThan( $maximum_chunk_period ) ) {
			$schedule = new SSA_Availability_Schedule();
			foreach ( $this->period->split( $chunk_interval ) as $chunk_period ) {
				$chunk_query = new SSA_Availability_Query(
					$this->appointment_type,
					$chunk_period,
					$this->args
				);
				$chunk_schedule = $chunk_query->get_schedule( $args );
				$schedule = $schedule->pushmerge( $chunk_schedule->get_blocks() );
			}

			$this->schedule = $schedule;
			return $this->schedule;
		}

		$query_period = $this->period;
		$this->period = new Period(
			$this->period->getStartDate()->sub($this->appointment_type->get_buffered_duration_interval() ),
			$this->period->getEndDate()->add( $this->appointment_type->get_buffered_duration_interval() )
		);
		$this->schedule = $this->appointment_type->get_schedule( $this->period, $args );

		if ( ! empty( $args['blackout_dates'] ) ) {
			$blackout_dates_schedule = ssa()->blackout_dates->get_schedule( $this->appointment_type, $this->period, $args );

			if ( null !== $blackout_dates_schedule ) {
				$this->schedule = $this->schedule->merge_min( $blackout_dates_schedule );
			}
		}

		if ( empty( $this->appointment_type ) ) {
			return $this->schedule;
		}

		if ( ! empty( $args['google_calendar'] ) ) {
			$google_calendar_schedule = ssa()->google_calendar->get_schedule( $this->appointment_type, $this->period, $args );
			if ( null !== $google_calendar_schedule ) {
				$google_calendar_schedule = $google_calendar_schedule->subrange( $this->period );
				$this->schedule = $this->schedule->merge_min( $google_calendar_schedule );
			}
		}

		if ( ! empty( $args['staff'] ) ) {
			if ( ssa()->settings_installed->is_activated( 'staff' ) ) {
				$staff_schedule = ssa()->staff->get_schedule( $this->appointment_type, $this->period, $args );

				if ( null !== $staff_schedule ) {
					$this->schedule = $this->schedule->merge_min( $staff_schedule );
				}
			}
		}

		$this->schedule = $this->schedule->subrange( $query_period ); // cut off the edges
		$this->period = $query_period;

		return $this->schedule;
	}

	public function why_not_bookable() {
		// TODO // so we can return an error code "blackout dates"
	}

	public function get_bookable_appointments() {
		if ( empty( $this->appointment_type ) ) {
			throw new SSA_Exception( 'Appointment Type required' );
		}

		$schedule = $this->get_schedule();

		$bookable_appointments = array();

		$availability_interval = $this->appointment_type->get_availability_interval();
		$availability_increment = $this->appointment_type->availability_increment;
		if ( ! $availability_interval instanceof DateInterval ) {
			$availability_interval = new DateInterval( 'PT15M' );
		}
		$availability = $this->appointment_type->availability;
		$timezone = $this->appointment_type->get_timezone();
		$duration = $this->appointment_type->duration;
		$capacity_type = $this->appointment_type->capacity_type;
		if ( 'group' === $capacity_type ) {
			$booked_group_appointments = $this->appointment_type->get_appointment_objects( $this->period, array(
				'status' => SSA_Appointment_Model::get_unavailable_statuses(),
			) );
		}

		foreach ($schedule->get_blocks() as $block) {
			if ( $block->capacity_available <= 0 ) {
				continue;
			}

			$starting_minute = (int)$block->get_period()->getStartDate()->setTimezone( $timezone )->format( 'i' );
			$minutes_to_add = 0;
			while( ( $starting_minute + $minutes_to_add ) % $availability_increment !== 0 ) {
				$minutes_to_add++;
			}
			if ( $minutes_to_add ) {
				$start_date = $block->get_period()->getStartDate();
				$end_date = $block->get_period()->getEndDate();
				$new_start_date = $start_date->add( new DateInterval( 'PT'.$minutes_to_add.'M' ) );
				if ( $new_start_date >= $end_date ) {
					continue;
				}
				$block = $block->set_period( new Period( $new_start_date, $end_date ) );
			}

			foreach ( $block->get_period()->split( $availability_interval ) as $period ) {
				if ( 'start_times' === $this->appointment_type->availability_type ) {
					$start_datetime_tz = $period->getStartDate()->setTimezone( $timezone );
					$day_of_week = $start_datetime_tz->format( 'l' );
					if ( empty( $availability[$day_of_week]['0']['time_start'] ) ) {
						continue; // no start times set for this day of the week
					}

					$is_available_start_time = false;
					foreach ( $availability[$day_of_week] as $availability_value ) {
						if ( $availability_value['time_start'] === $start_datetime_tz->format( 'H:i:s' ) ) {
							$is_available_start_time = true;
							break;
						}
					}

					if ( ! $is_available_start_time ) {
						continue;
					}
				}

				if ( 'group' === $capacity_type ) {
					foreach ( $booked_group_appointments as $booked_group_appointment ) {
						if ( $booked_group_appointment->get_buffered_period()->overlaps( $period ) ) {
							if ( $booked_group_appointment->get_appointment_period()->getStartDate() != $period->getStartDate() ) {
								continue 2;
							}
						}
					}
				}

				$appointment = SSA_Appointment_Factory::create( $this->appointment_type, array(
					'id' => 0,
					'start_date' => $period->getStartDate()->format( 'Y-m-d H:i:s' ),
				) );
				if ( ! $this->is_prospective_appointment_bookable( $appointment ) ) {
					continue;
				}

				$bookable_appointments[] = $appointment;
			}
		}

		return $bookable_appointments;
	}

	public function get_bookable_appointment_periods() {
		$bookable_appointment_periods = array();
		$bookable_appointments = $this->get_bookable_appointments();
		foreach ($bookable_appointments as $appointment) {
			$bookable_appointment_periods[] = $appointment->get_appointment_period();
		}

		return $bookable_appointment_periods;
	}

	public function get_bookable_appointment_start_datetime_strings_plucked() {
		$bookable_start_datetime_strings = $this->get_bookable_appointment_start_datetime_strings();
		if ( empty( $bookable_start_datetime_strings['0']['start_date'] ) ) {
			return array();
		}
		$bookable_start_datetime_strings = wp_list_pluck( $bookable_start_datetime_strings, 'start_date' );
		return $bookable_start_datetime_strings;
	}

	public function get_bookable_appointment_start_datetime_strings() {
		$query_hash = $this->get_query_hash();
		$cache_key = 'availability/'.$query_hash.'/bookable_datetime_strings';
		if ( ! empty( $this->args['cache_level_read'] ) && $this->args['cache_level_read'] == 1 ) {		
			if ( ssa()->availability_cache->is_enabled() ) {
				$cached = ssa_cache_get( $cache_key );
				if ( $cached !== false && is_array( $cached ) ) {
					if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
						foreach ($cached as &$value) {
							if ( isset( $value['capacity_available'] ) ) {
								unset( $value['capacity_available'] );
							}
						}
					}

					$excluded_start_datetimes = ssa_cache_get('availability/appointment_type/' . $this->appointment_type->id . '/excluded_start_datetimes');
					if ( ! empty($excluded_start_datetimes) ) {
						$cached = array_filter($cached, function( $value ) use ( $excluded_start_datetimes ) {
							return ! in_array( $value['start_date'], $excluded_start_datetimes );
						});
						$cached = array_values( $cached ); // needed to keep JSON response an array
					}

					return $cached;
				}
			}
		}

		$developer_settings = ssa()->developer_settings->get();
		$bookable_start_datetimes = $this->get_bookable_appointment_start_datetimes();
		$data = array();
		$schedule = $this->get_schedule();
		foreach ($bookable_start_datetimes as $start_datetime) {
			$data_array = array(
				'start_date' => $start_datetime->format('Y-m-d H:i:s'),
			);
			if ( ! empty( $developer_settings['display_capacity_available'] ) ) {
				$block = $schedule->get_block_for_date( $start_datetime->format('Y-m-d H:i:s') );
				if ( false === $block ) {
					ssa_debug_log( 'get_bookable_appointment_start_datetime_strings(): block not found in schedule: ' . $start_datetime->format( 'Y-m-d H:i:s' ) );
				} else {
					$data_array['capacity_available'] = $block->capacity_available;
				}
			}
			$data[] = $data_array;
		}

		if ( ! empty( $this->args['cache_level_write'] ) && $this->args['cache_level_write'] == 1 ) {		
			if ( ssa()->availability_cache->is_enabled() ) {
				ssa_cache_set(
					$cache_key,
					$data
				);
				ssa()->availability_cache->remember_recent( 'availability_query_args', $this->get_query_args(), 10 );
			}
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			foreach ($data as &$value) {
				if ( isset( $value['capacity_available'] ) ) {
					unset( $value['capacity_available'] );
				}
			}
		}
		return $data;
	}

	public function get_bookable_appointment_start_datetimes() {
		$bookable_appointment_start_datetimes = array();
		$bookable_appointments = $this->get_bookable_appointments();
		foreach ($bookable_appointments as $appointment) {
			$bookable_appointment_start_datetimes[] = $appointment->get_appointment_period()->getStartDate();
		}

		return $bookable_appointment_start_datetimes;
	}

	public function get_queried_appointments() {
		if ( null !== $this->_queried_appointments ) {
			return $this->_queried_appointments;
		}

		$queried_appointments_array = $this->get_queried_appointments_array();

		$queried_appointments = array();
		foreach ($queried_appointments_array as $queried_appointment) {
			$queried_appointment = SSA_Appointment_Object::instance( $queried_appointment );
			$queried_appointments[] = $queried_appointment;
		}

		$this->_queried_appointments = $queried_appointments;
		return $this->_queried_appointments;
	}

	public function get_queried_appointments_array() {
		$args = array(
			'number' => -1,
			'orderby' => 'start_date',
			// 'appointment_type_id' => $appointment_type->id,
			'intersects_period' => $this->period,
			'status' => SSA_Appointment_Model::get_unavailable_statuses(),
		);

		$queried_appointments_array = ssa()->appointment_model->query( $args );
		return $queried_appointments_array;
	}

	public function get_booked_group_appointments() {
		if ( null !== $this->_booked_group_appointments ) {
			return $this->_booked_group_appointments;
		}

		if ( null === $this->appointment_type ) {
			return;
		}

		$this->_booked_group_appointments = $this->appointment_type->get_appointment_objects( $this->period, array(
			'status' => SSA_Appointment_Model::get_unavailable_statuses(),
		) );

		return $this->_booked_group_appointments;
	}

	public function is_prospective_appointment_bookable( SSA_Appointment_Object $appointment ) {
		$schedule = $this->get_schedule( array(
			'skip_appointment_id' => $appointment->id,
		) );
		if ( null === $schedule ) {
			return false;
		}

		if ( ! empty( $this->appointment_type ) ) {
			// We should use the appointment type that we're querying for if it's set (faster than getting appointment type dynamically each run)
			$appointment_type = $this->appointment_type;
		} else {
			$appointment_type = $appointment->get_appointment_type();
		}

		if ( ! $schedule->is_appointment_period_available( $appointment, $appointment_type ) ) {
			return false;
		}

		$appointment_buffered_period = $appointment->get_buffered_period();
		$appointment_period = $appointment->get_appointment_period();

		if ( 'group' === $appointment_type->capacity_type ) {
			$booked_group_appointments = $this->get_booked_group_appointments();

			if ( ! empty( $booked_group_appointments ) ) {
				foreach ( $booked_group_appointments as $booked_group_appointment ) {
					if ( $booked_group_appointment->get_buffered_period()->overlaps( $appointment_buffered_period ) ) {
						// There might be a potential conflict

						if ( $booked_group_appointment->get_appointment_period()->getStartDate() != $appointment_period->getStartDate() ) {
							// And we've confirmed it's not the exact same start time (since that would be allowed)

							if ( $booked_group_appointment->get_buffered_period()->overlaps( $appointment_period ) ) {
								return false;
							}
							
							if ( $booked_group_appointment->get_appointment_period()->overlaps( $appointment_buffered_period ) ) {
								return false;
							}
						}
					}
				}
			}
		}

		// If `any` team member is selected, we need to check each team member since the merge_max can't cover every scenario
		if (!empty($this->args['staff'])) {
			if (ssa()->settings_installed->is_activated('staff')) {
				$appointment_type_staff_settings = $appointment_type->staff;
				if ( ! empty( $appointment_type_staff_settings ) && ! empty( $appointment_type_staff_settings['required'] ) ) {
					if ( 'any' === $appointment_type_staff_settings['required'] ) {
						// static $total;
						// static $unavailable;
						// ssa_debug_log( $total++ . ': Trying to find staff member for ' . $appointment->start_date, 10 );
						// proactive and effective, but very slow (about 4x slower):
						// TODO: only run the following block in async mode so the slow-building cache can be as correct as possible
						// $data = ssa()->staff->assign_appointment_to_staff( array(
						// 	'appointment_type_id' => $appointment_type,  // the assign_appointment_to_staff function will use the SSA_Appointment_Type object we're passing as `appointment_type_id` and save a lookup step
						// 	'start_date' => $appointment->start_date, 
						// 	// 'availability_query' => $this, 
						// ) );

						// if ( empty( $data['staff_ids'] ) ) {
						// 	// assigning to a staff member would end up empty, so we shouldn't allow this appointment to be booked
						// 	ssa_debug_log($unavailable++ . ' UNAVAILABLE: Trying to find staff member for ' . $appointment->start_date, 10);
						// 	return false;
						// }
					}
				}
			}
		}

		// Other Appointment Types Shared Availability
		$developer_settings = ssa()->developer_settings->get();
		$separate_appointment_type_availability = $developer_settings['separate_appointment_type_availability'];
		if ( $separate_appointment_type_availability ) {
			return true;
		}

		// TODO: use cached appointment schedule for all other appointment types?
		$queried_appointments = $this->get_queried_appointments();

		if ( empty( $queried_appointments ) ) {
			return true;
		}

		foreach ($queried_appointments as $queried_appointment) {
			if ( $queried_appointment->appointment_type_id == $appointment_type->id ) {
				continue; // this is already checked and accounted for in the appointment_type object's get_schedule() function
			}

			if ( $appointment_buffered_period->overlaps( $queried_appointment->get_appointment_period() )) {
				return false;
			}

			if ( $appointment_period->overlaps( $queried_appointment->get_buffered_period() ) ) {
				return false;
			}
		}

		return true;
	}



}
