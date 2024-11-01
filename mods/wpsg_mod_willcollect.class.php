<?php

	/**
	 * Modul für die Zahlungsart Selbstabholung / Barzahlung bei Abholung
	 * @author daniel
	 */
	class wpsg_mod_willcollect extends wpsg_mod_basic
	{
		
		var $lizenz = 2;
		var $id = 130;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{

			parent::__construct();
			
			$this->name = __('Selbstabholung', 'wpsg');
			$this->group = __('Versand', 'wpsg');
			$this->desc = __('Ermöglicht die Versandart "Selbstabholung" mit der Zahlweise "Barzahlung bei Abholung".', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
			
			$this->shop->checkDefault('wpsg_mod_willcollect_bezeichnung', __('Selbstabholung', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_willcollect_aktiv', '1');
			$this->shop->checkDefault('wpsg_mod_willcollect_hint', __('Vereinbaren Sie mit uns nach der Bestellung einen Termin an dem Sie die Ware abholen.', 'wpsg'));
			$this->shop->checkDefault('wpsg_mod_willcollect_paymentneed', '0');
			
			$this->shop->checkDefault('wpsg_mod_willcollect_adress', '');
			$this->shop->checkDefault('wpsg_mod_willcollect_street', '');
			$this->shop->checkDefault('wpsg_mod_willcollect_plzort', '');
			
			$this->shop->checkDefault('wpsg_mod_willcollect_payment', '1');
			$this->shop->checkDefault('wpsg_mod_willcollect_payment_bezeichnung', __('Barzahlung', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_willcollect_payment_hint', __('Sie bezahlen die Ware bei Abholung. Ist nur mit der Versandart "Selbstabholung" möglich.', 'wpsg'), false, true);

			$this->shop->checkDefault('wpsg_mod_willcollect_gebuehr', '0');
			$this->shop->checkDefault('wpsg_mod_willcollect_payment_gebuehr', '0');
			$this->shop->checkDefault('wpsg_mod_willcollect_mwst', '0');
			$this->shop->checkDefault('wpsg_mod_willcollect_mwstland', '0');
			
		} // public function install()
		
		public function settings_edit()
		{
						
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_willcollect/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_willcollect_bezeichnung', $_REQUEST['wpsg_mod_willcollect_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_willcollect_aktiv', $_REQUEST['wpsg_mod_willcollect_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_willcollect_hint', $_REQUEST['wpsg_mod_willcollect_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_willcollect_paymentneed', $_REQUEST['wpsg_mod_willcollect_paymentneed'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_willcollect_dontMerge', $_REQUEST['wpsg_mod_willcollect_dontMerge'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			$this->shop->update_option('wpsg_mod_willcollect_adress', $_REQUEST['wpsg_mod_willcollect_adress'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_willcollect_street', $_REQUEST['wpsg_mod_willcollect_street'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_willcollect_plzort', $_REQUEST['wpsg_mod_willcollect_plzort'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->update_option('wpsg_mod_willcollect_payment', $_REQUEST['wpsg_mod_willcollect_payment'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_willcollect_payment_bezeichnung', $_REQUEST['wpsg_mod_willcollect_payment_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_willcollect_payment_hint', $_REQUEST['wpsg_mod_willcollect_payment_hint'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->update_option('wpsg_mod_willcollect_gebuehr', $_REQUEST['wpsg_mod_willcollect_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_willcollect_payment_gebuehr', $_REQUEST['wpsg_mod_willcollect_payment_gebuehr'], false, false, WPSG_SANITIZE_TEXTFIELD);

			$this->shop->update_option('wpsg_mod_willcollect_mwst', $_REQUEST['wpsg_mod_willcollect_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			$this->shop->update_option('wpsg_mod_willcollect_mwstland', $_REQUEST['wpsg_mod_willcollect_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			
		} // public function settings_save()
		
		public function produkt_save_before(&$produkt_data)  
		{
			
			// Selbstabholung erfordert Barzahlung?
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_willcollect_paymentneed')))
			{
				
				$arAllowedPayments = wpsg_trim(explode(',', $produkt_data['allowedpayments']));
				$arAllowedShipping = wpsg_trim(explode(',', $produkt_data['allowedshipping']));
				
				// Selbstabholung drin aber keine Barzahlung
				if (wpsg_isSizedArray($arAllowedPayments) && wpsg_isSizedArray($arAllowedShipping))
				{
			 
					if (in_array($this->id, $arAllowedShipping) && !in_array($this->id, $arAllowedPayments))
					{
					 
						$this->shop->addBackendError(
							wpsg_translate(
								__('Die Versandart "#1#" erfordert die Zahlungsart "#2#".', 'wpsg'),
								$this->shop->get_option('wpsg_mod_willcollect_bezeichnung'),
								$this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung')
							)
						);
						
						$arAllowedPayments[] = $this->id;
						
						$produkt_data['allowedpayments'] = implode(',', $arAllowedPayments);
						$produkt_data['allowedshipping'] = implode(',', $arAllowedShipping);
					
					}
					else if (in_array($this->id, $arAllowedPayments) && !in_array($this->id, $arAllowedShipping))
					{
						
						$this->shop->addBackendError(
							wpsg_translate(
								__('Die Zahlungsart "#1#" erfordert die Versandart "#2#".', 'wpsg'),
								$this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung'),
								$this->shop->get_option('wpsg_mod_willcollect_bezeichnung')
							)
						);
						
						$arAllowedShipping[] = $this->id;
						
						$produkt_data['allowedpayments'] = implode(',', $arAllowedPayments);
						$produkt_data['allowedshipping'] = implode(',', $arAllowedShipping);
						
					}
					 
				}
				
			}
					
		}
		
		public function addPayment(&$arPayment) { 

			if (is_admin() || $this->shop->get_option('wpsg_mod_willcollect_payment') == '1') {
			
				$arPayment[$this->id] = array(
					'id' => $this->id,
					'name' => __($this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung'), 'wpsg'),
					'price' => $this->shop->get_option('wpsg_mod_willcollect_payment_gebuehr'),
					'tax_key' => $this->shop->get_option('wpsg_mod_willcollect_mwst'),
					'mwst_null' => $this->shop->get_option('wpsg_mod_willcollect_mwstland'),
					'hint' => __($this->shop->get_option('wpsg_mod_willcollect_payment_hint'), 'wpsg')
				);
				 				
			}
			
		} // public function addPayment(&$arPayment)
 
		public function addShipping(&$arShipping, $va_active = false) {
			
			if (!is_admin() && $this->shop->get_option('wpsg_mod_willcollect_aktiv') != '1') return;
			
			$arShipping[$this->id] = array(
				'id' => $this->id,
				'active' => $this->shop->get_option('wpsg_mod_willcollect_aktiv'),
				'name' => __($this->shop->get_option('wpsg_mod_willcollect_bezeichnung'), 'wpsg'),
				'price' => $this->shop->get_option('wpsg_mod_willcollect_gebuehr'),
				'tax_key' => $this->shop->get_option('wpsg_mod_willcollect_mwst'),
				'mwst_null' => $this->shop->get_option('wpsg_mod_willcollect_mwstland'),
				'hint' => __($this->shop->get_option('wpsg_mod_willcollect_hint'), 'wpsg')
			);
			 			
		} // public function addShipping(&$arShipping)
		 		
		/**
		 * Fehlermeldung wird in der setBasketData geschrieben,
		 * umleitung erfolgt aber schon hier, damit man aus dem Warenkorb nicht rauskommt
		 */
		public function checkBasket(&$bError)
		{
			
			if ($this->shop->basket->arCheckout['payment'] == $this->id)
			{
				
				// Zahlungsart Barzahlung wurde gewählt => Dann muss die Versandart "Selbstabholung" sein
				if ($this->shop->basket->arCheckout['shipping'] != $this->id) { $bError = false; }
				
			}
			
			if ($this->shop->basket->arCheckout['shipping'] == $this->id)
			{
				
				if ($this->shop->basket->arCheckout['payment'] != $this->id && $this->shop->get_option('wpsg_mod_willcollect_paymentneed') == '1') { $bError = false; }
				
			}
			
		}
		
		public function checkCheckout(&$state, &$error, &$arCheckout) 
		{ 
			
			if ($state >= 2)
			{
				
				if ($arCheckout['payment'] == $this->id)
				{
					
					// Zahlungsart Barzahlung wurde gewählt => Dann muss die Versandart "Selbstabholung" sein
					if ($arCheckout['shipping'] != $this->id) { $this->shop->addFrontendError(wpsg_translate(
						__('Die Zahlungsart "#1#" erfordert die Versandart "#2#".', 'wpsg'),
						__($this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung'), 'wpsg'),
						__($this->shop->get_option('wpsg_mod_willcollect_bezeichnung'), 'wpsg')
					)); $error = true; }
					
				}
				
				if ($arCheckout['shipping'] == $this->id)
				{
					
					if ($arCheckout['payment'] != $this->id && $this->shop->get_option('wpsg_mod_willcollect_paymentneed') == '1') { $this->shop->addFrontendError(wpsg_translate(
						__('Die Versandart "#1#" erfordert die Zahlungsart "#2#".', 'wpsg'),
						__($this->shop->get_option('wpsg_mod_willcollect_bezeichnung'), 'wpsg'),
						__($this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung'), 'wpsg')
					)); $error = true; }
					
				}
				
			}
			
		} // public function checkCheckout(&$state, &$error, &$arCheckout)

		public function setBasketData() 
		{ 
			
			// Selbstabholung erfordert Barzahlung?
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_willcollect_paymentneed')))
			{

				// Wechsel auf Selbstabholung
				if (wpsg_isSizedString($_REQUEST['set_shipping'], strval($this->id)))
				{
					
					// Zahlungsart ist nicht Barzahlung
					if (!wpsg_isSizedString($_REQUEST['set_payment'], strval($this->id)))
					{
						
						$this->shop->addFrontendError(
							wpsg_translate(
								__('Die Versandart "#1#" erfordert die Zahlungsart "#2#".', 'wpsg'),
								$this->shop->get_option('wpsg_mod_willcollect_bezeichnung'),
								$this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung')
							)
						);
						
						if (!array_key_exists($_SESSION['wpsg']['checkout']['payment'], $this->shop->arPayment)) $_SESSION['wpsg']['checkout']['payment'] = $this->id;
						else unset($_SESSION['wpsg']['checkout']['payment']);
												
						return;
						
					}
										
				}
				
				// Wechsel auf ungleich Barzahlung bei eingestellter Selbstabholung
				if (wpsg_isSizedString($_REQUEST['set_payment'], strval($this->id)) && !wpsg_isSizedString($_REQUEST['set_shipping'], strval($this->id)))
				{
				
					if (!array_key_exists($_SESSION['wpsg']['checkout']['shipping'], $this->shop->arShipping)) $_SESSION['wpsg']['checkout']['shipping'] = $this->id;
					else unset($_SESSION['wpsg']['checkout']['shipping']);					
					 
					$this->shop->addFrontendError(
						wpsg_translate(
							__('Die Zahlungsart "#1#" erfordert die Versandart "#2#".', 'wpsg'),
							$this->shop->get_option('wpsg_mod_willcollect_payment_bezeichnung'),
							$this->shop->get_option('wpsg_mod_willcollect_bezeichnung')
						)
					);
					
				}
				
			}
		 
		} // public function setBasketData()
		
		public function mail_shipping() 
		{ 

			if ($this->shop->view['basket']['checkout']['shipping'] != $this->id) return;
			
			$this->shop->view['wpsg_mod_willcollect']['adress'] = $this->shop->get_option('wpsg_mod_willcollect_adress');
			$this->shop->view['wpsg_mod_willcollect']['street'] = $this->shop->get_option('wpsg_mod_willcollect_street');
			$this->shop->view['wpsg_mod_willcollect']['plzort'] = $this->shop->get_option('wpsg_mod_willcollect_plzort');
			
			if ($this->shop->htmlMail === true)
			{
			
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_willcollect/mail_html.phtml');
				
			}
			else
			{
				
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_willcollect/mail.phtml');
				
			}
			
		} // public function mail_shipping()
		
	} // class wpsg_mod_willcollect extends wpsg_mod_basic

?>