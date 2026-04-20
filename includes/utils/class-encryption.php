<?php
/**
 * Encryption utility.
 *
 * Encrypts and decrypts sensitive data (like API keys) using WordPress salts.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Encryption
 */
class CatalogBench_Encryption {

	/**
	 * Encryption method.
	 *
	 * @var string
	 */
	private static $method = 'aes-256-cbc';

	/**
	 * Encrypt a string.
	 *
	 * @param string $plaintext The string to encrypt.
	 * @return string The encrypted string (base64 encoded) or original string if failed.
	 */
	public static function encrypt( $plaintext ) {
		if ( empty( $plaintext ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $plaintext ); // Fallback if openssl is missing
		}

		$key  = self::get_key();
		$iv   = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::$method ) );
		$data = openssl_encrypt( $plaintext, self::$method, $key, 0, $iv );

		if ( false === $data ) {
			return '';
		}

		// Store IV bundled with data.
		return base64_encode( $iv . $data );
	}

	/**
	 * Decrypt a string.
	 *
	 * @param string $ciphertext The encrypted string (base64 encoded).
	 * @return string The decrypted string or original string if failed.
	 */
	public static function decrypt( $ciphertext ) {
		if ( empty( $ciphertext ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return base64_decode( $ciphertext ); // Fallback if openssl is missing
		}

		$data   = base64_decode( $ciphertext );
		$iv_len = openssl_cipher_iv_length( self::$method );

		// Data must be at least as long as IV.
		if ( strlen( $data ) <= $iv_len ) {
			return '';
		}

		$iv   = substr( $data, 0, $iv_len );
		$enc  = substr( $data, $iv_len );
		$key  = self::get_key();

		$decrypted = openssl_decrypt( $enc, self::$method, $key, 0, $iv );
		return false !== $decrypted ? $decrypted : '';
	}

	/**
	 * Generate an encryption key from WordPress salts.
	 *
	 * @return string Hex key.
	 */
	private static function get_key() {
		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'catalogbench-fallback-salt';
		$key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'catalogbench-fallback-key';
		return hash( 'sha256', $salt . $key );
	}
}
