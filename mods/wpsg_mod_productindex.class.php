<?php

	/**
	 * Modul welches Produktübersichtsseiten ermöglicht
	 */
	class wpsg_mod_productindex extends wpsg_mod_basic
	{

		var $lizenz = 1;
		var $id = 30;
		var $hilfeURL = 'http://wpshopgermany.de/?p=3183';

		var $order = false; // Wird für die Übergabe an die Sortierfunktion verwendet
		var $ascdesc = false; // Wird für die Richtugn der Sortierung für die Callback Funktion verwendet

		public function __construct()
		{

			parent::__construct();

			$this->name = __('Produktübersichten', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht die Darstellung von Produktübersichtsseiten.', 'wpsg');

		} // public function __construct()

		public function init()
		{

			add_action('add_meta_boxes', array(&$this, 'wpsg_add_post_meta_boxes'));
			add_action('save_post', array(&$this, 'wpsg_save_postdata'));

		} // public function init()

		public function content_filter(&$content)
		{
		    
		    $id = wpsg_get_the_id();

			if ($id <= 0) return;

			$index_page = get_post_meta($id, 'wpsg_mod_productindex_active', true);

			if ($index_page === '1')
			{

                $_REQUEST = wpsg_xss($_REQUEST);
			    
				// Theme übernehmen?
				if (class_exists('\\sto\\frontend\\Productindex') && in_array(get_post_meta($id, 'wpsg_mod_productindex_template', true), array('0', '1')))
				{

					$content = \sto\frontend\Productindex::render($id);

					return -2;

				}

				$this->shop->view['arProducts'] = $this->getProducts($id);

				if (isset($_REQUEST['wpsg_mod_productindex']['template']))
				{

					$template = $_REQUEST['wpsg_mod_productindex']['template'];

				}
				else
				{

					$template = get_post_meta($id, 'wpsg_mod_productindex_template', true);

				}

				$arTemplates = $this->getTemplates();
				
				if (is_numeric($template))
					$templatefile = $arTemplates[$template];
				else
					$templatefile = $template;
				
				//$this->shop->view['wpsg_mod_productindex']['template'] = $template;

				//$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productindex/layouts/'.$template, false);

				$this->shop->view['wpsg_mod_productindex']['template'] = $templatefile;
 
				$content = $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productindex/layouts/'.$templatefile, false);

				return -2;

			}

		} // public function content_filter(&$content)

		public function wpsg_enqueue_scripts()
		{

			if (is_admin()) return;

			$index_page = get_post_meta(get_the_id(), 'wpsg_mod_productindex_active', true);

			if ($index_page === '1')
			{

				wp_enqueue_style('wpsg-mod-productindex-style', $this->shop->getRessourceURL('mods/mod_productindex/css/productindex.css'));

			}

		} // public function wpsg_enqueue_scripts()

		/* Modulfunktionen */

		/**
		 * Gibt einen Array von Produkten für die Seite zurück
		 */
		function getProducts($post_id)
		{

			global $wpdb;

			if (isset($_REQUEST['wpsg_mod_productindex']['filter']))
			{

				$filter = $_REQUEST['wpsg_mod_productindex']['filter'];

			}
			else
			{

				$filter = array();

			}

			$show = get_post_meta($post_id, 'wpsg_mod_productindex_show', true);

			$strQuerySELECT = "";
			$strQueryJOIN = "";

			if ($show == '1')
			{

				// Auswahl (Kommagetrennte IDs) anzeigen
				$arIDs = explode(',', get_post_meta($post_id, 'wpsg_mod_productindex_productids', true));

				if (sizeof($arIDs) <= 0)
				{

					wpsg_debug(__('Bitte geben sie eine gültige Auswahl von kommagetrennten ProduktIDs auf dieser Seite ein.', 'wpsg'));
					$strQuerySELECT .= " AND 0 ";

				}
				else if (sizeof($arIDs) == 1)
				{

					$strQuerySELECT .= " AND P.`id` = '".wpsg_q($arIDs[0])."' ";

				}
				else
				{

					$strQuerySELECT .= " AND P.`id` IN (".wpsg_q(implode(',', $arIDs)).") ";

				}

			}
			else if ($show == '2')
			{

				$arPG = array_values(get_post_meta($post_id, 'wpsg_mod_productindex_groups', true));

				if (sizeof($arPG) <= 0)
				{

					wpsg_debug(__('Bitte wählen sie mindestens eine Produktgruppe zur Anzeige bei den Seiteneinstellungen dieser Seite aus.', 'wpsg'));
					$strQuerySELECT .= " AND 0 ";

				}
				else if (sizeof($arPG) == 1)
				{

					$strQuerySELECT .= " AND P.`pgruppe` = '".wpsg_q($arPG[0])."' ";

				}
				else
				{

					$strQuerySELECT .= " AND P.`pgruppe` IN (".wpsg_q(implode(',', $arPG)).") ";

				}

			}
			else if ($show == '3')
			{

				$arKat = array_values(get_post_meta($post_id, 'wpsg_mod_productindex_categories', true));

				if (sizeof($arKat) <= 0)
				{

					wpsg_debug(__('Bitte wählen sie mindestens eine Kategorie zur Anzeige bei den Seiteneinstellungen dieser Seite aus.', 'wpsg'));
					$strQuerySELECT .= " AND 0 ";

				}
				else
				{

					// Weiter unten wird noch einmal gejoint mit posts, für die Sortierung. Aliase beachten !
					$strQueryJOIN .= " LEFT JOIN `".$this->shop->prefix."posts` AS PP ON (PP.`wpsg_produkt_id` = P.`id` AND PP.`post_type` = '".wpsg_q($this->shop->get_option('wpsg_mod_produktartikel_pathkey'))."') ";
					$strQueryJOIN .= " LEFT JOIN `".$this->shop->prefix."term_relationships` AS TR ON (PP.`id` = TR.`object_id`) ";
					$strQueryJOIN .= " LEFT JOIN `".$this->shop->prefix."term_taxonomy` AS TT ON (TT.`term_taxonomy_id` = TR.`term_taxonomy_id`) ";

					if (sizeof($arKat) == 1)
					{

						//$strQuerySELECT .= " AND TR.`term_taxonomy_id` = '".wpsg_q($arKat[0])."' ";
						$strQuerySELECT .= " AND TT.`term_id` = '".wpsg_q($arKat[0])."' ";

					}
					else
					{

						//$strQuerySELECT .= " AND TR.`term_taxonomy_id` IN (".wpsg_q(implode(',', $arKat)).") ";
						$strQuerySELECT .= " AND TT.`term_id` IN (".wpsg_q(implode(',', $arKat)).") ";

					}

				}

			} 
			else if ($show === 'top1cat') {
			    
			    $arProductIDs = $this->shop->callMod('wpsg_mod_produktartikel', 'getTop1CatProductIDs');

			    if (wpsg_isSizedArray($arProductIDs)) {
                
			        $strQuerySELECT .= " AND P.`id` IN (".wpsg_q(implode(',', $arProductIDs)).") ";
			        
                } else {
			        
			        $strQuerySELECT .= " AND 0 ";
			        
                }
                			    
            }
			
            if (get_post_meta($post_id, 'wpsg_mod_productindex_hideOrder', true) == '1') $this->shop->view['hideOrder'] = true;
			else $this->shop->view['hideOrder'] = false;

			if (get_post_meta($post_id, 'wpsg_mod_productindex_hideViewSelect', true) == '1') $this->shop->view['hideViewSelect'] = true;
			else $this->shop->view['hideViewSelect'] = false;

			if (!isset($filter['order']))
			{
				$filter['order'] = get_post_meta($post_id, 'wpsg_mod_productindex_order', true);
			}

			switch ($filter['order'])
			{

				case 'price_asc': $this->order = 'min_preis'; $this->ascdesc = 'ASC'; break;
				case 'price_desc': $this->order = 'min_preis'; $this->ascdesc = 'DESC'; break;
				case 'name_asc': $this->order = 'name'; $this->ascdesc = 'ASC'; break;
				case 'name_desc': $this->order = 'name'; $this->ascdesc = 'DESC'; break;
				case 'cdate_asc': $this->order = 'cdate'; $this->ascdesc = 'ASC'; break;
				case 'pos_asc': $this->order = 'pos'; $this->ascdesc = 'ASC'; break;
				case 'pos_desc': $this->order = 'pos'; $this->ascdesc = 'DESC'; break;
				case 'anr_asc': $this->order = 'anr'; $this->ascdesc = 'ASC'; break;
				case 'anr_desc': $this->order = 'anr'; $this->ascdesc = 'DESC'; break;
				default: $this->order = 'cdate'; $this->ascdesc = 'DESC';

			}

			$strQuery = "
				SELECT
					P.`id`
				FROM
					`".WPSG_TBL_PRODUCTS."` AS P
					".$strQueryJOIN."
				WHERE
					P.`deleted` != '1' AND
					P.`disabled` != '1' AND 
					P.`lang_parent` = '0'
					".$strQuerySELECT."
				GROUP BY
					P.`id`
			";

			$arProductIDs = $this->db->fetchAssocField($strQuery);

			// Seiten pro Seite
			$filter['perPage'] = get_post_meta($post_id, 'wpsg_mod_productindex_perPage', true);
			if ($filter['perPage'] <= 0) $filter['perPage'] = 10;

			// Produkte durch Module selektieren (Lagerbestand)
			$arProducts = array();

			foreach ($arProductIDs as $p_id)
			{

				$oProduct = wpsg_product::getInstance($p_id);
				if (!$oProduct->canDisplay()) continue;

				$product_data = $this->shop->loadProduktArray($p_id);

				if ($product_data['disabled'] != 1)
				{

					// Sonst immer hinzufügen
					$arProducts[] = $product_data;
				}

			}

			// Anzahl an Produkten
			$filter['count'] = sizeof($arProducts);

			// Anzahl an Seiten
			$filter['pages'] = ceil($filter['count'] / $filter['perPage']);

			// Aktuelle Seite
			if (!isset($filter['page']) || $filter['page'] > $filter['pages'] || $filter['page'] <= 0) $filter['page'] = 1;

			// Sortieren
			uasort($arProducts, array($this, 'order'));

			// Seite selektieren
			$arProducts = array_slice($arProducts, ($filter['page'] - 1) * $filter['perPage'], $filter['perPage']);

			$this->shop->view['wpsg_mod_productindex']['filter'] = $filter;

			return $arProducts;

		} // function getProducts($post_id)

		/**
		 * Sortiert zwei Produktarrays nach $this->order
		 */
		public function order($a, $b)
		{

			if ($this->order == 'cdate')
			{

				$a = strtotime($a[$this->order]);
				$b = strtotime($b[$this->order]);

			}
			else
			{

				$a = $a[$this->order];
				$b = $b[$this->order];

			}

			// Vorsicht Bei Sortierung nach Datum
			$arSearch = array("Ä", "ä", "Ö", "ö", "Ü", "ü", "ß", "-");
			$arReplace = array("Ae", "ae", "Oe", "oe", "Ue", "ue", "ss", " ");

			$a = str_replace($arSearch, $arReplace, $a);
			$b = str_replace($arSearch, $arReplace, $b);

			if ($a == $b)
			{

				return 0;

			}

			if ($this->ascdesc == 'ASC')
				return ($a < $b) ? -1 : 1;
			else
				return ($a > $b) ? -1 : 1;

		} // public function order($a, $b)

		/**
		 * Wird zum Speichern der Page Meta Box aufgerufen
		 */
		function wpsg_save_postdata($post_id)
		{
			
			/* Erweiterung für Kompatibilität mit Customer Fields */
			//if ( $_POST['post_type'] != 'post' && $_POST['post_type'] != 'page' ) return; 
		    if (isset($_POST['post_type']) && $_POST['post_type'] != 'post' && $_POST['post_type'] != 'page' ) return;
		    
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

			if (isset($_POST['wpsg_mod_productindex']))
				if (!wp_verify_nonce($_POST['wpsg_mod_productindex'], plugin_basename(__FILE__))) return;

			if (!isset($_POST['post_type'])) return;

			if ('page' == $_POST['post_type'])
			{

				if (!current_user_can('edit_page', $post_id)) return;

			}
			else
			{

				if (!current_user_can('edit_post', $post_id)) return;

			}

			$post_ID = $_POST['post_ID'];

			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_active', $_REQUEST['wpsg_mod_productindex_active']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_template', $_REQUEST['wpsg_mod_productindex_template']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_show', $_REQUEST['wpsg_mod_productindex_show']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_perPage', $_REQUEST['wpsg_mod_productindex_perPage']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_order', $_REQUEST['wpsg_mod_productindex_order']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_groups', array_values((array)wpsg_getArray($_REQUEST['wpsg_mod_productindex_groups'])));
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_productids', $_REQUEST['wpsg_mod_productindex_productids']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_categories', array_values((array)wpsg_getArray($_REQUEST['wpsg_mod_productindex_categories'])));
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_hideOrder', $_REQUEST['wpsg_mod_productindex_hideOrder']);
			$this->update_post_meta($post_ID, 'wpsg_mod_productindex_hideViewSelect', $_REQUEST['wpsg_mod_productindex_hideViewSelect']);

		} // function wpsg_save_postdata($post_id)

		/**
		 * Hilfsfunktion damit ich die kommenden 4 Zeilen nicht x mal schreiben muss ..
		 */
		private function update_post_meta($post_ID, $key, $value)
		{

			if (get_post_meta($post_ID, $key, true) !== false)
				update_post_meta($post_ID, $key, $value);
			else
				add_post_meta($post_ID, $key, $value, true);

		} // private function update_post_meta($post_ID, $key, $value)

		/**
		 * Fügt die Post Meta Boxen hinzu
		 */
		function wpsg_add_post_meta_boxes()
		{

			$this->shop->view['wpsg_mod_productindex']['arShow'] = array(
				'0' => __('Alle', 'wpsg'),
				'1' => __('Auswahl', 'wpsg')
			);

			// Produktgruppen ?
			if ($this->shop->hasMod('wpsg_mod_productgroups'))
			{

				$this->shop->view['wpsg_mod_productindex']['arShow']['2'] = __('Produktgruppen', 'wpsg');
				$this->shop->view['wpsg_mod_productindex']['arProductGroups'] = $this->db->fetchAssocField("
					SELECT
						PG.`id`, PG.`name`
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
					WHERE
						PG.`deleted` != '1'
					ORDER BY
						PG.`name` ASC
				", "id", "name");

			}

			// Produktartikel ?
			if ($this->shop->hasMod('wpsg_mod_produktartikel'))
			{

				$this->shop->view['wpsg_mod_productindex']['arShow']['3'] = __('Kategorien', 'wpsg');
                $this->shop->view['wpsg_mod_productindex']['arShow']['top1cat'] = __('Erstes Produkt aus allen Kategorien', 'wpsg');
				$this->shop->view['wpsg_mod_productindex']['arCategories'] = $this->shop->callMod('wpsg_mod_produktartikel', 'getCategorySelectArray');

			}

			$this->shop->view['wpsg_mod_productindex']['arTemplates'] = $this->getTemplates();

			add_meta_box('wpsg_mod_productindex', __('wpShopGermany Produktübersicht', 'wpsg'), array(&$this, 'wpsg_post_meta_box'), 'page');;

		} // function wpsg_add_post_meta_boxes()

		/**
		 * Inhalt der Post Meta Box innerhalb der Seiten
		 */
		function wpsg_post_meta_box($post)
		{

			wp_nonce_field(plugin_basename(__FILE__), 'wpsg_mod_productindex');

			$this->shop->view['wpsg_mod_productindex']['post_id'] = $post->ID;

			$this->shop->view['wpsg_mod_productindex']['arOrder'] = array(
				'price_asc' => __('Preis (Aufsteigend)', 'wpsg'),
				'price_desc' => __('Preis (Absteigend)', 'wpsg'),
				'name_asc' => __('Name (Aufsteigend)', 'wpsg'),
				'name_desc' => __('Name (Absteigend)', 'wpsg'),
				'cdate_asc' => __('Datum (Älteste zuerst)', 'wpsg'),
				'cdate_desc' => __('Datum (Neueste zuerst)', 'wpsg'),
				'anr_asc' => __('Artikelnr. (Aufsteigend)', 'wpsg'),
				'anr_desc' => __('Artikelnr. (Absteigend)', 'wpsg')
			);

			if ($this->shop->hasMod('wpsg_mod_produktartikel'))
			{

				$this->shop->view['wpsg_mod_productindex']['arOrder']['pos_asc'] = __('Position (Aufsteigend)', 'wpsg');
				$this->shop->view['wpsg_mod_productindex']['arOrder']['pos_desc'] = __('Position (Absteigend)', 'wpsg');

			}

			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_productindex/page_metabox.phtml');

		} // function wpsg_post_meta_box()

		/**
		 * Gibt einen Array mit den verfügbaren Templates zurück
		 */
		private function getTemplates()
		{

			$arTemplates = array();

			$handle = @opendir(WPSG_PATH_VIEW.'/mods/mod_productindex/layouts/');

			if ($handle) {
				while ($file = readdir($handle))
				{
					if (is_file(WPSG_PATH_VIEW.'/mods/mod_productindex/layouts/'.$file) && preg_match('/(.*).phtml$/', $file))
					{

						$arTemplates[] = $file;

					}

				}

			}

			@closedir($handle);

			$handle = @opendir(WPSG_PATH_USERVIEW_OLD.'/mods/mod_productindex/layouts/');

			if ($handle) {
				while ($file = readdir($handle))
				{
					if (is_file(WPSG_PATH_USERVIEW_OLD.'/mods/mod_productindex/layouts/'.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
					{

						$arTemplates[] = $file;

					}
				}
			}

			@closedir($handle);

			$handle = @opendir(WPSG_PATH_USERVIEW.'/mods/mod_productindex/layouts/');

			if ($handle) {
				while ($file = readdir($handle))
				{
					if (is_file(WPSG_PATH_USERVIEW.'/mods/mod_productindex/layouts/'.$file) && preg_match('/(.*).phtml$/', $file) && !in_array($file, $arTemplates))
					{

						$arTemplates[] = $file;

					}
				}
			}

			@closedir($handle);

			return $arTemplates;

		} // private function getTemplates()

	} // class wpsg_mod_productindex extends wpsg_mod_basic

?>