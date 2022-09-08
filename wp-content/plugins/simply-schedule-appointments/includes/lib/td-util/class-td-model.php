<?php
/* don't extend TD_Model
 * instead extend TD_CPT_Model or TD_DB_Model
 */
abstract class TD_Model {
	protected $plugin;
	protected $hook_namespace;
	protected $dev_mode;
	protected $version;
	protected $computed_schema;

	public function get_primary_key() {
		return $this->primary_key;
	}

	public function __construct( $plugin ) {		
		$this->plugin = $plugin;
	}

	public function get_version() {
		if ( empty( $this->version ) ) {
			$this->version = '0.0.0';
		}

		if ( !empty( $this->get_dev_mode() ) ) {
			return $this->version . '.' . time();	
		}

		return $this->version;
	}

	public function get_dev_mode() {
		if ( defined( 'TD_DEV_MODE' ) && TD_DEV_MODE ) {
			return true;
		}

		return $this->dev_mode;
	}

	public function namespace_wp_hook( $custom_hook_name ) {
		$tag = '';
		if ( !empty( $this->hook_namespace ) ) {
			$tag .= $this->hook_namespace . '/';
		}

		$tag .= $this->slug . '/';

		if ( !empty( $prefix ) ) {
			$tag .= $prefix . '_';
		}

		$tag .= $custom_hook_name;

		return $tag;
	}

	public function get_computed_schema() {
		if ( !empty( $this->computed_schema ) || false === $this->computed_schema ) {
			return $this->computed_schema;
		}
	}

	public function add_computed_values_to_response( $item, $item_id, $recursive, $fetch = array() ) {
		$computed_schema = $this->get_computed_schema();
		if ( empty( $computed_schema ) ) {
			return $item;
		}

		if ( is_array( $computed_schema ) ) {
			if ( empty( $computed_schema['fields'] ) ) {
				return $item;
			}

			foreach ($computed_schema['fields'] as $computed_field ) {
				if ( $recursive <= 0 && empty( $fetch[$computed_field['name']] ) ) {
					continue;
				}

				if ( !empty( $computed_field['get_input'] ) ) {
					$input = $computed_field['get_input'];
				} elseif ( !empty( $computed_field['get_input_path'] ) && isset( $item[$computed_field['get_input_path']] ) ) {
					$input = $item[$computed_field['get_input_path']];
				} else {
					$input = null;
				}

				$computed_value = call_user_func( $computed_field['get_function'], $input );
				$item[$computed_field['name']] = $computed_value;
			}
		}

		return $item;
	}

	public function get( $row_id, $recursive=0 ) {
		$tag = $this->namespace_wp_hook( 'before_get' );
		$row_id = apply_filters( $tag, $row_id, $recursive );

		$item = $this->db_get( $row_id, $recursive );

		$tag = $this->namespace_wp_hook( 'after_get' );
		$item = apply_filters( $tag, $item, $row_id, $recursive );

		return $item;
	}

	public function get_by( $field, $row_id, $recursive=0 ) {
		return $this->db_get_by( $field, $row_id, $recursive  );
	}

	public function get_field( $field, $row_id ) {
		return $this->db_get_field( $field, $row_id );
	}

	public function get_field_by( $field, $field_where, $field_value ) {
		return $this->db_get_field_by( $field, $field_where, $field_value );
	}

	public function query( $args=array() ) {
		return $this->db_query( $args );
	}

	public function insert( $data, $type = '' ) {
		$tag = $this->namespace_wp_hook( 'before_insert' );
		$data = apply_filters( $tag, $data, $type );

		if ( ! empty( $data['error'] ) ) {
			if ( ! current_user_can( 'ssa_manage_site_settings' ) && ! empty( $data['error']['admin_message'] ) ) {
				unset( $data['error']['admin_message'] );
			}

			return array(
				// 'error' => array(
				// 	'code' => '', 
				// 	'message' => '', 
				//  'admin_message' => ''
				// 	'data' => array(), 
				// ),
				'error' => $data['error'], 
			);
		}

		$insert_id = $this->db_insert( $data, $type = '' );

		$tag = $this->namespace_wp_hook( 'after_insert' );
		do_action( $tag, $insert_id, $data );

		return $insert_id;
	}

	public function raw_insert( $data, $type = '' ) {
		// $tag = $this->namespace_wp_hook( 'before_insert' );
		// $data = apply_filters( $tag, $data, $type );

		$insert_id = $this->db_raw_insert( $data, $type = '' );

		// $tag = $this->namespace_wp_hook( 'after_insert' );
		// do_action( $tag, $insert_id, $data );

		return $insert_id;
	}

	public function update( $row_id, $data = array() ) {
		$tag = $this->namespace_wp_hook( 'before_update' );
		$data_before = $this->get( $row_id );
		$data = apply_filters( $tag, $data, $data_before, $row_id, $data );

		$response = $this->db_update( $row_id, $data );

		$tag = $this->namespace_wp_hook( 'after_update' );
		do_action( $tag, $row_id, $data, $data_before, $response );

		return $response;
	}

