<?php
class GUI_ListPanel_Decorator_Sorter extends GUI_ListPanel_Decorator_Decorator  {
	
	protected $orderBy  = '';
	protected $paramName = 'o';
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		parent::__construct( $listPanel );
		$prevTemplate = $this->getTemplate();
		$this->setTemplate( new GUI_ListPanel_Decorator_SorterTemplate( $prevTemplate, $this ) );
	}
	
	public function setOrderBy( $strOrderBy ){
		$this->orderBy = $strOrderBy;
	}
	
	public function getOrderBy(){
		return $this->orderBy;
	}
	
	public function setParamName( $name ){
		$this->paramName = $name;
	}
	
	public function getParamName(){
		return $this->paramName;
	}
	
}
?>