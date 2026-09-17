<?php
class PaymentGateway_PayFlow_Pro_Result_Approved extends Objeto {
	
	/**
	 * The array containing all the results of the transaction
	 *
	 * @var unknown_type
	 */
	protected $result;
	
	public function __construct( $result ){
		parent::__construct();
		$this->result = $result;
	}
	
	/**
	 * Retrieves a transaction value from the result array according the the name provided.
	 *
	 * @param string $name - lower case letters will be converted to upper case.
	 * @return string or null if the value name doesn't exist. 
	 */
	public function getTransactionValue( $name ){
		$name = strtoupper( $name );
		if ( isset( $this->result[ $name ] ) ){
			return $this->result[ $name ];
		}
		else {
			return null;
		}
	}

	/**
	 * Gets the Result Code from this transaction (SHOULD ALWAYS BE 0)
	 *
	 * @return integer
	 * @see PayFlow Pro Developer's Guide "RESULT Codes and RESPMSG Values" p.51
	 */
	public function getResultCode(){
		return intval( $this->getTransactionValue( 'RESULT' ) );
	}
	
	/**
	 * Retrieves the Transaction's Reference Number
	 *
	 * @return string - Alphanumeric (12 chars)
	 */
	public function getReferenceNumber(){
		return $this->getTransactionValue( 'PNREF' );
	}
	
	/**
	 * Returns the CVV2 Match Result 
	 *
	 * @return string - Y,N,X or empty string
	 */
	public function getCVV2Result(){
		return $this->getTransactionValue( 'CVV2MATCH' );
	}

	/**
	 * The response message returned with the transaction result. 
	 * 
	 * Exact wording varies. Sometimes a colon appears after the initial RESPMSG 
	 * followed by more detailed information.
	 *
	 * @return string
	 * @see PayFlow Pro Developer's Guide "RESULT Codes and RESPMSG Values" p.51
	 */
	public function getResponseMessage(){
		return $this->getTransactionValue( 'RESPMSG' );
	}

	/**
	 * Returned for Sale, Authorization, and Voice Authorization transactions. 
	 * AUTHCODE is the approval code obtained over the telephone from the 
	 * processing network.
	 *
	 * @return string - 6 chars
	 */
	public function getAuthorizationCode(){
		return $this->getTransactionValue( 'AUTHCODE' );
	}
	
	/**
	 * AVS address responses are for advice only. This process does not affect the 
	 * outcome of the authorization.
	 *
	 * @return string - Y, N, X or empty string
	 * @see PayFlow Developer's Guide "Using Address Verification Service" p.44
	 */
	public function getAVSAddressCode(){
		return $this->getTransactionValue( 'AVSADDR' );
	}
	
	/**
	 * Address verification service ZIP code responses are for advice only. 
	 * This process does not affect the outcome of the authorization
	 *
	 * @return string - Y, N, X or empty string
	 * @see PayFlow Developer's Guide "Using Address Verification Service" p.44
	 */
	public function getAVSZipCode(){
		return $this->getTransactionValue( 'AVSZIP' );
	}
	
	/**
	 * International AVS address responses are for advice only. This value does 
	 * not affect the outcome of the transaction. Indicates whether AVS response 
	 * is international (Y), US (N), or cannot be determined (X). 
	 * 
	 * Client version 3.06 or later is required
	 *
	 * @return string - Y, N, X or empty string
	 * @see PayFlow Developer's Guide "Using Address Verification Service" p.44
	 */
	public function getInternationalAVSCode(){
		return $this->getTransactionValue( 'IAVS' );
	}

	/////////////////////////////////
	// Recurrent Billing Responses //
	/////////////////////////////////
	
	/**
	 * Gets the Profile ID for Recurrent Billing transactions
	 * Profile IDs for test profiles start with the characters RT.
	 * Profile IDs for live profiles start with RP.
	 *
	 * @return string
	 */
	public function getProfileId(){
		return $this->getTransactionValue( 'PROFILEID' );
	}
	
	/**
	 * Reference number to this particular action request for Recurrent Billing Transactions.
	 *
	 * @return unknown
	 */
	public function getRecurrentReferenceNumber(){
		return $this->getTransactionValue( 'RPREF' );
	}

	/**
	 * Returns the Transaction Reference Number for the Optional Transaction (setup/initial fee) for Recurrent Billing Transactions
	 *
	 * @return string
	 */
	public function getOptionalTransactionReferenceNumber(){
		return $this->getTransactionValue( 'TRXPNREF' );
	}
	
	/**
	 * Returns the Result Code for the Optional Transaction (setup/initial fee) for Recurrent Billing Transactions
	 *
	 * @return integer
	 */
	public function getOptionalTransactionResultCode(){
		return $this->getTransactionValue( 'TRXRESULT' );
	}
	
	/**
	 * Returns the Response Message for the Optional Transaction (setup/initial fee) for Recurrent Billing Transactions
	 *
	 * @return string
	 */
	public function getOptionalTransactionResponseMessage(){
		return $this->getTransactionValue( 'TRXRESPMSG' );
	}
	
}
?>