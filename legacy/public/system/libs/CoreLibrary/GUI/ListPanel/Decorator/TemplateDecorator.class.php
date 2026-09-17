<?php
class GUI_ListPanel_Decorator_TemplateDecorator extends GUI_ListPanel_Template {
	
	protected $template;
	
	public function __construct( GUI_ListPanel_Template $template ){
		$this->template = $template;
	}
	
	public function display($utf8encode = false){
	    $this->template->display($utf8encode);
	}
	
	public function getFormHtml(){
		return $this->template->getFormHtml();
	}
	
	public function getTable(){
		return $this->template->getTable();
	}
	
	public function getHiddenFieldsHtml(){
		return $this->template->getHiddenFieldsHtml();
	}
	
	public function getHeadersRows(){
		return $this->template->getHeadersRows();
	}
	
	public function getHeaderCells(){
		return $this->template->getHeaderCells();
	}
	
	public function getHeaderCellContent( $headerName ){
		return $this->template->getHeaderCellContent( $headerName );
	}
	
	public function getDataRows(){
		return $this->template->getDataRows();
	}
	
	public function getDataCells( $record ){
		return $this->template->getDataCells( $record );
	}
	
	public function getDataCell( $column, $value ){
		return $this->template->getDataCell( $column, $value );
	}
	
	public function getDataCellContent( $column, $value ){
		return $this->template->getDataCellContent( $column, $value );
	}
	
	public function getFormName(){
		return $this->template->getFormName();
	}

	public function getJavaScript(){
		$this->template->getJavaScript();
	}
	
	public function getOriginalTemplate(){
		return $this->template;
	}
}
?>