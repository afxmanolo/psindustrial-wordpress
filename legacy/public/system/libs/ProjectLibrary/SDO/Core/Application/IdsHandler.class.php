<?php
/**
 * This class provides access to manipulation of Users in the System.
 * 
 */	  
class ProjectLibrary_SDO_Core_Application_IdsHandler extends ProjectLibrary_SDO_Core_Application{
	protected $IdHandler;
	protected $maximum;
	
	protected $arrOptions;
	const TOKEN = ',';
	
	function __construct( $name, $maximum = 20 ){
		
		parent::__construct();
		$this->IdHandler = new ProjectLibrary_Entity_IdsHandler();
		$this->IdHandler->setName( $name );
		$this->maximum = $maximum;
		$this->arrOptions = array();
	}

	
//------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------
	public function setArrIds( $arrIds ){
		$this->IdHandler->setArrayIds( $arrIds );
	}
	
	public function setArrOptions( $arrTxtPerId ){
		$this->arrOptions = $arrTxtPerId;
	}

	public function createBOImages( $title ='Registro',$hideOrdenDeAparicion = false){
		
		$savant = new ProjectLibrary_Savant();
		$arrImages = $this->IdHandler->getArrayIds();
		$html = '';
		$ban = false;
		
		if( $arrImages > 0 && $arrImages[0] !=''){
			$i=0;
			$html = '<table border="0" bgcolor="#EFEFEF">
			<tr valign="top" bgcolor="#CCCCCC"><th width="50">'.$savant->trans('delete').'</th>' . ($hideOrdenDeAparicion ? '' : ('<th width="70">'.$savant->trans('appearance_order').'</th>') ). ' <th width="250" align="left">' . $title . '</th></tr>';
			foreach ( $arrImages as $image){
				$imageInfo = empty($this->arrOptions[ $image ]) ? '' : $this->arrOptions[ $image ]; 
				//if( !empty($imageInfo) ){
					$i++;
					$html .= '<tr>
					<td align="center">' . ($hideOrdenDeAparicion ? ('<input type="hidden" size="3" name="InputOrder_' . $image . '_' . $this->IdHandler->getName() . '" value="' . $i . '">') : '') . '
					<input onChange="updateContador2_'.$this->IdHandler->getName().'( this.checked )" type="checkbox" name="checkBoxDelete_' . $image . '_' . $this->IdHandler->getName() . '" value="1"></td>' .
					($hideOrdenDeAparicion ? '' : ('<td align="center"><input type="text" size="3" name="InputOrder_' . $image . '_' . $this->IdHandler->getName() . '" value="' . $i . '"></td>') ) .
					'<td align="left">' . $imageInfo . '</td>' .
					'</tr>';
					$ban = true;
				//}
			}
			$html .= '</table>';
		}
		
		if(!$ban){
			$html = '<br /><b>'.$savant->trans('no_values_assigned_to_register').'</b>';
		}
		
		$nActualImages = $arrImages[0]=='' ? 0 : count($arrImages);
		$html .= '<br /><div style="border: solid 1px #ccc;padding:5px;width:300px;" >';
		$html .= '<div class="fileinputs_' . $this->IdHandler->getName().'" id="fileinputs_' . $this->IdHandler->getName().'" style="padding:5px;width:300px;" >';
		
		$nameInputFile = 'file_' . $this->IdHandler->getName() ;
		
		
		$html .= '</div>';
		$html .= '<a href="javascript:newFile_'.$this->IdHandler->getName().'();"> + '.$savant->trans('add').'</a></div>';
		$html .= '</div>';
		
		$html .= '
		<input type="hidden" name="nfiles_'.$this->IdHandler->getName().'" id="nfiles_'.$this->IdHandler->getName().'" value="0">
		<script language="Javascript">';
		
		
		$html .= '
			contador_'.$this->IdHandler->getName().' = 0;
			contador2_'.$this->IdHandler->getName().' = '.$nActualImages.';
			var opsSelect_'.$this->IdHandler->getName().' = new Array();
			var opsValues_'.$this->IdHandler->getName().' = new Array();
			';
		
		$options = $this->arrOptions;
		$i = 0;
		
		foreach($options as $key => $option){
			$html .=  "\n opsSelect_".$this->IdHandler->getName()."[ " . $i . " ] = '" . $option ."';";
			$html .=  "\n opsValues_".$this->IdHandler->getName()."[ " . $i . " ] = '" . $key ."';";
			$i++;
		}
		
		$html .='
			
			function updateContador2_'.$this->IdHandler->getName().'( isChecked ){
				if( isChecked ){
					contador2_'.$this->IdHandler->getName().'--;
					//newFile_'.$this->IdHandler->getName().'();
				}else{
					contador2_'.$this->IdHandler->getName().'++;
					alert("'.$savant->trans('this').' '.$savant->trans('field').' '.$savant->trans('support a maximum of').' '.$this->maximum.' '.$savant->trans('register(h)'.($this->maximum>1?'(p)':'')).'. \\n'.$savant->trans('additional_registers_will_not_be_uploaded').'.");
				}
			}
			
			function newFile_'.$this->IdHandler->getName().'(){
				content = document.getElementById("fileinputs_' . $this->IdHandler->getName().'");
				nfiles = document.getElementById("nfiles_'.$this->IdHandler->getName().'");
				
				if( (contador_'.$this->IdHandler->getName().'+ contador2_'.$this->IdHandler->getName().' + 1) <= '.$this->maximum.'){
					opciones = \'<option value="1">uno</option><option value="2">dos</option>\';
					opciones = "";
					for(i=0; i < opsValues_'.$this->IdHandler->getName().'.length; i++){
						opciones = opciones + \'<option value="\'+ opsValues_'.$this->IdHandler->getName().'[ i ] +\'">\'+ opsSelect_'.$this->IdHandler->getName().'[ i ] +\'</option>\';
					}
					
					content.innerHTML = content.innerHTML + \'<table>\'
					+ \'<tr><td>' . $title . ': </td><td><select name="' . $nameInputFile . '_\' + contador_'.$this->IdHandler->getName().' + \'">\'+ opciones +\'</select></td></tr></table>\' ;
					
					nfiles.value = contador_'.$this->IdHandler->getName().' + 1;
					contador_'.$this->IdHandler->getName().'++;
				}else{
					alert("'.$savant->trans('this').' '.$savant->trans('field').' '.$savant->trans('support a maximum of').' '.$this->maximum.' '.$savant->trans('register(h)'.($this->maximum>1?'(p)':'')).'");
				}
			}';
		
		if( count($arrImages) < $this->maximum || $arrImages[0]==''){
			$html .= 'newFile_'.$this->IdHandler->getName().'();';
		}
		$html .='</script>';
		
		return $html;
	}
	
