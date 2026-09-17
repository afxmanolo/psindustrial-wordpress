<?php
class PaymentGateway_PayFlow_Pro_Transaction_RecurrentBilling extends PaymentGateway_PayFlow_Pro_Transaction {

	///////////////////////
	// Transaction Types //
	///////////////////////
	/**
	 * Sets the transaction to be of a recurrent billing type.
	 * Note: This will be automatically be the case when you set recurrent 
	 * billing transaction details 
	 */
	const TRANSACTION_RECURRENT_BILLING = 'R';
	
	///////////////////////////////
	// Recurrent Billing Actions //
	///////////////////////////////
	/**
	 * Ads a new profile
	 */
	const RECURRENT_BILLING_ACTION_ADD = 'A';
	/**
	 * Make changes to an existing profile. If the profile is currently inactive, then the Modify action reactivates it.
	 */
	const RECURRENT_BILLING_ACTION_MODIFY = 'M';
	/**
	 * Reactivate an inactive profile.
	 */
	const RECURRENT_BILLING_ACTION_REACTIVATE = 'R';
	/**
	 * Deactivate an existing profile.
	 */
	const RECURRENT_BILLING_ACTION_CANCEL = 'C';
	/**
	 * The Inquiry action enables you to view either of the following sets of data about a customer:
	 * - Status of a customer’s profile
	 * - Details of each payment for a profile
	 */
	const RECURRENT_BILLING_ACTION_INQUIRY = 'I';
	/**
	 * Retry a previously failed payment.
	 */
	const RECURRENT_BILLING_ACTION_PAYMENT = 'P';
	
	/////////////////////
	// Payment Periods //
	/////////////////////
	/**
	 * Every week on the same day of the week as the first payment.
	 */
	const RECURRENT_BILLING_PERIOD_WEEKLY = 'WEEK';
	/**
	 * Every other week on the same day of the week as the first payment.
	 */
	const RECURRENT_BILLING_PERIOD_EVERY_TWO_WEEKS = 'BIWK';
	/**
	 * The 1st and 15th of the month. Results in 24 payments per year. 
	 * Payment date can start on 1st to 15th of the month, second payment 15 days 
	 * later or on the last day of the month
	 */
	const RECURRENT_BILLING_PERIOD_TWICE_EVERY_MONTH = 'SMMO';
	/**
	 * Every 28 days from the previous payment date beginning with the first payment date. Results in 13 payments per year.
	 */
	const RECURRENT_BILLING_PERIOD_EVERY_FOUR_WEEKS = 'FRWK';
	/**
	 * Every month on the same date as the first payment. Results in 12 payments per year.
	 */
	const RECURRENT_BILLING_PERIOD_MONTHLY = 'MONT';
	/**
	 * Every three months on the same date as the first payment.
	 */
	const RECURRENT_BILLING_PERIOD_QUARTERLY = 'QTER';
	/**
	 * Every six months on the same date as the first payment.
	 */
	const RECURRENT_BILLING_PERIOD_EVERY_SIX_MONTHS = 'SMYR';
	/**
	 * Every 12 months on the same date as the first payment.
	 */
	const RECURRENT_BILLING_PERIOD_YEARLY = 'YEAR';
	
	/**
	 * Creates a new transaction for a recurrent billing.
	 * Default settings:
	 * ACTION          = 'A'      // Creates a new profile (ADD)
	 * START           = tomorrow // Beginning of the billing cycle starts tomorrow (only accepts days in the future)  
	 * TERM            = 0        // Infinite payment cycles
	 * PAYPERIOD       = 'MONT'   // => 12 payments per year 
	 * MAXFAILPAYMENTS = 1        // 1 failed payment and the profile is cancelled (deactivated)
	 * OPTIONALTRX     = 'A'      // Attempts and Authorization of $1 before creating the profile
	 *
	 * @param float $amount - 2 decimal float number (no thousand separator, 10 integers max. )
	 * @param string $currency - 3 letter currency code (USD, EUR, GBP, CAD, JPY, AUD)
	 * @param string $paymentMethod - Must be PAYMENT_CREDIT_CARD or PAYMENT_AUTOMATED_CLEARING_HOUSE
	 */
	public function __construct( $amount, $currency = 'USD',
	                             $paymentMethod = self::PAYMENT_CREDIT_CARD ){
		parent::__construct( $amount, $currency, 
		                     $paymentMethod,												// 'C' or 'A' 
		                     self::TRANSACTION_RECURRENT_BILLING );
		                     
		$this->setAction( self::RECURRENT_BILLING_ACTION_ADD );
		$this->setStartDateTomorrow();
		$this->setTerm( 0 );
		$this->setPaymentPeriod( self::RECURRENT_BILLING_PERIOD_MONTHLY );
		$this->setMaxFailedPayments( 1 );
		$this->setOptionalTransaction( 1 );
	}
	
