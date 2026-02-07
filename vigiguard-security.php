<?php
/**
 * Plugin Name:       VigiGuard Security
 * Plugin URI:        https://wordpress.org/plugins/vigiguard-security
 * Description:       Simple, one-click WordPress security hardening for non-technical users. Protect your site without reading a manual.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            VigiGuard Security Team
 * Author URI:        https://profiles.wordpress.org/kashifahmedkhan/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vigiguard-security
 * Domain Path:       /languages
 *
 * @package VigiGuard_Security
 * @since      1.0.0
 * 
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
function vigiguard_security_check_requirements() {
	global $wp_version;

	// Check WordPress version.
	if ( version_compare( $wp_version, VIGIL_SECURITY_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'vigiguard_security_wp_version_notice' );
		return false;
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, VIGIL_SECURITY_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'vigiguard_security_php_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display WordPress version incompatibility notice.
 */
function vigiguard_security_wp_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version */
				esc_html__( 'VigiGuard Security requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress.', 'vigiguard-security' ),
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
function vigiguard_security_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'VigiGuard Security requires PHP version %1$s or higher. You are running version %2$s. Please contact your hosting provider.', 'vigiguard-security' ),
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
if ( ! vigiguard_security_check_requirements() ) {
	return;
}

/**
 * The code that runs during plugin activation.
 */
function vigiguard_security_activate() {
	require_once VIGIL_SECURITY_PATH . 'includes/class-vigiguard-activator.php';
	VigiGuard_Security\VigiGuard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function vigiguard_security_deactivate() {
	require_once VIGIL_SECURITY_PATH . 'includes/class-vigiguard-deactivator.php';
	VigiGuard_Security\VigiGuard_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'vigiguard_security_activate' );
register_deactivation_hook( __FILE__, 'vigiguard_security_deactivate' );

/**
 * The core plugin class.
 */
require_once VIGIL_SECURITY_PATH . 'includes/class-vigiguard-core.php';


/**
 * Begin execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function vigiguard_security_run() {
	$plugin = new VigiGuard_Security\VigiGuard_Core();
	$plugin->run();
}

vigiguard_security_run();