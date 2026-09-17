<?php
class ProjectLibrary_SDO_Core_Validator_Exception extends Exception {
	
	protected $entity;
	
	/**
	 * @var array
	 */
	protected $errors = array();
	
	public function __construct( ProjectLibrary_SDO_Core_Validator $validator ){
		
		$msg = 'The validator ' . get_class( $validator ) . ' has failed to validate the object given. See errors for details.';
		$code = 1;
		$this->errors = $validator->getErrorTable();
		$this->entity = $validator->getEntity();
		
		parent::__construct( $msg, $code );
	}
	
	public function getErrors(){
		return $this->errors;
	}
	
	public function getErrorDescription( $name ){
		return ( isset( $this->errors[ $name ] ) ) ? $this->errors[ $name ] : null;
	}
	
}
?>