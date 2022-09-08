<?php
abstract class TD_Hooks_Model extends TD_DB_Model {
	protected $slug = 'hook';
	protected $version = '1.8.3';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   0.0.2
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );

		$this->hooks_hooks();
	}

	public function hooks_hooks() {

	}

	protected $indexes = array(
		'author_id' => [ 'author_id' ],
		'status' => [ 'status' ],
		'date_created' => [ 'date_created' ],
		'date_modified' => [ 'date_modified' ],
	);

	protected $schema = array(
		'author_id' => array(
			'field' => 'author_id',
			'label' => 'Author',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 11,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
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

		'object' => array(
			'field' => 'object',
			'label' => 'Object',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '255',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'object_id' => array(
			'field' => 'object_id',
			'label' => 'Object ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 11,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'object_type' => array(
			'field' => 'object_type',
			'label' => 'Object Type',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '255',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'object_data' => array(
			'field' => 'object_data',
			'label' => 'Object Data',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TEXT',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,

			'type' => 'array',
			'encoder' => 'json',
		),

		'date_created' => array(
			'field' => 'date_created',
			'label' => 'Date Created',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'DATETIME',
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
			'mysql_type' => 'DATETIME',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'date_queued' => array(
			'field' => 'date_queued',
			'label' => 'Date Queued',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'DATETIME',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'date_processed' => array(
			'field' => 'date_processed',
			'label' => 'Date Processed',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'DATETIME',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'process_batch_token' => array(
			'field' => 'process_batch_token',
			'label' => 'Process Batch Token',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '50',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'date_dispatched' => array(
			'field' => 'date_dispatched',
			'label' => 'Date Dispatched',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'DATETIME',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

	);

	public function get_items_permissions_check( $request ) {
		return false;
	}
	public function get_item_permissions_check( $request ) {
		return false;
	}
	public function create_item_permissions_check( $request ) {
		return false;
	}
	public function update_item_permissions_check( $request ) {
		return false;
	}
	public function delete_item_permissions_check( $request ) {
		return false;
	}

}