<?php

	/**
	 * Modulklasse "Produktvarianten"
	 * @author Daschmi (27.04.2016)
	 */
	class wpsg_mod_productvariants extends wpsg_mod_basic
	{

		var $lizenz = 1;
		var $id = 91;

		const TYPE_SELECT = 0;
		const TYPE_RADIO = 1;
		const TYPE_IMAGE = 2;

		static $arTypeLabel;

		/**
		 * Constructor
		 */
		public function __construct()
		{

			parent::__construct();

			self::$arTypeLabel = array(
				self::TYPE_SELECT => __('Select Box', 'wpsg'),
				self::TYPE_RADIO => __('Radio Boxen', 'wpsg'),
				self::TYPE_IMAGE => __('Bilderauswahl', 'wpsg')
			);

			$this->name = __('Produktvarianten', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht es zentral Varianten anzulegen und diese im Produkt zu aktivieren.', 'wpsg');

			wpsg_define('WPSG_TBL_VARIANTS', $this->shop->prefix.'wpsg_variants');
			wpsg_define('WPSG_TBL_VARIANTS_VARI', $this->shop->prefix.'wpsg_variants_vari');
			wpsg_define('WPSG_TBL_PRODUCTS_VARIANT', $this->shop->prefix.'wpsg_products_variant');
			wpsg_define('WPSG_TBL_PRODUCTS_VARIATION', $this->shop->prefix.'wpsg_products_variation');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			$sql = "CREATE TABLE ".WPSG_TBL_VARIANTS." (
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				product_id int(11) NOT NULL,
				deleted int(1) NOT NULL,
				pos int(11) NOT NULL,
				type int(11) NOT NULL,
				PRIMARY KEY  (id),
				KEY product_id (product_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

			$sql = "CREATE TABLE ".WPSG_TBL_VARIANTS_VARI." (
				id int(11) NOT NULL AUTO_INCREMENT,
				variant_id int(11) NOT NULL,
				name varchar(255) NOT NULL,
				shortname varchar(255) NOT NULL,
				anr varchar(255) NOT NULL,				 
				deleted int(1) NOT NULL,
				pos int(11) NOT NULL,
				KEY variant_id (variant_id),
				PRIMARY KEY  (id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_VARIANT." (
				id int(11) NOT NULL AUTO_INCREMENT,
				variant_id int(11) NOT NULL,
				product_id int(11) NOT NULL,
				pos int(11) NOT NULL,
				PRIMARY KEY  (id),
				KEY product_id (product_id),
				KEY variant_id (variant_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_VARIATION." (
				id int(11) NOT NULL AUTO_INCREMENT,
				variation_id int(11) NOT NULL,
				product_id int(11) NOT NULL,
				active int(1) NOT NULL,
				anr varchar(255) NOT NULL,
				price double(10,2) NOT NULL,
				stock int(11) NOT NULL,
				min_stock int(11) NOT NULL,
				images text NOT NULL,
				images_set text NOT NULL,
				weight double(10,2) NOT NULL,
				fmenge double(10,2) NOT NULL,
				PRIMARY KEY  (id),
				KEY product_id (product_id),
				KEY variation_id (variation_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

			dbDelta($sql);

		} // public function install()

		public function settings_edit()
		{

			$this->shop->view['wpsg_mod_productvariants']['html'] = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/admin_html.phtml', false);

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/settings_edit.phtml');

		} // public function settings_edit_afterform()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_productvariants_price', $_REQUEST['wpsg_mod_productvariants_price'], false, false, WPSG_SANITIZE_CHECKBOX);
		    $this->shop->update_option('wpsg_vp_detailview', $_REQUEST['wpsg_vp_detailview'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_vp_replaceanr', wpsg_xss($_REQUEST['wpsg_vp_replaceanr']));

		} // public function settings_save()

		public function be_ajax()
		{

			$action = $_REQUEST['subaction'].'Action';

			$this->$action();

		} // public function be_ajax()

		public function wpsg_enqueue_scripts()
		{

			if (!is_admin())
			{

				wp_enqueue_script('wpsg_mod_productvariants_js', $this->shop->getRessourceURL('mods/mod_productvariants/frontend.js'));
				wp_localize_script('wpsg_mod_productvariants_js', 'wpsg_vp_showpic', array('wpsg_vp_showpic' => $this->shop->get_option('wpsg_vp_showpic')));

			}

		} // public function wpsg_enqueue_scripts()

		public function product_addedit_content(&$product_content, &$product_data)
		{

			// Wenn eine Übersetzung bearbeitet wird, dann nichts machen
			if (isset($_REQUEST['wpsg_lang'])) return;

			if (wpsg_isSizedInt($product_data['id']))
			{

				$this->shop->view['wpsg_mod_productvariants']['html'] = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/admin_html.phtml', false);

			}

			$product_content['wpsg_mod_productvariants'] = array(
				'title' => __('Produktvarianten', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/produkt_addedit_content.phtml', false)
			);

		} // public function product_addedit_content(&$product_content, &$produkt_data)

		public function produkt_copy(&$produkt_id, &$copy_id)
		{

			// Kopieren der Varianten/Variationen
			$variants = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_VARIANTS."` WHERE `product_id` = '".wpsg_q($produkt_id)."'");
			$pos = 0;

			foreach ($variants as $v) {

				$v['product_id'] = $copy_id;
				$v['pos'] = $pos;
				$vid = $v['id'];
				unset($v['id']);
				$newvid = $this->db->ImportQuery(WPSG_TBL_VARIANTS, $v);

				// WPSG_TBL_PRODUCTS_VARIANT schreiben
				$pv = array();
				$pv['variant_id'] = $newvid;
				$pv['product_id'] = $copy_id;
				$pv['pos'] = $pos;
				unset($pv['id']);
				$newpvid = $this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIANT, $pv);
				$pos++;

				$varis = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_VARIANTS_VARI."` WHERE `variant_id` = '".wpsg_q($vid)."'");

				$mimages = array();

				foreach ($varis as $vv) {
					$vv['variant_id'] = $newvid;
					$vvid = $vv['id'];
					unset($vv['id']);
					$newvvid = $this->db->ImportQuery(WPSG_TBL_VARIANTS_VARI, $vv);

					$pvari = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `variation_id` = '".wpsg_q($vvid)."' AND `product_id` = '".wpsg_q($produkt_id)."'");
					$pvari['variation_id'] = $newvvid;
					$pvari['product_id'] = $copy_id;
					
					$images = explode(',', $pvari['images']);
					$images_set = explode(',', $pvari['images_set']);
					
					foreach ($images as $k => $i_id) {
						
						if (isset($GLOBALS['wpsg_product_copy_imagemapping'][$i_id])) $images[$k] = $GLOBALS['wpsg_product_copy_imagemapping'][$i_id];
						else unset($images[$k]);
						
					}
					
					foreach ($images_set as $k => $i_id) {
						
						if (isset($GLOBALS['wpsg_product_copy_imagemapping'][$i_id])) $images_set[$k] = $GLOBALS['wpsg_product_copy_imagemapping'][$i_id];
						else unset($images_set[$k]);
						
					}
					
					$pvari['images'] = implode(',', $images);
					$pvari['images_set'] = implode(',', $images_set);
					
					unset($pvari['id']);
					
					$this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $pvari);
					
					// images/images_set
					/*
					$images = explode(',', $pvari['images']);
					$images_set = explode(',', $pvari['images_set']);
					$ih = new wpsg_imagehandler();

					$nimages = array();
					$nimages_set = array();

					foreach ($images as $postid) {

						if (wpsg_isSizedInt($postid)) {
							
							$post = $this->db->fetchRow("SELECT * FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID`='".wpsg_q($postid)."'");							
							$npostid = $ih->addImageToProduct($post['guid'], $copy_id);
															
						}
						
						if (!wpsg_isSizedArray($mimages['alt']) || !in_array($postid, $mimages['alt'])) {
							
							$mimages['alt'][] = $postid;
							$post = $this->db->fetchRow("SELECT * FROM `".$GLOBALS['wpdb']->prefix."posts` WHERE `ID`='".wpsg_q($postid)."'");
							// addImageToProduct($file, $product_id)
							$npostid = $ih->addImageToProduct($post['guid'], $copy_id);
							//$nimages[] = $npostid;
							$mimages['neu'][] = $npostid;

						}

					}

					foreach ($images_set as $iset) {
						$ak = array_search($iset, $mimages['alt']);
						$nimages_set[] = $mimages['neu'][$ak];
					}
					foreach ($images as $iset) {
						$ak = array_search($iset, $mimages['alt']);
						$nimages[] = $mimages['neu'][$ak];
					}

					$pvari['images'] = implode(',', $nimages);
					$pvari['images_set'] = implode(',', $nimages_set);
					unset($pvari['id']);
					$newpvid = $this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $pvari);
					*/
					
					

				}

			}

		}	// public function produkt_copy($produkt_id, $copy_id)

		public function template_redirect()
		{

			if (wpsg_isSizedString($_REQUEST['action'], 'wpsg_productvariants_switch'))
			{

				if (wpsg_isSizedInt($_REQUEST['wpsg_post_id']))
				{

					global $post;
					$post = get_post($_REQUEST['wpsg_post_id']);

				}

				// Produkt Key nach der Auswahl bilden
				parse_str($_REQUEST['form_data'], $form_data);

				$product_id = $this->shop->getProduktId($form_data['wpsg']['produkt_id']);
                $product_key = '';

                $this->getProductKeyFromRequest($product_key, $product_id, $form_data);

				$product_data = $this->shop->loadProduktArray($product_id, array(
					'id' => $product_key,
					'product_key' => $product_key,
					'menge' => $form_data['wpsg']['menge'],
					'referer' => $form_data['myReferer']
				));

				// Damit das Div die gleiche Index Id bekommt
				$GLOBALS['wpsg_produkt_index'] = $_REQUEST['product_index'] - 1;

				$this->shop->noReleatedProducts = true;

				if (wpsg_isSizedString($form_data['wpsg']['template'])) $template = $form_data['wpsg']['template'];
				else $template = false;

				if (wpsg_isSizedInt($form_data['titleDisplayed'])) $this->shop->titleDisplayed = 1;

				//$dat = $this->shop->renderProdukt($product_data, $template);

				die($this->shop->renderProdukt($product_data, $template));

			}

		} // public function template_redirect()
		
		/**
		 * @param wpsg_product $oProduct
		 * @param string $product_key
		 */
		public function product_setProductKey(&$oProduct, $product_key) { 
			
			if ($this->isVariantsProductKey($product_key)) {
				
				$price = $oProduct->preis; 
				
				foreach ($this->getVariantenInfoArray($product_key) as $k => $v) {
					
					if (is_numeric($k)) {
						
						$price += $v['preis'];
												
					}
					
				}
			
				if ($this->shop->getBackendTaxview() === WPSG_BRUTTO) {
					
					$oProduct->loadedData['preis_brutto'] = $price;
					$oProduct->loadedData['preis_netto'] = wpsg_calculatePreis($price, WPSG_NETTO, $oProduct->getDefaultTaxValue());
					
				} else {
					
					$oProduct->loadedData['preis_netto'] = $price;
					$oProduct->loadedData['preis_brutto'] = wpsg_calculatePreis($price, WPSG_BRUTTO, $oProduct->getDefaultTaxValue());
					
				}
				
				if ($this->shop->getFrontendTaxView() === WPSG_BRUTTO) {
					
					$oProduct->loadedData['preis'] = $oProduct->loadedData['preis_brutto'];
					
				}
				else
				{
					
					$oProduct->loadedData['preis'] = $oProduct->loadedData['preis_netto'];
					
				}
				
				
			}
			
		}
		
		public function loadProduktArray(&$product_data)
		{

			//if ($GLOBALS['step'] > 4) return;

			$product_data['arVariant'] = $this->getVariants($product_data['product_id'], true, true, true);

			if (wpsg_isSizedArray($product_data['arVariant']))
			{

				// Default Kombination setzen
                if (preg_match('/^pv_/', $product_data['id']))
                {

                    $product_data['product_key'] = $product_data['id'];

                }
				else if (!isset($product_data['product_key']) || is_numeric($product_data['product_key']))
				{

					$arDefaultKey = array();

					foreach ($product_data['arVariant'] as $variant_id => $variant_data)
					{

						$arDefaultKeyValues = $variant_id.':'.array_keys($variant_data['arVariation'])[0];
						$arDefaultKey[] = $arDefaultKeyValues;

					}

					$strDefaultKey = 'pv_'.$product_data['id'].'|'.implode('|', $arDefaultKey);

					$product_data['product_key'] = $strDefaultKey;
					$product_data['id'] = $strDefaultKey;

				}

				$arProductKey = $this->explodeProductKey($product_data['product_key']);

                $product_data['stock'] = 0;

                foreach ($arProductKey['arVari'] as $var_id => $vari_id) {

                    $product_data['stock'] += $product_data['arVariant'][$var_id]['arVariation'][$vari_id]['stock'];

                }

				$arProductImagesPossible = array();
				$arPostidsImagesPossible = array();

				// Gesetzte Variante wählen und aufwerten
				foreach ($product_data['arVariant'] as $var_id => $var_data)
				{

					foreach ($var_data['arVariation'] as $vari_id => $vari_data)
					{

						if ($arProductKey['arVari'][$var_id] == $vari_id)
						{

							$vari_data['pics']= explode(',', $vari_data['images_set']);
							$vari_data['postids']= explode(',', $vari_data['images']);
							/*
							$pics = unserialize($vari_data['images']);
							unset($vari_data['pics']);
							if (wpsg_isSizedArray($pics)) {
								$pic = explode(',', $pics['pic']);
								if (strlen($pic[0]) == 0) unset($pic[0]);
								$postids = explode(',', $pics['postid']);
								if (strlen($postids[0]) == 0) unset($postids[0]);
								//$vari_data['images'] = array('pic' => array());
								//$vari_data['images']['pic'] = $pic;
								foreach ($pic as $k => &$file) {
									$file = preg_replace('/\-(\d+)x(\d+)\./', '.', $file);
								}
								$vari_data['pics'] = $pic;
								$vari_data['postids'] = $postids;
							}
							*/
							if (isset($vari_data['pics'])) {
								if (wpsg_isSizedArray($arProductImagesPossible)) $arProductImagesPossible = array_intersect($arProductImagesPossible, $vari_data['pics']);
								else if (wpsg_isSizedArray($vari_data['pics'])) $arProductImagesPossible = $vari_data['pics'];

							}
							if (isset($vari_data['postids'])) {
								if (wpsg_isSizedArray($arPostidsImagesPossible)) $arPostidsImagesPossible = array_intersect($arPostidsImagesPossible, $vari_data['postids']);
								else if (wpsg_isSizedArray($vari_data['postids'])) $arPostidsImagesPossible = $vari_data['postids'];

							}

							$product_data['arVariant'][$var_id]['arVariation'][$vari_id]['set'] = true;

						}
						else $product_data['arVariant'][$var_id]['arVariation'][$vari_id]['set'] = false;

					}

				}

				if (wpsg_isSizedArray($arPostidsImagesPossible)) $product_data['image_postid'] = array_shift($arPostidsImagesPossible);

				if (wpsg_isSizedArray($arProductImagesPossible)) $product_data['image_show'] = array_shift($arProductImagesPossible);
				if (wpsg_isSizedString($product_data['image_show'])) $product_data['image_show'] = sanitize_file_name($product_data['image_show']);

				// Preise für die Varianten berechnen
				if (!(isset($product_data['varPriceAdded']) && ($product_data['varPriceAdded'] = 1)))
				{

					$product_data['preis_netto_preVariants'] = $product_data['preis_netto'];
					$product_data['preis_brutto_preVariants'] = $product_data['preis_brutto'];

					if ($this->shop->getBackendTaxView() === WPSG_BRUTTO)
					{

						$product_data['preis_brutto'] = $this->calculateVariantsPrice($product_data['preis_brutto'], $product_data['arVariant'], $product_data['product_key']);
						$product_data['preis_netto'] = wpsg_calculatePreis($product_data['preis_brutto'], WPSG_NETTO, $product_data['mwst_value']);

					}
					else
					{

						$product_data['preis_netto'] = $this->calculateVariantsPrice($product_data['preis_netto'], $product_data['arVariant'], $product_data['product_key']);
						$product_data['preis_brutto'] = wpsg_calculatePreis($product_data['preis_netto'], WPSG_BRUTTO, $product_data['mwst_value']);

					}

					$product_data['preis_preVariants'] = $product_data['preis'];

				}

				// Preise für das Frontend ermitteln
				if ($this->shop->getFrontendTaxView() === WPSG_BRUTTO)
				{

					$product_data['preis'] = $product_data['preis_brutto'];

				}
				else
				{

					$product_data['preis'] = $product_data['preis_netto'];

				}

			}

		} // public function loadProduktArray(&$produkt_data)

        public function mail_row($index, $produkt)
        {

            if (!preg_match('/pv_(.*)/', $produkt['id'])) return;

            $this->shop->view['variante'] = $this->getVariantenInfoArray($produkt['id']);
            $this->shop->view['i'] = $index;

            if ($this->shop->htmlMail === true) $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/mail_row_html.phtml');
            else $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/mail_row.phtml');

        } // public function mail_row($index, $produkt)

        public function order_view_row(&$p, $i)
        {

            if (!preg_match('/pv_(.*)/', $p['product_key'])) return;

            $this->shop->view['variante'] = $this->getVariantenInfoArray($p['product_key']);
            $this->shop->view['i'] = $i;

            $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/order_view_row.phtml');

        } // public function order_view_row(&$p, $i)

        public function getProductKeyFromRequest(&$product_key, $product_id, $form_data)
        {
 
        	if (!wpsg_isSizedArray($form_data['wpsg_vp'])) return false;

        	foreach ($form_data['wpsg_vp'] as $var_id => $vari_id) $form_data['wpsg_vp'][$var_id] = $var_id.':'.$vari_id;
        	$product_key = 'pv_'.$product_id.'|'.implode('|', $form_data['wpsg_vp']);

        } // public function getProductKeyFromRequest($product_id, $form_data)

        public function produkt_ajax() {

			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw new \Exception(__('Ungültige ID bei den Produktvarianten BE Ajax', 'wpsg'));
        	
        	if ($_REQUEST['cmd'] == 'wpsg_var_setImageOrder')
        	{
				
				$vp_data = $this->getVariants($_REQUEST['edit_id'], true, false, true);
				
				$vp_data[$_REQUEST['var_id']]['arVariation'][$_REQUEST['vari_id']]['images'] = implode(',', $_REQUEST['wpsg_reorder']);

				$this->saveVariantsData($vp_data);

        		die("1");
				
        	}
        	else if ($_REQUEST['cmd'] == 'wpsg_vp_vari_setPic')
        	{

        		$vp_data = $this->getVariants($_REQUEST['edit_id'], true, false, true);
				
				$arImages = explode(',', @$vp_data[$_REQUEST['var_id']]['arVariation'][$_REQUEST['vari_id']]['images_set']);
				
				if (!in_array($_REQUEST['attachment_id'], $arImages)) $arImages[] = $_REQUEST['attachment_id'];
				else unset($arImages[array_search($_REQUEST['attachment_id'], $arImages)]);
				
				$vp_data[$_REQUEST['var_id']]['arVariation'][$_REQUEST['vari_id']]['images_set'] = implode(',', wpsg_trim(array_unique($arImages)));
				
				$this->saveVariantsData($vp_data);

        		die("1");

        	}
        	else if ($_REQUEST['cmd'] == 'wpsg_vp_vari_inlineEdit')
        	{
        		die('wpsg_vp_vari_inlineEdit');

        		$vp_data = $this->loadVarianten($_REQUEST['edit_id']);
        		$product_data = $this->shop->cache->loadProduct($_REQUEST['edit_id']);

        		if ($_REQUEST['typ'] == "vari_name")
        		{

        			$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

        			if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        			{
        				$vp_data[$_REQUEST['var_id']]['lang'][$_REQUEST['wpsg_lang']]['vari'][$_REQUEST['vari_id']]['name'] = wpsg_q($_REQUEST['value']);
        			}
        			else
        			{
        				$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['name'] = wpsg_q($_REQUEST['value']);
        			}

        		}
        		else if ($_REQUEST['typ'] == "vari_artnr")
        		{

			        $_REQUEST['value'] = wpsg_xss($_REQUEST['value']);

        			if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        			{
        				$vp_data[$_REQUEST['var_id']]['lang'][$_REQUEST['wpsg_lang']]['vari'][$_REQUEST['vari_id']]['artnr'] = wpsg_q($_REQUEST['value']);
        			}
        			else
        			{
        				$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['artnr'] = wpsg_q($_REQUEST['value']);
        			}

        		}
        		else if ($_REQUEST['typ'] == "vari_preis")
        		{

			        $_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

        			$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['preis'] = $_REQUEST['value'];
        			$_REQUEST['value'] = wpsg_ff($_REQUEST['value'], $this->shop->get_option('wpsg_currency'));

        		}
        		else if ($_REQUEST['typ'] == "vari_fmenge")
        		{

			        $_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

        			$arFeinheiten = explode(',', $this->shop->get_option('wpsg_mod_fuellmenge_einheit'));

        			$_REQUEST['value'] = wpsg_ff($_REQUEST['value'], $arFeinheiten[$product_data['feinheit']]);
        			$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['fmenge'] = $_REQUEST['value'];

        		}
        		else if ($_REQUEST['typ'] == "vari_weight")
        		{

			        $_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

        			$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['weight'] = $_REQUEST['value'];

        		}
        		else if ($_REQUEST['typ'] == "vari_stock")
        		{

        			$_REQUEST['value'] = intval(wpsg_sinput("key", $_REQUEST['value']));
        			$vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['stock'] = $_REQUEST['value'];

        		}
		        else if ($_REQUEST['typ'] == "vari_min_stock")
		        {

			        $_REQUEST['value'] = intval(wpsg_sinput("key", $_REQUEST['value']));
			        $vp_data[$_REQUEST['var_id']]['vari'][$_REQUEST['vari_id']]['min_stock'] = $_REQUEST['value'];

		        }
        		else if ($_REQUEST['typ'] == "var_name")
        		{

			        $_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

        			if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        			{
        				$vp_data[$_REQUEST['var_id']]['lang'][$_REQUEST['wpsg_lang']]['name'] = $_REQUEST['value'];
        			}
        			else
        			{
        				$vp_data[$_REQUEST['var_id']]['name'] = $_REQUEST['value'];
        			}

        		}
        		else if ($_REQUEST['typ'] == "var_preis")
        		{

        			$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));
        			$vp_data[$_REQUEST['var_id']]['preis'] = $_REQUEST['value'];
        			$_REQUEST['value'] = wpsg_ff($_REQUEST['value'], $this->shop->get_option('wpsg_currency'));

        		}
        		else if ($_REQUEST['typ'] == "var_weight")
        		{

        			$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));
        			$vp_data[$_REQUEST['var_id']]['weight'] = $_REQUEST['value'];

        		}
        		else if ($_REQUEST['typ'] == "var_fmenge")
        		{

        			$arFeinheiten = explode(',', $this->shop->get_option('wpsg_mod_fuellmenge_einheit'));

        			$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"), $arFeinheiten[$product_data['feinheit']]);
        			$vp_data[$_REQUEST['var_id']]['fmenge'] = $_REQUEST['value'];

        		}
        		else if ($_REQUEST['typ'] == "var_artnr")
        		{

			        $_REQUEST['value'] = wpsg_xss($_REQUEST['value']);

        			if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        			{
        				$vp_data[$_REQUEST['var_id']]['lang'][$_REQUEST['wpsg_lang']]['artnr'] = wpsg_q($_REQUEST['value']);
        			}
        			else
        			{
        				$vp_data[$_REQUEST['var_id']]['artnr'] = wpsg_q($_REQUEST['value']);
        			}

        		}
        		else if ($_REQUEST['typ'] == "var_stock")
        		{

        			$_REQUEST['value'] = intval(wpsg_sinput("key", $_REQUEST['value']));
        			$vp_data[$_REQUEST['var_id']]['stock'] = $_REQUEST['value'];

        		}
		        else if ($_REQUEST['typ'] == "var_min_stock")
		        {

			        $_REQUEST['value'] = intval(wpsg_sinput("key", $_REQUEST['value']));
			        $vp_data[$_REQUEST['var_id']]['min_stock'] = $_REQUEST['value'];

		        }

        		$this->saveVariantsData($vp_data);

        		die(strval(wpsg_xss($_REQUEST['value'])));

        	}

        } // public function produkt_ajax()
		
		public function product_getPrice(&$oProduct, &$price_netto, &$price_brutto, $product_key, $amount, $weight) {
			
			if ($this->isVariantsProduct($oProduct->id)) {
				
				$arVariant = $this->getVariants($oProduct->id, true, true, true);
				
				if ($product_key === false) {
					
					// Standardschlüssel ermitteln
										
					foreach ($arVariant as $variant_id => $variant_data)
					{
						
						$arDefaultKeyValues = $variant_id.':'.array_keys($variant_data['arVariation'])[0];
						$arDefaultKey[] = $arDefaultKeyValues;
						
					}
					
					$product_key = 'pv_'.$oProduct->id.'|'.implode('|', $arDefaultKey);
					
				}
				
			}
			
			if ($this->isVariantsProductKey($product_key)) {
				
				if ($this->shop->getBackendTaxView() === WPSG_BRUTTO) {
						
					$price_brutto = $this->calculateVariantsPrice($price_brutto, $arVariant, $product_key);
					$price_netto = wpsg_calculatePreis($price_brutto, WPSG_NETTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
					
				} else {
					
					$price_netto = $this->calculateVariantsPrice($price_netto, $arVariant, $product_key);
					$price_brutto = wpsg_calculatePreis($price_netto, WPSG_BRUTTO, $this->shop->getDefaultCountry()->getTax($oProduct->mwst_key));
					
				}
				
			}
			
		}
			
			/**
		 * Darf das Produkt gekauft werden?
		 */
		public function canOrder($product_key) {

			$product_id = $this->shop->getProduktID($product_key);

			if ($this->isVariantsProduct($product_id)) {

				$nActive = 0;

				$variants = $this->getVariants($product_id);

				foreach ($variants as $var_id => $var_info)
				{

					$nActive += $var_info['count_active'];

				}

				if ($nActive <= 0) return -2;

			}

		}
		
		/**
		 * Ermöglicht es die Ersetzungsfunktion aus einem Modul zu erweitern
		 */
		public function replaceUniversalPlatzhalter(&$arReplace, $order_id = false, $kunden_id = false, $rechnung_id = false, $product_id = false, $product_index = false) {
			
			$product_key = $this->db->fetchOne("SELECT `productkey` FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($order_id)."' AND `p_id` = '".wpsg_q($product_id)."' AND `product_index` = '".wpsg_q($product_index)."' ");
			 			
			if ($this->isVariantsProductKey($product_key)) {
				
				$arVariantenInfo = $this->getVariantenInfoArray($product_key);
				
				foreach ($arVariantenInfo as $k => $v) {
					
					if (is_numeric($k)) {
						
						$arReplace['/%productvariants_'.$k.'_value%/i'] = $v['value'];
						$arReplace['/%productvariants_'.$k.'_artnr%/i'] = $v['artnr'];
						$arReplace['/%productvariants_'.$k.'_price%/i'] = $v['preis'];
						
					}
					
				}
				
				$arReplace['/%productvariants_key%/i'] = $arVariantenInfo['key'];
				$arReplace['/%productvariants_akey%/i'] = $arVariantenInfo['akey'];
								
			} 
			
		}
		
		/* Modulfunktionen */

		/**
		 * Gibt den Bestand für eine Variation zurück
		 * @var $product_key String
		 * Beispiel: pv_1|1:1
		 *
		 * Wird vom Lagerbestandsmodul aufgerufen, sollte NUR mit einem Variantenschlüssel aufgerufen werden
		 */
		public function getStockForVariation($product_key)
		{

			$arVariInfo = $this->explodeProductKey($product_key);
			$oProduct = wpsg_product::getInstance($arVariInfo['product_id']);

			if (!wpsg_isSizedArray($arVariInfo['arVari'])) return $oProduct->stock;
			else
			{

				$nStock = 0;
				$variConf = $this->getVariants($arVariInfo['product_id'], true, true, true);

				foreach ($arVariInfo['arVari'] as $var_id => $vari_id)
				{

					$nStock += $variConf[$var_id]['arVariation'][$vari_id]['stock'];

				}

				return $nStock;

			}

		} // public function getStockForVariation($product_key)

        /**
         * Speichert die Varianten
         */
        public function saveVariantsData($vp_data)
        {
 
			foreach ($vp_data as $var_id => $var_data)
			{
				
				foreach ($var_data['arVariation'] as $vari_id => $vari_data)
				{
					
					if (!wpsg_isSizedInt($vari_data['id']))
					{
					
						// Variation zu Produkt suchen
						$exist_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `variation_id` = '".wpsg_q($_REQUEST['vari_id'])."' AND `product_id` = '".wpsg_q($_REQUEST['product_id'])."' ");
						
						$vari_data['id'] = $exist_id;
						
					}
						
					
					if (!wpsg_isSizedInt($vari_data['id']))
					{
						
						// Ist im Request alles drin was ich brauche, dann anlegen
						if (wpsg_isSizedInt($_REQUEST['var_id']) && wpsg_isSizedInt($_REQUEST['vari_id']) && wpsg_isSizedInt($_REQUEST['product_id']))
						{
							
							$this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, array(
								'variation_id' => wpsg_q($_REQUEST['vari_id']),
								'product_id' => wpsg_q($_REQUEST['product_id']),
								'images' => wpsg_q($vari_data['images']),
								'images_set' => wpsg_q($vari_data['images_set'])
							));
							
						}
						
					} 
					else 
					{
						
						$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARIATION, array(
							'images' => wpsg_q($vari_data['images']),
							'images_set' => wpsg_q($vari_data['images_set'])
						), " `id` = '".wpsg_q($vari_data['id'])."' ");
						
					}
					
				}
				
			} 
						
        } // public function saveVariantsData($produkt_id, $vp_data)
 
        /**
         * Lädt die Varianteninformationen aus dem serialisierten Array
         * @param $noTrans Wird diese Variable auf true gesetzt, so wird die Übersetzung nicht geladen (Wie Backend) Wichtig wenn die Varianten wieder gespeichert werden sollen!
         */
        public function loadVarianten($produkt_id, $noTrans = false, $noCache = false)
        {

        	if (is_admin()) $noCache = true;

        	if (!$noCache && array_key_exists($produkt_id.$noTrans, wpsg_getArray($this->cache_variData))) return $this->cache_variData[$produkt_id.$noTrans];

        	// Übersetzung verarbeiten
        	if (is_admin() || $noTrans === true)
        	{

        		//$vp_data = @unserialize($this->db->fetchOne("SELECT `images` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `id` = '".wpsg_q($produkt_id)."'"));
        		$vp_data = @unserialize($this->db->fetchOne("SELECT `images` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `id` = '".wpsg_q($_REQUEST['vari_id'])."'"));
        		if (!is_array($vp_data)) $vp_data = array();

        	}
        	else
        	{

        		// Im Frontend geht es nach der Sprache auf der der Shop aktuell läuft
        		if ($this->shop->isOtherLang())
        		{

        			$parent_lang_id = $this->db->fetchOne("SELECT `lang_parent` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($produkt_id)."'");
        			if (!wpsg_isSizedInt($parent_lang_id)) $parent_lang_id = $produkt_id;

        			$vp_data = @unserialize($this->db->fetchOne("SELECT `images` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `id` = '".wpsg_q($parent_lang_id)."'"));
        			if (!is_array($vp_data)) $vp_data = array();

        			foreach ($vp_data as $k => $v)
        			{

        				// Den Namen der Variante auf die aktuelle Sprache stellen
        				if (wpsg_isSizedString($vp_data[$k]['lang'][$this->shop->getCurrentLanguageCode()]['name'])) $vp_data[$k]['name'] = $vp_data[$k]['lang'][$this->shop->getCurrentLanguageCode()]['name'];

        				if (wpsg_isSizedArray($vp_data[$k]['vari']))
        				{

        					foreach ($vp_data[$k]['vari'] as $k2 => $vari)
        					{

        						// Den Namen der Variation auf die aktuelle Sprache korrigieren
        						if (wpsg_isSizedString($vp_data[$k]['lang'][$this->shop->getCurrentLanguageCode()]['vari'][$k2]['name'])) $vp_data[$k]['vari'][$k2]['name'] = $vp_data[$k]['lang'][$this->shop->getCurrentLanguageCode()]['vari'][$k2]['name'];

        					}

        				}

        			}

        		}
        		else
        		{

        			if (isset($_REQUEST['vari_id']))
					{

        				$vp_data = @unserialize($this->db->fetchOne("SELECT `images` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `id` = '".wpsg_q($_REQUEST['vari_id'])."'"));
        				if (!is_array($vp_data)) $vp_data = array();

        			}
        			else
        			{

        				$strQuery =
        				"SELECT PVV.`id`, PVV.`variation_id`, PVV.`product_id`, PVV.`anr`, PVV.`price`, PVV.`stock`, PVV.`min_stock`, PVV.`images`, PVV.`weight`, PVV.`fmenge`, PVV.`active`,
        				 VV.`variant_id`, VV.`name` AS VVname, VV.`shortname`, PV.`pos` AS PVpos, VV.`pos` AS VVpos,
        				 V.`name` AS Vname, V.`type`
        				 FROM ".WPSG_TBL_VARIANTS." AS V, ".WPSG_TBL_VARIANTS_VARI." AS VV,
        				 ".WPSG_TBL_PRODUCTS_VARIATION." AS PVV, ".WPSG_TBL_PRODUCTS_VARIANT." AS PV
        				 WHERE V.`id` = VV.`variant_id` AND VV.`id` = PVV.`variation_id` AND PV.`variant_id` = VV.`variant_id` AND
        				 PVV.`product_id` = '".wpsg_q($produkt_id)."' AND VV.`deleted` != 1 AND V.`deleted` != 1
        				 ORDER BY PV.`pos`, VV.`pos`";

        				//$vp_data = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `product_id` = '".wpsg_q($produkt_id)."'");
        				$vp_data = $this->db->fetchAssoc($strQuery);
        				if (!is_array($vp_data)) $vp_data = array();
        				$this->unserializeVariation($vp_data);

        			}

        		}

        	}

        	$this->cache_variData[$produkt_id.$noTrans] = $vp_data;

        	return $vp_data;

        } // public function loadVarianten($produkt_id)

        private function clearArrayForSerialization($ar)
        {

        	foreach ((array)$ar as $k => $v)
        	{

        		if (wpsg_isSizedArray($v))
        		{

        			$ar[$k] = $this->clearArrayForSerialization($v);

        		}
        		else
        		{

        			$ar[$k] = preg_replace('/\'|\`|\´|\"/', '', $v);

        		}

        	}

        	return $ar;

        } // private function clearArrayForSerialization($ar)

        /**
         * Gibt den Namen der Variante zurück
         */
        public function getVariantenName($vp_data, $var_id)
        {

        	$name = $vp_data[$var_id]['name'];

        	if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        	{
        		if ($vp_data[$var_id]['lang'][$_REQUEST['wpsg_lang']]['name'] != "")
        		{
        			$name = $vp_data[$var_id]['lang'][$_REQUEST['wpsg_lang']]['name'];
        		}
        	}

        	return ((trim($name) == "")?"----":$name);

        } // public function getVariantenName($vp_data, $v_id)

        /**
         * Gibt den Namen der Variation zurück
         */
        public function getVariName($vp_data, $var_id, $vari_id)
        {

        	$name = $vp_data[$var_id]['arVariation'][$vari_id]['name'];

        	if (wpsg_isSizedString($_REQUEST['wpsg_lang']))
        	{
        		if ($vp_data[$var_id]['lang'][$_REQUEST['wpsg_lang']]['arVariation'][$vari_id]['name'] != "")
        		{
        			$name = $vp_data[$var_id]['lang'][$_REQUEST['wpsg_lang']]['arVariation'][$vari_id]['name'];
        		}
        	}

        	return ((trim($name) == "")?"----":$name);

        } // public function getVariName($vp_data, $var_id, $vari_id)

		/**
		 * Prüft ob das Produkt ein Variantenprodukt ist oder nicht
		 */
		public function isVariantsProduct($product_id)
		{

			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					`".WPSG_TBL_VARIANTS_VARI."` AS VI
						LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI ON (PVI.`variation_id` = VI.`id`)
						LEFT JOIN `".WPSG_TBL_VARIANTS."` AS V ON (V.`id` = VI.`variant_id`)
				WHERE
					PVI.`product_id` = '".wpsg_q($product_id)."' AND
					PVI.`active` = '1' AND
					V.`deleted` != '1' AND
					VI.`deleted` != '1'
			";

			$nActiveVariants = intval($this->db->fetchOne($strQuery));

			if ($nActiveVariants > 0) return true; else return false;

		} // public function isVariantsProduct($product_id)

		/**
		 * Integration in das Produkttemplate
		 */
		public function productTemplate($product_data)
		{

			die("Veraltet");

			if (!wpsg_isSizedArray($product_data['arVariant'])) return;

			$this->shop->view['wpsg_mod_productvariants']['product_id'] = $product_data['product_id'];
			$this->shop->view['wpsg_mod_productvariants']['arVariants'] = $product_data['arVariant'];
			$this->shop->view['wpsg_mod_productvariants']['product_variants'] = $this->explodeProductKey($product_data['product_key'])['arVari'];

			$this->shop->render(WPSG_PATH_VIEW.'mods/mod_productvariants/productTemplate.phtml');

		} // public function productTemplate($product_key)

		/**
		 * Übernimmt die Auswahl der Variante im Produkttemplate
		 */
		public function renderTemplate($product_key)
		{

			if (!$this->isVariantsProductKey($product_key)) return false;

			$product_id = $this->shop->getProduktID($product_key);
			
			$vp_data = $this->getVariants($product_id, true, true, true);
									
			$this->shop->view['vp_data'] = $vp_data;
			$this->shop->view['vp_info'] = $this->getVariantenInfoArray($product_key);
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/produkt.phtml', false);

		} // public function renderTemplate($produkt_id)

		/**
		 * Gibt ein Array mit Informationen aus dem Variantenschlüssel zurück
		 * Besp: pv_1|1:1
		 * Array
			(
    			[product_id] => 1
    			[arVari] => Array
		 		(
            		[1] => 1
        		)

			)
		 * Anhand des Produktschlüssels z.B. pv_1|4:6|1:8|6:11|5:10
		 */
		public function explodeProductKey($product_key)
		{

			if (is_numeric($product_key)) return array(
				'product_id' => $product_key,
				'arVari' => array()
			);

			$arKey = explode('|', $product_key);
			$arReturn = array();

			for ($i = 1; $i < sizeof($arKey); $i ++)
			{

				$arKeyValue = explode(':', $arKey[$i]);
				$arReturn[$arKeyValue[0]] = $arKeyValue[1];

			}

			return array(
				'product_id' => substr($arKey[0], 3),
				'arVari' => $arReturn
			);

		} // public function explodeProductKey($product_key)

		/**
		 * Gibt true zurück, wenn der Preis im Produkttemplate angezeigt werden soll
		 * Andersrum programmiert, da die Default Einstellung anzeigen ist????
		 */
		public function showVariPrice($vari_price)
		{

			switch ($this->shop->get_option('wpsg_mod_productvariants_price'))
			{

				case '1': // nur wenn größer 0

					if ($vari_price <= 0) return false;

					break;

				case '0': // nie anzeigen

					return false;

					break;

			}

			return true;

		} // public function showVariPrice($vari_price)

		private function calculateVariantsPrice($price, $arVariant, $product_key)
		{

			$arProductVariant = $this->explodeProductKey($product_key)['arVari'];

			foreach ($arProductVariant as $variant_id => $variation_id)
			{

				$price += $arVariant[$variant_id]['arVariation'][$variation_id]['price'];

			}

			return $price;
		}

		private function admin_showAction()
		{

			$this->shop->view['product_id'] = $_REQUEST['product_id'];
			$this->shop->view['arVariants'] = $this->getVariants($_REQUEST['product_id'], true);

			die($this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/admin_show.phtml'));

		} // private function showAction()

		private function admin_addAction()
		{

			$this->db->ImportQuery(WPSG_TBL_VARIANTS, array(
				'name' => wpsg_q(''),
				'product_id' => wpsg_q($_REQUEST['product_id']),
				'deleted' => '0',
				'pos' => '5000'
			));

			$this->admin_showAction();

		} // private function admin_addAction()

		private function admin_editAction()
		{

			$_REQUEST['product_id'] = wpsg_sinput("key", $_REQUEST['product_id']);
			$_REQUEST['variant_id'] = wpsg_sinput("key", $_REQUEST['variant_id']);

			$this->shop->view['product_id'] = wpsg_getStr($_REQUEST['product_id'], '0');
			$this->shop->view['variant'] = $this->getVariant($_REQUEST['variant_id']);
			$this->shop->view['arVariation'] = $this->getVariationOfVariant($_REQUEST['variant_id'], $_REQUEST['product_id']);

			//die(wpsg_debug($this->shop->view['arVariation']));

			die($this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/admin_edit.phtml'));

		} // private function admin_editAction()

		private function admin_variation_addAction()
		{

			$variation_id = $this->db->ImportQuery(WPSG_TBL_VARIANTS_VARI, array(
				'variant_id' => wpsg_q($_REQUEST['variant_id']),
				'name' => '',
				'deleted' => '0'
			));

			if (wpsg_isSizedInt($_REQUEST['product_id']))
			{

				$arAttachmentIDs = $this->shop->imagehandler->getAttachmentIDs($_REQUEST['product_id']);
				$arAttachmentIDsAll = $this->shop->imagehandler->getAttachmentIDs($_REQUEST['product_id']);
				
				$this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, array(
					'variation_id' => wpsg_q($variation_id),
					'images' => implode(',', $arAttachmentIDsAll),
					'product_id' => wpsg_q($_REQUEST['product_id'])
				));

			}

			$this->admin_editAction();

		} // private function admin_variation_addAction()

		private function admin_delAction()
		{

			$this->db->UpdateQuery(WPSG_TBL_VARIANTS, array(
				'deleted' => '1'
			), " `id` = '".wpsg_q($_REQUEST['variant_id'])."' ");

			$this->admin_showAction();

		} // private function admin_delAction()

		private function admin_variation_delAction()
		{

			$this->db->UpdateQuery(WPSG_TBL_VARIANTS_VARI, array(
				'deleted' => '1'
			), " `id` = '".wpsg_q($_REQUEST['variation_id'])."' ");

			$this->admin_editAction();

		} // private function admin_variation_delAction()

		private function setProductVariant($product_id, $variant_id, $field, $value)
		{

			if (!wpsg_isSizedInt($product_id) || !wpsg_isSizedInt($variant_id)) die("Systemfehler");

			$id_exist = $this->db->fetchOne("
				SELECT
					`id`
				FROM
					`".WPSG_TBL_PRODUCTS_VARIANT."`
				WHERE
					`variant_id` = '".wpsg_q($variant_id)."' AND
					`product_id` = '".wpsg_q($product_id)."'
			");

			$data = array(
				$field => wpsg_q($value),
				'product_id' => wpsg_q($product_id),
				'variant_id' => wpsg_q($variant_id)
			);

			if (wpsg_isSizedInt($id_exist)) $this->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARIANT, $data, " `id` = '".wpsg_q($id_exist)."' ");
			else $this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIANT, $data);

		} // private function setProductVariant($product_id, $variant_id, $field, $value)

		private function setProductVariation($product_id, $variation_id, $field, $value)
		{

			if (!wpsg_isSizedInt($product_id) || !wpsg_isSizedInt($variation_id)) die("Systemfehler");

			$id_exist = $this->db->fetchOne("
				SELECT
					`id`
				FROM
					`".WPSG_TBL_PRODUCTS_VARIATION."`
				WHERE
					`variation_id` = '".wpsg_q($variation_id)."' AND
					`product_id` = '".wpsg_q($product_id)."'
			");

			$data = array(
				$field => wpsg_q($value),
				'product_id' => wpsg_q($product_id),
				'variation_id' => wpsg_q($variation_id)
			);

			if (wpsg_isSizedInt($id_exist)) $this->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARIATION, $data, " `id` = '".wpsg_q($id_exist)."' ");
			else $this->db->ImportQuery(WPSG_TBL_PRODUCTS_VARIATION, $data);

			if (($field == 'stock') || ($field == 'active'))
			{
				$this->setStockFromVariation($product_id);
			}

		} // private function setProductVariation($product_id, $field, $value)

		private function setStockFromVariation($product_id)
		{

			if ($this->isVariantsProductKey($product_id)) {
				$arr = $this->explodeProductKey($product_id);
				$product_id = $arr['product_id'];
			}

			$sql = "SELECT SUM(PVI.`stock`) AS SU FROM `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI
						LEFT JOIN `".WPSG_TBL_VARIANTS_VARI."` AS VVI ON PVI.`variation_id`= VVI.`id`
						WHERE PVI.`product_id`='".wpsg_q($product_id)."' AND VVI.`deleted`!='1' AND PVI.`active`='1'
				";
			$stock = $this->db->fetchOne($sql);

			$data = array('stock' => wpsg_q($stock));

			$this->db->UpdateQuery(WPSG_TBL_PRODUCTS, $data, " `id` = '".wpsg_q($product_id)."' ");

			//getProductKeyFromRequest(&$product_key, $product_id, $form_data)
			//$stock = $this->shop->callMod('wpsg_mod_productvariants', 'getStockForVariation', array());

		}	// private function setStockFromVariation($product_id)

		private function admin_inlineEditAction()
		{

			$_REQUEST['field'] = wpsg_sinput("text_field", $_REQUEST['field']);

			if (wpsg_isSizedString($_REQUEST['field'], 'name'))
			{

				$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

				$this->db->UpdateQuery(WPSG_TBL_VARIANTS, array('name' => wpsg_q($_REQUEST['value'])), " `id` = '".wpsg_q($_REQUEST['field_id'])."' ");

			}
			else if (wpsg_isSizedString($_REQUEST['field'], 'type'))
			{

				$this->db->UpdateQuery(WPSG_TBL_VARIANTS, array('type' => wpsg_q(wpsg_sinput("key", $_REQUEST['value']))), " `id` = '".wpsg_q(wpsg_sinput("key", $_REQUEST['field_id']))."' ");
				die(self::$arTypeLabel[$_REQUEST['value']]);

			}
			else if (wpsg_isSizedString($_REQUEST['field'], 'pos'))
			{

				$i = 0; foreach ($_REQUEST['value'] as $var)
				{

					$var_id = substr($var, 4);

					if (wpsg_isSizedInt($_REQUEST['product_id'])) $this->setProductVariant($_REQUEST['product_id'], $var_id, 'pos', $i);
					else $this->db->UpdateQuery(WPSG_TBL_VARIANTS, array('pos' => wpsg_q($i)), " `id` = '".wpsg_q($var_id)."' ");

					$i ++;

				}

				die('1');

			}
			else if (wpsg_isSizedString($_REQUEST['field'], 'vari_name'))
			{

				$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

				$this->db->UpdateQuery(WPSG_TBL_VARIANTS_VARI, array('name' => wpsg_q($_REQUEST['value'])), " `id` = '".wpsg_q($_REQUEST['field_id'])."' ");

			}
			else if (wpsg_isSizedString($_REQUEST['field'], 'vari_shortname'))
			{

				$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

				$this->db->UpdateQuery(WPSG_TBL_VARIANTS_VARI, array('shortname' => wpsg_q($_REQUEST['value'])), " `id` = '".wpsg_q($_REQUEST['field_id'])."' ");

			}
			else if (wpsg_isSizedString($_REQUEST['field'], 'vari_pos'))
			{

				$i = 0; foreach ($_REQUEST['value'] as $vari)
				{

					$vari_id = substr($vari, 5);

					$this->db->UpdateQuery(WPSG_TBL_VARIANTS_VARI, array('pos' => wpsg_q($i)), " `id` = '".wpsg_q($vari_id)."' ");

					$i ++;

				}

				die('1');

			}
			else if (preg_match('/vari_(.*)/', $_REQUEST['field']))
			{

				$value = $_REQUEST['value'];
				$f = $_REQUEST['field'];

				if (($f == 'vari_fmenge') || ($f == 'vari_stock') || ($f == 'vari_min_stock') || ($f == 'vari_weight'))
					$value = $_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $value, "isFloat"));

				else
				{

					if($f == 'vari_anr') $value = $_REQUEST['value'] = wpsg_xss($value);
					else $value = $_REQUEST['value'] = wpsg_sinput("text_field", $value);

					if($f == 'vari_price') $value = str_replace(",", ".", $value);

				}

				$this->setProductVariation($_REQUEST['product_id'], $_REQUEST['field_id'], substr($_REQUEST['field'], 5), $value);

			}

			// TODO: Übersetzung

			die($_REQUEST['value']);

		} // private function admin_inlineEditAction()

		/**
		 * Gibt einen Array zurück, bei denen die Schlüssel die Varianten sind und die Werte die gewählten Variationen
		 */
		private function getSetVariArray($product_key)
		{

			if (!$this->isVariantsProductKey($product_key)) return array();

			$arVariSet = explode('|', preg_replace('/^pv_\d*\//', '', $product_key));
			$arReturn = array();
			unset($arVariSet[0]);

			foreach ($arVariSet as $var_combi)
			{

				$var_combi = explode(':', $var_combi);
				$var = $var_combi[0];
				$vari = $var_combi[1];

				$arReturn[$var] = $vari;

			}

			return $arReturn;

		}	// private function getSetVariArray($product_key)

		/**
		 * Gibt die Variationen einer Variante zurück
		 * @param Integer $variant_id ID der Variante
		 */
		public function getVariationOfVariant($variant_id, $product_id = false, $arProductFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryJOIN = "";
			$strQueryWHERE = "";
			$strQueryHAVING = "";

			if (wpsg_isSizedArray($arProductFilter))
			{

				//return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);
				//list($strQueryP_WHERE, $strQueryP_JOIN, $strQueryP_HAVING, $strQueryP_ORDER) = wpsg_product::getQueryParts($arProductFilter);

				list($strQueryP_SELECT, $strQueryP_WHERE, $strQueryP_JOIN, $strQueryP_HAVING, $strQueryP_ORDER) = wpsg_product::getQueryParts($arProductFilter);

				$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PV ON (PV.`variation_id` = VI.`id`) ";
				$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = PV.`product_id`) ";

				$strQuerySELECT .= $strQueryP_SELECT;
				$strQueryJOIN .= $strQueryP_JOIN;
				$strQueryWHERE .= $strQueryP_WHERE;
				$strQueryHAVING .= $strQueryP_HAVING;

			}
			else if (wpsg_isSizedInt($product_id))
			{

				$strQueryJOIN .= " LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PV ON (PV.`variation_id` = VI.`id` AND PV.`product_id` = '".wpsg_q($product_id)."') ";
				$strQuerySELECT .= " , PV.`id` AS iid, PV.`variation_id`, PV.`active`, PV.`anr`, PV.`price`, PV.`stock`, PV.`min_stock`, PV.`images`, PV.`images_set`, PV.`weight`, PV.`fmenge` ";

			}

			$strQuery = "
				SELECT
					VI.`id`, VI.`name`, VI.`shortname`, VI.`deleted`, VI.`pos`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_VARIANTS_VARI."` AS VI
						".$strQueryJOIN."
				WHERE
					VI.`variant_id` = '".wpsg_q($variant_id)."' AND
					VI.`deleted` != '1'
					".$strQueryWHERE."
				GROUP BY
					VI.`id`
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					VI.`pos`
			";

			$arVari = $this->db->fetchAssoc($strQuery, "id");

			// Daschmi: 
			//$this->checkMinStock($arVari, $product_id);

			return $arVari;

		} // public function getVariationOfVariant($variant_id)
		
		/*
		 * Prüft, ob der Minimallagerbestand erreicht wurde und schickt - sofern dieser Fall eintritt - eine E-Mail
		 */
		public function checkMinStock($arVari, $product_id)
		{
			
			$product_data = $this->shop->cache->loadProduct($this->shop->getProduktID($product_id));
			
			foreach ($arVari['arVari'] as $var_id => $vari_id) {
				
				$vari_info = $this->db->fetchRow("SELECT PV.`stock`, PV.`min_stock`, VARI.`id`, VARI.`name` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` AS PV LEFT JOIN `".wpsg_q(WPSG_TBL_VARIANTS_VARI)."` AS VARI ON (VARI.`id` = PV.`variation_id`) WHERE PV.`product_id` = '".wpsg_q($this->shop->getProduktID($product_id))."' AND PV.`variation_id` = '".wpsg_q($vari_id)."' ");
				
				if (isset($vari_info['min_stock']) && (int)$vari_info['stock'] <= (int)$vari_info['min_stock']) {
					
					// Spezifischen Index setzen um Email als "Varianten-Information" zu identifizieren
					$adminName = $this->shop->get_option('wpsg_shopdata_owner')?:__("Administrator", "wpsg");
					$GLOBALS['stockemail_prodvariant'] = array("produkt" => $product_data, "variant" => $vari_info, "admin_name" => $adminName);
					
					if ($this->shop->get_option('wpsg_htmlmail') == 1) {
						
						$mail_html = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/stockmail_html.phtml', false);
						
					} else {
						
						$mail_html = false;
						$mail_text = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_stock/stockmail.phtml', false);
						
					}
					
					$to = $product_data['minstockproduct_mail'];
					if(!wpsg_isSizedString($to)) $to = get_bloginfo('admin_email');
					
					list($subject, $text) = @$this->shop->sendMail($mail_text, $to, 'wpsgmodstockminstockmail', array(), false, false, $mail_html, wpsg_translate(__('Der Minimalbestand der Variation #1# des Produkts #2# wurde erreicht.', 'wpsg'), $arVari[$variation_id]['name'], $product_data['name']));
					
					// Wenn Email gesendet, spezifischen Index löschen
					if(isset($subject) && wpsg_isSized($subject) && isset($text) && wpsg_isSized($text))
						unset($GLOBALS['stockemail_prodvariant']);
					
				}
				
			}
			
		} // public static function checkMinStock($arVari)

		private function unserializeVariation(&$arVari) {
			foreach ($arVari as &$vi) {
				$ar = @unserialize($vi['images']);
				$vi['pic'] = '';
				$vi['sel'] = 0;
				$vi['picOrder'] = Array();
				if (wpsg_isSizedArray($ar)) {
					foreach($ar as $k => $v) {
						$v = preg_replace('/\-(\d+)x(\d+)\./', '.', $v);
						$vi[$k] = $v;
					}
				}
			}
		}	// private function unserializeVariation(&$arVari)
 
		public function stockVarianten($product_id)
		{

			$this->shop->view['product_id'] = wpsg_getStr($product_id, '0');
			//$this->shop->view['variant'] = $this->getVariant($_REQUEST['variant_id']);
			//$this->shop->view['arVariation'] = $this->getVariationOfVariant($_REQUEST['variant_id'], $_REQUEST['product_id']);
			//$this->shop->view['arVariant']
			$vp_data = $this->getVariants($product_id, true, true, true);
			
			$html = '<table>';
			foreach ($vp_data as $var) {
				$html .= '<tr>';
				$html .= '<td class="col_shortname" colspan="3">'.wpsg_hspc($var['name']).'</td>';
				$html .= '</tr>';
			    foreach ($var['arVariation'] as $vari) {
			    	$html .= '<tr>';
			    	$html .= '<td class="col_active">&nbsp;';
			    	$html .= '</td>';
			    	$txt = wpsg_hspc($vari['name']).' : '.wpsg_hspc($vari['stock']);
			    	$html .= '<td class="col_shortname">'.$txt.'</td>';
			    	$html .= '<td class="col_1">';
			    	$html .= '</td>';
			    	$html .= '</tr>';
				}
			}
			$html .= '</table>';
			die($html);

		} // private function stockVarianten($product_id)

		/**
		 * Reduziert den Bestand der Variationen in dem Array
		 */
		public function reduceStock($produkt_key, $menge, $reduce = true)
		{

			//$produkt_id = preg_replace('/(^pv_)|(\|(.*)$)/', '', $produkt_key);
			//$vari_teil = preg_replace('/(.*)\//', '', $produkt_key);
			//$arVarianten = explode('|', $vari_teil);
			$arVar = $this->explodeProductKey($produkt_key);

			//$vari_data = $this->loadVarianten($produkt_id, true, true);

			// Tabellen sperren
			if ($this->shop->get_option('wpsg_lockOrderTables') != '1')
			{

				$arLockTables[WPSG_TBL_PRODUCTS_VARIATION] = "WRITE";
				$strQuery = "LOCK TABLES ";
				foreach ($arLockTables as $table_name => $locktype) $strQuery .= " `".$table_name."` ".$locktype.",";
				$this->db->Query(substr($strQuery, 0, -1));
			}

			foreach ($arVar['arVari'] as $v => $vv)
			{

				$pid = $arVar['product_id'];
				if ($reduce === true)
				{

					//$vari_data[$variante_id]['vari'][$vari_id]['stock'] -= $menge;
					$this->db->Query("
							UPDATE ".WPSG_TBL_PRODUCTS_VARIATION." SET `stock` = `stock` - ".wpsg_q($menge)." WHERE `variation_id` = '".wpsg_q($vv)."'
							AND `product_id` = '".wpsg_q($pid)."'");

				}
				else
				{

					//$vari_data[$variante_id]['vari'][$vari_id]['stock'] += $menge;
					$this->db->Query("
							UPDATE ".WPSG_TBL_PRODUCTS_VARIATION." SET `stock` = `stock` + ".wpsg_q($menge)." WHERE `variation_id` = '".wpsg_q($vv)."'
							AND `product_id` = '".wpsg_q($pid)."'");

				}

			}

			$this->db->unlockTables();

			$this->setStockFromVariation($produkt_key);

			$this->checkMinStock($arVar, $produkt_key);

			// Array zurückspeichern
			//$this->saveVarianten($produkt_id, $vari_data);

		} // public function reduceStock($produkt_id, $menge)
		
		/**
		 *	zusammengesetzte Variantenartikelnummer ersetzt die Artikelnummer 
		 */
		public function getProductAnr($product_key, &$anr)
		{
		
			if ($this->isVariantsProductKey($product_key) && wpsg_isSizedInt($this->shop->get_option('wpsg_vp_replaceanr')))
			{
		
				$arVariantenInfo = $this->getVariantenInfoArray($product_key);
		
				if (wpsg_isSizedString($arVariantenInfo['akey'])) $anr = $arVariantenInfo['akey'];
		
				// Artikelnummer gebildet, keine weiteren Module betrachten
				return -2;
		
			}
				
		} // public function getProductAnr($product_key, &$anr)
		
		/**
		 * Gibt true zurück, wenn der übergebene Produktkey ein Varianten Produktkey ist. Sonst false.
		 * @param \String $productkey
		 */
		public function isVariantsProductKey($productkey)
		{

			if (preg_match('/^pv_\d+/', $productkey))
			{

				return true;

			}
			else
			{

				return false;

			}

		} // public function isVariantsProductKey($productkey)

		/**
		 * Gibt eine einzelne Variante zurück
		 * @param unknown $variant_id
		 */
		public function getVariant($variant_id, $bHideDeleted = true)
		{

			$strQueryWHERE = "";

			if ($bHideDeleted === true) $strQueryWHERE .= " AND V.`deleted` != '1' ";

			$strQuery = "
				SELECT
					V.*
				FROM
					`".WPSG_TBL_VARIANTS."` AS V
				WHERE
					V.`id` = '".wpsg_q($variant_id)."'
					".$strQueryWHERE."
			";

			$arVariant = $this->db->fetchRow($strQuery);

			if (!wpsg_isSizedInt($arVariant['id'])) return false;

			// TODO: Übersetzung

			return $arVariant;

		} // public function getVariant($variant_id)

		public function getVariation($variation_id)
		{

			$arVariation = $this->db->fetchRow("
				SELECT
					VI.*
				FROM
					`".WPSG_TBL_VARIANTS_VARI."` AS VI
				WHERE
					VI.`id` = '".wpsg_q($variation_id)."'
			");

			$arVariation['images'] = wpsg_trim(explode(',', $arVariation['images']));

			// TODO: Übersetzung

			return $arVariation;

		} // public function getVariation($variation_id)

		/**
		 * Gibt einen Array der Produktvarianten zurück
		 * @param Integer|Boolean $product_id Produkt ID
		 * @param Boolean $global Globale Varianten?
		 */
		public function getVariants($product_id = false, $global = true, $onlyActive = false, $loadVariationen = false)
		{

			$strQuerySELECT = "";
			$strQueryORDER = "";
			$strQueryJOIN = "";
			$strProductQuery = " AND ( 0 ";

			if (wpsg_isSizedInt($product_id))
			{

				$strProductQuery .= " OR V.`product_id` = '".wpsg_q($product_id)."' ";

				$strQueryWHERE = "";

				// Wenn Lagerbestand aktiv, dann nur Veriationen mit Lagerbestand zählen
				// Im Backend zähle ich auch ausverkaufte Variationen mit, sonst steht in der Übersicht 0/2 auch wenn bei einem der Haken gesetzt ist
				if ($this->shop->hasMod('wpsg_mod_stock') && !is_admin()) $strQueryWHERE .= " AND PVI.`stock` > 0 ";

				$strQuerySELECT .= ", (
					SELECT
						COUNT(*)
					FROM
						`".WPSG_TBL_VARIANTS_VARI."` AS VI
							LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI ON (PVI.`variation_id` = VI.`id`)
					WHERE
						PVI.`product_id` = '".wpsg_q($product_id)."' AND
						PVI.`active` = '1' AND
						VI.`variant_id` = V.`id` AND
						VI.`deleted` != '1'
						".$strQueryWHERE."
				) AS `count_active` ";

				$strQueryJOIN = " LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIANT."` AS PV ON (PV.`variant_id` = V.`id` AND PV.`product_id` = '".wpsg_q($product_id)."') ";

				$strQueryORDER .= " PV.`pos` ASC, ";

			}
			else
			{

				$strQuerySELECT .= ", '0' AS `count_active` ";

			}

			if ($global === true)
			{

				$strProductQuery .= " OR V.`product_id` = '0' ";

			}

			$strProductQuery .= " ) ";

			$arData = $this->db->fetchAssoc("
				SELECT
					V.*,
					(SELECT COUNT(*) FROM `".WPSG_TBL_VARIANTS_VARI."` AS VI WHERE VI.`variant_id` = V.`id` AND VI.`deleted` != '1') AS `count_variation`,
					(
						SELECT
							COUNT(DISTINCT `product_id`)
						FROM
							`".WPSG_TBL_VARIANTS_VARI."` AS VI
								LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI ON (PVI.`variation_id` = VI.`id`)
								LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (PVI.`product_id` = P.`id`)
						WHERE
							VI.`variant_id` = V.`id` AND
							PVI.`active` = '1' AND
							P.`id` > 0 AND
							P.`deleted` != '1'
					) AS `count_produkte`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_VARIANTS."` AS V
						".$strQueryJOIN."
				WHERE
					V.`deleted` != '1'
					".$strProductQuery."
				GROUP BY
					V.`id`
				ORDER BY
					".$strQueryORDER."
					V.`product_id` ASC, V.`pos`
			", "id");

			foreach ($arData as $k => $v)
			{

				if ($onlyActive === true && !wpsg_isSizedInt($v['count_active'])) unset($arData[$k]);
				else
				{

					$arData[$k]['type_label'] = self::$arTypeLabel[$arData[$k]['type']];

					if ($loadVariationen === true && wpsg_isSizedInt($product_id))
					{

						$strQueryWHERE = "";
						
						if ($onlyActive === true) $strQueryWHERE .= " AND PVI.`active` = '1' ";
						
						$arData[$k]['arVariation'] = $this->db->fetchAssoc("
							SELECT
								PVI.*,
								VI.`name`
							FROM
								`".WPSG_TBL_VARIANTS_VARI."` AS VI
									LEFT JOIN `".WPSG_TBL_PRODUCTS_VARIATION."` AS PVI ON (PVI.`variation_id` = VI.`id`)
							WHERE
								VI.`deleted` != '1' AND
								VI.`variant_id` = '".wpsg_q($v['id'])."' AND
								PVI.`product_id` = '".wpsg_q($product_id)."' 
								".$strQueryWHERE."
							ORDER BY
								VI.`pos` ASC
						", "variation_id");
 
					}
				}

			}

			// TODO: Übersetzung

			return $arData;

		}

		public function basket_preInsertDefekt()
		{

			if (is_array($_REQUEST['wpsg_vp']) && sizeof($_REQUEST['wpsg_vp']) > 0)
			{

				$var_key = 'pv_'.$_REQUEST['wpsg']['produkt_id'].'|';

				foreach ($_REQUEST['wpsg_vp'] as $var => $var_value)
				{

					$var_key .= $var.":".$var_value."|";

				}

				$var_key = substr($var_key, 0, -1);

				$_REQUEST['wpsg']['produkt_id'] = $var_key;

			}

		} // public function basket_preInsert()

		public function basket_row(&$p, $i)
		{

			if (!preg_match('/pv_(.*)/', $p['id'])) return;

			$this->shop->view['variante'] = $this->getVariantenInfoArray($p['id']);

			$this->shop->view['i'] = $i;

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/basket_row.phtml');

		} // public function basket_row(&$p)

		public function overview_row(&$p, $i)
		{

			if (!preg_match('/pv_(.*)/', $p['id'])) return;

			$this->shop->view['variante'] = $this->getVariantenInfoArray($p['id']);

			$this->shop->view['i'] = $i;

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productvariants/overview_row.phtml');

		} // public function basket_row(&$p)

		/**
		 * Ersetzt in einem ProduktKey den Wert einer Variante ($var_id) durch die Variation $vari_id
		 * 
		 * @param $product_key
		 * @param $var_id
		 * @param $vari_id
		 * @return string
		 */
		public function getSimulatedVariKey($product_key, $var_id, $vari_id)
		{
			
			$vari_info = $this->getVariantenInfoArray($product_key);
			
			$product_key_return = 'pv_'.$vari_info['product_id'];
			
			foreach ($vari_info as $k => $i)
			{
				
				if (is_numeric($k))
				{
					
					if ($var_id == $k) $product_key_return .= '|'.$k.':'.$vari_id;
					else $product_key_return .= '|'.$k.':'.$i['vari_id'];
					
				}
				
			}
			
			return $product_key_return;			
			
		} // public function getSimulatedVariKey($product_key, $vari_id, $vari_value)
		
		/**
		 * Liest die Informationen anhand eines Variantenschlüssels aus
		 */
		public function getVariantenInfoArray($product_key)
		{

			$produkt_id = $this->shop->getProduktID($product_key);
			$arVari = explode('|', preg_replace('/pv_'.$produkt_id.'\|/', '', $product_key));

			//$vp_data = $this->loadVarianten($produkt_id);
			$vp_data = $this->getVariants($produkt_id, true, true, true);
			 
			$arReturn = array(
				'product_id' => $produkt_id
			);
			
			$arKey = array();
			$arAKey = array();
			$arPics = array();

			// kein Produkt-Key dann keine Weiterarbeit nötig
			if (is_numeric($product_key)) {
				$arReturn['key'] = '';
				$arReturn['akey'] = '';
				$arReturn['images'] = array();
				return $arReturn;

			}

			if ($this->shop->hasMod('wpsg_mod_fuellmenge')) $arReturn['fmenge'] = 0;
			if ($this->shop->hasMod('wpsg_mod_weight')) $arReturn['weight'] = 0;

			/** Alle Bilder des Produkts */
			$arImagesProduct = $this->shop->imagehandler->getAttachmentIDs($produkt_id);
			$arImages = $arImagesProduct;
	
			foreach ($arVari as $var_key)
			{

				$var_id = preg_replace('/\:(.*)/', '', $var_key);
				$var_value = preg_replace('/(.*)\:/', '', $var_key);

				$arPicsVariante = array();

				/* Bilder dieser Variation */
				$arImagesVariation = explode(',', @$vp_data[$var_id]['arVariation'][$var_value]['images_set']);
				
				/* Schnittmenge */
				$arImages = array_intersect($arImages, $arImagesVariation);
				
				$r = array(
					'vari_id' => $var_value,
					'name' 	=> $vp_data[$var_id]['name'],
					'value' => $vp_data[$var_id]['arVariation'][$var_value]['name'],
					'preis'	=> $vp_data[$var_id]['arVariation'][$var_value]['price'],
					'artnr'	=> @$vp_data[$var_id]['arVariation'][$var_value]['anr'],
					'images' => $arImagesVariation
				);

				if ($this->shop->hasMod('wpsg_mod_weight') && wpsg_isSizedInt($vp_data[$var_id]['arVariation'][$var_value]['weight']))
				{

					$r['weight'] = $vp_data[$var_id]['arVariation'][$var_value]['weight'];
					$arReturn['weight'] += $r['weight'];

				}

				if ($this->shop->hasMod('wpsg_mod_fuellmenge') && wpsg_isSizedInt($vp_data[$var_id]['arVariation'][$var_value]['fmenge']))
				{

					$r['fmenge'] = $vp_data[$var_id]['arVariation'][$var_value]['fmenge'];
					$arReturn['fmenge'] += $r['fmenge'];

				}

				$arReturn[$var_id] = $r;

				$arKey[] = $vp_data[$var_id]['arVariation'][$var_value]['name'];
				$arAKey[] = @$vp_data[$var_id]['arVariation'][$var_value]['anr'];
 
			}

			$arReturn['key'] = implode(' / ', $arKey);
			$arReturn['akey'] = implode(' / ', $arAKey);
			$arReturn['images'] = wpsg_trim(array_unique($arImages));
			
			if (!wpsg_isSizedArray($arReturn['images'])) $arReturn['images'] = $arImagesProduct;

			return $arReturn;

		} // public function getVariantenInfoArray($vari_key)

		/**
		 * Wird nach dem speichern des Produktes aus der saveAction des 
		 * Produktcontrollers aufgerufen
		 * 
		*/
		public function produkt_save(&$produkt_id) {
			
			$varis = $GLOBALS['wpsg_sc']->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `product_id`='".wpsg_q($produkt_id)."' ");
			
			return;
			//TODO
			foreach ($varis as $v)
			{
				$im0 = unserialize($v['images']);
                $im0['pic'] = trim($im0['pic'], ',');
                $v['images'] = serialize($im0);
				var_dump('ALT:'.$v['images'].'<br/>');
                //die($v['images']);
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
                        //die($postid[count($postid) - 1].'/'.$post['ID']);
                        if (($postid[count($postid) - 1]) != $post['ID']) {
                            $postid[] = $post['ID'];
                            $arrf = pathinfo($post['guid']);
                            $file = $arrf['basename'];
                            var_dump($file.'<br/>');
                            $file = preg_replace('/\-(\d+)x(\d+)\./', '.', $file);
                            $pic[] = $file;
                        }
					}
				}
				$im1['pic'] = implode(',', $pic);
				$im1['postid'] = implode(',', $postid);
				$images = serialize($im1);
                var_dump('NEU:'.$images.'<br/>');
                //die($images);
				// Update WPSG_TBL_PRODUCTS_VARIATION
				$data = array('images' => $images);
				$GLOBALS['wpsg_sc']->db->UpdateQuery(WPSG_TBL_PRODUCTS_VARIATION, $data, "`id` = '".wpsg_q($v['id'])."'");
				
			}
            //die();
		}	// public function produkt_save(&$produkt_id)

        public function wpsg_mod_export_loadFields(&$arFields) { 
            
            $arFields[10]['fields']['wpsg_mod_productvariants_varname'] = __('Varianten Name', 'wpsg');
            $arFields[10]['fields']['wpsg_mod_productvariants_varanr'] = __('Varianten Artikelnummer', 'wpsg');
	
			$arVariant = $this->db->fetchAssoc("
                SELECT
                    V.`id`, V.`name`, V.`product_id`
                FROM
                    ".WPSG_TBL_VARIANTS." AS V
                    	LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (P.`id` = V.`product_id`)
                WHERE
                    V.`deleted` != '1' AND
                    (V.`product_id` <= 0 OR P.`deleted` = '0')
            ");
            
            foreach ($arVariant as $v) {
                
                if (wpsg_isSizedInt($v['product_id'])) $label = __('Produktvariante', 'wpsg');
                else $label = __('Globale Variante', 'wpsg');

                if (trim($v['name']) === '') $label = wpsg_translate(__('Unbenannte #1# (ID:#2#)', 'wpsg'), $label, $v['id']); 
                else $label = wpsg_translate(__('#1# #2# (ID:#3#)', 'wpsg'), $label, $v['name'], $v['id']);

                $arFields[10]['fields']['var_'.$v['id']] = $label;
                
            }
             
        }

        public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $product_key, $product_index, $profil_separator) {

		    if (preg_match('/^var_\d+$/', $field_value)) {
		        
		        $var_id = intval(substr($field_value, 4));
		        $arProductKey = $this->explodeProductKey($product_key);
		        $arVariation = $this->getVariationOfVariant($var_id);
		        
		        $return = $arVariation[$arProductKey['arVari'][$var_id]]['name'];
		        		        
            } else if (in_array($field_value, ['wpsg_mod_productvariants_varname', 'wpsg_mod_productvariants_varanr'])) {
            
                $return = '';
    
                $arProductExportProductKeys = [];
                $arReturn = [];
    
                if (wpsg_isSizedString($product_key)) $arProductExportProductKeys = [$product_key];
                else {
    
                    $oOrder = wpsg_order::getInstance($o_id);
    
                    /** @var \wpsg_order_product $oOrderProducts */
                    foreach ($oOrder->getOrderProducts() as $oOrderProduct) {
    
                        if (!in_array($oOrderProduct->getProductKey(), $arProductExportProductKeys)) $arProductExportProductKeys[] = $oOrderProduct->getProductKey();
    
                    }
    
                }
                
                foreach ($arProductExportProductKeys as $product_key) {
                    
                    if ($this->isVariantsProductKey($product_key)) {
        
                        $arVariInfo = $this->getVariantenInfoArray($product_key);
                        
                        if ($field_value === 'wpsg_mod_productvariants_varname') {
                                                
                            $return = $arVariInfo['key']; 
                            
                        } else if ($field_value === 'wpsg_mod_productvariants_varanr') {
        
                            $return = $arVariInfo['akey'];
        
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
	} // class wpsg_mod_productvariants extends wpsg_mod_basic

?>