<?php

	/**
	 * Klasse für die Zahlungsart "Nachnahme"
	 * @author daniel
	 *
	 */
	class wpsg_mod_debitpayment extends wpsg_mod_basic 
	{
		
		var $lizenz = 2;
		var $id = 3;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Nachnahme', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlungsart "Nachnahme".', 'wpsg');
									
		} // public function __construct()
	
		public function install()
		{

			$this->shop->checkDefault('wpsg_mod_debitpayment_name', $this->name, false, true);
			$this->shop->checkDefault('wpsg_mod_debitpayment_hint', __('Der Rechnungsbetrag wird an den Zusteller bar gezahlt. Hierbei können zusätzliche Kosten entstehen.', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_debitpayment_rabgeb', '0');
			$this->shop->checkDefault('wpsg_mod_debitpayment_mwstland', '0');
			$this->shop->checkDefault('wpsg_mod_debitpayment_mwst', '0');
			
		} // public function install()
				
		public function settings_edit()
		{
			 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_debitpayment/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() 
		{
			
		    $this->shop->update_option('wpsg_mod_debitpayment_name', $_REQUEST['wpsg_mod_debitpayment_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_debitpayment_aktiv', $_REQUEST['wpsg_mod_debitpayment_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->addTranslationString('wpsg_mod_debitpayment_name', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_debitpayment_name']));
			
			$this->shop->update_option('wpsg_mod_debitpayment_hint', $_REQUEST['wpsg_mod_debitpayment_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_debitpayment_hint', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_debitpayment_hint']));
			
			$this->shop->update_option('wpsg_mod_debitpayment_rabgeb', $_REQUEST['wpsg_mod_debitpayment_rabgeb'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_debitpayment_mwstland', $_REQUEST['wpsg_mod_debitpayment_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_debitpayment_mwst', $_REQUEST['wpsg_mod_debitpayment_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
						
		} // public function settings_save()
		
		public function addPayment(&$arPayment) { 

			if (!is_admin() && $this->shop->get_option('wpsg_mod_debitpayment_aktiv') != '1') return;
			 
			$arPayment[$this->id] = array(
				'id' => $this->id,
				'name' => __($this->shop->get_option('wpsg_mod_debitpayment_name'), 'wpsg'),
				'hint' => __($this->shop->get_option('wpsg_mod_debitpayment_hint')),
				'price' => $this->shop->get_option('wpsg_mod_debitpayment_rabgeb'),
				'tax_key' => $this->shop->get_option('wpsg_mod_debitpayment_mwst'),
				'mwst_null' => $this->shop->get_option('wpsg_mod_debitpayment_mwstland')
			);
						
		} // public function addPayment(&$arPayment)
		 
	} // class wpsg_mod_debitpayment extends wpsg_mod_payment

?>