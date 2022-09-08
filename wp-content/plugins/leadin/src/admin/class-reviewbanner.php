<?php

namespace Leadin\admin;

use Leadin\wp\User;
use Leadin\admin\AdminConstants;
use Leadin\AssetsManager;

/**
 * Class responsible for rendering the review banner
 */
class ReviewBanner {

	/**
	 * Constructor enqueues scripts for tracking events and creates background leadin iframe
	 */
	public function __construct() {
		AssetsManager::enqueue_review_banner_tracking_script();
	}

	/**
	 * Render the review banner.
	 */
	public function leadin_render_review_banner() {
		$nonce               = wp_create_nonce( 'leadin-review' );
		$dismiss_notice_text = __( 'Dismiss this notice.', 'leadin' );
		$hello_text          = sprintf(
			__( 'Hey %1$s,', 'leadin' ),
			User::get_metadata( 'first_name' ) ? User::get_metadata( 'first_name' ) : User::get_metadata( 'nickname' )
		);
		$notice_text         = __( 'Congratulations, new contacts were added to your HubSpot CRM this week! Enjoying the plugin?', 'leadin' );
		$leave_review_text   = __( 'Leave us a review', 'leadin' );
		$from_hubspot_text   = __( 'HubSpot for WordPress Team', 'leadin' );
		?>
			<div id="leadin-review-banner" class="leadin-banner leadin-review-banner notice notice-warning">

				<a href="?leadin_review=false&_wpnonce=<?php echo esc_html( $nonce ); ?>"
					id="dismiss-review-banner-button">
						<button class="leadin-review-banner__dismiss notice-dismiss">
							<span class="screen-reader-text">
								<?php	echo esc_html( $dismiss_notice_text ); ?>
							</span>
						</button>
				</a>

				<div class="leadin-review-banner__content">
					<p class="leadin-review-banner__text">
						<?php	echo esc_html( $hello_text ); ?>
					</p>

					<div class="leadin-review-banner__content-body">
						<p class="leadin-review-banner__text">
							<?php	echo esc_html( $notice_text ); ?>
							<a
								class="leadin-banner__link"
								id="leave-review-button" target="_blank"
								href="?leadin_review=true&_wpnonce=<?php echo esc_html( $nonce ); ?>"
								aria-label="<?php echo esc_html( __( 'Leave us a review | link opens in a new tab', 'leadin' ) ); ?>"
							>
								<?php	echo esc_html( $leave_review_text ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="leadin-review-banner__author">
					<img src="<?php echo esc_attr( LEADIN_PATH . '/assets/images/hubspot-team-profile.png' ); ?>" height="48" />

					<p class="leadin-review-banner__text">
						Kelly
						<br/>
						<?php echo esc_html( $from_hubspot_text ); ?>
					</p>
				</div>
			</div>


		<?php
	}
}
