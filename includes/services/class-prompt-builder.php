<?php
/**
 * Prompt Builder Service.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Prompt_Builder
 *
 * Compiles product data into an optimized system prompt for the AI providers.
 */
class CatalogBench_Prompt_Builder {

	/**
	 * Build the prompt.
	 *
	 * @param array  $product_data Array of WooCommerce product data.
	 * @param string $content_type The type of content to generate (description, short_description, seo_title, seo_meta).
	 * @param string $tone         The tone of voice requested.
	 * @param string $custom_instr Any custom instructions from the user.
	 * @return string The compiled prompt.
	 */
	public static function build( $product_data, $content_type, $tone, $custom_instr = '' ) {
		$target = self::get_target_label( $content_type );
		$length = self::get_target_length( $content_type );

		$prompt = "You are a professional e-commerce copywriter.\n";
		$prompt .= "Generate {$target} for the following product.\n\n";

		$prompt .= "TONE: {$tone}\n";
		if ( ! empty( $custom_instr ) ) {
			$prompt .= "CUSTOM INSTRUCTIONS: {$custom_instr}\n";
		}
		$prompt .= "MAX LENGTH: {$length}\n";
		$prompt .= "FORMATTING COMMAND: Do NOT output Markdown. Return raw HTML or plain text suitable for a WordPress WYSIWYG editor. No markdown bolding (**), no markdown headings (###).\n\n";

		$prompt .= "PRODUCT DATA:\n";
		$prompt .= "- Title: " . ( isset( $product_data['title'] ) ? $product_data['title'] : '' ) . "\n";
		$prompt .= "- Current Description: " . ( isset( $product_data['description'] ) ? wp_strip_all_tags( $product_data['description'] ) : '' ) . "\n";
		$prompt .= "- Short Description: " . ( isset( $product_data['short_description'] ) ? wp_strip_all_tags( $product_data['short_description'] ) : '' ) . "\n";
		$prompt .= "- Categories: " . ( isset( $product_data['categories'] ) ? $product_data['categories'] : '' ) . "\n";
		$prompt .= "- Tags: " . ( isset( $product_data['tags'] ) ? $product_data['tags'] : '' ) . "\n";
		$prompt .= "- Price: " . ( isset( $product_data['price'] ) ? $product_data['price'] : '' ) . "\n";
		$prompt .= "- SKU: " . ( isset( $product_data['sku'] ) ? $product_data['sku'] : '' ) . "\n";
		
		if ( ! empty( $product_data['attributes'] ) ) {
			$prompt .= "- Attributes: " . $product_data['attributes'] . "\n";
		}

		$prompt .= "\nOUTPUT FORMAT:\n";
		$prompt .= "Return ONLY the generated text. No explanations, no pleasantries.\n";

		return $prompt;
	}

	/**
	 * Map internal type to readable target label.
	 *
	 * @param string $type
	 * @return string
	 */
	private static function get_target_label( $type ) {
		switch ( $type ) {
			case 'description':
				return 'a comprehensive HTML long description';
			case 'short_description':
				return 'a concise plain-text short description';
			case 'seo_title':
				return 'an SEO-optimized title tag';
			case 'seo_meta':
				return 'an SEO-optimized meta description';
			default:
				return 'product content';
		}
	}

	/**
	 * Get length instruction for the prompt.
	 *
	 * @param string $type
	 * @return string
	 */
	private static function get_target_length( $type ) {
		$settings = get_option( 'catalogbench_settings', array() );
		$lengths  = isset( $settings['max_length'] ) ? $settings['max_length'] : array();

		switch ( $type ) {
			case 'description':
				$max = isset( $lengths['description'] ) ? $lengths['description'] : 300;
				return "Approx {$max} words";
			case 'short_description':
				$max = isset( $lengths['short_description'] ) ? $lengths['short_description'] : 60;
				return "Max {$max} words";
			case 'seo_title':
				$max = isset( $lengths['seo_title'] ) ? $lengths['seo_title'] : 60;
				return "Max {$max} characters";
			case 'seo_meta':
				$max = isset( $lengths['seo_meta'] ) ? $lengths['seo_meta'] : 160;
				return "Max {$max} characters";
			default:
				return 'Flexible';
		}
	}
}
