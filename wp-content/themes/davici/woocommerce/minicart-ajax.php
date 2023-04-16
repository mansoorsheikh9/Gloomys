<?php 
if ( !class_exists('Woocommerce') ) { 
	return false;
}
$davici_settings = davici_global_settings();
$cart_style = davici_get_config('cart-style','dropdown');
global $woocommerce; ?>
<div class="dropdown mini-cart top-cart">
	<?php if($cart_style == "popup"){ ?>
	<div class="remove-cart-shadow"></div>
	<?php } ?>
	<a class="dropdown-toggle cart-icon" data-toggle="dropdown" data-hover="dropdown" data-delay="0" href="#" title="<?php esc_attr_e('View your shopping cart', 'davici'); ?>">
		<span class="icons-cart"><i class="icon-bag"></i><span class="cart-count"><?php echo esc_attr($woocommerce->cart->cart_contents_count); ?></span></span>
    </a>
	<div class="cart-popup <?php echo esc_attr($cart_style); ?>">
		<?php if($cart_style=="popup"){ ?>
		<div class="remove-cart">
			<a class="dropdown-toggle cart-remove" data-toggle="dropdown" data-hover="dropdown" data-delay="0" href="#" title="<?php esc_attr_e('View your shopping cart', 'davici'); ?>">
				<?php echo esc_html__("close","davici") ?><i class="icon_close"></i>
			</a>
		</div>
		<?php } ?>
		<?php woocommerce_mini_cart(); ?>
	</div>
</div>