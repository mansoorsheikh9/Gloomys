<?php
/*
Plugin Name: Wpbingo Core
Plugin URI: https://themeforest.net/user/wpbingo
Description: Use For Wpbingo Theme.
Version: 1.0
Author: TungHV
Author URI: https://themeforest.net/user/wpbingo
*/

// don't load directly
if (!defined('ABSPATH'))
    die('-1');

require_once( dirname(__FILE__) . '/function.php');
require_once( dirname(__FILE__) . '/elementor.php');
define('WPBINGO_ELEMENTOR_PATH', dirname(__FILE__) . '/elementor/');
define('WPBINGO_ELEMENTOR_TEMPLATE_PATH', dirname(__FILE__) . '/elementor-template/');
define('WPBINGO_WIDGET_PATH', dirname(__FILE__) . '/widgets/');
define('WPBINGO_WIDGET_TEMPLATE_PATH', dirname(__FILE__) . '/widgets-template/');
define('WPBINGO_CONTENT_TYPES_LIB', dirname(__FILE__) . '/lib/');
require_once WPBINGO_CONTENT_TYPES_LIB . 'lookbook/includes/bwp_lookbook_class.php';
define ( 'LOOKBOOK_TABLE', 'bwp_lookbook');
class WpbingoShortcodesClass {
    function __construct() {
        // Init plugins
		$this->loadInit();
		add_filter( 'wp_calculate_image_srcset', array( $this, 'bwp_disable_srcset' ) );
		remove_filter('pre_term_description', 'wp_filter_kses');
		load_plugin_textdomain('wpbingo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
	function loadInit() {
		global $woocommerce;
		if ( ! isset( $woocommerce ) || ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', array( $this, 'bwp_woocommerce_admin_notice' ) );
			return;
		}else{
			add_action('wp_enqueue_scripts', array( $this, 'bwp_framework_script' ) );	
			require_once(WPBINGO_CONTENT_TYPES_LIB.'settings/save_settings.php');
			$this->bwp_load_file(WPBINGO_WIDGET_PATH);
			$this->bwp_load_file(WPBINGO_CONTENT_TYPES_LIB);
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );
			add_action( 'init',array( $this, 'wpbingo_remove_default_action'));
		}
    }
	function register_widgets(){
		register_widget( 'bwp_recent_post_widget');
		register_widget( 'bwp_ajax_filter_widget' );
	}	
	function wpbingo_remove_default_action(){
		if ( class_exists( 'YITH_Woocompare_Frontend' ) && get_option('yith_woocompare_compare_button_in_product_page') == 'yes' ) {
			global $yith_woocompare;
			if( ! is_admin() ) {
				remove_action('woocommerce_after_shop_loop_item', array($yith_woocompare->obj, 'add_compare_link'), 20);
				remove_action('woocommerce_single_product_summary', array($yith_woocompare->obj, 'add_compare_link'), 35);
			}
		}
		remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
		remove_filter( 'woocommerce_get_item_data', 'dokan_product_seller_info', 10 );
	}
	function bwp_load_file($path){
		$files = array_diff(scandir($path), array('..', '.'));
		if(count($files)>0){
			foreach ($files as  $file) {
				if (strpos($file, '.php') !== false)
					require_once($path . $file);
			}
		}		
	}
	function bwp_framework_script(){
		wp_enqueue_script( 'jquery-ui-slider', false, array('jquery'));
		wp_enqueue_script('bwp_wpbingo_js',plugins_url( '/wpbingo/assets/js/wpbingo.js' ),array("jquery"),false,true);	
		wp_register_script( 'jquery-cookie', plugins_url( '/wpbingo/assets/js/jquery.cookie.min.js' ), array( 'jquery' ), null, true );
		wp_enqueue_script( 'jquery-cookie' );
		wp_register_script( 'wpbingo-newsletter', plugins_url( '/wpbingo/assets/js/newsletter.js' ), array('jquery','jquery-cookie'), null, true );
		wp_enqueue_script( 'wpbingo-newsletter' );
		wp_register_style( 'bwp_woocommerce_filter_products', plugins_url('/wpbingo/assets/css/bwp_ajax_filter.css') );
		if (!wp_style_is('bwp_woocommerce_filter_products')) {
			wp_enqueue_style('bwp_woocommerce_filter_products');
		}
		wp_register_script('bwp_woocommerce_filter', plugins_url( '/wpbingo/assets/js/filter.js' ), array('jquery'), null, true);	
		wp_localize_script( 'bwp_woocommerce_filter', 'filter_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script('bwp_woocommerce_filter');		
	}
	function bwp_woocommerce_admin_notice(){ ?>
		<div class="error">
			<p><?php echo esc_html__( 'Wpbingo is enabled but not effective. It requires WooCommerce in order to work.', 'wpbingo' ); ?></p>
		</div>
		<?php
	}
	function bwp_disable_srcset( $sources ) {		
		return false;	
	}
}
function lookbook_install () {
    global $wpdb;
	
    $table_name = $wpdb->prefix . LOOKBOOK_TABLE;
	include_once ABSPATH.'wp-admin/includes/upgrade.php';
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
				  `title` varchar(255),
				  `description` varchar(255),
                  `width` smallint(5) unsigned NOT NULL,
                  `height` smallint(5) unsigned NOT NULL,		  
                  `image` varchar(255) NOT NULL,
                  `pins` text NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
		if(dbDelta($sql)){
			$sql_insert = "INSERT INTO `" . $table_name . "` (`id`, `name`, `title`, `description`, `width`, `height`, `image`, `pins`) VALUES
			(14, 'Lookbook 1', '', '', 1920, 859, 'img2-6.jpg', '[{\"id\":\"1593481412062\",\"top\":194,\"left\":441,\"width\":30,\"height\":30,\"slug\":\"briarwood-decorative-2\",\"img_height\":859,\"img_width\":1920,\"editable\":true},{\"id\":\"1593481424437\",\"top\":594,\"left\":214,\"width\":30,\"height\":30,\"slug\":\"aqua-globes-2\",\"img_height\":859,\"img_width\":1920,\"editable\":true},{\"id\":\"1593481432694\",\"top\":432,\"left\":768,\"width\":30,\"height\":30,\"slug\":\"chair-padded-seat\",\"img_height\":859,\"img_width\":1920,\"editable\":true},{\"id\":\"1593481440477\",\"top\":427,\"left\":1726,\"width\":30,\"height\":30,\"slug\":\"armen-living\",\"img_height\":859,\"img_width\":1920,\"editable\":true}]'),
			(15, 'Lookbook 2', '', '', 1930, 947, '0_img3-6.jpg', '[{\"id\":\"1593574511429\",\"top\":584,\"left\":384,\"width\":30,\"height\":30,\"slug\":\"alarm-clock\",\"img_height\":947,\"img_width\":1923,\"editable\":true},{\"id\":\"1593574524117\",\"top\":746,\"left\":976,\"width\":30,\"height\":30,\"slug\":\"chair-padded-seat\",\"img_height\":947,\"img_width\":1923,\"editable\":true},{\"id\":\"1593574535973\",\"top\":593,\"left\":1320,\"width\":30,\"height\":30,\"slug\":\"armen-living\",\"img_height\":947,\"img_width\":1923,\"editable\":true},{\"id\":\"1593574547557\",\"top\":270,\"left\":1554,\"width\":30,\"height\":30,\"slug\":\"cwi-gifts-wood\",\"img_height\":947,\"img_width\":1923,\"editable\":true}]');";
			dbDelta($sql_insert);
		}
    }
    $file = new bwp_lookbook_class();
    $file->create_folder_recursive(LOOKBOOK_UPLOAD_PATH);
    $file->create_folder_recursive(LOOKBOOK_UPLOAD_PATH_THUMB);
	add_option('update2prof_notice', 0,0);
}

register_activation_hook(__FILE__, 'lookbook_install');

register_deactivation_hook(__FILE__, 'lookbook_deactivate');

function lookbook_deactivate() {
    if( !function_exists( 'the_field' )) {
        update_option( 'update2prof_notice', 0 );
    }
}
// Finally initialize code
new WpbingoShortcodesClass();

	
	