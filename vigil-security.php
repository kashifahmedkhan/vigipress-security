<?php
/**
 * Plugin Name:       Vigil Security
 * Plugin URI:        https://wordpress.org/plugins/vigil-security
 * Description:       Simple, one-click WordPress security hardening for non-technical users. Protect your site without reading a manual.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vigil-security
 * Domain Path:       /languages
 *
 * @package Vigil_Security
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Uses SemVer - https://semver.org
 */
define( 'VIGIL_SECURITY_VERSION', '1.0.0' );

/**
 * Plugin root directory path.
 */
define( 'VIGIL_SECURITY_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin root directory URL.
 */
define( 'VIGIL_SECURITY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'VIGIL_SECURITY_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum WordPress version required.
 */
define( 'VIGIL_SECURITY_MIN_WP_VERSION', '5.8' );

/**
 * Minimum PHP version required.
 */
define( 'VIGIL_SECURITY_MIN_PHP_VERSION', '7.4' );

/**
 * Check WordPress and PHP version compatibility before loading plugin.
 */
function vigil_security_check_requirements() {
	global $wp_version;

	// Check WordPress version.
	if ( version_compare( $wp_version, VIGIL_SECURITY_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'vigil_security_wp_version_notice' );
		return false;
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, VIGIL_SECURITY_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'vigil_security_php_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display WordPress version incompatibility notice.
 */
function vigil_security_wp_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version */
				esc_html__( 'Vigil Security requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress.', 'vigil-security' ),
				esc_html( VIGIL_SECURITY_MIN_WP_VERSION ),
				esc_html( $GLOBALS['wp_version'] )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display PHP version incompatibility notice.
 */
function vigil_security_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'Vigil Security requires PHP version %1$s or higher. You are running version %2$s. Please contact your hosting provider.', 'vigil-security' ),
				esc_html( VIGIL_SECURITY_MIN_PHP_VERSION ),
				esc_html( PHP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Only proceed if requirements are met.
 */
if ( ! vigil_security_check_requirements() ) {
	return;
}

/**
 * The code that runs during plugin activation.
 */
function vigil_security_activate() {
	require_once VIGIL_SECURITY_PATH . 'includes/class-vigil-activator.php';
	Vigil_Security\Vigil_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function vigil_security_deactivate() {
	require_once VIGIL_SECURITY_PATH . 'includes/class-vigil-deactivator.php';
	Vigil_Security\Vigil_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'vigil_security_activate' );
register_deactivation_hook( __FILE__, 'vigil_security_deactivate' );

/**
 * The core plugin class.
 */
require_once VIGIL_SECURITY_PATH . 'includes/class-vigil-core.php';

/**
 * Begin execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function vigil_security_run() {
	$plugin = new Vigil_Security\Vigil_Core();
	$plugin->run();
}

vigil_security_run();