<?php

class WPM2AWS_AdminPagesConsole extends WPM2AWS_AdminPages
{
    // private static $instanceDetails;
    private static $consoleDetails;
    private static $instanceRam;
    private static $instanceCpu;
    private static $instanceDisk;
    private static $instanceTransfer;
    private static $instancePlatform;

    private static $consoleRemoteData;

    public function __construct()
    {


    }

    public static function loadAwsConsole()
    {
        self::makePageHeading(2);

        /* Add admin notice */
        add_action('wpm2aws_admin_notices', 'wpm2aws_admin_error_notice');
        do_action('wpm2aws_admin_notices');

        echo "<br>";
        // if (false === get_option('wpm2aws-lightsail-instance-details') || '' === get_option('wpm2aws-lightsail-instance-details')) {
        //     echo self::makeConsoleHoldingPage();
        // } else {

            // self::$instanceDetails = get_option('wpm2aws-lightsail-instance-details')['details'];
            $apiGlobal = new WPM2AWS_ApiGlobal();

            if (empty(self::$consoleRemoteData)) {
                self::$consoleRemoteData = $apiGlobal->getRemoteConsoleData();
            }

            if (empty(self::$consoleRemoteData)) {
                echo self::makeEmptyConsolePage();
            } else if (isset(self::$consoleRemoteData['error'])) {
                echo self::makeConsoleErrorPage();
            } else {
                echo self::makeConsolePage();
            }
        // }
    }

    private static function makeConsoleHoldingPage()
    {
        $html = 'Your Console will appear here when you run migration';

        return $html;
    }

    private static function makeEmptyConsolePage()
    {
        $html = '';
        $html .= '<div class="wpm2aws-console-section" style="text-align:center;padding-top:75px;padding-bottom:75px;">';

        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/offlineIcon.png"/>';
        $html .= '<h2>We are unable to Connect to your AWS Console at this time</h2>';
        $html .= '</div>';

        return $html;
    }


    private static function makeConsoleErrorPage()
    {
        $html = '';
        $html .= '<div class="wpm2aws-console-section" style="text-align:center;padding-top:75px;padding-bottom:75px;">';

        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/unauthorized.png"/>';
        $html .= '<h2>' . self::$consoleRemoteData['error'] . '</h2>';
        $html .= '</div>';

        return $html;
    }


    private static function makeConsolePage()
    {
        // print_r(self::$consoleRemoteData);
        $html = '';

        $html .= '<div class="wpm2aws-console-sub-heading-section">';
        $html .= self::makeSubHeading();

        $html .= self::makeOtherPlanLinkSection();

        $html .= self::makeRegionSection();

        // $html .= self::makeRebootInstanceButtonSection();
        // $html .= self::makeRebootInstanceConfirmationSection();


        $html .= self::makePricingSection();
        $html .= '</div>';


        $html .= '<div class="wpm2aws-console-actions-section">';

        $html .= self::makeRebootInstanceButtonSection();
        $html .= self::makeRebootInstanceConfirmationSection();

        $html .= self::makeCreateInstanceSnapshotButtonSection();
        $html .= self::makeCreateInstanceSnapshotConfirmationSection();

        /// removed from v.1.9.1 $html .= self::makeChangeInstanceRegionButtonSection();
        $html .= self::makeChangeInstanceRegionConfirmationSection();

        $html .= self::makeChangeInstancePlanButtonSection();
        $html .= self::makeChangeInstancePlanConfirmationSection();

        $html .= '</div>';


        // Left Side
        $html .= '<div class="wpm2aws-half-width-panel wpm2aws-half-width-panel-left">';

        $html .= self::makeCpuUsageSection();

        $html .= self::makeInstanceAlarmsSection();

        $html .= self::makeSystemDiskSection();

        $html .= '</div>';


        // Right Side
        $html .= '<div class="wpm2aws-half-width-panel wpm2aws-half-width-panel-right">';

        $html .= self::makeIpAddressSection();

        $html .= '<div class="wpm2aws-4-by-4-panel">';

        $html .= '<div class="wpm2aws-4-by-4-sub-panel wpm2aws-4-by-4-sub-panel-left">';
        $html .= self::makeLoadBalancerSection();
        $html .= '</div>';

        $html .= '<div class="wpm2aws-4-by-4-sub-panel wpm2aws-4-by-4-sub-panel-right">';
        $html .= self::makeDatabaseSection();
        $html .= '</div>';

        $html .= '<div class="wpm2aws-4-by-4-sub-panel wpm2aws-4-by-4-sub-panel-left">';
        $html .= self::makeManualSnapshotSection();
        $html .= '</div>';

        $html .= '<div class="wpm2aws-4-by-4-sub-panel wpm2aws-4-by-4-sub-panel-right">';
        $html .= self::makeAutomaticSnapshotSection();
        $html .= '</div>';



        $html .= '</div>';

        $html .= self::makeInstanceHistorySection();

        $html .= '</div>';

        return $html;
    }

    private static function makeSubHeading()
    {
        $html = '';
        $html .= '<div class="">';
        $html .= '<h3>' . esc_html(__('AWS Control Panel', 'migrate-2-aws')) . '</h3>';
        $html .= '</div>';
        return $html;
    }


    private static function makeOtherPlanLinkSection()
    {
        $html = '';

        if (false !== get_option('wpm2aws_console_changed_plan_instance_ip') && '' !== get_option('wpm2aws_console_changed_plan_instance_ip')) {
            $changedPlanInstanceIp = get_option('wpm2aws_console_changed_plan_instance_ip');
            $html .= '<div class="wpm2aws-console-section">';
            $html .= '<p>A bundle plan change site is available</p>';
            $html .= 'View site on New Plan: <a href="http://' . $changedPlanInstanceIp . '" target="_blank">' . $changedPlanInstanceIp . '</a>';
            $html .= '<br><br>';
            $html .= 'To switch your site over to this New Plan, please contact us to complete the process';
            $html .= '<br><br>';
            $html .= '<a class="button button-primary" href="mailto:' . WPM2AWS_SEAHORSE_EMAIL_ADDRESS . '">Email Us</a>';
            $html .= '</div>';
        }
        return $html;
    }

