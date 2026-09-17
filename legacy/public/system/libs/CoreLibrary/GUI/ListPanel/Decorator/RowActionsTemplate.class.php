<?php
class GUI_ListPanel_Decorator_RowActionsTemplate extends GUI_ListPanel_Decorator_TemplateDecorator {

	private $myListPanel;

	public function __construct( GUI_ListPanel_Template $template, GUI_ListPanel_Decorator_RowActions $listPanel ){
		parent::__construct( $template );
		$this->myListPanel = $listPanel;
	}
	
	public function getDataCells( $record ){
		$html = parent::getDataCells( $record );
		$html.= $this->getRowActions( $record );
		return $html;
	}
	
	protected function getRowActions( $record ){
		$actions = $this->myListPanel->getActions();
		
		$html = '<td align="center" style="">';
		$html.=   '<div class="listPanel_RowActions" style="white-space: nowrap;">';
		foreach( $actions as $action ){
  		$link  = $this->parseString( htmlentities( $action->getLink(), ENT_COMPAT, CHARSET_PROJECT  ), $record );
  		$event = $this->parseString( htmlentities( $action->getOnClickEvent(), ENT_COMPAT, CHARSET_PROJECT ), $record ); 
  		$alt   = $this->parseString( htmlentities( $action->getImageAlt(), ENT_COMPAT, CHARSET_PROJECT ), $record );
  		$title = $this->parseString( htmlentities( $action->getImageTitle(), ENT_COMPAT, CHARSET_PROJECT ), $record );
			$html.= '<a href="' . $link . '" onclick="' . $event . '" target="' . $action->getTarget() . '" class="btn btn-default" title='.$title.' '.$action->getParams().'>';
			/*$html.=   '<img src="' . $action->getImageUrl() . '" '. 
			               'alt="' . $alt . '" '. 
			               'title="' . $title . '" '.
			               'border="0">';*/
			$html .= '<i class="'.$action->getImageUrl().'" aria-hidden="true"></i>';
			$html.= '</a> ';
		}
		$html.=   '</div>';
		$html.= '</td>';
		return $html;
	}

	private function parseString( $string, $record ){
		$reg = '/\$(\w*)/';
		$rs = array();
		preg_match_all( $reg, $string, $rs );
		foreach( $rs[0] as $i => $pattern ){
			
			if ( array_key_exists( substr( $pattern, 1 ), $record ) ) { //  $record[ $rs[1][ $i ] ] ) ){
//				if ( $record[ $rs[1][$i] ] == null ){
////					$string = str_replace( '{' . $pattern .'}', 'NULL', $string );
//				}
//				else {
					$string = str_replace( '{' . $pattern .'}', $record[ $rs[1][$i] ], $string );
//				}
			}
		}
		return $string;
	}

	public function getHeaderCells(){
		$html = parent::getHeaderCells();
		$html.= '<th>'. $this->myListPanel->getHeaderLabel() .'</th>';
		return $html;
	}
}
?>