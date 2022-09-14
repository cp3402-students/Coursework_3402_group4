<?php

class WPM2AWS_AdminPages
{
    private static $uploadFromPath;

    public function __construct()
    {
    }


    public static function loadIAMform()
    {
        self::makePageHeading(1);

        WPM2AWS_WelcomePanel::template();

        /* Add admin notice */
        add_action('wpm2aws_admin_notices', 'wpm2aws_admin_error_notice');
        do_action('wpm2aws_admin_notices');

        if (false === get_option('wpm2aws_valid_licence') || '' === get_option('wpm2aws_valid_licence') || empty(get_option('wpm2aws_valid_licence'))) {
            self::makeValidateLicenceSection();
        } else {
            // Launch User Page
            wpm2awsUserSetup();

            self::$uploadFromPath = wpm2aws_content_dir();

            self::makeConfirmedLightsailInfoSection();

            self::makeInputsSection();

            self::makeSummarySection();
        }
    }

    private static function makeValidateLicenceSection()
    {
        echo '<div style="width:50%;margin:auto;margin-top:50px;">';
        echo '<div class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>Licence</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body wpm2aws-inputs-row-body-fw">';

        // Validate Licence
        $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('register_licence_form');
        self::generateInputSection(
            'Register Your Licence<br><small><em>(You should have received this from Seahorse)</em></small>',
            "wpm2aws_register_licence_form",
            $formElements,
            "register-licence",
            'Register Licence'
        );

        echo '</div>';
        echo '</div>';
        echo '</div>';

        return;
    }

    private static function generateInputSection($title, $formAction, $formElements, $reference, $label, $additionalInfo = null)
    {
        $disabledInput = '';
        if ("wpm2aws_aws_region" === $formAction) {
            if (
                false !==  get_option('wpm2aws_zipped_fs_upload_complete') &&
                'success' === get_option('wpm2aws_zipped_fs_upload_complete') &&
                (
                    false === get_option('wpm2aws_zipped_fs_upload_failures') ||
                    '' === get_option('wpm2aws_zipped_fs_upload_failures')
                )
            ) {
                $disabledInput = ' disabled="disabled"';
            }

            if (
                false !==  get_option('wpm2aws_valid_licence_type') &&
                'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
            ) {
                $disabledInput = ' disabled="disabled"';
            }
        }

        if ("wpm2aws_iam_form" === $formAction) {
            if (
                false !==  get_option('wpm2aws_valid_licence_type') &&
                'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
            ) {
                $disabledInput = ' readonly';
                $additionalInfo .= '<p style="color:red;">We have detected that you are running a trial version of WP on AWS.<br />Credentials have been pre-populated.<br />Please click "Save IAM Credentials" below to Proceeed.</p>';
            }
        }
        ?>
        <div class="wpm2aws-inputs-item">
            <h3><?php echo $title; ?></h3>

            <?php
            if (!empty($additionalInfo)) {
                echo $additionalInfo;
            }

            if (!empty($formElements)) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>?action=<?php echo $formAction; ?>">
                <?php
                foreach ($formElements as $element) : ?>
                        <div style="font-size: 13px; margin-bottom:3px;">
                        <label for="<?php echo esc_attr($element['field_id']); ?>"><strong><?php echo esc_attr($element['field_label']); ?>: </strong></label>
                        </div>
                    <?php if ($element['field_type'] === 'select') : ?>
                        <div>
                        <select id="<?php echo esc_attr($element['field_id']); ?>" name="<?php echo esc_attr($element['field_name']); ?>" <?php echo $disabledInput;?>>
                        <option value=""><?php echo esc_attr($element['field_placeholder']); ?></option>
                        <?php foreach ($element['field_data'] as $dataKey => $dataVal) : ?>
                            <?php
                            $selected = '';
                            if ($dataKey === $element['field_value'] || (string)$dataKey === (string)$element['field_value']) {
                                $selected = 'selected';
                            } ?>
                            <option value="<?php echo esc_attr($dataKey); ?>" <?php echo $selected; ?>><?php echo esc_attr($dataVal); ?></option>
                        <?php endforeach; ?>
                        </select>
                        </div>
                    <?php else : ?>
                        <div>
                        <input type = "<?php echo esc_attr($element['field_type']); ?>" name="<?php echo esc_attr($element['field_name']); ?>" id="<?php echo esc_attr($element['field_id']); ?>" placeholder="<?php echo esc_attr($element['field_placeholder']); ?>" value="<?php echo esc_attr($element['field_value']); ?>" class="wpm2aws-input-field" style="width:50%;"  <?php echo $disabledInput;?>/>
                        </div>
                    <?php endif; ?>
                    <br>
                <?php endforeach; ?>

                <input type="hidden" name="action" value="<?php echo $formAction; ?>" />
                <?php wp_nonce_field($formAction, 'wpm2aws-' . $reference . '-nonce'); ?>
                <p><input type="submit" class="button" value="<?php echo esc_attr(__($label, 'migrate-2-aws')); ?>" <?php echo $disabledInput;?>/></p>
                </form>
            <?php else : ?>
                <div class="wpm2aws_error">Error! No Input Fields</div>
            <?php endif; ?>

        </div>

        <?php
    }

    protected static function makePageHeading($page)
    {
        ?>
        <div class="wrap">
            <div style="width:75%;display:inline-block;">
                <div style="display:inline-block;height:25px;">
                    <img style="height:100%;" src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>assets/images/menu_icon.png"/>
                </div>
                <div style="display:inline-block">
                    <h1 class="wp-heading-inline">
                        &nbsp;
                        <?php echo($page == 1 ? esc_html(__(WPM2AWS_PAGE_TITLE_1, 'migrate-2-aws')) : esc_html(__(WPM2AWS_PAGE_TITLE_2, 'migrate-2-aws'))); ?>
                    </h1>
                </div>
            </div>

            <div style="display:inline-block;">
                <div style="margin-left:5px;margin-top:10px;">
                    <p style="display:inline-block;font-style: italic;margin: 0px;padding: 0px;">Brought to you by&nbsp;</p>
                    <div style="display:inline-block;height:auto;width:115px;">
                    <a href="<?php echo WPM2AWS_SEAHORSE_WEBSITE_URL; ?>" target="_blank">
                        <img style="height:auto;width:100%;" src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>assets/images/seahorse-logo_trimmed.png"/>
                    </a>
                </div>
                </div>
            </div>

        </div>

        <hr class="wp-header-end">

        <?php
    }

