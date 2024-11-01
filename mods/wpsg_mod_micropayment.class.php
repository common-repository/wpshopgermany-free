<?php

	/**
	 * Klasse, die verschiedene Zahlungsarten über den micropayment.de Dienstleister ermöglicht
	 * Ist nur für die Lizenztypen Pro und Enterpreise zulässig
	 * @author Daschmi (05.07.2013)
	 */
	class wpsg_mod_micropayment extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 1750;
		var $hilfeURL = 'http://wpshopgermany.de/?p=3424';
		
		var $event_creditcard_url = 'http://billing.micropayment.de/creditcard/event/';
		var $event_directdebit_url = 'http://billing.micropayment.de/lastschrift/event/';
		var $event_ebank2pay_url = 'http://billing.micropayment.de/ebank2pay/event/';				
		var $event_prepayment_url = 'http://billing.micropayment.de/prepay/event/';
		var $event_call2pay_url = 'http://billing.micropayment.de/call2pay/event/';
		var $event_handypay_url = 'http://billing.micropayment.de/handypay/event/';
		
		var $dispatcher = false;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
				
			parent::__construct();
				
			$this->name = __('micropayment™', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlung über den Zahlungsdienstleister <a href="http://r132.micropayment.de">micropayment™</a>.', 'wpsg');
		 			
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_creditcard_url'))) $this->event_creditcard_url = $this->shop->get_option('wpsg_mod_micropayment_event_creditcard_url');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_directdebit_url'))) $this->event_directdebit_url = $this->shop->get_option('wpsg_mod_micropayment_event_directdebit_url');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_ebank2pay_url'))) $this->event_ebank2pay_url = $this->shop->get_option('wpsg_mod_micropayment_event_ebank2pay_url');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_prepayment_url'))) $this->event_prepayment_url = $this->shop->get_option('wpsg_mod_micropayment_event_prepayment_url');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_call2pay_url'))) $this->event_call2pay_url = $this->shop->get_option('wpsg_mod_micropayment_event_call2pay_url');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_event_handypay_url'))) $this->event_handypay_url = $this->shop->get_option('wpsg_mod_micropayment_event_handypay_url');
			
		} // public function __construct()
		
		public function install()
		{

			// Kundentabelle erweitern
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			$sql = "CREATE TABLE ".WPSG_TBL_ORDER." (
		   		wpsg_mod_micropayment_customerid VARCHAR(255) NOT NULL,
				wpsg_mod_micropayment_sessionid VARCHAR(255) NOT NULL,
				wpsg_mod_micropayment_transactionid VARCHAR(255) NOT NULL			
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
			// Kreditkarte
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcard_name', 'Kreditkarte micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcard_hint', __('Abrechnung per Kreditkarte', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcard_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcard_mwstland', '0', false, false);
						
			// Kreditkarte mit Reserivierung
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcardreservation_name', 'Kreditkarte micropayment™ (Reservierung)', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcardreservation_hint', __('Abrechnung per Kreditkarte (Reservierung)', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcardreservation_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_creditcardreservation_mwstland', '0', false, false);
			
			// Lastschrift
			$this->shop->checkDefault('wpsg_mod_micropayment_directdebit_name', 'Lastschrift micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_directdebit_hint', __('Abrechnung per Lastschrift', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_directdebit_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_directdebit_mwstland', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_directdebit_subject', 'O%order_id% - K%kunde_id%', false, true);
			
			// eBank2Pay
			$this->shop->checkDefault('wpsg_mod_micropayment_ebank2pay_name', 'Online Überweisung micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_ebank2pay_hint', __('Abrechnung per Online Banking', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_ebank2pay_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_ebank2pay_mwstland', '0', false, false);
						
			// Vorkasse
			$this->shop->checkDefault('wpsg_mod_micropayment_prepayment_name', 'Vorkasse micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_prepayment_hint', __('Abrechnung per Vorkasse', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_prepayment_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_prepayment_mwstland', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_prepayment_subject', 'O%order_id% - K%kunde_id%', false, true);
			
			// Call2Pay
			$this->shop->checkDefault('wpsg_mod_micropayment_call2pay_name', 'Call2Pay micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_call2pay_hint', __('Abrechnung per Anruf / Telefon', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_call2pay_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_call2pay_mwstland', '0', false, false);
						
			// HandyPay
			$this->shop->checkDefault('wpsg_mod_micropayment_handypay_name', 'HandyPay micropayment™', false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_handypay_hint', __('Abrechnung per SMS / TAN', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_micropayment_handypay_mwst', '0', false, false);
			$this->shop->checkDefault('wpsg_mod_micropayment_handypay_mwstland', '0', false, false);
						
		} // public function install()
		
		public function settings_edit()
		{
			
			$pages = get_pages();
			
			$arPages = array(
					'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			
			$this->shop->view['wpsg_mod_micropayment']['arPages'] = $arPages;
			
			$api_url = $this->shop->getURL(wpsg_ShopController::URL_BASKET);
				
			if (strpos($api_url, '?') === false)
			{
			
				$api_url .= '?wpsg_plugin=wpsg_mod_micropayment&confirm=micropayment';
			
			}
			else
			{
			
				$api_url .= '&wpsg_plugin=wpsg_mod_micropayment&confirm=micropayment';
			
			}
			
			$this->shop->view['wpsg_mod_micropayment']['apiURL'] = $api_url;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_micropayment/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save()
		{
					
			// Allgemein
		    $this->shop->update_option('wpsg_mod_micropayment_projectid', $_REQUEST['wpsg_mod_micropayment_projectid'], false, false, WPSG_SANITIZE_APIKEY);
		    $this->shop->update_option('wpsg_mod_micropayment_accountid', $_REQUEST['wpsg_mod_micropayment_accountid'], false, false, WPSG_SANITIZE_APIKEY);
		    $this->shop->update_option('wpsg_mod_micropayment_accesskey', $_REQUEST['wpsg_mod_micropayment_accesskey'], false, false, WPSG_SANITIZE_APIKEY);
		    $this->shop->createPage(__('Erfolgreiche Zahlung', 'wpsg'), 'wpsg_mod_micropayment_successPage', wpsg_sinput(WPSG_SANITIZE_INT, $_REQUEST['wpsg_mod_micropayment_successPage']));
			$this->shop->update_option('wpsg_mod_micropayment_paystart', $_REQUEST['wpsg_mod_micropayment_paystart'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			$this->shop->update_option('wpsg_mod_micropayment_account', $_REQUEST['wpsg_mod_micropayment_account'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_layout', $_REQUEST['wpsg_mod_micropayment_layout'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_bgcolor', $_REQUEST['wpsg_mod_micropayment_bgcolor'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_campaign', $_REQUEST['wpsg_mod_micropayment_campaign'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_sandbox', $_REQUEST['wpsg_mod_micropayment_sandbox'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_bggfx', $_REQUEST['wpsg_mod_micropayment_bggfx'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			// Kreditkarte
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_activ', $_REQUEST['wpsg_mod_micropayment_creditcard_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_name', $_REQUEST['wpsg_mod_micropayment_creditcard_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_creditcard_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_creditcard_name']));
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_hint', $_REQUEST['wpsg_mod_micropayment_creditcard_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_creditcard_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_creditcard_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_logo', $_REQUEST['wpsg_mod_micropayment_creditcard_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_gebuehr', $_REQUEST['wpsg_mod_micropayment_creditcard_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_mwst', $_REQUEST['wpsg_mod_micropayment_creditcard_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_creditcard_mwstland', $_REQUEST['wpsg_mod_micropayment_creditcard_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			// Kreditkarte (Reservierung)
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_activ', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_name', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_creditcardreservation_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_creditcardreservation_name']));
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_hint', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_creditcardreservation_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_creditcardreservation_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_logo', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_gebuehr', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_mwst', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_creditcardreservation_mwstland', $_REQUEST['wpsg_mod_micropayment_creditcardreservation_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
						
			// Lastschrift
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_activ', $_REQUEST['wpsg_mod_micropayment_directdebit_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_name', $_REQUEST['wpsg_mod_micropayment_directdebit_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_directdebit_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_directdebit_name']));
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_hint', $_REQUEST['wpsg_mod_micropayment_directdebit_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_directdebit_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_directdebit_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_logo', $_REQUEST['wpsg_mod_micropayment_directdebit_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_gebuehr', $_REQUEST['wpsg_mod_micropayment_directdebit_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_mwst', $_REQUEST['wpsg_mod_micropayment_directdebit_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_mwstland', $_REQUEST['wpsg_mod_micropayment_directdebit_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_directdebit_subject', $_REQUEST['wpsg_mod_micropayment_directdebit_subject'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			// eBank2Pay
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_activ', $_REQUEST['wpsg_mod_micropayment_ebank2pay_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_name', $_REQUEST['wpsg_mod_micropayment_ebank2pay_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_ebank2pay_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_ebank2pay_name']));
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_hint', $_REQUEST['wpsg_mod_micropayment_ebank2pay_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_ebank2pay_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_ebank2pay_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_logo', $_REQUEST['wpsg_mod_micropayment_ebank2pay_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_gebuehr', $_REQUEST['wpsg_mod_micropayment_ebank2pay_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_mwst', $_REQUEST['wpsg_mod_micropayment_ebank2pay_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_ebank2pay_mwstland', $_REQUEST['wpsg_mod_micropayment_ebank2pay_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
						
			// Vorkasse
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_activ', $_REQUEST['wpsg_mod_micropayment_prepayment_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_name', $_REQUEST['wpsg_mod_micropayment_prepayment_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_prepayment_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_prepayment_name']));
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_hint', $_REQUEST['wpsg_mod_micropayment_prepayment_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_prepayment_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_prepayment_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_logo', $_REQUEST['wpsg_mod_micropayment_prepayment_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_gebuehr', $_REQUEST['wpsg_mod_micropayment_prepayment_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_mwst', $_REQUEST['wpsg_mod_micropayment_prepayment_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_mwstland', $_REQUEST['wpsg_mod_micropayment_prepayment_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_prepayment_subject', $_REQUEST['wpsg_mod_micropayment_prepayment_subject'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			// Call2Pay
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_activ', $_REQUEST['wpsg_mod_micropayment_call2pay_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_name', $_REQUEST['wpsg_mod_micropayment_call2pay_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_call2pay_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_call2pay_name']));
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_hint', $_REQUEST['wpsg_mod_micropayment_call2pay_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_call2pay_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_call2pay_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_logo', $_REQUEST['wpsg_mod_micropayment_call2pay_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_gebuehr', $_REQUEST['wpsg_mod_micropayment_call2pay_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_mwst', $_REQUEST['wpsg_mod_micropayment_call2pay_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_call2pay_mwstland', $_REQUEST['wpsg_mod_micropayment_call2pay_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
						
			// HandyPay
			$this->shop->update_option('wpsg_mod_micropayment_handypay_activ', $_REQUEST['wpsg_mod_micropayment_handypay_activ'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_micropayment_handypay_name', $_REQUEST['wpsg_mod_micropayment_handypay_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_handypay_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_handypay_name']));
			$this->shop->update_option('wpsg_mod_micropayment_handypay_hint', $_REQUEST['wpsg_mod_micropayment_handypay_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_micropayment_handypay_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_micropayment_handypay_hint']));
			$this->shop->update_option('wpsg_mod_micropayment_handypay_logo', $_REQUEST['wpsg_mod_micropayment_handypay_logo'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_micropayment_handypay_gebuehr', $_REQUEST['wpsg_mod_micropayment_handypay_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_micropayment_handypay_mwst', $_REQUEST['wpsg_mod_micropayment_handypay_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_micropayment_handypay_mwstland', $_REQUEST['wpsg_mod_micropayment_handypay_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
						
			$this->getBillingURLs();
			
		} // public function settings_save()

        public function wpsg_deinstall_sites() {

            wp_delete_post($this->shop->get_option('wpsg_mod_micropayment_successPage'));

        } // public function wpsg_deinstall_sites()

		public function addPayment(&$arPayment) {
			
			// Neue Zustände hinzufügen
			$this->shop->arStatus['700'] = __('Zahlung reserviert', 'wpsg');
			$this->shop->arStatus['701'] = __('Reservierung eingelöst', 'wpsg');
			
			// Kreditkarte
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_creditcard_activ') == '1') {
				 
				$arPayment[$this->id.'_1'] = array(
					'id' => $this->id.'_1',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_creditcard_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_creditcard_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_creditcard_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_creditcard_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_creditcard_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_creditcard_logo')))?$this->shop->get_option('wpsg_mod_micropayment_creditcard_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
						
			}
			
			// Kreditkartenreservierung
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_activ')) {
				
				$arPayment[$this->id.'_7'] = array(
					'id' => $this->id.'_7',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_logo')))?$this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
								
			}
			
			// Lastschrift
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_directdebit_activ') == '1') {

				$arPayment[$this->id.'_2'] = array(
					'id' => $this->id.'_2',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_directdebit_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_directdebit_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_directdebit_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_directdebit_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_directdebit_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_directdebit_logo')))?$this->shop->get_option('wpsg_mod_micropayment_directdebit_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
					 
			}
			
			// eBank2Pay
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_ebank2pay_activ') == '1') {

				$arPayment[$this->id.'_3'] = array(
					'id' => $this->id.'_3',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_ebank2pay_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_ebank2pay_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_ebank2pay_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_ebank2pay_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_ebank2pay_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_ebank2pay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_ebank2pay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
				 			
			}
			
			// Vorkasse
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_prepayment_activ') == '1') {
				
				$arPayment[$this->id.'_4'] = array(
					'id' => $this->id.'_4',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_prepayment_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_prepayment_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_prepayment_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_prepayment_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_prepayment_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_prepayment_logo')))?$this->shop->get_option('wpsg_mod_micropayment_prepayment_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
			
			}
			
			// Call2Pay
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_call2pay_activ') == '1') {
				 	
				$arPayment[$this->id.'_5'] = array(
					'id' => $this->id.'_5',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_call2pay_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_call2pay_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_call2pay_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_call2pay_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_call2pay_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_call2pay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_call2pay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
					 			
			}
			
			// HandyPay
			if (is_admin() || $this->shop->get_option('wpsg_mod_micropayment_handypay_activ') == '1') {
					
				
				$arPayment[$this->id.'_6'] = array(
					'id' => $this->id.'_6',
					'name' => __($this->shop->get_option('wpsg_mod_micropayment_handypay_name'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_micropayment_handypay_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_micropayment_handypay_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_micropayment_handypay_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_micropayment_handypay_hint')),
					'logo' => ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_handypay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_handypay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/logo_100x25.png'))
				);
					 
			}
				
		} // public function addPayment(&$arPayment)
		 
		public function order_done(&$order_id, &$done_view)
		{
			
			$oOrder = wpsg_order::getInstance($order_id);
			
			// Bestellungen mit 0 geben nix aus
			if ($oOrder->getToPay() <= 0) return;
				
			if (preg_match('/^'.$this->id.'_\d+$/', $this->shop->view['basket']['checkout']['payment']))
			{

				$payment = $this->shop->view['basket']['checkout']['payment'];
				
				switch ($payment)
				{
										
					case $this->id.'_1': // Kreditkarte
						
						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_creditcard_logo')))?$this->shop->get_option('wpsg_mod_micropayment_creditcard_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/creditcard.png'));						
						break;
						
					case $this->id.'_7': // Kreditkarte mit Reservierung
						
						//$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_logo')))?$this->shop->get_option('wpsg_mod_micropayment_creditcardreservation_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/creditcard.png'));
						$logo = false;
						
						break;
						
					case $this->id.'_2': // Lastschrift

						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_directdebit_logo')))?$this->shop->get_option('wpsg_mod_micropayment_directdebit_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/directdebit.png'));
						break;
						
					case $this->id.'_3': // eBankPay

						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_ebank2pay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_ebank2pay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/bank2pay.png'));
						break;
						
					case $this->id.'_4': // Vorkasse

						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_prepayment_logo')))?$this->shop->get_option('wpsg_mod_micropayment_prepayment_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/prepayment.png'));
						break;
						
					case $this->id.'_5': // Call2Pay

						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_call2pay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_call2pay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/call2pay.png'));
						break;
						
					case $this->id.'_6': // HandyPay
						  
						$logo = ((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_handypay_logo')))?$this->shop->get_option('wpsg_mod_micropayment_handypay_logo'):$this->shop->getRessourceURL('mods/mod_micropayment/gfx/handypay.png'));
						break;
						
					default: throw new \wpsg\Exception(__('Im Basket Array war eine Micropayment Zahlungsart die nicht definiert ist (Innerhalb calcPayment)', 'wpsg'));
					
				}
				
				$this->shop->view['wpsg_mod_micropayment']['logo'] = $logo;
				
				if ($payment != $this->id.'_7')
				{
				
					$this->shop->view['wpsg_mod_micropayment']['payLink'] = $this->getPayLink($order_id);
									
				}
				else
				{
					
					$this->shop->view['wpsg_mod_micropayment']['payLink'] = false;
						
				}
					
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_micropayment/order_done.phtml');
				
			}
		
		} // public function order_done(&$order_id)
		 
		public function mail_payment()
		{
		
			if (!preg_match('/^'.$this->id.'_\d+$/', $this->shop->view['basket']['checkout']['payment'])) return;
			
			if ($this->shop->htmlMail === true)
			{
				
				echo '<a href="'.$this->shop->getDoneURL($this->shop->view['o_id']).'">'.__('Zahlungslink', 'wpsg').'</a>'.__(', um die Zahlung durchzuführen', 'wpsg');						
				
			}
			else
			{
			
				echo wpsg_pad_right(__('Zahlungslink', 'wpsg').':', 35).$this->shop->getDoneURL($this->shop->view['o_id']);
				
			}
				
		} // public function mail_payment()
		
		public function template_redirect() 
		{
			
			if (wpsg_getStr($_REQUEST['wpsg_plugin']) == 'wpsg_mod_micropayment' && $_REQUEST['module_action'] == 'pay')
			{

				$_REQUEST['order_id'] = wpsg_sinput("key", $_REQUEST['order_id']);

				$form_data = null;
				parse_str(wpsg_sinput("text_field", $_REQUEST['form_data']), $form_data);
				
				if (!wpsg_isSizedString($form_data['number'])) die(__('Bitte die Kreditkartennummer angeben.', 'wpsg'));
				if (!wpsg_isSizedString($form_data['cvc2'])) die(__('Bitte die Prüfziffer der Kreditkarte angeben.', 'wpsg'));
								
				$oOrder = $this->shop->cache->loadOrderObject($_REQUEST['order_id']);
				$this->api_init();
				
				$api_customer_id = null;
				
				if (!wpsg_isSizedString($oOrder->wpsg_mod_micropayment_customerid))
				{
										
					$api_customer_id = $this->api_customerCreate($oOrder);
					$oOrder->wpsg_mod_micropayment_customerid = $api_customer_id;
					
					$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
						'wpsg_mod_micropayment_customerid' => wpsg_q($api_customer_id)
					), " `id` = '".wpsg_q($oOrder->id)."' ");
										
				}
				else
				{
					
					$api_customer_id = $oOrder->wpsg_mod_micropayment_customerid;
					
				}
								 
				try 
				{
				
					// Kreditkartendaten übermitteln
					$result = $this->api_creditcardDataSet($oOrder, $api_customer_id, $form_data['number'], $form_data['expiryYear'], $form_data['expiryMonth']);
					
					// Session erstellen
					$result = $this->api_sessionCreate($oOrder, $api_customer_id);

					if (wpsg_isSizedString($result['sessionId']))
					{
						
						$session_id = $result['sessionId'];
						$result = $this->api_transactionAuthorization($oOrder, $result['sessionId'], $form_data['cvc2']);
						
						if (wpsg_isSizedString($result['transactionId']) && $result['transactionStatus'] == 'SUCCESS')
						{
							
							// Authorisierung erfolgreich
							$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
								'wpsg_mod_micropayment_sessionid' => wpsg_q($session_id),
								'wpsg_mod_micropayment_transactionid' => wpsg_q($result['transactionId'])								
							), " `id` = '".wpsg_q($oOrder->id)."' ");
							
							// Status setzen
							$this->shop->setOrderStatus($oOrder->id, '700', false);
							
							$oOrder->log(
								__('Erfolgreiche Micropayment Authorisierung', 'wpsg'),
								print_r($result, 1)
							);
							
						}
						else
						{
														
							die(__('Authorisierung konnte nicht durchgeführt werden.', 'wpsg'));
							
						}
						
					}
					else
					{
			 
						die(__('Session für Zahlung konnte nicht aufgebaut werden.', 'wpsg'));
						
					}
					
				}
				catch (Exception $e)
				{

					wpsg_debug($e);
					die(__('Die Kreditkartendaten wurden nicht akzeptiert.', 'wpsg'));
					
				}
				
				die("1");
				
			}
			else if ($_REQUEST['wpsg_plugin'] == 'wpsg_mod_micropayment' && $_REQUEST['confirm'] == 'micropayment')
			{

				$_REQUEST['title'] = wpsg_sinput("text_field", $_REQUEST['title']);
				$_REQUEST['amount'] = wpsg_sinput("key", $_REQUEST['amount']);

				$title = explode('|', $_REQUEST['title']);
				$order_id = $title[0];
				$token = base64_decode($title[1]);
				
				$order_data = $this->shop->cache->loadOrder($order_id);
				
				if ($order_data['id'] != $order_id || $order_id <= 0) throw new \wpsg\Exception(__('Ungültiger Titel oder Bestellung nicht gefunden bei MP Request', 'wpsg'));
				
				$token_check = md5($this->shop->get_option('wpsg_mod_micropayment_accesskey').$order_data['cdate']);
				
				if ($token_check != $token) throw new \wpsg\Exception(__('Ungültiger Sicherheitstoken bei MP Request', 'wpsg'));
				
				$this->db->ImportQuery(WPSG_TBL_OL, array(
					"title" => __("Micropayment REQUEST", "wpsg"),
					"cdate" => "NOW()",
					"o_id" => wpsg_q($order_id),
					"mailtext" => wpsg_q(wpsg_hspc(print_r($_REQUEST, 1)))
				));
				
				switch ($_REQUEST['function'])
				{
					
					case 'billing':
						
						if ($this->shop->setPayMent($order_id, $_REQUEST['amount'] / 100))
						{
								
							$this->shop->setOrderStatus($order_id, 100, true);
							
						}
						
						break; 
					
				}
				
				$url = get_permalink($this->shop->get_option('wpsg_mod_micropayment_successPage'));
				die("status=ok\nurl=".$url."\ntarget=_top\nforward=1");
				
			}
				
		} // public function template_redirect()			
		
		public function admin_debugInfo() 
		{ 
			
			echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_micropayment/debugInfo.phtml', true);			
			
		} // public function admin_debugInfo()
		
		public function setOrderStatus($order_id, $status_id, $inform) 
		{ 
			
			if (!in_array($status_id, array('500', '701'))) return;
			
			$oOrder = $this->shop->cache->loadOrderObject($order_id);
			$bError = false; $ol_text = "";
			
			$this->api_init();
			
			if ($status_id == '701')
			{
												
				// Wechsel auf "Reservierung eingelöst"
				try 
				{
					
					$result = $this->api_transactionCapture($oOrder);
									
					if ($result['transactionStatus'] == "SUCCESS" && $result['sessionStatus'] == "SUCCESS")
					{
						
						$oOrder->log(
							__('Micropayment: Zahlung wurde gebucht', 'wpsg'),
							print_r($result, 1)								
						);
						
					}
					else
					{
						
						$bError = true;
						$ol_text = print_r($result, 1);
												
					}
					
				} 
				catch (Exception $e)
				{
					
					$bError = true;
					$ol_text = $e->getMessage();
					
				}

				if ($bError === true)
				{
					
					// Sonst ist die Meldung mit dem erfolgreichem Statuswechsel sichtbar
					unset($_SESSION['wpsg']['backendMessage']);
					
					// Statuswechsel abrechen					
					$this->shop->addBackendError(
						wpsg_translate(
							__('Reservierung konnte nicht gebucht werden. Status auf #1# zurückgesetzt.', 'wpsg'),
							$this->shop->arStatus[$this->shop->view['order']['status']]
						)
					);
						
					$oOrder->log(
						wpsg_translate(
							__('Micropayment: Zahlung konnte nicht freigegeben werden / Status auf #1# zurückgesetzt.', 'wpsg'),
							$this->shop->arStatus[$this->shop->view['order']['status']]
						),
						$ol_text
					);
					
					$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
						'status' => wpsg_q($this->shop->view['order']['status'])
					), " `id` = '".wpsg_q($oOrder->id)."' ");
						 
				}
				
			}
			else if ($status_id == '500' && $this->shop->view['order']['status'] == '700')
			{
				
				// Storniert
				
				try 
				{
				
					$result = $this->api_transactionReversal($oOrder);
					
					if ($result['sessionStatus'] === "SUCCESS" && $result['transactionStatus'] === "SUCCESS")
					{
						
						$oOrder->log(
							__('Micropayment: Zahlungsreservierung wurde erfolgreich storniert', 'wpsg'),
							print_r($result, 1)								
						);
										
					}
					else
					{
						
						$bError = true;
						$ol_text = print_r($result, 1);
						
					}
					
				} 
				catch (Exception $e)
				{
					
					$bError = true;
					$ol_text = $e->getMessage();
					
				}
				
				if ($bError === true)
				{
					
					// Kein zurücksetzen, ich denke es macht Sinn das der Status denoch auf storniert geht 
					$this->shop->addBackendError(__('Zahlungsreservierung konnte nicht aufgehoben werden', 'wpsg'));
					
					$oOrder->log(
						__('Micropayment: Zahlungsreservierung konnte nicht aufgehoben werden', 'wpsg'),
						$ol_text
					);
					
				}
				 
			}
			
			
		} // public function setOrderStatus($order_id, $status_id, $inform)
		
		/** Modulfunktionen */
		
		private function api_init()
		{
			
			require_once WPSG_PATH_MOD.'mod_micropayment/mcp-serviceclient_1_17/lib/init.php';
			require_once WPSG_PATH_MOD.'mod_micropayment/mcp-serviceclient_1_17/services/IMcpCreditcardService_v1_2.php';
			require_once MCP__SERVICELIB_DISPATCHER.'TNvpServiceDispatcher.php';
			
			$this->dispatcher = new TNvpServiceDispatcher('IMcpCreditcardService_v1_2', 'https://sipg.micropayment.de/public/creditcard/v1.2/nvp/');
			
		} // private function api_init()
		
		private function api_customerCreate(&$oOrder)
		{
			  			
			$result = $this->dispatcher->customerCreate(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'),
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'),
				null,
				null, //  Liste mit freien Parametern, die dem Kunden zugeordnet werden
				$oOrder->getInvoiceFirstName(),
				$oOrder->getInvoiceName(), 
				$oOrder->getCustomer()->getEMail(), 
				'de-DE' // 	Sprache & Land des Kunden | gültige Beispielwerte sind 'de', 'de-DE', 'en-US'
			); 
			
			return $result;
	 
		} // private function api_customerCreate($order_id)
		
		private function api_sessionCreate(&$oOrder, $api_customer_id)
		{ 
			  
			$_SERVER['REMOTE_ADDR'] = '134.97.73.195';
			$result = $this->dispatcher->sessionCreate(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'), // AccessKey aus dem Controlcenter
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'), // aktiviert Testumgebung
				$api_customer_id, // ID des Kunden
				null, // eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt [max. 40 Zeichen]
				$this->shop->get_option('wpsg_mod_micropayment_projectid'), // das Projektkürzel für den Vorgang
				((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_campaign')))?$this->shop->get_option('wpsg_mod_micropayment_campaign'):null), // ein Kampagnenkürzel des Projektbetreibers
				((wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_account')))?$this->shop->get_option('wpsg_mod_micropayment_account'):null), // Account des beteiligten Webmasters sonst eigener - setzt eine Aktivierung der Webmasterfähigkeit des Projekts vorraus - Hinweis: Webmasterfähigkeit steht momentan nicht zur Verfügung
				null, // ein Kampagnenkürzel des Webmasters
				wpsg_round($oOrder->getAmount(), 2) * 100, // abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
				'EUR', // Währung
				null, // Bezeichnung der zu kaufenden Sache - Verwendung in Falle einer auftretenden Benachrichtigung wird dieser Wert als Produktidentifizierung mit geschickt, wird kein Wert übergeben, wird Der aus der Konfiguration verwendet
				null, // Bezeichnung der zu kaufenden Sache - Verwendung beim Mailversand, sollten Sie Diesen wünschen
				$_SERVER['REMOTE_ADDR'], // IPv4 des Benutzers
				null, // Liste mit freien Parametern, die dem Vorgang zugeordnet werden
				false // sendMail ?
			);
			
			return $result;
			
		}
		
		public function api_transactionAuthorization(&$oOrder, $session_id, $cvc2)
		{

			$result = $this->dispatcher->transactionAuthorization(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'), // AccessKey aus dem Controlcenter
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'), // aktiviert Testumgebung
				$session_id,
				$cvc2					
			);
			
			return $result;
			
		} // public function api_transactionAuthorization(&$oOrder, $session_id, $cvc2)
		
		public function api_transactionReversal(&$oOrder)
		{
			
			$result = $this->dispatcher->transactionReversal(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'), // AccessKey aus dem Controlcenter
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'), // aktiviert Testumgebung	
				$oOrder->wpsg_mod_micropayment_sessionid,
				$oOrder->wpsg_mod_micropayment_transactionid
			);
			
			return $result;
			
		} // public function api_transactionReversal(&$oOrder)
		
		public function api_creditcardDataSet(&$oOrder, $api_customer_id, $number, $expiryYear, $expiryMonth)
		{
			 				
			$result = $this->dispatcher->creditcardDataSet(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'), // AccessKey aus dem Controlcenter
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'), // aktiviert Testumgebung
				$api_customer_id, // ID des Kunden
				$number,
				$expiryYear,
				$expiryMonth
			); 
			
			return $result;
			
		} // public function creditcardDataSet($order_id, $api_customer_id, $number, $expiryYear, $expiryMonth)
		
		public function api_transactionCapture(&$oOrder)
		{
			
			$result = $this->dispatcher->transactionCapture(
				$this->shop->get_option('wpsg_mod_micropayment_accesskey'), // AccessKey aus dem Controlcenter
				(($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')?'1':'0'), // aktiviert Testumgebung
				$oOrder->wpsg_mod_micropayment_sessionid,
				$oOrder->wpsg_mod_micropayment_transactionid
			);
			
			return $result;
			
		} // public function api_transactionCapture(&$oOrder)
		
		/**
		 * Ruft die URLs von dem Micropayment Billing Info auf
		 */
		public function getBillingURLs()
		{
			
			$url = 'https://webservices.micropayment.de/public/info/index.php?'.http_build_query(
				array(
					'action' => 'GenerateUrl',
					'format' => 'json',
					'account_id' => '27937'
				),
				null,
				'&'		
			);
			
			$json_data = json_decode($this->shop->get_url_content($url));
					 
			if (wpsg_isSizedString($json_data->billing))
			{
				
				$this->shop->update_option('wpsg_mod_micropayment_event_creditcard_url', 'http://'.$json_data->billing.'/creditcard/event/');
				$this->shop->update_option('wpsg_mod_micropayment_event_directdebit_url', 'http://'.$json_data->billing.'/lastschrift/event/');
				$this->shop->update_option('wpsg_mod_micropayment_event_ebank2pay_url', 'http://'.$json_data->billing.'/ebank2pay/event/');
				$this->shop->update_option('wpsg_mod_micropayment_event_prepayment_url', 'http://'.$json_data->billing.'/prepay/event/');
				$this->shop->update_option('wpsg_mod_micropayment_event_call2pay_url', 'http://'.$json_data->billing.'/call2pay/event/');
				$this->shop->update_option('wpsg_mod_micropayment_event_handypay_url', 'http://'.$json_data->billing.'/handypay/event/');
				 
			}
			
		} // public function getBillingURLs()
		
		/**
		 * Gibt den Link zum bezahlen einer Bestellung anhand der BestellID zurück
		 * @param Int $order_id ID der Bestellung 
		 */
		public function getPayLink($order_id)
		{
			
			$order_data = $this->shop->cache->loadOrder($order_id);
			 
			$oOrder = wpsg_order::getInstance($order_id);
			
			if (!preg_match('/^'.$this->id.'_\d+$/', $order_data['type_payment'])) throw new \wpsg\Exception(__('Es wurde versucht einen Zahlungslink für eine Bestellung anzufragen, die nicht mit Micropayment bezahlt wurde', 'wpsg'));
			
			$subPayTyp = preg_replace('/^\d+_/', '', $order_data['type_payment']);
			
			$arParam = array();
			$arParam['project'] = $this->shop->get_option('wpsg_mod_micropayment_projectid');
			
			$arParam['title'] = $order_id.'|'.base64_encode(md5($this->shop->get_option('wpsg_mod_micropayment_accesskey').$order_data['cdate']));
			
			switch ($subPayTyp)
			{
									
				case '1': // Kreditkarte
					
					$url = $this->event_creditcard_url;
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					$arParam['currency'] = 'EUR';
					
					break;
					
				case '7': // Kreditkarte mit Reservierung
					
					$url = $this->event_creditcard_url;
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					$arParam['currency'] = 'EUR';
							
					break;
					
				case '2': // Lastschrift
					
					$url = $this->event_directdebit_url;
					$arParam['paytext'] = $this->shop->replaceUniversalPlatzhalter(__($this->shop->get_option('wpsg_mod_micropayment_directdebit_subject'), 'wpsg'), $order_id);
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					$arParam['userid'] = $order_data['k_id'];
					
					break;
					
				case '3': // eBankPay
					
					$url = $this->event_ebank2pay_url;
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					
					break;
					
				case '4': // Vorkasse
					
					$url = $this->event_prepayment_url;
					$arParam['paytext'] = $this->shop->replaceUniversalPlatzhalter(__($this->shop->get_option('wpsg_mod_micropayment_prepayment_subject'), 'wpsg'), $order_id);
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					
					break;
					
				case '5': // Call2Pay
				
					$url = $this->event_call2pay_url;
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					
					break;
					
				case '6': // HandyPay
							
					$url = $this->event_handypay_url; 
					$arParam['amount'] = wpsg_round($oOrder->getToPay(), 2) * 100;
					
					break;
					
				default: throw new \wpsg\Exception(__('Es wurde keine gültige Micropayment Zahlungsart beim generieren des Bezahllinks gefunden', 'wpsg'));
				
			}
			
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_account'))) $arParam['account'] = $this->shop->get_option('wpsg_mod_micropayment_account');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_layout'))) $arParam['theme'] = $this->shop->get_option('wpsg_mod_micropayment_layout');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_bgcolor'))) $arParam['bgcolor'] = $this->shop->get_option('wpsg_mod_micropayment_bgcolor');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_campaign'))) $arParam['projectcampaign'] = $this->shop->get_option('wpsg_mod_micropayment_campaign');
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_micropayment_bggfx'))) $arParam['bggfx'] = $this->shop->get_option('wpsg_mod_micropayment_bggfx'); 
			
			$params = http_build_query($arParam, null, '&');
			
			$seal = md5($params.$this->shop->get_option('wpsg_mod_micropayment_accesskey'));
			$params .= '&seal='.$seal;

			if ($this->shop->get_option('wpsg_mod_micropayment_sandbox') == '1')
			{
				
				$params .= '&testmode=1';
				
			}
			
			return $url.'?'.$params;
			
		} // public function getPayLink($order_id)
		
	} // class wpsg_mod_micropayment extends wpsg_mod_basic

