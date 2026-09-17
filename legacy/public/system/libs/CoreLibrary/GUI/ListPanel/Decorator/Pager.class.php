<?php
class GUI_ListPanel_Decorator_Pager extends GUI_ListPanel_Decorator_Decorator  {
	
	// Paging options
	protected $pageParamName  = 'p';
	protected $currentPage    = 1;
	protected $totalPages     = 1;
	protected $recordsPerPage = 50;
	protected $pagingLabel;
	
	protected $previousLabel;
	
	protected $nextLabel;
	
	protected $firstPageLabel;
	
	protected $lastPageLabel;
	
	protected $pageHorizon    = 5; 						// 0 = Display ALL Pages as links 

	protected $offsetParamName = 'k';
	protected $offset          = 50;
	protected $offsetIncrement = 50;
	protected $offsetMax       = 100;
	protected $offsetLabel;
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		parent::__construct( $listPanel );
		$prevTemplate = $this->getTemplate();
		$this->setTemplate( new GUI_ListPanel_Decorator_PagerTemplate( $prevTemplate, $this ) );
	}
	
	public function setCurrentPage( $intPage ){
		$this->currentPage = $intPage;
	}
	
	public function getCurrentPage(){
		return $this->currentPage;
	}
	
	public function setTotalPages( $intTotalPages ){
		$this->totalPages = $intTotalPages;
	}
	
	public function getTotalPages(){
		return $this->totalPages;
	}

	public function setRecordsPerPage( $intRecordsPerPage ){
		$this->recordsPerPage = $intRecordsPerPage;
	}
	
	public function getRecordsPerPage(){
		return $this->recordsPerPage;
	}
	
	public function setPageParamName( $name ){
		$this->pageParamName = $name;
	}
	
	public function getPageParamName(){
		return $this->pageParamName;
	}
	
	public function setPagingLabel( $label ){
		$this->pagingLabel = $label;
	}
	
	public function getPagingLabel(){
		return $this->pagingLabel;
	}
	
	public function setPreviousLabel( $label){
		$this->previousLabel = $label;
	}
	
	public function getPreviousLabel(){
		return $this->previousLabel;
	}
	
	public function setNextLabel( $label ){
		$this->nextLabel = $label;
	}
	
	public function getNextLabel(){
		return $this->nextLabel;
	}
	
	public function getFirstPageLabel() {
		return $this->firstPageLabel;
	}
	
	public function setFirstPageLabel( $firstPageLabel ){ 
		$this->firstPageLabel = $firstPageLabel; 
	}
	
	public function getLastPageLabel(){ 
		return $this->lastPageLabel;
	}
	
	public function setLastPageLabel( $lastPageLabel ){ 
		$this->lastPageLabel = $lastPageLabel; 
	}
	
	public function setPageHorizon( $intPages ){
		$this->pageHorizon = $intPages;
	}
	
	public function getPageHorizon(){
		return $this->pageHorizon;
	}

	public function setOffsetParamName( $name ){
		$this->offsetParamName = $name;
	}

	public function getOffsetParamName(){
		return $this->offsetParamName;
	}
	
	public function setOffset( $intOffset){
		$this->offset = $intOffset;
	}
	
	public function getOffset(){
		return $this->offset;
	}

	public function setOffsetLabel( $label ){
		$this->offsetLabel = $label;
	}
	
	public function getOffsetLabel(){
		return $this->offsetLabel;
	}

	public function setOffsetMax( $intMax ){
		$this->offsetMax = $intMax;
	}
	
	public function getOffsetMax(){
		return $this->offsetMax;
	}
	
	public function setOffsetIncrement( $intIncrement ){
		$this->offsetIncrement = $intIncrement;
	}
	
	public function getOffsetIncrement(){
		return $this->offsetIncrement;
	}
}
?>