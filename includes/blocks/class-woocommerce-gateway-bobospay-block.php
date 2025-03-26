<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

//use Automattic\WooCommerce\Blocks\Payments\Integrations\PaymentContext;
//use Automattic\WooCommerce\Blocks\Payments\PaymentResult;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Woocommerce_Gateway_Bobospay_Block extends AbstractPaymentMethodType {

	protected $name = 'woocommerce_gateway_bobospay_block';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_woocommerce_gateway_bobospay_settings', [] );

//		add_action( 'woocommerce_rest_checkout_process_payment_with_context', [
//			$this,
//			'add_payment_request_order_meta'
//		], 8, 2 );
	}

	public function is_active() {
		return true;

		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'woocommerce-gateway-bobospay-block-script',
			plugin_dir_url( dirname( __DIR__ ) ) . 'public/js/woocommerce-gateway-bobospay-block.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			null,
			true
		);

		return [ 'woocommerce-gateway-bobospay-block-script' ];
	}

	public function get_payment_method_data(): array {

		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => [ 'products' ],
		];
	}

	public function add_payment_request_order_meta( PaymentContext $context, PaymentResult $result ) {

		$data = $context->payment_data;

		if ( $context->payment_method == 'woocommerce_gateway_bobospay' ) {
			$context->set_payment_data( $data );
		}

	}
}
