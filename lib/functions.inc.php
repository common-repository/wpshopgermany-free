<?php
	
	/**
	 * Prüfen ob Ajax-Request vorliegt
	 * @return	boolean
	 */
	function wpsg_isAjaxRequest()
	{

		if(isset($_REQUEST['transfer']) && $_REQUEST['transfer'] == 'ajax')
		{
			return true;	
		}
		else if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
		{
			return true;
		}
		
		return false;
	
	}

	function wpsg_removeBOM($data) 
	{
    
		if (0 === strpos(bin2hex($data), 'efbbbf')) 
		{
       
			return substr($data, 3);
    
		}
    
		return $data;

	}

	/**
	 * Arraysortierung
	 * @param 	array	zu sortierendes Array
	 * @param 	string	zu sortierende Spalte
	 * @return	array
	 */
	function wpsg_array_csort($marray, $column) 
	{
		
		foreach ($marray as $row) 
		{
	   		$sortarr[] = $row[$column];
		}
	 	@array_multisort($sortarr, $marray);
	 	
	 	return $marray;
	 	
	}
	 
	/**
	 * Wie array_unique nur Multidimensional
	 */
	function wpsg_array_unique($ar)
	{
 
		$arRewrite = array(); $arHashes = array();
		
		foreach($ar as $k => $v) 
		{

			$hash = md5(serialize($v));

			if (!isset($arHashes[$hash])) 
			{
				
				$arHashes[$hash] = $hash;
				$arRewrite[$k] = $v;
								
			}
			
		}
		
		return $arRewrite;
		
	} // function wpsg_array_unique($ar)	

	/**
	 * Funktion ist nötig, da alle Ajax Anfragen is_admin mit true beantworten laut Doku
	 * Ich benötige aber eine Unterscheidung ob ich im Frontend eine Aktion ausführe oder im Backend
	 * Daher habe ich für Frontend Anfragen den Parameter wpsg_frontend_ajax auf 1 gesetzt
	 */
	function wpsg_is_admin()
	{

		if (isset($_REQUEST['wpsg_frontend_ajax']) && $_REQUEST['wpsg_frontend_ajax'] === '1') return false;
		else return is_admin();

	} // function wpsg_is_admin()

    function wpsg_rglob($pattern, $flags = 0)
    {

        $arFiles = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {

            $arFiles = array_merge($arFiles, wpsg_rglob($dir.'/'.basename($pattern), $flags));

        }

        return $arFiles;

    }

	/**
	 * Prüft die Eingabe eines Geburtsdatums auf Gültigkeit
	 * @param String $val
	 */  
	function wpsg_isValidGeb(&$val)
	{
		
	    if (preg_match('/\d{4}\-\d{2}\-\d{2}/', $val))	        
        {
            
            $val = date('d.m.Y', strtotime($val));
            
        }
	    
		if (wpsg_isSizedString($val) && preg_match('/\d{2}\.\d{2}\.\d{4}/', $val)) return true;
		else return false;
		
	} // function wpsg_isValidGeb($val)
	
	/**
	 * Prüft die Eingabe einer E-Mail Adresse auf gültigkeit
	 * @param String $val
	 */
	function wpsg_isValidEMail(&$val)
	{
		
		if (wpsg_isSizedString($val) && strpos($val, '@') !== false) return true;
		else return false;
		
	} // function wpsg_isValidEMail($val)
	
	/**
	 * Definiert eine Konstante, wenn Sie noch nicht definiert wurde
	 * @param unknown $name
	 * @param unknown $value
	 */
	function wpsg_define($name, $value)
	{
		
		if (!defined($name)) define($name, $value);
		
	} // function wpsg_define($name, $value)
	
	function wpsg_parse_size($size) 
	{
  
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  
		if ($unit) 
		{
    
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
  		
		}
  		else 
		{
    
			return round($size);
  
		}

	} // function wpsg_parse_size($size) 

	function wpsg_get_file_upload_max_size() 
	{
  
		static $max_size = -1;

  		if ($max_size < 0) 
		{
    
    		$post_max_size = wpsg_parse_size(ini_get('post_max_size'));
    
			if ($post_max_size > 0) 
			{
      
				$max_size = $post_max_size;
    
			}

    		$upload_max = wpsg_parse_size(ini_get('upload_max_filesize'));
    
			if ($upload_max > 0 && $upload_max < $max_size) 
			{
      
				$max_size = $upload_max;
    
			}
  
		}
  
		return $max_size;
		
	} // function wpsg_get_file_upload_max_size()


	/**
	 * Setzt einen Wert oder addiert ihn
	 * Um Warnungen zu verhindern wenn man einen Wert auf eine Variable addiert die möglicherweise nicht definiert ist
	 */
	function wpsg_addSet(&$arrayElement, $addSet)
	{
		
		if (isset($arrayElement))
		{

			$arrayElement += (double)$addSet;
			
		}
		else
		{
			
			$arrayElement = $addSet;
			
		}
		
	}
	
	/**
	 * Versucht den für den Kunden aktuellen Ländercode (Kürzel) zu ermitteln
	 */
	function wpsg_geo_code()
	{
		
		$country_code = false;
		
		// GeoIP
		if (isset($_SERVER['GEOIP_COUNTRY_CODE']) && $GLOBALS['wpsg_sc']->get_option('wpsg_geo_determination') == '1')
		{
				
			// Apache mod_geoip
			$country_code = $_SERVER['GEOIP_COUNTRY_CODE'];
							
		}		
		else if (function_exists("geoip_country_code_by_name") && $GLOBALS['wpsg_sc']->get_option('wpsg_geo_determination') == '2')
		{
				
			// PECL php_geoip
			$country_code = geoip_country_code_by_name($_SERVER["REMOTE_ADDR"]); 
				
		}
		else if (wpsg_isSizedString($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $GLOBALS['wpsg_sc']->get_option('wpsg_geo_determination') == '3')
		{
				
			if (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ',') !== false)
			{
		
				$country_code = array_shift(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
		
			}
			else
			{
		
				$country_code = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		
			} 
			 
		}
		
		// Sollte der Code das format xx-XX haben
		if (preg_match('/(.*)-(.*)/', $country_code)) $country_code = preg_replace('/-(.*)/', '', $country_code);
		
		if (wpsg_isSizedString($country_code)) return strtolower($country_code);
		else return false;
		
	}
	
	/**
	 * Angepasste Sortierfunktion, die Umlaute beachtet
	 * Ruft wpsg_asort_function über uasort auf
	 */
	function wpsg_asort(&$ar)
	{
		
		uasort($ar, 'wpsg_asort_function');
		
	} // function wpsg_asort($ar)
	
	/**
	 * Sortierfunktion für wpsg_asort
	 */
	function wpsg_asort_function($a, $b)
	{
		
		$arSearch = array("Ä", "ä", "Ö", "ö", "Ü", "ü", "ß", "-");
		$arReplace = array("Ae", "ae", "Oe", "oe", "Ue", "ue", "ss", " ");
		
		$a = str_replace($arSearch, $arReplace, $a);
		$b = str_replace($arSearch, $arReplace, $b);
		
		if ($a == $b) return 0;
		else return ($a < $b)?-1:1;
		
	} // function wpsg_asort_function($a, $b)
	
	/**
	 * Gibt einen Pfad formatiert zurück
	 * - Entfernt doppelte // bzw. \\
	 * - Wandelt // in Systemspezifische Trenner um
	 */
	function wpsg_format_path($strPath)
	{
		
		$strPath = preg_replace('/(\/+)|(\\+)/', DIRECTORY_SEPARATOR, $strPath);
		
		return $strPath;
		
	} // function wpsg_format_path($strPath)
	
	function wpsg_array_csort_pk_function($accountA, $accountB)
	{

		$key = $GLOBALS['wpsg_array_csort_pk_key'];
		
		$arSearch = array("Ä", "ä", "Ö", "ö", "Ü", "ü", "ß", "-");
		$arReplace = array("Ae", "ae", "Oe", "oe", "Ue", "ue", "ss", " ");

		$a = str_replace($arSearch, $arReplace, $accountA[$key]);
		$b = str_replace($arSearch, $arReplace, $accountB[$key]);
		
		if ($a == $b) return 0;
		else return ($a < $b)?-1:1;
		
	} // function wpsg_array_csort_pk_function($accountA, $accountB)
	
	function wpsg_array_csort_pk($accounts, $key)
	{
		
		$GLOBALS['wpsg_array_csort_pk_key'] = $key;
		uasort($accounts, 'wpsg_array_csort_pk_function');
		
		return $accounts;
							    
	}

	function wpsg_tax_groups($noRata = false, $noSuffix = false)
	{
		
		$arTaxGroups = array(
			'0' => __('anteilig', 'wpsg'),
			'a' => 'A',
			'b' => 'B',
			'c' => 'C',
			'd' => 'D',
			'e' => 'Nullsatz'	
		);
		
		if ($noRata === true) unset($arTaxGroups[0]);		
		if ($noSuffix === true) return $arTaxGroups;
		
		foreach ($arTaxGroups as $tax_key => &$tax_label)
		{
			
			if (!in_array($tax_key, array('0', 'e')))
			{
			
				$default_country = $GLOBALS['wpsg_sc']->getDefaultCountry();

				if (is_object($default_country))
				{
				
					$tax_value = $default_country->getTax($tax_key);			
					if (!is_null($tax_value)) $tax_label .= ' ('.wpsg_ff(wpsg_tf($tax_value), '%').' / '.$default_country->kuerzel.')';
					
				}
				
			}
			
		}
		
		return $arTaxGroups;
		
	} // function wpsg_tax_groups()

	/**
	 * Wandelt einen Key => Value Array für die InlineEdit JS Funktion um
	 * @param $ar
	 */
	function wpsg_prepare_for_inlineEdit($ar)
	{
		
		if (!wpsg_isSizedArray($ar)) return json_encode(array());
		
		$arReturn = array();
		
		foreach ($ar as $k => $v)
		{
			
			$arReturn[] = array('value' => $k, 'text' => $v);
			
		}
		
		return json_encode($arReturn);
		
	} // function wpsg_prepare_for_inlineEdit($ar)

	function wpsg_prepare_for_debug(&$value)
	{
		
		if (is_array($value))
		{
			
			foreach ($value as $k => $v)
			{
				
				if (is_object($v)) $value[$k] = 'Object['.get_class($v).']';
				else if (is_array($v)) wpsg_prepare_for_debug($value[$k]);
				
			}
			
		}
		
	}
	
	function wpsg_debug_console($value)
	{
		
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_debugModus') != "1") return;
		
		echo '<script type="text/javascript"> console.log('.json_encode($value).'); </script>';
		
	}

	/**
	 * Debug Funktion, die den übergebenen Wert ausgibt wenn die Option im Backend aktiviert ist.
	 */
	function wpsg_debug($value, $small = false)
	{
		 
		if ($GLOBALS['wpsg_sc']->get_option('wpsg_debugModus') != "1") return;
		
		echo '<pre style="color:red; '.(($small === true)?'font-size:12px; overflow-x:scroll;':'').'">';
		
		if (is_array($value))
		{
			
			wpsg_prepare_for_debug($value);
			 
			print_r($value);
			echo '</pre>';
			
		}
		else if (is_bool($value))
		{
			
			if ($value === true) echo 'true';
			else echo 'false';
			
		}
		else
		{
			echo $value;
		}
		
		echo '</pre>';
		
	} // function wpsg_debug($value)
	
	/**
	 * Entfernt aus einem Wert / Array alle XSS Attacken
	 * @param $value
	 * @return array|string
	 */
	function wpsg_xss($value)
	{

		if(is_object($value)) return $value;

		if (is_array($value))
		{
			
			foreach ($value as $k => $v)
			{
				
				$value[$k] = wpsg_xss($v);
				
			}
			
		}
		else
		{
			
			$value = strip_tags($value);
			
		}
		
		return $value;
		
	} // function wpsg_xss($value)
	
	/**
	 * Prüft Eingaben auf Gültigkeit
	 *
	 * @param $val
	 * @param $type
	 *
	 * @param null $param
	 * @return bool true wenn Gültig
	 * @throws Exception
	 */
	function wpsg_checkInput(&$val, $type, $param = null) {
		
		$bReturn = false;
		
		if (wpsg_isSizedArray($param['allow']) && in_array($val, $param['allow'])) return true;		
		if (!isset($val) && !wpsg_isTrue($param['allowEmpty'])) return false;
				
		if (!is_numeric($type)) $type = -1;
		
		if (wpsg_isTrue($param['allowEmpty']) && strval($val) === '') return true;
		
		switch ($type) {
			
			case WPSG_SANITIZE_ZIP: // PLZ
				
				if (preg_match('/^\d{5}$/', $val)) $bReturn = true;
				else $bReturn = false;
				
				break;
			
			case WPSG_SANITIZE_USTIDNR:
				
				if (preg_match('/^([A-z]*)?\d+$/i', $val)) $bReturn = true;
				else $bReturn = false;
				
				break;
			
			case WPSG_SANITIZE_DATE:
				
				if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $val)) $bReturn = true;
				else $bReturn = false;
				
				break;
			
			case WPSG_SANITIZE_DATETIME:
				
				if (preg_match('/\d{2}\.\d{2}\.\d{4}(\040\d{2}\:\d{2}(\:\d{2})?)?/', $val)) $bReturn = true;
				else $bReturn = false;
				
				break;
			
			case WPSG_SANITIZE_COSTKEY:
				
				$filtered = preg_replace('/(\d)|(\:)|(,)|(\|)|(\,)/', '', $val);
				
				if (trim($filtered) === '') $bReturn = true;
				else $bReturn = false;
				
				break;
				
			case WPSG_SANITIZE_HTML:
				
				$filtered = \wp_kses_post($val);
				
				if ($filtered === $val) $bReturn = true;
				else $bReturn = false;
				
				break;
				
			case WPSG_SANITIZE_CHECKBOX:
				
				if (in_array($val, ['0', '1'])) $bReturn = true;
				
				break;
			
			case WPSG_SANITIZE_HEXCOLOR:
				
				if ($val === '' || preg_match('/^\#[0-9A-F]{6}$/', $val)) $bReturn = true;
				
				break;
				
			case WPSG_SANITIZE_EMAIL:
				
				if (sanitize_email($val) == $val) $bReturn = true;
				
				break;
			
			case WPSG_SANITIZE_EMAILNAME:
				
				// TODO RFC 2822? Checken
				// <NAME> E-Mail
				// Aber auch nur E-Mail
				//if (preg_match('/(?:[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\ x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/', $val)) $bReturn = true; 
				$bReturn = true;
				
				break;
				
			case WPSG_SANITIZE_PATH:
			case WPSG_SANITIZE_URL:
			case WPSG_SANITIZE_DOMAIN:
			case WPSG_SANITIZE_ARRAY_LANG:
			case WPSG_SANITIZE_TEXTFIELD:
			
				if (is_array($val)) {
					
					$bReturn = true;
					
					foreach ($val as $k => $v) {
						
						$bReturn = $bReturn && wpsg_checkInput($v, WPSG_SANITIZE_TEXTFIELD);
						
					}
					
				} else {
					
					if (sanitize_text_field($val) == trim($val)) $bReturn = true; 
					
				}
				 
				break;
				
			case WPSG_SANITIZE_TEXTAREA:
				
				if (sanitize_textarea_field($val) == trim($val)) $bReturn = true;
				
				break;
				
			case WPSG_SANITIZE_PAGEID:
			case WPSG_SANITIZE_INT: 
				
				if (strval(intval($val)) === strval($val) || strval($val) === '0') $bReturn = true;
				 
				break; 
			
			case WPSG_SANITIZE_VALUES: 
				
				if (!wpsg_isSizedArray($param)) throw new \Exception(__('Systemfehlder! wpsg_checkInput mit $type = values ohne Angabe von möglichen Werten aufgerufen.'));
				
				if (in_array($val, $param)) $bReturn = true;
				
				break;
				
			case WPSG_SANITIZE_FLOAT:
				
				if (\sanitize_text_field($val) == $val) {
					
					$bReturn = true;
									
					$val = wpsg_tf($val, true);
					
				}
				
				break;
				
			case WPSG_SANITIZE_TAXKEY:
				
				if (in_array($val, ['0',  'a', 'b', 'c', 'd', 'e'])) $bReturn = true;
				
				break;
			
			case WPSG_SANITIZE_APIKEY:
			case WPSG_SANITIZE_NONE: $bReturn = true;
				
				break;
				
			case WPSG_SANITIZE_ARRAY_INT:
				
				if (!is_array($val)) $bReturn = false;
				else {
					
					$bReturn = true;
					
					foreach ($val as $k => $v) {
						
						if (!wpsg_checkInput($v, WPSG_SANITIZE_INT)) $bReturn = false;
						
					}
					
				}
				
			default:
			 
				//throw new \Exception(wpsg_translate(__('Typ #1# für Eingabeüberprüfung nicht definiert.', 'wpsg'), $type));
				
				break;
				
		}
				
		return $bReturn;
		
	}
	
	/**
	 * Nutzt die wpsg_checkInput um eine Variable im Request zu validieren
	 *
	 * @param $name
	 * @param $arCheckInputArguments
	 * @param null $strLabel
	 * @param $data
	 * @param null $value
	 * @return bool
	 * @throws \wpsg\Exception
	 */
	function wpsg_checkRequest($name, $arCheckInputArguments, $strLabel = null, &$data, $value = null) {
		
		$type = $arCheckInputArguments[0];
		$param = null;
		
		if (isset($arCheckInputArguments[1])) $param = $arCheckInputArguments[1];
		
		if ($value === null) $value = $_REQUEST[$name]; 
		
		if (!wpsg_checkInput($value, $type, $param)) {
			
			wpsg_ShopController::getShop()->addInputFieldError($name, $strLabel);
			 						
			return false;
			
		} else {
			
			$data[$name] = wpsg_q($value);
			
			return true;
			
		}
		
	}
	
	/**
	 * Entry function for universal sanitization and validation
	 * Suited for assigning another value if function returns false due to an error
	 *
	 * @param String    $type
	 * @param array     $params
	 *
	 * @return array|String
	 * @throws \wpsg\Exception  Do *not* catch
	 *
	 * @see "Securing Input" ( https://developer.wordpress.org/plugins/security/securing-input/ )
	 */
	function wpsg_sanitize($type, ...$params)
	{
 
		$err = false;

		// Main parameter used for comparison
		$primary = isset($params["primary"]) ? $params['primary'] : $params[0];

		// If $primary is not even set
		if($primary === NULL) return false;

		// If prefix "sanitize_" not assigned
		if(strpos($primary, "sanitize_") === false) $type = "sanitize_$type";

		$validTypes = array(
			"sanitize_email" => "str",
			"sanitize_file_name" => "str",
			"sanitize_hex_color" => "str",
			"sanitize_hex_color_no_hash" => "str",
			"sanitize_html_class" => "str",			
			"sanitize_key" => "int",
			'sanitize_wpsg_taxkey' => 'taxkey',
			'sanitize_wpsg_tf' => 'tf',
			"sanitize_meta" => "int", // gettype($meta_key) === "int"
			"sanitize_mime_type" => "str",
			"sanitize_option" => "mixed",
			"sanitize_sql_orderby" => "str",
			"sanitize_text_field" => "str",
			"sanitize_title" => "str",
			"sanitize_title_for_query" => "str",
			"sanitize_title_with_dashes" => "str",
			"sanitize_user" => "str",
			'sanitize_wpsg_in_array' => 'in_array',
			'sanitize_wpsg_checkbox' => 'checkbox',			
		);
		
		if (!array_key_exists($type, $validTypes)) return wpsg_xss($primary);
		if (!function_exists($type) && strpos($type, "wpsg_") === false)
			throw new \wpsg\Exception("Function $type does not exists in the WordPress function pool.");
		 
		# Validation (and Sanitization for type txt_tbl)
		switch($validTypes[$type])
		{

			// Asked for a string
			case "str":
				
				if(gettype($primary) !== "string") {
					
					$err = __("Bitte überprüfen sie folgende Eingabe: ", "wpsg");
					
				}
				
				break;
			
			case 'checkbox':
				 
				if (!in_array($primary, ['0', '1'])) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Ungültige Eingaben, bitte überprüfen Sie die markierten Felder.', 'wpsg'));
					
					return false;
					
				}
				
				break;
				
			case 'in_array':
				
				if (!in_array($primary, $params[1])) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Ungültige Eingaben, bitte überprüfen Sie die markierten Felder.', 'wpsg'));
					
					return false;
					
				}
				
				$sanitized_val = $primary;
				
				break;
				
			case 'tf': 
				
				if (!preg_match('/^\-?\d+(\.|\,)\d+$/', $primary)) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Ungültige Eingaben, bitte überprüfen Sie die markierten Felder.', 'wpsg'));
					
					return false;
					
				}
				
				$sanitized_val = wpsg_tf($primary);
						 
				break;

			case "taxkey":
				 
				$primary = strtolower($primary);
				
				if (!in_array($primary, ['0', 'a', 'b', 'c', 'd'])) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Überprüfen Sie den Mehrwertsteuersatz, die Eingabe war ungültig.', 'wpsg'));
					
					return false;
					
				}  
				
				break;
				
			case "int":
				
				$nPrimary = (int)$primary;
				
				if ($nPrimary !== $primary) {
					
					$GLOBALS['wpsg_sc']->addBackendError(__('Ungültige Eingaben, bitte überprüfen Sie die markierten Felder.', 'wpsg'));
					
					return false;
					
				}
				
				break;
								
			// Asked for an integer
			/*case "int":
				
				if($primary[0] === "-")
				{

					$isNegative = true;
					$primary = substr($primary, 1, strlen($primary) - 1);

				}

				$prefix = wpsg_isTrue($isNegative) ? "-" : "";

				if(in_array("isFloat", $params))
				{

					$fPrimary = wpsg_tf($primary);


					if(
						(bool)((double)$fPrimary <= (double)wpsg_tf(0)) &&
						wpsg_isSizedString($primary) &&
						!in_array($primary, array("0", "0.0", "0.00", "0,0", "0,00")))
					{

						$fPrimary = $primary;
						$prefix = "";

						$err = __("Bitte überprüfen sie folgende Eingabe: ");

					}

				}
				else
				{
					
					$nPrimary = (int)$primary;
					
					if(
						gettype($nPrimary) !== "integer" ||
						!empty($nPrimary) && !wpsg_isSizedInt($nPrimary) && $primary !== "0" ||
						wpsg_isSizedString($primary) && !wpsg_isSizedInt($nPrimary) && $primary !== "0"
					) $err = __("Bitte überprüfen sie folgende Eingabe: ");
					
				}
				break;
			*/
			
			case "txt_tbl":
				$returnArr = array();

				foreach($primary as $k => $arr)
					foreach($arr as $_k => $_v)
						switch($_k)
						{

							case "text":
								$returnArr[$k][$_k] = wpsg_sanitize("text_field", $_v);
								break;

							case "x":
							case "y":
							case "fontsize":
							case "alpha":
							case "angle":
							case "align":
							case "aktiv":
							case "bg":
								$returnArr[$k][$_k] = wpsg_sanitize("key", $_v);
								break;

							case "color":
								$returnArr[$k][$_k] = wpsg_sanitize("hex_color", $_v);
								break;

						}

				return $returnArr;
				break;

			// Mixed value
			default:
				break;

		}

		# Sanitization WP Funktion
		if (strpos($type, "wpsg_") === false) {
			
			try {
	
				// If a float/double value is wanted
				if($validTypes[$type] !== "int" && !in_array("isFloat", $params) || $err !== false)
					$sanitized_val = call_user_func_array($type, $params);
				else if(in_array("isFloat", $params))
					$sanitized_val = sanitize_text_field($prefix.wpsg_ff($fPrimary));
				else {
				
					$sanitized_val = sanitize_text_field($prefix.$primary);
					
				}
	
			} catch(Exception $e) {
				
				throw new \wpsg\Exception($e->getMessage(), $e->getCode());
				
			}
			
		}

		# If error was thrown --> output $primary after sanitization
		if($err !== false)
		{

			$GLOBALS['wpsg_sc']->addBackendError(
				$err . (wpsg_isSizedString($sanitized_val) ? substr($sanitized_val, 0, 10) : $sanitized_val)
			);

			return false;

		}

		return $sanitized_val ?: wpsg_xss($primary);

	} // function wpsg_sanitize(String $type, array|string ...$params)


	/**
	 * Secure Input Alias
	 * Suited for returning either the wp sanitized or the wpsg_xss sanitized value
	 *
	 * @param       $type
	 * @param mixed ...$params
	 *
	 * @return array|string
	 * @throws \wpsg\Exception  Do *not* catch
	 */
	function wpsg_sinput($type, ...$params)
	{

		return $params[0];
		
		/*
		 * Deaktiviert
		$primary = isset($params["primary"]) ? $params['primary'] : $params[0];
		$sanitizedVal = wpsg_sanitize($type, ...$params);

		return !$sanitizedVal ? wpsg_xss($primary) : $sanitizedVal;
		*/
		
	} // function wpsg_sinput(String $type, array|string ...$params)

	/**
	 * Entry function for global escaping
	 *
	 * @param String $type
	 * @param array  $params
	 *
	 * @return string
	 * @throws \wpsg\Exception Do *not* catch
	 * @see "Securing Output" ( https://developer.wordpress.org/plugins/security/securing-output/ )
	 */
	function wpsg_escape($type, ...$params)
	{

		// Main parameter used for comparison
		$primary = isset($params["primary"]) ? $params['primary'] : $params[0];

		// If $primary is not even set
		if($primary === NULL) return false;

		// If prefix "esc_" is not assigned
		if(strpos($type, "esc_") === false) $type = "esc_" . $type;

		$validTypes = array(
			"esc_html",
			"esc_url",
			"esc_js",
			"esc_attr"
		);

		if(!in_array($type, $validTypes)) return wpsg_q($primary);
		if(!function_exists($type)) throw new \wpsg\Exception("Function $type does not exists in the WordPress function pool.");

		# Escape function execution
		try{
			if(wpsg_isSizedArray($params)) $returnVal = call_user_func_array(
				$type, array_unshift($params, $primary)
			);
			else $returnVal = $type($primary);
		} catch(Exception $e) {
			throw new \wpsg\Exception($e->getMessage(), $e->getCode());
		}

		return $returnVal ?: wpsg_q($primary);

	} // function wpsg_escape(String $type, array|String $params)

	/**
	 * Sortiert den Array $ar um, nach den Indexen in $newIndexOrder
	 *
	 * @param unknown $ar
	 * @param unknown $newIndexOrder
	 *
	 * @throws \wpsg\Exception
	 */
	function wpsg_array_reorder(&$ar, $newIndexOrder)
	{
		
		$ar_alt = $ar;
		$ar = array();
		
		$index = 0;
		foreach ($newIndexOrder as $order)
		{
			
			if (array_key_exists($order, $ar_alt))
			{
			
				$ar[$index] = $ar_alt[$order];
				unset($ar_alt[$order]);
				
			}
			
			$index ++;
			
		}
		
		if (wpsg_isSizedArray($ar_alt))
		{
			
			throw new \wpsg\Exception(__('Beim umsortieren eines Arrays gab es im Original Array mehr Elemente als in der angegebenen Sortierung', 'wpsg'));
			
		}
		
	} // function wpsg_array_reorder(&$ar, $newIndexOrder)
	 
    function wpsg_anonymip($value) {
    
        return preg_replace('/\d*$/', 'xxx', $value);

    }
	
	/**
	 * Generiert eine zufällige Zeichenkette der Länge $laenge
	 */
	function wpsg_genCode($laenge, $chars = false) {
		
		if ($laenge <= 0) $size = 10;
		
		if ($chars === false) $chars = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
		
		$arCode = array();
		for ($i = 1; $i <= $laenge; $i++) { $arCode[] = $chars[rand(0, (strlen($chars) - 1))]; }
		
		$strCode = implode('', @$arCode);
		
		return $strCode;
		
	} // public function genCode($laenge)
    
	/**
	 * Escape Funktion für die Datenbank
	 */
	function wpsg_q($value)
	{
		 
		if (is_array($value))
		{
			
			foreach ($value as $k => $v)
			{
				
				$value[$k] = wpsg_q($v);
				
			}
			
			return $value;
			
		}
		else
		{
		
			if (is_object($value))
			{
				
				throw new \wpsg\Exception(__('Beim escapen wurde ein Objekt übergeben, hier sind nur Strings erlaubt.', 'wpsg'));
				
			}
			
			return esc_sql($value);
			
		}
		
	} // function wpsg_q($value)
	
	/**
	 * Wandelt einen Wert oder einen Array in UTF8 um
	 */
	function wpsg_toUtf8($value)
	{
		
		if (is_array($value))
		{
			
			foreach ($value as $k => $v)
			{
				
				$value[$k] = wpsg_toUtf8($v);
				
			}
			
			return $value;
			
		}
		else
		{
			
			return utf8_encode($value);
			
		}
		
	} // function wpsg_toUtf8($value)
	
	/**
	 * Array Merge und Indexe nicht neu nummerieren
	 * Siehe: http://de2.php.net/manual/de/function.array-merge.php#106803
	 */
	function wpsg_array_merge($a, $b)
	{
						
		$result = array_diff_key((array)$a, (array)$b) + (array)$b;
		
		return $result;
		
	} // function wpsg_array_merge($a, $b)
	
	/**
	 * Hilfsfunktion für das Hinzufügen von Attributen (XML)
	 */
	function wpsg_addAttributs($doc, $element, $arAttribute)
	{
		
		foreach ($arAttribute as $name => $value)
		{
			$att = $doc->createAttribute($name);
			$att->appendChild($doc->createTextNode($value));
			$element->appendChild($att);
		}
				
	} // function addAttributs($doc, $element, $arAttributs)
	
	/**
	 * & in den urls wird vom Validator angemeckert ...
	 */
	function wpsg_url($url) { return htmlspecialchars($url); }
	
	/**
	 * Wrapper für htmlspecialchars
	 */
	function wpsg_hspc($string) { if (isset($string)) return htmlspecialchars($string); else return ''; } // function wpsg_hspc($string)

	/**
	 * Dient zur Erkennung des Cron Jobs
	 * (Für die Funktion canDisplay im Produkt wird der Admin Status abgefragt, so dass Produkte im Backend angezeigt werden auch wenn sie ausverkauft sind
	 * Im Cron müssen diese Produkte auch exportiert werden damit die Darstellung mit dem Backend stimmt
	 */
	function wpsg_is_cron()
	{
		
		if (defined("WPSG_CRON") && WPSG_CRON === true) return true;
		else return false;
		
	}

	/**
	 * Formatiert einen Double Wert für Ausgaben im Frontend
	 */
	function wpsg_ff($value, $einheit = false, $keep = false, $stellen = 2)
	{
		
		if (!isset($value)) $value = 0;
		
		if (strpos($value, '%') !== false)
		{
			
			$einheit = '%';
			$value = str_replace('%', '', $value);
			
		}
		
		if ($keep === true)
		{

			$strReturn = preg_replace('/\./', ',', $value);
			
		}
		else
		{
 
			$value = doubleval($value);
			   
			if ($value < 1)
			{
				
				preg_match('/0\.(0+)\d/', $value, $match);
				
				if (isset($match[1])) {
					
					$stellen = strlen($match[1]) + $stellen;
					
				}
				
			}
			
			$strReturn  = number_format(doubleval($value), $stellen, ',', '.');
			
		}
		
		if ($einheit !== false && $einheit !== true) $strReturn .= ' '.$einheit;
		
		return $strReturn;
		
	} // function wpsg_ff($value)
	
	/**
	 * Versucht Nutzereingaben in valide Floatwerte zu wandeln
	 */
	function wpsg_tf($value, $keepProcent = false)
	{
		
		if ($keepProcent === true && strpos($value, '%') !== false) $keepProcent = true; else $keepProcent = false;
		
		// Alles außer Zahlen, Punkt und Komma entfernen
		$value = preg_replace('/[^\d|^\.|^\,|^\-]/', '', $value);
				
		if (strpos($value, ".") && strpos($value, ","))
		{
			
			// , und . drin				
			if (strpos($value, ",") > strpos($value, "."))
			{
				
				//1.123,23
				return wpsg_tf(str_replace(",", ".", str_replace(".", "", $value)));
				
			}
			else
			{

				//1,234.23
				return wpsg_tf(str_replace(",", "", $value));
				
			}
			
		}
		
		if ($keepProcent === true)
		{
			return str_replace(",", ".", $value).'%';
		}
		else
		{
			return floatval(str_replace(",", ".", $value));
		}
		
	} // function wpsg_tf($value)
	
	/**
	 * Wie round, gibt aber immer ein Double Wert zurück.
	 * Unabhängig von den Locale Einstellungen
	 */
	function wpsg_round($value, $digits = 0)
	{
		
		$value = round($value, $digits);
		
		return wpsg_tf($value);
		
	} // function wpsg_round($value, $digits = 0)
	
	/**
	 * Gibt die RGB Farben im Dezimalformat für einen HEX Farbcode zurück
	 */
	function wpsg_getColor($strHEXCode)
	{
		
		if ($strHEXCode[0] == '#')
	        $strHEXCode = substr($strHEXCode, 1);
	
	    if (strlen($strHEXCode) == 6)
	        list($r, $g, $b) = array($strHEXCode[0].$strHEXCode[1],
	                                 $strHEXCode[2].$strHEXCode[3],
	                                 $strHEXCode[4].$strHEXCode[5]);
	    elseif (strlen($strHEXCode) == 3)
	        list($r, $g, $b) = array($strHEXCode[0].$strHEXCode[0], $strHEXCode[1].$strHEXCode[1], $strHEXCode[2].$strHEXCode[2]);
	    else
	        return false;
	
	    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
	    
	    return array($r, $g, $b);
	    
	} // private function getColor($colorcode)
	
	/**
	 * Ausgabe Bufferung beenden und Inhalt verwerfen
	 */
	function wpsg_ob_end_clean()
	{
		
		if (wpsg_isSizedArray(ob_list_handlers()))
		{
				
			foreach (ob_list_handlers() as $h) { ob_end_clean(); }
				
		}
		
	} // function wpsg_ob_end_clean()
	
	/**
	 * Erweitert einen String nach Rechts und kürzt ihn gegebenenfalls
	 */
	function wpsg_pad_right($value, $length)
	{
		
		//$string = str_pad($value, $length, ' ', STR_PAD_RIGHT);
		
		$diff = strlen($value) - mb_strlen($value);
    	$string = str_pad($value, $length + $diff, ' ', STR_PAD_RIGHT);
		
		//if (strlen($string) > $length) $string = substr($string, 0, $length - 2).'..';
		
		return $string;
		
	} // function wpsg_pad($value, $length)
	
	/**
	 * Erweitert einen String nach Links und kürzt ihn gegebenenfalls
	 */
	function wpsg_pad_left($value, $length)
	{
		
		//$string = str_pad($value, $length, ' ', STR_PAD_LEFT);
		
		$diff = strlen($value) - mb_strlen($value);
    	$string = str_pad($value, $length + $diff, ' ', STR_PAD_LEFT);
		
		//if (strlen($string) + $diff > $length) $string = substr($string, 0, $length - 2).'..';
		
		return $string;
		
	} // function wpsg_pad_left($value, $length)
	
	/**
	 * Wandelt eine Datumseingabe aus dem Frontend in ein Datumsformat für die Datenbank um
	 */
	function wpsg_toDate($value)
	{
			
		if (is_numeric($value))
		{
			
			return date('Y-m-d', $value); 
			
		}
		else if (preg_match('/\d{2}\.\d{2}\.\d{4}/', $value))
		{
			
			$arDate = explode('.', $value);
			
			return $arDate[2].'-'.$arDate[1].'-'.$arDate[0];
			
		}		
		else if (strtotime($value) > 0)
		{
			
			return date('Y-m-d', strtotime($value));
			
		}
		
		return '0000-00-00';
		
	} // function wpsg_toDate($value)
	
	/**
	 * Wandelt ein Datum aus der Datenbank in ein klar leserliches Datum um
	 */
	function wpsg_fromDate($value, $dateOnly = true)
	{
		 
		if ($value == '0000-00-00') return '';
		
		if ($dateOnly && strtotime($value) != 0) return date('d.m.Y', strtotime($value));
		else if (strtotime($value) != 0) return date('d.m.Y H:i:s', strtotime($value));
		
	} // function wpsg_fromDate($value)

	/**
	 * Gibt einen Timestamp formatiert als Datum/Datum+Zeit zurück
	 */
	function wpsg_formatTimestamp($ts, $dateOnly = false)
	{

		if (!is_numeric($ts)) $ts = strtotime($ts);

		if ($ts == 0) return '';
		
		if ($dateOnly) return date('d.m.Y', $ts);
		else return date('d.m.Y H:i:s', $ts);
		 
	} // function wpsg_formatTimestamp($ts)
	
	/**
	 * Erweiterung der Gettext Funktion um flexible Parameter
	 * Aufruf in der Form: translate(__("Es wurden #1# Häuser gefunden.", "wpsg"));
	 * 
	 * Zusätzlich wird der String noch durch Htmlspecialchars gejagt
	 */
	function wpsg_translate($string)
	{
		
		$arg = array(); 
			
	  	for($i = 1 ; $i < func_num_args(); $i++)
	  	{
	  		
	  		$arg = func_get_arg($i);
	  		$string = preg_replace("/#".$i."#/", $arg, $string);  	
	  	}
	   
	  	return $string; 
	  	
	} // function wpsg_translate($string)

    /**
     * Wie die Wordpress Funktion, bachtet aber die WPML Seitenzuordnung
     */
    function wpsg_get_the_id() {

        return $GLOBALS['wpsg_sc']->getPageId(get_the_id());

    } // function wpsg_get_the_id()

	function wpsg_calculatePreis($value, $brutto_netto, $mwst)
	{
		
		if (doubleval($value) <= 0) return 0;
		if (doubleval($mwst) <= 0) return $value;

		if ($brutto_netto == WPSG_BRUTTO)
		{
			
			// Brutto Preis bestimmen			
			return $value * (1 + ($mwst / 100));
			
		}
		else
		{

			// Netto Preis bestimmen
			return $value / (1 + ($mwst / 100));
			
		}
		
	} // public function wpsg_calculate($value, $brutto_netto, $mwst)
		
	/**
	 * Berechnet den Steuerteil eines Wertes
	 
	 * @param double $value Der Preis
	 * @param int $brutto_netto Preis in Netto oder Brutto
	 * @param double $mwst der Mehrwertsteuersatz
	 */
	function wpsg_calculateSteuer($value, $brutto_netto, $mwst)
	{
		
		if ($brutto_netto == WPSG_BRUTTO)
		{
			return $value - wpsg_calculatePreis($value, WPSG_NETTO, $mwst);
		}
		else
		{
			return wpsg_calculatePreis($value, WPSG_BRUTTO, $mwst) - $value;
		}
		
	} // public function wpsg_calculateSteuer($value, $brutto_netto, $mwst)
	
	/**
	 * Wie Explode, nur dass auch noch leere Elemente entfernt werden
	 */
	function wpsg_explode($del, $value)
	{
		
		$arData = explode($del, $value);
		
		foreach ($arData as $k => $v)
		{
			 
			if (trim($v) == "") unset($arData[$k]);
			
		}
		
		return $arData;
		
	} // function wpsg_explode($del, $value)
	
	function wpsg_getIBANBIC($blz, $knr, $country)
	{
		
		$bban_str = str_pad($blz, 8, "0", STR_PAD_LEFT).str_pad($knr, 10, "0", STR_PAD_LEFT);
		$country_num = strval(ord(substr($country_str, 0, 1)) - 55).strval(ord(substr($country_str, 1, 1)) - 55)."00";
		$cksum = str_pad(98-intval(bcmod($bban_str.$country_num, "97")), 2, "0", STR_PAD_LEFT);
		$iban = $country_str.$cksum.$bban_str;
		
		return array(
			'iban' => $iban				
		);
		
	} // function wpsg_getIBANBIC($blz, $knr, $country)
	
	/**
	 * Wie implode, nur das leere Elemente entfernt werden
	 * @param String $del Trennzeichen
	 * @param Array $ar Array der implodiert werden soll
	 */
	function wpsg_implode($del, $ar)
	{
		
		foreach ($ar as $k => $v)
		{
			
			if (trim($v) == '') unset($ar[$k]);
			
		}
		
		return implode($del, $ar);
		
	} // function wpsg_implode($ar)
	
	/**
	 * Wie trim, bei Arrays entfernt es leere Elemente
	 */
	function wpsg_trim($value, $clearEntry = '')
	{

		if (!is_array($clearEntry)) $testClearEntry = array($clearEntry);
		else $testClearEntry = $clearEntry;
		
		if (is_array($value))
		{

			foreach ($value as $k => $v)
			{
				
				$value[$k] = wpsg_trim($v, $clearEntry);
				if ($clearEntry !== false && in_array($value[$k], $testClearEntry)) unset($value[$k]);
				
			}
			
		}
		else 
		{
			
			$value = trim($value);
						
		}
		
		return $value;
		
	} // function wpsg_trim($value)
	
	/**
	 * Gibt die Dateigröße einer Datei formatiert zurück
	 * @param $size Dateigröße in Bytes der Datei
	 */
	function wpsg_formatSize($size) 
	{
	
		if (is_string($size) && !is_numeric($size)) $size = filesize($size);
		
		$mod = 1024;
	 
	    $units = explode(' ', 'B KB MB GB TB PB');
	
	    for ($i = 0; $size > $mod; $i++) 
	    {
	        $size /= $mod;
	    }
	 
	    return round($size, 2).' '.$units[$i];
	    
	} // private function wpsg_formatSize($size)
	
	function wpsg_isSizedDouble(&$value)
	{
		
		if (!isset($value)) return false;
		
		$dValue = doubleval($value);
		 
		if ($dValue <= 0) return false;
		
		return true;
		
	}
	
	/**
	 * Prüft ob eine Variable gesetzt und > 0 ist
	 * 0.1 ist true und 1 ist true
	 * TEST ist false
	 */
	function wpsg_isSized(&$value)
	{
		
		if (isset($value) && $value > 0)
		{
			
			return true;
			
		}
		
		else return false;
		
	} // function wpsg_isSized(&$value)
	
	/**
	 * Prüfung ob die gegebene Variable numerisch ist und > 0
	 * @param 	int
	 * @return 	boolean
	 */
	function wpsg_isSizedInt(&$int, $value = false)
	{
				
		$isset = true;
		if (!isset($int) || !is_numeric($int)) $isset = false;
		else if ($int <= 0) $isset = false;
		
		if ($isset === true && $value !== false)
		{
			
			if ($value == $int) return true;
			else return false;
			
		}
		
		return $isset;
		
	}	
		
	function wpsg_checkNounce($controller, $action = '', $arParam = []) {
		
		check_admin_referer(wpsg_getNounce($controller, $action, $arParam));
		
	}
	
	function wpsg_formNounce($controller, $action = '', $arParam = []) {
		
		return wp_nonce_field(wpsg_getNounce($controller, $action, $arParam));
		
	}
	
	function wpsg_getNounce($controller, $action = '', $arParam = []) {
		
		$strNounce = 'wpsg-'.strtolower($controller).'-'.strtolower($action).'-';
		
		if (wpsg_isSizedArray($arParam)) {
		
			ksort($arParam);
			
			foreach ($arParam as $k => $v) {
				
				$strNounce .= $k.'-'.$v;
				
			}
			
		}
		
		return $strNounce;
		
	}
	
	function wpsg_admin_url($controller, $action = '', $arParam = [], $arParamNoNounce = [], $html_entity_decode = false) {
		
		$strURL = WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-'.$controller.'&action='.$action;
		
		if (wpsg_isSizedArray($arParam)) {
			
			$strURL .= '&'.http_build_query($arParam);
						
		}		
		
		if (wpsg_isSizedArray($arParamNoNounce)) {
			
			$strURL .= '&'.http_build_query($arParamNoNounce);
			
		}
				
		$url = wp_nonce_url($strURL, wpsg_getNounce($controller, $action, $arParam));
		
		if ($html_entity_decode) return html_entity_decode($url);
		else return $url;
		
	}
	
	/**
	 * Prüfung ob befülltes Array vorhanden ist
	 * @param 	array	$array
	 * @return 	boolean
	 */
	function wpsg_isSizedArray(&$array, $size = 1)
	{
		
		if(isset($array) && is_array($array) && sizeof($array) >= $size)
		{
			return true;
		}
		
		return false;
		
	} // function isSizedArray($array, $size = 1)

	/**
	 * Gibt die Numerische Entsprechung einer Variable zurück oder $default
	 */
	function wpsg_getInt(&$value, $default = 0)
	{
			
		if (!isset($value) || !is_numeric($value)) return $default;
		
		return intval($value);
		
	}

	function wpsg_getFloat(&$value, $default = 0.0)
	{

		if (!isset($value)) return $default;
		else return doubleval($value);

	}

	/**
	 * Gibt einen String zurück und verhindert Fehler wenn eine Wert nicht definiert wurde
	 */
	function wpsg_getStr(&$value, $default = '')
	{
		
		if (!isset($value) || !wpsg_isSizedString($value)) return strval($default);
		
		return $value;		
		
	} // wpsg_getStr($value = '')

	/**
	 * Gibt ein Array zurück und verhindert Fehler wenn eine Wert nicht definiert wurde
	 */
	function wpsg_getArray(&$value)
	{
	
		if (!isset($value) || !is_array($value)) return Array();
	
		return $value;
	
	} // wpsg_getArray($value = '')
	
	/**
	 * Prüft ob eine Variable gesetzt und true ist
	 */
	function wpsg_isTrue(&$val)
	{
		 
		if (isset($val) && $val === true) return true;
		else return false;
		
	} // function wpsg_isTrue(&$val)
	
	function wpsg_explodeName($val)
	{
		
		$arWords = explode(' ', $val);
		
		return array($arWords[0], implode(' ', array_slice($arWords, 1)));
		
	}

	/**
	 * Prüft ob eine Varible ein String ist und die Länge > 0 ist
	 * Gibt auch bei (int)"1" true zurück (!!!!!)
	 */
	function wpsg_isSizedString(&$strValue, $value = false)
	{
		
		$oldValue = $strValue;
		
		$isset = true;		
		if (!isset($strValue)) return false;
		if (is_int($strValue)) $strValue = strval($strValue);
		
		if (gettype($strValue) != 'string') return false;
		
		if (strlen($strValue) <= 0) $isset = false;
		
		if ($isset === true && $value !== false)
		{
			
			if ($value === $strValue)
			{
				
				$strValue = $oldValue;
				return true;
				
			}
			else 
			{
				
				$strValue = $oldValue;
				return false;
				
			}
			
		}
		
		$strValue = $oldValue;
		return $isset;
		
	} // function wpsg_isSizedString($strValue)
	
	/**
	 * Rückgabe der Monatstage eines bestimmenten Monats in einem Jahr
	 * @param	int	$month
	 * @param 	int	$year
	 * @return 	int
	 */
	function wpsg_getDaysofMonth($month, $year) {
	    
		$time = mktime(0, 0, 0, $month, 1, $year);
		 
		return date('t', $time);
		
    }
		
	/**
	 * Gibt das Upload Verzeichnis zurück, in dem Daten von wpShopGermany gespeichert werden
	 * @param string $strPathKey Ein mögliches Unterverzeichnis, wird angelegt
	 * @return string Der absolute Pfad
	 */
	function wpsg_getUploadDir($strPathKey = '', $htprotection = true)
	{
		
	    if (strpos($strPathKey, '..')) throw new \Exception(__('Unzulässige Pfadangabe!'));
	    
		if ($GLOBALS['wpsg_sc']->isMultiBlog()) {
			
			$path = WP_CONTENT_DIR.'/'.WPSG_MB_UPLOADS.'/wpsg/'.$strPathKey.'/';
			
		} else {
			
			$path = WP_CONTENT_DIR.'/uploads/wpsg/'.$strPathKey.'/';
			
		}
		
		if ($strPathKey === '' || $htprotection === false) {
		    
		    if (!file_exists($path)) mkdir($path, 0775, true);
		    		    		    
        } else {
		
		    $GLOBALS['wpsg_sc']->protectDirectory($path);
		    
        }
		
		return $path;
		
	} // function wpsg_getUploadDir($strPathKey = '')

    /**
     * Siehe wpsg_getUploadDir
     * Gibt aber die URL zurück
     */
    function wpsg_getUploadUrl($strPathKey = '', $htprotection = true) {

        $path = wpsg_getUploadDir($strPathKey, $htprotection);
        $upload_dir = \wp_upload_dir();        
        
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
                
    }

	/**
	 * Verschiebt eine hochgeladene Datei  
	 * @param $strPathkey Pfad unterhalb von Uploads
	 * @param $arFile array $_FILES
	 * @return true or false
	 */
	function wpsg_fileUpload($strPathKey = '', $arFile)
	{
		
		$path = wpsg_getUploadDir($strPathKey);
				
		if (wpsg_isSizedString($arFile['tmp_name']) && $arFile['size'] > 0 && file_exists($arFile['tmp_name']))
		{
			
			$res = move_uploaded_file($arFile['tmp_name'], $path.'/'.$arFile['name']);
			
		}

		return $res;
		
	} // function wpsg_fileUpload($strPathKey = '', $arFile)

?>