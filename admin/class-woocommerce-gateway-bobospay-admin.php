<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://bobospay.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/admin
 * @author     Bobospay <support@bobospay.com>
 */
class Woocommerce_Gateway_Bobospay_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private $db_table_name = 'woocommerce_gateway_bobospay_orders_transactions';

	private $db_version_key = 'woocommerce_gateway_bobospay_db_version';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Gateway_Bobospay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Gateway_Bobospay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-gateway-bobospay-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Gateway_Bobospay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Gateway_Bobospay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

//		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-gateway-bobospay-admin.js', array( 'jquery' ), $this->version, false );

	}

	private function install() {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->db_table_name;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            bobospay_orders_transactions_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_id int(11) NOT NULL,
            order_id int(11) NOT NULL,
            hash varchar(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (bobospay_orders_transactions_id),
            KEY order_id (order_id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		add_option( $this->db_version_key, $this->version );
	}

	public function check_db() {
		if ( get_site_option( $this->db_version_key ) != $this->version ) {
			$this->install();
		}
	}

	public function show_bootstrap_warning() {
		$dependencies_message = get_option( 'woocommerce_gateway_bobospay_deps_warning_message', '' );

		if ( ! empty( $dependencies_message ) && 'yes' !== get_option( 'woocommerce_gateway_bobospay_deps_warning_message_dismissed', 'no' ) ) {

			include plugin_dir_path( __FILE__ ) . 'admin/partials/woocommerce-gateway-bobospay-admin-display.php';

			wp_enqueue_script( 'woocommerce-gateway-bobospay-dismiss-deps-warning', plugin_dir_url( __FILE__ ) . 'js/woocommerce-gateway-bobospay-admin-warning.js', array( 'jquery' ), null, true );

			wp_localize_script( 'woocommerce-gateway-bobospay-dismiss-deps-warning', 'bobospayAjax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => esc_js( wp_create_nonce( 'woocommerce_gateway_bobospay_dismiss_notice' ) )
			) );
		}
	}

	public function load_gateway() {
		/**
		 * The class responsible for defining all actions for woocommerce payment.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-gateway-bobospay-checkout.php';
	}

	/**
	 * @throws Exception
	 */
	public function check_dependencies() {
		try {
			if ( ! class_exists( 'WooCommerce' ) ) {
				throw new Exception( __( 'Bobospay Gateway for WooCommerce requires WooCommerce to be activated', 'woocommerce-gateway-bobospay' ) );
			}

			if ( version_compare( WooCommerce::instance()->version, '2.5', '<' ) ) {
				throw new Exception( __( 'Bobospay Gateway for WooCommerce requires WooCommerce version 2.5 or greater', 'woocommerce-gateway-bobospay' ) );
			}

			if ( ! function_exists( 'curl_init' ) ) {
				throw new Exception( __( 'Bobospay Gateway for WooCommerce requires cURL to be installed on your server', 'woocommerce-gateway-bobospay' ) );
			}

			$this->load_gateway();

			add_filter('woocommerce_payment_gateways', array( $this, 'add_bobospay_gateway_class' ));
			delete_option( 'woocommerce_gateway_bobospay_deps_warning_message' );
		} catch ( Exception $e ) {
			update_option( 'woocommerce_gateway_bobospay_deps_warning_message', $e->getMessage() );

			add_action( 'admin_notices', array( $this, 'show_bootstrap_warning' ) );
		}

	}

	public function add_bobospay_gateway_class( $gateways ) {
		$gateways[] = 'Woocommerce_Gateway_Bobospay_Checkout';

		return $gateways;
	}

	/**
	 * Add relevant links to plugins page.
	 *
	 * @param array $links Plugin action links
	 *
	 * @return array Plugin action links
	 * @since 1.2.0
	 *
	 */
	public function plugin_action_links( $links ): array {
		$plugin_links = array();

		if ( function_exists( 'WC' ) ) {
			$setting_url    = $this->get_admin_setting_link();
			$plugin_links[] = '<a href="' . esc_url( $setting_url ) . '">' . esc_html__( 'Settings', 'woocommerce-gateway-bobospay' ) . '</a>';
		}

		$plugin_links[] = '<a href="https://docs.bobospay.com/plugins/woocommerce" target="_blank">' . esc_html__( 'Docs', 'woocommerce-gateway-bobospay' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}


	/**
	 * Link to settings screen.
	 */
	public function get_admin_setting_link(): ?string {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . 'woocommerce-gateway-bobospay' );
	}

	public function ajax_dismiss_notice() {
		if ( empty( $_POST['dismiss_action'] ) ) {
			return;
		}

		check_ajax_referer( 'woocommerce_gateway_bobospay_dismiss_notice', 'nonce' );
		if ( $_POST['dismiss_action'] == 'woocommerce_gateway_bobospay_dismiss_deps_warning_message' ) {
			update_option( 'woocommerce_gateway_bobospay_deps_warning_message_dismissed', 'yes' );
		}
		wp_die();
	}

	public static function woocommerce_gateway_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/blocks/class-woocommerce-gateway-bobospay-block.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new Woocommerce_Gateway_Bobospay_Block() );
				}
			);
		}
	}

}
