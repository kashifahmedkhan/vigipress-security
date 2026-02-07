<?php
/**
 * Dashboard view template.
 *
 * @package    VigiGuard_Security
 * @subpackage VigiGuard_Security/admin/views
 * @since      1.0.0
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wrap vigiguard-wrap">
	<h1 class="vigiguard-page-title">
		<span class="dashicons dashicons-shield"></span>
		<?php esc_html_e( 'VigiGuard Security Dashboard', 'vigiguard-security' ); ?>
	</h1>

	<div class="vigiguard-dashboard">
		<!-- Health Score Card -->
		<div class="vigiguard-card vigiguard-health-card">
			<div class="vigiguard-card-header">
				<h2><?php esc_html_e( 'Security Health Score', 'vigiguard-security' ); ?></h2>
			</div>
			<div class="vigiguard-card-body">
				<div class="vigiguard-health-score">
					<div class="vigiguard-score-circle" style="border-color: <?php echo esc_attr( $health_data['color'] ); ?>">
						<div class="vigiguard-score-grade" style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['grade'] ); ?>
						</div>
						<div class="vigiguard-score-number">
							<?php echo esc_html( $health_score ); ?>/100
						</div>
					</div>
					<div class="vigiguard-score-status">
						<span style="color: <?php echo esc_attr( $health_data['color'] ); ?>">
							<?php echo esc_html( $health_data['status'] ); ?>
						</span>
					</div>
				</div>

				<div class="vigiguard-progress-bar">
					<div class="vigiguard-progress-fill" style="width: <?php echo esc_attr( $health_score ); ?>%; background-color: <?php echo esc_attr( $health_data['color'] ); ?>"></div>
				</div>

				<?php if ( $health_score < 90 ) : ?>
					<div class="vigiguard-quick-action">
						<p>
							<strong>
								<?php
								printf(
									/* translators: %d: number of security issues */
									esc_html( _n( '%d security issue found', '%d security issues found', count( $issues ), 'vigiguard-security' ) ),
									count( $issues )
								);
								?>
							</strong>
						</p>
						<button type="button" class="button button-primary button-hero vigiguard-fix-all-btn">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( 'Fix All Issues (One Click)', 'vigiguard-security' ); ?>
						</button>
					</div>
				<?php else : ?>
					<div class="vigiguard-success-message">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Excellent! Your site is well protected.', 'vigiguard-security' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Security Issues List -->
		<div class="vigiguard-card vigiguard-issues-card">
			<div class="vigiguard-card-header">
				<h2><?php esc_html_e( 'Security Checklist', 'vigiguard-security' ); ?></h2>
			</div>
			<div class="vigiguard-card-body">
				<?php if ( ! empty( $issues ) ) : ?>
					<ul class="vigiguard-issues-list">
						<?php foreach ( $issues as $issue ) : ?>
							<li class="vigiguard-issue vigiguard-issue-<?php echo esc_attr( $issue['severity'] ); ?>">
								<span class="vigiguard-issue-icon">
									<?php if ( 'critical' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<?php elseif ( 'warning' === $issue['severity'] ) : ?>
										<span class="dashicons dashicons-info" style="color: #f59e0b;"></span>
									<?php else : ?>
										<span class="dashicons dashicons-info-outline" style="color: #3b82f6;"></span>
									<?php endif; ?>
								</span>
								<div class="vigiguard-issue-content">
									<strong><?php echo esc_html( $issue['title'] ); ?></strong>
									<p><?php echo esc_html( $issue['description'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="vigiguard-no-issues">
						<span class="dashicons dashicons-yes-alt"></span>
						<p><?php esc_html_e( 'No security issues detected!', 'vigiguard-security' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Quick Stats -->
		<div class="vigiguard-stats-grid">
			<div class="vigiguard-stat-card">
				<div class="vigiguard-stat-icon">
					<span class="dashicons dashicons-lock"></span>
				</div>
				<div class="vigiguard-stat-content">
					<div class="vigiguard-stat-value">
						<?php echo ! empty( $this->settings['login_protection_enabled'] ) ? esc_html__( 'Active', 'vigiguard-security' ) : esc_html__( 'Inactive', 'vigiguard-security' ); ?>
					</div>
					<div class="vigiguard-stat-label"><?php esc_html_e( 'Login Protection', 'vigiguard-security' ); ?></div>
				</div>
			</div>

			<div class="vigiguard-stat-card">
				<div class="vigiguard-stat-icon">
					<span class="dashicons dashicons-admin-tools"></span>
				</div>
				<div class="vigiguard-stat-content">
					<div class="vigiguard-stat-value">
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
					<div class="vigiguard-stat-label"><?php esc_html_e( 'Hardening Rules', 'vigiguard-security' ); ?></div>
				</div>
			</div>

			<div class="vigiguard-stat-card">
				<div class="vigiguard-stat-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="vigiguard-stat-content">
					<div class="vigiguard-stat-value">
						<?php
						global $wpdb;
						$table_name = $wpdb->prefix . 'vigiguard_logs';
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$log_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
						echo esc_html( number_format_i18n( $log_count ) );
						?>
					</div>
					<div class="vigiguard-stat-label"><?php esc_html_e( 'Logged Events', 'vigiguard-security' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Locked IPs Widget -->
		<?php
		// Get locked IPs if login protection module is available.
		if ( class_exists( '\VigiGuard_Security\Modules\Login_Protection' ) ) {
			$login_protection = new \VigiGuard_Security\Modules\Login_Protection();
			$locked_ips       = $login_protection->get_locked_ips();

			if ( ! empty( $locked_ips ) ) :
				?>
				<div class="vigiguard-card vigiguard-locked-ips-card" style="grid-column: 1 / -1;">
					<div class="vigiguard-card-header">
						<h2><?php esc_html_e( 'Currently Blocked IP Addresses', 'vigiguard-security' ); ?></h2>
					</div>
					<div class="vigiguard-card-body">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'vigiguard-security' ); ?></th>
									<th><?php esc_html_e( 'Username Attempted', 'vigiguard-security' ); ?></th>
									<th><?php esc_html_e( 'Failed Attempts', 'vigiguard-security' ); ?></th>
									<th><?php esc_html_e( 'Locked At', 'vigiguard-security' ); ?></th>
									<th><?php esc_html_e( 'Time Remaining', 'vigiguard-security' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'vigiguard-security' ); ?></th>
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
												esc_html( _n( '%d minute', '%d minutes', $minutes, 'vigiguard-security' ) ),
												esc_html( $minutes )
											);
											?>
										</td>
										<td>
											<button 
												type="button" 
												class="button button-small vigiguard-unlock-ip-btn" 
												data-ip="<?php echo esc_attr( $lock['ip'] ); ?>"
											>
												<?php esc_html_e( 'Unlock', 'vigiguard-security' ); ?>
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
		$last_check = get_option( 'vigiguard_last_file_check', false );
		if ( ! empty( $this->settings['file_integrity_enabled'] ) ) :
			?>
			<div class="vigiguard-card vigiguard-file-integrity-card" style="grid-column: 1 / -1;">
				<div class="vigiguard-card-header">
					<h2><?php esc_html_e( 'File Integrity Monitor', 'vigiguard-security' ); ?></h2>
				</div>
				<div class="vigiguard-card-body">
					<?php if ( $last_check ) : ?>
						<div class="vigiguard-file-check-results">
							<p>
								<strong><?php esc_html_e( 'Last Scan:', 'vigiguard-security' ); ?></strong>
								<?php
								echo esc_html(
									wp_date(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$last_check['timestamp']
									)
								);
								?>
							</p>
							<div class="vigiguard-file-stats">
								<div class="vigiguard-file-stat">
									<span class="vigiguard-file-stat-number"><?php echo esc_html( $last_check['results']['checked'] ); ?></span>
									<span class="vigiguard-file-stat-label"><?php esc_html_e( 'Files Checked', 'vigiguard-security' ); ?></span>
								</div>
								<div class="vigiguard-file-stat <?php echo ! empty( $last_check['results']['modified'] ) ? 'vigiguard-file-stat-warning' : ''; ?>">
									<span class="vigiguard-file-stat-number"><?php echo esc_html( count( $last_check['results']['modified'] ) ); ?></span>
									<span class="vigiguard-file-stat-label"><?php esc_html_e( 'Modified', 'vigiguard-security' ); ?></span>
								</div>
								<div class="vigiguard-file-stat <?php echo ! empty( $last_check['results']['unexpected'] ) ? 'vigiguard-file-stat-critical' : ''; ?>">
									<span class="vigiguard-file-stat-number"><?php echo esc_html( count( $last_check['results']['unexpected'] ) ); ?></span>
									<span class="vigiguard-file-stat-label"><?php esc_html_e( 'Unexpected', 'vigiguard-security' ); ?></span>
								</div>
							</div>

							<?php if ( ! empty( $last_check['results']['modified'] ) || ! empty( $last_check['results']['unexpected'] ) ) : ?>
								<div class="vigiguard-file-alert">
									<span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
									<strong><?php esc_html_e( 'Security Alert:', 'vigiguard-security' ); ?></strong>
									<?php esc_html_e( 'Modified or unexpected files detected. Check your email for details.', 'vigiguard-security' ); ?>
								</div>
							<?php else : ?>
								<div class="vigiguard-file-success">
									<span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
									<?php esc_html_e( 'All core files are intact!', 'vigiguard-security' ); ?>
								</div>
							<?php endif; ?>

							<button type="button" class="button vigiguard-run-file-check-btn" style="margin-top: 15px;">
								<span class="dashicons dashicons-update-alt"></span>
								<?php esc_html_e( 'Run Manual Scan', 'vigiguard-security' ); ?>
							</button>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No file integrity scan has been run yet.', 'vigiguard-security' ); ?></p>
						<button type="button" class="button button-primary vigiguard-run-file-check-btn">
							<span class="dashicons dashicons-update-alt"></span>
							<?php esc_html_e( 'Run First Scan', 'vigiguard-security' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php
		endif;
		?>

	</div><!-- vigiguard-dashboard -->
</div><!-- wrap -->