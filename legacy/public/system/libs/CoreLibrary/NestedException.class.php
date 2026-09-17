<?php
class NestedException extends Exception {
	
	protected $nestedException;
	
	public function __construct( $msg, $code = 0, $nestedException = null ){
		parent::__construct( $msg, $code );
		$this->nestedException = $nestedException;
	}
	
	public function getNestedException(){
		return $this->nestedException;
	}
	
}
?>