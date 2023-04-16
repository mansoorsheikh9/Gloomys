<?php
/***** Active Plugin ********/
add_action( 'tgmpa_register', 'davici_register_required_plugins' );
function davici_register_required_plugins() {
    $plugins = array(
		array(
            'name'               => esc_html__('Woocommerce', 'davici'), 
            'slug'               => 'woocommerce', 
            'required'           => false
        ),
		array(
            'name'      		 => esc_html__('Elementor', 'davici'),
            'slug'     			 => 'elementor',
            'required' 			 => false
        ),		
		array(
            'name'               => esc_html__('Revolution Slider', 'davici'), 
			'slug'               => 'revslider',
			'source'             => get_template_directory() . '/plugins/revslider.zip', 
			'required'           => true, 
        ),
		array(
            'name'               => esc_html__('Wpbingo Core', 'davici'), 
            'slug'               => 'wpbingo', 
            'source'             => get_template_directory() . '/plugins/wpbingo.zip',
            'required'           => true, 
        ),			
		array(
            'name'               => esc_html__('Redux Framework', 'davici'), 
            'slug'               => 'redux-framework', 
            'required'           => false
        ),			
		array(
            'name'      		 => esc_html__('Contact Form 7', 'davici'),
            'slug'     			 => 'contact-form-7',
            'required' 			 => false
        ),	
		array(
            'name'     			 => esc_html__('WPC Smart Wishlist for WooCommerce', 'davici'),
            'slug'      		 => 'woo-smart-wishlist',
            'required' 			 => false
        ),
		array(
            'name'      		 => esc_html__('WPC Smart Compare for WooCommerce', 'davici'),
            'slug'      		 => 'woo-smart-compare',
            'required'			 => false
        ),		
		array(
            'name'     			 => esc_html__('WooCommerce Variation Swatches', 'davici'),
            'slug'      		 => 'variation-swatches-for-woocommerce',
            'required' 			 => false
        ),
    );
    $config = array();
    tgmpa( $plugins, $config );
}