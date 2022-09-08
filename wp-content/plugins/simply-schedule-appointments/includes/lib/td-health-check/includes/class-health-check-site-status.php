<?php

class TD_Health_Check_Site_Status {
	private $php_min_version_check;
	private $php_supported_version_check;
	private $php_rec_version_check;

	private $mysql_min_version_check;
	private $mysql_rec_version_check;

	public  $mariadb                        = false;
	private $mysql_server_version           = null;
	private $health_check_mysql_rec_version = null;

	public function __construct() {
		$this->init();
	}

	public function init() {
		$this->php_min_version_check       = version_compare( TD_HEALTH_CHECK_PHP_MIN_VERSION, PHP_VERSION, '<=' );
		$this->php_supported_version_check = version_compare( TD_HEALTH_CHECK_PHP_SUPPORTED_VERSION, PHP_VERSION, '<=' );
		$this->php_rec_version_check       = version_compare( TD_HEALTH_CHECK_PHP_REC_VERSION, PHP_VERSION, '<=' );

		$this->prepare_sql_data();
	}

	private function prepare_sql_data() {
		global $wpdb;

		if ( method_exists( $wpdb, 'db_version' ) ) {
			if ( $wpdb->use_mysqli ) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info
				$mysql_server_type = mysqli_get_server_info( $wpdb->dbh );
			} else {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_server_info
				$mysql_server_type = mysql_get_server_info( $wpdb->dbh );
			}

			$this->mysql_server_version = $wpdb->get_var( 'SELECT VERSION()' );
		}

		$this->health_check_mysql_rec_version = TD_HEALTH_CHECK_MYSQL_REC_VERSION;

		if ( stristr( $mysql_server_type, 'mariadb' ) ) {
			$this->mariadb                        = true;
			$this->health_check_mysql_rec_version = '10.0';
		}

