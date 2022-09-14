<?php

class WPM2AWS_Migrate
{
    public function __construct()
    {
        if (!function_exists('get_plugin_data')) {
            add_action('admin_init', array($this, 'pluginData'));
        }
    }

    /**
     * Called in construct()
     */
    public function pluginData()
    {
        require_once(get_home_path() . 'wp-admin/includes/plugin.php');
    }

    /**
     * Called in Settings
     * Calls registerLicenceForm()
     */
    public function registerLicence()
    {
        add_action('admin_post_wpm2aws_register_licence_form', array($this, 'registerLicenceForm'));
    }

    /**
     * Called from registerLicence()
     */
    public function registerLicenceForm()
    {
        $validatePost = wpm2awsValidatePost('register-licence-form');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Licence Key<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-licence-key' => $_POST['wpm2aws_licence_key'],
            'wpm2aws-licence-email' => $_POST['wpm2aws_licence_email'],
        );
        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Licence Key<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Register The Licence
        $registered = $this->registerSeahorseLicence($validatedInputs);

        if (!$registered) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Licence Key - Invalid Licence Key/Email', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }
        set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Licence Key - Registered', 'migrate-2-aws'));

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in Settings
     * Calls saveIamForm()
     */
    public function validateInputs()
    {
        add_action('admin_post_wpm2aws_iam_form', array($this, 'saveIamForm'));
    }

    /**
     * Called from validateInputs()
     */
    public function saveIamForm()
    {
        $validatePost = wpm2awsValidatePost('iam-form');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>IAM Credentials<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-iamid' => $_POST['wpm2aws_iamid'],
            'wpm2aws-iampw' => $_POST['wpm2aws_iampw']
        );
        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>IAM Credentials<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Set the Options
        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        // Check if this is a valid User
        $user = $this->verifyIam();

        // Set the User Name Option
        if ($user) {
            $validatedInputs['wpm2aws-iam-user'] = $user;

            // Set the Options
            wpm2awsAddUpdateOptions('wpm2aws-iam-user', $user);
            wpm2awsLogRAction('wpm2aws_validate_iam_user_success', $user);

            // Get a List of the Users Existing Buckets
            if (get_option('wpm2aws-customer-type') === 'self') {
                $this->getBuckets(true);  // Updated to Remote
            }

            // Create a Default Bucket Name for User
            $awsResourceName = WPM2AWS_PLUGIN_AWS_RESOURCE . '-' . strtolower(get_option('wpm2aws-iam-user'));
            $awsLightsailName = strtolower(get_option('wpm2aws-iam-user')) . '-' . WPM2AWS_PLUGIN_AWS_RESOURCE;

            if (
                false !==  get_option('wpm2aws_valid_licence_type') &&
                'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
            ) {
                $awsResourceNameAddition = get_option('wpm2aws_licence_email');

                $awsResourceName .= '-' . $awsResourceNameAddition;
                $awsLightsailName .= '-' . $awsResourceNameAddition;
            }

            // Make sure string is valid bucket name
            $awsResourceName = sanitizeTrailUserEmailToAwsBucketName($awsResourceName);

            // Set The value for deafult bucket name
            wpm2awsAddUpdateOptions('wpm2aws-aws-s3-default-bucket-name', $awsResourceName);

            // Trial or managed hosting
            if (get_option('wpm2aws-customer-type') === 'managed') {
                // Set AWS Region as the Default
                wpm2awsAddUpdateOptions('wpm2aws-aws-region', WPM2AWS_PLUGIN_AWS_REGION);

                // Create A Bucket
                $bucket = $this->createBucket();

                if ($bucket) {
                    // Update Bucket Name
                    wpm2awsAddUpdateOptions('wpm2aws-aws-s3-bucket-name', $awsResourceName);
                    wpm2awsLogRAction('wpm2aws_create_bucket_success', $awsResourceName);

                    // sns trigger to send mail after S3 bucket created
                    try {
                        $this->triggerSNSAlert(get_option('wpm2aws-aws-region'), 'S3', $user);
                    } catch (Exception $e) {
                        wpm2awsLogAction('Error: saveIamForm->triggerSNSAlert: ' . $e->getMessage());
                    }

                    // Make sure string is valid lightsail name
                    $awsLightsailName = sanitizeTrailUserEmailToLightsailName($awsLightsailName);

                    // Set the Name of the AWS Instance
                    wpm2awsAddUpdateOptions('wpm2aws-aws-lightsail-name', $awsLightsailName);

                    // Set the Region of the AWS Instance
                    wpm2awsAddUpdateOptions('wpm2aws-aws-lightsail-region', WPM2AWS_PLUGIN_AWS_REGION);

                    //Set the Size of AWS Instance
                    wpm2awsAddUpdateOptions('wpm2aws-aws-lightsail-size', WPM2AWS_PLUGIN_AWS_LIGHTSAIL_SIZE);

                    // Set the AWS Region as per Default Region

                    wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 2);
                }
            }

