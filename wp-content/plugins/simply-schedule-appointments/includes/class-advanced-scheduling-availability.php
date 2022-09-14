<?php
/**
 * Simply Schedule Appointments Advanced Scheduling Availability.
 *
 * @since   3.5.3
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Advanced Scheduling Availability.
 *
 * @since 3.5.3
 */
class SSA_Advanced_Scheduling_Availability {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.5.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.5.3
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
	 * @since  3.5.3
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'add_filters' ), 1 );
	}

	public function add_filters() {
		add_filter( 'ssa/appointment_type/prepare_item_for_response', array( $this, 'maybe_return_falsey_values' ) );

		$is_enabled = $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' );
		if ( ! $is_enabled ) {
			return;
		}

		add_filter( 'ssa/get_blocked_periods/blocked_periods', array( $this, 'filter_blocked_periods' ), 1, 3 );
	}

	public function maybe_return_falsey_values( $item ) {
		$is_enabled = $this->plugin->settings_installed->is_enabled( 'advanced_scheduling' );
		if ( $is_enabled ) {
			return $item;
		}

		$keys_to_redact = array(
			'availability_start_date',
			'availability_end_date',
			'booking_start_date',
			'booking_end_date',
		);
		foreach ($keys_to_redact as $key) {
			if ( isset( $item[$key] ) ) {
				$item[$key] = '';
			}
		}

		if ( isset( $item['max_booking_notice'] ) ) {
			$item['max_booking_notice'] = YEAR_IN_SECONDS / 60;
		}

		return $item;
	}

	public function filter_blocked_periods( $blocked_periods, $appointment_type, $args ) {
		if ( ! empty( $appointment_type['availability_start_date'] ) && $appointment_type['availability_start_date'] !== '0000-00-00 00:00:00' ) {
			$availability_start_date = $appointment_type['availability_start_date'];

			if ( !empty( $appointment_type['buffer_before'] ) ) {
				// If availability_start_date time and appointment type's start time are the same, make sure the buffer doesn't eliminate potential time slots at the start (like 9am if both start at 9am)
				$availability_start_date = ssa_datetime( $availability_start_date )->sub( new DateInterval( 'PT'.absint( $appointment_type['buffer_before'] ).'M' ) )->format( 'Y-m-d H:i:s' );
			}

			$blocked_period = new Period( ssa_datetime( $availability_start_date )->sub( new DateInterval( 'P10Y' ) ), ssa_datetime( $availability_start_date ) );

			$blocked_periods[] = $blocked_period;
		}

		if ( ! empty( $appointment_type['availability_end_date'] ) && $appointment_type['availability_end_date'] !== '0000-00-00 00:00:00' ) {
			$availability_end_date = $appointment_type['availability_end_date'];

			if ( !empty( $appointment_type['buffer_after'] ) ) {
				// If availability_end_date time and appointment type's end time are the same, make sure the buffer doesn't eliminate potential time slots at the end
				$availability_end_date = ssa_datetime( $availability_end_date )->add( new DateInterval( 'PT'.absint( $appointment_type['buffer_after'] ).'M' ) )->format( 'Y-m-d H:i:s' );
			}

			$blocked_period = new Period( ssa_datetime( $availability_end_date ), ssa_datetime( $availability_end_date )->add( new DateInterval( 'P10Y' ) ) );
			$blocked_periods[] = $blocked_period;
		}

		return $blocked_periods;
	}
}
