<?php
/**
 * Elementor Upcoming Appointments Widget.
 *
 * Elementor widget that inserts a coverflow-style carousel into the page.
 *
 * @since 1.0.0
 */

class SSA_Elementor_Booking_Widget extends \Elementor\Widget_Base {

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
		return 'ssa-booking';
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
		return __( 'Schedule an Appointment', 'simply-schedule-appointments' );
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
		return [ 'ssa', 'schedule', 'calendar', 'appointments', 'simply', 'booking' ];
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
			'section_ssa_booking',
			[
				'label' => __( 'Booking', 'simply-schedule-appointments' ),
			]
		);


		$appointment_types = ssa()->appointment_type_model->query( array(
			'number' => -1,
			'status' => 'publish',
		) );

		$map_appointment_type_ids_to_labels = wp_list_pluck( $appointment_types, 'title', 'slug' );
		$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );
		$map_appointment_type_ids_to_labels[''] = 'All';
		$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );

		$this->add_control(
			'appointment_type',
			[
				'label' => __( 'Appointment Type', 'simply-schedule-appointments' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $map_appointment_type_ids_to_labels,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Styles', 'elementor' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'accent_color',
			[
				'label'   => __( 'Accent Color', 'simply-schedule-appointments' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'alpha'   => false,
			]
		);		

		$this->add_control(
			'background_color',
			[
				'label'   => __( 'Background Color', 'simply-schedule-appointments' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'alpha'   => false,
			]
		);		

		$this->add_control(
			'font_family',
			[
				'label'   => __( 'Font Family', 'simply-schedule-appointments' ),
				'type' => \Elementor\Controls_Manager::FONT,
			]
		);		

		$this->add_control(
			'padding',
			[
				'label'   => __( 'Padding', 'simply-schedule-appointments' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', '%' ],	
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1
					],
					'em' => [
						'min' => 0,
						'max' => 100,
						'step' => 1
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
						'step' => 1
					],
					'vw' => [
						'min' => 0,
						'max' => 100,
						'step' => 1
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1
					],
				],							
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
		if ( $settings['appointment_type'] === 'none' || $settings['appointment_type'] === 'all' ) {
			$settings['appointment_type'] = '';			
		}

		$attrs = array();

		if( $settings['appointment_type'] && $settings['appointment_type'] !== '' ) {
			$attrs['type'] = $settings['appointment_type'];
		}
		if( $settings['accent_color'] && $settings['accent_color'] !== '' ) {
			$attrs['accent_color'] = ltrim( $settings['accent_color'], '#');
		}
		if( $settings['background_color'] && $settings['background_color'] !== '' ) {
			$attrs['background'] = ltrim( $settings['background_color'], '#' );
		}
		if( $settings['font_family'] && $settings['font_family'] !== '' ) {
			$attrs['font'] = $settings['font_family'];
		}
		if( $settings['padding'] && $settings['padding'] !== '' ) {
			$attrs['padding'] = $settings['padding']['size'] . $settings['padding']['unit'];
		}
		
		?>
		<div class="elementor-ssa-booking-wrapper">
			<div <?php echo $this->get_render_attribute_string( 'booking' ); ?>>
				<div class="ssa-booking">
					<?php echo ssa()->shortcodes->ssa_booking( $attrs ); ?>
				</div>
			</div>
		</div>
		<?php
	}

}
