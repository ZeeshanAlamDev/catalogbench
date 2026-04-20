<?php
/**
 * Main plugin orchestrator.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Plugin
 *
 * Singleton that bootstraps all plugin functionality.
 */
class CatalogBench_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var CatalogBench_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return CatalogBench_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — private to enforce singleton.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load required files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/admin/class-admin.php';
		require_once CATALOGBENCH_PLUGIN_DIR . 'includes/admin/class-ajax.php';
	}

	/**
	 * Register all hooks from a single location.
	 *
	 * @return void
	 */
	private function register_hooks() {
		$admin = new CatalogBench_Admin();
		$admin->register_hooks();

		$ajax = new CatalogBench_Ajax();
		$ajax->register_hooks();

		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'catalogbench',
			false,
			dirname( CATALOGBENCH_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
