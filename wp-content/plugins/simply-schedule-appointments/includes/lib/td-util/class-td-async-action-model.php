<?php
abstract class TD_Async_Action_Model extends TD_DB_Model {
	protected $slug = 'async_action';
	protected $version = '1.8.5';

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
		add_filter( 'query_'.$this->slug.'_db_where_conditions', array( $this, 'date_filter_where_conditions' ), 10, 2 );
	}

	public function date_filter_where_conditions( $where, $args ) {
		if ( !empty( $args['action'] ) ) {
			$where .= ' AND action="'.sanitize_text_field( $args['action'] ).'"';
		}

		if ( !empty( $this->schema['date_queued'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_queued'] ) ) {

				if( !is_array( $args['date_queued'] ) ) {
					if ( $args['date_queued'] == '0000-00-00 00:00:00' ) {
						$where .= " AND (`date_queued` = \"".$args['date_queued']."\" OR `date_queued` IS NULL ) ";
					} else {
						$where .= " AND `date_queued` = \"".$args['date_queued']."\" ";
					}
				}

			} else {

				if( ! empty( $args['date_queued_min'] ) ) {
					$where .= " AND `date_queued` >= '{$args["date_queued_min"]}'";
				}

				if( ! empty( $args['date_queued_max'] ) ) {
					$where .= " AND `date_queued` <= '{$args["date_queued_max"]}'";
				}

			}
		}

		if ( !empty( $this->schema['date_processed'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_processed'] ) ) {

				if( !is_array( $args['date_processed'] ) ) {
					if ( $args['date_processed'] == '0000-00-00 00:00:00' ) {
						$where .= " AND (`date_processed` = \"".$args['date_processed']."\" OR `date_processed` IS NULL ) ";
					} else {
						$where .= " AND `date_processed` = \"".$args['date_processed']."\" ";
					}
				}

				

			} else {

				if( ! empty( $args['date_processed_min'] ) ) {
					$where .= " AND `date_processed` >= '{$args["date_processed_min"]}'";
				}

				if( ! empty( $args['date_processed_max'] ) ) {
					$where .= " AND `date_processed` <= '{$args["date_processed_max"]}'";
				}

			}
		}

		if ( !empty( $this->schema['date_completed'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_completed'] ) ) {

				if( !is_array( $args['date_completed'] ) ) {
					if ( $args['date_completed'] == '0000-00-00 00:00:00' ) {
						$where .= " AND (`date_completed` = \"".$args['date_completed']."\" OR `date_completed` IS NULL ) ";
					} else {
						$where .= " AND `date_completed` = \"".$args['date_completed']."\" ";
					}
				}

			} else {

				if( ! empty( $args['date_completed_min'] ) ) {
					$where .= " AND `date_completed` >= '{$args["date_completed_min"]}'";
				}

				if( ! empty( $args['date_completed_max'] ) ) {
					$where .= " AND `date_completed` <= '{$args["date_completed_max"]}'";
				}

			}
		}

		return $where;
	}

	public function insert( $data, $type='' ) {
		if ( empty( $data['date_queued'] ) ) {
			$data['date_queued'] = gmdate( 'Y-m-d H:i:s' );
		}

		return parent::insert( $data, $type );
	}

	public function queue_action( $hook, $action = null, $priority = 10, $payload = array(), $object_type = null, $object_id = null, $action_group = null, $meta = array() ) {
		if ( empty( $action ) ) {
			$action = 'async_'.$hook;
		}

		$data = array(
			'hook' => $hook,
			'action' => $action,
			'priority' => $priority,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'action_group' => $action_group,
			'payload' => $payload,
		);

		$meta = shortcode_atts( array(
			'date_queued' => null,
		), $meta );

		foreach ($meta as $key => $value) {
			if ( empty( $value ) ) {
				continue;
			}

			$data[$key] = $value;
		}

		return $this->insert( $data );
	}

	public function complete_action( $action_id, $response = array() ) {
		$this->update( $action_id, array(
			'response' => $response,
			'date_completed' => gmdate( 'Y-m-d H:i:s' ),
		) );
	}

	public function process( $query = array() ) {
		$query = shortcode_atts( array(
			'date_processed' => '0000-00-00 00:00:00',
			'orderby' => 'priority',
			'order' => 'ASC',
			'date_queued_max' => gmdate( 'Y-m-d H:i:s' ),
		), $query );

		$actions_to_do = $this->query( $query );
		foreach ($actions_to_do as $key => $action_to_do) {
			if ( empty( $action_to_do['action'] ) ) {
				continue;
			}

			if ( empty( $action_to_do['date_queued'] ) || $action_to_do['date_queued'] > gmdate( 'Y-m-d H:i:s' ) ) {
				continue;
			}			

			$action_to_do = $this->get( $action_to_do['id'] );
			if ( !empty( $action_to_do['date_processed'] ) && $action_to_do['date_processed'] != '0000-00-00 00:00:00' ) {
				continue;
			}
			$action_to_do['date_processed'] = gmdate( 'Y-m-d H:i:s' );

			$this->update( $action_to_do['id'], array(
				'date_processed' => $action_to_do['date_processed']
			) );
			do_action( $action_to_do['action'], $action_to_do['payload'], $action_to_do );
		}

		if ( apply_filters( 'ssa/async/retry_incomplete_actions', false ) && empty( $action_to_do ) ) {
			// let's check for ones that started processing but never finished
			$ten_minutes_ago_ymd = gmdate( 'Y-m-d H:i:s', time()-600 );
			$query = shortcode_atts( array(
				'date_completed' => '0000-00-00 00:00:00',
				'date_processed_max' => $ten_minutes_ago_ymd,
				'date_processed_min' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS * 6 ),
				'orderby' => 'priority',
				'order' => 'ASC',
				'date_queued_max' => gmdate( 'Y-m-d H:i:s' ),
			), $query );

			$actions_to_do = $this->query( $query );
			foreach ($actions_to_do as $key => $action_to_do) {
				if ( empty( $action_to_do['action'] ) ) {
					continue;
				}

				if ( empty( $action_to_do['date_queued'] ) || $action_to_do['date_queued'] > gmdate( 'Y-m-d H:i:s' ) ) {
					continue;
				}			

				$action_to_do = $this->get( $action_to_do['id'] );
				if ( !empty( $action_to_do['date_processed'] ) && $action_to_do['date_processed'] > $ten_minutes_ago_ymd ) {
					// this already got picked up another time
					continue;
				}
				$action_to_do['date_processed'] = gmdate( 'Y-m-d H:i:s' );

				$this->update( $action_to_do['id'], array(
					'date_processed' => $action_to_do['date_processed'],
					'date_completed' => $action_to_do['date_processed'], // we don't want it to infinitely loop, just try one more time
				) );
				do_action( $action_to_do['action'], $action_to_do['payload'], $action_to_do );
			}
		}
	}

	protected $indexes = array(
		'author_id' => [ 'author_id' ],
		'hook' => [ 'hook' ],
		'action' => [ 'action' ],
		'date_queued' => [ 'date_queued' ],
		'date_processed' => [ 'date_processed' ],
		'date_completed' => [ 'date_completed' ],
		'process_batch_token' => [ 'process_batch_token' ],
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

		'hook' => array(
			'field' => 'hook',
			'label' => 'Hook',
			'default_value' => '',
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => 150,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'action' => array(
			'field' => 'action',
			'label' => 'Action',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '150',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'priority' => array(
			'field' => 'priority',
			'label' => 'Priority',
			'default_value' => 10,
			'format' => '%d',
			'mysql_type' => 'INT',
			'mysql_length' => '5',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'payload' => array(
			'field' => 'payload',
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

		'object_type' => array(
			'field' => 'object_type',
			'label' => 'Object Type',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '150',
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

		'action_group' => array(
			'field' => 'action_group',
			'label' => 'Object Type',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '150',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),

		'response' => array(
			'field' => 'response',
			'label' => 'Response',
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
			'mysql_allow_null' => true,
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
			'mysql_allow_null' => true,
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
			'mysql_allow_null' => true,
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
			'mysql_allow_null' => true,
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
		'date_completed' => array(
			'field' => 'date_completed',
			'label' => 'Date Completed',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'DATETIME',
			'mysql_length' => '',
			'mysql_unsigned' => false,
			'mysql_allow_null' => true,
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