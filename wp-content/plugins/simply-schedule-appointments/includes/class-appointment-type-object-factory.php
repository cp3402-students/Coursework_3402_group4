<?php
/**
 * Simply Schedule Appointments Appointment Type Object.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Type Object.
 *
 * @since 0.0.3
 */
class SSA_Appointment_Type_Object_Factory extends SSA_Appointment_Type_Object {

	public static function create( array $data = array() ) {
		static $id = 0;
		$id++;

		$instance = new SSA_Appointment_Type_Object( $id );

		$fixture_data = array (
		  'timezone' => new DateTimezone( 'UTC' ),
		  'id' => $id,
		  'author_id' => '1',
		  'title' => 'Phone Consultation',
		  'slug' => 'phone-consultation',
		  'location' => '',
		  'description' => '',
		  'instructions' => '',
		  'label' => 'light-green',
		  'capacity_type' => 'individual',
		  'capacity' => 1,
		  'buffer_before' => 0,
		  'duration' => 60,
		  'buffer_after' => 0,
		  'min_booking_notice' => '0',
		  'max_booking_notice' => '0',
		  'max_event_count' => '0',
		  'booking_start_date' => '0000-00-00 00:00:00',
		  'booking_end_date' => '0000-00-00 00:00:00',
		  'availability_type' => 'available_blocks',
		  'availability' => 
		  array (
		    'Monday' => 
		    array (
		      0 => 
		      array (
		        'time_start' => '09:00:00',
		        'time_end' => '17:00:00',
		      ),
		    ),
		    'Tuesday' => 
		    array (
		      0 => 
		      array (
		        'time_start' => '09:00:00',
		        'time_end' => '17:00:00',
		      ),
		    ),
		    'Wednesday' => 
		    array (
		      0 => 
		      array (
		        'time_start' => '09:00:00',
		        'time_end' => '17:00:00',
		      ),
		    ),
		    'Thursday' => 
		    array (
		      0 => 
		      array (
		        'time_start' => '09:00:00',
		        'time_end' => '17:00:00',
		      ),
		    ),
		    'Friday' => 
		    array (
		      0 => 
		      array (
		        'time_start' => '09:00:00',
		        'time_end' => '17:00:00',
		      ),
		    ),
		    'Saturday' => 
		    array (
		    ),
		    'Sunday' => 
		    array (
		    ),
		  ),
		  'availability_start_date' => NULL,
		  'availability_end_date' => NULL,
		  'availability_increment' => '15',
		  'timezone_style' => 'localized',
		  'booking_layout' => 'week',
		  'customer_information' => 
		  array (
		  ),
		  'custom_customer_information' => 
		  array (
		    0 => 
		    array (
		      'field' => 'Name',
		      'display' => true,
		      'required' => true,
		      'type' => 'single-text',
		      'icon' => 'face',
		      'values' => '',
		    ),
		    1 => 
		    array (
		      'field' => 'Email',
		      'display' => true,
		      'required' => true,
		      'type' => 'single-text',
		      'icon' => 'email',
		      'values' => '',
		    ),
		  ),
		  'notifications' => 
		  array (
		    0 => 
		    array (
		      'field' => 'admin',
		      'send' => true,
		    ),
		    1 => 
		    array (
		      'field' => 'customer',
		      'send' => true,
		    ),
		  ),
		  'payments' => '',
		  'staff' => '',
		  'google_calendars_availability' => 
		  array (
		  ),
		  'google_calendar_booking' => '',
		  'mailchimp' => '',
		  'status' => 'publish',
		  'visibility' => 'public',
		  'display_order' => '1',
		  'date_created' => '2020-03-31 23:35:05',
		  'date_modified' => '2020-03-31 23:35:14',
		);

		$data = array_merge( $fixture_data, $data );

		$instance->timezone = $data['timezone'];
		unset( $data['timezone'] );

		$instance->data = $data;

		return $instance;
	}


	public static function create_random( array $data = array() ) {
		$title = self::generate_title();
		$slug = sanitize_title( $title );

		$fixture_data = array (
		  'title' => $title,
		  'slug' => $slug,
		  'buffer_before' => rand(0,8) * 30,
		  'duration' => rand(0,12) * 15,
		  'buffer_after' => rand(0,8) * 30,
		);

		$data = array_merge( $fixture_data, $data );

		return self::create( $data );
	}

	public static function generate_title() {
		$choices = array(
			'Consultation Phone Call',
			'Life Coaching',
			'Personal Training',
			'Kittysitting',
			'Cat Therapy',
			'Dog Walking',
		);
		return $choices[array_rand( $choices )];
	}
}
