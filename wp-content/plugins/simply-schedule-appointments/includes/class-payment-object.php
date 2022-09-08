<?php
/**
 * Simply Schedule Appointments Appointment Object.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Object.
 *
 * @since 0.0.3
 */
class SSA_Payment_Object {
	protected $id = null;
	protected $model = null;
	protected $data = null;
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
		$this->plugin = Simply_Schedule_Appointments::get_instance();
		$this->model = $this->plugin->payment_model;

		if ( $id === 'transient' ) {		
			return;
		}

		$this->id = $id;

		$this->get();
		if ( empty( $this->data['id'] ) || $this->id != $this->data['id'] ) {
			throw new Exception("Unable to create SSA_Payment from id $id");
		}
	}

	/**
	 * Factory function to create with explicit data
	 *
	 * @param array $data
	 * @return SSA_Payment
	 * @author 
	 **/
	public static function create( array $data ) {
		$appointment_object = new SSA_Payment( 'transient' );
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
		switch ( $field ) {
			case 'id':
			case 'data':
				return $this->$field;
			default:
				if ( isset( $this->data[$field] ) ) {
					return $this->data[$field];
				}
				
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	public function get( $recursive = -1 ) {
		if ( $recursive > $this->recursive_fetched ) {
			if ( null === $this->data ) {
				$this->data = array();
			}
			$this->data = array_merge( $this->data, $this->model->get( $this->id, $recursive ) );
			$this->recursive_fetched = $recursive;
		}
	}

	public function update( $data ) {
		return $this->plugin->payment_model->update(
			$this->id,
			$data
		);
	}

	public function get_data( $recursive = -1, $fetch_fields = array() ) {
		$this->get( $recursive );

		if ( $recursive >= 0 ) {
			// if ( !isset( $fetch_fields['public_edit_url'] ) ) {
			// 	$fetch_fields['public_edit_url'] = true;
			// }
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
			if ( ! method_exists( $this, $method_name ) ) {
				throw new SSA_Exception(__CLASS__ . "->" . $method_name . "() not implemented", 1);
			}

			$this->$method_name( $fetch_options );
		}
	}

	// public function fetch_public_edit_url( $atts = array() ) {
	// 	$this->data['public_edit_url'] = $this->get_public_edit_url();
	// }

	public function get_appointment() {
		$appointment = new SSA_Appointment_Object( $this->appointment_id );
		return $appointment;
	}
}
