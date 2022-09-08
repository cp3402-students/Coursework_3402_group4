<?php
/**
 * Simply Schedule Appointments Filesystem.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Filesystem.
 *
 * @since 0.0.3
 */
class SSA_Filesystem {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks() {

	}

	public static function get_uploads_dir_path() {
		$dir = wp_upload_dir();
		$dir_path = $dir['basedir'] . '/ssa';
		if ( ! wp_mkdir_p( $dir_path ) ) {
			return false;
		}

		return $dir_path;
	}

	public static function get_uploads_dir_url() {
		$dir = wp_upload_dir();
		$ssa_dir_path = self::get_uploads_dir_path();
		$subdir = substr( $ssa_dir_path, strlen( $dir['basedir'] ) );

		$url = $dir['baseurl'] . $subdir;
		
		return $url;
	}

}