    /**
     * Prints the Input Section related
     * to AWS Setup Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeAwsSetupSection($verified, $managed)
    {
        // echo '<div id="wpm2aws-input-section-aws-settings">';
        echo '<div class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>Step 1. AWS Setup</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body">';

        // Validate Credentials
        $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('iam_form');
        self::generateInputSection(
            'Validate Credentials',
            "wpm2aws_iam_form",
            $formElements,
            "iam-validate",
            'Save IAM Credentials'
        );

        self::makeAwsRegionSection($verified, $managed);

        echo '</div>';
        echo '</div>';
        // echo '</div>';

        return;
    }

    private static function makeAwsSetupSectionHeader($verified, $managed)
    {
        $html = '';
        $html .= '<div class="wpm2aws-inputs-row">';
        $html .= '<div class="wpm2aws-inputs-row-header">';
        $html .= '<div>';
        $html .= '<h2 style="">AWS Setup';
        $html .= self::makeNavigateSectionButton('aws-settings', true);
        $html .= '</h2>';
        $html .= '<div style="clear:both;"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }


    private static function makeSectionTwoMain()
    {
        $html = '';
        $html .= '<div class="wpm2aws-inputs-row">';

        $html .= '<div class="wpm2aws-inputs-row-header">';
        $html .= '<h2>Step 2. Prepare Database</h2>';
        $html .= '</div>';

        $html .= '<div class="wpm2aws-inputs-row-body">';
        $html .= self::makeDataBaseDownloadSection(true);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeSectionThreeMain()
    {
        $html = '';
        $html .= '<div class="wpm2aws-inputs-row">';

        $html .= '<div class="wpm2aws-inputs-row-header">';
        $html .= '<h2>Step 3. Prepare File System</h2>';
        $html .= '</div>';

        $html .= '<div class="wpm2aws-inputs-row-body">';
        $html .= self::makePrepareFileSystemSection(true);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeSectionFourMain()
    {
        $html = '';
        $html .= '<div class="wpm2aws-inputs-row">';

        $html .= '<div class="wpm2aws-inputs-row-header">';
        $html .= '<h2>Step 4. Clone to AWS</h2>';
        $html .= '</div>';

        $html .= '<div class="wpm2aws-inputs-row-body">';
        $html .= self::makeZippedFileSystemUploadSection(true);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeSectionFiveMain()
    {
        $html = '';
        $html .= '<div class="wpm2aws-inputs-row">';

        $html .= '<div class="wpm2aws-inputs-row-header">';
        $html .= '<h2>Step 5. Launch a Clone of this site on AWS</h2>';
        $html .= '</div>';

        $html .= '<div class="wpm2aws-inputs-row-body">';
        if ( false !==  get_option('wpm2aws_valid_licence_type') &&
                'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
        ) {

        }
        else{
            $html .= '<p style="text-align:center">This software launches instances in 2GB ($10 p/m) by default.<br>If you wish to continue using this size instance you do not need to carry out the tasks in Step 13 on the manual. Increasing the instance size is an option but not a requirement.</p>';
        }
        $html .= self::makeRunMigrateButton();
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private static function makeSectionFiveManagedMain($verifiedUser, $managedUser)
    {
        $html = '';

        if (
            false !== get_option('wpm2aws-aws-lightsail-name') &&
            '' !== get_option('wpm2aws-aws-lightsail-name') &&
            false !== get_option('wpm2aws-aws-lightsail-region') &&
            '' !== get_option('wpm2aws-aws-lightsail-region')
        ) {
            $html .= self::makeSectionFiveMain();
        }

        $html .= self::makeLightsailSetupSection($verifiedUser, $managedUser);

        return $html;
    }


    /**
     * Prints the Input Section related
     * to AWS Region Select Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeAwsRegionSection($verified, $managed)
    {
        if ($verified === true) {
            if ($managed === true) {
                $buttonLabel = 'Update AWS Region';
                $SectionLabel = 'AWS Region';
            // echo '<div class="wpm2aws-inputs-item">';
                // echo '<h3>AWS Region: EU - Ireland (eu-west-1)';
                // echo '</div>';
            } else {
                $buttonLabel = 'Select AWS Region';
                $SectionLabel = 'AWS Region';
                // Select AWS Region
                // $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('select-region');
                // self::generateInputSection(
                //     'Select AWS Region',
                //     "wpm2aws_aws_region",
                //     $formElements,
                //     "select-aws-region",
                //     'Select AWS Region'
                // );
            }

            // Select AWS Region
            $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('select-region');
            self::generateInputSection(
                $SectionLabel,
                "wpm2aws_aws_region",
                $formElements,
                "select-aws-region",
                $buttonLabel
            );
        } else {
            echo '<div class="wpm2aws-inputs-item">';
            echo '</div>';
        }
        return;
    }


    /**
     * Prints the Input Section related
     * to S3 Bucket Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeS3SetupSection($verified, $managed)
    {
        echo '<div class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>Temporary Storage Configuration</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body">';

        if ($verified === true) {
            if ($managed === true) {
                echo '<h3>AWS S3 Storage Bucket Created for User:&nbsp';
                esc_attr_e(get_option('wpm2aws-iam-user'));
                echo '</h3>';
            } else {
                // if (false !== get_option('wpm2aws-existingBucketNames') && count(get_option('wpm2aws-existingBucketNames')) > 0) {
                // Use Existing S3 Bucket
                $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('use-s3-bucket');
                $refreshBucketList = '';
                $refreshBucketList .= '<form method="post" ';
                $refreshBucketList .= 'action="' . esc_url(admin_url('admin-post.php')) . '?action=wpm2aws_s3_refresh_bucket_list">';
                $refreshBucketList .= '<input type="hidden" name="action" value="wpm2aws_s3_refresh_bucket_list" />';
                $refreshBucketList .= wp_nonce_field('wpm2aws_s3_refresh_bucket_list');
                $refreshBucketList .= '<p><input type="submit" title="Refresh S3 Bucket List" class="button" value="&#x21bb" /></p>';
                $refreshBucketList .= '</form>';

                self::generateInputSection(
                    "Select S3 Bucket",
                    "wpm2aws_s3_use_bucket",
                    $formElements,
                    'use-s3-bucket',
                    "Selected S3 Bucket",
                    $refreshBucketList
                );

                echo "OR";
                // }

                // exit(print_r(get_option('wpm2aws-existingBucketNames')));
                // Create S3 Bucket
                $formElements = WPM2AWS_MigrationForm::getTemplate()->prop("create-s3-bucket");
                self::generateInputSection(
                    'Create s3 Bucket',
                    "wpm2aws_s3_create_bucket",
                    $formElements,
                    "create-s3-bucket",
                    'Create New S3 Bucket'
                );
            }
        } else {
            echo '<div class="wpm2aws-inputs-item">';
            echo '<h3><em>Validate Credentials to Activate this Section</em></h3>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        return;
    }


    /**
     * Prints the Input Section related
     * to File Export Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeFileExportSection($verified, $managed)
    {
        echo '<div style="display:none;" class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>File Export Setup</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body">';


        if ($verified === true) {
            echo '<div id="wpm2aws-edit-s3-notice-section">';
            echo '<h3>Editing this section is recommended for Advanced Users Only</h3>';
            echo '<span id="wpm2aws-edit-s3-section-notice-button" class="button">Edit this Section</span>';
            echo '</div>';

            echo '<div id="wpm2aws-edit-s3-inputs-section" style="display:none;">';

            // Set Upload Directory Name
            // Set the Additional Info for the user
            if (get_option('wpm2aws-aws-s3-upload-directory-name') === false) {
                // If there is no custom directory name set, use "wp-content"
                $directoryNameExtraInfo = sprintf(
                    esc_html__(
                        'The standard name for the content directory is %s.
                        This is the name of the source directory that will be used
                        to upload your content to the new site.
                        Edit below if required.',
                        'migrate-2-aws'
                    ),
                    '<strong>wp-content</strong>'
                );
            } else {
                // If the user has changed the path
                $directoryNameExtraInfo = esc_html__(
                    'This is the name of the source directory that will be used
                    to upload your content to the new site.
                    Edit below if required.',
                    'migrate-2-aws'
                );
            }

            // If the user has not already set an option
            // Then; Apply the standard location
            if (false === get_option('wpm2aws-aws-s3-upload-directory-name')) {
                wpm2awsAddUpdateOptions('wpm2aws-aws-s3-upload-directory-name', 'wp-content');
                // if (defined('WPM2AWS_TESTING')) {
                //     wpm2awsAddUpdateOptions('wpm2aws-aws-s3-upload-directory-name', 'wp-content/plugins/wp-migrate-2-aws/libraries/db');
                // }
            }

            $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('upload-directory-name');
            self::generateInputSection(
                'Set Upload Directory Name',
                "wpm2aws_upload-directory-name",
                $formElements,
                "set-upload-directory-name",
                'Set Upload Directory Name',
                $directoryNameExtraInfo
            );

            // *************************
            // Set Upload Directory Path
            // *************************

            // Set the Additional Info for the user
            if (self::$uploadFromPath) {
                // If there is a likely path or the user has set the path
                $directoryPathExtraInfo = sprintf(
                    esc_html__(
                        'We have detected that the path to your %s directory is %s%s%s.
                        This is the path to the source that will be used
                        to upload your content to the new site.
                        Edit below if required.',
                        'migrate-2-aws'
                    ),
                    '<strong>wp-content</strong>',
                    '<strong>',
                    ((get_option('wpm2aws-aws-s3-upload-directory-path') === false ? self::$uploadFromPath : get_option('wpm2aws-aws-s3-upload-directory-path'))),
                    '</strong>'
                );
            } else {
                // If a likely path could not be determined
                // and the user has not set the path
                $directoryPathExtraInfo = sprintf(
                    esc_html__(
                        'We cannot detected the path to your %s directory.
                        Please add the path to your % directory below.
                        This is the path to the source that will be used
                        to upload your content to the new site.',
                        'migrate-2-aws'
                    ),
                    '<strong>wp-content</strong>',
                    '<strong>wp-content</strong>'
                );
            }

            // If there is a path
            // And user has not already set an option
            // Then; Add the likely path as the option
            if (self::$uploadFromPath && false === get_option('wpm2aws-aws-s3-upload-directory-path')) {
                wpm2awsAddUpdateOptions('wpm2aws-aws-s3-upload-directory-path', self::$uploadFromPath);
            }


            $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('upload-directory-path');
            self::generateInputSection(
                'Set Upload Directory Path',
                "wpm2aws_upload-directory-path",
                $formElements,
                "set-upload-directory-path",
                'Set Upload Directory Path',
                $directoryPathExtraInfo
            );
            echo '</div>';
        } else {
            echo '<div class="wpm2aws-inputs-item">';
            echo '<h3><em>Validate Credentials to Activate this Section</em></h3>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        return;
    }

    /**
     * Prints the Input Section related
     * to AWS Instance Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeLightsailSetupSection($verified, $managed)
    {
        echo '<div class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>Step 5.1 AWS Instance Setup</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body">';

        if ($verified === true) {
            if ($managed === true) {
                echo '<h3>An cloned AWS Instance of this site will be created when the process is completed</h3>';
            } else {
                // Set AWS Instance Name
                $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('lightsail-instance-name');
                self::generateInputSection(
                    'Set AWS Instance Name',
                    "wpm2aws_lightsail-name",
                    $formElements,
                    "set-lightsail-instance-name",
                    'Save AWS Name'
                );

                // Set AWS Region
                $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('lightsail-instance-region');
                self::generateInputSection(
                    'Select AWS Region',
                    "wpm2aws_lightsail-region",
                    $formElements,
                    "set-lightsail-instance-region",
                    'Save AWS Region'
                );
            }
        } else {
            echo '<div class="wpm2aws-inputs-item">';
            echo '<h3><em>Validate Credentials to Activate this Section</em></h3>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        return;
    }


    /**
     * Prints the Input Section related
     * to Domain Naming Settings
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeDomainSetupSection($verified, $managed)
    {
        echo '<div class="wpm2aws-inputs-row">';
        echo '<div class="wpm2aws-inputs-row-header">';
        echo '<h2>Domain Setup</h2>';
        echo '</div>';

        echo '<div class="wpm2aws-inputs-row-body">';

        if ($verified === true) {
            // if ($managed === true) {
            //     echo '<h3>AWS S3 Storage Bucket Created for User:&nbsp';
            //     esc_attr_e(get_option('wpm2aws-iam-user'));
            //     echo '</h3>';
            // } else {
            // Edit Domain Name
            $formElements = WPM2AWS_MigrationForm::getTemplate()->prop('set-domain-name');
            self::generateInputSection(
                'Edit Domain Name',
                "wpm2aws_domainName",
                $formElements,
                "set-domain-name",
                'Update Domain Name',
                sprintf(
                    esc_html__(
                        'We have detected that your current Domain is %s%s%s. This is the domain that your AWS Instance will be launched. Edit below if required.',
                        'migrate-2-aws'
                    ),
                    '<strong>',
                    ((get_option('wpm2aws-aws-lightsail-domain-name') === false ? $_SERVER['SERVER_NAME'] : get_option('wpm2aws-aws-lightsail-domain-name'))),
                    '</strong>'
                )
            );
        // }
        } else {
            echo '<div class="wpm2aws-inputs-item">';
            echo '<h3><em>Validate Credentials to Activate this Section</em></h3>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        return;
    }



    /**
     * Prints the User Input Sections
     *
     * @return void
     */
    private static function makeInputsSection()
    {
        // Check if IAM User is Verified
        $verifiedUser = false;
        if (false !== get_option('wpm2aws-iamid') && false !== get_option('wpm2aws-iampw') && false !== get_option('wpm2aws-iam-user')) {
            $verifiedUser = true;
        }

        // Check if Running Self Option
        $managedUser = true;
        if (false !== get_option('wpm2aws-customer-type') && get_option('wpm2aws-customer-type') === 'self') {
            $managedUser = false;
        }

        self::runFileSystemZipStatusUpdateCheck();
        self::runFileSystemUploadStatusUpdateCheck();

        $currentActiveStep = get_option('wpm2aws_current_active_step');
        $userType = get_option('wpm2aws-customer-type');

        if (
            (
                false === $currentActiveStep ||
                '' === $currentActiveStep ||
                1 === $currentActiveStep
            )
            &&
            (
                false !== $userType &&
                'self' === $userType &&
                false !== get_option('wpm2aws-aws-s3-bucket-name') &&
                '' !== get_option('wpm2aws-aws-s3-bucket-name')
            )
        ) {
            wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 2);
        }
        ?>

        <div id="wpm2aws-edit-inputs-section" class="wpm2aws-admin-section-container">
            <div id="wpm2aws-inputs-spacer"><h3>Steps</h3></div>

            <?php
            /* STEP 1 - AWS SETUP */
            $displayStep = 'none';
            if (
                false === $currentActiveStep ||
                '' === $currentActiveStep ||
                1 === $currentActiveStep ||
                '1' === $currentActiveStep
            ) {
                $displayStep = 'block';
            }

            echo '<div id="wpm2aws-input-section-aws-settings" style="display:' . $displayStep . ';">';
            self::makeAwsSetupSection($verifiedUser, $managedUser);
            if (
                false !==  $userType &&
                'self' === $userType
            ) {
                self::makeS3SetupSection($verifiedUser, $managedUser);
            }
            echo '</div>';



            /* STEP 2 - PREPARE DATABASE */
            $displayStep = 'none';
            if (
                false !== $currentActiveStep &&
                '' !== $currentActiveStep &&
                (
                    2 === $currentActiveStep ||
                    '2' === $currentActiveStep
                )
            ) {
                $displayStep = 'block';
            }

            /* Run the DB Check */
            setDbSizeOption(); // Admin Functions

            echo '<div id="wpm2aws-input-section-prepare-database" style="display:' . $displayStep . ';">';
            echo self::makeSectionTwoMain();
            echo '</div>';


            /* STEP 3 - PREPARE FILE SYSTEM */
            $displayStep = 'none';
            if (
                false !== $currentActiveStep &&
                '' !== $currentActiveStep &&
                (
                    3 === $currentActiveStep ||
                    '3' === $currentActiveStep
                )
            ) {
                $displayStep = 'block';
            }
            echo '<div id="wpm2aws-input-section-prepare-filesystem" style="display:' . $displayStep . ';">';
            echo self::makeSectionThreeMain();
            echo '</div>';


            /* STEP 4 - UPLOAD FILE SYSTEM */
            $displayStep = 'none';
            if (
                false !== $currentActiveStep &&
                '' !== $currentActiveStep &&
                (
                    4 === $currentActiveStep ||
                    '4' === $currentActiveStep
                )
            ) {
                $displayStep = 'block';
            }
            echo '<div id="wpm2aws-input-section-upload-filesystem" style="display:' . $displayStep . ';">';
            echo self::makeSectionFourMain();
            echo '</div>';


            /* STEP 5 - LAUNCH ON AWS */
            $displayStep = 'none';
            if (
                false !== $currentActiveStep &&
                '' !== $currentActiveStep &&
                (
                    5 === $currentActiveStep ||
                    '5' === $currentActiveStep
                )
            ) {
                $displayStep = 'block';
            }
            echo '<div id="wpm2aws-input-section-launch-on-aws" style="display:' . $displayStep . ';">';
            if (
                false !==  $userType &&
                'self' === $userType
            ) {
                echo self::makeSectionFiveManagedMain($verifiedUser, $managedUser);
            } else {
                echo self::makeSectionFiveMain();
            }
            echo '</div>';
            ?>


