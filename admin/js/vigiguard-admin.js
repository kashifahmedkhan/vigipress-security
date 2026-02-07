/**
 * VigiGuard Security Admin JavaScript
 *
 * @package VigiGuard_Security
 * @since      1.0.0
 * 
 */

(function($) {
	'use strict';

	/**
	 * Initialize when document is ready.
	 */
	$(document).ready(function() {
		
		/**
		 * Handle "Fix All Issues" button click.
		 */
		$('.vigiguard-fix-all-btn').on('click', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const $healthCard = $('.vigiguard-health-card');
			const $issuesCard = $('.vigiguard-issues-card');
			const $scoreCircle = $('.vigiguard-score-circle');
			const $scoreGrade = $('.vigiguard-score-grade');
			const $scoreNumber = $('.vigiguard-score-number');
			const $scoreStatus = $('.vigiguard-score-status span');
			const $progressFill = $('.vigiguard-progress-fill');
			
			// Disable button and show loading state
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Applying fixes...');
			
			// Add loading overlay to cards
			$healthCard.css('opacity', '0.6');
			$issuesCard.css('opacity', '0.6');
			
			// Send AJAX request
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_fix_all_issues',
					nonce: vigiguardSecurity.nonce
				},
				success: function(response) {
					if (response.success) {
						const data = response.data;
						
						// Animate the score change
						animateScoreChange(data, $scoreCircle, $scoreGrade, $scoreNumber, $scoreStatus, $progressFill);
						
						// Update button to success state
						$button.html('<span class="dashicons dashicons-yes"></span> All Fixed!').css({
							'background-color': '#10b981',
							'border-color': '#10b981'
						});
						
						// Remove loading overlay
						$healthCard.css('opacity', '1');
						$issuesCard.css('opacity', '1');
						
						// Show success banner at top
						showSuccessBanner(data);
						
						// Replace issues list with success message
						setTimeout(function() {
							$issuesCard.find('.vigiguard-card-body').fadeOut(300, function() {
								$(this).html(`
									<div class="vigiguard-no-issues">
										<span class="dashicons dashicons-yes-alt"></span>
										<p>No security issues detected!</p>
									</div>
								`).fadeIn(300);
							});
						}, 800);
						
						// Update stats cards
						updateStatsCards();
						
					} else {
						// Show error
						$button.prop('disabled', false).html('<span class="dashicons dashicons-shield"></span> Fix All Issues (One Click)');
						$healthCard.css('opacity', '1');
						$issuesCard.css('opacity', '1');
						showErrorBanner(response.data.message);
					}
				},
				error: function() {
					// Show error
					$button.prop('disabled', false).html('<span class="dashicons dashicons-shield"></span> Fix All Issues (One Click)');
					$healthCard.css('opacity', '1');
					$issuesCard.css('opacity', '1');
					showErrorBanner('An error occurred. Please refresh and try again.');
				}
			});
		});

		/**
		 * Animate score change from old to new value.
		 */
		function animateScoreChange(data, $circle, $grade, $number, $status, $progress) {
			const oldScore = data.old_score;
			const newScore = data.new_score;
			const duration = 1500; // 1.5 seconds
			const steps = 60;
			const increment = (newScore - oldScore) / steps;
			let currentScore = oldScore;
			let step = 0;
			
			const interval = setInterval(function() {
				step++;
				currentScore += increment;
				
				if (step >= steps) {
					currentScore = newScore;
					clearInterval(interval);
				}
				
				// Update score number
				$number.text(Math.round(currentScore) + '/100');
				
				// Update progress bar
				$progress.css('width', Math.round(currentScore) + '%');
				
				// Update color and grade when we reach final score
				if (step >= steps) {
					$circle.css('border-color', data.color);
					$grade.css('color', data.color).text(data.grade);
					$status.css('color', data.color).text(data.status);
					$progress.css('background-color', data.color);
					
					// Add pulse animation
					$circle.addClass('vigiguard-pulse');
					setTimeout(function() {
						$circle.removeClass('vigiguard-pulse');
					}, 1000);
				}
			}, duration / steps);
		}

		/**
		 * Show success banner at top of page.
		 */
		function showSuccessBanner(data) {
			const $banner = $(`
				<div class="notice notice-success vigiguard-success-banner" style="display:none;">
					<p>
						<strong>Security Enhanced!</strong><br>
						Your security score improved from <strong>${data.old_score}</strong> to <strong>${data.new_score}</strong>. 
						${data.issues_fixed} security issues were fixed automatically.
						<a href="admin.php?page=vigiguard-security-settings" style="margin-left: 10px;">View Settings →</a>
					</p>
				</div>
			`);
			
			$('.vigiguard-wrap').prepend($banner);
			$banner.slideDown(300);
			
			// Scroll to top smoothly
			$('html, body').animate({ scrollTop: 0 }, 400);
		}

		/**
		 * Show error banner at top of page.
		 */
		function showErrorBanner(message) {
			const $banner = $(`
				<div class="notice notice-error vigiguard-error-banner" style="display:none;">
					<p><strong>Error:</strong> ${message}</p>
				</div>
			`);
			
			$('.vigiguard-wrap').prepend($banner);
			$banner.slideDown(300);
			
			// Auto-remove after 5 seconds
			setTimeout(function() {
				$banner.slideUp(300, function() { $(this).remove(); });
			}, 5000);
		}

		/**
		 * Update stats cards to reflect new settings.
		 */
		function updateStatsCards() {
			// Update "Login Protection" stat
			$('.vigiguard-stat-card').eq(0).find('.vigiguard-stat-value').fadeOut(200, function() {
				$(this).text('Active').css('color', '#10b981').fadeIn(200);
			});
			
			// Update "Hardening Rules" stat
			$('.vigiguard-stat-card').eq(1).find('.vigiguard-stat-value').fadeOut(200, function() {
				$(this).text('3/3').css('color', '#10b981').fadeIn(200);
			});
		}

		/**
		 * Add CSS animations.
		 */
		const style = document.createElement('style');
		style.innerHTML = `
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
			.dashicons.spin {
				animation: spin 1s linear infinite;
			}
			@keyframes pulse {
				0%, 100% { transform: scale(1); }
				50% { transform: scale(1.05); }
			}
			.vigiguard-pulse {
				animation: pulse 0.6s ease-in-out;
			}
			.vigiguard-success-banner {
				border-left: 4px solid #10b981;
			}
			.vigiguard-error-banner {
				border-left: 4px solid #dc2626;
			}
		`;
		document.head.appendChild(style);

		/**
		 * Handle custom dismissible notices (AJAX method).
		 */
		$(document).on('click', '.vigiguard-dismissible-notice .notice-dismiss', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $notice = $(this).closest('.notice');
			const noticeId = $notice.data('notice-id');
			
			if (!noticeId) {
				console.error('VigiGuard Security: No notice ID found');
				return;
			}
			
			// Immediately hide the notice (optimistic)
			$notice.fadeOut(200, function() {
				$(this).remove();
			});
			
			// Send AJAX to save dismissal
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_dismiss_notice',
					nonce: vigiguardSecurity.nonce,
					notice_id: noticeId
				},
				error: function(xhr, status, error) {
					console.error('VigiGuard Security: Failed to save notice dismissal', {
						status: status,
						error: error
					});
				}
			});
		});

		/**
		 * Handle IP unlock button clicks.
		 */
		$(document).on('click', '.vigiguard-unlock-ip-btn', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const ip = $button.data('ip');
			const $row = $button.closest('tr');
			
			if (!confirm('Unlock IP address ' + ip + '?')) {
				return;
			}
			
			// Disable button
			$button.prop('disabled', true).text('Unlocking...');
			
			// Send AJAX request
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_unlock_ip',
					nonce: vigiguardSecurity.nonce,
					ip: ip
				},
				success: function(response) {
					if (response.success) {
						// Remove row with fade effect
						$row.fadeOut(300, function() {
							$(this).remove();
							
							// If no more rows, hide the entire table
							if ($('.vigiguard-locked-ips-card tbody tr').length === 0) {
								$('.vigiguard-locked-ips-card').fadeOut(300, function() {
									$(this).remove();
								});
							}
						});
					} else {
						alert('Error: ' + response.data.message);
						$button.prop('disabled', false).text('Unlock');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$button.prop('disabled', false).text('Unlock');
				}
			});
		});

		/**
		 * Handle manual file integrity check button.
		 */
		$(document).on('click', '.vigiguard-run-file-check-btn', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const originalHtml = $button.html();
			
			// Disable button and show loading
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Scanning files...');
			
			// Send AJAX request
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_run_file_check',
					nonce: vigiguardSecurity.nonce
				},
				success: function(response) {
					if (response.success) {
						const data = response.data;
						
						// Show success message
						$button.html('<span class="dashicons dashicons-yes"></span> Scan Complete!').css('background-color', '#10b981');
						
						// Show results summary
						const resultMsg = `File scan completed!\n\nFiles checked: ${data.checked}\nModified files: ${data.modified}\nUnexpected files: ${data.unexpected}`;
						
						if (data.modified > 0 || data.unexpected > 0) {
							alert(resultMsg + '\n\nSecurity issues detected! Check your email for details.');
						} else {
							alert(resultMsg + '\n\nAll core files are intact!');
						}
						
						// Reload page after 2 seconds to show updated results
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						alert('Error: ' + response.data.message);
						$button.prop('disabled', false).html(originalHtml);
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$button.prop('disabled', false).html(originalHtml);
				}
			});
		});


		/**
		 * Handle "Reset Plugin" button.
		 */
		$(document).on('click', '.vigiguard-reset-plugin-btn', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			
			if (!confirm('Are you sure you want to reset all settings to defaults?\n\nThis will:\n• Reset all security settings\n• Keep your security logs\n• Require reconfiguration\n\nThis cannot be undone.')) {
				return;
			}
			
			// Disable button
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Resetting...');
			
			// Send AJAX request
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_reset_plugin',
					nonce: vigiguardSecurity.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message + '\n\nThe page will now reload.');
						location.reload();
					} else {
						alert('Error: ' + response.data.message);
						$button.prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Reset All Settings');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$button.prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Reset All Settings');
				}
			});
		});

		/**
		 * Handle "Clear All Logs" button.
		 */
		$(document).on('click', '.vigiguard-clear-logs-btn', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			
			if (!confirm('Are you sure you want to permanently delete ALL security logs?\n\nThis will:\n• Delete all login attempts\n• Delete all security events\n• Delete all activity history\n\nThis cannot be undone.')) {
				return;
			}
			
			// Double confirmation for destructive action
			if (!confirm('FINAL WARNING: This will permanently delete all security logs.\n\nAre you absolutely sure?')) {
				return;
			}
			
			// Disable button
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Clearing...');
			
			// Send AJAX request
			$.ajax({
				url: vigiguardSecurity.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vigiguard_clear_logs',
					nonce: vigiguardSecurity.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message + '\n\nThe page will now reload.');
						location.reload();
					} else {
						alert('Error: ' + response.data.message);
						$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear All Logs');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear All Logs');
				}
			});
		});


	});

})(jQuery);