<?php if ( (function_exists('is_shop') && is_shop()) ||  (function_exists('is_product_category') && is_product_category())) : ?>
	<?php if ( $wp_query->have_posts() ) : ?>
			<?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); ?>
				<li>
				<?php wc_get_template_part( 'content-'.esc_attr($category_view_mode), 'product' ); ?> 
				</li>
			<?php endwhile; // end of the loop. ?>
	<?php else : ?>

		<?php wc_get_template( 'loop/no-products-found.php' ); ?>

	<?php endif; ?>	
<?php endif; ?>