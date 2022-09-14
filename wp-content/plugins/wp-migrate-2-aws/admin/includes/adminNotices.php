<?php

function wpm2aws_admin_notice__error($msg) {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo $msg; ?></p>
    </div>
    <?php
}

function wpm2aws_admin_notice__success($msg)
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo $msg; ?></p>
    </div>
    <?php
}

function wpm2aws_admin_notice__warning($msg)
{
    ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php echo $msg; ?></p>
    </div>
    <?php
}


/** Admin Notice on Activation. @since 0.1.0 */
function wpm2aws_admin_error_notice()
{
    /* Check transient, if available display notice */
    if (get_transient('wpm2aws_admin_error_notice_' . get_current_user_id())) {
        wpm2aws_admin_notice__error(get_transient('wpm2aws_admin_error_notice_' . get_current_user_id()));
        /* Delete transient, only display this notice once. */
        delete_transient('wpm2aws_admin_error_notice_' . get_current_user_id());
    }

    if (get_transient('wpm2aws_admin_success_notice_' . get_current_user_id())) {
        wpm2aws_admin_notice__success(get_transient('wpm2aws_admin_success_notice_' . get_current_user_id()));
        /* Delete transient, only display this notice once. */
        delete_transient('wpm2aws_admin_success_notice_' . get_current_user_id());
    }
    
    if (get_transient('wpm2aws_admin_warning_notice_' . get_current_user_id())) {
        wpm2aws_admin_notice__warning(get_transient('wpm2aws_admin_warning_notice_' . get_current_user_id()));
        /* Delete transient, only display this notice once. */
        delete_transient('wpm2aws_admin_warning_notice_' . get_current_user_id());
    }

    if (
        false !== get_option('wpm2aws_zipped_fs_upload_complete') &&
        'success' === get_option('wpm2aws_zipped_fs_upload_complete') &&
        false !== get_option('wpm2aws_upload_errors') &&
        '' !== get_option('wpm2aws_upload_errors')
    ) {
        $errors = get_option('wpm2aws_upload_errors');
        foreach ($errors as $errIx => $errVal) {
            wpm2aws_admin_notice__error($errVal);
        }
    }
}
