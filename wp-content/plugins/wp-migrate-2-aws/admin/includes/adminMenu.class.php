<?php
class WPM2AWS_AdminMenu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'loadAdminMenu'), 9);
    }

    public function loadAdminMenu()
    {
        // wp_die('loadAdminMenu');
        global $_wp_last_object_menu;

        $_wp_last_object_menu++;

        add_menu_page(
            __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws'),
            // 'wpm2aws_run_migration',
            'manage_options',
            ((defined('WPM2AWS_MIGRATED_SITE') && true === WPM2AWS_MIGRATED_SITE) ? 'wpm2aws-console' : 'wpm2aws'),
            ((defined('WPM2AWS_MIGRATED_SITE') && true === WPM2AWS_MIGRATED_SITE) ? array($this, 'loadAwsConsole') : array($this, 'loadIamForm')),
            // 'dashicons-migrate',
            plugins_url('assets/images/menu_icon_sm.png', dirname(__FILE__)),
            $_wp_last_object_menu
        );

        add_submenu_page(
            ((defined('WPM2AWS_MIGRATED_SITE') && true === WPM2AWS_MIGRATED_SITE) ? 'wpm2aws-console' : 'wpm2aws'),
            __(WPM2AWS_PAGE_TITLE_1, 'migrate-2-aws'),
            __(WPM2AWS_PAGE_TITLE_1, 'migrate-2-aws'),
            // 'wpm2aws_run_migration',
            'manage_options',
            'wpm2aws',
            array($this, 'loadIamForm')
        );

        if (defined('WPM2AWS_MIGRATED_SITE') && true === WPM2AWS_MIGRATED_SITE) {
            add_submenu_page(
                ((defined('WPM2AWS_MIGRATED_SITE') && true === WPM2AWS_MIGRATED_SITE) ? 'wpm2aws-console' : 'wpm2aws'),
                __(WPM2AWS_PAGE_TITLE_2, 'migrate-2-aws'),
                __(WPM2AWS_PAGE_TITLE_2, 'migrate-2-aws'),
                'manage_options',
                'wpm2aws-console',
                array($this, 'loadAwsConsole')
            );
        }
    }


    public function loadIamForm()
    {
        try {
            WPM2AWS_Settings::checkSettings();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            echo '<div class="error notice">';  
            echo '<p>';
            echo '<img src="' . plugins_url('assets/images/menu_icon_sm.png', dirname(__FILE__)) . '" alt="' . __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws') . ' Logo" />';
            echo "&nbsp;&nbsp;";
            echo '<span style="font-size:150%;">' . __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws') . '</span>';
            echo '</p>';
            echo '<p><strong>User Notice!</strong></p>';
            echo $msg;
            echo '</div>';
            echo '</div>';
            wp_die();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }
        WPM2AWS_AdminPages::loadIAMform();
    }

    public function loadAwsConsole()
    {
        try {
            WPM2AWS_Settings::checkSettings();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            echo '<div class="error notice">';  
            echo '<p>';
            echo '<img src="' . plugins_url('assets/images/menu_icon_sm.png', dirname(__FILE__)) . '" alt="' . __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws') . ' Logo" />';
            echo "&nbsp;&nbsp;";
            echo '<span style="font-size:150%;">' . __(WPM2AWS_PAGE_TITLE, 'migrate-2-aws') . '</span>';
            echo '</p>';
            echo '<p><strong>User Notice!</strong></p>';
            echo $msg;
            echo '</div>';
            echo '</div>';
            wp_die();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            wp_die("Error: " . $msg);
        }

        $settings = New WPM2AWS_Settings();
        $settings->loadAPIFiles();
        WPM2AWS_AdminPagesConsole::loadAwsConsole();
    }
}
