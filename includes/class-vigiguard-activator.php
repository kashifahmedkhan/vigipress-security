<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    VigiGuard_Security
 * @subpackage VigiGuard_Security/includes
 * @since      1.0.0
 */

namespace VigiGuard_Security;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class VigiGuard_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates database tables, sets default options, and schedules cron jobs.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Create database tables.
		self::create_tables();

		// Set default plugin settings.
		self::set_default_settings();

		// Schedule cron jobs.
		self::schedule_cron_jobs();

		// Set activation flag (for showing welcome message).
		set_transient( 'vigiguard_security_activated', true, 60 );

		// Flush rewrite rules (in case we add custom endpoints later).
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'vigiguard_logs';

		// SQL to create logs table.
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type varchar(50) NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT 0,
			username varchar(60) DEFAULT '',
			ip_address varchar(45) NOT NULL,
			description text,
			severity enum('info','warning','critical') DEFAULT 'info',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY created_at (created_at),
			KEY ip_address (ip_address)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Store database version for future migrations.
		update_option( 'vigiguard_security_db_version', '1.0.0' );
	}

	/**
	 * Set default plugin settings.
	 *
	 * @since 1.0.0
	 */
	private static function set_default_settings() {
		$default_settings = array(
			// Login protection settings.
			'login_protection_enabled'  => false, // OFF by default (safe).
			'login_attempts_max'        => 5,
			'login_lockout_duration'    => 900, // 15 minutes in seconds.

			// Hardening settings.
			'disable_xmlrpc'            => false,
			'disable_file_edit'         => false,
			'hide_wp_version'           => false,
			'disable_user_enumeration'  => false,

			// Security headers.
			'enable_security_headers'   => false,

			// Activity log settings.
			'activity_log_enabled'      => true, // ON by default (passive, no risk).
			'activity_log_retention'    => 30, // Days to keep logs.

			// File integrity settings.
			'file_integrity_enabled'    => false,
			'file_integrity_email'      => get_option( 'admin_email' ),

			// Health score (calculated on activation).
			'health_score'              => 0,
			'health_score_last_check'   => current_time( 'timestamp' ),
		);

		// Only add if not already exists (prevents overwriting on reactivation).
		if ( ! get_option( 'vigiguard_security_settings' ) ) {
			add_option( 'vigiguard_security_settings', $default_settings );
		}

		// Log the activation.
		self::log_activation_event();
	}

	/**
	 * Schedule WordPress cron jobs.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cron_jobs() {
		// Schedule daily log cleanup (runs at 3 AM).
		if ( ! wp_next_scheduled( 'vigiguard_security_daily_cleanup' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 3:00am' ), 'daily', 'vigiguard_security_daily_cleanup' );
		}

		// Schedule weekly file integrity check (runs Sunday at 2 AM).
		if ( ! wp_next_scheduled( 'vigiguard_security_weekly_file_check' ) ) {
			wp_schedule_event( strtotime( 'next Sunday 2:00am' ), 'weekly', 'vigiguard_security_weekly_file_check' );
		}
	}

	/**
	 * Log plugin activation event.
	 *
	 * @since 1.0.0
	 */
	private static function log_activation_event() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'vigiguard_logs';
		$user       = wp_get_current_user();

		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => 'plugin_activated',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => self::get_user_ip(),
				'description' => 'VigiGuard Security plugin was activated',
				'severity'    => 'info',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get user's IP address (supports proxies).
	 *
	 * @since 1.0.0
	 * @return string User's IP address.
	 */
	private static function get_user_ip() {
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
}