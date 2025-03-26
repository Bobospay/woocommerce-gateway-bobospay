<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://bobospay.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/includes
 */

/**
 * Bobospay WC Payment Gateway class
 *
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Gateway_Bobospay
 * @subpackage Woocommerce_Gateway_Bobospay/includes
 * @author     Bobospay <support@bobospay.com>
 */
class Woocommerce_Gateway_Bobospay_Checkout extends WC_Payment_Gateway {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	private $db_table_name = 'woocommerce_gateway_bobospay_orders_transactions';

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	public $currencies = [ 'XOF', 'EUR', 'USD', 'NGN' ];

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOOCOMMERCE_GATEWAY_BOBOSPAY_VERSION' ) ) {
			$this->version = WOOCOMMERCE_GATEWAY_BOBOSPAY_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->id                 = 'woocommerce_gateway_bobospay';
		$this->plugin_name        = 'woocommerce-gateway-bobospay';
		$this->has_fields         = true;
		$this->method_title       = __( 'Bobospay', 'woocommerce-gateway-bobospay' );
		$this->order_button_text  = __( 'Continue to payment', 'woocommerce-gateway-bobospay' );
		$this->method_description = __( 'Bobospay Payment Gateway Plug-in for WooCommerce', 'woocommerce-gateway-bobospay' );
		$this->supports           = [ "products" ];

		$this->load_sdk();

		$this->init_form_fields();

		$this->init_settings();

//		if ( empty( $this->get_settings( 'icon_url' ) ) ) {
//			$this->update_option( 'icon_url', plugins_url( '../assets/img/bobospay.png', __FILE__ ) );
//		}

		$current_path     = plugins_url( '', __FILE__ );
		$plugin_root_path = str_replace( "/includes", "", $current_path );

		$this->update_option( 'icon_url', $plugin_root_path . '/assets/img/bobospay-woo.svg' );

		$this->set_icon();

		$this->setup_credentials();

