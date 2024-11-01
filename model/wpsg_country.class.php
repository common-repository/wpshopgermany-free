<?php

	/**
	 * Klasse die ein Land repräsentiert
	 * @author daniel
	 */
	class wpsg_country extends wpsg_model 
	{
		
		/**
		 * Lädt die Daten eines Landes
		 */
		public function load($country_id)
		{
			
			parent::load($country_id);
			
			$this->data = $this->db->fetchRow("SELECT L.* FROM `".WPSG_TBL_LAND."` AS L WHERE L.`id` = '".wpsg_q($country_id)."' ");
			
			if ($this->data['id'] != $country_id || !wpsg_isSizedInt($this->data['id']))
			{
				
				return false;
				
			}
			
			return true;
				
		} // public function load($deliverynote_id)

		/**
		 * Gibt das Kuerzel des Landes zurück
		 * @return mixed|null
		 */
		public function getShorttext()
		{
				
			return __($this->kuerzel, 'wpsg');
				
		} // public function getShorttext()
		
		/**
		 * Löscht das Land
		 */
		public function delete()
		{

			$this->db->Query("DELETE FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->id)."' ");

		} // public function delete()
		
		/**
		 * Gibt den Namen des Landes in der aktuellen Sprache zurück
		 */
		public function getName()
		{
			
			return __($this->name, 'wpsg');
			
		} // public function getName()
		
		public function getTax($tax_group)
		{						

			if ($tax_group == 'e' || !$this->isLoaded() || !array_key_exists('mwst_'.$tax_group, $this->data)) return 0;
								
			return $this->data['mwst_'.$tax_group];
						
		} // public function getTax($tax_group)
		
		public static function find($arFilter = array())
		{
			
			$arReturn = array();
			
			$strQueryWHERE = "";
			
			if (wpsg_isSizedInt($arFilter['vz']))
			{
								
				$strQueryWHERE .= " AND L.`vz` = '".wpsg_q($arFilter['vz'])."' ";
				
			}
			
			if (wpsg_isSizedArray($arFilter['vz']))
			{
				
				$strQueryWHERE .= " AND L.`vz` IN (".implode(',', wpsg_q($arFilter['vz'])).") ";
				
			}
			
			$strQuery = "
				SELECT
					L.`id`
				FROM
					`".WPSG_TBL_LAND."` AS L
				WHERE
					1
					".$strQueryWHERE."	
				ORDER BY
					L.`name`
			";
			
			$arIDs = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
			
			foreach ($arIDs as $id)
			{
				
				$land = new wpsg_country();
				$land->load($id);
				
				$arReturn[] = $land;
				
			}
			
			return $arReturn;
			
		} // public static function find($arFilter = array())
		
		public static function getCountryIDFromCode($code)
		{
			
			$id = $GLOBALS['wpsg_db']->fetchOne("SELECT `id` FROM `".WPSG_TBL_LAND."` WHERE LOWER(`kuerzel`) = LOWER('".wpsg_q($code)."') ");
			
			if (wpsg_isSizedInt($id)) return $id;
			else return false;
			
		} // public static function getCountryIDFromCode($code)
		
	} // class wpsg_country extends wpsg_model 

?>