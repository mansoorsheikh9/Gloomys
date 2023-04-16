<?php $davici_settings = davici_global_settings(); ?>
<article id="post-<?php esc_attr(the_ID()); ?>" <?php post_class(); ?>>
	<div class="content-image-single <?php if ( get_the_post_thumbnail() ){ ?>image<?php }; ?>">
		<?php if ( get_the_post_thumbnail() ){ ?>
			<div class="entry-thumb single-thumb">
				<?php the_post_thumbnail( 'full' ); ?>
			</div>
		<?php }; ?>
		<div class="content-info">
			<?php if ( in_array( 'category', get_object_taxonomies( get_post_type() ) ) && davici_categorized_blog() ) : ?>
				<div class="cat-links"><?php echo get_the_category_list(''); ?></div>
			<?php endif; ?>	
			<?php
				$show_post_title = davici_get_config('post-title',true);
				if ($show_post_title){
					if ( is_single() ){
						the_title( '<h3 class="entry-title">', '</h3>' );
					}else {
						the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );
					}
				}
			?>
			<div class="entry-by entry-meta">
				<?php davici_single_posted_on_2(); ?>
			</div>
		</div>
	</div>
	<?php if ( is_search() ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php else : ?>
	<div class="post-content">
		<div class="post-excerpt clearfix">
			<?php
				/* translators: %s: Name of current post */
				the_content( sprintf(
					the_title( '<span class="screen-reader-text">', '</span>', false )
				) );
				wp_link_pages( array(
					'before'      => '<div class="page-links clearfix"><span class="page-links-title">' . esc_html__( 'Pages:', 'davici' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
				) );
			?>
		</div>
		<div class="clearfix"></div>
	</div><!-- .entry-content -->
	<div class="post-content-entry">
		<!-- Tag -->
		<?php
		if ( 'post' === get_post_type() ) {
			$tags_list = get_the_tag_list( '', esc_html_x( '', 'list item separator', 'davici' ) );
			if ( $tags_list ) {
				printf( '<div class="tags-links">' . esc_html__( ' %1$s', 'davici' ) . '</div>', $tags_list ); // WPCS: XSS OK.
			}
		}		
		?>
		<!-- Social Share -->
		<?php if ( shortcode_exists( 'social_share' ) ) : ?> 
			<div class="entry-social-share">
			<?php echo do_shortcode( "[social_share]" ); ?>	
			</div>
		<?php endif; ?>
	</div>
	<!-- Previous/next post navigation. -->
	<div class="clearfix"></div>
	<?php davici_entry_footer(); ?>	
	<?php endif; ?>
</article><!-- #post-## -->