<?php
// Sanitise Inputs
function wpm2awsValidateSanitizeInputs($inputs)
{
    foreach ($inputs as $inputKey => $inputVal) {
        $inputs[$inputKey] = sanitize_text_field($inputVal);
    }
    return $inputs;
}

// Validate Nonce
function wpm2awsValidateNonce($nonceName, $nonceAction)
{
    if (!isset($_POST['wpm2aws-' . $nonceName . '-nonce'])) {
        return false;
    }

    if (!wp_verify_nonce($_POST['wpm2aws-' . $nonceName . '-nonce'], $nonceAction)) {
        return false;
    }
    return true;
}

// Validate POST Parameters
function wpm2awsValidatePost($form)
{
    if (empty($_POST)) {
        return false;
    }

    if ($form === 'register-licence-form') {
        if (empty($_POST['wpm2aws_licence_key']) || empty($_POST['wpm2aws_licence_email'])) {
            return false;
        }
        return wpm2awsValidateNonce('register-licence', 'wpm2aws_register_licence_form');
    } elseif ($form === 'iam-form') {
        if (empty($_POST['wpm2aws_iamid']) || empty($_POST['wpm2aws_iampw'])) {
            return false;
        }
        return wpm2awsValidateNonce('iam-validate', 'wpm2aws_iam_form');
    } elseif ($form === 'aws-region') {
        if (empty($_POST['wpm2aws_awsRegionSelect'])) {
            return false;
        }
        return wpm2awsValidateNonce('select-aws-region', 'wpm2aws_aws_region');
    } elseif ($form === 'aws-s3-existing-bucket') {
        if (empty($_POST['wpm2aws_s3BucketNameExisting'])) {
            return false;
        }
        return wpm2awsValidateNonce('use-s3-bucket', 'wpm2aws_s3_use_bucket');
    } elseif ($form === 'aws-s3-bucket-name') {
        if (empty($_POST['wpm2aws_s3BucketName'])) {
            return false;
        }
        return wpm2awsValidateNonce('create-s3-bucket', 'wpm2aws_s3_create_bucket');
    } elseif ($form === 'aws-s3-upload-name') {
        if (empty($_POST['wpm2aws_uploadDirectoryName'])) {
            return false;
        }
        return wpm2awsValidateNonce('set-upload-directory-name', 'wpm2aws_upload-directory-name');
    } elseif ($form === 'aws-s3-upload-path') {
        if (empty($_POST['wpm2aws_uploadDirectoryPath'])) {
            return false;
        }
        return wpm2awsValidateNonce('set-upload-directory-path', 'wpm2aws_upload-directory-path');
    } elseif ($form === 'aws-lightsail-name') {
        if (empty($_POST['wpm2aws_lightsailName'])) {
            return false;
        }
        if (wpm2awsRegexAlphaNumericDashUnderscore($_POST['wpm2aws_lightsailName']) === false) {
            return false;
        }
        return wpm2awsValidateNonce('set-lightsail-instance-name', 'wpm2aws_lightsail-name');
    } elseif ($form === 'aws-lightsail-name-and-size') {
        if (empty($_POST['wpm2aws_lightsailName'])) {
            return false;
        }
        if (wpm2awsRegexAlphaNumericDashUnderscore($_POST['wpm2aws_lightsailName']) === false) {
            return false;
        }
        if (empty($_POST['wpm2aws_lightsailInstanceSizeSelect'])) {
            return false;
        }

        return wpm2awsValidateNonce('set-lightsail-instance-name-and-size', 'wpm2aws_lightsail-name-and-size');
    } elseif ($form === 'aws-lightsail-region') {
        if (empty($_POST['wpm2aws_lightsailRegionSelect'])) {
            return false;
        }
        return wpm2awsValidateNonce('set-lightsail-instance-region', 'wpm2aws_lightsail-region');
    } elseif ($form === 'aws-lightsail-size') {
         if (empty($_POST['wpm2aws_lightsailInstanceSizeSelect'])) {
             return false;
         }
	         return wpm2awsValidateNonce('set-lightsail-instance-size', 'wpm2aws_lightsail-size');
    } elseif ($form === 'domain-name') {
        if (empty($_POST['wpm2aws_lightsailDomainName'])) {
            return false;
        }
        return wpm2awsValidateNonce('set-domain-name', 'wpm2aws_domainName');
    } elseif ($form === 'console-add-new-alarm-form') {
        if (
            empty($_POST['wpm2aws-add-alarm-name']) ||
            empty($_POST['wpm2aws-add-alarm-select-comparison']) ||
            empty($_POST['wpm2aws-add-alarm-comparison-value']) ||
            empty($_POST['wpm2aws-add-alarm-frequency-value']) ||
            (
                empty($_POST['wpm2aws-add-alarm-select-time-hours']) &&
                empty($_POST['wpm2aws-add-alarm-select-time-minutes'])
            )
        ) {
            return false;
        }

        return wpm2awsValidateNonce('add-new-metric-alarm-form', 'wpm2aws_add_new_metric_alarm_form');
    } elseif ($form === 'console-reboot-instance') {
        if (empty($_POST['wpm2aws-console-reboot-instance-cross-check']) || 'reboot-ok' !== $_POST['wpm2aws-console-reboot-instance-cross-check']) {
            return false;
        }

        return wpm2awsValidateNonce('console-reboot-instance', 'wpm2aws_console_reboot_instance');
    } elseif ($form === 'console-create-instance-snapshot') {
        if (empty($_POST['wpm2aws-console-create-instance-snapshot-cross-check']) || 'create-snapshot-ok' !== $_POST['wpm2aws-console-create-instance-snapshot-cross-check']) {
            return false;
        }
        if (empty($_POST['wpm2aws-console-create-instance-snapshot-name'])) {
            return false;
        }

        return wpm2awsValidateNonce('console-create-instance-snapshot', 'wpm2aws_console_create_instance_snapshot');
    } elseif ($form === 'console-change-instance-region') {
        if (empty($_POST['wpm2aws-console-change-instance-region-cross-check']) || 'change-region-ok' !== $_POST['wpm2aws-console-change-instance-region-cross-check']) {
            return false;
        }
        if (empty($_POST['wpm2aws-console-change-instance-region-new-region-ref'])) {
            return false;
        }
        if (empty($_POST['wpm2aws-console-change-instance-region-use-snapshot-ref'])) {
            return false;
        }

        return wpm2awsValidateNonce('console-change-instance-region', 'wpm2aws_console_change_instance_region');
    } elseif ($form === 'console-change-instance-plan') {
        if (empty($_POST['wpm2aws-console-change-instance-plan-cross-check']) || 'change-plan-ok' !== $_POST['wpm2aws-console-change-instance-plan-cross-check']) {
            return false;
        }
        if (empty($_POST['wpm2aws-console-change-instance-plan-new-plan-ref'])) {
            return false;
        }
        if (empty($_POST['wpm2aws-console-change-instance-plan-use-snapshot-ref'])) {
            return false;
        }

        return wpm2awsValidateNonce('console-change-instance-plan', 'wpm2aws_console_change_instance_plan');
    } else {
        return false;
    }
    return false;
}

function wpm2awsRegexAlphaNumericDashUnderscore($string)
{
    // Regex to check that string contains only
    // Alpha Numerics
    // Dash/Hyphen (-)
    // Underscore (_)
    // ^[a-zA-Z0-9-_]+$
    if (preg_match("/^[a-zA-Z0-9-_]+$/", $string)) {
        return true;
    }
    return false;
}
