<?php

	/**
	 * Zentrale Klasse des wpShopGermanys
	 */
	class wpsg_ShopController extends wpsg_SystemController
	{

		/**  Array mit allen Installierten Modulen */
		var $arModule;

		/** True, wenn eine Lizenz verwendet wird */
		var $bLicence = false;

		/** BasketController */
		var $basketController;

		/** @var wpsg_basket Der Warenkorb */
		var $basket;

		/** Alle Versandkosten der aktivierten Module */
		var $arShipping = null;
		var $arShippingAll = null;

		/** Versandarten wurden schon zusammengeführt */
		var $bShippingMerged = false;

		/** Alle Zahlungsarten der aktivierten Module */
		var $arPayment;

		/** Alle Bestellzustände */
		var $arStatus;

		/** Tabellenprefix */
		var $prefix;

		/** Cache Klasse */
		var $cache;

		/** Bindet im wp_foot den Layer ein, sollte es eine Shop Ausgabe erfordern */
		var $showEULayer = false;

		/** Ermöglicht es in einer anderen Sprache als die aktuelle Locale zu arbeiten */
		var $force_locale = false;

		/** Gibt an, ob das Produkt über einen Shortcode gerendert wird (Für [raw] Funtkion wichtig) */
		var $bShortcode = false;

		/** Wurden die Versandarten schon zusammengefügt */
		var $shipping_alreadymerged = false;

		/** Ob im Warenkorb mit gerundeten Werten weitergerechnet werden soll */
		var $addRoundedValues = true;

		/** Wird von der the_title auf true gesetzt wenn man auf einer Produktseite ist */
		var $titleDisplayed = false;

        /** Informationen die im SystemCheck gesammelt werden */
        public $arSystemCheck = null;
        
        /** Das Land, damit die Berechnung im BE beim bearbeiten des Produktes klappt */
        public $country = 0;

        /** Object Cache für die loadProduktArray */
        private $productCache = Array();
        
		const CHECK_NOTICE = 1;
		const CHECK_WARNING = 2;
		const CHECK_ERROR = 3;

		/** URL Konstanten */
		const URL_BASKET = 1;
		const URL_BASKET_AJAX = 2;
		const URL_CHECKOUT = 3;
		const URL_CHECKOUT2 = 4;
		const URL_OVERVIEW = 5;
		const URL_AGB = 6;
		const URL_VERSANDKOSTEN = 7;
		const URL_DATENSCHUTZ = 8;
		const URL_WIDERRUF = 9;
		const URL_IMPRESSUM = 10;
		const URL_PROFIL = 11;
		const URL_WIDGET_AJAX = 12;
		const URL_ORDER = 13;
		const URL_LOGOUT = 14;
		const URL_ONLINE_DISPUTE_RESOLUTION = 15;
		const URL_REQUEST = 16;
		const URL_LOSTPWD = 17;
		const URL_PRODUCTDETAIL = 18;
		const URL_ABO = 19;
		const URL_BASKET_MORE = 20;

		/** Status Konstanten */
		const STATUS_EINGEGANGEN = 0;
		const STATUS_AUFTRAGAKZEPTIERT = 1;
		const STATUS_UNVOLLSTAENDIG = 2;
		const STATUS_ZAHLUNGAKZEPTIERT = 100;
		const STATUS_RECHNUNGGESCHRIEBEN = 110;
		const STATUS_ZAHLUNGFEHLGESCHLAGEN = 200;
		const STATUS_WAREVERSENDET = 250;
		const STATUS_ZUGESTELLT = 300;
		const STATUS_ZURUECKGEZAHLT = 400;
		const STATUS_STORNIERT = 500;
		const STATUS_AKTIVABO = 600;
		const STATUS_OFFENEABOKUENDIGUNG = 610;
		const STATUS_GEKUENDIGTEABOS = 620;

		/** Seitenkonstanten */
		const PAGE_BASKET = 1;

		/**
		 * Constructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->arModule = array();
			$this->arShipping = array();
			$this->arPayment = array();

			$this->cache = new wpsg_cache($this->db);

			$this->basketController = new wpsg_BasketController();
 
			$wpsg_update_data = $this->get_option('wpsg_updatedata', true);
			if (wpsg_isSizedArray($wpsg_update_data) && @$wpsg_update_data['returnCode'] != '0') $this->bLicence = true;

			$this->arStatus = array(
				self::STATUS_EINGEGANGEN => __('Eingegangen', 'wpsg'),
				self::STATUS_AUFTRAGAKZEPTIERT => __('Auftrag akzeptiert', 'wpsg'),
				self::STATUS_ZAHLUNGAKZEPTIERT => __('Zahlung akzeptiert', 'wpsg'),
				self::STATUS_RECHNUNGGESCHRIEBEN => __('Rechnung geschrieben', 'wpsg'),
				self::STATUS_ZAHLUNGFEHLGESCHLAGEN => __('Zahlung fehlgeschlagen', 'wpsg'),
				self::STATUS_WAREVERSENDET => __('Ware versendet', 'wpsg'),
				self::STATUS_ZUGESTELLT => __("zugestellt", "wpsg"),
				self::STATUS_ZURUECKGEZAHLT => __("zurückgezahlt", "wpsg"),
				self::STATUS_STORNIERT => __("storniert", "wpsg"),
				self::STATUS_UNVOLLSTAENDIG => __('Unvollständig', 'wpsg'),
				self::STATUS_AKTIVABO => __('aktive Abos', 'wpsg'),
				self::STATUS_OFFENEABOKUENDIGUNG => __('ausstehende Abokündigungen', 'wpsg'),
				self::STATUS_GEKUENDIGTEABOS => __('gekündigte Abos', 'wpsg'),
			);
			
			$GLOBALS['wpsg_sc'] = $this;

		} // public function __construct()

		/**
		 * Filterung des Seitentitels
		 */
		public function the_title($title, $id = null)
		{

			if (get_the_ID() == $this->getPagePID(wpsg_ShopController::PAGE_BASKET))
			{

				// Bei der Produktanzeige über den Warenkorb soll kein Titel angezeigt werden
				if (wpsg_isSizedInt($id, $this->get_option('wpsg_page_basket')) && wpsg_isSizedString($_REQUEST['wpsg_action'], 'showProdukt') && doing_filter('the_content')) return '';

			}

			// Seitentitel auf Produktseiten nicht anzeigen
			if ($this->hasMod('wpsg_mod_produktartikel') && get_post_type() === $this->get_option('wpsg_mod_produktartikel_pathkey')) $this->titleDisplayed = true;

			return $title;

		}

		public function wpsg_daily_hook() {
		    
		    $wpsg_customerdatadelete = $this->get_option('wpsg_customerdatadelete');
		   	$wpsg_customerdatedelete_who = $this->get_option('wpsg_customerdatedelete_who'); 
		    
		    if (wpsg_isSizedInt($wpsg_customerdatadelete)) {
			
				$strQueryWHERE = "";
		    	
		        switch ($this->get_option('wpsg_customerdatadelete_unit')) {
		            
                    case '0': // Tage
                        
                        $strQueryHAVING = " GREATEST(`last_login`, `last_order_date`) < DATE_SUB(NOW(), INTERVAL ".wpsg_q($wpsg_customerdatadelete)." DAY) "; break;
                        
                    case '1': // Monate

                        $strQueryHAVING = " GREATEST(`last_login`, `last_order_date`) < DATE_SUB(NOW(), INTERVAL ".wpsg_q($wpsg_customerdatadelete)." MONTH) "; break;
                        
                    case '2': // Jahre

                        $strQueryHAVING = " GREATEST(`last_login`, `last_order_date`) < DATE_SUB(NOW(), INTERVAL ".wpsg_q($wpsg_customerdatadelete)." YEAR) "; break;
                        
                    default: throw new \Exception(__('Ungültige Einheit für das automatische Anonymisieren der Kundendaten.', 'wpsg'));
		            
                }
                
                if ($wpsg_customerdatedelete_who === '1') { // Nur Gäste
	
					$strQueryWHERE = " AND K.`passwort_saltmd5` = '' ";
		        	
				}
		        
                $strQuery = "
                    SELECT
                        K.`id`, K.`last_login`,
                        (SELECT O.`cdate` FROM `".WPSG_TBL_ORDER."` AS O WHERE O.`k_id` = K.`id` ORDER BY O.`cdate` DESC LIMIT 1) AS `last_order_date`
                    FROM
                        `".WPSG_TBL_KU."` AS K
                    WHERE
                        K.`status` != -1
                        ".$strQueryWHERE."		            
                    HAVING
                        ".$strQueryHAVING."  
                ";
                 
                $arCustomerID = $this->db->fetchAssoc($strQuery);
                               
                foreach ($arCustomerID as $row_c) {
                    
                    $oCustomer = wpsg_customer::getInstance($row_c['id']);
                    $oCustomer->anonymize();
                    
                }
                
            }
		    
		    die("-");
		    
        } // public function wpsg_daily_hook()
		
		/**
		 * Wird bei der erstmaligen Installation des Plugins aufgerufen
		 */
		public function firstInstall()
		{
			
			global $wpdb, $current_user;

			$user_id = 0;

			if (function_exists('wp_get_current_user')) {
				
				$current_user = wp_get_current_user();
				$user_id = $current_user->user_ID;
				
			} else if (function_exists("get_currentuserinfo"))
			{
				get_currentuserinfo();
				$user_id = $current_user->user_ID;
			}

			if ($user_id == 0 && function_exists("get_current_user_id"))
			{
				$user_id = get_current_user_id();
			}

			// Artikel zum Produkt anlegen
			$title = __('wpShopGermany DemoProdukt WordPress Artikel', 'wpsg');

			$partikel = $this->db->ImportQuery($wpdb->prefix."posts", array(
				"post_author" => $user_id,
				"post_date" => "NOW()",
				"post_title" => wpsg_q($title),
				"post_date_gmt" => "NOW()",
				"post_name" => wpsg_q(strtolower($title)),
				"post_status" => "publish",
				"comment_status" => "closed",
				"ping_status" => "neue-seite",
				"post_type" => "post",
				"post_content" => '',
				"ping_status" => "closed",
				"comment_status" => "closed"
			));

			$product_id = $this->db->ImportQuery(WPSG_TBL_PRODUCTS, array(
				'cdate' => 'NOW()',
				'partikel' => $partikel,
				'ptemplate_file' => 'standard.phtml',
				'name' => __('wpShopGermany DemoProdukt', 'wpsg'),
				'preis' => '119',
				'mwst_key' => 'c',
				'beschreibung' => __('Dies ist der Produkttext…', 'wpsg')
			));

			$this->db->UpdateQuery($wpdb->prefix."posts", array(
				"post_name" => wpsg_q($this->clear($title, $partikel)),
				"post_content" => wpsg_q('[wpshopgermany product="'.$product_id.'"]')
			), "`ID` = '".wpsg_q($partikel)."'");

			$title = __('wpShopGermany DemoProdukt WordPress Seite', 'wpsg');

			$page = $this->db->ImportQuery($wpdb->prefix."posts", array(
				"post_author" => $user_id,
				"post_date" => "NOW()",
				"post_title" => wpsg_q($title),
				"post_date_gmt" => "NOW()",
				"post_name" => wpsg_q(strtolower($title)),
				"post_status" => "publish",
				"comment_status" => "closed",
				"ping_status" => "neue-seite",
				"post_type" => "page",
				"post_content" => '',
				"ping_status" => "closed",
				"comment_status" => "closed"
			));

			$this->db->UpdateQuery($wpdb->prefix."posts", array(
				"post_name" => wpsg_q($this->clear($title, $page)),
				"post_content" => wpsg_q('[wpshopgermany product="'.$product_id.'"]')
			), "`ID` = '".wpsg_q($page)."'");

			if ($this->hasMod('wpsg_mod_request') == '1') {
				if ($this->get_option('wpsg_page_request') === false) $this->createPage(__('Anfrageliste', 'wpsg'), 'wpsg_page_request', '-1');
			}
			
			if ($this->get_option('wpsg_multiblog_standalone', true) === false) $this->update_option('wpsg_multiblog_standalone', '1', true);
			if ($this->get_option('wpsg_customer_start', true) === false) $this->update_option('wpsg_customer_start', '1', true);
			if ($this->get_option('wpsg_order_start', true) === false) $this->update_option('wpsg_order_start', '1', true);			
			
			if ($this->get_option('wpsg_page_basket') === false) $this->createPage(__('Warenkorb', 'wpsg'), 'wpsg_page_basket', '-1');
			if ($this->get_option('wpsg_currency') === false) $this->update_option('wpsg_currency', 'EUR');
			if ($this->get_option('wpsg_produkte_perpage') === false) $this->update_option('wpsg_produkte_perpage', '25');
			if ($this->get_option('wpsg_order_perpage') === false) $this->update_option('wpsg_order_perpage', '25');
			if ($this->get_option('wpsg_page_basket_more') === false) $this->createPage(__('Weiter shoppen', 'wpsg'), 'wpsg_page_basket_more', '-1');			
			if ($this->get_option('wpsg_page_versand') === false) $this->createPage(__('Versandkosten', 'wpsg'), 'wpsg_page_versand', '-1');
			if ($this->get_option('wpsg_page_agb') === false) $this->createPage(__('AGB', 'wpsg'), 'wpsg_page_agb', '-1');
			if ($this->get_option('wpsg_page_datenschutz') === false) $this->createPage(__('Datenschutz', 'wpsg'), 'wpsg_page_datenschutz', '-1');
			if ($this->get_option('wpsg_page_widerrufsbelehrung') === false) $this->createPage(__('Widerrufsbelehrung', 'wpsg'), 'wpsg_page_widerrufsbelehrung', '-1');
			if ($this->get_option('wpsg_page_impressum') === false) $this->createPage(__('Impressum', 'wpsg'), 'wpsg_page_impressum', '-1');
            if ($this->get_option('wpsg_page_product') === false) $this->createPage(__('Produktdetails', 'wpsg'), 'wpsg_page_product', '-1');
			//if ($this->get_option('wpsg_page_onlinedisputeresolution') === false) $this->createPage(__('Online Streitbeilegung', 'wpsg'), 'wpsg_page_onlinedisputeresolution', '-1');
			
			// Versandarten und Vorkasse aktivieren
			$this->update_option('wpsg_mod_versandarten', time());
			$this->update_option('wpsg_mod_prepayment', time());
			
			// Bestellbedingungen aktivieren
			$this->update_option('wpsg_mod_ordercondition', time());

			$this->loadModule(true);

			$this->arModule['wpsg_mod_versandarten']->install();
			$this->arModule['wpsg_mod_prepayment']->install();
			
			// Versandzone anlegen
			if ($this->hasMod('wpsg_mod_versandarten'))
			{

				$nVersandzonen = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_VZ."`");

				if ($nVersandzonen == 0)
				{

					$vz_default_name = "Inland";

					$vz_id = $this->db->ImportQuery(WPSG_TBL_VZ, array(
						"name" => wpsg_q($vz_default_name)
					));
					
					$this->addTranslationString('vz_'.$vz_id, $vz_default_name);

					// Lieferanten anlegen
					$nVa = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_VA."`");

					if ($nVa == 0)
					{

						$va_default_name = "Post";

						$va_id = $this->db->ImportQuery(WPSG_TBL_VA, array(
							"name" => wpsg_q($va_default_name),
							"vz" => "0",
							"aktiv" => "1",
							'mwst_key' => 'c'
						));

						$this->addTranslationString('wpsg_mod_versandarten_'.$va_id, $va_default_name);

					}

					// Länder anlegen
					$nLander = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_LAND."`");

					if ($nLander == 0)
					{

						$land_default_name = "Deutschland";
						$land_default_krzl = "DE";

						$land_id = $this->db->ImportQuery(WPSG_TBL_LAND, array(
							"name" => wpsg_q($land_default_name),
							"kuerzel" => wpsg_q($land_default_krzl),
							"vz" => '1',
							"mwst" => 0,
							"mwst_a" => 'NULL',
							"mwst_b" => '7',
							"mwst_c" => '19',
							"mwst_d" => 'NULL'
						));

						$this->addTranslationString('land_'.$land_id, $land_default_name);
						$this->addTranslationString('landkrzl_'.$land_id, $land_default_krzl);

						$this->update_option('wpsg_defaultland', $land_id);

					}

				}

			}

			if ($this->get_option('wpsg_kleinunternehmer_text') == '')
				$this->update_option('wpsg_kleinunternehmer_text', __('Aufgrund der Kleinunternehmerregelung gemäß § 19 UStG wird keine Umsatzsteuer erhoben oder ausgewiesen.', 'wpsg'));

			if ($this->hasMod('wpsg_mod_ordercondition')) $this->arModule['wpsg_mod_ordercondition']->install();

			// Widerrufsformular generieren
			if (!file_exists(WPSG_PATH_UPLOADS.'wpsg_revocation/')) mkdir(WPSG_PATH_UPLOADS.'wpsg_revocation/', 0775, true);
			$this->view['filename'] = WPSG_PATH_UPLOADS.'wpsg_revocation/widerrufsformular.pdf';
			$this->render(WPSG_PATH_VIEW.'/admin/musterwiderruf.pdf.phtml');
			$this->update_option('wpsg_revocationform', 'widerrufsformular.pdf');

			$this->update_option('wpsg_installed', time(), true);

		} // public function firstInstall()

		/**
		 * Installiert den Shop
		 */
		public function install()
		{
 
			$this->callMods('install');

			// Kundenfelder vorkonfigurieren
			$this->checkDefault('wpsg_admin_pflicht', @unserialize('a:15:{s:6:"anrede";s:1:"1";s:14:"anrede_auswahl";s:9:"Herr|Frau";s:5:"firma";s:1:"1";s:5:"vname";s:1:"0";s:4:"name";s:1:"0";s:3:"geb";s:1:"1";s:5:"email";s:1:"0";s:12:"emailconfirm";s:1:"1";s:3:"tel";s:1:"1";s:3:"fax";s:1:"1";s:7:"strasse";s:1:"0";s:3:"plz";s:1:"0";s:3:"ort";s:1:"0";s:4:"land";s:1:"0";s:7:"ustidnr";s:1:"1";}'));

			$this->checkDefault('wpsg_afterinsert', '1');
			$this->checkDefault('wpsg_salt', md5($_SERVER['REQUEST_URI'].rand(1, 1000)));

			// Betreffs der E-Mails vordefinieren
			$this->checkDefault('wpsg_global_betreff', 'Allgemeiner Betreff', false, true);
			$this->checkDefault('wpsg_global_absender', 'Shop XYZ <shop@'.$_SERVER['HTTP_HOST'].'>');

			$this->checkDefault('wpsg_adminmail_betreff', 'Eingang einer neuen Bestellung', false, true);
			$this->checkDefault('wpsg_adminmail_absender', 'Shop XYZ <shop@'.$_SERVER['HTTP_HOST'].'>');
			$this->checkDefault('wpsg_adminmail_empfaenger', 'bestellungen@'.$_SERVER['HTTP_HOST']);

			$this->checkDefault('wpsg_kundenmail_betreff', 'Bestellbestätigung', false, true);
			$this->checkDefault('wpsg_kundenmail_absender', 'Shop XYZ <shop@'.$_SERVER['HTTP_HOST'].'>');

			$this->checkDefault('wpsg_status_betreff', 'Statusänderung Ihrer Bestellung', false, true);
			$this->checkDefault('wpsg_status_absender', 'Shop XYZ <shop@'.$_SERVER['HTTP_HOST'].'>');

			$this->checkDefault('wpsg_path_upload_multiblog', 'uploads/sites/%blog_id%/', true);

			$this->checkDefault('wpsg_preisangaben', WPSG_BRUTTO);
			$this->checkDefault('wpsg_preisangaben_frontend', WPSG_BRUTTO);
			
		} // public function install()

		/**
		 * Überprüft ob der Kunde $customer_id das Produkt $product_id schon gekauft hat
		 * Wenn $customer_id = false ist, wird nach dem eingeloggten Benutzer geschaut.
		 * Ist kein Benutzer eingeloggt wird false zurückgegeben
		 *
		 * Keine Berücksichtigung auf Bestellstatus
		 *
		 * @author Daschmi (07.05.2014)
		 *
		 * @param int $product_id
		 * @param int $customer_id
		 *
		 * @return boolean
		 */
		public function hasOrder($product_id, $customer_id = false)
		{

			if ($customer_id === false)
			{

				if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['id']))
				{

					$customer_id = $_SESSION['wpsg']['checkout']['id'];

				}
				else
				{

					return false;

				}

			}

			$nCountOrder = $this->db->fetchOne("
				SELECT
					COUNT(*)
				FROM
					`".WPSG_TBL_ORDERPRODUCT."` AS OP
						LEFT JOIN `".WPSG_TBL_ORDER."` AS O ON (O.`id` = OP.`o_id`)
						LEFT JOIN `".WPSG_TBL_KU."` AS K ON (K.`id` = O.`k_id`)
				WHERE
					OP.`p_id` = '".wpsg_q($product_id)."' AND
					K.`id` = '".wpsg_q($customer_id)."'
			");

			if ($nCountOrder > 0) return true;
			else return false;

		} // public function hasOrder($product_id, $customer_id = false)

		/**
		 * Gibt eine URL zu einer Shop Seite zurück
		 */
		public function getURL($url_const, $mod = false, $action = false, $arParam = array(), $ForceHttps = false)
		{

			switch ($url_const)
			{

				case self::URL_VERSANDKOSTEN:

					$url = get_permalink($this->get_option('wpsg_page_versand'));
					break;

				case self::URL_AGB:

					$url = get_permalink($this->get_option('wpsg_page_agb'));
					break;

				case self::URL_DATENSCHUTZ:

					$url = get_permalink($this->get_option('wpsg_page_datenschutz'));
					break;

				case self::URL_BASKET:

					$url = get_permalink($this->get_option('wpsg_page_basket'));
					break;
					
				case self::URL_BASKET_MORE:
				    
				    $url = get_permalink($this->get_option('wpsg_page_basket_more'));
				    break;

				case self::URL_PRODUCTDETAIL:

					$url = get_permalink($this->getPagePID(self::URL_PRODUCTDETAIL));

					break;

				case self::URL_BASKET_AJAX:

					$url = get_permalink($this->get_option('wpsg_page_basket'));
					$url .= ((strpos($url, '?') === false)?'?wpsg_basket_ajax=1':'&wpsg_basket_ajax=1');
					break;

				case self::URL_WIDGET_AJAX:

					$url = get_permalink($this->get_option('wpsg_page_basket'));
					$url .= ((strpos($url, '?') === false)?'?wpsg_widget_ajax=1':'&wpsg_widget_ajax=1');
					break;

				case self::URL_CHECKOUT:

					if ($this->hasMod('wpsg_mod_onepagecheckout'))
					{

						$url = get_permalink($this->get_option('wpsg_mod_onepagecheckout_page'));
						break;

					}

					$url = $this->getURL(wpsg_ShopController::URL_BASKET);
					$url .= ((strpos($url, '?') === false)?'?wpsg_checkout':'&amp;wpsg_checkout');
					break;

				case self::URL_CHECKOUT2:

					if ($this->hasMod('wpsg_mod_onepagecheckout')) return $this->getURL(wpsg_ShopController::URL_CHECKOUT);

					$url = $this->getURL(wpsg_ShopController::URL_BASKET);
					$url .= ((strpos($url, '?') === false)?'?wpsg_checkout2':'&amp;wpsg_checkout2');
					break;

				case self::URL_OVERVIEW:

					// Wenn Crefopay aktiv ist, gibt es keine Bestellzusammenfassung, da diese auf CrefoPay Seite angezeigt wird
					if ($this->hasMod('wpsg_mod_crefopay'))
					{

						$url = $this->callMod('wpsg_mod_crefopay', 'getDoOrderUrl');

					}
					else
					{

						$url = $this->getURL(wpsg_ShopController::URL_BASKET);
						$url .= ((strpos($url, '?') === false)?'?wpsg_overview':'&amp;wpsg_overview');

					}

					break;

				case self::URL_WIDERRUF:

					$url = get_permalink($this->get_option('wpsg_page_widerrufsbelehrung'));
					break;

				case self::URL_REQUEST:

					$url = get_permalink ($this->get_option('wpsg_page_request'));
					break;

				case self::URL_ONLINE_DISPUTE_RESOLUTION:

					$url = $this->get_option('wpsg_page_onlinedisputeresolution');
					break;

				case self::URL_IMPRESSUM:

					$url = get_permalink($this->get_option('wpsg_page_impressum'));
					break;

				case self::URL_PROFIL:

					$url = get_permalink($this->get_option('wpsg_page_mod_kundenverwaltung_profil'));
					break;
					
				case self::URL_ABO:
					
					$url = get_permalink($this->get_option('wpsg_page_mod_kundenverwaltung_abo'));
					break;

				case self::URL_ORDER:

					$url = get_permalink($this->get_option('wpsg_page_mod_kundenverwaltung_order'));
					break;

				case self::URL_LOGOUT:

					$url = get_permalink($this->get_option('wpsg_page_mod_kundenverwaltung_profil'));
					$url .= ((strpos($url, '?') === false)?'?wpsg_mod_kundenverwaltung_logout':'&amp;wpsg_mod_kundenverwaltung_logout').'&amp;wpsg_referer='.$this->getCurrentURL();
					break;

				case self::URL_LOSTPWD:

					$url = $this->callMod('wpsg_mod_kundenverwaltung', 'getPwdVergessenURL');
					break;

			}

			if (wpsg_isSizedString($mod)) $url .= ((strpos($url, '?') === false)?'?':'&').'wpsg_mod='.$mod;
			if (wpsg_isSizedString($action)) $url .= ((strpos($url, '?') === false)?'?':'&').'wpsg_action='.$action;
			if (wpsg_isSizedArray($arParam)) $url .= ((strpos($url, '?') === false)?'?':'&').http_build_query($arParam);
			if ($ForceHttps === true) $url = preg_replace('/^http:/', 'https:', $url);

			return $url;

		} // public function getURL($url_const, $entity = false)

        /**
         * Gibt den Pfad zurück, in dem der Shop seine Dateien ablegt
         * Der Ordner wird für direkte Zugriffe über .htaccess gesperrt
         */
        public function getUplodatStoragePath() {

            if ($this->isMultiBlog()) {
 
                $path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/';

            } else {

                $path = WP_CONTENT_DIR.'/uploads/wpsg/';
                
            }
            
            $this->protectDirectory($path);

            return $path;
            
        } // public function getUplodatStoragePath()
        
		/**
		 * Checkt den Request und entfernt möglicherweise escapte Variablen
		 */
		public function checkEscape()
		{

			if (strlen($_GET['wpsg_quotecheck']) > 7 || get_magic_quotes_gpc()) {

				$_POST      = array_map('stripslashes_deep', $_POST);
			    $_GET       = array_map('stripslashes_deep', $_GET);
			    $_COOKIE    = array_map('stripslashes_deep', $_COOKIE);
			    $_REQUEST   = array_map('stripslashes_deep', $_REQUEST);

			    // Damit es bei mehrfachen Aufruf durch Module nicht erneut gemacht wird
			    $_GET['wpsg_quotecheck'] = '\"CHECK';

			}

		} // public function checkEscape()

		/**
		 * True, wenn im System Frontend angezeigt werden dürfen
		 * Bestimmt auch, ob bestellt werden darf
		 * 
		 * @return bool
		 */
		public function canDisplayPrice() {

			// Wenn Kundenverwaltung inaktiv, gibt es auch die Einstellung nicht => Preis anzeigen
			if (!$this->hasMod('wpsg_mod_kundenverwaltung')) return true;
			
			$wpsg_mod_kundenverwaltung_preisAnzeige = $this->get_option('wpsg_mod_kundenverwaltung_preisAnzeige');

			if (wpsg_isSizedInt($wpsg_mod_kundenverwaltung_preisAnzeige)) {

				// Preis und Kauf nur für angemeldete Benutzer
				$isLoggedIn = $this->callMod('wpsg_mod_kundenverwaltung', 'isLoggedIn');

				if (wpsg_isSizedInt($isLoggedIn))
				{

					return true;

				}
				else
				{

					return false;

				}

			} else return true;

		} // public function canDisplayPrice()

        /**
         * Wordpress "init" Hook
         */
        public function init() {

            $locale = apply_filters('plugin_locale', get_locale(), 'wpsg');
            
            if ($loaded = load_textdomain('wpsg', trailingslashit(WP_LANG_DIR).'wpsg/wpsg-'.$locale .'.mo')) {
                
                return $loaded;
                
            } else {
                
                load_plugin_textdomain('wpsg', false, WPSG_FOLDERNAME.'/lang/');
                
            }

        } // public function init()
        
		public function getShopLocations() {
        	
        	$arReturn = [
        		1 => [
					'label' => __('Hauptsitz', 'wpsg'),
					'street' => $this->get_option('wpsg_shopdata_street'),
					'zip' => $this->get_option('wpsg_shopdata_zip'),
					'city' => $this->get_option('wpsg_shopdata_city'),
					'country' => $this->getDefaultCountry()->getName(),
					'tel' => $this->get_option('wpsg_shopdata_tel'),
					'fax' => $this->get_option('wpsg_shopdata_fax'),
					'email' => $this->get_option('wpsg_shopdata_email')
				],
			];
        	
        	if ($this->get_option('wpsg_shopdata_2') === '1') { 
        		
        		$arReturn[2] = [
        			'label' => __('Zweigstelle', 'wpsg'),
        			'street' => $this->get_option('wpsg_shopdata_2_street'),
					'zip' => $this->get_option('wpsg_shopdata_2_zip'),
					'city' => $this->get_option('wpsg_shopdata_2_city'),
					'country' => $this->get_option('wpsg_shopdata_2_country'),
					'tel' => $this->get_option('wpsg_shopdata_2_tel'),
					'fax' => $this->get_option('wpsg_shopdata_2_fax'),
					'email' => $this->get_option('wpsg_shopdata_2_email')
				];
					
			}
			
			return $arReturn;
        	
		}
		
		/**
		 * Initiiert den Shop
		 * Ich hab das nicht in den Konstruktor gepackt, damit einige Funktionen aus der initShop Methode schon auf das $GLOBALS zugreifen kÔøΩnnen
		 */
		public function initShop($prefix)
		{
		    
			if (is_admin() && defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN == true)
			{

				// Backend läuft mit SSL
                

			}

			if (preg_match('/update-core\.php/', $_SERVER['PHP_SELF']))
			{

				$this->update_option('wpsg_lastupdate', false);

			}

			if (wpsg_isSizedInt($this->get_option('wpsg_autolineending'))) @ini_set('auto_detect_line_endings', true);

			$this->prefix = $prefix;
			$this->basket = new wpsg_basket();

			if ($this->get_option('wpsg_installed', true) > 0)
			{

				$this->loadModule();

				// Hier müssen die Änderungen der Session gespeichert werden
				if (wpsg_isSizedArray($_REQUEST['wpsg']['checkout']))
				{

					$_SESSION['wpsg']['checkout'] = wpsg_xss(
						wpsg_array_merge(
							(array)$_SESSION['wpsg']['checkout'],
							wpsg_trim($_REQUEST['wpsg']['checkout'], false)
						)
					);

				}

                if (isset($_REQUEST['wpsg_scss'])) {

                    // Generierung der CSS Dateien
                    $arFiles = [
                        'css/style.scss'
                    ];

                    $this->callMods('wpsg_scss', [&$arFiles]);
                     //die(wpsg_debug($arFiles));
                    $md5 = '';

                    foreach ($arFiles as $k => $f) {

                        $arFiles[$k] = $this->getRessourcePath($f);

                        $md5 .= md5_file($arFiles[$k]);

                    }

                    $css = '';
                    $scss_cache = $this->get_option('wpsg_scss_cache');
                    $scss_cache_file = WPSG_PATH_UPLOADS.'scss_cache.css';

                    header('Content-type: text/css');

                    if ($scss_cache === false || $scss_cache !== $md5 || !file_exists($scss_cache_file)) {

                        require_once WPSG_PATH_LIB.'vendor/autoload.php';

                        $scss = new Leafo\ScssPhp\Compiler();

                        foreach ($arFiles as $f) {

                            try {

                                $scss->setImportPaths(dirname($f));
                                $css .= $scss->compile(file_get_contents($f));

                            } catch (\Exception $e) {

                                echo $e->getMessage();
                                echo "\r\n\r\n";
                                echo $e->getTraceAsString ();

                                exit;
                                
                            }

                        }

                        $minifier = new MatthiasMullie\Minify\CSS();
                        $minifier->add($css);

                        $css = $minifier->minify();

                        file_put_contents($scss_cache_file, $css);

                        $this->update_option('wpsg_scss_cache', $md5);

                        echo $css;

                    } else {

                        readfile($scss_cache_file);

                    }

                    exit;

                }
				
			}

		} // public function initShop()

		public function wp_enqueue()
		{

			wp_enqueue_script('jquery');

			if (is_admin() && preg_match('/wpsg/', wpsg_getStr($_REQUEST['page'])))
			{

				wp_enqueue_script('wpsg_bsjs', $this->getRessourceURL('js/bootstrap-3.3.6-dist/js/bootstrap.min.js'));
                wp_enqueue_script('wpsg_vuejs', $this->getRessourceURL('js/vue.min.js'));

				wp_enqueue_script('common');

				wp_enqueue_script('wpsg_adminjs', $this->getRessourceURL('js/admin.js'));
				wp_enqueue_script('wpsg_cookiejs', $this->getRessourceURL('js/jquery.cookie.js'));
				wp_enqueue_script('wpsg_bs_editable', $this->getRessourceURL('js/bootstrap3-editable-1.5.1/bootstrap3-editable/js/bootstrap-editable.js'));
				wp_enqueue_script('wpsg_editable', $this->getRessourceURL('js/editable.js'));

				wp_enqueue_script('utils');
				wp_enqueue_script('jquery-color');

				wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-autocomplete');
				wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('jquery-ui-sortable');
				wp_enqueue_script('jquery-ui-datepicker'); //, $this->url(WPSG_URL_CONTENT.'plugins/'.WPSG_FOLDERNAME.'/lib/ui/jquery.ui.datepicker.js'), array('jquery', 'jquery-ui-core'));
				wp_enqueue_script('jquery-ui-datepicker-de', $this->url(WPSG_URL_CONTENT.'plugins/'.WPSG_FOLDERNAME.'/lib/ui/jquery.ui.datepicker-de.js'), array('jquery', 'jquery-ui-core'));

				wp_enqueue_style('wpsg-bscss', $GLOBALS['wpsg_sc']->getRessourceURL('js/bootstrap-3.3.6-dist/css/bootstrap.css'));
				wp_enqueue_style('wpsg-bs-theme-css', $GLOBALS['wpsg_sc']->getRessourceURL('js/bootstrap-3.3.6-dist/css/bootstrap-theme.css'));
				wp_enqueue_style('wpsg_bs_editable', $GLOBALS['wpsg_sc']->getRessourceURL('js/bootstrap3-editable-1.5.1/bootstrap3-editable/css/bootstrap-editable.css'));

				wp_enqueue_style('wp-jquery-ui-dialog');
				wp_enqueue_style('jquery-ui-datepicker');
				wp_enqueue_style('wpsg-adminstyle', $GLOBALS['wpsg_sc']->getRessourceURL('css/admin.css'), array('wpsg-bscss', 'wpsg-bs-theme-css'));

				wp_localize_script('wpsg_adminjs', 'wpsg_ajax', array(
					'img_ajaxloading' => $this->getRessourceURL('gfx/ajax-loader.gif'),
					'label_pleasewait' => __('Bitte warten', 'wpsg'),
					'ie_placeholder' => __('Ihr Text', 'wpsg'),
					'ie_emptytext' => __('keine Angaben.', 'wpsg'),
					'ie_validate_empty' => __('Bitte machen Sie hier eine Angabe.', 'wpsg')
				));
				
				//wp_enqueue_script('wpsg_ajaxupload', $this->getRessourceURL('js/ajaxupload.js')); ??
				wp_enqueue_media();

				// Font AweSome
				wp_enqueue_style('wpsg_fa', $GLOBALS['wpsg_sc']->getRessourceURL('js/font-awesome-4.5.0/css/font-awesome.min.css'));
				
				
			}
			else if (!is_admin())
			{

				if ($this->get_option('wpsg_load_jquery') == '1') wp_enqueue_script('jquery');
				
				//wp_enqueue_style('wpsg-bscss', $GLOBALS['wpsg_sc']->getRessourceURL('js/bootstrap-3.3.6-dist/css/bootstrap.css'));
				if ($this->get_option('wpsg_load_bootstrap_glyphfont_css') == '1') wp_enqueue_style('wpsg-bscss', $GLOBALS['wpsg_sc']->getRessourceURL('js/bootstrap-3.3.6-dist/css/bootstrap-glyphfont.css'));

				if ($this->get_option('wpsg_load_css') != '1') wp_enqueue_style('wpsg-frontendstyle', $this->getRessourceURL('css/frontend.css'));
                if ($this->get_option('wpsg_load_css') != '1') wp_enqueue_style('wpsg-frontendstyle', '/?wpsg_scss');
				
				if ($this->get_option('wpsg_load_thickbox_js') == '1') wp_enqueue_script('thickbox', null, array('jquery'));
				if ($this->get_option('wpsg_load_thickbox_css') == '1') 
				{
					
					wp_enqueue_style('dashicons');
					wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
					
				}
				if ($this->get_option('wpsg_load_jquery') == '1') wp_enqueue_script('jquery');

				wp_enqueue_script('wpsg_frontend', $this->getRessourceURL('js/frontend.js'), array('jquery'));

				wp_localize_script('wpsg_frontend', 'wpsg_ajax', array(
					'ajaxurl' => $this->getURL(wpsg_ShopController::URL_BASKET),
					'pageurl' => $this->getCurrentURL(),
					'wpsg_auf' => __('Klicken zum Aufklappen', 'wpsg'),
					'wpsg_zu' => __('Klicken zum Einklappen', 'wpsg'),
					'url_basket' => $this->getURL(wpsg_ShopController::URL_BASKET),
					'img_ajaxloading' => $this->getRessourceURL('gfx/ajax-loader.gif'),
					'label_pleasewait' => __('Bitte warten', 'wpsg')
				));

				// Validierung
				if ($this->get_option('wpsg_load_validierung_js') == '1')
				{

					switch ($this->get_option('wpsg_form_validation'))
					{

						case '1': // Validierung V1

							wp_enqueue_script('jquery-validation-de', $this->getRessourceURL('js/jquery.validationEngine-de.js'), array('jquery'));
							wp_enqueue_script('jquery-validation', $this->getRessourceURL('js/jquery.validationEngine.js'), array('jquery'));

							wp_localize_script('jquery-validation-de', 'wpsg_trans_v1', array(
								'required_alertText' => __('* Dieses Feld ist ein Pflichtfeld', 'wpsg'),
								'required_alertTextCheckboxMultiple' => __('* Bitte wählen Sie eine Option', 'wpsg'),
								'required_alertTextCheckboxe' => __('* Dieses Feld ist ein Pflichtfeld', 'wpsg'),
								'minSize_alertText' => __('* Mindestens ', 'wpsg'),
								'minSize_alertText2' => __(' Zeichen benötigt', 'wpsg'),
								'maxSize_alertText2' => __('* Maximal', 'wpsg'),
								'maxSize_alertText2' => __(' Zeichen benötigt', 'wpsg'),
								'groupRequired_alertText' => __('* You must fill one of the following fields', 'wpsg'),
								'min_alertText' => __('* Mindeswert ist ', 'wpsg'),
								'max_alertText' => __('* Maximalwert ist ', 'wpsg'),
								'past_alertText' => __('* Datum vor ', 'wpsg'),
								'future_alertText' => __('* Datum nach ', 'wpsg'),
								'maxCheckbox_alertText' => __('* Maximale Anzahl Markierungen überschritten', 'wpsg'),
								'minCheckbox_alertText' => __('* Bitte wählen Sie ', 'wpsg'),
								'minCheckbox_alertText2' => __(' Optionen', 'wpsg'),
								'equals_alertText' => __('* Felder stimmen nicht überein', 'wpsg'),
								'creditCard_alertText' => __('* Ungültige Kreditkartennummer', 'wpsg'),
								'phone_alertText' => __('* Ungültige Telefonnummer', 'wpsg'),
								'email_alertText' => __('* Ungültige E-Mail Adresse', 'wpsg'),
								'integer_alertText' => __('* Keine gültige Ganzzahl', 'wpsg'),
								'number_alertText' => __('* Keine gültige Fließkommazahl', 'wpsg'),
								'date_alertText' => __('* Ungültiges Datumsformat, erwartet wird das Format TT.MM.JJJJ', 'wpsg'),
								'ipv4_alertText' => __('* Ungültige IP Adresse', 'wpsg'),
								'url_alertText' => __('* Ungültige URL', 'wpsg'),
								'onlyLetterSp_alertText' => __('* Nur Buchstaben erlaubt', 'wpsg'),
								'onlyLetterNumber_alertText' => __('* Keine Sonderzeichen erlaubt', 'wpsg'),
								'ajaxUserCall_alertText' => __('* Dieser Benutzer ist bereits vergeben', 'wpsg'),
								'ajaxUserCall_alertTextLoad' => __('* Überprüfe Angaben, bitte warten', 'wpsg'),
								'ajaxNameCall_alertText' => __('* Dieser Name ist bereits vergeben', 'wpsg'),
								'ajaxNameCall_alertTextOk' => __('* Dieser Name ist verfügbar', 'wpsg'),
								'ajaxNameCall_alertTextLoad' => __('* Überprüfe Angaben, bitte warten', 'wpsg'),
								'validate2fields_alertText' => __('* Bitte HELLO eingeben', 'wpsg'),
								'vname_alertText' => __('* Bitte geben Sie einen Vorname an.', 'wpsg')
							));

							break;

						case '2': // Validierung V2

							wp_enqueue_script('wpsg-validation', $this->getRessourceURL('js/jquery-validation-1.11.1/dist/jquery.validate.min.js'), array('jquery'));
							wp_enqueue_script('wpsg-validation-2', $this->getRessourceURL('js/jquery-validation-1.11.1/dist/additional-methods.min.js'), array('jquery'));

							wp_localize_script('wpsg-validation', 'wpsg_trans_v2', array(
								'required' => __('Dieses Feld ist ein Pflichtfeld.', 'wpsg'),
								'remote' => __('Bitte wählen Sie eine Option.', 'wpsg'),
								'email' => __('Ungültige E-Mail Adresse.', 'wpsg'),
								'url' => __('Ungültige URL.', 'wpsg'),
								'date' => __('Format: TT.MM.JJJJ beachten.', 'wpsg'),
								'dateISO' => __('Ungültiges Datumsformat (ISO).', 'wpsg'),
								'number' => __('Keine gültige Ganzzahl.', 'wpsg'),
								'digits' => __('Keine gültige Fließkommazahl.', 'wpsg'),
								'creditcard' => __('Ungültige Kreditkartennummer.', 'wpsg'),
								'equalTo' => __('Felder stimmen nicht überein.', 'wpsg'),
								'maxlength' => __('Maximallänge ist {0}.', 'wpsg'),
								'minlength' => __('Mindestlänge ist {0} characters.', 'wpsg'),
								'rangelength' => __('Bitte geben Sie einen Wert zwischen {0} und {1} Länge ein.', 'wpsg'),
								'range' => __('Bitte geben Sie einen Wert zwischen {0} und {1} ein.', 'wpsg'),
								'max' => __('Maximalwert {0}.', 'wpsg'),
								'min' => __('Minimalwert {0}.', 'wpsg')
							));

							break;

					}

				}

				if ($this->get_option('wpsg_load_validierung_css') == '1')
				{

					switch ($this->get_option('wpsg_form_validation'))
					{

						case '1': // Validierung V1

							wp_enqueue_style('wpsg-validation',$this->getRessourceURL('css/validationEngine.jquery.css'));

							break;

						case '2': // Validierung V2

							// Hat nix

							break;

					}

				}

			}

			$GLOBALS['wpsg_sc']->callMods('wpsg_enqueue_scripts');

		}

		/**
		 * Ruft die Funktion $func_name in den Modulen auf und übergibt ihnen $arParam
		 * Gibt ein Modul -1 zurück wird kein weiteres Modul mehr angefragt
		 */
		public function callMods($func_name, $arParam = array()) {
		               
		    \do_action('wpsg_'.$func_name, $arParam);
		    
			foreach ($this->arModule as $m_key => $m) {

				if (method_exists($m, $func_name)) {
 
					$b = call_user_func_array(array($m, $func_name), $arParam);

					if ($b == -1) break;
					else if ($b == -2) return false;

				}

			}
			
			return true;

		} // public function callMods($func_name)

		/**
		 * Ruft eine spezielle Funktion eines speziellen Moduls auf
		 */
		public function callMod($mod_key, $func_name, $arParam = array()) {
    
			if (!array_key_exists($mod_key, $this->arModule)) return;
			
			if (method_exists($this->arModule[$mod_key], $func_name)) {

				return call_user_func_array(array($this->arModule[$mod_key], $func_name), $arParam);

			}

		} // public function callMod($mod_key, $func_name, $arParam = array())

		/**
		 * Ruft eine URL auf
		 * $typ = 0 Bei Kauf eines Produktes
		 * $typ = 1 Bei Bezahlung eines Produktes
		 * $typ = 2 Bei erstellung einer Rechnung
		 * $typ = 3 Benutzerdefiniert (Daten aus $custom)
		 */
		public function notifyURL($url, $produkt_key, $menge, $order_id, $typ, $custom = false, $customSet = false)
		{

			$arSend = array();

			switch ($typ)
			{
				case '0': $arSend['typ'] = 'buy'; break;
				case '1'; $arSend['typ'] = 'pay'; break;
				case '2'; $arSend['typ'] = 'rechnung'; break;
				case '3'; $arSend = $custom; break;
				default: die(__('Ungültige Anfrage!', 'wpsg'));
			}

			$arSend['time'] = time();

			if (wpsg_isSizedArray($customSet))
			{

				foreach ($customSet as $k => $v)
				{

					$arSend[$k] = $v;

				}

			}

			// Produktdaten an den Array hängen
			if ($produkt_key != false)
			{

				$produkt_data = $this->cache->loadProduct($this->getProduktID($produkt_key));
				foreach ($produkt_data as $k => $v) $arSend['product_'.$k] = $v;

                $arSend['produkt_key'] = $produkt_key;

			}

			if ($menge != false)
			{

				$arSend['menge'] = $menge;

			}

			if ($order_id > 0)
			{

				$order_data = $this->cache->loadOrder($order_id);
				$customer_data = $this->cache->loadKunden($order_data['k_id']);

				foreach ($order_data as $k => $v) $arSend['order_'.$k] = $v;
				foreach ($customer_data as $k => $v) $arSend['customer_'.$k] = $v;

				$this->callMods('notifyURL', array(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend));

				/* Veraltet: */
				$arSend['order'] = $this->db->fetchRow("
					SELECT
						O.*
					FROM
						`".WPSG_TBL_ORDER."` AS O
					WHERE
						O.`id` = '".wpsg_q($order_id)."'
				");

				if ($arSend['order']['k_id'] > 0)
				{

					$arSend['kunde'] = $this->cache->loadKunden($arSend['order']['k_id']);

				}
				/* Veraltet ENDE */

			}

			// Kontrollhash
			$hash = md5($this->get_option('wpsg_salt').$arSend['time']);
			$arSend['hash'] = $hash;

			// CURL Aufruf
			$curl = curl_init($url);
			
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
			curl_setopt($curl, CURLOPT_REFERER, $this->getURL(self::URL_BASKET));
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($arSend, '', '&'));

			if (!ini_get('safe_mode') && !ini_get('open_basedir'))
			{

				/*
				 * TRUE um jedem "Location: "-Header zu folgen, den der Server als Teil der HTTP-Header zurückgibt. Die Verarbeitung erfolgt rekursiv, PHP wird jedem "Location: "-Header folgen, sofern nicht CURLOPT_MAXREDIRS gesetzt ist.
				 * Kann im Safemode nicht geändert werden
				 */
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

			}

			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			$Rec_Data = curl_exec($curl);

			return $Rec_Data;

		} // public function notifyURL($url, $produkt_key, $order_id, $typ)

		/**
		 * Gibt die aktuelle URL der Seite zurück auf der man sich gerade befindet
		 * Wird für das Ziel des Produktformular verwendet und den MyReferer
		 */
		public function getCurrentURL()
		{

			$myReferer = "";

			if ($this->get_option('wpsg_referer_requesturi') == '1')
			{

				if (function_exists('qtrans_convertURL'))
				{

					$myReferer =  qtrans_convertURL($_SERVER["REQUEST_URI"]);

				}
				else
				{

					$myReferer = $_SERVER['REQUEST_URI'];

				}

			}
			else
			{

				if (is_page())
				{

					$myReferer = get_permalink(get_the_id());

				}
				else
				{

					if (wpsg_isSizedString($_SERVER['REDIRECT_URL']))
					{

						$myReferer .= $_SERVER['REDIRECT_URL'];

					}
					else
					{

						$myReferer = "";

						if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']))
						{

							$myReferer = $_SERVER['REQUEST_URI'];

						}
						else if (isset($_SERVER['SCRIPT_URL']) && !empty($_SERVER['SCRIPT_URL']))
						{

							$myReferer = $_SERVER['SCRIPT_URL'];

						}

						if ($_SERVER['QUERY_STRING'] != '' && stripos($_SERVER['QUERY_STRING'], $myReferer) !== false)
						{

							if (strpos($myReferer, '?') > 0)
							{

								$myReferer .= '&'.$_SERVER['QUERY_STRING'];

							}
							else
							{

								$myReferer .= '?'.$_SERVER['QUERY_STRING'];

							}

						}

					}

				}

			}
			
			return $myReferer;

		} // public function getCurrentURL()

        /**
         * Ermittelt einmalig die Daten für den Systemcheck
         * Wird immer im Backend ausgeführt
         *
         * @return array|null
         */
        public function systemcheck() {

            if (is_null($this->arSystemCheck)) {

                $page_admin_url = WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=seiten';

                $arData = Array();

                // Anfrageliste
                if ($this->hasMod('wpsg_mod_request')) {
                    
                	$page_id = $this->get_option('wpsg_page_request');
                	
                	if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                			'general_page_request',
                			self::CHECK_ERROR,
                			wpsg_translate(__('Die Anfrageliste ist nicht korrekt definiert. Der Shop wird nicht korrekt funktionieren. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                	);
                	
                }
                
                // Anhang an Kundenmail
                $old_attachment = $this->get_option('wpsg_kundenmail_attachfile');
                if (wpsg_isSizedString($old_attachment) && file_exists(wpsg_getUploadDir('wpsg_mailconf').$old_attachment)) {
                    
                    $arData[] = [
                        'general_customermail_attachment',
                        self::CHECK_ERROR,
                        wpsg_translate(__('Es gibt noch einen alten Anhang der Kundenmail außerhalb der Mediathek, dieser muss in die Mediathek überführt oder neu angegeben werden. <a href="#1#">Automatisiert übernehmen</a> / <a href="#2#">E-Mail Konfiguration</a>', 'wpsg'),
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=emailconf&migrateAttachmentToMediathek=1&noheader=1',
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=emailconf'
                        )
                    ];
                    
                }
                
                
                // Warenkorbseite
                $page_id = $this->get_option('wpsg_page_basket');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'general_page_basket',
                    self::CHECK_ERROR,
                    wpsg_translate(__('Die Warenkorbseite ist nicht korrekt definiert. Der Shop wird nicht korrekt funktionieren. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );
                
                // Weiter shoppen
                $page_id = $this->get_option('wpsg_page_basket_more');
                
                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'general_page_basket_more',
                    self::CHECK_ERROR,
                    wpsg_translate(__('Die "Weiter shoppen"-Seite ist nicht korrekt definiert. Der Shop wird nicht korrekt funktionieren. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );
                
                // OnePageCheckout
                if ($this->hasMod('wpsg_mod_onepagecheckout')) {
                	
                	$page_id = $this->get_option('wpsg_mod_onepagecheckout_page');
                	$page_mod_onepage = WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_onepagecheckout';
                	
                	if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                			'wpsg_page_onepagecheckout',
                			self::CHECK_ERROR,
                			wpsg_translate(__('Es ist keine Seite für das OnePageCheckout definiert. Der Shop wird nicht korrekt funktionieren. Sie können dies in den <a href="#1#">Moduleinstellungen</a> ändern.', 'wpsg'), $page_mod_onepage)
                	);
                	
                }
                	

                // Produktdetailseite
                $page_id = $this->get_option('wpsg_page_product');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'general_page_productdetail',
                    self::CHECK_ERROR,
                    wpsg_translate(__('Die Produktdetailseite ist nicht korrekt definiert. Dies kann bei bestimmten Konfigurationen zu Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                // Versandkosten
                $page_id = $this->get_option('wpsg_page_versand');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'general_page_shippingt',
                    self::CHECK_NOTICE,
                    wpsg_translate(__('Es ist keine Seite für die detaillierten Versandkosten definiert, dies könnte zu rechtlichen Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                // AGB
                $page_id = $this->get_option('wpsg_page_agb');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'wpsg_page_agb',
                    self::CHECK_NOTICE,
                    wpsg_translate(__('Es ist keine Seite für die AGB definiert, dies könnte zu rechtlichen Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                // Datenschutz
                $page_id = $this->get_option('wpsg_page_datenschutz');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'wpsg_page_agb',
                    self::CHECK_NOTICE,
                    wpsg_translate(__('Es ist keine Seite für die Datenschutzbedingungen definiert, dies könnte zu rechtlichen Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                // Wiederruf
                $page_id = $this->get_option('wpsg_page_widerrufsbelehrung');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'wpsg_page_cancellationterms',
                    self::CHECK_NOTICE,
                    wpsg_translate(__('Es ist keine Seite für die Widerrufsbelehrung definiert, dies könnte zu rechtlichen Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                // Wiederruf
                $page_id = $this->get_option('wpsg_page_impressum');

                if (!wpsg_isSizedInt($page_id) || !get_post_status($page_id)) $arData[] = array(
                    'wpsg_page_imprint',
                    self::CHECK_NOTICE,
                    wpsg_translate(__('Es ist keine Seite für das Impressum definiert, dies könnte zu rechtlichen Problemen führen. Sie können dies in der <a href="#1#">Seitenkonfiguration</a> ändern.', 'wpsg'), $page_admin_url)
                );

                if (!class_exists('SoapClient'))
                {

                    $arData[] = array(
                        'wpsg_noSoap',
                        self::CHECK_NOTICE,
                        __('Ihrem Server fehlt die PHP-Bibliothek für die SOAP-Unterstützung. Informationen finden Sie in unseren <a target="_blank" href="http://wpshopgermany.maennchen1.de/faqs/wie-pruefe-ich-ob-soap-und-curl-auf-meinem-server-aktiviert-sind/">FAQ</a>.', 'wpsg')
                    );

                }

                if (!$this->hasMod('wpsg_mod_ordercondition'))
                {

                    $arData[] = array(
                        'wpsg_noModOrdercondition',
                        self::CHECK_NOTICE,
                        wpsg_translate(
                            __('Ab der wpShopGermany Version 3.4 kann das kostenlose Modul "<a href="#1#">Bestellbedingungen</a>" verwendet werden. Sie können es <a href="#2#">hier</a> konfigurieren. Überprüfen Sie auch die anderen Änderungen, die wir <a href="#3#">in unserem Artikel</a> näher erläutern.', 'wpsg'),
                            'http://wpshopgermany.maennchen1.de/?p=13232',
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_ordercondition',
                            'http://wpshopgermany.maennchen1.de/?p=13952'
                        )
                    );

                }

                // Alte UserViews checken
                if (file_exists(WPSG_PATH_USERVIEW_OLD))
                {

                    $arData[] = array(
                        'wpsg_oldUserViews',
                        self::CHECK_NOTICE,
                        wpsg_translate(
                            __('Es existieren noch alte angepasste Templates unter #1#. Kopieren Sie diese bitte nach #2#. Zukünftig werden die Templateanpassungen nicht mehr im Pluginverzeichnis gesucht. Weitere Informationen erhalten Sie auch <a href="#3#">hier</a> oder auch in unserem kostenlosen <a href="#4#">Forum</a>.', 'wpsg'),
                            WPSG_PATH_USERVIEW_OLD,
                            WPSG_PATH_USERVIEW,
                            'http://wpshopgermany.maennchen1.de/?p=5130',
                            'http://forum.maennchen1.de'
                        )
                    );

                }

                if (!is_object($this->getDefaultCountry()))
                {

                    $arData[] = array(
                        'wpsg_nodefaultcountry',
                        self::CHECK_ERROR,
                        wpsg_translate(
                            __('Es wurde kein Standardland definiert, der Shop kann so nicht korrekt betrieben werden. Bitte überprüfen Sie die <a href="#1#">Länderkonfiguration</a>.', 'wpsg'),
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=laender'
                        )
                    );

                }

                // Schreibrechte auf der Sprachdatei
                if (file_exists(WPSG_PATH_TRANSLATION) && !is_writable(WPSG_PATH_TRANSLATION))
                {

                    $arData[] = array(
                        'wpsg_translationphtml',
                        self::CHECK_NOTICE,
                        wpsg_translate(
                            __('<b>wpShopGermany:</b> Sprachdatei kann nicht geschrieben werden! Prüfen Sie die Schreibrechte auf folgender Datei:<br /><b>#1#</b>', 'wpsg'),
                            WPSG_PATH_TRANSLATION
                        )
                    );

                }

                // Währungscode
                if ($this->get_option('wpsg_currency') === '')
                {

                    $arData[] = array(
                        'wpsg_currency',
                        self::CHECK_ERROR,
                        wpsg_translate(
                            __('Kein Währungscode definiert, dies kann zu Darstellungsproblemen und möglicherweise Abmahnungen führen. Sie können dies <a href="#1#">hier</a> konfigurieren.', 'wpsg'),
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin'
                        )
                    );

                }

                $this->callMods('systemcheck', array(&$arData));

                $arData = array_reverse(wpsg_array_csort($arData, 1));

                $countError = 0;
                foreach ($arData as $d) if ($d[1] === self::CHECK_ERROR) $countError++;

                $this->arSystemCheck = $arData;

                if ($countError > 0)
                {

                    $this->addBackendError(
                        'nohspc_'.wpsg_translate(
                            __('Die Systemprüfung hat #1# Fehler festgestellt, klicken Sie <a href="#2#">hier</a> für Details.', 'wpsg'),
                            $countError,
                            WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=ueber&subaction=systemcheck'
                        ),
                        'wpsg_systemcheck'
                    );

                }

            }

            return $this->arSystemCheck;

        } // public function systemcheck()

		/**
		 * Gibt die Ausgabe für ein Produkt zurück
		 */
		public function renderProdukt($produkt_id, $force_template = false, $force_locale = false)
		{
			
			global $locale, $l10n;

			if ($force_locale !== false && file_exists(dirname(__FILE__).'/../lang/wpsg-'.$force_locale.'.mo'))
			{

				$old_l10n = clone $l10n['wpsg'];
				$this->force_locale = $force_locale;

				// Hier muss load_(text)domain genommen werden, sonst ging es nicht!
				// Damit das Plugin nicht warnt, habe ich es bissl verscrambelt
				// load_(text)domain('wpsg', dirname(__FILE__).'/../lang/wpsg-'.$force_locale.'.mo');
				call_user_func_array(
					'load_textdomain',
					array(
						'wpsg', dirname(__FILE__).'/../lang/wpsg-'.$force_locale.'.mo'
					)
				);

			}

			if (wpsg_isSizedArray($produkt_id))
			{

				$this->view['data'] = $produkt_id;
				$produkt_id = $this->view['data']['id'];

			}
			else
			{

				$this->view['data'] = $this->loadProduktArray($produkt_id);
				if (!wpsg_isSizedArray($this->view['data'])) return '';
				
			}

			if (wpsg_isSizedInt($this->view['data']['disabled'])) return '';

			// Produkt, bei dem die EU Leistungsortregel gilt ?
			if (wpsg_isSizedInt($this->view['data']['euleistungsortregel'])) $this->showEULayer = true;

			if (wpsg_isSizedInt($this->view['data']['deleted'])) return;

			if ($force_template !== false)
			{
				$this->view['data']['ptemplate_file'] = $force_template;
			}

			if (isset($this->view['data']['ptemplate_file'])) {
				
				$template = $this->view['data']['ptemplate_file'];
				$template_file = WPSG_PATH_PRODUKTTEMPLATES.$this->view['data']['ptemplate_file'];
				
			} else {
				
				$template = 'standard.phtml';
				$template_file = WPSG_PATH_PRODUKTTEMPLATES.'standard.phtml';
				
			}

			$this->callMods('renderProdukt_templateSelect', array(&$this->view['data'], &$template_file));

			// Das Template im UserView suchen
			$template_file_child_theme = str_replace(WPSG_PATH_PRODUKTTEMPLATES, WPSG_PATH_PRODUKTTEMPLATES_CTV, $template_file);
            $template_file_theme = str_replace(WPSG_PATH_PRODUKTTEMPLATES, WPSG_PATH_PRODUKTTEMPLATES_TV, $template_file);
            $template_file_uv = str_replace(WPSG_PATH_PRODUKTTEMPLATES, WPSG_PATH_PRODUKTTEMPLATES_UV, $template_file);
            $template_file_uv_old = str_replace(WPSG_PATH_PRODUKTTEMPLATES, WPSG_PATH_PRODUKTTEMPLATES_UV_OLD, $template_file);

            if (file_exists($template_file_child_theme) && $this->get_option('wpsg_ignoreuserview') != '1') $template_file = $template_file_child_theme;
			else if (file_exists($template_file_theme) && $this->get_option('wpsg_ignoreuserview') != '1') $template_file = $template_file_theme;
			else if (file_exists($template_file_uv) && $this->get_option('wpsg_ignoreuserview') != '1') $template_file = $template_file_uv;
			else if (file_exists($template_file_uv_old) && $this->get_option('wpsg_ignoreuserview') != '1') $template_file = $template_file_uv_old;
			else {
				
				$arTemplate = $this->loadProduktTemplates(false, true);
				
				foreach ($arTemplate as $k => $v) {
					
					if (basename($v) === $template) $template_file = $v;
					
				}
								
			}
			
			if (!is_file($template_file)) { wpsg_debug(wpsg_translate(__('Das Template (#1#) für ein Produkt (ID:#2#) scheint nicht zu existieren!', 'wpsg'), $template_file, $produkt_id)); return; }

			// Den Index für getTemplateIndex hochzählen
			wpsg_addSet($GLOBALS['wpsg_produkt_index'], 1);
			$this->view['product_index'] = $GLOBALS['wpsg_produkt_index'];

			if (!wpsg_isSizedString($this->view['data']['referer'])) $myReferer = $this->getCurrentURL();
			else $myReferer = $this->view['data']['referer'];

			if (!wpsg_isSizedString($this->view['data']['product_key'])) $this->view['data']['product_key'] = $this->view['data']['id'];

			$this->view['data']['id'] = $this->getProduktId($this->view['data']['id']);

			$this->view['oProduct'] = wpsg_product::getInstance($this->view['data']['product_key']);
			$this->view['oProduct']->appendData($this->view['data']);

			if (wpsg_isSizedString($this->view['data']['product_key'])) $this->view['oProduct']->setProductKey($this->view['data']['product_key']);

			$html  = '';
			$html .= '<form class="wpsg_productform" id="wpsg_produktform_'.$GLOBALS['wpsg_produkt_index'].'" method="post" action="'.$myReferer.'">';
			$html .= $this->render($template_file, false);
			$html .= '<div style="display:none;">';
			$html .= '<input type="hidden" name="wpsg[template]" value="'.basename($template_file).'" />';
			$html .= '<input type="hidden" name="myReferer" value="'.htmlspecialchars($myReferer).'" />';
			$html .= '<input type="hidden" name="wpsg[produkt_id]" value="'.$this->getProduktId($produkt_id).'" />';
			$html .= '<input type="hidden" name="wpsg[product_key]" value="'.$this->view['data']['product_key'].'" />';
			$html .= '</div>';
			$html .= '</form>';

			$this->callMods('renderProdukt_afterForm', array(&$this->view['data'], &$html));

			// Wenn eine andere Sprache gewählt wurde dann zurücksetzen
			if ($force_locale !== false)
			{

				if (isset($old_l10n))
				{

					$l10n['wpsg'] = &$old_l10n;

				}

				$this->force_locale = false;

			}

			$this->titleDisplayed = false;

			return $html;

		} // public function renderProdukt($produkt_id)

		/**
		 * Shortcode für Warenkorbbutton, um Produkte aus Kategorieübersicht in Warenkorb zu legen (muss in jedem Beitrag eingefügt werden)
		 */
		public function shortcode_basket($atts, $content = '')
		{

			$product_id = wpsg_getStr($atts['product']);
			$title = wpsg_getStr($atts['title']);
			$linktext = wpsg_getStr($atts['linktext']);

			/* Wenn kein Linktext im Beitrag, Linktext aus Template */
			if (!wpsg_isSizedString($linktext)) $linktext = __('Produkt in den Warenkorb legen', 'wpsg');

			$product_url = $this->getProduktLink($product_id);

			$strReturn = '<a class="wpsg_button wpsg_button_categorie_basket wpsg_addProdukt ';

			/* Varianten=true -> öffnet Lightbox mit Variantenauswahl */
			$strReturn .= (($this->callMod('wpsg_mod_productvariants', 'isVariantsProduct', array($product_id)))?'wpsg_variantProduct':'');

			$strReturn .= '" ';

			$strReturn .= ' data-product_id="'.wpsg_hspc($product_id).'" ';

			$strReturn .= ' href="'.wpsg_url($product_url).'" ';

			$strReturn .= ' title="'.wpsg_hspc($title).'" ';

			$strReturn .= '>'.wpsg_hspc($linktext).'</a>';

			return $strReturn;

		}

		/**
		 * Verarbeitet den Shortcode wpsg
		 */
		public function shortcode($atts, $content = '')
		{

			try
			{

				$this->bShortcode = true;

				if (isset($atts['template']))
				{

					$template = $atts['template'];

				}
				else if (isset($atts['produktgruppe']))
				{

					return $this->callMod('wpsg_mod_productgroups', 'shortcode', array($atts));

				}
				else
				{

					$template = false;

				}

				if ($atts['product'] === '-1')
				{

					$arProductIDs = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` <= 0 ORDER BY `id` ASC ");
					$content = '';

					foreach ($arProductIDs as $product_id)
					{

						if (isset($atts['hide_title']) && $atts['hide_title'] === '1') $this->titleDisplayed = true;
						else $this->titleDisplayed = false;

						$oProduct = wpsg_product::getInstance($product_id);

						if ($oProduct->canDisplay()) $content .= $this->renderProdukt($product_id, $template);

					}

					return $content;

				}
				else
				{

					if (isset($atts['hide_title']) && $atts['hide_title'] === '1') $this->titleDisplayed = true;
					else $this->titleDisplayed = false;

					return $this->renderProdukt($atts['product'], $template);

				}

				$this->bShortcode = false;

			}
			catch (Exception $e)
			{

				return $e->getMessage();

			}

		} // public function shortcode($atts, $content = '')

		/**
		 * Checkt und setzt die Kundenvorgaben in der Session, sollte nichts eingetragen sein
		 * Setzt auch die Variablen für das Template
		 *
		 * Hier kann noch nicht nach vorhandensein der Zahlungsarten in this->arShipping geschaut werden!
		 */
		public function checkCustomerPreset()
		{

			if (!wpsg_isSizedString($_SESSION['wpsg']['checkout']['shipping']) && wpsg_isSizedString($this->get_option('wpsg_customerpreset_shipping')))
			{

				$_SESSION['wpsg']['checkout']['shipping'] = $this->get_option('wpsg_customerpreset_shipping');
				if (isset($this->view['basket'])) $this->view['basket']['checkout']['shipping'] = $_SESSION['wpsg']['checkout']['shipping'];

			}

			if (!wpsg_isSizedString($_SESSION['wpsg']['checkout']['payment']) && wpsg_isSizedString($this->get_option('wpsg_customerpreset_payment')))
			{

				$_SESSION['wpsg']['checkout']['payment'] = $this->get_option('wpsg_customerpreset_payment');
				if (isset($this->view['basket'])) $this->view['basket']['checkout']['payment'] = $_SESSION['wpsg']['checkout']['payment'];

			}

			if (!wpsg_isSizedString($_SESSION['wpsg']['checkout']['land']) && wpsg_isSizedString($this->get_option('wpsg_defaultland')))
			{

				$_SESSION['wpsg']['checkout']['land'] = $this->get_option('wpsg_defaultland');
				if (isset($this->view['basket'])) $this->view['basket']['checkout']['land'] = $_SESSION['wpsg']['checkout']['land'];

			}

			if (!wpsg_isSizedString($_SESSION['wpsg']['checkout']['title']) && wpsg_isSizedString($this->get_option('wpsg_customerpreset_title')))
			{

				$_SESSION['wpsg']['checkout']['title'] = $this->get_option('wpsg_customerpreset_title');
				if (isset($this->view['basket'])) $this->view['basket']['checkout']['title'] = $_SESSION['wpsg']['checkout']['land'];

			}

		} // public function checkCustomerPreset()

		/**
		 * Registriert die im Shop verwendeten Widgets
		 */
		public function widget_init()
		{

			require_once(dirname(__FILE__).'/../lib/wpsg_basket_widget.class.php');

			register_widget("wpsg_basket_widget");

		} // public function widget_init()

		/**
		 * Gibt das Standardland als wpsg_country Objekt zurück
		 * @param $id bool Wenn true dann wird nur die ID zurückgegeben für schnelle Vergleiche
         * @return wpsg_country|boolean
		 */
		public function getDefaultCountry($id = false)
		{

			$country_id = $this->get_option('wpsg_defaultland');

			// Kein Standardland, hier kann nichts zurückgegeben werden
			if ($country_id === false) return false;

			if ($id === false) {

				$default_country = wpsg_country::getInstance($country_id);

				return $default_country;

			} else {

				return $country_id;

			}

		} // public function getDefaultCountry()

		/**
		 * Gibt das aktuelle Land für die Frontendausgabe zurück
		 * ist wichtig für die Preisberechnung (Mehrwertsteuer)
		 */
		public function getFrontendCountry($id = false)
		{

			if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['land']))
			{

				$country_id = $_SESSION['wpsg']['checkout']['land'];

			}
			else
			{

				return $this->getDefaultCountry($id);

			}

			if ($id === false)
			{

				$oCountry = wpsg_country::getInstance($country_id);
				return $oCountry;

			}
			else
			{

				return $country_id;

			}

		} // public function getFrontendCountry()

		/**
		 * Gibt den Mehrwertsteuer Wert zurück,
		 * der für die Berechnung des Produktpreises notwendig ist
		 */
		public function getCalcTaxValue($tax_key)
		{

			if (wpsg_isSizedInt($this->view['oOrder']->adress_data['land'])) $country_id = $this->view['oOrder']->adress_data['land'];
			if (wpsg_isSizedInt($this->country)) $country_id = $this->country;
			if (wpsg_isSizedInt($country_id))
				$country = wpsg_country::getInstance($country_id);
			else 
				$country = $this->getDefaultCountry();
			
			//$country = $this->getDefaultCountry();
			$tax = $country->getTax($tax_key);
			if ($tax == 19)
				$tax = 19;
			if ($tax == 20)
				$tax = 20;
			return $country->getTax($tax_key);

			//$this->view['oOrder']->adress_data['land']
			//$country = $this->getFrontendCountry();

			$noMwSt = false;

			// 1 = keine MwSt.
			// 2 = keine MwSt. bei USt.IdNr.
			if ($country->mwst == 1) $noMwSt = true;
			if ($country->mwst == 2 && wpsg_isSizedString($this->basket->arCheckout['ustidnr'])) $noMwSt = true;

			if ($noMwSt)
			{

				// Hier die MwSt des Standardlandes für Berechnung verwenden
				$country = $this->getDefaultCountry();
				return $country->getTax($tax_key);

			}
			else
			{

				return $country->getTax($tax_key);

			}

		}

        /**
         * Die Funktion soll ein Verzeichnis vor direkten Browseranfragen schützen, in dem es eine .htaccess Datei anlegt
         * @param $path
         */
		public function protectDirectory($path, $arEnableFiles = []) {

            if (!file_exists($path)) mkdir($path, 0775, true);

            $htaccess = \trailingslashit($path).'.htaccess';
            
            if (!file_exists($htaccess))
            {

                $handle = fopen($htaccess, "w+");
                
                $content = "
                
Order Allow,Deny
                
<ifModule mod_authz_core.c>
Require all denied
</ifModule>

# line below if for Apache 2.2
<ifModule !mod_authz_core.c>
deny from all
</ifModule>

# section for Apache 2.2 and 2.4
IndexIgnore *
                
                ";
                
                foreach ($arEnableFiles as $f) {
                    
                    $content .= "

<Files ".$f.">
allow from all
</Files>

";
                    
                }
                
                fwrite($handle, $content, strlen($content));
                fclose($handle);

            }
		    
        } // public function protectDirectory($path)

		/**
		 * Lädt die Daten eines Produktes
		 */
		public function loadProduktArray($produkt_id, $override = array(), $loadDisabled = false)
		{
		    
		    //if (array_key_exists($produkt_id, $this->productCache)) return $this->productCache[$produkt_id];
		    
			$produkt = $this->cache->loadProduct($produkt_id);
			
			// Im Backend muss die loadArray durchlaufen werden, da die Pos Spalte für die Sortierung benötigt wird
			if ($loadDisabled === false && wpsg_isSizedInt($produkt['disabled']) && !is_admin()) return array();
			
			if (!wpsg_isSizedArray($produkt)) throw new \wpsg\Exception('Produkt (ID:'.$produkt_id.') konnte nicht geladen werden.');

			foreach ($override as $k => $v) $produkt[$k] = $v;

			// Übersetzung einbeziehen
			if ($this->isOtherLang())
			{

				$produkt_trans = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($produkt_id)."' AND `lang_code` = '".wpsg_q($this->getCurrentLanguageCode())."'");

				if ($produkt_trans['id'] > 0)
				{

					$produkt['name'] = $produkt_trans['name'];
					$produkt['beschreibung'] = $produkt_trans['beschreibung'];
					$produkt['detailname'] = $produkt_trans['detailname'];

				}

			}

			// Daten aus Order-Produkt nehmen, da die Bestellung im Backend bearbeitbar ist
			if (isset($produkt['op_mwst_key']))
			{
			    
				$produkt['mwst_key'] = $produkt['op_mwst_key'];
				$produkt['preis'] = $produkt['price'];
				$produkt['preis_netto'] = $produkt['price_netto'];
				$produkt['preis_brutto'] = $produkt['price_brutto'];
				//$produkt['mwst_value'] = $produkt['op_mwst_value'];
                
			}

			$produkt['product_id'] = $this->getProduktID($produkt_id);
			$produkt['mwst_value'] = $this->getCalcTaxValue($produkt['mwst_key']);

			// Preis umrechnen für Standardland
			if ($this->get_option('wpsg_preisangaben') == WPSG_NETTO)
			{

				// Preis im Backend ist in Netto
				$produkt['preis_netto'] = $produkt['preis'];
				$produkt['preis_brutto'] = wpsg_calculatePreis($produkt['preis'], WPSG_BRUTTO, $produkt['mwst_value']);

			}
			else
			{
			    
                $tax_value_default_country = $this->getDefaultCountry()->getTax($produkt['mwst_key']);

				// Preis im Backend ist in Brutto
				$produkt['preis_brutto'] = $produkt['preis'];
				$produkt['preis_netto'] = wpsg_calculatePreis($produkt['preis'], WPSG_NETTO, $tax_value_default_country);

			}

			$produkt['tax_defaultLand'] = $produkt['mwst_value'];
			$produkt['preis_defaultLand_brutto'] = $produkt['preis_brutto'];
			$produkt['preis_defaultLand_netto'] = $produkt['preis_netto'];

			// Jetzt habe ich Netto/Brutto Werte im Standardland
			// Wenn es jetzt ein Leistungsort Produkt ist, dann muss ich die Steuer des Ziellandes bestimmen
			// 1=euleistungsortregel
			if (wpsg_isSizedInt($produkt['euleistungsortregel']) && $this->getDefaultCountry(true) != $this->getFrontendCountry(true))
			{

				$oFrontendCountry = $this->getFrontendCountry();

				$produkt['mwst_value'] = $oFrontendCountry->getTax($produkt['mwst_key']);
				$produkt['preis_brutto'] = wpsg_calculatePreis($produkt['preis_netto'], WPSG_BRUTTO, $produkt['mwst_value']);

			}

			if ($this->getFrontendTaxview() == WPSG_BRUTTO) $produkt['preis'] = $produkt['preis_brutto'];
			else $produkt['preis'] = $produkt['preis_netto'];

			$produkt['min_preis'] = wpsg_tf($produkt['preis']);
			$produkt['max_preis'] = wpsg_tf($produkt['preis']);

			if (get_option('wpsg_options_nl2br_out') == '1')
			{

				$produkt['beschreibung'] = nl2br($produkt['beschreibung']);

			}

			if (get_option('wpsg_options_no_rte_apply_filter') != '1')
			{

				// Filter auf Beschreibung anwenden (RTE)
				// Den wpsgContentFilter deaktivieren um rekursion zu vermeiden
				remove_filter('the_content', array($GLOBALS['wpsg_sc'], 'content_filter'));
				$produkt['beschreibung'] = apply_filters('the_content', $produkt['beschreibung']);
				add_filter('the_content', array($GLOBALS['wpsg_sc'], 'content_filter'));

			}

			global $wp_rewrite;

			if (is_object($wp_rewrite))
			{

				$produkt['url'] = $this->getProduktLink($produkt['id']);

			}

			if ($this->hasMod('wpsg_mod_weight'))
			{

				$produkt['min_weight'] = wpsg_tf($produkt['weight']);
				$produkt['max_weight'] = wpsg_tf($produkt['weight']);

			}

			// Artikelnummer
			$produkt['anr'] = $this->getProductAnr($produkt['id']);
			
			// $_REQUEST  $_SESSION $GLOBALS
			if ($this->get_option('wpsg_afterinsert') == '3')
			{
				
				if (isset($_REQUEST['wpsg']['produkt_id']) && ($_REQUEST['wpsg']['produkt_id'] == $produkt['product_id'])) { $produkt['product_id'] = $_REQUEST['wpsg']['produkt_id']; }
				if (isset($_REQUEST['wpsg']['product_key'])) { $produkt['product_key'] = $_REQUEST['wpsg']['product_key']; }
			    
			}
			 
			$this->callMods('loadProduktArray', array(&$produkt));

			if ($produkt['preis'] < 0) $produkt['preis'] = 0;
			if ($produkt['preis_brutto'] < 0) $produkt['preis_brutto'] = 0;
			if ($produkt['preis_netto'] < 0) $produkt['preis_netto'] = 0;

            $this->productCache[$produkt_id] = $produkt;
            
			return $produkt;

		} // public function loadProduktArray($produkt_id)

		/**
		 * Gibt den Index aus einer Globalen zurück um sicherzustellen das JS Variablen nicht überschrieben werden
		 * wenn mehrere Produkte auf einer Seite sind. Wird von renderProdukt hochgez√§hlt
		 */
		public function getTemplateIndex()
		{

			return $GLOBALS['wpsg_produkt_index'];

		} // public function getTemplateIndex()

		/**
		 * Lädt die verfügbaren Produkttemplates
		 */
		public function loadProduktTemplates($key = false, $fullpath = false, $view_path = false) {
 
			$arTemplates = array();

			$handle = @opendir(WPSG_PATH_PRODUKTTEMPLATES);
			if ($handle) { while ($file = readdir($handle))
			{

				if (is_file(WPSG_PATH_PRODUKTTEMPLATES.$file) && preg_match('/(.*).phtml$/', $file))
				{

				    if ($fullpath && $view_path) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;
                    else if ($fullpath) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;

					if ($key) $arTemplates[$file] = $file;
					else $arTemplates[] = $file;

				}

			} }
			@closedir($handle);

			$handle = @opendir(WPSG_PATH_PRODUKTTEMPLATES_UV);
			if ($handle) { while ($file = readdir($handle))
			{

				if (is_file(WPSG_PATH_PRODUKTTEMPLATES_UV.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
				{

                    if ($fullpath && $view_path) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;
                    else if ($fullpath) $file = WPSG_PATH_PRODUKTTEMPLATES_UV.$file;

					if ($key) $arTemplates[$file] = $file;
					else $arTemplates[] = $file;

				}
			} }
			@closedir($handle);

			$handle = @opendir(WPSG_PATH_PRODUKTTEMPLATES_UV_OLD);
			if ($handle) { while ($file = readdir($handle))
			{
				if (is_file(WPSG_PATH_PRODUKTTEMPLATES_UV_OLD.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
				{

                    if ($fullpath && $view_path) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;
                    else if ($fullpath) $file = WPSG_PATH_PRODUKTTEMPLATES_UV_OLD.$file;

					if ($key) $arTemplates[$file] = $file;
					else $arTemplates[] = $file;

				}

			} }
			@closedir($handle);

			$handle = @opendir(WPSG_PATH_PRODUKTTEMPLATES_TV);
			if ($handle) { while ($file = readdir($handle))
			{

				if (is_file(WPSG_PATH_PRODUKTTEMPLATES_TV.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
				{

                    if ($fullpath && $view_path) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;
                    else if ($fullpath) $file = WPSG_PATH_PRODUKTTEMPLATES_TV.$file;

					if ($key) $arTemplates[$file] = $file;
					else $arTemplates[] = $file;

				}

			} }
			@closedir($handle);

            $handle = @opendir(WPSG_PATH_PRODUKTTEMPLATES_CTV);
            if ($handle) { while ($file = readdir($handle))
            {

                if (is_file(WPSG_PATH_PRODUKTTEMPLATES_CTV.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
                {

                    if ($fullpath && $view_path) $file = WPSG_PATH_PRODUKTTEMPLATES.$file;
                    else if ($fullpath) $file = WPSG_PATH_PRODUKTTEMPLATES_CTV.$file;

                    if ($key) $arTemplates[$file] = $file;
                    else $arTemplates[] = $file;

                }

            } }
            @closedir($handle);

            $this->callMods('loadProduktTemplates', [&$arTemplates, $key, $fullpath, $view_path]);
            
			return $arTemplates;

		} // public function loadProduktTemplates()

		/**
		 * Gibt die Produkt Artikelnummer für ein Produkt anhand des Produktschlüssels zurück
		 */
		public function getProductAnr($product_key)
		{

			$anr = false;

			$return = $this->callMods('getProductAnr', array($product_key, &$anr));

			if ($anr === false)
			{

				// Kein Modul hat die Erstellung der Produktartikelnummer übernommen
				$product_data = $this->cache->loadProduct( $this->getProduktID($product_key));

				return $product_data['anr'];

			}
			else
			{

				return $anr;

			}

		} // public function getProductAnr($product_key)

		/**
		 * Gibt den Link zu einem Produkt zurück wenn möglich
		 * Siehe auch: wpsg_product->getProductURL
		 */
		public function getProduktLink($basket_data)
		{

			if (wpsg_isSizedInt($basket_data)) $product_id = $basket_data;
			else if (isset($basket_data['product_id'])) $product_id = $basket_data['product_id'];
			else if (wpsg_isSizedString($basket_data)) $product_id = $this->getProduktID($basket_data);
			else $product_id = $this->getProduktID($basket_data['id']);

			$url = false;
			$this->callMods('getProduktlink', array($product_id, &$url));

			if ($url === false)
			{

				$produkt_data = $this->cache->loadProduct($product_id);

				if ($produkt_data['partikel'] > 0)
				{

					return get_permalink($produkt_data['partikel']);

				}
				else if (isset($basket_data['referer']) && $basket_data['referer'] != '')
				{

					return $basket_data['referer'];

				}
				else
				{


					//wpsg_debug("=".apply_filters( 'wpml_current_language', NULL ));
					//do_action('wpml_switch_language', string $language_code )

					// Für das Bakend der Produktverwaltung, dass die Links auf die entsprechende Sprache gestellt werden
					if (function_exists('icl_object_id'))
					{

						if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
						{

							$lang_reset = apply_filters('wpml_current_language', null);
							do_action('wpml_switch_language', $_REQUEST['wpsg_lang']);

						}
						else
						{

							$lang_reset = apply_filters('wpml_current_language', null);
							do_action('wpml_switch_language', $this->getDefaultLanguageCode());

						}

					}

					if ($this->get_option('wpsg_page_product') > 0)
						$product_url = $this->getURL(wpsg_ShopController::URL_PRODUCTDETAIL);
					else
						$product_url = $this->getURL(wpsg_ShopController::URL_BASKET);

					if (function_exists('icl_object_id') && isset($lang_reset))
					{

						do_action('wpml_switch_language', $lang_reset);

					}

					if (strpos($product_url, "?") > 0)
					{
						$product_url .= "&wpsg_action=showProdukt&produkt_id=".$product_id;
					}
					else
					{
						$product_url .= "?wpsg_action=showProdukt&produkt_id=".$product_id;
					}

					return $product_url;

				}

			}
			else
			{

				return $url;

			}

		} // public function getProduktLink($produkt_id)

		/**
		 * Gibt die ID des Produkts zurück
		 * Bei Varianten: vp_8/0_0
		 */
		public function getProduktID($produkt_key)
		{

			if (is_numeric($produkt_key)) return $produkt_key;
			else if (preg_match('/pv_(.*)/', $produkt_key))
			{
				return preg_replace('/(pv_)|(\|(.*))/', '', $produkt_key);
			}
			else if (preg_match('/vp_(.*)/', $produkt_key))
			{
				return preg_replace('/(vp_)|(\/(.*))/', '', $produkt_key);
			}
			else if (preg_match('/abo_\d+_\d+/', $produkt_key))
			{

				$arData = explode('_', $produkt_key);
				return $this->getProduktID($arData[2]);

			}
			else
			{

				throw new \wpsg\Exception(_('Produkt ID konnte nicht gebildet werden: ').$produkt_key, \wpsg\Exception::TYP_UNEXPECTED);
				return 0;

			}

		} // public function getProduktID($produkt_key)

		/**
		 * Gibt alle Produkte des Shops zurück und beachtet die Übersetzung
		 * Kombination aus ID/Name für Selectboxen
		 */
		public function getAllProductsForSelect()
		{

			$arProducts = $this->db->fetchAssocField("
				SELECT
					P.`id`, P.`name`
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
				WHERE
					P.`deleted` != '1' AND
					P.`lang_parent` = '0'
			", "id", "name");

			if ($this->isOtherLang())
			{

				foreach ($arProducts as $product_id => $product_name)
				{

					$product_trans_name = $this->db->fetchOne("SELECT `name` FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($product_id)."' AND `lang_code` = '".wpsg_q($this->getCurrentLanguageCode())."'");

					if (wpsg_isSizedString($product_trans_name))
					{

						$arProducts[$product_id] = $product_trans_name;

					}

				}

			}

			return $arProducts;

		} // public function getAllProductsForSelect()

		/**
		 * Setzt den Status einer Bestellung
		 * @param int $order_id ID der Bestellung
		 * @param int $status_id ID des Status
		 * @param bool $inform Kunde per Mail informieren
		 */
		public function setOrderStatus($order_id, $status_id, $inform)
		{
		    
			$this->cache->clearOrderCache($order_id);
			$this->view['order'] = $this->cache->loadOrder($order_id);
 
			$this->view['state_new_id'] = $status_id;
			
            // Keine Änderung, dann abbrechen
            if ($this->view['order']['status'] == $status_id) return false;

            // Muss vor der Mailsache aufgerufen werden da sonst die Felder der Bestellung nicht gesetzt sind (Paketverfolgung)
            // Die Bestellung darf auch noch nicht geladen sein, sonst wird das Objekt aus dem Cache genommen
            $this->callMods('setOrderStatus', array($order_id, $status_id, $inform));
            
			if (trim($this->view['order']['language']) != '') {

				// Die Bestellung wurde in einer anderen Sprache durchgeführt, hier wechsel ich auf die während der Bestellung gesetzte Sprach
				$this->setTempLocale($this->view['order']['language']);

			}
			
			// Bestellung wechselt auf Zahlungakzeptiert
			if ($status_id == 100) {

				// URL Benachrichtigung bei Zahlung ?
				$arProdukts = $this->db->fetchAssoc("
					SELECT
						P.`id`, OP.`menge`, OP.`mod_vp_varkey`, P.`posturl`, P.`posturl_bezahlung`, OP.`product_index`, OP.`productkey`
					FROM
						`".WPSG_TBL_ORDERPRODUCT."` AS OP
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = OP.`p_id`)
					WHERE
						OP.`o_id` = '".wpsg_q($order_id)."'
				");

				foreach ($arProdukts as $p) {

					if ($p['posturl'] != '' && $p['posturl_bezahlung'] == '1') {

						if ($p['productkey'] != '') $produkt_id = $p['productkey'];
						else $produkt_id = $p['id'];

						$this->notifyURL($p['posturl'], $produkt_id, $p['menge'], $order_id, 1, false, array(
							'product_index' => $p['product_index']
						));

					}

				}

				// Datum Zahlungseingang setzen
				$arrUpdate = array(
					'payed_date' => date('Y-m-d')
				);
				
				$this->db->updateQuery(WPSG_TBL_ORDER, $arrUpdate, "`id` = '".$order_id."'");

			}

			$status_alt = __($this->arStatus[$this->view['order']['status']], 'wpsg');
			$status_neu = __($this->arStatus[$status_id], 'wpsg');

			if ($inform) {

				$this->view['kunde'] = $this->cache->loadKunden($this->view['order']['k_id']);

				$this->view['status_alt'] = $this->arStatus[$this->view['order']['status']];
				$this->view['status_neu'] = $this->arStatus[$status_id];

				if ($this->get_option('wpsg_htmlmail') === '1')
				{

					$mail_html = $this->render(WPSG_PATH_VIEW.'/mailtemplates/html/status.phtml', false);

				}
				else
				{

					$mail_html = false;

				}

				$mail_text = $this->render(WPSG_PATH_VIEW.'/mailtemplates/status.phtml', false);

				// Ins Protokoll eintragen
				$this->db->ImportQuery(WPSG_TBL_OL, array(
					"o_id" => wpsg_q($order_id),
					"cdate" => "NOW()",
					"title" => wpsg_translate(__('Statusänderung mit Email an #1# von "#2#" auf "#3#"', 'wpsg'), $this->view['kunde']['email'], $status_alt, $status_neu),
					"mailtext" => $mail_text
				));

				$this->sendMail($mail_text, $this->view['kunde']['email'], 'status', array(), $order_id, false, $mail_html);

			}
			else {

				// Ins Protokoll eintragen
				$this->db->ImportQuery(WPSG_TBL_OL, array(
					'o_id' => wpsg_q($order_id),
					'cdate' => 'NOW()',
					'title' => wpsg_translate(__('Statusänderung von "#1#" auf "#2#"', 'wpsg'), $status_alt, $status_neu),
					'mailtext' => __('Kunde wurde nicht informiert', 'wpsg')
				));

			}

			$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
				'status' => wpsg_q($status_id)
			), "`id` = '".wpsg_q($order_id)."'");

			$this->restoreTempLocale();
			
			$this->callMods('setOrderStatus_after', array($order_id, $status_id, $inform));
			
			return true;

		} // public function setOrderStatus($order_id, $status_id, $inform)

		/**
		 * Gibt den Link zur Seite nach der Bestellung zur√ºck
		 */
		public function getDoneURL($order_id) {

			$basket_url = $this->getURL(wpsg_ShopController::URL_BASKET);
			
			$rand = wpsg_genCode(128);
			$this->db->UpdateQuery(WPSG_TBL_ORDER, ['secret' => wpsg_q($rand)], " `id` = '".wpsg_q($order_id)."' ");
			$code = md5($rand.$order_id.$rand);
			
			if (strpos($basket_url, '?') === false) {

				return $basket_url.'?order_id='.$order_id.'&wpsg_done='.rawurlencode($code);

			} else {

				return $basket_url.'&order_id='.$order_id.'&wpsg_done='.rawurlencode($code);

			}

		} // public function getDoneURL($order_id)

		/**
		 * Gibt die URL zum löschen eines Produktes zur√ºck
		 */
		public function getRemoveLinkURL($produkt_key)
		{

			$basket_url = $this->getURL(wpsg_ShopController::URL_BASKET);

			if (strpos($basket_url, '?') === false)
			{

				return $basket_url.'?wpsg_action=remove&wpsg_produkt='.$produkt_key;

			}
			else
			{

				return $basket_url.'&wpsg_action=remove&wpsg_produkt='.$produkt_key;

			}

		} // public function getRemoveLinkURL($produkt_key)

		/**
		 * Prüft ob das Modul aktiv ist
		 */
		public function hasMod($mod_name)
		{

			return array_key_exists($mod_name, $this->arModule);

		} // public function hasMod($mod_name)
		
		/*
		 * Prüft ob eine Moduldatei vorhanden ist
		 */
		public function hasModInstalled($mod_key)
		{
			
			return array_key_exists($mod_key, $this->arAllModule);
			
		}

		/**
		 * Prüft ob ein Modul eine Funktion implementiert
		 */
		public function hasModulFunction($mod_name, $function_name)
		{

			if (!$this->hasMod($mod_name)) return false;

			return method_exists($this->arModule[$mod_name], $function_name);

		} // public function hasModulFunction($mod, $function)

		/**
		 * Lädt die Module
		 */
		public function loadModule($all = false)
		{

			$this->arModule = array();
			$this->arAllModule = array();

			$mod_dir = opendir(WPSG_PATH_MOD);

			$global = false;
			if ($this->isMultiBlog() && $this->get_option('wpsg_multiblog_standalone', true) != '1') $global = true;

			while ($file = readdir($mod_dir)) {

				if (!is_dir(WPSG_PATH_MOD."/".$file) && $file != "." && $file != ".." && preg_match("/(.*)\.class\.php/i", $file) && $file != "wpsg_mod_basic.class.php") {

					if (file_exists(WPSG_PATH_USERMOD.$file)) require_once(WPSG_PATH_USERMOD.$file);
					else require_once(WPSG_PATH_MOD.$file);

					$class_name = preg_replace("/\.class\.php/", "", $file);

					$mod = new $class_name();

					if ($this->get_option($class_name, $global) > 0)
					{

					    if (property_exists($mod, 'version') && (property_exists($mod, 'free') && $mod->free !== true)) {

                            $wpsg_update_data = wpsg_get_update_data();

                            if ($this->bLicence && (@$wpsg_update_data['modulinfo'][get_class($mod)]['active'] == true || @$wpsg_update_data['modulinfo'][get_class($mod)]['demo_active'] == true))
                            {

                                $this->arModule[$class_name] = $mod;

                            }
                            
                        }
                        else
                        {
                            
					        $this->arModule[$class_name] = $mod;
                            
                        }

					}

					if ($all)
					{

						$this->arAllModule[$class_name] = $mod;

					}

				}
												
				$theme_mod_dir = get_template_directory().'/mods/';
				
				if (file_exists($theme_mod_dir))
				{
				
					$theme_mod_dir_h = opendir($theme_mod_dir);
					
					while ($file = readdir($theme_mod_dir_h))
					{
						
						if (!is_dir($theme_mod_dir.$file) && $file != "." && $file != ".." && preg_match("/(.*)\.class\.php/i", $file) && $file != "wpsg_mod_basic.class.php") {
							
							require_once($theme_mod_dir.$file);
		
							$class_name = preg_replace("/\.class\.php/", "", $file);
		
							$mod = new $class_name();
		
							if ($this->get_option($class_name, $global) > 0)
							{

                                if ($this->bLicence || !property_exists($mod, 'version') || (property_exists($mod, 'free') && $mod->free === true)) {
                                	
                                	$this->arModule[$class_name] = $mod;
                                	
								}
		
							}
		
							if ($all)
							{
		
							    $this->arAllModule[$class_name] = $mod;
		
							}
								
						}
						
					}
					
					
				}

			}

			\do_action('wpsg_loadModule', $all);
						 
            uasort($this->arModule, array($this, "cmp_mods"));

            if ($all) uasort($this->arAllModule, array($this, "cmp_mods"));
            
            // Module werden hier erst initiiert, da dann alle Module bekannt sind und die Reihenfolge hergestellt ist
            foreach ($this->arModule as $mod_key => $m)
            {

                if ($this->get_option(get_class($m), $global) > 0)
                {
                    
                    $m->init();

                }

            }

			return;

		} // public function loadModule($all = false)

		/**
		 * Gibt die ID einer Seite aus den Einstellungen zurück,
		 * beachtet dabei die aktuelle Sprache
		 */
		public function getPagePID($page_const)
		{

			switch ($page_const)
			{

				case self::PAGE_BASKET: return $this->getPageId($this->get_option('wpsg_page_basket'));
				case self::URL_PRODUCTDETAIL: return $this->getPageId($this->get_option('wpsg_page_product'));

			}

			throw new \wpsg\Exception(__('Ungültige Seite'));

		}

		/**
		 * Wie get_the_id() gibt aber bei WPML und übersetzten Seiten die ID der Originalseite zurück
		 */
		public function getPageId($page_id)
		{

			if (function_exists('icl_object_id')) //is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
			{

                return apply_filters('wpml_object_id', $page_id, 'post', false, $this->getDefaultLanguageCode());

			} else return $page_id;

		} // public function get_the_id()

		/**
		 * Gibt false oder die ID des übersetzten "Produkts" in der Produkttabelle zurück
		 */
		public function getTranslationID($product_id, $lang)
		{

			if (!array_key_exists($product_id, $this->_arProductTranslationIDs))
			{

				$trans_id = $this->_db->fetchOne("SELECT P.`id` FROM `".WPSG_TBL_PRODUCTS."` WHERE `lang_parent` = '".wpsg_q($product_id)."' AND `lang_code` = '".wpsg_q($lang)."'");

				if ($trans_id <= 0) return false;

				$this->_arProductTranslationIDs[$product_id] = $trans_id;

			}

			return $this->_arProductTranslationIDs[$product_id];

		} // public function getTranslationID($product_id, $lang)

		/**
		 * Gibt true zurück, sollte die aktive Sprache nicht die Aktuelle Sprache sein
		 */
		public function isOtherLang()
		{

			global $q_config;

			if ($this->force_locale !== false && $this->getCurrentLanguageLocale() != get_locale()) return true;

			if (function_exists('icl_object_id')) //if (is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
			{

				// WPML
				if (get_locale() != $this->getDefaultLanguageLocale()) return true;

			}
			else
			{

				// qTranslate

				$arLocales = $q_config['locale'];
				$qtDefault = $q_config['default_language'];

				if (isset($arLocales[$qtDefault]) && $arLocales[$qtDefault] != "" && get_locale() != $arLocales[$qtDefault])
				{

					return true;

				}

			}

			return false;

		} // public function isOtherLang()

		public function getLocaleToLanguageCode($code)
		{

			$arLang = $this->getStoreLanguages();

			foreach ($arLang as $lang)
			{

				if ($lang['lang'] == $code) return $lang['locale'];

			}

			return false;

		}

		/**
		 * Gibt true zurück wenn mehrere Sprachen im System verwendet werden
		 */
		public function isMultiLingual()
		{

			$arLang = $this->getStoreLanguages();

			return (sizeof($arLang) > 0);

		} // public function isMultiLingual()

		/**
		 * Gibt einen Array mit allen im Shop verwendeten Sprachen zurück
		 */
		public function getStoreLanguages()
		{

			global $q_config;

			$arLang = array();

			if (function_exists('icl_object_id')) // if (is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
			{

				// WPML

				$languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc&skip_missing=0');

				foreach ($languages as $l)
				{

					$arLang[$l['default_locale']] = array(
						'name' => $l['translated_name'],
						'flag' => $l['country_flag_url'],
						'locale' => $l['default_locale'],
						'lang' => $l['code']
					);

				}

			}
			else
			{

				// qTranslate Fallback

				if (wpsg_isSizedArray($q_config) && $q_config['enabled_languages'] != "")
				{

					$qt_arLang = $q_config['enabled_languages'];

					$arLocales = $q_config['locale'];
					$arFlags = $q_config['flag'];
					$arNames = $q_config['language_name'];

					foreach ($qt_arLang as $lang)
					{

						$arLang[$arLocales[$lang]] = array(
							"name" => $arNames[$lang],
							"flag" => $arFlags[$lang],
							"locale" => $arLocales[$lang],
							"lang" => $lang
						);

					}

				}

			}

			return $arLang;

		} // public function getStoreLanguages()

		/**
		 * Prüft die Einstellungen und setzt sie ggf. auf einen Standardwert
		 */
		public function checkDefault($name, $value, $force_global = false, $translation = false)
		{

			if ($this->get_option($name, $force_global) === false)
			{

				$this->update_option($name, $value, $force_global);

				if ($translation === true)
				{

					$this->addTranslationString($name, $value);

				}

			}

		} // public function checkDefault($name, $value)

		/**
		 * Sortier Hilfsfunktion für die Module
		 */
		public static function cmp_mods($mod1, $mod2)
		{

			if ((int)$mod1->id == (int)$mod2->id) return 0;
    		if ((int)$mod1->id > (int)$mod2->id) return 1;
    		if ((int)$mod1->id < (int)$mod2->id) return -1;

		} // private function cmp_mods($mod1, $mod2)

		/**
		 * Sortiert zwei Module nach dem Namen
		 */
		public static function cmp_mods_name($mod1, $mod2)
		{

			if ($mod1->name == $mod2->name) return 0;
			if ($mod1->name > $mod2->name) return 1;
			if ($mod1->name < $mod2->name) return -1;

		} // public static function cmp_mods_name($mod1, $mod2)

		/**
		 * Generiert einen Code gibt ihn zurück
		 */
		public function getCode($length = 8)
		{

			$pool = "qwertzupasdfghkyxcvbnm";
			$pool .= "23456789";
			$pool .= "WERTZUPLKJHGFDSAYXCVBNM";

			srand ((double)microtime() * 1000000);

			for ($i = 0; $i < $length; $i ++)
			{

    			$passw .= substr($pool, (rand() % (strlen($pool))), 1);

			}

    		return $passw;

		} // public function getCode($length = 8)

		/**
		 * Excerpt filtern
		 */
		public function the_excerpt($content)
		{

			$this->callMods('the_excerpt', array(&$content));

			return $content;

		}

		/**
		 * Ausgabe im Frontend
		 */
		public function content_filter($content)
		{

		    global $post;
		    
		    if (is_object($post) && \post_password_required($post)) return $content;
		    
			$out_content = $content;

			if (wpsg_isSizedInt($this->get_option('wpsg_page_product')) && get_the_ID() == $this->getPagePID(self::URL_PRODUCTDETAIL) && wpsg_isSizedString($_REQUEST['wpsg_action'], 'showProdukt') && wpsg_isSizedInt($_REQUEST['produkt_id']))
			{

				$content = $this->renderProdukt($_REQUEST['produkt_id']); return $content;

			}

			if (isset($_REQUEST['wpsg_mod']) && isset($_REQUEST['wpsg_action']) && $this->hasMod($_REQUEST['wpsg_mod']))
			{

				$this->callMod($_REQUEST['wpsg_mod'], $_REQUEST['wpsg_action'].'Action', array(&$out_content));

			}
			else
			{

				$bRenderModul = $this->callMods('content_filter', array(&$out_content));

				// Nur wenn kein Modul die Ausgabe übernommen hat
				if ($bRenderModul === true) $this->basketController->content_filter($out_content);

			}

			if ($this->get_option('wpsg_content_filter_direct') == '1')
			{

				echo $out_content;

			}

			if ($out_content != $content)
			{

				// wpShopGermany hat die Inhaltsausgabe manipuliert
				if (wpsg_isSizedInt($this->get_option('wpsg_nocache')))
				{

					if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);

				}

			}

			return $out_content;

		} // public function content_filter()

		/**
		 * Wordpress HOOK vor Ausführung des Querys
		 * @param unknown_type $query
		 */
		public function pre_get_posts(&$query)
		{

			$this->callMods('pre_get_posts', array(&$query));

		} // public function pre_get_posts($query)

		/**
		 * Lädt die Zahlungs- und Versandmethoden in den Shop Array
		 */
		public function addShipPay() {
 
			$this->arPayment = [
				0 => [
					'name' => __('Kostenlos', 'wpsg'),
					'id' => '0'
				]
			];
			
			$this->callMods('addShipping', array(&$this->arShipping));
			$this->callMods('addPayment', array(&$this->arPayment));
	
			$this->callMods('addShipping', array(&$this->arShippingAll, true));
			            
		} // public function addShipPay()

		/**
		 * Im Prinzip wie wp_loaded nur das get_permalink schon funktioniert
		 */
		public function template_redirect()
		{

			$this->checkEscape();

			// Das Land muss vor addShipping gesetzt werden
			if (isset($_REQUEST['wpsg']['checkout']['shipping_land'])) $_SESSION['wpsg']['checkout']['shipping_land'] = $_REQUEST['wpsg']['checkout']['shipping_land'];
			if (isset($_REQUEST['wpsg']['checkout']['land'])) $_SESSION['wpsg']['checkout']['land'] = $_REQUEST['wpsg']['checkout']['land'];

			if (!is_admin())
			{

			$this->basket->initFromSession();

			$this->addShipPay();

			}

			if (!is_admin())
			{

				// Sollte die Session noch nicht mit Werten gefüllt sein dann hier die Kundenvoreinstellungen laden
				$this->checkCustomerPreset();

				// Modulverarbeitung
				$this->callMods('template_redirect');

				if (wpsg_isSizedString($_REQUEST['wpsg_mod']) && wpsg_isSizedString($_REQUEST['wpsg_action']))
				{

					if ($this->hasModulFunction($_REQUEST['wpsg_mod'], $_REQUEST['wpsg_action'].'Redirect'))
					{

						$this->callMod($_REQUEST['wpsg_mod'], $_REQUEST['wpsg_action'].'Redirect'); return;

					}

				}

				// Verarbeitung im BasketController
 				$this->basketController->template_redirect();

				// Wenn Checkout abgeschickt wurde, dann Bestellung speichern
				if (wpsg_isSizedArray($_REQUEST['wpsg']['checkout'])) $this->basket->save(false);

				$this->basket->save(false);

				}

		} // public function template_redirect()

		public function wp_load() {
			
			if (is_admin()) {

                $this->addShipPay();
                
                if (!isset($_REQUEST['noheader'])) $this->systemcheck();

            }

		} // public function wp_load()

		/**
		 * Sendet eine Mail
		 * Sind $o_id order $k_id nicht false, so wird der Betreff und der Text durch die ShopErsetzungsfunktion gejagt
		 */
		public function sendMail(&$mail_text, $empfaenger, $mail_key, $anhang = array(), $o_id = false, $k_id = false, $mail_html = false, $force_subject = false)
		{

		    // Es gibt Mails, die beziehen sich nur auf Kunden 
            // Wenn aber eine Mail ohne Kunden aber mit Bestellung gesendet werden soll, so kann der Kunde auch aus der Bestellung genommen werden
		    if ($k_id === false && $o_id !== false)
            {
                
                $oOrder = wpsg_order::getInstance($o_id);
                $k_id = $oOrder->k_id;
                
            }
		    
			add_filter('wp_mail_content_type', 'wpsg_mail_content_type', 10, 2);

			if (wpsg_isSizedString($force_subject)) $subject = $force_subject;
			else $subject = $this->getMailValue('wpsg_'.$mail_key.'_betreff');

			$from = $this->getMailValue('wpsg_'.$mail_key.'_absender');
			$cc = $this->getMailValue('wpsg_'.$mail_key.'_cc');
			$bcc = $this->getMailValue('wpsg_'.$mail_key.'_bcc');

			$subject = $this->replaceUniversalPlatzhalter($subject, $o_id, $k_id);
			$mail_text = $this->replaceUniversalPlatzhalter($mail_text, $o_id, $k_id);
			$mail_html = $this->replaceUniversalPlatzhalter($mail_html, $o_id, $k_id);

			$headers = array();

			if ($from != '')
			{

				$headers['from'] = 'FROM:'.$from;
				$headers['rp'] = 'Return-Path:'.preg_replace('/(.*)\</', '<', $from);

			}

			if ($cc != '') $headers['cc'] = 'CC:'.$cc;
			if ($bcc != '') $headers['bcc'] = 'BCC:'.$bcc;

			// Werbetext / Impressum etc.
			$addText = $this->replaceUniversalPlatzhalter($this->getMailValue('wpsg_'.$mail_key.'_text'), $o_id, $k_id);
			if (wpsg_isSizedString($addText))
			{

				if (wpsg_isSizedString($mail_html)) $mail_html .= "<br /><br />".nl2br($addText);

				$mail_text .= "\r\n\r\n".strip_tags($addText);

			}

			if (wpsg_isSizedString($mail_html))
			{

				$mail_head = $this->render(WPSG_PATH_VIEW.'/mailtemplates/html/html_head.phtml', false);
				$mail_foot = $this->render(WPSG_PATH_VIEW.'/mailtemplates/html/html_foot.phtml', false);

				$mail_html = $mail_head.$mail_html.$mail_foot;

			}

			if (!wpsg_isSizedInt($this->get_option('wp_installed'))) {

				$mail_text = " 
----------------------------------------------------------------------------

Verwendete Shop Software: wpShopGermany (http://wpshopgermany.maennchen1.de)";	

				$footer = "<br />
<div style=\"border-top:1px solid #000000; width:100%; bottom:0px;  text-align:center; background-color:#F1EDED; color:000000\"><div style=\"padding:5px;\">
Verwendete Shop Software: <a href=\"http://wpshopgermany.de\">wpShopGermany</a></div></div>";
				
				if (wpsg_isSizedString($mail_html)) {

					$mail_html = preg_replace('/\<\/body\>/', $footer, $mail_html);

				}

			}

			$mail_text_send = $mail_text;

			if ($this->get_option('wpsg_htmlmail') === '1')
			{

				$this->text_message = $mail_text;

				if (wpsg_isSizedString($mail_html))
				{

					$mail_text_send = $mail_html;

				}

			}

			// Anhänge aus der Mediathek anfügen
            $arAttachmentSet = $this->getMailValue('wpsg_'.$mail_key.'_mediaattachment');

            if (wpsg_isSizedString($arAttachmentSet)) {

                $arAttachmentSet = explode(',', $arAttachmentSet);

                foreach ($arAttachmentSet as $a_id) {

                    $a_file = get_attached_file($a_id);
                    
                    if (file_exists($a_file)) {
                        
                        $anhang[] = $a_file;
                        
                    }
                    
                }
            }
			
			$this->callMods('sendMail', array($mail_key, $o_id, $k_id, &$empfaenger, &$subject, &$mail_text_send, &$headers, &$anhang));

			wp_mail($empfaenger, $subject, $mail_text_send, $headers, $anhang);

			return array($subject, $mail_text);

		} // public function sendMail($mail_text, $mail_key)

		/**
		 * Gibt eine Konfiguration der E-Mail Konfiguration zurück
		 * Wenn eine globale Vorgabe gesetzt ist wird diese verwendet
		 */
		public function getMailValue($key)
		{

			if ($this->get_option($key) === false || $this->get_option($key) == '')
			{

				$key_global = preg_replace('/\_(.*)\_/', '_global_', $key);

				return __($this->get_option($key_global), 'wpsg');

			}
			else
			{

				return __($this->get_option($key), 'wpsg');

			}

		} // public function getMailValue($key)

		/**
		 * Zeichnet die Kundenvariablen innerhalb des checkout2.phtml
		 */
		public function renderKundenField(&$checkout_view, $kv_id)
		{

			$arKV = $this->loadPflichtFeldDaten();

			if (!is_array($arKV)) return;

			// Gültige Kundenvariable?
			if (!array_key_exists($kv_id, $arKV['custom'])) return;

			$this->view['error'] = $checkout_view['error'];
			$this->view['basket'] = $checkout_view['basket'];
			$this->view['field'] = $arKV['custom'][$kv_id];
			$this->view['field']['id'] = $kv_id;

			$this->render(WPSG_PATH_VIEW.'/warenkorb/kundendaten_renderfield.phtml');

		} // public function renderKundenField()

		/**
		 * Gibt den Basket aus der Session als Objekt zurück
		 */
		public function getFullBasket()
		{

			$arBasket = array();

			// Session durchgehen


			return $arBasket;

		} // public function getFullBasket()

		/**
		 * Ersetzt in dem String $value die Platzhalter und gibt den neuen String zurück
		 */
		public function replaceUniversalPlatzhalter($value, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $arCustomReplace = array(), $product_index = false)
		{

			$arReplace = array();

			if ($order_id !== false)
			{
				
				$order_data = $this->cache->loadOrder($order_id);

				if (!wpsg_isSizedInt($kunden_id))
				{

					$kunden_id = $order_data['k_id'];

				}

				foreach ((array)$order_data as $k => $v)
				{

					if ($k == 'cdate')
					{

						$arReplace['/%order_'.$k.'%/i'] = wpsg_fromDate($v);

					}
					else
					{

						$arReplace['/%order_'.$k.'%/i'] = $v;

					}

				}

			}

			if (wpsg_isSizedInt($kunden_id))
			{

				$kunden_data = $this->cache->loadKunden($kunden_id);

				foreach ((array)$kunden_data as $k => $v)
				{

					$arReplace['/%kunde_'.$k.'%/i'] = $v;

				}

				// Kundenvariablen
				//wpsg_admin_pflicht
				$arPflicht = $this->get_option('wpsg_admin_pflicht');

				if (wpsg_isSizedArray($arPflicht['custom']))
				{

					foreach ($arPflicht['custom'] as $index => $kv)
					{

						$arReplace['/%kv_'.$index.'%/i'] = $this->getCustomFieldFromDB($kunden_id, $index);

					}

				}
				
				$arTitle = explode('|', $arPflicht['anrede_auswahl']);
				$arReplace['/%kunde_anrede%/i'] = __(wpsg_getStr($arTitle[$kunden_data['title']]), 'wpsg');

			}

			if ($product_id !== false && $product_id > 0)
			{

				$product_data = $this->cache->loadProduct($product_id);

				foreach ($product_data as $k => $v)
				{

					$arReplace['/%product_'.$k.'%/i'] = $v;

				}

				$arReplace['/%product_url/i'] = $this->getProduktLink($product_id);

			}

			if ($product_index !== false)
			{

				$arReplace['/%product_index%/'] = $product_index;

			}

			$arReplace['/%rand%/'] = rand(0, 1000);

			$arReplace['/%a%/'] = strftime('%a');
			$arReplace['/%A%/'] = strftime('%A');
			$arReplace['/%b%/'] = strftime('%b');
			$arReplace['/%B%/'] = strftime('%B');
			$arReplace['/%c%/'] = strftime('%c');
			$arReplace['/%C%/'] = strftime('%C');
			$arReplace['/%d%/'] = strftime('%d');
			$arReplace['/%D%/'] = strftime('%D');
			$arReplace['/%e%/'] = strftime('%e');
			$arReplace['/%g%/'] = strftime('%g');
			$arReplace['/%G%/'] = strftime('%G');
			$arReplace['/%h%/'] = strftime('%h');
			$arReplace['/%H%/'] = strftime('%H');
			$arReplace['/%I%/'] = strftime('%I');
			$arReplace['/%j%/'] = strftime('%j');
			$arReplace['/%m%/'] = strftime('%m');
			$arReplace['/%M%/'] = strftime('%M');
			$arReplace['/%n%/'] = strftime('%n');
			$arReplace['/%p%/'] = strftime('%p');
			$arReplace['/%r%/'] = strftime('%r');
			$arReplace['/%R%/'] = strftime('%R');
			$arReplace['/%S%/'] = strftime('%S');
			$arReplace['/%t%/'] = strftime('%t');
			$arReplace['/%T%/'] = strftime('%T');
			$arReplace['/%u%/'] = strftime('%u');
			$arReplace['/%U%/'] = strftime('%U');
			$arReplace['/%V%/'] = strftime('%V');
			$arReplace['/%w%/'] = strftime('%w');
			$arReplace['/%W%/'] = strftime('%W');
			$arReplace['/%x%/'] = strftime('%x');
			$arReplace['/%X%/'] = strftime('%X');
			$arReplace['/%y%/'] = strftime('%y');
			$arReplace['/%y1%/'] = strftime('%y', mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y') + 1));
			$arReplace['/%Y%/'] = strftime('%Y');
			$arReplace['/%Y1%/'] = strftime('%Y', mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y') + 1));
			$arReplace['/%Z%/'] = strftime('%Z');

			$arReplace['/%widerrufsformular_url%/i'] = WPSG_URL_UPLOADS.'wpsg_revocation/'.$this->get_option('wpsg_revocationform');

			$arReplace['/%anfrageliste_url%/i'] = $this->getURL(wpsg_ShopController::URL_REQUEST);
			$arReplace['/%warenkorb_url%/i'] = $this->getURL(wpsg_ShopController::URL_BASKET);
			$arReplace['/%warenkorb_more_url%/i'] = $this->getURL(wpsg_ShopController::URL_BASKET_MORE);
			$arReplace['/%versandkosten_url%/i'] = $this->getURL(wpsg_ShopController::URL_VERSANDKOSTEN);
			$arReplace['/%agb_url%/i'] = $this->getURL(wpsg_ShopController::URL_AGB);
			$arReplace['/%datenschutz_url%/i'] = $this->getURL(wpsg_ShopController::URL_DATENSCHUTZ);
			$arReplace['/%widerruf_url%/i'] = $this->getURL(wpsg_ShopController::URL_WIDERRUF);
			$arReplace['/%kundendaten_url%/i'] = $this->getURL(wpsg_ShopController::URL_CHECKOUT);
			$arReplace['/%impressum_url%/i'] = $this->getURL(wpsg_ShopController::URL_IMPRESSUM);
			$arReplace['/%profil_url%/i'] = $this->getURL(wpsg_ShopController::URL_PROFIL);
			$arReplace['/%bestellungen_url%/i'] = $this->getURL(wpsg_ShopController::URL_ORDER);
			$arReplace['/%abo_url%/i'] = $this->getURL(wpsg_ShopController::URL_ABO);
			$arReplace['/%logout_url%/i'] = $this->getURL(wpsg_ShopController::URL_LOGOUT);
			$arReplace['/%passwortvergessen_url%/i'] = $this->getURL(wpsg_ShopController::URL_LOSTPWD);

			/* Shopinfo */
			$arReplace['/%shopinfo_name%/i'] = $this->get_option('wpsg_shopdata_name');
			$arReplace['/%shopinfo_owner%/i'] = $this->get_option('wpsg_shopdata_owner');
			$arReplace['/%shopinfo_tel%/i'] = $this->get_option('wpsg_shopdata_tel');
			$arReplace['/%shopinfo_fax%/i'] = $this->get_option('wpsg_shopdata_fax');
			$arReplace['/%shopinfo_email%/i'] = $this->get_option('wpsg_shopdata_email');
			$arReplace['/%shopinfo_taxnr%/i'] = $this->get_option('wpsg_shopdata_taxnr');
			$arReplace['/%shopinfo_ustidnr%/i'] = $this->get_option('wpsg_shopdata_ustidnr');
			$arReplace['/%shopinfo_street%/i'] = $this->get_option('wpsg_shopdata_street');
			$arReplace['/%shopinfo_zip%/i'] = $this->get_option('wpsg_shopdata_zip');
			$arReplace['/%shopinfo_city%/i'] = $this->get_option('wpsg_shopdata_city');		
			$arReplace['/%shopinfo_country%/i'] = $this->getDefaultCountry()->getName();
			
			$arReplace['/%shopinfo_2_street%/i'] = $this->get_option('wpsg_shopdata_2_street');
			$arReplace['/%shopinfo_2_zip%/i'] = $this->get_option('wpsg_shopdata_2_zip');
			$arReplace['/%shopinfo_2_city%/i'] = $this->get_option('wpsg_shopdata_2_city');
			$arReplace['/%shopinfo_2_country%/i'] = $this->get_option('wpsg_shopdata_2_country');
			$arReplace['/%shopinfo_2_tel%/i'] = $this->get_option('wpsg_shopdata_2_tel');
			$arReplace['/%shopinfo_2_fax%/i'] = $this->get_option('wpsg_shopdata_2_fax');
			$arReplace['/%shopinfo_2_email%/i'] = $this->get_option('wpsg_shopdata_2_email');
			
			$arReplace['/%shopinfo_eu_name%/i'] = $this->get_option('wpsg_shopdata_eu_name');
			$arReplace['/%shopinfo_eu_tel%/i'] = $this->get_option('wpsg_shopdata_eu_tel');
			$arReplace['/%shopinfo_eu_fax%/i'] = $this->get_option('wpsg_shopdata_eu_fax');
			$arReplace['/%shopinfo_eu_email%/i'] = $this->get_option('wpsg_shopdata_eu_email');
			$arReplace['/%shopinfo_eu_street%/i'] = $this->get_option('wpsg_shopdata_eu_street');
			$arReplace['/%shopinfo_eu_zip%/i'] = $this->get_option('wpsg_shopdata_eu_zip');
			$arReplace['/%shopinfo_eu_city%/i'] = $this->get_option('wpsg_shopdata_eu_city');
			$arReplace['/%shopdata_eu_country%/i'] = $this->get_option('wpsg_shopdata_eu_country');
			
			$arReplace['/%shopinfo_bankname%/i'] = $this->get_option('wpsg_shopdata_bank_name');
			$arReplace['/%shopinfo_accountowner%/i'] = $this->get_option('wpsg_shopdata_bank_owner');
			$arReplace['/%shopinfo_iban%/i'] = $this->get_option('wpsg_shopdata_bank_iban');
			$arReplace['/%shopinfo_bic%/i'] = $this->get_option('wpsg_shopdata_bank_bic');

			/* Platzhalter für die Warenkorbdarstellung, da es recht aufwendig ist suche ich hier vorher nach dem Vorkommen */
			if (strpos(strtolower($value), '%basket_html%') !== false) $arReplace['/%basket_html%/i'] = $this->render(WPSG_PATH_VIEW.'/mailtemplates/html/order.phtml', false);
			if (strpos(strtolower($value), '%basket_txt%') !== false) $arReplace['/%basket_txt%/i'] = $this->render(WPSG_PATH_VIEW.'/mailtemplates/order.phtml', false);

			$revocation_form = WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->get_option('wpsg_revocationform');
			if (file_exists($revocation_form) && is_file($revocation_form)) $arReplace['/%widerrufformular_pdf_url%/i'] = WPSG_URL_UPLOADS.'wpsg_revocation/'.$this->get_option('wpsg_revocationform');

			$this->callMods('replaceUniversalPlatzhalter', array(&$arReplace, $order_id, $kunden_id, $rechnung_id, $product_id, $product_index));

			if (is_admin()) {
				
				$current_user = wp_get_current_user();
				
				$arReplace['/%user_username%/i'] = $current_user->user_login;
				$arReplace['/%user_email%/i'] = $current_user->user_email;
				$arReplace['/%user_firstname%/i'] = $current_user->user_firstname;				
				$arReplace['/%user_lastname%/i'] = $current_user->user_lastname;
				$arReplace['/%display_name%/i'] = $current_user->display_name; 
				
			}
			
			if (wpsg_isSizedArray($arCustomReplace)) foreach ($arCustomReplace as $k => $v) { $arReplace[$k] = $v; }

			$value = preg_replace(array_keys($arReplace), array_values($arReplace), $value);

			// Sollte irgendein platzhalter mal nicht ersetzt werden denn clear ich das
			if (isset($this->noReplace) && $this->noReplace === true) return $value;
			else
			{

				$value = preg_replace('/%[A-Za-z][^(%|\040)]*%/', '', $value);

				return $value;

			}

		} // public function replaceUniversalPlatzhalter($value, $order_id = false, $kunden_id = false, $rechnung_id = false)

        /**
         * Verzeichnis, in dem öffentliche Daten abgelegt werden können
         * @param bool $getUrl Wenn true, dann wird die URL zurückgegeben
         */
        public function getPublicDir($getUrl = false) {

            if ($this->isMultiBlog()) {

                $path = WPSG_PATH_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_public/';
                $url = WPSG_URL_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_public/';

            } else {

                $path = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_public/';
                $url = WPSG_URL_CONTENT.'uploads/wpsg/wpsg_public/';

            }

            if (!file_exists($path)) mkdir($path, 0775, true);
            
            if ($getUrl === true) return $url; else return $path;

        }

		/**
		 * Gibt das Verzeichnis zurück, in dem der Shop temporäre Daten ablegen kann
		 */
		public function getTempDir($getUrl = false) {

			if ($this->isMultiBlog()) {

				$path = WPSG_PATH_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/';
				$url = WPSG_URL_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/';

			} else {

				$path = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_temp/';
				$url = WPSG_URL_CONTENT.'uploads/wpsg/wpsg_temp/';

			}

			if (!file_exists($path)) wpsg_mkdir($path);

			if ($getUrl === true) return $url; else return $path;

		} // public function getTempDir()

		/**
		 * Generiert eine Temporäre Datei und gibt den Pfad dahingehend zurück
		 */
		public function getTempName($filename = '')
		{

			$path = $this->getTempDir();
			$filename = trim(time().rand(0, 1000)).$filename;

			return $path.$filename;

		} // public function getTempName()

		/**
		 * Führt ein Kommando aus und gibt die Programmausgabe zurück
		 */
		function exec($cmd)
		{

            $back="";

            $io = array();

            $p = proc_open($cmd,
                           array(1 => array('pipe', 'w'),
                                 2 => array('pipe', 'w')),
                           $io);

            while (!feof($io[1])) {
                $back .= htmlspecialchars(fgets($io[1]),
                                                        ENT_COMPAT, 'UTF-8');
            }

            while (!feof($io[2])) {
                $back .= htmlspecialchars(fgets($io[2]),
                                                        ENT_COMPAT, 'UTF-8');
            }

            fclose($io[1]);
            fclose($io[2]);

            proc_close($p);

            return $back;

		} // function exec($cmd)
		
		/**
		 * Fügt in die Dummy phtml ein zu übersetzenden String hinzu
		 * 
		 * @param $key
		 * @param $value
		 * @param null $sanitize_type
		 * @param array $sanitize_params
		 * 
		 * @return bool
		 * 
		 * @throws \wpsg\Exception
		 */
		public function addTranslationString($key, $value, $sanitize_type = null, $sanitize_params = []) {
			
			if (wpsg_isSizedString($sanitize_type)) {
				 
				$bValid = wpsg_checkInput($value, $sanitize_type, $sanitize_params);
				
				if (!$bValid) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Ihre Eingaben in den markierten Feldern waren ungültig, bitte überprüfen.', 'wpsg'));
					
					$_SESSION['sanitization_err_fields'][$key] = 0;
					
					return false;
					
				}
				
			}
			
			if (function_exists('icl_register_string'))
			{

				// WPML Version

                // Doppeleinträge verhindern
                $exist = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_WPML_ICL_STRINGS."` WHERE `value` = '".wpsg_q($value)."' AND `context` = 'wpsg' ");

                if (!wpsg_isSizedInt($exist))
                {

                    icl_register_string('wpsg', 'wpsg_auto_'.$key, $value, false, $this->get_option('wpsg_backend_language'));

                }

			}
			else
			{

				try
				{

					if (file_exists(WPSG_PATH_TRANSLATION))
					{

						preg_match_all('/\$(.*)\040+\=\040+\_\_\(\'([^\']*)/i', file_get_contents(WPSG_PATH_TRANSLATION), $matches);

						if (@sizeof($matches[1]) != @sizeof($matches[2]))
						{

							$this->addBackendError(__('Unerwarteter Fehler beim schreiben der Übersetzungsdatei.', 'wpsg'));
							return;

						}

						$bDrin = false;
						foreach ($matches[1] as $k => $m)
						{

							if ($matches[1][$k] == $key)
							{

								$bDrin = true;
								$matches[2][$k] = $value;

							}

						}

						if (!$bDrin)
						{

							$matches[1][] = $key;
							$matches[2][] = $value;

						}

						$strFile = '';

						foreach ($matches[1] as $k => $m)
						{

							$strFile .= '$'.$matches[1][$k]." = __('".$matches[2][$k]."', 'wpsg');\r\n";

						}

						@file_put_contents(WPSG_PATH_TRANSLATION, "<?php die(); \r\n".$strFile."\r\n?>");

					}
					else
					{

						// Leere Datei erstellen
						@file_put_contents(WPSG_PATH_TRANSLATION, "<?php die(); \r\n$".$key." = __('".$value."', 'wpsg'); \r\n?>");

					}

				}
				catch (Exception $e)
				{

					$this->addBackendError('nohspc_'.__('Sprachdatei konnte nicht geschrieben werden! Setzen Sie bitte Schreibrechte auf folgender Datei überprüfen:<br /><b>'.WPSG_PATH_TRANSLATION.'</b>', 'wpsg'));

				}

			}

		} // public function addTranslationString($key, $value)

		/**
		 * Prüft allgemeine Fehler und gibt Sie im Backend aus
		 */
		public function checkGeneralBackendError()
		{

			// Bei Ajaxanfragen etc. nix machen
			if (isset($_REQUEST['noheader']) && $_REQUEST['noheader'] == '1') return;

			if (
				$this->get_option("wpsg_version_installed", true) != false &&
				$this->get_option("wpsg_version_installed", true) != WPSG_VERSION &&
				$this->get_option('wpsg_message_db') != '1')
			{

				$this->addBackendError('nohspc_'.wpsg_translate(
					__('Ihre Datenbankversion ist nicht auf dem aktuellen Stand, aktualisieren Sie die Datenbank, indem Sie <a href="#1#">hier</a> klicken.<br />Klicken Sie <a href="#1#">hier</a>, um die Meldung auszublenden.', 'wpsg'),
						wp_nonce_url(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=allgemein&do=update&submit=1&noheader=1', 'wpsg-admin-db-update'),
					WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&noheader=1&action=clearMessage&wpsg_message=wpsg_message_soaphint&wpsg_redirect='.rawurlencode($_SERVER['REQUEST_URI'])
				));

			}

			$this->callMods('checkGeneralBackendError');

			/** Schreibrechte auf dem Pluginverzeichnis um Module zu installieren

			if (!is_writable(WPSG_PATH) && $this->get_option('wpsg_message_pluginwrite') != '1') $this->addBackendError('nohspc_'.wpsg_translate(__('<b>wpShopGermany:</b> Das Pluginverzeichnis (#1#) ist nicht durch den Webserver beschreibbar. Die wpShopGermany Module lassen sich möglicherweise nicht automatisch installieren. Klicken Sie <a href="#2#">hier</a>, um die Meldung auszublenden.', 'wpsg'),
				WPSG_PATH,
				WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&noheader=1&action=clearMessage&wpsg_message=wpsg_message_pluginwrite&wpsg_redirect='.rawurlencode($_SERVER['REQUEST_URI'])
			));

			*/

		} // public function checkGeneralBackendError()

		/**
		 * Wird von Wordpress aufgerufen über add_filter('wp_head ...
		 */
		public function wp_head()
		{

			echo '<!-- wpShopGermany Version '.WPSG_VERSION.' -->';

			$this->callMods('wp_head');

		} // public function wp_head()

		/**
		 * Wird von Wordpress aufgerufen über add_filter('wp_footer ...
		 */
		public function wp_foot()
		{

			// Variable wird mein anzeigen eines Warenkorbes oder beim Anzeigen eines Produktes aufgerufen
			// Widget kann dies auch auslösen
			if ($this->get_option('wpsg_geo_determination') !== '4')
			{

				$default_country = $this->getDefaultCountry();

				if ($this->showEULayer && wpsg_geo_code() != strtolower($default_country->kuerzel))
				{

					// Wurde der Dialog schon beantwortet?
					if (!isset($_SESSION['wpsg']['priceDialog']) || $_SESSION['wpsg']['priceDialog'] !== true)
					{

						echo '<script type="text/javascript">/* <![CDATA[ */';

						//echo 'jQuery(document).ready(function() { wpsg_customerquestion("'.$this->getCurrentURL().'"); } );';

						//Lieber die REQUEST_URI, damit alle _GET Variablen erhalten bleiben
						echo 'jQuery(document).ready(function() { wpsg_customerquestion("'.$_SERVER['REQUEST_URI'].'"); } );';

						echo '/* ]]> */</script>';

					}

				}

			}

			if (($this->hasFrontendError() || $this->hasFrontendMessage()) && $this->bMessageOut === false)
			{

				echo $this->render(WPSG_PATH_VIEW.'warenkorb/messageDialog.phtml');

			}

			$this->callMods('wp_foot');

		} // public function wp_foot()

		/**
		 * Verteilt den Wert $value auf die MwSt Sätze in $arBasket
		 * $value wird Brutto übergeben
		 */
		public function subMwSt(&$arBasket, $value)
		{

			if (wpsg_tf($value) <= 0) return 0;

			$price_option = WPSG_BRUTTO; //$this->get_option('wpsg_preisangaben');
			//if (isset($arBasket['price_frontend']))
			//	$price_option = $arBasket['price_frontend'];

			// Anteilig auf die Sätze verteilen
			foreach ((array)$arBasket['mwst'] as $mw_id => $mw)
			{

				if ($mw['base_value'] > 0)
				{
					if (($price_option == WPSG_BRUTTO))
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_brutto'];

						$arBasket['mwst'][$mw_id]['base_value'] -= $proz * $value;
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_BRUTTO, $mw['value']);

					}
					else
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_netto'];

						$arBasket['mwst'][$mw_id]['base_value'] -= $proz * $value;
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_NETTO, $mw['value']);

					}

				}

			}

			// Mehrwertsteuer Summe korrigieren
			$sum_mwst = 0;
			foreach ($arBasket['mwst'] as $mw_id => $mw)
			{

				$sum_mwst += $arBasket['mwst'][$mw_id]['sum'];

			}

			$sub = $arBasket['sum']['mwst'];

			$arBasket['sum']['mwst'] = abs($sum_mwst);

			return $sub - $arBasket['sum']['mwst'];

		} // public function subMwSt(&$arBasket, $value)

		/**
		 * Ermittelt den zu besteuernden Teil eines Nettowertes und verteilt ihn auf die MwSt. Sätze in $arBasket
		 * $value wird in Netto übergeben
		 */
		public function addMwSt(&$arBasket, $value)
		{

			if (wpsg_tf($value) <= 0) return 0;

			$price_option = WPSG_BRUTTO; //$this->get_option('wpsg_preisangaben');
			//if (isset($arBasket['price_frontend']))
			//	$price_option = $arBasket['price_frontend'];

			// Anteilig auf die Sätze verteilen
			foreach ((array)$arBasket['mwst'] as $mw_id => $mw)
			{

				if ($mw['base_value'] > 0)
				{
					if (($price_option == WPSG_BRUTTO))
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_brutto'];

						$arBasket['mwst'][$mw_id]['base_value'] -= wpsg_calculatePreis($proz * $value, WPSG_BRUTTO, $mw['value']);
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_BRUTTO, $mw['value']);

					}
					else
					{
						$proz = $mw['base_value'] / $arBasket['sum']['preis_netto'];

						//$arBasket['mwst'][$mw_id]['base_value'] -= wpsg_calculatePreis($proz * $value, WPSG_NETTO, $mw['value']);
						$arBasket['mwst'][$mw_id]['base_value'] -= $proz * $value;
						$arBasket['mwst'][$mw_id]['sum'] = wpsg_calculateSteuer($arBasket['mwst'][$mw_id]['base_value'], WPSG_NETTO, $mw['value']);

					}

				}

			}

			// Mehrwertsteuer Summe korrigieren
			$sum_mwst = 0.0;
			foreach ($arBasket['mwst'] as $mw_id => $mw)
			{

				$sum_mwst += $arBasket['mwst'][$mw_id]['sum'];

			}

			$sub = $arBasket['sum']['mwst'];

			$arBasket['sum']['mwst'] = abs($sum_mwst);

			return $sub - $arBasket['sum']['mwst'];

		} // public function addMwSt(&$arBasket, $value)

		/**
		 * Gibt den Wert einer benutzerdefinierten Kundenvariable zurück wenn die Bestellung noch nicht in der Datenbank ist
		 */
		public function getCustomerFieldFromSession($field_id)
		{

			if (!isset($_SESSION['wpsg']['checkout']['custom'][$field_id])) return;

			$pflicht = $this->loadPflichtFeldDaten();

			if (!is_array($pflicht) || !isset($pflicht['custom'][$field_id])) return;

			return $_SESSION['wpsg']['checkout']['custom'][$field_id];

		} // public function getCustomerFieldFromSession($field_id)

		/**
		 * Gibt den Wert einer benutzerdefinierten Kundenvariable zurück wenn die Bestellung schon in der Datenbank ist
		 */
		public function getCustomFieldFromDB($customer_id, $field_id)
		{

			$kunde_data = $this->cache->loadKunden($customer_id);
			$kunde_data['custom'] = @unserialize($kunde_data['custom']);

			if (!is_array($kunde_data)) return;

			if (!isset($kunde_data['custom'][$field_id])) return;

			$pflicht = $this->loadPflichtFeldDaten();

			if (!is_array($pflicht) || !isset($pflicht['custom'][$field_id])) return;

			return $kunde_data['custom'][$field_id];

		} // public function getCustomFieldFromDB($customer_id, $field_id)

		/**
		 * Setzt eine Zahlung für eine Bestellung
		 *
		 * Gibt true zurück wenn die Bestellung komplett beglichen werden kann
		 *
		 */
		public function setPayMent($order_id, $pay_amount)
		{

			$order_data = $this->cache->loadOrder($order_id);

			if (round(doubleval($order_data['price_gesamt']), 2) == round(doubleval($pay_amount), 2))
			{

				// Alles grün, Zahlung stimmt mit Bestellwert überein
				return true;

			}
			else
			{

				$logTitle = wpsg_translate(__('Versuchte Zahlung über #1#.', 'wpsg'), wpsg_ff($pay_amount, $this->get_option('wpsg_currency')));

				// Hier mal was ins Log schreiben
				$this->db->ImportQuery(WPSG_TBL_OL, array(
					"title" => $logTitle,
					"cdate" => "NOW()",
					"o_id" => wpsg_q($order_id),
					"mailtext" => wpsg_q(print_r($_REQUEST, 1)."\r\n".print_r($_SERVER, 1))
				));

			}

			return false;

		} // public function setPayMent($order_id, $pay_amount)

		/**
		 * Lädt die Daten für die Pflichtfelder und beachtet die Übersetzungen
		 */
		public function loadPflichtFeldDaten()
		{

			$arPflicht = $this->get_option('wpsg_admin_pflicht');

			if (!wpsg_isSizedArray($arPflicht)) {
				$arPflicht = array();
				$arPflicht['anrede'] = '1';
				$arPflicht['anrede_auswahl'] = 'Herr|Frau';
				$arPflicht['firma'] = '1';
				$arPflicht['vname'] = '1';
				$arPflicht['name'] = '1';
				$arPflicht['geb'] = '1';
				$arPflicht['email'] = '1';
				$arPflicht['emailconfirm'] = '1';
				$arPflicht['tel'] = '1';
				$arPflicht['fax'] = '1';
				$arPflicht['strasse'] = '1';
				$arPflicht['wpsg_showNr'] = '0';
				$arPflicht['plz'] = '1';
				$arPflicht['ort'] = '1';
				$arPflicht['land'] = '1';
				$arPflicht['ustidnr'] = '1';
				
			}
			// Mann|Frau usw. übersetzen
			$arPflicht['anrede_auswahl'] = __($arPflicht['anrede_auswahl'], 'wpsg');

			if (wpsg_isSizedArray($arPflicht['custom']))
			{

				foreach ($arPflicht['custom'] as $k => $v)
				{

					$arPflicht['custom'][$k]['name'] = __($arPflicht['custom'][$k]['name'], 'wpsg');
					$arPflicht['custom'][$k]['auswahl'] = __($arPflicht['custom'][$k]['auswahl'], 'wpsg');

				}

			}

			return $arPflicht;

		} // public function loadPflichtFeldDaten()

		/**
		 * Baut die Bestellnummer
		 */
		public function buildONR($o_id, $k_id, $knr)
		{

			if (!wpsg_isSizedInt($o_id)) {
				
				$o_id = $this->db->ImportQuery(WPSG_TBL_ORDER, [
					'cdate' => 'NOW()',
					'status' => wpsg_q(wpsg_ShopController::STATUS_UNVOLLSTAENDIG)
				]);
				
				$_SESSION['wpsg']['order_id'] = $o_id;
				
			}
			
			$onr_modul = "";
			if ($this->callMods('buildONR', array(&$k_id, &$o_id, &$onr_modul)) === false)
			{

				return $onr_modul;

			}

			$onr = $this->get_option('wpsg_order_start');

			if (trim($this->get_option('wpsg_format_onr')) == '')
			{
				$format = "%onr%";
			}
			else
			{
				$format = $this->get_option('wpsg_format_onr');
			}

			$arReplace = array(
					'/%Y%/' => date('Y'),
					'/%m%/' => date('m'),
					'/%d%/' => date('d'),
					'/%H%/' => date('H'),
					'/%i%/' => date('i'),
					'/%s%/' => date('s'),
					'/%oid%/' => $o_id,
					'/%onr%/' => $onr,
					'/%order_onr%/' => $onr,
					'/%kid%/' => $k_id,
					'/%knr%/' => $knr,
					'/%kunde_knr%/' => $knr
			);

			$strReturn = preg_replace(array_keys($arReplace), array_values($arReplace), $format);
			$strReturn = $this->replaceUniversalPlatzhalter($strReturn, $o_id, $k_id);

			// Bestellnummer Zähler hochzählen
			$this->update_option('wpsg_order_start', intval($this->get_option('wpsg_order_start')) + 1);

			return $strReturn;

		} // private function buildONR($o_id, $k_id)

		/**
		 * Erstellt die Kundennummer (Wird vom Basket bei Bestellung und vom Kundenmodul bei Registrierung genutzt)
		 */
		public function buildKNR($k_id)
		{

			$knr_modul = "";
			if ($this->callMods('buildKNR', array(&$k_id, &$knr_modul)) === false)
			{

				return $knr_modul;

			}

			$knr = $this->get_option('wpsg_customer_start');

			if (trim($this->get_option('wpsg_format_knr')) == '')
			{
				$format = "%knr%";
			}
			else
			{
				$format = $this->get_option('wpsg_format_knr');
			}

			$arReplace = array(
				'/%Y%/' => date('Y'),
				'/%m%/' => date('m'),
				'/%d%/' => date('d'),
				'/%H%/' => date('H'),
				'/%i%/' => date('i'),
				'/%s%/' => date('s'),
				'/%knr%/' => $knr,
				'/%kid%/' => $k_id,
				'/%kunde_id%/' => $k_id,
				'/%kunde_knr%/' => $knr,
				'/%id%/' => $k_id
			);

			$strReturn = preg_replace(array_keys($arReplace), array_values($arReplace), $format);
			$strReturn = $this->replaceUniversalPlatzhalter($strReturn, false, $k_id);

			// Kundennummer Zähler hochzählen
			$this->update_option('wpsg_customer_start', intval($this->get_option('wpsg_customer_start')) + 1);

			return $strReturn;

		} // private function buildKNR($k_id)

		/**
		 * Hilfsfunktion um die Versandarten zusammenzufügen und Kombinationen zu bilden
		 * Siehe test9() in wpsg_mod_test
		 */
		public function mergeShipping($arShipping1, $arShipping2)
		{

			/*
			wpsg_debug("merge");
			wpsg_debug($arShipping1);
			wpsg_debug($arShipping2);
			*/

			$arReturn = array();

			$arIntersect = array_intersect($arShipping1, $arShipping2);

			if (wpsg_isSizedArray($arIntersect) && (sizeof($arIntersect) > 1 || !array_search('130', $arIntersect)))
			//if (wpsg_isSizedArray($arIntersect))
			{

				$arReturn = $arIntersect;

			}
			else
			{

				// Jedes mit jedem prüfen
				foreach ($arShipping1 as $shipping1)
				{

					foreach ($arShipping2 as $shipping2)
					{

						if (is_array($shipping1))
						{

							if (!in_array($shipping2, $shipping1))
							{

								$s_merge = $shipping1;
								$s_merge[] = $shipping2;

								// Ob die Kombination im Rückgabe Array schon gefunden wurde
								$bFoundKombination = false;

								foreach ($arReturn as $r)
								{

									if (!wpsg_isSizedArray(array_diff($r, $s_merge)))
									{

										$bFoundKombination = true; break;

									}

								}

								if (!$bFoundKombination)
								{

									$arReturn[] = $s_merge;

								}

							}
							else
							{

								$bDrin = false;

								foreach ($arReturn as $r)
								{

									if (is_array($r) && !wpsg_isSizedArray(array_diff($r, $shipping1))) $bDrin = true;

								}

								if (!$bDrin) $arReturn[] = $shipping1;

							}

						}
						else
						{

							// Ob die Kombination im Rückgabe Array schon gefunden wurde
							$bFoundKombination = false;

							foreach ($arReturn as $r)
							{

								if (in_array($shipping1, $r) && in_array($shipping2, $r))
								{

									$bFoundKombination = true; break;

								}

							}

							if (!$bFoundKombination)
							{

								$arReturn[] = array($shipping1, $shipping2);

							}

						}

					}

				}

			}

			return $arReturn;

		} // private function mergeShipping($arShipping1, $arShipping2)

		/**
		 * Gibt den Namen der Versandart zurück
		 * Ist notwendig geworden, da die Versandarten zusammengefügt wurden.
		 * In $this->arShipping sind schon die zusammengefügten Pakete drin
		 */
		public function getShippingName($shipping_key)
		{

			if (array_key_exists($shipping_key, $this->arShipping))
			{

				return __($this->arShipping[$shipping_key]['name'], 'wpsg');

			}
			else
			{

				return wpsg_translate(__('Deaktivierte Versandart (#1#)', 'wpsg'), $shipping_key);

			}

		} // public function getShippingName($shipping_key)

		/**
		 * Gibt den Produktnamen als HTML zurück
		 * - Wenn Detailname, dann der Detailname
		 * - Box^4 wird in Box<span class="wpsg_upper">4</div>
		 * @param bool $detailname Wenn auf true, dann wird der Detailname verwendet wenn vorhanden
		 * @param int $product_id ID des Produktes
		 */
		public function getProductName($product_id, $detailname = false)
		{

			$oProduct = wpsg_product::getInstance($product_id);

			return $oProduct->getProductName($detailname);

		} // public function getProductName($product_id)

		/**
		 * Überprüft die vorhandenen Versandarten und entfernt Versandarten die im Warenkorb nicht erlaubt sind
		 */
		public function checkShippingAvailable() {
 
			$arShippingNew = array();

			// Damit es auch ohne View aus wpsg_basket.class beim prüfen der Versandarten aufgerufen werden kann
			if (!wpsg_isSizedArray($this->view['basket'])) $this->view['basket'] = $this->basket->toArray();

			if (wpsg_isSizedArray($this->view['basket']['produkte']) && !$this->bShippingMerged)
			{

				$arSystemShippingAllowed = array_keys($this->arShipping);
				$arProductShippingAllowd = $arSystemShippingAllowed;

				$bDontMerge130 = wpsg_isSizedInt($this->get_option('wpsg_mod_willcollect_dontMerge'));
				$bMerge130 = false;
				$bMoreThen130Allowed = true;

				// Schauen welche Versandarten übrig bleiben
				foreach ($this->view['basket']['produkte'] as $basket_product)
				{

					$oProduct = $this->cache->loadProductObject($this->getProduktID($basket_product['id']));

					if ($oProduct->hasLimitedShipping())
					{

						$arSingleProductShippingAllowed = array_intersect($arSystemShippingAllowed, $oProduct->getAllowedShipping());
						$arProductShippingAllowd = array_intersect($arProductShippingAllowd, $arSingleProductShippingAllowed);

						if (sizeof($arSingleProductShippingAllowed) === 1 && in_array('130', $arSingleProductShippingAllowed)) $bMoreThen130Allowed = false;
						else $bMoreThen130Allowed = $bMoreThen130Allowed && true;

					}
					else $bMoreThen130Allowed = true;

				}

				if ($bMoreThen130Allowed === true && (!wpsg_isSizedArray($arProductShippingAllowd) || (sizeof($arProductShippingAllowd) == 1 && in_array('130', $arProductShippingAllowd))))
				{

					// Es ist keine Versandart übrig geblieben oder nur Selbstabholung
					// Sofortüberweisung soll zusätzlich drin bleiben die anderen aber kombiniert werden
					// Sofortüberweisung rausnehmen
					$arSystemShippingAllowedMerged = $arSystemShippingAllowed;

					if (in_array('130', $arSystemShippingAllowed) && $bDontMerge130) unset($arSystemShippingAllowedMerged[array_search('130', $arSystemShippingAllowedMerged)]);

					foreach ($this->view['basket']['produkte'] as $basket_product)
					{

						$oProduct = $this->cache->loadProductObject($this->getProduktID($basket_product['id']));

						if ($oProduct->hasLimitedShipping())
						{

							$arSingleProductShippingAllowed = array_intersect($arSystemShippingAllowed, $oProduct->getAllowedShipping());

							if (in_array('130', $arSingleProductShippingAllowed) && $bDontMerge130)
							{

								unset($arSingleProductShippingAllowed[array_search('130', $arSingleProductShippingAllowed)]);
								if (wpsg_isSizedArray($arSingleProductShippingAllowed) > 0) $arSystemShippingAllowedMerged = $this->mergeShipping($arSystemShippingAllowedMerged, $arSingleProductShippingAllowed);
								else
								{

									//$arSystemShippingAllowedMerged = array();
									$bMerge130 = true;

								}

							}
							else
							{

								$arSystemShippingAllowedMerged = $this->mergeShipping($arSystemShippingAllowedMerged, $arSingleProductShippingAllowed);

							}

						}

					}

				}
				else
				{

					$arSystemShippingAllowedMerged = $arProductShippingAllowd;

				}

				// Selbstabholung wieder mit rein
				if ($bDontMerge130 && in_array('130', $arProductShippingAllowd) && !in_array('130', $arSystemShippingAllowedMerged)) $arSystemShippingAllowedMerged[] = '130';

				if ($bMerge130 && wpsg_isSizedArray($arSystemShippingAllowedMerged[0]) > 0 && !in_array('130', $arSystemShippingAllowedMerged))
				{

					$arSystemShippingAllowedMerged[0][] = '130';

				}

				//$arSystemShippingAllowedMerged = wpsg_trim($arSystemShippingAllowedMerged);

				foreach ($arSystemShippingAllowedMerged as $shipping)
				{

					if (is_array($shipping))
					{

						asort($shipping, SORT_STRING);

						$subKey = implode('-', $shipping);

						$arShippingNew[$subKey] = array(
							'id' => $subKey,
							'sub' => array(),
							'price' => 0
						);

						foreach ($shipping as $subShipping) {

							$sub = $this->arShipping[$subShipping];

							$arShippingNew[$subKey]['name'] = implode(' + ', wpsg_trim(array_merge(array($sub['name']), (array)explode(',', $arShippingNew[$subKey]['name']))));
							$arShippingNew[$subKey]['hint'] = implode('<br /><br />', wpsg_trim(array_merge(array($sub['hint']), (array)explode(',', $arShippingNew[$subKey]['hint']))));
							
							// w-50
							$arPriceSub = explode('-', $sub['price']);
							
							//if (isset($arShippingNew[$subKey]['price'])) $arShippingNew[$subKey]['price'] = $arShippingNew[$subKey]['price'] + $arPriceSub[1];
							//else if (isset($sub['price'])) $arShippingNew[$subKey]['price'] = $arPriceSub[1];
							 
							//if (isset($arShippingNew[$subKey]['price'])) $arShippingNew[$subKey]['price'] = $arShippingNew[$subKey]['price'] + $sub['price'];
							//else if (isset($sub['price'])) $arShippingNew[$subKey]['price'] = $sub['price'];
							
							$arShippingNew[$subKey]['sub'][$subShipping] = $sub;

						}

					}
					else
					{

						$arShippingNew[$shipping] = $this->arShipping[$shipping];

					}

				}
				
				$this->arShipping = $arShippingNew;
				$this->bShippingMerged = true;

			}

			// Unschöne Warnungen verhindern, sollte hier der Array null sein
			if (!wpsg_isSizedArray($this->arShipping)) $this->arShipping = array();

			$this->callMods('checkShippingAvailable', array(&$this->arShipping));

			if (isset($_SESSION['wpsg']['checkout']['shipping'])) {
								
				if (!array_key_exists($_SESSION['wpsg']['checkout']['shipping'], $this->arShipping)) {
						
					$_SESSION['wpsg']['checkout']['shipping'] = array_keys($this->arShipping)[0];
						
				}
												
			}
			
		} // public function checkShippingAvailable()

		/**
		 * Überprüft die vorhandenen Zahlungsarten und entfernt Zahlungsarten die im Warenkorb nicht elaubt sind
		 */
		public function checkPaymentAvailable() {

			$sum_basket = \wpsg\wpsg_calculation::getSessionCalculation()->getSum(WPSG_NETTO);
			 
			if ($sum_basket <= 0) {
				
				// Neue ab 13.01.2020
				// Wenn <= 0 Euro, wird kostenlos als Zahlungsart verwendet
				// Alle anderen Zahlungsarten fallen raus
				
				foreach ($this->arPayment as $k => $v) {
					
					if ($k !== 0) unset($this->arPayment[$k]);
					
				}
				
			} else {
			
				unset($this->arPayment[0]);
				
				if (wpsg_isSizedArray($this->view['basket']['produkte'])) {
	
					foreach ($this->view['basket']['produkte'] as $basket_product) {
	
						$oProduct = $this->cache->loadProductObject($this->getProduktID($basket_product['id']));
	
						if ($oProduct->hasLimitedPayment()) {
	
							$arPaymentAllowed = $oProduct->getAllowedPayment();
	
							foreach ($this->arPayment as $payment_key => $payment) {
	
								if (!in_array($payment['id'], $arPaymentAllowed)) {
	
									unset($this->arPayment[$payment_key]);
	
								}
	
							}
	
						}
	
					}
	
				}
	
				// Unschöne Warnungen verhindern, sollte hier der Array null sein
				if (!wpsg_isSizedArray($this->arPayment)) $this->arPayment = array();
	
				$this->callMods('checkPaymentAvailable', array(&$this->arPayment, &$this->view['basket']));

			}
				
		} // public function checkPaymentAvailable()

		/**
		* Gibt den Namen der Zahlungsart zurück (Eingebaut für CrefoPay)
		* Standardmäßig wird der Name aus $this->shop->arPayment genommen
		*
		* @param Integer $payment_type Key der Zahlungsart
		* @param Integer $order_id BestellID
		*/
		public function getPaymentName($payment_type, $order_id)
		{

			$payment_name = @$this->arPayment[$payment_type]['name'];

			$this->callMods('getPaymentName', array($payment_type, $order_id, &$payment_name));

			return $payment_name;

		} // public function getPaymentName($payment_type, $order_id)

		/**
		 * Ermittelt ob im Backend Netto oder Brutto Preise angezeigt und definiert werden sollen
		 */
		public function getBackendTaxview()
		{

			return $this->get_option('wpsg_preisangaben');

		} // public function getBackendTaxview()

		/**
		 * Ermittelt ob im Frontend Netto oder Brutto Preise angezeigt werden sollen
		 */
		public function getFrontendTaxview()
		{

			// Kleinunternehmer sehen immer im Frontend NETTO ab 17.02.2016 egal was bei Frontend/Brutto eingestellt ist
			if ($this->get_option('wpsg_kleinunternehmer') == '1') return WPSG_NETTO;

			if (isset($_SESSION['wpsg']['customertype']) && $_SESSION['wpsg']['customertype'] == 0)
			{

				// Hier hat der Kunde die Auswahl "Firmenkunde" getroffen --> Netto anzeigen
				return WPSG_NETTO;

			}

			$nFrontendTaxview = $this->get_option('wpsg_preisangaben_frontend');

			if ($this->hasMod('wpsg_mod_kundenverwaltung'))
			{

				$customer_group_id = $this->callMod('wpsg_mod_kundenverwaltung', 'getCustomerGroup');

				if (wpsg_isSizedInt($customer_group_id))
				{

					$oCustomerGroup = $this->callMod('wpsg_mod_kundenverwaltung', 'getCustomerGroupObject', array($customer_group_id));

					if ($oCustomerGroup->calculation >= 0)
					{

						$nFrontendTaxview = $oCustomerGroup->calculation;

					}

				}

			}

			return $nFrontendTaxview;

		} // public function getFrontendTaxview()
		
		/**
		 * @return wpsg_ShopController
		 */
		public static function getShop() {
			
			return $GLOBALS['wpsg_sc'];
			
		}

	} // class ShopController extends SystemController
