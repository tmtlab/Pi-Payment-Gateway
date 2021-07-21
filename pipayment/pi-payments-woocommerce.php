<?php
/**
 * Plugin Name: Pi Payments Gateway
 * Plugin URI: https://pitogo.app
 * Author: Carl Chang
 * Author URI: https://pitogo.app
 * Description: Local Payments Gateway for mobile.
 * Version: 0.1.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: pi-payments-woo
 * 
 * Class WC_Gateway_Pitogo file.
 *
 * @package WooCommerce\Pi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'pi_payment_init', 11 );
add_filter( 'woocommerce_currencies', 'pitogo_add_pi_currencies' );
add_filter( 'woocommerce_currency_symbol', 'pitogo_add_pi_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_pi_payment_gateway');


function pi_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-pi.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/pi-order-statuses.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/pi-checkout-description-fields.php';		
	}
}

function add_to_woo_pi_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Pi';
    return $gateways;
}

function pitogo_add_pi_currencies( $currencies ) {
	$currencies['PI'] = __( 'Pi Network', 'pi-payments-woo' );
	return $currencies;
}

function pitogo_add_pi_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'PI': 
			$currency_symbol = '𝝿'; 
		break;
	}
	return $currency_symbol;
}
