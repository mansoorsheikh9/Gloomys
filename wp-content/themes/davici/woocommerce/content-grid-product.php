<?php global $product, $woocommerce_loop, $post; 
$davici_settings = davici_global_settings();
if(isset($davici_settings['product-countdown']) && $davici_settings['product-countdown'] ){
	add_action('woocommerce_before_shop_loop_item_title', 'davici_add_countdownt_item', 15 );
}
remove_action('woocommerce_after_shop_loop_item', 'davici_quickview', 35 );
?>
<div class="products-entry clearfix product-wapper">
	<div class="products-thumb">
		<?php
			/**
			 * woocommerce_before_shop_loop_item_title hook
			 *
			 * @hooked woocommerce_show_product_loop_sale_flash - 10
			 * @hooked woocommerce_template_loop_product_thumbnail - 10
			 */
			do_action( 'woocommerce_before_shop_loop_item_title' );
		?>
		<?php if(isset($davici_settings['product_quickview']) && $davici_settings['product_quickview'] ){ 
			davici_quickview();	
		} ?>
		<div class='product-button'>
			<?php do_action('woocommerce_after_shop_loop_item'); ?>
		</div>
	</div>
	<div class="products-content">
		<div class="contents">
			<?php woocommerce_template_loop_rating(); ?>
			<h3 class="product-title"><a href="<?php esc_url(the_permalink()); ?>"><?php esc_html(the_title()); ?></a></h3>
			<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
		</div>
	</div>
</div>