		$this->mysql_min_version_check = version_compare( TD_HEALTH_CHECK_MYSQL_MIN_VERSION, $this->mysql_server_version, '<=' );
		$this->mysql_rec_version_check = version_compare( $this->health_check_mysql_rec_version, $this->mysql_server_version, '<=' );
	}

	public function check_wp_version_check_exists() {
		if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'ssa_manage_site_settings' ) || ! isset( $_GET['health-check-test-wp_version_check'] ) ) {
			return;
		}

		echo ( has_filter( 'wp_version_check', 'wp_version_check' ) ? 'yes' : 'no' );

		die();
	}

	public function site_status() {
		$function = sprintf(
			'test_%s',
			$_POST['feature']
		);

		if ( ! method_exists( $this, $function ) || ! is_callable( array( $this, $function ) ) ) {
			die();
		}

		$call = call_user_func( array( $this, $function ) );

		die();
	}

	public function test_wordpress_version() {
		if ( ! function_exists( 'get_core_updates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$core_current_version = get_bloginfo( 'version' );
		$core_updates         = get_core_updates();

		if ( ! is_array( $core_updates ) ) {
			return array(
							'status' => 'error',
							'value' => '--',
							'notices' => array(
								sprintf(
								// translators: %1$s: Your current version of WordPress. %2$s The latest version of WordPress available.
									esc_html__( 'We were unable to check if any new versions are available' ),
									$core_current_version,
									$update->version
								)
							),
						);
		} else {
			foreach ( $core_updates as $core => $update ) {
				if ( 'upgrade' === $update->response ) {
					$current_version = explode( '.', $core_current_version );
					$new_version     = explode( '.', $update->version );

					$current_major = $current_version[0] . '.' . $current_version[1];
					$new_major     = $new_version[0] . '.' . $new_version[1];
					if ( $current_major !== $new_major ) {
						// This is a major version mismatch.
						return array(
							'status' => 'warning',
							'value' => $core_current_version,
							'notices' => array(
								sprintf(
								// translators: %1$s: Your current version of WordPress. %2$s The latest version of WordPress available.
									esc_html__( '%1$s ( Latest version: %2$s )', 'simply-schedule-appointments' ),
									$core_current_version,
									$update->version
								)
							),
						);
					} else {
						// This is a minor version, sometimes considered more critical.
						return array(
							'status' => 'error',
							'value' => $core_current_version,
							'notices' => array(
								sprintf(
								// translators: %1$s: Your current version of WordPress. %2$s The latest version of WordPress available.
									esc_html__( '%1$s ( Latest version: %2$s ) - We strongly urge you to update, as minor updates are often security related.', 'simply-schedule-appointments' ),
									$core_current_version,
									$update->version
								)
							),
						);
					}
				} else {
					return array(
						'status' => 'good',
						'value' => $core_current_version,
						'notices' => array(
							sprintf(
							// translators: %1$s: Your current version of WordPress.
								esc_html__( 'You are running WordPress %1$s.', 'simply-schedule-appointments' ),
								$core_current_version
							)
						),
					);
				}
			}
		}

		return array(
			'status' => 'warning',
			'value' => '--',
			'notices' => array(
				__( 'We were unable to check if any new versions are available' ),
			),
		);
	}

	/**
	 * Check if the user is currently in Troubleshooting Mode or not.
	 *
	 * @return bool
	 */
	public function is_troubleshooting() {
		// Check if a session cookie to disable plugins has been set.
		if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
			$_GET['health-check-disable-plugin-hash'] = $_COOKIE['health-check-disable-plugins'];
		}

		// If the disable hash isn't set, no need to interact with things.
		if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
			return false;
		}

		$disable_hash = get_option( 'health-check-disable-plugin-hash', null );

		if ( empty( $disable_hash ) ) {
			return false;
		}

		// If the plugin hash is not valid, we also break out
		if ( $disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
			return false;
		}

		return true;
	}

	public function test_plugin_version() {
		$plugins        = get_plugins();
		$plugin_updates = get_plugin_updates();

		$show_unused_plugins  = true;
		$plugins_have_updates = false;
		$plugins_active       = 0;
		$plugins_total        = 0;
		$plugins_needs_update = 0;

		if ( $this->is_troubleshooting() ) {
			$show_unused_plugins = false;
		}

		foreach ( $plugins as $plugin_path => $plugin ) {
			$plugins_total++;

			if ( is_plugin_active( $plugin_path ) ) {
				$plugins_active++;
			}

			$plugin_version = $plugin['Version'];

			if ( array_key_exists( $plugin_path, $plugin_updates ) ) {
				$plugins_needs_update++;
				$plugins_have_updates = true;
			}
		}

		echo '<ul>';

		if ( $plugins_needs_update > 0 ) {
			printf(
				'<li><span class="error"></span> %s',
				sprintf(
					// translators: %d: The amount of outdated plugins.
					esc_html( _n(
						'Your site has %d plugin waiting to be updated.',
						'Your site has %d plugins waiting to be updated.',
						$plugins_needs_update,
						'health-check'
					) ),
					$plugins_needs_update
				)
			);
		} else {
			printf(
				'<li><span class="good"></span> %s',
				sprintf(
					// translators: %d: The amount of plugins.
					esc_html( _n(
						'Your site has %d active plugin, and it is up to date.',
						'Your site has %d active plugins, and they are all up to date.',
						$plugins_total,
						'health-check'
					) ),
					$plugins_total
				)
			);
		}

		if ( ( $plugins_total > $plugins_active ) && $show_unused_plugins ) {
			$unused_plugins = $plugins_total - $plugins_active;
			printf(
				'<li><span class="warning"></span> %s',
				sprintf(
					// translators: %d: The amount of inactive plugins.
					esc_html( _n(
						'Your site has %d inactive plugin, it is recommended to remove any unused plugins to enhance your site security.',
						'Your site has %d inactive plugins, it is recommended to remove any unused plugins to enhance your site security.',
						$unused_plugins,
						'health-check'
					) ),
					$unused_plugins
				)
			);
		}

		echo '</ul>';
	}

	/**
	 * Check if the current SSA plugin version is up to date.
	 *
	 * @since 4.8.9
	 *
	 * @return array
	 */
	public function test_ssa_plugin_version() {
		if ( ! function_exists( 'get_plugin_updates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}

		$current_version = ssa()->get_current_version();
		$update_version  = $current_version;
		$plugin_updates  = get_plugin_updates();
		$status          = 'good';
		$value           = Simply_Schedule_Appointments::VERSION;
		$message         = sprintf(
			// translators: SSA Plugin version.
			__( 'Your version of Simply Schedule Appointments (v%s) is up to date.', 'simply-schedule-appointments' ),
			$current_version,
			get_admin_url( null, 'update-core.php' )
		);

		// if the SSA plugin is included on the array of possible updates, then let's check the update version.
		if ( array_key_exists( SSA_PLUGIN_BASENAME, $plugin_updates ) ) {
			if ( isset( $plugin_updates[ SSA_PLUGIN_BASENAME ]->update->new_version ) ) {
				$update_version = $plugin_updates[ SSA_PLUGIN_BASENAME ]->update->new_version;
			}
		}

		// if current version is lower than the version found on WP.org, then the user should update.
		if ( version_compare( $current_version, $update_version, '<' ) ) {
			$status  = 'warning';
			$value   = false;
			$message = sprintf(
				// translators: 1: SSA Plugin version 2: WordPress's Update screen.
				__( 'Your version of Simply Schedule Appointments, (v%1$s) is out of date. <a href="%2$s">Upgrade to the latest version (v%3$s)</a>', 'simply-schedule-appointments' ),
				$current_version,
				get_admin_url( null, 'update-core.php' ),
				$update_version
			);

		}

		return array(
			'status'  => $status,
			'value'   => $value,
			'notices' => array( $message ),
		);
	}

	public function test_theme_version() {
		$theme_updates = get_theme_updates();

		$themes_total        = 0;
		$themes_need_updates = 0;
		$themes_inactive     = 0;

		// This value is changed dduring processing to determine how many themes are considered a reasonable amount.
		$allowed_theme_count = 1;

		$has_default_theme  = false;
		$has_unused_themes  = false;
		$show_unused_themes = true;

		if ( $this->is_troubleshooting() ) {
			$show_unused_themes = false;
		}

		// Populate a list of all themes available in the install.
		$all_themes   = wp_get_themes();
		$active_theme = wp_get_theme();

		foreach ( $all_themes as $theme_slug => $theme ) {
			$themes_total++;

			if ( WP_DEFAULT_THEME === $theme_slug ) {
				$has_default_theme = true;
			}

			if ( array_key_exists( $theme_slug, $theme_updates ) ) {
				$themes_need_updates++;
			}
		}

		// If this is a child theme, increase the allowed theme count by one, to account for the parent.
		if ( $active_theme->parent() ) {
			$allowed_theme_count++;
		}

		// If there's a default theme installed, we count that as allowed as well.
		if ( $has_default_theme ) {
			$allowed_theme_count++;
		}

		if ( $themes_total > $allowed_theme_count ) {
			$has_unused_themes = true;
			$themes_inactive   = ( $themes_total - $allowed_theme_count );
		}

		echo '<ul>';

		if ( $themes_need_updates > 0 ) {
			printf(
				'<li><span class="error"></span> %s',
				sprintf(
					// translators: %d: The amount of outdated themes.
					esc_html( _n(
						'Your site has %d theme waiting to be updated.',
						'Your site has %d themes waiting to be updated.',
						$themes_need_updates,
						'health-check'
					) ),
					$themes_need_updates
				)
			);
		} else {
			printf(
				'<li><span class="good"></span> %s',
				sprintf(
					// translators: %d: The amount of themes.
					esc_html( _n(
						'Your site has %d installed theme, and it is up to date.',
						'Your site has %d installed themes, and they are all up to date.',
						$themes_total,
						'health-check'
					) ),
					$themes_total
				)
			);
		}

		if ( $has_unused_themes && $show_unused_themes ) {

			// This is a child theme, so we want to be a bit more explicit in our messages.
			if ( $active_theme->parent() ) {
				printf(
					'<li><span class="warning"></span> %s',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme. %4$s: The active themes parent theme.
						esc_html( _n(
							'Your site has %1$d inactive theme. To enhance your sites security it is recommended to remove any unused themes. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, the parent theme.',
							'Your site has %1$d inactive themes. To enhance your sites security it is recommended to remove any unused themes. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, the parent theme.',
							$themes_inactive,
							'health-check'
						) ),
						$themes_inactive,
						WP_DEFAULT_THEME,
						$active_theme->name,
						$active_theme->parent()->name
					)
				);

			} else {
				printf(
					'<li><span class="warning"></span> %s',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme.
						esc_html( _n(
							'Your site has %1$d inactive theme, other than %2$s, the default WordPress theme, and %3$s, your active theme. It is recommended to remove any unused themes to enhance your sites security.',
							'Your site has %1$d inactive themes, other than %2$s, the default WordPress theme, and %3$s, your active theme. It is recommended to remove any unused themes to enhance your sites security.',
							$themes_inactive,
							'health-check'
						) ),
						$themes_inactive,
						WP_DEFAULT_THEME,
						$active_theme->name
					)
				);

			}
		}

		if ( ! $has_default_theme ) {
			printf(
				'<li><span class="warning"></span> %s',
				esc_html__( 'Your site does not have a default theme, default themes are used by WordPress automatically if anything is wrong with your normal theme.', 'simply-schedule-appointments' )
			);
		}

		echo '</ul>';
	}

	public function test_php_version() {
		$status = 'good';
		$notice = array();

		if ( ! $this->php_min_version_check ) {
			$status   = 'error';
			$notice[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'simply-schedule-appointments' )
				),
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version. %3$s: Minimum PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates and is not supported by WordPress. You should contact your host for an upgrade, WordPress recommends using PHP version %2$s, but will work with version %3$s or newer.', 'simply-schedule-appointments' ),
					PHP_VERSION,
					TD_HEALTH_CHECK_PHP_REC_VERSION,
					TD_HEALTH_CHECK_PHP_MIN_VERSION
				)
			);
		} elseif ( ! $this->php_supported_version_check ) {
			$status   = 'warning';
			$notice[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'simply-schedule-appointments' )
				),
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates. You should contact your host for an upgrade, WordPress recommends using PHP version %2$s.', 'simply-schedule-appointments' ),
					PHP_VERSION,
					TD_HEALTH_CHECK_PHP_REC_VERSION
				)
			);
		} elseif ( ! $this->php_rec_version_check ) {
			$status   = 'good';
			$notice[] = sprintf(
				// translators: %s: Recommended PHP version
				esc_html__( 'Your version of PHP, %s is current and meets all requirements. For even better performance, you can upgrade to PHP %s or higher.', 'simply-schedule-appointments' ),
				PHP_VERSION,
				TD_HEALTH_CHECK_PHP_REC_VERSION
			);
		} else {
			$status = 'good';
			$notice[] = sprintf(
				// translators: %s: Recommended PHP version
				esc_html__( 'Your version of PHP, %s is current and meets all requirements', 'simply-schedule-appointments' ),
				PHP_VERSION,
				TD_HEALTH_CHECK_PHP_REC_VERSION
			);
		}

		return array(
			'status' => $status,
			'value' => PHP_VERSION,
			'notices' => $notice,
		);

	}

	public function test_json_extension() {
		$json_check = TD_Health_Check::json_check();

		$status = 'good';
		$notice = array();

		if ( ! $json_check ) {
			return array(
				'status' => 'error',
				'value' => false,
				'notices' => array(
					esc_html__( 'The PHP install on your server has the JSON extension disabled and is therefore not compatible with WordPress 3.2 or newer.', 'simply-schedule-appointments' )
				),
			);
		} else {
			return array(
				'status' => 'good',
				'value' => true,
				'notices' => array(
					esc_html__( 'Your PHP install supports JSON.', 'simply-schedule-appointments' )
				),
			);
		}
	}

	public function test_sql_server() {
		$status = 'good';
		$notice = array();

		$db_dropin = file_exists( WP_CONTENT_DIR . '/db.php' );

		if ( ! $this->mysql_rec_version_check ) {
			$status   = 'warning';
			$notice[] = sprintf(
				// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server recommended version number.
				esc_html__( 'For performance and security reasons, we strongly recommend running %1$s version %2$s or higher.', 'simply-schedule-appointments' ),
				( $this->mariadb ? 'MariaDB' : 'MySQL' ),
				$this->health_check_mysql_rec_version
			);
		}

		if ( ! $this->mysql_min_version_check ) {
			$status   = 'error';
			$notice[] = sprintf(
				// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server minimum version number.
				esc_html__( 'WordPress 3.2+ requires %1$s version %2$s or higher.', 'simply-schedule-appointments' ),
				( $this->mariadb ? 'MariaDB' : 'MySQL' ),
				TD_HEALTH_CHECK_MYSQL_MIN_VERSION
			);
		}

		if ( $db_dropin ) {
			// translators: %s: The database engine in use (MySQL or MariaDB).
			$notice[] = wp_kses(
				sprintf(
					// translators: %s: The name of the database engine being used.
					__( 'You are using a <code>wp-content/db.php</code> drop-in which might mean that a %s database is not being used.', 'simply-schedule-appointments' ),
					( $this->mariadb ? 'MariaDB' : 'MySQL' )
				),
				array(
					'code' => true,
				)
			);
		}

		return array(
			'status' => $status,
			'value' => $this->mysql_server_version,
			'notices' => array(
				sprintf(
					'%s%s',
					esc_html( $this->mysql_server_version ),
					( ! empty( $notice ) ? '<br> - ' . implode( '<br>', $notice ) : '' )
				)
			)
		);
	}

	public function test_utf8mb4_support() {
		global $wpdb;

		if ( ! $this->mariadb ) {
			if ( version_compare( $this->mysql_server_version, '5.5.3', '<' ) ) {
				return array(
					'status' => 'warning',
					'value' => $this->mysql_server_version,
					'notices' => array(
						sprintf(
							/* translators: %s: Number of version. */
							esc_html__( 'WordPress\' utf8mb4 support requires MySQL version %s or greater', 'simply-schedule-appointments' ),
							'5.5.3'
						)
					),
				);
			} else {
				return array(
					'status' => 'good',
					'value' => $this->mysql_server_version,
					'notices' => array(
						esc_html__( 'Your MySQL version supports utf8mb4', 'simply-schedule-appointments' )
					),
				);
			}
		} else { // MariaDB introduced utf8mb4 support in 5.5.0
			if ( version_compare( $this->mysql_server_version, '5.5.0', '<' ) ) {
				return array(
					'status' => 'warning',
					'value' => $this->mysql_server_version,
					'notices' => array(
						sprintf(
							/* translators: %s: Number of version. */
							esc_html__( 'WordPress\' utf8mb4 support requires MariaDB version %s or greater', 'simply-schedule-appointments' ),
							'5.5.0'
						)
					),
				);
			} else {
				return array(
					'status' => 'good',
					'value' => $this->mysql_server_version,
					'notices' => array(
						esc_html__( 'Your MariaDB version supports utf8mb4', 'simply-schedule-appointments' )
					),
				);
			}
		}

		if ( $wpdb->use_mysqli ) {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info
			$mysql_client_version = mysqli_get_client_info();
		} else {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_client_info
			$mysql_client_version = mysql_get_client_info();
		}

		/*
		 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
		 * mysqlnd has supported utf8mb4 since 5.0.9.
		 */
		if ( false !== strpos( $mysql_client_version, 'mysqlnd' ) ) {
			$mysql_client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $mysql_client_version );
			if ( version_compare( $mysql_client_version, '5.0.9', '<' ) ) {
				return array(
					'status' => 'warning',
					'value' => $mysql_client_version,
					'notices' => array(
						sprintf(
							/* translators: %1$s: Name of the library, %2$s: Number of version. */
							__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'simply-schedule-appointments' ),
							'mysqlnd',
							'5.0.9'
						)
					),
				);
			}
		} else {
			if ( version_compare( $mysql_client_version, '5.5.3', '<' ) ) {
				return array(
					'status' => 'warning',
					'value' => $mysql_client_version,
					'notices' => array(
						sprintf(
							/* translators: %1$s: Name of the library, %2$s: Number of version. */
							__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'simply-schedule-appointments' ),
							'libmysql',
							'5.5.3'
						)
					),
				);
			}
		}
	}

	public function test_dotorg_communication() {
		$wp_dotorg = wp_remote_get( 'https://wordpress.org', array(
			'timeout' => 10,
		) );
		if ( ! is_wp_error( $wp_dotorg ) ) {
			return array(
				'status' => 'good',
				'value' => true,
				'notices' => array(
					esc_html__( 'WordPress.org is reachable from your server.', 'simply-schedule-appointments' )
				),
			);
		} else {
			return array(
				'status' => 'error',
				'value' => false,
				'notices' => array(
					sprintf(
					// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
						__( 'Unable to reach WordPress.org at %1$s: %2$s', 'simply-schedule-appointments' ),
						gethostbyname( 'wordpress.org' ),
						$wp_dotorg->get_error_message()
					)
				),
			);
		}
	}

	public function test_https_status() {
		if ( is_ssl() ) {
			$wp_url   = get_bloginfo( 'wpurl' );
			$site_url = get_bloginfo( 'url' );

			if ( 'https' !== substr( $wp_url, 0, 5 ) || 'https' !== substr( $site_url, 0, 5 ) ) {
				return array(
					'status' => 'warning',
					'value' => false,
					'notices' => array(
						sprintf(
						// translators: %s: URL to Settings > General to change options.
							__( 'You are accessing this website using HTTPS, but your <a href="%s">WordPress Address</a> is not set up to use HTTPS by default.', 'simply-schedule-appointments' ),
							esc_url( admin_url( 'options-general.php' ) )
						)
					),
				);
			} else {
				return array(
					'status' => 'good',
					'value' => true,
					'notices' => array(
						esc_html__( 'You are accessing this website using HTTPS.', 'simply-schedule-appointments' )
					),
				);
			}
		} else {
			return array(
					'status' => 'warning',
					'value' => false,
					'notices' => array(
						esc_html__( 'You are not using HTTPS to access this website.', 'simply-schedule-appointments' )
					),
			);
		}
	}

	public function test_ssl_support() {
		$supports_https = wp_http_supports( array( 'ssl' ) );

		if ( $supports_https ) {
			return array(
				'status' => 'good',
				'value' => true,
				'notices' => array(
					esc_html__( 'Your WordPress install can communicate securely with other services.', 'simply-schedule-appointments' )
				),
			);
		} else {
			return array(
				'status' => 'error',
				'value' => false,
				'notices' => array(
					esc_html__( 'Your WordPress install cannot communicate securely with other services. Talk to your web host about OpenSSL support for PHP.', 'simply-schedule-appointments' )
				),
			);
		}
	}

	public function test_scheduled_events() {
		$scheduled_events = new TD_Health_Check_WP_Cron();

		if ( is_wp_error( $scheduled_events->has_missed_cron() ) ) {
			return array(
				'status' => 'error',
				'value' => false,
				'notices' => array(
					esc_html( $scheduled_events->has_missed_cron()->get_error_message() )
				),
			);
		} else {
			if ( $scheduled_events->has_missed_cron() ) {
				return array(
					'status' => 'warning',
					'value' => false,
					'notices' => array(
						sprintf(
						// translators: %s: The name of the failed cron event.
							esc_html__( 'A scheduled event (%s) has failed to run. Your site still works, but this may indicate that notifications and synchronization with 3rd party services may not work as intended.', 'simply-schedule-appointments' ),
							$scheduled_events->last_missed_cron
						)
					),
				);
			} else {
				return array(
					'status' => 'good',
					'value' => true,
					'notices' => array(
						esc_html__( 'No scheduled events have been missed.', 'simply-schedule-appointments' )
					),
				);
			}
		}
	}

	public function test_background_updates() {
		$automatic_updates = new TD_Health_Check_Auto_Updates();
		$tests             = $automatic_updates->run_tests();

		echo '<ul>';

		foreach ( $tests as $test ) {
			printf(
				'<li><span class="%s"></span> %s</li>',
				esc_attr( $test->severity ),
				$test->desc
			);
		}

		echo '</ul>';
	}

	public function test_loopback_requests() {
		$check_loopback = TD_Health_Check_Loopback::can_perform_loopback();

		printf(
			'<span class="%s"></span> %s',
			esc_attr( $check_loopback->status ),
			$check_loopback->message
		);

		if ( 'error' === $check_loopback->status ) {
			echo '<br><button type="button" id="loopback-no-plugins" class="button button-primary">Test without plugins</button>';
		}
	}

	public function test_php_default_timezone() {
		$health_check = WP_Site_Health::get_instance();

		$timezone_check = $health_check->get_test_php_default_timezone();

		return array(
			'status' => $timezone_check['status'] !== 'good' ? 'error' : 'good',
			'value' => $timezone_check['status'] === 'good',
			'notices' => [strip_tags($timezone_check['description'])] ,
		);
	}
}
