<?php 
	get_header(); 
	$davici_settings = davici_global_settings();
	$background = davici_get_config('background');
	$bgs 			= 	( isset($davici_settings['img-404']['url']) && $davici_settings['img-404']['url'] ) ? $davici_settings['img-404']['url'] : get_template_directory_uri() . '/images/image_404.jpg';
	$title_error 	=	( isset($davici_settings['title-error']) && $davici_settings['title-error'] ) ? $davici_settings['title-error'] : esc_html__('404', 'davici');
	$sub_title 		=	( isset($davici_settings['sub-title']) && $davici_settings['sub-title'] ) ? $davici_settings['sub-title'] : esc_html__("Oops! That page can't be found.", "davici");
	$sub_error 		=	( isset($davici_settings['sub-error']) && $davici_settings['sub-error'] ) ? $davici_settings['sub-error'] : esc_html__('Sorry, but the page you are looking for is not found. Please, make sure you have typed the current URL.', 'davici');
	$btn_error 		=	( isset($davici_settings['btn-error']) && $davici_settings['btn-error'] ) ? $davici_settings['btn-error'] : esc_html__('Go To Home', 'davici');
?>
<div class="page-404">
	<div class="content-page-404">
		<div class="title-error"><?php echo esc_html($title_error); ?></div>
		<div class="sub-title"><?php echo esc_html($sub_title); ?></div>
		<div class="sub-error"><?php echo esc_html($sub_error); ?></div>
		<a class="btn" href="<?php echo esc_url( home_url('/') ); ?>"><?php echo esc_html($btn_error); ?></a>	
	</div>
</div>
<?php
get_footer();