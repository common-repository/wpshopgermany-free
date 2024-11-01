<?php

	/**
	 * Rabatt Modul
	 */
	class wpsg_mod_discount extends wpsg_mod_basic
	{

		var $id = 700;
		var $lizenz = 1;
		var $hilfeURL = 'http://wpshopgermany.de/?p=1550';

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Rabatt', 'wpsg');
			$this->group = __('Bestellung', 'wpsg');
			$this->desc = __('Ist dieses Modul aktiv, so lassen sich Rabatte abhängig vom Bestellwert vergeben. Der Bestellwert ist der Artikelpreis + Kosten für Versandkosten etc.', 'wpsg');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/** Kundengruppentabelle erweitern */
			$sql = "CREATE TABLE ".WPSG_TBL_KG." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
   				discount VARCHAR(255) NOT NULL,
				PRIMARY KEY  (id)
   			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

		} // public function install()

		public function settings_edit()
		{

			$this->shop->view['data'] = $this->getOptionData();

			$hierarchie = explode(',', $this->shop->get_option('wpsg_mod_discount_hierarchie'));
			if (!wpsg_isSizedArray($hierarchie)) $hierarchie = array();

			if (!in_array('general', $hierarchie)) $hierarchie[] = 'general';
			if (!in_array('product', $hierarchie)) $hierarchie[] = 'product';
			if (!in_array('productgroup', $hierarchie)) $hierarchie[] = 'productgroup';
			if (!in_array('customer', $hierarchie)) $hierarchie[] = 'customer';

			$this->shop->view['wpsg_mod_discount']['hierarchie'] = $this->getHierarchie();

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_discount/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_discount_productdiscount', $_REQUEST['wpsg_mod_discount_productdiscount'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_discount_universal', $_REQUEST['wpsg_mod_discount_universal'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option("wpsg_mod_discount_universal_from", wpsg_xss($_REQUEST['wpsg_mod_discount_universal_from']));
			$this->shop->update_option("wpsg_mod_discount_universal_to", wpsg_xss($_REQUEST['wpsg_mod_discount_universal_to']));
			$this->shop->update_option("wpsg_mod_discount_universal_value", wpsg_ff($_REQUEST['wpsg_mod_discount_universal_value']), false, false, WPSG_SANITIZE_FLOAT);
			if ($this->shop->hasMod('wpsg_mod_productgroups')) $this->shop->update_option('wpsg_mod_discount_productgroupdiscount', $_REQUEST['wpsg_mod_discount_productgroupdiscount'], false, false, WPSG_SANITIZE_CHECKBOX);
			if ($this->shop->hasMod('wpsg_mod_customergroup')) $this->shop->update_option('wpsg_mod_discount_customergroup', $_REQUEST['wpsg_mod_discount_customergroup'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_discount_show', $_REQUEST['wpsg_mod_discount_show'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_discount_hierarchie', implode(',', $_REQUEST['wpsg_mod_discount_hierarchie']));
			
			$data_rabatt = $this->getOptionData();

			// Den Nullwert speichern
			$data_rabatt[0]['rabatt'] = wpsg_tf($_REQUEST['value'][0]['rabatt'], true);
			$data_rabatt[0]['value'] = 0;

			$insert = 0;

			if (wpsg_tf($_REQUEST['neu']['value']) > 0 && wpsg_tf($_REQUEST['neu']['rabatt']) > 0)
			{

				$data = array(
					"value" => wpsg_tf($_REQUEST['neu']['value']),
					"rabatt" => wpsg_tf($_REQUEST['neu']['rabatt'], true)
				);

				$find = false;
				foreach ($data_rabatt as $k => $v)
				{
					if ($v['value'] == $data['value']) $find = $k;
				}

				if ($find !== false)
					$data_rabatt[$find] = $data;
				else
					$data_rabatt[] = $data;

				$insert = wpsg_tf($_REQUEST['neu']['value']);

				// Felder für Formular löschen
				$_REQUEST['neu']['value'] = "";
				$_REQUEST['neu']['rabatt'] = "";

			}
			else if (wpsg_tf($_REQUEST['neu']['value']) > 0 || wpsg_tf($_REQUEST['neu']['rabatt']) > 0)
			{

				$this->shop->addBackendError(__("Bitte einen gültigen Bestellwert und einen Rabatt eingeben!", "wpsg"));

			}

			foreach ($_REQUEST['value'] as $k => $v)
			{

				$find = false;
				foreach ($data_rabatt as $k2 => $v2)
				{
					$vval = wpsg_getInt($v['value']);
					$v2val = wpsg_getInt($v2['value']);

					if (	wpsg_tf($v2val) == wpsg_tf($vval) &&
							wpsg_tf($v2val) != $insert	)
					{

						$find = $k2;

					}
				}

				if ($find !== false)
				{
					$vdel = wpsg_getInt($v['del']);
					if ($vdel == 1) unset($data_rabatt[$find]);
					else $data_rabatt[$find]['rabatt'] = wpsg_tf($v['rabatt'], true);
				}

			}

			$this->shop->update_option("wpsg_mod_discount_data", serialize($data_rabatt));
			$this->shop->update_option('wpsg_mod_discount_stopRabatt', $_REQUEST['wpsg_mod_discount_stopRabatt'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_discount_showBasket', $_REQUEST['wpsg_mod_discount_showBasket'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_discount_voucher', $_REQUEST['wpsg_mod_discount_voucher'], false, false, WPSG_SANITIZE_CHECKBOX);		
			$this->shop->update_option('wpsg_mod_discount_productindex', $_REQUEST['wpsg_mod_discount_productindex'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function settings_save()

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (wpsg_isSizedInt($product_data['id']))
			{

				if (isset($_REQUEST['wpsg_lang'])) return;

				$this->shop->view['wpsg_mod_discount']['data'] = explode("_", $product_data['rabatt']);

			}

			/*$product_content['wpsg_mod_discount'] = array(
					'title' => __('Rabatte', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_discount/produkt_addedit_sidebar.phtml', false)
			);*/

			if (isset($product_content['price']['content']))
				$product_content['price']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_discount/produkt_addedit_sidebar.phtml', false);


		} // public function produkt_edit_sidebar(&$product_content, &$produkt_data)

		/**
		 * Soll den Rabattierten Preis zurückgeben
		 * @param unknown $product_id
		 * @param unknown $price
		 */
		public function getDiscountPrice($product_id, $price)
		{

			$arDiscountTypes = $this->getHierarchie();

			if (wpsg_isSizedArray($arDiscountTypes))
			{

				foreach ($arDiscountTypes as $discount_type)
				{

					$discount_value = false;

					if ($discount_type == 'product' && $this->hasProductDiscount($product_id))
					{

						$discount_value = $this->getProductDiscount($product_id);

					}
					else if ($discount_type == 'productgroup' && $this->hasProductgroupDiscount($product_id))
					{

						$discount_value = $this->getProductgroupDiscount($product_id);

					}
					else if ($discount_type == 'general' && $this->hasGeneralProductdiscount())
					{

						$discount_value = $this->getGeneralProductdiscount();

					}
					else if ($discount_type == 'customer' && $this->hasCustomergroupRabatt())
					{

						$discount_value = $this->getCustomergroupRabatt();

					}

					if (wpsg_tf($discount_value) > 0)
					{

						//$this->applyDiscountToProductData($discount_value, $product_data, $discount_type);
						if (strpos($discount_value, "%") !== false)
						{

							$price -= ($price * wpsg_tf($discount_value) / 100);

						}
						else
						{

							$price -= wpsg_tf($discount_value);

						}

						if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_stopRabatt'))) break;

					}

				}

			}

			return $price;

		} // public function getDiscountPrice($product_id, $price)

		public function loadProduktArray(&$product_data)
		{
			 
			if (wpsg_is_admin()) return;
			$arDiscountTypes = $this->getHierarchie();
			$produkt_id = $this->shop->getProduktId($product_data['id']);

			if (wpsg_isSizedArray($arDiscountTypes))
			{

				foreach ($arDiscountTypes as $discount_type)
				{

					$discount_value = false;

					if ($discount_type == 'product' && $this->hasProductDiscount($produkt_id))
					{

						$discount_value = $this->getProductDiscount($produkt_id);

					}
					else if ($discount_type == 'productgroup' && $this->hasProductgroupDiscount($produkt_id))
					{

						$discount_value = $this->getProductgroupDiscount($produkt_id);

					}
					else if ($discount_type == 'general' && $this->hasGeneralProductdiscount())
					{

						$discount_value = $this->getGeneralProductdiscount();

					}
					else if ($discount_type == 'customer' && $this->hasCustomergroupRabatt())
					{

						$discount_value = $this->getCustomergroupRabatt();

					}
					
					if (wpsg_tf($discount_value) > 0)
					{

						$this->applyDiscountToProductData($discount_value, $product_data, $discount_type);

						if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_stopRabatt'))) break;

					}

				}

			}

			if ($product_data['preis'] < 0) $product_data['preis'] = 0;
			if ($product_data['preis_brutto'] < 0) $product_data['preis_brutto'] = 0;
			if ($product_data['preis_netto'] < 0) $product_data['preis_netto'] = 0;

			//wpsg_debug("Rabatt:loadProduktArray = ".$product_data['preis']);

		} // public function loadProduktArray(&$produkt_data)
		
		public function product_getPrice(&$oProduct, &$price_netto, &$price_brutto, $product_key, $amount, $weight) {
			
			$arDiscountTypes = $this->getHierarchie();
			
			if (wpsg_isSizedArray($arDiscountTypes)) {
					
				foreach ($arDiscountTypes as $discount_type) {
					
					$discount_value = false;
			
					if ($discount_type == 'product' && $this->hasProductDiscount($oProduct->id)) {
						
						$discount_value = $this->getProductDiscount($oProduct->id);
						
					} else if ($discount_type == 'productgroup' && $this->hasProductgroupDiscount($oProduct->id)) {
						
						$discount_value = $this->getProductgroupDiscount($oProduct->id);
						
					} else if ($discount_type == 'general' && $this->hasGeneralProductdiscount()) {
						
						$discount_value = $this->getGeneralProductdiscount();
						
					} else if ($discount_type == 'customer' && $this->hasCustomergroupRabatt()) {
						
						$discount_value = $this->getCustomergroupRabatt();
						
					}
			
					if (wpsg_tf($discount_value) > 0) {
			
						if ($this->shop->getBackendTaxview() == WPSG_NETTO) {
							
							$this->applyDiscount($discount_value, $price_netto);	
							$price_brutto = wpsg_calculatePreis($price_netto, WPSG_BRUTTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
							
						} else {
							
							$this->applyDiscount($discount_value, $price_brutto);
							$price_netto = wpsg_calculatePreis($price_brutto, WPSG_NETTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
							
						}
						 						
						if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_stopRabatt'))) break;
						
					}
					
				}
				
			}
			
		}
		
		public function wpsg_mod_productgroups_addedit_sidebar(&$productgroupdata)
		{

			$this->shop->view['wpsg_mod_discount']['data'] = explode("_", wpsg_getStr($productgroupdata['rabatt']));
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_discount/productgroups_addedit_sidebar.phtml');

		} // public function wpsg_mod_productgroups_addedit_sidebar(&$productgroupdata)

		public function wpsg_mod_productgroups_save($productgroup_id) {
			
			$strDiscount = "";
			
			/**
			 * Ich speichere die Rabatteinstellungen in einem Feld innerhalb des Produktes das Trennzeichen ist "_"
			 */
			if (isset($_REQUEST['wpsg_mod_discount'])) {
				
				try {
					
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_aktiv'], WPSG_SANITIZE_CHECKBOX)) throw new \Exception(_('Aktion aktiv'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_from'], WPSG_SANITIZE_DATE, ['allowEmpty' => true])) throw new \Exception(_('Start (TT.MM.JJJJ)'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_to'], WPSG_SANITIZE_DATE, ['allowEmpty' => true])) throw new \Exception(_('Ende (TT.MM.JJJJ)'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_value'], WPSG_SANITIZE_FLOAT, ['allowEmpty' => true])) throw new \Exception(_('Rabatt'));
					
					$dis_active = $_REQUEST['wpsg_mod_discount']['discount_aktiv'];
					$dis_from = $_REQUEST['wpsg_mod_discount']['discount_from'];
					$dis_to = $_REQUEST['wpsg_mod_discount']['discount_to'];
					$dis_value = wpsg_tf($_REQUEST['wpsg_mod_discount']['discount_value'], true);
					
					$strDiscount = $dis_active."_".$dis_from."_".$dis_to."_".$dis_value;
					
				} catch (\Exception $e) {
					
					$this->shop->addBackendError(wpsg_translate(__('Eingaben in Feld "#1#" überprüfen.', $e->getMessage())));
					
					return;
					
				} 
				
			}
			 
			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_GROUP, array(
				'rabatt' => wpsg_q($strDiscount)
			), "`id` = '".wpsg_q($productgroup_id)."'");

		} // public function wpsg_mod_productgroups_save($productgroup_id)

		public function produkt_save(&$produkt_id) {
			
			$strDiscount = "";

			/**
			 * Ich speichere die Rabatteinstellungen in einem Feld innerhalb des Produktes das Trennzeichen ist "_"
			 */
			if (isset($_REQUEST['wpsg_mod_discount'])) {

				try {
				
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_aktiv'], WPSG_SANITIZE_CHECKBOX)) throw new \Exception(_('Aktion aktiv'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_from'], WPSG_SANITIZE_DATE, ['allowEmpty' => true])) throw new \Exception(_('Start (TT.MM.JJJJ)'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_to'], WPSG_SANITIZE_DATE, ['allowEmpty' => true])) throw new \Exception(_('Ende (TT.MM.JJJJ)'));
					if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount_value'], WPSG_SANITIZE_FLOAT, ['allowEmpty' => true])) throw new \Exception(_('Rabatt'));
				
				} catch (\Exception $e) {
					
					$this->shop->addBackendError(wpsg_translate(__('Eingaben in Feld "#1#" überprüfen.', $e->getMessage())));
					
					return;
					
				}
					
				$dis_active = $_REQUEST['wpsg_mod_discount']['discount_aktiv'];
				$dis_from = $_REQUEST['wpsg_mod_discount']['discount_from'];
				$dis_to = $_REQUEST['wpsg_mod_discount']['discount_to'];
				$dis_value = wpsg_tf($_REQUEST['wpsg_mod_discount']['discount_value'], true);
				
				$strDiscount = $dis_active."_".$dis_from."_".$dis_to."_".$dis_value;

			}

			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array(
				'rabatt' => wpsg_q($strDiscount)
			), "`id` = '".wpsg_q($produkt_id)."'");

		} // public function produkt_save($produkt_id)

		public function customergroup_edit(&$oCustomergroup)
		{

			$this->shop->view['wpsg_mod_discount']['discount'] = wpsg_getStr($oCustomergroup->data['discount']);

			$this->shop->render(WPSG_PATH_VIEW.'mods/mod_discount/customergroup_edit.phtml');

		} // public function customergroup_edit(&$customergroup_id)

		public function customergroup_save(&$customergroup_id) {
			
			$db_udpate = [];
			
			if (!wpsg_checkInput($_REQUEST['wpsg_mod_discount']['discount'], WPSG_SANITIZE_FLOAT)) {
				
				$this->shop->addBackendMessage(__('Bitte die Eingaben im Feld "Rabatt" prüfen.', 'wpsg'));
				
			} else $db_udpate['discount'] = wpsg_q($_REQUEST['wpsg_mod_discount']['discount']);
						
			$this->db->UpdateQuery(WPSG_TBL_KG, $db_udpate, " `id` = '".wpsg_q($customergroup_id)."' ");

		} // public function customergroup_save(&$customergroup_id)

		public function addDiscountToVari(&$product_id, &$varianten_data)
		{

			$arDiscountTypes = $this->getHierarchie();

			if (wpsg_isSizedArray($arDiscountTypes))
			{

				foreach ($arDiscountTypes as $discount_type)
				{

					$discount_value = false;

					if ($discount_type == 'product' && $this->hasProductDiscount($product_id))
					{

						$discount_value = $this->getProductDiscount($product_id);

					}
					else if ($discount_type == 'productgroup' && $this->hasProductgroupDiscount($product_id))
					{

						$discount_value = $this->getProductgroupDiscount($product_id);

					}
					else if ($discount_type == 'general' && $this->hasGeneralProductdiscount())
					{

						$discount_value = $this->getGeneralProductdiscount();

					}
					else if ($discount_type == 'customer' && $this->hasCustomergroupRabatt())
					{

						$discount_value = $this->getCustomergroupRabatt();

					}

					if (wpsg_tf($discount_value) > 0)
					{

						$this->applyDiscountToVariData($discount_value, $varianten_data, $discount_type);

						if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_stopRabatt'))) break;

					}

				}

			}

		} // public function addDiscountToVari(&$product_id, &$varianten_data)

		public function basket_top()
		{

			if (!wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_showBasket'))) return false;

			$nextBasketDiscount = $this->getNextBasketDiscount();

			if ($nextBasketDiscount === false) return false;

			$this->shop->view['wpsg_mod_discount']['discount'] = $nextBasketDiscount['discount'];
			$this->shop->view['wpsg_mod_discount']['value'] = $nextBasketDiscount['value'];
			$this->shop->view['wpsg_mod_discount']['discountdifference'] = $nextBasketDiscount['discountdifference'];

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_discount/basket_top.phtml');

		} // public function basket_top()
		
		/**
		 * @param \wpsg\wpsg_calculation $oCalculation
		 * @param $product_done
		 * @param $payship_done
		 */
		public function calculation_fromSession(&$oCalculation, $product_done, $payship_done) { 
			
			if ($product_done && $payship_done) {
			 
				$arCalculation = $oCalculation->getCalculationArray();
				
				$discount = false; 
				
				$value = $arCalculation['sum']['brutto'];
				if ($value == 0) return;
				
				foreach ((array)$this->getOptionData() as $k => $v) {
				 
					
					if (!isset($v['value']) || $value >= $v['value']  && wpsg_tf($v['rabatt']) > 0) {
						
						$discount = $v['rabatt'];
						
					}
					
				}
			
				if ($discount !== false) {
					
					$oCalculation->addDiscount($discount, $this->shop->getBackendTaxview(), '0', 1);
					
				}
				  
			}
			
		} // public function calculation_fromSession(&$oCalculation, $product_done, $payship_done) { 
		
		/* Modulfunktionen */
		
		/**
		 * Gibt true zurück, wenn auf dem Basket ein Rabatt vorhanden ist
		 * @param $arBasket
		 */
		public function hasDiscount($arBasket) {
			 
			// Produktrabatt
			foreach ($arBasket['produkte'] as $p) {
				
				if (wpsg_tf($p['discount_value']) > 0) { return true; }
				
			}
			
			// Warenkorbrabatt
			if (wpsg_tf($arBasket['sum']['preis_rabatt_netto']) > 0) {
				
				return true;
				
			}
				
			return false;
			
		} // public function hasDiscount($arBasket)
				
		private function cmp($a, $b)
		{
			$aval = wpsg_getInt($a['value']);
			$bval = wpsg_getInt($b['value']);

			if ($a['value'] == $b['value']) return 0;

			return ($a['value'] < $b['value']) ? -1 : 1;

		} // private function cmp($a, $b)

		/**
		 * Gibt den nächsten Rabatt und den dafür nötigen Warenkorbwert zurück
		 * oder false
		 */
		public function getNextBasketDiscount()
		{

			$data_rabatt = $this->getOptionData();

			if (!wpsg_isSizedArray($data_rabatt)) return false;

			$arBasket = $this->shop->basket->toArray();
			$basket_amount_brutto = wpsg_getFloat($arBasket['sum']['preis_gesamt_brutto']) + wpsg_getFloat($arBasket['sum']['preis_rabatt_brutto']);

			if ($basket_amount_brutto <= 0) return false;

			foreach ($data_rabatt as $k => $discount)
			{

				if (isset($discount['value']) && $discount['value'] > $basket_amount_brutto)
				{

					return array(
						'discount' => $discount['rabatt'],
						'value' => $discount['value'],
						'discountdifference' => abs($basket_amount_brutto - $discount['value'])
					);

				}

			}

			return false;

		} // public function getNextBasketDiscount()

		/**
		 * Rabatt in Warenkorb einrechnen
		 */
		public function basket_toArray_discount(&$basket, &$arReturn)
		{ 
			
			// Analog Gutschein, für den Warenkorbrabatt

			$data_rabatt = $this->getOptionData();
			$discount = false;
			$discount_tax = 0;

			$value = $arReturn['sum']['preis_gesamt_brutto'];
			if ($value == 0) return;

			foreach ((array)$data_rabatt as $k => $v)
			{

				if (!isset($v['value']) || $value >= $v['value']  && wpsg_tf($v['rabatt']) > 0)
				{

					$discount = $v['rabatt'];

				}

			}

			if ((isset($arReturn['backend'])) && ($arReturn['backend'] == true))
			{
				// Im Backend Rabatt nicht neu berechnen aus Bestellung entnehmen
				//$discount_brutto = $basket->arOrder['price_rabatt_brutto'];
				//$discount_netto = $basket->arOrder['price_rabatt_netto'];
				if ($basket->arOrder['price_rabatt'] <= 0) return;

				$discount = $basket->arOrder['price_rabatt'];
				$price_option = $this->shop->get_option('wpsg_preisangaben');
				$discount_tax = 0.0;
				if ($price_option == WPSG_BRUTTO)
				{

					// Rabatt auf Produktpreis begrenzen
					if ($discount > $arReturn['sum']['preis_gesamt_brutto']) $discount = $arReturn['sum']['preis_gesamt_brutto'];
					
					if ($this->shop->get_option('wpsg_kleinunternehmer') != '1')
					{

						// Rabatt auf die verschiedenen Produkte aufteilen.
						//$discount_tax = $this->shop->addMwSt($arReturn, $discount);
						$discount_tax = $this->shop->subMwSt($arReturn, $discount);

					}

					if ($this->shop->addRoundedValues === true)
					{

						$discount_brutto = round($discount, 2);
						$discount_netto = round($discount - $discount_tax, 2);

					}
					else
					{

						$discount_brutto = $discount;
						$discount_netto = $discounto - $discount_tax;

					}

				}
				else	// if ($price_option == WPSG_BRUTTO)
				{

					if ($discount > $arReturn['sum']['preis_gesamt_netto']) $discount = $arReturn['sum']['preis_gesamt_netto'];
					
					if ($this->shop->get_option('wpsg_kleinunternehmer') != '1')
					{

						$discount_tax = $this->shop->addMwSt($arReturn, $discount);

					}

					if ($this->shop->addRoundedValues === true)
					{

						$discount_brutto = round($discount + $discount_tax, 2);
						$discount_netto = round($discount, 2);

					}
					else
					{

						$discount_brutto = $discount + $discount_tax;
						$discount_netto = $discount;

					}

				}	// else if ($price_option == WPSG_BRUTTO)


				$arReturn['sum']['preis_brutto'] -= $discount_brutto;
				$arReturn['sum']['preis_netto'] -= $discount_netto;
				$arReturn['sum']['preis_gesamt_brutto'] -= $discount_brutto;
				$arReturn['sum']['preis_gesamt_netto'] -= $discount_netto;
				$arReturn['sum']['preis_rabatt_netto'] = $discount_netto;
				$arReturn['sum']['preis_rabatt_brutto'] = $discount_brutto;

				if ($price_option == WPSG_NETTO)
				{

					$arReturn['sum']['preis_rabatt'] = $discount_netto;
					$arReturn['sum']['preis'] = $arReturn['sum']['preis_netto'];
					$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_netto'];

				}
				else if ($price_option == WPSG_BRUTTO)
				{

					$arReturn['sum']['preis_rabatt'] = $discount_brutto;
					$arReturn['sum']['preis'] = $arReturn['sum']['preis_brutto'];
					$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_brutto'];

				}



			}
			else	// if ($arReturn['backend'] == true)
			{

				if ($discount !== false)
				{

					$discount = wpsg_tf($discount, true);

					if (strpos($discount, "%") !== false)
					{

						$discount_value = $arReturn['sum']['preis_gesamt_brutto'] / 100 * wpsg_tf($discount);

						if ($this->shop->get_option('wpsg_kleinunternehmer') != '1')
						{

							$discount_tax = $this->shop->subMwSt($arReturn, $discount_value);

						}

					}
					else if ($discount)
					{

						if ($this->shop->get_option('wpsg_preisangaben') == WPSG_NETTO)
						{

							if ($this->shop->get_option('wpsg_kleinunternehmer') != '1')
							{

								$discount_tax = $this->shop->addMwSt($arReturn, $discount);

							}

							$discount_value = $discount + $discount_tax;

						}
						else
						{

							$discount_value = $discount;

							if ($this->shop->get_option('wpsg_kleinunternehmer') != '1')
							{

								$discount_tax = $this->shop->subMwSt($arReturn, $discount);

							}

						}

					}

					if ($this->shop->addRoundedValues === true)
					{

						$discount_brutto = round($discount_value, 2);
						$discount_netto = round($discount_value - $discount_tax, 2);

					}
					else
					{

						$discount_brutto = $discount_value;
						$discount_netto = $discount_value - $discount_tax;

					}

					$arReturn['sum']['preis_brutto'] -= $discount_brutto;
					$arReturn['sum']['preis_netto'] -= $discount_netto;
					$arReturn['sum']['preis_gesamt_brutto'] -= $discount_brutto;
					$arReturn['sum']['preis_gesamt_netto'] -= $discount_netto;
					$arReturn['sum']['preis_rabatt_netto'] = $discount_netto;
					$arReturn['sum']['preis_rabatt_brutto'] = $discount_brutto;

					if ($this->shop->getFrontendTaxview() == WPSG_NETTO)
					{

						$arReturn['sum']['preis_rabatt'] = $discount_netto;
						$arReturn['sum']['preis'] = $arReturn['sum']['preis_netto'];
						$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_netto'];

					}
					else if ($this->shop->getFrontendTaxview() == WPSG_BRUTTO)
					{

						$arReturn['sum']['preis_rabatt'] = $discount_brutto;
						$arReturn['sum']['preis'] = $arReturn['sum']['preis_brutto'];
						$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_brutto'];

					}

				}
			}  // else if ($arReturn['backend'] == true)
			/*
			if ($discount !== false)
			{

				if ($this->shop->addRoundedValues === true)
				{

					$discount_brutto = round($discount_value, 2);
					$discount_netto = round($discount_value - $discount_tax, 2);

				}
				else
				{

					$discount_brutto = $discount_value;
					$discount_netto = $discount_value - $discount_tax;

				}

				$arReturn['sum']['preis_brutto'] -= $discount_brutto;
				$arReturn['sum']['preis_netto'] -= $discount_netto;
				$arReturn['sum']['preis_gesamt_brutto'] -= $discount_brutto;
				$arReturn['sum']['preis_gesamt_netto'] -= $discount_netto;
				$arReturn['sum']['preis_rabatt_netto'] = $discount_netto;
				$arReturn['sum']['preis_rabatt_brutto'] = $discount_brutto;

				if ($this->shop->getFrontendTaxview() == WPSG_NETTO)
				{

					$arReturn['sum']['preis_rabatt'] = $discount_netto;
					$arReturn['sum']['preis'] = $arReturn['sum']['preis_netto'];
					$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_netto'];

				}
				else if ($this->shop->getFrontendTaxview() == WPSG_BRUTTO)
				{

					$arReturn['sum']['preis_rabatt'] = $discount_brutto;
					$arReturn['sum']['preis'] = $arReturn['sum']['preis_brutto'];
					$arReturn['sum']['preis_gesamt'] = $arReturn['sum']['preis_gesamt_brutto'];

				}

			}	*/

		} // public function basket_toArray_discount(&$basket, &$arBasket)

		/**
		 * Bearbeitet den Varianten Array und fügt die rabattierten Preise hinzu
		 */
		public function applyDiscountToVariData($discount_value, &$varianten_data, $discount_type)
		{

			if (strpos($discount_value, '%') !== false)
			{

				foreach ($varianten_data as $var_key => &$var)
				{

					if ($var['typ'] == 'checkbox')
					{

						$var['preis'] -= ($var['preis'] * wpsg_tf($discount_value) / 100);

					}
					else
					{

						foreach ($var['arVariation'] as $vari_key => &$vari)
						{

							$vari['preis'] -= ($vari['preis'] * wpsg_tf($discount_value) / 100);

						}

					}

				}

			}

		} // public function applyDiscountToVariData($discount_value, &$varianten_data, $discount_type)

		private function applyDiscount($discount_value, &$price) {
			
			if (strpos($discount_value, '%') !== false)
			{
				
				$price = $price - ($price * wpsg_tf($discount_value) / 100); 
				
			}
			else
			{
				
				$price = $price - wpsg_tf($discount_value); 
				
			}
			
		} // C:\xampp\htdocs\wp.home\wpsg4\wp-content\plugins\wpshopgermany-free\mods\wpsg_mod_discount.class.php
		
		/**
		 * Bearbeitet einen Array mit Produktinformationen mit dem Rabatt
		 */
		public function applyDiscountToProductData($discount_value, &$product_data, $discount_type)
		{

			$mwst_value = $product_data['mwst_value'];

			if (!wpsg_isSizedArray($product_data['discount'])) $product_data['discount'] = array();
			$product_data['discount'][$discount_type] = $discount_value;

			$discount_min_price = false; if ($this->shop->hasMod('wpsg_mod_productvariants') && isset($product_data['min_preis'])) $discount_min_price = &$product_data['min_preis'];
			$discount_max_price = false; if ($this->shop->hasMod('wpsg_mod_productvariants') && isset($product_data['max_preis'])) $discount_max_price = &$product_data['max_preis'];

			if (!isset($product_data['preis_prediscount'])) $product_data['preis_prediscount'] = $product_data['preis'];
			if (!isset($product_data['preis_netto_prediscount'])) $product_data['preis_netto_prediscount'] = $product_data['preis_netto'];
			if (!isset($product_data['preis_brutto_prediscount'])) $product_data['preis_brutto_prediscount'] = $product_data['preis_brutto'];
			
			if ($discount_min_price && !isset($product_data['min_preis_prediscount'])) $product_data['min_preis_prediscount'] = $product_data['min_preis'];
			if ($discount_max_price && !isset($product_data['max_preis_prediscount'])) $product_data['max_preis_prediscount'] = $product_data['max_preis'];

			if ($this->shop->getBackendTaxview() == WPSG_BRUTTO)
			{

				$preis_discount_calc = &$product_data['preis_brutto'];
				$preis_calc = &$product_data['preis_netto'];
				$calc = WPSG_NETTO;

			}
			else
			{

				$preis_discount_calc = &$product_data['preis_netto'];
				$preis_calc = &$product_data['preis_brutto'];
				$calc = WPSG_BRUTTO;

			}

			if (strpos($discount_value, '%') !== false)
			{

				$preis_discount_calc = $preis_discount_calc - ($preis_discount_calc * wpsg_tf($discount_value) / 100);
				if ($discount_min_price) $discount_min_price = $discount_min_price - ($discount_min_price * $discount_value / 100);
				if ($discount_max_price) $discount_max_price = $discount_max_price - ($discount_max_price * $discount_value / 100);

			}
			else
			{

				$preis_discount_calc = $preis_discount_calc = $preis_discount_calc - $discount_value;
				if ($discount_min_price) $discount_min_price = $discount_min_price - $discount_value;
				if ($discount_max_price) $discount_max_price = $discount_max_price - $discount_value;

			}

			// Ich lasse hier negative Discounts zu, da diese später noch vom Variantenmodul abgezogen werden
			//if ($preis_discount_calc < 0) $preis_discount_calc = 0;

			$preis_calc = wpsg_calculatePreis($preis_discount_calc, $calc, $mwst_value);

			// Preis für Anzeige wieder setzen
			if ($this->shop->getFrontendTaxview() == WPSG_BRUTTO)
			{

				$product_data['preis'] = &$product_data['preis_brutto'];

			}
			else
			{

				$product_data['preis'] = &$product_data['preis_netto'];

			}

			// Rabattwert eintragen
			$product_data['discount_value'] = $product_data['preis_prediscount'] - $product_data['preis'];

		} // public function applyDiscountToProductData($discount_value, &$product_data)

		/**
		 * Gibt true zurück, sofern das Produkt einen aktiven Produktrabatt hat
		 */
		public function hasProductDiscount($product_id)
		{

			/**
			 * Jetzt noch ein eventueller Rabatt aus den Produkten
			 */
			$bRabatt_produkt = false;

			if ($this->shop->get_option("wpsg_mod_discount_productdiscount") != "1")
			{

				$bRabatt_produkt = false;

			}
			else
			{

				$product_data = $this->shop->cache->loadProduct($product_id);
				$discount = explode('_', $product_data['rabatt']);

				// Zeitraum und gültigen Wert überprüfen
				if ($discount[0] != "1" || !preg_match("/\d{2}\.\d{2}\.\d{4}/", $discount[1]) || !preg_match("/\d{2}\.\d{2}\.\d{4}/", $discount[2]) || !is_float((float)$discount[3]))
				{

					$bRabatt_produkt = false;

				}
				else
				{

					$rabatt_value = $discount[3];
					$rabatt_start = strtotime($discount[1]);
					$rabatt_end = strtotime($discount[2]);

					// im Zeitrahmen?
					if (time() >= $rabatt_start && time() <= $rabatt_end)
					{

						$bRabatt_produkt = true;

					}
					else
					{

						$bRabatt_produkt = false;

					}

				}

			}

			return $bRabatt_produkt;

		} // public function hasProductDiscount($product_id)

		/**
		 * Gibt einen Array mit den Rabattinformationen bezüglich des Produktrabattes zurück
		 */
		public function getProductDiscount($product_id)
		{

			if (!$this->hasProductDiscount($product_id)) return 0;

			$product_data = $this->shop->cache->loadProduct($product_id);
			$discount = explode('_', $product_data['rabatt']);

			return $discount[3];

		} // public function getProductDiscount($product_id)

		/**
		 * Gibt true zurück, sofern das Produkt einen aktiven Rabatt aus der Kundengruppe hat
		 */
		public function hasCustomergroupRabatt()
		{

			if ($this->shop->hasMod('wpsg_mod_customergroup') && $this->shop->get_option('wpsg_mod_discount_customergroup') === '1')
			{

				$act_customer_group = $this->shop->callMod('wpsg_mod_kundenverwaltung', 'getCustomerGroup');

				if (wpsg_isSizedInt($act_customer_group))
				{

					$oCustomerGroup = wpsg_customergroup::getInstance($act_customer_group);

					if ($oCustomerGroup !== false && wpsg_isSizedString($oCustomerGroup->discount))
					{

						return true;

					}

				}

			}

		} // public function hasCustomergroupRabatt()

		/**
		 * Gibt den Rabatt aus der Kundengruppe zurück
		 */
		public function getCustomergroupRabatt()
		{

			if (!$this->hasCustomergroupRabatt()) return 0;

			$act_customer_group = $this->shop->callMod('wpsg_mod_kundenverwaltung', 'getCustomerGroup');

			if (wpsg_isSizedInt($act_customer_group))
			{

				$oCustomerGroup = wpsg_customergroup::getInstance($act_customer_group);

				if ($oCustomerGroup !== false && wpsg_isSizedString($oCustomerGroup->discount))
				{

					return $oCustomerGroup->discount;

				}

			}

			return 0;

		} // public function getCustomergroupRabatt()

		/**
		 * Gibt true zurück, sofern das Produkt einen aktiven Rabatt aus dem generellen Produktrabatt hat
		 */
		public function hasGeneralProductdiscount()
		{

			$bRabatt_allgemein = true;

			if ($this->shop->get_option("wpsg_mod_discount_universal") != "1")
			{
				$bRabatt_allgemein = false;
			}

			// Genereller Rabatt ist aktiviert nun den Zeitraum überprüfen dazu überprüf ich zuerst ob die Datumseingaben valide sind
			if (!preg_match("/\d{2}\.\d{2}\.\d{4}/", $this->shop->get_option("wpsg_mod_discount_universal_from")) ||
			!preg_match("/\d{2}\.\d{2}\.\d{4}/", $this->shop->get_option("wpsg_mod_discount_universal_to")) ||
			!is_float((float)$this->shop->get_option("wpsg_mod_discount_universal_value")))
			{
				$bRabatt_allgemein = false;
			}

			if ($bRabatt_allgemein)
			{

				$rabatt_value = $this->shop->get_option("wpsg_mod_discount_universal_value");
				$rabatt_start = strtotime($this->shop->get_option("wpsg_mod_discount_universal_from"));
				$rabatt_end = strtotime($this->shop->get_option("wpsg_mod_discount_universal_to"));

				// im Zeitrahmen?
				if (time() >= $rabatt_start && time() <= $rabatt_end)
				{

					$bRabatt_allgemein = true;

				}
				else
				{

					$bRabatt_allgemein = false;

				}

			}

			return $bRabatt_allgemein;

		} // public function hasGeneralProductdiscount()

		/**
		 * Gibt den generellen Produktrabatt zurück
		 */
		public function getGeneralProductdiscount()
		{

			if (!$this->hasGeneralProductdiscount()) return 0;

			return $this->shop->get_option("wpsg_mod_discount_universal_value");

		} // public function getGeneralProductdiscount()

		/**
		 * Gibt true zurück, sofern das Produkt einen aktiven Rabatt aus der Produktgruppe hat
		 */
		public function hasProductgroupDiscount($product_id)
		{

			if (!$this->shop->hasMod('wpsg_mod_productgroups')) return false;

			$bRabatt_produktgruppen = true;

			$rabatt = array();

			if ($this->shop->get_option("wpsg_mod_discount_productgroupdiscount") != "1")
			{

				$bRabatt_produktgruppen = false;

			}
			else
			{

				// Jetzt brauch ich für weiter Kontrollen den Rabatt aus der Produkgruppe
				$rabatt_produktgruppe = $this->db->fetchOne("
					SELECT
						PG.`rabatt`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
							LEFT JOIN `".WPSG_TBL_PRODUCTS_GROUP."` AS PG ON (P.`pgruppe` = PG.`id`)
					WHERE
						P.`id` = '".wpsg_q($product_id)."'
				");

				$rabatt = explode("_", $rabatt_produktgruppe);

			}

			// Zeitraum und gültigen Wert überprüfen
			if (!wpsg_isSizedArray($rabatt) || $rabatt[0] != "1" || !preg_match("/\d{2}\.\d{2}\.\d{4}/", $rabatt[1]) || !preg_match("/\d{2}\.\d{2}\.\d{4}/", $rabatt[2]) || !is_float((float)$rabatt[3]))
			{

				$bRabatt_produktgruppen = false;

			}

			if ($bRabatt_produktgruppen)
			{

				$rabatt_value = $rabatt[3];
				$rabatt_start = strtotime($rabatt[1]);
				$rabatt_end = strtotime($rabatt[2]);

				// im Zeitrahmen?
				if (time() >= $rabatt_start && time() <= $rabatt_end)
				{

					$bRabatt_produktgruppen = true;

				}
				else
				{

					$bRabatt_produktgruppen = false;

				}

			}

			return $bRabatt_produktgruppen;

		} // public function hasProductgroupDiscount($product_id)

		/**
		 * Gibt den Rabatt zurück, der aus der Produktgruppe resultiert
		 */
		public function getProductgroupDiscount($product_id)
		{

			if (!$this->hasProductgroupDiscount($product_id)) return 0;

			$rabatt_produktgruppe = $this->db->fetchOne("
				SELECT
					PG.`rabatt`
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
						LEFT JOIN `".WPSG_TBL_PRODUCTS_GROUP."` AS PG ON (P.`pgruppe` = PG.`id`)
				WHERE
					P.`id` = '".wpsg_q($product_id)."'
			");

			$discount = explode("_", $rabatt_produktgruppe);

			return $discount[3];

		} // public function getProductgroupDiscount($product_id)

		private function getOptionData()
		{

			if (@is_array($this->shop->get_option("wpsg_mod_discount_data"))) $data_rabatt = $this->shop->get_option("wpsg_mod_discount_data");
	 		else
	 		{
				$data_rabatt = @unserialize($this->shop->get_option("wpsg_mod_discount_data"));
				if (!is_array($data_rabatt)) $data_rabatt = array();
	 		}

	 		usort($data_rabatt, array($this, "cmp"));

	 		return $data_rabatt;

		} // private function getOptionData()

		/**
		 * Gibt die Rabattschlüssel in der definierten Reihenfolge als Array zurück
		 */
		private function getHierarchie()
		{

			$hierarchie = explode(',', $this->shop->get_option('wpsg_mod_discount_hierarchie'));
			if (!wpsg_isSizedArray($hierarchie)) $hierarchie = array();

			if (!in_array('general', $hierarchie)) $hierarchie[] = 'general';
			if (!in_array('product', $hierarchie)) $hierarchie[] = 'product';
			if (!in_array('productgroup', $hierarchie)) $hierarchie[] = 'productgroup';
			if (!in_array('customer', $hierarchie)) $hierarchie[] = 'customer';

			return wpsg_trim(array_unique($hierarchie));

		} // private function getHierarchie()

		/**
		 * Gibt den Namen für einen Rabatttyp zurück
		 */
		public function getNameFromType($discount_type)
		{

			switch ($discount_type)
			{

				case 'general': return __('Rabatt auf alle Produkte', 'wpsg'); break;
				case 'product': return __('Produktrabatt', 'wpsg'); break;
				case 'productgroup': return __('Produktgruppenrabatt', 'wpsg'); break;
				case 'customer': return __('Kundengruppenrabatt', 'wpsg'); break;

				default: throw new \wpsg\Exception(__('Es konnte kein Name für einen Rabatttyp zurückgegeben werden', 'wpsg'));

			}

		} // public function getNameFromType($discount_type)

	} // class wpsg_mod_discount

