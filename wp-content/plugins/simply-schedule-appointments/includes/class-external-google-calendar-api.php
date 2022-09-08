<?php
/**
 * Simply Schedule Appointments External Google Calendar Api.
 *
 * @since   4.1.2
 * @package Simply_Schedule_Appointments
 */

use \NSquared\SSA\Vendor\Google\Service\Calendar\EventAttendee;
use \NSquared\SSA\Vendor\Google_Service_Calendar as Google_Service_Calendar;
use \NSquared\SSA\Vendor\Google_Service_Calendar_Event as Google_Service_Calendar_Event;
use \NSquared\SSA\Vendor\Google_Service_Calendar_EventAttendee as Google_Service_Calendar_EventAttendee;
use \NSquared\SSA\Vendor\Google_Service_Calendar_EventDateTime as Google_Service_Calendar_EventDateTime;
use \NSquared\SSA\Vendor\Google_Service_Calendar_EventExtendedProperties as Google_Service_Calendar_EventExtendedProperties;
use \NSquared\SSA\Vendor\Google_Client as Google_Client;
use \NSquared\SSA\Vendor\Google_Service as Google_Service;

/**
 * Simply Schedule Appointments External Google Calendar Api.
 *
 * @since 4.1.2
 */
class SSA_External_Google_Calendar_Api extends SSA_External_Calendar_Api {
	const SERVICE = 'google';
	protected $api_service;

	public static function create_with_access_token( $access_token ) {
		$instance = new self;
		$instance->set_access_token( $access_token );

		return $instance;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	private function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}
	public function get_api() {
		if ( null !== $this->api ) {
			return $this->api;
		}

		// FOR DEBUGGING ONLY
		$httpClient = new \NSquared\SSA\Vendor\GuzzleHttp\Client([
			'verify' => false, // otherwise HTTPS requests will fail.
		]);

		$this->api = new \NSquared\SSA\Vendor\Google\Client();
		$this->api->setHttpClient($httpClient);	

		$settings = ssa()->google_calendar_settings->get();
		$this->api->setClientId( $settings['client_id'] );
		$this->api->setClientSecret( $settings['client_secret'] );

		return $this->api;
	}

	public function get_api_service() {
		if ( null !== $this->api_service ) {
			return $this->api_service;
		}

		$google_calendar_settings = ssa()->google_calendar_settings->get();

		$access_token = $this->get_access_token();
		$this->api_set_access_token( $access_token );
		$new_access_token = $this->api_get_access_token();
		if ( $access_token != $new_access_token ) {

			// TODO: store refreshed token
			// do_action( 'ssa/google_calendar/access_token/updated', $new_access_token, $access_token );

			// if ( !ssa()->settings_installed->is_enabled( 'staff') ) {
			// 	$google_calendar_settings['access_token'] = $new_access_token;
			// 	ssa()->google_calendar_settings->update( $google_calendar_settings );
			// }
		}

		$this->api_service = new Google_Service_Calendar( $this->get_api() );
		return $this->api_service;
	}

	public function api_get_access_token() {
		return $this->get_api()->getAccessToken();
	}

	public function api_set_access_token( $access_token ) {
		if ( is_array( $access_token ) ) {
			$access_token = json_encode( $access_token );
		}
		$this->get_api()->setAccessToken( $access_token );
		if ( $this->get_api()->isAccessTokenExpired() ) {
			$this->get_api()->refreshToken( $this->get_api()->getRefreshToken() );
		}
	}

	/**
	 * Revoke Google Calendar token.
	 */
	public function api_revoke_client_token( $access_token ) {
		try {
			$this->get_api()->revokeToken( $access_token );
		} catch ( \Exception $e ) {
			$this->errors[] = $e->getMessage();
		}
	}

	public function api_get_identity() {		
		$oauth2 = new \NSquared\SSA\Vendor\Google\Service_Oauth2( $this->get_api() );
		return $oauth2->userinfo->get();

		$user_service = new \NSquared\SSA\Vendor\Google\Service_IdentityToolkit_UserInfo( $this->get_api() );
		return $user_service->getDisplayName();
	}

	/**
	 * Get list of Google Calendars.
	 *
	 * @return array
	 */
	public function get_calendar_list() {
		$result = array();
		try {
			$calendarList = $this->get_api_service()->calendarList->listCalendarList();
			while ( true ) {
				/** @var \Google_Service_Calendar_CalendarListEntry $calendarListEntry */
				foreach ( $calendarList->getItems() as $calendarListEntry ) {
					$result[ $calendarListEntry->getId() ] = array(
						'primary' => $calendarListEntry->getPrimary(),
						'summary' => $calendarListEntry->getSummary(),
						'description' => $calendarListEntry->getDescription(),
						'kind' => $calendarListEntry->getKind(),
						'location' => $calendarListEntry->getLocation(),
						'role' => $calendarListEntry->getAccessRole(),
						'background_color' => $calendarListEntry->getBackgroundColor(),
						'foreground_color' => $calendarListEntry->getForegroundColor(),
						'color_id' => $calendarListEntry->getColorId(),
						'default_reminders' => $calendarListEntry->getDefaultReminders(),
						'time_zone' => $calendarListEntry->getTimeZone(),
					);
				}
				$pageToken = $calendarList->getNextPageToken();
				if ( $pageToken ) {
					$optParams    = array( 'pageToken' => $pageToken );
					$calendarList = $this->service->calendarList->listCalendarList( $optParams );
				} else {
					break;
				}
			}
		} catch ( \Exception $e ) {
			if ( class_exists( 'Session' ) ) {
				Session::set( 'staff_google_auth_error', json_encode( $e->getMessage() ) );
			} else {
				throw new Exception( 'staff_google_auth_error' . $e->getMessage(), '500' );
			}
		}

		return $result;
	}


