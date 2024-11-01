<?php

	/**
	 * Model für eine Bestellung
	 */
	class wpsg_order extends wpsg_model
	{

		/** @var bool wpsg_customer */
		var $customer = false; // Kundenobjekt
		
		/* Klassenvariablen */
		var $_innerEu = null;
		var $_shippingZoneID = null;
		var $_arOrderProducts = null;
		
		public $data = false;
		public $adress_data = false;
		public $shipping_adress_data = false;
		
		/**
		 * Lädt die Daten der Bestellung
		 */
		public function load($order_id)
		{

			parent::load($order_id);
						
			@$this->data = &$this->shop->cache->loadOrder($order_id, true);
			@$this->customer = &$this->shop->cache->loadCustomerObject($this->data['k_id']);
			$this->bShippingAdress = $this->shop->callMod('wpsg_mod_shippingadress', 'check_different_shippingadress', array($this->data['k_id'], $order_id));
				
			if (isset($this->data['id']) && ($this->data['id'] != $order_id)) throw new \wpsg\Exception(__('Die Daten eines Bestellobjekts konnten nicht geladen werden ', 'wpsg'));
				
			if (wpsg_isSizedInt($this->data['adress_id'])) $this->adress_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($this->data['adress_id'])."' ");
			if (wpsg_isSizedInt($this->data['shipping_adress_id'])) $this->shipping_adress_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($this->data['shipping_adress_id'])."' ");
	 			
			return true;
			
		} // public function load($order_id)
		
		/**
		 * Aktualisiert die Versandadresse
		 * @param $adress_data
		 */
		public function updateShippingAdress($adress_data)
		{
				
			if (!wpsg_isSizedInt($this->data['shipping_adress_id']))
			{
		
				// Es kann sein, dass zu einer Bestellung noch kein Adressdatensatz existiert, dann anlegen
				$adress_data['cdate'] = 'NOW()';
		
				$adress_id = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adress_data);
		
				$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
					'shipping_adress_id' => wpsg_q($adress_id)
				), " `id` = '".wpsg_q($this->id)."' ");
		
				$this->data['shipping_adress_id'] = $adress_id;
		
			}
			else
			{
		
				$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adress_data, " `id` = '".wpsg_q($this->data['shipping_adress_id'])."' ");
		
			}
				
			$this->shipping_adress_data = $adress_data;
				
		} // public function updateShippingAdress($adress_data)
		
		/**
		 * Aktuallisiert den Adressdatensatz
		 * @param Array $adress_data (Quoted!)
		 */
		public function updateAdress($adress_data)
		{
			
			$order_data = [];
			
			$insert = false;
				
			if (!wpsg_isSizedInt($this->data['adress_id']))
			{
		
				$insert = true;
		
			}
			else
			{
					
				$adress_db_exists = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($this->data['adress_id'])."' ");
		
				if ($adress_db_exists != $this->data['adress_id']) $insert = true;
		
			}
				
			if ($insert === true)
			{
		
				// Es kann sein, dass zu einer Bestellung noch kein Adressdatensatz existiert, dann anlegen
				$adress_data['cdate'] = 'NOW()';
		
				$adress_id = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adress_data);
				
				$order_data['adress_id'] = wpsg_q($adress_id);
		
			}
			else
			{
					
				$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adress_data, " `id` = '".wpsg_q($this->data['adress_id'])."' ");
		
			}
				
			if (isset($adress_data['land'])) {
				
				$oCountry = wpsg_country::getInstance($adress_data['land']);
				
				
				
				$order_data['target_country_id'] = wpsg_q($adress_data['land']);
				$order_data['target_country_tax'] = wpsg_q($oCountry->mwst);
				$order_data['target_country_tax_a'] = wpsg_q($oCountry->mwst_a);
				$order_data['target_country_tax_b'] = wpsg_q($oCountry->mwst_b);
				$order_data['target_country_tax_c'] = wpsg_q($oCountry->mwst_c);
				$order_data['target_country_tax_d'] = wpsg_q($oCountry->mwst_d);
			
			}
			
			if (wpsg_isSizedArray($order_data)) {
			
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $order_data, " `id` = '".wpsg_q($this->id)."' ");
				
				foreach ($order_data as $k => $v) {
					
					$this->data[$k] = $v;
					
				}
				
			}
			
			$this->adress_data = $adress_data;
				
		} // public function updateAdress($adress_data)
		
		/**
		 * Erstellt einen Eintrag im Bestellprotokoll
		 * @param string $subject Text des Betreffs
		 * @param string $text Text der protokolliert werden soll
		 * @throws \wpsg\Exception
		 */
		public function log($subject, $text)
		{
			
			$this->db->ImportQuery(WPSG_TBL_OL, array(
				'o_id' => wpsg_q($this->id),
				'cdate' => 'NOW()',
				'title' => wpsg_q($subject),
				'mailtext' => wpsg_q(htmlentities($text, ENT_IGNORE)) 					
			));
			
		} // public function log($subject, $text)

		public function getLog()
		{

			return $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_OL."` WHERE `o_id` = '".wpsg_q($this->id)."' ORDER BY `cdate` DESC, `id` DESC ");

		}

		/**
		 * Gibt den letzten Log Eintrag zurück 
		 */
		public function getLastLogEntry()
		{
			
			return $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_OL."` WHERE `o_id` = '".wpsg_q($this->id)."' ORDER BY `cdate` DESC, `id` DESC LIMIT 1 ");
			
		} // public function getLastLogEntry()
		
		/**
		 * Setzt einen Wert in der META Tabelle 
		 */
		public function setMeta($meta_key, $meta_value) {
			
			$target_id = $this->id;
			
			if (!wpsg_isSizedInt($target_id)) return;
			
			if (is_null($meta_value)) {
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_META."` WHERE `meta_key` = '".wpsg_q($meta_key)."' AND `target_id` = '".wpsg_q($this->id)."' AND `meta_table` = 'WPSG_TBL_ORDER' ");
				
			} else {
			
				$nExistsID = $this->db->fetchOne("SELECT M.`id` FROM `".WPSG_TBL_META."` AS M WHERE M.`meta_table` = 'WPSG_TBL_ORDER' AND M.`meta_key` = '".wpsg_q($meta_key)."' AND `target_id` = '".wpsg_q($this->id)."' ");
				
				if (wpsg_isSizedInt($nExistsID))
				{
					
					$this->db->UpdateQuery(WPSG_TBL_META, array(					
						'meta_value' => wpsg_q($meta_value)
					), " `id` = '".wpsg_q($nExistsID)."' ");
					
				}
				else
				{
					
					$this->db->ImportQuery(WPSG_TBL_META, array(
						'target_id' => wpsg_q($this->id),
						'meta_table' => "WPSG_TBL_ORDER",
						'meta_key' => wpsg_q($meta_key),
						'meta_value' => wpsg_q($meta_value)
					));
					
				}
				
			}
			
		} // public function setMeta($meta_key, $meta_value)
		
		/**
		 * Gibt einen META Value zurück
		 */
		public function getMeta($meta_key)
		{
			
			$meta_value = $this->db->fetchOne("SELECT M.`meta_value` FROM `".WPSG_TBL_META."` AS M WHERE M.`meta_table` = 'WPSG_TBL_ORDER' AND M.`meta_key` = '".wpsg_q($meta_key)."' AND `target_id` = '".wpsg_q($this->id)."' ");
			
			return $meta_value;
			
		} // public function getMeta($meta_key)

		public function getPaymentTaxAmount()
		{
			
			return wpsg_tf($this->mwst_payment);
			
		}
		
		public function getShippingTaxAmount()
		{
			
			return wpsg_tf($this->mwst_shipping);
			
		} // public function getShippingTaxAmount()
		
		
		public function getToPay($brutto_netto = WPSG_BRUTTO) {
						
			if ($brutto_netto == WPSG_BRUTTO) {
				
				return wpsg_tf($this->data['topay_brutto']);
				
			} else {
				
				return wpsg_tf($this->data['topay_netto']);
				
			}
			
		}
		
		/**
		 * Gibt den Wert der Bestellung zurück
		 * Dies ist der Wert, der über die Zahlungsanbieter abgerechnet wird
		 */
		public function getAmount($brutto_netto = WPSG_BRUTTO)
		{
						
			//wpsg_debug($this->data['price_gesamt_netto'].":".$this->data['price_gesamt_brutto']);
			
			if ($brutto_netto == WPSG_BRUTTO)
			{
				
				return wpsg_tf($this->data['price_gesamt_brutto']);
				
			}
			else
			{
			
				return wpsg_tf($this->data['price_gesamt_netto']);
				
			}
			
		} // public function getAmount()
				
		/**
		 * Summe aller MwSt. Beträge der Bestellung
		 */
		public function getTaxAmount()
		{
				
			$tax_return = 0;
			
			foreach ($this->getOrderProducts() as $oOrderProduct) 
			{
				
				$tax_return += $oOrderProduct->getTaxAmount();
				
			}
			
			$tax_return += wpsg_tf($this->data['mwst_shipping']);
			$tax_return += wpsg_tf($this->data['mwst_payment']);
			
			return wpsg_tf($tax_return);
				
		} // public function getTaxAmount()
		 		
		public function getProductAmount($brutto_netto = WPSG_BRUTTO)
		{
				
			//wpsg_debug($this->getAmount($brutto_netto));
			//wpsg_debug($this->getShippingAmount($brutto_netto));
			//wpsg_debug($this->getPaymentAmount($brutto_netto));
			
			return $this->getAmount($brutto_netto) - $this->getShippingAmount($brutto_netto) - $this->getPaymentAmount($brutto_netto); 
				
		} // public function getProductAmount($brutto_netto = WPSG_BRUTTO)
		
		/**
		 * Gibt das Kundenobjekt zurück
		 * @return wpsg_customer Kundenobjekt
		 */
		public function getCustomer()
		{
			
			return $this->customer;
			
		} // public function getCustomer()
		 	
		/**
		 * Gibt die Anzahl zurück, wie oft das Produkt in der Bestellung vorkommt
		 */
		public function getProductCount($product_id)
		{
			
			return $this->db->fetchOne("
				SELECT
					SUM(`menge`)
				FROM
					`".WPSG_TBL_ORDERPRODUCT."`
				WHERE
					`p_id` = '".wpsg_q($product_id)."' AND
					`o_id` = '".wpsg_q($this->id)."'
			");
			
		} // public function getProductCount($product_id)
		
		/**
		 * Gibt einen Array mit allen bestellten Produkten zurück
         * @return wpsg_order_product[]
		 */
		public function getOrderProducts()
		{
			
			if (!is_null($this->_arOrderProducts)) return $this->_arOrderProducts;
			else
			{
			
				$arOpIds = $this->db->fetchAssocField("SELECT OP.`id` FROM `".WPSG_TBL_ORDERPRODUCT."` AS OP WHERE OP.`o_id` = '".wpsg_q($this->id)."' ORDER BY `id` ASC ");
				
				$arReturn = array();
				$number = 1;
				
				foreach ($arOpIds as $order_product_id)
				{
					
					$oOrderProduct = new wpsg_order_product();
					$oOrderProduct->load($order_product_id);
					$oOrderProduct->number = $number;
					
					$arReturn[] = $oOrderProduct;
					
					$number ++;
					
				}
				
				$this->_arOrderProducts = $arReturn;
				
				return $arReturn;
				
			}	// public function getOrderProducts()
			
		} // public function getOrderProducts()
		
		/**
		 * Gibt die Kosten für die Versandart zurück in Euro
		 * Derzeit ist es so, dass 
		 */
		public function getShippingAmount($brutto_netto = WPSG_BRUTTO)
		{			
			
			if ($brutto_netto === WPSG_NETTO)
			{
				
				return wpsg_tf($this->price_shipping_netto);
				
			}
			else
			{
			
				return wpsg_tf($this->price_shipping_brutto);
				
			}
			
		} // public function getShippingAmount()
		
		/**
		 * Gibt die Kosten für die Zahlungsart zurück in Euro 
		 */
		public function getPaymentAmount($brutto_netto = WPSG_BRUTTO)
		{
			
			if ($brutto_netto === WPSG_NETTO)
			{
			
				return wpsg_tf($this->price_payment_netto);
			
			}
			else
			{
					
				return wpsg_tf($this->price_payment_brutto);
			
			} 
			
		} // public function getPaymentAmount()
		
		/**
		 * Gibt die verwendete Zahlungsart der Bestellung zurück
		 */
		public function getPaymentID()
		{
			
			return $this->data['type_payment'];
			
		} // public function getPaymentID()

		/**
		 * @return String Name der verwendeten Zahlungsart
		 */
		public function getPaymentLabel()
		{

			if (!array_key_exists($this->getPaymentID(), $this->shop->arPayment)) return wpsg_translate(__('Deaktivierte Zahlungsart (#1#)', 'wpsg'), $this->getPaymentID());
			else return $this->shop->arPayment[$this->getPaymentID()]['name'];

 		} // public function getPaymentLabel()

		/**
		 * @return String Name der verwendeten Versandart
		 */
		public function getShippingLabel()
		{

			if (preg_match('/(.*)-(.*)/', $this->type_shipping))
			{

				// Versandart ist zusammengesetzt
				$arShippingKey = explode('-', $this->type_shipping);
				$arShippingNames = array();

				foreach ($arShippingKey as $shipping_key)
				{

					$arShippingNames[] = $this->shop->getShippingName($shipping_key);

				}

				return implode(' + ', $arShippingNames);

			}
			else
			{

				return $this->shop->getShippingName($this->type_shipping);

			}

		} // public function getShippingLabel()

		/**
		 * @return String Key der verwendeten Versandart
		 */
		public function getShippingID()
		{

			return $this->data['type_shipping'];

		} // public function getShippingID()

		/**
		 * Gibt einen Array mit ProduktKeys zurück, die in dieser Bestellung bestellt wurden
		 */
		public function getProductKeys()
		{
		
			$order_product_keys = $this->db->fetchAssocField("
				SELECT
					`productkey`
				FROM
					`".WPSG_TBL_ORDERPRODUCT."` 
				WHERE
					`o_id` = '".wpsg_q($this->id)."'	
			");
			
			return $order_product_keys;

		} // public function getProductKeys()
 
		/**
		 * Gibt die Bezeichnung des Status der Bestellung zurück
		 * @return String
		 */
		public function getStateLabel()
		{

			if (!array_key_exists($this->status, $this->shop->arStatus))
			return wpsg_translate(__('Unbekannter Statuscode (#1#)', 'wpsg'), $this->status);
			else return $this->shop->arStatus[$this->status];

		} // public function getStateLabel()

		/* Rechnungsadresse */
		
		/**
		 * Gibt das Geschlecht des Rechnungsempfängers zurück
		 * @return String Geschlecht des Rechnungsempfängers
		 */
		public function getInvoiceGender()
		{
			
			if ($this->adress_data === false) return $this->customer->getGender();
			else 
			{
				
				if (preg_match('/Frau/i', $this->getInvoiceTitle()))
				{
					
					return 'f';
					
				}
				else
				{
					
					return 'm';
					
				}
				
			}
			
		} // public function getInvoiceGender()

		/**
		 * Gibt die Firma des Rechnungsempfängers zurück
		 */
		public function getInvoiceCompany()
		{
			
			if ($this->adress_data === false) return $this->customer->getCompany();
			else return $this->adress_data['firma'];
			
		} // public function getInvoiceCompany()
		
		/**
		 * Gibt die Anrede zurück
		 */
		public function getInvoiceTitle()
		{
			 
			if ($this->adress_data === false) $title = wpsg_getStr($this->data['title']);
			else $title = wpsg_getStr($this->adress_data['title']);
			
			$arAnrede = explode('|', $this->shop->get_option('wpsg_admin_pflicht')['anrede_auswahl']);
			if (($title < 0) || ($title == '')) return '';
			else return $arAnrede[$title];
			
		} // public function getTitle()
		
		/**
		 * Gibt den Vornamen des Rechnungsempfängers zurück
		 * @return String Vorname des Rechnungsempfängers
		 */
		public function getInvoiceFirstName()
		{
			 
			if ($this->adress_data === false) return $this->customer->getFirstName();
			else return wpsg_getStr($this->adress_data['vname']); 
			
		} // public function getInvoiceFirstName()
		
		/**
		 * Gibt den Namen des Rechnungsempfängers zurück
		 * @return String Name des Rechnungsempfängers
		 */
		public function getInvoiceName()
		{
			
			if ($this->adress_data === false) return $this->customer->getName();
			else return wpsg_getStr($this->adress_data['name']);
			
		} // public function getInvoiceName()

		/**
		 * Gibt die Fax Nummer des Rechnungsempfängers zurück
		 */
		public function getInvoiceFax()
		{
			
			if ($this->adress_data === false) return $this->customer->getFax();
			else return wpsg_getStr($this->adress_data['fax']);
			
		} // public function getInvoiceFax()
		
		/**
		 * Gibt die Fax Nummer des Rechnungsempfängers zurück
		 */
		public function getInvoicePhone()
		{
			
			if ($this->adress_data === false) return $this->customer->getPhone();
			else return wpsg_getStr($this->adress_data['tel']);
			
		} // public function getInvoicePhone()
		
		/**
		 * Gibt die Straße der Rechnungsadresse zurück (Mit Hausnummer)
		 * @return String Straße der Rechnungsanschrift
		 */
		public function getInvoiceStreet()
		{
			
			if ($this->adress_data === false) return $this->customer->getStreet();
			else return trim(wpsg_getStr($this->adress_data['strasse']).' '.wpsg_getStr($this->adress_data['nr']));
			
		} // public function getInvoiceStreet()
		
		/**
		 * @param $trimHnr Wenn true, dann wird versucht die Hausnummer abzutrennen (Wenn Hnr nicht separat erfasst)
		 * @return String der Reine Straßenname der Rechnungsadresse
		 */
		public function getInvoiceStreetClear($trimHnr = false)
		{
		
			if ($this->adress_data === false)
			{
				
				return $this->customer->getStreetClear($trimHnr);
				
			}
			else 
			{
				
				if (wpsg_isSizedInt($this->shop->get_option('wpsg_showNr')) && $trimHnr === true)
				{
					 
					return preg_replace('/\040+\d+$/', '', $this->adress_data['strasse']);
					
				}
				else
				{
					 				
					return $this->adress_data['strasse'];
					
				}
				
			} 
			
		} // public function getInvoiceStreetClear()
		
		/**
		 * Gibt die Straßennummer der Rechnungsadresse zurück
		 * @return String Hausnummer der Rechnungsanschrift
		 */
		public function getInvoiceStreetNr()
		{
			
			if ($this->adress_data === false) return $this->customer->getStreetNr();
			else return $this->adress_data['nr'];			
			 			
		} // public function getInvoiceStreetNr()
		
		/**
		 * Gibt die Postleitzahl der Rechnungsadresse zurück
		 * @return String PLZ der Rechnungsadresse
		 */
		public function getInvoiceZip()
		{
			
			if ($this->adress_data === false) return $this->customer->getZip();
			else return wpsg_getStr($this->adress_data['plz']);
			
		} // public function getInvoiceZip()
		
		/**
		 * Gibt den Ort der Rechnungsadresse zurück
		 * @return String Ort der Rechnungsadresse
		 */
		public function getInvoiceCity()
		{
			
			if ($this->adress_data === false) return $this->customer->getCity();
			else return wpsg_getStr($this->adress_data['ort']);
						 
		} // public function getInvoiceCity()
		
		/**
		 * Gibt die ID des Landes zurück, an die die Rechnung ging
		 * @return \Integer ID des Landes für die Rechnung
		 */
		public function getInvoiceCountryID()
		{
				
			if ($this->adress_data === false) return $this->customer->getCountryID();
			else return wpsg_getStr($this->adress_data['land']);
						
		} // public function getInvoiceCountry()
		
		/**
		 * Gibt das Länderobjekt für die Rechnung zurück
		 */
		public function getInvoiceCountry()
		{
						
			return wpsg_country::getInstance($this->getInvoiceCountryID());
						
		} // public function getInvoiceCountry()
		
		/**
		 * Gibt das Kürzel des Rechnungslandes zurück
		 */
		public function getInvoiceCountryKuerzel()
		{
				
			$invoiceCountry = $this->getInvoiceCountry();
			
			if (!is_object($invoiceCountry)) return __('Nicht definiert.', 'wpsg');
			else return $this->getInvoiceCountry()->getShorttext();
				
		} // public function getInvoiceCountryKuerzel()
		
		/**
		 * Gibt den Namen des Landes der Rechnungsadresse zurück
		 */
		public function getInvoiceCountryName()
		{
			
			$invoiceCountry = $this->getInvoiceCountry();
			
			if (!is_object($invoiceCountry)) return __('Nicht definiert.', 'wpsg');
			else return $this->getInvoiceCountry()->getName(); 
			
		} // public function getInvoiceCountryName()
		
		/* Lieferadresse */
		
		/**
		 * Gibt das Geschlecht des Empfängers der Bestellung zurück
		 * @return Geschlecht des Empfängers
		 */
		public function getShippingGender()
		{
			
			if ($this->hasShippingAdress()) 
			{
				
				if (preg_match('/Frau/i', $this->getShippingTitle()))
				{
					
					return 'f';
					
				}
				else
				{
					
					return 'm';
					
				}
				
			}
			else
			{
				
				return $this->getInvoiceGender();
				
			}
			
		} // public function getShippingGender()
		
		/** 
		 * Gibt die Firma der Lieferadresse zurück 
		 */
		public function getShippingCompany()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['firma'];
			else return $this->getInvoiceCompany(); 
			
		}	// public function getShippingCompany()
		
		/** 
		 * Anrede der Lieferadresse
		 */
		public function getShippingTitle()
		{
			
			//if ($this->hasShippingAdress()) return $this->shipping_adress_data['title'];
			//else return $this->getInvoiceTitle();
			if ($this->bShippingAdress === true)
			{
			
				if (wpsg_isSizedString($this->shipping_adress_data['title'])) $title = $this->shipping_adress_data['title'];
				else $title = $this->data['shipping_title'];
			
			}
				else $title = $this->getInvoiceTitle();
			
			$arAnrede = explode('|', $this->shop->get_option('wpsg_admin_pflicht')['anrede_auswahl']);
			if ($title < 0) return '';
			else return $arAnrede[$title];
			
		}	// public function getShippingTitle()
		
		/**
		 * Gibt den Vornamen des Empfängers der Bestellung zurück
		 * @return string Vorname des Empfängers
		 */
		public function getShippingFirstName()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['vname'];
			else return $this->getInvoiceFirstName(); 
			
		} // public function getShippingFirstName()
		
		/**
		 * Gibt den Namen des Empfängers der Bestellung zurück
		 * @return string Name des Empfängers
		 */
		public function getShippingName()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['name'];
			else return $this->getInvoiceName(); 
			 						
		} // public function getShippingName()
		
		/**
		 * Gibt die komplette Straße des Empfängers der Bestellung zurück (Inklusive Hausnummer)
		 * @return string komplette Straße des Empfängers
		 */
		public function getShippingStreet()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['strasse'].' '.$this->shipping_adress_data['nr'];
			else return $this->getInvoiceStreet(); 
			 			
		} // public function getShippingStreet()
		
		/**
		 * Gibt den reinen Straßennamen des Empfängers zurück
		 * @return string reiner Straßenname des Empfängers
		 */
		public function getShippingStreetClear($trimHnr = false)
		{
			
			if ($this->hasShippingAdress())
			{
				
				if (wpsg_isSizedInt($this->shop->get_option('wpsg_showNr')) && $trimHnr === true)
				{
					 
					return preg_replace('/\040+\d+$/', '', wpsg_getStr($this->shipping_adress_data['strasse']));
					
				}
				else
				{
				
					return $this->shipping_adress_data['strasse']; 
					
				}
				
			}
			else return $this->getInvoiceStreetClear($trimHnr); 
			 			
		} // public function getShippingStreetClear()
		
		/**
		 * Gibt die Hausnummer des Empfängers der Bestellung zurück
		 * @return string Hausnummer des Empfängers
		 */
		public function getShippingStreetNr()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['nr'];
			else return $this->getInvoiceStreetNr(); 
			 
		} // public function getShippingStreetNr()
		
		/**
		 * Gibt die Postleitzahl des Empfängers zurück
		 * @return string Postleitzahl des Empfängers
		 */
		public function getShippingZip()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['plz'];
			else return $this->getInvoiceZip(); 
			 
		} // public function getShippingZip()
		
		/**
		 * Gibt den Wohnort des Empfängers zurück
		 * @return string Ort des Empfängers
		 */
		public function getShippingCity()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['ort'];
			else return $this->getInvoiceCity(); 
			 
		} // public function getShippingCity()
						
		/**
		 * Gibt das Lieferland der Bestellung zurück
		 * @return \Integer ID des Landes, in das die Bestellung geliefert wird
		 */
		public function getShippingCountryID()
		{
			
			if ($this->hasShippingAdress()) return $this->shipping_adress_data['land'];
			else return $this->getInvoiceCountryID(); 
			 
		} // public function getShippingCountryID()
		
		/**
		 * Gibt das Lieferland als Objekt zurück
		 * @return wpsg_country
		 */
		public function getShippingCountry()
		{
			
			return wpsg_country::getInstance($this->getShippingCountryID());
			
		} // public function getShippingCountry()
		
		/**
		 * Gibt das Kuerzel des Lieferlandes zurück
		 */
		public function getShippingCountryKuerzel()
		{
			
			$shippingCountry = $this->getShippingCountry();
			
			if (!is_object($shippingCountry)) return __('Nicht definiert.', 'wpsg');
			else return $this->getShippingCountry()->getShorttext();  
			
			
		} // public function getShippingCountryKuerzel()
		
		/** 
		 * Gibt den Namen des Lieferlandes zurück
		 */
		public function getShippingCountryName()
		{
			
			$shippingCountry = $this->getShippingCountry();
			 
			if (!is_object($shippingCountry)) return __('Nicht definiert.', 'wpsg');
			else return $this->getShippingCountry()->getName(); 
			
		}	// public function getShippingCountryName()
		
		/**
		 * Gibt die Bestellnummer zurück
		 * @return string|null
		 */
		public function getNr() { return $this->onr; }
  
		public function addLogEntry($title, $text) {
			
			$this->db->ImportQuery(WPSG_TBL_OL, [
				"title" => $title,
				"cdate" => "NOW()",
				"o_id" => wpsg_q($this->id),
				"mailtext" => wpsg_q($text)
			]);
			
		}
		
		/**
		 * Gibt die ID der Versandzone zurück, in die geliefert wird
		 * @return \Integer ID der Versandzone, in die geliefert wird
		 */
		public function getShippingZoneID()
		{
			
			if (is_null($this->_shippingZoneID))
			{
			
				$this->_shippingZoneID = $this->db->fetchOne("
					SELECT 
						L.`vz` 
					FROM
						`".WPSG_TBL_LAND."` AS L							
					WHERE
						L.`id` = '".wpsg_q($this->getShippingCountryID())."'
				");
				
			}
			
			return $this->_shippingZoneID;
			
		} // public function getShippingZoneID()
		
		/**
		 * Gibt true zurück, wenn die Bestellung in ein Land gesendet wird, dessen Versandzone als "Innergemeinschaftliche Lieferung" markiert ist
		 * @return \Boolean
		 */
		public function isInnerEu()
		{
			
			if (is_null($this->_innerEu))
			{
				
				$innerEu = $this->db->fetchOne("SELECT VZ.`innereu` FROM `".WPSG_TBL_VZ."` AS VZ WHERE VZ.`id` = '".wpsg_q($this->getShippingZoneID())."' ");
				
				if ($innerEu == "1")
				{
					
					$this->_innerEu = true;
					
				}
				else
				{
					
					$this->_innerEu = false;
					
				}
				
			}
			
			return $this->_innerEu;
			
		} // public function isInnerEu()

		/**
		 * return Boolean true, wenn eine separate Versandadresse verwendet wurde
		 */
		public function hasShippingAdress()
		{

			return wpsg_isSizedInt($this->data['shipping_adress_id']);

		} // public function hasShippingAdress()

		/**
		 * Speichert die Bestelldaten in die Datenbank
		 */
		public function save()
		{
			
			$this->db->UpdateQuery(WPSG_TBL_ORDER, $this->data, " `id` = '".wpsg_q($this->id)."' ");
			
		} // public function save()

		/**
		 * Löscht eine Bestellung
		 */
		public function delete()
		{
			
			// Module aufrufen, damit sie die Abhängigkeiten löschen können
			$this->shop->callMods('delOrder', array(&$this->id));
			 			
			// Bestelldaten aus der Bestelltabelle löschen
			$this->db->Query("DELETE FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($this->id)."' ");
				
			// Bestellte Produkte
			$this->db->Query("DELETE FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($this->id)."' ");
		
			// Log Einträge
			$this->db->Query("DELETE FROM `".WPSG_TBL_OL."` WHERE `o_id` = '".wpsg_q($this->id)."' ");
				
			// Meta Daten
			$this->db->Query("DELETE FROM `".WPSG_TBL_META."` WHERE `meta_table` = 'WPSG_TBL_ORDER' AND `target_id` = '".wpsg_q($this->id)."' ");
				
		} // public function delete()

		/* Statische Funktionen */

		/**
		 * Zählt die Bestellungen anhand des Filters
		 */
		public static function count($arFilter)
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					(
						SELECT
						  	DISTINCT O.`id`
						FROM
							`".WPSG_TBL_ORDER."` AS O
							".$strQueryJOIN."
						WHERE
							1
							".$strQueryWHERE." 
						HAVING
							1
							".$strQueryHAVING."											
					) AS innerSelect
			";

			return $GLOBALS['wpsg_db']->fetchOne($strQuery);

		} // public static function count($arFilter)

		/**
		 * Gibt einen Array von Bestellungen zurück, die auf den übergebenen Filter passen
		 * @param array $arFilter
		 */
		public static function find($arFilter = array(), $load = true)
		{
			
			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strLimit = "";

			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);

			$strQuery = "
				SELECT
					O.`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_ORDER."` AS O
					".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				GROUP BY 
					O.`id`
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."
				".$strLimit."
			";
 			
			$arOrderID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery, "id", "id");

			if ($load !== true) return $arOrderID;

			$arReturn = array();
			
			foreach ($arOrderID as $order_id)
			{

				$oOrder = $GLOBALS['wpsg_sc']->cache->loadOrderObject($order_id);
				$arReturn[] = $oOrder;
				
			}
			
			return $arReturn;
			
		} // public static function find($arFilter = array(), $load = true)

		public static function getQueryParts($arFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryWHERE = "";
			$strQueryJOIN = "";
			$strQueryHAVING = "";
			$strQueryORDER = "";

			$bJoinProducts = false;
			$bJoinOrderProducts = false;			
			$bJoinCustomer = false;
			$bJoinInvoice = false;
			$bJoinOrderAdress = false;

			if (wpsg_isSizedInt($arFilter['k_id'])) $strQueryWHERE .= " AND O.`k_id` = '".wpsg_q($arFilter['k_id'])."' ";
			if (wpsg_isSizedInt($arFilter['cdate_from'])) $strQueryWHERE .= " AND O.`cdate` > '".wpsg_date('Y-m-d', $arFilter['cdate_from'])."' ";
			if (wpsg_isSizedInt($arFilter['product_id'])) { $strQueryWHERE .= " AND OP.`p_id` = '".wpsg_q($arFilter['product_id'])."' "; $bJoinOrderProducts = true; }
			if (wpsg_isSizedString($arFilter['cdate_y']) && $arFilter['cdate_y'] != '-1') { $strQueryWHERE .= " AND YEAR(O.`cdate`) = '".wpsg_q($arFilter['cdate_y'])."' "; }
			if (wpsg_isSizedString($arFilter['cdate_m']) && $arFilter['cdate_m'] != '-1') { $strQueryWHERE .= " AND MONTH(O.`cdate`) = '".wpsg_q(ltrim($arFilter['cdate_m'], '0'))."' "; }
			if (wpsg_isSizedString($arFilter['invoicedate_y']) && $arFilter['invoicedate_y'] != '-1') { $bJoinInvoice = true; $strQueryWHERE .= " AND YEAR(I.`datum`) = '".wpsg_q($arFilter['invoicedate_y'])."' "; }
			if (wpsg_isSizedString($arFilter['invoicedate_m']) && $arFilter['invoicedate_m'] != '-1') { $bJoinInvoice = true; $strQueryWHERE .= " AND MONTH(I.`datum`) = '".wpsg_q(ltrim($arFilter['invoicedate_m'], '0'))."' "; }
			if (wpsg_isSizedInt($arFilter['productgroup_id'])) {
				
				$bJoinProducts = true;
				$strQueryWHERE .= " AND P.`pgruppe` = '".wpsg_q($arFilter['productgroup_id'])."' ";
				
			}
			if (wpsg_isSizedString($arFilter['s']))
			{

				$strQueryWHERE .= "
					AND
					(
						OA.`vname` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						OA.`name` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						C.`email` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						OA.`firma` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						C.`ustidnr` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						O.`onr` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						O.`id` = '".wpsg_q($arFilter['s'])."' 
					)
				";

				$bJoinOrderAdress = true;
				$bJoinCustomer = true;

			}

			if (wpsg_isSizedInt($arFilter['status']) || $arFilter['status'] == '0')
			{

				$strQueryWHERE .= " AND O.`status` = '".wpsg_q($arFilter['status'])."' ";

			}
			else if (wpsg_isSizedArray($arFilter['status']))
			{

				$strQueryWHERE .= " AND O.`status` IN (".wpsg_q(implode(',', $arFilter['status'])).") ";

			}

			if (wpsg_isSizedInt($arFilter['NOTstatus']))
			{

				$strQueryWHERE .= " AND O.`status` != '".wpsg_q($arFilter['NOTstatus'])."' ";

			}
			else if (wpsg_isSizedArray($arFilter['NOTstatus']))
			{

				$strQueryWHERE .= " AND O.`status` NOT IN (".wpsg_q(implode(',', $arFilter['NOTstatus'])).") ";

			}

			// Sortierung
			if (wpsg_isSizedString($arFilter['order'], 'cdate')) { $strQueryORDER = " O.`cdate` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'nr')) { $strQueryORDER = " O.`onr` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'customer')) { $strQueryORDER = " CONCAT(OA.`name`, ' ', OA.`vname`) "; $bJoinCustomer = true; $bJoinOrderAdress = true; }
			else if (wpsg_isSizedString($arFilter['order'], 'payment')) {

				$strQuerySELECT .= " , (SELECT CASE `type_payment` ";

				foreach ($GLOBALS['wpsg_sc']->arPayment as $payment_key => $payment_info)
				{

					$strQuerySELECT .= " WHEN '".wpsg_q($payment_key)."' THEN '".wpsg_q($payment_info['name'])."' ";

				}

				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Deaktivierte Zahlungsart (', 'wpsg'))."', O.`type_payment`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `paymentlabel` ";

				$strQueryORDER = " CONCAT(`paymentlabel`, ' - ', `price_payment`) ";

			}
			else if (wpsg_isSizedString($arFilter['order'], 'shipping')) {

				$strQuerySELECT .= " , (SELECT CASE `type_shipping` ";

				foreach ($GLOBALS['wpsg_sc']->arShipping as $shipping_key => $shipping_info)
				{

					$strQuerySELECT .= " WHEN '".wpsg_q($shipping_key)."' THEN '".wpsg_q($shipping_info['name'])."' ";

				}

				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Deaktivierte Versandart (', 'wpsg'))."', O.`type_shipping`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `shippinglabel` ";

				$strQueryORDER = " CONCAT(`shippinglabel`, ' - ', `price_shipping`) ";

			}
			else if (wpsg_isSizedString($arFilter['order'], 'products'))
			{
				
				$bJoinOrderProducts = true;
				$strQuerySELECT .= ", COUNT(OP.`id`) AS `count_products` ";
				$strQueryORDER = " `count_products` ";

			}
			else if (wpsg_isSizedString($arFilter['order'], 'amount')) $strQueryORDER .= " `price_gesamt_brutto` ";
			else if (wpsg_isSizedString($arFilter['order'], 'state'))
			{

				$strQuerySELECT .= " , (SELECT CASE `status` ";

				foreach ($GLOBALS['wpsg_sc']->arStatus as $state_key => $state_label)
				{

					$strQuerySELECT .= " WHEN '".wpsg_q($state_key)."' THEN '".wpsg_q($state_label)."' ";

				}

				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Unbekannter Statuscode (', 'wpsg'))."', O.`status`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `statelabel` ";
				$strQueryORDER = " `statelabel` ";

			}
			else $strQueryORDER = " O.`id` ";

			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";

			if ($bJoinProducts === true) $bJoinOrderProducts = true;
			
			// Optionale Joins
			if ($bJoinOrderProducts === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ORDERPRODUCT."` AS OP ON (O.`id` = OP.`o_id`) ";
			if ($bJoinProducts === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (OP.`p_id` = P.`id`) ";
			if ($bJoinCustomer === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_KU."` AS C ON (C.`id` = O.`k_id`) ";
			if ($bJoinInvoice === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_RECHNUNGEN."` AS I ON (I.`o_id` =  O.`id` AND I.`storno` = '0000-00-00 00:00:00' AND I.`rnr` != '') ";
			if ($bJoinOrderAdress === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ADRESS."` AS OA ON (OA.`id` = O.`adress_id`) ";
			
			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);

		}	// public static function getQueryParts($arFilter = array())

		/**
		 * Zählt die Abo-Bestellungen anhand des Filters
		 */
		public static function countAbo($arFilter)
		{
			
			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryPartsAbo($arFilter);
			
			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					(
						SELECT
						  	DISTINCT O.`id`
						FROM
							`".WPSG_TBL_ORDER."` AS O
							".$strQueryJOIN."
						WHERE
							1
							".$strQueryWHERE."
						HAVING
							1
							".$strQueryHAVING."
					) AS innerSelect
			";
			
			return $GLOBALS['wpsg_db']->fetchOne($strQuery);
			
		} // public static function countAbo($arFilter)
		
		/**
		 * Gibt einen Array von Abo-Bestellungen zurück, die auf den übergebenen Filter passen
		 * @param array $arFilter
		 */
		public static function findAbo($arFilter = array(), $load = true)
		{
			
			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryPartsAbo($arFilter);
			
			$strLimit = "";
			
			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);
			
			$strQuery = "
				SELECT
					O.`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_ORDER."` AS O
					".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				GROUP BY
					O.`id`
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."
				".$strLimit."
			";
			
			$arOrderID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery, "id", "id");
			
			if ($load !== true) return $arOrderID;
			
			$arReturn = array();
			
			foreach ($arOrderID as $order_id)
			{
				
				$oOrder = $GLOBALS['wpsg_sc']->cache->loadOrderObject($order_id);
				$arReturn[] = $oOrder;
				
			}
			
			return $arReturn;
			
		} // public static function findAbo($arFilter = array(), $load = true)
		
		public static function getQueryPartsAbo($arFilter = array())
		{
			
			$strQuerySELECT = "";
			$strQueryWHERE = "";
			$strQueryJOIN = "";
			$strQueryHAVING = "";
			$strQueryORDER = "";
			
			$bJoinOrderProducts = true;
			$bJoinCustomer = false;
			$bJoinInvoice = false;
			$bJoinAbo = false;
			$bJoinOrderAdress = false;
			
			if (wpsg_isSizedInt($arFilter['k_id'])) $strQueryWHERE .= " AND O.`k_id` = '".wpsg_q($arFilter['k_id'])."' ";
			if (wpsg_isSizedInt($arFilter['cdate_from'])) $strQueryWHERE .= " AND O.`cdate` > '".wpsg_date('Y-m-d', $arFilter['cdate_from'])."' ";
			if (wpsg_isSizedInt($arFilter['product_id'])) { $strQueryWHERE .= " AND OP.`p_id` = '".wpsg_q($arFilter['product_id'])."' "; $bJoinOrderProducts = true; }
			if (wpsg_isSizedString($arFilter['cdate_y']) && $arFilter['cdate_y'] != '-1') { $strQueryWHERE .= " AND YEAR(O.`cdate`) = '".wpsg_q($arFilter['cdate_y'])."' "; }
			if (wpsg_isSizedString($arFilter['cdate_m']) && $arFilter['cdate_m'] != '-1') { $strQueryWHERE .= " AND MONTH(O.`cdate`) = '".wpsg_q(ltrim($arFilter['cdate_m'], '0'))."' "; }
			if (wpsg_isSizedString($arFilter['enddate_y']) && $arFilter['enddate_y'] != '-1') { $bJoinAbo= true; $strQueryWHERE .= " AND YEAR(A.`expiration`) = '".wpsg_q($arFilter['enddate_y'])."' "; }
			if (wpsg_isSizedString($arFilter['enddate_m']) && $arFilter['enddate_m'] != '-1') { $bJoinAbo= true; $strQueryWHERE .= " AND MONTH(A.`expiration`) = '".wpsg_q(ltrim($arFilter['enddate_m'], '0'))."' "; }
			if (wpsg_isSizedString($arFilter['s']))
			{
				
				$strQueryWHERE .= "
					AND
					(
						OA.`vname` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						OA.`name` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						C.`email` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						OA.`firma` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						C.`ustidnr` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						O.`onr` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						O.`id` = '".wpsg_q($arFilter['s'])."'
					)
				";
				
				$bJoinOrderAdress = true;
				$bJoinCustomer = true;
				
			}
			
			if (wpsg_isSizedInt($arFilter['status']) || $arFilter['status'] == '0')
			{
				
				$strQueryWHERE .= " AND A.`status` = '".wpsg_q($arFilter['status'])."' ";
				
			}
			else if (wpsg_isSizedArray($arFilter['status']))
			{
				
				$strQueryWHERE .= " AND O.`status` IN (".wpsg_q(implode(',', $arFilter['status'])).") ";
				
			}
			
			if (wpsg_isSizedInt($arFilter['NOTstatus']))
			{
				
				$strQueryWHERE .= " AND O.`status` != '".wpsg_q($arFilter['NOTstatus'])."' ";
				
			}
			else if (wpsg_isSizedArray($arFilter['NOTstatus']))
			{
				
				$strQueryWHERE .= " AND O.`status` NOT IN (".wpsg_q(implode(',', $arFilter['NOTstatus'])).") ";
				
			}
			
			// Sortierung
			if (wpsg_isSizedString($arFilter['order'], 'cdate')) { $strQueryORDER = " O.`cdate` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'nr')) { $strQueryORDER = " O.`onr` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'customer')) { $strQueryORDER = " CONCAT(OA.`vname`, ' ', OA.`name`) "; $bJoinCustomer = true; $bJoinOrderAdress = true; }
			else if (wpsg_isSizedString($arFilter['order'], 'payment')) {
				
				$strQuerySELECT .= " , (SELECT CASE `type_payment` ";
				
				foreach ($GLOBALS['wpsg_sc']->arPayment as $payment_key => $payment_info)
				{
					
					$strQuerySELECT .= " WHEN '".wpsg_q($payment_key)."' THEN '".wpsg_q($payment_info['name'])."' ";
					
				}
				
				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Deaktivierte Zahlungsart (', 'wpsg'))."', O.`type_payment`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `paymentlabel` ";
				
				$strQueryORDER = " CONCAT(`paymentlabel`, ' - ', `price_payment`) ";
				
			}
			else if (wpsg_isSizedString($arFilter['order'], 'shipping')) {
				
				$strQuerySELECT .= " , (SELECT CASE `type_shipping` ";
				
				foreach ($GLOBALS['wpsg_sc']->arShipping as $shipping_key => $shipping_info)
				{
					
					$strQuerySELECT .= " WHEN '".wpsg_q($shipping_key)."' THEN '".wpsg_q($shipping_info['name'])."' ";
					
				}
				
				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Deaktivierte Versandart (', 'wpsg'))."', O.`type_shipping`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `shippinglabel` ";
				
				$strQueryORDER = " CONCAT(`shippinglabel`, ' - ', `price_shipping`) ";
				
			}
			else if (wpsg_isSizedString($arFilter['order'], 'products'))
			{
				
				$bJoinOrderProducts = true;
				$strQuerySELECT .= ", COUNT(OP.`id`) AS `count_products` ";
				$strQueryORDER = " `count_products` ";
				
			}
			else if (wpsg_isSizedString($arFilter['order'], 'amount')) $strQueryORDER .= " `price_gesamt_brutto` ";
			else if (wpsg_isSizedString($arFilter['order'], 'state'))
			{
				
				$strQuerySELECT .= " , (SELECT CASE `status` ";
				
				foreach ($GLOBALS['wpsg_sc']->arStatus as $state_key => $state_label)
				{
					
					$strQuerySELECT .= " WHEN '".wpsg_q($state_key)."' THEN '".wpsg_q($state_label)."' ";
					
				}
				
				$strQuerySELECT .= " ELSE CONCAT('".wpsg_q(__('Unbekannter Statuscode (', 'wpsg'))."', O.`status`, '".wpsg_q(__(')'))."') ";
				$strQuerySELECT .= "END) AS `statelabel` ";
				$strQueryORDER = " `statelabel` ";
				
			}
			else $strQueryORDER = " O.`id` ";
			
			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";
			
			// Optionale Joins
			if ($bJoinOrderProducts === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ORDERPRODUCT."` AS OP ON (O.`id` = OP.`o_id`) ";
			if ($bJoinCustomer === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_KU."` AS C ON (C.`id` = O.`k_id`) ";
			if ($bJoinInvoice === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_RECHNUNGEN."` AS I ON (I.`o_id` =  O.`id` AND I.`storno` = '0000-00-00 00:00:00' AND I.`rnr` != '') ";
			if ($bJoinOrderAdress === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ADRESS."` AS OA ON (OA.`id` = O.`adress_id`) ";

			// Abo-Tabelle immer dazu wegen Abo-Status
			$bJoinAbo = true;
			if ($bJoinAbo === true) $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ABO."` AS A ON (A.`order_id` =  O.`id`) ";
			$strQueryWHERE .= " AND P.`wpsg_mod_abo_activ` = 1 ";
			$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = OP.`p_id`) ";
			
			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);
			
		}	// public static function getQueryPartsAbo($arFilter = array())
		
	} // class wpsg_mod_order extends wpsg_model
 
?>