<?php
abstract class TD_DB_Model extends TD_API_Model {
	/**
	 * The db prefix/namespace (in addition to wp_ wpdb prefix)
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $db_namespace = 'td';

	/**
	 * The name of this item
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $slug = false;

	/**
	 * The plural name of this item
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $pluralized_slug = false;

	/**
	 * The name of our database table
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $table_name;

	/**
	 * The version of our database table
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $version;

	/**
	 * The name of the primary field
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $primary_key = 'id';

	/**
	 * The name of the field used as a post_id foreign key
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $post_id_field = false;

	/**
	 * Default caching method
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $cache_method  = false;

	/**
	 * Default caching timeout (ignored with postmeta)
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $cache_timeout  = 0;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   0.0.2
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );

		if ( empty( $this->slug ) ) {
			die( 'define $this->slug in ' . get_class( $this ) );
		}
		if ( empty( $this->primary_key ) ) {
			die( 'define $this->primary_key in ' . get_class( $this ) );
		}
		if ( empty( $this->get_schema() ) ) {
			die( 'define $this->get_schema() in ' . get_class( $this ) );
		}

		$this->plugin = $plugin;
		$this->db_hooks();
	}

	public function db_hooks() {
		$this->maybe_create_table();
		add_filter( 'query_'.$this->slug.'_db_where_conditions', array( $this, 'filter_where_conditions' ), 10, 2 );
	}

	public function filter_where_conditions( $where, $args ) {
		return $where;
	}

	public function belongs_to() {
		return array();
	}

	public function has_many() {
		return array();
	}

	public function get_schema() {
		$schema = $this->schema;

		if ( empty( $schema[$this->primary_key] ) ) {
			$default_schema = array(
				$this->primary_key => array(
					'field' => $this->primary_key,
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
			);

			$schema = array_merge( $default_schema, $schema );
		}

		return $schema;
	}

	public function get_table_name() {
		if ( !empty( $this->table_name ) ) {
			return $this->table_name;
		}
		global $wpdb;
		$this->table_name = $wpdb->prefix;
		if ( !empty( $this->db_namespace ) ) {
			$this->table_name .= $this->db_namespace . '_';
		}

		$this->table_name .= $this->get_pluralized_slug();

		return $this->table_name;
	}

	public function get_pluralized_slug() {
		if ( !empty( $this->pluralized_slug ) ) {
			return $this->pluralized_slug;
		}

		$this->pluralized_slug = $this->slug . 's';

		return $this->pluralized_slug;
	}

	/**
	 * Get fields and formats
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function get_fields() {
		$schema = $this->get_schema();
		return array_combine(
			wp_list_pluck( $this->get_schema(), 'field' ),
			wp_list_pluck( $this->get_schema(), 'format' )
		);
	}

	/**
	 * Get default field values
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function get_field_defaults() {
		$schema = $this->get_schema();
		$defaults = array_combine(
			wp_list_pluck( $this->get_schema(), 'field' ),
			wp_list_pluck( $this->get_schema(), 'default_value' )
		);

		foreach ($defaults as $field => $value) {
			if ( $value === false ) {
				$defaults[$field] = '';
			} elseif ( $value === NULL ) {
				$defaults[$field] = 'NULL';
			}
		}

		return $defaults;
	}
	/**
	 * Retrieve a row by the primary key
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  object
	 */
	public function db_get( $row_id, $recursive=0 ) {
		global $wpdb;
		$row = (array)$wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->get_table_name()} WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
		$row = $this->prepare_item_for_response( $row, $recursive );
		return $row;
	}

	/**
	 * Retrieve a row by a specific field / value
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  object
	 */
	public function db_get_by( $field, $row_id, $recursive=0 ) {
		global $wpdb;
		$field = esc_sql( $field );
		$row = (array)$wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->get_table_name()} WHERE $field = %s LIMIT 1;", $row_id ) );
		$row = $this->prepare_item_for_response( $row, $recursive );
		return $row;
	}

	/**
	 * Retrieve a specific field's value by the primary key
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  string
	 */
	public function db_get_field( $field, $row_id ) {
		global $wpdb;
		$field = esc_sql( $field );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $field FROM {$this->get_table_name()} WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
	}

	/**
	 * Retrieve a specific field's value by the the specified field / value
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  string
	 */
	public function db_get_field_by( $field, $field_where, $field_value ) {
		global $wpdb;
		$field_where = esc_sql( $field_where );
		$field       = esc_sql( $field );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $field FROM {$this->get_table_name()} WHERE $field_where = %s LIMIT 1;", $field_value ) );
	}

	/**
	 * Insert a new row
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  int
	 */
	public function db_insert( $data, $type = '' ) {
		$data = $this->prepare_item_for_database( $data );

		global $wpdb;
		$schema = $this->get_schema();
		if ( empty( $type ) ) {
			$type = $this->slug;
		}

		// Set default values
		$data = array_merge( $this->get_field_defaults(), $data );
		unset( $data[$this->primary_key] );

		if ( !empty( $schema['author_id'] ) ) {
			$author_id = get_current_user_id();
			if( empty( $data['author_id'] ) || !current_user_can( 'manage_options' ) ) {
				$data['author_id'] = $author_id;
			}
		}

		if ( !empty( $schema['date_created'] ) && empty( $data['date_created'] ) ) {
			$data['date_created'] = gmdate('Y-m-d H:i:s' );
		}
		if ( !empty( $schema['date_modified'] ) && empty( $data['date_modified'] ) ) {
			$data['date_modified'] = gmdate('Y-m-d H:i:s' );
		}

		// do_action( $this->db_namespace.'_pre_insert_' . $type, $data );

		// Initialise field format array
		$field_formats = $this->get_fields();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list fields
		$data = array_intersect_key( $data, $field_formats );

		// Reorder $field_formats to match the order of fields given in $data
		$data_keys = array_keys( $data );
		$field_formats = array_merge( array_flip( $data_keys ), $field_formats );

		$wpdb->insert( $this->get_table_name(), $data, $field_formats );

		// do_action( $this->db_namespace.'_post_insert_' . $type, $wpdb->insert_id, $data );

		$data[$this->primary_key] = $wpdb->insert_id;

		return $data[$this->primary_key];
	}

	/**
	 * Insert a new row
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  int
	 */
	public function db_raw_insert( $data, $type = '' ) {
		$raw_data = $data;
		$data = $this->prepare_item_for_database( $data );
		if ( ! empty( $raw_data[$this->primary_key] ) ) {
			$data[$this->primary_key] = $raw_data[$this->primary_key];
		}

		global $wpdb;
		$schema = $this->get_schema();
		if ( empty( $type ) ) {
			$type = $this->slug;
		}

		// Set default values
		$data = array_merge( $this->get_field_defaults(), $data );

		if ( !empty( $schema['author_id'] ) ) {
			$author_id = get_current_user_id();
			if( empty( $data['author_id'] ) || !current_user_can( 'manage_options' ) ) {
				$data['author_id'] = $author_id;
			}
		}

		if ( !empty( $schema['date_created'] ) && empty( $data['date_created'] ) ) {
			$data['date_created'] = gmdate('Y-m-d H:i:s' );
		}
		if ( !empty( $schema['date_modified'] ) && empty( $data['date_modified'] ) ) {
			$data['date_modified'] = gmdate('Y-m-d H:i:s' );
		}

		// do_action( $this->db_namespace.'_pre_insert_' . $type, $data );

		// Initialise field format array
		$field_formats = $this->get_fields();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list fields
		$data = array_intersect_key( $data, $field_formats );

		// Reorder $field_formats to match the order of fields given in $data
		$data_keys = array_keys( $data );
		$field_formats = array_merge( array_flip( $data_keys ), $field_formats );

		$wpdb->insert( $this->get_table_name(), $data, $field_formats );

		// do_action( $this->db_namespace.'_post_insert_' . $type, $wpdb->insert_id, $data );

		$data[$this->primary_key] = $wpdb->insert_id;

		return $data[$this->primary_key];
	}

	/**
	 * Bulk Insert new rows
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  int
	 */
	public function db_bulk_insert( $array_of_data, $type = '' ) {
		if ( empty( $array_of_data ) ) {
			return;
		}
		$value_rows = array();
		global $wpdb;
		foreach ($array_of_data as $data) {
			$data = $this->prepare_item_for_database( $data );
			$schema = $this->get_schema();
			if ( empty( $type ) ) {
				$type = $this->slug;
			}

			// Set default values
			$data = array_merge( $this->get_field_defaults(), $data );
			unset( $data[$this->primary_key] );

			if ( !empty( $schema['author_id'] ) ) {
				$author_id = get_current_user_id();
				if( empty( $data['author_id'] ) || !current_user_can( 'manage_options' ) ) {
					$data['author_id'] = $author_id;
				}
			}

			if ( !empty( $schema['date_created'] ) && empty( $data['date_created'] ) ) {
				$data['date_created'] = gmdate('Y-m-d H:i:s' );
			}
			if ( !empty( $schema['date_modified'] ) && empty( $data['date_modified'] ) ) {
				$data['date_modified'] = gmdate('Y-m-d H:i:s' );
			}

			// do_action( $this->db_namespace.'_pre_insert_' . $type, $data );

			// Initialise field format array
			$field_formats = $this->get_fields();

			// Force fields to lower case
			$data = array_change_key_case( $data );

			// White list fields
			$data = array_intersect_key( $data, $field_formats );

			// Reorder $field_formats to match the order of fields given in $data
			$data_keys = array_keys( $data );
			$field_formats = array_merge( array_flip( $data_keys ), $field_formats );
			$data_formats = array_intersect_key( $field_formats, $data );
			$value_rows[] = $wpdb->prepare( '('.implode( ',', $data_formats ).')', $data );
		}

		$table = $this->get_table_name();
		$fields  = '`' . implode( '`, `', $data_keys ) . '`';
		$formats = implode( ', ', $field_formats );
		$values_csv = implode( ",\n", $value_rows );
		$sql = "INSERT INTO `$table` ($fields) VALUES $values_csv";
		$wpdb->get_results( $sql );
		// do_action( $this->db_namespace.'_post_insert_' . $type, $wpdb->insert_id, $data );

		return $wpdb->insert_id;
	}

	public function get_unique_slug( $title, $row_id = false ) {
		if ( empty( $this->schema['slug'] ) ) {
			return $title;
		}

		$slug = trim( sanitize_title( $title ) );

		$is_unique = false;
		$original_slug = $slug;
		$int_to_try_appending = 2;
		$already_appended = array();
		while ( $is_unique === false ) {
			$query_by_slug = $this->db_query( array( 
				'slug' => $slug,
			) );

			if ( empty( $query_by_slug ) ) {
				$is_unique = true;
			} elseif ( count( $query_by_slug ) === 1 && $query_by_slug['0']['id'] == $row_id ) {
				$is_unique = true;
			}

			if ( $is_unique ) {
				continue;
			}

			$slug = $original_slug.'-'.$int_to_try_appending;
			$int_to_try_appending++;
			if ( $int_to_try_appending > 20) {
				$int_to_try_appending = rand( 21, 100 );
			}
		}

		return $slug;
	}

	public function prepare_item_for_database( $params ) {
		$schema = $this->get_schema();

		$field_defaults = $this->get_field_defaults();
		$params = shortcode_atts( $field_defaults, $params );

		foreach ($schema as $schema_key => $schema_field) {
			if ( isset( $params[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json' ) {
				$params[$schema_key] = json_encode( $params[$schema_key] );
			} else if ( isset( $params[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json_serialize' ) {
				if ( is_array( $params[$schema_key] ) ) {
					$params[$schema_key] = json_encode( $params[$schema_key] );
				}
			}
			if ( $schema_key === 'slug' ) {
				$id = false;
				if ( !empty( $params['id'] ) ) {
					$id = $params['id'];
				}

				if ( !empty( $params[$schema_key] ) ) {
					$slug = trim( $params[$schema_key] );
					$params[$schema_key] = $slug;
				} else {
					if ( !empty( $params['title'] ) ) {
						$slug = trim( $params['title'] );
					} else {
						$slug = 'Untitled';
					}

					if ( !empty( $id ) ) {
						$me = $this->db_get( $id );
						if ( !empty( $me['slug'] ) ) {
							$slug = $me['slug'];
						}
					}
					
					$params[$schema_key] = $this->get_unique_slug( $slug, $id );
				}

			}
		}
		
		if (isset( $params[$this->primary_key] ) ) {
			unset( $params[$this->primary_key] );
		}

		if (isset( $params['1'] ) ) {
			unset( $params['1'] );
		}

		return $params;
	}

	public function prepare_item_for_response( $item, $recursive=0 ) {
		if ( $recursive > 0 ) {
			$item = $this->prepare_belongs_to_relationship( $item, $recursive );
			$item = $this->prepare_has_many_relationship( $item, $recursive );
		}

		foreach ($this->get_schema() as $schema_key => $schema_field) {
			if ( !empty( $item[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json' ) {
				if ( is_string( $item[$schema_key] ) ) {
					$item[$schema_key] = json_decode( $item[$schema_key], true );
				}
			} else if ( !empty( $item[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json_serialize' ) {
				if ( is_string( $item[$schema_key] ) ) {
					if ( 0 === strpos( $item[$schema_key], '{' ) || 0 === strpos( $item[$schema_key], '[' ) ) {
						// this is an array value
						$item[$schema_key] = json_decode( $item[$schema_key], true );
					}
				}
			}
		}

		// if ( $recursive > 0 ) {
		// 	$item = $this->add_computed_values_to_response( $item, $item[$this->primary_key], $recursive - 1 );
		// }

		if ( ! empty( $item[$this->primary_key] ) ) {		
			$item = apply_filters( $this->namespace_wp_hook( 'prepare_item_for_response' ), $item, $item[$this->primary_key], $recursive );
		}

		return $item;
	}

	public function prepare_collection_for_response( $items, $recursive=0 ) {
		$prepared_items = array();
		foreach ($items as $key => $item) {
			$prepared_items[$key] = $this->prepare_item_for_response( $item );
		}

		return $prepared_items;
	}

	public function cache_set( $data, $field ) {
		if ( empty( $field['cache_key'] ) ) {
			return false;
		}
		if ( empty( $field['cache_method'] ) ) {
			$field['cache_method'] = $this->cache_method;
		}

		if ( empty( $field['cache_method'] ) ) {
			return false;
		}

		$method = 'cache_set_'.$field['cache_method'];
		if ( !method_exists( $this, $method ) ) {
			return false;
		}

		return $this->$method( $field, $data );
	}

	public function cache_get( $field, $uid ) {
		if ( empty( $field['cache_key'] ) ) {
			return false;
		}
		if ( empty( $field['cache_method'] ) ) {
			$field['cache_method'] = $this->cache_method;
		}

		if ( empty( $field['cache_method'] ) ) {
			return false;
		}

		$method = 'cache_get_'.$field['cache_method'];
		if ( !method_exists( $this, $method ) ) {
			return false;
		}

		return $this->$method( $field, $data );
	}


	public function cache_set_postmeta( $field, $data ) {
		if ( empty( $data[$this->post_id_field] ) ) {
			return false; // we can't cache to postmeta without post_id
		}

		update_post_meta(
			$data[$this->post_id_field],
			$this->get_unique_cache_key( $field, $data ),
			$data[$field['field']]
		);

		return true;
	}

	public function cache_get_postmeta( $field, $uid ) {
		return get_post_meta(
			$uid,
			$this->get_unique_cache_key( $field, $data ),
			true
		);
	}

	public function get_unique_cache_key( $field, $data ) {
		if ( $field['cache_method'] === 'postmeta' ) {
			return $field['cache_key'];
		}

		$unique_cache_key = 'cache_'
			.$this->get_table_name()
			.'_'
			.$this->primary_key
			.'_'
			.$data[$this->primary_key]
			.'_'
			.$field['cache_key'];

		return $unique_cache_key;
	}
	/**
	 * Update a row
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  bool
	 */
	public function db_update( $row_id, $data = array() ) {
		$prepared_data = $this->prepare_item_for_database( $data ); // works for creating, not updating yet
		$data = shortcode_atts( $data, $prepared_data );
		if ( !empty( $data[$this->primary_key] ) ) {
			unset( $data[$this->primary_key] );
		}

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if( empty( $row_id ) ) {
			return false;
		}

		if( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise field format array
		$field_formats = $this->get_fields();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list fields
		$data = array_intersect_key( $data, $field_formats );

		if ( !empty( $this->schema['date_modified'] ) && empty( $data['date_modified'] ) ) {
			$data['date_modified'] = gmdate('Y-m-d H:i:s' );
		}

		// Reorder $field_formats to match the order of fields given in $data
		$data_keys = array_keys( $data );
		$field_formats = array_merge( array_flip( $data_keys ), $field_formats );
		$field_formats = shortcode_atts( $data, $field_formats );

		if ( false === $wpdb->update( $this->get_table_name(), $data, array( $where => $row_id ), $field_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  bool
	 */
	public function db_delete( $row_id = 0, $force_delete=false ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );
		if( empty( $row_id ) ) {
			return false;
		}
		
		$should_delete = false;

		$schema = $this->get_schema();
		if ( !empty( $force_delete ) || empty( $schema['status']['supports']['trash'] ) ) {
			$should_delete = true;
		}

		// TODO: Implement trash support (status ->delete if trash, else status ->trash)

		if ( $should_delete ) {
			if ( !empty( $schema['status']['supports']['soft_delete'] ) ) {				
				$data = array( $schema['status']['field'] => 'delete', );
				if ( false === $wpdb->update( $this->get_table_name(), $data, array( $this->primary_key => $row_id ) ) ) {
					return false;
				}
			} else {
				if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->get_table_name()} WHERE $this->primary_key = %d", $row_id ) ) ) {
					return false;
				}
			}

			return true;
		}


		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  bool
	 */
	public function db_bulk_delete( $args=array() ) {
		global $wpdb;

		if( empty( $args ) ) {
			return false;
		}

		$query = "DELETE FROM {$this->get_table_name()} WHERE 1=1";
		$query .= $this->filter_where_conditions( $this->db_where_conditions( $args ), $args );
		// foreach ($args as $key => $value) {
		// 	$query = $wpdb->prepare( $query .= " AND ".$key." = %s", $value );
		// }

		$result = $wpdb->query( $query );
		if ( false === $result ) {
			return false;
		}

		return $result;
	}

	/**
	 * Truncate a table and resets the auto increment values.
	 *
	 * @access 	public
	 * @since 	0.0.3
	 * @return 	void
	 */
	public function truncate() {
		global $wpdb;
		$query = "TRUNCATE TABLE {$this->get_table_name()}";
		$result = $wpdb->query( $query );

		return $result;
	}

	/**
	 * Truncate a table and resets the auto increment values.
	 *
	 * @access 	public
	 * @since 	0.0.3
	 * @return 	void
	 */
	public function drop() {
		global $wpdb;
		$query = "DROP TABLE IF EXISTS {$this->get_table_name()}";
		$result = $wpdb->query( $query );

		return $result;
	}

	/**
	 * Check if the given table exists
	 *
	 * @since  2.4
	 * @param  string $table The table name
	 * @return bool          If the table name exists
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	public function maybe_create_table() {
		if ( empty( $this->get_table_name() ) ) {
			return false;
		}
		if ( empty( $this->get_version() ) ) {
			return false;
		}

		$db_version = get_option( $this->get_table_name() . '_db_version', '0.0.0' );
		if ( !empty( $this->get_dev_mode() ) || version_compare( $db_version, $this->get_version(), '<' ) ) {
			$this->create_table();
		}
		
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function create_table() {
		$schema = $this->get_schema();
		if ( empty( $schema ) ) {
			return;
		}

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->get_table_name() . " (\n";
		foreach ($schema as $key => $field) {
			$new_line = $field['field'] . ' ';
			$new_line .= $field['mysql_type'];
			if ( !empty( $field['mysql_length'] ) ) {
				$new_line .= '('.(int)$field['mysql_length'].')';
			}
			$new_line .= ' ';
			if ( !empty( $field['mysql_unsigned'] ) ) {
				$new_line .= 'UNSIGNED ';
			}
			if ( empty( $field['mysql_allow_null'] ) ) {
				$new_line .= 'NOT NULL ';
			}
			if ( $field['default_value'] !== false ) {
				if ($field['default_value'] === NULL ) {
					$new_line .= 'DEFAULT NULL ';
				} else {
					$new_line .= "DEFAULT '".$field['default_value']."'";
				}
			}
			if ( !empty( $field['mysql_extra'] ) ) {
				$new_line .= $field['mysql_extra'];
			}
			$sql .= trim($new_line).", \n";
		}
		$sql .= "PRIMARY KEY  (`".$this->primary_key."`)";
		foreach ($this->indexes as $key => $fields) {
			$sql .= ",\n KEY `".$key."` (".implode( ',',$fields).")";
		}

		$sql .= " ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
		$sql .= " COMMENT='Created with schema v".$this->get_version()." (".current_time( 'mysql', true ).")'";
		// $sql .= " ROW_FORMAT=DYNAMIC"; // not necessary if we have smaller indexes
		$sql .= ";";
		ob_start();
		$dbdelta_response = @dbDelta( $sql );
		$errors = ob_get_clean();
		$last_query = $wpdb->last_query;
		$last_error = $wpdb->last_error;

		$confirm_query = 'DESCRIBE ' . $this->get_table_name();
		$confirm_results = $wpdb->get_results( $confirm_query );
		if ( empty( $wpdb->last_error ) ) {
			update_option( $this->get_table_name() . '_db_version', $this->get_version() );
			return true;
		}

		error_log( 'Failed to create table `'.$this->get_table_name().'`' );
		error_log( 'last_query: ' . $last_query );
		error_log( 'last_error: ' . $last_error );
		error_log( 'dbdelta error: ' . print_r( $dbdelta_response, true ) );
	}

	public function db_where_conditions( $args ) {
		global $wpdb;
		$where = '';
		$schema = $this->get_schema();

		if ( ! empty( $args['append_where_sql'] ) ) {
			if( ! is_array( $args['append_where_sql'] ) ) {
				$args['append_where_sql'] = array( $args['append_where_sql'] );
			}

			foreach ($args['append_where_sql'] as $where_sql) {
				$where .= $where_sql;
			}
		}

		if( ! empty( $args['id'] ) ) {

			if( is_array( $args['id'] ) ) {
				$ids = implode( ',', array_map('intval', $args['id'] ) );
			} else {
				$ids = intval( $args['id'] );
			}

			$where .= " AND `".$this->primary_key."` IN( {$ids} ) ";

		}

		if ( !empty( $schema['author_id'] ) ) {		
			// rows for specific user actions	
			if( ! empty( $args['author_id'] ) ) {

				if( is_array( $args['author_id'] ) ) {
					$author_ids = implode( ',', array_map('intval', $args['author_id'] ) );
				} else {
					$author_ids = intval( $args['author_id'] );
				}

				$where .= " AND `author_id` IN( {$author_ids} ) ";

			}
		}

		// specific rows by name
		if ( !empty( $schema['slug'] ) ) {		
			if( ! empty( $args['slug'] ) ) {
				$where .= $wpdb->prepare( " AND `slug` = '" . '%s' . "' ", $args['slug'] );
			}
		}

		// specific rows by name
		if ( !empty( $schema['status'] ) ) {		
			if( ! empty( $args['status'] ) ) {
				if ( is_array( $args['status'] ) ) {
					$where .= ' AND (';
						foreach ($args['status'] as $key => $status) {
							$where .= $wpdb->prepare( "`status` = '" . '%s' . "' ", $status );
							if ( $key + 1 < count( $args['status'] ) ) {
								$where .= 'OR ';
							}
						}
					$where .= ') ';
				} else {
					$where .= $wpdb->prepare( " AND `status` = '" . '%s' . "' ", $args['status'] );
				}
			}
		}


		// specific rows by name
		if ( !empty( $schema['type'] ) ) {		
			if( ! empty( $args['type'] ) ) {
				$where .= $wpdb->prepare( " AND `type` = '" . '%s' . "' ", $args['type'] );
			}
		}


		// specific rows by name
		if ( !empty( $schema['name'] ) ) {		
			if( ! empty( $args['name'] ) ) {
				$where .= $wpdb->prepare( " AND `name` = '" . '%s' . "' ", $args['name'] );
			}
		}

		if ( !empty( $schema['date_created'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_created'] ) ) {

				if( !is_array( $args['date_created'] ) ) {

					$year  = gmdate( 'Y', strtotime( $args['date_created'] ) );
					$month = gmdate( 'm', strtotime( $args['date_created'] ) );
					$day   = gmdate( 'd', strtotime( $args['date_created'] ) );

					$where .= " AND $year = YEAR ( date_created ) AND $month = MONTH ( date_created ) AND $day = DAY ( date_created )";
				}

			} else {

				if( ! empty( $args['date_created_min'] ) ) {

					$start = gmdate( 'Y-m-d H:i:s', strtotime( $args['date_created_min'] ) );

					$where .= " AND `date_created` >= '{$args["date_created_min"]}'";

				}

				if( ! empty( $args['date_created_max'] ) ) {

					$end = gmdate( 'Y-m-d H:i:s', strtotime( $args['date_created_max'] ) );

					$where .= " AND `date_created` <= '{$args["date_created_max"]}'";

				}

			}
		}

		return $where;
	}

	/**
	 * Retrieve rows from the database
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function db_query( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'       => 100000,
			'offset'       => 0,
			'orderby'      => $this->primary_key,
			'order'        => 'DESC',
			'recursive'    => 0,
			'fetch'        => array(),
			'fields'       => array(),
		);

		$schema = $this->get_schema();
		if ( ! empty( $schema['display_order'] ) ) {
			$defaults['orderby'] = $schema['display_order']['field'];
			$defaults['order'] = 'ASC';
		}

		$args  = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = ' WHERE 1=1';

		$where .= $this->db_where_conditions( $args );
		$where = apply_filters( 'query_'.$this->slug.'_db_where_conditions', $where, $args );

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_fields() ) ? $this->primary_key : $args['orderby'];

		// $cache_key = 'spdb_'.md5( $this->db_namespace.$this->get_table_name().'_rows_' . serialize( $args ) );

		// $rows = wp_cache_get( $cache_key, 'rows' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );
		$table_name      = $this->get_table_name();
		$fields          = empty( $args['fields'] ) ? '*' : '`' . implode( '`, `', $args['fields'] ) . '`';

		// if( $rows === false ) {
			$sql = $wpdb->prepare( "SELECT $fields FROM  $table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$rows = $wpdb->get_results( $sql );
			$rows = array_map( function($row) { return (array)$row; }, $rows );
		// }

		if ( !empty( $args['calc_date_created_time_ago'] ) ) {
			foreach ($rows as $key => $row) {
				$rows[$key]['date_created_time_ago'] = human_time_diff( strtotime( $row['date_created'] ) );
			}
		}

		if ( empty( $args['fields'] ) ) {
			foreach ($rows as $key => $value) {
				$rows[$key] = $this->prepare_item_for_response( $value, $args['recursive'] );
				if ( ! empty( $args['fetch'] ) ) {
					$rows[$key] = $this->add_computed_values_to_response( $rows[$key], $rows[$key][$this->primary_key], (int)$args['recursive'] - 1, $args['fetch'] );
				}
			}
		}
		
		// wp_cache_set( $cache_key, $rows, 'rows', 60 );
		return $rows;

	}

	public function bulk_meta_query( $args=array() ) {
		global $wpdb;

		if ( empty( $args['field'] ) ) {
			return false;
		}

		$schema = $this->get_schema();
	
		$defaults = array(
			'field' => '',
			'post_type' => '',
			$this->post_id_field => '',
			$this->post_id_field.'s' => '',
			'author_id' => '',
		);
		$args = shortcode_atts( $defaults, $args );

		if ( empty( $schema[$args['field']]['cache_key'] ) ) {
			$meta_key = $args['field'];
		} else {
			$meta_key = $schema[$args['field']]['cache_key'];
		}

		$where = 'WHERE meta_key="'.$meta_key.'"';
		if ( !empty( $args['post_type'] ) ) {
			$where .= ' AND post_type="'.esc_attr( $args['post_type'] ).'"';
		}
		$where .= $this->db_where_conditions( $args );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id as ".$this->post_id_field.",meta_value as ".$args['field'].",post_type,post_title,post_name FROM $wpdb->postmeta INNER JOIN $wpdb->posts on $wpdb->posts.ID=$wpdb->postmeta.post_id $where", null ) );
		if ( empty( $rows ) ) {
			return false;
		}

		$data = array();
		foreach ($rows as $key => $row) {
			$data[$row->{$this->post_id_field}] = (array)$row;
		}
		return $data;
	}

	/**
	 * Count the total number of rows in the database
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function count( $args = array() ) {

		global $wpdb;
		
		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'orderby'      => $this->primary_key,
			'order'        => 'DESC'
		);

		$args  = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = ' WHERE 1=1 ';

		$where .= $this->db_where_conditions( $args );

		$where = apply_filters( 'query_'.$this->slug.'_db_where_conditions', $where, $args );
		$where = apply_filters( 'count_query_'.$this->slug.'_db_where_conditions', $where, $args );

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_fields() ) ? $this->primary_key : $args['orderby'];

		// $cache_key = 'spdb_'.md5( $this->db_namespace.'_rows_count' . serialize( $args ) );

		// $count = wp_cache_get( $cache_key, 'rows' );

		// if( $count === false ) {
			$count = $wpdb->get_var( "SELECT COUNT($this->primary_key) FROM " . $this->get_table_name() . "{$where};" );
			// wp_cache_set( $cache_key, $count, 'rows', 60 );
		// }

		return absint( $count );

	}

	/**
	 * Check if the Customers table was ever installed
	 *
	 * @since  2.4
	 * @return bool Returns if the rows table was installed and upgrade routine run
	 */
	public function installed() {
		return $this->table_exists( $this->get_table_name() );
	}

}
