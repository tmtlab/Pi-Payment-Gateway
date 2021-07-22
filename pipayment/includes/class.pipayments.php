<?php

class PiPayments {
    const API_HOST = 'api.minepi.com/v2/payments';
	private static $initiated = false;
    
    public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
		
		add_action( 'wp_enqueue_scripts', array( 'PiPayments', 'enqueue_scripts' ) ); 		
	}

	
    public static function get_api_key() {
		return get_option('pi_api_key');
		//return apply_filters( 'pi_payments_woo_get_api_key', get_option('pi_api_key') );
	}


    /**
	 * Make a POST request to the Pi Network API.
	 *
	 * @param string $request The body of the request.
	 * @param string $path The path for the request.
	 * @param string $ip The specific IP address to hit.
	 * @return array A two-member array consisting of the headers and the response body, both empty in the case of a failure.
	 */
	public static function http_post( $request, $path ) {

	    $pi_payments_woo_ua = sprintf( 'WordPress/%s | pi_payments_woo/%s', $GLOBALS['wp_version'], constant( 'PI_PAYMENTS_WOO_VERSION' ) );
	    $pi_payments_woo_ua = apply_filters( 'pipayments_ua', $pi_payments_woo_ua );

		$content_length = strlen( $request );

		$api_key   = self::get_api_key();
		$host      = self::API_HOST;

		if ( !empty( $api_key ) )
			$host = $api_key.'.'.$host;

		$http_host = $host;
		// // use a specific IP if provided
		// // needed by Akismet_Admin::check_server_connectivity()
		// if ( $ip && long2ip( ip2long( $ip ) ) ) {
		// 	$http_host = $ip;
		// }

		$http_args = array(
			'body' => $request,
			'headers' => array(
				'Authorization' => 'Key ' . $api_key,
				'Host' => $host,
				'User-Agent' => $pipayments_ua,
			),
			'httpversion' => '1.0',
			'timeout' => 15
		);

		$pi_payments_woo_url = $http_pi_payments_woo_url = "http://{$http_host}/1.1/{$path}";

		/**
		 * Try SSL first; if that fails, try without it and don't try it again for a while.
		 */

		$ssl = $ssl_failed = false;

		// Check if SSL requests were disabled fewer than X hours ago.
		$ssl_disabled = get_option( 'pi_payments_woo_ssl_disabled' );

		if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
			$ssl_disabled = false;
			delete_option( 'pi_payments_woo_ssl_disabled' );
		}
		else if ( $ssl_disabled ) {
			do_action( 'pi_payments_woo_ssl_disabled' );
		}

		if ( ! $ssl_disabled && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
			$pipayments_url = set_url_scheme( $pipayments_url, 'https' );

			do_action( 'pi_payments_woo_https_request_pre' );
		}

		$response = wp_remote_post( $pipayments_url, $http_args );

		PiPayments::log( compact( 'pi_payments_woo_url', 'http_args', 'response' ) );

		if ( $ssl && is_wp_error( $response ) ) {
			do_action( 'pi_payments_woo_https_request_failure', $response );

			// Intermittent connection problems may cause the first HTTPS
			// request to fail and subsequent HTTP requests to succeed randomly.
			// Retry the HTTPS request once before disabling SSL for a time.
			$response = wp_remote_post( $pi_payments_woo_url, $http_args );
			
			PiPayments::log( compact( 'pi_payments_woo_url', 'http_args', 'response' ) );

			if ( is_wp_error( $response ) ) {
				$ssl_failed = true;

				do_action( 'pi_payments_woo_https_request_failure', $response );

				do_action( 'pi_payments_woo_http_request_pre' );

				// Try the request again without SSL.
				$response = wp_remote_post( $http_pi_payments_woo_url, $http_args );

				PiPayments::log( compact( 'http_pi_payments_woo_url', 'http_args', 'response' ) );
			}
		}

		if ( is_wp_error( $response ) ) {
			do_action( 'pi_payments_woo_request_failure', $response );

			return array( '', '' );
		}

		if ( $ssl_failed ) {
			// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
			update_option( 'pi_payments_woo_ssl_disabled', time() );
			
			do_action( 'pi_payments_woo_https_disabled' );
		}
		
		$simplified_response = array( $response['headers'], $response['body'] );
		
		self::update_alert( $simplified_response );

		return $simplified_response;
	}

	public static function view( $name, array $args = array() ) {
		$args = apply_filters( 'pi_payments_woo_view_arguments', $args, $name );
		
		foreach ( $args AS $key => $val ) {
			$$key = $val;
		}
		
		load_plugin_textdomain( 'pi_payments_woo' );

		$file = PI_PAYMENTS_WOO__PLUGIN_DIR . 'views/'. $name . '.php';

		include( $file );
	}

//	/**
//	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
//	 * @static
//	 */
//	public static function plugin_activation() {
//		//echo 'do something with activation';
//	    if ( version_compare( $GLOBALS['wp_version'], PI_PAYMENTS_WOO__MINIMUM_WP_VERSION, '<' ) ) {
//			load_plugin_textdomain( 'akismet' );
//			
//			$message = '<strong>'.sprintf(esc_html__( 'Akismet %s requires WordPress %s or higher.' , 'akismet'), AKISMET_VERSION, AKISMET__MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the Akismet plugin</a>.', 'akismet'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://wordpress.org/extend/plugins/akismet/download/');
//
//			//Akismet::bail_on_activation( $message );
//		} else {
//			pipaymentswrite_log('activating pi payments');
//			add_option( 'Activated_pi_payments_woo', true );
//		}
//		
//	}
//
//	/**
//	 * Removes all connection options
//	 * @static
//	 */
//	public static function plugin_deactivation( ) {
//		// echo 'do something in deactivation';
//		// self::deactivate_key( self::get_api_key() );
//		
//		// // Remove any scheduled cron jobs.
//		// $akismet_cron_events = array(
//		// 	'akismet_schedule_cron_recheck',
//		// 	'akismet_scheduled_delete',
//		// );
//		
//		// foreach ( $akismet_cron_events as $akismet_cron_event ) {
//		// 	$timestamp = wp_next_scheduled( $akismet_cron_event );
//			
//		// 	if ( $timestamp ) {
//		// 		wp_unschedule_event( $timestamp, $akismet_cron_event );
//		// 	}
//		// }
//	}
//
//		/**
//	 * Log debugging info to the error log.
//	 *
//	 * Enabled when WP_DEBUG_LOG is enabled (and WP_DEBUG, since according to
//	 * core, "WP_DEBUG_DISPLAY and WP_DEBUG_LOG perform no function unless
//	 * WP_DEBUG is true), but can be disabled via the akismet_debug_log filter.
//	 *
//	 * @param mixed $akismet_debug The data to log.
//	 */
//	public static function log( $pipayments_debug ) {
//		if ( apply_filters( 'pipayments_debug_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && defined( 'PIPAYMENTS_DEBUG' ) && PIPAYMENTS_DEBUG ) ) {
//			error_log( print_r( compact( 'pipayments_debug' ), true ) );
//		}
//	}
}