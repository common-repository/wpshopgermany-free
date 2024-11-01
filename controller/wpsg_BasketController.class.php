<?php
	
	/**
	 * Dieser Controller übernimmt die Aktionen des Warenkorbs
	 */
	class wpsg_BasketController extends wpsg_SystemController {
		
		private static $_outputCache = [];
		
		public function content_filter(&$content) {
			
			if (wpsg_get_the_id() ==  $this->shop->getPagePID(wpsg_ShopController::PAGE_BASKET)) {
				
				parent::dispatch();
				
				if (isset($_REQUEST['wpsg_checkout']))
				{
					
					$this->checkoutAction($content);
					
				}
				else if (isset($_REQUEST['wpsg_checkout2']))
				{
					
					$this->checkout2Action($content);
					
				}
				else if (isset($_REQUEST['wpsg_overview']))
				{
					
					$this->overviewAction($content);
					
				}
				else if (isset($_REQUEST['wpsg_done']))
				{
					
					$this->doneAction($content);
					
				}
				else
				{
					
					$this->basketAction($content);
					
				}
				
			}
			
		} // public function content_filter($content)
		
		/**
		 * Gibt den Warenkorb aus, wird von content_filter aufgerufen
		 */
		public function basketAction(&$content)
		{
			
			if (isset($_REQUEST['wpsg_action']) && $_REQUEST['wpsg_action'] == 'showProdukt' && $_REQUEST['produkt_id'] > 0)
			{
				
				$content = $this->shop->renderProdukt($_REQUEST['produkt_id']); return;
				
			}
			
			// Basket aus Session zusammenbauen
			$this->shop->basket->initFromSession();
			$this->shop->basket->save(false);
			
			$this->shop->view['basket'] = $this->shop->basket->toArray(false, false);
			
			$this->shop->view['error'] = array();
			if (wpsg_isSizedArray($_SESSION['wpsg']['errorFields'])) $this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
			
			$this->shop->checkShippingAvailable();
			$this->shop->checkPaymentAvailable();
			
			// Sollte es nur eine Versand/Zahlungsart geben, dann diese verwenden
			
			$array_keys_shipping = array_keys($this->shop->arShipping);
			if (sizeof($this->shop->arShipping) == 1) $_SESSION['wpsg']['checkout']['shipping'] = $array_keys_shipping[0];
			
			$array_keys_payment = array_keys($this->shop->arPayment);
			if (sizeof($this->shop->arPayment) == 1) $_SESSION['wpsg']['checkout']['payment'] = $array_keys_payment[0];
			
			if (sizeof($this->shop->arShipping) == 1 || sizeof($this->shop->arPayment) == 1)
			{
				
				$this->shop->basket->initFromSession(true);
				$this->shop->view['basket'] = $this->shop->basket->toArray(false, false);
				
			}
			
			// Zur Sicherheit überprüfe ich hier noch einmal die Zahlungsarten
			// Es kann vorkommen, das eine Zahlungsart voreingestellt ist die nicht mehr verfügbar ist
			// Ist nicht ganz schön, da aber checkShippingAvailable von toArray() abhängig ist
			if (isset($_SESSION['wpsg']['checkout']['shipping']) && (is_array($this->shop->arShipping) && !array_key_exists($_SESSION['wpsg']['checkout']['shipping'], $this->shop->arShipping)))
			{
				
				unset($_SESSION['wpsg']['checkout']['shipping']);
				unset($this->shop->basket->arCheckout['shipping']);
				
				$this->shop->view['basket'] = $this->shop->basket->toArray(false, false);
				
			}
			
			$this->shop->view['arLander'] = $this->db->fetchAssocField("SELECT L.`id`, L.`name` FROM `".WPSG_TBL_LAND."` AS L ORDER BY L.`name` ", "id", "name");
			
			// colspan für die Zusammenfassung berechnen je nach Option
			$this->shop->view['colspan'] = 3;
			if ($this->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;
			
			if ($this->shop->hasMod('wpsg_mod_onepagecheckout') && ($this->get_option('wpsg_mod_onepagecheckout_basket') == 1))
			{
				
				$this->shop->view['arShipping'] = $this->shop->arShipping;
				$this->shop->view['arPayment'] = $this->shop->arPayment;
				$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
				$this->shop->view['laender'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `name` ASC");
				
				$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_onepagecheckout/onepage.phtml', false);
				
			}
			else
			{
				
				$content = $this->shop->render(WPSG_PATH_VIEW.'/warenkorb/basket.phtml', false);
				
			}
			
		} // public function basketAction()
		
		public function setBasketData()
		{
			
			// gewählte Lieferanschrift im Warenkorb setzen
			if (wpsg_isSizedInt($_REQUEST['set_land']))
			{
				
				if ($this->shop->hasMod('wpsg_mod_shippingadress') && wpsg_isSizedInt($_SESSION['wpsg']['checkout']['shipping_land']))
				{
					
					$_SESSION['wpsg']['checkout']['shipping_land'] = $_REQUEST['set_land'];
					
				}
				else
				{
					
					$_SESSION['wpsg']['checkout']['land'] = $_REQUEST['set_land'];
					
				}
				
				// Für die Abfrage von EU Leistungsort Produkten eintragen
				if (wpsg_isSizedInt($_SESSION['wpsg']['customerCountry'])) $_SESSION['wpsg']['customerCountry'] = $_REQUEST['set_land'];
				
			}
			
			// gewählte Versandart setzen
			if (wpsg_isSizedString($_REQUEST['set_shipping']))
			{
				
				$_SESSION['wpsg']['checkout']['shipping'] = $_REQUEST['set_shipping'];
				
			}
			
			// gewählte Zahlungsart setzen
			if (wpsg_isSizedString($_REQUEST['set_payment']))
			{
				
				$_SESSION['wpsg']['checkout']['payment'] = $_REQUEST['set_payment'];
				
			}
			
			$this->shop->callMods('setBasketData');
			
		} // public function setBasketData()
		
		/**
		 * Eingabe der Kundendaten (Checkout)
		 */
		public function checkoutAction(&$content)
		{
			
			$this->setBasketData();
			
			$this->shop->basket->initFromSession();
			
			$bError = true;
			$this->shop->callMods('checkBasket', array(&$bError));
			
			if ($bError === false)
			{
				
				$this->basketAction($content);
				return;
				
			}
			
			// Wenn keine Produkte drin sind, dann mit Fehlermeldung zum Warenkorb leiten
			$arProductIDs = $this->shop->basket->getProductIDs();
			
			if (!wpsg_isSizedArray($arProductIDs))
			{
				
				$this->shop->addFrontendError(__('Es befinden sich keine Produkte im Warenkorb.', 'wpsg'));
				$this->basketAction($content);
				return;
				
			}
			
			// Sollte die Session noch nicht mit Werten gefüllt sein dann hier die Kundenvoreinstellungen laden
			$this->shop->checkCustomerPreset();
			
			if ($this->shop->hasMod('wpsg_mod_onepagecheckout'))
			{
				if ($this->shop->get_option('wpsg_mod_onepagecheckout_basket') == 1)
				{
					$this->basketAction($content); return;
				}
				else
				{
					$content = $this->shop->callMod('wpsg_mod_onepagecheckout', 'onepage');	return;
				}
			}
			
			$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
			
			$this->shop->view['error'] = array();
			if (wpsg_isSizedArray($_SESSION['wpsg']['errorFields'])) $this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
			
			$this->shop->view['basket'] = $this->shop->basket->toArray();
			$this->shop->view['basket']['checkout']['geb'] = wpsg_fromDate($this->shop->view['basket']['checkout']['geb'], true);
			
			$this->shop->view['laender'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `name` ASC");
			
			$this->shop->view['arAnrede']= explode('|', $this->shop->get_option('wpsg_admin_pflicht')['anrede_auswahl']);
			
			$content = $this->shop->render(WPSG_PATH_VIEW.'/warenkorb/checkout.phtml', false);
			
		} // public function checkoutAction()
		
		/**
		 * Eingabe der Versand- und Zahlungsart
		 */
		private function checkout2Action(&$content)
		{
			
			$this->shop->basket->initFromSession();
			
			// Wenn keine Produkte drin sind, dann mit Fehlermeldung zum Warenkorb leiten
			$temp = $this->shop->basket->getProductIDs();
			if (!wpsg_isSizedArray($temp))
			{
				
				$this->shop->addFrontendError(__('Es befinden sich keine Produkte im Warenkorb.', 'wpsg'));
				$this->basketAction($content);
				return;
				
			}
			
			$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
			$this->shop->view['basket'] = $this->shop->basket->toArray();
			
			// Alte Werte löschen / Warum war das mal drin ? 			
			//unset($_SESSION['wpsg']['checkout']['shipping']);
			//unset($_SESSION['wpsg']['checkout']['payment']);
			
			$this->shop->checkShippingAvailable();
			$this->shop->checkPaymentAvailable();
			
			if (sizeof($this->shop->arShipping) > 0 && (!in_array($_SESSION['wpsg']['checkout']['shipping'], array_keys($this->shop->arShipping)) || !in_array($_SESSION['wpsg']['checkout']['payment'], array_keys($this->shop->arPayment)))) {
				
				if (!in_array($_SESSION['wpsg']['checkout']['shipping'], array_keys($this->shop->arShipping))) $_SESSION['wpsg']['checkout']['shipping'] = array_keys($this->shop->arShipping)[0];
				if (!in_array($_SESSION['wpsg']['checkout']['payment'], array_keys($this->shop->arPayment))) $_SESSION['wpsg']['checkout']['payment'] = array_keys($this->shop->arPayment)[0];
				
				$this->shop->basket->initFromSession(true);
				$this->shop->view['basket'] = $this->shop->basket->toArray();
				
			}
			
			$this->shop->view['arShipping'] = $this->shop->arShipping;
			$this->shop->view['arPayment'] = $this->shop->arPayment;
			
			$content = $this->shop->render(WPSG_PATH_VIEW.'/warenkorb/checkout2.phtml', false);
			
		} // private function checkout2Action()
		
		/**
		 * Funktion für die Bestellzusammenfassung
		 */
		private function overviewAction(&$content) {
			
			if (!isset($this->_outputCache['overview'])) {
				
				$this->shop->basket->initFromSession();
				
				// Wenn keine Produkte drin sind, dann mit Fehlermeldung zum Warenkorb leiten
				$temp = $this->shop->basket->getProductIDs();
				if (!wpsg_isSizedArray($temp))
				{
					
					$this->shop->addFrontendError(__('Es befinden sich keine Produkte im Warenkorb.', 'wpsg'));
					$this->basketAction($content);
					return;
					
				}
				
				// Wenn die Overview Prüfung nicht bestanden wurde, dann zum Checkout leiten
				if (!$this->shop->basket->checkCheckout(3))
				{
					
					if ($this->shop->hasMod('wpsg_mod_onepagecheckout') && ($this->shop->get_option('wpsg_mod_onepagecheckout_basket') == 1))
					{
						$this->basketAction($content);
						return;
					}
					else
					{
						$this->checkoutAction($content);
						return;
					}
					
				}
				
				$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();				
				$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];				
				$this->shop->view['basket'] = $this->shop->basket->toArray();
				
				$this->shop->checkShippingAvailable();
				$this->shop->checkPaymentAvailable();
				 				
				if (!isset($this->shop->arShipping[$this->shop->view['basket']['checkout']['shipping']]) || !isset($this->shop->arShipping[$this->shop->view['basket']['checkout']['payment']])) {
					
					$this->shop->basket->initFromSession(true);
					$this->shop->view['basket'] = $this->shop->basket->toArray();
					
				}

            	$this->shop->view['arShipping'] = $this->shop->arShipping;
            	$this->shop->view['arPayment'] = $this->shop->arPayment;
            
				// colspan für die Zusammenfassung berechnen je nach Option
				$this->shop->view['colspan'] = 2;
				if ($this->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;
				
				$this->_outputCache['overview'] = $this->shop->render(WPSG_PATH_VIEW.'/warenkorb/overview.phtml', false);
	
			 }
			
			$content = $this->_outputCache['overview'];
			
		} // private function overviewAction()
		
		/**
		 * Seite, die nach der Bestellung angezeigt wird
		 * @param $content
		 * @throws \wpsg\Exception
		 */
		private function doneAction(&$content) {
			
			if (!wpsg_checkInput($_REQUEST['order_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			else {
						
				// Hash verifizierne
				$order_id = intval($_REQUEST['order_id']);
				
				if ($order_id <= 0) throw \wpsg\Exception::getInvalidValueException();
			
				$oOrder = wpsg_order::getInstance($order_id);
				
				$hash = md5($oOrder->__get('secret').$order_id.$oOrder->__get('secret'));
				
				if (hash_equals($hash, rawurldecode($_REQUEST['wpsg_done']))) {					
					 
					$this->shop->basket->initFromDB($order_id);
					$this->shop->view['basket'] = $this->shop->basket->toArray();
					$this->shop->view['o_id'] = $order_id;
					$this->shop->view['order'] = $this->shop->cache->loadOrder($order_id);
					$this->shop->view['customer'] = $this->shop->cache->loadKunden($this->shop->view['order']['k_id']);
					
					$content = $this->shop->render(WPSG_PATH_VIEW.'/warenkorb/done.phtml', false);	
					
				} else $this->addFrontendError(__('Der Link ist ausgelaufen oder ungültig.', 'wpsg'));
				
			}
				
		} // private function doneAction()
		
		/**
		 * Verarbeitet die Anfragen und leitet anschließend weiter
		 */
		public function template_redirect()
		{
			
			if (isset($_REQUEST['wpsg_cron'])) {
				
				// ersetzt den direkten Aufruf der cron.php
				
				$this->shop->callMods('cron');
				$this->shop->update_option('wpsg_lastCron', time());
				
				exit;
				
			}
			
			
			if (isset($_REQUEST['wpsg_form_data']))
			{
				
				parse_str($_REQUEST['wpsg_form_data'], $request);
				$_REQUEST = array_merge_recursive($_REQUEST, $request);
				
			}
			
			if (isset($_REQUEST['wpsg']['action']) && $_REQUEST['wpsg']['action'] == 'customeranswer')
			{
				
				// Der Preisdialog wurde beantwortet
				$_SESSION['wpsg']['priceDialog'] = true;
				$_SESSION['wpsg']['customertype'] = $_REQUEST['wpsg']['customertype'];
				$_SESSION['wpsg']['checkout']['land'] = $_REQUEST['wpsg']['customerCountry'];
				
				$this->redirect($_REQUEST['wpsg']['redirect']);
				
			}
			else if (wpsg_isSizedString($_REQUEST['wpsg']['action'], 'updateCheckout'))
			{
				
				// Checkout Aktualisierung über AJAX				
				if (isset($_REQUEST['wpsg']['checkout']['shipping'])) $_SESSION['wpsg']['checkout']['shipping'] = $_REQUEST['wpsg']['checkout']['shipping'];
				if (isset($_REQUEST['wpsg']['checkout']['payment'])) $_SESSION['wpsg']['checkout']['payment'] = $_REQUEST['wpsg']['checkout']['payment'];
				
				die("1");
				
			}
			else if (wpsg_isSizedString($_REQUEST['wpsg']['action'], 'widget'))
			{
				
				the_widget('wpsg_basket_widget');
				die();
				
			}
			
			// Der Warenkorb wurde abgeschickt
			if (isset($_REQUEST['wpsg_basket_submit']))
			{
				
				// Ein Modul hat einen Fehler im Warenkorb entdeckt, zurück zum Warenkorb leiten
				if ($this->shop->callMods('basket_checkoutAction', array(&$this)) == false)
				{
					
					$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
					
				}
				
			}
			
			// Warenkorb wurde verarbeitet, soll ein Modul die Umleitung übernehmen?
			if (wpsg_isSizedString($_REQUEST['wpsg_mod_submit']))
			{
				
				$this->shop->callMod($_REQUEST['wpsg_mod_submit'], 'basket_submitSuccess');
				
			}
			
			if (isset($_REQUEST['wpsg']['action']) && $_REQUEST['wpsg']['action'] == 'customerquestion')
			{
				
				// Das Widget mit der Frage bzgl. der Preisberechnung EU Rotz soll angezeigt werden
				parent::dispatch();
				
				$this->shop->view['defaultCountry_id'] = $this->shop->getFrontendCountry(true);
				$this->shop->view['url'] = $_REQUEST['wpsg']['url'];
				
				$geo_code = wpsg_geo_code();
				$country_id = $this->db->fetchOne("SELECT L.`id` FROM `".WPSG_TBL_LAND."` AS L WHERE L.`kuerzel` = '".wpsg_q($geo_code)."' ");
				if (wpsg_isSizedInt($country_id)) $this->shop->view['defaultCountry_id'] = $country_id;
				
				$this->shop->view['arCountry'] = wpsg_country::find();
				
				die($this->shop->render(WPSG_PATH_VIEW.'/warenkorb/customerquestion.phtml'));
				
			}
			else if (isset($_REQUEST['wpsg']['submit']) && intval($_REQUEST['wpsg']['menge']) > 0 && intval($_REQUEST['wpsg']['produkt_id']) > 0)
			{
				
				// Ein Produktformular wurde abgeschickt
				// http://wp.home/wpsg4/warenkorb/?wpsg[produkt_id]=6&wpsg[submit]=1&wpsg[menge]=1
				// https://shop.maennchen1.de/warenkorb/?wpsg[produkt_id]=63&wpsg[submit]=1&wpsg[menge]=1&wpsg_vp[5]=10
				
				parent::dispatch();
				
				$this->shop->callMods('basket_preInsert');
				
				$product_key = $_REQUEST['wpsg']['produkt_id'];
				$this->shop->callMods('getProductKeyFromRequest', array(&$product_key, $_REQUEST['wpsg']['produkt_id'], $_REQUEST));
				
				// Produkt hinzufügen
				$bOK = $this->shop->basket->addProduktToSession($product_key, $_REQUEST['wpsg']['menge']);
				
				if ($this->shop->get_option('wpsg_afterinsert') == '2') die();
				
				if ($this->shop->get_option('wpsg_afterinsert') == '3')
				{
					
					$this->shop->basket->initFromSession(true);
					
					$this->shop->view['product_data'] = $this->shop->loadProduktArray($this->shop->getProduktId($product_key));
					$this->shop->view['oProduct'] = wpsg_product::getInstance($this->shop->getProduktID($product_key));
					$this->shop->view['product_key'] = $_REQUEST['wpsg']['produkt_id'];
					$this->shop->view['amount_add'] = $_REQUEST['wpsg']['menge'];
					$this->shop->view['amount_basket'] = $this->shop->basket->getBasketAmount($_REQUEST['wpsg']['produkt_id']);
					$this->shop->view['product_index'] = $GLOBALS['wpsg_lastInsertIndex'];
					
				}
				
				if ($this->shop->get_option('wpsg_afterinsert') == '4')
				{
					
					$this->redirect($_REQUEST['myReferer']);
					
				}
				
				// Erfolgsmeldung hinzufügen
				if ($bOK === true) $this->shop->addFrontendMessage(__('Produkt erfolgreich in den Warenkorb gelegt.', 'wpsg'));
				else if ($bOK === -1)
				{
					
					// Hier Warenkorb ohne Meldung anzeigen / Für den Fall, dass das Produkt nur einmal im Warenkorb auftauchen darf
					
				}
				else
				{
					
					if ($this->shop->get_option('wpsg_afterinsert') == '3')
					{
						
						die($this->shop->render(WPSG_PATH_VIEW.'warenkorb/messageDialog.phtml'));
						
					}
					
					// Fehler beim hinzufügen, hier einfach auf der Seite bleiben					
					$this->redirect($_REQUEST['myReferer']);
					
				}
				
				if (wpsg_isSizedInt($_REQUEST['wpsg']['ajax'], 1))
				{
					
					if ($this->shop->get_option('wpsg_afterinsert') == '3')
					{
						
						$this->shop->view['product_data']['preis'] = $this->shop->view['oProduct']->getPrice($product_key, $this->shop->getFrontendTaxview());
						
						$this->shop->view['content'] = $this->shop->render(WPSG_PATH_VIEW.'warenkorb/ajaxDialog.phtml', false);
						
						die($this->shop->render(WPSG_PATH_VIEW.'warenkorb/messageDialog.phtml'));
						
					}
					else
					{
						
						die("1");
						
					}
					
				}
				else if (wpsg_isSizedInt($this->get_option('wpsg_afterinsert'), 1))
				{
					
					$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
					
				}
				else
				{
					
					$this->redirect($_REQUEST['myReferer']);
					
				}
				
			}
			else if (isset($_REQUEST['wpsg_basket_ajax']))
			{
				
				$content = '';
				
				$this->basketAction($content);
				
				die($content);
			}
			else if (isset($_REQUEST['wpsg_redirect_basket']))
			{
				
				// Button "Zurück zum Warenkorb wurde gedrückt"
				// -> Abbruch und Checkout nicht speichern
				
				$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
				
			}
			else if (isset($_REQUEST['wpsg_redirect_checkout']))
			{
				
				// Weiterleitung zum Checkout ohne speichern
				// Wird normalerweise bei "Zurück zur Kasse" im Checkout2 aufgerufen
				
				$this->redirect($this->shop->getURL(wpsg_ShopController::URL_CHECKOUT));
				
			}
			else if (isset($_REQUEST['wpsg_gutschein_add']))
			{
				
				// Gutscheincode soll hinzugefügt werden
				$code = $_REQUEST['wpsg']['gutschein'];
				
				$gs_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `code` = '".wpsg_q($code)."'");
				
				if (!isset($gs_db['id']) || $gs_db['id'] <= 0)
				{
					
					$this->addFrontendError(__('Ein Gutschein mit diesem Code existiert nicht!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'gutschein';
					
					$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
					
				}
				else
				{
					
					// Gültigkeit des Gutscheins prüfen
					$tStart = strtotime($gs_db['start_date']);
					$tEnd = strtotime($gs_db['end_date']);
					
					$arBasket = $this->shop->cache->getShopBasketArray();
					
					if (!(wpsg_time() >= $tStart && wpsg_time() <= $tEnd))
					{
						
						// Zeit ist abgelaufen	
						$this->addFrontendError(__('Dieser Gutscheincode ist nicht gültig!', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'gutschein';
						
						$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
						
					}
					else
					{
						
						if ($gs_db['o_id'] > 0 && $gs_db['multi'] !== '1') {
							
							// Gutschein ist bereits verbraucht
							$this->addFrontendError(__('Dieser Gutscheincode wurde bereits eingelöst und ist nicht mehrfach verwendbar.', 'wpsg'));
							$_SESSION['wpsg']['errorFields'][] = 'gutschein';
							
						}
						else
						{
							
							$bCheckDiscount = true;
							
							if ($this->shop->get_option('wpsg_mod_discount_voucher') === '1') {
								
								if ($this->shop->callMod('wpsg_mod_discount', 'hasDiscount', [$arBasket]) === true) $bDiscount = true;
								else $bDiscount = false;
								
								if ($bDiscount) {
									
									$this->addFrontendError(__('Dieser Gutscheincode kann nicht eingelöst werden, da bereits Rabatte angerechnet sind.', 'wpsg'));
									$_SESSION['wpsg']['errorFields'][] = 'gutschein';
									
									$bCheckDiscount = false;
									
								}
								
							}
							
							if ($bCheckDiscount) {
								
								$oVoucher = wpsg_voucher::getInstance($gs_db['id']);
								
								if (!$oVoucher->isUsabel()) {
									
									$this->addFrontendError(__('Gutschein kann nicht verwendet werden. Er ist nicht mehr aktiv oder verbraucht.', 'wpsg'));
									
								} else {
									
									// Gutschein ist von der größe des Warenkorbes begrenzt.
									if (wpsg_tf($gs_db['minvalue']) > 0 && wpsg_tf($gs_db['minvalue']) > $arBasket['sum']['preis'])
									{
										
										$this->addFrontendError(wpsg_translate(__('Dieser Gutscheincode kann nicht eingelöst werden, da der Mindestbestellwert von #1# noch nicht erreicht ist.', 'wpsg'), wpsg_ff(wpsg_tf($gs_db['minvalue']), $this->shop->get_option('wpsg_currency'))));
										$_SESSION['wpsg']['errorFields'][] = 'gutschein';
										
									}
									else
									{
										
										$this->shop->basket->initFromSession();
										$arProductIDsBasket = $this->shop->basket->getProductIDs();
										
										$wrongProducts = array();
										
										// Produktgültigkeit prüfen
										$tarr = wpsg_explode(',', $gs_db['products']);
										if (wpsg_isSizedArray($tarr))
										{
											
											if (sizeof(array_diff($arProductIDsBasket, wpsg_explode(',', $gs_db['products']))) > 0)
											{
												
												$wrongProducts = array_diff($arProductIDsBasket, wpsg_explode(',', $gs_db['products']));
												
											}
											
										}
										
										// Produktgruppengültigkeit prüfen
										$tarr = wpsg_explode(',', $gs_db['productgroups']);
										if (wpsg_isSizedArray($tarr))
										{
											
											$ProductGroupIDs = wpsg_explode(',', $gs_db['productgroups']);
											
											$arProductIDs = $this->db->fetchAssocField("
												SELECT P.`id` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`pgruppe` IN (".wpsg_implode(',', $ProductGroupIDs).")
											");
											
											if (sizeof(array_diff($arProductIDsBasket, $arProductIDs)) > 0)
											{
												
												$wrongProducts = array_merge($wrongProducts, array_diff($arProductIDsBasket, $arProductIDs));
												
											}
											
										}
										
										if (wpsg_isSizedArray($wrongProducts))
										{
											
											if (sizeof($wrongProducts) === 1)
											{
												
												$product = $this->shop->loadProduktArray(array_values($wrongProducts)[0]);
												$this->addFrontendError(wpsg_translate(__('Gutschein konnte nicht hinzugefügt werden, da er für das Produkt #1# nicht zulässig ist.', 'wpsg'), $product['name']));
												
											}
											else
											{
												
												$arProductNames = array();
												foreach ($wrongProducts as $product_id)
												{
													
													$product_data = $this->shop->loadProduktArray($product_id);
													$arProductNames[] = $product_data['name'];
													
												}
												
												$this->addFrontendError(wpsg_translate(__('Gutschein konnte nicht hinzugefügt werden, da er für die Produkte #1# nicht zulässig ist.', 'wpsg'), wpsg_implode(', ', $arProductNames)));
												
											}
											
										}
										else
										{
											
											$this->addFrontendMessage(__('Gutschein wurde der Bestellung erfolgreich hinzugefügt.', 'wpsg'));
											$this->shop->basket->addGutscheinToSession($gs_db['value'], $gs_db['calc_typ'], $gs_db['code'], $gs_db['id']);
											
										}
										
									}
									
								}
								
							}
							
						}
						
						$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
						
					}
					
				}
				
			}
			else if (isset($_REQUEST['wpsg_order']))
			{
				
				parent::dispatch();
				
				// Bestellung abschließen
				$this->shop->basket->initFromSession();
				
				$bOK = $this->shop->basket->checkCheckout();
				
				if ($bOK)
				{
					
					$bModulError = false; $this->shop->callMods('checkFinaly', array(&$bModulError));
					
					if ($bModulError === true)
					{
						
						$this->redirect($this->shop->getURL(wpsg_ShopController::URL_OVERVIEW));
						
					}
					else if (!$this->shop->hasMod('wpsg_mod_ordercondition') && wpsg_getStr($_REQUEST['wpsg']['agb']) != '1' )
					{
						
						$this->addFrontendError(
							wpsg_translate(
								__('Sie müssen unsere #1# und #2# gelesen haben, um eine Bestellung durchzuführen! Bitte setzen Sie unten das entsprechende Häkchen!', 'wpsg'),
								'<a href="'.$this->shop->getURL(wpsg_ShopController::URL_AGB).'">'.__('AGB', 'wpsg').'</a>',
								'<a href="'.$this->shop->getURL(wpsg_ShopController::URL_WIDERRUF).'">'.__('Widerrufsbelehrung', 'wpsg').'</a>'
							)
						);
						
						$this->redirect($this->shop->getURL(wpsg_ShopController::URL_OVERVIEW));
						
					}
					else
					{
						
						$order_id = $this->shop->basket->save();
						
						$this->redirect($this->shop->getDoneURL($order_id));
						
					}
					
				}
				else
				{
					
					$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
					
				}
				
			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'remove') && isset($_REQUEST['wpsg_produkt']))
			{
				
				parent::dispatch();
				
				if (preg_match('/^voucher_(\d+)?/', $_REQUEST['wpsg_produkt'], $m)) {
					
					foreach ($_SESSION['wpsg']['gs'] as $gs_k => $gs) {
						
						if (intval($gs['id']) === intval($m[1])) unset($_SESSION['wpsg']['gs'][$gs_k]);
						
					}
					
					$this->shop->addFrontendMessage(__('Gutschein erfolgreich entfernt.', 'wpsg'));
					
				}
				else
				{
					
					// Produkt entfernen
					if ($this->shop->basket->removeProduktFromSession($_REQUEST['wpsg_produkt']))
					{
						
						// Erfolgsmeldung hinzufügen
						$this->shop->addFrontendMessage(__('Produkt erfolgreich entfernt.', 'wpsg'));
						
					}
					
					$this->shop->callMods('basket_afterRemove');
					
				}
				
				$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
				
			}
			else if (isset($_REQUEST['wpsg_basket_refresh']))
			{
				
				parent::dispatch();
				
				$this->shop->callMods('basket_preUpdate');
				
				$bError = false;
				//die(wpsg_debug($_REQUEST['wpsg']['menge']));
				// Warenkorb aktualisieren
				
				foreach ($_REQUEST['wpsg']['menge'] as $product_index => $produkt_menge)
				{
					
					$product_key = $_SESSION['wpsg']['basket'][$product_index]['id'];
					$product_data = $this->shop->cache->loadProduct($this->shop->getProduktId($product_key));
					
					if ($product_data['basket_multiple'] == 1)
					{
						
						// Mehrfach mit beliebiger Menge
						if (!$this->shop->basket->updateProduktFromSession($product_index, $produkt_menge)) $bError = true;
						
					}
					else if ($product_data['basket_multiple'] == 2) {
						
						// Mehrfach mit Menge 1
						if ($produkt_menge > 1) {
							
							// Grundprodukt auf 1 setzen
							$this->shop->basket->updateProduktFromSession($product_index, 1);
							
							$request = $_REQUEST;
							$this->shop->callMods('basket_preMultiple', array($product_index));
							
							$bOK = true;
							for ($i = 1; $i < $produkt_menge; $i ++) {
								
								$bOK = $bOK && $this->shop->basket->addProduktToSession($product_key, 1);
								
							}
							
							$_REQUEST = $request;
							
							if (!$bOK) $bError = true;
							
						}
						else
						{
							
							// Menge ist 1 gebliegen, hier normal aktualisieren
							if (!$this->shop->basket->updateProduktFromSession($product_index, $produkt_menge)) $bError = true;
							
						}
						
					}
					else if ($product_data['basket_multiple'] == 4)
					{
						
						if ($produkt_menge != "1")
						{
							
							$this->shop->addFrontendError(__('Produkt darf nur einmal im Warenkorb auftauchen.', 'wpsg'));
							
						}
						
					}
					else
					{
						
						// Nur einmal mit beliebiger Menge
						if (!$this->shop->basket->updateProduktFromSession($product_index, $produkt_menge)) $bError = true;
						
					}
					
				} // foreach
				
				$this->setBasketData();
				$this->shop->cache->clearShopBasketArray();
				
				$this->shop->callMods('basket_afterUpdate', array(&$bError));
				
				if (!$bError)
				{
					
					// Fehler ist aufgetreten
					$this->shop->addFrontendMessage(__('Warenkorb erfolgreich aktualisiert', 'wpsg'));
					
				}
				
				$this->redirect($this->shop->getURL(wpsg_ShopController::URL_BASKET));
				
			}
			// Der Ausschluß von wpsg_mod ist nötig, da PayPal PLUS die Versandart ändert
			else if (isset($_REQUEST['wpsg']['checkout']) && !isset($_REQUEST['wpsg_mod']))
			{
				
				// Checkout wurde abgeschickt
				parent::dispatch();
				
				if (wpsg_isSizedInt($_SESSION['wpsg']['customerCountry']) && $_SESSION['wpsg']['customerCountry'] != $_SESSION['wpsg']['land']) $_SESSION['wpsg']['customerCountry'] = $_SESSION['wpsg']['land'];
				
				$this->shop->callMods('doCheckout');
				
				$this->shop->basket->initFromSession(true);
				
				// Checkout1 muss immer geprüft werden, ist z.B. für das Kundenmodul notwendig!				
				$bOK = $this->shop->basket->checkCheckout(1);
				
				if (isset($_REQUEST['wpsg_checkout']))
				{
					
					// Änderungen bei der Lieferadresse nach dem "overview" beachten
					if (isset($_REQUEST['wpsg']['checkout']['act_checkout_shippingadress']))
					{
						if ($_REQUEST['wpsg']['checkout']['act_checkout_shippingadress'] !== 'act') unset($_SESSION['wpsg']['checkout']['act_checkout_shippingadress']);
					}
					
					if ($this->shop->hasMod('wpsg_mod_onepagecheckout'))
					{
						
						$bOK = $bOK && $this->shop->basket->checkCheckout(2);
						
						$target_error = $this->shop->getURL(wpsg_ShopController::URL_CHECKOUT);
						$target = $this->shop->getURL(wpsg_ShopController::URL_OVERVIEW);
						
					}
					else
					{
						
						$target_error = $this->shop->getURL(wpsg_ShopController::URL_CHECKOUT);
						
						if (array_key_exists($this->shop->basket->arCheckout['shipping'], $this->shop->arShipping) && array_key_exists($this->shop->basket->arCheckout['payment'], $this->shop->arPayment) && $this->shop->get_option('wpsg_skip_checkout2') === '1' && $this->shop->basket->arCheckout['payment'] != 20)
						{
							
							$target = $this->shop->getURL(wpsg_ShopController::URL_OVERVIEW);
							
						}
						else
						{
							
							$target = $this->shop->getURL(wpsg_ShopController::URL_CHECKOUT2);
							
						}
						
					}
					
				}
				else if (isset($_REQUEST['wpsg_checkout2']))
				{
					
					$bOK = $this->shop->basket->checkCheckout(2);
					
					$target_error = $this->shop->getURL(wpsg_ShopController::URL_CHECKOUT2);
					$target = $this->shop->getURL(wpsg_ShopController::URL_OVERVIEW);
					
				}
				else
				{
					
					die(__('Unerwarteter Fehler!', 'wpsg'));
					
				}
				
				// Speziell für PayPal PLUS, da an den IFrame gesendet wird
				if (wpsg_getStr($_REQUEST['wpsg_checkout']) === 'ppp')
				{
					
					unset($_SESSION['wpsg']['checkout']['payment']);
					
					if (!$bOK) die('<script type="application/javascript">/* <![CDATA[ */ parent.wpsg_ppp_handleError(); /* ]]> */</script>');
					else die('<script type="application/javascript">/* <![CDATA[ */ parent.wpsg_ppp_run(); /* ]]> */</script>');
					
				}
				
				if (!$bOK)
				{
					
					// Es sind Fehler aufgetreten, wieder zum Checkout leiten
					$this->redirect($target_error);
					
				}
				else
				{
					
					$this->redirect($target);
					
				}
				
				die();
				
			}
			
		} // public function template_redirect()
		
	} // class wpsg_BasketController extends wpsg_SystemController

