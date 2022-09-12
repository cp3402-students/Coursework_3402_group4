<?php

class WPM2AWS_ZippedFsUploader
{
    /**
     * @var WPM2AWS_RunRequestZippedFsUpload
     */
    protected $process_single;

    /**
     * @var WPM2AWS_RunProcessZippedFsUpload
     */
    protected $process_all;

    /**
     * Example_Background_Processing constructor.
     */
    public function __construct()
    {
        // add_action('plugins_loaded', array( $this, 'init' ));
        add_action('admin_init', array( $this, 'init' ));
        add_action('admin_init', array( $this, 'process_handler' ));
    }

    /**
     * Init
     */
    public function init()
    {
        require_once plugin_dir_path(__FILE__) . 'logger.class.php';


        if (! class_exists('WP_Async_Request', false)) {
            include_once WPM2AWS_PLUGIN_DIR  . '/vendor/a5hleyrich/wp-background-processing/classes/wp-async-request.php';
        }

        if (! class_exists('WP_Background_Process', false)) {
            include_once WPM2AWS_PLUGIN_DIR . '/vendor/a5hleyrich/wp-background-processing/classes/wp-background-process.php';
        }
        require_once WPM2AWS_PLUGIN_DIR . '/admin/abstracts/uploader/async-requests/runRequestZippedFsUpload.class.php';

        require_once WPM2AWS_PLUGIN_DIR . '/admin/abstracts/uploader/background-processes/runProcessZippedFsUpload.class.php';

        $this->process_single = new WPM2AWS_RunRequestZippedFsUpload();
        $this->process_all = new WPM2AWS_RunProcessZippedFsUpload();
    }


    /**
     * Process handler
     */
    public function process_handler()
    {
        if (isset($_POST['_wpnonce'])) {
            // echo "<br>In Post Section of Process Handlere";
            // if ((! isset($_POST['wpm2aws-process-once-submit']) && ! isset($_POST['wpm2aws-process-all-submit'])) || ! isset($_POST['_wpnonce'])) {
            //     wp_die('Invalid Post');
            //     return;
            // }

            if (!isset($_POST['wpm2aws-process-zipped-fs-uploader-all-submit']) && !isset($_POST['wpm2aws-process-zipped-fs-uploader-all-restart'])) {
                wp_die('Invalid Post');
                return;
            }



            if (! wp_verify_nonce($_POST['_wpnonce'], 'wpm2aws-run-zipped-fs-upload')) {
                print_r($_POST);
                wp_die('Invalid Nonce');
                return;
            }
            // echo "<br>Post:";
            // print_r($_POST);
            if (isset($_POST['wpm2aws-process-zipped-fs-uploader-once-submit'])) {
                // echo "<br>Process Once";
                $this->handle_single('POST_process-zipped-fs-uploader-once');
            } elseif (isset($_POST['wpm2aws-process-zipped-fs-uploader-all-submit'])) {

                // If Self-Managed:
                // Check that the S3 Bucket Location
                // Is in the Same Location as the AWS region
                if ('self' === get_option('wpm2aws-customer-type')) {
                    // Establish API Connection
                    try {
                        $awsApi = new WPM2AWS_ApiGlobal();
                    } catch (Exception $e) {
                        $errorMsg = 'Error: cant set class: ' . $e->getMessage();
                        wpm2awsLogAction($errorMsg);
                        wpm2awsLogRAction('wpm2aws_fs_uploader_error', $errorMsg);
                        set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __($errorMsg, 'migrate-2-aws'));

                        wpm2awsWpRedirectAndExit();
//                        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
                    }

                    // Get Bucket Location
                    try {
                        $location = $awsApi->getBucketResidesLocation(false);
                    } catch (Exception $e) {
                        $errorMsg = 'Error: Can\'t Get bucket location: ' . $e->getMessage();
                        wpm2awsLogAction($errorMsg);
                        wpm2awsLogRAction('wpm2aws_fs_uploader_error', $errorMsg);
                        set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __($errorMsg, 'migrate-2-aws'));

                        wpm2awsWpRedirectAndExit();
//                        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
                    }

                    // Check Bucket Location Matches AWS Region
                    if ($location !== get_option('wpm2aws-aws-region')) {
                        $errorMsg = 'Error: The Selected Bucket does not reside in the the Selected AWS Region.';
                        $errorMsg .= '<br><br>';
                        $errorMsg .= 'Selected Bucket Location: <strong>' . $location . '.</strong>';
                        $errorMsg .= '<br>';
                        $errorMsg .= 'Selected AWS Region: <strong>' . get_option('wpm2aws-aws-region') . '.</strong>';
                        $errorMsg .= '<br><br>';
                        $errorMsg .= 'Please Review Settings and Re-Run';

                        wpm2awsLogAction($errorMsg);
                        wpm2awsLogRAction('wpm2aws_fs_uploader_error', $errorMsg);
                        set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __($errorMsg, 'migrate-2-aws'));

                        wpm2awsWpRedirectAndExit();
//                        exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
                    }
                }

