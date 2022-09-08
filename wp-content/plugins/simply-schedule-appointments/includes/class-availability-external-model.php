<?php
/**
 * Simply Schedule Appointments Availability External Model.
 *
 * @since   4.0.8.beta4
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Availability External Model.
 *
 * @since 4.0.8.beta4
 */
class SSA_Availability_External_Model extends SSA_Db_Model {
	protected $slug = 'availability_external';
	protected $pluralized_slug = 'availability_external';
	protected $version = '1.1.3';

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

	protected $schema = array(
		'staff_id' => array(
			'field' => 'staff_id',
			'label' => 'Staff ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'appointment_id' => array(
			'field' => 'appointment_id',
			'label' => 'Appointment ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'appointment_type_id' => array(
			'field' => 'appointment_type_id',
			'label' => 'Appointment ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
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
		'service' => array(
			'field' => 'service',
			'label' => 'Service',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '8',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
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
		'status' => array(
			'field' => 'status',
			'label' => 'Status',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => 20,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'account_id' => array(
			'field' => 'account_id',
			'label' => 'Account ID',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TINYTEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'calendar_id' => array(
			'field' => 'calendar_id',
			'label' => 'Calendar ID',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TINYTEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'calendar_id_hash' => array(
			'field' => 'calendar_id_hash',
			'label' => 'Calendar ID Hash',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'INT',
			'mysql_length' => 10,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'ical_uid' => array(
			'field' => 'ical_uid',
			'label' => 'iCal UID',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TINYTEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'event_id' => array(
			'field' => 'event_id',
			'label' => 'Event ID',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TINYTEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'is_all_day' => array(
			'field' => 'is_all_day',
			'label' => 'Is All Day Event?',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'TINYINT',
			'mysql_length' => 1,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'transparency' => array(
			'field' => 'transparency',
			'label' => 'Description',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => 20,
			'mysql_unsigned' => false,
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
	);

	public $indexes = array(
		'staff_id' => [ 'staff_id' ],
		'calendar_id_hash' => [ 'calendar_id_hash' ],
		'is_available' => [ 'is_available' ],
		'start_date' => [ 'start_date' ],
		'end_date' => [ 'end_date' ],
		'type' => [ 'type' ],
		'subtype' => [ 'subtype' ],
		'service' => [ 'service' ],
		'date_created' => [ 'date_created' ],
	);

	public function filter_where_conditions( $where, $args ) {
		if ( !empty( $args['calendar_id_hash_IN'] ) ) {
			if ( is_array( $args['calendar_id_hash_IN'] ) ) {
				$calendar_id_hash_csv = implode( ',', $args['calendar_id_hash_IN'] );
			} else {
				$calendar_id_hash_csv = $args['calendar_id_hash_IN'];
			}

			$where .= ' AND calendar_id_hash IN ('.sanitize_text_field( $calendar_id_hash_csv ).')';
		}

		if ( !empty( $args['staff_id'] ) ) {
			$where .= ' AND staff_id='.sanitize_text_field( $args['staff_id'] );
		}
		
		if ( !empty( $args['calendar_id_hash'] ) ) {
			$where .= ' AND calendar_id_hash='.sanitize_text_field( $args['calendar_id_hash'] );
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

		if ( isset( $args['service'] ) ) {
			$where .= ' AND service="'.sanitize_text_field( $args['service'] ).'"';
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
			'service' => '',
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
		$query = $wpdb->prepare( $query .= " AND service = %s", $args['service'] );
		$result = $wpdb->query( $query );

	}

	public function bulk_delete( $args=array() ) {
		return $this->db_bulk_delete( $args );
	}
}
