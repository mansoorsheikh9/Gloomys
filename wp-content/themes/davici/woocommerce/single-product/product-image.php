<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.5.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if(davici_image_single_product()->product_layout_thumb == "scroll") 
	wc_get_template( 'single-product/content-image/scroll.php' );
elseif(davici_image_single_product()->product_layout_thumb == "sticky")
	wc_get_template( 'single-product/content-image/sticky.php' );
elseif(davici_image_single_product()->product_layout_thumb == "sticky2")
	wc_get_template( 'single-product/content-image/sticky2.php' );
elseif(davici_image_single_product()->product_layout_thumb == "slider")
	wc_get_template( 'single-product/content-image/slider.php' );
elseif(davici_image_single_product()->product_layout_thumb == "large_grid")
	wc_get_template( 'single-product/content-image/large_grid.php' );
elseif(davici_image_single_product()->product_layout_thumb == "small_grid")
	wc_get_template( 'single-product/content-image/small_grid.php' );
else
	wc_get_template( 'single-product/content-image/zoom.php' );