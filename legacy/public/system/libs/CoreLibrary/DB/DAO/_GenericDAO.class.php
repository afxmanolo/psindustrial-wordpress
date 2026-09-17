<?php
class DB_DAO_GenericDAO extends Objeto {
	
	protected $table;
	protected $id_field;
	protected $autoIncrement;
	protected $joins;
	protected $md5_fields;
	/**
	 * DB object
	 *
	 * @var DB
	 */
	protected $db;
	
	public function __construct( DB $db ){
		$this->db = $db;		
	}

	public function loadStructure( ){
		
		$sql = "DESC `" . $this->table . "`"; 
		$rs = $this->db->sqlGetResult( $sql );
		
		$record = array();
		foreach ( $rs as $field ){

			$name = $field['field'];
			$type = $field['type'];
			$type = $field['type'];
			$null = $field['null'];
			$default = $field[ 'default' ];
			
			if ( $null == 'YES' ){
				$value = null;
			}
			else {
				if ( strpos( $type, '(' ) > 0 ){
					$type = substr( $type, 0, strpos( $type, '(' ) );			// Remove the (11) from the type int(11), etc
				}
				$value = $this->db->castDataToType( $default, $type );
			}
			$record[ $name ] = $value;
		}
		
		return $record;
		
	}

	public function getIdFields(){
		$idField = $this->id_field;
		if(strpos($idField,",") > -1){
			$idField = split(",", $this->id_field);
		}
		return $idField;
	}
	
	private function getWhereIdFields( $id ){
		$idField = $this->getIdFields();
		if(!is_array($idField)){
			$where = $idField . " = '" . addslashes( $id ) . "'";
		}else{
			$where = $idField[0] . " = '" . addslashes( $id[ $idField[0] ] ) . "'";
			unset($idField[0]);
			foreach($idField as $idF){
				$where .= " AND " . $idF . " = '" . addslashes( $id[ $idF ] ) . "'";
			}
		}
		return $where;
	}
	
	public function loadById( $id, $loadStructureIfEmpty = false ){
		$sql = "SELECT 	* 
						FROM		`" . $this->table . "`
						WHERE		" . $this->id_field . " = '" . addslashes( $id ) . "'";
		$obj = $this->db->sqlGetRecord( $sql );
		
		if ( empty( $obj ) && $loadStructureIfEmpty ){			
			return $this->loadStructure();
		}
		else {
			return $obj;			
		}
	}
	
	public function loadAll( $orderBy = '' ){
		$sqlOrderBy = '';
		if ( $orderBy != '') {
			$sqlOrderBy = 'ORDER BY ' . $orderBy;
		}
		$sql = "SELECT 	* 
						FROM		" . $this->table . ' ' .
						$sqlOrderBy;
		
		return $this->db->sqlGetResult( $sql );
	}
	
	public function saveOrUpdate( &$arrEvent ){
		if ( !isset( $arrEvent[ $this->id_field ] ) || $arrEvent[ $this->id_field ] == 0 ){		
			$return = $this->saveNew( $arrEvent );
		}
		else {			
			$return = $this->update( $arrEvent );
		}
		
		return $return;
	}

	public function saveNew( &$arrEvent ){
		// The primary Key is auto-inc, therefore shouldn't be present in the SQL statement
		if ( $this->autoIncrement && isset( $arrEvent[ $this->id_field ] ) ) {
			unset( $arrEvent[ $this->id_field ] );
		}
		
		$fields = $this->prepareFields( $arrEvent );
		
		$sql = "INSERT INTO `" . $this->table . "` SET " . implode( ",\n ", $fields ) . "";
		echo $sql."<br/>";
		die();
		$id = $this->db->sqlExecute( $sql );
		$arrEvent[ $this->id_field ] = $id;

		return $id;
	}
	
	public function update( &$arrEvent ){
		$id = $arrEvent[ $this->id_field ];
		unset( $arrEvent[ $this->id_field ] );
		
		$fields = $this->prepareFields( $arrEvent );
		
		$sql =  "UPDATE `" . $this->table . "`" .
						" SET   " . join( ',', $fields ) . 
						" WHERE " . $this->id_field . " = '" . addslashes( $id ) . "'";
		$affectedRows = $this->db->sqlExecute( $sql );
		$arrEvent = $this->loadById( $id );
		return $affectedRows;
	}
	
	public function delete( $ids ){
		// Prepare everything to work as a well formed array
		if ( !is_array( $ids ) ){
			$ids = array( $ids );
		}
		elseif ( count( $ids ) == 0 ){
			$ids = array( 0 );   
		}
		
		$sql = "DELETE FROM `" . $this->table . "`" . 
					" WHERE 			" . $this->id_field . " IN ( " . implode( ", ", $ids ) . " )";
		return $this->db->sqlExecute( $sql );
		
	}
	
///////////////////
// Utility methods

	public function setTable( $table ){
		$this->table = $table;
	}
	public function getTable(){
		return $this->table;
	}
	
	public function setIdField( $id_field ){
		$this->id_field = $id_field;
	}
	public function getIdField(){
		return $this->id_field;
	}
	
	/*protected function prepareFields( $array, $utf8decode = false ){
		$fields = array();
		foreach ( $array as $key => $value ){
			if ( $value === null ){
				$fields[] = $key . " = NULL";
			}
			else {
				if ( get_magic_quotes_gpc() ){
					$value = stripslashes( $value );
				}
				$value = $this->db->escapeString( $value );
				
				if ( $utf8decode ){
					$value = utf8_decode( $value );
				}
				$fields[] = $key . " = '" . $value . "'";		// FIXME ARL
			}
		}
		return $fields;
	}*/
	protected function prepareFields( &$array, $utf8decode = false ){
		$fields = array();
		$md5_fields = explode(',', $this->md5_fields);
	
		foreach ( $array as $key => $value ){
			if ( $value === null ){
				$fields[] = "`" . $key . "` = NULL";
			}
			else {
				if ( get_magic_quotes_gpc() ){
					$value = stripslashes( $value );
				}
				$value = $this->db->escapeString( $value );
	
				if ( $utf8decode ){
					$value = utf8_decode( $value );
				}
	
				if(array_search($key, $md5_fields) !== false){
					$value = md5($value);
				}
	
				$fields[] = "`" . $key . "` = '" . $value . "'";		// FIXME ARL
			}
		}
		return $fields;
	}

	/**
	 * @return DB
	 */
	public function getDB(){
		return $this->db;
	}
	
	public function addJoin($table, $on, $join_type = 'INNER'){
		$joins = $this->joins;
		$joins[] = array('table' => $table, 'on' => $on, 'type' => $join_type);
		$this->joins = $joins;
	}
	
	public function addFields($strfields){
		$fields = $this->fields;
		$new_fileds = explode(',', $strfields);
		foreach ($new_fileds as $fiel){
			$fields[] = $fiel;
		}
		$this->fields = $fields;
	}
}
?>