<?php
// -------------------------------------------------------------------------------------------------
//  init library and import Nvp-Serializer
//   - Serializer is required for right response encoding
// -------------------------------------------------------------------------------------------------
	require_once( realpath('../lib/init.php') );
	require_once(MCP__SERVICELIB_SERIALIZER . 'TNvpSerializer.php');
	
// -------------------------------------------------------------------------------------------------
//  helper classes
// -------------------------------------------------------------------------------------------------
	/**
	 * status of notification processing
	 */
	class NotificationStatus {
		/**
		 * Notification was received and processed successfully
		 */
		const SUCCESS = 'SUCCESS';
		
		/**
		 * Notification was received successfully, but an error occurred on processing
		 */
		const FAILED = 'FAILED';
		
		/**
		 * Notification wasn't received / processed without an error
		 */
		const ERROR = 'ERROR';
	}
	
	
	/**
	 * data mapper class for response
	 *
	 */
	class NotificationResponse {
		/**
		 * @var string
		 * @see NotificationStatus
		 */
		public $status			= null;
		
		/**
		 * @var string
		 */
		public $statusMessage	= null;
		
		/**
		 * @var array|null
		 */
		public $freeParam		= null;
		
		/**
		 * @param string $status
		 * @param string|null $statusMessage
		 * @param array|null $freeParam
		 */
		public function __construct($status=NotificationStatus::ERROR, $statusMessage=null, $freeParam=null) {
			$this -> status			= $status;
			$this -> statusMessage	= $statusMessage;
			$this -> freeParam		= $freeParam;
		}
	}
	
	/**
	 * data mapper class contains data of "transactionChargeback" notification
	 * for later usage
	 */
	class TransactionChargebackNotificationData {
		/**
		 * @var string
		 */
		public $sessionId		= '';

		/**
		 * @var string
		 */
		public $customerId		= '';
		
		/**
		 * @var string
		 */
		public $transactionId	= '';
		
		/**
		 * @var string
		 */
		public $transactionAuth	= '';
		
		/**
		 * @var string
		 */
		public $title			= '';
		
		/**
		 * @var string
		 */
		public $amount			= '';
		
		/**
		 * @var integer
		 */
		public $currency		= '';
		
		/**
		 * @var string
		 */
		public $country			= '';
		
		/**
		 * @var array
		 */
		public $freeParam		= array();
	}
	
	
// -------------------------------------------------------------------------------------------------
//  start main loop
// -------------------------------------------------------------------------------------------------
	do {
		$oResponse = new NotificationResponse(NotificationStatus::ERROR);

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
		
		switch($action) {
			case 'transactionChargeback':
				$oRequest = new TransactionChargebackNotificationData();
				$oRequest -> sessionId			= isset($_REQUEST['sessionId']) ? (string)$_REQUEST['sessionId'] : null;
				$oRequest -> customerId			= isset($_REQUEST['customerId']) ? (string)$_REQUEST['customerId'] : null;
				$oRequest -> transactionId		= isset($_REQUEST['transactionId']) ? (string)$_REQUEST['transactionId'] : null;
				$oRequest -> transactionAuth	= isset($_REQUEST['transactionAuth']) ? (string)$_REQUEST['transactionAuth'] : null;
				$oRequest -> title				= isset($_REQUEST['title']) ? (string)$_REQUEST['title'] : null;
				$oRequest -> amount				= isset($_REQUEST['amount']) ? (integer)$_REQUEST['amount'] : null;
				$oRequest -> currency			= isset($_REQUEST['currency']) ? (string)$_REQUEST['currency'] : null;
				$oRequest -> country			= isset($_REQUEST['country']) ? (string)$_REQUEST['country'] : null;
				$oRequest -> freeParam			= isset($_REQUEST['freeParam']) AND is_array($_REQUEST['freeParam']) ? $_REQUEST['freeParam'] : null;		
				
				$oResponse -> status		= NotificationStatus::FAILED;
				$oResponse -> statusMessage	= '';
			break;
			
			case null:
				$oResponse -> statusMessage = sPrintF('action is required but empty');
			break 2; // switch, do...while
			break 2;
			default:
				$oResponse -> statusMessage = sPrintF('action "%s" not supported', $action);
			break 2; // switch, do...while
		}		
	
	/*
	 perform your notification processing on $oRequest here
	 e.g. validation, storage or logging
	 
	 if an error occurred simply "break" do...while loop
	 optionally assign statusmessage $oResponse -> statusMessage = 'damm, error';
	 you can later read in controlcenter
	*/
		
	// example: simplified validation
		$err = array();
		if( empty($oRequest -> sessionId) ) $err['sessionId']				= 'sessionId is empty';
		if( empty($oRequest -> customerId) ) $err['customerId']				= 'customerId is empty';
		if( empty($oRequest -> transactionId) ) $err['transactionId']		= 'transactionId is empty';
		if( empty($oRequest -> transactionAuth) ) $err['transactionAuth']	= 'transactionAuth is empty';
		if( empty($oRequest -> title) ) $err['title']						= 'title is empty';
		if( empty($oRequest -> amount) ) $err['amount']						= 'amount is empty';

		if( count($err) > 0) {
			$oResponse -> statusMessage = implode(', ', $err);
			break;
		}
	
		
		$oResponse -> status		= NotificationStatus::SUCCESS;
		$oResponse -> statusMessage	= '';
	} while(0);
	
// -------------------------------------------------------------------------------------------------
//  send response to client
// -------------------------------------------------------------------------------------------------
	header('Content-type: text/plain; charset=iso-8859-1');
	header('X-MCP-API-ResponseServiceProtocol: ' . TServiceProtocol::NVP);
	echo TNvpSerializer::serialize($oResponse);
?>