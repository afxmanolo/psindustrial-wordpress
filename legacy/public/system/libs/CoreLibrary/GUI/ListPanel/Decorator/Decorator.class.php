<?php
abstract class GUI_ListPanel_Decorator_Decorator extends GUI_ListPanel_ListPanel {
	
	public $listPanel;
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		$this->listPanel = $listPanel;
	}
	
	public function setData( $data ){
		$this->listPanel->setData( $data );
	}

	public function getData(){
		return $this->listPanel->getData( $data );
	}
	
	public function setCallBacks( $callBacks ){
		$this->listPanel->setCallBacks( $callBacks );
	}

	public function getCallBacks(){
		return $this->listPanel->getCallBacks();
	}

	public function addCallBack( $columnName, $functionName ){
		$this->listPanel->addCallBack( $columnName, $functionName );
	}
	
	public function getCallBack( $columnName ){
		return $this->listPanel->getCallBack( $columnName );
	}
	
	public function setColumnNamesOverride( $columnNames ){
		$this->listPanel->setColumnNamesOverride( $columnNames );
	}

	public function getColumnNamesOverride(){
		return $this->listPanel->getColumnNamesOverride();
	}
	
	public function addColumnNameOverride( $column, $name ){
		$this->listPanel->addColumnNameOverride( $column, $name );
	}
	
	public function setHiddenColumns( $columns ){
		$this->listPanel->setHiddenColumns( $columns );
	}
	
	public function getHiddenColumns(){
		return $this->listPanel->getHiddenColumns();
	}
	
	public function addHiddenColumn( $columnName ){
		$this->listPanel->addHiddenColumn( $columnName );
	}
	
	public function isColumnVisible( $columnName ){
		return $this->listPanel->isColumnVisible( $columnName );
	}
	
	public function setDefaultColumns( $columns ){
		$this->listPanel->setDefaultColumns( $columns );
	}
	
	public function getDefaultColumns(){
		return $this->listPanel->getDefaultColumns();
	}
	
	public function setColumnAlignment( $columnAlignments ){
		$this->listPanel->setColumnAlignment( $columnAlignments );
	}
	
	public function getColumnAligment(){
		return $this->listPanel->getColumnAligment();
	}
	
	public function addColumnAlignment( $columnName, $alignment ){
		$this->listPanel->addColumnAlignment( $columnName, $alignment );
	}
	
	public function getColumnAlignmentByName( $columnName ){
		return $this->listPanel->getColumnAlignmentByName( $columnName );
	}
	
	public function addDefaultColumn( $columnName ){
		$this->listPanel->addDefaultColumn( $columnName );
	}

	public function getName(){
		return $this->listPanel->getName();
	}
	
	public function getTableWidth(){
		return $this->listPanel->getTableWidth();
	}
	
	public function setTableWidth( $width ){
		$this->listPanel->setTableWidth( $width );
	}

	public function setTemplate( GUI_ListPanel_Template $template ){
		$this->listPanel->setTemplate( $template );
	}

	public function getTemplate(){
		return $this->listPanel->getTemplate();
	}
	
	public function getVisibleColumns(){
		return $this->listPanel->getVisibleColumns();
	}
	
	public function getTotalColumnsCount(){
		return $this->listPanel->getTotalColumnsCount();
	}
	
	public function display($utf8=null){
	    $this->listPanel->display($utf8);
	}
	
	public function setHiddenFields( $arrFields ){
		$this->listPanel->setHiddenFields( $arrFields );
	}
	
	public function addHiddenField( $name, $value ){
		$this->listPanel->addHiddenField( $name, $value );
	}
	
	public function getHiddenFields(){
		return $this->listPanel->getHiddenFields();
	}

	public function __call( $name, $arguments ){
		call_user_method_array( $name, $this->listPanel, $arguments );
	}
}
?>