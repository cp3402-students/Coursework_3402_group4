<?php
/**
 * Elementor Upcoming Appointments Widget.
 *
 * Elementor widget that inserts a coverflow-style carousel into the page.
 *
 * @since 1.0.0
 */
class SSA_Elementor_Upcoming_Appointments_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve coverflow carousel widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ssa-upcoming-appointments';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Upcoming Appointments widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Upcoming Appointments', 'simply-schedule-appointments' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve Upcoming Appointments widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'ssa', 'schedule', 'calendar', 'appointments', 'simply' ];
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the Upcoming Appointments widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general' ];
	}

	/**
	 * Retrieve the list of scripts the Upcoming Appointments widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.3.0
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return [];
	}

	/**
	 * Retrieve the list of styles the Upcoming Appointments widget depended on.
	 *
	 * Used to set style dependencies required to run the widget.
	 *
	 * @since 1.3.0
	 * @access public
	 *
	 * @return array Widget styles dependencies.
	 */
	public function get_style_depends() {
		return [];
	}

	/**
	 * Register Upcoming Appointments widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls() {
		$this->start_controls_section(
			'section_ssa_upcoming_appointments',
			[
				'label' => __( 'Upcoming Appointments', 'simply-schedule-appointments' ),
			]
		);


		$this->add_control(
			'no_results_message',
			[
				'label' => __( 'Message to display if customer has no upcoming appointments', 'simply-schedule-appointments' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'No upcoming appointments', 'simply-schedule-appointments' ),
			]
		);



		$this->end_controls_section();

	}

	/**
	 * Render Upcoming Appointments widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="elementor-ssa-upcoming-appointments-wrapper">
			<div <?php echo $this->get_render_attribute_string( 'upcoming_appointments' ); ?>>
				<div class="ssa-upcoming-appointments">
					<?php echo ssa()->shortcodes->ssa_upcoming_appointments( array(
						'no_results_message' => $settings['no_results_message'],
					) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

}
