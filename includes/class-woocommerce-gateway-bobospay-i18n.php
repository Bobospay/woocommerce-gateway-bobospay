<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://bobospay.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/includes
 * @author     Bobospay <support@bobospay.com>
 */
class Woocommerce_Gateway_Bobospay_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woocommerce-gateway-bobospay',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

	}
}
