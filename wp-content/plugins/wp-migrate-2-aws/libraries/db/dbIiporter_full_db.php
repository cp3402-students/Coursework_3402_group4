<?php


// wpm2awsLogAction('wp-m-2-aws Error');
require_once '/home/bitnami/apps/wordpress/htdocs/wp-content/plugins/wp-migrate-2-aws/vendor/autoload.php';
$fp = fopen('/wpm2aws-dbImport.txt', 'w');
fwrite($fp, 'Passed Require Once...');
fclose($fp);
// exit();
use Coderatio\SimpleBackup\SimpleBackup;

$fp = fopen('/wpm2aws-dbImport', 'a');
fwrite($fp, 'Passed Use...');
fclose($fp);
// exit();
// $password = shell_exec('cat /home/bitnami/bitnami_application_password');
// $passwordTrimmed = str_replace(array("\r", "\n", "\""), '', $password);

$password=`cat /home/bitnami/bitnami_application_password`;
$passwordTrimmed = str_replace(array("\r", "\n", "\""), '', $password);

$fp = fopen('/wpm2aws-dbImport', 'a');
fwrite($fp, 'Password: ' . $passwordTrimmed. '...');
fclose($fp);
// exit();

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
    )->importFrom(
        '/bitnami_wordpress.sql'
    );

    /**
    * You can then dump the response like this.
    *
    * @return object
    **/
    // var_dump($simpleBackup->getResponse());
    $responseString = json_encode($simpleBackup->getResponse());
    // wpm2awsLogAction($responseString);

    $fp = fopen('/wpm2aws-dbImport', 'a');
    fwrite($fp, 'Response: ' .     $responseString . '...');
    fclose($fp);
    exit();
} catch (Exception $e) {
    // wpm2awsLogAction('WP-Migrate-2-AWS Error: ' . $e->getMessage());
    $fp = fopen('/wpm2aws-dbImport', 'a');
    fwrite($fp, 'Error: ' .     $e->getMessage() . '...');
    fclose($fp);
    exit();
}
exit();
