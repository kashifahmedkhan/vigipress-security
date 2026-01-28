<?php
/**
 * Login Protection Module
 *
 * Handles brute force protection via rate limiting.
 *
 * @package    VigiPress_Security
 * @subpackage VigiPress_Security/includes/modules
 * @since      1.0.0
 * 
 */

namespace VigiPress_Security\Modules;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Login Protection class.
 *
 * Protects against brute force attacks by limiting login attempts.
 */
class Login_Protection {

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
		$this->settings = get_option( 'vigipress_security_settings', array() );

		// Only initialize if login protection is enabled.
		if ( ! empty( $this->settings['login_protection_enabled'] ) ) {
			$this->init_hooks();
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Check if IP is blocked before authentication.
		add_filter( 'authenticate', array( $this, 'check_ip_blocked' ), 30, 1 );

		// Track failed login attempts.
		add_action( 'wp_login_failed', array( $this, 'log_failed_login' ) );

		// Clear login attempts on successful login.
		add_action( 'wp_login', array( $this, 'clear_login_attempts' ), 10, 2 );

		// Show lockout message on login page.
		add_action( 'login_errors', array( $this, 'show_lockout_message' ) );
	}

	/**
	 * Check if current IP is blocked.
	 *
	 * @since 1.0.0
	 * @param \WP_User|\WP_Error|null $user User object or error.
	 * @return \WP_User|\WP_Error User object or error.
	 */
	public function check_ip_blocked( $user ) {
		$ip = $this->get_user_ip();

		// Check if IP is currently locked out via transient.
		// Transients auto-expire, so no manual cleanup needed.
		$lockout_data = get_transient( 'vigipress_lockout_' . md5( $ip ) );

		if ( false !== $lockout_data ) {
			// Calculate remaining lockout time.
			$remaining_time = $lockout_data['unlock_time'] - time();

			// Log the blocked attempt for audit trail.
			$this->log_event(
				'login_blocked',
				0,
				'',
				$ip,
				sprintf(
					/* translators: %s: remaining lockout time in minutes */
					__( 'Login attempt blocked. %s minutes remaining in lockout.', 'vigipress-security' ),
					ceil( $remaining_time / 60 )
				),
				'warning'
			);

			// Return WP_Error to prevent login.
			return new \WP_Error(
				'vigipress_ip_blocked',
				sprintf(
					/* translators: %s: remaining lockout time in minutes */
					__( '<strong>Security Alert:</strong> Too many failed login attempts. Please try again in %s minutes.', 'vigipress-security' ),
					ceil( $remaining_time / 60 )
				)
			);
		}

		// IP is not blocked, continue with authentication.
		return $user;
	}

	/**
	 * Log failed login attempt and implement lockout if necessary.
	 *
	 * @since 1.0.0
	 * @param string $username Username used in failed login.
	 */
	public function log_failed_login( $username ) {
		$ip = $this->get_user_ip();

		// Get current attempt count from transient.
		$transient_key = 'vigipress_login_attempts_' . md5( $ip );
		$attempts      = get_transient( $transient_key );

		if ( false === $attempts ) {
			$attempts = array(
				'count'          => 1,
				'first_attempt'  => time(),
				'last_attempt'   => time(),
				'attempted_user' => sanitize_user( $username ),
			);
		} else {
			$attempts['count']++;
			$attempts['last_attempt']   = time();
			$attempts['attempted_user'] = sanitize_user( $username );
		}

		// Get max attempts from settings.
		$max_attempts = ! empty( $this->settings['login_attempts_max'] ) ? $this->settings['login_attempts_max'] : 5;

		// Store updated attempts (expires in 1 hour).
		set_transient( $transient_key, $attempts, HOUR_IN_SECONDS );

		// Check if we need to lock out this IP.
		if ( $attempts['count'] >= $max_attempts ) {
			$this->lockout_ip( $ip, $attempts );
		}

		// Log the failed attempt.
		$this->log_event(
			'login_failed',
			0,
			$username,
			$ip,
			sprintf(
				/* translators: 1: attempt number, 2: max attempts */
				__( 'Failed login attempt (%1$d of %2$d)', 'vigipress-security' ),
				$attempts['count'],
				$max_attempts
			),
			'warning'
		);
	}

	/**
	 * Lockout an IP address.
	 *
	 * @since 1.0.0
	 * @param string $ip       IP address to lock out.
	 * @param array  $attempts Attempt data.
	 */
	private function lockout_ip( $ip, $attempts ) {
		// Get lockout duration from settings (default: 15 minutes).
		$lockout_duration = ! empty( $this->settings['login_lockout_duration'] ) ? $this->settings['login_lockout_duration'] : 900;

		$lockout_data = array(
			'ip'             => $ip,
			'locked_at'      => time(),
			'unlock_time'    => time() + $lockout_duration,
			'attempt_count'  => $attempts['count'],
			'attempted_user' => $attempts['attempted_user'],
		);

		// Store lockout in transient.
		set_transient( 'vigipress_lockout_' . md5( $ip ), $lockout_data, $lockout_duration );

		// Clear the attempts counter since we've now locked them out.
		delete_transient( 'vigipress_login_attempts_' . md5( $ip ) );

		// Log the lockout.
		$this->log_event(
			'ip_locked_out',
			0,
			$attempts['attempted_user'],
			$ip,
			sprintf(
				/* translators: 1: attempt count, 2: lockout duration in minutes */
				__( 'IP locked out after %1$d failed attempts. Lockout duration: %2$d minutes.', 'vigipress-security' ),
				$attempts['count'],
				$lockout_duration / 60
			),
			'critical'
		);

		// Send email notification if configured.
		$this->send_lockout_notification( $ip, $attempts );
	}

