<?php

function wpm2awsGetLogActionApiTimeOut()
{
    return 180;
}

function wpm2awsGetValidateLicenceActionApiTimeOut()
{
    return 180;
}

function wpm2awsAddUpdateOptions($optionName, $value)
{
    if (false === get_option($optionName)) {
        add_option($optionName, $value);
    } else {
        update_option($optionName, $value);
    }
    return;
}

function wpm2awsValidateBucketName($bucketName)
{
    // - Should not contain uppercase characters
    if (preg_match('/[A-Z]/', $bucketName)) {
        // wp_die('1');
        return false;
    }

    // - Should not contain underscores (_)
    if (preg_match('/[_]/', $bucketName)) {
        // wp_die('2');
        return false;
    }

    // - should not contain a space
    if (strpos($bucketName, ' ') !== false) {
        // wp_die('3');
        return false;
    }

    // Confirm Character Match
    $bucketNameLength = strlen($bucketName);
    $bucketNameLengthUtf8 = mb_strlen($bucketName, 'utf8');
    if ($bucketNameLength !== $bucketNameLengthUtf8) {
        // wp_die('4');
        return false;
    }

    // - Should be between 3 and 63 characters long
    if ($bucketNameLength < 3 || $bucketNameLength > 63) {
        // wp_die('5');
        return false;
    }

    // - Should not end with a dash
    if (substr($bucketName, -1) === '-') {
        // wp_die('6');
        return false;
    }

    // - Cannot contain two, adjacent periods
    if (strpos($bucketName, '..') !== false) {
        // wp_die('7');
        return false;
    }

    // - Cannot contain dashes next to periods (e.g., "my-.bucket.com" and "my.-bucket" are invalid)
    if (strpos($bucketName, '.-') !== false) {
        // wp_die('8');
        return false;
    }
    if (strpos($bucketName, '-.') !== false) {
        // wp_die('9');
        return false;
    }

    return true;
}

function sanitizeTrailUserEmailToAwsBucketName($unsanitizedString)
{
    // - All characters other than '-' should be replaced with a '-' (note escape for special character)
    $pattern = '/[^a-zA-Z0-9\-]+/';
    $replacement = '-';
    $sanitizedString = preg_replace($pattern, $replacement, $unsanitizedString);

    // Maximum length is 63 characters: Adjust For Max Length = 63
    if (strlen($sanitizedString) > 63) {
        $sanitizedString = substr($sanitizedString, 0, 63);
    }

    // Last character should not be '-': remove last charachter until it is not '-'
    $lastChar = substr($sanitizedString, -1);
    while ('-' === $lastChar) {
        $sanitizedString = substr($sanitizedString, 0, (strlen($sanitizedString) - 1));
        $lastChar = substr($sanitizedString, -1);
    }

    // - Should not contain uppercase characters
    return $lowercaseSanitizedString = strtolower($sanitizedString);
}

function sanitizeTrailUserEmailToLightsailName($unsanitizedString)
{
    // - All characters other than '-' should be replaced with a '-' (note escape for special character)
    $pattern = '/[^a-zA-Z0-9\-\_\.]+/';
    $replacement = '-';
    $sanitizedString = preg_replace($pattern, $replacement, $unsanitizedString);

    // Last character should not be '-': remove last charachter until it is not '-'
    $lastChar = substr($sanitizedString, -1);
    while ('-' === $lastChar) {
        $sanitizedString = substr($sanitizedString, 0, (strlen($sanitizedString) - 1));
        $lastChar = substr($sanitizedString, -1);
    }

    // Last character should not be '-': remove last charachter until it is not '-'
    $lastChar = substr($sanitizedString, -1);
    while ('-' === $lastChar) {
        $sanitizedString = substr($sanitizedString, 0, (strlen($sanitizedString) - 1));
        $lastChar = substr($sanitizedString, -1);
    }

    // Last character should not be .': remove last charachter until it is not '.'
    $lastChar = substr($sanitizedString, -1);
    while ('.' === $lastChar) {
        $sanitizedString = substr($sanitizedString, 0, (strlen($sanitizedString) - 1));
        $lastChar = substr($sanitizedString, -1);
    }

    return $sanitizedString;
}

