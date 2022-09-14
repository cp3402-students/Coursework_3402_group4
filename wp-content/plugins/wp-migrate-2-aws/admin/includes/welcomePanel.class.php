<?php

class WPM2AWS_WelcomePanel
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'loadAdminMenu'), 9);
    }

    public static function template()
    { ?>
        <div id="welcome-panel" class="wpm2aws-welcome-panel">
            <?php wp_nonce_field('wpm2aws-welcome-panel-nonce', 'welcomepanelnonce', false); ?>

            <div class="wpm2aws-welcome-panel-content">
                <div class="wpm2aws-welcome-panel-column-container">

                    <div style="max-width:10%;" class="wpm2aws-welcome-panel-column  wpm2aws-welcome-panel-first">

                        <p>
                        <img style="max-width:90%;" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>assets/images/aws-welcome-image.png"/>
                        </p>

                        </div>

                        <div style="max-width:25%;border-left:1px solid #d2d2d2;padding-left:25px; margin-top:8px;" class="wpm2aws-welcome-panel-column  wpm2aws-welcome-panel-middle">
                        <h3>Licence v.<?php echo constant("WPM2AWS_VERSION"); ?></h3>
                        <p>
                        <?php echo sprintf(
                            esc_html(
                                __(
                                    '%1$s to clone a site to AWS Now.',
                                    'migration-2-aws'
                                )
                            ),
                            wpm2awsHtmlLink(
                                __(
                                    WPM2AWS_SEAHORSE_WEBSITE_URL . '/checkout?edd_action=add_to_cart&download_id=8272',
                                    'migration-2-aws'
                                ),
                                __(
                                    'Get Credentials',
                                    'migration-2-aws'
                                ),
                                true,
                                array('target' => '_blank')
                            )
                        );
                        ?>
                        </p>

                        <p>
                        <?php
                        /* translators: links labeled 1: 'Migrate2AWS.com'*/
                        echo sprintf(
                            esc_html(
                                __(
                                    'View our %1$s.',
                                    'migration-2-aws'
                                )
                            ),
                            wpm2awsHtmlLink(
                                __(
                                    WPM2AWS_SEAHORSE_WEBSITE_URL . '/pricing/',
                                    'migration-2-aws'
                                ),
                                __(
                                    'Plan Options',
                                    'migration-2-aws'
                                ),
                                true,
                                array('target' => '_blank')
                            )
                        );
                        ?>
                        </p>

                    </div>

                        <div style="max-width:25%;border-left:1px solid #d2d2d2;padding-left:25px; margin-top:8px;" class="wpm2aws-welcome-panel-column  wpm2aws-welcome-panel-last">

                        <h3><?php echo esc_html(__("Tutorial", 'migration-2-aws')); ?></h3>
                       <p>
                        <?php echo sprintf(
                            esc_html(
                                __(
                                    '%1$s for AWS self-paced lab.',
                                    'migration-2-aws'
                                )
                            ),
                            wpm2awsHtmlLink(
                                __(
                                    'https://aws.amazon.com/getting-started/hands-on/migrating-a-wp-website/',
                                    'migration-2-aws'
                                ),
                                __(
                                    'Click Here',
                                    'migration-2-aws'
                                ),
                                true,
                                array('target' => '_blank')
                            )
                        );
                        ?>
                        </p>

                        <p>
                        <?php
                        /* translators: links labeled 1: 'Migrate2AWS.com'*/
                        echo sprintf(
                            esc_html(
                                __(
                                    '%1$s for Seahorse Support',
                                    'migration-2-aws'
                                )
                            ),
                            wpm2awsHtmlLink(
                                __(
                                    WPM2AWS_SEAHORSE_WEBSITE_URL . '/wp-on-aws-support-portal/',
                                    'migration-2-aws'
                                ),
                                __(
                                  'Click Here',
                                  'migration-2-aws'
                                ),
                                true,
                                array('target' => '_blank')
                            )
                        );
                        ?>
                        </p>


                        </div>

                </div>
            </div>
        </div>
        <?php
    }
}
