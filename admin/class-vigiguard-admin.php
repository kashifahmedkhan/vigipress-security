<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin menu,
 * settings, and AJAX handlers.
 *
 * @package    VigiGuard_Security
 * @subpackage VigiGuard_Security/admin
 * @since      1.0.0
 */

namespace VigiGuard_Security\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin menu and settings.
 */
class VigiGuard_Admin {

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
		$this->settings    = get_option( 'vigiguard_security_settings', array() );
		
		// Add AJAX handlers.
		add_action( 'wp_ajax_vigiguard_fix_all_issues', array( $this, 'ajax_fix_all_issues' ) );
		add_action( 'wp_ajax_vigiguard_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_vigiguard_unlock_ip', array( $this, 'ajax_unlock_ip' ) ); 

		add_action( 'wp_ajax_vigiguard_run_file_check', array( $this, 'ajax_run_file_check' ) );
		add_action( 'wp_ajax_vigiguard_reset_plugin', array( $this, 'ajax_reset_plugin' ) );
		add_action( 'wp_ajax_vigiguard_clear_logs', array( $this, 'ajax_clear_logs' ) );

		add_action( 'wp_ajax_vigiguard_run_file_check', array( $this, 'ajax_run_file_check' ) );  

	}

	/**
	 * Register the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'VigiGuard Security', 'vigiguard-security' ),           // Page title.
			__( 'VigiGuard Security', 'vigiguard-security' ),           // Menu title.
			'manage_options',                                    // Capability.
			'vigiguard-security',                                    // Menu slug.
			array( $this, 'display_dashboard_page' ),           // Callback function.
			'dashicons-shield',                                  // Icon.
			80                                                   // Position (below Settings).
		);

		// Add submenu pages.
		add_submenu_page(
			'vigiguard-security',
			__( 'Dashboard', 'vigiguard-security' ),
			__( 'Dashboard', 'vigiguard-security' ),
			'manage_options',
			'vigiguard-security',
			array( $this, 'display_dashboard_page' )
		);

		add_submenu_page(
			'vigiguard-security',
			__( 'Settings', 'vigiguard-security' ),
			__( 'Settings', 'vigiguard-security' ),
			'manage_options',
			'vigiguard-security-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'vigiguard-security',
			__( 'Activity Log', 'vigiguard-security' ),
			__( 'Activity Log', 'vigiguard-security' ),
			'manage_options',
			'vigiguard-security-logs',
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
		if ( ! $screen || strpos( $screen->id, 'vigiguard-security' ) === false ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			VIGIL_SECURITY_URL . 'admin/css/vigiguard-admin.css',
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
		if ( ! $screen || strpos( $screen->id, 'vigiguard-security' ) === false ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			VIGIL_SECURITY_URL . 'admin/js/vigiguard-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Pass data to JavaScript.
		wp_localize_script(
			$this->plugin_name,
			'vigiguardSecurity',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vigiguard_security_nonce' ),
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigiguard-security' ) );
		}

		// Refresh settings from database to ensure we have latest values
		$this->settings = get_option( 'vigiguard_security_settings', array() );

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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigiguard-security' ) );
		}

		// Handle form submission.
		if ( isset( $_POST['vigiguard_security_settings_nonce'] ) ) {
			// Verify nonce before processing
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vigiguard_security_settings_nonce'] ) ), 'vigiguard_security_save_settings' ) ) {
				$this->save_settings();
			}
		}

		// Refresh settings from database to ensure we have latest values.
		$this->settings = get_option( 'vigiguard_security_settings', array() );

		// Add helpful notice about security headers (dismissible).
		$user_id      = get_current_user_id();
		$is_dismissed = get_user_meta( $user_id, 'vigiguard_dismissed_headers_notice', true );
		
		// Only show if headers enabled AND not dismissed.
		$should_show = ! empty( $this->settings['enable_security_headers'] ) && ! $is_dismissed;
		
		if ( $should_show ) {
			?>
			<div class="notice notice-info is-dismissible vigiguard-dismissible-notice" data-notice-id="headers">
				<p>
					<strong><?php esc_html_e( 'Security Headers Active!', 'vigiguard-security' ); ?></strong>
					<?php esc_html_e( 'Security headers are being sent on all frontend pages. To verify, open your homepage (not admin) and check browser DevTools → Network → Response Headers.', 'vigiguard-security' ); ?>
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vigiguard-security' ) );
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

		// Refresh settings to get latest values
		$this->settings = get_option( 'vigiguard_security_settings', array() );

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
		update_option( 'vigiguard_security_settings', $this->settings );

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
				'status' => __( 'Excellent', 'vigiguard-security' ),
			);
		} elseif ( $score >= 80 ) {
			return array(
				'grade'  => 'B',
				'color'  => '#3b82f6',
				'status' => __( 'Good', 'vigiguard-security' ),
			);
		} elseif ( $score >= 70 ) {
			return array(
				'grade'  => 'C',
				'color'  => '#f59e0b',
				'status' => __( 'Fair', 'vigiguard-security' ),
			);
		} elseif ( $score >= 60 ) {
			return array(
				'grade'  => 'D',
				'color'  => '#ef4444',
				'status' => __( 'Poor', 'vigiguard-security' ),
			);
		} else {
			return array(
				'grade'  => 'F',
				'color'  => '#dc2626',
				'status' => __( 'Critical', 'vigiguard-security' ),
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
				'title'       => __( 'XML-RPC is enabled', 'vigiguard-security' ),
				'description' => __( 'This can be exploited for brute force attacks', 'vigiguard-security' ),
				'severity'    => 'warning',
			);
		}

		// Check file editing.
		if ( empty( $this->settings['disable_file_edit'] ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			$issues[] = array(
				'title'       => __( 'File editing is enabled', 'vigiguard-security' ),
				'description' => __( 'Hackers can modify your theme/plugin files', 'vigiguard-security' ),
				'severity'    => 'critical',
			);
		}

		// Check login protection.
		if ( empty( $this->settings['login_protection_enabled'] ) ) {
			$issues[] = array(
				'title'       => __( 'Login protection is disabled', 'vigiguard-security' ),
				'description' => __( 'Your site is vulnerable to brute force attacks', 'vigiguard-security' ),
				'severity'    => 'critical',
			);
		}

		// Check WP version.
		if ( empty( $this->settings['hide_wp_version'] ) ) {
			$issues[] = array(
				'title'       => __( 'WordPress version is visible', 'vigiguard-security' ),
				'description' => __( 'Attackers can target known vulnerabilities', 'vigiguard-security' ),
				'severity'    => 'info',
			);
		}

		// Check SSL.
		if ( ! is_ssl() && ! defined( 'FORCE_SSL_ADMIN' ) ) {
			$issues[] = array(
				'title'       => __( 'SSL is not enforced on admin', 'vigiguard-security' ),
				'description' => __( 'Your login credentials can be intercepted', 'vigiguard-security' ),
				'severity'    => 'warning',
			);
		}

		// Check database prefix.
		global $wpdb;
		if ( $wpdb->prefix === 'wp_' ) {
			$issues[] = array(
				'title'       => __( 'Database uses default prefix', 'vigiguard-security' ),
				'description' => __( 'Makes SQL injection attacks easier', 'vigiguard-security' ),
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
		$table_name = $wpdb->prefix . 'vigiguard_logs';

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
		if ( ! isset( $_POST['vigiguard_security_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vigiguard_security_settings_nonce'] ) ), 'vigiguard_security_save_settings' ) ) {
			add_settings_error(
				'vigiguard_security_messages',
				'vigiguard_security_nonce_error',
				__( 'Security check failed. Please try again.', 'vigiguard-security' ),
				'error'
			);
			return;
		}


		// Update file integrity settings.
		$settings['file_integrity_enabled'] = isset( $_POST['file_integrity_enabled'] ) ? 1 : 0;
		$settings['file_integrity_email']   = isset( $_POST['file_integrity_email'] ) ? sanitize_email( wp_unslash( $_POST['file_integrity_email'] ) ) : get_option( 'admin_email' );

		// Save data retention preference separately (not in main settings array)
		if ( isset( $_POST['keep_data_on_uninstall'] ) ) {
			update_option( 'vigiguard_security_keep_data_on_uninstall', 1 );
		} else {
			update_option( 'vigiguard_security_keep_data_on_uninstall', 0 );
		}
		


		// Get current settings.
		$settings = get_option( 'vigiguard_security_settings', array() );

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
		update_option( 'vigiguard_security_settings', $settings );

		// Update the class property so changes show immediately.
		$this->settings = $settings;

		// Show success message.
		add_settings_error(
			'vigiguard_security_messages',
			'vigiguard_security_settings_saved',
			__( 'Settings saved successfully!', 'vigiguard-security' ),
			'success'
		);

		// Set a flag so we know to reload the settings after save.
		set_transient( 'vigiguard_security_settings_saved', true, 5 );
	}

	/**
	 * AJAX handler for "Fix All Issues" button.
	 *
	 * @since 1.0.0
	 */
	public function ajax_fix_all_issues() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		// Get current settings and calculate old score FIRST
		$this->settings = get_option( 'vigiguard_security_settings', array() );
		$old_score      = $this->calculate_health_score(); // Calculate BEFORE making changes

		// Now apply all safe hardening measures.
		$this->settings['disable_xmlrpc']           = 1;
		$this->settings['disable_file_edit']        = 1;
		$this->settings['hide_wp_version']          = 1;
		$this->settings['disable_user_enumeration'] = 1;
		$this->settings['enable_security_headers']  = 1;
		$this->settings['login_protection_enabled'] = 1;
		$this->settings['activity_log_enabled']     = 1;

		// Save settings.
		update_option( 'vigiguard_security_settings', $this->settings );

		// IMPORTANT: Refresh settings from database before recalculating
		$this->settings = get_option( 'vigiguard_security_settings', array() );
		
		// Recalculate health score with NEW settings.
		$new_score = $this->calculate_health_score();

		// Get updated health data.
		$health_data = $this->get_health_grade( $new_score );

		// Log the fix action.
		$this->log_fix_all_event( $old_score, $new_score );

		// Return success response.
		wp_send_json_success(
			array(
				'message'      => __( 'All security issues have been fixed!', 'vigiguard-security' ),
				'old_score'    => $old_score,
				'new_score'    => $new_score,
				'grade'        => $health_data['grade'],
				'status'       => $health_data['status'],
				'color'        => $health_data['color'],
				'issues_fixed' => 6,
				'redirect_url' => admin_url( 'admin.php?page=vigiguard-security&fixed=1' ), // Optional: for fallback
			)
		);
	}

	/**
	 * Log the "Fix All" action to activity log.
	 *
	 * @since 1.0.0
	 * @param int $old_score Old health score.
	 * @param int $new_score New health score.
	 */
	private function log_fix_all_event( $old_score, $new_score ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'vigiguard_logs';

		$user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => 'security_fixed',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => $this->get_user_ip(),
				'description' => sprintf(
					/* translators: 1: old score, 2: new score */
					__( 'Applied all security fixes. Health score improved from %1$d to %2$d.', 'vigiguard-security' ),
					$old_score,
					$new_score
				),
				'severity'    => 'info',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get user's IP address (supports proxies and CDNs).
	 *
	 * @since 1.0.0
	 * @return string User's IP address or 0.0.0.0 if unable to detect.
	 */
	private function get_user_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
	);

	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

			if ( strpos( $ip, ',' ) !== false ) {
				$ip_array = explode( ',', $ip );
				$ip       = trim( $ip_array[0] );
			}

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}

	return '0.0.0.0';
	}

	/**
	 * AJAX handler for dismissing admin notices.
	 *
	 * @since 1.0.0
	 */
	public function ajax_dismiss_notice() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		// Get notice ID.
		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'vigiguard-security' ) ) );
		}

		// Save dismissal in user meta.
		$meta_key = 'vigiguard_dismissed_' . $notice_id . '_notice';
		update_user_meta( get_current_user_id(), $meta_key, true );

		wp_send_json_success( array( 
			'message' => __( 'Notice dismissed', 'vigiguard-security' ),
			'meta_key' => $meta_key,
			'user_id' => get_current_user_id()
		) );
	}

	/**
	 * Handle notice dismissal via URL parameter.
	 * This catches the dismissal immediately before page reload.
	 *
	 * @since 1.0.0
	 */
	public function handle_notice_dismissal() {
		// Check if this is a notice dismissal request
		if ( isset( $_GET['vigiguard_dismiss'] ) && isset( $_GET['vigiguard_nonce'] ) ) {
			// Verify nonce
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['vigiguard_nonce'] ) ), 'vigiguard_dismiss_notice' ) ) {
				return;
			}

			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Get notice ID
			$notice_id = sanitize_text_field( wp_unslash( $_GET['vigiguard_dismiss'] ) );

			// Save dismissal
			$meta_key = 'vigiguard_dismissed_' . $notice_id . '_notice';
			update_user_meta( get_current_user_id(), $meta_key, true );

			// Redirect to clean URL (remove parameters)
			$redirect_url = remove_query_arg( array( 'vigiguard_dismiss', 'vigiguard_nonce' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * AJAX handler for unlocking an IP address.
	 *
	 * @since 1.0.0
	 */
	public function ajax_unlock_ip() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		// Get IP address.
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address', 'vigiguard-security' ) ) );
		}

		// Unlock the IP.
		if ( class_exists( '\VigiGuard_Security\Modules\Login_Protection' ) ) {
			$login_protection = new \VigiGuard_Security\Modules\Login_Protection();
			$result           = $login_protection->unlock_ip( $ip );

			if ( $result ) {
				wp_send_json_success( array(
					'message' => sprintf(
						/* translators: %s: IP address */
						__( 'IP address %s has been unlocked.', 'vigiguard-security' ),
						$ip
					),
				) );
			}
		}

		wp_send_json_error( array( 'message' => __( 'Failed to unlock IP address', 'vigiguard-security' ) ) );
	}

	/**
	 * AJAX handler for running manual file integrity check.
	 *
	 * @since 1.0.0
	 */
	public function ajax_run_file_check() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		// Run file check.
		if ( class_exists( '\VigiGuard_Security\Modules\File_Integrity' ) ) {
			$file_integrity = new \VigiGuard_Security\Modules\File_Integrity();
			$results        = $file_integrity->run_file_check();

			if ( isset( $results['error'] ) ) {
				wp_send_json_error( array( 'message' => $results['error'] ) );
			}

			wp_send_json_success( array(
				'message'    => __( 'File integrity scan completed!', 'vigiguard-security' ),
				'checked'    => $results['checked'],
				'modified'   => count( $results['modified'] ),
				'unexpected' => count( $results['unexpected'] ),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'File integrity module not available', 'vigiguard-security' ) ) );
	}


	/**
	 * AJAX handler for resetting plugin to default settings.
	 *
	 * Resets all settings to factory defaults while preserving security logs.
	 *
	 * @since 1.0.0
	 */
	public function ajax_reset_plugin() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		// Get default settings from activator.
		$default_settings = array(
			'login_protection_enabled'  => false,
			'login_attempts_max'        => 5,
			'login_lockout_duration'    => 900,
			'disable_xmlrpc'            => false,
			'disable_file_edit'         => false,
			'hide_wp_version'           => false,
			'disable_user_enumeration'  => false,
			'enable_security_headers'   => false,
			'activity_log_enabled'      => true,
			'activity_log_retention'    => 30,
			'file_integrity_enabled'    => false,
			'file_integrity_email'      => get_option( 'admin_email' ),
			'health_score'              => 0,
			'health_score_last_check'   => current_time( 'timestamp' ),
		);

		// Reset settings.
		update_option( 'vigiguard_security_settings', $default_settings );

		// Log the reset.
		global $wpdb;
		$table_name = $wpdb->prefix . 'vigiguard_logs';
		$user       = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => 'plugin_reset',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => $this->get_user_ip(),
				'description' => __( 'Plugin settings reset to defaults', 'vigiguard-security' ),
				'severity'    => 'warning',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array(
			'message' => __( 'Plugin settings have been reset to defaults.', 'vigiguard-security' ),
		) );
	}

	/**
	 * AJAX handler for clearing all security logs.
	 *
	 * Permanently deletes all log entries from the database.
	 * Logs the deletion action as a new entry.
	 *
	 * @since 1.0.0
	 */
	public function ajax_clear_logs() {
		// Verify nonce.
		check_ajax_referer( 'vigiguard_security_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'vigiguard-security' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'vigiguard_logs';

		// Get count before deletion.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_before = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		// Delete all logs.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		// Log the clear action (creates new log entry).
		$user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => 'logs_cleared',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => $this->get_user_ip(),
				'description' => sprintf(
					/* translators: %d: number of deleted logs */
					__( 'Cleared %d security log entries', 'vigiguard-security' ),
					$count_before
				),
				'severity'    => 'warning',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array(
			'message'      => sprintf(
				/* translators: %d: number of deleted logs */
				__( '%d security log entries have been cleared.', 'vigiguard-security' ),
				$count_before
			),
			'logs_deleted' => $count_before,
		) );
	}


}