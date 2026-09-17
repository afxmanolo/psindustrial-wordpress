<?php
abstract class ProjectLibrary_Entity_Search_Result extends ProjectLibrary_Entity_Search {
	
	/**
	 * @var int
	 */
	protected $totalPages;
	/**
	 * @var ArrayObject
	 */
	protected $results;
	
	protected $totalRecords;
	
	public function __construct( ProjectLibrary_Entity_Search $search ){
		parent::__construct();
		$this->setKeywords( $search->getKeywords() );
		$this->setOrderBy( $search->getOrderBy() );
		$this->setPage( $search->getPage() );
		$this->setResultsPerPage( $search->getResultsPerPage() );
		$this->setSearchAsPhrase( $search->getSearchAsPhrase() );
	}	

	public function getTotalRecords(){
		return $this->totalRecords;
	}
	public function setTotalRecords( $totalRecords ){
		$this->totalRecords = $totalRecords;
	}
	/**
	 * @return ArrayObject
	 */
	public function getResults(){
		return $this->results;
	}

	/**
	 * @param ArrayObject $results
	 */
	public function setResults( ArrayObject $results ){
		$this->results = $results;
	}

	/**
	 * @return int
	 */
	public function getTotalPages(){
		return $this->totalPages;
	}

	/**
	 * @param int $totalPages
	 */
	public function setTotalPages( $totalPages ){
		$this->totalPages = $totalPages;
	}

}
?>