<?php
/**
 * TODO ARL: Enter description
 *
 */
class PaymentGateway_PayFlow_Pro extends Objeto {
	
	/**
	 * URL used for Transactions on test mode
	 *
	 * @var string
	 */
	const URL_TEST = 'https://pilot-payflowpro.verisign.com';
	/**
	 * URL used for Transactions on live mode (real charges)
	 *
	 * @var string
	 */
	const URL_LIVE = 'https://payflowpro.verisign.com';

	/**
	 * Merchant information array (user, vendor, partner and password)
	 *
	 * @var array
	 */
	protected $merchantData = array();
	
	/**
	 * Is this transaction a test?
	 *
	 * @var boolean
	 */
	protected $testTransaction;
	
	/**
	 * Transaction object
	 *
	 * @var PaymentGateway_PayFlow_Pro_Transaction
	 */
	protected $transaction;
	
	/**
	 * Creates a new instance of the PayFlow generic object.
	 * REMOVE_ADDR is used as the customer IP address
	 *
	 * @param string $user
	 * @param string $vendor
	 * @param string $partner
	 * @param string $password
	 * @param boolean $isTest
	 */
	public function __construct( $user, $vendor, $partner, $password,
	                             PaymentGateway_PayFlow_Pro_Transaction $transaction, $isTest = false ){
		parent::__construct();
		$this->setUser( $user );
		$this->setVendor( $vendor );
		$this->setPartner( $partner );
		$this->setPassword( $password );
		$this->setTransaction( $transaction );
		$this->testTransaction = $isTest;
	}
	
	/**
	 * Sets the Merchant User Account.
	 *
	 * @param string $user - Max 64 chars.
	 * @todo Validate data
	 */
	protected function setUser( $user ){
		$this->merchantData[ 'USER' ] = $user;
	}
	
	/**
	 * Your merchant login ID that you created when you registered for the Payflow Pro account.
	 *
	 * @param string $vendor - Max 64 chars
	 * @todo Validate Data
	 */
	protected function setVendor( $vendor ){
		$this->merchantData[ 'VENDOR' ] = $vendor;
	}
	
	/**
	 * The ID provided to you by the authorized PayPal eseller who registered you for the Payflow Pro service.
	 *
	 * @param string $partner - Max 12 chars.
	 * @todo Validate Data
	 */
	protected function setPartner( $partner ){
		$this->merchantData[ 'PARTNER' ] = $partner;
	}
	
	/**
	 * The 6- to 32-character password that you defined while registering for the account.
	 *
	 * @param string $password - 6-32 chars.
	 * @todo ValidateData
	 */
	protected function setPassword( $password ){
		$this->merchantData[ 'PWD' ] = $password;
	}
	
	/**
	 * Returns true if the transaction is in test mode, false otherwise. 
	 *
	 * @return boolean
	 */
	protected function isTestTransaction(){
		return $this->testTransaction;
	}
	
	/**
	 * Sets the transaction object that will be processed
	 *
	 * @param PayFlow_Transaction $transaction
	 */
	public function setTransaction( PaymentGateway_PayFlow_Pro_Transaction $transaction ){
		$this->transaction = $transaction;
	}
	
	/**
	 * Gets the transaction object that will be processed by PayFlow.
	 *
	 * @return PayFlow_Transaction
	 */
	public function getTransaction(){
		return $this->transaction;
	}
	
	/**
	 * Process the current transaction using the Merchant Account.
	 *
	 * @return PaymentGateway_PayFlow_Pro_Result_Approved
	 * @throws PaymentGateway_PayFlow_Pro_Result_Declined
	 */
	public function process(){
		
		$data = $this->getNVPString();
		$result = $this->sendRequestToGateway( $data );
		
		$resultCode = intval( $result['RESULT'] );
		if ( $resultCode == 0 ){
			// Approved transaction
			return new PaymentGateway_PayFlow_Pro_Result_Approved( $result );
		}
		else {
			// Declined transaction
			$msg = 'Error ' . $resultCode .  ': ' .$result['RESPMSG'];
			throw new PaymentGateway_PayFlow_Pro_Result_Declined( $msg, $resultCode, $result );
		}

	}
	
