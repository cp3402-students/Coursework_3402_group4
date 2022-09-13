<?php

class WPM2AWS_adminPagesCommon
{
    protected static function makePageHeading($page)
    {
        $pageTitle = constant("WPM2AWS_PAGE_TITLE_{$page}");

        ?>
        <div class="wrap" id="wpm2aws-page-heading-bar">
            <div style="width:75%;display:inline-block;">
                <div style="display:inline-block;height:35px;">
                    <img style="height:100%;" src="<?php echo plugin_dir_url( dirname( dirname(__FILE__) ) ); ?>assets/images/menu_icon.png"/>
                </div>
                <div style="display:inline-block">
                    <h1 class="wp-heading-inline">
                        &nbsp;
                        <?php echo esc_html(__($pageTitle, 'migrate-2-aws')); ?>
                    </h1>
                </div>
            </div>

            <div style="display:inline-block;">
                <div style="margin-left:5px;margin-top:10px;">
                    <p style="display:inline-block;margin: 0px;padding: 0px;">Powered by&nbsp;</p>
                    <div style="display:inline-block;height:auto;width:160px;">
                        <a href="<?php echo WPM2AWS_SEAHORSE_WEBSITE_URL; ?>" target="_blank">
                            <img style="height:auto;width:100%;" src="<?php echo plugin_dir_url( dirname( dirname(__FILE__) ) ); ?>assets/images/seahorse-logo_trimmed.png"/>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <hr class="wp-header-end">

        <?php
    }

    protected static function makeActionButtons()
    {
        $licenceType = get_option('wpm2aws_valid_licence_type');

        $disabledSectionClass = ' wpm2aws-disabled-sections';
        $noSectionLink = false;

        $html = '';

        // Row opener
        $html .= '<div class="wpm2aws-console-section wpm2aws-action-buttons">';

        // Ordering if licence key not yet confirmed
        if ($licenceType === false) {
            // Create Clone Block
            $html .= self::createCloneBlock($licenceType,'left');

            // Manage AWS Block
            $html .= self::createManageAwsBlock($licenceType, $disabledSectionClass, $noSectionLink, 'right');
        }

        // Ordering if licence key confirmed
        if ($licenceType !== false && $licenceType !== '') {
            // Manage AWS Block
            $html .= self::createManageAwsBlock($licenceType, $disabledSectionClass, $noSectionLink, 'left');

            // Create Clone Block
            $html .= self::createCloneBlock($licenceType,'right');
        }

        // Row closer
        $html .= '</div>';


        // Row opener
        $html .= '<div class="wpm2aws-console-section wpm2aws-action-buttons">';

        // Create Staging Block
        $html .= self::createStagingOnAwsBlock($licenceType, $disabledSectionClass, $noSectionLink, 'left');

        // Create Upgrade Stack Block
        $html .= self::createUpgradeStackOnAwsBlock($licenceType, $disabledSectionClass, $noSectionLink, 'right');

        // Row closer
        $html .= '</div>';

        return $html;
    }

