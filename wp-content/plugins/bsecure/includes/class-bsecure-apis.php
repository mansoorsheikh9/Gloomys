<?php
/**
 * The file that contains bsecure api features.
 *
 * @link       https://www.bsecure.pk
 * @since      1.3.6
 *
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 */

/**
 * The core plugin class.
 *
 * bsecure checkout features
 *
 * @since      1.3.6
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 * @author     bSecure <info@bsecure.pk>
 */


class Bsecure_Apis extends WC_Bsecure {	


	public function __construct(){

		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');              

        $this->base_url = get_option('wc_bsecure_base_url');

        add_action( 'rest_api_init', array($this, 'manage_bsecure_order_for_apps'));

        add_action( 'rest_api_init', array($this, 'get_bsecure_order_for_apps'));

        add_action( 'rest_api_init', array($this, 'get_bsecure_signin_link_for_apps'));

        add_action( 'rest_api_init', array($this, 'get_bsecure_checkout_btn_for_apps'));

        add_action( 'rest_api_init', array($this, 'get_bsecure_customer_profile_for_apps'));

        add_action( 'rest_api_init', array($this, 'get_all_wc_products'));
        
        add_action( 'rest_api_init', array($this, 'get_all_wc_order_statuses'));
	}

	/*
	* Get bSecure Order Details api Route for mobile apps specific 
	*/
	public function get_bsecure_order_for_apps(){		
	    
	    register_rest_route( 'webhook/v2', '/get_bsecure_order_by_ref/', array(
	            'methods' => 'GET', 
	            'callback' => array($this, 'get_bsecure_order_by_ref' ),
	            'permission_callback' => function () {
			      return true;
			    } 
	    	) );

	}


