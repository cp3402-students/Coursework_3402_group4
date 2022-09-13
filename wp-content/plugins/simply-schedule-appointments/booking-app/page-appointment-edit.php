<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo apply_filters( 'ssa_appointment_edit_page_title', __( 'Edit Appointment', 'simply-schedule-appointments' ) ); ?></title>
    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <?php
    global $ssa_current_appointment_id;
    if ( empty( $ssa_current_appointment_id ) ) {
      die( 'No appointment found, please check the URL' );
    }
    $shortcode = '[ssa_booking edit="'.$ssa_current_appointment_id.'"';
    if ( ! empty( $_GET['paypal_success'] ) || ! empty( $_GET['paypal_cancel'] ) ) {
      $shortcode .= ' view="confirm_payment"';
    }
    $appointment = new SSA_Appointment_Object( $ssa_current_appointment_id );
    $customer_locale = $appointment->customer_locale;
    if ( ! empty( $customer_locale ) ) {
      $shortcode .= ' ssa_locale="'. $customer_locale . '"';
    }
    $shortcode .= ']';
    echo do_shortcode( $shortcode );
    ?>
  </body>
  <?php
  /* We need this section to prevent plugin conflicts (some plugins output HTML, like Cookie/GDPR notices) */
  remove_all_actions( 'wp_footer' );
  add_action( 'wp_footer', 'wp_print_footer_scripts', 20 ); // from WordPress core default filters
  add_action( 'wp_footer', 'wp_admin_bar_render', 1000 ); // from WordPress core default filters

  wp_footer();
  ?>
</html>