function wpm2aws_compress_directory($directoryPath, $fileOrDirectoryName, $pathSeparator, $forDownload = false)
{
    if (true === $forDownload) {
        return $path = wpm2aws_compress_directory_for_download($directoryPath, $fileOrDirectoryName, $pathSeparator, $forDownload);
    } else {
        return $path = wpm2aws_compress_directory_for_upload($directoryPath, $fileOrDirectoryName, $pathSeparator);
    }


    // // if source-filepath does not exist
    // // then abort
    // if (!file_exists($directoryPath . DIRECTORY_SEPARATOR . $fileOrDirectoryName)) {
    //     return '';
    // }

    // // Get real path for our folder
    // // $rootPath = realpath('folder-to-zip');
    // $fileOrDirectoryParentpath = '';
    // $endPointName = $fileOrDirectoryName;

    // $endPointSeparator = strrpos($fileOrDirectoryName,  DIRECTORY_SEPARATOR);
    // // if (false === $endPointSeparator) {
    // //     $endPointSeparator = strrpos($fileOrDirectoryName, '\\');
    // // }

    // if (false !== $endPointSeparator) {
    //     $fileOrDirectoryParentpath = substr($fileOrDirectoryName, 0, $endPointSeparator);
    //     $endPointName = substr($fileOrDirectoryName, ($endPointSeparator +1));
    // }

    // // $ziptoDirectory = $directoryPath . $fileOrDirectoryParentpath . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;


    // $ziptoDirectory = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;
    // $base = $ziptoDirectory;
    // $relativePostion = false;
    // if (true === $forDownload) {
    //     if (strpos($fileOrDirectoryName, DIRECTORY_SEPARATOR) !== false) {
    //         $subDirectory = str_replace($endPointName, '', $fileOrDirectoryName);
    //         if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
    //             wpm2awsLogAction('Mkdir : ' . $subDirectory);
    //         }
    //         $ziptoDirectory .= DIRECTORY_SEPARATOR . $subDirectory;
    //         $relativePostion = true;
    //     }
    // }

    // // $ziptoDirectory = /plugins/

    // if (is_dir($ziptoDirectory) === false) {
    //     // makeTempZipDirectory($ziptoDirectory);
    //     if (substr_count($subDirectory, DIRECTORY_SEPARATOR) > 1) {
    //         $dirArray = explode(DIRECTORY_SEPARATOR, $subDirectory);
    //         foreach ($dirArray as $dirIx => $dirName) {
    //             if (is_dir($base . $dirName) === false) {
    //                 try {
    //                     mkdir($base . DIRECTORY_SEPARATOR . $dirName);
    //                     wpm2awsLogAction('Made dir (' . $dirIx . '): ' . $base . DIRECTORY_SEPARATOR . $dirName);
    //                     $base .= DIRECTORY_SEPARATOR . $dirName;
    //                 } catch (Exception $e) {
    //                     wpm2awsLogAction('New Dir Error : ' . $e->getMessage());
    //                 }
    //             }
    //         }
    //     } else {
    //         try {
    //             mkdir($ziptoDirectory);
    //             wpm2awsLogAction('Made dir (std): ' . $ziptoDirectory);
    //         } catch (Exception $e) {
    //             wpm2awsLogAction('New Dir Error : ' . $e->getMessage());
    //         }
    //     }


    // }




    // if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
    //     wpm2awsLogAction('Zip Root Path : ' . $directoryPath);
    //     wpm2awsLogAction('Zip Root Name : ' . $fileOrDirectoryName);
    // }

    // $rootPath = $directoryPath . $fileOrDirectoryName;
    // $rootPath = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $rootPath);
    // if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
    //     wpm2awsLogAction('Zip Root Path : ' . $rootPath);
    // }

    // // Initialize archive object
    // $zip = new ZipArchive();

    // wpm2awsLogAction('End Point Name : ' . $endPointName);
    // $zipFilename = $ziptoDirectory  . $endPointName . '.zip';



    // if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
    //     wpm2awsLogAction('Zip File NAme : ' . $zipFilename);
    // }

    // $zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
    //     wpm2awsLogAction('Creating Zip @ : ' . $zipFilename);
    // }

    // $zipLogfile = fopen(WPM2AWS_ZIP_LOG_FILE_PATH, 'a');
    // fwrite($zipLogfile, $zipFilename . PHP_EOL);
    // fclose($zipLogfile);
    // // Create recursive directory iterator
    // /** @var SplFileInfo[] $files */
    // $files = new RecursiveIteratorIterator(
    //     new RecursiveDirectoryIterator($rootPath),
    //     RecursiveIteratorIterator::LEAVES_ONLY
    // );

    // foreach ($files as $name => $file) {
    //     // Skip directories (they would be added automatically)
    //     if (!$file->isDir()) {
    //         // Get real and relative path for current file
    //         $filePath = $file->getRealPath();

    //         $filePath = str_replace('\\', '/', $filePath );
    //         // wpm2awsLogAction('Root PAth : ' . $rootPath);
    //         // wpm2awsLogAction('File PAth : ' . $filePath);
    //         if (true === $relativePostion) {
    //             $relativePath = substr($filePath, strlen($rootPath) + 1);
    //         } else {
    //             $relativePath = substr($filePath, strlen($rootPath));
    //         }
    //         // wpm2awsLogAction('Relative PAth : ' . $relativePath);


    //         // wpm2awsLogAction('Relative Path : ' . $relativePath);
    //         // $relativePath = $ziptoDirectory . DIRECTORY_SEPARATOR  . $endPointName . '.zip' . DIRECTORY_SEPARATOR . $file->getFileName();
    //         // wpm2awsLogAction('Relative Path : ' . $ziptoDirectory . DIRECTORY_SEPARATOR  . $endPointName . '.zip' . DIRECTORY_SEPARATOR . $file->getFileName());

    //         // Add current file to archive
    //         $zip->addFile($filePath, $relativePath);
    //     }
    // }

    // // Zip archive will be created only after closing object
    // $zip->close();
    // return $path = $ziptoDirectory . DIRECTORY_SEPARATOR . $endPointName;
}

