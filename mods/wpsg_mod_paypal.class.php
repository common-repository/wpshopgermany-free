<?php

	/**
	 * Dieses Modul ermöglicht die Zahlungsart "PayPal"
	 */
	class wpsg_mod_paypal extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 2;		
		var $hilfeURL = 'http://wpshopgermany.de/?p=613';

		var $url = false; // URL zum PayPal Endpunkt, wird von der init() Methode gesetzt
		var $apiContext = false;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('PayPal', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Ermöglicht die Zahlungsart PayPal.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{

			$this->shop->checkDefault('wpsg_mod_paypal_bezeichnung', $this->name, false, true);
			$this->shop->checkDefault('wpsg_mod_paypal_aktiv', '1');
			$this->shop->checkDefault('wpsg_mod_paypal_hint', __('Zahlen Sie die Bestellung mittels ihres PayPal Kontos.', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_paypal_currency', 'EUR');
			$this->shop->checkDefault('wpsg_mod_paypal_gebuehr', '0');
			$this->shop->checkDefault('wpsg_mod_paypal_mwst', '0');
			$this->shop->checkDefault('wpsg_mod_paypal_mwstland', '0');
			$this->shop->checkDefault('wpsg_mod_paypal_autostart', '0');
			$this->shop->checkDefault('wpsg_mod_paypal_sandbox', '0');			
			$this->shop->checkDefault('wpsg_mod_paypal_language', 'DE');
			$this->shop->checkDefault('wpsg_mod_paypal_subject', 'O%order_id% - K%kunde_id%', false, true);
			
			$this->shop->checkDefault('wpsg_mod_paypal_stornostate', array('400' => '1', '500' => '1'));
			
		} // public function install()
		
		public function init() 
		{
			
			spl_autoload_register(array($this, 'spl_autoload'));
			
			if ($this->shop->get_option('wpsg_mod_paypal_sandbox') == 1)
			{
				$this->url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
			}
			else		
			{		
				$this->url = "https://www.paypal.com/cgi-bin/webscr";
			}
			
		} // public function init()
		
		public function settings_edit()
		{
			 
			$pages = get_pages();
			
			$arPages = array(
				'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			
			$this->shop->view['pages'] = $arPages;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_paypal/settings_edit.phtml');
			
		} // public function settings_edit()
				
		public function settings_save() {
 
			foreach($_REQUEST['wpsg_mod_paypal_stornostate'] as $k => $v) {
				
				if (wpsg_checkInput($v, WPSG_SANITIZE_INT)) $_REQUEST['wpsg_mod_paypal_stornostate'][$k] = intval($v);
				else unset($_REQUEST[$k]);
				
			}
			
			$this->shop->update_option('wpsg_mod_paypal_stornostate', $_REQUEST['wpsg_mod_paypal_stornostate']);

			$this->shop->update_option('wpsg_mod_paypal_bezeichnung', $_REQUEST['wpsg_mod_paypal_bezeichnung'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_paypal_aktiv', $_REQUEST['wpsg_mod_paypal_aktiv'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_paypal_hint', $_REQUEST['wpsg_mod_paypal_hint'], false, false, WPSG_SANITIZE_TEXTAREA);
			$this->shop->update_option('wpsg_mod_paypal_gebuehr', $_REQUEST['wpsg_mod_paypal_gebuehr'], false, false, WPSG_SANITIZE_FLOAT);
			$this->shop->update_option('wpsg_mod_paypal_mwst', $_REQUEST['wpsg_mod_paypal_mwst'], false, false, WPSG_SANITIZE_TAXKEY);
			
			// Vor dem CreateWebHook
			$this->shop->update_option('wpsg_mod_paypal_sandbox', $_REQUEST['wpsg_mod_paypal_sandbox'], false, false, WPSG_SANITIZE_CHECKBOX);
									
			// Rest API
			$this->shop->update_option('wpsg_mod_paypal_clientid', $_REQUEST['wpsg_mod_paypal_clientid'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_paypal_secret', $_REQUEST['wpsg_mod_paypal_secret'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			if (wpsg_isSizedInt($_REQUEST['wpsg_mod_paypal_createwebhook'])) {
				
				$this->createWebHook();
				
			}
			
			$this->shop->update_option('wpsg_mod_paypal_subject', $_REQUEST['wpsg_mod_paypal_subject'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->addTranslationString('wpsg_mod_paypal_hint', wpsg_sanitize(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_paypal_hint']) ?: $this->shop->get_option('wpsg_mod_paypal_hint'));
			
			$this->shop->update_option('wpsg_mod_paypal_currency', $_REQUEST['wpsg_mod_paypal_currency'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_paypal_autostart', $_REQUEST['wpsg_mod_paypal_autostart'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_paypal_mwstland', $_REQUEST['wpsg_mod_paypal_mwstland'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_paypal_language', $_REQUEST['wpsg_mod_paypal_language'], false, false, WPSG_SANITIZE_TEXTFIELD);
			
			$this->shop->createPage(__('Erfolgreiche PayPal Zahlung', 'wpsg'), 'wpsg_page_mod_paypal_success', wpsg_sinput(WPSG_SANITIZE_INT, $_REQUEST['wpsg_page_mod_paypal_success']));
			$this->shop->createPage(__('Fehlgeschlagene PayPal Zahlung', 'wpsg'), 'wpsg_page_mod_paypal_error', wpsg_sinput(WPSG_SANITIZE_INT, $_REQUEST['wpsg_page_mod_paypal_error']));
			
			$this->shop->addTranslationString('mod_paypal_bezeichnung', wpsg_sanitize(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['mod_paypal_bezeichnung']) ?: $this->shop->get_option('mod_paypal_bezeichnung'));
			$this->shop->addTranslationString('wpsg_mod_paypal_hint', wpsg_sanitize(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_paypal_hint']) ?: $this->shop->get_option('wpsg_mod_paypal_hint'));
			
		} // public function settings_save()

        public function wpsg_deinstall_sites() {

            wp_delete_post($this->shop->get_option('wpsg_page_mod_paypal_success'));
            wp_delete_post($this->shop->get_option('wpsg_page_mod_paypal_error'));

        } // public function wpsg_deinstall_sites()

		public function order_ajax() {
					
			if (wpsg_isSizedString($_REQUEST['do'], 'refresh')) {
				
				wpsg_checkNounce('Order', 'view', ['action' => 'ajax', 'edit_id' => wpsg_getInt($_REQUEST['edit_id']), 'do' => 'refresh', 'mod' => 'wpsg_mod_paypal']);
				
				if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException(); else $order_id = intval($_REQUEST['edit_id']);
				 
				$state = $this->getPaymentState($order_id);
		
				$this->shop->addBackendMessage(wpsg_translate(__('Status der Zahlung (#1#) erfolgreich abgefragt.', 'wpsg'), $state));
				
				$this->shop->redirect(
					wpsg_admin_url('Order', 'view', ['edit_id' => $order_id])
				);
		
			}
				
		} // public function order_ajax()
		
		public function systemcheck(&$arData) {
			
			if ($this->shop->get_option('wpsg_mod_paypal_aktiv') === '1') {
			
				$arPflicht = $this->shop->loadPflichtFeldDaten();
				
				if (!isset($arPflicht['plz']) || $arPflicht['plz'] !== '0') {
					
					$arData[] = array(
						'wpsg_mod_paypal_zip',
						wpsg_ShopController::CHECK_WARNING,
						wpsg_translate(
							__('Bei verwendung von PayPal als Zahlungsart sollte die PLZ ein Pflichtfeld sein, damit die API funktioniert. <a href="#1#">hier</a> konfigurieren.', 'wpsg'),
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=kundendaten'
						)
					);
					
				}
				
				if (!wpsg_isSizedString($this->shop->get_option('wpsg_mod_paypal_clientid')) && !wpsg_isSizedString($this->shop->get_option('wpsg_mod_paypal_secret'))) {
					
					$arData[] = array(
						'wpsg_mod_paypal_apidata',
						wpsg_ShopController::CHECK_ERROR,
						wpsg_translate(
							__('Das Modul "PayPal" ist aktiviert und es wurden keine API Zugangsdaten hinterlegt. Gehen Sie in die <a href="#1#">Einstellungen</a> des Moduls um die API Daten zu hinterlegen.', 'wpsg'),
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_paypal'
						)
					); 
					
				}
				
				if ($this->shop->get_option('wpsg_currency') !== 'EUR') {
					
					$arData[] = array(
						'wpsg_mod_paypal_currency',
						wpsg_ShopController::CHECK_ERROR,
						wpsg_translate(
							__('Der Währungscode sollte bei der Verwendung mit PayPal auf "EUR" stehen. Gehen Sie in die <a href="#1#">Einstellungen</a> um dies zu korrigieren.', 'wpsg'),
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=allgemein'
						)
					);
					
				}
				
			}
			
		}
		 
		public function addPayment(&$arPayment) { 
			
			if (!is_admin() && $this->shop->get_option('wpsg_mod_paypal_aktiv') != '1') return;
			
			$arPayment[$this->id] = array(
				'id' => $this->id,
				'name' => __($this->shop->get_option('wpsg_mod_paypal_bezeichnung'), 'wpsg'),
				'price' => $this->shop->get_option('wpsg_mod_paypal_gebuehr'),
				'tax_key' => $this->shop->get_option('wpsg_mod_paypal_mwst'),
				'mwst_null' => $this->shop->get_option('wpsg_mod_paypal_mwstland'),
				'hint' => __($this->shop->get_option('wpsg_mod_paypal_hint')),
				'logo' => $this->shop->getRessourceURL('mods/mod_paypal/gfx/logo_100x25.png')
			);
			 			
		} // public function addPayment(&$arPayment)
		 
		public function setOrderStatus($order_id, $status_id, $inform)
		{
		
			$oOrder = wpsg_order::getInstance($order_id);
				
			if ($oOrder->getPaymentID() == $this->id)
			{
		
				$arStornoState = (array)$this->shop->get_option('wpsg_mod_paypal_stornostate');
					
				if (array_key_exists($status_id, $arStornoState) && $arStornoState[$status_id] == '1')
				{
						
					if (wpsg_isSizedString($oOrder->getMeta('wpsg_mod_paypal_saleid')))
					{
							
						$bStorno = $this->stornoOrder($order_id);
		
						if ($bStorno === true) $this->shop->addBackendMessage(__('Zahlung erfolgreich über die PayPal API storniert.', 'wpsg'));
						else $this->shop->addBackendError(__('Es gab ein Problem bei der Stornierung der Zahlung über die PayPal API. Bitte Bestellprotokoll beachten.', 'wpsg'));
		
					}
						
				}
		
			}
		
		}
		
		public function order_view_afterpayment(&$order_id)
		{
		
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_paypal/order_view_afterpayment.phtml');
		
		} // public function order_view_sidebar(&$order_id)
		
		public function order_done(&$order_id, &$done_view)  { 
 
			// Bestellungen mit 0 geben nix aus
			if ($done_view['basket']['arCalculation']['sum']['topay_brutto'] <= 0 || $this->shop->view['basket']['checkout']['payment'] != $this->id) return;
							
            if ($this->getPaymentState($order_id) === 'approved') {
                    
                $this->shop->view['wpsg_mod_paypal']['done'] = '1';
            
            } else {
                    
                $this->shop->view['paypalLink'] = $this->shop->getUrl(wpsg_ShopController::URL_BASKET, 'wpsg_mod_paypal', 'startPayPalPayment', array('order_id' => $order_id));
                                
            }
            			
			echo $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_paypal/order_done.phtml', false);
						
		} // public function order_done(&$order_id)

		public function template_redirect() { 
			
			if (wpsg_isSizedString($_REQUEST['wpsg_plugin'], 'wpsg_mod_paypal') && wpsg_isSizedString($_REQUEST['confirm'], 'pp'))			
			{
				
				$this->shop->checkEscape();
				
				// IPN Zahlungsbenachrichtigung
				$req = 'cmd=_notify-validate';
				
				foreach ($_POST as $key => $value)
				{ 
					 			
					$req .= "&".$key."=".urlencode($value);
					
				}

				$header = "";
				$header .= "POST /cgi-bin/webscr HTTP/1.1\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
								
				if ($this->shop->get_option('wpsg_mod_paypal_sandbox') == 1)
				{
					
					$header .= "Host: www.sandbox.paypal.com\r\n";
					$fp = fsockopen ('tls://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
					
				}
				else	
				{	
							
					$header .= "Host: www.paypal.com\r\n";
					$fp = fsockopen ('tls://www.paypal.com', 443, $errno, $errstr, 30);
					
				}
 
				$header .= "Connection: Close\r\n";
				$header .= "Content-Length: ".strlen($req)."\r\n\r\n";
				
				if (!$fp) 
				{
					
					
					
				} 
				else 
				{
												
					fputs ($fp, $header.$req);
					
					while (!feof($fp)) 
					{
						
						$res = fgets ($fp, 1024);
						
						if (strcmp(trim($res), "VERIFIED") == 0) 
						{
							
							if (strtolower($_REQUEST['payment_status']) == "completed")
							{
								
								// Wir haben hier nicht wpsg_hspc (htmlspecialchars) verwendet da es ein Problem mit seltsammen (Steuerzeichen?) gab
								// Wenn mann sich den Request so angeschaut hat war alles in Ordnung konnte mit gleichen Daten auch nicht nachgestellt werden
								// Problem trat nur bei den Requests von PayPal auf.
								// Nicht zentral gelöst, da im Manual ein Hinweis auf ein mögliches Sicherheitsproblem existiert
								
								$this->db->ImportQuery(WPSG_TBL_OL, array(
									"title" => __("PayPal VERIFIED", "wpsg"),
									"cdate" => "NOW()",
									"o_id" => wpsg_q(wpsg_xss($_REQUEST['custom'])),
									"mailtext" => wpsg_q(htmlentities(print_r($_REQUEST, 1), ENT_IGNORE))
								));
							
								if ($this->shop->setPayMent(wpsg_xss($_REQUEST['custom']), $_REQUEST['mc_gross']))
								{
									
									$this->shop->setOrderStatus(wpsg_xss($_REQUEST['custom']), 100, true);
									
								}
															
							}
							
						}
						else if (strcmp(trim($res), "INVALID") == 0) 
						{

							$this->db->ImportQuery(WPSG_TBL_OL, array(
								"title" => __("PayPal FAILED", "wpsg"),
								"cdate" => "NOW()",
								"o_id" => wpsg_q(wpsg_xss($_REQUEST['custom'])),
								"mailtext" => wpsg_q(wpsg_hspc(print_r($_REQUEST, 1)))
							));
										
						}
						
					}
					
					fclose($fp);
				
				}
				
				die();
					
			}
			
		} // public function template_redirect()
		
		public function mail_payment() 
		{ 

			if ($this->shop->view['basket']['checkout']['payment'] != $this->id) return;			
			
			if ($this->shop->htmlMail === true)
			{
				
				echo '<a href="'.$this->shop->getDoneURL($this->shop->view['o_id']).'">'.__('Zahlungslink', 'wpsg').'</a>'.__(', um die Zahlung durchzuführen', 'wpsg');
				
			}
			else
			{
			
				echo wpsg_pad_right(__('Zahlungslink', 'wpsg').':', 35).$this->shop->getDoneURL($this->shop->view['o_id']);
				
			}
			
		} // public function mail_payment()
		
		/* Modulfunktionen */
				
		/* REST API */
		
		/**
		 * Wird bei einem WebHook Event aufgerufen
		 * @throws Exception
		 */
		public function webHookRedirect()
		{
				
			$bodyReceived = file_get_contents('php://input');
				
			try
			{
					
				if (!$this->isSandbox())
				{
						
					// Wenn nicht validiert, dann wird hier eine Exception erzeugt, die Daten hole ich dann aus der original Body Nachricht, damit es konform mit der Sandbox ist
					//$output = \PayPal\Api\WebhookEvent::validateAndGetReceivedEvent($bodyReceived, $this->getApiContext());
						
				}
					
				$jsonData = json_decode($bodyReceived);
				$payment_id = $jsonData->resource->parent_payment;
				$amount = $jsonData->resource->amount->total;
				$order_id = $this->db->fetchOne("SELECT `target_id` FROM `".WPSG_TBL_META."` WHERE `meta_table` = 'WPSG_TBL_ORDER' AND `meta_key` = 'wpsg_mod_paypal_paymentid' AND `meta_value` = '".wpsg_q($payment_id)."' ");
				$oOrder = wpsg_order::getInstance($order_id);
		
				if ($oOrder === false) throw new Exception(__('Keine Bestellung zu PaymentID gefunden.', 'wpsg'));
		
				$state = $this->getPaymentState($order_id);
				$orderPaymentID = $oOrder->getMeta('wpsg_mod_paypal_paymentid');
		
				if ($this->shop->setPayMent($oOrder->id, $amount) && $orderPaymentID === $payment_id && $state === 'approved')
				{
		
					$this->shop->setOrderStatus($oOrder->id, 100, true);
						
					$oOrder->setMeta('wpsg_mod_paypal_saleid', $jsonData->resource->id);
						
					$oOrder->log($jsonData->summary, __('PayPal Erfolg: ', 'wpsg')."\r\n".print_r($jsonData, 1));
		
				}
				else
				{
						
					$oOrder->log($jsonData->summary, __('PayPal Abgelehnt: ', 'wpsg')."\r\n".print_r($jsonData, 1)."\r\nOrder PaymentID: ".$orderPaymentID."\r\nRequest PaymentID:".$payment_id."\r\nOrder State:".$state);
						
				}
		
			}
			catch (Exception $ex)
			{
		
				// WebHook Invalid
				header('HTTP/1.1 404'); exit(1); 
		
			}
		
			header('HTTP/1.1 200 OK'); exit(1);
		
		} // public function webHookRedirect()
		
		/**
		 * Startet die Zahlungsabwicklung über PayPal mittels der API
		 */
		public function startPayPalPaymentRedirect()
		{
				
			if (!wpsg_isSizedInt($_REQUEST['order_id'])) throw new \wpsg\Exception(__('Beim start der Zahlung wurde keine BestellID übergeben.', 'wpsg'));
				
			$oOrder = wpsg_order::getInstance(wpsg_sinput("key", $_REQUEST['order_id']));
			$paymentId = $oOrder->getMeta('wpsg_mod_paypal_paymentid');
				
			if (0&&wpsg_isSizedString($paymentId))
			{
		
				try
				{
		
					$payment = \PayPal\Api\Payment::get($oOrder->getMeta('wpsg_mod_paypal_paymentid'), $this->getApiContext());
					$approvalLink = $payment->getApprovalLink();
						
					if (!wpsg_isSizedString($approvalLink))
					{
		
						$oOrder->log(__('PayPal Fehler: Zahlung konnte nicht erneut ausgeführt werden', 'wpsg'), 'PaymentStatus: '.$payment->getState());
						$this->shop->redirect($this->shop->getDoneURL($oOrder->id));
		
					}
					else
					{
							
						$this->shop->redirect($payment->getApprovalLink());
		
					}
						
				}
				catch (Exception $ex)
				{

					$data = json_decode($ex->getData(), true);
						
					ob_start();
					var_dump($ex);
					$dump = ob_get_contents();
					ob_end_clean();
						
					$oOrder->log(wpsg_translate(__('PayPal Fehler: #1#', 'wpsg'), $data['message']), print_r($data, 1)."\r\n".$dump);
						
					$this->shop->addFrontendError(__('PayPal Fehler, bitte Shop Betreiber kontaktieren.', 'wpsg'));
					$this->shop->redirect(get_permalink($this->shop->get_option('wpsg_page_mod_paypal_error')));
						
				}
		
			}
				
			if ($this->id != $oOrder->getPaymentID()) throw new \wpsg\Exception(__('Es wurde versucht eine Zahlung zu starten zu einer Bestellung die nicht mit PayPalAPI ausgeführt wurde', 'wpsg'));
		
			$payer = new \PayPal\Api\Payer();
			$payer->setPaymentMethod("paypal");
				
			$billing_address = new \PayPal\Api\Address();
			$billing_address->setCity($oOrder->getInvoiceCity());
			$billing_address->setPostalCode($oOrder->getInvoiceZip());
			$billing_address->setLine1($oOrder->getInvoiceStreet());
			$billing_address->setCountryCode($oOrder->getInvoiceCountryKuerzel());
		
			$shipping_address = new \PayPal\Api\Address();
			$shipping_address->setCity($oOrder->getShippingCity());
			$shipping_address->setPostalCode($oOrder->getShippingZip());
			$shipping_address->setLine1($oOrder->getShippingStreet());
			$shipping_address->setCountryCode($oOrder->getShippingCountryKuerzel());
		
			$payer_info = new \PayPal\Api\PayerInfo();
			$payer_info->setFirstName($oOrder->getInvoiceFirstName());
			$payer_info->setLastName($oOrder->getInvoiceName());
			$payer_info->setBillingAddress($billing_address);
			//$payer_info->setShippingAddress($shipping_address);
		
			$payer->setPayerInfo($payer_info);
			
			$item = new \PayPal\Api\Item();
			$item->setName(__('Bestellbetrag', 'wpsg'));
			$item->setCurrency($this->shop->get_option('wpsg_currency'));
			$item->setQuantity(1);
			$item->setPrice($oOrder->getToPay(WPSG_BRUTTO));
			
			$itemList = new \PayPal\Api\ItemList();
			$itemList->setItems([$item]);
			
			$details = new \PayPal\Api\Details();
			$details->setShipping($oOrder->getShippingAmount(WPSG_BRUTTO));
			$details->setSubtotal($oOrder->getToPay(WPSG_BRUTTO) - $oOrder->getShippingAmount(WPSG_BRUTTO) - $oOrder->getPaymentAmount(WPSG_BRUTTO));
			
			/*
			 $arProducts = $oOrder->getOrderProducts();
			 	
			 $arItems = array();
			 	
			 foreach ($arProducts as $oOrderProduct)
			 {
		
			 $oProduct = $oOrderProduct->getProduct();
		
			 $item = new \PayPal\Api\Item();
			 $item->setName(substr($oProduct->getProductName(), 0, 127));
			 //$item1->setDescription() 127 Zeichen
			 $item->setCurrency($this->shop->get_option('wpsg_currency'));
			 $item->setQuantity($oOrderProduct->getCount());
			 //$item->setTax($oOrderProduct->getOneTaxAmount());
			 $item->setPrice($oOrderProduct->getOneAmount(WPSG_BRUTTO));
		
			 $arItems[] = $item;
		
			 }
		
			 if ($oOrder->getPaymentAmount(WPSG_BRUTTO) > 0)
			 {
		
			 $item = new \PayPal\Api\Item();
			 $item->setName(__('Kosten für Zahlungsart', 'wpsg'));
			 $item->setCurrency($this->shop->get_option('wpsg_currency'));
			 $item->setQuantity('1');
			 //$item->setTax($oOrder->getPaymentTaxAmount());
			 $item->setPrice($oOrder->getPaymentAmount(WPSG_BRUTTO));
		
			 $arItems[] = $item;
		
			 }
			 	
			 $itemList = new \PayPal\Api\ItemList();
			 $itemList->setItems($arItems);
		
			 $details = new \PayPal\Api\Details();
			 $details->setShipping($oOrder->getShippingAmount(WPSG_BRUTTO));
			 $details->setSubtotal($oOrder->getToPay(WPSG_BRUTTO) - $oOrder->getShippingAmount(WPSG_BRUTTO) - $oOrder->getPaymentAmount(WPSG_BRUTTO));
			*/
			
			$amount = new \PayPal\Api\Amount();
			$amount->setCurrency($this->shop->get_option('wpsg_mod_paypal_currency'));
			$amount->setTotal($oOrder->getToPay(WPSG_BRUTTO));
			//$amount->setDetails($details);
				
			$transaction = new \PayPal\Api\Transaction();
			$transaction->setAmount($amount);
			$transaction->setItemList($itemList);
			$transaction->setDescription($this->shop->replaceUniversalPlatzhalter(__($this->shop->get_option('wpsg_mod_paypal_subject'), 'wpsg'), $oOrder->id));
			$transaction->setInvoiceNumber($oOrder->id);
				
			$redirectUrls = new \PayPal\Api\RedirectUrls();
			$redirectUrls->setReturnUrl($this->shop->getUrl(wpsg_ShopController::URL_BASKET, 'wpsg_mod_paypal', 'executePayment'));
			$redirectUrls->setCancelUrl(get_permalink($this->shop->get_option('wpsg_page_mod_paypal_error')));
				
			$payment = new \PayPal\Api\Payment();
			$payment->setIntent("sale");
			$payment->setPayer($payer);
			$payment->setRedirectUrls($redirectUrls);
			$payment->setTransactions(array($transaction));
				
			try {
					
				$response = $payment->create($this->getApiContext());
		
				$oOrder->setMeta('wpsg_mod_paypal_paymentid', $response->getId());
		
			} catch (Exception $ex) {
					
				$data = json_decode($ex->getData(), true);
		
				ob_start();
				var_dump($ex);
				$dump = ob_get_contents();
				ob_end_clean();
		
				$oOrder->log(wpsg_translate(__('PayPal Fehler: #1#', 'wpsg'), $data['message']), print_r($data, 1)."\r\n".$dump);
		
				$this->shop->addFrontendError(__('PayPal Fehler, bitte Shop Betreiber kontaktieren.', 'wpsg'));
				$this->shop->redirect($this->shop->getDoneURL($oOrder->id));
		
			}
				
			$this->getPaymentState($oOrder->id);
				
			$this->shop->redirect($payment->getApprovalLink());
		
		} // public function startPayPalPaymentRedirect()
		
		/**
		 * Führt eine Zahlung aus wird von startPayPalPaymentRedirect aufgerufen
		 */
		public function executePaymentRedirect()
		{
		
			$payment = \PayPal\Api\Payment::get(wpsg_sinput("key", $_REQUEST['paymentId']), $this->getApiContext());
		
			$execution = new \PayPal\Api\PaymentExecution();
			$execution->setPayerId(wpsg_sinput("key", $_REQUEST['PayerID']));
		
			try
			{
		
				$result = $payment->execute($execution, $this->getApiContext());
		
			}
			catch (Exception $ex)
			{
					
				$this->shop->redirect(get_permalink($this->shop->get_option('wpsg_page_mod_paypal_error')));
					
			}
		
			$this->shop->redirect(get_permalink($this->shop->get_option('wpsg_page_mod_paypal_success')));
				
		} // public fucntion executePaymentRedirect()
		
		private function getPaymentState($order_id)
		{
		
			$oOrder = wpsg_order::getInstance($order_id);
				
			if (!wpsg_isSizedString($oOrder->getMeta('wpsg_mod_paypal_paymentid'))) return false;
				
			try
			{
					
				$payment = \PayPal\Api\Payment::get($oOrder->getMeta('wpsg_mod_paypal_paymentid'), $this->getApiContext());
					
				$oOrder->setMeta('wpsg_mod_paypal_paymentstate', $payment->getState());
		
				return $payment->getState();
		
			}
			catch (Exception $e)
			{
		
				return false;
					
			}
				
		}
		
		/**
		 * Wird optional beim speichern ausgelöst, versucht den WebHook anzulegen und setzt die BackendMeldungen 
		 */
		private function createWebHook()
		{
								
			$create_webhook = false;
		
			try
			{
					
				$output = \PayPal\Api\Webhook::getAll($this->getApiContext());
					
				if (wpsg_isSizedArray($output->getWebhooks()))
				{
		
					$nExists = false;
		
					foreach ($output->getWebhooks() as $wh)
					{
							
						if ($wh->getUrl() === $this->shop->getUrl(wpsg_ShopController::URL_BASKET, 'wpsg_mod_paypal', 'webHook', array(), true))
						{
		
							$nExists = true;
							break;
		
						}
		
					}
		
					if ($nExists === false) $create_webhook = true;
					else
					{
							
						$this->shop->addBackendError(__('WebHook ist bereits registriert.', 'wpsg'));
							
					}
		
				}
				else $create_webhook = true;
					
					
			}
			catch (Exception $ex)
			{
		
				$this->shop->addBackendError(__('WebHook konnte nicht abgefragt/angelegt werden. Zugangsdaten überprüfen und erneut speichern.', 'wpsg'));
		
			}
		
			if ($create_webhook === true)
			{
					
				$webhook = new \PayPal\Api\Webhook();
				$webhook->setUrl($this->shop->getUrl(wpsg_ShopController::URL_BASKET, 'wpsg_mod_paypal', 'webHook', array(), true));
					
				$webhook->setEventTypes(array(
					new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}')
				));
					
				try
				{
		
					$output = $webhook->create($this->getApiContext());
					$this->shop->addBackendMessage(__('WebHook erfolgreich angelegt.', 'wpsg'));
						
				}
				catch (Exception $ex)
				{
						
					$data = json_decode($ex->getData(), true);
					$this->shop->addBackendError(wpsg_translate(__('WebHook konnte nicht angelegt werden. (#1#)', 'wpsg'), $data['details'][0]['issue']));
		
				}
		
			}
					
		} // private function createWebHook();
		
		private function stornoOrder($order_id)
		{
				
			$oOrder = wpsg_order::getInstance($order_id);
			if ($oOrder === false) throw new \wpsg\Exception(__('Bei einer Stornierung konnte Bestellung nicht geladen werden', 'wpsg'));
				
			$sale_id = $oOrder->getMeta('wpsg_mod_paypal_saleid');
		
			$amount = new \PayPal\Api\Amount();
			$amount->setCurrency($this->shop->get_option('wpsg_mod_paypal_currency'));
			$amount->setTotal($oOrder->getToPay(WPSG_BRUTTO));
				
			$refund = new \PayPal\Api\Refund();
			$refund->setAmount($amount);
				
			$sale = new \PayPal\Api\Sale();
			$sale->setId($sale_id);
				
			try
			{
						 
				$refundedSale = $sale->refund($refund, $this->getApiContext());
				
				$oOrder->setMeta('wpsg_mod_paypal_saleid', null);
				$oOrder->setMeta('wpsg_mod_paypal_paymentid', null);
				
				return true;
		
			}
			catch (Exception $ex)
			{
		
				$oOrder->log(__('PayPal Fehler', 'wpsg'), $ex->getMessage());
				return false;
		
			}
				
		} // public function stornoOrder($order_id)
		
		/**
		 * Gibt den API Context für alle API Anfragen zurück
		 */
		private function getApiContext()
		{
				
			if ($this->apiContext === false)
			{
		
				$this->apiContext = new \PayPal\Rest\ApiContext(new PayPal\Auth\OAuthTokenCredential(
					$this->shop->get_option('wpsg_mod_paypal_clientid'),
					$this->shop->get_option('wpsg_mod_paypal_secret')
				));
		
				if (!$this->isSandbox())
				{
						
					$this->apiContext->setConfig(array(
						'mode' => 'live'
					));
						
				}
		
			}
				
			return $this->apiContext;
				
		} // public function getApiContext()
		
		/**
		 * Gibt true/false zurück ob die Api auf die PayPal Sandbox zurückgreift
		 * @return boolean
		 */
		public function isSandbox()
		{
				
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_paypal_sandbox')))
			{
		
				return true;
		
			}
			else
			{
		
				return false;
		
			}
				
		} // public function isSandbox()
		
		/**
		 * Autoloader für die PayPal Klassen
		 */
		function spl_autoload($class)
		{
				
			if (substr($class, 0, 7) == "PayPal\\")
			{
					
				$path = WPSG_PATH_LIB.str_replace("\\", DIRECTORY_SEPARATOR, $class).'.php';
		
				if (file_exists($path)) require_once($path);
					
			} else if (substr($class, 0, 4) == "Psr\\") {
				
				$path = WPSG_PATH_LIB.str_replace("\\", DIRECTORY_SEPARATOR, $class).'.php';
				
				if (file_exists($path)) require_once($path);
				
			}
				
		} // function spl_autoload($class)
		
	} // class wpsg_mod_paypal extends wpsg_mod_basic

?>