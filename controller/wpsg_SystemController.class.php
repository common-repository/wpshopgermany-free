<?php

	/**
	 * Primäre Klasse, von dem alle anderen Controller abgeleitet sind
	 */
	class wpsg_SystemController 
	{
				
		/** Datenbankobjekt */
		var $db;
		
		/** @var wpsg_ShopController Das Shop Objekt vom Typ wpsg_ShopController */
		var $shop;
		
		/** @var wpsg_imagehandler Das Imagehandler Objekt */
		var $imagehandler;
		
		/** Text des letzten CURL Fehlers */
		var $last_curl_error;
		
		/** @var array */
		var $arTemplateStack;
		
		/** @var boolean - Wird auf true gesetzt, wenn die Meldungen ausgegbene werden */
		public $bMessageOut = false;
		 
		var $htmlMail = false;
		
		/**
		 * Contstructor
		 */
		public function __construct() {
			
			$this->db = &$GLOBALS['wpsg_db'];
			
			if (get_class($this) != 'wpsg_ShopController') {

				$this->shop = &$GLOBALS['wpsg_sc'];
				
			}
			
			$this->imagehandler = &$GLOBALS['wpsg_ih'];
			
			$this->view = array();
			$this->arTemplateStack = array();
			
		} // public function __construct()
		
		/**
		 * Wird von den abgeleiteten Controllern aufgerufen wenn sie die Ausgabe übernehmen
		 */
		public function dispatch()
		{
			
			$this->shop->checkEscape();
			
		} // public function dispatch()
				
		/**
		 * Gibt eine Einstellung anhand ihres Schlssels zur�ck
		 * Erst mal nur Tunnel für die Wordpress Funktion
		 */
		public function &get_option($key, $force_global = false)
		{
			
			$return = false;
			
			if ($force_global)
				$return = get_site_option($key);
			else
				$return = get_option($key);

			return $return;
			
		} // public function get_option($key)
		
		/**
		 * Setzt eine Einstellung
		 * Est mal nur Tunnel für die Wordpress Funktion
		 *
		 * @param       $key
		 * @param       $value
		 * @param bool $force_global
		 * @param bool $addTrans
		 * @param null $sanitize_type
		 * @param array $sanitize_params
		 * @return bool
		 * @throws Exception
		 */
		public function update_option($key, $value, $force_global = false, $addTrans = false, $sanitize_type = NULL, $sanitize_params = array()) {

			// fallback to check data Textfield (sanitize_text_field)
			if ($sanitize_type === null) $sanitize_type = WPSG_SANITIZE_TEXTFIELD;
							
			$bValid = wpsg_checkInput($value, $sanitize_type, $sanitize_params);

			if (!$bValid) {
				 
				// Not valid
				
				$GLOBALS['wpsg_sc']->addBackendError(__('Ihre Eingaben in den markierten Feldern waren ungültig, bitte überprüfen.', 'wpsg'));
				
				$_SESSION['sanitization_err_fields'][$key] = 0;
				
				return false; 
				
			} else {
			
				// Sanitized
				
				if ($force_global) {
					
					update_site_option($key, $value);
					
				} else {
					
					update_option($key, $value);
					
				}
				
				if ($addTrans === true) $GLOBALS['wpsg_sc']->addTranslationString($key, $value);
				
			}
			
		} // public function update_option($key, $value)
		
		/**
		 * Führt eine Header Weiterleitung durch. Es darf vorher keine Ausgabe erfolgen (logischerweise)
		 */
		public function redirect($url) 
		{

			header('Location: '.html_entity_decode($url));
			exit;

		} // public function redirect($url)
		
		/**
        * Führt eine Anfrage an $url mittels curl durch und liefert das Ergebniss als String zurück
        * POST Daten können dabei mittels $post_data angehängt werden
        * @param string $url URL die angefragt werden soll
        * @param array $post_data Daten, die mittels POST mitgesendet werden sollen
        */
        public function get_url_post_content($url, $post_data = array(), $addition_curl_options = array())
        {

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HEADER, 0);

            if (wpsg_isSizedArray($addition_curl_options))
            {

                foreach ($addition_curl_options as $option_key => $option_value)
                {

                    curl_setopt($ch, $option_key, $option_value);

                }

            }

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($post_data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data, null, '&'));

            $data = curl_exec($ch);

            if ($data === false)
            {

                $this->last_curl_error = curl_error($ch);

                wpsg_debug(curl_error($ch));

            }

            curl_close($ch);

            return $data;

        } // public function get_url_post_content($url, $post_data = array())
		
		/**
		 * Gibt die Antwort einer URL zurück
		 */
		public function get_url_content($url)
		{
			
			$Return = @file_get_contents($url);			
						
			if (!$Return)
			{

				$ch = curl_init();
 
				curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
 
				$data = curl_exec($ch); 
				curl_close($ch);
 
				return $data;
				
			}
			else
			{
				
				return $Return;
				
			}
			
		} // public function get_url_content($url)
		
		/**
		 * Fügt eine Hinweismeldung eines Backend Moduls hinzu 
 		 * Wird mittels writeBackendMessage ausgegeben
		 */
		public function addBackendMessage($message)
		{

            $message_key = md5($message);

            // Wenn schon drin, dann nichts machen
            if (@array_key_exists($message_key, (array)$_SESSION['wpsg']['backendError'])) return;

            if (substr($message, 0, 7) === 'nohspc_') $message = substr($message, 7);
            else $message = wpsg_hspc($message);

            $message = '
                <div id="message" class="notice notice-success">
                    <p>'.$message.'</p>
                </div>
            ';

            if (!wpsg_isSizedArray($_SESSION['wpsg']['backendMessage'])) $_SESSION['wpsg']['backendMessage'] = array();

			$_SESSION['wpsg']['backendMessage'][$message_key] = $message;

		} // public function addBackendMessage($message)
	
		public function addInputFieldError($field_name, $field_label) {
			
			$this->addBackendError(
				wpsg_translate(
					__('Überprüfen Sie die Eingaben im Feld "#1#", diese war ungültig.', 'wpsg'),
					$field_label
				)
			);
			
			$_SESSION['sanitization_err_fields'][$field_name] = 0;
			
		}
		
		/**
		 * Fügt eine neue Fehlermeldung eines Backend Moduls hinzu
		 *
		 * @param \String $hideLink Soll die Meldung ausblendbar sein, so muss ein Key mitgegeben werden der die
		 *                          Meldung identifiziert
		 *
		 * @return bool|void
		 */
		public function addBackendError($message, $hideLinkKey = false, $addBlendOut = true)
		{
			
			if ($hideLinkKey === false) $message_key = md5($message);
			else $message_key = $hideLinkKey;
			
			// Wenn schon drin, dann nichts machen
            if (@array_key_exists($message_key, wpsg_getArray($_SESSION['wpsg']['backendError']))) return;

            if (substr($message, 0, 7) === 'nohspc_') $message = substr($message, 7);
            else $message = wpsg_hspc($message);

            $arMsgHidden = $this->get_option('wpsg_msgHidden');
            if (!wpsg_isSizedArray($arMsgHidden)) $arMsgHidden = array();

			if (wpsg_isSizedString($hideLinkKey) && $addBlendOut === true)
			{
				
				// Wurde die Meldung bereits ausgeblendet ?
				if (in_array($hideLinkKey, $arMsgHidden)) return false;

                $data_dismiss_url = WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=clearMessage&msg_key='.$hideLinkKey.'&noheader=1';

				$message = '
                    <div id="message" class="error notice is-dismissible wpsg-is-dismissible" data-dismiss-url="'.$data_dismiss_url.'">
                        <p>'.$message.'</p>                        
                    </div>
                ';

			}
			else
            {

                $message = '
                    <div id="message" class="error notice">
                        <p>'.$message.'</p>
                    </div>
                ';

            }
							
			$_SESSION['wpsg']['backendError'][$message_key] = $message;
							
		} // public function addBackendError($message, $hideLinkKey = false)

		/**
		 * Gibt true oder false zurück, jenachdem ob man sich in einem Blognetzwerk befindet
		 */
		public function isMultiBlog()
		{
			
			if (defined('MULTISITE') && MULTISITE === true) 
			{
				
				// Multiblog ist aktiviert
				return true;
				
			}
			else
			{
				return false;
			}
			
		} // public function isMultiBlog() 
		
		/**
		 * Gibt den Pfad zurück in dem wpShopGermany seine Daten ablegt
		 * Der Pfad soll ab Version 4.0 nicht über den Browser zugreifbar sein, die Funktion soll das sicherstellen
		 */
		public function getStorageRoot()
		{
			
			if ($this->isMultiBlog()) {

				$path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/';
				 								 
			} else {
				
				$path = WPSG_PATH_CONTENT.'uploads/wpsg/';				
				
			}
						
			return $path;
			
		} // public function getStorageRoot()
		
		/**
		 * Fügt eine Meldung fürs Frontend hinzu
		 * Die Meldungen werden zum nächstmöglichen Zeitpunkt ausgegeben
		 */
		public function addFrontendMessage($message)
		{
			
			if (!isset($_SESSION['wpsg']['frontendMessage'])) $_SESSION['wpsg']['frontendMessage'] = array();
			
			if (!in_array($message, (array)$_SESSION['wpsg']['frontendMessage'])) $_SESSION['wpsg']['frontendMessage'][] = $message;
			
		} // public function addFrontendMessage($message)
		
		/**
		 * Fügt eine Fehlermeldung fürs Frontend hinzu
		 * Die Meldungen werden zum nächstmöglichen Zeitpunkt ausgegeben
		 */
		public function addFrontendError($message)
		{
			
			if (!isset($_SESSION['wpsg']['frontendError'])) $_SESSION['wpsg']['frontendError'] = array();
			
			if (!in_array($message, (array)$_SESSION['wpsg']['frontendError'])) $_SESSION['wpsg']['frontendError'][] = $message;
								
		} // public function addFrontendError($error)
		
		public function hasFrontendMessage() { return wpsg_isSizedArray($_SESSION['wpsg']['frontendMessage']); }
		
		public function hasFrontendError() { return wpsg_isSizedArray($_SESSION['wpsg']['frontendError']); }
		
		public function hasBackendError() { $arE = $_SESSION['wpsg']['backendError']; unset($arE['wpsg_systemcheck']); return wpsg_isSizedArray($arE); }
		
		/**
		 * Gibt die Fehler und Hinweise im Frontend aus
		 */
		public function writeFrontendMessage()
		{
			
			$this->bMessageOut = true;
			
			$strOut  = '';
			
			if (wpsg_isSizedArray($_SESSION['wpsg']['frontendMessage']))
			{
	 
				$strOut  .= '<ul id="wpsg_message" class="wpsg_message_wrap">';
				
				foreach ($_SESSION['wpsg']['frontendMessage'] as $m) 
				{
					$strOut .= '<li>'.$m.'</li>';
				}
	
				$strOut .= '</ul>';

				//wird jetzt in der Shutdown Action gemacht
				//unset($_SESSION['wpsg']['frontendMessage']);
				
			}
										
			if (wpsg_isSizedArray($_SESSION['wpsg']['frontendError']))
			{

				$strOut  .= '<ul id="wpsg_error" class="wpsg_error_wrap">';
				
				foreach ($_SESSION['wpsg']['frontendError'] as $m) 
				{
					$strOut .= '<li>'.$m.'</li>';
				}
	
				$strOut .= '</ul>';
				
				//wird jetzt in der Shutdown Action gemacht
				//unset($_SESSION['wpsg']['frontendError']);
				
			}
		 
			return $strOut;
			
		} // public function writeFrontendMessage()
		
		/**
		 * Gibt die Backend Messages aus 
		 */
		public function writeBackendMessage($onlyMessage = false)
		{

			$this->bMessageOut = true;
			$GLOBALS['wpsg_sc']->bMessageOut = true;
			
			$strOut  = '';
			 
			if (wpsg_isSizedArray($_SESSION['wpsg']['backendMessage'])) {

				foreach ($_SESSION['wpsg']['backendMessage'] as $m) { $strOut .= $m; }

				unset($_SESSION['wpsg']['backendMessage']);
				
			}
			
			if (wpsg_isSizedArray($_SESSION['wpsg']['backendError'])) {

				foreach ($_SESSION['wpsg']['backendError'] as $m) { $strOut .= $m; }

                unset($_SESSION['wpsg']['backendError']);

			}

			if (wpsg_isSizedString($strOut)) $strOut = '<div class="wrap">'.$strOut.'</div>';

			return $strOut;
			
		} // public function writeBackendMessage()
		
		/**
		 * Löscht die Session mit den fehlerhaft eingegebenen Feldern
		 */
		public function ClearSessionErrors()
		{
			
			unset($_SESSION['wpsg']['errorFields']);
			
		} // public function ClearSessionErrors()
		
		/**
		 * Löscht die Fehlermeldungen für das Frontend
		 */
		public function clearFrontendError()
		{
			
			unset($_SESSION['wpsg']['frontendError']);
			
		}
		
		/**
		 * Löscht die Meldungen für das Frontend
		 */
		public function clearFrontendMessage()
		{
		
			unset($_SESSION['wpsg']['frontendMessage']);
			
		}
				
		/**
		 * Löscht die Meldungen aus der Session
		 */
		public function clearMessages()
		{
				
			unset($_SESSION['wpsg']['backendError']);
			unset($_SESSION['wpsg']['backendMessage']);
				
			$this->clearFrontendError();
			$this->clearFrontendMessage();
							
		} // public function clearMessages()
		
		/**
		 * Gibt die URL zu einer Ressource (JS/GFX/CSS/..) die unter views liegt zurück
		 * In der Variable path wird der Pfad ab dem views Verzeichni übergeben. Es wird dann geprüft ob die Ressource
		 * im user_views liegt und wenn ja dieser Pfad zurückgegeben
		 */
		public function getRessourceURL($path)
		{

            if (file_exists(WPSG_PATH_CHILD_TEMPLATEVIEW.$path) && $this->get_option('wpsg_ignoreuserview') != '1')
            {

                // Datei existiert im UserView
                $url = get_stylesheet_directory_uri().'/wpsg_views/'.$path;

            }
            else if (file_exists(WPSG_PATH_TEMPLATEVIEW.'/'.$path) && $this->get_option('wpsg_ignoreuserview') != '1')
            {

                // Datei existiert im Template
                $url = get_template_directory_uri().'/wpsg_views/'.$path;

            }
			else if (file_exists(WPSG_PATH_USERVIEW.$path) && $this->get_option('wpsg_ignoreuserview') != '1')
			{
				
				// Datei existiert im UserView
				$url = WPSG_URL_USERVIEW.$path;
				
			}
			else if (file_exists(WPSG_PATH_USERVIEW_OLD.'/'.$path) && $this->get_option('wpsg_ignoreuserview') != '1')			
			{
				
				// Datei existiert im alten UserView
				$url = WPSG_URL_CONTENT.'plugins/'.WPSG_FOLDERNAME.'/user_views/'.$path;
				
			}
			else
			{
				
				$url = WPSG_URL_CONTENT.'plugins/'.WPSG_FOLDERNAME.'/views/'.$path;
				
			}
			
			$url = $this->url($url);
			
			return $url;
			
		} // public function getRessourceURL($path)
		
		/**
		 * Gibt die URL für die Flaggen bei Mehrsprachigkeit zurück
		 */
		public function getFlagURL()
		{

			if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) return '';
			else return WPSG_URL_CONTENT.'plugins/qtranslate-x/flags/';
			
		} // public function getFlagURL()
		
		/**
		 * Bearbeitet URLs
		 * Die Funktion macht erst einmal nichts
		 * @param \String $url URL vor Bearbeitung
		 * @return \String $url URL nach Bearbeitung
		 */
		public function url($url)
		{
			
			return $url;
			//return preg_replace('/(^http:\/\/)|(^https:\/\/)|(^ssl:\/\/)/', '//', $url);
			
		} // public function url($url)
		
		/**
		 * Gitb Pfad zu einer Datei die unter views liegt zurück
		 * In der Variable path wird der Pfad ab dem views Verzeichni übergeben. Es wird dann geprüft ob die Datei
		 * im user_views liegt und wenn ja dieser Pfad zurückgegeben
		 */
		public function getRessourcePath($path) {

            $plugin_dir = ABSPATH.WPSG_CONTENTDIR_WP.'/plugins/';
                        
            if (strpos(realpath($path), realpath ($plugin_dir)) === 0) {
                
                $view_path = preg_replace('/(.*)views/', '',realpath($path));
                                
            } else $view_path = $path;
		    
			if (file_exists(WPSG_PATH_USERVIEW.$view_path)) {
			
				// Datei existiert im UserView
				return WPSG_PATH_USERVIEW.$view_path;
				
			} else if (file_exists(WPSG_PATH_TEMPLATEVIEW.'/'.$view_path)) {
				 
				// Datei existiert im Template
				return WPSG_PATH_TEMPLATEVIEW.'/'.$view_path;
				
			} else if (file_exists(WPSG_PATH_USERVIEW_OLD.'/'.$view_path)) {

				// Datei existiert im alten UserView
				return WPSG_PATH_USERVIEW_OLD.'/'.$view_path;
				
			} else {

			    if ($path !== $view_path) return $path;
			    else return WPSG_PATH_VIEW.'/'.$path;
				
			}
			
		} // public function getRessourceURL($path)

        public function getTemplatefile($file)
        {

            // Da die views jetzt im Pluginverzeichnis liegen suche ich den (.*)view Teil
            $path_view = preg_replace('/\/views\/(.*)/', '', $file).'/views/';

            // Datei im UserView
            $uv_file = str_replace($path_view, WPSG_PATH_USERVIEW, $file);
            $uv_old_file = str_replace($path_view, WPSG_PATH_USERVIEW_OLD, $file);
            $theme_file = str_replace($path_view, WPSG_PATH_TEMPLATEVIEW, $file);
            $child_theme_file = str_replace($path_view, WPSG_PATH_CHILD_TEMPLATEVIEW, $file);

            if (file_exists($child_theme_file) && $this->get_option('wpsg_ignoreuserview') != '1') $render_file = $child_theme_file;
            else if (file_exists($theme_file) && $this->get_option('wpsg_ignoreuserview') != '1') $render_file = $theme_file;
            else if (file_exists($uv_file) && $this->get_option('wpsg_ignoreuserview') != '1') $render_file = $uv_file;
            else if (file_exists($uv_old_file) && $this->get_option('wpsg_ignoreuserview') != '1') $render_file = $uv_old_file;
            else if (file_exists($file)) $render_file = $file;
            else throw new \wpsg\Exception(wpsg_translate(__('Template (#1#) Datei nicht gefunden', 'wpsg'), $file), \wpsg\Exception::TYP_UNEXPECTED);

            // Ich lasse nur Dateien unterhalb von wp-content zu aus Sicherheitsgründen
            if (strpos(sanitize_file_name(realpath($render_file)), sanitize_file_name(WPSG_PATH_CONTENT)) !== 0 || !preg_match('/\.phtml$/i', $render_file)) {
                 
                throw new \Exception(__('Zugriffsfehler!', 'wpsg'));
                
            }
                        
            return $render_file;

        }

		/**
		 * Zeigt eine Template Datei an
		 */
		public function render($file, $out = true)
		{

			// Ticket #572 doppelte Slash stören
			$file = str_replace('//', '/', $file);
			
			$this->arTemplateStack[] = preg_replace('/(.*)\//', '', $file);

			if (!$out) { ob_start(); }
			
			if (sizeof($this->arTemplateStack) == 1 && $this->get_option('wpsg_autoraw') === '1' && !is_admin() && !$this->bShortcode) echo '[raw]';

            $render_file = $this->getTemplatefile($file);

			if ($this->get_option('wpsg_displayTemplatesLog') > 0) wpsg_debug_console($this->clearPathForDebug($render_file));
			
			if (!is_admin() && $this->get_option('wpsg_displayTemplates') > 0)
			{
				
				echo '<div style="display:inline-block; box-shadow: inset 0px 0px 0px 1px #003C6A; position:relative;">';			
				echo '<div style="background-color:#003C6A; color:#FFFFFF; white-space:nowrap; font-size:11px; font-family:monospace; line-height:14px; margin-top:-14px; position:absolute;">'.$this->clearPathForDebug($render_file).'</div>';
								
			}
			
			include $render_file;
			
			if (!is_admin() && $this->get_option('wpsg_displayTemplates') > 0)
			{
				
				echo '</div>';
				
			}
			
			if (sizeof($this->arTemplateStack) == 1 && $this->get_option('wpsg_autoraw') === '1' && !is_admin() && !$this->bShortcode) echo '[/raw]';
			
			array_pop($this->arTemplateStack);
						
			if (!$out) { $content = ob_get_contents(); ob_end_clean(); return $content; }
			
		} // public function render($file)
		
		public function clearPathForDebug($file)
		{
			
			$file = preg_replace('/(\/\/)|(\\\)/', '/', $file);
			$file = str_replace(WPSG_PATH_WP, '/', $file);
			
			return $file;
			
		} // private function clearPath($file)
		
		/**
		 * Gibt die Locale der Standard Backend Sprache zurück
		 * de / en etc.
		 */
		public function getDefaultLanguageCode()
		{

			return $this->get_option('wpsg_backend_language');
						
		} // public function getDefaultLanguageCode()

		/**
		 * Gibt die Locale für die aktuelle Sprache zurück
		 */
		public function getDefaultLanguageLocale()
		{

			if (!$this->force_locale) 
			{
				
				$arStoreLanguages = $this->getStoreLanguages();
				
				foreach ($arStoreLanguages as $lang)
				{
					
					if ($lang['lang'] == $this->getDefaultLanguageCode()) return $lang['locale'];
					
				}
				
				return false; 
				
			}
			else return $this->force_locale;
			
		} // public function getDefaultLanguageLocale()
 
		public function getCurrentLanguageCode()
		{
			 
			$arStoreLanguages = $this->getStoreLanguages();
				
			foreach ($arStoreLanguages as $lang)
			{
				
				if ($lang['locale'] == $this->getCurrentLanguageLocale()) return $lang['lang'];
				
			}
			
			return '';
			
		}
		
		public function getCurrentLanguageLocale()
		{
			
			return get_locale();
			
		}
				
		
		/**
		 * Ändert zeitweise die Locale, um z.B. die Sprache einer Bestellung zu berücksichtigen
		 * Nach dem Aufruf und der Durchführung sollte wieder die Originalspreach mit restoreTemLocale gesetzt werden
		 */
		public function setTempLocale($locale) {

			global $l10n;
		
			if (file_exists(dirname(__FILE__).'/../lang/wpsg-'.$locale.'.mo')) {
			
				$this->old_l10n = clone $l10n['wpsg']; 
				$this->force_locale = $locale;
			 			 
				call_user_func_array(
					'load_textdomain',
					array(
						'wpsg', dirname(__FILE__).'/../lang/wpsg-'.$locale.'.mo'
					)
				);
				
			}
			
		} // public function setTempLocale($locale)
		
		/**
		 * Setzt die Sprache auf die Original Sprache zurück
		 * Wird die Sprache zeitweise mit setTempLocale geändert, so sollte sie am Ende wieder zurückgesetzt werden
		 */
		public function restoreTempLocale()
		{
			
			global $l10n;
			
			if (isset($this->old_l10n) && $this->old_l10n !== false)
			{
				
				$l10n['wpsg'] = clone $this->old_l10n;

				$this->force_locale = false;
				$this->old_l10n = false;
				
			}
			
		} // public function restoreTempLocale()
			
		/**
		 * Erstellt eine neue Seite im Wordpress 
		 */
		public function createPage($title, $page_key, $page_id)
		{

			global $wpdb, $current_user;

			if (!wpsg_checkInput($page_id, WPSG_SANITIZE_PAGEID)) return false;
			
			if ($page_id == -1) 
			{
				
				$user_id = 0;
			
				if (function_exists("get_currentuserinfo"))
				{
					//get_currentuserinfo();
					$user_id = $current_user->user_ID;
				}
				
				if ($user_id == 0 && function_exists("get_current_user_id"))
				{
					$user_id = get_current_user_id();
				} 
				
				$page_id = $this->db->ImportQuery($wpdb->prefix."posts", array(
					"post_author" => $user_id,
					"post_date" => "NOW()",
					"post_title" => $title,
					"post_date_gmt" => "NOW()",
					"post_name" => mb_strtolower($title),
					"post_status" => "publish",
					"comment_status" => "closed",
					"ping_status" => "neue-seite",
					"post_type" => "page",
					"post_content" => '',
					"ping_status" => "closed",
					"comment_status" => "closed",
					"post_excerpt" => "",
					"to_ping" => "",
					"pinged" => "",
					"post_content_filtered" => ""
				));

				$this->db->UpdateQuery($wpdb->prefix."posts", array(
					"post_name" => $this->clear($title, $page_id)
				), "`ID` = '".wpsg_q($page_id)."'");
				
				 $set_language_args = array(
					'element_id' => $page_id,
					'element_type'  => 'post_page',
					'trid' => false,
					'language_code' => 'de'
				);
		 
				if (function_exists('icl_object_id'))
				{
				
					do_action('wpml_set_element_language_details', $set_language_args);
				
				}
								
			}
			
			$this->update_option($page_key, $page_id);
			
		} // private function createPage($title)
		 
		/**
		 * Bereinigt den URL Key bzw. das Path Segment
		 * Ist der Parameter post_id angegeben, so wird überprüft das kein Post ungleich dieser ID mit diesem Segment existiert
		 */
		public function clear($value, $post_id = false)
		{
				 
			global $wpdb;
			
			$arReplace = array(
				'/Ö/' => 'Oe', '/ö/' => 'oe',
				'/Ü/' => 'Ue', '/ü/' => 'ue',
				'/Ä/' => 'Ae', '/ä/' => 'ae',
				'/ß/' => 'ss', '/\040/' => '-',
				'/\€/' => 'EURO',
				'/\//' => '_',
				'/\[/' => '',
				'/\]/' => '',
				'/\|/' => ''
			);
			
			$strReturn = preg_replace(array_keys($arReplace), array_values($arReplace), $value);
			$strReturn = sanitize_title($strReturn);
		 			
			if (is_numeric($post_id) && $post_id > 0)
			{
				
				$n = 0;
				
				while (true)
				{
					
					$n ++;
					
					$nPostsSame = $this->db->fetchOne("SELECT COUNT(*) FROM `".$wpdb->prefix."posts` WHERE `post_name` = '".wpsg_q($strReturn)."' AND `id` != '".wpsg_q($post_id)."'");
					
					if ($nPostsSame > 0)
					{
						
						$strReturn .= $n;
						
					}
					else
					{
						
						break;
						
					}
					
				}
				
			}
			
			return $strReturn;
			
		} // private function clear($value)
		
	} // public class SystemController 

?>