		// Lets check for SSL
		add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );
		add_action( 'admin_notices', array( $this, 'check_currency' ) );

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}

		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_order_status' ) );
	}

	/**
	 * Enqueues admin scripts.
	 */
	public function admin_scripts() {
		// Image upload.
		wp_enqueue_media();

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../admin/js/woocommerce-gateway-bobospay-admin.js', array( 'jquery' ), $this->version, false );
	}

	private function load_sdk() {
		if ( ! class_exists( 'Bobospay\Bobospay' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
		}
	}

	private function setup_credentials() {
		$is_test_mode = $this->get_settings( 'testmode' ) == 'yes';
		$credentials  = $this->get_credentials( $is_test_mode );

		\Bobospay\Bobospay::setClientId( $credentials['client_id'] );
		\Bobospay\Bobospay::setClientSecret( $credentials['client_secret'] );
		\Bobospay\Bobospay::setEnvironment( $is_test_mode ? 'sandbox' : 'live' );
	}

	private function get_credentials( $is_test_mode = false ): array {
		$mode = $is_test_mode ? 'test' : 'live';

		return [
			"client_id"     => $this->get_settings( 'bobospay_' . $mode . '_client_id' ),
			"client_secret" => $this->get_settings( 'bobospay_' . $mode . '_client_secret' )
		];
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-bobospay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bobospay', 'woocommerce-gateway-bobospay' ),
				'default' => 'no',
			),
			'title'                       => array(
				'title'       => __( 'Title', 'woocommerce-gateway-bobospay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-bobospay' ),
				'default'     => __( 'Mobile Money - Credit cards (Bobospay)', 'woocommerce-gateway-bobospay' ),
				'desc_tip'    => true,
			),
			'description'                 => array(
				'title'       => __( 'Description', 'woocommerce-gateway-bobospay' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-bobospay' ),
				'default'     => __( "Pay via Bobospay; you can pay with your credit card or Mobile Money", 'woocommerce-gateway-bobospay' ),
			),
//			'icon_url'                    => array(
//				'title'       => __( 'Logo Image (190×60)', 'woocommerce-gateway-bobospay' ),
//				'type'        => 'image',
//				'description' => __( 'If you want Bobospay to co-brand the checkout page with your logo, enter the URL of your logo image here.<br/>The image must be no larger than 190x60, GIF, PNG, or JPG format, and should be served over HTTPS.', 'woocommerce-gateway-bobospay' ),
//				'default'     => '',
//				'desc_tip'    => true,
//				'placeholder' => __( 'Optional', 'woocommerce-gateway-bobospay' ),
//			),
			'testmode'                    => array(
				'title'       => __( 'Bobospay sandbox', 'woocommerce-gateway-bobospay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Bobospay sandbox', 'woocommerce-gateway-bobospay' ),
				'default'     => 'no',
				/* translators: %s is the URL to the Bobospay developer account signup page */
				'description' => sprintf( __( 'Bobospay sandbox can be used to test payments. Sign up for a <a target="_blank" href="%s">developer account</a>.', 'woocommerce-gateway-bobospay' ), 'https://sandbox.bobospay.com/' ),
			),
			'bobospay_test_client_id'     => array(
				'title'       => __( 'Test Client Id', 'woocommerce-gateway-bobospay' ),
				'type'        => 'text',
				'description' => __( 'This is the Test client id found in API Keys in Account Dashboard.', 'woocommerce-gateway-bobospay' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Bobospay Test Client Id', 'woocommerce-gateway-bobospay' )
			),
			'bobospay_live_client_id'     => array(
				'title'       => __( 'Live Client Id', 'woocommerce-gateway-bobospay' ),
				'type'        => 'text',
				'description' => __( 'This is the Live Client id found in API Keys in Account Dashboard.', 'woocommerce-gateway-bobospay' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Bobospay Live Client Id', 'woocommerce-gateway-bobospay' )
			),
			'bobospay_test_client_secret' => array(
				'title'       => __( 'Test Client Secret', 'woocommerce-gateway-bobospay' ),
				'type'        => 'password',
				'description' => __( 'This is the Test Client Secret found in API Keys in Account Dashboard.', 'woocommerce-gateway-bobospay' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Bobospay Test Client Secret', 'woocommerce-gateway-bobospay' )
			),
			'bobospay_live_client_secret' => array(
				'title'       => __( 'Live Client Secret', 'woocommerce-gateway-bobospay' ),
				'type'        => 'password',
				'description' => __( 'This is the Live Client Secret found in API Keys in Account Dashboard.', 'woocommerce-gateway-bobospay' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Bobospay Live Client Secret', 'woocommerce-gateway-bobospay' )
			),
//			'checkoutmodale'        => array(
//				'title'       => __( 'Payment modal', 'woocommerce-gateway-bobospay' ),
//				'type'        => 'checkbox',
//				'label'       => __( 'Enable payment modal', 'woocommerce-gateway-bobospay' ),
//				'default'     => 'no',
//				'description' => sprintf( __( 'If enabled, a payment modal will open instead of redirecting the user. Warning! This operation needs you to connect your website to Bobospay Checkout. <a target="_blank" href="%s">Learn more</a>', 'woocommerce-gateway-bobospay' ), 'https://docs.Bobospay.com/paiements/checkout' ),
//			)
		);
	}

	public function do_ssl_check() {
		if ( $this->enabled == "yes" ) {
			if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				/* translators: %s is the payment method title, %s is the URL to the WooCommerce settings page */
				echo "<div class=\"error\"><p>" . sprintf( __( '<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', 'woocommerce-gateway-bobospay' ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=advanced' ) ) . "</p></div>";
			}
		}
	}

	/**
	 * To make sure that the currency used one the store
	 * is the one we actually support
	 */
	public function check_currency() {
		$currency = get_woocommerce_currency();

		if ( ! $this->isValideCurrency( $currency ) ) {
			/* translators: %s is the payment method title, %s is the URL to the WooCommerce general settings page */
			echo "<div class=\"error\"><p>" . sprintf( __( '<strong>%s</strong> does not support the currency you are currently using. Please set your shop\'s currency to one of the supported currencies: XOF (FCFA), USD, EUR, or NGN. You can do this <a href="%s">here.</a>', 'woocommerce-gateway-bobospay' ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=general' ) ), "</p></div>";
		}
	}

	/**
	 * Verify if provided currency is supported by Bobospay
	 *
	 * @param string $currency
	 *
	 * @return bool
	 */
	private function isValideCurrency( string $currency ): bool {
		return in_array( $currency, $this->currencies );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Set Woocommerce_Gateway_Bobospay icon variable
	 */
	private function set_icon() {
		if ( filter_var( $this->get_settings( 'icon_url' ), FILTER_VALIDATE_URL ) !== false ) {
			$url = $this->get_settings( 'icon_url' );
		} else {
			$url = wp_get_attachment_url( $this->get_settings( 'icon_url' ) );
		}

		$this->icon = $this->append_url_version( $url );
	}

	/**
	 * Append version to a specific url
	 */
	private function append_url_version( $url ): string {
		$url = trim( $url );
		if ( strpos( $url, '?' ) === false ) {
			$url .= '?';
		} else {
			$url .= '&';
		}

		return $url . 'v=' . $this->version;
	}

	/**
	 * Check Order status on callback
	 */
	public function check_order_status() {
		global $woocommerce;

		$order_id = 0;
//		$transaction_id = 0;
		$token = null;


		$order_id            = (int) filter_input( INPUT_GET, 'wgbp_order_id', FILTER_SANITIZE_NUMBER_INT );
		$encoded_transaction = filter_input( INPUT_GET, 'transaction', FILTER_SANITIZE_STRING );
		$r_transaction       = $this->decode_transaction( $encoded_transaction );
		$token               = (string) filter_input( INPUT_GET, 'wgbp_token', FILTER_SANITIZE_STRING );


		$order             = wc_get_order( $order_id );
		$order_transaction = $this->getOrderTransaction( $order_id, $r_transaction->id );
		$url               = wc_get_checkout_url();

		if ( $order && $order_transaction ) {
			try {
				$transaction = \Bobospay\Transaction::retrieve( $r_transaction->id );
				$hash        = md5( $order_id . ( (float) $transaction->amount ) . $order->get_currency() . $token );

				if ( $hash && $hash === $order_transaction->hash ) {
					$this->updateOrderStatus( $order, $transaction->status );

					if ( $transaction->status == 'Success' ) {
						$woocommerce->cart->empty_cart();
						$url = $this->get_return_url( $order );
					}

					return wp_redirect( $url );
				}
			} catch ( \Exception $e ) {
				$this->displayErrors( $e );
			}
		}

		$this->updateOrderStatus( $order );
		wp_redirect( $url );
	}

	function decode_transaction( $base_64_string ) {
		return json_decode( base64_decode( $base_64_string ) );
	}

	/**
	 * Store each order with its transaction id
	 */
	public function addOrderTransaction( $order_id, $transaction_id, $hash ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->db_table_name;

		$wpdb->insert(
			$table_name,
			array(
				'transaction_id' => $transaction_id,
				'order_id'       => $order_id,
				'hash'           => $hash,
				'created_at'     => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Retrieve order with transaction info from database
	 */
	public function getOrderTransaction( $order_id, $transaction_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->db_table_name;
		$info       = $wpdb->get_results(
			"SELECT * FROM `" . $table_name . "` " .
			"WHERE `order_id` = '" . (int) $order_id . "' " .
			"AND `transaction_id` = '" . (int) $transaction_id . "' LIMIT 1"
		);

		if ( isset( $info[0] ) ) {
			return $info[0];
		}

		return null;
	}

	/**
	 * We're processing the payments here
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order     = wc_get_order( $order_id );
		$amount    = $order->get_total();
		$firstname = sanitize_text_field( $order->get_billing_first_name() );
		$lastname  = sanitize_text_field( $order->get_billing_last_name() );
		$email     = sanitize_email( $order->get_billing_email() );

		$token = md5( uniqid() );
		$hash  = md5( $order_id . $amount . $order->get_currency() . $token );

		$callback_url = home_url( '/' ) . 'wc-api/' . get_class( $this ) . '/?wgbp_order_id=' . $order_id . '&wgbp_token=' . $token;
		$description  = 'Commande ' . $order->get_id();

		foreach ( $order->get_items() as $item ) {
			$description .= ', Article: ' . $item->get_name();
			break; // Use juste first item name
		}

		if ( ! $this->isValideCurrency( $order->get_currency() ) ) {
			/* translators: %s is the payment method title */
			wc_add_notice( sprintf( __( "%s currently supports only XOF, USD, EUR, and NGN as currencies. Please select one of these or contact the store manager.", 'woocommerce-gateway-bobospay' ), $this->method_title ), 'error' );
		}

		try {
			$transaction = \Bobospay\Transaction::create( array(
				'note'         => $description,
				'amount'       => $amount,
				'currency'     => $order->get_currency(),
				'callback_url' => $callback_url,
				"custom_data"  => [
					"woo_order_id" => $order_id
				],
				'customer'     => [
					'firstname' => $firstname,
					'lastname'  => $lastname,
					'email'     => $email
				]
			) );

			$this->addOrderTransaction( $order_id, $transaction->id, $hash );

			return [
				'result'   => 'success',
				'redirect' => $this->getRedirectUrl( $transaction )
			];
		} catch ( \Exception $e ) {
			$this->displayErrors( $e );
		}
	}

	/**
	 * Display payment request errors
	 *
	 * @param \Exception $e
	 */
	private function displayErrors( \Exception $e ) {
		wc_add_notice( __( 'Payment error: ' . $e->getMessage(), 'woocommerce-gateway-bobospay' ), 'error' );

		if ( $e instanceof \Bobospay\Exception\ApiConnection && $e->getErrors() ) {
			foreach ( $e->getErrors() as $key => $errors ) {
				foreach ( $errors as $error ) {
					wc_add_notice( __( $key . ' ' . $error, 'woocommerce-gateway-bobospay' ), 'error' );
				}
			}
		}
	}

	/**
	 * Update order status
	 *
	 * @param $order WC_Order
	 * @param $transaction_status Bobospay\Transaction transaction status
	 */
	private function updateOrderStatus( $order, $transaction_status = null ) {
		switch ( $transaction_status ) {
			case "Pending":
				$order->update_status( 'processing' );
				wc_add_notice( __( 'Transaction is pending', 'woocommerce-gateway-bobospay' ), 'success' );
				$order->add_order_note( __( 'Hey, the order is processing. Thanks!', 'woocommerce-gateway-bobospay' ), true );
				break;
			case 'Success':
				$order->update_status( 'completed' );
				wc_add_notice( __( 'Transaction completed successfully', 'woocommerce-gateway-bobospay' ), 'success' );
				$order->add_order_note( __( 'Hey, the order has been completed. Thanks!', 'woocommerce-gateway-bobospay' ), true );
				break;
			case 'Blocked':
				$order->update_status( 'cancelled', 'Error:' );
				$order->add_order_note( __( 'Hey, the order has been cancelled. Try again!', 'woocommerce-gateway-bobospay' ), true );
				wc_add_notice( __( 'Transaction has been cancelled: Try again!', 'woocommerce-gateway-bobospay' ), 'error' );
				break;
			default:
				$order->add_order_note( __( 'Hey, the order payment failed. Try again!', 'woocommerce-gateway-bobospay' ), true );
				wc_add_notice( __( 'Transaction failed: Try again!', 'woocommerce-gateway-bobospay' ), 'error' );
				break;
		}
	}


	/**
	 * Return the redirect uri according to settings
	 *
	 * @param $transaction Bobospay\Transaction transaction object
	 *
	 * @return string
	 * @throws \Bobospay\Exception\ApiConnection
	 * @throws \Bobospay\Exception\InvalidRequest
	 */
	private function getRedirectUrl( $transaction ) {
		return $transaction->generateToken()->url;
	}

	private function get_settings( $key ) {
		return $this->settings[ $key ] ?? null;
	}

}
