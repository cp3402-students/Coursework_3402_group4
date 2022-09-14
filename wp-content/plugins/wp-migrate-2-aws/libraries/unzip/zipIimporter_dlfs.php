<?php
$zippedFiles = fopen('/home/bitnami/apps/wordpress/htdocs/zipLog.txt', 'r');
$zippedDirLocations = array();
$basePath = '/home/bitnami/apps/wordpress/htdocs/';
if ($zippedFiles) {
    while(!feof($zippedFiles)) {
        $line = fgets($zippedFiles);
        # do same stuff with the $line
        $line = str_replace('\\', '/', $line);

        $parentDirStart = strpos($line, 'wp-content');
        $unZipFromPath = substr($line, $parentDirStart);

        if (!empty($unZipFromPath) && '' !== $unZipFromPath) {
            $unZipFromPathFull = $basePath . $unZipFromPath;
            
            $unZipToPath = str_replace('/wpm2aws-zips/', 'wp-content/', $unZipFromPathFull);
            $unZipToPath = str_replace('.zip', '', $unZipToPath);

            $fullPathNoZip = $unZipFromPathFull;
            $fullPathNoZip = str_replace('.zip', '', $fullPathNoZip);

            $fullPathNoZip =  str_replace('/wpm2aws-zips/', 'wp-content/', $fullPathNoZip);

            
            // Make Sub Directories if not exist
            if (!file_exists($fullPathNoZip) || !is_dir($fullPathNoZip)) {
                $realPath =  explode("/", substr($unZipToPath, strlen($basePath . 'wp-content/')));
                $buildPath = '';
                foreach ($realPath as $rpIx => $rpVal) {
                    $subPath = $basePath . 'wp-content/' . $buildPath . $rpVal;
                    $buildPath .= '/' . $rpVal . '/';
                    if (!is_dir($subPath)) {
                        $command = 'mkdir ' . $subPath;
                        $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
                        `$commandTrimmed`;
                    }
                }
            }

            // Unzip the file into relevant sub-directory
            $command = 'unzip ' . $unZipFromPathFull . ' -d ' . $fullPathNoZip;
            $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
            `$commandTrimmed`;
        }
    }
    fclose($zippedFiles);
}

// Remove Zip File
// Remove unzipped Dir
// Remove Logs
// Remove this file

exit();