function wpm2aws_compress_directory_for_upload($directoryPath, $fileOrDirectoryName, $pathSeparator)
{
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('UPLOAD FUNCTION - In Fn: wpm2aws_compress_directory_for_upload');
    }

    // if source-filepath does not exist
    // then abort
    if (!file_exists($directoryPath . DIRECTORY_SEPARATOR . $fileOrDirectoryName)) {
        return '';
    }

    // Get real path for our folder
    // $rootPath = realpath('folder-to-zip');
    $fileOrDirectoryParentpath = '';
    $endPointName = $fileOrDirectoryName;

    $endPointSeparator = strrpos($fileOrDirectoryName, '/');
    if (false === $endPointSeparator) {
        $endPointSeparator = strrpos($fileOrDirectoryName, '\\');
    }

    if (false !== $endPointSeparator) {
        $fileOrDirectoryParentpath = substr($fileOrDirectoryName, 0, $endPointSeparator);
        $endPointName = substr($fileOrDirectoryName, ($endPointSeparator + 1));
    }

    $ziptoDirectory = $directoryPath . $fileOrDirectoryParentpath . $pathSeparator . WPM2AWS_ZIP_EXPORT_PATH;


    if (is_dir($ziptoDirectory) === false) {
        // makeTempZipDirectory($ziptoDirectory);
        mkdir($ziptoDirectory);
    }

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Root Path : ' . $directoryPath);
        wpm2awsLogAction('Zip Root Name : ' . $fileOrDirectoryName);
    }

    $rootPath = $directoryPath . $fileOrDirectoryName;
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Root Path : ' . $rootPath);
    }

    // Initialize archive object
    $zip = new ZipArchive();
    $zipFilename = $ziptoDirectory . $pathSeparator  . $endPointName . '.zip';

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip File NAme : ' . $zipFilename);
    }

    $zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Creating Zip @ : ' . $zipFilename);
    }

    $zipLogfile = fopen(WPM2AWS_ZIP_LOG_FILE_PATH, 'a');
    fwrite($zipLogfile, $zipFilename . PHP_EOL);
    fclose($zipLogfile);
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();

            $filePath = str_replace('\\', '/', $filePath);
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            // wpm2awsLogAction('Relative Path : ' . $relativePath);
            // $relativePath = $ziptoDirectory . $pathSeparator  . $endPointName . '.zip' . $pathSeparator . $file->getFileName();
            // wpm2awsLogAction('Relative Path : ' . $ziptoDirectory . $pathSeparator  . $endPointName . '.zip' . $pathSeparator . $file->getFileName());

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
    return $path = $ziptoDirectory . $pathSeparator  . $endPointName;
}


