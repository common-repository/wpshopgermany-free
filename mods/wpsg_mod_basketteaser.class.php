<?php

	class wpsg_mod_basketteaser extends wpsg_mod_basic {
		
		var $lizenz = 2;
		var $id = 105;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Warenkorbteaser', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht es im Warenkorb Produkte anzupreisen.', 'wpsg');
									
		} // public function __construct()
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/**
			 * Kundentabelle erweitern
			 */ 
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
		   		mod_basketteaser_from date NOT NULL,
		   		mod_basketteaser_to date NOT NULL,
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	   	 
   			dbDelta($sql);
   			
   			if ($this->shop->get_option('wpsg_mod_basketteaser_template') === false || trim($this->shop->get_option('wpsg_mod_basketteaser_template')) == '') $this->shop->update_option('wpsg_mod_basketteaser_template', 'basketteaser.phtml');
   			if ($this->shop->get_option('wpsg_mod_basketteaser_show') === false || trim($this->shop->get_option('wpsg_mod_basketteaser_show')) == '') $this->shop->update_option('wpsg_mod_basketteaser_show', '0'); 
			
		} // public function install()
		 		
		public function settings_edit() {
			
			$this->shop->view['templates'] = $this->shop->loadProduktTemplates(true);
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_basketteaser/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() { 
			
			$arProductTemplates = $this->shop->loadProduktTemplates(true);
			
			$this->shop->update_option('wpsg_mod_basketteaser_template', $_REQUEST['wpsg_mod_basketteaser_template'], false, false, "wpsg_in_array", [
				[0] + array_keys($arProductTemplates)
			]);
			
			$this->shop->update_option('wpsg_mod_basketteaser_show', $_REQUEST['wpsg_mod_basketteaser_show'], false, false, WPSG_SANITIZE_VALUES, ['0', '1']);
			
		} // public function settings_save()
		 
		public function basket_after(&$basket_view) { 
			
			if ($this->shop->get_option('wpsg_mod_basketteaser_show') != '0') return;
									
			$arTeaserProducts = $this->getTeaserProducts();
			
			if (wpsg_isSizedArray($arTeaserProducts))
			{
				
				foreach ($arTeaserProducts as $p)
				{
					
					echo $this->shop->renderProdukt($p['id'], $this->shop->get_option('wpsg_mod_basketteaser_template'), false);
					
				}
				
			}
			
		} // public function basket_inner_prebutton(&$basket_view)
		
		public function basket_row_afterproducts(&$p, $i) {
			
			if ($this->shop->get_option('wpsg_mod_basketteaser_show') != '1') return;
			
			$arTeaserProducts = $this->getTeaserProducts();
			
			if (wpsg_isSizedArray($arTeaserProducts))
			{
				
				foreach ($arTeaserProducts as $p)
				{
					
					$this->shop->view['data'] = $this->shop->loadProduktArray($p['id']);
					$this->shop->view['i'] = $i;
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_basketteaser/basket_row.phtml');
					
				}
				
			}
			
		} // public function basket_row_afterproducts(&$p, $i)

		public function product_addedit_content(&$product_content, &$product_data)
		{
		
			if (isset($_REQUEST['wpsg_lang'])) return;
			
			$this->shop->view['data'] = $product_data;
			$this->shop->view['wpsg_mod_basketteaser']['data'] = $product_data;
		
			$product_content['wpsg_mod_basketteaser'] = array(
					'title' => __('Warenkorbteaser', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_basketteaser/produkt_addedit_sidebar.phtml', false)
			);
		
		} //public function product_addedit_content(&$product_content, &$product_data)
		
		public function produkt_save_before(&$produkt_data) { 
			
			$produkt_data['mod_basketteaser_from'] = wpsg_toDate(wpsg_sinput("key", $_REQUEST['mod_basketteaser_from']));
			$produkt_data['mod_basketteaser_to'] = wpsg_toDate(wpsg_sinput("key", $_REQUEST['mod_basketteaser_to']));
						 
		} // public function produkt_save_before(&$produkt_data)
		
		public function basket_preUpdate() { 
			
			if (wpsg_isSizedArray($_REQUEST['wpsg_mod_basketteaser_row']))
			{
				
				foreach ($_REQUEST['wpsg_mod_basketteaser_row'] as $p_id => $p) 
				{
					
					if ($p > 0)
					{
					
						$this->shop->basket->addProduktToSession($p_id, $p);

					}
					
				}
				
			}
			
		} // public function basket_preUpdate()
		
		/* -- */
		
		/**
		 * Gibt einen Array mit Produktids zurück für die aktuell anzuteasernden Produkte
		 */
		private function getTeaserProducts()
		{
			
			$arProdukteBasket = array();
			
			if (is_object($this->shop->basket) && is_array($this->shop->basket->toArray()))
			{
				
				$arBasket = $this->shop->basket->toArray();
				 
				foreach ($arBasket['produkte'] as $p)
				{
					
					if (preg_match('/pv_(.*)/', $p['id']))
					{
						$produkt_id = preg_replace('/(pv_)|(\|(.*))/', '', $p['id']);
					}
					else 
					{
						$produkt_id = $p['id'];
					}
					
					$arProdukteBasket[] = $produkt_id;
					
				}
				
			} 
			
			$strQuery = "
				SELECT
					P.`id`
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
				WHERE
					NOW() > P.`mod_basketteaser_from` AND
					NOW() < P.`mod_basketteaser_to` AND
					P.`deleted` != '1'
			";
			
			if (wpsg_isSizedArray($arProdukteBasket))
			{
			
				$strQuery .= " AND P.`id` NOT IN (".implode(",", $arProdukteBasket).") ";
				
			}
			
			return $this->db->fetchAssoc($strQuery);
			
		} // private function getTeaserProducts()
		
	} // class wpsg_mod_basketteaser extends wpsg_mod_basic

?>