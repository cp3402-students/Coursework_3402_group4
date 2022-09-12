<?php

class WPM2AWS_FileSizer
{
    public function __construct()
    {
    }

    /**
     * Helper method to call class method from WordPress
     *
     * @return void
     */
    public function searchAndSetOverSizedFilesAndDirectories()
    {
        add_action('admin_init', array( $this, 'findOverSizedFilesAndDirectories' ));
    }

    /**
     * Find a list of large files and add them to an excluded directories array.
     * An error array can be set if there are no directories scanned.
     *
     * @return void
     */
    public function findOverSizedFilesAndDirectories()
    {
        $this->setDirectoryPathInfo();

        // New Functionality to Restrict the Size of DIRS that are included in the Zipping
        $scannedFs = $this->scanFileSystemDirectories();

        if (\array_key_exists('error', $scannedFs)) {
            wpm2awsAddUpdateOptions('wpm2aws_excluded_over_sized_directories_files', $scannedFs);

            return;
        }

        if (\count( $scannedFs ) === 0) {
            $errorArray = array('error' => 'No Directories Scanned');
            wpm2awsAddUpdateOptions('wpm2aws_excluded_over_sized_directories_files', $errorArray);

            return;
        }

        $excludedDirectories = array();

        $excludeUploads = $this->isUploadsDirectoryExcluded();

        wpm2awsAddUpdateOptions('wpm2aws_uploads_directory_fully_excluded', $excludeUploads);

        // Identify Directories bigger than allowed
        foreach ($scannedFs as $fsVal) {
            // Never Exclude this Plugin (required for AWS Launch)
            if ('plugins' . DIRECTORY_SEPARATOR . WPM2AWS_PLUGIN_NAME === $fsVal['name']) {
                continue;
            }

            $isFileInUploadsDirectory = \strpos($fsVal['name'], 'uploads' . DIRECTORY_SEPARATOR);

            // If the file is in the uploads directory
            // AND
            // If the whole uploads directory is excluded,
            // THEN
            // always add the file to the excluded list
            if ($isFileInUploadsDirectory !== false && $excludeUploads === true) {
                $excludedDirectories[] = $fsVal;

                continue;
            }

            $fileSizeUnit = $fsVal['size']['unit'];
            $fileSizeValue = $fsVal['size']['value'];

            $isFileSizeUnitBiggerThanMb = $this->checkIfUnitIsGbTbPb($fileSizeUnit);
            $upperCaseFileSizeUnit = \strtoupper($fileSizeUnit);

            // If the file is in the uploads directory AND if the whole uploads directory is NOT excluded
            // OR
            // If the file is NOT in the uploads directory,
            // then run the standard file size checker
            if ($isFileSizeUnitBiggerThanMb) {
                $excludedDirectories[] = $fsVal;
            }

            if ('MB' === $upperCaseFileSizeUnit && $fileSizeValue > WPM2AWS_MAX_DIR_SIZE_ZIP) {
                $excludedDirectories[] = $fsVal;
            }
        }

        // Update the Option for use During the Zipping Process & elsewhere in the process
        $excludedDirectoryNames = array_column($excludedDirectories, 'name');

        wpm2awsAddUpdateOptions('wpm2aws_excluded_over_sized_directories_files', $excludedDirectories);
        wpm2awsAddUpdateOptions('wpm2aws_exclude_dirs_from_zip_process', $excludedDirectoryNames);
    }

