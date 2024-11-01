<?php

	class wpsg_mod_auftragsbestaetigung extends wpsg_mod_basic 
	{ 	
		
		var $lizenz = 1;
		var $id = 70;

		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name 	= __('Auftragsbestätigung', 'wpsg');
			$this->group 	= __('Bestellung', 'wpsg');
			$this->desc 	= __('Erlaubt es in der Bestellverwaltung dem Kunden eine Auftragsbestätigung zu senden.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
		
			$this->shop->checkDefault('wpsg_auftragsbestaetigung_betreff', __('Ihre Auftragsbestätigung', 'wpsg'));
			
		} // public function install()
		
		/**
		 * zeigt das Formular zum Senden in der Bestellverwaltung
		 */
		public function order_view($order_id, &$arSidebarArray)
		{

			$arSidebarArray[$this->id] = array(
				'title' => $this->name,
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_auftragsbestaetigung/order_view.phtml', false)
			);

		} // public function order_view_content($order_id)
	 
		/**
		 * aktualisiert die Anzeige im Backend der Bestellverwaltung
		 */
		public function order_ajax()
		{ 

			$this->shop->checkEscape();
			
			$this->shop->view['auftrag_note'] = wpsg_sinput("text_field", $_REQUEST['auftrag_note']);

			$this->send(wpsg_q($_REQUEST['edit_id']));
			
			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&action=view&edit_id='.$_REQUEST['edit_id']);
			 
		} // public function order_ajax()
		
		/**
		 * zeigt das Formular zur Mailconfiguration
		 */
		public function admin_emailconf() 
		{ 
			
			echo wpsg_drawEMailConfig(
				'auftragsbestaetigung',
				__('E-Mail Auftragsbestätigung (Kunde)', 'wpsg'),
				__('Diese Mail bekommt der Kunde wenn eine Auftragsbestätigung versand wird.', 'wpsg')); 
			 
		} // public function admin_emailconf()
		
		/**
		 * speichert die mailconfiguration zum Modul
		 */
		public function admin_emailconf_save()
		{
			
			wpsg_saveEMailConfig("auftragsbestaetigung");
			 			
		} // public function admin_emailconf_save()
		
		/**
		 * sendet die Auftragsbestätigung und macht einen Eintrag im Order-Log
		 * @param unknown_type $order_id
		 */
		private function send($order_id)
		{
			
			$basket = new wpsg_basket();
			$basket->initFromDB($order_id);
			
			$this->shop->view['basket']= $basket->toArray(true);
			
			/*  Alt
			$arrShippingData = $this->db->fetchRow("SELECT 
														`shipping_vname`,
														`shipping_name`,
														`shipping_strasse`,
														`shipping_hausnr`,
														`shipping_plz`,
														`shipping_ort`,
														`shipping_land`,
														`shipping_firma`
													FROM 
														`".WPSG_TBL_ORDER."`
													WHERE 
														`id` = '".$order_id."'
													");
			*/
			$arrData = $this->db->fetchRow("SELECT
														`shipping_adress_id`
													FROM
														`".WPSG_TBL_ORDER."`
													WHERE
														`id` = '".$order_id."'
													");
			$arrAdress = $this->db->fetchRow("SELECT *
													FROM
														`".WPSG_TBL_ADRESS."`
													WHERE
														`id` = '".$arrData['shipping_adress_id']."'
													");
			$arrShippingData = array();
			$arrShippingData['shipping_vname'] = $arrAdress['vname'];
			$arrShippingData['shipping_name'] = $arrAdress['name'];
			$arrShippingData['shipping_strasse'] = $arrAdress['strasse'];
			$arrShippingData['shipping_nr'] = $arrAdress['nr'];
			$arrShippingData['shipping_plz'] = $arrAdress['plz'];
			$arrShippingData['shipping_ort'] = $arrAdress['ort'];
			$arrShippingData['shipping_land'] = $arrAdress['land'];
			$arrShippingData['shipping_firma'] = $arrAdress['firma'];
			
			$strShippingLand = $this->db->fetchRow("SELECT
														`name`
													FROM
														`".WPSG_TBL_LAND."`
													WHERE
														`id` = '".$arrShippingData['shipping_land']."'
													");
			
			$arrShippingData['shipping_land'] = $strShippingLand['name'];
			
			foreach ($arrShippingData as $shipping=>$value)
			{
				$this->shop->view['basket']['checkout'][$shipping] = $value;
			}
 
			$this->shop->view['order'] = $this->shop->cache->loadOrder($order_id);
			$this->shop->view['customer'] = $this->shop->cache->loadKunden($this->shop->view['order']['k_id']);
			$this->shop->view['o_id'] = $order_id;
			 
			// Damit customer.phtml die richtigen Variablen hat ist folgendes leider notwendig
			$this->shop->view['basket']['checkout']['knr'] = $this->shop->view['customer']['knr'];
			$this->shop->view['basket']['checkout']['onr'] = $this->shop->view['order']['onr'];
			$this->shop->view['basket']['checkout']['datum'] = strtotime($this->shop->view['order']['cdate']);			
			
			$empfaenger = $this->shop->view['customer']['email'];
			$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_auftragsbestaetigung/auftragsbestaetigung.phtml', false);

			if ($this->shop->get_option('wpsg_htmlmail') === '1')
			{
				
				$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_auftragsbestaetigung/auftragsbestaetigung_html.phtml', false);
				
			}
			else
			{
				
				$mail_html = false;
				
			}
			
			$header['from'] = 'From: '.$this->shop->get_option('wpsg_auftragsbestaetigung_absender').' <'.$this->shop->get_option('wpsg_global_absender').'>'."\r\n" .
			    'Reply-To: '.$this->shop->get_option('wpsg_global_absender')."\r\n";
			
			if ($this->shop->get_option('wpsg_auftragsbestaetigung_cc') != "")
				$header['bcc'] .= 'BCC: '.$this->shop->get_option('wpsg_auftragsbestaetigung_cc')."\r\n";
				
			if ($this->shop->get_option('wpsg_auftragsbestaetigung_bcc') != "")
				$header['cc'] .= 'CC: '.$this->shop->get_option('wpsg_auftragsbestaetigung_bcc')."\r\n";
			
			$this->shop->addBackendMessage(__('Auftragsbestätigung wurde versendet.', 'wpsg'));

			if (trim($this->shop->view['order']['language']) != '') $this->shop->setTempLocale($this->shop->view['order']['language']);
			
			$anhang = array();
			
			if ($this->shop->get_option('wpsg_widerrufsformular_orderconfirm') === '1' && wpsg_isSizedString($this->shop->get_option('wpsg_revocationform')))
			{
			
				$revocationFile = WPSG_PATH_UPLOADS.'wpsg_revocation/'.$this->shop->get_option('wpsg_revocationform');
			
				if (file_exists($revocationFile) && is_file($revocationFile))
				{
			
					$anhang[] = $revocationFile;
			
				}
			
			}
			
			$this->shop->sendMail($mail_text, $empfaenger, 'auftragsbestaetigung', $anhang, $order_id, false, $mail_html);
			
			$this->shop->restoreTempLocale();
			
			$this->db->importQuery(WPSG_TBL_OL, array(
				"cdate" => "NOW()",
				"o_id" => wpsg_q($order_id),
				"title" => wpsg_translate(__('Auftragsbestätigung versendet an:#1#', 'wpsg'), $empfaenger),
				"mailtext" => wpsg_q($mail_text)
			));
			
			$this->shop->setOrderStatus($_REQUEST['edit_id'], 1, false);
			
		} // public function send($order_id)
		
	}
	
?>