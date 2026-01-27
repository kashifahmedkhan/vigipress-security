<?php
/**
 * Hardening Module
 *
 * Applies WordPress security hardening measures.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/includes/modules
 */

namespace Vigil_Security\Modules;

/**
 * Hardening class.
 *
 * Handles security hardening features.
 */
class Hardening {

	/**
	 * Plugin settings.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array $settings Plugin settings.
	 */
	private $settings;

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->settings = get_option( 'vigil_security_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Hide WordPress version.
		if ( ! empty( $this->settings['hide_wp_version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'style_loader_src', array( $this, 'remove_version_from_assets' ), 9999 );
			add_filter( 'script_loader_src', array( $this, 'remove_version_from_assets' ), 9999 );
		}

		// Disable XML-RPC.
		if ( ! empty( $this->settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', array( $this, 'remove_xmlrpc_header' ) );
		}

		// Disable file editing.
		if ( ! empty( $this->settings['disable_file_edit'] ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}

		// Block user enumeration.
		if ( ! empty( $this->settings['disable_user_enumeration'] ) ) {
			add_action( 'template_redirect', array( $this, 'block_user_enumeration' ) );
			add_filter( 'rest_authentication_errors', array( $this, 'block_user_rest_endpoint' ) );
		}

		// Add security headers.
		if ( ! empty( $this->settings['enable_security_headers'] ) ) {
			add_action( 'send_headers', array( $this, 'add_security_headers' ) );
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
	 * Remove X-Pingback header.
	 *
	 * @since 1.0.0
	 * @param array $headers HTTP headers.
	 * @return array Modified headers.
	 */
	public function remove_xmlrpc_header( $headers ) {
		if ( isset( $headers['X-Pingback'] ) ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
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
	}

	/**
	 * Block user REST API endpoint for unauthenticated requests only.
	 *
	 * @since 1.0.0
	 * @param \WP_Error|null|bool $result Error from previous authentication handler.
	 * @return \WP_Error|null|bool Authentication result.
	 */
	public function block_user_rest_endpoint( $result ) {
		// Allow if user is logged in and is admin.
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return $result;
		}

		global $wp;

		if ( isset( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], '/wp/v2/users' ) !== false ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Forbidden', 'vigil-security' ),
				array( 'status' => 403 )
			);
		}

		return $result;
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

	/**
	 * Get list of hardening recommendations.
	 *
	 * @since 1.0.0
	 * @return array List of recommendations with status.
	 */
	public function get_recommendations() {
		$recommendations = array();

		// XML-RPC check.
		$recommendations[] = array(
			'id'          => 'disable_xmlrpc',
			'title'       => __( 'Disable XML-RPC', 'vigil-security' ),
			'description' => __( 'Prevents brute force attacks via XML-RPC', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['disable_xmlrpc'] ),
			'safe'        => true, // Safe to auto-enable.
		);

		// File editing check.
		$recommendations[] = array(
			'id'          => 'disable_file_edit',
			'title'       => __( 'Disable File Editing', 'vigil-security' ),
			'description' => __( 'Prevents editing theme/plugin files from admin', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['disable_file_edit'] ) || defined( 'DISALLOW_FILE_EDIT' ),
			'safe'        => true,
		);

		// Hide WP version check.
		$recommendations[] = array(
			'id'          => 'hide_wp_version',
			'title'       => __( 'Hide WordPress Version', 'vigil-security' ),
			'description' => __( 'Removes version number from page source', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['hide_wp_version'] ),
			'safe'        => true,
		);

		// User enumeration check.
		$recommendations[] = array(
			'id'          => 'disable_user_enumeration',
			'title'       => __( 'Block User Enumeration', 'vigil-security' ),
			'description' => __( 'Prevents discovering usernames via URL', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['disable_user_enumeration'] ),
			'safe'        => true,
		);

		// Security headers check.
		$recommendations[] = array(
			'id'          => 'enable_security_headers',
			'title'       => __( 'Enable Security Headers', 'vigil-security' ),
			'description' => __( 'Adds protective HTTP headers', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['enable_security_headers'] ),
			'safe'        => true,
		);

		// Login protection check.
		$recommendations[] = array(
			'id'          => 'login_protection_enabled',
			'title'       => __( 'Enable Login Protection', 'vigil-security' ),
			'description' => __( 'Blocks brute force login attempts', 'vigil-security' ),
			'enabled'     => ! empty( $this->settings['login_protection_enabled'] ),
			'safe'        => true,
		);

		return $recommendations;
	}
}