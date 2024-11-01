<?php 

	/**
	 * Modul für die Eingabe einer Versandadresse während der Bestellung
	 * @author daniel
	 *
	 */
	class wpsg_mod_shippingadress extends wpsg_mod_basic 
	{
		 
		var $lizenz = 2;
		var $id = 135;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Lieferadresse', 'wpsg');
			$this->group = __('Versand', 'wpsg');
			$this->desc = __('Erlaubt das Angeben einer Lieferadresse, unabhängig von der Rechnungsadresse.', 'wpsg');
						
		} // public function __construct()
		 
		/**
		 * zeigt das Formular zu den Einstellungen des Moduls im BE an
		 * 
		 */
		public function settings_edit()
		{
									
		} // public function settings_edit()
				
		/**
		 * speichert die Einstellungen zum Modul
		 * 
		 */
		public function settings_save()
		{
		
		} // public function settings_save()
				
		public function order_ajax() {
				
			if (!wpsg_checkInput($_REQUEST['edit_id'], WPSG_SANITIZE_INT)) throw \wpsg\Exception::getSanitizeException();
			
			$r = [
				'edit_id' => intval($_REQUEST['edit_id'])				
			];
			
			parse_str($_REQUEST['form_data'], $form_data);
			
			$shipping_adress_id = intval($this->db->fetchOne("
				SELECT
					`shipping_adress_id`
				FROM
					`".WPSG_TBL_ORDER."`
				WHERE
					`id` = '".wpsg_q(wpsg_sinput("key", $r['edit_id']))."'
			"));
			
			if ($form_data['dialog_delete'] === '1') {
				
				if ($shipping_adress_id > 0) {
				
					$this->db->Query("DELETE FROM `".WPSG_TBL_ADRESS."` WHERE `id` = '".wpsg_q($shipping_adress_id)."' ");
					
				}
				
				$this->db->UpdateQuery(WPSG_TBL_ORDER, ['shipping_adress_id' => ''], " `id` = '".wpsg_q($r['edit_id'])."' ");
				
			} else {
			
				$db_data = [
					'firma' => wpsg_q($form_data['dialog_shipping_firma']),
					'title' => wpsg_q($form_data['dialog_shipping_title']),
					'vname' => wpsg_q($form_data['dialog_shipping_vname']),
					'name' => wpsg_q($form_data['dialog_shipping_name']),
					'strasse' => wpsg_q($form_data['dialog_shipping_strasse']),
					'nr' => wpsg_q($form_data['dialog_shipping_nr']),
					'plz' => wpsg_q($form_data['dialog_shipping_plz']),
					'ort' => wpsg_q($form_data['dialog_shipping_ort']),
					'land' => wpsg_q($form_data['dialog_shipping_land'])
				];
				
				if ($shipping_adress_id > 0) {
					
					$this->db->UpdateQuery(WPSG_TBL_ADRESS, $db_data, " `id` = '".wpsg_q($shipping_adress_id)."' ");
					
				} else {
					
					$shipping_adress_id = $this->db->ImportQuery(WPSG_TBL_ADRESS, $db_data);
					$this->db->UpdateQuery(WPSG_TBL_ORDER, ['shipping_adress_id' => wpsg_q($shipping_adress_id)], " `id` = '".wpsg_q($r['edit_id'])."' ");
					 
				}
				
			}
			
			die("1");
				
		} // public function order_ajax()
		
		/**
		 * fügt dem checkout zusätzliche Felder für eine seperate Lieferdresse ein
		 * 
		 */
		public function checkout_inner_prebutton(&$checkout_view)
		{
			
			$this->shop->view['data'] = $_SESSION['wpsg']['checkout'];
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_shippingadress/checkout_inner_prebutton.phtml');
			
		} // public function checkout_inner_prebutton(&$checkout_view)
		 		
		public function checkCheckout(&$state, &$error, &$arCheckout)  
		{
			
			if (isset($_REQUEST['wpsg_checkout']))
			{
		 
				if (wpsg_getStr($_REQUEST['wpsg']['checkout']['diff_shippingadress']) == '1')
				{
	
					$pflicht = $GLOBALS['wpsg_sc']->loadPflichtFeldDaten();
					
					$arCheckout['diff_shippingadress'] = 1;
					if (($pflicht['firma'] != '2') && ($pflicht['firma'] != '1') && ($arCheckout['shipping_firma'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_firma';
					if (($pflicht['anrede'] != '2') && ($pflicht['anrede'] != '1') && ($arCheckout['shipping_title'] == '-1'))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_title';
					if (($pflicht['plz'] != '2') && ($pflicht['plz'] != '1') && ($arCheckout['shipping_plz'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_plz';
					if (($pflicht['vname'] != '2') && ($pflicht['vname'] != '1') && ($arCheckout['shipping_vname'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_vname';
					if (($pflicht['name'] != '2') && ($pflicht['name'] != '1') && ($arCheckout['shipping_name'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_name';
					if (($pflicht['strasse'] != '2') && ($pflicht['strasse'] != '1') && ($arCheckout['shipping_strasse'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_strasse';
					if (($pflicht['ort'] != '2') && ($pflicht['ort'] != '1') && ($arCheckout['shipping_ort'] == ''))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_ort';
					if (($pflicht['land'] != '2') && ($pflicht['land'] != '1') && (!wpsg_isSizedInt($arCheckout['shipping_land'])))
						$_SESSION['wpsg']['errorFields'][] = 'shipping_land';
					
					if ($pflicht['wpsg_showNr'] === '1') {
						
						if (($pflicht['strasse'] != '2') && ($pflicht['strasse'] != '1') && ($arCheckout['shipping_nr'] == ''))
							$_SESSION['wpsg']['errorFields'][] = 'shipping_nr';
						
					}
					
					if (count($_SESSION['wpsg']['errorFields']) > 0)
					{
						
						$this->shop->addFrontendError(__('Bitte überprüfen Sie die Eingaben bei der Lieferadresse.', 'wpsg'));
						
						$error = true;
						
					}
					
				}
				else
				{
	
					unset($_SESSION['wpsg']['checkout']['shipping_title']); unset($arCheckout['shipping_title']);
					unset($_SESSION['wpsg']['checkout']['shipping_plz']); unset($arCheckout['shipping_plz']);
					unset($_SESSION['wpsg']['checkout']['shipping_vname']); unset($arCheckout['shipping_vname']);
					unset($_SESSION['wpsg']['checkout']['shipping_name']); unset($arCheckout['shipping_name']);				
					unset($_SESSION['wpsg']['checkout']['shipping_strasse']); unset($arCheckout['shipping_strasse']);
					unset($_SESSION['wpsg']['checkout']['shipping_nr']); unset($arCheckout['shipping_nr']);
					unset($_SESSION['wpsg']['checkout']['shipping_ort']); unset($arCheckout['shipping_ort']);
					unset($_SESSION['wpsg']['checkout']['shipping_land']); unset($arCheckout['shipping_land']);
					unset($_SESSION['wpsg']['checkout']['shipping_firma']); unset($arCheckout['shipping_firma']);
					unset($_SESSION['wpsg']['checkout']['shipping_land']); unset($arCheckout['shipping_land']);
					unset($_SESSION['wpsg']['checkout']['diff_shippingadress']); unset($arCheckout['diff_shippingadress']);
									
				}
				
			}

		} // public function checkCheckout(&$state, &$error, &$arCheckout)
				
		public function wpsg_order_view_customerdata(&$order_id) 
		{ 
			
			$order_data = $this->shop->cache->loadOrder($order_id);
			
			if ($this->check_different_shippingadress($order_data['k_id'], $order_id) === false) {

				$this->shop->view['wpsg_mod_shippingadress'] = [];
				
			} else {
				
				$this->shop->view['wpsg_mod_shippingadress'] = $this->db->fetchRow("
					SELECT
						`title` AS shipping_title,
						`name` AS shipping_name,
						`vname` AS shipping_vname,
						`firma` AS shipping_firma,
						`strasse` AS shipping_strasse,
						`nr` AS shipping_nr,
						`plz` AS shipping_plz,
						`ort` AS shipping_ort,
						`land` AS shipping_land
					FROM
						`".WPSG_TBL_ADRESS."`
					WHERE
						`id` = '".wpsg_q($order_data['shipping_adress_id'])."'
				");

				$this->shop->view['wpsg_mod_shippingadress']['oCountry'] = wpsg_country::getInstance($this->shop->view['wpsg_mod_shippingadress']['shipping_land']);
									
			}
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_shippingadress/wpsg_order_view_customerdata.phtml');			
			
		} // public function wpsg_order_view_customerdata(&$order_id)

		// Modulfunktionen
		public function check_different_shippingadress($k_id, $o_id)
		{	
			/* TODO alt
			$arrAdrKunde = $this->db->fetchRow("SELECT 
													`name`, `vname`, `firma`, `strasse`, `plz`, `ort`, `land` 
												FROM
													`".WPSG_TBL_KU."`
												WHERE
													`id` = '".wpsg_q($k_id)."'");
	
			$arrAdrShipping = $this->db->fetchRow("SELECT
													`shipping_name` AS name,
													`shipping_vname` AS vname,
													`shipping_firma` AS firma,
													`shipping_strasse` AS strasse,
													`shipping_plz` AS plz,
													`shipping_ort` AS ort,
													`shipping_land` AS land
												FROM
													`".WPSG_TBL_ORDER."`
												WHERE
													`id` = '".wpsg_q($o_id)."'");

			if (wpsg_isSizedArray($arrAdrShipping))
			{
				$arrDiff = array_diff((array)$arrAdrKunde, $arrAdrShipping);
			}

			if (wpsg_isSizedArray($arrDiff) && implode('', $arrAdrShipping) != "")
			{

				return true;
			}
			else 
			{
				return false;
			}
			*/
			
			$arrAdr = $this->db->fetchRow("SELECT *
											FROM
												`".WPSG_TBL_ORDER."`
											WHERE
												`id` = '".wpsg_q($o_id)."'");

			if ($arrAdr['shipping_adress_id'] == 0) return false;
			if ($arrAdr['shipping_adress_id'] != $arrAdr['adress_id']) return true;
			return false;
			
		} // public function check_different_shippingadress()
		
	}
	
