<?php

	/**
	 * Modul für die Produktvariablen (Kundeneingaben für ein bestelltes Produkt)
	 * @author daniel
	 *
	 */
	class wpsg_mod_productvars extends wpsg_mod_basic
	{

		var $lizenz = 1;
		var $id = 950;
		var $hilfeURL = 'http://wpshopgermany.de/?p=815';
		var $inline = false;

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Produktvariablen', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht das Erfassen von Kundeneingaben zu bestellten Produkten.', 'wpsg');

			$this->arTypen = array(
				1 => __('Auswahl', 'wpsg'),
				2 => __('Texteingabe', 'wpsg'),
				3 => __('Checkbox', 'wpsg')
			);

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/*
			 * Posts Tabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_VARS." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name VARCHAR(255) NOT NULL,
		   		typ VARCHAR(100) NOT NULL,
		   		auswahl VARCHAR(5000) NOT NULL,
				pos int(11) NOT NULL,
		   		pflicht INT(1) NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

		} // public function install()

		public function settings_edit()
		{

			$this->shop->mod = $this;
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_productvars_showProduct', $_REQUEST['wpsg_mod_productvars_showProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_productvars_showBasket', $_REQUEST['wpsg_mod_productvars_showBasket'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_productvars_showOverview', $_REQUEST['wpsg_mod_productvars_showOverview'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_productvars_showMail', $_REQUEST['wpsg_mod_productvars_showMail'], false, false, WPSG_SANITIZE_CHECKBOX);

		}

		public function be_ajax()
		{

			$this->shop->mod = $this;

			if ($_REQUEST['do'] == 'add')
			{

				$new_name = __('Anklicken um den Namen der Produktvariable zu ändern ...', 'wpsg');
				$new_auswahl = __('Bitte zum Bearbeiten anklicken.', 'wpsg');

				// Versandzone in Datenbank eintragen
				$pv_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARS, array(
					'name' => wpsg_q($new_name),
					'typ' => '2',
					'auswahl' => $new_auswahl,
					'pflicht' => ''
				));

				$this->shop->addTranslationString('wpsg_mod_productvars_'.$pv_id, $new_name);
				$this->shop->addTranslationString('wpsg_mod_productvars_auswahl_'.$pv_id, $new_auswahl);

				die($this->pv_list());

			}
			else if ($_REQUEST['do'] == 'reorder')
			{

				parse_str($_REQUEST['wpsg_reorder'], $wpsg_reorder);

				foreach ($wpsg_reorder['pv'] as $pos => $pv_id)
				{

					$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARS, array(
						'pos' => wpsg_q($pos)
					), " `id` = '".wpsg_q($pv_id)."' ");

				}

				die("1");

			}
			else if ($_REQUEST['do'] == 'del')
			{

				$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($_REQUEST['pv_id'])."'");

				die($this->pv_list());

			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{

				$data = array();
				if ($_REQUEST['field'] == 'name') {

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

					$data['name'] = wpsg_q($_REQUEST['value']);
					$this->shop->addTranslationString('wpsg_mod_productvars_'.$_REQUEST['pv_id'], $_REQUEST['value']);
					$value = __($_REQUEST['value'], 'wpsg');

				}
				else if ($_REQUEST['field'] == 'pflicht') { $data['pflicht'] = wpsg_q(wpsg_sinput("key", $_REQUEST['value'])); $value = wpsg_sinput("key", $_REQUEST['value']); }
				else if ($_REQUEST['field'] == 'typ') { $data['typ'] = wpsg_q(wpsg_sinput("key", $_REQUEST['value'])); $value = $this->arTypen[wpsg_sinput("key", $_REQUEST['value'])]; }
				else if ($_REQUEST['field'] == 'auswahl') {

					$data['auswahl'] = wpsg_q($_REQUEST['value']);
					$this->shop->addTranslationString('wpsg_mod_productvars_auswahl'.$_REQUEST['pv_id'], wpsg_sinput("key", $_REQUEST['value']));
					$value = $_REQUEST['value'];

				}

				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARS, $data, "`id` = '".wpsg_q($_REQUEST['pv_id'])."'");

				die($value);

			}

		} // public function be_ajax()

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (isset($_REQUEST['wpsg_lang']) || !wpsg_isSizedInt($_REQUEST['edit_id'])) return;

			$this->shop->view['wpsg_mod_productvars']['productvars_set'] = explode(",", $product_data['produktvars']);
			$this->shop->view['wpsg_mod_productvars']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` ORDER BY `pos` ASC, `id` ASC");

			//$this->shop->view['wpsg_mod_productvars']['data'] = $product_data;

			$product_content['wpsg_mod_productvars'] = array(
					'title' => __('Produktvariable', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/produkt_addedit_sidebar.phtml', false)
			);

		} //public function product_addedit_content(&$product_content, &$product_data)

		public function produkt_save(&$produkt_id) {
			
			if (isset($_REQUEST['wpsg_pv'])) {
				
				foreach ((array)$_REQUEST['wpsg_pv'] as $k => $v) { if ($v != '1') unset($_REQUEST['wpsg_pv'][$k]); else $_REQUEST['wpsg_pv'][$k] = intval($v); }

				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array('produktvars' => implode(",", array_keys($_REQUEST['wpsg_pv']))), "`id` = '".wpsg_q($produkt_id)."'");
			}

		} // public function produkt_save(&$produkt_id)

		public function basket_check()
		{

			$bError = false;

			foreach ($_SESSION['wpsg']['basket'] as $product_index => $p)
			{

				$arPV = $this->getAllProductVars($this->shop->getProduktID($p['id']));

				foreach ((array)$arPV as $k => $pv_db)
				{

					$pv_id = $pv_db['id'];
					$value = $this->getProductVarValueSession($product_index, $pv_id);

					if ($pv_db['typ'] == '1' && $pv_db['pflicht'] == '1' && ($value == 'not_set' || $value === false)) // Auswahlfeld
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Auswahl treffen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
						$bError = true;

					}
					else if ($pv_db['typ'] == '2' && $pv_db['pflicht'] == '1' && (trim($value) == '' || $value === false)) // Textfield
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Angabe machen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
						$bError = true;

					}
					else if ($pv_db['typ'] == '3' && $pv_db['pflicht'] == '1' && ($value != '1' || $value === false)) // Checkbox
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte das Feld "#1#" aktivieren!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
						$bError = true;

					}

				}

			}

			if ($bError === true)
			{

				return -2;

			}

		} // public function basket_check()

		public function basket_checkoutAction(&$basketController)
		{

			$bError = false;

			if (wpsg_isSizedArray($_REQUEST['wpsg_mod_productvars']))
			{

				foreach ($_REQUEST['wpsg_mod_productvars'] as $pv_id => $pv_values)
				{

					$pv_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($pv_id)."' ORDER BY `pos` ASC, `id` ASC");

					// Wert speichern
					foreach ($pv_values as $pv_product_index => $value)
					{

						foreach ($_SESSION['wpsg']['basket'] as $product_index => $ses_data)
						{

							if ($product_index == $pv_product_index)
							{

								$_SESSION['wpsg']['basket'][$pv_product_index]['wpsg_mod_productvars'][$pv_id] = wpsg_xss($value);

								if ($pv_db['typ'] == '1' && $pv_db['pflicht'] == '1' && $value == 'not_set') // Auswahlfeld
								{

									$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Auswahl treffen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
									$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
									$bError = true;

								}
								else if ($pv_db['typ'] == '2' && $pv_db['pflicht'] == '1' && trim($value) == '') // Textfield
								{

									$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Angabe machen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
									$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
									$bError = true;

								}
								else if ($pv_db['typ'] == '3' && $pv_db['pflicht'] == '1' && $value != '1') // Checkbox
								{

									$this->shop->addFrontendError(wpsg_translate(__('Bitte das Feld "#1#" aktivieren!', 'wpsg'), __($pv_db['name'], 'wpsg')));
									$_SESSION['wpsg']['errorFields'][] = wpsg_xss('wpsg_mod_productvars_'.$pv_id.'_'.$product_index);
									$bError = true;

								}

							}

						}

					}

				}

			}

			if ($bError) return -2;

		} // public function basket_checkoutAction(&$basketController)

		public function basket_preUpdate()
		{

			if (isset($_REQUEST['wpsg_mod_productvars']) && isset($_SESSION['wpsg']['basket']))
			{

				foreach ((array)$_REQUEST['wpsg_mod_productvars'] as $pv_id => $pv_values)
				{

					foreach ((array)$pv_values as $product_index => $value)
					{

						foreach ((array)$_SESSION['wpsg']['basket'] as $product_index_session => $ses_data)
						{

							if ($product_index_session == $product_index)
							{

								$_SESSION['wpsg']['basket'][$product_index]['wpsg_mod_productvars'][$pv_id] = wpsg_xss($value);

							}

						}

					}

				}

			}

		} // public function basket_preUpdate()
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) { 
			
			if (wpsg_isSizedArray($_SESSION['wpsg']['basket'])) {
			
				$pv_store = [];
							
				foreach ($_SESSION['wpsg']['basket'] as $product_index => $p) {
					
					if (isset($p['wpsg_mod_productvars']) && is_array($p['wpsg_mod_productvars']) && sizeof($p['wpsg_mod_productvars']) > 0) {
						
						$pv_store[$product_index] = $p['wpsg_mod_productvars'];
						
					}
					
				}
				
				if (is_array($pv_store) && sizeof($pv_store) > 0) {
					
					$db_data['pvars'] = wpsg_q(serialize($pv_store));
					
				}
				
			}
			
		}
		
		public function order_view_row(&$p, $i)
		{

			if (!isset($this->shop->view['data'])) return;
			$this->shop->view['wpsg_mod_productvars']['data'] = $this->getAllProductVarValues($this->shop->view['data']['id'], $p['product_index']);

			if (wpsg_isSizedArray($this->shop->view['wpsg_mod_productvars']['data']))
			{

				$this->shop->view['wpsg_mod_productvars']['p'] = $p;
				$this->shop->view['wpsg_mod_productvars']['i'] = $i;
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/order_view_row.phtml');

			}

		} // public function order_view_row(&$p, &$i)

		public function admin_presentation()
		{

			echo wpsg_drawForm_Checkbox('wpsg_mod_productvars_showvariprice', __('"Leere" Produktvariablen anzeigen', 'wpsg'), $this->shop->get_option('wpsg_mod_productvars_showvariprice'));

		} // public function admin_presentation()

		public function admin_presentation_submit()
		{

			$this->shop->update_option('wpsg_mod_productvars_showvariprice', $_REQUEST['wpsg_mod_productvars_showvariprice'], false, false, WPSG_SANITIZE_CHECKBOX);

		} // public function admin_presentation_submit()

		public function user_order_view_row(&$p, $oid)
		{

			$strCustomData = $this->db->fetchOne("
						SELECT
							`custom_data`
						FROM
							`".WPSG_TBL_ORDER."`
						WHERE
							`id` = '".wpsg_q($oid)."'
					");

			$strUnserializeData = unserialize($strCustomData);

			$this->shop->view['UnserializeProductData'] = $strUnserializeData['basket']['produkte'];
		}

		public function basket_row(&$p, $i)
		{
			if ($this->shop->get_option('wpsg_mod_productvars_showBasket') != '1')
			{ 
				$produkt_id = $this->shop->basket->getProduktDBID($p['id']);
	
				if ($produkt_id > 0)
				{
	
					$pVarsSet = explode(",", $this->db->fetchOne("SELECT `produktvars` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($produkt_id)."'"));
					foreach ($pVarsSet as $k => $v) { if (trim($v) == '') { unset($pVarsSet[$k]); } }
	
					if (is_array($pVarsSet) && sizeof($pVarsSet) > 0)
					{
	
						$this->shop->view['wpsg_mod_productvars']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` IN (".wpsg_q(implode(',', $pVarsSet)).") ORDER BY `pos` ASC, `id` ASC", "id");
	
						foreach ($this->shop->view['wpsg_mod_productvars']['data'] as $k => $v)
						{
							$this->shop->view['wpsg_mod_productvars']['data'][$k]['auswahl'] = explode("|", $v['auswahl']);
						}
	
						$this->shop->view['wpsg_mod_productvars']['i'] = $i;
						$this->shop->view['wpsg_mod_productvars']['p'] = $p;
	
						$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/basket_row.phtml');
	
					}
	
				}
			}
				
		} // public function basket_row(&$p, $i)

		public function basket_preMultiple($product_index)
		{

			if (wpsg_isSizedArray($_REQUEST['wpsg_mod_productvars']))
			{

				foreach ($_REQUEST['wpsg_mod_productvars'] as $pv_id => $value)
				{

					foreach ($value as $pv_product_index => $pv_value)
					{

						if ($pv_product_index == $product_index)
						{

							$_REQUEST['wpsg_mod_productvars'][$pv_id] = $pv_value;

						}

					}

				}

			}

		}

		public function basket_produkttosession($produkt_key, &$menge, &$ses_data)
		{

			$bError = false;

			if (isset($_REQUEST['wpsg_mod_productvars']) && is_array($_REQUEST['wpsg_mod_productvars']))
			{

				foreach ($_REQUEST['wpsg_mod_productvars'] as $pv_id => $value)
				{

					$pv_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($pv_id)."' ");

					$ses_data['wpsg_mod_productvars'][$pv_id] = $value;

					if ($pv_db['typ'] == '1' && $pv_db['pflicht'] == '1' && $value == 'not_set') // Auswahlfeld
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Auswahl treffen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_productvars_'.$pv_id.'_'.$ses_data['id'];
						$bError = true;

					}
					else if ($pv_db['typ'] == '2' && $pv_db['pflicht'] == '1' && trim($value) == '') // Textfield
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte im Feld "#1#" eine Angabe machen!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_productvars_'.$pv_id.'_'.$ses_data['id'];
						$bError = true;

					}
					else if ($pv_db['typ'] == '3' && $pv_db['pflicht'] == '1' && $value != '1') // Checkbox
					{

						$this->shop->addFrontendError(wpsg_translate(__('Bitte das Feld "#1#" aktivieren!', 'wpsg'), __($pv_db['name'], 'wpsg')));
						$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_productvars_'.$pv_id.'_'.$ses_data['id'];
						$bError = true;

					}

				}

			}

			// Nix machen bei Warenkorbaktualisierung
			if (isset($_REQUEST['wpsg_basket_refresh'])) return null;

			if ($bError === true) return -2; else return null;

		} // public function basket_produkttosession($produkt_key, $menge, &$ses_data)

		public function product_bottom(&$produkt_id, $template_index)
		{
 
			if ($this->shop->get_option('wpsg_mod_productvars_showProduct') != '1') return false;

			$pVarsSet = explode(",", $this->db->fetchOne("SELECT `produktvars` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($produkt_id)."'"));

			foreach ($pVarsSet as $k => $v) { if (trim($v) == '') { unset($pVarsSet[$k]); } }

			if (is_array($pVarsSet) && sizeof($pVarsSet) > 0)
			{

				$this->shop->view['wpsg_mod_productvars']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` IN (".wpsg_q(implode(',', $pVarsSet)).") ORDER BY `pos` ASC, `id` ASC");

				foreach ($this->shop->view['wpsg_mod_productvars']['data'] as $k => $v)
				{
					$this->shop->view['wpsg_mod_productvars']['data'][$k]['auswahl'] = explode("|", $v['auswahl']);
				}

				if (isset($_REQUEST['form_data']))
				{

					$this->shop->checkEscape();

					$form_data = array();
					parse_str($_REQUEST['form_data'], $form_data);

					foreach ($form_data['wpsg_mod_productvars'] as $pv_id => $pv_value)
					{

						foreach ($this->shop->view['wpsg_mod_productvars']['data'] as $pv_index => $pv)
						{

							if ($pv['id'] == $pv_id)
							{

								$this->shop->view['wpsg_mod_productvars']['data'][$pv_index]['value'] = $pv_value;

							}

						}

					}

				}

				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/renderTemplate.phtml', false);

			}

		} // public function product_bottom(&$produkt_id, $template_index)

		public function mail_row($index, $produkt)
		{
			if ($this->shop->get_option('wpsg_mod_productvars_showMail') != '1')
			{
				$order_id = $this->shop->view['o_id'];
	
				$arPVars = $this->getAllProductVarValues($order_id, $produkt['product_index']);
	
				if (wpsg_isSizedArray($arPVars))
				{
	
					$this->shop->view['wpsg_mod_productvars']['data'] = $arPVars;
					$this->shop->view['wpsg_mod_productvars']['i'] = $index;
					$this->shop->view['wpsg_mod_productvars']['p'] = $produkt;
	
					if ($this->shop->htmlMail === true)
					{
	
						$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/mail_row_html.phtml');
	
					}
					else
					{
	
						$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/mail_row.phtml');
	
					}
	
				}
			}
		}

		public function order_ajax()
		{

			if (wpsg_isSizedString($_REQUEST['do'], 'inlinedit'))
			{

				$db_pvar = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($_REQUEST['pv_id'])."'");
				$arAuswahl = explode('|', $db_pvar['auswahl']);

				if ($db_pvar['typ'] == 1 && is_numeric($_REQUEST['value']))
				{

					$_REQUEST['value'] = $arAuswahl[$_REQUEST['value']];

				}

				$db_order = $this->shop->cache->loadOrder($_REQUEST['order_id']);
				$pvars = @unserialize($db_order['pvars']);
				$pvars[$_REQUEST['p_id']][$_REQUEST['pv_id']] = $_REQUEST['value'];

				$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
					'pvars' => wpsg_q(@serialize($pvars))
				), " `id` = '".wpsg_q($_REQUEST['order_id'])."' ");

				if ($db_pvar['typ'] == 1 && $_REQUEST['value'] == 'not_set') $_REQUEST['value'] = __('Keine Angabe', 'wpsg');
				else if ($db_pvar['typ'] == 3)
				{

					if ($_REQUEST['value'] == '1') $_REQUEST['value'] = __('Ja', 'wpsg');
					else $_REQUEST['value'] = __('Nein', 'wpsg');

				}

				die($_REQUEST['value']);

			}

		}

		public function overview_row(&$p, $i)
		{
			
			if ($this->shop->get_option('wpsg_mod_productvars_showOverview') != '1')
			{
				$produkt_id = $this->shop->basket->getProduktDBID($p['id']);
	
				if ($produkt_id > 0)
				{
	
					$pVarsSet = explode(",", $this->db->fetchOne("SELECT `produktvars` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($produkt_id)."'"));
					foreach ($pVarsSet as $k => $v) { if (trim($v) == '') { unset($pVarsSet[$k]); } }
	
					if (is_array($pVarsSet) && sizeof($pVarsSet) > 0)
					{
	
						$this->shop->view['wpsg_mod_productvars']['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` IN (".wpsg_q(implode(',', $pVarsSet)).") ORDER BY `pos` ASC, `id` ASC");
	
						if ($this->shop->get_option('wpsg_mod_productvars_showvariprice') !== '1' && wpsg_isSizedArray($this->shop->view['wpsg_mod_productvars']['data']))
						{
	
							// Es sollen nicht alle angezeigt werden
							foreach ($this->shop->view['wpsg_mod_productvars']['data'] as $k => $v)
							{
	
								if ($v['typ'] === '1' && $p['wpsg_mod_productvars'][$v['id']] === 'not_set') { unset($this->shop->view['wpsg_mod_productvars']['data'][$k]); }
								else if ($v['typ'] === '2' && !wpsg_isSizedString($p['wpsg_mod_productvars'][$v['id']])) { unset($this->shop->view['wpsg_mod_productvars']['data'][$k]); }
	
							}
	
						}
	
						$this->shop->view['wpsg_mod_productvars']['i'] = $i;
						$this->shop->view['wpsg_mod_productvars']['p'] = $p;
						$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/overview_row.phtml');
	
					}
	
				}
			}
		} // public function overview_row(&$p, $i)

		public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false)
		{

			if ($product_id !== false && $product_id > 0 && $order_id !== false && $order_id > 0)
			{

				 $arPVData = $this->getAllProductVarValues($order_id, $product_index);

				 foreach ((array)$arPVData as $k => $v)
				 {

				 	$arReplace['/%pv_'.$k.'%/'] = $v['value'];

				 }

			}

		} // public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false)

		public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend)
		{

			if ($produkt_key == false) return false;
			$product_id = $this->shop->getProduktID($produkt_key);

			if ($product_id !== false && $product_id > 0 && $order_id !== false && $order_id > 0)
			{

				$arPVData = $this->getAllProductVarValues($order_id, $product_id);

				foreach ((array)$arPVData as $k => $v)
				{

					$arSend['pv_'.$k] = $v['value'];

				}

			}

		} // public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend)

		/* Modulfunktionen */

		/**
		 * Prüft ob die Produktvariable mit der ID $pvar_id in dem Produkt mit der ID $product_id aktiv ist und abgefragt werden muss
		 * @param int $product_id Id des Produkts
		 * @param int $pvar_id Id der Produktvariable
		 */
		public function hasProductProductVarActive($product_id, $pvar_id)
		{

			$pVarsSet = explode(",", $this->db->fetchOne("SELECT `produktvars` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($product_id)."'"));
			$pVarsSet = wpsg_trim($pVarsSet);

			if (in_array($pvar_id, $pVarsSet)) return true;
			else return false;

		} // public function hasProductProductVarActive($product_id, $pvar_id)

		/**
		 * Gibt die Werte aller Produktvariablen einer Bestellung und Produkt zurück
		 * Nur die, die auch gefüllt sind und mit Werten belegt wurden, oder alle angezeigt werden sollen
		 */
		public function getAllProductVarValues($order_id, $product_index, $bAll = false)
		{

			$order_data = $this->shop->cache->loadOrder($order_id);
			$arPVars = @unserialize($order_data['pvars']);
			$arPVars = $arPVars[$product_index];

			$arReturn = array();

			$arPVarsDB = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."`");

			foreach ($arPVarsDB as $k => $pv_db)
			{

				if ($bAll || array_key_exists($pv_db['id'], (array)$arPVars))
				{

					$value = "";

					if (array_key_exists($pv_db['id'], $arPVars))
					{

						switch ($pv_db['typ'])
						{
							case 3: // Checkbox

								if ($arPVars[$pv_db['id']] == '1') $value = __('Ja', 'wpsg');
								else $value = __('Nein', 'wpsg');

								break;

							case 2: // Text

								$value = $arPVars[$pv_db['id']];

								break;

							case 1: // Auswahl

								$value = $arPVars[$pv_db['id']];
								if ($value == "not_set") $value = __('Keine Angabe', 'wpsg');

								break;

						}

					}

					$arReturn[$pv_db['id']] = array(
						'id' => $pv_db['id'],
						'typ' => $pv_db['typ'],
						'auswahl' => explode('|', $pv_db['auswahl']),
						'name' => __($pv_db['name'], 'wpsg'),
						'value' => $value
					);

				}

			}

			/*
			if (is_array($arPVars))
			{

				foreach ($arPVars as $k => $v)
				{

					if ($k == $product_index)
					{

						foreach ($arPVars[$k] as $k2 => $v2)
						{

							$pvar_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($k2)."'");

							if ($pvar_db['typ'] == 3) // Checkbox
							{

								$arReturn[$k2] = array('typ' => '3', 'id' => $k2, 'name' => __($pvar_db['name'], 'wpsg'), 'value' => (($v2 === '1')?__('Ja', 'wpsg'):__('Nein', 'wpsg')));
								$arIDSet[] = $k2;

							}
							else if ($pvar_db['typ'] == 1) // Auswahl
							{

								if ($v2 != 'not_set' || $this->shop->get_option('wpsg_mod_productvars_showvariprice') === '1')
								{

									if ($v2 == 'not_set') $v2 = __('Nicht angegeben', 'wpsg');

									$arReturn[$k2] = array('typ' => '1', 'id' => $k2, 'name' => __($pvar_db['name'], 'wpsg'), 'value' => $v2);
									$arIDSet[] = $k2;

								}

							}
							else if ($pvar_db['typ'] == 2) // Text
							{

								if (wpsg_isSizedString($v2) || $this->shop->get_option('wpsg_mod_productvars_showvariprice') === '1')
								{

									if (!wpsg_isSizedString($v2)) $v2 = __('Keine Angaben', 'wpsg');

									$arReturn[$k2] = array('typ' => '2', 'id' => $k2, 'name' => __($pvar_db['name'], 'wpsg'), 'value' => $v2);
									$arIDSet[] = $k2;

								}

							}

						}

					}

				}

			} */

			return $arReturn;

		} // public function getAllProductVarValues($order_id, $product_id)

		public function getProductVarValueSession($product_index, $pv_id)
		{

			if (isset($_SESSION['wpsg']['basket'][$product_index]['wpsg_mod_productvars'][$pv_id])) return $_SESSION['wpsg']['basket'][$product_index]['wpsg_mod_productvars'][$pv_id];
			else return false;

		}

		public function getAllProductVars($product_id)
		{

			$product_data = $this->shop->cache->loadProduct($product_id);

			if (!wpsg_isSizedString($product_data['produktvars'])) return array();

			$arProductVars_active = $this->db->fetchAssoc("
				SELECT
					PV.*
				FROM
					`".WPSG_TBL_PRODUCTS_VARS."` AS PV
				WHERE
					FIND_IN_SET(PV.`id`, '".wpsg_q($product_data['produktvars'])."')
				ORDER BY
					PV.`pos` ASC, PV.`id` ASC
			");

			foreach ($arProductVars_active as $k => $pv)
			{

				$arProductVars_active[$k]['name'] = __($pv['name'], 'wpsg');

			}

			return $arProductVars_active;

		} // public function getAllProductVars($product_id)

		/**
		 * Gibt die Liste der Produktvariablen für das Backend zurück
		 */
		public function pv_list()
		{

			$this->shop->view['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` ORDER BY `pos` ASC, `id` ASC");
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvars/pv_list.phtml');

		} // private function pv_list()

		/**
		 * Gibt den Namen der Produktvariable zurück
		 */
		public function getNameFromID($pVarsID)
		{

			$db_pvars = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` WHERE `id` = '".wpsg_q($pVarsID)."' ");

			return __($db_pvars['name'], 'wpsg');

		} // public function getNameFromID($pVarsID)

	} // class wpsg_mod_productvars extends wpsg_mod_basic

?>