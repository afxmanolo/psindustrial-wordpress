<?php

class GUI_ListPanel_ListPanel extends Objeto {
	
	private $name;
	private $tblWidth;
	private $template;
	
	// Data & columns handling
	protected $data = array();
	protected $hiddenColumns = array();
	protected $columnNames = array();
	protected $callBacks = array();
	protected $defaultColumns = array();
	protected $columnAlignment = array();
	protected $hiddenFields = array();
	
	/**
	 * Creates a new instance of the ListPanel
	 */
	public function __construct( $name, $tblWidth = '100%' ){
		parent::__construct();
		$this->name = $name;
		$this->tblWidth = $tblWidth;
		$this->setTemplate( new GUI_ListPanel_Template( $this ) );
	}
	
	/**
	 * Sets the Data (array) that will be displayed by the ListPanel
	 *
	 * @param array $data
	 * @return void
	 */
	public function setData( $data ){
		$this->data = $data;
	}

	/**
	 * Returns an Array containing the data displayed by the ListPanel
	 * @return array
	 */
	public function getData(){
		return $this->data;
	}
	
	/**
	 * Sets the CallBack functions that will be used when displaying the ListPanel
	 *  
	 * @param array $callBacks
	 * @return void
	 * @example $callBacks = array( 'date' => 'parseDate', ...) 
	 * 					where 'parseDate' is the name of the function that will be 
	 * 					called each time the column 'date' is being printed by the ListPanel.
	 */
	public function setCallBacks( $callBacks ){
		$this->callBacks = $callBacks;
	}

	/**
	 * Returns the array containing the CallBack functions, where the key is the name of the column and the value is the name of the function
	 * 
	 * @return array
	 */
	public function getCallBacks(){
		return $this->callBacks;
	}

	/**
	 * Adds a callback function for a given column name. 
	 * 
	 * If the column already has a callback function associated, it's overwritten by the new function name.
	 *
	 * @param string $columnName
	 * @param string $functionName
	 * @return void
	 */
	public function addCallBack( $columnName, $functionName ){
		$this->callBacks[ $columnName ] = $functionName;
	}
	
	/**
	 * Retrieves the function name for the callback procedure of the give column or NULL if not found. 
	 *
	 * @param string $columnName
	 * @return string or null
	 */
	public function getCallBack( $columnName ){
		if ( isset( $this->callBacks[ $columnName ] ) ){
			return $this->callBacks[ $columnName ];
		}
		else {
			return null;
		}
	}
	
	/**
	 * Overrides the names of the columns (taken from the data array keys) with custom names
	 *
	 * @param array $columnNames 
	 * @return void
	 * @example $columnNames = array( 'fname' => 'First Name', ... ) where 'fname' is the key of the data array.
	 */
	public function setColumnNamesOverride( $columnNames ){
		$this->columnNames = $columnNames;
	}

	/**
	 * Returns an Array containing the data displayed by the ListPanel
	 * @return array
	 */
	public function getColumnNamesOverride(){
		return $this->columnNames;
	}
	
	public function addColumnNameOverride( $column, $name ){
		$this->columnNames[$column] = $name;
	}
	
	public function setHiddenColumns( $columns ){
		$this->hiddenColumns = $columns;
	}
	
	public function getHiddenColumns(){
		return $this->hiddenColumns;
	}
	
	public function addHiddenColumn( $columnName ){
		$this->hiddenColumns[] = $columnName;
	}
	
	public function isColumnVisible( $columnName ){
		if ( in_array( $columnName, $this->hiddenColumns ) ){
			return false;
		}
		else {
			return true;
		}
	}
	
	public function setDefaultColumns( $columns ){
		$this->defaultColumns = $columns;
	}
	
	public function getDefaultColumns(){
		return $this->defaultColumns;
	}
	
	public function setColumnAlignment( $columnAlignments ){
		$this->columnAlignment = $columnAlignments;
	}
	
	public function getColumnAligment(){
		return $this->columnAlignment;
	}
	
	public function addColumnAlignment( $columnName, $alignment ){
		$this->columnAlignment[ $columnName ] = $alignment;
	}
	
	public function getColumnAlignmentByName( $columnName ){
		return ( isset( $this->columnAlignment[ $columnName ] ) ) 
							? $this->columnAlignment[ $columnName ]
							: 'center';
	}
	
	public function addDefaultColumn( $columnName ){
		$this->defaultColumns[] = $columnName;
	}

	public function getName(){
		return $this->name;
	}
	
	public function getTableWidth(){
		return $this->tblWidth;
	}
	
	public function setTableWidth( $width ){
		$this->tblWidth = $width;
	}

	public function setTemplate( GUI_ListPanel_Template $template ){
		$this->template = $template;
	}

	public function getTemplate(){
		return $this->template;
	}
	
	public function getVisibleColumns(){
		$data = $this->getData();
		$headers = !empty( $data ) 	? array_keys( $data[0] ) 
                           			: $this->getDefaultColumns();
		$headers = array_diff( $headers, $this->getHiddenColumns() );
		return $headers;
	}
	
	public function getTotalColumnsCount(){
		return count( $this->getVisibleColumns() );
	}
	
	public function display($utf8 = null){
		$this->template->display($utf8);
	}

	public function setHiddenFields( $arrFields ){
		$this->hiddenFields = $arrFields;
	}
	
	public function addHiddenField( $name, $value ){
		$this->hiddenFields[ $name ] = $value;
	}
	
	public function getHiddenFields(){
		return $this->hiddenFields;
	}
}
?>