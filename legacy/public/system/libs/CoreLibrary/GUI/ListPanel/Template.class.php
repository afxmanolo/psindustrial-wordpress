<?php

class GUI_ListPanel_Template extends Objeto {
	
	protected $listPanel;
	protected $formName;
	protected $template;
	
	public function __construct( GUI_ListPanel_ListPanel $listPanel ){
		parent::__construct();
		$this->listPanel = $listPanel;
		$this->formName = 'frm' . ucwords( $listPanel->getName() );
	}
	
	public function display( $utf8encode = false ){
		$this->template = $this->listPanel->getTemplate();		// Back reference from ListPanel
		$html = $this->template->getFormHtml();		
		if ( $utf8encode ){
			echo  $html ;
		}
		else {
			//echo utf8_decode($html);
		    echo $html;
		}
	}
	
	public function getFormHtml(){
		$html = '<form name="' . $this->formName . '" method="get" action="" id="' . $this->formName . '" style="margin:0px; padding: 0px;">';
		$html.= $this->template->getJavaScript();
		$html.= $this->template->getHiddenFieldsHtml();
		$html.= $this->template->getTable();
		$html.= '</form>';
		return $html;
	}
	
	public function getTable(){
		$html = '<table  id="table" class="table table-bordered table-striped" >';
		$html.= $this->template->getHeadersRows();
		$html.= $this->template->getDataRows();
		$html.= '</table>';
		return $html;
	}
	
	public function getHiddenFieldsHtml(){
		$fields = $this->listPanel->getHiddenFields();
		$html = '';
		foreach ( $fields as $name => $value ){
			$html .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
		}
		return $html;
	}
	
	public function getHeadersRows(){
		$html = '<thead><tr>';
		$html.= $this->template->getHeaderCells();
		$html.= '</tr></thead>';
		return $html;
	}
	
	public function getHeaderCells(){
		$headers = $this->listPanel->getVisibleColumns();
		$html = '';
		foreach( $headers as $header ){
  			$html.= '<th>';
  			$html.= $this->template->getHeaderCellContent( $header );
  			$html.= '</th>';
		}
		return $html;
	}
	
	public function getHeaderCellContent( $headerName ){
		$headerNames = $this->listPanel->getColumnNamesOverride();
		$headerName = isset( $headerNames[ $headerName ] ) ? $headerNames[ $headerName ] : ucwords( str_replace( '_', ' ', $headerName ) );
		return $headerName;
	}
	
	public function getDataRows(){
		//$lang = ProjectLibrary_SDO_LanguageManager::GetActualLanguage();
		$headers = $this->listPanel->getVisibleColumns();
		$data = $this->listPanel->getData();
		$html = '';
		if ( count( $this->listPanel->getData() ) == 0 ) {
			$label = $this->trans('no_records_to_display');
			$html .= '<tbody><tr>';
			$html .= '<td colspan="' . count( $headers ) . '">';
			$html .= '<i>' . $label . '</i>';
			$html .= '</td>';
			$html .= '</tr></tbody>';
		}
		else {
			$i = 1;
			foreach( $data as $record) {
				$html .= '<tr class="listPanel_RecordRow ' . ( ( $i % 2 == 0) ? 'listPanel_recordOddRow' : '' ) .'">';
				$html .= $this->template->getDataCells( $record );
				$html .= '</tr>';
				$i++;
			}
		}
		return $html;
	}
	
	public function getDataCells( $record ){
		$html = '';
		foreach( $record as $column => $value ) {
			$html .= $this->template->getDataCell( $column, $value );
		}
		return $html;
	}
	
	public function getDataCell( $column, $value ){
		$html = '';
		if ( in_array( $column, $this->listPanel->getVisibleColumns() ) ){
			$alignment = $this->listPanel->getColumnAlignmentByName( $column );
			$html.= '<td class="listPanel_RecordCell" align="' . $alignment . '">';
			$html.= $this->template->getDataCellContent( $column, $value );
			$html.= '</td>';
		}
		return $html;
	}
	
	public function getDataCellContent( $column, $value ){
		$functionName = $this->listPanel->getCallBack( $column );
		$value = ( $functionName == null ) ? $value : call_user_func( $functionName, $value ); 
		return $value;
	}

	public function getFormName(){
		return $this->formName;
	}

	private function getJavaScript(){
		$html = '<script language="javascript">';
		$html.= ' function setListPanelParam( formName, fieldName, value, submit ){';
		$html.= '   if ( submit == null ) {';
		$html.= '     submit = true;';
		$html.= '   }';
		$html.= '   var form = document.forms[formName];';
		$html.= '   form.elements[fieldName].value = value;';
		$html.= '   if ( submit ) {';
		$html.= '   	form.submit();';
		$html.= '   }';
		$html.= ' }';
		$html.= '</script>';
		return $html;
	}
}

?>