                // Reset Log File
                wpm2awsLogResetAll();

                $this->handle_all('POST_process-zipped-fs-uploader-all');

                wpm2awsWpRedirectAndExit();
//                exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
            } elseif (isset($_POST['wpm2aws-process-zipped-fs-uploader-all-restart'])) {
                $this->handle_restart();

                wpm2awsWpRedirectAndExit();
//                exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
            } else {
                // wp_die('bad post');
                wp_die(print_r($_POST));
            }
        } elseif ((isset($_GET['action']) && $_GET['action'] === 'wp_wpm2aws-zipped-fs-uploader-once') && isset($_GET['nonce'])) {
            // echo "<br>In Get Single<br>";
            $this->handle_single('GET_zipped-fs-uploader-once');
        } elseif ((isset($_GET['action']) && $_GET['action'] === 'wp_wpm2aws-zipped-fs-uploader-all') && isset($_GET['nonce'])) {
            // echo "<br>In Get All<br>";
            // $this->handle_all();
            return;
        } else {
            wp_die('Invalid Access');
        }

        print_r($_GET);
    }

    /**
     * Handle single
     */
    protected function handle_single($param)
    {
        // echo "<br>Handle Single";
        $names = $this->get_names($param);
        $rand  = array_rand($names, 1);
        $name  = $names[ $rand ];
        // echo "<br>Name: " . $name;
        $this->process_single->data(array( 'name' => $name ))->dispatch();
        // echo "<br>";
        // print_r($result);
        // echo "<br>Processed";
        $this->process_single->logProcessFinished();
    }

    /**
     * Handle all
     */
    protected function handle_all($param)
    {
        // echo "<br>Handle All";
        // $names = $this->get_names($param);

        wpm2awsAddUpdateOptions('wpm2aws_zipped_fs_upload_errors', array());
        $fullFilePath = '';

        try {
            // Check File Exists
            // $fullFilePath = get_option('wpm2aws-aws-s3-upload-directory-path') . '/' . get_option('wpm2aws-aws-s3-upload-directory-name');

            $fullFilePath .= get_option('wpm2aws-aws-s3-upload-directory-path');
            $pathSeparator = '/';
            if (strpos($fullFilePath, '\\') !== false) {
                $pathSeparator = '\\';
            }
            $fullFilePath .= $pathSeparator;
            $fullFilePath .= get_option('wpm2aws-aws-s3-upload-directory-name');

            if (!is_dir($fullFilePath)) {
                wpm2awsLogAction('Error! Directory does not exist: ' . $fullFilePath);
                wpm2awsLogRAction('wpm2aws_fs_uploader_error', 'Error! Directory does not exist: ' . $fullFilePath);
                throw new Exception('Error! Directory does not exist:<br><br>' . $fullFilePath);
                return false;
            }
        } catch (Exception $e) {
            wpm2awsLogAction('Directory Zipped FS Upload Failed! Error Mgs: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws_fs_uploader_error', 'Directory Zipped FS Upload Failed! Error Mgs: ' . $e->getMessage());
            return false;
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('Source Path: ' . $fullFilePath);
        }

        $baseLocation = 'plugins' . DIRECTORY_SEPARATOR . 'wp-migrate-2-aws' . DIRECTORY_SEPARATOR;

        $importScriptFile = $baseLocation . 'libraries' . DIRECTORY_SEPARATOR . 'unzip' . DIRECTORY_SEPARATOR . 'zipIimporter_dlfs.php';

        $importLogFile = $baseLocation . 'inc' . DIRECTORY_SEPARATOR . 'zipLog.txt';

        $names = array(
                'plugins\wp-migrate-2-aws__launch' => $importScriptFile,
                'plugins\zippedFilesLog' => $importLogFile,
                'plugins\zippedFiles' => 'plugins' . DIRECTORY_SEPARATOR . 'wpm2aws-zips.zip',
        );


        // Deliberate Failure Files
        if (defined('WPM2AWS_TEST_FAILURE')) {
            $names['intentional_fail'] = 'intentional_fail.php';
            $names['intentional_fail_2'] = 'plugins/fail/intentional_fail_2.wpm2awsZipDir';
        }


        wpm2awsAddUpdateOptions('wpm2aws_zipped_fs_upload_failures', '');
        wpm2awsAddUpdateOptions('wpm2aws_zipped_fs_upload_started', true);
        wpm2awsAddUpdateOptions('wpm2aws_zipped_fs_upload_complete', false);

        $total = 0;
        $complete = 0;
        if (!empty($names)) {
            $total = count($names);
        }
        wpm2awsAddUpdateOptions(
            'wpm2aws_zipped_fs_upload_counter',
            array(
                'total' => $total,
                'complete' => $complete
            )
        );

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            foreach ($names as $name) {
                wpm2awsLogAction("Processing File: " . $name);
            }
        }

        foreach ($names as $name) {
            $this->process_all->push_to_queue($name);
        }

        wpm2awsLogAction('Zipped FS Upload Process Started - ' . date("d-m-Y @ H:i:s"));
        wpm2awsLogRAction('wpm2aws_fs_uploader_started', 'Zipped FS Upload Process Started - ' . date("d-m-Y @ H:i:s"));
        // wp_die(print_r($this->process_all));
        $this->process_all->save()->dispatch();
    }


    protected function handle_restart()
    {
        // Get current batch
        global $wpdb;

        $sql = "SELECT option_id, option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE 'wp_wpm2aws-zipped-fs-uploader-all_batch_%'";

        $existingBatches = $wpdb->get_results($sql, OBJECT);
        $currentBatch = array();
        if (!empty($existingBatches)) {
            foreach ($existingBatches as $batchIx => $batchDetails) {
                if (empty($currentBatch)) {
                    $currentBatch = $existingBatches[$batchIx];
                    $currentBatchOptionName = $batchDetails->option_name;
                } else {
                    if ($batchDetails->option_id > $currentBatch->option_id) {
                        $previousBatchOptionName = $currentBatchOptionName;

                        $currentBatch = $existingBatches[$batchIx];
                        $currentBatchOptionName = $batchDetails->option_name;

                        delete_option($previousBatchOptionName);
                    } else {
                        $optionName = $batchDetails->option_name;
                        delete_option($optionName);
                    }
                }
            }
        }

        // put items into array
        $items = unserialize($currentBatch->option_value);

        // push itmes to queue
        foreach ($items as $item) {
            $this->process_all->push_to_queue($item);
        }

        // delete old batch
        delete_option($currentBatchOptionName);

        // displatch new queue
        wpm2awsLogAction('Zipped FS Upload Process Re-Started - ' . date("d-m-Y @ H:i:s"));
        wpm2awsLogRAction('wpm2aws_fs_uploader_re_started', 'Zipped FS Upload Process Re-Started - ' . date("d-m-Y @ H:i:s"));
        $this->process_all->save()->dispatch();

        // return;
    }


    private function listFolderFiles($dir, $parentDir = '')
    {
        $files = array();
        $counter = 0;

        $basePath = get_option('wpm2aws-aws-s3-upload-directory-path');
        $pathSeparator = '/';
        if (strpos($basePath, '\\') !== false) {
            $pathSeparator = '\\';
        }

        // $pluginsDir = str_replace(DIRECTORY_SEPARATOR . WPM2AWS_PLUGIN_NAME, '', WPM2AWS_PLUGIN_DIR);
        // $pluginsDirParentEnd = strrpos($pluginsDir, DIRECTORY_SEPARATOR);
        // $pluginsDirParentStart = strrpos($pluginsDir, DIRECTORY_SEPARATOR, $pluginsDirParentEnd - strlen($pluginsDir) - 1);
        // $pluginsDirParent = substr($pluginsDir, $pluginsDirParentStart);

        $pluginsDir = str_replace(DIRECTORY_SEPARATOR . WPM2AWS_PLUGIN_NAME, '', WPM2AWS_PLUGIN_DIR);
        //wpm2awsLogAction('Plugins Directory: ' . $pluginsDir);
        $uploadsPath = $dir;
        $pluginsDirParent = str_replace($uploadsPath, '', $pluginsDir);

        //wpm2awsLogAction('Uploads Path: ' . $uploadsPath);
        //wpm2awsLogAction('Plugins Parent Dir: ' . $pluginsDirParent);


        if (DIRECTORY_SEPARATOR === substr($pluginsDirParent, 0, 1)) {
            $pluginsDirParent = substr($pluginsDirParent, 1);
        }

        if (DIRECTORY_SEPARATOR === substr($pluginsDirParent, (strlen($pluginsDirParent) - 1), 1)) {
            $pluginsDirParent = substr($pluginsDirParent, 0, (strlen($pluginsDirParent) - 1));
        }


        $strippedParentDir = str_replace($uploadsPath, '', $parentDir);
        if (DIRECTORY_SEPARATOR === substr($strippedParentDir, 0, 1)) {
            $strippedParentDir = substr($strippedParentDir, 1);
        }

        if (DIRECTORY_SEPARATOR === substr($strippedParentDir, (strlen($strippedParentDir) - 1), 1)) {
            $strippedParentDir = substr($strippedParentDir, 0, (strlen($strippedParentDir) - 1));
        }

        $wpm2aws_exclude_wp_core_themes = array(
            'twentyfifteen',
            'twentysixteen',
            'twentyseventeen',
            'twentyeighteen',
            'twentynineteen',
            'twentytwenty'
        );

        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                if ($fileInfo->isDir()) {
                    if (
                        strpos($dir, $pluginsDir) !== false ||
                        // in_array($fileInfo->getFilename(), WPM2AWS_EXCLUDE_WP_CORE_THEMES)
                        in_array($fileInfo->getFilename(), $wpm2aws_exclude_wp_core_themes)
                    ) {
                        $files[$fileInfo->getFilename()] = $parentDir . $pathSeparator . $fileInfo->getFilename() . '.wpm2awsZipDir';
                    } else {
                        if (strpos($fileInfo->getPathname(), '.git') === false) {
                            $indexRef = $fileInfo->getFilename();
                            if ($parentDir) {
                                $indexRef = $parentDir . $pathSeparator . $fileInfo->getFilename();
                            }
                            $files[$fileInfo->getPathname()] = $this->listFolderFiles($fileInfo->getPathname(), $indexRef);
                        }
                    }
                } else {
                    $indexRef = $counter;
                    $filePath = '';
                    if ($parentDir) {
                        $counter++;
                        $indexRef = $parentDir . '__' . $counter;
                        $filePath = $parentDir . $pathSeparator;
                    }
                    $files[$indexRef] = $filePath . $fileInfo->getFilename();
                }
                $counter++;
            }
        }

        foreach ($files as $fileIx => $fileData) {
            if (is_array($fileData)) {
                $files = array_merge($files, $fileData);
                unset($files[$fileIx]);
            }
        }
        return $files;
    }
}
