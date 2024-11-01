<?php

	/**
	 * Dieses Modul ermöglicht die Versandart "Versandkostenfrei"
	 */
	class wpsg_mod_freeshipping extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 500;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Versandkostenfrei', 'wpsg');
			$this->group = __('Versand', 'wpsg');
			$this->desc = __('Ermöglicht die Versandart "Versandkostenfrei".', 'wpsg');
									
		} // public function __construct()
		
		public function install()
		{

			if ($this->shop->get_option('wpsg_mod_freeshipping_bezeichnung') === false || $this->shop->get_option('wpsg_mod_freeshipping_bezeichnung') == '') 
			{
				
				$this->shop->update_option('wpsg_mod_freeshipping_bezeichnung', $this->name);
				$this->shop->addTranslationString('wpsg_mod_freeshipping_bezeichnung', $this->name);
				
			}
			
		} // public function install()
		
		public function settings_edit()
		{
			
			$this->render(WPSG_PATH_VIEW.'/mods/mod_freeshipping/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save()
		{
			
		    $this->shop->update_option('wpsg_mod_freeshipping_bezeichnung', $_REQUEST['wpsg_mod_freeshipping_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_freeshipping_aktiv', $_REQUEST['wpsg_mod_freeshipping_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_freeshipping_minvalue', $_REQUEST['wpsg_mod_freeshipping_minvalue'], false, false, WPSG_SANITIZE_FLOAT);
			
			$this->shop->addTranslationString('wpsg_mod_freeshipping_bezeichnung', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_freeshipping_bezeichnung']));
			
		} // public function settings_save()
		
		public function addShipping(&$arShipping, $va_active = false) 
		{ 
	 
			if (!is_admin() && $this->shop->get_option('wpsg_mod_freeshipping_aktiv') != '1') return;
			
			$arShipping[$this->id] = array(
				'active' => $this->shop->get_option('wpsg_mod_freeshipping_aktiv'),
				'id' => $this->id,
				'name' => $this->shop->get_option('wpsg_mod_freeshipping_bezeichnung'),
				'price' => 0, 
				'tax_key' => 0
			); 
				
		} // public function addShipping(&$arShipping)
		
		public function checkShippingAvailable(&$arShipping) 
		{
			
			if (!wpsg_isSizedArray($this->shop->arShipping) || !array_key_exists($this->id, $this->shop->arShipping)) return;
						
			$oCalculation = \wpsg\wpsg_calculation::getSessionCalculation();
			$arCalculation = $oCalculation->getCalculationArray();
			
			if ($this->shop->getBackendTaxview() === WPSG_NETTO) $sum = $arCalculation['sum']['productsum_netto'];
			else $sum = $arCalculation['sum']['productsum_brutto'];
			  				
			if ($sum < wpsg_tf($this->shop->get_option('wpsg_mod_freeshipping_minvalue'))) {
								
				unset($arShipping[$this->id]);
			
			} else {
				
				// Alle anderen Versandarten entfernen
				foreach ($arShipping as $k => $v) { if ($k != $this->id && $k != '130') unset($arShipping[$k]); }
				
			} 
			
		} // public function checkShippingAvailable(&$arShipping)
 	
	} // class wpsg_mod_freeshipping extends wpsg_mod_basic

?>