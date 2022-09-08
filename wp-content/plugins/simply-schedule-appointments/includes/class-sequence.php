<?php
/**
 * Simply Schedule Appointments Sequence.
 *
 * @since   3.6.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Sequence.
 *
 * @since 3.6.3
 */

use League\Period\Period;

class SSA_Sequence implements ArrayAccess, Countable, Iterator {

	protected $ssa_periods = array();
	protected $count = 0;
	protected $position = 0;

	/**
	 * Constructor.
	 *
	 * @since  3.6.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $ssa_periods = array() ) {
		if ( is_a( $ssa_periods, 'SSA_Period' ) ) {
			$ssa_periods = array( $ssa_periods );
		}
		
		$this->ssa_periods = $ssa_periods;
	}

	public function raw_periods() {
		$raw_periods = array();
		foreach ($this->ssa_periods as $key => $ssa_period) {
			$raw_period = $ssa_period->get_raw_period();
			if ( ! empty( $raw_period ) ) {
				$raw_periods[] = $raw_period;
			}
		}

		return $raw_periods;
	}

	public function get_ssa_periods() {
		return $this->ssa_periods;
	}

	/**
	 * Returns an instance sorted according to the given comparison callable
	 * but does not maintain index association.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the sorted ssa_periods. The key are re-indexed
	 */
	public function sorted(callable $compare = null) {
		if ( null === $compare ) {
			$compare = array( $this, 'sort_by_raw_start_date' );
		}
		$ssa_periods = $this->ssa_periods;
		usort($ssa_periods, $compare);
		if ($ssa_periods === $this->ssa_periods) {
			return $this;
		}

		return new self( $ssa_periods );
	}


	/**
	 * Filters the sequence according to the given predicate.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the interval which validate the predicate.
	 */
	public function filter( callable $predicate ) {
		$ssa_periods = array_filter( $this->ssa_periods, $predicate, ARRAY_FILTER_USE_BOTH );
		if ( $ssa_periods === $this->ssa_periods ) {
			return $this;
		}

		return new self( $ssa_periods );
	}

	
	public function raw_boundaries() {
		if ( empty( $this->ssa_periods ) ) {
			return null;
		}

		$sequence = $this->sorted( array( $this, 'sort_by_buffered_start_date' ) );
		$ssa_periods = $sequence->get_ssa_periods();

		$start_date = $ssa_periods[0]->get_raw_period()->getStartDate();

		$end_date = array_pop( $ssa_periods )->get_raw_period()->getEndDate();

		return new Period( $start_date, $end_date );
	}

	
	public function buffered_boundaries() {
		if ( empty( $this->ssa_periods ) ) {
			return null;
		}

		$sequence = $this->sorted( array( $this, 'sort_by_buffered_start_date' ) );
		$ssa_periods = $sequence->get_ssa_periods();

		$start_date = $ssa_periods[0]->get_full_buffered_period()->getStartDate();

		$end_date = array_pop( $ssa_periods )->get_full_buffered_period()->getEndDate();

		return new Period( $start_date, $end_date );
	}

	/**
	 * Sorts two Interval instance using their start datepoint.
	 */
	private function sort_by_raw_start_date(SSA_Period $interval1, SSA_Period $interval2) {
		$a = $interval1->get_raw_period()->getStartDate();
		$b = $interval2->get_raw_period()->getStartDate();

		if ( $a == $b ) {
			return 0;
		} else if ( $a < $b ) {
			return -1;
		} else if ( $a > $b ) {
			return 1;
		}

		return null;
	}

	/**
	 * Sorts two Interval instance using their start datepoint.
	 */
	private function sort_by_buffered_start_date(SSA_Period $interval1, SSA_Period $interval2) {
		$a = $interval1->get_full_buffered_period()->getStartDate();
		$b = $interval2->get_full_buffered_period()->getStartDate();

		if ( $a == $b ) {
			return 0;
		} else if ( $a < $b ) {
			return -1;
		} else if ( $a > $b ) {
			return 1;
		}

		return null;
	}


	/* ArrayAccess */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->ssa_periods[] = $value;
		} else {
			$this->ssa_periods[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->ssa_periods[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->ssa_periods[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->ssa_periods[$offset]) ? $this->ssa_periods[$offset] : null;
	}


	/* Countable */
	public function count() {
		return $this->count;
	}


	/* Iterator */
	public function rewind() {
		$this->position = 0;
	}

	public function current() {
		return $this->ssa_periods[$this->position];
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		return isset($this->ssa_periods[$this->position]);
	}
}
