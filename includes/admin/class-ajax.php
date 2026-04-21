<?php
/**
 * AJAX endpoints for CatalogBench.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Ajax
 */
class CatalogBench_Ajax {

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Generic wrapper endpoints
		add_action( 'wp_ajax_catalogbench_test_api_key', array( $this, 'test_api_key' ) );
		add_action( 'wp_ajax_catalogbench_get_product_data', array( $this, 'get_product_data' ) );
		add_action( 'wp_ajax_catalogbench_generate_single', array( $this, 'generate_single' ) );
		add_action( 'wp_ajax_catalogbench_apply_single', array( $this, 'apply_single' ) );
		add_action( 'wp_ajax_catalogbench_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_catalogbench_get_settings', array( $this, 'get_settings' ) );
		
		// Bulk endpoints
		add_action( 'wp_ajax_catalogbench_get_products', array( $this, 'get_products' ) );
		add_action( 'wp_ajax_catalogbench_start_bulk_job', array( $this, 'start_bulk_job' ) );
		add_action( 'wp_ajax_catalogbench_get_bulk_status', array( $this, 'get_bulk_status' ) );
	}

	/**
	 * Verify standard nonces and permissions.
	 */
	private function check_permissions() {
		check_ajax_referer( 'catalogbench_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'catalogbench' ) ), 403 );
		}
	}

	/**
	 * Save plugin settings.
	 */
	public function save_settings() {
		$this->check_permissions();

		$settings = get_option( 'catalogbench_settings', array() );

		if ( ! empty( $_POST['gemini_key'] ) ) {
			require_once CATALOGBENCH_PLUGIN_DIR . 'includes/utils/class-encryption.php';
			$settings['gemini_key'] = CatalogBench_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['gemini_key'] ) ) );
		}

		if ( ! empty( $_POST['openai_key'] ) ) {
			require_once CATALOGBENCH_PLUGIN_DIR . 'includes/utils/class-encryption.php';
			$settings['openai_key'] = CatalogBench_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['openai_key'] ) ) );
		}

		if ( ! empty( $_POST['openai_model'] ) ) {
			$settings['openai_model'] = sanitize_text_field( wp_unslash( $_POST['openai_model'] ) );
		}

		if ( ! empty( $_POST['gemini_model'] ) ) {
			$settings['gemini_model'] = sanitize_text_field( wp_unslash( $_POST['gemini_model'] ) );
		}

		if ( ! empty( $_POST['selected_provider'] ) ) {
			$settings['selected_provider'] = sanitize_text_field( wp_unslash( $_POST['selected_provider'] ) );
		}

		update_option( 'catalogbench_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'catalogbench' ) ) );
	}

	/**
	 * Get plugin settings securely.
	 */
	public function get_settings() {
		$this->check_permissions();

		$settings = get_option( 'catalogbench_settings', array() );
		
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/utils/class-encryption.php';

		$response = array(
			'gemini_key'        => ! empty( $settings['gemini_key'] ) ? CatalogBench_Encryption::decrypt( $settings['gemini_key'] ) : '',
			'openai_key'        => ! empty( $settings['openai_key'] ) ? CatalogBench_Encryption::decrypt( $settings['openai_key'] ) : '',
			'openai_model'      => ! empty( $settings['openai_model'] ) ? $settings['openai_model'] : 'gpt-5.4-mini',
			'gemini_model'      => ! empty( $settings['gemini_model'] ) ? $settings['gemini_model'] : 'gemini-2.0-flash',
			'selected_provider' => ! empty( $settings['selected_provider'] ) ? $settings['selected_provider'] : 'gemini',
		);

		wp_send_json_success( $response );
	}

	/**
	 * Test the active API key connection.
	 */
	public function test_api_key() {
		$this->check_permissions();

		$provider_key = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
		
		if ( empty( $provider_key ) ) { // Could be coming in via JSON payload from Axios
			$payload      = json_decode( trim( file_get_contents( 'php://input' ) ), true );
			$provider_key = isset( $payload['provider'] ) ? sanitize_text_field( $payload['provider'] ) : '';
		}

		if ( empty( $provider_key ) ) {
			wp_send_json_error( array( 'message' => __( 'No provider specified.', 'catalogbench' ) ) );
		}

		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-ai-service.php';

		try {
			$provider = CatalogBench_AI_Service::get_provider( $provider_key );
			$success  = $provider->test_connection();

			if ( $success ) {
				wp_send_json_success( array( 'message' => sprintf( __( '%s connected successfully.', 'catalogbench' ), ucfirst( $provider_key ) ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Connection failed. Ensure your API key is correct and active.', 'catalogbench' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Generate content for a single product.
	 */
	public function generate_single() {
		$this->check_permissions();

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'description';
		$tone         = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$custom_instr = isset( $_POST['custom_instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_instruction'] ) ) : '';

		if ( 0 === $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Product ID.', 'catalogbench' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'catalogbench' ) ) );
		}

		// Gather product data
		$product_data = array(
			'title'             => $product->get_name(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'price'             => wc_price( wc_get_price_to_display( $product ) ),
			'sku'               => $product->get_sku(),
			'categories'        => wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) ),
			'tags'              => wp_strip_all_tags( wc_get_product_tag_list( $product->get_id() ) ),
		);

		if ( $product->is_type( 'variable' ) ) {
			$attrs = $product->get_attributes();
			$attr_texts = array();
			foreach ( $attrs as $attr ) {
				$attr_texts[] = $attr->get_name() . ': ' . implode( ', ', $attr->get_options() );
			}
			$product_data['attributes'] = join( ' | ', $attr_texts );
		}

		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-prompt-builder.php';
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/services/class-ai-service.php';

		$prompt = CatalogBench_Prompt_Builder::build( $product_data, $content_type, $tone, $custom_instr );

		$settings = get_option( 'catalogbench_settings', array() );
		$provider_key = isset( $settings['selected_provider'] ) ? $settings['selected_provider'] : 'gemini';

		try {
			$provider = CatalogBench_AI_Service::get_provider( $provider_key );
			$result   = $provider->generate( $prompt );
			
			wp_send_json_success( array(
				'content' => $result
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Apply generated content to a specific product.
	 */
	public function apply_single() {
		$this->check_permissions();

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';
		$content      = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( 0 === $product_id || empty( $content_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Product ID or Content Type.', 'catalogbench' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'catalogbench' ) ) );
		}

		try {
			switch ( $content_type ) {
				case 'description':
					$product->set_description( $content );
					break;
				case 'short_description':
					$product->set_short_description( $content );
					break;
				case 'seo_title':
					update_post_meta( $product_id, '_yoast_wpseo_title', $content );
					update_post_meta( $product_id, 'rank_math_title', $content );
					break;
				case 'seo_meta':
					update_post_meta( $product_id, '_yoast_wpseo_metadesc', $content );
					update_post_meta( $product_id, 'rank_math_description', $content );
					break;
				default:
					throw new Exception( __( 'Unknown content type.', 'catalogbench' ) );
			}

			// Core standard WC save
			if ( in_array( $content_type, array( 'description', 'short_description' ), true ) ) {
				$product->save();
			}

			wp_send_json_success( array( 'message' => __( 'Product updated successfully.', 'catalogbench' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Get current product data for the Single Generator UI.
	 */
	public function get_product_data() {
		$this->check_permissions();

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Valid Product ID is required.', 'catalogbench' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'catalogbench' ) ) );
		}

		$data = array(
			'id'                => $product->get_id(),
			'title'             => $product->get_name(),
			'sku'               => $product->get_sku(),
			'price'             => wc_price( wc_get_price_to_display( $product ) ),
			'description'       => wp_kses_post( $product->get_description() ),
			'short_description' => wp_kses_post( $product->get_short_description() ),
			'seo_title'         => get_post_meta( $product_id, '_yoast_wpseo_title', true ),
			'seo_meta'          => get_post_meta( $product_id, '_yoast_wpseo_metadesc', true ),
		);

		// Fallback to Rank Math if Yoast lacks data
		if ( empty( $data['seo_title'] ) ) {
			$data['seo_title'] = get_post_meta( $product_id, 'rank_math_title', true );
		}
		if ( empty( $data['seo_meta'] ) ) {
			$data['seo_meta'] = get_post_meta( $product_id, 'rank_math_description', true );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Get products for the Bulk Generator table.
	 */
	public function get_products() {
		$this->check_permissions();

		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$args = array(
			'status'   => 'publish',
			'limit'    => 50,
			'page'     => $page,
			'paginate' => true,
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( ! empty( $category ) ) {
			$args['category'] = array( $category );
		}

		$products_result = wc_get_products( $args );

		$products = array();
		foreach ( $products_result->products as $product ) {
			$products[] = array(
				'id'        => $product->get_id(),
				'name'      => $product->get_name(),
				'sku'       => $product->get_sku(),
				'has_desc'  => ! empty( $product->get_description() ),
				'has_short' => ! empty( $product->get_short_description() ),
			);
		}

		wp_send_json_success( array(
			'products'      => $products,
			'total_pages'   => $products_result->max_num_pages,
			'total_results' => $products_result->total,
		) );
	}

	/**
	 * Starts a new Action Scheduler bulk AI job.
	 */
	public function start_bulk_job() {
		$this->check_permissions();

		$raw_ids = isset( $_POST['product_ids'] ) ? $_POST['product_ids'] : '';
		if ( is_string( $raw_ids ) ) {
			$raw_ids = json_decode( wp_unslash( $raw_ids ), true );
		}
		$product_ids = is_array( $raw_ids ) ? array_map( 'absint', $raw_ids ) : array();

		$raw_config = isset( $_POST['config'] ) ? $_POST['config'] : '';
		if ( is_string( $raw_config ) ) {
			$raw_config = json_decode( wp_unslash( $raw_config ), true );
		}
		$config = is_array( $raw_config ) ? $raw_config : array();

		if ( empty( $product_ids ) || empty( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing products or configuration.', 'catalogbench' ) ) );
		}

		// Enforce documented 500-product limit.
		if ( count( $product_ids ) > 500 ) {
			wp_send_json_error( array( 'message' => __( 'Maximum 500 products per bulk job.', 'catalogbench' ) ) );
		}

		$job_id = CatalogBench_Bulk_Processor::start_job( $product_ids, $config );
		wp_send_json_success( array( 'job_id' => $job_id ) );
	}

	/**
	 * Polls the current status of an ongoing bulk job.
	 */
	public function get_bulk_status() {
		$this->check_permissions();

		$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
		if ( empty( $job_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Job ID required.', 'catalogbench' ) ) );
		}

		$status = get_transient( $job_id );
		if ( false === $status ) {
			wp_send_json_error( array( 'message' => __( 'Job not found or expired.', 'catalogbench' ) ) );
		}

		wp_send_json_success( $status );
	}
}
