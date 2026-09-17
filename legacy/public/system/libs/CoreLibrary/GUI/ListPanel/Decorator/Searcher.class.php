<?php
class GUI_ListPanel_Decorator_Searcher extends GUI_ListPanel_Decorator_Decorator {
	
	protected $searchString = '';
	protected $paramName = 'q';
	protected $buttonLabel;
	protected $fieldLabel;
	protected $clearButtonLabel;
	protected $clearButtonVisible = true;
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		parent::__construct( $listPanel );
		$prevTemplate = $this->getTemplate();
		$this->setTemplate( new GUI_ListPanel_Decorator_SearcherTemplate( $prevTemplate, $this ) );
	}
	
	public function setSearchString( $searchString ){
		$this->searchString = $searchString;
	}
	
	public function getSearchString(){
		return $this->searchString;
	}
	
	public function setParamName( $name ){
		$this->paramName = $name;
	}
	
	public function getParamName(){
		return $this->paramName;
	}
	
	public function setButtonLabel( $label ){
		$this->buttonLabel = $label;
	}
	
	public function getButtonLabel(){
		return $this->buttonLabel;
	}
	
	public function setFieldLabel( $label ){
		$this->fieldLabel = $label;
	}
	
	public function getFieldLabel(){
		return $this->fieldLabel;
	}
	
	public function setClearButtonLabel( $label ){
		$this->clearButtonLabel = $label;
	}
	
	public function getClearButtonLabel(){
		return $this->clearButtonLabel;
	}

	public function setClearButtonVisibility( $boolean ){
		$this->clearButtonVisible = $boolean;
	}
	
	public function isClearButtonVisible(){
		return $this->clearButtonVisible;
	}
}
?>