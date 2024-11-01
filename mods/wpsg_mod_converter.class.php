<?php

require_once(WPSG_PATH_MOD. 'mod_converter/M1_Converter_update.php');
require_once(WPSG_PATH_MOD. 'mod_converter/M1_progressbar.class.php');

	/**
	 * Dieses Modul ermöglicht die Konvertierung der Daten von WPSG3 nach WPSG4
	 * @author hartmut
	 */
	class wpsg_mod_converter extends wpsg_mod_basic
	{
		
		var $id = 5300;
		var $hilfeURL = 'https://doc.wpshopgermany.de/4/m1-converter/';
		
		var $m1cauto = 0;
		var $m1cconv;
		var $m1cpb;
		var $status = 0;
		
		/**
		 * Constructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Konverter', 'wpsg');
			$this->group = __('Sonstiges', 'wpsg');
			$this->desc = __('Ermöglicht die Konvertierung von WPSG3 nach WPSG4.', 'wpsg');
			
			$this->m1cpb = new progressbar1(0, 100, 300, 30);
			$this->m1cconv = new M1_Converter_update($this->m1cpb);
			
		} // public function __construct()
		
		/** Initiiert das Modul / Wird nur aufgerufen wenn das Modul aktiv ist */
		public function init()
		{
			add_action( 'admin_init', array($this, 'restrict_admin_with_redirect'), 1 );
			
		}
		
		function wpsg_add_pages($default_page)
		{
			
			add_submenu_page(
					null,
					'M1-Konverter',
					'M1-Konverter',
					'manage_options',
					'M1_Converter_dispatch',
					array($this, 'M1_Converter_dispatch'));
			
		}
		
		function restrict_admin_with_redirect() {
			
			if (isset($_REQUEST['noheader'])) return;
			
			if (isset($_REQUEST['page']) && preg_match('/^wpsg/i', $_REQUEST['page']) && $_REQUEST['page'] != 'M1_Converter_dispatch')
			{
			
				if (is_admin() && $this->shop->get_option('wpsg_mod_converter') != false)
				{
					
					//http://shop4.home/wp-admin/admin.php?page=M1_Converter_dispatch
					//$this->shop->update_option('wpsg_mod_converter', false);
					$this->shop->update_option('wpsg_mod_converter_auto', 0);
					$this->shop->update_option('wpsg_mod_converter_status', 0);
					wp_redirect(WPSG_URL_WP.'wp-admin/admin.php?page=M1_Converter_dispatch');
				}
			}
			
		}
		
		/**
		 * Wird von wpsg_enqueue_scripts aufgerufen
		 */
		public function wpsg_enqueue_scripts()
		{
			if (is_admin()) {
				//wp_enqueue_script('wpsg_mod_converter', $this->shop->getRessourceURL('js/shariff/shariff.min.js'));
				//wp_enqueue_style ('wpsg_mod_converter_style', $this->shop->getRessourceURL('js/shariff/shariff.min.css'));
				
			}
			
		}
		
		/**
		 * Prüft den Status der Konvertierung und gibt true zurück,
		 * wenn alle Schritte durchgeführt wurden.
		 */
		private function checkStatus($step)
		{
			
			$this->status = $this->shop->get_option('wpsg_mod_converter_status');
			$a = bindec($step);
			$this->status = $this->status | $a;
			$this->shop->update_option('wpsg_mod_converter_status', $this->status);
			$this->m1cauto = $this->shop->get_option('wpsg_mod_converter_auto');
			
			// 111 1111 1111
			if (($this->status == 2047) && ($this->m1cauto == 0)) {
				$this->shop->update_option('wpsg_mod_converter', false);
				//$this->shop->update_option('wpsg_mod_converter_status', 0);
				$this->shop->update_option('wpsg_mod_converter_done', 1);
				sleep(3);
				wp_redirect(WPSG_URL_WP.'wp-admin/');
				
				//$_REQUEST['action'] = 'ende';
				//$this->M1_Converter_dispatch();
			}
			
		}
		
		
		/**
		 * Funktionsverteiler
		 *
		 * Je nach der gewählten Aktion wird die entsprechende Funktion aufgerufen.
		 *
		 */
		function M1_Converter_dispatch()
		{
			//global $m1cpb, $m1cconv;
			
			//parent::dispatch();
			
			if (!isset($_REQUEST['action'])) {
				
				$this->M1_Converter_preshow();
				
			}
			
			//$m1cconv = new M1_Converter($GLOBALS['m1cpb']);
			
			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'auto')
			{
				
				//$this->m1cauto = true;
				$this->shop->update_option('wpsg_mod_converter_auto', 1);
				//$this->M1_Converter_auto();
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'show')
			{
				
				$this->M1_Converter_show();
				//$this->shop->update_option('wpsg_mod_converter', false);
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'ende')
			{
				
				$this->shop->update_option('wpsg_mod_converter', false);
				//$this->shop->update_option('wpsg_mod_converter_status', 0);
				$this->shop->update_option('wpsg_mod_converter_done', 1);
				//$this->shop->update_option('wpsg_mod_converter_auto', false);
				//wp_redirect('/wp-admin/admin.php');
				//wp_redirect('/wp-admin/admin.php?page=wpsg-Admin&action=module');
				//http://shop4.home/wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_converter
				die("FIN");
				//wp_redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_converter');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'end')
			{
				
				$this->shop->update_option('wpsg_mod_converter', false);
				$this->shop->update_option('wpsg_mod_converter_done', 1);
				$auto = $this->shop->get_option('wpsg_mod_converter_auto');
				$status = $this->shop->get_option('wpsg_mod_converter_status');
				//die('Konverter beendet. Statuscode: '.$auto.'-'.$status);
				$this->shop->addBackendMessage(__('Konverter beendet. Statuscode: '.$auto.'/'.$status));
				sleep(1);
				wp_redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'allgemein')
			{
				
				$this->m1cconv->M1_Converter_allgemein();
				$this->checkStatus('1'); 
					
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'kunden')
			{
				
				$this->m1cconv->M1_Converter_kunden();
				$this->checkStatus('10');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'order')
			{
				
				$this->m1cconv->M1_Converter_order();
				$this->checkStatus('100');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'anrede')
			{
				
				$this->m1cconv->M1_Converter_anrede();
				$this->checkStatus('1000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'media')
			{
				
				$this->m1cconv->M1_Converter_media();
				$this->checkStatus('10000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'prod0')
			{
				
				die($this->m1cconv->M1_Converter_prod0());
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'prod1')
			{
				
				//die($this->m1cconv->M1_Converter_prod0());
				$this->m1cconv->M1_Converter_prod1();
				$this->checkStatus('100000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'preis')
			{
				
				$this->m1cconv->M1_Converter_preis();
				$this->checkStatus('1000000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'category')
			{
				
				$this->m1cconv->M1_Converter_category();
				$this->checkStatus('10000000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'dlplus')
			{

				$this->m1cconv->M1_Converter_dlplus();
				$this->checkStatus('100000000');

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'lief')
			{

				$this->m1cconv->M1_Converter_lief();
				$this->checkStatus('100000000000');

			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'rech')
			{
				
				$this->m1cconv->M1_Converter_rech();
				$this->checkStatus('1000000000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'export')
			{
				
				$this->m1cconv->M1_Converter_export();
				$this->checkStatus('10000000000');
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'zip')
			{
				
				$this->m1cconv->M1_Converter_zip();
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'unzip')
			{
				
				$this->m1cconv->M1_Converter_unzip();
				
			}
			else if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'test')
			{
				
				$this->m1cconv->M1_Converter_test();
				
			}
			
		}	// function M1_Converter_dispatch()
		
		/**
		 * Startseite
		 *
		 * Prüfen, ob der Shop als Modul installiert ist und eine
		 * richtige Versionsnummer hat.
		 *
		 */
		function M1_Converter_preshow() {
			
			global $m1cpb;
			
			?>
    
	<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />

	<div class="m1_converter_admin_content" >
    	<div class="panel panel-default" >
    		<div class="panel-heading clearfix" >
        		<h2 class="panel-title" >M1-Konverter</h2>
			</div>
			
			<div class="panel-body" >
				
				<label ><?php echo __('Der M1-Konverter integriert die Daten in die neue wpShopGermany Version 4.', 'wpsg'); ?></label>
	        	<br /><br />
				
		        <?php
		        $wpsgi = true;

		        if ((isset($GLOBALS['wpsg_sc']) && is_object($GLOBALS['wpsg_sc'])))
		        {
		        	echo __('WPSG-Installation gefunden in: '.admin_url(), 'wpsg');
					$wpsgi = true;
		        }
				else
				{
					echo __('<br />Keine WPSG-Installation gefunden!', 'wpsg');
					$wpsgi = false;
				}

				if (!defined('WPSG_VERSION')) define('WPSG_VERSION', '0.0.0');
			
				echo __('<br /> WPSG-Version: '.WPSG_VERSION.' ', 'wpsg');
				$ver = substr(WPSG_VERSION, 0, 1);
				$res = version_compare(WPSG_VERSION, '3.99.0');
				// -1: erste Version ist kleiner
				// 0:  die Versionen sind gleich
				// 1:  die zweite Version ist kleiner
				if ($res >= 0)
				{
					echo __('<br /> WPSG-Version OK.', 'wpsg');
					$wpsgi = true;
				}
				else
				{
					echo __('<br />WPSG-Version zu niedrig!', 'wpsg');
					$wpsgi = false;
				}
				
				echo '<br /><br />';
				
		        if ($wpsgi == true) {
				?>
		        	<a class="m1c_button " title="Konvertieren von WPSG Version 3" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=show'); ?>">Konvertieren von WPSG Version 3</a>
				
				<?php 
					// wpsg_mod_productvariants aktivieren, wenn wpsg_mod_varianten aktiv ist
					// Option nicht vorhanden=false, Option ohne Value=empty
					if (($this->shop->get_option('wpsg_mod_varianten') != false) && !empty($this->shop->get_option('wpsg_mod_varianten'))) {

						$this->shop->update_option('wpsg_mod_productvariants', time());
                        
						$this->shop->loadModule();
						$this->shop->callMod('wpsg_mod_productvariants', 'install');
						
                    }
					
					$mpvar = $this->shop->get_option('wpsg_mod_productvariants');
				
				?>
				<?php } ?>
    		</div>
		</div>
	</div>
	<div id="wpsg-bs"></div>
	
<?php }
/**
 * Hauptseite von der die einzelnen Funktionen aufgerufen werden
 *
 * Analyse der Datenbank und anzeigen der wichtigsten Informationen.
 * Buttons für den Aufruf der einzelnen Funktionen und für
 * einen automatischen Durchlauf durch die gesamte Konvertierung.
 *
 *
 */
function M1_Converter_show() {
	global $m1cpb, $m1cconv;
	//962: deactivate_plugins(WPSG_FOLDERNAME.'/wpshopgermany.php');  
	?>
    
	<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/views/js/bootstrap-3.3.6-dist/css/bootstrap.css'; ?>" type="text/css" media="all" />
	<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/views/js/bootstrap-3.3.6-dist/css/bootstrap-glyphfont.css'; ?>" type="text/css" media="all" />
	<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />

	<script src="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/views/js/bootstrap-3.3.6-dist/js/bootstrap.min.js'?>"></script>

	<div class="m1_converter_admin_content" >
    	<div class="panel panel-default" >
    		<div class="panel-heading clearfix" >
        		<h2 class="panel-title" >M1-Konverter</h2>
			</div>
			
			<div class="panel-body" >
				
				<label ><?php echo __('Der M1-Konverter wandelt die bestehenden Daten aus Ihrer wpShopGermany Version 3 um und integriert die Daten in die neue wpShopGermany Version 4.', 'wpsg'); ?></label>
	        	<br /><br />

		        <?php
		        $wpsgi = true;
		        
		        if ((isset($GLOBALS['wpsg_sc']) && is_object($GLOBALS['wpsg_sc'])))
		        {
		        	echo __('WPSG-Installation gefunden in: '.admin_url(), 'wpsg');
					$wpsgi = true;
		        }
				else
				{
					echo __('<br />Keine WPSG-Installation gefunden!', 'wpsg');
					$wpsgi = false;
				}

				if (!defined('WPSG_VERSION')) define('WPSG_VERSION', '0.0.0');
			
				echo __('<br /> WPSG-Version: '.WPSG_VERSION.' ', 'wpsg');
				$ver = substr(WPSG_VERSION, 0, 1);
				$res = version_compare(WPSG_VERSION, '3.99.0');
				// -1: die erste Version ist kleiner
				// 0:  die Versionen sind gleich
				// 1:  die zweite Version ist kleiner
				if ($res >= 0) 
				{
					echo __('<br /> WPSG-Version OK.', 'wpsg');
					$wpsgi = true;
				}
				else
				{
					echo __('<br />WPSG-Version zu niedrig!', 'wpsg');
					$wpsgi = false;
				}
				
		        if ($wpsgi == true) {
				
				$db = $GLOBALS['wpsg_db'];
		
				echo '<br />';
				/*
				$kunden = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_KU."` ORDER BY `id` ASC");
				echo __('<br />'.count($kunden).' Kunden', 'wpsg');
		
				$orders = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDER."` ORDER BY `id` ASC");
				echo __('<br />'.count($orders).' Bestellungen', 'wpsg');
				*/
				
				$products = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` ORDER BY `id` ASC");
				$i1 = 0;
				$i2 = 0;

				$cnt1 = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDER."`");
				$cnt2 = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_KU."`");
				echo __('<br />'.$cnt2.' Kunden', 'wpsg');
				echo __('<br />'.$cnt1.' Bestellungen', 'wpsg');
				
				foreach ($products as $p)
				{
					$arPic = $this->m1cconv->getProduktBilder($p['id'], false);
					$i1++;
					$i2 = $i2 + count($arPic);
				}
				echo __('<br />'.$i1.' Produkte mit '.$i2.' Bildern', 'wpsg');
		
				echo __('<br /><br />Bilderverzeichnis für Produkt 1: '.$this->m1cconv->getPicPath(1), 'wpsg');
		
				$status = $this->shop->get_option('wpsg_mod_converter_status');
				
        		?>
        		<br /><br />
        		<a id="idauto" class="m1c_button" title="Auto" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=auto'); ?>">Automatisch</a>
        		Ausführen der Schritte Allgemein, Kunden, Bestellungen bis Exportprofile
        		
		        <br /><br />
		        <label ><?php echo __('Wählen Sie die gewünschte Aktion aus. Abhängig vom Umfang kann die Übertragung ein paar Minuten dauern. Während dieser Zeit ist es ratsam, keine Veränderungen an der Instanz vorzunehmen oder andere Prozesse zeitgleich abzuarbeiten.', 'wpsg'); ?></label>
		        <br /><br />
        
				<div>
					<div style="background:white;width:10px;vertical-align:top;margin-top:10px;height:490px;display:inline-block;">
					<canvas id="sprogress" ></canvas>
					</div>
					<div style="background:white;width:8px;height:500px;display:inline-block;"></div>
					<div style="display:inline-block;width:90%;">

			        <a class="m1c_button" title="Allgemeine Konfigurationen" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=allgemein'); ?>">Allgemein</a>
					<a href="#" data-wpsg-tip="M1C-Allgemein" 
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Allgemeine Konfigurationen 
					<span id="idallgemein" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 1) == 1) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Konvertieren der Kundenadressen" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=kunden'); ?>">Kunden</a>
					<a href="#" data-wpsg-tip="M1C-Kunden" 
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Konvertieren der Kundenadressen
					<span id="idkunden" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 2) == 2) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Konvertieren der Bestellungen" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=order'); ?>">Bestellungen</a>
					<a href="#" data-wpsg-tip="M1C-Bestellungen"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Konvertieren der Bestellungen, Rechnungsadresse und Lieferadresse
					<span id="idorder" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 4) == 4) echo 'style="display:inline-block;"'; ?> ></span><br />
					

			        <a class="m1c_button" title="Konvertieren der Anreden" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=anrede'); ?>">Anreden</a>
					<a href="#" data-wpsg-tip="M1C-Anrede"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Konvertieren der Anrede in der Tabelle der Adressen
					<span id="idanrede" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 8) == 8) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <div style="height:48px;margin-bottom:3px;">
			        <a class="m1c_button" title="Löschen der Produktbilder in der Mediathek" id="idmedia" href="#">Mediathek</a>
					<a href="#" data-wpsg-tip="M1C-Mediathek" 
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0" 
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					<div style="display:inline-block; height:48px; vertical-align:middle">Löschen der Produktbilder in der Mediathek.<br /><span id="mprogress">0 Produktbilder in der Mediathek gelöscht.</span></div>
 					<span id="idmedien" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 16) == 16) echo 'style="display:inline-block;"'; ?> ></span><br />
					</div>
 
			        <div style="height:48px;margin-bottom:3px;">
			        <a class="m1c_button" title="Konvertieren der Produkte" id="idajax" href="#">Produkte</a>
					<a href="#" data-wpsg-tip="M1C-Produkte" 
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0" 
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					<div style="display:inline-block; height:48px; vertical-align:middle">Konvertieren der Produkt-Varianten und Laden der Produktbilder in die Mediathek.<br /><span id="progress">0 Produkte konvertiert.</span></div>
 					<span id="idprodukt" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 32) == 32) echo 'style="display:inline-block;"'; ?> ></span><br />
					</div>

			        <a class="m1c_button" title="Berechnen der Preise netto/brutto" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=preis'); ?>">Preise</a>
					<a href="#" data-wpsg-tip="M1C-Preise"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Berechnen der Preise netto/brutto
 					<span id="idpreise" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 64) == 64) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Konvertieren der Kategorien" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=category'); ?>">Kategorien</a>
					<a href="#" data-wpsg-tip="M1C-Kategorien"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Konvertieren der Kategorien
 					<span id="idcategory" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 128) == 128) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Lieferscheine in neue Verzeichnisstruktur kopieren" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=lief'); ?>">Lieferscheine</a>
					<a href="#" data-wpsg-tip="M1C-Lieferscheine"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Lieferscheine in die neue Verzeichnisstruktur kopieren
 					<span id="idlief" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 256) == 256) echo 'style="display:inline-block;"'; ?> ></span><br />

					<a class="m1c_button" title="Downloadplus in neue Verzeichnisstruktur kopieren" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=dlplus'); ?>">Downloadplus</a>
					<a href="#" data-wpsg-tip="M1C-Downloadplus"
					   rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					   class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Produktdokumente und Bestelldokumente in die neue Verzeichnisstruktur kopieren
					<span id="iddlplus" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 2048) == 2048) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Rechnungen in neue Verzeichnisstruktur kopieren" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=rech'); ?>">Rechnungen</a>
					<a href="#" data-wpsg-tip="M1C-Rechnungen"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Rechnungen in die neue Verzeichnisstruktur kopieren
 					<span id="idrech" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 512) == 512) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Exportprofile konvertieren" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=export'); ?>">Exportprofile</a>
					<a href="#" data-wpsg-tip="M1C-Exportprofile"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Exportprofile konvertieren
 					<span id="idrech" class="glyphicon glyphicon-ok glyph_ok" title="Konvertierung erfolgt" <?php if (($status & 1024) == 1024) echo 'style="display:inline-block;"'; ?> ></span><br />

			        <a class="m1c_button" title="Löschen der Thumbnails und Zippen der Produktbilder" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=zip'); ?>">Packen</a>
					<a href="#" data-wpsg-tip="M1C-Zip"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Löschen der Thumbnails und Zippen der Produktbilder von WPSG3<br />

			        <a class="m1c_button" title="Entpacken der Produktbilder" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=unzip'); ?>">Entpacken</a>
					<a href="#" data-wpsg-tip="M1C-Unzip"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Entpacken der Produktbilder<br />

			        <a class="m1c_button" title="Konverter beenden" href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=end&noheader=1'); ?>">Beenden</a>
					<a href="#" data-wpsg-tip="M1C-Beenden"
					rel="?page=wpsg-Admin&amp;subaction=loadHelp&amp;noheader=1&amp;field=Bestellung_0"
					class="glyphicon glyphicon-question-sign" aria-hidden="true"></a>
					Konverter beenden. Späterer manueller Start durch Aktivieren des Moduls.<br />

					<!--
			        <a href="<?php echo admin_url('admin.php?page=M1_Converter_dispatch&action=test'); ?>">Test</a>
			        <br /><br />
					-->
					
					</div>
					</div>
				<?php } ?>
    		</div>
		</div>
	</div>
	<div id="wpsg-bs"></div>
	
