<?php
$zippedFiles = fopen('/home/bitnami/apps/wordpress/htdocs/wp-content/zipLog.txt', 'r');
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

            $unZipToPath = str_replace('wpm2aws-zips/', '', $unZipFromPathFull);
            $unZipToPath = str_replace('.zip', '', $unZipToPath);

            // if (!is_dir($unZipToPath)) {
                // Unzip the File to Base Location
                // $command = 'unzip ' . $unZipFromPathFull . ' -d ' . $unZipToPath;

                $fullPathNoZip = $unZipFromPathFull;
                $fullPathNoZip = str_replace('.zip', '', $fullPathNoZip);


                $command = 'unzip ' . $unZipFromPathFull . ' -d ' . $fullPathNoZip;
                $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
                // echo $command . "\n";
                `$commandTrimmed`;

                
                $command = 'mv -n ' . $fullPathNoZip . ' ' . $unZipToPath;
                $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
                // echo $command . "\n";
                `$commandTrimmed`;
            // }
                     

            // // Remove the Zip Director
            // $command = 'rm ' . $unZipFromPathFull;
            // // echo $command . "\n";
            // $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
            // `$commandTrimmed`;


            // Log the Parent Directory
            $parentDir = substr($unZipFromPathFull, 0, strpos($unZipFromPathFull, 'wpm2aws-zips/'));
            array_push($zippedDirLocations, $parentDir . 'wpm2aws-zips');

        }
    }
    fclose($zippedFiles);

    // Remove Duplicates
    $zippedDirLocations = array_unique($zippedDirLocations);
}

// if (!empty($zippedDirLocations)) {
//     foreach ($zippedDirLocations as $zdlIx => $directoryName) {
//         $command = 'rm -R ' . $directoryName;
//         // echo $command . "\n";
//         $commandTrimmed = str_replace(array("\r", "\n", "\""), '', $command);
//         `$commandTrimmed`;
//     }
// }

exit();