function wpm2aws_compress_directory_for_download($directoryPath, $fileOrDirectoryName, $pathSeparator, $forDownload = true)
{
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('DOWNLOAD FUNCTION - In Fn: wpm2aws_compress_directory_for_download');
    }

    // if source-filepath does not exist
    // then abort
    if (!file_exists($directoryPath . DIRECTORY_SEPARATOR . $fileOrDirectoryName)) {
        wpm2awsLogAction('File Does Not Exist: ' . $directoryPath . DIRECTORY_SEPARATOR . $fileOrDirectoryName);
        return '';
    }

    // Get real path for our folder
    $fileOrDirectoryParentpath = '';

    $fullDirPath = $directoryPath . DIRECTORY_SEPARATOR . $fileOrDirectoryName;

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Full Dir Path: ' . $fullDirPath);
    }

    $endPointName = $fileOrDirectoryName;

    $endPointSeparator = strrpos($fileOrDirectoryName, DIRECTORY_SEPARATOR);

    if (false !== $endPointSeparator) {
        $fileOrDirectoryParentpath = substr($fileOrDirectoryName, 0, $endPointSeparator);
        $endPointName = substr($fileOrDirectoryName, ($endPointSeparator + 1));
    }

    $ziptoDirectory = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;

    $base = $ziptoDirectory;
    $relativePostion = false;
    if (true === $forDownload) {
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('For Download : TRUE');
        }

        if (strpos($fileOrDirectoryName, DIRECTORY_SEPARATOR) !== false) {
            $subDirectory = str_replace($endPointName, '', $fileOrDirectoryName);
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Mkdir : ' . $subDirectory);
            }
            $ziptoDirectory .= DIRECTORY_SEPARATOR . $subDirectory;
            $relativePostion = true;
        }
    } else {
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            wpm2awsLogAction('For Download : FALSE');
        }
    }

    if (is_dir($ziptoDirectory) === false) {
        if (substr_count($subDirectory, DIRECTORY_SEPARATOR) > 1) {
            $dirArray = explode(DIRECTORY_SEPARATOR, $subDirectory);
            foreach ($dirArray as $dirIx => $dirName) {
                if (is_dir($base . $dirName) === false) {
                    try {
                        mkdir($base . DIRECTORY_SEPARATOR . $dirName);
                        $base .= DIRECTORY_SEPARATOR . $dirName;

                        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                            wpm2awsLogAction('Made dir (' . $dirIx . '): ' . $base . DIRECTORY_SEPARATOR . $dirName);
                        }
                    } catch (Exception $e) {
                        wpm2awsLogAction('New Dir Error : ' . $e->getMessage());
                    }
                }
            }
        } else {
            try {
                mkdir($ziptoDirectory);

                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('Made dir (std): ' . $ziptoDirectory);
                }
            } catch (Exception $e) {
                wpm2awsLogAction('New Dir Error : ' . $e->getMessage());
            }
        }
    }

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Root Path : ' . $directoryPath);
        wpm2awsLogAction('Zip Root Name : ' . $fileOrDirectoryName);
    }

    $rootPath = $directoryPath . $fileOrDirectoryName;
    $rootPath = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $rootPath);
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Root Path : ' . $rootPath);
    }

    // Initialize archive object
    $zip = new ZipArchive();

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('End Point Name : ' . $endPointName);
    }

    $zipFilename = $ziptoDirectory  . DIRECTORY_SEPARATOR . $endPointName . '.zip';

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip File NAme : ' . $zipFilename);
    }

    $zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Creating Zip @ : ' . $zipFilename);
    }


    // /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/wpm2aws-zips/languages.zip

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Log File Name : ' . $zipFilename);
    }
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Zip Log Base Name : ' . $base . ' | (' . strlen($base) . ')');
    }


    $zipLogfile = fopen(WPM2AWS_ZIP_LOG_FILE_PATH, 'a');
    // fwrite($zipLogfile, $zipFilename . PHP_EOL);

    // PC Edit 03-06-2020
    // $zipLogFileName = DIRECTORY_SEPARATOR . 'wpm2aws-zips' . DIRECTORY_SEPARATOR . substr($zipFilename, strlen($base));

    // PC Edit 03-06-2020
    $zipLogFileName = DIRECTORY_SEPARATOR . 'wpm2aws-zips' . DIRECTORY_SEPARATOR . $fileOrDirectoryName . '.zip';

    // Remove Duplicate Path Separators
    $logFileNameParts = explode(DIRECTORY_SEPARATOR, $zipLogFileName);
    $trimmedLogFileName = '';
    $trimmedLogFileName .= DIRECTORY_SEPARATOR;
    foreach ($logFileNameParts as $ix => $val) {
        if (!empty($val)) {
            $trimmedLogFileName .= $val;
            if ($ix < (count($logFileNameParts) - 1)) {
                $trimmedLogFileName .= DIRECTORY_SEPARATOR;
            }
        }
    }
    fwrite($zipLogfile, $trimmedLogFileName . PHP_EOL);
    // fwrite($zipLogfile, $zipLogFileName. PHP_EOL);
    fclose($zipLogfile);

    // Write files that can be downloaded
    $zipDownloadFilesLogfile = fopen(WPM2AWS_ZIP_DL_FILES_LOG_FILE_PATH, 'a');
    fwrite($zipDownloadFilesLogfile, $zipFilename . PHP_EOL);
    fclose($zipDownloadFilesLogfile);

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('File Can Be Downloaded : ' . $zipFilename);
    }


    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Files Recursive From : ' . $rootPath);
    }

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();

            $filePath = str_replace('\\', '/', $filePath);
            if (true === $relativePostion) {
                if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                    wpm2awsLogAction('Relative Postiion: TRUE');
                }
                $relativePath = substr($filePath, strlen($rootPath) + 1);
            } else {
                $relativePath = substr($filePath, strlen($rootPath));
            }

            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('File Path: ' . $filePath);
                wpm2awsLogAction('Relative Path: ' . $relativePath);
            }


            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
    return $path = $ziptoDirectory . DIRECTORY_SEPARATOR . $endPointName;
}


