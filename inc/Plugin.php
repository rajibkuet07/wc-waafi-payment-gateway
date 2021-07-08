<?php 

namespace WCWPG;

class Plugin{
    public static function init() {
			register_activation_hook( WCWPG_FILE, [ 'WCWPG\Plugin', 'on_activation' ] );

			add_action( 'wp_enqueue_scripts', [ 'WCWPG\Enqueue', 'init' ] );

			add_filter( 'woocommerce_payment_gateways', [ 'WCWPG\Plugin', 'add_waafi_payment_gateway_class' ] );
    }

		public static function on_activation() {
			// Make sure that woocommerce is activated
			if ( ! in_array(
				'woocommerce/woocommerce.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
			) ) {
				exit( __( 'WooCommerce plugin is not activated. To use this you need to install and active woocommerce first!', 'wc-waafi-payment-gateway' ) );
			}
		}

		public static function add_waafi_payment_gateway_class( $methods ) {
			$methods[] = 'WCWPG\WC_Waafi_Payment_Gateway';
   		return $methods;
		}
}