            <?php // self::makeAwsSetupSection($verifiedUser, $managedUser); ?>

            <?php // self::makeS3SetupSection($verifiedUser, $managedUser); ?>

            <?php
            /* AB removed for Sep 1st 2020 release #109 */
            self::makeFileExportSection($verifiedUser, $managedUser);
            ?>

            <?php // self::makeLightsailSetupSection($verifiedUser, $managedUser); ?>

            <?php // self::makeDomainSetupSection($verifiedUser, $managedUser);?>


            <div>
                <hr>
                <h3>Reports</h3>
                <?php echo self::makeZippedUploadReportSection(); ?>

                <?php echo self::makeFsZipReportSection(); ?>
            </div>
        </div>

        <?php
        return;
    }


    /**
     * Prints the Output Section related
     * to Zipped Upload Errors
     *
     * @param boolean $verified
     * @param boolean $managed
     * @return void
     */
    private static function makeZippedUploadReportSection()
    {
        $html = '';

        if (false !== get_option('wpm2aws_zipped_fs_upload_failures') && '' !== get_option('wpm2aws_zipped_fs_upload_failures')) {
            $html .= '<div class="wpm2aws-inputs-row" id="wpm2aws-zipped-fs-upload-error-report">';
            $html .= '<div class="wpm2aws-inputs-row-header">';
            $html .= '<h2>Clone to AWS Error Report</h2>';
            $html .= '</div>';

            $html .= '<div class="wpm2aws-inputs-row-body">';

            $html .= '<h3>The following Errors were identified during the Upload Process to S3</h3>';

            $content = get_option('wpm2aws_zipped_fs_upload_failures');
            $html .= $content;

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }


    /**
     * Prints the Output Section related
     * to Download Errors or Individual File Download Links
     *
     * @return string
     */
    private static function makeFsZipReportSection()
    {
        $html = '';

        if (false !== get_option('wpm2aws_fszipper_failures') && '' !== get_option('wpm2aws_fszipper_failures')) {
            $html .= '<div class="wpm2aws-inputs-row" id="wpm2aws-file-system-fszipper-error-report">';
            $html .= '<div class="wpm2aws-inputs-row-header">';
            $html .= '<h2>File-System Zip Error Report</h2>';
            $html .= '</div>';

            $html .= '<div class="wpm2aws-inputs-row-body">';

            $html .= '<h3>The following Errors were identified during the Directory Zip Process</h3>';

            $content = get_option('wpm2aws_fszipper_failures');
            $html .= $content;

            $html .= '</div>';
            $html .= '</div>';
        }

        if (false !== get_option('wpm2aws_fszipper_complete') && 'success' === get_option('wpm2aws_fszipper_complete')) {
            $html .= '<div class="wpm2aws-inputs-row" id="wpm2aws-file-system-fszipper-file-links-report">';
            $html .= '<div class="wpm2aws-inputs-row-header">';
            $html .= '<h2>Download Individual Zip Files</h2>';
            $html .= '</div>';

            $html .= '<div class="wpm2aws-inputs-row-body">';

            $html .= '<h3>The following Zip Files are Available for Download</h3>';

            $content = '';

            $zipList = WPM2AWS_ZIP_LOG_FILE_PATH;

            $downloads = fopen($zipList, 'r');
            if ($downloads) {
                while (!feof($downloads)) {
                    $line = fgets($downloads);
                    $line = str_replace(array("\r", "\n"), '', $line);
                    $line = str_replace("\\\\", "\\", $line);
                    $line = str_replace("//", "/", $line);
                    $line = str_replace('\\', DIRECTORY_SEPARATOR, $line);
                    if (!empty($line) && '' !== $line) {
                        $line = get_option('wpm2aws-aws-s3-upload-directory-path')  .  DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . str_replace('/', DIRECTORY_SEPARATOR, $line);
                    }


                    if (!empty($line) && file_exists($line)) {
                        $parentDirStart = strpos($line, get_option('wpm2aws-aws-s3-upload-directory-name'));
                        $unZipFromPath = substr($line, $parentDirStart);

                        // Format Link
                        // Remove path & .zip extension
                        $linkTitleStart = strpos($unZipFromPath, WPM2AWS_ZIP_EXPORT_PATH) + strlen(WPM2AWS_ZIP_EXPORT_PATH);
                        $linkTitle = substr($unZipFromPath, $linkTitleStart, -4);
                        if (!empty($unZipFromPath) && '' !== $unZipFromPath) {
                            $content .= '<p><a href="' . get_home_url() . '/' . $unZipFromPath . '" target="_blank">' . $linkTitle . '</a></p>';
                        }
                    }
                }
            } else {
                $content .= '<p>There are no files available for download</p>';
            }
            fclose($downloads);


            // Add Link to Log File
            $parentDirStart = strpos(WPM2AWS_ZIP_LOG_FILE_PATH, get_option('wpm2aws-aws-s3-upload-directory-name'));
            $logFilePath = substr(WPM2AWS_ZIP_LOG_FILE_PATH, $parentDirStart);
            $content .= '<p><a href="' . get_home_url() . '/' . $logFilePath . '" target="_blank">Zip Log File</a></p>';

            $html .= $content;

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Prints the Summary Section (sidebar)
     *
     * @return void
     */
    private static function makeSummarySection()
    {
        ?>
        <div id="wpm2aws-summary-section" class="wpm2aws-admin-section-container">

            <?php echo self::makeRestartButton(); ?>

            <br>

            <?php echo self::makeAwsSetupSideSection(); ?>

            <br>

            <?php echo self::makeDataBaseDownloadSection(); ?>

            <br>

            <?php echo self::makePrepareFileSystemSection(); ?>

            <br>

            <?php echo self::makeZippedFileSystemUploadSection(); ?>

            <!-- <br> -->

            <?php // echo self::makeFileSystemUploadSection();?>

            <br>


            <?php echo self::makeRunFullMigrationSection(); ?>

            <h3>Current Settings</h3>
            <div id="wpm2aws-iam-credentials-container" class="wpm2aws-admin-summary-subsection-container">
                <h4 style="margin: 0.5em 0;">IAM Credentials</h4>
                <p style="margin: 0.5em 0;word-break: break-all;"><?php esc_attr_e('AWS IAM Key', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-iamid')); ?></p>
                <p style="margin: 0.5em 0;word-break: break-all;"><?php esc_attr_e('AWS IAM Password', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-iampw')); ?></p>
                <p style="margin: 0.5em 0;word-break: break-all;"><?php esc_attr_e('AWS IAM User Name', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-iam-user')); ?></p>
                <!-- <p><button id="wpm2aws-edit-credentials" class="wpm2aws-edit-inputs-button" data="iam-credentials"><?php // echo __('Edit Credentials', 'migrate-2-aws');?></button></p> -->
            </div>
            <br>
            <div id="wpm2aws-aws-region-container" class="wpm2aws-admin-summary-subsection-container">
                <h4 style="margin: 0.5em 0;">AWS Region Details</h4>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('AWS Region', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-aws-region')); ?></p>
                <!-- <p><button id="wpm2aws-edit-credentials" class="wpm2aws-edit-inputs-button" data="iam-credentials"><?php // echo __('Edit Credentials', 'migrate-2-aws');?></button></p> -->
            </div>
            <br>
            <div id="wpm2aws-s3-bucket-container" class="wpm2aws-admin-summary-subsection-container">
                <h4 style="margin: 0.5em 0;">AWS S3 Bucket Details</h4>
                <?php
                $bucketName = get_option('wpm2aws-aws-s3-bucket-name');

                echo "Default: " . get_option('wpm2aws-aws-s3-default-bucket-name'); ?>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('S3 Bucket Name', 'migrate-2-aws'); ?>: <?php esc_attr_e($bucketName); ?></p>
                <?php
                if (substr(get_option('wpm2aws-aws-s3-bucket-name'), 0, 8) === 'wpm2aws-') {
                    $bucketType = esc_attr('New Bucket Created', 'migrate-2-aws');
                } else {
                    $bucketType = esc_attr('Existing Bucket Assigned', 'migrate-2-aws');
                } ?>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('S3 Bucket Type', 'migrate-2-aws'); ?>: <?php echo $bucketType; ?></p>
                <!-- <p><button id="wpm2aws-edit-credentials" class="wpm2aws-edit-inputs-button" data="iam-credentials"><?php // echo __('Edit Credentials', 'migrate-2-aws');?></button></p> -->
            </div>
            <br>
            <div id="wpm2aws-upload-container" class="wpm2aws-admin-summary-subsection-container">
                <h4 style="margin: 0.5em 0;">File/Directory Upload Settings</h4>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('File / Directory Name', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-aws-s3-upload-directory-name')); ?></p>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('File / Directory Path', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-aws-s3-upload-directory-path')); ?></p>
                <!-- <p><button id="wpm2aws-edit-credentials" class="wpm2aws-edit-inputs-button" data="iam-credentials"><?php // echo __('Edit Credentials', 'migrate-2-aws');?></button></p> -->
            </div>
            <br>
            <div id="wpm2aws-lightsail-container" class="wpm2aws-admin-summary-subsection-container">
                <h4 style="margin: 0.5em 0;">AWS Settings</h4>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('AWS Instance Name', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-aws-lightsail-name')); ?></p>
                <p style="margin: 0.5em 0;"><?php esc_attr_e('AWS Instance Region', 'migrate-2-aws'); ?>: <?php esc_attr_e(get_option('wpm2aws-aws-lightsail-region')); ?></p>
                <!-- <p style="margin: 0.5em 0;"><?php // esc_attr_e('AWS Instance Domain Name', 'migrate-2-aws');?>: <?php // echo $domain = ((get_option('wpm2aws-aws-lightsail-domain-name') === false ? $_SERVER['SERVER_NAME'] : esc_attr(get_option('wpm2aws-aws-lightsail-domain-name'))));?></p> -->
                <!-- <p><button id="wpm2aws-edit-credentials" class="wpm2aws-edit-inputs-button" data="iam-credentials"><?php // echo __('Edit Credentials', 'migrate-2-aws');?></button></p> -->
            </div>
            <br>
            <?php

            echo self::makeResetButton();

            self::makeRunMigrateButton();

            ?>

        </div>

        <?php
    }

    private static function allSettingsSet()
    {
        if (defined('WPM2AWS_TESTING')) {
            return true;
        }

        $expectedSettings = array(
            'wpm2aws-iamid',
            'wpm2aws-iampw',
            'wpm2aws-iam-user',
            'wpm2aws-aws-region',
            'wpm2aws-aws-s3-bucket-name',
            'wpm2aws-aws-s3-upload-directory-name',
            'wpm2aws-aws-s3-upload-directory-path',
            'wpm2aws-aws-lightsail-name',
            'wpm2aws-aws-lightsail-region',
            'wpm2aws_download_db_complete',
            'wpm2aws_fszipper_complete',
            'wpm2aws_zipped_fs_upload_complete',
            // 'wpm2aws-aws-lightsail-domain-name'
        );

        foreach ($expectedSettings as $optionName) {
            if (!self::checkOptionSet($optionName)) {
                return false;
            }
        }

        if (get_option('wpm2aws_download_db_complete') !== 'success') {
            return false;
        }
        if (get_option('wpm2aws_fszipper_complete') !== 'success') {
            return false;
        }
        if (get_option('wpm2aws_zipped_fs_upload_complete') !== 'success') {
            return false;
        }

        return true;
    }

    private static function adminDownloadSettingsSet()
    {
        if (!defined('WPM2AWS_ADMIN_DL_TESTING')) {
            return false;
        }

        return false;

        if (defined('WPM2AWS_TESTING')) {
            return true;
        }

        $expectedSettings = array(
            'wpm2aws-iamid',
            'wpm2aws-iampw',
            'wpm2aws-iam-user',
            'wpm2aws-aws-region',
            'wpm2aws-aws-s3-bucket-name',
            'wpm2aws-aws-s3-upload-directory-name',
            'wpm2aws-aws-s3-upload-directory-path',
            'wpm2aws-aws-lightsail-name',
            'wpm2aws-aws-lightsail-region',
            'wpm2aws_download_db_complete',
            'wpm2aws_download_complete',
            'wpm2aws_admin_upload_complete',
            // 'wpm2aws-aws-lightsail-domain-name'
        );

        foreach ($expectedSettings as $optionName) {
            if (!self::checkOptionSet($optionName)) {
                return false;
            }
        }

        if (get_option('wpm2aws_download_db_complete') !== 'success') {
            return false;
        }
        if (get_option('wpm2aws_download_complete') !== 'success') {
            return false;
        }
        if (get_option('wpm2aws_admin_upload_complete') !== 'success') {
            return false;
        }

        return true;
    }

    private static function fileSystemUploadSettingsSet()
    {
        $expectedSettings = array(
            'wpm2aws-iamid',
            'wpm2aws-iampw',
            'wpm2aws-iam-user',
            'wpm2aws-aws-region',
            'wpm2aws-aws-s3-bucket-name',
            'wpm2aws-aws-s3-upload-directory-name',
            'wpm2aws-aws-s3-upload-directory-path',
            'wpm2aws_download_db_complete'
            // 'wpm2aws-aws-lightsail-name',
            // 'wpm2aws-aws-lightsail-region',
            // 'wpm2aws-aws-lightsail-domain-name'
        );

        foreach ($expectedSettings as $optionName) {
            if (!self::checkOptionSet($optionName)) {
                return false;
            }
        }
        if (get_option('wpm2aws_download_db_complete') !== 'success') {
            return false;
        }
        return true;
    }

    // private static function allSettingsSet()
    // {
    //     $expectedSettings = array(
    //         'wpm2aws-iamid',
    //         'wpm2aws-iampw',
    //         'wpm2aws-iam-user',
    //         'wpm2aws-aws-region',
    //         'wpm2aws-aws-s3-bucket-name',
    //         'wpm2aws-aws-s3-upload-directory-name',
    //         'wpm2aws-aws-s3-upload-directory-path',
    //         'wpm2aws-aws-lightsail-name',
    //         'wpm2aws-aws-lightsail-region',
    //         // 'wpm2aws-aws-lightsail-domain-name'
    //     );

    //     foreach ($expectedSettings as $optionName) {
    //         if (!self::checkOptionSet($optionName)) {
    //             return false;
    //         }
    //     }
    //     return true;
    // }

    private static function checkOptionSet($option)
    {
        return $optionVal = get_option($option);
    }

    // Simple Progress bar
    private static function makeProgressBar($total, $complete, $inMainSection = false)
    {
        $html = '';
        if ($total === 0) {
            $percentageComplete = 0;
        } else {
            $percentageComplete = (int)round(($complete / $total) * 100);
        }

        $progressBarId = "wpm2aws-progress-bar";
        if ($inMainSection) {
            $progressBarId .= '-main';
        }
        $html .= '<div class="wpm2aws-progress-bar-bg">';

        $html .= '<div id="' . $progressBarId . '" class="wpm2aws-progress-bar-fg" data-progress="' . $percentageComplete . '" style="width:' . $percentageComplete . '%"> ' . $percentageComplete . '%</div>';
        $html .= '</div>';

        return $html;
    }

    // File System Section

    private static function makePrepareFileSystemSection($inMainSection = false)
    {
        $zipStarted = false;
        $uploadStarted = false;
        $zipComplete = false;
        $uploadComplete = false;

        $processStarted = false;
        $processComplete = false;

        $containerClass = $inMainSection ? '' : 'wpm2aws-admin-summary-subsection-container';

        $html = '';

        $html .= '<div id="wpm2aws-fszip-results-container" class="' . $containerClass . '">';

        if (!$inMainSection) {
            $html .= '<div>';
            $html .= '<h4 style="margin: 0.5em 0;display:inline-block;">Step 3: Prepare File System Status</h4>';
            $html .= self::makeNavigateSectionButton('prepare-filesystem');
            $html .= '<div class="clear-both"></div>';
            $html .= '</div>';
        }


        // Start Process
        if (false !== get_option('wpm2aws_fszipper_started') && get_option('wpm2aws_fszipper_started') !== '') {
            $zipStarted = true;
        }
        if (false !== get_option('wpm2aws_zipped_fs_upload_started') && get_option('wpm2aws_zipped_fs_upload_started') !== '') {
            $uploadStarted = true;
        }

        if (true === $zipStarted || true === $uploadStarted) {
            $processStarted = true;
        }


        // End Process
        if (false !== get_option('wpm2aws_fszipper_complete') && get_option('wpm2aws_fszipper_complete') !== '') {
            $zipComplete = true;
        }
        if (false !== get_option('wpm2aws_zipped_fs_upload_complete') && get_option('wpm2aws_zipped_fs_upload_complete') !== '') {
            $uploadComplete = true;
        }

        if (true === $zipComplete || true === $uploadComplete) {
            $processComplete = true;
        }


        // If both Upload AND Zipping
        // have NOT yet started
        if (false === $processStarted) {
            // Check if all options are set
            $fileSystemPath = '';
            $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-path'));
            if ($fileSystemPath !== '') {
                $pathSeparator = '/';
                if (strpos($fileSystemPath, '\\') !== false) {
                    $pathSeparator = '\\';
                }
                $fileSystemPath .= $pathSeparator;
                $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-name'));
            }

            // if there is a file-system path
            if ($fileSystemPath !== '') {
                if (is_dir($fileSystemPath)) {
                    $zipButton = self::makeRunZipFileSystemButton();
                    $html .= '<p class="notice notice-warning" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Prepare File System Not Commenced &nbsp;';
                    $html .= '</p>';
                    // $html .= '<span style="display:inline-block">';
                    $html .= '<span>';
                    $html .= $zipButton;
                    $html .= "</span>";
                } else {
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Error! Invalid File System Path - ' . $fileSystemPath;
                    $html .= '</p>';
                }
            } else {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }


            if (!self::checkOptionSet('wpm2aws_download_db_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare Database" first!';
                $html .= '</p>';
            }
        } // Process Has Not Yet Begun


        // Once process Has Started
        if (true === $processStarted) {
            if (true === $zipStarted && true !== $uploadStarted) {
                if (false !== get_option('wpm2aws_fszipper_counter') && is_array(get_option('wpm2aws_fszipper_counter'))) {
                    $showCounter = true;
                    $progress = get_option('wpm2aws_fszipper_counter');
                }

                $total = 0;
                $complete = 0;
                $total = (isset($progress['total']) ? $progress['total'] : 0);
                $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
                $html .= self::makeProgressBar($total, $complete, $inMainSection);
            }



            // If the process is underway & not yet complete
            // Show the Notice & hidden restart button
            if (false === $processComplete) {
                // If the "Zipping" Process Has Started
                // Show the link for restart
                if (true === $zipStarted) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">Prepare File System In Progress...</p>';
                    $html .= self::makeRestartFileUploadButton('fs-zip', 'fszipper');
                    $html .= '</div>';
                }
            }
        }


        // Once Either Process Has
        // Completed
        if (true === $processComplete) {
            // If Zipping Process has Completed
            if (true === $zipComplete) {
                if ('error' === get_option('wpm2aws_fszipper_complete')) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Upload Error - Please Re-Run';
                    $html .= '</p>';
                    $html .= self::makeRestartFileUploadButton('fs-zip', 'fszipper');
                    $html .= '</div>';
                }

                if ('success' === get_option('wpm2aws_fszipper_complete')) {
                    if (false === get_option('wpm2aws_fszipper_failures') || '' === get_option('wpm2aws_fszipper_failures')) {
                        $noticeClass = 'success';
                        $zipErrors = false;
                    } else {
                        $noticeClass = 'warning';
                        $zipErrors = true;
                    }

                    $noticeMsg = 'Prepare File System Successful';


                    // Download Buttons for Super User
                    // URL: "{base_plugin_url}&wpm2aws-super-admin=download"
                    if (!empty($_GET['wpm2aws-super-admin']) && 'download' === $_GET['wpm2aws-super-admin']) {
                        $noticeMsg .= '<div class="wpm2aws-row-padding-bottom">&nbsp;</div>';
                        $downloadLink = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;
                        $downloadLinkStart = strpos($downloadLink, get_option('wpm2aws-aws-s3-upload-directory-name'));
                        $downloadLink = substr($downloadLink, $downloadLinkStart) . '.zip';

                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-primary" href="' . get_home_url() . '/' . $downloadLink . '" target="_blank">Download Full Directory</a></span>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-file-system-fszipper-file-links-report">Download Individual Files</a></span>';
                        $noticeMsg .= '<br>';
                    }


                    // Add Functionality to upload all to S3
                    // $noticeMsg .= self::makeRunDownloadedDataUploadButton();

                    if (true === $zipErrors) {
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><strong>WARNING:</strong> Some errors occurred during the Zip process</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-file-system-fszipper-error-report">Review Warnings</a></span>';
                        $noticeMsg .= '<br>';
                    }

                    if (false !== $zipComplete && 'success' === get_option('wpm2aws_fszipper_complete')) {
                        $html .= '<p id="wpm2aws-fszip-success-notice" class="notice notice-' . $noticeClass . '" style="margin: 0.5em 0;padding:10px;">';
                        $html .= $noticeMsg;
                        $html .= '</p>';
                    }
                }
            }
        }

        if (false === $uploadStarted && false === $zipStarted) {
            // show start buttons
        }


        if (true === $uploadStarted) {
            // hide download buttons
        }

        if (true === $uploadComplete) {
        }

        if (true === $zipComplete) {
        }



        $html .= '</div>';

        return $html;
    }


    private static function makeZippedFileSystemUploadSection($inMainSection = false)
    {
        $dbDownloadStarted = false;
        $zipDownloadStarted = false;
        $zipUploadStarted = false;

        $dbDownloadComplete = false;
        $zipDownloadComplete = false;
        $zipUploadComplete = false;

        $processStarted = false;
        $processComplete = false;

        $containerClass = $inMainSection ? '' : 'wpm2aws-admin-summary-subsection-container';

        $html = '';

        $html .= '<div id="wpm2aws-zipped-fs-upload-results-container" class="' . $containerClass . '">';

        if (!$inMainSection) {
            $html .= '<div>';
            $html .= '<h4 style="margin: 0.5em 0;display:inline-block;">Step 4: Clone to AWS Status</h4>';
            $html .= self::makeNavigateSectionButton('upload-filesystem');
            $html .= '<div class="clear-both"></div>';
            $html .= '</div>';
        }

        // Start Process
        if (false !== get_option('wpm2aws_download_db_started') && get_option('wpm2aws_download_db_started') !== '') {
            $dbDownloadStarted = true;
        }

        if (false !== get_option('wpm2aws_fszipper_started') && get_option('wpm2aws_fszipper_started') !== '') {
            $zipDownloadStarted = true;
        }
        if (false !== get_option('wpm2aws_zipped_fs_upload_started') && get_option('wpm2aws_zipped_fs_upload_started') !== '') {
            $zipUploadStarted = true;
        }

        if (true === $zipUploadStarted) {
            $processStarted = true;
        }


        // End Process
        if (false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') !== '') {
            $dbDownloadComplete = true;
        }
        if (false !== get_option('wpm2aws_fszipper_complete') && get_option('wpm2aws_fszipper_complete') !== '') {
            $zipDownloadComplete = true;
        }
        if (false !== get_option('wpm2aws_zipped_fs_upload_complete') && get_option('wpm2aws_zipped_fs_upload_complete') !== '') {
            $zipUploadComplete = true;
        }

        if (true === $zipUploadComplete) {
            $processComplete = true;
        }


        // If Upload
        // has NOT yet started
        if (false === $processStarted) {
            // Check if all options are set
            $fileSystemPath = '';
            $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-path'));
            if ($fileSystemPath !== '') {
                $pathSeparator = '/';
                if (strpos($fileSystemPath, '\\') !== false) {
                    $pathSeparator = '\\';
                }
                $fileSystemPath .= $pathSeparator;
                $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-name'));
            }

            // if there is a file-system path
            if ($fileSystemPath !== '') {
                if (is_dir($fileSystemPath)) {
                    $uploadButton = self::makeRunZippedFileSystemUploadButton();
                    $html .= '<p class="notice notice-warning" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Clone to AWS Not Commenced &nbsp;';
                    $html .= '</p>';
                    $html .= '<span style="display:inline-block">';
                    $html .= $uploadButton;
                    $html .= "</span>";
                } else {
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Error! Invalid File System Path - ' . $fileSystemPath;
                    $html .= '</p>';
                }
            } else {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }


            if (!self::checkOptionSet('wpm2aws_download_db_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare Database" first!';
                $html .= '</p>';
            }
            if (!self::checkOptionSet('wpm2aws_fszipper_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare File System" first!';
                $html .= '</p>';
            }
        } // Process Has Not Yet Begun


        // Once either process Has Started
        if (true === $processStarted) {
            if (true === $zipUploadStarted) {
                if (false !== get_option('wpm2aws_zipped_fs_upload_counter') && is_array(get_option('wpm2aws_zipped_fs_upload_counter'))) {
                    $showCounter = true;
                    $progress = get_option('wpm2aws_zipped_fs_upload_counter');
                }
                $total = 0;
                $complete = 0;
                $total = (isset($progress['total']) ? $progress['total'] : 0);
                $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
                $html .= self::makeProgressBar($total, $complete, $inMainSection);
            }
            // If the process is underway & not yet complete
            // Show the Notice & hidden restart button
            if (false === $processComplete) {
                // If the "Upload" Process Has Started
                // Show the link for restart
                if (true === $zipUploadStarted) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">Clone to AWS In Progress...</p>';
                    $html .= self::makeRestartFileUploadButton('zipped-fs-upload', 'zipped-fs-uploader');
                    $html .= '</div>';
                }
            }
        }


        // Once Either Process Has
        // Completed
        if (true === $processComplete) {
            // If Upload Process Has Complete
            if (true === $zipUploadComplete) {
                if ('error' === get_option('wpm2aws_zipped_fs_upload_complete')) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Upload Error - Please Re-Run';
                    $html .= '</p>';
                    $html .= self::makeRestartFileUploadButton('zipped-fs-upload', 'zipped-fs-uploader');
                    $html .= '</div>';
                }

                if ('success' === get_option('wpm2aws_zipped_fs_upload_complete')) {
                    if (false === get_option('wpm2aws_zipped_fs_upload_failures') || '' === get_option('wpm2aws_zipped_fs_upload_failures')) {
                        $noticeClass = 'success';
                        $noticeMsg = 'Clone to AWS Successful - Ready to Generate Site';
                    } else {
                        $noticeClass = 'warning';
                        $noticeMsg = '';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom">Upload to S3 Complete</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><strong>WARNING:</strong> Some files could not be transferred</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-zipped-fs-upload-error-report">Review Warnings</a></span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom">Ready to Generate Site</span>';
                    }
                    $html .= '<p id="wpm2aws-zipped-fs-upload-success-notice" class="notice notice-' . $noticeClass . '" style="margin: 0.5em 0;padding:10px;">';
                    $html .= $noticeMsg;
                    $html .= '</p>';
                }
            }
        }

        // if (false !== get_option('wpm2aws_upload_started') && get_option('wpm2aws_upload_started') !== '') {
        //     if (false !== get_option('wpm2aws_upload_counter') && is_array(get_option('wpm2aws_upload_counter'))) {
        //         $total = 0;
        //         $complete = 0;
        //         $progress = get_option('wpm2aws_upload_counter');
        //         $total = (isset($progress['total']) ? $progress['total'] : 0);
        //         $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
        //         $html .= self::makeProgressBar($total, $complete);
        //     }
        // }

        if (false === $zipUploadStarted && false === $zipDownloadStarted) {
            // show start buttons
        }


        if (true === $zipUploadStarted) {
            // hide download buttons
        }

        // if (false === $downloadStarted && false === $downloadComplete) {
        // show upload buttons

        // if (false === get_option('wpm2aws_upload_complete') || get_option('wpm2aws_upload_complete') === '') {
        //     if (false === get_option('wpm2aws_upload_started') ||  get_option('wpm2aws_upload_started') === '') {



        // if (false !== get_option('wpm2aws_upload_complete')) {
        if (true === $zipUploadComplete) {
        }
        // }




        if (true === $zipDownloadComplete) {
        }



        $html .= '</div>';

        return $html;
    }


    private static function makeFileSystemUploadSection_redundant()
    {
        $downloadStarted = false;
        $uploadStarted = false;
        $downloadComplete = false;
        $uploadComplete = false;
        $adminUploadStarted = false;
        $adminUploadComplete = false;

        $processStarted = false;
        $processComplete = false;
        $html = '';

        $html .= '<div id="wpm2aws-upload-results-container" class="wpm2aws-admin-summary-subsection-container">';
        $html .= '<h4 style="margin: 0.5em 0;">Step 3: Clone to AWS Status</h4>';


        // Start Process
        if (false !== get_option('wpm2aws_download_started') && get_option('wpm2aws_download_started') !== '') {
            $downloadStarted = true;
        }
        if (false !== get_option('wpm2aws_upload_started') && get_option('wpm2aws_upload_started') !== '') {
            $uploadStarted = true;
        }
        if (false !== get_option('wpm2aws_admin_upload_started') && get_option('wpm2aws_admin_upload_started') !== '') {
            $adminUploadStarted = true;
        }

        if (true === $downloadStarted || true === $uploadStarted || true === $adminUploadStarted) {
            $processStarted = true;
        }


        // End Process
        if (false !== get_option('wpm2aws_download_complete') && get_option('wpm2aws_download_complete') !== '') {
            $downloadComplete = true;
        }
        if (false !== get_option('wpm2aws_upload_complete') && get_option('wpm2aws_upload_complete') !== '') {
            $uploadComplete = true;
        }
        if (false !== get_option('wpm2aws_admin_upload_complete') && get_option('wpm2aws_admin_upload_complete') !== '') {
            $adminUploadComplete = true;
        }

        if (true === $downloadComplete || true === $uploadComplete || true === $adminUploadComplete) {
            $processComplete = true;
        }


        // If both Upload AND Download
        // hav NOT yet started
        if (false === $processStarted) {
            // Check if all options are set
            $fileSystemPath = '';
            $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-path'));
            if ($fileSystemPath !== '') {
                $pathSeparator = '/';
                if (strpos($fileSystemPath, '\\') !== false) {
                    $pathSeparator = '\\';
                }
                $fileSystemPath .= $pathSeparator;
                $fileSystemPath .= esc_attr(get_option('wpm2aws-aws-s3-upload-directory-name'));
            }

            // if there is a file-system path
            if ($fileSystemPath !== '') {
                if (is_dir($fileSystemPath)) {
                    $uploadButton = self::makeRunFileSystemUploadButton();
                    $html .= '<p class="notice notice-warning" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Clone to AWS Not Commenced &nbsp;';
                    $html .= '</p>';
                    $html .= '<span style="display:inline-block">';
                    $html .= $uploadButton;
                    $html .= "</span>";

                    // Download Buttons for Super User
                    // URL: "{base_plugin_url}&wpm2aws-super-admin=download"
                    if (!empty($_GET['wpm2aws-super-admin']) && 'download' === $_GET['wpm2aws-super-admin']) {
                        // Show Download Buttons
                        // Only if Upload has not commenced
                        if (false === $uploadStarted || (true === $uploadStarted && true === $uploadComplete)) {
                            $downloadButton = self::makeRunFileSystemDownloadButton();
                            $html .= '<span style="display:inline-block;float:right;">';
                            $html .= $downloadButton;
                            $html .= "</span>";
                        }
                    }
                } else {
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Error! Invalid File System Path - ' . $fileSystemPath;
                    $html .= '</p>';
                }
            } else {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }


            if (!self::checkOptionSet('wpm2aws_download_db_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare Database" first!';
                $html .= '</p>';
            }
        } // Process Has Not Yet Begun


        // Once either process Has Started
        if (true === $processStarted) {
            if (true === $uploadStarted) {
                if (false !== get_option('wpm2aws_upload_counter') && is_array(get_option('wpm2aws_upload_counter'))) {
                    $showCounter = true;
                    $progress = get_option('wpm2aws_upload_counter');
                }
            }
            if (true === $downloadStarted) {
                if (false !== get_option('wpm2aws_download_counter') && is_array(get_option('wpm2aws_download_counter'))) {
                    $showCounter = true;
                    $progress = get_option('wpm2aws_download_counter');
                }
            }
            if (true === $adminUploadStarted) {
                if (false !== get_option('wpm2aws_admin_upload_counter') && is_array(get_option('wpm2aws_admin_upload_counter'))) {
                    $showCounter = true;
                    $progress = get_option('wpm2aws_admin_upload_counter');
                }
            }

            $total = 0;
            $complete = 0;
            $total = (isset($progress['total']) ? $progress['total'] : 0);
            $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
            $html .= self::makeProgressBar($total, $complete);

            // If the process is underway & not yet complete
            // Show the Notice & hidden restart button
            if (false === $processComplete) {
                // If the "Upload" Process Has Started
                // Show the link for restart
                if (true === $uploadStarted) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">Clone to AWS In Progress...</p>';
                    $html .= self::makeRestartFileUploadButton('upload');
                    $html .= '</div>';
                }

                // If the "Download" Process Has Started
                // Show the link for restart
                if (true === $downloadStarted) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">Prepare File System In Progress...</p>';
                    $html .= self::makeRestartFileUploadButton('download');
                    $html .= '</div>';
                }
            }
        }


        // Once Either Process Has
        // Completed
        if (true === $processComplete) {
            // If Upload Process Has Complete
            if (true === $uploadComplete) {
                if ('error' === get_option('wpm2aws_upload_complete')) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Upload Error - Please Re-Run';
                    $html .= '</p>';
                    $html .= self::makeRestartFileUploadButton();
                    $html .= '</div>';
                }

                if ('success' === get_option('wpm2aws_upload_complete')) {
                    if (false === get_option('wpm2aws_upload_failures') || '' === get_option('wpm2aws_upload_failures')) {
                        $noticeClass = 'success';
                        $noticeMsg = 'Clone to AWS Successful - Ready to Generate Site';
                    } else {
                        $noticeClass = 'warning';
                        $noticeMsg = '';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom">Upload to S3 Complete</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><strong>WARNING:</strong> Some files could not be transferred</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-file-system-upload-error-report">Review Warnings</a></span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom">Ready to Generate Site</span>';
                    }
                    $html .= '<p id="wpm2aws-upload-success-notice" class="notice notice-' . $noticeClass . '" style="margin: 0.5em 0;padding:10px;">';
                    $html .= $noticeMsg;
                    $html .= '</p>';
                }
            }

            // If Download Process has Completed
            if (true === $downloadComplete) {
                if ('error' === get_option('wpm2aws_download_complete')) {
                    $html .= '<div>';
                    $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                    $html .= 'Upload Error - Please Re-Run';
                    $html .= '</p>';
                    $html .= self::makeRestartFileUploadButton();
                    $html .= '</div>';
                }

                if ('success' === get_option('wpm2aws_download_complete')) {
                    if (false === get_option('wpm2aws_download_failures') || '' === get_option('wpm2aws_download_failures')) {
                        $noticeClass = 'success';
                        $zipErrors = false;
                    } else {
                        $noticeClass = 'warning';
                        $zipErrors = true;
                    }

                    $noticeMsg = '<span class="wpm2aws-row-padding-bottom">File System Zip - Complete</span>';
                    $noticeMsg .= '<br>';
                    $downloadLink = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;
                    $downloadLinkStart = strpos($downloadLink, get_option('wpm2aws-aws-s3-upload-directory-name'));
                    $downloadLink = substr($downloadLink, $downloadLinkStart) . '.zip';

                    $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-primary" href="' . get_home_url() . '/' . $downloadLink . '" target="_blank">Download Full Directory</a></span>';
                    $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-file-system-download-file-links-report">Download Individual Files</a></span>';
                    $noticeMsg .= '<br>';

                    // Add Functionality to upload all to S3
                    $noticeMsg .= self::makeRunDownloadedDataUploadButton();

                    if (true === $zipErrors) {
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><strong>WARNING:</strong> Some errors occurred during the Zip process</span>';
                        $noticeMsg .= '<br>';
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom"><a class="button button-secondary" href="#wpm2aws-file-system-download-error-report">Review Warnings</a></span>';
                        $noticeMsg .= '<br>';
                    }

                    if (false !== get_option('wpm2aws_admin_upload_complete') && 'success' === get_option('wpm2aws_admin_upload_complete')) {
                        $noticeMsg .= '<span class="wpm2aws-row-padding-bottom">Zip Files Upload - Complete</span>';
                        $html .= '<p id="wpm2aws-upload-success-notice" class="notice notice-' . $noticeClass . '" style="margin: 0.5em 0;padding:10px;">';
                        $html .= $noticeMsg;
                        $html .= '</p>';
                    }

                    if (false === get_option('wpm2aws_admin_upload_started') || '' === get_option('wpm2aws_admin_upload_started')) {
                        $html .= '<p id="wpm2aws-upload-success-notice" class="notice notice-' . $noticeClass . '" style="margin: 0.5em 0;padding:10px;">';
                        $html .= $noticeMsg;
                        $html .= '</p>';
                    }
                }
            }
        }

        // if (false !== get_option('wpm2aws_upload_started') && get_option('wpm2aws_upload_started') !== '') {
        //     if (false !== get_option('wpm2aws_upload_counter') && is_array(get_option('wpm2aws_upload_counter'))) {
        //         $total = 0;
        //         $complete = 0;
        //         $progress = get_option('wpm2aws_upload_counter');
        //         $total = (isset($progress['total']) ? $progress['total'] : 0);
        //         $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
        //         $html .= self::makeProgressBar($total, $complete);
        //     }
        // }

        if (false === $uploadStarted && false === $downloadStarted) {
            // show start buttons
        }


        if (true === $uploadStarted) {
            // hide download buttons
        }

        // if (false === $downloadStarted && false === $downloadComplete) {
        // show upload buttons

        // if (false === get_option('wpm2aws_upload_complete') || get_option('wpm2aws_upload_complete') === '') {
        //     if (false === get_option('wpm2aws_upload_started') ||  get_option('wpm2aws_upload_started') === '') {



        // if (false !== get_option('wpm2aws_upload_complete')) {
        if (true === $uploadComplete) {
        }
        // }




        if (true === $downloadComplete) {
        }



        $html .= '</div>';

        return $html;
    }

    private static function makeRunFileSystemUploadButton_redundant()
    {
        $formAction = 'wpm2aws-run-fs-upload';
        $label = 'Upload File System';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .=  wp_nonce_field($formAction);
            $html .=  '<input type="submit" class="button button-primary" name="wpm2aws-process-all-submit" value="' . $btnLabel . '" />';
            $html .=  '</form>';
        } else {
            if (!self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }

    private static function makeRunZippedFileSystemUploadButton()
    {
        $formAction = 'wpm2aws-run-zipped-fs-upload';
        $label = 'Clone to AWS';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (
            self::fileSystemUploadSettingsSet() &&
            false !== get_option('wpm2aws_download_db_complete') &&
            'success' === get_option('wpm2aws_download_db_complete') &&
            false !== get_option('wpm2aws_fszipper_complete') &&
            'success' === get_option('wpm2aws_fszipper_complete')
        ) {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .=  wp_nonce_field($formAction);
            $html .=  '<input type="submit" class="button button-primary" name="wpm2aws-process-zipped-fs-uploader-all-submit" value="' . $btnLabel . '" />';
            $html .=  '</form>';
        } else {
            if (!self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }

    private static function makeRunZipFileSystemButton()
    {
        $formAction = 'wpm2aws-run-fs-zip';
        $label = 'Prepare File System';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (
            self::fileSystemUploadSettingsSet() &&
            false !== get_option('wpm2aws_download_db_complete') &&
            get_option('wpm2aws_download_db_complete') === 'success'
        ) {
            // New Functionality to Restrict the Size of DIRS that are included in the Zipping
            if (defined('WPM2AWS_LIMIT_ZIP_DIR_SIZE') && true === WPM2AWS_LIMIT_ZIP_DIR_SIZE) {
                $scannedFs = self::scanFsDirs();

                $excludedDirs = array();
                if (!empty($scannedFs)) {
                    // Identify DIRS bigger than allowed
                    foreach ($scannedFs as $fsIx => $fsVal) {
                        // Never Exclude this Plugin (required for AWS Launch)
                        if ('plugins' . DIRECTORY_SEPARATOR . WPM2AWS_PLUGIN_NAME !== $fsVal['name']) {
                            if (
                                'PB' === $fsVal['size']['unit'] ||
                                'TB' === $fsVal['size']['unit'] ||
                                'GB' === $fsVal['size']['unit']
                            ) {
                                array_push($excludedDirs, $scannedFs[$fsIx]);
                            }

                            if (
                                'MB' === $fsVal['size']['unit'] &&
                                $fsVal['size']['value'] > WPM2AWS_MAX_DIR_SIZE_ZIP
                            ) {
                                array_push($excludedDirs, $scannedFs[$fsIx]);
                            }
                        }
                    }

                    if (!empty($excludedDirs)) {
                        // Update the Option for use During the Zipping Process
                        $excludedDirNames = array_column($excludedDirs, 'name');
                        wpm2awsAddUpdateOptions('wpm2aws_exclude_dirs_from_zip_process', $excludedDirNames);

                        // Output Notice to User
                        $html .= '<div class="wpm2aws-sidebar-row">';

                        $html .= '<div class="wpm2aws-sidebar-row-header">';
                        $html .= '<p>The following Directories have been identified as potentially disrupting your Migration and are excluded.</p>';
                        $html .= '</div>';

                        $html .= '<div class="wpm2aws-sidebar-row-body">';
                        $html .= '<ul>';
                        foreach ($excludedDirs as $edIx => $edVal) {
                            $html .= '<li>' . $edVal['name'] . ' <em>(' . $edVal['size']['value'] . ' ' . $edVal['size']['unit'] . ')</em></li>';
                        }

                        $html .= '</ul>';
                        $html .= '<p><em>Please contact <a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/wp-on-aws-support-portal/">Seahorse Support</a> if required.</em></p>';
                        $html .= '</div>';
                        $html .= '</div>';
                    } else {
                        delete_option('wpm2aws_exclude_dirs_from_zip_process');
                    }
                } else {
                    $html .= '<div class="wpm2aws-sidebar-row">';
                    $html .= '<div class="wpm2aws-sidebar-row-header">';
                    $html .= "<p>No Directories Scanned</p>";
                    $html .= '</div>';
                    $html .= '</div>';
                }
            } else {
                delete_option('wpm2aws_exclude_dirs_from_zip_process');
            }





            // if (!empty($scannedFs)) {
            //     $standardDirs = array('languages', 'upgrade', 'plugins', 'themes', 'uploads');
            //     $nonStandardDirs = array();
            //     $html .= '<div class="wpm2aws-sidebar-row">';

            //     $html .= '<div class="wpm2aws-sidebar-row-header">';
            //     $html .= '<p>The following Directories will be Migrated:</p>';
            //     $html .= '</div>';

            //     $html .= '<div class="wpm2aws-sidebar-row-body">';

            //     $html .= '<ul>';
            //     foreach ($scannedFs as $fsIx => $fsVal) {
            //         if (in_array($fsVal['name'], $standardDirs)) {
            //             $html .= '<li>' . $fsVal['name'] . " - " . $fsVal['size'] . '</li>';
            //         } else {
            //             array_push($nonStandardDirs, $fsVal);
            //         }
            //     }
            //     $html .= '</ul>';
            //     $html .= '</div>';
            //     $html .= '</div>';
            // } else {
            //     $html .= '<div class="wpm2aws-sidebar-row">';
            //     $html .= '<div class="wpm2aws-sidebar-row-header">';
            //     $html .= "<p>No Directories Scanned</p>";
            //     $html .= '</div>';
            //     $html .= '</div>';
            // }

            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';

            // if (!empty($nonStandardDirs)) {
            //     $html .= '<div class="wpm2aws-sidebar-row">';

            //     $html .= '<div class="wpm2aws-sidebar-row-header">';
            //     $html .= '<p>The following Directories have been identified as potentially slowing down your Migration and are excluded by defualt.</p>';
            //     $html .= '</div>';

            //     $html .= '<div class="wpm2aws-sidebar-row-body">';
            //     $html .= '<fieldset>';
            //     $html .= '<legend>Select to be included</legend>';
            //     foreach ($nonStandardDirs as $nsdIx => $nsdVal) {
            //         $html .= '<div>';
            //         $html .= '<input type="checkbox" id="wpm2aws_non_standard_dir_' . $nsdVal['name'] . '" name="wpm2aws_non_standard_dir_' . $nsdVal['name'] . '" value="wpm2aws_non_standard_dir_' . $nsdVal['name'] . '">';
            //         $html .= '<label for="wpm2aws_non_standard_dir_' . $nsdVal['name'] . '">' . $nsdVal['name'] . ' <em>(' . $nsdVal['size'] . ')</em></label>';
            //         $html .= '</div>';
            //     }
            //     $html .= '</fieldset>';
            //     $html .= '</div>';
            //     $html .= '</div>';
            // }

            $html .=  wp_nonce_field($formAction);
            $html .=  '<input type="submit" class="button button-primary" name="wpm2aws-process-fszipper-all-submit" value="' . $btnLabel . '" />';
            $html .=  '</form>';
        } else {
            if (!self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }


    private static function makeRunDownloadedDataUploadButton()
    {
        $formAction = 'wpm2aws-run-fs-admin-upload';
        $label = 'Upload Downloaded Data';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .=  wp_nonce_field($formAction);
            $html .=  '<input type="submit" class="button button-primary" name="wpm2aws-process-admin-uploader-all-submit" value="' . $btnLabel . '" />';
            $html .=  '</form>';
        } else {
            if (!self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }


    private static function makeRunFileSystemDownloadButton()
    {
        $formAction = 'wpm2aws-run-fs-download';
        $label = 'Download File System';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .=  wp_nonce_field($formAction);
            $html .=  '<input type="submit" class="button button-secondary" name="wpm2aws-process-download-all-submit" value="' . $btnLabel . '" />';
            $html .=  '</form>';
        } else {
            if (!self::fileSystemUploadSettingsSet() && false !== get_option('wpm2aws_download_db_complete') && get_option('wpm2aws_download_db_complete') === 'success') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }

    /**
     * Html for 'Reset All' Button
     * In user Interface
     *
     * @return string
     */
    private static function makeResetButton()
    {
        $formAction = 'wpm2aws-reset-all-settings';
        $label = 'Reset All';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));

        $html = '';
        $html .= '<form id="wpm2aws-reset-form" class="wpm2aws-align-right" method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
        $html .=  wp_nonce_field($formAction);
        $html .=  '<input type="submit" class="button wpm2aws-btn-danger" name="wpm2aws-reset-all-settings" value="' . $btnLabel . '" />';
        $html .=  '</form>';

        return $html;
    }

    /**
     * Html for 'Restart' Button
     * In user Interface
     *
     * @return string
     */
    private static function makeRestartButton()
    {
        $formAction = 'wpm2aws-restart-process';
        $label = 'Restart';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));

        $html = '';
        $html .= '<form id="wpm2aws-restart-form" class="wpm2aws-align-right" method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
        $html .=  wp_nonce_field($formAction);
        $html .=  '<input type="submit" class="button wpm2aws-btn-warning" name="wpm2aws-restart-process" value="' . $btnLabel . '" />';
        $html .=  '</form>';

        return $html;
    }

    /**
     * Html for 'Re-start File Upload' Button
     * In user Interface
     *
     * NOTE: DO NOT DISPLAY AT SAME TIME AS MAIN BUTTON
     *
     * @return void
     */
    private static function makeRestartFileUploadButton($action, $option)
    {
        $formAction = 'wpm2aws-run-' . $action;
        $label = 're-start';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        $inputRef = 'wpm2aws-process-';
        $inputRef .= $option;
        $inputRef .= '-all-restart';

        $html .= '<div><span id="wpm2aws-show-re-start-btn" class="dashicons dashicons-admin-tools" style="font-size: 15px;padding-top: 5px;color: #d2d2d2;text-align:right;width:100%;"></span>';
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
        $html .= wp_nonce_field($formAction);
        $html .= '<input type="submit" class="button button-secondary wpm2aws-process-all-restart-button" style="display:none;" id="' . $inputRef . '" name="' . $inputRef . '" title="re-start file ' . $option . ' process" value="&#x21bb;&nbsp; ' . $btnLabel . '" />';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    private static function makeNavigateSectionButton($sectionName, $inHeader = false)
    {
        $formAction = 'wpm2aws-navigate-section-' . $sectionName;
        $label = 'View Step';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));

        $btnClass = $inHeader ? 'button-seconday' : 'button-primary';
        $formStyle = $inHeader ? 'margin-top:-5px;margin-right:10px;' : '';
        $html = '';

        $html .= '<button data-wpm2aws-section="' . $sectionName . '" style="float:right;" class="wpm2aws-navigate-button button ' . $btnClass . '">' . $label . '</button>';
        // $html .= '<form style="float:right;' . $formStyle . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
        // $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
        // $html .=  wp_nonce_field($formAction);
        // $html .=  '<input type="submit" class="button ' . $btnClass . '" name="wpm2aws-run-download-db" value="' . $btnLabel . '" />';
        // $html .=  '</form>';

        return $html;
    }

    // makeAwsSetupSideSection
    private static function makeAwsSetupSideSection()
    {
        $html = '';
        $button = '';
        $currentActiveStep = get_option('wpm2aws_current_active_step');
        if (false !==  $currentActiveStep && '' !== $currentActiveStep && 1 !== $currentActiveStep) {
            $button = self::makeNavigateSectionButton('aws-settings');
        }
        $html .= '<div>';
        // $html .= '<div style="width:25%;float:left;">';
        // $html .= '<div style="width:20px;height:20px;border:1px solid blue;border-radius:40px;">';

        // $html .= '</div>';
        // $html .= '</div>';

        // $html .= '<div style="width:75%;float:left;">';
        $html .= '<div id="wpm2aws-aws-settings-container" class="wpm2aws-admin-summary-subsection-container">';

        // $html .= '<div style="width:25%;float:left;">';
        // $html .= '<div style="width:20px;height:20px;border:1px solid blue;border-radius:40px;">';

        // $html .= '</div>';
        // $html .= '</div>';

        // $html .= '<div style="width:75%;float:left;">';

        $html .= '<div>';
        $html .= '<h4 style="margin: 0.5em 0;display:inline-block">Step 1: AWS Setup</h4>';
        $html .= $button;
        $html .= '<div class="clear-both"></div>';
        $html .= '</div>';

        $iamId = get_option('wpm2aws-iamid');
        $iamKey = get_option('wpm2aws-iampw');
        $iamName = get_option('wpm2aws-iam-user');
        $iamRegion = get_option('wpm2aws-aws-region');
        if (
            false !== $iamId &&
            '' !== $iamId &&
            false !== $iamKey &&
            '' !== $iamKey &&
            false !== $iamName &&
            '' !== $iamName &&
            false !== $iamRegion &&
            '' !== $iamRegion
        ) {
            $html .= '<p class="notice notice-success" style="margin: 0.5em 0;padding:10px;">';
            $html .= 'AWS Settings Complete';
            $html .= '</p>';
        } else {
            $html .= '<p class="notice notice-warning" style="margin: 0.5em 0;padding:10px;">';
            $html .= 'Update AWS Settings &nbsp;';
            $html .= '</p> ';
        }

        $html .= '</div>';
        $html .= '</div>';
        // $html .= '</div>';
        // $html .= '<div style="clear:both;"></div>';

        return $html;
    }


    // Prepare Databases Section
    private static function makeDataBaseDownloadSection($inMainSection = false)
    {
        $html = '';

        $containerClass = $inMainSection ? '' : 'wpm2aws-admin-summary-subsection-container';
        $button = self::makeRunDatabaseDownloadButton();


        $html .= '<div id="wpm2aws-download-results-container" class="' . $containerClass . ' wpm2aws-download-results-container">';

        if (!$inMainSection) {
            $html .= '<div>';
            $html .= '<h4 style="margin: 0.5em 0;display:inline-block;">Step 2: Prepare Database Status</h4>';
            $html .= self::makeNavigateSectionButton('prepare-database');
            $html .= '<div class="clear-both"></div>';
            $html .= '</div>';
        }

        if ($inMainSection) {
            $dbSizeWarning = get_option('wpm2aws_db_size_warning');
            if (false !== $dbSizeWarning && '' !== $dbSizeWarning) {
                $html .= '<p class="notice notice-warning wpm2aws-prepare-database-btn-container" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Large Database identified (' . $dbSizeWarning . 'MB).';
                $html .= '<br /><strong>** Warning **</strong> This may cause an error during the site cloning process.';
                $html .= '<br /><strong>** Warning **</strong> This may cause the build process to fail.';
                $html .= '<br />Please contact Seahorse if you experience an issue: ';
                $html .= '<a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/contact/" target="_blank">Contact Us</a>';
                $html .= '</p> ';
            }
        }


        if (false === get_option('wpm2aws_download_db_complete')) {
            if (false === get_option('wpm2aws_download_db_started')) {

                $html .= '<div class="wpm2aws_prepare_database_loader" style="display:none;">';
                $html .= '<div class="" style="text-align:center;">';
                $html .= '<h2 style="color:#0085ba;">Prepare Database Commenced...</h2>';
                $html .= '<img src="' . plugins_url('assets/images/ajax-loader-circle.gif', dirname(__FILE__)) . '"/ alt="Loading...">';
                $html .= '<h4><em style="color:#DD6B10;">please wait</em></h4>';
                $html .= '</div>';
                $html .= '</div>';

                $html .= '<p class="notice notice-warning wpm2aws-prepare-database-btn-container" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Prepare Database Not Commenced';
                $html .=  $button;
                $html .= '</p> ';
            } else {
                $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Prepare Database Commenced. In Process...';
                $html .= '</p>';
            }
        } else {
            if ('error' === get_option('wpm2aws_download_db_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Prepare Database Error - Please Re-Run';
                $html .= $button;

                // $html .= print_r(get_option('wpm2aws_upload_errors'));
                $html .= '</p>';
            } elseif ('success' === get_option('wpm2aws_download_db_complete')) {
                $html .= '<p class="notice notice-success" style="margin: 0.5em 0;padding:10px;">Prepare Database Successful</p>';
            } else {
                $html .= '<p class="notice notice-info" style="margin: 0.5em 0;padding:10px;">Prepare Database in Process...</p>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    private static function makeRunDatabaseDownloadButton()
    {
        $formAction = 'wpm2aws-run-db-download';
        $label = 'Prepare Database';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        if (
            !self::checkOptionSet('wpm2aws_download_db_progress') &&
            (
                !self::checkOptionSet('wpm2aws_download_db_complete') ||
                self::checkOptionSet('wpm2aws_download_db_complete') === 'error'
            )
        ) {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '" class="wpm2aws-download-database-form">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .=  wp_nonce_field($formAction);
            // $html .=  '<p class="wpm2aws-run-migration-btn-container">';
            $html .=  '<input type="submit" class="button button-primary wpm2aws-prepare-database-button" name="wpm2aws-run-download-db" value="' . $btnLabel . '" />';
            // $html .=  '</p>';
            $html .=  '</form>';
        } else {
            $html .= '<span class="button button-primary" disabled>' . $btnLabel . '</span>';
        }
        return $html;
    }


    // Run Migration Section

    private static function makeRunFullMigrationSection($inMainSection = false)
    {
        $html = '';
        $button = self::makeRunMigrateButton();


        $html .= '<div id="wpm2aws-run-migration-container" class="wpm2aws-admin-summary-subsection-container">';
        if (!$inMainSection) {
            $html .= '<div>';
            $html .= '<h4 style="margin: 0.5em 0;display:inline-block;width:65%;">Step 5: Launch a Clone of this site on AWS</h4>';
            $html .= self::makeNavigateSectionButton('launch-on-aws');
            $html .= '<div class="clear-both"></div>';
            $html .= '</div>';
        }
        $html .= $button;
        $html .= '</div>';

        return $html;
    }

    private static function makeRunMigrateButton($withSettings = false)
    {
        $formAction = 'wpm2aws-run-full-migration';
        $label = 'Launch on AWS';
        $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
        $html = '';

        $html .= '<div class="wpm2aws_post_launch_loader" style="display:none;">';
        $html .= '<div class="" style="text-align:center;">';
        $html .= '<h2 style="color:#0085ba;">A Clone of this Website is currently being compiled on AWS</h2>';
        $html .= '<img src="' . plugins_url('assets/images/ajax-loader-circle.gif', dirname(__FILE__)) . '"/ alt="Loading...">';
        $html .= '<h4><em style="color:#DD6B10;">please wait</em></h4>';
        $html .= '</div>';
        $html .= '</div>';

        if (self::allSettingsSet()) {
            if (
                false === get_option('wpm2aws-lightsail-instance-details') ||
                '' === get_option('wpm2aws-lightsail-instance-details')
            ) {
                $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
                $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
                $html .= wp_nonce_field($formAction);
                $html .= '<p class="wpm2aws-run-migration-btn-container">';
                $html .= '<input type="submit" class="button button-primary wpm2aws-button-large wpm2aws-launch-aws-button" id="wpm2aws-demo-full-migration"  name="wpm2aws-demo-full-migration" value="' . $btnLabel . '" />';
                $html .= '</p>';
                $html .= '</form>';
            } else {
                $html .= '<div class="wpm2aws-run-migration-btn-container">';
                $html .= '<div class="button button-primary wpm2aws-button-large" disabled title="This Website Has Already Been Cloned on AWS">' . $btnLabel . '</div>';
                $html .= '</div>';
            }
        } elseif (self::adminDownloadSettingsSet()) {
            $formAction = 'wpm2aws-run-full-migration-admin';
            $label = 'Run Migration (admin)';
            $btnLabel = esc_attr(__($label, 'migrate-2-aws'));
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=' . $formAction . '">';
            $html .= '<input type="hidden" name="action" value="' . $formAction . '" />';
            $html .= wp_nonce_field($formAction);
            $html .= '<p class="wpm2aws-run-migration-btn-container">';
            $html .= '<input type="submit" class="button button-secondary wpm2aws-button-large wpm2aws-launch-aws-button" name="wpm2aws-full-migration-admin" value="' . $btnLabel . '" />';
            $html .= '</p>';
            $html .= '</form>';
        } else {
            if (false === get_option('wpm2aws_download_db_complete') || get_option('wpm2aws_download_db_complete') === 'error') {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare Database" first!';
                $html .= '</p>';
            }
            if (false === get_option('wpm2aws_fszipper_complete') || 'error' === get_option('wpm2aws_fszipper_upload_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Prepare File System" first!';
                $html .= '</p>';
            }
            if (false === get_option('wpm2aws_zipped_fs_upload_complete') || 'error' === get_option('wpm2aws_zipped_fs_upload_complete')) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Run "Clone to AWS" first!';
                $html .= '</p>';
            }

            if (
                false !== get_option('wpm2aws_download_db_complete') &&
                'success' !== get_option('wpm2aws_download_db_complete') &&
                false !== get_option('wpm2aws_fszipper_complete') &&
                'success' !== get_option('wpm2aws_fszipper_complete') &&
                false !== get_option('wpm2aws_zipped_fs_upload_complete') &&
                'success' !== get_option('wpm2aws_zipped_fs_upload_complete')
            ) {
                $html .= '<p class="notice notice-error" style="margin: 0.5em 0;padding:10px;">';
                $html .= 'Please re-check process settings.';
                $html .= '</p>';
            }
            $html .= '<div class="wpm2aws-run-migration-btn-container">';
            $html .= '<div class="button button-primary wpm2aws-button-large" disabled title="This Website Has Already Been Cloned on AWS">' . $btnLabel . '</div>';
            $html .= '</div>';
        }
        return $html;
    }


    private static function makeShhKeyDownloadButton()
    {
        $html = '';
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '?action=wpm2aws-download-key">';
        $html .= '<input type="hidden" name="action" value="wpm2aws-download-key" />';
        $html .=  wp_nonce_field('wpm2aws-download-key');
        $html .= '<p>';
        $html .= '<input type="submit" class="button button-primary" name="wpm2aws-download-key" value="Download Key" />';
        $html .= '</p>';
        $html .= '</form>';

        // $parentDirStart = strpos(WPM2AWS_KEY_DOWNLOAD_PATH, get_option('wpm2aws-aws-s3-upload-directory-name'));
        // $downloadPath = substr(WPM2AWS_KEY_DOWNLOAD_PATH, $parentDirStart);

        // $html .= '<div><a class="button button-primary" href="' . get_home_url() . '/' . $downloadPath . '" target="_blank">Download Key</a></div>';

        return $html;
    }

    /**
     * Convert instance created at time to formatted output
     */
    private static function formatInstanceLaunchTime($createdAt)
    {
        try {
            $dateTime = new \DateTime($createdAt);
        } catch (Exception $exception) {
            $exceptionMessage= $exception->getMessage();

            return \sprintf('not available (%s)', $exceptionMessage);
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * Function to calculate the time for progress bar
     *
     * @param int $buildDurationSeconds
     * @param string $instanceLaunchTime
     *
     * @return false|int
     */
    private static function remainingTimeInSecondsForProgressBar($buildDurationSeconds, $instanceLaunchTime)
    {
        $launchTimeUnix = \strtotime($instanceLaunchTime);

        if ($launchTimeUnix === false) {
            return false;
        }

        $expectedBuildTime = $launchTimeUnix + $buildDurationSeconds;
        $now = \time();
        $timeRemaining = $expectedBuildTime - $now;

        if ($timeRemaining < 1) {
            return false;
        }

        return $timeRemaining;
    }

    // Current Settings Section
    private static function makeConfirmedLightsailInfoSection()
    {
        if (get_option('wpm2aws-lightsail-instance-details') === false) {
            return;
        }

        $lightsailDetails = get_option('wpm2aws-lightsail-instance-details');

        $instance_datetime = $lightsailDetails['details']['createdAt'];
        $formattedLaunchDate = self::formatInstanceLaunchTime($instance_datetime);

        $buildDurationSeconds = (15 * 60); // 15 minutes

        $remainingTime = self::remainingTimeInSecondsForProgressBar($buildDurationSeconds, $formattedLaunchDate);

        echo '<div class="wpm2aws-inputs-row" class="wpm2aws-launch-instance-content" style="border-color:#DD6B10;width:97%;padding:15px 10px;display:flex;align-items: center;">';

        echo self::makeLaunchGraphic($remainingTime);

        echo '<div class="wpm2aws-launch-instance-details">';

        if ($remainingTime !== false) {
            echo '<div class="wpm2aws-launch-build-notice">';
            echo '<h3>Hold tight, your site is building!</h3>';
            echo '<p>You have successfully created a clone of your website on AWS! The site build is currently in progress.';
            echo '<br>';
            echo 'Links to your cloned site will be displayed once the build has completed. (average build time: 15 minutes approximately)</p>';
            echo '<div id="wpm2aws-launch-build-progress-bar">';
            echo '<div id="wpm2aws-launch-build-completed" class="wpm2aws-launch-build-completed-progress-bar" data-total-time="' . $buildDurationSeconds . '" data-remaining-time="' . $remainingTime . '">';
            echo '<div id="wpm2aws-launch-build-progress-bar-background-loop"></div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        echo "<a class='wpm2aws-launch-build-launch-button' target='_blank' style='background: #1a7899;padding: 10px 20px;border-radius: 30px;color: #fff;text-decoration: none;font-weight: 600;line-height: 1;margin: 0;' href='" . esc_url($lightsailDetails['publicIp']). "'>Visit Your Clone Site Here</a>";

        echo '<h4 style="font-weight:bold">An email will be sent to the email address associated with your licence key with further information and active links.</h4>';
        echo '<h4>The details outlined below are for reference only.</h4>';
        echo '<ul style="padding:15px;background: #f0f0f0;color:#fff;color: #1a7899;border: 1px solid #e59335;border-radius: 5px;">';

        if (defined('WPM2AWS_TESTING')) {
            echo '<li> <strong>Instance Name: </strong>' . $lightsailDetails['name'] . '</li>';
        }

        echo '<li> <strong>Region: </strong>' . $lightsailDetails['region'] . ', (Ireland)</li>';

        echo '<li> <strong>Launch Time: </strong>' . $formattedLaunchDate . '</li>';
        echo '<li> <strong>Source URL: </strong>' . get_bloginfo('wpurl') . '</li>';
        echo '<li> <strong>Plan Type: </strong>';
        echo '<a target="_blank" style="color:#e59335;font-weight:600;" href="https://aws.amazon.com/lightsail/pricing/"> $10 per month</a>';
        echo '</li>';
        echo '</ul>';

         // show this to the review user
         if (
            false !==  get_option('wpm2aws_valid_licence_type') &&
            'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
        ) {
            echo '<h3>Clone to AWS Trial</h3>';
            echo '<p>This temporary site will remain active for review for 36 hours after launch at which point it will be shut-down.</p>';
            echo '<p>If you are interested in migrating to AWS after this time the Self-Managed option offers the full migration features in the plugin, connected directly to your own AWS account.</p>';
            echo '<p>Would you like to know more? Check out the available options <a target="_blank" href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/pricing/">here</a>.</p>';
        } else {
            /// show this to a paid user (not review licence)
            if (false !== get_option('wpm2aws_lightsail_ssh') && '' !== get_option('wpm2aws_lightsail_ssh')) {
                echo '<h3>NB: Download and store this SSH Key securely.</h3>';
                $shhDetails = get_option('wpm2aws_lightsail_ssh');
                if (!empty($shhDetails['prkey'])) {
                    if (!file_exists(WPM2AWS_KEY_DOWNLOAD_PATH)) {
                        wpm2aws_makeDownloadKeyFile();
                    }
                    echo self::makeShhKeyDownloadButton();
                }
            }
        }

        echo '</div>';

        echo '<div class="wpm2aws-launch-instance-details-right-image">';
        echo '<img src="' . plugins_url('assets/images/launch-illustration.png', dirname(__FILE__)) . '">';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Generates the launch card with image.
     *
     * @return string
     */
    private static function makeLaunchGraphic($remainingTime)
    {
        $html = '';

        $html .= '<div class="wpm2aws-instance-launch-graphic">';
        $html .= '<div class="wpm2aws-instance-launch-graphic-img">';
        $html .= '<img src="' . plugins_url('assets/images/launch-instance.png', dirname(__FILE__)) . '">';
        $html .= '</div>';
        $html .= '<div class="wpm2aws-instance-launch-graphic-content">';
        $html .= '<h2>Congratulations!</h2>';

        if ($remainingTime !== false) {
            $html .= '<h3 class="wpm2aws-instance-launch-text-before-launch-complete">Your cloned site is currently building and will be available shortly</h3>';
            $html .= '<h3 style="display:none;" class="wpm2aws-instance-launch-text-after-launch-complete">You have successfully created<br> your site clone instance!</h3>';
        } else {
            $html .= '<h3>You have successfully created<br> your site clone instance!</h3>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function scanFsDirs()
    {
        $fullFilePath = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name');

        if (!is_dir($fullFilePath)) {
            return 'Error! Directory does not exist: ' . $fullFilePath;
        }

        $directories = wpm2aws_listDirectoriesForZip($fullFilePath);

        // wp_die(print_r($directories));

        if (!empty($directories)) {
            $zippingDirsList = array();
            foreach ($directories as $dirIx => $dirVals) {
                // $separatorPos = strrpos($dirIx, DIRECTORY_SEPARATOR);
                // if ($separatorPos) {
                //     $dirName = substr($dirIx, 0, $separatorPos);
                // } else {
                //     $dirName = $dirIx;
                // }

                $dirName = $dirIx;
                if (!isset($zippingDirsList[$dirName])) {
                    $zippingDirsList[$dirName] = array('name' => $dirName);
                }
            }
        }

        // wp_die(print_r($zippingDirsList));

        require_once 'adminFileSizer.class.php';
        $fileSizer = new WPM2AWS_FileSizer();

        if (!empty($zippingDirsList)) {
            foreach ($zippingDirsList as $dirIx => $dirVals) {
                $zippingDirsList[$dirIx]['size'] = $fileSizer->getDirSize($fullFilePath . DIRECTORY_SEPARATOR . $dirVals['name']);
            }
        }
        // wp_die(print_r($zippingDirsList));
        return $zippingDirsList;
    }

    private static function runFileSystemZipStatusUpdateCheck()
    {
        $step = get_option('wpm2aws_current_active_step');

        if (3 !== $step) {
            return false;
        }

        $started = get_option('wpm2aws_fszipper_started');
        $complete = get_option('wpm2aws_fszipper_complete');
        $counter = get_option('wpm2aws_fszipper_counter');
        $errors = get_option('wpm2aws_fszipper_errors');

        if (
            false === $started ||
            false === $complete ||
            false === $counter ||
            !isset($counter['total']) ||
            !isset($counter['complete'])
        ) {
            return false;
        }

        if (false !== $errors && !empty($errors)) {
            return false;
        }

        if ('success' === $complete) {
            return true;
        }

        if ($counter['total'] === $counter['complete']) {
            wpm2awsAddUpdateOptions('wpm2aws_fszipper_complete', 'success');
            wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 4);
            wpm2awsLogRAction('wpm2aws_fszipper_issue', 'runFileSystemZipStatusUpdateCheck: Alternate Complete Update Action Triggered');
        }
        return true;
    }

    private static function runFileSystemUploadStatusUpdateCheck()
    {
        $step = get_option('wpm2aws_current_active_step');

        if (4 !== $step) {
            return false;
        }

        $started = get_option('wpm2aws_zipped_fs_upload_started');
        $complete = get_option('wpm2aws_zipped_fs_upload_complete');
        $counter = get_option('wpm2aws_zipped_fs_upload_counter');
        $errors = get_option('wpm2aws_zipped_fs_upload_errors');

        if (
            false === $started ||
            false === $complete ||
            false === $counter ||
            !isset($counter['total']) ||
            !isset($counter['complete'])
        ) {
            return false;
        }

        if (false !== $errors && !empty($errors)) {
            return false;
        }

        if ('success' === $complete) {
            return true;
        }

        if ($counter['total'] === $counter['complete']) {
            wpm2awsAddUpdateOptions('wpm2aws_zipped_fs_upload_complete', 'success');
            wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 5);
            wpm2awsLogRAction('wpm2aws_fs_uploader_issue', 'runFileSystemUploadStatusUpdateCheck: Alternate Complete Update Action Triggered');
        }
        return true;
    }
}
