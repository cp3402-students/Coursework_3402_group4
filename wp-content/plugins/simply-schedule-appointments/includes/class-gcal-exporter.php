<?php
/**
 * Simply Schedule Appointments Gcal Exporter.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Gcal Exporter.
 *
 * @since 0.0.3
 */
class SSA_Gcal_Exporter {
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

	}

	public function sanitize_string( $string ) {
		$string = wp_strip_all_tags($string);
		$string = str_replace("\n\n\n", "\n\n",$string);

		return $string;
	}

	public function get_add_link_from_appointment( $appointment, $template = 'customer' ) {
		$developer_settings = ssa()->developer_settings->get();

		$link = 'https://www.google.com/calendar/event';
		$link = add_query_arg(
			array(
				'action'   => 'TEMPLATE',
				'text'     => urlencode( $appointment->get_title( 'customer' ) ),
				'dates'    => date( 'Ymd', $appointment->start_date_timestamp ) . 'T' . date( 'His', $appointment->start_date_timestamp ) . 'Z' . '/' . date( 'Ymd', $appointment->end_date_timestamp ) . 'T' . date( 'His', $appointment->end_date_timestamp ) . 'Z',
				'details'  => urlencode( $this->sanitize_string( $appointment->get_description( 'customer' ) ) ),
				'location' => ! empty( $developer_settings['beta_calendar_events'] ) ? urlencode( $appointment->get_location( 'customer' ) ) : '',
				'trp'      => false,
				'sprop'    => 'name:',
			),
			$link
		);

		return $link;
	}
}
