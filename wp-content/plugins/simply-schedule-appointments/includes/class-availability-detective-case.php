<?php
/**
 * Simply Schedule Appointments Availability Detective Case.
 *
 * @since   4.3.8-beta7
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Availability Detective Case.
 *
 * @since 4.3.8-beta7
 */
class SSA_Availability_Detective_Case {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.3.8-beta7
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $appointment_type;
	protected $period;
	protected $appointment;

	public $all_suspects = array(
		'appointment_type.min_booking_notice',
		'appointment_type.max_booking_notice',
		'appointment_type.booking_window',
		'appointment_type.availability_window',
		'appointment_type.max_per_day',

		'appointment_type.buffers',
		'appointment_type.capacity',

		'staff',
		// 'staff.appointment_type.buffers',
		// 'staff.appointment_type.capacity',
		
		'blackout_dates',
		// 'blackout_dates.staff',
		// 'blackout_dates.staff.123',
		// 'blackout_dates.staff.456',
		
		'google_calendar',
		// 'google_calendar.staff',
		// 'google_calendar.staff.123',
		// 'google_calendar.staff.456',
	);

	public $required = array(
		'appointment_type'
	);
	public $already_investigated = array();
	public $sequestered = array();
	public $suspects = array(
		'blackout_dates',
		'google_calendar',
	);
	public $prime_suspects = array();
	public $interrogation_room = array();
	public $cleared = array();
	public $culprits = array();

	/**
	 * Constructor.
	 *
	 * @since  4.3.8-beta7
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct(
		SSA_Appointment_Type_Object $appointment_type,
		Period $period,
		SSA_Appointment_Object $appointment
	) {
		$this->appointment_type = $appointment_type;
		$this->period = $period;
		$this->appointment = $appointment;
	}

	private function clone_instance() {
		$clone = clone $this;

		return $clone;
	}

	private function subinvestigation() {
		$case = $this->clone_instance();
		$case->required = array();
		$case->sequestered = array();
		$case->suspects = array();
		$case->prime_suspects = array();
		$case->interrogation_room = array();
		$case->cleared = array();
		$case->culprits = array();

		return $case;
	}

	public function prepare_interrogation_room() {
		$case = $this->clone_instance();

		if ( ! empty( $case->required ) ) {
			if ( empty( $case->cleared ) && empty( $case->culprits ) ) {
				// we haven't tested the required suspects yet
				$case->interrogation_room = $case->required;
				return $case;
			}
		}

		$culprits_that_are_required = array_intersect( $case->culprits, $case->required );
		if ( ! empty( $culprits_that_are_required ) ) {
			// if required suspects are guilty then don't continue investigation
			$case->interrogation_room = array();
			return $case;
		}

		if ( empty( $case->suspects ) ) {
			// nobody left to interrogate
			$case->interrogation_room = array();
			return $case;
		}

		$next_suspects = array( array_shift( $case->suspects ) );
		// solo interrogation
		$case->interrogation_room = array_merge( $case->required, $next_suspects );
// $case->sequestered = array_diff( $case->suspects, $case->required, $case->cleared );
		// group interrogation
		// TODO: test suspects with all previously cleared modules
		// $case->interrogation_room = array_merge( $case->required, $case->cleared, $next_suspects );

		return $case;
	}

	public function clear_suspects( $suspects ) {
		if ( ! is_array( $suspects ) ) {
			$suspects = array( $suspects );
		}
		$case = $this->clone_instance();
		$case->cleared = array_unique( array_merge( $case->cleared, $suspects ) );
		$case->suspects = array_diff( $case->suspects, $case->cleared );

		return $case;
	}

	public function get_suspect_group( $group_prefix ) {
		$suspect_group = array_filter( $this->all_suspects, function( $suspect ) use ( $group_prefix ) {
			if ( strpos( $suspect, $group_prefix.'.' ) === 0 ) {
				return true;
			}

			return false;
		} );

		return $suspect_group;
	}

	public function get_uncleared_suspects_in_interrogation_room() {
		$uncleared_suspects = array_diff( $this->interrogation_room, $this->cleared, $this->culprits );

		return $uncleared_suspects;
	}

	public function clear_suspect_group( $group_prefix ) {
		$case = $this->clone_instance();

		$suspect_group = $this->get_suspect_group( $group_prefix );
		
		$case->cleared = array_merge( $case->cleared, array( $group_prefix ), $suspect_group );
		$case->suspects = array_diff( $case->suspects, array( $group_prefix ), $suspect_group );

		return $case;
	}
	
	public function mark_culprits( $suspects ) {
		if ( ! is_array( $suspects ) ) {
			$suspects = array( $suspects );
		}
		$case = $this->clone_instance();
		$case->culprits = array_unique( array_merge( $case->culprits, $suspects ) );
		$case->suspects = array_diff( $case->suspects, $suspects );

		return $case;
	}

	public function investigate() {
		$case = $this->prepare_interrogation_room();
		if ( empty( $case->interrogation_room ) ) {
			return $case;
		}

		$interrogation = new SSA_Availability_Query(
			$case->appointment_type,
			$case->period,
			$case->get_interrogation_room_query_args()
		);
		if ( $interrogation->is_prospective_appointment_bookable( $case->appointment ) ) {			
			$case = $case->declare_innocent();
		} else {
			$new_suspects = $case->get_uncleared_suspects_in_interrogation_room();
			$case = $case->declare_guilty();
			foreach ($new_suspects as $group) {
				if ( in_array( $group, $case->already_investigated ) ) {
					continue;
				}
				$subgroup = $case->get_suspect_group( $group );
				if ( empty( $subgroup ) ) {
					continue;
				}
				
				$closer_look = $case->subinvestigation();
				$closer_look->already_investigated = array( $group );
				$closer_look->required = array( $group );
				$closer_look->sequestered = array_merge( $case->cleared, $case->suspects, $case->culprits );
				$closer_look->suspects = $subgroup;
				$closer_look = $closer_look->investigate();
				if ( ! empty( $closer_look->culprits ) ) {
					$case = $case->mark_culprits( $closer_look->culprits );
				}
			}
		}

		$case = $case->investigate();
		return $case;
	}

	public function declare_innocent() {
		$case = $this->clone_instance();
		$new_suspects = $this->get_uncleared_suspects_in_interrogation_room();
		$case = $case->clear_suspects( $new_suspects );
		// foreach ($new_suspects as $new_suspect) {
		// 	$case = $case->clear_suspect_group( $new_suspect );
		// }

		return $case;
	}

	public function declare_guilty() {
		$case = $this->clone_instance();
		$new_suspects = $this->get_uncleared_suspects_in_interrogation_room();
		$case = $case->mark_culprits( $new_suspects );

		return $case;
	}

	public function get_interrogation_room_query_args() {
		$cache_args = array(
			'cache_level_read' => false,
			'cache_level_write' => false,
		);
		$default_false = array_fill_keys( array_merge( $this->suspects, $this->cleared, $this->culprits ), false );
		$sequestered_false = array_fill_keys( $this->sequestered, false );
		$interrogating_true = array_fill_keys( $this->interrogation_room, true );
		
		$query_args = array_merge( $default_false, $sequestered_false, $interrogating_true, $cache_args );

		return $query_args;
	}

	public function investigate_methodically() {

	}

	public function investigate_randomly() {

	}
}
