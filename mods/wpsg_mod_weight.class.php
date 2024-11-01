<?php

	/**
	 * Modul für die Verwaltung von Produktgewichten
	 * @author Daschmi
	 */
	class wpsg_mod_weight extends wpsg_mod_basic
	{

		var $lizenz = 2;
		var $id = 95;

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name 	= __('Produktgewicht', 'wpsg');
			$this->group 	= __('Produkte', 'wpsg');
			$this->desc 	= __('Erlaubt die Verwaltung von Gewichten pro Produkt.', 'wpsg');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Posts Tabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
		   		weight DOUBLE(10,2) NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

   			/**
   			 * Tabelle für die bestellten Produkte
			 * Zur Sicherheit speichere ich hier das Gewicht zum Zeitpunkt der Bestellung
   			 */
   			$sql = "CREATE TABLE ".WPSG_TBL_ORDERPRODUCT." (
   				weight DOUBLE(10,2) NOT NULL
   			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

   			/**
   			 * Tabelle für die Bestellung erweitern, hier wird das Gesamtgewicht der Bestellung abgespeichert
   			 */
   			$sql = "CREATE TABLE ".WPSG_TBL_ORDER." (
   				weight DOUBLE(10,2) NOT NULL
   			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

   			$this->shop->checkDefault('wpsg_mod_weight_unit', 'g');
   			$this->shop->checkDefault('wpsg_mod_weight_showProduct', '1');
   			$this->shop->checkDefault('wpsg_mod_weight_showBasket', '1');
   			$this->shop->checkDefault('wpsg_mod_weight_showBasketProduct', '1');
   			$this->shop->checkDefault('wpsg_mod_weight_showOverview', '1');
   			$this->shop->checkDefault('wpsg_mod_weight_showOverviewProduct', '1');

		} // public function install()

		public function settings_edit()
		{

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_weight_unit', $_REQUEST['wpsg_mod_weight_unit'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_weight_showProduct', $_REQUEST['wpsg_mod_weight_showProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_weight_showBasket', $_REQUEST['wpsg_mod_weight_showBasket'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_weight_showAjaxDialog', $_REQUEST['wpsg_mod_weight_showAjaxDialog'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_weight_showBasketProduct', $_REQUEST['wpsg_mod_weight_showBasketProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_weight_showOverview', $_REQUEST['wpsg_mod_weight_showOverview'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_weight_showOverviewProduct', $_REQUEST['wpsg_mod_weight_showOverviewProduct'], false, false, WPSG_SANITIZE_CHECKBOX);

			if ($this->shop->hasMod('wpsg_mod_request'))
			{

			    $this->shop->update_option('wpsg_mod_weight_showRequestPage', $_REQUEST['wpsg_mod_weight_showRequestPage'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->shop->update_option('wpsg_mod_weight_showRequestPageProduct', $_REQUEST['wpsg_mod_weight_showRequestPageProduct'], false, false, WPSG_SANITIZE_CHECKBOX);

			}

			if ($this->shop->hasMod('wpsg_mod_productindex'))
			{
			    $this->shop->update_option('wpsg_mod_weight_showProductindex', $_REQUEST['wpsg_mod_weight_showProductindex'], false, false, WPSG_SANITIZE_CHECKBOX);
			}
			$this->shop->update_option('wpsg_mod_weight_showProductindexBackend', $_REQUEST['wpsg_mod_weight_showProductindexBackend'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function settings_save()

		public function basket_row(&$p, $i)
		{

			if ($this->shop->get_option('wpsg_mod_weight_showBasketProduct') != '1') return;

			$this->shop->view['weight'] = $p['weight'];

			$this->shop->view['i'] = $i;

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/basket_row.phtml');

		} // public function basket_row(&$p, $i)

		public function basket_row_end(&$basket_view)
		{

			if ($this->shop->get_option('wpsg_mod_weight_showBasket') != '1') return;
						
			$this->shop->view['wpsg_mod_weight']['weight'] = $this->getSessionBasketWeight();

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/basket_row_end.phtml');

		} // public function basket_row_end(&$basket)

		public function overview_row(&$p, $i)
		{

			if ($this->shop->get_option('wpsg_mod_weight_showOverviewProduct') != '1') return;

			$this->shop->view['i'] = $i;
			$this->shop->view['weight'] = $p['weight'];

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/overview_row.phtml');

		} // public function overview_row(&$p, $i)

		public function overview_row_end(&$overview_view) {

			if ($this->shop->get_option('wpsg_mod_weight_showOverview') != '1') return;

			$this->shop->view['wpsg_mod_weight']['weight'] = $this->getSessionBasketWeight(); 
			
			//$this->shop->view['wpsg_mod_weight']['weight'] = wpsg_getFloat($overview_view['basket']['sum']['weight']);
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/overview_row_end.phtml');

		} // public function overview_row_end(&$overview_view)

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (isset($_REQUEST['wpsg_lang'])) return;

			if (!array_key_exists('stock', $product_content))
			{

				$this->shop->view['arSubAction']['stock'] = array(
					'title' => __('Produktgewicht', 'wpsg'),
					'content' => ''
				);

			}

			$this->shop->view['wpsg_mod_weight']['weight'] = wpsg_getFloat($product_data['weight']);
			$this->shop->view['arSubAction']['stock']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_weight/product_addedit_content.phtml', false);

		} // public function produkt_edit_allgemein(&$produkt_data)

		public function produkt_save_before(&$produkt_data) {
			
			wpsg_checkRequest('weight', [WPSG_SANITIZE_FLOAT], __('Produktgewicht', 'wpsg'), $produkt_data, $_REQUEST['wpsg_mod_weight']['weight']);
			
		} // public function produkt_save_before(&$produkt_data)

		public function loadProduktArray(&$product_data)
		{
         
			$product_data['weight'] = $this->getWeight($product_data['id']);

		}

		public function basket_toArray(&$produkt, $backend = false, $noMwSt = false)
		{

			$produkt['weight_one'] = $this->getWeight($produkt['id']);
			$produkt['weight'] = $this->getWeight($produkt['id']) * $produkt['menge'];

		}

		public function basket_toArray_preshippayment(&$basket, &$arBasket)
		{

			if ($basket->loadFromSession)
			{

				$weight_sum = 0;

				foreach ($arBasket['produkte'] as $k => $p)
				{

					$weight_sum += $p['weight'];

				}

				$arBasket['sum']['weight'] = $weight_sum;

			}
			else if ($basket->o_id > 0)
			{

				$order_data = $this->shop->cache->loadOrder($basket->o_id);

				$arBasket['sum']['weight'] = $order_data['weight'];

			}

		} // public function basket_toArray_final(&$basket, &$arBasket)
 
		/**
		 * @var \wpsg\wpsg_calculation $oCalculation  
		 */
		public function calculation_saveProduct(&$oCalculation, $calc_product, &$db_product_data, $finish_order) { 
			
			$oProduct = wpsg_product::getInstance($calc_product['product_key']);
			
			$db_product_data['weight'] = wpsg_q($oProduct->weight * $calc_product['amount']);
			 			
		}
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) {
		
			$weight = 0;
			
			foreach ($arCalculation['product'] as $p) {
				
				$oProduct = wpsg_product::getInstance($p['product_key']);
				
				$weight += $p['amount'] * $oProduct->weight;
				
			}
			
			$db_data['weight'] = wpsg_q($weight);
			
		}
		
		/* --- */
		
		/**
		 * Gibt das Gewicht der Produkte im aktuellen Warenkorb zurück (Session)
		 */
		public function getSessionBasketWeight() {
			
			$basket_amount = 0;
			
			if (wpsg_isSizedArray($_SESSION['wpsg']['basket'])) {
								
				foreach ($_SESSION['wpsg']['basket'] as $product_index => $product_data) {
					
					$basket_amount += $this->getWeight($product_data['id']) * $product_data['menge'];
					
				}
								
			}
			
			return $basket_amount;
			
		}
		
		public function getWeight($product_key)
		{
		    
			$product_data_db = $this->shop->cache->loadProduct($this->shop->getProduktId($product_key));

			if ($this->shop->hasMod('wpsg_mod_productvariants') && $this->shop->callMod('wpsg_mod_productvariants', 'isVariantsProductKey', array($product_key)))
			{

				$variInfo = $this->shop->callMod('wpsg_mod_productvariants', 'getVariantenInfoArray', array($product_key));
				$weight = $product_data_db['weight'] + $variInfo['weight'];

			}
			else
			{

				$weight = $product_data_db['weight'];

			}

			return $weight;

		} // public function getWeight($produkt_key)

	} // class wpsg_mod_weight extends wpsg_mod_basic

?>