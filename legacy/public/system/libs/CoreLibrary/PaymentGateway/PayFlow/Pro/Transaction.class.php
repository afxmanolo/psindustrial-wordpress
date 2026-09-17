<?php
class PaymentGateway_PayFlow_Pro_Transaction extends Objeto {
	
  /////////////////////
	// Payment Methods //
	/////////////////////
	const PAYMENT_AUTOMATED_CLEARING_HOUSE = 'A';  // See ACH Guide
	const PAYMENT_CREDIT_CARD = 'C';
	const PAYMENT_PINLESS_DEBIT = 'D';
	const PAYMENT_ELECTRONIC_CHECK = 'E';
	const PAYMENT_TELECHECK = 'K';
	const PAYMENT_PAYPAL = 'P';
	
	///////////////////////
	// Transaction Types //
	///////////////////////
	const TRANSACTION_SALE = 'S';
	const TRANSACTION_CREDIT = 'C';
	const TRANSACTION_AUTHORIZATION = 'A';
	const TRANSACTION_DELAYED_CAPTURE = 'D';
	const TRANSACTION_VOID = 'V';
	const TRANSACTION_VOICE_AUTHORIZATION = 'F';
	const TRANSACTION_INQUIRY = 'I';
	/**
	 * A type N transaction represents a duplicate transaction (version 4 SDK or HTTPS interface only) with a PNREF the same as the original. It appears only in the PayPal Manager user interface will never settle.
	 */
	const TRANSACTION_DUPLICATE = 'N';	
	
	//////////////////////
	// Verbosity Levels //
	//////////////////////
	/**
	 * LOW setting causes PayPal to normalize the transaction result values.
	 */
	const VERBOSITY_LOW = 'LOW';
	/**
	 * MEDIUM setting causes the errors to be in RAW messages (more detailed but can be user un-friendly)
	 */
	const VERBOSITY_MEDIUM = 'MEDIUM';
	
	//////////////////////
	// Transaction data //
	//////////////////////
	
	/**
	 * Array that holds all the transaction details
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Creates a new Transaction Object
	 *
	 * @param float $amount - 2 decimal float number (no thousand separator, 10 integers max. )
	 * @param string $currency - 3 letter currency code (USD, EUR, GBP, CAD, JPY, AUD)
	 * @param string $paymentMethod - See class constants PAYMENT_*
	 * @param string $transactionType - See class constants TRANSACTION_*
	 */
	public function __construct( $amount, $currency = 'USD',
	                             $paymentMethod = self::PAYMENT_CREDIT_CARD, 
	                             $transactionType =  self::TRANSACTION_SALE ){
		parent::__construct();
		$this->setAmount( $amount, $currency );
		$this->setPaymentMethod( $paymentMethod );
		$this->setTransactionType( $transactionType );
		
		// Adds the customer IP to the NVP list
		$this->setExtraData( 'CUSTIP', $_SERVER['REMOTE_ADDR'] );
		
	}
	
	/**
	 * Sets the Credit Card information
	 *
	 * @param string $ccNumber         - Credit Card Number (max. 19 chars)
	 * @param string $ccExpirationDate - 4 digit string (MMYY) 
	 * @param string $cvv2             - 4 digit CVV2 code (optional)
	 * @todo Validate data
	 */
	public function setCreditCard( $ccNumber, $ccExpirationDate, $cvv2 = null ){
		$this->data[ 'ACCT' ] = $ccNumber;
		$this->data[ 'EXPDATE'] = $ccExpirationDate;
		if ( $cvv2 != null ){
			$this->data[ 'CVV2' ] = $cvv2;
		}
	}

	/**
	 * Sets the account holder information 
	 *
	 * @param string $firstName  - Max 30 chars
	 * @param string $middleName - 
	 * @param string $lastName   - Max 30 chars
	 * @param string $email      - Max 64 chars
	 * @todo Find out max length
	 */
	public function setBillingDetails( $firstName, $middleName, $lastName ){
		$this->data[ 'FIRSTNAME' ]  = $firstName;
		$this->data[ 'MIDDLENAME' ] = $middleName;
		$this->data[ 'LASTNAME' ]   = $lastName;
	}
	
	/**
	 * Sets the customer email
	 *
	 * @param string $email
	 */
	public function setEmail( $email ){
		$this->data[ 'EMAIL' ]      = $email;
	}
	
	/**
	 * Sets the billing address
	 *
	 * @param string $street - Max 30 chars
	 * @param string $city   - Max 40 chars
	 * @param string $state  - Max 10 chars
	 * @param string $zip    - 5 or 9 chars (no dashes, spaces, etc)
	 * @param string $country - ISO country code (2 letters)
	 */
	public function setBillingAddress( $street, $city, $state, $zip, $country = 'US' ){
		$this->data[ 'STREET' ]  = $street;
		$this->data[ 'CITY' ]    = $city;
		$this->data[ 'STATE']    = $state;
		$this->data[ 'ZIP' ]     = $zip;
		$this->data[ 'COUNTRY' ] = $country;
	}

