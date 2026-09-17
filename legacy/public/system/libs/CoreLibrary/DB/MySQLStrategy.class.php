<?php

final class DB_MySQLStrategy extends Objeto implements DB_DBStrategy {

  public $connection = null;
  
  public function __construct(){
    parent::__construct();
  }

  public function connect( $db_host, $db_user, $db_pass, $db_name ){
    try {
      $conn = mysql_connect( $db_host, $db_user, $db_pass, true );
      if ( !mysql_select_db( $db_name, $conn ) ){
        throw new Exceptions_MySQLException( 'Database "' . $db_name . '" cannot be used.', 0 );
      }
      self::setConnection( $conn );
    }
    catch ( Exception $e ){
      throw new Exceptions_MySQLException( 'Connection could not be stablished', mysql_error() );
    }
  }

  private function setConnection( $resource ){
    $this->connection = $resource;
  }

  private function getConnection(){
    return $this->connection;
  }

  public function query( $sql ){
    if ( !$this->connection ){
      throw new MySQLException( "Connection isn't stablished", mysql_error() );
    }
    else {
      $rs = mysql_query( $sql, $this->connection );
      if ( !$rs ){
        throw new Exceptions_MySQLException( "Query isn't a valid SQL statement:\n\n" . $sql . "\n\n" . $this->getErrorDescription(), $this->getErrorNumber() );
      }
      else {
        return $rs;
      }
    }
  }
  
  public function getNumRows( $rs ){
    if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_num_rows( $rs );
  }

  public function getNumFields( $rs ){
    if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_num_fields( $rs );
  }
  
  public function getErrorNumber(){
    return mysql_errno( $this->connection );
  }
  
  public function getErrorDescription(){
    return mysql_error( $this->connection );
  }

  public function getFieldType( $rs, $intField ){
    if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_field_type( $rs, $intField );
  }

  public function getFieldName( $rs, $intField ){
  if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_field_name( $rs, $intField );
  }
  
  public function getResult( $rs, $intRow, $field ){
    if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_result( $rs, $intRow, $field );
  }
  
  public function getRowAsArray( $rs ){
    if ( !$rs ){
      throw new MySQLException( 'Result Set is invalid', 0 );
    }
    return mysql_fetch_array( $rs );
  }
  
  public function getLastInsertedId(){
    return mysql_insert_id( $this->connection );
  }

  public function getAffectedRows(){
    return mysql_affected_rows( $this->connection );
  }

	public function escapeString( $str ){
		return mysql_escape_string( $str );
	}
}
?>