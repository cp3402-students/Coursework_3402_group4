<?php
/**
 * Simply Schedule Appointments Templates Api.
 *
 * @since   5.2.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Templates Api.
 *
 * @since 5.2.0
 */
class SSA_Templates_Api extends WP_REST_Controller {
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
		$version   = '1';
		$namespace = 'ssa/v' . $version;
		$base      = 'templates';

		register_rest_route(
			$namespace,
			'/' . $base . '/validate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'validate_twig_template' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args' => array(
						'message' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
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
	 * Given a message, verify if it's a valid Twig string or if it contain errors while parsing it.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response $response The response data.
	 */
	public function validate_twig_template( WP_REST_Request $request ) {
		$template_string = $request->get_param( 'message' );
		$template_string = $this->plugin->templates->cleanup_variables_in_string( $template_string );
		$template_string = $this->plugin->notifications->prepare_notification_template( $template_string );

		$loader = new Twig_Loader_Array(
			array(
				'template' => $template_string,
			)
		);

		$twig = new Twig_Environment( $loader );
		$twig->addExtension( new SSA_Twig_Extension() );
		$twig->getExtension( 'Twig_Extension_Core' )->setTimezone( 'UTC' );

		try {
			$render = $twig->render( 'template' );
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => "Error: {$e->getMessage()}",
				),
				200
			);
		}

		return new WP_REST_Response( array( 'success' => true ) );
	}
}