function zipFullZipDir()
{
    $expectedBaseDir = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . WPM2AWS_ZIP_EXPORT_PATH;

    if (!is_dir($expectedBaseDir)) {
        return '400';
    }

    // Initialize archive object
    $zip = new ZipArchive();


    $zip->open($expectedBaseDir . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($expectedBaseDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();

            $filePath = str_replace('\\', '/', $filePath);

            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Full Dir Root PAth : ' . $expectedBaseDir);
                wpm2awsLogAction('Full Dir File PAth : ' . $filePath);
            }

            // if (true === $relativePostion) {
                $relativePath = substr($filePath, strlen($expectedBaseDir) + 1);
                // $relativePath = $expectedBaseDir;
                // } else {
            //     $relativePath = substr($filePath, strlen($expectedBaseDir));
            // }

            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                wpm2awsLogAction('Full Dir Relative PAth : ' . $relativePath);
            }

            // wpm2awsLogAction('Relative Path : ' . $relativePath);
            // $relativePath = $ziptoDirectory . DIRECTORY_SEPARATOR  . $endPointName . '.zip' . DIRECTORY_SEPARATOR . $file->getFileName();
            // wpm2awsLogAction('Relative Path : ' . $ziptoDirectory . DIRECTORY_SEPARATOR  . $endPointName . '.zip' . DIRECTORY_SEPARATOR . $file->getFileName());

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

    // $path = $ziptoDirectory . DIRECTORY_SEPARATOR . $endPointName;
    $path = $ziptoDirectory . DIRECTORY_SEPARATOR;
    if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
        wpm2awsLogAction('Full Dir - Full Path : ' . $path);
    }

    return $path;
}


