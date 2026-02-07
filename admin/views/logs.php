<?php
/**
 * Activity logs page view template.
 *
 * @package    VigiGuard_Security
 * @subpackage VigiGuard_Security/admin/views
 * @since      1.0.0
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="wrap vigiguard-wrap">
	<h1 class="vigiguard-page-title">
		<span class="dashicons dashicons-clipboard"></span>
		<?php esc_html_e( 'Activity Log', 'vigiguard-security' ); ?>
	</h1>

	<div class="vigiguard-card">
		<div class="vigiguard-card-body">
			<?php if ( ! empty( $logs ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date & Time', 'vigiguard-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Event', 'vigiguard-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'User', 'vigiguard-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'IP Address', 'vigiguard-security' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Severity', 'vigiguard-security' ); ?></th>
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
										echo '<em>' . esc_html__( 'Guest', 'vigiguard-security' ) . '</em>';
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
									<span class="vigiguard-badge" style="background-color: <?php echo esc_attr( $color ); ?>">
										<?php echo esc_html( ucfirst( $log->severity ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="vigiguard-no-logs">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No activity logs found.', 'vigiguard-security' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>