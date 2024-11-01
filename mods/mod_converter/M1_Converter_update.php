<?php

/**
 * Dieses Modul dient zur Konvertierung der Daten aus wpShopGermany Version 3
 * in die wpShopGermany Version 4.
 * 
 * @author hartmut
 */
class M1_Converter_update
{
	
	private $m1cpb1;
	
	const TYPE_ORDER = '1';
	const TYPE_PRODUCT = '2';
	const TYPE_CUSTOMER = '3';
	
	const ENCODING_UTF8 = '1';
	const ENCODING_ISO88591 = '2';
	
	const FORMAT_CSV = '1';
	const FORMAT_XML = '2';
	
	
	public function __construct($m1cpb) {
		//global $GLOBALS;
		//$this->glob = &$GLOBALS;
		$this->m1cpb1 = $m1cpb;
	}
	
	/**
	 * Konvertierung der Kundenadressen.
	 * 
	 * Wenn das Feld adress_id=0 ist, wird die Adresse aus der Kundentabelle
	 * in die Adresstabelle kopiert und in dem Feld adress_id der Bezug
	 * zur Adresse hergestellt.
	*/
	public function M1_Converter_kunden() {
		
	    $anz1 = 0;
	    $anz2 = 0;
	?>
	
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
	<?php
	    $kunden = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_KU."` ORDER BY `id` ASC");
	 
