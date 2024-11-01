<?php

	/**
	 * Dieses Modul erlaubt die Erstellung und Verwendung von Gutscheinen
	 */
	class wpsg_mod_gutschein extends wpsg_mod_basic
	{

		var $id = 160;
		var $lizenz = 1;
		var $hilfeURL = 'http://wpshopgermany.de/?p=455';

		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Gutscheine', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Erlaubt das Erstellen von Gutscheinen.', 'wpsg');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Tabelle für die Gutscheine
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_GUTSCHEIN." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		code VARCHAR(255) NOT NULL,
		   		coupon INT(1) NOT NULL COMMENT '1 bei Wertgutscheinen',
		   		value DOUBLE(10,2) NOT NULL, 
		   		o_id INT(11) NOT NULL,
		   		calc_typ ENUM('w', 'p') NOT NULL,
		   		cdate datetime NOT NULL,
		   		start_date datetime NOT NULL,
		   		end_date datetime NOT NULL,
		   		multi INT(11) NOT NULL,
		   		comment TEXT NOT NULL,
		   		autocreate_order INT(11) NOT NULL,
		   		autocreate_product INT(11) NOT NULL,
				autocreate_order_product INT(11) NOT NULL,
		   		minvalue DOUBLE(10,2) NOT NULL,
				productgroups TEXT NOT NULL,
				products TEXT NOT NULL,
		   		PRIMARY KEY  (`id`),
		   		KEY o_id (o_id),
		   		KEY autocreate_order (autocreate_order),
		   		KEY autocreate_product (autocreate_product)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);
			
			/** Tabelle für die Zuordnung Bestellung <=> Gutschein */
			$sql = "CREATE TABLE ".WPSG_TBL_ORDER_VOUCHER." (
				id int(11) NOT NULL AUTO_INCREMENT,
				create_time DATETIME NOT NULL,
				edit_time DATETIME NOT NULL,
				order_id INT(11) NOT NULL,
				voucher_id INT(11) NOT NULL,
				set_value VARCHAR(100) NOT NULL, 
				sum_netto DOUBLE(10,2) NOT NULL,
				sum_brutto DOUBLE(10,2) NOT NULL,
				tax_key VARCHAR(10) NOT NULL,
				bruttonetto INT(1) NOT NULL,
				code VARCHAR(1000) NOT NULL,
				coupon INT(1) NOT NULL,
				PRIMARY KEY  (`id`),
				KEY order_id (order_id),
				KEY voucher_id (voucher_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);
			
			$this->shop->checkDefault('wpsg_mod_gutschein_size', '10');
			$this->shop->checkDefault('wpsg_mod_gutschein_perPage', '25');
			
		} // public function install()

		public function init()
		{

			// Modell einbinden
			require_once(WPSG_PATH_MOD.'mod_gutschein/wpsg_voucher.php');

			$role_object = get_role('administrator');
			$role_object->add_cap('wpsg_voucher');

		} // public function init()

		public function dispatch() {

			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {

				$this->addAction();

			} if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {

                $this->editAction();

            } else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'import') {

				wpsg_checkNounce('Voucher','import'); 
				
				$this->importAction();

			} else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {

				$this->saveAction();

			} else {

				$this->indexAction();

			}

		} // public function dispatch()

		public function admin_setcapabilities() {

			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/admin_setcapabilities.phtml');

		} // public function admin_setcapabilities()

		public function wpsg_add_pages($default_page)
		{

			add_submenu_page($default_page, __("Gutscheine", "wpsg"), __("Gutscheine", "wpsg"), 'wpsg_voucher', 'wpsg-Voucher', array($this, 'dispatch'));

		} // public function wpsg_add_pages()
		
		public function calculation_fromSession(&$oCalculation, $product_done, $payship_done) { 
		 
			if ($product_done && $payship_done) {
				
				if (wpsg_isSizedArray($_SESSION['wpsg']['gs'])) {
					
					foreach ($_SESSION['wpsg']['gs'] as $gs) {
						
						$oVoucher = wpsg_voucher::getInstance($gs['id']);
						
						if ($oVoucher->isUsabel()) {
						
							if ($oVoucher->isCoupon()) {
								
								$oCalculation->addCoupon($oVoucher->getFreeAmount(), $this->shop->getBackendTaxview(), '0', 1, $gs['code'], $gs['id']);
								
							} else {
							
								if ($gs['calc'] === 'p') $gs['value'] = $gs['value'].'%';
								else $gs['value'] = $oVoucher->getFreeAmount();
							
								$oCalculation->addVoucher($gs['value'], $this->shop->getBackendTaxview(), '0', 1, $gs['code'], $gs['id']);
								
							}
							
						}
						
					}
					 
				}
				
			}
			
		}
				
		/*
        public function editAction() {

            $this->shop->view['mod_gutschein'] = array();

            $oVoucher = wpsg_voucher::getInstance($_REQUEST['edit_id']);
            
            $this->shop->view['mod_gutschein']['value'] = $oVoucher->value;;
            $this->shop->view['mod_gutschein']['calc'] = $oVoucher->calc_typ;;
            $this->shop->view['mod_gutschein']['start'] = date('d.m.Y H:i:s', strtotime($oVoucher->start_date));
            $this->shop->view['mod_gutschein']['end'] = date('d.m.Y H:i:s', strtotime($oVoucher->end_date));
            $this->shop->view['mod_gutschein']['count'] = $oVoucher->count;
            $this->shop->view['mod_gutschein']['gen'] = $oVoucher->gen;
            $this->shop->view['mod_gutschein']['code'] = $oVoucher->code;
            $this->shop->view['mod_gutschein']['comment'] = $oVoucher->comment;
            $this->shop->view['mod_gutschein']['multi'] = $oVoucher->multi;
            $this->shop->view['mod_gutschein']['minvalue'] = $oVoucher->minvalue;
            $this->shop->view['mod_gutschein']['products'] = explode(',', $oVoucher->products);
            $this->shop->view['mod_gutschein']['productgroups'] = explode(',', $oVoucher->productgroups);
            $this->shop->view['edit_id'] = $_REQUEST['edit_id'];

            if ($this->shop->hasMod('wpsg_mod_productgroups')) {

                $this->shop->view['wpsg_mod_gutschein']['arProductGroups'] = $this->shop->callMod('wpsg_mod_productgroups', 'getAllProductGroups');

            }
            
            $this->shop->view['wpsg_mod_gutschein']['arProducts'] = $this->shop->getAllProductsForSelect();

            $this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/add.phtml');
            
        } */
        
		/**
		 * Maske für einen neuen Gutschein
		 *
		public function addAction() {

			$this->shop->view['mod_gutschein'] = array();

			if (isset($_SESSION['wpsg']['mod_gutschein_error'])) {

				$this->shop->view['mod_gutschein']['value'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_value'];
				$this->shop->view['mod_gutschein']['calc'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_calc'];
				$this->shop->view['mod_gutschein']['start'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_start'];
				$this->shop->view['mod_gutschein']['end'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_end'];
				$this->shop->view['mod_gutschein']['count'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_count'];
				$this->shop->view['mod_gutschein']['gen'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_gen'];
				$this->shop->view['mod_gutschein']['code'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_code'];
				$this->shop->view['mod_gutschein']['comment'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_comment'];
				$this->shop->view['mod_gutschein']['multi'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_multi'];
				if (isset($_REQUEST['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_minvalue'])) $this->shop->view['mod_gutschein']['minvalue'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_minvalue'];
				if (isset($_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_products'])) $this->shop->view['mod_gutschein']['products'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_products'];
				if (isset($_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_productgroups'])) $this->shop->view['mod_gutschein']['productgroups'] = $_SESSION['wpsg']['mod_gutschein_error']['wpsg_mod_gutschein_productgroups'];

				unset($_SESSION['wpsg']['mod_gutschein_error']);

			} else {

				$this->shop->view['mod_gutschein']['value'] = 10;
				$this->shop->view['mod_gutschein']['calc'] = 'w';
				$this->shop->view['mod_gutschein']['count'] = '1';
				$this->shop->view['mod_gutschein']['multi'] = '0';
				$this->shop->view['mod_gutschein']['productgroups'] = '-1';

			}

			if ($this->shop->hasMod('wpsg_mod_productgroups'))
			{

				$this->shop->view['wpsg_mod_gutschein']['arProductGroups'] = $this->shop->callMod('wpsg_mod_productgroups', 'getAllProductGroups');

			}

			$this->shop->view['wpsg_mod_gutschein']['arProducts'] = $this->shop->getAllProductsForSelect();

			if (!isset($this->shop->view['mod_gutschein']['start']) || !is_int($this->view['mod_gutschein']['start']) || $this->view['mod_gutschein']['start'] <= 0)
				$this->shop->view['mod_gutschein']['start'] = wpsg_date('d.m.Y H:i');

			if (!isset($this->shop->view['mod_gutschein']['end']) || !is_int($this->view['mod_gutschein']['end']) || $this->view['mod_gutschein']['end'] <= 0)
				$this->shop->view['mod_gutschein']['end'] = wpsg_date('d.m.').(wpsg_date('Y') + 1).' '.wpsg_date('H:i');

			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/add.phtml');

		} // public function addAction() */
		
		public function be_ajax() {
			
			if (wpsg_isSizedString($_REQUEST['subaction'], 'addedit')) $this->be_ajax_addedit();
			else if (wpsg_isSizedString($_REQUEST['subaction'], 'save')) $this->be_ajax_save();
			else if (wpsg_isSizedString($_REQUEST['subaction'], 'delete')) $this->be_ajax_delete();
			
			exit;
			
		}
		
		public function be_ajax_save() {
			
			$bError = false;
			
			parse_str($_REQUEST['form_data'], $form_data);
			
			try {
				
				if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_start'], WPSG_SANITIZE_DATETIME)) throw new \Exception(__('Eingabefehler in Feld "Gültig ab"', 'wpsg'));
				if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_end'], WPSG_SANITIZE_DATETIME)) throw new \Exception(__('Eingabefehler in Feld "Gültig bis"', 'wpsg'));
				if (!wpsg_isSizedInt($form_data['voucher_id']) && (!wpsg_checkInput($form_data['wpsg_mod_gutschein_count'], WPSG_SANITIZE_INT) || !wpsg_isSizedInt($form_data['wpsg_mod_gutschein_count']))) throw new \Exception(__('Eingabefehler in Feld "Menge"', 'wpsg'));
				
				$t_start = strtotime($form_data['wpsg_mod_gutschein_start']);
				$t_end = strtotime($form_data['wpsg_mod_gutschein_end']);
				
				if ($t_start <= 0 || $t_end <= 0 || $t_end <= $t_start) throw new \Exception(__('Eingaben in "Gültig ab" und "Gültig bis" überprüfen.', 'wpsg'));
				
				if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_value'], WPSG_SANITIZE_FLOAT) || wpsg_tf($form_data['wpsg_mod_gutschein_value']) <= 0) throw new \Exception(__('Eingaben im Feld "Wert" überprüfen.', 'wpsg'));
				
				if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_comment'], WPSG_SANITIZE_TEXTAREA)) throw new \Exception(__('Ungültige Eingaben im Feld "Kommentar"', 'wpsg'));
				if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_minvalue'], WPSG_SANITIZE_FLOAT)) throw new \Exception(__('Ungültige Eingaben im Feld "Minimaler Warenwert"', 'wpsg'));
				
				if (
					!wpsg_checkInput($form_data['wpsg_mod_gutschein_calc'], WPSG_SANITIZE_VALUES, ['p', 'w']) ||
					!wpsg_checkInput($form_data['wpsg_mod_gutschein_multi'], WPSG_SANITIZE_CHECKBOX) ||
					!wpsg_checkInput($form_data['wpsg_mod_gutschein_coupon'], WPSG_SANITIZE_CHECKBOX) ||
					(isset($form_data['wpsg_mod_gutschein_productgroups']) && !wpsg_checkInput($form_data['wpsg_mod_gutschein_productgroups'], WPSG_SANITIZE_ARRAY_INT)) ||
					(isset($form_data['wpsg_mod_gutschein_products']) && !wpsg_checkInput($form_data['wpsg_mod_gutschein_products'], WPSG_SANITIZE_ARRAY_INT)) || 
					(isset($form_data['voucher_id']) && !wpsg_checkInput($form_data['voucher_id'], WPSG_SANITIZE_INT)) || 
					(!isset($form_data['voucher_id']) && !wpsg_checkInput($form_data['wpsg_mod_gutschein_gen'], WPSG_SANITIZE_VALUES, ['0', '1']))
				) throw new \Exception(__('Eingabevalidierungsfehler!', 'wpsg'));
									
				if (isset($form_data['voucher_id'])) {
										
					$this->db->UpdateQuery(WPSG_TBL_GUTSCHEIN, [
						'code' => wpsg_q($form_data['wpsg_mod_gutschein_code']),
						'coupon' => wpsg_q($form_data['wpsg_mod_gutschein_coupon']),
						'value' => wpsg_q(wpsg_tf($form_data['wpsg_mod_gutschein_value'])),						
						'calc_typ' => wpsg_q($form_data['wpsg_mod_gutschein_calc']),
						'start_date' => wpsg_q(date('Y-m-d H:i:s', $t_start)),
						'end_date' => wpsg_q(date('Y-m-d H:i:s', $t_end)),
						'multi' => wpsg_q($form_data['wpsg_mod_gutschein_multi']),
						'comment' => wpsg_q($form_data['wpsg_mod_gutschein_comment']),
						'minvalue' => wpsg_q(wpsg_tf($form_data['wpsg_mod_gutschein_minvalue'])),
						'productgroups' => wpsg_q(implode(',', wpsg_getArray($form_data['wpsg_mod_gutschein_productgroups']))),
						'products' => wpsg_q(implode(',', wpsg_getArray($form_data['wpsg_mod_gutschein_products']))),
					], " `id` = '".wpsg_q($form_data['voucher_id'])."' ");
					
					$this->shop->addBackendMessage(__('Gutschein erfolgreich gespeichert.', 'wpsg'));
										
				} else {
					
					$arProductGroups = array(); if (wpsg_isSizedArray($form_data['wpsg_mod_gutschein_productgroups'])) $arProductGroups = $form_data['wpsg_mod_gutschein_productgroups'];
					$arProducts = array(); if (wpsg_isSizedArray($form_data['wpsg_mod_gutschein_products'])) $arProducts = $form_data['wpsg_mod_gutschein_products'];
					
					for ($i = 0; $i < $form_data['wpsg_mod_gutschein_count']; $i ++) {
						
						if ($form_data['wpsg_mod_gutschein_gen'] == '1') {
														
							if (!wpsg_checkInput($form_data['wpsg_mod_gutschein_code'], WPSG_SANITIZE_APIKEY)) throw new \Exception(__('Ungültige Eingaben im Feld "Code"', 'wpsg'));
							
							$setCode = $form_data['wpsg_mod_gutschein_code'];
							
						} else {
							
							$setCode = false;
							
						}
						
						if ($form_data['wpsg_mod_gutschein_coupon'] === '1') $isCoupon = true;
						else $isCoupon = false;
						
						$this->genGS(
							wpsg_tf($form_data['wpsg_mod_gutschein_value']),
							$form_data['wpsg_mod_gutschein_calc'],
							$t_start,
							$t_end,
							$form_data['wpsg_mod_gutschein_multi'],
							$this->shop->get_option('wpsg_mod_gutschein_size'),
							$form_data['wpsg_mod_gutschein_comment'],
							0,
							0,
							0,
							$arProductGroups,
							$arProducts,
							wpsg_tf($form_data['wpsg_mod_gutschein_minvalue']),
							$setCode,
							$isCoupon
						);
						
					}
					
					if ($form_data['wpsg_mod_gutschein_count'] > 1) {
						
						$this->shop->addBackendMessage(__('Gutscheine wurden erfolgreich angelegt.', 'wpsg'));
						
					} else {
						
						$this->shop->addBackendMessage(__('Gutschein wurde erfolgreich angelegt.', 'wpsg'));
						
					}
					
				}
				
			} catch (\Exception $e) {
				
				echo $e->getMessage(); exit;
				
			}
						
			echo "1"; exit;
			
		}
		
		/**
		 * @throws Exception
		 */
		public function be_ajax_addedit() {
			
			if ($this->shop->hasMod('wpsg_mod_productgroups')) {
				
				$this->shop->view['wpsg_mod_gutschein']['arProductGroups'] = $this->shop->callMod('wpsg_mod_productgroups', 'getAllProductGroups');
				
			}
			
			$this->shop->view['wpsg_mod_gutschein']['arProducts'] = $this->shop->getAllProductsForSelect();
			
			if (isset($_REQUEST['voucher_id'])) {
				 
				if (!wpsg_checkInput($_REQUEST['voucher_id'], WPSG_SANITIZE_INT)) throw new \Exception(__('Fehler in übergebenenem Parameter!', 'wpsg'));
				
				$oVoucher = wpsg_voucher::getInstance($_REQUEST['voucher_id']);
				
				$this->shop->view['edit_id'] = $oVoucher->getId();
				$this->shop->view['mod_gutschein'] = [					
					'value' => $oVoucher->__get('value'),
					'coupon' => $oVoucher->__get('coupon'),
					'calc' => $oVoucher->__get('calc_typ'),
					'start' => date('d.m.Y', strtotime($oVoucher->start_date)),
					'end' => date('d.m.Y', strtotime($oVoucher->end_date)),
					'code' => $oVoucher->__get('code'),
					'minvalue' => $oVoucher->__get('minvalue'),
					'multi' => $oVoucher->__get('multi'),
					'comment' => $oVoucher->__get('comment'),
					'productgroups' => explode(',', $oVoucher->__get('productgroups')),
					'products' => explode(',', $oVoucher->__get('products')),
				];
				
			}
			
			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/add.phtml');
			
		}
		
		public function be_ajax_delete() {
			
			if (!wpsg_checkInput($_REQUEST['voucher_id'], WPSG_SANITIZE_INT)) throw new \Exception(__('Parameterfehler'));
			
			$oVoucher = wpsg_voucher::getInstance(intval($_REQUEST['voucher_id']));
			$oVoucher->delete();
			
			$this->shop->addBackendMessage(__('Gutschein wurde erfolgreich gelöscht.', 'wpsg'));
			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher');
			
		}
		
		/**
		 * CSV Import von Gutscheinen
		 */
		public function importAction()
		{

			if (isset($_REQUEST['submit']))
			{

				// Datei angegeben?
				if (!file_exists($_FILES['wpsg_file']['tmp_name']) || !preg_match('/(.*)\.csv$/i', $_FILES['wpsg_file']['name']))
				{

					$this->shop->addBackendError(__('Bitte eine CSV Datei angeben!', 'wpsg'));
					$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher&action=import');

				}

				$arData = array();

				if (($handle = fopen($_FILES['wpsg_file']['tmp_name'], "r")) !== false)
				{

					while (($data = fgetcsv($handle, 1000, ";")) !== false)
					{

						$arData[] = $data;

					}

					fclose($handle);

				}

				if (!wpsg_isSizedArray($arData))
				{

					$this->shop->addBackendError(__('CSV Datei konnte nicht geladen werden!', 'wpsg'));
					$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher&action=import');

				}

				// Spalten identifizieren
				$arData[0][0] = wpsg_removeBOM($arData[0][0]);
				$arCol = array_flip($arData[0]);
				$arColPflicht = array('code', 'value', 'start_date', 'end_date');
				$bColError = false;

				foreach ($arColPflicht as $v)
				{

					if (!isset($arCol[$v]))
					{

						$this->shop->addBackendError(wpsg_translate(
							__('CSV Datei ungültig. (Spalte "#1#" muss vorhanden sein)', 'wpsg'),
							$v)
						);

						$bColError = true;

					}

				}

				if ($bColError === true)
				{

					$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher&action=import');

				}

				$nImport = 0;

				// Daten importieren / Erste Zeile sind die Schlüssel
				for ($i = 1; $i < sizeof($arData); $i ++)
				{

					// Leere Zeilen überspringen
					if (!wpsg_isSizedArray($arData[$i])) continue;

					if (!preg_match('/\d{4}\-\d{2}\-\d{2}\040\d{2}\:\d{2}\:\d{2}/', $arData[$i][$arCol['start_date']]))
					{

						$this->shop->addBackendError(wpsg_translate(
							__('Spalte "#1#" in Zeile #2# enthält kein gültiges Datum -> Zeile wurde übersprungen', 'wpsg'),
							'start_date',
							$i + 1)
						);

						continue;

					}

					if (!preg_match('/\d{4}\-\d{2}\-\d{2}\040\d{2}\:\d{2}\:\d{2}/', $arData[$i][$arCol['end_date']]))
					{

						$this->shop->addBackendError(wpsg_translate(
							__('Spalte "#1#" in Zeile #2# enthält kein gültiges Datum -> Zeile wurde übersprungen', 'wpsg'),
							'end_date',
							$i + 1)
						);

						continue;

					}

					if (strlen($arData[$i][$arCol['code']]) < $this->shop->get_option('wpsg_mod_gutschein_size'))
					{

						$this->shop->addBackendError(wpsg_translate(
							__('Ungültiger Gutscheincode in Zeile #1# (zu Kurz) -> Zeile wurde übersprungen', 'wpsg'),
							$i + 1)
						);

						continue;

					}

					if (doubleval($arData[$i][$arCol['value']]) <= 0)
					{

						$this->shop->addBackendError(wpsg_translate(
							__('Ungültiger Gutscheinwert in Zeile #1# (kleiner gleich 0) -> Zeile wurde übersprungen', 'wpsg'),
							$i + 1)
						);

						continue;

					}

					// Existiert schon ein Gutschein mit dem Code
					$bExists = $this->db->fetchOne("SELECT G.`id` FROM `".WPSG_TBL_GUTSCHEIN."` AS G WHERE G.`code` = '".wpsg_q($arData[$i][$arCol['code']])."'");

					$doImport = false;

					if ($bExists > 0)
					{

						// Existiert schon
						if ($_REQUEST['gs_exists'] == '0')
						{

							$this->shop->addBackendMessage(wpsg_translate(
								__('Code aus Zeile #1# existierte schon und wurde überschrieben.', 'wpsg'),
								$i + 1)
							);

							$doImport = true;

						}
						else
						{

							$this->shop->addBackendMessage(wpsg_translate(
								__('Code (#1#) aus Zeile #2# existiert schon -> Zeile wurde übersprungen.', 'wpsg'),
								$arData[$i][$arCol['code']],
								$i + 1)
							);

						}

					}
					else
					{

						$nImport ++;
						$doImport = true;

					}

					if ($doImport === true)
					{

						if ($bExists > 0) $this->db->Query("DELETE FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` = '".wpsg_q($bExists)."'");

						$data = array(
							'code' => wpsg_q($arData[$i][$arCol['code']]),
							'value' => wpsg_q(wpsg_tf($arData[$i][$arCol['value']])),
							'start_date' => wpsg_q($arData[$i][$arCol['start_date']]),
							'end_date' => wpsg_q($arData[$i][$arCol['end_date']])
						);

						if ($arData[$i][$arCol['calc_typ']] == 'p') $data['calc_typ'] = 'p'; else $data['calc_typ'] = 'w';
						if ($arData[$i][$arCol['multi']] == '1') $data['multi'] = '1'; else $data['multi'] = '0';
						if (isset($arCol['products']) && wpsg_isSizedString($arData[$i][$arCol['products']])) $data['products'] = $arData[$i][$arCol['products']]; else $data['products'] = '';
						if (isset($arCol['productgroups']) && wpsg_isSizedString($arData[$i][$arCol['productgroups']])) $data['productgroups'] = $arData[$i][$arCol['productgroups']]; else $data['productgroups'] = '';
						if (isset($arCol['comment']) && wpsg_isSizedString($arData[$i][$arCol['comment']])) $data['comment'] = $arData[$i][$arCol['comment']]; else $data['comment'] = '';

						$this->db->ImportQuery(WPSG_TBL_GUTSCHEIN, $data);

					}

				} // for data

				if ($nImport == 1) $this->shop->addBackendMessage(__('Ein Gutschein erfolgreich importiert.', 'wpsg'));
				else if ($nImport > 1) $this->shop->addBackendMessage(wpsg_translate(__('#1# Gutscheine erfolgreich importiert.', 'wpsg'), $nImport));
				else $this->shop->addBackendMessage(__('Keine Gutscheine importiert.', 'wpsg'));

				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher');

			}

			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/import.phtml');

		} // public function importAction()
		
		public function mail_order_end(&$arCalculation, $html) {
			
			if ($arCalculation['sum']['topay_brutto'] !== $arCalculation['sum']['brutto'] && wpsg_isSizedArray($arCalculation['coupon'])) {
				
				if ($html) $this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/mail_order_end_html.phtml');
				else $this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/mail_order_end.phtml');
				
			}
			
		}
		
		public function basket_row_end_coupon() {
			
			$arCalculation = &$this->shop->view['basket']['arCalculation'];
			
			if ($arCalculation['sum']['topay_brutto'] !== $arCalculation['sum']['brutto'] && wpsg_isSizedArray($arCalculation['coupon'])) {
				
				$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/basket_row_end_coupon.phtml');
				
			}
			
		}
		
		public function overview_row_end_coupon() {
			
			$arCalculation = &$this->shop->view['basket']['arCalculation'];
			
			if ($arCalculation['sum']['topay_brutto'] !== $arCalculation['sum']['brutto'] && wpsg_isSizedArray($arCalculation['coupon'])) {
				
				$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/overview_row_end_coupon.phtml');
				
			}
			
		}
		
		public function basket_row_end(&$basket_view)
		{

			if ($this->shop->get_option('wpsg_mod_gutschein_hideInsert') == '1') return;

			$this->view['error'] = $basket_view['error'];

			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/basket_row_end.phtml');

		} // public function basket_row_end(&$basket)

		public function basket_preInsert()
		{

			if (!wpsg_isSizedArray($_SESSION['wpsg']['gs'])) return;

			$gs_db = $this->db->fetchRow("SELECT G.`id`, G.`productgroups`, G.`products` FROM `".WPSG_TBL_GUTSCHEIN."` AS G WHERE G.`id` = '".wpsg_q($_SESSION['wpsg']['gs']['id'])."'");

			if ($gs_db['id'] != $_SESSION['wpsg']['gs']['id']) throw new \Exception(__('In der Session befindet sich eine Gutschein ID, die es nicht in der Datenbank gibt', 'wpsg'));

			$product_id = $this->shop->getProduktID($_REQUEST['wpsg']['produkt_id']);

			$product_allowed = wpsg_explode(',', $gs_db['products']);

			$temp = wpsg_explode(',', $gs_db['productgroups']);
			if (wpsg_isSizedArray($temp))
			{

				$ProductGroupIDs = wpsg_explode(',', $gs_db['productgroups']);
				$product_allowed = array_merge($product_allowed, $this->db->fetchAssocField("
					SELECT P.`id` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`pgruppe` IN (".wpsg_implode(',', $ProductGroupIDs).")
				"));

			}

			$product_allowed = wpsg_trim($product_allowed);

			if (wpsg_isSizedArray($product_allowed) && !in_array($product_id, $product_allowed))
			{

				$product_data = $this->shop->loadProduktArray($product_id);
				$this->shop->addFrontendError(wpsg_translate(__('Gutschein wurde entfernt, da er nicht mit Produkt #1# verwendet werden kann', 'wpsg'), $product_data['name']));
				unset($_SESSION['wpsg']['gs']);

			}

		} // public function basket_preInsert()

		public function basket_afterRemove()
		{

			$arBasket = $this->shop->cache->getShopBasketArray();

			$this->_checkBasket($this->shop->cache->getShopBasketArray());

		} // public function basket_afterRemove()

		public function basket_afterUpdate(&$bError)
		{

			$this->_checkBasket($this->shop->cache->getShopBasketArray());

		} // public function basket_afterUpdate(&$bError)

		public function checkCheckout(&$state, &$error, &$arCheckout)
		{

			if (!$this->_checkBasket($this->shop->cache->getShopBasketArray())) $error = true;

		} // public function checkCheckout(&$state, &$error, &$arCheckout)

		/* Modulfunktionen */
 
		/**
		 * Prüft den Basket und entfernt den Gutschein, sollte irgendetwas nicht passen
		 */
		private function _checkBasket($arBasket)
		{

			if (!wpsg_isSizedArray($arBasket['gs'])) return true;
			if (!wpsg_isSizedInt($_SESSION['wpsg']['gs']['id']))  throw new \wpsg\Exception(__('In der Session ist keine ID für den Gutschein im Basket Array, sollte nicht passieren', 'wpsg'));

			$gs_db = $this->db->fetchRow("SELECT G.`id`, G.`minvalue` FROM `".WPSG_TBL_GUTSCHEIN."` AS G WHERE G.`id` = '".wpsg_q($_SESSION['wpsg']['gs']['id'])."'");

			if ($gs_db['id'] != $_SESSION['wpsg']['gs']['id']) throw new \wpsg\Exception(__('In der Session befindet sich eine Gutschein ID, die es nicht in der Datenbank gibt', 'wpsg'));
 
			if ($this->shop->get_option('wpsg_mod_discount_voucher') === '1' && $this->shop->callMod('wpsg_mod_discount', 'hasDiscount',[$arBasket])) {
				
				$this->shop->addFrontendError(wpsg_translate(__('Gutschein wurde entfernt, da ein Rabatt angewendet wurde.', 'wpsg')));
				unset($_SESSION['wpsg']['gs']);
				
				return false;
				
			} 
							
			if (wpsg_tf($gs_db['minvalue']) > 0 && ($arBasket['sum']['preis'] + $arBasket['sum']['gs']) < $gs_db['minvalue'])
			{

				$this->shop->addFrontendError(wpsg_translate(__('Gutschein wurde entfernt, da der Mindestbestellwert von #1# unterschritten wurden.', 'wpsg'), wpsg_ff($gs_db['minvalue'], $this->shop->get_option('wpsg_currency'))));
				unset($_SESSION['wpsg']['gs']);

				return false;

			}

			return true;

		} // private function _checkBasket($arBasket)

		/**
		 * Generiert einen Gutschein in der Datenbank
		 *
		 * @param double  $value              der Wert des Gutscheins
		 * @param varchar $calc_typ           Typ 'p' = Prozentual, 'w' = Absoluter Wert
		 * @param int     $tStart             Timestamp Start der Gültigkeit
		 * @param int     $tEnd               Timestamp Ende der Gültigkeit
		 * @param int     $multi              1 = Mehrfach, 0 = Einmalig
		 * @param int     $laenge             Länge des Gutscheins
		 * @param String  $comment            Kommentar
		 * @param int     $autocreate_order   ID Der Bestellung aus der der Gutschein generiert wurde
		 * @param int     $autocreate_product ID Des Produkts aus dem der Gutschein generiert wurde
		 * @param <int>Array $productgroups Array mit IDs von Produktgruppen für die der Gutschein gültig ist
		 * @param <int>Array $products Array mit IDs von Produkten für die der Gutschein gültig ist
		 *
		 * @return Der generierte Code
		 * @throws \wpsg\Exception
		 */
		public function genGS($value, $calc_typ, $tStart, $tEnd, $multi, $laenge, $comment, $autocreate_order = 0, $autocreate_product = 0, $autocreate_order_product, $productgroups = array(), $products = array(), $minValue = false, $code = false, $isCoupon = false)
		{

			$value = wpsg_tf($value);

			if (wpsg_isSizedString($code))
			{

				$strCode = $code;

			}
			else
			{

				$laenge = get_option('wpsg_mod_gutschein_size');
				if ($laenge <= 0) $size = 10;

				$zeichen = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";

				while (true)
				{

					$arCode = array();

					for ($i = 1; $i <= $laenge; $i++) { $arCode[] = $zeichen[rand(0, (strlen($zeichen) - 1))]; }

					$strCode = implode('', @$arCode);

					$bExists = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `code` = '".wpsg_q($strCode)."'");

					if ($bExists <= 0) break;
					break;

				}

			}

			$this->db->ImportQuery(WPSG_TBL_GUTSCHEIN, array(
				'code' => wpsg_q($strCode),
				'value' => wpsg_q($value),
				'minvalue' => wpsg_q(wpsg_tf($minValue)),
				'calc_typ' => wpsg_q($calc_typ),
				'cdate' => 'NOW()',
				'start_date' => wpsg_q(date('Y-m-d H:i:00', $tStart)),
				'end_date' => wpsg_q(date('Y-m-d H:i:00', $tEnd)),
				'multi' => wpsg_q($multi),
				'comment' => wpsg_q($comment),
				'autocreate_order' => wpsg_q($autocreate_order),
			 	'autocreate_product' => wpsg_q($autocreate_product),
				'autocreate_order_product' => wpsg_q($autocreate_order_product),
				'productgroups' => wpsg_q(wpsg_implode(',', $productgroups)),
				'products' => wpsg_q(wpsg_implode(',', $products)),
				'coupon' => (($isCoupon === true)?'1':'0')
			));

			return $strCode;

		} // public function genGS($value, $calc_typ, $tStart, $tEnd, $multi, $laenge)

		/**
		 * Ist für die Übersicht der Gutscheine zuständig
		 */
		public function indexAction()
		{

			
			if (isset($_REQUEST['wpshopgermany-submit-gs_del']))
			{

				if (sizeof($_REQUEST['wpsg_gs_cb']) > 0)
				{

					$this->db->Query("DELETE FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` IN (".implode(",", array_values($_REQUEST['wpsg_gs_cb'])).")");

				}

				$this->shop->addBackendMessage(__('Gutscheine erfolgreich gelöscht.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher');

			}

			// Suchstring aufbauen
			$strQueryWHERE = "";

			$this->shop->view['filter'] = array(
				'search' => ''
			);

			if (isset($_REQUEST['wpsg_search']))
			{

				$this->shop->view['filter']['search'] = $_REQUEST['wpsg_search'];
				$strQueryWHERE .= " AND `code` LIKE '%".wpsg_q($_REQUEST['wpsg_search'])."%' ";

			}

			// Gefilterten Status für das View definieren
			if (isset($_REQUEST['wpsg_status']) && $_REQUEST['wpsg_status'] >= 0)
			{
				$this->shop->view['filter']['status'] = $_REQUEST['wpsg_status'];
			}

			$strQueryORDER = '';

			if (!isset($_REQUEST['filter']['order']))
			{

				$this->shop->view['filter']['order'] = 'id';

			}
			else
			{

				$this->shop->view['filter']['order'] = $_REQUEST['filter']['order'];

			}

			switch ($this->shop->view['filter']['order'])
			{

				case 'code': $strQueryORDER = "G.`code`"; break;
				case 'start_date': $strQueryORDER = "G.`start_date`"; break;
				case 'end_date': $strQueryORDER = "G.`end_date`"; break;
				case 'value': $strQueryORDER = "G.`value`"; break;
				default: $strQueryORDER = "G.`id`"; break;

			}

			if (!isset($_REQUEST['filter']['ascdesc']))
			{

				$this->shop->view['filter']['ascdesc'] = 'asc';

			}
			else
			{

				$this->shop->view['filter']['ascdesc'] = $_REQUEST['filter']['ascdesc'];

			}

			// Alle gefilterten holen
			$arDataAll = $this->db->fetchAssoc("
				SELECT
					G.*
				FROM
					`".WPSG_TBL_GUTSCHEIN."` AS G
				WHERE
					1
					".$strQueryWHERE."
				ORDER BY
					".wpsg_q($strQueryORDER)." ".wpsg_q($this->shop->view['filter']['ascdesc'])."
			");

			$this->shop->view['arStatus'] = array();
			$this->shop->view['countAll'] = sizeof($arDataAll);

			foreach ($arDataAll as $k => $v)
			{

				$tStart = strtotime($v['start_date']);
				$tEnd = strtotime($v['end_date']);

				if ($tStart < wpsg_time() && $tEnd > wpsg_time())
				{
					$nStatus = 1; $strStatus = __('Aktiv', 'wpsg');
				}
				else if ($tStart > wpsg_time())
				{
					$nStatus = 2; $strStatus = __('Wartend', 'wpsg');
				}
				else if ($tEnd < wpsg_time())
				{
					$nStatus = 3; $strStatus = __('Ausgelaufen', 'wpsg');
				}
				else
				{

					// Sollte eignetlich nich auftreten
					$nStatus = 999; $strStatus = __('Ungültig', 'wpsg');

				}

				if ($v['multi'] == '0' && $v['o_id'] > 0)
				{

					$nStatus = 4; $strStatus = __('Verbraucht', 'wpsg');

				}
				else
				{

					$arDataAll[$k]['ordered'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` WHERE `gs_id` = '".wpsg_q($v['id'])."'");

				}

				if (array_key_exists($nStatus, $this->shop->view['arStatus']))
				{
					$this->shop->view['arStatus'][$nStatus][1] ++;
				}
				else
				{
					$this->shop->view['arStatus'][$nStatus] = array($strStatus, 1);
				}

				if (isset($this->shop->view['filter']['status']) && $this->shop->view['filter']['status'] > 0 && $nStatus != $this->shop->view['filter']['status'])
				{
					unset($arDataAll[$k]);
				}
				else
				{
					$arDataAll[$k]['status'] = $strStatus;
				}

			}

			//if (isset($_REQUEST['wpsg_voucher_export']) && $_REQUEST['wpsg_voucher_export'] == '1')
			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'export')
				{

				$strCSV  = "code;value;start_date;end_date;comment;calc_typ;multi;products";
				if ($this->shop->hasMod('wpsg_mod_productgroups')) $strCSV .= ";productgroups";
				$strCSV .= "\r\n";

				$arData = array();



				foreach ($arDataAll as $d)
				{

					$strCSV .= $d['code'].';'.$d['value'].';'.$d['start_date'].';'.$d['end_date'].';'.$d['comment'].';'.$d['calc_typ'].';'.$d['multi'].';'.$d['products'];
					if ($this->shop->hasMod('wpsg_mod_productgroups')) $strCSV .= ';'.$d['productgroups'];
					$strCSV .= "\r\n";

				}

				header("Content-Type: text/csv");
				header("Content-Disposition: attachment; filename=gutscheine.csv");
				header("Content-Description: csv File");
				header("Pragma: no-cache");
				header("Expires: 0");

				die($strCSV);

			}

			// Anzahl an Elementen
			$this->shop->view['count'] = sizeof($arDataAll);

			// Pro Seite / Fallback auf 25 wenn was falsches eingegeben wurde
			$perPage = ((intval($this->shop->get_option('wpsg_mod_gutschein_perPage')) > 0)?intval($this->shop->get_option('wpsg_mod_gutschein_perPage')):25);

			// Anzahl an Seiten
			$this->shop->view['pages'] = ceil($this->shop->view['count'] / $perPage);

			// Seite bestimmen
			if (isset($_REQUEST['seite']) && intval($_REQUEST['seite']) > 0) $this->shop->view['page'] = intval($_REQUEST['seite']); else $this->shop->view['page'] = 1;

			// Aktuele Seite aus den Datensätzen herausnehmen
			$this->shop->view['data'] = array_splice($arDataAll, (($this->shop->view['page'] - 1) * $perPage), $perPage);

			if ($this->shop->view['filter']['order'] == 'status')
			{

				wpsg_array_csort($this->shop->view['data'], 'status');

			}
			

			if (isset($_REQUEST['submit-multidelete']))
			{

				if (!wpsg_isSizedArray($_REQUEST['wpsg_multido']))
				{

					$this->shop->addBackendError(__('Bitte mindestens einen Gutschein auswählen', 'wpsg'));

				}
				else
				{

					$nDelete = 0;

					foreach ($_REQUEST['wpsg_multido'] as $k => $voucher_id)
					{

						$oVoucher = wpsg_voucher::getInstance($voucher_id);
						$oVoucher->delete();

						$nDelete ++;

					}

					$this->shop->addBackendMessage(wpsg_translate(__('#1# Gutschein(e) wurden gelöscht.', 'wpsg'), $nDelete));

				}

				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Voucher&action=index');

			}

			$nPerPage = $this->shop->get_option('wpsg_mod_gutschein_perPage');
			if ($nPerPage <= 0) $nPerPage = 25;

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

				$this->shop->view['arFilter'] = wpsg_xss($_REQUEST['filter']);
				$this->shop->view['hasFilter'] = true;

			}
			else if (wpsg_isSizedArray($_SESSION['wpsg']['backend']['voucher']['arFilter'])) $this->shop->view['arFilter'] = $_SESSION['wpsg']['backend']['voucher']['arFilter'];

			$this->shop->view['countAll'] = wpsg_voucher::count($this->shop->view['arFilter']);

			if (wpsg_isSizedInt($_REQUEST['seite'])) $this->shop->view['arFilter']['page'] = $_REQUEST['seite'];

			$this->shop->view['pages'] = ceil($this->shop->view['countAll'] / $nPerPage);
			if ($this->shop->view['arFilter']['page'] <= 0 || $this->shop->view['arFilter']['page'] > $this->shop->view['pages']) $this->shop->view['arFilter']['page'] = 1;

			$this->shop->view['arFilter']['limit'] = array(($this->shop->view['arFilter']['page'] - 1) * $nPerPage, $nPerPage);

			// Filter speichern
			$_SESSION['wpsg']['backend']['voucher']['arFilter'] = $this->shop->view['arFilter'];

			$this->shop->view['arStatus'] = array();
			$this->shop->view['arStatus']['0']['label'] = __('Alle', 'wpsg');
			$this->shop->view['arStatus']['1']['label'] = __('Aktiv', 'wpsg');
			$this->shop->view['arStatus']['2']['label'] = __('Inaktiv', 'wpsg');
			$this->shop->view['arStatus']['3']['label'] = __('Verbraucht', 'wpsg');
			$this->shop->view['arStatus']['4']['label'] = __('Wartend', 'wpsg');
			$this->shop->view['arStatus']['0']['count'] = '0';
			$this->shop->view['arStatus']['1']['count'] = '0';
			$this->shop->view['arStatus']['2']['count'] = '0';
			$this->shop->view['arStatus']['3']['count'] = '0';
			$this->shop->view['arStatus']['4']['count'] = '0';
			
			$this->shop->view['arData'] = wpsg_voucher::find($this->shop->view['arFilter']);

			$this->shop->view['arStatus']['0']['count'] = $this->shop->view['arData']['counts'][0];
			$this->shop->view['arStatus']['1']['count'] = $this->shop->view['arData']['counts'][1];
			$this->shop->view['arStatus']['2']['count'] = $this->shop->view['arData']['counts'][2];
			$this->shop->view['arStatus']['3']['count'] = $this->shop->view['arData']['counts'][3];
			$this->shop->view['arStatus']['4']['count'] = $this->shop->view['arData']['counts'][4];
			
			unset($this->shop->view['arData']['counts']);
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/index.phtml');

		} // public function indexAction()

		public function settings_edit()
		{

			$this->render(WPSG_PATH_VIEW.'/mods/mod_gutschein/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_gutschein_size', $_REQUEST['wpsg_mod_gutschein_size'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_gutschein_perPage', $_REQUEST['wpsg_mod_gutschein_perPage'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    			
		} // public function settings_save()
 
		public function admin_presentation()
		{

			echo wpsg_drawForm_Checkbox('wpsg_mod_gutschein_hideInsert', __('Gutschein einfügen im Warenkorb verbergen', 'wpsg'), $this->shop->get_option('wpsg_mod_gutschein_hideInsert'), array('help' => 'wpsg_mod_gutschein_hideInsert'));

		} // public function admin_presentation()

		public function admin_presentation_submit()
		{

			$this->shop->update_option('wpsg_mod_gutschein_hideInsert', $_REQUEST['wpsg_mod_gutschein_hideInsert'], false, false, WPSG_SANITIZE_CHECKBOX);

		} // public function admin_presentation_submit()

		/* Modulfunktionen */

	} // class wpsg_mod_gutschein extends wpsg_mod_basic
 