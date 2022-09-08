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
class SSA_Appointment_Factory extends SSA_Appointment_Object {

	public static function create( SSA_Appointment_Type_Object $appointment_type, array $data = array() ) {
		static $appointment_id = 0;
		$appointment_id++;

		$instance = new SSA_Appointment_Object( $appointment_id );

		$start_date_string = $data['start_date'];
		$start_date = new DateTimeImmutable( $start_date_string );
		try {
			$end_date = $start_date->add( new DateInterval( 'PT'.$appointment_type->duration.'M' ) );
		} catch ( Exception $e ) {
			ssa_debug_log( 'empty duration for Appointment Type #' . $appointment_type->id, 10 );
			$end_date = $start_date; // TODO: fix this to prevent unexpected behavior
			wp_die( 'empty duration for Appointment Type #' . $appointment_type->id, 'Appointment Factory duration missing' );
		}

		$fixture_data = array (
		  'id' => '',
		  'appointment_type_id' => $appointment_type->id,
		  'rescheduled_from_appointment_id' => 0,
		  'rescheduled_to_appointment_id' => 0,
		  'author_id' => 0,
		  'customer_id' => 0,
		  'customer_information' => array(
		  	'Name' => 'Oliver',
		  	'Email' => 'Oliver@gmail.com',
		  ),
		  'customer_timezone' => 'America/Los_Angeles',
		  'start_date' => $start_date->format( 'Y-m-d H:i:s' ),
		  'end_date' => $end_date->format( 'Y-m-d H:i:s' ),
		  'title' => '',
		  'description' => '',
		  'price_full' => '',
		  'payment_method' => '',
		  'payment_received' => '',
		  'mailchimp_list_id' => '',
		  'google_calendar_id' => '',
		  'google_calendar_event_id' => '',
		  'allow_sms' => '',
		  'status' => 'booked',
		  'date_created' => '2020-01-01 00:00:00',
		  'date_modified' => '2020-01-01 00:00:00',
		);

		if ( isset ( $data['id'] ) ) {
			$instance->id = $data['id'];
		}
		$data = array_merge( $fixture_data, $data );

		$instance->appointment_type = $appointment_type;
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
