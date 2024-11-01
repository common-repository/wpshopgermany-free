<?php

	/**
	 * Klasse die die vom Shop verwendeten Datenbankfunktionen kapselt
	 */
	class wpsg_db
	{
		
		/**
		 * Fehlerbehandlung
		 */
		function handleError()
		{
		
			global $wpdb;
		
			$lastQuery = $wpdb->last_query;
			
			throw new \wpsg\Exception($wpdb->last_error, \wpsg\Exception::TYP_DB, array(
				array(__('Letzter Query', 'wpsg'), $lastQuery)
			));
		
		} // function handleError()
		
		/**
		 * Gibt eine einzelne Zelle aus der Datenbank zurück
		 */
		function fetchOne($strQuery) {
			
			global $wpdb;

			if ($wpdb->query($strQuery) === false) {
				
				$this->handleError();
				
			}
			else
			{
			
				$result = $wpdb->get_var($strQuery);
 
				return $result;
				
			} 
			
		} // function fetchOne($strQuery)
		
		/**
		 * Gibt eine ganze Zeile als Ergebnis aus der Datenbank zurück
		 */
		function fetchRow($strQuery)
		{
			
			global $wpdb;
			 
			if ($wpdb->query($strQuery) === false)
			{
			
				$this->handleError();
			
			}
			else
			{
					
				$result = $wpdb->get_row($strQuery, ARRAY_A);
			 
				return $result;
			
			} 
			
		} // function fetchRow($strQuery)
		
		/**
		 * Gibt mehrere Zeilen aus einer Tabelle als Array von Arrays zurück
		 * Ist der Parameter $key ungleich zu false, so ist der Schlüssel des Arrays die in $key übergebene Spalte 
		 */
		function fetchAssoc($strQuery, $key = false)
		{
			
			global $wpdb;
			
			if ($wpdb->query($strQuery) === false)
			{
					
				$this->handleError();
					
			}
			else
			{
			
				$arReturn1 = $wpdb->get_results($strQuery, ARRAY_A);
				
				if ($key != false)
				{
					
					$arReturn = array();
					
					foreach ($arReturn1 as $k => $v)
					{
						
						$arReturn[$v[$key]] = $v;
						
					}
					
					return $arReturn;
					
				}
				else
				{
					
					return $arReturn1;
					
				} 
				
			}
			
		} // function fetchAssoc($strQuery)
		
		/**
		 * Liefert eine Spalte eines Querys als Array zurück
		 * Der Parameter strKeyField ist die Spalte für den Schlüssel des Arrays (Sollte eindeutig sein)
		 * Der Parameter strValueField ist die Spalte für den Wert des Arrays 
		 */
		function fetchAssocField($strQuery, $strKeyField = false, $strValueField = false)
		{
		
			global $wpdb;
			
			if ($wpdb->query($strQuery) === false)
			{
					
				$this->handleError();
					
			}
			else
			{
			
				$db_rows = $wpdb->get_results($strQuery, ARRAY_A);
				$arReturn = array();			
				
				foreach ($db_rows as $row)
				{
					 
					if ($strKeyField != false && $strValueField != false)
						$arReturn[$row[$strKeyField]] = $row[$strValueField];
					else
						$arReturn[] = reset($row);
					
				} 
				
				return $arReturn;
				
			}
			
		} // function fetchAssocField($strQuery, $strField)
		
		/**
		 * Importiert die Daten aus $data als neue Zeile in die Tabelle $table
		 * $data muss dabei aus einem Schlüssel/Wert Array bestehen
		 * Der Rückgabewert ist die ID des eingefügten Datensatzes
		 */
		function ImportQuery($table, $data, $checkCols = false)
		{
			
			global $wpdb;
			
			/**
			 * Wenn diese Option aktiv ist, so werden Spalten nur importiert
			 * wenn sie auch in der Zieltabelle existieren.
			 */
			if ($checkCols === true)
			{
				
				$arFields = $this->fetchAssoc("SHOW COLUMNS FROM `".wpsg_q($table)."` ");
				
				$arCols = array();				
				foreach ($arFields as $f) { $arCols[] = $f['Field']; }				
				foreach ($data as $k => $v) { if (!in_array($k, $arCols)) { unset($data[$k]); } }
				
			}
			
			if (!wpsg_isSizedArray($data)) return false;
			
			// Query zusammenbauen
			$strQuery = "INSERT INTO `".wpsg_q($table)."` SET ";
			
			foreach ($data as $k => $v)
			{
				
				if ($v != "NOW()" && $v != "NULL") $v = "'".$v."'";
					
				$strQuery .= "`".$k."` = ".$v.", ";
				
			}
			
			$strQuery = substr($strQuery, 0, -2);
			
			$res = $wpdb->query($strQuery);

			if ($res === false)
			{
				 
				$this->handleError();
				
			}			
			else
			{
						
				return $wpdb->insert_id;
				
			}
			
		} // function ImportQuery($table, $data)
				
		/**
		 * Aktualisiert Zeilen in der Datenbank anhand des $where Selectse
		 */
		function UpdateQuery($table, $data, $where, $checkCols = false)
		{
			
			global $wpdb;

            /**
             * Wenn diese Option aktiv ist, so werden Spalten nur importiert
             * wenn sie auch in der Zieltabelle existieren.
             */
            if ($checkCols === true)
            {

                $arFields = $this->fetchAssoc("SHOW COLUMNS FROM `".wpsg_q($table)."` ");

                $arCols = array();
                foreach ($arFields as $f) { $arCols[] = $f['Field']; }
                foreach ($data as $k => $v) { if (!in_array($k, $arCols)) { unset($data[$k]); } }

            }
            
            if (!wpsg_isSizedArray($data)) throw new \Exception(__('Update ohne Daten?', 'wpsg'));
			
			// Query aufbauen, da wir den kompletten QueryWHERE String als String übergeben
			$strQuery = "UPDATE `".wpsg_q($table)."` SET ";
			
			foreach ($data as $k => $v)
			{
				
				if ($v != "NOW()" && $v != "NULL") $v = "'".$v."'";
					
				$strQuery .= "`".$k."` = ".$v.", ";
				
			}
		 
			$strQuery = substr($strQuery, 0, -2)." WHERE ".$where;
			
			$res = $wpdb->query($strQuery);

			if ($res === false)
			{
				 
				$this->handleError();
				
			}
			
			return $res;
			
		} // function UpdateQuery($table, $data, $where)
				
		/**
		 * Gibt den nächsten AUTO_INCREMENT Wert einer Tabelle zurück
		 */
		function getNextAutoincrementValue($table)
		{
			
			$result = $this->fetchRow("SHOW TABLE STATUS LIKE '".$table."'");
			
			return $result['Auto_increment'];
			
		} // function getNextAutoincrementValue($table)
		
		/**
		 * Führt einen Query aus. Z.b. für Delete Querys
		 */
		function Query($strQuery)
		{
			
			global $wpdb;
			
			$res = $wpdb->query($strQuery);
			
			if ($res === false)
			{
				
				$this->handleError();
				
			}
			 			
		} // function Query($strQuery)
	 		
		/**
		 * Entsperrt alle Tabellen
		 */
		function unlockTables()
		{
			
			$this->Query("UNLOCK TABLES");
 			
 		} // function unlockTables()
		
	} // class wpsg_db

?>