<?php

namespace WCWPG;

class WC_Waafi_Payment_Gateway extends \WC_Payment_Gateway {
	/**
	 * Constructor of the WC_Waafi_Payment_Gateway class
	 */
	public function __construct() {
		// required variables
		$this->id                 = 'wcwpg';
		$this->icon               = apply_filters( 'wc_waafi_payment_gateway_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'WaafiPay', 'wc-waafi-payment-gateway' );
		$this->method_description = __( 'Take payments using Waafi Pay Hosted Pay Page.', 'wc-waafi-payment-gateway' );

		// laod settings
		$this->init_form_fields();
		$this->init_settings();

		// set the settings
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->enabled      = $this->get_option( 'enabled' );
		$this->testmode     = 'yes' === $this->get_option( 'testmode' );
		$this->merchant_uid = $this->get_option( 'merchant_uid' );
		$this->store_id     = $this->get_option( 'store_id' );
		$this->hpp_key      = $this->get_option( 'hpp_key' );
		$this->instructions = $this->get_option( 'instructions' );

		// process admin settings
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		}

		// thankyou message
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );

		// customer Emails
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
	}

	/**
	 * Initialize the form fields
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled' => [
				'title'       => __( 'Enable/Disable', 'wc-waafi-payment-gateway' ),
				'label'       => __( 'Enable WaafiPay Payment', 'wc-waafi-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			],
			'title' => [
				'title'       => __( 'Title', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-waafi-payment-gateway' ),
				'default'     => __( 'WaafiPay Payment', 'wc-waafi-payment-gateway' ),
			],
			'description' => [
				'title'       => __( 'Message for Customer', 'wc-waafi-payment-gateway' ),
        'type'    		=> 'textarea',
        'default' 		=> '',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-waafi-payment-gateway' ),
			],
			'merchant_uid' => [
				'title'       => __( 'Marchant Uid', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Marchant Uid for this Waafi account.', 'wc-waafi-payment-gateway' ),
			],
			'store_id' => [
				'title'       => __( 'Store ID', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric unique ID for this store.', 'wc-waafi-payment-gateway' ),
			],
			'hpp_key' => [
				'title'       => __( 'HPP Key', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric unique HPP key for this store.', 'wc-waafi-payment-gateway' ),
			],
			'testmode' => [
				'title'       => __( 'Test mode', 'wc-waafi-payment-gateway' ),
				'label'       => __( 'Enable Test Mode', 'wc-waafi-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode using sandbox.', 'wc-waafi-payment-gateway' ),
				'default'     => 'yes',
			],
			'instructions' => [
				'title'       => __( 'Instructions', 'wc-waafi-payment-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-waafi-payment-gateway'),
				'default'     => '',
			],
		];
	}

	/**
	 * Handling payment and processing the order
	 * also tells WC where to redirect the use
	 *
	 * @param int $order_id
	 * @return array
	 */
	function process_payment( $order_id ) {
    global $woocommerce;
    $order = wc_get_order( $order_id );

    // Mark as on-hold (we're awaiting the cheque)
    $order->update_status( 'on-hold', __( 'Awaiting WaafiPay payment', 'wc-waafi-payment-gateway' ) );

		// process the api calls here
		$api_res = api_call();

		if ( $api_res->status === 'error' ) {
			//wc_add_notice( $api_res->data );
			die($api_res->data);
			return;
		}

    // Remove cart
    $woocommerce->cart->empty_cart();

    // Return thankyou redirect
    return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
    );
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
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
		if (
			$this->instructions &&
			! $sent_to_admin
			&& 'wcwpg' === $order->get_payment_method()
			&& $order->has_status( 'on-hold' )
		) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
}
