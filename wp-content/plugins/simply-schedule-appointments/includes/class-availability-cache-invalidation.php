<?php
/**
 * Simply Schedule Appointments Availability Cache Invalidation.
 *
 * @since   4.0.1
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Availability Cache Invalidation.
 *
 * @since 4.0.1
 */
class SSA_Availability_Cache_Invalidation {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.0.1
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.0.1
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
	 * @since  4.0.1
	 */
	public function hooks() {
		add_action( 'ssa/settings/blackout_dates/updated', array( $this, 'invalidate_global_setting' ), 1000, 2 );
		add_action( 'ssa/settings/advanced_scheduling/updated', array( $this, 'invalidate_global_setting' ), 1000, 2 );
		add_action( 'ssa/settings/google_calendar/updated', array( $this, 'invalidate_global_setting' ), 1000, 2 );

		add_action( 'ssa/appointment/after_insert', array( $this, 'invalidate_appointment' ), 50, 2 );
		add_action( 'ssa/appointment/after_update', array( $this, 'invalidate_appointment' ), 50, 2 );
		// add_action( 'ssa/appointment/after_delete', array( $this, 'delete_appointment', 1000, 2 );

		add_action( 'ssa/appointment_type/after_update', array( $this, 'invalidate_appointment_type'), 1000, 2 );
		add_action( 'ssa/appointment_type/after_delete', array( $this, 'invalidate_appointment_type'), 1000, 1 );

		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	public function invalidate_everything( $args = array() ) {
		if ( ! empty( $args['invalidate_period'] ) && $args['invalidate_period'] instanceof Period ) {
			$this->plugin->availability_model->bulk_delete( array(
				'intersects_period' => $args['invalidate_period'],
			) );
		} else {
			$this->plugin->availability_model->truncate();
		}
		$this->increment_cache_version();
	}

	public static function get_cache_version() {
		$cache_version = get_transient( 'ssa/cache_version' );

		if ( false === $cache_version ) {
			$cache_version = 0;
		}

		return $cache_version;
	}

	public static function get_cache_group() {
		return 'v'.Simply_Schedule_Appointments::VERSION.'/'.self::get_cache_version();
	}

	public function increment_cache_version() {
		$cache_version = $this->get_cache_version();
		if ( false === $cache_version ) {
			$cache_version = 0;
		}

		static $has_expired_all_transients = false; // we only want to do this once in case increment_cache_version() gets called multiple times in a request
		if ( false === $has_expired_all_transients ) {
			$this->plugin->availability_cache->delete_all_transients();
			$has_expired_all_transients = true;
		}
		$cache_version++;
		// $is_cache_already_locked = get_transient( 'ssa/cache/lock_global' );
		// if ( ! $is_cache_already_locked ) {
		// 	set_transient( 'ssa/cache/lock_global', time(), 30 );
		// 	if ( ssa_doing_async() ) {
		// 		add_filter( 'ssa/shutdown/populate_cache', '__return_true' );
		// 	} else {
		// 		ssa_queue_action(
		// 			'populate_cache',
		// 			'ssa_populate_cache',
		// 			10000,
		// 			array(),
		// 			'',
		// 			0,
		// 			'',
		// 			array(
		// 				'date_queued' => gmdate( 'Y-m-d H:i:s' )
		// 			)
		// 		);
		// 	}
		// }
		set_transient( 'ssa/cache_version', $cache_version, MONTH_IN_SECONDS );
		return $cache_version;
	}

	public function shutdown() {
		if ( apply_filters( 'ssa/shutdown/populate_cache', false ) ) {
			$this->plugin->availability_cache->populate_cache();
		}
		if ( apply_filters( 'ssa/shutdown/delete_all_transients', false ) ) {
			$this->plugin->availability_cache->delete_all_transients();
		}
	}

	public function invalidate_global_setting( $new_settings = array(), $old_settings = array() ) {
		$this->invalidate_type( 'appointment_type' );
		$this->increment_cache_version();

		$this->plugin->availability_cache->delete_expired_transients();
		// $this->invalidate_type( 'staff' );
		// $this->invalidate_type( 'resource' );
		// $this->invalidate_type( 'location' );
		// $this->invalidate_type( 'global' );
	}

	public function invalidate_type( $type ) {
		$this->plugin->availability_model->bulk_delete( array(
			'type' => $type,
		) );
		$this->increment_cache_version();
	}

	public function invalidate_subtype( $type, $subtype ) {
		$this->plugin->availability_model->bulk_delete( array(
			'type' => $type,
			'subtype' => $subtype,
		) );
		$this->increment_cache_version();
	}

	public function invalidate_appointment( $appointment_id, $data ) {
		if ( empty( $appointment_id ) ) {
			return;
		}
		
		if ( ! empty( $data['appointment_type_id'] ) ) {
			$appointment_type_id = $data['appointment_type_id'];
		}

		$appointment = SSA_Appointment_Object::instance( $appointment_id );
		if ( ! $appointment instanceof SSA_Appointment_Object ) {
			return;
		}

		if ( empty( $appointment_type_id ) ) {
			$appointment_type_id = $appointment->appointment_type_id;
		}
		if ( empty( $appointment_type_id ) ) {
			return;
		}


		if (! empty( $data['staff_ids'] ) ) {
			$staff_ids = $data['staff_ids'];
		} else {
			$staff_ids = $this->plugin->staff_appointment_model->get_staff_ids( $appointment_id );
		}

		$invalidate_args = array(
			'invalidate_period' => $appointment->get_buffered_period(),
		);
		if ( ! empty( $staff_ids ) ) {
			foreach ($staff_ids as $staff_id) {
				$this->invalidate_staff_id( $staff_id, $invalidate_args );
			}
		}
		
		$this->invalidate_appointment_type( $appointment_type_id, $invalidate_args );

		$this->increment_cache_version();
	}

	public function invalidate_prospective_appointment( SSA_Appointment_Object $appointment ) {
		$args = array(
			'appointment_type_id' => $appointment->get_appointment_type()->id,
			'intersects_period' => $appointment->get_buffered_period(),
		);
		$this->plugin->availability_model->bulk_delete( $args );

		$this->increment_cache_version();
	}

	public function invalidate_appointment_type( $appointment_type_id, $data = array() ) {
		$developer_settings = $this->plugin->developer_settings->get();

		if ( empty( $developer_settings['separate_appointment_type_availability'] ) ) {
			$this->invalidate_everything( $data );
			$this->plugin->google_calendar->maybe_queue_refresh_check( $appointment_type_id );
			return;
		}

		$args =  array(
			'appointment_type_id' => $appointment_type_id,
		);
		if ( ! empty( $data['invalidate_period'] ) && $data['invalidate_period'] instanceof Period ) {
			$args = array_merge( $args, array(
				'intersects_period' => $data['invalidate_period'],
			) );
		}
		$this->plugin->availability_model->bulk_delete( $args );
		$this->plugin->google_calendar->maybe_queue_refresh_check( $appointment_type_id );
		if ( ! empty( $data['invalidate_period'] ) && $data['invalidate_period'] instanceof Period ) {		
			$appointment_type = new SSA_Appointment_Type_Object( $appointment_type_id );
			$availability_query = new SSA_Availability_Query(
				$appointment_type,
				$data['invalidate_period'],
				array(
					'cache_level_read' => 2,
					'cache_level_write' => 2,
				)
			);
			$availability_query->get_schedule();
		}
		$this->increment_cache_version();
	}

	public function invalidate_staff_id( $staff_id, $data = array() ) {
// TODO: make caching appointment type ID aware
		$args =  array();
		if ( ! empty( $data['invalidate_period'] ) && $data['invalidate_period'] instanceof Period ) {
			$args = array(
				'intersects_period' => $data['invalidate_period'],
			);
		}
		$this->plugin->availability_model->bulk_delete( array_merge( $args, array(
			'type' => 'staff',
			'staff_id' => $staff_id,
			// 'appointment_type_id' => $appointment_type_id,
		) ) );
		$this->plugin->availability_model->bulk_delete( array_merge( $args, array(
			'type' => 'staff',
			'subtype' => 'any',
			// 'appointment_type_id' => $appointment_type_id,
		) ) );
		$this->plugin->availability_model->bulk_delete( array_merge( $args, array(
			'type' => 'staff',
			'subtype' => 'all',
			// 'appointment_type_id' => $appointment_type_id,
		) ) );

		$this->increment_cache_version();
		// find all appointment types using staff and this specific ID
		// $appointment_type_ids = $this->plugin->staff_appointment_type_model->get_appointment_type_ids( $staff_id );
		// foreach ($appointment_type_ids as $appointment_type_id) {
		// 	$this->invalidate_appointment_type( $appointment_type_id, $data );
		// }
	}

	public function invalidate_google_calendar_id( $calendar_id ) {
		// TODO
		// query and find staff that match this calendar id
		// foreach ($variable as $key => $value) {
		// $this->invalidate_staff_id( $staff_id );
		// }

		// query and find appointment types that match this calendar id
		// foreach ($variable as $key => $value) {
		// $this->invalidate_appointment_type( $appointment_type_id );
		// }
		$this->increment_cache_version();
	}
}
