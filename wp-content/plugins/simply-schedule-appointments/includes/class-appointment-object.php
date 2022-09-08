<?php
/**
 * Simply Schedule Appointments Appointment Object.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

use League\Period\Period;

/**
 * Simply Schedule Appointments Appointment Object.
 *
 * @since 0.0.3
 */
class SSA_Appointment_Object {
	protected $id = null;
	protected $model = null;
	protected $data = null;
	protected $appointment_type;
	protected $staff_members;
	protected $recursive_fetched = -2;

	protected $status;

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
	public function __construct( $id ) {
		if ( $id === 'transient' ) {		
			return;
		}

		$this->id = $id;
	}

	public static function instance( $appointment ) {
		if ( $appointment instanceof SSA_Appointment_Object ) {
			return $appointment;
		}

		if ( is_array( $appointment ) ) {
			$appointment = new SSA_Appointment_Object( $appointment['id'] );
			return $appointment;
		}

		$appointment = new SSA_Appointment_Object( $appointment );
		return $appointment;
	}

	/**
	 * Factory function to create with explicit data
	 *
	 * @param array $data
	 * @return SSA_Appointment_Object
	 * @author 
	 **/
	public static function create( SSA_Appointment_Type_Object $appointment_type, array $data ) {
		$appointment_object = new SSA_Appointment_Object( 'transient' );
		$appointment_object->appointment_type = $appointment_type;
		$data['appointment_type_id'] = $appointment_type->id;
		$appointment_object->data = $data;
		return $appointment_object;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		if ( empty( $this->data ) && $field !== 'id' ) {
			$this->get();
		}

		switch ( $field ) {
			case 'id':
			case 'data':
				return $this->$field;
			case 'start_date_datetime':
			case 'end_date_datetime':
				$date_time = ssa_datetime( $this->data[str_replace('_datetime', '', $field)] );
				return $date_time;
			case 'start_date_timestamp':
			case 'end_date_timestamp':
				return ssa_gmtstrtotime( $this->data[str_replace('_timestamp', '', $field)] );
			case 'web_meeting_url':
				if ( isset( $this->data[$field] ) ) {
					return trim( $this->data[$field] );
				}
			default:
				if ( isset( $this->data[$field] ) ) {
					return $this->data[$field];
				}
				
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	public function refresh( $recursive = -1 ) {
		$this->recursive_fetched = -2;
		$this->get( $recursive );
	}

	public function get( $recursive = -1 ) {
		if ( $recursive > $this->recursive_fetched ) {
			if ( null === $this->data ) {
				$this->data = array();
			}

			$model_data = ssa()->appointment_model->get( $this->id, $recursive );
			if ( empty( $model_data['id'] ) ) {
				throw new Exception( 'Appointment ID ' . $this->id . ' not found' );
			}
			$this->data = array_merge( $this->data, $model_data );
			$this->recursive_fetched = $recursive;
		}
	}

	public function get_appointment_period() {
		$start_date = $this->__get( 'start_date' );
		$end_date = $this->__get( 'end_date' );

		if ( $end_date < $start_date ) {
			ssa_debug_log( __CLASS__ . ' ' . __FUNCTION__ . '():' . __LINE__ );
			ssa_debug_log( 'The ending datepoint must be greater or equal to the starting datepoint in appointment ID ' . $this->id, 10 );

			return new Period(
				$start_date,
				$start_date
			);
		}

		return new Period(
			$start_date,
			$end_date
		);
	}

	public function get_buffer_before_period() {
		$buffer_before = $this->get_appointment_type()->buffer_before;
		if ( empty( $buffer_before ) ) {
			return false;
		}

		$start_date = ssa_datetime( $this->__get( 'start_date' ) );
		$calculated_period = new Period( $start_date->sub( new DateInterval( 'PT'.absint( $buffer_before ).'M' ) ), $start_date );
		
		return $calculated_period;
	}

	public function get_buffer_after_period() {
		$buffer_after = $this->get_appointment_type()->buffer_after;
		if ( empty( $buffer_after ) ) {
			return false;
		}

		$end_date = ssa_datetime( $this->__get( 'end_date' ) );
		$calculated_period = new Period( $end_date, $end_date->add( new DateInterval( 'PT'.absint( $buffer_after ).'M' ) ) );
		
		return $calculated_period;
	}

	public function get_buffered_period() {
		$period = $this->get_appointment_period();

		$buffer_before_period = $this->get_buffer_before_period();
		$buffer_after_period = $this->get_buffer_after_period();

		if ( false === $buffer_before_period && false === $buffer_after_period ) {
			return $period;
		}

		if ( false !== $buffer_before_period ) {
			if ( $buffer_before_period->getStartDate() < $period->getStartDate() ) {
				$period = new Period( $buffer_before_period->getStartDate(), $period->getEndDate() );
			}
			// TODO: else { Log error (we should never end up here), but at least we have prevented a fatal error }
		}
		if ( false !== $buffer_after_period ) {
			if ( $period->getEndDate() < $buffer_after_period->getEndDate() ) {
				$period = new Period( $period->getStartDate(), $buffer_after_period->getEndDate() );
			}
			// TODO: else { Log error (we should never end up here), but at least we have prevented a fatal error }
		}

		return $period;
	}

	public function get_buffered_query_period() {
		$period = $this->get_appointment_period();

		$buffer_before = $this->get_appointment_type()->buffer_before;
		$buffer_after = $this->get_appointment_type()->buffer_after;
		$buffer_max = max( $buffer_before, $buffer_after );

		if ( empty( $buffer_max ) ) {
			return $period;
		}
		
		$start_date = ssa_datetime($this->__get('start_date'));
		$end_date = ssa_datetime($this->__get('end_date'));
		$period = new Period(
			$start_date->sub(new DateInterval('PT' . absint($buffer_max) . 'M')),
			$end_date->add(new DateInterval('PT' . absint($buffer_max) . 'M'))
		);

		return $period;
	}


	public function get_data( $recursive = -1, $fetch_fields = array() ) {
		$this->get( $recursive );

		if ( $recursive >= 0 ) {
			if ( !isset( $fetch_fields['public_edit_url'] ) ) {
				$fetch_fields['public_edit_url'] = true;
			}
			if ( !isset( $fetch_fields['date_timezone'] ) ) {
				$fetch_fields['date_timezone'] = true;
			}
		}

		if ( !empty( $fetch_fields ) ) {
			$this->fetch_fields( $fetch_fields );
		}

		return $this->data;
	}

	public function fetch_fields( $fetch_fields = array() ) {
		if ( !is_array( $fetch_fields ) ) {
			throw new SSA_Exception("$fetch_fields must be an array", 1);
		}

		foreach ( $fetch_fields as $fetch_field => $fetch_options ) {
			if ( is_int( $fetch_field ) ) {
				$fetch_field = $fetch_options;
				$fetch_options = array();
			}

			$method_name = 'fetch_'.$fetch_field;
			if ( !method_exists( $this, $method_name ) ) {
				throw new SSA_Exception(__CLASS__ . "->" . $method_name . "() not implemented", 1);
			}

			$this->$method_name( $fetch_options );
		}
	}

	public function fetch_add_to_calendar_links( $atts = array() ) {
		if ( !is_array( $atts ) ) {
			$atts = array();
		}

		$atts = shortcode_atts( array(
			'customer' => true,
		), $atts );

		if ( !empty( $atts['customer'] ) ) {
			$this->data['ics']['customer']  = $this->get_ics_download_url( 'customer' );
			$this->data['gcal']['customer'] = $this->get_gcal_add_link( 'customer' );
		}
	}

	public function fetch_date_timezone( $atts = array() ) {
		$this->data['date_timezone'] = $this->get_date_timezone();
	}

	public function fetch_public_edit_url( $atts = array() ) {
		$this->data['public_edit_url'] = $this->get_public_edit_url();
	}

	public function get_appointment_type() {
		if ( ! empty( $this->appointment_type ) ) {
			return $this->appointment_type;
		}

		$this->appointment_type = new SSA_Appointment_Type_Object( $this->appointment_type_id );
		return $this->appointment_type;
	}

	public function get_staff_members() {
		if ( ! empty( $this->staff_members ) ) {
			return $this->staff_members;
		}

		$is_enabled = ssa()->settings_installed->is_enabled( 'staff' );
		if ( ! $is_enabled ) {
			return;
		}

		$staff_ids = ssa()->staff_appointment_model->get_staff_ids( $this->id );
		if ( empty( $staff_ids ) ) {
			$this->staff_members = null;
			return;
		}

		$staff_members = array();
		foreach ($staff_ids as $staff_id) {
			$staff_members[] = new SSA_Staff_Object( $staff_id );
		}
		$this->staff_members = $staff_members;

		return $this->staff_members;
	}

	public function get_date_timezone( $for_type = '', $for_id = '' ) {
		if ( $for_type === 'staff' ) {
			// TODO: Customize for staff ID
			// TODO: Customize for location ID
			// TODO: Customize for customer
			// TODO: Customize for admin
		} else {
			$settings = ssa()->settings->get();
			$date_timezone = new DateTimeZone( $settings['global']['timezone_string'] );
		}

		return $date_timezone;
	}

	public function get_customer_name() {
		$customer_information = $this->__get( 'customer_information' );
		$customer_name = '';
		if ( ! empty( $customer_information['name'] ) ) {
			$customer_name = $customer_information['name'];
		} elseif ( ! empty( $customer_information['Name'] ) ) {
			$customer_name = $customer_information['Name'];
		}

		return $customer_name;
	}

	public function get_customer_email() {
		$customer_information = $this->__get( 'customer_information' );
		$customer_email = '';
		if ( ! empty( $customer_information['email'] ) ) {
			$customer_email = $customer_information['email'];
		} elseif ( ! empty( $customer_information['Email'] ) ) {
			$customer_email = $customer_information['Email'];
		}

		return $customer_email;
	}

	/**
	 * Given an appointment, get the customer timezone string.
	 *
	 * @since 4.9.1
	 *
	 * @return string
	 */
	public function get_customer_timezone_string() {
		$customer_timezone = $this->__get( 'customer_timezone' );
		return $customer_timezone;
	}

	/**
	 * Given an appointment, get the customer timezone object.
	 *
	 * @since 4.9.1
	 *
	 * @return DateTimeZone
	 */
	public function get_customer_timezone() {
		$customer_timezone = $this->get_customer_timezone_string();

		if ( empty( $customer_timezone ) ) {
			return null;
		}
		return new DateTimeZone( $customer_timezone );
	}

	public function get_calendar_event_title( SSA_Recipient $recipient ) {
		$developer_settings = ssa()->developer_settings->get();

		// if beta_calendar_events feature is enabled, use the new function.
		if ( ! empty( $developer_settings['beta_calendar_events'] ) ) {
			return $this->get_calendar_event_title_beta( $recipient );
		}

		$settings               = ssa()->settings->get();
		$sitename               = $settings['global']['company_name'];
		$staff_name             = $settings['global']['staff_name'];
		$appointment_type_title = $this->get_appointment_type()->title;

		if ( $recipient->is_customer() && $recipient->is_business() ) {
			if ( $this->is_group_event() ) {
				$title = $this->get_appointment_type()->title .' (' . $sitename . ')';
			} elseif ( $this->is_individual_appointment() ) {
				$title = $this->get_customer_name() . ' + ' . $sitename . ': ' . $this->get_appointment_type()->title;
			}
		} elseif ( $recipient->is_customer() ) {
			$settings = ssa()->settings->get();
			$sitename = $settings['global']['company_name'];
			$title    = $this->get_appointment_type()->title .' (' . $sitename . ')';
		} elseif ( $recipient->is_business() ) {
			if ( $this->is_group_event() ) {
				$title = $this->get_appointment_type()->title;
			} elseif ( $this->is_individual_appointment() ) {
				$title = $this->get_customer_name() . ' - ' . $this->get_appointment_type()->title;
			}
		}

		if ( $this->is_group_event() ) {
			if ( $this->is_group_canceled() ) {
				$title = __( 'Canceled', 'simply-schedule-appointments' ) . ': ' . $title;
			}
		} elseif ( $this->is_individual_appointment() ) {		
			if ( $this->is_canceled() ) {
				$title = __( 'Canceled', 'simply-schedule-appointments' ) . ': ' . $title;
			}
		}

		return $title;
	}

	/**
	 * Given a specific recipient type, get's the right twig template to
	 * populate the event title, and parses the content.
	 *
	 * @since 5.4.0
	 *
	 * @param SSA_Recipient $recipient The recipient object.
	 * @return string the event title.
	 */
	public function get_calendar_event_title_beta( SSA_Recipient $recipient ) {
		$event_type     = $this->get_calendar_event_type( $recipient );
		$calendar_event = new SSA_Calendar_Events_Object( $event_type );

		$title = $calendar_event->get_calendar_event_content( 'title', $this->id, true );

		// if appointment is canceled, prefix with "Canceled: ".
		if ( $this->is_individual_appointment() && $this->is_canceled() ) {
			$title = 'Canceled: ' . $title;
		}

		if ( $this->is_group_event() && $this->is_group_canceled() ) {
			$title = 'Canceled: ' . $title;
		}

		return $title;
	}

	/**
	 * Given a specific recipient type, get's the right twig template to
	 * populate the event location, and parses the content.
	 *
	 * @since 5.4.0
	 *
	 * @param SSA_Recipient $recipient The recipient object.
	 * @return string the event location.
	 */
	public function get_calendar_event_location( SSA_Recipient $recipient ) {
		$event_type     = $this->get_calendar_event_type( $recipient );
		$calendar_event = new SSA_Calendar_Events_Object( $event_type );

		$location = $calendar_event->get_calendar_event_content( 'location', $this->id, true );

		return $location;
	}

	public function get_calendar_event_description( SSA_Recipient $recipient ) {
		$developer_settings = ssa()->developer_settings->get();

		// if beta_calendar_events feature is enabled, use the new function.
		if ( ! empty( $developer_settings['beta_calendar_events'] ) ) {
			return $this->get_calendar_event_description_beta( $recipient );
		}

		$description = '';
		$eol         = "\r\n";

		$web_meeting_url = $this->__get( 'web_meeting_url' );
		if ( ! empty( $web_meeting_url ) ) {
			// translators: %s is the web meeting URL.
			$description .= sprintf( __( "This event has a web meeting:\r\n%s", 'simply-schedule-appointments' ), $web_meeting_url );
			$description .= $eol;
			$description .= $eol;
		}

		if ( $this->is_group_event() ) {
			if ( $recipient->is_business() && ! $recipient->is_customer() ) {
				$appointments = $this->query_group_appointments();
				$description .= __( 'Attendees', 'simply-schedule-appointments' ) . ':' . $eol;
				foreach ( $appointments as $appointment ) {
					if ( ! $appointment->is_booked() ) {
						continue;
					}

					$description .= htmlspecialchars( $appointment->get_customer_name() ) . $eol;
				}
			}
		}

		if ( $this->is_individual_appointment() ) {
			$customer_information = $this->__get( 'customer_information' );

			foreach ( $customer_information as $label => $value ) {
				$description .= ucwords( str_replace( '_', ' ', $label ) ) . ': ';
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				$value = htmlspecialchars($value);
				$description .= $value . $eol;
			}

			$description .= $eol . __( 'Need to make changes to this event?', 'simply-schedule-appointments' );
			$description .= $eol . $this->get_public_edit_url();
		}

		return $description;
	}

	/**
	 * Given a specific recipient type, get's the right twig template to
	 * populate the event description, and parses the content.
	 *
	 * @since 5.4.0
	 *
	 * @param SSA_Recipient $recipient The recipient object.
	 * @return string the event description.
	 */
	public function get_calendar_event_description_beta( SSA_Recipient $recipient ) {
		$event_type     = $this->get_calendar_event_type( $recipient );
		$calendar_event = new SSA_Calendar_Events_Object( $event_type );

		$description = $calendar_event->get_calendar_event_content( 'details', $this->id );

		$clear_map = array(
			'</p>'   => "</p>\r\n",
			'<br />' => "\r\n",
			'<br>'   => "\r\n",
			'<br/>'  => "\r\n",
		);

		// Remove all html tags and turn paragraphs or <br> into line breaks.
		// Required to avoid html issues with Google Calendar and other calendar apps.
		$description = str_replace( array_keys( $clear_map ), array_values( $clear_map ), $description );
		$description = wp_strip_all_tags( $description );

		return $description;
	}

	/**
	 * Returns the event title for a specific template.
	 *
	 * @since 5.4.0
	 *
	 * @param string $template The template name.
	 * @return string The event title.
	 */
	public function get_title( $template ) {
		if ( 'customer' === $template ) {
			$recipient = SSA_Recipient_Customer::create();
		}
		if ('staff' === $template ) {
			$recipient = SSA_Recipient_Staff::create();
		}

		return $this->get_calendar_event_title( $recipient );
	}

	/**
	 * Returns the event location for a specific template.
	 *
	 * @since 5.4.0
	 *
	 * @param string $template The template name.
	 * @return string The event location.
	 */
	public function get_location( $template ) {
		if ( 'customer' === $template ) {
			$recipient = SSA_Recipient_Customer::create();
		}
		if ( 'staff' === $template ) {
			$recipient = SSA_Recipient_Staff::create();
		}
		return $this->get_calendar_event_location( $recipient );
	}

	public function get_description( $template, $eol = "\r\n" ) {
		$developer_settings = ssa()->developer_settings->get();

		// if beta_calendar_events feature is enabled, use the new function.
		if ( ! empty( $developer_settings['beta_calendar_events'] ) ) {
			return $this->get_description_beta( $template );
		}

		$description = '';
		if ( $template == 'staff' ) {
			$customer_information = $this->customer_information;
			foreach ( $customer_information as $label => $value ) {
				$description .= ucwords( str_replace( '_', ' ', $label ) ) . ': ';
				$description .= $value . $eol;
			}
		} elseif ( $template == 'customer' ) {
			$description  = $this->get_appointment_type()->description;
			$instructions = $this->get_appointment_type()->instructions; 
			if ( ! empty( $instructions ) ) {
				$description = $instructions . $eol . $description;
			}

			$description .= $eol . __( 'Need to make changes to this event?', 'simply-schedule-appointments' ) . "\r\n";
			$description .= $this->get_public_edit_url() . "\r\n";
		}

		$web_meeting_url = $this->__get( 'web_meeting_url' );
		if ( ! empty( $web_meeting_url ) ) {
			// translators: %s is the web meeting URL.
			$description .= $eol . sprintf( __( "This event has a web meeting:\r\n%s", 'simply-schedule-appointments' ), $web_meeting_url );
		}

		return $description;
	}

	/**
	 * Returns the event description for a specific template.
	 *
	 * @since 5.4.0
	 *
	 * @param string $template The template name.
	 * @return string The event description.
	 */
	public function get_description_beta( $template ) {
		if ( 'customer' === $template ) {
			$recipient = SSA_Recipient_Customer::create();
		}
		if ( 'staff' === $template ) {
			$recipient = SSA_Recipient_Staff::create();
		}

		return $this->get_calendar_event_description( $recipient );
	}

	/**
	 * Returns a list of attendees for the this appointment.
	 *
	 * @since 5.6.0
	 *
	 * @return array The list of attendees.
	 */
	public function get_attendees() {
		$attendees = array();

		// Add customer.
		$attendees[] = array(
			'email' => $this->get_customer_email(),
			'name'  => $this->get_customer_name(),
		);

		// Add team members if any is set to the appointment.
		$staff_ids = ssa()->staff_appointment_model->get_staff_ids( $this->id );
		if ( ! empty( $staff_ids ) ) {
			foreach ( $staff_ids as $staff_id ) {
				$staff = new SSA_Staff_Object( $staff_id );

				$attendees[] = array(
					'email' => $staff->email,
					'name'  => $staff->display_name,
				);
			}
		}

		/**
		 * Filters the list of attendees for an appointment.
		 *
		 * @since 5.6.0
		 *
		 * @param array $attendees The list of attendees.
		 * @param int  $appointment_id The appointment ID.
		 */
		$attendees = apply_filters( 'ssa/appointment/attendees', $attendees, $this->id ); //@codingStandardsIgnoreLine (WordPress doesn't like hook names with slashes).

		return $attendees;
	}

	public function get_calendar_id() {
		$group = $this->get_group_appointment();
		if ( ! empty( $group ) ) {
			return $group->__get( 'google_calendar_id' );
		}

		return $this->__get( 'google_calendar_id' );
	}

	public function get_calendar_event_id() {
		$group = $this->get_group_appointment();
		if ( ! empty( $group ) ) {
			return $group->google_calendar_event_id;
		}

		return $this->__get( 'google_calendar_event_id' );
	}

	/**
	 * Set an SSA_Ics_Exporter instance and define the template.
	 *
	 * @param string $template The template name.
	 * @return SSA_Ics_Exporter
	 */
	public function get_ics_exporter( $template = 'customer' ) {
		$ics_exporter           = new SSA_Ics_Exporter();
		$ics_exporter->template = $template;

		return $ics_exporter;
	}

	/**
	 * Get .ics file contents and headers.
	 *
	 * @param string $template The template name.
	 * @return array The .ics file contents and headers.
	 */
	public function get_ics( $template = 'customer' ) {
		$ics_exporter = $this->get_ics_exporter( $template );
		$ics          = $ics_exporter->get_ics_for_appointment( $this );

		return $ics;
	}

	/**
	 * Return the download url for the .ics file.
	 *
	 * @since 5.4.4
	 *
	 * @param string $type The user type.
	 *
	 * @return string The download url.
	 */
	public function get_ics_download_url( $type = 'customer' ) {
		// Get the rest api root url.
		$base = ssa()->appointment_model->get_ics_endpoints_base();
		$url  = $base . $this->id . '/ics/download/' . $type;

		return $url;
	}

	public function get_gcal_add_link( $template = 'customer' ) {
		$link = ssa()->gcal_exporter->get_add_link_from_appointment( $this, $template );

		return $link;
	}


	public function is_all_day() {
		return false;
	}

	public function get_public_edit_url() {
		$url = ssa()->appointment_model->get_public_edit_url( $this->id );
		return $url;
	}
	public function get_admin_edit_url() {
		$url = ssa()->appointment_model->get_admin_edit_url( $this->id );
		return $url;
	}

	public function is_unavailable() {
		return in_array( $this->__get( 'status' ), SSA_Appointment_Model::get_unavailable_statuses() );
	}
	public function is_available() {
		return ! $this->is_unavailable();
	}
	public function is_reserved() {
		return in_array( $this->__get( 'status' ), SSA_Appointment_Model::get_reserved_statuses() );
	}
	public function is_booked() {
		return in_array( $this->__get( 'status' ), SSA_Appointment_Model::get_booked_statuses() );
	}
	public function is_canceled() {
		return in_array( $this->__get( 'status' ), SSA_Appointment_Model::get_canceled_statuses() );
	}
	public function is_group_canceled() {
		if ( ! $this->is_group_event() ) {
			return null;
		}

		$group_id = $this->__get( 'group_id' );
		if ( empty( $group_id ) ) {
			return false;
		}

		$appointment_arrays = ssa()->appointment_model->query( array(
			'number' => -1,
			'group_id' => $group_id,
		) );
		if ( empty( $appointment_arrays ) ) {
			return false;
		}

		$is_group_canceled = true;
		foreach ($appointment_arrays as $appointment_array) {
			if ( in_array( $appointment_array['status'], SSA_Appointment_Model::get_booked_statuses() ) ) {
				return false;
			}
		}

		return $is_group_canceled;
	}

	public function get_group_appointment() {
		if ( ! $this->is_group_event() ) {
			return;
		}

		$group_id = $this->__get( 'group_id' );
		if ( empty( $group_id ) ) {
			return;
		}

		$group = new SSA_Appointment_Object( $group_id );
		return $group;
	}

	public function query_group_appointments() {
		if ( ! $this->is_group_event() ) {
			return;
		}

		$group_id = $this->__get( 'group_id' );
		if ( empty( $group_id ) ) {
			return;
		}

		$groups = ssa()->appointment_model->query( array(
			'number' => -1,
			'group_id' => $group_id,
		) );

		$group_objects = array();
		foreach ($groups as $group) {
			$group_objects[] = new SSA_Appointment_Object( $group['id'] );
		}

		return $group_objects;
	}

	public function is_group_event() {
		$capacity_type = $this->get_appointment_type()->capacity_type;
		return ( $capacity_type === 'group' );
	}

	public function is_individual_appointment() {
		$capacity_type = $this->get_appointment_type()->capacity_type;
		return ( $capacity_type === 'individual' );
	}

	/**
	 * Given a specific recipient type, returns the calendar event type slug.
	 *
	 * @since 5.4.0
	 *
	 * @param SSA_Recipient_Admin|SSA_Recipient_Customer|SSA_Recipient_Shared|SSA_Recipient_Staff $recipient the recipient class.
	 * @return string|boolean the event type slug.
	 */
	public function get_calendar_event_type( $recipient ) {
		if (
			! $recipient instanceof SSA_Recipient_Admin &&
			! $recipient instanceof SSA_Recipient_Customer &&
			! $recipient instanceof SSA_Recipient_Shared &&
			! $recipient instanceof SSA_Recipient_Staff
		) {
			return null;
		}

		if ( $recipient->is_customer() && $recipient->is_business() ) {
			if ( $this->is_group_event() ) {
				return 'group_shared';
			} elseif ( $this->is_individual_appointment() ) {
				return 'individual_shared';
			}
		} elseif ( $recipient->is_customer() ) {
			return 'customer';
		} elseif ( $recipient->is_business() ) {
			if ( $this->is_group_event() ) {
				return 'group_admin';
			} elseif ( $this->is_individual_appointment() ) {
				return 'individual_admin';
			}
		}
	}

	public function format_webhook_payload( $payload ) {
		$payload['appointment'] = shortcode_atts( array(
			'id' => '',
			'appointment_type_id' => '',
			'appointment_type_slug' => '',
			'customer_id' => '',
			'customer_information' => array(),
			'post_information'  => array(),
			'customer_timezone' => '',
			'start_date' => '',
			'end_date' => '',
			'status' => '',
			'date_created' => '',
			'date_modified' => '',
			'public_edit_url' => '',
			'payment_method' => '',
			'price_full' => '',
		), $payload['appointment'] );

		$dates_to_localize = array(
			'start_date',
			'end_date',
			'date_created',
			'date_modified',
		);

		if ( empty( $payload['appointment']['appointment_type_slug'] ) && !empty( $payload['appointment']['appointment_type_id'] ) ) {
			$appointment_type_object = $this->get_appointment_type();
			$payload['appointment']['appointment_type_slug'] = $appointment_type_object->slug;
		}

		if ( empty( $payload['appointment']['appointment_type_title'] ) && !empty( $payload['appointment']['appointment_type_id'] ) ) {
			$appointment_type_object = $this->get_appointment_type();
			$payload['appointment']['appointment_type_title'] = $appointment_type_object->title;
		}

		$settings_global = ssa()->settings->get()['global'];
		foreach ( $dates_to_localize as $key ) {
			if ( empty( $payload['appointment'][$key] ) ) {
				continue;
			}

			/* Raw */
			$payload['appointment']['local_time_for']['appointment_type']['raw'][$key] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'Y-m-d H:i:s' );
			$payload['appointment']['local_time_for']['appointment_type']['raw_parts'][$key]['date'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'Y-m-d' );
			$payload['appointment']['local_time_for']['appointment_type']['raw_parts'][$key]['time'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'H:i:s' );
			$payload['appointment']['local_time_for']['appointment_type']['raw_parts'][$key]['timezone'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'e' );
			$payload['appointment']['local_time_for']['appointment_type']['raw_parts'][$key]['timezone_offset'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'O' );


			/* Formatted */
			$payload['appointment']['local_time_for']['appointment_type']['formatted'][$key] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( $settings_global['date_format'].' '.$settings_global['time_format']. ' T' );

			$payload['appointment']['local_time_for']['appointment_type']['formatted_parts'][$key]['date'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( $settings_global['date_format'] );
			$payload['appointment']['local_time_for']['appointment_type']['formatted_parts'][$key]['time'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( $settings_global['time_format'] );
			$payload['appointment']['local_time_for']['appointment_type']['formatted_parts'][$key]['timezone'] = ssa()->utils->get_datetime_as_local_datetime( $payload['appointment'][$key], $payload['appointment']['appointment_type_id'] )->format( 'T' );
		}

		if( ! empty( $payload['meta']['booking_url'] ) ) {
			$payload['post_information']['booking_url'] = $payload['meta']['booking_url'];
		}
		if( ! empty( $payload['meta']['booking_title'] ) ) {
			$payload['post_information']['booking_title'] = $payload['meta']['booking_title'];
		}
		if( ! empty( $payload['meta']['booking_post_id'] ) ) {
			$payload['post_information']['booking_post_id'] = $payload['meta']['booking_post_id'];
		}

		return $payload;
	}

	public function get_webhook_payload( $action ) {
		$action_noun = '';
		$action_verb = '';
		if ( false !== strpos( $action, '_' ) ) {
			$action_noun = explode( '_', $action )[0];
			$action_verb = explode( '_', $action )[1];
		}
		$payload = array(
			'action' => $action,
			'action_noun' => $action_noun,
			'action_verb' => $action_verb,
			'appointment' => $this->get_data( 0 ),
			'meta'        => ssa()->appointment_model->get_metas( $this->id ),
		);
		$payload = $this->format_webhook_payload( $payload );

		return $payload;
	}
}
