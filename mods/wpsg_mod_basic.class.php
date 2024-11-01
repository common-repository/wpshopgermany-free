<?php

	/**
	 * Basisklasse für die Module
	 */
	class wpsg_mod_basic 
	{
		
		/** Der Lizenzlevel des Moduls */
		var $lizenz = 0;
		
		/** URL zu der Hilfe Seite */
		var $helpURL = '';
		
		/** Array in dem die Reihenfolge der Module bestimmt wird */
		var $arIDs = array( 
		    -1      => 'wpsg_mod_apiproduct',
			1 		=> 'wpsg_mod_prepayment', // Vorkasse
			2 		=> 'wpsg_mod_paypal', // Paypal	
			3		=> 'wpsg_mod_debitpayment',		
			5 		=> 'wpsg_mod_su', //Sofortüberweisung
			6 		=> 'wpsg_mod_versandarten', // Versandarten
			7		=> 'wpsg_mod_skrill', // Skrill
			9 		=> 'wpsg_mod_productgroups', // Produktgruppen
			10      => 'wpsg_mod_paypalapi', // Paypal Plus			
			11		=> 'wpsg_mod_shs',
			12		=> 'wpsg_mod_wirecard', // Wirecard
			13 		=> 'wpsg_mod_si', // SofortIdent
			14      => 'wpsg_mod_test',
			15 		=> 'wpsg_mod_produktartikel', // ProduktArtikel
			16		=> 'wpsg_mod_creditcard', // Kreditkarte
			17		=> 'wpsg_mod_targo', // Targobank
			18 		=> 'wpsg_mod_paypalratepay', // PayPal Ratenzahlung
			20 		=> 'wpsg_mod_autodebit', 
			30		=> 'wpsg_mod_productindex', // Produktübersicht
			70		=> 'wpsg_mod_auftragsbestaetigung', // Auftragsbestätigung			
			85 		=> 'wpsg_mod_produktattribute', // Produktattribute
			90 		=> 'wpsg_mod_stock', // Lagerbestand
            91 		=> 'wpsg_mod_productvariants', // Vor Weight, da Gewicht schon den Standard Key brauch
			95 		=> 'wpsg_mod_weight', // Produktgewicht
			96 		=> 'wpsg_mod_userpayment', // Zahlungsarten			
			97	 	=> 'wpsg_mod_scaleprice', // Vor Varianten
			98 		=> 'wpsg_mod_varianten', // Vor Rabatt (100 nicht vergeben = Core)			 
			101     => 'wpsg_mod_downloadplus',	// 100 = Core reserviert	
			105 	=> 'wpsg_mod_basketteaser', // Warenkorbteaser
			125		=> 'wpsg_mod_protectedshops', // Protected Shops
			130 	=> 'wpsg_mod_willcollect',
			135 	=> 'wpsg_mod_shippingadress', // Lieferadresse			
			160		=> 'wpsg_mod_gutschein', // Gutschein
			161		=> 'wpsg_mod_voucherproduct', // Zubehörprodukt
			300 	=> 'wpsg_mod_export', // Exportprofile
			440 	=> 'wpsg_mod_statistics', // Statistiken
			450 	=> 'wpsg_mod_piwik', // Piwik
			500 	=> 'wpsg_mod_freeshipping', // Versandkostenfrei
			599		=> 'wpsg_mod_newsletter', // wpNewsletterGermany
			600		=> 'wpsg_mod_nlsatolo', // Satollo Newsletter Integration			
			601		=> 'wpsg_mod_downloadprodukte', // Downloadprodukte
			602		=> 'wpsg_mod_videodownload', // Videodownload			
			700		=> 'wpsg_mod_discount', // Rabatt muss nach den Varianten kommen, damit Rabatt auf Variantenpreis gerechnet wird
			701		=> 'wpsg_mod_fuellmenge', // Nach Rabatt
			810 	=> 'wpsg_moc_cab',
			815 	=> 'wpsg_mod_billsafe', // Billsafe
			816		=> 'wpsg_mod_klarna', // Klarna
			900		=> 'wpsg_mod_ordervars', // Bestellvariable
			950 	=> 'wpsg_mod_productvars', // Produktvariable
			951		=> 'wpsg_mod_ordercondition', // Bestellbedingungen
			1000	=> 'wpsg_mod_kundenkontakt', // Kundenkontakt
			1001    => 'wpsg_mod_customerbudget', // Kundenbudget
			1600 	=> 'wpsg_mod_relatedproducts', //Zubehörprodukte
			1610	=> 'wpsg_mod_bankalignment', // Bankenabgleich
			1630	=> 'wpsg_mod_abo', // Aboprodukte			
			1700 	=> 'wpsg_mod_onepagecheckout', // Einseitencheckout
			1750	=> 'wpsg_mod_micropayment', // Micropayment
			1800	=> 'wpsg_mod_haendlerbund', // Händlerbund
			1850	=> 'wpsg_mod_productfilter', // Produktfilter
			1900	=> 'wpsg_mod_legaltexts', // Rechtstexte
			1950 	=> 'wpsg_mod_topseller', // Topseller
			2000 	=> 'wpsg_mod_deliverynote', // Lieferschein			
			2050    => 'wpsg_mod_funding', // Crowdfunding 
			2100	=> 'wpsg_mod_productpackage', // Produktpaket
			2200    => 'wpsg_mod_anschreiben', // Anschreiben
			3000 	=> 'wpsg_mod_spconditions',
			3050	=> 'wpsg_mod_giropay', /* Giropay*/
			3060	=> 'wpsg_mod_securepay', /* SecurePay*/
			3061	=> 'wpsg_mod_icp', /* Kopie von Securepay */
			3070	=> 'wpsg_mod_minrequest', /* Mindestbestellwert */
			3080	=> 'wpsg_mod_minquantity', /* Mindestbestellmenge */			
			3090	=> 'wpsg_mod_customergroup', /* Kundengruppen */
			3100	=> 'wpsg_mod_kundenverwaltung', /* Kundenverwaltung */ 
			3110    => 'wpsg_mod_customernr', /* Anpassung für Länderspezifische Kundennummern */
			3120    => 'wpsg_mod_flexipay', /* Flexipay */			
			3130	=> 'wpsg_mod_packagetracking', /* Paketverfolgung */
			3140	=> 'wpsg_mod_amazon', /* Amazon Payment */
			4000	=> 'wpsg_mod_crefopay', /* Sollte die letzte Zahlungsart bleiben, da sie die anderen ausblendet */
			3150	=> 'wpsg_mod_request', // Anfrageprodukt 
			3250	=> 'wpsg_mod_addressvalidation', // Adress-Validierung 
			5000	=> 'wpsg_mod_trustedshops', // Sollte nach den Zahlungsanbietern kommen wegen order_done
			5100    => 'wpsg_mod_productview', // Produktansicht 
			5300	=> 'wpsg_mod_converter', // Konverter von wpsg3 auf wpsg4
			5400 	=> 'wpsg_mod_surfaceproduct', // Druckerei	
            5500    => 'wpsg_mod_datainformation' // Datenauskunft	
		);
		
		/** Wenn true, dann speichert das Modul seine Daten alle inline (Nur Optische Funktion) */
		var $inline = false;
		
		/** @var wpsg_ShopController */
		var $shop = false;
		
		/**
		 * Erstellt ein neues Modul
		 */
		public function __construct() 
		{
			
			$this->shop = $GLOBALS['wpsg_sc'];
			$this->db = $GLOBALS['wpsg_db'];
			
		} // public function __construct() 
		
		/**
		 * Um die Zugriffe auf die Controllerfunktion zu vereinfachen
		 */
		public function render($file, $out = true)
		{
			
			$this->shop->render($file, $out);
			
		} // public function render()
		
		/** Initiiert das Modul / Wird nur aufgerufen wenn das Modul aktiv ist */
		public function init() { }
		
		/** Aufruf um eventuell das Erstellen der Kundennummer zu beeinflussen */
		public function buildKNR(&$customer_id, &$knr_modul) { }
		
		/** Aufruf um eventuell das Erstellen der Bestellnummer zu beeinflussen */
		public function buildONR(&$customer_id, &$order_id, &$onr_modul) { }
		
		/** Ermittlung des ProductKeys aus einem Request Array */
		public function getProductKeyFromRequest(&$product_key, $product_id, $form_data) {}
		
		/** */
		public function wpsg_admin_init() {}
						
		/** Wird bei der Darstellung der Bibliotheken / Includes aufgerufen */
		public function admin_includes() { }

		/** Ermöglicht es Debuginformationen in der wpAdminBar anzuzeigen, wenn der Debug Modus aktiv ist */
		public function admin_debugInfo() { }
		
		/** Wird beim speichern der Bibliothekes / Includes Seite aufgerufen */
		public function admin_includes_save() { }
		
		/** Wird beim Installieren des Moduls aufgerufen */
		public function install() { } // public function install()
		
		/** Ajax Anfragen im Backend */
		public function be_ajax() { }
		
		/** Für das hinzufügen von Buttons zum RTE */
		public function tinymce_plugin(&$plugin_array) { }
		
		/** Button für den TinyMCE */
		public function tinymce_button(&$buttons) { }
		
		/** Integriert sich in die Bestellzusammenfassung (overview.phtml) nach der AGB */
		public function overview_top(&$arBasket) { }
		
		/** Hier kann in einem Modul verhindert werden, dass eine Bestellung abgeschlossen wird (Ein Modul muss -2 zurückgeben) */
		public function canFinishOrder($temp_order_id) { }
		
		/** Wird aufgerufen anstelle des Buttons "Zahlung akzeptieren" wenn canFinishOrder false ergibt */
		public function canNotOrder($temp_order_id) { } 
		
		/** Wird aufgerufen wenn die Seiten im Backend hinzugefügt werden */
		public function wpsg_add_pages($default_page) { }
		
		/** Wird in der indexAction des AdminKontrollers aufgerufen */
		public function admin_index($ac) { }
		
		/** Wird auf der Einstellungsseite "Darstellung" in der Konfiguration aufgerufen */
		public function admin_presentation() { }
		
		/** Wird beim speichern der Einstellungsseite "Darstellung" in der Konfiguration aufgerufen */
		public function admin_presentation_submit() { }
		
		/** Wird im Backend bei der E-Mail Konfiguration aufgerufen (Ausgabe) */
		public function admin_emailconf() { }
				
		/** Wird beim Aufruf einer URL Aktion in den Modulen aufgerufen */
		public function notifyURL(&$url, &$produkt_key, &$menge, &$order_id, &$typ, &$arSend) { }
		
		/** Wird beim speichern der E-Mail Konfiguration aufgerufen */
		public function admin_emailconf_save() { }
		
		/** Wird vom AdminController (access.phtml) beim bearbeiten der Zugriffsbeschränkungen aufgerufen */
		public function admin_setcapabilities() { } 
		 
		/** Wird in die Kundenmail nach der Berechnung eingebaut */
		public function mail_aftercalculation(&$order_id) { }
		
		/** Wird in der Mail für die Statusänderung aufgerufen */
		public function mail_status(&$order, &$customer) { }
		
		public function mail_order_end(&$arCalculation, $html) { }
		
		/** Wird in der Kundenmail nach den AGB eingebaut */
		public function kundenmail_afteragb(&$order_id) { }
		
		/** Wird bei der Calculation aufgerufen @var \wpsg\wpsg_calculation $oCalculation */		
		public function calculation_fromSession(&$oCalculation, $product_done, $payship_done) { }
		
		public function calculation_saveProduct(&$oCalculation, $calc_product, &$db_product_data, $finish_order) { }
	
		/** @var \wpsg\wpsg_calculation $oCalculation */
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) { }
				
		/**
		 * Gibt den Namen der Zahlungsart zurück (Eingebaut für CrefoPay)
		 * Standardmäßig wird der Name aus $this->shop->arPayment genommen
		 * 
		 * @param Integer $payment_type Key der Zahlungsart
		 * @param Integer $order_id BestellID
		 * @param String $payment_name Der Name der Zahlungsart
		 */
		public function getPaymentName($payment_type, $order_id, &$payment_name) { return @$this->shop->arPayment[$payment_type]['name']; } // public function getPaymentName($payment_type, $order_id)
		
		/** Wird innerhalb der Kundengruppenverwaltung beim bearbeiten einer Kundengruppe aufgerufen */
		public function customergroup_edit(&$oCustomergroup) { }
		
		/** Wird nach dem Speichern der Kundengruppe innerhalb der Kundengruppenverwaltung aufgerufen */
		public function customergroup_save(&$customergroup_id) { }
		
		/**
		 * Wird aufgerufen wenn die allgemeinen Backend Fehler geschrieben werden
		 * Hier soll auf fehlkonfigurierte Module hingewiesen werden
		 */
		public function checkGeneralBackendError() { }
		
		/** Wird bei korrekt eingerichtetem Cron Job periodisch aufgerufen */
		public function cron() { }
		
		/** 
		 * Verarbeutet Ajax Anfragen innerhalb der Produktverwaltung
		 * Die URLs sehen dann etwa so aus: <?php echo WPSG_URL_WP; ?>wp-admin/admin.php?page=wpsg-Produkt&action=ajax&mod=wpsg_mod_productvariants&cmd=produktbilder_liste 
		 */
		public function produkt_ajax() { }
		
		/** Integriert sich in den Kopf der Bestellverwaltung */
		public function produkt_index_head() { }

		/**
		 * Wird in der Produktverwaltung im Menü eines Produktes aufgerufen
		 */
		public function produkt_index_editmenu(&$pData) { }

		/** Integriert eine Reiter in die Bestellüberischt */
		public function order_index_tab(&$arTabs) { }
		
		/** Integriert sich in die Bestellverwaltung nach dem Suchformular */
		public function order_index_aftersearch() { }
		
		/** Wird beim bearbeiten eines Produktes aufgerufen, hier kann der Array modifiziert werden, der an das view übergeben wird */
		public function produkt_edit(&$produkt_data) { }
		
		/** Wird beim kopieren eines Produktes aufgerufen */
		public function produkt_copy(&$produkt_id, &$copy_id) { }
		
		/** DEPRECATED und INACTIV:Soll durch product_addedit_content ersetzt werden
		 * Wird beim anlegen eines neuen Produktes aufgerufen */
		public function produkt_add(&$produkt_data) { }
		
		/**
		 * Wird aufgerufen wenn in der Produktverwaltung ein Produkt das erste mal übersetzt wird
		 */
		public function produkt_createTranslation(&$produkt_id, &$produkt_translate_id) { }
		
		/**
		 * Wird beim löschen eines Produktes im Backend aufgerufen
		 */
		public function produkt_del($produkt_id) { }
		
		/** 
		 * DEPRECATED:Soll durch product_addedit_content ersetzt werden
		 * Wird vom Bearbeiten/Anlegen Template der Produktverwaltung aufgerufen und ermöglicht es in der Seitenleiste neue Elemente zu platzieren 
		 * */
		public function produkt_edit_sidebar(&$produkt_data) { }
				
		/** Wird in der Postbox Allgmeine beim Bearbeiten eines Produktes aufgerufen */
		public function produkt_edit_allgemein(&$produkt_data) { }
		
		/**
		 * DEPRECATED:Soll durch product_addedit_content ersetzt werden 
		 * Wird beim bearbeiten eines Produktes im Content Teil aufgerufen
		 * Hier können z.B. weitere PostBoxen installiert werden 
		 */
		public function produkt_edit_content(&$produkt_data) { }
		
		/**
		 * @param wpsg_product $oProduct
		 * @param double $price_netto
		 * @param double $price_brutto
		 * @param string $product_key		 * 
		 * @param int Menge für Staffelpreise wenn false, dann aus der Session
		 * @param double $weight Gewicht des Produktes im Warenkob, bei false wird anhand der Session ermittelt
		 */
		public function product_getPrice(&$oProduct, &$price_netto, &$price_brutto, $product_key, $amount, $weight) { }
		
		/**
		 * Integration von Modulen in die Produktverwaltung 
		 * @param Array $product_content Hier ist die Konfiguration der Reiter drin. { "title":"", "content":"" } 
		 * @param Array $product_data Array mit den Produktdaten aus der DB
		 */
		public function product_addedit_content(&$product_content, &$product_data) { }
		
		/** Wird nach dem speichern des Produktes aus der saveAction des Produktcontrollers aufgerufen */
		public function produkt_save(&$produkt_id) { }
				
		/**
		 * Wie produkt_save wird aber beim speichern einer Übersetzung aufgerufen
		 */
		public function produkt_save_translation(&$produkt_id, &$trans_id) { }
		
		/**
		 * Wird vor dem Speichern eines Produkts aufgerufen 
		 */
		public function produkt_save_before(&$produkt_data) { }
		
		/** Wird im Produkttemplate eingebunden oberhalb der Ausgaben des Produkttemplates (Nach dem Produktnamen) */
		public function product_top_afterheadline($product_id, $template_index) { }
				
		/** Wird im Produkttemplate eingebunden oberhalb der Ausgaben des Produkttemplates */
		public function product_top($product_id, $template_index) { }
		
		/** Wird im Produkttemplate eingebunden im unteren Bereich nach der Beschreibung */
		public function product_bottom(&$produkt_id, $template_index) { }
 		
		/** Wird über dem Warenkorb eingebunden (Rabatthinweis) */
		public function basket_top() { } 
		
		/** Wird aufgerufen wenn der Warenkorb über einen Modulbutton abgeschickt wurde */
		public function basket_submitSuccess() { }
		
		/** Wird aus dem BasketController aufgerufen wenn die Daten (Versandart und Zahlungsart) gesetzt werden */
		public function setBasketData() { } 
		
		/** Prüft die Daten, die im Warenkorb erfasst sind auf Validität */
		public function basket_check() { } // public function basket_check()

        /** Wird aufgerufen wenn ein Produkt aus dem Warenkorb entfernt wird */
        public function basket_removeProduktFromSession($product_index) { } // public function basket_removeProduktFromSession($product_index)
        
		/** Wird nach dem Formular im basket.phtml aufgerufen */
		public function basket_after(&$basket_view) { }
		
		/**
		 * Wird für Ajax Anfragen in der Produktverwaltung verwendet
		 */
		public function order_ajax() { }
		
		/**
		 * Wird in der Bestellansicht aufgerufen um im linken Bereich neue Elemente zu platzieren
		 */
		public function order_view_content($order_id) { }
		
		/** Wird in das Formular für die Statusänderung innerhalb der Bestellung integriert */
		public function order_view_switchStatus(&$order_id) { }
		
		/** Wird in der Bestellansicht aufgerufen nach der Zahlungsart */
		public function order_view_afterpayment(&$order_id) { }
		
		/**
		 * Wird bei der Änderung des Bestellstatus aufgerufen
		 */
		public function setOrderStatus($order_id, $status_id, $inform) { }
		
		/**
		 * Wird in der Bestellverwaltung aufgerufen um im rechten Bereich neue Elemente zu platzieren
		 */
		public function order_view_sidebar(&$order_id) { }
		
		public function order_view($order_id, &$arSidebarArray) { }

		/** Wird bei der Darstellung einer Produktzeile in der Bestellverwaltung aufgerufen */
		public function order_view_row(&$p, $i) { }
		
		/** Wird beim laden des Produktes aufgerufen */
		public function loadProduktArray(&$produkt_data) { }
				
		/** Gibt die Produkt Artikelnummer anhand des Produktschlüssels zurück */
		public function getProductAnr($product_key, &$anr) { }
		
		/** Gibt die URL für ein Produkt zurück */
		public function getProduktlink($produkt_id, &$url, $language_code = false) { }
		
		/** Wird vom Backend aufgerufen wenn die Einstellungen bearbeitet werden */
		public function settings_edit() { }
		
		/** Wird vom Backend aufgerufen wenn die Einstellungen bearbeitet werden (Nach dem Formular) */
		public function settings_edit_afterform() { }
		
		/** Wird vom Backend aufgerufen wenn die Einstellungen gespeichert werden sollen */
		public function settings_save() { }

        /** Wird beim deinstallieren aufgerufen wenn die Seiten gelöscht werden sollen, die der Shop angelegt hat */
        public function wpsg_deinstall_sites() { } // public function wpsg_deinstall_sites()

		/** Wird während der Anzeige der Produkte im Frontend aufgerufen um das Template für die Anzeige zu verändern. */
		public function renderProdukt_templateSelect(&$produkt_data, &$template_file) { }
				 
		/** Wird bei der Produktausgabe nach dem Formular aufgerufen */
		public function renderProdukt_afterForm(&$produkt_data, &$html) { }
		
		/** Wird beim initiieren der Module aufgerufen und erweiterte die möglichen Versandoptionen */
		public function addShipping(&$arShipping, $va_active = false) { }
		
		/** Wird beim initiieren der Module aufgerufen und erweiterte die möglichen Bezahloptionen */
		public function addPayment(&$arPayment) { }
		
		/** Wird nach einer erfolgreichen Bestellung aufgerufen. Wird auch bei reload der Finish Seite ausgeführt */
		public function order_done(&$order_id, &$done_view) { } 
		 		
		/**
		 * Wird vor dem Einfügen eines Produktes in den Warenkorb aufgerufen
		 * z.B. vom Variantenmodul um die ProduktID im Request zu modifizieren
		 */
		public function basket_preInsert() { }
		
		/** Wird vor dem vervielfältigen aufgerufen */
		public function basket_preMultiple($product_index) { }
		
		/**
		 * Wird vor dem Aktualisieren des Warenkorbes aufgerufen
		 */
		public function basket_preUpdate() { }
		
		/* Wird nach dem entfernen eines Produktes aus dem Warenkorb aufgerufen */ 
		public function basket_afterRemove() { }
		
		/** Integriert sich in die Länderverwaltung beim bearbeiten/anlegen eines Landes */
		public function laender_edit() {}
		
		/**
		 * Wird nach der Aktualisierung des Warenkorbs aufgerufen
		 */
		public function basket_afterUpdate(&$bError) { }
		
		/**
		 * Wird als letzte Zeile innerhalb des Warenkorbs und OnepageCheckout aufgerufen
		 */
		public function basket_row_end(&$basket_view) { }
		
		/**
		 * Wird innerhalb des Warenkorbes (basket.phtml) aufgerufen, innerhalb des Formulars vor den Absenden Buttons
		 */
		public function basket_inner_prebutton(&$basket_view) { }
		
		/**
		 * Wird aufgerufen wenn ein Produkt im Warenkorb aktualisiert wird
		 */
		public function basket_updateProduktFromSession(&$product_key, &$stock) { }
		
		/**
		 * Wird aufgerufen wenn ein neues Produkt aus dem Request in die Session aufgenommen werden soll
		 */
		public function basket_produkttosession($produkt_key, &$menge, &$ses_data) { }
		
		/**
		 * Wird aufgerufen wenn ein bestehendes Produkt aktualsiert werden soll
		 */
		public function basket_produktupdatesession($produkt_key, $menge, &$ses_data) { }
		
		/**
		 * Wird vor dem Anzeigen des Checkouts aufgerufen um mögliche Werte aus dem Basket Request zu verarbeiten
		 */
		public function basket_checkoutAction(&$basketController) { }
		
		/**
		 * Wird beim erstellen des BasketArrays aufgerufen, hier werden Preise und Namen des Produktes aus dem ProduktKey im Warenkorb geladen
		 */
		public function basket_toArray(&$produkt, $backend = false, $noMwSt = false) { }
				 
		/**
		 * Wird von der Basket Klasse beim erstellen des Arrays kurz vor schluß aufgerufen
		 */
		public function basket_toArray_final(&$basket, &$arBasket) { }
		
		/** Wird von der Basket Klasse beim erstellen des Array kurz vor den Versand / Zahlungsarten aufgerufen */
		public function basket_toArray_preshippayment(&$basket, &$arBasket) { }
		
		/**
		 * Wird nach dem Rendern des Produktes im Basket aufgerufen (aus basket.phtml)
		 */
		public function basket_row(&$p, $i) { }
		
		/**
		 * Wird nach dem Rendern des Produktes im Basket aufgerufen (aus basket.phtml) Nach den Produkten, nur einmal
		 */
		public function basket_row_afterproducts(&$p, $i) { }
		
		/** Wird im Checkout und OnePageCheckout aufgerufen innerhalb der Kundendaten */
		public function checkout_customer_inner() { }
		
		/** Für PayPal API, wenn es die Auswahl der Zahlungsarten übernimmt */
		public function checkout_handlePayment() { }
		
		/**
		 * Wird im Checkout (checkout.phtml) innerhalb des Formulars vor den Buttons aufgerufen 
		 */
		public function checkout_inner_prebutton(&$checkout_view) { }
		
		/** Wird im Checkout2 (checkout2.phtml) innerhalb des Formulars vor den Buttons aufgerufen */
		public function checkout2_inner_prebutton(&$checkout_view) { }
		
		/**
		 * Wird bei der Zusammenfassung der Bestellung aufgerufen (overview.phtml)
		 */
		public function overview_row(&$p, $i) { }
		
		/**
		 * Wird als letzte Zeile innerhalb der Bestellzusammenfassung aufgerufen
		 */
		public function overview_row_end(&$overview_view) { }
		
		/**
		 * Wird innerhalb der Bestellzusammenfassung aufgerufen, vor den Buttons
		 */
		public function overview_inner_prebutton(&$basket_view) { }
		
		/**
		 * Überprüft den Warenkorb
		 * Wird beim Klicken von "Zur Kasse" im Warenkorb aufgerufen und leitet im Fehlerfall ($error == true) zum Warenkorb weiter
		 * @param \Boolean $error
		 * @param \Array $arBasket
		 */
		public function checkBasket(&$error) { }
		
		/** Wird von der wpsg_basket Klasse beim prüfen des Checkouts aufgerufen */
		public function checkCheckout(&$state, &$error, &$arCheckout) { }
		
		/** Wird beim speichern der Daten in die Session aufgerufen */
		public function doCheckout() { }
		
		/** Wird im Template für die Mail aufgerufen um die Darstellung der Produkte zu erweitern */
		public function mail_row($index, $produkt) { }
		
		/** Erlaubt das Hinzufügen von Zahlungslinks etc. in den Mails */
		public function mail_payment() { }
		
		/** Erlaubt das Hinzufügen von Hinweisen zur Versandart etc. in der Mail */
		public function mail_shipping() { }
		
		public function sendMail($mail_key, $order_id, $customer_id, &$empfaenger, &$subject, &$mail_text_send, &$headers, &$anhang) { }
		
		/** Wird beim speichern eines Kunden während der Bestellung aufgerufen */
		public function basket_save_kunde(&$data, &$checkout) { }
				
		/** Wird beim löschen der Session nach der Bestellung aufgerufen */
		public function clearSession() { }
				
		/**
		 * Wird nach dem erfolgreichen Speichern der Bestellung mit der Bestell- und Kundenid aufgerufen
		 */
		public function basket_save_done(&$order_id, &$kunde_id, &$oBasket) { }
		
		/** 
		 * Wie basket_save_done, bekommt aber anstatt des BasketObjects den Basket Array übergeben
		 */
		public function basket_save_done_array(&$order_id, &$kunde_id, &$arBasket) { }
		
		/**
		 * Wird vor dem löschen des Kunden aufgerufen
		 */
		public function customer_delete_pre(&$customer_id, $delete) { }
		
		/**
		 * Wird aufgerufen wenn das Kundenpasswort geändert wurde
		 */
		public function customer_updatePwd(&$customer_id, &$customer_pwd) { } // public function customer_updatePwd(&$customer_id, &$customer_pwd)
		 
		/**
		 * Wird nach dem Ändern der Kundendaten aufgerufen
		 */
		public function customer_updated(&$customer_id) { }
		
		/**
		 * Wird nach dem Anlegen eines Kunden aufgerufen
		 */
		public function customer_created(&$customer_id, &$pwd) { }
		
		/**
		 * Überprüft die Versandarten ob sie bei dem übergebenen Warenkorb möglich sind
		 * Sollte dies nicht möglich sein wird die Versandart entfernt
		 * 
		 * Erweitert außrdem den Array um den genauen preis
		 * 
		 *
                    [name] => Vorkasse
                    [preis] => 119 // Grundeinstellung des Preises unabhängig vom Warenkorb
                    [mwst] => 1 // ID der MwSt Klasse
                    [mwst_value] => 19.00 // MwSt in %
                    [preis_calc] => 100 // Berechneter Preis in abhängigkeit vom Warenkorb
                    [mwst_calc] => 19.00 // MwSt in Euro
		 * 
		 */
		public function checkShippingAvailable(&$arShipping) { }

		/**
		 * Überprüft die Zahlungsarten ob sie bei dem übergebenen Warenkorb möglich sind
		 * Sollte dies nicht möglich sein wird die Zahlungsart entfernt
		 */
		public function checkPaymentAvailable(&$arPayment, &$arBasket) { } 
		
		/** Berechnet die Kosten für den Versand innerhalb des Warenkorbes */
		public function calcShipping(&$arBasket, $shipping_key) { }
				
		/** 
		 * Wird vor der Ausgabe des Contents aufgerufen
		 * DEPRECATED: Durch template_redirect ersetzt, da ich da mehr Funktionen nutzen kann
		 */
		public function wp_loaded() { }
		
		/** Wird vor der Ausgabe des Content aufgerufen. get_permalink ist möglich */
		public function template_redirect() { }
		
		/**
		 * Ermöglicht die Filterung der Exceprt Ausgabe
		 */
		public function the_excerpt(&$content) { } // public function the_excerpt(&$content)
		
		/** Wird beim seitenaufruf im Frontend aufgerufen Sollte -2 zurückgeben, wenn ein Modul die Ausgabe übernimmt (Vor allem wenn es über die Basket ID geht) */
		public function content_filter(&$content) { }
				
		/** Wird von Wordpress vor dem Ausführen des Querys aufgerufen */
		public function pre_get_posts(&$query) { }
		
		/** Wird aufgerufen wenn das Plugin geladen wird. Hier können add_filter und add_action genutzt werden */
		public function load() { }
		
		/** Wird aufgerufen um Ausgaben zwischen <html> und </html> in ein Template einzubauen */
		public function wp_head() { }

        /**
         * Erlaubt es CSS/SCSS Dateien zur CSS Ausgabe hinzuzufügen
         */
		public function wpsg_scss(&$arFiles) { }
		
		/**
		 * Wird von dem Produktgruppenmodul in der Sidebar der Produktgruppeneinstellungen aufgerufen
		 */
		public function wpsg_mod_productgroups_addedit_sidebar(&$productgroupdata) { }
		
		/**
		 * Wird beim speichern der Produktgruppen aufgerufen
		 */
		public function wpsg_mod_productgroups_save($produktgroup_id) { }
		
		/**
		 * Wird in der Rechnungsmail aufgerufen
		 */
		public function wpsg_mod_rechnungen_mail() { }
		
		/**
		 * Wird bei der Generierung der RechnungsPDF aufgerufen		 
		 * @param FPDF Object $pdf
		 * @param Integer $order_id
		 * @param Boolean $bPreview
		 * @param Boolean $bInvoice
		 */
		public function wpsg_mod_rechnungen_pdf(&$pdf, &$order_id, &$bPreview, &$bInvoice) { } 
		
		/**
		 * Wird in der Kundenverwaltung beim bearbeiten/Anlagen eines Kunden im Bereich der Sidebar aufgerufen
		 */
		public function wpsg_mod_customer_sidebar(&$customer_data) { }
		
		/**
		 * Wird nach dem Menü in der Kundenverwaltung aufgerufen (Übersicht der Kunden)
		 */
		public function wpsg_mod_customer_head() { }
		
		/** 
		 * Wird vom Exportmodul aufgerufen wenn die verfügbaren Felder geladen werden
		 */
		public function wpsg_mod_export_loadFields(&$arFields) { }
		
		/**
		 * Gibt einen Wert für eine Exportspalte zurück. Das Modul schreibt das Ergebnis in $return zurück
		 */
		public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator) { }
		
		/**
		 * Wird innerhalb der Bestellansicht in der Box "Kundendaten" aufgerufen
		 */
		public function wpsg_order_view_customerdata(&$order_id) { }
		
		/**
		 * Wird vor dem Speichern der Kundendaten in der Kundenverwaltung aufgerufen
		 */
		public function wpsg_mod_customer_save(&$customer_data) { }
	
		/** 
		 * Wird von wpsg_enqueue_scripts aufgerufen
		 */
		public function wpsg_enqueue_scripts() { }

		/**
		 * Ermöglicht es die Ersetzungsfunktion aus einem Modul zu erweitern
		 */
		public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false) { }
		
		/**
		 * Ermöglicht es weitere Zeilen in die Konfiguration der Versandarten einzubauen
		 * @param Array $va Datensatz der Versandart
		 */
		public function wpsg_mod_versandarten_listrow(&$va) { }
		
		/** Ermöglicht die Erweiterung des Varianten Arrays */
		public function wpsg_mod_varianten_loadVarianten(&$product_id, &$varianten_data) { } // public function wpsg_mod_varianten(&$varianten_data)
		
		/** Wird im Footer aufgerufen */
		public function wp_foot() { }
	 
		/** ------------------------------------------------------------------- */		

		public function __call($name, $arguments) {
			
			if (method_exists($this, $name)) {
	        	                   
	        	return call_user_func_array(array($this, $name), $arguments);
	        	
	        } else {
	         
				throw \wpsg\Exception::getMethodNotFoundException();

	        }
			
		} // public function __call($name, $arguments)
		
		/**
		 * Gibt den Mehrwertsteuersatz zurück für den übermittelten Steuerwert
		 * Ausgabe anhand des im Frontend gewählten Landes
		 */
		protected function getTaxValue($tax_key, $country)
		{
			 
			if ($tax_key == 'e' || !is_object($country)) return 0;
			
			$tax_value = $country->getTax($tax_key);
			
			return $tax_value;
			
		} // protected function getTaxValue($tax_key, $country)
		
		/**
		 * Hilfsfunktion, die von den Versand- und Zahlungsmodulen genutzt werden kann
		 * Berechnet den Preis anhand des Preisschlüssels und der MwSt
		 * @param String $preis_key 	Der Preisschlüssel derzeit absolute oder prozentuale Werte
		 * @param double $preis			Der Preis auf den sich die prozentualen Angaben beziehen sollen
		 */
		protected function getPreis($preis_key, $preis)
		{
		
			if (strpos($preis_key, '%') !== false)
			{
				
				$proz = str_replace('%', '', $preis_key);				
				$preis = $preis / 100 * $proz;
				
			}
			else
			{
				
				$preis = wpsg_tf($preis_key);
								
			}
			
			return $preis;
		
		} // protected function getPreis($preis_key, $preis, $brutnet, $mwst_value)
				
		/**
		 *  Hilfsfunktion, die die Versandkosten auf den Warenkorb Array draufschlägt
		 *  @param $arBasket Der Array des Warenkorbs
		 *  @param $shipping_price Die absoluten Versandkosten 
		 *  @param $mwst_id Die ID der MwSt, 0 für anteilig
		 */
		public function setShippingKosten(&$arBasket, $shipping_key, $shipping_price, $tax_key)
		{
			
			// Das richtige Land ermitteln für FE oder BE
			if (isset($arBasket['backend']) && ($arBasket['backend'] == true))
			{	// Backend
				$oCountry = wpsg_country::getInstance($arBasket['checkout']['land']);
				$country_id = $arBasket['checkout']['land'];
				
			}
			else
			{	// Frontend
				//$oCountry = $this->shop->getFrontendCountry($arBasket['checkout']['land']);
				$oCountry = wpsg_country::getInstance($arBasket['checkout']['land']);
				$country_id = $arBasket['checkout']['land'];
				
			}
			if (!isset($oCountry))
			{
				$oCountry = $this->shop->getDefaultCountry();
				$country_id = $oCountry->id;
				$test = 2;
			}
			
			if (in_array($tax_key, array('a', 'b', 'c', 'd', 'e')))
			{
				 
				// Fixer Satz
				//$tax_value = $this->getTaxValue($tax_key, $this->shop->getDefaultCountry());
				$tax_value = $this->getTaxValue($tax_key, $oCountry);
				
				if ($this->shop->get_option('wpsg_preisangaben') == WPSG_BRUTTO)
				{
					
					$shipping_brutto = $shipping_price;
					$shipping_netto = wpsg_calculatePreis($shipping_price, WPSG_NETTO, $tax_value);
					
				}
				else
				{
					
					$shipping_brutto = wpsg_calculatePreis($shipping_price, WPSG_BRUTTO, $tax_value);
					$shipping_netto = $shipping_price;				
										
				}

				$shipping_mwst = (int)$shipping_brutto - $shipping_netto;
				if ($arBasket['noMwSt'] == true) $shipping_mwst = 0;
				
				$this->shop->basket->checkMwSt($tax_key, $this->shop->getDefaultCountry(), $arBasket);
				$this->shop->basket->checkMwSt($tax_key, $oCountry, $arBasket);
				
				wpsg_addSet($arBasket['mwst'][$tax_key.'_'.$country_id]['sum'], $shipping_mwst);
				wpsg_addSet($arBasket['mwst'][$tax_key.'_'.$country_id]['base_value'], $shipping_brutto);
				
				wpsg_addSet($arBasket['sum']['preis_shipping_netto'], $shipping_netto);
				wpsg_addSet($arBasket['sum']['preis_shipping_brutto'], $shipping_brutto);
				wpsg_addSet($arBasket['sum']['mwst'], $shipping_mwst);
				
				$arBasket['shipping']['mwst'] = $shipping_mwst;
				$arBasket['shipping']['tax_value'] = $tax_value;
				$arBasket['shipping']['preis_shipping_netto'] = $shipping_netto;
				$arBasket['shipping']['preis_shipping_brutto'] = $shipping_brutto;
				$arBasket['shipping']['tax_rata'] = false;
				
				//$arBasket['shipping']['mwst'] = $tax_value; // Der Steuersatz der Versandart
				//$arBasket['shipping']['tax_rata'] = false;
				
			}
			else
			{
				
				// Anteilig
				$arMwStAnteile = Array(); // Hier sind die % drin, die die einzelnen Sätze ausmachen
 	
				// Für die Anteilsberechnung muss ich die 0% Sätze rausnehmen und den tatsächlichen besteuerten Betrag ermitteln
				$tax_base_sum = 0;
				foreach ((array)$arBasket['mwst'] as $tax_key => $mw)
				{
					
					if ($mw['value'] > 0) $tax_base_sum += $mw['base_value'];
					
				}
				 
				foreach ((array)$arBasket['mwst'] as $tax_key => $mw)
				{
					 
					if ($mw['value'] > 0 && $mw['base_value'] > 0 && $tax_base_sum > 0)
					{
						 
						$arMwStAnteile[$tax_key] = $mw['base_value'] / $tax_base_sum;
						
					}
					
				}
				
				// Sollte es keine MwSt Verteilung geben, dann auf den ersten Satz draufrechnen
				if (!wpsg_isSizedArray($arMwStAnteile) && wpsg_isSizedArray($arBasket['mwst']))
				{
					
					foreach ((array)$arBasket['mwst'] as $tax_key => $mw) { $arMwStAnteile[$tax_key] = 1; break; }
					
				}
				 
				$tax_sum = 0;
				
				$tax_netto_gesamt = 0;
				$tax_brutto_gesamt = 0;				
				
				foreach ($arMwStAnteile as $tax_key => $mw_anteil)
				{
					
					// Eventuell Nettopreis bestimmen
					if ($this->shop->getBackendTaxview() == WPSG_BRUTTO)
					{
							
						//$defaultCountry = $this->shop->getDefaultCountry();
						$defaultCountry = $oCountry;
						$defaultTax = $defaultCountry->getTax(substr($tax_key, 0, 1));

						$tax_netto = 0;
						if (!empty($shipping_price))
							$tax_netto = wpsg_calculatePreis(($shipping_price * $mw_anteil), WPSG_NETTO, $defaultTax); // * $mw_anteil;
						
					}
					else
					{
						
						$tax_netto = $shipping_price * $mw_anteil;
						
					}
					
					$tax_brutto = wpsg_calculatePreis($tax_netto, WPSG_BRUTTO, $arBasket['mwst'][$tax_key]['value']);
					
					$tax = $tax_brutto - $tax_netto;
					$tax_sum += $tax;

					$tax_netto_gesamt += $tax_netto;
					$tax_brutto_gesamt += $tax_brutto;
					
					$arBasket['mwst'][$tax_key]['sum'] += $tax;
					$arBasket['mwst'][$tax_key]['base_value'] += $tax_brutto;
										
				}
 
				wpsg_addSet($arBasket['sum']['mwst'], $tax_sum);
				
				wpsg_addSet($arBasket['sum']['preis_shipping_netto'], $tax_netto_gesamt);
				wpsg_addSet($arBasket['sum']['preis_shipping_brutto'], $tax_brutto_gesamt);
				 				 
				$arBasket['shipping']['mwst'] = $tax_sum;
				$arBasket['shipping']['preis_shipping_netto'] = $tax_netto_gesamt;
				$arBasket['shipping']['preis_shipping_brutto'] = $tax_brutto_gesamt;				
				$arBasket['shipping']['tax_rata'] = true;
								
			}
			 
		} // protected function setShippingKosten(&$arBasket, $shipping_key, $shipping_price, $mwst_id)

		/**
		 * @param $product_key string
		 */
		public function canOrder($product_key) { }
		
		/** Bestimmt ob das Produkt angezeigt werden kann */
		public function canDisplay($product_key) { }
		
		/** wird von wpsg_product aufgerufen, wenn der Produktkey ergänzt wird */
		public function product_setProductKey(&$oProduct, $product_key) { }
		
		/**
		 *  Hilfsfunktion, die die Zahlungskosten auf den Warenkorb Array draufschlägt
		 *  @param $arBasket Der Array des Warenkorbs
		 *  @param $shipping_price Die absoluten Zahlungskosten 
		 *  @param $mwst_id Die ID der MwSt, 0 für anteilig
		 */
		public function setPaymentKosten(&$arBasket, $payment_price, $tax_key)
		{
			
			// Das richtige Land ermitteln für FE oder BE
			if (isset($arBasket['backend']) && ($arBasket['backend'] == true))
			{	// Backend
				$oCountry = wpsg_country::getInstance($arBasket['checkout']['land']);
				$country_id = $arBasket['checkout']['land'];
				
			}
			else
			{	// Frontend
				//$oCountry = $this->shop->getFrontendCountry($arBasket['checkout']['land']);
				$oCountry = wpsg_country::getInstance($arBasket['checkout']['land']);
				$country_id = $arBasket['checkout']['land'];
				
			}
			if (!isset($oCountry))
			{
				$oCountry = $this->shop->getDefaultCountry();
				$country_id = $oCountry->id;
				$test = 2;
			}
			
			if (in_array($tax_key, array('a', 'b', 'c', 'd', 'e')) && !$this->shop->basket->hasEULeistungsortProduct($arBasket))
			{
				
				// Fixer Satz
				$tax_value = $this->getTaxValue($tax_key, $oCountry); 
				
				if ($this->shop->get_option('wpsg_preisangaben') == WPSG_BRUTTO)
				{
					
					$payment_brutto = $payment_price;
					$payment_netto = wpsg_calculatePreis($payment_price, WPSG_NETTO, $tax_value);
					
				}
				else
				{
					
					$payment_brutto = wpsg_calculatePreis($payment_price, WPSG_BRUTTO, $tax_value);
					$payment_netto = $payment_price;				
										
				}
				 	
				$payment_mwst = $payment_brutto - $payment_netto;
				
				$this->shop->basket->checkMwSt($tax_key, $oCountry, $arBasket);
				wpsg_addSet($arBasket['mwst'][$tax_key.'_'.$country_id]['sum'], $payment_mwst);
				wpsg_addSet($arBasket['mwst'][$tax_key.'_'.$country_id]['base_value'], $payment_brutto);

				wpsg_addSet($arBasket['sum']['preis_payment_netto'], $payment_netto);
				wpsg_addSet($arBasket['sum']['preis_payment_brutto'], $payment_brutto);
				wpsg_addSet($arBasket['sum']['mwst'], $payment_mwst);
				
				$arBasket['payment']['mwst'] = $payment_mwst;
				$arBasket['payment']['tax_value'] = $tax_value;
				$arBasket['payment']['preis_payment_netto'] = $payment_netto;
				$arBasket['payment']['preis_payment_brutto'] = $payment_brutto;
				$arBasket['payment']['tax_rata'] = false;
				
				//$arBasket['payment']['mwst'] = $tax_value; // Der Steuersatz der Zahlungsmethode
				//$arBasket['payment']['tax_rata'] = false;

			}
			else
			{
								
				// Anteilig
				$arMwStAnteile = Array(); // Hier sind die % drin, die die einzelnen Sätze ausmachen
  
				// Für die Anteilsberechnung muss ich die 0% Sätze rausnehmen und den tatsächlichen besteuerten Betrag ermitteln
				$tax_base_sum = 0;
				foreach ((array)$arBasket['mwst'] as $tax_key => $mw)
				{
					
					if ($mw['value'] > 0) $tax_base_sum += wpsg_getFloat($mw['base_value']);
					
				}
				
				foreach ((array)$arBasket['mwst'] as $tax_key => $mw)
				{
					 
					if (wpsg_getFloat($mw['base_value']) > 0 && $tax_base_sum > 0)
					{
					
						$arMwStAnteile[$tax_key] = $mw['base_value'] / $tax_base_sum;
						
					}
						
				}
				
				// Sollte es keine MwSt Verteilung geben, dann auf den ersten Satz draufrechnen
				if (!wpsg_isSizedArray($arMwStAnteile) && wpsg_isSizedArray($arBasket['mwst']))
				{

					foreach ((array)$arBasket['mwst'] as $tax_key => $mw) { $arMwStAnteile[$tax_key] = 1; break; }

				}
				
				$mwst_sum = 0;
				$tax_netto_gesamt = 0;
				$tax_brutto_gesamt = 0;
				$tax_sum = 0;
				/* XXHR
				foreach ($arMwStAnteile as $tax_key => $mw_anteil)
				{
					
					// Das ist der Anteil des Bruttopreises der mit dieser Steuer besteuert werden soll ... bescheuert
					if ($this->shop->getBackendTaxview() == WPSG_NETTO)
					{
					
						$tax_netto = $payment_price * $mw_anteil;
						$tax_brutto = wpsg_calculatePreis($tax_netto, WPSG_BRUTTO, $arBasket['mwst'][$tax_key]['value']);
					
						$mwst = $tax_brutto - $tax_netto;
							
					}
					else
					{
					
						$mwst_base = $payment_price * $mw_anteil;
						$netto = $mwst_base / (1 + $arBasket['mwst'][$tax_key]['value'] / 100);
					
						$mwst = $mwst_base - $netto;
					
					}
					
					$arBasket['mwst'][$tax_key]['sum'] += $mwst;
					$mwst_sum += $mwst;
					 					
				}
				*/

				foreach ($arMwStAnteile as $tax_key => $mw_anteil)
				{
						
					$tax_netto = 0;
					// Eventuell Nettopreis bestimmen
					if ($this->shop->getBackendTaxview() == WPSG_BRUTTO)
					{
							
						$defaultCountry = $oCountry;
						$defaultTax = $defaultCountry->getTax(substr($tax_key, 0, 1));
						if (!empty($payment_price))
							$tax_netto = wpsg_calculatePreis(($payment_price * $mw_anteil), WPSG_NETTO, $defaultTax); // * $mw_anteil;
				
					}
					else
					{
						
						if (is_numeric($payment_price) && is_numeric($mw_anteil)) {
						
							$tax_netto = $payment_price * $mw_anteil;
				
						} else {
													
						}
					}
						
					$tax_brutto = wpsg_calculatePreis($tax_netto, WPSG_BRUTTO, $arBasket['mwst'][$tax_key]['value']);
						
					$tax = $tax_brutto - $tax_netto;
					$tax_sum += $tax;
				
					$tax_netto_gesamt += $tax_netto;
					$tax_brutto_gesamt += $tax_brutto;
						
					wpsg_addSet($arBasket['mwst'][$tax_key]['sum'], $tax);
					wpsg_addSet($arBasket['mwst'][$tax_key]['base_value'], $tax_brutto);
					
				}
				
				wpsg_addSet($arBasket['sum']['mwst'], $tax_sum);
				
				/* HR
				if ($this->shop->getFrontendTaxview() == WPSG_NETTO)
				{
						
					wpsg_addSet($arBasket['sum']['preis_payment_netto'], $payment_price);
					wpsg_addSet($arBasket['sum']['preis_payment_brutto'], ($payment_price + $mwst_sum));
												
				}
				else
				{
					
					wpsg_addSet($arBasket['sum']['preis_payment_netto'], ($payment_price - $mwst_sum));
					wpsg_addSet($arBasket['sum']['preis_payment_brutto'], $payment_price);
																			
				}
				*/
				
				wpsg_addSet($arBasket['sum']['preis_payment_netto'], $tax_netto_gesamt);
				wpsg_addSet($arBasket['sum']['preis_payment_brutto'], $tax_brutto_gesamt);
				
				$arBasket['payment']['mwst'] = $tax_sum;
				$arBasket['payment']['preis_payment_netto'] = $tax_netto_gesamt;
				$arBasket['payment']['preis_payment_brutto'] = $tax_brutto_gesamt;
				$arBasket['payment']['tax_rata'] = true;
				
			}
			
		} // protected function setPaymentKosten(&$arBasket, $payment_price, $mwst_id)
		
		/*
 		 * Gibt den Absoluten Pfad zurück wo temporäre wpsg Dateien gespeichert sind
 		 * Wordkaround falls /tmp nicht beschreibbar 
 		 */
 		public function getTmpFilePath($burl = false)
 		{
 		 
 			if ($this->shop->isMultiBlog())
			{
          
				if ($burl) $url = WPSG_URL_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/';
				$path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/';
				 
			}
			else
			{
				
				if ($burl) $url = WPSG_URL_CONTENT.'uploads/wpsg/wpsg_temp/';
				$path = WP_CONTENT_DIR.'/uploads/wpsg/wpsg_temp/';
				
			}
			
			if (!file_exists($path)) mkdir($path, 0777, true);
			
			if ($burl) return $url;
			else return $path;
 			 			 
 		} // protected function getTmpFilePath($burl = false)

 		/**
 		 * Wird beim löschen einer Bestellung aufgerufen
 		 */
 		public function delOrder(&$order_id) { } // public function delOrder(&$order_id)

		public function systemcheck(&$arData) { } // public function systemcheck($arData)
		
	} // class wpsg_mod_basic

?>