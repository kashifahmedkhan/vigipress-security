<?php
/**
 * Activity logs page view template.
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
		<span class="dashicons dashicons-clipboard"></span>
		<?php esc_html_e( 'Activity Log', 'vigil-security' ); ?>
	</h1>

	<div class="vigil-card">
		<div class="vigil-card-body">
			<?php if ( ! empty( $logs ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date & Time', 'vigil-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Event', 'vigil-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'User', 'vigil-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'IP Address', 'vigil-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Severity', 'vigil-security' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td>
									<?php
									echo esc_html(
										wp_date(
											get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
											strtotime( $log->created_at )
										)
									);
									?>
								</td>
								<td>
									<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $log->event_type ) ) ); ?></strong>
									<?php if ( ! empty( $log->description ) ) : ?>
										<br><small><?php echo esc_html( $log->description ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<?php
									if ( ! empty( $log->username ) ) {
										echo esc_html( $log->username );
									} else {
										echo '<em>' . esc_html__( 'Guest', 'vigil-security' ) . '</em>';
									}
									?>
								</td>
								<td>
									<code>
										<?php
										$ip_display = $log->ip_address;
										// Make localhost IPs more readable
										if ( $ip_display === '::1' ) {
											$ip_display = '::1 (localhost IPv6)';
										} elseif ( $ip_display === '127.0.0.1' ) {
											$ip_display = '127.0.0.1 (localhost)';
										}
										echo esc_html( $ip_display );
										?>
									</code>
								</td>
								<td>
									<?php
									$severity_colors = array(
										'info'     => '#3b82f6',
										'warning'  => '#f59e0b',
										'critical' => '#dc2626',
									);
									$color           = $severity_colors[ $log->severity ] ?? '#6b7280';
									?>
									<span class="vigil-badge" style="background-color: <?php echo esc_attr( $color ); ?>">
										<?php echo esc_html( ucfirst( $log->severity ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="vigil-no-logs">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No activity logs found.', 'vigil-security' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>