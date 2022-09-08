<?php
/**
 * Simply Schedule Appointments Translation.
 *
 * @since   3.2.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Translation.
 *
 * @since 3.2.2
 */
class SSA_Translation {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.2.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	public $programmatic_locale; // if this value is set, it will override everything (necessary for execution of background jobs, setting it for a notification, etc)

	/**
	 * Constructor.
	 *
	 * @since  3.2.2
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
	 * @since  3.2.2
	 */
	public function hooks() {
		add_filter( 'locale', array( $this, 'filter_wp_locale' ), 1000 );
		add_action( 'rest_api_init', array( $this, 'register_fetch_endpoint' ) );
	}

	public function filter_wp_locale( $locale ) {
		$lang = self::get_locale( false );

		if ( empty( $lang ) ) {
			$lang = $locale;
		}

		return $lang;
	}

	public static function is_rtl() {
		if ( ! isset( $_GET['ssa_is_rtl'] ) ) {
			return is_rtl();
		}

		$is_rtl = ( empty( $_GET['ssa_is_rtl'] ) ) ? false : true;
		return $is_rtl;
	}

	public static function get_locale( $return_default = true ) {
		if ( ! empty( ssa()->translation->programmatic_locale ) ) {
			return ssa()->translation->programmatic_locale;
		}
		if ( ! isset( $_GET['ssa_locale'] ) ) {
			if ( empty( $return_default ) ) {
				return; // prevent infinite loop
			}
			
			if ( self::is_rtl() ) {
				return 'en_US';
			}

			$locale = get_locale();
			if ( empty( $locale ) ) {
				$locale = 'en_US';
			}

			return $locale;
		}

		$lang = ( empty( $_GET['ssa_locale'] ) ) ? 'en_US' : esc_attr( $_GET['ssa_locale'] );

		return $lang;
	}

	public function register_fetch_endpoint( $request ) {
		$namespace = 'ssa/v1';
		$base = 'translation';

		register_rest_route( $namespace, '/' . $base . '/fetch', array(
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'fetch_endpoint' ),
				'permission_callback' => array( 'TD_API_Model', 'nonce_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
		) );
	}

	public function fetch_endpoint( $request ) {
		$params = $request->get_params();

		if ( empty( $params['locale'] ) ) {
			$response = array(
				'response_code' => 422,
				'error' => __( 'Missing locale', 'simply-schedule-appointments' ),
				'data' => $data,
			);

			return new WP_REST_Response( $response, 422 );
		}

		$translation_settings = $this->plugin->translation_settings->get();
		$locale = esc_attr( $params['locale'] );

		if ( empty( $translation_settings['locales'] ) ) {
			$translation_settings['locales'] = array();
		}
		if ( empty( $translation_settings['locales'][$locale] ) ) {
			$translation_settings['locales'][$locale] = array();
		}

		$errors = $this->download_translation( $locale );
		if ( empty( $errors ) ) {
			$translation_settings['locales'][$locale]['last_fetched_date'] = gmdate( 'Y-m-d H:i:s' );
			$this->plugin->translation_settings->update( $translation_settings );
		}

		$data = array( 'locale' => $locale );
		$response = array(
			'response_code' => 200,
			'error' => $errors,
			'data' => $data,
		);

		return new WP_REST_Response( $response, 200 );
	}

	public function download_translation( $locale ) {
		$errors = array();
		foreach ( array( 'po', 'mo' ) as $type ){
			$import = $this->import( 'wp-plugins/simply-schedule-appointments', $locale, $type );

			if( is_wp_error( $import ) ) {
				$errors[] = array(
					'status'  => 'error',
					'content' => $import->get_error_message()
				);
			}
		}

		return $errors;
	}

	/**
	 * Import translation file.
	 *
	 * @param string $project   File project
	 * @param string $locale    File locale
	 * @param string $format    File format
	 * @return null|WP_Error    File path to get source.
	 */
	function import( $project_slug, $locale = '', $format = 'mo', $variant = 'default' ) {
		if ( empty( $locale ) ) {
			$locale = get_user_locale();
		}

		preg_match("/wp-(.*)/", $project_slug, $project_path);

		$source = $this->get_source_path( $project_slug, $locale, $format, 'dev', $variant );
		$target = sprintf(
			'%s-%s.%s',
			$project_path[1],
			$locale,
			$format
		);
		$response = wp_remote_get( $source );

		if ( !is_array( $response )
			|| $response['headers']['content-type'] !== 'application/octet-stream' ) {
			return new WP_Error( 'ssa-translation-source-not-found', sprintf(
				__( 'Cannot get source file: %s', 'simply-schedule-appointments' ),
				'<b>' . esc_html( $source ) . '</b>'
			) );
		}
		else {
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			// $creds = request_filesystem_credentials( '' );
			if ( ! WP_Filesystem() ) {
				request_filesystem_credentials($url, '', true, false, null);
			}

			global $wp_filesystem;
			if( ! $wp_filesystem->is_dir( WP_LANG_DIR ) ) {
				$wp_filesystem->mkdir( WP_LANG_DIR );
			}
			if( ! $wp_filesystem->is_dir( WP_LANG_DIR . '/plugins' ) ) {
				$wp_filesystem->mkdir( WP_LANG_DIR . '/plugins' );
			}

			$wpfs_response = $wp_filesystem->put_contents(
				WP_LANG_DIR . '/' . $target,
				$response['body'],
				FS_CHMOD_FILE // predefined mode settings for WP files
			);
		}
	}

	/**
	 * Generate a file path to get translation file.
	 *
	 * @param string $project   File project
	 * @param string $locale    File locale
	 * @param string $type      File type
	 * @param string $format    File format
	 * @return $path            File path to get source.
	 */
	function get_source_path( $project, $locale, $format = 'mo', $type = 'dev', $variant = 'default' ) {
		$locale = SSA_Locales::by_field( 'wp_locale', $locale );

		if ( isset( $locale->variant ) && ! empty( $locale->variant ) ) {
			$variant = $locale->variant;
			$locale->slug = str_replace( '_'.$variant, '', $locale->slug );
		}

		$path = sprintf( 'https://translate.wordpress.org/projects/%1$s/%2$s/%3$s/%4$s/export-translations?filters[status]=current_or_waiting_or_fuzzy',
			$project,
			$type,
			$locale->slug,
			$variant
		);

		$path = ( $format == 'po' ) ? $path : $path . '&format=' . $format;
		$path = esc_url_raw( $path );
		return $path;
	}
}