	/**
	 * Sets the Recurrent Billing Profile action
	 *
	 * @param string $action - See class constants RECURRENT_BILLING_ACTION_*
	 */
	public function setAction( $action = self::RECURRENT_BILLING_ACTION_ADD ){
		$this->data[ 'ACTION' ] = $action;
	}
	
	/**
	 * Name for the profile (user-specified). Can be used to search for a profile.
	 *
	 * @param string $name - Non-unique identifying text name
	 */
	public function setProfileName( $name ){
		$this->data[ 'PROFILENAME' ] = $name;
	}
	
	/**
	 * Beginning date for the recurring billing cycle used to calculate when payments should be made. 
	 * Use tomorrow’s date or a date in the future. 
	 *
	 * @param string $date - Format MMDDYYYY
	 */
	public function setStartDateAsString( $date ){
		$this->data[ 'START' ] = $date;
	}

	/**
	 * Beginning date for the recurring billing cycle used to calculate when payments should be made. 
	 * Use tomorrow’s date or a date in the future. 
	 *
	 * @param integer $time - Unix timestamp
	 */
	public function setStartDateAsTime( $time ){
		$date = date( 'mdY', strtotime( 'mdY', $time ) );
		$this->data[ 'START' ] = $date;
	}
	
	/**
	 * Beginning date for the recurring billing cycle starts tomorrow.
	 */
	public function setStartDateTomorrow(){
		$this->setStartDateAsString( date( 'mdY', strtotime( date('Y-m-d') . ' +1 day' ) ) );
	}
	
	/**
	 * Number of payments to be made over the life of the agreement.
	 * 
	 * @param integer $numberOfPayments - 0 means that payments should continue until the profile is deactivated. 
	 */
	public function setTerm( $numberOfPayments = 0 ){
		$this->data[ 'TERM' ] = $numberOfPayments;
	}
	
	/**
	 * Specifies how often the payment occurs.
	 *
	 * @param string $period - See class constants RECURRENT_BILLING_PERIOD_*
	 */
	public function setPaymentPeriod( $period = self::RECURRENT_BILLING_PERIOD_MONTHLY ){
		$this->data[ 'PAYPERIOD' ] = $period;  
	}

	/**
	 * PNREF value of the original transaction used to create a new profile
	 *
	 * @param string $PNRef
	 */
	public function setOriginalReferenceNumber( $PNRef ){
		$this->data[ 'ORIGID' ] = $PNRef;
	}

	/**
	 * The number of payment periods for which the transaction is allowed to fail before PayPal cancels a profile.
	 * These periods need not be consecutive (for example, if payments fail in 
	 * January, March, and June, the profile is cancelled).
	 * For example, if you specify 3, then PayPal allows a maximum of three failed
	 * payment periods (possibly with multiple retries during each payment period,
	 * and possibly non-consecutive periods). If the transaction is not approved 
	 * for any three periods (months in the example), then PayPal deactivates the 
	 * profile.
	 * 
	 * @param integer $number - 0 means no limit
	 */
	public function setMaxFailedPayments( $number = 0 ){
		$this->data[ 'MAXFAILPAYMENTS' ] = $number;
	}
	
	/**
	 * Performs an Authorization transaction prior to creating the profile to validate the Credit Card.
	 *
	 * @param float $amount - $1 default
	 */
	public function setOptionalTransaction( $amount = 1 ){
		$amount = number_format( $amount, 2, '.', '' );		// Formats the amount
		$this->data[ 'OPTIONALTRX' ] = self::TRANSACTION_AUTHORIZATION;
		$this->data[ 'OPTIONALTRXAMT' ] = $amount;
	}
	
	/**
	 * Amount of the Optional Transaction. Required only when OPTIONALTRX=S
	 *
	 * @param float $amount
	 */
	public function setInitialFee( $amount ){
		$amount = number_format( $amount, 2, '.', '' );		// Formats the amount
		$this->data[ 'OPTIONALTRX' ] = self::TRANSACTION_SALE;
		$this->data[ 'OPTIONALTRXAMT' ] = $amount;
	}
}
?>