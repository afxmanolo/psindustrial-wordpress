<?php
class GUI_ListPanel_Decorator_PagerTemplate extends GUI_ListPanel_Decorator_TemplateDecorator {

	public $pagerListPanel;

	public function __construct( GUI_ListPanel_Template $template, GUI_ListPanel_Decorator_Pager $pagerListPanel ){
		parent::__construct( $template );
		$this->pagerListPanel = $pagerListPanel;
	}

/*	public function getDataRows(){
		$html = parent::getDataRows();
		$html.= $this->getPagingRow();
		return $html;
	}*/

	public function getPagingRow(){
		$totalColumns = $this->pagerListPanel->getTotalColumnsCount();
		$html = '<tr class="listPanel_PagingRow">';
		$html.=   '<td colspan="' . $totalColumns . '" class="listPanel_PagingCell">';
		$html.=     $this->getPagingCellContent();
		$html.=   '</td>';
		$html.= '</tr>';
		return $html;
	}

	public function getPagingCellContent(){
		$html = '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
		$html.=   '<tr>';
		$html.=     '<td width="50%" align="left">';
		$html.=       $this->getPagingNumbering();
		$html.=     '</td>';
		$html.=     '<td width="50%" align="right">';
		$html.=       $this->getPagingOffsetSelector();
		$html.=     '</td>';
		$html.=   '</tr>';
		$html.= '</table>';
		return $html;
	}

	public function getPagingNumbering(){
		$html = $this->pagerListPanel->getPagingLabel();
		$pages = $this->pagerListPanel->getTotalPages();
		if ( $pages <= 0 ){
			$pages = 1;
		}
		$curPage = $this->pagerListPanel->getCurrentPage();
		
		$html .= ' ';
		$html .= $this->getPreviousButtons( $curPage );

		$stringPages = array();
		$hide = false;
		$hidingFactor = $this->pagerListPanel->getPageHorizon();
		
		$i = ( $curPage - $hidingFactor );
		$j = ( $curPage + $hidingFactor );
		
		if ( $i < 1 ){
			$j = $j+abs($i);           // Shift right by the number of negative numbers
			$i = 1;
		}
		elseif ( $j > $pages ){
			$i = $i - ($j - $pages);   // Shift left by the number of numbers over the limit
			$j = $pages;
		}
		// Limit the range just in case the window (horizon) of pages is too broad for the resultset
		if ( $i < 1 ){
			$i = 1;
		}
		if ( $j > $pages ){
			$j = $pages;
		}
		
		
		if ( $i < 1 || $hidingFactor == 0 ) {
			$i = 1;
		}
		if ( $j > $pages || $hidingFactor == 0 ){
			$j = $pages;
		}

		$pagesInHorizon = range( $i, $j );
		$pagesRange = range( 1, $pages );
		
		if( $i > 1 ) {
			$stringPages[] = '...';
		}
		foreach( range( 1, $pages ) as $page ){
			if ( $curPage == $page ){
				$stringPages[] = '<b><u>' . $page . '</u></b>';
			}
			elseif( in_array( $page, $pagesInHorizon ) ){
				$stringPages[] = '<a href="#" onClick="setListPanelParam(\'' . $this->getFormName() .'\',\''. $this->pagerListPanel->getPageParamName() .'\',\'' . $page . '\');">' . $page . '</a>';
			}
		}
		if( $j < $pages ){
			$stringPages[] = '...';
		}
		
		$html .= implode( ", ", $stringPages ) . ' ';
		$html .= $this->getNextButtons( $curPage, $pages );
		return $html;
	}
	
