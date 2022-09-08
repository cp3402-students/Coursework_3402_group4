<?php
/**
 * Simply Schedule Appointments Shortcodes.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Shortcodes.
 *
 * @since 0.0.3
 */
class SSA_Shortcodes {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;
	protected $script_handle_whitelist = array();
	protected $style_handle_whitelist = array();
	protected $custom_script_handle_whitelist = array();
	protected $disable_third_party_styles = false;
	protected $disable_third_party_scripts = false;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
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
	 * @since  0.0.3
	 */
	public function hooks() {
		add_shortcode( 'ssa_booking', array( $this, 'ssa_booking' ) );
		add_shortcode( 'tec_ssa_booking', array( $this, 'tec_ssa_booking' ) );
		add_shortcode( 'ssa_upcoming_appointments', array( $this, 'ssa_upcoming_appointments' ) );
		add_shortcode( 'ssa_admin', array( $this, 'ssa_admin' ) );

		add_action( 'init', array( $this, 'store_enqueued_styles_scripts' ), 1 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts_sitewide' ), 0 );

		add_action( 'wp_enqueue_scripts', array( $this, 'disable_third_party_scripts' ), 9999999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'disable_third_party_styles' ), 9999999 );

		add_action('init', array( $this, 'custom_rewrite_basic' ) );
		add_action( 'query_vars', array( $this, 'register_query_var' ) );
		add_filter( 'template_include', array( $this, 'hijack_booking_page_template' ) );
		add_filter( 'template_include', array( $this, 'hijack_embedded_page' ), 9999999 );
		add_filter( 'template_include', array( $this, 'hijack_appointment_edit_page' ), 9999999 );
		add_filter( 'template_include', array( $this, 'hijack_appointment_edit_url' ), 9999999 );

		// REST API Endpoint to pull generate output from shortcode
		add_action('rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function custom_rewrite_basic() {
		add_rewrite_rule('^ssa-embed?', 'index.php?ssa_embed=yes', 'top');
	}

	public function register_query_var( $vars ) {
		$vars[] = 'ssa_embed';

		return $vars;
	}

	public function store_enqueued_styles_scripts() {
		if ( is_admin() ) {
			return;
		}

		global $wp_scripts;
		if ( empty( $wp_scripts->queue ) ) {
			$this->script_handle_whitelist = array();
		} else {
			$this->script_handle_whitelist = $wp_scripts->queue;
		}
	}

	public function disable_third_party_scripts() {
		if ( !$this->disable_third_party_scripts ) {
			return;
		}

		global $wp_scripts;
		if ( ! empty( $wp_scripts->queue ) ) {
			foreach ($wp_scripts->queue as $key => $handle) {
				if ( strpos( $handle, 'ssa-' ) === 0 ) {
					continue;
				}

				if ( in_array( $handle, $this->script_handle_whitelist ) || in_array( $handle, $this->custom_script_handle_whitelist ) ) {
					continue;
				}

				wp_dequeue_script( $handle );
			}
		}
	}

	public function disable_third_party_styles() {
		if ( !$this->disable_third_party_styles ) {
			return;
		}

		global $wp_styles;
		if ( ! empty( $wp_styles->queue ) ) {
			foreach ($wp_styles->queue as $key => $handle) {
				if ( strpos( $handle, 'ssa-' ) === 0 ) {
					continue;
				}

				if ( in_array( $handle, $this->style_handle_whitelist ) || in_array( $handle, $this->custom_script_handle_whitelist ) ) {
					continue;
				}

				wp_dequeue_style( $handle );
			}
		}
	}

	public function is_booking_page() {
		$page_id = get_queried_object_id();
		$settings = $this->plugin->settings->get();
		if ( empty( $page_id ) || $page_id != $settings['global']['booking_post_id'] ) {
			return false;
		}

		return true;
	}

	public function get_appointment_edit_permalink() {
		return apply_filters( 'ssa/booking/appointment_edit_permalink', 'appointments/change' );
	}

	public function is_embedded_page() {
		$query_var = get_query_var( 'ssa_embed' );
		if ( !empty( $query_var ) ) {
			return true;
		}
		if ( !empty( $_GET['ssa_embed'] ) ) {
			return true;
		}

		global $wp;
		if ( !empty( $wp->matched_rule ) && $wp->matched_rule === '^ssa-embed?' ) {
			return true;
		}

		if ( $wp->request === 'ssa-embed' ) {
			return true;
		}

		return false;
	}

	public function hijack_embedded_page( $template ) {
		if ( ! $this->is_embedded_page() ) {
			return $template;
		}

		return $this->plugin->dir( 'booking-app/iframe-inner.php' );
	}

	public function hijack_booking_page_template( $template ) {
		if ( !$this->is_booking_page() ) {
			return $template;
		}

		return $this->plugin->dir( 'booking-app/fullscreen-page.php' );
	}

	public function hijack_appointment_edit_page( $template ) {
		$appointment_edit_permalink = $this->get_appointment_edit_permalink();
		global $wp;
		$uri = sanitize_text_field( $wp->request );
		if ( strpos( $uri, $appointment_edit_permalink ) !== 0 ) {
			return $template;
		}

		$uri = str_replace( $appointment_edit_permalink, '', $uri );
		$uri = ltrim( $uri, '/' );
		$uri = rtrim( $uri, '/' );
		$parts = explode( '-', $uri );

		$provided_hash = $parts['0'];
		$provided_hash = substr( $uri, 0, 32 );
		$appointment_id = substr( $uri, 32 );
		$correct_hash = $this->plugin->appointment_model->get_id_token( array( 'id' => $appointment_id ) );

		if ( $correct_hash != $provided_hash ) {;
			die( 'An error occurred, please check the URL' );
		}

		add_filter( 'show_admin_bar', '__return_false' );
		global $ssa_current_appointment_id;
		$ssa_current_appointment_id = $appointment_id;
		return $this->plugin->dir( 'booking-app/page-appointment-edit.php' );
	}

	public function hijack_appointment_edit_url( $template ) {
		if ( empty( $_GET['appointment_action'] ) || 'edit' !== $_GET['appointment_action'] || empty( $_GET['appointment_token'] ) ) {
			return $template;
		}
		$this->disable_third_party_scripts = true;
		$this->disable_third_party_styles = true;
		$appointment_token = sanitize_text_field( $_GET['appointment_token'] );
		$provided_hash = substr( $appointment_token, 0, 32 );
		$appointment_id = substr( $appointment_token, 32 );
		$correct_hash = $this->plugin->appointment_model->get_id_token( array( 'id' => $appointment_id ) );

		if ( $correct_hash != $provided_hash ) {;
			die( 'An error occurred, please check the URL' );
		}

		add_filter( 'show_admin_bar', '__return_false' );
		global $ssa_current_appointment_id;
		$ssa_current_appointment_id = $appointment_id;
		return $this->plugin->dir( 'booking-app/page-appointment-edit.php' );
	}

	public function body_class( $classes ) {
		$classes = "$classes ssa-admin-app";

		return $classes;
	}


	public function register_scripts() {
		wp_register_script( 'ssa-booking-manifest', $this->plugin->url('booking-app/dist/static/js/manifest.js'), array(), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-booking-vendor', $this->plugin->url('booking-app/dist/static/js/vendor.js'), array( 'ssa-booking-manifest' ), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-booking-app', $this->plugin->url('booking-app/dist/static/js/app.js'), array( 'ssa-booking-vendor' ), Simply_Schedule_Appointments::VERSION, true );
		$this->custom_script_handle_whitelist[] = 'ssa-booking-manifest';
		$this->custom_script_handle_whitelist[] = 'ssa-booking-vendor';
		$this->custom_script_handle_whitelist[] = 'ssa-booking-app';

		wp_register_script( 'ssa-iframe-inner', $this->plugin->url('assets/js/iframe-inner.js'), array(), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-iframe-outer', $this->plugin->url('assets/js/iframe-outer.js'), array(), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-tracking', $this->plugin->url('assets/js/ssa-tracking.js'), array(), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-form-embed', $this->plugin->url('assets/js/ssa-form-embed.js'), array( 'jquery' ), Simply_Schedule_Appointments::VERSION, true );
		wp_register_script( 'ssa-unsupported-script', $this->plugin->url('assets/js/unsupported.js'), array(), Simply_Schedule_Appointments::VERSION );
	}

	public function register_styles() {
		wp_register_style( 'ssa-booking-material-icons', $this->plugin->url('assets/css/material-icons.css'), array(), Simply_Schedule_Appointments::VERSION );
		wp_register_style( 'ssa-booking-style', $this->plugin->url('booking-app/dist/static/css/app.css'), array(), Simply_Schedule_Appointments::VERSION );
		wp_register_style( 'ssa-booking-roboto-font', $this->plugin->url('assets/css/roboto-font.css'), array(), Simply_Schedule_Appointments::VERSION );
		wp_register_style( 'ssa-iframe-inner', $this->plugin->url('assets/css/iframe-inner.css'), array(), Simply_Schedule_Appointments::VERSION );
		wp_register_style( 'ssa-styles', $this->plugin->url('assets/css/ssa-styles.css'), array(), Simply_Schedule_Appointments::VERSION );
		wp_register_style( 'ssa-unsupported-style', $this->plugin->url('assets/css/unsupported.css'), array(), Simply_Schedule_Appointments::VERSION );
	}

	public function enqueue_styles() {
		global $post;
		if ( $this->is_embedded_page() ) {
			wp_enqueue_style( 'ssa-unsupported-style' );
			wp_enqueue_style( 'ssa-booking-material-icons' );
			wp_enqueue_style( 'ssa-booking-style' );
			wp_enqueue_style( 'ssa-booking-roboto-font' );
			wp_enqueue_style( 'ssa-iframe-inner' );
		} elseif ( is_a( $post, 'WP_Post' ) ) {
	      wp_enqueue_style( 'ssa-styles');
		}
	}

	public function maybe_enqueue_scripts_sitewide() {
		$developer_settings = $this->plugin->developer_settings->get();
		if ( empty( $developer_settings['enqueue_everywhere'] ) ) {
			return;
		}

		if ( $this->is_embedded_page() ) {
			return;
		}

		wp_enqueue_script( 'ssa-iframe-outer' );
		wp_enqueue_script( 'ssa-tracking' );
		wp_enqueue_script( 'ssa-form-embed' );
	}

	public function tec_ssa_booking( $atts = array() ) {
		$post_id = get_the_ID();
		if ( ! function_exists( 'tribe_is_event' ) || ! tribe_is_event( $post_id ) ) {
			return $this->ssa_booking( $atts );
		}

		$event = tribe_get_event( $post_id );
		if ( empty( $atts ) ) {
			$atts = array();
		}
		$atts = array_merge( array(
			'availability_start_date' => $event->dates->start_utc->format( 'Y-m-d H:i:s' ),
			'availability_end_date' => $event->dates->end_utc->format( 'Y-m-d H:i:s' ),
		), $atts );

		return $this->ssa_booking( $atts );
	}

	public function get_ssa_booking_arg_defaults() {
		return array(
			'integration'     => '', // internal use only for integrations
			'type'            => '',
			'types'           => '',
			'edit'            => '',
			'view'            => '',
			'ssa_locale'      => SSA_Translation::get_locale(),
			'ssa_is_rtl'      => SSA_Translation::is_rtl(),
			'sid'             => sha1( gmdate( 'Ymd' ) . get_current_user_id() ), // busts full-page caching so each URL is user-specific (daily) and doesn't leak sensitive data
			'availability_start_date' => '',
			'availability_end_date' => '',
			
			'accent_color'    => '',
			'background'      => '',
			'padding'         => '',
			'font'            => '',
			'booking_url'     => urlencode( get_permalink() ),
			'booking_post_id' => urlencode( get_the_ID() ),
			'booking_title'   => urlencode( get_the_title() ),
			'_wpnonce' => wp_create_nonce( 'wp_rest' ),
		);
	}

	public function get_passed_args() {
		$passed_args = array_diff_key(
			$_GET,
			$this->get_ssa_booking_arg_defaults()
		);

		return $passed_args;
	}

	public function ssa_booking( $atts, $is_embedded_page = false ) {
		$atts = shortcode_atts( $this->get_ssa_booking_arg_defaults(), $atts, 'ssa_booking' );
		$atts = apply_filters( 'ssa_booking_shortcode_atts', $atts );

		$appointment_type = '';
		if ( !empty( $atts['type'] ) ) {
			if ( $atts['type'] == (string)(int) $atts['type'] ) {
				// integer ID provided
				$appointment_type_id = (int) sanitize_text_field( $atts['type'] );
			} else {
				// slug provided
				$appointment_types = $this->plugin->appointment_type_model->query( array(
					'slug' => sanitize_text_field( $atts['type'] ),
					'status' => 'publish',
				) );
				if ( !empty( $appointment_types['0']['id'] ) ) {
					$appointment_type_id = $appointment_types['0']['id'];
				}

				if ( empty( $appointment_type_id ) ) {
					$error_message = '<h3>Sorry this appointment type isn\'t available, please check back later</h3>';
					if ( current_user_can( 'ssa_manage_site_settings' ) ) {
						$error_message .= '<code>The specified appointment type "'.$atts['type'].'" can\'t be found</code> (this message only viewable to site administrators)';
					}

					return $error_message;
				}
			}

			if ( !empty( $appointment_type_id ) ) {
				$appointment_type = $this->plugin->appointment_type_model->get( $appointment_type_id );
				if ( empty( $atts['types'] ) ) {
					$atts['types'] = $appointment_type_id;
				}
			}
		}

		if ( $is_embedded_page || $this->is_embedded_page() ) {
			// wp_localize_script( 'ssa-booking-app', 'ssaBookingParams', array(
			// 	'apptType' => $appointment_type,
			// 	'translatedStrings' => array(
			// 	),
			// ) );
			wp_localize_script( 'ssa-booking-app', 'ssa', $this->plugin->bootstrap->get_api_vars() );
			wp_localize_script( 'ssa-booking-app', 'ssa_translations', $this->get_translations() );

			wp_enqueue_script( 'ssa-unsupported-script' );
			wp_enqueue_script( 'ssa-booking-manifest' );
			wp_enqueue_script( 'ssa-booking-vendor' );
			wp_enqueue_script( 'ssa-booking-app' );
			wp_enqueue_script( 'ssa-iframe-inner' );

			return '
			<div id="ssa-booking-app">
				<noscript>
					<div class="unsupported">
						<div class="unsupported-container">
							<h1 class="unsupported-label">' . __('Simply Schedule Appointments requires JavaScript', 'simply-schedule-appointments') . '</h1>
							<p class="unsupported-description">' . __('To book an appointment, please make sure you enable JavaScript in your browser.', 'simply-schedule-appointments') . '</p>
						</div>
					</div>
				</noscript>
			</div>
			<div id="ssa-unsupported" style="display:none;">
					<div class="unsupported">
						<div class="unsupported-container">
							<h1 class="unsupported-label">' . __('Unsupported Browser', 'simply-schedule-appointments') . '</h1>
							<p class="unsupported-description">' . __('To book an appointment, please update your browser to something more modern. We recommend Firefox or Chrome.', 'simply-schedule-appointments') . '</p>
						</div>
					</div>
			</div>
			';
		}

		wp_localize_script( 'ssa-iframe-outer', 'ssa', $this->plugin->bootstrap->get_api_vars() );
		wp_enqueue_script( 'ssa-iframe-outer' );
		if ( $this->plugin->settings_installed->is_enabled( 'tracking' ) ) {
			wp_enqueue_script( 'ssa-tracking' );
		}
		if ( !empty( $atts['integration'] ) ) {
			wp_enqueue_script( 'ssa-form-embed' );
		}
		$settings = $this->plugin->settings->get();
		// $link = get_page_link( $settings['global']['booking_post_id'] );
		$api_vars = $this->plugin->bootstrap->get_api_vars();

		if ( !empty( $atts['edit'] ) ) {
			$appointment_id = sanitize_text_field( $atts['edit'] );
			if ( ! empty( $appointment_id ) ) {
				$appointment = SSA_Appointment_Object::instance( $appointment_id );
				$appointment_type_id = $appointment->get_appointment_type()->id;
	
				if ( empty( $atts['types'] ) ) {
					$atts['types'] = $appointment_type_id;
				}
			}
		}

		$link = add_query_arg( $atts, $api_vars['api']['root'] . '/embed-inner' );

		if ( !empty( $atts['edit'] ) ) {
			$appointment_id = sanitize_text_field( $atts['edit'] );

			// if it's an integration form, and the appointment status is 'pending_form', load the appointment on a pre confirmed state.
			if ( ! empty( $atts['integration'] ) && $appointment->is_reserved() ) {
				$appointment_id_token = ! empty( $atts['token'] ) ? $atts['token'] : $this->plugin->appointment_model->get_id_token( array( 'id' => sanitize_text_field( $atts['edit'] ) ) );

				$link = add_query_arg( array( 'token' => $appointment_id_token ), $link );
				$link = $link . '#/load-appointment/' . $appointment_id;
			} else {
				$appointment_id_token = $this->plugin->appointment_model->get_id_token( array( 'id' => sanitize_text_field( $atts['edit'] ) ) );
				$link = $link . '#/change/' . $appointment_id_token . $appointment_id;
			}
		} elseif ( ! empty( $appointment_type ) ) {
			// $link = $link . '#/type/' . $appointment_type['slug'];
			$link = $link . '#/'; // the types array will be restricted so that this is the only type

			$link = add_query_arg( $atts, $api_vars['api']['root'] . '/embed-inner' );
		}
		
		if ( !empty( $atts['edit'] ) ) {
			$appointment_id = sanitize_text_field( $atts['edit'] );
			$appointment_id_token = $this->plugin->appointment_model->get_id_token( array( 'id' => sanitize_text_field( $atts['edit'] ) ) );
			$link = $link . '#/change/'. $appointment_id_token . $appointment_id;
		} elseif ( ! empty( $appointment_type ) ) {
			// $link = $link . '#/type/' . $appointment_type['slug'];
			$link = $link . '#/'; // the types array will be restricted so that this is the only type
		} else {
			$link = $link . '#/';
		}

		if ( ! empty( $atts['integration'] ) ) {
			$link = str_replace( '#/', '#/integration/'. esc_attr( $atts['integration'] ).'/', $link );
		}

		if ( !empty( $atts['view'] ) ) {
			$link .= '/view/'.$atts['view'];
		}

		$link = SSA_Bootstrap::maybe_fix_protocol( $link );
		$escaped_passed_args = array();	
		foreach( $this->get_passed_args() as $passed_key => $passed_value ) {
			if ( $passed_key==='Email' ) {
				$passed_value = str_replace( ' ', '+', trim( $passed_value ) ); // since + is a URL equivalent of %20, an email like 'example+123@gmail.com' needs to be re-encoded to prevent it from becoming 'example 123@gmail.com'
			}
			$escaped_passed_args[htmlspecialchars($passed_key)] = htmlspecialchars($passed_value);
		}

		$link = add_query_arg( $escaped_passed_args, $link );
		$link = str_replace('+', '%2B', $link);
		$lazy_load_mode = apply_filters( 'ssa/performance/lazy_load', false );
		if ( false === $lazy_load_mode ) {
			$iframe_src = '<iframe src="' . $link . '" height="400px" width="100%" name="ssa_booking" loading="eager" frameborder="0" data-skip-lazy="1" class="ssa_booking_iframe skip-lazy" title="' . esc_attr__( 'Book a time', 'simply-schedule-appointments' ) . '"></iframe>';
		} else if ( true === $lazy_load_mode ) {
			$iframe_src = '<iframe src="' . $link . '" height="400px" width="100%" name="ssa_booking" loading="lazy" frameborder="0" class="ssa_booking_iframe" title="' . esc_attr__( 'Book a time', 'simply-schedule-appointments' ) . '"></iframe>';
		} else {
			$iframe_src = '<iframe src="' . $link . '" height="400px" width="100%" name="ssa_booking" frameborder="0" class="ssa_booking_iframe" title="' . esc_attr__( 'Book a time', 'simply-schedule-appointments' ) . '"></iframe>';
		}

		return $iframe_src;
	}

	public function ssa_upcoming_appointments( $atts ) {
		$atts = shortcode_atts( array(
			'status' => 'booked',
			'number' => -1,
			'orderby' => 'start_date',
			'order' => 'ASC',
			'customer_id' => get_current_user_id(),

			'no_results_message' => __( 'No upcoming appointments', 'simply-schedule-appointments' ),
			'logged_out_message' => '',

			'start_date_min' => ssa_datetime()->sub( new DateInterval( 'PT1H' ) )->format( 'Y-m-d H:i:s' ),

			'details_link_displayed' => true,
			'details_link_label' => __( 'View Details', 'simply-schedule-appointments' ),
		), $atts, 'ssa_upcoming_appointments' );

		ob_start();
		include $this->plugin->dir( 'templates/customer/upcoming-appointments.php' );
		$output = ob_get_clean();

		return $output;
	}

	public function ssa_admin() {

		// If current user or visitor can't manage appointments, display a warning message.
		if ( ! current_user_can( 'ssa_manage_appointments' ) ) { 
			return '<div class="ssa-admin-warning">' . __( 'It looks like you\'re not allowed to see this screen. Please check with your site administrator if you think this message is an error.', 'simply-schedule-appointments' ) . '</div>';
		}

		// Make sure we have the nonce for the admin page.
		$nonce = wp_create_nonce( 'wp_rest' );

		wp_localize_script( 'ssa-iframe-outer', 'ssa', $this->plugin->bootstrap->get_api_vars() );
		wp_enqueue_script( 'ssa-iframe-outer' );
		$api_vars = $this->plugin->bootstrap->get_api_vars();

		$link = add_query_arg(
			array(
				'_wpnonce' => $nonce,
			),
			$api_vars['api']['root'] . '/embed-inner-admin'
		);

		// Check if we have a 'ssa_state' url parameter on the current page. If so, we need to add it to the iframe src as a url hash.
		$ssa_state = isset( $_GET['ssa_state'] ) ? sanitize_text_field( $_GET['ssa_state'] ) : '';
		if ( ! empty( $ssa_state ) ) {
			$link = $link . '#' . $ssa_state;
		}

		$link = SSA_Bootstrap::maybe_fix_protocol( $link );

		return '<iframe src="' . $link . '" height="400px" width="100%" name="ssa_admin" loading="eager" frameborder="0" data-skip-lazy="1" class="ssa_booking_iframe skip-lazy"></iframe>';
	}

	public function get_translations() {
		include $this->plugin->dir( 'languages/booking-app-translations.php' );
		return $translations;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_rest_routes() {
		$version = '1';
		$namespace = 'ssa/v' . $version;
		$base = 'embed';
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_embed_output' ),
				'permission_callback' => '__return_true',
				'args'            => array(
				),
			),
		) );

		register_rest_route( $namespace, '/' . 'embed-inner', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_embed_inner_output' ),
				'permission_callback' => '__return_true',
				'args'            => array(
				),
			),
		) );

		register_rest_route( $namespace, '/' . 'embed-inner-admin', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_embed_inner_admin_output' ),
				'permission_callback' => '__return_true',
				'args'            => array(
				),
			),
		) );
	}

	/**
	 * Takes $_REQUEST params and returns the booking shortcode output.
	 * 
	 * @since 3.7.6
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_embed_output( WP_REST_Request $request ) {
		$params = $request->get_params();

		$args = array();

		if( ! empty( $params['appointment_type'] ) ) {
			$args['type'] = esc_attr( $params['appointment_type'] );
		}

		if( ! empty( $params['accent_color'] ) ) {
			$args['accent_color'] = ltrim( esc_attr( $params['accent_color'] ), '#' );
		}

		if( ! empty( $params['background_color'] ) ) {
			$args['background'] = ltrim( esc_attr( $params['background_color'] ), '#' );
		}

		if( ! empty( $params['font'] ) ) {
			$args['font'] = esc_attr( $params['font'] );
		}

		if( ! empty( $params['padding'] ) ) {
			$args['padding'] = esc_attr( $params['padding'] );
		}

		$output = ssa()->shortcodes->ssa_booking( $args );

		return new WP_REST_Response( $output, 200 );
	}

	/**
	 * Takes $_REQUEST params and returns the booking shortcode output.
	 * 
	 * @since 3.7.6
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_embed_inner_output( WP_REST_Request $request ) {
		$params = $request->get_params();

		$args = array();

		header( 'Content-Type: text/html' );

		// If "SSA_BOOKING_APP_NEW" constant is set, embed the new booking app instead of the old one.
		if ( defined( 'SSA_BOOKING_APP_NEW' ) && SSA_BOOKING_APP_NEW ) {
			include $this->plugin->dir( 'booking-app-new/iframe-inner.php' );
		} else {
			include $this->plugin->dir( 'booking-app/iframe-inner.php' );
		}
		// $output = ssa()->shortcodes->ssa_booking( $params, true );
		// echo $output;
		exit;

		// return new WP_REST_Response( $output, 200 );
	}

	/**
	 * Takes $_REQUEST params and returns the admin shortcode output.
	 * 
	 * @since 3.7.6
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_embed_inner_admin_output( WP_REST_Request $request ) {
		$params = $request->get_params();

		$args = array();

		header( 'Content-Type: text/html' );
		include $this->plugin->dir( 'admin-app/iframe-inner.php' );
		// $output = ssa()->shortcodes->ssa_booking( $params, true );
		// echo $output;
		exit;

		// return new WP_REST_Response( $output, 200 );
	}
}
