<?php 

namespace WCWPG;

class Enqueue{
    public static function init(){
        self::register();
        self::localize();
    }

    public static function register() {
			wp_register_style(
				'wc-waafi-payment-gateway',
				WCWPG_URL . '/assets/css/wc-waafi-payment-gateway.css',
				[],
				'1.0.0',
				'all'
			);
    }

    public static function localize() {}

    public static function enqueue( $handles, $type = 'script' ) {
			if ( $type === 'script' ) {
				foreach ( $handles as $h ) {
					wp_enqueue_script( $h );
				}
				return;
			}
	
			// enqueue styles
			foreach ( $handles as $h ) {
				wp_enqueue_style( $h );
			}
		}
}
