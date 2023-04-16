<?php

/**
 * The file that contains bsecure checkout features.
 *
 * @link       https://www.bsecure.pk
 * @since      1.0.1
 *
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 */

/**
 * The core plugin class.
 *
 * bsecure checkout features
 *
 * @since      1.0.1
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 * @author     bSecure <info@bsecure.pk>
 */
class Bsecure_Checkout extends WC_Bsecure {	

	/**
	* Order statuses
	*/

	const STATUS_PENDING = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELED = 'cancelled';
	const STATUS_ONHOLD = 'on-hold';
	const STATUS_FAILED = 'failed';
	const STATUS_DRAFT = 'bsecure_draft';
	const ORDER_TYPE_APP = 'app';
	const ORDER_TYPE_MANUAL = 'manual'; 
	const ORDER_TYPE_PAYMENT_GATEWAY = 'payment_gateway';
	const BSECURE_CREATED_STATUS = 1;
	const BSECURE_INITIATED_STATUS = 2;
	const BSECURE_PROCESSING_STATUS = 3;
	const BSECURE_EXPIRED_STATUS = 6;
	const BSECURE_FAILED_STATUS = 7;
	const BSECURE_DEV_VIEW_ORDER_URL = 'https://partners-dev.bsecure.app/view-order/';
	const BSECURE_STAGE_VIEW_ORDER_URL = 'https://partners-stage.bsecure.app/view-order/';
	const BSECURE_LIVE_VIEW_ORDER_URL = 'https://partner.bsecure.pk/view-order/';

	private $order_create_endpoint;

	private $order_status_endpoint;

