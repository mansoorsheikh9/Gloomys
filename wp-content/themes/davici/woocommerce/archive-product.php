<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header();
do_action( 'woocommerce_before_main_content' );
$category_style  = davici_get_config('category_style','sidebar');
$show_banner	 = davici_get_config('show-banner-category',false);
$show_bestseller	 = davici_get_config('show-bestseller-category',false);
$limit_bestseller	= davici_get_config('bestseller_limit',9);
$product_col_large	= davici_get_config('product_col_large',4);	
$product_col_medium = davici_get_config('product_col_medium',3);
$product_col_sm 	= davici_get_config('product_col_sm',1);
$product_col_xs 	= davici_get_config('product_col_xs',1);
$current_category = get_queried_object();
$product_visibility_term_ids = wc_get_product_visibility_term_ids();
if( isset($current_category->term_id) && $current_category->term_id){
	$banner = get_term_meta( $current_category->term_id, 'category_banner', true );
	$title = get_term_meta( $current_category->term_id, 'category_title_banner', true );
	$subtitle = get_term_meta( $current_category->term_id, 'category_subtitle_banner', true );
	$button = get_term_meta( $current_category->term_id, 'category_button_banner', true );
	$link_button = get_term_meta( $current_category->term_id, 'category_link_button', true );
	$args = array(
		'post_type'				=> 'product',
		'post_status' 			=> 'publish',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' 		=> $limit_bestseller,
		'tax_query'	=> array(
			array(
				'taxonomy'	=> 'product_cat',
				'field'		=> 'slug',
				'terms'		=> $current_category->slug
			),
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['featured'],
			)						
		)
	);	
}else{
	$args = array(
		'post_type'				=> 'product',
		'post_status' 			=> 'publish',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' 		=> $limit_bestseller,
		'tax_query'	=> array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['featured'],
			)						
		)
	);	
}
$list = new \WP_Query( $args );
?>
<div class="<?php echo esc_attr($category_style); ?>">
	<?php if( function_exists('is_shop') && is_shop() && $category_style == 'only_categories' && apply_filters( 'davici_custom_category', $html = '' ) ): ?>
	<div class="container">
		<div class="main-archive-product">
			<ul class="woocommerce-product-category row">
				<?php davici_woocommerce_output_product_categories(); ?>
			</ul>
		</div>
	</div>
	<?php else: ?>
		<div class="container">
			<div class="main-archive-product row">
				<?php if(( ($category_style == 'sidebar') || ($category_style == 'filter_drawer') ) && is_active_sidebar('sidebar-product')): ?>	
					<div class="bwp-sidebar sidebar-product <?php echo esc_attr(davici_get_class()->class_sidebar_left); ?>">
						<?php if($category_style == 'filter_drawer'): ?>
							<div class="button-filter-toggle hidden-lg hidden-md">
								<?php echo esc_html__("Hide Filter","davici") ?>
							</div>
						<?php endif; ?>
						<?php if ( ( class_exists("WCV_Vendors") && WCV_Vendors::is_vendor_page() ) || is_tax('dc_vendor_shop') ) { ?>
							<?php dynamic_sidebar( 'sidebar-vendor' ); ?>
						<?php }else{ ?>	
							<?php dynamic_sidebar( 'sidebar-product' ); ?>
						<?php } ?>
					</div>				
				<?php endif; ?>
				<div class="<?php echo esc_attr(davici_get_class()->class_product_content); ?>" >
					<?php do_action( 'woocommerce_archive_description' ); ?>
					<?php if ( have_posts() ) : ?>
						<div class="bwp-top-bar top clearfix">				
							<?php davici_category_top_bar(); ?>							
						</div>
						<?php if($category_style != 'sidebar' && $category_style != 'filter_drawer' && is_active_sidebar('filter-product')): ?>
							<div class="bwp-sidebar sidebar-product-filter full">
								<?php if($category_style == 'filter_offcanvas'): ?>
									<div class="button-filter-toggle">
										<?php echo esc_html__("Hide Filter","davici") ?>
									</div>
								<?php endif; ?>
								<?php dynamic_sidebar( 'filter-product' ); ?>
							</div>
						<?php endif; ?>
						<?php if( isset($banner) && !empty($banner) && $show_banner ) : ?>
							<div class="banner-shop">
								<div class="item-thumbnail">
									<img src="<?php echo esc_url($banner); ?>" alt="<?php echo esc_html__("banner category","davici");?>" />
								</div>
								<div class="content">
									<?php if( isset($subtitle) && !empty($subtitle) ) : ?>
										<div class="subtitle"><?php echo esc_html($subtitle); ?></div>
									<?php endif; ?>
									<?php if( isset($title) && !empty($title) ) : ?>
										<h2 class="title"><?php echo esc_html($title); ?></h2>
									<?php endif; ?>
									<?php if( isset($button) && !empty($button) &&  isset($link_button) && !empty($link_button) ) : ?>
									<div class="button">
										<a href="<?php echo esc_url($link_button) ?>"><?php echo esc_html($button); ?></a>
									</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
						<?php if( $show_bestseller ) : ?>
							<div class="bestseller-product">
								<h2 class="title-bestseller"><?php echo esc_html__("Best Seller","davici") ?></h2>
								<div class="slick-carousel products-list grid" data-slidestoscroll="true" data-dots="true" data-columns4="1" data-columns3="1" data-columns2="<?php echo esc_attr($product_col_sm); ?>" data-columns1="<?php echo esc_attr($product_col_medium); ?>" data-columns="<?php echo esc_attr($product_col_large); ?>">	
								<?php while($list->have_posts()): $list->the_post();global $product, $post, $wpdb, $average; ?>
									<div class="item">
										<?php wc_get_template_part( 'content-grid', 'product' ); ?>
									</div>
								<?php endwhile; wp_reset_postdata(); ?>
								</div>
							</div>
						<?php endif; ?>
						<?php woocommerce_product_loop_start(); ?>
							<?php while ( have_posts() ) : the_post(); ?>
								<?php wc_get_template_part( 'content', 'product' ); ?>
							<?php endwhile;  ?>
						<?php woocommerce_product_loop_end(); ?>
						<div class="bwp-top-bar bottom clearfix">
							<?php do_action('woocommerce_after_shop_loop'); ?>
						</div>
					<?php else : ?>
						<?php wc_get_template( 'loop/no-products-found.php' ); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>	
	<?php endif; ?>
</div>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>