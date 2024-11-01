<?php

	/**
	 * Dieser Service stellt eine Server2Server-Schnittstelle (API), zur Buchung von Transaktionen über die Zahlungsart Kreditkarte, zur Verfügung (Creditcard - API.Event).
	 * 
	 *   Vorraussetzungen:
	 *  - Ein Account bei micropayment(tm) - Anmeldung unter www.micropayment.de
	 *  - Die Freischaltung von Creditcard - API.Event durch unseren Händler-Support (support@micropayment.de)
	 *  - Eine Vertrags-Unternehmennummer (VU-Nummer) die Sie im ControlCenter beantragen können
	 * 
	 *  Konfiguration:
	 *  Nach der Freischaltung von Creditcard - API.Event durch unseren Support loggen Sie sich in das ControlCenter Ihres Accounts ein und begeben sich zu dem Menüpunkt "Meine Konfiguration":
	 *  - hier finden Sie im unteren Bereich den `AccessKey` den Sie für die Verwendung des API benötigen
	 *  - im Untermenüpunkt "APIs" konfigurien und aktivieren Sie Creditcard - API.Event
	 *  - im Untermenüpunkt "Zugriffsberechtigungen" konfigurieren Sie bitte die IP-Adresse/IP-Range der Server, von dem aus Sie auf das API zugreifen möchten
	 * 
	 *  Hinweis zu Hoch-Traffic-Anwendungen:
	 *  Grundsätzlich kann ein Kreditkartenterminal immer nur eine Buchung zur selben Zeit ausführen.
	 *  Bei normalem Traffic fängt eine Warteschlange gleichzeitige Anfragen auf.
	 *  Wenn aber parallele Transaktionen erforderlich sind (z.B. bei Trafficspitzen), können Sie zur Lastverteilung bei unserem Support zusätzliche Terminals ordern.
	 * 
	 *  Hinweise zum TestModus (Verfügbar erst ab Version 1.1):
	 *  - Aktivierung der Testumgebung erfolgt in der jeweiligen Funktion über den Parameter `testMode`
	 *  - für den TestModus stehen folgende Test-Kartennummern zur Verfügung:
	 * 	- VISA: 4111111111111111
	 * 	- MASTER: 5454545454545454
	 * 	- AMEX: 343434343434343
	 *  - um erfolgreiche Buchungen durchzuführen, ist als CVC2-Code die 666 zu verwenden, bei allen Anderen gilt die Transaktion als fehlgeschlagen
	 *  - um einen spezifischen Terminal-Fehlercode auszulösen, ist als CVC2-Code die 555 zu verwenden:
	 *    Der als Integerwert (bspw. 534) angefordertet Endkundenbetrag, liefert im Eurocent-Anteil (bspw. 34) den Fehlercode (`transactionResultCode`) zurück - z.B. 534 => ipg34.
	 *  	 Bitte beachten Sie, dass das erwartete Ergebnis nur bei der Verwendung der Währung (`currency`) "EUR" erreicht wird.
	 * 
	 *  Übermittlung von Adressdaten:
	 *  Sollten Ihnen die Adressdaten Ihrer Endkunden zur Verfügung stehen, empfehlen wir diese mittles "addressSet" im Kundendatensatz zu hinterlegen.
	 *  Die im Kundendatensatz (customerCreate, customerSet) hinterlegten Adressdaten, werden zum Einen für das Adress-Verifikations-System (AVS, verfügbar ab Version 1.5) benötigt,
	 *  zum Anderen werden diese Daten bei Chargeback-Anfragen (s.g. Beleganforderungen von Issuer und Acquirerer) verwendet.
	 * 
	 *  Fehlercodes:
	 *  Eine Übersicht über die möglichen Fehlercodes des API als auch des Terminals finden Sie unter folgender Adresse:
	 *  https://webservices.micropayment.de/public/creditcard/ERRORCODES/
	 * 
	 *  Updates/Changelog:
	 *  Wenn Sie bisher eine ältere Version dieses APIs verwenden, könnten Sie sich im Changelog einen Überblick über die Neuerungen verschaffen:
	 *  https://webservices.micropayment.de/public/creditcard/CHANGELOG/
	 *
	 * @copyright 2011 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Yves Berkholz, Guido Franke
	 * @version 1.0
	 * @created 2011-10-11 21:38:00
	 */
	interface IMcpCreditcardService_v1_0 {

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
		 * @param string $culture (default='de-DE')  Sprache & Land des Kunden | gültige Beispielwerte sind 'de', 'de-DE', 'en-US'
		 * 
		 * @return string eigene oder erzeugte eindeutige ID des Kunden
		 */
		public function customerCreate($accessKey, $testMode=0, $customerId=null, $freeParams=null, $firstname, $surname, $email=null, $culture='de-DE');

		/**
		 * Ändert Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param map $freeParams (default=null)  Liste mit freien Parametern: [NULL] - Parameterliste bleibt unverändert | leeres HashMap - löscht Parameterliste | gefülltes HashMap erweitert/überschreibt bestehende Parameterliste
		 * @param string $firstname (default=null)  Vorname des Kunden: [NULL] - aktueller Wert bleibt erhalten | gültiger Wert z.B 'Max'
		 * @param string $surname (default=null)  Nachname des Kunde: [NULL] - aktueller Wert bleibt erhalten | gültiger Wert z.B 'Mustermann'
		 * @param string $email (default=null)  E-Mail-Adresse des Kunden: [NULL] - aktueller Wert bleibt erhalten | gültiger Wert z.B. 'max@mustermann.de' ersetzt den aktuellen Wert
		 * @param string $culture (default=null)  Sprache & Land des Kunden: [NULL] - aktueller Wert bleibt erhalten | gültige Wert z.B. 'de-DE' ersetzt den aktuellen Wert
		 * 
		 * @return boolean 
		 */
		public function customerSet($accessKey, $testMode=0, $customerId, $freeParams=null, $firstname=null, $surname=null, $email=null, $culture=null);

		/**
		 * Gibt die Daten eines bestehenden Kunden zurück.
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
		 * Verknüpft Kreditkartendaten mit einem bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $number Kreditkartennummer
		 * @param integer $expiryYear Gültigkeits Jahr
		 * @param integer $expiryMonth Gültigkeits Monat
		 * 
		 * @return boolean Rückgabewert gibt Auskunft darüber, ob bei der nächsten Buchung der CVC2-Code erforderlich ist
		 */
		public function creditcardDataSet($accessKey, $testMode=0, $customerId, $number, $expiryYear, $expiryMonth);

		/**
		 * Gibt die mit einem Kunden verknüpften Kreditkartendaten zurück.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result string $type Kartentyp
		 * @result string $number partielle Kreditkartennummer [letzten 4 Stellen]
		 * @result integer $expiryYear Gültigkeits Jahr
		 * @result integer $expiryMonth Gültigkeits Monat
		 * @result boolean $cvc2Required Bei der nächsten Buchung ist der CVC2-Code erforderlich
		 */
		public function creditcardDataGet($accessKey, $testMode=0, $customerId);

		/**
		 * Gibt eine Liste von Bezahlvorgängen anhand von Parametern zurück
		 * 
		 *  Folgende frei kombinierbare Parameter stehen zur Verfügung:
		 *  - Kunde (customerId)
		 *  - Zeitraum von/bis (dtmFrom/dtmTo)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eindeutige ID des Kunden oder [NULL] ohne Kundeneinschränkung
		 * @param datetime $dtmFrom (default=null)  Anfang der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * @param datetime $dtmTo (default=null)  Ende der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * 
		 * @return string[] 
		 */
		public function sessionList($accessKey, $testMode=0, $customerId=null, $dtmFrom=null, $dtmTo=null);

		/**
		 * Erzeugt einen neuen Bezahlvorgang für einen Kunden
		 * 
		 *  Zur Durchführung wird zwingender Weise ein Kunde (customerId) benötigt, den Sie mittels `customerCreate` anlegen können.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $sessionId (default=null)  eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt [max. 40 Zeichen]
		 * @param string $project das Projektkürzel für den Vorgang
		 * @param string $projectCampaign (default=null)  ein Kampagnenkürzel des Projektbetreibers
		 * @param string $account (default=null)  Account des beteiligten Webmasters sonst eigener - setzt eine Aktivierung der Webmasterfähigkeit des Projekts vorraus - Hinweis: Webmasterfähigkeit steht momentan nicht zur Verfügung
		 * @param string $webmasterCampaign (default=null)  ein Kampagnenkürzel des Webmasters
		 * @param integer $amount (default=null)  abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
		 * @param string $currency (default='EUR')  Währung
		 * @param string $title (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung in Falle einer auftretenden Benachrichtigung wird dieser Wert als Produktidentifizierung mit geschickt, wird kein Wert übergeben, wird Der aus der Konfiguration verwendet
		 * @param string $paytext (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung beim Mailversand, sollten Sie Diesen wünschen
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
		 * Gibt Informationen über einen Bezahlvorgang, inklusive einer Liste von verknüpften Transaktionen, zurück.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId eindeutige ID des Vorgangs
		 * 
		 * @return struct 
		 * @result string $customerId ID des Kunden
		 * @result string $project das Projektkürzel für den Vorgang
		 * @result string $projectCampaign ein Kampagnenkürzel des Projektbetreibers
		 * @result string $account Account des beteiligten Webmasters sonst eigener
		 * @result string $webmasterCampaign ein Kampagnenkürzel des Webmasters
		 * @result integer $amount abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
		 * @result string $currency Währungseinheit
		 * @result string $title Bezeichnung der zu kaufenden Sache
		 * @result string $ip IPv4 des Benutzers
		 * @result map $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * @result SessionStatus $status 
		 * @result datetime $expire (default=null)  Verfallsdatum der Session, nur wenn $status INIT oder EXPIRED
		 * @result MailStatus $mail Status des Mailversands
		 * @result string[] $transactionIds (default=null)  Liste von TransaktionsIds die mit dieser Session verknüpft sind
		 */
		public function sessionGet($accessKey, $testMode=0, $sessionId);

		/**
		 * Gibt Informationen über eine Transaktion anhand einer Transaktionsnummer zurück.
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
		 * @result integer $amount abgerechneter Betrag
		 * @result string $currency Währungseinheit
		 * @result TransactionType $type Art der Transaktion
		 * @result TransactionStatus $status Status der Transaktion
		 * @result datetime $created Zeitpunkt der Transaktion
		 * @result string $ip IPv4 des Benutzers
		 * @result string $cardType Kartentyp
		 * @result string $cardNumber partielle Kreditkartennummer (letzten 4 Stellen)
		 * @result integer $cardExpiryYear Gültigkeits Jahr
		 * @result integer $cardExpiryMonth Gültigkeits Monat
		 */
		public function transactionGet($accessKey, $testMode=0, $transactionId);

		/**
		 * Führt eine Transaktion zur sofortigen Buchung, des durch `sessionCreate` definierten Betrages, durch.
		 * 
		 *  Zur Durchführung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt haben,
		 *  sowie dem Kunden, unter Zuhilfenahme von `creditcardDataSet`, Kreditkartendaten zugewiesen haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, muß min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionPurchase($accessKey, $testMode=0, $sessionId, $cvc2=null);

		/**
		 * Führt eine Transaktion zur Vorautorisierung, des durch `sessionCreate` definierten Betrages, durch.
		 * 
		 *  Der gewünschten Betrag wird für Sie reserviert bis:
		 *  - Sie den Betrag mittels `transactionReversal` wieder freigeben
		 *  - Sie den Gesamt- oder Teilbetrag mit `transactionCapture` buchen
		 *  - die Reservierung nach ca. 14 Tagen automatisch wieder freigegeben wird
		 * 
		 *  Zur Durchführung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt haben,
		 *  sowie dem Kunden, unter Zuhilfenahme von `creditcardDataSet`, Kreditkartendaten zugewiesen haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, muß min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionAuthorization($accessKey, $testMode=0, $sessionId, $cvc2=null);

		/**
		 * Führt eine Transaktion zur Buchung (Gesamt- oder Teilbetrag) einer Vorautorisierung durch.
		 * 
		 *  Sie buchen den reservierte Betrag:
		 *  - In voller Höhe (Gesamtbetrag) - der Parameter `amount` entfällt ([NULL])
		 *  - Oder nur einen Teilbetrag - der Parameter `amount` enthält den gewünschten Teilbetrag
		 * 
		 *  Zur Durchführung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang,
		 *  sowie unter Verwendung von `transactionAuthorization` eine Vorautorisierung, erstellt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId (default=null)  Transaktionsnummer von "transactionAuthorization"
		 * @param integer $amount (default=null)  [NULL] - entspricht Betrag aus Vorautorisierung | wenn abweichend, der zu buchende Betrag <= Betrag aus Vorautorisierung
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionCapture($accessKey, $testMode=0, $sessionId, $transactionId=null, $amount=null);

		/**
		 * Transaktion zur gebührenfreier Stornierung einer Buchung vor Kassenschnitt oder Freigabe von Vorautorisierungen.
		 * 
		 *  Die gebührenfreie Stornierung einer mit `transactionPurchase` durchgeführten Buchung muss am gleichen Tag vor Kassenschnitt um 24 Uhr erfolgen.
		 *  Die Freigabe einer mittels `transactionAuthorization` erstellten Vorautorisierungen hingegen kann jederzeit durchgeführt werden.
		 * 
		 *  Zur Durchführung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt,
		 *  sowie unter Verwendung von `transactionAuthorization` eine Vorautorisierung oder mit `transactionPurchase` eine Buchung durchgeführt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zurückgebucht werden soll
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionReversal($accessKey, $testMode=0, $sessionId, $transactionId);

		/**
		 * Transaktion zur Buchung einer Gesamt- oder Teilrückzahlung einer den Kunden belastende Buchung.
		 * 
		 *  Sie erstatten den gebuchten Betrag:
		 *  - In voller Höhe (Gesamtbetrag) - der Parameter `amount` entfällt ([NULL])
		 *  - Oder nur einen Teilbetrag - der Parameter `amount` enthält den gewünschten Teilbetrag
		 * 
		 *  Zur Durchführung der Transaktion ist es notwendig das Sie mittels `sessionCreate` einen Bezahlvorgang erstellt,
		 *  sowie unter Verwendung von `transactionPurchase` oder `transactionCapture` eine den Kunden belastende Buchung durchgeführt haben.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zurückgebucht werden soll
		 * @param integer $amount (default=null)  zurückzubuchender Betrag, falls abweichend von Orginaltransaktion
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionRefund($accessKey, $testMode=0, $sessionId, $transactionId, $amount=null);

	}

?>