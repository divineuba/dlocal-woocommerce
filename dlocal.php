<?php
/**
 * Plugin Name: DLocal Payment Gateway
 * Plugin URI: Marshall Divine 
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 1.0.1
 * Author: Proximogies
 * Author URI: https://zoethon.com
 * Author Email: divinemarshalluba@gmail.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 5.7
 * Requires PHP: 7.2
 */


 if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'dlocal_init', 0 );

 function dlocal_init(){
     if( class_exists('WC_Payment_Gateway')) {
         class WC_DlocalPay extends WC_Payment_Gateway {
             
            public function __construct() {
                $this->id = "dlocal_payment";
                $this->method_title = __( "dLocal", 'd-local' );
                $this->method_description = __( "dLocal Payment Gateway Plug-in for WooCommerce", 'd-local' );
                $this->title = __( "dLocal", 'd-local' );
                $this->icon = null;
                $this->has_fields = true;
                $this->supports_callbacks = true;
                $this->init_form_fields();
                $this->init_settings();
                
                foreach ( $this->settings as $setting_key => $value ) {
                  $this->$setting_key = $value;
                }                
                if ( is_admin() ) {
                  add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                }    
              }

              public function init_form_fields() {
                $this->form_fields = array(
                  'enabled' => array(
                    'title'    => __( 'Enable / Disable', 'd-local' ),
                    'label'    => __( 'Enable this payment gateway', 'd-local' ),
                    'type'    => 'checkbox',
                    'default'  => 'no',
                  ),
                  'title' => array(
                    'title'    => __( 'Title', 'd-local' ),
                    'type'    => 'text',
                    'desc_tip'  => __( 'Payment title of checkout process.', 'd-local' ),
                    'default'  => __( 'Credit card', 'd-local' ),
                  ),
                  'description' => array(
                    'title'    => __( 'Description', 'd-local' ),
                    'type'    => 'textarea',
                    'desc_tip'  => __( 'Payment title of checkout process.', 'd-local' ),
                    'default'  => __( 'Successfully payment through credit card.', 'd-local' ),
                    'css'    => 'max-width:450px;'
                  ),
                  'x_login' => array(
                    'title'    => __( 'D-Local API Login', 'd-local' ),
                    'type'    => 'text',
                    'desc_tip'  => __( 'This is the API Login provided by D-Local when you signed up for an account.', 'd-local' ),
                  ),
                  'x_transkey' => array(
                    'title'    => __( 'D-Local Transaction Key', 'd-local' ),
                    'type'    => 'text',
                    'desc_tip'  => __( 'This is the Transaction Key provided by D-Local when you signed up for an account.', 'd-local' ),
                  ),
                  'secret_key' => array(
                    'title'    => __( 'D-Local Secret Key', 'd-local' ),
                    'type'    => 'text',
                    'desc_tip'  => __( 'This is the Secret Key', 'd-local' ),
                  ),
                  'environment' => array(
                    'title'    => __( 'D-Local Test Mode', 'd-local' ),
                    'label'    => __( 'Enable Test Mode', 'd-local' ),
                    'type'    => 'checkbox',
                    'description' => __( 'This is the test mode of gateway.', 'd-local' ),
                    'default'  => 'no',
                  ),                
                  'callbackurl' => array(
                    'title'    => __( 'Callback URL', 'callbackurl' ),
                    'type'    => 'text',
                    'desc_tip' => __( 'Callback URL', 'callbackurl' ),
                  )
                  'notification' => array(
                    'title'    => __( 'Notification URL', 'notificationurl' ),
                    'type'    => 'text',
                    'desc_tip' => __( 'Notification URL', 'callbackurl' ),
                  )                 
                );    
              }

              public function process_payment( $order_id ) {
                global $woocommerce;
                $customer_order = new WC_Order( $order_id );
                
                $environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
                $environment_url = ( "FALSE" == $environment ) 
                                           ? 'https://sandbox.dlocal.com/payments'
                           : 'https://sandbox.dlocal.com/payments';

                $currency = "NGN";
                $country = $customer_order->billing_country;
                $payment_method_id = "CARD";
                $payment_method_flow = "REDIRECT";
                $payer_name = "$customer_order->billing_first_name $customer_order->billing_last_name";
                $payer_email = "$customer_order->billing_email";
                $payer_user_reference = $customer_order->get_order_number();
                $payer_document = $customer_order->get_order_number();
                $payer_addres_city = $customer_order->billing_city;
                $payer_addres_state = $customer_order->billing_state;
                $payer_addres_zip_code = $customer_order->billing_postcode;
                $get_the_user_ip = $_SERVER['REMOTE_ADDR'];
                $notification_url = $this->notification;
                $callback_url = $this->callbackurl;
                
                $body = '{
                    "amount": ' . $customer_order->order_total .',
                    "currency": "' . $currency .'",
                    "country": "' . $country . '",
                    "payment_method_id" : "' . $payment_method_id . '",
                    "payment_method_flow" : "' . $payment_method_flow . '",
                    "payer":{
                        "name" : "' . $payer_name . '",
                        "email" : "' . $payer_email . '",
                        "user_reference": "' . $payer_user_reference . '",
                        "document": "' . $payer_document . '",
                        "address": {
                            "state"  : "' . $payer_addres_state . '",
                            "city" : "' . $payer_addres_city  . '",
                            "zip_code" : "' . $payer_addres_zip_code . '"
                        },
                        "ip": "' . $get_the_user_ip . '"
                    },
                    "order_id": "' . $payer_user_reference . '",
                    "notification_url": "' . $notification_url . '",
                    "callback_url": "' . $callback_url . '"
                }';

                $xlogin = $this->x_login;
                $secretKey = $this->secret_key;
                $xDate = date('Y-m-d\TH:i:s.\0\0\0\Z');
                
                $data = "$xlogin$xDate";
                $data .= json_encode($body);
                $signature = hash_hmac("sha256", "$xlogin$xDate$body", $this->secret_key);
                $response = null;


                $headers = [
                    'X-Version' => '2.1',
                    'X-Trans-Key' => $this->x_transkey,
                    'Authorization' => 'V2-HMAC-SHA256, Signature: ' . $signature,
                    'Content-Type' => 'application/json',
                    'X-Login' => $xlogin,
                    'X-Date' => $xDate
                ];
                $response = null;
                $args = [
                    'body'        => $body,
                    'timeout'     => '5',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                    'cookies'     => [],
                ];



                $response = wp_remote_post( $environment_url, $args );
              

                if ( is_wp_error( $response ) ) 
                  throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'd-local' ) );
                if ( empty( $response['body'] ) )
                  throw new Exception( __( 'D-Local\'s Response was not get any data.', 'd-local' ) );
                  
                
                $resc = json_decode($response['body']);
                
                
                if($resc->status_code == 100 || $resc->status_code == 101 || $resc->status_code == 102 || $resc->status_code == 103){                    

                    return array(
                        'result' => 'success',
                        'redirect' => $resc->redirect_url,
                    );
                }
                  else {
                    wc_add_notice( json_encode($resc->status_detail), 'error' );
                    $customer_order->add_order_note( 'Error: '. json_encode($resc->status_detail));   
                }

                
                return wp_redirect($resc->redirect_url);
              }
             

              public function validate_fields() {
                return true;
              }
              
              public function callback() {
                $notification = json_decode(file_get_contents('php://input'), true);
                return array(
                    'id' => rgar($notification, 'id'),
                    'type' => $this->status_code_convert(rgar($notification, 'status')),
                    'amount' => rgar($notification, 'amount'),
                    'entry_id' => rgar($notification, 'order_id'),
                    'payment_status' => rgar($notification, 'status'),
                    'note' => rgar($notification, 'status_code') . ' - ' . rgar($notification, 'status_detail')
                );
            }

               
         }
         
     }
 }

 
 add_filter( 'woocommerce_payment_gateways', 'add_to_woo_dlocal');

function add_to_woo_dlocal( $gateways ) {
    $gateways[] = 'WC_DlocalPay';
    return $gateways;
}
