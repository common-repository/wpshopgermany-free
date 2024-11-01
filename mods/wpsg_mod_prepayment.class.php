<?php

	/**
	 * Dieses Module ermöglicht die Zahlungsart "Vorkasse"
	 */
	class wpsg_mod_prepayment extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 1;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Vorkasse', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlungsart Vorkasse.', 'wpsg');
									
		} // public function __construct()
		
		public function install()
		{
			
			$this->shop->checkDefault('wpsg_mod_prepayment_bezeichnung', $this->name, false, true);
			$this->shop->checkDefault('wpsg_mod_prepayment_aktiv', '1');
			$this->shop->checkDefault('wpsg_mod_prepayment_hint', __('Zahlen Sie die Bestellung mittels Überweisung. Der Betreff wird ihnen in der Bestellbestätigung mitgeteilt.', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_prepayment_subject', 'O%order_id% - K%kunde_id%');
			$this->shop->checkDefault('wpsg_mod_prepayment_mwst', '0');
			$this->shop->checkDefault('wpsg_mod_prepayment_mwstland', '0');
			
			$this->shop->checkDefault('wpsg_mod_prepayment_kinhaber', '%shopinfo_accountowner%');
			$this->shop->checkDefault('wpsg_mod_prepayment_bank', '%shopinfo_bankname%');
			$this->shop->checkDefault('wpsg_mod_prepayment_iban', '%shopinfo_iban%');
			$this->shop->checkDefault('wpsg_mod_prepayment_swift', '%shopinfo_bic%');
			
		} // public function install()
		
		public function settings_edit()
		{
			 		
			$this->render(WPSG_PATH_VIEW.'/mods/mod_prepayment/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save()
		{
			
		    $this->shop->update_option('wpsg_mod_prepayment_bezeichnung', $_REQUEST['wpsg_mod_prepayment_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_aktiv', $_REQUEST['wpsg_mod_prepayment_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_prepayment_hint', $_REQUEST['wpsg_mod_prepayment_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_gebuehr', $_REQUEST['wpsg_mod_prepayment_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_prepayment_mwst', $_REQUEST['wpsg_mod_prepayment_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_prepayment_mwstland', $_REQUEST['wpsg_mod_prepayment_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_prepayment_kinhaber', $_REQUEST['wpsg_mod_prepayment_kinhaber'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_bank', $_REQUEST['wpsg_mod_prepayment_bank'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_iban', $_REQUEST['wpsg_mod_prepayment_iban'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_swift', $_REQUEST['wpsg_mod_prepayment_swift'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_prepayment_subject', $_REQUEST['wpsg_mod_prepayment_subject'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->addTranslationString('wpsg_mod_prepayment_bezeichnung', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_prepayment_bezeichnung']));
			$this->shop->addTranslationString('wpsg_mod_prepayment_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_prepayment_hint']));
			
		} // public function settings_save()
		
		public function checkGeneralBackendError() 
		{ 

			if ($this->shop->get_option('wpsg_mod_prepayment_aktiv') === '1')
			{
				
				if (	!wpsg_isSizedString($this->shop->get_option('wpsg_mod_prepayment_bank')) ||
						!wpsg_isSizedString($this->shop->get_option('wpsg_mod_prepayment_iban')) ||
						!wpsg_isSizedString($this->shop->get_option('wpsg_mod_prepayment_swift')) || 
						!wpsg_isSizedString($this->shop->get_option('wpsg_mod_prepayment_kinhaber'))	)
				{
					
					$this->shop->addBackendError('nohspc_'.wpsg_translate(
						wpsg_translate(
							__('Die Zahlungsart "Vorkasse" ist aktiv, es wurden aber nur unvollständige Kontodaten angegeben. Überprüfen Sie die <a href="#1#">Moduleinstellungen</a>.', 'wpsg'),
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_prepayment'
						)
					));
					
				}
				
			}
			
		} // public function checkGeneralBackendError()
		
		public function addPayment(&$arPayment) { 
 
			if (!is_admin() && $this->shop->get_option('wpsg_mod_prepayment_aktiv') != '1') return;
			
			$arPayment[$this->id] = array(
				'id' => $this->id,
				'name' => __($this->shop->get_option('wpsg_mod_prepayment_bezeichnung'), 'wpsg'),
				'price' => $this->shop->get_option('wpsg_mod_prepayment_gebuehr'),
				'tax_key' => $this->shop->get_option('wpsg_mod_prepayment_mwst'),
				'mwst_null' => $this->shop->get_option('wpsg_mod_prepayment_mwstland'),
				'hint' => $this->shop->get_option('wpsg_mod_prepayment_hint'),
				'logo' => $this->shop->getRessourceURL('mods/mod_prepayment/gfx/logo_100x25.png')
			);
			 			
		} // public function addPayment(&$arShipping)
		 
		public function order_done(&$order_id, &$done_view) 
		{ 

			// Bestellungen mit 0 geben nix aus
			if ($done_view['basket']['sum']['preis_gesamt_brutto'] <= 0) return;
			
			if ($this->shop->view['basket']['checkout']['payment'] == $this->id)
			{
				
				$this->shop->view['wpsg_mod_prepayment']['subject'] = $this->get_subject($order_id);
				
				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_prepayment/order_done.phtml', false);

			}
						
		} // public function order_done(&$order_id)
		
		public function mail_payment() 
		{ 

			if ($this->shop->view['basket']['checkout']['payment'] != $this->id) return;
			
			$this->shop->view['mod_prepayment']['subject'] = $this->get_subject($this->shop->view['o_id']);
			
			if ($this->shop->htmlMail === true)
			{

				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_prepayment/mail_html.phtml');
				
			}
			else
			{
			
				echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_prepayment/mail.phtml');
				
			}
			
		} // public function mail_payment()
				
		/**
		 * Ersetzt die Platzhalter im Betreff
		 */
		public function get_subject($order_id) {
			
			$this->shop->cache->clearOrderCache($order_id);
			
			$ret = $this->shop->replaceUniversalPlatzhalter($this->shop->get_option('wpsg_mod_prepayment_subject'), $order_id);
			
			return $ret;
			
		} // private function get_subject($order_id)
		
		/**
		 * QRCode für eMail erzeugen
		 */
		public function genQRCode($order_id, $subject, $betrag, $size, $out) {
			
			require_once WPSG_PATH_LIB.'phpqrcode/qrlib.php';
			require_once WPSG_PATH_LIB.'phpgirocode.class.php';
			
            if (!file_exists(WPSG_PATH_UPLOADS.'wpsg_girocode/')) mkdir(WPSG_PATH_UPLOADS.'wpsg_girocode/', 0755, true);
            
			$fname1 = WPSG_PATH_UPLOADS.'wpsg_girocode/'.$order_id.'.png';
						
			$phpGiroCode = new PhpGirocode();
			$phpGiroCode->setBIC(wpsg_getStr($this->shop->get_option('wpsg_shopdata_bank_bic')));
			$phpGiroCode->setIBAN(wpsg_getStr($this->shop->get_option('wpsg_shopdata_bank_iban')));
			$phpGiroCode->setReciver(wpsg_getStr($this->shop->get_option('wpsg_shopdata_bank_owner')));
			$phpGiroCode->setAmount($betrag);
			$phpGiroCode->setText($subject);
			$phpGiroCode->setSize($size);
			
			if ($out == PhpGirocode::OUTPUT_BROWSER) {
				
				$phpGiroCode->generate(PhpGirocode::OUTPUT_BROWSER, $fname1);
			
			} else if ($out == PhpGirocode::OUTPUT_FILE) {
			
				$phpGiroCode->generate(PhpGirocode::OUTPUT_FILE, $fname1);
				
				$fname2 = WPSG_URL_WP.'?wpsg_action=wpsg_getGiroCode&order_id='.$order_id;
				
				$ret = '<img src="'.$fname2.'" />';

			} else if ($out == PhpGirocode::OUTPUT_BASE64) {
				
				$tmpfname = @tempnam("/tmp", "phpgirocode");				
				
				$ret = $phpGiroCode->generate(PhpGirocode::OUTPUT_BASE64, $tmpfname);
				
				
			} else {
				
				$ret = $phpGiroCode->generate(PhpGirocode::OUTPUT_TEST, false);
				
			}
			
			return $ret;
			
		} // private function genQRCode($betrag, $subject, $size, $out)
		
		public function template_redirect() {
			
			if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'wpsg_getGiroCode')) {
				
				header("Content-Type: image/png");
				
				readfile(WPSG_PATH_UPLOADS.'wpsg_girocode/'.intval(wpsg_sinput("key", $_REQUEST['order_id'])).'.png');
				
			}
			
		}
		
	} // class wpsg_mod_prepayment extends wpsg_mod_basic

?>