<?php
/**
 * Calendar Event Details (Customer)
 * *
 * This template can be overridden by copying it to wp-content/themes/your-theme/ssa/calendar-events/customer.php
 * Note: this is just the default template that is used as a starting pont.
 * Once the user makes edits in the SSA Settings interface, 
 * the template stored in the database will be used instead
 *
 * @see         https://simplyscheduleappointments.com
 * @author      Simply Schedule Appointments
 * @package     SSA/Templates
 * @version     4.3.7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
{% if Appointment.web_meeting_url %}
<?php _e( "This event has a web meeting:", 'simply-schedule-appointments' ) ?>
{{Appointment.web_meeting_url}}
{% endif %}

{% if instructions %}
<?php echo sprintf( __( 'Instructions: %s', 'simply-schedule-appointments' ), '{{ instructions|raw }}' ); ?> 
{% endif %}

{% if Appointment.AppointmentType.description %}
{{ Appointment.AppointmentType.description }}
{% endif %}

<?php _e('Need to make changes to this event?', 'simply-schedule-appointments') ?> 
{{Appointment.public_edit_url}}