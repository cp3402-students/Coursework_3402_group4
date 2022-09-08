<?php
/**
 * Simply Schedule Appointments Users.
 *
 * @since   3.6.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Users.
 *
 * @since 3.6.2
 */
class SSA_Users {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.6.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.6.2
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
	 * @since  3.6.2
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_users_endpoint' ) );
	}

	public function register_users_endpoint() {
		$namespace = 'ssa/v1';
		$base = 'users';

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

		$role__in = array();
		foreach( wp_roles()->roles as $role_slug => $role ) {
			if( ! empty( $role['capabilities']['edit_posts'] ) ) {
				$role__in[] = $role_slug;
				continue;
			}

			if( ! empty( $role['capabilities']['ssa_manage_appointments'] ) ) {
				$role__in[] = $role_slug;
				continue;
			}
		}

		$args = array(
			'role__in' => $role__in,
		);

		$wp_user_query = new WP_User_Query( $args );
		$users = $wp_user_query->get_results();
		if ( empty( $users ) ) {
			return $data;
		}

		foreach ($users as $user) {
			$user_data = get_userdata($user->ID);
			$data[] = array(
				'id' => $user_data->ID,
				'email' => $user_data->user_email,
				'display_name' => $user_data->display_name,
				'gravatar_url' => get_avatar_url( $user_data->ID ),
			);
		}

		$response = array(
			'response_code' => 200,
			'error' => '',
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}
}
