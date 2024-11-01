<?php 

	class wpsg_mod_kundenkontakt extends wpsg_mod_basic 
	{
		
		var $lizenz = 1;
		var $id = 1000;
		var $hilfeURL = 'http://wpshopgermany.de/?p=3886';
		
		static $arSMSStatus = [];
		static $sms_endpoint = 'https://www.smsflatrate.net/appkey.php';
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name 	= __('Kundenkontakt', 'wpsg');
			$this->group 	= __('Sonstiges', 'wpsg');
			$this->desc 	= __('Erlaubt es in der Bestellverwaltung dem Kunden E-Mails zu senden.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			 			
			/**
			 * Ländertabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_LAND." (
			  	telprefix TEXT NOT NULL COMMENT 'Vorwahlen, kommagetrennt für SMS Versand Kundenkontakt'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
		}
		
		public function init() {
			
			self::$arSMSStatus = [
				'100' => __('SMS erfolgreich an das Gateway übertragen.', 'wpsg'),
				'101' => __('SMS wurde zugestellt.', 'wpsg'),
				'102' => __('SMS wurde noch nicht zugestellt.', 'wpsg'),
				'103' => __('SMS konnte vermutlich nicht zugestellt werden (Rufnummer falsch, SIM nicht aktiv).', 'wpsg'),
				'104' => __('SMS konnte nach Ablauf von 48 Stunden noch immer nicht zugestellt werden.', 'wpsg'),
				'109' => __('SMS ID abgelaufen oder ungültig (manuelle Status-Abfrage).', 'wpsg'),
				'110' => __('Falscher Schnittstellen-Key oder Ihr Account ist gesperrt.', 'wpsg'),
				'120' => __('Guthaben reicht nicht aus.', 'wpsg'),
				'130' => __('Falsche Datenübergabe (z.B. Absender fehlt).', 'wpsg'),
				'131' => __('Empfänger nicht korrekt.', 'wpsg'),
				'132' => __('Absender nicht korrekt.', 'wpsg'),
				'133' => __('Nachrichtentext nicht korrekt.', 'wpsg'),
				'140' => __('Falscher AppKey oder Ihr Account ist gesperrt.', 'wpsg'),
				'150' => __('Sie haben versucht an eine internationale Handynummer eines Gateways, das ausschließlich für den Versand nach Deutschland bestimmt ist, zu senden.', 'wpsg'),
				'170' => __('Parameter „time=“ ist nicht korrekt. Bitte im Format: TT.MM.JJJJ-SS:MM oder Parameter entfernen für sofortigen Versand.', 'wpsg'),
				'171' => __('Parameter „time=“ ist zu weit in der Zukunft terminiert (max. 360 Tage).', 'wpsg'),
				'231' => __('Keine smsflatrate.net Gruppe vorhanden oder nicht korrekt.', 'wpsg'),
				'404' => __('Unbekannter Fehler. Bitte dringend Support (ticket@smsflatrate.net) kontaktieren.', 'wpsg'),
			];
			
		} // public function init()
		
		public function settings_edit() {
			 			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenkontakt/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() {

		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_active', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['active'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_key', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['key'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_from', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['from'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_type', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['type'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_status', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['status'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_reply', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['reply'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_kundenkontakt_smsflatrate_replytomail', $_REQUEST['wpsg_mod_kundenkontakt']['smsflatrate']['replytomail'], false, false, WPSG_SANITIZE_EMAIL);
						
		}
		
		/**
		 * zeigt das Formular zum Senden in der Bestellverwaltung
		 */
		public function order_view($order_id, &$arSidebarArray)
		{
			
			$path = $this->shop->getRessourcePath('mods/mod_kundenkontakt/templates/');

			$this->shop->view['arTemplates'] = array();
			
			$arrFiles = scandir($path);
			
			foreach ($arrFiles as $file)
			{
				
				if (is_file($path.$file) && preg_match('/(.*)\.phtml/', $file) && !preg_match('/(.*)_html\.phtml/', $file))
				{
					
					$template_name = str_replace('.phtml', '', $file);
					
					$this->shop->view['arTemplates'][$file]['filename'] = $file;
					$this->shop->view['arTemplates'][$file]['name'] = ucfirst($template_name);
					
				}
				
			}
			
			$oOrder = wpsg_order::getInstance($order_id);
			
			$phone = $oOrder->getCustomer()->getPhone();
			$phone = preg_replace('/^\+/', '00',$phone);
			
			$this->shop->view['valid'] = $this->isValidPhoneNumber($phone);
			$this->shop->view['phone'] = $phone;
			
			$arSidebarArray[$this->id] = array(
				'title' => $this->name,
				'content' => $this->shop->render(WPSG_PATH_VIEW.'mods/mod_kundenkontakt/order_view.phtml', false)
			);
			
		} // public function order_view_content($order_id)
		
		public function order_ajax()
		{
			
			$_REQUEST['do'] = wpsg_getStr($_REQUEST['do']);
			
			if ($_REQUEST['do'] === 'kk_switchTemplate') {
				
				$this->switchTemplate();
				
			} else if ($_REQUEST['do'] === 'kk_sendMail') {
				
				$this->send();
				
			} else if ($_REQUEST['do'] === 'validateNumber') {
							
				$valid = $this->isValidPhoneNumber($_REQUEST['phone']);
				
				$strHTML = '';
				
				if ($valid) {
					
					$strHTML .= '<div class="alert alert-success">';
					$strHTML .= wpsg_translate(__('Die Telefonnummer #1# ist gültig.', 'wpsg'), $_REQUEST['phone']);
					$strHTML .= '</div>';
						
				} else {
					
					$strHTML .= '<div class="alert alert-danger">';
					$strHTML .= wpsg_translate(__('Die Telefonnummer #1# ist nicht gültig.', 'wpsg'), $_REQUEST['phone']);
					$strHTML .= '</div>';
					
				}
				
				wpsg_header::JSONData([
					'valid' => $valid,
					'text' => $strHTML
				]);
				
				exit;
				
			} else if ($_REQUEST['do'] == 'sms_submit') {
				
				$_REQUEST['edit_id'] = intval($_REQUEST['edit_id']);
				
				\check_admin_referer('wpsg_mod_kundenkontakt_sms_form_'.$_REQUEST['edit_id']);
				
				$r = $this->sendSMS($_REQUEST['phone'], $_REQUEST['text']);
				
				if (!in_array($r[0], ['100', '101'])) {
					
					$this->shop->addBackendError($r[1]);
					
				} else {
					
					$this->shop->addBackendMessage($r[1]);
					
				}
				
				$oOrder = wpsg_order::getInstance($_REQUEST['edit_id']);
				
				$log_title = wpsg_translate(__('SMS: #1# / #2#', 'wpsg'), $_REQUEST['phone'], $r[1]);
				$log_text  = "Empfänger  : ".$_REQUEST['phone']."\r\n";
				$log_text .= "API Return : ".$r[0].': '.$r[1]."\r\n";
				$log_text .= "RAW        : ".$r[2];
				
				$oOrder->addLogEntry($log_title, $log_text);
				
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&action=view&edit_id='.$_REQUEST['edit_id']);
				exit;
				
			}
			
		}
		
		public function admin_emailconf()
		{
			
			echo wpsg_drawEMailConfig(
				'customercontact', 
				__('E-Mail, die vom Kundenkontakt Modul versendet wird.', 'wpsg'), 
				__('Diese Mail kann in der Bestellverwaltung an den Kunden versendet werden.', 'wpsg')); 
						
		} // public function admin_emailconf()
		
		public function admin_emailconf_save() 
		{
			
			wpsg_saveEMailConfig("customercontact");
			 			
		} // public function admin_emailconf_save()
		
		public function laender_edit() {
						
			echo wpsg_drawForm_Textarea('country[telprefix]', __('Mögliche Vorwahlen für SMS Versand. (kommagetrennt)', 'wpsg'),wpsg_getStr($this->shop->view['land']['telprefix']), ['help' => 'country_telprefix']);
			
		}
		
		/* Modulfunktionen */
		 
		private function isValidPhoneNumber($phone) {
			
			$arLaender = $this->shop->cache->getCountry();
			$arPrefix = [];
			
			foreach ($arLaender as $l_db) {
				
				$arPrefix = array_merge($arPrefix, explode(',', $l_db['telprefix']));
				
			}
			
			$arPrefix = array_unique($arPrefix);
			$arPrefix = wpsg_trim($arPrefix);
			
			foreach ($arPrefix as $p) {
				
				if (substr($phone, 0, strlen($p)) === $p) return true;
				
			}
			
			return false;
			
		}
		
		/**
		 * Gibt true zurück, wenn das SMS Versandmodul aktiv ist und komplett konfiguriert
		 */
		private function isPossibleSMSSend($tel = false) {
			
			if ($this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_active') !== '1') return false;
			
			if ($tel !== false) {
				
				return $this->isValidPhoneNumber($tel);
				
			}
			
			return true;
			
		}
						
		public function sendSMS($to, $text) {
			
			if (!$this->isPossibleSMSSend()) throw new \wpsg\Exception(__('SMS Versand nicht aktiviert.', 'wpsg'));
			
			$arFields = [
				'lizenz' => '215100700',
				'aid' => '6546',
				'appkey' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_key'),
				'from' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_from'),
				'to' => $to,
				'text' => $text,
				'type' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_type'),
				'status' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_status'),
				'reply' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_reply'),
				'replytomail' => $this->shop->get_option('wpsg_mod_kundenkontakt_smsflatrate_replytomail')
			];
			
			$ch = curl_init();
			
			//curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($ch,CURLOPT_URL,self::$sms_endpoint);
			curl_setopt($ch,CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $arFields);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			
			$result = curl_exec($ch);
			
			curl_close($ch);
			
			$send_sms_return = substr($result, 0, 3); 
						
			return array($send_sms_return, wpsg_getStr(self::$arSMSStatus[$send_sms_return], __('Unbekannte API Antwort', 'wpsg')), $result);
			
		}
		
		/**
		 * 
		 * ermöglicht die Auswahl eines Templates zum Versenden als Kundenkontakt
		 */
		private function switchTemplate()
		{
			
			if ($_REQUEST['template_file'] != '-1')
			{
				 
				$path = $this->shop->getRessourcePath('mods/mod_kundenkontakt/templates/');
				
				$basket = new wpsg_basket();
				$basket->initFromDB(wpsg_q($_REQUEST['edit_id']));

				if ($this->shop->get_option('wpsg_htmlMail') === '1')
				{
					
					$this->shop->htmlMail = true;
					
				}
				
				$this->shop->view['basket'] = $basket->toArray();
				$this->shop->view['order'] = $this->shop->cache->loadOrder($_REQUEST['edit_id']);
				$this->shop->view['kunde'] = $this->shop->cache->loadKunden($this->shop->view['order']['k_id']);
				
				$order_data = $this->shop->cache->loadOrder($_REQUEST['edit_id']);
				if (trim($order_data['language']) != '') $this->shop->setTempLocale($order_data['language']);
				
				$this->shop->view['datum'] = time();
				
				// Namen und Betreff des Templates ermitteln
				$template_name = "";
				$template_betreff = ""; 
				
            	global $template_name, $template_betreff;
				
				$template = $path.wpsg_q($_REQUEST['template_file']);
				
				if ($this->shop->get_option('wpsg_htmlmail') === '1')
				{
					
					$template_html = preg_replace('/\.phtml$/i', '_html.phtml', $template);
					
					if (file_exists($template_html)) 
					{
						
						$template = $template_html;
												
					}
					
				}
								
				$content = $this->shop->render($template, false);				
				$content = $this->shop->replaceUniversalPlatzhalter($content, $_REQUEST['edit_id']);
				$template_betreff = $this->shop->replaceUniversalPlatzhalter($template_betreff, $_REQUEST['edit_id']);
				 				
				$this->shop->restoreTempLocale();
								
				$arData = array(
					'subject' => wpsg_sinput("text_field", $template_betreff),
					'content' => $content						
				);
				
				header('Content-Type: application/json');
				
				die(json_encode($arData));
				
			}

		}
		
		/**
		 * 
		 * Enter description here ...
		 */
		private function send()
		{ 
			
			$mail_text = $this->shop->replaceUniversalPlatzhalter($_REQUEST['text'], $_REQUEST['edit_id']);
			$to = $_REQUEST['empfaenger'];
			$subject = $this->shop->replaceUniversalPlatzhalter($_REQUEST['subject'], $_REQUEST['edit_id']);
			
			// HTML Mail
			if ($this->shop->get_option('wpsg_htmlmail') == '1')
			{
				
				$mail_html = $this->shop->replaceUniversalPlatzhalter($_REQUEST['text'], $_REQUEST['edit_id']);
				$mail_text = strip_tags($mail_html);
				
			} else $mail_html = false;
			 
			$this->shop->sendMail($mail_text, $to, 'customercontact', array(), $_REQUEST['edit_id'], false, $mail_html, $subject);
			
			$this->shop->addBackendMessage(__('Eine Nachricht über den Kundenkontakt wurde versendet.', 'wpsg'));
			
			$this->db->ImportQuery(WPSG_TBL_OL, array(
				'cdate' => 'NOW()',
				'o_id' => wpsg_q($_REQUEST['edit_id']),
				'title' => wpsg_q(wpsg_translate(__('Kundenkontakt "#1#"', 'wpsg'), $subject)),
				'mailtext' => wpsg_q($mail_text)					
			));
			 			
			die('1');
			
		}
	
	} // class wpsg_mod_kundenkontakt extends wpsg_mod_basic 
