<?php

class WPM2AWS_Zipper
{
    /**
     * @var WPM2AWS_RunRequestZip
     */
    protected $process_single;

    /**
     * @var WPM2AWS_RunProcessZip
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
        require_once WPM2AWS_PLUGIN_DIR . '/admin/abstracts/uploader/async-requests/runRequestZip.class.php';

        require_once WPM2AWS_PLUGIN_DIR . '/admin/abstracts/uploader/background-processes/runProcessZip.class.php';

        $this->processsingle = new WPM2AWS_RunRequestZip();
        $this->process_all = new WPM2AWS_RunProcessZip();
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

            if (!isset($_POST['wpm2aws-process-fszipper-all-submit']) && !isset($_POST['wpm2aws-process-fszipper-all-restart'])) {
                wp_die('Invalid Post');
                return;
            }



            if (! wp_verify_nonce($_POST['_wpnonce'], 'wpm2aws-run-fs-zip')) {
                print_r($_POST);
                wp_die('Invalid Nonce');
                return;
            }
            // echo "<br>Post:";
            // print_r($_POST);
            if (isset($_POST['wpm2aws-process-fszipper-once-submit'])) {
                if (defined('WPM2AWS_TESTING_BACKGROUND_PROCESS')) {
                    $postParams = json_encode($_POST);
                    wpm2awsLogAction('Background Remote Call (POST): ' . $postParams );
                }
                // echo "<br>Process Once";
                $this->handle_single('POST_process-fszipper-once');
            } elseif (isset($_POST['wpm2aws-process-fszipper-all-submit'])) {
                // echo "<br>Process All";

                // Reset Log File
                wpm2awsLogResetAll();


                $this->handle_all('POST_process-fszipper-all');

                wpm2awsWpRedirectAndExit();
//                exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
            } elseif (isset($_POST['wpm2aws-process-fszipper-all-restart'])) {
                $this->handle_restart();

                wpm2awsWpRedirectAndExit();
//                exit(wp_redirect(admin_url('/admin.php?page=wpm2aws')));
            } else {
                wp_die(print_r($_POST));
            }
        } elseif ((isset($_GET['action']) && $_GET['action'] === 'wp_wpm2aws-fszipper-once') && isset($_GET['nonce'])) {
            // echo "<br>In Get Single<br>";
            if (defined('WPM2AWS_TESTING_BACKGROUND_PROCESS')) {
                $getParams = json_encode($_GET);
                wpm2awsLogAction('Background Remote Call (GET 1): ' . $getParams );
            }
            $this->handle_single('GET_fszipper-once');
        } elseif ((isset($_GET['action']) && $_GET['action'] === 'wp_wpm2aws-fszipper-all') && isset($_GET['nonce'])) {
            // echo "<br>In Get All<br>";
            // $this->handle_all();
            if (defined('WPM2AWS_TESTING_BACKGROUND_PROCESS')) {
                $getParams = json_encode($_GET);
                $server = json_encode($_SERVER);
                wpm2awsLogAction('Background Remote Call (GET 2): ' . $server . ' | ' . $getParams );
            }
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

        wpm2awsAddUpdateOptions('wpm2aws_fszipper_errors', array());
        $fullFilePath = '';

        try {
            // Check File Exists
            // $fullFilePath = get_option('wpm2aws-aws-s3-download-directory-path') . '/' . get_option('wpm2aws-aws-s3-download-directory-name');

            $fullFilePath .= get_option('wpm2aws-aws-s3-upload-directory-path');
            $pathSeparator = DIRECTORY_SEPARATOR;

            $fullFilePath .= $pathSeparator;
            $fullFilePath .= get_option('wpm2aws-aws-s3-upload-directory-name');

            if (!is_dir($fullFilePath)) {
                wpm2awsLogAction('Error! Directory does not exist: ' . $fullFilePath);
                wpm2awsLogRAction('wpm2aws_fszipper_error', 'Error! Directory does not exist: ' . $fullFilePath);
                throw new Exception('Error! Directory does not exist:<br><br>' . $fullFilePath);
                return false;
            }
        } catch (Exception $e) {
            wpm2awsLogAction('Directory Zipping Failed! Error Mgs: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws_fszipper_error', 'Directory Zipping Failed! Error Mgs: ' . $e->getMessage());
            return false;
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('Source Path: ' . $fullFilePath);
        }

        $names = $this->listDirectoriesForZip($fullFilePath);

        // New Addition
        // Remove Excluded DIRS
        // From Zipping Process
        if (false !== get_option('wpm2aws_exclude_dirs_from_zip_process') && '' !== get_option('wpm2aws_exclude_dirs_from_zip_process')) {
            $excludedDirs = get_option('wpm2aws_exclude_dirs_from_zip_process');
            if (!empty($excludedDirs) && is_array($excludedDirs)) {
                foreach ($excludedDirs as $exdIx => $exdName) {
                    if (isset($names[$exdName])) {
                        unset($names[$exdName]);
                    }
                }
            }
        }
        // wp_die(print_r($names));

        // add-in Launch Script
        // $names['plugins\wp-migrate-2-aws__launch'] = 'plugins\wp-migrate-2-aws\libraries\unzip\zipIimporter_fs.php';
        $importScriptFile = '';
        $importScriptFile .= 'plugins';
        $importScriptFile .= DIRECTORY_SEPARATOR;
        $importScriptFile .= 'wp-migrate-2-aws';
        $importScriptFile .= DIRECTORY_SEPARATOR;
        $importScriptFile .= 'libraries';
        $importScriptFile .= DIRECTORY_SEPARATOR;
        $importScriptFile .= 'unzip';
        $importScriptFile .= DIRECTORY_SEPARATOR;
        $importScriptFile .= 'zipIimporter_fs.php';

        // $names['plugins\wp-migrate-2-aws__launch'] = $importScriptFile;

        // Deliberate Failure Files
        if (defined('WPM2AWS_TEST_FAILURE')) {
            $names['intentional_fail'] = 'intentional_fail.php';
            $names['intentional_fail_2'] = 'plugins/fail/intentional_fail_2.wpm2awsZipDir';
        }

        wpm2awsAddUpdateOptions('wpm2aws_fszipper_failures', '');
        wpm2awsAddUpdateOptions('wpm2aws_fszipper_started', true);
        wpm2awsAddUpdateOptions('wpm2aws_fszipper_complete', false);

        $total = 0;
        $complete = 0;
        if (!empty($names)) {
            $total = count($names);
        }
        wpm2awsAddUpdateOptions(
            'wpm2aws_fszipper_counter',
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

        // wp_die(print_r($names));
        foreach ($names as $name) {
            $this->process_all->push_to_queue($name);
        }

        wpm2awsLogAction('FS Zipping Process Started - ' . date("d-m-Y @ H:i:s"));
        wpm2awsLogRAction('wpm2aws_fszipper_started', 'FS Zipping Process Started - ' . date("d-m-Y @ H:i:s"));
        $this->process_all->save()->dispatch();
    }

    protected function handle_restart()
    {

        // Get current batch
        global $wpdb;

        $sql = "SELECT option_id, option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE 'wp_wpm2aws-fszipper-all_batch_%'";

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

        // dispatch new queue
        wpm2awsLogAction('FS Zipping Process Re-Started - ' . date("d-m-Y @ H:i:s"));
        wpm2awsLogRAction('wpm2aws_fszipper_re_start', 'FS Zipping Process Re-Started - ' . date("d-m-Y @ H:i:s"));
        $this->process_all->save()->dispatch();

        // return;
    }

    private function listDirectoriesForZip($dir, $parentDir = '')
    {
        $files = array();
        $counter = 0;
        $getSubDirs = array('themes', 'uploads', 'plugins',);
        $getSubSubDirs = array('uploads');
        $subSubDirList = array();

        $basePath = get_option('wpm2aws-aws-s3-upload-directory-path');
        $pathSeparator = DIRECTORY_SEPARATOR;

        $pluginsDir = str_replace(DIRECTORY_SEPARATOR . WPM2AWS_PLUGIN_NAME, '', WPM2AWS_PLUGIN_DIR);
        $zipsPath = $dir;
        $pluginsDirParent = str_replace($zipsPath, '', $pluginsDir);

        if (DIRECTORY_SEPARATOR === substr($pluginsDirParent, 0, 1)) {
            $pluginsDirParent = substr($pluginsDirParent, 1);
        }

        if (DIRECTORY_SEPARATOR === substr($pluginsDirParent, (strlen($pluginsDirParent) - 1), 1)) {
            $pluginsDirParent = substr($pluginsDirParent, 0, (strlen($pluginsDirParent) - 1));
        }

        $strippedParentDir = str_replace($zipsPath, '', $parentDir);
        if (DIRECTORY_SEPARATOR === substr($strippedParentDir, 0, 1)) {
            $strippedParentDir = substr($strippedParentDir, 1);
        }

        if (DIRECTORY_SEPARATOR === substr($strippedParentDir, (strlen($strippedParentDir) - 1), 1)) {
            $strippedParentDir = substr($strippedParentDir, 0, (strlen($strippedParentDir) - 1));
        }

        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                if ($fileInfo->isDir()) {
                    $indexRef = $fileInfo->getFilename();
                    if ($parentDir) {
                        $indexRef = $parentDir . $pathSeparator . $fileInfo->getFilename();
                    }

                    $files[$indexRef] = $parentDir . $pathSeparator . $fileInfo->getFilename() . '.wpm2awsZipDir';


                    // if ($fileInfo->getFilename() === 'uploads') {

                    // }

                    if (in_array($fileInfo->getFilename(), $getSubDirs)) {
                        if (strpos($fileInfo->getPathname(), '.git') === false) {
                            $indexRef = $fileInfo->getFilename();
                            $files[$indexRef . '_children'] = $this->listDirectoriesForZip($fileInfo->getPathname(), $indexRef);
                        }
                    }

                    if (!empty($parentDir) && in_array($strippedParentDir, $getSubSubDirs)) {
                        $indexRef = $parentDir . $pathSeparator . $fileInfo->getFilename();

                        $files[$indexRef . '_sub_children'] = $this->listDirectoriesForZip($fileInfo->getPathname(), $indexRef);
                    }
                }
                $counter++;
            // } else {
            //     wpm2awsLogAction('Dot File: ' . $fileInfo->getFilename());
            }
        }

        $noSubDirs = array();
        // return $files;
        foreach ($files as $fileIx => $fileData) {
            if (is_array($fileData)) {

                // if (!empty($fileData)) {
                if (
                    strpos($fileIx, '_children') !== false &&
                    in_array(str_replace('_children', '', $fileIx), $getSubSubDirs, true)
                ) {
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction('Child Dir: ' . $fileIx);
                    }
                    foreach ($fileData as $fileDataIx => $fileDataData) {
                        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                            wpm2awsLogAction('Child Sub Dir: ' . $fileDataIx);
                        }
                        // wpm2awsLogAction('Child Sub Dir Base name: ' . substr($fileDataIx, (strpos($fileDataIx, DIRECTORY_SEPARATOR) + 1) ));
                        // if (empty($fileDataData)) {
                        //     wpm2awsLogAction('Empty Child Dir (1): ' . $fileDataIx);
                        // }

                        if (substr_count($fileDataIx, DIRECTORY_SEPARATOR) === 1) {
                            $baseChildName = substr($fileDataIx, (strpos($fileDataIx, DIRECTORY_SEPARATOR) + 1));
                            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                                wpm2awsLogAction('Child Sub Dir Base name: ' . $baseChildName);
                            }

                            // New Condition added - only unset if empty
                            if (is_numeric($baseChildName)) {
                                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                                    wpm2awsLogAction('Unsetting: ' . $fileDataIx);
                                }
                                // wpm2awsLogAction('Unsetting Data: ' . json_encode($fileDataData));
                                unset($fileData[$fileDataIx]);
                            }
                        }
                    }
                        // } else {
                    //     wpm2awsLogAction('Not meeting Child Checks: ' . $fileIx);
                    }

                    // if (empty($fileData)) {
                    //     wpm2awsLogAction('Empty Child Dir (2): ' . $fileIx);
                    // }

                    // New Condition added - only unset if empty
                    // if (!empty($fileData)) {
                    $files = array_merge($files, $fileData);

                    unset($files[$fileIx]);
                    // } else {
                    //     wpm2awsLogAction('Empty Ix: ' . $fileIx);
                    //     $fileData = array($fileIx => $fileIx);
                    //     wp_die(print_r($fileData));
                    //     array_push($noSubDirs, $fileIx);
                    //     // $files = array_merge($files, array($fileIx . 'blllllllaaaa' => $fileIx));
                    //     // unset($files[$fileIx]);
                    //     // $files['test'] = 'test';
                    // }
                // } else {
                //     wpm2awsLogAction('Empty  Array: ' . $fileIx);
                // }
            } else {
                // wpm2awsLogAction('Not Array: ' . $fileIx);
            }
        }
        // return $files;

        foreach ($getSubDirs as $subDirIx => $subDirName) {
            if (isset($files[$subDirName])) {
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('Unsetting (3): ' . $subDirName);
                }
                unset($files[$subDirName]);
            }
        }
        return $files;
    }
}
