<?php

	/**
	 * Erlaubt die Definition und Anzeige von Lieferzeiten
	 * @author Daschmi
	 */
	class wpsg_mod_deliverytime extends wpsg_mod_basic
	{

		var $lizenz = 1;
		var $id = 1610;
		var $hilfeURL = 'http://wpshopgermany.de/?p=3968';

		const MODE_DAYS = 1;
		const MODE_SELECT = 2;

		/**
		 * Constructor
		 */
		public function __construct()
		{

			parent::__construct();

			$this->name = __('Lieferzeit', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Erlaubt die Definition und Anzeige von Lieferzeiten.', 'wpsg');

		} // public function __construct()

		public function install()
		{

			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Produkt Tabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
		   		wpsg_mod_deliverytime_source INT(1) DEFAULT 0 NOT NULL COMMENT '1=vom Produkt',
				wpsg_mod_deliverytime_deliverytime VARCHAR(255) NOT NULL,
				wpsg_mod_deliverytime_storeproduct VARCHAR (255) NOT NULL,
				wpsg_mod_deliverytime_storetext VARCHAR (255) NOT NULL COMMENT 'Hinweistext',
				wpsg_mod_deliverytime_storelink VARCHAR (255) NOT NULL COMMENT 'Link zur Adresse',
				wpsg_mod_deliverytime_delay INT(1) DEFAULT 0 NOT NULL COMMENT '1=Verzögerung',
				wpsg_mod_deliverytime_delaytext VARCHAR (255) NOT NULL COMMENT 'Grund',
				wpsg_mod_deliverytime_delaytime VARCHAR (255) NOT NULL COMMENT 'Zeitangabe',
				wpsg_mod_deliverytime_holiday INT(1) DEFAULT 0 NOT NULL COMMENT '1=Urlaub aktiv',
				wpsg_mod_deliverytime_holidayStart VARCHAR (255) NOT NULL COMMENT 'ZeitangabeBEGINN',
				wpsg_mod_deliverytime_holidayEnd VARCHAR (255) NOT NULL COMMENT 'ZeitangabeENDE',
				wpsg_mod_deliverytime_holidaytext VARCHAR (255) NOT NULL COMMENT 'Urlaubstext'
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

   			dbDelta($sql);

			// Vorgaben
			$this->shop->checkDefault('wpsg_mod_deliverytime_mode', self::MODE_DAYS);
			$this->shop->checkDefault('wpsg_mod_deliverytime_mode_days_default', '7');
			$this->shop->checkDefault('wpsg_mod_deliverytime_mode_select_values', __('3 Tage, 1 Woche, 3 Wochen', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_deliverytime_mode_select_default', __('1 Woche', 'wpsg'), false, true);
			$this->shop->checkDefault('wpsg_mod_deliverytime_show_product', '1');
			$this->shop->checkDefault('wpsg_mod_deliverytime_show_basket', '1');
			$this->shop->checkDefault('wpsg_mod_deliverytime_show_overview', '1');
			$this->shop->checkDefault('wpsg_mod_deliverytime_show_mail', '1');

		} // public function install()

		public function product_addedit_content(&$product_content, &$product_data)
		{

			if (isset($_REQUEST['wpsg_lang'])) return;

			if ($this->shop->get_option('wpsg_mod_deliverytime_mode') == self::MODE_SELECT)
			{

				$this->shop->view['wpsg_mod_deliverytime']['arSelection'] = $this->getPossibleSelection();

			}
			
			$this->shop->view['wpsg_mod_deliverytime']['arSelection'] = $this->getPossibleSelection();
			
			$pages = get_pages();
			
			$arPages = array(
					'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			
			$this->shop->view['pages'] = $arPages;
			
			//if (isset($this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_deliverytime']))
				$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_deliverytime'] = $product_data['wpsg_mod_deliverytime_deliverytime'];
			//if (isset($this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storeproduct']))
				$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storeproduct'] = $product_data['wpsg_mod_deliverytime_storeproduct'];

			// Neue Felder im Produkt übergeben
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_source'] = $product_data['wpsg_mod_deliverytime_source'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storetext'] = $product_data['wpsg_mod_deliverytime_storetext'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storelink'] = $product_data['wpsg_mod_deliverytime_storelink'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delay'] = $product_data['wpsg_mod_deliverytime_delay'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delaytext'] = $product_data['wpsg_mod_deliverytime_delaytext'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delaytime'] = $product_data['wpsg_mod_deliverytime_delaytime'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holiday'] = $product_data['wpsg_mod_deliverytime_holiday'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidaytext'] = $product_data['wpsg_mod_deliverytime_holidaytext'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidayStart'] = $product_data['wpsg_mod_deliverytime_holidayStart'];
			$this->shop->view['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidayEnd'] = $product_data['wpsg_mod_deliverytime_holidayEnd'];

			$product_content['general']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/produkt_addedit_allgemein.phtml', false);
		}


		public function settings_edit()
		{

			$this->shop->view['wpsg_mod_deliverytime']['arSelection'] = $this->getPossibleSelection();

			$pages = get_pages();
			
			$arPages = array(
					'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
			
			foreach ($pages as $k => $v)
			{
				$arPages[$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			}
			
			$this->shop->view['pages'] = $arPages;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/settings_edit.phtml');

		} // public function settings_edit()

		public function settings_save()
		{

		    $this->shop->update_option('wpsg_mod_deliverytime_mode', $_REQUEST['wpsg_mod_deliverytime_mode'], false, false, WPSG_SANITIZE_INT);
		    $this->shop->update_option('wpsg_mod_deliverytime_mode_select_values', $_REQUEST['wpsg_mod_deliverytime_mode_select_values'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_deliverytime_mode_select_default', $_REQUEST['wpsg_mod_deliverytime_mode_select_default'], false, false, WPSG_SANITIZE_INT);
			$this->shop->update_option('wpsg_mod_deliverytime_mode_days_default', $_REQUEST['wpsg_mod_deliverytime_mode_days_default'], false, false, WPSG_SANITIZE_TEXTFIELD);

			$this->shop->update_option('wpsg_mod_deliverytime_store', $_REQUEST['wpsg_mod_deliverytime_store'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_storetext', $_REQUEST['wpsg_mod_deliverytime_storetext'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_page_mod_deliverytime_storelink', $_REQUEST['wpsg_page_mod_deliverytime_storelink'], false, false, WPSG_SANITIZE_TEXTFIELD);

			$this->shop->update_option('wpsg_mod_deliverytime_show_product', $_REQUEST['wpsg_mod_deliverytime_show_product'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_show_basket', $_REQUEST['wpsg_mod_deliverytime_show_basket'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_show_overview', $_REQUEST['wpsg_mod_deliverytime_show_overview'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_show_mail', $_REQUEST['wpsg_mod_deliverytime_show_mail'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_source', wpsg_xss($_REQUEST['wpsg_mod_deliverytime_source']));
			
			$this->shop->update_option('wpsg_mod_deliverytime_delay', $_REQUEST['wpsg_mod_deliverytime_delay'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_delayText', $_REQUEST['wpsg_mod_deliverytime_delayText'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_deliverytime_delayTime', $_REQUEST['wpsg_mod_deliverytime_delayTime'], false, false, WPSG_SANITIZE_FLOAT);
			
			$this->shop->update_option('wpsg_mod_deliverytime_holiday', $_REQUEST['wpsg_mod_deliverytime_holiday'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_deliverytime_holidayStart', wpsg_xss($_REQUEST['wpsg_mod_deliverytime_holidayStart']));
			$this->shop->update_option('wpsg_mod_deliverytime_holidayEnd', wpsg_xss($_REQUEST['wpsg_mod_deliverytime_holidayEnd']));
			$this->shop->update_option('wpsg_mod_deliverytime_holidaytext', $_REQUEST['wpsg_mod_deliverytime_holidaytext'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_deliverytime_productindex', $_REQUEST['wpsg_mod_deliverytime_productindex'], false, false, WPSG_SANITIZE_CHECKBOX);
			
			$this->shop->addTranslationString('wpsg_mod_deliverytime_mode_select_values', wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, $_REQUEST['wpsg_mod_deliverytime_mode_select_values']));
			$this->shop->addTranslationString('wpsg_mod_deliverytime_mode_select_default', wpsg_sinput(WPSG_SANITIZE_INT, $_REQUEST['wpsg_mod_deliverytime_mode_select_default']));
						

		} // public function settings_save()

		public function produkt_save_before(&$produkt_data)
		{
 
		    $produkt_data['wpsg_mod_deliverytime_deliverytime'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_FLOAT, $_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_deliverytime']));
			$produkt_data['wpsg_mod_deliverytime_storeproduct'] = wpsg_q(wpsg_sinput("key", wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storeproduct'])));

			// Neue Felder im Produkt
			$produkt_data['wpsg_mod_deliverytime_storetext'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_FLOAT, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storetext'])));
			$produkt_data['wpsg_mod_deliverytime_storelink'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_INT, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_storelink'])));
			$produkt_data['wpsg_mod_deliverytime_source'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_source'])));
			$produkt_data['wpsg_mod_deliverytime_delay'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_VALUES, ['0, 1, 2'], wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delay'])));
			$produkt_data['wpsg_mod_deliverytime_delaytext'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_FLOAT, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delaytext'])));
			$produkt_data['wpsg_mod_deliverytime_delaytime'] = wpsg_tf(wpsg_q(wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_delaytime']), WPSG_SANITIZE_FLOAT)));
			$produkt_data['wpsg_mod_deliverytime_holiday'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_VALUES, ['0, 1, 2'], wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holiday'])));
			$produkt_data['wpsg_mod_deliverytime_holidaytext'] = wpsg_q(wpsg_sinput(WPSG_SANITIZE_TEXTFIELD, wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidaytext'])));
			$produkt_data['wpsg_mod_deliverytime_holidayStart'] = wpsg_q(wpsg_xss(wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidayStart'])));
			$produkt_data['wpsg_mod_deliverytime_holidayEnd'] = wpsg_q(wpsg_xss(wpsg_getStr($_REQUEST['wpsg_mod_deliverytime']['wpsg_mod_deliverytime_holidayEnd'])));
			
		} // public function produkt_save_before(&$produkt_data)

		public function mail_row($index, $produkt)
		{

			if ($this->shop->get_option('wpsg_mod_deliverytime_show_mail') != '1') return;

			$this->shop->view['wpsg_mod_deliverytime']['i'] = $index;
			$this->shop->view['wpsg_mod_deliverytime']['p'] = $produkt;
			$this->shop->view['wpsg_mod_deliverytime']['deliverytime'] = $this->displayDeliveryTime($this->shop->getProduktID($produkt['id']));
			$this->shop->view['wpsg_mod_deliverytime']['delaytime'] = $this->displayDelayTime($this->shop->getProduktID($produkt['id']));
			
			if ($this->shop->htmlMail === true)
			{

				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/mail_row_html.phtml');

			}
			else
			{

				$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/mail_row.phtml');

			}

		} // public function mail_row($index, $produkt)

		public function basket_row(&$p, $i)
		{

			if ($this->shop->get_option('wpsg_mod_deliverytime_show_basket') != '1') return;

			$this->shop->view['wpsg_mod_deliverytime']['i'] = $i;
			$this->shop->view['wpsg_mod_deliverytime']['p'] = $p;
			
			$this->shop->view['wpsg_mod_deliverytime']['deliverytime'] = $this->displayDeliveryTime($this->shop->getProduktID($p['id']));
			$this->shop->view['wpsg_mod_deliverytime']['delaytime'] = $this->displayDelayTime($this->shop->getProduktID($p['id']));
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/basket_row.phtml');

		} // public function basket_row(&$p, $i)

		public function overview_row(&$p, $i)
		{

			if ($this->shop->get_option('wpsg_mod_deliverytime_show_overview') != '1') return;

			$this->shop->view['wpsg_mod_deliverytime']['i'] = $i;
			$this->shop->view['wpsg_mod_deliverytime']['p'] = $p;
			
			$this->shop->view['wpsg_mod_deliverytime']['deliverytime'] = $this->displayDeliveryTime($this->shop->getProduktID($p['id']));
			$this->shop->view['wpsg_mod_deliverytime']['delaytime'] = $this->displayDelayTime($this->shop->getProduktID($p['id']));
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_deliverytime/overview_row.phtml');

		} // public function overview_row(&$p, $i)

		public function wpsg_mod_export_loadFields(&$arFields)
		{

			$arFields[20]['fields']['product_deliverytime'] = __('Lieferzeit', 'wpsg');

		} // public function wpsg_mod_export_loadFields(&$arFields)

		public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator)
		{

			if ($field_value == 'product_deliverytime')
			{

				$return = $this->getProductDeliveryTime($p_id);

			}

		} // public function wpsg_mod_export_getValue(&$return, $field_value, $o_id, $p_id, $productkey, $product_index, $profil_separator)

		/* Modul Functions */

		/**
		 * Liefert einen Text, der bei Lieferverzögerungen angezeigt wird
		 * @param $product_key
		 */
		public function displayDelayTime($product_key)
		{

			// 'Hinweistext bei Verzögerungen anzeigen', 'wpsg'),
			// array(0 => 'Standardeinstellung', 1 => 'Anzeigen', 2 => 'Nicht anzeigen')
			
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
			
			if ($oProduct->wpsg_mod_deliverytime_delay == 1) return wpsg_translate(__('#1# #2#', 'wpsg'), $oProduct->wpsg_mod_deliverytime_delaytext, $oProduct->wpsg_mod_deliverytime_delaytime);
			else if ($oProduct->wpsg_mod_deliverytime_delay == 2) return false;
			
			if ($this->shop->get_option('wpsg_mod_deliverytime_delay') != '1') return false;

			return wpsg_translate(__('#1##2#', 'wpsg'), $this->shop->get_option('wpsg_mod_deliverytime_delayText'), $this->shop->get_option('wpsg_mod_deliverytime_delayTime'));

		} // public function displayDelayTime($product_key)

		public function canOrder($product_key)
		{
			
			/* Offlineprodukt */
			if ($this->isStoreProduct($product_key)) return -2;
			
			/* Urlaubsmodul*/
			if ($this->shop->get_option('wpsg_mod_deliverytime_holiday') == '1') {
			
				$time = time();
				$holidaystart = strtotime($this->shop->get_option('wpsg_mod_deliverytime_holidayStart'));
				$holidayend = strtotime($this->shop->get_option('wpsg_mod_deliverytime_holidayEnd'));
				
				if ($time >= $holidaystart && $time <= $holidayend) return -2;
				
			}
			
		}
		
		/**
		 *	Liefert den Text, der bei aktivierter Urlaubsoption angezeigt wird 
		 * 	@param $product_key
		 */
		public function displayHolidaytext($product_key)
		{
				
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
				
			if ($oProduct->wpsg_mod_deliverytime_holiday == 1) return __($oProduct->wpsg_mod_deliverytime_holidaytext, 'wpsg');
			else return __($this->shop->get_option('wpsg_mod_deliverytime_holidaytext'), 'wpsg');
				
		} // public function displayHolidaytext($product_key)
		
		/**
		 * Liefert einen Text, der bei Offline-Produkten angezeigt wird
		 * @param $product_key
		 */
		public function displayStoreText($product_key)
		{
			
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
			
			if ($oProduct->wpsg_mod_deliverytime_storeproduct == 1) return __($oProduct->wpsg_mod_deliverytime_storetext, 'wpsg');
			else return __($this->shop->get_option('wpsg_mod_deliverytime_storetext'), 'wpsg');
			
		} // public function displayStoreText($product_key)
		
		/**
		 * Liefert einen Link, der bei Offline-Produkten angezeigt wird
		 * @param $product_key
		 */
		public function displayStoreLink($product_key)
		{
			
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
			
			if ($oProduct->wpsg_mod_deliverytime_storeproduct == 1) return __($oProduct->wpsg_mod_deliverytime_storelink, 'wpsg');
			else return __($this->shop->get_option('wpsg_page_mod_deliverytime_storelink'), 'wpsg');
			
		} // public function displayStoreLink($product_key)
		
		/**
		 * Zeigt die Lieferzeit formatiert an.
		 * Bsp: Lieferzeit: 4 Tag(e)
		 */
		public function displayDeliveryTime($product_key, $valueOnly = false)
		{

			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));

			$strValue = "";

			if ($this->shop->get_option('wpsg_mod_deliverytime_mode') == wpsg_mod_deliverytime::MODE_DAYS)
			{

				//$temp = $oProduct->wpsg_mod_deliverytime_deliverytime;
				
				$temp = $oProduct->wpsg_mod_deliverytime_source;
				if (wpsg_isSizedInt($temp))
				{

					/* Lieferzeit = 1 vom Produkt */
					$strValue = wpsg_translate(__('#1# Tag(e)', 'wpsg'), $oProduct->wpsg_mod_deliverytime_deliverytime);

				}
				else if (wpsg_isSizedString($this->shop->get_option('wpsg_mod_deliverytime_mode_days_default')))
				{

					/* Lieferzeit = 0 Global */
					$strValue = wpsg_translate(__('#1# Tag(e)', 'wpsg'), $this->shop->get_option('wpsg_mod_deliverytime_mode_days_default'));

				}
				else
				{

					$strValue = __('Das Produkt ist sofort lieferbar (Lieferzeit: 0 Tage)', 'wpsg');

				}

			}
			else
			{

				$arPossibleDelivery = wpsg_trim(explode(',', $this->shop->get_option('wpsg_mod_deliverytime_mode_select_values')));
				$strDeliveryTime = @$oProduct->wpsg_mod_deliverytime_deliverytime;

				//if (wpsg_isSizedString($strDeliveryTime) && in_array($strDeliveryTime, $arPossibleDelivery))
				//if (wpsg_isSizedString($strDeliveryTime) && array_key_exists($strDeliveryTime, $arPossibleDelivery))
				$temp = $oProduct->wpsg_mod_deliverytime_source;
				if (wpsg_isSizedInt($temp))
				{

					$strValue = __($strDeliveryTime, 'wpsg');
					$strValue = __(@$arPossibleDelivery[$strDeliveryTime], 'wpsg');
					
				}
				else
				{

					if (array_key_exists($this->shop->get_option('wpsg_mod_deliverytime_mode_select_default'), $arPossibleDelivery))
					{

						$strValue = __(@$arPossibleDelivery[$this->shop->get_option('wpsg_mod_deliverytime_mode_select_default')], 'wpsg');

					}
					else
					{

						$strValue = __('Ungültige Lieferzeitkonfiguration', 'wpsg');

					}

				}

			}

			if ($valueOnly === true) return $strValue;
			else return wpsg_translate(__('#1#', 'wpsg'), $strValue);

		} // public function displayDeliveryTime($product_key)

		/**
		 * Gibt true zurück, wenn das Produkt ein Offline Produkt ist, und die Option "Offline Produkte verwenden" aktiviert ist
		 * @param string $product_key
		 */
		public function isStoreProduct($product_key)
		{

			// Die Produkteinstellung Als Offlineprodukt anzeigen
			// 'Als Offlineprodukt anzeigen', 'wpsg'), 
			// array(0 => 'Standardeinstellung', 1 => 'Offlineprodukt', 2 => 'Onlineprodukt')
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
			
			if ($oProduct->wpsg_mod_deliverytime_storeproduct == 2) return false;
			else if ($oProduct->wpsg_mod_deliverytime_storeproduct == 1) return true;
			else if ($this->shop->get_option('wpsg_mod_deliverytime_store') != '1') return false;
			else return true;
			
		} // public function isStoreProduct($product_key)
		
	
		public function holiday ($product_key)
		{
		
			// Die Produkteinstellung als Urlaub anzeigen
			// array(0 => 'Standardeinstellung', 1 => 'Offlineprodukt', 2 => 'Onlineprodukt')
			$oProduct = wpsg_product::getInstance($this->shop->getProduktID($product_key));
				
			if ($oProduct->wpsg_mod_deliverytime_holiday == 2) return false;
			else if ($oProduct->wpsg_mod_deliverytime_holiday == 1) return true;
			else if ($this->shop->get_option('wpsg_mod_deliverytime_holiday') != '1') return false;
			else return true;
				
		}
		
		/**
		 * Gibt die Lieferzeit eines Produktes anhand seiner ID zurück
		 * @param unknown $product_id
		 */
		public function getProductDeliveryTime($product_id)
		{

			$product_data = $this->shop->cache->loadProduct($product_id);

			if ($this->shop->get_option('wpsg_mod_deliverytime_mode') == self::MODE_DAYS)
			{

				//if (!wpsg_isSizedString($product_data['wpsg_mod_deliverytime_deliverytime']))
					
				if (!wpsg_isSizedInt($product_data['wpsg_mod_deliverytime_source']))
				{

					return $this->shop->get_option('wpsg_mod_deliverytime_mode_days_default').__(' Tag(e)', 'wpsg');

				}
				else
				{

					return $product_data['wpsg_mod_deliverytime_deliverytime'].__(' Tag(e)', 'wpsg');

				}

			}
			else if ($this->shop->get_option('wpsg_mod_deliverytime_mode') == self::MODE_SELECT)
			{

				$arPossibleDeliveryTimes = $this->getPossibleSelection();

				//if (!in_array($product_data['wpsg_mod_deliverytime_deliverytime'], $arPossibleDeliveryTimes))
				//if (!array_key_exists($product_data['wpsg_mod_deliverytime_deliverytime'], $arPossibleDeliveryTimes))
				if (!wpsg_isSizedInt($product_data['wpsg_mod_deliverytime_source']))
				{

					return __($arPossibleDeliveryTimes[$this->shop->get_option('wpsg_mod_deliverytime_mode_select_default')], 'wpsg');

				}
				else
				{

					return __($arPossibleDeliveryTimes[$product_data['wpsg_mod_deliverytime_deliverytime']], 'wpsg');
					
				}

			}

		} // public function getProductDeliveryTime($product_id)

		/**
		 * Gibt die möglichen Lieferzeiten als Array zurück, ist nur Sinnvoll wenn
		 * MODE_SELECT aktiv ist.
		 */
		public function getPossibleSelection()
		{

			return wpsg_trim(explode(',', $this->shop->get_option('wpsg_mod_deliverytime_mode_select_values')));

		} // public function getPossibleSelection()

	} // class wpsg_mod_deliverytime extends wpsg_mod_basic

?>