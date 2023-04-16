<?php $tag_id = 'testimonial_' .rand().time(); 
	$args = array(
		'post_type' => 'testimonial',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	);
	$query = new WP_Query($args);
	$count = $query->post_count;
	$j=0;
?>
<?php if($query->have_posts()):?>
<div class="bwp-testimonial layout3">
	<div class="block">
		<div class="block_content">
			<div id="<?php echo esc_attr($tag_id); ?>" class="slick-carousel " data-slidesToScroll="true" data-nav="<?php echo esc_attr($show_nav);?>" data-dots="<?php echo esc_attr($show_pag);?>" data-columns4="<?php echo $columns4; ?>" data-columns3="<?php echo $columns3; ?>" data-columns2="<?php echo $columns2; ?>" data-columns1="<?php echo $columns1; ?>" data-columns="<?php echo $columns; ?>">
				<?php while($query->have_posts()):$query->the_post(); ?>
					<?php $testimonial_job  = get_post_meta( get_the_ID(), 'testimonial_job',true) ? get_post_meta( get_the_ID(), 'testimonial_job',true) : ''; ?>
					<?php $testimonial_rating  = get_post_meta( get_the_ID(), 'testimonial_rating',true) ? get_post_meta( get_the_ID(), 'testimonial_rating',true) : ''; ?>
					<?php	if( ( $j % $item_row ) == 0 ) { ?>
						<div class="testimonial-content">
					<?php } ?>
						<div class="item">
							<div class="testimonial-image">
								<?php the_post_thumbnail( 'thumbnail' ); ?>
							</div>
							<div class="testimonial-info">
								<h2 class="testimonial-customer-name"><?php the_title(); ?></h2>
								<div class="rating"><div class="rating-<?php echo $testimonial_rating ?> rating-content"></div></div>
								<?php if($testimonial_job): ?>	
									<div class="testimonial-job"><?php echo esc_html($testimonial_job); ?></div>
								<?php endif; ?>
								<div class="testimonial-item">
									<?php echo wpbingo_get_excerpt( $length, false ); ?>
								</div>
							</div>
						</div>
						<!-- Wrapper for slides -->
					<?php if(( $j+1 ) % $item_row == 0 || ( $j+1 ) == $count ){?> 
					</div>
					<?php  } ?>
				<?php $j++; endwhile; ?>
			</div>
		</div>
 	</div>
</div>
<?php endif;?>