	public function GetDescriptionField(){
		return '_description_' . $this->IdHandler->getName() ;
	}
	
	public function proccessIds( $formatString = true ){
		$arrImagesReturn = array();
		$arrImages = $this->IdHandler->getArrayIds();
		if( $arrImages > 0){
			$arrImagesReturn2 = array();
			foreach ( $arrImages as $image){
				
				if(!empty($image)){
					if( $_REQUEST[ 'checkBoxDelete_' . $image . '_' . $this->IdHandler->getName() ] != '1' ){
						$order = (!isset($_REQUEST[ 'InputOrder_' . $image . '_' . $this->IdHandler->getName() ]) ||
								 !is_numeric($_REQUEST[ 'InputOrder_' . $image . '_' . $this->IdHandler->getName() ]) ) ? 0 :
								 $_REQUEST[ 'InputOrder_' . $image . '_' . $this->IdHandler->getName() ];
						$arrImagesReturn2[ $image ] = $order;
					}
				}
			}
			if( count($arrImagesReturn2) > 0){
				$arrImagesReturn = array_keys($arrImagesReturn2);
			}
			$arrImages = $arrImagesReturn;
		}

		$imageIds = array(0);
		if( count($arrImages) < $this->maximum || $arrImages[0]=='' || empty($arrImages) ){
			$imageIds = $this->uploadFile( count($arrImages) );
		}

		foreach($imageIds as $imageId){
			if( !in_array($imageId, $arrImagesReturn) && $imageId > 0){
				$arrImagesReturn[] = $imageId;
			}
		}

		return ($formatString) ? join(',',$arrImagesReturn) : $arrImagesReturn;
	}
	
	public function uploadFile( $nActualFiles ){
		$arrIds = array();
		$nFiles = 'nfiles_' . $this->IdHandler->getName() ;
		$nFiles = empty($_REQUEST[$nFiles]) || !is_numeric($_REQUEST[$nFiles]) ? 0 : $_REQUEST[$nFiles];
		for( $i = 0; $i < $nFiles; $i++){
			if( $nActualFiles < $this->maximum ){
				$fileName = 'file_' . $this->IdHandler->getName() .'_' . $i;
				$arrIds[] = $_REQUEST[ $fileName ];
				$nActualFiles++;
			}else{
				break;
			}
		}
		
		return $arrIds;
	}
	
	public function setMaximum( $max ){
		$this->maximum = $max;
	}
	
	
}



?>