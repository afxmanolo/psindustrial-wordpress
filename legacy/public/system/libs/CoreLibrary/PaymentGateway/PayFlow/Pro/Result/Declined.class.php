<?php
class PaymentGateway_PayFlow_Pro_Result_Declined extends PaymentGateway_PayFlow_Exception  {
	
	/**
	 * The array containing the result
	 *
	 * @var array
	 */
	protected $result;
	
	public function __construct( $message, $code, $result ){
		parent::__construct( $message, $code );
		$this->result = $result;
	}

	public function getTransactionError(){
		return $this->getResultValue( 'RESPMSG' );
	}
	
	public function getTransactionErrorCode(){
		return $this->getResultValue( 'RESULT' );
	}
	
	public function getReferenceNumber(){
		return $this->getResultValue( 'PNREF' );
	}
	
	public function getResultValue( $name ){
		$name = strtoupper( $name );
		if ( isset( $this->result[ $name ] ) ){
			return $this->result[ $name ];
		}
		else {
			return null;
		}
	}
	
}
?>