<?php

class PaymentGateway_USAePay_Result_Approved extends Objeto {
	
	////////////////////////////////
	// General Result Information //
	////////////////////////////////
	
	/**
	 * Result (Approved, Declined, Error, Verification). This class expects it to be Approved
	 *
	 * @var string - "Approved" should be ALWAYS the string here
	 */
	protected $result;
	/**
	 * Result Code: A, D, E or V
	 *
	 * @var string - "A" should ALWAYS be the content of the string
	 */
	protected $resultCode;
	/**
	 * Transaction's Authorization Code
	 *
	 * @var string
	 */
	protected $authCode;
	/**
	 * Transaction's Reference Number
	 *
	 * @var integer
	 */
	protected $refNum;
	/**
	 * Transaction's Batch Number
	 *
	 * @var integer
	 */
	protected $batchNum;
	
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
	/**
	 * Whether this transaction is a duplicate.
	 * Authorization Code, Reference Number, etc. are from the original transaction
	 *
	 * @var boolean
	 */
	protected $duplicate;
	
	//////////////////////////////////////////////////////////////////
	// Conversion information when using a Multi-Currency processor //
	//////////////////////////////////////////////////////////////////
	
	/**
	 * Transaction's amount converted to merchant's currency
	 *
	 * @var unknown_type
	 */
	protected $convertedAmount;
	/**
	 * The currency for the converted amount.
	 *
	 * @var unknown_type
	 */
	protected $convertedCurrency;
	/**
	 * Conversion rate used to convert the currency
	 *
	 * @var unknown_type
	 */
	protected $conversionRate;
	
	///////////////////////////////////
	// Recurrent Billing information //
	///////////////////////////////////
	
	/**
	 * Customer number assigned by USA ePay for recurrent transactions only
	 *
	 * @var unknown_type
	 */
	protected $customerNumber;
	
	public function __construct( $result, $resultCode, $authCode, $refNum, $batchNum, 
	                             $avsCode, $cvv2Code,
	                             $avsResult, $cvv2Result, 
	                             $isDuplicate = false, 
	                             $convertedAmount = null, $convertedCurrency = null, 
	                             $conversionRate = null, $customerNumber = null ){
		parent::__construct();
		$this->result = $result;
		$this->resultCode = $resultCode;
		$this->authCode = $authCode;
		$this->refNum = $refNum;
		$this->batchNum = $batchNum;
		
		$this->avsCode = $avsCode;
		$this->avsResult = $avsResult;
		
		$this->cvv2Code = $cvv2Code;
		$this->cvv2Result = $cvv2Result;
		
		$this->duplicate = $isDuplicate;
		$this->convertedAmount = $convertedAmount;
		$this->convertedCurrency = $convertedCurrency;
		$this->conversionRate = $conversionRate;
		$this->customerNumber = $customerNumber;
	}
	
	/**
	 * Transaction's Result (Approved, Declined, Error, Verification). This class expects it to be Approved
	 *
	 * @return string - "Approved" should ALWAYS be the content of the string
	 */
	public function getResult(){
		return $this->result;
	}
	
	/**
	 * Result Code: A, D, E or V
	 *
	 * @return string - "A" should ALWAYS be the content of the string
	 */
	public function getResultCode(){
		return $this->resultCode;
	}
	
	/**
	 * Transaction's Authorization Code
	 *
	 * @return string
	 */
	public function getAuthorizationCode(){
		return $this->authCode;
	}
	
	/**
	 * Transaction's Reference Number
	 *
	 * @return integer
	 */
	public function getReferenceNumber(){
		return $this->refNum;
	}
	
	/**
	 * Transaction's Batch Number
	 *
	 * @return integer
	 */
	public function getBatchNumber(){
		return $this->batchNum;
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
	
	/**
	 * Whether this transaction was duplicated or not. If so, Authorization Code, 
	 * Reference Number, etc. are from the original Transaction 
	 *
	 * @return boolean
	 */
	public function isDuplicate(){
		return $this->duplicate;
	}
	
	/**
	 * If using a Multi-Currency Processor, this is the amount converted to the merchant's currency
	 *
	 * @return float
	 */
	public function getConvertedAmount(){
		return $this->convertedAmount;
	}
	
	/**
	 * If using a Multi-Currency Processor, this is the merchant's currency
	 *
	 * @return string
	 */
	public function getConvertedCurrency(){
		return $this->convertedCurrency;
	}
	
	/**
	 * If using a Multi-Currency Processor, this is the conversion rate used to 
	 * convert the original currency to the merchant's currecy
	 *
	 * @return float
	 */
	public function getConversionRate(){
		return $this->conversionRate;
	}
	
	/**
	 * The USA ePay customer number for recurrent billings only
	 *
	 * @return integer
	 */
	public function getCustomerNumber(){
		return $this->customerNumber;
	}
	
}
?>