<?php
/**
 * Simply Schedule Appointments Notices Api.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notices Api.
 *
 * @since 0.1.0
 */
class SSA_Notices_Api extends WP_REST_Controller {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 1.0.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function hooks() {
		$this->register_routes();
	}


	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version = '1';
		$namespace = 'ssa/v' . $version;
		$base = 'notices';
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(

				),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '/(?P<id>[a-zA-Z0-9_-]+)', array(
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
				'args'            => array(
					'global' => array(
						'required' => true,
					),
				),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'global'    => array(
						'required'      => true,
					),
				),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '/schema', array(
			'methods'         => WP_REST_Server::READABLE,
			'callback'        => array( $this, 'get_public_item_schema' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$dismissed_notices = $this->plugin->notices->get_dismissed_notices();
		if ( count( $dismissed_notices ) ) {
			$dismissed_notices = array_combine( $dismissed_notices, array_fill(0, count( $dismissed_notices ), true ) );
		}
		if ( empty( $dismissed_notices ) ) {
			$dismissed_notices = new stdClass();
		}

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'dismissed_notices' => $dismissed_notices,
			),
		);
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$is_visible = true;

		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		/* is this dismissed globally? */
		$global_dismissed_notices = get_option( 'ssa_dismissed_notices', array() );
		if ( in_array( $notice_name, $global_dismissed_notices ) ) {
			$is_visible = false;
		}

		/* is this dismissed for the current user? */
		if ( is_user_logged_in() ) {
			$user_dismissed_notices = get_user_meta( get_current_user_id(), 'ssa_dismissed_notices', true );
			if ( empty( $user_dismissed_notices ) ) {
				$user_dismissed_notices = array();
			}

			if ( in_array( $notice_name, $user_dismissed_notices ) ) {
				$is_visible = false;
			}
		}

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,
				'isVisible' => $is_visible,
			),
		);
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );
		$is_visible = false;

		if ( !empty( $params['global'] ) ) {
			$global_dismissed_notices = get_option( 'ssa_dismissed_notices', array() );
			if ( !in_array( $notice_name, $global_dismissed_notices ) ) {
				$global_dismissed_notices[] = $notice_name;
				update_option( 'ssa_dismissed_notices', $global_dismissed_notices );
			}
		} elseif ( is_user_logged_in() ) {
			$user_dismissed_notices = get_user_meta( get_current_user_id(), 'ssa_dismissed_notices', true );
			if ( empty( $user_dismissed_notices ) ) {
				$user_dismissed_notices = array();
			}

			if ( !in_array( $notice_name, $user_dismissed_notices ) ) {
				$user_dismissed_notices[] = $notice_name;
				update_user_meta( get_current_user_id(), 'ssa_dismissed_notices', $user_dismissed_notices );
			}
		}
		
		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,
				'isVisible' => $is_visible,
			),
		);
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );
		$is_visible = true;

		if ( !empty( $params['global'] ) ) {
			$global_dismissed_notices = get_option( 'ssa_dismissed_notices', array() );
			if ( in_array( $notice_name, $global_dismissed_notices ) ) {
				$pos = array_search( $notice_name, $global_dismissed_notices );
				unset( $global_dismissed_notices[$pos] );
				update_option( 'ssa_dismissed_notices', $global_dismissed_notices );
			}
		} elseif ( is_user_logged_in() ) {
			$user_dismissed_notices = get_user_meta( get_current_user_id(), 'ssa_dismissed_notices', true );
			if ( empty( $user_dismissed_notices ) ) {
				$user_dismissed_notices = array();
			}

			if ( in_array( $notice_name, $user_dismissed_notices ) ) {
				$pos = array_search( $notice_name, $user_dismissed_notices );
				unset( $user_dismissed_notices[$pos] );
				update_user_meta( get_current_user_id(), 'ssa_dismissed_notices', $user_dismissed_notices );
			}
		}

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,
				'isVisible' => $is_visible,
			),
		);
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return TD_API_Model::nonce_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return TD_API_Model::nonce_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return array();
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
}
