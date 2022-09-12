<?php

class WPM2AWS_AdminMenuSourceSite extends WPM2AWS_AdminMenu
{
    public function loadAdminMenu()
    {

        // If licence not registered, then show the square panel "clone to AWS"
        // Show other squares, unavailable with overlay that they are available after migration, licence type specific - see licences
        // Page URL should be "wpm2aws-actions"
        // Submenu should be main action page - clone to aws, URL = 'wpm2aws'
        if ($this->licenceType === false || $this->licenceType === '' || $this->licenceType === null) {
            $this->loadMenuLicenceNotVerified();

            return;
        }

        // If licence is registered, then do not show navigation
        // Page URL should be "wpm2aws"
        // Show submenu with other options
        // Submenu shows other squares, unavailable with overlay that they are available after migration, licence type specific - see licences
        $this->loadMenuLicenceIsVerified();
    }

    /**
     * Menu to display BEFORE the user has validated their licecne
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
            'wpm2aws-actions',
            array($this, 'loadLandingNavigation'),
            plugins_url('assets/images/menu_icon_sm.png', dirname( dirname( __FILE__ ) ) ),
            $_wp_last_object_menu
        );

        $this->addSubmenuCreateClone(true, 'wpm2aws-actions', 'wpm2aws');
    }

    /**
     * Menu to display AFTER the user has validated their licecne
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
            function () {
                $this->loadIamForm('MAIN');
            },
            plugins_url('assets/images/menu_icon_sm.png', dirname( dirname(__FILE__) ) ),
            $_wp_last_object_menu
        );

        $this->addSubmenuMainNavigation('wpm2aws', 'wpm2aws-actions');
    }
}