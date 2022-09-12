<?php

/**
 * Determine if User
 * has correct permissions to
 * carry-out the migration
 */
class WPM2AWS_Permissions
{

    /**
     * Run Function at Admin Page Init
     * Callable from Main
     *
     * @return void
     */
    public function runPermissionsCheck()
    {
        add_action('admin_init', array($this, 'checkUserPermissions' ));
    }

    /**
     * Parent Function
     * Runs Individual Admin Checks
     * Kills Page with Notice if not Authorised
     *
     * @return die|true
     */
    public function checkUserPermissions()
    {
        if (!$this->isUserLoggedIn()) {
            wp_die(__('You are not authorised to access this page', 'migrate-2-aws' ));
            return false;
        }
        
        if (!$this->isUserAdmin()) {
            wp_die(__('You are not authorised to access this page', 'migrate-2-aws' ));
            return false;
        }

        if (!$this->canUserManageOptions()) {
            wp_die(__('You are not authorised to access this page', 'migrate-2-aws' ));
            return false;
        }
        return true;
    }

    /**
     * Checks that User is Logged In
     *
     * @return boolean
     */
    private function isUserLoggedIn()
    {
        // WP Fn;
        // Returns boolean;
        return is_user_logged_in();
    }
    

    /**
     * Checks that User is Admin
     *
     * @return boolean
     */
    private function isUserAdmin()
    {
        // WP Fn;
        // Returns boolean;
        return current_user_can('administrator');
    }

    /**
     * Checks that User Can Manage Options
     *
     * @return boolean
     */
    private function canUserManageOptions()
    {
        // WP Fn;
        // Returns boolean
        return current_user_can('manage_options');
    }
}
