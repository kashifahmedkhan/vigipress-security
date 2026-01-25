<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file handles complete cleanup of all plugin data when the user
 * clicks "Delete" on the Plugins page.
 *
 * @package Vigil_Security
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options.
 *
 * @since 1.0.0
 */
function vigil_security_delete_options() {
	delete_option( 'vigil_security_settings' );
	delete_option( 'vigil_security_db_version' );

	// Delete any transients.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vigil_%' OR option_name LIKE '_transient_timeout_vigil_%'"
	);
}

/**
 * Drop all custom database tables.
 *
 * @since 1.0.0
 */
function vigil_security_drop_tables() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'vigil_logs';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

/**
 * Clear all scheduled cron jobs.
 *
 * @since 1.0.0
 */
function vigil_security_clear_cron() {
	wp_clear_scheduled_hook( 'vigil_security_daily_cleanup' );
	wp_clear_scheduled_hook( 'vigil_security_weekly_file_check' );
}

/**
 * Remove any wp-config.php modifications.
 *
 * Note: This is a safety measure. Our plugin will NOT directly edit wp-config.php
 * in the MVP, but this is here for future versions.
 *
 * @since 1.0.0
 */
function vigil_security_cleanup_wp_config() {
	// Future implementation: Remove any defines we added.
	// For MVP, we don't modify wp-config.php, so this is a placeholder.
}

// Execute cleanup.
vigil_security_delete_options();
vigil_security_drop_tables();
vigil_security_clear_cron();
vigil_security_cleanup_wp_config();