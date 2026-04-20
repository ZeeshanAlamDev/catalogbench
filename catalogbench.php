<?php
/**
 * Plugin Name:       CatalogBench – Bulk AI Product Editor for WooCommerce
 * Plugin URI:        https://catalogbench.com
 * Description:       Use your own AI API keys to bulk-generate and optimize WooCommerce product descriptions, SEO titles, and meta descriptions with visual preview.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            CatalogBench
 * Author URI:        https://catalogbench.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       catalogbench
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'CATALOGBENCH_VERSION', '1.0.0' );
define( 'CATALOGBENCH_PLUGIN_FILE', __FILE__ );
define( 'CATALOGBENCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CATALOGBENCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CATALOGBENCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check for WooCommerce dependency before bootstrapping.
 */
function catalogbench_check_requirements() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'catalogbench_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Admin notice when WooCommerce is not active.
 *
 * @return void
 */
function catalogbench_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: WooCommerce plugin name */
				esc_html__( '%1$sCatalogBench%2$s requires %1$sWooCommerce%2$s to be installed and active.', 'catalogbench' ),
				'<strong>',
				'</strong>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Activation hook.
 *
 * @return void
 */
function catalogbench_activate() {
	require_once CATALOGBENCH_PLUGIN_DIR . 'includes/class-activator.php';
	CatalogBench_Activator::activate();
}
register_activation_hook( __FILE__, 'catalogbench_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function catalogbench_deactivate() {
	require_once CATALOGBENCH_PLUGIN_DIR . 'includes/class-deactivator.php';
	CatalogBench_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'catalogbench_deactivate' );

/**
 * Declare WooCommerce HPOS compatibility.
 *
 * @return void
 */
function catalogbench_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'catalogbench_declare_hpos_compatibility' );

/**
 * Bootstrap the plugin after all plugins are loaded.
 *
 * @return void
 */
function catalogbench_init() {
	if ( ! catalogbench_check_requirements() ) {
		return;
	}

	require_once CATALOGBENCH_PLUGIN_DIR . 'includes/class-plugin.php';
	CatalogBench_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'catalogbench_init' );