    /**
     * @param $licenceType
     * @param $sectionClass
     * @param $sectionLink
     *
     * @return string
     */
    protected static function createManageAwsBlock($licenceType, $sectionClass, $sectionLink, $panelPosition)
    {
        $manageAwsAllowedLicenceTypes = ['single-migration', 'self-managed'];
        $offerUpgradeOptionLicenceTypes = ['trial', 'TRIAL'];

        // Set action button link if applicable
        if (\in_array($licenceType, $manageAwsAllowedLicenceTypes) === true) {
            $sectionLink = admin_url('/admin.php?page=wpm2aws-console');
            $sectionClass = '';
        }

        $html = '';
        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-' . $panelPosition . $sectionClass . '">';

        // Add upgrade option overlay if applicable
        if (\in_array($licenceType, $offerUpgradeOptionLicenceTypes) === true) {
            $html .= self::createUpgradeOptionOverlay();
        }

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        // Add action button link if available
        if ($sectionLink !== false) {
            $html .= '<a class="wpm2aws-action-button" href="' . $sectionLink . '">';
        }

        $html .= '<img style="max-width:70px;margin-bottom:30px;" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/manage-icon.png"/>';
        $html .= '<h3>'. esc_html(__('Manage AWS', 'migrate-2-aws')) .'</h3>';
        $html .= '<p>'. esc_html(__('View instance data, reboot, create snapshot, change instance size etc. from within WordPress', 'migrate-2-aws')) .'</p>';

        if ($sectionLink !== false) {
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param bool $optionUnavailable
     *
     * @return string
     */
    protected static function createCloneBlock($licenceType, $panelPosition, $optionUnavailable = false)
    {
        $createCloneAllowedLicenceTypes = ['trial', 'TRIAL', 'self-managed'];
        $offerUpgradeOptionLicenceTypes = ['single-migration'];

        $sectionLink = false;
        $sectionClass = ' wpm2aws-disabled-sections';

        // Set action button link if applicable
        if (\in_array($licenceType, $createCloneAllowedLicenceTypes) === true) {
            $sectionLink = admin_url('/admin.php?page=wpm2aws-clone');
            $sectionClass = '';
        }

        if ($licenceType === false || $licenceType === '' || $licenceType === null) {
            $sectionLink = admin_url('/admin.php?page=wpm2aws');
            $sectionClass = '';
        }

        if ($licenceType === 'trial' || $licenceType === 'TRIAL') {
            $sectionLink = admin_url('/admin.php?page=wpm2aws');
            $sectionClass = '';
        }

        // Set action button link if applicable
        if ($optionUnavailable === true) {
            $sectionLink = false;
            $sectionClass = ' wpm2aws-disabled-sections';
        }

        $html = '';
        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-' . $panelPosition . $sectionClass . '">';

        // Add upgrade option overlay if applicable
        if (\in_array($licenceType, $offerUpgradeOptionLicenceTypes) === true) {
            $html .= self::createUpgradeOptionOverlay();
        }

        if ($optionUnavailable === true) {
            $html .= self::createUpgradeOptionOverlay();
        }

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        // Add action button link if available
        if ($sectionLink !== false) {
            $html .= '<a class="wpm2aws-action-button" href="' . $sectionLink . '">';
        }

        $html .= '<img style="max-width:70px;margin-bottom:30px;" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/clone-icon.png"/>';
        $html .= '<h3>'. esc_html(__('Create Clone', 'migrate-2-aws')) .'</h3>';
        $html .= '<p>'. esc_html(__('This option allows you to clone this site onto the latest bitnami stack', 'migrate-2-aws')) .'</p>';

        if ($sectionLink !== false) {
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }


    /**
     * @param $licenceType
     * @param $sectionClass
     * @param $sectionLink
     *
     * @return string
     */
    protected static function createStagingOnAwsBlock($licenceType, $sectionClass, $sectionLink, $panelPosition)
    {
        $createStagingAllowedLicenceTypes = ['self-managed'];
        $offerUpgradeOptionLicenceTypes = ['trial', 'TRIAL', 'single-migration'];

        // Set action button link if applicable
        if (\in_array($licenceType, $createStagingAllowedLicenceTypes) === true) {
            $sectionLink = admin_url('/admin.php?page=wpm2aws-create-staging');
            $sectionClass = '';
        }

        $html = '';
        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-' . $panelPosition . $sectionClass . '">';

        // Add upgrade option overlay if applicable
        if (\in_array($licenceType, $offerUpgradeOptionLicenceTypes) === true) {
            $html .= self::createUpgradeOptionOverlay();
        }

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        // Add action button link if available
        if ($sectionLink !== false) {
            $html .= '<a class="wpm2aws-action-button" href="' . $sectionLink . '">';
        }

        $html .= '<img style="max-width:70px;margin-bottom:15px;" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/staging-icon.png"/><br>';
        $html .= '<h3>'. esc_html(__('Create Staging', 'migrate-2-aws')) .'</h3>';
        $html .= '<p>'. esc_html(__('This option allows you to create a direct copy (existing stack version) for launching staging/dev sites', 'migrate-2-aws')) .'</p>';

        if ($sectionLink !== false) {
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param $licenceType
     * @param $sectionClass
     * @param $sectionLink
     *
     * @return string
     */
    protected static function createUpgradeStackOnAwsBlock($licenceType, $sectionClass, $sectionLink, $panelPosition)
    {
        $upgradeStackAllowedLicenceTypes = ['self-managed'];
        $offerUpgradeOptionLicenceTypes = ['trial', 'TRIAL', 'single-migration'];

        // Set action button link if applicable
        if (\in_array($licenceType, $upgradeStackAllowedLicenceTypes) === true) {
            $sectionLink = admin_url('/admin.php?page=wpm2aws-upgrade-stack');
            $sectionClass = '';
        }

        $html = '';
        $html .= '<div class="wpm2aws-sub-panel wpm2aws-sub-panel-' . $panelPosition . $sectionClass . '">';

        // Add upgrade option overlay if applicable
        if (\in_array($licenceType, $offerUpgradeOptionLicenceTypes) === true) {
            $html .= self::createUpgradeOptionOverlay();
        }

        $html .= '<div class="wpm2aws-console-section wpm2aws-console-section-inverse">';

        // Add action button link if available
        if ($sectionLink !== false) {
            $html .= '<a class="wpm2aws-action-button" href="' . $sectionLink . '">';
        }


        $html .= '<img style="max-width:70px;margin-bottom:15px;" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/upgrade-icon.png"/><br>';
        $html .= '<h3>'. esc_html(__('Upgrade Stack', 'migrate-2-aws')) .'</h3>';
        $html .= '<p>'. esc_attr(__('This option allows you adopt the latest bitnami stack for this site (upgrading PHP, Apache, MariaDB & WordPress to latest available versions)', 'migrate-2-aws')) .'</p>';

        if ($sectionLink !== false) {
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    protected static function createUpgradeOptionOverlay()
    {
        $upgradeOptionOverlayLink = WPM2AWS_SEAHORSE_WEBSITE_URL . '/login';
        $upgradeOptionOverlay = '';
        $upgradeOptionOverlay .= '<div class="wpm2aws-action-button-overlay">';
        $upgradeOptionOverlay .= '<div class="wpm2aws-action-button-overlay-contents">';
        $upgradeOptionOverlay .= '<div class="wpm2aws-action-button-overlay-contents-text">Subscription licences have access to this features</div>';
        $upgradeOptionOverlay .= '<a class="wpm2aws-btn-warning" href="' . $upgradeOptionOverlayLink . '" target="_blank">Upgrade Licence Now</a>';
        $upgradeOptionOverlay .= '</div>';
        $upgradeOptionOverlay .= '</div>';

        return $upgradeOptionOverlay;
    }

    public static function makeActionsPage()
    {
        self::makePageHeading('MAIN');

        // print_r(self::$consoleRemoteData);
        $license_user_type = get_option('wpm2aws_valid_licence_type');
        $html = '';
        $html .= WPM2AWS_WelcomePanel::template();
        $html .= '<div id="wpm2aws-edit-inputs-section" class="wpm2aws-admin-section-container">';

        $html .= self::makeActionButtons();

        $html .= '</div>';

        $html .= WPM2AWS_AdminPagesGeneral::makeCurrentSettingsSummarySection();

        return $html;
    }
}