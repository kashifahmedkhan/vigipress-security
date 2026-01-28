<?php
/**
 * Activity logs page view template.
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
?>

<div class="wrap vigipress-wrap">
	<h1 class="vigipress-page-title">
		<span class="dashicons dashicons-clipboard"></span>
		<?php esc_html_e( 'Activity Log', 'vigipress-security' ); ?>
	</h1>

	<div class="vigipress-card">
		<div class="vigipress-card-body">
			<?php if ( ! empty( $logs ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date & Time', 'vigipress-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Event', 'vigipress-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'User', 'vigipress-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'IP Address', 'vigipress-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Severity', 'vigipress-security' ); ?></th>
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
										echo '<em>' . esc_html__( 'Guest', 'vigipress-security' ) . '</em>';
									}
									?>
								</td>
								<td>
									<code><?php echo esc_html( $log->ip_address ); ?></code>
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
									<span class="vigipress-badge" style="background-color: <?php echo esc_attr( $color ); ?>">
										<?php echo esc_html( ucfirst( $log->severity ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="vigipress-no-logs">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No activity logs found.', 'vigipress-security' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>