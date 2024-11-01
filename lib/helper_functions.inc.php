<?php
	
	/**
     * Escape quotes in java script
     *
     * @param mixed $data
     * @param string $quote
     * @return mixed
     */
    function wpsg_jsquoteescape($data, $quote='\'')
    {
        if (is_array($data)) 
        {
            $result = array();
            
            foreach ($data as $item) 
            {
                
            	$result[] = str_replace($quote, '\\'.$quote, $item);
            	
            }
            
            return $result;
            
        }
        return str_replace($quote, '\\'.$quote, $data);
    }
	
	/**
	 * Wie date beachtet aber die Wordpress Zeitzonen Einstellung
	 */
	function wpsg_date($format, $timestamp = false)
	{
		
		if ($timestamp === false) $timestamp = time();
		
		return date($format, $timestamp + (get_option('gmt_offset') * 3600));
		
	} // function wpsg_date($format, $timestamp = false)

	/**
	 * Funktion zum löschen von Verzeichnissen/Dateien
	 */
	function wpsg_rrmdir($file)
	{
	
		global $wp_filesystem;
			
		// Datei existiert überhaupt nicht und muss daher nicht gelöscht werden
		if (!file_exists($file)) return;
		
		if (is_file($file))
		{
				
			$ok = @unlink($file);
				
			if (!$ok)
			{

				$file = preg_replace('/(.*)'.WPSG_CONTENTDIR_WP.'/', '/'.WPSG_CONTENTDIR_WP, $file);

				return $wp_filesystem->delete($file, true);
	
			}
				
			return true;
				
		}
		else
		{
				
		    $arSubFiles = scandir($file);
            
            foreach ($arSubFiles as $subfile) {
                
                if (!in_array($subfile, ['.', '..'])) {

                    $ok = wpsg_rrmdir($file."/".$subfile);
                    if (!$ok) return false;
                    
                }
                
            }
		    
			$ok = @rmdir($file);
            
			if (!$ok)
			{
	
				$file = preg_replace('/(.*)'.WPSG_CONTENTDIR_WP.'/', '/'.WPSG_CONTENTDIR_WP, $file);
				return $wp_filesystem->delete($file, true);
	
			}
				
		}
	
		return true;
	
	} // function wpsg_rrmdir($file)
	
	function wpsg_chmod($file, $mask = 0777)
	{
		
		global $wp_filesystem;
		
		if (!file_exists($file)) return;
		
		$bOK = chmod($file, $mask);
		
		if ($bOK) return true;
		
		return $wp_filesystem->chmod($wp_filesystem->find_folder($file), $mask);
		
	} // function wpsg_chmod($file, $mask)
	
	/**
	 * Funktion die ein Verzeichnis anlegt. Sollte das Anlegen mittels des Webservers fehlschlagen, so wird wp_filesystem versucht
	 */
	function wpsg_mkdir($path)
	{
	
		global $wp_filesystem;
	
		if (file_exists($path)) return true;
	
		// Versuchen über den Web User anzulegen
		$bOK = @mkdir($path, 0777, true);
		if ($bOK) return true;
	
		// Jetzt wirds knifflig, mit dem wp_filesystem versuchen
		// An das wp_filesystem wird alles ab /wp-content übergeben
		$path_wp = preg_replace('/(.*)'.WPSG_CONTENTDIR_WP.'/', '/'.WPSG_CONTENTDIR_WP, $path);
	
		// Pfad auftrennen, da wp_filesystem leider nicht rekursiv arbeitet
		$arPath = explode('/', $path_wp);
	
		$subPath = '/';
	
		foreach ($arPath as $path_segment)
		{
	
			if ($path_segment != '' && is_object($wp_filesystem))
			{
				 	
				$subPath .= $path_segment.'/';
				$wp_filesystem->mkdir($subPath, 0777);
	
			}
				
		}
	
		if (file_exists($path) && is_dir($path)) return true;
	
		return false;
	
	} // function wpsg_mkdir($path)
	
	/**
	 * Funktion um Dateien/Verzeichnise zu kopieren
	 */
	function wpsg_copy($src, $dst)
	{
	
		global $wp_filesystem;
			
		if (is_file($src))
		{
				
			if (!file_exists(dirname($dst)))
			{
	
				$ok = wpsg_mkdir(dirname($dst));
				if (!$ok) die(__('Verzeichnis konnte nicht angelegt werden.', 'wpsg'));
	
			}
			 
			$source = $src;
			$target = $dst;
			 
			$ok = @copy($source, $target);
			 
			if (!$ok)
			{
	
				// Das Kopieren mit den Rechten des Webservers ist fehlgeschlagen, jetzt noch wp_filesystem versuchen
				$source = trailingslashit($wp_filesystem->find_folder($source));
				$target = trailingslashit($wp_filesystem->find_folder($target));
				
				$source = preg_replace('/(.*)'.WPSG_CONTENTDIR_WP.'/', '/'.WPSG_CONTENTDIR_WP, $source);
				$target = preg_replace('/(.*)'.WPSG_CONTENTDIR_WP.'/', '/'.WPSG_CONTENTDIR_WP, $target);
	
				return $wp_filesystem->copy($source, $target, true, 0777);
	
			}
	
			return true;
				
		}
	
		if (is_dir($src) && !file_exists($dst))
		{
				
			$ok = wpsg_mkdir($dst);
			if (!$ok) die(__('Verzeichnis konnte nicht angelegt werden.', 'wpsg'));
				
		}
	
		$dir_hdle = opendir($src);
	
		while ($file = readdir($dir_hdle))
		{
		  
			if (!in_array($file, array(".", "..")))
			{
				 
				$ok = wpsg_copy($src."/".$file, $dst.'/'.$file);
				if (!$ok) return false;
				 
			}
	
		}
		 
		closedir($dir_hdle);
		 
		return true;
	
	} // function wpsg_copy($source, $target)
	
	/**
	 * Wandelt einen Timestamp aus dem System auf die in Wordpress eingestellte Zeitzone um
	 */
	function wpsg_timestamp($time)
	{
		
		return $time + (get_option('gmt_offset') * 3600);
		
	} // function wpsg_timestamp($time)
	
	/**
	 * Wie Time, beachtet aber die Wordpress Zeitzonen Einstellung
	 */
	function wpsg_time()
	{
		
		return (time() + (get_option('gmt_offset') * 3600));
		
	} // function wpsg_time()
	
	/**
	 * Gibt den Timestamp aus einem REQUEST Array, der von wpsg_drawForm_Date übergeben zurück
	 */
	function wpsg_fieldarray_todate($arDate)
	{

		return mktime($arDate['H'], $arDate['i'], 0, $arDate['m'], $arDate['d'], $arDate['Y']);
		
	} // function wpsg_fieldarray_todate($arData)
	 
	function wpsg_saveEMailConfig($key)
	{
		
		if (isset($_REQUEST['wpsg_'.$key.'_betreff']))
		{
		
			$GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_betreff', $_REQUEST['wpsg_'.$key.'_betreff'], false, false, WPSG_SANITIZE_TEXTFIELD);
			$GLOBALS['wpsg_sc']->addTranslationString('wpsg_'.$key.'_betreff', $_REQUEST['wpsg_'.$key.'_betreff'], WPSG_SANITIZE_TEXTFIELD);
			
		}
		
		if (isset($_REQUEST['wpsg_'.$key.'_absender'])) {
			
			$GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_absender', $_REQUEST['wpsg_'.$key.'_absender'], false, false, WPSG_SANITIZE_EMAILNAME);
			$GLOBALS['wpsg_sc']->addTranslationString('wpsg_'.$key.'_absender', $_REQUEST['wpsg_'.$key.'_absender'], WPSG_SANITIZE_EMAILNAME);
		}

		if (isset($_REQUEST['wpsg_'.$key.'_empfaenger'])) $GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_empfaenger', $_REQUEST['wpsg_'.$key.'_empfaenger'], false, false, WPSG_SANITIZE_EMAILNAME);
		if (isset($_REQUEST['wpsg_'.$key.'_cc'])) $GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_cc', $_REQUEST['wpsg_'.$key.'_cc'], false, false, WPSG_SANITIZE_EMAILNAME);
		if (isset($_REQUEST['wpsg_'.$key.'_bcc'])) $GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_bcc', $_REQUEST['wpsg_'.$key.'_bcc'], false, false, WPSG_SANITIZE_EMAILNAME);
		if (isset($_REQUEST['wpsg_'.$key.'_text'])) {
			
			$GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_text', $_REQUEST['wpsg_'.$key.'_text'], false, false, WPSG_SANITIZE_HTML);
			$GLOBALS['wpsg_sc']->addTranslationString('wpsg_'.$key.'_text', $_REQUEST['wpsg_'.$key.'_text'], WPSG_SANITIZE_HTML);
			
		}
		
		if (isset($_FILES['wpsg_'.$key.'_attachfile']['name']) && file_exists($_FILES['wpsg_'.$key.'_attachfile']['tmp_name']))
		{
		
			/* Alte Datei eventuell löschen */
			if (wpsg_isSizedString($GLOBALS['wpsg_sc']->get_option('wpsg_'.$key.'_attachfile')) && file_exists(wpsg_getUploadDir('wpsg_mailconf').$GLOBALS['wpsg_sc']->get_option('wpsg_'.$key.'_attachfile')))
			{
		
				@unlink(wpsg_getUploadDir('wpsg_mailconf').$GLOBALS['wpsg_sc']->get_option('wpsg_'.$key.'_attachfile'));
		
			}
				
			wpsg_fileUpload('wpsg_mailconf', $_FILES['wpsg_'.$key.'_attachfile']);
				
			$GLOBALS['wpsg_sc']->addBackendMessage(__('Anhang hochgeladen.', 'wpsg'));
			$GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_attachfile', $_FILES['wpsg_'.$key.'_attachfile']['name']);
			
		}
		
		// Anhänge aus Mediathek speichern
		if (isset($_REQUEST['wpsg_'.$key.'_mediaattachment'])) {
		    
		    $GLOBALS['wpsg_sc']->update_option('wpsg_'.$key.'_mediaattachment', $_REQUEST['wpsg_'.$key.'_mediaattachment']);
		    
        }
				
	} // function wpsg_saveEMailConfig($key)

	/**
	 * Rendert ein Feld für die E-Mail Konfiguration
	 *
	 * @param unknown $key
	 * @param string  $strTitle
	 * @param string  $notice
	 *
	 * @param bool    $bTo
	 * @param bool    $bAttachment
	 *
	 * @return false|string
	 */
	function wpsg_drawEMailConfig($key, $strTitle = '', $notice = '', $bTo = false, $bAttachment = false)
	{
		
		$TC = new wpsg_SystemController();
		
		$TC->view['field_key'] = $key;
		$TC->view['field_title'] = $strTitle;
		$TC->view['field_notice'] = $notice;
		$TC->view['field_to'] = $bTo;
		$TC->view['field_attachment'] = $bAttachment;
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/emailconf.phtml', false);
				
	} // function wpsg_drawEMailConfig($key)
	
	function wpsg_drawForm_getID($field_name)
	{
		
		$field_id = $field_name;
		$field_id = preg_replace('/\[|\]/', '', $field_id);
		
		return $field_id;
		
	}
	
	function wpsg_drawForm_Button($field_name, $field_label, $conf)
	{
		
		if (wpsg_isSizedString($conf['id'])) $field_id = $conf['id'];
		else $field_id = $field_name;

		if (wpsg_isSizedString($conf['button_text'])) $button_text = $conf['button_text'];
		else $button_text = $field_name;
		
		if (wpsg_isSizedString($field_label)) $field_label .= ':';
		else $field_label = '&nbsp;';
		
		if (wpsg_isSizedString($conf['button_class'])) $button_class = $conf['button_class'];
		else $button_class = '';
		
		if (wpsg_isSizedString($conf['button_onclick'])) $button_onclick = $conf['button_onclick'];
		else $button_onclick = '';
				
		$strReturn = '
			<div class="wpsg_form_field">
				<div class="wpsg_form_left">
					<label for="'.$field_id.'">'.$field_label.'</label>
				</div>
				<div class="wpsg_form_right">
					<input type="button" class="button '.$button_class.'" onclick="'.$button_onclick.'" value="'.$button_text.'" name="'.$field_name.'" />
				</div>
				<div class="wpsg_clear"></div>
			</div>
		';
		
		return $strReturn;
		
	} // function wpsg_drawForm_Button($field_name, $field_label, $conf)
	
	function wpsg_drawForm_Checkbox($field_name, $field_label, $field_checked, $conf = array())
	{
		
		$TC = new wpsg_SystemController();
		
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;
		$TC->view['field_id'] = wpsg_getStr($conf['id'], wpsg_drawForm_getID($field_name));
		$TC->view['field_checked'] = $field_checked;
		$TC->view['field_config'] = $conf;
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/checkbox.phtml', false);
		 
	} // function wpsg_drawForm_Checkbox($field_name, $field_label, $conf = array())

	function wpsg_drawForm_AdminboxStart($title = false, $wrap_class = '', $arConf = array())
	{
		
		echo '<div class="panel panel-default '.$wrap_class.'" style="'.wpsg_getStr($arConf['style']).'">';

		if (wpsg_isSizedString($title))
		{

			echo '<div class="panel-heading clearfix">';
			echo '<h3 class="panel-title">'.$title.'</h3>';
			echo '</div>';

		}

		echo '<div class="panel-body '.wpsg_getStr($arConf['panel-body-class']).'">';
				
	}
	
	function wpsg_drawForm_AdminboxEnd()
	{
		
		echo '</div>';
		echo '</div>';
	
	}
	
	function wpsg_drawForm_TextStart() 
	{
		
		ob_start();
		
	} // function wpsg_drawForm_TextStart()
	
	function wpsg_drawForm_TextEnd($field_label = '', $field_config = array())
	{
	
		$TC = new wpsg_SystemController();
		
		$TC->view['field_label'] = $field_label;
		$TC->view['field_value'] = ob_get_contents();
		$TC->view['field_config'] = $field_config;
		
		ob_end_clean();

		return $TC->render(WPSG_PATH_VIEW.'admin/form/text.phtml', false);
	
	} // function wpsg_drawForm_TextEnd($field_label)
	
	function wpsg_drawForm_Text($field_label, $field_value, $field_id = false, $conf = array())
	{
		
		$TC = new wpsg_SystemController();
		
		$TC->view['field_label'] = $field_label;
		$TC->view['field_value'] = $field_value;
		$TC->view['field_config'] = $conf;
		$TC->view['field_id'] = $field_id;
		
		if (wpsg_isSizedArray($TC->view['field_config']['inlineEdit_source'])) $TC->view['field_config']['inlineEdit_source'] = wpsg_prepare_for_inlineEdit($TC->view['field_config']['inlineEdit_source']);
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/text.phtml', false);
		
	}
	
	function wpsg_drawForm_SubmitButton($field_label)
	{
		
		$TC = new wpsg_SystemController();
		
		$TC->view['field_label'] = $field_label; 
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/input_submit.phtml', false);
		
	}
	
	function wpsg_drawForm_Input($field_name, $field_label, $field_value, $conf = array())
	{
				
		$TC = new wpsg_SystemController();
		
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;
		$TC->view['field_id'] = wpsg_drawForm_getID($field_name);
		$TC->view['field_value'] = $field_value;
		$TC->view['field_config'] = $conf;
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/input.phtml', false);
		
		/*
		$field_id = $field_name;
		$field_id = preg_replace('/\[|\]/', '', $field_id);
		
		$class_wrap = '';
		$class_div = '';
		$class_p = '';
		$class = '';
		$att = '';
		
		if (isset($conf['datepicker']) && $conf['datepicker'] === true)
		{
			$class .= 'wpsg-datepicker';
		}

		if (isset($conf['readonly']) && $conf['readonly'] === true)
		{
			$att .= ' readonly="readonly" ';
		}
		
		if (isset($conf['disabled']) && $conf['disabled'] === true)
		{
			$att .= ' disabled="disabled" ';
		}
		
		$tabindex = 0;
		if (!wpsg_isSizedString($conf['tabindex']))
		{
			
			wpsg_addSet($GLOBALS['wpsg']['tabindex'], 1);
			$tabindex = $GLOBALS['wpsg']['tabindex'];
			
		}	
		else
		{
			
			$tabindex = $conf['tabindex'];
			
		}
		
		$att .= ' tabindex="'.$tabindex.'" ';
		
		if (isset($conf['class_div']))
		{
			$class_div .= ' '.wpsg_hspc($conf['class_div']).' ';			
		}
		
		if (isset($conf['class_text']))
		{			
			$class_p .= ' '.wpsg_hspc($conf['class_text']).' ';			
		}
				
		if (wpsg_isSizedString($conf['class_wrap'])) $class_wrap = wpsg_hspc($conf['class_wrap']);
		
		if (wpsg_isSizedString($field_label)) $field_label = $field_label.':';	
		else $field_label = '&nbsp;';
		
		$strReturn = '
			<div class="wpsg_form_field '.$class_wrap.'">
				<div class="wpsg_form_left">
					<label for="'.$field_id.'">'.$field_label.'</label>
				</div>
				<div class="'.$class_div.'wpsg_form_right'.((isset($conf['unit']))?' wpsg_form_right_unit':'').'">
		';

		if (isset($conf['text']) && $conf['text'] === true)
		{

			if (isset($conf['nohspc']) && $conf['nohspc'] === true)
			{
				
				$strReturn .= '<p id="'.$field_id.'" class="'.$class_p.'">'.$field_value;
				
			}
			else
			{
			
				$strReturn .= '<p id="'.$field_id.'" class="'.$class_p.'">'.wpsg_hspc($field_value);
				
			}
	
			if (isset($conf['remove']))
			{
			
				$strReturn .= '<a title="'.wpsg_hspc($conf['remove']).'" href="#" class="wpsg_icon wpsg_icon_right wpsg_icon_remove"></a>';
	
			}
			
			if (isset($conf['help']))
			{
			
				$strReturn .= '<a href="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" rel="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" class="wpsg_form_help"></a>';
	
			}
			
			$strReturn .= '</p>';
			
		}
		else
		{
		
			if (isset($conf['pwd']) && $conf['pwd'] === true)
				$strType = 'password';
			else
				$strType = 'text';
			
			if (!isset($conf['nohspc']) || $conf['nohspc'] !== true) $value = wpsg_hspc($value);
			
			$strReturn .= '<input id="'.$field_id.'" type="'.$strType.'" class="text '.$class.'" '.$att.' name="'.$field_name.'" value="'.$field_value.'" />';
			
			if (isset($conf['help']))
			{
			
				$strReturn .= '<a href="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" rel="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" class="wpsg_form_help"></a>';
	
			}
			
			if (isset($conf['remove']))
			{
			
				$strReturn .= '<a title="'.wpsg_hspc($conf['remove']).'" href="#" class="wpsg_icon wpsg_icon_right wpsg_icon_remove"></a>';
	
			}
			
			if (isset($conf['unit']))
			{
				
				$strReturn .= '<p class="wpsg_unit">'.wpsg_hspc($conf['unit']).'</p>';
				
			}
			
			if (isset($conf['hint']))
			{
				
				if (substr($conf['hint'], 0, 7) == 'nohspc_')
					$strReturn .= '<div class="wpsg_clear"></div><p class="wpsg_hinweis">'.substr($conf['hint'], 7).'</p>';
				else
					$strReturn .= '<div class="wpsg_clear"></div><p class="wpsg_hinweis">'.wpsg_hspc($conf['hint']).'</p>';
				
			}
			
		}
		
		$strReturn .= '</div>';
					
		if (!isset($conf['clear_after'])) $strReturn .= '<div class="wpsg_clear"></div>';
				
		$strReturn .= '</div>';
		
		if (isset($conf['clear_after']) && $conf['clear_after'] === true) $strReturn .= '<div class="wpsg_clear"></div>';
		
		return $strReturn;
		*/		
		
	} // function wpsg_drawForm_Input($field_name, $field_label, $field_value, $conf = array())
	
	function wpsg_drawForm_Textarea($field_name, $field_label, $field_value, $conf = array())
	{
		
		$TC = new wpsg_ShopController();
		
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;
		$TC->view['field_id'] = wpsg_drawForm_getID($field_name);
		$TC->view['field_value'] = $field_value;
		$TC->view['field_config'] = $conf;
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/textarea.phtml', false);
		
	} // function wpsg_drawForm_Textarea($field_name, $field_label, $field_value, $conf = array())
	
	function wpsg_drawForm_Upload($field_name, $field_label, $field_value = false, $size = '50', $maxlength = '100000', $conf = array())
	{
		
		$TC = new wpsg_ShopController();
		
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;
		$TC->view['field_id'] = wpsg_drawForm_getID($field_name);
		$TC->view['field_value'] = $field_value;
		$TC->view['field_size'] = $size;
		$TC->view['field_maxLength'] = $maxlength;
		$TC->view['field_config'] = $conf;
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/upload.phtml', false);
		
		/*
		$field_id = $field_name;
		
		$fileType = "";
		
		if (is_array($conf) && count($conf) > 0)
		{
			
			$arConf = $conf;
			
			if (array_key_exists('accept', $arConf))
			{
				$fileType = 'accept="'.$arConf['accept'].'"';
			}
			
		}
		
		$strReturn = '';
		
		$strReturn .= '<div class="wpsg_form_field wpsg_upload_field">';
		
		$strReturn .= '<div class="wpsg_form_left">';
		$strReturn .= '<label for="'.$field_id.'">'.$field_label.':</label>';
		$strReturn .= '</div>';
		
		$strReturn .= '<div class="wpsg_form_right">';
		$strReturn .= '<input id="'.$field_id.'" name="'.$field_name.'" type="file" size="'.$size.'" maxlength="'.$maxlength.'" '.$fileType.' />';
		$strReturn .= '</div>';
		
		$strReturn .= '<div class="wpsg_clear"></div>';
		$strReturn .= '</div>';
		
		return $strReturn;
		*/
		
	}
	
	function wpsg_drawForm_Radio($field_name, $field_label, $field_values, $field_value, $conf = array())
	{
	    
	    
	    
	}
	
	function wpsg_drawForm_Select($field_name, $field_label, $field_values, $field_value, $conf = array())
	{
		
		$TC = new wpsg_SystemController();
		
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;

		if (wpsg_isSizedString($conf['id'])) $TC->view['field_id'] = $conf['id'];
		else $TC->view['field_id'] = wpsg_drawForm_getID($field_name);

		$TC->view['field_value'] = $field_value;
		$TC->view['field_values'] = $field_values;
		$TC->view['field_config'] = $conf;
		$TC->view['atts_select'] = '';

		if (wpsg_isSizedString($conf['onchange']))
		{

			$TC->view['atts_select'] .= ' onchange="'.$conf['onchange'].'" ';

		}

		if (wpsg_isSizedInt($conf['multiple']))
		{
		
			$TC->view['field_name'] .= '[]';
			$TC->view['atts_select'] .= ' size="'.$conf['multiple'].'" multiple=""multiple" ';
			
		}
		
		return $TC->render(WPSG_PATH_VIEW.'admin/form/select.phtml', false);
	
		/*
		if (wpsg_isSizedString($conf['id']))
		{
			
			$field_id = $conf['id'];
			
		}
		else
		{
			
			$field_id = $field_name;
			
		}
		
		$class_select = '';
		
		if (isset($conf['class_select']))
		{
			
			$class_select .= ' '.$conf['class_select'].' ';
			
		}
			
		$select = '';
		
		if (isset($conf['multiple']) && $conf['multiple'] > 0)
		{
			
			$select .= ' multiple="multiple" size="'.intval($conf['multiple']).'" ';
			
			if (strpos($field_name, '[]') === false) $field_name .= '[]';
			
		}
		
		$tabindex = 0;
		if (!wpsg_isSizedString($conf['tabindex']))
		{
				
			wpsg_addSet($GLOBALS['wpsg']['tabindex'], 1);
			$tabindex = $GLOBALS['wpsg']['tabindex'];
				
		}
		else
		{
				
			$tabindex = $conf['tabindex'];
				
		}
		
		$select .= ' tabindex="'.$tabindex.'" ';
		
		if (wpsg_isSizedString($field_label)) $field_label .= ':';
		
		$strReturn = '
			<div class="wpsg_form_field">
				<div class="wpsg_form_left">
					<label for="'.$field_id.'">';
		
		if (wpsg_isSizedString($conf['labellink']))
		{
		
			$strReturn .= '<a href="'.$conf['labellink'].'">'.$field_label.'</a>';
			
		}
		else
		{
			
			$strReturn .= $field_label;
						
		}
		
		$strReturn .= '&nbsp;</label>
				
				</div>
				<div class="wpsg_form_right '.((isset($conf['help']))?'wpsg_form_right_help':'').'">
					<select id="'.$field_id.'" name="'.$field_name.'" '.$select.' class="select '.$class_select.'">
		';
		
		foreach ((array)$field_values as $k => $v)
		{
			
			if (is_array($v) && array_key_exists('select_key', $v) && array_key_exists('select_value', $v))
			{
				
				$k = $v['select_key'];
				$v = $v['select_value'];
				
			}

			if (isset($conf['noIndex']) && $conf['noIndex'] === true)
				$key = $v;
			else
				$key = $k;
			
			$strSelect = '';
				
			if (is_array($field_value))
			{
				
				if (in_array($key, $field_value)) $strSelect = 'selected="selected"';
				
			}
			else
			{
				
				if ($key == $field_value) $strSelect = 'selected="selected"';
				
			}
				
			$strReturn .= '
						<option value="'.wpsg_hspc($key).'" '.$strSelect.'>'.wpsg_hspc($v).'</option>
			';
			
		}
					
		$strReturn .= '
					</select>
		';
		
		if (isset($conf['help']))
		{
		
			$strReturn .= '<a href="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" rel="?page=wpsg-Admin&subaction=loadHelp&noheader=1&field='.wpsg_hspc($conf['help']).'" class="wpsg_form_help"></a>';

		}
				
		if (isset($conf['remove']))
		{
		
			$strReturn .= '<a title="'.wpsg_hspc($conf['remove']).'" href="#" class="wpsg_icon wpsg_icon_right wpsg_icon_remove"></a>';

		}
		
		if (isset($conf['hint']))
		{
			
			$hint = $conf['hint']; 
			if (substr($hint, 0, 7) != 'nohspc_') $hint = wpsg_hspc($hint);
			else $hint = substr($hint, 7);
			
			$strReturn .= '<div class="wpsg_clear"></div><p class="wpsg_hinweis">'.$hint.'</p>';
						
		}

		$strReturn .= '
					</div>
				<div class="wpsg_clear"></div>
			</div>
		';
		
		return $strReturn;
		*/
		
	} // function wpsg_drawForm_Select($field_name, $field_label, $field_values, $field_value, $conf = array())
	
	function wpsg_drawForm_Date($field_name, $field_label, $field_value, $conf = array())
	{
		
		$strReturn = '';
		
		//01.02.2012 12:12
		if (is_int($field_value) && $field_value > 0)
		{
			$time = $field_value;
		}
		else 
		{

			$time = strtotime($field_value);
			
			if ($time <= 0) $time = time();
			
		}		
		
		$field_id = $field_name;
		
		$strReturn .= '
			<div class="wpsg_form_field" id="'.$field_id.'">
				<div class="wpsg_form_left">
					<label for="'.$field_id.'">'.$field_label.':</label>
				</div>
				<div class="wpsg_form_right">
					<div class="timestamp-wrap">
						<select class="wpsg_month" name="'.$field_name.'[m]">
							<option value="01" '.((date('m', $time) == "01")?'selected="selected"':'').'>Jan</option>
							<option value="02" '.((date('m', $time) == "02")?'selected="selected"':'').'>Feb</option>
							<option value="03" '.((date('m', $time) == "03")?'selected="selected"':'').'>Mrz</option>
							<option value="04" '.((date('m', $time) == "04")?'selected="selected"':'').'>Apr</option>
							<option value="05" '.((date('m', $time) == "05")?'selected="selected"':'').'>Mai</option>
							<option value="06" '.((date('m', $time) == "06")?'selected="selected"':'').'>Jun</option>
							<option value="07" '.((date('m', $time) == "07")?'selected="selected"':'').'>Jul</option>
							<option value="08" '.((date('m', $time) == "08")?'selected="selected"':'').'>Aug</option>
							<option value="09" '.((date('m', $time) == "09")?'selected="selected"':'').'>Sep</option>
							<option value="10" '.((date('m', $time) == "10")?'selected="selected"':'').'>Okt</option>
							<option value="11" '.((date('m', $time) == "11")?'selected="selected"':'').'>Nov</option>
							<option value="12" '.((date('m', $time) == "12")?'selected="selected"':'').'>Dez</option>
						</select>';
		
		
		$strReturn .= ' <input class="wpsg_day" type="text" maxlength="2" size="2" value="'.date('d', $time).'" name="'.$field_name.'[d]">,
						<input class="wpsg_year" type="text" maxlength="4" size="4" value="'.date('Y', $time).'" name="'.$field_name.'[Y]">';
		 
		if (!isset($conf['time']) || $conf['time'] === true)
		{
			$strReturn .= '@ 
						<input class="wpsg_hour" type="text" maxlength="2" size="2" value="'.date('H', $time).'" name="'.$field_name.'[H]"> : 
						<input class="wpsg_minute" type="text" maxlength="2" size="2" value="'.date('i', $time).'" name="'.$field_name.'[i]">';
		}
							
		$strReturn	.= '
					</div>
				</div>
				<div class="wpsg_clear"></div>
			</div>
		';
		
		return $strReturn;
		
	} // function wpsg_drawForm_Date($field_name, $field_label, $field_value, $conf = array())

	function wpsg_drawForm_Link($field_name, $field_label, $field_link, $conf = array())
	{
	
		$TC = new wpsg_SystemController();
	
		$TC->view['field_name'] = $field_name;
		$TC->view['field_label'] = $field_label;
		$TC->view['field_id'] = wpsg_drawForm_getID($field_name);
		$TC->view['field_link'] = $field_link;
		$TC->view['field_config'] = $conf;
	
		return $TC->render(WPSG_PATH_VIEW.'admin/form/link.phtml', false);
	
	} // function wpsg_drawForm_Link($field_name, $field_label, $field_link, $conf = array())
	
?>