<?php
/**
 * Simply Schedule Appointments Appointment Types Db.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Types Db.
 *
 * @since 0.0.3
 */
class SSA_Appointment_Types_Db extends SSA_Db_Model {
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
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'ssa_appointment_types';
		$this->slug  = 'appointment_type';
		$this->primary_key = 'appointment_type_id';
		$this->version     = '0.9'.time();
		$this->maybe_create_table();
		$this->post_id_field = false;

		parent::__construct();

		$this->plugin = $plugin;
		$this->hooks();
	}

	public $indexes = array(
		'appointment_type_id' => [ 'appointment_type_id' ],
		'date_created' => [ 'date_created' ],
		'date_modified' => [ 'date_modified' ],
	);

	public $schema = array(
		'appointment_type_id' => array(
			'field' => 'appointment_type_id',
			'label' => 'ID',
			'default_value' => false,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => 'AUTO_INCREMENT',
			'cache_key' => false,
		),
		'name' => array(
			'field' => 'name',
			'label' => 'Name',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '255',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'title' => array(
			'field' => 'title',
			'label' => 'Title',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '255',
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

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks() {

	}
}
