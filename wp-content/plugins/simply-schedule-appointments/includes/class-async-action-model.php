<?php
/**
 * Simply Schedule Appointments Action Model.
 *
 * @since   1.9.4
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Action Model.
 *
 * @since 1.9.4
 */
class SSA_Async_Action_Model extends TD_Async_Action_Model {
	protected $hook_namespace = 'ssa';
	protected $db_namespace = 'ssa';
	protected $api_namespace = 'ssa';
	protected $api_version = '1';

	/**
	 * Parent plugin class.
	 *
	 * @since 1.9.4
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  1.9.4
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.9.4
	 */
	public function hooks() {
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );
		if( ! wp_next_scheduled( 'ssa_cron_process_async_actions' ) ){
			add_action( 'init', array( $this, 'schedule_cron' ) );
		}
		add_action( 'ssa_cron_process_async_actions', array( $this, 'execute_cron_process_async_actions' ) );
	}

	public function filter_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ssa_async_interval'] ) ) {
			$interval_in_seconds = 60;
			if ( defined( 'SSA_ASYNC_CRON_INTERVAL' ) ) {
				$interval_in_seconds = SSA_ASYNC_CRON_INTERVAL;
			}

			$schedules['ssa_async_interval'] = array(
				'interval' => $interval_in_seconds,
				'display' => __( 'Once every minute', 'simply-schedule-appointments' ),
			);
		}

		return $schedules;
	}

	public function schedule_cron() {
		wp_schedule_event( time(), 'ssa_async_interval', 'ssa_cron_process_async_actions' );
	}

	public function execute_cron_process_async_actions() {
		$this->process();
	}

	public function register_routes() {
		$version = '1';
		$namespace = 'ssa/v' . $version;
		$base = 'async';

		register_rest_route( $namespace, '/' . $base , array(
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'process_endpoint' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'process_endpoint' ),
				'permission_callback' => '__return_true',
			),
		) );
	}

	public function process_endpoint( $request ) {
		define( 'SSA_DOING_ASYNC', true );
		$params = $request->get_params();
		$params = shortcode_atts( array(
			'object_type' => '',
			'object_id' => '',
		), $params );

		// TODO: narrow scope to only appointment type

		$this->process();

		return true;
	}
}

function ssa_doing_async() {
	return defined( 'SSA_DOING_ASYNC' ) && SSA_DOING_ASYNC;
}

function ssa_queue_action( $hook, $action = null, $priority = 10, $payload = array(), $object_type = null, $object_id = null, $action_group = null, $meta = array() ) {
	if ( empty( $action ) ) {
		$action = 'ssa_async_'.$hook;
	}
	
	ssa()->async_action_model->queue_action( $hook, $action, $priority, $payload, $object_type, $object_id, $action_group, $meta );
}

function ssa_complete_action( $action_id, $response = array() ) {
	ssa()->async_action_model->complete_action( $action_id, $response );
}
