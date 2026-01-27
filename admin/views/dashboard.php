<?php
/**
 * Dashboard view template.
 *
 * @package    Vigil_Security
 * @subpackage Vigil_Security/admin/views
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wrap vigil-wrap">
	<h1 class="vigil-page-title">
		<span class="dashicons dashicons-shield"></span>
		<?php esc_html_e( 'Vigil Security Dashboard', 'vigil-security' ); ?>
	</h1>

	<div class="vigil-dashboard">
		<!-- Health Score Card -->
		<div class="vigil-card vigil-health-card">
			<div class="vigil-card-header">
				<h2><?php esc_html_e( 'Security Health Score', 'vigil-security' ); ?></h2>
			</div>
			<div class="vigil-card-body">
				<div class="vigil-health-score">
					<div class="vigil-score-circle" style="border-color: <?php echo esc_attr( $health_data['color'] ); ?>">
						<div class="vigil-score-grade" style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['grade'] ); ?>
						</div>
						<div class="vigil-score-number">
							<?php echo esc_html( $health_score ); ?>/100
						</div>
					</div>
					<div class="vigil-score-status">
						<span style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['status'] ); ?>
						</span>
					</div>
				</div>

				<div class="vigil-progress-bar">
					<div class="vigil-progress-fill" style="width: <?php echo esc_attr( $health_score ); ?>%; background-color: <?php echo esc_attr( $health_data['color'] ); ?>"></div>
				</div>

				<?php if ( $health_score < 90 ) : ?>
					<div class="vigil-quick-action">
						<p>
							<strong>
								<?php
								printf(
									/* translators: %d: number of security issues */
									esc_html( _n( '%d security issue found', '%d security issues found', count( $issues ), 'vigil-security' ) ),
									count( $issues )
								);
								?>
							</strong>
						</p>
						<button type="button" class="button button-primary button-hero vigil-fix-all-btn">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( 'Fix All Issues (One Click)', 'vigil-security' ); ?>
						</button>
					</div>
				<?php else : ?>
					<div class="vigil-success-message">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Excellent! Your site is well protected.', 'vigil-security' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Security Issues List -->
		<div class="vigil-card vigil-issues-card">
			<div class="vigil-card-header">
				<h2><?php esc_html_e( 'Security Checklist', 'vigil-security' ); ?></h2>
			</div>
			<div class="vigil-card-body">
				<?php if ( ! empty( $issues ) ) : ?>
					<ul class="vigil-issues-list">
						<?php foreach ( $issues as $issue ) : ?>
							<li class="vigil-issue vigil-issue-<?php echo esc_attr( $issue['severity'] ); ?>">
								<span class="vigil-issue-icon">
									<?php if ( 'critical' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<?php elseif ( 'warning' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-info" style="color: #f59e0b;"></span>
									<?php else : ?>
										<span class="dashicons dashicons-info-outline" style="color: #3b82f6;"></span>
									<?php endif; ?>
								</span>
								<div class="vigil-issue-content">
									<strong><?php echo esc_html( $issue['title'] ); ?></strong>
									<p><?php echo esc_html( $issue['description'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="vigil-no-issues">
						<span class="dashicons dashicons-yes-alt"></span>
						<p><?php esc_html_e( 'No security issues detected!', 'vigil-security' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Quick Stats -->
		<div class="vigil-stats-grid">
			<div class="vigil-stat-card">
				<div class="vigil-stat-icon">
					<span class="dashicons dashicons-lock"></span>
				</div>
				<div class="vigil-stat-content">
					<div class="vigil-stat-value">
						<?php echo ! empty( $this->settings['login_protection_enabled'] ) ? esc_html__( 'Active', 'vigil-security' ) : esc_html__( 'Inactive', 'vigil-security' ); ?>
					</div>
					<div class="vigil-stat-label"><?php esc_html_e( 'Login Protection', 'vigil-security' ); ?></div>
				</div>
			</div>

			<div class="vigil-stat-card">
				<div class="vigil-stat-icon">
					<span class="dashicons dashicons-admin-tools"></span>
				</div>
				<div class="vigil-stat-content">
					<div class="vigil-stat-value">
						<?php
						$enabled_count = 0;
						if ( ! empty( $this->settings['disable_xmlrpc'] ) ) {
							$enabled_count++;
						}
						if ( ! empty( $this->settings['disable_file_edit'] ) ) {
							$enabled_count++;
						}
						if ( ! empty( $this->settings['hide_wp_version'] ) ) {
							$enabled_count++;
						}
						echo esc_html( $enabled_count ) . '/3';
						?>
					</div>
					<div class="vigil-stat-label"><?php esc_html_e( 'Hardening Rules', 'vigil-security' ); ?></div>
				</div>
			</div>

			<div class="vigil-stat-card">
				<div class="vigil-stat-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="vigil-stat-content">
					<div class="vigil-stat-value">
						<?php
						global $wpdb;
						$table_name = $wpdb->prefix . 'vigil_logs';
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$log_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
						echo esc_html( number_format_i18n( $log_count ) );
						?>
					</div>
					<div class="vigil-stat-label"><?php esc_html_e( 'Logged Events', 'vigil-security' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Locked IPs Widget -->
		<?php
		// Get locked IPs if login protection module is available.
		if ( class_exists( '\Vigil_Security\Modules\Login_Protection' ) ) {
			$login_protection = new \Vigil_Security\Modules\Login_Protection();
			$locked_ips       = $login_protection->get_locked_ips();

			if ( ! empty( $locked_ips ) ) :
				?>
				<div class="vigil-card vigil-locked-ips-card" style="grid-column: 1 / -1;">
					<div class="vigil-card-header">
						<h2><?php esc_html_e( 'Currently Blocked IP Addresses', 'vigil-security' ); ?></h2>
					</div>
					<div class="vigil-card-body">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'vigil-security' ); ?></th>
									<th><?php esc_html_e( 'Username Attempted', 'vigil-security' ); ?></th>
									<th><?php esc_html_e( 'Failed Attempts', 'vigil-security' ); ?></th>
									<th><?php esc_html_e( 'Locked At', 'vigil-security' ); ?></th>
									<th><?php esc_html_e( 'Time Remaining', 'vigil-security' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'vigil-security' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $locked_ips as $lock ) : ?>
									<tr>
										<td><code><?php echo esc_html( $lock['ip'] ); ?></code></td>
										<td><?php echo esc_html( $lock['attempted_user'] ); ?></td>
										<td><?php echo esc_html( $lock['attempt_count'] ); ?></td>
										<td>
											<?php
											echo esc_html(
												wp_date(
													get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
													$lock['locked_at']
												)
											);
											?>
										</td>
										<td>
											<?php
											$minutes = ceil( $lock['remaining_time'] / 60 );
											printf(
												/* translators: %d: minutes remaining */
												esc_html( _n( '%d minute', '%d minutes', $minutes, 'vigil-security' ) ),
												esc_html( $minutes )
											);
											?>
										</td>
										<td>
											<button 
												type="button" 
												class="button button-small vigil-unlock-ip-btn" 
												data-ip="<?php echo esc_attr( $lock['ip'] ); ?>"
											>
												<?php esc_html_e( 'Unlock', 'vigil-security' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php
			endif;
		}
		?>

		<!-- File Integrity Widget -->
		<?php
		// Get last file check results.
		$last_check = get_option( 'vigil_last_file_check', false );
		if ( ! empty( $this->settings['file_integrity_enabled'] ) ) :
			?>
			<div class="vigil-card vigil-file-integrity-card" style="grid-column: 1 / -1;">
				<div class="vigil-card-header">
					<h2><?php esc_html_e( 'File Integrity Monitor', 'vigil-security' ); ?></h2>
				</div>
				<div class="vigil-card-body">
					<?php if ( $last_check ) : ?>
						<div class="vigil-file-check-results">
							<p>
								<strong><?php esc_html_e( 'Last Scan:', 'vigil-security' ); ?></strong>
								<?php
								echo esc_html(
									wp_date(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$last_check['timestamp']
									)
								);
								?>
							</p>
							<div class="vigil-file-stats">
								<div class="vigil-file-stat">
									<span class="vigil-file-stat-number"><?php echo esc_html( $last_check['results']['checked'] ); ?></span>
									<span class="vigil-file-stat-label"><?php esc_html_e( 'Files Checked', 'vigil-security' ); ?></span>
								</div>
								<div class="vigil-file-stat <?php echo ! empty( $last_check['results']['modified'] ) ? 'vigil-file-stat-warning' : ''; ?>">
									<span class="vigil-file-stat-number"><?php echo esc_html( count( $last_check['results']['modified'] ) ); ?></span>
									<span class="vigil-file-stat-label"><?php esc_html_e( 'Modified', 'vigil-security' ); ?></span>
								</div>
								<div class="vigil-file-stat <?php echo ! empty( $last_check['results']['unexpected'] ) ? 'vigil-file-stat-critical' : ''; ?>">
									<span class="vigil-file-stat-number"><?php echo esc_html( count( $last_check['results']['unexpected'] ) ); ?></span>
									<span class="vigil-file-stat-label"><?php esc_html_e( 'Unexpected', 'vigil-security' ); ?></span>
								</div>
							</div>

							<?php if ( ! empty( $last_check['results']['modified'] ) || ! empty( $last_check['results']['unexpected'] ) ) : ?>
								<div class="vigil-file-alert">
									<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<strong><?php esc_html_e( 'Security Alert:', 'vigil-security' ); ?></strong>
									<?php esc_html_e( 'Modified or unexpected files detected. Check your email for details.', 'vigil-security' ); ?>
								</div>
							<?php else : ?>
								<div class="vigil-file-success">
									<span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
									<?php esc_html_e( 'All core files are intact!', 'vigil-security' ); ?>
								</div>
							<?php endif; ?>

							<button type="button" class="button vigil-run-file-check-btn" style="margin-top: 15px;">
								<span class="dashicons dashicons-update-alt"></span>
								<?php esc_html_e( 'Run Manual Scan', 'vigil-security' ); ?>
							</button>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No file integrity scan has been run yet.', 'vigil-security' ); ?></p>
						<button type="button" class="button button-primary vigil-run-file-check-btn">
							<span class="dashicons dashicons-update-alt"></span>
							<?php esc_html_e( 'Run First Scan', 'vigil-security' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php
		endif;
		?>

	</div><!-- .vigil-dashboard -->
</div><!-- .wrap -->