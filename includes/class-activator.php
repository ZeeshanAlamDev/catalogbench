<?php
/**
 * Plugin activator.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Activator
 *
 * Runs on plugin activation to set default options.
 */
class CatalogBench_Activator {

	/**
	 * Activation routine.
	 *
	 * Sets default plugin settings if they don't exist.
	 *
	 * @return void
	 */
	public static function activate() {
		$existing = get_option( 'catalogbench_settings' );

		if ( false === $existing ) {
			$defaults = array(
				'gemini_key'       => '',
				'openai_key'       => '',
				'openai_model'     => 'gpt-5.4-mini',
				'default_provider' => 'gemini',
				'default_tone'     => 'professional',
				'batch_size'       => 5,
				'batch_delay'      => 2,
				'seo_sync'         => true,
				'prompt_template'  => '',
				'max_length'       => array(
					'description'      => 300,
					'short_description' => 60,
					'seo_title'        => 60,
					'seo_meta'         => 160,
				),
			);

			add_option( 'catalogbench_settings', $defaults, '', 'no' );
		}

		// Store installed version for future migrations.
		update_option( 'catalogbench_version', CATALOGBENCH_VERSION, 'no' );
	}
}