            // Set the Admin Notice
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>IAM Credentials Validated<br><br>IAM User: ' . $user, 'migrate-2-aws'));
        } else {
            wpm2awsLogRAction('wpm2aws_validate_iam_user_error', "Invalid IAM Credentials");
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Invalid IAM Credentials<br><br>Please Try Again', 'migrate-2-aws'));
        }

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in Settings
     * Calls saveAwsRegion()
     */
    public function addUpdateRegion()
    {
        add_action('admin_post_wpm2aws_aws_region', array($this, 'saveAwsRegion'));
    }

    /**
     * Called from addUpdateRegion()
     */
    public function saveAwsRegion()
    {
        $validatePost = wpm2awsValidatePost('aws-region');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set AWS Region<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-region' => $_POST['wpm2aws_awsRegionSelect']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set AWS Region<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Also Update AWS Region
        $validatedInputs['wpm2aws-aws-lightsail-region'] = $validatedInputs['wpm2aws-aws-region'];

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        // Get a List of the Users Existing Buckets
        if (get_option('wpm2aws-customer-type') === 'self' && get_option('wpm2aws-existingBucketNames') === false) {
            $this->getBuckets(true); // Updated to Remote
        }

        wpm2awsLogRAction('wpm2aws_save_region_success', $validatedInputs['wpm2aws-aws-lightsail-region']);

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called from Settings
     * Calls saveS3Bucket()
     */
    public function addUpdateS3BucketName()
    {
        add_action('admin_post_wpm2aws_s3_bucket', array($this, 'saveS3Bucket'));
        add_action('admin_post_wpm2aws_s3_create_bucket', array($this, 'saveS3Bucket'));
        add_action('admin_post_wpm2aws_s3_use_bucket', array($this, 'saveS3Bucket'));
    }

    /**
     * Called in addUpdateS3BucketName()
     */
    public function saveS3Bucket()
    {
        $validatePost = wpm2awsValidatePost('aws-s3-existing-bucket');
        if ($validatePost === true) {
            $this->useS3Bucket();
            return;
        }

        $validatePost = wpm2awsValidatePost('aws-s3-bucket-name');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set S3 Bucket Name<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-s3-bucket-name' => $_POST['wpm2aws_s3BucketName']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set S3 Bucket Name<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        if (!wpm2awsValidateBucketName($_POST['wpm2aws_s3BucketName'])) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set S3 Bucket Name<br><br>Must Conform to URI Standards - <a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/bucketnamingrules.html" target="_blank">View Bucket Naming Rules here</a>', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Set the Options
        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        // create the bucket
        $bucket = $this->createBucket(false);

        // Get a List of the Users Existing Buckets
        if ($bucket) {
            $this->getBuckets(); // Updated to Remote

            // sns trigger to send mail after S3 bucket created
            try {
                $this->triggerSNSAlert(get_option('wpm2aws-aws-region'), 'S3', $user);
            } catch (Exception $e) {
                wpm2awsLogAction('Error: saveS3Bucket->triggerSNSAlert: ' . $e->getMessage());
            }
        }

        // Set the Admin Notice
        if ($bucket) {
            wpm2awsLogRAction('wpm2aws_bucket_created_success', $bucket);
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>New Bucket Created<br><br>Bucket Name: ' . $user, 'migrate-2-aws'));
        } else {
            wpm2awsLogRAction('wpm2aws_bucket_created_fail', 'Invalid Bucket Details');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Set S3 Bucket Name<br><br>Must Conform to URI Standards - <a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/bucketnamingrules.html" target="_blank">View Bucket Naming Rules here</a>', 'migrate-2-aws'));
        }

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in saveS3Bucket()
     */
    private function useS3Bucket()
    {
        $validatePost = wpm2awsValidatePost('aws-s3-existing-bucket');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Use S3 Bucket<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-s3-existing-bucket' => $_POST['wpm2aws_s3BucketNameExisting'],
            'wpm2aws-aws-s3-bucket-name' => $_POST['wpm2aws_s3BucketNameExisting']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            wpm2awsLogRAction('wpm2aws_use_bucket_fail', 'Use S3 Bucket - Required Input is Empty');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Use S3 Bucket<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsLogRAction('wpm2aws_use_bucket_success', 'Bucket Name: ' . $validatedInputs['wpm2aws-aws-s3-bucket-name']);

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called from Settings
     * Calls setUploadName()
     */
    public function setS3UploadName()
    {
        add_action('admin_post_wpm2aws_upload-directory-name', array($this, 'setUploadName'));
    }

    /**
     * Called from setS3UploadName()
     */
    public function setUploadName()
    {
        $validatePost = wpm2awsValidatePost('aws-s3-upload-name');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Upload Directory Name<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-s3-upload-directory-name' => $_POST['wpm2aws_uploadDirectoryName']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Upload Directory Name<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called from Settings
     * Calls setUploadPath()
     */
    public function setS3UploadPath()
    {
        add_action('admin_post_wpm2aws_upload-directory-path', array($this, 'setUploadPath'));
    }

    /**
     * Called from setS3UploadPath()
     */
    public function setUploadPath()
    {
        $validatePost = wpm2awsValidatePost('aws-s3-upload-path');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Upload Directory Path<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-s3-upload-directory-path' => $_POST['wpm2aws_uploadDirectoryPath']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Upload Directory Path<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called from Settings
     * Calls saveLightsailName()
     */
    public function addUpdateLightsailName()
    {
        add_action('admin_post_wpm2aws_lightsail-name', array($this, 'saveLightsailName'));
    }

    /**
     * Called from Settings
     * Calls saveLightsailNameAndSize()
     */
    public function addUpdateLightsailNameAndSize()
    {
        add_action('admin_post_wpm2aws_lightsail-name-and-size', array($this, 'saveLightsailNameAndSize'));
    }

    /**
     * Called in addUpdateLightsailName()
     */
    public function saveLightsailName()
    {
        $validatePost = wpm2awsValidatePost('aws-lightsail-name');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Instance Name<br><br>Invalid/Incomplete Input<br><br>Name can contain letters and numbers; hyphen (-) and underscore (_) characters may separate words', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-lightsail-name' => $_POST['wpm2aws_lightsailName']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            wpm2awsLogRAction('wpm2aws_set_lightsail_name_error', 'AWS Instance Name - Required Input is Empty');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Instance Name<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsLogRAction('wpm2aws_set_lightsail_name_success', 'AWS Name: ' . $validatedInputs['wpm2aws-aws-lightsail-name']);

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in addUpdateLightsailNameAndSize()
     */
    public function saveLightsailNameAndSize()
    {
        $validatePost = wpm2awsValidatePost('aws-lightsail-name-and-size');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Instance Information<br><br>Invalid/Incomplete Input<br><br>Name can contain letters and numbers; hyphen (-) and underscore (_) characters may separate words', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-lightsail-name' => $_POST['wpm2aws_lightsailName'],
            'wpm2aws-aws-lightsail-size' => $_POST['wpm2aws_lightsailInstanceSizeSelect']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);

        if (empty($validatedInputs)) {
            wpm2awsLogRAction('wpm2aws_set_lightsail_info_error', 'AWS Instance Information - Required Input is Empty');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Instance Information<br><br>Required Input is Empty', 'migrate-2-aws'));
            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsLogRAction('wpm2aws_set_lightsail_info_success', 'AWS Name: ' . $validatedInputs['wpm2aws-aws-lightsail-name']);
        wpm2awsLogRAction('wpm2aws_set_lightsail_info_success', 'AWS Size: ' . $validatedInputs['wpm2aws-aws-lightsail-size']);
        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in Settings
     * Calls saveLightsailRegion()
     */
    public function addUpdateLightsailRegion()
    {
        add_action('admin_post_wpm2aws_lightsail-region', array($this, 'saveLightsailRegion'));
    }

    /**
     * Called From addUpdateLightsailRegion()
     */
    public function saveLightsailRegion()
    {
        $validatePost = wpm2awsValidatePost('aws-lightsail-region');
        if ($validatePost === false) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Region<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $requiredInputs = array(
            'wpm2aws-aws-lightsail-region' => $_POST['wpm2aws_lightsailRegionSelect']
        );

        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            wpm2awsLogRAction('wpm2aws_set_lightsail_region_error', 'AWS Instance Region - Required Input is Empty');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Region<br><br>Required Input is Empty', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsLogRAction('wpm2aws_set_lightsail_region_success', 'AWS Region: ' . $validatedInputs['wpm2aws-aws-lightsail-region']);

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }


    /**
      * Called in Settings
      * Calls saveLightsailInstanceSize()
      */
     public function addUpdateLightsailSize()
     {
         add_action('admin_post_wpm2aws_lightsail-size', array($this, 'saveLightsailInstanceSize' ));
     }

     /**
      * Called From addUpdateLightsailSize()
      */
     public function saveLightsailInstanceSize()
     {
         $validatePost = wpm2awsValidatePost('aws-lightsail-size');
         if ($validatePost === false) {
             set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Instance Size is not valid<br><br>Invalid/Incomplete Input', 'migrate-2-aws'));
             exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
         }

         $requiredInputs = array(
             'wpm2aws-aws-lightsail-size' => $_POST['wpm2aws_lightsailInstanceSizeSelect']
         );

         $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
         if (empty($validatedInputs)) {
             wpm2awsLogRAction('wpm2aws_set_lightsail_region_error', 'AWS Instance Size - Required Input is Empty');
             set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>AWS Size<br><br>Required Input is Empty', 'migrate-2-aws'));
             exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
         }

         foreach ($validatedInputs as $valInKey => $valInVal) {
             wpm2awsAddUpdateOptions($valInKey, $valInVal);
         }

         wpm2awsLogRAction('wpm2aws_set_lightsail_size_success', 'AWS Instance Size: ' . $validatedInputs['wpm2aws-aws-lightsail-size']);
         exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
	  }

    /**
     * Called in registerLicenceForm()
     */
    public function registerSeahorseLicence($licenceDetails)
    {
        if (empty($licenceDetails['wpm2aws-licence-key'])) {
            return false;
        }

        if (empty($licenceDetails['wpm2aws-licence-email'])) {
            return false;
        }

        return $register = wpm2aws_register_licence($licenceDetails);
    }

    /**
     * Called in saveIamForm()
     * Called in createLightsailAdmin()
     * Called in createLightsailZippedRemote()
     */
    public function verifyIam()
    {
        $apiRemote = new WPM2AWS_ApiRemoteIam();
        $user = $apiRemote->getIamUser();

        wpm2awsAddUpdateOptions('wpm2aws-customer-type', $user['user-type']);
        return $user['user-name'];
    }

    /**
     * Called in Settings
     * Calls getBuckets()
     */
    public function getExistingS3Buckets()
    {
        add_action('admin_post_wpm2aws_s3_refresh_bucket_list', array($this, 'getBuckets'));
        add_action('admin_post_wpm2aws-run-full-migration', array($this, 'getBuckets'));
    }

    /**
     * Called from getExistingS3Buckets()
     * Called from saveIamForm()
     * Called from saveS3Bucket()
     */
    public function getBuckets($subFunction = false)
    {
        // $apiGlobal = new WPM2AWS_ApiGlobal();
        // $buckets = $apiGlobal->getBucketList();

        $apiRemote =  new WPM2AWS_ApiRemoteS3();
        $buckets = $apiRemote->getBucketList();

        // TODO: Check if empty $buckets
        if (count($buckets) < 1) {
            set_transient('wpm2aws_admin_warning_notice_' . get_current_user_id(), __('Warning!<br><br>There are currently no S3 Buckets connected with this AWS User<br><br>Please Create a Bucket', 'migrate-2-aws'));

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        wpm2awsAddUpdateOptions('wpm2aws-existingBucketNames', $buckets);

        if ($subFunction) {
            return true;
        }

        set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>S3 Bucket List Refreshed', 'migrate-2-aws'));

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in saveIamForm()
     * Called in saveS3Bucket()
     */
    public function createBucket($restricted = true)
    {
        // $apiGlobal = new WPM2AWS_ApiGlobal();
        // return $bucket = $apiGlobal->createBucket($restricted);

        $apiRemote =  new WPM2AWS_ApiRemoteS3();

        $bucketExists = $apiRemote->checkBucketExists();

        if ($bucketExists === 'true' || $bucketExists === true) {
            if (get_option('wpm2aws-customer-type') === 'managed') {
                return true;
            }

            wp_die('<strong>Bucket Already Exists</strong><br><br>Return to <a href="' . admin_url('/admin.php?page=wpm2aws') . '">Plugin Page</a>');
        }

        // Create New (including setting a lifecycle for Trial User Buckets)
        $newBucket = $apiRemote->createBucket($restricted);

        return $newBucket;
    }

    /**
     * Called in saveIamForm()
     * Called in saveS3Bucket()
     * Called in createLightsailAdmin()
     */
    public function triggerSNSAlert($region, $type, $id, $ip = null)
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();

        $sourceLocation = get_home_url();
        if (empty($sourceLocation)) {
            $sourceLocation = 'Unknown';
        }

        $licenceEmail = get_option('wpm2aws_licence_email');
        if (false === $licenceEmail || '' === $licenceEmail) {
            $licenceEmail = 'Unknown';
        }

        $message = 'An event has been triggered via the WPM2AWS Software';
        $message .= ' | Event Type : ' . $type;
        $message .= ' | Region : ' . $region;
        $message .= ' | IAM User : ' . $id;
        $message .= ' | Licence Email : ' . $licenceEmail;
        $message .= ' | Source Location : ' . $sourceLocation;

        if(null !== $ip) {
            $message .= ' | Instance IP: ' . $ip;
        }

        $topic = 'arn:aws:sns:' . $region . ':' . WPM2AWS_PLUGIN_AWS_NUMBER . ':WPM2AWS_' . $type . '_Created';

        try {
            $sns = $apiGlobal->triggerSNSAlert($message, $topic);
            return $sns;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }

    }

    /**
     * Called in Settings
     */
    public function createLightsailInstanceAdmin()
    {
        add_action('admin_post_wpm2aws-run-full-migration-admin', array($this, 'createLightsailAdmin'));
    }

    /**
     * Latest Version
     * Called in Settings
     */
    public function createLightsailInstanceZippedRemote()
    {
        add_action('admin_post_wpm2aws-run-full-migration', array($this, 'createLightsailZippedRemote'));
    }

    /**
     * Called in createLightsailInstanceAdmin()
     */
    public function createLightsailAdmin()
    {
        $apiGlobal = new WPM2AWS_ApiGlobal();
        $user = $this->verifyIam();
        $instance = $apiGlobal->createLightsail('admin');

        // sns trigger to send mail after AWS Instance created
        try {
            $this->triggerSNSAlert(get_option('wpm2aws-aws-lightsail-region'), 'LS', $user, $instance['publicIpAddress']);
        } catch (Exception $e) {
            wpm2awsLogAction('Error: createLightsailAdmin->triggerSNSAlert: ' . $e->getMessage());
        }

        set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>AWS Launched<br><br><a href="http://' . $instance['publicIpAddress'] . '/" target="_blank">' . $instance['publicIpAddress'] . '</a>', 'migrate-2-aws'));

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Latest Version
     * Called in createLightsailInstanceZippedRemote()
     */
    public function createLightsailZippedRemote()
    {
        try {
            $apiRemote = new WPM2AWS_ApiRemote();
        } catch (Throwable $e) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $e->getMessage());

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $e->getMessage());

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        $user = $this->verifyIam();

        try {
            $instance = $apiRemote->createLightsailFromZip();
        } catch (Throwable $e) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $e->getMessage());

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), $e->getMessage());

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        wpm2awsAddUpdateOptions(
            'wpm2aws-lightsail-instance-details',
            array(
                'name' => $instance['name'],
                'region' => $instance['region'],
                'size' => $instance['size'],
                'publicIp' => $instance['publicIp'],
                'accessControl' => $instance['accessControl'],
                'details' => $instance['details']
            )
        );

        if (!empty($instance['key-pair-details'])) {
            wpm2awsAddUpdateOptions('wpm2aws_lightsail_ssh', $instance['key-pair-details']);
        }

        $msg =  __('Success! Site clone is building.', 'migrate-2-aws');
        set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), $msg);

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in Settings
     * Calls addUpdateDomainName()
     */
    public function updateDomainName()
    {
        add_action('admin_post_wpm2aws_domainName', array($this, 'addUpdateDomainName'));
    }

    /**
     * Called from updateDomainName()
     */
    public function addUpdateDomainName()
    {
        $validatePost = wpm2awsValidatePost('domain-name');
        if ($validatePost === false) {
            //ToDo: Return User Error Notice
            wp_die('Invalid Post Data.<br><br>Return to <a href="' . admin_url('/admin.php?page=wpm2aws') . '">Plugin Page</a>');
        }

        $requiredInputs = array(
            'wpm2aws-aws-lightsail-domain-name' => $_POST['wpm2aws_lightsailDomainName']
        );
        $validatedInputs = wpm2awsValidateSanitizeInputs($requiredInputs);
        if (empty($validatedInputs)) {
            //ToDo: Return User Error Notice

            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));

            wp_die('Invalid Post Data.<br><br>Return to <a href="' . admin_url('/admin.php?page=wpm2aws') . '">Plugin Page</a>');
        }
        foreach ($validatedInputs as $valInKey => $valInVal) {
            wpm2awsAddUpdateOptions($valInKey, $valInVal);
        }

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in Settings
     * Main Reset All Button Action
     */
    public function resetAll()
    {
        add_action('admin_post_wpm2aws-reset-all-settings', array($this, 'removeTempZipDirs'));
        add_action('admin_post_wpm2aws-reset-all-settings', array($this, 'clearAllSavedOptions'));
    }

    /**
     * Called in Settings
     * Main "restart" button action
     */
    public function restartProcess()
    {
        add_action('admin_post_wpm2aws-restart-process', array($this, 'removeTempZipDirs'));
        add_action('admin_post_wpm2aws-restart-process', array($this, 'clearProcessSavedOptions'));
    }

    /**
     * Called from resetAll()
     */
    public function removeTempZipDirs()
    {
        // Remove the temp directory for zipped files
        $pluginsPath = str_replace('wp-migrate-2-aws', '', WPM2AWS_PLUGIN_DIR);
        $zipTempDirectory = $pluginsPath . WPM2AWS_ZIP_EXPORT_PATH;

        deleteDirectoryTree($zipTempDirectory);

        if (file_exists($zipTempDirectory . '.zip')) {
            unlink($zipTempDirectory . '.zip');
        }
    }

    /**
     * Called from resetAll()
     */
    public function clearAllSavedOptions($reloadPage = true)
    {
        $options = $this->getAllSavedOptions();

        $notice = 'Notice! All settings have been reset.';

        $this->clearOptionsUploadsAndLogs($options, $notice, $reloadPage);
    }

    /**
     * Called from restartAll()
     *
     * @param  bool  $reloadPage
     * @return void
     */
    public function clearProcessSavedOptions($reloadPage = true)
    {
        $options = $this->getProcessSavedOptions();

        $notice = 'Notice! The process has been reset and can be re-started.';

        $this->clearOptionsUploadsAndLogs($options, $notice, $reloadPage);
    }

    /**
     * Get an array of the wp_options that relate to the "process" of the migration
     *
     * @return array
     */
    private function getProcessSavedOptions()
    {
        return array(
            'wpm2aws_current_active_step',
            'wpm2aws-aws-region',

            'wpm2aws-aws-s3-bucket-name',
            'wpm2aws-aws-s3-default-bucket-name',
            'wpm2aws-existingBucketNames',
            'wpm2aws-aws-s3-existing-bucket',

            'wpm2aws-aws-s3-upload-directory-name',
            'wpm2aws-aws-s3-upload-directory-path',

            'wpm2aws-aws-lightsail-name',
            'wpm2aws-aws-lightsail-region',
            'wpm2aws-aws-lightsail-size',
            'wpm2aws-aws-lightsail-domain-name',

            'wpm2aws_db_size_warning',
            'wpm2aws_database_size',
            'wpm2aws_download_db_over_sized_tables',
            'wpm2aws_download_db_started',
            'wpm2aws_download_db_complete',

            'wpm2aws_upload_process_start_time',
            'wpm2aws_upload_complete',
            'wpm2aws_upload_started',
            'wpm2aws_upload_failures',
            'wpm2aws_upload_counter',
            'wpm2aws_upload_errors',

            'wpm2aws_admin_upload_complete',
            'wpm2aws_admin_upload_started',
            'wpm2aws_admin_upload_failures',
            'wpm2aws_admin_upload_counter',
            'wpm2aws_admin_upload_errors',

            'wpm2aws_excluded_over_sized_directories_files',
            'wpm2aws_uploads_directory_fully_excluded',
            'wpm2aws_exclude_dirs_from_zip_process',
            'wpm2aws_console_changed_plan_instance_name',
            'wpm2aws_console_changed_plan_instance_ip',
            'wpm2aws_console_copy_snapshot_pending_name',
            'wpm2aws_console_copy_snapshot_pending_region',

            'wpm2aws_fszipper_complete',
            'wpm2aws_fszipper_started',
            'wpm2aws_fszipper_failures',
            'wpm2aws_fszipper_counter',
            'wpm2aws_fszipper_errors',

            'wpm2aws_zipped_fs_upload_complete',
            'wpm2aws_zipped_fs_upload_started',
            'wpm2aws_zipped_fs_upload_failures',
            'wpm2aws_zipped_fs_upload_counter',
            'wpm2aws_zipped_fs_upload_errors',

            'wpm2aws_bgProcessAttempts',

            'wpm2aws-lightsail-instance-details',
        );
    }

    /**
     * Get an array of all the wp_options that relate to wpm2aws
     *
     * @return array
     */
    private function getAllSavedOptions()
    {
        $processOptions = $this->getProcessSavedOptions();

        $nonProcessOptions = array(
            'wpm2aws_valid_licence',
            'wpm2aws-licence-key',
            'wpm2aws_licence_key',
            'wpm2aws_licence_email',
            'wpm2aws_licence_url',

            'wpm2aws_valid_licence_type',
            'wpm2aws_valid_licence_plan',
            'wpm2aws_valid_licence_keyp',
            'wpm2aws_valid_licence_keys',
            'wpm2aws_valid_licence_dyck',

            'wpm2aws-customer-type',

            'wpm2aws-iamid',
            'wpm2aws-iampw',
            'wpm2aws-iam-user',

            'wpm2aws-redirect-home-name',
        );

        return \array_merge($processOptions, $nonProcessOptions);
    }

    /**
     * Clears the given options from the wp_log
     * Deletes all pending uploads
     * Deletes all logs
     * Returns a notice to the user
     *
     * @param array $options
     * @param  string  $notice
     *
     * @return void
     */
    private function clearOptionsUploadsAndLogs($options, $notice, $reloadPage)
    {
        foreach ($options as $option) {
            delete_option($option);
        }

        $pendingUploadsNotice = $this->deleteBatchQueue();

        $combinedNotice = $notice . ' ' . $pendingUploadsNotice;

        $this->deleteLogs();

        if ($reloadPage === false) {
            wp_die($pendingUploadsNotice . 'Options Cleared<br><br>Return to <a href="' . admin_url('/admin.php?page=wpm2aws') . '">Plugin Page</a>');
        }

        set_transient(
            'wpm2aws_admin_error_notice_' . get_current_user_id(),
            __($combinedNotice, 'migrate-2-aws')
        );

        wpm2awsWpRedirectAndExit();
//        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Called in clearOptionsUploadsAndLogs()
     *
     * @return string
     */
    private function deleteBatchQueue()
    {
        global $wpdb;

        $existingBatches = $wpdb->get_results(
            "SELECT option_name
            FROM {$wpdb->prefix}options
            WHERE option_name
            LIKE 'wp_wpm2aws-uploader-all_batch_%'
            OR
            option_name
            LIKE 'wp_wpm2aws-admin-uploader-all_batch_%'
            OR
            option_name
            LIKE 'wp_wpm2aws-fszipper-all_batch_%'
            OR
            option_name
            LIKE 'wp_wpm2aws-zipped-fs-uploader-all_batch_%'",
            OBJECT
        );

        if (!empty($existingBatches)) {
            foreach ($existingBatches as $batchIx => $batchDetails) {
                $optionName = $batchDetails->option_name;
                delete_option($optionName);
            }
        }

        return 'Pending File Uploads removed.';
    }

    /**
     * Deletes any logs stored in the various log files within the plugin.
     *
     * @return void
     */
    private function deleteLogs()
    {
        wpm2awsLogResetAll();
        wpm2awsZipLogResetAll();
        wpm2awsdownloadZipLogResetAll();
    }
}
