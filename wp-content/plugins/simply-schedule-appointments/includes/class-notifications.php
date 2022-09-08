<?php
/**
 * Simply Schedule Appointments Notifications.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notifications.
 *
 * @since 0.0.3
 */
class SSA_Notifications {
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
		add_action( 'ssa/appointment/booked', array( $this, 'queue_booked_notifications' ), 1000, 4 );
		add_action( 'ssa/appointment/booked', array( $this, 'queue_start_date_notifications' ), 1000, 4 );
		add_action( 'ssa/appointment/customer_information_edited', array( $this, 'queue_customer_information_edited_notifications' ), 1000, 4 );
		add_action( 'ssa/appointment/canceled', array( $this, 'queue_canceled_notifications' ), 1000, 4 );

		add_action( 'ssa_fire_appointment_booked_notifications', array( $this, 'maybe_fire_notification'), 10, 2 );
		add_action( 'ssa_fire_appointment_start_date_notifications', array( $this, 'maybe_fire_notification'), 10, 2 );
		add_action( 'ssa_fire_appointment_customer_information_edited_notifications', array( $this, 'maybe_fire_notification'), 10, 2 );
		add_action( 'ssa_fire_appointment_canceled_notifications', array( $this, 'maybe_fire_notification'), 10, 2 );
		add_action( 'ssa/async/send_notifications', array( $this, 'fire_notification' ), 10, 2 );
	}

	public function get_payload( $hook, $appointment_id, $data, $data_before = array(), $response = null ) {
		$appointment_object = new SSA_Appointment_Object( $appointment_id );

		$action_pieces = explode( '_', $hook );
		$action_verb = array_pop( $action_pieces );
		$action_noun = implode( '_', $action_pieces );

		$payload = array(
			'action' => $hook,
			'action_noun' => $action_noun,
			'action_verb' => $action_verb,
			'appointment' => $appointment_object->get_data( 0 ),
			'data_before' => $data_before,
		);

		return $payload;

	}

	public function queue_booked_notifications( $appointment_id, $data, $data_before = array(), $response = null ) {
		$this->queue_notifications( 'appointment_booked', 'ssa_fire_appointment_booked_notifications', $appointment_id, $data, $data_before, $response );
	}

	public function queue_start_date_notifications( $appointment_id, $data, $data_before = array(), $response = null ) {
		$this->queue_notifications( 'appointment_start_date', 'ssa_fire_appointment_start_date_notifications', $appointment_id, $data, $data_before, $response );
	}

	public function queue_customer_information_edited_notifications( $appointment_id, $data, $data_before = array(), $response = null ) {
		$this->queue_notifications( 'appointment_customer_information_edited', 'ssa_fire_appointment_customer_information_edited_notifications', $appointment_id, $data, $data_before, $response );
	}

	public function queue_canceled_notifications( $appointment_id, $data, $data_before = array(), $response = null ) {
		$this->queue_notifications( 'appointment_canceled', 'ssa_fire_appointment_canceled_notifications', $appointment_id, $data, $data_before, $response );
	}

	public function queue_notifications( $hook, $action_to_fire, $appointment_id, $data, $data_before = array(), $response = null ) {
		if ( ! $this->plugin->settings_installed->is_enabled( 'notifications' ) ) {
			return false;
		}

		$notifications = $this->plugin->notifications_settings->get_notifications();
		if ( empty( $notifications ) ) {
			return;
		}

		$appointment_object = new SSA_Appointment_Object( $appointment_id );
		foreach ($notifications as $key => $notification) {
			if ( ! empty( $notification['appointment_types'] ) && is_array( $notification['appointment_types'] ) && ! in_array( $appointment_object->appointment_type_id, $notification['appointment_types'] ) ) {
				continue;
			}

			if ( $notification['trigger'] !== $hook ) {
				continue;
			}

			if ( isset( $notification['active'] ) && empty( $notification['active'] ) ) {
				continue; // if it isn't set yet, then the settings may have been stored before the active toggle existed. They default on, so if 'active' isn't set, we'll assume it should be on.
			}

			if ( 'sms' === $notification['type'] && ! empty( $notification['sms_to'] ) ) {
				if ( ! $this->plugin->settings_installed->is_enabled( 'sms' ) ) {
					continue;
				}
			}

			$meta = array();
			$date_queued_datetime = ssa_datetime();
			if ( $notification['trigger'] === 'appointment_start_date' ) {
				$date_queued_datetime = $appointment_object->start_date_datetime;
			}

			if ( ! empty( $notification['duration'] ) ) {
				$interval_string = 'PT'.absint( $notification['duration'] ).'M';
				if ( $notification['when'] === 'after' ) {
					$date_queued_datetime = $date_queued_datetime->add( new DateInterval( $interval_string ) );
				} else {
					$date_queued_datetime = $date_queued_datetime->sub( new DateInterval( $interval_string ) );
				}
			}

			if ( $notification['trigger'] === 'appointment_start_date' && $date_queued_datetime <= ssa_datetime() ) {
				continue; // Don't schedule reminders if they would be sent before the appointment was actually booked
			}
			$date_queued_string = $date_queued_datetime->format( 'Y-m-d H:i:s' );
			$meta['date_queued'] = $date_queued_string;
			$payload = $this->get_payload( $hook, $appointment_id, $data, $data_before, $response );
			$payload['notification'] = array(
				'id' => $notification['id'],
			);
			// ssa_debug_log( $notification, 1, 'Notification queued' );
			ssa_queue_action( $hook, $action_to_fire, 10, $payload, 'appointment', $appointment_id, 'notifications', $meta );
		}

	}

	public function fail_async_action( $async_action, $error_code = 500, $error_message = '', $context = array() ) {
		$response = array(
			'status_code' => $error_code,
			'error_message' => $error_message,
			'context' => $context,
		);
		// ssa_debug_log( $async_action, 1, 'async_action failed' );
		ssa_complete_action( $async_action['id'], $response );
	}

	public function should_fire_notification( $single_notification_settings, $payload ) {
		// ssa_debug_log( __FUNCTION__ .'()' );
		// ssa_debug_log( $single_notification_settings, 1, '$single_notification_settings' );
		// ssa_debug_log( $payload, 1, '$payload' );

		if ( ! $this->plugin->settings_installed->is_enabled( 'notifications' ) ) {
			return false;
		}

		if ( isset( $single_notification_settings['active'] ) && empty( $single_notification_settings['active'] ) ) {
			return false; // if it isn't set yet, then the settings may have been stored before the active toggle existed. They default on, so if 'active' isn't set, we'll assume it should be on.
		}

		// Only try to send if the notification IDs match
		if ( empty( $single_notification_settings['id'] ) || empty( $payload['notification']['id'] ) || $payload['notification']['id'] != $single_notification_settings['id'] || empty( $payload['action'] ) ) {
			return false;
		}

		// Check appointment type
		if ( is_array( $single_notification_settings ) && ! isset( $single_notification_settings['appointment_types'] ) ) {
			return false;
		}

		if ( empty( $payload['appointment']['id'] ) ) {
			return false;
		}
		$appointment_object = new SSA_Appointment_Object( $payload['appointment']['id'] );
		try {
			$status = $appointment_object->get();
		} catch (Exception $e) {
			ssa_debug_log( 'Appointment ID ' . $payload['appointment']['id'] . ' not found in should_fire_notification()' );
			return false;
		}

		if ( $appointment_object->status === 'canceled' ) {
			// We shouldn't send notifications if the appointment was canceled after this action was queued
			if (  $payload['action'] !== 'appointment_canceled') {
				return false;  
			}
			// unless this is specifically an "appointment_canceled" trigger, in which case we continue on...
		}

		if ( $appointment_object->status === 'abandoned' ) {
			// We shouldn't send notifications if the appointment was abandoned after this action was queued
			if (  $payload['action'] !== 'appointment_abandoned') {
				return false;
			}
			// unless this is specifically an "appointment_abandoned" trigger, in which case we continue on...
		}

		if ( $single_notification_settings['when'] === 'before' && $single_notification_settings['trigger'] === 'appointment_start_date' ) {
			// We shouldn't send notifications if the appointment already started and this was supposed to go out *before* the appointment start time
			if ( ssa_datetime() >= $appointment_object->start_date_datetime ) {
				return false;
			}
		}

		// Default is all appointment types if not specifically set
		if ( empty( $single_notification_settings['appointment_types'] ) ) {
			return true;
		}

		// Let's check if the appointment type is one of the allowed ones
		if ( in_array( $appointment_object->get_appointment_type()->id, $single_notification_settings['appointment_types'] ) ) {
			return true;
		}
		
		// We've reached this in error, default to not sending the notification
		return false;
	}

	public function maybe_fire_notification( $payload, $async_action ) {
		$notifications = $this->plugin->notifications_settings->get_notifications();
		$responses = array();
		if ( empty( $notifications ) ) {
			$this->fail_async_action( $async_action, 500, 'No notifications in settings', array( 'notifications' => $notifications ) );
			return;
		}

		$appointment_id = $payload['appointment']['id'];
		foreach ( $notifications as $notification_key => $notification ) {
			if ( empty( $payload['notification']['id'] ) || $payload['notification']['id'] != $notification['id'] ) {
				continue; // skip any non-matches
			}
			if ( ! $this->should_fire_notification( $notification, $payload ) ) {
				$responses[] = array(
					'action' => $payload['action'],
					'skipped' => true,
					'notification' => $notification,
				);				
				continue;
			}

			$responses[] = array(
				'action' => $payload['action'],
				'notification' => $notification,
				'payload' => $payload,
				'response' => $this->fire_notification( $notification, $payload ),
			);

		}

		ssa_complete_action( $async_action['id'], $responses );
		return true;
	}

	public function prepare_notification_template( $string ) {
		$string = str_replace( '<br>', '<br />', $string );
		$string = str_replace(
			array( '<p><br />', '<br /></p>', '}}<br />', '%}<br />' ),
			array( '<p>'      , '</p>'      , '}}'      , '%}'       ),
			$string
		);
		$string = str_replace( '{{ Appointment.customer_information_summary }}', '{% for label, entered_value in Appointment.customer_information_strings if entered_value|trim %}{{ label|internationalize }}: {{ entered_value|trim }} <br />{% endfor %}', $string );
		$instructions = "{{ Appointment.AppointmentType.instructions|raw }} 

			{% if Appointment.web_meeting_url %}
			{{ Appointment.web_meeting_url }} 
			{% endif %}";
		$string = str_replace( '{{ instructions }}', $instructions, $string );

		return $string;
	}

	public function fire_notification( $notification_to_fire, $payload ) {
		// ssa_debug_log( __FUNCTION__ .'()' );
		// ssa_debug_log( $notification_to_fire, 1, '$notification_to_fire' );
		// ssa_debug_log( $payload, 1, '$payload' );

		if ( empty( $payload['appointment']['id'] ) ) {
			return false;
		}

		$settings = $this->plugin->settings->get();
		$notifications = $this->plugin->notifications_settings->get_notifications();
		sleep(1); // Throttle emails for shared hosts and prevent race condition with Google Meet web meeting urls
		$appointment_object = new SSA_Appointment_Object( $payload['appointment']['id'] );

		foreach ($notifications as $key => $notification) {

			if ( $notification_to_fire['id'] != $notification['id'] ) {
				continue;
			}

			$appointment_object = new SSA_Appointment_Object( $payload['appointment']['id'] );
			$notification_vars = $this->plugin->templates->get_template_vars( 'notification', array(
				'appointment_id' => $payload['appointment']['id'],
			) );

			if ( empty( $notification['subject'] ) ) {
				$subject = '';
			} else {
				$subject = wp_strip_all_tags( $this->get_rendered_template_string_for_appointment( $appointment_object, $notification['subject'], $notification_vars ), true );
			}
			$message = $this->get_rendered_template_string_for_appointment( $appointment_object, $notification['message'], $notification_vars );

			$recipients = array(
				'sent_to' => array(),
				'sms_to' => array(),
				'cc' => array(),
				'bcc' => array(),
			);
			$recipient_type = 'customer';
			foreach ( $recipients as $recipients_key => $recipient_addresses ) {
				if ( empty( $notification[$recipients_key] ) || ! is_array( $notification[$recipients_key] ) ) {
					continue;
				}

				if ( $recipients_key === 'sent_to' ) {
					// set recipient type to staff if "To" is not sent to a customer
					if ( ! in_array( '{{customer_email}}', $notification[$recipients_key] ) ) {
						$recipient_type = 'staff';
					}
				}

				foreach ( $notification[$recipients_key] as $recipient_address_key => $recipient_address ) {
					if ( 'sms' === $notification['type'] && ! empty( $notification['sms_to'] ) && 'sms_to' === $recipients_key && '{{ customer_phone }}' === $recipient_address ) {
						$allow_sms = $appointment_object->allow_sms;
						if ( empty ( $allow_sms ) ) {
							continue;
						}
					}


					$address = $this->plugin->templates->render_template_string( $recipient_address, $notification_vars );
					$address = str_replace( "\n", '', $address );
					if ( empty( $address ) ) {
						continue;
					}
					if ( strpos( $address, ', ' ) ) {
						$addresses = explode( ', ', $address );
						foreach ($addresses as $address) {
							if ( 'email' === $notification['type'] && ! is_email( $address ) ) {
								continue;
							}
							$recipients[$recipients_key][] = $address;
						}
					} else {
						if ( 'email' === $notification['type'] && ! is_email( $address ) ) {
							continue;
						}
						$recipients[$recipients_key][] = $address;
					}
				}
			}

			if ( 'sms' === $notification['type'] && ! empty( $recipients['sms_to'] ) ) {
				if ( ! $this->plugin->settings_installed->is_enabled( 'sms' ) ) {
					continue;
				}

				$response = array();
				foreach ($recipients['sms_to'] as $key => $to_number) {
					$response[] = $this->plugin->sms->deliver_notification( array(
						'to_number' => $to_number,
						'notification' => $notification,
						'notification_vars' => $notification_vars,
						'appointment_object' => $appointment_object,
						'subject' => $subject,
						'message' => $message,
					) );
				}

				return $response;

			}

			if ( 'email' === $notification['type'] && ! empty( $recipients['sent_to'] ) ) {			
				$headers = array(
					'Reply-To: '.$this->get_reply_to_email_for_appointment( $appointment_object, $recipient_type, 'notification' ),
					'Content-Type: text/html',
				);
				if ( ! empty( $recipients['cc'] ) ) {
					$headers[] = 'Cc: '.implode( ',', $recipients['cc'] );
				}

				if ( ! empty( $recipients['bcc'] ) ) {
					$headers[] = 'Bcc: '.implode( ',', $recipients['bcc'] );
				}

				$from_email = $settings['global']['admin_email'];
				$from_name = $this->get_from_name_for_appointment( $appointment_object, $recipient_type, 'notification' );
				$attachments = array();
				// ssa_debug_log($recipients, 10, 'recipients for ' . $notification['title']);
				
				if ( empty( $recipients['sent_to'] ) ) {
					return;
				}
				
				return $this->ssa_wp_mail(
					$recipients['sent_to'],
					$subject,
					$message,
					$headers,
					$attachments,
					$from_email,
					$from_name
				);
			}
		}

	}

	private function get_template_rendered_for_appointment( SSA_Appointment_Object $appointment_object, $template ) {
		$content = $this->plugin->templates->get_template_rendered( 
			'notifications/email/text/'.$template.'.php',
			array(
				'appointment_id' => $appointment_object->id,
			)
		);

		return $content;
	}

	public function get_rendered_template_string_for_appointment( SSA_Appointment_Object $appointment_object, $template_string, $notification_vars = array() ) {
		if ( empty( $notification_vars ) ) {
			$notification_vars = $this->plugin->templates->get_template_vars( 'notification', array(
				'appointment_id' => $appointment_object->id,
			) );
		}

		$template_string = $this->plugin->templates->cleanup_variables_in_string( $template_string );
		$template_string = $this->prepare_notification_template( $template_string );
		$template_string = $this->plugin->templates->render_template_string( $template_string, $notification_vars );
		$template_string = str_replace(
			array( '&nbsp;' ),
			array( ' ' ),
			$template_string
		);
		$template_string = htmlspecialchars_decode( $template_string );
		$template_string = make_clickable( $template_string );

		return $template_string;
	}

	public function get_rendered_template_string_for_example_appointment_type( SSA_Appointment_Type_Object $appointment_type_object, $template_string, $notification_vars = array() ) {
		if ( empty( $notification_vars ) ) {
			$notification_vars = $this->plugin->templates->get_template_vars( 'notification', array(
				'example_appointment_type_id' => $appointment_type_object->id,
			) );
		}

		$template_string = $this->prepare_notification_template( $template_string );
		$template_string = $this->plugin->templates->render_template_string( $template_string, $notification_vars );
		$template_string = str_replace(
			array( '&nbsp;' ),
			array( ' ' ),
			$template_string
		);
		$template_string = htmlspecialchars_decode( $template_string );
		$template_string = make_clickable( $template_string );

		return $template_string;
	}


	/**
	 * Get mail headers (From/Cc/Bcc) for a given appointment or appointment type
	 *
	 * @param SSA_Appointment_Object $appointment_object
	 * @param string $template
	 * @return array
	 * @author 
	 **/
	public function get_mail_headers_for_appointment( SSA_Appointment_Object $appointment_object, $template ) {
		$headers = array();

		
	}

	public function get_from_name_for_appointment( SSA_Appointment_Object $appointment_object, $recipient, $template ) {
		$settings = $this->plugin->settings->get();

		if ( $recipient == 'customer' ) {
			$value = str_replace( '"', '', $settings['global']['staff_name'] ) .' '.__('at', 'simply-schedule-appointments' ).' '.str_replace( '"', '', $settings['global']['company_name'] );
		} elseif ( $recipient == 'staff' ) {
			$value = $appointment_object->customer_information['Name'] .' ('.$settings['global']['company_name'].')';
		}

		return $value;
	}

	// public function get_from_email_for_appointment( SSA_Appointment_Object $appointment_object, $template ) {
	// 	$settings = $this->plugin->settings->get();

	// 	$value = str_replace( '"', '', $settings['global']['staff_name'] ) .' at '.str_replace( '"', '', $settings['global']['company_name'] );

	// 	return $value;
	// }

	public function get_reply_to_email_for_appointment( SSA_Appointment_Object $appointment_object, $recipient, $template ) {
		$settings = $this->plugin->settings->get();

		if ( $recipient == 'customer' ) {
			$value = $settings['global']['admin_email'];
		} elseif ( $recipient == 'staff' ) {
			$value = $appointment_object->customer_information['Email'];
		}
		
		return $value;
	}

	public function set_ssa_from_name( $name ) {
		global $ssa_wp_mail_from_name;
		global $ssa_wp_mail_from_name_swp;
		$ssa_wp_mail_from_name_swp = $ssa_wp_mail_from_name;
		$ssa_wp_mail_from_name = $name;
	}

	public function reset_ssa_from_name() {
		global $ssa_wp_mail_from_name;
		global $ssa_wp_mail_from_name_swp;
		$ssa_wp_mail_from_name = $ssa_wp_mail_from_name_swp;
		unset( $ssa_wp_mail_from_name_swp );
	}

	public function get_ssa_from_name() {
		global $ssa_wp_mail_from_name;
		return $ssa_wp_mail_from_name;
	}

	public function ssa_wp_mail( $to, $subject, $message, $headers = '', $attachments = array(), $from_email = '', $from_name = '' ) {
		if ( empty( $to ) ) {
			return;
		}
		
		$this->set_ssa_from_name( $from_name );

		add_filter( 'wp_mail_from_name', array( $this, 'get_ssa_from_name' ), 5 );
		wp_mail( $to, $subject, $message, $headers, $attachments );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_ssa_from_name' ), 5 );

		$this->reset_ssa_from_name();
	}

}