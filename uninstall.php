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

// Load the uninstaller class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vigil-uninstaller.php';

// Run the uninstall process.
\Vigil_Security\Vigil_Uninstaller::uninstall();