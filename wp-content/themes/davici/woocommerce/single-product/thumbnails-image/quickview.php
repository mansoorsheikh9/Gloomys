<?php
global $post, $woocommerce, $product;
$post_thumbnail_id = get_post_thumbnail_id( $post->ID );
$full_size_image   = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
$image_title       = get_post_field( 'post_excerpt', $post_thumbnail_id );
$placeholder       = has_post_thumbnail() ? 'with-images' : 'without-images';
$attachment_ids = $product->get_gallery_image_ids();
$data_product = $product->get_data();
$image_id = $data_product['image_id'] ? $data_product['image_id'] : array();
if($image_id )
	array_unshift ($attachment_ids,$image_id);
$columns = 	davici_image_single_product()->product_count_thumb;
remove_action( 'woocommerce_single_product_summary', 'davici_add_thumb_single_product', 40 );
remove_action( 'woocommerce_after_single_product', 'davici_sticky_cart', 50 );
?>
<div id="quickview-slick-carousel">
	<div class="col-sm-12">
		<div class="image-additional slick-carousel" data-draggable="true" data-asnavfor=".image-thumbnail" data-focusonselect="true" data-columns4="1" data-columns3="1" data-columns2="1" data-columns1="1" data-columns="1">
			<?php
				if ( $attachment_ids ) {
					foreach ( $attachment_ids as $attachment_id ) { ?>
						<div class="img-thumbnail">
						<?php $image_link = wp_get_attachment_url( $attachment_id );
						if ( ! $image_link )
							continue;
						$image_title 	= esc_attr( get_the_title( $attachment_id ) );
						$image_caption 	= esc_attr( get_post_field( 'post_excerpt', $attachment_id ) );
						$image       = wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_small_thumbnail_size', 'shop_single' ), 0, $attr = array(
							'title' => $image_title,
							'alt'   => $image_title,
							) );
						$image_class = "item-additional";
						echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', sprintf( '<div class="%s" title="%s">%s</div>',$image_class, $image_caption, $image ), $attachment_id, $post->ID, $image_class );
						?>
						</div>
					<?php }							
				}
			?>
		</div>
	</div>
	<div class="col-sm-12">
		<div class="image-thumbnail slick-carousel" data-asnavfor=".image-additional" data-centermode="true" data-focusonselect="true" data-columns4="<?php echo esc_attr($columns); ?>" data-columns3="<?php echo esc_attr($columns); ?>" data-columns2="<?php echo esc_attr($columns); ?>" data-columns1="<?php echo esc_attr($columns); ?>" data-columns="<?php echo esc_attr($columns); ?>" data-nav="true">
		<?php
			foreach ( $attachment_ids as $attachment_id ) {		
				$image_link = wp_get_attachment_url( $attachment_id );
				if ( ! $image_link )
					continue;
				$image_title 	= esc_attr( get_the_title( $attachment_id ) );
				$image_caption 	= esc_attr( get_post_field( 'post_excerpt', $attachment_id ) );
				$image       = wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_small_thumbnail_size', 'shop_catalog' ), 0, $attr = array(
					'title' => $image_title,
					'alt'   => $image_title
					) ); ?>
				<div class="img-thumbnail">
					<span class="img-thumbnail-scroll">
					<?php echo wp_kses($image,'social'); ?>
					</span>
				</div>
				<?php
			}
		?>
		</div>
	</div>
</div>