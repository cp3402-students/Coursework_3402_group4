<?php

/**
 * This is an example module with only the basic
 * setup necessary to get it working.
 *
 * @class SSAUpcomingAppointmentsModule
 */
class SSAUpcomingAppointmentsModule extends FLBuilderModule {

    /**
     * Constructor function for the module. You must pass the
     * name, description, dir and url in an array to the parent class.
     *
     * @method __construct
     */
    public function __construct()
    {
        parent::__construct(array(
            'name'          => __('Upcoming Appointments', 'simply-schedule-appointments'),
            'description'   => __('Show a list of upcoming appointments to a logged-in user.', 'simply-schedule-appointments'),
            'category'		=> __('Simply Schedule Appointments', 'simply-schedule-appointments'),
            'dir'           => ssa()->dir( 'includes/beaver-builder/modules/upcoming-appointments/' ),
            'url'           => ssa()->url( 'includes/beaver-builder/modules/upcoming-appointments/'),
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled'       => true, // Defaults to true and can be omitted.
        ));
    }
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('SSAUpcomingAppointmentsModule', array(
    'general'       => array( // Tab
        'title'         => __('General', 'simply-schedule-appointments'), // Tab title
        'sections'      => array( // Tab Sections
            'general'       => array( // Section
                'title'         => __('Options', 'simply-schedule-appointments'), // Section Title
                'fields'        => array( // Section Fields
                    'no_results_message'     => array(
                        'type'          => 'text',
                        'label'         => __('Message to display if customer has no upcoming appointments', 'simply-schedule-appointments'),
                        'default'       => __( 'No upcoming appointments', 'simply-schedule-appointments' ),
                    ),
                )
            )
        )
    )
));