	    foreach ($kunden as $k)
	    {
	    	$anz2++;
	    	if (isset($k['adress_id']) && ($k['adress_id'] == '0'))
	    	{
	    		$kid = $k['id'];
	    		
	    		// Daten aus Kunden in Tabelle Adresse übernehmen
	    		// und die adress_id in WPSG_TBL_KU eintragen.
	    		$data = array();
	    		$data['cdate'] = "NOW()";
	    		$data['title'] = $k['title'];
	    		$data['name'] = $k['name'];
	    		$data['vname'] = $k['vname'];
	    		$data['firma'] = $k['firma'];
	    		$data['fax'] = $k['fax'];
	    		$data['strasse'] = $k['strasse'];
	    		$data['nr'] = wpsg_getStr($k['nr']);
	    		$data['plz'] = $k['plz'];
	    		$data['ort'] = $k['ort'];
	    		$data['land'] = $k['land'];
	    		$data['tel'] = $k['tel'];
	    		$aid = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_ADRESS, wpsg_q($data));
	    		
	    		$kdata = array('adress_id' => $aid);
	    		$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_KU, wpsg_q($kdata), "`id` = '".$kid."'");
	    		$anz1++;
	    		
	    	}
	    	
	    }
	    
	    unset($_REQUEST['action']);
    	echo '<br /><br />';
    	echo $anz1.' Adresse(n) von '.$anz2.' Kunden neu angelegt.<br /><br />';
    	
    	if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 2) {
    		echo '<br />Konvertierung beendet<br /><br />';
    		echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
    	} else {
    		echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
    	}
    	//$GLOBALS['wpsg_sc']->addFrontendMessage(__('Test.', 'wpsg'));
    	
    	?>
		</div></div></div>
		<?php
    	
	}	// public function M1_Converter_kunden()

	/**
	 * Konvertierung der Bestellungen
	 *
	 * Über die k_id wird die adress_id aus der Kundentabelle geholt
	 * und in das Feld adress_id in der Bestellungen-Tabelle eingetragen.
	 * Wenn eine Lieferadresse vorhanden ist, wird diese in die Tabelle
	 * Adressen kopiert und die id in das Feld shipping_adress_id eingetragen.
	 *
	 */
	public function M1_Converter_order() {
		
		$anz1 = 0;
		$anz2 = 0;
		$anz3 = 0;
		$anz4 = 0;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
	<?php
		
		$order = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDER."` ORDER BY `id` ASC");
	
		foreach ($order as $o)
		{
			$anz2++;
			$oid = $o['id'];
			
			if (isset($o['adress_id']) && ($o['adress_id'] == '0'))
			{
			
				// Aus WPSG_TBL_ORDER k_id nehmen und aus WPSG_TBL_KU adress_id in
				// WPSG_TBL_ORDER in das Feld adress_id eintragen.
				$kid = $o['k_id'];
				
				$kdata = $GLOBALS['wpsg_sc']->db->fetchRow("SELECT * FROM `".WPSG_TBL_KU."` WHERE `id` = '".wpsg_q($kid)."' ");
				if (!is_array($kdata))
				{
					$anz4++;
				}
				
				if (isset($kdata['adress_id']) && $kdata['adress_id'] != '0')
				{
					$aid = $kdata['adress_id'];
					$odata = array('adress_id' => $aid);
					$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ORDER, $odata, "`id` = '".$oid."'");
					$anz3++;
				}
			}
	
			// Wenn in WPSG_TBL_ORDER eine Versandadresse angegeben ist, diese in
			// WPSG_TBL_ADRESS anlegen und in WPSG_TBL_ORDER in shipping_adress_id eintragen.
			if (isset($o['shipping_ort']) && ($o['shipping_ort'] != '') && ($o['shipping_adress_id'] == '0'))
			{
				
				$data = array();
				$data['cdate'] = "NOW()";
				$data['title'] = $o['shipping_title'];
				$data['name'] = $o['shipping_name'];
				$data['vname'] = $o['shipping_vname'];
				$data['firma'] = $o['shipping_firma'];
				$data['strasse'] = $o['shipping_strasse'];
				$data['nr'] = $o['shipping_nr'];
				$data['plz'] = $o['shipping_plz'];
				$data['ort'] = $o['shipping_ort'];
				$data['land'] = $o['shipping_land'];
				$aid = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_ADRESS, wpsg_q($data));
				
				$odata = array('shipping_adress_id' => $aid);
				$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ORDER, $odata, "`id` = '".$oid."'");
				
				$anz1++;
			}
				
		}
		
		unset($_REQUEST['action']);
		echo '<br /><br />';
		echo $anz1.' Versandadresse(n) von '.$anz2.' Bestellungen neu angelegt.<br /><br />';
		echo $anz3.' Rechnungsadresse(n) aus den Kunden übernommen. '.$anz4.' Kunden nicht gefunden.<br /><br />';
		
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 4) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_order()

	/**
	 * Konvertierung der Anrede bei den Adressen.
	 *
	 * Herr wird zu 0 und Frau wird zu 1 geändert.
	*/
	public function M1_Converter_anrede() {
		
		$anz1 = 0;
		$anz2 = 0;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
			<div class="panel-body" >
	<?php
	    $adress = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ADRESS."` ORDER BY `id` ASC");
	 
	    foreach ($adress as $a)
	    {
	    	$anz1++;

	    	if (($a['title'] == 'Herr') || ($a['title'] == 'Frau'))
	    	{
		    	$aid = $a['id'];
		    	if ($a['title'] == 'Herr') $title = 0;
		    	if ($a['title'] == 'Frau') $title = 1;
	    	
		    	$data = array('title' => $title);
		    	$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ADRESS, $data, "`id` = '".$aid."'");

	    		$anz2++;
	    	}
	    	
	    }
	    
	    unset($_REQUEST['action']);
    	echo '<br /><br />';
    	echo $anz2.' Anreden von '.$anz1.' Adressen geändert.<br /><br />';
    	
    	if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 8) {
    		echo '<br />Konvertierung beendet<br /><br />';
    		echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
    	} else {
    		echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
    	}
    	
    	?>
		</div></div></div>
		<?php
    	
	}	// public function M1_Converter_anrede()


	/**
	 * Prüfen, ob der Import der Produktbilder und Varianten schon durchgeführt wurde.
	 * 
	 * @return int  0 oder 1 (1 wenn noch kein Import erfolgt ist, 0 wenn ja)
	*/
	public function M1_Converter_prod0() {

		require_once(ABSPATH."wp-admin".'/includes/post.php');
		date_default_timezone_set('Europe/Berlin');

		if (!wpsg_isSizedString(get_option('wpsg_mod_produktartikel_pathkey_cat'))) update_option('wpsg_mod_produktartikel_pathkey_cat', 'wpsgtax');

		if ( null == get_page_by_title('wpsg_variants_import') ) {
			// Create the page
			$arPost = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_author' => 1,
				'post_date' => date('Y-m-d H:i:s'),
				'post_name' => 'wpsg_variants_import',
				'post_title' => 'wpsg_variants_import',
				'post_status' => 'publish',
				'post_type' => 'page'
    		);
			wp_insert_post($arPost);
			//$this->m1cpb1 = new progressbar(0, 100, 300, 30);
			//$this->m1cpb1->print_code();
			
			// Variantentabellen leeren
			$GLOBALS['wpsg_sc']->db->Query("TRUNCATE `".WPSG_TBL_VARIANTS."`");
			$GLOBALS['wpsg_sc']->db->Query("TRUNCATE `".WPSG_TBL_VARIANTS_VARI."`");
			$GLOBALS['wpsg_sc']->db->Query("TRUNCATE `".WPSG_TBL_PRODUCTS_VARIANT."`");
			$GLOBALS['wpsg_sc']->db->Query("TRUNCATE `".WPSG_TBL_PRODUCTS_VARIATION."`");
			
			return '1';
		} else {
			// The page exists
			$page = get_page_by_title('wpsg_variants_import');
			$ar = get_object_vars($page);
			$dt1 = $ar['post_date'];
			$dt2 = wpsg_fromDate($dt1, false);
			//echo '<br /><br />';
			//echo 'Übernahme der Varianten und Produktbilder ist schon erfolgt am '.$dt2.'.<br /><br />';
			unset($_REQUEST['action']);
			return '0';
		}
		
	}	// public function M1_Converter_prod0()
	
	/**
	 * Importieren der Bilder und Varianten eines Produktes.
	 * 
	 * Die Produktbilder werden in die Mediathek importiert
	 * und die Varianten/Variationen aus der Produkt-Tabelle 
	 * Feld mod_varianten in die neue Tabellenstruktur übernommen.
	 * 
	*/
	public function M1_Converter_prod1() {
		
		$anz1 = 0;
		$anz2 = 0;
		$anz3 = 0;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		<br />
	<?php

		ini_set('max_execution_time', 1200);
		
		$products = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` ORDER BY `id` ASC");
	
		$i1 = 0;
		$i2 = 0;
		$arPostids = array();
		$arNamen1 = array();	// alter Name
		$arNamen2 = array();	// neuer Name
		$pcount = count($products);
		usleep(5000);			// 5ms
		
		//foreach ($products as $p)
		{
			
			$pid = $_REQUEST['pid'];
			$p = $GLOBALS['wpsg_sc']->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id`=".$pid);
			
			// Alle Produktbilder hochladen, postid und neuen Namen merken.
			// Diese Funktion hat keine Wirkung, wenn PHP im Safe Mode ausgeführt wird
			$temp = set_time_limit(2000);		// Retriggern
			//if ($temp == false) echo 'set_time_limit=false';
			$arPic = $this->getProduktBilder($pid);
			//$arPics = array_unique(array_merge($arPics, $arPic));
			//$path = 'C:\xampp\htdocs\wp1\wp-content\uploads\wpsg_produktbilder';
			
			foreach ($arPic as $pic)
			{
				$anz3++;
				$postid = 0;
				$this->loadPicture($pic, $pid, $postid, $dname1, $dname2);
				$arPostids[] = $postid;
				$arNamen1[] = $dname1;
				$arNamen2[] = $dname2;
				
			}
			
			//$pid = $p['id'];
			$panr = $p['anr'];
			$arPics = array();
			
			if ($p['mod_varianten'] != '')
			{
				$arVar = unserialize($p['mod_varianten']);
				
				$i1 = 0;
				
				foreach ($arVar as $v)
				{
					$data = array();
					if ($v['typ'] == 'checkbox') $data['type'] = 0;
					if ($v['typ'] == 'select') $data['type'] = 0;
					if ($v['typ'] == 'radio') $data['type'] = 1;
					if ($v['typ'] == 'image') $data['type'] = 2;
					$data['product_id'] = $pid;
					$data['name'] = $v['name'];
					$vid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_VARIANTS, $data);
					
					$data = array();
					$data['variant_id'] = $vid1;
					$data['product_id'] = $pid;
					$data['pos'] = $i1;
					$vid2 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIANT, $data);
					
					$vaktiv = $v['aktiv'];
					
					if ($v['typ'] == 'checkbox')
					{	// Checkbox durch Selectbox ersetzen mit zwei Variationen (ja/nein).
						// Checkbox hat im Shop V3 keine Variationen.
						$data1 = array();
						$data1['variant_id'] = $vid1;
						$data1['name'] = 'ja';
						$data1['shortname'] = 'ja';
						$data1['price'] = $v['preis'];
						$data1[images]= implode(',', $arPostids);
						if (isset($v['weight'])) $data1['weight'] = $v['weight'];
						$vvid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_VARIANTS_VARI, $data1);
						$data2 = array();
						$data2['variation_id'] = $vvid1;
						$data2['product_id'] = $pid;
						$data2['active'] = $v['aktiv'];
						// Ticket #573 Variationen inaktiv setzen, wenn Variante inaktiv ist
						if ($vaktiv != '1') $data2['active'] = '0';
						$data2['price'] = $v['preis'];
						$data2['images']= implode(',', $arPostids);
						$data2['images_set'] = '';
						$arpic = wpsg_trim(explode(',', $v['pic']));
						foreach ($arpic as $pic) {
							$x = array_search($pic, $arNamen1);
							if ($x !== false)
							{
								if (strlen($data2['images_set']) > 0 ) $data2['images_set'] .= ',';
								$data2['images_set'] .= $arPostids[$x];
							}
						}
						if (isset($v['weight'])) $data2['weight'] = $v['weight'];
						$vvid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $data2);
						
						$data1['name'] = 'nein';
						$data1['shortname'] = 'nein';
						$vvid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_VARIANTS_VARI, $data1);
						$data2['variation_id'] = $vvid1;
						$vvid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $data2);
						
						//vp_673/0_1|1_2
						//$i1.'_'.$i2 ersetzen durch $vid1.':'.$vvid1
						$x1 = $i1.'_'.$i2;
						$x2 = $vid1.':'.$vvid1;
						$pk = 'vp_'.$pid;
						//WHERE `p_id` = $pid   oder  WHERE `productkey` LIKE '%'.$pk.'%'
						$sql = "UPDATE ".WPSG_TBL_ORDERPRODUCT." SET `productkey` = REPLACE(`productkey`, '".$x1."', '".$x2."') WHERE `p_id`=".$pid;
						$GLOBALS['wpsg_sc']->db->Query($sql);
						$i2++;
						
					}
					
					$anz1++;
					$i2 = 0;
					foreach ($v['vari'] as $vv)
					{
						$data = array();
						$data['variant_id'] = $vid1;
						if (isset($vv['name']))  $data['name'] = $vv['name'];
						if (isset($vv['name']))  $data['shortname'] = $vv['name'];
						if (isset($vv['artnr'])) $data['anr'] = $vv['artnr'];
						else $data['anr'] = $panr;
						
						$vvid1 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_VARIANTS_VARI, $data);
						
						$sdata = array();
						if (isset($vv['pic'])) $sdata['pic'] = $vv['pic'];
						if (isset($vv['picOrder'])) $sdata['picOrder'] = $vv['picOrder'];
						$arpic = wpsg_trim(explode(',', $vv['pic']));
						$sdata['postid'] = '';
						$sdata['postid'] = implode(',', $arPostids);
						$sdata['pic'] = '';
						foreach ($arpic as $pic) {
							$x = array_search($pic, $arNamen1);
							if ($x !== false)
							{

								if (strlen($sdata['pic']) > 0 ) $sdata['pic'] .= ',';
								$sdata['pic'] .= $arPostids[$x];
							}
						}
						
						if (isset($sdata['picOrder'])) {
							$sdata['postid'] = '';
							foreach ($sdata['picOrder'] as &$pic) {
								$x = array_search($pic, $arNamen1);
								if ($x !== false)
								{
									//$pic = $arNamen2[$x];
									unset($arNamen1[$x]);
									if (strlen($sdata['postid']) > 0 ) $sdata['postid'] .= ',';
									$sdata['postid'] .= $arPostids[$x];
									unset($arPostids[$x]);
								}
							}
							foreach ($arPostids as $postid) {
								if (strlen($sdata['postid']) > 0 ) $sdata['postid'] .= ',';
								$sdata['postid'] .= $postid;
							}
						}
						
						$data = array();
						$data['variation_id'] = $vvid1;
						$data['product_id'] = $pid;
						if (isset($vv['aktiv'])) $data['active'] = $vv['aktiv'];
						// Ticket #573 Variationen inaktiv setzen, wenn Variante inaktiv ist
						if ($vaktiv != '1') $data['active'] = '0';
						
						if (isset($vv['artnr'])) $data['anr'] = $vv['artnr'];
						else $data['anr'] = $panr;
						
						if (isset($vv['preis'])) $data['price'] = $vv['preis'];
						if (isset($vv['stock'])) $data['stock'] = $vv['stock'];
						//$data['images'] = $ser;
						$data['images'] = $sdata['postid'];
						$data['images_set'] = $sdata['pic'];
						$vvid2 = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $data);
						
						if (isset($vv['pic']))
						{
							$arPicsVariante = wpsg_trim(explode(',', $vv['pic']));
							$arPics = array_unique(array_merge($arPics, $arPicsVariante));
						}
						$anz2++;
						
						//vp_673/0_1|1_2
						//$i1.'_'.$i2 ersetzen durch $vid1.':'.$vvid1
						$x1 = $i1.'_'.$i2;
						$x2 = $vid1.':'.$vvid1;
						$pk = 'vp_'.$pid;
						//WHERE `p_id` = $pid   oder  WHERE `productkey` LIKE '%'.$pk.'%'
						$sql = "UPDATE ".WPSG_TBL_ORDERPRODUCT." SET `productkey` = REPLACE(`productkey`, '".$x1."', '".$x2."') WHERE `p_id`=".$pid;
						$GLOBALS['wpsg_sc']->db->Query($sql);
						$i2++;
						
					}
					$i1++;
					
				}	// foreach ($arVar as $v)
				
				// 'vp_'.$pid.'/'  ersetzen durch  'pv_'.$pid.'|'
				$x1 = 'vp_'.$pid.'/';
				$x2 = 'pv_'.$pid.'|';
				$sql = "UPDATE ".WPSG_TBL_ORDERPRODUCT." SET `productkey` = REPLACE(`productkey`, '".$x1."', '".$x2."') WHERE `p_id`=".$pid;
				$GLOBALS['wpsg_sc']->db->Query($sql);

				// Tabelle Produkte postids löschen
				$sql = "UPDATE ".WPSG_TBL_PRODUCTS." SET `postids` = '' WHERE `id`=".$pid;
				$GLOBALS['wpsg_sc']->db->Query($sql);
				
			}	// if ($p['mod_varianten'] != '')
			
			$this->m1cpb1->step(100 / $pcount);
		}	// foreach ($products as $p)
		
		//if (($num + 1) == $pcount)
		{
			echo $pcount.' Produkte analysiert.<br /><br />';
			unset($_REQUEST['action']);
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show">Zurück</a><br />');
			
		}
		
		ini_set('max_execution_time', 30);
		
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_prod1()

	/**
	 * Laden des Bildes in den Mediathek-Ordner und Eintragen der 
	 * Informationen in die Tabelle wp_posts und wp_postmeta.
	 * 
	 * @param string $pic	Dateiname des Bildes
	 * @param string $pid	Produkt-ID
	 * @param string $postid	Post-ID
	 * @param string $dname1	Dateiname
	 * @param string $dname2	Basename
	*/
	public function loadPicture($pic, $pid, &$postid, &$dname1, &$dname2)
	{
		//$file = $path.'/'.$pid.'/'.$pic;
		$path = $this->getPicPath($pid);
		$file = $path.$pic;
		if (!file_exists($file)) return;
		
		$filename = basename($file);
		$parent_post_id = 0;
		$wp_upload_dir = wp_upload_dir();
		
		// Lädt das Bild in den Mediathek-Ordner (uploads/yyyy/mm/)
		$upload_file = wp_upload_bits($filename, null, file_get_contents($file));
		if (!$upload_file['error']) {
			$wp_filetype = wp_check_filetype($filename, null);
			$arFn = pathinfo($upload_file['file']); 
			$attachment = array(
					'guid' => $wp_upload_dir['url'] . '/' . $filename,
					'guid' => $upload_file['url'],
					'post_mime_type' => $wp_filetype['type'],
					'post_parent' => $parent_post_id,
					'post_title' => preg_replace('/\.[^.]+$/', '', $arFn['basename']),
					'post_excerpt' => $arFn['basename'],
					'post_parent' => '0',
					'wpsg_produkt_id' => $pid,
					'post_content' => '',
					'post_status' => 'inherit'
			);
			// Eintrag in wp_posts und wp_postmeta (_wp_attached_file)
			// Returns the resulting post ID (int) on success or 0 (int) on failure.
			$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );
			$postid = $attachment_id;
			$dname2 = $arFn['basename'];
			$dname1 = $filename;
			//$attachment_id = media_handle_upload( '0', 0, $attachment );	//OK
			// Eintrag in wp_postmeta (wpsg_produkt_id)
			add_post_meta( $attachment_id, 'wpsg_produkt_id', $pid );
			
			if (!is_wp_error($attachment_id)) {
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				// Erzeugt attachment-metadata
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
				// Eintrag in wp_postmeta (_wp_attachment_metadata)
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}
		}
		
	}
	
	
	/**
	 * Zippen der Produktbilder
	 * 
	 * Löschen der Thumbnails und Zippen der Produktbilder von WPSG Version 3
	 * in die Datei uploads/wpsg_produktbilder/backup.zip
	 * 
	*/
	public function M1_Converter_zip() {
		
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
	<?php
	
		ini_set('max_execution_time', 300);
		
		$products = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` ORDER BY `id` ASC");
	
		// Thumbnail-Verzeichnisse löschen
		foreach ($products as $p)
		{
	
			$path_post_thumbnail = $this->getPicPath($p['id'], false, false).'tn/';
			wpsg_rrmdir($path_post_thumbnail);
	
		}
		
		//$path = 'C:/xampp/htdocs/wp2/wp-content/uploads/wpsg_produktbilder/';
		$path = $this->getPicPath('');
		$path = str_replace('//', '/', $path);
		$path = str_replace('\\', '/', $path);
		$zpath = $path.'backup.zip';
		
		$oldPath = getcwd();
		if (chdir($path) != true) {
			die("ERROR: Could not change directory");
		}
		
		// Objekt erstellen und prüfen, ob der Server zippen kann
		$zip = new ZipArchive();
		// ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE überschreibt nicht das Archiv,
		// deshalb die Archivdatei vorher löschen
		if (file_exists($zpath)) unlink($zpath);
		
		if ($zip->open($zpath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) !== TRUE) {
			die ("ERROR: Could not open archive");
		}
		
		// Gehe durch die Ordner und füge alle Dateien zum Archiv hinzu
		$len = strlen($path);
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		foreach ($iterator as $key => $value) {
			if (substr($key, strlen($key) - 1) != '.') {
				$filename = substr($key, $len);
				$zip->addFile(realpath($key), $filename) or die ("ERROR: Could not add file: $key");
				echo $key.'<br />';
			}
		}
		
		// Zip-Datei speichern
		$anz = $zip->numFiles;
		$zip->close();
		echo "<br />Archiv erfolgreich erstellt.";
		chdir($oldPath);
		
		echo '<br /><br />';
		echo $anz.' Dateien komprimiert.<br /><br />';
		
		unset($_REQUEST['action']);
		echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show">Zurück</a><br />');
	
		ini_set('max_execution_time', 30);
		
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_zip()

	/**
	 * Unzippen der Produktbilder
	 *
	 * Entpacken der Produktbilder in das Verzeichnis uploads/wpsg_produktbilder.
	 *
	 */
	public function M1_Converter_unzip() {
		
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
	<?php
	
		ini_set('max_execution_time', 300);
		
		//$path = 'C:/xampp/htdocs/wp2/wp-content/uploads/wpsg_produktbilder/';
		$path = $this->getPicPath('');
		$path = str_replace('//', '/', $path);
		$path = str_replace('\\', '/', $path);
		$zpath = $path.'backup.zip';
		
		// Objekt erstellen und prüfen, ob der Server unzippen kann
		$zip = new ZipArchive();
		
		if ($zip->open($zpath) !== TRUE) {
			die ("ERROR: Could not open archive");
		}
		
		if ($zip->extractTo($path) == true) echo '<br /><br />ZIP-Archiv erfolgreich entpackt.<br /><br />';
		else echo '<br /><br />Fehler beim Entpacken des ZIP-Archivs.<br /><br />';
	
		$zip->close();
		
		unset($_REQUEST['action']);
		echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show">Zurück</a><br />');
	
		ini_set('max_execution_time', 30);
		
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_unzip()

	/**
	 * Ändern der Kategorie von category in wpsgtax in der Tabelle wp_term_taxonomy
	 * 
	*/
	public function M1_Converter_category() {
		
		global $myprogressbar1;
		global $GLOBALS;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		$anz = 0;
	
		$sql = "SELECT * FROM `".$GLOBALS['wpdb']->prefix."term_relationships`";
		$arTRel = $GLOBALS['wpsg_sc']->db->fetchAssoc($sql);
	
		$pathkey = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_produktartikel_pathkey'));
	
		foreach ($arTRel AS $trel) {
		
			$sql = "SELECT * FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID` = '".wpsg_q($trel['object_id'])."'";
			$arPosts = $GLOBALS['wpsg_sc']->db->fetchRow($sql);
		
			if ($arPosts['post_type'] == $pathkey) {
			
				$tt_id = $trel['term_taxonomy_id'];
			
				// Tabelle wp_term_taxonomy Feld taxonomy von category in wpsgtax ändern
				$data = array(
				'taxonomy' => $GLOBALS['wpsg_sc']->get_option('wpsg_mod_produktartikel_pathkey_cat')
				);

				$krit = "`term_taxonomy_id` = '".wpsg_q($tt_id)."' AND `taxonomy` = '".wpsg_q('category')."'";
				$tname = $GLOBALS['wpdb']->prefix."term_taxonomy";
				$GLOBALS['wpsg_sc']->db->UpdateQuery($GLOBALS['wpdb']->prefix."term_taxonomy", $data, "`term_taxonomy_id` = '".wpsg_q($tt_id)."' AND `taxonomy` = '".wpsg_q('category')."'");
			
				$anz++;
			}
			
		}
	
		//$mp->set(15); // auf 15 % setzen
		//$mp->complete(); // fertig!
		
		echo '<br /><br />';
		echo $anz.' Kategorien geändert von category auf '.$GLOBALS['wpsg_sc']->get_option('wpsg_mod_produktartikel_pathkey_cat').'.<br /><br />';
	
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 128) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);
	
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_category()

	/**
	 * Umkopieren der Lieferscheine in die neue Verzeichnisstruktur.
	 * 
	 * Lieferscheine kopieren von
	 * C:\xampp\htdocs\wp2\wp-content\uploads\wpsg\wpsg_deliverynote\order_id\deliverynote_dn_id.pdf
	 * nach
	 * C:\xampp\htdocs\wp2\wp-content\uploads\wpsg\wpsg_deliverynote\yyyy\mm\order_id
	 * 
	*/
	public function M1_Converter_lief() {
		
		global $myprogressbar1;
		global $GLOBALS;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		ini_set('max_execution_time', 600);
	
		$anz1 = 0;
		$anz2 = 0;
		
		if ($GLOBALS['wpsg_sc']->hasMod('wpsg_mod_deliverynote')) {
		
		$liefs = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_DELIVERYNOTE."` ORDER BY `id` ASC");
		
		foreach ($liefs AS $lief) {
		
			$datum = $lief['cdate'];
			$ym = date('Y/m/', strtotime($datum));
			$fname = 'deliverynote_'.$lief['id'].'.pdf';
			$oid = $lief['order_id'];
			
			if ($GLOBALS['wpsg_sc']->isMultiBlog())
			{
				$from = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_deliverynote/'.$oid.'/';
				$to = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_deliverynote/'.$ym.$oid.'/';
			}
			else
			{
				$from = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_deliverynote/'.$oid.'/';
				$to = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_deliverynote/'.$ym.$oid.'/';
			}
			$anz1++;
			
			if (file_exists($from.$fname))
			{
				if (!is_dir($to)) {
					mkdir($to, 0777, true);
				}
				//mkdir($to, 0777, true);
				copy($from.$fname, $to.$fname);
				$anz2++;
			}
			
		}
	
		}
		
		//$mp->set(15); // auf 15 % setzen
		//$mp->complete(); // fertig!
		
		echo '<br /><br />';
		echo $anz1.' Lieferscheine bearbeitet.<br /><br />';
		echo $anz2.' Lieferscheindateien kopiert.<br /><br />';
		
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 256) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);

		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_lief()

	/**
	 * Umkopieren von
	 * wp-content/uploads/wpsg_pdfprodukte -> wp-content/uploads/wpsg/wpsg_pdfprodukte/
	 * wp-content/uploads/wpsg_pdfprodukte_order -> wp-content/uploads/wpsg/wwpsg_pdfprodukte_order/
	 */
	public function M1_Converter_dlplus() {
 
		?>
		
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />

		<div class="m1_converter_admin_content" >
			<div class="panel panel-default" >
				<div class="panel-heading clearfix" >
					<h2 class="panel-title" >M1-Konverter</h2>
				</div>

				<div class="panel-body" >

					<?php
					
						ini_set('max_execution_time', 600);

						if ($GLOBALS['wpsg_sc']->isMultiBlog()) {
							
							$from = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg_pdfprodukte/';
							$to = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_pdfprodukte/';

							$from2 = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg_pdfprodukte_order/';
							$to2 = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_pdfprodukte_order/';
							
						} else {
							
							$from = WPSG_PATH_CONTENT.'uploads/wpsg_pdfprodukte/';
							$to = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_pdfprodukte/';
							
							$from2 = WPSG_PATH_CONTENT.'uploads/wpsg_pdfprodukte_order/';
							$to2 = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_pdfprodukte_order/';
							
						}

						if (file_exists($from) && is_dir($from)) wpsg_copy($from, $to);
						if (file_exists($from2) && is_dir($from2)) wpsg_copy($from2, $to2);

						echo '<br /><br />';
						echo 'Verzeichnisse kopiert.<br /><br />';

						unset($_REQUEST['action']);
					
						if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 512) {
							
							echo '<br />Konvertierung beendet<br /><br />';
							echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
							
						} else {
							
							echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
					
						}
					
						ini_set('max_execution_time', 30);

					?>
				</div>
			</div>
		</div>
		
		<?php
		
	} // public function M1_Converter_dlplus()
	
	/**
	 * Umkopieren der Rechnungen in die neue Verzeichnisstruktur.
	 * 
	 * Rechnungen kopieren von
	 * C:\xampp\htdocs\wp2\wp-content\uploads\wpsg_rechnungen\order_id\re_id.pdf
	 * nach
	 * C:\xampp\htdocs\wp2\wp-content\uploads\wpsg\wpsg_rechnungen\yyyy\mm\order_id
	 * 
	*/
	public function M1_Converter_rech() {
		
		global $myprogressbar1;
		global $GLOBALS;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		ini_set('max_execution_time', 600);
		$anz1 = 0;
		$anz2 = 0;
		
		if ($GLOBALS['wpsg_sc']->hasMod('wpsg_mod_rechnungen')) {
			
		$rechs = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_RECHNUNGEN."` ORDER BY `id` ASC");
		
		foreach ($rechs AS $rech) {
		
			$datum = $rech['datum'];
			$ym = date('Y/m/', strtotime($datum));
			$fname = ''.$rech['id'].'.pdf';
			$oid = $rech['o_id'];
			
			if ($GLOBALS['wpsg_sc']->isMultiBlog())
			{
				$from = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg_rechnungen/'.$oid.'/';
				$to = WPSG_PATH_CONTENT.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_rechnungen/'.$ym.$oid.'/';
			}
			else
			{
				$from = WPSG_PATH_CONTENT.'uploads/wpsg_rechnungen/'.$oid.'/';
				$to = WPSG_PATH_CONTENT.'uploads/wpsg/wpsg_rechnungen/'.$ym.$oid.'/';
			}
			$anz1++;
			
			if (file_exists($from.$fname))
			{
				if (!is_dir($to)) {
					mkdir($to, 0777, true);
				}
				//mkdir($to, 0777, true);
				copy($from.$fname, $to.$fname);
				$anz2++;
			}
			
		}
	
		}
		
		//$mp->set(15); // auf 15 % setzen
		//$mp->complete(); // fertig!
		
		echo '<br /><br />';
		echo $anz1.' Rechnungen/Gutschriften bearbeitet.<br /><br />';
		echo $anz2.' Rechnungs-/Gutschrift-Dateien kopiert.<br /><br />';
		
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 512) {
			echo '<br />Konvertierung beendet<br /><br />'; 
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);
	
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_rech()
	
	/**
	 * Löschen der Produktbilder in der Mediathek und der zugehörigen
	 * Einträge in wp_posts und wp_postmeta.
	 *  
	*/
	public function M1_Converter_media() {

	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		
		ini_set('max_execution_time', 1200);

		//wp_delete_post( $postid, $force_delete );
		//wp_delete_attachment( $attachmentid, $force_delete );
		//SELECT * FROM `wp_postmeta` WHERE `meta_key`='wpsg_produkt_id' AND `meta_value`=11
		
		$post_id = $_REQUEST['post_id'];
		wp_delete_attachment($post_id, true);
		
		$GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `post_title` = '".wpsg_q('wpsg_variants_import')."' ");
		
		?>
		</div></div></div>
		<?php
		
	}

	/**
	 * Berechnen der Preise netto/brutto, da diese vorher nicht geführt wurden.
	 * 
	 * Wenn die Preise netto/brutto 0 sind, diese neu berechnen,
	 * denn diese sind erst in neueren WPSG3-Versionen benutzt.
	 * 
	*/
	public function M1_Converter_preis() {
		
		global $myprogressbar1;
		global $GLOBALS;
	?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		$anz1 = 0;	// Bestellungen
		$anz2 = 0;	// Bestellte Produkte
	
		$order = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDER."` ORDER BY `id` ASC");
	
		foreach ($order as $o)
		{
			if (!wpsg_isSized($o['price_gesamt_netto']) && !wpsg_isSized($o['price_gesamt_brutto']))
			{
				// Wenn die Preise netto/brutto 0 sind, diese neu berechnen.
				$odata = array();
				
				$adress = $GLOBALS['wpsg_sc']->db->fetchRow("SELECT * FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($o['adress_id'])."' ");
				if (wpsg_isSizedInt($adress['land']))
					$land = $adress['land'];
				else
					$land = 1;
				
				$country = wpsg_country::getInstance($land);
				$arcountry = get_object_vars($country);
				$arcountry = $arcountry['data'];
			
				// Tabelle wp_wpsg_order_products berechnen und updaten
				$oProds = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($o['id'])."' ");
				
				$sum_netto = 0.0;
				$sum_brutto = 0.0;
				
				foreach ($oProds as $op)
				{
					// Aus Tabelle wp_wpsg_products mwst_key holen
					$prod = $GLOBALS['wpsg_sc']->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($op['p_id'])."' ");
					
					if ($prod['mwst_key'] == 'a')
						$mwst = $arcountry['mwst_a'];
					else if ($prod['mwst_key'] == 'b')
						$mwst = $arcountry['mwst_b'];
					else if ($prod['mwst_key'] == 'c')
						$mwst = $arcountry['mwst_c'];
					else if ($prod['mwst_key'] == 'd')
						$mwst = $arcountry['mwst_d'];
					else $mwst = 0.0;
					
					if (!wpsg_isSized($mwst)) $mwst = 0.0;
					$mwst = $mwst / 100;
					
					// mwst_value, price_netto, price_brutto, mwst_key, mod_vp_varkey berechnen
					$opdata = array();
					$opdata['mod_vp_varkey'] = '';
					$opdata['mwst_key'] = $prod['mwst_key'];
					
					if (!wpsg_isSized($op['price_brutto']) && !wpsg_isSized($op['price_netto']))
					{
					
						if ($o['price_frontend'] == WPSG_BRUTTO)
						{
							$opdata['price_brutto'] = $op['price'];
							$opdata['price_netto'] = $op['price'] / (1 + $mwst);
							$opdata['mwst_value'] = $opdata['price_brutto'] - $opdata['price_netto'];
						}
						else
						{
							$opdata['price_netto'] = $op['price'];
							$opdata['price_brutto'] = $op['price'] * (1 + $mwst);
							$opdata['mwst_value'] = $op['price'] * $mwst;
						}
					
						// Summieren für Tabelle wp_wpsg_order
						$sum_netto += $op['menge'] * $opdata['price_netto'];
						$sum_brutto += $op['menge'] * $opdata['price_brutto'];

					}
					else
					{
						$sum_netto += $op['menge'] * $op['price_netto'];
						$sum_brutto += $op['menge'] * $op['price_brutto'];
					}
					
					// Tabelle wp_wpsg_order_products updaten
					$anz2++;
					$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ORDERPRODUCT, $opdata, "`id` = '".$op['id']."'");
					
				}
				
				$mwsta = $sum_brutto / $sum_netto;
				
				// Preise in wp_wpsg_order berechnen
				if ($o['price_frontend'] == WPSG_BRUTTO)
				{
					if ($o['price_gs'] > 0)
					{
						$odata['price_gs_brutto'] = $o['price_gs'];
						$odata['price_gs_netto'] = $o['price_gs'] / $mwsta;
					}
					$sum_brutto -= $odata['price_gs_brutto'];
					$sum_netto -= $odata['price_gs_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_rabatt'] > 0)
					{
						$odata['price_rabatt_brutto'] = $o['price_rabatt'];
						$odata['price_rabatt_netto'] = $o['price_rabatt'] / $mwsta;
					}
					$sum_brutto -= $odata['price_rabatt_brutto'];
					$sum_netto -= $odata['price_rabatt_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_payment'] > 0)
					{
						$sp = $GLOBALS['wpsg_sc']->arPayment[$o['type_payment']];
						if ($sp['mwst_key'] == '0') $mwstx = $mwsta; else $mwstx=$sp['mwst_value'] / 100 + 1;
						$odata['price_payment_brutto'] = $o['price_payment'];
						$odata['price_payment_netto'] = $o['price_payment'] / $mwsta;
						$odata['mwst_payment'] = $odata['price_payment_brutto'] - $odata['price_payment_netto'];
					}
					$sum_brutto += $odata['price_payment_brutto'];
					$sum_netto += $odata['price_payment_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_shipping'] > 0)
					{
						$sp = $GLOBALS['wpsg_sc']->arShipping[$o['type_shipping']];
						if ($sp['mwst_key'] == '0') $mwstx = $mwsta; else $mwstx=$sp['mwst_value'] / 100 + 1;
						$odata['price_shipping_brutto'] = $o['price_shipping'];
						$odata['price_shipping_netto'] = $o['price_shipping'] / $mwstx;
						$odata['mwst_shipping'] = $odata['price_shipping_brutto'] - $odata['price_shipping_netto'];
					}
					$sum_brutto += $odata['price_shipping_brutto'];
					$sum_netto += $odata['price_shipping_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
				}
				else	// if ($o['price_frontend'] == WPSG_BRUTTO)
				{
					if ($o['price_gs'] > 0)
					{
						$odata['price_gs_brutto'] = $o['price_gs'];
						$odata['price_gs_netto'] = $o['price_gs'] * $mwsta;
					}
					$sum_brutto -= $odata['price_gs_brutto'];
					$sum_netto -= $odata['price_gs_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_rabatt'] > 0)
					{
						$odata['price_rabatt_brutto'] = $o['price_rabatt'];
						$odata['price_rabatt_netto'] = $o['price_rabatt'] * $mwsta;
					}
					$sum_brutto -= $odata['price_rabatt_brutto'];
					$sum_netto -= $odata['price_rabatt_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_payment'] > 0)
					{
						$sp = $GLOBALS['wpsg_sc']->arPayment[$o['type_payment']];
						if ($sp['mwst_key'] == '0') $mwstx = $mwsta; else $mwstx=$sp['mwst_value'] / 100 + 1;
						$odata['price_payment_brutto'] = $o['price_payment'];
						$odata['price_payment_netto'] = $o['price_payment'] * $mwstx;
						$odata['mwst_payment'] = $o['price_payment'] * ($mwstx - 1);
					}
					$sum_brutto += $odata['price_payment_brutto'];
					$sum_netto += $odata['price_payment_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
					if ($o['price_shipping'] > 0)
					{
						$sp = $GLOBALS['wpsg_sc']->arShipping[$o['type_shipping']];
						if ($sp['mwst_key'] == '0') $mwstx = $mwsta; else $mwstx=$sp['mwst_value'] / 100 + 1;
						$odata['price_shipping_brutto'] = $o['price_shipping'];
						$odata['price_shipping_netto'] = $o['price_shipping'] * $mwstx;
						$odata['mwst_shipping'] = $o['price_shipping'] * ($mwstx - 1);
					}
					$sum_brutto += $odata['price_shipping_brutto'];
					$sum_netto += $odata['price_shipping_netto'];
					$mwsta = $sum_brutto / $sum_netto;
					
				}	// if ($o['price_frontend'] == WPSG_BRUTTO)  else
					
				$odata['price_gesamt_netto'] = $sum_netto;
				$odata['price_gesamt_brutto'] = $sum_brutto;
				
				// Tabelle wp_wpsg_order updaten
				$anz1++;
				$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ORDER, $odata, "`id` = '".$o['id']."'");
				
			}
		
		}	// foreach ($order as $o)
	
		//$mp->complete(); // fertig!
	
		echo '<br /><br />';
		echo $anz1.' Bestellungen bearbeitet.<br /><br />';
		echo $anz2.' Bestellte Produkte bearbeitet.<br /><br />';
		
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 64) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);

		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_preis()
	
	/**
	 * Allgemeine Konvertierungsfunktionen
	 * 
	 * Shop V3 Zahlungsart Rechnung (4) als Zahlvariante anlegen, wenn verwendet.
	 * Tabelle WPSG_TBL_ORDER Feld type_payment.
	 * In der Tabelle wp_options sind die Einstellungen für die Zahlungsart
	 * Rechnung enthalten, wenn sie im Shop V3 benutzt wurde.
	 * 
	 */
	public function M1_Converter_allgemein() {
		
		global $myprogressbar1;
		global $GLOBALS;
		?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />

		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		$anz1 = 0;
		$anz2 = 0;
		
		$orders = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDER."` WHERE `type_payment`='4' ORDER BY `id` ASC");
		
		// Test
		/*
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_bezeichnung', 'Rechnung');
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_gebuehr', 2);
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_hint', 'Rechnung-Beschreibung');
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_aktiv', 1);
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_mwst', 'c');
		$GLOBALS['wpsg_sc']->update_option('wpsg_mod_invoice_type_mwstland', 0);
		*/
		
		if (wpsg_isSizedArray($orders)) {
			// Zahlvariante anlegen
			$data = array();
			$data['name'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_bezeichnung'));
			$data['rabgeb'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_gebuehr'));
			$data['hint'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_hint'));
			$data['mwst_key'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_mwst'));
			$data['mwst_laender'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_mwstland'));
			$data['aktiv'] = wpsg_q($GLOBALS['wpsg_sc']->get_option('wpsg_mod_invoice_type_aktiv'));
			
			$zid = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_ZV, $data);
			
			$zdata = array();
			$zdata['type_payment'] = $zid;
			
			// Zahlvariante bei diesen Produkten korrigieren
			foreach ($orders as $order) {
				$oid = $order['id'];
				$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_ORDER, $zdata, "`id` = '".$oid."'");
				$anz1++;
			}
			
		}
		
		echo '<br /><br />';
		echo $anz1.' Bestellungen Zahlungsart Rechnungen korrigiert.<br /><br />';
		
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 1) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);
	
		?>
		</div></div></div>
		<?php
		
	}	// public function M1_Converter_allgemein()
	
	
	/**
	 * Konvertierung der Exportprofile
	 *
	 * Die Tabelle wp_wpsg_exportprofile wird erweitert und die
	 * Tabelle wp_wpsg_exportprofile_fields wird neu angelegt.
	 * Shop V3 das Feld data in der Tabelle wp_wpsg_exportprofile wird
	 * unserialisiert und die Kopfdaten in die Tabelle 
	 * wp_wpsg_exportprofile geschrieben.
	 * Die Auflistung des Array fields wird in die Tabelle
	 * wp_wpsg_exportprofile_fields geschrieben.
	 *
	 */
	public function M1_Converter_export() {
		
		global $myprogressbar1;
		global $GLOBALS;
		$anz1 = 0;
		$anz2 = 0;
		?>
		<link rel="stylesheet" href="<?php echo WPSG_PLUGIN_URL.WPSG_FOLDERNAME.'/mods/mod_converter/style.css'; ?>" type="text/css" media="all" />
	
		<div class="m1_converter_admin_content" >
	    	<div class="panel panel-default" >
	    		<div class="panel-heading clearfix" >
	        		<h2 class="panel-title" >M1-Konverter</h2>
				</div>
				
				<div class="panel-body" >
		
	<?php
		
		require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
	
		/*
		 * Tabelle für die Exportprofile
		 */
		$sql = "CREATE TABLE ".WPSG_TBL_EXPORTPROFILE." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name VARCHAR(255) NOT NULL COMMENT 'Der Name des Profils',
		   		filename VARCHAR(255) NOT NULL COMMENT 'Dateiname für den Export',
		   		export_type INT(1) NOT NULL COMMENT 'Typ des Exportprofils (Produkt / Bestellung)',
		   		format INT(1) NOT NULL COMMENT 'Format des Exportes (XML/CSV)',
		   		field_delimiter VARCHAR(1) NOT NULL COMMENT 'Feld-Trennzeichen (CSV)',
		   	  	field_enclosure VARCHAR(1) NOT NULL COMMENT 'Feld-Begrenzungs Zeichen (CSV)',
		   	  	field_escape VARCHAR(1) NOT NULL COMMENT 'Maskierungs-Zeichen (CSV)',
		   	  	order_online INT(1) NOT NULL COMMENT 'Bestellungen in einer Zeile aufführen',
		   	  	order_onetime INT(1) NOT NULL COMMENT 'Bestellungen nur einmal exportieren',
		   	  	csv_fieldnames INT(1) NOT NULL COMMENT 'Beim CSV Export die Feldnamen in erster Zeile aufführen',
		   	  	cron_interval INT(1) NOT NULL DEFAULT '0' COMMENT 'Cron Einstellungen (Inaktiv/Intervall)',
		   	  	cron_path VARCHAR(500) NOT NULL COMMENT 'Pfad in dem die automatischen Dateien abgelegt werden',
		   	  	cron_lastrun DATE NOT NULL COMMENT 'Letzte Ausführung des Crons',
		   	  	orderfilter TEXT NOT NULL COMMENT 'Serialisizerter Bestellfilter',
		   	  	xml_roottag TEXT NOT NULL COMMENT 'Tagname des XML Root Elements',
		   	  	xml_ordertag TEXT NOT NULL COMMENT 'Tagname des XML Bestellung Elements',
		   	  	xml_productroottag TEXT NOT NULL COMMENT 'Tagname des XML Produkt Rootelements',
		   	  	xml_producttag TEXT NOT NULL COMMENT 'Tagname des Produkt Elements',
		   	  	xml_customertag TEXT NOT NULL COMMENT 'Tagname des Kunden Elements (Kundenexport)',
		   	  	file_encoding INT(1) NOT NULL COMMENT 'Encoding der Datei',
		   	  	PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
		dbDelta($sql);
	
		/**
		 * Tabelle für die Felder des Exportprofils
		 */
		$sql = "CREATE TABLE ".WPSG_TBL_EXPORTPROFILE_FIELDS." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				profil_id INT(11) NOT NULL COMMENT 'Link zu WPSG_TBL_EXPORTPROFILE',
				pos int(11) NOT NULL COMMENT 'Position im Export',
				name VARCHAR(255) NOT NULL COMMENT 'Spaltenname / Feldname im XML',
				value_key VARCHAR(255) NOT NULL COMMENT 'Der Schlüssel, mit dem der Wert gefüllt wird',
				format INT(2) NOT NULL COMMENT 'Zellenformat',
				xml_att INT(1) NOT NULL COMMENT 'Tag oder Attributexport',
				userformat VARCHAR(255) NOT NULL COMMENT 'Benutzerdefiniertes Format',
				clear_spaces INT(1) NOT NULL COMMENT 'Leerzeichen entfernen',
				INDEX profil_id (profil_id),
				PRIMARY KEY  (id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
		dbDelta($sql);
	
		$sql = "SELECT * FROM ".WPSG_TBL_EXPORTPROFILE;
		$arep = $GLOBALS['wpsg_sc']->db->fetchAssoc($sql);
	
		$test = unserialize($arep[0]['data']);
	
		foreach ($arep as $ep)
		{
			$data = array();
			if (!isset($ep['data'])) continue;	// Keine WPSG3-Version
			if (($ep['data'] == '')) continue;
			$us = unserialize($ep['data']);
			
			$data['filename'] = $us['filename']; // Dateiname für den Export
			$data['export_type'] = self::TYPE_ORDER; // Typ des Exportprofils (Produkt / Bestellung)
			$data['format'] = self::FORMAT_CSV; // Format des Exportes (XML/CSV)
			$data['field_delimiter'] = $us['separator']; // Feld-Trennzeichen (CSV)
			$data['field_enclosure'] = '"'; // Feld-Begrenzungs Zeichen (CSV)
			$data['field_escape'] = wpsg_q('\\'); // Maskierungs-Zeichen (CSV)
			$data['order_online'] = $us['oneline']; // Bestellungen in einer Zeile aufführen
			$data['order_onetime'] = 0; // Bestellungen nur einmal exportieren
			$data['csv_fieldnames'] = $us['firstlinecolname']; // Beim CSV Export die Feldnamen in erster Zeile aufführen
			$data['cron_interval'] = 0; // Cron Einstellungen (Inaktiv/Intervall)
			$data['cron_path'] = ''; // Pfad in dem die automatischen Dateien abgelegt werden
			$data['cron_lastrun'] = '0000-00-00'; // Letzte Ausführung des Crons
			$data['orderfilter'] = ''; // Serialisierter Bestellfilter
			$data['xml_roottag'] = ''; // Tagname des XML Root Elements
			$data['xml_ordertag'] = ''; // Tagname des XML Bestellung Elements
			$data['xml_productroottag'] = ''; // Tagname des XML Produkt Rootelements
			$data['xml_producttag'] = ''; // Tagname des Produkt Elements
			$data['xml_customertag'] = ''; // Tagname des Kunden Elements (Kundenexport)
			$data['file_encoding'] = $us['iso']; // Encoding der Datei
			
			$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_EXPORTPROFILE, $data, "`id` = '".wpsg_q($ep['id'])."'");
			$anz1++;
			
			$i = 0;
			foreach ($us['fields'] as $epf)
			{
				$fdata = array();
				$fdata['profil_id'] = $ep['id']; // Link zu WPSG_TBL_EXPORTPROFILE
				$fdata['pos'] = $i; // Position im Export
				$fdata['name'] = $epf['name']; // Spaltenname / Feldname im XML
				$fdata['value_key'] = $epf['value']; // Der Schlüssel, mit dem der Wert gefüllt wird
				$fdata['format'] = 0;
				if (isset($epf['format'])) $fdata['format'] = $epf['format']; // Zellenformat
				$fdata['xml_att'] = 0; // Tag oder Attributexport
				$fdata['userformat'] = ''; // Benutzerdefiniertes Format
				$fdata['clear_spaces'] = 0; // Leerzeichen entfernen
				$i++;
				
				$epid = $GLOBALS['wpsg_sc']->db->ImportQuery(WPSG_TBL_EXPORTPROFILE_FIELDS, $fdata);
				$anz2++;
			}
		
		}
		
		echo '<br /><br />';
		echo $anz1.' Exportprofile bearbeitet.<br /><br />';
		echo $anz2.' Exportprofil-Felder angelegt.<br /><br />';
		
		unset($_REQUEST['action']);
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_status') == 1023 - 512) {
			echo '<br />Konvertierung beendet<br /><br />';
			echo '<a class="m1c_button" href="'.admin_url('').'">Zurück zum Backend</a><br />';
		} else {
			echo '<a class="m1c_button" href="'.admin_url('admin.php?page=M1_Converter_dispatch&action=show').'">Zurück</a><br />';
		}
		ini_set('max_execution_time', 30);
		
		?>
		</div></div></div>
		<?php
		
		
	}
	
	public function M1_Converter_test() {
		
		global $myprogressbar1;
		global $GLOBALS;
	?>
	    <div class="wrap">
	        <h2>M1-Konverter</h2>
		</div>
		
	<?php
		
		ini_set('max_execution_time', 1200);
	
		$fn1 = sanitize_title('Baby bumble chrom.jpg');
		$fn2 = sanitize_file_name('Baby bumble chrom.jpg');
		$fn3 = sanitize_file_name('Baby-- bumble chrom.jpg');
		
		$products = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS."` ORDER BY `id` ASC");
		
		$i1 = 0;
		$i2 = 0;
		$anz = count($products);

		echo '<br /><br />';
		echo $anz.' Produkte.<br /><br />';
		$this->m1cpb1->print_code();
		
		$test = unserialize('a:1:{i:1;a:4:{s:4:"name";s:5:"Farbe";s:3:"typ";s:6:"select";s:5:"aktiv";s:1:"1";s:4:"vari";a:5:{i:0;a:7:{s:4:"name";s:3:"rot";s:5:"preis";s:1:"0";s:5:"aktiv";s:1:"1";s:3:"pic";s:51:",bimble-baby-red-1-small.png,bimble-red-2-small.png";s:5:"artnr";s:5:"10-12";s:5:"stock";s:1:"2";s:6:"weight";s:1:"0";}i:1;a:7:{s:4:"name";s:7:"limette";s:5:"preis";s:1:"0";s:5:"aktiv";s:1:"1";s:3:"pic";s:24:",bimble-lime-2-small.png";s:5:"artnr";s:5:"10-14";s:5:"stock";s:1:"1";s:6:"weight";s:1:"0";}i:2;a:6:{s:4:"name";s:4:"gelb";s:5:"preis";s:0:"";s:5:"aktiv";s:1:"1";s:5:"stock";s:1:"3";s:5:"artnr";s:5:"10-11";s:3:"pic";s:26:",bimble-yellow-2-small.png";}i:3;a:6:{s:4:"name";s:7:"violett";s:5:"preis";s:0:"";s:5:"aktiv";s:1:"1";s:5:"stock";s:1:"3";s:5:"artnr";s:5:"10-13";s:3:"pic";s:26:",bimble-purple-2-small.png";}i:4;a:6:{s:4:"name";s:7:"türkis";s:5:"preis";s:0:"";s:5:"aktiv";s:1:"1";s:5:"stock";s:1:"3";s:5:"artnr";s:5:"10-15";s:3:"pic";s:24:",bimble-cyan-2-small.png";}}}}');


		foreach ($products as $p)
		{
			$varis = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `product_id`='".wpsg_q($p['id'])."' ");
			
			foreach ($varis as $v)
			{
				$im0 = unserialize($v['images']);
				
				$im1 = array();
				$im1 = $im0;
				//$im1['pic'] = array(); 
				//$im1['picOrder'] = $im0['picOrder'];
				//$im1['postid'] = array();
				
				$pids = explode(',', $im0['postid']);
				$postid = array();
				$pic = array();
				foreach ($pids as $pid)
				{
					
					$post = $GLOBALS['wpsg_sc']->db->fetchRow("SELECT * FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID` = '".wpsg_q($pid)."' ");
					if (isset($post['ID'])) 
					{
						$postid[] = $post['ID'];
						$pic[] = $post['post_excerpt'];
					}
				}
				$im1['pic'] = implode(',', $pic);
				$im1['postid'] = implode(',', $postid);
				$images = serialize($im1);
				// Update WPSG_TBL_PRODUCTS_VARIATION
				$data = array('images' => $images);
				////$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARIATION, $data, "`id` = '".wpsg_q($v['id'])."'");
				
			}
			
		}
		
		
		//wp_delete_post( $postid, $force_delete );
		//wp_delete_attachment( $attachmentid, $force_delete );
		//SELECT * FROM `wp_postmeta` WHERE `meta_key`='wpsg_produkt_id' AND `meta_value`=11
		
		$posts = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".$GLOBALS['wpdb']->prefix."postmeta` WHERE `meta_key`='".wpsg_q('wpsg_produkt_id')."' ORDER BY `post_id`");
		foreach ($posts AS $post) {
			////wp_delete_attachment($post['post_id'], true);
			
		}
		////$GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `post_title` = '".wpsg_q('wpsg_variants_import')."' ");
		
		foreach ($products as $p)
		{
			$arPic = $this->getProduktBilder($p['id'], false);
			$i1++;
			$i2 = $i2 + count($arPic);

			$mp = $this->m1cpb1;
			$mp->step(100 / $anz);
			usleep(20000);			// 20ms
			//sleep(1);
			$temp = set_time_limit(2000);		// Retriggern
			//if ($temp == false) echo 'set_time_limit=false ';
			
		}
		
		//$mp->set(15); // auf 15 % setzen
		$mp->complete(); // fertig!
		
		echo '<br /><br />';
		echo $anz.' Produkte.<br /><br />';
	
		unset($_REQUEST['action']);
		echo '<a class="m1c_button " style="cursor:pointer; margin-top:10px; width:120px; text-align:center; padding:5px 5px 5px 5px; line-height:28px; border:1px solid #29556E; color:#29556E; background-color:#ffffff; font-weight:bold; display:inline-block;text-decoration:none;" href="';
		echo admin_url('admin.php?page=M1_Converter_dispatch');
		echo '">Zurück</a><br />';
		ini_set('max_execution_time', 30);
	
	}	// public function M1_Converter_test()
	

	/*
	 * Gibt den absoluten Pfad zurück wo die Bilder gespeichert sind
	 * Ist der Parameter $url auf true so wird der relative Pfad für die Ausgabe in 
	 * URLs zurückgegeben.
	 * 
	 */
	public function getPicPath($produkt_id, $url = false, $mkdir = true)
	{
		
		if ($GLOBALS['wpsg_sc']->isMultiBlog())
		{
			
			if ($url) return WPSG_URL_CONTENT.WPSG_MB_UPLOADS.'/wpsg/wpsg_produktbilder/'.$produkt_id.'/';
			else
			{
				
				$path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_produktbilder/'.$produkt_id.'/';
				
				if (!file_exists($path) && ($mkdir == true)) mkdir($path, 0777, true);
				
				return $path;
				
			}
			
		}
		else
		{
			
			if ($url) return WPSG_URL_CONTENT.'uploads/wpsg_produktbilder/'.$produkt_id.'/';
			else
			{
				
				$path = WP_CONTENT_DIR.'/uploads/wpsg_produktbilder/'.$produkt_id.'/';
				
				if (!file_exists($path) && ($mkdir == true)) mkdir($path, 0777, true);
				
				return $path;
				
			}
			
		}
		
	} // function getPicPath($produkt_id, $url = false)

	/**
	 * Gibt ein Array mit den Produktbildern zurück
	 */
	public function getProduktBilder($produkt_id, $mkdir = true)
	{
		
		$arFiles = array();
		
		// Bilder aus dem Dateisystem raussuchen
		$path = $this->getPicPath($produkt_id, false, $mkdir);
		
		if (!file_exists($path) && ($mkdir == false)) return array();
		
		$handle = opendir($path);
		
		while ($filename = readdir($handle))
		{
			
			if (is_file($path.'/'.$filename) && $filename != '.' && $filename != '..' && $filename != '')
			{
				
				$arFiles[] = $filename;
				
			}
			
		}
		
		wpsg_asort($arFiles);
		
		$arReturn = array();
		foreach ($arFiles as $k => $v) $arReturn[] = $v;
		
		return $arReturn;
		
	} // function getProduktBilder($produkt_id)

} // class M1_Converter
?>