	/*
	* Get bSecure Order Details from mobile apps 
	*/
	public function get_bsecure_order_by_ref(){		

		$bsecure_order_ref = sanitize_text_field($_GET['order_reference']);			

		$order_data = [];		

		$response = $this->bsecureGetOauthToken();	
		
		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			status_header( 422 );
			echo json_encode($validateResponse);
			exit;
			

		}else{

			// Get Order //
			$this->access_token = $response->access_token;

			//$headers =	'Authorization: Bearer '.$this->access_token;
			$headers =   $this->getApiHeaders($this->access_token);

			$request_data['order_ref'] = $bsecure_order_ref;						   			

			$params = 	[
							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];	

			$config = $this->getBsecureConfig();  	        
	    	$order_status_endpoint = !empty($config->orderStatus) ? $config->orderStatus : ""; 
			$response = $this->bsecureSendCurlRequest( $order_status_endpoint, $params, 0, true);		

			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){
				
				status_header( 422 );
				echo json_encode($validateResponse);
				exit;

			}else{

				$order_data = $response->body;
			}


		}


		$validateOrderData =  $this->validateOrderData($order_data);

		if(!empty($validateOrderData['status'])){

			status_header( 422 );
			echo json_encode($validateResponse);
			exit;

		}


		echo json_encode($order_data);
		exit;

	}




	/*
	* Manage bSecuer Order api Route for mobile apps specific 
	*/
	public function manage_bsecure_order_for_apps(){		
	    
	    register_rest_route( 'webhook/v2', '/manage_bsecure_order/', array(
	            'methods' => 'POST', 
	            'callback' => array($this, 'manage_bsecure_order' ),
	            'permission_callback' => function () {
			      return true;
			    } 
	    	) );

	}


	/*
	* Manage bSecure Order from mobile apps 
	*/
	public function manage_bsecure_order(){		

		$order_data = json_decode(file_get_contents('php://input'));				

		$return_json = ['status' => false, 'msg' => __("Invalid Request", 'wc-bsecure')]; 

		$validateOrderData =  $this->validateAppsOrderData($order_data);

		$request_data 	= []; 

		if (!empty($validateOrderData['status'])) {

			status_header( 422 );
			echo json_encode($validateOrderData);
			exit;

		} else {

			$request_data = [];
			$request_data['customer'] =  array('country_code' => '', 'phone_number' => '');
			$request_data['products'] =  array();
			$request_data['amount_to_charge'] = '';
			$request_data['order_id'] = '';
			$request_data['currency_code'] = '';
			$request_data['total_amount'] = '';
			$request_data['sub_total_amount'] = '';
			$request_data['discount_amount'] = '';

			if (!empty($order_data->customer_id)) {

				$customer = get_user_by( 'ID', $order_data->customer_id );

				if (!empty($customer->ID)) {

					$user_phone = get_user_meta($customer->ID, 'phone', true);
					$billing_phone = get_user_meta($customer->ID, 'billing_phone', true);
					$billing_country = get_user_meta($customer->ID, 'billing_country', true);
					$wc_bsecure_auth_code = get_user_meta($customer->ID, 'wc_bsecure_auth_code', true);
					$wc_bsecure_auth_code = !empty($order_data->auth_code) ? $order_data->auth_code : $wc_bsecure_auth_code;
					$country_code = get_user_meta($customer->ID, 'country_code', true);
					$phone = !empty($user_phone) ? $user_phone : $billing_phone;

					if(class_exists('WC')){

						$country_calling_code = WC()->countries->get_country_calling_code( $billing_country );

						$country_code = !empty($country_calling_code) ? str_replace(array('+','-',' '), '', $country_calling_code) : $country_code;
					}

					$country_code = $this->getCountryCallingCodeFromPhone($phone, $country_code);

					$request_data = [ 'customer' => [
														'name' =>  $customer->user_nicename,
														'email' =>  $customer->user_email,
														'country_code' => $country_code,
														'phone_number' => $this->phoneWithoutCountryCode($phone, $country_code, $billing_country),
														

													]
												];

					// if auth_code found then send it with request
					if(!empty($wc_bsecure_auth_code)){
						$request_data['customer']['auth_code'] = $wc_bsecure_auth_code;
					}
					
				}				

			}

			$cart_total_amount = 0;

			foreach ($order_data->line_items as $key => $value) {

				if(!empty($value->product_id)){

					$product_id = $value->product_id;
					$quantity = !empty($value->quantity) ? $value->quantity : 1;
					$getProductDetail = wc_get_product($product_id);

					if(!empty($getProductDetail)){

						$product_options = [];	    			
		    			$product_title = $getProductDetail->get_title(); 

		       			$image_id  = $getProductDetail->get_image_id();
						$image = wp_get_attachment_image_url( $image_id, 'full' );
						$regular_price = !empty($getProductDetail->get_regular_price()) ? $getProductDetail->get_regular_price() : 0;
		       			$sale_price = !empty($getProductDetail->get_sale_price()) ? $getProductDetail->get_sale_price() : $regular_price;

		       			$total_amount = $quantity * $sale_price;

		       			$cart_total_amount += $total_amount; 

						$request_data['products'][] = [

														'id' => $product_id,
														'name' => $product_title,
														'sku' => $getProductDetail->get_sku(),
														'quantity' => $quantity,
														'price' => floatval($regular_price),
														'discount' => 0,
														'sale_price' => floatval($sale_price),
														'sub_total' => $total_amount,
														'image' => $image,
														'short_description' => $getProductDetail->get_short_description(),
														'description' => $getProductDetail->get_description(),
														'line_total' => $total_amount,
														'product_options' => $product_options

													];
			
					}
				}				
				
			}			

			if(!empty($order_data->billing)){

				$billing = $order_data->billing;
					
				$billing_address    =   array(
			        'first_name' => $billing->first_name,
			        'last_name'  => $billing->last_name,
			        'email'      => sanitize_email($billing->email),
			        'address_1'  => $billing->address_1,
			        'address_2'  => $billing->address_2,
			        'city'       => $billing->city,
			        'state'      => $billing->state,
			        'state_name' => $billing->state_name,
			        'postcode'   => $billing->postcode,
			        'country'    => $billing->country,
			        'phone'      => $billing->phone,
			        'company'     => $billing->company

			    );			    

			}

			if(!empty($order_data->shipping)){

				$shipping = $order_data->shipping;

				$shipping_address    =   array(
			        'first_name' => $shipping->first_name,
			        'last_name'  => $shipping->last_name,			        
			        'address_1'  => $shipping->address_1,
			        'address_2'  => $shipping->address_2,
			        'city'       => $shipping->city,
			        'state'      => $shipping->state,
			        'state_name' => $shipping->state_name,
			        'postcode'   => $shipping->postcode,
			        'country'    => $shipping->country,		        
			        'company'     => $shipping->company
			    );			    

			}

			if(!empty($order_data->shipping_lines)){

				foreach ($order_data->shipping_lines as $key => $value) {
					if(!empty($value->total)){
						$cart_total_amount += $value->total;
					}
				}	    

			}
						
			
			$request_data['order_id'] = $this->getBsecureCustomOrderId();
			$request_data['currency_code'] = get_option('woocommerce_currency');
			$request_data['total_amount'] = $cart_total_amount;
			$request_data['sub_total_amount'] = $cart_total_amount;
			$request_data['discount_amount'] = 0;

		}

		$response = $this->bsecureGetOauthToken();	

		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			status_header( 422 );
			echo json_encode(['status' => false, 'msg' => $validateResponse['msg']]);
			exit;	
			

		}

		$accessToken = !empty($response->access_token) ? $response->access_token : "";
		
		$config = $this->getBsecureConfig();  	        
	    $order_url = !empty($config->orderCreate) ? $config->orderCreate : "";
		//$headers =	'Authorization: Bearer '.$accessToken;					  
		$headers =   $this->getApiHeaders($accessToken); 			

		$params = 	[
						'method' => 'POST',
						'body' => $request_data,
						'headers' => $headers,					

					];				
				
		$response = $this->bsecureSendCurlRequest($order_url,$params);
		
		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			status_header( 422 );
			echo json_encode(['status' => false, 'msg' => $validateResponse['msg']]);
			exit;	
			

		}

		echo json_encode(['status' => true, 'response' => $response]);
		exit;
	}

	/*
	* Validate posted data
	*/	
	public function validateAppsOrderData($order_data){


		if (empty($order_data->customer_id) ){

			//return  ['status' => true, 'msg' => __("No customer returned from bSecure server. Please resubmit your order.", "wc-bsecure")];
		}


		if (empty($order_data->line_items) ){

			return  ['status' => true, 'msg' => __("No items returned from server. Please resubmit your order.", "wc-bsecure")];

		}else{

			$product_id = 0;

			foreach ($order_data->line_items as $key => $value) {

				if(!empty($value->product_id)){					

					$product = wc_get_product($value->product_id);					

					if(empty($product) && !is_object($product)){

						$msg =  __("No product found in woocommerce against product_id: ", "wc-bsecure") . $value->product_id;
					}else{

						$product_id = $product->get_id();
					}

				}

				if(empty($product_id)){

					return  ['status' => true, 'msg' => $msg];
					
				}
				
			}
		}

		return ['status' => false, 'msg' => __('Order data validated successfully.','wc-bsecure')];

	}	



	/*
	* endpoint for bSecure signin link
	*/
	public function get_bsecure_signin_link_for_apps(){		
	    
	    register_rest_route( 'webhook/v2', '/get_bsecure_signin_link/', array(
	            'methods' => 'GET', 
	            'callback' => array($this, 'get_bsecure_signin_link' ),
	            'permission_callback' => function () {
			      return true;
			    } 
	    	) );

	}

	/**
	* bSecure Signin link 
	*/
	public function get_bsecure_signin_link(){	

		if(class_exists('Sign_In_With_Bsecure')){

			$bsecureSignIn = new Sign_In_With_Bsecure;
			
			if($bsecureSignIn->build_bsecure_redirect_url()){

				$response = [
								'signin_link' => $bsecureSignIn->build_bsecure_redirect_url(),
								'store_url' => site_url()

							];
				echo json_encode(['status' => true, 'response' => $response]);
				exit;
			}
		}

	}

	
	/*
	* endpoint for bSecure checkout btn
	*/
	public function get_bsecure_checkout_btn_for_apps(){		
	    
	    register_rest_route( 'webhook/v2', '/get_bsecure_checkout_btn/', array(
	            'methods' => 'GET', 
	            'callback' => array($this, 'get_bsecure_checkout_btn' ),
	            'permission_callback' => function () {
			      return true;
			    } 
	    	) );

	}



	/**
	 * Get auth info
	 */
	public function get_bsecure_checkout_btn(){

		$response = $this->bsecureGetOauthToken();	

		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			status_header( 422 );
			echo json_encode(['status' => false, 'msg' => $validateResponse['msg']]);
			exit;	
			

		} else {

			$response = ['checkout_btn' => $response->checkout_btn];
			echo json_encode(['status' => true, 'response' => $response]);
			exit;
		}
		
	}


	public function get_bsecure_customer_profile_for_apps(){

		register_rest_route( 'webhook/v2', '/get_bsecure_customer_profile/', array(
            'methods' => 'POST', 
            'callback' => array($this, 'get_bsecure_customer_profile' ),
            'permission_callback' => function () {
		      return true;
		    } 
    	) );

	}


	public function get_bsecure_customer_profile(){

		$data = json_decode(file_get_contents('php://input'));		

		$state = !empty($data->state) ? sanitize_text_field($data->state) : "";
		$auth_code = !empty($data->auth_code) ? sanitize_text_field($data->auth_code) : "";

		/*$validate = $this->validateState($state);

		if(!$validate['status']){

			status_header( 422 );
			echo json_encode($validate);
			exit;
		}*/

		$response = $this->bsecureGetOauthToken();	

		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			status_header( 422 );
			echo json_encode(['status' => false, 'msg'=>'Response Error: '.$validateResponse['msg']]);
			exit;

		}  

		$access_token = "";

		if(!empty($response->access_token)){

			$access_token = $response->access_token;
		}

		// Sanitize auth code.
		
		$get_customer_endpoint = '/sso/customer/profile';
        $base_url = get_option('wc_bsecure_base_url');

		$url = $base_url.$get_customer_endpoint;

		//$headers =	'Authorization: Bearer '.$access_token;
		$headers =   $this->getApiHeaders($access_token);

		$args = [
			'sslverify' => false,
			'method' => 'POST',
			'body' => ['code' => $auth_code],
			'headers' 	=> $headers
		];

		$response = $this->bsecureSendCurlRequest($url, $args);	

		$validateResponse = $this->validateResponse($response);	

		if($validateResponse['error']){
			
			status_header( 422 );
			echo json_encode(['status' => false, 'msg'=>'Response Error: '.$validateResponse['msg']]);
			exit;

		} else {

			echo json_encode(['status' => true, 'response' => $response->body]);
			exit;
		}		

		
	}



	public function validateState($state){

		$wc_bsecure_client_secret = get_option( 'wc_bsecure_client_secret' );
		$state 	= base64_decode($state);

		$return = ['status' => true, 'msg' => __("State validated","wc-bsecure")];

		if(!wp_verify_nonce( $state, "state-".$wc_bsecure_client_secret )){

			$return = ['status' => false, 'msg' => __("Access Forbidden: Invalid login request found. Please try again later.","wc-bsecure")]; 
			
		}

		return $return;

	}


	public function get_all_wc_products(){

		register_rest_route( 'webhook/v2', '/get_all_wc_products/', array(
            'methods' => 'GET', 
            'callback' => array($this, 'getAllProducts' ),
            'permission_callback' => function () {
		      return true;
		    } 
    	) );

	}

	public function getAllProducts(){
		
		    $page_number  = (isset($_GET['page_number'])) ? $_GET['page_number'] : 1;
		    $post_per_page = (isset($_GET["ppp"])) ? $_GET["ppp"] : 3;
		    $product_list = [];

		    if(class_exists('Bsecure_Checkout')){

				$bsecureCheckout = new Bsecure_Checkout;
			}

		    $args = array(
		        'post_type' => 'product',
		        'offset' => ($page_number - 1) * $post_per_page,
		        'posts_per_page' => $post_per_page,
		    );

		    $wp_query = new WP_Query($args);	
		    
		    unset($args['offset']);	    
		    $args['posts_per_page'] = -1;	    
		    $wp_query2 = new WP_Query($args);		    

		    foreach ($wp_query->posts as $key => $post) {
		    	
		        $product_data = wc_get_product( $post->ID );

		        if(!empty($bsecureCheckout)){
		        	$product_list[] = $bsecureCheckout->get_product_for_api($product_data);
		        }
		        
		    } wp_reset_query();		   
		    
		    if(!empty($product_list)){

		    	//$product_list['page_number'] = $page_number;
		    	//$product_list['ppp'] = $post_per_page;
		    	$product_list['records'] = $wp_query->post_count;		    	
		    	$product_list['total_products'] = $wp_query2->post_count;		    	
		    			    	

		    	echo json_encode(['status' => true, 'response' => $product_list]);
		    	exit;

		    } else {

		    	status_header( 422 );
				echo json_encode(['status' => false, 'response' => __('No product available')]);
				exit;
		    }
		    

	}


	public function get_all_wc_order_statuses(){

		register_rest_route( 'webhook/v2', '/get_all_wc_order_statuses/', array(
            'methods' => 'GET', 
            'callback' => array($this, 'getAllWcOrderStatuses' ),
            'permission_callback' => function () {
		      return true;
		    } 
    	) );
		
	}


	public function getAllWcOrderStatuses(){

		return wc_get_order_statuses();
	}

}