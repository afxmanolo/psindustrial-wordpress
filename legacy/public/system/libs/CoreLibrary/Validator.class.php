<?php

class Validator extends Objeto {
	
	/**
	 * An array of Validator_Tester
	 *
	 * @var Validator_Tester array
	 */
	protected $testers = array();
	/**
	 * An array of errors found during testing
	 *
	 * @var array
	 */
	protected $errors = array();
	/**
	 * @var array
	 */
	protected $fileTesters = array();
	
	/**
	 * Creates a new instance of Validator
	 *
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * Creates a new Tester for the data.
	 *
	 * @param string $name - Unique name for this data
	 * @param mixed $value - Value of the data
	 * @param boolean $isRequired - Is the value required (  != null/'' ) 
	 * @throws Validator_Exception - When attempting to create a new Teter for an already existing data
	 */
	public function addData( $name, $value, $isRequired = true ){
		$tester = new Validator_Tester( $value, $isRequired );
		$this->addTester( $name, $tester );
	}
	
	/**
	 * @param string $name - Unique name for this tester
	 * @param Validator_Tester $tester
	 * @throws Validator_Exception
	 */
	public function addTester( $name, Validator_Tester $tester ){
	if ( !isset( $this->testers[ $name ] ) ){
			// We don't have a tester for that data. Create a new one
			$this->testers[ $name ] = $tester;
		}
		else {
			// Oops... we do! This is a programmer problem
			throw new Validator_Exception( "This validator already has a data named '$name'." );
		}
	}
	
	/**
	 * @param string $name
	 * @param Validator_FileTester $tester
	 * @throws Validator_Exception
	 */
	public function addFileTester( $name, Validator_FileTester $tester ){
		if ( !isset( $this->fileTesters[ $name ] ) ){
			// We don't have a tester for that data. Create a new one
			$this->fileTesters[ $name ] = $tester;
		}
		else {
			// Oops... we do! This is a programmer problem
			throw new Validator_Exception( "This validator already has a file tester named '$name'." );
		}
	}
	
	/**
	 * Creates a new FileTester for a file upload
	 *
	 * @param string $name          - The name to identify this file testing session
	 * @param string $structureName - $_FILES[ $structureName ] 
	 * @param boolean $isRequired   - Is the upload required
	 * @throws Validator_Exception
	 */
	public function addFileUpload( $name, $structureName, $isRequired = true ){
		if ( !isset( $this->fileTesters[ $name ] ) ){
			// We don't have a tester for this file upload. Create a new one.
			$this->fileTesters[ $name ] = new Validator_FileTester( $structureName, $isRequired );
		}
		else {
			throw new Validator_Exception( "This validator already has a file upload named '$name'." );
		}
	}
	
	/**
	 * Adds a new Test to the data defined by $name
	 *
	 * @param string $name
	 * @param Validator_Test $test
	 * @param string $errorMessage - Error message to return when the test fails
	 */
	public function addTestToData( $name, Validator_Test $test, $errorMessage ){
		$helper = $this->getTesterForData( $name );
		$helper->addTest( $test, $errorMessage );
	}
	
	/**
	 * Adds a new Test to the File Upload defined by $name
	 *
	 * @param string $name          - The name to identify the file upload
	 * @param Validator_Test $test  - The test itself
	 * @param string $errorMessage  - Error message to return when the test fails
	 */
	public function addTestToFileUpload( $name, Validator_Test $test, $errorMessage ){
		$tester = $this->getTesterForFileUpload( $name );
		$tester->addTest( $test, $errorMessage );
	}
	
	/**
	 * Gets the object that handles all the data tests.
	 *
	 * @param string $name
	 * @return Validator_Tester
	 */
	protected function getTesterForData( $name ){
		if ( isset( $this->testers[ $name ] ) ){
			return $this->testers[ $name ];
		}
		else {
			throw new Validator_Exception( "The data '$name' isn't handled by this validator." );
		}
	}
	
	/**
	 * Gets the object that handles all the file upload tests.
	 *
	 * @param string $name
	 * @return Validator_FileTester
	 */
	protected function getTesterForFileUpload( $name ){
		if ( isset( $this->fileTesters[ $name ] ) ){
			return $this->fileTesters[ $name ];
		}
		else {
			throw new Validator_Exception( "The file upload '$name' isn't handled by this validator." );
		}
	}
	
	/**
	 * Retrieves the list of Tests the data is tested against.
	 *
	 * @param string $name - The name of the data
	 * @return Validator_Test array
	 */
	public function getTestsForData( $name ){
		$tester = $this->getTesterForData( $name );
		return $tester->getTests();
	}

	/**
	 * Validate all the data with their pertinent tests.
	 *
	 * @return boolean - True if all tests passed, false otherwise
	 */
	public function validate(){
		$this->executeTesters( $this->testers );
		$this->executeTesters( $this->fileTesters );
		return $this->isValid();
	}
	
	/**
	 * Executes each of the testers
	 *
	 * @param array $testerArray - Array in the format of { $name => $tester }*
	 */
	protected function executeTesters( $testerArray ){
		foreach( $testerArray as $name => $tester ){
			try {
				$tester->test();
			}
			catch( Validator_TestException $e ){
				$this->errors[ $name ] = $e->getMessage();
			}
		}
	}
	
	/**
	 * Returns if the validator's tests have passed
	 *
	 * @return boolean
	 */
	public function isValid(){
		return ( count( $this->errors ) > 0 ) ? false : true;
	}
	
	/**
	 * Returns the array of errors
	 *
	 * @return array - Key is the data name, value is error message
	 */
	public function getErrors(){
		return $this->errors;
	}
}
?>