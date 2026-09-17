<?php
class DB_DAO_AdvancedDAO extends DB_DAO_GenericDAO {
	
	protected $searchFields = array();
	protected $extraConditions = array();
	
	public function __construct( DB $db ){
		parent::__construct( $db );
	}
	
	public function loadAllByParameters( $searchParam = null, $explodeWords = false, $orderBy = '', $page = 1, $inc = 10, $count = false ){
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
		//echo $sql;
		/*echo "<pre>";
		var_dump($sql);
		echo "</pre>";
		die();*/
		if ( !$count ){
			$rs = $this->db->sqlGetResult( $sql );
			return $rs;
		}
		else {
			$sql = "SELECT COUNT(*) FROM ( " . $sql . " ) AS data ";
			$counter = $this->db->sqlGetField( $sql );
			$pages = ceil( $counter / $inc );
			return ( $pages < 1 ) ? 1 : $pages;
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
		//echo $sql;
		return $sql; 
	}
	
	protected function getSqlSelect() {
		return "SELECT `".$this->table.'`.*';
	}

	protected function getSqlFrom(){
		$joins = '';
		if(count($this->joins)>0){
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