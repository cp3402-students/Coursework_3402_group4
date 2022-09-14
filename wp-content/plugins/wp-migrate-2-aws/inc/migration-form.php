<?php

class WPM2AWS_MigrationForm
{
    private $locale;
    private $properties = array();
    private static $current = null;

    public function __construct()
    {
    }

    public static function getTemplate($args = '')
    {
        global $l10n;

        $defaults = array( 'locale' => null, 'title' => '' );
        $args = wp_parse_args($args, $defaults);

        $locale = $args['locale'];
        $title = $args['title'];

        if ($locale) {
            $mo_orig = $l10n['migrate-2-aws'];
            wpm2aws_load_textdomain($locale);
        }

        self::$current = $migration_form = new self;
        $migration_form->title = __('New Clone Process - Add AWS Account Details (IAM User)', 'migrate-2-aws');
        $migration_form->locale = ($locale ? $locale : get_user_locale());

        $properties = $migration_form->getProperties();

        foreach ($properties as $key => $value) {
            $properties[$key] = WPM2AWS_MigrationFormTemplate::getDefault($key);
        }

        $migration_form->properties = $properties;

        $migration_form = apply_filters(
            'wpm2aws_migration_form_default_pack',
            $migration_form,
            $args
        );

        if (isset($mo_orig)) {
            $l10n['migrate-2-aws'] = $mo_orig;
        }

        return $migration_form;
    }


    public function prop($name)
    {
        $props = $this->getProperties();
        return isset($props[$name]) ? $props[$name] : null;
    }


    public function getProperties()
    {
        $properties = (array) $this->properties;

        $properties = wp_parse_args(
            $properties,
            array(
                'register_licence_form' => '',
                'iam_form' => '',
                'select-region' => '',
                'create-s3-bucket' => '',
                'use-s3-bucket' => '',
                'upload-directory-name' => '',
                'upload-directory-path' => '',
                'lightsail-instance-name' => '',
                'lightsail-instance-region' => '',
                'aws-get-all-regions' => '',
                'lightsail-instance-size' => '',
                'lightsail-instance-name-and-size' => '',
                'set-domain-name' => ''
            )
        );

        $properties = (array) apply_filters(
            'wpm2aws_migration_form_properties',
            $properties,
            $this
        );

        return $properties;
    }


    public function locale()
    {
        if (wpm2aws_is_valid_locale($this->locale)) {
            return $this->locale;
        } else {
            return '';
        }
    }


    public function setLocale($locale)
    {
        $locale = trim($locale);

        if (wpm2aws_is_valid_locale($locale)) {
            $this->locale = $locale;
        } else {
            $this->locale = 'en_GB';
        }
    }


    public function inDemoMode()
    {
        return $this->isTrue('demo_mode');
    }


    public function nonceIsActive()
    {
        $is_active = WPM2AWS_VERIFY_NONCE;

        if ($this->isTrue('subscribers_only')) {
            $is_active = true;
        }

        return (bool) apply_filters('wpm2aws_verify_nonce', $is_active, $this);
    }
}
