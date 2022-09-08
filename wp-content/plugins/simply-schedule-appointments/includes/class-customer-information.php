<?php
/**
 * Simply Schedule Appointments Customer Information.
 *
 * @since   3.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Customer Information.
 *
 * @since 3.1.0
 */
class SSA_Customer_Information {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.1.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  3.1.0
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
	 * @since  3.1.0
	 */
	public function hooks() {
	}

	public function get_defaults() {
		$defaults = array(
			'Name' => '',
			'Email' => '',
		);

		if ( is_user_logged_in() && ! current_user_can( 'edit_users' ) && ! current_user_can( 'ssa_manage_appointments' ) ) {
			$current_user = new WP_User( get_current_user_id() );
			if ( ! empty( $current_user ) && ! empty( $current_user->data ) ) {
				if ( ! empty( $current_user->data->display_name ) ) {
					$defaults['Name'] = $current_user->data->display_name;
				}
				if ( ! empty( $current_user->data->user_email ) ) {
					$defaults['Email'] = $current_user->data->user_email;
				}
			}
		}

		$passed_args = $this->plugin->shortcodes->get_passed_args();
		$defaults = array_merge( $defaults, $passed_args ); // Allow field values to get passed by URL
		$defaults = apply_filters( 'ssa/appointments/customer_information/get_defaults', $defaults );

		return $defaults;
	}

	public function get_phone_number_field_for_appointment_type_id($appointment_type_id) {
		$appointment_type_object = new SSA_Appointment_Type_Object($appointment_type_id);
		return $this->get_phone_number_field_for_appointment_type($appointment_type_object);
	}

	public function get_phone_number_field_for_appointment_type(SSA_Appointment_Type_Object $appointment_type_object) {
		$customer_information_fields = $appointment_type_object->custom_customer_information;
		if (empty($customer_information_fields['0']['type'])) {
			return false;
		}

		foreach ($customer_information_fields as $key => $field) {
			if ('phone' === $field['type']) {
				return $field['field'];
			}
		}

		return false;
	}

	public function get_phone_number_for_appointment_id($appointment_id) {
		$appointment_object = new SSA_Appointment_Object($appointment_id);
		return $this->get_phone_number_for_appointment($appointment_object);
	}

	public function get_phone_number_for_appointment(SSA_Appointment_Object $appointment_object) {
		$field_name = $this->plugin->customer_information->get_phone_number_field_for_appointment_type($appointment_object->get_appointment_type());
		$customer_information = $appointment_object->customer_information;
		if (!empty($customer_information[$field_name])) {
			return $customer_information[$field_name];
		}

		return false;
	}
}
