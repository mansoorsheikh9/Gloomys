
<?php
$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_woo_slider_'.rand().time();
$class_col_lg = ($columns == 5) ? '2-4'  : (12/$columns);
$class_col_md = ($columns1 == 5) ? '2-4'  : (12/$columns1);
$class_col_sm = ($columns2 == 5) ? '2-4'  : (12/$columns2);
$class_col_xs = ($columns3 == 5) ? '2-4'  : (12/$columns3);
$attributes = 'col-xl-'.$class_col_lg .' col-lg-'.$class_col_md .' col-md-'.$class_col_sm .' col-'.$class_col_xs;
$count = $list->post_count;
$j = 1;
do_action( 'before' ); 
if ( $list -> have_posts() ){ ?>
	<div id="<?php echo $widget_id; ?>" class="bwp_product_list <?php echo $widget_class; ?> <?php echo esc_attr($layout); ?> <?php echo esc_attr($class); ?> <?php if(empty($title1)) echo 'no-title'; ?>">
		<div class="content products-list grid row">	
		<?php while($list->have_posts()): $list->the_post(); global $product, $post, $wpdb, $average; ?>
				<?php	if( ($j == 1) ||  ( $j % 3  == 1 ) ||  ( $j % 3  == 0 ) ) { ?>
					<div class="item-product <?php echo $attributes ?> <?php if($j % 3  == 1) { ?>item-two<?php } ?>">
				<?php } ?>
					<div class="items">
						<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product.php'); ?>
					</div>
				<?php if( ($j == $count) || ($j % 3 == 0) ||  ( $j % 3  == 2 ) ){?> 
					</div><!-- #post-## -->
				<?php  } $j++;?>
		<?php endwhile; wp_reset_postdata(); ?>		
		</div>			
	</div>
	<?php
	}
?>