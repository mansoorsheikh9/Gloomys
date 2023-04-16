<?php
/**
 * The template for displaying error messages 
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package bSecure
 */

get_header(); ?>
<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found">
				<header class="page-header">
					<h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'wc-bsecure' ); ?></h1>
				</header>
				<div class="page-content">
					<p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'wc-bsecure' ); ?></p>

				</div>
			</section>
		</main>
	</div>
</div><!-- .wrap -->
<?php
get_footer();
