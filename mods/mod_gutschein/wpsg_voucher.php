<?php

	/**
	 * Gutscheinmodel
	 * @author Daschmi
	 */
	class wpsg_voucher extends wpsg_model
	{

		/**
		 * Lädt die Daten des Gutscheins
		 */
		public function load($voucher_id)
		{

			parent::load($voucher_id);

			$this->data = $this->db->fetchRow("
				SELECT 
					V.* 
				FROM
					`".WPSG_TBL_GUTSCHEIN."` AS V
				WHERE
					V.`id` = '".wpsg_q($voucher_id)."'
			");

			if ($this->data['id'] != $voucher_id || !wpsg_isSizedInt($voucher_id)) return false;

			return true;

		} // public function __construct($voucher_id)
				
		public function getFreeAmount() {
			
			if ($this->isMultiUsable()) return $this->value;
			
			$free = wpsg_tf($this->value) - $this->getUsedAmount();
			
			if ($free < 0) $free = 0;
			
			return $free;
			
		}
		
		public function isMultiUsable() {
			
			return wpsg_isSizedInt($this->multi);
			
		}
		
        public function getUsed() {

			return intval($this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` WHERE `gs_id` = '".wpsg_q($this->id)."' "));
			
        }
        
        public function getUsedAmount($taxView = false) {
	
			if ($taxView === false) $taxView = $this->shop->getBackendTaxview();
			
			if ($taxView === WPSG_NETTO) {
				
				return abs(wpsg_tf($this->db->fetchOne("SELECT SUM(`sum_netto`) FROM `".WPSG_TBL_ORDER_VOUCHER."` WHERE `voucher_id` = '".wpsg_q($this->getId())."' ")));
				
			} else if ($taxView === WPSG_BRUTTO) {
				
				return abs(wpsg_tf($this->db->fetchOne("SELECT SUM(`sum_brutto`) FROM `".WPSG_TBL_ORDER_VOUCHER."` WHERE `voucher_id` = '".wpsg_q($this->getId())."' ")));
				
			} else throw new \Exception(__('Nicht definiert.', 'wpsg'));
			
		}
        
        public function isCoupon() {
			
			return wpsg_isSizedInt($this->__get('coupon'));
			
		}

        public function isAutoCreated() {

            $autocreate_order = $this->autocreate_order;
                
            return wpsg_isSizedInt($autocreate_order);
		    
        }
        
        public function isUsabel() {
			
			if (strtotime($this->start_date) > time()) return false;
			if (strtotime($this->end_date) < time()) return false;
			if (!$this->isMultiUsable() && $this->getUsed() > 0) return false;
			if ($this->getFreeAmount() <= 0) return false;
			
			return true;
			
		}
        
        public function getStatusLabel()
        {
        	
        	if ($this->getFreeAmount() <= 0) return __('Verbraucht');

			if ($this->multi === '1')
			{
				
				if (current_time('timestamp') < strtotime($this->start_date)) return __('Wartend', 'wpsg');
				
				if (current_time('timestamp') < strtotime($this->start_date) || current_time('timestamp') > strtotime($this->end_date))
				{
					
					if ($this->getUsed() > 0) return __('Verbraucht', 'wpsg');
					else return __('Inaktiv', 'wpsg');
					
				}
				else return __('Aktiv', 'wpsg');
				
			}
			else
			{
				
				if (current_time('timestamp') < strtotime($this->start_date)) return __('Wartend', 'wpsg');
				
				if ($this->getUsed() > 0) return __('Verbraucht', 'wpsg');
				else
				{
				
					if (current_time('timestamp') < strtotime($this->start_date) || current_time('timestamp') > strtotime($this->end_date)) return __('Inaktiv', 'wpsg');
					else return __('Aktiv', 'wpsg');
					
				}
				
			}

        }

        public function getStatus()
        {
        	
        	if ($this->multi === '1')
        	{
        		
        		if (current_time('timestamp') < strtotime($this->start_date)) return 4;	// Wartend
        		
        		if (current_time('timestamp') < strtotime($this->start_date) || current_time('timestamp') > strtotime($this->end_date))
        		{
        			
        			if ($this->getUsed() > 0) return 3;   //('Verbraucht', 'wpsg');
        			else return 2; 	//('Inaktiv', 'wpsg');
        			
        		}
        		else return 1;	//__('Aktiv', 'wpsg');
        		
        	}
        	else
        	{

        		if (current_time('timestamp') < strtotime($this->start_date)) return 4;	// Wartend
        		
        		if ($this->getUsed() > 0) return 3;	//('Verbraucht', 'wpsg');
        		else
        		{
        			
        			if (current_time('timestamp') < strtotime($this->start_date) || current_time('timestamp') > strtotime($this->end_date)) return 2; 	//('Inaktiv', 'wpsg');
        			else return 1; 	//('Aktiv', 'wpsg');
        			
        		}
        		
        	}
        	
        }
        
        public function delete()
        {

            $this->db->Query("DELETE FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` = '".wpsg_q($this->id)."' ");

        }

        /* Statische Funktionen */

		public static function count($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					(
						SELECT
						  	DISTINCT V.`id`
						FROM
							`".WPSG_TBL_GUTSCHEIN."` AS V
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

		} // public static function count($arFilter = array())

		/**
		 * Gibt einen Array von Kundengruppen zurück, die auf den übergebenen Filter passen
		 * @param array $arFilter
		 */
		public static function find($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strLimit = "";

			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);

			$strQuery = "
				SELECT
					V.`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_GUTSCHEIN."` AS V
						".$strQueryJOIN."
				WHERE
					1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."		
				
			";
			// ".$strLimit."
			
			$arID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
			$arReturn = array();

			$cnt = array();
			$cnt[0] = 0;	// Alle
			$cnt[1] = 0;	// Aktiv
			$cnt[2] = 0;	// Inaktiv
			$cnt[3] = 0;	// Verbraucht
			$cnt[4] = 0;	// Wartend
			
			$start = 0;		// Vergleich mit $arFilter['limit'][0]
			$cntx = 0;		// Vergleich mit $arFilter['limit'][1]
			foreach ($arID as $id)
			{

				$vv = self::getInstance($id);
				$st = $vv->getStatus();
				if (isset($arFilter['status'])) {
					
					$cnt[0]++;
					$cnt[$st]++;
					
					if (($arFilter['status'] == $st) || ($arFilter['status'] == 0)) {
						if ($start >= $arFilter['limit'][0]) {
							if ($cntx >= $arFilter['limit'][1]) continue;
							$arReturn[$id] = self::getInstance($id);
							$cntx++;
						}
						$start++;
					}
					
				}
				
			}

			$arReturn['counts'] = $cnt;
			
			return $arReturn;


		} // public static function find($arFilter = array())

		public static function getQueryParts($arFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryWHERE = "";
			$strQueryJOIN = "";
			$strQueryHAVING = "";
			$strQueryORDER = "";

 			if (wpsg_isSizedString($arFilter['s'])) $strQueryWHERE .= " AND V.`code` LIKE '%".wpsg_q($arFilter['s'])."%' ";

			if (wpsg_isSizedString($arFilter['code'])) $strQueryWHERE .= " AND V.`code` LIKE '%".wpsg_q($arFilter['code'])."%' ";

			if (wpsg_isSizedString($arFilter['order'], 'code')) { $strQueryORDER = " V.`code` "; }
            else if (wpsg_isSizedString($arFilter['order'], 'start_date')) { $strQueryORDER = " V.`start_date` "; }
            else if (wpsg_isSizedString($arFilter['order'], 'end_date')) { $strQueryORDER = " V.`end_date` "; }
            else if (wpsg_isSizedString($arFilter['order'], 'value')) { $strQueryORDER = " V.`value` "; }
			else $strQueryORDER = " V.`id` ";

			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";

			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);

		}

	} // class wpsg_voucher

?>