<?php
/**
 * Uninstaller Class
 *
 * Handles complete plugin data removal on uninstall.
 *
 * @package    VigiGuard_Security
 * @subpackage VigiGuard_Security/includes
 * @since      1.0.0
 */

namespace VigiGuard_Security;

/**
 * Uninstaller class.
 *
 * Defines all code necessary to run during the plugin's uninstallation.
 */
class VigiGuard_Uninstaller {

	/**
	 * Run the uninstallation process.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		// Check if user wants to keep data.
		$keep_data = get_option( 'vigiguard_security_keep_data_on_uninstall', false );

		if ( $keep_data ) {
			// Only remove plugin options, keep security data.
			self::remove_plugin_options();
			self::log_uninstall( 'partial' );
		} else {
			// Complete cleanup.
			self::remove_all_options();
			self::drop_database_tables();
			self::remove_user_meta();
			self::clear_all_transients();
			self::clear_scheduled_events();
			self::log_uninstall( 'complete' );
		}
	}

	/**
	 * Remove only plugin configuration options (keep security data).
	 *
	 * @since 1.0.0
	 */
	private static function remove_plugin_options() {
		delete_option( 'vigiguard_security_settings' );
		delete_option( 'vigiguard_security_db_version' );
		delete_option( 'vigiguard_security_keep_data_on_uninstall' );
	}

	/**
	 * Remove all plugin options.
	 *
	 * @since 1.0.0
	 */
	private static function remove_all_options() {
		global $wpdb;

		// Get all VigiGuard options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$options = $wpdb->get_col(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'vigiguard_%'"
		);

		// Delete each option.
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Drop all custom database tables.
	 *
	 * @since 1.0.0
	 */
	private static function drop_database_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'vigiguard_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Remove all user meta related to VigiGuard Security.
	 *
	 * @since 1.0.0
	 */
	private static function remove_user_meta() {
		global $wpdb;

		// Delete all user meta with 'vigiguard_' prefix.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'vigiguard_%'"
		);
	}

	/**
	 * Clear all transients created by the plugin.
	 *
	 * @since 1.0.0
	 */
	private static function clear_all_transients() {
		global $wpdb;

		// Delete transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_vigiguard_%' 
			OR option_name LIKE '_transient_timeout_vigiguard_%'"
		);
	}

	/**
	 * Clear all scheduled cron events.
	 *
	 * @since 1.0.0
	 */
	private static function clear_scheduled_events() {
		// Clear daily cleanup.
		wp_clear_scheduled_hook( 'vigiguard_security_daily_cleanup' );

		// Clear weekly file check.
		wp_clear_scheduled_hook( 'vigiguard_security_weekly_file_check' );
	}

	/**
	 * Log the uninstallation event.
	 *
	 * @since 1.0.0
	 * @param string $type Type of uninstall (partial or complete).
	 */
	private static function log_uninstall( $type ) {
		global $wpdb;

		// Only log if table still exists.
		$table_name = $wpdb->prefix . 'vigiguard_logs';

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
				'event_type'  => 'plugin_uninstalled',
				'user_id'     => $user->ID,
				'username'    => $user->user_login,
				'ip_address'  => self::get_user_ip(),
				'description' => sprintf(
					/* translators: %s: uninstall type */
					__( 'Plugin uninstalled (%s cleanup)', 'vigiguard-security' ),
					$type
				),
				'severity'    => 'info',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get user's IP address.
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

	/**
	 * Get data removal statistics.
	 *
	 * @since 1.0.0
	 * @return array Statistics about what will be removed.
	 */
	public static function get_removal_stats() {
		global $wpdb;

		$stats = array(
			'options'    => 0,
			'logs'       => 0,
			'user_meta'  => 0,
			'transients' => 0,
		);

		// Count options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['options'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'vigiguard_%'"
		);

		// Count logs.
		$table_name = $wpdb->prefix . 'vigiguard_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if ( $table_exists === $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$stats['logs'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		}

		// Count user meta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['user_meta'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'vigiguard_%'"
		);

		// Count transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['transients'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_vigiguard_%' 
			OR option_name LIKE '_transient_timeout_vigiguard_%'"
		);

		return $stats;
	}
}