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
}
?>

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
	</div>
</div>