function wpm2aws_register_licence($licence)
{
    $requestData = array(
        'wpm2aws-licence-key' => (!empty($licence['wpm2aws-licence-key']) ? $licence['wpm2aws-licence-key'] : ''),
        'wpm2aws-licence-email' => (!empty($licence['wpm2aws-licence-email']) ? $licence['wpm2aws-licence-email'] : ''),
        'wpm2aws-licence-site' => (!empty(get_site_url()) ? get_site_url() : ''),
    );
    // wp_die(print_r($requestData));

    $response = wp_remote_post(
        WPM2AWS_MIGRATIONS_API_URL . '/api/migration/validateLicence',
        array(
            'method' => 'POST',
            'timeout' => wpm2awsGetValidateLicenceActionApiTimeOut(),
            'redirection' => 10,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Cache-Control' => 'no-cache',
            ),
            'body' => array( 'data' => $requestData ),
            'cookies' => array(),
        )
    );

    $responseCode = wp_remote_retrieve_response_code($response);
    $isError = is_wp_error($response);

    if ($isError === true) {
        // Alert Error
        // At this point in the process, we cannot trigger a "bad remote connection" call because we do not yet have IAM credentials.

        $errorMessage = $response->get_error_message();
        wp_die('An error has occurred. <br>' . $errorMessage . '<br>Please contact <a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/wp-on-aws-support-portal/" target="_blank">Seahorse Support</a>');
    }

    $valid = ('200' === $responseCode || 200 === $responseCode) ? true : false;

    wpm2awsAddUpdateOptions('wpm2aws_valid_licence', $valid);

    if ($valid) {
        wpm2awsAddUpdateOptions('wpm2aws_licence_key', $licence['wpm2aws-licence-key']);
        wpm2awsAddUpdateOptions('wpm2aws_licence_email', $licence['wpm2aws-licence-email']);
        wpm2awsAddUpdateOptions('wpm2aws_licence_url', $requestData['wpm2aws-licence-site']);

        $responseData = json_decode($response['body'], true);

        // wp_die(print_r($responseData));

        if (!empty($responseData['type'])) {
            wpm2awsAddUpdateOptions('wpm2aws_valid_licence_type', $responseData['type']);
        }
        if (!empty($responseData['plan'])) {
            wpm2awsAddUpdateOptions('wpm2aws_valid_licence_plan', $responseData['plan']);
        }

        if (!empty($responseData['keyp'])) {
            wpm2awsAddUpdateOptions('wpm2aws_valid_licence_keyp', $responseData['keyp']);
        }
        if (!empty($responseData['keys'])) {
            wpm2awsAddUpdateOptions('wpm2aws_valid_licence_keys', $responseData['keys']);
        }
        if (!empty($responseData['dyck'])) {
            wpm2awsAddUpdateOptions('wpm2aws_valid_licence_dyck', $responseData['dyck']);
        }
    } else {
        $errorMessage = wp_remote_retrieve_response_message($response);

        if ((int)$responseCode >= 500 && (int)$responseCode < 600) {
            wpm2awsLogRAction('wpm2aws_register_licence_error', $errorMessage);

            // Alert Bad Connection
            // At this point in the process, we cannot trigger a "bad remote connection" call because we do not yet have IAM credentials.

            wp_die('Error! This Action Can Not Be Completed. Internal Server Error<br>' . $errorMessage);
        }
    }
    return $valid;
}

