<?php
class Validator_ErrorHandler extends Objeto {
	
	protected $description;
	protected $errorTable = array();
	
	public function __construct( $strDescription = '', $errors = array() ){
		$this->description = $strDescription;
		$this->setErrors( $errors );
	}
	
	public function addError( $field, $description ){
		$this->errorTable[ $field ] = $description;
	}
	
	public function setErrors( $errors ){
		$this->errorTable = $errors;
	}
	
	public function getErrorTable(){
		return $this->errorTable;
	}
	
	public function getError( $field ){
		if ( isset( $this->errorTable[ $field ] ) ){
			return $this->errorTable[ $field ];
		}
		else {
			return null;
		}
	}
	
	public function getErrorCount(){
		return count( $this->errorTable );
	}

	public function __toString(){
		return $this->description;
	}
	
	public function getDescription(){
		return $this->description;
	}
	
	public function getHtmlError( $field, $output = true ){
		if ( $this->getError( $field ) != null ){
  		$html = '<dd class="error"><b>&times;</b> ' . $this->getError( $field ) . '</dd>';
  		if ( $output ){
  			echo $html;
  		}
  		else {
  			return $html;
  		}
		}
	}

	public function setDescription( $msg ){
		$this->description = $msg;
	}
}
?>