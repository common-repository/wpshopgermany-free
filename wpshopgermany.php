<?php
	
	/*
	Plugin Name: wpShopGermany
	Text Domain: wpsg
	Domain Path: /lang
	Plugin URI: http://wpshopgermany.maennchen1.de/
	Description: Das deutsche WordPress Shop-Plugin!
	Author: maennchen1.de
	Version: 4.2.0.7665
	Author URI: http://maennchen1.de/
	*/
	
	define('WPSG_VERSION', '4.2.0.7665');

	global $wpdb;
	
	if (defined('MULTISITE') && MULTISITE === true && get_site_option('wpsg_multiblog_standalone', true) != '1') {
		
		$prefix = $wpdb->base_prefix;
		
	} else {
		
		$prefix = $wpdb->prefix;
		
	}
	
	// Tabellen
	define('WPSG_TBL_ADRESS', $prefix.'wpsg_adress');
	define('WPSG_TBL_META', $prefix.'wpsg_meta');
	define('WPSG_TBL_PRODUCTS', $prefix.'wpsg_products');
	define('WPSG_TBL_PRODUCTS_GROUP', $prefix.'wpsg_productgroups'); 
	define('WPSG_TBL_MWST', $prefix.'wpsg_mwst'); // Deprecated ab 3.5 nur noch für Migrationszwecke! 
	define('WPSG_TBL_ORDER', $prefix.'wpsg_order');
	define('WPSG_TBL_ORDER_VOUCHER', $prefix.'wpsg_order_voucher');
	define('WPSG_TBL_ORDERPRODUCT', $prefix.'wpsg_order_products');
	define('WPSG_TBL_KU', $prefix.'wpsg_kunden');
	define('WPSG_TBL_KG', $prefix.'wpsg_customergroup');
	define('WPSG_TBL_VZ', $prefix.'wpsg_versandzonen');
	define('WPSG_TBL_LAND', $prefix.'wpsg_laender');
	define('WPSG_TBL_AT', $prefix.'wpsg_produktattribute');
	define('WPSG_TBL_PRODUCTS_AT', $prefix.'wpsg_products_attribute');
	define('WPSG_TBL_OL', $prefix.'wpsg_orderlog');
	define('WPSG_TBL_VA', $prefix.'wpsg_versandarten');
	define('WPSG_TBL_RECHNUNGEN', $prefix.'wpsg_rechnungen');
	define('WPSG_TBL_GUTSCHEIN', $prefix.'wpsg_gutscheine');
 	define('WPSG_TBL_PRODUCTS_REL', $prefix.'wpsg_products_related');
	define('WPSG_TBL_EXPORTPROFILE', $prefix.'wpsg_exportprofile');
	define('WPSG_TBL_EXPORTPROFILE_FIELDS', $prefix.'wpsg_exportprofile_fields');
 	define('WPSG_TBL_PRODUCTS_VARS', $prefix.'wpsg_productvars');
 	define('WPSG_TBL_PRODUCTS_STICKY', $prefix.'wpsg_products_sticky');
 	define('WPSG_TBL_ZV', $prefix.'wpsg_zahlvarianten');
 	define('WPSG_TBL_VIDEOINDIV', $prefix.'wpsg_videoindividualization');
	define('WPSG_TBL_PDFINDIV', $prefix.'wpsg_pdfindividualization');
	define('WPSG_TBL_ORDERVARS', $prefix.'wpsg_ordervars');
 	define('WPSG_TBL_ORDERCOND', $prefix.'wpsg_orderconditions');
 	define('WPSG_TBL_CABLOG', $prefix.'wpsg_cab_log');
 	define('WPSG_TBL_PAP', $prefix.'wpsg_pap_referenz');
 	define('WPSG_TBL_ABO', $prefix.'wpsg_abo');
 	define('WPSG_TBL_SCALEPRICE', $prefix.'wpsg_scaleprice');
 	define('WPSG_TBL_DELIVERYNOTE', $prefix.'wpsg_deliverynote');
 		
	// WPML Tabellen
	define('WPSG_TBL_WPML_ICL_STRINGS', $prefix.'icl_strings');
	define('WPSG_TBL_WPML_ICL_STRING_PAGES', $prefix.'icl_string_pages');
	define('WPSG_TBL_WPML_ICL_STRING_POSITIONS', $prefix.'icl_string_positions');
	define('WPSG_TBL_WPML_ICL_STRING_TRANSLATIONS', $prefix.'icl_string_translations');

	// Slug
 	define('WPSG_FOLDERNAME', basename(dirname(__FILE__)));
 	define('WPSG_SLUG', WPSG_FOLDERNAME.'/wpshopgermany.php');
	
 	// Eingabeüberprüfung
 	define('WPSG_SANITIZE_INT', 0);
	define('WPSG_SANITIZE_VALUES', 1);
	define('WPSG_SANITIZE_CHECKBOX', 2); 
	define('WPSG_SANITIZE_TEXTFIELD', 3);
	define('WPSG_SANITIZE_APIKEY', 4);
	define('WPSG_SANITIZE_FLOAT', 5);
	define('WPSG_SANITIZE_TAXKEY', 6);
	define('WPSG_SANITIZE_PATH', 7);
	define('WPSG_SANITIZE_URL', 8);
	define('WPSG_SANITIZE_TEXTAREA', 9);
	define('WPSG_SANITIZE_EMAIL', 10);
	define('WPSG_SANITIZE_HEXCOLOR', 11);
	define('WPSG_SANITIZE_PAGEID', 12);
	define('WPSG_SANITIZE_DATETIME', 13);
	define('WPSG_SANITIZE_ARRAY_INT', 14);
	define('WPSG_SANITIZE_HTML', 15);
 	define('WPSG_SANITIZE_COSTKEY', 16);
	define('WPSG_SANITIZE_NONE', 17);
 	define('WPSG_SANITIZE_EMAILNAME', 18);
	define('WPSG_SANITIZE_DATE', 19);
	define('WPSG_SANITIZE_USTIDNR', 20);
	define('WPSG_SANITIZE_ZIP', 21);
	define('WPSG_SANITIZE_DOMAIN', 22);
	define('WPSG_SANITIZE_ARRAY_LANG', 23);
 	
	// Ist in Multiblog manchma nicht definiert :? Sonst ist hier das Verzeichnis drin
	if (!defined('SITECOOKIEPATH')) define('SITECOOKIEPATH', '/');
	
	$wp_upload_dir = wp_upload_dir();
	 
	// Pfade
	define('WPSG_PATH', dirname(__FILE__).'/');
	define('WPSG_PATH_LIB', WPSG_PATH.'/lib/');
	define('WPSG_PATH_WP', ABSPATH);
	define('WPSG_PATH_CONTENT', WP_CONTENT_DIR.'/'); /* wp-content/ */
	define('WPSG_PATH_UPLOADS', $wp_upload_dir['basedir'].'/wpsg/');
	define('WPSG_PATH_VIEW', dirname(__FILE__).'/views/');
	define('WPSG_PATH_TEMPLATEVIEW', get_template_directory().'/wpsg_views/');
	define('WPSG_PATH_CHILD_TEMPLATEVIEW', get_stylesheet_directory().'/wpsg_views/');
	define('WPSG_PATH_USERVIEW', WPSG_PATH_UPLOADS.'user_views/');
	define('WPSG_PATH_USERVIEW_OLD', WPSG_PATH.'user_views/');
	define('WPSG_PATH_USERVIEW_UPLOADS', WPSG_PATH_UPLOADS.'user_views/');
	define('WPSG_PATH_MOD', dirname(__FILE__).'/mods/');	
	define('WPSG_PATH_USERMOD', WPSG_PATH_UPLOADS.'user_mods/');
	define('WPSG_PATH_PRODUKTTEMPLATES', WPSG_PATH_VIEW.'produkttemplates/');
	define('WPSG_PATH_PRODUKTTEMPLATES_UV', WPSG_PATH_USERVIEW.'produkttemplates/');
	define('WPSG_PATH_PRODUKTTEMPLATES_UV_OLD', WPSG_PATH_USERVIEW_OLD.'produkttemplates/');
	define('WPSG_PATH_PRODUKTTEMPLATES_TV', WPSG_PATH_TEMPLATEVIEW.'produkttemplates/');
	define('WPSG_PATH_PRODUKTTEMPLATES_CTV', WPSG_PATH_CHILD_TEMPLATEVIEW.'produkttemplates/');
	define('WPSG_URL', plugins_url().'/'.WPSG_FOLDERNAME.'/');
	define('WPSG_URL_WP', site_url().'/');
	define('WPSG_URL_CONTENT', content_url().'/');
	define('WPSG_URL_UPLOADS',  $wp_upload_dir['baseurl'].'/wpsg/');
	define('WPSG_URL_USERVIEW', $wp_upload_dir['baseurl'].'/wpsg/user_views/');
	define('WPSG_PLUGIN_URL', plugins_url().'/');
	define('WPSG_PATH_TRANSLATION', dirname(__FILE__).'/lib/translation.phtml');

	# "WordPress allows users to change the name of wp-content" --> "wp-content" oder mögliche, andere Formen
	define('WPSG_CONTENTDIR_WP', substr(content_url(), -(strlen(content_url()) - strrpos(content_url(), "/")) + 1));

	// Konstanten
	define('WPSG_BRUTTO', '0');
	define('WPSG_NETTO', '1');
	
	// Wird für die Sharif Überprüfung im Produkttemplate eingebunden
	if (!function_exists('is_plugin_active')) require_once(ABSPATH.'/wp-admin/includes/plugin.php');

	require_once(dirname(__FILE__).'/lib/functions.inc.php');
	require_once(dirname(__FILE__).'/lib/filter_functions.inc.php');
	require_once(dirname(__FILE__).'/lib/helper_functions.inc.php');
	require_once(dirname(__FILE__).'/lib/wpsg_db.class.php');
	require_once(dirname(__FILE__).'/lib/wpsg_imagehandler.class.php');
	require_once(dirname(__FILE__).'/lib/wpsg_cache.class.php');
	require_once(dirname(__FILE__).'/lib/wpsg_basket.class.php');    
	require_once(dirname(__FILE__).'/lib/wpsg_header.class.php');
	require_once(dirname(__FILE__).'/lib/wpsg_remoteconnection.class.php');
	require_once(dirname(__FILE__).'/lib/wpsg_exceptionhandler.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_exception.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_model.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_product.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_order.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_country.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_order_product.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_customer.class.php');
	require_once(dirname(__FILE__).'/model/wpsg_news.class.php');
	require_once(dirname(__FILE__).'/mods/wpsg_mod_basic.class.php');
    require_once(dirname(__FILE__).'/lib/wpsg_calculation.class.php');
	require_once(dirname(__FILE__).'/controller/wpsg_SystemController.class.php');
	require_once(dirname(__FILE__).'/controller/wpsg_ShopController.class.php');
	require_once(dirname(__FILE__).'/controller/wpsg_AdminController.class.php');
	require_once(dirname(__FILE__).'/controller/wpsg_ProduktController.class.php');	
	require_once(dirname(__FILE__).'/controller/wpsg_BasketController.class.php');
	require_once(dirname(__FILE__).'/controller/wpsg_OrderController.class.php');
	
	$_GET['wpsg_quotecheck'] = '\"CHECK';
	
	/** @var wpsg_db */
	$GLOBALS['wpsg_db'] = new wpsg_db();

	/** @var wpsg_imagehandler */
	$GLOBALS['wpsg_ih'] = new wpsg_imagehandler();

	/** @var wpsg_ShopController */
	$shop = new wpsg_ShopController();	
	
	// Nicht behandelte Exceptions werden hier verarbeitet
	set_exception_handler(array('\\wpsg\\exceptionhandler', 'exception'));
	
	if ($shop->isMultiBlog() && !is_admin() && $shop->get_option('wpsg_multiblog_sessionPath', true) == '1')
	{
		
		global $current_blog; 
		 
		session_set_cookie_params(0, $current_blog->path);
				
	}
	
	if (!session_id()) { session_start(); }

	// UPLOADS Dir für Multiblogunterstützung
	global $current_blog;

	if ($shop->isMultiBlog()) {
	
		$path = $shop->get_option('wpsg_path_upload_multiblog', true);
		$path = preg_replace('/%blog_id%/', $current_blog->blog_id, $path);
	
		define('WPSG_MB_UPLOADS', $path);
	
	}

	$shop->initShop($prefix);
	 
	// Standardrollen für die Rechteverteilung 
	$role_object = get_role('administrator');

    $role_object = get_role('administrator');
    
    if (is_object($role_object)) {
    
        $role_object->add_cap('wpsg_menu');
        $role_object->add_cap('wpsg_conf');
        $role_object->add_cap('wpsg_order');
        $role_object->add_cap('wpsg_produkt');
        $role_object->add_cap('wpsg_lizence');
    }

    register_activation_hook(__FILE__, 'wpsg_install');
	register_deactivation_hook(__FILE__, 'wpsg_uninstall');
	
	add_action('admin_menu', 'wpsg_add_pages');	
	add_action('phpmailer_init', 'wpsg_phpmailer_init');
	add_action('shutdown', 'wpsg_shutdown');
	add_action('wp_loaded', array($shop, 'wp_load'));
    add_action('admin_bar_menu', 'wpsg_admin_bar_menu', 2000);

	add_filter('upgrader_pre_install', 'wpsg_pre_install', 10, 2);
	add_filter('upgrader_post_install', 'wpsg_post_install', 10, 2);


	if (is_admin())
	{

		add_filter('plugins_api', 'wpsg_info', 10, 3);
		add_filter('site_transient_update_plugins', 'wpsg_update');
		
		add_action('admin_enqueue_scripts', array($shop, 'wp_enqueue'));
		add_action('admin_init', 'wpsg_admin_init');
		add_action('admin_footer', 'wpsg_admin_footer');
        add_action('in_plugin_update_message-wpshopgermany/wpshopgermany.php', 'wpsg_updateNotification', 10, 2);
        add_action('admin_notices', 'wpsg_admin_notices');

	} 
	else
	{
		
		add_shortcode('wpshopgermany', array($shop, 'shortcode'));
		add_shortcode('wpshopgermany_link', array($shop, 'shortcode_basket'));
		
		add_filter('the_title', array($shop, 'the_title'), 10, 2);
		add_filter('the_content', array($shop, 'content_filter'));	
		add_filter('the_excerpt', array($shop, 'the_excerpt'));	 
		add_filter('pre_get_posts', array($shop, 'pre_get_posts'));
				
		add_action('wp_enqueue_scripts', array($shop, 'wp_enqueue'));
		add_action('wp_head', array($shop, 'wp_head'));
		add_action('wp_footer', array($shop, 'wp_foot'));
		add_action('template_redirect', array($shop, 'template_redirect'));
					
		if ($shop->get_option('wpsg_removeWpTrimExcerpt') == '1') remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		if ($shop->get_option('wpsg_removeWpAutoOp') == '1') remove_filter('the_content', 'wpautop');
		
	}

    if (!wp_next_scheduled('wpsg_daily_hook')) wp_schedule_event(time(), 'daily', 'wpsg_daily_hook');
		
	add_action('init', [$shop, 'init']);
	add_action('widgets_init', array($shop, 'widget_init'));
    add_action('wpsg_daily_hook', [$shop, 'wpsg_daily_hook']);
    
	$shop->callMods('load', array());
	