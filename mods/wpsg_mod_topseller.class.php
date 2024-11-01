<?php

	/**
	 * Klasse, die es ermöglicht Produkte als "Topseller" auszuzeichnen
	 * @author daniel 
	 */
	class wpsg_mod_topseller extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 1950;

		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('TopSeller', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht es Produkte als "TopSeller" darzustellen.', 'wpsg');
			 			
		} // public function __construct()
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/*
			 * Posts Tabelle erweitern
			 */ 
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
		   		wpsg_mod_topseller INT(1) NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	   	 
   			dbDelta($sql);
   			
   			// Default Werte
   			$this->shop->checkDefault('wpsg_mod_topseller_auto', '1');
   			$this->shop->checkDefault('wpsg_mod_topseller_limit', '4');
   			$this->shop->checkDefault('wpsg_mod_topseller_template', 'standard.phtml');
			
		} // public function install()
		
		public function settings_edit()
		{

			// Verfügbare Produkttemplates
			$this->shop->view['arTemplates'] = $this->shop->loadProduktTemplates();
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_topseller/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_topseller_auto', $_REQUEST['wpsg_mod_topseller_auto'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_topseller_limit', $_REQUEST['wpsg_mod_topseller_limit'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_topseller_template', $_REQUEST['wpsg_mod_topseller_template'], false, false, WPSG_SANITIZE_INT);
			
		} // public function settings_save()
		
		public function product_addedit_content(&$product_content, &$product_data)
		{
		
			if (isset($_REQUEST['wpsg_lang'])) return;
			
			$this->shop->view['wpsg_mod_topseller']['status'] = wpsg_getStr($product_data['wpsg_mod_topseller']);
			
			$this->shop->view['wpsg_mod_topseller']['data'] = $product_data;
		
			$product_content['general']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_topseller/produkt_addedit_allgemein.phtml', false);			
		
		} //public function product_addedit_content(&$product_content, &$product_data)
		
		public function produkt_save_before(&$produkt_data) {
			
			wpsg_checkRequest('wpsg_mod_topseller', [WPSG_SANITIZE_CHECKBOX], __('Topseller Status'), $produkt_data, $_REQUEST['wpsg_mod_topseller']['status']);
			
		} // public function produkt_save_before(&$produkt_data)
				
		public function init()
		{
			
			add_shortcode('wpsg_topseller', array($this, 'shortcode'));
			
		} // public function init()
		
		public function load() 
		{ 

			require_once(dirname(__FILE__).'/mod_topseller/wpsg_mod_topseller_widget.class.php');

			add_action('widgets_init', function() { return register_widget("wpsg_mod_topseller_widget"); } );
			
		} // public function load()
		
		/** Modulfunktionen */
		
		/**
		 * Ersetzt den Shortcode durch die Ausgabe 
		 */
		public function shortcode($atts)
		{
			
			if (!wpsg_isSizedArray($atts)) $atts = array();
			
			$template = false; if (wpsg_isSizedString($atts['template'])) $template = $atts['template'];
			$limit = $this->shop->get_option('wpsg_mod_topseller_limit'); if (wpsg_isSizedInt($atts['limit'])) $limit = $atts['limit'];  
			
			return $this->renderTopSeller($template, $limit, false);
						
		} // public function shortcode($atts)
		
		/**
		 * Zeichent die Liste der TopSeller für das Frontend
		 * @param unknown_type $template
		 * @param unknown_type $limit
		 */
		public function renderTopSeller($template = false, $limit = false, $out = true)
		{
			
			if ($template === false || !wpsg_isSizedString($template))
			{
				
				$template = $this->shop->get_option('wpsg_mod_topseller_template');
				
			}
			
			$this->shop->view['template'] = $template; 
			$this->shop->view['topseller'] = $this->getTopSeller($limit);
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_topseller/topseller.phtml', $out);
			
		} // public function renderTopSeller($template = false, $limit = false)
		
		/**
		 * Gibt einen Array von ProduktIDs und Anzahl an Verkäufen zurück die TopSeller sind
		 */
		public function getTopSeller($limit = false)
		{

			$strQueryLIMIT = '';
			$arTopSeller = array();
			
			if (wpsg_isSizedInt($limit))
			{

				$strQueryLIMIT = " LIMIT ".$limit." ";
				
			}
			
			if ($this->shop->get_option('wpsg_mod_topseller_auto') == '1')
			{
				
				$arTopSeller = $this->shop->callMod('wpsg_mod_statistics', 'loadTopProducts', array(&$strQueryLIMIT));
				 				 
			}
			else
			{
				
				$arTopSeller = $this->db->fetchAssoc("
					SELECT
						P.`id`, SUM(OP.`menge`) AS `count_buy`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
							LEFT JOIN `".WPSG_TBL_ORDERPRODUCT."` AS OP ON (P.`id` = OP.`p_id`)
					WHERE
						P.`deleted` != '1' AND 
						P.`wpsg_mod_topseller` = '1'
					GROUP BY
				 		P.`id`
					ORDER BY
						`count_buy` DESC
					".$strQueryLIMIT."
				");
				
			}
			
			return $arTopSeller;
			
		} // public function getTopSeller($limit = false)
		
	} // class wpsg_mod_topseller

?>