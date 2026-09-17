<?php
class DB_DAO_AdvancedDAO extends DB_DAO_GenericDAO {
    
    protected $searchFields = array();
    protected $extraConditions = array();
    
    public function __construct( DBPEAR $db ){
        parent::__construct( $db );
    }
    
    public function loadAllByParameters( $searchParam = null, $explodeWords = false, $orderBy = '', $page = 1, $inc = 10, $count = false, $countAllRecords = false ){
        $page = $page - 1;
        $where = array();
        if ( !empty( $searchParam ) ) {
            $where[] = $this->createSearchString( $searchParam, $explodeWords );
        }
        $extraConditions = $this->getExtraConditions();
        $where = array_merge( $where, $extraConditions );
        $sqlWhere = ( !empty( $where ) ) ? "WHERE ( " . implode( " ) AND ( ", $where ) . " )" : '';
        $sqlOrderBy = ( !empty( $orderBy ) ) ? "ORDER BY " . $orderBy : '';
        
        $sqlLimit = ( $inc != -1 && !$count ) ? ( "LIMIT " . ( $page * $inc ) . "," . $inc )  : '';
        
        $sql =  $this->getSql( $sqlWhere, $sqlOrderBy, $sqlLimit );
        
        if ( !$count ){
            $rs = $this->db->sqlGetResult( $sql );
            return $rs;
        }
        else {
            $sql = "SELECT COUNT(*) FROM ( " . $sql . " ) AS data ";
            $counter = $this->db->sqlGetField( $sql );
            $pages = ceil( $counter / $inc );
            if( $countAllRecords ){
                return $counter;
            }else{
                return ( $pages < 1 ) ? 1 : $pages;
            }
            
        }
    }
    
    public function createSearchString( $strSearch, $explodeWords = false ){
        return implode( " OR ", $this->getSQLSearchArray( $strSearch, $explodeWords ) );
    }
    
    protected function getSql( $sqlWhere = '', $sqlOrderBy = '', $sqlLimit = '' ){
        $sql =  $this->getSqlSelect() . "
						" . $this->getSqlFrom() . "
            " . $sqlWhere . "
						" . $this->getSqlGroupBy() . "
						" . $this->getSqlHaving() . "
            " . $sqlOrderBy . "
						" . $sqlLimit;
        //echo $sql;die;
        return $sql;
    }
    
    protected function getSqlSelect() {
        $str_fields = '';
        if(is_array($this->fields) && count($this->fields) > 0){
            foreach ($this->fields as $field)$str_fields .= "{$field}, ";
            $str_fields = empty($str_fields) ? '' : ", ".substr($str_fields, 0, -2);
        }
        
        return "SELECT `{$this->table}`.*{$str_fields}";
    }
    
    protected function getSqlFrom(){
        $joins = '';
        if(!is_null($this->joins) && count($this->joins)>0){
            foreach ($this->joins as $join){
                $joins .= "{$join['type']} JOIN `{$join['table']}` ON {$join['on']} ";
            }
        }
        return "FROM " . $this->table . " $joins";
    }
    
    protected function getSqlGroupBy(){
        return '';
    }
    
    protected function getSqlHaving(){
        return '';
    }
    
    private function getSQLSearchArray( $param, $explodeWords = false ){
        if ( $explodeWords ){
            $param = explode( " ", $param );
        }
        else {
            $param = array( $param );
        }
        
        $fields = $this->getSearchFields();
        if ( empty( $fields ) ){
            throw new Exception( "Search fields not defined for the table: " . $this->table );
        }
        
        $sqlRestriction = array();
        foreach( $param as $searchParam ){
            foreach( $fields as $field ){
                $sqlRestriction[] = $field . " LIKE '%" . $searchParam . "%'";
            }
        }
        
        return $sqlRestriction;
    }
    
    public function setSearchFields( $arrFields ){
        $this->searchFields = $arrFields;
    }
    
    public function getSearchFields(){
        return $this->searchFields;
    }
    
    public function getExtraConditions(){
        return $this->extraConditions;
    }
    
    public function addExtraCondition( $sql ){
        $this->extraConditions[] = $sql;
    }
}
?>