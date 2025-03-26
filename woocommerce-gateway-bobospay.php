<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bobospay.com/
 * @since             1.0.0
 * @package           Woocommerce_Gateway_Bobospay
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Bobospay Gateway
 * Plugin URI:        https://wordpress.org/plugins/woocommerce-gateway-bobospay/
 * Description:       Take credit card and mobile money payments on your store using Bobospay.
 * Version:           1.0.0
 * Author:            Bobospay
 * Author URI:        https://bobospay.com/
 * Requires at least: 4.4
 * Tested up to: 6.6.2
 * WC requires at least: 2.6
 * WC tested up to: 9.3.3
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-gateway-bobospay
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_GATEWAY_BOBOSPAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-gateway-bobospay-activator.php
 */
function activate_woocommerce_gateway_bobospay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-gateway-bobospay-activator.php';
	Woocommerce_Gateway_Bobospay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-gateway-bobospay-deactivator.php
 */
function deactivate_woocommerce_gateway_bobospay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-gateway-bobospay-deactivator.php';
	Woocommerce_Gateway_Bobospay_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_gateway_bobospay' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_gateway_bobospay' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-gateway-bobospay.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_gateway_bobospay() {

	$plugin = new Woocommerce_Gateway_Bobospay();
	$plugin->run();

}
run_woocommerce_gateway_bobospay();
