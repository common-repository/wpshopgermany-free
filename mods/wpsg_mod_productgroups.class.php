<?php

	/**
	 * Modul für die Produktgruppenverwaltung
	 * @author daniel
	 */
	class wpsg_mod_productgroups extends wpsg_mod_basic
	{

		var $lizenz = 1;
		var $id = 9;
		var $hilfeURL = 'http://wpshopgermany.de/?p=868';

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Produktgruppen', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht die Zuordnung von Produkten zu Produktgruppen.', 'wpsg');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Produktgruppentabelle anlegen
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_GROUP." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		cdate datetime NOT NULL,
		   		name VARCHAR(255) NOT NULL,
		   		template_file VARCHAR(255) NOT NULL,
		   		infopage INT(11) NOT NULL,
		   		deleted INT(1) NOT NULL,
		   		rabatt TEXT(255) NOT NULL,
		   		stock_aktiv VARCHAR(255) NOT NULL,
		   		stock_value INT(11) NOT NULL,
				lang TEXT NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

   			/**
		   	 * Tabelle für die Sticky Produkte
		   	 */
		   	$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_STICKY." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		produkt_id INT(11) NOT NULL,
		   		von INT NOT NULL,
		   		bis INT NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

		   	dbDelta($sql);

		} // public function install()

		public function init()
		{

			require_once(WPSG_PATH_MOD.'mod_productgroups/wpsg_productgroup.php');

			$role_object = get_role('administrator');
			$role_object->add_cap('wpsg_productgroup');

		} // public function init()

		public function settings_edit()
		{

			$pages = get_pages();

			$arPages = array(
				'-1' => __('Nicht zugeordnet', 'wpsg')
			);

			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}

			$this->shop->view['pages'] = $arPages;

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{
			
			$this->shop->update_option('wpsg_productgroups_page', $_REQUEST['wpsg_productgroups_page'], false, false, WPSG_SANITIZE_PAGEID);
			$this->shop->update_option('wpsg_productgroups_order', $_REQUEST['wpsg_productgroups_order'], false, false, WPSG_SANITIZE_VALUES, ['id', 'alphabetisch', 'buyed', 'erstellungsdatum', 'preis']);
			$this->shop->update_option('wpsg_mod_productgroups_order_filter', $_REQUEST['wpsg_mod_productgroups_order_filter'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_productgroups_productindex', $_REQUEST['wpsg_mod_productgroups_productindex'], false, false, WPSG_SANITIZE_CHECKBOX);
						
		} // public function settings_save()

		public function admin_setcapabilities() {

			$this->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/admin_setcapabilities.phtml');

		} // public function admin_setcapabilities()

		public function wpsg_add_pages($default_page)
		{

			add_submenu_page($default_page, __("Produktgruppen", "wpsg"), __("Produktgruppen", "wpsg"), 'wpsg_productgroup', 'wpsg-Productgroups', array($this, 'dispatch'));

		} // public function wpsg_add_pages()

		public function produkt_del($produkt_id)
		{

			$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS_STICKY."` WHERE `produkt_id` = '".wpsg_q($produkt_id)."'");

		} // public function produkt_del($produkt_id)

		public function shortcode($atts)
		{

			// [wpshopgermany produktgruppe="1" sortierung="id" richtung="asc"]

			$pg_id = $atts['produktgruppe'];
			$pg_template = $this->db->fetchOne("SELECT `template_file` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($pg_id)."'");

			$arSortierung = Array('id', 'name', 'anr', 'preis');
			$arDirection = Array('asc', 'desc');

			$strOrder = 'sticky DESC, ';

			if (isset($atts['sortierung']) && in_array($atts['sortierung'], $arSortierung))
			{

				$strOrder .= 'P.`'.wpsg_q($atts['sortierung']).'`';

				if (isset($atts['richtung']) && in_array($atts['richtung'], $arDirection))
				{

					$strOrder .= ' '.wpsg_q($atts['richtung']);

				}
				else
				{

					$strOrder .= ' ASC';

				}

			}
			else
			{

				$strOrder .= 'P.`id` ASC';

			}

			$arProduktIDs = $this->db->fetchAssocField("
				SELECT
					P.`id`,
					IF (
						(SELECT PS.`id` FROM `".WPSG_TBL_PRODUCTS_STICKY."` AS PS WHERE PS.`produkt_id` = P.`id` AND  UNIX_TIMESTAMP(NOW()) BETWEEN `von` AND `bis`) > 0,
						'1',
						'0'
					) AS sticky
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
				WHERE
					P.`pgruppe` = '".wpsg_q($pg_id)."' AND
					P.`deleted` != '1' AND 
					P.`disabled` != '1' AND 
					P.`lang_parent` <= 0 
				ORDER BY
					".$strOrder."
			");

			if ($pg_template !== '0' && $pg_template !== '') $template = $pg_template; else $template = false;

			$strReturn = '';
			foreach ((array)$arProduktIDs as $p_id)
			{

				//if ($pg_template !== '0' && $pg_template !== '') $template = $pg_template; else $template = $this->db->fetchOne("SELECT `ptemplate_file` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($p_id)."'");

				$strReturn .= $this->shop->renderProdukt($p_id, $template);

			}

			return $strReturn;

		} // public function shortcode($atts)

		/**
		 * Integriert den Namen der Gruppe in das Produktarray
		 */
		public function loadProduktArray(&$arrProdukt)
		{

			$arrGrp = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($arrProdukt['pgruppe'])."'");

			if ($this->shop->isOtherLang())
			{

				$lang = @unserialize($arrGrp['lang']);

				if (is_array($lang) && wpsg_isSizedString($lang[$this->shop->getCurrentLanguageCode()]['name'])) $arrGrp['name'] = $lang[$this->shop->getCurrentLanguageCode()]['name'];

			}

			$arrProdukt['pgruppe_name'] = $arrGrp['name'];

			// ?? Scheint zu funktionieren
			$arrProdukt['rabatt'] = $arrGrp['rabatt'];

		}

		public function produkt_save(&$produkt_id)
		{

			$sticky_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_PRODUCTS_STICKY."` WHERE `produkt_id` = '".wpsg_q($produkt_id)."'");

			$data = array(
				'produkt_id' => wpsg_q($produkt_id),
				'von' => wpsg_q(strtotime(wpsg_xss($_REQUEST['wpsg_productgroup_sticky_von']))),
				'bis' => wpsg_q(strtotime(wpsg_xss($_REQUEST['wpsg_productgroup_sticky_bis'])))
			);

			if ($sticky_id > 0)
			{
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_STICKY, $data, "`id` = '".wpsg_q($sticky_id)."'");
			}
			else
			{
				$this->db->ImportQuery(WPSG_TBL_PRODUCTS_STICKY, $data);
			}

			$data = array(
				'pgruppe' => wpsg_q($_REQUEST['wpsg_productgroup'])
			);

			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, "`id` = '".wpsg_q($produkt_id)."'");

		} // public function produkt_save($produkt_id)

		public function produkt_copy(&$produkt_id, &$copy_id)
		{

			$arSticky = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_STICKY."` WHERE `produkt_id` = '".wpsg_q($produkt_id)."'");

			foreach ((array)$arSticky as $s)
			{

				unset($s['id']);
				$s['produkt_id'] = wpsg_q($copy_id);

				$this->db->ImportQuery(WPSG_TBL_PRODUCTS_STICKY, $s);

			}

		} // public function produkt_copy(&$produkt_id, &$copy_id)

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (isset($_REQUEST['wpsg_lang'])) return;

			$this->shop->view['wpsg_mod_productgroups']['data'] = array();
			$this->shop->view['wpsg_mod_productgroups']['data'][0] = __('Nicht zugewiesen.', 'wpsg');

			// Kein Array Merge ! Da indexe gelöscht werden
			$groups_db = $this->db->fetchAssocField("
				SELECT
					PG.`id`, PG.`name`
				FROM
					`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
				WHERE
					PG.`deleted` != '1'
				ORDER BY
					`id` ASC
			", "id", "name");
			$this->shop->view['wpsg_mod_productgroups']['data'] = array_diff_key($this->shop->view['wpsg_mod_productgroups']['data'], $groups_db) + $groups_db;

			if (wpsg_isSizedInt($product_data['id']))
			{

				$this->shop->view['wpsg_mod_productgroups']['produkt_data'] = $product_data;

				$sticky_data = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_STICKY."` WHERE `produkt_id` = '".wpsg_q($product_data['id'])."'");

				if ($sticky_data['von'] > 0) $this->shop->view['wpsg_mod_productgroups']['sticky_von'] = date('d.m.Y', $sticky_data['von']);
				if ($sticky_data['bis'] > 0) $this->shop->view['wpsg_mod_productgroups']['sticky_bis'] = date('d.m.Y', $sticky_data['bis']);;

			}

			$product_content['wpsg_mod_productgroups'] = array(
				'title' => __('Produktgruppen', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/produkt_addedit_sidebar.phtml', false)
			);

		} // public function produkt_edit_sidebar(&$product_content, &$produkt_data)

		public function content_filter(&$content)
		{

		    $id = wpsg_get_the_id();

			if ($id <= 0 || $id != $this->shop->get_option('wpsg_productgroups_page')) return;

			if (isset($_REQUEST['show']) && $_REQUEST['show'] > 0)
			{

				$arrGrp = $this->db->fetchRow("
					SELECT
						*
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."`
					WHERE
						`id` = '".wpsg_q($_REQUEST['show'])."' AND
						`deleted` = '0'
					ORDER BY
						`name`
				");

				if ($this->shop->isOtherLang())
				{

					$lang = @unserialize($arrGrp['lang']);

					if (is_array($lang) && wpsg_isSizedString($lang[$this->shop->getCurrentLanguageCode()]['name'])) $arrGrp['name'] = $lang[$this->shop->getCurrentLanguageCode()]['name'];

				}

				$strOrder = "sticky DESC";
				if ($this->shop->get_option('wpsg_productgroups_order') != '')
				{

					switch ($this->shop->get_option('wpsg_productgroups_order'))
					{

						case 'id':
							$strOrder .= ", P.`id` ASC ";
							break;
						case 'alphabetisch':
							$strOrder .= ", P.`name` ASC ";
							break;
						case 'buyed':
							$strOrder .= ", `buyed` DESC ";
							break;
						case 'erstellungsdatum':
							$strOrder .= ", `cdate` DESC ";
							break;
						case 'preis':
							$strOrder .= ", `preis` DESC ";
							break;

					}

				}

				if ($strOrder != "") $strOrder = " ORDER BY ".$strOrder;

				$arrProdukte = $this->shop->db->fetchAssoc("SELECT
						P.`id`,
						(IF('".time()."' > PS.`von` AND '".time()."' < PS.`bis`, 1, 0)) AS sticky,
						(SELECT SUM(`menge`) FROM `".WPSG_TBL_ORDERPRODUCT."` AS OP WHERE OP.`p_id` = P.`id`) AS buyed
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
							LEFT JOIN `".WPSG_TBL_PRODUCTS_STICKY."` AS PS ON (P.`id` = PS.`produkt_id`)
					WHERE
						P.`pgruppe` = '".wpsg_q($arrGrp['id'])."' AND
						P.`lang_parent` = 0 AND  
						P.`disabled` != '1' AND 
						P.`deleted` = '0'
					".$strOrder."
				");

				// Bilder der Produkte
				foreach ($arrProdukte as $k => $p)
				{

					$arrProdukte[$k] = $this->shop->loadProduktArray($p['id']);

					$arrProdukte[$k]['bilder'] = $this->shop->imagehandler->getAttachmentIDs($p['id']);
					if (!wpsg_isSizedArray($arrProdukte[$k]['bilder'])) $arrProdukte[$k]['bilder'] = array();

				}

				$this->shop->view['data'] = $arrGrp;
				$this->shop->view['data']['produkte'] = $arrProdukte;

				$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/show.phtml', false);

			}
			else
			{

				$arrGrp = $this->db->fetchAssoc("
					SELECT
						*
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."`
					WHERE
						`deleted` = '0'
					ORDER BY
						`name`
				");

				foreach ($arrGrp as $k => $pg)
				{

					if ($this->shop->isOtherLang())
					{

						$lang = @unserialize($pg['lang']);

						if (is_array($lang) && wpsg_isSizedString($lang[$this->shop->getCurrentLanguageCode()]['name'])) $pg['name'] = $lang[$this->shop->getCurrentLanguageCode()]['name'];

					}

					$strOrder = "sticky DESC";

					if ($this->shop->get_option('wpsg_productgroups_order') != '')
					{

						switch ($this->shop->get_option('wpsg_productgroups_order'))
						{

							case 'id':
								$strOrder .= ", P.`id` ASC ";
								break;
							case 'alphabetisch':
								$strOrder .= ", P.`name` ASC ";
								break;
							case 'buyed':
								$strOrder .= ", `buyed` DESC ";
								break;
							case 'erstellungsdatum':
								$strOrder .= ", `cdate` DESC ";
								break;
							case 'preis':
								$strOrder .= ", `preis` DESC ";
								break;

						}

					}

					if ($strOrder != "") $strOrder = " ORDER BY ".$strOrder;

					$arrProdukte = $this->db->fetchAssoc("SELECT
															P.`id`,
															(IF('".time()."' > PS.`von` AND '".time()."' < PS.`bis`, 1, 0)) AS sticky,
															(SELECT SUM(`menge`) FROM `".WPSG_TBL_ORDERPRODUCT."` AS OP WHERE OP.`p_id` = P.`id`) AS buyed
														FROM
															`".WPSG_TBL_PRODUCTS."` AS P
																	LEFT JOIN `".WPSG_TBL_PRODUCTS_STICKY."` AS PS ON (P.`id` = PS.`produkt_id`)
														WHERE
															P.`pgruppe` = '".wpsg_q($pg['id'])."' AND
															P.`lang_parent` = 0  AND
															P.`disabled` != '1' AND
															P.`deleted` = '0'
														".$strOrder."
													");

					// Bilder der Produkte
					foreach ($arrProdukte as $k2 => $p)
					{

						$arrProdukte[$k2] = $this->shop->loadProduktArray($p['id']);

						$arrProdukte[$k2]['bilder'] = $this->shop->imagehandler->getAttachmentIDs($p['id']);
						if (!wpsg_isSizedArray($arrProdukte[$k2]['bilder'])) $arrProdukte[$k2]['bilder'] = array();

					}
					$this->shop->view['data'][$pg['id']]['produkte'] = $arrProdukte;
					$this->shop->view['data'][$pg['id']]['pgruppe_name'] = $pg['name'];

				}

				$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/list.phtml', false);

			}

			return -2;

		}

		public function wpsg_mod_export_loadFields(&$arFields)
		{

			$arFields[20]['fields']['pgruppe_id'] = __('Produktgruppe ID', 'wpsg');
			$arFields[20]['fields']['pgruppe_name'] = __('Produktgruppe Name', 'wpsg');

		} // public function wpsg_mod_export_loadFields(&$arFields)

		public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator)
		{

			if (wpsg_isSizedInt($o_id) && !wpsg_isSizedInt($p_id))
			{

				$product_db = $this->db->fetchRow("
					SELECT
						GROUP_CONCAT(P.`pgruppe`) AS `pgruppe`,
						GROUP_CONCAT(PG.`name`) AS `pgruppe_name`
					FROM
						`".WPSG_TBL_ORDERPRODUCT."` AS OP
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (OP.`p_id` = P.`id`)
							LEFT JOIN `".WPSG_TBL_PRODUCTS_GROUP."` AS PG ON (PG.`id` = P.`pgruppe`)
					WHERE
						OP.`o_id` = '".wpsg_q($o_id)."'
					GROUP BY
						P.`id`
				");

			}
			else if (wpsg_isSizedInt($p_id))
			{

				$product_db = $this->shop->cache->loadProduct($p_id);
				$product_db['pgruppe_name'] = $this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($product_db['pgruppe'])."' ");

			}
			else
			{
				return;
			}

			switch ($field_value)
			{

				case 'pgruppe_id': $return = $product_db['pgruppe']; break;
				case 'pgruppe_name': $return = $product_db['pgruppe_name']; break;

			}

		} // public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator)

		/* -- */

		/**
		 * Gibt einen Array mit allen Produktgruppen zurück und beachtet die Übersetzung
		 */
		public function getAllProductGroups($withOrder = false)
		{
			
			if ($withOrder === true) {

				$strQueryWHERE = "";
				
				if ($this->shop->get_option('wpsg_showincompleteorder') !== '1') {
					
					$strQueryWHERE .= " AND O.`status` != '".wpsg_q(wpsg_ShopController::STATUS_UNVOLLSTAENDIG)."' ";
					
				}
				 
				$strQuery = "
					SELECT
						PG.`id`, PG.`name`
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`pgruppe` = PG.`id`)
							LEFT JOIN `".WPSG_TBL_ORDERPRODUCT."` AS OP ON (OP.`p_id` = P.`id`)
							LEFT JOIN `".WPSG_TBL_ORDER."` AS O ON (O.`id` = OP.`o_id`)
					WHERE
						PG.`deleted` != '1' AND
						O.`id` > 0 
						".$strQueryWHERE."
					GROUP BY
						PG.`id`
					ORDER BY
						PG.`name` ASC
				";
				 
				$arPG = $this->db->fetchAssocField($strQuery, "id", "name");
				
			} else {
				
				$arPG = $this->db->fetchAssocField("
					SELECT
						PG.`id`, PG.`name`
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
					WHERE
						PG.`deleted` != '1'
					ORDER BY
						PG.`name` ASC
				", "id", "name");
				
			}

			if ($this->shop->isOtherLang())
			{

				foreach ($arPG as $k => $pg)
				{

					$lang = @unserialize($pg['lang']);

					if (is_array($lang) && wpsg_isSizedString($lang[$this->shop->getCurrentLanguageCode()]['name'])) $arPG['name'] = $lang[$this->shop->getCurrentLanguageCode()]['name'];

				}

			}

			return $arPG;

		} // public function getAllProductGroups()

		public function dispatch() {

			if (isset($_REQUEST['wpsg_mod_action']) && $_REQUEST['wpsg_mod_action'] == "add") {
				
				wpsg_checkNounce('Productgroups', '', ['wpsg_mod_action' => 'add']);
				
				$this->addAction();
				
			} else if (isset($_REQUEST['wpsg_mod_action']) && $_REQUEST['wpsg_mod_action'] == "edit") {
				
				wpsg_checkNounce('Productgroups', '', ['wpsg_mod_action' => 'edit', 'edit_id' => $_REQUEST['edit_id']]);
				
				$this->editAction();
				
			} else if (isset($_REQUEST['wpsg_mod_action']) && $_REQUEST['wpsg_mod_action'] == "del") {
				
				wpsg_checkNounce('Productgroups', '', ['wpsg_mod_action' => 'del', 'edit_id' => $_REQUEST['edit_id']]);
				
				$this->delAction();
				
			} else if (isset($_REQUEST['wpsg_mod_action']) && $_REQUEST['wpsg_mod_action'] == "save") {
				
				\check_admin_referer('wpsg-productgroup-save-'.wpsg_getInt($_REQUEST['edit_id']));
				
				$this->saveAction();
				
			} else {
				$this->indexAction();
			}

		} // public function dispatch()

		public function indexAction() {
			
			if (isset($_REQUEST['submit-button'])) check_admin_referer('wpsg-mod-productgroups-search');

			$nPerPage = 25;
			if ($nPerPage <= 0) $nPerPage = 10;

			$this->shop->view['hasFilter'] = false;
			$this->shop->view['arFilter'] = array(
				'order' => 'cdate',
				'ascdesc' => 'ASC',
				'status' => '0',
				'page' => '1'
			);
			$this->shop->view['arData'] = array();
			$this->shop->view['pages'] = 1;

			if (wpsg_isSizedArray($_REQUEST['filter']))
			{

				$this->shop->view['arFilter'] = $_REQUEST['filter'];
				$this->shop->view['hasFilter'] = true;

			}
			else if (wpsg_isSizedArray($_SESSION['wpsg']['backend']['customergroup']['arFilter']))
			{

				//$this->shop->view['arFilter'] = $_SESSION['wpsg']['backend']['customer']['arFilter'];

			}

			$this->shop->view['countAll'] = wpsg_productgroup::count($this->shop->view['arFilter']);

			if (wpsg_isSizedInt($_REQUEST['seite'])) $this->shop->view['arFilter']['page'] = $_REQUEST['seite'];

			$this->shop->view['pages'] = ceil($this->shop->view['countAll'] / $nPerPage);
			if ($this->shop->view['arFilter']['page'] <= 0 || $this->shop->view['arFilter']['page'] > $this->shop->view['pages']) $this->shop->view['arFilter']['page'] = 1;

			$this->shop->view['arFilter']['limit'] = array(($this->shop->view['arFilter']['page'] - 1) * $nPerPage, $nPerPage);

			// Filter speichern
			$_SESSION['wpsg']['backend']['customergroup']['arFilter'] = $this->shop->view['arFilter'];

			$this->shop->view['arData'] = wpsg_productgroup::find($this->shop->view['arFilter']);

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/index.phtml');

		} // public function indexAction()

		public function delAction() {
			
			$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			$this->shop->addBackendMessage(__('Produktgruppe wurde erfolgreich gelöscht.', 'wpsg'));

			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Productgroups&wpsg_mod_action=index');

		} // public function delAction()

		public function saveAction() {
						
			$arTemplateFiles = [0] + $this->shop->loadProduktTemplates(true);
			
			$data = [];
			
			wpsg_checkRequest('name', [WPSG_SANITIZE_TEXTFIELD], __('Produktgruppenname'), $data);
			wpsg_checkRequest('template_file', [WPSG_SANITIZE_VALUES, $arTemplateFiles], __('Template'), $data);
			wpsg_checkRequest('infopage', [WPSG_SANITIZE_PAGEID], __('1nfo Seite'), $data);
			
			if ($this->shop->hasMod('wpsg_mod_stock')) {
				
				wpsg_checkRequest('stock_value', [WPSG_SANITIZE_INT, ['allowEmpty' => true]], __('Bestand'), $data, $_REQUEST['wpsg_mod_productgroups']['stock_value']);
				wpsg_checkRequest('stock_aktiv', [WPSG_SANITIZE_CHECKBOX], __('Lagerbestand zählen'), $data, $_REQUEST['wpsg_mod_productgroups']['stock_aktiv']);
				
			}
	 
			$arLang = [];
			
			if (wpsg_isSizedArray($_REQUEST['lang'])) {
				
				foreach ($_REQUEST['lang'] as $k => $l) {
					
					if (wpsg_checkInput($l, WPSG_SANITIZE_TEXTFIELD) && wpsg_checkInput($k, WPSG_SANITIZE_INT)) {
					
						$arLang[$k] = wpsg_xss($l);
						
					} 
					
				}
				
				$data['lang'] = wpsg_q(serialize($arLang));
				
			}

			if (wpsg_isSizedArray($data)) {
			
				if (wpsg_getStr($_REQUEST['edit_id']) > 0) {

					$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_GROUP, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
					$this->shop->addBackendMessage(__('Produktgruppe erfolgreich gespeichert.', 'wpsg'));
	
				} else {
	
					$data['cdate'] = "NOW()";
					
					$_REQUEST['edit_id'] = $this->db->ImportQuery(WPSG_TBL_PRODUCTS_GROUP, $data);
					
					$this->shop->addBackendMessage(__('Produktgruppe erfolgreich angelegt.', 'wpsg'));
	
				}
				
			}

			$this->shop->callMods('wpsg_mod_productgroups_save', array($_REQUEST['edit_id']));

			if (isset($_REQUEST['submit_index'])) $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Productgroups&wpsg_mod_action=index');
			else $this->shop->redirect(wpsg_admin_url('Productgroups', '', ['wpsg_mod_action' => 'edit', 'edit_id' => $_REQUEST['edit_id']]));

		} // public function saveAction()

		public function editAction() {

			$this->shop->view['data'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			$this->shop->view['data']['lang'] = @unserialize($this->shop->view['data']['lang']);
			if (!is_array($this->shop->view['data']['lang'])) $this->shop->view['data']['lang'] = array();
			$this->shop->view['languages'] = $this->shop->getStoreLanguages();

			$this->addeditAction();

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/add.phtml');

		} // public function editAction()

		public function addAction()
		{

			$this->addeditAction();
			$this->shop->view['data'] = array(
				'name' => '',
				'template_file' => '',
				'infopage' => ''
			);

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/add.phtml');

		} // public function addAction()

		public function addeditAction()
		{

			$this->shop->view['templates'] = array();
			$this->shop->view['templates'][0] = __('Individuelle Produkttemplates', 'wpsg');
			$this->shop->view['templates'] = wpsg_array_merge($this->shop->view['templates'], $this->shop->loadProduktTemplates(true));

			$pages = get_pages();
			$arPages = array(
				'-1' => __('Nicht zugeordnet', 'wpsg')
			);
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			$this->shop->view['pages'] = $arPages;

		} // public function addeditAction()

		public function checkPgroupsBestand($produkt, &$menge, &$menge_neu)
		{

			if (preg_match('/(pv_)|(\|(.*))/', $produkt['id']))
			{
				$pID = preg_replace('/(pv_)|(\|(.*))/', '', $produkt['id']);
			}
			else
			{
				$pID = $produkt['id'];
			}

			$g_id = intval($this->db->fetchOne("SELECT `pgruppe` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".$pID."'"));

			$pgroups_stock = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_GROUP."` WHERE `id` = '".$g_id."'");

			if ($pgroups_stock['stock_aktiv'] != '1') { return; }

			if ($menge_neu >= intval($pgroups_stock['stock_value']))
			{

				if ($this->shop->get_option('wpsg_mod_stock_allow') != '1')
				{
					$this->shop->addFrontendError(__('Menge überschreitet Warenbestand!', 'wpsg'));
				}
				else
				{
					$this->shop->addFrontendError(__('Menge wurde korrigiert, da sie den Warenbestand überschreitet!', 'wpsg'));
					$menge = intval($pgroups_stock['stock_value']);
					return -1;
				}
			}
		}

		public function wpsg_mod_productgroups_addedit_sidebar(&$productgroupdata)
		{

			$this->shop->view['wpsg_mod_productgroups']['data'] = $productgroupdata;

			if (wpsg_isSizedInt($this->shop->view['wpsg_mod_productgroups']['data']['stock_aktiv'])) { $this->shop->view['wpsg_mod_productgroups']['data']['stock_aktiv'] = true; }

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productgroups/productgroups_addedit_sidebar.phtml');

		} // public function wpsg_mod_productgroups_addedit_sidebar(&$productgroupdata)

	} // class wpsg_mod_productgroups extends wpsg_mod_basic

