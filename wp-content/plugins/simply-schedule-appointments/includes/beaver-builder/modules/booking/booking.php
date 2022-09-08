<?php

/**
 * This is an example module with only the basic
 * setup necessary to get it working.
 *
 * @class SSABookingModule
 */
class SSABookingModule extends FLBuilderModule {

    /**
     * Constructor function for the module. You must pass the
     * name, description, dir and url in an array to the parent class.
     *
     * @method __construct
     */
    public function __construct()
    {
        parent::__construct(array(
            'name'          => __('Booking', 'simply-schedule-appointments'),
            'description'   => __('Show an appointment booking form.', 'simply-schedule-appointments'),
            'category'		=> __('Simply Schedule Appointments', 'simply-schedule-appointments'),
            'dir'           => ssa()->dir( 'includes/beaver-builder/modules/booking/' ),
            'url'           => ssa()->url( 'includes/beaver-builder/modules/booking/'),
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled'       => true, // Defaults to true and can be omitted.
        ));
    }
}

$appointment_types = ssa()->appointment_type_model->query( array(
    'number' => -1,
    'status' => 'publish',
) );

$map_appointment_type_ids_to_labels = wp_list_pluck( $appointment_types, 'title', 'slug' );
$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );
$map_appointment_type_ids_to_labels[''] = 'All';
$map_appointment_type_ids_to_labels = array_reverse( $map_appointment_type_ids_to_labels, true );


/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('SSABookingModule', array(
    'general'       => array( // Tab
        'title'         => __('General', 'simply-schedule-appointments'), // Tab title
        'sections'      => array( // Tab Sections
            'general'       => array( // Section
                'title'         => __('Options', 'simply-schedule-appointments'), // Section Title
                'fields'        => array( // Section Fields
                    'appointment_type'     => array(
                        'type'          => 'select',
                        'label'         => __( 'Appointment Type', 'simply-schedule-appointments' ),
                        'default'       => '',
                        'options'       => $map_appointment_type_ids_to_labels
                    ),
                )
			),
			'style'       => array( // Section
                'title'         => __('Styles', 'simply-schedule-appointments'), // Section Title
                'fields'        => array( // Section Fields
					'accent_color' => array(
						'type'          => 'color',
						'label'         => __( 'Accent Color', 'simply-schedule-appointments' ),
						'show_reset'    => true,
						'show_alpha'    => true
					),
					'background_color' => array(
						'type'          => 'color',
						'label'         => __( 'Background Color', 'simply-schedule-appointments' ),
						'show_reset'    => true,
						'show_alpha'    => true
					),
					'font_family' => array(
						'type'          => 'font',
						'label'         => __( 'Font Family', 'simply-schedule-appointments' ),
					),	
					'padding' => array(
						'type'   => 'unit',
						'label'  => __( 'Padding', 'simply-schedule-appointments' ),
						'units'  => array( 'px', 'em', 'rem', 'vw', '%' ),
						'slider' => true,
					),									
				)
            )
        )
    )
));