	/**
	 * Returns the NVP string based on the transaction's data and Merchant Account
	 * 
	 * @return string
	 */
	protected function getNVPString(){
		
		$arrNVP = array();
		
		// Merge Transaction Data and Merchant Account information
		$data = array_merge( $this->transaction->getData(), $this->merchantData );
		
		foreach( $data as $name => $value ){
			$name = strtoupper( $name );
			// Replace invalid characters that have special meaning for the NVP string
			$value = str_replace( array( "'", '"', '&', '=' ), '', $value );
			$arrNVP[] = strtoupper( $name ) . '=' . $value;
		}
		
		// Create a single string containing all the information
		return join( '&', $arrNVP );
		
	}

	/**
	 * Returns the HTTP Headers used by CURL to submit the request
	 *
	 * @return array
	 */
	protected function getCurlHeaders(){
		$headers = array();
		$headers[] = "Content-Type: text/namevalue";
		$headers[] = "X-VPS-Timeout: 45";
		$uniqueId = ( $this->transaction->getDataValue( 'INVNUM' ) != null ) ?
		              $this->transaction->getDataValue( 'INVNUM' ) :
		              md5(uniqid());
		$headers[] = "X-VPS-Request-ID:" . $uniqueId ;
		
		return array_merge( $headers, $this->getAdditionalHeaders() );
		
	}
	
	/**
	 * Defines additional CURL Headers to be send.
	 *
	 * @return array
	 */
	protected function getAdditionalHeaders(){
		return array();
	}
	
	/**
	 * Retrieves the current server url according if this is a testTransaction
	 *
	 * @return unknown
	 */
	protected function getServerUrl(){
		if ( $this->isTestTransaction() ){
			return self::URL_TEST;
		}
		else {
			return self::URL_LIVE;
		}
	}
	
	/**
	 * Returns the User Agent used by CURL to submit the request 
	 *
	 * @return string
	 */
	protected function getCurlUserAgent(){
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	/**
	 * Sends the HTTPS request to PayFlow using CURL
	 *
	 * @param string $data
	 * @return array
	 */
	protected function sendRequestToGateway( $data ){
		$submitUrl = $this->getServerUrl();
		$headers = $this->getCurlHeaders();
		$userAgent = $this->getCurlUserAgent();
		
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $submitUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HEADER, 1); 		// tells curl to include headers in response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 	// return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); 		// times out after 90 secs
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 	// this line makes it work under https
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 	//adding POST data
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2); 	//verifies ssl certificate
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE); 	//forces closure of connection when done 
    curl_setopt($ch, CURLOPT_POST, 1); //data sent as POST 
		
    $i=1;
    while ($i++ <= 3) {
			$result = curl_exec($ch);
			$headers = curl_getinfo($ch);
			if ($headers['http_code'] != 200) {
				sleep(5);  // Let's wait 5 seconds to see if its a temporary network issue.
			}
			elseif ($headers['http_code'] == 200) {
				// we got a good response, drop out of loop.
        break;
			}
    }

		curl_close($ch);
    if ($headers['http_code'] != 200) {
      throw new PaymentGateway_PayFlow_Exception( "Wrong Payment Gateway URL" );
    }

    $result = strstr($result, "RESULT");

    $proArray = array();
    while(strlen($result)){
        // name
        $keypos= strpos($result,'=');
        $keyval = substr($result,0,$keypos);
        // value
        $valuepos = strpos($result,'&') ? strpos($result,'&'): strlen($result);
        $valval = substr($result,$keypos+1,$valuepos-$keypos-1);
        // decoding the respose
        $proArray[$keyval] = $valval;
        $result = substr($result,$valuepos+1,strlen($result));
    }
    return $proArray;
	}
}
?>