<?php

trait WPM2AWS_Logger
{

    /**
     * Really long running process
     *
     * @return int
     */
    public function really_long_running_task()
    {
        return usleep(10000);
        // return sleep( 0.1 );
    }
    
    public function backgroundUploadToS3($fileOrDir, $zippedFsUpload = false)
    {
        try {
            // Check DIR Exists
            $basePath = '';
            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-path');

            $pathSeparator = '/';
            if (strpos($basePath, '\\') !== false) {
                $pathSeparator = '\\';
            }

            $pathSeparator = DIRECTORY_SEPARATOR;
            $basePath .= $pathSeparator;


            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-name');
            if (!is_dir($basePath)) {
                $errorMsg = 'Error! Directory does not exist:<br><br>' . $basePath;
                wpm2awsLogAction($errorMsg);
                // $this->log($errorMsg);
                $errors = get_option('wpm2aws_upload_errors');
                $errors[] = $errorMsg;
                wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
            }
        } catch (Exception $e) {
            $errorMsg = 'Directory Upload Failed! Error Mgs: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
            // $this->log($errorMsg);
            $errors = get_option('wpm2aws_upload_errors');
            $errors[] = $errorMsg;
            wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);

            // better error handle
            // return 200;
            return false;
        }
        
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: Currently Processing File: " . $fileOrDir);
        }

        try {
            $awsApi = new WPM2AWS_ApiGlobal();
        } catch (Exception $e) {
            $errorMsg = 'Error: cant set class: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
        }
        
        
        if (strpos($fileOrDir, '.wpm2awsZipDir') === false) {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Debug: Processing File (not directory): ' . $fileOrDir);
            }

            $uploadFileOrDir = '';
            if (strpos($fileOrDir, 'wp-migrate-2-aws') !== false && strpos($fileOrDir, 'zipIimporter_fs.php') !== false) {
                $uploadFileOrDir = $fileOrDir;
                $uploadFileOrDir = str_replace('wp-migrate-2-aws', 'wp-migrate-2-aws-launcher', $uploadFileOrDir);
            }



            // ToDo
            // If File bigger thatn 0.5MB
            // Zip the file
            // Change the filename to "xxxx.zip"
            $fileName = $basePath . $pathSeparator . $fileOrDir;

            // The function `filesize` is cached, therefore we need to clear the cache before running.
            \clearstatcache(true, $fileName);

            $fileSize = \filesize($fileName);

            // If File is bigger than 1MB
            if ($fileSize >= 1000000) {
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('File Size: ' . $fileSize);
                }

                
                // try {

                //     $transferred = $awsApi->zipDirectoryAndUpload($basePath, $fileOrDir);

                //     if (defined('WPM2AWS_DEV')) {
                //         wpm2awsLogAction("Debug Returning Transferred: " . $transferred);
                //     }


                //     // $upload = $awsApi->backgroundMultiPartUploadFileToBucket($basePath, $fileOrDir);
                //     // if (defined('WPM2AWS_DEBUG')) {
                //     //     wpm2awsLogAction("DebugReturning Upload: " . $upload);
                //     // }
                //     // return $upload;
                // } catch (Exception $e) {
                //     $errorMsg = 'Error: No Array: ' . $e->getMessage();
                //     wpm2awsLogAction($errorMsg);
                //     // $this->log($errorMsg);
                //     $errors = get_option('wpm2aws_upload_errors');
                //     $errors[] = $errorMsg;
                //     wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                //     return false;
                // }
            } 
            // else {

                // Confirm that file exists before processing
                if (!file_exists($basePath . $pathSeparator . $fileOrDir)) {
                    wpm2awsLogAction("Error! File does Not exists: " . $basePath . $pathSeparator . $fileOrDir);
                    return $status = '404';
                }

                try {
                    // Log the start-time
                    wpm2awsAddUpdateOptions('wpm2aws_upload_process_start_time', time());

                    // Run the background process
                    $upload = $awsApi->backgroundUploadFileToBucket($basePath, $fileOrDir, $uploadFileOrDir, $zippedFsUpload);
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("DebugReturning Upload: " . $upload);
                    }
                    return $upload;
                } catch (Exception $e) {
                    $errorMsg = 'Error: No Array: ' . $e->getMessage();
                    wpm2awsLogAction($errorMsg);
                    // $this->log($errorMsg);
                    $errors = get_option('wpm2aws_upload_errors');
                    $errors[] = $errorMsg;
                    wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                    return false;
                }
            // }

            
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: End: Background Process (1)");
            }
            return false;
        } else {
       
        // if (is_array($fileOrDir)) {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: File / Dir listing IS a Directory");
            }

            
            
            // $trimmedFileName = str_replace('.dir', '', $fileOrDir);
            $trimmedFileName = str_replace('.wpm2awsZipDir', '', $fileOrDir);
            // $zippedFile = $awsApi->gZipAndTransferToS3($basePath, $trimmedFileName);
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Trimmed File Name: " . $trimmedFileName);
            }

            // If Uploading this plugin,
            // then upload the launch sequence separate from Zipping
            // if (strpos($fileOrDir, 'wp-migrate-2-aws') !== false) {
            //     $launchFilePath =  $trimmedFileName . $pathSeparator . 'libraries' . $pathSeparator . 'unzip' . $pathSeparator . 'zipIimporter_fs.php';
            //     $launchFilePathDestination =  $trimmedFileName . '-launcher' . $pathSeparator . 'libraries' . $pathSeparator . 'unzip' . $pathSeparator . 'zipIimporter_fs.php';

            //     try {
            //         $uploadLaunch = $awsApi->backgroundUploadFileToBucket($basePath, $launchFilePath);
            //     } catch (Exception $e) {
            //         $errorMsg = 'Error: No Array: ' . $e->getMessage();
            //         wpm2awsLogAction($errorMsg);
            //         // $this->log($errorMsg);
            //         $errors = get_option('wpm2aws_upload_errors');
            //         $errors[] = $errorMsg;
            //         wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
            //         return false;
            //     }
            // }

            $wpm2aws_exclude_wp_core_themes = array(
                'twentyfifteen',
                'twentysixteen',
                'twentyseventeen',
                'twentyeighteen',
                'twentynineteen',
                'twentytwenty'
            );


            // if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), WPM2AWS_EXCLUDE_WP_CORE_THEMES)) {
            if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), $wpm2aws_exclude_wp_core_themes)) {

                
               
                // *********************************
                // *** New Addition - 24-02-2020 ***
                // *********************************
                // $pathSeparator = '/';
                // if (strpos($basePath, '\\') !== false) {
                //     $pathSeparator = '\\';
                // }
                $pathSeparator = DIRECTORY_SEPARATOR;
                $fullDirectoryPath = $basePath . $pathSeparator . $trimmedFileName;

                // Confirm that file exists before processing
                if (!file_exists($fullDirectoryPath)) {
                    wpm2awsLogAction("Error! File (zip) does Not exists: " . $fullDirectoryPath);
                    return $status = '404';
                }


                // try {
                //     // Zip the Directory
                //     $path = wpm2aws_compress_directory($fullDirectoryPath)
                // } catch (Exception $e) {
                //     $errorMsg = "Error! Failed to Transfer (zipped) Directory: " . $e->getMessage();
                //     if (defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                //         wpm2awsLogAction("Error Zipping File: " . $trimmedFileName);
                //     }
                //     wpm2awsLogAction($errorMsg);
                //     $errors = get_option('wpm2aws_upload_errors');
                //     $errors[] = $errorMsg;
                //     wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                //     return false;
                // }

                try {
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Running Zip & Upload");
                    }
                    // $transferred = $awsApi->gZipAndTransferToS3($basePath, $trimmedFileName);
                    
                    // Log the start-time
                    wpm2awsAddUpdateOptions('wpm2aws_upload_process_start_time', time());

                    // Run the background process
                    $transferred = $awsApi->zipDirectoryAndUpload($basePath, $trimmedFileName);

                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Debug Returning Transferred: " . $transferred);
                    }
                    return $transferred;
                } catch (Exception $e) {
                    $errorMsg = "Error! Failed to Transfer Directory: " . $e->getMessage();
                    wpm2awsLogAction($errorMsg);
                    $errors = get_option('wpm2aws_upload_errors');
                    $errors[] = $errorMsg;
                    wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                    return false;
                }


                // *** END: New Addition - 24-02-2020 ***


                // try {
                //     $transferred = $awsApi->uploadSingleZipToBucket($basePath, '', $trimmedFileName);
                //     if (defined('WPM2AWS_DEBUG')) {
                //         wpm2awsLogAction("Debug Returning Transferred: " . $transferred);
                //     }
                //     return $transferred;
                // } catch (Exception $e) {
                //     $errorMsg = "Error! Failed to Transfer Directory: " . $e->getMessage();
                //     wpm2awsLogAction($errorMsg);
                //     $errors = get_option('wpm2aws_upload_errors');
                //     $errors[] = $errorMsg;
                //     wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                //     return false;
                // }
            } else {
                wpm2awsLogAction("Intentionally Excluded From Upload: " . $trimmedFileName);
                return $status = '200';
            }

            // foreach ($fileOrDir as $parent => $childFiles) {
                // wpm2awsLogAction('Processing DIR: ' . $parent);
                // $awsApi->backgroundTransferFullDirToS3($basePath, $parent);
            // }


        // } else {
        //     if (defined('WPM2AWS_DEBUG')) {
        //         wpm2awsLogAction("Debug: File / Dir listing is not an array");
        //     }
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: End: Background Process (2)");
        }

        return false;
    }

    
    public function backgroundDownloadToLocal($fileOrDir)
    {
        wpm2awsLogAction('In Background Dl Local');
        
        try {
            // Check DIR Exists
            $basePath = '';
            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-path');

            $pathSeparator = DIRECTORY_SEPARATOR;
            $basePath .= $pathSeparator;


            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-name');
            if (!is_dir($basePath)) {
                $errorMsg = 'Error! Directory does not exist:<br><br>' . $basePath;
                wpm2awsLogAction($errorMsg);
                // $this->log($errorMsg);
                $errors = get_option('wpm2aws_download_errors');
                $errors[] = $errorMsg;
                wpm2awsAddUpdateOptions('wpm2aws_download_errors', $errors);
            }
        } catch (Exception $e) {
            $errorMsg = 'Directory Upload Failed! Error Mgs: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
            // $this->log($errorMsg);
            $errors = get_option('wpm2aws_download_errors');
            $errors[] = $errorMsg;
            wpm2awsAddUpdateOptions('wpm2aws_download_errors', $errors);

            // return 200;
            return false;
        }
        
        wpm2awsLogAction('Test 1');

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: Currently Processing File: " . $fileOrDir);
        }

        try {
            $awsApi = new WPM2AWS_ApiGlobal();
        } catch (Exception $e) {
            $errorMsg = 'Error: cant set class: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
        }
        
        wpm2awsLogAction('Test 2');

        if (strpos($fileOrDir, '.wpm2awsZipDir') === false) {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Debug: Processing File (not directory): ' . $fileOrDir);
            }

            $downloadFileOrDir = '';
            if (strpos($fileOrDir, 'wp-migrate-2-aws') !== false && strpos($fileOrDir, 'zipIimporter_fs.php') !== false) {
                $downloadFileOrDir = $fileOrDir;
                $downloadFileOrDir = str_replace('wp-migrate-2-aws', 'wp-migrate-2-aws-launcher', $downloadFileOrDir);
            }



            // ToDo
            // If File bigger thatn 0.5MB
            // Zip the file
            // Change the filename to "xxxx.zip"
            $fileName = $basePath . $pathSeparator . $fileOrDir;

            // The function `filesize` is cached, therefore we need to clear the cache before running.
            \clearstatcache(true, $fileName);

            $fileSize = \filesize($fileName);

            // If File is bigger than 1MB
            if ($fileSize >= 1000000) {
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('File Size: ' . $fileSize);
                }
            } 

            // Confirm that file exists before processing
            if (!file_exists($basePath . $pathSeparator . $fileOrDir)) {
                wpm2awsLogAction("Error! File does Not exists: " . $basePath . $pathSeparator . $fileOrDir);
                return $status = '404';
            }

            wpm2awsLogAction('Test 3'); 
            try {
                // Log the start-time
                wpm2awsAddUpdateOptions('wpm2aws_download_process_start_time', time());

                // Run the background process
                // $download = $awsApi->backgroundUploadFileToBucket($basePath, $fileOrDir, $uploadFileOrDir);
                $download = $awsApi->backgroundDownloadFileToLocal($basePath, $fileOrDir, $downloadFileOrDir);
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction("DebugReturning Download: " . $download);
                }
                wpm2awsLogAction("Debug: Returning Download: " . $download);
                return $download;
            } catch (Exception $e) {
                $errorMsg = 'Error: No Array: ' . $e->getMessage();
                wpm2awsLogAction($errorMsg);
                // $this->log($errorMsg);
                $errors = get_option('wpm2aws_upload_errors');
                $errors[] = $errorMsg;
                wpm2awsAddUpdateOptions('wpm2aws_upload_errors', $errors);
                return false;
            }

            wpm2awsLogAction('Test 4');
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: End: Background Process (1)");
            }
            return false;
        } else {
            wpm2awsLogAction('Test 5');
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: File / Dir listing IS a Directory");
            }

            $trimmedFileName = str_replace('.wpm2awsZipDir', '', $fileOrDir);
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Trimmed File Name: " . $trimmedFileName);
            }

            $wpm2aws_exclude_wp_core_themes = array(
                'twentyfifteen',
                'twentysixteen',
                'twentyseventeen',
                'twentyeighteen',
                'twentynineteen',
                'twentytwenty'
            );

            // if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), WPM2AWS_EXCLUDE_WP_CORE_THEMES)) {
            if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), $wpm2aws_exclude_wp_core_themes)) {
                
               
                // *********************************
                // *** New Addition - 24-02-2020 ***
                // *********************************
                $pathSeparator = DIRECTORY_SEPARATOR;
                    
                $fullDirectoryPath = $basePath . $pathSeparator . $trimmedFileName;

                // Confirm that file exists before processing
                if (!file_exists($fullDirectoryPath)) {
                    wpm2awsLogAction("Error! File (zip) does Not exists: " . $fullDirectoryPath);
                    return $status = '404';
                }

                try {
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Running Zip & Download");
                    }
                    // $transferred = $awsApi->gZipAndTransferToS3($basePath, $trimmedFileName);
                    
                    // Log the start-time
                    wpm2awsAddUpdateOptions('wpm2aws_download_process_start_time', time());

                    // Run the background process
                    $transferred = $awsApi->zipDirectoryAndDownload($basePath, $trimmedFileName);
                    wpm2awsLogAction("Debug Returning Transferred: " . $transferred);
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Debug Returning Transferred: " . $transferred);
                    }
                    return $transferred;
                } catch (Exception $e) {
                    $errorMsg = "Error! Failed to Transfer Directory: " . $e->getMessage();
                    wpm2awsLogAction($errorMsg);
                    $errors = get_option('wpm2aws_download_errors');
                    $errors[] = $errorMsg;
                    wpm2awsAddUpdateOptions('wpm2aws_download_errors', $errors);
                    return false;
                }


                // *** END: New Addition - 24-02-2020 ***
                return false;
            } else {
                wpm2awsLogAction("Intentionally Excluded From Download: " . $trimmedFileName);
                return $status = '200';
            }
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: End: Background Process (2)");
        }

        return false;
    }

    
    public function backgroundZipFsToLocal($fileOrDir)
    {
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('In Background Zip Fs Local');
        }
        
        try {
            // Check DIR Exists
            $basePath = '';
            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-path');

            $pathSeparator = DIRECTORY_SEPARATOR;
            $basePath .= $pathSeparator;


            $basePath .= get_option('wpm2aws-aws-s3-upload-directory-name');
            if (!is_dir($basePath)) {
                $errorMsg = 'Error! Directory does not exist:<br><br>' . $basePath;
                wpm2awsLogAction($errorMsg);
                $errors = get_option('wpm2aws_fszipper_errors');
                $errors[] = $errorMsg;
                wpm2awsAddUpdateOptions('wpm2aws_fszipper_errors', $errors);
            }
        } catch (Exception $e) {
            $errorMsg = 'Directory Upload Failed! Error Mgs: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
            $errors = get_option('wpm2aws_fszipper_errors');
            $errors[] = $errorMsg;
            wpm2awsAddUpdateOptions('wpm2aws_fszipper_errors', $errors);
            return false;
        }
        
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('Test 1');
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: Currently Processing File: " . $fileOrDir);
        }

        try {
            $awsApi = new WPM2AWS_ApiGlobal();
        } catch (Exception $e) {
            $errorMsg = 'Error: cant set class: ' . $e->getMessage();
            wpm2awsLogAction($errorMsg);
        }
        
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('Test 2');
        }

        if (strpos($fileOrDir, '.wpm2awsZipDir') === false) {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Debug: Processing File (not directory): ' . $fileOrDir);
            }

            $downloadFileOrDir = '';
            if (strpos($fileOrDir, 'wp-migrate-2-aws') !== false && strpos($fileOrDir, 'zipIimporter_fs.php') !== false) {
                $downloadFileOrDir = $fileOrDir;
                $downloadFileOrDir = str_replace('wp-migrate-2-aws', 'wp-migrate-2-aws-launcher', $downloadFileOrDir);
            }



            // ToDo
            // If File bigger thatn 0.5MB
            // Zip the file
            // Change the filename to "xxxx.zip"
            $fileName = $basePath . $pathSeparator . $fileOrDir;

            // The function `filesize` is cached, therefore we need to clear the cache before running.
            \clearstatcache(true, $fileName);

            $fileSize = \filesize($fileName);

            // If File is bigger than 1MB
            if ($fileSize >= 1000000) {
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('File Size: ' . $fileSize);
                }
            } 

            // Confirm that file exists before processing
            if (!file_exists($basePath . $pathSeparator . $fileOrDir)) {
                wpm2awsLogAction("Error! File does Not exists: " . $basePath . $pathSeparator . $fileOrDir);
                return $status = '404';
            }

            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Test 3'); 
            }
            
            try {
                // Log the start-time
                wpm2awsAddUpdateOptions('wpm2aws_fszipper_process_start_time', time());

                // Run the background process
                $download = $awsApi->backgroundZipFileToLocal($basePath, $fileOrDir, $downloadFileOrDir);
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction("DebugReturning Zip: " . $download);
                }
                wpm2awsLogAction("Debug: Returning Zip: " . $download);
                return $download;
            } catch (Exception $e) {
                $errorMsg = 'Error: No Array: ' . $e->getMessage();
                wpm2awsLogAction($errorMsg);
                // $this->log($errorMsg);
                $errors = get_option('wpm2aws_fszipper_errors');
                $errors[] = $errorMsg;
                wpm2awsAddUpdateOptions('wpm2aws_fszipper_errors', $errors);
                return false;
            }

            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Test 4');
            }
            
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: End: Background Process (1)");
            }

            return false;
        } else {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Test 5');
            }
            
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Debug: File / Dir listing IS a Directory");
            }

            $trimmedFileName = str_replace('.wpm2awsZipDir', '', $fileOrDir);
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction("Trimmed File Name: " . $trimmedFileName);
            }

            $wpm2aws_exclude_wp_core_themes = array(
                'twentyfifteen',
                'twentysixteen',
                'twentyseventeen',
                'twentyeighteen',
                'twentynineteen',
                'twentytwenty'
            );

            // if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), WPM2AWS_EXCLUDE_WP_CORE_THEMES)) {
            if (!in_array(str_replace('themes' . $pathSeparator, '', $trimmedFileName), $wpm2aws_exclude_wp_core_themes)) {
                
               
                // *********************************
                // *** New Addition - 24-02-2020 ***
                // *********************************
                $pathSeparator = DIRECTORY_SEPARATOR;
                    
                $fullDirectoryPath = $basePath . $pathSeparator . $trimmedFileName;

                // Confirm that file exists before processing
                if (!file_exists($fullDirectoryPath)) {
                    wpm2awsLogAction("Error! File (zip) does Not exists: " . $fullDirectoryPath);
                    return $status = '404';
                }

                try {
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Running Zip");
                    }
                    // $transferred = $awsApi->gZipAndTransferToS3($basePath, $trimmedFileName);
                    
                    // Log the start-time
                    wpm2awsAddUpdateOptions('wpm2aws_fszipper_process_start_time', time());

                    // Run the background process
                    $transferred = $awsApi->zipDirectoryAndDownload($basePath, $trimmedFileName);
                    
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Debug Returning Zipped: " . $transferred);
                    }
                    
                    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                        wpm2awsLogAction("Debug Returning Zipped: " . $transferred);
                    }

                    return $transferred;
                } catch (Exception $e) {
                    $errorMsg = "Error! Failed to Zip Directory: " . $e->getMessage();
                    wpm2awsLogAction($errorMsg);
                    $errors = get_option('wpm2aws_fszipper_errors');
                    $errors[] = $errorMsg;
                    wpm2awsAddUpdateOptions('wpm2aws_fszipper_errors', $errors);
                    return false;
                }


                // *** END: New Addition - 24-02-2020 ***
                return false;
            } else {
                wpm2awsLogAction("Intentionally Excluded From Zipping: " . $trimmedFileName);
                return $status = '200';
            }
        }

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Debug: End: Background Process (2)");
        }

        return false;
    }


    public function zipFullZippedDirLocal()
    {
        $zippedAll = zipFullZipDir();

        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction("Full Zip Dir: " . $zippedAll);
        }
    }


    private function getFileNameFromDir($filePath)
    {
        $fileName = '';
        $fileName = substr(strrchr($filePath, "/"), 1);
        return $fileName;
    }
    
    public function pathAfterWpContents($filePath, $base = 'wp-content')
    {
        $pos = strrpos($filePath, $base);
        if ($pos === false) {
            return $filePath;
        }
        return substr($filePath, 0, $pos + 1);
    }

    /**
     * Log
     *
     * @param string $message
     */
    public function log($message)
    {
        wpm2awsLogAction($message);
    }

    /**
     * Get lorem
     *
     * @param string $name
     *
     * @return string
     */
    protected function get_message($name)
    {
        if (is_array($name)) {
            $name = json_encode($name);
        }
        return $name;
    }
}
