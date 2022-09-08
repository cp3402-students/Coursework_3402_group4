<?php
/**
 * Simply Schedule Appointments Bootstrap.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Bootstrap.
 *
 * @since 0.0.3
 */
class SSA_Bootstrap {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.0.3
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.0.3
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
	 * @since  0.0.3
	 * @return void
	 */
	public function hooks() {
		// add_action( 'wp_footer', array( $this, 'output_ssa_variable' ) );
		// add_action( 'admin_footer', array( $this, 'output_ssa_variable' ) );

		add_filter( 'nonce_user_logged_out', array( $this, 'fix_bug_from_aggressive_woocommerce_cookie' ), 1000, 2 );
	}

	public static function maybe_remove_query_args( $url ) {
		$pos_of_question_mark = strpos( $url, '?' );
		if ( false === $pos_of_question_mark ) {
			return $url;
		}

		$url = substr( $url, 0, $pos_of_question_mark );
		return $url;
	}

	public static function maybe_fix_protocol( $url, $desired_protocol = null ) {
		$pos_of_colon_slash_slash = strpos( $url, '://' );
		if ( $pos_of_colon_slash_slash === false ) {
			return $url;
		}

		if ( empty( $desired_protocol ) ) {
			if ( defined( 'SSA_PROTOCOL' ) && str_to_lower( SSA_PROTOCOL ) === 'https' ) {
				$desired_protocol = 'https';
			} else {
				$desired_protocol = 'http';
			}
			if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) {
				$desired_protocol = 'https';
			} elseif ( !empty( $_SERVER['REDIRECT_HTTPS'] ) && $_SERVER['REDIRECT_HTTPS'] !== 'off' ) {
				$desired_protocol = 'https';
			} elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
				$desired_protocol = 'https';
			} elseif ( !empty( $_SERVER['protocol'] ) ) {
				$desired_protocol = strtolower( substr( $_SERVER["SERVER_PROTOCOL"], 0, 5 ) ) == 'https' ? 'https' : 'http';
			}
		}

		$url = $desired_protocol . '://' . substr( $url, $pos_of_colon_slash_slash + 3 );

		return $url;
	}

	public static function maybe_fix_www_prefix( $url, $should_be_www = null ) {
		$pos_of_colon_slash_slash = strpos( $url, '://' );
		if ( $pos_of_colon_slash_slash === false ) {
			return $url;
		}

		if ( $should_be_www === null ) {
			if ( strpos( $_SERVER['HTTP_HOST'], 'www.' ) === 0 ) {
				$should_be_www = true;
			} else {
				$should_be_www = false;
			}
		}

		if ( !empty( $should_be_www ) ) {
			$url = str_replace( array(
				'://',
				'://www.www.',
			), array( 
				'://www.',
				'://www.',
			), $url );
		} else {
			$url = str_replace( array(
				'://www.www.',
				'://www.',
			), array( 
				'://',
				'://',
			), $url );
		}

		return $url;
	}

	public function get_api_vars() {
		// $avatar = false;

		// $user = get_userdata( get_current_user_id() );
		// if ( !empty( $user->data->user_email ) ) {
		// 	$avatar = get_avatar_data( $user->ID );
		// }

		$admin_static_url = $this->plugin->url( 'admin-app/dist/static' );
		if ( defined( 'WP_SITEURL' ) && WP_SITEURL === 'http://localhost:8080' ) {
			$admin_static_url = $this->plugin->url( 'admin-app/public/static' );
		}

		$booking_static_url = $this->plugin->url( 'booking-app/dist/static' );
		if ( defined( 'WP_SITEURL' ) && WP_SITEURL === 'http://localhost:8080' ) {
			if ( defined( 'SSA_BOOKING_APP_NEW' ) && SSA_BOOKING_APP_NEW ) {
				$booking_static_url = $this->plugin->url( 'booking-app/public/static' );
			} else {
				$booking_static_url = $this->plugin->url( 'booking-app/static' );
			}
		}

		$api_array = array(
			'prefix'				=> rest_get_url_prefix(),
			'root'					=> untrailingslashit( self::maybe_fix_protocol( self::maybe_remove_query_args( home_url( rest_get_url_prefix().'/ssa/v1' ) ) ) ),
			'admin_static_url' 		=> self::maybe_fix_protocol( $admin_static_url ),
			'booking_static_url' 	=> self::maybe_fix_protocol( $booking_static_url ),
			'nonce'					=> wp_create_nonce( 'wp_rest' ),
			'public_nonce'					=> TD_API_Model::create_nonce( 'wp_rest' ),
			'home_url' => self::maybe_fix_www_prefix( self::maybe_fix_protocol( home_url() ) ),
			'site_url' => self::maybe_fix_www_prefix( self::maybe_fix_protocol( site_url() ) ),
			'network_site_url' => self::maybe_fix_www_prefix( self::maybe_fix_protocol( network_site_url() ) ),
			'admin_url' => self::maybe_fix_www_prefix( self::maybe_fix_protocol( admin_url() ) ),
			'site_icon_url' => self::maybe_fix_www_prefix( self::maybe_fix_protocol( get_site_icon_url() ) ),
			'locale' => SSA_Translation::get_locale(),
			'locale_fjy' => SSA_Utils::php_to_moment_format( 'F j, Y' ),
			'locale_gia' => SSA_Utils::php_to_moment_format( 'g:i a' ),
			'locale_fjygia' => SSA_Utils::php_to_moment_format( 'F j, Y g:i a' ),
		);

		$api_array['embed_url'] = $api_array['root'] . '/embed-inner';

		$user_array = array();
		$user_array['capabilities'] = $this->plugin->capabilities->current_user_all_caps();
		$user_array['user_id'] = get_current_user_id();
		$user_array['staff_id'] = 0;
		if ( ! empty( $user_array['user_id'] ) && $this->plugin->settings_installed->is_enabled( 'staff' ) ) {
			$staff = $this->plugin->staff_model->find_by_user_id( $user_array['user_id'] );
			if ( empty( $staff['id'] ) ) {
				$user_array['staff_id'] = '';
			} else {
				$user_array['staff_id'] = $staff['id'];
			}
		}

		$user_array['guides_support'] = $this->get_guides_edition_support();

		return array(
			'api'  => $api_array,
			'user' => $user_array,
		);
	}

	public function fix_bug_from_aggressive_woocommerce_cookie( $uid, $action ) {
		if ( 'wp_rest' === $action ) {
			if ( ! is_user_logged_in() ) {
				return 0;
			}
		}

		return $uid;
	}

	public function output_ssa_variable() {
		$plugin = $this->plugin;
		$script = "var ssa = " . wp_json_encode( $this->get_api_vars() ) . ';';
		echo $script;
	}

	/**
	 * Check with version of the plugin is running, and returns a list of ssa-edition terms based on that.
	 *
	 * @since 4.9.5
	 *
	 * @param boolean $use_slug Whether to use the slug or the ID of the term.
	 *
	 * @return array
	 */
	public function get_guides_edition_support( $use_slug = false ) {
		$version_num = (int) substr( $this->plugin->version, 0, 1 );

		// if $version_num is bigger than 4, then it means that it's a local version of the plugin. Default to business edition.
		if ( $version_num > 4 ) {
			$version_num = 4;
		}
		$version_index = $version_num - 1;
		$version_map   = array(
			'free'     => 186,
			'plus'     => 187,
			'pro'      => 188,
			'business' => 189,
		);

		$version_map = $use_slug ? array_keys( $version_map ) : array_values( $version_map );

		return $version_map[ $version_index ];
	}

}
