<?php

	/**
	 * Klasse, die ein Produkt behandelt
	 */
	class wpsg_product extends wpsg_model
	{

		const MULTIPLE_ONE_MULTI = 0; // Nur einmal mit beliebiger Menge (Standard)
		const MULTIPLE_ONE_ONE = 4; // Nur einmal mit Menge 1
		const MULTIPLE_MULTI_MULTI = 1; // Mehrfach mit beliebiger Menge
		const MULTIPLE_MULTI_ONE = 2; // Mehrfach mit Menge 1

		private $_cache = [];
		private $arMeta = null;
		
		public $loadedData = array(); /* Enthält die Daten, die über den ShopController und die Module geladen / erweitert wurden */		
		public $product_key = "";		

		/**
		 * Lädt die Daten des Produkts
		 */
		public function load($product_id, $loadedData = false) {		 

			parent::load($product_id);

			$this->data = $this->db->fetchRow("SELECT P.* FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`id` = '".wpsg_q($product_id)."' ");

			if ($this->shop->isOtherLang()) {

				$trans_produkt = $this->db->fetchRow("
					SELECT
						P.`id`, P.`name`, P.`beschreibung`, P.`detailname`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
					WHERE
						P.`lang_code` = '".wpsg_q($this->shop->getCurrentLanguageCode())."' AND
						P.`lang_parent` = '".wpsg_q($product_id)."'
				");

				if ($trans_produkt['id'] > 0)
				{

					$this->data['name'] = $trans_produkt['name'];
					$this->data['beschreibung'] = $trans_produkt['beschreibung'];
					$this->data['detailname'] = $trans_produkt['detailname'];

				}

			}
 
			$this->loadedData = $this->shop->loadProduktArray($product_id);

			if ($this->data['id'] != $product_id) throw new \wpsg\Exception(wpsg_translate(__('Gesuchte ID:#1# Gefundene ID:#2#', 'wpsg'), $product_id, $this->data['id']));

			return true;

		} // public function load($product_id)
		
		/**
		 * Gibt die Artikelnummer zurück
		 * @return string
		 */
		public function getNr() { return $this->anr; }
		
		public function appendData($arData)
		{

			$this->loadedData = $arData;

		} // public function appendData($arData)

		public function delete()
		{

			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array(
				'deleted' => '1'
			), "`id` = '".wpsg_q($this->id)."'");

			$this->shop->callMods('produkt_del', array($this->id));

			// Übersetzung löschen
			$this->db->UpdateQuerY(WPSG_TBL_PRODUCTS, array(
				'deleted' => '1'
			), "`lang_parent` = '".wpsg_q($this->id)."'");

		} // public function delete()

		/**
		 * Setzt den Produkt Key
		 */
		public function setProductKey($product_key)
		{

			$this->product_key = $product_key;
			
			$this->shop->callMods('product_setProductKey', [&$this, $product_key]);

		} // public function setProductKey($product_key)

		/**
		 * Gibt den ProductKey zurück
		 */
		public function getProductKey()
		{
			
			if (strlen($this->product_key) > 0) return $this->product_key;
			else
			{
				 
				if (wpsg_isSizedString($this->loadedData['product_key'])) return $this->loadedData['product_key'];
				
			}
			
			return $this->id;
			
		} // public function getProductKey()

		/**
		 * Setzt einen Wert in der META Tabelle
		 */
		public function setMeta($meta_key, $meta_value)
		{

			$nExistsID = $this->db->fetchOne("SELECT M.`id` FROM `".WPSG_TBL_META."` AS M WHERE M.`meta_table` = 'WPSG_TBL_PRODUCTS' AND M.`meta_key` = '".wpsg_q($meta_key)."' AND `target_id` = '".wpsg_q($this->id)."' ");

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
					'meta_table' => "WPSG_TBL_PRODUCTS",
					'meta_key' => wpsg_q($meta_key),
					'meta_value' => wpsg_q($meta_value)
				));

			}

		} // public function setMeta($meta_key, $meta_value)

		/**
		 * Gibt einen META Value zurück
		 */
		public function getMeta($meta_key) {

			if ($this->arMeta === null) {
				
				$this->arMeta = $this->db->fetchAssocField("SELECT M.`meta_key`, M.`meta_value` FROM `".WPSG_TBL_META."` AS M WHERE M.`meta_table` = 'WPSG_TBL_PRODUCTS' AND M.`target_id` = '".wpsg_q($this->id)."' ", "meta_key", "meta_value");
				 				
			}
			
			if (isset($meta_key, $this->arMeta)) return $this->arMeta[$meta_key];
			else return '';

		} // public function getMeta($meta_key)
		
		/**
		 * @return wpsg_productgroup
		 */
		public function getProductgroup() {

			return wpsg_productgroup::getInstance($this->pgruppe);

		} // public function getProductgroup()

		public function getProductgroupName()
		{

			$oProductGroup = $this->getProductgroup();

			if ($oProductGroup === false) return '----';
			else return $oProductGroup->name;

		} // public function getProductgroupName()

		/**
		 * Gibt die Produktbeschreibung zurück
		 */
		public function getShortDescription()
		{

			return $this->data['beschreibung'];

		} // public function getShortDescription()

		/**
		 * Gibt den Namen des Produktes zurück
		 */
		public function getProductName($detailname = false)
		{

			$strDetailname = $this->detailname;

			if (wpsg_isSizedString($strDetailname) && $detailname === true)
			{

				$strName = $strDetailname;

			}
			else
			{

				$strName = $this->name;

			}

			if (strpos($strName, '^') !== false)
			{

				$strName = preg_replace('/\^/', '<span class="wpsg_upper">', $strName).'</span>';

			}

			return $strName;

		} // public function getProductName()

		/**
		 * Gibt die ProduktURL zurück
		 * Siehe auch wpsg_ShopController->getProduktLink (Macht das gleiche, aber beachtet den Basket von welcher URL es hinzugefügt wurde da URL nicht eindeutig)
		 */
		public function getProductURL($language_key = false)
		{

			$url = false;

			$this->shop->callMods('getProduktlink', array($this->id, &$url, $language_key));

			if ($url === false)
			{

				$partikel = $this->partikel;

				if (wpsg_isSizedInt($partikel))
				{

					return get_permalink($this->partikel);

				}
				else
				{

					$basket_url = $this->shop->getURL(wpsg_ShopController::URL_BASKET);

					if (strpos($basket_url, "?") > 0)
					{

						$basket_url .= "&wpsg_action=showProdukt&produkt_id=".$this->id;

					}
					else
					{

						$basket_url .= "?wpsg_action=showProdukt&produkt_id=".$this->id;

					}

					return $basket_url;

				}

			}
			else
			{

				return $url;

			}

		} // public function getProductURL() 
        
		public function getDefaultTaxValue()
		{

			return $GLOBALS['wpsg_sc']->getDefaultCountry()->getTax($this->mwst_key);

		} // public function getDefaultTaxValue()

		/**
		 * Bestimmt ob das Produkt z.B. in den Produktübersichten angezeigt werden kann
		 *
		 * @param String $product_key Der ProduktKey
		 * @return bool true wenn das Produkt sichtbar ist
		 */
		public function canDisplay($product_key = false)
		{

			if ($product_key === false) $product_key = $this->id;

			// Module Checken
			$bOK = $this->shop->callMods('canDisplay', array($product_key));

			return $bOK;

		} // public function canDisplay($product_key = false)

		/**
		 * Bestimmt ob der Preis des Produktes angezeigt werden darf
		 *
		 * @param String $product_key Der ProduktKey
		 * @param bool $product_key true wenn der Preis angezeigt werden darf
		 */
		public function canDisplayPrice($product_key = false)
		{

			if ($product_key === false) $product_key = $this->id;
						
			// War mal im Produkt, muss aber auch ohne Produktbezug funktionieren z.B. für den Preisfilter
			// Im ShopController wird die Preisanzeige für angemeldete Nutzer geprüft
			return $GLOBALS['wpsg_sc']->canDisplayPrice();

		} // public function canDisplayPrice($product_key = false)

		/**
		 * Gibt true zurück, wenn das Produkt gekauft werden kann, false wenn nicht
		 * Bestimmt ob der Button im Warenkorb angezeigt wird
		 */
		public function canOrder($product_key = false)
		{

			if ($product_key === false) $product_key = $this->id;

			// Anfrageprodukt?
			if ($this->shop->hasMod('wpsg_mod_request'))
			{

				$wpsg_mod_request_set = $this->getMeta('wpsg_mod_request_set');

				//if (!(!is_numeric($wpsg_mod_request_set) || in_array($wpsg_mod_request_set, array(wpsg_mod_request::TYP_YES, wpsg_mod_request::TYP_YESWITHBASKET)))) return false;
				if (!(!is_numeric($wpsg_mod_request_set) || ($wpsg_mod_request_set == wpsg_mod_request::TYP_NO))) return false;
				
			}
			
			// Kauf nur für angemeldete Benutzer?
			if (!$this->shop->canDisplayPrice()) return false;
			
						// Module Checken
			$bOK = $this->shop->callMods('canOrder', array($product_key));
			
			return $bOK;

		} // public function canOrder()

		/**
		 * Gibt den MwSt. Wert für das Frontend zurück
		 */
		public function getFrontendTaxValue()
		{

		    $oCountry = $this->shop->getFrontendCountry();
             
            return $oCountry->getTax($this->mwst_key);

		} // public function getFrontendTaxValue()

		/**
		 * Gibt den Alten Preis für das Frontend zurück
		 */
		public function getOldPrice($taxView = false)
		{
		    
		    if ($taxView === false)
		    {
		        
		        if (is_admin()) $taxView = $this->shop->getBackendTaxview();
		        else $taxView = $this->shop->getFrontendTaxview();
		        
		    }
		    
		    $oldprice = wpsg_tf($this->oldprice);
		    
		    // Rabatt überschreibt die Produkteinstellung
		    if ($this->shop->hasMod('wpsg_mod_discount') && wpsg_isSizedInt($this->shop->get_option('wpsg_mod_discount_show')) && isset($this->loadedData['preis_prediscount']))
		    {
		        
		        // Rabatt
		        $oldprice = $this->loadedData['preis_prediscount'];
		        
		    }
		    
		    if ($oldprice > 0) return $this->calculateTaxViewPrice($oldprice, $taxView);
		    else return false;
		    
		}
		

		public function getMinPrice($product_key = false)
		{

			return $this->getPrice($product_key);

		}

		/**
		 * Gibt den Preis für das Frontend zurück
		 * @param $product_key Produktkey
		 * @param $taxView Anzuzeigende Steuer
		 * @param int $amount Menge des Produktes im Warenkorb. bei false wird in die Session geschaut
		 * @param double $weight Gewicht des Produktes im Warenkob, bei false wird anhand der Session ermittelt 
		 * @param boolean $scalePrice Wenn False, dann wird der Staffelpreis ignoriert
		 */
		public function getPrice($product_key = false, $taxView = false, $amount = false, $weight = false, $scalePrice = true) {
	
			// Abwärtskompatibilität, weil alte Produkttemplates die Funktion ohne Parameter aufrufen
			if ($product_key === false && wpsg_isSizedString($GLOBALS['wpsg_sc']->view['data']['product_key'])) $product_key = $GLOBALS['wpsg_sc']->view['data']['product_key'];
			
			// Grundpreis aus dem Produkt
			if ($this->shop->getBackendTaxview() === WPSG_BRUTTO) {
				
				$price_brutto = $this->data['preis'];
				$price_netto = wpsg_calculatePreis($price_brutto, WPSG_NETTO,$this->shop->getDefaultCountry()->getTax($this->data['mwst_key']));
				
			} else {
				
				$price_netto = $this->data['preis'];
				$price_brutto = wpsg_calculatePreis($price_netto, WPSG_BRUTTO, $this->shop->getDefaultCountry()->getTax($this->data['mwst_key']));
				
			}
			
			if ($scalePrice) {
				
				// Staffelpreis
				$this->shop->callMod('wpsg_mod_scaleprice','product_getPrice', [&$this, &$price_netto, &$price_brutto, $product_key, $amount, $weight]);
				
			}
	 
			// Varianten 
			$this->shop->callMod('wpsg_mod_productvariants', 'product_getPrice', [&$this, &$price_netto, &$price_brutto, $product_key, $amount, $weight]);
			
			// Rabatte
			$this->shop->callMod('wpsg_mod_discount', 'product_getPrice', [&$this, &$price_netto, &$price_brutto, $product_key, $amount, $weight]);
			 
			if ($taxView === false)
			{
				
				if (is_admin()) $taxView = $this->shop->getBackendTaxview();
				else $taxView = $this->shop->getFrontendTaxview();
				
			}
			
			if ($taxView === WPSG_BRUTTO)
			{
				
				$price = $price_brutto;
				
			}
			else
			{
				
				$price = $price_netto;
				
			} 
			
			return $price;

		}

		/**
		 * Gibt true zurück, wenn die Zahlungsarten für das Produkt eingeschränkt sind
		 * @return bool
		 */
		public function hasLimitedPayment()
		{

			$arAllowedPayment = explode(',', $this->allowedpayments);
			$arAllowedPayment = wpsg_trim($arAllowedPayment, array('', '0'));

			if (wpsg_isSizedArray($arAllowedPayment))
			{

				return true;

			}

		} // public function hasLimitedPayment()

		/**
		 * Gibt einen Array mit IDs von Zahlungsarten zurück, die für dieses Produkt erlaubt sind
		 * Vorsicht! Wenn alle erlaubt sind gibt es einen leeren Array zurück, deshalb mit hasLimitedPayment vorher prüfen
		 * @return array
		 */
		public function getAllowedPayment()
		{

			if (!$this->hasLimitedPayment()) return array();
			else
			{

				$arPayment = wpsg_trim((array)explode(',', $this->allowedpayments), '0');

				return $arPayment;

			}

		} // public function getAllowedPayment()

		/**
		 * Gibt true zurück, wenn die Versandarten für das Produkt eingeschränkt sind
		 */
		public function hasLimitedShipping()
		{

			$arAllowedShipping = explode(',', $this->allowedshipping);
			$arAllowedShipping = wpsg_trim($arAllowedShipping, array('', '0'));

			if (wpsg_isSizedArray($arAllowedShipping))
			{

				return true;

			}

			if ($this->shop->hasMod('wpsg_mod_downloadprodukte') && $this->shop->callMod('wpsg_mod_downloadprodukte', 'getProdFiles',[$this->id]) !== false) {
							    
			    return true;
			    
            }
			
			return false;

		} // public function hasLimitedShipping()

        public function getRatingCount()
        {

            if (!isset($this->loadedData['rating'])) return 0;
            else if ($this->loadedData['rating'] === '-1')
            {

                return sizeof(\get_comments(array(
                    'post_id' => $this->GetPostID(),
                    'type' => 'wpsg_product_comment',
                    'status' => 'approve'
                )));

            }
            else return 1;

        }
        
		public function getRating()
		{
			 
			if (!isset($this->loadedData['rating_calculated']))
			{
				
				if (!isset($this->loadedData['rating'])) $this->loadedData['rating_calculated'] = 0;
				else
				{
					
					if ($this->loadedData['rating'] === '-1')
					{
						
						$arApprovedComments = \get_comments(array(
							'post_id' => $this->GetPostID(),
							'type' => 'wpsg_product_comment',
							'status' => 'approve'
						));
						
						$point = 0;
						$nPointSet = 0;
						
						foreach ($arApprovedComments as $oWP_Comment)
						{
							
							$comment_point = intval(get_comment_meta($oWP_Comment->comment_ID, 'sto_points', true));
							
							if ($comment_point > 0)
							{
								
								$point += $comment_point;
								$nPointSet ++;
								
							}
							
						}
								 				
						if ($nPointSet <= 0) $this->loadedData['rating_calculated'] = 0;
						else $this->loadedData['rating_calculated'] = $point / $nPointSet;
						
					}
					else
					{
						
						$this->loadedData['rating_calculated'] = $this->loadedData['rating'];
						
					}
					
				}
				
			}
			
			return $this->loadedData['rating_calculated'];
			
		}
		
		public function getPostID()
		{
			
			if (!isset($this->loadedData['post_id'])) return false;
			else return $this->loadedData['post_id'];
			
		} // public function getPostID()
		
		/**
		 * Gibt einen Array mit IDs von Versandarten zurück, die für dieses Produkt erlaubt sind
		 * Vorsicht! Wenn alle erlaubt sind gibt es einen leeren Array zurück, deshalb mit hasLimitedPayment vorher prüfen
		 * @return array
		 */
		public function getAllowedShipping()
		{

            $arShipping = wpsg_trim((array)explode(',', $this->allowedshipping), ['', '0']);
            
            if (wpsg_isSizedArray($arShipping)) return $arShipping;
            else {
                
                if (!is_array($this->shop->arShippingAll)) $this->shop->arShippingAll = [];
                
                $arShipping = array_keys($this->shop->arShippingAll);

                if ($this->shop->hasMod('wpsg_mod_downloadprodukte')) {
                    
                    if ($this->shop->callMod('wpsg_mod_downloadprodukte', 'getProdFiles',[$this->id]) === false) {

                        unset($arShipping[array_search('601', $arShipping)]);

                    } else {
                        
                        $arShipping = ['601'];
                        
                    }
                        
                }

                if ($this->shop->hasMod('wpsg_mod_downloadplus')) {

                    if ($this->shop->callMod('wpsg_mod_downloadplus', 'isPDFProdukt',[$this->id]) === false) {

                        unset($arShipping[array_search('101', $arShipping)]);

                    } else {

                        $arShipping = ['101'];

                    }

                }

                return $arShipping;
                
            } 

		} // public function getAllowedShipping()

		/* Private Funktionen */

		/**
		 * @param $price	Der Preis aus dem Backend
		 * @param $taxView
		 * @return float
		 */
		private function calculateTaxViewPrice($price, $taxView)
		{

			if ($this->shop->getBackendTaxview() === WPSG_NETTO)
			{

				$price_netto = $price;
				$price_brutto = wpsg_calculatePreis($price_netto, WPSG_BRUTTO, $this->shop->getCalcTaxValue($this->mwst_key));

			}
			else
			{

				$price_brutto = $price;
				$price_netto = wpsg_calculatePreis($price_brutto, WPSG_NETTO, $this->shop->getCalcTaxValue($this->mwst_key));

			}

			if ($taxView === false)
			{

				if (is_admin()) $taxView = $this->shop->getBackendTaxview();
				else $taxView = $this->shop->getFrontendTaxview();

			}

			if ($taxView === WPSG_NETTO) return $price_netto;
			else return $price_brutto;

		}
		
		public function getMenuOrder() {
			
			return $this->loadedData['menu_order'];
			
		}
		
		/* Statische Funktionen */

		public static function findMaxPrice($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					MAX(P.`preis`)
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
						".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
			";

			return $GLOBALS['wpsg_db']->fetchOne($strQuery);

		}

		public static function findMinPrice($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					MIN(P.`preis`)
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
						".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
			";
 
			return $GLOBALS['wpsg_db']->fetchOne($strQuery);

		} // public static function getMinPrice($arFilter = array())

		public static function count($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					(
						SELECT
						  	DISTINCT P.`id`
						FROM
							`".WPSG_TBL_PRODUCTS."` AS P
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

		}

		public static function find($arFilter = array(), $load = true)
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strLimit = "";

			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);

			$strQuery = "
				SELECT
					P.`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
						".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				GROUP BY
					P.`id`
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."
				".$strLimit."
			";
              
			$arID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
			$arReturn = array();

			// Manche Filterungen erfordern leider Objektzugriff
			$force_load = false;

			// Wenn ich die nicht kaufbaren Produkte rausfiltern will, kann ich es nicht auf einen DB Query beschränken, da ich die Module anfragen muss
			//if (wpsg_isSizedInt($arFilter['hideNotBuyable'])) $force_load = true;

			foreach ($arID as $id)
			{

				if ($load === true || $force_load === true)
				{

					$arReturn[$id] = self::getInstance($id);

					//if (wpsg_isSizedInt($arFilter['hideNotBuyable']) && !$arReturn[$id]->canOrder()) { unset($arReturn[$id]); continue; }

					// Standardmäßig werden nicht anzeigbare Produkte ausgeblendet
					// Erfordert das die Objekt geladen wurden, sonst muss später geprüft werden
					if (!$arReturn[$id]->canDisplay()) { unset($arReturn[$id]); continue; }

					if (isset($arReturn[$id]) && $force_load === true && $load === false) $arReturn[$id] = $id;

				}
				else $arReturn[$id] = $id;

			}

            if (isset($arFilter['order']) && $arFilter['order'] === 'price') {

                uasort($arReturn, function($a, $b) {

                    if (!is_object($a)) $a = wpsg_product::getInstance($a);
                    if (!is_object($b)) $b = wpsg_product::getInstance($b);

                    if ($a->getMinPrice() == $b->getMinPrice()) return 0;
                    return ($a->getMinPrice() < $b->getMinPrice())?-1:1;

                } );

            }

			return $arReturn;

		} // public function find($arQuery = array())

		public static function getQueryParts($arFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryWHERE = " AND P.`lang_parent` <= 0 ";
			$strQueryJOIN = "";
			$strQueryHAVING = "";
			$strQueryORDER = "";
            
            $bJoinPost = false;
            $bJoinVariants = false;
            
			if (wpsg_isSizedArray($arFilter['product_ids'])) $strQueryWHERE .= " AND P.`id` IN (".wpsg_q(implode(',', $arFilter['product_ids'])).") ";

			if (wpsg_isSizedArray($arFilter['productgroup_ids'])) $strQueryWHERE .= " AND P.`pgruppe` IN (".wpsg_q(implode(',', $arFilter['productgroup_ids'])).") ";
			else if (wpsg_isSizedString($arFilter['productgroup_ids']) && $arFilter['productgroup_ids'] != '-1') $strQueryWHERE .= " AND P.`pgruppe` IN (".wpsg_q($arFilter['productgroup_ids']).") ";

			if (wpsg_isSizedString($arFilter['price_min'])) $strQueryWHERE .= " AND P.`preis` >= '".wpsg_q($arFilter['price_min'])."' ";
			if (wpsg_isSizedString($arFilter['price_max'])) $strQueryWHERE .= " AND P.`preis` <= '".wpsg_q($arFilter['price_max'])."' ";
			if (wpsg_isSizedString($arFilter['s']))
			{

				$strQueryWHERE_OR = "";
                
                if ($GLOBALS['wpsg_sc']->hasMod('wpsg_mod_productvariants') && wpsg_isSizedInt($arFilter['searchExt']))
                {
                    
                    $bJoinVariants = true;
                     
                    //$strQueryWHERE_OR .= " OR V.`name` LIKE '%".wpsg_q($arFilter['s'])."%' ";
                    $strQueryWHERE_OR .= " OR VV.`name` LIKE '%".wpsg_q($arFilter['s'])."%' ";
                    $strQueryWHERE_OR .= " OR VV.`shortname` LIKE '%".wpsg_q($arFilter['s'])."%' ";
                    $strQueryWHERE_OR .= " OR PV.`anr` LIKE '%".wpsg_q($arFilter['s'])."%' ";
                        
                }

                $strQueryWHERE .= "
					AND (
						P.`name` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						P.`anr` LIKE '%".wpsg_q($arFilter['s'])."%'
						".$strQueryWHERE_OR."
					)
				";

			}

			if (wpsg_isSizedArray($arFilter['cat_ids']) && $GLOBALS['wpsg_sc']->hasMod('wpsg_mod_produktartikel'))
			{

				$strQueryJOIN .= " LEFT JOIN `".wpsg_q($GLOBALS['wpsg_sc']->prefix.'posts')."` AS POST ON (POST.`wpsg_produkt_id` = P.`id`) ";
				$strQueryJOIN .= " LEFT JOIN `".wpsg_q($GLOBALS['wpsg_sc']->prefix.'term_relationships')."` AS PCAT ON (POST.`ID` = PCAT.`object_id`) ";

				$strQueryWHERE .= " AND PCAT.`term_taxonomy_id` IN (".wpsg_q(implode(',', $arFilter['cat_ids'])).") ";

			}

			if ((wpsg_isSizedString($arFilter['productcategory_ids'])) && ($arFilter['productcategory_ids'] != '-1') && ($GLOBALS['wpsg_sc']->hasMod('wpsg_mod_produktartikel')))
			{

				$strQueryJOIN .= " LEFT JOIN `".wpsg_q($GLOBALS['wpsg_sc']->prefix.'posts')."` AS POST ON (POST.`wpsg_produkt_id` = P.`id`) ";
				$strQueryJOIN .= " LEFT JOIN `".wpsg_q($GLOBALS['wpsg_sc']->prefix.'term_relationships')."` AS TR ON (POST.`ID` = TR.`object_id`) ";

				$strQueryWHERE .= " AND TR.`term_taxonomy_id` = ".$arFilter['productcategory_ids']." ";
				/*
				$strQueryWHERE = " AND P.`lang_parent` <= 0 ";
				$strQueryHAVING .= "
					AND (
						P.`name` LIKE '%".wpsg_q($arFilter['s'])."%' OR
						P.`anr` LIKE '%".wpsg_q($arFilter['s'])."%'
					)
				";
				*/

			}

			if (wpsg_isSizedArray($arFilter['variants']))
			{

				foreach (wpsg_trim($arFilter['variants']) as $variant_id => $variation_id)
				{

					$in = wpsg_q($variant_id);
					$strQueryJOIN .= " RIGHT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI".$in." ON (PVI".$in.".`product_id` = P.`id` AND PVI".$in.".`variation_id` IN (".wpsg_q($variation_id).") AND PVI".$in.".`active` = '1') ";

				}

			}

			if (wpsg_isSizedArray($arFilter['attributs']))
			{

				foreach ($arFilter['attributs'] as $attribut_id => $attribut_value)
				{

					$in = wpsg_q($attribut_id);
					$arValues = wpsg_trim(explode(',', $attribut_value));

					$strQueryJOIN_ATTRIBUTE_ON  = " ( 0 ";
					foreach ($arValues as $k => $value) $strQueryJOIN_ATTRIBUTE_ON .= " OR PA".$in.".`value` = '".wpsg_q($value)."' ";
					$strQueryJOIN_ATTRIBUTE_ON .= " ) ";

					$strQueryJOIN .= " RIGHT JOIN `".WPSG_TBL_PRODUCTS_AT."` AS PA".$in." ON (PA".$in.".`p_id` = P.`id` AND PA".$in.".`a_id` = '".wpsg_q($attribut_id)."' AND ".$strQueryJOIN_ATTRIBUTE_ON.") ";

				}

			}
			
			if (!wpsg_isTrue($arFilter['showDisabled'])) $strQueryWHERE .= " AND P.`disabled` != '1' ";

			if (wpsg_isSizedString($arFilter['order'], 'name')) { $strQueryORDER = " P.`name` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'price')) { $strQueryORDER = " P.`preis` "; }
            else if (wpsg_isSizedString($arFilter['order'], 'cdate')) { $strQueryORDER = " P.`cdate` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'anr')) { $strQueryORDER = " P.`anr` "; }
            else if (wpsg_isSizedString($arFilter['order'], 'pos')) { $strQueryORDER = " PP.`menu_order` "; $bJoinPost = true; }
			else if (wpsg_isSizedString($arFilter['order'], 'pgruppe'))
			{

				$strQuerySELECT .= " , (SELECT PG.`name` FROM `".WPSG_TBL_PRODUCTS_GROUP."` AS PG WHERE PG.`id` = P.`pgruppe`) AS `order_count` ";
				$strQueryORDER = " order_count ";

			}
			else $strQueryORDER = " P.`id` ";

            if ($bJoinPost) {
                
                $strQueryJOIN .= " LEFT JOIN `".$GLOBALS['wpsg_sc']->prefix."posts` AS PP ON (PP.`wpsg_produkt_id` = P.`id` AND PP.`post_type` = '".wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_produktartikel_pathkey'))."' ) ";
                
            }
            
            if ($bJoinVariants)
            {

                $strQueryJOIN .= " 
                    LEFT JOIN `".WPSG_TBL_VARIANTS."` AS V ON (V.`product_id` = P.`id` OR V.`product_id` = 0)
                    LEFT JOIN `".WPSG_TBL_VARIANTS_VARI."` AS VV ON (VV.`variant_id` = V.`id`)
                    LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PV ON (PV.`variation_id` = V.`id` AND PV.`product_id` = P.`id`)
                "; 
                
            }
            
			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";

			$strQueryWHERE .= " AND P.`deleted` != '1' ";

			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);

		} // public function getQueryParts($arFilter = array())

        /**
         * @param int $product_key
         * @param bool $noCache
         * @return wpsg_product
         */
		public static function getInstance($product_key, $noCache = false)
		{
			
			$class_name = get_called_class();
			 
			if (wpsg_isSizedArray($product_key))
			{
				
				$arReturn = [];
				
				foreach ($product_key as $_id)
				{
					
					$arReturn[$_id] = self::getInstance($_id);
										
				}
				
				return $arReturn;
				
			}
			else if (is_array($product_key)) return array();
			else
			{
			
				if (!array_key_exists($class_name.'_'.$product_key, self::$_objCache) || $noCache === true)
				{
					
					$product_id = $GLOBALS['wpsg_sc']->getProduktId($product_key);
					
					$oObject = new $class_name(); 
					$oObject->load($product_id);
					$oObject->setProductKey($product_key);
	
					self::$_objCache[$class_name.'_'.$product_key] = $oObject;
			
				}
					
				return self::$_objCache[$class_name.'_'.$product_key];
				
			}
			
		} // public static function getInstance($id, $noCache = false)

	} // class wpsg_product extends wpsg_model

?>