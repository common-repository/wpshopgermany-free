<?php

	/**
	 * Modul für die Zahlungsart Sofortüberweisung
	 */
	class wpsg_mod_su extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 5;
		var $url = false; 
		var $hilfeURL = 'http://wpshopgermany.de/?p=1335';
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Sofortüberweisung', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlungsart Sofortüberweisung.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{

			$this->shop->checkDefault('wpsg_mod_su_bezeichnung', $this->name, false, true);
			$this->shop->checkDefault('wpsg_mod_su_hint', __('Zahlen Sie die per Sofortüberweisung. Ihre Bank muss dieses Verfahren unterstützen und sie benötigen ihre PIN/TAN zur Zahlung.', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_su_subject1', 'O%order_id% - K%kunde_id%', false, true);
			$this->shop->checkDefault('wpsg_mod_su_subject2', '', false, true);			
			
			$this->shop->checkDefault('wpsg_mod_su_gebuehr', '0');
			$this->shop->checkDefault('wpsg_mod_su_mwst', '0');
			$this->shop->checkDefault('wpsg_mod_su_mwstland', '0');
			
			$this->shop->checkDefault('wpsg_mod_su_currency', 'EUR');
			$this->shop->checkDefault('wpsg_mod_su_userid', __('', 'wpsg'));
			$this->shop->checkDefault('wpsg_mod_su_projectid', __('', 'wpsg'));
			$this->shop->checkDefault('wpsg_mod_su_projectpassword', __('', 'wpsg'));
			$this->shop->checkDefault('wpsg_mod_su_noticepassword', __('', 'wpsg'));
			$this->shop->checkDefault('wpsg_mod_su_language', 'DE');
			$this->shop->checkDefault('wpsg_mod_su_hash', 'md5');
			$this->shop->checkDefault('wpsg_mod_su_autostart', '0');
					
		} // public function install()
		
		public function init() 
		{
			
			if ($this->shop->get_option('wpsg_mod_su_sandbox') == 1)
			{
				$this->url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
			}
			else		
			{		
				$this->url = "https://www.paypal.com/cgi-bin/webscr";
			}
			
		} // public function init()
		
		public function settings_edit()
		{
			 
			$pages = get_pages();
			
			$arPages = array(
				'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			$arPagesURL = array();
			
			foreach ($pages as $k => $v)
			{
				
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
				$arPagesURL[$v->ID] = get_permalink($v->ID);
				
			}
			
			$basket_url = $this->shop->getURL(wpsg_ShopController::URL_BASKET);
			
			if (strpos($basket_url, '?') === false)
			{
				
				$basket_url .= '?wpsg_plugin=wpsg_mod_su&confirm=su';
				
			}
			else
			{
				
				$basket_url .= '&wpsg_plugin=wpsg_mod_su&confirm=su';
				
			}
			
			$this->shop->view['wpsg_mod_su_confirmurl'] = $basket_url;			
			$this->shop->view['pagesurl'] = $arPagesURL;
			$this->shop->view['pages'] = $arPages;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_su/settings_edit.phtml');
			
		} // public function settings_edit()
				
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_su_bezeichnung', $_REQUEST['wpsg_mod_su_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_aktiv', $_REQUEST['wpsg_mod_su_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_su_hint', $_REQUEST['wpsg_mod_su_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->update_option('wpsg_mod_su_subject1', $_REQUEST['wpsg_mod_su_subject1'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_su_subject1', $_REQUEST['wpsg_mod_su_subject1'], WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_subject2', $_REQUEST['wpsg_mod_su_subject2'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_su_subject2', $_REQUEST['wpsg_mod_su_subject2'], WPSG_SANITIZE_TEXTFIELD);
						
			$this->shop->update_option('wpsg_mod_su_gebuehr', $_REQUEST['wpsg_mod_su_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_su_mwst', $_REQUEST['wpsg_mod_su_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_su_mwstland', $_REQUEST['wpsg_mod_su_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			$this->shop->update_option('wpsg_mod_su_currency', $_REQUEST['wpsg_mod_su_currency'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_userid', $_REQUEST['wpsg_mod_su_userid'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_projectid', $_REQUEST['wpsg_mod_su_projectid'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_projectpassword', $_REQUEST['wpsg_mod_su_projectpassword'], false, false, WPSG_SANITIZE_APIKEY);
			$this->shop->update_option('wpsg_mod_su_noticepassword', $_REQUEST['wpsg_mod_su_noticepassword'], false, false, WPSG_SANITIZE_APIKEY);
			$this->shop->update_option('wpsg_mod_su_language', $_REQUEST['wpsg_mod_su_language'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_hash', $_REQUEST['wpsg_mod_su_hash'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_su_autostart', $_REQUEST['wpsg_mod_su_autostart'], false, false, WPSG_SANITIZE_CHECKBOX);
			 			
		} // public function settings_save()
		
		public function addPayment(&$arPayment) { 

			if (!is_admin() && $this->shop->get_option('wpsg_mod_su_aktiv') != '1') return;
			
			$arPayment[$this->id] = array(
				'id' => $this->id,
				'name' => __($this->shop->get_option('wpsg_mod_su_bezeichnung'), 'wpsg'),
				'hint' => __($this->shop->get_option('wpsg_mod_su_hint')),
				'price' => $this->shop->get_option('wpsg_mod_su_gebuehr'),
				'tax_key' => $this->shop->get_option('wpsg_mod_su_mwst'),
				'mwst_null' => $this->shop->get_option('wpsg_mod_su_mwstland'),
			    'logo' => $this->shop->getRessourceURL('mods/mod_su/gfx/logo_100x25.png')
			);
						
		} // public function addPayment(&$arPayment)
		 
		public function order_done(&$order_id, &$done_view) { 
			
			$oOrder = wpsg_order::getInstance($order_id);			

			// Bestellungen mit 0 geben nix aus
			if ($oOrder->getToPay() <= 0) return;
			
			if ($this->shop->view['basket']['checkout']['payment'] == $this->id)
			{
			
				$this->shop->view['suLink'] = $this->getPayLink($order_id);
			
				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_su/order_done.phtml', false);

			}
						
		} // public function order_done(&$order_id)
		
		public function mail_payment() 
		{ 
			
			if ($this->shop->view['basket']['checkout']['payment'] != $this->id) return;
			
			if ($this->shop->htmlMail === true)
			{
				
				echo '<a href="'.$this->shop->getDoneURL($this->shop->view['o_id']).'">'.__('Zahlungslink', 'wpsg').'</a>'.__(', um die Zahlung durchzuführen', 'wpsg');
				
			}
			else
			{
			
				echo wpsg_pad_right(__('Zahlungslink', 'wpsg').':', 35).$this->shop->getDoneURL($this->shop->view['o_id']);
				
			}
			
		} // public function mail_payment()
				
		public function template_redirect() { 

			if (wpsg_isSizedString($_REQUEST['wpsg_plugin'], 'wpsg_mod_su') && wpsg_isSizedString($_REQUEST['confirm'], 'su')) {

				try {
				
					if (!wpsg_checkInput($_REQUEST['user_variable_2'], WPSG_SANITIZE_INT)) throw new \Exception(__('Ungültiger Wert in user_variable_2', 'wpsg'));
					
					$order = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($_REQUEST['user_variable_2'])."'");
					if ($order['id'] <= 0) die();
					
					$oOrder = wpsg_order::getInstance($order['id']);
					
					// Den Request validiere ich hier nicht weiter, da ich ihn nur zum Hash Abgleich brauche und somit die validität prüfe
					
					$arHash = array(
						'transaction' => $_REQUEST['transaction'],
						'user_id' => $_REQUEST['user_id'],
						'project_id' => $_REQUEST['project_id'],
						'sender_holder' => $_REQUEST['sender_holder'],
						'sender_account_number' => $_REQUEST['sender_account_number'],
						'sender_bank_code' => $_REQUEST['sender_bank_code'],
						'sender_bank_name' => $_REQUEST['sender_bank_name'],
						'sender_bank_bic' => $_REQUEST['sender_bank_bic'],
						'sender_iban' => $_REQUEST['sender_iban'],
						'sender_country_id' => $_REQUEST['sender_country_id'],
						'recipient_holder' => $_REQUEST['recipient_holder'],
						'recipient_account_number' => $_REQUEST['recipient_account_number'],
						'recipient_bank_code' => $_REQUEST['recipient_bank_code'],
						'recipient_bank_name' => $_REQUEST['recipient_bank_name'],
						'recipient_bank_bic' => $_REQUEST['recipient_bank_bic'],
						'recipient_iban' => $_REQUEST['recipient_iban'],
						'recipient_country_id' => $_REQUEST['recipient_country_id'],
						'international_transaction' => $_REQUEST['international_transaction'],
						'amount' => number_format(wpsg_tf($oOrder->getToPay()), 2, '.', ''),
						'currency_id' => $_REQUEST['currency_id'],
						'reason_1' => $_REQUEST['reason_1'],
						'reason_2' => $_REQUEST['reason_2'],
						'security_criteria' => $_REQUEST['security_criteria'],
						'user_variable_0' => $_REQUEST['user_variable_0'],
						'user_variable_1' => $_REQUEST['user_variable_1'],
						'user_variable_2' => $_REQUEST['user_variable_2'],
						'user_variable_3' => $_REQUEST['user_variable_3'],
						'user_variable_4' => $_REQUEST['user_variable_4'],
						'user_variable_5' => $_REQUEST['user_variable_5'],
						'created' => $_REQUEST['created'],
						'notification_password' => $this->shop->get_option('wpsg_mod_su_noticepassword')
					);
					
					$strHash = '';
					
					switch ($this->shop->get_option('wpsg_mod_su_hash')) {
						
						case 'md5':
							$strHash = md5(implode("|", $arHash));
							break;
						case 'sha1':
							$strHash = sha1(implode("|", $arHash));
							break;
						case 'sha256':
							$strHash = hash("sha256", implode("|", $arHash));
							break;
						case 'sha512':
							$strHash = hash("sha512", implode("|", $arHash));
							break;
							
					}
					
					if ($strHash === $_REQUEST['hash']) {
						
						$this->db->ImportQuery(WPSG_TBL_OL, array(
							"title" => __("Sofortüberweisung VERIFIED", 'wpsg'),
							"cdate" => "NOW()",
							"o_id" => wpsg_q($order['id']),
							"mailtext" => print_r($_REQUEST, 1)
						));
						
						$this->shop->setOrderStatus($order['id'], 100, true);
						
					} else throw new \Exception(__('Hash konnte nicht verifiziert werden.', 'wpsg'));
					
				} catch (\Exception $e) {
					
					$this->db->ImportQuery(WPSG_TBL_OL, array(
						"title" =>  __("Sofortüberweisung FAILED: ", 'wpsg').$e->getMessage(),
						"cdate" => "NOW()",
						"o_id" => wpsg_q($order['id']),
						"mailtext" => print_r($_REQUEST, 1)
					));
					
				}
				
				die();
				
			}
		
		} // public function template_redirect()
			
		/**
		 * Gibt den Link für die Bezahlung anhand der BestellID zurück
		 */
		public function getPayLink($order_id)
		{

			$basket_link = get_permalink($this->shop->get_option('wpsg_page_basket'));
			
			if (strpos($basket_link, "?") > 0)
			{
				$basket_link .= "&wpsg_plugin=wpsg_mod_paypal&confirm=pp";
			}
			else
			{
				$basket_link .= "?wpsg_plugin=wpsg_mod_paypal&confirm=pp";
			}
 
			$order = $this->db->fetchRow("
				SELECT
					O.`id` AS o_id, O.`onr`, O.`price_gesamt`,
					K.`id` AS k_id, K.`knr`
				FROM
					`".WPSG_TBL_ORDER."` AS O
						LEFT JOIN `".WPSG_TBL_KU."` AS K ON (O.`k_id` = K.`id`)
				WHERE
					O.`id` = '".wpsg_q($order_id)."'						
			");
			
			$subject1 = $this->shop->replaceUniversalPlatzhalter(__($this->shop->get_option('wpsg_mod_su_subject1'), 'wpsg'), $order_id);
			$subject2 = $this->shop->replaceUniversalPlatzhalter(__($this->shop->get_option('wpsg_mod_su_subject2'), 'wpsg'), $order_id);
			
			$oOrder = wpsg_order::getInstance($order_id);
			
			$arHash = array(
				$this->shop->get_option('wpsg_mod_su_userid'), // user_id
				$this->shop->get_option('wpsg_mod_su_projectid'), // project_id
				"", // sender_holder
				"", // sender_account_number
				"", // sender_bank_code
				"", // sender_country_id
				number_format(wpsg_tf($oOrder->getToPay()), 2, '.', ''), // amount
				$this->shop->get_option("wpsg_mod_su_currency"), // currency_id
				$subject1, // reason_1
				$subject2, // reason_2
				"Kundennummer: ".$order['k_id'], // user_variable_0
				"Bestellnummer: ".((trim($order['onr']) != '')?$order['onr']:$order['o_id']), // user_variable_1
				$order['o_id'], // user_variable_2
				"", // user_variable_3
				"", // user_variable_4
				"", // user_variable_5
				$this->shop->get_option('wpsg_mod_su_projectpassword'), // project_password
			);
			
			switch ($this->shop->get_option('wpsg_mod_su_hash'))
			{
				case 'md5':
					$strHash = md5(implode("|", $arHash));
					break;
				case 'sha1':
					$strHash = sha1(implode("|", $arHash));
					break;
				case 'sha256':
					$strHash = hash("sha256", implode("|", $arHash));
					break;
				case 'sha512':
					$strHash = hash("sha512", implode("|", $arHash));
					break;
			}
			
			return "https://www.sofortueberweisung.de/payment/start?".
				"user_id=".urlencode($this->shop->get_option('wpsg_mod_su_userid'))."&".
				"project_id=".urlencode($this->shop->get_option('wpsg_mod_su_projectid'))."&".
				"amount=".urlencode(number_format(wpsg_tf($oOrder->getToPay()), 2, '.', ''))."&".
				"reason_1=".urlencode($subject1)."&".
				"reason_2=".urlencode($subject2)."&".
				"hash=".urlencode($strHash)."&".
				"currency_id=".urlencode($this->shop->get_option('wpsg_mod_su_currency'))."&".
				"language_id=".urlencode($this->shop->get_option('wpsg_mod_su_language'))."&".
				"user_variable_0=".urlencode("Kundennummer: ".$order['k_id'])."&".
				"user_variable_1=".urlencode("Bestellnummer: ".((trim($order['onr']) != '')?$order['onr']:$order['o_id']))."&".
				"user_variable_2=".$order['o_id'];
			
		} // public function getPayLink($order_id)
		
	} // class wpsg_mod_su extends wpsg_mod_basic

?>