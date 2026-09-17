<?php
class Validator_FileTester extends Validator_Tester {

	protected $errors = array (
			UPLOAD_ERR_CANT_WRITE => 'The server couldn\'t save the uploaded file.',
			UPLOAD_ERR_EXTENSION  => 'File extension is blocked by the server.',
			UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the maximum size specified in the HTML form.',
			UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the maximum size for uploaded files',
			UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially transfered.',
			UPLOAD_ERR_NO_TMP_DIR => 'The server isn\'t properly configured to accept file uploads',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
		);
		
	public function __construct( $value, $isRequired = true ){
		parent::__construct( $value, $isRequired ); 
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
			// First we need to ensure the transfer was successfull
			$file = &$_FILES[ $this->value ];		// We use a reference to avoid having copies.
			if ( $file['error'] != UPLOAD_ERR_OK  ){
				// The file was not a successful upload
				throw new Validator_TestException( $this->errors[ $file['error'] ], $file['error'] );
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
	}
	
	protected function isValueEmpty(){
		if ( !isset( $_FILES[ $this->value ] ) || $_FILES[ $this->value ]['error'] == UPLOAD_ERR_NO_FILE ){
			// Either the File input was not present, or the user didn't upload any files
			return true;
		}
		else {
			return false;
		}
	}
	
}
?>