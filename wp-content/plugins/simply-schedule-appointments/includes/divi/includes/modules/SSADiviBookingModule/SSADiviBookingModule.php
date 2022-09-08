<?php

class SSA_Divi_Booking_Module extends ET_Builder_Module {

	public $slug       = 'ssa_divi_booking_module';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://simplyscheduleappointments.com',
		'author'     => 'Simply Schedule Appointments',
		'author_uri' => 'https://simplyscheduleappointments.com',
	);

	public function init() {
		$this->name       = esc_html__( 'Appointment Booking', 'simply-schedule-appointments' );
		$this->plural     = esc_html__( 'Appointment Bookings', 'simply-schedule-appointments' );
		$this->slug       = 'ssa_divi_booking_module';		
		// $this->icon       = '📅';
	}

	public function get_fields() {
		$appointment_types = ssa()->appointment_type_model->query( array(
			'number' => -1,
			'status' => 'publish',
		) );
		
		$map_appointment_type_ids_to_labels = wp_list_pluck( $appointment_types, 'title', 'slug' );
		$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );
		$map_appointment_type_ids_to_labels[''] = 'All';
		$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );
				
		return array(
			'appointment_type' => array(
				'label'           => __( 'Appointment Type', 'simply-schedule-appointments' ),
				'type'            => 'select',
				'options'         => $map_appointment_type_ids_to_labels,
				'tab_slug'        => 'general',
				'toggle_slug'     => 'booking_type'
			),
			'accent_color' => array(
				'label'           => __( 'Accent Color', 'simply-schedule-appointments' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'colors'
			),
			'background_color' => array(
				'label'           => __( 'Background Color', 'simply-schedule-appointments' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'colors'
			),
			'font_family' => array(
				'label'               => __( 'Font Family', 'simply-schedule-appointments' ),
				'type'                => 'font',
				'disable_toggle'      => true,
				'hide_font_size'      => true,
				'hide_letter_spacing' => true,
				'hide_line_height'    => true,
				'hide_text_color'     => true,
				'hide_text_shadow'    => true,
				'tab_slug'            => 'advanced',
				'toggle_slug'         => 'font_family'
			),
			'padding' => array(
				'label'           => __( 'Padding', 'simply-schedule-appointments' ),
				'type'            => 'range',
				'disable_toggle'  => true,
				'range_settings'  => array(
					'min' => 0,
					'max' => 100
				),
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'padding'
			),
			'padding_css_unit' => array(
				'label'           => __( 'Padding Unit', 'simply-schedule-appointments' ),
				'type'            => 'select',
				'options'         => array(
					'px'  => 'px',
					'em'  => 'em',
					'rem' => 'rem',
					'vw'  => 'vw',
					'%'   => '%',
				),
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'padding'
			),
		);
	}

	public function get_advanced_fields_config() {
		return array(
			'background' => false,
			'borders'    => false,
			'box_shadow' => false,
			'button'     => false,
			'filters'    => false,
			'transform'  => false,
			'animation'  => false,
			'margin_padding' => false,
			'max_width'  => false,
			'fonts'      => false,
			'text'       => false,
		);
	}

	public function get_settings_modal_toggles(){
		return array(
			'general' => array(
				'toggles' => array(
					'booking_type' => array(
						'title' => esc_html__( 'Booking Type', 'simply-schedule-appointments' )
					)
				)
			),
		    'advanced' => array(
		        'toggles' => array(
		            'font_family'  => array(
						'title' => esc_html__( 'Font Family', 'simply-schedule-appointments' ),
					),
		            'colors'  => array(
						'title' => esc_html__( 'Colors', 'simply-schedule-appointments' ),
					),
		            'padding'      => array(
		                'title'    => esc_html__( 'Padding', 'simply-schedule-appointments' ),
		            ),
		        ),
		    ),
		);		
	}

	public function parse_props_to_shortcode_params( $props = array() ) {
		$args = array();

		if( $props['appointment_type'] ) {
			$args['type'] = $props['appointment_type'];
		}

		if( $props['accent_color'] ) {
			$args['accent_color'] = ltrim( $props['accent_color'], '#' );
		}

		if( $props['background_color'] ) {
			$args['background'] = ltrim( $props['background_color'], '#' );
		}

		if( $props['font_family'] ) {
			$font_props = explode( '|', $props['font_family'] );
			$args['font'] = $font_props[0];
		}

		if( $props['padding'] ) {
			$unit = isset( $props['padding_css_unit'] ) && $props['padding_css_unit'] ? $props['padding_css_unit'] : 'px';
			$args['padding'] = $props['padding'] . $unit;
		}

		return $args;
	}
	
	public function render( $attrs, $content, $render_slug ) {
		$attrs = $this->parse_props_to_shortcode_params( $this->props );
		ob_start(); ?>
		<div class="divi-module-ssa-booking-wrapper">
			<div class="ssa-booking">
				<?php echo ssa()->shortcodes->ssa_booking( $attrs ); ?>
			</div>
		</div>
		<?php

		$html = ob_get_clean();
			
		return $html;
	}
}

new SSA_Divi_Booking_Module;
