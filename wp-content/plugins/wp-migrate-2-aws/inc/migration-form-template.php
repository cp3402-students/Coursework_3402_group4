<?php

class WPM2AWS_MigrationFormTemplate
{
    public static function getDefault($prop = 'register_licence_form')
    {
        if ('register_licence_form' === $prop) {
            $template = self::registerLicenceForm();
        } elseif ('iam_form' === $prop) {
            $template = self::iamForm();
        } elseif ('select-region' === $prop) {
            $template = self::awsRegions();
        } elseif ('create-s3-bucket' === $prop) {
            $template = self::createS3Bucket();
        } elseif ('use-s3-bucket' === $prop) {
            $template = self::useExistingS3Bucket();
        } elseif ('upload-directory-name' === $prop) {
            $template = self::setDirectoryName();
        } elseif ('upload-directory-path' === $prop) {
            $template = self::setDirectoryPath();
        } elseif ('lightsail-instance-name' === $prop) {
            // $template = self::createLightsail();
            $template = self::setLightsailName();
        } elseif ('lightsail-instance-region' === $prop) {
            $template = self::setLightsailRegion();
        } elseif ('aws-get-all-regions' === $prop) {
            $template = self::getAwsRegions();
        } elseif ('set-domain-name' === $prop) {
            $template = self::updateDomainName();
        } elseif ('lightsail-instance-size' === $prop) {
            $template = self::setLightsailInstanceSize();
        } elseif ('lightsail-instance-name-and-size' === $prop) {
            $template = self::setLightsailInstanceNameAndSize();
        } else {
            $template = null;
        }

        return apply_filters('wpm2aws_default_template', $template, $prop);
    }

    /**
     * @return string[]
     */
    public static function getAwsRegions()
    {
        return array(
            'us-east-2' => 'US East (Ohio)',
            'us-east-1' => 'US East (N. Virginia)',
            'us-west-2' => 'US West (Oregon)',
            'ap-south-1' => 'Asia Pacific (Mumbai)',
            'ap-northeast-2' => 'Asia Pacific (Seoul)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'ca-central-1' => 'Canada (Central)',
            'eu-central-1' => 'EU (Frankfurt)',
            'eu-west-1' => 'EU (Ireland)',
            'eu-west-2' => 'EU (London)',
            'eu-west-3' => 'EU (Paris)'
        );
    }

    /**
     * @return string[]
     */
    public static function getAwsRegionsUrls()
    {
        return array(
            'us-east-2' => 'lightsail.us-east-2.amazonaws.com',
            'us-east-1' => 'lightsail.us-east-1.amazonaws.com',
            'us-west-2' => 'lightsail.us-west-2.amazonaws.com',
            'ap-south-1' => 'lightsail.ap-south-1.amazonaws.com',
            'ap-northeast-2' => 'lightsail.ap-northeast-2.amazonaws.com',
            'ap-southeast-1' => 'lightsail.ap-southeast-1.amazonaws.com',
            'ap-southeast-2' => 'lightsail.ap-southeast-2.amazonaws.com',
            'ap-northeast-1' => 'lightsail.ap-northeast-1.amazonaws.com',
            'ca-central-1' => 'lightsail.ca-central-1.amazonaws.com',
            'eu-central-1' => 'lightsail.eu-central-1.amazonaws.com',
            'eu-west-1' => 'lightsail.eu-west-1.amazonaws.com',
            'eu-west-2' => 'lightsail.eu-west-2.amazonaws.com',
            'eu-west-3' => 'lightsail.eu-west-3.amazonaws.com',
        );
    }

    public static function awsRegions()
    {
        $regions = self::getAwsRegions();

        $regionUrl = self::getAwsRegionsUrls();

        $template = array(
            array(
                'field_type' => 'select',
                'field_name' => 'wpm2aws_awsRegionSelect',
                'field_id' => 'wpm2aws_awsRegionSelect',
                'field_placeholder' => __('Select AWS Region', 'migrate-2-aws'),
                'field_label' => __('Select AWS Region', 'migrate-2-aws'),
                'field_data' => $regions,
                'field_value' => (false === get_option('wpm2aws-aws-region') ? '' : get_option('wpm2aws-aws-region')),
            ),
        );


        return $template;
    }


