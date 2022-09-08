<?php
/**
 * Simply Schedule Appointments Period Object.
 *
 * @since   3.6.2
 * @package Simply_Schedule_Appointments
 */

use League\Period\Period;

/**
 * Simply Schedule Appointments Period Object.
 *
 * @since 3.6.2
 */
class SSA_Period {
	private $raw_start_date;
	private $raw_end_date;

	private $appointment_type_object;
	private $appointment_type_id;

	private $appointment_object;
	private $appointment_id;

	/* calculated variables */
	private $buffer_before_period;
	private $raw_period;
	private $buffer_after_period;
	private $full_buffered_period;


	/**
	 * Constructor.
	 *
	 * @since  3.6.2
	 */
	public function __construct() {
		
	}

	public static function create_from_dates( $start_date, $end_date ) {
		$period = new Period( $start_date, $end_date );
		$ssa_period = self::create_from_period( $period );

		return $ssa_period;
	}

	public static function create_from_appointment_id( $appointment_id ) {
		$ssa_period = new self;
		$ssa_period->set_appointment_object( $appointment_id );

		return $ssa_period;
	}

	public static function create_from_appointment_type_duration( $start_date, $appointment_type ) {
		$ssa_period = new self;
		$ssa_period->raw_start_date = ssa_datetime( $start_date );
		$ssa_period->set_appointment_type_object( $appointment_type );

		return $ssa_period;
	}

