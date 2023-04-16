<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
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
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.1
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 * @author     bSecure <info@bsecure.pk>
 */

class WC_Bsecure {	

    const PLUGIN_NAME = 'WooCommerce';
    const PLUGIN_VERSION = '1.7.4';
    const QISSTPAY_PER_MONTH = 4;
    const QISSTPAY_AMOUNT_MAX_LIMIT = 50000;
    const QISSTPAY_AMOUNT_MIN_LIMIT = 1500;
    const RETURNING_CUSTOMER_MAX_LIMIT = 50000;
    const BSECURE_CHECKOUT_IMG = 'bsecure-checkout-img.svg';
    const BSECURE_QISSTPAY_JUST_SELECT_IMG = 'select-qisstpay-at-checkout.png';

	/**
	 * The access token for accessing bSecure APIs.
	 *
	 * @since 1.0.1
	 * @access private
	 * @var string $access_token The token.
	 */
	private $access_token = '';

	/**
	 * The user's information.
	 *
	 * @since 1.0.1
	 * @access private
	 * @var string $user The user data.
	 */
	private $user;

	public $base_url = "";

	public function __construct(){

		$this->load_dependencies();

		$this->base_url = get_option('wc_bsecure_base_url');

	}

    public function init() {
        
    	if ( ! class_exists( 'WooCommerce' ) ) {

			add_action('admin_notices', array($this, 'show_admin_messages'));            

		} 

        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );

        add_action( 'woocommerce_sections_bsecure',      array( $this, 'output_sections' ) ); 

        add_action( 'woocommerce_settings_bsecure', array( $this, 'output' ) );

        add_action( 'woocommerce_settings_save_bsecure', array( $this, 'save' ) );    	

        add_action( 'woocommerce_update_options_bsecure', array($this, 'update_settings') , 10 );      

        add_action( 'wp_enqueue_scripts', array($this, 'bsecure_frontend_scripts' ), 99 );	 

        add_action( 'admin_enqueue_scripts', array($this, 'bsecure_admin_scripts' ), 99 );   

