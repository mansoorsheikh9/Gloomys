	<?php
		global $woocommerce;
		$davici_settings = davici_global_settings();
		$cart_style = davici_get_config('cart-style','popup');
		$show_minicart = (isset($davici_settings['show-minicart']) && $davici_settings['show-minicart']) ? ($davici_settings['show-minicart']) : false;
		$show_compare = (isset($davici_settings['show-compare']) && $davici_settings['show-compare']) ? ($davici_settings['show-compare']) : false;
		$enable_sticky_header = ( isset($davici_settings['enable-sticky-header']) && $davici_settings['enable-sticky-header'] ) ? ($davici_settings['enable-sticky-header']) : false;
		$show_searchform = (isset($davici_settings['show-searchform']) && $davici_settings['show-searchform']) ? ($davici_settings['show-searchform']) : false;
		$show_wishlist = (isset($davici_settings['show-wishlist']) && $davici_settings['show-wishlist']) ? ($davici_settings['show-wishlist']) : false;
		$show_currency = (isset($davici_settings['show-currency']) && $davici_settings['show-currency']) ? ($davici_settings['show-currency']) : false;
		$show_menutop = (isset($davici_settings['show-menutop']) && $davici_settings['show-menutop']) ? ($davici_settings['show-menutop']) : false;
	?>
	<h1 class="bwp-title hide"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
	<header id='bwp-header' class="bwp-header header-v2">
		<?php davici_menu_mobile(); ?>
		<div class="header-desktop">
			<?php if(($show_minicart || $show_wishlist || $show_compare || $show_searchform || is_active_sidebar('top-link')) && class_exists( 'WooCommerce' ) ){ ?>
			<div class='header-wrapper' data-sticky_header="<?php echo esc_attr($davici_settings['enable-sticky-header']); ?>">
				<div class="container">
					<div class="row">
						<div class="col-xl-6 col-lg-8 col-md-12 col-sm-12 col-12 header-left content-header">
							<?php davici_header_logo(); ?>
							<div class="wpbingo-menu-mobile header-menu">
								<div class="header-menu-bg">
									<?php davici_top_menu(); ?>
								</div>
							</div>
						</div>
						<div class="col-xl-6 col-lg-4 col-md-12 col-sm-12 col-12 header-right">
							<div class="header-search-form hidden-md hidden-sm hidden-xs">
								<!-- Begin Search -->
								<?php if($show_searchform && class_exists( 'WooCommerce' )){ ?>
									<?php get_template_part( 'search-form' ); ?>
								<?php } ?>
								<!-- End Search -->	
							</div>
							<div class="header-page-link">
								<!-- Begin Search -->
								<?php if($show_searchform && class_exists( 'WooCommerce' )){ ?>
								<div class="search-box hidden-lg">
									<div class="search-toggle"><i class="icon-search"></i></div>
								</div>
								<?php } ?>
								<!-- End Search -->
								<div class="account">
								<?php if (is_user_logged_in()) { ?>
									<a href="<?php echo wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><i class="icon-logout"></i><?php echo esc_html__('Logout', 'davici')?></a>
								<?php }else{ ?>
									<div class="active-login">
										<h2><i class="icon-login"></i><?php echo esc_html__('Login', 'davici')?></h2>
									</div>
								<?php } ?>
								</div>						
								<?php if($show_wishlist && class_exists( 'WPCleverWoosw' )){ ?>
								<div class="wishlist-box">
									<a href="<?php echo WPcleverWoosw::get_url(); ?>"><i class="icon-heart"></i></a>
								</div>
								<?php } ?>
								<?php if($show_compare && class_exists( 'WPCleverWooscp' )){ ?>
								<div class="compare-box hidden-sm hidden-xs">        
									<a href="<?php echo WPCleverWooscp::wooscp_get_page_url(); ?>"><?php echo esc_html__('Compare', 'davici')?></a>
								</div>
								<?php } ?>
								<?php if($show_minicart && class_exists( 'WooCommerce' )){ ?>
								<div class="davici-topcart <?php echo esc_attr($cart_style); ?>">
									<?php get_template_part( 'woocommerce/minicart-ajax' ); ?>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div><!-- End header-wrapper -->
			<?php }else{ ?>
				<div class="header-normal">
					<div class='header-wrapper' data-sticky_header="<?php echo esc_attr($davici_settings['enable-sticky-header']); ?>">
						<div class="container">
							<div class="row">
								<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6 header-left">
									<?php davici_header_logo(); ?>
								</div>
								<div class="col-xl-9 col-lg-9 col-md-6 col-sm-6 col-6 wpbingo-menu-mobile header-main">
									<div class="header-menu-bg">
										<?php davici_top_menu(); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
			<?php davici_login_form(); ?>
		</div>
	</header><!-- End #bwp-header -->