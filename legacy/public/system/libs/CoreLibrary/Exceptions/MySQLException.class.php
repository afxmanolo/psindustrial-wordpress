<?php
class Exceptions_MySQLException extends Exception {
  
  public function __construct( $msg, $errorNumber ){
    parent::__construct( $msg, 0 ); //$errorNumber );
  }
}
?>