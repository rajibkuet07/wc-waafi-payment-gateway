<?php

namespace WCWPG;

class WC_Waafi_Payment_Gateway extends \WC_Payment_Gateway {
	/**
	 * Wc Api callback
	 *
	 * @var string
	 */
	public $api_callback = 'waafi-callback';

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
		
		// turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		$this->merchant = $this->testmode == 1 ? $this->sandbox_merchant_uid : $this->merchant_uid;
		$this->store    = $this->testmode == 1 ? $this->sandbox_store_id : $this->store_id;
		$this->hpp      = $this->testmode == 1 ? $this->sandbox_hpp_key : $this->hpp_key;

		// logger
		$this->log = new \WC_Logger();

		// process admin settings
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		}

		// process the api response from waafi
		add_action( 'woocommerce_api_' . $this->api_callback, [ $this, 'waafi_response' ] );

		// customer Emails
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );

		// thankyou message
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );
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
			'invoice_prefix' => [
				'title'       => __( 'Prefix for the invoice id', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric prefix for this store.', 'wc-waafi-payment-gateway' ),
				'default'     => 'wcwpg-',
			],
			'ref_prefix' => [
				'title'       => __( 'Prefix for the referrence id', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric prefix for this store.', 'wc-waafi-payment-gateway' ),
				'default'     => 'wcwpg-',
			],
			'instructions' => [
				'title'       => __( 'Instructions', 'wc-waafi-payment-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-waafi-payment-gateway'),
				'default'     => '',
			],
			'sandbox_api_info'	=> [
				'title'       => __( 'Test Mode API Details', 'wc-waafi-payment-gateway' ),
				'type'        => 'title',
				'description' => __( 'Add the API credentials to process payment on test mode.', 'wc-waafi-payment-gateway' ),
			],
			'testmode' => [
				'title'       => __( 'Test mode', 'wc-waafi-payment-gateway' ),
				'label'       => __( 'Enable Test Mode', 'wc-waafi-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode using sandbox.', 'wc-waafi-payment-gateway' ),
				'default'     => 'yes',
			],
			'payment_method' 		=> [
				'title' 			=> __( 'Payment Method', 'wc-waafi-payment-gateway' ),
				'type' 				=> 'select',
				'description' => __( 'Select the payment type for your shop.', 'wc-waafi-payment-gateway' ),
				'options' 		=> [
					'CREDIT_CARD'          => 'Credit Card',
					'MWALLET_ACCOUNT'      => 'MFS Transactions',
					'MWALLET_BANKACCOUNT'  => 'MFS Customer\'s Bank Account',
				],
				'default' => 'CREDIT_CARD',
			],
			'sandbox_merchant_uid' => [
				'title'       => __( 'Test Mode Marchant Uid', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Marchant Uid for this Waafi account.', 'wc-waafi-payment-gateway' ),
			],
			'sandbox_store_id' => [
				'title'       => __( 'Test Mode Store ID', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric unique ID for this store.', 'wc-waafi-payment-gateway' ),
			],
			'sandbox_hpp_key' => [
				'title'       => __( 'Test Mode HPP Key', 'wc-waafi-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Alphanumeric unique HPP key for this store.', 'wc-waafi-payment-gateway' ),
			],
			'live_api_info'	=> [
				'title'       => __( 'Live API Details', 'wc-waafi-payment-gateway' ),
				'type'        => 'title',
				'description' => __( 'Add the API credentials to process payment on live.', 'wc-waafi-payment-gateway' ),
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
		];
	}

	/**
	 * Handling payment and processing the order
	 * also tells WC where to redirect the use
	 *
	 * @param int order id
	 * @return array
	 */
	function process_payment( $order_id ) {
    $order = \wc_get_order( $order_id );

		// API interaction
		$api          = new API( $this );
		$api_response = $api->initiate_payment( $order_id );

		if ( $api_response->status === 'error' ) {
			if ( ! $this->is_rest_api ) {
				\wc_add_notice( $api_response->message, 'error' );
				return;
			}
		} else {
			$api_url = $api_response->url;
		}

    // Return to the checkout payment page to take payment
    return array(
			'result'   => 'success',
			'redirect' => $api_url
    );
	}

	public function get_transaction_url( $order ) {
		$sandbox    = $this->testmode ? 'sandbox' : 'api';
		$invoice_id = get_post_meta($order->get_id(), '_edahab_invoice', true);

		return "https://edahab.net/$sandbox/payment?invoiceId=" . $invoice_id;
	}

	/**
	 * Process the response from the waafipay payment gateway
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function waafi_response() {
		global $wpdb;

		if ( empty( $_REQUEST ) ) {
			wp_die( 'WaafiPay Payment Request Failure', 'WaafiPay', array( 'response' => 500 ) );
		}
		
		$this->log->add( $this->id, 'Response from WaafiPay: ' . print_r( wc_clean( $_REQUEST ), true ) );

		if ( ! isset( $_REQUEST['state'] ) || wc_clean( $_REQUEST['state'] ) == 'FAILED' ) {
			$message = isset( $_REQUEST['responseMsg'] ) ? wc_clean( $_REQUEST['responseMsg'] ) : '';
			wc_add_notice(
				sprintf( __( 'Failed to complete the payment. Error: %s', 'wc-waafi-payment-gateway'), $message ),
				'error'
			);
			wp_redirect( wc_get_checkout_url() );
			exit;
		}
		
		$order_id       = 0;
		$referrence_id  = wc_clean( $_REQUEST['referenceId'] );

		// get the order using the referrence id
		$post_meta      = $wpdb->get_row(
			$wpdb->prepare(
				"select post_id from {$wpdb->postmeta} where meta_key = '_wcwpg_reference_id' and meta_value = '%s'",
				$referrence_id
			),
			ARRAY_A
		);

		if ( is_array( $post_meta ) && isset( $post_meta['post_id'] ) && $post_meta['post_id'] ) {
			$order_id = $post_meta['post_id'];
		}

		$order = wc_get_order( $order_id );

		if ( ! $order_id || ! $order ) {
			$this->log->add( $this->id, 'Error: order not found.' . print_r( $order_id, true ) );
			wc_add_notice( __( 'Order not found in the payment response.', 'wc-waafi-payment-gateway') , 'error' );
			wp_redirect( wc_get_checkout_url() );
			exit;
		}

		if ( $_REQUEST['responseMsg'] == 'RCS_SUCCESS' && $_REQUEST['state'] == 'APPROVED' ) {
			// add some meta
			update_post_meta( $order_id, '_wcwpg_transaction_id', wc_clean( $_REQUEST['transactionId'] ) );
			update_post_meta( $order_id, '_wcwpg_card_no', wc_clean( $_REQUEST['cardNo'] ) );
			update_post_meta( $order_id, '_wcwpg_card_holder', wc_clean( $_REQUEST['cardHolder'] ) );
			update_post_meta( $order_id, '_wcwpg_card_exp_date', wc_clean( $_REQUEST['cardExpDate'] ) );
			update_post_meta( $order_id, '_wcwpg_proc_description', wc_clean( $_REQUEST['procDescription'] ) );

			// payment complete and clean the cart
			$order->payment_complete();
			WC()->cart->empty_cart();

			$order->add_order_note( sprintf( __( 'Payment was successfully processed by WaafiPay.', 'wc-waafi-payment-gateway' ) ) );
		} else {
			$order->update_status( 'failed' );
			$order->add_order_note( sprintf( __( 'Error Message: %s', 'wc-waafi-payment-gateway' ), wc_clean( $_REQUEST['responseMsg'] ) ) );
		}

		// finally redirect to the thank you page
		$redirect = $this->get_return_url( $order );
		wp_redirect( $redirect );
		exit;
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
