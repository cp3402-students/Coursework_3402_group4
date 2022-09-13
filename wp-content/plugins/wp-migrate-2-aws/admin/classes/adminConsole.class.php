<?php

class WPM2AWS_Console
{
    public function __construct()
    {
        if (!function_exists('get_plugin_data')) {
            add_action('admin_init', array($this, 'pluginData'));
        }
    }

    public function pluginData()
    {
        require_once(get_home_path() . 'wp-admin/includes/plugin.php');
    }

    public function addNewMetricAlarm()
    {
        add_action('admin_post_wpm2aws_add_new_metric_alarm_form', array($this, 'addMetricAlarm'));
    }


    public function runRebootInstance()
    {
        add_action('admin_post_wpm2aws_console_reboot_instance', array($this, 'rebootInstance'));
    }

    public function runCreateInstanceSnapshot()
    {
        add_action('admin_post_wpm2aws_console_create_instance_snapshot', array($this, 'createInstanceSnapshot'));
    }




    public function runChangeInstanceRegion()
    {
        add_action('admin_post_wpm2aws_console_change_instance_region', array($this, 'changeInstanceRegion'));
    }

    public function runChangeInstancePlan()
    {
        add_action('admin_post_wpm2aws_console_change_instance_plan', array($this, 'changeInstancePlan'));
    }

    /**
     * Add A New Alarm
     * Parent Function
     * Validations before passing to
     * API Wrapper Function
     */
    public function addMetricAlarm()
    {
        $validatePost = wpm2awsValidatePost('console-add-new-alarm-form');

        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Add New Alarm<br><br>Invalid/Insufficient parameters to set Alarm', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }


        // wp_die(print_r($_POST));
        $requiredInputs = array(
            'wpm2aws-add-alarm-name' => $_POST['wpm2aws-add-alarm-name'],
            'wpm2aws-add-alarm-select-comparison' => $_POST['wpm2aws-add-alarm-select-comparison'],
            'wpm2aws-add-alarm-comparison-value' => $_POST['wpm2aws-add-alarm-comparison-value'],
            'wpm2aws-add-alarm-frequency-value' => $_POST['wpm2aws-add-alarm-frequency-value'],
            'wpm2aws-add-alarm-select-time-minutes' => $_POST['wpm2aws-add-alarm-select-time-minutes'],
            'wpm2aws-add-alarm-select-time-hours' => $_POST['wpm2aws-add-alarm-select-time-hours'],
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Add New Alarm<br><br>Required Input is Empty', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }


        // Create an Alarm
        $hoursMinsValue = $validatedInputs['wpm2aws-add-alarm-select-time-hours']*60;
        $minutesValue = $validatedInputs['wpm2aws-add-alarm-select-time-minutes'];
        $timeValue = round(((int)$hoursMinsValue + (int)$minutesValue) /5);

        try {
            $alarm = $this->createAlarm(
                $validatedInputs['wpm2aws-add-alarm-name'],
                'CPUUtilization',
                $validatedInputs['wpm2aws-add-alarm-select-comparison'],
                (int)$validatedInputs['wpm2aws-add-alarm-comparison-value'],
                (int)$validatedInputs['wpm2aws-add-alarm-frequency-value'],
                (int)$timeValue
            );
        } catch (Exception $e) {
            // wp_die($e->getMessage());
            $msg = 'Error!<br><br>Alarm not Added<br><br>' . $e->getMessage() . '<br><br>Please Try Again';
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $msg);
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        // Set the User Name Option
        if ($alarm) {
            // Set the Admin Notice
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Alarm Added', 'migrate-2-aws'));
        } else {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Alarm not Added<br><br>Please Try Again', 'migrate-2-aws'));
        }

        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
    }

    /**
     * Add A New Alarm
     * API Wrapper Function
     */
    public function createAlarm($name, $metric, $comparitor, $triggerPoint, $threshold, $timePeriods)
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        // $alarmName, $metric, $comparitor, $triggerPoint, $threshold
        try {
            $alarm = $apiGlobal->remoteAddNewMetricAlarm($name, $metric, $comparitor, $triggerPoint, $threshold, $timePeriods);
            // wp_die(print_r($alarm));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (isset($alarm['error'])) {
            throw new Exception($alarm['error']);
        }

        // try {
        //     $alarm = $apiGlobal->addNewMetricAlarm($name, $metric, $comparitor, $triggerPoint, $threshold);
        // } catch (Exception $e) {
        //     throw new Exception($e->getMessage());
        // }

        return $alarm;
    }




    /**
     * Reboot the Instance
     * Parent Function
     * Validations before passing to
     * API Wrapper Function
     */
    public function rebootInstance()
    {
        $validatePost = wpm2awsValidatePost('console-reboot-instance');

        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Reboot Instance (1)<br><br>Invalid/Insufficient parameters set', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        $requiredInputs = array(
            'wpm2aws-reboot-instance-check' => $_POST['wpm2aws-console-reboot-instance-cross-check'],
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Reboot Instance<br><br>Invalid Input', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }


        // Reboot the Instance
        try {
            $reboot = $this->rebootAwsInstance();
        } catch (Exception $e) {
            // wp_die($e->getMessage());
            $msg = 'Error!<br><br>Instance not Rebooted<br><br>' . $e->getMessage() . '<br><br>Please Try Again';
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $msg);
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        if ($reboot) {
            // Set the Admin Notice
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Instance rebooted', 'migrate-2-aws'));
        } else {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Instance not rebooted<br><br>Please Try Again', 'migrate-2-aws'));
        }

        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
    }

