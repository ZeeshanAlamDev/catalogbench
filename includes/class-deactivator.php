<?php
/**
 * Plugin deactivator.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Deactivator
 *
 * Runs on plugin deactivation to clean up scheduled actions.
 */
class CatalogBench_Deactivator {

	/**
	 * Deactivation routine.
	 *
	 * Cancels all pending Action Scheduler actions owned by this plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'catalogbench_process_batch' );
		}
	}
}
