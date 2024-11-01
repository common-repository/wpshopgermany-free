<?php

    /**
     * Übernimmt die Ausgabe im Backend
     */
    function wpsg_dispatch()
    {
    
        if ($_REQUEST['page'] == 'wpsg-Admin')
        {
    
            // AdminController übernimmt
            $AC = new wpsg_AdminController();
            $AC->dispatch();
    
        }
        else if ($_REQUEST['page'] == 'wpsg-Produkt')
        {
    
            // ProduktController übernimmt
            $PC = new wpsg_ProduktController();
            $PC->dispatch();
    
        }
        else if ($_REQUEST['page'] == 'wpsg-Order')
        {
    
            // Bestellverwaltung übernimmt
            $OC = new wpsg_OrderController();
            $OC->dispatch();
    
        }
    
    } // function wpsg_dispatch()
    
    function wpsg_admin_notices()
    {
    
        $wpsg_update_data = wpsg_get_update_data();
    
        if (is_object($wpsg_update_data['updateData']))
        {
    
            if (property_exists($wpsg_update_data['updateData'], 'banner'))
            {
    
                $arBanner = $wpsg_update_data['updateData']->banner;
    
                if (wpsg_isSizedArray($arBanner))
                {
    
                    foreach ($arBanner as $b)
                    {
    
                        echo '<div class="wpsg_banner '.$b['class'].'">';
                        echo $b['content'];
                        echo '</div>';
    
                    }
    
                }
    
            }
    
        }
    
        echo $GLOBALS['wpsg_sc']->writeBackendMessage();
    
    }
    
    /**
     * Wird in der Wordpress Pluginverwaltung nach dem Plugin aufgerufen
     */
    function wpsg_after_plugin_row($pluginfile, $plugindata, $context)
    {

        if ($plugindata['Name'] == 'wpShopGermany')
        {

            //if ($GLOBALS['wpsg_sc']->isMultiBlog()) echo 'multi';
            //if (strpos($_SERVER['REQUEST_URI'], 'network') !== false) echo 'network';
            //if ($GLOBALS['wpsg_sc']->bLicence === true) echo 'licence';    
			
            if ($GLOBALS['wpsg_sc']->bLicence === true)
            {
                
                if (($GLOBALS['wpsg_sc']->isMultiBlog() && (strpos($_SERVER['REQUEST_URI'], 'network') !== false)) || !$GLOBALS['wpsg_sc']->isMultiBlog())
                {
                  
                    // Lizenzschlüssel wurde eingegeben
    
                    echo '<tr class="plugin-update-trr '.((is_plugin_active(WPSG_FOLDERNAME.'/wpshopgermany.php'))?'active':'').'" id="wpsg-licence"><td colspan="4">';
                    
                    echo '<span id="wpsg_lizenz_link">';
                    
                    $wpsg_update_data = wpsg_get_update_data();
                    
                    echo '<div style="height:10px; width:40px; float:left;"></div>';
                    
                    if (isset($wpsg_update_data['licence_model']) && $wpsg_update_data['licence_model'] == 'pro')
                    {
                    
                        echo '[ Pro Version ] ';
                    
                    }
                    else if (isset($wpsg_update_data['licence_model']) && $wpsg_update_data['licence_model'] == 'enterprise')
                    {
                    
                        echo '[ Enterprise Version ] ';
                    
                    }
                    
                    echo '<a style="" onclick="jQuery(\'#wpsg_lizenz\').show(100); jQuery(\'#wpsg_licence_file\').focus(); jQuery(\'#wpsg_lizenz_link\').hide(); return false;" href="">'.__('Neue Lizenz aktivieren', 'wpsg').'</a>&nbsp;|&nbsp;';
                    echo '<a style="" onclick="return confirm(\''.__('Sind Sie sich sicher?', 'wpsg').'\');" href="'.WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&wpsg_removelicence_submit=1">'.__('Lizenz löschen', 'wpsg').'</a>';
                    echo '</span>';
                    
                    echo '<div id="wpsg_lizenz" style="display:none;"><div style="display:flex; align-items:center;">';
                    echo '<strong style="margin-left:40px;">'.__('Lizenzcode: ', 'wpsg').'</strong>';
                    echo '<input type="text" style="margin:0px 10px;" id="wpsg_licence_file" name="wpsg_licence_file" />';
                    echo '<input type="submit" style="margin:0px 10px;" class="button" value="'.__('Code prüfen', 'wpsg').'" id="wpsg_insertlicence_submit" name="wpsg_insertlicence_submit" />';
                    echo '<input type="hidden" name="" id="wpsg_insertlicence_hidden" value="1" />';
                    echo '</div></div>';
                    
                    echo '<script type="text/javascript"> ';
                    
                    echo 'jQuery("input[name=\'wpsg_insertlicence_submit\']").on("click", function() { jQuery(this).closest("tr").prev().find("input[type=\'checkbox\']").prop("checked", true); } ); ';
                    echo 'jQuery("input[name=\'wpsg_licence_file\']").on("keydown", function(event) { if(event.which == 10 || event.which == 13) { jQuery("#wpsg_insertlicence_hidden").attr("name", "wpsg_insertlicence_submit"); jQuery("#wpsg_insertlicence_submit").click(); }  } ); ';
                    echo 'jQuery("#the-list").parent().parent().attr("enctype", "multipart/form-data"); ';
                    
                    echo 'jQuery(document).ready(function() { ';
                    echo 'if (jQuery("#wpshopgermany-update").length > 0) { jQuery("#wpsg-licence").insertAfter(jQuery("#wpshopgermany-update")); } ';
                    echo '} ); ';
                    
                    echo '</script>';
                    
                    echo '</td></tr>';
                    
                }
                
            }
            else
            {

                if (($GLOBALS['wpsg_sc']->isMultiBlog() && (strpos($_SERVER['REQUEST_URI'], 'network') !== false)) || !$GLOBALS['wpsg_sc']->isMultiBlog()) {
                    
                    echo '<tr class="plugin-update-trr '.((is_plugin_active(WPSG_FOLDERNAME.'/wpshopgermany.php'))?'active':'').'" id="wpsg-licence"><td colspan="4">';
                    echo '<div style="margin-left:40px; line-height:20px;">';
                    echo wpsg_translate(
                    __('<span style="color:red; font-weight:bold;">wpShopGermany Vollversion erwerben, Updates erhalten und weitere Funktionen freischalten.</span><br />', 'wpsg')
                    );
                    echo '</div>';
                    
                    echo '<div style="margin-left:40px; line-height:20px;">';
                    echo wpsg_translate(
                    __('[ <a href="#" onclick="jQuery(\'#wpsg_lizenz\').show(100); jQuery(\'#wpsg_licence_file\').focus(); jQuery(\'#wpsg_lizenz_link\').hide(); return false;">Lizenzcode eingeben</a> ] [ <a target="_blank" href="#1#">Vollversion kaufen</a> ] [ <a target="_blank" href="#2#">Weitere Infos</a> ]', 'wpsg'),
                    'https://shop.maennchen1.de/produkt/wpshopgermany4-lizenzkey/',
                    'https://wpshopgermany.maennchen1.de/'
                    );
                    echo '</div>';
                    echo '<div id="wpsg_lizenz" style="display:none;"><br /><div style="display:flex; align-items:center;">';
                    echo '<strong style="margin-left:40px;">'.__('Lizenzcode: ', 'wpsg').'</strong>';
                    echo '<input type="text" style="margin:0px 10px;" id="wpsg_licence_file" name="wpsg_licence_file" />';
                    echo '<input type="submit" style="margin:0px 10px;" id="wpsg_insertlicence_submit" class="button" value="'.__('Code prüfen', 'wpsg').'" name="wpsg_insertlicence_submit" />';
                    echo '<input type="hidden" name="" id="wpsg_insertlicence_hidden" value="1" />';
                    echo '</div></div>';
                    
                    echo '<script type="text/javascript"> ';
                    
                    echo 'jQuery("input[name=\'wpsg_insertlicence_submit\']").on("click", function() { jQuery(this).closest("tr").prev().find("input[type=\'checkbox\']").prop("checked", true); } ); ';
                    echo 'jQuery("input[name=\'wpsg_licence_file\']").on("keydown", function(event) { if(event.which == 10 || event.which == 13) { jQuery("#wpsg_insertlicence_hidden").attr("name", "wpsg_insertlicence_submit"); jQuery("#wpsg_insertlicence_submit").click(); }  } ); ';
                    echo 'jQuery("#the-list").parent().parent().attr("enctype", "multipart/form-data"); ';
                    
                    echo 'jQuery(document).ready(function() { ';
                    echo 'if (jQuery("#wpshopgermany-update").length > 0) { jQuery("#wpsg-licence").insertAfter(jQuery("#wpshopgermany-update")); } ';
                    echo '} ); ';
                    
                    echo '</script>';
                    
                    echo '</td></tr>';
                    
                }
    
            }
    
        }
    
    } // function wpsg_after_plugin_row_notactivated($pluginfile, $plugindata, $context)
    
    /**
     * Fügt die Menüpunkte zum Backend hinzu
     */
    function wpsg_add_pages()
    {
    
        global $userdata, $wpdb, $wp_roles;
    
        /*
         * Sicherheitshalber die Rechte für den Admin immer setzen
         */
    
        $default_page = '';
        $wp_user = wp_get_current_user();
        
        $arAccessPage = [
        	'wpsg_conf' => 'wpsg-Admin', 
			'wpsg_produkt' => 'wpsg-Produkt', 
			'wpsg_order' => 'wpsg-Order',
			'wpsg_kundenverwaltung' => 'wpsg-Customer',
			'wpsg_abo' => 'wpsg-Abo',			
			'wpsg_voucher' => 'wpsg-Voucher',
			'wpsg_statistics' => 'wpsg-Statistics',
			'wpsg_customergroup' => 'wpsg-Customergroup',			
			'manage_options' => 'M1-Konverter'
		];
        
        foreach ($arAccessPage as $a => $page) {
	  
			if (user_can(wp_get_current_user(), $a) || in_array('administrator', $wp_user->roles)) {
		
				$default_page = $page; break;
		
			} 
        	
		}
		  
        add_menu_page('wpShopGermany', 'wpShopGermany', 'wpsg_menu', $default_page, 'wpsg_dispatch', "dashicons-cart", 9);
    
        add_submenu_page($default_page, __("Konfiguration", "wpsg"), __("Konfiguration", "wpsg"), 'wpsg_conf', 'wpsg-Admin', 'wpsg_dispatch');
        add_submenu_page($default_page, __("Produktverwaltung", "wpsg"), __("Produktverwaltung", "wpsg"), 'wpsg_produkt', 'wpsg-Produkt', 'wpsg_dispatch');
        add_submenu_page($default_page, __("Bestellverwaltung", "wpsg"), __("Bestellverwaltung", "wpsg"), 'wpsg_order', 'wpsg-Order', 'wpsg_dispatch');
    
        $GLOBALS['wpsg_sc']->callMods('wpsg_add_pages', [$default_page]);
    
    } // function wpsg_add_pages()
    
    /**
     * Wird nach dem Seitenaufbau von Wordpress aufgerufen
     */
    function wpsg_shutdown()
    {
    
        if ($GLOBALS['wpsg_sc']->bMessageOut === true) $GLOBALS['wpsg_sc']->clearMessages();
    
    } // function wpsg_shutdown()
    
    function wpsg_phpmailer_init($phpmailer)
    {
    
        if ($GLOBALS['wpsg_sc']->get_option('wpsg_htmlmail') === '1')
        {
    
            $phpmailer->AltBody = $GLOBALS['wpsg_sc']->text_message;
    
        }
    
    }
    
    function wpsg_mail_content_type()
    {
    
        if ($GLOBALS['wpsg_sc']->get_option('wpsg_htmlmail') === '1')
        {
    
            return 'multipart/alternative';
    
        }
    
        return 'text/plain';
    
    }
    
    /**
     * Wird beim Installieren des Shops von Wordpress aus aufgerufen
     */
    function wpsg_install($version = false)
    {
    
        include WPSG_PATH.'/lib/install.php';
    
        $GLOBALS['wpsg_sc']->install();
        
        // Converter aktivieren
        if ($GLOBALS['wpsg_sc']->get_option('wpsg_key', true) !== false && $GLOBALS['wpsg_sc']->get_option('wpsg_mod_converter_done') != 1)
        {
    
            if (!class_exists('Crypt_RSA'))
            {
    
                $bOK = @set_include_path(WPSG_PATH.'/lib/phpseclib0.3.0/'.PATH_SEPARATOR.get_include_path());
    
                require_once(WPSG_PATH.'/lib/phpseclib0.3.0/Crypt/RSA.php');
    
            }
    
            $rsa = new Crypt_RSA();
            $rsa->loadKey(file_get_contents(WPSG_PATH.'/lib/.htpublic'));
    
            $arKey = @unserialize($rsa->decrypt(base64_decode($GLOBALS['wpsg_sc']->get_option('wpsg_key', true))));
    
            if (is_array($arKey) && $arKey['anwendung'] == 'wpShopGermany')
            {
    
                // Modul aktivieren
                $GLOBALS['wpsg_sc']->update_option('wpsg_mod_converter', time());
    
            }
    
        }
     
        if ($GLOBALS['wpsg_sc']->get_option('wpsg_installed', true) === false && $GLOBALS['wpsg_sc']->get_option('wpsg_version_installed', true) === false)
        {
    
            $GLOBALS['wpsg_sc']->firstInstall();
    
        }
    
        if ($version === false)
            $GLOBALS['wpsg_sc']->update_option('wpsg_version_installed', WPSG_VERSION, true);
        else
            $GLOBALS['wpsg_sc']->update_option('wpsg_version_installed', $version, true);
    
    } // function wpshopgermany_install()
    
    /**
     * Wird beim DeInstallieren des Shops von Wordpress aus aufgerufen
     */
    function wpsg_uninstall()
    {
    
    } // function wpshopgermany_install()
    
    function wpsg_info($data, $action = null, $args = null)
    {
    
        if ($action != 'plugin_information' || empty($args->slug) || $args->slug != 'wpshopgermany-free')
        {
    
            return $data;
    
        }
        else
        {
    
            $wpsg_update_data = wpsg_get_update_data();
    
            if (is_object($wpsg_update_data['updateData'])) return $wpsg_update_data['updateData'];
            else return new \stdClass();
    
        }
    
    }
    
    function wpsg_admin_bar_menu()
    {
    
        global $wp_admin_bar, $wpdb;
    
        if (!is_super_admin() || !is_admin_bar_showing())  { return;
    
            $wp_admin_bar->add_menu(array('id' => 'wpsg', 'title' => __('wpShopGermany Debug', 'wpsg'), 'href' => home_url().'?wpsg_debug=1'));
    
        } else {
    
            // add wpShopGermany to the WP Toolbar
            $args = array(
                'id' => 'wpshopgermany',
                'title' => 'wpShopGermany',
                'href' => "/wp-admin/admin.php?page=wpsg-Admin",
                'meta' => array(
                    'class' => 'wpsg_adminbar_link',
                    'title' => 'wpShopGermany'
                )
            );
    
            $wp_admin_bar->add_node($args);
    
        }
    }
    
    function wpsg_updateNotification($currentPluginMetadata, $newPluginMetadata)
    {
    
        if (isset($newPluginMetadata->upgrade_notice) && strlen(trim($newPluginMetadata->upgrade_notice)) > 0)
        {
    
            echo '<span style="display:block; background-color:#d54e21; padding:10px; color:#f9f9f9; margin-top:10px;"><strong>'.__('Wichtiger Update Hinweis', 'wpsg').': </strong>'.$newPluginMetadata->upgrade_notice.'</span>';
    
        }
    
    }
    
    function wpsg_api_call($func, $arParam = array(), $key = false)
    {
    
        //wpsg_debug_console('wpsgApiCall: '.$func);
        //wpsg_debug('wpsgApiCall: '.$func);
    
        $plugin_data = get_plugin_data(dirname(__FILE__).'/../wpshopgermany.php');
    
        if ($key === false) $key = $GLOBALS['wpsg_sc']->get_option('wpsg_key', true);
    
        $api_return = $GLOBALS['wpsg_sc']->get_url_post_content('https://api.maennchen1.de/', array(
            'f' => $func,
            'app' => 'wpsg',
            'key' => $key,
            'version' => $plugin_data['Version'],
            'host' => $_SERVER['HTTP_HOST'],
            'data' => $arParam
        ));
    
        //wpsg_debug('wpsgApiReturn: '.$func.":".$api_return);die();
    
        $api_return_json = json_decode($api_return, true);
    
        //wpsg_debug_console($api_return_json);
    
        if (wpsg_isSizedArray($api_return_json))
        {
    
            // In der Antwort waren gleich aktualisierte Daten enthalten
            if (isset($api_return_json['wpsg_update_data']['updateData']))
            {
    
                $api_return_json['wpsg_update_data']['updateData'] = (object)$api_return_json['wpsg_update_data']['updateData'];
    
                $GLOBALS['wpsg_sc']->update_option('wpsg_lastupdate', strval(time()), true);
                $GLOBALS['wpsg_sc']->update_option('wpsg_updatedata', $api_return_json['wpsg_update_data'], true);
    
                $GLOBALS['wpsg_sc']->update_option('wp_installed', (($api_return_json['wpsg_update_data']['returnCode']  != '0')?'1':'0'));
    
            }
    
            return $api_return_json;
    
        }
        else
        {
    
            //wpsg_debug('wpsgApiReturn: '.$func.":".$api_return);
            return false;
    
        }
    
    }
    
    function wpsg_get_update_data($key = false, $force = false)
    {
    
        if ($key === false) $key = $GLOBALS['wpsg_sc']->get_option('wpsg_key', true);
    
        $wpsg_lastupdate = $GLOBALS['wpsg_sc']->get_option('wpsg_lastupdate', true);
        $wpsg_update_data = $GLOBALS['wpsg_sc']->get_option('wpsg_updatedata', true);
    
        if ($force === true) $wpsg_lastupdate = false;
    
        $min = 15;
        //a$min = 0.2;

        if ($wpsg_lastupdate == false || $wpsg_lastupdate < time() - 60 * $min || !wpsg_isSizedArray($wpsg_update_data))
        {
    
            try
            {
    
                $wpsg_update_data_return = wpsg_api_call('checkUpdate', array(), $key);

                if (wpsg_isSizedArray($wpsg_update_data_return) && isset($wpsg_update_data_return['returnCode']))
                {
    
                    $wpsg_update_data = $wpsg_update_data_return;
                    $wpsg_update_data['updateData'] = (object)$wpsg_update_data['updateData'];
    
                    $GLOBALS['wpsg_sc']->update_option('wpsg_lastupdate', strval(time()), true, false, WPSG_SANITIZE_NONE);
                    $GLOBALS['wpsg_sc']->update_option('wpsg_updatedata', $wpsg_update_data, true, false, WPSG_SANITIZE_NONE);
    
                    $GLOBALS['wpsg_sc']->update_option('wp_installed', (($wpsg_update_data['returnCode']  != '0')?'1':'0'));
    
                }
    
            }
            catch (\Exception $e)
            {
    
                $GLOBALS['wpsg_sc']->addBackendError(wpsg_translate(__('Fehler: #1#', 'wpsg'), $e->getMessage()));
    
            }
    
        }

        return $wpsg_update_data;
    
    }
    
    function wpsg_update($data) {
	
    	// Unklar warum das passiert aber es ist bei Kunden aufgetreten
		if (!is_object($data)) return $data;	
		if (!isset($data->response) || !is_array($data->response)) $data->response = array();
    	
        $wpsg_update_data = wpsg_get_update_data();

        if (is_object($wpsg_update_data['updateData']) && property_exists($wpsg_update_data['updateData'], 'noUpdate')) $noUpdate = $wpsg_update_data['updateData']->noUpdate; else $noUpdate = false;
        
        if (is_object($wpsg_update_data['updateData']) && $noUpdate !== true) $data->response[WPSG_SLUG] = $wpsg_update_data['updateData'];
        else if (is_object($data)) unset($data->response[WPSG_SLUG]);
    
        return $data;
      
    }
    
    function wpsg_admin_footer() {
    	
        if (is_admin() && preg_match('/wpsg/', wpsg_getStr($_REQUEST['page']))) {

        	$content = '';
            $sanitization_err_code = "";
	 
			if (wpsg_isSizedArray($_SESSION['sanitization_err_fields'])) {
				
				//ob_start(); wpsg_debug($_SESSION['sanitization_err_fields']); $content .= '<div style="position:fixed; background-color:lightgrey; z-index:10000; left:50%; top:50%; width:500px; height:500px; overflow:scroll; margin-left:-250px; margin-top:-250px;">'.ob_get_contents().'</div>'; ob_end_clean();
				
	        	foreach($_SESSION['sanitization_err_fields'] as $field_name => $nCalls) {

		        	$_SESSION['sanitization_err_fields'][$field_name] ++;
					
		        	if ($_SESSION['sanitization_err_fields'][$field_name] >= 1) unset($_SESSION['sanitization_err_fields'][$field_name]);

		        	$sanitization_err_code .= "document.getElementsByName('$field_name').forEach(el => { 
		        	
		        		if ((' ' + el.className + ' ').replace(/[\\n\\t]/g, ' ').indexOf(' wp-editor-area ') > -1 ) {
		        		
		        			el.parentNode.style.borderColor = '#D9534F';
		        		
		        		} else { el.style.borderColor = '#D9534F'; } 
		        	
		        	});\n";
					
		        }
		        
	        }
			
            $content .= '
                    <script>
                            
                        jQuery(document).ready(function() {
                        
                        	'.$sanitization_err_code.'
                        	
                            jQuery.datepicker.setDefaults(jQuery.datepicker.regional["de"]);
                            jQuery(".wpsg-datepicker").datepicker({
                                showOn: "both",
                                buttonImage: "images/date-button.gif",
                                buttonImageOnly: true
                            });
                        
                        } );
                        
                    </script>
                ';
    
            echo $content;
    
        }
    
    }
    
    function wpsg_admin_init()
    {

        if (isset($_REQUEST['wpsg_insertlicence_submit']))
        {
    
            $AC = new wpsg_AdminController();
            $AC->loadLicenceAction();
    
        }
        else if (isset($_REQUEST['wpsg_removelicence_submit']))
        {
    
            if (!current_user_can('edit_plugins')) die();
    
            $GLOBALS['wpsg_sc']->update_option('wpsg_key', false, true);
            $GLOBALS['wpsg_sc']->update_option('wpsg_lastupdate', false, true);
            $GLOBALS['wpsg_sc']->update_option('wpsg_updatedata', false, true);
    
            $wpsg_update_data = wpsg_get_update_data(false, true);
    
            $GLOBALS['wpsg_sc']->addBackendMessage(__('Lizenz wurde entfernt.', 'wpsg'));
            $GLOBALS['wpsg_sc']->redirect(WPSG_URL_WP.'wp-admin/plugins.php');
    
        }
    
        add_action('after_plugin_row', 'wpsg_after_plugin_row', 10, 3);
    
        //wp_enqueue_style('wp-jquery-ui-dialog');		
        //wp_enqueue_style('wpsg-adminstyle', $GLOBALS['wpsg_sc']->getRessourceURL('css/admin.css'));
    
        // Produktauswahl im RTE
        add_filter('mce_buttons', 'wpsg_tinymce_button');
        add_filter('mce_external_plugins', 'wpsg_tinymce_plugin');
    
        $GLOBALS['wpsg_sc']->checkGeneralBackendError();
    
        $GLOBALS['wpsg_sc']->callMods('wpsg_admin_init');
    
    } // function wpsg_admin_init()
    
    function wpsg_tinymce_button($buttons)
    {
    
        array_push($buttons, '|', 'wpsg');
    
        $GLOBALS['wpsg_sc']->callMods('tinymce_button', array(&$buttons));
    
        return $buttons;
    
    } // function wpsg_tinymce_button($buttons)
    
    function wpsg_tinymce_plugin($plugin_array)
    {
    
        if (wpsg_getStr($_REQUEST['page']) != 'wpsg-Produkt')
        {
    
            $GLOBALS['wpsg_sc']->callMods('tinymce_plugin', array(&$plugin_array));
    
        }
    
        $plugin_array['wpsg'] = WPSG_URL.'/views/js/mce_plugin.js';
    
        return $plugin_array;
    
    } // function wpsg_tinymce_plugin($plugin_array) 
    
    function wpsg_get_permalink($post_id)
    {
    
        if ($GLOBALS['wpsg_sc']->isMultiBlog() && $GLOBALS['wpsg_sc']->get_option('wpsg_multiblog_standalone', true) != '1')
        {
    
            switch_to_blog(1);
            $return = get_permalink($post_id);
            restore_current_blog();
    
        }
        else
        {
    
            $return = get_permalink($post_id);
    
        }
    
        return $return;
    
    } // function wpsg_get_permalink($post_id)
    
    /**
     * Diese Funktion ermöglicht es, Ausgaben im Head eines Themes unterzubringen
     */
    function wpsg_head()
    {
    
        $GLOBALS['wpsg_sc']->wp_head();
    
    } // function wpsg_head()
    
    /**
     * Funktion, die vor dem Update aufgerufen wird um die Dateien aus dem Verzeichnis zu schieben die nicht gelöscht werden sollen
     */
    function wpsg_pre_install($test)
    {
    
        global $wp_filesystem;
    
        if ($_REQUEST['plugin'] != WPSG_FOLDERNAME.'/wpshopgermany.php') return;
    
        if (!isset($_REQUEST['_ajax_nonce'])) echo __('Kopiere Shop aus dem Pluginverzeichnis ...', 'wpsg').'<br />';
    
        // Temporäres Verzeichnis 
        if ($GLOBALS['wpsg_sc']->isMultiBlog())
            $path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/update_temp/';
        else
            $path = WP_CONTENT_DIR.'/uploads/wpsg_temp/update_temp/';
    
        if (!file_exists($path)) wpsg_mkdir($path);
        //if (!file_exists($path)) die(__('Fehler beim Anlegen des Backupverzeichnisses.', 'wpsg'));
    
        // Translation rauskopieren
        if (file_exists(WPSG_PATH.'/lib/translation.phtml'))
        {
    
            if (!file_exists($path.'/lib/')) wpsg_mkdir($path.'/lib/');
            //if (!file_exists($path.'/lib/')) die(__('Fehler beim Anlegen des Backupverzeichnisses.', 'wpsg'));
    
            wpsg_copy(WPSG_PATH.'/lib/translation.phtml', $path.'/lib/translation.phtml');
            //if (!file_exists($path.'/lib/translation.phtml')) die(__('Übersetzungsdatei wurde nicht kopiert.', 'wpsg'));
    
        }
    
        // Übersetzungsdatei rauskopieren
        if (file_exists(WPSG_PATH.'/lang/'))
        {
    
            @wpsg_mkdir($path.'/lang/', 0777, true);
            wpsg_copy(WPSG_PATH.'/lang/', $path.'/lang/');
    
            //if (!file_exists($path.'/lang/')) die(__('Übersetzung konnte nicht kopiert werden.', 'wpsg'));
    
        }
    
        // User_views kopieren
        if (file_exists(WPSG_PATH.'/user_views/'))
        {
    
            @wpsg_mkdir($path.'/user_views/', 0777, true);
            wpsg_copy(WPSG_PATH.'/user_views/', $path.'/user_views');
    
            //if (!file_exists($path.'/user_views/')) die(__('Userviews wurden nicht kopiert.', 'wpsg'));    
    
        }
    
    } // function wpsg_pre_install()
    
    /**
     * Funktion, die nach dem Update aufgerufen wird um die Dateien wieder an die richtige Stelle zu schieben
     */
    function wpsg_post_install()
    {
    
        global $wp_filesystem;
    
        $wpsg_update_data = wpsg_get_update_data(false, true);
    
        if ($_REQUEST['plugin'] != WPSG_FOLDERNAME.'/wpshopgermany.php') return;
    
        if (!isset($_REQUEST['_ajax_nonce'])) echo __('Kopiere Shop zurück ...', 'wpsg').'<br />';
    
        // Temporäres Verzeichnis 
        if ($GLOBALS['wpsg_sc']->isMultiBlog())
            $path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/wpsg_temp/update_temp/';
        else
            $path = WP_CONTENT_DIR.'/uploads/wpsg_temp/update_temp/';
    
        //if (file_exists($path.'/lib/translation.phtml')) copy($path.'/lib/translation.phtml', WPSG_PATH.'/lib/translation.phtml');
        //if (file_exists($path.'/user_views/')) wpsg_copy($path.'/user_views/', WPSG_PATH.'/user_views/');	
        //if (file_exists($path.'/lang/')) wpsg_copy($path.'/lang/', WPSG_PATH.'/lang/');
    
        if (file_exists($path.'/lib/translation.phtml'))
        {
    
            $source_file = $path.'lib/translation.phtml';
            $target_file = WPSG_PATH.'lib/translation.phtml';
    
            wpsg_copy($source_file, $target_file);
    
        }
    
        if (file_exists($path.'/user_views/'))
        {
    
            $source_file = $path.'user_views';
            $target_file = WPSG_PATH.'user_views';
    
            wpsg_copy($source_file, $target_file);
    
        }
    
        if (file_exists($path.'/lang'))
        {
    
            $source_file = $path.'lang';
            $target_file = WPSG_PATH.'lang';
    
            wpsg_copy($source_file, $target_file);
    
        }
    
        $AC = new wpsg_AdminController();
    
        // Module aus Lizenz nachinstallieren
        foreach ($wpsg_update_data['modulinfo'] as $modul_key => $modul_info)
        {
    
            if ($modul_info['active'] === true || $modul_info['demo_active'] === true)
            {
    
                $AC->installModul($modul_key);
    
            }
    
        }
    
        // Aufräumen
        //if (file_exists($path)) wpsg_rrmdir($path);
    
        // Datenbank aktualisieren
        if (!isset($_REQUEST['_ajax_nonce'])) echo '<br />'.__('Aktualisiere Shop Datenbank ...', 'wpsg').'<br />';
    
        // Einmalige Meldungen zurücksetzen
        $GLOBALS['wpsg_sc']->db->Query("DELETE FROM `".$GLOBALS['wpsg_sc']->prefix."options` WHERE `option_name` LIKE 'wpsg_message_%' ");
    
        $GLOBALS['wpsg_sc']->clearMessages();
        $GLOBALS['wpsg_sc']->loadModule();
    
        wpsg_install($wpsg_update_data['updateData']->new_version);
    
    } // function wpsg_post_install()

?>