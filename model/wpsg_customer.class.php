<?php

	/**
	 * Model für einen Kunden
	 */
	class wpsg_customer extends wpsg_model
	{
		
		private $adress_data;
		public $data;
		
		/**
		 * Lädt die Kundendaten aus der Datenbank
		 */
		public function load($customer_id)
		{
			
			parent::load($customer_id);
			
			$this->data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_KU."` WHERE `id` = '".wpsg_q($customer_id)."' ");
			
			if (wpsg_isSizedInt($this->data['adress_id'])) $this->adress_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($this->data['adress_id'])."' ");
			else $this->adress_data = false;
			
			return true;
			
		} // public function load($id)

        public function anonymize() {
		    
		    $arOrder = wpsg_Order::find(['k_id' => $this->id]);
		    $arAdressID = [];
		    
		    foreach ($arOrder as $oOrder) {
		        
		        if ($oOrder->adress_id > 0) $arAdressID[] = $oOrder->adress_id;
		        if ($oOrder->shipping_adress_id > 0) $arAdressID[] = $oOrder->shipping_adress_id;
		     
            }
            
            $arAdressID = array_unique($arAdressID);
		    
		    if (wpsg_isSizedArray($arAdressID)) {
		        
		        $this->db->UpdateQuery(WPSG_TBL_ADRESS, [
		            'title' => '',
                    'name' => '----',
                    'vname' => '----',
                    'firma' => '----',
                    'fax' => '----',
                    'strasse' => '----',
                    'nr' => '----',
                    'plz' => '----',
                    'ort' => '----',
                    'land' => '----',
                    'tel' => '----',
                ], " `id` IN (".wpsg_q(implode(',', $arAdressID)).") ");
		        
            }
            
            if (intval($this->wp_user_id) > 0) {

                wp_delete_user($this->wp_user_id);
		        
            }
            
            $this->db->UpdateQuery(WPSG_TBL_KU, [
                'paypal_payer_id' => '----',
                'geb' => '0000-00-00',
                'email' => '----',
                'ustidnr' => '----',
                'custom' => '----',
                'passwort_saltmd5' => '',
                'comment' => '',
                'wp_user_id' => '',
                'group_id' => '',
                'wpsg_mod_statistics_long' => '----',
                'wpsg_mod_statistics_lat' => '----',
                'deleted' => '1',
                'status' => '-1',
                'anonymized' => 'NOW()'
            ], " `id` = '".wpsg_q($this->id)."' ", true);
		    
        } // public function anonymize()
        
		/**
		 * Aktuallisiert den Adressdatensatz
		 * @param Array $adress_data (Quoted!)
		 */
		public function updateAdress($adress_data)
		{
		
			if (!wpsg_isSizedInt($this->data['adress_id']))
			{
					
				// Es kann sein, dass zu einem Kunden noch kein Adressdatensatz existiert, dann anlegen
				$adress_data['cdate'] = 'NOW()';
		
				$adress_id = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adress_data);
		
				$this->db->UpdateQuery(WPSG_TBL_KU, array(
					'adress_id' => wpsg_q($adress_id)
				), " `id` = '".wpsg_q($this->id)."' ");
		
				$this->data['adress_id'] = $adress_id;
		
			}
			else
			{
		
				$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adress_data, " `id` = '".wpsg_q($this->data['adress_id'])."' ");
		
			}
				
			$this->adress_data = $adress_data;
				
		} // public function updateAdress($adress_data)
		
		/**
		 * Gibt das Geschlecht des Kunden zurück
		 * m = male (männlich)
		 * f = female (weiblich)
		 */
		public function getGender()
		{
			
			if (preg_match('/Frau/i', $this->getTitle()))
			{
				
				return 'f';
				
			}
			else
			{
				
				return 'm';
				
			}
			
		} // public function getGender()
		
		/**
		 * Gibt die Anrede zurück
		 */
		public function getTitle()
		{
			
			$title = "";
			
			if ($this->adress_data === false) $title = $this->data['title'];
			else $title = $this->adress_data['title'];
			
			$arAnrede = explode('|', $this->shop->get_option('wpsg_admin_pflicht')['anrede_auswahl']);
			if ($title < 0) return '';
			else return $arAnrede[$title];
			
		} // public function getTitle()
		
		/**
		 * Gibt die Firma zurück
		 */
		public function getCompany()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['firma']);
			else return $this->adress_data['firma']; 
			
		} // public function getCompany()
		
		/**
		 * Gibt den Vornamen des Kunden zurück
		 * @return String Vorname des Kunden
		 */
		public function getFirstname()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['vname']);
			else return $this->adress_data['vname'];
			
		} // public function getFirstname()
		 
		/**
		 * Gibt den Namen des Kunden zurück
		 * @return String Name des Kunden
		 */
		public function getName()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['name']);
			else return $this->adress_data['name'];
			
		} // public function getName()
				
		/**
		 * Gibt die Telefonnummer zurück
		 */
		public function getPhone()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['tel']);
			else return $this->adress_data['tel']; 
			
		} // public function getPhone()
		
		/**
		 * Gibt die Fax Nummer zurück
		 */
		public function getFax()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['fax']);
			else return $this->adress_data['fax']; 
			
		} // public function getFax()
				
		/**
		 * @param $trimHnr Wenn true, dann wird versucht die Hausnummer abzutrennen (Wenn Hnr nicht separat erfasst)
		 * @return String der Reine Straßenname der Rechnungsadresse
		 */
		public function getStreetClear($trimHnr = false)
		{
			
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_showNr')) && $trimHnr === true)
			{
				
				if ($this->adress_data === false) $street = wpsg_getStr($this->data['strasse']);
				else $street = $this->adress_data['strasse'];
				
				return preg_replace('/\040+\d+$/', '', $street);
				
			}
			else
			{
				
				if ($this->adress_data === false) return wpsg_getStr($this->data['strasse']);
				else return $this->adress_data['strasse'];
				
			}
			
		} // public function getStreetClear() 	
		
		/**
		 * Gibt die Straße (inkl. Hausnummer) des Kunden zurück
		 * @return String Straße des Kunden
		 */
		public function getStreet()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['strasse']);
			else return rtrim($this->adress_data['strasse'].' '.$this->adress_data['nr']);
						
		} // public function getStreet()
		
		/**
		 * Gibt die Hausnummer des Kunden zurück
		 * @return String Hausnummer des Kunden
		 */
		public function getStreetNr()
		{
			
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_showNr')))
			{
				
				return $this->nr;
				
			}
			
			return preg_replace('/(.*)\040/', '', $this->strasse);
			
		} // public function getStreetNr()
		
		/**
		 * Gibt die Postleitzahl des Kunden zurück
		 * @return String Postleitzahl des Kunden
		 */
		public function getZip()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['plz']);
			else return $this->adress_data['plz'];
			
		} // public function getZip()
		
		/**
		 * Gibt den Order des Kunden zurück
		 * @return String Wohnort des Kunden
		 */
		public function getCity()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['ort']);
			else return $this->adress_data['ort'];
			
		} // public function getCity()
				
		/**
		 * Gibt die ID des Landes zurück
		 * @return \Integer ID des Landes
		 */
		public function getCountryID()
		{
			
			if ($this->adress_data === false) return wpsg_getStr($this->data['land']);
			else return $this->adress_data['land']; 
			
		} // public function getCountryID()
		
		/**
		 * Gibt das Kürzel des Landes zurück
		 * In der Regel sollte es dem ISO Code entsprechen
		 */
		public function getCountryKuerzel()
		{
			
			return $this->db->fetchOne("SELECT `kuerzel` FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->getCountryID())."' ");
			
		} // public function getCountryKuerzel()
		
		/**
		 * Gibt den Namen des Landes zurück
		 */
		public function getCountryName()
		{
			 
			return $this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->getCountryID())."' ");
			
		} // public function getCountryName()
 		
		/**
		 * Darstellung des Kunden
		 */
		public function getLabel()
		{

			return trim($this->getFirstname().' '.$this->getName());

		} // public function getLabel()

		/** Gibt die Kundennummer zurück */
		public function getNr() { return $this->knr; } // public function getNr()
    
		/**
		 * Gibt das Geburtsdatum zurück
		 * @param Mixed $format false = Standard, timestamp = Timestamp
		 */
		public function getBirthdate($format = false)
		{
			
			if ($format !== false)
			{
				
				if ($format === "timestamp") return strtotime($this->geb);
				else return wpsg_date($format, strtotime($this->geb));	
				
			}
			else 
			{
			
				return wpsg_formatTimestamp(strtotime($this->geb), true);
				
			}
			
		} // public function getBirthdate()
 
		/**
		 * Gibt die EMail des Kunden zurück 
		 */
		public function getEMail()
		{
			
			return $this->email;
			
		} // public function getEMail()
		
		/**
		 * Gibt den Umsatz eines Kunden mit Bestellungen $order_status oder über alle Bestellungen zurück
		 * @param unknown $order_status
		 */
		public function getOrderAmount($order_status = false)
		{
			
			$arOrderFilter = array(
				'k_id' => $this->id					
			);
			
			if (wpsg_isSizedInt($order_status) || wpsg_isSizedArray($order_status))
			{
				
				$arOrderFilter['status'] = $order_status;
				
			}
			
			$arOrder = wpsg_order::find($arOrderFilter);
			$amount = 0;
			
			foreach ($arOrder as $oOrder)
			{
				
				$amount += $oOrder->getAmount();
				
			} // foreach ($arOrder as $oOrder)
			
			return $amount;
			
		} // public function getOrderAmount($order_status = false)

		/**
		 * Gibt die Anzahl der Bestellungen des Kunden zurück
		 */
		public function getOrderCount()
		{

			if ($this->shop->get_option('wpsg_showincompleteorder') != '1')
			{
				$stat = array();
				foreach ($this->shop->arStatus as $k => $s)
				{
					if ($k != wpsg_ShopController::STATUS_UNVOLLSTAENDIG) $stat[] = $k;
				}
				return wpsg_order::count(array('k_id' => $this->id, 'status' => $stat));
			}
			else
				return wpsg_order::count(array('k_id' => $this->id));

		} // public function getOrderCount()

		public function delete()
		{
			
			// Kundendatensatz löschen
			$this->db->UpdateQuery(WPSG_TBL_KU, array('deleted' => '1'), " `id` = '".wpsg_q($this->id)."' ");
			
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
						  	DISTINCT K.`id`
						FROM
							`".WPSG_TBL_KU."` AS K
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

		public static function find($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strLimit = "";

			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);
			
			$strQuery = "
				SELECT
					K.`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_KU."` AS K
					    ".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."		
				".$strLimit."
			";	
			
			$arCustomerID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
			$arReturn = array();
			
			foreach ($arCustomerID as $customer_id)
			{
				 
				$arReturn[$customer_id] = self::getInstance($customer_id);
				
			}
			
			return $arReturn;
			
		} // public function find($arQuery = array())
		
        public static function hasFilteR($arFilter = []) {

            list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);
            
            if (wpsg_isSizedString($strQueryWHERE) || wpsg_isSizedString($strQueryHAVING)) return true;
            else return false;
		    
        }
        
		public static function getQueryParts($arFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryWHERE = "";
			$strQueryJOIN = "";
			$strQueryHAVING = "";
            
            $bJoinAdress = false;            

			$strQuerySELECT .= ",
				(SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id` AND O.`status` != '".wpsg_q(wpsg_ShopController::STATUS_UNVOLLSTAENDIG)."') AS order_count,
				(SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id` AND O.`status` = '".wpsg_q(wpsg_ShopController::STATUS_UNVOLLSTAENDIG)."') AS order_count_incomplete
			";

			if (wpsg_isSizedString($arFilter['email']))
			{
				
				$strQueryWHERE .= " AND K.`email` = '".wpsg_q($arFilter['email'])."' ";
				
			}
			
			if (wpsg_isSizedInt($arFilter['group_id'])) $strQueryWHERE .= " AND `group_id` = '".wpsg_q($arFilter['group_id'])."' ";

			if (wpsg_isSizedString($arFilter['s']))
			{

			    $bJoinAdress = true;
			    
				$strQueryWHERE .= " 
					AND (
						CA.`name` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						CA.`vname` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						K.`email` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						K.`id` = '".wpsg_q($arFilter['s'])."' OR
						K.`knr` LIKE '%".wpsg_q($arFilter['s'])."%'
					)
				";

			}
			
			if (!wpsg_isTrue($arFilter['showDeleted'])) $strQueryWHERE .= " AND `deleted` != '1' ";

			//$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_KU."` AS C ON (C.`id` = O.`k_id`) ";
			//$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = C.`adress_id`) ";
			
			//if (wpsg_isSizedString($arFilter['order'], 'nr')) { $strQueryORDER = " K.`knr`, K.`vname`, K.`name` "; }
			if (wpsg_isSizedString($arFilter['order'], 'nr')) { $strQueryORDER = " K.`knr` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'adress')) { $bJoinAdress = true; $strQueryORDER = " CONCAT(CA.`plz`, CA.`ort`) "; }
			else if (wpsg_isSizedString($arFilter['order'], 'status')) { $strQueryORDER = " order_count "; }
			else $strQueryORDER = " K.`id` ";
            
			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";

            if ($bJoinAdress)
            {
                
                $strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = K.`adress_id`) ";
                
            }
            
			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);
				
		} // public function getQueryParts($arFilter = array())
		
	} // class wpsg_order extends wpsg_model
	
?>