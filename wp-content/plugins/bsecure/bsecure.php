<?php
/*
 * Plugin Name: bSecure
 * Plugin URI: https://bsecure.pk
 * Description: The best way to checkout for your customers.
 * Author: bSecure
 * Author URI: https://bsecure.pk/
 * Version: 1.7.4
 *
 */

require plugin_dir_path( __FILE__ ) . 'includes/class-wc-bsecure.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

	

	function wc_besecure_run() {		

		$wc_bsecure = new WC_Bsecure;
		$wc_bsecure->init();

	}
	
	// from version 1.5.8 
	add_action( 'plugins_loaded',  'bsecure_check_wc'  );

	function bsecure_check_wc(){

		// check woocommerce is activate and installed
		if ( class_exists( 'WooCommerce' ) ) {
			wc_besecure_run();
		}
	}
	

	/**
	* Change Proceed To Checkout Text in WooCommerce
	**/
	$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no'); 
	$wc_show_checkout_btn = get_option('wc_show_checkout_btn', ''); 	

	if($wc_bsecure_is_active == 'yes'){

		if($wc_show_checkout_btn != 'bsecure_wc_only'){

			if ( !function_exists( 'woocommerce_button_proceed_to_checkout' ) ) { 

				function woocommerce_button_proceed_to_checkout() {

					$wc_show_checkout_btn = get_option('wc_show_checkout_btn', 'bsecure_only');				

					if($wc_show_checkout_btn == 'bsecure_only' || $wc_show_checkout_btn == 'bsecure_wc_both'){
							$wc_bsecure = new WC_Bsecure;
				 			echo $wc_bsecure->getBsecureBtnHtml();

				 			echo  $wc_bsecure->getQisstPayText();
				 			
					 	if($wc_show_checkout_btn == 'bsecure_wc_both'){

					 		wc_get_template( 'cart/proceed-to-checkout-button.php' );
					 	}
					}else{

						wc_get_template( 'cart/proceed-to-checkout-button.php' );
					}
				 	
				}

			}
		}
		
	}


	/**
	* Add setting lin at plugin page
	*/
	add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'bsecure_add_plugin_page_settings_link');

	function bsecure_add_plugin_page_settings_link( $links ) {

		$links = array_merge( array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=bsecure' ) ) . '">' . __( 'Settings', 'wc-bsecure' ) . '</a>'
		), $links );
		
		return $links;
	}

	


	/**
	 * Check bSecure woocommerce plugin requirements before activating //
	 */
	function wc_bscure_superess_activate() {

	    //check woocommerce plugin version
	    $woocommerce_ext = 'woocommerce/woocommerce.php';

	    // last version tested
	    $version_to_check = '4.3.5'; 

	    $woocommerce_error = false;

	    if(file_exists(WP_PLUGIN_DIR.'/'.$woocommerce_ext)){
	        $woocommerce_ext_data = get_plugin_data( WP_PLUGIN_DIR.'/'.$woocommerce_ext);
	        $woocommerce_error = !version_compare ( $woocommerce_ext_data['Version'], $version_to_check, '>=') ? true : false;
	    }   

	    $wc_bsecure = new WC_Bsecure;	    

	    if ( ! $wc_bsecure::wc_bsecure_check_ssl() ) {
	       echo '<div class="notice notice-error"><p><strong>bSecure WooCommerce</strong> '.__('plugin require ssl enabled to activate it at your domain.', 'wc-bsecure').' </p></div>'; 	 

	       @trigger_error(__('Please enable ssl to continue using this plugin.', 'wc-bsecure'), E_USER_ERROR);     
	    }


	    if ( $woocommerce_error ) {
	       echo '<div class="notice notice-error"><p><strong>bSecure WooCommerce</strong> '.__('plugin require the minimum WooCommerce plugin version of', 'wc-bsecure').' '.$version_to_check.' </p></div>'; 	 

	       @trigger_error(__('Please update woocommerce plugin to continue using this plugin.', 'wc-bsecure'), E_USER_ERROR);     
	    }

	    
	    if ( ! class_exists( 'WooCommerce' ) ) {

	    	$error_message = __('The bSecure plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'woocommerce');
	    	echo '<div class="notice notice-error"><p>'.$error_message.' </p></div>'; 
	       @trigger_error(__('bSecure plugin required Woocommerce plugin to be installed and activate.', 'wc-bsecure'), E_USER_ERROR); 
	    }

	    $bsecure_activated = get_option('bsecure_activated', 0);

		if ($bsecure_activated < 1){

		    if (class_exists('Bsecure_Admin')){

			    $bsecure_admin = new Bsecure_Admin;
			    $bsecure_admin->plugin_activate_deactivate();
			}
		}

	}


	function wc_bscure_superess_decactivate(){

		$bsecure_activated = get_option('bsecure_activated');

		if ($bsecure_activated == 1){

			if (class_exists('Bsecure_Admin')){

			    $bsecure_admin = new Bsecure_Admin;
			    $bsecure_admin->plugin_activate_deactivate('deactivate');
			}
		}

		
	}

	register_activation_hook(__FILE__, 'wc_bscure_superess_activate');
	register_deactivation_hook(__FILE__, 'wc_bscure_superess_decactivate');