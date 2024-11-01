<?php

	/**
	 * Kundenverwaltungsmodul
	 * mit Backendverwaltung, Frontendregistrierung, Frontendlogin
	 */
	class wpsg_mod_kundenverwaltung extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 10;
		var $hilfeURL = 'http://wpshopgermany.maennchen1.de/?p=3302';
		
		var $customerGroupCache = array();
				
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Kundenverwaltung', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht eine Kundenverwaltung mit Profil, Kundengruppen, Registrierung und Login Mechanismen.', 'wpsg');
									
		} // public function __construct()
		
		public function init()
		{
		    
		    $role_object = get_role('administrator');
		    $role_object->add_cap('wpsg_customer');
		    
		    if (is_admin() && $this->shop->get_option('wpsg_wpsg_kundenverwaltung_cappreset') === false) {
		        
		        $arRoles = get_option($wpdb->prefix."user_roles");
		        
		        if (!isset($arRoles['administrator']['capabilities']['wpsg_kundenverwaltung'])) {
		            
		            $role_object = get_role('administrator');
		            $role_object->add_cap('wpsg_kundenverwaltung');
		            
		            $this->shop->update_option('wpsg_wpsg_kundenverwaltung_cappreset', '1');
		            
		        }
		        
		    }
		    
		} // public function init()
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			 			
			// Grundeinstellung Pro Seite
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_perpage') === false || $this->shop->get_option('wpsg_mod_kundenverwaltung_perpage') == '') {
			    
				$this->shop->update_option('wpsg_mod_kundenverwaltung_perpage', '20');
				
			}
			
			// default "Betreff" für die Status Mail eines Kunden- Accounts setzen
			$this->shop->update_option('wpsg_activate_betreff', 'Der Status Ihres Accounts hat sich geändert');
			
			// default "Betreff" für die Registrierungsmail Mail eines Kunden- Accounts setzen
			$this->shop->update_option('wpsg_register_betreff', 'Registrierung Ihres Accounts');
			
			/** Kundentabelle erweitern */ 
			$sql = "CREATE TABLE ".WPSG_TBL_KU." (
		   		passwort_saltmd5 VARCHAR(255) NOT NULL,
		   		comment TEXT NOT NULL,
		   		wp_user_id INT(11) DEFAULT 0 NOT NULL,		   		
				status VARCHAR(255) NOT NULL,
				last_login DATETIME NOT NULL COMMENT 'Datum des letzten Logins',
				anonymized DATETIME NOT NULL COMMENT 'Datum der Anonymisierung'
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	   	 
   			dbDelta($sql);
   			   			
   			$this->shop->checkDefault('wpsg_kundenpwdrequest_betreff', __('Passwortänderungsanfrage', 'wpsg'));
   			$this->shop->checkDefault('wpsg_kundenpwd_betreff', __('Ihr neues Passwort', 'wpsg'));
   			$this->shop->checkDefault('wpsg_mod_kundenverwaltung_redirectlogin', '0');
   			$this->shop->checkDefault('wpsg_mod_kundenverwaltung_redirectLogout', '0');
			$this->shop->checkDefault('wpsg_mod_kundenverwaltung_loginZwang', '1');
			$this->shop->checkDefault('wpsg_page_mod_kundenverwaltung_status', '1');

            $this->db->UpdateQuery(WPSG_TBL_KU, ['last_login' => 'NOW()'], " `last_login` = '0000-00-00 00:00:00' ");
			
		} // public function install()
		
		public function be_ajax() 
		{ 
					    
			if ($_REQUEST['wpsg_mod_kundenverwaltung_setActiv'] == '1')
			{
				
				$this->db->UpdateQuery(WPSG_TBL_KU, array(
					'status' => '1'
				), " `deleted` != '1' ");

				$this->shop->addBackendMessage(__('Alle Kunden wurden auf Aktiv gesetzt.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_kundenverwaltung');
				
			}
			else if ($_REQUEST['wpsg_mod_kundenverwaltung_setInactiv'] == '1')
			{
				
				$this->db->UpdateQuery(WPSG_TBL_KU, array(
					'status' => '0'
				), " `deleted` != '1' ");
				
				$this->shop->addBackendMessage(__('Alle Kunden wurden auf Inaktiv gesetzt.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_kundenverwaltung');
				
			} else if ($_REQUEST['be_ajax'] === 'su' && is_admin()) {
	
				unset($_SESSION['wpsg']);
                
                $this->login($_REQUEST['k_id']);
                $kunde_data = $this->shop->cache->loadKunden($_REQUEST['k_id']);

                $this->shop->addBackendMessage(wpsg_translate(__('Sie sind jetzt im Frontend als #1# #2# angemeldet.', 'wpsg'), $kunde_data['vorname'], $kunde_data['name']));
                $this->shop->redirect(wpsg_admin_url('Customer', 'edit', ['edit_id' => $_REQUEST['k_id']]));
                
            } else if ($_REQUEST['be_ajax'] === 'su_index' && is_admin()) {
	
				wpsg_checkNounce('Admin', 'module', ['modul' => 'wpsg_mod_kundenverwaltung', 'be_ajax' => 'su_index', 'k_id' => $_REQUEST['k_id']]);
				
            	unset($_SESSION['wpsg']);

                $this->login($_REQUEST['k_id']);
                $kunde_data = $this->shop->cache->loadKunden($_REQUEST['k_id']);

                $this->shop->addBackendMessage(wpsg_translate(__('Sie sind jetzt im Frontend als #1# #2# angemeldet.', 'wpsg'), $kunde_data['vorname'], $kunde_data['name']));
                $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer&action=index');
                
            }
			
		} // public function be_ajax()
		
		public function settings_edit()
		{

			global $wpdb;
			
			$pages = get_pages();
			
			$arPages = array(
				'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			
			$this->shop->view['pages'] = $arPages;

			$this->shop->view['arPageWithoutCreate'] = $arPages;
			unset($this->shop->view['arPageWithoutCreate']['-1']);
			
			$this->shop->view['arRoles'] = get_option($wpdb->prefix."user_roles");
			foreach ($this->shop->view['arRoles'] as $k => $v) { $this->shop->view['arRoles'][$k] = $v['name']; }
						
			$this->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function admin_setcapabilities() {
		
			$this->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/admin_setcapabilities.phtml');
		
		} // public function admin_setcapabilities()
		
		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_kundenverwaltung_perpage', $_REQUEST['wpsg_mod_kundenverwaltung_perpage'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_showCheckoutLogin', $_REQUEST['wpsg_mod_kundenverwaltung_showCheckoutLogin'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_showCheckoutRegister', $_REQUEST['wpsg_mod_kundenverwaltung_showCheckoutRegister'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_showCheckoutRegisterzwang', $_REQUEST['wpsg_mod_kundenverwaltung_showCheckoutRegisterzwang'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_loginZwang', $_REQUEST['wpsg_mod_kundenverwaltung_loginZwang'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_preisAnzeige', $_REQUEST['wpsg_mod_kundenverwaltung_preisAnzeige'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			$this->shop->update_option('wpsg_mod_kundenverwaltung_redirectlogin', $_REQUEST['wpsg_mod_kundenverwaltung_redirectlogin'], false, false, WPSG_SANITIZE_INT);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_redirectLogout', $_REQUEST['wpsg_mod_kundenverwaltung_redirectLogout'], false, false, WPSG_SANITIZE_INT);
			$this->shop->update_option('wpsg_page_mod_kundenverwaltung_status', $_REQUEST['wpsg_page_mod_kundenverwaltung_status'], false, false, WPSG_SANITIZE_INT);

			$this->shop->update_option('wpsg_mod_kundenverwaltung_wpuser', $_REQUEST['wpsg_mod_kundenverwaltung_wpuser'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_wpuser_role', wpsg_xss($_REQUEST['wpsg_mod_kundenverwaltung_wpuser_role']));
			
			$this->shop->update_option('wpsg_mod_kundenverwaltung_aweber', $_REQUEST['wpsg_mod_kundenverwaltung_aweber'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_aweber_formid', $_REQUEST['wpsg_mod_kundenverwaltung_aweber_formid'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_aweber_listname', $_REQUEST['wpsg_mod_kundenverwaltung_aweber_listname'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_kundenverwaltung_aweber_metaAdtracking', $_REQUEST['wpsg_mod_kundenverwaltung_aweber_metaAdtracking'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->update_option('wpsg_mod_kundenverwaltung_recaptcha_register', $_REQUEST['wpsg_mod_kundenverwaltung_recaptcha_register'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_kundenveraltung_recaptcha_key', $_REQUEST['wpsg_mod_kundenveraltung_recaptcha_key'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_kundenveraltung_recaptcha_secretkey', $_REQUEST['wpsg_mod_kundenveraltung_recaptcha_secretkey'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->createPage(__('Profil', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_profil', $_REQUEST['wpsg_page_mod_kundenverwaltung_profil']);
			$this->shop->createPage(__('Registrierung', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_registrierung', $_REQUEST['wpsg_page_mod_kundenverwaltung_registrierung']);
			$this->shop->createPage(__('Registrierung abgeschlossen', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_weiterleitung_nach_registrierung', $_REQUEST['wpsg_page_mod_kundenverwaltung_weiterleitung_nach_registrierung']);
			$this->shop->createPage(__('Passwort gesendet', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_passwordsend', $_REQUEST['wpsg_page_mod_kundenverwaltung_passwordsend']);
			$this->shop->createPage(__('Bestellungen', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_order', $_REQUEST['wpsg_page_mod_kundenverwaltung_order']);
			$this->shop->createPage(__('Abonnements', 'wpsg'), 'wpsg_page_mod_kundenverwaltung_abo', $_REQUEST['wpsg_page_mod_kundenverwaltung_abo']);
			
		} // public function settings_save()

        public function wpsg_deinstall_sites() {

            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_profil'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_weiterleitung_nach_registrierung'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_passwordsend'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_order'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_kundenverwaltung_abo'));

        } // public function wpsg_deinstall_sites()

        public function wpsg_enqueue_scripts() { 
		    
		    $register_page = intval($this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'));
            $wpsg_mod_kundenverwaltung_recaptcha_register = $this->shop->get_option('wpsg_mod_kundenverwaltung_recaptcha_register');
		    
		    if (wpsg_isSizedInt($wpsg_mod_kundenverwaltung_recaptcha_register ) && wpsg_isSizedInt($register_page) && \get_the_ID() === $register_page) {

                \wp_enqueue_script('wpsg-recaptcha', 'https://www.google.com/recaptcha/api.js', false);
                
            } 
		    
        } // public function wpsg_enqueue_scripts()
        
        public function systemcheck(&$arData) {

            $wpsg_mod_kundenverwaltung_recaptcha_register = $this->shop->get_option('wpsg_mod_kundenverwaltung_recaptcha_register');
            $wpsg_mod_kundenveraltung_recaptcha_key = $this->shop->get_option('wpsg_mod_kundenveraltung_recaptcha_key');
            $wpsg_mod_kundenveraltung_recaptcha_secretkey = $this->shop->get_option('wpsg_mod_kundenveraltung_recaptcha_secretkey');
		    
		    if ($wpsg_mod_kundenverwaltung_recaptcha_register === '1' && (!wpsg_isSizedString($wpsg_mod_kundenveraltung_recaptcha_key) || !wpsg_isSizedString($wpsg_mod_kundenveraltung_recaptcha_secretkey))) {

                $arData[] = array(
                    'wpsg_mod_kundenverwaltung_recaptcha',
                    wpsg_ShopController::CHECK_ERROR,
                    wpsg_translate(
                        __('Die reCaptcha Überprüfung ist in den Einstellungen aktiv, aber die Konfiguration ist nicht komplett. Bitte <a href="#1#">hier</a> konfigurieren.', 'wpsg'),
                        WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_kundenverwaltung'
                    )
                );
		        
            }
		    
        } // public function systemcheck($arData)
		            		
		public function wpsg_add_pages($default_page)
		{
						
			add_submenu_page($default_page, __("Kundenverwaltung", "wpsg"), __("Kundenverwaltung", "wpsg"), 'wpsg_kundenverwaltung', 'wpsg-Customer', array($this, 'dispatch'));
			
		} // public function wpsg_add_pages()

		public function load() 
		{ 

			require_once(dirname(__FILE__).'/mod_kundenverwaltung/wpsg_kundenverwaltung_widget.class.php');
			add_action('widgets_init', function() { return register_widget("wpsg_kundenverwaltung_widget"); } );
			
            if (is_admin())
            {

                if (wpsg_isSizedString($_REQUEST['wpsg_do'], 'setAccount') && !wpsg_isSizedArray($_REQUEST['customer']))
                {

                    $this->shop->addBackendError(__('Bitte mindestens einen Kunden wählen.', 'wpsg'));
                    $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer');
                    
                }
                else if (wpsg_isSizedString($_REQUEST['wpsg_do'], 'setAccount') && isset($_REQUEST['submit_do']) && !wpsg_isSizedInt($_REQUEST['set_target']))
                {
                    
                    $this->shop->addBackendError(__('Kein Zielkunde ausgewählt.', 'wpsg'));
                    unset($_REQUEST['submit_do']);
                    
                }
                else if (isset($_REQUEST['submit_do']))
                {

                    // Hier die Kunden zusammenführen, damit ich umleiten kann
                    $nOrder = 0;
                    
                    foreach ($_REQUEST['customer'] as $customer_id)
                    {
                    
                        if ($customer_id != $_REQUEST['set_target'])
                        {
                            
                            $oCustomer = wpsg_customer::getInstance($customer_id);
                            $nOrder += $oCustomer->getOrderCount();

                            $this->db->UpdateQuery(WPSG_TBL_ORDER, array(
                                'k_id' => wpsg_q($_REQUEST['set_target'])
                            ), " `k_id` = '".wpsg_q($customer_id)."' ");
                            
                            $oCustomer->delete();
                            
                        }
                        
                    }
                    
                    $this->shop->addBackendMessage(wpsg_translate(__('#1# Bestellung(en) dem Kundenkonto zugeordnet.', 'wpsg'), $nOrder));
                    $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer');
                                        
                }
                
            }
            
		} // public function load()
		
		public function dispatch() {
						
			if (wpsg_isSizedString($_REQUEST['action'], 'edit')) {
				
				wpsg_checkNounce('Customer', 'edit', ['edit_id' => $_REQUEST['edit_id']]);
				
				$this->be_editAction();
				
			} else if (wpsg_isSizedString($_REQUEST['action'], 'add')) {
				
				wpsg_checkNounce('Customer', 'add');
				
				$this->be_addAction();
				
			} else if (wpsg_isSizedString($_REQUEST['action'], 'export')) {
				
				wpsg_checkNounce('Customer', 'export');
				
				$this->be_exportAction();
				
			} else if (wpsg_isSizedString($_REQUEST['action'], 'import')) {
				
				wpsg_checkNounce('Customer', 'import');
				
				$this->be_importAction();
				
			} else if (wpsg_isSizedString($_REQUEST['action'], 'del')) {
				
				wpsg_checkNounce('Customer', 'del', ['edit_id' => $_REQUEST['edit_id']]);
				
				$this->be_delAction();
				
			} else if (wpsg_isSizedString($_REQUEST['action'], 'save')) {
				
				wpsg_checkNounce('Customer', 'save');
				
				$this->be_saveAction();
				
			} else {
				
				if (isset($_REQUEST['submit-button'])) wpsg_checkNounce('Customer', 'search');
				
				$this->be_indexAction();
				
			}
			
		} // public function dispatch()

		public function checkCheckout(&$state, &$error, &$arCheckout) 
		{
			
			if (isset($_REQUEST['wpsg_mod_kundenverwaltung_login']))
			{
											
				// Error wird immer true, damit checkout2 nicht aufgerufen wird	
				$error = true;

				// Damit zum Checkout geleitet wird
				$_REQUEST['wpsg_checkout'] = '1';
									
				// Damit die anderen Checkoutfelder nicht geprüft werden
				$state = 0;
								
			}
			else if ($state == 1)
			{

				if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser')))
				{
					
					$temp = wpsg_customer::find(array('email' => $arCheckout['email']));
					$customer = array_shift($temp);
					
					$curuser = get_current_user_id();
					$wp_user_id = wpsg_getInt($customer->wp_user_id);
					if (is_object($customer) && wpsg_isSizedInt($wp_user_id) && ($wp_user_id != $curuser || !wpsg_isSizedInt($curuser)))
					{
						
						// Es gibt einen Kunden mit dieser E-Mail Adresse, Die Wordpress User Kopplung ist aktiv und die ID des eingeloggten Benutzers stimmt nicht überein
						// Ich breche hier ab, da auch der Kunde sonst in Wordpress angemeldet werden sollte
						
						$this->shop->addFrontendError(__('Es gibt einen Benutzer mit dieser E-Mail Adresse. Bitte melden Sie sich erst an, bevor sie bestellen. (Auch im Wordpress)', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'email';						
						$error = true;
						
						$_REQUEST['wpsg_checkout'] = 1;
						
						return;
						
					}
					 
				}
				
				if (isset($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1']) && isset($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd2']))
				{
										
					// Registrierungszwang und leer
					if ((isset($_REQUEST['wpsg_mod_kundenverwaltung_register']) || $this->shop->get_option('wpsg_mod_kundenverwaltung_showCheckoutRegisterzwang') == '1') && trim($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1']) == '' && $_REQUEST['wpsg']['mod_kundenverwaltung']['pwd2'] == '')
					{
						
						$this->shop->addFrontendError(__('Bitte ein Passwort festlegen!', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd1';
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd2';
						$error = true;
						
						$_REQUEST['wpsg_checkout'] = '1'; 
						
						return;
						
					}

					// Angabe in Feld1 aber ungleich Feld2
					if ($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1'] != $_REQUEST['wpsg']['mod_kundenverwaltung']['pwd2'] && $_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1'] != '')
					{	
						
						$this->shop->addFrontendError(__('Bitte Passwort Wiederholung prüfen!', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd1';
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd2';
						$error = true;
						
						return;
						
					}
								
					// Eingaben speichern
					if (trim($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1']) != '')
					{
						
						// In Checkout speichern
						$_SESSION['wpsg']['checkout']['password'] = wpsg_xss($_REQUEST['wpsg']['mod_kundenverwaltung']['pwd1']);
						
					} 
					
				}
				
				// Doppelbelegung der E-Mail prüfen				
				if (!wpsg_isSizedInt($arCheckout['id']))
				{

					// Wenn Wordpress Kopplung aktiv ist, prüfen ob schon ein Wordpress Nutzer mit dieser E-Mail existiert
					if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1')
					{
						
						$arCheckout['email'] = strtolower($arCheckout['email']);
						
						$user = get_user_by('login', $arCheckout['email']);
						
						if ($user != false)
						{
							
							$this->shop->addFrontendError(__('Es existiert bereits ein Wordpress Nutzer mit dieser E-Mail Adresse.', 'wpsg'));
							$_SESSION['wpsg']['errorFields'][] = 'email';							
							$_SESSION['wpsg']['errorFields'][] = 'email2';
							
							$error = true;
							
							return;
							
						} 
						
					}
					
					// Kunde ist nicht eingeloggt
					if ($this->shop->get_option('wpsg_mod_kundenverwaltung_loginZwang') == '1')
					{
					
						$kunde_id = $this->db->fetchOne("SELECT K.`id` FROM `".WPSG_TBL_KU."` AS K WHERE K.`email` = '".wpsg_q($arCheckout['email'])."'");
						
						// Die KundenID der Temporären Bestellung muss hier ausgeschlossen werden, da man sonst nicht weiterkommt
						$temp_customer_id = $this->db->fetchOne("SELECT `k_id` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($_SESSION['wpsg']['order_id'])."' ");
						
						$strQuery = "
							SELECT 
								K.`id` 
							FROM 
								`".WPSG_TBL_KU."` AS K 
							WHERE 
								K.`email` = '".wpsg_q($arCheckout['email'])."' AND
								K.`id` != '".wpsg_q($temp_customer_id)."'  
						";
						 
						$kunde_id = $this->db->fetchOne($strQuery);
						
						if ($kunde_id > 0)
						{
				 
							$this->shop->addFrontendError(__('Ein Kunde mit dieser E-Mail Adresse existiert bereits, bitte loggen Sie sich ein oder fordern Sie ein neues Passwort an!', 'wpsg'));
							
							$_SESSION['wpsg']['errorFields'][] = 'email';
							
							$error = true; 
	
						}
						
					}
					
				}
				else 
				{
					 
					// Prüfen ob die E-Mail Adresse noch nicht registriert wurde
					$kunde_mail = $this->db->fetchOne("SELECT K.`email` FROM `".WPSG_TBL_KU."` AS K WHERE K.`id` = '".wpsg_q($arCheckout['id'])."'");

					if (strtolower($kunde_mail) != strtolower($arCheckout['email']))
					{
						
						$kunde_id = $this->db->fetchOne("SELECT K.`id` FROM `".WPSG_TBL_KU."` AS K WHERE K.`email` = '".wpsg_q($arCheckout['email'])."'");
						
						if ($kunde_id > 0)
						{
							
							$this->shop->addFrontendError(__('Ein Kunde mit dieser E-Mail Adresse existiert schon, die E-Mail wurde zurückgesetzt!', 'wpsg'));
							$_SESSION['wpsg']['errorFields'][] = 'email';							
							$error = true;
							
							$arCheckout['email'] = $kunde_mail;
							if (isset($arCheckout['email2'])) $arCheckout['email2'] = $kunde_mail;
							 							
						}
						
					}
					
				}
				 
				if (isset($_REQUEST['wpsg']['register']['register_pwd1']))
				{
				
					if (trim($_REQUEST['wpsg']['register']['register_pwd1']) != '' || trim($_REQUEST['wpsg']['register']['register_pwd2']) != '')
					{
						
						if ($_REQUEST['wpsg']['register']['register_pwd1'] != $_REQUEST['wpsg']['register']['register_pwd2'])
						{
							
							$this->shop->addFrontendError(__('Bitte die Passworteingaben überprüfen.', 'wpsg'));
							$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd1';
							$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd2';
							$error = true;
							 
						}
						
					}
					else
					{
						
						$this->shop->addFrontendError(__('Eine Registrierung ohne Passwort ist nicht möglich.', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd1';
						$_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_pwd2';
						$error = true;
						
					}
					
				}

                if (isset($_REQUEST['wpsg_mod_kundenverwaltung_register']) && $this->shop->get_option('wpsg_mod_kundenverwaltung_recaptcha_register') === '1') {

                    $url = 'https://www.google.com/recaptcha/api/siteverify';
                    $data = array(
                        'secret' => $this->shop->get_option('wpsg_mod_kundenveraltung_recaptcha_secretkey'),
                        'response' => $_REQUEST["g-recaptcha-response"]
                    );
                    $options = array(
                        'http' => array (
                            'method' => 'POST',
                            'content' => http_build_query($data)
                        )
                    );
                    $context = stream_context_create($options);
                    $verify = file_get_contents($url, false, $context);
                    $captcha_success = json_decode($verify);

                    if ($captcha_success->success === false) {

                        $this->shop->addFrontendError(__('Bitte bestätigen Sie, dass Sie kein Bot sind.', 'wpsg'));
                        $_SESSION['wpsg']['errorFields'][] = 'mod_kundenverwaltung_recaptcha';

                    }
                    
                }
				
			}
			
		} // public function checkCheckout(&$state, &$error)
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) {
			
			if ($finish_order) return;
			
			// Wenn Wordpress User gekoppelt sind, dann auch den Wordpress User einloggen
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1')
			{
				
				if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['id']))
				{
					
					$this->login($_SESSION['wpsg']['checkout']['id'], $_SESSION['wpsg']['checkout']['email'], $_SESSION['wpsg']['checkout']['password']);
					
				}
				
			}
			
		} 
		
		public function basket_save_kunde(&$data, &$checkout) 
		{ 
 
			// Passwort nur ändern wenn etwas drin steht. Validierung erfolgt an anderer Stelle, aber auch leeres Passwort ist unter umständen valid
			if (wpsg_isSizedString($checkout['password'])) $data['passwort_saltmd5'] = wpsg_q($this->hashString($checkout['password']));
						
			if ($this->shop->get_option("wpsg_mod_kundenverwaltung_aweber") == "1") {
			
				// Anfrage an aWeber starten
				$fields = array(
					"meta_web_form_id" => $this->shop->get_option("wpsg_mod_kundenverwaltung_aweber_formid"),
					"meta_split_id" => "",
					"listname" => $this->shop->get_option("wpsg_mod_kundenverwaltung_aweber_listname"),
					"redirect" => "",
					"meta_adtracking" => $this->shop->get_option("wpsg_mod_kundenverwaltung_aweber_metaAdtracking"),
					"meta_message" => "1",
					"meta_required" => "name,email",
					"meta_tooltip" => "",
					"name" => @$data['vname'].' '.@$data['name'],
					"email" => @$data['email']					
				);
				
				$fields_string = '';
				
				foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
				rtrim($fields_string,'&');
				
				$url = 'http://www.aweber.com/scripts/addlead.pl';
				
				$ch = curl_init($url);				
				curl_setopt($ch, CURLOPT_URL, $url);				
			    curl_setopt($ch, CURLOPT_POST, sizeof($fields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch, CURLOPT_TIMEOUT, 2);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
				
				ob_start();
			    $result = curl_exec($ch);
			    curl_close ($ch);
			    ob_end_clean();
			    
			}
			
		} // public function basket_save_kunde(&$data)

		public function admin_emailconf_save()
		{
			
			wpsg_saveEMailConfig("kundenpwdrequest");
			wpsg_saveEMailConfig("kundenpwd");
			wpsg_saveEMailConfig("register");
			wpsg_saveEMailConfig("activate");
			 			
		} // public function admin_emailconf_save()
		
		public function admin_emailconf() 
		{ 
		
			echo wpsg_drawEMailConfig(
				'kundenpwdrequest',
				__('E-Mail bei vergessenem Passwort (Anfrage)', 'wpsg'),
				__('Diese Mail bekommt der Kunde wenn er sein Passwort vergessen hat und ein neues anfordert.', 'wpsg'));
			
			echo wpsg_drawEMailConfig(
				'kundenpwd',
				__('E-Mail bei vergessenem Passwort (Neues Passwort)', 'wpsg'),
				__('Mit dieser E-Mail erhält der Kunde sein neues Passwort.', 'wpsg'));
			
			echo wpsg_drawEMailConfig(
				'register',
				__('E-Mail bei Registrierung (Kundenverwaltung)', 'wpsg'),
				__('Mit dieser E-Mail erhält der Kunde eine Mail mit seinen registrierten Daten.', 'wpsg'));
			
			echo wpsg_drawEMailConfig(
				'activate',
				__('E-Mail bei Statusänderung im Kunden- Account', 'wpsg'),
				__('Mit dieser E-Mail erhält der Kunde ein Info nach Änderung seines Accounts.', 'wpsg'));
						
		} // public function admin_emailconf()
		
		public function template_redirect() 
		{ 
			
			// Wurde das Loginformular abgechickt?
			if (isset($_REQUEST['wpsg_mod_kundenverwaltung_login']))
			{
				
				$_SESSION['wpsg']['errorFields'] = array();
				$_REQUEST['wpsg']['mod_kundenverwaltung']['email'] = strtolower($_REQUEST['wpsg']['mod_kundenverwaltung']['email']);
				
				if (trim($_REQUEST['wpsg']['mod_kundenverwaltung']['password']) == '' || trim($_REQUEST['wpsg']['mod_kundenverwaltung']['email']) == '')
				{
					
					$_SESSION['wpsg']['wpsg_mod_kundenverwaltung']['errorEMail'] = $_REQUEST['wpsg']['mod_kundenverwaltung']['email'];
										
					$this->shop->addFrontendError(__('Bitte eine E-Mail Adresse und ein Passwort angeben!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_kundenverwaltung_email';
					$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_kundenverwaltung_password';
					
				}
				else
				{
								
					$pwd_hash = $this->hashString($_REQUEST['wpsg']['mod_kundenverwaltung']['password']);
					
					// Versuchen den Kunden zu laden
					$kunde_id = $this->db->fetchOne("
						SELECT
							K.`id`
						FROM
							`".WPSG_TBL_KU."` AS K
						WHERE
							K.`email` = '".wpsg_q($_REQUEST['wpsg']['mod_kundenverwaltung']['email'])."' AND
							K.`passwort_saltmd5` = '".wpsg_q($pwd_hash)."' AND
							K.`deleted` != '1' AND
							K.`status` != '0' AND K.`status` != '-1'
					");
										
					if ($kunde_id > 0) 
					{
						
						$this->login(
							$kunde_id, 
							$_REQUEST['wpsg']['mod_kundenverwaltung']['email'], 
							$_REQUEST['wpsg']['mod_kundenverwaltung']['password']
						);						
						
						$this->shop->addFrontendMessage(__('Sie wurden erfolgreich angemeldet.', 'wpsg'));
						
					}
					else 
					{
					
						$this->shop->addFrontendError(__('Es wurde kein Kunde mit diesen Zugangsdaten gefunden!', 'wpsg'));
						
						$_SESSION['wpsg']['wpsg_mod_kundenverwaltung']['errorEMail'] = $_REQUEST['wpsg']['mod_kundenverwaltung']['email'];
						
						$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_kundenverwaltung_email';
						$_SESSION['wpsg']['errorFields'][] = 'wpsg_mod_kundenverwaltung_password';
						
					}
					
				}
				
				if (isset($_REQUEST['wpsg']['checkout']))
				{
				
					// Wenn sich über den Checkout eingeloggt wird, leite ich auch wieder auf den Checkout
					$this->shop->redirect($this->shop->getURL(wpsg_ShopController::URL_CHECKOUT));
					
				}
				else
				{
				
					// Wenn im Backend ID > 0 eingestellt ist und die Seite existiert
					if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_kundenverwaltung_redirectlogin')) && is_page($this->shop->get_option('wpsg_mod_kundenverwaltung_redirectlogin')))
					{
												
						$this->shop->redirect(get_permalink($this->shop->get_option('wpsg_mod_kundenverwaltung_redirectlogin')));
						
					} 
					else if (wpsg_isSizedString($_REQUEST['wpsg_referer']))
					{
						
						$this->shop->redirect($_REQUEST['wpsg_referer']);
						
					} 
					else 
					{
						
						$this->shop->redirect($this->shop->getURL(wpsg_ShopController::URL_PROFIL));
						
					} 
				
				}
					
			}
			else if (isset($_REQUEST['wpsg_mod_kundenverwaltung_getpwd']) && isset($_REQUEST['hash']) && isset($_REQUEST['email']))
			{
				
				/** Link in einer E-Mail zur Passwortanfrage wurde gedrückt */
				$kunde = $this->db->fetchRow("SELECT K.* FROM `".WPSG_TBL_KU."` AS K WHERE K.`email` = '".wpsg_q($_REQUEST['email'])."'");
				$hash_gen = $this->hashString($kunde['id']);
				
				if ($hash_gen == $_REQUEST['hash'] && $kunde['id'] > 0)
				{
					
					// Passwort generieren
					$new_pwd = $this->createPasswort(10);
					unset($kunde['passwort_saltmd5']);
					$kunde['passwort'] = $new_pwd;
					$this->db->UpdateQuery(WPSG_TBL_KU, array(
						'passwort_saltmd5' => wpsg_q($this->hashString($new_pwd))
					), "`id` = '".wpsg_q($kunde['id'])."'");
					
					$this->shop->view['kunde'] = $kunde;
					
					$this->shop->callMods('customer_updatePwd', array(&$kunde['id'], &$new_pwd));
					
					$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_pwdsend.phtml', false);
											
					if ($this->shop->get_option('wpsg_htmlmail') === '1')
					{
						
						$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_pwdsend_html.phtml', false);
						
					}
					else
					{
						
						$mail_html = false;
						
					}
					
					$this->shop->sendMail($mail_text, $kunde['email'], 'kundenpwd', array(), false, false, $mail_html);		
								
					$this->shop->addFrontendMessage(__('Ihnen wurde eine neues Passwort zugesendet.', 'wpsg'));					
					
				}
				else 
				{
					
					$this->shop->addFrontendError(__('Ungültige Anfrage', 'wpsg'));
					
				}
				
				//$this->shop->redirect($this->getPwdVergessenURL());
				$this->shop->redirect($this->getProfilURL());
				
			}
			else if (isset($_REQUEST['wpsg_mod_kundenverwaltung_sendpwd']))
			{
								
				/** Passwort Vergessen */
				if (trim($_REQUEST['wpsg']['sendpwd']['email']) == '')
				{
					
					$this->shop->addFrontendError(__('Bitte eine E-Mail Adresse angeben!', 'wpsg'));
					$_SESSION['wpsg']['errorFields'][] = 'email';
					
				}
				else 
				{

					$kunde = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_KU."` AS K WHERE K.`email` = '".wpsg_q($_REQUEST['wpsg']['sendpwd']['email'])."'");
					
					if ($kunde['id'] > 0)
					{
						
						$this->shop->view['kunde'] = $kunde;
						$this->shop->view['strLink'] = html_entity_decode($this->getPwdVergessenURL().'&hash='.$this->hashString($kunde['id']).'&email='.$kunde['email']);												
						
						$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_pwdrequest.phtml', false);						
						
						if ($this->shop->get_option('wpsg_htmlmail') === '1')
						{
							
							$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_pwdrequest_html.phtml', false);
							
						}
						else
						{
							
							$mail_html = false;
							
						}
						
						$this->shop->sendMail($mail_text, $kunde['email'], 'kundenpwdrequest', array(), false, false, $mail_html);

						$this->shop->addFrontendMessage(__('Ein Link zur Generierung eines neuen Passwortes wurde Ihnen zugesendet. Überprüfen Sie bitte Ihr Postfach.', 'wpsg'));
						$this->shop->redirect($this->shop->getURL(wpsg_ShopController::URL_PROFIL));

					}
					else
					{
						
						$this->shop->addFrontendError(__('Kein Benutzer mit dieser E-Mail Adresse gefunden!', 'wpsg'));
						$_SESSION['wpsg']['errorFields'][] = 'email';
						
					}
					
				}
				 
				$this->shop->redirect($this->shop->getURL(wpsg_ShopController::URL_LOSTPWD));
				
			}
			else if (isset($_REQUEST['wpsg_mod_kundenverwaltung_register']))
			{
				
				/** Wird aufgerufen wenn sich ein Kunde registrieren möchte. */
				$this->shop->checkEscape();
								
				$basket = new wpsg_basket();				
				$basket->arCheckout = $_REQUEST['wpsg']['register'];
				
				// Ich speichere die Angaben schon im Checkout
				$_SESSION['wpsg']['checkout'] = wpsg_xss($basket->arCheckout);
				
				if ($basket->checkCheckout(1))
				{
 					
					// Alles OK
					$kunde_data = $basket->arCheckout;
					unset($kunde_data['email2']);		
					$kunde_data['geb'] = wpsg_toDate($kunde_data['geb']);
					 					
					// Passwort speichern
					//$kunde_data['passwort_saltmd5'] = wpsg_q($this->hashString($kunde_data['register_pwd1']));
					$kunde_data['password'] = $kunde_data['register_pwd1'];
					unset($kunde_data['register_pwd1']);
					unset($kunde_data['register_pwd2']);
					
					$pwd = $kunde_data['password'];
					
					$this->shop->callMods('basket_save_kunde', array(&$kunde_data, &$kunde_data));
					
					unset($kunde_data['password']);
					
					$kunde_data['custom'] = @serialize($kunde_data['custom']);
															
					$kunde_data = wpsg_q($kunde_data);
		
					// Kundengruppe für Checkout
					if ($this->shop->hasMod('wpsg_mod_customergroup'))
					{
					
						if ($this->shop->get_option('wpsg_page_mod_kundenverwaltung_group_register') > 0) $kunde_data['group_id'] = $this->shop->get_option('wpsg_page_mod_kundenverwaltung_group_register');
						
					}
					
					$kunde_data['email'] = strtolower($kunde_data['email']);
					unset($kunde_data['payment']);
						
					// Kundendaten/Adressdaten trennen
					$adata = Array();
					$adata['cdate'] = 'NOW()';
					$adata['title'] = wpsg_getStr($kunde_data['title']);
					$adata['name'] = wpsg_getStr($kunde_data['name']);
					$adata['vname'] = wpsg_getStr($kunde_data['vname']);
					$adata['firma'] = wpsg_getStr($kunde_data['firma']);
					$adata['fax'] = wpsg_getStr($kunde_data['fax']);
					$adata['strasse'] = wpsg_getStr($kunde_data['strasse']);
					$adata['nr'] = wpsg_getStr($kunde_data['nr']);
					$adata['plz'] = wpsg_getStr($kunde_data['plz']);
					$adata['ort'] = wpsg_getStr($kunde_data['ort']);
					$adata['land'] = wpsg_getStr($kunde_data['land']);
					$adata['tel'] = wpsg_getStr($kunde_data['tel']);
					
					$kdata = Array();
					$kdata['email'] = wpsg_getStr($kunde_data['email']);
					$kdata['ustidnr'] = wpsg_getStr($kunde_data['ustidnr']);
					$kdata['geb'] = wpsg_getStr($kunde_data['geb']);
					$kdata['id'] = $kunde_data['id'];
					$kdata['passwort_saltmd5'] = $kunde_data['passwort_saltmd5'];
					$kdata['custom'] = $kunde_data['custom'];
						
					$kdata['adress_id'] = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adata);
					$kunde_id = $this->db->ImportQuery(WPSG_TBL_KU, $kdata);
					$knr = $this->shop->buildKNR($kunde_id);

					$this->shop->cache->clearKundenCache($kunde_id);
					$this->shop->callMods('customer_created', array(&$kunde_id, &$pwd));
					
					$this->db->UpdateQuery(WPSG_TBL_KU, array(
						'knr' => wpsg_q($knr),
						'status' => get_option('wpsg_page_mod_kundenverwaltung_status')
					), "`id` = '".wpsg_q($kunde_id)."'");
					 
					$this->registerMail($kunde_data);
					
					//$_SESSION['wpsg']['checkout']['id'] = wpsg_xss($kunde_id); *** ersetzt um den WP-User anzumelden ***
					$this->login($kunde_id);
										
					$this->shop->addFrontendMessage(__('Ihr Profil wurde erfolgreich angelegt.', 'wpsg'));

					$this->shop->redirect($this->getRegisteredRedirectURL()); 
					
				}
				else
				{

					$this->shop->redirect($this->getRegisterURL()); 
					
				}
				
			}
			else if (isset($_REQUEST['wpsg_mod_kundenverwaltung_save']))
			{
				
				/** Wird beim speichern des Profils aufgerufen */
				$this->shop->checkEscape();
				
				$kunde_id = $_SESSION['wpsg']['checkout']['id'];
				
				$oCustomer = wpsg_customer::getInstance($kunde_id);
				
				// Pflichtfelder checken
				// Ich simuliere hier die Prüfung im Checkout, um doppel Implementation zu vermeiden
				$basket = new wpsg_basket();				
				$basket->arCheckout = $_REQUEST['wpsg']['profil'];
				$basket->arCheckout['id'] = $kunde_id;	

				if ($basket->checkCheckout(1))
				{
					
					// Kunde speichern
					$kunde_data = $basket->arCheckout;
					unset($kunde_data['id']);
					unset($kunde_data['email2']);		
					$kunde_data['geb'] = wpsg_toDate($kunde_data['geb']);			

					// Passwort geändert?
					if (trim($_REQUEST['wpsg']['mod_kundenverwaltung']['register_pwd1']) != '')
					{
						
						if ($_REQUEST['wpsg']['mod_kundenverwaltung']['register_pwd1'] != $_REQUEST['wpsg']['mod_kundenverwaltung']['register_pwd2'])
						{
							
							$this->shop->addFrontendError(__('Passwortwiederholung ist nicht korrekt. Passwort wurde nicht geändert!', 'wpsg'));
							
						}
						else
						{
						
							if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser'))
							{
								
								$curuser = get_current_user_id();
								$wp_user_id = $oCustomer->wp_user_id;
								if (!wpsg_isSizedInt($curuser) || $curuser != $wp_user_id)
								{
							
									$this->shop->addFrontendError(__('Passwort kann nicht verändert werden, da sie nicht im Wordpress Backend angemeldet sind.', 'wpsg'));
									$this->shop->redirect($this->getProfilURL());
									
								}
									
							}
							
							$this->shop->callMods('customer_updatePwd', array(&$kunde_id, &$_REQUEST['wpsg']['mod_kundenverwaltung']['register_pwd1']));						
							$kunde_data['passwort_saltmd5'] = $this->hashString($_REQUEST['wpsg']['mod_kundenverwaltung']['register_pwd1']);
							
						}
						
					}
					
					$this->shop->addFrontendMessage(__('Profil erfolgreich gespeichert.', 'wpsg'));
					
					$db_customer_presave = $this->shop->cache->loadKunden($kunde_id);
					$customer_presave_custom = @unserialize($db_customer_presave['custom']);
					if (wpsg_isSizedArray($customer_presave_custom))
					{
						
						foreach ($customer_presave_custom as $k => $v)
						{
							
							if (isset($kunde_data['custom'][$k])) $customer_presave_custom[$k] = $kunde_data['custom'][$k];
							
						}
						
						$kunde_data['custom'] = @serialize($customer_presave_custom);
						
					}
					else
					{

						$kunde_data['custom'] = @serialize($kunde_data['custom']);
						
					}
					 					
					$kunde_data = wpsg_q($kunde_data);
					unset($kunde_data['payment']);
					
					$adata['cdate'] = 'NOW()';
					$adata['title'] = wpsg_getStr($kunde_data['title']); unset($kunde_data['title']);
					$adata['name'] = wpsg_getStr($kunde_data['name']); unset($kunde_data['name']);
					$adata['vname'] = wpsg_getStr($kunde_data['vname']); unset($kunde_data['vname']);
					$adata['firma'] = wpsg_getStr($kunde_data['firma']); unset($kunde_data['firma']);
					$adata['fax'] = wpsg_getStr($kunde_data['fax']); unset($kunde_data['fax']);
					$adata['strasse'] = wpsg_getStr($kunde_data['strasse']); unset($kunde_data['strasse']);
					$adata['nr'] = wpsg_getStr($kunde_data['nr']); unset($kunde_data['nr']);
					$adata['plz'] = wpsg_getStr($kunde_data['plz']); unset($kunde_data['plz']);
					$adata['ort'] = wpsg_getStr($kunde_data['ort']); unset($kunde_data['ort']);
					$adata['land'] = wpsg_getStr($kunde_data['land']); unset($kunde_data['land']);
					$adata['tel'] = wpsg_getStr($kunde_data['tel']); unset($kunde_data['tel']);
					
					$a = $this->db->fetchRow("
						SELECT
							`adress_id`
						FROM
							`".WPSG_TBL_KU."`
						WHERE
							`id` = '".wpsg_q($kunde_id)."'
					");
					$kunde_data['adress_id'] = $a['adress_id'];
					
					if ($a['adress_id'] == 0)
						$kunde_data['adress_id'] = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adata);
					else
						$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adata, "`id` = '".wpsg_q($a['adress_id'])."'");
								
					$this->db->UpdateQuery(WPSG_TBL_KU, $kunde_data, "`id` = '".wpsg_q($kunde_id)."'");
					
				}

				// In der Session übernehme ich die Werte auch wenn fehlerhaft, da die endgültige Abfrage bei Bestellung folgt				
				$_SESSION['wpsg']['checkout'] = wpsg_xss($basket->arCheckout);
				$_SESSION['wpsg']['checkout']['id'] = wpsg_xss($kunde_id);
				
				// Fehler aufgetreten
				$this->shop->redirect($this->getProfilURL());	
							
			}
			else if (isset($_REQUEST['wpsg_mod_kundenverwaltung_logout']))
			{
				
				$this->logout();
				
				if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_kundenverwaltung_redirectLogout')))
				{
					
					$this->shop->redirect(get_permalink($this->shop->get_option('wpsg_mod_kundenverwaltung_redirectLogout')));
					
				}
				else if (wpsg_isSizedString($_REQUEST['wpsg_referer']))
				{
					
					$this->shop->redirect($_REQUEST['wpsg_referer']);
					
				}
				else
				{
					
					$this->shop->redirect($this->getProfilURL());
					
				}
								
			}
			
		} // public function template_redirect()
		
		public function content_filter(&$content) 
		{
			
			if (wpsg_get_the_id() <= 0) return;
            
			if (wpsg_get_the_id() == $this->shop->get_option('wpsg_page_mod_kundenverwaltung_profil'))
			{
								
				// Eingelogt
				if ($this->isLoggedIn() > 0)
				{
					
					$kunde = $this->shop->cache->loadKunden($this->isLoggedIn());
					$kunde['geb'] = wpsg_fromDate($kunde['geb']);
						
					$this->shop->view['data'] = array_merge($_SESSION['wpsg']['checkout'], $kunde);
					$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];					
					$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
					$this->shop->view['custom_values'] = @unserialize($kunde['custom']);
					$this->shop->view['laender'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `name` ASC");

					// Benutzerdefinierte Felder laden
					$this->shop->view['data']['custom'] = array();					
					foreach ((array)$this->shop->view['pflicht']['custom'] as $k => $v)
					{
						
						$this->shop->view['data']['custom'][$k] = wpsg_getStr($this->shop->view['custom_values'][$k]);						
						
					}
					
					$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/profil.phtml', false);
					
				}
				else
				{
					
					if (isset($_SESSION['wpsg']['wpsg_mod_kundenverwaltung']['errorEMail']))
					{
						
						$this->shop->view['wpsg_mod_kundenverwaltung']['email'] = $_SESSION['wpsg']['wpsg_mod_kundenverwaltung']['errorEMail'];
						
						unset($_SESSION['wpsg']['wpsg_mod_kundenverwaltung']['errorEMail']);
						
					}
					
					$this->shop->view['error'] = !isset ($_SESSION['errorFields']) or $_SESSION['wpsg']['errorFields'];
					$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/login.phtml', false);
					
				}
				
			}
			
			else if (wpsg_get_the_id() == $this->shop->get_option('wpsg_page_mod_kundenverwaltung_abo') && $this->shop->hasMod('wpsg_mod_abo'))
			{

                // Eingelogt
                if ($this->isLoggedIn() > 0)
                {
			    
                    $this->shop->view['arOrder'] = wpsg_order::find(Array(
                        'k_id' => $_SESSION['wpsg']['checkout']['id'],
                        'NOTstatus' => wpsg_ShopController::STATUS_UNVOLLSTAENDIG
                    ));
                    
                    // Jetzt noch normale Bestellungen rausfiltern
                    foreach ($this->shop->view['arOrder'] as $k => $oOrder)
                    {
                        
                        if ($this->shop->callMod('wpsg_mod_abo', 'isAboOrder', Array($oOrder->id)) !== 1)
                        {
                            
                            unset($this->shop->view['arOrder'][$k]);
                            
                        }
                        
                    }
                    
				    $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_abo/page_abo.phtml');

                }
                else
                {

                    $this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
                    $content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/login.phtml', false);

                }

                return -2;
			
			}	
						
			else if (wpsg_get_the_id() == $this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'))
			{
				
				if (isset($_REQUEST['wpsg_mod_kundenverwaltung_getpwd']))
				{
				
					// Passwort vergessen Link
					if ($this->isLoggedIn() > 0)
					{
						
						$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/logout.phtml');
											
					}
					else
					{
						
						$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
						$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
						
						$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/getpwd.phtml', false);
				
					}
						
				}
				else
				{
				
					if ($this->isLoggedIn() > 0)
					{
						
						$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/logout.phtml', false);
											
					}
					else
					{
						
						$this->shop->view['data'] = $_SESSION['wpsg']['checkout'];
						$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];					
						$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
						$this->shop->view['laender'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `name` ASC");
						
						$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/register.phtml', false);
						
					}
					
				}
				
			}
			else if (wpsg_get_the_id() == $this->shop->get_option('wpsg_page_mod_kundenverwaltung_order'))
			{
				
				// Eingelogt
				if ($this->isLoggedIn() > 0)
				{
				 
					$this->shop->view['arOrder'] = $this->db->fetchAssoc("
						SELECT
							*
						FROM
							`".WPSG_TBL_ORDER."`
						WHERE
							`k_id` = '".wpsg_q($_SESSION['wpsg']['checkout']['id'])."'
						ORDER BY
						 	`cdate` DESC
					");

					$strQuerySELECT = "";
					
					// Aufwerten
					foreach ($this->shop->view['arOrder'] as $k => $v)
					{
						
						if ($this->shop->hasMod('wpsg_mod_productvariants'))
						{
							
							$strQuerySELECT .= ",`mod_vp_varkey`";
							
						}
						
						$arProdukt = $this->db->fetchAssoc("
							SELECT
								(OP.`price` * OP.`menge`) AS `price`, OP.`p_id`,
								OP.`menge`,
								OP.`productkey`,
								OP.`mod_vp_varkey`,
								OP.`product_index`
								".$strQuerySELECT."
							FROM
								`".WPSG_TBL_ORDERPRODUCT."` AS OP
									LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (OP.`p_id` = P.`id`)
							WHERE
								OP.`o_id` = '".wpsg_q($v['id'])."'	
						"); 
						
						$this->shop->view['arOrder'][$k]['arProdukte'] = array();
						
						foreach ($arProdukt as $k2 => $v2)
						{
							
							$produkt = $this->shop->loadProduktArray($v2['p_id']);
							
							if ($this->shop->hasMod('wpsg_mod_productvariants'))
							{
								
								$produkt['mod_vp_varkey'] = $v2['mod_vp_varkey'];
								
							}
							
							$produkt['productkey'] = $v2['productkey'];
							
							// Fallback für alte Bestellungen ohne ProductKey Eintrag
							if (!wpsg_isSizedString($produkt['productkey']) && wpsg_isSizedString($v2['mod_vp_varkey'])) $produkt['productkey'] = $v2['mod_vp_varkey'];
							
							// Wenn kein ProduktKey dann die ID rein
							if (!wpsg_isSizedString($produkt['productkey'])) $produkt['productkey'] = $produkt['id'];
							
							$produkt['menge'] = $v2['menge'];							
							$produkt['preis'] = $v2['price'];
							$produkt['product_index'] = $v2['product_index'];
							
							$this->shop->view['arOrder'][$k]['arProdukte'][] = $produkt;
							
						}	

						$wpsg_basket = new wpsg_basket();
						$wpsg_basket->initFromDB($v['id']);
						
						$this->shop->view['arOrder'][$k]['basket'] = $wpsg_basket->toArray();
												
					}
					
					$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/order.phtml', false);
				
				}
				else
				{
				
					$this->shop->view['error'] = $_SESSION['wpsg']['errorFields'];
					$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/login.phtml', false);
				
				}

				return -2;
				
			}
			
		} // public function content_filter($content)
		
		public function customer_updatePwd(&$customer_id, &$customer_pwd) 
		{ 
			
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1' && wpsg_isSizedString($customer_pwd))
			{
				
				$kunde_data = $this->shop->cache->loadKunden($customer_id);
				
				$curuser = get_current_user_id();
				if ($curuser == $kunde_data['wp_user_id'] && wpsg_isSizedInt($curuser))
				{
				
					// Das Passwort darf nur geändert werden, wenn der User für den es geändert werden soll angemeldet ist
					wp_set_password($customer_pwd, $kunde_data['wp_user_id']);
					
				}
				 
			}	
			
		} // public function customer_updatePwd(&$customer_id, &$customer_pwd)
		
		public function customer_created(&$kunde_id, &$pwd)
		{
			
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1')
			{
				
				$kunde_data = $this->shop->cache->loadKunden($kunde_id);
				if ($kunde_data['email'] == '') return false;
				
				$wp_user_id = wp_create_user($kunde_data['email'], $pwd, $kunde_data['email']);
				
				wp_update_user(array(
					'ID' => $wp_user_id,
					'first_name' => $kunde_data['vname'],
					'last_name' => $kunde_data['name'],
					'user_email' => $kunde_data['email']
				));
				
				$user = new WP_User($wp_user_id);
				$user->set_role($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser_role'));
				 
				if (is_object($wp_user_id) && get_class($wp_user_id) == 'WP_Error')
				{
					
					$errors = $wp_user_id->errors;
					
					foreach ((array)$errors as $e)
					{

						$this->shop->addFrontendError(__('Es gab ein Problem beim anlegen des Wordpress Nutzers: ', 'wpsg').implode(',', $e));
						
					}
																
				}
				else if (!wpsg_isSizedInt($wp_user_id)) throw new \wpsg\Exception(__('Beim Anlegen des Wordpress Nutzers gab es eine unerwartete Rückgabe', 'wpsg'));
				else
				{
					
					$this->db->UpdateQuery(WPSG_TBL_KU, array('wp_user_id' => wpsg_q($wp_user_id)), "`id` = '".wpsg_q($kunde_id)."'");
					
				}				
				 
			}
			
		} // public function customer_created($kunde_id, $pwd)
		
		public function customer_delete_pre(&$customer_id, $delete) 
		{ 
			
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1')
			{
		 
				$kunde_data = $this->shop->cache->loadKunden($customer_id);
				
				if (wpsg_isSizedInt($kunde_data['wp_user_id']) && ($delete == 'delete')) 
					wp_delete_user($kunde_data['wp_user_id']);
				
			}
			 
		} // public function customer_delete_pre(&$customer_id)
		
		/**
		 * Wird im Checkout eingebunden um das Login Formular anzuzeigen
		 */
		public function checkout_login()
		{
			
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_showCheckoutLogin') == '1')
			{
				
				if ($this->isLoggedIn())
				{
					
					// Angemeldet
					return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/checkout_logout.phtml');
					
				}
				else
				{
					
					// Nicht angemeldet
					return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/checkout_login.phtml');
						
				}
				
			}
			
			return '';

		} // public function checkout_login()
		 
		public function checkout_customer_inner()
		{
				
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_showCheckoutRegister') != '1' || $this->isLoggedIn()) return;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/checkout_customer_inner.phtml');
			
		} // public function checkout_customer_inner()
		
		/**
		 * Gibt die URL auf die Profilseite zurück
		 */
		public function getProfilURL()
		{
			
			$page_url = get_permalink($this->shop->get_option('wpsg_page_mod_kundenverwaltung_profil'));
			
			return $page_url;
			
		} // public function getProfilURL()
		 
		/**
		 * Gibt die URL auf die Weiterleitungsseite nach der Registrierung zurück
		 */
		public function getRegisteredRedirectURL()
		{
			
			$page_url = get_permalink($this->shop->get_option('wpsg_page_mod_kundenverwaltung_weiterleitung_nach_registrierung'));
			
			return $page_url;
			
		} // public function getRegisteredRedirectURL()
		 
		/**
		 * Gibt die URL auf die Passwort-gesendet-Seit zurück
		 */
		public function getPasswordsendURL()
		{
		
			$page_url = get_permalink($this->shop->get_option('wpsg_mod_kundenverwaltung_passwordsend'));
		
			return $page_url;
		
		} // public function getProfilURL()
		
		/**
		 * Gibt die URL auf die Registrierungsseite zurück
		 */
		public function getRegisterURL()
		{
			
			$page_url = get_permalink($this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'));
			
			return $page_url;
			
		} // public function getRegisterURL()
		
		/**
		 * Gibt die URL für den Passwort vergessen Link zurück
		 */
		public function getPwdVergessenURL()
		{
			
			$page_url = get_permalink($this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'));
			
			if (strpos($page_url, '?') === false)
			{
				
				return $page_url.'?wpsg_mod_kundenverwaltung_getpwd';
				
			}
			else
			{
				
				return $page_url.'&amp;wpsg_mod_kundenverwaltung_getpwd';
				
			}
			
			return $page_url;
			
		} // public function getPwdVergessenURL()
		
		/**
		 * TODO Funktion zum setzen der statusinformatioenen aller registrierten Benutzer im BE einbauen (checkbox)
		 * checkt den Status eines Benutzers
		 */
		public function getUserStatus($uid)
		{
			
			$status = $this->db->fetchOne("
				SELECT
					K.`status`
				FROM
					`".WPSG_TBL_KU."` AS K
				WHERE
					K.`id` = '".wpsg_q($uid)."'			
			");
			
			if ($status == '1')
			{
				return true;
			}
			else 
			{
				return false;
			}
			
		} // public function getUserStatus($uid)
		
		/**
		 * Gibt die URL für die Logout URL zurück
		 */
		public function getLogoutURL()
		{
			
			$page_url = get_permalink($this->shop->get_option('wpsg_page_mod_kundenverwaltung_registrierung'));
			
			if (strpos($page_url, '?') === false)
			{
				
				return $page_url.'?wpsg_mod_kundenverwaltung_logout';
				
			}
			else
			{
				
				return $page_url.'&amp;wpsg_mod_kundenverwaltung_logout';
				
			}
			
			return $page_url;
			
		} // public function getLogoutURL()
		
		/**
		 * Export der Kundendaten nach CSV
		 */
		private function be_exportAction()
		{
			
			// Export WPSG_TBL_KU und WPSG_TBL_ADRESS
			//$arData = $this->db->fetchAssoc("SELECT * FROM `".wpsg_q(WPSG_TBL_KU)."` ");
			/*
			$arData = $this->db->fetchAssoc("
					SELECT
						C.*, CA.*, C.`id` AS 'kid'
					FROM
						`".WPSG_TBL_KU."` AS C
						 	LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = C.`adress_id`)
				");
			*/
			
			$arData = $this->db->fetchAssoc("
				SELECT
					C.*, C.`id` AS kid, 
					CA.`title` AS `title`,
					CA.`name` AS `name`,
					CA.`vname` AS `vname`,
					CA.`firma` AS `firma`,
					CA.`fax` AS `fax`,
					CA.`strasse` AS `strasse`,
					CA.`nr` AS `nr`,
					CA.`plz` AS `plz`,
					CA.`ort` AS `ort`,
					CA.`land` AS `land`,
					CA.`tel` AS `tel`,
					CA.`id`
			  	FROM
			  		`".wpsg_q(WPSG_TBL_KU)."` AS C
			  		 	LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = C.`adress_id`)
				WHERE
					C.`deleted` != '1' AND
					C.`email` != ''
			");
			
			if (!wpsg_isSizedArray($arData)) { $this->shop->addBackendError(__('Keine Daten zum Exportieren vorhanden.', 'wpsg')); $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer&amp;action=index'); return; }
						
			$mb = new wpsg_mod_basic();
			$path = $mb->getTmpFilePath();
			
			$fp = fopen($path.'/wpsg_customerexport.csv', 'w');
			fputcsv($fp, array_keys($arData[0]), ',');
			foreach ($arData as $e)
			{
				
				// Zeilenumbrüche entfernen
				if (get_option('wpsg_impexp_clearlinebreak') === '1')
				{
					foreach ($e as $k => $v) { $e[$k] = preg_replace('/\r|\n/', '', $v); }										
				}
				foreach ($e as $k => $v) {
					if (($k == 'budget') || ($k == 'wpsg_mod_statistics_long') || ($k == 'wpsg_mod_statistics_lat'))
						$e[$k] = str_replace('.', ',', $e[$k]);
				}
				
				fputcsv($fp, $e, ',', '"');
				//fputcsv($fp, $e, ';', '"');
				
			}
			fclose($fp);
			
			header('Content-type: application/download');
			header('Content-Disposition:inline; filename="wpsg_customerexport.csv"');
			header('Expires: 0');
			header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
			header('Pragma:public');
				 
			readfile($path.'/wpsg_customerexport.csv');
			die(); 
			
		} // private function be_exportAction()
		
		/**
		 * Importiert die Kunden aus der CSV Datei
		 */
		private function be_importAction()
		{
			// Import WPSG_TBL_KU und WPSG_TBL_ADRESS
			if (isset($_REQUEST['wpsg_import']) && file_exists($_FILES['wpsg_importfile']['tmp_name']))
			{
				
				// Import starten 
 
				$nImported = 0;
				
				$keys = array();
				$handle = fopen($_FILES['wpsg_importfile']['tmp_name'], "r");  
				
				$i = 0;
				while (($row = fgetcsv ($handle, 0, ",")) !== FALSE ) 
				{
					
					if ($i > 0)
					{
						
						$data = array();
						foreach ($keys as $k => $k_name)
						{							
							$data[$k_name] = wpsg_q($row[$k]);
						}

						//if ($data['id'] <= 0) unset($data['id']);

						// Daten splitten in Kunden und Adresse
						// K: knr,paypal_payer_id,email,geb,ustidnr,custom,wpsg_mod_statistics_long,wpsg_mod_statistics_lat,
						// adress_id,passwort_saltmd5,comment,wp_user_id,status,group_id,budget,kid
						$kdata['id'] = $data['kid'];
						$kdata['knr'] = wpsg_q($data['knr']);
						$kdata['paypal_payer_id'] = wpsg_q($data['paypal_payer_id']);
						$kdata['email'] = wpsg_q($data['email']);
						$kdata['geb'] = wpsg_q($data['geb']);
						$kdata['ustidnr'] = wpsg_q($data['ustidnr']);
						$kdata['custom'] = wpsg_q($data['custom']);
						$kdata['wpsg_mod_statistics_long'] = wpsg_q(str_replace(',', '.', $data['wpsg_mod_statistics_long']));
						$kdata['wpsg_mod_statistics_lat'] = wpsg_q(str_replace(',', '.', $data['wpsg_mod_statistics_lat']));
						$kdata['adress_id'] = wpsg_q($data['adress_id']);
						$kdata['passwort_saltmd5'] = wpsg_q($data['passwort_saltmd5']);
						$kdata['comment'] = wpsg_q($data['comment']);
						$kdata['wp_user_id'] = wpsg_q($data['wp_user_id']);
						$kdata['status'] = wpsg_q($data['status']);
						$kdata['group_id'] = wpsg_q($data['group_id']);
						$kdata['budget'] = wpsg_q(str_replace(',', '.', $data['budget']));
						
						// A: id,title,name,vname,firma,fax,strasse,plz,ort,land,tel,cdate,nr
						$adata['id'] = $data['id'];
						$adata['title'] = $data['title'];
						$adata['name'] = wpsg_q($data['name']);
						$adata['vname'] = wpsg_q($data['vname']);
						$adata['firma'] = wpsg_q($data['firma']);
						$adata['fax'] = wpsg_q($data['fax']);
						$adata['strasse'] = wpsg_q($data['strasse']);
						$adata['plz'] = wpsg_q($data['plz']);
						$adata['ort'] = wpsg_q($data['ort']);
						$adata['land'] = $data['land'];
						$adata['tel'] = wpsg_q($data['tel']);
						$adata['cdate'] = 'NOW()';
						$adata['nr'] = wpsg_q($data['nr']);
						
						// Alte Sachen lösche ich vor dem Import mit der Übergebenen ID !
						
						$this->shop->callMods('customer_delete_pre', array(&$kdata['id'], 'no'));
						
						// Kunden löschen
						$this->db->Query("DELETE FROM `".WPSG_TBL_KU."` WHERE `id` = '".wpsg_q($kdata['id'])."'");
						$this->db->Query("DELETE FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($adata['id'])."'");
						
						$nImported ++;
	 
						$k_id = $this->db->importQuery(WPSG_TBL_KU, wpsg_q($kdata), true);
						if ($kdata['adress_id'] > 0)
							$a_id = $this->db->importQuery(WPSG_TBL_ADRESS, wpsg_q($adata), true);
						
					}
					else
					{

						// Schlüssel erzeugen
						$keys = $row;
					
					}
					
					$i ++;
					
			    }
			    
			    fclose($handle);
				
			    $this->shop->addBackendMessage(wpsg_translate(__('#1# Kunden wurden importiert.', 'wpsg'), $nImported));
			    die($this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer&amp;action=index'));
			    
			}
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/import.phtml');
			
		} // private function be_importAction()
		
		/**
		 * Bearbeitung eines Kunden im Backend
		 */
		private function be_editAction()
		{
			
			$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
			$this->shop->view['arTitles'] = explode('|', $this->shop->view['pflicht']['anrede_auswahl']);
			
			$this->shop->view['data'] = $this->db->fetchRow("
				SELECT
					K.*,
					(SELECT SUM(`price_gesamt`) FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id`) AS `umsatz`,
					(SELECT O.`cdate` FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id` ORDER BY O.`cdate` DESC LIMIT 1) AS `lastorder`,
					(SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id`) AS `countOrder`
				FROM
					`".WPSG_TBL_KU."` AS K
				WHERE
					K.`id` = '".wpsg_q($_REQUEST['edit_id'])."'
			");
			
			if ($this->shop->view['data']['adress_id'] != 0)
				$this->shop->view['data'] = array_merge($this->shop->view['data'], $this->db->fetchRow("
					SELECT
						KA.*
					FROM
						`".WPSG_TBL_ADRESS."` AS KA
					WHERE
						KA.`id` = '".wpsg_q($this->shop->view['data']['adress_id'])."'
				"));
				
			$this->shop->view['data']['id']= wpsg_q($_REQUEST['edit_id']);
			
			$this->shop->view['data']['custom'] = @unserialize($this->shop->view['data']['custom']);
			$this->shop->view['arLand'] = $this->db->fetchAssocField("
				SELECT L.`id`, L.`name` FROM `".WPSG_TBL_LAND."` AS L ORDER BY `name` ASC
			", "id", "name");
			
			$this->shop->view['oCustomer'] = $this->shop->cache->loadCustomerObject($_REQUEST['edit_id']);

			if ($this->shop->hasMod('wpsg_mod_customergroup'))
			{
			
				$this->shop->view['arCustomergroup'] = wpsg_array_merge(array('0' => __('Unzugeordnet', 'wpsg')), $this->db->fetchAssocField("
					SELECT
						KG.`id`, CONCAT(KG.`name`, ' (ID:', KG.`id`, ')') AS `name`
					FROM
						`".WPSG_TBL_KG."` AS KG
					ORDER BY
						KG.`name` ASC
				", "id", "name"));
				
			}
			
			
			if ($this->shop->hasMod('wpsg_mod_statistics'))
			{
			
				$arStatusAbgeschlossen = wpsg_trim(explode(',', $this->shop->get_option('wpsg_mod_statistics_status')));
				
				$this->shop->view['amountAll'] = $this->shop->view['oCustomer']->getOrderAmount();
				$this->shop->view['amountStorno'] = $this->shop->view['oCustomer']->getOrderAmount(500);				
				
				if (wpsg_isSizedArray($arStatusAbgeschlossen))
					$this->shop->view['amountPayed'] = $this->shop->view['oCustomer']->getOrderAmount($arStatusAbgeschlossen);
				else
					$this->shop->view['amountPayed'] = 0;
				
			}
			 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/edit.phtml');
			
		} // private function be_editAction()
		
		/**
		 * Anlegen eines Kunden im Backend
		 */
		private function be_addAction()
		{
			
			$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
			$this->shop->view['arTitles'] = explode('|', $this->shop->view['pflicht']['anrede_auswahl']);
			$this->shop->view['data'] = array(
				'status' => '1'
			);
			$this->shop->view['arLand'] = $this->db->fetchAssocField("
				SELECT L.`id`, L.`name` FROM `".WPSG_TBL_LAND."` AS L ORDER BY `name` ASC
			", "id", "name");

			if ($this->shop->hasMod('wpsg_mod_customergroup'))
			{

				$this->shop->view['arCustomergroup'] = wpsg_array_merge(array('0' => __('Unzugeordnet', 'wpsg')), $this->db->fetchAssocField("
					SELECT
						KG.`id`, CONCAT(KG.`name`, ' (ID:', KG.`id`, ')') AS `name`
					FROM
						`".WPSG_TBL_KG."` AS KG
					ORDER BY
						KG.`name` ASC
				", "id", "name"));

			}

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/edit.phtml');
			
		} // private function be_addAction()
		
		/**
		 * Löscht einen Kunden aus der Datenbank
		 */
		private function be_delAction() {
			
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			
			$customer_id = intval($_REQUEST['edit_id']);
			
			$this->shop->callMods('customer_delete_pre', array(&$_REQUEST['edit_id'], 'delete'));
			
			$a = $this->db->fetchRow("
				SELECT
					`adress_id`
				FROM
					`".WPSG_TBL_KU."`
				WHERE
					`id` = '".wpsg_q($customer_id)."'
			");
			
			if ($a['adress_id'] != 0) {
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($a['adress_id'])."'");
				
			}
			
			$this->db->UpdateQuery(WPSG_TBL_KU, ['deleted' => '1'], " `id` = '".wpsg_q($customer_id)."' ");
			//$this->db->Query("DELETE FROM `".WPSG_TBL_KU."` WHERE `id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			
			$this->shop->addBackendMessage(__('Kunde erfolgreich gelöscht.', 'wpsg'));
			
			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer'); 
			
		} // private function be_delAction()
		
		/**
		 * Wird beim speichern des Kunden im Backend aufgerufen
		 */
		private function be_saveAction() {

			if (isset($_REQUEST['edit_id']) && !wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw new \Exception(__('Ungültige ID beim specihern des Kunden übergeben.', 'wpsg'));
			
			$data = [];
			
			wpsg_checkRequest('knr', [WPSG_SANITIZE_TEXTFIELD], __('Kundennummer', 'wpsg'), $data);
			wpsg_checkRequest('email', [WPSG_SANITIZE_EMAIL, ['allowEmpty' => true]], __('E-Mail', 'wpsg'), $data);
			wpsg_checkRequest('ustidnr', [WPSG_SANITIZE_USTIDNR, ['allowEmpty' => true]], __('UStIdNr.', 'wpsg'), $data);
			wpsg_checkRequest('comment', [WPSG_SANITIZE_TEXTAREA], __('Kundenkommentar', 'wpsg'), $data);
			wpsg_checkRequest('status', [WPSG_SANITIZE_TEXTAREA], __('Status', 'wpsg'), $data);
			
			if (isset($data['geb'])) {
				
				wpsg_checkRequest('geb', [WPSG_SANITIZE_DATE, ['allowEmpty' => true]], __('Geburtsdatum', 'wpsg'), $data);
				
				$data['geb'] = wpsg_toDate($data['geb']);
				
			}
						
			if ($this->shop->hasMod('wpsg_mod_customergroup')) {
				
				wpsg_checkRequest('group_id', [WPSG_SANITIZE_INT], __('Produktgruppe', 'wpsg'),$data);
				
			}
			
			// Adressdaten
			$adata = [
				'cdate' => 'NOW()'
			];
						 
			if (isset($_REQUEST['title'])) {
				
				$arTitles = explode('|', $this->shop->loadPflichtFeldDaten()['anrede_auswahl']);
				
				wpsg_checkRequest('title', [WPSG_SANITIZE_VALUES, array_keys($arTitles)], __('Anrede', 'wpsg'), $adata);
				
			}
			
			wpsg_checkRequest('name', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Name', 'wpsg'), $adata);
			wpsg_checkRequest('vname', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Vorname', 'wpsg'), $adata);
			wpsg_checkRequest('firma', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Firma', 'wpsg'), $adata);
			wpsg_checkRequest('fax', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Fax', 'wpsg'), $adata);
			wpsg_checkRequest('strasse', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Straße', 'wpsg'), $adata);
			
			if (isset($_REQUEST['nr'])) wpsg_checkRequest('nr', [WPSG_SANITIZE_TEXTFIELD], __('Nr', 'wpsg'), $adata);
			
			wpsg_checkRequest('plz', [WPSG_SANITIZE_ZIP, ['allowEmpty' => true]], __('PLZ', 'wpsg'), $adata);
			wpsg_checkRequest('ort', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Ort', 'wpsg'), $adata);
			wpsg_checkRequest('land', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Land', 'wpsg'), $adata);
			wpsg_checkRequest('tel', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Telefon', 'wpsg'), $adata);
						
			$data['custom'] = @serialize($_REQUEST['custom']);

			if (isset($_REQUEST['deleted'])) {
				
				wpsg_checkRequest('deleted', [WPSG_SANITIZE_INT], __('Gelöscht', 'wpsg'), $_REQUEST);
				
			}

			wpsg_checkRequest('password1', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Passwort', 'wpsg'), $_REQUEST);
			wpsg_checkRequest('password2', [WPSG_SANITIZE_TEXTFIELD, ['allowEmpty' => true]], __('Passwort Wiederholung', 'wpsg'), $_REQUEST);
			
			if (wpsg_isSizedString($_REQUEST['password1']) || wpsg_isSizedString($_REQUEST['password2'])) {
			
				if ($_REQUEST['password1'] === $_REQUEST['password2'] && trim($_REQUEST['password1']) != '') {
					
					$data['passwort_saltmd5'] = $this->hashString($_REQUEST['password1']);
									
					$this->shop->addBackendMessage(__('Passwort wurde erfolgreich geändert.', 'wpsg'));
									
				} else if (trim($_REQUEST['password1']) != '' || trim($_REQUEST['password2']) != '') {
					
					$this->shop->addBackendError(__('Passwort wurde nicht geändert, da die Wiederholung nicht übereinstimmte.', 'wpsg'));
					
				}
				
			}
			
			$this->shop->callMods('wpsg_mod_customer_save', array(&$data));
			
			if (wpsg_getInt($_REQUEST['edit_id']) > 0) {
				 
				if (wpsg_isSizedInt($_REQUEST['info-mail'])) {
					 			 
					$this->activateMail($data); 
					
				}
				
				if ($_REQUEST['password1'] === $_REQUEST['password2'] && trim($_REQUEST['password1']) != '') {
					
					$this->shop->callMods('customer_updatePwd', array(&$_REQUEST['edit_id'], &$_REQUEST['password1']));
					
				}

				$a = $this->db->fetchRow("
					SELECT
						`adress_id`
					FROM
						`".WPSG_TBL_KU."`
					WHERE
						`id` = '".wpsg_q($_REQUEST['edit_id'])."'
				");
				
				if ($a['adress_id'] == 0) {
									
					$data['adress_id'] = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adata);
					
				} else {
					
					$this->db->UpdateQuery(WPSG_TBL_ADRESS, $adata, "`id` = '".wpsg_q($a['adress_id'])."'");
					
				}
				
				$this->db->UpdateQuery(WPSG_TBL_KU, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."' ");

			} else {
								
				$data['adress_id'] = $this->db->ImportQuery(WPSG_TBL_ADRESS, $adata);
				
				$kunde_id = $this->db->ImportQuery(WPSG_TBL_KU, $data);
				
				$_REQUEST['edit_id'] = $kunde_id;
				
				$knr = $this->shop->buildKNR($kunde_id);
				
				$this->shop->cache->clearKundenCache($kunde_id);
					
				$this->db->UpdateQuery(WPSG_TBL_KU, array(
					'knr' => wpsg_q($knr),
					'status' => get_option('wpsg_page_mod_kundenverwaltung_status')
				), "`id` = '".wpsg_q($kunde_id)."'");
				
				if ((wpsg_getStr($_REQUEST['password1']) == '') || (wpsg_getStr($_REQUEST['password2']) == '') || 
					(wpsg_getStr($_REQUEST['email']) == '') || (wpsg_getStr($_REQUEST['password1']) != wpsg_getStr($_REQUEST['password2'])))
				{
					
					//$this->shop->addBackendMessage(__('WP-User wurde nicht angelegt.', 'wpsg'));
					
				} else {
					
					$this->shop->callMods('customer_created', array(&$_REQUEST['edit_id'], &$_REQUEST['password1']));
					
				}
				
			}
			
			$this->shop->addBackendMessage(__('Kunde wurde erfolgreich gespeichert.', 'wpsg'));
			
			if (isset($_REQUEST['submit_index'])) $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Customer&action=index');
			else $this->shop->redirect(wpsg_admin_url('Customer', 'edit', ['edit_id' => $_REQUEST['edit_id']]));
			
		} // private function be_saveAction()
		
        private function be_index_setAccount()
        {
 
            if (isset($_REQUEST['submit-button']) || isset($_REQUEST['submit_do']))
            {

                $this->shop->view['targetCustomer'] = wpsg_customer::find(array(
                    's' => wpsg_xss($_REQUEST['filter']['s'])
                ));
            }
                       
            $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/index_setAccount.phtml');
                        
        }
        
		/**
		 * Übersicht über die Kunden im Backend
		 */
		private function be_indexAction()
		{

		    if (isset($_REQUEST['wpsg_do']) && $_REQUEST['wpsg_do'] !== '-1')
            {
                
                if ($_REQUEST['wpsg_do'] === 'setAccount') return $this->be_index_setAccount();
                
            }
            
			$nPerPage = $this->shop->get_option('wpsg_mod_kundenverwaltung_perpage');
			if ($nPerPage <= 0) $nPerPage = 10;

			$this->shop->view['arFilter'] = array(
				'order' => 'cdate',
				'ascdesc' => 'ASC',
				'status' => '0',
				'page' => '1'
			);
			$this->shop->view['arData'] = array();
			$this->shop->view['pages'] = 1;

			if (wpsg_isSizedArray($_REQUEST['filter'])) {

				if (!wpsg_checkInput($_REQUEST['filter']['s'], WPSG_SANITIZE_TEXTFIELD)) {
					
					unset($_REQUEST['filter']['s']);
					
				}
				
				if (isset($_REQUEST['group_id']) && !wpsg_checkInput($_REQUEST['filter']['group_id'], WPSG_SANITIZE_INT)) {
					
					unset($_REQUEST['filter']['group_id']);
					
				}
				
				$this->shop->view['arFilter'] = $_REQUEST['filter'];

			}
			else if (wpsg_isSizedArray($_SESSION['wpsg']['backend']['customer']['arFilter']))
			{

				$this->shop->view['arFilter'] = $_SESSION['wpsg']['backend']['customer']['arFilter'];
                
			}

            $this->shop->view['hasFilter'] = wpsg_customer::hasFilter($this->shop->view['arFilter']); 
			
			$this->shop->view['countAll'] = wpsg_customer::count($this->shop->view['arFilter']);

			if (wpsg_isSizedInt($_REQUEST['seite'])) $this->shop->view['arFilter']['page'] = $_REQUEST['seite'];

			$this->shop->view['pages'] = ceil($this->shop->view['countAll'] / $nPerPage);
			if ($this->shop->view['arFilter']['page'] <= 0 || $this->shop->view['arFilter']['page'] > $this->shop->view['pages']) $this->shop->view['arFilter']['page'] = 1;

			$this->shop->view['arFilter']['limit'] = array(($this->shop->view['arFilter']['page'] - 1) * $nPerPage, $nPerPage);

			// Filter speichern
			$_SESSION['wpsg']['backend']['customer']['arFilter'] = $this->shop->view['arFilter'];

			$this->shop->view['arData'] = wpsg_customer::find($this->shop->view['arFilter']);

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/index.phtml');
			 			
		} // private function be_indexAction()
				
		/**
		 * Logt den Kunden aus
		 */
		private function logout()
		{
			
			$kunde_data = $this->shop->cache->loadKunden($_SESSION['wpsg']['checkout']['id']);
			
			if (wpsg_isSizedInt($kunde_data['wp_user_id']))
			{
				
				wp_logout();
				
			}
			
			unset($_SESSION['wpsg']['checkout']);

			// Ich lösche auch eine eventuell temporär existierende Bestellung, damit der Kunde nicht mehr geleert wird
			unset($_SESSION['wpsg']['order_id']);

		} // private function logout()
		                
		/**
		 * Logt den Kunden mit der ID $kunde_id ein
		 * Der Kunde sollte in der Datenbank existieren das muss vorher abgefragt werden
		 */
		public function login($kunde_id)
		{
			
			$kunde = $this->db->fetchRow("
				SELECT
					C.`id`,
					CA.`title` AS `title`,
					CA.`name` AS `name`,
					CA.`vname` AS `vname`,
					CA.`firma` AS `firma`,
					CA.`fax` AS `fax`,
					CA.`strasse` AS `strasse`,
					CA.`nr` AS `nr`,
					CA.`plz` AS `plz`,
					CA.`ort` AS `ort`,
					CA.`land` AS `land`,
					CA.`tel` AS `tel`,
					C.`geb`, C.`ustidnr`, C.`email`, C.`id`, C.`wp_user_id`, C.`custom` AS `customin`
				FROM
					`".WPSG_TBL_KU."` AS C
						LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = C.`adress_id`)
				WHERE
					C.`id` = '".wpsg_q($kunde_id)."' AND
					C.`deleted` != '1' AND
					C.`status` != '0' AND C.`status` != '-1'
			");
			
			if ($kunde['id'] != $kunde_id) return;
			
			$this->db->UpdateQuery(WPSG_TBL_KU, ['last_login' => 'NOW()'], " `id` = '".wpsg_q($kunde['id'])."' ");
			
			$kunde['custom'] = @unserialize($kunde['customin']);
			
			if (!is_array($_SESSION['wpsg']['checkout'])) $_SESSION['wpsg']['checkout'] = array();
		
			if (wpsg_isSizedInt($_SESSION['wpsg']['order_id']))
			{
			
				$temp_k_id = $this->db->fetchOne("SELECT `k_id` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($_SESSION['wpsg']['order_id'])."' ");
			
				if (wpsg_isSizedInt($temp_k_id) && $temp_k_id != $kunde_id)
				{
			
					// Temporären Kunden löschen
					// Zur Sicherheit check ich mal ob es Bestellungen mit ihm gibt.
					$count_order = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` WHERE `k_id` = '".wpsg_q($temp_k_id)."'  AND `id` != '".wpsg_q($_SESSION['wpsg']['order_id'])."' ");
			
					if ($count_order > 0) throw new \wpsg\Exception(__('Die Daten eines Bestellobjekts konnten nicht geladen werden', 'wpsg'));
					else
					{
			
						$oCustomer = wpsg_customer::getInstance($temp_k_id);
						$oCustomer->delete();
			
					}
			
				}
			
			}
			
			$_SESSION['wpsg']['checkout'] = wpsg_array_merge($_SESSION['wpsg']['checkout'], wpsg_xss(array(
				'id' => $kunde['id'],
				'firma' => $kunde['firma'],
				'title' => $kunde['title'],
				'vname' => $kunde['vname'],
				'name' => $kunde['name'],
				'email' => $kunde['email'],
				'email2' => $kunde['email'],
				'custom' => $kunde['custom'],
				'geb' => wpsg_fromDate($kunde['geb']),
				'fax' => $kunde['fax'],
				'tel' => $kunde['tel'],
				'strasse' => $kunde['strasse'],
				'nr' => $kunde['nr'],
				'plz' => $kunde['plz'],
				'ort' => $kunde['ort'],
				'land' => $kunde['land'],
				'ustidnr' => $kunde['ustidnr']
			)));
			
			// Sonst werden die Werte in der Session durch die leeren Felder ersetzt wenn onepagecheckout aktiv
			unset($_REQUEST['checkout']);
			
			if ($this->shop->get_option('wpsg_mod_kundenverwaltung_wpuser') == '1')
			{
				
				/*
				$wp_user_id = $kunde['wp_user_id'];
				
				if (wpsg_isSizedInt($wp_user_id))
				{
					
					$creds = array();
					$creds['user_login'] = $username;
					$creds['user_password'] = $password;
					$creds['remember'] = true;
					
					$user = wp_signon($creds, false);
					
					if (is_wp_error($user))
					{
						
						$this->shop->addFrontendError(__('Beim einloggen des Wordpress Users gab es ein Problem: ', 'wpsg').$user->get_error_message());
						
					}
					    
				}
				*/
				
				$wp_user_id = $kunde['wp_user_id'];
				
				if (wpsg_isSizedInt($wp_user_id))
				{
				
					$loggedin = wp_set_auth_cookie($wp_user_id);
										
				}
				
			}
			
		} // public function login($kunde_id)
		
		/**
		 * Gibt den Hash für das Passwort zurück
		 */
		public function hashString($pwd)
		{
			
			return md5(crypt($pwd, $this->shop->get_option("wpsg_salt")));
			
		} // private function hashString($pwd)
		
		/**
		 * Gibt die ID des Users zurück wenn eingeloggt sonst false
		 */
		public function isLoggedIn()
		{
				
			if (isset($_SESSION['wpsg']['checkout']['id']) && $_SESSION['wpsg']['checkout']['id'] > 0)
			{
				
				return $_SESSION['wpsg']['checkout']['id'];
				
			}
			else 
			{
				
				return false;
				
			}
			
		} // public function isLoggedIn()

		/**
		 * Gibt die aktuelle ID der Kundengruppe zurück oder false wenn nicht eingelogt oder zugeordnet
		 */
		public function getCustomerGroup()
		{
			
			if (!$this->shop->hasMod('wpsg_mod_customergroup')) return false;
			
			$customer_id = $this->isLoggedIn();
			
			if ($customer_id !== false)
			{

				$customer_db = $this->shop->cache->loadKunden($customer_id);
				
				if (wpsg_isSizedInt($customer_db['group_id']))
					return $customer_db['group_id'];
												
			}

			return false;
			
		} // public function getCustomerGroup()
		
		public function getCustomerGroupObject($customer_group_id)
		{
			
			if (!$this->shop->hasMod('wpsg_mod_customergroup')) return false;
			
			if (!array_key_exists($customer_group_id, $this->customerGroupCache))
			{
			
				$oCustomerGroup = new wpsg_customergroup();
				$oCustomerGroup->load($customer_group_id);
			
				$this->customerGroupCache[$customer_group_id] = $oCustomerGroup;
				
			}
			
			return $this->customerGroupCache[$customer_group_id];
			
		} // public function getCustomerGroupObject($customer_group_id)
		
		/**
		 * Generiert ein neues Passwort und gibt es zurück
		 */
		public function createPasswort($length = 8)
		{ 
			
			return $this->shop->getCode($length);
    		
		} // private function createPasswort($anz = 8)
        
		/**
		 * versendet eine Mail nach der Registrierung
		 * Enter description here ...
		 */
		private function registerMail(&$data)
		{

			$this->shop->view['kunde'] = $data;
			
			// Land mit Name ausgeben
			$this->shop->view['kunde']['land'] = $this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_LAND."` WHERE `id` = '".$data['land']."' ");
			
			$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_register.phtml', false);

			if ($this->shop->get_option('wpsg_htmlmail') === '1')
			{
				
				$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_register_html.phtml', false);
				
			}
			else
			{
				
				$mail_html = false;
				
			}
			
			$this->shop->sendMail($mail_text, $data['email'], 'register', array(), false, false, $mail_html);	
		}
		
		/**
		 * versendet eine Mail bei Aktivierung des Accounts
		 * Enter description here ...
		 */
		private function activateMail(&$data)
		{

			$this->shop->view['kunde'] = $data;
			
			if ($this->shop->view['kunde']['status'] == '1')
			{
				$this->shop->view['kunde']['status'] = 'aktiv';
			}
			
			if ($this->shop->view['kunde']['status'] == '0')
			{
				$this->shop->view['kunde']['status'] = 'inaktiv';
			}

			$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_activate.phtml', false);
					
			if ($this->shop->get_option('wpsg_htmlmail') === '1')
			{
				
				$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/mail_activate_html.phtml', false);
				
			}
			else 
			{
				
				$mail_html = false;
				
			}
			
			$this->shop->sendMail($mail_text, $data['email'], 'activate', array(), false, false, $mail_html);
				
		}
		
	} // class wpsg_mod_kundenverwaltung extends wpsg_mod_basic

?>