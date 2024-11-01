<?php

	/**
	 * AdminController für Backend
	 */
	class wpsg_AdminController extends wpsg_SystemController
	{
		
		const SHOPDATA_EU_GERMANY = '0';
		const SHOPDATA_EU_EU = '1';
		const SHOPDATA_EU_WORLD = '2';
		
		/**
		 * Übernimmt das Routing
		 */
		public function dispatch()
		{

			parent::dispatch();

			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'module')
			{

				$this->moduleAction();

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'hilfe')
			{

				$this->hilfeAction();

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'migratemwst')
			{

				$this->migratemwstAction();

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'ueber')
			{

				$this->ueberAction();

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'news')
			{

				$this->newsAction();

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'registrierung')
			{

				if (!current_user_can('wpsg_lizence')) die(__('Kein Zugriff', 'wpsg'));

				$this->registrierungAction();

			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'resetMessages'))
            {

                $this->update_option('wpsg_msgHidden', array());

                $this->addBackendMessage(__('Systemmeldungen wurden zurückgesetzt.', 'wpsg'));
                $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=ueber&subaction=systemcheck');

            }
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'clearMessage')
			{

                $arMsgHidden = $this->get_option('wpsg_msgHidden');
                if (!wpsg_isSizedArray($arMsgHidden)) $arMsgHidden = array();

                $arMsgHidden[] = $_REQUEST['msg_key'];

                $this->update_option('wpsg_msgHidden', $arMsgHidden);

                die("1");

			}
			else
			{

				$this->indexAction();

			}

		} // public function dispatch()
		
		public function init()
		{
		
			// Option gab es früher nicht und wird hier auf den Default Wert korrigiert.
			if ($this->shop->get_option('wpsg_shopdata_eu') === false) $this->shop->update_option('wpsg_shopdata_eu', self::EXPIRE_GERMANY);
			if ($this->shop->get_option('wpsg_shopdata_eu') === false) $this->shop->update_option('wpsg_shopdata_eu', self::EXPIRE_EU);
			if ($this->shop->get_option('wpsg_shopdata_eu') === false) $this->shop->update_option('wpsg_shopdata_eu', self::EXPIRE_WORLD);
		
		} // public function init()
		
		/**
		 * Index des Backends
		 */
		public function indexAction() {

			$this->shop->view = array(
				'actionName' => 'index',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/konfiguration.phtml'
			);

			$this->shop->view['arSubAction'] = array();
			$this->shop->view['arSubAction']['allgemein'] = array(
				'Menutext' => __('Allgemein', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/konfiguration.phtml'
			);
			$this->shop->view['arSubAction']['shopdata'] = array(
				'Menutext' => __('Shopinfo', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/shopdata.phtml'
			);
			$this->shop->view['arSubAction']['extended'] = array(
				'Menutext' => __('Erweitert', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/extended.phtml'
			);

			if (function_exists('icl_object_id'))
			{

				$this->shop->view['arSubAction']['wpml'] = array(
					'Menutext' => __('WPML Einstellungen', 'wpsg'),
					'subTemplate' => WPSG_PATH_VIEW.'/admin/wpml.phtml'
				);

			}

			$this->shop->view['arSubAction']['presentation'] = array(
				'Menutext' => __('Darstellung', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/presentation.phtml'
			);
			$this->shop->view['arSubAction']['loadsavesettings'] = array(
				'Menutext' => __('Einstellungen sichern', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/loadsavesettings.phtml'
			);

			if ($this->shop->isMultiBlog() && is_super_admin())
			{

				$this->shop->view['arSubAction']['blognetzwerk'] = array(
					'Menutext' => __('Blognetzwerk', 'wpsg'),
					'subTemplate' => WPSG_PATH_VIEW.'/admin/blognetzwerk.phtml'
				);

				// Wenn da später mal mehr drin ist als der Multiblog Pfad dann sollte das aus dem If raus!
				$this->shop->view['arSubAction']['path'] = array(
					'Menutext' => __('Pfade', 'wpsg'),
					'subTemplate' => WPSG_PATH_VIEW.'/admin/path.phtml'
				);

			}
			$this->shop->view['arSubAction']['dataprotection'] = array(
					'Menutext' => __('Datenschutz', 'wpsg'),
					'subTemplate' => WPSG_PATH_VIEW.'/admin/dataprotection.phtml'
			);
			$this->shop->view['arSubAction']['kalkulation'] = array(
				'Menutext' => __('Preiskalkulation', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/kalkulation.phtml'
			);
			$this->shop->view['arSubAction']['access'] = array(
				'Menutext' => __('Berechtigungen', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/access.phtml'
			);
			$this->shop->view['arSubAction']['widerrufsbelehrung'] = array(
				'Menutext' => __('Widerruf', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/widerrufsbelehrung.phtml'
			);
			$this->shop->view['arSubAction']['includes'] = array(
				'Menutext' => __('Bibliotheken/Includes', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/includes.phtml'
			);
			$this->shop->view['arSubAction']['seiten'] = array(
				'Menutext' => __('Seitenkonfiguration', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/seiten.phtml'
			);
			//$this->shop->view['arSubAction']['customerpreset'] = array(
			//	'Menutext' => __('Kundenvoreinstellungen', 'wpsg'),
			//	'subTemplate' => WPSG_PATH_VIEW.'/admin/customerpreset.phtml'
			//);
			$this->shop->view['arSubAction']['kundendaten'] = array(
				'Menutext' => __('Kundendaten', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/kundendaten.phtml'
			);
			$this->shop->view['arSubAction']['emailconf'] = array(
				'Menutext' => __('E-Mail Konfiguration', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/emailconf.phtml'
			);
			$this->shop->view['arSubAction']['vz'] = array(
				'Menutext' => __('Versandzonen', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/versandzonen.phtml'
			);
			$this->shop->view['arSubAction']['laender'] = array(
				'Menutext' => __('Länderverwaltung', 'wpsg'),
				'subTemplate' => WPSG_PATH_VIEW.'/admin/laender.phtml'
			);

			if (!$this->shop->isMultiBlog() || is_super_admin())
			{

				$this->shop->view['arSubAction']['deinstallieren'] = array(
					'Menutext' => __('Deinstallieren', 'wpsg'),
					'subTemplate' => WPSG_PATH_VIEW.'/admin/deinstall.phtml'
				);

			}
			
			if (isset($_REQUEST['submit'])) { $this->submitAction(); }
			
			if (isset($_REQUEST['subaction']))
			{

				if (method_exists($this, $_REQUEST['subaction'].'Action')) { call_user_func(array($this, $_REQUEST['subaction'].'Action')); }

				$this->shop->view['subAction'] = $_REQUEST['subaction'];
				$this->shop->view['subTemplate'] = $this->shop->view['arSubAction'][$_REQUEST['subaction']]['subTemplate'];

			}
			else
			{
				
				if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'update') { $this->updateAction(); }
				else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'loadHelp') { $this->loadHelpAction(); }

				$this->shop->view['subAction'] = 'allgemein';

			}

			// Korrektur der Serialisierten Einstellungen beim Aufruf der Allgemein Seite
			if ($this->shop->view['subAction'] == 'allgemein')
			{

				$arLanguages = $this->shop->getStoreLanguages();

				$this->shop->view['arLanguages'] = array();

				if (wpsg_isSizedArray($arLanguages))
				{

					foreach ($arLanguages as $lang)
					{

						$this->shop->view['arLanguages'][$lang['lang']] = $lang['name'];

					}

				}

				$this->CheckAndCorrectSerOption();

			}

			$this->shop->callMods('admin_index', array($this));
			$this->shop->view['adminController'] = &$this;

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		} // public function indexAction()
 	
		/**
		 * Beim Speichern und Anzeigen der Shopdaten
		 */
		public function shopdataAction() {

			if (isset($_REQUEST['submit'])) {
				
				\check_admin_referer('wpsg-save-admin-shopdata');
				
			    $this->shop->update_option('wpsg_shopdata_name', $_REQUEST['wpsg_shopdata_name'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_owner', $_REQUEST['wpsg_shopdata_owner'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_tel', $_REQUEST['wpsg_shopdata_tel'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_fax', $_REQUEST['wpsg_shopdata_fax'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_email', $_REQUEST['wpsg_shopdata_email'], false, true, WPSG_SANITIZE_EMAIL);
			    $this->shop->update_option('wpsg_shopdata_taxnr', $_REQUEST['wpsg_shopdata_taxnr'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_ustidnr', $_REQUEST['wpsg_shopdata_ustidnr'], false, true, WPSG_SANITIZE_TEXTFIELD);

			    $this->shop->update_option('wpsg_shopdata_street', $_REQUEST['wpsg_shopdata_street'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_zip', $_REQUEST['wpsg_shopdata_zip'], false, true, WPSG_SANITIZE_TEXTFIELD);
			    $this->shop->update_option('wpsg_shopdata_city', $_REQUEST['wpsg_shopdata_city'], false, true, WPSG_SANITIZE_TEXTFIELD);

				$this->shop->update_option('wpsg_shopdata_2', $_REQUEST['wpsg_shopdata_2'], false, true, WPSG_SANITIZE_CHECKBOX);
				$this->shop->update_option('wpsg_shopdata_2_street', $_REQUEST['wpsg_shopdata_2_street'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_zip', $_REQUEST['wpsg_shopdata_2_zip'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_city', $_REQUEST['wpsg_shopdata_2_city'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_country', $_REQUEST['wpsg_shopdata_2_country'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_tel', $_REQUEST['wpsg_shopdata_2_tel'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_fax', $_REQUEST['wpsg_shopdata_2_fax'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_2_email', $_REQUEST['wpsg_shopdata_2_email'], false, true, WPSG_SANITIZE_EMAIL);
				
				$this->shop->update_option('wpsg_shopdata_eu', $_REQUEST['wpsg_shopdata_eu'], false, true, WPSG_SANITIZE_VALUES, [0, 1, 2]);
				$this->shop->update_option('wpsg_shopdata_eu_name', $_REQUEST['wpsg_shopdata_eu_name'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_tel', $_REQUEST['wpsg_shopdata_eu_tel'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_fax', $_REQUEST['wpsg_shopdata_eu_fax'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_email', $_REQUEST['wpsg_shopdata_eu_email'], false, true, WPSG_SANITIZE_EMAIL);
				$this->shop->update_option('wpsg_shopdata_eu_street', $_REQUEST['wpsg_shopdata_eu_street'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_zip', $_REQUEST['wpsg_shopdata_eu_zip'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_city', $_REQUEST['wpsg_shopdata_eu_city'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_eu_country', $_REQUEST['wpsg_shopdata_eu_country'], false, true, WPSG_SANITIZE_TEXTFIELD);
				
				/*
				$this->shop->update_option('dataprotectioncommissioner', $_REQUEST['dataprotectioncommissioner'], false, true, WPSG_SANITIZE_CHECKBOX);
				$this->shop->update_option('dataprotectioncommissioner_name', $_REQUEST['dataprotectioncommissioner_name'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('dataprotectioncommissioner_tel', $_REQUEST['dataprotectioncommissioner_tel'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('dataprotectioncommissioner_email', $_REQUEST['dataprotectioncommissioner_email'], false, true, WPSG_SANITIZE_EMAIL);
				$this->shop->update_option('dataprotectioncommissioner_baskettext', $_REQUEST['dataprotectioncommissioner_baskettext'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('dataprotectioncommissioner_mailtext', $_REQUEST['dataprotectioncommissioner_mailtext'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('dataprotectioncommissioner_texts', $_REQUEST['dataprotectioncommissioner_texts'], false, true, WPSG_SANITIZE_TEXTFIELD);
				*/
								
				$this->shop->update_option('wpsg_shopdata_bank_name', $_REQUEST['wpsg_shopdata_bank_name'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_bank_owner', $_REQUEST['wpsg_shopdata_bank_owner'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_bank_iban', $_REQUEST['wpsg_shopdata_bank_iban'], false, true, WPSG_SANITIZE_TEXTFIELD);
				$this->shop->update_option('wpsg_shopdata_bank_bic', $_REQUEST['wpsg_shopdata_bank_bic'], false, true, WPSG_SANITIZE_TEXTFIELD);

				$this->addBackendMessage(__('Shopdaten erfolgreich gespeichert.', 'wpsg'));

				$this->shop->update_option('wpsg_message_shopdata_change', false);

				if ($this->shop->get_option('wpsg_revocationform') != false && !array_key_exists('wpsg_message_shopdata', (array)$_SESSION['wpsg']['backendError']))
				{

					$this->shop->addBackendError('nohspc_'.wpsg_translate(
            			__('Die allgemeinen Daten des Shops haben sich verändert, sie sollten das <a href="#1#">Widerrufsformular</a> überprüfen bzw. neu generieren.', 'wpsg'),
						WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=widerrufsbelehrung'
					), 'wpsg_message_shopdata_change');

				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=shopdata');

			}

		} // public function shopdataAction()

		/**
		 * Lädt die Lizenz in die Datenbank wenn valide
		 */
		public function loadLicenceAction()
		{

		    $wpsg_update_data = wpsg_get_update_data(
		    	wpsg_sinput("text_field", $_REQUEST['wpsg_licence_file']), true
		    );

            if (!wpsg_isSizedArray($wpsg_update_data))
            {

                $this->addBackendError(__('Keine Verbindung zum Registrierungsserver', 'wpsg'));

            }
            else
            {

                if ($wpsg_update_data['returnCode'] === '0')
                {

                    $this->addBackendError(__('Ungültiger Lizenzcode', 'wpsg'));

                }
                else
                {

					$this->addBackendMessage(__('wpShopGermany wurde aktiviert.', 'wpsg'));
					$this->update_option('wpsg_key', $_REQUEST['wpsg_licence_file'], true, false, WPSG_SANITIZE_APIKEY);

                }

            }

            if ($this->shop->isMultiBlog()) die($this->redirect(WPSG_URL_WP.'wp-admin/network/plugins.php'));
            else die($this->redirect(WPSG_URL_WP.'wp-admin/plugins.php'));

		} // public function loadLicenceAction()

		/**
		 * Verwaltung der Registrierung
		 */
		public function registrierungAction() {

			if (wpsg_isSizedString($_REQUEST['do'], 'activatemodul')) {
				
				check_admin_referer('wpsg-admin-licence-activatemodul');
				
				try
				{
				
					if (!wpsg_isSizedString($_REQUEST['modulcode']))
					{
						
						throw new \Exception(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module');
						
					}
					else
					{

						$_REQUEST['modulcode'] = wpsg_xss($_REQUEST['modulcode']);
						$api_return = wpsg_api_call('registerModule', array($_REQUEST['modulcode']));
						
						if (!wpsg_isSizedArray($api_return) || !isset($api_return['returnCode']))
						{
							
							// API Fehler
							throw new \Exception(__('Registrierungsserver antwortet nicht.', 'wpsg'));
						
						}
						else
						{
							
							if ($api_return['returnCode'] === 0)
							{
								
								throw new \Exception(__('Ihre Lizenz ist ungültig.', 'wpsg'));
								
							}
							else if ($api_return['returnCode'] === 1)
							{
								
								throw new \Exception(__('Modulcode wurde nicht akzeptiert.', 'wpsg'));
								
							}
							else if ($api_return['returnCode'] === 2)
							{
								
								throw new \Exception(__('Modulcode wurde bereits verbraucht.', 'wpsg'));
								
							}
							else if ($api_return['returnCode'] === 3)
							{
								
								throw new \Exception(__('Modulcode wurde bereits für diesen Schlüssel verbraucht.', 'wpsg'));
								
							}
							else if ($api_return['returnCode'] === 4)
							{
																
								$this->addBackendMessage(__('Modul wurde für diesen Lizenzschlüssel aktiviert.', 'wpsg'));
								
								if (wpsg_isSizedString($_REQUEST['source'], 'licence')) $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=modulactivation');
								else $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module');
								
							}
							else
							{
								
								throw new \Exception(__('Nicht definierte API Antwort.'));
								
							}
							
						}  
						
					}
					
				}
				catch (Exception $e)
				{
					
					$this->addBackendError($e->getMessage());
					
					if (wpsg_isSizedString($_REQUEST['source'], 'licence')) $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=modulactivation');
					else $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module');
					
				}
				
			} else if (wpsg_isSizedString($_REQUEST['do'], 'saveRegister')) {

				check_admin_referer('wpsg-admin-licence-register');
				
				foreach($_REQUEST['register'] as $k => $v)
					$_REQUEST['register'][$k] = wpsg_xss($v);

				$api_return = wpsg_api_call('updateRegisterData', array($_REQUEST['register']));
					
				try
				{
					
					if ($api_return['returnCode'] === 0)
					{
						
						throw new \Exception(__('Ihre Lizenz ist ungültig.', 'wpsg'));
						
					}
					else if ($api_return['returnCode'] === 1)
					{
						
						$this->addBackendMessage(__('Daten erfolgreich aktualisiert.', 'wpsg'));
						$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung');
						
					}
					else
					{
						
						throw new \Exception(__('Nicht definierte API Antwort.'));
						
					}
					
				}
				catch (Exception $e)
				{
				
					$this->addBackendError($e->getMessage());
					$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung');
					
				}
				
			} else if (wpsg_isSizedString($_REQUEST['do'], 'domainRegister')) {
				
				check_admin_referer('wpsg-admin-licence-domainRegister');
				
				$api_return = wpsg_api_call('domainRegister', array($_SERVER['HTTP_HOST']));
					
				try
				{
					
					if ($api_return['returnCode'] === 1)
					{
						
						$this->addBackendMessage(__('Domain erfolgreich registriert.', 'wpsg'));
						$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=domaindata');
						
					}
					else if ($api_return['returnCode'] === -1)
					{
						
						throw new \Exception(__('Ihre Lizenz ist ungültig.', 'wpsg'));
						
					}
					else if ($api_return['returnCode'] === -2)
					{
						
						throw new \Exception(__('Diese Domain ist schon auf Ihren Schlüssel registriert.', 'wpsg'));
						
					}					
					else
					{
						
						throw new \Exception(__('Nicht definierte API Antwort.'));
						
					}
					
				}
				catch (Exception $e)
				{
				
					$this->addBackendError($e->getMessage());
					$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=domaindata');
					
				}
				
			} else if (wpsg_isSizedString($_REQUEST['do'], 'domainDeRegister')) {
			
				check_admin_referer('wpsg-admin-licence-domainDeRegister');
				
				$api_return = wpsg_api_call('domainDeRegister', array($_SERVER['HTTP_HOST']));
					
				try {
					
					if ($api_return['returnCode'] === 1)
					{
						
						$this->addBackendMessage(__('Domain erfolgreich unregistriert.', 'wpsg'));
						$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=domaindata');
						
					}
					else if ($api_return['returnCode'] === -1)
					{
						
						throw new \Exception(__('Ihre Lizenz ist ungültig.', 'wpsg'));
						
					}
					else if ($api_return['returnCode'] === -2)
					{
						
						throw new \Exception(__('Diese Domain ist nicht auf Ihren Schlüssel registriert.', 'wpsg'));
						
					}					
					else
					{
						
						throw new \Exception(__('Nicht definierte API Antwort.'));
						
					}
					
				} catch (Exception $e) {
				
					$this->addBackendError($e->getMessage());
					$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=domaindata');
					
				}
				
			}
			else if (wpsg_isSizedString($_REQUEST['do'], 'startDemo'))
			{
				
				$api_return = wpsg_api_call('startDemo', array($_REQUEST['modul']));
				
				try
				{
					
					if ($api_return['returnCode'] === 1)
					{
						
						$this->addBackendMessage(__('Demo Modus erfolgreich gestartet.', 'wpsg'));
												
					}
					else if (isset($api_return['returnCode']) && isset($api_return['returnMessage']))
					{
						
						throw new \Exception($api_return['returnMessage']);
												
					} 
					else
					{
						
						throw new \Exception(__('Nicht definierte API Antwort.'));
						
					}
					 
				}
				catch (Exception $e)
				{
				
					$this->addBackendError($e->getMessage());
										
				}
				
				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul='.$_REQUEST['modul']);
				
			}
			else if (wpsg_isSizedString($_REQUEST['do'], 'installModul')) 
			{

			    global $wp_filesystem;

			    // Kann die Moduldatei geschrieben werden?
                if (!is_writable(WPSG_PATH_MOD))
                {

                    ob_start();
                    $request_creds = request_filesystem_credentials(
                        WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&noheader=1&do=installModul&modul='.$modul_key.'&source='.$_REQUEST['source'],
                        '',
                        false,
                        false,
                        null
                    );
                    ob_end_clean();

                    // 1 gibt es bei Windows Rechnern zurück, da es hier egal ist
                    // Ein Array ist es wenn das Formular bereits abgeschickt wurde und die Anfrage vom Formular kommt
                    // false ist es wenn kein Zugriff ist dann muss das Formular angezeigt werden

                    if (false === $request_creds)
                    {

                        // Berechtigungen anfragen
                        $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&do=getCredentials&modul='.$_REQUEST['modul'].'&source='.$_REQUEST['source']);

                    }
                    else if (is_array($request_creds))
                    {

                        if (!WP_Filesystem($request_creds))
                        {

                            // Eingegebene Daten waren falsch
                            $this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&do=getCredentials&modul='.$_REQUEST['modul'].'&sourcce='.$_REQUEST['source']);

                        }

                    }

                }

				$bOK = $this->installModul($_REQUEST['modul']);
				$_REQUEST['source'] = wpsg_xss($_REQUEST['source']);
						
				if ($bOK === true) $this->addBackendMessage(__('Modul erfolgreich installiert.', 'wpsg'));

				if (wpsg_isSizedString($_REQUEST['source'], 'licence')) $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&subaction=modulactivation');
				else $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module');
				
			}
			else if (wpsg_isSizedString($_REQUEST['do'], 'getCredentials'))
			{

			    //https://dev4-wpshopgermany.maennchen1.de/wp-admin/admin.php?page=wpsg-Admin&action=registrierung&noheader=1&do=installModul&modul=wpsg_mod_paypalapi&source=module

                echo request_filesystem_credentials(
                    WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=registrierung&noheader=1&do=installModul&modul='.$_REQUEST['modul'].'&source='.$_REQUEST['source'],
                    '',
                    false,
                    false,
                    null
                );

                return;

            }

			$this->view['actionName'] = 'licence';

			if (wpsg_isSizedString($_REQUEST['subaction'])) $this->view['subaction'] = $_REQUEST['subaction'];
			else $this->view['subaction'] = 'registerdata';
			
			$this->render(WPSG_PATH_VIEW.'/admin/licence.phtml'); 	
			
		} // public function registrierungAction()

		/**
		 * Gibt die Liste der Versandzonen zurück
		 */
		public function vz_listAction()
		{

			$this->shop->view['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_VZ."` ORDER BY `name` ASC");

			$this->shop->render(WPSG_PATH_VIEW.'/admin/versandzonen_list.phtml');

		} // public function vz_listAction()

		/**
		 * Migriert die alten Mehrwertsteuersätze die vor 3.5 existierten in die Länder und passt die Produkte an.
		 */
		public function migratemwstAction()
		{

			if (isset($_REQUEST['submit']))
			{

				// Mehrwertsteuersätze der Produkte
				if (wpsg_isSizedArray($_REQUEST['mwst']))
				{

					foreach ($_REQUEST['mwst'] as $tax_id => $tax_key)
					{

						if ($tax_id == 0)
						{

							// Das sind die Produkte mit MwSt Sätzen die nicht mehr exitierten
							$arProductIDs = $this->db->fetchAssocField("
								SELECT
									P.`id`
								FROM
									`".WPSG_TBL_PRODUCTS."` AS P
										LEFT JOIN `".WPSG_TBL_MWST."` AS M ON (P.`mwst` = M.`id`)
								WHERE
									M.`id` IS NULL AND
									(P.`mwst_key` = '' OR P.`mwst_key` IS NULL)
							");

							$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array('mwst_key' => wpsg_q($tax_key)), " `id` IN (".implode(',', wpsg_q($arProductIDs)).") ");

						}
						else
						{

							$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, array('mwst_key' => wpsg_q($tax_key)), " (`mwst_key` = '' OR `mwst_key` IS NULL) AND `mwst` = '".wpsg_q($tax_id)."' ");

						}

					}

				}

				// Konfiguration speichern
				if (wpsg_isSizedArray($_REQUEST['conf']))
				{

					foreach ($_REQUEST['conf'] as $conf_key => $conf_value)
					{

						// TODO: Check wether $conf_value could be a numeric value
						$this->shop->update_option($conf_key, $conf_value, false, false, "text_field");

					}

				}

				// Zahlungsvarianten
				if (wpsg_isSizedArray($_REQUEST['pv']))
				{

					foreach ($_REQUEST['pv'] as $pv_id => $tax_key)
					{

						$this->db->UpdateQuery(WPSG_TBL_ZV, array('mwst_key' => wpsg_q($tax_key)), " `id` = '".wpsg_q($pv_id)."' ");

					}

				}

				// Versandarten
				if (wpsg_isSizedArray($_REQUEST['sv']))
				{

					foreach ($_REQUEST['sv'] as $sv_id => $tax_key)
					{

						$this->db->UpdateQuery(WPSG_TBL_VA, array('mwst_key' => wpsg_q($tax_key)), " `id` = '".wpsg_q($sv_id)."' ");

					}

				}

				$this->shop->addBackendMessage(__('Migration der MwSt. Sätze erfolgreich durchgeführt.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=hilfe');

			}

			$this->shop->view = array(
				'actionName' => 'hilfe',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/migratemwst.phtml'
			);

			$arMwStTable = $this->db->fetchAssocField("SHOW TABLES LIKE '".WPSG_TBL_MWST."'");
			if (in_array(WPSG_TBL_MWST, $arMwStTable))
			{

				$this->shop->view['distinctProductTax'] = $this->db->fetchAssoc("
					SELECT
						M.*,
						COUNT(P.`mwst`) AS `count_product`
					FROM
						`".WPSG_TBL_PRODUCTS."` AS P
							LEFT JOIN `".WPSG_TBL_MWST."` AS M ON (P.`mwst` = M.`id`)
					WHERE
						P.`mwst_key` = '' OR P.`mwst_key` IS NULL
					GROUP BY
						M.`id`
				");

			}

			if ($this->shop->hasMod('wpsg_mod_userpayment'))
			{

				$this->shop->view['arPaymentMethods'] = $this->db->fetchAssoc("
					SELECT
						PM.*
					FROM
						`".WPSG_TBL_ZV."` AS PM
					WHERE
						PM.`mwst_key` = '' OR PM.`mwst_key` IS NULL
				");

			}

			if ($this->shop->hasMod('wpsg_mod_versandarten'))
			{

				$this->shop->view['arShippingMethods'] = $this->db->fetchAssoc("
					SELECT
						SM.*
					FROM
						`".WPSG_TBL_VA."` AS SM
					WHERE
						SM.`mwst_key` = '' OR SM.`mwst_key` IS NULL
				");

			}

			$this->shop->view['arConf'] = array();

			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_paypal_mwst'] = __('Mehrwertsteuersatz für PayPal Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_autodebit_mwst'] = __('Mehrwertsteuersatz für Bankeinzugs Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_billsafe_mwst'] = __('Mehrwertsteuersatz für Billsafe Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_cab_mwst'] = __('Mehrwertsteuersatz für Click&Buy Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_debitpayment_mwst'] = __('Mehrwertsteuersatz für Nachnahme Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_flexipay_mwst'] = __('Mehrwertsteuersatz für Flexipay Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_giropay_giropay_mwst'] = __('Mehrwertsteuersatz für Giropay (Giropay) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_giropay_creditcard_mwst'] = __('Mehrwertsteuersatz für Giropay (Creditcard) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_giropay_directdebit_mwst'] = __('Mehrwertsteuersatz für Giropay (Lastschrift) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_invoice_type_mwst'] = __('Mehrwertsteuersatz der Gebühren für Zahlung mit Rechnung', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_klarna_mwst'] = __('Mehrwertsteuersatz für Klarna Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_creditcard_mwst'] = __('Mehrwertsteuersatz für Micropayment (Creditcard) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_creditcardreservation_mwst'] = __('Mehrwertsteuersatz für Micropayment (Creditcardreservation) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_directdebit_mwst'] = __('Mehrwertsteuersatz für Micropayment (Lastschrift) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_ebank2pay_mwst'] = __('Mehrwertsteuersatz für Micropayment (eBank2Pay) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_prepayment_mwst'] = __('Mehrwertsteuersatz für Micropayment (Vorkasse) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_call2pay_mwst'] = __('Mehrwertsteuersatz für Micropayment (Call2Pay) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_micropayment_handypay_mwst'] = __('Mehrwertsteuersatz für Micropayment (HandyPay) Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_prepayment_mwst'] = __('Mehrwertsteuersatz für Vorkasse Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_rechnungen_gutschrifttax'] = __('Mehrwertsteuersatz für Gebühren von Gutschriften', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_securepay_mwst'] = __('Mehrwertsteuersatz für Securepay Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_shs_mwst'] = __('Mehrwertsteuersatz für Gebühren Sparkasse Internetkasse Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_skrill_mwst'] = __('Mehrwertsteuersatz für Skrill Gebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_su_mwst'] = __('Mehrwertsteuersatz für Sofortüberweisungsgebühren', 'wpsg'); }
			if (wpsg_isSizedInt($this->get_option('wpsg_mod_paypal_mwst'))) { $this->shop->view['arConf']['wpsg_mod_willcollect_mwst'] = __('Mehrwertsteuersatz für Gebühren der Selbstabholung', 'wpsg'); }

			$this->shop->view['defaultCountry'] = $this->shop->getDefaultCountry();

			if (	!wpsg_isSizedArray($this->shop->view['distinctProductTax']) &&
					!wpsg_isSizedArray($this->shop->view['arPaymentMethods']) &&
					!wpsg_isSizedArray($this->shop->view['arShippingMethods']) &&
					!wpsg_isSizedArray($this->shop->view['arConf'])	)
			{

				$this->shop->addBackendError(__('Es gibt keine alten MwSt. Sätze zu migrieren.', 'wpsg'));

				$this->shop->view['subTemplate'] = WPSG_PATH_VIEW.'/admin/hilfe.phtml';

			}

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		}

		/**
		 * Wird aufgerufen wenn die Versandzonen verwaltet werden sollen
		 */
		public function vzAction() {
			
			$this->shop->view['arVZ'] = $this->db->fetchAssocField("SELECT VZ.`id`, VZ.`name` FROM `".WPSG_TBL_VZ."` AS VZ WHERE 1 ORDER BY VZ.`name` ASC ", "id", "name");

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add') {
				
				check_admin_referer('wpsg-admin-versandzonen-add');
				
				// Neue Zone anlegen
				$new_name = __('Anklicken um Bezeichnung zu ändern ...', 'wpsg');

				$vz_id = $this->db->ImportQuery(WPSG_TBL_VZ, array(
					'name' => wpsg_q($new_name)
				));

				$this->shop->addTranslationString('vz_'.$vz_id, $new_name);

				die($this->vz_listAction());

			} else if (@$_REQUEST['do'] == 'loadStandard') {
				
				check_admin_referer('wpsg-admin-versandzonen-loadStandard');
				
				$this->loadStandardLaenderVz();

				die($this->vz_listAction());

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove') {
				
				check_admin_referer('wpsg-admin-versandzonen-delete');

				// Versandzone löschen
				$this->db->Query("DELETE FROM `".WPSG_TBL_VZ."` WHERE `id` = '".wpsg_q($_REQUEST['vz_id'])."'");

				die($this->vz_listAction());

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'inlinedit') {
				
				check_admin_referer('wpsg-admin-versandzonen-inline_edit-'.$_REQUEST['vz_id']);

				// Eingaben prüfen
				if (!wpsg_checkInput($_REQUEST['vz_id'], WPSG_SANITIZE_INT) || !wpsg_checkInput($_REQUEST['value'], WPSG_SANITIZE_TEXTFIELD) || !wpsg_checkInput($_REQUEST['field'], WPSG_SANITIZE_TEXTFIELD)) 
					throw new \Exception(__('Parameterfehler!', 'wpsg'));
				
				$field = wpsg_xss($_REQUEST['field']);
				$value = wpsg_xss($_REQUEST['value']);
				$vz_id = intval($_REQUEST['vz_id']);
				
				if ($field === 'name') {
					
					$this->shop->addTranslationString('vz_'.$vz_id, $value);
					
				}
				
				$data[$field] = $value;
				
				$this->db->UpdateQuery(WPSG_TBL_VZ, $data, "`id` = '".wpsg_q($vz_id)."'");

				die(stripslashes($value));

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'save_plz') {
				
				check_admin_referer('wpsg-admin-versandzonen-save_plz');

				$this->db->UpdateQuery(WPSG_TBL_VZ, array(
					'param' => trim(wpsg_sinput("text_field", $_REQUEST['textarea']))
				), "`id` = '".wpsg_q($_REQUEST['vz_id'])."'");

				die('1');

			}

		} // public function vzAction()

		/**
		 * Ist für das Deinstallieren zuständig
		 */
		public function deinstallierenAction() {

			global $wpdb;

			if (isset($_REQUEST['submit'])) {

				check_admin_referer('wpsg-admin-deinstall');
				
				// Sanitization
				foreach($_REQUEST as $k => $v)
					if(strpos($k, "wpsg_deinstall_") !== false)
						$_REQUEST[$k] = wpsg_sinput("key", $v);

				if ($_REQUEST['wpsg_deinstall_products'] == '1')
				{

					// Produkte löschen
					$this->db->Query("TRUNCATE TABLE `".WPSG_TBL_PRODUCTS."`");
					$this->db->Query("DELETE FROM `".WPSG_TBL_META."` WHERE `meta_table` = 'WPSG_TBL_PRODUCTS' ");

					$this->shop->addBackendMessage(__('Produkte erfolgreich gelöscht.', 'wpsg'));

				}

				if ($_REQUEST['wpsg_deinstall_customer'] == '1')
				{

					// Kunden löschen
					$this->db->Query("TRUNCATE TABLE `".WPSG_TBL_KU."`");
					$this->db->Query("DELETE FROM `".WPSG_TBL_META."` WHERE `meta_table` = 'WPSG_TBL_KU' ");

					$this->shop->addBackendMessage(__('Kunden erfolgreich gelöscht.', 'wpsg'));

				}

				if ($_REQUEST['wpsg_deinstall_order'] == '1')
				{

					// Bestellungen löschen
					$arOrder = wpsg_order::find(array());

					foreach ($arOrder as $oOrder)
					{

						$oOrder->delete();

					}

					$this->shop->addBackendMessage(__('Bestellungen erfolgreich gelöscht.', 'wpsg'));

				}

				if ($_REQUEST['wpsg_deinstall_sites'] == '1')
				{
				
					// Seiten löschen

                    wp_delete_post($this->get_option('wpsg_page_basket'), true);
                    wp_delete_post($this->get_option('wpsg_page_basket_more'), true);
                    wp_delete_post($this->get_option('wpsg_page_versand'), true);
                    wp_delete_post($this->get_option('wpsg_page_product'), true);
                    wp_delete_post($this->get_option('wpsg_page_agb'), true);
                    wp_delete_post($this->get_option('wpsg_page_datenschutz'), true);
                    wp_delete_post($this->get_option('wpsg_page_widerrufsbelehrung'), true);
                    wp_delete_post($this->get_option('wpsg_page_impressum'), true);

                    // Module
                    $this->shop->callMods('wpsg_deinstall_sites');

					$this->shop->addBackendMessage(__('Seiten erfolgreich gelöscht.', 'wpsg'));
				
				}

				if ($_REQUEST['wpsg_deinstall_incompleteorder'] == '1')
				{

					$arOrder = wpsg_order::find(array(
						'status' => wpsg_ShopController::STATUS_UNVOLLSTAENDIG
					));

					foreach ($arOrder as $oOrder)
					{

						$oOrder->delete();

					}

					$this->shop->addBackendMessage(__('Unvollständige Bestellungen erfolgreich gelöscht.', 'wpsg'));

				}

				if ($_REQUEST['wpsg_deinstall_core'] == '1')
				{

					// Tabellen löschen
					foreach ((array)get_defined_constants() as $const_key => $const)
					{

						if (preg_match('/^WPSG_TBL_(.*)/', $const_key))
						{

							$this->db->Query("DROP TABLE IF EXISTS `".$const."`");

						}

					}

					// Plugin deaktivieren
					deactivate_plugins(WPSG_FOLDERNAME.'/wpshopgermany.php');

					// Einstellungen löschen
					$this->db->Query("DELETE FROM `".$wpdb->prefix."options` WHERE `option_name` LIKE 'wpsg_%'");

					// Plugindaten löschen
					wpsg_rrmdir(WPSG_PATH);

					$this->shop->clearMessages();
					$this->shop->redirect(WPSG_URL_WP.'wp-admin/plugins.php');

				}
				else
				{

					$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&amp;subaction=deinstallieren');

				}

			}

			$this->shop->view['count_order_incomplete'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."` WHERE `status` = '".wpsg_q(wpsg_ShopController::STATUS_UNVOLLSTAENDIG)."' ");
			$this->shop->view['count_order'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."`");
			$this->shop->view['count_customer'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_KU."`");
			$this->shop->view['count_products'] = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."`");

		} // public function deinstallierenAction()

		/**
		 * Wird aufgerufen wenn die Länder verwaltet werden sollen
		 */
		public function laenderAction() {

			if (isset($_REQUEST['submit'])) {
				
				check_admin_referer('wpsg-admin-laender');

				if (!wpsg_isSizedArray($_REQUEST['arDelete'])) {

					$this->addBackendError(__('Bitte mindestens ein Land zum löschen auswählen.', 'wpsg'));

				} else  {

					foreach ($_REQUEST['arDelete'] as $country_id => $c) {

						if (!wpsg_checkInput($country_id, WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
						
						$oCountry = wpsg_country::getInstance(wpsg_sinput("key", $country_id));
						$oCountry->delete();

					}

				}

				$this->addBackendMessage(__('Die markierten Länder wurden erfolgreich gelöscht.', 'wpsg'));
				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=laender');

			}

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'save') {
				
				check_admin_referer('wpsg-laender-save');

				$form_data = []; parse_str($_REQUEST['form_data'], $form_data);
				$update_data = [];

				// Sanitization
				wpsg_checkRequest('name', [WPSG_SANITIZE_TEXTFIELD], __('Name', 'wpsg'), $update_data, $form_data['country']['name']);
				wpsg_checkRequest('kuerzel', [WPSG_SANITIZE_TEXTFIELD], __('Kürzel', 'wpsg'), $update_data, $form_data['country']['kuerzel']);
				wpsg_checkRequest('vz', [WPSG_SANITIZE_INT], __('Versandzone', 'wpsg'), $update_data, $form_data['country']['vz']);
				wpsg_checkRequest('mwst', [WPSG_SANITIZE_VALUES, ['0', '1', '2']], __('MwSt. Grundlage', 'wpsg'), $update_data, $form_data['country']['mwst']);
				wpsg_checkRequest('mwst_a', [WPSG_SANITIZE_FLOAT], __('MwSt. Satz A (stark ermäßigter Satz)', 'wpsg'), $update_data, $form_data['country']['mwst_a']);
				wpsg_checkRequest('mwst_b', [WPSG_SANITIZE_FLOAT], __('MwSt. Satz B (ermäßigter Satz)', 'wpsg'), $update_data, $form_data['country']['mwst_b']);
				wpsg_checkRequest('mwst_c', [WPSG_SANITIZE_FLOAT], __('MwSt. Satz C (Normalsatz)', 'wpsg'), $update_data, $form_data['country']['mwst_c']);
				wpsg_checkRequest('mwst_d', [WPSG_SANITIZE_FLOAT], __('MwSt. Satz D (Zwischensatz)', 'wpsg'), $update_data, $form_data['country']['mwst_d']);
				
				if (isset($form_data['country']['telprefix'])) wpsg_checkRequest('telprefix', [WPSG_SANITIZE_FLOAT], __('MwSt. Satz D (Zwischensatz)', 'wpsg'), $update_data, $form_data['country']['telprefix']);
				 
				if (wpsg_isSizedArray($update_data)) {
				
					if (wpsg_isSizedInt($form_data['id'])) {
	
						$this->db->UpdateQuery(WPSG_TBL_LAND, wpsg_q($update_data), " `id` = '".wpsg_q($form_data['id'])."' ");
						$this->addBackendMessage(__('Land erfolgreich gespeichert.', 'wpsg'));
	
					} else {
	
						$form_data['id'] = $this->db->ImportQuery(WPSG_TBL_LAND, wpsg_q($update_data));
						$this->addBackendMessage(__('Land erfolgreich angelegt.', 'wpsg'));
	
					}
								
					if (isset($update_data['name'])) $this->shop->addTranslationString('land_'.$form_data['id'], $update_data['name']);
					
				}
 
				if (wpsg_isSizedInt($form_data['standard'])) $this->update_option('wpsg_defaultland', $form_data['id'], false, false, WPSG_SANITIZE_CHECKBOX);

				die($this->laenderList());

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'eu_import') {
				
				check_admin_referer('wpsg-laender-eu_import');

				// EU Import
				$this->loadEULaender();
				$this->shop->addBackendMessage(__('EU-Länder erfolgreich importiert.', 'wpsg'));

				die($this->laenderList());

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'import') {
				
				check_admin_referer('wpsg-laender-import');

				$this->loadStandardLaenderVz();
				$this->shop->addBackendMessage(__('Länder erfolgreich importiert.', 'wpsg'));

				die($this->laenderList());

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove') {

				check_admin_referer('wpsg-laender-delete');
				
				$this->clearMessages();
				$oCountry = wpsg_country::getInstance($_REQUEST['land_id']);
				$oCountry->delete();

				die("1");

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add') {
				
				check_admin_referer('wpsg-laender-add');
				
				$this->shop->view['vz'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPSG_TBL_VZ."` ORDER BY `name` ASC", "id", "name");

				$this->shop->view['land']['mwst_a'] = '';
				$this->shop->view['land']['mwst_b'] = '';
				$this->shop->view['land']['mwst_c'] = '';
				$this->shop->view['land']['mwst_d'] = '';

				$this->shop->view['land']['standard'] = '0';

				die($this->shop->render(WPSG_PATH_VIEW.'/admin/laender_edit.phtml'));

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'edit') {

				check_admin_referer('wpsg-laender-edit');
				
				$this->shop->view['vz'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPSG_TBL_VZ."` ORDER BY `name` ASC", "id", "name");
				$this->shop->view['land'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($_REQUEST['land_id'])."' ");

				if (wpsg_tf($this->shop->view['land']['mwst_a']) >= 0 && wpsg_tf($this->shop->view['land']['mwst_a']) !== '') $this->shop->view['land']['mwst_a'] = wpsg_ff(wpsg_tf($this->shop->view['land']['mwst_a']), '%'); else $this->shop->view['land']['mwst_a'] = '';
				if (wpsg_tf($this->shop->view['land']['mwst_b']) >= 0 && wpsg_tf($this->shop->view['land']['mwst_b']) !== '') $this->shop->view['land']['mwst_b'] = wpsg_ff(wpsg_tf($this->shop->view['land']['mwst_b']), '%'); else $this->shop->view['land']['mwst_b'] = '';
				if (wpsg_tf($this->shop->view['land']['mwst_c']) >= 0 && wpsg_tf($this->shop->view['land']['mwst_c']) !== '') $this->shop->view['land']['mwst_c'] = wpsg_ff(wpsg_tf($this->shop->view['land']['mwst_c']), '%'); else $this->shop->view['land']['mwst_c'] = '';
				if (wpsg_tf($this->shop->view['land']['mwst_d']) >= 0 && wpsg_tf($this->shop->view['land']['mwst_d']) !== '') $this->shop->view['land']['mwst_d'] = wpsg_ff(wpsg_tf($this->shop->view['land']['mwst_d']), '%'); else $this->shop->view['land']['mwst_d'] = '';

				if ($this->shop->get_option('wpsg_defaultland') == $_REQUEST['land_id']) $this->shop->view['land']['standard'] = '1'; else $this->shop->view['land']['standard'] = '0';

				die($this->shop->render(WPSG_PATH_VIEW.'/admin/laender_edit.phtml'));

			}

			$this->shop->view['vz'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPSG_TBL_VZ."` ORDER BY `name` ASC", "id", "name");
			$this->shop->view['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `vz` ASC, `name` ASC");

		} // public function laenderAction()

		public function wpmlAction()
		{

			if (isset($_REQUEST['wpsg_set_string_translation']))
			{

				if (!function_exists('icl_register_string')) throw new \wpsg\Exception(__('WPML ist nicht komplett Installiert (Funktion icl_register_string ist nicht erreichbar)', 'wpsg'));

				// Alle Automatisch angelegten String-Übersetzungen löschen
				$arStringTrans = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_WPML_ICL_STRINGS."` WHERE `context` = 'wpsg' AND `name` LIKE 'wpsg_auto_%' ");

				foreach ($arStringTrans as $st_id)
				{

					// Löschen
					$this->db->Query("DELETE FROM `".WPSG_TBL_WPML_ICL_STRING_PAGES."` WHERE `string_id` = '".wpsg_q($st_id)."' ");
					$this->db->Query("DELETE FROM `".WPSG_TBL_WPML_ICL_STRING_POSITIONS."` WHERE `string_id` = '".wpsg_q($st_id)."' ");
					$this->db->Query("DELETE FROM `".WPSG_TBL_WPML_ICL_STRING_TRANSLATIONS."` WHERE `string_id` = '".wpsg_q($st_id)."' ");
					$this->db->Query("DELETE FROM `".WPSG_TBL_WPML_ICL_STRINGS."` WHERE `id` = '".wpsg_q($st_id)."' ");

				}

				// Ländernamen
				$arToTrans = $this->db->fetchAssocField("SELECT CONCAT('land_', `id`) AS `key`, `name` FROM `".WPSG_TBL_LAND."` ", "key", "name");

				// Versandzonen
				$arToTrans += $this->db->fetchAssocField("SELECT CONCAT('vz_', `id`) AS `key`, `name` FROM `".WPSG_TBL_VZ."` ", "key", "name");

				// Auswahl der Anrede
				$wpsg_admin_pflicht = $this->shop->get_option('wpsg_admin_pflicht');
				$arToTrans['anrede_auswahl'] = $wpsg_admin_pflicht['anrede_auswahl'];

				// Text Widerrufsmail
				$arToTrans['wpsg_ps_mailwiderruf'] = $this->shop->get_option('wpsg_ps_mailwiderruf');

				// Module
				// TODO

				foreach ($arToTrans as $k => $t)
				{

				    // Doppeleinträge verhindern
                    $exist = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_WPML_ICL_STRINGS."` WHERE `value` = '".wpsg_q($t)."' AND `context` = 'wpsg' ");

                    if (!wpsg_isSizedInt($exist))
                    {

                        icl_register_string('wpsg', 'wpsg_auto_'.$k, $t, false, $this->get_option('wpsg_backend_language'));

                    }

				}

				$this->addBackendMessage(wpsg_translate(__('#1# Texte zur String-Übersetzung der Domain "wpsg" hinzugefügt.'), sizeof($arToTrans)));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=wpml');

			}

		} // public function wpmlAction()

		private function laenderList()
		{

			$this->shop->view['vz'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPSG_TBL_VZ."` ORDER BY `name` ASC", "id", "name");
			$this->shop->view['data'] = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ORDER BY `vz` ASC, `name` ASC");

			return $this->shop->render(WPSG_PATH_VIEW.'/admin/laender_list.phtml', false);

		} // private function laenderList()

		/**
		 * Importiert die EU-Länder aus der Liste
		 * Aktualisiert dabei bestehende
		 */
		public function loadEULaender()
		{

			$handle = fopen(WPSG_PATH."/lib/data/wp_wpsg_laender.csv", "r");

			// Schauen ob die EU Versandzone existiert
			$nExists = $this->db->fetchOne("SELECT VZ.`id` FROM `".WPSG_TBL_VZ."` AS VZ WHERE VZ.`id` = '2' ");
			if (!wpsg_isSizedInt($nExists)) $this->db->ImportQuery(WPSG_TBL_VZ, array(
				'id' => '2',
				'name' => __('EU', 'wpsg'),
				'innereu' => '1'
			));

			while (($row = fgetcsv($handle, 1000, ";")) !== false)
			{

				if (!in_array($row['3'], array(1, 2))) continue;

				$country_data = array(
					'id' => wpsg_q($row['0']),
					'name' => wpsg_q($row['1']),
					'kuerzel' => wpsg_q($row['2']),
					'vz' => wpsg_q($row['3']),
					'mwst' => wpsg_q($row['4']),
					'mwst_a' => ((wpsg_tf($row[5]) >= 0 && wpsg_tf($row[5]) !== '')?wpsg_q(wpsg_tf($row[5])):'NULL'),
					'mwst_b' => ((wpsg_tf($row[6]) >= 0 && wpsg_tf($row[6]) !== '')?wpsg_q(wpsg_tf($row[6])):'NULL'),
					'mwst_c' => ((wpsg_tf($row[7]) >= 0 && wpsg_tf($row[7]) !== '')?wpsg_q(wpsg_tf($row[7])):'NULL'),
					'mwst_d' => ((wpsg_tf($row[8]) >= 0 && wpsg_tf($row[8]) !== '')?wpsg_q(wpsg_tf($row[8])):'NULL')
				);

				$nExists = $this->db->fetchOne("SELECT L.`id` FROM `".WPSG_TBL_LAND."` AS L WHERE UPPER(L.`name`) = '".wpsg_q(strtoupper($country_data['name']))."' ");

				if (wpsg_isSizedInt($nExists))
				{

					unset($country_data['id']);
					$this->db->UpdateQuery(WPSG_TBL_LAND, $country_data, " `id` = '".wpsg_q($nExists)."' ");

				}
				else
				{

					// Checken ob die ID schon existiert.
					$idName = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($country_data['id'])."' ");

					if (wpsg_isSizedInt($idName)) unset($country_data['id']);

					$this->db->ImportQuery(WPSG_TBL_LAND, $country_data);

				}

			}

		} // public function loadEULaender()

		/**
		 * Diese Funktion lädt die Standard Länder und Versandzonen
		 * Wird aus der Länderverwaltung und der Versandzonenverwaltung aufgerufen
		 */
		private function loadStandardLaenderVz()
		{

			// Standard Länder und Versandzonen laden
			$this->db->Query("DELETE FROM `".WPSG_TBL_VZ."`");
			$this->db->Query("DELETE FROM `".WPSG_TBL_LAND."`");

			// Versandzonen anlegen
			$this->db->ImportQuery(WPSG_TBL_VZ, array('id' => '1', 'name' => __('Inland', 'wpsg')));
			$this->db->ImportQuery(WPSG_TBL_VZ, array('id' => '2', 'name' => __('EU Länder', 'wpsg'), 'innereu' => '1'));
			$this->db->ImportQuery(WPSG_TBL_VZ, array('id' => '3', 'name' => __('Ausland', 'wpsg')));

			$this->shop->addTranslationString('vz_1', __('Inland', 'wpsg'));
			$this->shop->addTranslationString('vz_2', __('EU Länder', 'wpsg'));
			$this->shop->addTranslationString('vz_3', __('Ausland', 'wpsg'));

			$handle = fopen(WPSG_PATH."/lib/data/wp_wpsg_laender.csv", "r");

			while (($row = fgetcsv($handle, 1000, ";")) !== false)
			{

				$land_id = $this->db->ImportQuery(WPSG_TBL_LAND, array(
					'id' => wpsg_q($row['0']),
					'name' => wpsg_q($row['1']),
					'kuerzel' => wpsg_q($row['2']),
					'vz' => wpsg_q($row['3']),
					'mwst' => wpsg_q($row['4']),
					'mwst_a' => ((wpsg_tf($row[5]) >= 0 && wpsg_tf($row[5]) !== '')?wpsg_q(wpsg_tf($row[5])):'NULL'),
					'mwst_b' => ((wpsg_tf($row[6]) >= 0 && wpsg_tf($row[6]) !== '')?wpsg_q(wpsg_tf($row[6])):'NULL'),
					'mwst_c' => ((wpsg_tf($row[7]) >= 0 && wpsg_tf($row[7]) !== '')?wpsg_q(wpsg_tf($row[7])):'NULL'),
					'mwst_d' => ((wpsg_tf($row[8]) >= 0 && wpsg_tf($row[8]) !== '')?wpsg_q(wpsg_tf($row[8])):'NULL')
				));

				$this->shop->addTranslationString('land_'.$land_id, $row['1']);

			}

			$this->shop->update_option('wpsg_defaultland', '1');

		} // private function loadStandardLaenderVz()


		/**
		 * Wird aufgerufen wenn eine Mail an den Kunden gesendet werden soll
		 */
		public function sendMailAction()
		{
			//die('sendMail');

			$oid = 0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'send')
			{

				//$temp = date("d.m.Y H:i:s", strtotime($basket->arCheckout['datum']));
				//$temp = date("d.m.Y H:i:s", strtotime('2016-12-14'));
				$this->shop->basket->initFromDB($_REQUEST['edit_id'], true);
				$arBasket = $this->shop->basket->toArray(true);

				$this->shop->basket->sendOrderSaveMails($_REQUEST['edit_id'], $arBasket, true, false, false);

				$this->shop->addBackendMessage(wpsg_translate(__('Kundenmail wurde erfolgreich an #1# gesendet.', 'wpsg'), $basket->arCheckout['email']));

			}

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		} // public function sendMailAction()


		/**
		 * Wird aufgerufen wenn die Pflichtfelder verwaltet werden sollen
		 */
		public function kundendatenAction()
		{
			
			$this->shop->view['pflicht'] = $this->get_option('wpsg_admin_pflicht');

			$this->shop->view['arShipping'] = array();
			foreach ($this->shop->arShipping as $s_id => $s) $this->shop->view['arShipping'][$s['id']] = $s['name'];

			$this->shop->view['arPayment'] = array();
			foreach ($this->shop->arPayment as $p_id => $p) $this->shop->view['arPayment'][$p['id']] = $p['name'];

			$this->shop->view['customerdatadelete'] = array();
			foreach (@(array)$this->shop->customerdatadelete as $d_id => $d) $this->shop->view['customerdatadelete'][$d['id']] = $d['name'];

			$this->shop->view['arLander'] = $this->db->fetchAssocField("SELECT L.`id`, L.`name` FROM `".WPSG_TBL_LAND."` AS L ORDER BY `name` ASC ", "id", "name");

			$this->shop->view['arTitle'] = (array)explode('|', $this->shop->view['pflicht']['anrede_auswahl']);

			if (isset($_REQUEST['show']) && $_REQUEST['show'] == 'code')
			{

				check_admin_referer('wpsg-admin-kundendaten-code');
				
				$this->shop->view['id'] = $_REQUEST['kv_id'];
				$this->shop->view['field'] = $this->shop->view['pflicht']['custom'][$_REQUEST['kv_id']];

				die($this->shop->render(WPSG_PATH_VIEW.'/admin/kundendaten_codeinfo.phtml'));

			}

			if (isset($_REQUEST['submit'])) {
 
				check_admin_referer('wpsg-admin-kundendaten');
				
			    $this->shop->update_option('wpsg_customerpreset_shipping', $_REQUEST['wpsg_customerpreset_shipping'], false, false, WPSG_SANITIZE_VALUES, array_keys($this->shop->arShipping));
			    $this->shop->update_option('wpsg_customerpreset_payment', $_REQUEST['wpsg_customerpreset_payment'], false, false, WPSG_SANITIZE_VALUES, array_keys($this->shop->arPayment));
			    $this->shop->update_option('wpsg_defaultland', $_REQUEST['wpsg_defaultland'], false, false, WPSG_SANITIZE_INT);
			    $this->shop->update_option('wpsg_customerpreset_title', $_REQUEST['wpsg_customerpreset_title'], false, false, WPSG_SANITIZE_INT);
			    $this->shop->update_option('wpsg_kundenvariablen_show', $_REQUEST['wpsg_kundenvariablen_show'], false, false, WPSG_SANITIZE_CHECKBOX, ['allowEmpty' => true]);

				foreach ($_REQUEST['pflicht'] as $k => $v)
				{

					if ($k == 'custom')
					{

						foreach ($_REQUEST['pflicht']['custom'] as $c_id => $c)
						{

							if (wpsg_getInt($c['del']) == '1')
								unset($this->shop->view['pflicht']['custom'][$c_id]);
							else
							{
								/*
								// wird jetzt über InlineEdit gespeichert
								$this->shop->view['pflicht']['custom'][$c_id]['typ'] = $c['typ'];
								$this->shop->view['pflicht']['custom'][$c_id]['show'] = $c['show'];
								$this->shop->view['pflicht']['custom'][$c_id]['auswahl'] = $c['auswahl'];

								$this->shop->addTranslationString('customervars_'.$c_id.'_auswahl', $c['auswahl']);
								*/
							}

						}

					}
					else
					{

						if ($k == 'anrede_auswahl') {

							if (wpsg_checkInput($v, WPSG_SANITIZE_TEXTFIELD)) {
							
								$this->shop->view['pflicht'][$k] = $v;
								$this->shop->addTranslationString('anrede_auswahl', $v);
								
							} else $this->shop->addBackendError(__('Bitte die Eingaben bei Anrede überprüfen.', 'wpsg'));

						} else {
							
							if (wpsg_checkInput($v, WPSG_SANITIZE_VALUES, ['0', '1', '2'])) {
								
								$this->shop->view['pflicht'][$k] = $v;
								
							} else $this->shop->addBackendError(wpsg_translate(__('Bitte die Eingaben bei #1# überprüfen.', 'wpsg'), $k));
							
						}

					}

				}

				$this->update_option('wpsg_admin_pflicht', $this->shop->view['pflicht']);

				$this->shop->addBackendMessage(__('Kundenvariablen erfolgreich gespeichert.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=kundendaten');

			}

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add') {

				check_admin_referer('wpsg-admin-kundendaten-add');
				
				if (!is_array($this->shop->view['pflicht'])) $this->shop->view['pflicht'] = array();
				$this->shop->view['pflicht']['custom'][] = array(
					"name" => __('Neues benutzerdefiniertes Feld', 'wpsg'),
					"show" => '0',
					"typ" => '0',
					"auswahl" => ''
				);

				$this->update_option('wpsg_admin_pflicht', $this->shop->view['pflicht']);

				die($this->shop->render(WPSG_PATH_VIEW.'/admin/kundendaten_tab2.phtml'));

			} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove') {
				
				check_admin_referer('wpsg-admin-kundendaten-delete');

				unset($this->shop->view['pflicht']['custom'][$_REQUEST['kv_index']]);
				$this->update_option('wpsg_admin_pflicht', $this->shop->view['pflicht']);

				die("1");

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'inlinedit')
			{

				if ($_REQUEST['field'] == 'name')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);
					$this->shop->view['pflicht']['custom'][$_REQUEST['field_id']]['name'] = $_REQUEST['value'];

				}
				else if ($_REQUEST['field'] == 'show')
				{

					$_REQUEST['value'] = wpsg_sinput("key", $_REQUEST['value']);
					$this->shop->view['pflicht']['custom'][$_REQUEST['field_id']]['show'] = $_REQUEST['value'];

				}
				else if ($_REQUEST['field'] == 'typ')
				{

					$_REQUEST['value'] = wpsg_sinput("key", $_REQUEST['value']);
					$this->shop->view['pflicht']['custom'][$_REQUEST['field_id']]['typ'] = $_REQUEST['value'];

				}
				else if ($_REQUEST['field'] == 'auswahl')
				{

					$this->shop->view['pflicht']['custom'][$_REQUEST['field_id']]['auswahl'] = $_REQUEST['value'];

				}

				$this->update_option('wpsg_admin_pflicht', $this->shop->view['pflicht']);

				die(stripslashes($_REQUEST['value']));

			}

		} // public function kundendatenAction()

		/**
		 * Konfiguration der E-Mail Einstellungen
		 */
		public function emailconfAction()
		{

			if (isset($_REQUEST['migrateAttachmentToMediathek'])) {
				
				$old_attachment = $this->get_option('wpsg_kundenmail_attachfile');				
				$file = wpsg_getUploadDir('wpsg_mailconf').$old_attachment;
				
				if (wpsg_isSizedString($old_attachment) && file_exists($file)) {
					
					$wp_upload_dir = wp_upload_dir();
					
					$attachment = array(
						'guid' => $wp_upload_dir['url'].'/'.basename($file),
						'post_mime_type' => 'application/pdf',
						'post_title' => 'Migrierter Anhang aus altem Kundenanhang',
						'post_content' => 'Migrierter Anhang aus altem Kundenanhang',
						'post_status' => 'inherit'
					);
					
					copy($file,$wp_upload_dir['path'].'/'.basename($file));
					
					$image_id = wp_insert_attachment($attachment, $wp_upload_dir['path'].'/'.basename($file), 0);
					
					//require_once(ABSPATH.'wp-admin/includes/image.php');					
					//$attach_data = wp_generate_attachment_metadata($image_id, basename($file));					
					//wp_update_attachment_metadata($image_id, $attach_data);
					
					$oldIDs = $this->shop->get_option('wpsg_kundenmail_mediaattachment');
					$oldIDs = explode(',', $oldIDs);
					
					$oldIDs[] = $image_id;
					
					$oldIDs = array_unique($oldIDs); wpsg_trim($oldIDs);
					
					$GLOBALS['wpsg_sc']->update_option('wpsg_kundenmail_mediaattachment', implode(',', $oldIDs));
					
					@unlink($file);
					$this->shop->update_option('wpsg_kundenmail_attachfile', false);
					
				}
				
				$this->shop->addBackendMessage(__('Anhang migriert.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=emailconf');

				exit;
				
			}
			
			if (isset($_REQUEST['submit']))
			{
				
				check_admin_referer('wpsg-admin-emailconf');

			    $this->update_option('wpsg_htmlmail', $_REQUEST['wpsg_htmlmail'], false, false, WPSG_SANITIZE_CHECKBOX);

				wpsg_saveEMailConfig("global"); // Global
				wpsg_saveEMailConfig('adminmail'); // Admin Mail
				wpsg_saveEMailConfig('kundenmail'); // Kunden Mail
				wpsg_saveEMailConfig('status'); // Mail Statusänderung

				$this->shop->callMods('admin_emailconf_save');

				$this->shop->addBackendMessage(__('E-Mail Konfiguration erfolgreich gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=emailconf');

			}

			if (isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'del_attach_file')
			{
				$this->update_option('wpsg_kundenmail_attachfile', '');
			}

		} // public function emailconfAction()

		/**
		 * Wird aufgerufen wenn die Seiten verwaltet werden sollen
		 */
		public function seitenAction()
		{

			$pages = get_pages();

			$arPages = array(
				'0' => __('Keine Zuordnung', 'wpsg'),
				'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);

			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}

			$this->shop->view['pages'] = $arPages;

		} // public function seitenAction()

		/**
		 * Modulverwaltung
		 */
		public function moduleAction() {
						
			if (isset($_REQUEST['noheader']) && $_REQUEST['noheader'] == '1' && !isset($_REQUEST['submit']))
			{
                
				// Ajax Anfrage eines Moduls
				$this->shop->callMod($_REQUEST['modul'], 'be_ajax');
				die();

			}

			$this->shop->view = array(
				'actionName' => 'module',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/module.phtml',
				'groups' => array()
			);

			$this->shop->view['global'] = false;
			if ($this->shop->isMultiBlog() && $this->shop->get_option('wpsg_multiblog_standalone', true) != '1') $this->shop->view['global'] = true;

			$this->shop->loadModule(true);

			if (isset($_REQUEST['submit'])) { $this->submitAction(); }

			foreach ($this->shop->arAllModule as $mod_key => $m)
			{

				$group = $m->group;

				if (trim($group) == '') $group = __('Sonstiges', 'wpsg');

				$this->shop->view['groups'][$group][$mod_key] = $m;

			}

			// Gruppen sortieren
			ksort($this->shop->view['groups']);

			// Module in den Gruppen sortieren
			foreach ($this->shop->view['groups'] as $k => $g)
			{

				uasort($this->shop->view['groups'][$k], array($this->shop, 'cmp_mods_name'));

			}

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		} // public function moduleAction()

		/**
		 * Hilfe Seite
		 */
		public function hilfeAction()
		{

			$this->shop->view = array(
				'actionName' => 'hilfe',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/hilfe.phtml'
			);

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		} // public function hilfeAction()

		/**
		 * Behandelt das Speichern der Widerrufsbelehrung
		 */
		public function widerrufsbelehrungAction() {
			
			if (isset($_REQUEST['download'])) {
				
				check_admin_referer('wpsg-admin-widerrufsbelehrung-download');
				
				wpsg_header::PDFPlugin(WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->get_option('wpsg_revocationform'));
				
			}

			if (isset($_REQUEST['submit']))
			{

                \check_admin_referer('wpsg-save-revocation');

                $this->shop->update_option('wpsg_ps_mailwiderruf', $_REQUEST['wpsg_ps_mailwiderruf'], false, false, WPSG_SANITIZE_TEXTAREA);
				$this->shop->addTranslationString('wpsg_ps_mailwiderruf', wpsg_sinput("text_field", $_REQUEST['wpsg_ps_mailwiderruf']));

				if (file_exists($_FILES['wpsg_widerrufsformular']['tmp_name']))
				{
				    
				    if (mime_content_type($_FILES['wpsg_widerrufsformular']['tmp_name']) === 'application/pdf') {

                        if (!file_exists(WPSG_PATH_UPLOADS.'wpsg_revocation/')) mkdir(WPSG_PATH_UPLOADS.'wpsg_revocation/', 0775, true);
    
                        $this->clearRevocationForm();
    
                        move_uploaded_file($_FILES['wpsg_widerrufsformular']['tmp_name'], WPSG_PATH_UPLOADS.'wpsg_revocation/'.$_FILES['wpsg_widerrufsformular']['name']);
                        $this->addBackendMessage(__('Widerrufsformular erfolgreich hochgeladen.', 'wpsg'));
    
                        $this->shop->update_option('wpsg_revocationform', $_FILES['wpsg_widerrufsformular']['name']);
                        
                    } else {
				        
				        $this->shop->addBackendError(__('Dateiformat muss vom Type PDF sein.', 'wpsg'));
				        
                    }

				} else if (wpsg_isSizedString($_FILES['wpsg_widerrufsformular']['name']) && $_FILES['wpsg_widerrufsformular']['error']) {
				    
				    $this->shop->addBackendError(__('Mit dem Upload gab es ein Problem, möglicherweise ist die Datei zu groß.', 'wpsg'));
				    
                }

                $this->shop->update_option('wpsg_widerrufsformular_kundenmail', $_REQUEST['wpsg_widerrufsformular_kundenmail'], false, false, WPSG_SANITIZE_CHECKBOX);

				if ($this->shop->hasMod('wpsg_mod_rechnungen'))
				{

				    $this->shop->update_option('wpsg_widerrufsformular_invoice', $_REQUEST['wpsg_widerrufsformular_invoice'], false, false, WPSG_SANITIZE_CHECKBOX);

				}

				if ($this->shop->hasMod('wpsg_mod_auftragsbestaetigung'))
				{

				    $this->shop->update_option('wpsg_widerrufsformular_orderconfirm', $_REQUEST['wpsg_widerrufsformular_orderconfirm'], false, false, WPSG_SANITIZE_CHECKBOX);

				}

				if (!$this->shop->hasBackendError()) $this->shop->addBackendMessage(__('Widerruf erfolgreich gespeichert.', 'wpsg'));
				
				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=widerrufsbelehrung');

			}

			if (wpsg_isSizedString($_REQUEST['do']) && $_REQUEST['do'] == 'generateWiderrufsformular') {
				
				check_admin_referer('wpsg-admin-widerrufsbelehrung-generate');
				
				if (!file_exists(WPSG_PATH_UPLOADS.'wpsg_revocation/')) mkdir(WPSG_PATH_UPLOADS.'wpsg_revocation/', 0775, true);

				$this->clearRevocationForm();

				$this->shop->view['filename'] = WPSG_PATH_UPLOADS.'wpsg_revocation/widerrufsformular.pdf';
				$this->shop->render(WPSG_PATH_VIEW.'/admin/musterwiderruf.pdf.phtml');

				$this->shop->update_option('wpsg_revocationform', 'widerrufsformular.pdf');

				$this->addBackendMessage(__('Widerrufsformular erfolgreich generiert.', 'wpsg'));
				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=widerrufsbelehrung');

			}
			else if (wpsg_isSizedString($_REQUEST['do']) && $_REQUEST['do'] == 'removeWiderrufsformular') {
				
				check_admin_referer('wpsg-admin-widerrufsbelehrung-delete');

				$bOK = unlink(WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->shop->get_option('wpsg_revocationform'));

				if ($bOK === true)
				{

					$this->shop->update_option('wpsg_revocationform', false);

					$this->addBackendMessage(__('Widerrufsformular erfolgreich gelöscht.', 'wpsg'));

				}
				else
				{

					$this->addBackendError(__('Widerrufsformular konnte nicht gelöscht werden.', 'wpsg'));

				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=widerrufsbelehrung');

			}

			$revocationform = WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->shop->get_option('wpsg_revocationform');

			if (file_exists($revocationform) && is_file($revocationform))
			{

				$this->shop->view['revocationform'] = basename($revocationform);

			}

		} // public function widerrufsbelehrungAction()

		private function clearRevocationForm()
		{

			$bOK = @unlink(WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->shop->get_option('wpsg_revocationform'));
			$this->shop->update_option('wpsg_revocationform', false);

		} // private function clearRevocationForm()

		/**
		 * Zeigt die News im Backend an
		 */
		public function newsAction()
		{

			if (wpsg_isSizedInt($_REQUEST['reload'])) {
 
				wpsg_news::getLatestNewsFromRSS();
				
				$this->shop->addBackendMessage(__('Refresh der News erfolgreich durchgeführt.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=news');
				
			} else if (wpsg_isSizedString($_REQUEST['read'])) {

				$news = wpsg_news::getNewsById(rawurldecode($_REQUEST['read']));

				if ($news === false) {

					$this->shop->addBackendError(__('News wurde nicht gefunden,', 'wpsg'));
					$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=news');

				} else {

					wpsg_news::markRead($_REQUEST['read']);
					
					$this->shop->redirect($news['url']);

				}

			}

			$this->shop->view = array(
				'actionName' => 'news',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/news.phtml',
				'news' => wpsg_news::getLatestNews()
			);

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		} // public function newsAction()

		/**
		 * Zeigt Informationen zu dem Shop und aktivierten Modulen an
		 */
		public function ueberAction()
		{

            if (wpsg_isSizedString($_REQUEST['subaction'], 'clearSysLog'))
            {

                @unlink($GLOBALS['wpsg_sc']->getStorageRoot().'exception.log');

                $this->addBackendMessage(__('Fehlerprotokoll gelöscht.', 'wpsg'));

                $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=ueber&subaction=systemlog');

            }

			$this->shop->view = array(
				'actionName' => 'ueber',
				'subTemplate' => WPSG_PATH_VIEW.'/admin/ueber.phtml'
			);

			$this->shop->render(WPSG_PATH_VIEW.'/admin/index.phtml');

		} // public function ueberAction()

		/**
		 * Erweiterte Einstellungen
		 */
		public function extendedAction()
		{

			$this->shop->view['arGeoMode'] = array('0' => __('Immer anzeigen', 'wpsg'));

			if (isset($_SERVER['GEOIP_COUNTRY_CODE'])) $this->shop->view['arGeoMode'][1] = __('Anhand des Apache Moduls mod_geoip', 'wpsg');
			if (function_exists("geoip_country_code_by_name")) $this->shop->view['arGeoMode'][2] = __('Anhand der PECL Erweiterung php_geoip', 'wpsg');

			$this->shop->view['arGeoMode'][3] = __('Anhand der Browsersprache', 'wpsg');
			$this->shop->view['arGeoMode'][4] = __('nicht anzeigen', 'wpsg');

		} // public function extendedAction()

		/**
		 * Wird beim absenden des Formulars aufgerufen
		 */
		public function submitAction()
		{

			global $wpdb;

			if (@$_REQUEST['subaction'] == 'konfiguration') {

                \check_admin_referer('wpsg-save-config');
			    
				$this->update_option('wpsg_currency', $_REQUEST['wpsg_currency'], false, false, WPSG_SANITIZE_TEXTFIELD);
				$this->update_option('wpsg_produkte_perpage', $_REQUEST['wpsg_produkte_perpage'], false, false, WPSG_SANITIZE_INT);
				$this->update_option('wpsg_order_perpage', $_REQUEST['wpsg_order_perpage'], false, false, WPSG_SANITIZE_INT);
				$this->update_option('wpsg_showincompleteorder', $_REQUEST['wpsg_showincompleteorder'], false, false, WPSG_SANITIZE_CHECKBOX);				
				$this->update_option('wpsg_emptyorder_clear', $_REQUEST['wpsg_emptyorder_clear'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_afterinsert', $_REQUEST['wpsg_afterinsert'], false, false, WPSG_SANITIZE_VALUES, ['0', '1', '2', '3']);
				$this->update_option('wpsg_afterorder', $_REQUEST['wpsg_afterorder'], false, false, WPSG_SANITIZE_VALUES, ['0', '1']);
				$this->update_option('wpsg_format_knr', $_REQUEST['wpsg_format_knr'], false, false, WPSG_SANITIZE_TEXTFIELD);
				$this->update_option('wpsg_order_knr', $_REQUEST['wpsg_order_knr'], false, false, WPSG_SANITIZE_VALUES, ['0', '1']);
				$this->update_option('wpsg_customer_start', $_REQUEST['wpsg_customer_start'], false, false, WPSG_SANITIZE_INT);
				$this->update_option('wpsg_format_onr', $_REQUEST['wpsg_format_onr'], false, false, WPSG_SANITIZE_TEXTFIELD);
				$this->update_option('wpsg_order_start', $_REQUEST['wpsg_order_start'], false, false, WPSG_SANITIZE_INT);
				$this->update_option('wpsg_skip_checkout2', $_REQUEST['wpsg_skip_checkout2'], false, false, WPSG_SANITIZE_CHECKBOX);
				
				if (wpsg_isSizedString($_REQUEST['wpsg_backend_language'])) {
					
					$arLang = $this->shop->getStoreLanguages();
					$arLangKey = [];
					
					if (wpsg_isSizedArray($arLang)) {
						
						foreach ($arLang as $lang) {
							
							$arLangKey[] = $lang['lang'];
							
						}
						
					}
					
					$this->update_option('wpsg_backend_language', $_REQUEST['wpsg_backend_language'], false, false,WPSG_SANITIZE_VALUES, $arLangKey);
					
				}

				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin');

			} else if (@$_REQUEST['subaction'] == "dataprotection") {
				
				check_admin_referer('wpsg-admin-dataprotection');
				
				$this->update_option('dataprotectioncommissioner', $_REQUEST['dataprotectioncommissioner'], false, false, WPSG_SANITIZE_CHECKBOX);
				
				if (wpsg_isSizedInt($_REQUEST['dataprotectioncommissioner'], 1)) {
					
					$this->update_option('dataprotectioncommissioner_name', $_REQUEST['dataprotectioncommissioner_name'], false, false, WPSG_SANITIZE_TEXTFIELD);
					$this->update_option('dataprotectioncommissioner_tel', $_REQUEST['dataprotectioncommissioner_tel'], false, false, WPSG_SANITIZE_TEXTFIELD);
					$this->update_option('dataprotectioncommissioner_email', $_REQUEST['dataprotectioncommissioner_email'], false, false, WPSG_SANITIZE_EMAIL);	
					
				}
				
				$this->update_option('wpsg_customerdatadelete', $_REQUEST['wpsg_customerdatadelete'], false, false, WPSG_SANITIZE_TEXTFIELD);
				$this->update_option('wpsg_customerdatadelete_unit', $_REQUEST['wpsg_customerdatadelete_unit'], false, false, WPSG_SANITIZE_VALUES, [0, 1, 2]);
				$this->update_option('wpsg_customerdatedelete_who', $_REQUEST['wpsg_customerdatedelete_who'], false, false, WPSG_SANITIZE_VALUES, [0, 1]);
								
				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));
				
				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=dataprotection');
				
			} else if (@$_REQUEST['subaction'] == 'extended') {
				
				\check_admin_referer('wpsg-save-admin-extended');

			    $this->update_option('wpsg_salt', $_REQUEST['wpsg_salt'], false, false, WPSG_SANITIZE_TEXTFIELD);
				$this->update_option('wpsg_options_nl2br', $_REQUEST['wpsg_options_nl2br'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_debugModus', $_REQUEST['wpsg_debugModus'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_displayTemplates', $_REQUEST['wpsg_displayTemplates'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_displayTemplatesLog', $_REQUEST['wpsg_displayTemplatesLog'], false, false, WPSG_SANITIZE_CHECKBOX);

				$this->update_option('wpsg_referer_requesturi', $_REQUEST['wpsg_referer_requesturi'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_ignoreuserview', $_REQUEST['wpsg_ignoreuserview'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_classicupload', $_REQUEST['wpsg_classicupload'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_content_filter_direct', $_REQUEST['wpsg_content_filter_direct'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_options_no_rte_apply_filter', $_REQUEST['wpsg_options_no_rte_apply_filter'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_options_nl2br_out', $_REQUEST['wpsg_options_nl2br_out'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_impexp_clearlinebreak', $_REQUEST['wpsg_impexp_clearlinebreak'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_removeWpAutoOp', $_REQUEST['wpsg_removeWpAutoOp'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_removeWpTrimExcerpt', $_REQUEST['wpsg_removeWpTrimExcerpt'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_lockOrderTables', $_REQUEST['wpsg_lockOrderTables'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_autoraw', $_REQUEST['wpsg_autoraw'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_nocache', $_REQUEST['wpsg_nocache'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_autolineending', $_REQUEST['wpsg_autolineending'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_dontcopymedia', $_REQUEST['wpsg_dontcopymedia'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_switchtolowestshippingafterproductremove', $_REQUEST['wpsg_switchtolowestshippingafterproductremove'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_geo_determination', $_REQUEST['wpsg_geo_determination'], false, false, WPSG_SANITIZE_VALUES, [0, 3, 4]);
				
				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=extended');

			} else if (@$_REQUEST['subaction'] == 'presentation') {
				
				\check_admin_referer('wpsg-save-admin-presentation');
				
			    $this->update_option('wpsg_imagehandler_basketimage', $_REQUEST['wpsg_imagehandler_basketimage'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_imagehandler_overviewimage', $_REQUEST['wpsg_imagehandler_overviewimage'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_showMwstAlways', $_REQUEST['wpsg_showMwstAlways'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_form_validation', $_REQUEST['wpsg_form_validation'], false, false, WPSG_SANITIZE_VALUES, [0, 1, 2]);
				$this->update_option('wpsg_showArticelnumber', $_REQUEST['wpsg_showArticelnumber'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_hideBasketCountrySelect', $_REQUEST['wpsg_hideBasketCountrySelect'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_alternativeProductDetailDesign', $_REQUEST['wpsg_alternativeProductDetailDesign'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_alternativeOrderDesign', $_REQUEST['wpsg_alternativeOrderDesign'], false, false, WPSG_SANITIZE_CHECKBOX);
								
				$this->shop->callMods('admin_presentation_submit');

				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=presentation');

			} else if (@$_REQUEST['subaction'] == 'path') {

				$this->shop->update_option('wpsg_path_upload_multiblog', $_REQUEST['wpsg_path_upload_multiblog'], true);

				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=path');

			} else if (@$_REQUEST['subaction'] == 'loadsavesettings') {
								
				if (isset($_REQUEST['wpsg_do']) && $_REQUEST['wpsg_do'] == 'downloadsettings') {
					
					\check_admin_referer('wpsg-save-admin-loadsavesettings-download');
					
					$arSettings = $this->db->fetchAssoc("
						SELECT
							*
						FROM
							`".$wpdb->prefix."options`
						WHERE
							(
								`option_name` LIKE 'wpsg_%'
							)
							AND
							(
								`option_name` != 'wpsg_installed' AND
								`option_name` != 'wpsg_modul'
							)
					");

					$doc = new DOMDocument('1.0');
					$doc->formatOutput = true;
					$doc->encoding = "utf-8";

					$node = $doc->createElement("wpsg");

					$settings = $doc->createElement("settings");
					foreach ($arSettings as $s)
					{

						$setting = $doc->createElement("option");
						wpsg_addAttributs($doc, $setting, array(
							"option_id" => $s['option_id'],
							"blog_id" => $s['blog_id'],
							"option_name" => $s['option_name'],
							"option_value" => $s['option_value'],
							"autoload" => $s['autoload']
						));
						$settings->appendChild($setting);

					}

					$node->appendChild($settings);
					$doc->appendChild($node);

					header('Cache-Control: private');
					header('Content-Type: application/download; charset=utf-8');
					header('Content-Disposition: filename=wpsg_settings_'.date('dmYHi').'.xml');
					header('Pragma: public');

					session_cache_limiter('nocache');

					die($doc->saveXML());

				}
				
				\check_admin_referer('wpsg-save-admin-loadsavesettings');

				if (wpsg_isSizedString($_FILES['wpsg_settings']['tmp_name'])) {
										
					if (file_exists($_FILES['wpsg_settings']['tmp_name'])) {

						$xml = simplexml_load_file($_FILES['wpsg_settings']['tmp_name']);
	
						if ($xml === false)
						{
	
							$this->shop->addBackendError(__('Keine gültige XML Einstellungsdatei angegeben!', 'wpsg'));
	
						}
						else
						{
	
							$path ="/wpsg/settings/option";
							$res = $xml->xpath($path);
	
							foreach ($res as $k => $v)
							{
	
								$a = $v->attributes();
	
								$bExists = $this->db->fetchOne("SELECT `option_id` FROM `".$wpdb->prefix."options` WHERE `option_name` = '".wpsg_q(strval($a->option_name))."' ");
	
								// Wenn eine Seite importiert wird,
								if(strpos(wpsg_q(strval($a->option_name)), "wpsg_page") !== false && wpsg_isSizedInt(wpsg_q(strval($a->option_value))))
								{
									// welche in der momentanen Instanz nicht existiert
									if(!get_post_status(wpsg_q(strval($a->option_value))))
									{
	
										$this->shop->addBackendError(wpsg_translate(__('Die importierte Seite mit der ID #1# ist noch nicht angelegt worden. Daher wird sie auf "Keine Zuordnung" gestellt.', "wpng"), wpsg_q(strval($a->option_value))));
										$a->option_value = 0;
	
	
									}
	
								}
	
								if ($bExists > 0)
								{
	
									$this->db->UpdateQuery($wpdb->prefix."options", array(
										"option_value" => wpsg_q(strval($a->option_value))
									), "`option_name` = '".wpsg_q(strval($a->option_name))."'");
	
								}
								else
								{
	
									$this->db->ImportQuery($wpdb->prefix."options", array(
										"blog_id" => wpsg_q(strval($a->blog_id)),
										"option_name" => wpsg_q(strval($a->option_name)),
										"option_value" => wpsg_q(strval($a->option_value)),
										"autoload" => wpsg_q(strval($a->autoload))
									), true);
	
								}
	
							}
	
							$this->shop->addBackendMessage(__('Einstellungen wurden erfolgreich importiert.', 'wpsg'));
	
						}
	
					}
					else
					{
	
						$this->shop->addBackendError(__('Bitte eine Einstellungsdatei angeben!', 'wpsg'));
	
					}
					
				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=loadsavesettings');

			} else if (@$_REQUEST['subaction'] == 'includes') {

				check_admin_referer('wpsg-admin-includes');
				
			    $this->update_option('wpsg_load_css', $_REQUEST['wpsg_load_css'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_jquery', $_REQUEST['wpsg_load_jquery'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_thickbox_js', $_REQUEST['wpsg_load_thickbox_js'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_thickbox_css', $_REQUEST['wpsg_load_thickbox_css'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_validierung_js', $_REQUEST['wpsg_load_validierung_js'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_validierung_css', $_REQUEST['wpsg_load_validierung_css'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_load_bootstrap_glyphfont_css', $_REQUEST['wpsg_load_bootstrap_glyphfont_css'], false, false, WPSG_SANITIZE_CHECKBOX);

				$this->shop->callMods('admin_includes_save');

				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=includes');

			} else if (@$_REQUEST['subaction'] == 'blognetzwerk') {

				$this->update_option('wpsg_multiblog_standalone', $_REQUEST['wpsg_multiblog_standalone'], true);
				$this->update_option('wpsg_multiblog_sessionPath', $_REQUEST['wpsg_multiblog_sessionPath'], true);

				$this->addBackendMessage(__('Einstellungen gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=blognetzwerk');

			} else if (@$_REQUEST['subaction'] == 'kalkulation') {
				
				check_admin_referer('wpsg-admin-kalkulation');

			    $this->update_option('wpsg_kleinunternehmer', $_REQUEST['wpsg_kleinunternehmer'], false, false, WPSG_SANITIZE_CHECKBOX);
			    $this->update_option('wpsg_kleinunternehmer_text', $_REQUEST['wpsg_kleinunternehmer_text'], false, false, WPSG_SANITIZE_TEXTFIELD);
			    $this->update_option('wpsg_preisangaben', $_REQUEST['wpsg_preisangaben'], false, false, WPSG_SANITIZE_VALUES, ['1', '0']);
			    $this->update_option('wpsg_preisangaben_frontend', $_REQUEST['wpsg_preisangaben_frontend'], false, false, WPSG_SANITIZE_VALUES, ['1', '0']);
				$this->update_option('wpsg_hideemptyshipping', $_REQUEST['wpsg_hideemptyshipping'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_hideemptypayment', $_REQUEST['wpsg_hideemptypayment'], false, false, WPSG_SANITIZE_CHECKBOX);
				$this->update_option('wpsg_noroundamount', $_REQUEST['wpsg_noroundamount'], false, false, WPSG_SANITIZE_CHECKBOX);

				$this->addBackendMessage(__('Einstellung gespeichert.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction='.$_REQUEST['subaction']);

			} else if (@$_REQUEST['subaction'] == 'access') {
				
				check_admin_referer('wpsg-admin-access');

				$this->addBackendMessage(__('Berechtigungen gespeichert.', 'wpsg'));

				if (wpsg_isSizedArray($_REQUEST['wpsg_cap']))
				{
					 
					// Sanitization
					foreach ($_REQUEST['wpsg_cap'] as $k => $v) {
						
						foreach ($v as $_k => $_v) {
												
							if (
								!wpsg_checkInput($_v, WPSG_SANITIZE_CHECKBOX) ||
								!wpsg_checkInput($_k, WPSG_SANITIZE_TEXTFIELD)
							) {
								
								$this->shop->addBackendMessage(__('Bitte überprüfen sie die Formulareingaben.', 'wpsg'));
								
								unset($_REQUEST['wpsg_cap'][$k][$_k]);
								
							}  
														
						}
						
					}

					// Applying Settings
					foreach ($_REQUEST['wpsg_cap'] as $role_name => $cap)
					{

						$role_object = get_role($role_name);

						foreach ($cap as $cap_key => $cap_value)
						{

							if ($cap_value == '1')
							{

								$role_object->add_cap($cap_key);

							}
							else
							{

								$role_object->remove_cap($cap_key);

							}

						}

					}

				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction='.$_REQUEST['subaction']);

			} else if (@$_REQUEST['subaction'] == 'seiten') {

				check_admin_referer('wpsg-admin-seiten');
				
				// Seiten speichern
				$this->createPage(__('Anfrageliste', 'wpsg'), 'wpsg_page_request', $_REQUEST['wpsg_page_request']);
				$this->createPage(__('Warenkorb', 'wpsg'), 'wpsg_page_basket', $_REQUEST['wpsg_page_basket']);
				$this->createPage(__('Weiter shoppen', 'wpsg'), 'wpsg_page_basket_more', $_REQUEST['wpsg_page_basket_more']);
				$this->createPage(__('Versandkosten', 'wpsg'), 'wpsg_page_versand', $_REQUEST['wpsg_page_versand']);
				$this->createPage(__('Produktdetail', 'wpsg'), 'wpsg_page_product', $_REQUEST['wpsg_page_product']);
				$this->createPage(__('AGB', 'wpsg'), 'wpsg_page_agb', $_REQUEST['wpsg_page_agb']);
				$this->createPage(__('Datenschutz', 'wpsg'), 'wpsg_page_datenschutz', $_REQUEST['wpsg_page_datenschutz']);
				$this->createPage(__('Widerrufsbelehrung', 'wpsg'), 'wpsg_page_widerrufsbelehrung', $_REQUEST['wpsg_page_widerrufsbelehrung']);
				$this->createPage(__('Impressum', 'wpsg'), 'wpsg_page_impressum', $_REQUEST['wpsg_page_impressum']);
				
				//$this->update_option('wpsg_page_onlinedisputeresolution', $_REQUEST['wpsg_page_onlinedisputeresolution'], false, false, "key");
				
				$this->addBackendMessage(__('Seiteneinstellungen bearbeitet.', 'wpsg'));

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction='.$_REQUEST['subaction']);

			} else if (@$_REQUEST['action'] == 'module') {

				$global = false;
				if ($this->shop->isMultiBlog() && $this->shop->get_option('wpsg_multiblog_standalone', true) != '1') $global = true;

				if (!wpsg_checkInput($_REQUEST['aktiv'], WPSG_SANITIZE_CHECKBOX, ['allowEmpty' => true])) throw \wpsg\Exception::getSanitizeException();
				
				if ($this->get_option($_REQUEST['modul'], $global) > 0 && $_REQUEST['aktiv'] == '1' && array_key_exists($_REQUEST['modul'], $this->shop->arModule))
				{
					
					\check_admin_referer('wpsg-admin-submit-module-'.$_REQUEST['modul']);
					
					$this->shop->arModule[$_REQUEST['modul']]->settings_save();

					$this->shop->addBackendMessage(__('Moduleinstellungen gespeichert', 'wpsg'));

				}

				// Modul aktivieren wenn noch nicht aktiviert
				if ($_REQUEST['aktiv'] == '1' && $this->get_option($_REQUEST['modul'], $global) == false) {
 
					$this->update_option($_REQUEST['modul'], time(), $global);
					$this->shop->arAllModule[$_REQUEST['modul']]->installFirst();
					$this->shop->arAllModule[$_REQUEST['modul']]->install();

					// Ticket #637 (Automatisch Admin Rechte bei Aktivierung eines Moduls erteilen)
					$role_object = get_role("administrator");
					$role_object->add_cap("administrator");
					
					$this->shop->addBackendMessage(__('Modul aktiviert.', 'wpsg'));

				}
				else if ($_REQUEST['aktiv'] == '0')
				{

					$this->update_option($_REQUEST['modul'], false, $global);
					
					$this->shop->addBackendMessage(__('Modul deaktiviert.', 'wpsg'));
					
				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul='.$_REQUEST['modul']);

			} else {

				if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'update') {
					
					check_admin_referer('wpsg-admin-db-update');

					wpsg_install();
					$this->addBackendMessage(__('Datenbank erfolgreich abgeglichen!', 'wpsg'));

					$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=allgemein');

				}

			}

		} // public function submitAction()

		/**
		 * Soll die Datenbank auf den neuesten Stand bringen
		 */
		public function updateAction()
		{

			include WPSG_PATH.'/lib/install.php';

			$this->shop->callMods('install');

			$this->addBackendMessage(__('Datenbank aktualisiert', 'wpsg'));

		} // public function updateAction()

		/**
		 * Zeigt die Hilfe für den Tooltip an
		 */
		private function loadHelpAction()
		{

			global $q_config;

			$page = $_REQUEST['field'];

			$xml_content = $this->shop->get_url_content('http://trac.wpshopgermany.de/wiki/'.$page);

			if ($xml_content !== false && strlen(trim($xml_content)) > 0)
			{

				$doc = new DOMDocument();
				$doc->loadHTML($xml_content);

				$xpath = new DOMXPath($doc);
				$entries = $xpath->query('//div[@id="wikipage"]');

				$strReturn = '';

				foreach ($entries as $entry) {

					$strReturn = $entry->ownerDocument->saveXml($entry);

				}

				$strReturn = preg_replace('/\"\//', '"http://trac.wpshopgermany.de/', $strReturn);

				$arLocales = $arLocales = $q_config['locale'];
				if (wpsg_isSizedArray($arLocales) && wpsg_isSizedString(get_locale()))
				{

					$iso_lang = array_search(get_locale(), $arLocales);

					if (wpsg_isSizedString($iso_lang) && preg_match('/&lt;localization_'.$iso_lang.'&gt;(.*)&lt;\/localization_'.$iso_lang.'&gt;/is', $strReturn))
					{

						$strReturn = preg_replace('/(.*)&lt;localization_'.$iso_lang.'&gt;/is', '', $strReturn);
						$strReturn = preg_replace('/&lt;\/localization_'.$iso_lang.'&gt;(.*)/is', '', $strReturn);

					}


				}

				// Andere Übersetzungen entfernen
				$strReturn = preg_replace('/&lt;localization_(.*)&gt;(.*)&lt;\/localization_(.*)&gt;/is', '', $strReturn);

				//$strReturn .= '<a style="color:#FFFFFF; text-decoration:underline;" target="_blank" href="http://trac.wpshopgermany.de/wiki/'.$page.'">'.__('zur Hilfe Seite ...', 'wpsg').'</a><br /><br />';

			}
			else
			{

				$strReturn = __('Noch kein Hilfetext hinterlegt. Bitte versuchen Sie es später noch einmal.<br />Hilfe finden sie auch unter <a href="http://forum.maennchen1.de">http://forum.maennchen1.de</a>', 'wpsg');

			}

			die('<div class="wpsg-help-content">'.$strReturn.'</div>');

		} // private function loadHelpAction()

		/**
		 * Deaktiviert alle Plugins außer wpShopGermany und merkt sich die aktivierten Plugins
		 */
		public function plugintest_disableAction()
		{

			// Plugins vor dem Test
			$arPlugins = $this->shop->get_option('active_plugins');

			// Plugins vor dem Test speichern
			$this->shop->update_option('wpsg_plugintest_active_plugins', $arPlugins);

			// Alle Plugins außer wpShopGermany entfernen
			foreach ($arPlugins as $k => $p)
			{

				if (!preg_match('/(.*)wpshopgermany\.php(.*)/', $p)) unset($arPlugins[$k]);

			}

			// Zurückspeichern
			$this->shop->update_option('active_plugins', $arPlugins);

			$this->shop->addBackendMessage(__('Es wurden alle Plugins bis auf wpShopGermany deaktiviert.', 'wpsg'));
			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=ueber');

		} // public function plugintest_disableAction()

		/**
		 * Stellt die vorher mittels plugintest_disableAction deaktivierten Module wieder her
		 */
		public function plugintest_restoreAction()
		{

			$arPlugins_restore = $this->shop->get_option('wpsg_plugintest_active_plugins');

			if (!wpsg_isSizedArray($arPlugins_restore))
			{

				$this->shop->addBackendError(__('Plugins konnten nicht wieder hergestellt werden, da nicht gespeichert wurde welche Plugins aktiv waren.', 'wpsg'));

			}
			else
			{

				// Alte Plugins wieder herstellen
				$this->shop->update_option('active_plugins', $arPlugins_restore);

				$this->shop->update_option('wpsg_plugintest_active_plugins', false);

				$this->shop->addBackendMessage(__('Aktivierte Plugins wurden erfolgreich wieder hergesetellt.', 'wpsg'));

			}

			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=ueber');

		} // public function plugintest_restoreAction()

		/**
		 * Installiert ein Modul
		 */
		public function installModul($modul_key)
		{

			global $wp_filesystem;

			$temp_name = $this->shop->getTempName($modul_key.'.zip');
			@unlink($temp_name);
				
			$wpsg_update_data = wpsg_get_update_data();
			$url = $wpsg_update_data['modulinfo'][$modul_key]['download_url'];
			
			if (!wpsg_isSizedString($url)) throw new \Exception("Ungültige Download URL.");
						
			$bOK = @copy($url, $temp_name);
			
			if (!$bOK)
			{
			
				$bOK = @file_put_contents($temp_name, $this->shop->get_url_content($url));
			
				if (!$bOK)
				{
						
					// Pfad finden für das Wordpress Filesystem
					if ($GLOBALS['wpsg_sc']->isMultiBlog())
						$ftp_path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/';
					else
						$ftp_path = WP_CONTENT_DIR.'/uploads/wpsg/wpsg_temp/';
								
						$ftp_path = trailingslashit($wp_filesystem->find_folder($ftp_path)).preg_replace('/(.*)\/wpsg_temp\//', '', $temp_name);
								
						$bOK = $wp_filesystem->put_contents($ftp_path, $this->shop->get_url_content($url), FS_CHMOD_FILE);
								
						if (!$bOK)
						{
									
							$this->shop->addBackendError(wpsg_translate(__('Modul (#1#) konnte nicht kopiert werden!', 'wpsg'), $modul_key));
							return false;
			
						}
								
				}
			
			}
				
			$zip = new ZipArchive();
			 
			if ($zip->open($temp_name) === true)
			{
			
				try
				{
															
					$bOK = @$zip->extractTo(realpath(WPSG_PATH.'/../'));
					$zip->close();
			
					if ($bOK === false)
					{
			
						throw new Exception();
							
					}
					else
					{
			
						return true;
							
					}
			
				}
				catch (Exception $e)
				{
						
					// Es ist ein Fehler aufgetreten, jetzt versuche ich die Datei mit dem Wordpress Filesystem zu entpacken
						
					// Pfad finden für das Wordpress Filesystem
					$ftp_path = trailingslashit($wp_filesystem->find_folder(realpath(WPSG_PATH.'/../')));
						
					$bOK = unzip_file($temp_name, $ftp_path);
						
					if ($bOK !== true)
					{
			
						$strError = '';
			
						// Hier ist immer noch ein Fehler aufgetreten
						foreach ((array)$bOK->errors as $e)
						{
								
							$strError .= $e[0].' ';
			
						}
						 
						$this->shop->addBackendError(wpsg_translate(__('Fehler beim Entpacken: #1#', 'wpsg'), $strError)); return false;
			
					}
					else
					{
			
						return true;
			
					}
						
				}
			
			}
			else
			{
			
				$this->shop->addBackendError(__('Datei konnte nicht übertragen werden.', 'wpsg')); return false;
			
			}
			
			$this->shop->addBackendError(__('Datei konnte nicht entpackt werden.', 'wpsg')); return false;

		} // private function installModul($modul_key)

		/**
		 * Nach dem Update auf Version3 ist es durch den Export in die XML Datei zu einer serialisierung des schon serialisierten Strings gekommen
		 * Diese Funktion korrigiert dies
		 * Es betrifft folgende Schlüssel:
		 *
		 * wpsg_admin_pflicht
		 * wpsg_mod_discount_data
		 * wpsg_mod_downloadplus_text
		 * wpsg_mod_pdfdownload_text
		 * wpsg_mod_rechnungen_texte
		 * wpsg_rechnungen_footer
		 *
		 * Aufgerufen wird sie im Backend bei den allgemeinen Einstellungen
		 */
		private function CheckAndCorrectSerOption()
		{

			$arOptions = array('wpsg_admin_pflicht', 'wpsg_mod_discount_data', 'wpsg_mod_downloadplus_text', 'wpsg_mod_pdfdownload_text', 'wpsg_mod_rechnungen_texte', 'wpsg_rechnungen_footer');

			foreach ($arOptions as $o)
			{

				$value = $this->shop->get_option($o);

				if ($value === false) continue; // Wert ist überhaupt nicht gesetzt, eventuell weil Modul nicht aktiviert

				if (!is_array($value) && (is_string($value) && preg_match('/^a\:\d+/', $value)))
				{

					$korrektur_value = @unserialize($value);

					if (is_array($korrektur_value)) $this->shop->update_option($o, $korrektur_value);


				}

			}

		} // public function CheckAndCorrectSerOption()

	} // class wpsg_AdminController extends wpsg_SystemController

?>