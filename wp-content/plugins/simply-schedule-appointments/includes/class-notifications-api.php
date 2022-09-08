<?php
/**
 * Simply Schedule Appointments Notifications Api.
 *
 * @since   2.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notifications Api.
 *
 * @since 2.0.3
 */
class SSA_Notifications_Api extends WP_REST_Controller {
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
		$base = 'notifications';

		register_rest_route( $namespace, '/' . $base . '/preview', array(
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'preview_permissions_check' ),
				'args' => array(
					'template' => array(
						'required' => true,
					),
				),
			),
		) );

	}

	public function preview_permissions_check( $request ) {
		if ( current_user_can( 'ssa_manage_site_settings' ) ) {
			return true;
		}

		return false;
	}
	
	public function preview( $request ) {
		$params = $request->get_params();
		$params = shortcode_atts( array(
			'template' => '',
			'appointment_id' => '',
			'appointment_type_id' => '',
		), $params );

		$response = array();
		$template_string = $params['template'];
		$appointment_id = (int)sanitize_text_field( $params['appointment_id'] );
		if ( empty( $appointment_id ) ) {
			$appointment_type_id = (int)sanitize_text_field( $params['appointment_type_id'] );
			if ( empty( $appointment_type_id ) ) {
				return array(
					'response_code' => 500,
					'error' => 'Appointment ID Required',
					'data' => $response,
				);
			}
			$appointment_type_object = new SSA_Appointment_Type_Object( $appointment_type_id );

			// otherwise, we're displaying a default response for an appointment type (not a specific appointment id)
			$template_string = $this->plugin->templates->cleanup_variables_in_string( $template_string );
			$preview = $this->plugin->notifications->get_rendered_template_string_for_example_appointment_type($appointment_type_object, $template_string );
			$response['preview'] = $preview;
			return array(
				'response_code' => 200,
				'error' => '',
				'data' => $response,
			);
		}
		try {
			$appointment_object = new SSA_Appointment_Object( $appointment_id );
		} catch ( Exception $e ) {
			return array(
				'response_code' => 500,
				'error' => 'Appointment ID Not Found',
				'data' => $response,
			);
		}

		$template_string = $this->plugin->templates->cleanup_variables_in_string( $template_string );
		$preview = $this->plugin->notifications->get_rendered_template_string_for_appointment( $appointment_object, $template_string );
		$response['preview'] = $preview;

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => $response,
		);
	}



	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'ssa_manage_site_settings' );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function disconnect_permissions_check( $request ) {
		return current_user_can( 'ssa_manage_site_settings' );
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