	public function pull_availability_calendar( $calendar_id, $args=array() ) {
		$args = shortcode_atts( array(
			'start_date' => new DateTime(),
			'end_date' => '',
			'type' => '',
			'subtype' => '',
			'staff_id' => 0,
		), $args );

		try {
			$calendar = $this->get_api_service()->calendarList->get( $calendar_id );
		} catch( Exception $e ) {
			ssa_debug_log( $e->getMessage() );
		}

		// get all events from calendar, without timeMin filter (the end of the event can be later then the start of searched time period)
		$result = array();

		try {
			$calendar_access = $calendar->getAccessRole();
			$limit_events    = 500;

			$timeMin = $args['start_date']->format( \DateTime::RFC3339 );

			$events = $this->get_api_service()->events->listEvents( $calendar_id, array(
				'singleEvents' => true,
				'orderBy'      => 'startTime',
				'timeMin'      => $timeMin,
				'maxResults'   => $limit_events,
			) );

			while ( true ) {
				foreach ( $events->getItems() as $event ) {
					/** @var \Google_Service_Calendar_Event $event */
					// Skip events created by SSA in non freeBusyReader calendar.
					if ( $calendar_access != 'freeBusyReader' ) {
						$ext_properties = $event->getExtendedProperties();
						if ( $ext_properties !== null ) {
							$private = $ext_properties->private;
							if ( ! empty( $private['ssa_home_id'] ) && $private['ssa_home_id'] == SSA_Utils::get_home_id() ) {
								continue; // If this event comes from this site, we don't need to load it from gcal, we can use the local db copy in wp_appointments table instead
							}

							$shared = $ext_properties->shared;
							if ( ! empty( $shared['ssa_home_id'] ) && $shared['ssa_home_id'] == SSA_Utils::get_home_id() ) {
								continue; // If this event comes from this site, we don't need to load it from gcal, we can use the local db copy in wp_appointments table instead
							}
						}
					}
					$event_transparency = ( $event->getTransparency() === null || $event->getTransparency() === 'opaque' ) ? 'opaque' : 'transparent';

					// Get start/end dates of event and transform them into WP timezone (Google doesn't transform whole day events into our timezone).
					$event_start = $event->getStart();
					$event_end   = $event->getEnd();

					if ( $event_start->dateTime == null ) {
						// All day event.
						$event_start_date = new \DateTime( $event_start->date, new \DateTimeZone( 'UTC' ) );
						$event_end_date = new \DateTime( $event_end->date, new \DateTimeZone( 'UTC' ) );
						$is_all_day = 1;
					} else {
						// Regular event.
						$event_start_date = new \DateTime( $event_start->dateTime );
						$event_end_date = new \DateTime( $event_end->dateTime );
						$is_all_day = 0;
					}

					// Convert to WP time zone.
					$event_start_date = date_timestamp_set( date_create( 'UTC' ), $event_start_date->getTimestamp() );
					$event_end_date   = date_timestamp_set( date_create( 'UTC' ), $event_end_date->getTimestamp() );

					$result[] = array(
						'type' => $args['type'],
						'subtype' => $args['subtype'],
						'staff_id' => $args['staff_id'],
						'service' => self::SERVICE,
						'calendar_id' => $calendar_id,
						'calendar_id_hash' => ssa_int_hash( $calendar_id ),
						'ical_uid' => $event->getICalUID(),
						'event_id' => $event->id,
						'status' => $event->getStatus(),
						'start_date' => $event_start_date->format( 'Y-m-d H:i:s' ),
						'end_date' => $event_end_date->format( 'Y-m-d H:i:s' ),
						'is_all_day' => $is_all_day,
						'transparency' => $event_transparency,
						'is_available' => ( $event_transparency === 'transparent' ) ? 1 : 0,
					);
				}

				if ( ! $limit_events && $events->getNextPageToken() ) {
					$events = $this->get_api_service()->events->listEvents( $calendar_id, array(
						'singleEvents' => true,
						'orderBy'      => 'startTime',
						'timeMin'      => $timeMin,
						'pageToken'    => $events->getNextPageToken()
					) );
				} else {
					break;
				}
			}

			return $result;
		} catch ( \Exception $e ) {
			ssa_debug_log( $e->getMessage() );
		}

		return array();

	}

	// TODO:
	public function push_appointment( SSA_Appointment_Object $appointment ) {
		die( 'TODO' );
	}
	public function pull_appointment( SSA_Appointment_Object $appointment ) {
		die( 'TODO' );
	}

}
