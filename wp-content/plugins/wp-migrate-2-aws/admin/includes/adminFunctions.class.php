<?php

use Aws\Exception\AwsException as ApiException;
use Aws\Sns\SnsClient;

class WPM2AWS_AdminFunctions
{
    private $credentials;

    public function __construct()
    {
        $this->loadGlobal();

        // Call in the Patch for Vendor DIR
        require_once WPM2AWS_PLUGIN_DIR . '/vendor/aws/aws-sdk-php/src/SeahorsePatch/functions.php';
    }

    private function loadGlobal()
    {
        if (false === get_option('wpm2aws-iamid')) {
            // Convert to Warning Message & Reload Plugin Page
            wp_die("No Access Key ID Saved 2");
        }

        if (false === get_option('wpm2aws-iampw')) {
            // Convert to Warning Message & Reload Plugin Page
            wp_die("No Secret Access Key Saved");
        }

        $awsRegion = (false === get_option('wpm2aws-aws-region') ? WPM2AWS_PLUGIN_AWS_REGION : get_option('wpm2aws-aws-region'));

        $key = get_option('wpm2aws-iamid');
        $secret = get_option('wpm2aws-iampw');

        if (
            false !==  get_option('wpm2aws_valid_licence_type') &&
            'TRIAL' === strtoupper(get_option('wpm2aws_valid_licence_type'))
        ) {
            $key = str_replace(get_option('wpm2aws_valid_licence_dyck'), '', base64_decode(get_option('wpm2aws-iamid')));
            $secret = str_replace(get_option('wpm2aws_valid_licence_dyck'), '', base64_decode(get_option('wpm2aws-iampw')));
        }

        $this->credentials = array(
            'region' => $awsRegion, /// need to determine the region
            'version' => 'latest',
            'credentials' => array( /// cannot create credentials.ini file so have to use legacy method
                // 'key'    => AWS_ACCESS_KEY_ID,
                // 'secret' => AWS_SECRET_ACCESS_KEY,
                'key' => $key,
                'secret' => $secret,
            )
        );
    }

    /**
     * Alerts Seahorse of Bad Connection to Remote Connection
     *
     * @param $responseCode
     * @param $errorMessage
     * @param $triggerSource
     */
    public function wpm2awsAlertBadRemoteConnection($responseCode, $errorMessage, $triggerSource)
    {
        $sourceLocation = get_home_url();

        if (empty($sourceLocation) === true) {
            $sourceLocation = 'Unknown';
        }

        $subject = '== Critical == Remote Seahorse API Failure/Error';

        $message = 'An event has been triggered via the WPM2AWS Software';
        $message .= ' | Event Type : Bad Remote Connection';
        $message .= ' | Message : ' . $errorMessage;
        $message .= ' | Code : ' . $responseCode;
        $message .= ' | Source : ' . $sourceLocation . '::' . $triggerSource;

        $topic = 'arn:aws:sns:eu-west-1:' . WPM2AWS_PLUGIN_AWS_NUMBER . ':WPM2AWS_REMOTE_BAD_CONNECTION';

        try {
            $this->wpm2awsTriggerSNSAlert($subject, $message, $topic);
        } catch (Exception $e) {
            wpm2awsLogRAction('wpm2awsAlertBadRemoteConnection_error::' . $triggerSource, $e->getMessage());
        }
    }

    /**
     * Called in adminFunctions.php::wpm2awsAlertBadRemoteConnection
     *
     * TODO: Update usages of $apiGlobal->triggerSNSAlert() with this method. Make sure that this file is loaded is using function
     *
     * @param $subject
     * @param $message
     * @param $topic
     *
     * @throws Exception
     */
    private function wpm2awsTriggerSNSAlert($subject, $message, $topic)
    {
        try {
            $this->credentials['version'] = '2010-03-31';
            $SnsClient = new SnsClient($this->credentials);
            $result = $SnsClient->publish([
                'Subject' => $subject,
                'Message' => $message,
                'TopicArn' => $topic,
            ]);
        } catch (ApiException $e) {
            throw new Exception('API Call Failed! triggerSNSAlert (api) Error Mgs: ' . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage());
        } catch (Exception $e) {
            throw new Exception('API Call Failed! triggerSNSAlert (php) Error Mgs: ' . $e->getMessage());
        }
    }
}
