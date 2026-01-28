<?php
/**
 * Dashboard view template.
 *
 * @package    VigiPress_Security
 * @subpackage VigiPress_Security/admin/views
 * @since      1.0.0
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wrap vigipress-wrap">
	<h1 class="vigipress-page-title">
		<span class="dashicons dashicons-shield"></span>
		<?php esc_html_e( 'VigiPress Security Dashboard', 'vigipress-security' ); ?>
	</h1>

	<div class="vigipress-dashboard">
		<!-- Health Score Card -->
		<div class="vigipress-card vigipress-health-card">
			<div class="vigipress-card-header">
				<h2><?php esc_html_e( 'Security Health Score', 'vigipress-security' ); ?></h2>
			</div>
			<div class="vigipress-card-body">
				<div class="vigipress-health-score">
					<div class="vigipress-score-circle" style="border-color: <?php echo esc_attr( $health_data['color'] ); ?>">
						<div class="vigipress-score-grade" style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['grade'] ); ?>
						</div>
						<div class="vigipress-score-number">
							<?php echo esc_html( $health_score ); ?>/100
						</div>
					</div>
					<div class="vigipress-score-status">
						<span style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['status'] ); ?>
						</span>
					</div>
				</div>

				<div class="vigipress-progress-bar">
					<div class="vigipress-progress-fill" style="width: <?php echo esc_attr( $health_score ); ?>%; background-color: <?php echo esc_attr( $health_data['color'] ); ?>"></div>
				</div>

				<?php if ( $health_score < 90 ) : ?>
					<div class="vigipress-quick-action">
						<p>
							<strong>
								<?php
								printf(
									/* translators: %d: number of security issues */
									esc_html( _n( '%d security issue found', '%d security issues found', count( $issues ), 'vigipress-security' ) ),
									count( $issues )
								);
								?>
							</strong>
						</p>
						<button type="button" class="button button-primary button-hero vigipress-fix-all-btn">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( 'Fix All Issues (One Click)', 'vigipress-security' ); ?>
						</button>
					</div>
				<?php else : ?>
					<div class="vigipress-success-message">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Excellent! Your site is well protected.', 'vigipress-security' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Security Issues List -->
		<div class="vigipress-card vigipress-issues-card">
			<div class="vigipress-card-header">
				<h2><?php esc_html_e( 'Security Checklist', 'vigipress-security' ); ?></h2>
			</div>
			<div class="vigipress-card-body">
				<?php if ( ! empty( $issues ) ) : ?>
					<ul class="vigipress-issues-list">
						<?php foreach ( $issues as $issue ) : ?>
							<li class="vigipress-issue vigipress-issue-<?php echo esc_attr( $issue['severity'] ); ?>">
								<span class="vigipress-issue-icon">
									<?php if ( 'critical' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<?php elseif ( 'warning' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-info" style="color: #f59e0b;"></span>
									<?php else : ?>
										<span class="dashicons dashicons-info-outline" style="color: #3b82f6;"></span>
									<?php endif; ?>
								</span>
								<div class="vigipress-issue-content">
									<strong><?php echo esc_html( $issue['title'] ); ?></strong>
									<p><?php echo esc_html( $issue['description'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="vigipress-no-issues">
						<span class="dashicons dashicons-yes-alt"></span>
						<p><?php esc_html_e( 'No security issues detected!', 'vigipress-security' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Quick Stats -->
		<div class="vigipress-stats-grid">
			<div class="vigipress-stat-card">
				<div class="vigipress-stat-icon">
					<span class="dashicons dashicons-lock"></span>
				</div>
				<div class="vigipress-stat-content">
					<div class="vigipress-stat-value">
						<?php echo ! empty( $this->settings['login_protection_enabled'] ) ? esc_html__( 'Active', 'vigipress-security' ) : esc_html__( 'Inactive', 'vigipress-security' ); ?>
					</div>
					<div class="vigipress-stat-label"><?php esc_html_e( 'Login Protection', 'vigipress-security' ); ?></div>
				</div>
			</div>

			<div class="vigipress-stat-card">
				<div class="vigipress-stat-icon">
					<span class="dashicons dashicons-admin-tools"></span>
				</div>
				<div class="vigipress-stat-content">
					<div class="vigipress-stat-value">
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
					<div class="vigipress-stat-label"><?php esc_html_e( 'Hardening Rules', 'vigipress-security' ); ?></div>
				</div>
			</div>

			<div class="vigipress-stat-card">
				<div class="vigipress-stat-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="vigipress-stat-content">
					<div class="vigipress-stat-value">
						<?php
						global $wpdb;
						$table_name = $wpdb->prefix . 'vigipress_logs';
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$log_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
						echo esc_html( number_format_i18n( $log_count ) );
						?>
					</div>
					<div class="vigipress-stat-label"><?php esc_html_e( 'Logged Events', 'vigipress-security' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Locked IPs Widget -->
		<?php
		// Get locked IPs if login protection module is available.
		if ( class_exists( '\VigiPress_Security\Modules\Login_Protection' ) ) {
			$login_protection = new \VigiPress_Security\Modules\Login_Protection();
			$locked_ips       = $login_protection->get_locked_ips();

			if ( ! empty( $locked_ips ) ) :
				?>
				<div class="vigipress-card vigipress-locked-ips-card" style="grid-column: 1 / -1;">
					<div class="vigipress-card-header">
						<h2><?php esc_html_e( 'Currently Blocked IP Addresses', 'vigipress-security' ); ?></h2>
					</div>
					<div class="vigipress-card-body">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'vigipress-security' ); ?></th>
									<th><?php esc_html_e( 'Username Attempted', 'vigipress-security' ); ?></th>
									<th><?php esc_html_e( 'Failed Attempts', 'vigipress-security' ); ?></th>
									<th><?php esc_html_e( 'Locked At', 'vigipress-security' ); ?></th>
									<th><?php esc_html_e( 'Time Remaining', 'vigipress-security' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'vigipress-security' ); ?></th>
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
												esc_html( _n( '%d minute', '%d minutes', $minutes, 'vigipress-security' ) ),
												esc_html( $minutes )
											);
											?>
										</td>
										<td>
											<button 
												type="button" 
												class="button button-small vigipress-unlock-ip-btn" 
												data-ip="<?php echo esc_attr( $lock['ip'] ); ?>"
											>
												<?php esc_html_e( 'Unlock', 'vigipress-security' ); ?>
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
		$last_check = get_option( 'vigipress_last_file_check', false );
		if ( ! empty( $this->settings['file_integrity_enabled'] ) ) :
			?>
			<div class="vigipress-card vigipress-file-integrity-card" style="grid-column: 1 / -1;">
				<div class="vigipress-card-header">
					<h2><?php esc_html_e( 'File Integrity Monitor', 'vigipress-security' ); ?></h2>
				</div>
				<div class="vigipress-card-body">
					<?php if ( $last_check ) : ?>
						<div class="vigipress-file-check-results">
							<p>
								<strong><?php esc_html_e( 'Last Scan:', 'vigipress-security' ); ?></strong>
								<?php
								echo esc_html(
									wp_date(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$last_check['timestamp']
									)
								);
								?>
							</p>
							<div class="vigipress-file-stats">
								<div class="vigipress-file-stat">
									<span class="vigipress-file-stat-number"><?php echo esc_html( $last_check['results']['checked'] ); ?></span>
									<span class="vigipress-file-stat-label"><?php esc_html_e( 'Files Checked', 'vigipress-security' ); ?></span>
								</div>
								<div class="vigipress-file-stat <?php echo ! empty( $last_check['results']['modified'] ) ? 'vigipress-file-stat-warning' : ''; ?>">
									<span class="vigipress-file-stat-number"><?php echo esc_html( count( $last_check['results']['modified'] ) ); ?></span>
									<span class="vigipress-file-stat-label"><?php esc_html_e( 'Modified', 'vigipress-security' ); ?></span>
								</div>
								<div class="vigipress-file-stat <?php echo ! empty( $last_check['results']['unexpected'] ) ? 'vigipress-file-stat-critical' : ''; ?>">
									<span class="vigipress-file-stat-number"><?php echo esc_html( count( $last_check['results']['unexpected'] ) ); ?></span>
									<span class="vigipress-file-stat-label"><?php esc_html_e( 'Unexpected', 'vigipress-security' ); ?></span>
								</div>
							</div>

							<?php if ( ! empty( $last_check['results']['modified'] ) || ! empty( $last_check['results']['unexpected'] ) ) : ?>
								<div class="vigipress-file-alert">
									<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<strong><?php esc_html_e( 'Security Alert:', 'vigipress-security' ); ?></strong>
									<?php esc_html_e( 'Modified or unexpected files detected. Check your email for details.', 'vigipress-security' ); ?>
								</div>
							<?php else : ?>
								<div class="vigipress-file-success">
									<span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
									<?php esc_html_e( 'All core files are intact!', 'vigipress-security' ); ?>
								</div>
							<?php endif; ?>

							<button type="button" class="button vigipress-run-file-check-btn" style="margin-top: 15px;">
								<span class="dashicons dashicons-update-alt"></span>
								<?php esc_html_e( 'Run Manual Scan', 'vigipress-security' ); ?>
							</button>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No file integrity scan has been run yet.', 'vigipress-security' ); ?></p>
						<button type="button" class="button button-primary vigipress-run-file-check-btn">
							<span class="dashicons dashicons-update-alt"></span>
							<?php esc_html_e( 'Run First Scan', 'vigipress-security' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php
		endif;
		?>

	</div><!-- .vigipress-dashboard -->
</div><!-- .wrap -->