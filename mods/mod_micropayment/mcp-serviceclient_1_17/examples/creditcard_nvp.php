<pre>
<?php
/**
 * @copyright 2008 micropayment GmbH
 * @link http://www.micropayment.de/
 * @link https://sipg.micropayment.de/public/creditcard/v1.2/nvp/
 * @author Yves Berkholz
 * @package MCP-Service-Client
 * @subpackage Demo
 * @version 1.02
 */
// =================================================================================================
//
// WICHTIG!!! Tragen Sie hier Ihren AccessKey ein
//
// Sie finden Ihren AccessKey im ControlCenter unter dem Menupunkt "Meine Konfiguration"
//
// =================================================================================================
	if(!defined('MCP__ACCESSKEY')) define('MCP__ACCESSKEY', 'HIER IHREN ACCESSKEY EINTRAGEN');
// =================================================================================================

// TestModus (deaktivieren)
// -------------------------------------------------------------------------------------------------
	if(!defined('MCP__TESTMODE')) define('MCP__TESTMODE', 1);

// ServiceURL die aufgerufen werden soll
// -------------------------------------------------------------------------------------------------
	if(!defined('MCP__CREDITCARDSERVICE_NVP_URL')) define('MCP__CREDITCARDSERVICE_NVP_URL', 'https://sipg.micropayment.de/public/creditcard/v1.2/nvp/');

// Service-Interface das verwendet werden soll
// -------------------------------------------------------------------------------------------------
	if(!defined('MCP__CREDITCARDSERVICE_INTERFACE')) define('MCP__CREDITCARDSERVICE_INTERFACE', 'IMcpCreditcardService_v1_2');

// Bibliothek/Service-Interface und den gewünschten Dispatcher laden
// -------------------------------------------------------------------------------------------------
	require_once( realpath('../lib/init.php') );
	require_once( realpath('../services/' . MCP__CREDITCARDSERVICE_INTERFACE . '.php') );
	require_once( MCP__SERVICELIB_DISPATCHER . 'TNvpServiceDispatcher.php');

// Beispieldaten, die ggf. äbgeändert werden müssen
// -------------------------------------------------------------------------------------------------
	$email 		= 'max.mustermann2@example.com';
	$firstname	= 'Max';
	$surname	= 'Mustermann';
	$bSendMail	= false;
	$customerId = md5($email);
	$customerIP = '127.0.0.1';

	$address	= 'Mustergasse 123';
	$zipcode	= '12345';
	$town		= 'Berlin';
	$country	= 'DE';

	$cardNumber 		= '4111111111111111';
	$cardCVC2			= '666';
	$cardExpiryYear		= '2011';
	$cardExpiryMonth	= '11';

	$amount				= 250; // cent
	$currency			= 'EUR';
	$project			= 'demo';
	$account			= null;
	$projectCampaign	= null;
	$webmasterCampaign	= null;
	$title				= 'livesystemtest';
	$paytext			= 'Live System Test';
	$sessionFreeParams	= array(
							'foo'		=> 'bar',
							'bar'		=> 'foo',
							'foobar'	=> 'foobar'
						);


/* ---------------------------------------------------------------------------------------------- *\
| Folgender allgemeiner Ablaufplan ist für die Durchführung von Transaktion zu berücksichtigen
+---------------------------------------------------------------------------------------------------
	  I. Es muß ein Kunde existieren für den gebucht werden soll:
			(a) "customerCreate" legt einen neuen Kunden an
			(b) "customerGet" liefert bestehende Kundendaten, kann auch zur Überprüfung der Existenz eines Kunden verwendet werden
			(c) "customerSet" ändern bestehende Kundendaten

	 II. Für den Kunden müssen Kreditkarteninformationen hinterlegt sein
			(a) "creditcardDataSet" weist einem bestehenden Kunden Kreditkartendaten zu
			(b) "creditcardDataGet" ruft Diese wieder ab

	III. Es muß eine gültige Session vorhanden sein
			(a) "sessionCreate" erzeugt eine Session mit allen allgemein relevanten Informationen für eine Buchung
			(b) "sessionGet" liefert die mit einer Session vernknüpten Informationen, inklusive der durchgeführten Transaktionen

\* ---------------------------------------------------------------------------------------------- */


/* ---------------------------------------------------------------------------------------------- *\
// Ablaufplan des Beispiels
+---------------------------------------------------------------------------------------------------
	1. Kunden anlegen - falls dieser noch nicht existiert und ggf. mit zusätzlichen Daten versehen
	2. Kreditkartendaten für Kunden abfragen / hinterlegen
	3. Session erzeugen / abfragen

	4a. Purchase Transaktion durchführen						$bPurchase
	4b. Authorization Transaktion durchführen					$bAuthorization

	5. Capture Transaktion durchführen							$bCapture

	6a. Reversal Transaktion durchführen						$bReversal
	6b. Refund Transaktion durchführen							$bRefund

	   Session sowie verknüpfte Transaktionen abfragen
\* ---------------------------------------------------------------------------------------------- */
	$bResetData			= true;
	$bPurchase 			= false;
	$bAuthorization 	= false;
	$bCapture			= false;
	$bReversal			= false;
	$bRefund			= false;

