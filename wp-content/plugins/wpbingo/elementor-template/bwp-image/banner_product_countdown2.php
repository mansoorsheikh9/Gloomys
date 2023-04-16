<div class="bwp-widget-banner <?php echo esc_html( $layout ); ?>">
	<div class="bg-banner">
		<?php  if($product_id && $product = wc_get_product( $product_id )):
			$symboy = get_woocommerce_currency_symbol( get_woocommerce_currency() );
			$attributes = $product->get_attributes();			
		?>
		<div class="content-products">
			<div class="products-content">
				<div class="content">
					<?php if( $title1) : ?>
					<h4 class="title-banner"><?php echo esc_html( $title1 ); ?></h4>
					<?php endif; ?>
					<h3 class="product-title"><a href="<?php echo get_permalink( $product_id );  ?>"><?php echo $product->get_title(); ?></a></h3>
					<div class="product-price"><?php echo $product->get_price_html(); ?></div>
					<?php if( $label) : ?>
						<div class="button-banner">
							<a class="btn-banner" href="<?php echo esc_url($link);?>"><?php echo esc_html( $label ); ?></a>
						</div>
					<?php endif; ?>
				</div>
				<div class="desc-countdown">
					<?php if( $description) : ?>
					<div class="desc-banner"><?php echo esc_html( $description ); ?></div>
					<?php endif; ?>
					<?php if( $time_deal) : ?>
					<div class="countdown-deal">
						<?php
							$start_time = time();
							$countdown_time = strtotime($time_deal);
							$date = bwp_timezone_offset( $countdown_time );
						?>
						<div class="product-countdown"  
							data-day="<?php echo esc_html__("Days","wpbingo"); ?>"
							data-hour="<?php echo esc_html__("Hours","wpbingo"); ?>"
							data-min="<?php echo esc_html__("Mins","wpbingo"); ?>"
							data-sec="<?php echo esc_html__("Secs","wpbingo"); ?>"	
							data-date="<?php echo esc_attr( $date ); ?>"  
							data-sttime="<?php echo esc_attr( $start_time ); ?>" 
							data-cdtime="<?php echo esc_attr( $countdown_time ); ?>" 
							data-id="<?php echo $widget_id; ?>">
						</div>
					</div>
					<?php endif;?>
				</div>
			</div>
		</div>
		<div class="content-image">
			<div class="image">
				<img src="<?php echo esc_url($image); ?>" alt="">
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
