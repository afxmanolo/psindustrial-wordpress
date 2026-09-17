<?php
class ErrorException2 extends ErrorException  {

  public function __construct( $msg, $code = 0, $severity = 0, $filename = '', $line = 0 ){
    parent::__construct( $msg, $code, $severity, $filename, $line);
  }
  
}

class Error extends ErrorException {} 
class Warning extends ErrorException {}
class Parse extends ErrorException {}
class Notice extends ErrorException {}
class CoreError extends ErrorException {}
class CoreWarning extends ErrorException {}
class CompileError extends ErrorException {}
class CompileWarning extends ErrorException {}
class UserError extends ErrorException {}
class UserWarning extends ErrorException {}
class UserNotice extends ErrorException {}
class Strict extends ErrorException {}
class RecoverableError extends ErrorException {}
?>
