<?php

class GUI_Calendar_CalendarApplication extends Application {

	protected $name;
	protected $dao;
	protected $validator;
	protected $db;

	public function __construct( DB $db, $calendarName = '' ){
		parent::__construct();
		if ( $calendarName == '' ) $calendarName = '' . time();
		$this->name = $calendarName;
		$this->db = $db;
    
		$this->setRedirectStrategies();
	}
	
	protected function setRedirectStrategies(){
		$this->redirectStrategies[ 'list' ] = "calendar_list.php";
		$this->redirectStrategies[ 'edit' ] = "calendar_edit.php";
		$this->redirectStrategies[ 'deny' ] = "index.php";
	}

	protected function setValidator( $validator ){
		$this->validator = $validator;
	}

	public function execute(){
		$cmd = $this->getCommand();
		
		if ( !parent::hasPermission( $cmd ) ){
			$url = $this->redirectStrategies[ 'deny' ];
			$this->redirect( $url, false);
			exit;
		}
		switch( $cmd ){
			case 'modify':
				$this->_modify();
				break;
					
			case 'view':
				$url = $this->redirectStrategies[ 'view' ];

			case 'list':
			default:
				$this->_list();
				break;
		}
	}

	protected function _list(){
		$events = $this->dao->loadAll();
		Request::addAttribute( 'events', $events );

		$url = $this->redirectStrategies[ 'list' ];

		$this->redirect( $url );
	}

	protected function _modify(){

		$id = ( isset( $_REQUEST[ 'id' ] ) && !empty( $_REQUEST[ 'id' ] ) ) ? $_REQUEST[ 'id' ] : 0;

		$action = parent::getAction();
		switch ( $action ){
				
			case 'view':
				$url = $this->redirectStrategies[ 'view' ] . '?id=' . $id;
				$event = $this->dao->loadById( $id );
				Request::addAttribute( 'event', $event );
				$this->redirect( $url );
				break;

			case 'delete':
				$url = $this->redirectStrategies[ 'list' ];
				$rowsDeleted = $this->dao->delete( $id );
				Request::addAttribute( 'deleted_rows', $rowsDeleted );
				$this->_list();
				break;

			case 'save':
				$this->_save();
				break;
					
			case 'add':
			case 'edit':
			default :
				$event = $this->dao->loadById( $id, true );
				Request::addAttribute( 'event', $event);
				$url = $this->redirectStrategies[ 'edit' ];
				$this->redirect( $url );
				break;
		}

	}

	protected function _save(){
		if ( $this->validator != null ){
			$this->validator->validate();
			if ( $this->validator->isValid() == false ){
				// Do something to return the user to the original page and display errors
			}
		}
		else {
			// We don't need no validation. Proceed to saving.

			// Verify the form submitted the info inside the 'event' array;
			$event = isset( $_REQUEST[ 'event' ] ) ? $_REQUEST[ 'event' ] : array();

			if ( is_array( $event ) && !empty( $event ) ){
				$event['start_date'] = date( 'Y-m-d', strtotime( $event['start_date'] ) );
				if ( isset( $_REQUEST[ 'hasEndDate' ] ) ){
					$event['end_date'] = date( 'Y-m-d', strtotime( $event['end_date'] ) );
				}
				$id = $this->dao->save( $event );
				$event['more'] = strip_tags( $event['more'], '<a><p><br>' );
			}
			else {
				throw new Exception( "Could not save event because source data is empty." );
			}

		}
		
		Request::addAttribute( 'msg', 'The event was successfully saved.');
		header( 'Location: calendar.php?cmd=list' );

	}

}
?>