<?php
/**
 * Simply Schedule Appointments Appointment Meta Db.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Meta Db.
 *
 * @since 0.0.3
 */
class SSA_Appointment_Meta_Db extends TD_DB_Model {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	protected $db_namespace = 'ssa';

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'ssa_appointmentmeta';
		$this->slug  = 'appointmentmeta';
		$this->primary_key = 'appointmentmeta_id';
		$this->version     = '1.0';
		$this->maybe_create_table();
		$this->post_id_field = false;

		parent::__construct( $plugin );

		$this->plugin = $plugin;
		$this->hooks();
	}

	public $indexes = array(
		'appointment_id' => [ 'appointment_id' ],
		'meta_key' => [ 'meta_key' ],
		'date_created' => [ 'date_created' ],
	);

	public $schema = array(
		'appointmentmeta_id' => array(
			'field' => 'appointmentmeta_id',
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
		'appointment_id' => array(
			'field' => 'appointment_id',
			'label' => 'ID',
			'default_value' => false,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'user_id' => array(
			'field' => 'user_id',
			'label' => 'ID',
			'default_value' => false,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'meta_key' => array(
			'field' => 'meta_key',
			'label' => 'Meta Key',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '255',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'meta_value' => array(
			'field' => 'meta_value',
			'label' => 'Meta Value',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'LONGTEXT',
			'mysql_length' => '',
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
	);

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks() {

	}
}
