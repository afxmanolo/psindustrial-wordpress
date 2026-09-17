<?php
class ProjectLibrary_Entity_Search extends Objeto {
	
	/**
	 * @var string
	 */
	protected $keywords;
	/**
	 * @var boolean
	 */
	protected $searchAsPhrase = false;
	/**
	 * @var string
	 */
	protected $orderBy;
	/**
	 * @var int
	 */
	protected $page = 1;
	/**
	 * @var int
	 */
	protected $resultsPerPage = 15;
	
	
	public function __construct( $keywords = null, $orderBy = null, $page = 1, $resultsPerPage = 15 ){
		parent::__construct();
		$this->setKeywords( $keywords);
		$this->setOrderBy( $orderBy );
		$this->setPage( $page );
		$this->setResultsPerPage( $resultsPerPage );
	}

	/**
	 * @return string
	 */
	public function getKeywords(){
		return $this->keywords;
	}

	/**
	 * @param string $keywords
	 */
	public function setKeywords( $keywords ){
		$this->keywords = $keywords;
	}

	/**
	 * @return string
	 */
	public function getOrderBy(){
		return $this->orderBy;
	}

	/**
	 * @param string $orderBy
	 */
	public function setOrderBy( $orderBy ){
		$this->orderBy = $orderBy;
	}

	/**
	 * @return int
	 */
	public function getPage(){
		return $this->page;
	}

	/**
	 * @param int $page
	 */
	public function setPage( $page ){
		$this->page = $page;
	}

	/**
	 * @return int
	 */
	public function getResultsPerPage(){
		return $this->resultsPerPage;
	}

	/**
	 * @param int $resultsPerPage
	 */
	public function setResultsPerPage( $resultsPerPage ){
		$this->resultsPerPage = $resultsPerPage;
	}

	/**
	 * @return boolean
	 */
	public function getSearchAsPhrase(){
		return $this->searchAsPhrase;
	}

	/**
	 * @param boolean $searchAsPhrase
	 */
	public function setSearchAsPhrase( $searchAsPhrase ){
		$this->searchAsPhrase = $searchAsPhrase;
	}

	
}
?>