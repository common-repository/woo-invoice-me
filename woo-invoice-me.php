<?php
/*
Plugin Name: WooCommerce Invoice Me
Plugin URI: http://woocommerce.rmweblab.com/invoice-me-payment-gateway
Description: Extends WooCommerce Payment Gateway allow customers to purchase products with Invoice Me Checkout Option.
Author: Anas
Version: 2.0.0
Author URI: http://rmweblab.com
Text Domain: woo-invoice-me
Domain Path: /languages
WC tested up to: 3.2.3
WC requires at least: 3.2.3

Copyright: © 2017 RMWebLab.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Main InvoiceMe class which sets the gateway up for us
 */
class WC_InvoiceMeGateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_INVOICEME_VERSION', '2.0.0' );
		define( 'WC_INVOICEME_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_INVOICEME_MAIN_FILE', __FILE__ );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_order' ) );
	}

	/**
	 * Add relevant links to plugins page
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=invoiceme' ) . '">' . __( 'Settings', 'woo-invoice-me' ) . '</a>',
			'<a href="https://rmweblab.com/support">' . __( 'Support', 'woo-invoice-me' ) . '</a>',
			'<a href="https://plugins.rmweblab.com/invoice-me-payment-gateway">' . __( 'Docs', 'woo-invoice-me' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Gateway Invoice Me Admin
		require_once( 'inc/class-wc-gateway-invoiceme-admin.php' );

		// Includes
		require_once( 'inc/class-wc-gateway-invoiceme.php' );

		// Localisation
		load_plugin_textdomain( 'woo-invoice-me', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {

		if(is_user_logged_in()){
			$current_user_id = get_current_user_id();
			if(class_exists( 'WC_Gateway_InvoiceMeCustomer' ) && ((esc_attr( get_the_author_meta( 'invoice_me', $current_user_id ) ) == 'yes') || current_user_can('manage_options'))){
				$methods[] = 'WC_Gateway_InvoiceMeCustomer';
			}
		}

		return $methods;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id
	 */
	public function capture_order( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( $order->get_payment_method() == 'invoiceme' ) {

			//Sent Invoice to customer
			date_default_timezone_set("UTC");
			update_post_meta( $order_id, '_invoice_me_status', 'sent' );
			$date_time = date("Y-m-d H:i:s");
			update_post_meta( $order_id, '_invoice_me_date', $date_time );

		}
	}

	/**
	 * Cancel pre-auth on cancellation
	 *
	 * @param  int $order_id
	 */
	public function cancel_order( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( $order->get_payment_method() == 'invoiceme' ) {

			if(get_post_meta( $order_id, '_invoice_me', true )){
				//clean up invoice me meta value for this order.
				delete_post_meta($order_id, '_invoice_me_status', 'pending');
				delete_post_meta($order_id, '_invoice_me', 'yes');
			}

		}
	}
}

new WC_InvoiceMeGateway();
