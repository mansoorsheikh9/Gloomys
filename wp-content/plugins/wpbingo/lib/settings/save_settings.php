<?php
	add_action('redux/options/davici_settings/saved', 'davici_save_theme_settings', 10, 2);
	use Leafo\ScssPhp\Compiler;
	use Leafo\ScssPhp\Server;	
	function davici_save_theme_settings() {
		global $davici_settings,$skins;
		$reduxdaviciSettings = new Redux_Framework_davici_settings();
		$reduxFramework = $reduxdaviciSettings->ReduxFramework;
		if (isset($davici_settings['compile-css']) && $davici_settings['compile-css']) {
			require_once( dirname(__FILE__) . '/scssphp/scss.inc.php');			
			ob_start();
            $sassDir = get_template_directory().'/sass/';
            $cssDir = get_template_directory().'/css/';
            $variables = '';
            if (is_writable($sassDir) == false){
                @chmod($sassDir, 0755);
            }
            $scss = new Compiler();
            $scss->addImportPath($sassDir);
			$variables = '$theme-color: '.$davici_settings['main_theme_color'].';';
			$string_sass = $variables . file_get_contents($sassDir . "template.scss");
			$string_css = $scss->compile($string_sass);
			file_put_contents($cssDir . 'template.css', $string_css);			
		}	
	}
?>