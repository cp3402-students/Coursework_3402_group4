<?php
class WPM2AWS_AdminMenu
{
    protected $licenceType;

    public function __construct()
    {
        // Get Licence type
        $this->licenceType = get_option('wpm2aws_valid_licence_type');

        add_action('admin_menu', array($this, 'loadAdminMenu'), 9);
    }

    /**
     * Add a submenu for "Main Navigation"
     *
     * @return void
     */
    public function addSubmenuMainNavigation($parentSlug, $menuSlug)
    {
        add_submenu_page(
            $parentSlug,
            __(WPM2AWS_PAGE_TITLE_MANAGE, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_MANAGE, 'migrate-2-aws'),
            'manage_options',
            $menuSlug,
            array($this, 'loadLandingNavigation')
        );
    }

    /**
     * Add a submenu for "Manage AWS"
     *
     * @return void
     */
    public function addSubmenuManageAws($parentSlug)
    {
        add_submenu_page(
            $parentSlug,
            __(WPM2AWS_PAGE_TITLE_MANAGE, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_MANAGE, 'migrate-2-aws'),
            'manage_options',
            'wpm2aws-console',
            array($this, 'loadAwsConsole')
        );
    }

    /**
     * Add a submenu for "Create Clone"
     *
     * @param $isAvailable
     * @param string $parentSlug
     * @param string $menuSlug
     * @return void
     */
    protected function addSubmenuCreateClone($isAvailable, $parentSlug = 'wpm2aws-actions', $menuSlug = 'wpm2aws-clone')
    {
        add_submenu_page(
            $parentSlug,
            __(WPM2AWS_PAGE_TITLE_CLONE, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_CLONE, 'migrate-2-aws'),
            'manage_options',
            $menuSlug,
            function () use ($menuSlug, $isAvailable) {
                wpm2awsAddUpdateOptions('wpm2aws-redirect-home-name', $menuSlug);

                if ($isAvailable === true) {
                    $this->loadIamForm('CLONE');

                    return;
                }

                self::loadAwsCreateClone();
            }
        );
    }

    /**
     * Add a submenu for "Create Staging"
     *
     * @param bool $isAvailable
     * @param string $parentSlug
     * @return void
     */
    protected function addSubmenuCreateStaging($isAvailable, $parentSlug)
    {
        $menuSlug = 'wpm2aws-create-staging';

        add_submenu_page(
            $parentSlug,
            __(WPM2AWS_PAGE_TITLE_STAGING, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_STAGING, 'migrate-2-aws'),
            'manage_options',
            $menuSlug,
            function () use ($menuSlug, $isAvailable) {
                wpm2awsAddUpdateOptions('wpm2aws-redirect-home-name', $menuSlug);

                if ($isAvailable === true) {
                    $this->loadIamForm('STAGING');

                    return;
                }

                $this->loadAwsCreateStaging();
            }
        );
    }

    /**
     * Add a submenu for "Upgrade Stack"
     *
     * @param bool $isAvailable
     * @param string $parentSlug
     * @return void
     */
    protected function addSubmenuUpgradeStack($isAvailable, $parentSlug)
    {
        $menuSlug = 'wpm2aws-upgrade-stack';

        add_submenu_page(
            $parentSlug,
            __(WPM2AWS_PAGE_TITLE_UPGRADE, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_UPGRADE, 'migrate-2-aws'),
            'manage_options',
            $menuSlug,
            function () use ($menuSlug, $isAvailable) {
                wpm2awsAddUpdateOptions('wpm2aws-redirect-home-name', $menuSlug);

                if ($isAvailable === true) {
                    $this->loadIamForm('UPGRADE');

                    return;
                }

                $this->loadAwsUpgradeStack();
            }
        );
    }

    /**
     * @param $pageTitle
     * @return void
     */
    public function loadIamForm($pageTitle)
    {
        try {
            WPM2AWS_Settings::checkSettings();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo '<div class="error notice">';
            echo '<p>';
            echo '<img src="' . plugins_url('assets/images/menu_icon_sm.png', dirname( dirname(__FILE__) )) . '" alt="' . __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws') . ' Logo" />';
            echo "&nbsp;&nbsp;";

            $pageTitle = constant("WPM2AWS_PAGE_TITLE_{$pageTitle}");
            echo '<span style="font-size:150%;">' . __($pageTitle, 'migrate-2-aws') . '</span>';
            echo '</p>';
            echo '<p><strong>User Notice!</strong></p>';
            echo $msg;
            echo '</div>';
            echo '</div>';
            wp_die();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        WPM2AWS_AdminPagesGeneral::loadIAMform($pageTitle);
    }

    public function loadAwsConsole()
    {
        try {
            WPM2AWS_Settings::checkSettings();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo '<div class="error notice">';
            echo '<p>';
            echo '<img src="' . plugins_url('assets/images/menu_icon_sm.png', dirname( dirname(__FILE__) )) . '" alt="' . __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws') . ' Logo" />';
            echo "&nbsp;&nbsp;";
            echo '<span style="font-size:150%;">' . __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws') . '</span>';
            echo '</p>';
            echo '<p><strong>User Notice!</strong></p>';
            echo $msg;
            echo '</div>';
            echo '</div>';
            wp_die();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        $validLicence =  get_option('wpm2aws_valid_licence');
        if ($validLicence === false || $validLicence === '') {
            WPM2AWS_AdminPagesGeneral::makeValidateLicenceSection();
            return;
        }

        $settings = New WPM2AWS_Settings();
        $settings->loadAPIFiles();
        WPM2AWS_AdminPagesConsole::loadAwsConsole();
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

        echo WPM2AWS_AdminPagesCommon::makeActionsPage();

    }

    public function loadAwsActions()
    {
        try {
            WPM2AWS_Settings::checkSettings();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo '<div class="error notice">';
            echo '<p>';
            echo '<img src="' . plugins_url('assets/images/menu_icon_sm.png', dirname( dirname(__FILE__) )) . '" alt="' . __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws') . ' Logo" />';
            echo "&nbsp;&nbsp;";
            echo '<span style="font-size:150%;">' . __(WPM2AWS_PAGE_TITLE_MAIN, 'migrate-2-aws') . '</span>';
            echo '</p>';
            echo '<p><strong>User Notice!</strong></p>';
            echo $msg;
            echo '</div>';
            echo '</div>';
            wp_die();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        $validLicence =  get_option('wpm2aws_valid_licence');
        if ($validLicence === false || $validLicence === '') {
            WPM2AWS_AdminPagesGeneral::makeValidateLicenceSection();
            return;
        }

        $iamUserName =  get_option('wpm2aws-iam-user');
        if ($iamUserName === false || $iamUserName === '') {
            WPM2AWS_AdminPagesGeneral::makeIamSection();
            return;
        }

        $settings = New WPM2AWS_Settings();
        $settings->loadAPIFiles();
        WPM2AWS_AdminPagesConsole::loadAwsActions();
    }

    /**
     * @return void
     */
    public function loadAwsCreateClone()
    {
        WPM2AWS_AdminPagesConsole::loadAwsCreateCloneView();
    }

    /**
     * @return void
     */
    public function loadAwsCreateStaging()
    {
        WPM2AWS_AdminPagesConsole::loadAwsCreateStagingView();
    }

    /**
     * @return void
     */
    public function loadAwsUpgradeStack()
    {
        WPM2AWS_AdminPagesConsole::loadAwsUpgradeStackView();
    }
}