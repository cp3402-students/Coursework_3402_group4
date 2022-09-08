<?php
/**
 * Simply Schedule Appointments Templates.
 *
 * @since   2.0.3
 * @package Simply_Schedule_Appointments
 */


/**
 * Simply Schedule Appointments Templates.
 *
 * @since 2.0.3
 */
class SSA_Templates {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		
		SSA_Utils::define( 'SSA_TEMPLATE_DEBUG_MODE', false );

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.3
	 */
	public function hooks() {
		add_filter( 'ssa/templates/get_template_vars', array( $this, 'add_global_template_vars' ), 5, 2 );
		add_filter( 'ssa/templates/get_template_vars', array( $this, 'add_appointment_template_vars' ), 5, 2 );
		add_filter( 'ssa/templates/get_template_vars', array( $this, 'add_appointment_calendar_link_vars' ), 5, 2 );
		add_filter( 'ssa/templates/get_template_vars', array( $this, 'add_example_appointment_type_template_vars' ), 5, 2 );
	}

	public function get_template_vars( $template, $vars = array() ) {
		$vars = apply_filters( 'ssa/templates/get_template_vars', $vars, $template );

		return $vars;
	}

	public function add_global_template_vars( $vars, $template ) {
		if ( empty( $vars['Global'] ) ) {
			$vars['Global'] = array();
		}

		$global_settings = $this->plugin->settings->get()['global'];
		$vars['Global'] = array_merge( $vars['Global'], array(
			'site_url' => site_url(),
			'home_url' => home_url(),
			'company_name' => get_bloginfo('name')
		), $global_settings );

		return $vars;
	}

	/**
	 * Add appointment template vars.
	 *
	 * @since  5.5.3
	 *
	 * @param  array  $vars Template vars.
	 * @param  string $template Template name.
	 *
	 * @return array
	 */
	public function add_appointment_calendar_link_vars( $vars, $template ) {
		if ( empty( $vars['appointment_id'] ) ) {
			return $vars;
		}

		if ( empty( $vars['Appointment'] ) ) {
			$vars['Appointment'] = array();
		}
		remove_filter( 'ssa/templates/get_template_vars', array( ssa()->templates, 'add_appointment_calendar_link_vars' ), 5, 2 );
		$appointment_obj = new SSA_Appointment_Object( (int) $vars['appointment_id'] );
		// Add to calendar link.
		$vars['add_to_calendar_link']         = array();
		$vars['add_to_calendar_link']['ics']  = $appointment_obj->get_ics_download_url( 'customer' );
		$vars['add_to_calendar_link']['gcal'] = $appointment_obj->get_gcal_add_link( 'customer' );

		// Get public token for ics download.
		$public_token = ssa()->appointment_model->get_id_token( $vars['appointment_id'] );

		$vars['add_to_calendar_link']['ics'] = add_query_arg(
			array(
				'token' => $public_token,
			),
			$vars['add_to_calendar_link']['ics']
		);

		add_filter( 'ssa/templates/get_template_vars', array( ssa()->templates, 'add_appointment_calendar_link_vars' ), 5, 2 );

		return $vars;
	}

