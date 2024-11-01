<?php

	/**
	 * Hier sollen zukünftig Update Funktionen aufgerufen werden
	 */

	$version_old = $GLOBALS['wpsg_sc']->get_option('wpsg_version_installed', true);
	$version_neu = WPSG_VERSION;

	if ($version_old !== false)
	{
	
		if (version_compare(WPSG_VERSION, '3.0.11') >= 0) // Versionsnummer > 3.0.10
		{
			
			$widget = $GLOBALS['wpsg_sc']->get_option('widget_wpsg_basket_widget');
			$bSave = false;
			
			// Die alten Einstellungen des Widgets übernehmen wenn vorhanden
			$pages_show = $GLOBALS['wpsg_sc']->get_option('wpsg_widget_pages_show');
			
			if ($pages_show !== false && wpsg_isSizedArray($pages_show))
			{
				
				$wpsg_reqpage = (($pages_show['wpsg_reqpage'] == '1')?'1':'0');
				$wpsg_agbpage = (($pages_show['wpsg_agbpage'] == '1')?'1':'0');
			  	$wpsg_wrpage = (($pages_show['wpsg_wrpage'] == '1')?'1':'0');
			  	$wpsg_dspage = (($pages_show['wpsg_dspage'] == '1')?'1':'0');
			  	$wpsg_vkpage = (($pages_show['wpsg_vkpage'] == '1')?'1':'0');
			  	$wpsg_odrpage = (($pages_show['wpsg_odrpage'] == '1')?'1':'0');
			  	$wpsg_imppage = (($pages_show['wpsg_imppage'] == '1')?'1':'0');
			  	 		  	
			  	foreach ((array)$widget as $k => $w)
			  	{
			  		
			  		if (wpsg_isSizedArray($w))
			  		{
			  	 
			  			$widget[$k]['wpsg_reqpage'] = $wpsg_reqpage;
				  		$widget[$k]['wpsg_agbpage'] = $wpsg_agbpage;
				  		$widget[$k]['wpsg_wrpage'] = $wpsg_wrpage;
				  		$widget[$k]['wpsg_dspage'] = $wpsg_dspage;
				  		$widget[$k]['wpsg_vkpage'] = $wpsg_vkpage;
				  		$widget[$k]['wpsg_odrpage'] = $wpsg_odrpage;
				  		$widget[$k]['wpsg_imppage'] = $wpsg_imppage;
				  		
			  		}
			  		
			  	}
			  	
			  	$GLOBALS['wpsg_sc']->update_option('wpsg_widget_pages_show', false);
			  	$bSave = true;
				
			}
			
			// Alter Versandhinweis des Widgets
			$versandhinweis = $GLOBALS['wpsg_sc']->get_option('wpsg_widget_versandhinweis');
			if ($versandhinweis !== false && strlen($versandhinweis) > 0)
			{
				 
				foreach ((array)$widget as $k => $w)
				{
					
					if (wpsg_isSizedArray($w))
					{
					
						$widget[$k]['wpsg_versandhinweis'] = $versandhinweis;
						
					}
					
				}
				
				$GLOBALS['wpsg_sc']->update_option('wpsg_widget_versandhinweis', false);
				$bSave = true;
				 			
			}
			
			if ($bSave)
			{
				 
				$GLOBALS['wpsg_sc']->update_option('widget_wpsg_basket_widget', $widget);
				
			}
			
		}
		
		if ($version_old !== false && preg_match('/\d+\.\d+\.\d+/', $version_old) && version_compare($version_old, '3.2.0') < 0) // Update vor 3.2.0
		{
			
			$shop = $GLOBALS['wpsg_sc'];
			
			$arPersistentBackendError = $shop->get_option('wpsg_persistentBackendError');
			if (!is_array($arPersistentBackendError)) $arPersistentBackendError = array();
			
			$bHinweisTemplate = false;
			
			// Standard2.phtml auf Standard.phtml umstellen
			$arProducts = $GLOBALS['wpsg_db']->fetchAssocField("SELECT P.`id` FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`ptemplate_file` = 'standard2.phtml' OR P.`ptemplate_file` = 'standard_login.phtml' ");
			if (wpsg_isSizedArray($arProducts))
			{
				
				$GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_PRODUCTS, array(
					'ptemplate_file' => 'standard.phtml'
				), "`ptemplate_file` = 'standard2.phtml' OR `ptemplate_file` = 'standard_login.phtml' ");
				
				$bHinweisTemplate = true;
				
			}
			
			$arOptions = $GLOBALS['wpsg_db']->fetchAssocField("SELECT `option_id` FROM `".$GLOBALS['wpsg_sc']->prefix."options` WHERE (`option_value` = 'standard2.phtml' OR `option_value` = 'standard_login.phtml') AND `option_name` LIKE 'wpsg_%' ");
			
			if (wpsg_isSizedArray($arOptions))
			{
				
				$GLOBALS['wpsg_db']->UpdateQuery($GLOBALS['wpsg_sc']->prefix."options", array(
					'option_value' => 'standard.phtml'
				), " (`option_value` = 'standard2.phtml'  OR `option_value` = 'standard_login.phtml') AND `option_name` LIKE 'wpsg_%' ");
				
				$bHinweisTemplate = true;
				
			}
			
			if ($GLOBALS['wpsg_sc']->hasMod('wpsg_mod_productgroups'))
			{
	
				$arProductGroups = $GLOBALS['wpsg_db']->fetchAssocField("SELECT PG.`id` FROM `".WPSG_TBL_PRODUCTS_GROUP."` AS PG WHERE PG.`template_file` = 'standard2.phtml' OR PG.`template_file` = 'standard_login.phtml' ");
				if (wpsg_isSizedArray($arProductGroups))
				{
					
					$GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_PRODUCTS_GROUP, array(
						'template_file' => 'standard.phtml'
					), " `template_file` = 'standard2.phtml' OR `template_file` = 'standard_login.phtml' ");
					
					$bHinweisTemplate = true;
					
				}		
				
			}
			
			if ($bHinweisTemplate)
			{
			
				$arPersistentBackendError['wpsg_update_3.2_0_1'] = array(
					'message' => __('Die Produkttemplates "standard2.phtml" und "standard_login.phtml" wurden mit dem Update auf Version 3.2.0 entfernt. Die Konfiguration wurde automatisch angepasst.<br />Weitere Informationen erhalten Sie <a href="http://wpshopgermany.maennchen1.de/?p=3647">hier</a>.', 'wpsg'),
					'hide' => false
				);
				
			}
			
			$arPersistentBackendError['wpsg_update_3.2_0_2'] = array(
				'message' => __('Das Produkttemplate wurde mit dem Update auf 3.2.0 angepasst, bitte überprüfen Sie die Darstellung in Ihrem Shop.', 'wpsg'),
				'hide' => false
			);
			 
			$shop->update_option('wpsg_persistentBackendError', $arPersistentBackendError);
			
		} 

		if (version_compare($version_old, '3.5') < 0)
		{
			 
			$shop = $GLOBALS['wpsg_sc'];
			
			$arPersistentBackendError = $shop->get_option('wpsg_persistentBackendError');
			if (!is_array($arPersistentBackendError)) $arPersistentBackendError = array();
			
			$arPersistentBackendError['wpsg_update_3.5_3'] = array(
				'message' => wpsg_translate(
					__('Die Mehrwertsteuersätze sind jetzt an die Länder gekoppelt. <a onclick="return confirm(\'Sind Sie sich sicher?\');" href="#1#">Importieren</a> sie die Standardliste oder überprüfen sie die <a href="#2#">Länderkonfiguration</h2>.', 'wpsg'),
					WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=laender&do=import&noheader=1',
					WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=laender'
				),
				'hide' => false
			);
			 			
			$arPersistentBackendError['wpsg_update_3.5_1'] = array(
				'message' => wpsg_translate(
					__('Bitte überprüfen Sie die Konfiguration der Mehrwertsteuer in den Produkten und der Konfiguration. <a href="#1#">Migrationsassistent starten</a>', 'wpsg'),
					WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&action=migratemwst'
				),
				'hide' => false
			);
						
			$CustomDataColExists = sizeof($GLOBALS['wpsg_db']->fetchAssoc("SHOW COLUMNS FROM `".WPSG_TBL_ORDER."` WHERE `Field` = 'custom_data' "));
			$PVarsColExists = sizeof($GLOBALS['wpsg_db']->fetchAssoc("SHOW COLUMNS FROM `".WPSG_TBL_ORDER."` WHERE `Field` = 'pvars' "));
			$pvars_neu = array();
			
			if ($CustomDataColExists > 0)
			{
			 				
				$arOrder = $GLOBALS['wpsg_db']->fetchAssoc("SELECT O.`id` FROM `".WPSG_TBL_ORDER."` AS O ");
			
				foreach ($arOrder as $o)
				{
					 
					$custom_data = @unserialize($GLOBALS['wpsg_db']->fetchOne("SELECT `custom_data` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($o['id'])."' "));
					if ($PVarsColExists > 0) $pvars = @unserialize($GLOBALS['wpsg_db']->fetchOne("SELECT `pvars` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($o['id'])."' "));
					
					if (wpsg_isSizedArray($custom_data['basket']['produkte']))
					{
 
						// Bestellte Produkte mit Index versehen					
						foreach ($custom_data['basket']['produkte'] as $k => &$v)
						{
							
							if (!isset($v['product_index']))
							{
								
								// Altes Produkt
								$v['product_index'] = $k;
								
								if (isset($v['productkey']))
								{
								
									$GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_ORDERPRODUCT, array(
										"product_index" => wpsg_q($k)
									), " `o_id` = '".wpsg_q($o['id'])."' AND (`productkey` = '".wpsg_q($v['productkey'])."' OR `mod_vp_varkey` = '".wpsg_q($v['productkey'])."') AND `p_id` = '".wpsg_q($v['id'])."' ");
	
								}
								else
								{
									
									$GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_ORDERPRODUCT, array(
										"product_index" => wpsg_q($k)
									), " `o_id` = '".wpsg_q($o['id'])."' AND `p_id` = '".wpsg_q($v['id'])."' ");
									
								}
								
								if ($PVarsColExists > 0 && wpsg_isSizedArray($v['wpsg_mod_productvars'])) $pvars_neu[$k] = $v['wpsg_mod_productvars'];
									
							}
							
						}
						
						if (wpsg_isSizedArray($custom_data)) $GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_ORDER, array('custom_data' => wpsg_q(serialize($custom_data))), " `id` = '".wpsg_q($o['id'])."' ");
						if (wpsg_isSizedArray($pvars_neu)) $GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_ORDER, array('pvars' => wpsg_q(serialize($pvars_neu))), " `id` = '".wpsg_q($o['id'])."' ");		 					
					
					}
					else
					{
						 
						$arOrderProducts = $GLOBALS['wpsg_db']->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($o['id'])."' ORDER BY `id` ASC ");
						
						$i = 0;
						foreach ($arOrderProducts as $op)
						{
							
							$GLOBALS['wpsg_db']->UpdateQuery(WPSG_TBL_ORDERPRODUCT, array(
								'product_index' => wpsg_q($i)
							), " `id` = '".wpsg_q($op['id'])."' ");
							 							
						}
						
					}
			
				}
					
			}
	 
			$shop->update_option('wpsg_persistentBackendError', $arPersistentBackendError);
			
		}
		
	}

?>