    private static function makeRegionSection()
    {
        $html = '';
        $html .= '<div class="wpm2aws-console-section wpm2aws-console-region">';
        $html .= '<div class="wpm2aws-console-region-flag">' . self::getRegionFlag() . '</div>';
        $html .= '<div class="wpm2aws-console-region-names">';
        $html .= '<h2>' . self::getRegionName() . '</h2>';
        $html .= '<h4>' . self::$consoleRemoteData['instance-details']['location']['regionName'] . '</h4>';
        // $html .= '<h4>' . self::$instanceDetails['location']['regionName'] . '</h4>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }


    private static function makeRebootInstanceButtonSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-reboot-instance-section wpm2aws-console-action-section-button-container">';
        $html .= '<div id="wpm2aws-console-reboot-instance-button" class="button wpm2aws-button-aws"><h2>Reboot Instance</h2></div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeRebootInstanceConfirmationSection()
    {
        $formAction = 'wpm2aws_console_reboot_instance';

        $html = '';

        $html .= '<div id="wpm2aws-console-reboot-instance-confirmation-section" class="wpm2aws-console-reboot-instance-confirmation-section wpm2aws-confimation-overlay" style="display:none;">';
        $html .= '<div class="wpm2aws-confimation-form-container">';
        $html .= '<h2>Reboot your instance?</h2>';
        $html .= '<p>Rebooting makes any website or service on your instance temporarily unavailable.</p>';
        $html .= '<p>Do you want to reboot your instance?</p>';
        $html .= '<div id="wpm2aws-console-reboot-instance-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '"/>';
        $html .= '<input type="hidden" id="wpm2aws-console-reboot-instance-cross-check" name="wpm2aws-console-reboot-instance-cross-check" value=""/>';
        $html .= wp_nonce_field($formAction, 'wpm2aws-console-reboot-instance-nonce');
        $html .= '<input type="submit" class="button wpm2aws-button-aws" id="wpm2aws-console-reboot-instance-confirm-button" name="wpm2aws-console-reboot-instance" value="Reboot Instance"/>';
        $html .= '</form>';

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeCreateInstanceSnapshotButtonSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-create-instance-snapshot-section wpm2aws-console-action-section-button-container">';
        $html .= '<div id="wpm2aws-console-create-instance-snapshot-button" class="button wpm2aws-button-aws"><h2>Create Snapshot</h2></div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeCreateInstanceSnapshotConfirmationSection()
    {
        $formAction = 'wpm2aws_console_create_instance_snapshot';

        $html = '';

        $html .= '<div id="wpm2aws-console-create-instance-snapshot-confirmation-section" class="wpm2aws-console-create-instance-snapshot-confirmation-section wpm2aws-confimation-overlay" style="display:none;">';
        $html .= '<div class="wpm2aws-confimation-form-container">';
        $html .= '<h2>Create a Manual Snapshot</h2>';
        $html .= '<p>You can create a snapshot to back up your instance, its system disk, and attached disks.</p>';

        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '"/>';
        $html .= '<input type="hidden" id="wpm2aws-console-create-instance-snapshot-cross-check" name="wpm2aws-console-create-instance-snapshot-cross-check" value=""/>';
        $html .= '<div class="wpm2aws-console-inputs-container">';
        $html .= '<input type="text" id="wpm2aws-console-create-instance-snapshot-name" name="wpm2aws-console-create-instance-snapshot-name" placeholder="Add Snapshot Name" value=""/>';
        $html .= '</div>';
        $html .= '<div id="wpm2aws-console-create-instance-snapshot-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
        $html .= wp_nonce_field($formAction, 'wpm2aws-console-create-instance-snapshot-nonce');
        $html .= '<input type="submit" class="button wpm2aws-button-aws"  id="wpm2aws-console-create-instance-snapshot-confirm-button" name="wpm2aws-console-create-instance-snapshot" value="Create Snapshot"/>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }


    private static function makeChangeInstanceRegionButtonSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-change-instance-region-section wpm2aws-console-action-section-button-container">';
        $html .= '<div id="wpm2aws-console-change-instance-region-button" class="button wpm2aws-button-aws"><h2>Change Region</h2></div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeChangeInstanceRegionConfirmationSection()
    {
        if (
            false !== get_option('wpm2aws_console_copy_snapshot_pending_region') &&
            '' !== get_option('wpm2aws_console_copy_snapshot_pending_region') &&
            false !== get_option('wpm2aws_console_copy_snapshot_pending_name') &&
            '' !== get_option('wpm2aws_console_copy_snapshot_pending_name')
        ) {
            return self::getChangeInstanceRegionLaunchInstanceSection();
        }

        return self::getChangeInstanceRegionCopyInstanceSection();


    }


    private static function getChangeInstanceRegionCopyInstanceSection()
    {
        $existingSnapshots = array();
        if (!empty(self::$consoleRemoteData['manual-snapshot-details'])) {
            // $existingSnapshots = self::$consoleDetails['manual-snapshot-details'];
            $existingSnapshots = self::$consoleRemoteData['manual-snapshot-details'];
        }
        $formAction = 'wpm2aws_console_change_instance_region';
        $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('lightsail-instance-region');
        $html = '';

        $html .= '<div id="wpm2aws-console-change-instance-region-confirmation-section" class="wpm2aws-console-change-instance-region-confirmation-section wpm2aws-confimation-overlay" style="display:none;">';
        $html .= '<div class="wpm2aws-confimation-form-container">';
        $html .= '<h2>Change the Region in which the Instance is located?</h2>';
        $html .= '<p>Changing the Region makes any website or service on your instance temporarily unavailable.</p>';
        $html .= '<p>Do you want to change your instance region?</p>';

        if (!empty($formElements[0]['field_data'])) {
            $currentRegion = 'unknown';
            if (false !== get_option('wpm2aws-aws-lightsail-region')) {
                $currentRegion = get_option('wpm2aws-aws-lightsail-region');
            }
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '"/>';
            $html .= '<input type="hidden" id="wpm2aws-console-change-instance-region-cross-check" name="wpm2aws-console-change-instance-region-cross-check" value=""/>';
            $html .= '<div class="wpm2aws-console-inputs-container">';
            $html .= '<h3>Current Region: ' . $currentRegion . '</h3>';
            $html .= '<select id="wpm2aws-console-change-instance-region-new-region-ref" name="wpm2aws-console-change-instance-region-new-region-ref">';
            $html .= '<option value="">Select New Region</option>';
            foreach ($formElements[0]['field_data'] as $dataKey => $dataVal) {
                $disabled = '';
                if ($dataKey === $currentRegion) {
                    $disabled = 'disabled readonly="readonly"';
                }
                $html .= '<option ' . $disabled . 'value="' . esc_attr($dataKey) . '">' . esc_attr($dataVal) . '</option>';
            }
            $html .= '</select>';

            $html .= '<select id="wpm2aws-console-change-instance-region-use-snapshot-ref" name="wpm2aws-console-change-instance-region-use-snapshot-ref">';
            $html .= '<option value="">Select Snapshot To Create From</option>';
            foreach ($existingSnapshots as $esnapKey => $esnapVal) {
                $html .= '<option value="' . esc_attr($esnapVal['name']) . '">' . esc_attr($esnapVal['name']) . '</option>';
            }
            $html .= '</select>';

            $html .= '</div>';
            $html .= '<h3 class="wpm2aws-text-error">Service Currently Unavailable</h3>';
            $html .= '<div id="wpm2aws-console-change-instance-region-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
            // $html .= wp_nonce_field($formAction, 'wpm2aws-console-change-instance-region-nonce');
            // $html .= '<input type="submit" class="button wpm2aws-button-aws" id="wpm2aws-console-change-instance-region-confirm-button" name="wpm2aws-console-change-instance-region" value="Change Region"/>';
            $html .= '</form>';
        } else {
            $html .= '<div><h4 class="wpm2aws-text-error">Error. There are Currently no Regions Available</h4></div>';
            $html .= '<div id="wpm2aws-console-change-instance-region-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
        }


        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }


    private static function getChangeInstanceRegionLaunchInstanceSection()
    {
        $html = '';

        if (
            false === get_option('wpm2aws_console_copy_snapshot_pending_region') ||
            '' === get_option('wpm2aws_console_copy_snapshot_pending_region') ||
            false === get_option('wpm2aws_console_copy_snapshot_pending_name') ||
            '' === get_option('wpm2aws_console_copy_snapshot_pending_name')
        ) {
            $html .= '<div><h4 class="wpm2aws-text-error">Error. Launch Configuration Cannot Bew Determined. Please Contact your Administrator</h4></div>';
            return $html;
        }

        $newRegion = get_option('wpm2aws_console_copy_snapshot_pending_region');
        $newSnapshotName = get_option('wpm2aws_console_copy_snapshot_pending_name');

        $formAction = 'wpm2aws_console_change_instance_region';
        $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('lightsail-instance-region');


        $html .= '<div id="wpm2aws-console-change-instance-region-confirmation-section" class="wpm2aws-console-change-instance-region-confirmation-section wpm2aws-confimation-overlay" style="display:none;">';
        $html .= '<div class="wpm2aws-confimation-form-container">';
        $html .= '<h2>Change the Region in which the Instance is located?</h2>';
        $html .= '<p>Changing the Region makes any website or service on your instance temporarily unavailable.</p>';
        $html .= '<p>Do you want to change your instance region?</p>';

        if (!empty($formElements[0]['field_data'])) {
            $currentRegion = 'unknown';
            if (false !== get_option('wpm2aws-aws-lightsail-region')) {
                $currentRegion = get_option('wpm2aws-aws-lightsail-region');
            }
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '"/>';
            $html .= '<input type="hidden" id="wpm2aws-console-change-instance-region-cross-check" name="wpm2aws-console-change-instance-region-cross-check" value=""/>';
            $html .= '<div class="wpm2aws-console-inputs-container">';

            $html .= '<h3>Current Region: ' . $currentRegion . '</h3>';
            $html .= '<h3>New Region: ' . $newRegion . '</h3>';
            $html .= '<h3>Launch From Snapshot: ' . $newSnapshotName . '</h3>';
            $html .= '<input type="hidden" name="wpm2aws-console-change-instance-region-new-region-ref" value="' . $newRegion . '"/>';
            $html .= '<input type="hidden" name="wpm2aws-console-change-instance-region-use-snapshot-ref" value="' . $newSnapshotName . '"/>';

            $html .= '</div>';
            $html .= '<div id="wpm2aws-console-change-instance-region-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
            $html .= wp_nonce_field($formAction, 'wpm2aws-console-change-instance-region-nonce');
            $html .= '<input type="submit" class="button wpm2aws-button-aws" id="wpm2aws-console-change-instance-region-confirm-button" name="wpm2aws-console-change-instance-region" value="Change Region"/>';
            $html .= '</form>';
        } else {
            $html .= '<div><h4 class="wpm2aws-text-error">Error. There are Currently no Regions Available</h4></div>';
            $html .= '<div id="wpm2aws-console-change-instance-region-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
        }


        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeChangeInstancePlanButtonSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-change-instance-plan-section wpm2aws-console-action-section-button-container">';
        $html .= '<div id="wpm2aws-console-change-instance-plan-button" class="button wpm2aws-button-aws"><h2>Change Plan</h2></div>';
        $html .= '</div>';
        return $html;
    }

    private static function makeChangeInstancePlanConfirmationSection()
    {
        $formAction = 'wpm2aws_console_change_instance_plan';

        $html = '';

        $html .= '<div id="wpm2aws-console-change-instance-plan-confirmation-section" class="wpm2aws-console-change-instance-plan-confirmation-section wpm2aws-confimation-overlay" style="display:none;">';
        $html .= '<div class="wpm2aws-confimation-form-container">';
        $html .= '<h2>Change the Pricing Plan of your instance?</h2>';
        $html .= '<p>Changing the Pricing Plan makes any website or service on your instance temporarily unavailable.</p>';
        $html .= '<p>Do you want to change the Pricing Plan of your instance?</p>';


        $wpm2aws_instance_bundle_types = array('LINUX_UNIX');

        if (!empty(self::$consoleRemoteData['bundle-details'])) {
            // $pricingBundles = self::$consoleDetails['bundle-details'];
            $pricingBundles = self::$consoleRemoteData['bundle-details'];

            $currentBundle = 'unknown';
            foreach ($pricingBundles as $pbKey => $pbVal) {
                // if ($pbVal['bundleId'] === self::$instanceDetails['bundleId']) {
                if ($pbVal['bundleId'] === self::$consoleRemoteData['instance-details']['bundleId']) {
                    $currentBundle = $pbVal['bundleId'];
                }
            }
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '"/>';
            $html .= '<input type="hidden" id="wpm2aws-console-change-instance-plan-cross-check" name="wpm2aws-console-change-instance-plan-cross-check" value=""/>';
            $html .= '<div class="wpm2aws-console-inputs-container">';
            $html .= '<h3>Current Plan: ' . $currentBundle . '</h3>';
            $html .= '<select id="wpm2aws-console-change-instance-plan-new-plan-ref" name="wpm2aws-console-change-instance-plan-new-plan-ref">';
            $html .= '<option value="">Select New Plan</option>';



            foreach ($pricingBundles as $bundleIx => $bundleDetails) {
                // foreach (WPM2AWS_INSTANCE_BUNDLE_TYPES as $bIx => $bVal) {
                foreach ($wpm2aws_instance_bundle_types as $bIx => $bVal) {
                    if (in_array($bVal, $bundleDetails['supportedPlatforms'])) {
                        $name = ucfirst(substr($bundleDetails['bundleId'], 0, strpos($bundleDetails['bundleId'], '_')));
                        $disabled = '';
                        $title = esc_attr($name);
                        if ($bundleDetails['bundleId'] === $currentBundle) {
                            $disabled = 'disabled readonly="readonly"';
                            $title = 'This is your Current Plan';
                        }
                        if ('nano_2_0' !== $bundleDetails['bundleId'] && 'micro_2_0' !== $bundleDetails['bundleId']) {
                            $disabled = 'disabled readonly="readonly"';
                            $title = 'This Plan is Unavailable';
                        }

                        $html .= '<option ' . $disabled . ' title="' . $title . '" value="' . esc_attr($bundleDetails['bundleId']) . '">' . esc_attr($name) . ' - ' . esc_attr($bundleDetails['ramSizeInGb']) . 'GB' . '</option>';
                    }
                }
            }
            $html .= '</select>';

            // $existingSnapshots = self::$consoleDetails['manual-snapshot-details'];
            $existingSnapshots = self::$consoleRemoteData['manual-snapshot-details'];
            $html .= '<select id="wpm2aws-console-change-instance-plan-use-snapshot-ref" name="wpm2aws-console-change-instance-plan-use-snapshot-ref">';
            $html .= '<option value="">Select Snapshot To Change From</option>';
            foreach ($existingSnapshots as $esnapKey => $esnapVal) {
                $html .= '<option value="' . esc_attr($esnapVal['name']) . '">' . esc_attr($esnapVal['name']) . '</option>';
            }
            $html .= '</select>';

            $html .= '</div>';
            $html .= '<div id="wpm2aws-console-change-instance-plan-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
            $html .= wp_nonce_field($formAction, 'wpm2aws-console-change-instance-plan-nonce');
            $html .= '<input type="submit" class="button wpm2aws-button-aws" id="wpm2aws-console-change-instance-plan-confirm-button" name="wpm2aws-console-change-instance-plan" value="Change Plan"/>';
            $html .= '</form>';
        } else {
            $html .= '<div><h4 class="wpm2aws-text-error">Error. There are Currently no Plans Available</h4></div>';
            $html .= '<div id="wpm2aws-console-change-instance-plan-cancel-button" class="button button-default wpm2aws-confirmation-cancel-button"><h2>Cancel</h2></div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private static function makePricingSection()
    {
        // $bundles = self::$consoleDetails['bundle-details'];

        $html = '';
        $html = '<div class="wpm2aws-console-bundles-section">';
        $wpm2aws_instance_bundle_types = array('LINUX_UNIX');

        if (!empty(self::$consoleRemoteData['bundle-details'])) {
            $bundles = self::$consoleRemoteData['bundle-details'];


            foreach ($bundles as $bundleIx => $bundleDetails) {
                $activeClass = '';

                if ($bundleDetails['bundleId'] === self::$consoleRemoteData['instance-details']['bundleId']) {
                // if ($bundleDetails['bundleId'] === self::$instanceDetails['bundleId']) {
                    $activeClass = ' wpm2aws-active-bundle';
                    self::$instanceRam = $bundleDetails['ramSizeInGb'];
                    self::$instanceCpu = $bundleDetails['cpuCount'];
                    self::$instanceDisk = $bundleDetails['diskSizeInGb'];
                    self::$instanceTransfer = $bundleDetails['transferPerMonthInGb'];
                    self::$instancePlatform = $bundleDetails['supportedPlatforms'];
                }
                foreach ($bundleDetails['supportedPlatforms'] as $platformIx => $platformVal) {
                    // if (in_array($platformVal, WPM2AWS_INSTANCE_BUNDLE_TYPES)) {
                    if (in_array($platformVal, $wpm2aws_instance_bundle_types)) {
                        $refName = ucfirst(substr($bundleDetails['bundleId'], 0, strpos($bundleDetails['bundleId'], '_')));
                        $details = '';
                        $details .= '<div class="wpm2aws-pricing-amount"><h2>' . $refName . '</h2><h4>' . $bundleDetails['ramSizeInGb'] . ' GB</h4></div>';
                        // $details .= '<div class="wpm2aws-pricing-amount"><h2>$ ' . $refName . $bundleDetails['price'] . ' USD/mth</h2></div>';
                        $title = '';
                        $title .= 'Memory: ' . $bundleDetails['ramSizeInGb'] . ' - ';
                        $title .= 'Processor: ' . $bundleDetails['cpuCount'] . ' Cores' . ' - ';
                        $title .= 'Disk: ' . $bundleDetails['diskSizeInGb'] . ' SSD' . ' - ';
                        $title .= 'Transfer*: ' . $bundleDetails['transferPerMonthInGb'] . ' - ';
                        $title .= 'Platform: ' . $platformVal;

                        // $details .= '<div class="wpm2aws-pricing-details">';
                        // $details .= 'Memory: ' . $bundleDetails['ramSizeInGb'] . '<br>';
                        // $details .= 'Processor: ' . $bundleDetails['cpuCount'] . ' Cores' . '<br>';
                        // $details .= 'Disk: ' . $bundleDetails['diskSizeInGb'] . ' SSD' . '<br>';
                        // $details .= 'Transfer*: ' . $bundleDetails['transferPerMonthInGb'] . '<br>';
                        // $details .= 'Platform: ' . $platformVal . '<br>';
                        // $details .= '</div>';

                        $html .= '<div class="wpm2aws-console-section wpm2aws-console-bundle' . $activeClass . '" title="' . $title . '">' . $details . '</div>';
                    }
                }
            }
        } else {
            $html .= '<div class="wpm2aws-text-error">Bundle Data Cannot Be Retrieved At This Time</div>';
        }
        // $html .= '<div class="wpm2aws-console-section wpm2aws-console-region">';
        // $html .= '<div class="wpm2aws-console-region-flag">' . self::getRegionFlag() . '</div>';
        // $html .= '<div class="wpm2aws-console-region-names">';
        // $html .= '<h2>' . self::getRegionName() . '</h2>';
        // $html .= '<h4>' . self::$instanceDetails['location']['regionName'] . '</h4>';
        // $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private static function getRegionFlag()
    {
        $flags = array(
            // 'eu-west-1' => 'tricolour'
        );
        $flag = isset($flags[self::$consoleRemoteData['instance-details']['location']['regionName']]) ? $flags[self::$consoleRemoteData['instance-details']['location']['regionName']] : '<span class="dashicons dashicons-flag"></span>';
        //  $flag = isset($flags[self::$instanceDetails['location']['regionName']]) ? $flags[self::$instanceDetails['location']['regionName']] : '<span class="dashicons dashicons-flag"></span>';
        return $flag;
    }

    private static function getRegionName()
    {
        $names = array(
            'us-east-2' => 'Ohio (US East)',
            'us-east-1' => 'N. Virginia (US East',
            'us-west-2' => 'Oregon (US West)',
            'ap-south-1' => 'Mumbai (Asia Pacific)',
            'ap-northeast-2' => 'Seoul (Asia Pacific)',
            'ap-southeast-1' => 'Singapore (Asia Pacific)',
            'ap-southeast-2' => 'Sydney (Asia Pacific',
            'ap-northeast-1' => 'Tokyo (Asia Pacific',
            'ca-central-1' => 'Canada (Central)',
            'eu-central-1' => 'Frankfurt (EU)',
            'eu-west-1' => 'Ireland (EU)',
            'eu-west-2' => 'London (UK)',
            'eu-west-3' => 'Paris (EU)'
        );
        $name = isset($names[self::$consoleRemoteData['instance-details']['location']['regionName']]) ? $names[self::$consoleRemoteData['instance-details']['location']['regionName']] : 'Unknown Region Name';
        // $name = isset($names[self::$instanceDetails['location']['regionName']]) ? $names[self::$instanceDetails['location']['regionName']] : 'Unknown Region Name';
        return $name;
    }


    private static function makeCpuUsageSection()
    {
        $times = array();
        // if (!empty(self::$consoleDetails['usage-metrics'])) {
        //     $times = self::$consoleDetails['usage-metrics'];
        // }
        if (!empty(self::$consoleRemoteData['usage-metrics'])) {
            $times = self::$consoleRemoteData['usage-metrics'];
        }
        // print_r($times);
        // $times = array();

        $labelData = array();
        if (!empty($times)) {
            foreach ($times as $timeKey => $timeVals) {
                if (0 === $timeKey || (0 !== $timeKey && 0 === (($timeKey + 1) % 4))) {
                    // $labels .= '"' . $timeVals['timestamp'] . '", ';
                    // $data .= '"' . $timeVals['average'] . '", ';

                    if ('string' === gettype($timeVals['timestamp'])) {
                        // strtotime($ctValsVals['EventTime'])
                        $labelData[strtotime($timeVals['timestamp'])] = array('time' => date("j M G:i", strtotime($timeVals['timestamp'])), 'data' => $timeVals['average']);
                    } else {
                        $labelData[$timeVals['timestamp']->format('U')] = array('time' => $timeVals['timestamp']->format('j M G:i'), 'data' => $timeVals['average']);
                    }
                }
            }
            // $labels .= ']';
            // $data .= ']';

            // echo "<br><br>";
            // print_r($labelData);
            // echo "<br><br>";
            ksort($labelData);
            // echo "<br><br>";
            // print_r($labelData);

            $labels = '[';
            $data = '[';
            foreach ($labelData as $timeLabel => $dataVal) {
                $labels .= '"' . $dataVal['time'] . '", ';
                $data .= '"' . $dataVal['data'] . '", ';
            }
            $labels .= ']';
            $data .= ']';
        }

        // ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange']


        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Average CPU Utilisation per 10 minutes', 'migrate-2-aws')) . '</h2>';

        // $html .= '<img style="width:100%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/CPU_Usage.png">';

        if (!empty($times)) {
            // wp_die(print_r($times));
            $html .= '<canvas id="myChart" height="450" style="width:100%;"></canvas>';
            $html .= "<script>
            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: " . $labels . ",
                    datasets: [{
                        label: '% CPU Usage',
                        data: " . $data . ",
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1

                    }]
                },
                xAxisID: 'Time Intervals',
                options: {
                    responsive:false,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Average CPU Utilization per 10 minutes'
                    },
                    scales: {
                        yAxes: [{
                            scaleLabel: {
                                display: true,
                                labelString: '% CPU Usage'
                            },
                            ticks: {
                                // Include a dpercent sign in the ticks
                                callback: function(value, index, values) {
                                    return value + '%';
                                },
                                beginAtZero: true,
                                suggestedMax: 100
                            }
                        }],
                        xAxes: [{
                            scaleLabel: {
                                display: true,
                                labelString: 'Time Intervals'
                            },
                            ticks: {
                                userCallback: function(item, index) {
                                    if (index===0) return item;
                                    if (((index+1)%4)===0) return item;
                                },
                                autoSkip: false
                            }
                        }],
                    }
                }
            });
            </script>";
        } else {
            $html .= '<div class="wpm2aws-text-error">Chart Data Currently Unavailable</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function makeInstanceAlarmsSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Alarms', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></</h2>';

        $alarmMsg = '';
        $alarmMsg .= '<br>';

        // if (empty(self::$consoleDetails['alarm-details'])) {
        if (empty(self::$consoleRemoteData['alarm-details'])) {
            $alarmMsg .= '<hr>';
            $alarmMsg .= '<h2>No Alarm Currently Set</h2>';
        } else {
            // foreach (self::$consoleDetails['alarm-details'] as $alarmIx => $alarmVal) {
            foreach (self::$consoleRemoteData['alarm-details'] as $alarmIx => $alarmVal) {
                $alarmMsg .= '<br>';

                $alarmMsg .= '<div class="wpm2aws-active-alarm-container">';
                $alarmMsg .= '<h2>Alarm ' . ($alarmIx + 1);
                $alarmMsg .= ' - ';
                if (false !== $alarmVal['notificationEnabled']) {
                    $alarmMsg .= '<span class="wpm2aws-success-text">Enabled&nbsp;<span class="dashicons dashicons-yes"></span></span>';
                } else {
                    $alarmMsg .= '<span class="wpm2aws-danger-text">Disabled&nbsp;<span class="dashicons dashicons-no-alt"></span></span>';
                }

                $alarmMsg .= '</h2>';

                if ('ALARM' === $alarmVal['state']) {
                    $alarmMsg .= '<div class="wpm2aws-console-alarm-notice wpm2aws-console-section-inner-panel">Notice: This Alarm has been Triggered</div>';
                }

                $alarmMsg .= '<p>';
                $alarmMsg .= 'Notify when ';
                $alarmMsg .= preg_replace('/(?<!\ )[A-Z]/', ' $0', $alarmVal['metricName']);
                $alarmMsg .= ' is ';
                $alarmMsg .= preg_replace('/(?<!\ )[A-Z]/', ' $0', str_replace('Threshold', '', $alarmVal['comparisonOperator']));
                $alarmMsg .= ' ';
                $alarmMsg .= $alarmVal['threshold'];
                $unit = $alarmVal['unit'];
                if ('Percent' === $alarmVal['unit']) {
                    $unit = '%';
                }
                $alarmMsg .= $unit;
                $alarmMsg .= ' for ';
                $alarmMsg .= $alarmVal['datapointsToAlarm'];
                $alarmMsg .= ' times within the last ';
                $alarmMsg .= ($alarmVal['evaluationPeriods'] * 5);
                $alarmMsg .= ' minutes';
                $alarmMsg .= '</p>';
                // foreach ($alarmVal as $aIx => $alarmDetail) {
                //     $html .= '<p>' .  $alarmIx . ' => ' . $aIx . ' => ' . $alarmDetail . '</p>';
                // }
                $alarmMsg .= '</div>';
            }

            // foreach (self::$consoleDetails['alarm-details'] as $alarmIx => $alarmVal) {
            //     foreach ($alarmVal as $aIx => $alarmDetail) {
            //         if (is_array($alarmDetail)) {
            //             $html .= '<p>' .  $alarmIx . ' => ' . $aIx . ' => </p>';
            //             foreach ($alarmDetail as $adIx => $adVals) {
            //                 $html .= '<p>' .  $adIx . ' => ' . $adVals . ' => </p>';
            //             }
            //         } else {
            //             $html .= '<p>' .  $alarmIx . ' => ' . $aIx . ' => ' . $alarmDetail . '</p>';
            //         }
            //     }
            // }


            $html .= $alarmMsg;
        }


        $html .= self::addNewAlarmSection();


        $html .= '</div>';

        return $html;
    }

    private static function addNewAlarmSection()
    {
        $addAlarmComparisons = array(
            'GreaterThanOrEqualToThreshold' => 'Greater Than Or Equal To',
            'GreaterThanThreshold' => 'Greater Than',
            'LessThanThreshold' => 'Less Than',
            'LessThanOrEqualToThreshold' => 'Less Than Or Equal To',
        );

        $addAlarmTimeFrames = array(
            'minutes' => 'minutes',
            'hours' => 'hours',
        );

        $formAction = 'wpm2aws_add_new_metric_alarm_form';

        $html = '';

        $html .= '<br>';
        $html .= '<div id="wpm2aws-add-new-metric-alarm-button" style="color:#dd6b10;cursor:pointer;"><span class="dashicons dashicons-plus"></span> Add New Alarm</div>';

        $html .= '<div id="wpm2aws-add-new-metric-alarm-container" style="display:none;">';
        $html .= '<form id="wpm2aws-create-new-alarm" method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
        $html .= '<div>';
        $html .= '<input type="text" id="wpm2aws-add-alarm-name" name="wpm2aws-add-alarm-name" placeholder="Alarm Name" class="regular-text"/>';
        $html .= '</div>';
        $html .= '<br>';

        $html .= '<div>';
        $html .= '<select id="wpm2aws-add-alarm-select-comparison" name="wpm2aws-add-alarm-select-comparison">';
        foreach ($addAlarmComparisons as $compIx => $compName) {
            $html .= '<option value="' . $compIx . '">' . $compName . '</option>';
        }
        $html .= '</select>';

        $html .= '<input type="text" id="wpm2aws-add-alarm-comparison-value" name="wpm2aws-add-alarm-comparison-value" maxlength="5" placeholder="50" value="50" class="small-text"/> percent';
        $html .= '</div>';
        $html .= '<br>';
        $html .= '<div>';

        $html .= 'for ';
        $html .= '<input type="text" id="wpm2aws-add-alarm-frequency-value" name="wpm2aws-add-alarm-frequency-value" maxlength="3" placeholder="2" value="2" class="small-text"/>';
        $html .= ' times within the last ';

        $html .= '<select id="wpm2aws-add-alarm-select-time-hours" name="wpm2aws-add-alarm-select-time-hours">';
        foreach (range(0, 24) as $number) {
            $html .= '<option value="' . $number . '">' . $number . '</option>';
        }
        $html .= '</select>';
        $html .= ' hours ';

        $html .= '<select id="wpm2aws-add-alarm-select-time-minutes" name="wpm2aws-add-alarm-select-time-minutes">';
        foreach (range(0, 55, 5) as $number) {
            $sel = '';
            if (20 === $number) {
                $sel ='selected';
            }
            $html .= '<option ' . $sel . ' value="' . $number . '">' . $number . '</option>';
        }
        $html .= '</select>';
        $html .= ' minutes';

        $html .= '</div>';

        $html .= '<br>';

        $html .= '<div>';
        $html .= '<p><em>AWS evaluates datapoints for alarms every 5 minutes, and each datapoint for alarms represents a 5 minute period of aggregated data.</em></p>';
        $html .= '</div>';

        $html .= '<br>';

        $html .= '<div>';
        $html .= '<div id="wpm2aws-cancel-new-metric-alarm" class="button button-secondary">Cancel</div>';
        $html .= wp_nonce_field($formAction, 'wpm2aws-add-new-metric-alarm-form-nonce');
        $html .= '<input type="submit" class="button button-primary" name="wpm2aws-add-new-metric-alarm" value="Add Alarm"/>';
        $html .= '</div>';
        $html .= '</form>';

        $html .= '</div>';

        return $html;
    }


    private static function makeSystemDiskSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('System Disk', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h2>';

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse wpm2aws-console-section-inner-panel" style="width: 300px;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:33%;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel-icon">';
        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/diskSystem.png"/>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel">';
        $html .= '<h2>' . self::$instanceDisk . " GB, block storage disk" . '</h2>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<hr';

        $html .= '<h4 class="wpm2aws-console-header">' . esc_html(__('Attach Additional Disk', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h4>';

        $html .= '</div>';

        return $html;
    }

    private static function makeIpAddressSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('IP Addresses', 'migrate-2-aws')) . '</h2>';

        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-left">';
        $html .= self::makeIpAddressSubSection('Public');
        $html .= '</div>';

        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-right">';
        $html .= self::makeIpAddressSubSection('Private');
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeIpAddressSubSection($subSectionName)
    {
        // wp_die(print_r(self::$consoleRemoteData));
        $link = false;
        if ('Public' === $subSectionName) {
            // print_r(self::$instanceDetails);
            // if (!empty(self::$consoleDetails['static-ip-details']['ipAddress'])) {
            if (!empty(self::$consoleRemoteData['static-ip-details']['ipAddress'])) {
                $subSectionName = 'Static';
                // $address = self::$consoleDetails['static-ip-details']['ipAddress'];
                $address = self::$consoleRemoteData['static-ip-details']['ipAddress'];
                $link = true;
            } elseif (!empty(self::$consoleRemoteData['instance-details']['isStaticIp'])) {
                $subSectionName = 'Static';
                $address = self::$consoleRemoteData['instance-details']['publicIpAddress'];
                $link = true;
            } elseif (!empty(self::$consoleRemoteData['instance-details']['publicIpAddress'])) {
                $address = self::$consoleRemoteData['instance-details']['publicIpAddress'];
                $link = true;
            } else {
                $address = '<span class="wpm2aws-test-error">IP Address unavailable</span>';
            }

        } else if ('Private' === $subSectionName) {
            // $address = 'Make API Call';
            // $address = self::$instanceDetails['privateIpAddress'];
            $address = self::$consoleRemoteData['instance-details']['privateIpAddress'];
        } else if ('Static' === $subSectionName) {
            // $address = 'Make API Call';
            // $address = self::$instanceDetails['isStaticIp'];
            $address = self::$consoleRemoteData['instance-details']['isStaticIp'];
        } else {
            $address = 'Not Available';
        }

        $html = '';
        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        $html .= '<h4 class="wpm2aws-console-header">' . $subSectionName . ' IP <span class="dashicons dashicons-editor-help"></span></h4>';

        $html .= '<h2 class="wpm2aws-console-header">';

        if (true === $link) {
            $html .= '<a href="https://' . $address . '" target="_blank">' . $address . '</a>';
        } else {
            $html .= $address;
        }

        $html .= '</h2>';

        $html .= '</div>';
        return $html;
    }

    private static function makePrivateIpAddressSection()
    {
        $html = '';
        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        $html .= '<h4 class="wpm2aws-console-header">' . esc_html(__('Private IP', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h4>';

        $html .= '<h2 class="wpm2aws-console-header">';
        $html .= '<a href="https://' . self::$consoleRemoteData['instance-details']['publicIp'] . '" target="_blank">' . self::$consoleRemoteData['instance-details']['publicIp'] . '</a>';
        $html .= '</h2>';

        $html .= '</div>';
        return $html;
    }

    private static function makeManualSnapshotSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Manual Snapshots', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h2>';

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse wpm2aws-console-section-inner-panel">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:33%;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel-icon">';
        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/snapshot.png"/>';
        $html .= '</div>';
        $html .= '</div>';


        // if (empty(self::$consoleDetails['manual-snapshot-details'])) {
        if (empty(self::$consoleRemoteData['manual-snapshot-details'])) {
            $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
            $html .= '<h2>No Manual Snapshots Available</h2>';
            $html .= '</div>';
        } else {
            $manualSnapshots = array();

            // foreach (self::$consoleDetails['manual-snapshot-details']as $msdIx => $msdVals) {
            foreach (self::$consoleRemoteData['manual-snapshot-details']as $msdIx => $msdVals) {
                array_push(
                    $manualSnapshots,
                    array(
                        'name' => $msdVals['name'],
                        'created' => $msdVals['createdAt'],
                        'status' => $msdVals['state'],
                    )
                );
            }

            if (empty($manualSnapshots)) {
                $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
                $html .= '<h2>No Manual Snapshots Available</h2>';
                $html .= '</div>';
            } else {
                $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
                $html .= '<h2>Manual Snapshots Available</h2>';
                $html .= '</div>';



                $conditionalDateStampTextDisplay = false;



                $html .= '<div class="">';
                foreach ($manualSnapshots as $snapshotIx => $snapshotData) {
                    $html .= '<div class="wpm2aws-console-manual-snapshot-item">';
                    $html .= '<p><strong>' . $snapshotData['name'] . '</strong></p>';
                    if ('string' === gettype($snapshotData['created'])) {
                        $html .= '<p>Created: ' . date("d M Y @ H:i", strtotime($snapshotData['created'])) . '</p>';
                    } else {
                        $html .= '<p>Created: ' . $snapshotData['created']->format('d M Y @ H:i') . '</p>';
                    }
                    // $html .= '<p>Created At: ' . $snapshotData['created']->format('d M Y @ H:i') . '</p>';
                    $html .= '<p>Status: ' . $snapshotData['status'] . '</p>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeAutomaticSnapshotSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Automatic Snapshots', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h2>';

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse wpm2aws-console-section-inner-panel">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:33%;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel-icon">';
        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/snapshot.png"/>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
        // wp_die(print_r(self::$instanceDetails));
        if (empty(self::$consoleRemoteData['instance-details']['addOns'])) {
            $html .= '<h2>Automatic Snapshots Not Set 1</h2>';
            $html .= '</div>';
        } else {
            $enabledSnapshots = array();
            foreach (self::$consoleRemoteData['instance-details']['addOns'] as $aoIx => $aoVals) {
                if ('AutoSnapshot' === $aoVals['name'] && 'Enabled' === $aoVals['status']) {
                    array_push($enabledSnapshots, $aoVals['snapshotTimeOfDay']);
                }
            }

            if (empty($enabledSnapshots)) {
                $html .= '<h2>Automatic Snapshots Not Set</h2>';
            } else {
                $html .= '<h2>Automatic Snapshots are Enabled</h2>';
            }
            $html .= '</div>';
                if (!empty($enabledSnapshots)) {
                $html .= '<div class="wpm2aws-console-manual-snapshot-item">';
                $html .= '<p><strong>Auto Snapshots</strong></p>';
                foreach ($enabledSnapshots as $snapshotIx => $snapshotTime) {
                    $html .= '<p>Snapshot ' . ($snapshotIx+1) . ': Snapshot time is ' . $snapshotTime . '</p>';
                }
                $html .= '<p><em>Seven most recent snapshots are stored</em></p>';
                $html .= '</div>';
                }
        }


        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function makeLoadBalancerSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Load Balancer', 'migrate-2-aws')) . '</h2>';

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse wpm2aws-console-section-inner-panel">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:33%;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel-icon">';
        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/loadBalancer.png"/>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
        if (empty(self::$consoleRemoteData['instance-details']['disks'])) {
            $html .= '<h2>No Load Balancer Configured</h2>';
        } else {
            $html .= '<h2>TODO: Get Load Balancer</h2>';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeDatabaseSection()
    {
        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Database', 'migrate-2-aws')) . '</h2>';

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse wpm2aws-console-section-inner-panel">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:33%;">';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel-icon">';
        $html .= '<img style="max-width:80%;" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/mySqlDb.png"/>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="wpm2aws-console-section-inner-sub-panel" style="width:66%;">';
        if (empty(self::$consoleRemoteData['instance-details']['disks'])) {
            $html .= '<h2>No Remote Database Configured</h2>';
        } else {
            $html .= '<h2>TODO: Get Databases</h2>';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function sortByTime($a, $b){
        // $a = strtotime($a['EventTime']);
        // $b = strtotime($b['EventTime']);

        $a = $a['EventTime'];
        $b = $b['EventTime'];

        return $b - $a;
    }
    private static function makeInstanceHistorySection()
    {
        $cloudTrails = array();
        // foreach (self::$consoleDetails['cloud-trail-details'] as $ctIx => $ctVals) {
        if (!empty(self::$consoleRemoteData['cloud-trail-details'])) {
            foreach (self::$consoleRemoteData['cloud-trail-details'] as $ctIx => $ctVals) {
                if (is_array($ctVals)) {
                    foreach ($ctVals as $ctValsIx => $ctValsVals) {
                        array_push(
                            $cloudTrails,
                            array(
                                'EventName' => $ctValsVals['EventName'],
                                'EventTime' => strtotime($ctValsVals['EventTime'])
                                // 'EventTime' => $ctValsVals['EventTime']->format('U')
                            )
                        );
                    }
                }
            }
            array_push(
                $cloudTrails,
                array(
                    'EventName' => 'Created',
                    'EventTime' => strtotime(self::$consoleRemoteData['instance-details']['createdAt']),
                    // 'EventTime' => self::$instanceDetails['createdAt']->format('U'),
                )
            );

            usort($cloudTrails, 'self::sortByTime');
        }


        // wp_die(print_r(self::$consoleDetails['cloud-trail-details']));
        // if (empty(self::$consoleDetails['cloud-trail-details'])) {
        if (empty(self::$consoleRemoteData['cloud-trail-details'])) {
            array_push(
                $cloudTrails,
                array(
                    'EventName' => '<em>Event Logs Cannot be Retrieved at this time.</em>',
                    'EventTime' => '-'
                )
            );
        }

        // print_r($cloudTrails);

        $html = '';

        $html .= '<div class="wpm2aws-console-section">';

        $html .= '<h2 class="wpm2aws-console-header">' . esc_html(__('Instance History (24hrs)', 'migrate-2-aws')) . ' <span class="dashicons dashicons-editor-help"></span></h2>';

        if (!empty($cloudTrails)) {
            $html .= '<ul class="wpm2aws-console-list">';
            $conditionalDateStampText = '<small><em>* Time Stamp may not be accurately determined</em></small>';
            $conditionalDateStampTextDisplay = false;
            foreach ($cloudTrails as $eventIx => $eventDetails) {
                $dateTime = new DateTime();
                $conditionalDateStamp = false;
                if (is_numeric($eventDetails['EventTime'])) {
                    try {
                        $dateTime->setTimestamp($eventDetails['EventTime']);
                    } catch (Exception $e) {
                        $conditionalDateStamp = true;
                        $conditionalDateStampTextDisplay = true;
                    }
                } else {
                    $conditionalDateStamp = true;
                    $conditionalDateStampTextDisplay = true;
                }

                $html .= '<li>';
                $html .= '<span class="wpm2aws-console-history-event-name">' . $eventDetails['EventName'] . '</span>';
                $html .= '<span class="wpm2aws-console-history-event-time">' . $dateTime->format('d M Y - H:i') . (true === $conditionalDateStamp ? ' *' : '') . '</span>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            if (true === $conditionalDateStamp) {
                $html .= $conditionalDateStampText;
            }
        } else {
            $html .= 'No History Logs Availble';
        }

        $html .= '</div>';

        return $html;
    }


}
