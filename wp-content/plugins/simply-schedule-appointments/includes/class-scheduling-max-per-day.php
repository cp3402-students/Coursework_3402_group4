<?php
/**
 * Simply Schedule Appointments Scheduling Max Per Day.
 *
 * @since   1.8.3
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Scheduling Max Per Day.
 *
 * @since 1.8.3
 */
class SSA_Scheduling_Max_Per_Day {
	/**
	 * Parent plugin class.
	 *
	 * @since 1.8.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;
	protected $booked_count_by_appointment_type_id_and_local_start_date = array();

	/**
	 * Constructor.
	 *
	 * @since  1.8.3
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
	 * @since  1.8.3
	 */
	public function hooks() {
		add_filter( 'ssa/get_blocked_periods/blocked_periods', array( $this, 'filter_blocked_periods' ), 10, 3 );
		add_filter( 'ssa/get_booked_periods/booked_periods', array( $this, 'calculate_daily_booked_counts' ), 10, 2 );
	}

	public function calculate_daily_booked_counts( array $booked_periods, $appointment_type ) {
		$this->booked_count_by_appointment_type_id_and_local_start_date = array();

		foreach ($booked_periods as $key => $booked_period) {
			$start_datetime = $booked_period->getStartDate();
			$local_start_datetime = $this->plugin->utils->get_datetime_as_local_datetime( $start_datetime, $appointment_type['id'] );
			$local_start_date_string = $local_start_datetime->format( 'Y-m-d' );

			$updated_count = (empty($this->booked_count_by_appointment_type_id_and_local_start_date[$appointment_type['id']][$local_start_date_string])) ? 1 : $this->booked_count_by_appointment_type_id_and_local_start_date[$appointment_type['id']][$local_start_date_string] + 1;

			$this->booked_count_by_appointment_type_id_and_local_start_date[$appointment_type['id']][$local_start_date_string] = $updated_count;

		}

		return $booked_periods;
	}

	public function filter_blocked_periods( array $blocked_periods, $appointment_type, $args ) {
		if ( empty( $appointment_type['max_event_count'] ) ) {
			return $blocked_periods;
		}

		if ( empty( $this->booked_count_by_appointment_type_id_and_local_start_date[$appointment_type['id']] ) ) {
			return $blocked_periods;
		}

		foreach ($this->booked_count_by_appointment_type_id_and_local_start_date[$appointment_type['id']] as $start_date_string => $count) {
			if ( $count < $appointment_type['max_event_count'] ) {
				continue;
			}

			$start_datetime = new DateTimeImmutable( $start_date_string.' 00:00:00', $this->plugin->utils->get_datetimezone( $appointment_type['id'] ) );

			$blocked_period = Period::createFromDuration( $start_datetime, '1 DAY' );
			$blocked_periods[] = $blocked_period;
		}

		$this->booked_count_by_appointment_type_id_and_local_start_date = array();
		return $blocked_periods;
	}
}
