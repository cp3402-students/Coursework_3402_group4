<?php

/* prevent access from outside cms */
defined('WPM2AWS_ABSPATH') or die('You are not authorised to access this file.');

if (!class_exists('WPM2AWS_Settings')) {
    class WPM2AWS_Settings
    {
        private $adminPage;
        private static $settingsErrors;

        public static function checkSettings()
        {
            self::$settingsErrors = array();

            self::client_supported();
            self::fopen_available();
            self::isMultisiteInstallation();
            self::zip_archive_available();
            self::zip_archive_disabled();
            self::checkSimplyStaticPlugin();

            if (!empty(self::$settingsErrors)) {
                $errorMsg = '';
                $errorMsg .= '<p>Minimum System Requirements are not available to run this Plugin:</p>';
                $errorMsg .= '<ul style="list-style:inside;">';
                foreach (self::$settingsErrors as $errorIx => $errorText) {
                    $errorMsg .= '<li>' . $errorText . '</li>';
                }
                $errorMsg .= '</ul>';

                $errorMsg .= '<p><a href="' . admin_url('/index.php') . '">Return to Dashboard</a></p>';
                throw new Exception($errorMsg);
            }
            return true;
        }

        private static function has_fully_supported_php()
        {
            $versionCheck = version_compare( PHP_VERSION, '5.3', '>' );
            if (!$versionCheck) {
                self::$settingsErrors[] = 'PHP Version Below Minimum Requirement';
            }
            return $versionCheck;
        }

        private static function curl_available()
        {
            $curlAvailable = extension_loaded( 'curl' );
            if (!$curlAvailable) {
                self::$settingsErrors[] = 'cURL Extension Not Available';
            }
            return $curlAvailable;
        }

        private static function curl_exec_disabled()
        {
            $disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
            $curlExecDisabled = in_array( 'curl_exec', $disabled_functions );
            if ($curlExecDisabled) {
                self::$settingsErrors[] = '"curl_exec" must be Enabled';
            }
            return $curlExecDisabled;
        }

        /**
         * Check if the current site is a multi-site installation & set an error message.
         *
         * @return bool
         */
        private static function isMultisiteInstallation()
        {
            $isMultiSiteInstallation = is_multisite();

            if ($isMultiSiteInstallation === true) {
                self::$settingsErrors[] = 'We have detected that this site is a multisite installation. WordPress Multisite is not yet supported.';
            }

            return $isMultiSiteInstallation;
        }



        private static function fopen_available()
        {
            $fopenAllowed = ini_get( 'allow_url_fopen' );
            if (!$fopenAllowed) {
                self::$settingsErrors[] = 'fopen Directive must be Enabled';
            }
            return $fopenAllowed;
        }

        private static function checkSimplyStaticPlugin()
        {
            $isActive = is_plugin_active( 'simply-static/simply-static.php' );
            if ($isActive === true) {
              self::$settingsErrors[] = 'Simply Static plugin is active on this site and can cause issues with the migration process. Please disable this plugin for the duration of the cloning process.';
            }
            return $isActive;
        }

        private static function zip_archive_available()
        {
            $zipArchiveAvailable = class_exists('ZipArchive');
            if (!$zipArchiveAvailable) {
                self::$settingsErrors[] = 'ZipArchive must be Enabled';
            }
            return $zipArchiveAvailable;
        }

        private static function zip_archive_disabled()
        {
            $disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
            $zipDisabled = in_array( 'zip', $disabled_functions );
            if ($zipDisabled) {
                self::$settingsErrors[] = '"zip" must be Enabled';
            }
            return $zipDisabled;
        }

        private static function client_supported()
        {
            return 	self::has_fully_supported_php() &&
                    self::curl_available() &&
                    !self::curl_exec_disabled();
        }

        /**
         * Main Action Function
         */
        public function run()
        {
            add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));

            if (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_register_licence_form') {
                $this->registerLicence();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_iam_form') {
                $this->loadAPIFiles(); // TODO: REMOVE WHEN S3 (CREATE BUCKET) IS MIGRATED
                // $this->loadAPIGlobalFiles();
                $this->loadAPIRemoteIamFiles();
                $this->validateIamUser();
                $this->runFindOverSizedDataBaseTables();
	            $this->runFindExcludedFileAndDirectories();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_aws_region') {
                $this->updateAwsRegion();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_s3_create_bucket') {
                $this->loadAPIFiles();
                $this->updateS3BucketName();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_s3_refresh_bucket_list') {
                $this->loadAPIFiles();
                $this->refreshS3BucketList();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_s3_use_bucket') {
                $this->updateS3BucketName();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_upload-directory-name') {
                $this->setS3UploadName();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_upload-directory-path') {
                $this->setS3UploadPath();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_lightsail-name') {
                $this->updateLightsailName();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_lightsail-region') {
                $this->updateLightsailRegion();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_lightsail-size') {
                $this->updateLightsailSize();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_lightsail-name-and-size') {
                $this->updateLightsailNameAndSize();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_domainName') {
                $this->updateDomainName();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-full-migration') {
                $this->runFullMigrationZipped();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-full-migration-admin') {
                $this->runFullMigrationAdmin();
            } elseif (isset($_GET['action']) && ($_GET['action'] === 'wp_wpm2aws-uploader-once' || $_GET['action'] === 'wp_wpm2aws-uploader-all')) {
                $this->runDetachedUploader();
            } elseif (isset($_GET['action']) && ($_GET['action'] === 'wp_wpm2aws-fszipper-once' || $_GET['action'] === 'wp_wpm2aws-fszipper-all')) {
                $this->runDetachedFsZipper();
            } elseif (isset($_GET['action']) && ($_GET['action'] === 'wp_wpm2aws-zipped-fs-uploader-once' || $_GET['action'] === 'wp_wpm2aws-zipped-fs-uploader-all')) {
                $this->runDetachedZippedFsUploader();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-export-db') {
                $this->runDbExport();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-db-download') {
                $this->runDbExport();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-fs-upload') {
                $this->runFsUploader();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-fs-zip') {
                $this->runFsZipper();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-run-zipped-fs-upload') {
                $this->runZippedFileSystemUploader();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-reset-all-settings') {
                $this->resetAllSettings();
            }elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-restart-process') {
                $this->restartProcessSettings();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-get-dynamic-progress') {
                $this->runDynamicProgressCheck();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_add_new_metric_alarm_form') {
                $this->loadAPIFiles();
                $this->addMetricAlarm();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_console_reboot_instance') {
                $this->loadAPIFiles();
                $this->rebootInstance();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_console_create_instance_snapshot') {
                $this->loadAPIFiles();
                $this->createInstanceSnapshot();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_console_change_instance_region') {
                $this->loadAPIFiles();
                $this->changeInstanceRegion();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws_console_change_instance_plan') {
                $this->loadAPIFiles();
                $this->changeInstancePlan();
            } elseif (isset($_GET['action']) && $_GET['action'] === 'wpm2aws-download-key') {
                $this->downloadKey();
            } else {
                $this->loadAdminMenu();
            }
        }

        // Add the specific assets
        public function enqueueAssets()
        {
            // enqueue pages/css/js etc
            wp_enqueue_style('wpm2aws_styles', plugins_url('/admin/assets/styles.css', WPM2AWS_PLUGIN_BASENAME));
            wp_enqueue_style('wpm2aws_console_styles', plugins_url('/admin/assets/console-styles.css', WPM2AWS_PLUGIN_BASENAME));
            wp_enqueue_style('wpm2aws_flags_styles', plugins_url('/admin/assets/flag-icons.css', WPM2AWS_PLUGIN_BASENAME));
            wp_enqueue_script('wpm2aws_script', plugins_url('/admin/assets/scripts.js', WPM2AWS_PLUGIN_BASENAME), array('jquery'), WPM2AWS_VERSION, true);
            wp_enqueue_script('wpm2aws_console_script', plugins_url('/admin/assets/console-scripts.js', WPM2AWS_PLUGIN_BASENAME), array('jquery'), WPM2AWS_VERSION, true);
            // wp_enqueue_script('prefix-çhart-js', 'https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js');
            wp_enqueue_script('wpm2aws_chart_js', plugins_url('/vendor/chartjs/chart.js', WPM2AWS_PLUGIN_BASENAME), array('jquery'), WPM2AWS_VERSION, false);

            // Make variable available in '/admin/assets/scripts.js'
            wp_localize_script(
                'wpm2aws_script',
                'wpm2aws_script_defined_variables',
                array(
                    'wpm2aws_migrations_api_url' => WPM2AWS_MIGRATIONS_API_URL,
                    'wpm2aws_seahorse_website_url' => WPM2AWS_SEAHORSE_WEBSITE_URL,
                )
            );
        }

        // Load required helper files
        private function loadFiles()
        {
            // require_once WPM2AWS_PLUGIN_DIR . '/inc/capabilities.php';
            require_once WPM2AWS_PLUGIN_DIR . '/inc/functions.php';
        }


        /**
         * Load required migration helper files
         *
         * @return void
         */
        private function loadAdminMigrationFiles()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/adminMigrate.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminValidations.php';
        }

        /**
         * Load required console helper files
         *
         * @return void
         */
        private function loadAdminConsoleFiles()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/adminConsole.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminValidations.php';
            return;
        }

        /**
         * Loads required helper files
         *
         * @return void
         */
        private function loadAdminPageFiles()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminValidations.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminNotices.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminMenu/adminMenu.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminMenu/adminMenuDestinationSite.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminMenu/adminMenuSourceSite.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/welcomePanel.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminPages/adminPagesCommon.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminPages/adminPagesGeneral.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminPages/adminPagesConsole.php';
            require_once WPM2AWS_PLUGIN_DIR . '/inc/migration-form.php';
            require_once WPM2AWS_PLUGIN_DIR . '/inc/migration-form-template.php';
        }

        /**
         * Loads the Administrators Menu
         *
         * @return void
         */
        public function loadAdminMenu()
        {
            $this->loadFiles();
            $this->loadAdminPageFiles();

            if (defined('WPM2AWS_MIGRATED_SITE') === true && WPM2AWS_MIGRATED_SITE === true ) {
                new WPM2AWS_AdminMenuDestinationSite();

                return;
            }

            new WPM2AWS_AdminMenuSourceSite();
        }

        /**
         * Runs registration on Seahorse Licence
         *
         * @return void
         */
        public function registerLicence()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->registerLicence();
            return;
        }

        /**
         * Runs validation on IAM User Inputs
         *
         * @return void
         */
        public function validateIamUser()
        {
            $this->loadAPIRemoteS3Files();
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->validateInputs();
            return;
        }

        /**
         * Updates & Saves preferred AWS region
         *
         * @return void
         */
        public function updateAwsRegion()
        {
            $this->loadAPIRemoteS3Files();
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateRegion();
        }

        public function refreshS3BucketList()
        {
            $this->loadAPIRemoteS3Files();
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->getExistingS3Buckets();
            return;
        }

        public function updateS3BucketName()
        {
            $this->loadAPIRemoteS3Files();
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateS3BucketName();
            return;
        }

        public function setS3UploadName()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->setS3UploadName();
        }

        public function setS3UploadPath()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->setS3UploadPath();
        }

        public function updateLightsailName()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateLightsailName();
        }

        private function updateLightsailRegion()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateLightsailRegion();
        }

        private function updateLightsailSize()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateLightsailSize();
        }

        public function updateLightsailNameAndSize()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->addUpdateLightsailNameAndSize();
        }

        public function updateDomainName()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $migrate->updateDomainName();
        }


        public function loadAPIFiles()
        {
            // Require the Composer autoloader.
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiGlobal.class.php';
        }

        public function loadAPIRemoteFiles()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiRemote.class.php';
        }

        public function loadAPIRemoteS3Files()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiRemoteS3.class.php';
        }

        public function loadAPIRemoteIamFiles()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiRemoteIam.class.php';
        }

        public function loadUploaderFiles()
        {
            // Require the Composer autoloader.
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            // Require AWS API Functions
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiGlobal.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/uploader.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/logger.class.php';
        }

        public function loadZippedFsUploaderFiles()
        {
            // Require the Composer autoloader.
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            // Require AWS API Functions
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiGlobal.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/zippedFsUploader.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/logger.class.php';
        }

        public function loadFsZipperFiles()
        {
            // Require the Composer autoloader.
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            // Require AWS API Functions
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.class.php';
            require_once WPM2AWS_PLUGIN_DIR . '/admin/classes/apiGlobal.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/zipper.class.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/logger.class.php';
        }

        public function loadDbDownloaderFiles()
        {
            // Require the Composer autoloader.
            require_once WPM2AWS_PLUGIN_DIR . '/vendor/autoload.php';
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/dbDownloader.class.php';
        }

        public function loadFileSizerFiles()
        {
            require WPM2AWS_PLUGIN_DIR . '/admin/classes/adminFileSizer.class.php';
        }

        /* Current Migration Runner */
        public function runFullMigrationZipped()
        {
            // Check S3 Bucket Exists
            $this->checkS3BucketAvailable();

            $this->loadAPIRemoteIamFiles();

            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $this->loadAPIFiles();
            $this->loadAPIRemoteFiles();

            return $instance = $migrate->createLightsailInstanceZippedRemote();
        }

        public function runFullMigrationAdmin()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            $this->loadAPIFiles();

            if (isset($_POST["wpm2aws-full-migration-admin"])) {
                return $instance = $migrate->createLightsailInstanceAdmin();
            }
        }

        public function addMetricAlarm()
        {
            $this->loadAdminConsoleFiles();
            $console = new WPM2AWS_Console();
            $console->addNewMetricAlarm();
            return;
        }

        public function rebootInstance()
        {
            $this->loadAdminConsoleFiles();
            $console = new WPM2AWS_Console();
            $console->runRebootInstance();
            return;
        }

        public function createInstanceSnapshot()
        {
            $this->loadAdminConsoleFiles();
            $console = new WPM2AWS_Console();
            $console->runCreateInstanceSnapshot();
            return;
        }

        public function changeInstanceRegion()
        {
            $this->loadAdminConsoleFiles();
            $console = new WPM2AWS_Console();
            $console->runChangeInstanceRegion();
            return;
        }

        public function changeInstancePlan()
        {
            $this->loadAdminConsoleFiles();
            $console = new WPM2AWS_Console();
            $console->runChangeInstancePlan();
            return;
        }

        public function runDetachedUploader()
        {
            $this->loadUploaderFiles();
            new WPM2AWS_Uploader();
        }

        public function runDetachedZippedFsUploader()
        {
            $this->loadZippedFsUploaderFiles();
            new WPM2AWS_ZippedFsUploader();
        }

        public function runDetachedFsZipper()
        {
            $this->loadFsZipperFiles();
            new WPM2AWS_Zipper();
        }

        public function runFindOverSizedDataBaseTables()
        {
            $this->loadAdminMigrationFiles();
            $this->loadDbDownloaderFiles();
            $databaseDownloader = new WPM2AWS_DbDownloader();
            $databaseDownloader->searchAndSetOverSizedDataBaseTables();
        }

	    public function runFindExcludedFileAndDirectories()
	    {
            $this->loadFiles();
            $this->loadAdminMigrationFiles();
		    $this->loadFileSizerFiles();
		    $databaseDownloader = new WPM2AWS_FileSizer();
		    $databaseDownloader->searchAndSetOverSizedFilesAndDirectories();
	    }

        public function runDbExport()
        {
            $this->loadAdminMigrationFiles();
            $this->loadDbDownloaderFiles();
            $databaseDownloader = new WPM2AWS_DbDownloader();
            $databaseDownloader->runDataBaseExport();
        }

        public function runFsUploader()
        {
            $this->loadAdminMigrationFiles();
            $this->loadUploaderFiles();
            new WPM2AWS_Uploader();
            return;
        }

        public function runFsZipper()
        {
            $this->loadAdminMigrationFiles();
            $this->loadFsZipperFiles();
            new WPM2AWS_Zipper();
            return;
        }

        public function runZippedFileSystemUploader()
        {
            // Check S3 Bucket Exists
            $this->checkS3BucketAvailable();

            $this->loadAdminMigrationFiles();
            $this->loadZippedFsUploaderFiles();
            new WPM2AWS_ZippedFsUploader();
            return;
        }

        public function resetAllSettings()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            if (isset($_POST["wpm2aws-reset-all-settings"])) {
                $migrate->resetAll();
            }
            return;
        }

        public function restartProcessSettings()
        {
            $this->loadAdminMigrationFiles();
            $migrate = new WPM2AWS_MIGRATE();
            if (isset($_POST["wpm2aws-restart-process"])) {
                $migrate->restartProcess();
            }
            return;
        }

        private function runDynamicProgressCheck()
        {
            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminDynamicProgress.php';
            return;
        }

        public function downloadKey()
        {
            add_action('admin_init', array($this,'getDownloadKey'));
        }

        public function getDownloadKey()
        {
            if (file_exists(WPM2AWS_KEY_DOWNLOAD_PATH)) {
                $parentDirStart = strpos(WPM2AWS_KEY_DOWNLOAD_PATH, get_option('wpm2aws-aws-s3-upload-directory-name'));
                $downloadPath = substr(WPM2AWS_KEY_DOWNLOAD_PATH, $parentDirStart);
                $url =  get_home_url() . '/' . $downloadPath;
                header("Content-type: application/x-x509-ca-cert", true, 200);
                header("Content-Disposition: attachment; filename=public_access_key.pem");
                header("Pragma: no-cache");
                header("Expires: 0");
                $contents = file_get_contents(WPM2AWS_KEY_DOWNLOAD_PATH);
                echo $contents;
                exit();
            }


            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>SSH Key File Could Not Be Downloaded', 'migrate-2-aws'));

            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';
            wpm2awsWpRedirectAndExit();
//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        private function checkS3BucketAvailable()
        {
            // Check S3 Bucket Exists
            $this->loadAPIRemoteS3Files();
            $s3 = new WPM2AWS_ApiRemoteS3();

            require_once WPM2AWS_PLUGIN_DIR . '/admin/includes/adminFunctions.php';

            try {
                $bucketExists = $s3->checkBucketExists();
            } catch (\Exception $e) {
                $exceptionMessage = $e->getMessage();
                set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __($exceptionMessage . '. Click \'Reset All\' to re-run.', 'migrate-2-aws'));

                wpm2awsWpRedirectAndExit();
//                exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
            }

            if ($bucketExists === 'true' || $bucketExists === true) {
                return;
            }

            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error! Upload Destination Does Not Exist. Click \'Reset All\' to re-run.', 'migrate-2-aws'));


            wpm2awsWpRedirectAndExit();

//            exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
        }
    }
}
