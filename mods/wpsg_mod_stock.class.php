<?php

	/**
	 * Dieses Modul erlaubt die Verwaltung des Warenbestandes
	 *
	 */
	class wpsg_mod_stock extends wpsg_mod_basic
	{

		var $id = 90;
		var $lizenz = 1;

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Lagerbestand', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Erlaubt die Verwaltung des Lagerbestandes bei den Produkten.', 'wpsg');

		} // public function __construct()

		public function install() {

			$this->shop->checkDefault('wpsg_mod_stock_showBackendStock', '1');
			$this->shop->checkDefault('wpsg_mod_stock_updateProductSave', '1');
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Produkttabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
			  	stock int(11) NOT NULL,
			  	stock_count int(1) NOT NULL,
				minstockproduct_count int(11) NOT NULL,
				minstockproduct_mail varchar (255) NOT NULL,
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

		} // public function install()

		public function settings_edit()
		{

			// Verfügbare Produkttemplates
			$this->shop->view['arTemplates'] = $this->shop->loadProduktTemplates();

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

			//$this->shop->update_option('wpsg_mod_stock_template', $_REQUEST['wpsg_mod_stock_template']);
		    $this->shop->update_option('wpsg_mod_stock_allow', $_REQUEST['wpsg_mod_stock_allow'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_stock_showBackendStock', $_REQUEST['wpsg_mod_stock_showBackendStock'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_stock_minstockproduct', $_REQUEST['wpsg_mod_stock_minstockproduct'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_stock_hideSoldout', $_REQUEST['wpsg_mod_stock_hideSoldout'], false, false, WPSG_SANITIZE_CHECKBOX);
		    
		    if ($this->shop->hasMod('wpsg_mod_productgroups')) {
		    
		    	$this->shop->update_option('wpsg_mod_stock_updateProductSave', $_REQUEST['wpsg_mod_stock_updateProductSave'], false, false, WPSG_SANITIZE_CHECKBOX);
		    	
			}

			if ($this->shop->hasMod('wpsg_mod_productindex')) {
				
			    $this->shop->update_option('wpsg_mod_stock_showProductindex', $_REQUEST['wpsg_mod_stock_showProductindex'], false, false, WPSG_SANITIZE_CHECKBOX);
			    
			}

			$this->shop->update_option('wpsg_mod_stock_showProduct', $_REQUEST['wpsg_mod_stock_showProduct'], false, false, WPSG_SANITIZE_CHECKBOX);

		} // public function settings_save()

		/**
		 *
		 */
		public function canOrder($product_key)
		{

			if ($this->getBestand($product_key) - $this->getAmountSession($product_key) <= 0) return -2;

		} // public function canOrder($oProduct)

		public function canDisplay($product_key)
		{

			if (!is_admin() && !wpsg_is_cron() && $this->shop->get_option('wpsg_mod_stock_hideSoldout') === '1' && $this->getBestand($product_key) - $this->getAmountSession($product_key) <= 0) return -2;

		} // public function canDisplay($product_key)

		public function produkt_ajax() {

			if ($_REQUEST['wpsg_cmd'] == 'getVariInfo')
			{

				if ($this->shop->hasMod('wpsg_mod_productvariants'))
				{

					$this->shop->callMod('wpsg_mod_productvariants', 'stockVarianten', array(wpsg_sinput("key", $_REQUEST['product_id'])));

				}
				else
				{

					die(wpsg_translate(__('Aufrufsfehler! Fehlercode:#1#', 'wpsg'), '90_1'));

				}

			}

		} // public function produkt_ajax()

		public function produkt_edit(&$data)
		{

			$data = wpsg_array_merge($data, $this->db->fetchRow("
				SELECT
					`stock`, `stock_count`
				FROM
					`".WPSG_TBL_PRODUCTS."`
				WHERE
					`id` = '".wpsg_q($data['id'])."'
			"));

		} // public function produkt_edit(&$data)
	
		public function produkt_save_before(&$product_data) {
			
			wpsg_checkRequest('stock', [WPSG_SANITIZE_INT, ['allowEmpty' => true]], __('Lagerbestand'),$product_data, $_REQUEST['wpsg_mod_stock_stock']);
			wpsg_checkRequest('stock_count', [WPSG_SANITIZE_CHECKBOX], __('Lagerbestand zählen'), $product_data, $_REQUEST['wpsg_mod_stock_stock_count']);
			wpsg_checkRequest('minstockproduct_count', [WPSG_SANITIZE_INT, ['allowEmpty' => true]], __('Mindestlagerbestand zählen'), $product_data, $_REQUEST['wpsg_mod_minstockproduct']);
			wpsg_checkRequest('minstockproduct_mail', [WPSG_SANITIZE_EMAIL, ['allowEmpty' => true]], __('Lagerbestand / Benachrichtigung an'), $product_data, $_REQUEST['wpsg_mod_stock_minstockproduct_mail']);
			 
		}
		
		public function produkt_save(&$product_id) {

			if ($this->shop->get_option('wpsg_mod_stock_updateProductSave') === '1' && $this->shop->hasMod('wpsg_mod_productgroups')) {

				$this->calculateProductgroupStock($product_id);
								
			}
			
		} // public function produkt_save(&$produkt_id)
		
		public function produkt_copy(&$produkt_id, &$copy_id) {
			
			if ($this->shop->get_option('wpsg_mod_stock_updateProductSave') === '1' && $this->shop->hasMod('wpsg_mod_productgroups')) {
				
				$this->calculateProductgroupStock($copy_id);
				
			}
			
		}
		
		public function produkt_edit_sidebar(&$produkt_data)
		{

			if (isset($_REQUEST['wpsg_lang'])) return;

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/produkt_edit_sidebar.phtml');

		} // public function produkt_edit_sidebar(&$produkt_data)

		public function setOrderStatus($order_id, $status_id, $inform)
		{

			if (in_array($status_id, array(500)) ||
				(isset($this->shop->view['order']) && ($this->shop->view['order']['status'] == 500)))
			{

				// Entweder ist die Bestellung von Storniert in einen anderen Status gewächselt oder in Storniert

				// Lagerbestand zurücksetzen
				$arProductsBuy = $this->db->fetchAssoc("
					SELECT
						OP.*
					FROM
						`".WPSG_TBL_ORDERPRODUCT."` AS OP
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = OP.`p_id`)
					WHERE
						OP.`o_id` = '".wpsg_q($order_id)."'
				");

				foreach ($arProductsBuy as $p)
				{

					// Bei gekauften Abo Verlängerungen nicht machen
					if (preg_match('/^abo_\d+(.*)/', $p['productkey'])) continue;

					if ($this->shop->hasMod('wpsg_mod_productgroups'))
					{

						// Verwaltung des Lagerbestandes in der Produktgruppe

						// Produktdaten laden um die Produktgruppe herauszufinden
						$product_data = $this->shop->cache->loadProduct($p['p_id']);

						if (wpsg_isSizedInt($product_data['pgruppe']))
						{

							$pgruppe_stock_active = $this->db->fetchOne("SELECT `stock_aktiv` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($product_data['pgruppe'])."' ");

							if ($pgruppe_stock_active === '1')
							{

								if ($this->shop->view['order']['status'] == 500) /* war storniert */
								{

									$this->db->Query("
										UPDATE ".WPSG_TBL_PRODUCTS_GROUP." SET `stock_value` = `stock_value` - ".wpsg_q($p['menge'])." WHERE `id` = '".wpsg_q($product_data['pgruppe'])."'
									");

								}
								else
								{

									$this->db->Query("
										UPDATE ".WPSG_TBL_PRODUCTS_GROUP." SET `stock_value` = `stock_value` + ".wpsg_q($p['menge'])." WHERE `id` = '".wpsg_q($product_data['pgruppe'])."'
									");

								}

							}

						}

					}

					// Verwaltung im Produkt oder Produktgruppenlagerbestand nicht aktiv
					$product_data =  $this->shop->cache->loadProduct($p['p_id']);

					if ($product_data['stock_count'] != '1') continue;

					$var_key = false;

					if ($this->shop->hasMod('wpsg_mod_productvariants'))
					{

						if (wpsg_isSizedString($p['mod_vp_varkey']) && preg_match('/^pv_\d+(.*)/', $p['mod_vp_varkey']))
						{

							$var_key = $p['mod_vp_varkey'];

						}
						else if (wpsg_isSizedString($p['productkey']) && preg_match('/^pv_\d+(.*)/', $p['productkey']))
						{

							$var_key = $p['productkey'];

						}

					}

					// Normales Produkt

					if ($this->shop->view['order']['status'] == 500) /* war storniert */
					{

						$this->db->Query("
							UPDATE ".WPSG_TBL_PRODUCTS." SET `stock` = `stock` - ".wpsg_q($p['menge'])." WHERE `id` = '".wpsg_q($p['p_id'])."'
						");

					}
					else
					{

						$this->db->Query("
							UPDATE ".WPSG_TBL_PRODUCTS." SET `stock` = `stock` + ".wpsg_q($p['menge'])." WHERE `id` = '".wpsg_q($p['p_id'])."'
						");

					}

					if ($var_key !== false)
					{

						// Variantenprodukt

						if ($this->shop->view['order']['status'] == 500)
						{

							$this->shop->callMod('wpsg_mod_productvariants', 'reduceStock', array($var_key, $p['menge'], true));

						}
						else
						{

							$this->shop->callMod('wpsg_mod_productvariants', 'reduceStock', array($var_key, $p['menge'], false));

						}

					}

				}

			}

		} // public function setOrderStatus($order_id, $status_id, $inform)

		public function basket_updateProduktFromSession(&$product_index, &$stock)
		{

			$product_key = $_SESSION['wpsg']['basket'][$product_index]['id'];

			if (!$this->checkBestand($product_key, $stock))
			{

				/* Nicht mehr ausreichend im Lager */
				if ($this->shop->get_option('wpsg_mod_stock_allow') === '1')
				{

					/* Bestellungen mit negativem Lagerbestand verhindern ist aktiv */
					$stock = $this->getBestand($product_key);

					$this->shop->addFrontendError(__('Menge wurde korrigiert, da sie den Warenbestand überschreitet!', 'wpsg'));

				}
				else
				{

					$this->shop->addFrontendError(__('Menge überschreitet Warenbestand!', 'wpsg'));

				}

			}

		} // public function basket_updateProduktFromSession(&$product_key, &$stock)

		public function basket_produkttosession($product_key, &$amount_set, &$ses_data)
		{

			//$ses_amount = max(array($this->getAmountSession($product_key), $this->getAmountSessionById($this->shop->getProduktId($product_key))));

			if ($this->shop->hasMod('wpsg_mod_productvariants') && $this->shop->callMod('wpsg_mod_productvariants', 'isVariantsProductKey', array($product_key)))
			{
				$ses_amount = $this->getAmountSession($product_key);
			}
			else 
			{
				$ses_amount = $this->getAmountSessionById($this->shop->getProduktId($product_key));
			}
			
			$check_amount = $amount_set + $ses_amount;

 			if (!$this->checkBestand($product_key, $check_amount))
			{

				/* Nicht mehr ausreichend im Lager */
				if ($this->shop->get_option('wpsg_mod_stock_allow') === '1')
				{

					/* Bestellungen mit negativem Lagerbestand verhindern ist aktiv */
					$amount_stock = $this->getBestand($product_key);

					$amount_set = $amount_stock - $ses_amount;

					$this->shop->addFrontendError(__('Die ausgewählte Menge wurde korrigiert, da sie den Warenbestand überschreitet!', 'wpsg'));

					if ($amount_set <= 0) return -2;

				}
				else
				{

					$this->shop->addFrontendError(__('Bitte beachten Sie, dass die ausgewählte Menge den Warenbestand überschreitet, die Bestellung jedoch abgeschlossen werden kann! Bei Fragen zur Bestellung, wenden Sie sich bitte an uns.', 'wpsg'));

				}

			}

		}

		public function checkCheckout(&$state, &$error, &$arCheckout)
		{

			if (!wpsg_isSizedInt($this->shop->get_option('wpsg_mod_stock_allow')) || $state !== true) return;

			foreach ((array)$this->shop->basket->arProdukte as $product_data)
			{

				if (!$this->checkBestand($product_data['id'], $product_data['menge']))
				{

					$nStockAvailable = $this->getBestand($product_data['id']);

					if ($nStockAvailable <= 0)
					{

						$this->shop->addFrontendError(
							wpsg_translate(
								__('Der Lagerbestand von Produkt "#1#" hat sich zwischenzeitlich verändert. Es sind nur noch #2# Stück verfügbar.', 'wpsg'),
								$this->shop->getProductName($product_data['id']),
								$nStockAvailable
							)
						);

						$error = true;

					}

				}

			}

		} // public function checkCheckout(&$state, &$error, &$arCheckout)

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (isset($_REQUEST['wpsg_lang'])) return;

			if (!array_key_exists('stock', $product_content))
			{

				$this->shop->view['arSubAction']['stock'] = array(
					'title' => __('Bestand / Gew. / Füllm.', 'wpsg'),
					'content' => ''
				);

			}

			$this->shop->view['data'] = $product_data;
			$this->shop->view['arSubAction']['stock']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/product_addedit_content.phtml', false);

		} // public function produkt_add(&$produkt_data)

		/* Modulfunktionen */

		public function calculateProductgroupStock($product_id) {
			
			$oProduct = wpsg_product::getInstance($product_id);
			$oProductGroup = $oProduct->getProductgroup();
			
			if ($oProductGroup->isLoaded()) {
				
				$arProductRows = $this->db->fetchAssoc("SELECT P.`id`, P.`stock` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`pgruppe` = '".wpsg_q($oProductGroup->getId())."' ");
				
				$stock = 0;
				
				foreach ($arProductRows as $p_row) {
					
					$stock += intval($p_row['stock']);
					
				}
				
				$stock_pre = intval($oProductGroup->__get('stock_value'));
				
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_GROUP, [
					'stock_value' => wpsg_q($stock)
				], " `id` = '".wpsg_q($oProductGroup->getId())."' ");
				
				if ($stock_pre !== $stock && is_admin()) {
					
					$this->shop->addBackendMessage(wpsg_translate(
						__('Der Lagerbestand in Produktgruppe #1# wurde von #2# auf #3# automatisch geändert.', 'wpsg'),
						$oProductGroup->getLabel(),
						$stock_pre,
						$stock
					));
					
				}
				
			}
			
		}
		
		/**
		 * Gibt den aktuellen Bestand für ein Produkt zurück
		 */
		public function getBestand($produkt_key)
		{

			$arStock = array();

			if ($this->shop->hasMod('wpsg_mod_productgroups'))
			{

				$product_id = $this->shop->getProduktID($produkt_key);
				$product_data = $this->shop->cache->loadProduct($product_id);

				if (wpsg_isSizedInt($product_data['pgruppe']))
				{

					$pgroup = $this->db->fetchRow("SELECT `stock_aktiv`, `stock_value` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($product_data['pgruppe'])."' ");

					if ($pgroup['stock_aktiv'] === '1')
					{

						$arStock[] = $pgroup['stock_value'];

					}

				}

			}

			if (is_numeric($produkt_key))
			{

				// Lagerbestand in der Datenbank
				$arStock[] = $this->db->fetchOne("SELECT P.`stock` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`id` = '".wpsg_q($produkt_key)."'");

			}
			else if ($this->shop->hasMod('wpsg_mod_productvariants'))
			{

				$arStock[] = $this->shop->callMod('wpsg_mod_productvariants', 'getStockForVariation', array($produkt_key));

			}

			if (!wpsg_isSizedArray($arStock)) return 0;
			else return min($arStock);

		} // public function getBestand($produkt_id)

		/**
		 * Gibt die aktuelle Menge eines Produktes aus der Session zurück
		 */
		public function getAmountSession($product_key)
		{

			$amount_ses = 0;

			if (wpsg_isSizedArray($_SESSION['wpsg']['basket']))
			{

				foreach ($_SESSION['wpsg']['basket'] as $product_index => $product_data)
				{

					if ($product_key == $product_data['id']) $amount_ses += $product_data['menge'];

				}

			}

			return $amount_ses;

		} // public function getAmountSession($product_key)

		/**
		 * Gibt die aktuelle Menge eines Produktes aus der Session zurück
		 * Betrachtet dabei den Gesamt Produktbestand (Für Varianten)
		 */
		public function getAmountSessionById($product_id)
		{

			$amount_ses = 0;

			if (wpsg_isSizedArray($_SESSION['wpsg']['basket']))
			{

				foreach ($_SESSION['wpsg']['basket'] as $product_index => $product_data)
				{

					if ($this->shop->getProduktId($product_data['id']) == $product_id) $amount_ses += $product_data['menge'];

				}

			}

			return $amount_ses;

		} // public function getAmountSessionById($product_id)

		/**
		 * Überprüft ob der Bestand eines Produktes noch ausreichend ist
		 * @return true|false
		 */
		public function checkBestand($produkt_key, $menge)
		{

			if (substr($produkt_key, 0, 3) == 'abo') return;

			$product_id = $this->shop->getProduktID($produkt_key);
			$product_data = $this->shop->cache->loadProduct($product_id);

			if ($this->shop->hasMod('wpsg_mod_productgroups'))
			{

				if (wpsg_isSizedInt($product_data['pgruppe']))
				{

					$pgroup = $this->db->fetchRow("SELECT `stock_aktiv`, `stock_value` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($product_data['pgruppe'])."' ");

					if ($pgroup['stock_aktiv'] === '1')
					{

						$stock = $pgroup['stock_value'];

						/* Lagerbestand der Produktgruppe ist aktiv */
						if ($menge > $stock)
						{

							return false;

						}

					}

				}

			}

			$product_stock = $this->getBestand($produkt_key);

			if ($menge > $product_stock)
			{

				return false;

			}

			return true;


		} // public function checkBestand(&$produkt_key, $menge)
		
		public function calculation_saveProduct(&$oCalculation, $calc_product, &$db_product_data, $finish_order) {
			
			if (!$finish_order) return;
			
			$data = [
				'productkey' => $calc_product['product_key'],
				'menge' => $calc_product['amount']
			];
			
			$product_id = $this->shop->getProduktID($data['productkey']);

			// Tabellen sperren
			if ($this->shop->get_option('wpsg_lockOrderTables') != '1')
			{

				$arLockTables[WPSG_TBL_PRODUCTS] = "WRITE";
				$arLockTables[$this->shop->prefix.'posts'] = "WRITE";
				$arLockTables[$this->shop->prefix.'options'] = "WRITE";
				if ($this->shop->hasMod('wpsg_mod_productgroups')) $arLockTables[WPSG_TBL_PRODUCTS_GROUP] = "WRITE";

				$strQuery = "LOCK TABLES ";
				foreach ($arLockTables as $table_name => $locktype) $strQuery .= " `".$table_name."` ".$locktype.",";
				$this->db->Query(substr($strQuery, 0, -1));

			}

			//sleep(20);

			$product_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($product_id)."' ");
			$stock = $data['menge'];

			// Fehlerbehandlung für Produktbestand
			//if ($product_data['stock_count'] == '1' && $stock > $product_data['stock']) return false;
			if ($product_data['stock_count'] == '1' &&
				(($this->shop->get_option('wpsg_mod_stock_allow') === '1') && ($stock > $product_data['stock'])))
			{
				$this->db->unlockTables();
				return false;
			}

			/* Produktgruppe runterzählen */
			if ($this->shop->hasMod('wpsg_mod_productgroups'))
			{

				$pgroup = $this->db->fetchRow("SELECT `stock_aktiv`, `stock_value` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($product_data['pgruppe'])."' ");

				if ($pgroup['stock_aktiv'] == '1')
				{

					//if ($pgroup['stock_aktiv'] == '1' && ($stock > $pgroup['stock_value'])) return false;
					if (($this->shop->get_option('wpsg_mod_stock_allow') === '1') && ($stock > $pgroup['stock_value']))
					{
						$this->db->unlockTables();
						return false;
					}

					$this->db->Query("
						UPDATE `".WPSG_TBL_PRODUCTS_GROUP."` SET `stock_value` = `stock_value` - '".wpsg_q($stock)."' WHERE `id` = '".wpsg_q($product_data['pgruppe'])."'
					");
				}

			}

			// Lagerbestand im Produkt zählen?
			if ($product_data['stock_count'] == '1')
			{

				if ($this->shop->hasMod('wpsg_mod_productvariants') && preg_match('/^pv_(.*)/', $data['productkey']))
				{

					// Bestand der Variationen runterzählen
					// und Produktbestand als Summe der Variantenbestände
					$this->shop->callMod('wpsg_mod_productvariants', 'reduceStock', array($data['productkey'], $stock));

				}
				else
				{
					// Im Produkt selbst runterzählen
					$this->db->Query("UPDATE `".WPSG_TBL_PRODUCTS."` SET `stock` = `stock` - '".wpsg_q($stock)."' WHERE `id` = '".wpsg_q($product_id)."' ");
				}
				
				// Benachrichtigung bei Unterschreiten von mindest Menge
				if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_stock_minstockproduct')))
				{

					$product_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($product_id)."' ");
					$aktStock = $product_data['stock'];
					// Wenn Mindestbestellmenge unterschritten oder gleich
					if ($aktStock <= $product_data['minstockproduct_count']) $this->sendMinStockMail($data['productkey']);

				}

			}

			// Tabellen entsperren
			$this->db->Query("UNLOCK TABLES");

			return true;

		} // public function checkReduceStock(&$data, &$product_data)

        /**
         * Export der Daten als CSV
         * {@inheritDoc}
         * @see wpsg_mod_basic::wpsg_mod_export_loadFields()
         */
        public function wpsg_mod_export_loadFields(&$arFields) {

            $arFields[20]['fields']['mod_stock_stock'] = __('Lagerbestand', 'wpsg');
            $arFields[20]['fields']['mod_stock_minstock'] = __('Mindestlagerbestand', 'wpsg');

        }
        
		public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator) {

		    // Abbrechen wenn uninteressante Felder
            if (!in_array($field_value, ['mod_stock_stock', 'mod_stock_minstock'])) return;
		    
            $arProductExportIDs = [];
            $arReturn = [];
		    
		    if (wpsg_isSizedInt($p_id)) $arProductExportIDs = [$p_id];
            else {

                $oOrder = wpsg_order::getInstance($o_id);
                
                /** @var \wpsg_order_product $oOrderProducts */
                foreach ($oOrder->getOrderProducts() as $oOrderProduct) {
                    
                    if (!in_array($oOrderProduct->getProductId(), $arProductExportIDs)) $arProductExportIDs[] = $oOrderProduct->getProductId();
                    
                }
                
            }
            		                            
		    if ($field_value === 'mod_stock_stock') {
		    		        
		        foreach ($arProductExportIDs as $product_id) {

                    $oProduct = wpsg_product::getInstance($product_id);
                    
                    $arReturn[] = $oProduct->stock;
		            
                }
                
                $return = implode(',', $arReturn);
		        
            } else if ($field_value === 'mod_stock_minstock') {

                foreach ($arProductExportIDs as $product_id) {

                    $oProduct = wpsg_product::getInstance($product_id);

                    $arReturn[] = $oProduct->minstockproduct_count;
                    
                }

                $return = implode(',', $arReturn);
                
            }
			
		}
		
		/**
		 * Sendet eine Mail bei erreichen des Mindestbestandes
		 */
		public function sendMinStockMail($product_key)
		{

			$product_id = $this->shop->getProduktID($product_key);
			$product_data = $this->shop->cache->loadProduct($product_id);

			if ($this->shop->get_option('wpsg_htmlmail') === '1')
			{

				$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/stockmail_html.phtml', false);

			}
			else
			{

				$mail_html = false;

			}

			$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/stockmail.phtml', false);

			$to = $product_data['minstockproduct_mail'];
			if (!wpsg_isSizedString($to)) $to = get_bloginfo('admin_email');

			list($subject, $text) = $this->shop->sendMail($mail_text, $to, 'wpsgmodstockminstockmail', array(), false, false, $mail_html, wpsg_translate(__('Der Minimalbestand im Produkt #1# wurde erreicht.', 'wpsg'), $product_data['name']));

		} // public function sendMinStockMail($product_key)

	} // class wpsg_mod_stock extends wpsg_mod_basic

