<?php
class WrongDataTypeException extends Exception {

  public function __construct( $msg, $errorNumber = 0 ){
    parent::__construct( $msg, $errorNumber );
  }
  
}

?>
