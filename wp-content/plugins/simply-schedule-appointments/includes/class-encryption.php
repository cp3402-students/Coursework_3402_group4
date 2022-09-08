<?php
/**
 * Simply Schedule Appointments Encryption.
 *
 * @since   4.5.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Encryption.
 *
 * @since 4.5.3
 */
class SSA_Encryption {
	const SALT = 'SSAECdGtpUDdxUzVWNSsyWW1nbE95VcDU4c1VtRnRqWXhHSU9yalNxQUR1dGszSGd5c1ZVR1NIUT09OjqCTV54';

	/**
	 * Parent plugin class.
	 *
	 * @since 4.5.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.5.3
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
	 * @since  4.5.3
	 */
	public function hooks() {

	}

	public static function get_encryption_key() {
		$encryption_key = get_option( 'ssa_ec' );
		if ( ! empty( $encryption_key ) ) {
			return $encryption_key;
		}

		$encryption_key = base64_encode(openssl_random_pseudo_bytes(32));
		update_option( 'ssa_ec', $encryption_key );
		return $encryption_key;
	}

	public static function encrypt($data, $key=null) {
		if ( empty( $key ) ) {
			$key = self::get_encryption_key();
		}
		// Remove the base64 encoding from our key
		$encryption_key = base64_decode($key);
		// Generate an initialization vector
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		// Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
		// The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
		return base64_encode($encrypted . '::' . $iv);
	}

	public static function decrypt($data, $key=null) {
		if ( empty( $key ) ) {
			$key = self::get_encryption_key();
		}
		// Remove the base64 encoding from our key
		$encryption_key = base64_decode($key);
		// To decrypt, split the encrypted data from our IV - our unique separator used was "::"
		list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
		return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
	}
}
