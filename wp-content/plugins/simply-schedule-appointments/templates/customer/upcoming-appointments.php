<?php
	// $atts are defined in class-shortcodes.php
	// don't try to load this file directly, instead call ssa()->shortcodes->ssa_upcoming_appointments()

	$upcoming_appointments = ssa()->appointment_model->query( $atts );

	$settings = ssa()->settings->get();
	$date_format = SSA_Utils::localize_default_date_strings( 'F j, Y g:i a' ) . ' (T)';
?>

<div class="ssa-upcoming-appointments">
	<ul class="ssa-upcoming-appointments">
		<?php if ( ! is_user_logged_in() ): ?>
			<?php echo $atts['logged_out_message']; ?>
		<?php elseif ( empty( $upcoming_appointments ) ): ?>
			<?php echo $atts['no_results_message']; ?>
		<?php else: ?>
			<?php foreach ($upcoming_appointments as $upcoming_appointment) : ?>
				<li>
					<span class="ssa-upcoming-appointments-appointment">
						<span class="ssa-upcoming-appointments-start-date">
							<?php
							$upcoming_appointment_datetime = ssa_datetime( $upcoming_appointment['start_date'] );
							if ( ! empty( $upcoming_appointment['customer_timezone'] ) ) {
								$customer_timezone_string = $upcoming_appointment['customer_timezone'];
							} else {
								$customer_timezone_string = 'UTC';
							}
							$customer_timezone = new DateTimezone( $customer_timezone_string );
							$localized_string = $upcoming_appointment_datetime->setTimezone( $customer_timezone )->format( $date_format );
							$localized_string = SSA_Utils::translate_formatted_date( $localized_string );
							echo $localized_string;
							if ( ! empty( $atts['details_link_displayed'] ) ) {
								echo ' <a target="_blank" href="' . ssa()->appointment_model->get_public_edit_url( $upcoming_appointment['id'] ) . '">' . $atts['details_link_label'] . '</a>';
							}
							?>
						</span>
					</span>
				</li>
			<?php endforeach; ?>
		<?php endif ?>
	</ul>
</div>
