<?php
	function davici_get_config($option,$default='1'){
		$davici_settings = davici_global_settings();
		$query_string = davici_get_query_string();
		parse_str($query_string, $params);
		if(isset($params[$option]) && $params[$option]){
			return $params[$option];
		}
		else{
			$value = isset($davici_settings[$option]) ? $davici_settings[$option] : $default;
			return $value;
		}
	}
	function davici_get_query_string(){
		global $wp_rewrite;
		$request = remove_query_arg( 'paged' );
		$home_root = esc_url(home_url());
		$home_root = parse_url($home_root);
		$home_root = ( isset($home_root['path']) ) ? $home_root['path'] : '';
		$home_root = preg_quote( $home_root, '|' );
		$request = preg_replace('|^'. $home_root . '|i', '', $request);
		$request = preg_replace('|^/+|', '', $request);
		$request = preg_replace( "|$wp_rewrite->pagination_base/\d+/?$|", '', $request);
		$request = preg_replace( '|^' . preg_quote( $wp_rewrite->index, '|' ) . '|i', '', $request);
		$request = ltrim($request, '/');
		$qs_regex = '|\?.*?$|';
		preg_match( $qs_regex, $request, $qs_match );
		if ( !empty( $qs_match[0] ) ) {
			$query_string = $qs_match[0];
			$query_string = str_replace("?","",$query_string);
		} else {
			$query_string = '';
		}
		return 	$query_string;
	}
	function davici_add_url_parameter($url, $paramName, $paramValue) {
		$url_data = esc_url($url);
		$url_data = parse_url($url);
		if(!isset($url_data["query"]))
			$url_data["query"]="";
		$params = array();
		parse_str($url_data['query'], $params);
		$params[$paramName] = $paramValue;
		$url_data['query'] = http_build_query($params);
		return davici_build_url( $url_data );
	}	
	function davici_build_url($url_data) {
		$url="";
		if(isset($url_data['host'])){
			 $url .= $url_data['scheme'] . '://';
			 if (isset($url_data['user'])) {
				 $url .= $url_data['user'];
					 if (isset($url_data['pass'])) {
						 $url .= ':' . $url_data['pass'];
					 }
				 $url .= '@';
			 }
			 $url .= $url_data['host'];
			 if (isset($url_data['port'])) {
				 $url .= ':' . $url_data['port'];
			 }
		}
		if (isset($url_data['path'])) {
			$url .= $url_data['path'];
		}
		if (isset($url_data['query'])) {
			$url .= '?' . $url_data['query'];
		}
		if(isset($url_data['fragment'])) {
			$url .= '#' . $url_data['fragment'];
		}
		return $url;
	}	
	function davici_global_settings(){
		global $davici_settings;
		return $davici_settings;
	}

	function davici_limit_verticalmenu(){
		global $page_id;
		$vertical = new stdClass;
		$max_number_1530	= davici_get_config('max_number_1530',12);
		$vertical->max_number_1530 	= (get_post_meta( $page_id, 'max_number_1530', true )) ? get_post_meta($page_id, 'max_number_1530', true ) : $max_number_1530;
		
		$max_number_1200	= davici_get_config('max_number_1200',8);
		$vertical->max_number_1200  	= (get_post_meta( $page_id, 'max_number_1200', true )) ? get_post_meta($page_id, 'max_number_1200', true ) : $max_number_1200;
		
		$max_number_991		= davici_get_config('max_number_991',6);
		$vertical->max_number_991  	= (get_post_meta( $page_id, 'max_number_991', true )) ? get_post_meta($page_id, 'max_number_991', true ) : $max_number_991;
		
		return $vertical;
	}
	if ( ! function_exists( 'davici_popup_newsletter' ) ) {
		function davici_popup_newsletter() {
			$davici_settings = davici_global_settings(); 
			echo '<div class="popupshadow"></div>';
			echo '<div id="newsletterpopup" class="bingo-modal newsletterpopup">';
			echo '<span class="close-popup popup-1"><i aria-hidden="true" class="icon_close"></i></span>';
			echo '<span class="close-popup popup-2">'. esc_html__("no thank","davici") .'</span>';
			echo '<div class="wp-newletter">';
				if(isset($davici_settings['background_newletter_img']['url']) && !empty($davici_settings['background_newletter_img']['url'])){
					echo '<div class="image"> <img src='.esc_url($davici_settings['background_newletter_img']['url']).' alt="'.esc_attr__( 'Image Newletter','davici' ).'"></div>';
				}
				dynamic_sidebar('newletter-popup-form');
			echo '</div>';
			echo '</div>';
		}
	}
	function davici_config_font(){
		$config_fonts = array();
		$text_fonts = array(
			'family_font_body',
			'family_font_custom',
			'h1-font',
			'h2-font',
			'h3-font',
			'h4-font',
			'h5-font',
			'h6-font',
			'class_font_custom'
		);
		foreach ($text_fonts as $text) {
			if(davici_get_config($text))
				$config_fonts[$text] = davici_get_config($text);
		}
		return $config_fonts;
	}
	function davici_get_class(){
		$class = new stdClass;
		$sidebar_left_expand 		= davici_get_config('sidebar_left_expand',3);
		$sidebar_left_expand_md 	= davici_get_config('sidebar_left_expand_md',3);
		$class->class_sidebar_left  = 'col-xl-'.$sidebar_left_expand.' col-lg-'.$sidebar_left_expand_md.' col-md-12 col-12';
		$sidebar_right_expand 		= davici_get_config('sidebar_right_expand',3);
		$sidebar_right_expand_md 	= davici_get_config('sidebar_right_expand_md',3);
		$class->class_sidebar_right  = 'col-xl-'.$sidebar_right_expand.' col-lg-'.$sidebar_right_expand_md.' col-md-12 col-12';
		$sidebar_blog = davici_blog_sidebar();
		if($sidebar_blog == 'left' && is_active_sidebar('sidebar-blog')){
			$blog_content_expand = 12- $sidebar_left_expand;
			$blog_content_expand_md = 12- $sidebar_left_expand_md;
		}elseif($sidebar_blog == 'right' && is_active_sidebar('sidebar-blog')){
			$blog_content_expand = 12- $sidebar_right_expand;
			$blog_content_expand_md = 12- $sidebar_right_expand_md;
		}else{
			$blog_content_expand = 12;
			$blog_content_expand_md = 12;
		}
		$class->class_blog_content  = 'col-xl-'.$blog_content_expand.' col-lg-'.$blog_content_expand_md.' col-md-12 col-12';		
		$post_single_layout = davici_post_sidebar();
		if($post_single_layout == 'left' && is_active_sidebar('sidebar-blog')){
			$blog_single_expand = 12- $sidebar_left_expand;
			$blog_single_expand_md = 12- $sidebar_left_expand_md;
		}elseif($post_single_layout == 'right' && is_active_sidebar('sidebar-blog')){
			$blog_single_expand = 12- $sidebar_right_expand;
			$blog_single_expand_md = 12- $sidebar_right_expand_md;
		}else{
			$blog_single_expand = 12;
			$blog_single_expand_md = 12;
		}
		$class->class_single_content  = 'col-xl-'.$blog_single_expand.' col-lg-'.$blog_single_expand_md.' col-md-12 col-12';		
		$category_style = davici_get_config('category_style','sidebar');
		if($category_style == 'sidebar' && is_active_sidebar('sidebar-product')){
			$product_content_expand = 12- $sidebar_left_expand;
			$product_content_expand_md = 12- $sidebar_left_expand_md;
		}else{
			$product_content_expand = 12;
			$product_content_expand_md = 12;
		}
		$class->class_product_content  = 'col-xl-'.$product_content_expand.' col-lg-'.$product_content_expand_md.' col-md-12 col-12';		
		$sidebar_detail_product = davici_get_config('sidebar_detail_product');
		if($sidebar_detail_product == 'left' && is_active_sidebar('sidebar-product')){
			$product_content_expand = 12- $sidebar_left_expand;
			$product_content_expand_md = 12- $sidebar_left_expand_md;
		}elseif($sidebar_detail_product == 'right' && is_active_sidebar('sidebar-product')){
			$product_content_expand = 12- $sidebar_right_expand;
			$product_content_expand_md = 12- $sidebar_right_expand_md;
		}else{
			$product_content_expand = 12;
			$product_content_expand_md = 12;
		}
		$class->class_detail_product_content  = 'col-xl-'.$product_content_expand.' col-lg-'.$product_content_expand_md.' col-md-12 col-12';	
		$blog_col_large 	= 12/(davici_get_config('blog_col_large',3));
		$blog_col_medium = 12/(davici_get_config('blog_col_medium',3));
		$blog_col_sm 	= 12/(davici_get_config('blog_col_sm',3));
		$class->class_item_blog = 'col-xl-'.$blog_col_large.' col-lg-'.$blog_col_medium.' col-md-'.$blog_col_sm.' col-sm-12 col-12';
		return $class;
	}
	function davici_post_sidebar(){
		$sidebar_post = get_post_meta( get_the_ID(), 'post_single_layout', true );
		if( !empty($sidebar_post)){
			$post_single_layout = $sidebar_post;
		}else{
			$post_single_layout = davici_get_config('post-single-layout','left');
		}
		return 	$post_single_layout;
	}
	function davici_blog_view(){
		$category = get_category( get_query_var( 'cat' ) );
		$id_category =  ( isset($category->term_id) && $category->term_id ) ? $category->term_id : 0;
		$layout_blog = get_term_meta( $id_category, 'layout_blog', true );
		if( !empty($layout_blog) ){
			$blog_view = $layout_blog;
		}else{
			$blog_view = davici_get_config('layout_blog','list');
		}
		return 	$blog_view;
	}
	function davici_blog_sidebar(){
		$category = get_category( get_query_var( 'cat' ) );
		$id_category =  ( isset($category->term_id) && $category->term_id ) ? $category->term_id : 0;
		$sidebar_blog = get_term_meta( $id_category, 'sidebar_blog', true );
		if( !empty($sidebar_blog) ){
			$sidebar = $sidebar_blog;
		}else{
			$sidebar 		= davici_get_config('sidebar_blog','left');
		}
		return 	$sidebar;
	}	
	function davici_is_customize(){
		return isset($_POST['customized']) && ( isset($_POST['customize_messenger_chanel']) || isset($_POST['wp_customize']) );
	}	
	function davici_search_form( $form ) {
		$form = '<form role="search" method="get" id="searchform" class="search-from" action="' . esc_url(home_url( '/' )) . '" >
					<div class="container">
						<div class="form-content">
							<input type="text" value="' . esc_attr(get_search_query()) . '" name="s"  class="s" placeholder="' . esc_attr__( 'Search...', 'davici' ) . '" />
							<button id="searchsubmit" class="btn" type="submit">
								<i class="icon_search"></i>
								<span>' . esc_html__( 'Search', 'davici' ) . '</span>
							</button>
						</div>
					</div>
				  </form>';
		return $form;
	}
	add_filter( 'get_search_form', 'davici_search_form' );
	// Remove each style one by one
	add_filter( 'woocommerce_enqueue_styles', 'davici_jk_dequeue_styles' );
	function davici_jk_dequeue_styles( $enqueue_styles ) {
		unset( $enqueue_styles['woocommerce-general'] );	// Remove the gloss
		unset( $enqueue_styles['woocommerce-layout'] );		// Remove the layout
		unset( $enqueue_styles['woocommerce-smallscreen'] );	// Remove the smallscreen optimisation
		return $enqueue_styles;
	}
	// Or just remove them all in one line
	add_filter( 'woocommerce_enqueue_styles', '__return_false' );					
	function davici_woocommerce_breadcrumb( $args = array() ) {
		$args = wp_parse_args( $args, apply_filters( 'woocommerce_breadcrumb_defaults', array(
			'delimiter'   => '<span class="delimiter"></span>',
			'wrap_before' => '<div class="breadcrumb" ' . ( is_single() ? 'itemprop="breadcrumb"' : '' ) . '>',
			'wrap_after'  => '</div>',
			'before'      => '',
			'after'       => '',
			'home'        => _x( 'Home', 'breadcrumb', 'davici' )
		) ) );
		$breadcrumbs = new WC_Breadcrumb();
		if ( $args['home'] ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'woocommerce_breadcrumb_home_url', home_url() ) );
		}
		$args['breadcrumb'] = $breadcrumbs->generate();
		wc_get_template( 'global/breadcrumb.php', $args );
	}
	add_filter('woocommerce_add_to_cart_fragments', 'davici_woocommerce_header_add_to_cart_fragment');
	function davici_woocommerce_header_add_to_cart_fragment( $fragments )
	{
	    global $woocommerce;
	    ob_start(); 
	    get_template_part( 'woocommerce/minicart-ajax' );
	    $fragments['.mini-cart'] = ob_get_clean();
	    return $fragments;
	}
	function davici_display_view(){
		echo davici_grid_list();
    }
	function davici_grid_list(){
		$active_column_2 = $active_column_3 = $active_column_4 = $active_list = '';
		$product_col_large = davici_get_config('product_col_large',4);
		$category_view_mode = davici_category_view();
		$query_string = '?'.davici_get_query_string();
		$product_col_medium = 12 /(davici_get_config('product_col_medium',3));
		$product_col_sm 	= 12 /(davici_get_config('product_col_sm',1));
		$product_col_xs 	= 12 /(davici_get_config('product_col_xs',1));
		$class_item_product = 'col-lg-'.$product_col_medium.' col-md-'.$product_col_sm.' col-'.$product_col_xs;
		if($category_view_mode == 'grid'){
			$active_column_2 = ($product_col_large == 2 ) ? 'active' : '';
			$active_column_3 = ($product_col_large == 3 ) ? 'active' : '';
			$active_column_4 = ($product_col_large == 4 ) ? 'active' : '';			
		}else{
			$active_list = ($category_view_mode == 'list') ? 'active' : '';
		}
		$query_grid_string 	= davici_add_url_parameter($query_string, 'category-view-mode', 'grid');
		$html = '<ul class="display hidden-sm hidden-xs">
				<li>
					<a data-col="col-xl-6 '.esc_attr($class_item_product).'" class="view-grid two '.esc_attr($active_column_2).'" href="'. davici_add_url_parameter($query_grid_string, 'product_col_large', '2').'"><span class="icon-column"><span class="layer first"><span></span><span></span></span><span class="layer middle"><span></span><span></span></span><span class="layer last"><span></span><span></span></span></span></a>
				</li>
				<li>
					<a data-col="col-xl-4 '.esc_attr($class_item_product).'" class="view-grid three '.esc_attr($active_column_3).'" href="'. davici_add_url_parameter($query_grid_string, 'product_col_large', '3').'"><span class="icon-column"><span class="layer first"><span></span><span></span><span></span></span><span class="layer middle"><span></span><span></span><span></span></span><span class="layer last"><span></span><span></span><span></span></span></span></a>
				</li>
				<li>
					<a data-col="col-xl-3 '.esc_attr($class_item_product).'" class="view-grid four '.esc_attr($active_column_4).'" href="'. davici_add_url_parameter($query_grid_string, 'product_col_large', '4').'"><span class="icon-column"><span class="layer first"><span></span><span></span><span></span><span></span></span><span class="layer middle"><span></span><span></span><span></span><span></span></span><span class="layer last"><span></span><span></span><span></span><span></span></span></span></a>
				</li>
				<li>
					<a class="view-list '.esc_html($active_list).'" href="'. davici_add_url_parameter($query_string, 'category-view-mode', 'list').'"><span class="icon-column"><span class="layer first"><span></span><span></span></span><span class="layer middle"><span></span><span></span></span><span class="layer last"><span></span><span></span></span></span></a>
				</li>
			</ul>';
		return $html;
	}
	function davici_category_view(){
		$id_category =  is_tax() ? get_queried_object()->term_id : 0;
		$category_view = get_term_meta( $id_category, 'category_view', true );
		if( $category_view &&  $id_category != 0 ){
			$category_view_mode = $category_view;
		}else{
			$category_view_mode 		= davici_get_config('category-view-mode','grid');	
		}
		return 	$category_view_mode;
	}	
	function davici_main_menu($id, $layout = "") {
		global $davici_settings, $post;
		$show_cart = $show_wishlist = false;
		if ( isset($davici_settings['show_cart']) ) {
		$show_cart            = $davici_settings['show_cart'];
		}
		if ( isset($davici_settings['show_wishlist']) ) {
		$show_wishlist            = $davici_settings['show_wishlist'];
		}
		$vertical_header_text = (isset($davici_settings['vertical_header_text']) && $davici_settings['vertical_header_text']) ? $davici_settings['vertical_header_text'] : '';
		$page_menu = $menu_output = $menu_full_output = $menu_with_search_output = $menu_float_output = $menu_vert_output = "";
		$main_menu_args = array(
			'echo'            => false,
			'theme_location' => 'main_navigation',
			'walker' => new davici_mega_menu_walker,
		);
		$menu_output .= '<nav id="'.$id.'" class="std-menu clearfix">'. "\n";
		if(function_exists('wp_nav_menu')) {
			if (has_nav_menu('main_navigation')) {
				$menu_output .= wp_nav_menu( $main_menu_args );
			}
			else {
				if(is_user_logged_in()){
					$menu_output .= '<div class="no-menu">'. esc_html__("Please assign a menu to the Main Menu in Appearance > Menus", 'davici').'</div>';
				}
			}
		}
		$menu_output .= '</nav>'. "\n";
		switch ($layout) {
			case 'full':
					$menu_full_output .= '<div class="container">'. "\n";
					$menu_full_output .= '<div class="row">'. "\n";
					$menu_full_output .= '<div class="menu-left">'. "\n";
					$menu_full_output .= $menu_output . "\n";
					$menu_full_output .= '</div>'. "\n";
					$menu_full_output .= '<div class="menu-right">'. "\n";
					$menu_full_output .= '</div>'. "\n";
					$menu_full_output .= '</div>'. "\n";
					$menu_full_output .= '</div>'. "\n";
					$menu_output = $menu_full_output;
				break;
			case 'float':
					$menu_float_output .= '<div class="float-menu">'. "\n";
					$menu_float_output .= $menu_output . "\n";
					$menu_float_output .= '</div>'. "\n";
					$menu_output = $menu_float_output;
				break;
			case 'float-2':
					$menu_float_output .= '<div class="float-menu container">'. "\n";
					$menu_float_output .= $menu_output . "\n";
					$menu_float_output .= '</div>'. "\n";
					$menu_output = $menu_float_output;
				break;				
			case 'vertical':
				$menu_vertical_output .= $menu_output . "\n";
				$menu_vertical_output .= '<div class="vertical-menu-bottom">'. "\n";
				if($vertical_header_text)
				$menu_vertical_output .= '<div class="copyright">'.do_shortcode(stripslashes($vertical_header_text)).'</div>'. "\n";
				$menu_vertical_output .= '</div>'. "\n";
				$menu_output = $menu_vertical_output;
				break;
		}	
		return $menu_output;
	}				
	add_action('admin_enqueue_scripts','davici_upload_scripts');	
	function davici_upload_scripts()
    {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }		
	function davici_body_classes( $classes ) {
		if (is_single() || is_page() && !is_front_page()) {
			$classes[] = basename(get_permalink());
		}			
		$type_banner = davici_get_config('banners_effect');
		$classes[] = $type_banner;		
		$direction = davici_get_direction(); 
		if($direction && $direction == 'rtl'){
			$classes[] = 'rtl';
		}
		return $classes;
	}
	add_filter( 'body_class', 'davici_body_classes' );
	function davici_post_classes( $classes ) {
		if ( ! post_password_required() && ! is_attachment() && has_post_thumbnail() ) {
			$classes[] = 'has-post-thumbnail';
		}
		return $classes;
	}
	add_filter( 'post_class', 'davici_post_classes' );
	function davici_get_excerpt($limit = 45, $more_link = true, $more_style_block = false) {
		$davici_settings = davici_global_settings();
		if (!$limit) {
			$limit = 45;
		}
		if (has_excerpt()) {
			$content = get_the_excerpt();
		} else {
			$content = get_the_content();
		}
		if($content)
		{
			$check_readmore = false;
			$content = davici_strip_tags( apply_filters( 'the_content', $content ) );
			$content = explode(' ', $content, $limit);
			if (count($content) >= $limit) {
				$check_readmore = true;
				array_pop($content);
				$content = implode(" ",$content).'... ';
			} else {
				$content = implode(" ",$content);
			}
			$content = '<p class="post-excerpt">'.wp_kses($content,'social').'</p>';
			if ($more_link && $check_readmore) {
				if ($more_style_block) {
					$content .= ' <a class="read-more read-more-block" href="'.esc_url( apply_filters( 'the_permalink', get_permalink() ) ).'">'.esc_html__('Read more', 'davici').'</a>';
				} else {
					$content .= ' <a class="read-more" href="'.esc_url( apply_filters( 'the_permalink', get_permalink() ) ).'">'.esc_html__('Read more', 'davici').'</a>';
				}
			}
		}
		return $content;
	}
	function davici_strip_tags( $content ) {
		$content = str_replace( ']]>', ']]&gt;', $content );
		$content = preg_replace("/<script.*?\/script>/s", "", $content);
		$content = preg_replace("/<style.*?\/style>/s", "", $content);
		$content = strip_tags( $content );
		return $content;
	}
	if( !function_exists( 'davici_get_direction' ) ) :
	function davici_get_direction(){
		$direction = davici_get_config('direction','ltr');		
		if (isset($_COOKIE['davici_direction_cookie']))
			$direction = $_COOKIE['davici_direction_cookie'];
		if(isset($_GET['direction']) && $_GET['direction'])
			$direction = $_GET['direction'];
		return 	$direction;
	}
	endif;	
	function davici_get_entry_content_asset( $post_id ){
		$post = get_post( $post_id );
		$content = apply_filters ("the_content", $post->post_content);
		$video = get_media_embedded_in_content( $content, array( 'video', 'object', 'embed', 'iframe' ) );
		if ( ! empty( $video ) ) {
			$html = '';
			foreach ( $video as $video_html ) {
				$html .=   '<div class="video-wrapper">';
					$html .= $video_html;
				$html .= '</div>';
			}
			return $html;
		}
	}
	function davici_loading_overlay(){
		$davici_settings = davici_global_settings();
		if(isset($davici_settings['show-loading-overlay']) && $davici_settings['show-loading-overlay'] ){
			echo'<div class="loader-content">
				<div id="loader">
					<div class="chasing-dots"><div></div><div></div><div></div><div></div></div>
				</div>
			</div>';
		}
	}
	function davici_header_logo(){
		$davici_settings = davici_global_settings(); 
		$sitelogo = (isset($davici_settings['sitelogo']['url']) && $davici_settings['sitelogo']['url']) ? $davici_settings['sitelogo']['url'] : "";
		$page_logo_url = get_post_meta( get_the_ID(), 'page_logo', true );
		$page_logo_url = ($page_logo_url) ? $page_logo_url : $sitelogo; ?>
		<div class="wpbingoLogo">
			<a  href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php if($page_logo_url){ ?>
					<img src="<?php echo esc_url($page_logo_url); ?>" alt="<?php bloginfo('name'); ?>"/>
				<?php }else{
					$logo = get_template_directory_uri().'/images/logo/logo.png'; ?>
					<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php bloginfo('name'); ?>"/>
				<?php } ?>
			</a>
		</div> 
	<?php }
	function davici_top_menu(){
		$davici_settings = davici_global_settings();
		echo '<div class="wpbingo-menu-wrapper">
			<div class="megamenu">
				<nav class="navbar-default">
					<div  class="bwp-navigation primary-navigation navbar-mega" data-text_close = "'.esc_html__('Close','davici').'">
						'.davici_main_menu( 'main-navigation', 'float' ).'
					</div>
				</nav> 
			</div>       
		</div>';
	}	
	function davici_top_menu_right(){
		$davici_settings = davici_global_settings();
		echo '<div class="wpbingo-menu-wrapper">
			<div class="megamenu">
				<div  class="bwp-navigation primary-navigation navbar-mega">
					'.davici_main_menu( 'main-navigation', 'float' ).'
				</div>
			</div>       
		</div>';
	}
	
	function davici_navbar_vertical_menu(){
		echo '<div class="wpbingo-verticalmenu-mobile">
			<div class="navbar-header">
				<button type="button" id="show-verticalmenu"  class="navbar-toggle">
					<span>'. esc_html__("Vertical","davici") .'</span>
				</button>
			</div>
		</div>';
	}
	
	function davici_vertical_menu() {
		global $davici_settings;
		$menu_output = "";
		$vertical_menu_args = array(
			'echo'            => false,
			'theme_location' => 'vertical_menu',
			'walker' => new davici_mega_menu_walker,
		);	
		if(function_exists('wp_nav_menu')) {
			if (has_nav_menu('vertical_menu')) {
				$menu_output .=	'<h3 class="widget-title"><i class="fa fa-bars" aria-hidden="true"></i>'.esc_html__('All Departments','davici').'</h3>';
				$menu_output .='<div class="verticalmenu">
					<div  class="bwp-vertical-navigation primary-navigation navbar-mega">
						'.wp_nav_menu( $vertical_menu_args ).'
					</div> 
				</div>';
			}
		}
		
		return $menu_output;
	}
	
	function davici_dropdown_vertical_menu(){
		global $page_id;
		$show_vertical_menu  = (get_post_meta( $page_id, 'show_vertical_menu', true )) ? get_post_meta($page_id, 'show_vertical_menu', true ) : 'accordion';
		return $show_vertical_menu;
	}	
	
	function davici_category_post(){
		global $post;
		$obj_category = new stdClass;
		$term_list = wp_get_post_terms($post->ID,'category',array('fields'=>'ids'));
		$cat_id = (int)$term_list[0];
		$category = get_term( $cat_id, 'category' );
		$obj_category->name = $category->name;
		$obj_category->cat_link = get_term_link ($cat_id, 'category');	
		return $obj_category;
	}
	function davici_copyright(){
		$davici_settings = davici_global_settings();?>
		<div class="bwp-copyright">
			<div class="container">		
			    <div class="row">
					<?php if(isset($davici_settings['footer-copyright']) && $davici_settings['footer-copyright']) : ?>		
						<div class="site-info col-sm-6 col-xs-12">
							<?php echo esc_html($davici_settings['footer-copyright']); ?>
						</div><!-- .site-info -->
					<?php else: ?>					
						<div class="site-info col-sm-6 col-xs-12">
							<?php echo esc_html__( 'Copyright 2020 ','davici'); ?>					
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html__('davici', 'davici'); ?></a>
							<?php echo esc_html__( 'Template. All Rights Reserved.','davici'); ?>
						</div><!-- .site-info -->		
					<?php endif; ?>
					<?php if(isset($davici_settings['footer-payments']) && $davici_settings['footer-payments']) : ?>
						<div class="payment col-sm-6 col-xs-12">
							<a href="<?php echo isset($davici_settings['footer-payments-link']) ? esc_url($davici_settings['footer-payments-link']) : "#"; ?>">
								<img src="<?php echo isset($davici_settings['footer-payments-image']['url']) ? esc_url($davici_settings['footer-payments-image']['url']) : ""; ?>" alt="<?php echo isset($davici_settings['footer-payments-image-alt']) ? esc_attr($davici_settings['footer-payments-image-alt']) : ""; ?>" />
							</a>
						</div>		
					<?php endif; ?>	
				</div>
			</div>
		</div>	
		<?php	
	}
	function davici_render_footer($footer_style){
		$elementor_instance = Elementor\Plugin::instance();
		return $elementor_instance->frontend->get_builder_content_for_display( $footer_style );
	}
	if( !is_admin() ){
		add_filter( 'language_attributes', 'davici_direction', 20 );
		function davici_direction( $doctype = 'html' ){
	   		$direction = davici_get_direction();
	   		if ( ( function_exists( 'is_rtl' ) && is_rtl() ) || $direction == 'rtl' ){
	    		$attribute[] = 'direction="rtl"';
				$attribute[] = 'dir="rtl"';
	    		$attribute[] = 'class="rtl"';
	   		}
	   		( $direction === 'rtl' ) ? $lang = 'ar' : $lang = get_bloginfo('language');
	   		if ( $lang ) {
	   			if ( get_option('html_type') == 'text/html' || $doctype == 'html' )
	    			$attribute[] = "lang=\"$lang\"";
	   			if ( get_option('html_type') != 'text/html' || $doctype == 'xhtml' )
	    			$attribute[] = "xml:lang=\"$lang\"";
	   		}
	   		$davici_output = implode(' ', $attribute);
	   		return $davici_output;
		}
	}	
	function davici_comment( $comment, $args, $depth ) {
		if ( 'div' == $args['style'] ) {
			$tag = 'div';
			$add_below = 'comment';
		} else {
			$tag = 'li';
			$add_below = 'div-comment';
		}
		?>
		<div class="media">
			<div class="media-left">
				<?php echo get_avatar( $comment, 70 ); ?>
			</div>
			<div class="media-body">
				<div class="comment-meta media-content commentmetadata">
					<div class="comment-author vcard">
					<?php printf( wp_kses_post( '<h2 class="media-heading">%s</h2>', 'davici' ), get_comment_author_link() ); ?>
					</div>
					<?php if ( '0' == $comment->comment_approved ) : ?>
					<em class="comment-awaiting-moderation"><?php echo esc_html__( 'Your comment is awaiting moderation.', 'davici' ); ?></em>
					<?php endif; ?>
					<div class="media-silver">
						<a href="<?php echo esc_url( htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ); ?>" class="comment-date">
							<?php echo '<time datetime="' . get_comment_date( 'c' ) . '">' . get_comment_date() . '</time>'; ?>
						</a>
						<?php edit_comment_link( __( 'Edit', 'davici' ), '  ', '' ); ?>
					</div>
					<div id="div-comment-<?php comment_ID() ?>" class="comment-content">
						<div class="comment-text">
						<?php comment_text(); ?>
						</div>
					</div>
					<?php comment_reply_link( array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
				</div>
			</div>
		</div>
	<?php
	}
	function davici_prefix_kses_allowed_html($allowed_tags, $context) {
		switch($context) {
			case 'social': 
			$allowed_tags = array(
				'a' => array(
					'class' => array(),
					'href'  => array(),
					'rel'   => array(),
					'title' => array(),
				),
				'abbr' => array(
					'title' => array(),
				),
				'b' => array(),
				'blockquote' => array(
					'cite'  => array(),
				),
				'cite' => array(
					'title' => array(),
				),
				'code' => array(),
				'br' => array(),
				'del' => array(
					'datetime' => array(),
					'title' => array(),
				),
				'dd' => array(),
				'div' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'dl' => array(),
				'dt' => array(),
				'em' => array(),
				'h1' => array(),
				'h2' => array(),
				'h3' => array(),
				'h4' => array(),
				'h5' => array(),
				'h6' => array(),
				'i' => array(
					'class'  => array(),
				),
				'img' => array(
					'alt'    => array(),
					'class'  => array(),
					'height' => array(),
					'src'    => array(),
					'width'  => array(),
				),
				'li' => array(
					'class' => array(),
				),
				'ol' => array(
					'class' => array(),
				),
				'p' => array(
					'class' => array(),
				),
				'q' => array(
					'cite' => array(),
					'title' => array(),
				),
				'span' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'strike' => array(),
				'strong' => array(),
				'ul' => array(
					'class' => array(),
				),
				'button' => array(
					'class' => array(),
					'type' => array(),
				),				
			);
			return $allowed_tags;
			default:
			return $allowed_tags;
		}
	}
	add_filter( 'wp_kses_allowed_html', 'davici_prefix_kses_allowed_html', 10, 2);
	if ( ! function_exists( 'wp_body_open' ) ) {
		function wp_body_open() {
			do_action( 'wp_body_open' );
		}
	}
	function davici_menu_mobile($vertical = false){
	$davici_settings = davici_global_settings();
	$cart_style = davici_get_config('cart-style','popup');
	$show_searchform = (isset($davici_settings['show-searchform']) && $davici_settings['show-searchform']) ? ($davici_settings['show-searchform']) : false;
	$show_wishlist = (isset($davici_settings['show-wishlist']) && $davici_settings['show-wishlist']) ? ($davici_settings['show-wishlist']) : false;
	$show_minicart = (isset($davici_settings['show-minicart']) && $davici_settings['show-minicart']) ? ($davici_settings['show-minicart']) : false;
	?>
	<div class="header-mobile">
		<div class="container">
			<div class="row">
				<?php if( $vertical || ($show_minicart && class_exists( 'WooCommerce' )) ){ ?>
				<div class="col-xl-4 col-lg-4 col-md-4 col-sm-3 col-3 header-left">
					<div class="navbar-header">
						<button type="button" id="show-megamenu"  class="navbar-toggle">
							<span><?php echo esc_html__("Menu","davici"); ?></span>
						</button>
					</div>
				</div>
				<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6 header-center ">
					<?php davici_header_logo(); ?>
				</div>
				<div class="col-xl-4 col-lg-4 col-md-4 col-sm-3 col-3 header-right">
					<?php if($vertical){?>
						<?php davici_navbar_vertical_menu(); ?>
					<?php } ?>
					<?php if($show_minicart && class_exists( 'WooCommerce' )){ ?>
					<div class="davici-topcart <?php echo esc_attr($cart_style); ?>">
						<?php get_template_part( 'woocommerce/minicart-ajax' ); ?>
					</div>
					<?php } ?>
				</div>
				<?php }else{ ?>
					<div class="col-xl-8 col-lg-8 col-md-8 col-sm-8 col-8 header-left header-left-default ">
						<?php davici_header_logo(); ?>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-4 col-4 header-right header-right-default">
						<div class="navbar-header">
							<button type="button" id="show-megamenu"  class="navbar-toggle">
								<span><?php echo esc_html__("Menu","davici"); ?></span>
							</button>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php if(class_exists( 'WooCommerce' )){ ?>
		<div class="header-mobile-fixed">
			<div class="shop-page">
				<a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>"><i class="wpb-icon-shop"></i></a>
			</div>
			<div class="my-account">
				<a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>"><i class="wpb-icon-user"></i></a>
			</div>		
			<!-- Begin Search -->
			<?php if($show_searchform){ ?>
			<div class="search-box">
				<div class="search-toggle"><i class="wpb-icon-magnifying-glass"></i></div>
			</div>
			<?php } ?>
			<!-- End Search -->
			<?php if($show_wishlist && class_exists( 'YITH_WCWL' )){ ?>
			<div class="wishlist-box">
				<a href="<?php echo get_permalink( get_option('yith_wcwl_wishlist_page_id') ); ?>"><i class="wpb-icon-heart"></i></a>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
	<?php }
	function davici_login_form() { ?>
	<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
		<div class="form-login-register">
			<div class="box-form-login">
				<div class="active-login"><i class="icon_close"></i></div>
				<div class="box-content">
					<div class="form-login active">
						<form method="post" class="login">
							<h2><?php echo esc_html__("Sign in","davici") ?></h2>
							<div class="content">
								<?php do_action( 'woocommerce_login_form_start' ); ?>
								<div class="username">
									<input type="text" required="required" class="input-text" name="username" id="username" placeholder="<?php echo esc_html__("Your name","davici") ?>" />
								</div>
								<div class="password">
									<input class="input-text" required="required" type="password" name="password" id="password" placeholder="<?php echo esc_html__("Password","davici") ?>" />
								</div>
								<div class="rememberme-lost">
									<div class="rememberme">
										<input name="rememberme" type="checkbox" id="rememberme" value="forever" />
										<label for="rememberme" class="inline"><?php echo esc_html__( 'Remember me', 'davici' ); ?></label>
									</div>
									<div class="lost_password">
										<a href="<?php echo esc_url( wc_lostpassword_url() ); ?>"><?php echo esc_html__( 'Lost your password?', 'davici' ); ?></a>
									</div>
								</div>
								<div class="button-login">
									<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
									<input type="submit" class="button" name="login" value="<?php echo esc_html__( 'Login', 'davici' ); ?>" /> 
								</div>
								<a class="button-next-reregister" href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>"><?php echo esc_html__("Create An Account","davici") ?></a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	<?php }
?>