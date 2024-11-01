<?php

	/**
	 * Api steuert die Bezahlung per Vorkasse
	 *
	 * @copyright 2011 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Holger Heyne
	 * @version 1.0
	 * @created 2011-12-30 13:03:22
	 */
	interface IMcpPrepayService_v1_0 {

		/**
		 * l�scht alle Kunden und Transaktionen in der Testumgebung
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
		 * ordnet weitere freie Parameter dem Kunden zu, oder �ndert sie
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param array $freeParams (default=null)  Liste mit zus�tzlichen freien Parametern
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
		 * @result array $freeParams (default=null)  Liste mit allen freien Parametern
		 */
		public function customerGet($accessKey, $testMode=0, $customerId);

		/**
		 * ermittelt alle Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param integer $from (default=0)  Position des ersten auszugebenden Kunden
		 * @param integer $count (default=100)  Anzahl der auszugebenden Kunden
		 * 
		 * @return array 
		 * @result array $customerIdList Liste mit allen freien Parametern
		 * @result integer $count Anzahl der Kunden in der Liste
		 * @result integer $maxCount Gesamtanzahl aller Kunden
		 */
		public function customerList($accessKey, $testMode=0, $from=0, $count=100);

		/**
		 * erzeugt oder �ndert Adressdaten eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $form (default='NONE')  Anrede "NONE", "SIR", "MADAM", "MISS", "COMPANY"
		 * @param string $firstName Vorname
		 * @param string $surName Nachname
		 * @param string $address (default='')  Zus�tzliche Angaben z.B. "bei Schmidt"
		 * @param string $street Strasse und Hausnummer
		 * @param string $zip Postleitzahl
		 * @param string $city Ort
		 * @param string $country (default='DE')  Land
		 * 
		 * @return void 
		 */
		public function addressSet($accessKey, $testMode=0, $customerId, $form='NONE', $firstName, $surName, $address='', $street, $zip, $city, $country='DE');

		/**
		 * ermittelt die Adresse des Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result string $form Anrede
		 * @result string $firstName Vorname
		 * @result string $surName Nachname
		 * @result string $address Zus�tzliche Angaben
		 * @result string $street Strasse und Hausnummer
		 * @result string $zip Postleitzahl
		 * @result string $city Ort
		 * @result string $country Land
		 */
		public function addressGet($accessKey, $testMode=0, $customerId);

		/**
		 * erzeugt oder �ndert Kontaktdaten eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $email (default=null)  Emailadresse des Kunden
		 * @param string $phone (default=null)  Festnetzanschluss
		 * @param string $mobile (default=null)  Handynummer
		 * @param string $language (default=null)  Sprache
		 * 
		 * @return void 
		 */
		public function contactDataSet($accessKey, $testMode=0, $customerId, $email=null, $phone=null, $mobile=null, $language=null);

		/**
		 * ermittelt die Kontaktdaten des Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result string $email Emailadresse
		 * @result string $phone Festnetzanschluss
		 * @result string $mobile Handynummer
		 * @result string $language Sprache
		 */
		public function contactDataGet($accessKey, $testMode=0, $customerId);

		/**
		 * erzeugt einen neuen Bezahlvorgang
		 *  l�st die Benachrichtigung sessionStatus mit dem Status "INIT" aus
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $sessionId (default=null)  eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt
		 * @param string $project das Projektk�rzel f�r den Vorgang
		 * @param string $projectCampaign (default=null)  ein Kampagnenk�rzel des Projektbetreibers
		 * @param string $account (default=null)  Account des beteiligten Webmasters
		 * @param string $webmasterCampaign (default='')  ein Kampagnenk�rzel des Webmasters
		 * @param integer $amount (default=null)  abzurechnender Betrag in Cent, Standard aus Konfiguration
		 * @param string $currency (default='EUR')  W�hrung
		 * @param string $title (default=null)  Bezeichnung der zu kaufenden Sache, Standard aus Konfiguration
		 * @param string $payText (default=null)  Verwendungszweck f�r �berweisung, Standard Projektname und $title
		 * @param string $expireDays (default=21)  Ablauf der Session in Tagen, genauer Ablauf wird als $expireDate zur�ckgegeben
		 * @param string $ip (default=null)  IP des Benutzers
		 * @param array $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * 
		 * @return array 
		 * @result string $sessionId eigene oder erzeugte eindeutige ID des Vorgangs
		 * @result string $status Vorgangsstatus "INIT"
		 * @result integer $amount �bergebener Betrag bzw. Standard aus Konfiguration in Cent
		 * @result string $currency �bergebene W�hrung bzw. "EUR"
		 * @result string $title �bergebene Kaufsache bzw. Standard aus Konfiguration
		 * @result string $payToken Token, das Kunde bei �berweisung angeben muss
		 * @result string $payText Verwendungszweck, inkl. Token
		 * @result string $expireDate Ablaufdatum der Session, kann bis zu 2 Tagen l�nger sein als durch expireDays vorgegeben
		 * @result string $dueDate letzter �berweisungstermin f�r Kunden
		 * @result string $bankName Bank, an die �berwiesen werden soll
		 * @result string $bankCountry Land der Bank
		 * @result string $bankCode Bankleitzahl
		 * @result string $accountNumber Kontonummer
		 * @result string $accountHolder Kontoinhaber
		 * @result string $bic SWIFT BIC f�r ausl�ndische Kunden
		 * @result string $iban IBAN
		 */
		public function sessionCreate($accessKey, $testMode=0, $customerId, $sessionId=null, $project, $projectCampaign=null, $account=null, $webmasterCampaign='', $amount=null, $currency='EUR', $title=null, $payText=null, $expireDays=21, $ip=null, $freeParams=null);

		/**
		 * ordnet weitere freie Parameter der Session zu, oder �ndert sie
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param array $freeParams (default=null)  Liste mit zus�tzlichen freien Parametern
		 * 
		 * @return void 
		 */
		public function sessionSet($accessKey, $testMode=0, $sessionId, $freeParams=null);

		/**
		 * ermittelt Daten eines Bezahlvorgangs
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return array 
		 * @result string $status Vorgangsstatus "INIT", "OPEN", "PAID", "OVERPAID", "CLOSED"
		 * @result string $customerId ID des Kunden
		 * @result string $project zugeordnetes Projekt
		 * @result string $projectCampaign zugeordnete Projektkampagne
		 * @result string $account zugeordneter Webmasteraccount
		 * @result string $webmasterCampaign zugeordnete Webmasterkampagne
		 * @result integer $amount �bergebener Betrag bzw. Standard aus Konfiguration in Cent
		 * @result integer $orderAmount geforderter Betrag der Session
		 * @result integer $paidAmount bereits bezahlter Betrag
		 * @result integer $openAmount offener bzw. �berzahlter (negativer) Betrag, Differenz aus orderAmount und paidAmount
		 * @result string $currency �bergebene W�hrung bzw. "EUR"
		 * @result string $title �bergebene Kaufsache bzw. Standard aus Konfiguration
		 * @result string $payToken Token, das Kunde bei �berweisung angeben muss
		 * @result string $payText Verwendungszweck f�r �berweisung
		 * @result string $expireDate Ablaufdatum
		 * @result string $dueDate letzter �berweisungstermin
		 * @result string $bankName Bank
		 * @result string $bankCode Bankleitzahl
		 * @result string $accountNumber Kontonummer
		 * @result string $accountHolder Kontoinhaber
		 * @result string $bic BIC
		 * @result string $iban IBAN
		 * @result string $ip IP des Benutzers
		 * @result array $freeParams (default=null)  Liste mit allen freien Parametern
		 */
		public function sessionGet($accessKey, $testMode=0, $sessionId);

		/**
		 * ermittelt alle Bezahlvorg�nge eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result integer $count Anzahl der Eintr�ge in sessionIdList
		 * @result array $sessionIdList 0-indizierte Liste mit Vorgang-IDs
		 */
		public function sessionList($accessKey, $testMode=0, $customerId);

		/**
		 * Veranlasst eine Minderung des Betrags und ggf. eine (Teil-)Gutschrift
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID der zugeh�rigen Session
		 * @param integer $amount Minderung der Forderung, als positiver Betrag
		 * 
		 * @return array 
		 * @result string $status ggf. neuer Status, "INIT", "OPEN", "PAID", "OVERPAID", "CLOSED"
		 * @result integer $orderAmount neuer geforderter Betrag
		 * @result integer $paidAmount insgesamt gezahlter Betrag
		 * @result integer $openAmount offener bzw. �berzahlter Betrag
		 */
		public function sessionChange($accessKey, $testMode=0, $sessionId, $amount);

		/**
		 * simuliert einen Zahlungeingang f�r eine oder mehrere Sessions
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * @param string $sessionId ID der zugeh�rigen Session
		 * @param integer $amount gezahlter Betrag
		 * @param string $bankCountry (default='DE')  Land der Bank
		 * @param string $bankCode (default=null)  Bankleitzahl des Kunden
		 * @param string $accountNumber (default=null)  Kontonummer des Kunden
		 * @param string $accountHolder (default=null)  Kontoinhaber des Kunden
		 * 
		 * @return null 
		 */
		public function sessionPayinTest($accessKey, $testMode=0, $sessionId, $amount, $bankCountry='DE', $bankCode=null, $accountNumber=null, $accountHolder=null);

		/**
		 * simuliert das Ausl�sen einer Erinnerungsmail
		 *  l�st die Benachrichtigung sessionRemind aus
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * @param string $sessionId ID des Vorgangs
		 * @param bool $lastRemind (default=false)  Letzte Erinnerung
		 * 
		 * @return null 
		 */
		public function sessionRemindTest($accessKey, $testMode=0, $sessionId, $lastRemind=false);

		/**
		 * simuliert den Ablauf einer Session
		 *  l�st die Benachrichtigung sessionStatus mit dem Status "CLOSED" aus
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return null 
		 */
		public function sessionExpireTest($accessKey, $testMode=0, $sessionId);

		/**
		 * simuliert die automatische R�ck�berweisung f�r �berzahlte Betr�ge
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  muss 1 sein
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return null 
		 */
		public function sessionRefundTest($accessKey, $testMode=0, $sessionId);

		/**
		 * ermittelt alle Transaktionen f�r einen Bezahlvorgang
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * 
		 * @return array 
		 * @result integer $count Anzahl der Eintr�ge in transactionIdList
		 * @result array $transactionIdList 0-indizierte Liste mit Transaktions-IDs
		 */
		public function transactionList($accessKey, $testMode=0, $sessionId);

		/**
		 * ermittelt Daten einer Transaktion
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $transactionId ID des Vorgangs
		 * 
		 * @return array 
		 * @result string $sessionId ID des Vorgangs
		 * @result string $date Datum der Transaktion
		 * @result string $type Art der Transaktion "CREATE", "PAYIN", "CHANGE", "REFUND", "EXPIRE"
		 * @result integer $orderAmount Ver�nderung des geforderten Betrags
		 * @result integer $paidAmount Ver�nderung des bezahlten Betrags
		 * @result integer $openAmount (default=null)  Ver�nderung des offenen Betrags
		 */
		public function transactionGet($accessKey, $testMode=0, $transactionId);

	}

?>