function wpm2awsLogRAction($action, $logMsg = 'No Message')
{
    $requestData = array(
        'wpm2aws_site' => get_home_url(),
        'wpm2aws_action' => $action,
        'wpm2aws_message' => $logMsg,
        'wpm2aws_licence_email' => get_option('wpm2aws_licence_email'),
        'wpm2aws_licence_key' => get_option('wpm2aws_licence_key'),
        // 'wpm2aws_url' => (!empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ''),
    );

    $response = wp_remote_post(
        WPM2AWS_MIGRATIONS_API_URL . '/api/migration/log/action',
        array(
            'method' => 'POST',
            'timeout' => wpm2awsGetLogActionApiTimeOut(),
            'redirection' => 10,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Cache-Control' => 'no-cache',
            ),
            'body' => array( 'data' => $requestData ),
            'cookies' => array(),
        )
    );

    // Intentionally not triggering any Bad Connection Alert Here

    return;
}

function wpm2awsLogAction($logMsg)
{
    $logfile = fopen(WPM2AWS_LOG_FILE_PATH, 'a');
    fwrite($logfile, $logMsg . PHP_EOL);
    fclose($logfile);
    return;
}

function wpm2awsLogResetAll()
{
    $logfile = fopen(WPM2AWS_LOG_FILE_PATH, 'w');
    fwrite($logfile, '');
    fclose($logfile);
    return;
}

function wpm2awsZipLogResetAll()
{
    $logfile = fopen(WPM2AWS_ZIP_LOG_FILE_PATH, 'w');
    fwrite($logfile, '');
    fclose($logfile);
    return;
}

function wpm2awsdownloadZipLogResetAll()
{
    $logfile = fopen(WPM2AWS_ZIP_DL_FILES_LOG_FILE_PATH, 'w');
    fwrite($logfile, '');
    fclose($logfile);
    return;
}



// function deleteDirectoryTree($dir) {
//     $files = array_diff(
//         scandir($dir),
//         array(
//             '.',
//             '..'
//         )
//     );
//     foreach ($files as $file) {
//         (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
//     }
//     return rmdir($dir);
// }

// function deleteDirectoryTree($dirname) {
//     if (is_dir($dirname)) {
//         $dir_handle = opendir($dirname);
//         if (!$dir_handle) {
//             return false;
//         }

//         while($file = readdir($dir_handle)) {
//             if ("." !== $file && ".." !== $file) {
//                 if (!is_dir($dirname . DIRECTORY_SEPARATOR . $file)) {
//                     unlink($dirname  . DIRECTORY_SEPARATOR . $file);
//                 } else {
//                     deleteDirectoryTree($dirname . DIRECTORY_SEPARATOR . $file);
//                 }
//             }
//         }

//         closedir($dir_handle);
//         rmdir($dirname);
//         return true;
//     }
//     return false;
// }

function deleteDirectoryTree($dirname)
{

    if (! is_dir($dirname)) {
        return false;
    }

    if (substr($dirname, strlen($dirname) - 1, 1) !== DIRECTORY_SEPARATOR) {
        $dirname .= DIRECTORY_SEPARATOR;
    }

    // https://wordpress.stackexchange.com/questions/130215/call-to-a-member-function-put-contents-on-a-non-object
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(get_home_path() . 'wp-admin/includes/file.php');
        WP_Filesystem();
    }

    $wp_filesystem->rmdir($dirname, true);
    $wp_filesystem->delete($dirname, false, 'd');
    return true;
}

function makeTempZipDirectory($dirname)
{
    // https://wordpress.stackexchange.com/questions/130215/call-to-a-member-function-put-contents-on-a-non-object
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(get_home_path() . 'wp-admin/includes/file.php');
        WP_Filesystem();
    }

    $wp_filesystem->mkdir($dirname);
    return true;
}

