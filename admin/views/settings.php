<?php
/**
 * Settings page view template.
 *
 * @package    VigiPress_Security
 * @subpackage VigiPress_Security/admin/views
 * @since      1.0.0
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Display success/error messages.
settings_errors( 'vigipress_security_messages' );
?>

<div class="wrap vigipress-wrap">
	<h1 class="vigipress-page-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'VigiPress Security Settings', 'vigipress-security' ); ?>
	</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'vigipress_security_save_settings', 'vigipress_security_settings_nonce' ); ?>

		<div class="vigipress-settings-grid">
			<!-- Login Protection Settings -->
			<div class="vigipress-card">
				<div class="vigipress-card-header">
					<h2><?php esc_html_e( 'Login Protection', 'vigipress-security' ); ?></h2>
				</div>
				<div class="vigipress-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="login_protection_enabled">
									<?php esc_html_e( 'Enable Login Protection', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigipress-toggle">
									<input type="checkbox" name="login_protection_enabled" id="login_protection_enabled" value="1" <?php checked( ! empty( $this->settings['login_protection_enabled'] ) ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Blocks IP addresses after too many failed login attempts.', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="login_attempts_max">
									<?php esc_html_e( 'Max Login Attempts', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<input type="number" name="login_attempts_max" id="login_attempts_max" value="<?php echo esc_attr( $this->settings['login_attempts_max'] ?? 5 ); ?>" min="3" max="10" class="small-text">
								<p class="description">
									<?php esc_html_e( 'Number of failed attempts before lockout (3-10).', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="login_lockout_duration">
									<?php esc_html_e( 'Lockout Duration', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<select name="login_lockout_duration" id="login_lockout_duration">
									<option value="300" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 300 ); ?>>
										<?php esc_html_e( '5 minutes', 'vigipress-security' ); ?>
									</option>
									<option value="900" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 900 ); ?>>
										<?php esc_html_e( '15 minutes', 'vigipress-security' ); ?>
									</option>
									<option value="1800" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 1800 ); ?>>
										<?php esc_html_e( '30 minutes', 'vigipress-security' ); ?>
									</option>
									<option value="3600" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 3600 ); ?>>
										<?php esc_html_e( '1 hour', 'vigipress-security' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Hardening Settings -->
			<div class="vigipress-card">
				<div class="vigipress-card-header">
					<h2><?php esc_html_e( 'Security Hardening', 'vigipress-security' ); ?></h2>
				</div>
				<div class="vigipress-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="disable_xmlrpc">
									<?php esc_html_e( 'Disable XML-RPC', 'vigipress-security' ); ?>
								</label>
								</th>
							<td>
								<label class="vigipress-toggle">
									<input type="checkbox" name="disable_xmlrpc" id="disable_xmlrpc" value="1" <?php checked( ! empty( $this->settings['disable_xmlrpc'] ) ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents brute force attacks via XML-RPC (safe for most sites).', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="disable_file_edit">
									<?php esc_html_e( 'Disable File Editing', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigipress-toggle">
									<input type="checkbox" name="disable_file_edit" id="disable_file_edit" value="1" <?php checked( ! empty( $this->settings['disable_file_edit'] ) ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents editing theme/plugin files from admin (recommended).', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hide_wp_version">
									<?php esc_html_e( 'Hide WordPress Version', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigipress-toggle">
									<input type="checkbox" name="hide_wp_version" id="hide_wp_version" value="1" <?php checked( ! empty( $this->settings['hide_wp_version'] ) ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Removes version number from page source.', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="disable_user_enumeration">
									<?php esc_html_e( 'Block User Enumeration', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigipress-toggle">
									<input type="checkbox" name="disable_user_enumeration" id="disable_user_enumeration" value="1" <?php checked( ! empty( $this->settings['disable_user_enumeration'] ) ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents hackers from discovering usernames via /?author=1.', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<!-- Security Headers -->
		<div class="vigipress-card">
			<div class="vigipress-card-header">
				<h2><?php esc_html_e( 'Security Headers', 'vigipress-security' ); ?></h2>
			</div>
			<div class="vigipress-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_security_headers">
								<?php esc_html_e( 'Enable Security Headers', 'vigipress-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigipress-toggle">
								<input type="checkbox" name="enable_security_headers" id="enable_security_headers" value="1" <?php checked( ! empty( $this->settings['enable_security_headers'] ) ); ?>>
								<span class="vigipress-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Adds X-Frame-Options, X-XSS-Protection, and other protective headers.', 'vigipress-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<!-- Activity Log Settings -->
		<div class="vigipress-card">
			<div class="vigipress-card-header">
				<h2><?php esc_html_e( 'Activity Logging', 'vigipress-security' ); ?></h2>
			</div>
			<div class="vigipress-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="activity_log_enabled">
								<?php esc_html_e( 'Enable Activity Log', 'vigipress-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigipress-toggle">
								<input type="checkbox" name="activity_log_enabled" id="activity_log_enabled" value="1" <?php checked( ! empty( $this->settings['activity_log_enabled'] ) ); ?>>
								<span class="vigipress-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Track user logins, failed attempts, and security events.', 'vigipress-security' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="activity_log_retention">
								<?php esc_html_e( 'Keep Logs For', 'vigipress-security' ); ?>
							</label>
						</th>
						<td>
							<input type="number" name="activity_log_retention" id="activity_log_retention" value="<?php echo esc_attr( $this->settings['activity_log_retention'] ?? 30 ); ?>" min="7" max="365" class="small-text">
							<?php esc_html_e( 'days', 'vigipress-security' ); ?>
							<p class="description">
								<?php esc_html_e( 'Older logs will be automatically deleted (7-365 days).', 'vigipress-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<!-- File Integrity Settings -->
		<div class="vigipress-card">
			<div class="vigipress-card-header">
				<h2><?php esc_html_e( 'File Integrity Monitoring', 'vigipress-security' ); ?></h2>
			</div>
			<div class="vigipress-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="file_integrity_enabled">
								<?php esc_html_e( 'Enable File Monitoring', 'vigipress-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigipress-toggle">
								<input type="checkbox" name="file_integrity_enabled" id="file_integrity_enabled" value="1" <?php checked( ! empty( $this->settings['file_integrity_enabled'] ) ); ?>>
								<span class="vigipress-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Weekly check for modified WordPress core files.', 'vigipress-security' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="file_integrity_email">
								<?php esc_html_e( 'Alert Email', 'vigipress-security' ); ?>
							</label>
						</th>
						<td>
							<input type="email" name="file_integrity_email" id="file_integrity_email" value="<?php echo esc_attr( $this->settings['file_integrity_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Email address to receive alerts about file changes.', 'vigipress-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

		
		<!-- Data Management Settings -->
			<div class="vigipress-card">
				<div class="vigipress-card-header">
					<h2><?php esc_html_e( 'Data Management', 'vigipress-security' ); ?></h2>
				</div>
				<div class="vigipress-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="keep_data_on_uninstall">
									<?php esc_html_e( 'Keep Data on Uninstall', 'vigipress-security' ); ?>
								</label>
							</th>
							<td>
								<?php
								$keep_data = get_option( 'vigipress_security_keep_data_on_uninstall', false );
								?>
								<label class="vigipress-toggle">
									<input type="checkbox" name="keep_data_on_uninstall" id="keep_data_on_uninstall" value="1" <?php checked( $keep_data ); ?>>
									<span class="vigipress-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'If enabled, security logs and data will be preserved when you delete the plugin. Disable for complete removal.', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Reset Plugin', 'vigipress-security' ); ?>
							</th>
							<td>
								<button type="button" class="button button-secondary vigipress-reset-plugin-btn">
									<span class="dashicons dashicons-backup"></span>
									<?php esc_html_e( 'Reset All Settings', 'vigipress-security' ); ?>
								</button>
								<p class="description">
									<?php esc_html_e( 'Reset all settings to default values. Security logs will be preserved.', 'vigipress-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Clear Security Logs', 'vigipress-security' ); ?>
							</th>
							<td>
								<?php
								global $wpdb;
								$table_name = $wpdb->prefix . 'vigipress_logs';
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
								$log_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
								?>
								<button type="button" class="button button-secondary vigipress-clear-logs-btn">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Clear All Logs', 'vigipress-security' ); ?>
								</button>
								<p class="description">
									<?php
									printf(
										/* translators: %s: number of log entries */
										esc_html__( 'Permanently delete all %s security log entries. This cannot be undone.', 'vigipress-security' ),
										'<strong>' . esc_html( number_format_i18n( $log_count ) ) . '</strong>'
									);
									?>
								</p>
							</td>
						</tr>
					</table>

					<?php
					// Show data removal preview.
					$stats = \VigiPress_Security\VigiPress_Uninstaller::get_removal_stats();
					?>
					<div class="vigipress-data-preview" style="margin-top: 30px; padding: 15px; background: #f9fafb; border-left: 4px solid #3b82f6; border-radius: 4px;">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Data Removal Preview', 'vigipress-security' ); ?></h3>
						<p><?php esc_html_e( 'If you delete this plugin, the following data will be removed:', 'vigipress-security' ); ?></p>
						<ul style="margin-left: 20px;">
							<li><?php 
							/* translators: %d: number of plugin settings */
							printf( esc_html__( '%d plugin settings', 'vigipress-security' ), esc_html( $stats['options'] ) ); ?></li>
							<li><?php 
							/* translators: %d: number of security log entries */
							printf( esc_html__( '%d security log entries', 'vigipress-security' ), esc_html( number_format_i18n( $stats['logs'] ) ) ); ?></li>
							<li><?php 
							/* translators: %d: number of user preferences */
							printf( esc_html__( '%d user preferences', 'vigipress-security' ), esc_html( $stats['user_meta'] ) ); ?></li>
							<li><?php 
							/* translators: %d: number of temporary cache entries */
							printf( esc_html__( '%d temporary cache entries', 'vigipress-security' ), esc_html( $stats['transients'] ) ); ?></li>
						</ul>
						<p>
							<em>
								<?php
								if ( $keep_data ) {
									esc_html_e( '✅ Data retention is ENABLED. Security logs will be preserved.', 'vigipress-security' );
								} else {
									esc_html_e( '⚠️ Data retention is DISABLED. All data will be permanently deleted.', 'vigipress-security' );
								}
								?>
							</em>
						</p>
					</div>
				</div>
			</div>


		<p class="submit">
			<button type="submit" class="button button-primary button-large">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Save All Settings', 'vigipress-security' ); ?>
			</button>
		</p>
	</form>