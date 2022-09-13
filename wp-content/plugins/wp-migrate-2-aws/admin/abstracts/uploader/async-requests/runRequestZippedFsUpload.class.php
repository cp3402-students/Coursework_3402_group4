<?php

class WPM2AWS_RunRequestZippedFsUpload extends WP_Async_Request {
	use WPM2AWS_Logger;
	/**
	 * @var string
	 */
	protected $action = 'wpm2aws-zipped-fs-uploader-once';
	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		$this->log('<br>In Run Request handle');
		$message = $this->get_message( $_POST['name'] );
		$this->really_long_running_task();
		if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
			$this->log( $message );
		}
	}
}