        add_filter( 'woocommerce_available_payment_gateways', array($this, 'custom_available_payment_gateways' )); 
        

    }

    /**
	 * Load the required dependencies for this plugin.
	 *
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function load_dependencies() {

        if ( ! class_exists( 'WooCommerce' ) ) {

            //return false;
        }

		/**
		 * The class responsible checkout functionality
		 * core plugin.
		 */

        if ( file_exists(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-checkout.php') ) {

            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-checkout.php';

            new Bsecure_Checkout;

        }

        /**
         * The class responsible checkout functionality
         * core plugin.
         */
        if ( file_exists(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sign-in-with-bsecure.php') ) {

            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sign-in-with-bsecure.php';

            new Sign_In_With_Bsecure;

        }

        /**
         * The class responsible api functionality
         * core plugin.
         */
        if ( file_exists(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-apis.php') ) {

            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-apis.php';

            new Bsecure_Apis;

		}  

        /**
         * The class responsible api functionality
         * core plugin.
         */
        if ( file_exists(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-admin.php') ) {

            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bsecure-admin.php';

            new Bsecure_Admin;

        }      

	}

    /*
	 * show admin notices
	 */
	public function show_admin_messages(){

	    $plugin_messages = "";
	    $required_plugins = array();	   
	    $required_plugins = array(

	                                array('name'=>'Woocommerce', 'download'=>admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ), 'path'=>'woocommerce/woocommerce.php')

								  );

	    foreach($required_plugins as $aPlugin){

	        // Check if plugin not exists
	        if(!is_plugin_active( $aPlugin['path'] ))

	        {

	            $plugin_messages = '<a href="'.$aPlugin['download'].'" target="_blank">'.$aPlugin['name'].'</a>';

	        }

	    }

        // if woocomerce is not installed or deactivate
	    if(!empty($plugin_messages))
	    {

            echo '<div class="notice notice-error is-dismissible"><p>'.__('bSecure plugin requires ', 'wc-bsecure').' <strong>'.$plugin_messages.'</strong> '.__('plugin to be installed and activate.', 'wc-bsecure').'</p></div>';
	    }

        // if permalinks not activated
        if(empty(get_option('permalink_structure')))
        {

            echo '<div class="notice notice-warning is-dismissible"><p><strong>bSecure </strong> '.__('plugin require pretty  permelinks, please make sure your permalink settings are updated.', 'wc-bsecure').' </p></div>';              
        }

        // if permalinks not activated
        if(!$this->wc_bsecure_check_ssl())
        {
            echo '<div class="notice notice-error"><p><strong>bSecure</strong> '.__('plugin require ssl enabled at your domain, some of the plugin feature may not work properly without ssl enabled.', 'wc-bsecure').' </p></div>';              
        }
	}

    public function add_settings_tab( $settings_tabs ) {

        $settings_tabs['bsecure'] = __( 'bSecure', 'wc-bsecure' );

        return $settings_tabs;
    }

    /**
     *  Output sections
     */
    public function output_sections() {

        global $current_section;

        $sections = $this->get_sections();

        if ( empty( $sections ) || 1 === sizeof( $sections ) ) {

            return;

        }

        echo '<ul class="subsubsub">';

        $array_keys = array_keys( $sections );

        foreach ( $sections as $id => $label ) {

            echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=bsecure&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id || ($current_section == '' &&  $id == 'bsecure-setting') ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';

        }

        echo '</ul><br class="clear" />';

    }

    /**
     * Get sections
     *
     * @return array
     */
    public function get_sections() {

        $sections = array(

            'bsecure-setting'    => __( 'bSecure Settings', 'wc-bsecure' ),

            'bsecure-advance-setting'    => __( 'bSecure Advance Setting', 'wc-bsecure' ),            

        );        

        return apply_filters( 'woocommerce_get_sections_bsecure' , $sections );

    }

    /**
     * Output the settings
     */
    public function output() {

        global $current_section;

        $settings = $this->get_settings( $current_section );

        WC_Admin_Settings::output_fields( $settings );

    }

    /**
     * Save settings
     */
    public function save() {

        global $current_section;

        $settings = $this->get_settings( $current_section );         

        WC_Admin_Settings::save_fields( $settings );

        if (class_exists('Bsecure_Admin')){

            $bsecure_activated = get_option("bsecure_activated", 0);

            if($bsecure_activated < 1){

                $bsecure_admin = new Bsecure_Admin;

                $bsecure_admin->plugin_activate_deactivate();

            }

        }

    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public function settings_tab() {

    	 global $current_section;     

        //woocommerce_admin_fields( self::get_settings($current_section) );

    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public function update_settings() {

         global $current_section;

        //woocommerce_update_options( self::get_settings( $current_section) );

    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings( $section = '') {

        $defualtBaseUrl = 'https://api.bsecure.pk/v1';
        $bSecurePortalUrl = 'https://partner.bsecure.pk/integration-live'; 
        $section = (!empty($section)) ? $section : 'bsecure-setting';   

        switch( $section ){

            case 'bsecure-advance-setting':

                $settings = array('section_title' => array(

                    'name'     => __( 'bSecure Advance Settings', 'wc-bsecure' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'wc_bsecure_advance_settings_start'
                    ),                    

                    'is_enable' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Enable bSecure as Payment Gateway.', 'wc-bsecure' ),
                        'id'   => 'wc_bsecure_payment_gateway',
                        'default'   => 'no'
                    ),

                    // Commented since 11/04/2022 in verison 1.7.3
                    
                   /* 'show_qisstpay' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Show QisstPay Installments pricing.', 'wc-bsecure' ),
                        'id'   => 'wc_bsecure_show_qisstpay',
                        'default'   => 'no'
                    ),*/

                    'is_display_at_checkout_pg' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Show bSecure button at checkout page', 'wc-bsecure' ),
                        'id'   => 'wc_bsecure_btn_at_checkout_pg',
                        'default'   => 'no',
                        'value' => get_option('wc_bsecure_btn_at_checkout_pg', 'no')
                    ),                    

                    'auto_append_country_code' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Append Country Code at Billing Phone.', 'wc-bsecure' ),
                        'id'   => 'wc_auto_append_country_code',
                        'default'   => 'no',
                        'value' => get_option('wc_auto_append_country_code', 'no')
                    ),       

                    'is_hosted_checkout' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'bSecure checkout with hosted window', 'wc-bsecure' ),
                        'id'   => 'wc_is_hosted_checkout',
                        'default'   => 'no',
                        'value' => get_option('wc_is_hosted_checkout', 'yes')
                    ),

                    'reminder_popup' => array(

                        'name' => __( '', 'wc-bsecure' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Show bSecure checkout reminder popup', 'wc-bsecure' ),
                        'id'   => 'wc_bsecure_reminder_popup',
                        'default'   => 'no',
                        //'value' => get_option('wc_is_hosted_checkout', 'yes')
                    ),

                    'section_end' => array(

                         'type' => 'sectionend',
                         'id' => 'wc_bsecure_advance_settings_end'
                    )

                );

            break;

             case 'bsecure-setting':

                 $settings = array(

                        'section_title' => array(

                            'name'     => __( 'bSecure Settings', 'wc-bsecure' ),
                            'type'     => 'title',
                            'desc'     => '',
                            'id'       => 'wc_bsecure_section_title'
                        ),

                        'is_enable' => array(

                            'name' => __( '', 'wc-bsecure' ),
                            'type' => 'checkbox',
                            'desc' => __( 'Enable bSecure checkout.', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_is_active',
                            'default'   => 'no'
                        ),

                        'pim_enabled' => array(

                            'name' => __( '', 'wc-bsecure' ),
                            'type' => 'checkbox',
                            'desc' => __( 'Enable PIM', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_is_pim_enabled',
                            'default'   => 'no',
                            'desc_tip' => __('Syncing taptap allows you to seamlessly add products to the taptap app, increase your website orders and conversions.','wc-bsecure')
                        ),

                        'login_button' => array(

                            'name' => __( '', 'wc-bsecure' ),
                            'type' => 'checkbox',
                            'desc' => __( 'Show bSecure signup button at Login Form .', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_button_show_on_login',
                            'default'   => 'no'
                        ),

                        'show_checkout_btn' => array(

                            'name' => __( 'bSecure Checkout Button Display Type on Cart Page', 'wc-bsecure' ),
                            'type' => 'select',
                            'desc' => __( 'Show bSecure checkout button at your choice on cart page.', 'wc-bsecure' ),
                            'id'   => 'wc_show_checkout_btn',
                            'default'   => 'bsecure_only',
                            'options' => [

                                'bsecure_only' => __('Show only bSecure checkout button','wc-bsecure'),
                                'bsecure_wc_both' => __('Show both bSecure checkout and default WooCommerce checkout buttons','wc-bsecure'),
                                'bsecure_wc_only' => __('Show only Woocommerce default checkout button','wc-bsecure')
                            ]

                        ),                        

                        'base_url' => array(

                            'name' => __( 'bSecure Base URL', 'wc-bsecure' ),
                            'type' => 'text',
                            'desc' => '',
                            'id'   => 'wc_bsecure_base_url',
                            'default'   => $defualtBaseUrl
                        ),

                        'store_id' => array(

                            'name' => __( 'bSecure Store ID', 'wc-bsecure' ),
                            'type' => 'text',
                            'desc' => __( 'You can find this Store ID from bSecure portal.', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_store_id'
                        ),                        

                        'client_id' => array(

                            'name' => __( 'bSecure Client ID', 'wc-bsecure' ),
                            'type' => 'text',
                            'desc' => __( 'You can find this client id from bSecure portal.', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_client_id'
                        ),

                        'client_secret' => array(

                            'name' => __( 'bSecure Client Secret', 'wc-bsecure' ),
                            'type' => 'password',
                            'desc' => __( 'You can find this client secret from bSecure portal. <br> <a href="'.$bSecurePortalUrl.'" target="_blank">'.$bSecurePortalUrl.'</a>', 'wc-bsecure' ),
                            'id'   => 'wc_bsecure_client_secret'
                        ),

                        'section_end' => array(

                             'type' => 'sectionend',
                             'id' => 'wc_bsecure_section_end'
                        ),                        

                    );

           break;

        }      

        $settings = WC_Bsecure::handleMultisites($settings, $section);
        return apply_filters( 'wc_settings_bsecure', $settings, $section );

    }    

	/* Load Script file in front end */
	public function bsecure_frontend_scripts() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            //return false;
        }

	    wp_enqueue_script( 'bsecure_frontend_scripts', plugin_dir_url( __FILE__ ) . '../assets/js/bsecure-front.js',null, mt_rand(), true );

        wp_enqueue_style( 'bsecure_frontend_style', plugin_dir_url( __FILE__ ) . '../assets/css/bsecure-style.css',null, mt_rand(), false );

        $wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');

        $wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');

        $wc_bsecure_title = get_option('wc_bsecure_title', '');

        $bsecure_checkout_btn_url = get_option('bsecure_checkout_btn_url','');

        $wc_auto_append_country_code = get_option('wc_auto_append_country_code','yes');

        $wc_is_hosted_checkout = get_option('wc_is_hosted_checkout','yes');
        
        $wc_bsecure_reminder_popup = get_option('wc_bsecure_reminder_popup','no');

        $wc_bsecure_show_qisstpay = get_option("wc_bsecure_show_qisstpay","");

        $queryString = "&";

        if(strpos($bsecure_checkout_btn_url, "?") === false) {

            $queryString = "?";
        }

        $bsecure_checkout_btn_url = (!empty($bsecure_checkout_btn_url) && 
        !empty(@getimagesize($bsecure_checkout_btn_url))) ? $bsecure_checkout_btn_url. $queryString. 'v='.mt_rand() :

            plugin_dir_url( __FILE__ ) . '../assets/images/'.WC_Bsecure::BSECURE_CHECKOUT_IMG;

	     wp_localize_script( 'bsecure_frontend_scripts', 'bsecure_js_object',

	            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 
                    'ajax_loader' => plugin_dir_url( __FILE__ ) . '../assets/images/ajax-loader.gif',
                    'nonce' => wp_create_nonce('bsecure-ajax-nonce'), 
                    'wc_bsecure_is_active' => $wc_bsecure_is_active, 
                    'wc_show_checkout_btn' => $wc_show_checkout_btn, 
                    'wc_bsecure_title' => $wc_bsecure_title, 
                    'wc_bsecure_checkout_btn_url' =>  $bsecure_checkout_btn_url,
                    'bsecureWindow' => '',
                    'wc_auto_append_country_code' => $wc_auto_append_country_code, 
                    'wc_is_hosted_checkout' => $wc_is_hosted_checkout, 
                    'wc_bsecure_reminder_popup' => $wc_bsecure_reminder_popup, 
                    'wc_cart_url' => wc_get_cart_url(), 
                    'wc_checkout_url' => wc_get_checkout_url(), 
                    'bsecure_show_qisstpay' => $wc_bsecure_show_qisstpay, 
                    'site_url' => site_url() 
                ) 
            );

	}

    public function bsecure_admin_scripts() {

        wp_enqueue_script( 'bsecure_admin_scripts', plugin_dir_url( __FILE__ ) . '../assets/js/bsecure-admin.js',null, mt_rand(), true );

        wp_enqueue_style( 'bsecure_admin_style', plugin_dir_url( __FILE__ ) . '../assets/css/bsecure-style-admin.css',null, mt_rand(), false );

    }

	/**
     * Send curl request using wp core function WP_Http for curl request
     *
     * @return array server response .
     */
	public function bsecureSendCurlRequest($url, $params = '', $retry = 0, $isJson = false){

		$wp_http = new WP_Http;

        $params['timeout'] = 20; // How long the connection should stay open in seconds.

        $pluginInfo = ['x-plugin-name' => WC_Bsecure::PLUGIN_NAME, 'x-plugin-version' => WC_Bsecure::PLUGIN_VERSION];

        $params['headers'] = !empty($params['headers']) ? array_merge($pluginInfo,  $params['headers']) : $pluginInfo;

		$response = $wp_http->request( $url,  ($params));	
        
		if( is_wp_error( $response ) ) {    

            if($retry < 3){

                $retry++;

                $res = $this->bsecureSendCurlRequest($url, $params, $retry);

            }else{

                status_header( 422 );

                $msg = __("An error occurred while sending request: ","wc-bsecure") . $response->get_error_message();

                if($isJson){

                    echo json_encode(['status' => false, 'msg' => $msg]); die;

                }else{

                    echo ($msg); die;

                }

            }             

		}else if(!empty($response['body'])){			

			return json_decode($response['body']);						

		}

        // Retry request 3 times if failed
        if($retry < 3){

            $retry++;

            error_log('retries:'.$retry.' response blank');

            $this->bsecureSendCurlRequest($url, $params, $retry);          

        }else{

            status_header( 422 );

            $msg = __("An error occurred while sending request!","wc-bsecure") ; 

            if($isJson){

                echo json_encode(['status' => false, 'msg' => $msg]); die;

            }else{

                echo ($msg); die;

            }

            die;

        }

	}

    /*
     * Validate bSecure response 
     */
    public function validateResponse($response, $type = ''){

        $errorMessage = ["error" => false, "msg" => __("No response from bSecure server!")];

        if(empty($response)){

            return ["error" => true, "msg" => __("No response from bSecure server!")];

        }

        if(empty($response->status) && !empty($response->message)){         

            return $errorMessage;

        }else if((!empty($response->status) && $response->status != 200)){            

            $msg = (is_array($response->message)) ? implode(",", $response->message) : $response->message;

            $errorMessage = ["error" => true, "msg" => $msg];

        } else if(!empty($response->message) && !is_array($response->message) && !empty($response->status)){            
            if($response->status != 200){

                $errorMessage = ["error" => true, "msg" => $response->message];

            }            

        }else if(!empty($response->message) && is_array($response->message) && !empty($response->status)){      

            if($response->status != 200){

                $errorMessage = ["error" => true, "msg" => implode(",", $response->message)];

            }           

        }     

        return $errorMessage;

    }

    /*
     * Validate bSecure response order data 
     */
    public function validateOrderData($order_data){

        $defaultMessage = [
                            'status' => false, 
                            'msg' => __('Order data validated successfully.','wc-bsecure'),
                            'is_error' => false
                        ];

        if (strtolower($order_data->order_type) == 'payment_gateway') {

            return $defaultMessage;

        }

        if (empty($order_data->customer) ){

            //return  ['status' => true, 'msg' => __("No customer returned from bSecure server. Please resubmit your order.", "wc-bsecure")];

        }

        if (empty($order_data->items) ){

            return  [
                        'status' => true, 
                        'msg' => __("No cart items returned from bSecure server. Please resubmit your order.", "wc-bsecure"),
                        'is_error' => true
                    ];

        }else{

            $product_id = 0;

            foreach ($order_data->items as $key => $value) {

                if(!empty($value->product_id)){                 

                    $product = wc_get_product($value->product_id);                  

                    if(empty($product) && !is_object($product)){

                        $msg =  __("No product found in woocommerce against product_id: ", "wc-bsecure") . $value->product_id;

                    }else{

                        $product_id = $product->get_id();

                    }

                }else if($value->product_sku){

                    $product_id = wc_get_product_id_by_sku($value->product_sku);

                    if(empty($product_id)){

                        $msg =  __("No product found in woocommerce against SKU: ", "wc-bsecure") . $value->product_sku;

                    }                   

                }

                if(empty($product_id)){

                    return  ['status' => true, 'msg' => $msg, 'is_error' => true];                   

                }                

            }

        }

        return $defaultMessage;

    }

    /**
     * Get oauth token from server
     *
     * @return array server response .
     */
    public function bsecureGetOauthToken(){        

        $grant_type = 'client_credentials';
        $client_id = get_option('wc_bsecure_client_id');
        $client_secret = get_option('wc_bsecure_client_secret');
        $store_id = get_option('wc_bsecure_store_id', '');
        $client_id = !empty($store_id) ? $client_id.':'.sanitize_text_field($store_id) : $client_id;
        $config = $this->getBsecureConfig();

        if(!empty($config->token)){

            $oauth_url = $config->token;

        }else{

            return false;

        }        

        $params =   [
                        'sslverify' => false,
                        'method' => 'POST',
                        'body' => 
                            [
                                'grant_type' => $grant_type, 
                                'client_id' => $client_id, 
                                'client_secret' => $client_secret,
                            ],
                    ];

        $response = $this->bsecureSendCurlRequest($oauth_url,$params);

        if($response->status !== 200){

           return  $response;

        }

        if(!empty($response->body)){
           
            if (!empty($response->body->checkout_btn)) {

                update_option( 'bsecure_checkout_btn_url', $response->body->checkout_btn );

            }

            if (isset($response->body->buyer_protection_enabled)) {

                update_option( 'bsecure_buyer_protection_enabled', $response->body->buyer_protection_enabled );

            }

            if (isset($response->body->buyer_protection_tooltip_text)) {

                update_option( 'bsecure_buyer_protection_tooltip_text', $response->body->buyer_protection_tooltip_text );

            }

            if (!empty($response->body->payment_icon)) {

                update_option( 'bsecure_payment_gateway_icon', $response->body->payment_icon );

            } else {

                update_option( 'bsecure_payment_gateway_icon', '' );
            }

            if(function_exists('WC') && !empty($response->body->access_token)) {

                WC()->session = new WC_Session_Handler();
                WC()->session->init();
                WC()->session->set( 'bsecure_access_token', $response->body->access_token);
            }

            return $response->body;
        }       

        return $response;
    }

    /**
     * Get Configuration
     *
     * @return array server response .
     */
    public function getBsecureConfig(){

        if(!empty($this->base_url)){            

            $url = $this->base_url."/plugin/configuration";
            $params = ['method' => 'GET'];
            $response = $this->bsecureSendCurlRequest( $url,  $params);            

            if(!empty($response->body->api_end_points)){

                return $response->body->api_end_points;

            }

        }

        return false;

    }

    /*
     * Checkk ssl is enabled
     */
    public static function wc_bsecure_check_ssl(){

        //allow localhost environment to activate without ssl //
        $whitelist = array(

                            '127.0.0.1', 
                            '::1'

                        );

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){            

            if(!is_ssl()){

                return false;

            }

        }

        return true;

    }

    // Create record in wc_customer_lookup table
    public function add_wc_customer_lookup($data){

        // wc_customer_lookup //
        global $wpdb;

        $select_customer = $wpdb->get_row("SELECT email from {$wpdb->prefix}wc_customer_lookup WHERE email='".$data['user_email']."' OR user_id = '".$data['user_id']."'");

        if(empty($select_customer)){

            $customer_data = [

                        'user_id' => $data['user_id'],
                        'username' => $data['user_login'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['user_email'],
                        'date_last_active' => date('Y-m-d H:i:s'),
                        'date_registered' => date('Y-m-d H:i:s'),
                    ];            

            $wc_customer = $wpdb->insert("{$wpdb->prefix}wc_customer_lookup", $customer_data);

            return $wc_customer;

        }

        return false;

    }

    // Update record in wc_customer_lookup table
    public function update_wc_customer_lookup($data, $whereArray){

        global $wpdb;

        $customer = $wpdb->get_row("SELECT email from {$wpdb->prefix}wc_customer_lookup WHERE email='".$data['user_email']."'");

        if(!empty($customer)){

            $isUpdated = $wpdb->update("{$wpdb->prefix}wc_customer_lookup", $data,$whereArray);

            return $isUpdated;
        }       

        return false;

    }

    // Update activity in wc_customer_lookup table
    public function update_activity_wc_customer_lookup($user_id){

        global $wpdb;

        $customer = $wpdb->get_row("SELECT email from {$wpdb->prefix}wc_customer_lookup WHERE user_id='".$user_id."'");

        if(!empty($customer)){

            $isUpdated = $wpdb->update("{$wpdb->prefix}wc_customer_lookup", ['date_last_active' => date('Y-m-d H:i:s')],['user_id' => $user_id]);

            return $isUpdated;

        }        

        return false;

    }

    /*
     * Login user
     */
    public function login_user($user_id,$username){

        if ( !is_user_logged_in() ) {

            wp_set_current_user( $user_id, $username );
            wp_set_auth_cookie( $user_id );
            $this->update_activity_wc_customer_lookup($user_id);

            return true;

        }

        return false;

    }

    /*
     * Generate Unique Username
     */
    public function my_unique_user_slug( $slug ) {

        global $wpdb;

        $slug = $this->cleanString($slug);

        $check_sql = "SELECT user_login FROM $wpdb->users WHERE user_login = %s LIMIT 1";

        if ( ! $wpdb->get_var( $wpdb->prepare( $check_sql, $slug ) ) ) {

            return $slug;

        }

        $suffix = 2;

        do {

            $alt_slug = $slug . $suffix;
            $user_slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug ) );
            $suffix++;

        } while ( $user_slug_check );

        return $alt_slug;

    }

    /*
     * Extract first_name or last_name from fullName
     */
    public function get_first_name_or_last_name($fullName,$nameType = 'first_name'){

        $fullnameArray  = explode(' ', $fullName);
        $last_name      = !empty($fullnameArray[1]) ? end($fullnameArray) : "";
        $first_name     = str_replace(' '.$last_name, '', $fullName);

        return $nameType == 'last_name' ? sanitize_text_field($last_name) : sanitize_text_field($first_name);

     }

    /*
     * Remove bsecure payment gateway if exists
     */
    function custom_available_payment_gateways( $available_gateways ) {        

        $wc_bsecure_payment_gateway = get_option('wc_bsecure_payment_gateway', 'no');

        if(!empty($available_gateways['bsecures'])){            

            if ( $wc_bsecure_payment_gateway == 'no') {

                //unset($available_gateways['bsecures']);

            }

        }        

        return $available_gateways;

    }

    /*
     * Remove country code from phone number
     */
    public function  phoneWithoutCountryCode($phone_number, $country_code='92', $country = 'PK' ){

        if (preg_match('/^\+\d+$/', $phone_number)){

            if(!empty($country_code)){

                 return str_replace('+'.$country_code, '', $phone_number);

            }

            return $phone_number;

        }      

        $phone_number = str_replace(array('+','-',' '), '', $phone_number);

        if(!empty($country)){

            $country_code = WC()->countries->get_country_calling_code( $country );

            $country_code = !empty($country_code) ? str_replace(array('+','-',' '),'',$country_code) : $country_code;

        }

        if(strlen($phone_number) > 12){

            $calling_code = substr($phone_number,0,2);

            if ($calling_code == $country_code) {

                //$phone_number = substr($phone_number, -10);

                $phone_number = preg_replace("/^\+?$country_code|\|$country_code|\D/", '', ($phone_number));

            }else{

                $phone_number = preg_replace("/^\+?$calling_code|\|$calling_code|\D/", '', ($phone_number));

            }

        }

        $hasZero = substr($phone_number, 0,1);

        if($hasZero == '0'){

            $phone_number = substr($phone_number, 1,strlen($phone_number));

        }        

        return $phone_number;

    }

    /*
     * Add country code in phone number
     */
    public function  phoneWithCountryCode($phone_number, $country_code='92', $country = 'PK'){        

        $phone_number = '+'.$country_code.$phone_number;

        return $phone_number;

    }

    /* Save customer address */
    public function addUpdateAddress($address_info, $customer_id){

        update_user_meta( $customer_id, 'first_name', $address_info['first_name'] ); 
        update_user_meta( $customer_id, 'last_name', $address_info['last_name'] );
        update_user_meta( $customer_id, 'country_code', $address_info['country_code'] );

        // Save Billing Details //              
        update_user_meta( $customer_id, 'billing_first_name', $address_info['first_name'] );
        update_user_meta( $customer_id, 'billing_last_name', $address_info['last_name'] );
        update_user_meta( $customer_id, 'billing_address_1', $address_info['address_1'] ); 
        update_user_meta( $customer_id, 'billing_address_2', $address_info['address_2'] );
        update_user_meta( $customer_id, 'billing_city', $address_info['city'] );
        update_user_meta( $customer_id, 'billing_postcode', $address_info['postcode'] );
        update_user_meta( $customer_id, 'billing_country', $address_info['country'] );
        update_user_meta( $customer_id, 'billing_state', $address_info['state'] );
        update_user_meta( $customer_id, 'billing_phone', $address_info['phone'] );

        // Save Shipping Details //             
        update_user_meta( $customer_id, 'shipping_first_name', $address_info['first_name'] ); 
        update_user_meta( $customer_id, 'shipping_last_name', $address_info['last_name'] ); 
        update_user_meta( $customer_id, 'shipping_address_1', $address_info['address_1'] ); 
        update_user_meta( $customer_id, 'shipping_address_2', $address_info['address_2'] ); 
        update_user_meta( $customer_id, 'shipping_city', $address_info['city'] );
        update_user_meta( $customer_id, 'shipping_postcode', $address_info['postcode'] );
        update_user_meta( $customer_id, 'shipping_country', $address_info['country'] );
        update_user_meta( $customer_id, 'shipping_state', $address_info['state']);
        update_user_meta( $customer_id, 'shipping_phone', $address_info['phone'] );
    }

    /* Get country code form WC countries */
    public function get_country_code_by_country_name($country_name){

        $countries = WC()->countries->countries;

        foreach ($countries as $key => $value) {

            if($value == $country_name)
            return $key;
        }

        return $country_name;
    }

    /* Get state code form WC states */
    public function get_state_code($country_name, $state_name){

        $states = WC()->countries->get_states( $this->get_country_code_by_country_name($country_name) );

        if(!empty($states)){

            foreach ($states as $key => $value) {

                if(str_replace([' '], '-', $value) == str_replace([' '], '-', $state_name))

                return $key;
            }
        }

        return $state_name;
    } 

    /*
    * Generate/get bSecure custom order id 
    */   
    public function getBsecureCustomOrderId($merchant_order_id = 0, $useTimeStamp = true){

        global $wpdb;

        // using timestamp for custom order id
        if ($useTimeStamp) {

            $merchant_order_id = empty($merchant_order_id) ? time() : $merchant_order_id;

            $record = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_bsecure_order_id' AND meta_value = ".$merchant_order_id );

            if(!empty($record->post_id)){              

                $merchant_order_id = time();               

                $this->getBsecureCustomOrderId($merchant_order_id);

            } else {

                update_option('bsecure_merchant_order_id', $merchant_order_id);              

                return $merchant_order_id;

            }            
        }

         // using pre-defined id for custom order id
        $record = $wpdb->get_row( "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key = '_bsecure_order_id'"  );

        $previous_bsecure_merchant_order_id = 1;

        if(!empty($record->meta_value)){

            $previous_bsecure_merchant_order_id = (int) ($record->meta_value);  

        }

        $order_id_prefix = get_option('wc_order_id_prefix');
        $order_id_prefix = strlen($order_id_prefix) > 5 ? substr($order_id_prefix, 0,5) : $order_id_prefix;
        $merchant_order_id = (int) get_option('bsecure_merchant_order_id');
        $merchant_order_id = !empty($merchant_order_id) ? $merchant_order_id+1 : $previous_bsecure_merchant_order_id; 
        $bsecure_leading_zero_in_order_number = get_option('bsecure_leading_zero_in_order_number');

        if(empty($bsecure_leading_zero_in_order_number)){

            // Update with default value
            $bsecure_leading_zero_in_order_number = 8;

            update_option('bsecure_leading_zero_in_order_number',$bsecure_leading_zero_in_order_number);

        }       

        update_option('bsecure_merchant_order_id',$merchant_order_id);        

        $id_with_leading_zero = (str_pad($merchant_order_id, $bsecure_leading_zero_in_order_number, '0', STR_PAD_LEFT)); 
        $custom_order_id = $id_with_leading_zero;

        if(!empty($order_id_prefix)){

            $custom_order_id = $order_id_prefix.'-'.$merchant_order_id;

        }

        return $custom_order_id;
    }

    public function displayError($msg, $responseType = ""){

        if($responseType == 'json'){

            echo json_encode(['error' => true, 'msg' => $msg]); 

            exit;

        }else if($responseType == 'return'){

            return (['error' => true, 'msg' => $msg]); 

        }else{

            die($msg);

        }
    }

    public function cleanString($string) {

       $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
       return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

    }

    /*
    * Get bSecure button with html at main cart page     
    */
    public function getBsecureBtnHtml($page = 'cart', $onlyBtnUrl = false, $args = []){       

        $mainClass = "";
        $imgClass = "";
        $outerClass = "";
        $wc_bsecure_title = get_option('wc_bsecure_title');

        switch ($page) {

            case 'checkout':   

                $outerClass = 'bsecure-loader-outer-checkout'; 
                $imgClass = 'bsecure-btn-at-checkout';

                break;

            case 'minicart':                

                $mainClass = 'bsecure-checkout-mini-cart-widget';
                $imgClass = 'wc-bsecure-checkout-btn-mini-cart';

                break;

        }

        $queryString = "&";
        $bsecure_checkout_btn_url = get_option("bsecure_checkout_btn_url","");
        //$buyer_protection_enabled = get_option("bsecure_buyer_protection_enabled");
        // Added in v-5.6 only
        //$buyer_protection_tooltip_text = $buyer_protection_enabled == 1 ?  '<span class="tooltiptext">'.get_option("bsecure_buyer_protection_tooltip_text").'</span>' : "";
        
        if(strpos($bsecure_checkout_btn_url, "?") === false) {

            $queryString = "?";
        }
        
        $btnUrl = (!empty($bsecure_checkout_btn_url ) && 
        !empty(@getimagesize($bsecure_checkout_btn_url))) ? $bsecure_checkout_btn_url. $queryString . 'v='.mt_rand() :

                 plugins_url('bsecure') . '/assets/images/'.WC_Bsecure::BSECURE_CHECKOUT_IMG;
       
        $wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');
        $wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');
        $btnHtml = "";      

        if($wc_bsecure_is_active == 'yes'){

            $btnHtml = '<p class="'.$mainClass.'">
                            <a href="javascript:;" class="bsecure-checkout-button" data-btn-text="'.__( $wc_bsecure_title, "wc-bsecure" ).'" title="" style="outline-color: transparent;outline: none;">
                            <span class="bsecure-loader-outer '.$outerClass.'">
                                <span class="bsecure-loader-span"></span>                                
                                    <img src="'.$btnUrl.'" alt="'.__( $wc_bsecure_title, 'wc-bsecure' ).'" class="'.$imgClass.'"> 
                            </span>
                            </a>
                        </p>';
        }

        if($onlyBtnUrl){

            $btnHtml = '<a href="javascript:;" class="bsecure-checkout-button" data-btn-text="'.__( $wc_bsecure_title, "wc-bsecure" ).'" title="" style="outline-color: transparent;outline: none;">

                            <span class="bsecure-loader-outer '.$outerClass.'">

                                <span class="bsecure-loader-span"></span>
                                
                                    <img src="'.$btnUrl.'" alt="'.__( $wc_bsecure_title, 'wc-bsecure' ).'" class="'.$imgClass.'">                              
                            </span>

                            </a>';
        }


        if($page == 'reminder_popup'){

            $redirectUrl = !empty($args['redirect_url']) ? $args['redirect_url'] : "";

            $btnHtml = "<a href='javascript:;' 
            onclick=openBsecureWindow('".($redirectUrl)."'); class='reminder-popup-checkout-btn' data-btn-text='".__( $wc_bsecure_title, "wc-bsecure" )."' title='' style='outline-color: transparent;outline: none;'>

                            <span class='bsecure-loader-outer ".$outerClass."'>

                                <span class='bsecure-loader-span'></span>
                                
                                    <img src='".$btnUrl."' alt='".__( $wc_bsecure_title, 'wc-bsecure' )."' class='".$imgClass."'>                              
                            </span>

                            </a>";
        }

        return $btnHtml;
    }

    public function getCountryCallingCodeFromPhone($phone, $country_code){

        if (preg_match('/^\+\d+$/', $phone)){

            if(!empty($country_code)){

                 return $country_code;
            }

            return false;
        }

        return $country_code;
    }

    public static function getOptionValue($optionKey){

        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM ".$wpdb->base_prefix."options WHERE option_name = %s ", $optionKey ) );

                return  isset($row->option_value) ? $row->option_value : '';

    }

    public static function handleMultisites($settings,$section){

        if(is_multisite()){

            if(!is_main_site(get_current_blog_id())){               

                $wc_bsecure_is_active = WC_Bsecure::getOptionValue('wc_bsecure_is_active');
                $wc_bsecure_button_show_on_login = WC_Bsecure::getOptionValue('wc_bsecure_button_show_on_login');
                $wc_show_checkout_btn = WC_Bsecure::getOptionValue('wc_show_checkout_btn');
                $wc_bsecure_base_url = WC_Bsecure::getOptionValue('wc_bsecure_base_url');
                $wc_bsecure_store_id = WC_Bsecure::getOptionValue('wc_bsecure_store_id');
                $wc_bsecure_client_id = WC_Bsecure::getOptionValue('wc_bsecure_client_id');
                $wc_bsecure_client_secret = WC_Bsecure::getOptionValue('wc_bsecure_client_secret');
                $wc_bsecure_payment_gateway = WC_Bsecure::getOptionValue('wc_bsecure_payment_gateway');
                $wc_auto_append_country_code = WC_Bsecure::getOptionValue('wc_auto_append_country_code');

                if ($section == 'bsecure-setting') {

                    if(empty(get_option('wc_bsecure_is_active'))){

                        $settings['is_enable']['value'] =  $wc_bsecure_is_active;
                    }

                    if(empty(get_option('wc_bsecure_button_show_on_login'))){

                        $settings['login_button']['value'] =  $wc_bsecure_button_show_on_login;
                    }

                    if(empty(get_option('wc_show_checkout_btn'))){

                       $settings['show_checkout_btn']['value'] =  $wc_show_checkout_btn;
                    }

                    if(empty(get_option('wc_bsecure_base_url'))){

                       $settings['base_url']['value'] =  $wc_bsecure_base_url;
                    }                    

                    $settings['store_id']['custom_attributes'] =  ['required' => 'required'];

                    if(empty(get_option('wc_bsecure_client_id'))){

                       $settings['base_url']['value'] =  $wc_bsecure_client_id;
                    }

                    if(empty(get_option('wc_bsecure_client_secret'))){

                       $settings['base_url']['value'] =  $wc_bsecure_client_secret;

                    }                   

                }else{

                    if(empty(get_option('wc_bsecure_payment_gateway'))){

                        $settings['is_enable']['value'] =  $wc_bsecure_payment_gateway;

                    }

                    if(empty(get_option('wc_auto_append_country_code'))){

                        $settings['auto_append_country_code']['value'] =  $wc_auto_append_country_code;

                    } 
                } 
            }
        }

        return $settings; 
    }

    public function basicAuth(){

        $creds = array();
        $headers = getallheaders();       

        // Get username and password from the submitted headers.
        if ( (array_key_exists( 'Username', $headers ) && array_key_exists( 'Password', $headers )) || (array_key_exists( 'username', $headers ) && array_key_exists( 'password', $headers )) ) {

            $creds['user_login'] = !empty($headers["Username"]) ? $headers["Username"] : $headers["username"];
            $creds['user_password'] = !empty($headers["Password"]) ? $headers["Password"] : $headers["password"];
            $creds['remember'] = false;            

            $user = wp_signon( $creds, false );  // Verify the user. 

            // message reveals if the username or password are correct.
            if ( is_wp_error($user) ) {                

                return ['error_code' => 'invalid-method', 'message' => $user->get_error_message(), 'status' => 400]; 

                return $user; 
            }            

            wp_set_current_user( $user->ID, $user->user_login );             

            return ['error_code' => 'success', 'message' => __('Authorized successfully'), 'status' => 200]; 

        } else {

            return ['error_code' => 'invalid-method', 'message' => __('You must specify a valid username and password.'), 'status' => 400]; 
        }

    }

    public function getApiHeaders($accessToken){

        $headers =  ['Authorization' => 'Bearer '.$accessToken];
        return $headers;
    }

    public function getQisstPayText(){

        $bsecure_show_qisstpay = get_option("wc_bsecure_show_qisstpay","");
        $returnHtml = "";
        $cart_total_amount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );
        $cart_totals =   WC()->cart->get_totals();
        $amount_to_charge =   !empty($cart_totals['total']) ? floatval($cart_totals['total']) : $cart_total_amount;

        if($bsecure_show_qisstpay == 'yes'){                       

            if(!empty($amount_to_charge) && $amount_to_charge <= WC_Bsecure::QISSTPAY_AMOUNT_MAX_LIMIT && $amount_to_charge >= WC_Bsecure::QISSTPAY_AMOUNT_MIN_LIMIT){

                $perMonthAmount = $amount_to_charge / WC_Bsecure::QISSTPAY_PER_MONTH;

                $qisstPayTxt = __('As low as ','wc-bsecure');
                $qisstPayTxt .= '<span class="qisstpay-price-text">'.wc_price($perMonthAmount).'</span>'; 
                $qisstPayTxt .= '/mo ' . __(' for ','wc-bsecure') . WC_Bsecure::QISSTPAY_PER_MONTH . __(' months','wc-bsecure');               

                $returnHtml = '<div class="bsecure-qisstpay-wrapper"><div class="firstWrapper"><a href="javascript:;" class="bsecure-qisstpay-txt bsecure-checkout-button" >'.$qisstPayTxt.'</a>';

                $returnHtml .= '<a href="javascript:;" class="bsecure-qisstpay-learn-more" id="qisstpay-modal-btn">'.__(' Learn more','wc-bsecure').'</a></div>';

                $returnHtml .= '<div class="footerQistPayLogoBox">
                                    <span>' . __('Just select', 'wc-bsecure') . '</span>
                                     <img src="' . plugin_dir_url( __FILE__ ) . '../assets/images/qpLogo-1.png" alt="" />
                                     <img src="'. plugin_dir_url( __FILE__ ) . '../assets/images/qisst-pay.png" alt="" />
                                    <span> ' . __('at checkout', 'wc-bsecure') . '</span> 
                                  </div></div>';
            }           

        }

        return $returnHtml;

    }

    /*
    * Clean multidimentional array from html tags
    */
    public function strip_html_tags_deep($value)
    {
      return is_array($value) ?
        array_map($this->strip_html_tags_deep, $value) :
        htmlspecialchars($value);
    }


    public function cleanProductsArray($product_objs){

        if(!empty($product_objs)){
            
            foreach($product_objs as $keys => $products){

                if(!empty($products)){
                    
                    foreach($products as $key => $value){

                        $product_objs[$keys][$key] = $this->strip_html_tags_deep($value);

                    }   
                }            
            } 
        }

        return $product_objs;
    }


    public function sendLogsToBsecureServer($request_data = []){
        
        $base_url = get_option('wc_bsecure_base_url', ''); 
        $webhook_log_endpoint = '/webhook/pim/log-error';
        $webhook_log_url = $base_url.$webhook_log_endpoint; 

        $params =   [

                'method' => 'POST',
                'body' => json_encode($request_data),
                'headers' =>   array( "Content-type" => "application/json" )

            ];

        $this->bsecureSendCurlRequest($webhook_log_url,$params);
    }

}