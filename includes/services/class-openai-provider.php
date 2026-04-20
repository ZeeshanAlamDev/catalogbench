<?php
/**
 * OpenAI Provider.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_OpenAI_Provider
 */
class CatalogBench_OpenAI_Provider implements CatalogBench_AI_Provider {

	/**
	 * Provider Name
	 */
	public function get_name() {
		return 'openai';
	}

	/**
	 * Generate content using OpenAI.
	 *
	 * @param string $prompt  The compiled system prompt.
	 * @param array  $options Array of generation options (can override model).
	 * @return string The generated text.
	 * @throws Exception If the API request fails.
	 */
	public function generate( $prompt, $options = array() ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			throw new Exception( __( 'OpenAI API key is missing.', 'catalogbench' ) );
		}

		$url = 'https://api.openai.com/v1/chat/completions';

		$settings = get_option( 'catalogbench_settings', array() );
		$model    = isset( $options['model'] ) ? $options['model'] : ( isset( $settings['openai_model'] ) ? $settings['openai_model'] : 'gpt-5.4-mini' );

		$body = array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.7,
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
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
			throw new Exception( __( 'OpenAI Error: ', 'catalogbench' ) . $error_msg );
		}

		if ( ! empty( $body_json['choices'][0]['message']['content'] ) ) {
			return trim( $body_json['choices'][0]['message']['content'] );
		}

		throw new Exception( __( 'Failed to parse response from OpenAI.', 'catalogbench' ) );
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
	 * Estimate token count using word approximation (1 token = ~4 chars).
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
		$key_enc  = isset( $settings['openai_key'] ) ? $settings['openai_key'] : '';

		if ( ! empty( $key_enc ) ) {
			require_once CATALOGBENCH_PLUGIN_DIR . 'includes/utils/class-encryption.php';
			return CatalogBench_Encryption::decrypt( $key_enc );
		}
		return '';
	}
}
