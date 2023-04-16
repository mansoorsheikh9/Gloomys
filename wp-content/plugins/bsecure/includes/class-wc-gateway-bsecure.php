<?php

class WC_Gateway_Bsecure extends WC_Payment_Gateway { 
       
    public $default_desc = "";
    public $default_title = "";
    public $titleWithoutHtml = "";

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        // Setup general properties.
        $this->setup_properties();

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
       
        $this->default_desc = "You will be redirected to a secure website to confirm your order";
        $this->default_title = __('Debit/Credit Card Payments via bSecure','wc-bsecure');

        // Get settings.
        $this->title              = !empty($this->get_option( 'title' )) ? $this->get_option( 'title' ) : $this->default_title;
        
        $this->description        = !empty($this->get_option( 'description' )) ? $this->get_option( 'description' ) .'<br><br>'.__($this->default_desc,'wc-bsecure') : __($this->default_desc,'wc-bsecure');

        $this->description = is_admin() || !is_checkout() ? strip_tags($this->description) : $this->description;

        $this->titleWithoutHtml = !empty($this->get_option( 'title' )) ? $this->get_option( 'title' ) : $this->default_title;

        //$this->instructions       = $this->get_option( 'instructions' );
        //$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
        //$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

        $this->supports = array(
                'products'
            );


        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
        add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );
        

       
    }

   
    public function get_title(){
        
        global $woocommerce, $post;

        $paymentTitle = "";

        if (!empty($post)) {

            $paymentTitle = get_post_meta( $post->ID, '_payment_method_title', true);
        }        
       
        return !empty($paymentTitle) ? strip_tags($paymentTitle) : (is_admin() || !is_checkout() ? $this->titleWithoutHtml : $this->title) ;
    }

    

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties() {
        $this->id                 = 'bsecures';
        $this->icon               = apply_filters( 'bsecure_payment_icon', '' );
        $this->method_title       = __( 'bSecure Universal Checkout', 'wc-bsecure' );
        $this->method_description = __( 'bSecure Payment Gateway.', 'wc-bsecure' );
        $this->has_fields         = false;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
       

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'wc-bsecure' ),
                'label'       => __( 'Enable bSecure payment gateway', 'wc-bsecure' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes',
            ),
            'title' => array(
                'title'       => __( 'Title', 'wc-bsecure' ),
                'type'        => 'text',
                'description' => __( '', 'wc-bsecure' ),
                'default'     => $this->default_title,
                'desc_tip'    => true,
                'custom_attributes'    => ['required' => 'required'],
            ),
            'description' => array(
                'title'       => __( 'Description', 'wc-bsecure' ),
                'type'        => 'textarea',
                'description' => __( '', 'wc-bsecure' ),
                'default'     => __( 'Use bSecure as payment gateway', 'wc-bsecure' ),
                'desc_tip'    => true,
            ),
            /*'instructions' => array(
                'title'       => __( 'Instructions', 'wc-bsecure' ),
                'type'        => 'textarea',
                'description' => __( '', 'wc-bsecure' ),
                'default'     => __( '', 'wc-bsecure' ),
                'desc_tip'    => true,
            ),*/
            
       );
    }





    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment( $order_id ) {        

        $order = wc_get_order( $order_id );

        $requestData = $this->getOrderPayLoad($order);

        $response = $this->sendPaymentRequestBsecure( $requestData );

        if (!empty($response->checkout_url)) {

            //WC()->cart->empty_cart();
            if(!session_id()) {
                //session_start();
            }
            WC()->session->set( 'isFastCheckoutClicked', 1);
            //$_SESSION['isFastCheckoutClicked'] = 1;
            
            return array(
                'result'   => 'success',
                'redirect_bsecure' => $response->checkout_url,
                'order_reference' => $response->order_reference,
            );

            // Remove cart.
            
        }

        /*if ( $order->get_total() > 0 ) {
            // Mark as processing or on-hold (payment won't be taken until delivery).
            $order->update_status( apply_filters( 'woocommerce_'.$this->id.'_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ), __( 'Payment to be made upon delivery.', 'woocommerce' ) );
        } else {
            $order->payment_complete();
        }*/

        

        // Return thankyou redirect.
        
    }

    public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
        if ( $order && $this->id === $order->get_payment_method() ) {
            $status = 'completed';
        }
        return $status;
    }


    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }


    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            
        if ( $this->instructions && ! $sent_to_admin && 'offline' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
            echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        }
    }


    private function sendPaymentRequestBsecure( $requestData ){

        if (class_exists('WC_Bsecure')) {

            $bSecure = new WC_Bsecure;

            $response = $bSecure->bsecureGetOauthToken(); 

            $validateResponse = $bSecure->validateResponse($response,'token_request');     

            if( $validateResponse['error'] ){  


                wc_add_notice(  __('Response Error: ').$validateResponse['msg'], 'error' );
                return false;              
               
                return array(

                        'result' => 'failure',
                        'messages' => 'Response Error: '.$validateResponse['msg']
                    );                

            }else{               

                //$headers =  'Authorization: Bearer '.$response->access_token;
                //$headers =  ['Authorization' => 'Bearer '.$response->access_token];
                $headers =   $bSecure->getApiHeaders($response->access_token);

                $params =   [
                                'method' => 'POST',
                                'body' => $requestData,
                                'headers' => $headers,                  

                            ];  

                $config =  $bSecure->getBsecureConfig();
                $createPaymentGatewayOrder = !empty($config->createPaymentGatewayOrder) ? $config->createPaymentGatewayOrder : "";         

                $response = $bSecure->bsecureSendCurlRequest( $createPaymentGatewayOrder,$params);           

                $validateResponse = $bSecure->validateResponse($response); 

                if($validateResponse['error']){
                    
                    wc_add_notice(  __('Response Error: ').$validateResponse['msg'], 'error' );
                    return false;

                }else{

                    if (!empty($response->body)) {

                        return $response->body;
                    }
                }

            }

        }

        return false;        
    }



    private function getOrderPayLoad($order){

        $billingFirstName = $order->get_billing_first_name();
        $billingLastName = $order->get_billing_last_name();
        $billingEmail = $order->get_billing_email();
        $billingPhone = $order->get_billing_phone();
        $billingCountry = $order->get_billing_country();
        $billingCity = $order->get_billing_city();       
        $billingState = $order->get_billing_state();
        if(!empty( WC()->countries->get_states( $billingCountry )[$billingState] )){
            $billingState = WC()->countries->get_states( $billingCountry )[$billingState];
        }
        
        $billingAddress1 = $order->get_billing_address_1();
        $billingAddress2 = $order->get_billing_address_2();
        $billingAdress = trim($billingAddress1.' '.$billingAddress2);
        $customerName = trim($billingFirstName. ' ' .$billingLastName);
        $authCode = "";
        $countryCode = WC()->countries->get_country_calling_code( $billingCountry );
        $countryCode = str_replace(array('+','-',' '), '', $countryCode);

        $countryCallingCode = $order->get_meta('country_calling_code');
        $countryCallingCode = str_replace(array('+','-',' '), '', $countryCallingCode);

        $countryCode = !empty($countryCallingCode) ? $countryCallingCode : $countryCode;

        if ( is_user_logged_in() ) {

            $userInfo = wp_get_current_user();
            $userPhone = get_user_meta($userInfo->ID, 'phone', true);           
            $authCode = get_user_meta($userInfo->ID, 'wc_bsecure_auth_code', true);
            //$countryCode = get_user_meta($userInfo->ID, 'country_code', true);
            //$billingPhone = !empty($billingPhone) ? $billingPhone : $userPhone;
        }

        if (class_exists('WC_Bsecure')) {

            $bSecure = new WC_Bsecure;
            $countryCode = $bSecure->getCountryCallingCodeFromPhone($billingPhone, $countryCode);
            $billingPhone = $bSecure->phoneWithoutCountryCode($billingPhone, $countryCode,  $billingCountry);
        }
        

        $orderData = [
            "order_id" => $order->get_id(),
            "currency" => $order->get_currency(),
            "sub_total_amount" => $order->get_subtotal(),
            "discount_amount" => $order->get_total_discount(),
            "total_amount" => $order->get_total(),
            "customer" => [

                "auth_code" => $authCode,
                "name" => $customerName,
                "email" => $billingEmail,
                "country_code" => $countryCode,
                "phone_number" =>  $billingPhone
            ],
            "customer_address" => [
                "country" => $billingCountry,
                "city" => $billingCity,
                "address" => $billingAdress,
                "province" => $billingState,
                "area" => '',
                "address" => $billingAdress

            ],
            "customer_address_id" => 0,
            "products" => $this->getCartItems()

        ];

        //var_dump($orderData); die;

        return  $orderData;
    }


    public function getCartItems(){

        if (class_exists('Bsecure_Checkout')) {

            $bSecureCheckout = new Bsecure_Checkout;
        }

        $product_data = [];

        if ( ! WC()->cart->is_empty() ) {

            foreach(WC()->cart->get_cart() as $cart_item ) {

                $product_options = [];
                $product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] :  $cart_item['product_id'];

                //product image
                $getProductDetail = wc_get_product( $product_id );
                $product_title = $getProductDetail->get_title();
                

                if (!empty($cart_item['ppom'])) {
 
                    $product_options = $bSecureCheckout->get_product_options($cart_item['ppom']); 
                    
                }

                if($cart_item['variation_id'] > 0){
                    
                    $variation = get_post($product_id);                                     

                }

                $line_total = !empty($cart_item['line_total']) ? $cart_item['line_total'] : 0;
                $line_subtotal = !empty($cart_item['line_subtotal']) ? $cart_item['line_subtotal'] : 0;                            
                
                $image_id  = $getProductDetail->get_image_id();
                $image = wp_get_attachment_image_url( $image_id, 'full' );
                $regular_price = !empty($getProductDetail->get_regular_price()) ? $getProductDetail->get_regular_price() : $line_total;
                $sale_price = !empty($getProductDetail->get_sale_price()) ? $getProductDetail->get_sale_price() : $regular_price;
                $sub_total = $cart_item['quantity'] * $sale_price;
                $sub_total = !empty($sub_total) ? $sub_total : $line_subtotal;

                $product_data[] = [

                                                'id' => $product_id,
                                                'name' => $product_title,
                                                'sku' => $getProductDetail->get_sku(),
                                                'quantity' => $cart_item['quantity'],
                                                'price' => floatval($regular_price),
                                                'discount' => 0,
                                                'sale_price' => floatval($sale_price),
                                                'sub_total' => $sub_total,
                                                'image' => $image,
                                                'short_description' => $getProductDetail->get_short_description(),
                                                'description' => $getProductDetail->get_description(),
                                                'line_total' => $cart_item['line_total'],
                                                'product_options' => $product_options

                                            ];
            }          
        }

        return $product_data;
    }

}