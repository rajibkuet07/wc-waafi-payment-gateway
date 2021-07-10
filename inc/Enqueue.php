<?php 

namespace WCWPG;

class Enqueue{
    public static function init(){
        self::register();
        self::localize();
    }

    public static function register() {}

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
