<?php

	/**
	 * Modul zur Erfassung von Kundeneingaben zur Bestellung
	 * @author daschmi (daniel@maennchen1.de)
	 */
	class wpsg_mod_ordervars extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 950;
		var $hilfeURL = 'http://wpshopgermany.de/?p=794';				
		var $inline = true;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Bestellvariablen', 'wpsg');
			$this->group = __('Bestellung', 'wpsg');
			$this->desc = __('Ermöglicht das Erfassen von Kundeneingaben zu Bestellungen.', 'wpsg');
			
			$this->arTypen = array(
				1 => __('Auswahl', 'wpsg'),
				2 => __('Texteingabe', 'wpsg'),
				3 => __('Checkbox', 'wpsg') 
			);
									
		} // public function __construct()
		 
		public function install() 
		{ 
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			$sql = "CREATE TABLE ".WPSG_TBL_ORDERVARS." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name VARCHAR(255) NOT NULL,
		   		typ VARCHAR(100) NOT NULL,
		   		auswahl VARCHAR(500) NOT NULL,
		   		pflicht INT(1) NOT NULL, 
		   		deleted INT(1) NOT NULL,
				pos int(11) NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
		} // public function install() 
		 		
		public function settings_edit() 
		{
			
			$this->shop->mod = $this;
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function be_ajax()
		{
			
			$this->shop->mod = $this;
			
			if ($_REQUEST['do'] == 'add')
			{
				
				$new_name = __('Anklicken um den Namen der Bestellvariablen zu ändern ...', 'wpsg');
				$new_auswahl = __('Bitte zum Bearbeiten anklicken.', 'wpsg');
				
				// Versandzone in Datenbank eintragen
				$ov_id = $this->db->ImportQuery(WPSG_TBL_ORDERVARS, array(
					'name' => wpsg_q($new_name),
					'typ' => '2',
					'auswahl' => $new_auswahl,
					'pflicht' => ''					
				));
				
				$this->shop->addTranslationString('wpsg_mod_ordervars_'.$ov_id, $new_name);
				$this->shop->addTranslationString('wpsg_mod_ordervars_auswahl_'.$ov_id, $new_auswahl);
				
				die($this->ov_list());
				
			}
			else if ($_REQUEST['do'] == 'reorder')
			{
			
				parse_str($_REQUEST['wpsg_reorder'], $wpsg_reorder);
	 
				foreach ($wpsg_reorder['ov'] as $pos => $ov_id)
				{
						
					$this->db->UpdateQuery(WPSG_TBL_ORDERVARS, array(
						'pos' => wpsg_q($pos)
					), " `id` = '".wpsg_q($ov_id)."' ");
						
				}
			
				die("1");
			
			}
			else if ($_REQUEST['do'] == 'del')
			{
				
				$result = $this->db->Query("DELETE FROM `".WPSG_TBL_ORDERVARS."` WHERE `id` = '".wpsg_q($_REQUEST['ov_id'])."'");

				die($this->ov_list());
				
			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{
				
				$data = array();
				if ($_REQUEST['field'] == 'name') 
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

					$data['name'] = wpsg_q($_REQUEST['value']);
					$this->shop->addTranslationString('wpsg_mod_ordervars_'.$_REQUEST['ov_id'], $_REQUEST['value']);				
					$value = __($_REQUEST['value'], 'wpsg');
					
				}
				else if ($_REQUEST['field'] == 'pflicht') { $data['pflicht'] = wpsg_q($_REQUEST['value']); $value = $_REQUEST['value']; }
				else if ($_REQUEST['field'] == 'typ') { $data['typ'] = wpsg_q($_REQUEST['value']); $value = $this->arTypen[$_REQUEST['value']]; }
				else if ($_REQUEST['field'] == 'auswahl') { 
					
					$data['auswahl'] = wpsg_q($_REQUEST['value']);
					$this->shop->addTranslationString('wpsg_mod_ordervars_auswahl'.$_REQUEST['ov_id'], $_REQUEST['value']); 
					$value = $_REQUEST['value'];
					
					
				
				}
				
				$this->db->UpdateQuery(WPSG_TBL_ORDERVARS, $data, "`id` = '".wpsg_q($_REQUEST['ov_id'])."'");
				
				die($value);
				
			}
			
		} // public function be_ajax()
		
		public function checkCheckout(&$state, &$error, &$arCheckout)  
		{
			
			// Beim speichern des Profils nicht schauen
			if (isset($_REQUEST['wpsg_mod_kundenverwaltung_save'])) return;
			
			// Beim Registrieren eines Kunden nicht prüfen
			if (isset($_REQUEST['wpsg_mod_kundenverwaltung_register'])) return;
			
			// Verfügbare Bestellvariablen
			$arOV = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `deleted` != '1' ORDER BY `pos` ASC, `id` ASC ");
			
			foreach ($arOV as $ov)
			{
				
				if ($ov['pflicht'] == '1')
				{
					
					if ($ov['typ'] == '1') 
					{
						 
						// Auswahlfeld
						if ($_SESSION['wpsg']['wpsg_mod_ordervars'][$ov['id']] == '')
						{
					 
							$error = true;
							$this->shop->addFrontendError(wpsg_translate(__('Bitte treffen Sie im Feld #1# eine Auswahl.', 'wpsg'), $this->getNameById($ov['id'])));
							$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_ordervars_'.$ov['id']);
							
						}
						
					}
					else if ($ov['typ'] == '2')
					{
						
						// Textfeld
						if (trim($_SESSION['wpsg']['wpsg_mod_ordervars'][$ov['id']]) == '')
						{
							
							$error = true;
							$this->shop->addFrontendError(wpsg_translate(__('Bitte tragen Sie in das Feld #1# einen Wert ein.', 'wpsg'), $this->getNameById($ov['id'])));
							$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_ordervars_'.$ov['id']);
							
						}
						
					}
					else if ($ov['typ'] == '3')
					{
						
						// Checkbox
						if ($_SESSION['wpsg']['wpsg_mod_ordervars'][$ov['id']] <= 0)
						{
							
							$error = true;
							$this->shop->addFrontendError(wpsg_translate(__('Bitte aktivieren Sie das Feld #1#.', 'wpsg'), $this->getNameById($ov['id'])));
							$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_ordervars_'.$ov['id']);
							
						}
						
					}
					
				}
				
			}
			
		}
		
		public function order_ajax()
		{
		
			if (wpsg_isSizedString($_REQUEST['do'], 'inlinedit'))
			{

				$_REQUEST['order_id'] = wpsg_sinput("key", $_REQUEST['order_id']);

				$ov_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `id` = '".wpsg_q($_REQUEST['ov_id'])."' ");
				$arAuswahl = explode('|', $ov_db['auswahl']);

				if ($ov_db['typ'] == 1 && is_numeric($_REQUEST['value']))
				{
				
					$_REQUEST['value'] = wpsg_sinput("key", $arAuswahl[$_REQUEST['value']]);

				}
				else
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

				}
				
				$db_order = $this->shop->cache->loadOrder($_REQUEST['order_id']);
				$pvars = @unserialize($db_order['bvars']);
				$pvars[$_REQUEST['ov_id']] = $_REQUEST['value'];
		
				$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
					'bvars' => wpsg_q(@serialize($pvars))
				), " `id` = '".wpsg_q($_REQUEST['order_id'])."' ");
		
				if ($ov_db['typ'] == 1 && $_REQUEST['value'] == 'not_set') $_REQUEST['value'] = __('Keine Angabe', 'wpsg');
				else if ($ov_db['typ'] == 3)
				{
				
					if ($_REQUEST['value'] == '1') $_REQUEST['value'] = __('Ja', 'wpsg');
					else $_REQUEST['value'] = __('Nein', 'wpsg');
						
				}
				
				die($_REQUEST['value']);
		
			}
				
		}
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) { 
			
			if (isset($_SESSION['wpsg']['wpsg_mod_ordervars'])) {
					
				$db_data['bvars'] = serialize(@$_SESSION['wpsg']['wpsg_mod_ordervars']);
				
			}
			
		}
		 
		public function order_view($order_id, &$arSidebarArray)
		{

			$arOV_db = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `deleted` != '1' ORDER BY `pos` ASC, `id` ASC ");
			$arOV = array();

			foreach ($arOV_db as $k => $ov)
			{

				$arOV[$ov['id']] = wpsg_array_merge(array(
					'value' => $this->getValueByIdAndOrder($ov['id'], $order_id)
				), $ov);

				$arOV[$ov['id']]['auswahl'] = explode('|', $ov['auswahl']);

			}

			if (!wpsg_isSizedArray($arOV)) return;

			$this->shop->view['wpsg_mod_ordervars']['data'] = $arOV;

			$arSidebarArray[$this->id] = array(
				'title' => $this->name,
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/order_view_sidebar.phtml', false)
			);

		}

		public function clearSession() 
		{ 
			
			if ($this->shop->get_option('wpsg_afterorder') == '1')
			{
				
				unset($_SESSION['wpsg']['wpsg_mod_ordervars']);
				
			}
			
		}
		
		public function doCheckout() { 
			
			if (isset($_REQUEST['wpsg_mod_ordervars']) && is_array($_REQUEST['wpsg_mod_ordervars']))
			{
				
				$_SESSION['wpsg']['wpsg_mod_ordervars'] = wpsg_xss($_REQUEST['wpsg_mod_ordervars']);
				
			}
						
		}
		
		public function checkout_inner_prebutton(&$checkout_view) 
		{ 
						
			$this->shop->view['wpsg_mod_ordervars']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `deleted` != '1' ORDER BY `pos` ASC, `id` ASC ", "id");
			
			if (!wpsg_isSizedArray($this->shop->view['wpsg_mod_ordervars']['data'])) return;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/checkout_inner_prebutton.phtml');
			
		} // public function checkout_inner_prebutton(&$checkout_view) 
		
		public function mail_aftercalculation(&$order_id)
		{
			
			$arOV = $this->getOrderVarsByOrderID($order_id);
			
			if (!wpsg_isSizedArray($arOV)) return;
			
			$this->shop->view['wpsg_mod_ordervars']['data'] = $arOV;
			
			if ($this->shop->htmlMail === true)
			{
				
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/mail_html.phtml');
				
			}
			else
			{
			
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/mail.phtml');

			}
			
		} // public function mail_aftercalculation(&$order_id)
		
		public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false) 
		{ 
		
			if ($order_id !== false && $order_id > 0)
			{

				$arOrderVars = $this->getOrderVarsByOrderID($order_id);
				
				foreach ((array)$arOrderVars as $k => $v)
				{
					
					$arReplace['/%ov_'.$v['id'].'%/'] = $v['value'];
					
				}
				
			}
			
		} // public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false)
		
		public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend) 
		{ 
			
			if ($order_id > 0)
			{
				
				$arOrderVars = $this->getOrderVarsByOrderID($order_id);
				
				foreach ((array)$arOrderVars as $k => $v)
				{
					
					$arSend['ov_'.$v['id']] = $v['value'];
					
				}
				
			}
			
		} // public function notifyURL(&$url, &$produkt_key, &$mege, &$order_id, &$typ)
		
		/* Modulfunktionen */
		
		/**
		 * Gibt alle Bestellvariablen zurück
		 */
		public function getOrderVars()
		{
			
			$arOV = $this->db->fetchAssoc("
				SELECT
					OV.*
				FROM
					`".WPSG_TBL_ORDERVARS."` AS OV
				WHERE 
					`deleted` != '1'
				ORDER BY
					`pos` ASC, `id` ASC 
			");
			
			$arReturn = array();			
			
			foreach ($arOV as $ov_index => $ov)
			{
				
				$arOV[$ov_index]['name'] = __($ov['name'], 'wpsg');
				
				$arReturn[$ov['id']] = $arOV[$ov_index];
				
			}
			
			return $arReturn;
			
		} // public function getOrderVars()
		
		/**
		 * Gibt alle Bestellvariablen aus der Konfiguration zurück, gefüllt mit den Werten einer Bestellung
		 */
		public function getOrderVarsByOrderID($order_id)
		{
			
			$arOV = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_ORDERVARS."` WHERE `deleted` != '1' ORDER BY `pos` ASC, `id` ASC ");
			
			if (!wpsg_isSizedArray($arOV)) return false;
			
			$data = array();
			
			foreach ($arOV as $ov_id)
			{
				
				$data[] = array(
					'id' => $ov_id,
					'name' => $this->getNameById($ov_id),
					'value' => $this->getValueByIdAndOrder($ov_id, $order_id)
				);
				
			}
			
			return $data;
			
		} // public function getOrderVarsByOrderID($order_id)
		
		/**
		 * Gibt den Namen einer Bestellvariablen anhand ihrer ID zurück
		 */
		public function getNameById($ov_id)
		{
			
			return __($this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_ORDERVARS."` WHERE `id` = '".wpsg_q($ov_id)."'"), 'wpsg');
			
		} // public function getNameById($ov_id)

		/**
		 * Gibt den Wert einer Bestellvariable für eine ID und eine Bestell ID zurück
		 */
		public function getValueByIdAndOrder($ov_id, $order_id)
		{

			$order_data = $this->shop->cache->loadOrder($order_id);
			$ov_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `id` = '".wpsg_q($ov_id)."'");
			$arOV_order = @unserialize($order_data['bvars']);
			
			if (!isset($arOV_order[$ov_id])) return __('Keine Angabe', 'wpsg');
			
			if ($ov_db['typ'] == '1')
			{
				
				// Auswahl
				if ($arOV_order[$ov_id] == '')
				{
					return __('Keine Auswahl', 'wpsg');
				}  
				
			}
			else if ($ov_db['typ'] == '2')
			{
				
				// Textfeld
				if ($arOV_order[$ov_id] == '')
				{
					return __('Keine Angabe', 'wpsg');					
				}
				
			}
			else if ($ov_db['typ'] == '3')
			{

				// Checkbox
				if ($arOV_order[$ov_id] == '1')
				{
					return __('Ja', 'wpsg');
				}
				else
				{
					return __('Nein', 'wpsg');
				}
				
			}
			
			return $arOV_order[$ov_id];
			
		} // public function getValueByIdAndOrder($ov_id, $order_id)
		
		/**
		 * Gibt die Liste der Produktvariablen für das Backend zurück
		 */
		public function ov_list()
		{
			
			$this->shop->view['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERVARS."` ORDER BY `pos` ASC, `id` ASC ");			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_ordervars/ov_list.phtml');
			
		} // private function ov_list()
		
	} // class wpsg_mod_ordervars extends wpsg_mod_basic

?>