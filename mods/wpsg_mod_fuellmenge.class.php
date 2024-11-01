<?php

	/**
	 * Modul für die Füllmengenberechnung
	 */
	class wpsg_mod_fuellmenge extends wpsg_mod_basic
	{
		
		var $id = 701;		
		var $lizenz = 2;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Füllmengen', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Erlaubt das angeben der Füllmenge und die Berechnung des Grundpreises. Notwendig bei Flächenprodukten, Flüssigkeiten, o.ä. Produkten.<br />nähere Details siehe <a href="http://www.gesetze-im-internet.de/pangv/index.html" target="">Preisangabenverordnung</a>.', 'wpsg');
						
		} // public function __construct()
		
		public function install() 
		{ 

			if ($this->shop->get_option('wpsg_mod_fuellmenge_einheit') === false || $this->shop->get_option('wpsg_mod_fuellmenge_einheit') == '') $this->shop->update_option('wpsg_mod_fuellmenge_einheit', 'kg,l,m³,m,m²');
			if ($this->shop->get_option('wpsg_mod_fuellmenge_bezug') === false || $this->shop->get_option('wpsg_mod_fuellmenge_bezug') == '') $this->shop->update_option('wpsg_mod_fuellmenge_bezug', '1');
						
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Produkttabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
			  	feinheit varchar(50) NOT NULL,
			  	fmenge varchar(50) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
		}
		
		public function settings_edit()
		{
						
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() 
		{
			
		    $this->shop->update_option('wpsg_mod_fuellmenge_einheit', $_REQUEST['wpsg_mod_fuellmenge_einheit'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_fuellmenge_bezug', $_REQUEST['wpsg_mod_fuellmenge_bezug'], false, false, WPSG_SANITIZE_FLOAT);
		    $this->shop->update_option('wpsg_mod_fuellmenge_showAjaxDialog', $_REQUEST['wpsg_mod_fuellmenge_showAjaxDialog'], false, false, WPSG_SANITIZE_CHECKBOX);
			if ($this->shop->hasMod('wpsg_mod_fuellmenge') == '1')
			{
				
			    $this->shop->update_option('wpsg_mod_fuellmenge_showProductindex_fmenge', $_REQUEST['wpsg_mod_fuellmenge_showProductindex_fmenge'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->shop->update_option('wpsg_mod_fuellmenge_showProductindex_grundpreis', $_REQUEST['wpsg_mod_fuellmenge_showProductindex_grundpreis'], false, false, WPSG_SANITIZE_FLOAT);
				
			}
			$this->shop->update_option('wpsg_mod_fuellmenge_showBasketProduct', $_REQUEST['wpsg_mod_fuellmenge_showBasketProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_fuellmenge_showProductindexBackend_fmenge', $_REQUEST['wpsg_mod_fuellmenge_showProductindexBackend_fmenge'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_fuellmenge_showOverviewProduct', $_REQUEST['wpsg_mod_fuellmenge_showOverviewProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod:fuellmenge_showRequestPageProduct', $_REQUEST['wpsg_mod_fuellmenge_showRequestPageProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function settings_save()
		
		public function basket_row(&$p, $i)
		{
			
			if ($this->shop->get_option('wpsg_mod_fuellmenge_showBasketProduct') != '1') return;
						
			$this->shop->view['p'] = $p;
			$this->shop->view['i'] = $i; 
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/basket_row.phtml');
					
		} // public function basket_row(&$p, $i)
		
		public function overview_row(&$p, $i) 
		{
			if ($this->shop->get_option('wpsg_mod_fuellmenge_showOverviewProduct') != '1') return; 
			
			$this->shop->view['i'] = $i; 
			$this->shop->view['p'] = $p; 
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/overview_row.phtml');
			
		} //public function overview_row(&$p, $i)
		
		public function basket_toArray(&$produkt, $backend = false, $noMwSt = false)
		{
		
			$produkt['fuellmenge_one'] = $this->getFuellmenge($produkt['id']);
			$produkt['fuellmenge'] = $this->getFuellmenge($produkt['id']) * $produkt['menge'];
		
		} //public function basket_toArray(&$produkt, $backend = false; $noMwSt = false)
		
		public function basket_toArray_preshippayment(&$basket, &$arBasket)
		{
		
			if ($basket->loadFromSession)
			{
		
				$fuellmenge_sum = 0;
		
				foreach ($arBasket['produkte'] as $k => $p)
				{
		
					$fuellmenge_sum += $p['fuellmenge'];
		
				}
		
				$arBasket['sum']['fuellmenge'] = $fuellmenge_sum;
		
			}
			else if ($basket->o_id > 0)
			{
		
				$order_data = $this->shop->cache->loadOrder($basket->o_id);
		
				$arBasket['sum']['fuellmenge'] = $order_data['fuellmenge'];
		
			}
		
		} // public function basket_toArray_final(&$basket, &$arBasket)
		
		public function getFuellmenge($product_key)
		{
		
			$product_data_db = $this->shop->cache->loadProduct($this->shop->getProduktId($product_key));
		
			if ($this->shop->hasMod('wpsg_mod_productvariants') && $this->shop->callMod('wpsg_mod_productvariants', 'isVariantsProductKey', array($product_key)))
			{
		
				$variInfo = $this->shop->callMod('wpsg_mod_productvariants', 'getVariantenInfoArray', array($product_key));
				$fuellmenge = @$product_data_db['fuellmenge'] + @$variInfo['fuellmenge'];
		
			}
			else
			{
		
				$fuellmenge = @$product_data_db['fuellmenge'];
		
			}
		
			return $fuellmenge;
		
		} // public function getFuellmenge($produkt_key)
		
		public function product_addedit_content(&$product_content, &$product_data) {
		
			if (!wpsg_isSizedInt($product_data['id'])) return null;
			
			if (wpsg_isSizedInt($product_data['id']))
			{
				
				$product_data = wpsg_array_merge($product_data, $this->db->fetchRow("
					SELECT
						`feinheit`, `fmenge`
					FROM
						`".WPSG_TBL_PRODUCTS."`
					WHERE
						`id` = '".wpsg_q($product_data['id'])."'
				"));
				
			}
		
			$product_data['arFeinheiten'] = explode(',', $this->shop->get_option('wpsg_mod_fuellmenge_einheit'));
		
			if ($this->shop->hasMod('wpsg_mod_stock'))
			{
				
				$product_content['stock']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/produkt_edit_sidebar.phtml', false);
				
			}
			else 
			{
			
				$product_content['wpsg_mod_fuellmenge'] = array(
					'title' => __('Füllmenge', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/produkt_edit_sidebar.phtml', false)
				);
				 
			}	
	
		}
		
		public function produkt_save(&$produkt_id) {
			
			$db_data = [];
			
			wpsg_checkRequest('feinheit', [WPSG_SANITIZE_TEXTFIELD], __('Einheit Füllmenge', 'wpsg'), $db_data);
			wpsg_checkRequest('fmenge', [WPSG_SANITIZE_FLOAT], __('Füllmenge', 'wpsg'), $db_data);
			
			if (wpsg_isSizedArray($db_data)) {
			
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $db_data, "`id` = '".wpsg_q($produkt_id)."'");
				
			}
			
			$oProduct = wpsg_product::getInstance($produkt_id);
			
			if (isset($_REQUEST['fmenge_details']) && wpsg_checkInput($_REQUEST['fmenge_details'], WPSG_SANITIZE_CHECKBOX)) $oProduct->setMeta('fmenge_details', $_REQUEST['fmenge_details']);
			if (isset($_REQUEST['wpsg_mod_fuellmenge_referencevalue']) && wpsg_checkInput($_REQUEST['wpsg_mod_fuellmenge_referencevalue'], WPSG_SANITIZE_FLOAT)) $oProduct->setMeta('wpsg_mod_fuellmenge_referencevalue', $_REQUEST['wpsg_mod_fuellmenge_referencevalue']);
			if (isset($_REQUEST['referenceunit']) && wpsg_checkInput($_REQUEST['referenceunit'], WPSG_SANITIZE_TEXTFIELD)) $oProduct->setMeta('wpsg_mod_fuellmenge_referenceunit', $_REQUEST['referenceunit']);
			if (isset($_REQUEST['wpsg_mod_fuellmenge_conversionvalue']) && wpsg_checkInput($_REQUEST['wpsg_mod_fuellmenge_conversionvalue'], WPSG_SANITIZE_FLOAT)) $oProduct->setMeta('wpsg_mod_fuellmenge_conversionvalue', $_REQUEST['wpsg_mod_fuellmenge_conversionvalue']);
			
		} // public function produkt_save(&$produkt_id)
		
		public function produkt_edit_sidebar(&$produkt_data)
		{
			
			if (isset($_REQUEST['wpsg_lang'])) return;
			 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/produkt_edit_sidebar.phtml');
			
		} // public function produkt_edit_sidebar(&$produkt_data)
		 		
		/*
		public function loadProduktArray(&$produkt) {
			
			if (!isset($produkt['fmenge'])) $produkt['fmenge'] = 0; else $produkt['fmenge'] = wpsg_tf($produkt['fmenge']);
			
			$arValues = $this->calculateValues($produkt['preis'], $produkt['fmenge'], $produkt['id']);
			die("=".$produkt['id']);
			$produkt['fmenge'] = $arValues['fmenge'];			
			$produkt['feinheit'] = $arValues['feinheit'];
			$produkt['referencevalue'] = $arValues['feinheit'];
			$produkt['referenceunit'] = $arValues['referenceunit'];
			$produkt['fmenge_preis'] = $arValues['fmenge_preis'];
			
		} // public function renderProdukt_data(&$view)
		*/
		
		/** Modulfunktionen */
		
		/**
		 * Bis Januar 202 waren die Einheiten als index gespeichert, das habe ich geändert und daher diese Funktion
		 * 		 
		 * @param $feinheit
		 * @return mixed
		 */
		public function getUnit($feinheit) {

			if (is_numeric($feinheit)) {
				
				$arFeinheiten = explode(',', $this->shop->get_option('wpsg_mod_fuellmenge_einheit'));
				
				return $arFeinheiten[$feinheit];
				
			} else return $feinheit; 
					    
        }
		
		public function renderPriceInfo($price, $fmenge, $product_id, $layout = 1) {
			
			if ($price <= 0 || $fmenge <= 0) return;
			 
			$arValues = $this->calculateValues($price, $fmenge, $product_id);
			
			$this->shop->view['wpsg_mod_fuellmenge'] = array(
			    'layout' => $layout,
				'fmenge' => $arValues['fmenge'],
				'feinheit' => $arValues['feinheit'],
				'referencevalue' => $arValues['referencevalue'],
				'referenceunit' => $arValues['referenceunit'],
				'fmenge_preis' => $arValues['fmenge_preis']
			);
						 			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_fuellmenge/priceinfo.phtml');			
			
		} 
		
		public function calculateValues($price, $fmenge, $product_id) {
			 
			$oProduct = wpsg_product::getInstance($product_id);
			
			$feinheit = $this->getUnit($oProduct->__get('feinheit'));
			$fmenge_details = $oProduct->getMeta('fmenge_details'); // Erweitert
			$referencevalue = wpsg_tf($oProduct->getMeta('wpsg_mod_fuellmenge_referencevalue'));
			$referenceunit = $oProduct->getMeta('wpsg_mod_fuellmenge_referenceunit');
			$conversionvalue = $oProduct->getMeta('wpsg_mod_fuellmenge_conversionvalue');
			
			if ($fmenge_details !== '1') {
				 
				$referencevalue = $this->shop->get_option('wpsg_mod_fuellmenge_bezug');
				$referenceunit = $feinheit;
				
			}
			
			if ($conversionvalue <= 0 || $feinheit === $referenceunit) $conversionvalue = 1;
					
			// Umrechnung
			if ($conversionvalue !== 1) $fmenge_reference = ($fmenge * $referencevalue) / $conversionvalue;
			else $fmenge_reference = $fmenge;
			
			if ($fmenge_reference <= 0) $reference_price = 0;
			else $reference_price = $price / $fmenge_reference * $referencevalue;
			
			return [
				'fmenge' => round($fmenge, 2),
				'feinheit' => $feinheit,
				'referencevalue' => $referencevalue,
				'referenceunit' => $referenceunit,
				'fmenge_preis' => round($reference_price, 2)
			];
			
		}
		
	} // class wpsg_mod_fuellmenge extends wpsg_mod_basic

