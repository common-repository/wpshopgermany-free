<?php

	/**
	 * Integriert die Newsletteranmeldung für den wpNewsletterGermany in den Shop
	 * @author Daschmi
	 */
	class wpsg_mod_newsletter extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 599;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('wpNewsletterGermany', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht die Anmeldung an den wpNewsletterGermany.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
			
			$this->shop->checkDefault('wpsg_mod_newsletter_action', '0');
			
		} // public function install()
		
		public function settings_edit()
		{
			
			$this->shop->view['plugin_active'] = $this->checkNewsletterPlugin();
			
			if ($this->shop->view['plugin_active'] === true)
			{
								
				$this->shop->view['arGroup'] = wpng_Group::loadAllGroupNames();
				
			}
			
			$this->render(WPSG_PATH_VIEW.'/mods/mod_newsletter/settings_edit.phtml'); 
			
		} // public function settings_edit()
		
		public function settings_save()
		{
			
		    $this->shop->update_option('wpsg_mod_newsletter_groups', implode(',', (array)array_values($_REQUEST['wpsg_mod_newsletter_groups'])), false, false, WPSG_SANITIZE_TEXTFIELD);
		    $this->shop->update_option('wpsg_mod_newsletter_action', $_REQUEST['wpsg_mod_newsletter_action'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
		} // public function settings_save()
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) { 
			
			if (!$finish_order) return;
			
			if ($this->checkNewsletterPlugin() && $_SESSION['wpsg']['checkout']['wpsg_mod_newsletter'] == '1') {
				
				$checkout = $_SESSION['wpsg']['checkout'];
				
				// Abonnent eintragen
				$abo = new wpng_Abonnent();
				$abo->title = $checkout['title'];
				$abo->vname = $checkout['vname'];
				$abo->name = $checkout['name'];
				$abo->name = $checkout['name'];
				$abo->email = $checkout['email'];
				$abo->strasse = $checkout['strasse'];
				$abo->plz = $checkout['plz'];
				$abo->ort = $checkout['ort'];
				
				if ($this->shop->get_option('wpsg_mod_newsletter_action') == '0') {
					
					$abo->status = '1';
					$abo->save();
					
					$abo->sendDoubleOptIn();
					
					$this->shop->addFrontendMessage(__('Sie haben eine Mail erhalten, in der sie die Newsletteranmeldung bestätigen müssen.', 'wpsg'));
					
				} else if ($this->shop->get_option('wpsg_mod_newsletter_action') == '1') {
					
					$this->shop->addFrontendMessage(__('Sie wurden erfolgreich für den Newsletter angemeldet.', 'wpsg'));
					
					$abo->status = '2';
					$abo->save();
					
				}
				
				$abo->setGroups((array)explode(',', $this->shop->get_option('wpsg_mod_newsletter_groups')));
				
			}
			
		}
		
		public function wpsg_mod_customer_save(&$customer_data) 
		{ 
			
			if (wpsg_isSizedArray($_REQUEST['wpsg_mod_newsletter']) && wpsg_isSizedInt($_REQUEST['wpsg_mod_newsletter']['abo_id']))
			{
				
				$oAbo = new wpng_Abonnent();
				$oAbo->load($_REQUEST['wpsg_mod_newsletter']['abo_id']);
				
				if (isset($_REQUEST['wpsg_mod_newsletter']['status'])) $oAbo->status = wpsg_q($_REQUEST['wpsg_mod_newsletter']['status']);
				if (isset($_REQUEST['wpsg_mod_newsletter']['update']) && $_REQUEST['wpsg_mod_newsletter']['update'] === '1')
				{
					 
					$oAbo->title = wpsg_q($customer_data['title']);
					$oAbo->vname = wpsg_q($customer_data['vname']);
					$oAbo->name = wpsg_q($customer_data['name']);
					$oAbo->email = wpsg_q($customer_data['email']);
					$oAbo->strasse = wpsg_q($customer_data['strasse']);
					$oAbo->plz = wpsg_q($customer_data['plz']);
					$oAbo->ort = wpsg_q($customer_data['ort']);
					
				}
				
				$oAbo->setGroups($_REQUEST['wpsg_mod_newsletter']['group_id'], true);
				$oAbo->save();
				
			}
			
		} // public function wpsg_mod_customer_save(&$customer_data)
		
		public function wpsg_mod_customer_sidebar(&$customer_data) 
		{ 
			
			if (!$this->checkNewsletterPlugin()) return;
			
			// Abonnenten suchen
			$arAboId = $this->db->fetchAssocField("SELECT `id` FROM `".WPNG_TBL_ABO."` WHERE `email` = '".wpsg_q($customer_data['email'])."' ");
			
			$this->shop->view['wpsg_mod_newsletter']['found'] = false;
			$this->shop->view['wpsg_mod_newsletter']['status'] = '<span class="wpsg_error">'.__('Kein Abonnent gefunden', 'wpsg').'</span>';
			
			if (sizeof($arAboId) > 1)
			{
				
				$this->shop->view['wpsg_mod_newsletter']['status'] = '<span class="wpsg_error">'.__('Mehrere Abonnenten!', 'wpsg').'</span>';
				
			}
			else if (sizeof($arAboId) == 1)
			{

				$oAbo = new wpng_Abonnent();
				$oAbo->load($arAboId[0]);
				
				$this->shop->view['wpsg_mod_newsletter']['abo_id'] = $oAbo->id;
				$this->shop->view['wpsg_mod_newsletter']['found'] = true;
				$this->shop->view['wpsg_mod_newsletter']['name'] = '<a href="'.WPSG_URL_WP.'wp-admin/admin.php?page=wpng-Abonnent&wpng_action=edit&id='.$oAbo->id.'">'.$oAbo->getFormatedName().'</a>';
				$this->shop->view['wpsg_mod_newsletter']['arGroups'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPNG_TBL_GROUP."` ORDER BY `name` ASC ", "id", "name");			
				$this->shop->view['wpsg_mod_newsletter']['groupIds'] = $oAbo->getGroupIDs();				
				$this->shop->view['wpsg_mod_newsletter']['arStatus'] = array(
					'0' => __('Inaktiv', 'wpsg'),
					'1' => __('auf Bestätigung warten', 'wpsg'),
					'2' => __('Aktiv', 'wpsg') 						
				);
				$this->shop->view['wpsg_mod_newsletter']['status'] = $oAbo->status;
				
				
			}
			
			$this->shop->render(WPSG_PATH_VIEW.'mods/mod_newsletter/customer_sidebar.phtml');
			
		} // public function wpsg_mod_customer_sidebar(&$customer_data)
		
		/* Modulfunktion */
		
		public function checkout_customer_inner()
		{
			
			if (!$this->checkNewsletterPlugin()) return;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_newsletter/checkout_customer_inner.phtml');
			
		} // public function checkout_customer_inner()
		
		public function clearSession() 
		{
			 
			if ($this->shop->get_option('wpsg_afterorder') == '1')
			{
			
				unset($_SESSION['wpsg']['wpsg_mod_newsletter']);
				
			}
			
		} // public function clearSession()
		
		public function be_ajax()
		{
		
			if ($_REQUEST['do'] == 'import') {
				
				$nImport = 0;
				
				$arKunden = $this->db->fetchAssoc("
					SELECT
						K.*, CA.`name` AS adrname, CA.`name` AS adrvname 
					FROM
						`".WPSG_TBL_KU."` AS K
					LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = K.`adress_id`)	
				");
				
				foreach ($arKunden as $k) {
					
					$abo = wpng_Abonnent::getAbonnentByEMail($k['email']);
					
					if ($abo === false) {
						
						$abo = new wpng_Abonnent();
						
						// Neue auf aktiv setzen
						// 2 = Aktiv
						$abo->status = 2;
						
					} 
					
					$abo->email = $k['email'];
					if (trim($k['title']) != '' && $k['title'] != '-1') $abo->title = $k['title'];
					//$abo->vname = $k['vname'];
					//$abo->name = $k['name'];
					$abo->vname = $k['adrvname'];
					$abo->name = $k['adrname'];
					$abo->strasse = $k['strasse'];
					$abo->plz = $k['plz'];
					$abo->ort = $k['ort'];
										
					$abo->save();
					$abo->setGroups(explode(',', $_REQUEST['groups']), false);
					
					$nImport ++;
					
				}
				
				$this->shop->addBackendMessage(wpng_translate(__('#1# Kunden in die Newsletter Gruppen importiert.', 'wpsg'), $nImport));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_newsletter');
				
			}
			
		} // public function be_ajax()
		
		/** Modulfunktionen */
		
		/**
		 * Gibt true zurück wenn das wpNewsletterGermany Plugin installiert und aktiv ist
		 */
		private function checkNewsletterPlugin()
		{
			
			require_once(ABSPATH.'wp-admin/includes/plugin.php');
			
			if (is_plugin_active('wpnewslettergermany/wpnewslettergermany.php')) 
			{
			
				if ($GLOBALS['wpng_pc']->hasActiveLicence() || $GLOBALS['wpng_pc']->getDemoDays() > 0)
				{
					
					return true;
					
				}
				
			}
			
			return false;
			
		} // private function checkNewsletterPlugin()
		
	} // class wpsg_mod_newsletter extends wpsg_mod_basic