	public function add_example_appointment_type_template_vars( $vars, $template ) {
		if ( empty( $vars['example_appointment_type_id'] ) ) {
			return $vars;
		}

		if ( empty( $vars['Appointment'] ) ) {
			$vars['Appointment'] = array();
		}

		$settings = $this->plugin->settings->get();
		$appointment_type_object = new SSA_Appointment_Type_Object( (int)$vars['example_appointment_type_id'] );
		
		$vars['Appointment']['AppointmentType'] = $appointment_type_object->data;
		if ( isset( $vars['Appointment']['AppointmentType']['availability'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['availability'] );
		}
		if ( isset( $vars['Appointment']['AppointmentType']['notifications'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['notifications'] );
		}
		if ( isset( $vars['Appointment']['AppointmentType']['google_calendars_availability'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['google_calendars_availability'] );
		}
		if ( !empty( $vars['Appointment']['AppointmentType']['custom_customer_information'] ) ) {
			$vars['Appointment']['AppointmentType']['customer_information'] = $vars['Appointment']['AppointmentType']['custom_customer_information'];
		}
		if ( isset( $vars['Appointment']['AppointmentType']['custom_customer_information'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['custom_customer_information'] );
		}
		if ( isset( $vars['Author'] ) ) {
			$vars['Customer'] = $vars['Author'];
			unset( $vars['Author'] );
		}

		$vars['Appointment']['customer_information'] = array();
		$vars['Appointment']['customer_information_strings'] = array();
		foreach ($vars['Appointment']['AppointmentType']['customer_information'] as $key => $value) {
			$vars['Appointment']['customer_information'][$value['field']] = __( '[customer info will go here...]', 'simply-schedule-appointments' );
			$vars['Appointment']['customer_information_strings'][$value['field']] = __( '[customer info will go here...]', 'simply-schedule-appointments' );
		}
		$vars['Appointment']['start_date'] = gmdate( 'Y-m-d H:i:s' );
		$vars['Appointment']['end_date'] = gmdate( 'Y-m-d H:i:s' );
		$vars['Appointment']['status'] = 'booked';
		$vars['Appointment']['customer_id'] = 0;
		$vars['Appointment']['customer_timezone'] = 'UTC';
		$vars['Appointment']['appointment_type_id'] = $vars['example_appointment_type_id'];

		$vars['admin_email'] = $settings['global']['admin_email'];
		$vars['customer_email'] = $vars['Appointment']['customer_information']['Email'];
		$vars['customer_name'] = $vars['Appointment']['customer_information']['Name'];
		$vars['attendees_list'] = __( '[List of attendees will go here...]', 'simply-schedule-appointments' );
		$vars['instructions'] = $vars['Appointment']['AppointmentType']['instructions'];
		$vars['location'] = __( '[Event location info will go here...]', 'simply-schedule-appointments' );

		// Refund policy
		$vars['refund_policy'] = __('[Refund Policy will go here...]', 'simply-schedule-appointments');

		// Add to calendar link.
		$vars['add_to_calendar_link'] = array(
			'ics'  => __( '[Link to save .ics file will go here...]', 'simply-schedule-appointments' ),
			'gcal' => __( '[Link to add to calendar will go here...]', 'simply-schedule-appointments' ),
		);

		return $vars;
	}

	public function add_appointment_template_vars( $vars, $template ) {
		if ( empty( $vars['appointment_id'] ) ) {
			return $vars;
		}

		if ( empty( $vars['Appointment'] ) ) {
			$vars['Appointment'] = array();
		}

		$settings = $this->plugin->settings->get();
		$appointment_obj = new SSA_Appointment_Object( (int)$vars['appointment_id'] );

		$eol = "\r\n";

		$vars['Appointment'] = array_merge( $vars['Appointment'], $appointment_obj->get_data( 1 ) );
		$vars['Appointment']['customer_information_strings'] = array();
		foreach ( $vars['Appointment']['customer_information'] as $key => $value ) {
			if (is_array($value)) {
				$value = implode(', ', $value);
			}

			$value = htmlspecialchars($value);
			$vars['Appointment']['customer_information'][$key] = $value;
			$vars['Appointment']['customer_information_strings'][$key] = $value;
		}

		if ( isset( $vars['Appointment']['AppointmentType']['availability'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['availability'] );
		}
		if ( isset( $vars['Appointment']['AppointmentType']['notifications'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['notifications'] );
		}
		if ( isset( $vars['Appointment']['AppointmentType']['google_calendars_availability'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['google_calendars_availability'] );
		}
		if ( !empty( $vars['Appointment']['AppointmentType']['custom_customer_information'] ) ) {
			$vars['Appointment']['AppointmentType']['customer_information'] = $vars['Appointment']['AppointmentType']['custom_customer_information'];
		}
		if ( isset( $vars['Appointment']['AppointmentType']['custom_customer_information'] ) ) {
			unset( $vars['Appointment']['AppointmentType']['custom_customer_information'] );
		}

		if ( isset( $vars['Author'] ) ) {
			$vars['Customer'] = $vars['Author'];
			unset( $vars['Author'] );
		}

		$vars['admin_email'] = $settings['global']['admin_email'];
		$vars['customer_email'] = $vars['Appointment']['customer_information']['Email'];
		$vars['customer_name'] = $vars['Appointment']['customer_information']['Name'];

		/* BEGIN customer_start_date */
		if ( ! empty( $vars['Appointment']['customer_locale'] ) ) {
			$this->plugin->translation->programmatic_locale = esc_attr($vars['Appointment']['customer_locale']);
		}
		$format = SSA_Utils::localize_default_date_strings( 'F j, Y g:i a' ) . ' (T)';

		if ( empty( $vars['Appointment']['customer_timezone'] ) ) {
			$customer_timezone = $appointment_obj->get_date_timezone();
			$vars['Appointment']['customer_timezone'] = $customer_timezone->getName();
		} else if ( false !== strpos( $vars['Appointment']['customer_timezone'], 'Etc/' ) ) {
			$customer_timezone = $appointment_obj->get_date_timezone();
			$vars['Appointment']['customer_timezone'] = $customer_timezone->getName();
		} else {
			$customer_timezone = new DateTimeZone( $vars['Appointment']['customer_timezone'] );
		}

		$vars['Appointment']['customer_start_date'] = ssa_datetime( $vars['Appointment']['start_date'] )->setTimezone( $customer_timezone )->format( $format );
		$vars['Appointment']['customer_start_date'] = SSA_Utils::translate_formatted_date( $vars['Appointment']['customer_start_date'] );
		$this->plugin->translation->programmatic_locale = null;
		/* END customer_start_date */

		/* BEGIN business_start_date */
		$format = SSA_Utils::localize_default_date_strings( 'F j, Y g:i a' ) . ' (T)';
		$vars['Appointment']['business_start_date'] = ssa_datetime( $vars['Appointment']['start_date'] );

		$vars['Appointment']['business_start_date'] = $this->plugin->utils->get_datetime_as_local_datetime( $vars['Appointment']['business_start_date'], $vars['Appointment']['appointment_type_id'] )->format( $format );
		$vars['Appointment']['business_start_date'] = SSA_Utils::translate_formatted_date( $vars['Appointment']['business_start_date'] );
		/* END business_start_date */

		if ( empty( $vars['Appointment']['customer_timezone'] ) || false !== strpos( $vars['Appointment']['customer_timezone'], 'Etc/' ) ) {
			$vars['Appointment']['customer_timezone'] = $vars['Appointment']['date_timezone'];
		}

		// If Appointment date_timezone is a DateTimeZone object, get the name.
		if ( is_object( $vars['Appointment']['date_timezone'] ) ) {
			$vars['Appointment']['date_timezone'] = $vars['Appointment']['date_timezone']->getName();
		}

		// Include booking page variables
		$meta = $this->plugin->appointment_model->get_metas( (int)$vars['appointment_id'], array( 'booking_url', 'booking_post_id', 'booking_title' ) );

		$vars['booking_url'] = isset( $meta['booking_url'] ) ? $meta['booking_url'] : null;
		$vars['booking_post_id'] = isset( $meta['booking_post_id'] ) ? $meta['booking_post_id'] : null;
		$vars['booking_title'] = isset( $meta['booking_title'] ) ? $meta['booking_title'] : null;
		$vars['location'] = isset($vars['Appointment']['web_meeting_url'] ) ? $vars['Appointment']['web_meeting_url'] : '';

		// List of attendees
		$vars['attendees_list'] = '';
		$appointments = $appointment_obj->query_group_appointments();
		if( $appointments ) {
			foreach ($appointments as $appointment) {
				if ( ! $appointment->is_booked() ) {
					continue;
				}
	
				$vars['attendees_list'] .= $appointment->get_customer_name() . $eol;
			}		
		}

		if ( empty( $vars['instructions'] ) ) {
			$vars['instructions'] = '';
		}
		$vars['instructions'] .= $vars['Appointment']['AppointmentType']['instructions'];

		// Refund policy
		$vars['refund_policy'] = isset($vars['Appointment']['AppointmentType']['payments']) && isset($vars['Appointment']['AppointmentType']['payments']['refund_policy']) ? $vars['Appointment']['AppointmentType']['payments']['refund_policy'] : '';

		// adding empty values for {{team_member_name}}, {{team_member_email}}, {{team_member_phone}} in case they're used without the business edition
		$team_member_keys = array( 'name', 'email', 'phone' );
		foreach ($team_member_keys as $key) {
			$vars['team_member_'.$key] = '';
		}

		return $vars;
	}

	public function render_template_string( $template_string, $vars ) {
		$context = array();

		$loader = new Twig_Loader_Array(
			array(
				'template'   => $template_string,
				'autoescape' => false,
			)
		);

		$twig = new Twig_Environment( $loader );
		$twig->addExtension( new SSA_Twig_Extension() );
		$twig->getExtension( 'Twig_Extension_Core' )->setTimezone( 'UTC' );
		try {
			$rendered_template = $twig->render( 'template', $vars );
		} catch ( Exception $e ) {
			return $e;
		}

		return $rendered_template;
	}



	/**
	 * Get template part.
	 *
	 * SSA_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
	 *
	 * @access public
	 * @param mixed  $slug Template slug.
	 * @param string $name Template name (default: '').
	 * @param boolean $echo  Should echo $output to screen. (default: false).
	 * 
	 * @return string
	 */
	function get_template_part( $slug, $name = '', $echo = false ) {
		ob_start();
		$template = '';

		// Look in yourtheme/slug-name.php and yourtheme/ssa/slug-name.php.
		if ( $name && ! SSA_TEMPLATE_DEBUG_MODE ) {
			$template = locate_template( array( "{$slug}-{$name}.php", $this->plugin->template_subdirectory() . "{$slug}-{$name}.php" ) );
		}

		// Get default slug-name.php.
		if ( ! $template && $name && file_exists( $this->plugin->dir() . "templates/{$slug}-{$name}.php" ) ) {
			$template = $this->plugin->dir() . "templates/{$slug}-{$name}.php";
		}

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/ssa/slug.php.
		if ( ! $template && ! SSA_TEMPLATE_DEBUG_MODE ) {
			$template = locate_template( array( "{$slug}.php", $this->plugin->template_subdirectory() . "{$slug}.php" ) );
		}

		// Allow 3rd party plugins to filter template file from their plugin.
		$template = apply_filters( 'ssa/templates/get_template_part', $template, $slug, $name );

		if ( $template ) {
			load_template( $template, false );
		}

		$output = ob_get_clean();
		if ( !empty( $echo ) ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * Get other templates passing attributes and including the file.
	 *
	 * @access public
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 * @param boolean $echo  Should echo $output to screen. (default: false).
	 * 
	 * @return string
	 */
	function get_template( $template_name, $args = array(), $template_path = '', $default_path = '', $echo = false ) {
		ob_start();

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // @codingStandardsIgnoreLine
		}

		$located = $this->locate_template( $template_name, $template_path, $default_path );

		if ( ! file_exists( $located ) ) {
			return;
		}

		// Allow 3rd party plugin filter template file from their plugin.
		$located = apply_filters( 'ssa/templates/get_template', $located, $template_name, $args, $template_path, $default_path );

		do_action( 'ssa_before_template_part', $template_name, $template_path, $located, $args );

		include $located;

		do_action( 'ssa_after_template_part', $template_name, $template_path, $located, $args );

		$output = ob_get_clean();
		if ( !empty( $echo ) ) {
			echo $output;
		}

		return $output;
	}

	public function get_template_rendered( $template, $template_vars = array() ) {
		$template_string = $this->get_template( $template );
		$template_vars = $this->get_template_vars( $template, $template_vars );
		if ( empty( $template_string ) ) {
			return false;
		}

		$rendered_template_string = $this->render_template_string( $template_string, $template_vars );

		return $rendered_template_string;
	}


	/**
	 * Like get_template, but returns the HTML instead of outputting.
	 *
	 * @see get_template_html
	 * @since 2.5.0
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 *
	 * @return string
	 */
	function get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		ob_start();
		$this->get_template( $template_name, $args, $template_path, $default_path );
		return ob_get_clean();
	}
	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 * yourtheme/$template_path/$template_name
	 * yourtheme/$template_name
	 * $default_path/$template_name
	 *
	 * @access public
	 * @param string $template_name Template name.
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 * @return string
	 */
	function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = $this->plugin->template_subdirectory();
		}

		if ( ! $default_path ) {
			$default_path = $this->plugin->dir() . 'templates/';
		}

		// Look within passed path within the theme - this is priority.
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);

		// Get default template/.
		if ( ! $template || SSA_TEMPLATE_DEBUG_MODE ) {
			$template = $default_path . $template_name;
		}

		// Return what we found.
		return apply_filters( 'ssa/templates/locate_template', $template, $template_name, $template_path );
	}

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 * yourtheme/$template_path/$template_name
	 * yourtheme/$template_name
	 * $default_path/$template_name
	 *
	 * @access public
	 * @param string $template_name Template name.
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 * @return string
	 */
	function locate_template_url( $template_name, $template_path = '', $default_path = '' ) {
		$template = $this->locate_template( $template_name, $template_path, $default_path );

		if ( empty( $template ) ) {
			return;
		}

		if ( $themes_pos = strpos( $template, 'themes/' ) ) {
			return content_url( substr( $template, $themes_pos ) );
		}

		if ( $plugins_pos = strpos( $template, 'plugins/' ) ) {
			return content_url( substr( $template, $plugins_pos ) );
		}

		return false;
	}


	public function cleanup_variables_in_string( $string ) {
		$string = str_replace(
			array( '{{', '{{  ', '}}', '  }}', '{%', '{%  ', '%}', '  %}', "\n }}", "\n}}" ),
			array( '{{ ', '{{ ', ' }}', ' }}', '{% ', '{% ', ' %}', ' %}', ' }}', '}}' ),
			$string
		);

		return $string;
	}


}
