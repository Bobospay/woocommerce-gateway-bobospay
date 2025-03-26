/* global jQuery, wp */

(function( $ ) {
	'use strict';
	$('.woocommerce-gateway-bobospay-dismiss-deps-warning-message').on('click', '.notice-dismiss', function() {
		$.post(bobospayAjax.ajax_url, {
			action: "bobospay_dismiss_notice_message",
			dismiss_action: "woocommerce_gateway_bobospay_dismiss_deps_warning_message",
			nonce: bobospayAjax.nonce
		});
	});

})( jQuery );
