<?php
/**
 * Appointment Booked (to Staff)
 * *
 * This template can be overridden by copying it to wp-content/themes/your-theme/ssa/notifications/email/text/canceled-staff.php
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
<?php  echo sprintf( __( 'Your appointment "%s" with %s has been canceled', 'simply-schedule-appointments' ), '{{ Appointment.AppointmentType.title|raw }}', '{{ Appointment.customer_information.Name }}' ); ?> 
 
<?php echo __( '*** Canceled ***', 'simply-schedule-appointments' );?> 
<?php echo __( 'Appointment Details:', 'simply-schedule-appointments' );?> 
<?php echo sprintf( __( '%s', 'simply-schedule-appointments' ), '{{ Appointment.business_start_date }}' );?> 
  
<?php echo sprintf( __( 'Type: %s', 'simply-schedule-appointments' ), '{{ Appointment.AppointmentType.title|raw }}' ); ?> 

<?php echo __( 'Customer details:' ) ?> 
{{ Appointment.customer_information_summary }}