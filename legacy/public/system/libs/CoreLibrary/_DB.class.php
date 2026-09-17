<?php
/**
 * This is a bridge class to use PEAR::MDB2 
 *
 */
class DB extends Objeto {
	
	/**
	 * MDB2 instance controlled by the MDB2_Driver_Common
	 *
	 * @var MDB2_Driver_Common
	 */
	public $db;
	
	/**
	 * This construct a new DB Object using the DSN specified to open a new connection 
	 * using MDB2::singleton function
	 *
	 * @param string $dsn
	 * @example new DB( "mysql://root:pass@localhost:port/db_name?new_link=true
	 * @see MDB2::singleton();
	 * 
	 */
	public function __construct( $dsn ){		
		$this->db = MDB2::singleton( $dsn );
		$this->db->setFetchMode( MDB2_FETCHMODE_ASSOC );
	}
	
	/**
	 * Execute the specified query, fetch all the rows of the result set into
   * a two dimensional array and then frees the result set.
   * 
   * @example $rs = sqlGetResult( "SELECT id, name FROM user", array( 'integer', 'string' ) );
   *          $rs = array( 0 => array( 'id'   => 1, 
   *                                   'name' => 'Alejandro' ),
   *                       1 => array( 'id'   => 2,
   *                                   'name' => 'Fernando'  ) )
   * </code>  
	 *
	 * @param string $sql    - The SQL to be executed
	 * @param array $types   - The column data types expressed as an array of strings
	 * @param int $fetchmode - The fetch mode strategy used by MDB2 (MDB2_FETCH_MODE_*)
	 * @return bidimensional array
	 */
	public function sqlGetResult( $sql, $types = null, $fetchmode = MDB2_FETCHMODE_ASSOC ){
		$rs = $this->db->queryAll( $sql, $types, $fetchmode );
		return $this->returnResult( $rs );
	}
	
	/**
	 * Execute the specified query, fetch the values from the first row of the result 
	 * set into an array and then frees the result set.
	 * 
	 * @example $rs = sqlGetResult( "SELECT id, name FROM user WHERE id = 1", array( 'integer', 'string' ) );
	 *          $rs = array( 'id' => 1, 'name' => 'Alejandro' )
	 *
	 * @param string $sql    - The SQL to be executed
	 * @param array $types   - The column data types expressed as an array of strings
	 * @param int $fetchmode - The fetch mode strategy used by MDB2 (MDB2_FETCH_MODE_*)
	 * @return associative array
	 */
	public function sqlGetRecord( $sql, $types = null, $fetchmode = MDB2_FETCHMODE_ASSOC ){
		return $this->returnResult( $this->db->queryRow( $sql, $types, $fetchmode ) );
	}
	
	/**
	 * Execute the specified query, fetch the value from the first column of the 
	 * first row of the result set and then frees the result set.
	 *
	 * @param string $sql  - The SQL query to be executed
	 * @param string $type - The data type of the field to be returned
	 * @return mixed       - The data type defined by $type or string by default
	 */
	public function sqlGetField( $sql, $type = null ){
		return $this->returnResult( $this->db->queryOne( $sql, $type ) );
	} 
	
	/**
	 * Executes the specified query, fetch the values from the first 2 columns of the result.
	 * It the query retrieves 2 columns, the first one is used as the key of the array
	 * and the second column is the value. If the query retrieves more than 2 columns, an 
	 * exception will be thrown
	 * 
	 * WARNING: If the query retrieves 2 columns and the first column isn't unique,
	 * the values will be overwritten and the results might not be what's expected. 
	 *
	 * @param sql $sql       - The SQL query to be executed
	 * @param array $types   - The column data types expressed as an array of strings
	 * @param int $fetchmode - The fetch mode strategy used by MDB2 (MDB2_FETCH_MODE_*)
	 * @return [associative] array
	 */
	public function sqlGetArray( $sql, $types = null ){
		
		$rs = $this->db->queryAll( $sql, $types, MDB2_FETCHMODE_ORDERED );
		$rs = $this->returnResult( $rs );
		
		$array = array();
		foreach( $rs as $row ){
			$fields = count( $row );
			switch ( $fields ) {
				case 1:
					$array[] = $row[0];
					break;
				case 2:
					$array[ $row[0] ] = $row[1];
					break;
				default:
					throw new Exception( "The function sqlGetArray() can only handle a " .
					                     "maximum of 2 field columns \n" . $sql );
					break;
			}
		}
		return $array;
		
	}
	
	/**
	 * Execute a manipulation query to the database and return the number of affected rows,
	 * or the ID of the last insert id.
	 *
	 * @param string $sql
	 * @return int
	 */
	public function sqlExecute( $sql ){
		
		$affectedRows = $this->returnResult( $this->db->exec( $sql ) );
		// TODO ARL: We should be using $this->db->lastInsertId() but for some reason it doesn't work!
		$last_id = (int) $this->db->queryOne( "SELECT LAST_INSERT_ID()" );
		if( $affectedRows > 1 ) {
			return $affectedRows ;
		}
		else {
			return $last_id;
		}
	}
	
	/**
	 * This function only intercepts PEAR Errors and throws them as exceptions
	 *
	 * @param mixed $rs - The MDB2 resulset to verify
	 * @return mixed
	 */
	protected function returnResult( $rs ){
		if ( PEAR::isError( $rs ) ){
			$GLOBALS['exception'] = $rs;
			throw new SQLException( $rs->userinfo );
		}
		else {
			return $rs;
		}
	}
	
	///// Transaction functions /////
	
	/**
	 * Begins a transaction in the database
	 *
	 * @return true if successfull, exception thrown otherwise
	 */
	public function beginTransaction(){
		return $this->returnResult( $this->db->beginTransaction() );
	}
	
	/**
	 * Commits the current transaction
	 *
	 * @return true if successfull, throws exception otherwise
	 */
	public function commit(){
		if ( $this->db->inTransaction() ){
			// Call the returnResult method to catch any possible exceptions
			return $this->returnResult( $this->db->commit() );
		}
		else {
			throw new Exception( "There was no transaction in progress" );
		}
	}
	
	/**
	 * Rollbacks the current transaction
	 *
	 * @return true if successfull, throws exception otherwise.
	 */
	public function rollback(){
		if ( $this->db->inTransaction() ){
			// Call the returnResult method to catch any possible exceptions
			return $this->returnResult( $this->db->rollback() );
		}
		else {
			throw new Exception( "There was no transaction in progress" );
		}
	}

	///// Utility Functions /////
	
	public function castDataToType( $value, $field_type, $parseDate = false ){
		switch( $field_type ){
			case 'int':
			case 'bigint':
			case 'tinyint':
				$value = (int) $value;
				break;
			case 'real':
			case 'double':
			case 'float':
				$value = (float) $value;
				break;
			case 'datetime':
			  if ( $parseDate ){
				  $value = strtotime( $value );
			  }
			  break;
			case 'string':
			default:
				// Do nothing	
		}
		return $value;
	}

	/**
	 * Escapes an string according to the DB driver
	 *
	 * @param string $string - The string to escape
	 * @return string
	 */
	public function escapeString( $string ){
		return $this->db->escape( $string );
	}
}
?>