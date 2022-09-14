<?php
/**
 * Simply Schedule Appointments Availability Cache.
 *
 * @since   4.0.1
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Availability Cache.
 *
 * @since 4.0.1
 */
class SSA_Availability_Cache {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.0.1
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	const CACHE_MODE_DISABLED = 0;
	const CACHE_MODE_ENABLED = 10;

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
		// add_action( 'ssa_populate_cache', array( $this, 'async_process_populate_cache' ), 10, 2 );
	}

	public function generate_cache_key( $args ) {
		$args = $this->get_args( $args );
		$args['start_date'] = '1970-01-01';
		$args['end_date'] = '1970-01-01';
		$availability_id = $this->plugin->availability_model->insert( $args );

		$obj_cache_key = 'args:'.$args['cache_args_hash'].'/latest_cache_key';
		ssa_cache_set( $obj_cache_key, $availability_id );

		return $availability_id;
	}

	public function get_cache_args_hash( $args ) {
		return ssa_int_hash( json_encode( $args ) );
	}

	public function get_args( $args ) {
		$args = shortcode_atts( array(
			'appointment_type_id' => 0,
			'appointment_id' => 0,
			'staff_id' => 0,
			'type' => '',
			'subtype' => '',
			'skip_appointment_id' => '',

			'cache_key' => '',
			'cache_args_hash' => '',
			'cache_force' => false,
			'cache_level_read' => 1,
			'cache_level_write' => 1,
		), $args );

		$args_to_hash = $args;
		$args_to_hash['cache_key'] = '';
		$args_to_hash['cache_args_hash'] = '';
		$args_to_hash['cache_level_read'] = '';
		$args_to_hash['cache_level_write'] = '';
		$args_to_hash['cache_force'] = false;

		$args['cache_args_hash'] = $this->get_cache_args_hash( $args_to_hash );

		return $args;
	}

	public function get_cache_mode() {
		$developer_settings = $this->plugin->developer_settings->get();
		if ( ! empty( $developer_settings['disable_availability_caching'] ) ) {
			return self::CACHE_MODE_DISABLED;
		}

		return self::CACHE_MODE_ENABLED;
	}

	public function is_cache_mode( $mode ) {
		return $mode === $this->get_cache_mode();
	}

	public function is_enabled() {
		return ! $this->is_cache_mode( self::CACHE_MODE_DISABLED );
	}

	public function get_latest_cache_key( $args ) {
		$args = $this->get_args( $args );

		$obj_cache_key = 'args:'.$args['cache_args_hash'].'/latest_cache_key';

		$latest_cache_key = ssa_cache_get( $obj_cache_key );
		if ( false !== $latest_cache_key ) {
			return $latest_cache_key;
		}

		global $wpdb;
		$sql = 'SELECT cache_key FROM '.$this->plugin->availability_model->get_table_name().' WHERE cache_args_hash=%d ORDER BY cache_key DESC LIMIT 1';
		$sql = $wpdb->prepare(
			$sql,
			$args['cache_args_hash']
		);
		$latest_cache_key = $wpdb->get_row( $sql, ARRAY_A );
		$latest_cache_key = $latest_cache_key['cache_key'];
		ssa_cache_set( $obj_cache_key, $latest_cache_key );

		return $latest_cache_key;
	}

	public function query( SSA_Appointment_Type_Object $appointment_type, Period $query_period, $args ) {
		if ( ! $this->is_enabled() ) {
			return;
		}
		if ( empty( $args['cache_level_read'] ) || $args['cache_level_read'] > 2 ) {		
			return;
		}

		$appointment_type_id = ( empty( $appointment_type ) ) ? 0 : $appointment_type->id;
		if ( ! empty( $appointment_type_id ) || empty( $args['appointment_type_id'] ) ) {
			$args['appointment_type_id'] = $appointment_type_id;
		}
		$args = $this->get_args( $args );

		$query_args = array(
			'number' => -1,
			'cache_args_hash' => $args['cache_args_hash'],
			'intersects_period' => $query_period,
		);
		// $latest_cache_key = $this->get_latest_cache_key( $args );
		// if ( ! empty( $latest_cache_key ) ) {
		// 	$query_args['cache_key'] = $latest_cache_key;
		// }

		$availability_rows = $this->plugin->availability_model->query( $query_args );

		$schedule = new SSA_Availability_Schedule();
		$availability_blocks = array();
		foreach ($availability_rows as $availability_row) {
			if ( $availability_row['start_date'] > $availability_row['end_date'] ) {
				ssa_debug_log( 'availability-cache query()', 100 );
				ssa_debug_log( 'Invalid Period returned in query', 100 );
				$this->plugin->availability_model->truncate();
				return;
			}
			unset( $availability_row['id'] );
			$availability_row = shortcode_atts( array(
				'capacity_available' => '',
				'capacity_reserved' => '',
				'buffer_available' => '',
				'buffer_reserved' => '',
				'period' => new Period(
					$availability_row['start_date'],
					$availability_row['end_date']
				),
			), $availability_row );
			$availability_block = SSA_Availability_Block_Factory::create( $availability_row );
			$availability_blocks[] = $availability_block;
		}
		$schedule = $schedule->pushmerge( $availability_blocks );

		if ( $schedule->is_empty() ) {
			return;
		}
		$boundaries = $schedule->boundaries();
		if ( empty( $boundaries ) || ! $boundaries instanceof Period ) {
			return;
		}

		if ( ! $boundaries->contains( $query_period ) ) {
			return;
		}

		if ( ! $schedule->is_continuous() ) {
			return;
		}

		return $schedule;
	}

	private function insert( SSA_Availability_Block $block, $args = array() ) {
		if ( ! $this->is_enabled() ) {
			return;
		}
		// $args = $this->get_args( $args ); // <--- not needed if insert() remains a private function

		$args = array_merge( $args, array(
			'start_date' => $block->get_period()->getStartDate()->format( 'Y-m-d H:i:s' ),
			'end_date' => $block->get_period()->getEndDate()->format( 'Y-m-d H:i:s' ),
			'capacity_reserved' => $block->capacity_reserved,
			'capacity_available' => $block->capacity_available,
		) );

		$availability_id = $this->plugin->availability_model->db_insert( $args );
	}

	public function deprecated_merge_and_update_schedule( SSA_Availability_Schedule $new_schedule, $args = array() ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$boundaries = $new_schedule->boundaries();
		if ( ! empty( $boundaries ) ) {		
			$old_schedule = $this->query( SSA_Appointment_Type_Object::null(), $boundaries , $args );
			if ( empty( $old_schedule ) || $old_schedule->is_empty() ) {
				$this->insert_schedule( $new_schedule, $args );
				return;
			}

			if ( $boundaries->contains( $old_schedule->boundaries() ) ) {
				$this->insert_schedule( $new_schedule, $args );
				return;
			}
		}
		
		$merged_schedule = $old_schedule->merge_min( $new_schedule );
		$this->insert_schedule( $merged_schedule, $args );
	}

	public function insert_schedule( SSA_Availability_Schedule $schedule, $args = array() ) {
		if ( ! $this->is_enabled() ) {
			return;
		}
		if ( empty( $args['cache_level_write'] ) || $args['cache_level_write'] > 2 ) {		
			return;
		}
		$args = $this->get_args( $args );

		unset( $args['cache_level_read'] );
		unset( $args['cache_level_write'] );
		$args['cache_key'] = $this->generate_cache_key( $args );

		$availability_rows = array();
		foreach ($schedule->get_blocks() as $block) {
			$availability_rows[] = array_merge( $args, array(
				'start_date' => $block->get_period()->getStartDate()->format( 'Y-m-d H:i:s' ),
				'end_date' => $block->get_period()->getEndDate()->format( 'Y-m-d H:i:s' ),
				'capacity_reserved' => $block->capacity_reserved,
				'capacity_available' => $block->capacity_available,
				'buffer_reserved' => $block->buffer_reserved,
				'buffer_available' => $block->buffer_available,
			) );
		}
		$this->plugin->availability_model->db_bulk_insert( $availability_rows );

		$this->delete_schedule( $schedule, $args['cache_args_hash'], $args['cache_key'] );
	}

	public function delete_schedule( SSA_Availability_Schedule $schedule, $cache_args_hash, $below_this_cache_key = null ) {
		$boundaries = $schedule->boundaries();
		if ( empty( $boundaries ) ) {
			return;
		}
		global $wpdb;
		$start_date_string = $boundaries->getStartDate()->format( 'Y-m-d H:i:s' );
		$end_date_string = $boundaries->getEndDate()->format( 'Y-m-d H:i:s' );

		$sql = 'DELETE FROM '.$this->plugin->availability_model->get_table_name()." WHERE cache_args_hash=%d AND (
			(end_date > '{$start_date_string}' AND end_date < '{$end_date_string}' )
			OR
			(start_date < '{$end_date_string}' AND start_date > '{$start_date_string}' )
			OR
			(start_date < '{$start_date_string}' AND end_date > '{$end_date_string}' )
		)"; // same as intersects_period, except we use < rather than <= (so we don't delete the neighboring availability). This will delete any availability cache that starts in the period OR ends in the period OR contains the entire period

		$sql = $wpdb->prepare(
			$sql, array(
				$cache_args_hash,
			)
		);
		if ( empty( $below_this_cache_key ) ) {
			$this->wpdb_query_while_preventing_deadlock($sql);
			return;
		}

		$sql .= $wpdb->prepare( ' AND cache_key < %d', $below_this_cache_key );
		$this->wpdb_query_while_preventing_deadlock($sql);

		$sql = 'DELETE FROM '.$this->plugin->availability_model->get_table_name().' WHERE id=%d';
		$sql = $wpdb->prepare(
			$sql,
			$below_this_cache_key
		);
		$this->wpdb_query_while_preventing_deadlock( $sql );
	}

	private function wpdb_query_while_preventing_deadlock( $sql ) {
		global $wpdb;
		// This query can potentially be deadlocked if there is
		// high concurrency, in which case DB will abort the query which has done less work to resolve deadlock.
		// We will try up to 3 times before giving up.
		for ($count = 0; $count < 3; $count++) {
			$result = $wpdb->query( $sql ); // WPCS: unprepared SQL ok.
			if ( false !== $result ) {
				break;
			}
		}

		return $result;
	}

	public static function object_cache_get( $key, $group = '', $force = false, &$found = null ) {
		if ( ! ssa()->availability_cache->is_enabled() ) {
			return false;
		}

		$group .= SSA_Availability_Cache_Invalidation::get_cache_group();
		$key = 'ssa/' . $group . '/' . $key;

		return get_transient( $key );
	}

	public static function object_cache_set( $key, $data, $group = '', $expire = 0 ) {
		if ( ! ssa()->availability_cache->is_enabled() ) {
			return false;
		}

		$group .= SSA_Availability_Cache_Invalidation::get_cache_group();
		$key = 'ssa/' . $group . '/' . $key;
		if ( empty( $expire ) ) {
			$expire = WEEK_IN_SECONDS;
		}

		set_transient( $key, $data, $expire );
		return true;
	}

	public function remember_recent( $key, $value, $number_to_remember = 10 ) {
		$key = 'ssa/recent_'.$key;
		$recent_values = get_transient( $key );
		if ( empty( $recent_values ) ) {
			$recent_values = array();
		}
		$recent_values[] = $value;
		if ( count( $recent_values ) > $number_to_remember ) {
			array_shift( $recent_values );
		}

		set_transient( $key, $recent_values, MONTH_IN_SECONDS );
	}

	public function async_process_populate_cache( $payload, $async_action ) {
		// this won't be called until re-enabled in hooks()
		$start = microtime(true);
		$this->populate_cache();
		$end = microtime(true);
		ssa_complete_action( $async_action['id'], 'Execution time: '.( $end - $start ) . 's' );
	}
	
	public function populate_cache() {
		return; // disable
		
		if ( ! $this->is_enabled() ) {
			return;
		}

		$developer_settings = $this->plugin->developer_settings->get();
		if ( empty( $developer_settings['populate_cache'] ) ) {
			return;
		}

		$recent_availability_query_args = get_transient( 'ssa/recent_availability_query_args' );
		if ( empty( $recent_availability_query_args ) ) {
			return;
		}
		$recent_availability_query_args = array_reverse( $recent_availability_query_args ); // so start with the most recent queries
		$hashes_to_process = array();
		$availability_query_args_to_process = array();
		foreach ($recent_availability_query_args as $value) {
			if ( in_array( $value['query_hash'], $hashes_to_process ) ) {
				continue;
			}
			$query_hash = $value['query_hash'];
			$hashes_to_process[] = $query_hash;
			set_transient( 'ssa/cache/lock_'.$query_hash, true, 30 );
			unset( $value['query_hash'] );
			$availability_query_args_to_process[$query_hash] = $value;
			if ( count( $availability_query_args_to_process ) >= 5 ) {
				break;
			}
		}
		set_transient( 'ssa/cache/lock_global', false, 0 ); // once we've locked individual queries, unlock the global


		foreach ($availability_query_args_to_process as $query_hash => $value) {
			$appointment_type = new SSA_Appointment_Type_Object( $value['appointment_type_id'] );
			$availability_query = new SSA_Availability_Query(
				$appointment_type,
				$value['period'],
				$value['args']
			);
			$bookable_start_datetime_strings = $availability_query->get_bookable_appointment_start_datetime_strings();
			set_transient( 'ssa/cache/lock_'.$query_hash, false, 5 );
		}
	}

	public static function delete_expired_transients( $force_db = false ) {
		global $wpdb;

		if ( ! $force_db && wp_using_ext_object_cache() ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
				AND b.option_value < %d",
				$wpdb->esc_like( '_transient_ssa/' ) . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		if ( ! is_multisite() ) {
			// Single site stores site transients in the options table.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
					AND b.option_value < %d",
					$wpdb->esc_like( '_site_transient_ssa/' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
					time()
				)
			);
		} elseif ( is_multisite() && is_main_site() && is_main_network() ) {
			// Multisite stores site transients in the sitemeta table.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
					WHERE a.meta_key LIKE %s
					AND a.meta_key NOT LIKE %s
					AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
					AND b.meta_value < %d",
					$wpdb->esc_like( '_site_transient_ssa/' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
					time()
				)
			);
		}
	}

	public static function delete_all_transients( $force_db = false ) {
		global $wpdb;

		if ( ! $force_db && wp_using_ext_object_cache() ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE a FROM {$wpdb->options} a
				WHERE (a.option_name LIKE %s
				OR a.option_name LIKE %s)",
				$wpdb->esc_like( '_transient_ssa/v' ) . '%',
				$wpdb->esc_like( '_transient_timeout_ssa/v' ) . '%'
			)
		);

		if ( ! is_multisite() ) {
			// Single site stores site transients in the options table.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE a FROM {$wpdb->options} a
					WHERE a.option_name LIKE %s
					OR a.option_name LIKE %s",
					$wpdb->esc_like( '_site_transient_ssa/v' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_ssa/v' ) . '%'
				)
			);
		} elseif ( is_multisite() && is_main_site() && is_main_network() ) {
			// Multisite stores site transients in the sitemeta table.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE a FROM {$wpdb->sitemeta} a
					WHERE a.meta_key LIKE %s
					OR a.meta_key LIKE %s",
					$wpdb->esc_like( '_site_transient_ssa/v' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_ssa/v' ) . '%'
				)
			);
		}
	}

}
