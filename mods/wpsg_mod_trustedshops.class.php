<?php

	/**
	 * Modul zum einbinden von Trusted Shops
	 * @author daniel
	 */
	class wpsg_mod_trustedshops extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 5000;
		var $hilfeURL = 'http://wpshopgermany.maennchen1.de/?p=3751'; 
		
		var $cache_file;
		var $cache_url;
		var $cache_timeout = 10800;
		var $trusted_shop_url = 'https://www.trustedshops.com/bewertung/widget/widgets/';
		
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Trusted Shops', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht die Integration von Trusted Shops.', 'wpsg');
			
			$this->cache_file = $this->shop->getPublicDir().'trusted_shops_siegel.gif';
			$this->cache_url = $this->shop->getPublicDir(true).'trusted_shops_siegel.gif';
			
		} // public function __construct();
		
		public function install()
		{
			
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/**
			 * Bestelltabell erweitern für Option "Bewertung teilnehmen"
			 */ 
			$sql = "CREATE TABLE ".WPSG_TBL_ORDER." (
		   		wpsg_mod_trustedshops_set INT(1) DEFAULT '0' NOT NULL
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	   	 
   			dbDelta($sql); 
			
			$this->shop->checkDefault('wpsg_mod_trustedshops_siegelcache', '1');
			$this->shop->checkDefault('wpsg_mod_trustedshops_siegeltitle', __('Händlerbewertungen einsehen', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_trustedshops_customerset', '1');
			$this->shop->checkDefault('wpsg_mod_trustedshops_customerset_preset', '1');
			$this->shop->checkDefault('wpsg_mod_trustedshops_orderdone', '2');
			$this->shop->checkDefault('wpsg_mod_trustedshops_orderdonetitle', __('Klicken Sie hier, um eine Bewertung des Händlers abzugeben.', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_trustedshops_customermail', '2');
			$this->shop->checkDefault('wpsg_mod_trustedshops_customermailtitle', __('Bewerten Sie Ihr Einkaufserlebnis bei Trusted Shops! Vielen Dank!', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_trustedshops_invoicemail', '2');
			$this->shop->checkDefault('wpsg_mod_trustedshops_invoicemailtitle', __('Vergessen Sie nicht Ihr Einkaufserlebnis bei Trusted Shops zu bewerten! Vielen Dank!', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_trustedshops_mail_betreff', 'Bitte bewerten Sie ihre Bestellung.', false, true);
			
			$this->shop->checkDefault('wpsg_mod_trustedshops_reminder', '1');
			$this->shop->checkDefault('wpsg_mod_trustedshops_reminderDays', '60');
			$this->shop->checkDefault('wpsg_mod_trustedshops_state', array('110'));
						
		} // public function install()
				
		public function settings_edit()
		{
			
			$this->shop->view['siegelURL'] = $this->getSiegelURL();
			
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_trustedshops_lastExport'))) $this->shop->view['lastExport'] = date('d.m.Y', $this->shop->get_option('wpsg_mod_trustedshops_lastExport'));
			else $this->shop->view['lastExport'] = __('Noch nicht ausgeführt.', 'wpsg'); 
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/settings_edit.phtml');
			
		} // public function settings_edit()
				
		public function order_done(&$order_id, &$done_view) 
		{ 

			$this->shop->view['wpsg_mod_trustedshops']['vote'] = false;
			
			// Möchte der Kunde überhaupt an der Bewertung teilnehmen
			if ($this->shop->get_option('wpsg_mod_trustedshops_orderdone') == '2')
			{
				
				if ($this->shop->view['order']['wpsg_mod_trustedshops_set'] != '1') $this->shop->view['wpsg_mod_trustedshops']['vote'] = true;;
				
			}
			else if ($this->shop->get_option('wpsg_mod_trustedshops_orderdone') == '1')
			{
				
				$this->shop->view['wpsg_mod_trustedshops']['vote'] = true;
				
			}
			
			if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_trustedshops_orderdonelogo')))
			{
				
				$this->shop->view['wpsg_mod_trustedshops']['voteurl'] = $this->shop->get_option('wpsg_mod_trustedshops_orderdonelogo');
				
			}
			else
			{
				
				$this->shop->view['wpsg_mod_trustedshops']['voteurl'] = $this->shop->getRessourceURL('mods/mod_trustedshops/gfx/rate_now_button_de.png');
				
			}
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/order_done.phtml');
						
		} // public function order_done(&$order_id)
				
		public function mail_aftercalculation(&$order_id) 
		{ 
			 
			if (!in_array($this->shop->get_option('wpsg_mod_trustedshops_customermail'), array(1, 2))) return;
						
			// Möchte der Kunde überhaupt an der Bewertung teilnehmen
			if ($this->shop->get_option('wpsg_mod_trustedshops_customermail') == '2')
			{
				
				if ($this->shop->view['order']['wpsg_mod_trustedshops_set'] != '1') return;
				
			}
			
			$this->shop->view['order'] = $this->shop->cache->loadOrder($order_id);
			$this->shop->view['customer'] = $this->shop->cache->loadKunden($this->shop->view['order']['k_id']);
			
			if ($this->shop->htmlMail === true)
			{
				
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/mail_aftercalculation_html.phtml');
				
			}
			else
			{
			
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/mail_aftercalculation.phtml');
				
			}
			
		} // public function mail_aftercalculation(&$order_id)
				
		public function wpsg_mod_rechnungen_mail() 
		{
			
			if (!in_array($this->shop->get_option('wpsg_mod_trustedshops_invoicemail'), array(1, 2))) return;
						
			// Möchte der Kunde überhaupt an der Bewertung teilnehmen
			if ($this->shop->get_option('wpsg_mod_trustedshops_invoicemail') == '2')
			{
				
				if ($this->shop->view['order']['wpsg_mod_trustedshops_set'] != '1') return;
				
			}
			  
			if ($this->shop->htmlMail === true)
			{

				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/invoicemail_html.phtml');
				
			}
			else
			{
			
				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/invoicemail.phtml');
				
			}
			
		} // public function wpsg_mod_rechnungen_mail
				
		public function checkGeneralBackendError()
		{
			
			// Wenn deaktiviert dann keine Meldung
			if (!wpsg_isSizedInt($this->shop->get_option('wpsg_mod_trustedshops_reminder'))) return;
			
			try
			{
			
				// Wenn keine Elemente, dann keine Meldung
				$expdata = $this->getExportData();
				if (!wpsg_isSizedArray($expdata)) return;
				
				$current_timestamp = $this->db->fetchOne("SELECT UNIX_TIMESTAMP()");
				$last_export = $this->shop->get_option('wpsg_mod_trustedshops_lastExport');
				if ($last_export === false) $last_export = $current_timestamp;
		 
				$days_lastExport = floor(($current_timestamp - $last_export) / (60 * 60 * 24));
				
				if ($days_lastExport > $this->shop->get_option('wpsg_mod_trustedshops_reminderDays') || $days_lastExport === 0.0)
				{
					
					if ($last_export == $current_timestamp)
					{
						
						$message = wpsg_translate(
							'nohspc_'.__('Sie haben noch keinen Review Collector-Export gemacht. <a href="#1#">Jetzt starten.</a>', 'wpsg'),						
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_trustedshops'
						);
						
					}
					else
					{
					
						$message = wpsg_translate(
							'nohspc_'.__('Sie haben seit #1# Tagen keinen Review Collector-Export gemacht. <a href="#2#">Jetzt starten.</a>', 'wpsg'), 
							$days_lastExport,
							WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_trustedshops'
						);
						
					}
					
					$message .= '<p style="float:right;">'.wpsg_translate(
						__('<a href="#1#">Klicken Sie hier, um die Meldung auszublenden.</a>', 'wpsg'),
						WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_trustedshops&noheader=1&do=hideMessage&wpsg_redirect='.rawurlencode($_SERVER['REQUEST_URI'])
					).'</p><div class="wpsg_clearer"></div><br />';
					
					$this->shop->addBackendError($message, 'wpsg_mod_trustedshops_notice', false);
					
				} 
				
			} 
			catch (\wpsg\Exception $e)
			{
			
				// Nichts machen, kann bei fehlenden DB Spalten passieren
				
			}
			
		}
		
		public function settings_save()
		{

			foreach($_REQUEST['wpsg_mod_trustedshops_state'] as $k => $v)
				$_REQUEST['wpsg_mod_trustedshops_state'][$k] = wpsg_sinput("key", $v);

			$this->shop->update_option('wpsg_mod_trustedshops_shopid', $_REQUEST['wpsg_mod_trustedshops_shopid'], false, false, "key");
			
			$this->shop->update_option('wpsg_mod_trustedshops_siegelcache', $_REQUEST['wpsg_mod_trustedshops_siegelcache'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_siegeltitle', $_REQUEST['wpsg_mod_trustedshops_siegeltitle'], false, false, "text_field");
			$this->shop->addTranslationString('wpsg_mod_trustedshops_siegeltitle', wpsg_sanitize("text_field", $_REQUEST['wpsg_mod_trustedshops_siegeltitle']) ?: $this->shop->get_option('wpsg_mod_trustedshops_siegeltitle'));
			$this->shop->update_option('wpsg_mod_trustedshops_customerset', $_REQUEST['wpsg_mod_trustedshops_customerset'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_customerset_preset', $_REQUEST['wpsg_mod_trustedshops_customerset_preset'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_warranty', $_REQUEST['wpsg_mod_trustedshops_warranty'], false, false, "key");
			
			$this->shop->update_option('wpsg_mod_trustedshops_orderdone', $_REQUEST['wpsg_mod_trustedshops_orderdone'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_orderdonetitle', $_REQUEST['wpsg_mod_trustedshops_orderdonetitle'], false, false, "text_field");
			$this->shop->addTranslationString('wpsg_mod_trustedshops_orderdonetitle', wpsg_sanitize("text_field", $_REQUEST['wpsg_mod_trustedshops_orderdonetitle']) ?: $this->shop->get_option('wpsg_mod_trustedshops_orderdonetitle'));
			$this->shop->update_option('wpsg_mod_trustedshops_orderdonelogo', $_REQUEST['wpsg_mod_trustedshops_orderdonelogo'], false, false, "text_field");
			
			$this->shop->update_option('wpsg_mod_trustedshops_customermail', $_REQUEST['wpsg_mod_trustedshops_customermail'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_customermailtitle', $_REQUEST['wpsg_mod_trustedshops_customermailtitle'], false, false, "text_field");
			$this->shop->addTranslationString('wpsg_mod_trustedshops_customermailtitle', wpsg_sanitize("text_field", $_REQUEST['wpsg_mod_trustedshops_customermailtitle']) ?: $this->shop->get_option('wpsg_mod_trustedshops_customermailtitle'));
			
			if ($this->shop->hasMod('wpsg_mod_rechnungen')) 
			{
				
				$this->shop->update_option('wpsg_mod_trustedshops_invoicemail', $_REQUEST['wpsg_mod_trustedshops_invoicemail'], false, false, "key");
				$this->shop->update_option('wpsg_mod_trustedshops_invoicemailtitle', $_REQUEST['wpsg_mod_trustedshops_invoicemailtitle'], false, false, "text_field");
				$this->shop->addTranslationString('wpsg_mod_trustedshops_invoicemailtitle', $_REQUEST['wpsg_mod_trustedshops_invoicemailtitle'], false, false, "text_field");
				
			}
			
			$this->shop->update_option('wpsg_mod_trustedshops_reminder', $_REQUEST['wpsg_mod_trustedshops_reminder'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_reminderDays', $_REQUEST['wpsg_mod_trustedshops_reminderDays'], false, false, "key");
			$this->shop->update_option('wpsg_mod_trustedshops_state', $_REQUEST['wpsg_mod_trustedshops_state']);
			
			@unlink($this->cache_file);
			
		} // public function settings_save()
				
		public function checkout_customer_inner()
		{
			 
			if ($this->shop->get_option('wpsg_mod_trustedshops_customerset') != '1') return;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/checkout_customer_inner.phtml');
			
		} // public function checkout_customer_inner()
		
		public function clearSession() 
		{
			 
			unset($_SESSION['wpsg']['wpsg_mod_trustedshops_set']);
			
		} // public function clearSession()
		
		public function calculation_saveOrder(&$oCalculation, $arCalculation, &$db_data, $finish_order) {
			
			if (wpsg_isSizedInt($_SESSION['wpsg']['checkout']['wpsg_mod_trustedshops_set'])) {
				
				$db_data['wpsg_mod_trustedshops_set'] = '1';
				
			}
			
		}
		 
		public function order_view($order_id, &$arSidebarArray)
		{ 

			$arSidebarArray[$this->id] = array(
				'title' => $this->name,
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/order_view_sidebar.phtml', false)
			);
			
		} // public function order_view_sidebar(&$order_id)
		
		public function be_ajax()
		{
			 
			if ($_REQUEST['do'] == 'export')
			{
								 
				$arData = $this->getExportData();
				
				if (wpsg_isSizedArray($arData))
				{
				
					$tmp_file = $this->shop->getTempName('wpsg_mod_trustedshops_export.csv');				
					$fp = fopen($tmp_file, 'w');
					
					foreach ($arData as $d)
					{
						
						fputcsv($fp, $d);
						
					}
					
					fclose($fp);
													
					$this->shop->update_option('wpsg_mod_trustedshops_lastExport', $this->db->fetchOne("SELECT UNIX_TIMESTAMP()"));
					
					header("Content-type: text/csv");
					header("Content-Disposition: attachment; filename=reviewCollector_".date('dmY', time()).".csv");
					header("Pragma: no-cache");
					header("Expires: 0");
					
					echo file_get_contents($tmp_file);

					die();
					 
				}
				
			}
			else if ($_REQUEST['do'] == 'hideMessage')
			{
				
				$this->shop->update_option('wpsg_mod_trustedshops_reminder', false);
				
				$this->shop->addBackendMessage(__('Ihnen werden keine Benachrichtigungen für den Review Collector mehr angezeigt.', 'wpsg'));
				
				$this->shop->redirect($_REQUEST['wpsg_redirect']);
								
			}
			
		} // public function be_ajax()
		
		public function load()
		{
			
			require_once(dirname(__FILE__).'/mod_trustedshops/wpsg_mod_trustedshops_widget.class.php');

			add_action('widgets_init', function() {  return register_widget("wpsg_mod_trustedshops_widget"); } );
			
		} // public function load()
		
		public function order_ajax() 
		{

			$_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);

			if ($_REQUEST['do'] == 'mail')
			{
				
				$this->shop->view['order'] = $this->shop->cache->loadOrder($_REQUEST['edit_id']);
				$this->shop->view['customer'] = $this->shop->cache->loadKunden($this->shop->view['order']['k_id']);
								
				$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/mail.phtml', false);

				if ($this->shop->get_option('wpsg_htmlmail') === '1')
				{
					
					$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/mail_html.phtml', false);
					
				}
				else
				{
					
					$mail_html = false;
					
				}
				
				list($subject, $mailtext) = $this->shop->sendMail($mail_text, $this->shop->view['customer']['email'], 'mod_trustedshops_mail', array(), $_REQUEST['edit_id'], $this->shop->view['order']['k_id'], $mail_html);
				
				// Ins Bestelllog eintragen
				$this->db->ImportQuery(WPSG_TBL_OL, array(
					'o_id' => wpsg_q($_REQUEST['edit_id']),
					'cdate' => 'NOW()',
					'title' => wpsg_q($subject),
					'mailtext' => wpsg_q($mailtext)
				));
				
				$this->shop->addBackendMessage(__('E-Mail mit dem Bewertungslink wurde erfolgreich an den Kunden gesendet.', 'wpsg'));
				
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&action=view&edit_id='.$_REQUEST['edit_id']);
				
			}
			
		} // public function order_ajax()
		 
		public function admin_emailconf_save()
		{
			
			wpsg_saveEMailConfig("mod_trustedshops_mail");
			 
		} // public function admin_emailconf_save()
		
		public function admin_emailconf() 
		{ 
			
			echo wpsg_drawEMailConfig(
				'mod_trustedshops_mail',
				__('E-Mail mit dem Trusted Shops Bewertungslink an den Kunden', 'wpsg'),
				__('Diese E-Mail kann aus der Bestellansicht im Backend manuell an den Kunden versendet werden.', 'wpsg')); 
			 
		} // public function admin_emailconf()
		 
		/* Modulfunktionen */
		
		/**
		 * Gibt die Daten für den ReviewCollector Export zurück
		 */
		public function getExportData() 
		{
			
			// Keine Bestellzustände aktiviert, dann auch nix exportieren
			if (!wpsg_isSizedArray($this->shop->get_option('wpsg_mod_trustedshops_state'))) return array();
			
			$strQueryWHERE = "";
						
			if (wpsg_isSizedInt($this->shop->get_option('wpsg_mod_trustedshops_lastExport')))
			{
				
				$strQueryWHERE .= " AND UNIX_TIMESTAMP(O.`cdate`) > '".$this->shop->get_option('wpsg_mod_trustedshops_lastExport')."' ";
				
			}
			
			$strQuery = "
				SELECT
					O.`onr`, 
					K.`email`, A.`vname`, A.`name`
				FROM
					`".WPSG_TBL_ORDER."` AS O
						LEFT JOIN `".WPSG_TBL_KU."` AS K ON (K.`id` = O.`k_id`)
						LEFT JOIN `".WPSG_TBL_ADRESS."` AS A ON (A.`id` = K.`adress_id`)
				WHERE
					O.`wpsg_mod_trustedshops_set` = '1' AND
					O.`status` IN (".implode(",", $this->shop->get_option('wpsg_mod_trustedshops_state')).")							
					".$strQueryWHERE."
				ORDER BY 
					O.`cdate` DESC
			";
		 
			$arData = $this->db->fetchAssoc($strQuery);

			return $arData;
			
		} // public function getExportData() 
		
		public function getSiegelURL()
		{
			
			if ($this->shop->get_option('wpsg_mod_trustedshops_siegelcache') == '1')
			{
				 
				if ($this->cachecheck())
				{
					
					return $this->cache_url;
					
				}
				else
				{

					$url = $this->trusted_shop_url.$this->shop->get_option('wpsg_mod_trustedshops_shopid').'.gif';
					$url_contents = $this->shop->get_url_content($url);
					
					if (wpsg_isSizedString($url_contents))
					{
					
						// Bild Cachen
						file_put_contents($this->cache_file, $url_contents);
						
					}

					if (file_exists($this->cache_file)) return $this->cache_url;
					else return $this->trusted_shop_url.$this->shop->get_option('wpsg_mod_trustedshops_shopid').'.gif';
					
				}
				
			}
			else
			{

				return $this->trusted_shop_url.$this->shop->get_option('wpsg_mod_trustedshops_shopid').'.gif';
				
			}
			
		} // public function getSiegelURL()
				
		private function cachecheck() 
		{
			
 			if (file_exists($this->cache_file)) 
 			{
 				
 				$timestamp = filemtime($this->cache_file);
 
 				if (time() - $timestamp < $this->cache_timeout) 
 				{
 					
 					return true;
 				} 
 				else
 				{
 
 					return false;
 					
 				}
 				
 			} 
 			else
 			{
 
 				return false;
 				
 			}
 		
		} // private function cachecheck()  
				
	} // class wpsg_mod_trustedshops extends wpsg_mod_basic

?>