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
	 *  Hinweise zum TestModus (Verf�gbar erst ab Version 1.1):
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
		 * Gibt die mit einem Kunden verkn�pften Kreditkartendaten zur�ck.
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result string $type Kartentyp
		 * @result string $number partielle Kreditkartennummer [letzten 4 Stellen]
		 * @result integer $expiryYear G�ltigkeits Jahr
		 * @result integer $expiryMonth G�ltigkeits Monat
		 * @result boolean $cvc2Required Bei der n�chsten Buchung ist der CVC2-Code erforderlich
		 */
		public function creditcardDataGet($accessKey, $testMode=0, $customerId);

		/**
		 * Gibt eine Liste von Bezahlvorg�ngen anhand von Parametern zur�ck
		 * 
		 *  Folgende frei kombinierbare Parameter stehen zur Verf�gung:
		 *  - Kunde (customerId)
		 *  - Zeitraum von/bis (dtmFrom/dtmTo)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eindeutige ID des Kunden oder [NULL] ohne Kundeneinschr�nkung
		 * @param datetime $dtmFrom (default=null)  Anfang der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * @param datetime $dtmTo (default=null)  Ende der Zeitraumbegrenzung oder [NULL] um diese Zeitraumbegrenzung nicht zu definieren
		 * 
		 * @return string[] 
		 */
		public function sessionList($accessKey, $testMode=0, $customerId=null, $dtmFrom=null, $dtmTo=null);

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

		/**
		 * Gibt Informationen �ber eine Transaktion anhand einer Transaktionsnummer zur�ck.
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
		 * @result string $currency W�hrungseinheit
		 * @result TransactionType $type Art der Transaktion
		 * @result TransactionStatus $status Status der Transaktion
		 * @result datetime $created Zeitpunkt der Transaktion
		 * @result string $ip IPv4 des Benutzers
		 * @result string $cardType Kartentyp
		 * @result string $cardNumber partielle Kreditkartennummer (letzten 4 Stellen)
		 * @result integer $cardExpiryYear G�ltigkeits Jahr
		 * @result integer $cardExpiryMonth G�ltigkeits Monat
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
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionPurchase($accessKey, $testMode=0, $sessionId, $cvc2=null);

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
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionAuthorization($accessKey, $testMode=0, $sessionId, $cvc2=null);

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
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionCapture($accessKey, $testMode=0, $sessionId, $transactionId=null, $amount=null);

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
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionReversal($accessKey, $testMode=0, $sessionId, $transactionId);

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
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgel�sten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionRefund($accessKey, $testMode=0, $sessionId, $transactionId, $amount=null);

	}

?>