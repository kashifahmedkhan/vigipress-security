<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/includes
 */

namespace Vigil_Security;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Vigil_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clears scheduled cron jobs and logs the deactivation event.
	 * Does NOT delete database tables or settings (that's done in uninstall.php).
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear all scheduled cron jobs.
		self::clear_cron_jobs();

		// Log deactivation event.
		self::log_deactivation_event();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear all scheduled WordPress cron jobs.
	 *
	 * @since 1.0.0
	 */
	private static function clear_cron_jobs() {
		// Clear daily cleanup job.
		$timestamp = wp_next_scheduled( 'vigil_security_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'vigil_security_daily_cleanup' );
		}

		// Clear weekly file check job.
		$timestamp = wp_next_scheduled( 'vigil_security_weekly_file_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'vigil_security_weekly_file_check' );
		}
	}

	/**
	 * Log plugin deactivation event.
	 *
	 * @since 1.0.0
	 */
	private static function log_deactivation_event() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'vigil_logs';

		// Check if table exists before trying to log.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if ( $table_exists !== $table_name ) {
			return;
		}

		$user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'event_type'  => 'plugin_deactivated',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => self::get_user_ip(),
				'description' => 'Vigil Security plugin was deactivated',
				'severity'    => 'warning',
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
}