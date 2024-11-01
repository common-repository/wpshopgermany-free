<?php

	/**
	 * Dieses Modul erlaubt Bestellbedingungen zu definieren
	 * @author Daschmi
	 */
	class wpsg_mod_ordercondition extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 951; 
		var $hilfeURL = 'http://wpshopgermany.de/?p=13232';
						
		var $inline = true;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Bestellbedingungen', 'wpsg');
			$this->group = __('Bestellung', 'wpsg');
			$this->desc = __('Ermöglicht das Abfragen von Bedingungen zu Bestellungen oder einzelnen bestellten Produkten.', 'wpsg');
												
		} // public function __construct()
						
		public function install() 
		{ 
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			$sql = "CREATE TABLE ".WPSG_TBL_ORDERCOND." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name VARCHAR(255) NOT NULL,
		   		typ INT(1) NOT NULL,
		   		text TEXT NOT NULL, 
				errortext TEXT NOT NULL,
		   		deleted INT(1) NOT NULL,
		   		shipping TEXT NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
		   		wpsg_mod_ordercondition VARCHAR(255) NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
			$id_exists_1 = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_ORDERCOND."` WHERE `id` = '1' ");
			
			if ($id_exists_1 != "1")
			{
				
				$name = __('AGB + Widerrufsbelehrung', 'wpsg');				
				$text = __('Ich habe die <a href="%agb_url%" target="_blank">AGB</a> des Anbieters gelesen und erkläre mit dem Absenden der Bestellung mein Einverständnis. Die <a href="%widerruf_url%" target="_blank">Widerrufsbelehrung</a> habe ich zur Kenntnis genommen.', 'wpsg');
				$errortext = __('Sie müssen unsere <a href="%agb_url%" target="_blank">AGB</a> und <a href="%widerruf_url%" target="_blank">Widerrufsbelehrung</a> akzeptieren.', 'wpsg');
				
				$this->db->ImportQuery(WPSG_TBL_ORDERCOND, array(
					'id' => '1',
					'name' => wpsg_q($name),
					'typ' => '1',
					'text' => wpsg_q($text),
					'errortext' => wpsg_q($errortext),
					'deleted' => '0'
				));
				
				$this->shop->addTranslationString('wpsg_mod_ordercondition_1', $name);
				$this->shop->addTranslationString('wpsg_mod_ordercondition_text_1', $text);
				$this->shop->addTranslationString('wpsg_mod_ordercondition_errortext_1', $errortext);
				
			}
			
			$id_exists_2 = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_ORDERCOND."` WHERE `id` = '2' ");
			
			if ($id_exists_2 != "2")
			{
				
				$name = __('Bestimmungen zu Dienstleistungen', 'wpsg');
				$text = __('Ich bin einverstanden, dass Sie vor Ende der Widerrufsfrist mit der Ausführung der beauftragten Dienstleistung beginnen. Mir ist bekannt, dass ich im Falle des Widerrufs Wertersatz für die bereits erbrachten Dienstleistungen leisten muss. Ich stimme zu, dass der Vertrag von beiden Seiten vollständig erfüllt wird, bevor ich mein Widerrufsrecht ausgeübt habe. Das Widerrufsrecht erlischt in diesem Fall vorzeitig.', 'wpsg');
				$errortext = __('Sie müssen die Bestimmungen für Dienstleistungen akzeptieren.', 'wpsg');
				
				$this->db->ImportQuery(WPSG_TBL_ORDERCOND, array(
					'id' => '2',
					'name' => wpsg_q($name),
					'typ' => '2',
					'text' => wpsg_q($text),
					'errortext' => wpsg_q($errortext),
					'deleted' => '0'
				));
				
				$this->shop->addTranslationString('wpsg_mod_ordercondition_2', $name);
				$this->shop->addTranslationString('wpsg_mod_ordercondition_text_2', $text);
				$this->shop->addTranslationString('wpsg_mod_ordercondition_errortext_2', $errortext);
				
			}

			// Wenn Modul einmal installiert, dann ist diese Meldung hinfällig
			$this->shop->update_option('wpsg_message_ordercondition_34', '1');
			
		} // public function install() 
		
		public function settings_edit()
		{
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordercondition/settings_edit.phtml');
			
		} // public function settings_edit()

		public function be_ajax()
		{
			
			if ($_REQUEST['do'] == 'add')
			{
				
				$new_name = __('Anklicken um den Namen der Bestellbedingung zu ändern ...', 'wpsg');
				
				// Versandzone in Datenbank eintragen
				$oc_id = $this->db->ImportQuery(WPSG_TBL_ORDERCOND, array(
					'name' => wpsg_q($new_name),
					'typ' => '1'
				));
				
				$this->shop->addTranslationString('wpsg_mod_ordercondition_'.$oc_id, $new_name);
				
				die($this->oc_list());
				
			}
			else if ($_REQUEST['do'] == 'remove')
			{
				
				$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array("deleted" => "1"), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
				
				die($this->oc_list());
				
			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{

				if ($_REQUEST['field'] == 'name')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);
					$_REQUEST['oc_id'] = wpsg_sinput("key", $_REQUEST['oc_id']);

					$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array(
						'name' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
					
					$this->shop->addTranslationString('wpsg_mod_ordercondition_'.$_REQUEST['oc_id'], $_REQUEST['value']);
					
					die($_REQUEST['value']);
					
				}
				else if ($_REQUEST['field'] == 'shipping')
				{
					
					$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array(
						'shipping' => wpsg_q(implode(',', $_REQUEST['value']))
					), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
					
					die($_REQUEST['value']);
					
				}
				else if ($_REQUEST['field'] == 'typ')
				{
					
					$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array(
						'typ' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
					
					die($_REQUEST['value']);
					
				}
				else if ($_REQUEST['field'] == 'text')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);
					$_REQUEST['oc_id'] = wpsg_sinput("key", $_REQUEST['oc_id']);

					if ($this->shop->get_option('wpsg_options_nl2br') == '1') $_REQUEST['value'] = nl2br($_REQUEST['value']);
					
					$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array(
						'text' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
					
					$this->shop->addTranslationString('wpsg_mod_ordercondition_text_'.$_REQUEST['oc_id'], $_REQUEST['value']);
					
					die($_REQUEST['value']);
					
				}
				else if ($_REQUEST['field'] == 'errortext')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);
					$_REQUEST['oc_id'] = wpsg_sinput("key", $_REQUEST['oc_id']);

					if ($this->shop->get_option('wpsg_options_nl2br') == '1') $_REQUEST['value'] = nl2br($_REQUEST['value']);
						
					$this->db->UpdateQuery(WPSG_TBL_ORDERCOND, array(
						'errortext' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['oc_id'])."'");
						
					$this->shop->addTranslationString('wpsg_mod_ordercondition_errortext_'.$_REQUEST['oc_id'], $_REQUEST['value']);
						
					die($_REQUEST['value']);
					
				}
				
			}
			
		} // public function be_ajax()
		
		public function produkt_edit_sidebar(&$produkt_data)
		{

			// Nur für angelegte Produkte
			if ($produkt_data['id'] <= 0 || isset($_REQUEST['wpsg_lang'])) return false;
			 					 
			$this->shop->view['wpsg_mod_ordercondition']['data'] = $this->db->fetchAssoc("
				SELECT
					OC.*,
					IF (FIND_IN_SET(OC.`id`, (SELECT P.`wpsg_mod_ordercondition` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`id` = '".wpsg_q($produkt_data['id'])."')), '1', '0') AS `selected` 
				FROM
					`".WPSG_TBL_ORDERCOND."` AS OC
				WHERE
					OC.`deleted` != '1' AND
					OC.`typ` = '2'											
			"); 
			
			if (!wpsg_isSizedArray($this->shop->view['wpsg_mod_ordercondition']['data'])) return;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordercondition/produkt_edit_sidebar.phtml');
				
		} // public function produkt_edit_sidebar(&$produkt_data)
		
		public function produkt_save(&$produkt_id) 
		{ 
			
			$arSave = array();
			
			if (wpsg_isSizedArray($_REQUEST['wpsg_mod_ordercondition']))
			{

				foreach ($_REQUEST['wpsg_mod_ordercondition'] as $oc_id => $oc_value)
				{
					
					if ($oc_value === '1') $arSave[] = wpsg_sinput("key", $oc_id);
					
				}
			
			}
			
			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array('wpsg_mod_ordercondition' => wpsg_q(implode(',', $arSave))), "`id` = '".wpsg_q($produkt_id)."'");
			
		} // public function produkt_save(&$produkt_id)
		
		public function overview_top(&$arBasket) 
		{ 
			 
			$this->shop->view['wpsg_mod_ordercondition']['data'] =  $this->loadOrderConditionsByBasket($arBasket);
						
			if (sizeof($this->shop->view['wpsg_mod_ordercondition']['data']) > 0)
			{
				
				foreach ($this->shop->view['wpsg_mod_ordercondition']['data'] as $k => $v)
				{
					
					$this->shop->view['wpsg_mod_ordercondition']['data'][$k]['name'] = __($this->shop->view['wpsg_mod_ordercondition']['data'][$k]['name'], 'wpsg');
					$this->shop->view['wpsg_mod_ordercondition']['data'][$k]['text'] = __($this->shop->view['wpsg_mod_ordercondition']['data'][$k]['text'], 'wpsg');
					$this->shop->view['wpsg_mod_ordercondition']['data'][$k]['text'] = $this->shop->replaceUniversalPlatzhalter($this->shop->view['wpsg_mod_ordercondition']['data'][$k]['text']);
					
				}
				
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordercondition/overview_top.phtml');
				
			}
						
		} // public function overview_top(&$arBasket)
		
		public function checkFinaly(&$error) 
		{ 
			
			// Ist das Modul CrefoPay aktiv, so wird nichts geprüft
			if ($this->shop->hasMod('wpsg_mod_crefopay')) return;

			$arOC = $this->loadOrderConditionsByBasket($this->shop->basket->toArray());
			
			if (wpsg_isSizedArray($arOC))
			{
				
				foreach ($arOC as $oc)
				{
					
					if (!wpsg_isSizedArray($_REQUEST['wpsg_mod_ordercondition']) || $_REQUEST['wpsg_mod_ordercondition'][$oc['id']] != '1')
					{
						
						$error = true;
						$this->shop->addFrontendError($this->shop->replaceUniversalPlatzhalter(__($oc['errortext'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = 'row-check-agb-'.$oc['id'];
											}
					
				}
				
			} 
			
		} // public function checkFinaly(&$error) 
		
		public function checkGeneralBackendError() 
		{
		
			$nOrderCondition = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDERCOND."` WHERE `deleted` != '1' ");
			
			if (!wpsg_isSizedInt($nOrderCondition))
			{
			
				$this->shop->addBackendError('nohspc_'.wpsg_translate(
            		__('Sie haben das Modul "Bestellbedingungen" aktiviert, es sind aber keine Bestellbedingungen definiert. Überprüfen Sie die <a href="#1#">Modulkonfiguration</a>, ihr Shop ist möglicherweise nicht rechtssicher.', 'wpsg'),
					WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_ordercondition'
				), 'wpsg_message_ordercondition');
				
			}
			
		} // public function checkGeneralBackendError()
		
		/** Modulfunktionen */
		
		/**
		 * Lädt einen Array mit den Bestellbedingungen für die Bestellung des übergebenen Basket Arrays
		 */
		public function loadOrderConditionsByBasket($arBasket)
		{
			
			$arOC_products = array();
			
			// IDs der Bestellbedingungen die Produktabhängig sind ermitteln
			foreach ($arBasket['produkte'] as $p)
			{
				
				if (preg_match('//', $p['id'])) $produkt_id = preg_replace('/(^pv\_)|\|\d+\:\d+$/', '', $p['id']);
				else if (is_int($p['id'])) $produkt_id = $p['id'];
				
				$produkt_db = $this->shop->cache->loadProduct($produkt_id);
				
				$arOC_products = wpsg_array_merge($arOC_products, explode(",", $produkt_db['wpsg_mod_ordercondition']));
				
			}
			
			$arOC_products = array_unique($arOC_products);
			
			foreach ($arOC_products as $k => $v) if (trim($v) == '') unset($arOC_products[$k]);
			
			return $this->db->fetchAssoc("
				SELECT
					OC.*
				FROM
					`".WPSG_TBL_ORDERCOND."` AS OC
				WHERE
					OC.`deleted` != '1' AND
					(
						(OC.`typ` = '1') OR 
						(OC.`typ` = '2' AND FIND_IN_SET(OC.`id`, '".implode(",", $arOC_products)."')) OR
						(OC.`typ` = '3' AND FIND_IN_SET('".wpsg_q($arBasket['checkout']['shipping'])."', OC.`shipping`))
					)
			");
			
		} // public function loadOrderConditionsByBasket($arBasket)
		
		/**
		 * Zeichnet fürs Backend die Liste der Bestellbedingungen
		 */
		public function oc_list()
		{
			
			$this->shop->view['wpsg_mod_ordercondition']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERCOND."` WHERE `deleted` != '1'");
			$this->shop->view['arShipping'] = array();
			
			foreach ($this->shop->arShipping as $k => $v)
			{
			
				$this->shop->view['arShipping'][$v['id']] = $v['name'];
				
			}
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordercondition/list.phtml');
			
		} // public function oc_list()
		 
	} // class wpsg_mod_ordercondition extends wpsg_mod_basic

?>