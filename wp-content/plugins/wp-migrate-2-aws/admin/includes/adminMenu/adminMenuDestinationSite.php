<?php

class WPM2AWS_AdminMenuDestinationSite extends WPM2AWS_AdminMenu
{
    public function loadAdminMenu()
    {
        if ($this->licenceType === false || $this->licenceType === '' || $this->licenceType === null) {
            $this->loadMenuLicenceNotVerified();

            return;
        }

        $this->loadMenuLicenceIsVerified();
    }

    /**
     * Menu to display BEFORE the user has validated their licence
     *
     * @return void
     */
    private function loadMenuLicenceNotVerified()
    {
        global $_wp_last_object_menu;

        $_wp_last_object_menu++;

        add_menu_page(
            __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws'),
            'manage_options',
            'wpm2aws',
            function () {
                $this->loadIamForm('MAIN');
            },
            plugins_url('assets/images/menu_icon_sm.png', dirname( dirname( __FILE__ ) ) ),
            $_wp_last_object_menu
        );
    }

    /**
     * Menu to display AFTER the user has validated their licence
     *
     * @return void
     */
    private function loadMenuLicenceIsVerified()
    {
        global $_wp_last_object_menu;

        $_wp_last_object_menu++;

        add_menu_page(
            __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws'),
            'manage_options',
            'wpm2aws',
            array($this, 'loadLandingNavigation'),
            plugins_url('assets/images/menu_icon_sm.png', dirname( dirname(__FILE__) ) ),
            $_wp_last_object_menu
        );

        if (strtoupper($this->licenceType) === 'TRIAL') {
            return;
        }

        $subscriptionFeaturesAvailable = true;

        if ($this->licenceType === 'single-migration') {
            $subscriptionFeaturesAvailable = false;
        }

        $this->addSubmenuManageAws('wpm2aws');

        $this->addSubmenuCreateClone($subscriptionFeaturesAvailable, 'wpm2aws', 'wpm2aws-clone');

        $this->addSubmenuCreateStaging($subscriptionFeaturesAvailable, 'wpm2aws');

        $this->addSubmenuUpgradeStack($subscriptionFeaturesAvailable, 'wpm2aws');
    }

    /**
     * @return void
     */
    public function loadLandingNavigation()
    {
        /* Add admin notice */
        add_action('wpm2aws_admin_notices', 'wpm2aws_admin_error_notice');
        do_action('wpm2aws_admin_notices');

        echo "<br>";
        wpm2awsAddUpdateOptions('wpm2aws-redirect-home-name', 'wpm2aws');

        echo WPM2AWS_AdminPagesConsole::makeActionsPage();
    }

}