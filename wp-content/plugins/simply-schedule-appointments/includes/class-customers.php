<?php
/**
 * Simply Schedule Appointments Customers.
 *
 * @since   2.7.1
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Customers.
 *
 * @since 2.7.1
 */
class SSA_Customers {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.7.1
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.7.1
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.7.1
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_customers_endpoint' ) );
	}

	public function register_customers_endpoint( $request ) {
		$namespace = 'ssa/v1';
		$base = 'customers';

		register_rest_route( $namespace, '/' . $base . '/', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
		) );
	}

	public function get_items_permissions_check( $request ) {
		return current_user_can( 'ssa_manage_appointments' );
	}

	public function get_items( $request ) {
		$params = $request->get_params();

		$data = array();
		if ( ! empty( $params['ids'] ) ) {
			if ( ! is_array( $params['ids'] ) ) {
				$params['ids'] = json_decode( $params['ids'], true );
			}
			foreach ($params['ids'] as $key => $id) {
				$user = new WP_User( $id );
				if ( empty( (array)$user->data ) ) {
					continue;
				}

				$user_data = (array)$user->data;
				$data[] = array(
					'id' => $user_data['ID'],
					'name' => $user_data['display_name'],
					'gravatar_url' => get_avatar_url( $user_data['ID'] )
				);
			}
		}

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}
}
