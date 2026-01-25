<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/admin
 */

namespace Vigil_Security\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin menu and settings.
 */
class Vigil_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Plugin settings array.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array $settings Current plugin settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->settings    = get_option( 'vigil_security_settings', array() );
	}

	/**
	 * Register the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Vigil Security', 'vigil-security' ),           // Page title.
			__( 'Vigil Security', 'vigil-security' ),           // Menu title.
			'manage_options',                                    // Capability.
			'vigil-security',                                    // Menu slug.
			array( $this, 'display_dashboard_page' ),           // Callback function.
			'dashicons-shield',                                  // Icon.
			80                                                   // Position (below Settings).
		);

		// Add submenu pages.
		add_submenu_page(
			'vigil-security',
			__( 'Dashboard', 'vigil-security' ),
			__( 'Dashboard', 'vigil-security' ),
			'manage_options',
			'vigil-security',
			array( $this, 'display_dashboard_page' )
		);

		add_submenu_page(
			'vigil-security',
			__( 'Settings', 'vigil-security' ),
			__( 'Settings', 'vigil-security' ),
			'manage_options',
			'vigil-security-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'vigil-security',
			__( 'Activity Log', 'vigil-security' ),
			__( 'Activity Log', 'vigil-security' ),
			'manage_options',
			'vigil-security-logs',
			array( $this, 'display_logs_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Only load on our plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'vigil-security' ) === false ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			VIGIL_SECURITY_URL . 'admin/css/vigil-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Only load on our plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'vigil-security' ) === false ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			VIGIL_SECURITY_URL . 'admin/js/vigil-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Pass data to JavaScript.
		wp_localize_script(
			$this->plugin_name,
			'vigilSecurity',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vigil_security_nonce' ),
			)
		);
	}

	/**
	 * Display the main dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigil-security' ) );
		}

		// Calculate current health score.
		$health_score = $this->calculate_health_score();

		// Get health grade and color.
		$health_data = $this->get_health_grade( $health_score );

		// Get security issues.
		$issues = $this->get_security_issues();

		// Load dashboard view.
		include VIGIL_SECURITY_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigil-security' ) );
		}

		// Handle form submission.
		if ( isset( $_POST['vigil_security_settings_nonce'] ) ) {
			$this->save_settings();
		}

		// Add helpful notice about security headers.
		$settings = get_option( 'vigil_security_settings', array() );
		if ( ! empty( $settings['enable_security_headers'] ) ) {
			?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Security Headers Active!', 'vigil-security' ); ?></strong>
					<?php esc_html_e( 'Security headers are being sent on all frontend pages. To verify, open your homepage (not admin) and check browser DevTools → Network → Response Headers.', 'vigil-security' ); ?>
				</p>
			</div>
			<?php
		}

		// Load settings view.
		include VIGIL_SECURITY_PATH . 'admin/views/settings.php';
	}

	/**
	 * Display the activity log page.
	 *
	 * @since 1.0.0
	 */
	public function display_logs_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigil-security' ) );
		}

		// Get logs from database.
		$logs = $this->get_activity_logs();

		// Load logs view.
		include VIGIL_SECURITY_PATH . 'admin/views/logs.php';
	}

	/**
	 * Calculate security health score (0-100).
	 *
	 * @since 1.0.0
	 * @return int Health score.
	 */
	private function calculate_health_score() {
		$score  = 0;
		$checks = 10; // Total number of checks.

		// Check 1: XML-RPC disabled (10 points).
		if ( ! empty( $this->settings['disable_xmlrpc'] ) ) {
			$score += 10;
		}

		// Check 2: File editing disabled (10 points).
		if ( ! empty( $this->settings['disable_file_edit'] ) || defined( 'DISALLOW_FILE_EDIT' ) ) {
			$score += 10;
		}

		// Check 3: WP version hidden (10 points).
		if ( ! empty( $this->settings['hide_wp_version'] ) ) {
			$score += 10;
		}

		// Check 4: Login protection active (10 points).
		if ( ! empty( $this->settings['login_protection_enabled'] ) ) {
			$score += 10;
		}

		// Check 5: Security headers enabled (10 points).
		if ( ! empty( $this->settings['enable_security_headers'] ) ) {
			$score += 10;
		}

		// Check 6: SSL on admin (10 points).
		if ( is_ssl() || defined( 'FORCE_SSL_ADMIN' ) ) {
			$score += 10;
		}

		// Check 7: Database prefix changed (10 points).
		global $wpdb;
		if ( $wpdb->prefix !== 'wp_' ) {
			$score += 10;
		}

		// Check 8: No unused admin accounts (10 points).
		$admin_users = get_users( array( 'role' => 'administrator' ) );
		if ( count( $admin_users ) <= 2 ) {
			$score += 10;
		}

		// Check 9: File integrity enabled (10 points).
		if ( ! empty( $this->settings['file_integrity_enabled'] ) ) {
			$score += 10;
		}

		// Check 10: Plugins up to date (10 points).
		$update_plugins = get_site_transient( 'update_plugins' );
		if ( empty( $update_plugins->response ) ) {
			$score += 10;
		}

		// Update stored score.
		$this->settings['health_score']            = $score;
		$this->settings['health_score_last_check'] = current_time( 'timestamp' );
		update_option( 'vigil_security_settings', $this->settings );

		return $score;
	}

	/**
	 * Get health grade and color based on score.
	 *
	 * @since 1.0.0
	 * @param int $score Health score.
	 * @return array Grade data (grade, color, status).
	 */
	private function get_health_grade( $score ) {
		if ( $score >= 90 ) {
			return array(
				'grade'  => 'A',
				'color'  => '#10b981',
				'status' => __( 'Excellent', 'vigil-security' ),
			);
		} elseif ( $score >= 80 ) {
			return array(
				'grade'  => 'B',
				'color'  => '#3b82f6',
				'status' => __( 'Good', 'vigil-security' ),
			);
		} elseif ( $score >= 70 ) {
			return array(
				'grade'  => 'C',
				'color'  => '#f59e0b',
				'status' => __( 'Fair', 'vigil-security' ),
			);
		} elseif ( $score >= 60 ) {
			return array(
				'grade'  => 'D',
				'color'  => '#ef4444',
				'status' => __( 'Poor', 'vigil-security' ),
			);
		} else {
			return array(
				'grade'  => 'F',
				'color'  => '#dc2626',
				'status' => __( 'Critical', 'vigil-security' ),
			);
		}
	}

	/**
	 * Get list of security issues.
	 *
	 * @since 1.0.0
	 * @return array List of issues.
	 */
	private function get_security_issues() {
		$issues = array();

		// Check XML-RPC.
		if ( empty( $this->settings['disable_xmlrpc'] ) ) {
			$issues[] = array(
				'title'       => __( 'XML-RPC is enabled', 'vigil-security' ),
				'description' => __( 'This can be exploited for brute force attacks', 'vigil-security' ),
				'severity'    => 'warning',
			);
		}

		// Check file editing.
		if ( empty( $this->settings['disable_file_edit'] ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			$issues[] = array(
				'title'       => __( 'File editing is enabled', 'vigil-security' ),
				'description' => __( 'Hackers can modify your theme/plugin files', 'vigil-security' ),
				'severity'    => 'critical',
			);
		}

		// Check login protection.
		if ( empty( $this->settings['login_protection_enabled'] ) ) {
			$issues[] = array(
				'title'       => __( 'Login protection is disabled', 'vigil-security' ),
				'description' => __( 'Your site is vulnerable to brute force attacks', 'vigil-security' ),
				'severity'    => 'critical',
			);
		}

		// Check WP version.
		if ( empty( $this->settings['hide_wp_version'] ) ) {
			$issues[] = array(
				'title'       => __( 'WordPress version is visible', 'vigil-security' ),
				'description' => __( 'Attackers can target known vulnerabilities', 'vigil-security' ),
				'severity'    => 'info',
			);
		}

		// Check SSL.
		if ( ! is_ssl() && ! defined( 'FORCE_SSL_ADMIN' ) ) {
			$issues[] = array(
				'title'       => __( 'SSL is not enforced on admin', 'vigil-security' ),
				'description' => __( 'Your login credentials can be intercepted', 'vigil-security' ),
				'severity'    => 'warning',
			);
		}

		// Check database prefix.
		global $wpdb;
		if ( $wpdb->prefix === 'wp_' ) {
			$issues[] = array(
				'title'       => __( 'Database uses default prefix', 'vigil-security' ),
				'description' => __( 'Makes SQL injection attacks easier', 'vigil-security' ),
				'severity'    => 'info',
			);
		}

		return $issues;
	}

	/**
	 * Get activity logs from database.
	 *
	 * @since 1.0.0
	 * @param int $limit Number of logs to retrieve.
	 * @return array Activity logs.
	 */
	private function get_activity_logs( $limit = 100 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'vigil_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);

		return $logs;
	}

	/**
	 * Save settings from POST data.
	 *
	 * @since 1.0.0
	 */
	private function save_settings() {
		// Verify nonce.
		if ( ! isset( $_POST['vigil_security_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vigil_security_settings_nonce'] ) ), 'vigil_security_save_settings' ) ) {
			add_settings_error(
				'vigil_security_messages',
				'vigil_security_nonce_error',
				__( 'Security check failed. Please try again.', 'vigil-security' ),
				'error'
			);
			return;
		}

		// Get current settings.
		$settings = get_option( 'vigil_security_settings', array() );

		// Update login protection settings.
		$settings['login_protection_enabled'] = isset( $_POST['login_protection_enabled'] ) ? 1 : 0;
		$settings['login_attempts_max']       = isset( $_POST['login_attempts_max'] ) ? absint( $_POST['login_attempts_max'] ) : 5;
		$settings['login_lockout_duration']   = isset( $_POST['login_lockout_duration'] ) ? absint( $_POST['login_lockout_duration'] ) : 900;

		// Update hardening settings.
		$settings['disable_xmlrpc']           = isset( $_POST['disable_xmlrpc'] ) ? 1 : 0;
		$settings['disable_file_edit']        = isset( $_POST['disable_file_edit'] ) ? 1 : 0;
		$settings['hide_wp_version']          = isset( $_POST['hide_wp_version'] ) ? 1 : 0;
		$settings['disable_user_enumeration'] = isset( $_POST['disable_user_enumeration'] ) ? 1 : 0;

		// Update security headers.
		$settings['enable_security_headers'] = isset( $_POST['enable_security_headers'] ) ? 1 : 0;

		// Update activity log settings.
		$settings['activity_log_enabled']   = isset( $_POST['activity_log_enabled'] ) ? 1 : 0;
		$settings['activity_log_retention'] = isset( $_POST['activity_log_retention'] ) ? absint( $_POST['activity_log_retention'] ) : 30;

		// Update file integrity settings.
		$settings['file_integrity_enabled'] = isset( $_POST['file_integrity_enabled'] ) ? 1 : 0;
		$settings['file_integrity_email']   = isset( $_POST['file_integrity_email'] ) ? sanitize_email( wp_unslash( $_POST['file_integrity_email'] ) ) : get_option( 'admin_email' );

		// Save settings.
		update_option( 'vigil_security_settings', $settings );

		// Update the class property so changes show immediately.
		$this->settings = $settings;

		// Show success message.
		add_settings_error(
			'vigil_security_messages',
			'vigil_security_settings_saved',
			__( 'Settings saved successfully!', 'vigil-security' ),
			'success'
		);

		// Set a flag so we know to reload the settings after save.
		set_transient( 'vigil_security_settings_saved', true, 5 );
	}
}