	/**
	 * Sets the shipping address
	 *
	 * @param string $street - Max 30 chars
	 * @param string $city   - Max 30 chars
	 * @param string $state  - Max 30 chars
	 * @param string $zip    - 5 or 9 chars (no dashes, spaces, etc)
	 * @param string $country - ISO country code (2 letters)
	 */
	public function setShippingAddress( $street, $city, $state, $zip, $country = 'US'){
		$this->data[ 'SHIPTOSTREET' ]  = $street;
		$this->data[ 'SHIPTOCITY' ]    = $city;
		$this->data[ 'SHIPTOSTATE']    = $state;
		$this->data[ 'SHIPTOZIP' ]     = $zip;
		$this->data[ 'SHIPTOCOUNTRY' ] = $country;
	}
	
	/**
	 * Sets the person who will receive the shipping 
	 *
	 * @param string $firstName
	 * @param string $middleName
	 * @param string $lastName
	 * @todo Find out max length
	 */
	public function setShippingDetails( $firstName, $middleName, $lastName ){
		$this->data[ 'SHIPTOFIRSTNAME' ]  = $firstName;
		$this->data[ 'SHIPTOMIDDLENAME' ] = $middleName;
		$this->data[ 'SHIPTOLASTNAME' ]   = $lastName;
	}
	
	/**
	 * Sets the total amoun to charge the customer.
	 * CURRENCY is applicable only to processors that support transaction-level currency.
	 *
	 * @param float $amount    - 2 decimals required, no thousands separator
	 * @param string $currency - 3 letter currency code (USD, EUR, GBP, CAD, JPY, AUD)
	 * @todo Validate Data
	 */
	public function setAmount( $amount, $currency = 'USD' ){
		$amount = number_format( $amount, 2, '.', '' );
		$this->data[ 'AMT' ]     = $amount;
		$this->data[ 'CURRENCY'] = $currency;
	}
	
	/**
	 * Sets the transaction description. Long descriptions are trimmed
	 *
	 * @param string $description - 127 Chars max
	 */
	public function setTransactionDescription( $description ){
		$this->data[ 'ORDERDESC' ] = substr( $description, 0, 127 );
	}
	
	/**
	 * Sets the invoice number.
	 * 
	 * Merchant invoice number is used for authorizations and settlements and,
	 * depending on your merchant bank, will appear on your customer's credit
	 * card statement and your bank reconciliation report.
	 *
	 * If you do not provide an invoice number, the transaction ID (PNREF) will be
	 * submitted.
	 * 
	 * @param string $number - 9 chars max.
	 */
	public function setInvoiceNumber( $number ){
		$this->data[ 'INVNUM' ] = $number;
	}

	/**
	 * Sets a Merchant-defined value for reporting and auditing purposes.
	 *
	 * @param string $comment - 128 char. max.
	 */
	public function setComment( $comment ){
		$this->data[ 'COMMENT1' ] = substr( $comment, 0, 128 );
	}
	
	/**
	 * Sets a Merchant-defined value for reporting and auditing purposes.
	 *
	 * @param string $comment - 128 char. max.
	 */
	public function setComment2( $comment ){
		$this->data[ 'COMMENT2' ] = substr( $comment, 0, 128 );
	}
	
	/**
	 * Sets the Payment Method (tender)
	 *
	 * @param string $paymentMethod
	 * @see Class Constants PAYMENT_*
	 */
	public function setPaymentMethod( $paymentMethod = self::PAYMENT_CREDIT_CARD ){
		$this->data[ 'TENDER' ] = $paymentMethod;
	}
	
	/**
	 * Sets the Transaction type
	 *
	 * @param string $transactionType
	 * @see Class constants TRANSACTION_*
	 */
	public function setTransactionType( $transactionType = self::TRANSACTION_SALE ){
		$this->data[ 'TRXTYPE' ] = $transactionType;
	}
	
	/**
	 * Sets the verbosity level (description detail messages for declines and error conditions)
	 *
	 * @param string $level
	 * @see Class constant VERBOSITY_*
	 */
	public function setVerbosity( $level = self::VERBOSITY_LOW ){
		$this->data[ 'VERBOSITY' ] = $level;
	}
	
	/**
	 * Adds a new name-value-pair (NVP)
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setExtraData( $name, $value ){
		$name = strtoupper( $name );
		$this->data[ $name ] = $value;
	}
	
	/**
	 * Retuns the entire array containing all the information
	 *
	 * @return array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * Returns the value for the given data name
	 *
	 * @param string $name (case in-sensitive)
	 * @return mixed
	 */
	public function getDataValue( $name ){
		if ( isset( $this->data[ $name ] ) ){
			return $this->data[ $name ];
		}
		else {
			return null;
		}
	}
}
?>