	/**
	 * Clear login attempts on successful login.
	 *
	 * @since 1.0.0
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 */
	public function clear_login_attempts( $user_login, $user ) {
		$ip = $this->get_user_ip();

		// Clear attempts counter.
		delete_transient( 'vigipress_login_attempts_' . md5( $ip ) );

		// Log successful login.
		$this->log_event(
			'login_success',
			$user->ID,
			$user_login,
			$ip,
			__( 'User logged in successfully', 'vigipress-security' ),
			'info'
		);
	}

	/**
	 * Show custom lockout message on login page.
	 *
	 * @since 1.0.0
	 * @param string $error Existing error message.
	 * @return string Modified error message.
	 */
	public function show_lockout_message( $error ) {
		$ip = $this->get_user_ip();

		// Check if IP is locked out.
		$lockout_data = get_transient( 'vigipress_lockout_' . md5( $ip ) );

		if ( false !== $lockout_data ) {
			$remaining_time = $lockout_data['unlock_time'] - time();

			return sprintf(
				/* translators: %s: remaining lockout time in minutes */
				__( '<strong>Security Alert:</strong> This IP address has been temporarily blocked due to too many failed login attempts. Please try again in %s minutes.', 'vigipress-security' ),
				ceil( $remaining_time / 60 )
			);
		}

		return $error;
	}

	/**
	 * Send email notification about IP lockout.
	 *
	 * @since 1.0.0
	 * @param string $ip       Locked out IP.
	 * @param array  $attempts Attempt data.
	 */
	private function send_lockout_notification( $ip, $attempts ) {
		// Get admin email.
		$to = get_option( 'admin_email' );

		// Email subject.
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Security Alert: IP Address Locked Out', 'vigipress-security' ),
			get_bloginfo( 'name' )
		);

		// Email body.
		$message = sprintf(
			/* translators: 1: IP address, 2: username, 3: attempt count, 4: lockout duration */
			__(
				"Security Alert from VigiPress Security

An IP address has been locked out due to too many failed login attempts.

Details:
- IP Address: %1\$s
- Attempted Username: %2\$s
- Failed Attempts: %3\$d
- Lockout Duration: %4\$d minutes

This is an automated message from VigiPress Security plugin.
If you did not attempt to log in, your site may be under attack.

To view all security events, visit your WordPress admin dashboard.",
				'vigipress-security'
			),
			$ip,
			$attempts['attempted_user'],
			$attempts['count'],
			( ! empty( $this->settings['login_lockout_duration'] ) ? $this->settings['login_lockout_duration'] : 900 ) / 60
		);

		// Send email (silently fail if it doesn't work).
		wp_mail( $to, $subject, $message );
	}

	/**
	 * Get user's IP address (supports proxies).
	 *
	 * @since 1.0.0
	 * @return string User's IP address.
	 */
	private function get_user_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',  // Proxy.
			'HTTP_X_REAL_IP',        // Nginx proxy.
			'REMOTE_ADDR',           // Default.
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs (from proxies).
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
	 * Log security event to database.
	 *
	 * @since 1.0.0
	 * @param string $event_type  Event type.
	 * @param int    $user_id     User ID.
	 * @param string $username    Username.
	 * @param string $ip_address  IP address.
	 * @param string $description Description.
	 * @param string $severity    Severity level (info, warning, critical).
	 */
	private function log_event( $event_type, $user_id, $username, $ip_address, $description, $severity = 'info' ) {
		// Only log if activity logging is enabled.
		if ( empty( $this->settings['activity_log_enabled'] ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'vigipress_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => sanitize_text_field( $event_type ),
				'user_id'     => absint( $user_id ),
				'username'    => sanitize_text_field( $username ),
				'ip_address'  => sanitize_text_field( $ip_address ),
				'description' => sanitize_text_field( $description ),
				'severity'    => sanitize_text_field( $severity ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get currently locked out IPs.
	 *
	 * @since 1.0.0
	 * @return array List of locked out IPs with data.
	 */
	public function get_locked_ips() {
		global $wpdb;

		$locked_ips = array();

		// Query all transients that match our lockout pattern.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			"SELECT option_name, option_value 
			FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_vigipress_lockout_%'",
			ARRAY_A
		);

		foreach ( $results as $result ) {
			$data = maybe_unserialize( $result['option_value'] );
			if ( is_array( $data ) && isset( $data['ip'] ) ) {
				$data['remaining_time'] = max( 0, $data['unlock_time'] - time() );
				$locked_ips[]           = $data;
			}
		}

		return $locked_ips;
	}

	/**
	 * Manually unlock an IP address.
	 *
	 * @since 1.0.0
	 * @param string $ip IP address to unlock.
	 * @return bool Success status.
	 */
	public function unlock_ip( $ip ) {
		$deleted = delete_transient( 'vigipress_lockout_' . md5( $ip ) );

		if ( $deleted ) {
			$this->log_event(
				'ip_unlocked',
				get_current_user_id(),
				wp_get_current_user()->user_login,
				$this->get_user_ip(),
				sprintf(
					/* translators: %s: IP address */
					__( 'Manually unlocked IP: %s', 'vigipress-security' ),
					$ip
				),
				'info'
			);
		}

		return $deleted;
	}
}