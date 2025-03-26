<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://bobospay.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/admin/partials
 *
 *
 */

if (!defined('ABSPATH')) exit;

?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->


<div class="notice notice-warning is-dismissible woocommerce-gateway-bobospay-dismiss-deps-warning-message">
    <p><strong><?php echo esc_html(get_option('woocommerce_gateway_bobospay_deps_warning_message', '')); ?></strong></p>
</div>