// Bsp.-Kombination 1: Purchase + Rückbuchung
//	$bPurchase 			= true;
//	$bReversal			= true;

// Bsp.-Kombination 2: Authorization + Freigabe
//	$bAuthorization		= true;
//	$bReversal			= true;

// Bsp.-Kombination 2: Authorization + Capture + Rückbuchung
//	$bAuthorization		= true;
//	$bCapture			= true;
//	$bRefund			= true;



	try {
	// Dispatcher initialisieren
	// ---------------------------------------------------------------------------------------------
		$dispatcher = new TNvpServiceDispatcher(MCP__CREDITCARDSERVICE_INTERFACE, MCP__CREDITCARDSERVICE_NVP_URL);

	// Dispatcher aufrufen
	// ---------------------------------------------------------------------------------------------
	/*
		// Möglichkeit 1
		// dabei müssen alle Parameter in der Reihenfolge wie im Interface beschrieben, übergeben werden, auch jene die Standardwerte haben
			$result = $dispatcher -> myMethod($param1, $param2, $param3);

		// Möglichkeit 2
			$aParams = array();
			$aParams['param1'] = 'param1Value';
		//  $aParams['param2'] kann, wenn er einen Standardwert hat, weggelassen werden
			$aParams['param3'] = 'param3Value';
			$result = $dispatcher -> send('myMethod', $aParams);
	 */


	// 1. Kunden anlegen - falls dieser noch nicht existiert und ggf. mit zusätzlichen Daten versehen
	// ---------------------------------------------------------------------------------------------
		try {
			echo '<b>customerGet</b>:<br />';

			$result = $dispatcher -> customerGet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId);

			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
				echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';

			switch($e -> getCode()) {
				case 3003:
				case 3110:
					echo '<b>customerCreate</b>:<br />';


					$result = $dispatcher -> customerCreate(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, null, $firstname, $surname, $email, 'en-GB');
					if($result) $customerId = $result;
					print_r($result);
					echo '<br />';
				break;
				default:
					echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
				break;
			}
		}


		echo '<br />';
		echo '<b>customerSet-1</b>:<br/>';
		$result = $dispatcher -> customerSet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, array('foo' => 'bar', 'internalstatus' => '123'), $firstname, $surname, $email, 'de-DE');
		print_r($result);
		echo '<br />';

		echo '<br />';
		echo '<b>customerSet-2</b>:<br/>';
		$result = $dispatcher -> customerSet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, array('foo2' => 'bar2', 'internalstatus2' => '123'), $firstname, $surname, $email, 'de-DE');
		print_r($result);
		echo '<br />';

		echo '<br />';
		echo '<b>addressSet</b>:<br/>';
		$result = $dispatcher -> addressSet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, $address, $zipcode, $town, $country);
		print_r($result);

		echo '<br />';
		echo '<b>addressGet</b>:<br/>';
		$result = $dispatcher -> addressGet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId);
		print_r($result);


	// 2. Kreditkartendaten für Kunden abfragen / hinterlegen
	// ---------------------------------------------------------------------------------------------
		try {
			echo '<b>creditcardDataGet</b>:<br />';
			$result = $dispatcher -> creditcardDataGet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId);
			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}


		try {
			echo '<b>creditcardDataSet</b>:<br />';
			$result = $dispatcher -> creditcardDataSet(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, $cardNumber, $cardExpiryYear, $cardExpiryMonth);
			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}

	// 3. Session erzeugen / abfragen
	// ---------------------------------------------------------------------------------------------
		try {
			$sessionId = null;
			echo '<b>sessionCreate</b>:<br />';
			$result = $dispatcher -> sessionCreate(MCP__ACCESSKEY, MCP__TESTMODE, $customerId, $sessionId, $project, $projectCampaign, $account, $webmasterCampaign, $amount, $currency, $title, $paytext, $customerIP, $sessionFreeParams, $bSendMail);
			print_r($result);
			echo '<br />';

			$sessionId = $result['sessionId'];

			echo '<b>sessionGet</b>:<br />';
			$result = $dispatcher -> sessionGet(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId);
			print_r($result);
			echo '<br />';

		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}

	// 4a. Purchase Transaktion durchführen
	// ---------------------------------------------------------------------------------------------
	// erst ohne CVC2, falls der Kunde mit der Kreditkarte schonmal erfolgreich gebucht hat funktioniert doe Buchung
	// sollte die Buchung fehlschlagen die Transaktion einfach nochmal mit CVC2-Code durchführen
	// idealerweise läßt man vom Kunden den CVC2 Code immer angeben
	// ---------------------------------------------------------------------------------------------
	if($bPurchase) {

		try {
			echo '<b>transactionPurchase ohne CVC2</b>:<br />';
			$result = $dispatcher -> transactionPurchase(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId);
			print_r($result);

			$transactionId = $result['transactionId'];
			echo '<br />';
		}
		catch(Exception $e) {
			switch($e -> getCode()) {
				case 3510:
					echo ' Failed<br />';

					echo '<b>transactionPurchase mit CVC2</b>:<br />';
					$result = $dispatcher -> transactionPurchase(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId, $cardCVC2);

					$transactionId = $result['transactionId'];
					print_r($result);
					echo '<br />';
				break;
				default:
					echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
				break;
			}
		}
	} // if($bPurchase)


	// 4b. Authorization Transaktion durchführen
	// ---------------------------------------------------------------------------------------------
	// erst ohne CVC2, falls der Kunde mit der Kreditkarte schonmal erfolgreich gebucht hat funktioniert doe Buchung
	// sollte die Buchung fehlschlagen die Transaktion einfach nochmal mit CVC2-Code durchführen
	// idealerweise läßt man vom Kunden den CVC2 Code immer angeben
	// ---------------------------------------------------------------------------------------------
	if($bAuthorization) {

		try {
			echo '<b>transactionAuthorization ohne CVC2</b>:<br />';
			$result = $dispatcher -> transactionAuthorization(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId);
			print_r($result);

			$transactionId = $result['transactionId'];
			echo '<br />';
		}
		catch(Exception $e) {
			switch($e -> getCode()) {
				case 3510:
					echo ' Failed<br />';

					echo '<b>transactionAuthorization mit CVC2</b>:<br />';
					$result = $dispatcher -> transactionAuthorization(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId, $cardCVC2);

					$transactionId = $result['transactionId'];
					print_r($result);
					echo '<br />';
				break;
				default:
					echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
				break;
			}
		}
	} // if($bAuthorization)

	// 5. Capture Transaktion durchführen
	// ---------------------------------------------------------------------------------------------
	if($bCapture) {

		try {
			echo '<b>transactionCapture</b>:<br />';
			$result = $dispatcher -> transactionCapture(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId, $transactionId);

			$transactionId = $result['transactionId'];
			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}

	} // if($bCapture)



	// 6a. Reversal Transaktion durchführen
	// ---------------------------------------------------------------------------------------------
	if($bReversal) {

		try {
			echo '<b>transactionReversal</b>:<br />';
			$result = $dispatcher -> transactionReversal(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId, $transactionId);
			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}

	} // if($bReversal)

	// 6b. Refund Transaktion durchführen
	// ---------------------------------------------------------------------------------------------
	if($bRefund) {

		try {
			echo '<b>transactionRefund</b>:<br />';
			$result = $dispatcher -> transactionRefund(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId, $transactionId);
			print_r($result);
			echo '<br />';
		}
		catch(Exception $e) {
			echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
		}

	} // if($bRefund)




	// Session sowie verknüpfte Transaktionen abfragen
	// ---------------------------------------------------------------------------------------------
		$result = $dispatcher -> sessionGet(MCP__ACCESSKEY, MCP__TESTMODE, $sessionId);
		echo '<b>sessionGet</b>:<br />';
		print_r($result);
		echo '<br />';

		if($result AND is_array($result['transactionIds'] )) {
			foreach($result['transactionIds'] as $transactionId) {
				try {

					$result = $dispatcher -> transactionGet(MCP__ACCESSKEY, MCP__TESTMODE, $transactionId);
					echo '<b>transactionGet</b>:<br />';
					print_r($result);
					echo '<br />';

					if(MCP__TESTMODE) {
						$result = $dispatcher -> transactionChargebackNotificationTest(MCP__ACCESSKEY, MCP__TESTMODE, $transactionId);
						echo '<br /><b>transactionChargebackNotificationTest</b>:<br />';
						print_r($result);
						echo '<br />';
					}


				}
				catch(Exception $e) {
					echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
				}
			}
		}

	// Testdaten zurücksetzen
	// ---------------------------------------------------------------------------------------------
		if(MCP__TESTMODE && $bResetData) {
			$result = $dispatcher -> resetTest(MCP__ACCESSKEY, MCP__TESTMODE);
			echo '<br /><b>resetTest</b>:<br />';
			echo htmlentities($result);
			echo '<br />';
		}


	}
	catch(Exception $e) {
		echo get_class($e) .'[' . $e -> getCode() . '] ' . $e -> getMessage() . '<br />';
	}
?>
</pre>