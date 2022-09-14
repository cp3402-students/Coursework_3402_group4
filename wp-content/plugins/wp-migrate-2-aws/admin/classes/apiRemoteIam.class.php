<?php

class WPM2AWS_ApiRemoteIam
{
    const WPM2AWS_API_TIMEOUT = 180;

    private $requestData;

    public function __construct()
    {
        if (false === get_option('wpm2aws_licence_key') || '' === get_option('wpm2aws_licence_key')) {
            $this->abortWithErrorMessage("Error! No Licence Key Saved");
        }

        if (false === get_option('wpm2aws-iamid')) {
            $this->abortWithErrorMessage("No Access Key ID Saved");
        }

        if (false === get_option('wpm2aws-iampw')) {
            $this->abortWithErrorMessage("No Secret Access Key Saved");
        }

        // if (false === get_option('wpm2aws-aws-region')) {
        //     $this->abortWithErrorMessage("No AWS Region set");
        // }

        $iam_key = get_option('wpm2aws-iamid');
        $iam_secret = get_option('wpm2aws-iampw');
        $awsRegion = get_option('wpm2aws-aws-region');

        $this->requestData = array(
            'wpm2aws-licence-key' => get_option('wpm2aws_licence_key'),
            'wpm2aws-licence-email' => get_option('wpm2aws_licence_email'),
            'wpm2aws-licence-url' => get_option('wpm2aws_licence_url'),
            'wpm2aws-licence-site' => (!empty(get_site_url()) ? get_site_url() : ''),
            'wpm2aws-licence-type' => strtoupper(get_option('wpm2aws_valid_licence_type')),
            'wpm2aws_iam_key' => $iam_key,
            'wpm2aws_iam_secret' => $iam_secret,
            'wpm2aws_aws_region' => $awsRegion,
        );
    }


    public function getIamUser()
    {
        $response = wp_remote_post(
            WPM2AWS_MIGRATIONS_API_URL . '/api/migration/auth/user',
            array(
                'method' => 'POST',
                'timeout' => self::WPM2AWS_API_TIMEOUT,
                'redirection' => 10,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Cache-Control' => 'no-cache',
                ),
                'body' => array('data' => $this->requestData),
                'cookies' => array(),
            )
        );

        $responseCode = wp_remote_retrieve_response_code($response);
        $isError = is_wp_error($response);

        if ($isError === true) {
            // Alert Error
            $adminFunctions = new WPM2AWS_AdminFunctions();
            $errorMessage = $response->get_error_message();
            $adminFunctions->wpm2awsAlertBadRemoteConnection($responseCode, $errorMessage, 'getIamUser');
            $this->abortWithErrorMessage( 'An error has occurred. <br>' . $errorMessage . '<br>Please contact <a href="' . WPM2AWS_SEAHORSE_WEBSITE_URL . '/wp-on-aws-support-portal/" target="_blank">Seahorse Support</a>');
        }

        if ('200' !== $responseCode && 200 !== $responseCode) {
            $errorMessage = wp_remote_retrieve_response_message($response);

            if ((int)$responseCode >= 400 && (int)$responseCode < 500) {
                $this->abortWithErrorMessage('Error! This Action Can Not Be Completed. Unauthorised Access<br>' . $errorMessage);
            }

            if ((int)$responseCode >= 500 && (int)$responseCode < 600) {
                // Alert Bad Connection
                $adminFunctions = new WPM2AWS_AdminFunctions();
                $adminFunctions->wpm2awsAlertBadRemoteConnection($responseCode, $errorMessage, 'getIamUser');

                $this->abortWithErrorMessage('Error! This Action Can Not Be Completed. Internal Server Error.<br>' . $errorMessage);
            }

            $this->abortWithErrorMessage('Unauthorised Access<br>' . $errorMessage);
        }

        $responseData = json_decode( wp_remote_retrieve_body( $response ), true );

        if (empty($responseData['user-name'])) {
            $this->abortWithErrorMessage('Unauthorised Access<br>Unknown User');
        }

        if (empty($responseData['user-type'])) {
            $this->abortWithErrorMessage('Unauthorised Access<br>Unknown User Type');
        }

        return $user = $responseData;
    }

    public function abortWithErrorMessage($message)
    {
        set_transient(
            'wpm2aws_admin_error_notice_' . get_current_user_id(),
            __($message, 'migrate-2-aws')
        );

        wpm2awsWpRedirectAndExit();

//        $redirectPageName = get_option('wpm2aws-redirect-home-name');
//
//        if ($redirectPageName === false || $redirectPageName === '') {
//            $redirectPageName = 'wpm2aws';
//        }
//
//        exit(wp_redirect(admin_url('/admin.php?page=' . $redirectPageName)));
    }
}