    /**
     * Reboot the Instance
     * API Wrapper Function
     */
    public function rebootAwsInstance()
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        try {
            $rebooted = $apiGlobal->remoteRebootInstance();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (isset($rebooted['error'])) {
            throw new Exception($rebooted['error']);
        }

        // try {
        //     $rebooted = $apiGlobal->rebootInstance();
        // } catch (Exception $e) {
        //     throw new Exception($e->getMessage());
        // }

        return $rebooted;
    }


    /**
     * Create a Manual Snapshot of Instance
     * Parent Function
     * Validations before passing to
     * API Wrapper Function
     */
    public function createInstanceSnapshot()
    {
        $validatePost = wpm2awsValidatePost('console-create-instance-snapshot');

        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Create Instance Snapshot (1)<br><br>Invalid/Insufficient parameters set', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        $requiredInputs = array(
            'wpm2aws-create-instance-snapshot-check' => $_POST['wpm2aws-console-create-instance-snapshot-cross-check'],
            'wpm2aws-create-instance-snapshot-name-ref' => $_POST['wpm2aws-console-create-instance-snapshot-name'],
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Create Instance Snapshot (2)<br><br>Invalid Input', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        $checkSyntax = wpm2awsRegexAlphaNumericDashUnderscore($validatedInputs['wpm2aws-create-instance-snapshot-name-ref']);
        if (true !== $checkSyntax) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Create Instance Snapshot<br><br>Invalid Input<br><br>Snapshot Name can contain only letters and numbers; hyphen (-) and underscore (_) characters may separate words', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        // Create the Snapshot of the Instance
        try {
            $snapshot = $this->createAwsInstanceSnapshot(
                $validatedInputs['wpm2aws-create-instance-snapshot-name-ref']
            );
        } catch (Exception $e) {
            // wp_die($e->getMessage());
            $msg = 'Error!<br><br>Create Instance Snapshot (3)<br><br>' . $e->getMessage() . '<br><br>Please Try Again';
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $msg);
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        if ($snapshot) {
            // Set the Admin Notice
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Instance Snapshot Created', 'migrate-2-aws'));
        } else {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Create Instance Snapshot (4)<br><br>Please Try Again', 'migrate-2-aws'));
        }

        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
    }

    /**
     * Reboot the Instance
     * API Wrapper Function
     */
    public function createAwsInstanceSnapshot($snapshotName)
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        try {
            $snapshot = $apiGlobal->remoteCreateManualSnapshot($snapshotName);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (isset($snapshot['error'])) {
            throw new Exception($snapshot['error']);
        }
        // try {
        //     $snapshot = $apiGlobal->createManualSnapshot($snapshotName);
        // } catch (Exception $e) {
        //     throw new Exception($e->getMessage());
        // }

        return $snapshot;
    }




    /**
     * Change the Instance's Region
     * Parent Function
     * Validations before passing to
     * API Wrapper Function
     */
    public function changeInstanceRegion()
    {
        $validatePost = wpm2awsValidatePost('console-change-instance-region');

        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Change Instance Region (1)<br><br>Invalid/Insufficient parameters set', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        $requiredInputs = array(
            'wpm2aws-change-instance-region-check' => $_POST['wpm2aws-console-change-instance-region-cross-check'],
            'wpm2aws-change-instance-region-new-region-ref' => $_POST['wpm2aws-console-change-instance-region-new-region-ref'],
            'wpm2aws-change-instance-region-use-snapshot-ref' => $_POST['wpm2aws-console-change-instance-region-use-snapshot-ref'],
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Change Instance Region<br><br>Invalid Input', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }


        // Change the Instance Region
        try {
            $changedRegion = $this->changeAwsInstanceRegion(
                $validatedInputs['wpm2aws-change-instance-region-new-region-ref'],
                $validatedInputs['wpm2aws-change-instance-region-use-snapshot-ref']
            );
        } catch (Exception $e) {
            // wp_die($e->getMessage());
            $msg = 'Error!<br><br>Instance Region not Changed (1)<br><br>' . $e->getMessage() . '<br><br>Please Try Again';
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $msg);
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        if ($changedRegion) {
            // Set the Admin Notice

            // If returning with SnapShot Copied Msg
            // Set the options for
            // New Region Name
            // New Region Snapshot Name
            // Return Message to User re: running next step in 5mins
            if (!empty($changedRegion[0]['operationType']) && 'CopySnapshot' === $changedRegion[0]['operationType']) {
                //Set the Message
                $userMsg = 'Your Snapshot: <strong>"' . $changedRegion[0]['resourceName'] . '"</strong> has been copied to the New Region';
                $userMsg .= '<br>This Snapshot copy is currently being generated in the New Region.';
                $userMsg .= '<br><br>Please wait 5 minutes and re-run the "Change Region" process to Launch your Instance';

                // Set the Pending Snapshot Region
                wpm2awsAddUpdateOptions('wpm2aws_console_copy_snapshot_pending_region', $changedRegion[0]['location']['regionName']);
                // Set the Pending Snapshot Name
                wpm2awsAddUpdateOptions('wpm2aws_console_copy_snapshot_pending_name', $changedRegion[0]['resourceName']);

                // Store the User Message
                set_transient('wpm2aws_admin_warning_notice_' . get_current_user_id(), __('<strong>Change Region</strong><br>Step 1 Complete!<br><br>' . $userMsg, 'migrate-2-aws'));
            } else {
                // Clear the Pending Snapshot Region
                wpm2awsAddUpdateOptions('wpm2aws_console_copy_snapshot_pending_region', '');

                // Clear the Pending Snapshot Name
                wpm2awsAddUpdateOptions('wpm2aws_console_copy_snapshot_pending_name', '');

                // Store the User Message
                set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Instance Region Changed', 'migrate-2-aws'));
            }
        } else {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Instance Region Not Changed (2)<br><br>Please Try Again', 'migrate-2-aws'));
        }

        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
    }

