<?php
/**
 * Google Gemini Provider.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Gemini_Provider
 */
class CatalogBench_Gemini_Provider implements CatalogBench_AI_Provider {

	/**
	 * Provider Name
	 */
	public function get_name() {
		return 'gemini';
	}

	/**
	 * Generate content using Google Gemini.
	 *
	 * @param string $prompt  The compiled system prompt.
	 * @param array  $options Array of generation options.
	 * @return string The generated text.
	 * @throws Exception If the API request fails.
	 */
	public function generate( $prompt, $options = array() ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			throw new Exception( __( 'Google Gemini API key is missing.', 'catalogbench' ) );
		}

		$settings     = get_option( 'catalogbench_settings', array() );
		$gemini_model = isset( $settings['gemini_model'] ) ? $settings['gemini_model'] : 'gemini-2.0-flash';
		$url          = 'https://generativelanguage.googleapis.com/v1beta/models/' . $gemini_model . ':generateContent?key=' . $api_key;

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => $prompt,
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature' => 0.7,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_json   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code ) {
			$error_msg = isset( $body_json['error']['message'] ) ? $body_json['error']['message'] : __( 'Unknown API error.', 'catalogbench' );
			throw new Exception( __( 'Gemini Error: ', 'catalogbench' ) . $error_msg );
		}

		if ( ! empty( $body_json['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return trim( $body_json['candidates'][0]['content']['parts'][0]['text'] );
		}

		throw new Exception( __( 'Failed to parse response from Gemini.', 'catalogbench' ) );
	}

	/**
	 * Test the API connection.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function test_connection() {
		$result = $this->generate( 'Respond with exactly "OK".' );
		return stripos( $result, 'OK' ) !== false;
	}

	/**
	 * Estimate token count by assuming ~4 characters per token.
	 *
	 * @param string $prompt
	 * @return int
	 */
	public function estimate_tokens( $prompt ) {
		return intval( ceil( strlen( $prompt ) / 4 ) );
	}

	/**
	 * Retrieve and decrypt the API key.
	 *
	 * @return string
	 */
	private function get_api_key() {
		$settings = get_option( 'catalogbench_settings', array() );
		$key_enc  = isset( $settings['gemini_key'] ) ? $settings['gemini_key'] : '';

		if ( ! empty( $key_enc ) ) {
			require_once CATALOGBENCH_PLUGIN_DIR . 'includes/utils/class-encryption.php';
			return CatalogBench_Encryption::decrypt( $key_enc );
		}
		return '';
	}
}
