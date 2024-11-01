<?php

	/**
	 * Dieses Modul erlaubt dynamische Zahlungsvarianten
	 * @author daniel
	 */
	class wpsg_mod_userpayment extends wpsg_mod_basic 
	{
		
		var $lizenz = 2;
		var $id = 10;
		var $inline = true;
		
		/**
		 * Costructor
		 */
		public function __construct()
		{
			
			parent::__construct();
			
			$this->name = __('Zahlvarianten', 'wpsg');
			$this->group = __('Zahlungsarten', 'wpsg');
			$this->desc = __('Erlaubt das anlegen von benutzerdefinierten Zahlungsarten.', 'wpsg');
									
		} // public function __construct()
						
		public function install()
		{
			 
			require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');
			
			/**
			 * Tabelle für die Zahlvarianten
			 */ 
			$sql = "CREATE TABLE ".WPSG_TBL_ZV." (
		   		id mediumint(9) NOT NULL AUTO_INCREMENT,
		   		name varchar(255) NOT NULL,
		   		rabgeb varchar(255) NOT NULL,
		   		hint TEXT NOT NULL,		   
				mwst_key VARCHAR(1) NOT NULL,		  
		   		mwst_laender int(1) NOT NULL,
		   		aktiv int(1) NOT NULL,
		   		PRIMARY KEY  (id)
		   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	   	 
   			dbDelta($sql);
			
		} // public function install()
		
		public function settings_edit()
		{
 
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_userpayment/settings_edit.phtml');
			
		} // public function settings_edit()
		
		public function settings_save() {
			
		} // public function settings_save()
		
		public function be_ajax()
		{

			$_REQUEST['zv_id'] = wpsg_sinput("key", $_REQUEST['zv_id']);

			if ($_REQUEST['do'] == 'add')
			{
				
				$new_name = __('Anklicken um den Namen der Zahlvariante zu ändern ...', 'wpsg');
				
				// Versandzone in Datenbank eintragen
				$zv_id = $this->db->ImportQuery(WPSG_TBL_ZV, array(
					'name' => wpsg_q($new_name),
					'aktiv' => '1',
					'mwst_key' => 'c',
					'rabgeb' => 0
				));
				
				$this->shop->addTranslationString('wpsg_mod_userpayment_'.$zv_id, $new_name);
				
				die($this->zv_list());
				
			}
			else if ($_REQUEST['do'] == 'remove')
			{
				
				$this->db->Query("DELETE FROM `".WPSG_TBL_ZV."` WHERE `id` = '".wpsg_q($_REQUEST['zv_id'])."'");
				
				die($this->zv_list());
				
			}
			else if ($_REQUEST['do'] == 'inlinedit')
			{

				if ($_REQUEST['field'] == 'name')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'name' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");
					
					$this->shop->addTranslationString('wpsg_mod_userpayment_'.$_REQUEST['zv_id'], $_REQUEST['value']);
					
					die(wpsg_hspc($_REQUEST['value']));
					
				} 
				else if ($_REQUEST['field'] == 'hint')
				{

					$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'hint' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");
					
					$this->shop->addTranslationString('wpsg_mod_userpayment_hint_'.$_REQUEST['zv_id'], $_REQUEST['value']);
					
					die(wpsg_hspc($_REQUEST['value']));
					
				}
				else if ($_REQUEST['field'] == 'mwst_key')
				{

					$_REQUEST['value'] = wpsg_sinput("key", $_REQUEST['value']);

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'mwst_key' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");
					
					$tax_groups = wpsg_tax_groups();
					die(wpsg_hspc($tax_groups[$_REQUEST['value']]));
					
				}
				else if ($_REQUEST['field'] == 'mwst_laender')
				{

					$_REQUEST['value'] = wpsg_sinput("key", $_REQUEST['value']);

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'mwst_laender' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");

					die();
					
				}
				else if ($_REQUEST['field'] == 'rabgeb')
				{

					$_REQUEST['value'] = wpsg_tf(wpsg_sinput("key", $_REQUEST['value'], "isFloat"));

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'rabgeb' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");
					 
					die(wpsg_ff($_REQUEST['value']));
					
				}
				else if ($_REQUEST['field'] == 'aktiv')
				{

					$_REQUEST['value'] = wpsg_sinput("key", $_REQUEST['value']);

					$this->db->UpdateQuery(WPSG_TBL_ZV, array(
						'aktiv' => wpsg_q($_REQUEST['value'])
					), "`id` = '".wpsg_q($_REQUEST['zv_id'])."'");

					die();
					
				}
				
			}
			
		} // public function be_ajax()
				
		public function addPayment(&$arPayment) { 
			
			$arZV = $this->db->fetchAssoc("
				SELECT * FROM `".WPSG_TBL_ZV."` ORDER BY `name` ASC
			");
				  
			foreach ((array)$arZV as $zv) {
				
				if (!is_admin() && $zv['aktiv'] != '1') continue;
				
				$hint = $zv['hint'];
				
				$arPayment[$this->id.'_'.$zv['id']] = array(
					'id' => $this->id.'_'.$zv['id'],
					'active' => $zv['aktiv'],
					'name' => __($zv['name'], 'wpsg'),
					'hint' => __($hint, 'wpsg'),
					'price' => $zv['rabgeb'],
					'tax_key' => $zv['mwst_key'],
					'mwst_null' => $zv['mwst_laender'],
					'deleted' => wpsg_getInt($zv['deleted'])
				);	
								
			}
			
		} // public function addPayment(&$arPayment)
				
		/* ---- */
				
		public function zv_list()
		{
			
			$this->shop->view['wpsg_mod_userpayment']['data'] = $this->db->fetchAssoc("
				SELECT
					ZA.*
				FROM
					`".WPSG_TBL_ZV."` AS ZA				
			");
			
			$this->shop->render(WPSG_PATH_VIEW.'/mods/mod_userpayment/list.phtml');
			
		} // public function zv_list()
		
	} // class wpsg_mod_userpayment extends mod_basic

?>