    /**
     * Change the Instance's Region
     * API Wrapper Function
     */
    public function changeAwsInstanceRegion($region, $snapshotName)
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        try {
            $changedRegion = $apiGlobal->changeInstanceRegion($region, $snapshotName);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (isset($changedRegion['error'])) {
            throw new Exception($changedRegion['error']);
        }

        return $changedRegion;
    }


    /**
     * Change the Instance's Plan
     * Parent Function
     * Validations before passing to
     * API Wrapper Function
     */
    public function changeInstancePlan()
    {
        $validatePost = wpm2awsValidatePost('console-change-instance-plan');

        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Change Instance Plan (1)<br><br>Invalid/Insufficient parameters set', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        $requiredInputs = array(
            'wpm2aws-change-instance-plan-check' => $_POST['wpm2aws-console-change-instance-plan-cross-check'],
            'wpm2aws-change-instance-plan-new-plan-ref' => $_POST['wpm2aws-console-change-instance-plan-new-plan-ref'],
            'wpm2aws-change-instance-plan-use-snapshot-ref' => $_POST['wpm2aws-console-change-instance-plan-use-snapshot-ref'],
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Change Instance Plan<br><br>Invalid Input', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }


        // Change the Instance Plan
        try {
            $changedPlan = $this->changeAwsInstancePlan(
                $validatedInputs['wpm2aws-change-instance-plan-new-plan-ref'],
                $validatedInputs['wpm2aws-change-instance-plan-use-snapshot-ref']
            );
        } catch (Exception $e) {
            // wp_die($e->getMessage());
            $msg = 'Error!<br><br>Instance Plan not Changed<br><br>' . $e->getMessage() . '<br><br>Please Try Again';
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $msg);
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
        }

        if ($changedPlan) {
            // wp_die(print_r($changedPlan));

            // Get the Instance IP#
            $changedPlanInstanceIp = $changedPlan['new-instance-details']['publicIpAddress'];
            // Get the Instance Name
            $changedPlanInstanceName = $changedPlan['new-instance-details']['name'];

            // Set the Options for future use
            wpm2awsAddUpdateOptions('wpm2aws_console_changed_plan_instance_ip', $changedPlanInstanceIp);
            wpm2awsAddUpdateOptions('wpm2aws_console_changed_plan_instance_name', $changedPlanInstanceName);


            // Set the Admin Notice
            $msg = '';
            $msg .= 'Success!<br><br>Bundle Plan Changed';
            $msg .= '<br><br>';
            $msg .= 'View site on New Plan: <a href="http://' . $changedPlanInstanceIp . '" target="_blank">' . $changedPlanInstanceIp . '</a>';
            $msg .= '<br><br>';
            $msg .= 'To switch your site over to this New Plan, please contact us to complete the process';
            $msg .= '<br><br>';
            $msg .= '<a class="button button-primary" href="mailto:' . WPM2AWS_SEAHORSE_EMAIL_ADDRESS . '">Email Us</a>';

            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __($msg, 'migrate-2-aws'));
        } else {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Instance Plan Not Changed<br><br>Please Try Again', 'migrate-2-aws'));
        }

        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws-console')));
    }

    /**
     * Change the Instance's Plan
     * API Wrapper Function
     */
    public function changeAwsInstancePlan($planId, $snapshotName)
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        try {
            $changedPlan = $apiGlobal->remoteChangeInstancePlan($planId, $snapshotName);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        // try {
        //     $changedPlan = $apiGlobal->changeInstancePlan($planId, $snapshotName);
        // } catch (Exception $e) {
        //     throw new Exception($e->getMessage());
        // }
        if (isset($changedPlan['error'])) {
            throw new Exception($changedPlan['error']);
        }

        return $changedPlan;
    }
}