	public static function create_from_period( Period $period, $appointment_type_object = null ) {
		$ssa_period = new self;
		$ssa_period->raw_period = $period;

		if ( ! empty( $appointment_type_object ) ) {
			$this->set_appointment_type_object( $appointment_type_object );
		} else {
			$ssa_period->buffer_before_period = false;
			$ssa_period->buffer_after_period = false;
			$ssa_period->full_buffered_period = $ssa_period->raw_period;
		}

		return $ssa_period;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  3.6.2
	 */
	public function hooks() {

	}


	public function get_appointment_type_object() {
		if ( ! empty( $this->appointment_type_object ) ) {
			return $this->appointment_type_object;
		}
	}

	/**
	 * calculate_raw_period
	 * 
	 * update raw period based on other instance variables
	 *
	 * @return void
	 * @author 
	 **/
	public function get_raw_period() {
		if ( null !== $this->raw_period ) {
			return $this->raw_period;
		}

		$this->calculate_raw_period_by_instance_variables();
		$this->calculate_raw_period_by_appointment_id();
		$this->calculate_raw_period_by_appointment_type_id();

		return $this->raw_period;
	}

	public function get_full_buffered_period() {
		if ( null !== $this->full_buffered_period ) {
			return $this->full_buffered_period;
		}

		$buffer_before_period = $this->get_buffer_before_period();
		if ( empty( $buffer_before_period ) ) {
			$start_date = $this->get_raw_period()->getStartDate();
		} else {
			$start_date = $buffer_before_period->getStartDate();
		}

		$buffer_after_period = $this->get_buffer_after_period();
		if ( empty( $buffer_after_period ) ) {
			$end_date = $this->get_raw_period()->getEndDate();
		} else {
			$end_date = $buffer_after_period->getEndDate();
		}

		$this->full_buffered_period = new Period( $start_date, $end_date );

		return $this->full_buffered_period;
	}

	public function get_buffer_before_period() {
		if ( null !== $this->buffer_before_period ) {
			return $this->buffer_before_period;
		}

		$buffer_before = $this->get_appointment_type_object()->buffer_before;
		if ( empty( $buffer_before ) ) {
			$this->buffer_before_period = false;
		} else {
			$buffer_before = '-' . absint( $buffer_before ) . ' MIN';
			$calculated_period = new Period( $this->get_raw_period()->getStartDate(), $this->get_raw_period()->getStartDate() );
			$calculated_period = $calculated_period->moveStartDate( $buffer_before );
			$this->buffer_before_period = $calculated_period;
		}

		return $this->buffer_before_period;
	}

	public function get_buffer_after_period() {
		if ( null !== $this->buffer_after_period ) {
			return $this->buffer_after_period;
		}

		$buffer_after = $this->get_appointment_type_object()->buffer_after;
		if ( empty( $buffer_after ) ) {
			$this->buffer_after_period = false;
		} else {
			$buffer_after = '+' . absint( $buffer_after ) . ' MIN';
			$calculated_period = new Period( $this->get_raw_period()->getEndDate(), $this->get_raw_period()->getEndDate() );
			$calculated_period = $calculated_period->moveEndDate( $buffer_after );
			$this->buffer_after_period = $calculated_period;
		}

		return $this->buffer_after_period;
	}

	private function set_appointment_object( $appointment ) {
		if ( null !== $this->appointment_object ) {
			throw new Exception( 'SSA_Period ivars can\'t be modified' );
		}

		$appointment = SSA_Appointment_Object::instance( $appointment );

		$this->appointment_object = $appointment;
		$this->appointment_id = $appointment->id;

		$this->set_appointment_type_object( $appointment->get_appointment_type() );
	}

	private function set_appointment_type_object( $appointment_type ) {
		if ( null !== $this->appointment_type_object ) {
			throw new Exception( 'SSA_Period ivars can\'t be modified' );
		}

		$appointment_type = SSA_Appointment_Type_Object::instance( $appointment_type );

		$this->appointment_type_object = $appointment_type;
		$this->appointment_type_id = $appointment_type->id;
	}

	/**
	 * clear_calculated_variables
	 * 
	 * update dependent variables
	 *
	 * @return void
	 * @author 
	 **/
	public function clear_calculated_variables() {
		$this->raw_period = null;
		$this->buffer_before_period = null;
		$this->buffer_after_period = null;
	}


	private function calculate_raw_period_by_instance_variables() {
		if ( null !== $this->raw_period ) {
			return; // already calculated
		}

		if ( empty( $this->raw_start_date ) || empty( $this->raw_end_date ) ) {
			return;
		}

		$this->raw_period = new Period(
			$this->raw_start_date,
			$this->raw_end_date
		);
	}

	private function calculate_raw_period_by_appointment_id() {
		if ( null !== $this->raw_period ) {
			return; // already calculated
		}

		if ( empty( $this->appointment_object ) ) {
			return;
		}

		$this->raw_period = new Period(
			$this->appointment_object->start_date,
			$this->appointment_object->end_date
		);
	}

	private function calculate_raw_period_by_appointment_type_id() {
		if ( null !== $this->raw_period ) {
			return; // already calculated
		}

		if ( null !== $this->appointment_object ) {
			return;
		}

		if ( null === $this->appointment_type_object ) {
			return;
		}

		if ( null === $this->raw_start_date ) {
			return;
		}

		$duration = $this->appointment_type_object->duration;

		$calculated_end_date = ssa_datetime( $this->raw_start_date )->add( new DateInterval( 'PT'.$duration.'M' ) );

		$this->raw_period = new Period(
			$this->raw_start_date,
			$calculated_end_date
		);
	}

	public function log() {
		ssa_debug_log( '-------------------- SSA_Period --------------------' );
		ssa_debug_log( $this->appointment_id, 1, 'appointment_id:' );
		ssa_debug_log( $this->appointment_type_id, 1, 'appointment_type_id:' );
		ssa_debug_log( $this->get_buffer_before_period(), 1, 'buffer_before_period:' );
		ssa_debug_log( $this->get_raw_period(), 1, 'raw_period:' );
		ssa_debug_log( $this->get_buffer_after_period(), 1, 'buffer_after_period:' );
		ssa_debug_log( $this->get_full_buffered_period(), 1, 'full_buffered_period:' );
		ssa_debug_log( '--------------------------------------------------------------------------------' );
	}
}
