<?php
	
	/**
	 * Diese Klasse kapselt die Produkte
	 * @author Daschmi
	 */
	class wpsg_basket
	{
		
		/** @var wpsg_db */
		var $db;
		
		/** @var wpsg_ShopController */
		var $shop;
		
		/** Array mit den Produkten */
		public $arProdukte;
		
		/** Gutscheinwert */
		public $gs_value = false;
		
		/** Gutscheinberechnungstyp */
		public $gs_calc = false;
		
		/** Gutscheincode */
		public $gs_code = false;
		
		/** Gutschein ID */
		public $gs_id = false;
		
		/** True wenn aus Session geladen */
		public $loadFromSession = false;
		
		/** Wenn der Warenkorb aus der DB kommt ist hier die BestellID drin */
		public $o_id = false;
		
		/** Enthält die ID der Bestellung wenn aus DB geladen */
		public $db_id = false;
		
		public $arCheckout;
		
		/**
		 * Konstruktor
		 */
		public function __construct()
		{
			
			$this->db = &$GLOBALS['wpsg_db'];
			$this->shop = &$GLOBALS['wpsg_sc'];
			
			$this->reset();
			
		} // public function __construct()
		
		public function reset()
		{
			
			$this->arProdukte = array();
			$this->arCheckout = array();
			$this->db_id = false;
			$this->o_id = false;
			$this->loadFromSession = false;
			$this->gs_id = false;
			$this->gs_code = false;
			$this->gs_calc = false;
			
		}
		
		/**
		 * Fügt ein Produkt in die Session ein
		 */
		public function addProduktToSession($produkt_key, $menge)
		{
			
			if (!wpsg_isSizedArray($_SESSION['wpsg']['basket'])) $_SESSION['wpsg']['basket'] = array();
			
			$product_data = $this->shop->cache->loadProduct($this->shop->getProduktId($produkt_key));
			
			if ($product_data['basket_multiple'] == 1)
			{
				
				// Nur einmal mit beliebiger Menge
				// Hier nichts machen, es wird weiter unten hinzugefügt
				
			}
			else if ($product_data['basket_multiple'] == 2)
			{
				
				// Mehrfach mit Menge 1
				if ($menge > 1)
				{
					
					$bOK = true;
					
					// Hier die Funktion mehrfach aufrufen (mit Menge 1) und dann abbrechen
					for ($i = 1; $i <= $menge; $i ++)
					{
						
						$bOK = $bOK && $this->addProduktToSession($produkt_key, 1);
						
					}
					
					return $bOK;
					
				}
				
			}
			else if ($product_data['basket_multiple'] == 4)
			{
				
				// Einmal mit Menge 1
				// Basket durchgehen und schauen ob es schon drin ist und dann abbrechen sonst mit 1 hinzufügen
				foreach ((array)$_SESSION['wpsg']['basket'] as $p_key => $p)
				{
					
					if ($p['id'] == $produkt_key)
					{
						
						// Produkt ist schon drin, ich breche hier mit -1 ab, damit zum Warenkorb geleitet wird und eine Meldung wird angezeigt
						$this->shop->addFrontendMessage(__('Das Produkt befindet sich bereits im Warenkorb, es kann nur einmal erworben werden.', 'wpsg'));
						return -1;
						
					}
					
				}
				
				$menge = 1;
				
			}
			else
			{
				
				// Nur einmal mit beliebiger Menge
				// Basket durchgehen und schauen ob es schon drin ist und dann abbrechen sonst neu hinzufügen
				foreach ((array)$_SESSION['wpsg']['basket'] as $p_key => $p)
				{
					
					if ($p['id'] == $produkt_key)
					{
						
						$bOK = $this->shop->callMods('basket_produkttosession', array($produkt_key, &$menge, &$_SESSION['wpsg']['basket'][$p_key]));
						
						if ($bOK === false)
						{
							
							return false;
							
						}
						
						$GLOBALS['wpsg_lastInsertIndex'] = $p_key;
						
						$_SESSION['wpsg']['basket'][$p_key]['menge'] += intval($menge);
						
						// Sollte die Menge auf 0 korrigiert werden dann Produkt entfernen
						if ($_SESSION['wpsg']['basket'][$p_key]['menge'] <= 0)
						{
							
							unset($_SESSION['wpsg']['basket'][$p_key]);
							
						}
						
						return true;
						
					}
					
				}
				
			}
			
			$ses_data = array();
		 
			$bOK = $this->shop->callMods('basket_produkttosession', array($produkt_key, &$menge, &$ses_data));
			
			if ($bOK === false) return false;
			
			// War noch nicht drin => neu hinzufügen
			$ses_data = wpsg_array_merge($ses_data, array(
				'menge' => intval($menge),
				'id' => $produkt_key,
				'referer' => ''
			));
			
			if (isset($_REQUEST['myReferer'])) $ses_data['referer'] = $_REQUEST['myReferer'];
			
			//$_SESSION['wpsg']['basket'][] = wpsg_xss($ses_data);
			array_push($_SESSION['wpsg']['basket'], wpsg_xss($ses_data));
			end($_SESSION['wpsg']['basket']);
			$GLOBALS['wpsg_lastInsertIndex'] = key($_SESSION['wpsg']['basket']);
			
			return true;
			
		} // public function addProduktToBasket($produkt_id, $menge)
		
		/**
		 * Fügt einen Gutschein zum Warenkorb hinzu
		 * @param double $value Der Gutscheinwert
		 * @param string $mode w = Wertgutschein, p = Prozentual
		 */
		public function addGutscheinToSession($value, $calc, $code, $gs_id) {
			  
			if (!isset($_SESSION['wpsg']['gs'])) $_SESSION['wpsg']['gs'] = [];
			
			$_SESSION['wpsg']['gs'][] = wpsg_xss(array(
				'value' => $value,
				'calc' => $calc,
				'code' => $code,
				'id' => $gs_id
			));
			
		} // public function addGutscheinToSession($value, $calc, $code)
		
		/**
		 * Entfernt ein Produkt aus dem Warenkorb
		 */
		public function removeProduktFromSession($produkt_index) {
			
			unset($_SESSION['wpsg']['basket'][$produkt_index]);
			
			$this->shop->callMods('basket_removeProduktFromSession', [$produkt_index]);			
			$this->shop->cache->clearShopBasketArray();
			$this->shop->basket->initFromSession(true);
			
			if ($this->shop->get_option('wpsg_switchtolowestshippingafterproductremove') === '1' && isset($_SESSION['wpsg']['checkout']['shipping'])) {
				
				$oCalculation = \wpsg\wpsg_calculation::getSessionCalculation(true);
				$oCalculation->getCalculationArray();
				
				$min_key = null; $min_value = null;
				
				$this->shop->arShipping = $this->shop->arShippingAll;
				$this->shop->checkShippingAvailable();
				
				foreach ($this->shop->arShipping as $k => $v) {
					
					$price_shipping = $oCalculation->calculateCostKey($v['price']);
					
					if ($min_key === null || $price_shipping < $min_value) {
						
						$min_key = $k;
						$min_value = $price_shipping;
						
					}
					
				}
				
				if ($min_key !== null) {
					
					$_SESSION['wpsg']['checkout']['shipping'] = $min_key;
										
				}
								
			}
			
			return true;
			
		} // public function removeProduktFromSession($produkt_key)
		
		/**
		 * Aktualisiert den Warenkorb in der Session
		 */
		public function updateProduktFromSession($product_index, $produkt_menge)
		{
			
			if (intval($produkt_menge) <= 0)
			{
				
				$this->removeProduktFromSession($product_index);
				return true;
				
			}
			
			
			foreach ($_SESSION['wpsg']['basket'] as $k => $p)
			{
				
				if ($k == $product_index)
				{
					
					$this->shop->callMods('basket_updateProduktFromSession', array(&$product_index, &$produkt_menge));
					$_SESSION['wpsg']['basket'][$k]['menge'] = intval($produkt_menge);
					
					if (!wpsg_isSizedInt($_SESSION['wpsg']['basket'][$k]['menge'])) unset($_SESSION['wpsg']['basket'][$k]);
					
				}
				
			}
			
			return true;
			
		} // public function updateProduktFromSession($produkt_key, $produkt_menge)
		
		/**
		 * Initiiert den Warenkorb aus der Session heraus
		 */
		public function initFromSession($rebuild = false)
		{
			
			if (!isset($_SESSION['wpsg']['basket'])) return;
			if ($this->loadFromSession === true && $rebuild === false) return;
			
			$this->arProdukte = array();
			
			foreach ((array)$_SESSION['wpsg']['basket'] as $produkt_key => $b)
			{
				
				$this->arProdukte[$produkt_key] = $b;
				
			}
			
			if (isset($_SESSION['wpsg']['gs']))
			{
				
				$this->gs_value = $_SESSION['wpsg']['gs']['value'];
				$this->gs_calc = $_SESSION['wpsg']['gs']['calc'];
				$this->gs_code = $_SESSION['wpsg']['gs']['code'];
				$this->gs_id = $_SESSION['wpsg']['gs']['id'];
				
			}
			
			$this->arCheckout = wpsg_array_merge(array(
				'firma' => '',
				'title' => '-1',
				'vname' => '',
				'name' => '',
				'email' => '', 'email2' => '',
				'geb' => '',
				'fax' => '',
				'tel' => '',
				'strasse' => '', 'nr' => '',
				'plz' => '',
				'ort' => '',
				'land' => '',
				'custom' => '',
				'comment' => ''
			), wpsg_isSizedArray($_SESSION['wpsg']['checkout'])?$_SESSION['wpsg']['checkout']:array());
			
			$this->loadFromSession = true;
			
		} // public function initFromSession()
		
		/**
		 * Gibt die ID eines Produktes in der Datenbank zurück
		 */
		public function getProduktDBID($produkt_key)
		{
			
			if (is_numeric($produkt_key)) return $produkt_key;
			else if (preg_match('/pv_\d(.*)/', $produkt_key))
			{
				
				// Varianten Produktschlüssel
				$produkt_id = preg_replace('/(pv_)|(\|(.*))/', '', $produkt_key);
				
				return $produkt_id;
				
			}
			else return false;
			
		}
		
		/**
		 * Wandelt die Daten des Basket in die neue Klasse zur Berechnung
		 * @return wpsg\wpsg_calculation
		 */
		public function toCalculation() {
			
			$b = new \wpsg\wpsg_calculation();
			
			foreach ($this->arProdukte as $p) {
				
				$b->addProduct($p['productkey'], $p['product_index'], $p['preis_netto'], $p['menge'], $p['op_mwst_key'], $p['order_product_id']);
				
			}
			
			return $b;
			
		}
		
		/**
		 * Initiiert den Basket aus der Datenbank
		 */
		public function initFromDB($o_id, $backend = false)
		{
			
			$order = $this->db->fetchRow("
				SELECT
					*
			  	FROM
					`".WPSG_TBL_ORDER."`
			  	WHERE
			  		`id` = '".wpsg_q($o_id)."'
			");
			
			$kunde = $this->db->fetchRow("
				SELECT
					K.*, A.*, K.`id` AS `id`
				FROM
					`".WPSG_TBL_KU."` AS K
					 	LEFT JOIN `".WPSG_TBL_ADRESS."` AS A ON (A.`id` = K.`adress_id`)
				WHERE
					K.`id` = '".wpsg_q($order['k_id'])."'
			");
			 
			$this->arCheckout = array(
				'firma' => $kunde['firma'],
				'title' => $kunde['title'],
				'vname' => $kunde['vname'],
				'name' => $kunde['name'],
				'email' => $kunde['email'],
				'geb' => date('d.m.Y', strtotime($kunde['geb'])),
				'email2' => $kunde['email'],
				'tel' => $kunde['tel'],
				'strasse' => $kunde['strasse'],
				'nr' => $kunde['nr'],
				'fax' => $kunde['fax'],
				'plz' => $kunde['plz'],
				'ort' => $kunde['ort'],
				'land' => $kunde['land'],
				'ustidnr' => $kunde['ustidnr'],
				'custom' => $kunde['custom'],
				'shipping' => $order['type_shipping'],
				'payment' => $order['type_payment'],
				'comment' => $order['comment'],
				'onr' => $order['onr'],
				'knr' => $kunde['knr'],
				'datum' => strtotime($order['cdate'])
			);
			
			if (wpsg_isSizedInt($order['shipping_adress_id'])) {
				
				$shipping_adress = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($order['shipping_adress_id'])."' "); 
			
				foreach (['title', 'name', 'vname', 'firma', 'strasse', 'nr', 'plz', 'ort', 'land', 'tel'] as $sk) {
					
					$this->arCheckout['shipping_'.$sk] = $shipping_adress[$sk];
					
				}
				
			}
			
			if ($this->shop->hasMod('wpsg_mod_autodebit'))
			{
				
				$this->arCheckout['mod_autodebit_name'] = wpsg_getStr($order['mod_autodebit_name']);
				$this->arCheckout['mod_autodebit_blz'] = wpsg_getStr($order['mod_autodebit_blz']);
				$this->arCheckout['mod_autodebit_bic'] = wpsg_getStr($order['mod_autodebit_bic']);
				$this->arCheckout['mod_autodebit_inhaber'] = wpsg_getStr($order['mod_autodebit_inhaber']);
				$this->arCheckout['mod_autodebit_knr'] = wpsg_getStr($order['mod_autodebit_knr']);
				$this->arCheckout['mod_autodebit_iban'] = wpsg_getStr($order['mod_autodebit_iban']);
				
			}
			
			$this->arOrder = $order;
			
			// Produkte
			$arProdukte = $this->db->fetchAssoc("
				SELECT
					OP.`menge`, OP.`price` AS preis_brutto,
					OP.`price`, OP.`price_netto`, OP.`price_brutto`, OP.`mwst_key` AS op_mwst_key,
					OP.`mwst_value` AS `mwst_value`,
					IF (OP.`productkey` != '', OP.`productkey`, P.`id`) AS `productkey`,
					OP.`mod_vp_varkey`,
					OP.`id` AS `order_product_id`,
					P.`id`, P.`anr`, P.`name`, OP.`product_index`
				FROM
					`".WPSG_TBL_ORDERPRODUCT."` AS OP
						LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (OP.`p_id` = P.`id`)
				WHERE
					OP.`o_id` = '".wpsg_q($o_id)."'
			", "product_index");
			
			foreach ($arProdukte as $k => $v)
			{
				
				$arProdukte[$k]['varPriceAdded'] = 1;
				
				if ($v['productkey'] != '') $arProdukte[$k]['id'] = $v['productkey'];
				else if ($v['mod_vp_varkey'] != '') $arProdukte[$k]['id'] = $v['mod_vp_varkey'];
				
				// Preis ist immer in Brutto in der WPSG_TBL_ORDERPRODUCTS
				$arProdukte[$k]['preis_netto'] = wpsg_calculatePreis($v['preis_brutto'], WPSG_NETTO, $v['mwst_value']);
				
				if ($backend)
				{
					
					if ($this->shop->get_option('wpsg_preisangaben') == WPSG_BRUTTO)
					{
						$arProdukte[$k]['preis'] = $arProdukte[$k]['preis_brutto'];
					}
					else
					{
						$arProdukte[$k]['preis'] = $arProdukte[$k]['preis_netto'];
					}
					
				}
				else
				{
					
					if ($this->shop->getFrontendTaxview() == WPSG_BRUTTO)
					{
						$arProdukte[$k]['preis'] = $arProdukte[$k]['preis_brutto'];
					}
					else
					{
						$arProdukte[$k]['preis'] = $arProdukte[$k]['preis_netto'];
					}
					
				}
				
			}
			
			// Gutschein
			if (wpsg_isSizedInt($order['gs_id'])) {
				
				$gs = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` = '".wpsg_q($order['gs_id'])."'");
				
				$this->gs_value = $gs['value'];
				$this->gs_calc = $gs['calc_typ'];
				$this->gs_code = $gs['code'];
				$this->gs_id = $gs['id'];
				
			}
			
			// Gutschein-Wert aus der Order-Tabelle wegen Bearbeitung
			if ($backend) {
				
				$val = abs(@$order['price_gs']);
				$this->gs_value = $val;
				
			}
			
			$this->arProdukte = $arProdukte;
			
			$this->loadFromSession = false;
			$this->o_id = $o_id;
			
		} // public function initFromDB($o_id)
		
		/**
		 * Löscht die Session
		 */
		public function clearSession($customer_id)
		{
			
			$this->shop->callMods('clearSession');
			
			if ($this->shop->get_option('wpsg_afterorder') == '1')
			{
				
				// Löschen
				unset($_SESSION['wpsg']);
				
			}
			else
			{
				
				// In Session belassen
				unset($_SESSION['wpsg']['basket']);
				unset($_SESSION['wpsg']['gs']);
				
				if ($this->shop->hasMod('wpsg_mod_kundenverwaltung'))
				{
					
					$this->shop->callMod('wpsg_mod_kundenverwaltung', 'login', array($customer_id));
					
				}
				
			}
			
		} // public function clearSession()
		
		/**
		 * Sendet die Mails beim Kauf an Admin und Kunden
		 * @param Integer $order_id
		 * @param Array $arBasket (Daten des Basket als Array)
		 */
		public function sendOrderSaveMails($order_id, $arBasket, $bCustomerMail = true, $bAdminMail = true, $bDebug = false)
		{
			
			$oOrder = wpsg_order::getInstance($order_id);
			
			$this->shop->view['oOrder'] = $oOrder;
			$this->shop->view['basket'] = $arBasket;
			
			$this->shop->view['o_id'] = $order_id;
			$this->shop->view['k_id'] = $oOrder->k_id;
			$this->shop->view['order'] = $this->shop->cache->loadOrder($order_id);
			$this->shop->view['customer'] = $this->shop->cache->loadKunden($this->shop->view['oOrder']->k_id);
			
			$this->shop->view['basket']['checkout']['k_id'] = $oOrder->k_id;
			
			if ($bDebug === true)
			{
				
				$this->shop->view['basket']['checkout']['datum'] = time();
				$this->shop->view['basket']['checkout']['onr'] = $this->shop->view['oOrder']->onr;
				$this->shop->view['basket']['checkout']['knr'] = $this->shop->view['customer']['knr'];
				$this->shop->view['basket']['checkout']['comment'] = $oOrder->comment;
				
			}
			
			// Adminmail
			if ($bAdminMail === true)
			{
				
				$arAttach = array();
				// Anhang aufbereiten
				$this->shop->callMod('wpsg_mod_orderupload', 'getAdminAttachment', array(&$arAttach));
				
				$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mailtemplates/adminmail.phtml', false);
				
				if ($this->shop->get_option('wpsg_htmlmail') === '1') $mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mailtemplates/html/adminmail.phtml', false);
				else $mail_html = false;
				
				$this->shop->sendMail($mail_text, $this->shop->get_option('wpsg_adminmail_empfaenger'), 'adminmail', $arAttach, $order_id, $oOrder->k_id, $mail_html);
				
				if ($bDebug === false)
				{
					
					$this->db->ImportQuery(WPSG_TBL_OL, array(
						"o_id" => wpsg_q($order_id),
						"cdate" => "NOW()",
						"title" => wpsg_translate(__('Bestellmail (Admin) an:#1#', 'wpsg'), $this->shop->get_option('wpsg_adminmail_empfaenger')),
						"mailtext" => wpsg_q($mail_text)
					));
					
				}
				
				sleep(1);
				
			}
			
			// Kundenmail
			if ($bCustomerMail === true)
			{
				
				$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mailtemplates/kundenmail.phtml', false);
				
				if ($this->shop->get_option('wpsg_htmlmail') === '1') $mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mailtemplates/html/kundenmail.phtml', false);
				else $mail_html = false;
				
				$arAttachments = array();
				
				if ($this->shop->get_option('wpsg_widerrufsformular_kundenmail') === '1' && wpsg_isSizedString($this->shop->get_option('wpsg_revocationform')))
				{
					
					$revocationFile = WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->shop->get_option('wpsg_revocationform');
					
					if (file_exists($revocationFile) && is_file($revocationFile))
					{
						
						$arAttachments[] = $revocationFile;
						
					}
					
				}
				
				if ($bDebug === true) $empfaenger = $this->shop->get_option('wpsg_adminmail_empfaenger');
				else $empfaenger = $this->arCheckout['email'];
				
				$this->shop->sendMail($mail_text, $empfaenger, 'kundenmail', $arAttachments, $order_id, $oOrder->k_id, $mail_html);
				
				if ($bDebug === false)
				{
					
					$this->db->ImportQuery(WPSG_TBL_OL, array(
						"o_id" => wpsg_q($order_id),
						"cdate" => "NOW()",
						"title" => wpsg_translate(__('Bestellmail (Kunde) an:#1#', 'wpsg'), $this->arCheckout['email']),
						"mailtext" => wpsg_q($mail_text)
					));
					
				}
				
			}
			
		} // public function sendOrderSaveMails($order_id)
		
		/**
		 * Speichert die Bestellung in die Datenbank
		 * Ist der Parameter $finish_order auf false so bleiben die Bestelldaten in der Session (Zur Vorspeicherung)
		 */
		public function save($finish_order = true, $sendmail = true, $save = false) {
			
			$knr = '';
			 			
			try {
				
				// Eintrag in Kundentabelle
				$data = array(
					'email' => wpsg_q(wpsg_getStr($this->arCheckout['email'])),
					'invisible' => 1,
					'geb' => wpsg_q(wpsg_toDate(wpsg_getStr($this->arCheckout['geb']))),
					'ustidnr' => wpsg_q(wpsg_getStr($this->arCheckout['ustidnr']))
				);
				
				$this->shop->callMods('basket_save_kunde', array(&$data, &$this->arCheckout));
				
				$k_id = wpsg_getStr($this->arCheckout['id']);
				
				// Sollte die ID des eingeloggten Kunden nicht mit der ID in der Bestellung passen
				if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['id'])) {
					
					if (wpsg_isSizedInt($k_id) && $k_id != $_SESSION['wpsg']['checkout']['id']) {
						
						$k_id = $_SESSION['wpsg']['checkout']['id'];
						
					}
					
				}
				
				$data['custom'] = array();
				
				if (isset($this->arCheckout['custom']) && wpsg_isSizedArray($this->arCheckout)) $data['custom'] = $this->arCheckout['custom'];
				
				if (wpsg_isSizedInt($k_id)) {
					
					// Kunde existiert bereits, hier muss aufgepasst werden dass nur abgefragte Kundenvariablen ersetzt werden
					$kunde_data = $this->shop->cache->loadKunden($k_id);
					$custom_old = @unserialize($kunde_data['custom']);
					
					if (wpsg_isSizedArray($custom_old))
					{
						
						$data['custom'] = wpsg_array_merge($custom_old, (array)$this->arCheckout['custom']);
						
					}
					
				}
				
				$data['custom'] = serialize($data['custom']);
				
				$update_customer_data = array();
				
				if ($k_id > 0) {
					
					$this->db->UpdateQuery(WPSG_TBL_KU, $data, "`id` = '".wpsg_q($k_id)."'");
					$knr = $this->db->fetchOne("SELECT `knr` FROM `".WPSG_TBL_KU."` WHERE `id` = '".wpsg_q($k_id)."'");
					
					$this->shop->callMods('customer_updatePwd', array(&$k_id, &$this->arCheckout['password']));
					$this->shop->callMods('customer_updated', array(&$k_id));
					
				} else {
					
					if ($this->shop->hasMod('wpsg_mod_customergroup')) {
						
						if (wpsg_isSizedInt($this->shop->get_option('wpsg_page_mod_kundenverwaltung_group_checkout'))) $data['group_id'] = $this->shop->get_option('wpsg_page_mod_kundenverwaltung_group_checkout');
						
					}
					
					if (wpsg_isSizedString($data['email'])) {
						
						if (!wpsg_isSizedInt($_SESSION['wpsg']['checkout']['id'])) {
							
							$k_id = $this->db->ImportQuery(WPSG_TBL_KU, $data);
							
							$_SESSION['wpsg']['checkout']['id'] = $k_id;
							
						} else {
							
							$k_id = wpsg_getInt($_SESSION['wpsg']['checkout']['id']);
							
						}
						
						$knr = $this->shop->buildKNR($k_id);
						
						$update_customer_data['knr'] = wpsg_q($knr);
						
					}
					
					$this->shop->callMods('customer_created', array(&$k_id, &$this->arCheckout['password']));
					
				}
				
				// Adresse speichern wenn der Kunde noch keine Adresse hat
				$customer_data = $this->shop->cache->loadKunden($k_id, true);
				
				$adress_data = array(
					'firma' => wpsg_q(wpsg_getStr($this->arCheckout['firma'])),
					'title' => wpsg_q(wpsg_getStr($this->arCheckout['title'])),
					'vname' => wpsg_q(wpsg_getStr($this->arCheckout['vname'])),
					'name' => wpsg_q(wpsg_getStr($this->arCheckout['name'])),
					'strasse' => wpsg_q(wpsg_getStr($this->arCheckout['strasse'])),
					'nr' => wpsg_q(wpsg_getStr($this->arCheckout['nr'])),
					'plz' => wpsg_q(wpsg_getStr($this->arCheckout['plz'])),
					'ort' => wpsg_q(wpsg_getStr($this->arCheckout['ort'])),
					'tel' => wpsg_q(wpsg_getStr($this->arCheckout['tel'])),
					'fax' => wpsg_q(wpsg_getStr($this->arCheckout['fax'])),
					'land' => wpsg_q(wpsg_getStr($this->arCheckout['land']))
				);
				
				$update_data = [
					'k_id' => wpsg_q($k_id),
					'language' => wpsg_q($this->shop->getCurrentLanguageCode())
				];
				
				if (!wpsg_isSizedInt($customer_data['adress_id'])) {
					
					$adress_data['cdate'] = 'NOW()';
					
					if (wpsg_isSizedString($adress_data['name']) && wpsg_isSizedString($adress_data['vname'])) {
						
						$customer_data['adress_id'] = wpsg_q($this->db->ImportQuery(WPSG_TBL_ADRESS, $adress_data));
						$update_customer_data['adress_id'] = $customer_data['adress_id'];
						
					}
					
				} else {
					
					$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adress_data, " `id` = '".wpsg_q($customer_data['adress_id'])."' ");
					
				}
				
				$re_adress_id = $customer_data['adress_id'];
				
				// Kundendaten ggf. aktualisieren
				if (wpsg_isSizedArray($update_customer_data)) $this->db->UpdateQuery(WPSG_TBL_KU, $update_customer_data, "`id` = '".wpsg_q($k_id)."'");
				
				$arBasket['checkout']['k_id'] = $k_id;
				$arBasket['checkout']['knr'] = $knr;
				
				if (($finish_order === true) && (wpsg_getStr($this->arCheckout['diff_shippingadress']) == '1')) {
					
					// Gesonderte Lieferadresse speichern
					
					$adata['cdate'] = 'NOW()';
					
					$adata['title'] 	= $this->arCheckout['shipping_title'];
					$adata['vname'] 	= $this->arCheckout['shipping_vname'];
					$adata['name'] 		= $this->arCheckout['shipping_name'];
					$adata['strasse'] 	= $this->arCheckout['shipping_strasse'];
					$adata['nr'] 	= $this->arCheckout['shipping_nr'];
					$adata['plz'] 		= $this->arCheckout['shipping_plz'];
					$adata['ort'] 		= $this->arCheckout['shipping_ort'];
					$adata['firma'] 	= $this->arCheckout['shipping_firma'];
					$adata['land'] 		= $this->arCheckout['shipping_land'];
					
					$update_data['shipping_adress_id'] = wpsg_q($this->db->ImportQuery(WPSG_TBL_ADRESS, $adata));
					
				}
				
				$update_data['adress_id'] = $re_adress_id;
				
				$update_data['custom_data']['basket']['oOrder'] = Array();
				$update_data['custom_data'] = wpsg_q(serialize($update_data['custom_data']));
				
				// Neu durch calculation Klasse speichern				
				$oCalculation = \wpsg\wpsg_calculation::getSessionCalculation();
				//$o_id = $oCalculation->toDB(@$_SESSION['wpsg']['order_id'], [], $finish_order);
				
				$o_id = @$_SESSION['wpsg']['order_id'];
				
				if ($save) {
					
					$o_id = $oCalculation->toDB(@$_SESSION['wpsg']['order_id'], [], $finish_order);
					
					$_SESSION['wpsg']['order_id'] = $o_id;
					
				}
				
				if ($finish_order) {
					 
					$update_data['onr'] = wpsg_q($this->shop->buildONR(@$_SESSION['wpsg']['order_id'], $k_id, $knr));
					
					$o_id = $oCalculation->toDB(@$_SESSION['wpsg']['order_id'], [
						'comment' => $this->arCheckout['comment']
					], $finish_order);
										
				}
				 
				if ($finish_order === true) $update_data['status'] = wpsg_ShopController::STATUS_EINGEGANGEN;
				else $update_data['status'] = wpsg_ShopController::STATUS_UNVOLLSTAENDIG;
				
				if (wpsg_isSizedInt($o_id)) $this->db->UpdateQuery(WPSG_TBL_ORDER, $update_data, "`id` = '".wpsg_q($o_id)."'");
				
				// URL Benachrichtigung beim Kauf
				if ($finish_order === true) {
					
					foreach ($oCalculation->getCalculationArray()['product'] as $p) {
						 
						$produkt_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($p['product_id'])."'");
						
						if ($produkt_db['posturl'] != '' && $produkt_db['posturl_verkauf'] == '1')
						{
							
							$this->shop->notifyURL($produkt_db['posturl'], $p['product_key'], $p['amount'], $o_id, 0, false, array(
								'product_index' => $p['product_index']
							));
							
						}
						
					}
					
					$this->shop->cache->clearOrderCache($o_id);
					$this->shop->cache->clearKundenCache($k_id);
					
					$this->shop->callMods('basket_save_done', array(&$o_id, &$k_id, &$this));
					$this->shop->callMods('basket_save_done_array', array(&$o_id, &$k_id, &$arBasket));
					
					$this->shop->basket->initFromDB($o_id);
					$arBasket = $this->shop->basket->toArray();
					
					// Wenn CrefoPay aktiv, werden die Mails später versendet
					if (!$this->shop->hasMod('wpsg_mod_crefopay') && $sendmail) $this->sendOrderSaveMails($o_id, $arBasket);
					
					// Bestellung direkt auf "Zahlung akzeptiert setzen" wenn option aktiv ist
					if ($this->shop->get_option('wpsg_emptyorder_clear') == '1' && $arBasket['sum']['preis_gesamt_brutto'] == 0)
					{
						
						$this->shop->setOrderStatus($o_id, 100, true);
						
					}
					
					// Eintrag in die Kundentabelle
					$kdata = array(
						'invisible' => 0 	// 0=vollständige Bestellung
					);
					
					$this->db->UpdateQuery(WPSG_TBL_KU, $kdata, "`id` = '".wpsg_q($k_id)."'");
					 
					// Alte BestellID muss nach Abschluss entfernt werden
					unset($_SESSION['wpsg']['order_id']);
					unset($_SESSION['wpsg']['checkout']['payment_amount']);
					unset($_SESSION['wpsg']['checkout']['paymentId']);
					unset($_SESSION['wpsg']['checkout']['payer_id']);
					
					$this->clearSession($k_id);
					
				} else {
					 
					$_SESSION['wpsg']['order_id'] = $o_id;
					
				}
				
			} catch (Exception $e) {
				
				$this->db->unlockTables();
				
				die($e->getMessage());
				
			}
			
			$this->db->unlockTables();
			
			return $o_id;
			
		} // public function save()
		
		/**
		 * Wird als letzte Überprüfung der Bestellung aufgerufen und leitet zu overview.phtml wenn $error true wird
		 */
		public function checkFinaly(&$error) { }
		
		/**
		 * Überprüft die Korrektheit der Kunden- und Bestelldaten
		 */
		public function checkCheckout($state = true)
		{
			
			$bError = false;
			
			// Darf hier nicht überschrieben werden sonst keine Feldmarkierung für ordercondition
			if (!wpsg_isSizedArray($_SESSION['wpsg']['errorFields'])) $_SESSION['wpsg']['errorFields'] = array();
			
			$custom_config = $GLOBALS['wpsg_sc']->loadPflichtFeldDaten();
			
			$this->shop->callMods('checkCheckout', array(&$state, &$bError, &$this->arCheckout));
			
			if ($state == 1 || $state)
			{
				
				// Anrede überprüfen
				if ($custom_config['anrede'] != '2' && $custom_config['anrede'] != '1' && $this->arCheckout['title'] == '-1')
				{
					
					$this->shop->addFrontendError(__('Bitte im Feld "Anrede" eine Angabe machen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'title';
					$bError = true;
					
				}
				
				// Firma
				if ($custom_config['firma'] != '2' && $custom_config['firma'] != '1' && trim($this->arCheckout['firma']) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte im Feld "Firma" eine Angabe machen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'firma';
					$bError = true;
					
				}
				
				// Vorname
				if ($custom_config['vname'] != '2' && $custom_config['vname'] != '1' && trim(wpsg_getStr($this->arCheckout['vname'])) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte im Feld "Vorname" eine Angabe machen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'vname';
					$bError = true;
					
				}
				
				// Name
				if ($custom_config['name'] != '2' && $custom_config['name'] != '1' && trim(wpsg_getStr($this->arCheckout['name'])) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte im Feld "Name" eine Angabe machen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'name';
					$bError = true;
					
				}
				
				// Geburtsdatum
				if (
					($custom_config['geb'] != '2' && $custom_config['geb'] != '1' && !wpsg_isValidGeb($this->arCheckout['geb'])) ||
					(wpsg_isSizedString($this->arCheckout['geb']) && !wpsg_isValidGeb($this->arCheckout['geb']))
				
				)
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingabe im Feld "Geburtsdatum" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'geb';
					$bError = true;
					
				}
				
				// E-Mail Adresse
				if (
					($custom_config['email'] != '2' && $custom_config['email'] != '1' && !wpsg_isValidEMail($this->arCheckout['email'])) ||
					(wpsg_isSizedString($this->arCheckout['email']) && !wpsg_isValidEMail($this->arCheckout['email']))
				)
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingabe der E-Mail Adresse überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'email';
					$bError = true;
					
				}
				
				// E-Mail Eingabeüberprüfung
				if ($custom_config['emailconfirm'] == '1')
				{
					
					$this->arCheckout['email'] = strtolower($this->arCheckout['email']);
					$this->arCheckout['email2'] = strtolower($this->arCheckout['email2']);
					
					if ($this->arCheckout['email'] != wpsg_getStr($this->arCheckout['email2']))
					{
						
						$this->shop->addFrontendError(__('Bitte überprüfen Sie die Eingaben in der E-Mail Bestätigung!', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'email';
						$bError = true;
						
					}
					
				}
				
				// Telefonnummer validieren
				if ($custom_config['tel'] != '2' && $custom_config['tel'] != '1' && $this->arCheckout['tel'] == '')
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingabe im Feld "Telefonnummer" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'tel';
					$bError = true;
					
				}
				
				// Straße prüfen
				if ($custom_config['strasse'] != '2' && $custom_config['strasse'] != '1' && wpsg_getStr($this->arCheckout['strasse']) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "Straße" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'strasse';
					$bError = true;
					
				}
				
				// Hausnummer
				if (wpsg_getStr($custom_config['wpsg_showNr']) === '1' && $custom_config['strasse'] != '2' && $custom_config['strasse'] != '1' && wpsg_getStr($this->arCheckout['nr']) == '') {
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "Straße" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'nr';
					$bError = true;
					
				}
				
				// Fax prüfen
				if ($custom_config['fax'] != '2' && $custom_config['fax'] != '1' && $this->arCheckout['fax'] == '')
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "Fax" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'fax';
					$bError = true;
					
				}
				
				// PLZ überprüfen
				if ($custom_config['plz'] != '2' && $custom_config['plz'] != '1' && wpsg_getStr($this->arCheckout['plz']) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "PLZ" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'plz';
					$bError = true;
					
				}
				
				// Ort überprüfen
				if ($custom_config['ort'] != '2' && $custom_config['ort'] != '1' && wpsg_getStr($this->arCheckout['ort']) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "Ort" überprüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'ort';
					$bError = true;
					
				}
				
				// Land überprüfen
				if ($custom_config['land'] != '2' && $custom_config['land'] != '1' && $this->arCheckout['land'] <= 0)
				{
					
					$this->shop->addFrontendError(__('Bitte ein Land auswählen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'land';
					$bError = true;
					
				}
				
				// UStIdNr prüfen
				if ($custom_config['ustidnr'] != '2' && $custom_config['ustidnr'] != '1' && !preg_match("/^[a-zA-Z]+\d+/", $this->arCheckout['ustidnr']))
				{
					
					$this->shop->addFrontendError(__('Bitte die Eingaben im Feld "UStIdNr." prüfen!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'ustidnr';
					$bError = true;
					
				}
				
				// Benutzerdefinierte Felder prüfen
				foreach ((array)$custom_config['custom'] as $c_id => $c)
				{
					
					// Wenn die Kundenvariable nicht im Request drin ist dann auch nichts machen
					// Problem war der Fall, dass eine Kundenvariable nicht angezeigt wurde
					if (!isset($this->arCheckout['custom'][$c_id])) continue;
					
					if ($c['show'] == '0')
					{
						
						if ($c['typ'] == '2')
						{
							
							// Checkbox
							if ($this->arCheckout['custom'][$c_id] != '1')
							{
								
								$this->shop->addFrontendError(wpsg_translate(__('Bitte "#1#" akzeptieren!', 'wpsg'), __($c['name'], 'wpsg')));
								$_SESSION['wpsg']['errorFields'][] = 'custom_'.$c_id;
								$bError = true;
								
							}
							
						}
						else if ($c['typ'] == '1')
						{
							
							// Auswahl
							if ($this->arCheckout['custom'][$c_id] == '-1')
							{
								
								$this->shop->addFrontendError(wpsg_translate(__('Bitte eine Auswahl im Feld "#1#" treffen!', 'wpsg'), __($c['name'], 'wpsg')));
								$_SESSION['wpsg']['errorFields'][] = 'custom_'.$c_id;
								$bError = true;
								
							}
							
						}
						else if ($c['typ'] == '0')
						{
							
							// Texte
							if (trim($this->arCheckout['custom'][$c_id]) == '')
							{
								
								$this->shop->addFrontendError(wpsg_translate(__('Bitte machen Sie in Feld "#1#" eine Angabe!', 'wpsg'), __($c['name'], 'wpsg')));
								$_SESSION['wpsg']['errorFields'][] = 'custom_'.$c_id;
								$bError = true;
								
							}
							
						}
						
					}
					
				}
				
			}
			
			/**
			 * Versand- und Zahlungsarten nur Checken wenn State=2 oder alle State=true (Alles prüfen)
			 * Wenn Einseiten-Checkout aktiv, dann auch bei State=1 prüfen aber nicht(!) Wenn Profil oder Registrierung abgeschickt wird
			 */
			if ($state == 2 || $state === true ||
				($this->shop->hasMod('wpsg_mod_onepagecheckout') && $state == 1 && !isset($_REQUEST['wpsg_mod_kundenverwaltung_save']) && !isset($_REQUEST['wpsg_mod_kundenverwaltung_register'])))
			{
				
				$this->shop->checkShippingAvailable();
				$this->shop->checkPaymentAvailable();
				
				// Versandart prüfen
				if (!isset($this->arCheckout['shipping']) || !array_key_exists($this->arCheckout['shipping'], $this->shop->arShipping) || $this->arCheckout['shipping'] == '')
				{
					
					$this->shop->addFrontendError(__('Bitte eine gültige Versandart auswählen.', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'shipping';
					$bError = true;
					
				}
				
				// Zahlungsart prüfen
				if (!$this->shop->hasMod('wpsg_mod_crefopay'))
				{
					
					if (!isset($this->arCheckout['payment']) || !array_key_exists($this->arCheckout['payment'], $this->shop->arPayment) || $this->arCheckout['payment'] == '')
					{
						
						// Fake Zahlungsart PayPal Plus soll keinen Fehler schreiben
						if ($this->arCheckout['payment'] != 'ppp')
						{
							
							$this->shop->addFrontendError(__('Bitte eine gültige Zahlungsart auswählen.', 'wpsg'));
							$_SESSION['wpsg']['errorFields'][] = 'payment';
							$bError = true;
							
						}
						
					}
					
				}
				
			}
			
			// prüft ob tatsächlich mind. ein Produkt im Warenkorb liegt
			if (isset($_SESSION['wpsg']['basket']) && count($_SESSION['wpsg']['basket']) < 1 && !is_numeric($state))
			{
				$this->shop->addFrontendError(__('Keine Produkte im Warenkorb.', 'wpsg'));
				$_SESSION['wpsg']['errorFields'][] = 'empty_basket';
				$bError = true;
			}
			
			return !$bError;
			
		} // public function checkCheckout()
		
		/**
		 * Überprüft den Basket Array auf ein EU-Leistungsort Produkt und gibt true oder false zurück
		 */
		public function hasEULeistungsortProduct(&$arBasket)
		{
			
			foreach ($arBasket['produkte'] as $p)
			{
				
				if (wpsg_isSizedInt($p['euleistungsortregel'])) return true;
				
			}
			
			return false;
			
		} // public function hasEULeistungsortProduct(&$arBasket)
		
		/**
		 * Wandelt die in der Session gespeicherten Produkte in einen aufgewerteten Array
		 */
		public function toArray($backend = false, $clearVK = false)
		{
			
							
			$arReturn = array();
			
			if (wpsg_isSizedInt($_SESSION['wpsg']['order_id'])) $arReturn['oOrder'] = wpsg_order::getInstance($_SESSION['wpsg']['order_id']);
			
			$noMwSt = false;
			
			if (isset($this->arCheckout['land'])) {
			
				$land = $this->shop->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->arCheckout['land'])."'");
			
				if ($land['mwst'] == '1' || $this->shop->get_option('wpsg_kleinunternehmer') == '1') $noMwSt = true;
				else if ($land['mwst'] == '2' && wpsg_isSizedString($this->arCheckout['ustidnr'])) $noMwSt = true;
				else $noMwSt = false;
				
			}
			
			$arReturn['noMwSt'] = $noMwSt;
			$arReturn['produkte'] = array();
			$arReturn['mwst'] = array();
			$arReturn['sum'] = array(
				'preis' => 0,
				'preis_netto' => 0,
				'preis_brutto' => 0,
				'preis_gesamt_brutto' => 0,
				'preis_gesamt_netto' => 0,
				'preis_payment' => 0,
				'preis_shipping' => 0,
				'preis_shipping_netto' => 0,
				'preis_shipping_brutto' => 0,
				'preis_rabatt' => 0
			);
			
			// Hier sammel ich die Produktpreise
			$arProductPrice = array(
				WPSG_NETTO => array(),
				WPSG_BRUTTO => array()
			);
			
			foreach ($this->arProdukte as $product_index => &$b)
			{
				
				$produkt_id = $this->shop->getProduktID($b['id']);
				
				wpsg_addSet($arReturn['menge'], $b['menge']);
				
				$country = $this->shop->getDefaultCountry();
				
				if (is_numeric($produkt_id))
				{
					
					// Preis wird berechnet daher entfernen
					unset($b['preis']);
					unset($b['preis_netto']);
					unset($b['preis_brutto']);
					unset($b['mwst_key']); // Key muss auch gelöscht werden, damit die loadProduktArray den Preis korrekt ermittelt (Sonst funktioniert es beim 2. Mal nicht)
					
					// in $b sind eventuell auch Moduldaten drin (Produktvariablen)
					$this->shop->country = $land['id'];
					$b = $this->shop->loadProduktArray($produkt_id, $b, true);
					//$this->shop->country = 0;
					
					if (wpsg_isSizedInt($b['euleistungsortregel']))
					{
						
						$this->shop->showEULayer = true;
						$country = $this->shop->getFrontendCountry();
						
					}
					
					if ($backend === true)
					{
						$country = wpsg_country::getInstance($this->arCheckout['land']);
					}
					
					$b['productkey'] = $b['id'];
					
				}
				
				$this->shop->callMods('basket_toArray', array(&$b, $backend, $noMwSt));
				$this->checkMwSt($b['mwst_key'], $country, $arReturn);
				
				if ($noMwSt)
				{
					
					// Damit erreiche ich, dass 0% angezeigt wird, wenn keine MwSt. berechnet wird
					$this->arProdukte[$product_index]['mwst_key'] = false;
					
					$price_product_netto = $b['preis_netto'];
					$price_product_brutto = $b['preis_netto'];
					
				}
				else
				{
					
					$price_product_netto = $b['preis_netto'];
					$price_product_brutto = $b['preis_brutto'];
					
				}
				
				// Hier wird entschieden ob der gerundete oder der genaue Wert zum Gesamtpreis hinzuaddiert wird
				if ($this->shop->addRoundedValues === true)
				{
					
					$b['preis_netto'] = round($price_product_netto, 2);
					$b['preis_brutto'] = round($price_product_brutto, 2);
					
				}
				else
				{
					
					$b['preis_netto'] = $price_product_netto;
					$b['preis_brutto'] = $price_product_brutto;
					
				}
				
				$price_sum_netto = $b['preis_netto'] * $b['menge'];
				$price_sum_brutto = $b['preis_brutto'] * $b['menge'];
				
				if ($this->shop->getFrontendTaxView() == WPSG_NETTO) $b['preis'] = round($b['preis_netto'], 2);
				else $b['preis'] = round($b['preis_brutto'], 2);
				
				$arProductPrice[WPSG_NETTO][$b['mwst_key'].'_'.$country->id][] = $price_sum_netto;
				$arProductPrice[WPSG_BRUTTO][$b['mwst_key'].'_'.$country->id][] = $price_sum_brutto;
				
				$b['product_index'] = $product_index;
				$arReturn['produkte'][$product_index] = $b;
				
			} // foreach Produkte
							
			// Die Basis der Preisberechnung, wenn Brutto, dann wird Netto berechnet
			if ($this->shop->getFrontendTaxview() == WPSG_NETTO)
			{
				
				$base = WPSG_NETTO;
				$calc = WPSG_BRUTTO;
				
			}
			else
			{
				
				$base = WPSG_BRUTTO;
				$calc = WPSG_NETTO;
				
			}
			
			// Jetzt die jeweilige Steuer berechnen, damit die Anzeige stimmt
			foreach ($arProductPrice[$base] as $tax_key_lang => $tax)
			{
				
				$arTaxKey = explode('_', $tax_key_lang);
				$country_id = $arTaxKey[1];
				$country_id = $this->arCheckout['land'];
				$country = wpsg_country::getInstance($country_id);
				$tax_key = $arTaxKey[0];
				
				if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				
				$sum_tax_value = round(wpsg_calculateSteuer(array_sum($tax), $base, $tax_value), 2);
				
				wpsg_addSet($arReturn['sum']['mwst'], $sum_tax_value);
				
				if ($calc === WPSG_NETTO)
				{
					
					$sum_netto = round(wpsg_calculatePreis(array_sum($tax), WPSG_NETTO, $tax_value), 2);
					$sum_brutto = array_sum($tax);
					
				}
				else
				{
					
					$sum_netto = array_sum($tax);
					$sum_brutto = round(wpsg_calculatePreis(array_sum($tax), WPSG_BRUTTO, $tax_value), 2);
					
					// Rundungsfehler abfangen
					if (abs($price_sum_brutto - $sum_brutto) < 0.02) $sum_brutto = $price_sum_brutto;
					
				}
				
				wpsg_addSet($arReturn['sum']['preis_netto'], $sum_netto);
				wpsg_addSet($arReturn['sum']['preis_gesamt_netto'], $sum_netto);
				wpsg_addSet($arReturn['sum']['preis_brutto'], $sum_brutto);
				wpsg_addSet($arReturn['sum']['preis_gesamt_brutto'], $sum_brutto);
				
				if (!$noMwSt)
				{
					/*
					if ((isset($this->arOrder['price_frontend'])) && ($this->arOrder['price_frontend'] == WPSG_BRUTTO))
					{
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['sum'], $sum_tax_value);
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['base_value'], $sum_brutto);

					}
					else if ((isset($this->arOrder['price_frontend'])) && ($this->arOrder['price_frontend'] == WPSG_NETTO))
					{
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['sum'], $sum_tax_value);
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['base_value'], $sum_netto);

					}
					else */
					{
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['sum'], $sum_tax_value);
						wpsg_addSet($arReturn['mwst'][$tax_key_lang]['base_value'], $sum_brutto);
						
					}
					
				}
				
			}
			
			if ($base === WPSG_NETTO)
			{
				
				wpsg_addSet($arReturn['sum']['preis'], $arReturn['sum']['preis_netto']);
				wpsg_addSet($arReturn['sum']['preis_gesamt'], $arReturn['sum']['preis_gesamt_netto']);
				
			}
			else
			{
				
				wpsg_addSet($arReturn['sum']['preis'], $arReturn['sum']['preis_brutto']);
				wpsg_addSet($arReturn['sum']['preis_gesamt'], $arReturn['sum']['preis_gesamt_brutto']);
				
			}
			
			if ($backend)
			{
				$arReturn['backend'] = $backend;
				$arReturn['price_frontend'] = @$this->arOrder['price_frontend'];
				$arReturn['order_rabatt'] = @$this->arOrder['price_rabatt'];
				$arReturn['gs_value'] = @$this->arOrder['price_gs'];
				
			}
			
			// Gutschein einberechnen
			//$this->shop->callMod('wpsg_mod_gutschein', 'basket_toArray_gs', array(&$this, &$arReturn));
			
			// Staffelrabatt
			$this->shop->callMod('wpsg_mod_discount', 'basket_toArray_discount', array(&$this, &$arReturn));
			
			// Kundendaten einfügen
			$arReturn['checkout'] = $this->arCheckout;
						
			$this->shop->callMods('basket_toArray_preshippayment', array(&$this, &$arReturn));
			
			// Die Versandkosten für den Warenkorb berechnen
			// Dies darf im checkout2 nicht passieren, oder wenn der Parameter auf true
			//			if (false && $this->o_id > 0)
			if ($this->o_id > 0)
			{
				
				$order_data = $this->shop->cache->loadOrder($this->o_id);
				$price_shipping = $order_data['price_shipping'];
				$price_payment = $order_data['price_payment'];
				$arReturn['sum']['preis_payment_brutto'] = $order_data['price_payment_brutto'];
				$arReturn['sum']['preis_payment_netto'] = $order_data['price_payment_netto'];
				$arReturn['sum']['preis_shipping_brutto'] = $order_data['price_shipping_brutto'];
				$arReturn['sum']['preis_shipping_netto'] = $order_data['price_shipping_netto'];
				
				/*

				if ($this->shop->getFrontendTaxview() == WPSG_BRUTTO)
				{

					$arReturn['sum']['preis_payment_brutto'] = $price_payment;
					$arReturn['sum']['preis_payment_netto'] = wpsg_calculatePreis($price_payment, WPSG_NETTO, $order_data['mwst_payment']);

					$arReturn['sum']['preis_shipping_brutto'] = $price_shipping;
					$arReturn['sum']['preis_shipping_netto'] = wpsg_calculatePreis($price_shipping, WPSG_NETTO, $order_data['mwst_shipping']);

				}
				else
				{

					$arReturn['sum']['preis_payment_netto'] = $price_payment;
					$arReturn['sum']['preis_payment_brutto'] = wpsg_calculatePreis($price_payment, WPSG_BRUTTO, $order_data['mwst_payment']);

					$arReturn['sum']['preis_shipping_netto'] = $price_shipping;
					$arReturn['sum']['preis_shipping_brutto'] = wpsg_calculatePreis($price_shipping, WPSG_BRUTTO, $order_data['mwst_shipping']);

				}
				*/
			}
			else
			{
				
				// Bin mir nicht sicher warum die Versandkosten/Zahlungskosten im Checkout nicht berechnet wurde
				// Aufgrund von PayPal (PayPal Plus) brauch ich sie aber
				//if (!isset($_REQUEST['wpsg_checkout2']) && !$clearVK)
				{
					
					if ($backend) $arReturn['backend'] = true;
					else $arReturn['backend'] = false;
					
					if (!$backend)
					{
						
						/*
						 * Prüfen ob die gesetzte Zahlungsart auch in den Verfügbaren ist
						 * Ist für die Auswahl der Länder, Zahlungsarten und Versandarten im Warenkorb nötig geworden
						 */
						if (wpsg_isSizedInt($arReturn['checkout']['shipping']) && !@array_key_exists($arReturn['checkout']['shipping'], $this->shop->arShipping))
						{
							
							unset($arReturn['checkout']['shipping']);
							
						}
						
						if (wpsg_isSizedInt($arReturn['checkout']['payment']) && !array_key_exists($arReturn['checkout']['payment'], $this->shop->arPayment))
						{
							
							unset($arReturn['checkout']['payment']);
							
						}
						
					}
					
					// Versandarten können gruppiert sein, vorher trennen
					if (wpsg_isSizedString($arReturn['checkout']['shipping']) && preg_match('/(.*)\-(.*)/', $arReturn['checkout']['shipping']))
					{
						
						$arShipping = explode('-', $arReturn['checkout']['shipping']);
						
						// Ich simuliere hier die Berechnung in einem Extra Array, da die Funktion leider so gebaut ist
						// Sonst ist die Grundlage für die Berechnung nicht die Selbe
						$arBasketPreShipping = $arReturn;
						
						foreach ($arShipping as $shipping)
						{
							
							$basket_calc = $arBasketPreShipping;
							
							$this->shop->callMods('calcShipping', array(&$basket_calc, $shipping));
							
							wpsg_addSet($arReturn['sum']['preis_shipping'], $basket_calc['sum']['preis_shipping']);
							wpsg_addSet($arReturn['sum']['preis_shipping_brutto'], $basket_calc['sum']['preis_shipping_brutto']);
							wpsg_addSet($arReturn['sum']['preis_shipping_netto'], $basket_calc['sum']['preis_shipping_netto']);
							
							wpsg_addSet($arReturn['shipping']['mwst'], $basket_calc['shipping']['mwst']);
							wpsg_addSet($arReturn['shipping']['preis_shipping_netto'], $basket_calc['shipping']['preis_shipping_netto']);
							wpsg_addSet($arReturn['shipping']['preis_shipping_brutto'], $basket_calc['shipping']['preis_shipping_brutto']);
							
							foreach ($basket_calc['mwst'] as $tax_key => $mwst)
							{
								
								$this->shop->basket->checkMwSt(substr($tax_key, 0, 1), $this->shop->getDefaultCountry(), $arReturn);
								
								if (!array_key_exists($tax_key, $arBasketPreShipping['mwst']))
								{
									
									// Satz war vorher noch nicht drin
									$arReturn['mwst'][$tax_key]['sum'] += $mwst['sum'];
									$arReturn['mwst'][$tax_key]['base_value'] += $mwst['base_value'];
									
								}
								else
								{
									
									$arReturn['mwst'][$tax_key]['sum'] += ($mwst['sum'] - $arBasketPreShipping['mwst'][$tax_key]['sum']);
									$arReturn['mwst'][$tax_key]['base_value'] += ($mwst['base_value'] - $arBasketPreShipping['mwst'][$tax_key]['base_value']);
									
								}
								
							}
							
							if (wpsg_isSizedArray($arReturn['shipping']['methods'])) $arReturn['shipping']['methods'][] = $shipping;
							else $arReturn['shipping']['methods'] = array($shipping);
							
						}
						
						//wpsg_debug($arReturn);die();
						
						// In den Produktdaten sind die für das Produkt zulässigen Versandarten gespeichert, hier entferne ich noch die die in dieser Bestellung nicht gewählt wurden
						foreach ($arReturn['produkte'] as &$p)
						{
							
							if (wpsg_isSizedString($p['allowedshipping']))
							{
								
								$arAllowedShipping = explode(',', $p['allowedshipping']);
								
								foreach ($arAllowedShipping as $shipping)
								{
									
									if (!in_array($shipping, $arReturn['shipping']['methods'])) unset($arReturn['shipping']['methods'][$shipping]);
									
								}
								
								$p['order_allowedshipping'] = $arAllowedShipping;
								
							}
							
						}
						
						// Anteilig und kein eindeutiger Satz, wenn zusammengesetzte Versandart
						unset($arReturn['shipping']['mwst']);
						$arReturn['shipping']['tax_rata'] = true;
						
					}
					else
					{
						
						$this->shop->callMods('calcShipping', array(&$arReturn, $arReturn['checkout']['shipping']));
						
					}
					
				}
				
			}
			
			$this->shop->callMods('basket_toArray_final', array(&$this, &$arReturn));
		 
			// Länderdetails laden
			if (wpsg_isSizedInt($arReturn['checkout']['land'])) {
				
				$oCountry = wpsg_country::getInstance($arReturn['checkout']['land']);
				
				$arReturn['land'] = [
					'name' => $oCountry->getName(),
					'shorttext' => $oCountry->getShorttext()
				];
				
			}
			
			if (wpsg_isSizedInt($arReturn['checkout']['shipping_land'])) {
				
				$oCountry = wpsg_country::getInstance($arReturn['checkout']['shipping_land']);
				
				$arReturn['shipping_land'] = [
					'name' => $oCountry->getName(),
					'shorttext' => $oCountry->getShorttext()
				];
				
			}
			
			$oCalculation = new \wpsg\wpsg_calculation();
			
			if (!$this->loadFromSession) {
				
				$oCalculation->fromDB($this->o_id);
				
			} else {
				
				$oCalculation->fromSession();
				
			}
			
			$arCalculation = $oCalculation->getCalculationArray();
			
			unset($p); // Wichtig, da oben mit der Referenzt &$p gearbeitet wurde
			
			foreach ($arCalculation['product'] as $product_index => $p) {
				
				$product_index = $p['product_index'];
				
				$arReturn['produkte'][$product_index]['preis'] = (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$p['netto_single']:$p['brutto_single']);
				$arReturn['produkte'][$product_index]['preis_netto'] = $p['netto_single'];
				$arReturn['produkte'][$product_index]['preis_brutto'] = $p['brutto_single'];
				
				if ($p['tax'] == '0') $arReturn['produkte'][$product_index]['mwst_value'] = 0;
				else $arReturn['produkte'][$product_index]['mwst_value'] =  $arCalculation['tax'][$p['tax_key']]['tax_value'];
				
			}
			
			$arReturn['gs'] = null;
			$arReturn['sum'] = [
				'preis' => (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['productsum_netto']:$arCalculation['sum']['productsum_brutto']),
				'preis_netto' => $arCalculation['sum']['productsum_netto'],
				'preis_brutto' => $arCalculation['sum']['productsum_brutto'],
				'preis_gesamt_brutto' => $arCalculation['sum']['brutto'],
				'preis_gesamt_netto' => $arCalculation['sum']['netto'],
				'preis_payment' => (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['payment_netto']:$arCalculation['sum']['payment_brutto']),
				'preis_shipping' => (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['shipping_netto']:$arCalculation['sum']['shipping_brutto']),
				'preis_shipping_netto' => $arCalculation['sum']['shipping_netto'],
				'preis_shipping_brutto' => $arCalculation['sum']['shipping_brutto'],
				'preis_rabatt' => -1 * (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['discount_netto']:$arCalculation['sum']['discount_brutto']),
				'mwst' => $arCalculation['sum']['tax'],
				'preis_gesamt' => (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['netto']:$arCalculation['sum']['brutto']),
				'preis_rabatt_netto' => -1 * $arCalculation['sum']['discount_netto'],
				'preis_rabatt_brutto' => -1 * $arCalculation['sum']['discount_brutto']
			];
			
			if (isset($arCalculation['sum']['voucher_netto'])) {
				
				$arReturn['sum']['gs'] = -1 * (($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['sum']['voucher_netto']:$arCalculation['sum']['voucher_brutto']);
				
			}
			
			$arReturn['shipping']['mwst'] = wpsg_getStr($arCalculation['shipping'][0]['tax']);
			$arReturn['shipping']['preis_shipping_netto'] = wpsg_getStr($arCalculation['shipping'][0]['netto']);
			$arReturn['shipping']['preis_shipping_brutto'] = wpsg_getStr($arCalculation['shipping'][0]['brutto']);
			
			if (@isset($arCalculation['tax'][$arCalculation['shipping'][0]['tax_key']]['tax_value'])) $arReturn['shipping']['tax_value'] = $arCalculation['tax'][$arCalculation['shipping'][0]['tax_key']]['tax_value'];
			else $arReturn['shipping']['tax_value'] = 0;
			 
			$arReturn['mwst'] = [];
			
			foreach ($arCalculation['tax'] as $tax_key => $tax) {
				
				if (wpsg_isSizedDouble($tax['sum'])) {
					 
					// Anteilig muss ich nicht anzeigen, da dieser Teil schon in den Sätzen enthalten ist
					if ($tax_key == '0') continue;
						 
					$arTaxKey = explode('_', $tax_key);
						
					$country_id = $arTaxKey[1];
					$tax_key_clear = $arTaxKey[0];
															
					$arReturn['mwst'][$tax_key] = [
						'country' => $country_id,
						'tax_key' => $tax_key_clear,
						'sum' => $tax['sum'],
						'name' => $oCalculation->getTaxLabelArray(true)[$tax_key],
						'value' => $tax['tax_value'],
						'base_value' => $tax['brutto']
					];
					
				}
				
			}
			
			/*if (wpsg_isSizedArray($arCalculation['voucher'][0])) {
				
				$arReturn['gs_value'] = abs(($this->shop->getFrontendTaxview() === WPSG_NETTO)?$arCalculation['voucher'][0]['netto']:$arCalculation['voucher'][0]['brutto']);
				$arReturn['gs']['code'] = $arCalculation['voucher'][0]['code'];
				$arReturn['gs']['gs_value'] = $arReturn['gs_value'];
								
			}*/
			
			// Alte Templates greifen auf den Shipping Array zu, deshalb dort auch korrigieren
			//$this->shop->arShipping[$arCalculation['shipping'][0]['shipping_key']]['']			
			//$this->shop->arShipping[$arCalculation['shipping'][0]['shipping_key']]['mwst_value'] = $arCalculation['tax'][$arCalculation['shipping'][0]['tax_key']]['tax_value'];
			 
			$arReturn['arCalculation'] = $arCalculation;
			
			return $arReturn;
			
		} // public function toArray()
		
		/**
		 * Verteilt den Wert $value auf die MwSt Sätze in $arBasket
		 * $value wird Netto übergeben
		 */
		public function addMwSt(&$arBasket, $value)
		{
			
			if (wpsg_tf($value) <= 0) return 0;
			
			$price_option = $this->shop->get_option('wpsg_preisangaben');
			
			// Anteilig auf die Sätze verteilen
			foreach ((array)$arBasket['mwst'] as $mw_id => $mw)
			{
				
				if ($mw['base_value'] > 0)
				{
					if (($price_option == WPSG_BRUTTO))
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_brutto'];
						
						$arBasket['mwst'][$mw_id]['base_value'] += $proz * $value;
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_BRUTTO, $mw['value']);
						
					}
					else
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_netto'];
						
						$arBasket['mwst'][$mw_id]['base_value'] -= wpsg_calculatePreis($proz * $value, WPSG_NETTO, $mw['value']);
						//$arBasket['mwst'][$mw_id]['base_value'] += $proz * $value;
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_NETTO, $mw['value']);
						
					}
					
				}
				
			}
			
			// Mehrwertsteuer Summe korrigieren
			$sum_mwst = 0;
			foreach ($arBasket['mwst'] as $mw_id => $mw)
			{
				
				$sum_mwst += $arBasket['mwst'][$mw_id]['sum'];
				
			}
			
			$sub = $arBasket['sum']['mwst'];
			
			$arBasket['sum']['mwst'] = abs($sum_mwst);
			
			return $sub - $arBasket['sum']['mwst'];
			
		} // public function addMwSt(&$arBasket, $value)
		
		/**
		 * Gibt die Anzahl zurück, die im Warenkorb zu einem ProduktKEY enthalten ist
		 */
		public function getBasketAmount($product_key)
		{
			
			$nAmount = 0;
			
			if (!wpsg_isSizedArray($_SESSION['wpsg']['basket'])) return 0;
			
			foreach ($_SESSION['wpsg']['basket'] as $product_index => $product_data)
			{
				
				if ($product_data['id'] == $product_key)
				{
					
					$nAmount += $product_data['menge'];
					
				}
				
			}
			
			return $nAmount;
			
		} // public function getBasketAmount($product_key)
		
		/**
		 * Gibt die ProduktIDs der Produkte aus dem Warenkorb zurück
		 */
		public function getProductIDs()
		{
			
			$arReturn = array();
			
			foreach ($this->arProdukte as $p)
			{
				
				$product_id = $this->shop->getProduktID($p['id']);
				if (!in_array($product_id, $arReturn)) $arReturn[] = $product_id;
				
			}
			
			return $arReturn;
			
		} // public function getProductIDs()
		
		/**
		 * Verteilt den Wert $value auf die Steuerarten in $arReturn
		 */
		public function distributeMwSt($value, &$arReturn)
		{
			
			if ($arReturn['noMwSt'] == true)
			{
				
				$arReturn['sum']['preis_brutto'] -= $value;
				$arReturn['sum']['preis_gesamt_brutto'] -= $value;
				$arReturn['sum']['preis_netto'] -= $value;
				$arReturn['sum']['preis_gesamt_netto'] -= $value;
				$arReturn['sum']['preis'] -= $value;
				$arReturn['sum']['preis_gesamt'] -= $value;
				
			}
			
			// Da ich hier nicht weiß ob der Basket fürs Frontend/Backend berechnet werden soll schaue ich hier ob der NETTO oder BRUTTO Preis im Preisarray ist
			// Die unterschiedliche Speicherung in preis is ansich quatsch ... Bei Ausgabe sollte immer Brutto / Netto entsprechend verwendet werden
			if ($arReturn['sum']['preis'] == $arReturn['sum']['preis_netto'])
			{
				$brut_nett = WPSG_NETTO;
			}
			else
			{
				$brut_nett = WPSG_BRUTTO;
			}
			
			// Gesamt Brutto. vor der Verteilung
			$netto_gesamt = $arReturn['sum']['preis_netto'];
			
			foreach ($arReturn['mwst'] as $k => $v)
			{
				
				$proz = ($v['base_value'] - $v['sum']) * 100 / $netto_gesamt; // Anteil
				$value_anteilig = $value / 100 * $proz; // Der Teil, der mit der Steuer besteuert werden soll 70% mit 19%, 30% mit 7% usw.
				
				if ($brut_nett == WPSG_BRUTTO)
				{
					
					$mwst_anteilig = wpsg_calculateSteuer($value_anteilig, WPSG_BRUTTO, $v['value']); // Der anteilige Steuerwert
					
					$arReturn['mwst'][$k]['sum'] -= $mwst_anteilig;
					$arReturn['sum']['mwst'] -= $mwst_anteilig;
					$arReturn['sum']['preis_brutto'] -= $mwst_anteilig;
					$arReturn['sum']['preis_gesamt_brutto'] -= $mwst_anteilig;
					$arReturn['sum']['preis_netto'] -= $value_anteilig;
					$arReturn['sum']['preis_gesamt_netto'] -= $value_anteilig;
					$arReturn['sum']['preis'] -= $value_anteilig;
					$arReturn['sum']['preis_gesamt'] -= $value_anteilig;
					
				}
				else
				{
					
					$mwst_anteilig = wpsg_calculateSteuer($value_anteilig, WPSG_NETTO, $v['value']); // Der anteilige Steuerwert
					
					$arReturn['mwst'][$k]['sum'] -= $mwst_anteilig;
					$arReturn['sum']['mwst'] -= $mwst_anteilig;
					$arReturn['sum']['preis_brutto'] -= $mwst_anteilig;
					$arReturn['sum']['preis_gesamt_brutto'] -= $mwst_anteilig;
					$arReturn['sum']['preis_netto'] -= $value_anteilig;
					$arReturn['sum']['preis_gesamt_netto'] -= $value_anteilig;
					$arReturn['sum']['preis'] -= $value_anteilig;
					$arReturn['sum']['preis_gesamt'] -= $value_anteilig;
					
				}
				
			}
			
		} // public function distributeMwSt($value, &$arReturn)
		
		/**
		 * Hilfsfunktion
		 * Prüft ob die MwSt mit der id $mwst_key schon im Rückgabe Array ist, wenn nicht wird sie aus der DB geladen
		 */
		public function checkMwSt($tax_key, $country, &$arReturn)
		{
			
			if ($tax_key != null && !array_key_exists($tax_key.'_'.$country->id, (array)$arReturn['mwst']))
			{
				
				if (is_object($country)) $tax_value = $country->getTax($tax_key);
				else $tax_value = 0;
				
				$name = wpsg_ff($tax_value, '%');
				if ($country->id != $this->shop->getDefaultCountry(true)) $name .= ' / '.$country->name;
				
				$arReturn['mwst'][$tax_key.'_'.$country->id] = array(
					'country' => $country->id,
					'tax_key' => $tax_key,
					'sum' => '0',
					'name' => $name,
					'value' => $tax_value
				);
				
			}
			
		} // private function checkMwSt($mwst_id, $country, &$arReturn)
		
	} // class wpsg_basket

