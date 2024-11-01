<?php

	/**
	 * Api steuert die Bezahlung per Lastschrift
	 *
	 * @copyright 2011 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Holger Heyne
	 * @version 1.0
	 * @created 2011-02-22 18:42:35
	 */
	interface IMcpDebitService_v1_0 {

		/**
		 * löscht alle Kunden und Transaktionen in der Testumgebung
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode Muss 1 sein
		 * 
		 * @return void 
		 */
		public function resetTest($accessKey, $testMode);

		/**
		 * legt neuen Kunden an
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eigene eindeutige ID des Kunden, wird anderenfalls erzeugt
		 * @param array $freeParams (default=null)  Liste mit freien Parametern, die dem Kunden zugeordnet werden
		 * 
		 * @return array 
		 * @result string $customerId eigene oder erzeugte eindeutige ID des Kunden
		 */
		public function customerCreate($accessKey, $testMode=0, $customerId=null, $freeParams=null);

		/**
		 * ordnet weitere freie Parameter dem Kunden zu, oder ändert sie
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param array $freeParams (default=null)  Liste mit zusätzlichen freien Parametern
		 * 
		 * @return void 
		 */
		public function customerSet($accessKey, $testMode=0, $customerId, $freeParams=null);

		/**
		 * ermittelt alle freien Parameter des Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result array $freeParams Liste mit allen freien Parametern
		 */
		public function customerGet($accessKey, $testMode=0, $customerId);

		/**
		 * erzeugt oder ändert Bankverbindung eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $country (default='DE')  Sitz der Bank
		 * @param string $bankCode Bankleitzahl
		 * @param string $accountNumber Kontonummer
		 * @param string $accountHolder Kontoinhaber
		 * 
		 * @return array 
		 * @result string $bankName der ermittelte Name der Bank
		 */
		public function bankaccountSet($accessKey, $testMode=0, $customerId, $country='DE', $bankCode, $accountNumber, $accountHolder);

		/**
		 * ermittelt die Bankverbindung des Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result string $country Sitz der Bank
		 * @result string $bankCode Bankleitzahl
		 * @result string $bankName Name der Bank
		 * @result string $accountNumber Kontonummer
		 * @result string $accountHolder Kontoinhaber
		 */
		public function bankaccountGet($accessKey, $testMode=0, $customerId);

		/**
		 * erzeugt einen neuen Bezahlvorgang
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $sessionId (default='')  eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt
		 * @param string $project das Projektkürzel für den Vorgang
		 * @param string $projectCampaign (default='')  ein Kampagnenkürzel des Projektbetreibers
		 * @param string $account (default='')  Account des beteiligten Webmasters
		 * @param string $webmasterCampaign (default='')  ein Kampagnenkürzel des Webmasters
		 * @param integer $amount (default=0)  abzurechnender Betrag in Cent
		 * @param string $currency (default='EUR')  Währung
		 * @param string $title (default='')  Bezeichnung der zu kaufenden Sache
		 * @param string $payText (default='')  Abbuchungstext der Lastschrift
		 * @param string $ip (default='')  IP des Benutzers
		 * @param array $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * 
		 * @return array 
		 * @result string $sessionId eigene oder erzeugte eindeutige ID des Vorgangs
		 * @result string $status Vorgangsstatus "INIT" oder "REINIT"
		 * @result string $expire Ablaufzeit der Bestätigung
		 */
		public function sessionCreate($accessKey, $testMode=0, $customerId, $sessionId='', $project, $projectCampaign='', $account='', $webmasterCampaign='', $amount=0, $currency='EUR', $title='', $payText='', $ip='', $freeParams=null);

		/**
		 * ermittelt Daten eines Bezahlvorgangs
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return array 
		 * @result string $status Vorgangsstatus "INIT", "REINIT", "EXPIRED", "APPROVED", "FAILED", "CHARGED" oder "REVERSED"
		 * @result string $expire Ablaufzeit bzw. Bestätigung des Vorgangs
		 * @result string $statusDetail Beschreibung für gescheiterte Transaktionen
		 * @result string $customerId ID des Kunden
		 * @result string $project zugeordnetes Projekt
		 * @result string $projectCampaign zugeordnete Projektkampagne
		 * @result string $account zugeordneter Webmasteraccount
		 * @result string $webmasterCampaign zugeordnete Webmasterkampagne
		 * @result integer $amount übergebener Betrag bzw. Standard aus Konfiguration in Cent
		 * @result string $currency übergebene Währung bzw. "EUR"
		 * @result string $title übergebene Kaufsache bzw. Standard aus Konfiguration
		 * @result string $payText Abbuchungstext der Lastschrift
		 * @result string $ip übergebene IP des Benutzers
		 * @result array $freeParams (default=null)  Liste mit allen freien Parametern
		 */
		public function sessionGet($accessKey, $testMode=0, $sessionId);

		/**
		 * bestätigt den Lastschrifteinzug eines Vorgangs
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return array 
		 * @result string $status Vorgangsstatus "APPROVED" oder "FAILED"
		 * @result string $expire Zeitpunkt der Bestätigung
		 */
		public function sessionApprove($accessKey, $testMode=0, $sessionId);

		/**
		 * ermittelt alle Bezahlvorgänge eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result integer $count Anzahl der Einträge in sessionIdList
		 * @result array $sessionIdList 0-indizierte Liste mit Vorgang-IDs
		 */
		public function sessionList($accessKey, $testMode=0, $customerId);

		/**
		 * simuliert die Abbuchung für alle bestätigten Vorgänge
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * 
		 * @return array 
		 * @result integer $count Anzahl der gebuchten Vorgänge
		 */
		public function sessionChargeTest($accessKey, $testMode=0);

		/**
		 * simuliert Stornierung eines einzelnen Vorgangs
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return void 
		 */
		public function sessionReverseTest($accessKey, $testMode=0, $sessionId);

	}

?>