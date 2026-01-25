/**
 * Vigil Security Admin JavaScript
 *
 * @package Vigil_Security
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
		$('.vigil-fix-all-btn').on('click', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const originalText = $button.html();
			
			// Disable button and show loading state
			$button.prop('disabled', true);
			$button.html('<span class="dashicons dashicons-update spin"></span> Fixing issues...');
			
			// TODO: This will be implemented in Prompt #9 (AJAX functionality)
			// For now, just show a message
			setTimeout(function() {
				$button.prop('disabled', false);
				$button.html(originalText);
				alert('One-click fix functionality will be implemented in the next update!');
			}, 1000);
		});

		/**
		 * Add spinning animation to dashicons.
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
		`;
		document.head.appendChild(style);

	});

})(jQuery);