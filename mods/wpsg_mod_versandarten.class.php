<?php

	/**
	 * Dieses Modul ermöglicht eine Verwaltung von Versandkosten
	 * @author Daschmi (daniel@maennchen1.de)
	 */
	class wpsg_mod_versandarten extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 6;
		var $arTypen = array();
		var $hilfeURL = 'http://wpshopgermany.de/?p=287';		
		var $inline = true;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Versandarten', 'wpsg');
			$this->group = __('Versand', 'wpsg');
			$this->desc = __('Ermöglicht die Verwaltung von Versandkosten/Lieferanten.', 'wpsg');
												
		} // public function __construct()
		
		public function init()
		{

			$this->arTypen['w'] =__('Bestellwert', 'wpsg'); 
			$this->arTypen['s'] = __('Menge', 'wpsg');
			
			if ($this->shop->hasMod('wpsg_mod_weight'))
			{
				$this->arTypen['g'] =__('Gewicht', 'wpsg'); 
			}			
			  			
		} // public function init()
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/**
			 * Tabelle für die Lieferanten
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_VA." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,				
				name VARCHAR(255) NOT NULL,
				vz INT(11) NOT NULL,
				hint TEXT NOT NULL,
				mwst_key varchar(1) NOT NULL, 
				mwst_laender INT(1) NOT NULL,	 
				typ ENUM('w', 's', 'g') NOT NULL,
				kosten LONGTEXT NOT NULL,
				kosten_plz TEXT NOT NULL,
				deleted INT(1) NOT NULL,
				aktiv INT(1) NOT NULL,
			  	PRIMARY KEY  (id),
			  	KEY vz (vz)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
		   	dbDelta($sql);
			
		} // public function install()
		
		public function settings_edit()
		{
			
			$this->shop->mod = $this;
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_versandarten/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function be_ajax()
		{
			
			if ($_REQUEST['do'] == 'add')
			{
				
				$new_name = __('Anklicken um den Namen der Versandart zu ändern ...', 'wpsg');
				
				// Versandzone in Datenbank eintragen
				$va_id = $this->db->ImportQuery(WPSG_TBL_VA, array(
					'name' => wpsg_q($new_name),
					'aktiv' => '1',
					'mwst_key' => 'c',
					'typ' => 'w'					
				));
				
				$this->shop->addTranslationString('wpsg_mod_versandarten_'.$va_id, $new_name);
				
				die($this->va_list());
				
			}
			else if ($_REQUEST['do'] == 'remove') {

				if (wpsg_checkInput($_REQUEST['va_id'], WPSG_SANITIZE_INT)) {
				
					$this->db->UpdateQuery(WPSG_TBL_VA, array(
						"deleted" => "1"
					), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
				
				}

				die($this->va_list());
				
			} else if ($_REQUEST['do'] == 'inlinedit') {

				if (wpsg_checkInput($_REQUEST['va_id'], WPSG_SANITIZE_INT)) {
								
					if ($_REQUEST['field'] == 'name') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_TEXTFIELD)) {
							
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'name' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
						
							$this->shop->addTranslationString('wpsg_mod_versandarten_'.$_REQUEST['va_id'], $_REQUEST['value']);
						
							die($_REQUEST['value']);
							
						} else die(__('Ungültige Eingabe!', 'wpsg'));
						
					} else if ($_REQUEST['field'] == 'typ') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_VALUES, array_keys($this->arTypen))) {
						
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'typ' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							die($this->arTypen[$_REQUEST['value']]);
							
						} else die(__('Ungültige Eingabe!', 'wpsg'));
						
					} else if ($_REQUEST['field'] == 'hint') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_TEXTAREA)) {
								
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'hint' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							$this->shop->addTranslationString('wpsg_mod_versandarten_hint_'.$_REQUEST['va_id'], $_REQUEST['value']);
													
							die(wpsg_hspc($_REQUEST['value']));
							
						} else die(__('Ungültige Eingabe!', 'wpsg'));
						
					} else if ($_REQUEST['field'] == 'vz') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_INT, ['allow' => ['0']])) {
							
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'vz' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							if ($_REQUEST['value'] > 0) die($this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_VZ."` WHERE `id` = '".wpsg_q($_REQUEST['value'])."'"));
							else die(__('Alle Versandzonen', 'wpsg'));
							
						} else die(__('Ungültige Eingabe!', 'wpsg'));
							
					} else if ($_REQUEST['field'] == 'mwst_key') {
	
						if (!wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_TAXKEY)) {
							
							die(__('Ungültige Eingabe', 'wpsg'));
							
						} else {
						
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'mwst_key' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							$tax_groups = wpsg_tax_groups();
							die(wpsg_hspc($tax_groups[$_REQUEST['value']]));
							
						}
											
					} else if ($_REQUEST['field'] == 'kosten') {
	
						if (!wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_COSTKEY)) {
							
							die(__('Ungültige Eingabe', 'wpsg'));
							
						} else {
						
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'kosten' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
												
							die($_REQUEST['value']);
							
						} 
						
					} else if ($_REQUEST['field'] == 'kosten_plz') {
	
						if (!wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_TEXTAREA)) {
							
							die(__('Ungültige Eingabe', 'wpsg'));
							
						} else {
						 
							$strKosten = $this->db->fetchOne("SELECT `kosten_plz` FROM ".WPSG_TBL_VA." WHERE `id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							if (!empty($strKosten)) { $arPLZkosten = unserialize($strKosten); }
			
							$arPLZkosten[$_REQUEST['key']] = wpsg_q($_REQUEST['value']);
		
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'kosten_plz' => serialize($arPLZkosten)
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							die($_REQUEST['value']);
							
						}
							
					} else if ($_REQUEST['field'] == 'mwst_laender') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_CHECKBOX)) {
						
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'mwst_laender' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							die();
							
						} else die(__('Ungültige Eingabe', 'wpsg'));
						
					} else if ($_REQUEST['field'] == 'aktiv') {
	
						if (wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_CHECKBOX)) {
								
							$this->db->UpdateQuery(WPSG_TBL_VA, array(
								'aktiv' => wpsg_q($_REQUEST['value'])
							), "`id` = '".wpsg_q($_REQUEST['va_id'])."'");
							
							die();
							
						} else die(__('Ungültige Eingabe', 'wpsg'));
						
					}
					
				}
				
			}
			
		} // public function be_ajax()
		
		public function addShipping(&$arShipping, $va_active = false) {
			 
			if (!wpsg_is_admin() && !$va_active) {
			 
				$land = false;
				
				if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['shipping_land'])) $land = $_SESSION['wpsg']['checkout']['shipping_land'];
				else if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['land'])) $land = $_SESSION['wpsg']['checkout']['land'];
				
				$strQueryWHERE = "";
				
				if ($land !== false) {
					
					$vz = $this->db->fetchOne("SELECT `vz` FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($land)."'");
					
					$strQueryWHERE .= " AND (`vz` <= 0 OR `vz` = '".wpsg_q($vz)."') ";
					
				} else $strQueryWHERE .= " AND (`vz` <= 0) ";
								 				
				$strQuery = "
					SELECT 
						* 
					FROM 
						`".WPSG_TBL_VA."` 
					WHERE					 
						`deleted` != '1'
						".$strQueryWHERE." 
					ORDER BY 
						`name` ASC
				";
				
				$arVersandarten = $this->db->fetchAssoc($strQuery);
				 								
			} else {
				
				$arVersandarten = $this->db->fetchAssoc("
					SELECT * FROM `".WPSG_TBL_VA."` WHERE `deleted` != '1' ORDER BY `name` ASC
				");
				
			}

			foreach ($arVersandarten as $va) {
				
				if (!is_admin() && $va['aktiv'] != '1') continue;

				$va = $this->getVaKosten($arBasket, $va);
				
				$hint = $va['hint'];
				
				$arShipping[$this->id.'_'.$va['id']] = array(
					'active' => $va['aktiv'],
					'id' => $this->id.'_'.$va['id'],
					'va_id' => $va['id'],
					'name' => __($va['name'], 'wpsg'),
					'tax_key' => $va['mwst_key'],
					'hint' => __($hint, 'wpsg'),					
					'mwst_null' => $va['mwst_laender'],
					'deleted' => $va['deleted'],
					'price' => $va['typ'].'-'.$va['kosten'],
				);
				
				if (!empty($va['plz'])) $arShipping[$this->id.'_'.$va['id']]['plz'] = __($va['plz'], 'wpsg');
		 								
			}
 
		} // public function addShipping(&$arShipping)
				
		public function checkCheckout(&$state, &$error, &$arCheckout) 
		{ 
			
			/* Prüfung, ob arShipping != leer */
			$bOK = true;
			
			if (!empty($this->shop->arShipping)) {
			
				$bOK = false;
				
				// Alle möglichen Versandarten durchgehen und schauen ob es eine PLZ Beschränkung gibt
				foreach ($this->shop->arShipping as $shipping_key => $shipping)
				{
									
					$bOKShipping = true;
					
					if (preg_match('/^'.$this->id.'_\d+/', $shipping_key))
					{
					
						$va_id = preg_replace('/^'.$this->id.'_/', '', $shipping_key);				
						$va = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_VA."` WHERE `id` = '".wpsg_q($va_id)."' ");
						
						$kosten_plz = @unserialize($va['kosten_plz']);
										
						if (wpsg_isSizedInt($va['vz']) && wpsg_isSizedArray($kosten_plz)) 
						{
							
							$arPLZ = $this->getDefinedPLZAreas($va['vz']);
								
							foreach ($arPLZ as $plz_index => $plz)
							{
								
								$arPLZ = wpsg_trim(explode(',', $plz));
								
								if ($kosten_plz[$plz_index] === 'noshipping' && in_array($arCheckout['plz'], $arPLZ))
								{
									
									$bOKShipping = false;
									
								}
														
							}
							
						}
						
					}
					
					$bOK = $bOK || $bOKShipping;
					
					// Sobald eine Ok ist, kann ich aufhören zu schleifen :D
					if ($bOK === true) break;
					
				}
				
				if ($bOK === false)
				{
								
					$error = true;
					$this->shop->addFrontendError(__('Ein Versand in dieses Postleitzahlengebiet ist nicht möglich.'));
												
				}
				
			}
						
		} // public function checkCheckout(&$state, &$error, &$arCheckout)
		
		/**
		 * Berechnet die Kosten anhand des Kostenschlüssels für den Warenkorb
		 */
		private function calculatePreis($va, &$basket)
		{
			
			$kosten = $va['kosten'];
			$typ = $va['typ'];
			
			if ($typ == 's') // Menge
			{

				$value = 0;
				
				// Neu: Ich muss die Menge der Produkte die für diese Versandart erlaubt sind zählen
				foreach ((array)$basket['produkte'] as $p)
				{
					
					$wpsg_product = wpsg_product::getInstance($this->shop->getProduktId($p['id']));
					
					if (!$wpsg_product->hasLimitedShipping() || in_array($this->id.'_'.$va['id'], $wpsg_product->getAllowedShipping())) $value += $p['menge'];
					
				}
					
			}
			else if ($typ == 'g') // Gewicht
			{
				
				$value = 0;
				
				foreach ((array)$basket['produkte'] as $p)
				{
					
					$wpsg_product = wpsg_product::getInstance($this->shop->getProduktID($p['id']));
					
					if (!$wpsg_product->hasLimitedShipping() || in_array($this->id.'_'.$va['id'], $wpsg_product->getAllowedShipping())) $value += $p['weight'];
					
				}
				
			}
			else if ($typ == 'w') // Bestellwert
			{
				
				$value = 0;
				
				foreach ((array)$basket['produkte'] as $p)
				{
					
					$wpsg_product = wpsg_product::getInstance($this->shop->getProduktID($p['id']));
					
					if (!$wpsg_product->hasLimitedShipping() || in_array($this->id.'_'.$va['id'], $wpsg_product->getAllowedShipping())) $value += $p['preis'] * $p['menge'];
					
				}
				
			}
					
			$arKosten = explode('|', $kosten);
			if (sizeof($arKosten) == 1) $kosten = $arKosten[0];
			else 
			{
				
				$arKosten = array_reverse($arKosten);

				foreach ($arKosten as $k)
				{		
						
					$arP = explode(":", $k);
					 
					if (sizeof($arP) == 1) $kosten = $arP[0];
					else if (wpsg_tf($arP[0]) <= $value) $kosten = $arP[1];
										
				}
				
			}			
			
			return wpsg_tf($kosten);
			
		} // private function calculatePreis($kosten, &$basket)
		
		/**
		 * Zeichnet die Liste der Versandarten
		 */
		public function va_list()
		{
			
			$this->shop->view['data'] = $this->db->fetchAssoc("
				SELECT
					VA.*
				FROM	
					`".WPSG_TBL_VA."` AS VA
				WHERE
					`deleted` != '1'
				ORDER BY
					VA.`id`
			");
			
			$this->shop->view['arTypen'] = $this->arTypen;						
			$this->shop->view['arVZ'] = wpsg_array_merge(array('0' => array('name' => __('Alle Versandzonen', 'wpsg'))), $this->db->fetchAssoc("
				SELECT
					VZ.`id`, VZ.`name`
				FROM
					`".WPSG_TBL_VZ."` AS VZ
				ORDER BY 
					VZ.`name`
			", "id")); 
			  
			foreach ($this->shop->view['arVZ'] as $k => $vz)
			{
				 
				if ($k > 0)
				{
				 			 
					// Unterteilung nach PLZ Gebieten? 
					$this->shop->view['arVZ'][$k]['arPLZ'] = $this->getDefinedPLZAreas($vz['id']);
									
				}
				
			}
			
			foreach ($this->shop->view['data'] as $k => $v)
			{
				
				$this->shop->view['data'][$k]['kosten_plz'] = @unserialize($v['kosten_plz']);
				if (!wpsg_isSizedArray($this->shop->view['data'][$k]['kosten_plz'])) $this->shop->view['data'][$k]['kosten_plz'] = array();
				
			}
				 	
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_versandarten/list.phtml', false);
			
		} // public function va_list()
		
		/**
		 * Gibt einen Array mit definierten PLZ Gebieten zurück aus der Konfiguration der Versandzone
		 */
		public function getDefinedPLZAreas($vz_id)
		{
			
			$arPLZ = wpsg_trim(preg_split("/\r|\n/", $this->db->fetchOne("
				SELECT
					`param`
				FROM
					`".WPSG_TBL_VZ."` 
				WHERE
					`id` = '".wpsg_q($vz_id)."'
			")));
			
			$arReturn = array();
			
			$row = 1;
			foreach ((array)$arPLZ as $k => $v)
			{
				
				if (preg_match('/^(.+)\|(.+)$/', $v))
				{
					
					$id = preg_replace('/\|(.*)$/', '', $v);
					$value = preg_replace('/(.*)\|/', '', $v);
					 
				}
				else
				{
					
					$id = $row;
					$value = $v;
					
				}
				
				$arReturn[$id] = $value;
				
				$row ++;
				
			}
			
			return $arReturn;
			
		} // private function getDefinedPLZAreas($vz_id)
		
		/**
		 * Gibt den Kostenschlüssel für einen Warenkorb und einer Versandart zurück
		 * Greift dabei auf die PLZ Unterteilung zurück
		 */
		private function getVaKosten(&$arBasket, $va)
		{
			
			if ($va['vz'] > 0)
			{
				
				// Alle PLZ Gebiete der Zone laden
				$arPLZ = $this->getDefinedPLZAreas($va['vz']);
				$kosten_plz = @unserialize($va['kosten_plz']);
				if (!wpsg_isSizedArray($kosten_plz)) $kosten_plz = array();
				 
				$arPLZ_flatten = array();
				 
				// Unterteilungen auftrennen
				foreach ($arPLZ as $k => $v)
				{
					
					$arPLZ[$k] = wpsg_trim(explode(',', $v));
					
					foreach ($arPLZ[$k] as $k2 => $v2) 
					{
						
						// Nur zuordnen wenn auch Kosten angegben wurden
						if (wpsg_isSizedString($kosten_plz[$k])) $arPLZ_flatten[$v2] = $k;
						
					}
					
				} 
 
				uksort($arPLZ_flatten, array($this, 'sortPLZ'));			
				$arPLZ_flatten = array_reverse($arPLZ_flatten, TRUE);
				
				foreach ($arPLZ_flatten as $plz => $value)
				{
				
					if (strpos($arBasket['checkout']['plz'], (string)$plz) === 0)
					{
						
						$va['kosten'] = $kosten_plz[$value];				
						$va['plz'] = $plz; 
											
						return $va;
						
					}
					
				}
				
				return $va;
				
			}
			else
			{

				// Versandart für alle Zonen
				return $va;
				
			}
			
		}
		
		/**
		 * Sortiert die Postleitzahlen nach der Länge (Hilfsfunktion)
		 */
		private function sortPLZ($a, $b)
		{
			
			if (strlen($a) == strlen($b)) return 0;
			
			return ((strlen($a) < strlen($b))?-1:1);
			
		} // private function sortPLZ($a, $b)
		
	} // class wpsg_mod_versandarten extends wpsg_mod_basic

