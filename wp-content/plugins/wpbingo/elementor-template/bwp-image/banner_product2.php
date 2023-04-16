<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="bwp-widget-banner <?php echo esc_html( $layout ); ?>">
	<div class="bg-banner">
		<?php  if($product_id && $product = wc_get_product( $product_id )):
			$symboy = get_woocommerce_currency_symbol( get_woocommerce_currency() );
			$attributes = $product->get_attributes();			
		?>
		<div class="image">
			<img src="<?php echo esc_url($image); ?>" alt="">
		</div>
		<div class="products-content">
			<div class="content">
				<h3 class="product-title"><a href="<?php echo get_permalink( $product_id );  ?>"><?php echo $product->get_title(); ?></a></h3>
				<div class="product-price"><?php echo $product->get_price_html(); ?></div>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>

