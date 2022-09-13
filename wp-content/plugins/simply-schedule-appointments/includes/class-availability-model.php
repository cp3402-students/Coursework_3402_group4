<?php
/**
 * Simply Schedule Availabilities Availabilities Model.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Availabilities
 */
use League\Period\Period;

/**
 * Simply Schedule Availabilities Availabilities Model.
 *
 * @since 0.0.3
 */
class SSA_Availability_Model extends SSA_Db_Model {
	protected $slug = 'availability';
	protected $pluralized_slug = 'availability';
	protected $version = '1.9.2';

	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.2
	 *
	 * @var   Simply_Schedule_Availabilities
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.2
	 *
	 * @param  Simply_Schedule_Availabilities $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		// $this->version = $this->version.'.'.time(); // dev mode
		parent::__construct( $plugin );

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.2
	 */
	public function hooks() {

	}

	public function belongs_to() {
		return array(
			'AppointmentType' => array(
				'model' => $this->plugin->appointment_type_model,
				'foreign_key' => 'appointment_type_id',
			),
		);
	}

	protected $schema = array(
		'start_date' => array(
			'field' => 'start_date',
			'label' => 'Start Date',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'datetime',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => true,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'end_date' => array(
			'field' => 'end_date',
			'label' => 'End Date',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'datetime',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => true,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'capacity_available' => array(
			'field' => 'capacity_available',
			'label' => 'Capacity Available',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'MEDIUMINT',
			'mysql_length' => 6,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'capacity_reserved' => array(
			'field' => 'capacity_reserved',
			'label' => 'Capacity Reserved',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'MEDIUMINT',
			'mysql_length' => 6,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'buffer_available' => array(
			'field' => 'buffer_available',
			'label' => 'Buffer Available',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'MEDIUMINT',
			'mysql_length' => 6,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'buffer_reserved' => array(
			'field' => 'buffer_reserved',
			'label' => 'Buffer Reserved',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'MEDIUMINT',
			'mysql_length' => 6,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'capacity_meta' => array(
			'field' => 'capacity_meta',
			'label' => 'Capacity Meta',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => true,
			'mysql_extra' => '',
			'cache_key' => false,
			'encoder' => 'json',
		),
		'appointment_type_id' => array(
			'field' => 'appointment_type_id',
			'label' => 'Appointment Type ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'staff_id' => array(
			'field' => 'staff_id',
			'label' => 'Staff Id',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 11,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'type' => array(
			'field' => 'type',
			'label' => 'Type',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '16',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'subtype' => array(
			'field' => 'subtype',
			'label' => 'Subtype',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '16',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'description' => array(
			'field' => 'description',
			'label' => 'Description',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TEXT',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'status' => array(
			'field' => 'status',
			'label' => 'Status',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => 250,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'is_all_day' => array(
			'field' => 'is_all_day',
			'label' => 'Is All Day Event?',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => 250,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'cache_key' => array(
			'field' => 'cache_key',
			'label' => 'Cache Key',
			'default_value' => false,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 11,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'cache_args_hash' => array(
			'field' => 'cache_args_hash',
			'label' => 'Args Hash',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'INT',
			'mysql_length' => 10,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'date_created' => array(
			'field' => 'date_created',
			'label' => 'Date Created',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'datetime',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'date_modified' => array(
			'field' => 'date_modified',
			'label' => 'Date Modified',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'datetime',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'availability_type' => array(
			'field' => 'availability_type',
			'label' => 'Availability Type',
			'default_value' => false, // 'busy', 'buffer', 'free',
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '8',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'is_available' => array(
			'field' => 'is_available',
			'label' => 'Is Available',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'TINYINT',
			'mysql_length' => 1,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'availability_score' => array(
			'field' => 'availability_score',
			'label' => 'Availability Score',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'INT',
			'mysql_length' => 3,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
	);

	public $indexes = array(
		'appointment_type_id' => [ 'appointment_type_id' ],
		'is_available' => [ 'is_available' ],
		'start_date' => [ 'start_date' ],
		'end_date' => [ 'end_date' ],
		'type' => [ 'type' ],
		'subtype' => [ 'subtype' ],
		'cache_key' => [ 'cache_key' ],
		'cache_args_hash' => [ 'cache_args_hash' ],
		'date_created' => [ 'date_created' ],
		'cache_query' => [ 'cache_args_hash', 'start_date', 'end_date', 'cache_key' ],
	);

	public function filter_where_conditions( $where, $args ) {
		if ( !empty( $args['appointment_type_id'] ) ) {
			$where .= ' AND appointment_type_id="'.sanitize_text_field( $args['appointment_type_id'] ).'"';
		}

		if ( !empty( $args['staff_id'] ) ) {
			$where .= ' AND staff_id="'.sanitize_text_field( $args['staff_id'] ).'"';
		}
		
		if ( isset( $args['is_available'] ) ) {
			$where .= ' AND is_available="'.sanitize_text_field( $args['is_available'] ).'"';
		}

		if ( isset( $args['type'] ) ) {
			$where .= ' AND type="'.sanitize_text_field( $args['type'] ).'"';
		}

		if ( isset( $args['subtype'] ) ) {
			$where .= ' AND subtype="'.sanitize_text_field( $args['subtype'] ).'"';
		}

		if ( isset( $args['cache_args_hash'] ) ) {
			$where .= ' AND cache_args_hash='.sanitize_text_field( $args['cache_args_hash'] );
		}

		if ( isset( $args['cache_key'] ) ) {
			$where .= ' AND cache_key="'.sanitize_text_field( $args['cache_key'] ).'"';
		}

		if ( isset( $args['intersects_period'] ) ) {
			if ( $args['intersects_period'] instanceof Period ) {
				$start_date_string = $args['intersects_period']->getStartDate()->format( 'Y-m-d H:i:s' );
				$end_date_string = $args['intersects_period']->getEndDate()->format( 'Y-m-d H:i:s' );
				
				// it should END in the queried period
				// OR 
				// it should START in the queried period
				// OR
				// it should CONTAIN the queried period
				$where .= " AND (
					(end_date >= '{$start_date_string}' AND end_date <= '{$end_date_string}' )
					OR
					(start_date <= '{$end_date_string}' AND start_date >= '{$start_date_string}' )
					OR
					(start_date <= '{$start_date_string}' AND end_date >= '{$end_date_string}' )
				)";
			}
		}

		return $where;
	}

	public function update_rows( $availability_period_rows, $args = array() ) {
		$args = array_merge( array(
			'cache_key' => time(),
			'type' => '',
			'subtype' => '',
		), $args );

		$execution_datetime = gmdate( 'Y-m-d H:i:s' );
		global $wpdb;

		foreach ($availability_period_rows as $key => $availability_period_row) {
			$availability_period_row['cache_key'] = $args['cache_key'];
			$response = $this->insert( $availability_period_row );
		}

		$query = "DELETE FROM {$this->get_table_name()} WHERE 1=1";
		$query = $wpdb->prepare( $query .= " AND cache_key < %d", $args['cache_key'] );
		$query = $wpdb->prepare( $query .= " AND start_date >= %s", $args['start_date_min'] );
		$query = $wpdb->prepare( $query .= " AND start_date <= %s", $args['start_date_max'] );
		$query = $wpdb->prepare( $query .= " AND type = %s", $args['type'] );
		$query = $wpdb->prepare( $query .= " AND subtype = %s", $args['subtype'] );
		$result = $wpdb->query( $query );

	}

	public function bulk_delete( $args=array() ) {
		return $this->db_bulk_delete( $args );
	}

	public function register_routes() {
		// override register_routes() because we don't want any default behavior reading/writing to this database table
		$namespace = $this->api_namespace.'/v' . $this->api_version;
		$base = $this->get_api_base();

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'availability_query' ),
// 'permission_callback' => array( $this, 'get_items_permissions_check' ),
'permission_callback' => '__return_true',
				'args'            => array(

				),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/troubleshoot', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'troubleshoot' ),
// 'permission_callback' => array( $this, 'get_items_permissions_check' ),
'permission_callback' => '__return_true',
				'args'            => array(
					'start_date' => array(
						'required' => true,
					),
					'end_date' => array(
						'required' => false,
					),
					'appointment_type_id' => array(
						'required' => true,
					),
				),
			),
		) );
	}

	public function availability_query( $request ) {
		$params = $request->get_params();
		$appointment_type = SSA_Appointment_Type_Object::instance( (int) $params['appointment_type_id'] );
		try {
			$appointment_type_title = $appointment_type->title;
		} catch ( Exception $e ) {
			return 'appointment_type_not_found';
		}

		$start_date = ssa_datetime( $params['start_date'] ); // TODO: handle invalid dates?

		if ( ! empty( $params['end_date'] ) ) {
			$end_date = ssa_datetime( $params['end_date'] );
			$period = new Period( $start_date, $end_date );
		} else {
			$period = new Period( $start_date, $start_date->add( $appointment_type->get_duration_interval() ) );
		}

		$availability_query = new SSA_Availability_Query(
			$appointment_type,
			$period,
			array(
				'cache_level_read' => 1,
				'cache_level_write' => 1,
			)
		);
		$bookable_start_datetime_strings = $availability_query->get_bookable_appointment_start_datetime_strings();
		if ( ! empty( $bookable_start_datetime_strings['0']['start_date'] ) ) {
			$bookable_start_datetime_strings = wp_list_pluck( $bookable_start_datetime_strings, 'start_date' );
		}

		return $bookable_start_datetime_strings;
	}

	public function troubleshoot( $request ) {
		$params = $request->get_params();
		$appointment_type = SSA_Appointment_Type_Object::instance( (int) $params['appointment_type_id'] );
		try {
			$appointment_type_title = $appointment_type->title;
		} catch ( Exception $e ) {
			return 'appointment_type_not_found';
		}

		$start_date = ssa_datetime( $params['start_date'] ); // TODO: handle invalid dates?

		if ( ssa_datetime() > $start_date ) {
			return array( 
				'is_bookable' => false,
				'reasons' => array(
					array(
						'reason' => 'start_date_past',
						'reason_title' => __( 'That time has already passed', 'simply-schedule-appointments' ),
						'reason_description' => __( 'The date you selected is in the past, so a customer can\'t book it – without a time machine :)', 'simply-schedule-appointments' ),
						'help_center_url' => '',
					)
				)
			);
		}

		if ( ! empty( $params['end_date'] ) ) {
			$end_date = ssa_datetime( $params['end_date'] );
			$period = new Period( $start_date, $end_date );
		} else {
			$period = new Period( $start_date, $start_date->add( $appointment_type->get_duration_interval() ) );
		}

		$appointment = SSA_Appointment_Factory::create( $appointment_type, array(
			'id' => 0,
			'start_date' => $start_date->format( 'Y-m-d H:i:s' ),
		) );
		$public_query = new SSA_Availability_Query(
			$appointment_type,
			$period,
			array(
				'cache_level_read' => false,
				'cache_level_write' => false,
			)
		);

		if ( $public_query->is_prospective_appointment_bookable( $appointment ) ) {
			return array(
				'is_bookable' => true,
				'reasons' => array(
					array(
						'is_bookable' => true,
						'reason' => 'available',
						'reason_title' => __( 'Time slot is available', 'simply-schedule-appointments' ),
						'reason_description' => __( 'The date and time you selected is available to be booked.', 'simply-schedule-appointments' ),
						'help_center_url' => '',
						'ssa_path' => '',
					)
				)
			);
		}

		// the time isn't bookable, so let's find out why
		$case = new SSA_Availability_Detective_Case(
			$appointment_type,
			$period,
			$appointment
		);
		$case = $case->investigate();
		if ( ! empty( $case->culprits ) ) {
			$response = array(
				'is_bookable' => false,
				'cleared' => $case->cleared,
				'culprits' => $case->culprits,
				'reasons' => array()
			);
			
			if ( in_array( 'appointment_type.min_booking_notice', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'appointment_type.min_booking_notice',
					'reason_title' => __( 'Minimum booking notice', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is sooner than your minimum booking notice.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( in_array( 'appointment_type.max_booking_notice', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'appointment_type.max_booking_notice',
					'reason_title' => __( 'Advance', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is later than your maximum advance booking notice.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( in_array( 'appointment_type.max_per_day', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'appointment_type.max_per_day',
					'reason_title' => __( 'Maximum # Per Day', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is on a date that has already reached your setting for the maximum number of appointments per day.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( in_array( 'appointment_type.availability_window', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'appointment_type.availability_window',
					'reason_title' => __( 'Availability Window', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is outside the availability window you set for this appointment type.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( empty( $response['reasons'] ) && in_array( 'appointment_type', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'appointment_type',
					'reason_title' => __( 'Appointment Type settings', 'simply-schedule-appointments' ),
					'reason_description' => __( 'Please contact support to get help looking at your appointment type settings.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( in_array( 'blackout_dates', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'blackout_dates',
					'reason_title' => __( 'Blackout Dates', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is on a global blackout date.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			if ( in_array( 'google_calendar', $case->culprits ) ) {
				$response['reasons'][] = array(
					'reason' => 'google_calendar',
					'reason_title' => __( 'Google Calendar', 'simply-schedule-appointments' ),
					'reason_description' => __( 'The date and time you selected is unavailable because of an event on an excluded Google Calendar.', 'simply-schedule-appointments' ),
					'help_center_url' => '',
					'ssa_path' => '',
				);
			}

			return $response;
		}

		// we couldn't detect a reason, so let's recommend contacting support
		return array(
			'is_bookable' => false,
			'reasons' => array(
				array(
					'reason' => 'unknown',
					'reason_title' => __( 'Please contact support', 'simply-schedule-appointments' ),
					'reason_description' => __( 'This time is unavailable, but we are unable to detect the reason automatically. Please contact support.', 'simply-schedule-appointments' ),
					'ssa_path' => '/ssa/support/help',
				)
			)
		);
	}

	public function investigate( $case, SSA_Appointment_Type_Object $appointment_type, Period $period, SSA_Appointment_Object $appointment ) {
		// try required but not cleared
		// if no uncleared requirements, try cleared + next suspect, one by one
		// try combinations of suspects (google calendar + staff)
		if ( empty( $case['suspects'] ) ) {
			return $case;
		}

		$suspect_under_interrogation = array_shift( $case['suspects'] );
		$solo_interrogation_args = array_merge(
			$case['required'],
			array( $suspect_under_interrogation )
		);

		$solo_interrogation_query = new SSA_Availability_Query(
			$appointment_type,
			$period,
			array_merge( $solo_interrogation_args, array(
				'cache_level_read' => false,
				'cache_level_write' => false,
			) )
		);

		if ( $solo_interrogation_query->is_prospective_appointment_bookable( $appointment ) ) {
			$case['cleared'][] = $suspect_under_interrogation;
		} else {
			$case['culprits'][] = $suspect_under_interrogation;
		}

		return $this->investigate( $case, $appointment_type, $period, $appointment );
	}
}
