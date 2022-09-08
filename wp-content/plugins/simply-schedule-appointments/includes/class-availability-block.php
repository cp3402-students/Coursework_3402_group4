<?php
/**
 * Simply Schedule Appointments Availability Block.
 *
 * @since   3.6.10
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Availability Block.
 *
 * @since 3.6.10
 */
use League\Period\Period;

class SSA_Availability_Block {
	/**
	 * Parent plugin class.
	 *
	 * @since 3.6.10
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	protected $period;
	protected $type;
	protected $subtype;

	protected $available_block_id; // if it matches something in the database. We will need to cascade-delete it when reconciling with others. If no

	protected $appointment_id;
	protected $appointment_type_id;
	protected $staff_id;
	protected $calendar_id;

	protected $is_available;
	protected $is_calculated;

	protected $availability_score;

	protected $capacity_available = SSA_Constants::CAPACITY_MAX;
	protected $capacity_reserved = 0;

	protected $capacity_reserved_delta = 0;



	protected $buffer_available = SSA_Constants::CAPACITY_MAX;
	protected $buffer_reserved = 0;

	protected $buffer_reserved_delta = 0;

	/**
	 * Constructor.
	 *
	 * @since  3.6.10
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct() {
		
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
		if ( ! property_exists( $this, $field ) ) {
			throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}

		return $this->$field;
	}

	/**
	 * Magic setter for our object.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $field Field to set.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __set( $field, $value ) {
		if ( ! property_exists( $this, $field ) ) {
			throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}

		$this->$field = $value;
	}

	private function get_clone() {
		$instance = clone $this;
		return $instance;
	}

	public function get_period() {
		return $this->period;
	}
	
	public function set_period( $period ) {
		$clone = $this->get_clone();
		$clone->period = $period;

		return $clone;
	}

	public function binarize( $gte_threshold = 1 ) {
		$clone = $this->get_clone();

		if( $clone->capacity_available >= $gte_threshold ) {
			$clone->capacity_available = SSA_Constants::CAPACITY_MAX;
			$clone->capacity_reserved = 0;
		} else {
			$clone->capacity_available = 0;
			$clone->capacity_reserved = SSA_Constants::CAPACITY_MAX;
		}
		
		if( $clone->buffer_available >= $gte_threshold ) {
			$clone->buffer_available = SSA_Constants::CAPACITY_MAX;
			$clone->buffer_reserved = 0;
		} else {
			$clone->buffer_available = 0;
			$clone->buffer_reserved = SSA_Constants::CAPACITY_MAX;
		}

		return $clone;
	}

	public function set_capacity_reserved( $capacity_reserved ) {
		if ( $this->capacity_reserved === $capacity_reserved ) {
			return $this;
		}

		$clone = $this->get_clone();
		$clone->capacity_reserved = $capacity_reserved;

		return $clone;
	}

	public function set_capacity_available( $capacity_available ) {
		if ( $this->capacity_available === $capacity_available ) {
			return $this;
		}
		
		$clone = $this->get_clone();
		$clone->capacity_available = $capacity_available;

		return $clone;
	}

	public function set_buffer_reserved( $buffer_reserved ) {
		if ( $this->buffer_reserved === $buffer_reserved ) {
			return $this;
		}

		$clone = $this->get_clone();
		$clone->buffer_reserved = $buffer_reserved;

		return $clone;
	}

	public function set_buffer_available( $buffer_available ) {
		if ( $this->buffer_available === $buffer_available ) {
			return $this;
		}
		
		$clone = $this->get_clone();
		$clone->buffer_available = $buffer_available;

		return $clone;
	}

	public function is_before( SSA_Availability_Block $another_block ) {
		return $this->period->isBefore( $another_block->period );
	}
	public function is_before_period( Period $period ) {
		return $this->period->isBefore( $period );
	}

	public function is_after( SSA_Availability_Block $another_block ) {
		return $this->period->isAfter( $another_block->period );
	}
	public function is_after_period( Period $period ) {
		return $this->period->isAfter( $period );
	}

	public function contains( SSA_Availability_Block $another_block ) {
		return $this->period->contains( $another_block->period );
	}

	public function overlaps( SSA_Availability_Block $another_block ) {
		return $this->period->overlaps( $another_block->period );
	}
	public function overlaps_period( Period $another_period ) {
		return $this->period->overlaps( $another_period );
	}

	public function abuts( SSA_Availability_Block $another_block ) {
		return $this->period->abuts( $another_block->period );
	}
	public function abuts_period( Period $another_period ) {
		return $this->period->abuts( $another_period );
	}

	public function intersect_period( SSA_Availability_Block $another_block ) {
		return $this->period->intersect( $another_block->period );
	}

	public function diff_periods( SSA_Availability_Block $another_block ) {
		return $this->period->diff( $another_block->period );
	}

	public function gap_period( SSA_Availability_Block $another_block ) {
		return $this->period->gap( $another_block->period );
	}

	public function can_merge( SSA_Availability_Block $another_block ) {
		if ( ! $this->abuts( $another_block ) ) {
			return false;
		}

		if ( $this == $another_block->set_period( $this->get_period() ) ) {
			return true;
		}

		return false;
	}

	public function merge( SSA_Availability_Block $another_block ) {
		return $this->set_period(
			$this->get_period()->merge(
				$another_block->get_period()
			)
		);
	}
}
