<?php

/**
 * Pi Mobile Payments Gateway.
 *
 * Provides a Pi Mobile Payments Payment Gateway.
 *
 * @class       WC_Gateway_Pi
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 */
class WC_Gateway_Pi extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->api_key            = $this->get_option( 'api_key' );		
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );		

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'pi';
		$this->icon               = apply_filters( 'woocommerce_pi_icon', plugins_url('../assets/icon.png', __FILE__ ) );
		$this->method_title       = __( 'Pi Mobile Payments', 'pi-payments-woo' );
		$this->api_key            = __( 'Add API Key', 'pi-payments-woo' );		
		$this->method_description = __( 'Have your customers pay with Pi Mobile Payments.', 'pi-payments-woo' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'pi-payments-woo' ),
				'label'       => __( 'Enable Pi Mobile Payments', 'pi-payments-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
			'title'              => array(
				'title'       => __( 'Title', 'pi-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Pi Mobile Payment method description that the customer will see on your checkout.', 'pi-payments-woo' ),
				'default'     => __( 'Pi Mobile Payments', 'pi-payments-woo' ),
				'desc_tip'    => true,
			),
			'api_key'             => array(
				'title'       => __( 'API Key', 'pi-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Add your API key', 'pi-payments-woo' ),
				'desc_tip'    => true,
			),
			
			'description'        => array(
				'title'       => __( 'Description', 'pi-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Pi Mobile Payment method description that the customer will see on your website.', 'pi-payments-woo' ),
				'default'     => __( 'Pi Mobile Payments before delivery.', 'pi-payments-woo' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'pi-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'pi-payments-woo' ),
				'default'     => __( 'Pi Mobile Payments before delivery.', 'pi-payments-woo' ),
				'desc_tip'    => true,
			),
		);
	}


	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'pi' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		return false;
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			$this->pi_payment_processing( $order );
		}
	}

	private function pi_payment_processing( $order ) {

		$total = intval( $order->get_total() );
		var_dump($total);		
		    
		?>
		    <div class="container form-group" style="text-align:center">
		    <input id="pi_donate" name="pi_donate" class="form-text" type="number" min="0" step="0.01" lang="en-US"/>
		    <input id="button_click" name="button_click" value="Donate" type="button" class="btn btn-primary"/>
		    
		    </div>
		    
		    <script type="text/javascript">
		    jQuery(document).ready(function( ) {
		        const Pi = window.Pi;
		        Pi.init({ version: "2.0" });
		        
		        //alert(PiNetworkClient);
		        
		        async function auth() {
		            try {
		                // Identify the user with their username / unique network-wide ID, and get permission to request payments from them.
		                const scopes = ['username', 'payments'];
		                function onIncompletePaymentFound(payment) {
		                    
		                    var data = {
		                        'action': 'complete',
		                        'paymentId': payment.identifier,
		                        'txid': payment.transaction.txid,
		                        'app_client': 'pitogo'
		                    };
		                    return $.post( "https://pitogo.app/server1.php", data).done(function(data) {
		                        $("#button_click").prop( "disabled", false );
		                    }).fail(function() {
		                        $("#button_click").prop( "disabled", false );
		                    });
		                }; // Read more about this in the SDK reference
		                
		                Pi.authenticate(scopes, onIncompletePaymentFound).then(function(auth) {
		                    $( "#button_click" ).click(function() {
		                        if(parseFloat($("#pi_donate").val()) > 0)
		                        {
		                            $("#button_click").prop( "disabled", true );
		                            /*setTimeout(function ()
		                             {
		                             $("#button_click").prop( "disabled", false );
		                             }, 10000);*/
		                            transfer();
		                        }
		                        //alert("Click");
		                    });
		                        //alert('Hello ' + auth.user.username);
		                }).catch(function(error) {
		                    console.error(error);
		                });
		            } catch (err) {
		                alert(err);
		                // Not able to fetch the user
		            }
		        }
		        
		        async function transfer() {
		            try {
		                const payment = Pi.createPayment({
		                    // Amount of £k to be paid:
		                    amount: parseFloat($("#pi_donate").val()),
		                    // An explanation of the payment - will be shown to the user:
		                    memo: "Demo transfer request", // e.g: "Digital kitten #1234",
		                    // An arbitrary developer-provided metadata object - for your own usage:
		                    metadata: { paymentType: "donation" /* ... */ }, // e.g: { kittenId: 1234 }
		                }, {
		                    // Callbacks you need to implement - read more about those in the detailed docs linked below:
		                    onReadyForServerApproval: function(paymentId) {
		                        var data = {
		                            'action': 'approve',
		                            'paymentId': paymentId,
		                            'txid': '',
		                            'app_client': 'pitogo'
		                        };
		                        return $.post( "https://pitogo.app/server1.php", data).done(function(data) {
		                            $("#button_click").prop( "disabled", false );
		                        }).fail(function() {
		                            $("#button_click").prop( "disabled", false );
		                        });
		                    },
		                    onReadyForServerCompletion: function(paymentId, txid) {
		                        var data = {
		                            'action': 'complete',
		                            'paymentId': paymentId,
		                            "txid": txid,
		                            'app_client': 'pitogo'
		                        };
		                        return $.post( "https://pitogo.app/server1.php", data).done(function(data) {
		                            $("#button_click").prop( "disabled", false );
		                        }).fail(function() {
		                            $("#button_click").prop( "disabled", false );
		                        });
		                    },
		                    onCancel: function(paymentId) { $("#button_click").prop( "disabled", false ); /* ... */ },
		                    onError: function(error, payment) { $("#button_click").prop( "disabled", false ); /* ... */ },
		                    });
		            } catch(err) {
		                $("#button_click").prop( "disabled", false );
		                alert(err);
		                // Technical problem (eg network failure). Please try again
		            }
		        }
		        
		        auth();
		        
		    });
		        </script>
<?php
		
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$order->update_status( apply_filters( 'woocommerce_pi_process_payment_order_status', $order->has_downloadable_item() ? 'wc-invoiced' : 'processing', $order ), __( 'Payments pending.', 'pi-payments-woo' ) );
		}

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			var_dump($response_body['message']);
			if ( 'Thank you! Your payment was successful' === $response_body['message'] ) {
				$order->payment_complete();

				// Remove cart.
				WC()->cart->empty_cart();

				// Return thankyou redirect.
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		}
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Change payment complete order status to completed for pi orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && 'pi' === $order->get_payment_method() ) {
			$status = 'completed';
		}
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}