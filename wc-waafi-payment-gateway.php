<?php
/**
 * Plugin Name:     WC Waafi Payment Gateway
 * Plugin URI:      https://codeware.io
 * Description:    	
 * Author:          Codeware Team
 * Author URI:      https://codeware.io
 * Text Domain:     wc-waafi-payment-gateway
 * Requires PHP: 5.4
 * Requires at least: 5.0
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         CODEWARE
 */

use WCWPG\Plugin;

// defined required constants
define( 'WCWPG_FILE', __FILE__ );
define( 'WCWPG_URL', plugins_url( '', __FILE__ ) );
define( 'WCWPG_TEXT_DOMAIN', 'wc-waafi-payment-gateway' );
define( 'WCWPG_VERSION', '1.0.0');

require_once __DIR__ . '/vendor/autoload.php';

//WCWPG\api_call();


// init plugin
Plugin::init();
