<?php

	/**
	 * Modul um Produkten weitere Produkte als Zubehör etc. zuzuordnen
	 * Enter description here ...
	 * @author Daschmi
	 *
	 */
	class wpsg_mod_relatedproducts extends wpsg_mod_basic
	{
		
		var $lizenz = 1;
		var $id = 2;
		var $url = false; // URL zum PayPal Endpunkt, wird von der init() Methode gesetzt
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Zubehörprodukte', 'wpsg');
			$this->group = __('Produkte', 'wpsg');
			$this->desc = __('Ermöglicht es zu einem Produkt weitere Produkte als Zubehör etc. anzugeben und darzustellen.', 'wpsg');
						
		} // public function __construct()
		
		public function install()
		{
			 
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

			/**
			 * Produkttabelle erweitern
			 */
			$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS_REL." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				p_id int(11) NOT NULL,
				rel_id int(11) NOT NULL,
				template varchar(255) NOT NULL,
				PRIMARY KEY  (id),
				KEY p_id (p_id),
				KEY rel_id (rel_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			
			dbDelta($sql);
			
		} // public function install()
				
		public function init()
		{
				
			add_shortcode('wpsg_relatedproducts_basket', array($this, 'wpsg_relatedproducts_basket'));
				
		} // public function init()
		
		public function settings_edit()
		{
			
			$this->shop->view['wpsg_mod_relatedproducts']['arTemplates'] = $this->shop->loadProduktTemplates(true);
			array_unshift($this->shop->view['wpsg_mod_relatedproducts']['arTemplates'], __('Für jedes Produkt einstellbar', 'wpsg'));		
			 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/settings_edit.phtml');
			
		} // public function settings_edit()

		public function settings_save()
		{

			$arTemplateFiles = $this->shop->loadProduktTemplates(true);
			 
			$this->shop->update_option('wpsg_mod_relatedproducts_template', $_REQUEST['wpsg_mod_relatedproducts_template'], false, false, WPSG_SANITIZE_VALUES, array_keys(['0'] + $arTemplateFiles)); 
			
			$this->shop->update_option('wpsg_mod_relatedproducts_synchron', $_REQUEST['wpsg_mod_relatedproducts_synchron'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_relatedproducts_showBasket', $_REQUEST['wpsg_mod_relatedproducts_showBasket'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_relatedproducts_showBasketLimit', $_REQUEST['wpsg_mod_relatedproducts_showBasketLimit'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_relatedproducts_showBasketTemplate', $_REQUEST['wpsg_mod_relatedproducts_showBasketTemplate'], false, false, WPSG_SANITIZE_VALUES, array_keys(['0'] + $arTemplateFiles));
			$this->shop->update_option('wpsg_mod_relatedproducts_showAjaxDialog', $_REQUEST['wpsg_mod_relatedproducts_showAjaxDialog'], false, false, WPSG_SANITIZE_CHECKBOX);
			$this->shop->update_option('wpsg_mod_relatedproducts_showAjaxDialogLimit', $_REQUEST['wpsg_mod_relatedproducts_showAjaxDialogLimit'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$this->shop->update_option('wpsg_mod_relatedproducts_showAjaxDialogTemplate', $_REQUEST['wpsg_mod_relatedproducts_showAjaxDialogTemplate'], false, false, WPSG_SANITIZE_VALUES, array_keys(['0'] + $arTemplateFiles));
		 
		} // public function settings_save
		
		public function produkt_save(&$produkt_id) 
		{
			 	
			if (isset($_REQUEST['wpsg_mod_relatedproduct']) && is_array($_REQUEST['wpsg_mod_relatedproduct']) && sizeof($_REQUEST['wpsg_mod_relatedproduct']) > 0)
			{
				
				foreach ($_REQUEST['wpsg_mod_relatedproduct'] as $rp_id => $template)
				{
					
					$this->db->UpdateQuery(WPSG_TBL_PRODUCTS_REL, array(
						'template' => wpsg_q(wpsg_sinput("text_field", $template))
					), "`id` = '".wpsg_q(wpsg_sinput("key", $rp_id))."'");
					
				}
				
			}
			
		} // public function produkt_save($produkt_id)
		
		/*
		 * zeigt die Zubehörprodukte im Produktbackend an
		*/
		public function product_addedit_content(&$product_content, &$product_data)
		{
			
			if ($product_data['id'] <= 0) return;
			if (isset($_REQUEST['wpsg_lang'])) return;
						
			$this->shop->view['data'] = $product_data;			 
		 
			$product_content['general']['content'] .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/produkt_addedit_sidebar.phtml', false);
				
		
		} //public function product_addedit_content(&$product_content, &$product_data)
		
		
		public function produkt_ajax()
		{


			if (isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'wpsg_rp_add')
			{

				$_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);
				$_REQUEST['rel_id'] = wpsg_sinput("key", $_REQUEST['rel_id']);

				if ($_REQUEST['template'] > 0 && strlen($_REQUEST['template_file']) > 0) $template = $_REQUEST['template_file']; else $template = '';
				
				$bExists = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_PRODUCTS_REL."` WHERE `p_id` = '".wpsg_q($_REQUEST['edit_id'])."' AND `rel_id` = '".wpsg_q($_REQUEST['rel_id'])."'");
				
				if ($bExists <= 0)
				{
				
					$this->db->ImportQuery(WPSG_TBL_PRODUCTS_REL, array(
						'p_id' => wpsg_q($_REQUEST['edit_id']),
						'rel_id' => wpsg_q($_REQUEST['rel_id']),
						'template' => wpsg_q($template)
					));
					
				}
				
				die($this->drawList($_REQUEST['edit_id']));
				
			}
			else if (isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'wpsg_rp_remove')
			{
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_PRODUCTS_REL."` WHERE `id` = '".wpsg_q($_REQUEST['rel_id'])."'");
				
				die($this->drawList($_REQUEST['edit_id']));
				
			}
			
		} // public function produkt_ajax()
		
		public function basket_after(&$basket_view) 
		{ 
		
			if (!wpsg_isSizedInt($this->shop->get_option('wpsg_mod_relatedproducts_showBasket'))) return false;
			
			$arRelatedBasketProducts = $this->getRelatedBasketProducts($this->shop->get_option('wpsg_mod_relatedproducts_showBasketLimit'));
			
			if (!wpsg_isSizedArray($arRelatedBasketProducts)) return false;
			
			$this->shop->view['wpsg_mod_relatedproducts']['data'] = $arRelatedBasketProducts;
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/basket_after.phtml');
			
		} // public function basket_after(&$basket_view)
		
		public function renderProdukt_afterForm(&$produkt_data, &$html) 
		{ 
			 
			// Um Rekursion zu vermeiden
			if (wpsg_isTrue($this->shop->noReleatedProducts)) return;
			
			$arRelated = $this->getRelatedProducts($produkt_data['id']);
			
			if (is_array($arRelated) && sizeof($arRelated) > 0)
			{

				$this->shop->view['wpsg_mod_relatedproducts']['content'] = '';
				
				foreach ($arRelated as $rp) $this->shop->view['wpsg_mod_relatedproducts']['content'] .= $this->renderRelatedProduct($rp, $this->shop->get_option('wpsg_mod_relatedproducts_template'));
				
				$html .= $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/relatedproducts.phtml', false);
				
				$this->shop->noReleatedProducts = false;
				
			}
			
		} // public function renderProdukt_afterForm(&$produkt_data, &$html) 
		
		/* Modulfunktionen */
		
		/**
		 * Shortcode für die Anzeige von Zubehörprodukten zu den Warenkorbprodukten
		 */
		public function wpsg_relatedproducts_basket($atts)
		{
			
			if (wpsg_isSizedInt($atts['limit'])) $limit = $atts['limit'];
			else $limit = $this->shop->get_option('wpsg_mod_relatedproducts_showBasketLimit');
							
			$arRelatedBasketProducts = $this->getRelatedBasketProducts($limit);
				
			if (!wpsg_isSizedArray($arRelatedBasketProducts)) return '';
				
			$this->shop->view['wpsg_mod_relatedproducts']['data'] = $arRelatedBasketProducts;
			
			return $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/basket_after.phtml', false);
			
		} // public function wpsg_relatedproducts_basket($atts)
		
		/**
		 * Zeichnet ein "Zubehörprodukt"
		 * Bekommt die Daten aus der Zubehörprodukttabelle übergeben
		 */
		public function renderRelatedProduct(&$rp_data, $set_template)
		{
			
			$this->shop->noReleatedProducts = true;
					
			// Das Template 
			if (wpsg_isSizedString($set_template) && $set_template != '0')
			{
				
				$template = $set_template;
				
			}
			else
			{
				
				$template = (($rp_data['template'] != '' && $rp_data['template'] != '0')?$rp_data['template']:false);
				
			}
			
			return $this->shop->renderProdukt($rp_data['product_id'], $template);					
			
		} // public function renderRelatedProduct($rp_data)
		
		/**
		 * Gibt Verwante Produkte (Array mit IDs, Name, Template) zu den Produkten im Warenkorb zurück
		 * Maximal $limit
		 */
		public function getRelatedBasketProducts($limit)
		{
						
			$arReturn = array();			
			$arBasket = $this->shop->basket->toArray();
			
			foreach ($arBasket['produkte'] as $product_index => $product_data)
			{
				 
				$arRelatedProducts = $this->getRelatedProducts($this->shop->getProduktId($product_data['id']));
				 
				foreach ($arRelatedProducts as $product_related)
				{
					
					// Ist das Produkt schon im Warenkorb?
					$bDrin = false;
					foreach ($arBasket['produkte'] as $product_index2 => $product_data2) { if ($this->shop->getProduktId($product_data2['id']) == $product_related['product_id']) { $bDrin = true; break; } }
					
					if ($bDrin === false)
					{
					
						$arReturn[$product_related['product_id']] = $product_related;
					
						if (wpsg_isSizedInt($limit) && sizeof($arReturn) == $limit) return $arReturn;
						
					}
					
				}
				
			}
						
			return $arReturn;
			
		} // public function getRelatedBasketProducts($limit)
		
		/**
		 * Zeichnet die Liste der zugeordneten Produkte für das Backend
		 */
		public function drawList($produkt_id)
		{
				
			$this->shop->view['wpsg_mod_relatedproducts']['arTemplates'] = $this->shop->loadProduktTemplates(true);
			array_unshift($this->shop->view['wpsg_mod_relatedproducts']['arTemplates'], __('Aus Produkt', 'wpsg'));		
		
			$this->shop->view['wpsg_mod_relatedproducts']['data'] = $this->getRelatedProducts($produkt_id);
			$this->shop->view['wpsg_mod_relatedproducts']['product_id'] = $produkt_id;	
 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_relatedproducts/list.phtml');
			
		} // public function drawList($produkt_id)
		
		/**
		 * Gibt einen Array mit ID, Name und Template von Zubehör zurück anhand der Produkt_id
		 */
		public function getRelatedProducts($produkt_id)
		{
				
			if ($this->shop->get_option('wpsg_mod_relatedproducts_synchron') == '1')			
			{
				
				return $this->db->fetchAssoc("
					SELECT
						RP.`id`, RELP.`id` AS `product_id`, RELP.`name`, RP.`template`
					FROM
						`".WPSG_TBL_PRODUCTS_REL."` AS RP
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS RELP ON (RELP.`id` = RP.`rel_id` OR RP.`p_id` = RELP.`id`)
					WHERE
						(RP.`p_id` = '".wpsg_q($produkt_id)."' OR RP.`rel_id` = '".wpsg_q($produkt_id)."') AND
						RELP.`deleted` != '1' AND RELP.`id` != '".wpsg_q($produkt_id)."'
					GROUP BY
						RELP.`id`
				");
				
			}
			else
			{
			
				return $this->db->fetchAssoc("
					SELECT
						RP.`id`, RELP.`id` AS `product_id`, RELP.`name`, RP.`template`
					FROM
						`".WPSG_TBL_PRODUCTS_REL."` AS RP
							LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS RELP ON (RELP.`id` = RP.`rel_id`)
					WHERE
						RP.`p_id` = '".wpsg_q($produkt_id)."'  AND
						RELP.`deleted` != '1' 
				");
				
			}
			
		} // public function getRelatedProducts($produkt_id)		
		
	} // class wpsg_mod_relatedproducts extends wpsg_mod_basic

?>