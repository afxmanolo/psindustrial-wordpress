<?php
class PaymentGateway_USAePay_Result_Declined extends Exception {

	/**
	 * Transaction Result (Declined, Error or Verification).
	 *
	 * @var string
	 */
	protected $transactionResult;
	/**
	 * Transaction Error Description
	 *
	 * @var string
	 */
	protected $transactionError;
	/**
	 * Transaction Error Code
	 *
	 * @var integer
	 */
	protected $transactionErrorcode;
	/**
	 * Is the Transaction declined without a reason?
	 *
	 * @var boolean
	 */
	protected $transactionEmpty;
	/**
	 * CURL error generated while sending the payment information.
	 * Useful for debugging purposes
	 *
	 * @var string
	 */
	protected $curlError;
	
	/**
	 * Transaction's AVS code
	 *
	 * @var string
	 */
	protected $avsCode;
	/**
	 * Transaction's AVS code description
	 *
	 * @var string
	 */
	protected $avsResult;
	
	/**
	 * Transaction's CVV2 code result
	 *
	 * @var unknown_type
	 */
	protected $cvv2Code;
	/**
	 * Transaction's CVV2 code description
	 *
	 * @var string
	 */
	protected $cvv2Result;
	
	public function __construct( $result, $error, $errorCode, $curlError, $isBlank = false,
	                             $avsCode = null, $avsResult = null, 
	                             $cvv2Code = null, $cvv2Result = null ){
		
		parent::__construct( $result . ': ' . $error, $errorCode );
		
		$this->transactionResult = $result;
		$this->transactionError = $error;
		$this->transactionErrorcode = $errorCode;
		$this->curlError = $curlError;
		$this->transactionEmpty = $isBlank;
		
		$this->avsCode = $avsCode;
		$this->avsResult = $avsResult;
		
		$this->cvv2Code = $cvv2Code;
		$this->cvv2Result = $cvv2Result;
	}
	
	public function getTransactionResult(){
		return $this->transactionResult;
	}
	
	public function getTransactionError(){
		return $this->transactionError;
	}
	
	public function getTransactionErrorCode(){
		return $this->transactionErrorcode;
	}
	
	public function isTransactionEmpty(){
		return $this->transactionEmpty;
	}
	
	public function getCurlError(){
		return $this->curlError;
	}
	
	/**
	 * Credit Card AVS result code
	 *
	 * @return unknown
	 */
	public function getAvsCode(){
		return $this->avsCode;
	}
	
	/**
	 * Credit Card AVS check result (description)
	 *
	 * @return string
	 */
	public function getAvsResult(){
		return $this->avsResult;
	}
	
	/**
	 * Credit Card CVV2 result code
	 *
	 * @return unknown
	 */
	public function getCvv2Code(){
		return $this->cvv2Code;
	}
	
	/**
	 * Credit Card CVV2 check result (description)
	 *
	 * @return string
	 */
	public function getCvv2Result(){
		return $this->cvv2Result;
	}
	
}
?>