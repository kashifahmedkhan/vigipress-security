<?php
/**
 * Settings page view template.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/admin/views
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Display success/error messages.
settings_errors( 'vigil_security_messages' );
?>

<div class="wrap vigil-wrap">
	<h1 class="vigil-page-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Vigil Security Settings', 'vigil-security' ); ?>
	</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'vigil_security_save_settings', 'vigil_security_settings_nonce' ); ?>

		<div class="vigil-settings-grid">
			<!-- Login Protection Settings -->
			<div class="vigil-card">
				<div class="vigil-card-header">
					<h2><?php esc_html_e( 'Login Protection', 'vigil-security' ); ?></h2>
				</div>
				<div class="vigil-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="login_protection_enabled">
									<?php esc_html_e( 'Enable Login Protection', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigil-toggle">
									<input type="checkbox" name="login_protection_enabled" id="login_protection_enabled" value="1" <?php checked( ! empty( $this->settings['login_protection_enabled'] ) ); ?>>
									<span class="vigil-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Blocks IP addresses after too many failed login attempts.', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="login_attempts_max">
									<?php esc_html_e( 'Max Login Attempts', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<input type="number" name="login_attempts_max" id="login_attempts_max" value="<?php echo esc_attr( $this->settings['login_attempts_max'] ?? 5 ); ?>" min="3" max="10" class="small-text">
								<p class="description">
									<?php esc_html_e( 'Number of failed attempts before lockout (3-10).', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="login_lockout_duration">
									<?php esc_html_e( 'Lockout Duration', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<select name="login_lockout_duration" id="login_lockout_duration">
									<option value="300" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 300 ); ?>>
										<?php esc_html_e( '5 minutes', 'vigil-security' ); ?>
									</option>
									<option value="900" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 900 ); ?>>
										<?php esc_html_e( '15 minutes', 'vigil-security' ); ?>
									</option>
									<option value="1800" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 1800 ); ?>>
										<?php esc_html_e( '30 minutes', 'vigil-security' ); ?>
									</option>
									<option value="3600" <?php selected( $this->settings['login_lockout_duration'] ?? 900, 3600 ); ?>>
										<?php esc_html_e( '1 hour', 'vigil-security' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Hardening Settings -->
			<div class="vigil-card">
				<div class="vigil-card-header">
					<h2><?php esc_html_e( 'Security Hardening', 'vigil-security' ); ?></h2>
				</div>
				<div class="vigil-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="disable_xmlrpc">
									<?php esc_html_e( 'Disable XML-RPC', 'vigil-security' ); ?>
								</label>
								</th>
							<td>
								<label class="vigil-toggle">
									<input type="checkbox" name="disable_xmlrpc" id="disable_xmlrpc" value="1" <?php checked( ! empty( $this->settings['disable_xmlrpc'] ) ); ?>>
									<span class="vigil-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents brute force attacks via XML-RPC (safe for most sites).', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="disable_file_edit">
									<?php esc_html_e( 'Disable File Editing', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigil-toggle">
									<input type="checkbox" name="disable_file_edit" id="disable_file_edit" value="1" <?php checked( ! empty( $this->settings['disable_file_edit'] ) ); ?>>
									<span class="vigil-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents editing theme/plugin files from admin (recommended).', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hide_wp_version">
									<?php esc_html_e( 'Hide WordPress Version', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigil-toggle">
									<input type="checkbox" name="hide_wp_version" id="hide_wp_version" value="1" <?php checked( ! empty( $this->settings['hide_wp_version'] ) ); ?>>
									<span class="vigil-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Removes version number from page source.', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="disable_user_enumeration">
									<?php esc_html_e( 'Block User Enumeration', 'vigil-security' ); ?>
								</label>
							</th>
							<td>
								<label class="vigil-toggle">
									<input type="checkbox" name="disable_user_enumeration" id="disable_user_enumeration" value="1" <?php checked( ! empty( $this->settings['disable_user_enumeration'] ) ); ?>>
									<span class="vigil-toggle-slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents hackers from discovering usernames via /?author=1.', 'vigil-security' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<!-- Security Headers -->
		<div class="vigil-card">
			<div class="vigil-card-header">
				<h2><?php esc_html_e( 'Security Headers', 'vigil-security' ); ?></h2>
			</div>
			<div class="vigil-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_security_headers">
								<?php esc_html_e( 'Enable Security Headers', 'vigil-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigil-toggle">
								<input type="checkbox" name="enable_security_headers" id="enable_security_headers" value="1" <?php checked( ! empty( $this->settings['enable_security_headers'] ) ); ?>>
								<span class="vigil-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Adds X-Frame-Options, X-XSS-Protection, and other protective headers.', 'vigil-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<!-- Activity Log Settings -->
		<div class="vigil-card">
			<div class="vigil-card-header">
				<h2><?php esc_html_e( 'Activity Logging', 'vigil-security' ); ?></h2>
			</div>
			<div class="vigil-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="activity_log_enabled">
								<?php esc_html_e( 'Enable Activity Log', 'vigil-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigil-toggle">
								<input type="checkbox" name="activity_log_enabled" id="activity_log_enabled" value="1" <?php checked( ! empty( $this->settings['activity_log_enabled'] ) ); ?>>
								<span class="vigil-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Track user logins, failed attempts, and security events.', 'vigil-security' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="activity_log_retention">
								<?php esc_html_e( 'Keep Logs For', 'vigil-security' ); ?>
							</label>
						</th>
						<td>
							<input type="number" name="activity_log_retention" id="activity_log_retention" value="<?php echo esc_attr( $this->settings['activity_log_retention'] ?? 30 ); ?>" min="7" max="365" class="small-text">
							<?php esc_html_e( 'days', 'vigil-security' ); ?>
							<p class="description">
								<?php esc_html_e( 'Older logs will be automatically deleted (7-365 days).', 'vigil-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<!-- File Integrity Settings -->
		<div class="vigil-card">
			<div class="vigil-card-header">
				<h2><?php esc_html_e( 'File Integrity Monitoring', 'vigil-security' ); ?></h2>
			</div>
			<div class="vigil-card-body">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="file_integrity_enabled">
								<?php esc_html_e( 'Enable File Monitoring', 'vigil-security' ); ?>
							</label>
						</th>
						<td>
							<label class="vigil-toggle">
								<input type="checkbox" name="file_integrity_enabled" id="file_integrity_enabled" value="1" <?php checked( ! empty( $this->settings['file_integrity_enabled'] ) ); ?>>
								<span class="vigil-toggle-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Weekly check for modified WordPress core files.', 'vigil-security' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="file_integrity_email">
								<?php esc_html_e( 'Alert Email', 'vigil-security' ); ?>
							</label>
						</th>
						<td>
							<input type="email" name="file_integrity_email" id="file_integrity_email" value="<?php echo esc_attr( $this->settings['file_integrity_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Email address to receive alerts about file changes.', 'vigil-security' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
		<p class="submit">
			<button type="submit" class="button button-primary button-large">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Save All Settings', 'vigil-security' ); ?>
			</button>
		</p>
	</form>