<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/includes
 */

namespace Vigil_Security;

/**
 * The core plugin class.
 *
 * This is used to define admin hooks and module initialization.
 */
class Vigil_Core {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Array to store loaded modules.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array $modules Loaded plugin modules.
	 */
	private $modules = array();

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'vigil-security';
		$this->version     = VIGIL_SECURITY_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// Load module files with existence check.
		$login_protection_file = VIGIL_SECURITY_PATH . 'includes/modules/class-login-protection.php';
		$hardening_file        = VIGIL_SECURITY_PATH . 'includes/modules/class-hardening.php';

		$file_integrity_file   = VIGIL_SECURITY_PATH . 'includes/modules/class-file-integrity.php';
		$uninstaller_file      = VIGIL_SECURITY_PATH . 'includes/class-vigil-uninstaller.php';  // Add this line

		$file_integrity_file   = VIGIL_SECURITY_PATH . 'includes/modules/class-file-integrity.php';  
		
		if ( file_exists( $login_protection_file ) ) {
			require_once $login_protection_file;
		}
		
		if ( file_exists( $hardening_file ) ) {
			require_once $hardening_file;
		}
		

		if ( file_exists( $file_integrity_file ) ) {
			require_once $file_integrity_file;
		}
		
		if ( file_exists( $uninstaller_file ) ) {
			require_once $uninstaller_file;
		}

		if ( file_exists( $file_integrity_file ) ) {  // Add this
			require_once $file_integrity_file;
		}


		// Load admin class.
		require_once VIGIL_SECURITY_PATH . 'admin/class-vigil-admin.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'vigil-security',
			false,
			dirname( VIGIL_SECURITY_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Register all hooks related to the admin area.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		// Show activation notice.
		add_action( 'admin_notices', array( $this, 'show_activation_notice' ) );

		// Add settings link on plugins page.
		add_filter( 'plugin_action_links_' . VIGIL_SECURITY_BASENAME, array( $this, 'add_action_links' ) );

		// Initialize admin class.
		if ( is_admin() ) {
			$admin = new \Vigil_Security\Admin\Vigil_Admin( $this->plugin_name, $this->version );
			add_action( 'admin_menu', array( $admin, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Show welcome notice after plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function show_activation_notice() {
		// Only show if activation transient exists.
		if ( ! get_transient( 'vigil_security_activated' ) ) {
			return;
		}

		// Delete transient so notice only shows once.
		delete_transient( 'vigil_security_activated' );

		// Only show to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Vigil Security is now active!', 'vigil-security' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Your site is currently running with default (safe) security settings. The Security Dashboard will be available shortly.', 'vigil-security' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add "Settings" link to plugin row on Plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=vigil-security' ) ) . '">' . esc_html__( 'Settings', 'vigil-security' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Run the plugin.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		// Initialize modules.
		$this->init_modules();
	}

	/**
	 * Initialize security modules.
	 *
	 * @since 1.0.0
	 */
	private function init_modules() {
		// Initialize login protection (only if class exists).
		if ( class_exists( '\Vigil_Security\Modules\Login_Protection' ) ) {
			new \Vigil_Security\Modules\Login_Protection();
		}

		// Initialize hardening (only if class exists).
		if ( class_exists( '\Vigil_Security\Modules\Hardening' ) ) {
			new \Vigil_Security\Modules\Hardening();
		}

		// Initialize file integrity (only if class exists).  // Add this
		if ( class_exists( '\Vigil_Security\Modules\File_Integrity' ) ) {
			new \Vigil_Security\Modules\File_Integrity();
		}
	}


	/**
	 * Remove WordPress version from asset URLs.
	 *
	 * @since 1.0.0
	 * @param string $src Asset URL.
	 * @return string Modified asset URL.
	 */
	public function remove_version_from_assets( $src ) {
		if ( strpos( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Block user enumeration attempts.
	 *
	 * @since 1.0.0
	 */
	public function block_user_enumeration() {
		// Check if someone is trying to enumerate users.
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Block ?author=N queries.
		if ( preg_match( '/author=([0-9]*)/i', $request_uri ) ) {
			wp_die( esc_html__( 'Forbidden', 'vigil-security' ), 403 );
		}

		// Also block REST API user endpoints.
		global $wp;
		if ( isset( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], '/wp/v2/users' ) !== false ) {
			wp_die( esc_html__( 'Forbidden', 'vigil-security' ), 403 );
		}
	}

	/**
	 * Add security headers to HTTP responses.
	 *
	 * @since 1.0.0
	 */
	public function add_security_headers() {
		// Prevent clickjacking.
		header( 'X-Frame-Options: SAMEORIGIN' );

		// Enable XSS protection.
		header( 'X-XSS-Protection: 1; mode=block' );

		// Prevent MIME type sniffing.
		header( 'X-Content-Type-Options: nosniff' );

		// Referrer policy.
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );

		// Content Security Policy (basic).
		header( "Content-Security-Policy: frame-ancestors 'self'" );
	}
}