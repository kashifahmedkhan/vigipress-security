<?php
/**
 * File Integrity Module
 *
 * Monitors WordPress core files for unauthorized changes.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/includes/modules
 */

namespace Vigil_Security\Modules;

/**
 * File Integrity class.
 *
 * Checks WordPress core files against official checksums.
 */
class File_Integrity {

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

		// Only initialize if file integrity is enabled.
		if ( ! empty( $this->settings['file_integrity_enabled'] ) ) {
			$this->init_hooks();
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Hook into the weekly cron job.
		add_action( 'vigil_security_weekly_file_check', array( $this, 'run_file_check' ) );
	}

	/**
	 * Run file integrity check.
	 *
	 * @since 1.0.0
	 * @return array Results of the scan.
	 */
	public function run_file_check() {
		global $wp_version;

		// Include WordPress update functions
		if ( ! function_exists( 'get_core_checksums' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		// Get official WordPress checksums using core function
		$checksums = get_core_checksums( $wp_version, get_locale() );

		if ( ! $checksums || ! is_array( $checksums ) ) {
			// Try without locale (fallback to en_US)
			$checksums = get_core_checksums( $wp_version, 'en_US' );
		}

		if ( ! $checksums || ! is_array( $checksums ) ) {
			$this->log_event(
				'file_check_failed',
				0,
				'',
				$this->get_server_ip(),
				sprintf(
					/* translators: %s: WordPress version */
					__( 'Failed to retrieve WordPress checksums for version %s', 'vigil-security' ),
					$wp_version
				),
				'warning'
			);
			return array( 
				'error'   => __( 'Could not retrieve checksums', 'vigil-security' ),
				'checked' => 0,
				'modified' => array(),
				'unexpected' => array(),
			);
		}

		// Check core files
		$results = $this->check_core_files( $checksums );

		// Store results
		update_option( 'vigil_last_file_check', array(
			'timestamp' => current_time( 'timestamp' ),
			'results'   => $results,
			'version'   => $wp_version,
		) );

		// Send notification if issues found
		if ( ! empty( $results['modified'] ) || ! empty( $results['unexpected'] ) ) {
			$this->send_file_integrity_alert( $results );
		}

		// Log the check
		$this->log_event(
			'file_check_completed',
			0,
			'',
			$this->get_server_ip(),
			sprintf(
				/* translators: 1: files checked, 2: modified count, 3: unexpected count */
				__( 'File integrity check completed. Checked: %1$d, Modified: %2$d, Unexpected: %3$d', 'vigil-security' ),
				$results['checked'],
				count( $results['modified'] ),
				count( $results['unexpected'] )
			),
			! empty( $results['modified'] ) ? 'critical' : 'info'
		);

		return $results;
	}


	/**
	 * Check core files against checksums.
	 *
	 * @since 1.0.0
	 * @param array $checksums Official checksums.
	 * @return array Results with modified and unexpected files.
	 */
	private function check_core_files( $checksums ) {
		$results = array(
			'checked'    => 0,
			'modified'   => array(),
			'unexpected' => array(),
		);

		if ( empty( $checksums ) || ! is_array( $checksums ) ) {
			return $results;
		}

		$wp_root = ABSPATH;

		// Check each file in checksums
		foreach ( $checksums as $file => $checksum ) {
			$filepath = $wp_root . $file;

			// Skip if file doesn't exist (some files are optional)
			if ( ! file_exists( $filepath ) ) {
				continue;
			}

			$results['checked']++;

			// Calculate file hash
			$file_hash = md5_file( $filepath );

			// Compare with official checksum
			if ( $file_hash !== $checksum ) {
				$results['modified'][] = array(
					'file'     => $file,
					'expected' => $checksum,
					'actual'   => $file_hash,
				);
			}
		}

		return $results;
	}

	/**
	 * Check for unexpected PHP files in core directories.
	 *
	 * @since 1.0.0
	 * @param string $directory Directory to scan.
	 * @param array  $checksums Official checksums.
	 * @param array  &$results Results array (passed by reference).
	 */
	private function check_unexpected_files( $directory, $checksums, &$results ) {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$wp_root = ABSPATH;
		
		// Directories to exclude (plugins/themes often add files here legitimately)
		$exclude_dirs = array(
			'wp-admin/css',
			'wp-admin/js',
			'wp-admin/maint',
			'wp-admin/includes/plugin-install',
			'wp-includes/js',
			'wp-includes/css',
			'wp-includes/fonts',
			'wp-includes/images',
			'wp-includes/blocks',
			'wp-includes/block-patterns',
			'wp-includes/customize',
		);

		try {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $directory, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $files as $file ) {
				if ( $file->isDir() || $file->getExtension() !== 'php' ) {
					continue;
				}

				// Get relative path from WordPress root.
				$relative_path = str_replace( $wp_root, '', $file->getPathname() );
				$relative_path = str_replace( '\\', '/', $relative_path ); // Normalize for Windows.

				// Check if file is in an excluded directory.
				$is_excluded = false;
				foreach ( $exclude_dirs as $exclude ) {
					if ( strpos( $relative_path, $exclude ) === 0 ) {
						$is_excluded = true;
						break;
					}
				}

				if ( $is_excluded ) {
					continue;
				}

				// If file is not in official checksums, it's unexpected.
				if ( ! isset( $checksums[ $relative_path ] ) ) {
					$results['unexpected'][] = array(
						'file'     => $relative_path,
						'size'     => $file->getSize(),
						'modified' => gmdate( 'Y-m-d H:i:s', $file->getMTime() ),
					);
				}
			}
		} catch ( \Exception $e ) {
			// Silently fail if directory can't be read.
			return;
		}
	}

	/**
	 * Send email alert about file integrity issues.
	 *
	 * @since 1.0.0
	 * @param array $results Scan results.
	 */
	private function send_file_integrity_alert( $results ) {
		$to      = ! empty( $this->settings['file_integrity_email'] ) ? $this->settings['file_integrity_email'] : get_option( 'admin_email' );
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Security Alert: File Integrity Issues Detected', 'vigil-security' ),
			get_bloginfo( 'name' )
		);

		$message = __( "Vigil Security has detected changes to your WordPress core files.\n\n", 'vigil-security' );

		if ( ! empty( $results['modified'] ) ) {
			$message .= sprintf(
				/* translators: %d: number of modified files */
				__( "Modified Core Files (%d):\n", 'vigil-security' ),
				count( $results['modified'] )
			);
			foreach ( array_slice( $results['modified'], 0, 10 ) as $file ) {
				$message .= '- ' . $file['file'] . "\n";
			}
			if ( count( $results['modified'] ) > 10 ) {
				$message .= sprintf(
					/* translators: %d: number of additional files */
					__( "...and %d more files\n", 'vigil-security' ),
					count( $results['modified'] ) - 10
				);
			}
			$message .= "\n";
		}

		if ( ! empty( $results['unexpected'] ) ) {
			$message .= sprintf(
				/* translators: %d: number of unexpected files */
				__( "Unexpected Files (%d):\n", 'vigil-security' ),
				count( $results['unexpected'] )
			);
			foreach ( array_slice( $results['unexpected'], 0, 10 ) as $file ) {
				$message .= '- ' . $file['file'] . "\n";
			}
			if ( count( $results['unexpected'] ) > 10 ) {
				$message .= sprintf(
					/* translators: %d: number of additional files */
					__( "...and %d more files\n", 'vigil-security' ),
					count( $results['unexpected'] ) - 10
				);
			}
			$message .= "\n";
		}

		$message .= __( "What this means:\n", 'vigil-security' );
		$message .= __( "- Modified files may indicate a hack or plugin conflict\n", 'vigil-security' );
		$message .= __( "- Unexpected files could be malware injections\n\n", 'vigil-security' );
		$message .= __( "Recommended actions:\n", 'vigil-security' );
		$message .= __( "1. Check your WordPress admin dashboard for updates\n", 'vigil-security' );
		$message .= __( "2. Review the files listed above\n", 'vigil-security' );
		$message .= __( "3. Consider reinstalling WordPress core files\n\n", 'vigil-security' );
		$message .= sprintf(
			/* translators: %s: admin URL */
			__( "View full report: %s\n", 'vigil-security' ),
			admin_url( 'admin.php?page=vigil-security' )
		);

		// Send email.
		wp_mail( $to, $subject, $message );
	}

	/**
	 * Get last file check results.
	 *
	 * @since 1.0.0
	 * @return array|false Last check results or false if never run.
	 */
	public function get_last_check_results() {
		return get_option( 'vigil_last_file_check', false );
	}

	/**
	 * Get server IP address.
	 *
	 * @since 1.0.0
	 * @return string Server IP.
	 */
	private function get_server_ip() {
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
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
	 * @param string $severity    Severity level.
	 */
	private function log_event( $event_type, $user_id, $username, $ip_address, $description, $severity = 'info' ) {
		// Only log if activity logging is enabled.
		if ( empty( $this->settings['activity_log_enabled'] ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'vigil_logs';

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
}