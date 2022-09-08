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

if ($settings->appointment_type === 'none' || $settings->appointment_type === 'all' ) {
	$settings->appointment_type = '';
}

$attrs = array();

if( $settings->appointment_type && $settings->appointment_type !== '' ) {
	$attrs['type'] = $settings->appointment_type;
}
if( $settings->accent_color && $settings->accent_color !== '' ) {
	$attrs['accent_color'] = $settings->accent_color;
}
if( $settings->background_color && $settings->background_color !== '' ) {
	$attrs['background'] = $settings->background_color;
}
if( $settings->font_family && $settings->font_family !== '' ) {
	$attrs['font'] = $settings->font_family['family'];
}
if( $settings->padding && $settings->padding !== '' ) {
	$attrs['padding'] = $settings->padding . $settings->padding_unit;
}

?>

<div class="fl-module-ssa-booking-wrapper">
	<div class="ssa-booking">
		<?php echo ssa()->shortcodes->ssa_booking( $attrs ); ?>
	</div>
</div>
