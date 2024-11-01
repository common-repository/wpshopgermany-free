<?php

	/**
	 * Klasse zur Generierung von GiroCode QR Codes 
	 * @author Daschmi (daschmi@daschmi.de)
	 * Version 1.1 (07.06.2015)
	 * 
	 * http://daschmi.de
	 * https://www.girocode.de/wp-content/uploads/2014/06/GiroCode_EPC_Standard.pdf
	 */
	class PhpGirocode
	{
		
		const OUTPUT_BROWSER = 1;
		const OUTPUT_FILE = 2;
		const OUTPUT_BASE64 = 3;
		const OUTPUT_TEST = 4;
		
		/** Servicekennung 
		 * Beginnen die aus einem QR-Code extrahierten Daten mit der Zeichenfolge BCD gefolgt von einer Zeilenschaltung kann für die weitere Prüfung der Daten davon
		 *  ausgegangen werden, dass ein Datensatz zur Zahlungsinitiierung vorliegt.
		 * @var String 
		 * */
		private $serviceidentifier = "BCD"; 
		
		/** Version
		 * @var String 
		 * */
		private $version = "001";
		
		/** 
		 * Kodierung 
		 * 1 = UTF-8
		 * 2 = ISO 8859-1
		 * 3 = ISO 8859-2
		 * 4 = ISO 5589-4
		 * 5 = ISO 8859-5
		 * 6 = ISO 8859-7
		 * 7 = ISO 8859-10
		 * 8 = ISO 8859-15
		 * @var String
		 * */
		private $encoding = "1";
		
		/**
		 * Funktion
		 * Die Funktion wird durch den Schlüsselwert definiert: SCT - SEPA Credit Transfer
		 * @var String
		 */
		private $function = "SCT";
		
		/**
		 * BIC
		 * Länge: 8/11  
		 * @var String
		 */
		private $bic = "";
		
		/**
		 * Name Kontoinhaber
		 * Länge: 70
		 * @var String
		 */
		private $reciver = "";
				
		/**
		 * IBAN Nummer des Empfängers
		 * Länge: 34
		 * @var String
		 */
		private $iban = "";
		 
		/**
		 * Betrag
		 * Länge: 12
		 * Der Betrag ist ein empfohlenes, jedoch kein zwingend zu füllendes Feld. Bei fehlenden Beträgen ist, wie bei betragsoffenen Überweisungsbelegen, die Eingabe eines Betrags vorzusehen.
		 * Der Betrag ist maximal 999.999.999,99, hat maximal 2 Nachkommastellen, den Punkt als Dezimaltrennzeichen und wird unmittelbar nach dem dreibuchstabigen Währungscode in Großbuchstaben angegeben.
		 * Zur Verfügung steht als Währung ausschließlich EUR. Die Betragsdarstellung ist mit Rücksicht auf die Codegröße möglichst kurzzuhalten, z.B. besser EUR3 als EUR3.00. Vornullen sind nicht erlaubt.
		 * @var double
		 */
		private $amount = 0;
				
		/**
		 * Verwendungstext
		 * Länge: 140
		 * @var unknown_type
		 */
		private $text = "";
		
		/**
		 * Sammeln von Fehlermeldungen
		 * @var Array
		 */
		private $arError = array();
		
		/**
		 * Fehlerkorrektur-Level
		 * @var String
		 */
		private $errCorrLevel = "M";

		/**
		 * QRCode-Größe
		 * @var String
		 */
		private $qrsize = 4;
		
		/**
		 * Pfad für temporäre Dateien
		 * @var String
		 */
		private $strTmpPath = "/tmp";
		
		public function __construct()
		{
			
			$this->strTmpPath = sys_get_temp_dir();
			
		} // public function __construct()
		
		public function setTmpPath($path)
		{
			
			$this->strTmpPath = $path;
			
		} // public function setTmpPath($path)
		
		public function getTmpPath()
		{
			
			return $this->strTmpPath;
			
		} // public function getTmpPath()
		
		/**
		 * Setzt die Codierung
		 */
		public function setEncoding($strEncoding)
		{
			
			switch (strtolower($strEncoding))
			{

				case 'utf-8': $this->encoding = 1; break;
				case 'iso 8859-1': $this->encoding = 2; break;
				case 'iso 8859-2': $this->encoding = 3; break;
				case 'iso 8859-4': $this->encoding = 4; break;
				case 'iso 8859-5': $this->encoding = 5; break;
				case 'iso 8859-7': $this->encoding = 6; break;
				case 'iso 8859-10': $this->encoding = 7; break;
				case 'iso 8859-15': $this->encoding = 8; break;
				
				default: $this->handleError(1);
				
			}
			
		} // public function setEncoding($strEncoding)

		/**
		 * Setzt die BIC für die Überweisung
		 */
		public function setBIC($strBIC) 
		{

			if (!in_array(strlen($strBIC), array(8, 11)))
			{
				
				$this->handleError(2);
				
			}
			else
			{
				
				$this->bic = strtoupper($strBIC);
				
			}
			
		} // public function setBIC($strBIC)
		
		/**
		 * Setzt den Empfänger
		 */
		public function setReciver($strReciver)
		{
			
			if (strlen($strReciver) <= 0) $this->handleError(3); 
			else 
			{
				
				$this->reciver = substr($strReciver, 0, 70);
				
			}
			
		} // public function setReciver($strReciver)

		/**
		 * Setzt den Empfänger
		 */
		public function setErrCorrLevel($level)
		{
			
			$this->errCorrLevel = substr($level, 0, 1);
			
		} // public function setErrCorrLevel($level)
		
		/**
		 * Gibt die IBAN zurück
		 */
		public function getIBAN()
		{
			
			return $this->iban;
			
		} // public function getIBAN()
		
		/**
		 * Setzt die IBAN
		 */
		public function setIBAN($strIBAN)
		{
			
			if (strlen($strIBAN) > 34) $this->handleError(4);
			else
			{
				
				$this->iban = strtoupper(preg_replace('/\040/', '', $strIBAN));
				
			}
			
		} // public function setIBAN($strIBAN)
		
		/**
		 * Berechnet die IBAN aus Kontonummer und BLZ
		 */
		public function calculateIBANBIC($strKontonummer, $strBLZ, $strCountry = "DE")
		{
			
			$string = $strBLZ.$strKontonummer.$this->getAlphanumericInt(substr(strtoupper($strCountry), 0, 1)).$this->getAlphanumericInt(substr(strtoupper($strCountry), 1, 1)).'00';
			$pz = 98 - bcmod($string, 97);
			
			$this->iban = strtoupper($strCountry).$pz.$strBLZ.$strKontonummer;				
			
			$strFile = dirname(__FILE__).'/data/blzbic.txt';
			
			if (!file_exists($strFile))
			{
				
				$this->handleError(8);
				
			}
			else
			{
				
				$bankInfo = $this->readBLZBICFile($strFile, $strBLZ);
				
				if ($bankInfo === false) $this->handleError(9);
				else
				{
				
					$this->bic = $bankInfo['bic'];
					
				}
				
			}
			
		} // public function calculateIBANBIC($strKontonummer, $strBLZ)
		
		/**
		 * Setzt den Betrag für die Überweisung 
		 */
		public function setAmount($dAmount)
		{
			
			$value = $this->toFloat($dAmount);
			
			if ($value <= 0) $this->handleError(5);
			else
			{
				
				$this->amount = $value;
				
			}
			
		} // public function setAmount($dAmount)
		
		/**
		 * Setzt den Text
		 * @param String $strText
		 */
		public function setText($strText)
		{
			
			if (strlen($strText) > 140) $this->handleError(6);
			else
			{
				
				$this->text = $strText;
				
			}
			
		} // public function setText($strText)
		
		/**
		 * Setzt die QR-Code-Größe
		 * @param Integer $size
		 */
		public function setSize($size)
		{
			
			$this->qrsize = $size;
			
		} // public function setText($strText)
		
		
		/**
		 * Prüft die IBAN auf Validität
		 * @return true|false
		 */
		public function isValidIBAN()
		{
			
			if (!preg_match('/^[A-Z]{2}\d*/', $this->iban))
			{
				
				$this->handleError(7);
				
				return false;
				
			}
			else
			{
				
				$string = substr($this->iban, 4).$this->getAlphanumericInt(substr($this->iban, 0, 1)).$this->getAlphanumericInt(substr($this->iban, 1, 1)).substr($this->iban, 2, 2);

				if (bcmod($string, 97) === '1')
				{
					
					return true;
					
				}
				else
				{
					
					$this->handleError(11);
					return false;
					
				}
								
			}
			
		} // public function isValidIBAN()
				
		/**
		 * Generiert den Barcode
		 */
		public function generate($out = self::OUTPUT_BROWSER, $file = false)
		{
			 				
			$arField = array();
			
			$arField[1] = $this->serviceidentifier;
			$arField[2] = $this->version;
			$arField[3] = $this->encoding;
			$arField[4] = $this->function;
			$arField[5] = $this->bic;
			$arField[6] = $this->reciver;
			$arField[7] = $this->iban;						
			
			//if ($this->amount > 0) $arField[8] = "EUR".round($this->amount, 2); else $arFild[] = '';
			if ($this->amount > 0) $arField[8] = "EUR".sprintf('%.2f', $this->amount); else $arFild[] = '';
			
			$arField[9] = '';
			$arField[10] = '';
			
			if (strlen($this->text) > 0) $arField[11] = $this->text; else $arField[11] = '';			
			$arField[12] = '';
						
			// public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false)
			
			//$code = implode("\n", $arField);
			$code = implode(PHP_EOL, $arField);
			
			if ($out === self::OUTPUT_BROWSER)
			{
				
				QRcode::png($code, false, $this->errCorrLevel, $this->qrsize);
				
			}
			else if ($out === self::OUTPUT_FILE)
			{
				
				if (strlen($file) <= 0) $this->handleError(10);
				else 
				{
					
					if (!file_exists($file)) touch($file);
					
					QRcode::png($code, $file, $this->errCorrLevel, $this->qrsize);
					
				}
				
			}
			else if ($out === self::OUTPUT_BASE64)
			{
				
				$tmpfname = @tempnam("/tmp", "phpgirocode");
				
				QRcode::png($code, $tmpfname, $this->errCorrLevel, $this->qrsize);
				
				return base64_encode(file_get_contents($tmpfname));
				
			}
			else if ($out === self::OUTPUT_TEST)
			{
				
				return print_r($arField, 1);
				
			}
			
		} // public function generate()
		
		public function getMatchingBLZBIC($search)
		{
			
			$filename = dirname(__FILE__).'/data/blzbic.txt';
				
			if (!file_exists($filename))
			{
			
				$this->handleError(8);
			
			}
			else
			{
			
				$arData = array();
								
				$handle = fopen($filename, "r");
				
				while (($line = fgets($handle, 4096)) !== false)
				{
					
					$arRow = $this->explodeRow($line);
					 
					if (strpos($arRow['blz'], $search) !== false || strpos($arRow['bic'], $search) != false || strpos($arRow['name'], $search) != false)
					{
						
						$arData[] = $arRow;
						
					}
					
				}
				
				return $arData;
			
			}
			
		}
		
		private function explodeRow($line)
		{
			
			$arRow = array(
				'1' => substr($line, 0, 8), // Bankleitzahl
				'2' => substr($line, 8, 1), // Merkmal, ob bankleitzahlführender Zahlungsdienstleister („1“) oder nicht („2“)
				'3' => substr($line, 9, 58), // Bezeichnung des Zahlungsdienstleisters (ohne Rechtsform)
				'4' => substr($line, 67, 5),
				'5' => substr($line, 72, 35),
				'6' => substr($line, 107, 27), // Kurzbezeichnung des Zahlungsdienstleisters mit Ort (ohne Rechtsform)
				'7' => substr($line, 134, 5), // Institutsnummer für PAN
				'8' => substr($line, 139, 11),
				'9' => substr($line, 150, 2), // Knnzeichen für Prüfzifferberechnungsmethode
				'10' => substr($line, 152, 6), // Nummer des Datensatzes
				'11' => substr($line, 158, 1), // Änderungskennzeichen
				'12' => substr($line, 159, 1), // Hinweis auf beabsichtigte Bankleitzahllöschung
				'13' => substr($line, 160, 8), // Hinweis auf Nachfolge-Bankleitzahl
				'14' => substr($line, 168, 6) // Kennzeichen für die IBAN-Regel (nur erweiterte Bankleitzahlendatei)
			);
			
			$arRow['blz'] = $arRow[1];
			$arRow['name'] = $arRow[3];
			$arRow['plz'] = $arRow[4];
			$arRow['ort'] = $arRow[5];
			$arRow['bic'] = $arRow[8];
			
			return $arRow;
			
		} // private function explodeRow($row)
		
		/**
		 * Liest die BLZBic Infodatei und gibt Informationen anhand der BLZ oder BIC zurück
		 * http://www.bundesbank.de/Redaktion/DE/Downloads/Aufgaben/Unbarer_Zahlungsverkehr/Bankleitzahlen/merkblatt_bankleitzahlendatei.pdf?__blob=publicationFile
		 * http://www.bundesbank.de/Redaktion/DE/Standardartikel/Aufgaben/Unbarer_Zahlungsverkehr/bankleitzahlen_download.html
		 */
		private function readBLZBICFile($filename, $searchBLZ = false, $searchBIC = false)
		{
			
			if ($searchBLZ === false && $searchBIC === false) return array();
			
			$handle = fopen($filename, "r");
			 
			while (($line = fgets($handle, 4096)) !== false) 
			{
				
				$line = trim($line);
								
				$arRow = $this->explodeRow($line);
				
				// Nur Hauptsitze erfassen
				if (trim($arRow['8']) === '') continue;
												 
				if ($arRow['bic'] == $searchBIC || $arRow['blz'] == $searchBLZ) return $arRow;
				
			}
			
			return false;
			
		} // private function readBLZBICFile($file, $searchBLZ = false, $searchBIC = false)
		
		/**
		 * Gibt die Ganzzahl für einen Buchstaben zurück für Prüfsummenberechnung
		 */
		private function getAlphanumericInt($char)
		{
			
			return (ord($char) - 55);
			
		} // private function getAlphanumericInt($char)
		
		/**
		 * Versucht Nutzereingaben in valide Floatwerte zu wandeln
		 */
		private function toFloat($value)
		{
				
			// Alles außer Zahlen, Punkt und Komma entfernen
			$value = preg_replace('/[^\d|^\.|^\,|^\-]/', '', $value);
		
			if (strpos($value, ".") && strpos($value, ","))
			{
					
				// , und . drin
				if (strpos($value, ",") > strpos($value, "."))
				{
		
					//1.123,23
					return floatval(str_replace(",", ".", str_replace(".", "", $value)));
		
				}
				else
				{
		
					//1,234.23
					return floatval(str_replace(",", "", $value));
		
				}
					
			}
		
			return floatval(str_replace(",", ".", $value));
			
		} // function toFloat($value)
		
		/**
		 * Fehlerbehandlung
		 */
		private function handleError($errorCode)
		{
			
			$strError = "Ungültiger Fehlercode";
			
			switch ($errorCode)
			{
				
				case 1: $strError = 'Ungültige Zeichenkodierung'; break;
				case 2: $strError = 'Ungültige BIC'; break;
				case 3: $strError = 'Kein Epfänger angegeben'; break;
				case 4: $strError = 'Ungültige IBAN angebeen'; break;		
				case 5: $strError = 'Kein Betrag angegeben'; break;
				case 6: $strError = 'Text ist zu lang, es sind maximal 140 Zeichen möglich'; break;
				case 7: $strError = 'IBAN hat ein ungültiges Format'; break;
				case 8: $strError = 'BLZ/BIC Datendatei existiert nicht'; break;
				case 9: $strError = 'BLZ wurde in Bankdatei nicht gefunden'; break;
				case 10: $strError = 'Datei für Ausgabe kann nicht geschrieben werden'; break;
				case 11: $strError = 'Prüfziffer der IBAN stimmt nicht'; break;
								
			}
			
			$this->arError[] = $strError;
						
		} // private function handleError($errorCode)
		
		public function hasError()
		{
			
			return ((sizeof($this->arError) > 0)?true:false);
			
		} // private function hasError()
		
		public function getError()
		{
			
			$arReturn = $this->arError;
			
			$this->arError = array();
		 
			return $arReturn;
			
		} // private function getError()
		
	}

?>