	public function __construct(){

		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');
		$wc_bsecure_payment_gateway = get_option('wc_bsecure_payment_gateway', 'no');

		// for none admin or guests
		add_action( 'wp_ajax_nopriv_ajax_order_to_bsecure', array($this, 'ajax_order_to_bsecure' ) ); 
	    add_action( 'wp_ajax_ajax_order_to_bsecure', array($this, 'ajax_order_to_bsecure' ) );
	    add_action( 'wp_ajax_nopriv_ajax_reminder_popup', array($this, 'ajax_reminder_popup' ) ); 
	    add_action( 'wp_ajax_ajax_reminder_popup', array($this, 'ajax_reminder_popup' ) );
	    add_action( 'wp_ajax_nopriv_send_ajax_webhook_request', array($this, 'send_ajax_webhook_request' ) ); 
	    add_action( 'wp_ajax_send_ajax_webhook_request', array($this, 'send_ajax_webhook_request' ) );	    
	    add_action( 'rest_api_init', array($this, 'order_info'));
	    add_action( 'rest_api_init', array($this, 'product_info'));  
    	//Add a custom field (in an order) to the admin order detail page
		add_action( 'woocommerce_admin_order_data_after_order_details', array($this, 'action_woocommerce_admin_order_data_after_billing_address'), 10, 3 );
		add_action( 'wp_login', array($this, 'actions_at_login'), 10, 2);
		add_action( 'clear_auth_cookie', array($this, 'actions_at_logout') );
		add_action( 'wp', array($this, 'handle_checkout_page' ));	
		add_action( 'wp_loaded', array($this, 'load_bsecure_gateway_class' ) );		

		if ( $wc_bsecure_payment_gateway == 'yes' && $wc_bsecure_is_active == 'yes') {

			add_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );
			add_filter( 'bsecure_payment_icon', array($this, 'display_bsecure_payment_icon' ) );

		} else {

			remove_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );

		}			

		add_filter( 'woocommerce_order_shipping_to_display', array($this, 'wc_custom_order_shipping_to_display' ), 10, 2 );			
		$wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');

		if($wc_show_checkout_btn != 'bsecure_wc_only'){

			if($wc_show_checkout_btn == 'bsecure_wc_both'){	    			

		 		add_action( 'woocommerce_widget_shopping_cart_after_buttons', array($this, 'custom_widget_shopping_cart_proceed_to_checkout'), 20 );		 			
		 		add_action('wp_footer',array($this, 'refreshMiniCart'), 10 );		 			

		 	}else{		 		


		 		add_action( 'woocommerce_widget_shopping_cart_after_buttons', array($this, 'custom_widget_shopping_cart_proceed_to_checkout'), 30 );		 		
		 		add_action('wp_footer',array($this, 'refreshMiniCart'), 10 );

		 	}

	 	}

	 	add_action('wp_footer',array($this, 'refreshMiniCart'), 10 );

	    if(isset($_GET['order_ref']) && $wc_bsecure_is_active == 'yes'){

	    	add_action( 'wp_loaded', array($this, 'manage_wc_order' ));        	

	    }                  

	    $this->base_url = get_option('wc_bsecure_base_url');
	    add_action( 'wp_footer', array($this, 'bsecure_adding_country_prefix_on_billing_phone'));
	    add_action( 'wp_ajax_nopriv_append_country_prefix_in_billing_phone', array($this, 'country_prefix_in_billing_phone'));
		add_action( 'wp_ajax_append_country_prefix_in_billing_phone', array($this, 'country_prefix_in_billing_phone'));

		// Save the custom checkout field as the order meta
		add_action( 'woocommerce_checkout_create_order', array($this, 'custom_update_order_meta'), 10, 2 );

		// Show bSecure checkout button at WC Checkout Page
		add_action( 'woocommerce_before_checkout_form', array($this, 'action_woocommerce_before_checkout_form'), 10, 1 );

		/* Validation for bSecure Payment Gateway */
		add_action('woocommerce_checkout_process', array($this, 'validate_custom_checkout_fields'));

		// Froce to show country in orders
		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
		add_filter("woocommerce_checkout_fields", array($this, 'custom_override_checkout_fields'), 1);

		// Display QisstPay text at checkout page below place order button 
		add_action("woocommerce_review_order_after_submit", array($this, 'display_qisstpay_text_at_checkout'), 10);

		// Display QisstPay text at product below detail page  add to cart button button 
		add_action("woocommerce_after_add_to_cart_button", array($this, 'display_qisstpay_text_at_checkout'), 10);	

		// Load Qisstpay Amount & Other data
		add_action( 'wp_ajax_nopriv_ajax_load_qisstpay_popup', array($this, 'ajax_load_qisstpay_popup' ) ); 
		add_action( 'wp_ajax_ajax_load_qisstpay_popup', array($this, 'ajax_load_qisstpay_popup' ) );


	    // handle reminder popup check at add to cart hook
	    add_action('woocommerce_add_to_cart', array($this, 'bsecure_custom_add_to_cart' ));
	    
	    add_filter('woocommerce_rest_prepare_product_object', array($this,'custom_product_api_response'), 10, 3);

	    // trigger when wc order status changed
		add_action( 'woocommerce_order_status_changed', array($this,'bsecure_action_woocommerce_order_status_changed'), 10, 1 );

		//add_action('save_post', array($this, 'bsecure_action_create_or_update_product' ), 10, 3);
		add_action('post_updated', array($this, 'bsecure_action_after_post_updated' ), 10, 3);
		add_action('woocommerce_product_set_stock', array($this, 'bsecure_action_stock_updated' ));
		add_action('woocommerce_variation_set_stock', array($this, 'bsecure_action_stock_updated' ));
		add_action('woocommerce_thankyou', array($this, 'trigger_webhook_at_thankyou_page'));

	}	

	public function display_bsecure_payment_icon(){

		$payment_gateway_icon = get_option('bsecure_payment_gateway_icon', '');
		
		if (!empty($payment_gateway_icon) && !empty(@getimagesize($payment_gateway_icon))) {

			$queryString = "&";      

      if(strpos($payment_gateway_icon, "?") === false) {

          $queryString = "?";
      }

			return $payment_gateway_icon . $queryString . 'v='.mt_rand();
		}

		return false;

	}

	// Custom Checkout button in mini cart
	public function custom_widget_shopping_cart_proceed_to_checkout() {

		echo $this->getBsecureBtnHtml('minicart');

	}

	public function refreshMiniCart(){

		echo '<script type="text/javascript">

				jQuery(window).load(function(){ 

					jQuery(document.body).trigger("wc_fragment_refresh"); 

					loadMiniCartBtn();

				});

			</script>';

		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');
		$wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');

		if($wc_show_checkout_btn == 'bsecure_only' && $wc_bsecure_is_active == 'yes'){

			echo '<style type="text/css">

					p.woocommerce-mini-cart__buttons.buttons a.checkout.wc-forward {

						display:none;

					}

				</style>';

		}			

		echo '<div class="bsecure-popup-overlay" style="display:none;">

				<img src="'.plugin_dir_url( __FILE__ ) . "../assets/images/bsecure-logo.png".'" alt="">

				    <p>'.__("No longer see the bSecure window?","wc-bsecure").'</p>

				<a class="" href="javascript:focusBsecureWindow();">'.__("Click Here","wc-bsecure").'</a>

			  </div>';

		$display = "none";		

		if (isset($_GET['bsecure_hosted'])) {			

			$display = "block";			

		}		

		echo '<div class="bsecure-popup-loader" style="display:'.$display.';">

				<img src="'.plugin_dir_url( __FILE__ ) . "../assets/images/ajax-loader-large.gif".'" alt="">				    

			  </div>';

		$bsecure_show_qisstpay = get_option("wc_bsecure_show_qisstpay","");

		if( function_exists('WC') && $bsecure_show_qisstpay == 'yes'){
			
			//include(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/templates/qisstpay-popup.php');
		}


		echo '<div id="bsecure-reminder-modal" class="bsecure-modal"></div>';

		//include(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/templates/checkout-reminder-popup.php');
		

	}

	/**
	* Get Order Details api Route 
	*/
	public function order_info(){		

	    //Path to REST route and the callback function
	    register_rest_route( 'webhook/v2', '/order_info/', array(

	            'methods' => 'POST', 

	            'callback' => array($this, 'manage_order_webhook_api' ),

	            'permission_callback' => function () {

			      return true;

			    } 

	    	) );
	}

	/*
	* Get Product Details api Route 
	*/
	public function product_info(){

		$user_id = get_current_user_id();

	    //Path to REST route and the callback function
	    register_rest_route( 'webhook/v2', '/product_info/', array(

	            'methods' =>  'GET', 

	            //'methods' =>  WP_REST_Server::READABLE, 

	            'callback' => array($this, 'get_product_info_api' ),

	            'permission_callback' => function () {

			      return true;

			    } 

	    	) );

	}

	/**
	* Get Order Details from bSecure post request 
	* Create Order from api if not exists in wc else update status
	* Webhook
	*/
	public function manage_order_webhook_api(){		
		
		$order_data = json_decode(file_get_contents('php://input'));

		$order_data_json = json_encode($order_data);

		$wc_bsecure_client_secret = get_option( 'wc_bsecure_client_secret' );		

		$return_json = ['status' => false, 'msg' => __("Invalid Request", 'wc-bsecure'), 'is_error' => true]; 

		$validateOrderData =  $this->validateOrderData($order_data);

		if(!empty($validateOrderData['status'])){

			$return_json = $validateOrderData;

		}else{

			if(!empty($order_data->order_ref)){

				$bsecure_order_ref 	= $order_data->order_ref;

				$placement_status 	= $order_data->placement_status;

				$payment_status 	= $order_data->payment_status;

				$customer 			= $order_data->customer;

				$payment_method 	= $order_data->payment_method;

				$card_details 		= $order_data->card_details;

				$delivery_address 	= $order_data->delivery_address;

				$shipment_method 	= $order_data->shipment_method;

				$wc_order = $this->getWcOrderByBsecureRefId($bsecure_order_ref);

				if(!empty($wc_order)){			
					
					$order = wc_get_order($wc_order->ID);

					// Check if the order is re initiated after failed
					// if true then re-caculate order from v1.7.3
					if($order->get_status() == Bsecure_Checkout::STATUS_FAILED && $placement_status == Bsecure_Checkout::BSECURE_PROCESSING_STATUS){
						$order_id = $this->createOrderFromBsecureToWc($order_data); 
						
						$this->bsecure_action_woocommerce_order_status_changed($wc_order->ID);
						$return_json = ['status' => true, 'msg' => __("Order status updated successfully.", 'wc-bsecure'),'bsecure_order_id' => $wc_order->ID];
					}else{

						$order->update_status($this->woocommerceStatus($placement_status),"",true);

						//Update payment method title
						$this->updatePaymentMethodTitle($order,$order_data);

						//$bsecure_order_id = $order->get_meta('_bsecure_order_id');
						$bsecure_order_id = $order->get_id();

						$this->bsecure_action_woocommerce_order_status_changed($order->get_id());					

						$return_json = ['status' => true, 'msg' => __("Order status updated successfully.", 'wc-bsecure'),'bsecure_order_id' => $bsecure_order_id];		
					}			

				}else{

					if ($placement_status == Bsecure_Checkout::BSECURE_CREATED_STATUS 

						|| $placement_status == Bsecure_Checkout::BSECURE_INITIATED_STATUS) {

						$return_json = ['status' => true, 'msg' =>__("Sorry! your order has not been proccessed.","wc-bsecure"), 'bsecure_order_id' => $order_data->merchant_order_id];

					}  else {						

						$order_id = $this->createOrderFromBsecureToWc($order_data); 

						if($order_id > 0){

							$order = wc_get_order($order_id);

							//$bsecure_order_id = $order->get_meta('_bsecure_order_id');
							$bsecure_order_id = $order->get_id();

							// if order not success

							if($order->get_status() == Bsecure_Checkout::STATUS_CANCELED || $order->get_status() == Bsecure_Checkout::STATUS_FAILED){

								$return_json = ['status' => true, 'msg' =>__("Sorry! your order has been ".$order->get_status(),"wc-bsecure"), 'bsecure_order_id' => $bsecure_order_id];

							} else {

								$msg = __("Order added successfully at woocommerce.", "wc-bsecure");

								if($placement_status == Bsecure_Checkout::BSECURE_EXPIRED_STATUS || $placement_status == Bsecure_Checkout::BSECURE_FAILED_STATUS){
									$statusTitle = ($order_data->placement_status == Bsecure_Checkout::BSECURE_EXPIRED_STATUS) ? __('expired','wc-bsecure') : __('failed','wc-bsecure');

									$msg = __("This order is ".$statusTitle." at bSecure portal but status is different at woocommerce.", "wc-bsecure");
								}

								$this->bsecure_action_woocommerce_order_status_changed($order->get_id());
								
								$return_json = ['status' => true, 'msg' => $msg, 'bsecure_order_id' => $bsecure_order_id];

							}							

						}else{

							$return_json = ['status' => false, 'msg' => __("Unable to create order at woocommerce. Please contact administrator or retry","wc-bsecure"), 'is_error' => true];
						}

					}						 

				}				

			}

		}

		

		if(!$return_json['status']){

			status_header( 422 );

		}

		$return_json = apply_filters( 'return_json_order_info', $return_json );

		echo json_encode($return_json); die;

	}

	/**
	* Get wc product details by sku from bSecure get request
	*/
	public function get_product_info_api($sku=""){		

		// commented in 1.4.9 //
		/*$isAuth = $this->basicAuth();
		if($isAuth['status'] != 200){
			return new WP_Error( $isAuth['error_code'], $isAuth['message'], array( 'status' => $isAuth['status'] ) );
		}*/

		$sku = !empty($_GET['sku']) ? sanitize_text_field($_GET['sku']) : $sku;

		$product_id = wc_get_product_id_by_sku($sku);

		if(!empty($product_id)){

			$_product = wc_get_product($product_id);

			$product_type = $_product->get_type();			

			if($_product->is_in_stock()){

				$product_info = $this->get_product_for_api($_product);

				$inStockLabel = "in stock";

				if($_product->is_on_backorder()){

					$inStockLabel = "on backorder";

				}

				if($product_type == 'grouped' || $product_type == 'variable'){

					$children   = $_product->get_children();

					if(!empty($children)){

						foreach ($children as $key => $value) {

							$_product = wc_get_product($value);

							$product_info['children_products'][] = $this->get_product_for_api($_product);

						}					

					}

				}

				echo json_encode(['status' => true, "msg" => "Product is ".$inStockLabel,'product_details' => $product_info]); die;

			}else{

				echo json_encode(['status' => false, "msg" => "Product is out of stock ",'product_details' => []]); die;

			}			

		}else{

			status_header( 422 );

			echo json_encode(['status' => false, "msg" => "No product found for provided sku!"]); die;		

		}

	}

	/**
	 * Get formated product info to display in api
	 * for Manual Order in bSecure
	 */
	public function get_product_for_api($_product){

		$image_id  = $_product->get_image_id();

		$image = wp_get_attachment_image_url( $image_id, 'full' );

		$regular_price = !empty($_product->get_regular_price()) ? $_product->get_regular_price() : 0;

		$sale_price = !empty($_product->get_sale_price()) ? $_product->get_sale_price() : $regular_price;

		$product_type = $_product->get_type();

		$product_title = $_product->get_title();	

		// get the correct title if variation requested
		if($product_type == 'variation'){				

			$variation = get_post($_product->get_id());

			//$product_title = $variation->post_title;					

		}

		$product_info = [

							'id' => $_product->get_id(),

							'name' => $product_title,

							'sku' => $_product->get_sku(),									

							'price' => floatval($regular_price),

							'sale_price' => floatval($sale_price),									

							'image' => $image,

							'short_description' => $_product->get_short_description(),

							'description' => $_product->get_description(),

							'stock_quantity' => $_product->get_stock_quantity(),

							'is_in_stock' => $_product->is_in_stock(),

							'product_type' => $product_type

						];

			$product_info['children_products'] = [];

			if($product_type == 'grouped' || $product_type == 'variable'){

				$children   = $_product->get_children();

				if(!empty($children)){

					foreach ($children as $key => $value) {

						$_product = wc_get_product($value);

						$product_info['children_products'][] = $this->get_product_for_api($_product);

					}					

				}

			}

			//$product_info['product_data'] = $_product->get_data();

		return  $product_info;

	}

	/**
	* Manage order at wc
	* if order found in wc against bsecure order_ref then update status else create in wc
	*/
	public function manage_wc_order(){		

		$bsecure_order_ref = sanitize_text_field($_GET['order_ref']);

		$order_data = [];

		$wc_order = $this->getWcOrderByBsecureRefId($bsecure_order_ref);

		if(!empty($wc_order)){	

			$order = wc_get_order($wc_order->ID);			

			// if order not success

			if($order->get_status() == Bsecure_Checkout::STATUS_CANCELED || $order->get_status() == Bsecure_Checkout::STATUS_FAILED){				

				wc_clear_notices();

				wc_add_notice(__("Sorry! Your order has been ".$order->get_status(),"wc-bsecure"), 'notice' );

				wp_redirect(wc_get_cart_url());

				exit;

			} else if ($order->get_status() == Bsecure_Checkout::STATUS_DRAFT) {

				wc_clear_notices();

				wc_add_notice(__("Sorry! Your order has not been proccessed.","wc-bsecure"), 'notice' );

				wp_redirect(wc_get_cart_url());

				exit;

			}			

			$_order_key = get_post_meta($wc_order->ID, '_order_key', true);

			wp_redirect(wc_get_checkout_url()."order-received/".$wc_order->ID."/?key=".$_order_key);

			exit;

		}

		$response = $this->bsecureGetOauthToken();		

		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){			

			status_header( 422 );

			die('Response Error: '.$validateResponse['msg']);			

		}else{

			// Get Order //
			$this->access_token = $response->access_token;			

			$headers =   $this->getApiHeaders($this->access_token);

			$request_data['order_ref'] = $bsecure_order_ref;						   			

			$params = 	[

							'method' => 'POST',

							'body' => $request_data,

							'headers' => $headers,					

						];	

			$config = $this->getBsecureConfig();  	        

	    	$this->order_status_endpoint = !empty($config->orderStatus) ? $config->orderStatus : "";		 

			$response = $this->bsecureSendCurlRequest( $this->order_status_endpoint,$params);			

			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){				

				status_header( 422 );

				die('Response Error: '.$validateResponse['msg']);

			}else{

				$order_data = $response->body;

			}

		}

		$validateOrderData =  $this->validateOrderData($order_data);

		if(!empty($validateOrderData['status'])){

			echo  $validateOrderData['msg']; exit;

		}

		// Check if order is in initiated status then redirect back to cart
		if (!empty($order_data->placement_status)) {

			if ($order_data->placement_status == Bsecure_Checkout::BSECURE_CREATED_STATUS 

				|| $order_data->placement_status == Bsecure_Checkout::BSECURE_INITIATED_STATUS ) {

				wc_clear_notices();

				wc_add_notice(__("Sorry! Your order has not been proccessed.","wc-bsecure"), 'notice' );

				wp_redirect(wc_get_cart_url());

				exit;

			} 

			else if ($order_data->placement_status == Bsecure_Checkout::BSECURE_EXPIRED_STATUS || $order_data->placement_status == Bsecure_Checkout::BSECURE_FAILED_STATUS ) {

				$statusTitle = ($order_data->placement_status == Bsecure_Checkout::BSECURE_EXPIRED_STATUS) ? __('expired','wc-bsecure') : __('failed','wc-bsecure');

				if( ! WC()->cart->is_empty() ){
    		
					WC()->cart->empty_cart();
				}

				wc_clear_notices();

				wc_add_notice(__("Sorry! Your order has been ".$statusTitle.".","wc-bsecure"), 'notice' );

				wp_redirect(wc_get_page_permalink( 'shop' ));

				exit;

			}

		}

		$order_id = $this->createOrderFromBsecureToWc($order_data,'none-json');

		if($order_id > 0){

			if( function_exists('WC') ){

			  if( ! WC()->cart->is_empty() ){
    		
					WC()->cart->empty_cart();
				}

			}

			$order = wc_get_order($order_id);			

			// if order not success

			if($order->get_status() == Bsecure_Checkout::STATUS_CANCELED || $order->get_status() == Bsecure_Checkout::STATUS_FAILED){				

				wc_clear_notices();

				wc_add_notice(__("Sorry! Your order has been ".$order->get_status(),"wc-bsecure"), 'notice' );

				wp_redirect(wc_get_cart_url());

				exit;

			}

			$_order_key = get_post_meta($order_id, '_order_key', true);

			wp_redirect(wc_get_checkout_url()."order-received/".$order_id."/?key=".$_order_key);

			exit;

		} else {

			wc_clear_notices();

			wc_add_notice(__("Unable to create order at this moment please try again.","wc-bsecure"), 'notice' );	

			wp_redirect(wc_get_cart_url());

			exit;

		}		

	}

	/**
   * Create order at wc
   *
   * @return order_id
   */
	public function createOrderFromBsecureToWc($order_data, $reponse_type = 'json') {

		$product_counts = 0;
		$cart 			= WC()->cart;
		$order_args 	= []; 
		$first_name     = "";
	    $last_name      = "";
	    $email          = "";
	    $address_1      = "";
	    $address_2      = "";
	    $phone  		= "";
	    $gender         = "";
	    $city           = "";
	    $dob            = "";
	    $postcode       = "";
	    $country        = "";
	    $country_code   = "";
	    $state          = "";
	    $lat          	= "";
	    $long         	= "";

		// First Check Customer //
		if ( !empty($order_data->customer->email) && !empty($order_data->customer->name) ){

			$customer 		= $order_data->customer;
			$first_name		= $this->get_first_name_or_last_name($customer->name);
			$last_name 		= $this->get_first_name_or_last_name($customer->name,'last_name');
			$email 			= $customer->email;
			$phone 			= $customer->phone_number;
			$country_code 	= $customer->country_code;
			$gender 		= $customer->gender;
			$dob 			= $customer->dob;
			$customer		= $this->find_by_email_or_create($order_data->customer,$reponse_type);

			if(!empty($customer->ID)){

				$order_args = ['customer_id' => $customer->ID];

			}

		}

		$order = wc_get_order($order_data->merchant_order_id);

		// if Order type is via bSecure Payment Gateway then handle it from here //
		if(!empty($order_data->order_type) && !empty($order)){

			if(strtolower($order_data->order_type) == Bsecure_Checkout::ORDER_TYPE_PAYMENT_GATEWAY){

				$isCustomer = "";

				if (!empty($customer->ID)) {

					$isCustomer = $customer;

					update_post_meta( $order->get_id(), '_customer_user',  $customer->ID );			

				}
				
				// check if payment method is bSecure then update status
				if($order->get_payment_method() == 'bsecures' || ($order_data->payment_method->id == 1 && $order->get_payment_method() == 'cod' )){

					return $this->updateOrderPaymentGateway($order, $order_data, $isCustomer);
				}else{

					return  $order->get_id();
				}				

			}

		}

		if(empty($order)){

			$order = wc_create_order($order_args);

			$product_id = 0;

			if(!empty($order_data->items)){

				foreach ($order_data->items as $key => $value) {

					if(!empty($value->product_id)){

						$product = wc_get_product($value->product_id);

						if(!empty($product)){

							$product_id = $product->get_id();

						}else{

							$product_id = (!empty($value->product_sku)) ? wc_get_product_id_by_sku($value->product_sku) : false;

						}

						

					}else if(!empty($value->product_sku)){

						$product_id = wc_get_product_id_by_sku($value->product_sku);

					}else{

						return false;

					}					

					if(!empty($product_id)){

						$product_counts++;

						$order->add_product(wc_get_product($product_id) , $value->product_qty);

					}

				}

				if($product_counts == 0){

					return false;

				}

				$order->calculate_totals();			

				$item_price = 0;

				$item_subtotal = 0;

				foreach ($order->get_items() as $order_item_id => $order_item) {

					$productInfo = ($order_item->get_data());

					$product_id = !empty($productInfo['variation_id']) ? $productInfo['variation_id'] : $productInfo['product_id'];

					$item_price = $productInfo['total'];

					$item_subtotal = $productInfo['subtotal'];

					foreach ($order_data->items as $key => $item) {

						if($item->product_id == $product_id){							

							if(!empty($item->product_attributes_raw)){							

								$all_options = $this->get_all_options($item->product_attributes_raw);

								if(!empty($all_options)){

									foreach ($all_options as $option_key => $options) {										

										$option_value = "";

										$priceLabel = " , ";

										if (!empty($options)) {

											foreach ($options as $key => $value) {												

												if(!empty($value['value'])){													

														$price = $value['price'];					

														$item_price += $price;

														$item_subtotal += $price;														

														if(!empty($price)){

															$priceLabel = ' [+ '.wc_price($price).'] ';

														}														

														$option_value .= $value['value'] . $priceLabel;

												}

											}

										}

										$option_value = rtrim($option_value, ' , ');

										wc_update_order_item_meta($order_item_id, $option_key, trim($option_value));

									}									

								}

							}

						}

					}

				 		// Set the new price
						$order_item->set_total( $item_price );

					    $order_item->set_subtotal( $item_subtotal ); 

					    // Make new taxes calculations

					    $order_item->calculate_taxes();

					    $order_item->save(); // Save line item data

				}

				$order->calculate_totals();

			}

		}		

		if (!empty($customer->ID)) {

			update_post_meta( $order->get_id(), '_customer_user',  $customer->ID );

		}

		update_post_meta($order->get_id(),'_bsecure_order_ref', sanitize_text_field($order_data->order_ref));	

		$bsecure_order_id = "";

		if(!empty($order_data->merchant_order_id)){
			
			//$bsecure_order_id = $order_data->merchant_order_id;

		}

		// if order type manual then get bSecure Order ID
		if(!empty($order_data->order_type)){

			if(strtolower($order_data->order_type) == Bsecure_Checkout::ORDER_TYPE_MANUAL){

				$bsecure_order_id = $this->getBsecureCustomOrderId();

			}

			update_post_meta($order->get_id(),'_bsecure_order_type',strtolower($order_data->order_type));

		}

			$bsecure_order_id = !empty($bsecure_order_id) ? $bsecure_order_id : 	$order->get_id();	

    	update_post_meta($order->get_id(),'_bsecure_order_id',sanitize_text_field($bsecure_order_id));

		if(!empty($customer)){		

			$fname     		=    get_user_meta( $customer->ID, 'first_name', true );
			$first_name 	=	(!empty($fname)) ? $fname : $first_name;
	    $lname      	=    get_user_meta( $customer->ID, 'last_name', true );
	    $last_name 		= 	(!empty($lname)) ? $lname : $last_name;
	    $email          =    $customer->user_email;
	    $address_1      =    get_user_meta( $customer->ID, 'billing_address_1', true );
	    $address_2      =    get_user_meta( $customer->ID, 'billing_address_2', true );
	    $city           =    get_user_meta( $customer->ID, 'billing_city', true );
	    $postcode       =    get_user_meta( $customer->ID, 'billing_postcode', true );
	    $country        =    get_user_meta( $customer->ID, 'billing_country', true );
	    $state          =    get_user_meta( $customer->ID, 'billing_state', true );
	    $billing_phone  =    get_user_meta( $customer->ID, 'billing_phone', true );

		}  

		if(!empty($order_data->delivery_address)){

			$delivery_address = $order_data->delivery_address;

			if(!empty($delivery_address->name)){

				$first_name		= $this->get_first_name_or_last_name($delivery_address->name);
				$last_name 		= $this->get_first_name_or_last_name($delivery_address->name, 'last_name');				

			}

			$country 		= $this->get_country_code_by_country_name($delivery_address->country);
			$city 			= $delivery_address->city;
			$state 			= $this->get_state_code($delivery_address->country, $delivery_address->province);
			$address_2		= $delivery_address->area;
			$address_1		= $delivery_address->address;
			$lat			= $delivery_address->lat;
			$long			= $delivery_address->long;
			$phone 			= $this->phoneWithoutCountryCode($phone, $country_code, $country);

			if(!empty($customer)){

				$addressInfo = [

								'first_name' 	=> $first_name,
								'last_name' 	=> $last_name,
								'country_code' 	=> $country_code,
								'address_1' 	=> $address_1,
								'address_2' 	=> $address_2,
								'city' 			=> $city,
								'postcode' 		=> $postcode,
								'country' 		=> $country,
								'state' 		=> $state,
								'phone' 		=> $phone

							];

				$this->addUpdateAddress($addressInfo,$customer->ID);				

			}

		}   

	    $billing_address    =   array(

	        'first_name' => $first_name,
	        'last_name'  => $last_name,
	        'email'      => sanitize_email($email),
	        'address_1'  => $address_1,
	        'address_2'  => $address_2,
	        'city'       => $city,
	        'state'      => $state,
	        'postcode'   => $postcode,
	        'country'    => $country,
	        'phone'      => $phone,
	        'country_code' => $country_code,
	        'gender'     => $gender,
	        'dob'      	 => $dob,
	        'lat'      	 => $lat,
	        'long'       => $long,

	    );

	    $address = array(

	        'first_name' => $first_name,
	        'last_name'  => $last_name,
	        'email'      => sanitize_email($email),
	        'address_1'  => $address_1,
	        'address_2'  => $address_2,
	        'city'       => $city,
	        'state'      => $state,
	        'postcode'   => $postcode,
	        'country'    => $country,
	        'phone'      => $phone,
	        'country_code' => $country_code,                    

	    );

	    $order->set_address($billing_address,'billing');
	    $order->set_address($address,'shipping');
	    $paymentVia = 'cod';	    

		  if(!empty($order_data->payment_method->name)){

		    	$orderNotes = "Payment Method: ".$order_data->payment_method->name;

		    	//if('Credit Card' == $order_data->payment_method->name && 5 == $order_data->payment_method->id){

		    		$paymentVia = 'bsecures';

		    		if(!empty($order_data->card_details->card_name) && !empty($order_data->card_details->card_type)){

		    			//"card_type":"Mastercard","card_number":2449,"card_expire":"12\/25","card_name":"Khan WC1"

		    			$orderNotes = "Card Type: ".$order_data->card_details->card_type.'<br>';

		    			$orderNotes .= "Card Holder Name: ".$order_data->card_details->card_name.'<br>';

		    			$orderNotes .= "Card Number: ".$order_data->card_details->card_number.'<br>';

		    			$orderNotes .= "Card Expire: ".$order_data->card_details->card_expire;	    			

		    		}     		

		    	//}

		    	// add order notes
		    	$order->add_order_note( $orderNotes );

			}

			$wc_bsecure_payment_gateway = get_option('wc_bsecure_payment_gateway', 'no');
			$payment_gateways = WC()->payment_gateways->payment_gateways();

			if ($wc_bsecure_payment_gateway !== 'yes') {			

				add_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );

			}	    

	    // check if COD selected in bSecure
	    if (!empty($order_data->payment_method->id) && $order_data->payment_method->id == 1) {    		

    		if (in_array('cod', array_keys($payment_gateways))) {

    			$paymentVia = 'cod';

    		}

    	}	    

	   	if (!empty($payment_gateways[$paymentVia])) {

	   		$order->set_payment_method($payment_gateways[$paymentVia]);

	   		// Update payment title
	   		if(!empty($order_data->payment_method->name)){

	   			$order->set_payment_method_title($order_data->payment_method->name.' (via bSecure)');
	   			$order->save();

	   		}

	   	}

		

		if ($wc_bsecure_payment_gateway !== 'yes') {

			remove_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );

		}

	    ## ------------- ADD SHIPPING PROCESS START ---------------- ##

	  // Get the customer country code
		$country_code = $order->get_shipping_country();

		// Set the array for tax calculations
		$calculate_tax_for = array(

		    'country' => $country_code,
		    'state' => '', // Can be set (optional)
		    'postcode' => '', // Can be set (optional)
		    'city' => '', // Can be set (optional)

		);

		$shipping_method_title = __("Custom Shipping","wc-bsecure");
		$shipping_method_cost =  0.00;
		$shipping_method_id =  "custom_shipping:00";

	    if(!empty($order_data->shipment_method->id)){

	    	$shipment_method = $order_data->shipment_method;
	    	$shipping_method_title = !empty($shipment_method->name) ? $shipment_method->name : $shipping_method_title;
	    	$shipping_method_cost = !empty($shipment_method->cost) ? $shipment_method->cost : $shipping_method_cost;
	    	$shipping_method_id =  !empty($shipment_method->name) ? strtolower(str_replace(' ', '_', $shipment_method->name)) . ':'.$order_data->shipment_method->id : $shipping_method_id;


				// Get a new instance of the WC_Order_Item_Shipping Object
				$item = new WC_Order_Item_Shipping();
				$item->set_method_title( $shipping_method_title );
				$item->set_method_id( $shipping_method_id ); // set an existing Shipping method rate ID
				$item->set_total( $shipping_method_cost ); // (optional)
				$item->calculate_taxes($calculate_tax_for);
				$order->add_item( $item );
		    $order->calculate_totals();

	    }

	    ## ------------- ADD SHIPPING PROCESS END ---------------- ##

	    ## ------------- ADD additional_charges START From v-1.6.5 ---------------- ##   

	    if (!empty($order_data->summary->additional_charges)) {

		    $this->add_additional_charges($order->get_id(), $order_data->summary->additional_charges);

	    }

	    ## ------------- ADD DISCOUNT START From v-1.4.3 ---------------- ##	   

	    if (!empty($order_data->summary->discount_amount)) {	     	

				$this->wc_order_add_discount( $order->get_id(), __("bSecure Discount"), $order_data->summary->discount_amount );

	    }

	    ## ------------- ADD DISCOUNT END ---------------- ##

	    ## ------------- ADD FEE START From v-1.4.0 ---------------- ##	   

	    if (!empty($order_data->summary->merchant_service_charges)) {

		    $this->add_merchant_service_charges( $order->get_id(), $order_data->summary->merchant_service_charges);

	    }

	    

	    $order_placement_status = !empty($order_data->placement_status) ? $order_data->placement_status : '';
	    $order_status = $this->woocommerceStatus($order_placement_status);
	    $order->update_status($order_status);
	    $order->save();

	    if(!empty($order->get_id())){	    	

	    	if(!empty($order_data->order_type)){

				if(strtolower($order_data->order_type) == Bsecure_Checkout::ORDER_TYPE_APP){

					if(function_exists('wc_get_cart_item_data_hash')){					

						WC()->session = new WC_Session_Handler();

						WC()->session->init();						

						WC()->cart = new WC_Cart();

				    	if ( WC()->cart->get_cart_contents_count() > 0 ) {

								WC()->cart->empty_cart();

						}

					}

				}

			}   	

	    	return $order->get_id();

	    }else{

	    	die(__("Something went wrong while saving order! Please contact administrator.", "wc-bsecure"));

	    }

	    return false; 
	}

	/**
   * Create order at bSecure server
   *
   * @return array server response .
   */
	private function createOrderFromCartToBsecure($accessToken){

		if(!$accessToken){

			$msg = __("Access token not found while sending request at bSecure server", "wc-bsecure");

			$this->displayError($msg,$responseType);

		}

		$request_data = [];
		$request_data['customer'] =  array('country_code' => '', 'phone_number' => '');
		$request_data['products'] =  array();
		$request_data['amount_to_charge'] = '';
		$request_data['order_id'] = '';
		$request_data['currency_code'] = '';
		$request_data['total_amount'] = '';
		$request_data['sub_total_amount'] = '';
		$request_data['discount_amount'] = '';			

		$cart_total_amount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );
		$cart_totals =   WC()->cart->get_totals();
		$amount_to_charge =   !empty($cart_totals['total']) ? floatval($cart_totals['total']) : $cart_total_amount;

		if(is_user_logged_in()){

			$user_info = wp_get_current_user();
			$user_phone = get_user_meta($user_info->ID, 'phone', true);
			$billing_phone = get_user_meta($user_info->ID, 'billing_phone', true);
			$billing_country = get_user_meta($user_info->ID, 'billing_country', true);
			$country_calling_code = WC()->countries->get_country_calling_code( $billing_country );
			$country_code =   get_user_meta($user_info->ID, 'country_code', true);
			$wc_bsecure_auth_code = get_user_meta($user_info->ID, 'wc_bsecure_auth_code', true);
			$phone = !empty($user_phone) ? $user_phone : $billing_phone;
			$country_code = $this->getCountryCallingCodeFromPhone($phone, $country_code);		

			$request_data = [ 'customer' => [

												'name' =>  $user_info->display_name,
												'email' =>  $user_info->user_email,
												'country_code' => $country_code,
												'phone_number' => $this->phoneWithoutCountryCode($phone,$country_code, $billing_country),
											]

										];

			// if auth_code found then send it with request
			if(!empty($wc_bsecure_auth_code)){

				$request_data['customer']['auth_code'] = $wc_bsecure_auth_code;

			}

		}		

		if ( ! WC()->cart->is_empty() ) {

			foreach(WC()->cart->get_cart() as $cart_item ) {

				$product_options = [];

				$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] :  $cart_item['product_id'];

				//product image
  			$getProductDetail = wc_get_product( $product_id );
  			$product_title = $getProductDetail->get_title();  			

  			if (!empty($cart_item['ppom'])) {

  				$product_options = $this->get_product_options($cart_item['ppom']);   				

  			}

				if($cart_item['variation_id'] > 0){					

					$variation = get_post($product_id);										

				}

       	$image_id  = $getProductDetail->get_image_id();
				$image = wp_get_attachment_image_url( $image_id, 'full' );
				$regular_price = !empty($getProductDetail->get_regular_price()) ? $getProductDetail->get_regular_price() : 0;
       	$sale_price = !empty($getProductDetail->get_sale_price()) ? $getProductDetail->get_sale_price() : $regular_price;

				$request_data['products'][] = [

												'id' => $product_id,
												'name' => $product_title,
												'sku' => $getProductDetail->get_sku(),
												'quantity' => $cart_item['quantity'],
												'price' => floatval($regular_price),
												'discount' => 0,
												'sale_price' => floatval($sale_price),
												'sub_total' => $cart_item['quantity'] * $sale_price,
												'image' => $image,
												'short_description' => $getProductDetail->get_short_description(),
												'description' => $getProductDetail->get_description(),
												'line_total' => $cart_item['line_total'],
												'product_options' => $product_options

											];
			}

			$cart_total_amount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );
			$cart_totals =   WC()->cart->get_totals();
			$cart_total_amount =   !empty($cart_totals['total']) ? floatval($cart_totals['total']) : $cart_total_amount;

			// create order before send to bsecure server //
			$wc_order_id = $this->createOrderFromCartToWc();			

			if(empty($wc_order_id)){

				$msg = __("Unable to create order at woocommerce, Please try again or contact administrator!");
				$this->displayError($msg,$responseType);

			}

			$request_data['order_id'] = $wc_order_id;
			$request_data['currency_code'] = get_option('woocommerce_currency');
			$request_data['total_amount'] = $cart_total_amount;
			$request_data['sub_total_amount'] = WC()->cart->get_subtotal();
			$request_data['discount_amount'] = WC()->cart->get_discount_total();
			$request_data['cart_misc_fee'] = $this->getCartFee();// from v 1.4.0			

		}

		$request_data['shipment_charges'] = 0;
    	$request_data['shipment_method_name'] = '';
    	$request_data['shipment_method_id'] = '';
    	$rate_cost_incl_tax = 0;

		//Calculate shipping before add in request
		WC()->cart->calculate_shipping();

		// Add Shipping Details //
		if(!empty(WC()->cart->get_shipping_packages())){

			foreach ( WC()->cart->get_shipping_packages() as $package_id => $package ) {

					if(!empty(WC()->session->get('shipping_for_package_'.$package_id)['rates'] )){

						foreach( WC()->session->get('shipping_for_package_'.$package_id)['rates'] as $method_id => $rate ){

					    if( WC()->session->get('chosen_shipping_methods')[0] == $method_id ){						    	

					        $rate_label = $rate->label; // The shipping method label name
					        $rate_cost_excl_tax = floatval($rate->cost); // The cost excluding tax
					        // The taxes cost
					        $rate_taxes = 0;

					        foreach ($rate->taxes as $rate_tax){

					          $rate_taxes += floatval($rate_tax);
					        }

					        // The cost including tax
					        $rate_cost_incl_tax = $rate_cost_excl_tax + $rate_taxes;
					        $request_data['shipment_charges'] = $rate_cost_incl_tax;
					        $request_data['shipment_method_name'] = $rate->label;
					        $request_data['shipment_method_id'] = $rate->method_id;						       

					        break;

					    }
					}

				}

			}

		}		

		if(!empty($cart_total_amount)){
			//$request_data['total_amount'] = $cart_total_amount - $rate_cost_incl_tax;
		}
		

		$config = $this->getBsecureConfig();
	  	$this->order_create_endpoint = !empty($config->orderCreate) ? $config->orderCreate : "";
		$order_url = $this->order_create_endpoint;

		$headers =   $this->getApiHeaders($accessToken);		   			

		$params = 	[

						'method' => 'POST',
						'body' => $request_data,
						'headers' => $headers,					

					];

		$response = $this->bsecureSendCurlRequest($order_url,$params);	

		return $response;		

	}

	/**
   * Ajax action to send request at server
   *
   * @return json data.
   */
	public function ajax_order_to_bsecure(){

		$wp_nonce = !empty($_POST['wp_nonce']) ? $_POST['wp_nonce'] : "";
		$response = $this->bsecureGetOauthToken();
		$validateResponse = $this->validateResponse($response,'token_request');	

		if( $validateResponse['error'] ){				

			echo json_encode(['status' => false, 'msg' => $validateResponse['msg']]);		

		}else{

			// Create Order //
			$this->access_token = $response->access_token;
			$response = $this->createOrderFromCartToBsecure($this->access_token);
			$validateResponse = $this->validateResponse($response);	

			if( $validateResponse['error'] ){				

				echo json_encode(['status' => false, 'msg' => $validateResponse['msg']]);		

			}else{

				if(!empty($response->body->order_reference)){				

					$redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : "";

					// set reminder popup check  added after v-1.5.7 //
					$this->setReminderPopupCheck();

					echo json_encode(['status' => true, 'msg' => __("Request Success", 'wc-bsecure'), 'redirect' => $redirect, 'order_reference' => $response->body->order_reference]);

					

				}else{

					$complete_response =  __("No response from bSecure server, order_reference field not found.",'wc-bsecure');				

					$errorMsg = !empty($response->message) ? implode(',', $response->message) : $complete_response;

					echo json_encode(['status' => false, 'msg' => __("Your request to bSecure server failed.", 'wc-bsecure') .'<br>'.esc_html($errorMsg), 'redirect' => '']);

				}

			}

		}

		wc_clear_notices();

		wp_die();

	}

	/**
	* Map bScure statuses with woocommerce default statuses
	*/
	public function woocommerceStatus($placement_status){

		/*"order_status": {

      	'created'       => 1,

        'initiated'     => 2,

        'placed'        => 3,

        'awaiting-confirmation' => 4,

        'canceled' => 5,

        'expired' => 6,

        'failed' => 7

        'awaiting-payment' => 8

	    }*/

		$order_status = Bsecure_Checkout::STATUS_PROCESSING;
		$placement_status = (int) $placement_status;

		switch ($placement_status) {

			case 1:
			case 2:

				$order_status = Bsecure_Checkout::STATUS_DRAFT;

			break;

			case 3:

				$order_status = Bsecure_Checkout::STATUS_PROCESSING;

			break;			

			case 4:

				$order_status = Bsecure_Checkout::STATUS_ONHOLD;

			break;

			case 5: //uncommented at 25-06-2021
			//case 6:

				$order_status = Bsecure_Checkout::STATUS_CANCELED;

			break;

			//case 5:
			case 6:
			case 7:

				$order_status = Bsecure_Checkout::STATUS_FAILED;

			break;

			case 8: // Pending Payment at bSecure

				$order_status = Bsecure_Checkout::STATUS_PENDING;

			break;							

			default:

				$order_status = Bsecure_Checkout::STATUS_PROCESSING;

			break;

		}		

		return $order_status;

	}

	public function getWcOrderByBsecureRefId($bsecure_order_ref){		

		$args = array(

		    'posts_per_page'   => 1,
		    'post_type'        => 'shop_order',	
		    'post_status'      =>  array_keys( wc_get_order_statuses() ),

		    'meta_query' => array(

	            array(

	                'key' => '_bsecure_order_ref',
	                'value' => $bsecure_order_ref,
	                'compare' => '=',
	            )

	        )
		);		

		$order = get_posts( $args );

		return !empty($order[0]) ? $order[0] : [];

	}

	/**
	 * Add a custom link at order detail page to bSecure
	 */
	public function action_woocommerce_admin_order_data_after_billing_address( $order ) {

    	$bSecureOrderViewUrl = "";

    	if( !empty($order->get_meta('_bsecure_order_ref')) ){

    		switch ($this->base_url) {

    			case 'https://api-dev.bsecure.app/v1':

    				$bSecureOrderViewUrl = Bsecure_Checkout::BSECURE_DEV_VIEW_ORDER_URL;

    			break;

    			case 'https://api-stage.bsecure.app/v1':

    				$bSecureOrderViewUrl = Bsecure_Checkout::BSECURE_STAGE_VIEW_ORDER_URL;

    			break;

    			default :    				

    				$bSecureOrderViewUrl = Bsecure_Checkout::BSECURE_LIVE_VIEW_ORDER_URL;

    			break;    		

    		}

    		?>

	    		<p class="form-field form-field-wide wc_bsecure_order_ref">
						<a href="<?php echo $bSecureOrderViewUrl . $order->get_meta('_bsecure_order_ref') ; ?>" target="_blank"><?php	echo __( 'View order on bSecure', 'wc-bsecure' ); ?>							
						</a>
					</p>

    		<?php

    	}

	}	

	/**
	* Check if bSecure checkout is active if yes then create order at bSecure and redirect to bSecure
	*/
	public function handle_checkout_page(){

		global $wp;
		$current_url = home_url(add_query_arg(array(), $wp->request));
		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no'); 
		$wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');

		if(rtrim($current_url,"/") == rtrim(wc_get_checkout_url(),"/") &&  $wc_bsecure_is_active == 'yes' && WC()->cart->get_cart_contents_count() > 0 && $wc_show_checkout_btn != 'bsecure_wc_both' && $wc_show_checkout_btn != 'bsecure_wc_only'){			

			$response = $this->bsecureGetOauthToken();
			$validateResponse = $this->validateResponse($response,'token_request');	

			if( $validateResponse['error'] ){			

				status_header( 422 );
				die('Response Error: '.$validateResponse['msg']);				

			}else{

				// Create Order //
				$this->access_token = $response->access_token;
				$response = $this->createOrderFromCartToBsecure($this->access_token);
				$validateResponse = $this->validateResponse($response);	

				if( $validateResponse['error'] ){

					status_header( 422 );
					die('Response Error: '.$validateResponse['msg']);					

				}else{

					if(!empty($response->body->order_reference)){				

						$redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : wc_get_cart_url();
						wp_redirect($redirect);
						exit;					

					}else {	

						$complete_response =  __("No response from bSecure server, order_reference field not found.",'wc-bsecure');
						$errorMsg = !empty($response->message) ? implode(',', $response->message) : $complete_response;
						status_header( 422 );
						echo __("Your request to bSecure server failed.", 'wc-bsecure').'<br>'.esc_html($errorMsg); 
						exit;

					}

				}

			}			

		}

	}	

	/**
	 * actions to perform at login  //
	 */
	public function actions_at_login($username,$user) {
    	//Update Last active date at wc_customer_lookup table//
    	$this->update_activity_wc_customer_lookup($user->ID);
	}

	/**
	 * actions to perform at logout  //
	 */
	public function actions_at_logout() {

	    $userinfo = wp_get_current_user();
	    //Update Last active date at wc_customer_lookup table//
	    $this->update_activity_wc_customer_lookup($userinfo->ID);
	    delete_user_meta( $userinfo->ID, 'wc_bsecure_auth_code' );
	}

  	/**
	 * Gets a user by email or creates a new user.
	 *
	 * @since 1.0.0
	 * @param object $user_data  The bSecure user data object.
	 */
	protected function find_by_email_or_create( $user_data, $reponse_type = 'json') {		

		$user_pass       = wp_generate_password( 12 );
		$user_email      = $user_data->email;
		$user_email_data = explode( '@', $user_email );
		$user_login      = $this->my_unique_user_slug($user_email_data[0]);
		$first_name      = $this->get_first_name_or_last_name($user_data->name);
		$last_name       = $this->get_first_name_or_last_name($user_data->name,'last_name');
		$display_name    = $user_data->name;
		$role            = get_option( 'wp_bsecure_user_default_role', get_option( 'default_role', 'customer' ));

		$user = array(

			'user_pass'       => $user_pass,
			'user_login'      => $user_login,
			'user_email'      => $user_email,
			'display_name'    => $display_name,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'user_registered' => date( 'Y-m-d H:i:s' ),
			'role'            => $role,

		);		

		$user_found = get_user_by( 'email', sanitize_email($user_data->email) );

		if ( false !== $user_found ) {

			$user['user_id'] = $user_found->ID;
			$this->login_user($user_found->ID, $user_found->user_login);
			$this->add_wc_customer_lookup($user);
			return $user_found;

		}

		$new_user = wp_insert_user( $user );

		if ( is_wp_error( $new_user ) ) {

			error_log( $new_user->get_error_message() );

			if($reponse_type == 'json'){

				status_header( 422 );
				echo json_encode(['status' => false, 'msg' => $new_user->get_error_message()]); die;

			}else{

				die($new_user->get_error_message());

			}

			return false;

		} else {

			$user['user_id'] = $new_user;

			if(!empty($user_data->phone_number) && !empty($user_data->country_code)){

	   			$phone_number = '+'.$user_data->country_code.$user_data->phone_number;	   			

	   			update_user_meta( $new_user, 'billing_phone', $user_data->phone_number );
			    update_user_meta( $new_user, 'country_code', $user_data->country_code );			

	   		}

			$this->login_user($new_user, $user_login);

			$this->add_wc_customer_lookup($user);

			return get_user_by( 'id', $new_user );

		}

	}

	/**
	 * Load bSecure Payment Gatway
	 */
	public function load_bsecure_gateway_class(){		

		if (class_exists('WC_Payment_Gateway')){

			include( 'class-wc-gateway-bsecure.php');

		}		

	}

	/**
	 * Display bSecure Payment Gatway
	 */
	public function add_bsecure_gateway_class( $methods ) {	    

    $methods[] = 'WC_Gateway_Bsecure';

    return $methods;

  }
	

  /*
   * Force to display shipping in order details even if null
   */
	public function wc_custom_order_shipping_to_display($shipping,$obj){

		if ( 0 == abs( (float) $obj->get_shipping_total() ) ) {

			if($obj->get_shipping_method()){

				$shipping = $obj->get_shipping_method()." <br> ";

			}

			$shipping .= wc_price( $obj->get_shipping_total() + $obj->get_shipping_tax(), array( 'currency' => $obj->get_currency() ) );

		}		

		return $shipping;

	}

	/**
	 * Using PPOM plugin for product custom options
	 * Collect all option and array and send in request to bsecure
	 */
	public function get_product_options($options){

		$fields = [];
		$optionPrice = [];
		$orderItemMeta = [];
		$index = 0;
		$counter = 0;
		$dataNames = [];

		if(!empty($options['fields'])){

			$fields = ($options['fields']);

		}

		if(!empty($options['ppom_option_price'])){

			$optionPrice = json_decode(stripslashes($options['ppom_option_price']), true);

		}

		//store all data_names to compare with none prices labels
		if(!empty($optionPrice)){

			foreach ($optionPrice as $key => $prices) {

				if(!empty($prices['data_name'])){

					$dataNames[] = $prices['data_name'];	

				}						

			}

		}

		if(!empty($fields)){

			foreach ($fields as $label => $field) {

				$title  = ucwords(str_replace("_"," ",$label));				

				if(!empty($field) && $label != 'id'){

					$index++;

					// Store none prices meta
					if(!in_array($label, ($dataNames))){

						if(is_array($field)){

							$allNonePriceFields = [];

							foreach ($field as $key => $value) {

								$allNonePriceFields[] = ['name' => $value, 'price' => 0];

							}

						}else{

							$allNonePriceFields[] = ['name' => $field, 'price' => 0];

						}								

						$orderItemMeta[$index] = [

													'id' => $index,
													'name' => $title,
													'value' => $allNonePriceFields	

												];

					}							

					//$counter = $index;
					$price = [];

					foreach ($optionPrice as $key => $prices) {	

						if(!empty($prices['data_name'])){							

							if($prices['data_name'] == $label){

								if(is_array($field)){

									$allPriceFields = [];

									foreach ($field as $key => $value) {

										$allPriceFields[] = ['name' => $value, 'price' => $prices['price']];

									}

								}else{

									$allPriceFields[] = ['name' => $field, 'price' => $prices['price']];

								}							

								// Store single value in meta
								$orderItemMeta[$index] = [

														'id' => $index,
														'name' => $title,
														'value' => $allPriceFields	

													];

							}

						}

					}

				}

			}

		}	

		return $orderItemMeta;

	}

	/**
	 * Using PPOM plugin for product custom options 
	 * Get options from bsecure and format to add in wc order meta
	 */
	public function get_all_options($options){		

		$options = (array) $options;
		$allOptions = [];

		if (!empty($options)) {

			foreach ($options as $key => $value) {				

				$attrName = strtolower(str_replace(' ', '_', $value->name));				 

				foreach ($value->value as $keys => $meta_options) {

					$allOptions[$attrName][] = [

													'value' => $meta_options->name,
													'price'	=> $meta_options->price

												];

				}

			}			

		}			

		return $allOptions;

	}

	/**
	* Create order from cart first at wc then send to bsecure
	*/
	public function createOrderFromCartToWc(){

		$billing_email = 'guest@example.com';
		$billing_phone = '';
		$payment_method = 'bsecures';
		$country_code = '92';

		if ( ! WC()->cart->is_empty() ) {

			if(is_user_logged_in()){

				$user_info = wp_get_current_user();
				$user_phone = get_user_meta($user_info->ID, 'phone', true);
				$billing_phone = get_user_meta($user_info->ID, 'billing_phone', true);
				$billing_country = get_user_meta($user_info->ID, 'billing_country', true);
				$billing_email = get_user_meta($user_info->ID, 'billing_email', true);
				$wc_bsecure_auth_code = get_user_meta($user_info->ID, 'wc_bsecure_auth_code', true);
				$country_code = get_user_meta($user_info->ID, 'country_code', true);
				$phone = !empty($user_phone) ? $user_phone : $billing_phone;
				$billing_email = !empty($billing_email) ? $billing_email : $user_info->user_email;	

			}

			if(!empty($phone)){

				$country_code = $this->getCountryCallingCodeFromPhone($phone, $country_code);
				$data['billing_phone'] = $this->phoneWithoutCountryCode($phone, $country_code, $billing_country);

			}

			$data['billing_email'] = $billing_email;
			$data['payment_method'] = $payment_method;
			$cart = WC()->cart;
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();
			$checkout = WC()->checkout();
			$order_id = $checkout->create_order($data);
			$order = wc_get_order($order_id);
			update_post_meta($order_id, '_customer_user', get_current_user_id());
			$order->calculate_totals();
			$order->save();
			//$order->update_status('bsecure_draft');
			wp_update_post(  array(

			    'ID' => $order_id,
			    'post_status' => Bsecure_Checkout::STATUS_DRAFT,

			));

			update_post_meta($order_id, '_bsecure_order_id', $order_id);

			return $order_id;

		}

		return false;

	}	

	public function getBsecureOrderByRefId($bsecure_order_ref){

		$response = $this->bsecureGetOauthToken();
		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){			

			return false;			

		}else{	

			$headers =   $this->getApiHeaders($response->access_token);
			$request_data['order_ref'] = $bsecure_order_ref;						   			

			$params = 	[

							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];	

			$config = $this->getBsecureConfig();  	        
    		$this->order_status_endpoint = !empty($config->orderStatus) ? $config->orderStatus : ""; 
			$response = $this->bsecureSendCurlRequest( $this->order_status_endpoint,$params);	
			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){

				return false;				

			}else{

				return !empty($response->body) ? $response->body : false;

			}

		}

		return false;

	}

	// get cart fee
	public function getCartFee(){

		$feeBreakDown = [];
		$allFeeKeys = ['name','amount','total','tax']; 

		if ( ! WC()->cart->is_empty() ) {

			$fees = WC()->cart->get_fees();

			if (!empty($fees)) {

				foreach ($fees as $keys => $fee) {

					$feeBreakDown[] = [

										'key' => $fee->name,
										'value' => $fee->total
									];						
				}

			}

		}		

		return $feeBreakDown;

	}

	/**
	 * Use this function if payment gateway order type used
	 */
	public function updateOrderPaymentGateway($order, $order_data, $customer){		

		$placement_status 	= $order_data->placement_status;
		$order->update_status($this->woocommerceStatus($placement_status),"",true);

		## ------------- ADD SHIPPING PROCESS START ---------------- ##

	  	// Get the customer country code
		$country_code = $order->get_shipping_country();

		// Set the array for tax calculations
		$calculate_tax_for = array(

		    'country' => $country_code,
		    'state' => '', // Can be set (optional)
		    'postcode' => '', // Can be set (optional)
		    'city' => '', // Can be set (optional)

		);

		$shipping_method_title = __("Custom Shipping","wc-bsecure");
		$shipping_method_cost =  0.00;
		$shipping_method_id =  "custom_shipping:00";

		if(!empty($order_data->shipment_method->id)){

	    	$shipment_method = $order_data->shipment_method;
	    	$shipping_method_title = !empty($shipment_method->name) ? $shipment_method->name : $shipping_method_title;
	    	$shipping_method_cost = !empty($shipment_method->cost) ? $shipment_method->cost : $shipping_method_cost;
	    	$shipping_method_id =  !empty($shipment_method->name) ? strtolower(str_replace(' ', '_', $shipment_method->name)) . ':'.$order_data->shipment_method->id : $shipping_method_id;

			// Get a new instance of the WC_Order_Item_Shipping Object
			$item = new WC_Order_Item_Shipping();
			$item->set_method_title( $shipping_method_title );
			$item->set_method_id( $shipping_method_id ); // set an existing Shipping method rate ID
			$item->set_total( $shipping_method_cost ); // (optional)
			$item->calculate_taxes($calculate_tax_for);
			$order->add_item( $item );
		    $order->calculate_totals();

	  	}

	  	if (!empty($order_data->summary->additional_charges)) {

	    	$this->add_additional_charges($order->get_id(), $order_data->summary->additional_charges);  

    	}

		if (!empty($order_data->summary->discount_amount)) {    	
    	
			$this->wc_order_add_discount( $order->get_id(), __("bSecure Discount"), $order_data->summary->discount_amount );

	  	}

		if (!empty($order_data->summary->merchant_service_charges)) {

	     	$this->add_merchant_service_charges( $order->get_id(), $order_data->summary->merchant_service_charges);

	  	}

	  

		if(!empty($order_data->payment_method->name)){

	    	$orderNotes = "Payment Method: ".$order_data->payment_method->name;

			if(!empty($order_data->card_details->card_name) || !empty($order_data->card_details->card_type)){

				//"card_type":"Mastercard","card_number":2449,"card_expire":"12\/25","card_name":"Khan WC1"
				$orderNotes = "Card Type: ".$order_data->card_details->card_type.'<br>';
				$orderNotes .= "Card Holder Name: ".$order_data->card_details->card_name.'<br>';
				$orderNotes .= "Card Number: ".$order_data->card_details->card_number.'<br>';
				$orderNotes .= "Card Expire: ".$order_data->card_details->card_expire;

			}	    	

	    	// add order notes
	    	$order->add_order_note( $orderNotes );
		}

		// Update payment title
 		if(!empty($order_data->payment_method->name)){

 			$order->set_payment_method_title($order_data->payment_method->name.' (via bSecure)');

 		}
   		
   		if(!empty($order_data->delivery_address)){

			$delivery_address = $order_data->delivery_address;			
			$first_name		= $this->get_first_name_or_last_name($order_data->customer->name);
			$last_name 		= $this->get_first_name_or_last_name($order_data->customer->name,'last_name');	
			$phone 			= $order_data->customer->phone_number;
			$country_code 	= $order_data->customer->country_code;
			$country 		= $this->get_country_code_by_country_name($delivery_address->country);
			$city 			= $delivery_address->city;
			$state 			= $this->get_state_code($delivery_address->country, $delivery_address->province);
			$address_2		= $delivery_address->area;
			$address_1		= $delivery_address->address;
			$lat			= $delivery_address->lat;
			$long			= $delivery_address->long;
			$phone 			= $order_data->customer->phone_number;
			$postcode 		= "";

			if(!empty($customer->ID)){

				$addressInfo = [

								'first_name' 	=> $first_name,
								'last_name' 	=> $last_name,
								'country_code' 	=> $country_code,
								'address_1' 	=> $address_1,
								'address_2' 	=> $address_2,
								'city' 			=> $city,
								'postcode' 		=> $postcode,
								'country' 		=> $country,
								'state' 		=> $state,
								'phone' 		=> $phone

							];

				$this->addUpdateAddress($addressInfo,$customer->ID);
			
			}

		}
   		
   		$order->save();

		update_post_meta($order->get_id(),'_bsecure_order_ref', sanitize_text_field($order_data->order_ref));
	  	update_post_meta($order->get_id(),'_bsecure_order_type',strtolower($order_data->order_type));

	  	if( function_exists('WC') ){

		  	WC()->session = new WC_Session_Handler();
				WC()->session->init();			
		  	WC()->cart = new WC_Cart();

	    	if( ! WC()->cart->is_empty() ){
				WC()->cart->empty_cart();
			}

		}

	    return $order->get_id();

	}

	// define the woocommerce_before_checkout_form callback
	public function action_woocommerce_before_checkout_form( $wccm_autocreate_account ) {

		$wc_bsecure_btn_at_checkout_pg = get_option('wc_bsecure_btn_at_checkout_pg');
		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no'); 

		if($wc_bsecure_btn_at_checkout_pg == 'yes' && $wc_bsecure_is_active == 'yes'){

			$imgHtml = $this->getBsecureBtnHtml('checkout', true); 

	   		echo '<div class="woocommerce-info bsecure-btn-info-wrap">
	   				<div class="bsecure-checkout-info-txt">
					'.__('Want to checkout fast? Click on the button to your right ', 'wc-bsecure').'
					</div>'. $imgHtml.'
				</div>';

		}

	}	

	public function custom_override_checkout_fields($fields) {

		// you can also add some custom HTML here
		$countries_obj = new WC_Countries();
    	$countries = $countries_obj->get_allowed_countries();

    	$calling_code = [];

    	if(!empty($countries)){

    		foreach ($countries as $key => $value) {

    			$ccode = WC()->countries->get_country_calling_code( $key );
    			$ccodeVal = $ccode;

    			if(!empty($ccode)) {	    			

	    			$calling_code[$ccodeVal] = $ccode . ' ' . $key;

	    		}    			

    		}

    	}

	    if(get_option('wc_auto_append_country_code', 'no') == 'yes' ){

	    	$country_code = WC()->customer->get_billing_country();
	    	$default_country_calling_code = WC()->countries->get_country_calling_code( $country_code );

	    	$default_country_calling_code = !empty($default_country_calling_code) ? $default_country_calling_code : '+92';

	    	if(is_user_logged_in()){

		    	$user_info = wp_get_current_user();
			 	$country_code = get_user_meta($user_info->ID, 'country_code', true);
			 	$billing_phone = get_user_meta($user_info->ID, 'billing_phone', true);
			 	$billing_country = get_user_meta($user_info->ID, 'billing_country', true);
			 	$default_country_calling_code = "+".$country_code;
			 	$fields['billing']['billing_phone']['value'] = $this->phoneWithoutCountryCode($billing_phone, $country_code, $billing_country);

	    	}

		   $fields['billing']['country_calling_code']['type'] = 'select';
		   $fields['billing']['country_calling_code']['class'] = array('select2');
		   $fields['billing']['country_calling_code']['options'] = $calling_code;
		   $fields['billing']['country_calling_code']['default'] = $default_country_calling_code;
		   $fields['billing']['country_calling_code']['priority'] = 99;

			}

	    return $fields;
	}

	/* Validate at checkout page */
	public function validate_custom_checkout_fields($checkout) {

		if(get_option('wc_auto_append_country_code', 'no') == 'yes' ){

			if(!empty($_POST['payment_method']) && $_POST['payment_method'] == 'bsecures') {

				if (!preg_match('/^\d+$/', $_POST['billing_phone'])){

					wc_add_notice( __('Please enter a valid phone number <strong>03XXXXXXXXX</strong> in this format.', 'wc-bsecure'), 'error' );

				}	

			}

		}

	}

	public function bsecure_adding_country_prefix_on_billing_phone(){

    	$default_country_calling_code = '';
    	$default_billing_phone = '';    	

	    if(is_user_logged_in()){

	    	$user_info = wp_get_current_user();
		 	$country_code = get_user_meta($user_info->ID, 'country_code', true);
		 	$billing_phone = get_user_meta($user_info->ID, 'billing_phone', true);
		 	$billing_country = get_user_meta($user_info->ID, 'billing_country', true);
		 	$default_country_calling_code = "+".$country_code;
		 	$default_billing_phone = $this->phoneWithoutCountryCode($billing_phone, $country_code, $billing_country);
	    }   
	    

	    ?>

		    <script type="text/javascript">

		        ( function( $ ) {
		        	
		        	var default_country_calling_code = "<?php echo $default_country_calling_code; ?>";
		        	var billing_phone = "<?php echo $default_billing_phone; ?>";
		            jQuery( document.body ).on( 'updated_checkout', function(data) {

		                var country_code = $('#billing_country').val(),
		                payment_method = $('input[name=payment_method]:checked').val(),
		                auto_append_country_code = bsecure_js_object.wc_auto_append_country_code;

		                if(auto_append_country_code == 'no' ){

	                	    jQuery("#country_calling_code").parents('.woocommerce-input-wrapper:eq(0)').hide();
	                	    if(jQuery("#country_calling_code.select2-hidden-accessible").length > 0)
		                		jQuery("#country_calling_code").select2('destroy');

		                		jQuery("#country_calling_code").attr('disabled','disabled');

		                }else{

			                if(payment_method != 'bsecures'){

			                	jQuery("#country_calling_code").parents('.woocommerce-input-wrapper:eq(0)').hide();
			                	if(jQuery("#country_calling_code.select2-hidden-accessible").length > 0)
			                	jQuery("#country_calling_code").select2('destroy');

			                	jQuery("#country_calling_code").attr('disabled','disabled');

			                }else{

			                	jQuery("#country_calling_code").parents('.woocommerce-input-wrapper:eq(0)').show();
			                	jQuery("#country_calling_code").removeAttr('disabled');
			                	if(jQuery("#country_calling_code.select2-hidden-accessible").length > 0)
			                	jQuery("#country_calling_code").select2();

			                }
			            }

			            if(payment_method === 'bsecures' && auto_append_country_code == 'yes'){	

			                var ajax_data = {

			                    action: 'append_country_prefix_in_billing_phone',
			                    country_code: $('#billing_country').val()

			                };  

			                jQuery.post( bsecure_js_object.ajax_url, ajax_data, function( response ) {  

		                		jQuery('#country_calling_code').val(response.calling_code);		                
		                		if(jQuery("#country_calling_code.select2-hidden-accessible").length > 0)
		                		jQuery("#country_calling_code").select2();	               	

			                },'json')
			                .fail(function() {
										     
										  });
			           	}

		            });

		        })( jQuery );        

		    </script>
	    <?php
	}

	public function country_prefix_in_billing_phone() {

	    $calling_code = '';
	    $country_code = isset( $_POST['country_code'] ) ? sanitize_text_field($_POST['country_code']) : '';	 

	    if( $country_code ){

	        $calling_code = WC()->countries->get_country_calling_code( $country_code );
	        $calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;

	    }	    

	    echo json_encode(['calling_code' => $calling_code]);
	    die();

	}

	public function custom_update_order_meta( $order, $data ) {

	    if ( !empty($_POST['country_calling_code']) ) {

	    	$country_calling_code = (int) sanitize_text_field($_POST['country_calling_code']);
	        $order->update_meta_data( 'country_calling_code', "+".$country_calling_code );
	        $billingPhone = $order->get_billing_phone();
	        $hasZero = substr($billingPhone, 0,1);

	        if($hasZero == '0'){

	        	$billingPhone = $_POST['country_calling_code'].substr($billingPhone, 1,strlen($billingPhone));

	        }else{

	        	$billingPhone = $_POST['country_calling_code'].$billingPhone;

	        }

	        $order->set_billing_phone($billingPhone);

	    }

	}

	public function display_qisstpay_text_at_checkout(){

		echo $this->getQisstPayText();

	}


	public function ajax_load_qisstpay_popup()
	{

		if ( ! WC()->cart->is_empty() ) {

			$cart_total_amount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );
			$cart_totals =  WC()->cart->get_totals();
			$cart_total_amount =   !empty($cart_totals['total']) ? floatval($cart_totals['total']) : $cart_total_amount;
			$qisstpayNumOfMonths = 2;
			$fixedTwelveMonths = 12;

			if(class_exists('WC_Bsecure')){

				$wc_bsecure = new WC_Bsecure;
				$qisstpayNumOfMonths = WC_Bsecure::QISSTPAY_PER_MONTH;
				$fixedTwelveMonths = WC_Bsecure::QISSTPAY_PER_MONTH;
				
			}

			$qisstpayNumOfMonths = !empty($_POST['qisstpayNumOfMonths']) ? sanitize_text_field($_POST['qisstpayNumOfMonths']) : $qisstpayNumOfMonths;			

			if(!empty($cart_total_amount)){

				$monthly_amount = $cart_total_amount / $qisstpayNumOfMonths;
				$fixedTwelveMonthsAmount = $cart_total_amount / $fixedTwelveMonths;
				$data = [
								'cart_amount' => number_format($cart_total_amount,2),
								'monthly_amount' => number_format($monthly_amount,2),
								'monthly_amount_formated' => wc_price($fixedTwelveMonthsAmount),

							];

				wp_send_json_success(
							$data
						);
			}
		}

		wp_send_json_error(	 __("Your cart is empty.",'wc-bsecure') );
	}


	//Order Payment method title and other data to update
	public function updatePaymentMethodTitle($order,$order_data){

		$paymentVia = 'cod';	    

	  	if(!empty($order_data->payment_method->name)){

	    	$orderNotes = "Payment Method: ".$order_data->payment_method->name;

	    	//if('Credit Card' == $order_data->payment_method->name && 5 == $order_data->payment_method->id){

	    		$paymentVia = 'bsecures';

	    		if(!empty($order_data->card_details->card_name) && !empty($order_data->card_details->card_type)){

	    			$orderNotes = "Card Type: ".$order_data->card_details->card_type.'<br>';

	    			$orderNotes .= "Card Holder Name: ".$order_data->card_details->card_name.'<br>';

	    			$orderNotes .= "Card Number: ".$order_data->card_details->card_number.'<br>';

	    			$orderNotes .= "Card Expire: ".$order_data->card_details->card_expire;	    			

	    		}     		

	    	//}

	    	// add order notes
	    	$order->add_order_note( $orderNotes );

		}

		$wc_bsecure_payment_gateway = get_option('wc_bsecure_payment_gateway', 'no');
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		if ($wc_bsecure_payment_gateway !== 'yes') {			

			add_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );

		}	    

	    // check if COD selected in bSecure
	    if (!empty($order_data->payment_method->id) && $order_data->payment_method->id == 1) {    		

	  		if (in_array('cod', array_keys($payment_gateways))) {

	  			$paymentVia = 'cod';
	  		}
	  	}	    

	   	if (!empty($payment_gateways[$paymentVia])) {

	   		$order->set_payment_method($payment_gateways[$paymentVia]);

	   		// Update payment title
	   		if(!empty($order_data->payment_method->name)){

	   			$order->set_payment_method_title($order_data->payment_method->name.' (via bSecure)');
	   			$order->save();

	   		}

	   	}		

		if ($wc_bsecure_payment_gateway !== 'yes') {

			remove_filter( 'woocommerce_payment_gateways', array($this, 'add_bsecure_gateway_class' ) );

		}

	}


	public function setReminderPopupCheck(){
		
		if(function_exists('WC')) {
       		WC()->session->set( 'isFastCheckoutClicked', 1);
    	}

	}


	public function ajax_reminder_popup(){		

		$order_ref = !empty($_POST['order_ref']) ? sanitize_text_field($_POST['order_ref']) : "";
		$redirect_url = !empty($_POST['redirect_url']) ? sanitize_text_field($_POST['redirect_url']) : "";

		$return = [
							'status' => false,
							'order_ref' => '',
							'msg' => __('No reminder popup to display!') 
						];			
		
		$isFastCheckoutClicked = !empty(WC()->session->get( 'isFastCheckoutClicked')) ? 1 : 0;

		if( WC()->cart->is_empty()){

			$return['msg'] = __("Cart is Empty!","wc-bsecure");
		}

		if( get_option('wc_bsecure_reminder_popup','no') == 'no' ){

			$return['msg'] = __("Reminder popup id disabled from settings!","wc-bsecure");
		}


		if( $isFastCheckoutClicked == 0 ){

			$return['msg'] = __("Cart updated!","wc-bsecure");
		}
		
		if ( WC()->cart->is_empty() || get_option('wc_bsecure_reminder_popup','no') == 'no' || $isFastCheckoutClicked == 0) {
			echo json_encode($return); wp_die();
		}

		$response = $this->bsecureGetOauthToken();		

		$validateResponse = $this->validateResponse($response,'token_request');	

		if( $validateResponse['error'] ){	

			$return = [
							'status' => false,
							'order_ref' => '',
							'msg' => 'Response Error: '.$validateResponse['msg']
						];	

		}else{

			// Get Order //
			$this->access_token = $response->access_token;				

			$headers =   $this->getApiHeaders($this->access_token);

			$request_data['order_ref'] = $order_ref;						   			

			$params = 	[

							'method' => 'POST',

							'body' => $request_data,

							'headers' => $headers,					

						];	

			$config = $this->getBsecureConfig();  	        

	    	$this->order_status_endpoint = !empty($config->orderStatus) ? $config->orderStatus : "";		 

			$response = $this->bsecureSendCurlRequest( $this->order_status_endpoint,$params);			

			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){

				$return = [
							'status' => false,
							'order_ref' => '',
							'msg' => 'Response Error: '.$validateResponse['msg']
						];

			}else{

				$order_data = $response->body;
				$placement_status = (!empty($order_data->placement_status)) ? $order_data->placement_status : '';

				if ($placement_status == Bsecure_Checkout::BSECURE_CREATED_STATUS 

					|| $placement_status == Bsecure_Checkout::BSECURE_INITIATED_STATUS) {

						ob_start(); 
						include(plugin_dir_path( dirname( __FILE__ ) ) . '/includes/templates/checkout-reminder-popup.php');
						$output_string = ob_get_contents();
						ob_end_clean();

						$return = [
												'status' => true,
												'order_ref' => $order_ref,
												'msg' => __('Success'),
												'popup_html' => $output_string 
											];

					}

				}

			}			

		echo json_encode($return); wp_die();

	}


	public function bsecure_custom_add_to_cart(){		
		
    	WC()->session->set( 'isFastCheckoutClicked', 0);
	}


	// Hook to the product api response build process and add variations objects.	
	public function custom_product_api_response($response, $post, $request){

		$params = $request->get_params();
		// check request is for bsecure portal or not
		if(is_wp_error( $response )  || !isset($params['bsecure']) ) {

			return $response;
		}
			
		WC()->api->includes();
		WC()->api->register_resources( new WC_API_Server( '/' ) );
		
		$wc_webhook = new WC_Webhook();
		$variations_objs = [];

		$productKeys = ["id","name","short_description","slug","tags","sku","price","weight","categories","images","attributes","variations"];
		$variationKeys = ["id","title","price","sku","description","image","attributes","manage_stock","stock_quantity"];
		$new_response = [];

		// Filter product data as per provided keys
		/*foreach($productKeys as $productKey){
			// Temprorary Commented from Asim 1.6.1
			if(in_array($productKey, $response->data)){
				//$new_response[$productKey] = $response->data[$productKey];
			}

		}*/



	    // Store variation data in new variable
	    if(isset($response->data["type"])){
			if ( 'variable' === $response->data["type"] ) {
		       if(!empty($response->data["variations"])){ 
		        foreach( $response->data["variations"] as $variation ) {
		        		
		        		$product_objs = WC()->api->WC_API_Products->get_product( $variation );
				        $variations_objs[] = !empty($product_objs['product']) ? $product_objs['product'] : [];
				    }
				  }
		    }
		}

	    // Filter variation data as per provided keys
	    if(!empty($variations_objs)){
	    	$new_response["variations"] = [];
	    	foreach($variations_objs as $key => $variations_obj){

	    		foreach($variationKeys as $variationKey){

						if(in_array($variationKey, $variations_obj)){
							//$new_response["variations"][$key][$variationKey] = $variations_obj[$variationKey];
						}
					}
	    	}
	    }       
	    $response->data["variations"] = $variations_objs;
	    return $response;
	}

	/**
	 * The function used when order status changes at woocommerce
	 * Send webhook response to bsecure server
	 */

	public function bsecure_action_woocommerce_order_status_changed($order_id){
		
		$order = wc_get_order($order_id);
		$store_id = get_option('wc_bsecure_store_id', '');
		$base_url = get_option('wc_bsecure_base_url', '');
		$request_data = ["order_id" => $order_id, 'store_slug' => $store_id];
		$webhook_endpoint = '/webhook/pim/order-created';
		  
		$webhook_url = $base_url.$webhook_endpoint;	

		if(is_admin()){			
				$this->send_order_info_to_bsecure($order_id);
		}		

	}

	/**
	 * Add a discount to an Orders programmatically
	 * (Using the FEE API - A negative fee)
	 *
	 * @since  1.5.6
	 * @param  int     $order_id  The order ID. Required.
	 * @param  string  $title  The label name for the discount. Required.
	 * @param  mixed   $amount  Fixed amount (float) or percentage based on the subtotal. Required.
	 * @param  string  $tax_class  The tax Class. '' by default. Optional.
	 */
	public function wc_order_add_discount( $order_id, $title, $amount, $tax_class = '' ) {
	    
	    $order    = wc_get_order($order_id);
	    $total = $order->get_total();
	    $item     = new WC_Order_Item_Fee();

	    if ( strpos($amount, '%') !== false ) {
	        $percentage = (float) str_replace( array('%', ' '), array('', ''), $amount );
	        $percentage = $percentage > 100 ? -100 : -$percentage;
	        $discount   = $percentage * $total / 100;
	    } else {
	        $discount = (float) str_replace( ' ', '', $amount );
	        $discount = $discount > $total ? -$total : -$discount;
	    }

	    $item->set_tax_class( $tax_class );
	    $item->set_name( $title );
	    $item->set_amount( $discount );
	    $item->set_total( $discount );

	    if ( '0' !== $item->get_tax_class() && 'taxable' === $item->get_tax_status() && wc_tax_enabled() ) {
	        $tax_for   = array(
	            'country'   => $order->get_shipping_country(),
	            'state'     => $order->get_shipping_state(),
	            'postcode'  => $order->get_shipping_postcode(),
	            'city'      => $order->get_shipping_city(),
	            'tax_class' => $item->get_tax_class(),
	        );
	        $tax_rates = WC_Tax::find_rates( $tax_for );
	        $taxes     = WC_Tax::calc_tax( $item->get_total(), $tax_rates, false );	       

	        if ( method_exists( $item, 'get_subtotal' ) ) {
	            $subtotal_taxes = WC_Tax::calc_tax( $item->get_subtotal(), $tax_rates, false );
	            $item->set_taxes( array( 'total' => $taxes, 'subtotal' => $subtotal_taxes ) );
	            $item->set_total_tax( array_sum($taxes) );
	        } else {
	            $item->set_taxes( array( 'total' => $taxes ) );
	            $item->set_total_tax( array_sum($taxes) );
	        }
	        $has_taxes = true;
	    } else {
	        $item->set_taxes( false );
	        $has_taxes = false;
	    }
	    $item->save();
	    $order->add_item( $item );
	    $order->calculate_totals( $has_taxes );
	    $order->save();
	}

	/**
	 * Add a additional charges to an Orders programmatically
	 * (Using the FEE API)
	 *
	 * @since  1.5.6
	 * @param  int     $order_id  The order ID. Required.
	 * @param  string  $additional_charges  array of amount.	
	 */
	public function add_additional_charges($order_id,$additional_charges){

		$order    = wc_get_order($order_id);
		$additional_charges = is_array($additional_charges) ? $additional_charges : [];
		    
	    foreach ($additional_charges as $key => $value) {

	    	// Get a new instance of the WC_Order_Item_Fee Object		
			  $this->add_fee_to_order($order, $value->name, $value->amount);
	    }	
		
	}

	/**
	 * Add a merchant service charges to an Orders programmatically
	 * (Using the FEE API)
	 *
	 * @since  1.5.6
	 * @param  int     $order_id  The order ID. Required.
	 * @param  string  $merchant_service_charges amount.	
	 */
	public function add_merchant_service_charges($order_id,$merchant_service_charges){

		$order    = wc_get_order($order_id);
		$merchant_service_charges = abs($merchant_service_charges);		
		$this->add_fee_to_order($order, __("Service Charges"), $merchant_service_charges);

	}


	public function add_fee_to_order($order, $title, $amount, $tax_class = ''){

		// Get a new instance of the WC_Order_Item_Fee Object
		$item_fee = new WC_Order_Item_Fee();
		$item_fee->set_name( $title ); // Generic fee name
		$item_fee->set_amount( $amount ); // Fee amount
		$item_fee->set_total( $amount ); // Fee amount											
	  $order->add_item( $item_fee );
	  $order->calculate_totals();
		$order->save();

	}


	public function bsecure_action_create_or_update_product($post_id, $post, $update){

		if ($post->post_status != 'publish' || $post->post_type != 'product' || get_option('wc_bsecure_is_active', 'no') == 'no') {
        return;
    }

    $product = wc_get_product( $post_id );

    if (!$product) {
        return;
    }

    $this->send_info_to_bsecure_via_webhook($product);

	}


	
	public function bsecure_action_stock_updated( $product ) {

	    if(is_admin()){	    	
	    	$this->send_info_to_bsecure_via_webhook($product);
	    }
		
	}

	public function bsecure_action_after_post_updated($post_id, $post_after, $post_before){

		if($post_after->post_status == 'publish'){
			
			$product = wc_get_product( $post_id );

		    if (!$product) {
		        return;
		    }
		    if(is_admin()){	 	    	
		    	$this->send_info_to_bsecure_via_webhook($product);
		    }
		}
	}
	

	/*
	* 
	*/

	public function send_info_to_bsecure_via_webhook($products){	

		$product_ids = [];	
		$store_id = get_option('wc_bsecure_store_id', '');
		$base_url = get_option('wc_bsecure_base_url', '');

		if(is_array($products) && !empty($products)){
			foreach ($products as $key => $product) {
				if($product->get_type() != 'variation'){
					$request = new WP_REST_Request('GET', '/wc/v3/products/'.$product->get_id());
					// added bsecure=1 to see if request is from for bsecure
					$request->set_query_params(['bsecure'=>1]);
					if(isset($response->status) && $response->status !== 200){
			    	$sendData = ['store_slug' => $store_id, 'product_id' => $product->get_id(), 'error' => $response->get_data() ];
			    	$this->sendLogsToBsecureServer($sendData);
			    }

					$product_ids[] = $product->get_id();
			    //$request->set_query_params(['per_page' => 12]);
			    $response = rest_do_request($request);
			    $server = rest_get_server();
			    $product_objs[] = $server->response_to_data($response, false);
			  }
			}
		} else {

			$request = new WP_REST_Request('GET', '/wc/v3/products/'.$products->get_id());
			// added bsecure=1 to see if request is from for bsecure
			$request->set_query_params(['bsecure'=>1]); 
			$product_ids[] = $products->get_id();
	    //$request->set_query_params(['per_page' => 12]);
	    $response = rest_do_request($request);

	    if(isset($response->status) && $response->status !== 200){
	    	$sendData = ['store_slug' => $store_id, 'product_id' => $products->get_id(), 'error' => $response->get_data() ];
	    	$this->sendLogsToBsecureServer($sendData);
	    } 

	    $server = rest_get_server();
	    $product_objs[] = $server->response_to_data($response, false);

			if($products->get_type() == 'variation'){
				return false;
			}
		}

		$product_objs = $this->cleanProductsArray($product_objs);		
	
		
		$request_data = ['store_slug' => $store_id, 'products' => $product_objs];
		$webhook_endpoint = '/webhook/pim/product-updated';
		$webhook_url = $base_url.$webhook_endpoint;

		if(get_option('wc_bsecure_is_active', 'no') == 'yes' && get_option('wc_bsecure_is_pim_enabled', 'no') == 'yes'){			
      
			$this->access_token = $this->getAccessTokenSessionOrBsecure();			

			if(!empty($this->access_token)){				

				$headers =   $this->getApiHeaders($this->access_token);				

				$params = 	[

							'method' => 'POST',
							'body' => json_encode($request_data),
							'headers' => array_merge($headers, array( "Content-type" => "application/json" )),											

						];

				$response = $this->bsecureSendCurlRequest($webhook_url,$params);

				$validateResponse = $this->validateResponse($response);	

				if($validateResponse['error']){
		    	$sendData = ['store_slug' => $store_id, 'product_ids' => $product_ids, 'error' => $response ];
		    	$this->sendLogsToBsecureServer($sendData);
		    }
			}
		}
	}


	public function send_order_info_to_bsecure($order_id){
		
		$order = wc_get_order($order_id);
		$store_id = get_option('wc_bsecure_store_id', '');
		$base_url = get_option('wc_bsecure_base_url', '');
		$wc_order_statuses = wc_get_order_statuses();
		$order_status = $order->get_status();

		if(!empty($wc_order_statuses)){

			foreach($wc_order_statuses as $slug => $label){

				$clean_slug = str_replace("wc-","",$slug);

				if($clean_slug == $order->get_status()){

					$order_status = $label;
				}

			}

		}

		$request_data = ["order_id" => $order_id, 'store_slug' => $store_id, 'order_status' => $order_status,  'order_status_slug' => $order->get_status()];
		$webhook_endpoint = '/webhook/pim/order-fulfilled';		  
		$webhook_url = $base_url.$webhook_endpoint;

		if(get_option('wc_bsecure_is_active', 'no') == 'yes'){

			$this->access_token = $this->getAccessTokenSessionOrBsecure();								

			if(!empty($this->access_token)){

				$headers =   $this->getApiHeaders($this->access_token);

				$params = 	[

							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];
						
				$this->bsecureSendCurlRequest($webhook_url,$params);	

			}				

		}

	}


	/*
	* Send Order info at thank you page
	*/

	public function trigger_webhook_at_thankyou_page($order_id){

		?>

		<script type="text/javascript">

			jQuery(function(){
				var order_id = '<?php echo $order_id; ?>';
				jQuery.post(bsecure_js_object.ajax_url,
						{"action":"send_ajax_webhook_request","order_id":order_id},
					function(res){

					},"json");
					
			});

		</script>

		<?php

			
	}


	public function send_ajax_webhook_request(){

		$order_id = !empty($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : 0;

		$this->send_order_info_to_bsecure($order_id);

		$order = new WC_Order( $order_id );
		$items = $order->get_items();
		$products = [];

		foreach ( $items as $item ) {
		    
		    $product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
		    $product = wc_get_product($product_id);
		    $products[] = $product;
		}

		$this->send_info_to_bsecure_via_webhook($products);

		wp_die('success');

	}


	public function getAccessTokenSessionOrBsecure(){

		if(!class_exists('WC_Session_Handler')){
			return false;
		}
		
		WC()->session = new WC_Session_Handler();

		WC()->session->init();

		if(!empty(WC()->session->get('bsecure_access_token'))){

				return WC()->session->get('bsecure_access_token');

		}else{

			$response = $this->bsecureGetOauthToken();		

			$validateResponse = $this->validateResponse($response,'token_request');		

			if( $validateResponse['error'] ){			

				return false;

				error_log('bSecure Response Error: '.$validateResponse['msg']);			

			}else{

			 return $response->access_token;

			}

		}

		return false;
	}

}