<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file handles complete cleanup of all plugin data when the user
 * clicks "Delete" on the Plugins page.
 *
 * @package VigiPress_Security
 * @since      1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


// Load the uninstaller class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vigipress-uninstaller.php';

// Run the uninstall process.
\VigiPress_Security\VigiPress_Uninstaller::uninstall();

/**
 * Delete all plugin options.
 *
 * @since 1.0.0
 */
function vigipress_security_delete_options() {
	delete_option( 'vigipress_security_settings' );
	delete_option( 'vigipress_security_db_version' );

	// Delete any transients.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vigipress_%' OR option_name LIKE '_transient_timeout_vigipress_%'"
	);
}

/**
 * Drop all custom database tables.
 *
 * @since 1.0.0
 */
function vigipress_security_drop_tables() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'vigipress_logs';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

/**
 * Clear all scheduled cron jobs.
 *
 * @since 1.0.0
 */
function vigipress_security_clear_cron() {
	wp_clear_scheduled_hook( 'vigipress_security_daily_cleanup' );
	wp_clear_scheduled_hook( 'vigipress_security_weekly_file_check' );
}

/**
 * Remove any wp-config.php modifications.
 *
 * Note: This is a safety measure. Our plugin will NOT directly edit wp-config.php
 * in the MVP, but this is here for future versions.
 *
 * @since 1.0.0
 */
function vigipress_security_cleanup_wp_config() {
	// Future implementation: Remove any defines we added.
	// For MVP, we don't modify wp-config.php, so this is a placeholder.
}

// Execute cleanup.
vigipress_security_delete_options();
vigipress_security_drop_tables();
vigipress_security_clear_cron();
vigipress_security_cleanup_wp_config();
