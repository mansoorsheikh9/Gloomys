<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	do_action( 'woocommerce_before_single_product' );
	if ( post_password_required() ) {
	 	echo get_the_password_form();
	 	return;
	}
	$davici_settings = davici_global_settings();
	$product_layout_thumb = davici_get_config("layout-thumbs","zoom");
	$show_extra_sidebar = davici_get_config("show-extra-sidebar",false);
	$show_featured_icon = davici_get_config("show-featured-icon",false);
	$show_background = davici_get_config("background",false);
	$show_trust_bages = davici_get_config('show-trust-bages',true);
?>
<div id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="bwp-single-product <?php if($show_background) { ?>background <?php } ?> <?php if($show_extra_sidebar) { ?>extra-sidebar <?php } ?><?php echo esc_attr(davici_image_single_product()->product_layout_thumb); ?>"
		data-product_layout_thumb 	= 	"<?php echo esc_attr(davici_image_single_product()->product_layout_thumb); ?>" 
		data-zoom_scroll 			=	"<?php echo esc_attr((isset($davici_settings['zoom-scroll']) && $davici_settings['zoom-scroll']) ? 'true' : 'false'); ?>" 
		data-zoom_contain_lens 		=	"<?php echo esc_attr((isset($davici_settings['zoom-contain-lens']) && $davici_settings['zoom-contain-lens']) ? 'true' : 'false'); ?>" 
		data-zoomtype 				=	"<?php echo esc_attr(( isset($davici_settings['zoom-type']) && $davici_settings['zoom-type']) ? ($davici_settings['zoom-type']) : 'inner'); ?>" 
		data-lenssize 				= 	"<?php echo esc_attr(isset($davici_settings['zoom-lens-size']) ? ($davici_settings['zoom-lens-size']) : '200'); ?>" 
		data-lensshape 				= 	"<?php echo esc_attr(isset($davici_settings['zoom-lens-shape']) ? ($davici_settings['zoom-lens-shape']) : 'zoom-lens-shape'); ?>" 
		data-lensborder 			= 	"<?php echo esc_attr(isset($davici_settings['zoom-lens-border']) ? ($davici_settings['zoom-lens-border']) : '10'); ?>"
		data-bordersize 			= 	"<?php echo esc_attr(isset($davici_settings['zoom-border']) ? ($davici_settings['zoom-border']) : '2'); ?>"
		data-bordercolour 			= 	"<?php echo esc_attr(isset($davici_settings['zoom-border-color']) ? ($davici_settings['zoom-border-color']) : '#252525'); ?>"
		data-popup 					= 	"<?php echo esc_attr(isset($davici_settings['product-image-popup'] ) && ($davici_settings['product-image-popup']) ? 'true' : 'false'); ?>">	
		<div class="row">
			<?php if($product_layout_thumb == "slider"): ?>
				<?php
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 0 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
				remove_action( 'woocommerce_single_product_summary', 'davici_trust_bages', 50 );
				remove_action( 'woocommerce_single_product_summary', 'davici_add_loop_wishlist_link', 35 );
				remove_action( 'woocommerce_single_product_summary', 'davici_add_loop_compare_link', 36 );
				?>
				<div class="bwp-single-image col-lg-12 col-md-12 col-12">
					<?php
						/**
						 * woocommerce_before_single_product_summary hooked
						 *
						 * @hooked woocommerce_show_product_sale_flash - 10
						 * @hooked woocommerce_show_product_images - 20
						 */
						do_action( 'woocommerce_before_single_product_summary' );
					?>
				</div>
				<div class="bwp-single-info col-lg-12 col-md-12 col-12 ">
					<div class="summary entry-summary entry-heading">
						<?php woocommerce_template_single_rating(); ?>
						<?php woocommerce_template_single_title(); ?>
						<?php woocommerce_template_single_price(); ?>
					</div>
					<div class="summary entry-summary entry-info">
					<?php
						/**
						 * woocommerce_single_product_summary hook
						 *
						 * @hooked woocommerce_template_single_title - 5
						 * @hooked woocommerce_template_single_rating - 10
						 * @hooked woocommerce_template_single_price - 10
						 * @hooked woocommerce_template_single_excerpt - 20
						 * @hooked woocommerce_template_single_add_to_cart - 30
						 * @hooked woocommerce_template_single_meta - 40
						 * @hooked woocommerce_template_single_sharing - 50
						 */
						do_action( 'woocommerce_single_product_summary' );
					?>
					</div><!-- .summary -->
					<div class="summary entry-summary entry-cart">
						<?php woocommerce_template_single_add_to_cart(); ?>
						<?php davici_add_loop_wishlist_link(); ?>
						<?php davici_add_loop_compare_link(); ?>
						<?php woocommerce_template_single_meta(); ?>
						<?php if($show_trust_bages){ davici_trust_bages(); } ?>
					</div>
				</div>
			<?php else: ?>
				<?php if($show_extra_sidebar): ?>
					<div class="col-xl-9 col-lg-9 col-md-12 col-12">
						<div class="row">
							<div class="bwp-single-image col-lg-6 col-md-12 col-12">
								<?php
									/**
									 * woocommerce_before_single_product_summary hooked
									 *
									 * @hooked woocommerce_show_product_sale_flash - 10
									 * @hooked woocommerce_show_product_images - 20
									 */
									do_action( 'woocommerce_before_single_product_summary' );
								?>
							</div>
							<div class="bwp-single-info col-lg-6 col-md-12 col-12 ">
								<div class="summary entry-summary">
								<?php
									/**
									 * woocommerce_single_product_summary hook
									 *
									 * @hooked woocommerce_template_single_title - 5
									 * @hooked woocommerce_template_single_rating - 10
									 * @hooked woocommerce_template_single_price - 10
									 * @hooked woocommerce_template_single_excerpt - 20
									 * @hooked woocommerce_template_single_add_to_cart - 30
									 * @hooked woocommerce_template_single_meta - 40
									 * @hooked woocommerce_template_single_sharing - 50
									 */
									do_action( 'woocommerce_single_product_summary' );
								?>
								</div><!-- .summary -->
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-12 col-12 sidebar-extra">
						<?php if(is_active_sidebar('extra-sidebar')){ ?>
							<div class="block-top-link">
								<?php dynamic_sidebar( 'extra-sidebar' ); ?>
							</div>
						<?php } ?>
					</div>
				<?php else: ?>
					<div class="bwp-single-image col-lg-7 col-md-12 col-12">
						<?php
							/**
							 * woocommerce_before_single_product_summary hooked
							 *
							 * @hooked woocommerce_show_product_sale_flash - 10
							 * @hooked woocommerce_show_product_images - 20
							 */
							do_action( 'woocommerce_before_single_product_summary' );
						?>
					</div>
					<div class="bwp-single-info col-lg-5 col-md-12 col-12 ">
						<div class="summary entry-summary">
						<?php
							/**
							 * woocommerce_single_product_summary hook
							 *
							 * @hooked woocommerce_template_single_title - 5
							 * @hooked woocommerce_template_single_rating - 10
							 * @hooked woocommerce_template_single_price - 10
							 * @hooked woocommerce_template_single_excerpt - 20
							 * @hooked woocommerce_template_single_add_to_cart - 30
							 * @hooked woocommerce_template_single_meta - 40
							 * @hooked woocommerce_template_single_sharing - 50
							 */
							do_action( 'woocommerce_single_product_summary' );
						?>
						</div><!-- .summary -->
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php if($show_featured_icon): ?>
		<div class="featured-icon">
			<?php if(is_active_sidebar('featured-icon')){ ?>
				<div class="block-top-link">
					<?php dynamic_sidebar( 'featured-icon' ); ?>
				</div>
			<?php } ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
		/**
		 * woocommerce_after_single_product_summary hook
		 *
		 * @hooked woocommerce_output_product_data_tabs - 10
		 * @hooked woocommerce_upsell_display - 15
		 * @hooked woocommerce_output_related_products - 20
		 */
		do_action( 'woocommerce_after_single_product_summary' );
	?>
	<meta itemprop="url" content="<?php esc_url(the_permalink()); ?>" />
</div><!-- #product-<?php the_ID(); ?> -->
<?php do_action( 'woocommerce_after_single_product' ); ?>