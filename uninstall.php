<?php
/**
 * Uninstall handler.
 *
 * Removes all plugin data when the plugin is deleted via WP admin.
 *
 * @package CatalogBench
 */

// Abort if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'catalogbench_settings' );
delete_option( 'catalogbench_version' );

// Remove all plugin transients.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_cb_preview_' ) . '%',
		$wpdb->esc_like( '_transient_cb_bulk_' ) . '%'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_cb_preview_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_cb_bulk_' ) . '%'
	)
);

// Cancel any remaining scheduled actions.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'catalogbench_process_batch' );
}
