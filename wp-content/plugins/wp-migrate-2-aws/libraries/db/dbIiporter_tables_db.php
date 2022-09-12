<?php
require_once '/home/bitnami/apps/wordpress/htdocs/wp-content/plugins/wp-migrate-2-aws/vendor/autoload.php';
$fp = fopen('/wpm2aws-dbImport.txt', 'w+');
fwrite($fp, 'Passed Require Once...' . PHP_EOL);
fclose($fp);

use Coderatio\SimpleBackup\SimpleBackup;

$fp = fopen('/wpm2aws-dbImport.txt', 'a');
fwrite($fp, 'Passed Use...' . PHP_EOL);
fclose($fp);

$password=`cat /home/bitnami/bitnami_application_password`;
$passwordTrimmed = str_replace(array("\r", "\n", "\""), '', $password);

$tablesLocation = '/home/bitnami/apps/wordpress/htdocs/wp-content/plugins/wp-migrate-2-aws/libraries/db/tables/';


/**
 * Function for Importing DB Table
 */
function importTable($simpleBackupObj, $fullFileNamePath)
{
    try {
        $simpleBackupObj->importFrom(
            $fullFileNamePath
        );

        /**
        * You can then dump the response like this.
        *
        * @return object
        **/
        $responseString = json_encode($simpleBackupObj->getResponse());

        $fp = fopen('/wpm2aws-dbImport.txt', 'a');
        fwrite($fp, 'Response: ' .     $responseString . ' | ' . $fullFileNamePath . PHP_EOL);
        fclose($fp);
        return;
    } catch (Throwable $e) {
        $fp = fopen('/wpm2aws-dbImport.txt', 'a');
        fwrite($fp, 'Error: ' .     $e->getMessage() . ' | ' . $fullFileNamePath . PHP_EOL);
        fclose($fp);
        return;
    } catch (Exception $e) {
        $fp = fopen('/wpm2aws-dbImport.txt', 'a');
        fwrite($fp, 'Error: ' .     $e->getMessage() . ' | ' . $fullFileNamePath . PHP_EOL);
        fclose($fp);
        return;
    }
}


// Set the database to backup
try {
    $simpleBackup = SimpleBackup::setDatabase(
        [
            'bitnami_wordpress',
            'root',
            // $password,
            $passwordTrimmed,
            'localhost'
        ]
    );
} catch (Throwable $e) {
    $fp = fopen('/wpm2aws-dbImport.txt', 'a');
    fwrite($fp, 'Error Setting DB: ' .     $e->getMessage() . ' | ' . PHP_EOL);
    fclose($fp);
    exit();
} catch (Exception $e) {
    $fp = fopen('/wpm2aws-dbImport.txt', 'a');
    fwrite($fp, 'Error Setting DB: ' .     $e->getMessage() . ' | ' . PHP_EOL);
    fclose($fp);
    exit();
}


// Merge All SQL Files into Single SQL file for use in upload
$sqlTableString = '';
foreach (new DirectoryIterator($tablesLocation) as $fileInfo) {
    if (!$fileInfo->isDot()) {
        if (!$fileInfo->isDir()) {
            /* New Addition for UnZipping - 25/09/2020 - PCullen */
            // $sqlTableString .= $tablesLocation . $fileInfo->getFilename() . ' ';
            $fileName = $fileInfo->getFilename();
            if (strpos($fileInfo->getFilename(), '.sql.gz') !== false) {
                $zipFile = $fileInfo->getFilename();
                $fileName = $fileInfo->getBasename('.sql.gz') . '.sql';

                $fullZipFilePath = $tablesLocation . $zipFile;
                $fullFileNamePath = $tablesLocation . $fileName;
                $unzip = `gunzip < $fullZipFilePath > $fullFileNamePath`;
                
                importTable($simpleBackup, $fullFileNamePath);
            } else {
                $sqlTableString .= $tablesLocation . $fileName . ' ';
            }
            /* END: New Addition for UnZipping - 25/09/2020 - PCullen */
        }
    }
}

/* If there is content in string then send to new file */
if ('' !== $sqlTableString) {
	$sqlTableString .= '> ' . $tablesLocation . 'bitnami_wordpress.sql';
	$newDb = `cat $sqlTableString`;
}

/* If the new File Exists, then Import the File */
if (file_exists($tablesLocation . 'bitnami_wordpress.sql')) {
	importTable($simpleBackup, $tablesLocation . 'bitnami_wordpress.sql');
}

exit();