	public function raw_update( $row_id, $data = array() ) {
		// $tag = $this->namespace_wp_hook( 'before_update' );
		// $data_before = $this->get( $row_id );
		// $data = apply_filters( $tag, $data, $data_before, $row_id, $data );

		$response = $this->db_update( $row_id, $data );

		// $tag = $this->namespace_wp_hook( 'after_update' );
		// do_action( $tag, $row_id, $data, $data_before, $response );

		return $response;
	}

	public function delete( $row_id = 0, $force_delete = false ) {
		$tag = $this->namespace_wp_hook( 'before_delete' );
		$row_id = apply_filters( $tag, $row_id, $force_delete );

		$response = $this->db_delete( $row_id, $force_delete );

		$tag = $this->namespace_wp_hook( 'after_delete' );
		do_action( $tag, $row_id, $force_delete, $response );

		return $response;
	}

	public function prepare_belongs_to_relationship( $item, $recursive=0 ) {
		if ( empty( $recursive ) ) {
			return $item;
		}

		if ( empty( $this->belongs_to() ) ) {
			return $item;
		}

		foreach ($this->belongs_to() as $relationship_key => $relationship ) {
			$item[$relationship_key] = array();
			switch ( $relationship['model'] ) {
				case 'WP_Post_Model':
					$item = $this->prepare_wp_post_model( $item, $relationship_key, $relationship, $recursive - 1 );
					break;

				case 'WP_User_Model':
					$item = $this->prepare_wp_user_model( $item, $relationship_key, $relationship, $recursive - 1 );
					break;
				
				default:
					if ( empty( $relationship['model'] ) ) {
						break;
					}

					if ( !is_object( $relationship['model'] ) ) {
						break;
					}

					if ( !method_exists( $relationship['model'], 'get' ) ) {
						break;
					}

					if ( !method_exists( $relationship['model'], 'prepare_item_for_response' ) ) {
						break;
					}

					$item[$relationship_key] = $relationship['model']->get( $item[$relationship['foreign_key']], $recursive - 1 );

					break;
			}
		}

		return $item;
	}

	public function prepare_has_many_relationship( $item, $recursive=0 ) {
		if ( empty( $recursive ) ) {
			return $item;
		}

		if ( empty( $this->has_many() ) ) {
			return $item;
		}

		foreach ($this->has_many() as $relationship_key => $relationship ) {
			$item[$relationship_key] = array();
			switch ( $relationship['model'] ) {
				case 'WP_Post_Model':
					$this->prepare_wp_post_model( $item, $relationship_key, $relationship, $recursive - 1 );
					break;

				case 'WP_User_Model':
					$this->prepare_wp_user_model( $item, $relationship_key, $relationship, $recursive - 1 );
					break;
				
				default:
					if ( empty( $relationship['model'] ) ) {
						break;
					}

					if ( !is_object( $relationship['model'] ) ) {
						break;
					}

					if ( !method_exists( $relationship['model'], 'get' ) ) {
						break;
					}

					if ( !method_exists( $relationship['model'], 'prepare_item_for_response' ) ) {
						break;
					}

					$item[$relationship_key] = $relationship['model']->query( array(
						$relationship['foreign_key'] => $item[$this->primary_key],
						'recursive' => $recursive - 1,
					) );

					break;
			}
		}

		return $item;
	}

	public function prepare_wp_post_model( $item, $relationship_key, $relationship, $recursive=0 ) {
		$post = get_post( $item[$relationship['foreign_key']] );
		$item[$relationship_key] = array(
			'id' => $post->ID,
			'post_type' => $post->post_type,
			'title' => $post->post_title,
			'slug' => $post->post_name,
			'content' => $post->post_content,
			'date_published' => $post->post_date_gmt,
			'date_modified' => $post->post_modified_gmt,
		);
		if ( !empty( $relationship['fields'] ) ) {
			foreach ($relationship['fields'] as $field_key => $field) {
				if ( is_int( $field_key ) ) {
					$field_key = $field;
				}

				$item[$relationship_key][$field_key] = $post->$field;
			}
		}

		return $item;
	}

	public function prepare_wp_user_model( $item, $relationship_key, $relationship, $recursive=0 ) {
		$user = new WP_User( $item[$relationship['foreign_key']] );

		if ( empty( $user->ID ) ) {
			$item[$relationship_key] = array();
			return $item;
		}

		$item[$relationship_key] = array(
			'id' => $user->ID,
			'login' => $user->data->user_login,
			'email' => $user->data->user_email,
			'date_created' => $user->data->user_registered,
			'display_name' => $user->data->display_name,
			'roles' => $user->roles,
		);
		if ( !empty( $relationship['fields'] ) ) {
			foreach ($relationship['fields'] as $field_key => $field) {
				if ( is_int( $field_key ) ) {
					$field_key = $field;
				}

				$item[$relationship_key][$field_key] = $user->$field;
			}
		}

		return $item;
	}


	public function bulk_delete( $args=array() ) {
		return $this->db_bulk_delete( $args );
	}
}