    public static function createS3Bucket()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_s3BucketName',
                'field_id' => 'wpm2aws_s3BucketName',
                'field_placeholder' => __('Name your Storage Target', 'migrate-2-aws'),
                'field_label' => __('Storage Target Name', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-aws-s3-bucket-name') ? '' : get_option('wpm2aws-aws-s3-bucket-name')),
            ),
        );

        return $template;
    }

    public static function useExistingS3Bucket()
    {
        $template = array(
            array(
                'field_type' => 'select',
                'field_name' => 'wpm2aws_s3BucketNameExisting',
                'field_id' => 'wpm2aws_s3BucketNameExisting',
                'field_placeholder' => __('Select Storage Target', 'migrate-2-aws'),
                'field_label' => __('Select Storage Target', 'migrate-2-aws'),
                'field_data' => get_option('wpm2aws-existingBucketNames'),
                'field_value' => (false === get_option('wpm2aws-aws-s3-bucket-name') ? '' : get_option('wpm2aws-aws-s3-bucket-name')),
            ),
        );

        return $template;
    }

    public static function setDirectoryName()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_uploadDirectoryName',
                'field_id' => 'wpm2aws_uploadDirectoryName',
                'field_placeholder' => __('Set Upload Directory Name', 'migrate-2-aws'),
                'field_label' => __('Upload Directory Name', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-aws-s3-upload-directory-name') ? '' : get_option('wpm2aws-aws-s3-upload-directory-name')),
            ),
        );

        return $template;
    }

    public static function setDirectoryPath()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_uploadDirectoryPath',
                'field_id' => 'wpm2aws_uploadDirectoryPath',
                'field_placeholder' => __('Set Upload Directory Path', 'migrate-2-aws'),
                'field_label' => __('Upload Directory Path', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-aws-s3-upload-directory-path') ? '' : get_option('wpm2aws-aws-s3-upload-directory-path')),
            ),
        );

        return $template;
    }


    public static function setLightsailName()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_lightsailName',
                'field_id' => 'wpm2aws_lightsailName',
                'field_placeholder' => __('Name your AWS instance', 'migrate-2-aws'),
                'field_label' => __('AWS Instance Name', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-aws-lightsail-name') ? '' : get_option('wpm2aws-aws-lightsail-name')),
            ),
        );

        return $template;
    }

    public static function setLightsailRegion()
    {
        $regions = array(
            'us-east-2' => 'US East (Ohio)',
            'us-east-1' => 'US East (N. Virginia)',
            'us-west-2' => 'US West (Oregon)',
            'ap-south-1' => 'Asia Pacific (Mumbai)',
            'ap-northeast-2' => 'Asia Pacific (Seoul)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'ca-central-1' => 'Canada (Central)',
            'eu-central-1' => 'EU (Frankfurt)',
            'eu-west-1' => 'EU (Ireland)',
            'eu-west-2' => 'EU (London)',
            'eu-west-3' => 'EU (Paris)'
        );

        $regionUrl = array(
            'us-east-2' => 'lightsail.us-east-2.amazonaws.com',
            'us-east-1' => 'lightsail.us-east-1.amazonaws.com',
            'us-west-2' => 'lightsail.us-west-2.amazonaws.com',
            'ap-south-1' => 'lightsail.ap-south-1.amazonaws.com',
            'ap-northeast-2' => 'lightsail.ap-northeast-2.amazonaws.com',
            'ap-southeast-1' => 'lightsail.ap-southeast-1.amazonaws.com',
            'ap-southeast-2' => 'lightsail.ap-southeast-2.amazonaws.com',
            'ap-northeast-1' => 'lightsail.ap-northeast-1.amazonaws.com',
            'ca-central-1' => 'lightsail.ca-central-1.amazonaws.com',
            'eu-central-1' => 'lightsail.eu-central-1.amazonaws.com',
            'eu-west-1' => 'lightsail.eu-west-1.amazonaws.com',
            'eu-west-2' => 'lightsail.eu-west-2.amazonaws.com',
            'eu-west-3' => 'lightsail.eu-west-3.amazonaws.com',
        );

        if (false !== get_option('wpm2aws-aws-lightsail-region')) {
            $fieldValue = get_option('wpm2aws-aws-lightsail-region');
        } elseif (false !== get_option('wpm2aws-aws-region')) {
            $fieldValue = get_option('wpm2aws-aws-region');
        } else {
            $fieldValue = '';
        }

        $template = array(
            array(
                'field_type' => 'select',
                'field_name' => 'wpm2aws_lightsailRegionSelect',
                'field_id' => 'wpm2aws_lightsailRegionSelect',
                'field_placeholder' => __('Select Region', 'migrate-2-aws'),
                'field_label' => __('AWS Instance - Launch Region', 'migrate-2-aws'),
                'field_data' => $regions,
                'field_value' => $fieldValue,
            ),
        );

        return $template;
    }

    public static function setLightsailInstanceSize()
    {
        $aws_instances_sizes = array(
            'Nano' => 'Nano (1x CPU | 0.5Gb Ram | 20Gb Disk)',
            'Micro' => 'Micro (1x CPU | 1Gb Ram | 40Gb Disk)',
            'Small' => 'Small (1x CPU | 2Gb Ram | 60Gb Disk)',
            'Medium' => 'Medium (2x CPU | 4Gb Ram | 80Gb Disk)',
            'Large' => 'Large (2x CPU | 8Gb Ram | 160Gb Disk)',
            'Xlarge' => 'Xlarge (4x CPU | 16Gb Ram | 320Gb Disk)',
            '2Xlarge' => '2Xlarge (8x CPU | 32Gb Ram | 640Gb Disk)',
        );

        $fieldValue = get_option('wpm2aws-aws-lightsail-size');

        if ($fieldValue === false || $fieldValue === '') {
            $fieldValue = WPM2AWS_PLUGIN_AWS_LIGHTSAIL_SIZE;
        }

        $template = array(
            array(
                'field_type' => 'select',
                'field_name' => 'wpm2aws_lightsailInstanceSizeSelect',
                'field_id' => 'wpm2aws_lightsailInstanceSizeSelect',
                'field_placeholder' => __('Select Instance Size', 'migrate-2-aws'),
                'field_label' => __('AWS Instance - Launch Instance Size', 'migrate-2-aws'),
                'field_data' => $aws_instances_sizes,
                'field_value' => $fieldValue,
            ),
        );

        return $template;
    }

    public static function setLightsailInstanceNameAndSize()
    {
        $instanceNameTemplate = self::setLightsailName();
        $instanceSizeTemplate = self::setLightsailInstanceSize();

        $template = \array_merge($instanceNameTemplate, $instanceSizeTemplate);

        return $template;
    }

    public static function updateDomainName()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_lightsailDomainName',
                'field_id' => 'wpm2aws_lightsailDomainName',
                'field_placeholder' => __('Domain Name', 'migrate-2-aws'),
                'field_label' => __('Edit Domain Name', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-aws-lightsail-domain-name') ? '' : get_option('wpm2aws-aws-lightsail-domain-name')),
            ),
        );

        return $template;
    }


    public static function registerLicenceForm()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_licence_key',
                'field_id' => 'wpm2aws_licence_key',
                'field_placeholder' => __('Enter the Licence Key received from Seahorse', 'migrate-2-aws'),
                'field_label' => __('Licence key', 'migrate-2-aws'),
                'field_value' => '',
            ),
            array(
                'field_type' => 'email',
                'field_name' => 'wpm2aws_licence_email',
                'field_id' => 'wpm2aws_licence_email',
                'field_placeholder' => __('Enter the Email Address used when registering with Seahorse', 'migrate-2-aws'),
                'field_label' => __('Email Address', 'migrate-2-aws'),
                'field_value' => '',
            ),
        );

        return $template;
    }

    public static function iamForm()
    {
        $template = array(
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_iamid',
                'field_id' => 'wpm2aws_iamid',
                'field_placeholder' => __('Enter your xx digit IAM Key', 'migrate-2-aws'),
                'field_label' => __('IAM Key', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-iamid') ? '' : get_option('wpm2aws-iamid')),
            ),
            array(
                'field_type' => 'text',
                'field_name' => 'wpm2aws_iampw',
                'field_id' => 'wpm2aws_iampw',
                'field_placeholder' => __('Enter your xx digit IAM Secret', 'migrate-2-aws'),
                'field_label' => __('IAM Secret', 'migrate-2-aws'),
                'field_value' => (false === get_option('wpm2aws-iampw') ? '' : get_option('wpm2aws-iampw')),
            ),
        );

        return $template;
    }
}
