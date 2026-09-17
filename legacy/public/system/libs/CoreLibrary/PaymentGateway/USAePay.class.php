<?php

abstract class PaymentGateway_USAePay extends Objeto {
	
	/**
	 * USA ePay transaction objet
	 *
	 * @var umTransaction
	 */
	protected $transaction; 
	
	/**
	 * Creates a new instance of the USAePay class that handles payment using 
	 * USA ePay gateway
	 *
	 * @param string $key            - USA ePay Merchant Key
	 * @param integer $invoiceNumber - The UNIQUE Invoice number of this transaction
	 * @param boolean $isTest        - Whether this transaction is processed for real or not.
	 */
	public function __construct( $key, $invoiceNumber, $isTest = false ){
		parent::__construct();
		
		$this->transaction = new umTransaction();
		$this->transaction->key = $key;
		$this->setInvoiceNumber( $invoiceNumber );
		
		if ( $isTest ){
			$this->transaction->testmode = 1;
		}
		
		$this->transaction->software = '' . $this->transaction->software;
	}
	
	/**
	 * Sets the total amount to be charged in this transaction using detailed information
	 *
	 * This method prevents incoungrences that may cause the transaction to fail
	 * 
	 * @param float $subtotal
	 * @param float $tax
	 * @param float $discount
	 * @param float $shipping
	 * @param float $discount - Positive number (it will be substracted automatically).
	 */
	public function setAmount( $subtotal, $tax = 0, $discount = 0, $shipping = 0, $tip = 0, $discount = 0 ){
		$this->transaction->subtotal = $subtotal;
		$this->transaction->tax = $tax;
		$this->transaction->shipping = $shipping;
		$this->transaction->discount = $discount;
		
		$this->transaction->amount = $subtotal + $tax + $shipping + $tip - $discount;
	}
	
	/**
	 * Sets the Transaction description for the charge
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription( $description ){
		$this->transaction->description = $description;
	}
	
	/**
	 * This allows fraud blocking on the customers IP address.
	 *
	 * @param string $ip 
	 * @see USA ePay Documentation for more information
	 */
	public function setClientIP( $ip ){
		$this->transaction->ip = $ip;
	}
	
	/**
	 * Sets the Invoice Number (or Order ID) for this transaction. This is REQUIRED
	 * 
	 * USA ePay limits "invoice" to 10 digits and requires the usage of "orderId"
	 * for up to 64 characters 
	 *
	 * @param integer $invoice - up to 10 digits for regular invoice, up to 64 for "Order Id"
	 */
	public function setInvoiceNumber( $invoice ){
		if( strlen( $invoice) > 10 ){
			$this->transaction->orderid = $invoice;
		}
		else {
			$this->transaction->invoice = $invoice;
		}
	}
	
	/**
	 * Processes the Transaction
	 *
	 * @return PaymentGateway_USAePay_Result_Approved
	 * @throws PaymentGateway_USAePay_Result_Declined
	 */
	public function processTransaction(){
		if ( $this->transaction->Process() ) {
			$result = new PaymentGateway_USAePay_Result_Approved( 
			                                     $this->transaction->result,
			                                     $this->transaction->resultcode,
			                                     $this->transaction->authcode,
			                                     $this->transaction->refnum,
			                                     $this->transaction->batch,
			                                     $this->transaction->avs_result_code,
			                                     $this->transaction->cvv2_result_code,
			                                     $this->transaction->avs_result,
			                                     $this->transaction->cvv2_result,
			                                     $this->transaction->isduplicate,
			                                     $this->transaction->convertedamount,
			                                     $this->transaction->convertedamountcurrency,
			                                     $this->transaction->conversionrate,
			                                     $this->transaction->custnum
			                                     );
			return $result;
		}
		else {
			throw new PaymentGateway_USAePay_Result_Declined(
			                                    $this->transaction->result,
			                                    $this->transaction->error,
			                                    $this->transaction->errorcode,
			                                    $this->transaction->curlerror,
			                                    $this->transaction->blank,
			                                    $this->transaction->avs_result_code,
			                                    $this->transaction->avs_result,
			                                    $this->transaction->cvv2_result_code,
			                                    $this->transaction->cvv2_result
			                                    );
		}
	}
	
	/**
	 * Returns the Transaction object of USA ePay
	 *
	 * @return umTransaction
	 */
	public function getTransactionObject(){
		return $this->transaction;
	}

	/**
	 * This method verifies that all required data has been set.
	 * 
	 * This method is automatically called before the transaction is processed, 
	 * but doing it previously prevents the declined exception to be thrown by faulty data. 
	 *
	 * @return string or 0 if everything is Ok.
	 */
	public function testData(){
		return $this->transaction->CheckData();
	}

	/**
	 * Sets the billing details
	 *
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $email
	 * @param string $company
	 * @param string $phone
	 * @param string $fax
	 * @param string $website
	 */
	public function setBillingDetails( $firstName, $lastName, $email = null, $company = null, $phone = null, $fax = null, $website = null ){
		$this->transaction->billfname = $firstName;
		$this->transaction->billlname = $lastName;
		$this->transaction->email = $email;
		$this->transaction->billcompany = $company;
		$this->transaction->billphone = $phone;
		$this->transaction->fax = $fax;
		$this->transaction->website = $website;
	}
	
	/**
	 * Sets the shipping details
	 *
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $company
	 * @param string $phone
	 */
	public function setShippingDetails( $firstName, $lastName, $company = null, $phone = null ){
		$this->transaction->shipfname = $firstName;
		$this->transaction->shiplname = $lastName;
		$this->transaction->shipcompany = $company;
		$this->transaction->shipphone = $phone;
	}
	
	/**
	 * Sets the Shipping Address for this transaction
	 *
	 * @param string $street   - Street name and number
	 * @param string $street2  - Optional (Null or empty string)  
	 * @param string $city     - City name
	 * @param string $state    - State abbreviation (2 letters) 
	 * @param string $zip     - 5 digit zip code
	 * @param string $country  - Country ISO code (2 letters)
	 */
	public function setBillingAddress( $street, $street2, $city, $state, $zip, $country = 'US' ){
		if ( !preg_match( '/^\d{5}$/', $zip ) ){
			throw new WrongDataTypeException( 'Zip code must be a 5 digit string' );
		}
		$this->transaction->billstreet  = $street;
		$this->transaction->billstreet2 = $street2;
		$this->transaction->billcity    = $city;
		$this->transaction->billstate   = $state;
		$this->transaction->billzip     = $zip;
		$this->transaction->billcountry = $country;
	}
	
	/**
	 * Sets the Shipping Address for this transaction
	 *
	 * @param string $street   - Street name and number
	 * @param string $street2  - Optional (Null or empty string)  
	 * @param string $city     - City name
	 * @param string $state    - State abbreviation (2 letters) 
	 * @param string $zip     - 5 digit zip code
	 * @param string $country  - Country ISO code (2 letters)
	 */
	public function setShippingAddress( $street, $street2, $city, $state, $zip, $country = 'US' ){
		if ( !preg_match( '/^\d{5}$/', $zip ) ){
			throw new WrongDataTypeException( 'Zip code must be a 5 digit string' );
		}
		$this->transaction->shipstreet  = $street;
		$this->transaction->shipstreet2 = $street2;
		$this->transaction->shipcity    = $city;
		$this->transaction->shipstate   = $state;
		$this->transaction->shipzip     = $zip;
		$this->transaction->shipcountry = $country;
	}
	
}
?>