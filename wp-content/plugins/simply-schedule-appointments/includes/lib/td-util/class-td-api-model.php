<?php
/* 
 * don't extend TD_API_Model
 * instead extend TD_CPT_Model or TD_DB_Model
 */
abstract class TD_API_Model extends TD_Model {
	protected $plugin;

	protected $api_enabled = true;
	protected $api_namespace;
	protected $api_version = '1';
	protected $api_base;

	public function __construct( $plugin ) {
		parent::__construct( $plugin );

		$this->plugin = $plugin;
		$this->api_hooks();
	}

	public function api_hooks() {
		if ( empty( $this->api_enabled ) ) {
			return;
		}

		if ( empty( $this->api_namespace ) ) {
			die( 'define $this->api_namespace in ' . get_class( $this ) );
		}

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	public function rest_api_init() {
		$this->register_routes();
	}

	public function get_api_base() {
		if ( !empty( $this->api_base ) ) {
			return $this->api_base;
		}

		$this->api_base = $this->get_pluralized_slug();
		return $this->api_base;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = $this->api_namespace.'/v' . $this->api_version;
		$base = $this->get_api_base();

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(

				),
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => array(),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/bulk', array(
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => array(),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => array(),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
			),
		) );

		// Some servers have DELETE method disabled for some reason.
		// It's common enough that this workaround is nice to have as an option.
		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)/delete', array(
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
			),
		) );
		
		$this->register_custom_routes();
		// register_rest_route( $namespace, '/' . $base . '/schema', array(
		// 	'methods'         => WP_REST_Server::READABLE,
		// 	'callback'        => array( $this, 'get_public_item_schema' ),
		// ) );
	}

	public function register_custom_routes() {
		
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$params = $request->get_params();

		$schema = $this->get_schema();

		$data = $this->query( $params );
		$data = $this->prepare_collection_for_api_response( $data );

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$params = $request->get_params();
		$recursive = ( !empty( $params['recursive'] ) ) ? $params['recursive'] : 0;
		$data = $this->get( $params['id'], $recursive );
		$data = $this->prepare_item_for_api_response( $data );

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}

	public function prepare_item_for_api_response( $item, $recursive=0 ) {
		$keys_to_redact = array();
		foreach ($item as $key => $value) {
			if ( empty( $this->schema[$key]['required_capability'] ) ) {
				continue;
			}

			if ( current_user_can( $this->schema[$key]['required_capability'] ) ) {
				continue;
			}

			$keys_to_redact[$key] = $key;
		}

		$item = array_diff_key( $item, $keys_to_redact );
		return $item;
	}

	public function prepare_collection_for_api_response( $items, $recursive=0 ) {
		$prepared_items = array();
		foreach ($items as $key => $item) {
			$prepared_items[$key] = $this->prepare_item_for_api_response( $item );
		}

		return $prepared_items;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		$params = $request->get_params();
		$insert_id = $this->insert( $params );

		if ( empty( $insert_id ) ) {
			$response = array(
				'response_code' => '500',
				'error' => 'Not created',
				'data' => array(),
			);
		} elseif( is_wp_error( $insert_id ) ) {
			$response = array(
				'response_code' => '500',
				'error' => true,
				'data' => $insert_id,
			);
		} elseif( is_array( $insert_id ) && ! empty( $insert_id['error']['code'] ) ) {
			$response = array(
				'response_code' => $insert_id['error']['code'],
				'error' => $insert_id['error']['message'],
				'data' => $insert_id['error']['data'],
			);
		} else {
			$response = array(
				'response_code' => 200,
				'error' => '',
				'data' => $this->get( $insert_id ),
			);
		}

		return new WP_REST_Response( $response, 200 );

	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$item_id = $request['id'];
		$params = $request->get_params();
		// To remove afte testing (this should happen in the db abstraction level)
		// $schema = $this->get_schema();

		// $default_item = $this->get_field_defaults();
		// if ( isset( $default_item[$this->primary_key] ) ) {
		// 	unset( $default_item[$this->primary_key] );
		// }

		// $updated_item = array();
		// foreach ($default_item as $key => $value) {
		// 	if ( isset( $params[$key] ) ) {
		// 		$updated_item[$key] = $params[$key];
		// 	}
		// }

		// if ( !empty( $schema['date_modified'] ) && empty( $updated_item['date_modified'] ) ) {
		// 	$updated_item['date_modified'] = date('Y-m-d H:i:s' );
		// }
		$this->update( $item_id, $params );

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $this->get( $item_id ),
		);

		return new WP_REST_Response( $response, 200 );
	}

/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_items( $request ) {
		$item_id = $request['id'];
		$params = $request->get_params();
		if ( empty( $params['items']['0']['id'] ) ) {
			return array(
				'response_code' => 500,
				'error' => 'Please specify data: { items: [{ id: 1, title: ...}, {id: 2, title: ...} ...]',
				'data' => '',
			);
		}

		$updated = array();
		foreach ($params['items'] as $key => $item) {
			$item_id = $item['id'];
			unset( $item['id'] );
			$this->update( $item_id, $item );
		}

		// Return all appointment types
		$schema = $this->get_schema();

		$data = $this->query( $params );
		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		if ( empty( $request['id'] ) ) {
			return false;
		}

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $this->delete( $request['id'] ),
		);

		return new WP_REST_Response( $response, 200 );
	}

	public static function logged_in_permissions_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}

		return false;
	}

	public static function nonce_permissions_check( $request ) {
		if ( empty( $request->get_headers()['x_wp_nonce']['0'] ) ) {
			return false;
		}

		$nonce = $request->get_headers()['x_wp_nonce']['0'];
		$is_valid = wp_verify_nonce( $nonce, 'wp_rest' );

		if ( $is_valid ) {
			return $is_valid;
		}

		if ( empty( $request->get_headers()['x_public_nonce']['0'] ) ) {
			return false;
		}

		$nonce = $request->get_headers()['x_public_nonce']['0'];
		$is_valid = self::verify_nonce( $nonce, 'wp_rest' );

		return $is_valid;
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		if ( current_user_can( 'ssa_manage_site_settings' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		if ( current_user_can( 'ssa_manage_site_settings' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		if ( current_user_can( 'ssa_manage_site_settings' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'                   => array(
				'description'        => 'Current page of the collection.',
				'type'               => 'integer',
				'default'            => 1,
				'sanitize_callback'  => 'absint',
			),
			'per_page'               => array(
				'description'        => 'Maximum number of items to be returned in result set.',
				'type'               => 'integer',
				'default'            => 10,
				'sanitize_callback'  => 'absint',
			),
			'search'                 => array(
				'description'        => 'Limit results to those matching a string.',
				'type'               => 'string',
				'sanitize_callback'  => 'sanitize_text_field',
			),
		);
	}

	public static function create_nonce( $action = -1, $user_id = false ) {
		if ( $user_id !== false ) {		
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}
		}

		return substr( wp_hash( $action . '|' . $user_id, 'nonce' ), -12, 10 );
	}

	public static function verify_nonce( $nonce, $action = -1, $user_id = false ) {
		$nonce = (string) $nonce;
		
		if ( empty( $nonce ) ) {
			return false;
		}

		$expected = self::create_nonce( $action, $user_id );

		$token = wp_get_session_token();
		$i     = wp_nonce_tick();

		if ( hash_equals( $expected, $nonce ) ) {
			return 1;
		}

		return false;
	}
}