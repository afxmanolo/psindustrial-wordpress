<?php

class PaymentGateway_USAePay_RecurrentBilling extends PaymentGateway_USAePay_CreditCard {

	/**
	 * Creates a new instance for a Check (or Check Credit) Transaction.
	 *
	 * @param string $key            - USA ePay Merchant Key
	 * @param integer $invoiceNumber - The UNIQUE Invoice number of this transaction
	 * @param integer $cardNumber    - Credit Card Number
	 * @param integer $cardExp       - Credit Card Expiration Date (MMYY)
	 * @param boolean $isTest        - Whether this transaction is processed for real or not.
	 */
	public function __construct( $key, $invoiceNumber, $cardNumber, $cardExp, $isTest = false ){
		parent::__construct( $key, $invoiceNumber, $cardNumber, $cardExp, $isTest = false );
		throw new Exception( 'Class not implemented yet.', 0 );
	}
	
}

?>