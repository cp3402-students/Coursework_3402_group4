<?php

/**
 * This file should be used to render each module instance.
 * You have access to two variables in this file:
 *
 * $module An instance of your module class.
 * $settings The module's settings.
 *
 * Example:
 */

?>

<div class="fl-module-ssa-upcoming-appointments-wrapper">
	<div class="ssa-upcoming-appointments">
		<?php echo ssa()->shortcodes->ssa_upcoming_appointments( array(
			'no_results_message' => $settings->no_results_message
			) ); ?>
	</div>
</div>
