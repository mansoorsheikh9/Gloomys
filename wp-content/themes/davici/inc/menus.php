<?php
    /*
    *
    *	Wpbingo Framework Menu Functions
    *	------------------------------------------------
    *	Wpbingo Framework v3.0
    * 	Copyright Wpbingo Ideas 2017 - http://wpbingosite.com/
    *
    *	davici_setup_menus()
    *
    */
    /* CUSTOM MENU SETUP
    ================================================== */
    register_nav_menus( array(
        'main_navigation' => esc_html__( 'Main Menu', 'davici' ),
		'vertical_menu'     => esc_html__( 'Vertical Menu', 'davici' ),
		'currency_menu'     => esc_html__( 'Currency Menu', 'davici' ),   
        'language_menu'     => esc_html__( 'Language Menu', 'davici' ),
		'topbar_menu'     => esc_html__( 'Topbar Menu', 'davici' )
    ) );
?>