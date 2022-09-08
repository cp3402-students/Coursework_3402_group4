<?php
/**
 * Simply Schedule Appointments Capabilities.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Capabilities.
 *
 * @since 0.0.3
 */
class SSA_Capabilities {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

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
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'maybe_install_roles' ) );

		add_filter( 'user_has_cap', array( 'SSA_Capabilities', 'user_has_cap' ), 10, 3 );
	}

	public function init() {
		// Members plugin integration. Adding Simply Schedule Appointments roles to the checkbox list
		if ( $this->has_members_plugin() ) {
			add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 20 );
			add_action( 'members_register_caps', array( $this, 'members_register_caps' ) );
		}
	}

	public function maybe_install_roles() {
		global $wp_roles;

		if( ! is_object( $wp_roles ) ) {
			return;
		}

		if( empty( $wp_roles->roles ) || ! array_key_exists( 'team_member', $wp_roles->roles ) ) {
			$this->add_roles();
		}
	}

	public function add_roles() {
		add_role( 'team_member', __( 'Team Member', 'simply-schedule-appointments' ), array(
			'read'                   => true,
			'ssa_manage_appointments' => true,
			'ssa_manage_staff_blackout_dates' => true,
		) );
	}

	public function remove_roles() {
		remove_role( 'team_member' );
	}

	/**
	 * Determines if the 3rd party Members plugin is active.
	 *
	 * @since  Unknown
	 *
	 * @param string $version Minimum version number of Members plugin to check for.
	 *
	 * @return boolean True if the Members plugin is active. False otherwise.
	 */
	public static function has_members_plugin( $min_version = '2.0' ) {
		if ( version_compare( $min_version, '2.0', '>=' ) ) {
			return function_exists( 'members_register_cap_group' );
		}

		return false;
	}

	public static function members_register_caps() {
		$caps = self::get_all_caps();

		foreach ( $caps as $cap => $label ) {
			members_register_cap(
				$cap,
				array(
					'label' => $label,
					'group' => 'ssa',
				)
			);
		}
	}

	// called by Member plugin filter. Provides the plugin with list of SSA capabilities
	public static function filter_members_get_capabilities( $caps ) {
		return array_merge( $caps, self::get_all_caps() );
	}

	/**
	 * Register the Simply Schedule Appointments capabilities group with the Members plugin.
	 *
	 * @since  2.4
	 * @access public
	 */
	public static function members_register_cap_group() {
		members_register_cap_group(
			'ssa',
			array(
				'label' => esc_html__( 'Simply Schedule Appointments', 'simply-schedule-appointments' ),
				'icon'  => 'dashicons-calendar',
				'caps'  => array(),
			)
		);
	}

	public static function get_all_cap_slugs() {
		return array_keys( self::get_all_caps() );
	}

	public static function get_all_caps() {
		return array(
			'ssa_manage_site_settings' => __( 'Manage SSA Settings', 'simply-schedule-appointments' ),
			'ssa_manage_staff' => __( 'Manage Staff Members', 'simply-schedule-appointments' ),
			'ssa_manage_appointment_types' => __( 'Manage Appointment Types', 'simply-schedule-appointments' ),
			'ssa_manage_appointments' => __( 'Manage Appointments', 'simply-schedule-appointments' ),
			'ssa_manage_others_appointments' => __( 'Manage Others\' (Staff) Appointments', 'simply-schedule-appointments' ),
			'ssa_manage_staff_blackout_dates' => __( 'Manage Blackout Dates', 'simply-schedule-appointments' ),
		);
	}

	public static function user_has_cap( $all_caps, $cap, $args ) {
		$capability = SSA_Utils::array_key( $cap, 0 );
		if ( strpos( $capability, 'ssa_' ) !== 0 ) {
			return $all_caps;
		}

		if ( ! empty( $all_caps['ssa_full_access'] ) ) {
			$all_caps[$capability] = true;
			return $all_caps;
		}

		$ssa_caps = self::get_all_caps();
		if ( ! in_array( $capability, array_keys( $ssa_caps ) ) && $capability !== 'ssa_full_access' ) {
			return $all_caps;
		}

		if ( ! self::has_members_plugin() ) {
			//give full access to administrators if the members plugin is not installed
			if ( current_user_can( 'administrator' ) || is_super_admin() ) {
				$all_caps['ssa_full_access'] = true;
				$all_caps[$capability] = true;
			}
		} else if ( current_user_can( 'administrator' ) || is_super_admin() ) {
			//checking if user has any SSA permission.
			$has_ssa_cap = false;
			foreach ( $ssa_caps as $ssa_cap ) {
				if ( SSA_Utils::array_key( $all_caps, $ssa_cap ) ) {
					$has_ssa_cap = true;
				}
			}

			if ( ! $has_ssa_cap ) {
				//give full access to administrators if none of the SSA permissions are active by the Members plugin
				$all_caps['ssa_full_access'] = true;
				$all_caps[$capability] = true;
			}
		}

		return $all_caps;
	}

	public static function current_user_all_caps() {
		return self::current_user_can_all( self::get_all_cap_slugs() );
	}

	public static function current_user_can_any( $caps ) {
		return self::user_can_any( get_current_user_id(), $caps );
	}

	public static function current_user_can_which( $caps ) {
		return self::user_can_which( get_current_user_id(), $caps );
	}

	public static function current_user_can_all( $caps ) {
		return self::user_can_all( get_current_user_id(), $caps );
	}

	public static function user_can_any( $user, $caps ) {

		if ( ! is_array( $caps ) ) {
			$has_cap = user_can( $user, $caps ) || user_can( $user, 'ssa_full_access' );

			return $has_cap;
		}

		foreach ( $caps as $cap ) {
			if ( user_can( $user, $cap ) ) {
				return true;
			}
		}

		$has_full_access = user_can( $user, 'ssa_full_access' );

		return $has_full_access;
	}

	public static function user_can_which( $user, $caps ) {

		foreach ( $caps as $cap ) {
			if ( user_can( $user, $cap ) ) {
				return $cap;
			}
		}

		return '';
	}

	public static function user_can_all( $user, $caps ) {
		$allowed_caps = array();
		foreach ( $caps as $cap ) {
			if ( user_can( $user, $cap ) ) {
				$allowed_caps[] = $cap;
			}
		}

		return $allowed_caps;
	}

}