<script type="text/javascript">

	<?php
	$products = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` ORDER BY `id` ASC");
	//$products = array();
	$posts = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `meta_key`='".wpsg_q('wpsg_produkt_id')."' ORDER BY `post_id`");
	?>

	var m1cauto = 0;
	
function wpsg_ajaxBindM1C()
{
	
	// Hilfe Tooltips
	jQuery('*[data-wpsg-tip]').on('click', function() {

		jQuery(this).off('click').on('click', function() { return false; } );

		var po = this;

		jQuery(this).popover( {
			'html': true,
			'content': '<div id="wpsg-popover-content">Bitte warten...</div>',
			'trigger': 'focus',
			'container': '#wpsg-bs',
			'placement': 'right'
		} ).popover('show');

		jQuery.ajax( {
			url: '?page=wpsg-Admin&subaction=loadHelp&noheader=1',
			data: {
				field: jQuery(this).attr('data-wpsg-tip')
			},
			success: function(data) {
				
				var popover = jQuery(po).attr('data-content', data).data('popover');
				jQuery(po).data('bs.popover').options.content = data;
				
				jQuery(po).popover('show');
				
			}
		} );
		
		return false;
		
	} );

}

var po; 

function wpsg_ajaxBind()
{
	
	// Hilfe Tooltips
	jQuery('*[data-wpsg-tip]').on('click', function() { 

		//jQuery(this).off('click').on('click', function() { return false; } );

		if (typeof po === "object")
		{
			
			if (po != this) jQuery(po).popover('hide');
			
		}
		
		po = this;
		
		if (jQuery(this).hasClass('activated'))
		{
							
			jQuery(this).popover('show');
			
			return false;
			
		}
		
		jQuery(this).popover( {
			'html': true,
			'content': '<div id="wpsg-popover-content">Bitte warten...</div>',
			'trigger': 'focus',
			'container': '#wpsg-bs',
			'placement': 'right'
		} ).popover('show');

		jQuery.ajax( {
			url: '?page=wpsg-Admin&subaction=loadHelp&noheader=1',
			data: {
				field: jQuery(this).attr('data-wpsg-tip')
			},
			success: function(data) {
				
				var popover = jQuery(po).attr('data-content', data).data('popover');
				jQuery(po).data('bs.popover').options.content = data;
				
				jQuery(po).popover('show');
									
			}
		} );
		 
		jQuery(this).addClass('activated');
		
		return false;
		
	} );
			
}


jQuery(document).ready(function () {

	wpsg_ajaxBindM1C();
	//wpsg_ajaxBind();

	// Konvertieren der Produkte und Varianteninformationen und
	// Übernahme der Produktbilder in die Mediathek.
	jQuery(document).on("click", "#idajax", function () {
		//alert('ajax');

		var produkte = <?php echo json_encode($products); ?>;
		var pid = 1;
		var i, anz;
		anz = produkte.length;
		pid = produkte[0].id;

	    request = jQuery.ajax({
	        url: 'admin.php?page=M1_Converter_dispatch&action=prod0&noheader=1',
	        type: "post",
	        async: false,
        	success: function (response) {
				if ((response == '\n0') || (response == '0')) {
					alert('Die Übernahme ist schon erfolgt.');
					pid = -1;
					return;
        		}
        	},
        	error: function(jqXHR, textStatus, errorThrown) {
            	console.log(textStatus, errorThrown);
        	}
	        
	    });

		if (pid == -1) return false;
		
		for (i = 0; i < anz; i++) {
			pid = produkte[i].id;
		    request = jQuery.ajax({
		        url: 'admin.php?page=M1_Converter_dispatch&action=prod1&noheader=1',
		        type: "post",
		        async: false,
		        data: {
			        'pid': pid,
			        'num': i,
		        },
	        	success: function (response) {
					console.log((i + 1) + ' Produkte konvertiert.');
					jQuery('#progress').html(' ' + (i + 1) + ' Produkte konvertiert.');
	
	        	},
	        	error: function(jqXHR, textStatus, errorThrown) {
	            	console.log(textStatus, errorThrown);
	        	}
		        
		    });

		}

		if (m1cauto === 0)
			alert('Übernahme der Produktbilder und Varianteninformationen beendet.');
		jQuery('#idprodukt').show();
	    return false;
	});

	// Aufräumen der Mediathek.
	// Eventuell vorhandene Produktbilder werden gelöscht.
	jQuery(document).on("click", "#idmedia", function () {
		//alert('ajax');

		var posts = <?php echo json_encode($posts); ?>;
		var postid = 1;
		var i, anz;
		anz = posts.length;
		//alert('Anzahl: ' + anz);
		//pid = posts[0].post_id;

		for (i = 0; i < anz; i++) {
			pid = posts[i].post_id;
		    request = jQuery.ajax({
		        url: 'admin.php?page=M1_Converter_dispatch&action=media&noheader=1',
		        type: "post",
		        async: false,
		        data: {
			        'post_id': pid,
			        'num': i,
		        },
	        	success: function (response) {
					console.log((i + 1) + ' Produktbilder gelöscht.');
					jQuery('#mprogress').html(' ' + (i + 1) + ' Produktbilder in der Mediathek gelöscht.');
	
	        	},
	        	error: function(jqXHR, textStatus, errorThrown) {
	            	console.log(textStatus, errorThrown);
	        	}
		        
		    });

		}
		if (m1cauto == 0) alert('Mediathek aufräumen beendet.');
		jQuery('#idmedien').show();
	    return false;
	});
			
});

// Starten des automatischen Durchlaufs zur Konvertierung 
// der Daten.
jQuery(document).on("click", "#idauto", function () {

	// Buttons disable
	jQuery('.m1c_button').bind('click', false);
	m1cauto = 1;
	
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=auto&noheader=1');
	progress(0);
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=allgemein&noheader=1');
	progress(1);
	jQuery('#idallgemein').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=kunden&noheader=1');
	progress(2);
	jQuery('#idkunden').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=order&noheader=1');
	progress(3);
	jQuery('#idorder').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=anrede&noheader=1');
	progress(4);
	jQuery('#idanrede').show();

	jQuery('#idmedia').unbind('click', false);
	jQuery('#idmedia').trigger("click");
	jQuery('#idmedia').bind('click', false);
	progress(5);
	jQuery('#idajax').unbind('click', false);
	jQuery('#idajax').trigger("click");
	jQuery('#idajax').bind('click', false);
	progress(6);

	ajaxCall('admin.php?page=M1_Converter_dispatch&action=preis&noheader=1');
	progress(7);
	jQuery('#idpreise').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=category&noheader=1');
	progress(8);
	jQuery('#idcategory').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=lief&noheader=1');
	progress(9);
	jQuery('#idlief').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=rech&noheader=1');
	progress(10);
	jQuery('#idrech').show();
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=export&noheader=1');
	progress(11);
	jQuery('#idexport').show();

	// Buttons enable
	jQuery('.m1c_button').unbind('click', false);
	m1cauto = 0;
	//alert('Automatische Konvertierung beendet.');
	ajaxCall('admin.php?page=M1_Converter_dispatch&action=ende&noheader=1');
	return false;
	
});

function ajaxCall(url) {

    request = jQuery.ajax({
        url: url,
        type: "post",
        async: false,
        data: {
	        'post_id': 0,
	        'num': 0,
        },
    	success: function (response) {

    		if (response === "FIN") {
    			alert('Konvertierung beendet.');
        		//alert("<?php echo WPSG_URL_WP?>wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_converter");
				//wp_redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=module&modul=wpsg_mod_converter');
        		location.href = "<?php echo WPSG_URL_WP?>wp-admin/admin.php?page=wpsg-Admin&action=module";
        	}
			console.log(url);
    	},
    	error: function(jqXHR, textStatus, errorThrown) {
        	console.log(textStatus, errorThrown);
    	}
        
    });

}

// Anzeige des senkrechten Fortschrittsbalken.
function progress(fill) {
	var c = document.getElementById("sprogress");
	var ctx = c.getContext("2d");
	if (fill == 0) {
		ctx.fillStyle = "#FFFFFF";
		ctx.fillRect(0, 0, 10, 10 * 50);
	}
	jQuery('#sprogress').attr('height', fill * 50);
	ctx.fillStyle = "#00FF00";
	ctx.fillRect(0, 0, 8, fill * 50);
	//alert('progress');

}

function pause(delay) {
    var start = new Date().getTime();
    while (new Date().getTime() < start + delay);
}

</script>

<?php }
	} // class wpsg_mod_converter extends wpsg_mod_basic
?>