<?php
/**
 * The file that contains bsecure sign in feature.
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
 * bsecure sign in features
 *
 * @since      1.0.1
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 * @author     bSecure <info@bsecure.pk>
 */


class Sign_In_With_Bsecure extends WC_Bsecure {	


	private $access_token;
	private $get_customer_endpoint;
	private $user;
	private $sso_endpoint;
	private $auth_code;
	private $state;


	public function __construct(){

		if ( get_option( 'wc_bsecure_button_show_on_login' ) == 'yes' ) {
			//Removed by KHL asked by Miesam 11-Aug-2020 //
			//add_action( 'login_form', array($this, 'add_signin_button' ));
	        add_action( 'woocommerce_login_form', array($this, 'add_signin_button' ));

	        if(isset($_GET['show_notice'])){
	        	add_action( 'wp_loaded', array($this, 'show_message' ), 10);
	        }

	        // Handle bScecure's response before anything is rendered.
			if ( isset( $_GET['state'] ) && isset( $_GET['code'] ) ) {
				
				add_action( 'init', array($this, 'authenticate_user' ));
			}

		}


		if ( isset( $_GET['bsecure_redirect'] ) ) {
			add_action( 'template_redirect', array($this, 'bsecure_auth_redirect' ) );
		}

		$this->get_customer_endpoint = '/sso/customer/profile';

        $this->base_url = get_option('wc_bsecure_base_url');   

	}

	public function show_message(){

		if(!empty($_GET['show_notice'])){

			$notice_type = !empty($_GET['notice_type']) ? sanitize_text_field($_GET['notice_type']) : 'error';
			wc_add_notice(sanitize_text_field($_GET['show_notice']),$notice_type);
		}
		
	}


	/**
	 * Adds the sign-in button to the login form.
	 */
	public function add_signin_button() {

		// Keep existing url query string intact.
		$url = site_url( '?bsecure_redirect&' ) . $_SERVER['QUERY_STRING'];

		if ( get_option( 'wc_bsecure_button_show_on_login' ) == 'yes' ) {

			ob_start();
			
			?>
				<a id="btn-bsecure-new" href="<?php echo esc_url($url); ?>" class="bsecure-login-button" >
                    <img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/images/login-with-bsecure.jpg' ?>" class="btn-bsecure" style="width: 100%; margin:5px 0 5px 0;"  alt="<?php echo __("Login with bSecure","wc-bsecure");?>">
                </a>
			<?php
			echo ob_get_clean();

		}

	}

    

	/**
	 * Uses the code response from bSecure to authenticate the user.
	 *
	 * @since 1.0.0
	 */
	public function authenticate_user() {	

		$this->auth_code = sanitize_text_field($_GET['code']);	
		$this->state = sanitize_text_field($_GET['state']);

		$accessCode = $this->set_access_token($this->auth_code);		

		if(!$accessCode){
			
			$this->redirectToMyAccountPage(__('Authorization code expired.','wc-bsecure'));
		}


		$this->validateState($this->state);

		$this->set_user_info($this->auth_code);

		// If the user is logged in, just connect the authenticated bSecure account.
		if ( is_user_logged_in() ) {

			$this->user = wp_get_current_user();
			// link the account.
			$this->connect_account( $this->user->email );
			$this->update_activity_wc_customer_lookup($this->user->ID);
			// redirect back to the profile edit page.
			wp_redirect( get_permalink( get_option('woocommerce_myaccount_page_id')));
			exit;
		}	

		// Check if a user is linked to this bSecure account.
		if(!empty($this->user->email)){
			$linked_user = get_users(
				array(
					'meta_key'   => 'wc_bsecure_user_account_email',
					'meta_value' => sanitize_email($this->user->email),
				)
			);
		}

		// If user is linked to bSecure account, sign them in. Otherwise, create the user if necessary.
		if ( ! empty( $linked_user ) ) {

			$user = $this->find_by_email_or_create( $this->user );
			$user = $linked_user[0];		
			$this->login_user($user->ID, $user->user_login);
			update_user_meta( $user->ID, 'wc_bsecure_user_account_email',sanitize_email($this->user->email) );
			update_user_meta( $user->ID, 'wc_bsecure_access_token', $this->access_token );
			update_user_meta( $user->ID, 'wc_bsecure_auth_code', $this->auth_code );
			do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore

		} else {			

			$user = $this->find_by_email_or_create( $this->user );

			// Log in the user.
			if ( !empty($user) ) {				
				$this->login_user($user->ID, $user->user_login);
				update_user_meta( $user->ID, 'wc_bsecure_user_account_email', sanitize_email($this->user->email) );				
				update_user_meta( $user->ID, 'wc_bsecure_access_token', $this->access_token );
				update_user_meta( $user->ID, 'wc_bsecure_auth_code', $this->auth_code );				
				do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore
			}
		}

		if ( isset( $state->redirect_to ) && '' !== $state->redirect_to ) {
			$redirect = $state->redirect_to;
		} else {
			$redirect = get_permalink( get_option('woocommerce_myaccount_page_id') ); // Send users to the dashboard by default.
		}

		$redirect = apply_filters( 'bsecure_auth_redirect', $redirect ); // Allow the redirect to be adjusted.

		wp_redirect( $redirect );
		exit;

	}



