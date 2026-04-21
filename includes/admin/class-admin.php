<?php
/**
 * Admin page registration and asset enqueuing.
 *
 * @package CatalogBench
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CatalogBench_Admin
 *
 * Registers the admin menu page and enqueues the Vue.js SPA assets.
 */
class CatalogBench_Admin {

	/**
	 * The admin page hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Register all admin hooks from a single location.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the top-level admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		$this->hook_suffix = add_menu_page(
			__( 'CatalogBench', 'catalogbench' ),
			__( 'CatalogBench', 'catalogbench' ),
			'manage_woocommerce',
			'catalogbench',
			array( $this, 'render_admin_page' ),
			'dashicons-text-page',
			58
		);
	}

	/**
	 * Render the admin page container for the Vue.js SPA.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div class="wrap catalogbench-wrap">
			<div id="catalogbench-app"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue Vue.js built assets only on the plugin admin page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		$dist_path = CATALOGBENCH_PLUGIN_DIR . 'admin-ui/dist/';
		$dist_url  = CATALOGBENCH_PLUGIN_URL . 'admin-ui/dist/';

		// Shell CSS for the WordPress admin wrapper.
		wp_enqueue_style(
			'catalogbench-admin-shell',
			CATALOGBENCH_PLUGIN_URL . 'assets/css/admin-shell.css',
			array(),
			CATALOGBENCH_VERSION
		);

		// Vue.js built CSS — enqueue all CSS chunks from the dist folder.
		$css_files = glob( $dist_path . '*.css' );
		if ( $css_files ) {
			foreach ( $css_files as $index => $css_file ) {
				$handle = 'catalogbench-admin' . ( $index > 0 ? '-' . $index : '' );
				wp_enqueue_style(
					$handle,
					$dist_url . basename( $css_file ),
					$index === 0 ? array( 'catalogbench-admin-shell' ) : array(),
					CATALOGBENCH_VERSION
				);
			}
		}

		// Vue.js built JS.
		if ( file_exists( $dist_path . 'catalogbench-admin.js' ) ) {
			wp_enqueue_script(
				'catalogbench-admin',
				$dist_url . 'catalogbench-admin.js',
				array(),
				CATALOGBENCH_VERSION,
				array( 'strategy' => 'defer' )
			);

			// Pass data to the Vue.js app.
			wp_localize_script(
				'catalogbench-admin',
				'catalogbenchData',
				$this->get_localized_data()
			);
		}
	}

	/**
	 * Build the localized data array passed to the Vue.js app.
	 *
	 * @return array
	 */
	private function get_localized_data() {
		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'catalogbench_nonce' ),
			'version'   => CATALOGBENCH_VERSION,
			'isPro'     => false, // Updated by Freemius integration in Phase 6.
			'adminUrl'  => admin_url(),
			'pluginUrl' => CATALOGBENCH_PLUGIN_URL,
		);
	}
}