    /**
     * @return bool
     */
    private function checkIfUnitIsGbTbPb($unit)
    {
        $upperCaseUnit = \strtoupper($unit);

        if ($upperCaseUnit === 'GB') {
            return true;
        }

        if ($upperCaseUnit === 'TB') {
            return true;
        }

        if ($upperCaseUnit === 'PB') {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    private function setDirectoryPathInfo()
    {
        $uploadFromPath = wpm2aws_content_dir();

        // If there is a path
        // And user has not already set an option
        // Then; Add the likely path as the option
        if ($uploadFromPath && false === get_option('wpm2aws-aws-s3-upload-directory-path')) {
            wpm2awsAddUpdateOptions('wpm2aws-aws-s3-upload-directory-path', $uploadFromPath);
        }


        // If the user has not already set an option
        // Then; Apply the standard location
        if (false === get_option('wpm2aws-aws-s3-upload-directory-name')) {
            wpm2awsAddUpdateOptions('wpm2aws-aws-s3-upload-directory-name', 'wp-content');
        }
    }

    /**
     * @return array
     */
    private function scanFileSystemDirectories()
    {
        $fullFilePath = get_option('wpm2aws-aws-s3-upload-directory-path') . DIRECTORY_SEPARATOR . get_option('wpm2aws-aws-s3-upload-directory-name');

        if (!is_dir($fullFilePath)) {
            return array(
                'error' => 'Error! Directory does not exist: ' . $fullFilePath,
            );
        }

        $directories = wpm2aws_listDirectoriesForZip($fullFilePath);

        $zippingDirsList = array();

        if (!empty($directories)) {
            foreach ($directories as $dirIx => $dirVals) {
                $dirName = $dirIx;

                if (!isset($zippingDirsList[$dirName])) {
                    $zippingDirsList[$dirName] = array('name' => $dirName);
                }
            }
        }

        if (!empty($zippingDirsList)) {
            foreach ($zippingDirsList as $dirIx => $dirVals) {
                $zippingDirsList[$dirIx]['size'] = $this->getFormattedDirectorySize($fullFilePath . DIRECTORY_SEPARATOR . $dirVals['name']);
            }
        }

        return $zippingDirsList;
    }

    /**
     * Checks if the uploads directory is within the allowed size limits
     *
     * @return bool
     */
    private function isUploadsDirectoryExcluded()
    {
        $uploadDirectory = wp_upload_dir();
        $uploadDirectoryPath = $uploadDirectory['basedir'];
        $uploadDirectorySize = $this->getUnFormattedDirectorySizeInBytes($uploadDirectoryPath); // Returns size in bytes
        $uploadDirectorySizeInMegaBytes = $this->convertBytesToMegaBytes($uploadDirectorySize);

        return $uploadDirectorySizeInMegaBytes >= WPM2AWS_MAX_DIR_SIZE_UPLOADS;
    }

    /**
     * @param $dirname
     *
     * @return int
     */
    private function getUnFormattedDirectorySizeInBytes($dirname)
    {
        if (!is_dir($dirname) || !file_exists($dirname)) {
            return 0;
        }

        return $this->calculateDirectorySize($dirname);
    }

    public function getFormattedDirectorySize($directoryPath)
    {
        if (\file_exists($directoryPath) === false) {
            return false;
        }

        if (\is_dir($directoryPath) === false) {
            return false;
        }

        $directorySize = $this->calculateDirectorySize($directoryPath);

        return $this->convertSizeToFormattedValues($directorySize);
    }

    private function getSummaryOutputOfDirectorySizes()
    {
        $upload_dir     = wp_upload_dir();
        $upload_space   = $this->calculateDirectorySize($upload_dir['basedir']);
        $content_space  = $this->calculateDirectorySize(WP_CONTENT_DIR);
        $wp_space       = $this->calculateDirectorySize(ABSPATH);

        /* ABSOLUTE paths not being shown in Widget */

        // echo '<b>' . $upload_dir['basedir'] . ' </b><br />';
        echo '<i>Uploads</i>: ' . $this->convertSizeToFormattedValues($upload_space)['value'] . $this->convertSizeToFormattedValues($upload_space)['unit'] . '<br /><br />';

        // echo '<b>' . WP_CONTENT_DIR . ' </b><br />';
        echo '<i>wp-content</i>: ' . $this->convertSizeToFormattedValues($content_space)['value'] . $this->convertSizeToFormattedValues($content_space)['unit'] . '<br /><br />';

        if (is_multisite()) {
            echo '<i>wp-content/blogs.dir</i>: ' . $this->convertSizeToFormattedValues($this->calculateDirectorySize(WP_CONTENT_DIR . '/blogs.dir'))['value'] . $this->convertSizeToFormattedValues($this->calculateDirectorySize(WP_CONTENT_DIR . '/blogs.dir'))['unit'] . '<br /><br />';
        }

        // echo '<b>' . ABSPATH . ' </b><br />';
        echo '<i>WordPress</i>: ' . $this->convertSizeToFormattedValues($wp_space)['value'] . $this->convertSizeToFormattedValues($wp_space)['unit'];
    }

    private function calculateDirectorySize($path)
    {
        $total_size = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';

        foreach ($files as $t) {
            if ('.' != $t && '..' != $t) {
                $currentFile = $cleanPath . $t;
                if (is_dir($currentFile)) {
                    $size = $this->calculateDirectorySize($currentFile);
                    $total_size += $size;
                } else {
                    // The function `filesize` is cached, therefore we need to clear the cache before running.
                    \clearstatcache(true, $currentFile);
                    $size = \filesize($currentFile);
                    $total_size += $size;
                }
            }
        }

        return $total_size;
    }

    private function convertSizeToFormattedValues($size)
    {
        $units = explode(' ', 'B KB MB GB TB PB');

        $mod = 1024;

        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        $endIndex = strpos($size, ".") + 3;

        return array(
            'value' => substr($size, 0, $endIndex),
            'unit' => $units[$i]
        );

        // return substr($size, 0, $endIndex) . ' ' . $units[$i];
    }

    /**
     * Convert value from Bytes to Mega Bytes, rounded to given number of places.
     *
     * @param int $bytes
     * @param int $round
     * @return float
     */
    private function convertBytesToMegaBytes($bytes, $round = 2)
    {
        $rawValueInMegaBytes = $bytes / (1e+6);

        return \round($rawValueInMegaBytes, $round);
    }
}