	public function validateState($state){

		$wc_bsecure_client_secret = get_option( 'wc_bsecure_client_secret' );
		$state 	= base64_decode($state);

		if(!wp_verify_nonce( $state, "state-".$wc_bsecure_client_secret )){

			$this->redirectToMyAccountPage(__("Access Forbidden: Invalid login request found. Please try again later.","wc-bsecure"));

		}

		return true;

	}


	/**
	 * Gets a user by email or creates a new user.
	 *
	 * @since 1.0.0
	 * @param object $user_data  The bSecure user data object.
	 */
	protected function find_by_email_or_create( $user_data ) {

		$user_pass       = wp_generate_password( 12 );
		$user_email      = sanitize_email($user_data->email);
		$user_email_data = explode( '@', $user_email );
		$user_login      = $this->my_unique_user_slug($user_email_data[0]);
		$first_name      = $this->get_first_name_or_last_name($user_data->name);
		$last_name       = $this->get_first_name_or_last_name($user_data->name,'last_name');
		$last_name       = '';
		$display_name    = $first_name;
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

		$addressInfo = [];
		
		// check address
		if(!empty($user_data->address)){			

				$country_calling_code = WC()->countries->get_country_calling_code( $user_data->address->country );

				$country_code = $user_data->country_code;		

			$addressInfo = [
							'first_name' 	=> $this->get_first_name_or_last_name($user_data->name),
							'last_name' 	=> $this->get_first_name_or_last_name($user_data->name,'last_name'),
							'country_code' 	=> $country_code,
							'address_1' 	=> $user_data->address->address,
							'address_2' 	=> '',
							'city' 			=> $user_data->address->city,
							'postcode' 		=> !empty($user_data->address->postcode) ? $user_data->address->postcode : '',
							'country' 		=> $this->get_country_code_by_country_name($user_data->address->country),
							'state' 		=> $this->get_state_code($user_data->address->country, $user_data->address->state),
							'phone' 		=> $user_data->phone_number
							];
		}

		$user_found = get_user_by( 'email', sanitize_email($user_data->email) );

		if ( false !== $user_found ) {

			update_user_meta( $user_found->ID, 'first_name', $this->get_first_name_or_last_name($user_data->name));
			update_user_meta( $user_found->ID, 'last_name', $this->get_first_name_or_last_name($user_data->name,'last_name') ); 
			$user['user_id'] = $user_found->ID;
			$this->add_wc_customer_lookup($user);
			if(!empty($addressInfo)){
				$this->addUpdateAddress($addressInfo, $user_found->ID);
			}
			return $user_found;
		}

		

		$new_user_id = wp_insert_user( $user );

		if ( is_wp_error( $new_user_id ) ) {

			error_log( $new_user_id->get_error_message() );
			die( $new_user_id->get_error_message() );
			//return false;

		} else {

			$user['user_id'] = $new_user_id;			
			$this->add_wc_customer_lookup($user);
			if(!empty($addressInfo)){
				$this->addUpdateAddress($addressInfo, $new_user_id);
			}
			return get_user_by( 'id', $new_user_id );
		}

	}

	
	

	/**
	 * Sets the access_token using the response code.
	 *
	 * @since 1.0.0
	 * @param string $code The code provided by bSecure redirect.
	 *
	 * @return mixed Access token on success or WP_Error.
	 */
	protected function set_access_token( $code = '' ) {

		if ( ! $code ) {

			$this->redirectToMyAccountPage(__('No authorization code provided.','wc-bsecure'));
		}

		$response = $this->bsecureGetOauthToken();	

		$validateResponse = $this->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			$this->redirectToMyAccountPage('Response Error: '.$validateResponse['msg']);

		}else if(!empty($response->access_token)){

			$this->access_token = $response->access_token;
		}

