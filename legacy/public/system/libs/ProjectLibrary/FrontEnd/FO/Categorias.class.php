<?php
class ProjectLibrary_FrontEnd_FO_Categorias extends ProjectLibrary_FrontEnd_FO {
	/**
	 * @var ProjectLibrary_DAO_Event
	 */
	protected $lang;
	
	public function __construct(){
		parent::__construct();
		$this->lang = ProjectLibrary_SDO_Core_Application_LanguageManager::GetActualLanguage();
	}

	public function execute( $cmd='' ){
		
		$this->checkPermission();
		$cmd = $cmd=='' ? $this->getCommand() : $cmd;
		switch( $cmd ){
			case 'getLast3':
				return $this->_getLast3();
			break;
			case 'loadCategorias':
				$this->_loadCategorias();
			break;
			case 'search':
			default:
				$this->_searchCategorias();
			break;
		}
	}
	
	protected function _loadCategorias(){
		
		$CategoriasId = $this->getParameter();
		$Categorias = ProjectLibrary_SDO_Categorias::LoadCategorias($CategoriasId );
		

		$this->tpl->assign('Categorias', $Categorias);
		$this->tpl->display('CategoriasView.php');
	}
	
	protected function _searchCategorias(){
		$this->search = new ProjectLibrary_Entity_Search(
			'', //disabled search
			'categorias_id ASC', //column order  param example: fecha DESC
			$this->getParameter('p',true,1), //page number one
			 -1 //we need only 5 registers
		);
		$categoriaId = $this->getParameter('categoriaId',true);
		$extraConditions = array();
		$extraConditions[] = array("SQL"=>" padre_id=".$categoriaId);				
		$result = ProjectLibrary_SDO_Categorias::SearchCategorias( $this->search, $extraConditions );
		$Padre = ProjectLibrary_SDO_Core_Application_Categorias::LoadById($categoriaId);
		$this->tpl->assign('Categoria', $Padre);
		$this->tpl->assign('result', $result);
		$this->tpl->display('CategoriasMain.php');
	}
	
	protected function _getLast3(){
		$this->search = new ProjectLibrary_Entity_Search(
			'', //disabled search
			'', //column order  param example: fecha DESC
			1, //page number one
			 3 //we need only 3 registers
		);
		$extraConditions = array();
		
		//$extraConditions[] = array( "columName"=>"position","value"=>$surveyPosition,"isInteger"=>false);
		$result = ProjectLibrary_SDO_Categorias::SearchCategorias( $this->search, $extraConditions );
		
		$results = $result->getResults();
		$CategoriasReturn = array();
		foreach($results as $result){
			$CategoriasReturn[] = $result;
		}
		return $CategoriasReturn; 
	}
	
	
	protected function _searchCategoriasByDate(){
		$this->search = new ProjectLibrary_Entity_Search(
			'', //disabled search
			'date ASC', //column order  param example: fecha DESC
			$this->getParameter('p',true,1), //page number one
			 5 //we need only 5 registers
		);
		$extraConditions = array();
		
		$dCalendar = $this->getParameter("DCalendar",false,null);
		$calendar_events_month = $this->getParameter("calendar_events_month");
		
		$hidenFields = array();
		if( !empty($dCalendar) ){
			$dCalendar = GUI_Calendar_CalendarTools::GetValidDate($dCalendar);
			$extraConditions[] = array( "SQL"=>"date like '" . $dCalendar . "'");
			
			$hidenFields[ "DCalendar" ] = $dCalendar;
		}elseif( !empty($calendar_events_month) ){
			$hidenFields[ "calendar_events_month" ] = $calendar_events_month;
			$hidenFields[ "calendar_events_year" ] = $this->getParameter("calendar_events_year");
			$hidenFields[ "calendar_events_prev_month" ] = $this->getParameter("calendar_events_prev_month");
			$hidenFields[ "calendar_events_next_month" ] = $this->getParameter("calendar_events_next_month=1");
			
			
			$time = $this->_getTimeFromCalendarParams();
			$month = date("m", $time);
			$year = date("Y", $time);
			$extraConditions[] = array( "SQL"=>"date like '".$year."-".$month."-%'");
		}else{
			$extraConditions[] = array( "SQL"=>"date >= '" . date("Y-m-d") . "'");
		}
		$result = ProjectLibrary_SDO_Categorias::SearchCategorias( $this->search, $extraConditions );
		
		$this->tpl->assign('result', $result);
		$this->tpl->assign('ObjEvent', $this);
		$this->tpl->assign("hiddenFields",$hidenFields);
		$this->tpl->display('view/CategoriasMain.php');
	}
	

	protected function _getTimeFromCalendarParams(){
		$calendar_events_prev_month = $this->getParameter('calendar_events_prev_month');
		$calendar_events_next_month = $this->getParameter('calendar_events_next_month');
		$dCalendar = $this->getParameter("DCalendar",false,date("Y-m-d") );
		$month = $this->getParameter('calendar_events_month',true, GUI_Calendar_CalendarTools::GetMonthOfDate($dCalendar) );
		$year = $this->getParameter('calendar_events_year',true, GUI_Calendar_CalendarTools::GetYearOfDate($dCalendar) );

		if( $calendar_events_next_month ){
			$time = mktime(0,0,0,($month+1),1,$year);
		}elseif( $calendar_events_prev_month ){
			$time = mktime(0,0,0,($month-1),1,$year);
		}else{
			$time = mktime(0,0,0,$month,1,$year);
		}
		return $time;
	}
	
	public function _showCalendar(){
		$time = $this->_getTimeFromCalendarParams();
	
		$month = date("m", $time);
		$year = date("Y", $time);

		$this->search = new ProjectLibrary_Entity_Search('','date DESC',1,-1);
		$extraConditions = array();
		$extraConditions[] = array( "SQL"=>"date like '".$year."-".$month."-%'");
		$result = ProjectLibrary_SDO_Categorias::SearchCategorias( $this->search, $extraConditions );
		
		$noticias = $result->getResults();
		$ArrFechasMes = array();
		foreach ( $noticias as $noticia){
			$ArrFechasMes[] = date("d", strtotime($noticia->getDate() ));
		}
		$calendar = new GUI_Calendar_CalendarView( 'events' );
		$calendar->setEventDays( $ArrFechasMes );
		$calendar->setDate($year."-".$month."-01");
		$view = $calendar->getView( GUI_Calendar_CalendarView::VIEW_SMALL_MONTH );
		$view->setWidth( '200px' );
		$view->setLanguage("SP");
		//$view->setArrowRight("");
		$calendar->displayView( GUI_Calendar_CalendarView::VIEW_SMALL_MONTH );
	}


}
?>