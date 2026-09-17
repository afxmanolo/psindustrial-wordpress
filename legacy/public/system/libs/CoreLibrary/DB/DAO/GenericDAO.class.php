<?php
class DB_DAO_GenericDAO extends Objeto {
    
    protected $table;
    protected $id_field;
    protected $autoIncrement;
    protected $joins;
    protected $md5_fields;
    protected $fields;
    /**
     * DB object
     *
     * @var DB
     */
    protected $db;
    
    public function __construct( DBPEAR $db ){
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
            $idField = preg_split("/,/", $this->id_field);
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
        $where = $this->getWhereIdFields($id);
        
        $sql = "SELECT 	*
						FROM		`" . $this->table . "`
						WHERE		" . $where;
        
        
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
        $idField = $this->getIdFields();
        
        if(!is_array($idField)){
            $saveNew = ( !isset( $arrEvent[ $idField ] ) || $arrEvent[ $idField ] == 0 );
        }else{
            $saveNew = true;
            $idsSearch = array();
            $result = false;
            foreach($idField as $id){
                if( !isset( $arrEvent[ $id ] ) ){
                    $result = true;
                    break;
                }
                $idsSearch[ $id ] = $arrEvent[ $id ];
            }
            if(!$result){
                $register = $this->loadById($idsSearch);
                $saveNew = empty($register);
            }
        }
        
        
        //if ( !isset( $arrEvent[ $this->id_field ] ) || $arrEvent[ $this->id_field ] == 0 ){
        if($saveNew){
            $return = $this->saveNew( $arrEvent );
        }
        else {
            $return = $this->update( $arrEvent );
        }
        return $return;
    }
    
    public function saveNew( &$arrEvent ){
        $idField = $this->getIdFields();
        if(!is_array($idField)){
            // The primary Key is auto-inc, therefore shouldn't be present in the SQL statement
            if ( $this->autoIncrement && isset( $arrEvent[ $this->id_field ] ) ) {
                unset( $arrEvent[ $this->id_field ] );
            }
        }
        
        $fields = $this->prepareFields( $arrEvent );
        
        $sql = "INSERT INTO `" . $this->table . "` SET " . implode( ",\n ", $fields ) . "";
        //$sql = str_replace(array(" = '0'", " = ''")," = NULL",$sql);
        
        //echo $sql;
        $id = $this->db->sqlExecute( $sql );
        
        $idsFields = $this->getIdFields();
        
        if($id && !is_array($idsFields))$arrEvent[ $this->id_field ] = $id;
        
        return $id;
    }
    
    public function update( &$arrEvent ){
        $idField = $this->getIdFields();
        
        if(!is_array($idField)){
            $idValues = $arrEvent[ $this->id_field ];
            unset( $arrEvent[ $this->id_field ] );
        }else{
            $idValues = array();
            foreach($idField as $idF){
                $idValues[ $idF ] = $arrEvent[ $idF ];
                unset( $arrEvent[ $idF ] );
            }
        }
        
        
        $fields = $this->prepareFields( $arrEvent );
        
        $sql =  "UPDATE `" . $this->table . "`" .
            " SET   " . join( ',', $fields ) .
            " WHERE " . $this->getWhereIdFields($idValues);
        
        
        //$sql = str_replace(array(" = '0'", " = ''")," = NULL",$sql);
        //echo $sql;die;
        $affectedRows = $this->db->sqlExecute( $sql );
        $arrEvent = $this->loadById( $idValues );
        return $affectedRows;
    }
    
    public function delete( $ids ){
        
        $idField = $this->getIdFields();
        
        $sql = "DELETE FROM `" . $this->table . "`" .
            " WHERE 			";
        if(!is_array($idField)){
            // Prepare everything to work as a well formed array
            if ( !is_array( $ids ) ){
                $ids = array( $ids );
            }
            elseif ( count( $ids ) == 0 ){
                $ids = array( 0 );
            }
            
            $sql .= $this->id_field . " IN ( " . implode( ", ", $ids ) . " )";
        }else{
            $sql .= $this->getWhereIdFields($ids);
        }
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
    
    protected function prepareFields( $array, $utf8decode = false ){
        $fields = array();
        $md5_fields = !empty($this->md5_fields)?explode(',', $this->md5_fields):array();
        
        foreach ( $array as $key => $value ){
            if ( $value === null ){
                $fields[] = $key . " = NULL";
            }
            else {
                if ( addslashes($value)){
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