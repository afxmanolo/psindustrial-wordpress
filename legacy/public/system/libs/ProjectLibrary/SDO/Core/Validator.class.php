<?php
class ProjectLibrary_SDO_Core_Validator extends Objeto {
	
	/**
	 * Validator object
	 *
	 * @var Validator
	 */
	protected $validator;

	/**
	 * @var ProjectLibrary_Entity
	 */
	protected $entity;
	
	/**
	 * Required Fields for the User DB entity
	 *
	 * @var array
	 */
	protected $required = array();

	public function __construct( ProjectLibrary_Entity $entity ){ 
		parent::__construct();
		$this->entity = $entity;
		$this->validator = new Validator();
		$this->setup();
	}
	
	protected function setup(){
		// Do nothing here
	}
	
	public function validate(){
		return $this->validator->validate();
	}
	
	public function isValid(){
		return $this->validator->isValid();
	}
	
	/**
	 * @return Validator_ErrorHandler
	 */
	public function getErrorHandler(){
		return new Validator_ErrorHandler( 'The following errors where found while processing your request.',
		           $this->validator->getErrors() );
	}
	
	/**
	 * @return array
	 */
	public function getErrorTable(){
		return $this->validator->getErrors();
	}
	
	/**
	 * @return ProjectLibrary_Entity
	 * @see ProjectLibrary_Entity_*
	 */
	public function getEntity(){
		return $this->entity;
	}
}
?>