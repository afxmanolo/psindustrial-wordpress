<?php
class GUI_ListPanel_Decorator_SearcherTemplate extends GUI_ListPanel_Decorator_TemplateDecorator {
	
	private $searchListPanel;
	
	public function __construct( GUI_ListPanel_Template $template, GUI_ListPanel_Decorator_Searcher $searchListPanel ){
		parent::__construct( $template );
		$this->searchListPanel = $searchListPanel;
	}
	
/*	public function getTable( $utf8decode = false ){
		
		if ( $utf8decode){
			$query = utf8_decode( $this->searchListPanel->getSearchString() );
		}
		else {
			$query = $this->searchListPanel->getSearchString();
		}
		
		$paramName  = $this->searchListPanel->getParamName();
		
		$html = '<div align="center">';
		$html.=   '<div class="listPanel_SearchBox" style="width: ' . $this->searchListPanel->getTableWidth() . '; clear: left;" align="left">';
		$html.=   '<label for="' . $paramName . '">&nbsp;&nbsp;&nbsp;' . $this->searchListPanel->getFieldLabel() . '</label> ';
		$html.=   '<input type="text" size="30" '.
		                 'name="' . $paramName . '" id="' . $paramName . '" '.
		                 'value="' . $query . '" '.
                     'class="frmInput" /> ';
		$html.=   '<input type="submit" value="' . $this->searchListPanel->getButtonLabel() .'" class="frmButton" /> ';
		
		if ( $this->searchListPanel->isClearButtonVisible() == true ){
			$html.= '<input type="button" class="listPanel_SearchButton" '.
			               'value="' . $this->searchListPanel->getClearButtonLabel() . '" ' . 
		                 'onclick="setListPanelParam(\'' . $this->getFormName() .'\','. 
			                                           '\'' . $paramName .'\', \'\');" /> ';
		}
		$html.=   '</div>';
		$html.= '</div>';
		$html.= parent::getTable();
		return $html;
	}*/
	
}
?>