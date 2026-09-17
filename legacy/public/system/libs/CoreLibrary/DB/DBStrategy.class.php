<?php
interface DB_DBStrategy {
  
  public function connect( $db_host, $db_user, $db_pass, $db_name );

  public function query( $sql );
  
  public function getNumRows( $rs );

  public function getNumFields( $rs );
  
  public function getErrorNumber();
  
  public function getErrorDescription();

  public function getFieldType( $rs, $intField );

  public function getFieldName( $rs, $intField );
  
  public function getResult( $rs, $intRow, $field );
  
  public function getRowAsArray( $rs );
  
  public function getLastInsertedId();

  public function getAffectedRows();
}
?>