<!DOCTYPE html>
<?php
$ssa = ssa();
$ssa_settings = $ssa->settings->get();
$ssa_settings = $ssa->settings->remove_unauthorized_settings_for_current_user( $ssa_settings );
$ssa_appointment_types = $ssa->appointment_type_model->query();

$dismissed_notices = $ssa->notices->get_dismissed_notices();
if ( count( $dismissed_notices ) ) {
  $dismissed_notices = array_combine( $dismissed_notices, array_fill(0, count( $dismissed_notices ), true ) );
}
if ( empty( $dismissed_notices ) ) {
  $dismissed_notices = new stdClass();
}

function ssa_get_language_attributes( $doctype = 'html' ) {
  $attributes = array();

  $is_rtl = SSA_Translation::is_rtl();
  $lang = SSA_Translation::get_locale();
  $lang = str_replace( '_', '-', $lang );

  if ( $is_rtl ) {
    $attributes[] = 'dir="rtl"';
  }

  $attributes[] = 'lang="' . esc_attr( $lang ) . '"';

  $output = implode( ' ', $attributes );

  return $output;
}
?>
<html <?php echo ssa_get_language_attributes(); ?>>
  <head>
    <meta charset="utf-8">
    <title><?php the_title(); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow" />

    <link rel='stylesheet' id='ssa-admin-material-icons-css' href='<?php echo $ssa->url( 'assets/css/material-icons.css?ver='.$ssa::VERSION ); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='ssa-unsupported-style' href='<?php echo $ssa->url( 'assets/css/unsupported.css?ver='.$ssa::VERSION ); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='ssa-admin-roboto-font-css' href='<?php echo $ssa->url( 'assets/css/roboto-font.css?ver='.$ssa::VERSION ); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='ssa-admin-style-css' href='<?php echo $ssa->url( 'admin-app/dist/static/css/chunk-vendors.css?ver='.$ssa::VERSION ); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='ssa-admin-style-css' href='<?php echo $ssa->url( 'admin-app/dist/static/css/app.css?ver='.$ssa::VERSION ); ?>' type='text/css' media='all' />
    <link rel="stylesheet" href='<?php echo $ssa->url( 'assets/css/iframe-inner.css?ver='.$ssa::VERSION ); ?>'>
    <link rel='https://api.w.org/' href='<?php echo home_url( 'wp-json/' ); ?>' />
    <link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo home_url( 'xmlrpc.php?rsd' ); ?>" />
    <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="<?php echo home_url( 'wp-includes/wlwmanifest.xml' ); ?>" />
    <link rel="alternate" type="application/json+oembed" href="<?php echo home_url( 'wp-json/oembed/1.0/embed' ); ?>" />
    <link rel="alternate" type="text/xml+oembed" href="<?php echo home_url( 'wp-json/oembed/1.0/embed' ); ?>" />

    <style>
      .ssa-admin-app #wpadminbar,
      .ssa-admin-app #adminmenumain,
      .ssa-admin-app #wpfooter,
      .ssa-admin-app .hidden,
      #wpadminbar,
      #adminmenumain,
      #wpfooter,
      .hidden {
        display: none;
      }
    </style>

    <?php $admin_css_url = $ssa->templates->locate_template_url( 'admin-app/custom.css' ); ?>
    <link rel='stylesheet' id='ssa-admin-custom-css'  href='<?php echo $admin_css_url; ?>' type='text/css' media='all' />
    <?php do_action( 'ssa_admin_head' ); ?>
  </head>
  <body <?php body_class(); ?>>
  <?php echo '<div id="ssa-admin-app">
			<noscript>
				<div class="unsupported">
					<div class="unsupported-container">
						<img class="unsupported-icon" src="' . $ssa->url('admin-app/dist/static/images/foxes/fox-sleeping.svg') . '"/>
						<h1 class="unsupported-label">' . __('Simply Schedule Appointments requires JavaScript', 'simply-schedule-appointments') . '</h1>
						<p class="unsupported-description">' . __('Please make sure you enable JavaScript in your browser.', 'simply-schedule-appointments') . '</p>
					</div>
				</div>
			</noscript>
		</div>
		<div id="ssa-unsupported" style="display:none;">
				<div class="unsupported">
					<div class="unsupported-container">
						<img class="unsupported-icon" src="' . $ssa->url('admin-app/dist/static/images/foxes/fox-sleeping.svg') . '"/>
						<h1 class="unsupported-label">' . __('Unsupported Browser', 'simply-schedule-appointments') . '</h1>
						<p class="unsupported-description">' . __('Please update your browser to something more modern. We recommend Firefox or Chrome.', 'simply-schedule-appointments') . '</p>
					</div>
				</div>
		</div>' ?>
  
  <script type="text/javascript">
    var ssa = <?php echo json_encode( $ssa->bootstrap->get_api_vars() ); ?>;
    var ssa_dismissed_notices = <?php echo json_encode( $dismissed_notices ); ?>;
    var ssa_settings = <?php echo json_encode( $ssa_settings ); ?>;
    var ssa_appointment_types = <?php echo json_encode( $ssa_appointment_types ); ?>;
    var ssa_translations = <?php echo json_encode( $ssa->wp_admin->get_translations() ); ?>;
    var ssa_is_embed = true;
  </script>

  <script type='text/javascript' src='<?php echo $ssa->url( 'assets/js/unsupported-min.js?ver='.$ssa::VERSION ); ?>'></script>
  <script type='text/javascript' src='<?php echo $ssa->url( 'admin-app/dist/static/js/manifest.js?ver='.$ssa::VERSION ); ?>'></script>
  <script type='text/javascript' src='<?php echo $ssa->url( 'admin-app/dist/static/js/chunk-vendors.js?ver='.$ssa::VERSION ); ?>'></script>
  <script type='text/javascript' src='<?php echo $ssa->url( 'admin-app/dist/static/js/app.js?ver='.$ssa::VERSION ); ?>'></script>
  <script type='text/javascript' src='<?php echo $ssa->url( 'assets/js/iframe-inner.js?ver='.$ssa::VERSION ); ?>'></script>
  <?php do_action( 'ssa_admin_footer' ); ?>
  </body>
</html>
