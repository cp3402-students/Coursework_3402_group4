<?php
abstract class TD_CPT_Model extends TD_API_Model {
	protected $args = array();
	protected $schema = array();


	/**
	 * The db prefix/namespace (in addition to wp_ wpdb prefix)
	 *
	 * @access  protected
	 * @since   0.0.2
	 */
	protected $db_namespace = '';

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
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_filter( 'db_query_'.$this->slug.'_args', array( $this, 'filter_query_args' ) );
	}

	function register_cpt() {
		$args = array_merge( array (
			'supports' => array( 'title', 'editor' ),
		    'menu_position' => 20,
			'capability_type' => 'post',
			'map_meta_cap' => true,
		), $this->args );

		$names = array (
			'single' => ucwords( str_replace( '_', ' ', $this->slug ) ),
			'plural' => ucwords( str_replace( '_', ' ', $this->get_pluralized_slug() ) ),
		);

		$extended_cpt = register_extended_post_type( $this->slug,
			$args,
			$names 
		);

		if ( !empty( $this->taxonomies ) ) {
			foreach ($this->taxonomies as $taxonomy_slug => $taxonomy ) {
				if ( is_string( $taxonomy ) ) {
					$extended_cpt->add_taxonomy( $taxonomy );
					continue;
				}

				if ( empty( $taxonomy['args'] ) ) {
					$args = array();
				}
				if ( empty( $taxonomy['names'] ) ) {
					$names = array();
				}
				if ( is_int( $taxonomy_slug ) ) {
					if ( empty( $taxonomy['slug'] ) ) {
						continue;
					}
					$taxonomy_slug = $taxonomy['slug'];
				}

				$extended_cpt->add_taxonomy( $taxonomy_slug, $args, $names );
			}
		}
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
					'keypath' => 'ID',
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

		if ( empty( $schema['title'] ) ) {
			$schema['title'] = array(
				'field' => 'title',
				'keypath' => 'post_title',
				'label' => 'Title',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'VARCHAR',
				'mysql_length' => 250,
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		if ( empty( $schema['status'] ) ) {
			$schema['status'] = array(
				'field' => 'status',
				'keypath' => 'post_status',
				'label' => 'Status',
				'default_value' => 'publish',
				'format' => '%s',
				'mysql_type' => 'VARCHAR',
				'mysql_length' => 250,
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		if ( empty( $schema['slug'] ) ) {
			$schema['slug'] = array(
				'field' => 'slug',
				'keypath' => 'post_name',
				'label' => 'Slug',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'VARCHAR',
				'mysql_length' => 250,
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		if ( empty( $schema['content'] ) ) {
			$schema['content'] = array(
				'field' => 'content',
				'keypath' => 'post_content',
				'label' => 'Content',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'VARCHAR',
				'mysql_length' => 250,
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		if ( empty( $schema['excerpt'] ) ) {
			$schema['excerpt'] = array(
				'field' => 'excerpt',
				'keypath' => 'post_excerpt',
				'label' => 'Excerpt',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'VARCHAR',
				'mysql_length' => 250,
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}


		if ( empty( $schema['author_id'] ) ) {
			$schema['author_id'] = array(
				'field' => 'author_id',
				'keypath' => 'post_author',
				'querypath' => 'author',
				'label' => 'Author',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'BIGINT',
				'mysql_length' => 20,
				'mysql_unsigned' => true,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		if ( empty( $schema['date_created'] ) ) {
			$schema['date_created'] = array(
				'field' => 'date_created',
				'keypath' => 'post_date_gmt',
				'label' => 'Date Created',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'DATETIME',
				'mysql_length' => '',
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}
		if ( empty( $schema['date_modified'] ) ) {
			$schema['date_modified'] = array(
				'field' => 'date_modified',
				'keypath' => 'post_modified_gmt',
				'label' => 'Date Modified',
				'default_value' => false,
				'format' => '%s',
				'mysql_type' => 'DATETIME',
				'mysql_length' => '',
				'mysql_unsigned' => false,
				'mysql_allow_null' => false,
				'mysql_extra' => '',
				'cache_key' => false,
			);
		}

		return $schema;
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
		$row = get_post( $row_id );
		if ( $row->post_type !== $this->slug ) {
			return array();
		}
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
		global $wpdb;
		$schema = $this->get_schema();
		if ( empty( $type ) ) {
			$type = $this->slug;
		}

		// Set default values
		// $data = wp_parse_args( $data, $this->get_field_defaults() );
		if ( empty( $data['author_id'] ) || !current_user_can( 'ssa_manage_others_appointments' ) ) {
			$data['author_id'] = get_current_user_id();
		}

		// if ( !empty( $schema['date_created'] ) && empty( $data['date_created'] ) ) {
		// 	$data['date_created'] = date('Y-m-d H:i:s' );
		// }
		// if ( !empty( $schema['date_modified'] ) && empty( $data['date_modified'] ) ) {
		// 	$data['date_modified'] = date('Y-m-d H:i:s' );
		// }

		do_action( $this->db_namespace.'_pre_insert_' . $type, $data );

		// Initialise field format array
		$field_formats = $this->get_fields();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list fields
		$data = array_intersect_key( $data, $field_formats );

		// Reorder $field_formats to match the order of fields given in $data
		$data_keys = array_keys( $data );
		$field_formats = array_merge( array_flip( $data_keys ), $field_formats );

		$prepared_data = $this->prepare_item_for_database( $data );
		if (isset( $prepared_data[$this->primary_key] ) ) {
			unset( $prepared_data[$this->primary_key] );
		}

		$post_id = wp_insert_post( $prepared_data );

		return $post_id;
	}

	public function prepare_item_for_database( $params ) {
		$schema = $this->get_schema();

		if (isset( $params[$this->primary_key] ) ) {
			unset( $params[$this->primary_key] );
		}

		$fields = array_values( wp_list_pluck( $schema, 'field' ) );
		$default_values = array_values( wp_list_pluck( $schema, 'default_value' ) );
		$default_params = array_combine( $fields, $default_values );
		// $params = shortcode_atts( $default_params, $params );

		foreach ($schema as $schema_key => $schema_field) {
			if ( !empty( $params[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json' ) {
				$params[$schema_key] = json_encode( $params[$schema_key] );
			}
		}

		$prepared_data = array(
			'post_type' => $this->slug,
		);
		foreach ($this->get_schema() as $field_key => $field) {
			if ( !isset( $params[$field_key] ) ) {
				if ( !empty( $field['default_value'] ) ) {
					if ( !empty( $field['keypath'] ) ) {
						$prepared_data[$field['keypath']] = $field['default_value'];
					} else {
						$prepared_data[$field['field']] = $field['default_value'];
					}
				}

				continue;
			}
			if ( empty( $field['keypath'] ) ) {
				$prepared_data[$field['field']] = $params[$field_key];
				continue;
			}

			$post_meta_key = str_replace( 'post_meta.', '', $field['keypath'] );
			if ( $field['keypath'] !== $post_meta_key  ) {
				$prepared_data['meta_input'][$post_meta_key] = $params[$field_key];
				continue;
			}

			$prepared_data[$field['keypath']] = $params[$field_key];
			continue;
		}
		
		return $prepared_data;
	}

	public function prepare_item_for_response( $post, $recursive=0 ) {
		$item = array(
		);

		foreach ($this->get_schema() as $field_key => $field) {
			if ( empty( $field['keypath'] ) ) {
				$item[$field_key] = $post->$field['field'];
				continue;
			}

			$post_meta_field = str_replace( 'post_meta.', '', $field['keypath'] );
			if ( $field['keypath'] !== $post_meta_field  ) {
				$item[$field_key] = $post->$post_meta_field;
				continue;
			}

			$item[$field_key] = $post->{$field['keypath']};
			continue;
		}

		if ( $recursive > 0 ) {
			$item = $this->prepare_belongs_to_relationship( $item, $recursive );
			$item = $this->prepare_has_many_relationship( $item, $recursive );
		}

		foreach ($this->get_schema() as $schema_key => $schema_field) {
			if ( !empty( $item[$schema_key] ) && !empty( $schema_field['encoder'] ) && $schema_field['encoder'] === 'json' ) {
				$item[$schema_key] = json_decode( $item[$schema_key], true );
			}
		}

		$item['permalink'] = get_permalink( $post->ID );

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
	public function db_update( $post_id, $data = array() ) {

		global $wpdb;

		// Row ID must be positive integer
		$post_id = absint( $post_id );

		if( empty( $post_id ) ) {
			return false;
		}

		// array add/remove functionality
		foreach ($this->get_schema() as $schema_key => $schema_field) {
			if ( empty( $schema_field['type'] ) || $schema_field['type'] !== 'array' ) {
				continue;
			}
			$post_meta_key = str_replace( 'post_meta.', '', $schema_field['keypath'] );
			if ( $post_meta_key !== $schema_field['keypath'] ) {

				$existing_data = get_post_meta( $post_id, $post_meta_key, true );
				if ( $schema_field['encoder'] === 'json' && !empty( $existing_data ) ) {
					$existing_data = json_decode( $existing_data, true );
				}
				if ( empty( $existing_data ) ) {
					$existing_data = array();
				}

				if ( !empty( $data[$schema_key] ) ) {
					if ( is_string( $data[$schema_key] ) ) {
						$data[$schema_key] = array( $data[$schema_key] );
					}
					$new_array_data = $data[$schema_key];
				} else {
					$new_array_data = $existing_data;
				}

				if ( empty( $data[$schema_key.'_to_add'] ) && empty( $data[$schema_key.'_to_remove'] ) ) {
					continue;
				}

				if ( !empty( $data[$schema_key.'_to_add'] ) ) {
					if ( is_string( $data[$schema_key.'_to_add'] ) ) {
						$data_to_add = array( $data[$schema_key.'_to_add'] );
					} else {
						$data_to_add = $data[$schema_key.'_to_add'];
					}
					unset( $data[$schema_key.'_to_add'] );
				}
				if ( !empty( $data[$schema_key.'_to_remove'] ) ) {
					if ( is_string( $data[$schema_key.'_to_remove'] ) ) {
						$data_to_remove = array( $data[$schema_key.'_to_remove'] );
					} else {
						$data_to_remove = $data[$schema_key.'_to_remove'];
					}
					unset( $data[$schema_key.'_to_remove'] );
				}

				if ( !empty( $data_to_add ) ) {	
					foreach ($data_to_add as $key => $value) {
						$value = esc_attr( $value );
						if ( in_array( $value, $new_array_data ) ) {
							continue;
						}

						$new_array_data[] = $value;
					}
				}

				if ( !empty( $data_to_remove ) ) {	
					foreach ($data_to_remove as $key => $value) {
						$value = esc_attr( $value );
						if ( ( $found_key = array_search($value, $new_array_data ) ) !== false ) {
						    unset($new_array_data[$found_key]);
						}
					}
				}

				$data[$schema_key] = array_values( $new_array_data );

				continue;
			}

		}

		// Initialise field format array
		$field_formats = $this->get_fields();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list fields
		$data = array_intersect_key( $data, $field_formats );


		// format into update_post array
		$data = $this->prepare_item_for_database( $data );
		$data['ID'] = $post_id;
		$updated_post_id = wp_update_post( $data );

		if ( !empty( $updated_post_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @since   0.0.2
	 * @return  bool
	 */
	public function db_delete( $post_id = 0, $force_delete = false ) {
		return wp_delete_post( $post_id, $force_delete );
	}

	public function db_where_conditions( $args ) {
		global $wpdb;
		$where = '';
		$schema = $this->get_schema();

		if( ! empty( $args['id'] ) ) {

			if( is_array( $args['id'] ) ) {
				$ids = implode( ',', array_map('intval', $args['id'] ) );
			} else {
				$ids = intval( $args['id'] );
			}

			$where .= " AND `".$this->primary_key."` IN( {$ids} ) ";

		}

		if ( !empty( $schema['user_id'] ) ) {		
			// rows for specific user actions	
			if( ! empty( $args['user_id'] ) ) {

				if( is_array( $args['user_id'] ) ) {
					$user_ids = implode( ',', array_map('intval', $args['user_id'] ) );
				} else {
					$user_ids = intval( $args['user_id'] );
				}

				$where .= " AND `user_id` IN( {$user_ids} ) ";

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

					$year  = date( 'Y', strtotime( $args['date_created'] ) );
					$month = date( 'm', strtotime( $args['date_created'] ) );
					$day   = date( 'd', strtotime( $args['date_created'] ) );

					$where .= " AND $year = YEAR ( date_created ) AND $month = MONTH ( date_created ) AND $day = DAY ( date_created )";
				}

			} else {

				if( ! empty( $args['date_created_min'] ) ) {

					$start = date( 'Y-m-d H:i:s', strtotime( $args['date_created_min'] ) );

					$where .= " AND `date_created` >= '{$args["date_created_min"]}'";

				}

				if( ! empty( $args['date_created_max'] ) ) {

					$end = date( 'Y-m-d H:i:s', strtotime( $args['date_created_max'] ) );

					$where .= " AND `date_created` <= '{$args["date_created_max"]}'";

				}

			}
		}

		return $where;
	}

	public function filter_query_args( $args ) {
		return $args;
	}

	/**
	 * Retrieve rows from the database
	 *
	 * @access  public
	 * @since   0.0.2
	*/
	public function db_query( $args = array() ) {

		$defaults = array(
			'post_type' => $this->slug,
			'number'       => 10,
			'offset'       => 0,
			'recursive'    => 0,
			'fetch' => array(),
		);

		$args  = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		foreach ($this->get_schema() as $field_name => $field) {
			if ( empty( $args[$field_name] ) ) {
				continue;
			}
			if ( empty( $field['keypath'] ) ) {
				continue;
			}

			/* meta_query args */
			$post_meta_field = str_replace( 'post_meta.', '', $field['keypath'] );
			if ( $post_meta_field !== $field['keypath'] ) {
				$args['meta_query'][] = array(
					'key' => $post_meta_field,
					'value' => $args[$field_name],
				);
				unset($args[$field_name]);
				continue;
			}
			
			/* post fields */
			if ( !empty( $field['querypath'] ) ) {
				$args[$field['querypath']] = $args[$field_name];
			} else {
				$args[$field['keypath']] = $args[$field_name];
			}
			unset($args[$field_name]);
			continue;
		}


		$args = apply_filters( 'db_query_'.$this->slug.'_args', $args );

		$cache_key = $this->db_namespace.md5( $this->db_namespace.'_rows_' . serialize( $args ) );

		// $rows = wp_cache_get( $cache_key, 'rows' );

		// if( $rows === false ) {
			if ( isset( $args['number'] ) ) {
				$args['posts_per_page'] = $args['number'];
				unset( $args['number'] );
			}
			
			$query = new WP_Query( $args );
			if ( empty( $query->posts ) ) {
				return array();
			}

			$rows = $query->posts;
		// }

		
		foreach ($rows as $key => $value) {
			$rows[$key] = $this->prepare_item_for_response( $value, $args['recursive'] );
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


		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_fields() ) ? $this->primary_key : $args['orderby'];

		$cache_key = 'spdb_'.md5( $this->db_namespace.'_rows_count' . serialize( $args ) );

		// $count = wp_cache_get( $cache_key, 'rows' );

		// if( $count === false ) {
			$count = $wpdb->get_var( "SELECT COUNT($this->primary_key) FROM " . $this->get_table_name() . "{$where};" );
			// wp_cache_set( $cache_key, $count, 'rows', 60 );
		// }

		return absint( $count );

	}

}