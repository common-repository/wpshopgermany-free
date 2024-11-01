<?php

	/**
	 * Dieser Service stellt eine Server2Server-Schnittstelle (API), zur Buchung von Transaktionen �ber die Zahlungsart Kreditkarte, zur Verf�gung (Creditcard - API.Event).
	 * 
	 *   Vorraussetzungen:
	 *  - Ein Account bei micropayment(tm) - Anmeldung unter www.micropayment.de
	 *  - Die Freischaltung von Creditcard - API.Event durch unseren H�ndler-Support (support@micropayment.de)
	 *  - Eine Vertrags-Unternehmennummer (VU-Nummer) die Sie im ControlCenter beantragen k�nnen
	 * 
	 *  Konfiguration:
	 *  Nach der Freischaltung von Creditcard - API.Event durch unseren Support loggen Sie sich in das ControlCenter Ihres Accounts ein und begeben sich zu dem Men�punkt "Meine Konfiguration":
	 *  - hier finden Sie im unteren Bereich den `AccessKey` den Sie f�r die Verwendung des API ben�tigen
	 *  - im Untermen�punkt "APIs" konfigurien und aktivieren Sie Creditcard - API.Event
	 *  - im Untermen�punkt "Zugriffsberechtigungen" konfigurieren Sie bitte die IP-Adresse/IP-Range der Server, von dem aus Sie auf das API zugreifen m�chten
	 * 
	 *  Hinweis zu Hoch-Traffic-Anwendungen:
	 *  Grunds�tzlich kann ein Kreditkartenterminal immer nur eine Buchung zur selben Zeit ausf�hren.
	 *  Bei normalem Traffic f�ngt eine Warteschlange gleichzeitige Anfragen auf.
	 *  Wenn aber parallele Transaktionen erforderlich sind (z.B. bei Trafficspitzen), k�nnen Sie zur Lastverteilung bei unserem Support zus�tzliche Terminals ordern.
	 * 
	 *  Hinweise zum TestModus:
	 *  - Aktivierung der Testumgebung erfolgt in der jeweiligen Funktion �ber den Parameter `testMode`
	 *  - f�r den TestModus stehen folgende Test-Kartennummern zur Verf�gung:
	 * 	- VISA: 4111111111111111
	 * 	- MASTER: 5454545454545454
	 * 	- AMEX: 343434343434343
	 *  - um erfolgreiche Buchungen durchzuf�hren, ist als CVC2-Code die 666 zu verwenden, bei allen Anderen gilt die Transaktion als fehlgeschlagen
	 *  - um einen spezifischen Terminal-Fehlercode auszul�sen, ist als CVC2-Code die 555 zu verwenden:
	 *    Der als Integerwert (bspw. 534) angefordertet Endkundenbetrag, liefert im Eurocent-Anteil (bspw. 34) den Fehlercode (`transactionResultCode`) zur�ck - z.B. 534 => ipg34.
	 *  	 Bitte beachten Sie, dass das erwartete Ergebnis nur bei der Verwendung der W�hrung (`currency`) "EUR" erreicht wird.
	 * 
	 *  �bermittlung von Adressdaten:
	 *  Sollten Ihnen die Adressdaten Ihrer Endkunden zur Verf�gung stehen, empfehlen wir diese mittles "addressSet" im Kundendatensatz zu hinterlegen.
	 *  Die im Kundendatensatz (customerCreate, customerSet) hinterlegten Adressdaten, werden zum Einen f�r das Adress-Verifikations-System (AVS, verf�gbar ab Version 1.5) ben�tigt,
	 *  zum Anderen werden diese Daten bei Chargeback-Anfragen (s.g. Beleganforderungen von Issuer und Acquirerer) verwendet.
	 * 
	 *  Fehlercodes:
	 *  Eine �bersicht �ber die m�glichen Fehlercodes des API als auch des Terminals finden Sie unter folgender Adresse:
	 *  https://webservices.micropayment.de/public/creditcard/ERRORCODES/
	 * 
	 *  Updates/Changelog:
	 *  Wenn Sie bisher eine �ltere Version dieses APIs verwenden, k�nnten Sie sich im Changelog einen �berblick �ber die Neuerungen verschaffen:
	 *  https://webservices.micropayment.de/public/creditcard/CHANGELOG/
	 *
	 * @copyright 2011 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Yves Berkholz, Guido Franke
	 * @version 1.4.2
	 * @created 2011-10-11 21:40:22
	 */
	interface IMcpCreditcardService_v1_4_2 {

		/**
		 * Gibt eine Liste von Bezahlvorg�ngen anhand von Parametern zur�ck
		 * 
		 *  Folgende frei kombinierbare Parameter stehen zur Verf�gung:
		 *  - Kunde (customerId)
		 *  - Zeitraum von/bis (dtmFrom/dtmTo)
		 *  - SessionStatus (status)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eindeutige ID des Kunden oder [NULL] ohne Kundeneinschr�nkung
		 * @param datetime $dtmFrom (default=null)  Anfang der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * @param datetime $dtmTo (default=null)  Ende der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * @param SessionStatus $status (default=null)  Nur Sessions mit dem gew�nschten Status in das Ergebnisliste aufnehmen oder [NULL] um Statusfilter zu deaktivieren
		 * 
		 * @return string[] 
		 */
		public function sessionList($accessKey, $testMode=0, $customerId=null, $dtmFrom=null, $dtmTo=null, $status=null);

		/**
		 * Errechnet ein Scoring f�r einen zuvor angelegten Kunden, den zugeh�rigen Kreditkartendaten sowie der IP-Adresse und ggf. Http-Request-Headern des Kunden.
		 *  Daher ist es f�r die Verwendung sinnvoll m�glichst alle Daten zu erfassen um die Genauigkeit des Scorings zu erh�hen.
		 * 
		 *  Begrifflichkeiten:
		 *   E-Mail           E-Mail-Adressen aus Kundendaten
		 *   BillingCountry   Land des Kunden aus Adressendaten des Kunden [billCountry]
		 *   BillingLocation  Standort des Kunden aus Adressendaten des Kunden
		 *   BinCountry       Land der Kreditkartenbank (BIN-Code) [ccCountry]
		 *   IPCountry        Land durch IP ermittelt [ipCountry]
		 *   IPLocation       Standort ermittelt durch IP
		 * 
		 *  Wichtung  Wert
		 *     2.5    E-Mail (Anbieter von Tempor�ren E-Mail-Adressen / Freemail Provider > h�heres Risiko)
		 *     2.5    IPCountry != BillingCountry
		 *     2.0    BillingCountry != BinCountry
		 *     5.0    customerIp ist Anonymous-Proxy
		 *     2.5    customerIp ist bekannter Proxy
		 *    10.0    Entfernung IPLocation zu BillingLocation (h�here Entfernung = h�heres Risiko)
		 *     5.0    E-Mail steht auf Badlist (Hockrisiko)
		 *     5.0    BillingLocation ist bekannter Maildrop (Postfach etc.)
		 *     5.0    IPCountry oder BillingCountry sind Hochrisikol�nder - z.Z. sind das:
		 *              Egypt, Ghana, Indonesia, Lebanon, Macedonia, Morocco, Nigeria,
		 *              Pakistan, Romania, Serbia and Montenegro, Ukraine, Vietnam
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param string $customerIp IPv4 des Benutzers
		 * @param map $customerRequestHeader (default=null)  Liste mit Http-Request-Headern des Kunden - ausgewertet werden: User-Agent, Accept-Language sowie die Proxy-Header X-Forwarded-For/Client-Ip
		 * 
		 * @return struct 
		 * @result string $billCountry (default=null)  Land [ISO 3166-1-alpha-2] aus den Adressdaten des Kundensatzes bspw. DE, AT, CH
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result float $scoreValue (default=null)  Bewertung mit Werten zwischen 0 und 100, wobei 0 f�r geringes und 100 f�r hohes Risiko steht
		 * @result string $scoreExplanation (default=null)  Erl�uterung zu der Bewertung in Textform [Englisch]
		 */
		public function customerScoring($accessKey, $testMode=0, $customerId, $customerIp, $customerRequestHeader=null);

		/**
		 * Gibt Informationen �ber eine Transaktion anhand einer Transaktionsnummer zur�ck.
		 * 
		 *  Wenn sich `currencyInit` und `currencyBooked` unterscheiden, dann fehlt die native Unterst�tzung f�r die gew�nschte W�hrung.
		 *  In diesem Fall erfolgte eine Umrechnung in eine nativ unterst�tzte W�hrung.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $transactionId Transaktionsnummer
		 * 
		 * @return struct 
		 * @result string $transactionId Transaktionsnummer
		 * @result string $sessionId eindeutige ID des Vorgangs
		 * @result string $customerId ID des Kunden
		 * @result string $auth AuthCode
		 * @result integer $amountInit Betrag der abgerechnet werden sollte in currencyInit
		 * @result string $currencyInit W�hrungseinheit mit der abgerechnet werden sollte
		 * @result integer $amountBooked gebuchter Betrag in currencyBooked
		 * @result string $currencyBooked W�hrungseinheit mit der gebucht wurde
		 * @result integer $amountInternal abgerechneter Betrag in currencyInternal
		 * @result string $currencyInternal (default='EUR')  W�hrungseinheit f�r interne Verbuchung
		 * @result TransactionType $type Art der Transaktion
		 * @result TransactionStatus $status Status der Transaktion
		 * @result datetime $created Zeitpunkt der Transaktion
		 * @result string $ip IPv4 des Benutzers
		 * @result string $cardType (default=null)  Kartentyp, kann bei aktiviertem DataStorage-Feature null sein
		 * @result string $cardNumber (default=null)  partielle Kreditkartennummer [letzten 4 Stellen], kann bei aktiviertem DataStorage-Feature null sein
		 * @result integer $cardExpiryYear (default=null)  G�ltigkeits Jahr, kann bei aktiviertem DataStorage-Feature null sein
		 * @result integer $cardExpiryMonth (default=null) 	G�ltigkeits Monat, kann bei aktiviertem DataStorage-Feature null sein
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionGet($accessKey, $testMode=0, $transactionId);

		/**
		 * F�hrt eine Transaktion zur sofortigen Buchung, des durch `sessionCreate` definierten Betrages, durch.
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt haben,
		 *  sowie dem Kunden, unter Zuhilfenahme von `creditcardDataSet`, Kreditkartendaten zugewiesen haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, mu� min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionPurchase($accessKey, $testMode=0, $sessionId, $cvc2=null, $fraudDetection=1);

		/**
		 * F�hrt eine Transaktion zur Vorautorisierung, des durch `sessionCreate` definierten Betrages, durch.
		 * 
		 *  Der gew�nschten Betrag wird f�r Sie reserviert bis:
		 *  - Sie den Betrag mittels `transactionReversal` wieder freigeben
		 *  - Sie den Gesamt- oder Teilbetrag mit `transactionCapture` buchen
		 *  - die Reservierung nach ca. 14 Tagen automatisch wieder freigegeben wird
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt haben,
		 *  sowie dem Kunden, unter Zuhilfenahme von `creditcardDataSet`, Kreditkartendaten zugewiesen haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, mu� min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionAuthorization($accessKey, $testMode=0, $sessionId, $cvc2=null, $fraudDetection=1);

		/**
		 * F�hrt eine Transaktion zur Buchung (Gesamt- oder Teilbetrag) einer Vorautorisierung durch.
		 * 
		 *  Sie buchen den reservierte Betrag:
		 *  - In voller H�he (Gesamtbetrag) - der Parameter `amount` entf�llt ([NULL])
		 *  - Oder nur einen Teilbetrag - der Parameter `amount` enth�lt den gew�nschten Teilbetrag
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang,
		 *  sowie unter Verwendung von `transactionAuthorization` eine Vorautorisierung, erstellt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId (default=null)  Transaktionsnummer von "transactionAuthorization"
		 * @param integer $amount (default=null)  [NULL] - entspricht Betrag aus Vorautorisierung | wenn abweichend, der zu buchende Betrag <= Betrag aus Vorautorisierung
		 * @param string $currency (default=null)  W�hrung, falls abweichend von Originaltransaktion [nur relevant wenn `amount` abweichend]
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionCapture($accessKey, $testMode=0, $sessionId, $transactionId=null, $amount=null, $currency=null, $fraudDetection=1);

		/**
		 * Transaktion zur Buchung einer Gesamt- oder Teilr�ckzahlung einer den Kunden belastende Buchung.
		 * 
		 *  Sie erstatten den gebuchten Betrag:
		 *  - In voller H�he (Gesamtbetrag) - der Parameter `amount` entf�llt ([NULL])
		 *  - Oder nur einen Teilbetrag - der Parameter `amount` enth�lt den gew�nschten Teilbetrag
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt,
		 *  sowie unter Verwendung von `transactionPurchase` oder `transactionCapture` eine den Kunden belastende Buchung durchgef�hrt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zur�ckgebucht werden soll
		 * @param integer $amount (default=null)  zur�ckzubuchender Betrag, falls abweichend von Orginaltransaktion
		 * @param string $currency (default=null)  W�hrung, falls abweichend von Originaltransaktion [nur relevant wenn `amount` abweichend]
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionRefund($accessKey, $testMode=0, $sessionId, $transactionId, $amount=null, $currency=null, $fraudDetection=1);

		/**
		 * Transaktion zur Buchung einer nicht transaktionsbezogenen Gutschrift.
		 * 
		 *  Dies erm�glicht es Ihnen beliebige Betr�ge auf das Kreditkartenkonto einen Kunden zu �bertragen.
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt haben,
		 *  sowie dem Kunden, unter Zuhilfenahme von `creditcardDataSet`, Kreditkartendaten zugewiesen haben.
		 * 
		 *  HINWEIS:
		 *  Diese Funktionalit�t bedarf manueller Freischaltung, und wird vom buchbaren Volumen beschr�nkt
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param integer $amount (default=null)  zur�ckzubuchender Betrag, falls abweichend von Orginaltransaktion
		 * @param string $currency (default=null)  W�hrung, falls abweichend von Originaltransaktion [nur relevant wenn `amount` abweichend]
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $ccCountry (default=null)  Land [ISO 3166-1-alpha-2] der Kreditkarte wenn verf�gbar, bspw. DE, AT, CH
		 * @result string $ipCountry (default=null)  Land [ISO 3166-1-alpha-2] der IP wenn verf�gbar, bspw. GB, US
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionCredit($accessKey, $testMode=0, $sessionId, $amount=null, $currency=null, $fraudDetection=1);

		/**
		 * Zuordnung einer `dataStorageId` von einem Bestandskunden Ihres Systems zu einem Kunden (customerId) bei micropayment
		 * 
		 *  Wichtig:
		 *  Diese Funktion ist nur bei einem Wechsel von einem anderen DataStorage-Anbieter relevant und setzt den
		 *  Import der zugeh�rigen Kreditkartendaten vorraus separat beantragt und durchgef�hrt werden muss,
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $dataStorageId Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * 
		 * @return boolean 
		 */
		public function dataStorageIdImport($accessKey, $testMode=0, $customerId, $dataStorageId);

		/**
		 * Gibt die mit einem Kunden verkn�pften Kreditkartendaten zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result string $type (default=null)  Kartentyp
		 * @result string $number (default=null)  partielle Kreditkartennummer [letzten 4 Stellen]
		 * @result integer $expiryYear (default=null)  G�ltigkeits Jahr
		 * @result integer $expiryMonth (default=null) 	G�ltigkeits Monat
		 * @result boolean $cvc2Required Bei der n�chsten Buchung ist der CVC2-Code erforderlich
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 */
		public function creditcardDataGet($accessKey, $testMode=0, $customerId);

		/**
		 * Transaktion zur geb�hrenfreier Stornierung einer Buchung vor Kassenschnitt oder Freigabe von Vorautorisierungen.
		 * 
		 *  Die geb�hrenfreie Stornierung einer mit `transactionPurchase` durchgef�hrten Buchung muss am gleichen Tag vor Kassenschnitt um 24 Uhr erfolgen.
		 *  Die Freigabe einer mittels `transactionAuthorization` erstellten Vorautorisierungen hingegen kann jederzeit durchgef�hrt werden.
		 * 
		 *  Zur Durchf�hrung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt,
		 *  sowie unter Verwendung von `transactionAuthorization` eine Vorautorisierung oder mit `transactionPurchase` eine Buchung durchgef�hrt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zur�ckgebucht werden soll
		 * @param integer $fraudDetection (default=1)  de/aktiviert FraudDetection
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 * @result string $dataStorageId (default=null)  Wenn DataStorage-Feature aktiv, ID der Kreditkartendaten im DataStorage-Service
		 * @result string $transactionResultCode Transaktionergebnis-Code vom Terminal
		 * @result string $transactionResultMessage Transaktionergebnis-Nachricht vom Terminal
		 */
		public function transactionReversal($accessKey, $testMode=0, $sessionId, $transactionId, $fraudDetection=1);

		/**
		 * Gibt eine Liste der, vom System durch Umrechnung, unterst�tzten W�hrungen zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * 
		 * @return string[] 
		 */
		public function currenciesGetSupported($accessKey, $testMode=0);

		/**
		 * Gibt eine Liste der, von Ihrem Account, nativ unterst�tzten W�hrungen, optional f�r einen bestimmten Kartentyp, zur�ck.
		 * 
		 *  Hinweis:
		 *  Die native Unterst�tzung weiterer W�hrungen erhalten Sie durch die Beantragung zus�tzlicher VU-Nummern.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param CardType $cardtype (default=null)  Typ der Kreditkarte
		 * 
		 * @return string[] 
		 */
		public function currenciesGetNativeSupported($accessKey, $testMode=0, $cardtype=null);

		/**
		 * �berpr�fung von Kreditkarten-Daten (Nummer und Ablaufdatum) auf G�ltigkeit
		 * 
		 *  - syntaktischen �berpr�fung
		 *  - Luhn-Check
		 *  - �berschreitung des Ablaufdatums
		 * 
		 *  Hinweis: Es findet keine Online-�berpr�fung der Daten statt!
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $number Kreditkartennummer
		 * @param integer $expiryYear G�ltigkeits Jahr
		 * @param integer $expiryMonth G�ltigkeits Monat
		 * 
		 * @return boolean Kreditkarten-Daten sind syntaktisch g�ltig
		 */
		public function creditcardCheckValidity($accessKey, $testMode=0, $number, $expiryYear, $expiryMonth);

		/**
		 * L�scht alle im Testmodus �bertragenen Daten
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=1)  aktiviert Testumgebung
		 * 
		 * @return boolean 
		 */
		public function resetTest($accessKey, $testMode=1);

		/**
		 * Versendet eine Benachrichtigung �ber ein Chargeback an die im
		 *  ControlCenter angegebene URL und gibt Debuginformationen dar�ber zur�ck
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=1)  aktiviert Testumgebung
		 * @param string $transactionId Transaktionsnummer einer PURCHASE- oder CAPTURE-Transaktion
		 * 
		 * @return string 
		 */
		public function transactionChargebackNotificationTest($accessKey, $testMode=1, $transactionId);

		/**
		 * Verkn�pft Adressdaten mit einem bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)   aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $address Strasse und Hausnummer
		 * @param string $zipcode Postleitzahl
		 * @param string $town Ort
		 * @param string $country Land [ISO 3166-1-alpha-2] bspw. DE, AT, CH
		 * 
		 * @return boolean 
		 */
		public function addressSet($accessKey, $testMode=0, $customerId, $address, $zipcode, $town, $country);

		/**
		 * Gibt die mit einem Kunden verkn�pften Adressdaten zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)   aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result string $address Strasse und Hausnummer
		 * @result string $zipcode Postleitzahl
		 * @result string $town Ort
		 * @result string $country Land [ISO 3166-1-alpha-2] bspw. DE, AT, CH
		 */
		public function addressGet($accessKey, $testMode=0, $customerId);

		/**
		 * Erstellt einen neuen Kunden.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eigene eindeutige ID des Kunden, wird anderenfalls erzeugt [min./max. Zeichen 10/40, alphanumerisch]
		 * @param map $freeParams (default=null)  Liste mit freien Parametern, die dem Kunden zugeordnet werden
		 * @param string $firstname Vorname des Kunden
		 * @param string $surname Nachname des Kunde
		 * @param string $email (default=null)  E-Mail-Adresse des Kunden, wenn nach den Transaktionen einen E-Mail an der Kunden versand werden soll
		 * @param string $culture (default='de-DE')  Sprache & Land des Kunden | g�ltige Beispielwerte sind 'de', 'de-DE', 'en-US'
		 * 
		 * @return string eigene oder erzeugte eindeutige ID des Kunden
		 */
		public function customerCreate($accessKey, $testMode=0, $customerId=null, $freeParams=null, $firstname, $surname, $email=null, $culture='de-DE');

		/**
		 * �ndert Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param map $freeParams (default=null)  Liste mit freien Parametern: [NULL] - Parameterliste bleibt unver�ndert | leeres HashMap - l�scht Parameterliste | gef�lltes HashMap erweitert/�berschreibt bestehende Parameterliste
		 * @param string $firstname (default=null)  Vorname des Kunden: [NULL] - aktueller Wert bleibt erhalten | g�ltiger Wert z.B 'Max'
		 * @param string $surname (default=null)  Nachname des Kunde: [NULL] - aktueller Wert bleibt erhalten | g�ltiger Wert z.B 'Mustermann'
		 * @param string $email (default=null)  E-Mail-Adresse des Kunden: [NULL] - aktueller Wert bleibt erhalten | g�ltiger Wert z.B. 'max@mustermann.de' ersetzt den aktuellen Wert
		 * @param string $culture (default=null)  Sprache & Land des Kunden: [NULL] - aktueller Wert bleibt erhalten | g�ltige Wert z.B. 'de-DE' ersetzt den aktuellen Wert
		 * 
		 * @return boolean 
		 */
		public function customerSet($accessKey, $testMode=0, $customerId, $freeParams=null, $firstname=null, $surname=null, $email=null, $culture=null);

		/**
		 * Gibt die Daten eines bestehenden Kunden zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result map $freeParams (default=null)  Liste mit allen freien Parametern
		 * @result string $firstname (default=null)  Vorname des Kunden
		 * @result string $surname (default=null)  Nachname des Kunden
		 * @result string $email (default=null)  E-Mail-Adresse des Kunden
		 * @result string $culture (default=null)  Sprache & Land des Kunden
		 */
		public function customerGet($accessKey, $testMode=0, $customerId);

		/**
		 * Verkn�pft Kreditkartendaten mit einem bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $number Kreditkartennummer
		 * @param integer $expiryYear G�ltigkeits Jahr
		 * @param integer $expiryMonth G�ltigkeits Monat
		 * 
		 * @return boolean R�ckgabewert gibt Auskunft dar�ber, ob bei der n�chsten Buchung der CVC2-Code erforderlich ist
		 */
		public function creditcardDataSet($accessKey, $testMode=0, $customerId, $number, $expiryYear, $expiryMonth);

		/**
		 * Erzeugt einen neuen Bezahlvorgang f�r einen Kunden
		 * 
		 *  Zur Durchf�hrung wird zwingender Weise ein Kunde (customerId) ben�tigt, den Sie mittels `customerCreate` anlegen k�nnen.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $sessionId (default=null)  eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt [max. 40 Zeichen]
		 * @param string $project das Projektk�rzel f�r den Vorgang
		 * @param string $projectCampaign (default=null)  ein Kampagnenk�rzel des Projektbetreibers
		 * @param string $account (default=null)  Account des beteiligten Webmasters sonst eigener - setzt eine Aktivierung der Webmasterf�higkeit des Projekts vorraus - Hinweis: Webmasterf�higkeit steht momentan nicht zur Verf�gung
		 * @param string $webmasterCampaign (default=null)  ein Kampagnenk�rzel des Webmasters
		 * @param integer $amount (default=null)  abzurechnender Betrag, wird kein Betrag �bergeben, wird der Betrag aus der Konfiguration verwendet
		 * @param string $currency (default='EUR')  W�hrung
		 * @param string $title (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung in Falle einer auftretenden Benachrichtigung wird dieser Wert als Produktidentifizierung mit geschickt, wird kein Wert �bergeben, wird Der aus der Konfiguration verwendet
		 * @param string $paytext (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung beim Mailversand, sollten Sie Diesen w�nschen
		 * @param string $ip IPv4 des Benutzers
		 * @param map $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * @param boolean $sendMail (default=true) 
		 * 
		 * @return struct 
		 * @result string $sessionId eigene oder erzeugte eindeutige ID des Vorgangs
		 * @result SessionStatus $status Vorgangsstatus "INIT"
		 * @result datetime $expire Ablaufzeit des Vorgangs
		 */
		public function sessionCreate($accessKey, $testMode=0, $customerId, $sessionId=null, $project, $projectCampaign=null, $account=null, $webmasterCampaign=null, $amount=null, $currency='EUR', $title=null, $paytext=null, $ip, $freeParams=null, $sendMail=true);

		/**
		 * Gibt Informationen �ber einen Bezahlvorgang, inklusive einer Liste von verkn�pften Transaktionen, zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId eindeutige ID des Vorgangs
		 * 
		 * @return struct 
		 * @result string $customerId ID des Kunden
		 * @result string $project das Projektk�rzel f�r den Vorgang
		 * @result string $projectCampaign ein Kampagnenk�rzel des Projektbetreibers
		 * @result string $account Account des beteiligten Webmasters sonst eigener
		 * @result string $webmasterCampaign ein Kampagnenk�rzel des Webmasters
		 * @result integer $amount abzurechnender Betrag, wird kein Betrag �bergeben, wird der Betrag aus der Konfiguration verwendet
		 * @result string $currency W�hrungseinheit
		 * @result string $title Bezeichnung der zu kaufenden Sache
		 * @result string $ip IPv4 des Benutzers
		 * @result map $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * @result SessionStatus $status 
		 * @result datetime $expire (default=null)  Verfallsdatum der Session, nur wenn $status INIT oder EXPIRED
		 * @result MailStatus $mail Status des Mailversands
		 * @result string[] $transactionIds (default=null)  Liste von TransaktionsIds die mit dieser Session verkn�pft sind
		 */
		public function sessionGet($accessKey, $testMode=0, $sessionId);

	}

?>