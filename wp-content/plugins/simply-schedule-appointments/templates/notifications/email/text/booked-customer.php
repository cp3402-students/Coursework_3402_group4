<?php
/**
 * Appointment Booked (to Customer)
 * *
 * This template can be overridden by copying it to wp-content/themes/your-theme/ssa/notifications/email/text/booked-customer.php
 * Note: this is just the default template that is used as a starting pont.
 * Once the user makes edits in the SSA Settings interface, 
 * the template stored in the database will be used instead
 *
 * @see         https://simplyscheduleappointments.com
 * @author      Simply Schedule Appointments
 * @package     SSA/Templates
 * @version     2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<?php  echo sprintf( __( 'Hi %s,', 'simply-schedule-appointments' ), '{{ Appointment.customer_information.Name }}' ); ?> 

<?php  echo sprintf( __( 'This is a confirmation of the appointment you just booked on %s', 'simply-schedule-appointments' ), '{{ Global.site_url }}' ); ?> 

<?php echo sprintf( __( 'Appointment scheduled for %s', 'simply-schedule-appointments' ), '{{ Appointment.customer_start_date }}' );?> 

{% if instructions %}
<?php echo sprintf( __( 'Instructions: %s', 'simply-schedule-appointments' ), '{{ instructions|raw }}' ); ?> 
{% endif %}

{% if Appointment.web_meeting_url %}
<?php echo sprintf( __( 'At your appointment time, join the meeting using this link: %s' ), '{{ Appointment.web_meeting_url }}' ); ?>
{% endif %}

<?php echo sprintf( __( 'Type: %s', 'simply-schedule-appointments' ), '{{ Appointment.AppointmentType.title|raw }}' ); ?> 

<?php echo __( 'Your details:' ) ?> 
{{ Appointment.customer_information_summary }}

<?php echo __( 'If you need to cancel or change your appointment, you can do so by visiting this link:', 'simply-schedule-appointments' ); ?> 
{{ Appointment.public_edit_url }}