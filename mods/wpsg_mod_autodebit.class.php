<?php

	/**
	 * Dieses Modul erlaubt die Zahlungsart "Bankeinzug"
	 * @author daniel 
	 */
	class wpsg_mod_autodebit extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 20;
		var $hilfeURL = 'http://wpshopgermany.de/?p=3879';
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			global $wpdb;
			parent::__construct();
			
			$this->name = __('Bankeinzug', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlungsart Bankeinzug.', 'wpsg');
			
			if (defined('MULTISITE') && MULTISITE === true && get_site_option('wpsg_multiblog_standalone', true) != '1')
			{
			
				$prefix = $wpdb->base_prefix;
			
			}
			else
			{
			
				$prefix = $wpdb->prefix;
			
			}
			if (!defined('WPSG_TBL_BANKDATA')) {
				define('WPSG_TBL_BANKDATA', $prefix.'wpsg_bankdata');
			}
						
		} // public function __construct()
		
		public function install()
		{
			 
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/**
			 * Bestelltabelle erweitern
			 */ 
			$sql = "CREATE TABLE ".WPSG_TBL_ORDER." (
		   		mod_autodebit_name VARCHAR(255) NOT NULL,
		   		mod_autodebit_blz VARCHAR(255) NOT NULL,
				mod_autodebit_bic VARCHAR(11) NOT NULL,
		   		mod_autodebit_inhaber VARCHAR(255) NOT NULL,
		   		mod_autodebit_knr VARCHAR(255) NOT NULL,
				mod_autodebit_iban VARCHAR(255) NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
				
			$sql = "CREATE TABLE ".WPSG_TBL_BANKDATA." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				mod_autodebit_blz VARCHAR(255) NOT NULL,
		   		mod_autodebit_merkmal VARCHAR(1) NOT NULL,
		   		mod_autodebit_name VARCHAR(255) NOT NULL,
		   		mod_autodebit_plz VARCHAR(255) NOT NULL,
		   		mod_autodebit_ort VARCHAR(255) NOT NULL,
		   		mod_autodebit_kurz VARCHAR(255) NOT NULL,
		   		mod_autodebit_institut VARCHAR(255) NOT NULL,
				mod_autodebit_bic VARCHAR(11) NOT NULL,
				mod_autodebit_kenn VARCHAR(2) NOT NULL,
				mod_autodebit_dsnummer VARCHAR(255) NOT NULL,
				mod_autodebit_aekenn VARCHAR(1) NOT NULL,
				PRIMARY KEY  (id),
				KEY mod_autodebit_bic (mod_autodebit_bic),
				mod_autodebit_loesch VARCHAR(1) NOT NULL,
				mod_autodebit_nachfolge VARCHAR(255) NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);

			$this->db->Query("TRUNCATE TABLE ".WPSG_TBL_BANKDATA);
			
			$filename = WPSG_PATH_MOD.'mod_autodebit/blz.txt';
			$this->readBLZBICFile($filename, false, false, true);
			
   			
   			$this->shop->checkDefault('wpsg_mod_autodebit_bezeichnung', $this->name, false, true);
   			$this->shop->checkDefault('wpsg_mod_autodebit_aktiv', '1');
   			$this->shop->checkDefault('wpsg_mod_autodebit_hint', __('Wählen Sie diese Zahlungsart wenn sie uns eine Einzugsermächtigung für ihr Konto erteilen möchten. Wir benötigen dazu folgende Angaben:', 'wpsg'), false, true);
   			$this->shop->checkDefault('wpsg_mod_autodebit_gebuehr', '0');
   			$this->shop->checkDefault('wpsg_mod_autodebit_mwst', '0');
   			$this->shop->checkDefault('wpsg_mod_autodebit_mwstland', '0');
   			$this->shop->checkDefault('wpsg_mod_autodebit_iban', '1');
			
		} // public function install()
		
		public function settings_edit()
		{
									
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/settings_edit.phtml');
			
		}
		
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_autodebit_bezeichnung', $_REQUEST['wpsg_mod_autodebit_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_autodebit_aktiv', $_REQUEST['wpsg_mod_autodebit_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_autodebit_hint', $_REQUEST['wpsg_mod_autodebit_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_autodebit_gebuehr', $_REQUEST['wpsg_mod_autodebit_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_autodebit_mwst', $_REQUEST['wpsg_mod_autodebit_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_autodebit_mwstland', $_REQUEST['wpsg_mod_autodebit_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_autodebit_iban', $_REQUEST['wpsg_mod_autodebit_iban'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_autodebit_bic', $_REQUEST['wpsg_mod_autodebit_bic'], false, false, WPSG_SANITIZE_CHECKBOX);
				
		} // public function settings_save()
		
		public function be_ajax()
		{
		
			if ($_REQUEST['wpsg_mod_autodebit_bicibanconverter'] == '1')
			{
		
				$arOrder_bankeinzug = $this->db->fetchAssoc("
					SELECT
						O.`id` AS `order_id`, O.`custom_data`,
						K.`id` AS `customer_id`, CA.*,
						L.`kuerzel` AS `land_kuerzel`						
					FROM
						`".WPSG_TBL_ORDER."` AS O
							LEFT JOIN `".WPSG_TBL_KU."` AS K ON (K.`id` = O.`k_id`)
							LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (O.`adress_id` = CA.`id`)
							LEFT JOIN `".WPSG_TBL_LAND."` AS L ON (CA.`land` = L.`id`) 
					WHERE
						O.`type_payment` = '20'
				");
				
				foreach ($arOrder_bankeinzug as $order)
				{
										
					$custom_data = @unserialize($order['custom_data']);
					
					$knr = $custom_data['checkout']['mod_autodebit_knr'];
					$blz = $custom_data['checkout']['mod_autodebit_blz'];
					
					if (!wpsg_isSizedString($knr) || !wpsg_isSizedString($blz)) continue;
					
					$iban = $this->make_iban($blz, $knr, $order['land_kuerzel']);
					
					if ($this->test_iban($iban))
					{
						
						$custom_data['checkout']['mod_autodebit_iban'] = $iban;
						
					}
					
					$this->db->UpdateQuery(WPSG_TBL_ORDER, array('custom_data' => serialize($custom_data)), " `id` = '".wpsg_q($order['order_id'])."' ");
															
					
				}
				die("1");
		
			}
				
		} // public function be_ajax()
		
		public function addPayment(&$arPayment) { 
			
			//if (!is_admin() && $this->shop->get_option('wpsg_mod_autodebit_aktiv') != '1') return;
			if (is_admin() || $this->shop->get_option('wpsg_mod_autodebit_aktiv') == '1') {
				 
				$arPayment[$this->id] = array(
					'id' => $this->id,
					'name' => __($this->shop->get_option('wpsg_mod_autodebit_bezeichnung'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_autodebit_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_autodebit_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_autodebit_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_autodebit_hint')),
                    'logo' => $this->shop->getRessourceURL('mods/mod_autodebit/gfx/logo_100x25.png')
				);
				 			
			}
			
			// Ging leider nicht anders, da die ID der onepagecheckoutseite noch nicht abgefragt werden kann
			// Sollte denk ich nicht zu problemen führen wenn die Abfrage immer im hint drin ist, er wird ohnehin nur im checkout verwendet
			if (
				isset($_REQUEST['wpsg_checkout2']) ||
				(isset($_REQUEST['wpsg_checkout']) && $this->shop->hasMod('wpsg_mod_onepagecheckout')) ||
				true
			)
			{
				
				$this->shop->view['error'] = wpsg_getArray($_SESSION['wpsg']['errorFields']);
				
				$this->shop->view['wpsg_mod_autodebit']['name'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_name']));
				$this->shop->view['wpsg_mod_autodebit']['blz'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_blz']));
				$this->shop->view['wpsg_mod_autodebit']['bic'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_bic']));
				$this->shop->view['wpsg_mod_autodebit']['inhaber'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_inhaber']));
				$this->shop->view['wpsg_mod_autodebit']['knr'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_knr']));
				$this->shop->view['wpsg_mod_autodebit']['iban'] = wpsg_xss(wpsg_getStr($_SESSION['wpsg']['checkout']['mod_autodebit_iban']));
				
				$arPayment[$this->id]['hint'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/paymenthint.phtml', false);
								
			}
			
		} // public function addPayment(&$arPayment)
		 
		public function order_done(&$order_id, &$done_view)
		{
		
			// Bestellungen mit 0 geben nix aus
			if ($done_view['basket']['sum']['preis_gesamt_brutto'] <= 0) return;

			if ($this->shop->view['basket']['checkout']['payment'] == $this->id)
			{
					 
				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/order_done.phtml', false);
			
			}
			
		} // public function order_done(&$order_id, &$done_view)
		
		public function checkCheckout(&$state, &$error, &$arCheckout) 
		{ 
            
			if (!wpsg_isSizedString($arCheckout['payment'], strval($this->id))) return;
	
			// Werte in die Session und den Checkout schreiben schreiben
			if (
					(isset($_REQUEST['wpsg_checkout']) && wpsg_isSizedArray($_REQUEST['wpsg_mod_autodebit'])) || 
					(isset($_REQUEST['wpsg_checkout2']) && wpsg_isSizedArray($_REQUEST['wpsg_mod_autodebit']))
			)
			{
		 
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['name'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_name'] = $_REQUEST['wpsg_mod_autodebit']['name'];
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['bic'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_bic'] = $_REQUEST['wpsg_mod_autodebit']['bic'];
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['inhaber'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_inhaber'] = $_REQUEST['wpsg_mod_autodebit']['inhaber'];
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['iban'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_iban'] = $_REQUEST['wpsg_mod_autodebit']['iban'];
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['blz'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_blz'] = $_REQUEST['wpsg_mod_autodebit']['blz'];
				if (wpsg_checkInput($_REQUEST['wpsg_mod_autodebit']['knr'], WPSG_SANITIZE_TEXTFIELD)) $_SESSION['wpsg']['checkout']['mod_autodebit_knr'] = $_REQUEST['wpsg_mod_autodebit']['knr'];
				
				$arCheckout['mod_autodebit_name'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_name'];
				$arCheckout['mod_autodebit_blz'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_blz'];
				$arCheckout['mod_autodebit_bic'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_bic'];
				$arCheckout['mod_autodebit_inhaber'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_inhaber'];
				$arCheckout['mod_autodebit_knr'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_knr'];
				$arCheckout['mod_autodebit_iban'] = @$_SESSION['wpsg']['checkout']['mod_autodebit_iban'];

			}
						 
			if ( 
					($state > 1) ||
					($state >= 1 && $this->shop->hasMod('wpsg_mod_onepagecheckout'))
				)
			{
						
				if (trim($arCheckout['mod_autodebit_name']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_name'; $this->shop->addFrontendError(__('Bitte den Namen der Bank kontrollieren (Bankeinzug)', 'wpsg')); $error = true; }
				if (trim($arCheckout['mod_autodebit_inhaber']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_inhaber'; $this->shop->addFrontendError(__('Bitte den Inhaber des Kontos kontrollieren (Bankeinzug)', 'wpsg')); $error = true; }
				
				if ($this->shop->get_option('wpsg_mod_autodebit_iban') == '1')
				{

					if (trim($arCheckout['mod_autodebit_bic']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_bic'; $this->shop->addFrontendError(__('Bitte die BIC der Bank kontrollieren (Bankeinzug)', 'wpsg')); $error = true; }
					if (trim($arCheckout['mod_autodebit_iban']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_iban'; $this->shop->addFrontendError(__('Bitte die IBAN Nr überprüfen (Bankeinzug)', 'wpsg')); $error = true; }
					
				}
				else 
				{

					if (trim($arCheckout['mod_autodebit_blz']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_blz'; $this->shop->addFrontendError(__('Bitte die BLZ der Bank kontrollieren (Bankeinzug)', 'wpsg')); $error = true; }
					if (trim($arCheckout['mod_autodebit_knr']) == '') { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_knr'; $this->shop->addFrontendError(__('Bitte die Kontonummer überprüfen (Bankeinzug)', 'wpsg')); $error = true; }
					
				}
 			
				$iban = trim($arCheckout['mod_autodebit_iban']);
				
				if ($this->test_iban($iban) == false) { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_iban'; $this->shop->addFrontendError(__('Bitte die IBAN Nr überprüfen (Bankeinzug)', 'wpsg')); $error = true; }
				
				// BIC-Prüfung gewünscht
				if ($this->shop->get_option('wpsg_mod_autodebit_bic') == '1') { 
									
					$filename = WPSG_PATH_MOD.'mod_autodebit/blz.txt';
					$bic = trim($arCheckout['mod_autodebit_bic']);
									
					//SELECT * FROM `wp_wpsg_bankdata` WHERE `mod_autodebit_bic` = "HELADEF1WEM"
					$check = $this->db->fetchRow('SELECT * FROM '.WPSG_TBL_BANKDATA.
									 ' WHERE `mod_autodebit_bic` = "'.$bic.'"');
					
					//$check = $this->readBLZBICFile($filename, false, $bic);
					if ($check == null) { $_SESSION['wpsg']['errorFields'][] = 'mod_autodebit_bic'; $this->shop->addFrontendError(__('Bitte die BIC der Bank kontrollieren (Bankeinzug)', 'wpsg')); $error = true; }
				}
			}
			
		} // public function checkCheckout(&$state, &$error, &$arCheckout)
		
		/**
		 * Liest die BLZBic Infodatei und gibt Informationen anhand der BLZ oder BIC zurück
		 * http://www.bundesbank.de/Redaktion/DE/Downloads/Aufgaben/Unbarer_Zahlungsverkehr/Bankleitzahlen/merkblatt_bankleitzahlendatei.pdf?__blob=publicationFile
		 * http://www.bundesbank.de/Redaktion/DE/Standardartikel/Aufgaben/Unbarer_Zahlungsverkehr/bankleitzahlen_download.html
		 */
		private function readBLZBICFile($filename, $searchBLZ = false, $searchBIC = false, $import = false)
		{
				
			if ($searchBLZ === false && $searchBIC === false && $import === false) return array();
				
			$handle = fopen($filename, "r");
		
			while (($line = fgets($handle, 4096)) !== false)
			{
		
				$line = trim($line);
				//$line = utf8_encode($line);
				//$line = iconv("ISO-8859-1", "UTF-8", $line);
				//$line = iconv("ISO-8859-2", "UTF-8", $line);
				//$line = iconv("Windows-1251", "UTF-8", $line);
				//$line = iconv("Windows-1252", "UTF-8", $line);
				//$line = iconv("CP1252", "UTF-8", $line);
				//$line = iconv("UTF-8", "UTF-8", $line);
				
				$arRow = $this->explodeRow($line);
		
				// Nur Hauptsitze erfassen
				if (trim($arRow['8']) === '') continue;
					
				if ($import == false)
				{
					if ($arRow['bic'] == $searchBIC || $arRow['blz'] == $searchBLZ) return $arRow;
				}
				else 
				{
					// In Tabelle importieren
					// Insert Query in einem Modul: 
					// $this->db->ImportQuery(WPSG_TBL_BANKDATA, array('col' => wpsg_q('value'), 'col2' => wpsg_q('value2')));
					$bic = $arRow['bic'];
					$this->db->ImportQuery(WPSG_TBL_BANKDATA, array(
							'mod_autodebit_blz' => wpsg_q($arRow[1]),
							'mod_autodebit_merkmal' => wpsg_q($arRow[2]),
							'mod_autodebit_name' => wpsg_q($arRow[3]),
							'mod_autodebit_plz' => wpsg_q($arRow[4]),
							'mod_autodebit_ort' => wpsg_q($arRow[5]),
							'mod_autodebit_kurz' => wpsg_q($arRow[6]),
							'mod_autodebit_institut' => wpsg_q($arRow[7]),
							'mod_autodebit_bic' => wpsg_q($arRow[8]),
							'mod_autodebit_kenn' => wpsg_q($arRow[9]),
							'mod_autodebit_dsnummer' => wpsg_q($arRow[10]),
							'mod_autodebit_aekenn' => wpsg_q($arRow[11]),
							'mod_autodebit_loesch' => wpsg_q($arRow[12]),
							'mod_autodebit_nachfolge' => wpsg_q($arRow[13])
							));
				}
			}
			
			return false;
				
		} // private function readBLZBICFile($file, $searchBLZ = false, $searchBIC = false)
		
		private function explodeRow($line)
		{
				
			$arRow = array(
					'1' => mb_substr($line, 0, 8, "UTF-8"), // Bankleitzahl
					'2' => mb_substr($line, 8, 1, "UTF-8"), // Merkmal, ob bankleitzahlführender Zahlungsdienstleister („1“) oder nicht („2“)
					'3' => mb_substr($line, 9, 58, "UTF-8"), // Bezeichnung des Zahlungsdienstleisters (ohne Rechtsform)
					'4' => mb_substr($line, 67, 5, "UTF-8"),
					'5' => mb_substr($line, 72, 35, "UTF-8"),
					'6' => mb_substr($line, 107, 27, "UTF-8"), // Kurzbezeichnung des Zahlungsdienstleisters mit Ort (ohne Rechtsform)
					'7' => mb_substr($line, 134, 5, "UTF-8"), // Institutsnummer für PAN
					'8' => mb_substr($line, 139, 11, "UTF-8"),
					'9' => mb_substr($line, 150, 2, "UTF-8"), // Knnzeichen für Prüfzifferberechnungsmethode
					'10' => mb_substr($line, 152, 6, "UTF-8"), // Nummer des Datensatzes
					'11' => mb_substr($line, 158, 1, "UTF-8"), // Änderungskennzeichen
					'12' => mb_substr($line, 159, 1, "UTF-8"), // Hinweis auf beabsichtigte Bankleitzahllöschung
					'13' => mb_substr($line, 160, 8, "UTF-8"), // Hinweis auf Nachfolge-Bankleitzahl
					'14' => mb_substr($line, 168, 6, "UTF-8") // Kennzeichen für die IBAN-Regel (nur erweiterte Bankleitzahlendatei)
			);
				
			$arRow['blz'] = $arRow[1];
			$arRow['name'] = $arRow[3];
			$arRow['plz'] = $arRow[4];
			$arRow['ort'] = $arRow[5];
			$arRow['bic'] = $arRow[8];
				
			return $arRow;
				
		} // private function explodeRow($row)
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) { 
			
			if (isset($_SESSION['wpsg']['checkout']) && $_SESSION['wpsg']['checkout']['payment'] == $this->id) {
				
				$checkout = $_SESSION['wpsg']['checkout'];
				
				// Daten in die Bestelltabelle hinzutragen
				$db_data['mod_autodebit_name'] = wpsg_q($checkout['mod_autodebit_name']);
				$db_data['mod_autodebit_blz'] = wpsg_q($checkout['mod_autodebit_blz']);
				$db_data['mod_autodebit_bic'] = wpsg_q($checkout['mod_autodebit_bic']);
				$db_data['mod_autodebit_inhaber'] = wpsg_q($checkout['mod_autodebit_inhaber']);
				$db_data['mod_autodebit_knr'] = wpsg_q($checkout['mod_autodebit_knr']);
				$db_data['mod_autodebit_iban'] = wpsg_q($checkout['mod_autodebit_iban']);
			
			} 
			
		}
		 
		public function order_view_afterpayment(&$order_id) 
		{ 
									
			$order_data = $this->shop->cache->loadOrder($order_id);
			
			if ($order_data['type_payment'] != $this->id) return;
			
			$this->shop->view['wpsg_mod_autodebit']['name'] = $order_data['mod_autodebit_name'];
			$this->shop->view['wpsg_mod_autodebit']['blz'] = $order_data['mod_autodebit_blz'];
			$this->shop->view['wpsg_mod_autodebit']['bic'] = $order_data['mod_autodebit_bic'];
			$this->shop->view['wpsg_mod_autodebit']['inhaber'] = $order_data['mod_autodebit_inhaber'];
			$this->shop->view['wpsg_mod_autodebit']['knr'] = $order_data['mod_autodebit_knr'];
			$this->shop->view['wpsg_mod_autodebit']['iban'] = $order_data['mod_autodebit_iban'];
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/order_view_afterpayment.phtml');
			
		} // public function order_view_afterpayment(&$order_id)
		
		public function mail_payment() 
		{ 
			
			if ($this->shop->view['basket']['checkout']['payment'] != $this->id) return;
			
			$this->shop->view['wpsg_mod_autodebit']['name'] = $this->shop->view['basket']['checkout']['mod_autodebit_name'];
			$this->shop->view['wpsg_mod_autodebit']['blz'] = $this->shop->view['basket']['checkout']['mod_autodebit_blz'];
			$this->shop->view['wpsg_mod_autodebit']['bic'] = $this->shop->view['basket']['checkout']['mod_autodebit_bic'];
			$this->shop->view['wpsg_mod_autodebit']['inhaber'] = $this->shop->view['basket']['checkout']['mod_autodebit_inhaber'];
			$this->shop->view['wpsg_mod_autodebit']['knr'] = $this->shop->view['basket']['checkout']['mod_autodebit_knr'];
			$this->shop->view['wpsg_mod_autodebit']['iban'] = $this->shop->view['basket']['checkout']['mod_autodebit_iban'];
			
			if ($this->shop->htmlMail === true)
			{

				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/mail_html.phtml');
				
			}			
			else
			{
			
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_autodebit/mail.phtml');
				
			}
			
		} // public function mail_payment()
				
		public function make_iban($blz, $kontonr, $land_kuerzel) 
		{
			
  			$blz8 = str_pad ( $blz, 8, "0", STR_PAD_RIGHT);
  			$kontonr10 = str_pad ( $kontonr, 10, "0", STR_PAD_LEFT);
  			$bban = $blz8 . $kontonr10;
  			$pruefsumme = $bban."131400";
  			$modulo = (bcmod($pruefsumme,"97"));
  			$pruefziffer =str_pad ( 98 - $modulo, 2, "0",STR_PAD_LEFT);
  			$iban = $land_kuerzel.$pruefziffer.$bban;
  
  			return $iban;

		} // public function make_iban($blz, $kontonr) 
		
		/**
		 * Prüfen der IBAN auf Richtigkeit
		 * @param unknown $iban
		 * @return boolean
		 */
		public function test_iban($iban) 
		{
			
			$iban = str_replace(' ', '', $iban);
			$iban1 = substr($iban, 4).strval(ord($iban{0}) - 55).strval(ord($iban{1}) - 55). substr($iban, 2, 2);
		
			for ($i = 0; $i < strlen($iban1); $i++) 
			{
				
				if(ord($iban1{$i}) > 64 && ord($iban1{$i}) < 91) 
				{
					
					$iban1 = substr($iban1, 0, $i).strval(ord($iban1{$i}) - 55).substr($iban1, $i + 1);
					
				}
				
			}
			
			$rest = 0;
			
			for ($pos=0; $pos < strlen($iban1); $pos += 7) 
			{
				
				$part = strval($rest).substr($iban1, $pos, 7);
				$rest = intval($part) % 97;
				
			}
			
			$pz = sprintf("%02d", 98-$rest);
		
			if (substr($iban, 2, 2) == '00')
			{
			
				return substr_replace($iban, $pz, 2, 2);
				
			} 
			else 
			{
			
				return ($rest == 1)?true:false;
				
			}
		
		} // public function test_iban( $iban )
		
	} // class wpsg_mod_autodebit extends wpsg_mod_basic

?>