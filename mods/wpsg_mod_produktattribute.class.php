<?php

	/**
	 * Mit diesem Modul ist es möglich zu den Produkten verschiedene Attribute im Backend zu definieren
	 * Diese können dann im Produkttemplate angezeigt werden
	 */
	class wpsg_mod_produktattribute extends wpsg_mod_basic
	{

		var $id = 85;
		var $lizenz = 1;
		var $inline = false;
		var $hilfeURL = 'http://wpshopgermany.de/?p=335';

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Produktattribute', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Erlaubt es Produktattribute zu erstellen, die dann in der Produktverwaltung ausgefüllt und im Frontend angezeigt werden.', 'wpsg');

			$this->arTypen = array(
					'0' => __('Textfeld', 'wpsg'),
					'1' => __('Textfeld (RTE)', 'wpsg'),
					'2' => __('Auswahlfeld', 'wpsg'),
					'3' => __('Checkbox', 'wpsg')
			);

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Tabelle für die Produktattribute
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_AT." (
			   		id mediumint(9) NOT NULL AUTO_INCREMENT,
			   		name varchar(100) NOT NULL,
			   		typ varchar(100) NOT NULL,
			   		auswahl varchar(1000) NOT NULL,
			   		autoshow int(1) NOT NULL,
					pos int(11) NOT NULL,
			   		PRIMARY KEY  (id)
			   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

			/**
			 * Tabelle für die Werte der Produktattribute
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_AT." (
			   		p_id mediumint(9) NOT NULL,
			   		a_id mediumint(9) NOT NULL,
			   		value TEXT NOT NULL,
			   		KEY p_id (p_id),
			   		KEY a_id (a_id)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

			dbDelta($sql);

		} // public function install()

		public function be_ajax()
		{

			$this->shop->mod = $this;

			if ($_REQUEST['do'] == 'add')
			{

				$new_name = __('Anklicken um Bezeichnung zu ändern ...', 'wpsg');

				$at_id = $this->db->ImportQuery(WPSG_TBL_AT, array(
					'name' => wpsg_q($new_name),
					'typ' => 0
				));

				$this->shop->addTranslationString('wpsg_mod_produktattribute_'.$at_id, $new_name);

				$this->pa_listAction(); die();

			}
			else if ($_REQUEST['do'] == 'reorder')
			{

				parse_str($_REQUEST['wpsg_reorder'], $wpsg_reorder);

				foreach ((array)$wpsg_reorder['pab'] as $pos => $pa_id)
				{

					$this->db->UpdateQuery(WPSG_TBL_AT, array(
						'pos' => wpsg_q($pos)
					), " `id` = '".wpsg_q($pa_id)."' ");

				}

				die("1");

			}
			else if ($_REQUEST['do'] == 'genPACode')
			{

				$this->shop->view['id'] = $_REQUEST['pa_id'];
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/genPACode.phtml');
				die();

			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{

				if ($_REQUEST['field'] == 'name')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);
					$_REQUEST['pa_id'] = wpsg_sinput("key", $_REQUEST['pa_id']);

					$this->db->UpdateQuery(WPSG_TBL_AT, array(
						'name' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['pa_id'])."'");

					$this->shop->addTranslationString('wpsg_mod_produktattribute_'.$_REQUEST['pa_id'], $_REQUEST['value']);

					die($_REQUEST['value']);

				}
				else if ($_REQUEST['field'] == 'show')
				{

					$this->db->UpdateQuery(WPSG_TBL_AT, array(
						'autoshow' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['pa_id'])."'");

					die("1");

				}
				else if ($_REQUEST['field'] == 'typ')
				{

					$this->db->UpdateQuery(WPSG_TBL_AT, array(
						'typ' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['pa_id'])."'");

					//die("1");
					$value = $this->arTypen[$_REQUEST['value']];
					die($value);

				}
				else if ($_REQUEST['field'] == 'delete')
				{

					$this->db->Query("DELETE FROM `".WPSG_TBL_AT."` WHERE `id` = '".wpsg_q($_REQUEST['pa_id'])."'");

					die($this->pa_listAction());

				}
				else if ($_REQUEST['field'] == 'auswahl')
				{

					$this->db->UpdateQuery(WPSG_TBL_AT, array(
						'auswahl' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['pa_id'])."'");

					$this->shop->addTranslationString('wpsg_mod_produktattribute_'.$_REQUEST['pa_id'], $_REQUEST['value']);

					die(stripslashes($_REQUEST['value']));

				}

			}

		} // public function be_ajax()

		public function settings_edit()
		{

			//$this->shop->mod = &$this;
			$this->shop->mod = $this;
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{
			
		    $this->shop->update_option('wpsg_mod_produktattribute_showProduct', $_REQUEST['wpsg_mod_produktattribute_showProduct'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_produktattribute_showBasket', $_REQUEST['wpsg_mod_produktattribute_showBasket'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_produktattribute_showOverview', $_REQUEST['wpsg_mod_produktattribute_showOverview'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_produktattribute_showMail', $_REQUEST['wpsg_mod_produktattribute_showMail'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function settings_save()

		public function produkt_save(&$produkt_id) {

			foreach ((array)$_REQUEST as $pa_id => $pa_value) {

				$field_label = $pa_id;
				
				try {
				
					// Musste ich so abändern, da der RTE keine namen Felder vom Typ pa[name] erlaubt.
					if (preg_match('/^pa_/', $pa_id)) {
	
						$pa_id_clear = intval(substr($pa_id, 3));
						
						if (!wpsg_checkInput($pa_id_clear, WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
						
						$pa = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_AT."` WHERE `id` = '".wpsg_q($pa_id_clear)."' ORDER BY `pos` ASC, `id` ASC ");
	
						if (!wpsg_checkInput($pa['id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getInvalidValueException();
						
						$field_label = __($pa['name'], 'wpsg');
						
						$nExists = $this->db->fetchOne("
							SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS_AT."` WHERE `a_id` = '".wpsg_q($pa_id_clear)."' AND `p_id` = '".wpsg_q($produkt_id)."'
						");
												
						if ($pa['typ'] === '0') {
						
							if (!wpsg_checkInput($pa_value, WPSG_SANITIZE_TEXTAREA)) throw \wpsg\Exception::getSanitizeException();
								
						} else if ($pa['typ'] === '1') {
							
							if (!wpsg_checkInput($pa_value, WPSG_SANITIZE_HTML)) throw \wpsg\Exception::getSanitizeException();
							
							if ($this->shop->get_option('wpsg_options_nl2br') == '1') {
								
								$pa_value = nl2br($pa_value);
								
							}
							
						} else if ($pa['typ'] === '2') {
							
							$arSelect = explode('|', $pa['auswahl']);
							
							if (!wpsg_checkInput($pa_value, WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
							if (!array_key_exists(intval($pa_value), $arSelect)) throw new \Exception(__('Ungültige Auswahl', 'wpsg'));
													
						} else if ($pa['typ'] === '3') {
							
							if (!wpsg_checkInput($pa_value, WPSG_SANITIZE_CHECKBOX)) throw \wpsg\Exception::getSanitizeException();
							
						} else throw \wpsg\Exception::getInvalidValueException();
	
						if ($nExists > 0) {
	
							$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_AT, array(
								'value' => wpsg_q($pa_value)
							), "`a_id` = '".wpsg_q($pa_id_clear)."' AND `p_id` = '".wpsg_q($produkt_id)."' ");
	
						} else {
	
							$this->db->ImportQuery(WPSG_TBL_PRODUCTS_AT, array(
								'value' => wpsg_q($pa_value),
								'a_id' => wpsg_q($pa_id_clear),
								'p_id' => wpsg_q($produkt_id)
							));
	
						}
	
					}
					
				} catch (\Exception $e) {
					
					wpsg_ShopController::getShop()->addInputFieldError($pa_id, $field_label);
										
				}

			}

		} // public function produkt_save(&$produkt_id)

		public function produkt_save_translation(&$produkt_id, &$trans_id)
		{

			$this->produkt_save($trans_id);

		} // public function produkt_save_translation(&$produkt_id, &$trans_id)

		public function produkt_createTranslation(&$produkt_id, &$trans_id)
		{

			$arAttributeValues = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_AT."` WHERE `p_id` = '".wpsg_q($produkt_id)."'");

			foreach ($arAttributeValues as $a)
			{

				$a['p_id'] = $trans_id;

				$this->db->ImportQuery(WPSG_TBL_PRODUCTS_AT, $a);

			}

		} // public function produkt_createTranslation(&$produkt_id, &$trans_id)

		public function produkt_copy(&$produkt_id, &$copy_id) {

			// Wie bei der erstellung einer neuen Übersetzung ...
			$this->produkt_createTranslation($produkt_id, $copy_id);

		} // public function produkt_copy(&$produkt_id, &$copy_id)

		public function product_addedit_content(&$product_content, &$product_data)
		{

			// Nur für angelegte Produkte
			if ($product_data['id'] <= 0) return false;

			$this->shop->view['data'] = $product_data;
			$this->shop->view['data']['pa'] = $this->shop->callMod('wpsg_mod_produktattribute', 'getProductAttributeByProductId', array($this->shop->getProduktID($product_data['id'])));

			$this->shop->view['wpsg_mod_produktattribute']['data'] = $product_data;

			$product_content['wpsg_mod_produktattribute'] = array(
				'title' => __('Produktattribute', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/produkt_addedit_content.phtml', false)
			);

		} //public function product_addedit_content(&$product_content, &$product_data)

		public function product_bottom(&$produkt_id, $template_index)
		{ 
			if ($this->shop->get_option('wpsg_mod_produktattribute_showProduct') == '1')
			{
	
				$this->shop->view['wpsg_mod_produktattribute']['data'] = $this->db->fetchAssoc("
					SELECT
						PAT.`value`,
						AT.`id`,
						AT.`auswahl`,
						AT.`typ`,
						AT.`autoshow`,
						AT.`name`
					FROM
						`".WPSG_TBL_PRODUCTS_AT."` AS PAT
							LEFT JOIN  `".WPSG_TBL_AT."` AS AT ON (PAT.`a_id` = AT.`id`)
					WHERE
						PAT.`p_id` = '".wpsg_q($produkt_id)."'
					ORDER BY
						AT.`pos` ASC, AT.`id` ASC
				");
	
				if (wpsg_isSizedArray($this->shop->view['wpsg_mod_produktattribute']['data']))
				{
	
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/produkt_bottom.phtml');
	
				}
			}
		} // public function product_bottom(&$produkt_id, $template_index)

		public function basket_row(&$p, $i)
		{
			if ($this->shop->get_option('wpsg_mod_produktattribute_showBasket') != '1')
			{
				
				$this->shop->view['wpsg_mod_produktattribute']['data'] = $this->db->fetchAssocField("
					SELECT
						AT.`id`
					FROM
						`".WPSG_TBL_AT."` AS AT
					WHERE
						AT.`autoshow` = '1'
					ORDER BY
						AT.`pos` ASC, AT.`id` ASC
				");
	
				$this->shop->view['wpsg_mod_produktattribute']['id'] = $this->shop->getProduktID($p['id']);
				$this->shop->view['i'] = $i;
	
				return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/basket_row.phtml');

			}
		} // public function basket_row(&$p, $i)

		public function overview_row(&$p, $i)
		{
			if ($this->shop->get_option('wpsg_mod_produktattribute_showOverview') != '1')
			{
				
				$this->shop->view['wpsg_mod_produktattribute']['data'] = $this->db->fetchAssocField("
					SELECT
						AT.`id`
					FROM
						`".WPSG_TBL_AT."` AS AT
					WHERE
						AT.`autoshow` = '1'
					ORDER BY
						AT.`pos` ASC, AT.`id` ASC
				");
	
				$this->shop->view['wpsg_mod_produktattribute']['id'] = $this->shop->getProduktID($p['id']);
				$this->shop->view['i'] = $i;
	
				return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/overview_row.phtml');
			}
		} // public function overview_row(&$p, $i)

		public function mail_row($i, $p)
		{
			if ($this->shop->get_option('wpsg_mod_produktattribute_showMail') != '1')
			{
				
				$this->shop->view['wpsg_mod_produktattribute']['data'] = $this->db->fetchAssocField("
					SELECT
						`id`
					FROM
						`".WPSG_TBL_AT."`
					WHERE
						`autoshow` = '1'
					ORDER BY
						`pos` ASC, `id` ASC
				");
	
				$this->shop->view['wpsg_mod_produktattribute']['id'] = $this->shop->getProduktID($p['id']);
				$this->shop->view['i'] = $i;
	
				if ($this->shop->htmlMail === true)
				{
	
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/mail_row_html.phtml');
	
				}
				else
				{
	
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/mail_row.phtml');
	
				}
				
			}
		} // public function mail_row($i, $p)

		public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false)
		{

			if ($product_id !== false && $product_id > 0)
			{

				$arAttribute = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_AT."` ORDER BY `pos` ASC, `id` ASC ");

				foreach ((array)$arAttribute as $pa_id)
				{

					$arReplace['/%at_'.$pa_id.'%/'] = $this->getAttributeValue($product_id, $pa_id);

				}

			}

		} // public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false)

		public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend)
		{

			if ($produkt_key == false) return false;
			$product_id = $this->shop->getProduktID($produkt_key);

			if ($product_id !== false && $product_id > 0)
			{

				$arAttribute = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_AT."` ORDER BY `pos` ASC, `id` ASC ");

				foreach ((array)$arAttribute as $pa_id)
				{

					$arSend['at_'.$pa_id] = $this->getAttributeValue($product_id, $pa_id);

				}

			}

		} // public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend)

		/* Modulfunktionen */

		/**
		 * Gibt alle Möglichen Werte für ein Attribut zurück
		 */
		public function getAttributValues($attribute_id, $arProductFilter = array())
		{

			$strQueryWHERE = " AND AT.`a_id` = '".wpsg_q($attribute_id)."' ";
			$strQueryHAVING = "";
			$strQueryJOIN = " LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = AT.`p_id`) ";

			list($strQueryP_SELECT, $strQueryP_WHERE, $strQueryP_JOIN, $strQueryP_HAVING, $strQueryP_ORDER) = wpsg_product::getQueryParts($arProductFilter);

			$strQueryJOIN .= $strQueryP_JOIN;
			$strQueryWHERE .= $strQueryP_WHERE;
			$strQueryHAVING .= $strQueryP_HAVING;

			$strQuery = "
				SELECT
					DISTINCT AT.`value`
				FROM
				 	`".WPSG_TBL_PRODUCTS_AT."` AS AT
				 		".$strQueryJOIN."
				WHERE
				 	1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
			";

			return $this->db->fetchAssocField($strQuery);

		} // public function getAttributValues($attribute_id, $arProductFilter = array())

		/**
		 * Gibt alle Produktattribute zurück
		 */
		public function getProductattributs()
		{

			$arReturn = $this->db->fetchAssoc("
				SELECT
					A.*
				FROM
					`".WPSG_TBL_AT."` AS A
				WHERE
					1
			", "id");

			foreach ($arReturn as $k => $v)
			{

				if ($v['typ'] === '2') $arReturn[$k]['auswahl'] = wpsg_trim(explode('|', $v['auswahl']));

			}

			// TODO: Übersetzung

			return $arReturn;

		} // public function getProductattributs()

		public function getProductAttributeByProductId($product_id)
		{

			return $this->db->fetchAssoc("
				SELECT
					A.`id`, A.`name`, A.`typ`, PA.`value`, A.`auswahl`
				FROM
					`".WPSG_TBL_AT."` AS A
						LEFT JOIN `".WPSG_TBL_PRODUCTS_AT."` AS PA ON (PA.`a_id` = A.`id` AND PA.`p_id` = '".wpsg_q($product_id)."')
				ORDER BY
					A.`pos` ASC, A.`id` ASC
			");

		} // public function getProductAttributeByProductId($product_id)

		/**
		 * Gibt den Namen eines Attributs zurück
		 */
		public function getAttributeLabel($pa_id)
		{

			$name = $this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_AT."` WHERE `id` = '".wpsg_q($pa_id)."'");

			return __($name, 'wpsg');

		} // public function getAttributeLabel($pa_id)

		/**
		 * Gibt den Wert für ein Attribut zurück
		 */
		public function getAttributeValue($p_id, $pa_id)
		{

			$p_id = $this->shop->getProduktId($p_id);

			if ($this->shop->isOtherLang())
			{

				$produkt_trans_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($p_id)."' AND `lang_code` = '".wpsg_q($this->shop->getCurrentLanguageCode())."'");

				if (wpsg_isSizedInt($produkt_trans_id)) $p_id = $produkt_trans_id;

			}

			$pa = $this->db->fetchRow("
				SELECT
					PA.`value`, A.`typ`, A.`auswahl`
				FROM
					`".WPSG_TBL_PRODUCTS_AT."` AS PA
						LEFT JOIN `".WPSG_TBL_AT."` AS A ON (A.`id` = PA.`a_id`)
				WHERE
					PA.`a_id` = '".wpsg_q($pa_id)."' AND
					PA.`p_id` = '".wpsg_q($p_id)."'
				ORDER BY
					A.`pos` ASC, A.`id` ASC
			");

			if ($pa['typ'] == '1') /* RTE Feld */
			{

				// Filter auf Feld anwenden (RTE)
				// Den wpsgContentFilter deaktivieren um Rekursion zu vermeiden
				remove_filter('the_content', array($GLOBALS['wpsg_sc'], 'content_filter'));
				$value = apply_filters('the_content', $pa['value']);
				add_filter('the_content', array($GLOBALS['wpsg_sc'], 'content_filter'));

			}
			else if ($pa['typ'] == '3')
			{

				// Checkbox
				if ($pa['value'] == '1') $value = __('Ja', 'wpsg');
				else $value = __('Nein', 'wpsg');

			}
			else if ($pa['typ'] == '2')
			{
				// Auswahl
				$arAuswahl = explode('|', $pa['auswahl']);
				$value = $arAuswahl[$pa['value']];
			}
			else
			{

				// Normales Textfeld
				$value = $pa['value'];
			}

			return $value;

		} // public function getAttributeValue($p_id, $pa_id)

		/**
		 * Zeichnet die Liste der Produktattribute für das Backend
		 */
		public function pa_listAction()
		{

			$this->shop->view['data'] = $this->db->fetchAssoc("
				SELECT * FROM `".WPSG_TBL_AT."` ORDER BY `pos` ASC, `id` ASC
			");

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_produktattribute/list.phtml');

		} // private function pa_listAction()

	} // class wpsg_mod_produktattribute extends wpsg_mod_basic

?>