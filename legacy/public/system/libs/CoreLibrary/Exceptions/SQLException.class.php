<?php
class SQLException extends Exception {
  
  public function __construct( $msg, $errorNumber = 0 ){
    parent::__construct( $msg, $errorNumber );
  }
  
}
?>