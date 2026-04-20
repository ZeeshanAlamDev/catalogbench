<?php
/**
 * Cost Estimator Service.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Cost_Estimator
 */
class CatalogBench_Cost_Estimator {

	/**
	 * Token costs per 1,000,000 input tokens.
	 *
	 * @var array
	 */
	private static $costs_per_million = array(
		'gemini'       => 0.00, // Free tier
		'gpt-5.4-mini' => 0.15, // Approx cost
		'gpt-5.4'      => 5.00, // Approx cost
		'gpt-4o-mini'  => 0.15, // Legacy fallback
		'gpt-4o'       => 5.00, // Legacy fallback
	);

	/**
	 * Calculate the estimated cost for a given provider, model, and token count.
	 *
	 * @param string $provider
	 * @param string $model
	 * @param int    $token_count
	 * @return float Cost in USD
	 */
	public static function estimate( $provider, $model, $token_count ) {
		if ( 'gemini' === $provider ) {
			return 0.00;
		}

		$rate = isset( self::$costs_per_million[ $model ] ) ? self::$costs_per_million[ $model ] : 0.15;
		return ( $token_count / 1000000 ) * $rate;
	}

	/**
	 * Calculate the cost of generating multiple fields for multiple products.
	 *
	 * @param int    $product_count The number of products selected.
	 * @param array  $fields        The fields selected (e.g. ['description', 'seo_title']).
	 * @param string $provider      'gemini' or 'openai'.
	 * @param string $model         'gpt-5.4-mini' etc.
	 * @return array
	 */
	public static function estimate_bulk( $product_count, $fields, $provider, $model ) {
		// Avg input tokens per product depends on data, we assume ~500 tokens input per field generated.
		$tokens_per_field = 500;
		$field_count      = is_array( $fields ) ? count( $fields ) : 0;
		$total_tokens     = $product_count * $field_count * $tokens_per_field;

		$cost = self::estimate( $provider, $model, $total_tokens );

		return array(
			'tokens' => $total_tokens,
			'cost'   => round( $cost, 4 ),
		);
	}
}
