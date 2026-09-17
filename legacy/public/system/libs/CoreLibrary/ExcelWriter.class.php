<?php
class ExcelWriter extends Objeto {
	
	/**
	 * @var Spreadsheet_Excel_Writer
	 */
	protected $xls;
	
	/**
	 * Default worksheet
	 *
	 * @var Spreadsheet_Excel_Writer_Worksheet 
	 */
	
	protected $sheet;

	public function __construct( $fileName = '', $worksheetName = 'Sheet 1', $sendToUser = true ){
		$this->xls = new Spreadsheet_Excel_Writer();
		
		if ( empty( $fileName ) ){
			$fileName = date('Y-m-d_H-i-s');
		}
		
		if ( $sendToUser ){
			$this->xls->send( $fileName .'.xls' );
		}
		$this->sheet =& $this->xls->addWorksheet( $worksheetName );
		
	}
	
	public function writeHeaderColumns( $headers, $unwantedColumns = array() ){
		// Format
		$formatHeaders =& $this->xls->addFormat();
		$formatHeaders->setBold();
		
		// Write header columns
		$row = 0;
		$col = 0;
		
		// Delete columns that should not be displayed in the excel output
		$newHeaders = array();
		foreach( $headers as $header ){
			if  ( !in_array( $header, $unwantedColumns ) ){
				$newHeaders[] = ucwords( str_replace( '_', ' ', $header ) );
			}
		}
		
		$this->sheet->writeRow( $row, $col, $newHeaders, $formatHeaders );
		$row++;
	}
	
	public function writeDataArray( $data, $unwantedColumns, $utf8decode = true ){
		$row = 1;
		$col = 0;
		foreach( $data as $record ){
			if ( $utf8decode ){
				$record = array_map( "utf8_decode", $record );
			}
			$this->sheet->writeRow( $row, $col, $record );
			$row++;
		}
	}
	
	public function send(){
		 $this->xls->close();
	}
	
	public function writeHeadersAndDataFromArray( $data, $unwanteColumns = array() ){
		$this->writeHeaderColumns( array_keys( $data[0] ), $unwanteColumns);
		$this->writeDataArray( $data, $unwanteColumns );
		$this->send();
	}
	
}
?>