	private function getPreviousButtons( $curPage ){
		$html = '';
		if ( $curPage > 1){
			if ( $curPage > 2 ){
				$html .= '<a href="#" onClick="setListPanelParam(\'' . $this->getFormName() .'\',\''. $this->pagerListPanel->getPageParamName() .'\',\'1\');">';
				$html .= '<img src="img/resultset_first.png" border="0" alt="&lt;&lt;" title="' . $this->pagerListPanel->getFirstPageLabel() .'" align="absmiddle"/>';
				$html .= '</a> ';
			}
			else { 
				$html .= '<img src="img/resultset_first.dis.png" border="0" alt="&lt;&lt;" title="' . $this->pagerListPanel->getFirstPageLabel() .'" align="absmiddle" /> ';
			}
			$html .= '<a href="#" onClick="setListPanelParam(\'' . $this->getFormName() .'\',\''. $this->pagerListPanel->getPageParamName() .'\',\'' . ( $curPage - 1 ) . '\');">';
			$html .= 	 '<img src="img/resultset_previous.png" border="0" alt="&lt;" title="' . $this->pagerListPanel->getPreviousLabel() .'" align="absmiddle"/>';
			$html .= '</a> ';
		}
		else {
			$html .= '<img src="img/resultset_first.dis.png" border="0" alt="&lt;&lt;" title="' . $this->pagerListPanel->getFirstPageLabel() .'" align="absmiddle"/> ';
			$html .= '<img src="img/resultset_previous.dis.png" border="0" alt="&lt;" title="' . $this->pagerListPanel->getPreviousLabel() .'" align="absmiddle" /> ';
		}
		return $html; 
	}
	
	private function getNextButtons( $curPage, $pages ){
		$html = '';
		if ( $curPage < $pages ){
			$html .= '<a href="#" onClick="setListPanelParam(\'' . $this->getFormName() .'\',\''. $this->pagerListPanel->getPageParamName() .'\',\'' . ( $curPage + 1 ) . '\');">';
			$html .=   '<img src="img/resultset_next.png" border="0" alt="&gt;" title="' . $this->pagerListPanel->getNextLabel() .'"  align="absmiddle" />';
			$html .= '</a>';
			if ( $curPage < $pages-1 ){
				$html .= '<a href="#" onClick="setListPanelParam(\'' . $this->getFormName() .'\',\''. $this->pagerListPanel->getPageParamName() .'\',\'' . ( $pages ) . '\');">';
  			$html .=   '<img src="img/resultset_last.png" border="0" alt="&gt;&gt;" title="' . $this->pagerListPanel->getLastPageLabel() . ' (' . $pages .')"  align="absmiddle" />';
  			$html .= '</a>';
			}
			else {
				$html .=   '<img src="img/resultset_last.dis.png" border="0" alt="&gt;&gt;" title="' . $this->pagerListPanel->getLastPageLabel() . ' (' . $pages .')"  align="absmiddle" />';
			}
		}
		else {
			$html .= '<img src="img/resultset_next.dis.png" border="0" alt="&gt;" title="' . $this->pagerListPanel->getNextLabel() .'"  align="absmiddle" /> ';
			$html .= '<img src="img/resultset_last.dis.png" border="0" alt="&gt;&gt;" title="' . $this->pagerListPanel->getLastPageLabel() . ' (' . $pages .')"  align="absmiddle" />';
		}
		return $html;
	}
	
	public function getHiddenFieldsHtml(){
		$html = parent::getHiddenFieldsHtml();
		$html.= '<input type="hidden" name="' . $this->pagerListPanel->getPageParamName() .'" value="' . $this->pagerListPanel->getCurrentPage() . '" />';
		return $html;
	}

	public function getPagingOffsetSelector(){
		$offset = $this->pagerListPanel->getOffset();
		$inc    = $this->pagerListPanel->getOffsetIncrement();
		$max    = $this->pagerListPanel->getOffsetMax();
		$label  = $this->pagerListPanel->getOffsetLabel();
		$html = $label . ' ';
		$html .= '<select name="' . $this->pagerListPanel->getOffsetParamName() . '" onchange="setListPanelParam( \'' . $this->getFormName() . '\', \'p\', 1, false); document.forms[\'' . $this->getFormName() . '\'].submit();" class="listPanel_PageOffset_Selector">';
		for( $i = $inc; $i <= $max; $i += $inc	 ){
  		$html.= '<option class="listPanel_PageOffset_Option" value="' . $i . '" ' . ( ( $offset == $i ) ? 'selected="SELECTED"' : '' ) .' >' . $i . '</option>';
  		

  	}
  	if(Util_Server::getScript() != 'product.php')
  		$html .= '<option class="listPanel_PageOffset_Option" value="-1" ' . ( ( $offset == -1 ) ? 'selected="SELECTED"' : '' ) .' >&#8734;</option>';
  	$html .= '</select>';
  	
  	
  	return $html;
	}
}
?>