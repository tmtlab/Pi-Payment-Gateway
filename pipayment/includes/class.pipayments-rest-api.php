<?php

define('PI_API_BASE', 'https://api.minepi.com/v2/payments');

class PiPayments_REST_API {
	/**
	 * Register the REST API routes.
	 */
	public static function init() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			// The REST API wasn't integrated into core until 4.4, and we support 4.0+ (for now).
			return false;
		}

		register_rest_route( 'pipayment/v2', '/approve', array(
			array(
				'methods' => ['POST'],
				'permission_callback' => array( 'PiPayment_REST_API', 'privileged_permission_callback' ),
				'callback' => array( 'PiPayment_REST_API', 'approve_payment' ),
				'params' => array(
					'paymentId' => array(
						'required' => true,
						'type' => 'string',
						'description' => __( 'Payment Id to approve', 'pipayment' ),
					)
				),
			)
		) );

		register_rest_route( 'pipayment/v2', '/complete', array(
			array(
				'methods' => ['POST'],
				'permission_callback' => array( 'PiPayments_REST_API', 'privileged_permission_callback' ),
				'callback' => array( 'PiPayment_REST_API', 'complete_payment' ),
				'params' => array(
					'paymentId' => array(
						'required' => true,
						'type' => 'string',
						'description' => __( 'Payment Id to complete', 'pipayment' ),
					),
					'txid' => array(
						'required' => true,
						'type' => 'string',
						'description' => __( 'txid of payment to complete', 'pipayment' ),
					),
				),
			)
		) );

	}

	public static function pi_request($endpoint, $body = false) {

		$args = array(
			'body'        => !$body ? array() : $body,
			'timeout'     => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
			'Authorization' => 'Key ' . get_option('api_key'),
			),
			'cookies'     => array(),
		);
	
		$url = PI_API_BASE . $endpoint;

		error_log("making request to: $url");

		$response = wp_remote_post( $url, $args );

		return $response;
	}

	public static function approve_payment( $request = null ) {
		$paymentId = $request['paymentId'];
		//$paymentId = $_POST['paymentId'];
		//send pi approve call here
		error_log('going to make call to approve pi payment');
		self::pi_request("/$paymentId/approve");
	}

	public static function complete_payment( $request = null ) {
		//send pi complete call here
		$paymentId = $request['paymentId'];
		$txid = $request['txid'];

		error_log('going to make call to complete pi payment');
		self::pi_request("/$paymentId/complete", array('txid' => $txid));
	}

	public static function privileged_permission_callback() {
		return/*  current_user_can( 'manage_options' ); */ true;
	}

	// /**
	//  * For calls that Akismet.com makes to the site to clear outdated alert codes, use the API key for authorization.
	//  */
	// public static function remote_call_permission_callback( $request ) {
	// 	// $local_key = Akismet::get_api_key();

	// 	return $local_key && ( strtolower( $request->get_param( 'key' ) ) === strtolower( $local_key ) );
	// }

}
