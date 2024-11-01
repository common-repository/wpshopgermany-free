<?php

	/**
	 * Controller für die Produktverwaltung
	 */
	class wpsg_ProduktController extends wpsg_SystemController {

		/**
		 * Übernimmt die Verteilung der Anfragen
		 */
		public function dispatch() {

			parent::dispatch();

			if (!wpsg_checkInput($_REQUEST['action'], WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $action = wpsg_getStr($_REQUEST['action']);
			
			if (wpsg_isSizedString($action, 'add')) {
				
				$this->addAction();
				
			} else if (wpsg_isSizedString($action, 'edit')) {
				
				$this->editAction();
				
			} else if (wpsg_isSizedString($action, 'export')) {
				
				$this->exportAction();
				
			} else if (wpsg_isSizedString($action, 'exportMedia')) {

				$this->exportMediaAction();

			} else if (wpsg_isSizedString($action, 'import')) {
				
				$this->importAction();
				
			} else if (wpsg_isSizedString($action, 'copy')) {
				
				$this->copyAction();
				
			} else if (wpsg_isSizedString($action, 'del')) {
				
				$this->delAction();
				
			} else if (wpsg_isSizedString($action, 'save')) {
				
				$this->saveAction();
				
			} else if (wpsg_isSizedString($action, 'select')) {
				
				$this->selectAction();
				
			} else if (wpsg_isSizedString($action, 'ajax')) {
				
				$this->ajaxAction();
				
			} else {
				
				$this->indexAction();
			}

		} // public function dispatch()		

		/**
		 * Nimmt Ajax Anfragen innerhalb der Produktverwaltung entgege
		 */
		public function ajaxAction() {
			
			if (!wpsg_checkInput($_REQUEST['mod'], WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $mod = wpsg_getStr($_REQUEST['mod']);
			
			if (!wpsg_checkInput($_REQUEST['cmd'], WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $cmd = wpsg_getStr($_REQUEST['cmd']);
		 
			if (wpsg_isSizedString($mod)) {

				// Check valid text input
				if (!wpsg_checkInput($mod, WPSG_SANITIZE_TEXTFIELD)) throw \wpsg\Exception::getSanitizeException();
				
				$this->shop->callMod($mod, 'produkt_ajax');

			} else if ($cmd === 'upload') {
			    
				// Check Arrry of int
				if (!wpsg_checkInput($_REQUEST['post_id'], WPSG_SANITIZE_ARRAY_INT)) throw \wpsg\Exception::getSanitizeException();
								
				// Check Datatype
				if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
				else $edit_id = intval($_REQUEST['edit_id']);
				
			    foreach ($_REQUEST['post_id'] as $post_id) {
			    
			    	$post_id = intval($post_id);
			    	
                    add_post_meta($post_id, 'wpsg_produkt_id', $edit_id);

                }
				
                $this->shop->view['data']['id'] = $edit_id;
                    
                die($this->imagehandler->getProductListBackend($edit_id));

			} else if ($cmd === 'setImageOrder') {

				// Check int type
				if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
				else $pid = intval($_REQUEST['edit_id']);
												
				// Check array type
				if (!wpsg_isSizedArray($_REQUEST['wpsg_reorder'])) throw \wpsg\Exception::getSanitizeException();
				 
				$sreo = '';

				foreach ($_REQUEST['wpsg_reorder'] as $v) {

					$st = explode('_', $v);
										
					$sreo .= intval($st[1]).',';

				}

				$sreo = substr($sreo, 0, strlen($sreo) - 1);

				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array("postids" => wpsg_q($sreo)), "`id` = '".wpsg_q($pid)."'");

				die('1');

			} else if ($cmd == 'removeImage') {
				
				// Check Datatype int
				if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
				else $edit_id = intval($_REQUEST['edit_id']);
				
				// Check Datatype int
				if (!wpsg_checkInput($_REQUEST['pid'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
				else $pid = intval($_REQUEST['pid']);
				
				// Auch in der Mediathek löschen
				if (wpsg_isSizedString($_REQUEST['delmt'], 'true')) {

					// Sanitize
					if (!wpsg_checkInput($pid, WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
					else $pid = intval($pid);
					
					wp_delete_post($pid, true);

				}

				// Zuordnung löschen
				delete_post_meta($pid, 'wpsg_produkt_id', $edit_id);

				$this->shop->view['data']['id'] = $edit_id;

				if ($this->shop->hasMod('wpsg_mod_produktartikel')) $this->shop->callMod('wpsg_mod_produktartikel', 'updatePostThumbnail', array($edit_id));

				die($this->imagehandler->getProductListBackend($edit_id));

			} else if ($cmd === 'ratingDel') {

				if (!wpsg_checkInput($_REQUEST['c_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
				else $c_id = intval($_REQUEST['c_id']);
				
				wp_delete_comment($c_id, true); 

			}

		} // public function ajaxAction()

		/**
		 * Stellt die Übersicht der Produkte im Backend dar
		 */
		public function indexAction() {
						
			if (isset($_REQUEST['submit-button'])) check_admin_referer('wpsg-product-search');
						
			$nPerPage = intval($this->shop->get_option('wpsg_produkte_perpage'));
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

			if (wpsg_isSizedInt($_REQUEST['search']['pgruppe'])) {
				
				$_REQUEST['filter']['productgroup_ids'] = $_REQUEST['search']['pgruppe'];
				
			}
			
			if (wpsg_isSizedArray($_REQUEST['filter'])) {

				if (!wpsg_checkInput($_REQUEST['filter']['s'], WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
				else $_REQUEST['filter']['s'] = wpsg_xss($_REQUEST['filter']['s']); 

				$this->shop->view['arFilter'] = $_REQUEST['filter'];
				$this->shop->view['hasFilter'] = true;

			} 

			$this->shop->view['arFilter']['showDisabled'] = true;

			$this->shop->view['countAll'] = wpsg_product::count($this->shop->view['arFilter']);

			if (wpsg_isSizedInt($_REQUEST['seite'])) $this->shop->view['arFilter']['page'] = intval($_REQUEST['seite']);

			$this->shop->view['pages'] = ceil($this->shop->view['countAll'] / $nPerPage);
			if ($this->shop->view['arFilter']['page'] <= 0 || $this->shop->view['arFilter']['page'] > $this->shop->view['pages']) $this->shop->view['arFilter']['page'] = 1;

			$this->shop->view['arFilter']['limit'] = array(($this->shop->view['arFilter']['page'] - 1) * $nPerPage, $nPerPage);

			// Filter speichern
			$_SESSION['wpsg']['backend']['products']['arFilter'] = $this->shop->view['arFilter'];

			$this->shop->view['arData'] = wpsg_product::find(wpsg_array_merge(array('searchExt' => '1'), $this->shop->view['arFilter']));

			if (isset($_REQUEST['submit-button'])) $this->shop->view['submit'] = true;
			else $this->shop->view['submit'] = false;

			$this->shop->render(WPSG_PATH_VIEW.'/produkt/index.phtml');

		} // public function indexAction()
		
		/**
		 * Wird beim exportieren der Produkte aufgerufen
		 * @param bool $bReturnData
		 * @return array|void
		 * @throws \wpsg\Exception
		 */
		public function exportAction($bReturnData = false, $noNounce = false) {
			
			if (!$noNounce) check_admin_referer('wpsg-product-export');

			$arData = $this->db->fetchAssoc("SELECT * FROM `".wpsg_q(WPSG_TBL_PRODUCTS)."` WHERE `deleted` != '1'");

			if (!wpsg_isSizedArray($arData)) { $this->addBackendError(__('Keine Daten zum Exportieren vorhanden.', 'wpsg')); $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=index'); return; }

			foreach ($arData as $k => $v)
			{

				// Produktattribute laden
				if ($this->shop->hasMod('wpsg_mod_produktattribute'))
				{

					$att = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_AT."`");

					foreach ($att as $a)
					{

						$arData[$k]["ATT_".$a['name']] = $this->db->fetchOne("SELECT `value` FROM `".WPSG_TBL_PRODUCTS_AT."` WHERE `p_id` = '".wpsg_q($v['id'])."' AND `a_id` = '".wpsg_q($a['id'])."' ");

					}

				}

			}

			$mb = new wpsg_mod_basic();
			$path = $mb->getTmpFilePath();

			$fp = fopen($path.'/wpsg_productexport.csv', 'w');
			fputcsv($fp, array_keys($arData[0]));;

			$arDataExport = array();

			foreach ($arData as $e)
			{

				// Zeilenumbrüche entfernen
				if (get_option('wpsg_impexp_clearlinebreak') === '1')
				{

					foreach ($e as $k => $v) { $e[$k] = preg_replace('/\r|\n/', '', $v); }

				}

				$arDataExport[] = $e;

				fputcsv($fp, $e, ',', '"');

			}
			fclose($fp);

			if ($bReturnData) return array($path.'/wpsg_productexport.csv', $arDataExport);
			else
			{

				header('Content-type: application/download');
				header('Content-Disposition:inline; filename="wpsg_productexport.csv"');
				header('Expires: 0');
				header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
				header('Pragma:public');

				readfile($path.'/wpsg_productexport.csv');
				die();

			}

		} // public function exportAction()

		public function exportMediaAction() {
	 
			check_admin_referer('wpsg-product-exportMedia');

			@ini_set('memory_limit', '2000M');
			@set_time_limit(3600);

			$zip_file = tempnam(sys_get_temp_dir(), 'wpsg');
			$zip = new ZipArchive();

			if ($zip->open($zip_file, ZIPARCHIVE::CREATE) == true) {

				// Produktdaten, wie normaler Export
				list($product_export_file, $arData) = $this->exportAction(true, true);
				$zip->addFile($product_export_file, 'productdata.csv');

				// Bilddaten
				foreach ($arData as $d)
				{

					$arAttachmentIDs = $this->imagehandler->getAttachmentIDs($d['id']);

					if (wpsg_isSizedArray($arAttachmentIDs))
					{

						$zip->addEmptyDir($d['id']);

						foreach ($arAttachmentIDs as $attachment_id)
						{

							$file = get_attached_file($attachment_id);

							if (file_exists($file) && is_file($file))
							{

								$zip->addFile($file, $d['id'].'/'.basename($file));

							}

						}

					}

				}

				$zip->close();
 
				wpsg_header::ZIP($zip_file, 'wpsg_export.zip');
				exit;

			} else {

				$this->addBackendError(_('Konnte ZIP Archiv nicht erstellen.', 'wpsg'));

			}

			$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt');

		} // public function exportMediaAction()

		/**
		 * Wird beim importieren der Produkte aufgerufen. Zeichnet das Upload Formular und führt auch den Import durch
		 */
		public function importAction() {
			
			@ini_set('memory_limit', '2000M');
			@set_time_limit(3600);

			if (isset($_REQUEST['wpsg_import']) && file_exists($_FILES['wpsg_importfile']['tmp_name'])) {
				
				check_admin_referer('wpsg-product-import-do');
				
				// Import starten

				$nImported = 0;
				$arImages = array();
				$keys = array();
				$extract_dir = '';

				if (preg_match('/\.zip$/i', $_FILES['wpsg_importfile']['name']))
				{

					$zip = new ZipArchive();

					if ($zip->open($_FILES['wpsg_importfile']['tmp_name']) === true)
					{

						$extract_dir = sys_get_temp_dir().'/'.time();

						$zip->extractTo($extract_dir);
						$zip->close();

						if (!file_exists($extract_dir) || !is_dir($extract_dir))
						{

							$this->shop->addBackendError(__('Konnte ZIP Archiv nicht entpacken.', 'wpsg'));
							$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt');

						}
						else
						{

							$arZIPData = scandir($extract_dir);

							foreach ($arZIPData as $z)
							{

								if (is_numeric($z))
								{

									$arImages[$z] = array();
									$arImageFiles = scandir($extract_dir.'/'.$z);

									foreach ($arImageFiles as $if) { if (is_file($extract_dir.'/'.$z.'/'.$if)) { $arImages[$z][] = $if; } }

								}

							}

						}

						if (!file_exists($extract_dir.'/productdata.csv'))
						{

							$this->shop->addBackendError(__('Keine Produktdaten im ZIP Archiv gefunden.', 'wpsg'));
							$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt');

						}

						$handle = fopen($extract_dir.'/productdata.csv', "r");

					}
					else
					{

						$this->shop->addBackendError(__('Konnte ZIP Archiv nicht öffnen.', 'wpsg'));
						$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt');

					}

				}
				else
				{

					$handle = fopen($_FILES['wpsg_importfile']['tmp_name'], "r");

				}

				$i = 0;
				while (($row = fgetcsv ($handle, 0, ",")) !== FALSE )
				{

					if ($i > 0)
					{

						$data = array();

						foreach ($keys as $k => $k_name)
						{
							$data[$k_name] = wpsg_q($row[$k]);
						}

						if ($data['id'] <= 0) unset($data['id']);

						// Alte Sachen lösche ich vor dem Import mit der Übergebenen ID !

						// Produkt löschen
						$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($data['id'])."'");

						// Attributwerte löschen
						if ($this->shop->hasMod('wpsg_mod_produktattribute'))
						{
							$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS_AT."` WHERE `p_id` = '".wpsg_q($data['id'])."'");
						}

						if (!isset($data['lang_parent']) || $data['lang_parent'] <= 0) $nImported ++;

						unset($data['v_id']);
						unset($data['mwst_value']);
						unset($data['ptemplate']);

						$data_import = $data;

						foreach ($data_import as $k => $v)
						{
							if (preg_match("/^ATT_(.*)/", $k)) { unset($data_import[$k]); }
						}

						$pNeu_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS, $data_import, true);

						// Attribute speichern
						foreach ($data as $k => $v)
						{

							if (preg_match("/^ATT_(.*)/", $k))
							{

								// Attribute ID auslesen
								$att_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_AT."` WHERE `name` = '".wpsg_q(preg_replace("/^ATT_/", "", $k))."' ");

								if ($att_id > 0)
								{

									// Attribut speichern
									$this->db->ImportQuery(WPSG_TBL_PRODUCTS_AT, array(
										"p_id" => wpsg_q($pNeu_id),
										"a_id" => wpsg_q($att_id),
										"value" => $v
									));

								}

							}

						}

						// Bilder?
						if (wpsg_isSizedInt($data['id']) && wpsg_isSizedArray($arImages[$data['id']]))
						{

							$arAttachmentIDs = $this->imagehandler->getAttachmentIDs($data['id']);

							foreach (array_reverse($arImages[$data['id']]) as $img)
							{

								$exist = false;

								// Prüfen ob ein Bild anhand des Namens schon im Produkt existiert
								foreach ($arAttachmentIDs as $attachment_id)
								{

									$file = get_attached_file($attachment_id);

									if (strtolower(basename($file)) == strtolower($img)) { $exist = true; break; }

								}

								if (!$exist)
								{

									$this->imagehandler->addImageToProduct($extract_dir.'/'.$data['id'].'/'.$img, $data['id']);

								}

							}

						}

					}
					else
					{

						// Schlüssel erzeugen
						$keys = $row;

					}

					$i ++;

				}

				fclose($handle);

				$this->shop->addBackendMessage(wpsg_translate(__('#1# Produkte wurden importiert.', 'wpsg'), $nImported));
				die($this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&amp;action=index'));

			} else if (isset($_REQUEST['wpsg_import'])) {

				$this->shop->addBackendError(__('Keine Datei zum Import angegeben.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&amp;action=import');

			} else {
				
				check_admin_referer('wpsg-product-import');
				
			}

			$this->shop->render(WPSG_PATH_VIEW.'/produkt/import.phtml');

		} // public function importAction()

		/**
		 * Wird beim bearbeiten aufgerufen
		 */
		public function editAction() {
			
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			else $edit_id = intval($_REQUEST['edit_id']);
					
			if (!wpsg_checkInput($_REQUEST['wpsg_lang'], WPSG_SANITIZE_ARRAY_LANG, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $wpsg_lang = $_REQUEST['wpsg_lang'];
			
			check_admin_referer('wpsg-product-edit-'.$edit_id);

			// Verfügbare Produkttemplates
			$this->shop->view['templates'] = $this->shop->loadProduktTemplates();

			if (isset($wpsg_lang)) {
				
				$product_translated_id = $this->db->fetchOne("
					SELECT
						P.`id`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P 
					WHERE
						P.`lang_parent` = '".wpsg_q($edit_id)."' AND
						P.`lang_code` = '".wpsg_q($wpsg_lang)."'
				");

				if ($product_translated_id <= 0)
				{

					$arLang = $this->shop->getStoreLanguages();

					// Übersetzung anlegen
					$product_data_original = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($edit_id)."'");

					unset($product_data_original['id']);
					$product_data_original['lang_parent'] = wpsg_q($edit_id);
					$product_data_original['lang_code'] = wpsg_q($wpsg_lang);
					$product_data_original['name'] .= ' ['.$arLang[$this->shop->getLocaleToLanguageCode($wpsg_lang)]['name'].']';
					$product_data_original['beschreibung'] .= ' ['.$arLang[$this->shop->getLocaleToLanguageCode($wpsg_lang)]['name'].']';
					if (trim($product_data_original['detailname']) != '') $product_data_original['detailname'] .= ' ['.$arLang[$this->shop->getLocaleToLanguageCode($wpsg_lang)]['name'].']';

					$product_translated_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS, $product_data_original);

					$this->shop->callMods('produkt_createTranslation', array(&$edit_id, &$product_translated_id));

				}

				$this->shop->view['data'] = $this->db->fetchRow("
					SELECT
						P.*
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P 
					WHERE
						P.`lang_parent` = '".wpsg_q($edit_id)."' AND
						P.`lang_code` = '".wpsg_q($wpsg_lang)."'
				");

			}
			else
			{

				$this->shop->view['data'] = $this->db->fetchRow("
					SELECT
						P.*
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P 
					WHERE
						P.`id` = '".wpsg_q($edit_id)."'
				");

				$default_country = $this->shop->getDefaultCountry();
				$this->shop->view['data']['mwst_value'] = $default_country->getTax($this->shop->view['data']['mwst_key']);

				if ($this->shop->get_option('wpsg_preisangaben') == WPSG_NETTO) $this->shop->view['data']['tax_sum_value'] = wpsg_calculateSteuer($this->shop->view['data']['preis'], WPSG_NETTO, $this->shop->view['data']['mwst_value']);
				else $this->shop->view['data']['tax_sum_value'] = wpsg_calculateSteuer($this->shop->view['data']['preis'], WPSG_BRUTTO, $this->shop->view['data']['mwst_value']);

			}

			// Produktobject
			$this->shop->view['oProduct'] = wpsg_product::getInstance($edit_id);

			// Erlaubte Zahlungsarten
			$this->shop->view['allowedPayment'] = wpsg_trim((array)explode(',', $this->shop->view['data']['allowedpayments']));

			// Erlaubte Versandarten
			$this->shop->view['allowedShipping'] = wpsg_trim((array)explode(',', $this->shop->view['data']['allowedshipping']));

			$this->shop->callMods('produkt_edit', array(&$this->shop->view['data']));

			// Steuersätze des Standardlandes ermitteln
			$this->shop->view['arTaxGroup'] = array(
				'a' => 'A',
				'b' => 'B',
				'c' => 'C',
				'd' => 'D'
			);

			if (wpsg_isSizedInt($this->shop->get_option('wpsg_defaultland')))
			{

				$default_country = wpsg_country::getInstance($this->shop->get_option('wpsg_defaultland'));

				foreach ($this->shop->view['arTaxGroup'] as &$tax_group)
				{

					$tax_value = $default_country->getTax(strtolower($tax_group));

					if (!is_null($tax_value)) $tax_group .= ' ('.wpsg_ff($tax_value, '%').' / '.$default_country->kuerzel.')';

				}

			}

			$this->shop->view['partikel_select'] = array();

			$this->shop->view['partikel_select']['davor'][1]['']= 'Nicht zugeordnet';

			$arArtikel = get_posts('numberposts=-1'); if (wpsg_isSizedArray($arArtikel)) {

				$this->shop->view['partikel_select']['article'] = array(__('Artikel', 'wpsg'), array());
				foreach ($arArtikel as $a) $this->shop->view['partikel_select']['article'][1][$a->ID] = $a->post_title;

			}

			$arPages = get_pages(); if (wpsg_isSizedArray($arPages)) {

				$this->shop->view['partikel_select']['pages'] = array(__('Seiten', 'wpsg'), array());
				foreach ($arPages as $p) $this->shop->view['partikel_select']['pages'][1][$p->ID] = $p->post_title;

			}

			$this->shop->view['arSubAction'] = array(
				'general' => array(
					'title' => __('Allgemein', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_general.phtml', false)
				)
			);

			$this->shop->view['arSubAction']['texte'] = array(
				'title' => __('Texte', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_texte.phtml', false)
			);

			if ($this->shop->hasMod('wpsg_mod_produktartikel'))
			{

				$this->shop->view['arCom'] = $this->db->fetchAssoc("
                    SELECT 
                            C.`comment_ID`, C.`comment_author`, C.`comment_date`, C.`comment_content`, M.`meta_value`,  P.`wpsg_produkt_id` 
                    FROM 
                            `".$GLOBALS['wpdb']->prefix."comments` AS C
                                    LEFT JOIN ".$GLOBALS['wpdb']->prefix."commentmeta AS M ON (C.`comment_ID` = M.`comment_id` AND M.`meta_key` = 'sto_points')
                                    LEFT JOIN ".$GLOBALS['wpdb']->prefix."posts AS P ON (C.`comment_post_ID` = P.`ID`)
                    WHERE 
                            C.`comment_type` = 'wpsg_product_comment' AND 
                            P.`wpsg_produkt_id` = '".wpsg_q($edit_id)."'
                    
                    ORDER BY 
                            C.`comment_date` DESC
                ");

				$this->shop->view['arSubAction']['tabrating'] = array(
					'title' => __('Bewertungen', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_rating.phtml', false)
				);

			}

			if (!isset($wpsg_lang))
			{

				$this->shop->view['arSubAction']['price'] = array(
					'title' => __('Preis / Steuer', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_price.phtml', false)
				);

				$this->shop->view['arSubAction']['payship'] = array(
					'title' => __('Versand-/ Zahlungsarten', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_payship.phtml', false)
				);

			}

			/* Produktbilder */
			if (!isset($wpsg_lang))
			{

				if (wpsg_isSizedInt($this->shop->view['data']['id']))
				{

					$this->shop->view['strProductList'] = $this->imagehandler->getProductListBackend($this->shop->view['data']['id']);

				}

				$this->shop->view['arSubAction']['images'] = array(
					'title' => __('Produktbilder', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/images.phtml', false)
				);

			}

			$this->shop->callMods('product_addedit_content', array(&$this->shop->view['arSubAction'], &$this->shop->view['data']));

			if($this->get_option('wpsg_alternativeProductDetailDesign') == true)
			{

				$this->shop->render( WPSG_PATH_VIEW . '/produkt/addedit_alternativeDesign.phtml' );

			}
			else
			{

				$this->shop->render( WPSG_PATH_VIEW . '/produkt/addedit.phtml' );

			}

		} // public function editAction()

		/**
		 * Wird beim kopieren eines Produkts aufgerufen
		 */
		public function copyAction() {
			
			check_admin_referer('wpsg-product-copy-'.intval($_REQUEST['edit_id']));
			
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			else $edit_id = intval($_REQUEST['edit_id']);

			$produkt_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($edit_id)."' ");

			// Neues Produkt anlegen
			unset($produkt_db['id']);

			$produkt_db['cdate'] = 'NOW()';
			$produkt_db['name'] = '['.__('KOPIE', 'wpsg').'] '.$produkt_db['name'];

			$produkt_db = wpsg_q($produkt_db);

			$new_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS, $produkt_db);

			// Übersetzungen kopieren wenn vorhanden
			$produkt_translations = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($edit_id)."'");
			
			foreach ((array)$produkt_translations as $p)
			{

				unset($p['id']);
				$p['lang_parent'] = $new_id;

				$p = wpsg_q($p);

				$this->db->ImportQuery(WPSG_TBL_PRODUCTS, $p);

			}

			if ($this->shop->get_option('wpsg_dontcopymedia') !== '1') {
				
				// Bilder kopieren
				$ih = new wpsg_imagehandler();
				
				$arAttachments = $ih->getAttachmentIDs($edit_id);
				$GLOBALS['wpsg_product_copy_imagemapping'] = [];
				
				foreach ($arAttachments as $a_id) {
					
					$post = $this->db->fetchRow("SELECT * FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID`='".wpsg_q($a_id)."'");
					$n_a_id = $ih->addImageToProduct($post['guid'], $new_id);
					
					if (wpsg_isSizedInt($n_a_id)) $GLOBALS['wpsg_product_copy_imagemapping'][$a_id] = $n_a_id;
					
				}
				
			}
			
			$this->shop->callMods('produkt_copy', array(&$edit_id, &$new_id));

			$this->addBackendMessage(__('Produkt wurde erfolgreich kopiert.', 'wpsg'));

			$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=index');

		} // public function copyAction()

		/**
		 * Speichert ein Produkt
		 */
		public function saveAction() {
			
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $edit_id = intval($_REQUEST['edit_id']);
			 			
			check_admin_referer('wpsg-product-save-'.$edit_id);
			
			if ($this->shop->get_option('wpsg_options_nl2br') == '1' && wpsg_checkInput($_REQUEST['beschreibung'], WPSG_SANITIZE_TEXTFIELD)) { $_REQUEST['beschreibung'] = nl2br($_REQUEST['beschreibung']); }

			if (isset($_REQUEST['wpsg_lang'])){

				// Übersetzung speichern
				if (!wpsg_checkInput($_REQUEST['wpsg_lang'], WPSG_SANITIZE_ARRAY_LANG)) throw \wpsg\Exception::getSanitizeException();
				else $wpsg_lang = $_REQUEST['wpsg_lang'];

				$trans_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($edit_id)."' AND `lang_code` = '".wpsg_q($wpsg_lang)."'");

				if ($trans_id <= 0) throw new \wpsg\Exception(__('ERROR: Übersetzung existiert noch nicht, das dürfte nicht passieren!', 'wpsg'));

				$data = [];
				
				wpsg_checkRequest('name', [WPSG_SANITIZE_TEXTFIELD], __('Produktname Übersetzung'),$data);
				wpsg_checkRequest('disabled', [WPSG_SANITIZE_CHECKBOX], __('Produktstatus Übersetzung'),$data);
				wpsg_checkRequest('detailname', [WPSG_SANITIZE_TEXTFIELD], __('Produktname (Detail) Übersetzung'),$data);
				wpsg_checkRequest('shortdesc', [WPSG_SANITIZE_NONE], __('Produktbeschreibung (Kurz) Übersetzung'),$data);
				wpsg_checkRequest('beschreibung', [WPSG_SANITIZE_NONE], __('Kurztext Übersetzung'),$data);
				wpsg_checkRequest('partikel', [WPSG_SANITIZE_INT, ['allowEmpty' => true]], __('Produktname Übersetzung'),$data);
				  
				$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, "`id` = '".wpsg_q($trans_id)."'");

				$this->addBackendMessage(__('Übersetzung erfolgreich gespeichert', 'wpsg'));

				$this->shop->callMods('produkt_save_translation', array(&$edit_id, &$trans_id));

				if (isset($_REQUEST['submit_index'])) $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=index');
				else $this->redirect(wp_nonce_url(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=edit&edit_id='.$edit_id.'&wpsg_lang='.$wpsg_lang, 'wpsg-product-edit-'.wpsg_getInt($edit_id)));

			} else {

				// Reguläres Produkt speichern

				$data = [];
				
				wpsg_checkRequest('name', [WPSG_SANITIZE_TEXTFIELD], __('Produktname', 'wpsg'), $data);
				wpsg_checkRequest('disabled', [WPSG_SANITIZE_CHECKBOX], __('Produktstatus', 'wpsg'), $data);
				wpsg_checkRequest('detailname', [WPSG_SANITIZE_TEXTFIELD], __('Produktname (Detail)', 'wpsg'), $data);
				wpsg_checkRequest('shortdesc', [WPSG_SANITIZE_TEXTFIELD], __('Produktbeschreibung (Kurz)', 'wpsg'), $data);
				
				wpsg_checkRequest('beschreibung', [WPSG_SANITIZE_NONE], __('Kurztext', 'wpsg'), $data);
				wpsg_checkRequest('longdescription', [WPSG_SANITIZE_NONE], __('Langtext', 'wpsg'), $data);
				wpsg_checkRequest('moreinfos', [WPSG_SANITIZE_NONE], __('Zusätzliche Informationen', 'wpsg'), $data);
				wpsg_checkRequest('moreinfos2', [WPSG_SANITIZE_NONE], __('Lieferumfang', 'wpsg'), $data);
				
				wpsg_checkRequest('anr', [WPSG_SANITIZE_TEXTFIELD], __('Artikelnummer', 'wpsg'), $data);
				wpsg_checkRequest('ptemplate_file', [WPSG_SANITIZE_VALUES, $this->shop->loadProduktTemplates()], __('Produkttemplate', 'wpsg'), $data);
				wpsg_checkRequest('posturl', [WPSG_SANITIZE_URL], __('URL Benachrichtigung / URL', 'wpsg'), $data);
				wpsg_checkRequest('posturl_verkauf', [WPSG_SANITIZE_CHECKBOX], __('URL Benachrichtigung / Bei Kauf', 'wpsg'), $data);
				wpsg_checkRequest('posturl_bezahlung', [WPSG_SANITIZE_CHECKBOX], __('URL Benachrichtigung / Bei Bezahlung', 'wpsg'), $data);
				wpsg_checkRequest('partikel', [WPSG_SANITIZE_INT, ['allowEmpty' => true]], __('Zugeordneter Wordpress Artikel', 'wpsg'), $data);
				wpsg_checkRequest('basket_multiple', [WPSG_SANITIZE_VALUES, [wpsg_product::MULTIPLE_ONE_MULTI, wpsg_product::MULTIPLE_MULTI_MULTI, wpsg_product::MULTIPLE_MULTI_ONE, wpsg_product::MULTIPLE_ONE_ONE]], __('Produkt unterliegt den EU-Leistungsortregeln', 'wpsg'), $data);
				wpsg_checkRequest('rating', [WPSG_SANITIZE_VALUES, ['-1', '0', '1', '2', '3', '4', '5']], __('Bewertungspunkte', 'wpsg'), $data);
				
				if (wpsg_isSizedInt($edit_id)) {
					
					wpsg_checkRequest('mwst_key', [WPSG_SANITIZE_TAXKEY], __('Steuergruppe', 'wpsg'), $data);
					wpsg_checkRequest('euleistungsortregel', [WPSG_SANITIZE_CHECKBOX], __('Produkt unterliegt den EU-Leistungsortregeln', 'wpsg'), $data);
					wpsg_checkRequest('preis', [WPSG_SANITIZE_FLOAT], __('Preis', 'wpsg'), $data);
					wpsg_checkRequest('oldprice', [WPSG_SANITIZE_FLOAT], __('Alter Preis', 'wpsg'), $data);
					
				}
				
				// Erlaubte Zahlungsarten speichern
				$data['allowedpayments'] = '';
				if (wpsg_isSizedInt($_REQUEST['wpsg_paymentmethods_select'], '1')) {

					$arAllowedPayments = wpsg_trim($_REQUEST['wpsg_paymentmethods'], '0');										
					
					if (wpsg_isSizedArray($arAllowedPayments)) $data['allowedpayments'] = implode(',', $arAllowedPayments);
					else $data['allowedpayments'] = '';
					
				}

				// Erlaubte Versandarten speichern
				$data['allowedshipping'] = '';
				if (wpsg_isSizedInt($_REQUEST['wpsg_shippingmethods_select'], '1'))
				{

					$arAllowedShipping = wpsg_trim($_REQUEST['wpsg_shippingmethods'], '0');
					if (wpsg_isSizedArray($arAllowedShipping)) $data['allowedshipping'] = implode(',', $arAllowedShipping);
					else $data['allowedshipping'] = '';

				}
				 
				$this->shop->callMods('produkt_save_before', array(&$data));
				
				if (wpsg_getInt($edit_id) > 0)
				{

					$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, "`id` = '".wpsg_q($edit_id)."'");
					$this->addBackendMessage(__('Produkt erfolgreich gespeichert.', 'wpsg'));

				}
				else
				{

					$data['cdate'] = 'NOW()';
					$edit_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS, $data);
					$this->addBackendMessage(__('Produkt erfolgreich angelegt.', 'wpsg'));

				}

				// Artikelnummer = id, wenn nicht gesetzt
				if (trim($data['anr']) == '')
				{

					$data = array('anr' => wpsg_q($edit_id));

					$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, "`id` = '".wpsg_q($edit_id)."'");

					// Produkt Object Cache löschen
					$this->shop->cache->clearProductCache($edit_id);

				}

				$this->shop->callMods('produkt_save', array(&$edit_id));

				if (isset($_REQUEST['submit_index'])) $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=index');
				else $this->redirect(wp_nonce_url(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=edit&edit_id='.$edit_id, 'wpsg-product-edit-'.wpsg_getInt($edit_id)));

			}

			exit;

		} // public function saveAction()

		/**
		 * Wird beim erstellen eines neuen Produktes aufgerufen
		 */
		public function addAction() {
			
			check_admin_referer('wpsg-product-add');
			
			// Verfügbare Produkttemplates
			$this->shop->view['templates'] = $this->shop->loadProduktTemplates();

			$this->shop->view['data'] = array(
				'id' => false,
				'ptemplate_file' => 'standard.phtml'
			);

			$this->shop->view['partikel_select'] = array();

			$arArtikel = get_posts('numberposts=-1'); if (wpsg_isSizedArray($arArtikel)) {

			$this->shop->view['partikel_select']['article'] = array(__('Artikel', 'wpsg'), array());
			foreach ($arArtikel as $a) $this->shop->view['partikel_select']['article'][1][$a->ID] = $a->post_title;

		}

			$arPages = get_pages(); if (wpsg_isSizedArray($arPages)) {

			$this->shop->view['partikel_select']['pages'] = array(__('Seiten', 'wpsg'), array());
			foreach ($arPages as $p) $this->shop->view['partikel_select']['pages'][1][$p->ID] = $p->post_title;

		}

			$this->shop->view['arSubAction'] = array(
				'general' => array(
					'title' => __('Allgemein', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_general.phtml', false)
				)
			);

			$this->shop->view['arSubAction']['texte'] = array(
				'title' => __('Texte', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit_texte.phtml', false)
			);


			$this->shop->callMods('product_addedit_content', array(&$this->shop->view['arSubAction'], &$this->shop->view['data']));
			$this->shop->render(WPSG_PATH_VIEW.'/produkt/addedit.phtml');

		} // public function addAction()

		/**
		 * Wird beim löschen eines Produktes aufgerufen
		 */
		public function delAction() {
			
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			else $edit_id = $_REQUEST['edit_id'];
			
			check_admin_referer('wpsg-product-del-'.$edit_id);

			$oProduct = wpsg_product::getInstance($edit_id);
			$oProduct->delete();

			// Alle Bilder eines Produktes in der Mediathek löschen
			$pid = $edit_id;
			$data = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `meta_key`='".wpsg_q('wpsg_produkt_id')."' AND `meta_value`='".wpsg_q($pid)."' ORDER BY `post_id`");

			foreach ($data as $pm) {
				// Wenn das Bild mehrmals verwendet wird, ist die post_id mehrmals vorhanden.
				$sql = "SELECT * FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `post_id` = '".wpsg_q($pm['post_id'])."'";
				$pis = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `post_id` = '".wpsg_q($pm['post_id'])."' AND `meta_key` = '".wpsg_q('wpsg_produkt_id')."'");

				if (count($pis) > 1) {
					// Nur den Eintrag mit der post_id, meta_key='wpsg_produkt_id' und meta_value=product_id löschen.
					$GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `post_id` = '".wpsg_q($pm['post_id'])."' AND `meta_key` = '".wpsg_q('wpsg_produkt_id')."' AND `meta_value` = '".wpsg_q($pid)."' ");

				}
				if (count($pis) == 1) {
					// Alle Einträge in wp_postmeta mit der post_id und den Eintrag in wp_posts mit ID=post_id löschen.
					//$GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `post_id` = '".wpsg_q($pm['post_id'])."' ");
					//$GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID` = '".wpsg_q($pm['post_id'])."' ");

					// Tabelle Produkte Eintrag postids leeren
					$data = array(
						'postids' => ''
					);
					$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, "`id` = '".wpsg_q($pid)."'");

					// Bilddateien löschen
					//delete_post_meta($pm['post_id'], 'wpsg_produkt_id');	// Löscht den Eintrag bei mehreren Produkten
					wp_delete_attachment( $pm['post_id'], true );	// Löscht alle Einträge zu der post_id und die Bilder

				}
			}


			$this->addBackendMessage(__('Produkt erfolgreich gelöscht.', 'wpsg'));
			$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Produkt&action=index');

		} // public function delAction()

		/**
		 * Dialog für die Produktauswahl
		 */
		public function selectAction() {

			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
			else $edit_id = intval($_REQUEST['edit_id']);
			
			if (wpsg_isSizedString($_REQUEST['wpsg_mode'], 'filterDialog')) {

				die($this->shop->render(WPSG_PATH_VIEW.'/produkt/select_filter.phtml'));

			} else if (wpsg_isSizedString($_REQUEST['wpsg_mode'], 'filter')) {

				$strQueryWHERE = '';

				if (wpsg_checkInput($_REQUEST['filter_name'], WPSG_SANITIZE_TEXTFIELD)) $strQueryWHERE .= " AND P.`name` LIKE '%".wpsg_q($_REQUEST['filter_name'])."%' ";

				$arProductIDs = $this->db->fetchAssocField("
					SELECT
				 		P.`id`
					FROM
				 		`".WPSG_TBL_PRODUCTS."` AS P
				 	WHERE
				 		P.`deleted` = '0' AND
				 		P.`lang_parent` = '0'
						".$strQueryWHERE."
				");

				$this->shop->view['arProducts'] = array();

				// Aufwerten
				foreach ($arProductIDs as $p_id) {

					$product_data = $this->shop->loadProduktArray($p_id);
					$bVariante = false;

					if ($this->shop->hasMod('wpsg_mod_productvariants'))
					{

						$arVarianten = $this->shop->callMod('wpsg_mod_productvariants', 'getVariants', array($p_id, true, true, true));

						if (wpsg_isSizedArray($arVarianten))
						{

							$bVariante = true;

						}

					}

					if (!$bVariante) $this->shop->view['arProducts'][] = $product_data;

				}

				die($this->shop->render(WPSG_PATH_VIEW.'/produkt/select_filter_productlist.phtml'));

			} else if (wpsg_isSizedString($_REQUEST['wpsg_mode'], 'wpsg_mod_relatedproducts')) {

				// Bei der Auswahl für die Ähnlichen Produkte sollte das aktuelle Produkt und bereits relevante nicht zur auswahl stehen
				$this->shop->view['arProdukte'] = $this->db->fetchAssocField("
					SELECT
						P.`id`,
						P.`name`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
							LEFT JOIN `".WPSG_TBL_PRODUCTS_REL."` AS RP ON (RP.`rel_id` = P.`id`) 
					WHERE
						P.`deleted` = '0' AND
						P.`lang_parent` = '0' AND
						P.`id` != '".wpsg_q($edit_id)."'
				", "id", "name");

			} else {

				$this->shop->view['arProdukte'] = wpsg_array_merge(array('-1' => __('Alle Produkte', 'wpsg')), $this->db->fetchAssocField("
					SELECT
						`id`, `name`
					FROM
						`".WPSG_TBL_PRODUCTS."`
					WHERE
						`deleted` = '0' AND
						`lang_parent` = '0'
				", "id", "name"));

				if ($this->shop->hasMod('wpsg_mod_productgroups'))
				{

					$this->shop->view['arProduktgroups'] = $this->db->fetchAssocField("
						SELECT
							PG.*
						FROM
							`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
						WHERE
							PG.`deleted` != '1'					
					", "id", "name");

					$this->shop->view['arProductgroupsOrder'] = array(
						'id' => __('Interne ID', 'wpsg'),
						'name' => __('Produktname', 'wpsg'),
						'anr' => __('Artikelnummer', 'wpsg'),
						'preis' => __('Preis', 'wpsg')
					);

					$this->shop->view['arProductgroupsDirection'] = array(
						'asc' => __('Aufsteigend', 'wpsg'),
						'desc' => __('Absteigend', 'wpsg')
					);

				}

			}

			$this->shop->view['arTemplates'] = $this->shop->loadProduktTemplates();
			array_unshift($this->shop->view['arTemplates'], __('Aus Produkt', 'wpsg'));

			die($this->shop->render(WPSG_PATH_VIEW.'/produkt/select.phtml'));

		} // public function selectAction()

	} // class wpsg_ProduktController extends wpsg_SystemController
