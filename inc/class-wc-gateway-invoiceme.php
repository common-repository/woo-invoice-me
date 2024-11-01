<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Invoice Me Payment Gateway
 *
 * Provides a Invoice Payment Gateway, mainly for invoice purposes.
 *
 * @class 		WC_Gateway_InvoiceMeCustomer
 * @extends		WC_Payment_Gateway
 */
class WC_Gateway_InvoiceMeCustomer extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
	public function __construct() {
			$this->id                 = 'invoiceme';
			$this->icon               = apply_filters( 'woo_invoiceme_logo', plugins_url( 'images/invoiceme.png', dirname( __FILE__ ) ) );
			$this->has_fields         = true;
			$this->method_title       = __( 'Invoice Me', 'woo-invoice-me' );
			$this->method_description = __( 'Allows Invoice Me Option For Selected Customers In Checkout Page', 'woo-invoice-me' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->default_order_status  = $this->get_option( 'default_order_status' );
			$this->order_button_text  = $this->get_option( 'btn_text_paynow' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    	add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'woo-invoice-me' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable Invoice Me', 'woo-invoice-me' ),
						'default' => 'yes'
					),
					'title' => array(
						'title'       => __( 'Title', 'woo-invoice-me' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woo-invoice-me' ),
						'default'     => __( 'Invoice Me', 'woo-invoice-me' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'woo-invoice-me' ),
						'type'        => 'textarea',
						'description' => __( 'Payment method description that the customer will see on your checkout.', 'woo-invoice-me' ),
						'default'     => __( 'You are allowed to use our Manual invoice checkout.', 'woo-invoice-me' ),
						'desc_tip'    => true,
					),
					'instructions' => array(
						'title'       => __( 'Instructions', 'woo-invoice-me' ),
						'type'        => 'textarea',
						'description' => __( 'Instructions message that display on checkout confirmation page.', 'woo-invoice-me' ),
						'default'     => __( 'Thank you for staying with us.', 'woo-invoice-me' ),
						'desc_tip'    => true,
					),
					'btn_text_paynow' => array(
						'title'       => __( 'Button Text', 'woo-invoice-me' ),
						'type'        => 'text',
						'description' => __( 'Place order button text', 'woo-invoice-me' ),
						'default'     => __( 'Proceed Invoice Me', 'woo-invoice-me' ),
						'desc_tip'    => true,
					),
					'default_order_status' => array(
						'title'       => __( 'Order Status', 'woo-invoice-me' ),
						'type'        => 'select',
						'description' => __( 'Choose immediate order status at customer checkout.', 'woo-invoice-me' ),
						'default'     => 'on-hold',
						'desc_tip'    => true,
						'options'     => array(
							'on-hold'          => __( 'On Hold', 'woo-invoice-me' ),
							'processing' => __( 'Processing', 'woo-invoice-me' ),
							'completed' => __( 'Completed', 'woo-invoice-me' )
						)
					)
			);
    }

    /**
     * Output for the order received page.
     */
		public function thankyou_page() {
			if ( $this->instructions ){
	        	echo wpautop( wptexturize( $this->instructions ) );
			}
		}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	        if ( $this->instructions && ! $sent_to_admin && 'invoiceme' === $order->get_payment_method() && $order->has_status( $this->default_order_status ) ) {
							echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
					}
		}

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		update_post_meta( $order_id, '_invoice_me', 'yes' );
		update_post_meta( $order_id, '_invoice_me_status', 'pending' );

		// Mark as on-hold (we're awaiting shop manager approval)
		$order->update_status( $this->default_order_status, __( 'Awaiting Invoice Me', 'woo-invoice-me' ) );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}
}
