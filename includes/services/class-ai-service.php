<?php
/**
 * AI Service Factory and Provider Interface.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface CatalogBench_AI_Provider
 *
 * Defines the contract all AI providers must implement.
 */
interface CatalogBench_AI_Provider {

	/**
	 * Generate content using the AI provider.
	 *
	 * @param string $prompt  The compiled system prompt.
	 * @param array  $options Array of generation options (model, max_tokens).
	 * @return string The generated text.
	 * @throws Exception If the API request fails.
	 */
	public function generate( $prompt, $options = array() );

	/**
	 * Test the API connection.
	 *
	 * @return bool True if connected successfully, false otherwise.
	 */
	public function test_connection();

	/**
	 * Estimate the tokens for a given prompt.
	 *
	 * @param string $prompt The prompt string.
	 * @return int Estimated token count.
	 */
	public function estimate_tokens( $prompt );

	/**
	 * Get the internal provider name.
	 *
	 * @return string
	 */
	public function get_name();
}

/**
 * Class CatalogBench_AI_Service
 *
 * Factory array for resolving the correct AI provider class.
 */
class CatalogBench_AI_Service {

	/**
	 * Get an instance of the requested AI provider.
	 *
	 * @param string $provider The provider key ('gemini' or 'openai').
	 * @return CatalogBench_AI_Provider
	 * @throws Exception If provider is unsupported.
	 */
	public static function get_provider( $provider ) {
		switch ( $provider ) {
			case 'gemini':
				require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-gemini-provider.php';
				return new CatalogBench_Gemini_Provider();
			case 'openai':
				require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-openai-provider.php';
				return new CatalogBench_OpenAI_Provider();
			default:
				throw new Exception( __( 'Unsupported AI provider.', 'catalogbench' ) );
		}
	}
}
