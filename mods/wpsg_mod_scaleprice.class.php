<?php

	/**
	 * Modul zur verwaltung von Preisstaffeln
	 * Die Konfiguration der Preisstaffeln erfolgt nach Aktivierung im Modul Produke
	 * Ist nur für die Lizenztypen Pro und Enterprise verfügbar!
	 * @author daniel
	 */
	class wpsg_mod_scaleprice extends wpsg_mod_basic 
	{
				
		var $lizenz = 1;
		var $id = 97;
		var $hilfeURL = 'http://wpshopgermany.maennchen1.de/?p=3672';
		
		const TYP_QUANTITY = 0;
		const TYP_WEIGHT = 1;
		
		const CALC_REPLACE = 0;
		const CALC_ADD = 1;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
		
			parent::__construct();
		
			$this->name = __('Staffelpreise', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht eine gestaffelte Preisgestaltung', 'wpsg');
		
		} // public function __construct()
		
		public function install()
		{
				
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
				
			/**
			 * Produkttabelle erweitern
			*/
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
			  	wpsg_mod_scaleprice_activ int(1) DEFAULT 0 NOT NULL,
				wpsg_mod_scaleprice_typ int(11) DEFAULT 0 NOT NULL,	
				wpsg_mod_scaleprice_calc int(11) DEFAULT 0 NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
					
			dbDelta($sql);
			
			/**
			 * Tabelle für die Staffelpreise
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_SCALEPRICE." (
				id int(11) NOT NULL AUTO_INCREMENT,
				product_id int(11) DEFAULT 0 NOT NULL,
				scale double(10,2) DEFAULT 0 NOT NULL,
                scaleeinheit varchar(50) NOT NULL,	
				value double(10,2) DEFAULT 0 NOT NULL,
				PRIMARY KEY  (id),
				KEY product_id (product_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		 
			dbDelta($sql);
			
			$this->shop->checkDefault('wpsg_mod_scaleprice_showProductInfo', '1'); 
			$this->shop->checkDefault('wpsg_mod_scaleprice_unit', 'Stück');
			
		} // public function install()
		
		public function settings_edit()
		{
		    
		    $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/settings_edit.phtml');
		    
		} // public function settings_edit()
		
		public function settings_save()
		{
		    
		    $this->shop->update_option('wpsg_mod_scaleprice_unit', $_REQUEST['wpsg_mod_scaleprice_unit'], false, false, WPSG_SANITIZE_TEXTFIELD);
		
		} // public function settings_save()
		
		/*
		 * zeigt die Staffelpreise im Produktbackend an
		*/
		public function product_addedit_content(&$product_content, &$product_data)
		{
		
			if (!wpsg_isSizedInt($product_data['id'])) return;
			
			$this->shop->view['wpsg_mod_scaleprice']['product'] = $product_data; 
			
			$this->shop->view['wpsg_mod_scaleprice']['arTyp'] = array(
				wpsg_mod_scaleprice::TYP_QUANTITY => __('Menge', 'wpsg')	
			);
			
			if ($this->shop->hasMod('wpsg_mod_weight'))
			{
				
				$this->shop->view['wpsg_mod_scaleprice']['arTyp'][wpsg_mod_scaleprice::TYP_WEIGHT] = __('Gewicht', 'wpsg');
				
			}
			
			$this->shop->view['wpsg_mod_scaleprice']['list'] = $this->scaleList($product_data['id']);
		
			/*$product_content['wpsg_mod_scaleprice'] = array(
					'title' => __('Staffelpreise', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/produkt_addedit_sidebar.phtml', false)		
			);
			*/
			
			$product_content['price']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/produkt_addedit_sidebar.phtml', false);
			
		
		} //public function product_addedit_content(&$product_content, &$product_data)
		
		
		/*
		public function produkt_edit_sidebar(&$produkt_data) 
		{ 
			
			if (!isset($produkt_data['id'])) return;
			
			$this->shop->view['wpsg_mod_scaleprice']['product'] = $produkt_data; 
			
			$this->shop->view['wpsg_mod_scaleprice']['arTyp'] = array(
				wpsg_mod_scaleprice::TYP_QUANTITY => __('Menge', 'wpsg')	
			);
			
			if ($this->shop->hasMod('wpsg_mod_weight'))
			{
				
				$this->shop->view['wpsg_mod_scaleprice']['arTyp'][wpsg_mod_scaleprice::TYP_WEIGHT] = __('Gewicht', 'wpsg');
				
			}
			
			$this->shop->view['wpsg_mod_scaleprice']['list'] = $this->scaleList($produkt_data['id']);
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/produkt_edit_sidebar.phtml');
			
		} // public function produkt_edit_sidebar(&$produkt_data)
		*/
		
		public function produkt_save_before(&$produkt_data) 
		{ 

			$produkt_data['wpsg_mod_scaleprice_activ'] = wpsg_q(wpsg_sinput("key", $_REQUEST['wpsg_mod_scaleprice_activ']));
			$produkt_data['wpsg_mod_scaleprice_typ'] = wpsg_q(wpsg_sinput("key", $_REQUEST['wpsg_mod_scaleprice_typ']));
			$produkt_data['wpsg_mod_scaleprice_calc'] = wpsg_q(wpsg_sinput("key", $_REQUEST['wpsg_mod_scaleprice_calc']));
			
		} // public function produkt_save_before(&$produkt_data)
		
		public function produkt_ajax()
		{

			if(isset($_REQUEST['edit_id'])) $_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);
			if(isset($_REQUEST['scale_id'])) $_REQUEST['scale_id'] = wpsg_sinput("key", $_REQUEST['scale_id']);

			if ($_REQUEST['cmd'] == 'add')
			{
				 
				$this->db->ImportQuery(WPSG_TBL_SCALEPRICE, array(
					'product_id' => wpsg_q($_REQUEST['edit_id']),
					'scale' => wpsg_q(wpsg_sinput("key", $_REQUEST['scale'])),
					'value' => wpsg_q(wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat")))
				));
				
				die($this->scaleList($_REQUEST['edit_id']));
				
			}
			else if ($_REQUEST['cmd'] == 'refresh')
			{
				
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array(
					'wpsg_mod_scaleprice_activ' => '1',
					'wpsg_mod_scaleprice_typ' => wpsg_q(wpsg_sinput("key", $_REQUEST['typ'])),
					'wpsg_mod_scaleprice_calc' => wpsg_q(wpsg_sinput("key", $_REQUEST['calc']))
				), "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
				
				die($this->scaleList($_REQUEST['edit_id'], $_REQUEST['typ']));
				
			}
			else if ($_REQUEST['cmd'] == 'ajaxSave')
			{
				
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array(
					'wpsg_mod_scaleprice_activ' => '1',
					'wpsg_mod_scaleprice_typ' => wpsg_q(wpsg_sinput("key", $_REQUEST['typ'])),
					'wpsg_mod_scaleprice_calc' => wpsg_q(wpsg_sinput("key", $_REQUEST['calc']))
				), "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
				
				die("1");
				
			}
			else if ($_REQUEST['cmd'] == 'remove')
			{
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_SCALEPRICE."` WHERE `id` = '".wpsg_q($_REQUEST['scale_id'])."'");
				
				die("1");
				
			}
			else if ($_REQUEST['cmd'] == 'inlineEdit')
			{
				
				if ($_REQUEST['field'] == 'scale')
				{

					$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

					$this->db->UpdateQuery(WPSG_TBL_SCALEPRICE, array('scale' => wpsg_q($_REQUEST['value'])), "`id` = '".wpsg_q($_REQUEST['scale_id'])."'");
					
					die(wpsg_ff(wpsg_tf($_REQUEST['value'])));
					
				}
				else if ($_REQUEST['field'] == 'value')
				{

					$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

					$this->db->UpdateQuery(WPSG_TBL_SCALEPRICE, array('value' => wpsg_q($_REQUEST['value'])), "`id` = '".wpsg_q($_REQUEST['scale_id'])."'");
					
					die(wpsg_ff(wpsg_tf($_REQUEST['value'])));
					
				}
				
			}
			
		} // public function produkt_ajax()		 

		public function admin_presentation()
		{
			
			echo wpsg_drawForm_Checkbox('wpsg_mod_scaleprice_showProductInfo', __('Preisstaffel im Produkttemplate anzeigen', 'wpsg'), $this->shop->get_option('wpsg_mod_scaleprice_showProductInfo'));
			
		} // public function admin_presentation()
		
		public function admin_presentation_submit()
		{
			
			$this->shop->update_option('wpsg_mod_scaleprice_showProductInfo', $_REQUEST['wpsg_mod_scaleprice_showProductInfo'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function admin_presentation_submit()
		
		public function product_bottom(&$produkt_key, $template_index) 
		{ 
			
			if ($this->shop->get_option('wpsg_mod_scaleprice_showProductInfo') != '1') return;
			
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($produkt_key));
			
			if ($oProduct->wpsg_mod_scaleprice_activ != '1') return;
						
			$arScale = $this->db->fetchAssoc("
				SELECT
					S.`scale`, S.`value`
				FROM
					`".WPSG_TBL_SCALEPRICE."` AS S
				WHERE
					S.`product_id` = '".wpsg_q($this->shop->getProduktID($produkt_key))."'
				ORDER BY
					S.`scale` ASC
			");
						
			if (!wpsg_isSizedArray($arScale)) return;
			
			foreach ($arScale as $k => $v) {
				
				if ($oProduct->wpsg_mod_scaleprice_typ == self::TYP_QUANTITY) {
			 
					$arScale[$k]['value'] = $oProduct->getPrice($produkt_key, $this->shop->getFrontendTaxview(), $v['scale'], false);
					
				} else {
					
					$arScale[$k]['value'] = $oProduct->getPrice($produkt_key, $this->shop->getFrontendTaxview(), false, $v['scale']);
					
				}
				
			}
			
			$this->shop->view['wpsg_mod_scaleprice']['base'] = $oProduct->getPrice($produkt_key, $this->shop->getFrontendTaxview(),false, false, false);
			$this->shop->view['wpsg_mod_scaleprice']['scale'] = $arScale;			
			$this->shop->view['wpsg_mod_scaleprice']['typ'] = $oProduct->wpsg_mod_scaleprice_typ;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/product_bottom.phtml');
			  
		} // public function product_bottom(&$produkt_id, $template_index)
		
		/**
		 * @param wpsg_product $oProduct
		 * @param double $price_netto
		 * @param double $price_brutto
		 * @param string Produktschlüssel
		 */
		public function product_getPrice(&$oProduct, &$price_netto, &$price_brutto, $product_key, $amount, $weight) { 
			 
			if ($product_key === false) $product_key = $oProduct->id;
			
			if ($oProduct->wpsg_mod_scaleprice_activ === '1') {
				
				if ($oProduct->wpsg_mod_scaleprice_typ == wpsg_mod_scaleprice::TYP_QUANTITY) {
					
					if ($amount === false) {
					
						$amount_basket = 0;
					
						// Menge im Warenkorb bestimmen
						if (wpsg_isSizedArray($_SESSION['wpsg']['basket'])) {
						
							foreach ($_SESSION['wpsg']['basket'] as $product_index => $basket_data) {
								
								if ($product_key === $basket_data['id']) $amount_basket += $basket_data['menge'];
								
							}
						
						}
					
					} else $amount_basket = $amount;
						 
					$scale = $amount_basket;
					
				} else if ($this->shop->hasMod('wpsg_mod_weight') && $oProduct->wpsg_mod_scaleprice_typ == wpsg_mod_scaleprice::TYP_WEIGHT) {
					
					$weight_basket = 0;
					
					if ($weight === false) {
					
						// Menge im Warenkorb bestimmen					
						foreach ($_SESSION['wpsg']['basket'] as $product_index => $basket_data) {
							
							if ($this->shop->getProduktID($basket_data['id']) == $oProduct->id) {
								
								$weight_basket += $basket_data['menge'] * $this->shop->callMod('wpsg_mod_weight', 'getWeight', [$product_key]);
								
							}
							
						}
						
					} else {
						
						$weight_basket += $weight;
						
					}
					 
					$scale = $weight_basket;
										
				} else throw new \wpsg\Exception(__('Ungültige Preisberechnung bei Produkttemplate', 'wpsg'));
				
				if ($this->shop->getBackendTaxview() === WPSG_BRUTTO) {
					
					$price_brutto = $this->getScalePrice($oProduct, $price_brutto, $scale);
					$price_netto = wpsg_calculatePreis($price_brutto, WPSG_NETTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
					
				} else {
				
					$price_netto = $this->getScalePrice($oProduct, $price_netto, $scale);
					$price_brutto = wpsg_calculatePreis($price_netto, WPSG_BRUTTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
					
				}
				
			}
			
		}
		
		public function loadProduktArray0(&$product_data)
		{
 
			$product_key = $product_data['id'];
			$product_id = $this->shop->getProduktId($product_key);
			$product_data_db = $this->shop->cache->loadProduct($product_id);

			if ($product_data_db['wpsg_mod_scaleprice_activ'] == '1')
			{
				
				if ($product_data_db['wpsg_mod_scaleprice_typ'] == wpsg_mod_scaleprice::TYP_QUANTITY)
				{

					$scale = ((isset($product_data['menge']))?$product_data['menge']:0);
					 
				}
				else if ($this->shop->hasMod('wpsg_mod_weight') && $product_data_db['wpsg_mod_scaleprice_typ'] == wpsg_mod_scaleprice::TYP_WEIGHT)
				{
				
					$scale = $this->shop->callMod('wpsg_mod_weight', 'getWeight', array($product_key));
				
				}
				else throw new \wpsg\Exception(__('Ungültige Preisberechnung bei Produkttemplate', 'wpsg'));
				
				if ($this->shop->get_option('wpsg_preisangaben') == WPSG_NETTO)
				{
					
					$preis_netto = $this->getScalePrice($product_id, $product_data['preis_netto'], $scale);
					$preis_brutto = wpsg_calculatePreis($preis_netto, WPSG_BRUTTO, $product_data['mwst_value']);
					
				}
				else
				{
					
					$preis_brutto = $this->getScalePrice($product_id, $product_data['preis_brutto'], $scale);
					$preis_netto = wpsg_calculatePreis($preis_brutto, WPSG_NETTO, $product_data['mwst_value']);
					
				}
				 
				$product_data['preis_brutto'] = $preis_brutto;
				$product_data['preis_netto'] = $preis_netto;
								
				$product_data['preis_defaultLand_brutto'] = $preis_brutto;
				$product_data['preis_defaultLand_netto'] = $preis_netto;
				
				if ($this->shop->getFrontendTaxview() == WPSG_NETTO)
				{
					
					$product_data['preis'] = $product_data['preis_netto'];
					$product_data['preis_defaultLand'] = $product_data['preis_netto'];
										
				}
				else
				{
					
					$product_data['preis'] = $product_data['preis_brutto'];
					$product_data['preis_defaultLand'] = $product_data['preis_brutto'];
					
				}
				
				$product_data['min_preis'] = $product_data['preis'];
				$product_data['ax_preis'] = $product_data['preis'];
				
			}
			
			//wpsg_debug("Staffel:loadProduktArray = ".$product_data['preis']);
			
		} // public function loadProduktArray(&$product_data)
		 		
		/* Modulfunktionen */
		
		private function getScalePrice($oProduct, $price, $scale)
		{
			
			$arScale = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_SCALEPRICE."` WHERE `product_id` = '".wpsg_q($oProduct->id)."' ORDER BY `scale` ASC");
			
			$set_scale = false;
			
			foreach ($arScale as $s) {
				 
				if (wpsg_tf($scale) >= wpsg_tf($s['scale'])) {
					
					$set_scale = wpsg_tf($s['value']);
					//break;
					
				}
				
			}
			
			if ($set_scale !== false) {
				
				if ($oProduct->wpsg_mod_scaleprice_calc == wpsg_mod_scaleprice::CALC_REPLACE) { 
					
					return $set_scale;
					
				} else if ($oProduct->wpsg_mod_scaleprice_calc == wpsg_mod_scaleprice::CALC_ADD) {
					
					return $price + $set_scale;
					
				} else throw new \wpsg\Exception(__('Ungültige Preisberechnung im Frontend', 'wpsg'));
				
			} else {
			
				return $price;
				
			}
			
		} // private function getStaffel($product_id)
		
		private function scaleList($product_id, $typ = false)
		{
			
			if ($typ === false)
			{
			
				$this->shop->view['wpsg_mod_scaleprice']['typ'] = $this->db->fetchOne("SELECT `wpsg_mod_scaleprice_typ` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($product_id)."'");
				
			}
			else
			{
				
				$this->shop->view['wpsg_mod_scaleprice']['typ'] = $typ;
				
			}
			
			$this->shop->view['wpsg_mod_scaleprice']['arScale'] = $this->db->fetchAssoc("
				SELECT
					*
				FROM
					`".WPSG_TBL_SCALEPRICE."`
				WHERE
					`product_id` = '".wpsg_q($product_id)."'
				ORDER BY
					`scale` ASC
			");
			 
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_scaleprice/scaleList.phtml', false);
			
		} // private function scaleList($product_id)
		
	} // class wpsg_mod_scaleprice extends wpsg_mod_basic
