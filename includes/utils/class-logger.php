<?php
/**
 * Simple debug logger using WP_DEBUG_LOG.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

class CatalogBench_Logger {

	/**
	 * Log a message to WP_DEBUG_LOG if debugging is enabled.
	 *
	 * @param mixed  $message The message or data to log.
	 * @param string $level   Log level (info, warning, error).
	 */
	public static function log( $message, $level = 'info' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$prefix = '[CatalogBench ' . strtoupper( $level ) . '] ';
			
			if ( is_array( $message ) || is_object( $message ) ) {
				$message = print_r( $message, true );
			}
			
			error_log( $prefix . $message );
		}
	}
}