function wpm2aws_makeDownloadKeyFile()
{
    $keyFile = fopen(WPM2AWS_KEY_DOWNLOAD_PATH, 'a');
    $fileContents = get_option('wpm2aws_lightsail_ssh');
    $fileContents = $fileContents['prkey'];
    // $fileContents = str_replace(' ', PHP_EOL, $fileContents);
    fwrite($keyFile, $fileContents);
    fclose($keyFile);
    return true;
}


function wpm2aws_listDirectoriesForZip($dir, $parentDir = '')
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

                if (in_array($fileInfo->getFilename(), $getSubDirs)) {
                    if (strpos($fileInfo->getPathname(), '.git') === false) {
                        $indexRef = $fileInfo->getFilename();
                        $files[$indexRef . '_children'] = wpm2aws_listDirectoriesForZip($fileInfo->getPathname(), $indexRef);
                    }
                }

                if (!empty($parentDir) && in_array($strippedParentDir, $getSubSubDirs)) {
                    $indexRef = $parentDir . $pathSeparator . $fileInfo->getFilename();

                    $files[$indexRef . '_sub_children'] = wpm2aws_listDirectoriesForZip($fileInfo->getPathname(), $indexRef);
                }
            }
            $counter++;
        }
    }

    $noSubDirs = array();

    foreach ($files as $fileIx => $fileData) {
        if (is_array($fileData)) {
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
                            unset($fileData[$fileDataIx]);
                        }
                    }
                }
            }

            // New Condition added - only unset if empty
            // if (!empty($fileData)) {
            $files = array_merge($files, $fileData);

            unset($files[$fileIx]);
        } else {
            // ToDo
        }
    }

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

function wpm2awsUserSetup()
{
    if (
        false === get_option('wpm2aws_valid_licence') ||
        '' === get_option('wpm2aws_valid_licence') ||
        empty(get_option('wpm2aws_valid_licence'))
    ) {
        set_transient(
            'wpm2aws_admin_error_notice_' . get_current_user_id(),
            __('Invalid Licence', 'migrate-2-aws')
        );
        wp_die( '<a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/contact/" target="_blank" class="button">Contact Seahorse</a>');
    }


    if (
        false === get_option('wpm2aws_valid_licence_type') ||
        'TRIAL' !== strtoupper(get_option('wpm2aws_valid_licence_type'))
    ) {
        return;
    }

    if (
        false === get_option('wpm2aws_valid_licence_keyp') ||
        '' === get_option('wpm2aws_valid_licence_keyp') ||
        empty(get_option('wpm2aws_valid_licence_keyp')) ||
        false === get_option('wpm2aws_valid_licence_keys') ||
        '' === get_option('wpm2aws_valid_licence_keys') ||
        empty(get_option('wpm2aws_valid_licence_keys')) ||
        false === get_option('wpm2aws_valid_licence_dyck') ||
        '' === get_option('wpm2aws_valid_licence_dyck') ||
        empty(get_option('wpm2aws_valid_licence_dyck'))
    ) {
        return;
    }

    wpm2awsAddUpdateOptions('wpm2aws-iamid', get_option('wpm2aws_valid_licence_keyp'));
    wpm2awsAddUpdateOptions('wpm2aws-iampw', get_option('wpm2aws_valid_licence_keys'));
    return;
}

/**
 * Replacement for built-in method wp_redirect
 * Finds the relevant "home" page name & uses that in redirect.
 * Required for redirecting to "wpm2aws-staging" or "wpm2aws-upgrade" etc
 *
 * @return void
 */
function wpm2awsWpRedirectAndExit()
{
    $redirectPageName = get_option('wpm2aws-redirect-home-name');

    if ($redirectPageName === false || $redirectPageName === '') {
        $redirectPageName = 'wpm2aws';
    }

    $redirectUrl = admin_url('/admin.php?page=' . $redirectPageName);

    wp_redirect($redirectUrl);

    exit();
}