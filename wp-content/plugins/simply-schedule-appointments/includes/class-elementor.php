<?php
/**
 * Simply Schedule Appointments Elementor.
 *
 * @since   3.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Elementor.
 *
 * @since 3.0.3
 */
class SSA_Elementor {

	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin version.
	 */
	const VERSION = '1.5.8.0';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '1.5.8.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '1.5.8.0';

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * @var SSA_Elementor The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  3.0.3
	 */
	public function hooks() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init() {
		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			// add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for required Elementor version
		// if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
		// 	add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
		// 	return;
		// }

		// // Check for required PHP version
		// if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
		// 	add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
		// 	return;
		// }

		// Register widget styles
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );

		// Register widget scripts
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );

		// Add Plugin actions
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
		add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );
	}

	/**
	 * Widget styles
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function widget_styles() {

		wp_register_style( 'ssa-elementor', plugins_url( 'widgets/css/ssa-elementor.css', __FILE__ ) );

	}

	/**
	 * Widget scripts
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function widget_scripts() {
		wp_register_script( 'ssa-elementor', plugins_url( 'widgets/js/ssa-elementor.js', __FILE__ ) );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'simply-schedule-appointments' ),
			'<strong>' . esc_html__( 'SSA Elementor Integration', 'simply-schedule-appointments' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'simply-schedule-appointments' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'simply-schedule-appointments' ),
			'<strong>' . esc_html__( 'SSA Elementor Integration', 'simply-schedule-appointments' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'simply-schedule-appointments' ) . '</strong>',
			 self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'simply-schedule-appointments' ),
			'<strong>' . esc_html__( 'SSA Elementor Integration', 'simply-schedule-appointments' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'simply-schedule-appointments' ) . '</strong>',
			 self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Init Widgets
	 *
	 * Include widgets files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init_widgets() {

		// Include Widget files
		require_once( __DIR__ . '/elementor/widgets/upcoming-appointments.php' );
		require_once( __DIR__ . '/elementor/widgets/booking.php' );

		// Register widget
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \SSA_Elementor_Upcoming_Appointments_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \SSA_Elementor_Booking_Widget() );

	}

	/**
	 * Init Controls
	 *
	 * Include controls files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init_controls() {

		// Include Control files
		// require_once( __DIR__ . '/controls/test-control.php' );

		// Register control
		// \Elementor\Plugin::$instance->controls_manager->register_control( 'control-type-', new \Test_Control() );

	}

}