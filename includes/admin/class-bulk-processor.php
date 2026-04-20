<?php
/**
 * Handles background bulk processing using WooCommerce Action Scheduler.
 */

class CatalogBench_Bulk_Processor {

	/**
	 * Hook registered actions and filters.
	 */
	public function register_hooks() {
		add_action( 'catalogbench_process_batch', array( $this, 'process_batch' ), 10, 2 );
	}

	/**
	 * Initiate a new bulk job.
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

		// Schedule first batch
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'catalogbench_process_batch', array( $job_id, 0 ), 'catalogbench' );
		}

		return $job_id;
	}

	/**
	 * Process a specific batch of a job.
	 */
	public function process_batch( $job_id, $batch_index ) {
		$job_data = get_transient( $job_id );
		if ( ! $job_data || 'processing' !== $job_data['status'] ) {
			return; // Job cancelled or missing
		}

		$batch_size = 5;
		$start_idx  = $batch_index * $batch_size;
		$targets    = array_slice( $job_data['products'], $start_idx, $batch_size );

		if ( empty( $targets ) ) {
			// Job complete
			$job_data['status'] = 'completed';
			set_transient( $job_id, $job_data, 7 * DAY_IN_SECONDS );
			return;
		}

		$config = $job_data['config'];

		foreach ( $targets as $product_id ) {
			// Process each requested content type
			foreach ( $config['types'] as $type => $is_enabled ) {
				if ( ! $is_enabled ) {
					continue;
				}

				try {
					$prompt = CatalogBench_Prompt_Builder::build( $product_id, $type, $config['tone'], $config['custom_instruction'] );
					$provider = CatalogBench_AI_Service::get_provider();
					$result   = $provider->generate( $prompt );

					// Auto-apply logic in bulk mode
					$product = wc_get_product( $product_id );
					if ( $product ) {
						if ( 'description' === $type ) {
							$product->set_description( wp_kses_post( $result ) );
							$product->save();
						} elseif ( 'short_description' === $type ) {
							$product->set_short_description( wp_kses_post( $result ) );
							$product->save();
						} elseif ( 'seo_title' === $type ) {
							update_post_meta( $product_id, '_yoast_wpseo_title', sanitize_text_field( $result ) );
							update_post_meta( $product_id, 'rank_math_title', sanitize_text_field( $result ) );
						} elseif ( 'seo_meta' === $type ) {
							update_post_meta( $product_id, '_yoast_wpseo_metadesc', sanitize_text_field( $result ) );
							update_post_meta( $product_id, 'rank_math_description', sanitize_text_field( $result ) );
						}
					}
				} catch ( Exception $e ) {
					$job_data['errors']++;
					error_log( "CatalogBench Bulk Error for Product {$product_id}: " . $e->getMessage() );
				}
			}

			$job_data['completed']++;
		}

		// Save progress
		set_transient( $job_id, $job_data, 7 * DAY_IN_SECONDS );

		// Schedule next batch immediately
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'catalogbench_process_batch', array( $job_id, $batch_index + 1 ), 'catalogbench' );
		}
	}
}
