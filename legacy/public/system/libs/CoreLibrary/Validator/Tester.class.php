<?php
class Validator_Tester extends Objeto {
	
	/**
	 * @var mixed
	 */
	protected $value = null;
	/**
	 * @var array
	 */
	protected $tests = array();
	/**
	 * @var boolean
	 */
	protected $required;
	
	/**
	 * Creates a new Validator_Tester instance
	 *
	 * @param mixed $value - The value to be tested
	 * @param boolean $isRequired - Is the value required for the tests to succeed
	 */
	public function __construct( $value, $isRequired = true ){
		$this->value = $value;
		$this->required = $isRequired;
	}
	
	/**
	 * Adds a new test to the value.
	 *
	 * @param Validator_Test $test
	 * @param string $errorMessage
	 */
	public function addTest( Validator_Test $test, $errorMessage ){
		$this->tests[] = array( 'test' => $test, 'error' => $errorMessage );
	}
	
	/**
	 * This method tests the value and throws an exception when failure is found
	 * 
	 * @return void
	 * @throws Validator_TestException  
	 */
	public function test(){
		
		if (  $this->isValueEmpty() && !$this->isRequired() ){
			// The value is empty and it's not required.
			// No further validation is required
		}
		else {
			// The value is NOT empty, thus we need to test it
			foreach( $this->tests as $testData ){
				if ( !$testData['test']->isValid( $this->value ) ){
					throw new Validator_TestException( $testData['error'] );
				}
			}
		}
	}
	
	/**
	 * Returns an array of Tests
	 *
	 * @return array of Validator_Test's 
	 */
	public function getTests(){
		$tests = array();
		foreach( $this->tests as $testData ){
			$tests[] = $testData['test'];
		}
		return $tests;
	}
	
	protected function isValueEmpty(){
		if ( $this->value == '' || $this->value == null ){
			return true;
		}
		else {
			return false;
		}
	}
	
	protected function isRequired(){
		return $this->required;
	}
	
}
?>