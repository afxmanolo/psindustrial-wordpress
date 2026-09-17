<?php
class GUI_ListPanel_Decorator_RowActions extends GUI_ListPanel_Decorator_Decorator {
	
	protected $actions = array();
	protected $headerLabel;
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		parent::__construct( $listPanel );
		$prevTemplate = $this->getTemplate();
		$this->setTemplate( new GUI_ListPanel_Decorator_RowActionsTemplate( $prevTemplate, $this ) );
	}
	
	public function setActions( $actions ){
		$this->actions = $actions;
	}
	
	public function getActions(){
		return $this->actions;
	}
	
	public function addAction( GUI_ListPanel_Action $action ){
		$this->actions[] = $action;
	}
	
	public function getTotalColumnsCount(){
		return parent::getTotalColumnsCount() + 1;
	}
	
	public function setHeaderLabel( $label ){
		$this->headerLabel = $label;
	}
	
	public function getHeaderLabel(){
		return $this->headerLabel;
	}
}
?>