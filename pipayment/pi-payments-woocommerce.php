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

function load_pi_sdk() {
    ?>
        <script src="https://sdk.minepi.com/pi-sdk.js"></script>
		<style>
			#use-pi-browser-modal {
				display: none;
				position: fixed;
				background: grey;
				width: 100vw;
				height: 100vh;
				top: 0;
				z-index: 9999;
				justify-content: center;
				align-items: center;
			}

			#use-pi-browser-modal.show {
				display: flex !important;
			}
		</style>
		<script>
			const Pi = window.Pi
			Pi.init({ version: "2.0" })
			const piApiBase = "<?php echo get_site_url() ?>/wp-json/pipayments/v1";

			jQuery(document).ready(function($){
				const userAgent = navigator.userAgent;
				const isAjax = false;
				<?php 
				
					if(
						isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
						strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0
					){
						//Set our $isAjaxRequest to true.
						?> isAjax = <?php echo true;
					}
				?>
				
				if (!userAgent.includes('PiBrowser') && !isAjax) {
					$('#use-pi-browser-modal').addClass('show');
				}
			})
			
		</script>
		<script src="/wp-content/plugins/pipayment/includes/pi.js"></script>
    <?php
}

add_action('wp_head', 'load_pi_sdk');

function pi_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-pi.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.pipayments-rest-api.php';
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
			$currency_symbol = '��'; 
		break;
	}
	return $currency_symbol;
}
