<?php
class PaymentGateway_USAePay_CreditCard extends PaymentGateway_USAePay {

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
		parent::__construct( $key, $invoiceNumber, $isTest );
		$this->transaction->command = "check";
		$this->setCardNumber( $cardNumber );
		$this->setCardExpirationDate( $cardExp );
		$this->transaction->command = 'sale';
	}
	
	/**
	 * Sets the Card Number to be processed. Accepted companies:
	 * MasterCard, Visa, American Express, Dinners Club/Carte Blanche, Discover, enRoute, JCB
	 * 
	 * @param string $cardNumber
	 * @see umVerifyCreditCardNumber() function
	 */
	public function setCardNumber( $cardNumber, $validate = false ){
		if ( $validate ){
			if ( umVerifyCreditCardNumber( $cardNumber ) == 0 ){
				throw new WrongDataTypeException( 'Credit Card Number is incorrect. It fails the Luhn Mod-10 or it doesn\'t correspond to the accepted CC companies by USA ePay' );
			}
		}
		$this->transaction->card = $cardNumber;
	}
	
	/**
	 * Verified By Visa and Mastercard SecureCode
	 *
	 * @param string $auth
	 */
	public function setCardAuthorizationCode( $authCode ){
		$this->transaction->cardauth = $authCode;
	}
	
	/**
	 * Sets the Card's Expiration Date (MMYY)
	 *
	 * @param string $expDate
	 */
	public function setCardExpirationDate( $expDate){
		if ( !preg_match( '/^\d{4}$/', $expDate ) ){
			throw new WrongDataTypeException( 'Expiration date should be in the MMYY format.' );
		}
		$this->transaction->exp = $expDate;
	}
	
	/**
	 * Sets the Card Holder Details
	 *
	 * @param string $name
	 * @param string $street
	 * @param string $zip
	 */
	public function setCardDetails( $name, $street = null, $zip = null ){
		if ( $zip != null && !preg_match( '/^\d{5}$/', $zip ) ){
			throw new WrongDataTypeException( 'Zip code must be a 5 digit string' );
		}
		$this->transaction->cardholder = $name;
		$this->transaction->street = $street;
		$this->transaction->zip = $zip;
	}
	
}

?>