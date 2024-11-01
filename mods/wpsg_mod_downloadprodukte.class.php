<?php 

	/**
	 * Modul zur Verwaltung von Downloadprodukten
	 * @author daniel
	 */
	class wpsg_mod_downloadprodukte extends wpsg_mod_basic 
	{
		
		var $lizenz = 1;
		var $id = 601;

		/**
		 * Costructor
		 */
		public function __construct()
		{	
			parent::__construct();
			
			$this->name 	= __('Downloadprodukte', 'wpsg');
			$this->group 	= __('Produkte', 'wpsg');
			$this->desc 	= __('Ermöglicht es, Downloads als Produkt zu verkaufen. Diese Produkte können bei Sofortbezahlung (z.B. Paypal oder Sofortüberweisung) vom Kunden direkt heruntergeladen werden. Nach Zahlungseingang erhält der Kunde eine E-Mail mit personalisiertem Downloadlink.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
			
			$this->shop->checkDefault('wpsg_mod_downloadprodukte_raid', __('Ein Download ist nicht mehr möglich.', 'wpsg'), false, true);
			
		} // public function install()
		
		/**
		 * 
		 * Enter description here ...
		 */
		function wpsg_enqueue_scripts()
		{
			
			if (is_admin() && preg_match('/wpsg/', wpsg_getStr($_REQUEST['page'])))
			{
			
				wp_enqueue_script('wpsg_ajaxupload', $this->shop->getRessourceURL('js/ajaxupload.js'));
				
			}
			
		} // function wpsg_enqueue_scripts()
		
		/**
		 * 
		 * zeigt das Backendformular zur Konfiguration
		 */
		public function settings_edit()
		{
			
			$this->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/settings_edit.phtml');
			
		} // public function settings_edit()
		
		/**
		 * 
		 * speichert die Konfiguration in die DB
		 */
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_downloadprodukte_zt', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_zt']), false, false, WPSG_SANITIZE_INT);
			$this->shop->update_option('wpsg_mod_downloadprodukte_days', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_days']), false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_downloadprodukte_raid', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_raid']), false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_downloadprodukte_zip', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_zip']), false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_downloadprodukte_shipping', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_shipping']), false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_downloadprodukte_einsplusx', wpsg_q($_REQUEST['wpsg_mod_downloadprodukte_einsplusx']), false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_downloadprodukte_ziptemp', $_REQUEST['wpsg_mod_downloadprodukte_ziptemp'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
		} // public function settings_save()
		
		/* 
		 * zeigt ein Upload-Formular im linken Bereich des Produktbackend an 
		 * @param &array $produkt_data
		 */
		public function product_addedit_content(&$product_content, &$product_data)
		{
			
			$this->shop->view['data'] = $product_data;
			$this->shop->view['prodFiles'] = $this->getProdFileListe($product_data['id']);
			$this->shop->view['wpsg_mod_downloadprodukte']['path'] = str_replace('\\', '\\\\', $this->getFilePath($product_data['id']));
			
			$this->shop->view['wpsg_mod_downloadprodukte']['data'] = $product_data;
		
			$product_content['wpsg_mod_downloadprodukte'] = array(
				'title' => __('Downloadprodukte', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/produkt_addedit_sidebar.phtml', false)
			);
		
		} //public function product_addedit_content(&$product_content, &$product_data)
		
		public function order_view_row(&$p, $i) 
		{

			$arFiles = $this->getProdFiles($this->shop->getProduktId($p['product_key']));
			
			if (wpsg_isSizedArray($arFiles))
			{
			
				$this->shop->view['wpsg_mod_downloadprodukte']['arFiles'] = $arFiles;
				$this->shop->view['wpsg_mod_downloadprodukte']['product'] = $p;
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/order_view_row.phtml');
				
			}
			
		} // public function order_view_row(&$p, $i)
		
		/**
		 * 
		 * Enter description here ...
		 */
		public function produkt_ajax() 
		{

			$_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);

			if ($_REQUEST['cmd'] == 'upload_file')
			{
				$this->fileUpload(wpsg_q($_REQUEST['edit_id']));
			}

			if ($_REQUEST['cmd'] == 'remove')
			{
				$this->fileDelete();
			}
			
			if ($_REQUEST['cmd'] == 'produktfiles_list')
			{
				die($this->getProdFileListe(wpsg_q($_REQUEST['edit_id'])));
			}
			
			if ($_REQUEST['cmd'] == 'download_file')
			{

				header('Content-Disposition: attachment; filename="'.rawurldecode($_REQUEST['file']).'"');
				header('Content-type: application/download');
				header('Content-Disposition: inline; filename="'.$_REQUEST['file'].'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');

				wpsg_ob_end_clean();
				
				die(readfile($this->getFilePath($_REQUEST['edit_id']).'/'.rawurldecode($_REQUEST['file'])));				
				
			}
			
		} // public function produkt_ajax() 
		
		public function produkt_save(&$produkt_id)
		{ 
			
			if (isset($_FILES['wpsg_mod_downloadprodukte_file']['tmp_name']) && file_exists($_FILES['wpsg_mod_downloadprodukte_file']['tmp_name']))
			{
			
				$bOK = $this->fileUpload($produkt_id, false);
				
				if ($bOK === '1')
				{
					
					$this->shop->addBackendMessage(__('Datei für das Downloadmodul erfolgreich hochgeladen.', 'wpsg'));
					
				}
				else
				{
					
					$this->shop->addBackendError($bOK);
					
				}
				
			}
			
		} // public function produkt_save(&$produkt_id)
		
		/**
		 * 
		 * lädt eine Datei in das entspr. upload Verzeichnis
		 */
		private function fileUpload($produkt_id, $die = true)
		{
			
			$temp = wpsg_getStr($_FILES['wpsg_mod_downloadprodukte_file']['tmp_name'][0]);
			
			if (file_exists($temp)) {
				
				$uploaddir = $this->getFilePath($produkt_id);
				
				$this->shop->protectDirectory($uploaddir);
								
				$uploadfile = $uploaddir.basename($_FILES['wpsg_mod_downloadprodukte_file']['name'][0]);
				
				move_uploaded_file($_FILES['wpsg_mod_downloadprodukte_file']['tmp_name'][0], $uploadfile);
				
				if ($die) die('1');
				else return '1';
				
			}
			
		} // private function fileUpload($produkt_id)
		
		/**
		 * erzeugt ein ARRAY mit den Produktdateien
		 * @param str $produkt_id
		 * @return array Files
		 */
		public function getProdFiles($produkt_id)
		{

			if (!file_exists($this->getFilePath($produkt_id))) return false;

			$path = $this->getFilePath($produkt_id);
			
			$arrFiles = scandir($path);
			
			foreach ($arrFiles as $file)
			{
				
				if (is_file($path.$file) && $file !== '.htaccess')
				{
					
					$arProdFiles[] = array(
						$this->getFilePath($produkt_id, true).$file,
						$path.$file
					);
					
				}
				
			}
			
			if (!wpsg_isSizedArray($arProdFiles)) return false;
			
			return $arProdFiles;
			
		} // private function getProdFiles($produkt_id)
				
		/**
		 * 
		 * Enter description here ...
		 * @param str $produkt_id
		 */
		public function getProdFileListe($produkt_id)
		{
			
			$this->shop->view['prodFiles'] = $this->getProdFiles($produkt_id);
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/produkt_edit_list.phtml', false);
			
		} // public function getProdFileListe($produkt_id)
		
		/**
 		 * Gibt den Absoluten Pfad zurück wo die Dateien gespeichert sind
 		 * Ist der Parameter $url auf true so wird der relative Pfad für die Ausgabe in URLs zurückgegeben
 		 */
 		public function getFilePath($produkt_id, $url = false)
 		{
 		 
 			if ($this->shop->isMultiBlog())
			{

				if ($url) return WPSG_URL_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_downloadprodukte/'.$produkt_id.'/';
				else 
				{
					
					$path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_downloadprodukte/'.$produkt_id.'/'; 
					
					if (!file_exists($path))
					{
						mkdir($path, 0777, true);
					}
					
					return $path;
					
				}
				 
			}
			else
			{
				
				if ($url) return WPSG_URL_CONTENT.'uploads/wpsg/wpsg_downloadprodukte/'.$produkt_id.'/';
				else 
				{
					
					$path = WP_CONTENT_DIR.'/uploads/wpsg/wpsg_downloadprodukte/'.$produkt_id.'/';
					
					if (!file_exists($path)) mkdir($path, 0777, true);
					
					return $path;
					
				}
				
			}
			 
 		} // private function getFilePath($produkt_id, $url = false)
 		
 		/**
 		 * 
 		 * entfernt eine Datei aus dem Downloadprodukt
 		 */
 		public function fileDelete()
 		{
 			unlink($this->getFilePath(wpsg_q($_REQUEST['edit_id'])).wpsg_q($_REQUEST['file']));
 			
 			die($this->getProdFileListe(wpsg_q($_REQUEST['edit_id'])));
 			
 		} // public function fileDelete()
 		
 		/**
 		 * 
 		 * Enter description here ...
 		 * @param unknown_type $arShipping
 		 */
 		public function addShipping(&$arShipping, $va_active = false) {

            if ($this->shop->get_option('wpsg_mod_downloadprodukte_shipping') != '1') return;
			 
			$arShipping[$this->id] = array(
				'id' => $this->id,
				'name' => __('Versand per Mail', 'wpsg'),//$this->shop->get_option('wpsg_mod_freeshipping_bezeichnung'),
				'price' => 0,
				'tax_key' => 0
			);		
			 
		} // public function addShipping(&$arShipping)
		
		public function basket_toArray(&$produkt, $backend = false, $noMwSt = false) 
		{
						
			if ($this->shop->get_option('wpsg_mod_downloadprodukte_einsplusx') != '1')
			{

				$arFiles = $this->getProdFiles($this->shop->getProduktId($produkt['id']));
				
				if (wpsg_isSizedArray($arFiles))
				{
				
					if ($produkt['menge'] != 1)
					{
					
						// Datei ist ein Downloadprodukt
						$produkt['menge'] = '1';
						
						// In der Session auch korrigieren
						if (isset($_SESSION['wpsg']['basket']))
						{
							foreach ((array)$_SESSION['wpsg']['basket'] as $k => $v) { { if ($v['id'] == $produkt['id']) $_SESSION['wpsg']['basket'][$k]['menge'] = 1; } }
						}
					}
					
					$produkt['oneOnly'] = true;
					
				}
				
			}
			
		} // public function basket_toArray(&$produkt, $backend = false, $noMwSt = false) 
				
		/**
		 * ändert den Status der Bestllung und sendet eine Mail mit dem Downloadlink(s) an den Kunde
		 * @param unknown_type $order_id
		 * @param unknown_type $status_id
		 * @param unknown_type $inform
		 */
		public function setOrderStatus($order_id, $status_id, $inform)
		{
			
			//die(wpsg_debug($this->id));
			if ($status_id == 100 && $inform == true)
			{
				
				$strLinks = '';
				
				$basket = new wpsg_basket();
				$basket->initFromDB($order_id);
				$arrBasket = $basket->toArray();
				
				$orderValues = $this->db->fetchRow("SELECT 
														* 
													FROM 
														`".WPSG_TBL_ORDER."`
													WHERE `id` = '".$order_id."'
												");
				 
				foreach ($arrBasket['produkte'] as $produkt) {
					
					for ($i = 1; $i <= intval($produkt['menge']); $i ++) {
											
						$arProductFiles = $this->getProdFiles($this->shop->getProduktID($produkt['id']));
	
						if (wpsg_isSizedArray($arProductFiles)) {
						
							foreach ($arProductFiles as $p_id => $f) {	
						
								$chash = md5($orderValues['cdate'].basename($f[0]));
								
								if (strpos(get_permalink($this->shop->get_option('wpsg_page_basket')), "?") > -1)	
								{			
									
									$strLinks .= get_permalink($this->shop->get_option('wpsg_page_basket'))."&plugin=1&noheader=1&m_id=".$this->id."&order=".$order_id."&file=".rawurlencode(basename($f[0]))."&produkt=".$this->shop->getProduktID($produkt['id'])."&chash=".$chash."\r\n\r\n";
									$arLinks[] = get_permalink($this->shop->get_option('wpsg_page_basket'))."&plugin=1&noheader=1&m_id=".$this->id."&order=".$order_id."&file=".rawurlencode(basename($f[0]))."&produkt=".$this->shop->getProduktID($produkt['id'])."&chash=".$chash;
									
								}
								else
								{
	
									$strLinks .= get_permalink($this->shop->get_option('wpsg_page_basket'))."?plugin=1&noheader=1&m_id=".$this->id."&order=".$order_id."&file=".rawurlencode(basename($f[0]))."&produkt=".$this->shop->getProduktID($produkt['id'])."&chash=".$chash."\r\n\r\n";
									$arLinks[] = get_permalink($this->shop->get_option('wpsg_page_basket'))."?plugin=1&noheader=1&m_id=".$this->id."&order=".$order_id."&file=".rawurlencode(basename($f[0]))."&produkt=".$this->shop->getProduktID($produkt['id'])."&chash=".$chash; 
									
								}
		
							}
							
						}
						
					}
							
				}
				
				if (strlen($strLinks) > 0)
				{
				
					$order_data = $this->shop->cache->loadOrder($order_id);
					$customer_data = $this->shop->cache->loadKunden($order_data['k_id']);
					
					$this->shop->view['data']['strLinks'] = $strLinks;
					$this->shop->view['data']['basket'] = $arrBasket;
					$this->shop->view['data']['tage'] = $this->shop->get_option('wpsg_mod_downloadprodukte_days');
					$this->shop->view['data']['order_id'] = $order_id;
					$this->shop->view['order'] = $order_data;
					$this->shop->view['customer'] = $customer_data;
					$this->shop->view['arLinks'] = $arLinks;
										
					// ist die Bestellung in einer anderen Sprache gemacht worden?
					$order_data = $this->shop->cache->loadOrder($order_id);
					if (trim($order_data['language']) != '') $this->shop->setTempLocale($order_data['language']);
					
					$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/zahlung_downloadprodukt.phtml', false);
					
					if ($this->shop->get_option('wpsg_htmlmail') === '1')
					{
						
						$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_downloadprodukte/zahlung_downloadprodukt_html.phtml', false);
						
					}
					else 
					{
						
						$mail_html = false;
						
					}
					
					$empfaenger = $arrBasket['checkout']['email']; 
										
					$this->shop->sendMail($mail_text, $empfaenger, 'downloadprodukte', array(), $order_id, false, $mail_html);	
					
					// Sprache zurücksetzen
					$this->shop->restoreTempLocale();
					
					$this->db->importQuery(WPSG_TBL_OL, array(
						"cdate" => "NOW()",
						"o_id" => wpsg_q($order_id),
						"title" => __('E-Mail mit Links zum Download der Produkte', 'wpsg'),
						"mailtext" => wpsg_q($mail_text)
					));
					
				}

			}			
			
		} // public function setOrderStatus($order_id, $status_id, $inform)
		
		/**
		 * 
		 * wird ausgeführt wenn der Kunde den Link aus seiner Mail aufruft
		 */
		public function template_redirect()
		{
			
			if (!isset($_REQUEST['file'])) return;
			
			if ($_REQUEST['m_id'] == '601')
			{
				
				$basket = new wpsg_basket();
				$basket->initFromDB($_REQUEST['order']);
				$arrBasket = $basket->toArray();
				
				$order = $this->db->fetchRow("
					SELECT 
						* 
					FROM 
						`".WPSG_TBL_ORDER."`
					WHERE 
						`id` = '".$_REQUEST['order']."'
				");
								
				// Bestellung storniert?
				if ($order['status'] == "500")
				{
					die(__("Bestellung wurde zwischenzeitlich storniert.", "wpsg"));
				}
				
				// Datum prüfen
				if ($this->shop->get_option("wpsg_mod_downloadprodukte_zt") == "" || $this->shop->get_option("wpsg_mod_downloadprodukte_zt") == "0")
				{
					
					$arOdate = explode('-', trim(str_replace('00:00:00', '', $order['payed_date'])));
					
					// Tage				
					if (mktime(0, 0, 0, $arOdate[1], $arOdate[2], $arOdate[0]) + 86400 * $this->shop->get_option('wpsg_mod_downloadprodukte_days') < time())
					{
						
						die($this->shop->get_option('wpsg_mod_downloadprodukte_raid'));
						//die("Die Zeit ist ausgelaufen, sie k&ouml;nnen dieses Produkt nicht mehr downloaden.");
						
					}
					
				}
				
				foreach ($arrBasket['produkte'] as $_p)
				{
					
					$_p['id'] = $this->shop->getProduktID($_p['id']);
					
					$path = $this->getFilePath($_p['id']);
					$arFiles = scandir($path);
					
					foreach ($arFiles as $f)
					{
						
						if (is_file($path.$f))
						{
							$_file = $f;
							
							if ($_file == rawurldecode($_REQUEST['file']))
							{
								
								// Hash prüfen
								if ($_REQUEST['chash'] == "" || md5($order['cdate'].$_file) != $_REQUEST['chash'])
								{
									die(__("Zugriffsfehler", 'wpsg'));
								}
							
								if ($this->shop->get_option("wpsg_mod_downloadprodukte_zt") == "1")
								{
									
									$counter = $this->db->fetchOne("SELECT 
																		OP.`mod_downloadprodukt_counter`
																	FROM 
																		`".WPSG_TBL_ORDERPRODUCT."` AS OP
																	WHERE 
																		OP.`o_id` = '".wpsg_q($_REQUEST['order'])."' AND
																		OP.`p_id` = '".wpsg_q($_p['id'])."'
																	");
									
									if ($counter >= $this->shop->get_option("wpsg_mod_downloadprodukte_days")) 
									{ 

										die($this->shop->get_option('wpsg_mod_downloadprodukte_raid'));
										//die(__("Download nicht mehr m&ouml;glich!", 'wpsg')); 
									
									}
									
								}
								 
								wpsg_ob_end_clean();
																	
								if ($this->shop->get_option("wpsg_mod_downloadprodukte_zip") == "1")
					   			{
					   				
						   			//$zipfile = tempnam("tmp", "zip");						   			
						   			$zipfile = tempnam($this->getTmpFilePath(), "wpsg").'.zip';
						   									   			
									$zip = new ZipArchive();									
									$zip->open($zipfile, ZipArchive::CREATE);
									$zip->addFile($this->getFilePath($_p['id']).$_file, basename($_file));																								
									$zip->close();
										
									$pi_file = pathinfo($_file);
									$zip_name = $pi_file['filename'].".zip";
										
									header('Content-Type: application/zip');
									header('Content-Length: ' . filesize($zipfile));
									header('Content-Disposition: attachment; filename="'.$zip_name.'"');
									readfile($zipfile);
									unlink($zipfile); 
					   				
					   			}
					   			else
					   			{
					   				 
					   				header('Content-type: application/download');
									header('Content-Disposition: inline; filename="'.basename($_file).'"');
									header('Expires: 0');
									header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
									header('Pragma: public');

									readfile($this->getFilePath($_p['id']).$_file);
									
				   				}
				   				
				   				// Zähler hochzählen wenn im Zählmodus 
								if ($this->shop->get_option("wpsg_mod_downloadprodukte_zt") == "1")
								{
										
									$this->db->UpdateQuery(WPSG_TBL_ORDERPRODUCT, array(
										"mod_downloadprodukt_counter" => ($counter + 1)
									), "`o_id` = '".wpsg_q($_REQUEST['order'])."' AND `p_id` = '".wpsg_q($_REQUEST['produkt'])."'");
										
								}
								
								die();
								
							} 
							
						}
						
					}
					
				}
				
				die(__('Die Datei wurde nicht gefunden', 'wpsg'));
			}	
			
		}// public function template_redirect()
 
		/**
		 * zeigt das Formular zur Mailconfiguration
		 */
		public function admin_emailconf() 
		{ 
			
			echo wpsg_drawEMailConfig(
				'downloadprodukte',
				__('Downloadprodukte (Kunde)', 'wpsg'),
				__('Diese Mail bekommt der Kunde, darin sind die Links zu den Produkten enthalten.', 'wpsg'));
						
		} // public function admin_emailconf()
		
		/**
		 * speichert die mailconfiguration zum Modul
		 */
		public function admin_emailconf_save()
		{
			
			wpsg_saveEMailConfig("downloadprodukte");
						
		} // public function admin_emailconf_save()
		
	} // class wpsg_mod_downloadprodukte extends wpsg_mod_basic 

