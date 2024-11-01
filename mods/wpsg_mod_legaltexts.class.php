<?php

	/**
	 * Ermöglicht den Abgleich mit verschiedenen Anbietern von Rechtstexten
	 * @author roger
	 */
	class wpsg_mod_legaltexts extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 1900;  
		var $hilfeURL = 'http://wpshopgermany.maennchen1.de/?p=3704';
		
		const PROVIDER_PROTECTEDSHOPS = 1;
		const PROVIDER_HAENDLERBUND = 2;
		const PROVIDER_ITRECHT = 3;
		
		public function __construct() {
			
			parent::__construct();
			
			$this->name = __('Rechtstexte', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht den Abgleich mit Anbietern von "AGB-Rechtstext-Flatrates".', 'wpsg');
			
		} // public function __construct();
		
		public function install() {
			
			$this->shop->checkDefault('wpsg_mod_legaltexts_provider', self::PROVIDER_ITRECHT);
			
		}
		
		public function settings_edit() {

			$this->shop->view['wpsg_mod_legaltexts_provider'] = $this->shop->get_option('wpsg_mod_legaltexts_provider');
			if ($this->shop->view['wpsg_mod_legaltexts_provider'] === false || !in_array($this->shop->view['wpsg_mod_legaltexts_provider'], [self::PROVIDER_ITRECHT, self::PROVIDER_HAENDLERBUND, self::PROVIDER_PROTECTEDSHOPS])) $this->shop->view['wpsg_mod_legaltexts_provider'] = self::PROVIDER_ITRECHT;
						
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_legaltexts/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() {

			$this->shop->update_option('wpsg_mod_legaltexts_provider', $_REQUEST['wpsg_mod_legaltexts_provider'], false, false, WPSG_SANITIZE_INT);
			
			if (isset($_REQUEST['wpsg_mod_legaltexts_submitform']) && $_REQUEST['wpsg_mod_legaltexts_submitform'] == '1') {
				
				if ($_REQUEST['wpsg_mod_legaltexts_provider'] == wpsg_mod_legaltexts::PROVIDER_PROTECTEDSHOPS) {
					
					$wpsg_ps = new wpsg_protected_shops();
					$wpsg_ps->saveForm();
					
				} else if ($_REQUEST['wpsg_mod_legaltexts_provider'] == wpsg_mod_legaltexts::PROVIDER_HAENDLERBUND) {
					
					$wphb = new wphb();
					$wphb->saveForm();
					
				} else if ($_REQUEST['wpsg_mod_legaltexts_provider'] == wpsg_mod_legaltexts::PROVIDER_ITRECHT) {
					
					$wpit = new wpsg_itrecht();
					$wpit->saveForm();
					
				} else throw new \wpsg\Exception(__('Es wurde beim Speichern der Einstellungen ein ungültiger Provider übergeben', 'wpsg'));
				
			}

		} // public function settings_save()
		
		public function be_ajax()
		{

			if ($_REQUEST['modul'] == 'wpsg_mod_legaltexts')
			{
			
				if ($_REQUEST['provider'] == wpsg_mod_legaltexts::PROVIDER_HAENDLERBUND)
				{
										
					if (class_exists("wphb"))
					{

						$wphb = new wphb();
						
						$this->shop->view['wpsg_mod_legaltexts']['form'] = $wphb->showForm();
						
					}
					else
					{
						
						$this->shop->view['wpsg_mod_legaltexts']['form'] = false;
						
					}

					
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_legaltexts/form_haendlerbund.phtml');
					
				}
				else if ($_REQUEST['provider'] == wpsg_mod_legaltexts::PROVIDER_PROTECTEDSHOPS)
				{
					
					if (class_exists("wpsg_protected_shops"))
					{
						
						$wpsg_ps = new wpsg_protected_shops();
						
						if (!isset($wpsg_ps->version) || $wpsg_ps->version == "" || version_compare($wpsg_ps->version, "1.5") < 0)
						{
						
							$this->shop->view['wpsg_mod_legaltexts']['form'] = '<span style="color:red;">'.__("Die von Ihnen verwendete Version des ProtectedShops Plugin ist nicht ausreichend. Sie benötigen mindestens Version 1.6!", "wpsg").'</span>';
						
						}
						else
						{
						
							$this->shop->view['wpsg_mod_legaltexts']['form'] = $wpsg_ps->showForm();
						
						}
						
					}
					else
					{
						
						$this->shop->view['wpsg_mod_legaltexts']['form'] = false;
						
					}
					
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_legaltexts/form_protectedshops.phtml');
					
				}
				else if ($_REQUEST['provider'] == wpsg_mod_legaltexts::PROVIDER_ITRECHT)
				{
					
					if (class_exists("wpsg_itrecht"))
					{
						
						$wpit = new wpsg_itrecht();
						
						$this->shop->view['wpsg_mod_legaltexts']['form'] = $wpit->showForm();
						
					}
					else
					{
						
						$this->shop->view['wpsg_mod_legaltexts']['form'] = false;
						
					}
					
					$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_legaltexts/form_itrecht.phtml');
					
				}
				else throw new \wpsg\Exception(__('Es wurde bei der Ajax Anfrage der Provider ein ungültiger Provider angegeben', 'wpsg'));
							
			}
				
		} // public function be_ajax()
				
	} // class wpsg_mod_legaltexts extends wpsg_mod_basic

?>