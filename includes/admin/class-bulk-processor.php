<?php
/**
 * Handles background bulk processing using WooCommerce Action Scheduler.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

class CatalogBench_Bulk_Processor {

	/**
	 * Hook registered actions and filters.
	 */
	public function register_hooks() {
		add_action( 'catalogbench_process_batch', array( $this, 'process_batch' ), 10, 2 );
	}

	/**
	 * Initiate a new bulk job.
	 *
	 * @param array $product_ids List of WooCommerce product IDs.
	 * @param array $config      Job configuration (tone, types, custom_instruction).
	 * @return string The unique job ID.
	 */
	public static function start_job( $product_ids, $config ) {
		$job_id = uniqid( 'cb_job_' );

		$job_data = array(
			'id'          => $job_id,
			'status'      => 'processing',
			'products'    => $product_ids,
			'total'       => count( $product_ids ),
			'completed'   => 0,
			'errors'      => 0,
			'config'      => $config,
			'started_at'  => time(),
		);

		set_transient( $job_id, $job_data, 7 * DAY_IN_SECONDS );

		// Schedule first batch.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'catalogbench_process_batch', array( $job_id, 0 ), 'catalogbench' );
		}

		return $job_id;
	}

	/**
	 * Process a specific batch of a job.
	 *
	 * @param string $job_id      The unique job ID.
	 * @param int    $batch_index The batch index (0-based).
	 */
	public function process_batch( $job_id, $batch_index ) {
		$job_data = get_transient( $job_id );
		if ( ! $job_data || 'processing' !== $job_data['status'] ) {
			return; // Job cancelled or missing.
		}

		$batch_size = 5;
		$start_idx  = $batch_index * $batch_size;
		$targets    = array_slice( $job_data['products'], $start_idx, $batch_size );

		if ( empty( $targets ) ) {
			$job_data['status'] = 'completed';
			set_transient( $job_id, $job_data, 7 * DAY_IN_SECONDS );
			return;
		}

		// Load dependencies required in the background cron context.
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-prompt-builder.php';
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-ai-service.php';

		$config       = $job_data['config'];
		$settings     = get_option( 'catalogbench_settings', array() );
		$provider_key = isset( $settings['selected_provider'] ) ? $settings['selected_provider'] : 'gemini';

		foreach ( $targets as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				$job_data['errors']++;
				$job_data['completed']++;
				continue;
			}

			// Build product data array matching PromptBuilder::build() signature.
			$product_data = array(
				'title'             => $product->get_name(),
				'description'       => $product->get_description(),
				'short_description' => $product->get_short_description(),
				'price'             => $product->get_price(),
				'sku'               => $product->get_sku(),
				'categories'        => wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) ),
				'tags'              => wp_strip_all_tags( wc_get_product_tag_list( $product->get_id() ) ),
			);

			foreach ( $config['types'] as $type => $is_enabled ) {
				if ( ! $is_enabled ) {
					continue;
				}

				try {
					$custom_instr = isset( $config['custom_instruction'] ) ? $config['custom_instruction'] : '';
					$prompt       = CatalogBench_Prompt_Builder::build( $product_data, $type, $config['tone'], $custom_instr );
					$provider     = CatalogBench_AI_Service::get_provider( $provider_key );
					$result       = $provider->generate( $prompt );

					if ( 'description' === $type ) {
						$product->set_description( wp_kses_post( $result ) );
						$product->save();
						update_post_meta( $product_id, '_catalogbench_optimized', time() );
					} elseif ( 'short_description' === $type ) {
						$product->set_short_description( wp_kses_post( $result ) );
						$product->save();
						update_post_meta( $product_id, '_catalogbench_optimized', time() );
					} elseif ( 'seo_title' === $type ) {
						update_post_meta( $product_id, '_yoast_wpseo_title', sanitize_text_field( $result ) );
						update_post_meta( $product_id, 'rank_math_title', sanitize_text_field( $result ) );
						update_post_meta( $product_id, '_catalogbench_optimized', time() );
					} elseif ( 'seo_meta' === $type ) {
						update_post_meta( $product_id, '_yoast_wpseo_metadesc', sanitize_text_field( $result ) );
						update_post_meta( $product_id, 'rank_math_description', sanitize_text_field( $result ) );
						update_post_meta( $product_id, '_catalogbench_optimized', time() );
					}
				} catch ( Exception $e ) {
					$job_data['errors']++;
					error_log( "CatalogBench Bulk Error for Product {$product_id}: " . $e->getMessage() );
				}
			}

			$job_data['completed']++;
		}

		// Save progress.
		set_transient( $job_id, $job_data, 7 * DAY_IN_SECONDS );

		// Schedule next batch.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'catalogbench_process_batch', array( $job_id, $batch_index + 1 ), 'catalogbench' );
		}
	}
}
