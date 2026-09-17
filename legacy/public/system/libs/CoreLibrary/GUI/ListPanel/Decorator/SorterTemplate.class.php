<?php
class GUI_ListPanel_Decorator_SorterTemplate extends GUI_ListPanel_Decorator_TemplateDecorator {
	
	private $sortedListPanel;
	
	public function __construct( GUI_ListPanel_Template $template, GUI_ListPanel_Decorator_Sorter $sortedListPanel ){
		parent::__construct( $template );
		$this->sortedListPanel = $sortedListPanel;
	}
	
/*	public function getHeaderCellContent( $headerName ){
		$title1 = $this->template->trans('sort_ascending').' (A-Z)';
		$title2 = $this->template->trans('sort_descending').' (Z-A)';
		if(CHARSET_PROJECT != 'UTF-8') {
			$title1 = utf8_decode($title1);
			$title2 = utf8_decode($title2);
		}

		$html = '';
		
		$html .= '<div class="contain" >';
		
		$html .= '<div class="listPanel_SortingImages">';
		$html .=   '<img src="img/bullet_arrow_up.png" alt="&#916;"  title="'.$title1.'" border="0" ' . 
		                'onclick="setListPanelParam(\'' . $this->getFormName() .'\',' .
		                                           '\'' . $this->sortedListPanel->getParamName()  .'\',' . 
		                                           '\'' . $headerName .'\');" ' .
		                'style="cursor:pointer;margin:3px" />';
		$html .= '</div>';
		
		$label = parent::getHeaderCellContent( $headerName );
		
		if ( strpos( $this->sortedListPanel->getOrderBy(), $headerName ) === 0 ){
			$label = '<u>' . $label . '</u>';
		}

		$html .= '<div class="listPanel_ColumnName">' . $label . '</div>';
		
		$html .= '<div class="listPanel_SortingImages">';
		$html .=   '<img src="img/bullet_arrow_down.png"   alt="&#8711;" title="'.$title2.'" border="0" ' .
		                'onclick="setListPanelParam(\'' . $this->getFormName() .'\','.
                                               '\'' . $this->sortedListPanel->getParamName()   .'\','.
		                                           '\'' . $headerName .' DESC\');" '.
		                'style="cursor:pointer;margin:3px" />';
		$html .= '</div>';
		
		$html .= '</div>';
		return $html;
	}*/
	
	public function getHiddenFieldsHtml(){
		$html = parent::getHiddenFieldsHtml();
		$html.= '<input type="hidden" name="' . $this->sortedListPanel->getParamName() .'" value="' . $this->sortedListPanel->getOrderBy() . '" />';
		return $html;
	}

}
?>