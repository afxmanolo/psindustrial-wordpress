<?php

class PaymentGateway_USAePay_Check extends PaymentGateway_USAePay {
	
	/**
	 * Creates a new instance for a Check (or Check Credit) Transaction.
	 *
	 * @param string $key            - USA ePay Merchant Key
	 * @param integer $invoiceNumber - The UNIQUE Invoice number of this transaction
	 * @param integer $account       - Account Number
	 * @param integer $routing       - Routing Number
	 * @param boolean $isTest        - Whether this transaction is processed for real or not.
	 */
	public function __construct( $key, $invoiceNumber, $account, $routing, $check = null, $isTest = false ){
		parent::__construct( $key, $invoiceNumber, $isTest );
		$this->transaction->command = "check";
		$this->setAccountNumber( $account );
		$this->setRoutingNumber( $routing );
		$this->setCheckNumber( $check );
	}
	
	/**
	 * Sets the transaction to be a "CheckCredit" type
	 * 
	 * @see umTransaction::$command
	 */
	public function setCheckCredit(){
		$this->transaction->command = "checkcredit";
	}
	
	/**
	 * Sets the Check's Bank Account Number 
	 *
	 * @param string $account
	 */
	public function setAccountNumber( $account ){
		$this->transaction->account = $account;
	}
	
	/**
	 * Sets the Check's Routing Number
	 *
	 * @param string $routing
	 */
	public function setRoutingNumber( $routing ){
		$this->transaction->routing = $routing;
	}
	
	/**
	 * Sets the Check Number
	 *
	 * @param string $checkNum
	 */
	public function setCheckNumber( $checkNum ){
		$this->transaction->checknum = $checkNum;
	}
	
	/**
	 * Set's the US Social Security Number of the customer. No dashes or spaces
	 *
	 * @param string $ssn
	 */
	public function setSocialSecurityNumber( $ssn ){
		if ( !preg_match( '/^\d{9}$/', $ssn)){
			throw new WrongDataTypeException( '9 digit integer is expected' );
		}
		else {
			$this->transaction->ssn = $ssn;
		}
	}

	/**
	 * Sets the US Drivers Licence Number (15chars)
	 *
	 * @param string $dlnum
	 * @todo ARL: Limit up to 15 characters the driver licence
	 */
	public function setDriversLicenceNumber( $dlnum ){
		$this->transaction->dlnum = $dlnum;
	}
	
	/**
	 * Sets the US Driver's Licence State (2 chars)
	 *
	 * @param string $dlstate
	 * @todo ARL: Limit the Driver's Licence State to 2 chars
	 */
	public function setDriversLicenceState( $dlstate ){
		$this->transaction->dlstate = $dlstate;
	}
	
}
?>