		// Sanitize auth code.
		$code = sanitize_text_field( $code );
		
		$url = $this->base_url.$this->get_customer_endpoint;

		//$headers =	'Authorization: Bearer '.$this->access_token;
		$headers =   $this->getApiHeaders($this->access_token);

		$args = [
			'sslverify' => false,
			'method' => 'POST',
			'body' => ['code' => $code],
			'headers' 	=> $headers
		];

		$response = $this->bsecureSendCurlRequest($url, $args);	

		$validateResponse = $this->validateResponse($response);	

		if($validateResponse['error']){
			
			//wc_add_notice( 'Response Error: '.$validateResponse['msg'], 'error' );		
			$this->redirectToMyAccountPage('Response Error: '.$validateResponse['msg']);

		}else{

			return $response->body;
		}

		return false;
	}


	public function redirectToMyAccountPage($msg,$type='error'){

		$query_args = ['show_notice' => $msg,'notice_type' => $type];

		wp_redirect(add_query_arg($query_args, get_permalink( get_option('woocommerce_myaccount_page_id'))));
		exit;
	}


	/**
	 * Sets the user's information.
	 *
	 * @since 1.2.0
	 */
	protected function set_user_info($code) {
		$this->user = $this->get_user_by_token($code);
	}



	/**
	 * Add usermeta for current user and bSecure account email.
	 *
	 * @since 1.0.0
	 * @param string $email The users authenticated bSecure account email.
	 */
	protected function connect_account( $email = '' ) {

		if ( ! $email ) {
			return false;
		}

		$current_user = wp_get_current_user();		

		if ( ! ( $current_user instanceof WP_User ) ) {
			return false;
		}

		return add_user_meta( $current_user->ID, 'wc_bsecure_user_account_email',  sanitize_email($email), true );
	}

	/**
	 * Get the user's info.
	 *
	 * @since 1.2.0
	 */
	protected function get_user_by_token($code) {

		//$headers =	'Authorization: Bearer '.$this->access_token;
		$headers =   $this->getApiHeaders($this->access_token);

		$args = [
			'method' 	=> 	'POST',
			'body'		=>	['code' => $code],
			'headers' 	=> 	$headers
		];


		$response = $this->bsecureSendCurlRequest( $this->base_url.$this->get_customer_endpoint, $args );

		

		$validateResponse = $this->validateResponse($response);

		if($validateResponse['error']){

			$this->redirectToMyAccountPage('Response Error: '.$validateResponse['msg']);
		}

		return ( !empty($response->body) ) ? $response->body : false;
	}



	/**
	 * Redirect the user to get authenticated by bSecure.
	 *
	 * @since    1.0.0
	 */
	public function bsecure_auth_redirect() {

		if ( is_user_logged_in() ) {
			// redirect back to the profile edit page.
			wp_redirect( get_permalink( get_option('woocommerce_myaccount_page_id')));
			exit;
		}		

		$url = $this->build_bsecure_redirect_url();
		wp_redirect( $url );
		exit;
	}




	/**
	 * Builds out the bSecure redirect URL
	 *
	 * @since    1.0.0
	 */
	public function build_bsecure_redirect_url() {

		// Build the API redirect url.		
		$client_id 		= get_option( 'wc_bsecure_client_id' );
		$store_id = get_option('wc_bsecure_store_id', '');
		$client_id = !empty($store_id) ? $client_id.':'.sanitize_text_field($store_id) : $client_id;
		$wc_bsecure_client_secret 		= get_option( 'wc_bsecure_client_secret' );
		$config = $this->getBsecureConfig();  	      	
	    $this->sso_endpoint = !empty($config->ssoLogin) ? $config->ssoLogin : "/";		
		
		$response_type  = 'code';
		$this->state  		= wp_create_nonce( 'state-' . $wc_bsecure_client_secret );
		$this->state  		= base64_encode($this->state);
		$scope  		= 'profile';
		$redirect_uri 	= urlencode( site_url() );		

		return $this->sso_endpoint . '?scope=' . $scope . '&response_type=' . $response_type . '&client_id=' . $client_id . '&state=' . $this->state;
	}


}