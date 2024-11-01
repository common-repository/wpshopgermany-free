<?php

	/**
	 * Modul für die verschiedenen Export Profile
	 */
	class wpsg_mod_export extends wpsg_mod_basic 
	{
		
		var $lizenz = 1;
		var $id = 300;
		var $inline = true;
		
		/** Hier sind alle möglichen Typen drin, wird von loadFields gefüllt */
		var $fields = array();
		
		/** Array für die möglichen Format Umwandlungen */
		var $arFieldFormats = array();
		
		/** @var array Array mit den Exportformaten (XML/CSV) */
		var $arExportFormats = array();
		
		/** @var array Array mit verfügbaren Charsets */
		var $arFileEncoding = array();
				
		/** @var array Array mit den Cron Auswahloptionen */
		var $arCronTypes = array();
		
		var $arCache = array();
		
		const TYPE_ORDER = '1';
		const TYPE_PRODUCT = '2';
		const TYPE_CUSTOMER = '3';
		const TYPE_ABO = '4';
		
		const ENCODING_UTF8 = '1';
		const ENCODING_ISO88591 = '2';
		
		const FORMAT_CSV = '1';
		const FORMAT_XML = '2';
		
		const CRON_OFF = '0';
		const CRON_LIVE = '1';
		const CRON_DAILY = '2';
		const CRON_WEEKLY = '3';
		const CRON_MONTHLY = '4';
		
		/**
		 * Costructor
		 */
		public function __construct()
		{ 
			 
			parent::__construct();
						
			$this->arFieldFormats = array(
				0 => __('Unverändert', 'wpsg'),
				100 => __('Zahl (99.99)', 'wpsg'),
				200 => __('Zahl (99,99)', 'wpsg'),
				300 => __('Währung (99,99 €)', 'wpsg'),
				400 => __('Datum (TT.MM.YYYY)', 'wpsg'),
				500 => __('Zeit (hh:mm:ss)', 'wpsg'),
				600 => __('Datum und Zeit (TT.MM.YYYY hh:mm:ss)', 'wpsg'),
				700 => __('Benutzerdefiniert', 'wpsg'),
				800 => __('Text', 'wpsg'),
				900 => __('Text Excel 2007', 'wpsg')					
			);
			
			$this->arExportFormats = array(
				self::FORMAT_CSV => __('CSV', 'wpsg'),
				self::FORMAT_XML => __('XML', 'wpsg')
			);
			
			$this->arFileEncoding = array(
				self::ENCODING_UTF8 => __('UTF-8', 'wpsg'),
				self::ENCODING_ISO88591 => __('ISO-8859-1', 'wpsg')
			);
			
			$this->arCronTypes = array(
				wpsg_mod_export::CRON_OFF => __('Nie', 'wpsg'),
				wpsg_mod_export::CRON_LIVE => __('Mit jeder Cron Ausführung'),
                wpsg_mod_export::CRON_DAILY => __('Täglich', 'wpsg'),
                wpsg_mod_export::CRON_WEEKLY => __('Wöchentlich', 'wpsg'),
                wpsg_mod_export::CRON_MONTHLY => __('Monatlich', 'wpsg')	
			);
			
			$this->name = __('Exportprofile', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht es Produkte, Bestellungen und Kunden bezogen auf Bestellungen in verschiedene CSV Formate zu exportieren.', 'wpsg');

		} // public function __construct()
		
		public function install() 
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/*
		   	 * Tabelle für die Exportprofile
		   	 */
		   	$sql = "CREATE TABLE ".WPSG_TBL_EXPORTPROFILE." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name VARCHAR(255) NOT NULL COMMENT 'Der Name des Profils', 
		   		filename VARCHAR(255) NOT NULL COMMENT 'Dateiname für den Export',
		   		export_type INT(1) NOT NULL COMMENT 'Typ des Exportprofils (Produkt / Bestellung)',
		   		format INT(1) NOT NULL COMMENT 'Format des Exportes (XML/CSV)',
		   		field_delimiter VARCHAR(1) NOT NULL COMMENT 'Feld-Trennzeichen (CSV)',
		   	  	field_enclosure VARCHAR(1) NOT NULL COMMENT 'Feld-Begrenzungs Zeichen (CSV)',
		   	  	field_escape VARCHAR(1) NOT NULL COMMENT 'Maskierungs-Zeichen (CSV)',
		   	  	order_online INT(1) NOT NULL COMMENT 'Bestellungen in einer Zeile aufführen',
		   	  	order_onetime INT(1) NOT NULL COMMENT 'Bestellungen nur einmal exportieren',
		   	  	csv_fieldnames INT(1) NOT NULL COMMENT 'Beim CSV Export die Feldnamen in erster Zeile aufführen',
		   	  	cron_interval INT(1) NOT NULL DEFAULT '0' COMMENT 'Cron Eisntellunge (Inaktiv/Intervall)',
		   	  	cron_path VARCHAR(500) NOT NULL COMMENT 'Pfad in dem die automatischen Dateien abgelegt werden',
		   	  	cron_lastrun DATE NOT NULL COMMENT 'Letzte Ausführung des Crons',
		   	  	orderfilter TEXT NOT NULL COMMENT 'Serialisizerter Bestellfilter',
		   	  	xml_roottag TEXT NOT NULL COMMENT 'Tagname des XML Root Elements',
		   	  	xml_ordertag TEXT NOT NULL COMMENT 'Tagname des XML Bestellung Elements',
		   	  	xml_productroottag TEXT NOT NULL COMMENT 'Tagname des XML Produkt Rootelements',
		   	  	xml_producttag TEXT NOT NULL COMMENT 'Tagname des Produkt Elements',
		   	  	xml_customertag TEXT NOT NULL COMMENT 'Tagname des Kunden Elements (Kundenexport)',
		   	  	file_encoding INT(1) NOT NULL COMMENT 'Encoding der Datei',		   	  	
		   	  	PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		   	
		   	dbDelta($sql);

			/**
			 * Tabelle für die Felder des Exportprofils
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_EXPORTPROFILE_FIELDS." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				profil_id INT(11) NOT NULL COMMENT 'Link zu WPSG_TBL_EXPORTPROFILE',
				pos int(11) NOT NULL COMMENT 'Position im Export',
				name VARCHAR(255) NOT NULL COMMENT 'Spaltenname / Feldname im XML',
				value_key VARCHAR(255) NOT NULL COMMENT 'Der Schlüssel, mit dem der Wert gefüllt wird',
				format INT(2) NOT NULL COMMENT 'Zellenformat',
				xml_att INT(1) NOT NULL COMMENT 'Tag oder Attributexport',
				userformat VARCHAR(255) NOT NULL COMMENT 'Benutzerdefiniertes Format',
				clear_spaces INT(1) NOT NULL COMMENT 'Leerzeichen entfernen',
				INDEX profil_id (profil_id),
				PRIMARY KEY  (id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			 		   	
		} // public function install() 
		
		public function settings_edit()
		{
									
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/settings_edit.phtml'); 
						
		} // public function settings_edit()
		 
		public function profilList($selected_profile_id = false)
		{
		
			$this->shop->view['arProfile'] = $this->db->fetchAssoc("
				SELECT
					*
				FROM
					`".WPSG_TBL_EXPORTPROFILE."`
			"); 
			
            if ($selected_profile_id !== false) $this->shop->view['profil_id'] = $selected_profile_id;
			 			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/profillist.phtml');
			
		} // public function profilList()
						
		public function handleExport()
		{
						 
			$arProfile = wpsg_xss(array_values($_REQUEST['wpsg_mod_export_profile']));
			
			set_time_limit(300);
			
			parse_str($_REQUEST['filter'], $filter);
			if (!wpsg_isSizedArray($filter['filter'])) $filter['filter'] = array();
  
			$arFiles = array();
            
			if (sizeof($arProfile) >= 1)
			{
				
				$this->loadFields();
 						
				foreach ($arProfile as $profil_id)
				{
					
                    $profil = $this->loadProfil($profil_id);
                
					if ($profil['export_type'] === self::TYPE_ORDER)
					{
                    
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportCSV($profil_id, $filter['filter']);
                    	else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportXML($profil_id, $filter['filter']);
						
					}
					else if ($profil['export_type'] === self::TYPE_PRODUCT)
					{
						
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportProductCSV($profil_id, $filter['filter']);
						else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportProductXML($profil_id, $filter['filter']);
						
					}
					else if ($profil['export_type'] === self::TYPE_CUSTOMER)
					{
						
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportCustomerCSV($profil_id, $filter['filter']);
						else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportCustomerXML($profil_id, $filter['filter']);
						
					}
					else if ($profil['export_type'] === self::TYPE_ABO)
					{
					
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportAboCSV($profil_id, $filter['filter']);
						else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportAboXML($profil_id, $filter['filter']);
					
					}
                 
                    $arFiles[] = array(
                        $file,
                        $profil['filename'],
                        $profil['cron_path'],
						$profil['format']
                    );
                    
                }
				
			} 
            
            if (sizeof($arFiles) > 1)
            {
                
                // Zip erstellen und ablegen
                $zip = new ZipArchive();					
                $tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
                
                if ($zip->open($tmpfname, ZIPARCHIVE::CREATE) == true) 
                {

                    foreach ($arFiles as $f)
                    {
                    
                        $zip->addFile($f[0], $f[1]);
                        
                    }
                    
                    $zip->close();
                    
                    header("Content-Type: application/octet-stream"); 
                    header("Content-Disposition: attachment; filename=export.zip"); 
                    header("Pragma: no-cache"); 
                    header("Expires: 0"); 
                    
                    die(file_get_contents($tmpfname));
                    
                }
                else
                {
                    
                    throw new \Exception(__('Konnte Zip Archiv nicht erstellen!', 'wpsg'));
                    
                }

            }
            else
            {
            
                if ($arFiles[0][3] == self::FORMAT_CSV) header("Content-Type: text/csv");
				else header("Content-Type: text/xml");
				
                header("Content-Disposition: attachment; filename=".$arFiles[0][1]); 
                header("Pragma: no-cache"); 
                header("Expires: 0"); 
                    
                die(file_get_contents($arFiles[0][0]));
                    
            }
             
            $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order');
			
		} // public function handleExport()
		
		public function product_index_tab() 
		{ 
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/product_index_tab.phtml');
			
		} // public function product_index_tab() 
		
		public function order_index_tab(&$arTabs)
		{

			$this->shop->view['wpsg_mod_export']['arProfile'] = $this->db->fetchAssoc("
				SELECT 
					* 
				FROM 
					`".WPSG_TBL_EXPORTPROFILE."`
				WHERE
					`export_type` = '".self::TYPE_ORDER."'
			");
			
			if (sizeof($this->shop->view['wpsg_mod_export']['arProfile']) <= 0) return;
			
			$arTabs['left'][$this->id] = array(
				'tab_title' => __('Bestellexport', 'wpsg'),
				'tab_icon' => 'glyphicon glyphicon-export',
				'tab_content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/order_index_tab.phtml', false)
			);

		} // public function order_index_tab(&$arTabs)
		 		  		
		public function be_ajax()
		{ 
			
			if ($_REQUEST['do'] == 'wpsg_mod_export_addProfil')
			{
				
				$new_id = $this->db->ImportQuery(WPSG_TBL_EXPORTPROFILE, array(
					'name' => wpsg_q(__('Neues Profil', 'wpsg')),
					'filename' => 'export.csv',
					'export_type' => wpsg_q($_REQUEST['type']),
					'file_encoding' => wpsg_q(self::ENCODING_UTF8),
					'field_delimiter' => ';',
					'field_enclosure' => '"',
					'field_escape' => wpsg_q('\\'),
					'format' => wpsg_q(self::FORMAT_CSV)
				));
				
				die($this->profilList($new_id));
				
			}
			else if ($_REQUEST['do'] == 'handleExport')
			{
				
				die($this->handleExport());
				
			}
			else if ($_REQUEST['do'] == 'wpsg_mod_export_removeProfil')
			{
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_EXPORTPROFILE."` WHERE `id` = '".wpsg_q($_REQUEST['profil'])."'");
				
				die($this->profilList());
				
			}
			else if ($_REQUEST['do'] == 'wpsg_mod_export_removeField')
			{
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `id` = '".wpsg_q($_REQUEST['field_id'])."' ");
				
				die("1");
				
			}
			else if ($_REQUEST['do'] == 'wpsg_mod_export_reorder')
			{
				
				parse_str($_REQUEST['wpsg_reorder'], $reorder);
			
				foreach ($reorder['field'] as $k => $field_id)
				{
					
					$this->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, array(
						'pos' => wpsg_q($k)
					), " `id` = '".wpsg_q($field_id)."' ");
					
				}
				
				die("1");
				
			}
			else if ($_REQUEST['do'] == 'wpsg_mod_export_addField')
			{
                
                $max_pos = $this->db->fetchOne("SELECT MAX(`pos`) FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `profil_id` = '".wpsg_q($_REQUEST['profil_id'])."' ");
				
				$this->db->ImportQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, array(
					'profil_id' => wpsg_q($_REQUEST['profil_id']),
					'name' => __('Neues Feld', 'wpsg'),
                    'pos' => wpsg_q($max_pos + 1),
					'value_key' => '-1'
				));
				
				die($this->renderFields($this->loadProfil($_REQUEST['profil_id'])));
				
			}
			else if ($_REQUEST['do'] == 'wpsg_mod_export_profilSwitch')
			{
				
				die($this->renderProfil($_REQUEST['profil']));
								
			}
			else if ($_REQUEST['do'] == 'save')
			{
				
				$arProfil = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_EXPORTPROFILE."` WHERE `id` = '".wpsg_q($_REQUEST['profil_id'])."' ");
				$arProfil['fields'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `profil_id` = '".wpsg_q($arProfil['id'])."' ");
				
				wpsg_header::startDownloadContent('profil.json', json_encode($arProfil));
				exit;
				
			}
			else if ($_REQUEST['do'] == 'import')
			{
				
				$content = file_get_contents($_FILES['profil_file']['tmp_name']);
				$content = wpsg_removeBOM($content);
				$arData = @json_decode($content, true);
				
				if (!wpsg_isSizedArray($arData))
				{
					
					$this->shop->addBackendError(wpsg_translate(__('Datei ist keine gültige Profildatei. Error: #1#', 'wpsg'), json_last_error_msg()));
										
				}
				else
				{
					
					$arFields = $arData['fields']; unset($arData['fields']);
					
					unset($arData['id']); 
					unset($arData['cron_lastrun']);
					
					$profil_id = $this->db->ImportQuery(WPSG_TBL_EXPORTPROFILE, wpsg_q($arData), true);
					
					foreach ($arFields as $field_data)
					{
						
						unset($field_data['id']);
						$field_data['profil_id'] = $profil_id;
						
						$this->db->ImportQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, wpsg_q($field_data), true);
						
					}
					
					$this->shop->addBackendMessage(wpsg_translate(__('Profil als "#1#" erfolgreich importiert.', 'wpsg'), $arData['name']));
					
				}
				
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_export');
				
			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{

				if (preg_match('/field_/', $_REQUEST['name']) && !in_array($_REQUEST['name'], ['field_delimiter', 'field_enclosure', 'field_escape']))
				{

					// Feldwert wurde bearbeitet
					$field_id = preg_replace('/(.*)\_/', '', $_REQUEST['name']);
					$col = preg_replace('/\_\d+$/', '', $_REQUEST['name']);
								
					switch ($col) 
					{
						
						case 'field_name': $col = 'name'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
						case 'field_format': $col = 'format'; break;
						case 'field_userformat': $col = 'userformat'; break;
						case 'field_typ': $col = 'value_key'; break;
						case 'field_xml_att': $col = 'xml_att'; break;
						
						default: throw new \Exception(__('Ungültiger Feldname (Feld)', 'wpsg'));
							
					}
					
					$this->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, array(
						wpsg_q($col) => wpsg_q(wpsg_xss($_REQUEST['value']))
					), " `id` = '".wpsg_q($field_id)."' ");
					
					echo $this->db->fetchOne("SELECT `".wpsg_q($col)."` FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `id` = '".wpsg_q($field_id)."' "); 
					exit;
					
				}
				else if (preg_match('/orderfilter_/', $_REQUEST['name']))
				{

					// Wert aus dem Bestellfilter wurde bearbeitet					
					$col = substr($_REQUEST['name'], 12);
								
					switch ($col) 
					{
						
						case 's': $col = 's'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
						case 'k_id': $col = 'k_id'; break;
						case 'status': $col = 'status'; break;
						case 'cdate_m': $col = 'cdate_m'; break;
						case 'cdate_y': $col = 'cdate_y'; break;
						case 'invoicedate_m': $col = 'invoicedate_m'; break;
						case 'productgroup_ids': $col = 'productgroup_ids'; break;
						case 'productcategory_ids': $col = 'productcategory_ids'; break;
											
						default: throw new \Exception(__('Ungültiger Feldname (Bestellfilter)', 'wpsg'));
							
					}
					
					$orderfilter = $this->db->fetchOne("SELECT `orderfilter` FROM `".WPSG_TBL_EXPORTPROFILE."` WHERE `id` = '".wpsg_q($_REQUEST['profil_id'])."' ");
					$orderfilter = @unserialize($orderfilter);
					if (!is_array($orderfilter)) $orderfilter = array();
					
					$orderfilter[$col] = $_REQUEST['value'];
					
					$this->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE, array('orderfilter' => wpsg_q(serialize($orderfilter))), " `id` = '".wpsg_q($_REQUEST['profil_id'])."' ");
					
					echo $orderfilter[$col];
					exit;
					
				}
				
				$col = null;
				
				switch ($_REQUEST['name'])
				{
					
					case 'name': $col = 'name'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'filename': $col = 'filename'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'file_encoding': $col = 'file_encoding'; break;
					case 'format': $col = 'format'; break;
					case 'field_delimiter': $col = 'field_delimiter'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
		   	  		case 'field_enclosure': $col = 'field_enclosure'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'field_escape': $col = 'field_escape'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'order_online': $col = 'order_online'; break;
					case 'field_delimiter': $col = 'field_delimiter'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'field_enclosure': $col = 'field_enclosure'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'field_escape': $col = 'field_escape'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'order_onetime': $col = 'order_onetime'; break;
					case 'csv_fieldnames': $col = 'csv_fieldnames'; break; 
					case 'cron_interval': $col = 'cron_interval'; break;
					case 'cron_path': $col = 'cron_path'; $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']); break;
					case 'xml_roottag': $col = 'xml_roottag'; break;
					case 'xml_ordertag': $col = 'xml_ordertag'; break;
					case 'xml_productroottag': $col = 'xml_productroottag'; break;
					case 'xml_customertag': $col = 'xml_customertag'; break;
					case 'xml_producttag': $col = 'xml_producttag'; break;
															
					default: throw new \Exception(__('Ungültiger Feldname (Profil)', 'wpsg'));
					
				}

				$this->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE, array(
					wpsg_q($col) => wpsg_q($_REQUEST['value']) 
				), " `id` = '".wpsg_q($_REQUEST['profil_id'])."' ");
												
				echo $this->db->fetchOne("SELECT `".wpsg_q($col)."` FROM `".WPSG_TBL_EXPORTPROFILE."` WHERE `id` = '".wpsg_q($_REQUEST['profil_id'])."' "); 
				exit;
				 
			}
			else if ($_REQUEST['do'] == 'musterupload')
			{
				
				$arProfil = $this->loadProfil($_REQUEST['profil_id']);
						 
				if ($arProfil['file_encoding'] == self::ENCODING_ISO88591)
				{

					$strFileContent = wpsg_toUtf8(file_get_contents($_FILES['file']['tmp_name']));
					
				}
				else
				{
				
					$strFileContent = file_get_contents($_FILES['file']['tmp_name']);
					
				}

				if ($arProfil['format'] == self::FORMAT_CSV)
				{
									
					// Alles nach erstem Zeilenumbruch entfernen
					$strFileContent = preg_replace("/\r(.*)/s", "", $strFileContent);
	
					$arFields = explode($arProfil['field_delimiter'], $strFileContent);
					 
					if (sizeof($arFields) > 1) 
					{
						 						
						foreach ($arFields as $f) 
						{
								
							// Hochkommas am Anfang und am Ende abtrennen
							if (preg_match('/^\"(.*)\"$/', $f)) $f = substr($f, 1, strlen($f) - 2);
	
							// Existiert das Feld schon
							$existID = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `profil_id` = '".wpsg_q($_REQUEST['profil_id'])."' AND `name` = '".wpsg_q($f)."' ");
							
							if (!wpsg_isSizedInt($existID))
							{
							
								$this->db->ImportQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, array(
									'profil_id' => wpsg_q($_REQUEST['profil_id']),
									'name' => wpsg_q($f),
									'value_key' => '-1'
								));
								
							} 						
							
						}
					
					}
										
				}
					
				$this->shop->addBackendMessage(__('Muster Datei erfolgreich verarbeitet.', 'wpsg'));
				
				die($this->renderFields($this->loadProfil($_REQUEST['profil_id'])));
								
			}
		
		} // public function be_ajax()
		
		public function cron() 
		{ 
			
			// Alle Profile laden, die automatisiert aufgerufen werden
			$arProfile = $this->db->fetchAssoc("
				SELECT
					EXP.`id`, EXP.`cron_interval`, EXP.`cron_lastrun`, EXP.`orderfilter`, EXP.`cron_path`, EXP.`filename`, EXP.`format`, EXP.`export_type`
				FROM
					`".WPSG_TBL_EXPORTPROFILE."` AS EXP 
				WHERE
					EXP.`cron_interval` > 0
			");
			
			foreach ($arProfile as $profil)
			{
				
				if ($profil['cron_lastrun'] == '0000-00-00') $cron_lastrun = 0;
				else $cron_lastrun = strtotime($profil['cron_lastrun']);
				
				$bRun = false;
				if ($cron_lastrun <= 0) $bRun = true;
				else 
				{
					
					switch ($profil['cron_interval'])
					{
						
						case self::CRON_LIVE: 
							
							$bRun = true;
							break;
						
						case self::CRON_DAILY:
							
							if (strtotime("+1 day", $cron_lastrun) < time()) $bRun = true;
							break;
						
						case self::CRON_MONTHLY:
							
							if (strtotime("+1 month", $cron_lastrun) < time()) $bRun = true;
							break;
						
						case self::CRON_WEEKLY:
							
							if (strtotime("+1 week", $cron_lastrun) < time()) $bRun = true;
							break;
						
					}
					
				}
				
				if ($bRun === true)
				{
					
					$arFilter = @unserialize($profil['orderfilter']);
					if (!wpsg_isSizedArray($arFilter)) $arFilter = array();
					
					if ($profil['export_type'] == self::TYPE_ORDER)
					{
                    
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportCSV($profil['id'], $arFilter);
                    	else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportXML($profil['id'], $arFilter);
						
					}
					else if ($profil['export_type'] == self::TYPE_PRODUCT)
					{
						
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportProductCSV($profil['id'], $arFilter);
                    	else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportProductXML($profil['id'], $arFilter);
						
					}
					else if ($profil['export_type'] == self::TYPE_CUSTOMER)
					{
						
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportCustomerCSV($profil['id'], $arFilter);
                    	else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportCustomerXML($profil['id'], $arFilter);
						
					}
					else if ($profil['export_type'] == self::TYPE_ABO)
					{
					
						if ($profil['format'] == self::FORMAT_CSV) $file = $this->handleExportAboCSV($profil['id'], $arFilter);
						else if ($profil['format'] == self::FORMAT_XML) $file = $this->handleExportAboXML($profil['id'], $arFilter);
					
					}
					
                    if (wpsg_remoteconnection::handleConenctionString($profil['cron_path'], $profil['filename'], $file) !== true)
					{
						
						$target_path = $profil['cron_path'];
						if (is_dir($target_path)) $target_path = $target_path.'/'.$profil['filename'];
									
						file_put_contents($target_path, file_get_contents($file));
												
					}
									
					// Letzte Ausführung speichern
					$this->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE, array(
						'cron_lastrun' => 'NOW()'
					), " `id` = '".wpsg_q($profil['id'])."' ");
					
				}
				
			}
			
		} // public function cron()
		
		/* Modulfunktionen */

		/**
		 * Gibt einen Array der Profile aus der Datenbank zurück
		 * @param Array $type
		 */
		public function getProfile($type)
		{
			
			return $this->db->fetchAssoc("
				SELECT
					P.*
				FROM
					`".WPSG_TBL_EXPORTPROFILE."` AS P
			  	WHERE
					P.`export_type` = '".wpsg_q($type)."'  
			");
			
		} // public function getProfile($type)

		/**
		 * Erstellt die CSV und gibt den Pfad zur erstellten Datei zurück (Für einen Produktexport)
		 * 
		 * @param $profil_id
		 * @param array $arOrderFilter
		 */
		private function handleExportProductCSV($profil_id, $arProductFilter = array()) 
		{
			 
			$this->loadFields(); 
			
			$arData = wpsg_product::find($arProductFilter); 

            $tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            $filehandler = fopen($tmpfname, 'w');
            
            $profil = $this->loadProfil($profil_id);
            
            $arExportData = array();
            
            if ($profil['csv_fieldnames'] == '1')
            {
                
                $row = array();
                
                foreach ($profil['fields'] as $f)
                {
                    
                    $row[] = $f['name'];
                    
                }
                
                $arExportData[] = $row;
                
            }
            
            foreach ($arData as $oProduct) 
            {
                
				$row = array();
				                        
				foreach ($profil['fields'] as $f)
				{

					$row[] = $this->getValue($f, $profil['field_delimiter'], false, $oProduct->id);
														
				}

                $arExportData[] = $row; 
                                
            } // foreach arData
             
            foreach ($arExportData as $row)
            {
                
                fputcsv($filehandler, $row, $profil['field_delimiter'], $profil['field_enclosure'], $profil['field_escape']);
                                    
            }
            
            fclose($filehandler);
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
                        
            return $tmpfname;
			
		} // private function handleExportProductXML($profil_id, $arOrderFilter = array())
		
        /**
		 * Erstellt die CSV und gibt den Pfad zur erstellten Datei zurück (Für einen Bestellexport)
         * 
         * @param $profil_id
         * @param array $arOrderFilter
         */
		private function handleExportCSV($profil_id, $arOrderFilter = array())
		{
			
			$this->loadFields();
			
			$arData = wpsg_order::find($arOrderFilter, false); 
												
            $tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            $filehandler = fopen($tmpfname, 'w');
            
            $profil = $this->loadProfil($profil_id);
            
            $arExportData = array();
            
            if ($profil['csv_fieldnames'] == '1')
            {
                
                $row = array();
                
                foreach ($profil['fields'] as $f)
                {
                    
                    $row[] = $f['name'];
                    
                }
                
                $arExportData[] = $row;
                
            }
            
            foreach ($arData as $order_id) {
                
				$row = array();
				
                if ($profil['order_online'] == '1')
                {
                
                    foreach ($profil['fields'] as $f) 
                    {

                        $row[] = $this->getValue($f, $profil['field_delimiter'], $order_id);
                        
                    }
                    
                    $arExportData[] = $row; 
                    
                }
                else 
                {
                    
                    $arProdukte = $this->db->fetchAssoc("
                        SELECT 
                            OP.`p_id`, 
                            OP.`productkey`, 
                            OP.`product_index`,
                            OP.`id` AS `order_product_id`
                        FROM 
                            `".WPSG_TBL_ORDERPRODUCT."` AS OP 
                        WHERE 
                            OP.`o_id` = '".wpsg_q($order_id)."'
                    ");
                    
                    foreach ($arProdukte as $p)
                    {
                    
                        $row = array();
                        
                        foreach ($profil['fields'] as $f)
                        {

                            $row[] = $this->getValue($f, $profil['field_delimiter'], $order_id, $p['p_id'], $p['product_index'], $p['productkey'], $p['order_product_id']);
                                                                
                        }

                        $arExportData[] = $row; 
                        
                    }
                    
                }
	
				if (\memory_get_usage() > 200000) {
		
					$this->arCache = [];
		
				}
	
				wpsg_product::clearCache();
				wpsg_order::clearCache($order_id);
				wpsg_customer::clearCache();
				wpsg_order_product::clearCache();
	
				$this->shop->cache->clearOrderCache(false);
				$this->shop->cache->clearProductCache(false);
				$this->shop->cache->clearKundenCache(false);
	
				gc_collect_cycles ();
	
			} // foreach arData
             
            foreach ($arExportData as $row)
            {
                
                fputcsv($filehandler, $row, $profil['field_delimiter'], $profil['field_enclosure'], $profil['field_escape']);
                                    
            }
            
            fclose($filehandler);
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
                        
            return $tmpfname;
            			
		} // private function handleExportCSV($arProfile, $arOrderFilter = array(), $cron = false)

		/**
		 * Erstellt die CSV und gibt den Pfad zur erstellten Datei zurück (Für einen Aboexport)
		 * 
		 * 
		 */
		private function handleExportAboCSV() 
		{
			
			//TODO
			
		}
		
		/**
		 * Erstellt die CSV und gibt den Pfad zur erstellten Datei zurück (Für einen Kundenexport)
		 * 
		 * @param $profil_id
		 * @param array $arOrderFilter
		 */
		private function handleExportCustomerCSV($profil_id, $arCustomerFilter = array())
		{
			
			$this->loadFields(); 
			
			$arData = wpsg_customer::find($arCustomerFilter); 

            $tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            $filehandler = fopen($tmpfname, 'w');
            
            $profil = $this->loadProfil($profil_id);
            
            $arExportData = array();
            
            if ($profil['csv_fieldnames'] == '1')
            {
                
                $row = array();
                
                foreach ($profil['fields'] as $f)
                {
                    
                    $row[] = $f['name'];
                    
                }
                
                $arExportData[] = $row;
                
            }
            
            foreach ($arData as $oCustomer) 
            {
                
				$row = array();
				                        
				foreach ($profil['fields'] as $f)
				{

					$row[] = $this->getValue($f, $profil['field_delimiter'], false, false, 1, false, false, $oCustomer->id);
														
				}

                $arExportData[] = $row; 
                                
            } // foreach arData
             
            foreach ($arExportData as $row)
            {
                
                fputcsv($filehandler, $row, $profil['field_delimiter'], $profil['field_enclosure'], $profil['field_escape']);
                                    
            }
            
            fclose($filehandler);
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
                        
            return $tmpfname;
			
		} // private function handleExportCustomerCSV($profil_id, $arOrderFilter = array())
		
		/**
		 * Erstellt die XML und gibt den Pfad zur erstellten Datei zurück (Für einen Produktexport)
		 * 
		 * @param $profil_id
		 * @param array $arProductFilter
		 * @return string
		 * @throws Exception
		 */
		private function handleExportProductXML($profil_id, $arProductFilter = array())
		{
			
			$this->loadFields();
			
			$arData = wpsg_product::find($arProductFilter);
			$profil = $this->loadProfil($profil_id);
			
			if (!wpsg_isSizedString($profil['xml_roottag'])) throw new \Exception(wpsg_translate(__('Kein XML Root Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
			if (!wpsg_isSizedString($profil['xml_producttag'])) throw new \Exception(wpsg_translate(__('Kein XML Produkt Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
			
			$doc = new DOMDocument('1.0');
            $doc->formatOutput = true;
            
            $root = $doc->createElement($this->clearXML($profil['xml_roottag']));					
            $root = $doc->appendChild($root);
			
			foreach ($arData as $oProduct)
            {
                
                $tag_product = $doc->createElement($this->clearXML($profil['xml_producttag']));
                $tag_product = $root->appendChild($tag_product);
			
				foreach ($profil['fields'] as $f)
                {
                    
                    $value = $this->getValue($f, $profil['field_delimiter'], false, $oProduct->id);
                                                    
					if (wpsg_isSizedInt($f['xml_att']))
					{
						
						$att = $doc->createAttribute($this->clearXML($f['name']));
						$att->value = htmlspecialchars($value);
						
						$tag_product->appendChild($att);

						
					}
					else
					{
						
						$tag = $doc->createElement($this->clearXML($f['name']));
						$tag = $tag_product->appendChild($tag);
						
						$tag_value = $doc->createTextNode(htmlspecialchars($value));
						$tag->appendChild($tag_value);
						
					}
                                         
                }
				
			}
			
			$tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            file_put_contents($tmpfname, $doc->saveXML());
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
            
            return $tmpfname;
			
		} // private function handleExportProductXML($profil_id, $arProductFilter = array())
		
        /**
         * Erstellt die XML und gibt den Pfad zur erstellten Datei zurück (Für einen Bestellexport)
         * 
         * @param $arProfile
         * @param array $arOrderFilter
         */
		private function handleExportXML($profil_id, $arOrderFilter = array())
		{
			
			$this->loadFields();
			
			$arData = wpsg_order::find($arOrderFilter); 
												
            $profil = $this->loadProfil($profil_id);
            
            if (!wpsg_isSizedString($profil['xml_roottag'])) throw new \Exception(wpsg_translate(__('Kein XML Root Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
            if (!wpsg_isSizedString($profil['xml_ordertag'])) throw new \Exception(wpsg_translate(__('Kein XML Bestellung Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
            if (!wpsg_isSizedString($profil['xml_productroottag'])) throw new \Exception(wpsg_translate(__('Kein XML Produkt Roottagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
            if (!wpsg_isSizedString($profil['xml_producttag'])) throw new \Exception(wpsg_translate(__('Kein XML Produkt Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
            
            $doc = new DOMDocument('1.0');
            $doc->formatOutput = true;
            
            $root = $doc->createElement($this->clearXML($profil['xml_roottag']));					
            $root = $doc->appendChild($root);
            
            foreach ($arData as $oOrder)
            {
                
                $tag_order = $doc->createElement($this->clearXML($profil['xml_ordertag']));
                $tag_order = $root->appendChild($tag_order);
                 
                $tag_product_root = false; 
                
                if (wpsg_isSizedString($profil['xml_productroottag']))
                {
                    
                    $tag_product_root = $doc->createElement($this->clearXML($profil['xml_productroottag']));
                    $tag_product_root = $tag_order->appendChild($tag_product_root);
                                                
                }
                
                foreach ($profil['fields'] as $f)
                {
                    
                    // Wert ist kein Produktfeld
                    if ($this->getFieldGroup($f['value_key']) != 20)
                    {
                        
                        $value = $this->getValue($f, $profil['field_delimiter'], $oOrder->id);
                                                    
                        if (wpsg_isSizedInt($f['xml_att']))
                        {
                            
                            $att = $doc->createAttribute($this->clearXML($f['name']));
                            $att->value = htmlspecialchars($value);
                            
                            $tag_order->appendChild($att);

                            
                        }
                        else
                        {
                            
                            $tag = $doc->createElement($this->clearXML($f['name']));
                            $tag = $tag_order->appendChild($tag);
                            
                            $tag_value = $doc->createTextNode(htmlspecialchars($value));
                            $tag->appendChild($tag_value);
                            
                        }
                        
                    }
                    
                }
                
                // Produkte
                $arOrderProducts = $oOrder->getOrderProducts();
                
                foreach ($arOrderProducts as $oOrderProduct)
                {
                    
                    $tag_product = false;
                    
                    foreach ($profil['fields'] as $f)
                    {
                                                        
                        // Wert ist ein Produktfeld
                        if ($this->getFieldGroup($f['value_key']) == 20)
                        {
                                        
                            if ($tag_product === false) $tag_product = $doc->createElement($this->clearXML($profil['xml_producttag']));
                            
                            $value = $this->getValue($f, $profil['field_delimiter'], $oOrder->id, $oOrderProduct->getProductId(), $oOrderProduct->getProductIndex(), $oOrderProduct->getProductKey());
                        
                            if (wpsg_isSizedInt($f['xml_att']))
                            {
                                
                                $att = $doc->createAttribute($this->clearXML($f['name']));
                                $att->value = htmlspecialchars($value);
                                
                                $tag_product->appendChild($att);

                                
                            }
                            else
                            {
                                
                                $tag = $doc->createElement($this->clearXML($f['name']));
                                $tag = $tag_product->appendChild($tag);
                                
                                $tag_value = $doc->createTextNode(htmlspecialchars($value));
                                $tag->appendChild($tag_value);
                                
                            }
                            
                        }
                    }
                    
                    if ($tag_product !== false)
                    {
                        
                        $tag_product_root->appendChild($tag_product);
                        
                    }
                    
                } // foreach Produkte
                 
            } // foreach Order
            
            $tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            file_put_contents($tmpfname, $doc->saveXML());
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
            
            return $tmpfname;
              			
		} // private function handleExportXML($arProfile, $arOrderFilter = array(), $cron = false)

		/**
		 * Erstellt die XML und gibt den Pfad zur erstellten Datei zurück (Für einen Kundenexport)
		 * 
		 * @param $profil_id
		 * @param array $arOrderFilter
		 */
		private function handleExportCustomerXML($profil_id, $arCustomerFilter = array()) 
		{
			
			$this->loadFields();
			
			$arData = wpsg_customer::find($arCustomerFilter);
			$profil = $this->loadProfil($profil_id);
			
			if (!wpsg_isSizedString($profil['xml_roottag'])) throw new \Exception(wpsg_translate(__('Kein XML Root Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
			if (!wpsg_isSizedString($profil['xml_producttag'])) throw new \Exception(wpsg_translate(__('Kein XML Kunden Tagname für Profil "#1#" gesetzt!', 'wpsg'), $profil['name']));
			
			$doc = new DOMDocument('1.0');
            $doc->formatOutput = true;
            
            $root = $doc->createElement($this->clearXML($profil['xml_roottag']));					
            $root = $doc->appendChild($root);
			
			foreach ($arData as $oCustomer)
            {
                
                $tag_product = $doc->createElement($this->clearXML($profil['xml_customertag']));
                $tag_product = $root->appendChild($tag_product);
			
				foreach ($profil['fields'] as $f)
                {
                    
                    $value = $this->getValue($f, $profil['field_delimiter'], false, false, 1, false, false, $oCustomer->id);
                                                    
					if (wpsg_isSizedInt($f['xml_att']))
					{
						
						$att = $doc->createAttribute($this->clearXML($f['name']));
						$att->value = htmlspecialchars($value);
						
						$tag_product->appendChild($att);

						
					}
					else
					{
						
						$tag = $doc->createElement($this->clearXML($f['name']));
						$tag = $tag_product->appendChild($tag);
						
						$tag_value = $doc->createTextNode(htmlspecialchars($value));
						$tag->appendChild($tag_value);
						
					}
                                         
                }
				
			}
			
			$tmpfname = tempnam($this->getTmpFilePath(), "wpsg");
            file_put_contents($tmpfname, $doc->saveXML());
            
            // In ISO wandeln
            if ($profil['file_encoding'] == self::ENCODING_ISO88591)
            {
                
                file_put_contents($tmpfname, utf8_decode(file_get_contents($tmpfname)));
                                        
            }
            
            return $tmpfname;
			
		} // private function handleExportCustomerXML($profil_id, $arOrderFilter = array())
						
		/**
		 * Bereinigt Daten an XML für Tagname / Attributname
		 */
		private function clearXML($value)
		{
			
			$value = preg_replace('/\040+/', '_', $value);
			$value = preg_replace('/\"|\<|\>|\'/', '', $value);
			
			return htmlspecialchars($value);
			
		}
		
		private function getFieldGroup($key)
		{
			
			if (!wpsg_isSizedArray($this->fields)) $this->loadFields();
			
			foreach ($this->fields as $group_key => $group)
			{
				
				foreach ($group['fields'] as $field_key => $field_label)
				{
					
					if ($field_key == $key) return $group_key;
					
				}
				
			}
			
			return false;
			
		}
		
		public function renderProfil($profil_id)
		{
			
			$this->loadFields();
												
			$this->shop->view['profil'] = $this->loadProfil($profil_id);
									
			$this->shop->view['cdate_years'] = $this->db->fetchAssocField("SELECT DISTINCT DATE_FORMAT(`cdate`, '%Y') FROM `".WPSG_TBL_ORDER."` ORDER BY `cdate` ASC ");
			if ($this->shop->hasMod('wpsg_mod_rechnungen')) $this->shop->view['invoicedate_years'] = $this->db->fetchAssocField("SELECT DISTINCT DATE_FORMAT(`datum`, '%Y') FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `storno` = '0000-00-00' AND `gnr` = '' ORDER BY `datum` ASC ");
			
			$this->shop->view['arExportFormats'] = $this->arExportFormats;
			$this->shop->view['arFileEncoding'] = $this->arFileEncoding;				
			$this->shop->view['arFieldFormats'] = $this->arFieldFormats;
			$this->shop->view['arCronTypes'] = $this->arCronTypes;
			$this->shop->view['fields'] = $this->fields;
			$this->shop->view['strFields'] = $this->renderFields($this->shop->view['profil']);
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/profil.phtml', false);
			
		} // public function renderProfil($profil_id)

		/**
		 * Zeichner die Felder des Profils
		 * @param $profil_data
		 */
		function renderFields($profil_data)
		{
		
            if (!wpsg_isSizedArray($this->fields)) $this->loadFields();
            
			$this->shop->view['arFieldFormats'] = $this->arFieldFormats;
			$this->shop->view['fields'] = $this->fields;
			$this->shop->view['profil'] = $profil_data;
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_export/fields.phtml', false);
			
		} // function renderFields($profil_data)
		
		/**
		 * Gibt die Daten eines Profils zurück
		 * @return Array Array mit den Profildaten
		 * @param int $profil_id Die ID des zu ladenden Profils
		 */
		public function loadProfil($profil_id)
		{
			
			$arReturn = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_EXPORTPROFILE."` WHERE `id` = '".wpsg_q($profil_id)."' ");
			
			$arReturn['orderfilter'] = @unserialize($arReturn['orderfilter']);			
			if (!is_array($arReturn['orderfilter'])) $arReturn['orderfilter'] = array();
						
			$arReturn['fields'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_EXPORTPROFILE_FIELDS."` WHERE `profil_id` = '".wpsg_q($profil_id)."' ORDER BY `pos` ASC, `id` ASC ", "id");
			
			if ($profil_id != wpsg_getInt($arReturn['id'])) throw new \Exception(__('Profil konnte nicht geladen werden.', 'wpsg'));
			
			return $arReturn;
			
		} // public function loadProfil($profil_id)
		
		private function loadFields()
		{
			
			$this->fields = array(	
				5 => array(
					'name' => __('Allgemein', 'wpsg'),
					'fields' => array(
						'allgemein_empty' => __('Leer', 'wpsg'),
						//'allgemein_statisch' => __('Statisch', 'wpsg'),
						'allgemein_currency' => __('Währung', 'wpsg')
					)
				),
				10 => array(
					'name' => __('Bestellung', 'wpsg'),
					'fields' => array(
						'order_id' => __('BestellID', 'wpsg'),
						'order_cdate' => __('Erstellungsdatum', 'wpsg'),
						'order_onr' => __('Bestellnummer', 'wpsg'),
						'order_bemerkung' => __('Bemerkung Kunde', 'wpsg'),
						'order_price' => __('Bestellsumme', 'wpsg'),
						'order_price_netto' => __('Bestellsumme (Netto)', 'wpsg'),
						'order_shipping' => __('Versandkosten', 'wpsg'),
						'order_payment' => __('Kosten Bezahlmethode', 'wpsg'),
						'order_useragent' => __('UserAgent', 'wpsg'),
						'order_ip' => __('IP Adresse', 'wpsg'),						
						
						'order_invoice_title' => __('Rechnungsadresse Anrede', 'wpsg'),
						'order_invoice_firma' => __('Rechnungsadresse Firma', 'wpsg'),
						'order_invoice_vorname' => __('Rechnunsadresse Vorname', 'wpsg'),
						'order_invoice_name' => __('Rechnungsadresse Name', 'wpsg'),						
						'order_invoice_strasse' => __('Rechnungsadresse Straße (mit Hausnummer)', 'wpsg'),
						'order_invoice_strasse_strasse' => __('Rechnungsadresse Straße (ohne Hausnummer)', 'wpsg'),
						'order_invoice_strasse_nr' => __('Rechnungsadresse Hausnummer', 'wpsg'),
						'order_invoice_plz' => __('Rechnungsadresse PLZ', 'wpsg'),
						'order_invoice_ort' => __('Rechnungsadresse Ort', 'wpsg'),
						'order_invoice_land' => __('Rechnungsadresse Land (Name)', 'wpsg'),
						'order_invoice_land_krzl' => __('Rechnungsadresse Land (Kürzel)', 'wpsg'),
						'order_invoice_tel' => __('Rechnungsadresse Telefon', 'wpsg'),
						'order_invoice_fax' => __('Rechnungsadresse Fax', 'wpsg'),
									
						'order_title' => __('Lieferadresse Anrede', 'wpsg'),
						'order_firma' => __('Lieferadresse Firma', 'wpsg'),
						'order_vorname' => __('Lieferadresse Vorname', 'wpsg'),
						'order_name' => __('Lieferadresse Name', 'wpsg'),						
						'order_strasse' => __('Lieferadresse Straße (mit Hausnummer)', 'wpsg'),
                        'order_strasse_strasse' => __('Lieferadresse Straße (ohne Hausnummer)', 'wpsg'),
						'order_strasse_nr' => __('Lieferadresse Hausnummer', 'wpsg'),
						'order_plz' => __('Lieferadresse PLZ', 'wpsg'),
						'order_ort' => __('Lieferadresse Ort', 'wpsg'),
						'order_land' => __('Lieferadresse Land (Name)', 'wpsg'),
						'order_land_krzl' => __('Lieferadresse Land (Kürzel)', 'wpsg'),
						
						'order_payment_method' => __('Name der Zahlart', 'wpsg'),
						'order_shipping_method' => __('Name der Versandart', 'wpsg'),
						'order_count' => __('Anzahl Produkte', 'wpsg'),
						'order_bname' => __('Name der Bank (Bankeinzug)', 'wpsg'),
						'order_bblz' => __('BLZ der Bank (Bankeinzug)', 'wpsg'),
						'order_bic' => __('BIC der Bank (Bankeinzug)', 'wpsg'),
						'order_binhaber' => __('Kontoinhaber (Bankeinzug)', 'wpsg'),
						'order_bnr' => __('Kontonummer (Bankeinzug)', 'wpsg'),
						'order_iban' => __('IBAN Nummer (Bankeinzug)', 'wpsg'),						
						'order_status' => __('Status der Bestellung', 'wpsg'),
						'order_menge' => __('Anzahl an Artikeln', 'wpsg'),	
						'order_weight' => __('Gesamtgewicht', 'wpsg')
					)
				),
				20 => array(
					'name' => __('Produkt', 'wpsg'),
					'fields' => array(
						'produkt_id' => __('ProduktID', 'wpsg'),
						'produkt_index' => __('Position', 'wpsg'),
						'produkt_name' => __('Produktname', 'wpsg'),
						'product_url' => __('Produkt-URL', 'wpsg'),
						'product_picture_url' => __('Produktbild-URL', 'wpsg'),
						//'product_shippingprice' => __('Versandkosten', 'wpsg'),		
						'produkt_typ' => __('Produkttyp', 'wpsg'),
						'produkt_preis' => __('Produktpreis', 'wpsg'),
						'produkt_preis_brutto' => __('Produktpreis (Brutto)', 'wpsg'),
						'produkt_preis_netto' => __('Produktpreis (Netto)', 'wpsg'),
						'product_preis_order' => __('Produktpreis aus Bestellung', 'wpsg'),
						'product_preis_order_netto' => __('Produktpreis aus Bestellung (Netto)', 'wpsg'),
						'produkt_mwst' => __('Mehrwertsteuer (Schlüssel)', 'wpsg'),
						'produkt_mwst_satz' => __('Mehrwertsteuer (Satz z.B. 19%)', 'wpsg'),
						'produkt_mwst_value' => __('Mehrwertsteuer (Wert in €)', 'wpsg'),
						'produkt_order_mwst_value' => __('Mehrwertsteuer (Wert in € aus Bestellung)', 'wpsg'),
						'produkt_feinheit' => __('Füllmengeneinheit', 'wpsg'),
						'produkt_fmenge' => __('Füllmenge', 'wpsg'),
						'produkt_beschreibung' => __('Beschreibung', 'wpsg'),
						'produkt_weight' => __('Gewicht', 'wpsg'),		 
						'produkt_anr' => __('Artikelnummer', 'wpsg'),
						'produkt_menge' => __('Anzahl', 'wpsg'),
						'produkt_ean' => __('EAN', 'wpsg'),
						'produkt_gtin' => __('GTIN', 'wpsg')
					)
				),
				30 => array(
					'name' => __('Kunden', 'wpsg'),
					'fields' => array(
						'kunde_id' => __('KundenID', 'wpsg'),
						'kunde_nr' => __('Kundennummer', 'wpsg'),
						'kunde_title' => __('Anrede', 'wpsg'),
						'kunde_firma' => __('Firma', 'wpsg'),
						'kunde_vorname' => __('Vorname', 'wpsg'),
						'kunde_name' => __('Name', 'wpsg'),
						'kunde_strasse' => __('Straße (mit Hausnummer)', 'wpsg'),
                        'kunde_strasse_strasse' => __('Straße (ohne Hausnummer)', 'wpsg'),
						'kunde_strasse_nr' => __('Hausnummer', 'wpsg'),
						'kunde_ort' => __('Ort', 'wpsg'),
						'kunde_plz' => __('PLZ', 'wpsg'),
						'kunde_land' => __('Land (Name)', 'wpsg'),
						'kunde_land_krzl' => __('Land (Kürzel)', 'wpsg'),
						'kunde_tel' => __('Telefon', 'wpsg'),
						'kunde_fax' => __('Faxnummer', 'wpsg'),
						'kunde_email' => __('E-Mail', 'wpsg'),
						'kunde_ustid' => __('Umsatzsteuernummer', 'wpsg'),
						'kunde_geb' => __('Geburtsdatum', 'wpsg')					
					)
				),	
			);
			
			if ($this->shop->hasMod('wpsg_mod_stock')) {

                $this->fields[20]['fields']['produkt_stock'] = __('Lagerbestand', 'wpsg');
			    $this->fields[20]['fields']['product_stock_state'] = __('Lagerstatus', 'wpsg');
			    
            }
			
			// AboProdukte
			if ($this->shop->hasMod('wpsg_mod_abo'))
			{
				
				$this->field[12] = array(
					'name' => __('Abo', 'wpsg'),
					'fields' => array(
						'startdate' => __('Abobeginn', 'wpsg'),
						'enddate' => __('Aboende', 'wpsg'), 
						'runtime' => __('Laufzeit', 'wpsg')
					)	
						
				);
				
			}
			
			// Rechnungen
			if ($this->shop->hasMod('wpsg_mod_rechnungen'))
			{
				
				$this->fields[11] = array(
					'name' => __('Rechnung', 'wpsg'),
					'fields' => array(
						'rechnung_nr' => __('Rechnungsnummer', 'wpsg'),
						'gutschrift_nr' => __('Rechnungskorrekturnummer', 'wpsg'),
						'rdatum' => __('Datum der Rechnung/Rechnungskorrektur', 'wpsg')
					) 
				);
				
			}
			
			// Gutscheinmodul
			if ($this->shop->hasMod('wpsg_mod_gutschein'))
			{
				
				$this->fields[10]['fields']['order_gutschein'] = __('Gutscheinnummer', 'wpsg');
				
			}
			
			// Bestellvariablen 
			if ($this->shop->hasMod('wpsg_mod_ordervars'))
			{
				
				$arOV = array();
				$arOrderVars = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERVARS."` WHERE `deleted` != '1' ORDER BY `pos` ASC, `id` ASC ");
				
				foreach ($arOrderVars as $ov)
				{
					
					$arOV['ov_'.$ov['id']] = $ov['name'];	
					
				}
				
				if (is_array($arOV) && sizeof($arOV) > 0)
				{
					
					$arOV = array(
						'name' => __('Bestellvariablen', 'wpsg'),
						'fields' => $arOV
					);
					
					$this->fields[12] = $arOV;
					
				}
				
			}			
			
			/* Produktvariablen */ 
			if ($this->shop->hasMod('wpsg_mod_productvars'))
			{
							
				$arPV = array();
				$arProduktVars = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARS."` ORDER BY `name` ");
				
				foreach ($arProduktVars as $pv)
				{
					
					$arPV['pv_'.$pv['id']] = $pv['name'];
					
				}
				
				if (is_array($arPV) && sizeof($arPV) > 0)
				{
					
					$arPV = array(
						'name' => __('Produktvariablen', 'wpsg'),
						'fields' => $arPV
					);
					
					$this->fields[21] = $arPV;
					
				}
				
			} 
			
			/* Produktattribute */
			if ($this->shop->hasMod('wpsg_mod_produktattribute'))
			{
				
				$arPA = array();
				$arProduktAttribute = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_AT."` ORDER BY `name` ");
				
				foreach ($arProduktAttribute as $pa)
				{
					
					$arPA['pa_'.$pa['id']] = $pa['name'];
					
				}
				
				if (is_array($arPA) && sizeof($arPA) > 0)
				{
					
					$arPA = array(
						'name' => __('Produktattribute', 'wpsg'),
						'fields' => $arPA
					);
					
					$this->fields[22] = $arPA;
					
				}
				
			}
								
			/* Kundenvariablen */
			$kv = $this->shop->loadPflichtFeldDaten();
			if (is_array($kv) && sizeof($kv) > 0 && is_array($kv['custom']) && sizeof($kv['custom']) > 0)
			{
				
				$arKV = array(); 
				foreach ($kv['custom'] as $kv_key => $kv)
				{
					
					$arKV['kv_'.$kv_key] = $kv['name'];
					
				}
				
				if (is_array($arKV) && sizeof($arKV) > 0)
				{
					
					$arKV = array(
						'name' => __('Kundenvariablen', 'wpsg'),
						'fields' => $arKV
					);
					
					$this->fields[31] = $arKV;
					
				}
				
			}
			
			$this->shop->callMods('wpsg_mod_export_loadFields', array(&$this->fields));
			
			ksort($this->fields);
			
		}
		
		public function getValue($f, $profil_separator, $o_id, $p_id = false, $product_index = 1, $productkey = false, $order_product_id = false, $customer_id = false)
		{
			
			$field_value = $f['value_key'];
			
			$cache_key = $o_id.'_'.$p_id.'_'.$product_index.'_'.$productkey.'_'.$order_product_id.'_'.$customer_id;
			
			// Hier werden die Ergebnisse gespeichert
			if (array_key_exists($cache_key, $this->arCache))
			{
				
				$order = $this->arCache[$cache_key]['order'];
				$produkt = $this->arCache[$cache_key]['produkt'];
				$kunde = $this->arCache[$cache_key]['kunde'];
								
			}
			else
			{
			
				$order = $this->db->fetchRow("
					SELECT 
						O.*
					FROM
						`".WPSG_TBL_ORDER."` AS O
					WHERE
						O.`id` = '".wpsg_q($o_id)."'
				");
				
				// Anzahl an tatsächlich bestellten Artikeln
				$order['menge'] = $this->db->fetchOne("SELECT SUM(OP.`menge`) FROM `".WPSG_TBL_ORDERPRODUCT."` AS OP WHERE OP.`o_id` = '".wpsg_q($order['id'])."'");
				
                $order['oOrder'] = wpsg_order::getInstance($order['id']);
                
				// Anzahl an Produkten in der Bestellung (Mengenunabhängig)
				$order['count'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($order['id'])."'");
				
				$order['payment_method'] = $order['oOrder']->getPaymentLabel();
				$order['shipping_method'] = $order['oOrder']->getShippingLabel();
				
				$order['custom_data'] = unserialize($order['custom_data']);
				
				if ($this->shop->hasMod('wpsg_mod_rechnungen'))
				{
					
					$last_invoice = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `o_id` = '".wpsg_q($order['id'])."' AND `rnr` != '' ORDER BY `datum` DESC LIMIT 1 ");
					$last_storno = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `o_id` = '".wpsg_q($order['id'])."' AND `gnr` != '' ORDER BY `datum` DESC LIMIT 1 ");
					$last = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `o_id` = '".wpsg_q($order['id'])."' ORDER BY `datum` DESC LIMIT 1 ");
					
					$order['gnr'] = '--';
					$order['rnr'] = '--';
					$order['rdatum'] = '--'; 
					
					if (wpsg_isSizedInt($last_invoice['id']))
					{
						
						$order['rnr'] = $last_invoice['rnr'];
						$order['rdatum'] = $last_invoice['datum'];
						
					}
					
					if (wpsg_isSizedInt($last_storno['id']) && $last['id'] === $last_storno['id'])
					{
						
						$order['gnr'] = $last_storno['gnr']; 
						$order['rdatum'] = $last_storno['datum'];
						
					}
										
				}
				
				/* Bestellvariablen */
				if ($this->shop->hasMod('wpsg_mod_ordervars'))
				{
					
					$order['bvars'] = @unserialize($order['bvars']);
					
				}
				
				/* Produktvariablen */
				if ($this->shop->hasMod('wpsg_mod_productvars'))
				{
					
					$order['pvars'] = @unserialize($order['pvars']);
					
				}
				
				if ($customer_id !== false) $order['k_id'] = $customer_id;
								
				$kunde = $this->db->fetchRow("
					SELECT
						K.*							
					FROM
						`".WPSG_TBL_KU."` AS K
					WHERE
						K.`id` = '".wpsg_q($order['k_id'])."'
				");
				
				/* Kundenvariablen */
				$kunde['kv'] = @unserialize($kunde['custom']);
								
				if ($p_id == false)
				{
					
					$arProdukte = $this->db->fetchAssoc("
						SELECT
							P.*,
							OP.`mod_vp_varkey`,
							OP.`menge`,
							(OP.`price_brutto` - OP.`price_netto`) AS mwst_value,
							OP.`price` AS price 
						FROM
							`".WPSG_TBL_ORDERPRODUCT."` AS OP
								LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = OP.`p_id`)
						WHERE
							OP.`o_id` = '".wpsg_q($o_id)."'
					");
					
					$produkt = array();
					
					foreach ($arProdukte as $p)
					{
					
						foreach ($p as $col_name => $col_value)
						{

							$produkt[$col_name][] = $col_value;
							
						}
						
					}

					foreach ($produkt as $col_name => $values)
					{
						
						if ($profil_separator == ',') $produkt[$col_name] = implode(";", $values);
						else $produkt[$col_name] = implode(",", $values);
						
					}
					
				}
				else
				{
					
					if (wpsg_isSizedInt($order_product_id))
					{
						 
						$produkt = $this->db->fetchRow("
							SELECT
								P.*,
								OP.`mod_vp_varkey`,
								OP.`menge`,
								(OP.`price_brutto` - OP.`price_netto`) AS mwst_value,
								OP.`price` AS price
							FROM
								`".WPSG_TBL_ORDERPRODUCT."` AS OP
									LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = OP.`p_id`)
							WHERE
								OP.`id` = '".wpsg_q($order_product_id)."'
						");
					 
					}
					else
					{
						
						$produkt = $this->db->fetchRow("
							SELECT
								P.*,							
								OP.`mod_vp_varkey`,
								OP.`menge`,
								(OP.`price_brutto` - OP.`price_netto`) AS mwst_value,
								OP.`price` AS price
							FROM
								`".WPSG_TBL_PRODUCTS."` AS P
									LEFT JOIN `".WPSG_TBL_ORDERPRODUCT."` AS OP ON (P.`id` = OP.`p_id` AND OP.`o_id` = '".wpsg_q($order['id'])."')
							WHERE
								P.`id` = '".wpsg_q($p_id)."'
						");
						
					}
					
					/* Produktattribute */
					if ($this->shop->hasMod('wpsg_mod_produktattribute'))
					{
						
						$produkt['mod_attribute'] = $this->db->fetchAssocField("
							SELECT
								`a_id`, `value`
							FROM
								`".WPSG_TBL_PRODUCTS_AT."` AS PA
							WHERE
								PA.`p_id` = '".wpsg_q($produkt['id'])."'								
						", "a_id", "value");
						
					}
					
				}

				$this->arCache[$cache_key] = array(
					"order" => $order,
					"produkt" => $produkt,
					"kunde" => $kunde
				);
				
			}
			
			if (wpsg_isSizedInt($order['land']))
			{
				
				$oFrontendCountry = wpsg_country::getInstance($order['land']);
								
			}
			else
			{
				
				$oFrontendCountry = $this->shop->getDefaultCountry();
				
			}
			
			// Objekte laden 
			 
			/** @var wpsg_order $oOrder */
			$oOrder = wpsg_order::getInstance($order['id']);
			
			if ($customer_id !== false) $oCustomer = wpsg_customer::getInstance($customer_id);
			else $oCustomer = $oOrder->getCustomer();
 
			if ($p_id !== false) {
			    			    
			    $oProduct = wpsg_product::getInstance($p_id);
			                                    
            }
                        			
			switch ($field_value)
			{
				
				case 'allgemein_empty': $return = ''; break;
				case 'allgemein_statisch': $return = $f['static']; break;
				case 'allgemein_currency': $return = get_option('wpshopgermany_currency'); break; 
								
				case 'order_id': $return = $order['id']; break; 
				case 'order_cdate': 
					
					if (strtotime($order['cdate']) !== false)
					{
						$return = date('d.m.Y H:i:s', strtotime($order['cdate']));
					}
					else
					{
						$return = '--';
					}
					
					break;
				case 'order_onr': $return = $order['onr']; break;
				case 'order_bemerkung': $return = preg_replace('/\r\n|\r|\n/', ' ', $order['comment']); break;
				case 'order_price':
					
					$return = $order['price_gesamt_brutto'];
					 
					break;
					
				case 'order_price_netto':
					
					$return = $order['price_gesamt_netto'];						
					
					break;
					
				case 'order_shipping': $return = number_format($order['price_shipping'], 2, ',', '.'); break;
				case 'order_payment': $return = number_format($order['price_payment'], 2, ',', '.'); break;	
				case 'order_useragent':	$return = $order['useragent']; break;
				case 'order_ip': $return = $order['ip']; break;
				
				case 'order_invoice_title': $return = $oOrder->getInvoiceTitle(); break;
				case 'order_invoice_firma': $return = $oOrder->getInvoiceCompany(); break;
				case 'order_invoice_vorname': $return = $oOrder->getInvoiceFirstName(); break;
				case 'order_invoice_name': $return = $oOrder->getInvoiceName(); break;
				case 'order_invoice_strasse': $return = $oOrder->getInvoiceStreet(); break;
				case 'order_invoice_strasse_strasse': $return = $oOrder->getInvoiceStreetClear(); break;
				case 'order_invoice_strasse_nr': $return = $oOrder->getInvoiceStreetNr(); break;
				case 'order_invoice_plz': $return = $oOrder->getInvoiceZip(); break; 
				case 'order_invoice_ort': $return = $oOrder->getInvoiceCity(); break;
				case 'order_invoice_land': $return = $oOrder->getInvoiceCountryName(); break;
				case 'order_invoice_land_krzl': $return = $oOrder->getInvoiceCountryKuerzel(); break;
				case 'order_invoice_tel': $return = $oOrder->getInvoicePhone(); break;
				case 'order_invoice_fax': $return = $oOrder->getInvoiceFax(); break;
				
                case 'order_title': $return = $oOrder->getShippingTitle(); break;
                case 'order_firma': $return = $oOrder->getShippingCompany(); break;                    
				case 'order_vorname': $return = $oOrder->getShippingFirstName(); break;
				case 'order_name': $return = $oOrder->getShippingName(); break;
				case 'order_strasse': $return = $oOrder->getShippingStreet(); break;
                case 'order_strasse_strasse': $return = $oOrder->getShippingStreetClear(); break;
                case 'order_strasse_nr': $return = $oOrder->getShippingStreetNr(); break;
                case 'order_plz': $return = $oOrder->getShippingZip(); break;
				case 'order_ort': $return = $oOrder->getShippingCity(); break;
				case 'order_land': $return = $oOrder->getShippingCountryName(); break;
				case 'order_land_krzl': $return = $oOrder->getShippingCountryKuerzel(); break;
								
                case 'order_payment_method': $return = $order['payment_method']; break;
				case 'order_shipping_method': $return = $order['shipping_method']; break;				
				case 'order_count': $return = $order['count']; break;
				case 'order_firma': $return = $order['shipping_firma']; break;
				case 'order_status': $return = $this->shop->arStatus[$order['status']]; break;
				case 'order_bname': $return = $order['mod_autodebit_name']; break;
				case 'order_bblz': $return = $order['mod_autodebit_blz']; break;
				case 'order_binhaber': $return = $order['mod_autodebit_inhaber']; break;
				case 'order_menge': $return = $order['menge']; break;
				case 'order_bnr': $return = $order['mod_autodebit_knr']; break;
				case 'order_iban': $return = $order['mod_autodebit_iban']; break;
				case 'order_bic': $return = $order['mod_autodebit_bic']; break;
				case 'order_weight': $return = $order['weight']; break;
				case 'order_gutschein': 
										
					if (wpsg_isSizedInt($order['gs_id'])) {
					
						// Abwärtskompatibilität für alte Bestellungen						
						$return = $this->db->fetchOne("SELECT `code` FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` = '".wpsg_q($order['gs_id'])."'");
						
					} else {
						
						$return = $this->db->fetchOne("
							SELECT
								GROUP_CONCAT(`code`)
							FROM
								`".WPSG_TBL_ORDER_VOUCHER."`
							WHERE
								`order_id` = '".wpsg_q($order['id'])."'
							GROUP BY
								`order_id`
						");
							
					}
						
					break;
				
				case 'rechnung_nr': $return = $order['rnr']; break;
				case 'rechnungskorrektur_nr':
				case 'gutschrift_nr': 
					$return = $order['gnr']; break;
				case 'rdatum': 
					
					if (strtotime($order['rdatum']) !== false)
					{
						$return = date('d.m.Y H:i:s', strtotime($order['rdatum']));
					}
					else
					{
						$return = '--';
					} 
					
					break;				
								
				case 'produkt_id': $return = $produkt['id']; break;
				case 'produkt_index': $return = $product_index; break; 
				case 'produkt_name': $return = $produkt['name']; break;
                case 'product_url': $return = $this->shop->getProduktLink($produkt); break;
                case 'product_picture_url':

                    $nAttachmentID = $this->shop->imagehandler->getAttachmentID($produkt['id']);
                    $return = '';
                    
                    if (wpsg_isSizedInt($nAttachmentID)) $return = \wp_get_attachment_url($nAttachmentID);
                    
                    break;
                /*case 'product_shippingprice':
                     
                    $arCountry = wpsg_country::find([]);
                    
                    foreach ($arCountry as $oCountry) {
                        
                        $cost = 0;
 
                        $this->shop->basket = new wpsg_basket();
                        $this->shop->basket->arProdukte = [
                            [
                                'id' => $oProduct->id,
                                'menge' => 1
                            ]
                        ];
                        $this->shop->baskert->arCheckout['land'] = $oCountry->id;

                        $arShipping = [];
                        
                        $this->shop->callMods('addShipping', [&$arShipping, true]);
                        
                        foreach ($arShipping as $shipping_key => $shipping) {
                            
                            $arBasket = $this->shop->basket->toArray();
                            
                            $this->shop->callMods('calcShipping', [&$arBasket, $shipping_key]);
 
                            wpsg_debug($oCountry->getShorttext()." : ".$shipping_key.":".$arBasket['sum']['preis_shipping_brutto']);

                        }
                        
                    }
                    
                    
                    die("K");
                    
                    break;*/
				case 'produkt_typ': $return = $produkt['typ']; break;
				case 'produkt_preis': // Brutto Preis im Produkt
					
					if ($this->shop->getBackendTaxview() == WPSG_BRUTTO)
						$return = number_format($produkt['preis'], 2, ',', '.');
					else 
						$return = number_format($produkt['preis'] + $produkt['mwst_value'], 2, ',', '.');
										
					break;
					
				case 'produkt_preis_brutto':
					
					$return = $oProduct->getPrice(false, WPSG_BRUTTO);
					
					break;
                					
				case 'produkt_preis_netto': // Netto Preis im Produkt
					
					$return = $oProduct->getPrice(false, WPSG_NETTO); 
					
					break;
				
				case 'product_preis_order': // Brutto Preis aus bestelltem Produkt
					
					$return = number_format($produkt['price'], 2, ',', '.');
					break;
					
				case 'product_preis_order_netto':
					
					$return = number_format($produkt['price'] - $produkt['mwst_value'], 2, ',', '.');
					break;
				
				case 'produkt_mwst': $return = $produkt['mwst_key']; break;
				case 'produkt_mwst_satz': $return = wpsg_ff($oFrontendCountry->getTax($produkt['mwst_key'])); break;
				case 'produkt_mwst_value': $return = wpsg_ff($oProduct->getPrice(false, WPSG_BRUTTO) - $oProduct->getPrice(false, WPSG_NETTO)); break;
				 				
				// case 'produkt_picture_link': $return = $produktbild['url']; break; 
							
				// case 'produkt_shippingprice': $return = $produkt['shippingprice']; break;
				
				case 'produkt_order_mwst_value': $return = wpsg_ff($produkt['mwst_value'] * $produkt['menge']); break;
				
				case 'produkt_feinheit': $return = $produkt['feinheit']; break;
				case 'produkt_fmenge': $return = $produkt['fmenge']; break;
				case 'produkt_beschreibung': $return = $produkt['beschreibung']; break;
				case 'produkt_weight': $return = $produkt['weight']; break;
				case 'produkt_stock': $return = $produkt['stock']; break;
                case 'product_stock_state': 
                    
                    $return = ((wpsg_isSizedInt($produkt['stock']))?__('auf Lager', 'wpsg'):__('nicht auf Lager', 'wpsg'));

                    break;
				case 'produkt_anr': $return = $produkt['anr']; break;
				case 'produkt_menge': $return = $produkt['menge']; break;
				case 'produkt_ean': $return = $produkt['ean']; break;
				case 'produkt_gtin': $return = $produkt['gtin']; break;
				
				case 'kunde_id': $return = @$kunde['id']; break;
				case 'kunde_nr': $return = $kunde['knr']; break;
				
                case 'kunde_title': $return = $oCustomer->getTitle(); break;
                case 'kunde_firma': $return = $oCustomer->getCompany(); break;
				case 'kunde_vorname': $return = $oCustomer->getFirstname(); break;         
                case 'kunde_name': $return = $oCustomer->getName(); break;				
				case 'kunde_strasse': $return = $oCustomer->getStreet(); break;
                case 'kunde_strasse_strasse': $return = $oCustomer->getStreetClear(); break;
                case 'kunde_strasse_nr': $return = $oCustomer->getStreetNr(); break;
				case 'kunde_ort': $return = $oCustomer->getCity(); break;
				case 'kunde_plz': $return = $oCustomer->getZip(); break;
				case 'kunde_land': $return = $oCustomer->getCountryName(); break;
				case 'kunde_land_krzl': $return = $oCustomer->getCountryKuerzel(); break;
				case 'kunde_tel': $return = $oCustomer->getPhone(); break;
				case 'kunde_fax': $return = $oCustomer->getFax(); break;
				
                case 'kunde_email': $return = @$kunde['email']; break;		
				case 'kunde_ustid': $return = @$kunde['ustidnr']; break;
				case 'kunde_geb': 

					if (strtotime($kunde['geb']) !== false)
					{
						$return = date('d.m.Y H:i:s', strtotime($kunde['geb']));
					}
					else
					{
						$return = '--';
					} 
					
					break;			
				
				default:

					if (preg_match('/ov_\d+/', $field_value))
					{

						// Bestellvariable
						$ordervarid = substr($field_value, 3);
						$return = $order['bvars'][$ordervarid];
						
					}
					else if (preg_match('/pv_\d+/', $field_value))
					{
						 
						$produktvarid = substr($field_value, 3);
						 
						// Produktvariable (index kann auch 0 sein)
						if (isset($order['pvars'][$product_index]))
						{
							
							$return = $order['pvars'][$product_index][$produktvarid];
							
						}
						else
						{
							
							// Abwärtskompatibilität < 3.5.1 
							if ($produkt['mod_vp_varkey'] != '')
							{
								$produkt_key = $produkt['mod_vp_varkey'];
							}
							else
							{
								$produkt_key = $produkt['id'];
							}

							$return = $order['pvars'][$produkt_key][$produktvarid];
							
						}
					 
					}
					else if (preg_match('/pa_\d+/', $field_value))
					{
						
						// Produktattribute
						$paid = substr($field_value, 3);
						$return = $produkt['mod_attribute'][$paid];
						
					}
					else if (preg_match('/kv_\d+/', $field_value))
					{
						
						// Kundenvariable
						$kvid = substr($field_value, 3);
						$return = $kunde['kv'][$kvid];
						
					}
					else
					{
						
						$return = false;						
						$this->shop->callMods('wpsg_mod_export_getValue', array(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator));
						
						if ($return === false)
						{
						
							$return = __('Nicht definiert ('.$field_value.') !', 'wpsg');
							
						}
						
					}
				
			}  
			
			$f['size'] = 111255;
			
			if (wpsg_isSizedInt($f['trim'])) $return = preg_replace('/\040/', '', $return);
			
			if (isset($f['format']) && $f['format'] > 0 && $return != '--')
			{
				
				switch ($f['format'])
				{
					
					case 100: $return = number_format(floatval(wpsg_tf($return)), 2, '.', ''); break;
					case 200: $return = number_format(floatval(wpsg_tf($return)), 2, ',', ''); break;
					case 300: $reutrn = wpsg_ff(wpsg_tf($return), $this->shop->get_option('wpsg_currency')); break;
					case 400: $return = date('d.m.Y', strtotime($return)); break;
					case 500: $return = date('H:i:s', strtotime($return)); break;
					case 600: $return = date('d.m.Y H:i:s', strtotime($return)); break;					
					case 700: $return = strftime($this->shop->replaceUniversalPlatzhalter($f['userformat'], $order['id'], $kunde['id'], false, $p_id), strtotime($return)); break;
					case 800: $return = '"'.$return.'"'; break;
					case 900: $return = '"=""'.$return.'"""'; break;
					
				}
				
			}
						
			$return = substr($return, 0, intval($f['size'])); 
			
			return $return;
			
		} // public function getValue($field_value, $o_id, $p_id = false)
		
	} // wpsg_mod_export extends wpsg_mod_basic

?>