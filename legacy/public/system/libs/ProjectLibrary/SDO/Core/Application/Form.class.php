<?php
/**
 * This class provides access to manipulation of Users in the System.
 * 
  */	  
class ProjectLibrary_SDO_Core_Application_Form extends ProjectLibrary_SDO_Core_Application {
	public static function displayForm( $form, $errors, $idForm =''){
		$elements        = empty($form["elements"]) ? array() : $form["elements"];
		$displayFieldset = $form["displayFieldset"] ?? true;
		$html           ='';
		$principalTitle = $form['title'] ?? '';
		$principalTitle = empty($principalTitle) ? 'Informaci&oacute;n' : $principalTitle;
		//$html .= $displayFieldset ? '<fieldset><legend>' . $principalTitle . '</legend>' : '';
		$html .= $displayFieldset ? '<div class="form-group"><h3 class="box-title">'.$principalTitle.'</h3></div>' : '';

		$html .= '';
		foreach( $elements as $element){		    
			if( $element["type"] != 'hidden' && !empty($element["title"]) && $element["type"] != 'fieldset' && $element["type"] != 'iframe' && $element["type"] != 'htmltext'){
				$html .=	'<div class="form-group '.($element["class"] ?? '').'"><label for="'.$element["id"].'">' . $element["title"] . '</label>';
			}

			$html     .= self::buildHTMLElement($element);
			$comments = empty($element["comments"]) ? '' : $element["comments"];
			$html     .= ' ' . $comments ;

			if( $element["type"] != 'hidden' && !empty($element["title"]) && $element["type"] != 'fieldset' && $element["type"] != 'iframe' && $element["type"] != 'htmltext'){
				$html .= '</div>';
			}
		}
		echo $html;
	}

	public static function buildHTMLElement($element){
		$commentsBefore = empty($element["comments_before"]) ? '' : $element["comments_before"];
		$style          = empty($element["style"]) ? '' : 'style="' . $element["style"] . '"';
		$class          = $element["class"] ?? '';

		$html = '';
		switch ($element["type"]) {
			case 'hidden':
				$html .='<input type="hidden" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' />';
				break;
			case 'text':
				$params = $element["params"] ?? '';
				$html   .= $commentsBefore . '<input type="text" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' />';
				break;
			case 'phone':
				$params = $element["params"];
				$html   .= $commentsBefore . '<div class="input-group"><span class="input-group-addon"><i class="fa fa-phone"></i></span><input type="number" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' /></div>';
				break;
			case 'number':
				$params = $element["params"];
				$html   .= $commentsBefore . '<input type="number" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' />';
				break;
			case 'email':
				$params = $element["params"];
				$html   .= $commentsBefore . '<div class="input-group"><span class="input-group-addon"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' /></div>';
				break;
			case 'username':
				$params = $element["params"];
				$html   .= $commentsBefore . '<div class="input-group"><span class="input-group-addon">@</span><input type="email" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' /></div>';
				break;
			case 'password':
				$params = $element["params"];
				$html   .= $commentsBefore . '<div class="input-group"><span class="input-group-addon"><i class="fa fa-unlock-alt"></i></span><input type="password" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' /></div>';
				break;
			case 'select':
				$element["params"] = empty($element["params"]) ? '' : $element["params"];
				$html.= '
				<select name="'.$element["name"].'" id="'.$element["id"].'" ' . $element["params"] . ' class="form-control">';
				foreach( $element["options"] as $key => $option){
					if( is_array($element["value"]) ){
						$selected =in_array($key, $element["value"]);
					}else{
						$selected = ($key == $element["value"]);
					}//end if

					$selected = $selected ? 'selected="selected"' : '';
					$html .= '<option value="'.$key.'"' . $selected. '>'.$option.'</option>';
				}
				$html .= '</select>';
				break;
			case 'fieldset':
				$html .= '<div class="clearfix"></div><div class="form-group"><h3 class="box-title">' . $element["title"] . '</h3></div><hr>';
				break;
			case 'textarea':
					$html .= '<textarea name="'.$element["name"].'" id="'.$element["id"].'" ' . ($element["params"] ?? '') . ' class="form-control" rows="4">'.$element["value"].'</textarea>';
			break;
			case 'textarea_editor':
					$html .= '
					<div class="box">
						<div class="box-body pad">
							<textarea name="'.$element["name"].'" id="'.$element["id"].'" ' . ($element["params"] ?? '') . ' class="form-control">'.$element["value"].'</textarea>
						</div>
					</div>';
					$html .='
						<script>
							$(function(){
								CKEDITOR.replace("'.$element["id"].'", {
									language: "es_MX",
									uiColor: "#D2D6DE",
									height:"350px",
									extraPlugins: "filebrowser",
									extraPlugins: "justify",
									filebrowserUploadUrl: "'.ABS_HTTP_URL.'system/backoffice/uploader/upload.php",
									filebrowserImageUploadUrl: "'.ABS_HTTP_URL.'system/backoffice/uploader/upload.php?type=Images"
								});
							});
						</script>
					';
			break;
			case 'date':
				$params = $element["params"];
				$html   .= $commentsBefore . '<input type="text" class="form-control" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' autocomplete="off" />';
				$html .="<script type='text/javascript'>
						$(function () {
							$('#".$element["id"]."').datepicker({
								autoclose: true,
								format: 'dd/mm/yyyy',
							});
						});
						</script>";

				break;
			case 'date_icon':
				$params = $element["params"];
				$html .= '<div class="input-group date"><div class="input-group-addon"><i class="fa fa-calendar"></i></div>';
				$html .= '<input type="text" class="form-control pull-right"'.self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' autocomplete="off">';
                $html .= '</div>';
                $html .="<script type='text/javascript'>
						$(function () {
							$('#".$element["id"]."').datepicker({
								autoclose: true,
								format: 'yyyy-mm-dd',
							});
						});
						</script>";
				break;
			case 'array_files':			   
				$hideDes = $element['hide_description'] ?? false;
				$imageHandler = new ProjectLibrary_SDO_Core_Application_ImageHandler($element["id"], empty($element['max_files']) ? 1 : $element['max_files'] );				
				$imageHandler->setArrImages( $element["value"] );
				$html .= $imageHandler->createBOImages(null,null,$hideDes,($element["params"] ?? ''));
			break;
			case 'htmltext':
				$html .= $element["value"];
			break;
			case 'selectMultiple':
				$element["params"] = empty($element["params"]) ? '' : $element["params"];
				$html.= '
				<select name="'.$element["name"].'" id="'.$element["id"].'" ' . $element["params"] . ' class="form-control">';
				foreach( $element["options"] as $key => $option){
					if( is_array($element["value"]) ){
						$selected =in_array($key, $element["value"]);
					}else{
						$selected = ($key == $element["value"]);
					}//end if

					$selected = $selected ? 'selected="selected"' : '';
					$html .= '<option value="'.$key.'"' . $selected. '>'.$option.'</option>';
				}
				$html .= '</select>';
				$html .="<script type='text/javascript'>
						$(function () {
							$('#".$element["id"]."').select2();
						});
						</script>";
				break;
			default:
				# code...
				break;
		}
		return $html;		
	}//end function

	
	function hideAllHTMLElements($elements, $arrIdUnset = array() ){
		$result = array();
		foreach($elements as $element){
			$type = $element['type'];
			$id = $element['id'];
			if( $type != 'htmltext' && $type != 'fieldset' && $type != 'iframe' && !in_array($id,$arrIdUnset) ){
				$element['value'] = $type == 'array_files' ? implode(',',$element['value']) : $element['value'];
				$element['type'] = 'hidden'; 
				$result[] = $element;
			}
		}
		return $result;
	}
	function buildHTMLElement0($element, $errors){
			$commentsBefore = empty($element["comments_before"]) ? '' : $element["comments_before"];
			$style = empty($element["style"]) ? '' : 'style="' . $element["style"] . '"';
			
			$html ='';	
			switch( $element["type"] ){
				case 'datetime':
					$element["value"] = empty($element["value"]) ? date("Y-m-d H:i:s") : $element["value"];
					$html .='<input type="hidden" readonly="yes" size="20" maxlength="127" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' />';
					
					$fecha = date("Y-m-d",strtotime($element["value"]));
					$hora = date("H",strtotime($element["value"]));
					$minuto = date("i",strtotime($element["value"]));
					
					$html .= '<input type="text" readonly="yes" size="10" class="frmInput" ' . self::printNameIdAndValue( "", "date_".$element["id"], $fecha, false) . ' onChange="this.form.'.$element["id"].'.value=this.form.date_'.$element["id"].'.value+\' \'+this.form.hour_'.$element["id"].'.value+\':\'+this.form.minutes_'.$element["id"].'.value+\':00\';" /><button type="reset" id="f_trigger_b_'.$element["id"].'">...</button>
					<script type="text/javascript">
					    Calendar.setup({
					        inputField     :    "date_'.$element["id"].'",      // id of the input field
					        ifFormat       :    "%Y-%m-%d",       // format of the input field
					        showsTime      :    false,            // will display a time selector
					        button         :    "f_trigger_b_'.$element["id"].'",   // trigger for the calendar (button ID)
					        singleClick    :    true,           // double-click mode
					        step           :    1                // show all years in drop-down boxes (instead of every other year as default)
					    });
					</script>';
					
					$html .= $this->trans('hour') . ' : <select name="" id="hour_'.$element["id"].'" onChange="this.form.'.$element["id"].'.value=this.form.date_'.$element["id"].'.value+\' \'+this.value+\':\'+this.form.minutes_'.$element["id"].'.value+\':00\';">';
					for( $i =0; $i<24; $i++){
						$hr = str_pad( $i, 2, '0', STR_PAD_LEFT);
						$selected = ($hr == $hora) ? 'selected="selected"' : '';
						$html .= '<option value="'.$hr.'"' . $selected. '>'.$hr.'</option>';
					}
					$html .= '</select>';
					
					$html .= $this->trans('minute') . ' : <select name="" id="minutes_'.$element["id"].'" onChange="this.form.'.$element["id"].'.value=this.form.date_'.$element["id"].'.value+\' \'+this.form.hour_'.$element["id"].'.value+\':\'+this.value+\':00\';">';
					for( $i =0; $i<60; $i+= 1){
						$min = str_pad( $i, 2, '0', STR_PAD_LEFT);
						$selected = ($min == $minuto) ? 'selected="selected"' : '';
						$html .= '<option value="'.$min.'"' . $selected. '>'.$min.'</option>';
					}
					$html .= '</select>';
					
				break;
				case 'select':
					$element["params"] = empty($element["params"]) ? '' : $element["params"];
					$html .= '<select name="'.$element["name"].'" id="'.$element["id"].'" ' . $element["params"] . '>';
					foreach( $element["options"] as $key => $option){
						if( is_array($element["value"]) ){
							$selected =in_array($key, $element["value"]);
						}else{
							$selected = ($key == $element["value"]);
						}
						$selected = $selected ? 'selected="selected"' : '';
						$html .= '<option value="'.$key.'"' . $selected. '>'.$option.'</option>';
					}
					$html .= '</select><span id="spanNota"></span>';
				break;
				case 'label':
					$params = $element["params"];
					$html .= nl2br($element["value"]);
					$html .= $commentsBefore . '<input type="hidden" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' />';
					break;
				case 'selectWithOther':
					
					
					$firstValue ='';
					$html .= '<select name="" id="__idSelectTmp_'.$element["id"].'" onClick="if(this.value ==\'\')document.getElementById(\'__idTxtTmp_'.$element["id"].'\').style.visibility=\'visible\';else document.getElementById(\'__idTxtTmp_'.$element["id"].'\').style.visibility=\'hidden\';"'
					.' onChange="document.getElementById(\''.$element["id"].'\').value =this.value">'
					;
					foreach( $element["options"] as $key=> $option){
						$firstValue = $firstValue=='' ? $key : $firstValue;
						
						$selected = $key == $element["value"] ? 'selected="selected"' : '';
						$html .= '<option value="'.$key.'"' . $selected. '>'.$option.'</option>';
					}
					
					$firstValue = empty($element["value"]) ? $firstValue : $element["value"];
					
					$html .= '<option value="">Other</option>';
					$html .= '</select>';
					
					$html = '<input type="hidden" size="10" maxlength="127" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $firstValue, false) . ' />'
					.$html;
					
					$visibility = count($element["options"]) ? 'hidden' : 'visible';
					$html .='<input onkeydown="document.getElementById(\''.$element["id"].'\').value =this.value;" style=\'visibility:'.$visibility.';\' type="text" size="10" maxlength="127" class="frmInput" name="" id="__idTxtTmp_'.$element["id"].'" '
					.' onkeyup="document.getElementById(\''.$element["id"].'\').value =this.value;" />';					
					break;
				case 'hidden':
					$html .='<input type="hidden" size="10" maxlength="127" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' />';
				break;
				case 'calendar':
					$element["value"] = empty($element["value"]) ? date("Y-m-d") : $element["value"];
					$onUpdate = empty($element["onUpdate"]) ? '' : ', onUpdate	   :' . $element["onUpdate"];
					$html .= '<input type="text" readonly="yes" size="10" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' /><button type="reset" id="f_trigger_b_'.$element["id"].'">...</button>
					<script type="text/javascript">
					    Calendar.setup({
					        inputField     :    "'.$element["id"].'",      // id of the input field
					        ifFormat       :    "%Y-%m-%d",       // format of the input field
					        showsTime      :    false,            // will display a time selector
					        button         :    "f_trigger_b_'.$element["id"].'",   // trigger for the calendar (button ID)
					        singleClick    :    true,           // double-click mode
					        step           :    1 ' . $onUpdate . '                // show all years in drop-down boxes (instead of every other year as default)
					    });
					</script>';
				break;
				case 'float':
					$element["params"] = empty($element["params"]) ? 'cols="40" rows="6" class="frmInput"' : $element["params"]; 
					$value_int = intval($element["value"]);
					$value_cent = round($element["value"] - $value_int,2) * 100;
					 
					$docE = "document.getElementById('" . $element["id"] . "').value";
					$docE2 = "document.getElementById('" . $element["id"]. "_int').value";
					$docE3 = "document.getElementById('" . $element["id"]. "_cent').value";
					$html .= $commentsBefore . '<input maxlength="6" onkeyUp="' . $docE . '=' . $docE2 . ' + \'.\' + ' . $docE3 . ';" type="text" size="10" class="frmInput" ' . self::printNameIdAndValue( $element["name"] . '_int', $element["id"]. '_int', $value_int, false) . ' '.$style.' />';
					
					$html .= ' . <input maxlength="2" size="3" onkeyUp="' . $docE . '=' . $docE2 . ' + \'.\' + ' . $docE3 . ';" type="text" class="frmInput" ' . self::printNameIdAndValue( $element["name"] . '_cent', $element["id"]. '_cent', $value_cent, false) . ' '.$style.' />';
					
					$html .= '<input type="hidden" size="30" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' />';
				break;
				
				case 'radio':
					$i = 0;
					foreach( $element["options"] as $key=> $option){
						$selected = $key == $element["value"] ? 'checked="checked"' : '';
						$html .= '<input type="radio" name="' . $element["name"] . '" id="'.$element["id"].'_'.$i++.'"  value="'.$key.'"' . $selected. '>'.$option.' ';
					}
				break;
				
				case 'textarea':
					$element["params"] = empty($element["params"]) ? 'cols="40" rows="6" class="frmInput"' : $element["params"]; 
					$html .= '<textarea name="'.$element["name"].'" id="'.$element["id"].'" ' . $element["params"] . ' >'.$element["value"].'</textarea>';
				break;
				
				case 'file':
					$html .= '<input type="file" class="frmInput" name="'.$element["id"].'" id="'.$element["id"].'" >';
					$html .= '<input type="hidden" name="'.$element["name"].'" value="'.$element["value"].'">';
					$html .= ( $element["value"] > 0 ) ? " <a href='../file.php?id=".$element["value"]."' target='_blank'>Ver archivo</a>" : '';
				break;
				
				case 'array_files':
					$hideDes = is_null($element['hide_description']) ? false : $element['hide_description'];
					
					$imageHandler = new ProjectLibrary_SDO_Core_Application_ImageHandler($element["id"], empty($element['max_files']) ? 1 : $element['max_files'] );
					$imageHandler->setArrImages( $element["value"] );
					$html .= $imageHandler->createBOImages(null,null,$hideDes,true);					
					if($errors)
						$html = str_replace('{ERROR}', $errors->getHtmlError( "file_{$element["id"]}_0", false ), $html);
					else 
						$html = str_replace('{ERROR}', '', $html);
				break;
				
				case 'array_ids':
					$imageHandler = new ProjectLibrary_SDO_Core_Application_IdsHandler($element["id"], empty($element['max_files']) ? 1 : $element['max_files']);
					$imageHandler->setArrIds( $element["value"] );
					$imageHandler->setArrOptions( $element["options"] );
					$html .= $imageHandler->createBOImages( $element["title2"] );
				break;
				
				case 'editorhtml':
					//SE NECESITA QUE LA CARPETA tiny_mce ESTE DENTRO DE LA CARPETA BACKOFFICE
					$html .= '<script type="text/javascript">'
							.' tinyMCE.init({'
							.' mode : "exact",'
							//: "textareas",'
							.' elements : "' . $element["name"] . '",'
							.' theme : "advanced",'
							.' theme_advanced_buttons1 : "bold,italic,underline,separator,strikethrough,undo,redo,link,unlink,fontsizeselect",'
							.' theme_advanced_buttons2 : "",'
							.' theme_advanced_buttons3 : "",'
							.' theme_advanced_buttons4 : "",'
							.' theme_advanced_resizing : true,'
							.' content_css : "../../estilos2.css"'
							.' });'
							.' </script>'
							.'<textarea name="' . $element["name"] . '" id="' . $element["id"] . '" cols="80" rows="9">' . $element["value"] . '</textarea>';
                        break;
				
				case 'checkbox':
					$html .='<input type="checkbox" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' />';
				break;
				
				case 'htmltext':
					$html .= $element["value"];
				break;
		
				
				case 'fieldset':
					
						$html .= '</dl></fieldset>';
						$html .= '<fieldset><legend>' . $element["title"] . '</legend><dl class="form">';
				break;
				
				case 'iframe':
					$element["params"] = empty($element["params"]) ? ' style="width:700px;height:500px;" scrolling="Yes" frameborder="0"' : $element["params"];
					$html .= '<iframe name="' . $element["name"] . '" src="' . $element["value"] . '" ' . $element["params"] . '></iframe>';
					
				break;
				
				case 'listExplode':
					$arrayValues = explode(',',trim($element['value']));
					$element['max'] = !empty($element['max']) && $element['max'] > 0 ? $element['max'] : 1;
					
					
					if(!empty($arrayValues[0])){
						$html .= '
						<style type="text/css">
							.listExplode_list table{background-color: #EFEFEF; text-align:center;width: 290px}
							.listExplode_list th{background-color: #CCCCCC;}
							.listExplode_list a{float: right;}
						</style>
						<div class="listExplode_list">
						<table>
							<tr><th width="50">Delete</th><th align="left">'.$element['label'].'</th></tr>';
						$i = 0;
						foreach($arrayValues as $value){
							$html .= '
							<tr>
								<td><input type="checkbox" name="element_'.$element['id'].'_delete_'.$i.'" value="1" /></td>
								<td align="left"><div>'.$value.'</div></td>
							</tr>';
							$i++;
						}
						$html .= '</table>
						</div><br />';
					}else{
						$html .= '<b>There are no values assigned to this register</b><br />';
					}
					
					$html .= '
					<style type="text/css">
						.listExplode_add{border: solid 1px #ccc;padding:5px;width:300px;}
						.listExplode_add div{width:300px;}
					</style>
					<div class="listExplode_add">
						<div id="div_cont_'.$element['id'].'_0" class="fileinputs_array_images">
							<b>New</b><br>
							<table>
								<tr>
									<td>'.$element['label'].':</td>
									<td><input type="text" name="element_'.$element['id'].'_0" /></td>
								</tr>
							</table>
							'.($element['max'] > 1 ? '<div id="div_cont_'.$element['id'].'_1" ><a href="javascript:addElement'.$element['id'].'(1)">+ Add</a></div>':'').
						'</div>
					</div>
					<input type="hidden" name="total_'.$element['id'].'" id="total_'.$element['id'].'" value="1" />
					<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
					<script type="text/javascript">
						function addElement'.$element['id'].'(num){
							div = jQuery("#div_cont_'.$element['id'].'_"+num);
							num2 = num + 1;
							html = \'<b>New</b><br><table><tr><td>'.$element['label'].':</td><td><input type="text" name="element_'.$element['id'].'_\'+num+\'" /></td></tr></table>\';
							html += (num2 < '.intval($element['max']).') ? \'<div id="div_cont_'.$element['id'].'_\'+num2+\'" ><a href="javascript:addElement'.$element['id'].'(\'+num2+\')">+ Add</a></div>\' : \'\';
							div.html(html);
							input_total = jQuery("#total_'.$element['id'].'");
							input_total.attr(\'value\',num2);
						}
					</script>';
					break;
				
				case 'colorHex':
					$htmlText .= $commentsBefore . '<input size="8" maxlength=7 type="text" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' />';
					$html .= '
					<div align="left">
					<script language="JavaScript">
						lck=0;
						function r(hval){if ( lck == 0 ){document.getElementById("'.$element["id"].'").value=hval;}}
						function l(){if (lck == 0){lck = 1;} else {lck = 0;}}
						</script>
					<table border="0" height="18">
						<tr>
							<td>'.$htmlText.'</td>
							<td height="18" bgcolor="#a8a9ac"><a href="JavaScript:l()" onmouseover="r(\'#A8A9AC\'); return true"><img src="images/col.png" border="0" alt="" width="10" height="18" /></a></td>
				
							<td height="18" bgcolor="#bdbec0"><a href="JavaScript:l()" onmouseover="r(\'#BDBEC0\'); return true"><img src="images/col.png" border="0" alt="" width="10" height="18" /></a></td>
							<td height="18" bgcolor="#d3dce3"><a href="JavaScript:l()" onmouseover="r(\'#D3DCE3\'); return true"><img src="images/col.png" border="0" alt="" width="10" height="18" /></a></td>
							<td height="18" bgcolor="#ffffff"><a href="JavaScript:l()" onmouseover="r(\'#FFFFFF\'); return true"><img src="img/col.png" border="0" alt="" width="10" height="18" /></a></td>
							<td height="18" bgcolor="#ff0000"><a href="JavaScript:l()" onmouseover="r(\'#FF0000\'); return true"><img src="img/col.png" border="0" alt="" width="10" height="18" /></a></td>
							<td height="18" bgcolor="#0000ff"><a href="JavaScript:l()" onmouseover="r(\'#0000FF\'); return true"><img src="img/col.png" border="0" alt="" width="10" height="18" /></a></td>
						</tr>
					</table>
					<!--	Paleta de colores Completa	-->		
</div><div  align="left">
					<table cellSpacing="0" cellPadding="0" width="100" border="0" >
	<tr>

		<td height="10" class="textbox_val" bgColor="#00FF00"><a href="JavaScript:l()" onMouseOver="r(\'#00FF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00FF33"><a href="JavaScript:l()" onMouseOver="r(\'#00FF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00FF66"><a href="JavaScript:l()" onMouseOver="r(\'#00FF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00FF99"><a href="JavaScript:l()" onMouseOver="r(\'#00FF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00FFCC"><a href="JavaScript:l()" onMouseOver="r(\'#00FFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00FFFF"><a href="JavaScript:l()" onMouseOver="r(\'#00FFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00CC00"><a href="JavaScript:l()" onMouseOver="r(\'#00CC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00CC66"><a href="JavaScript:l()" onMouseOver="r(\'#00CC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00CC66"><a href="JavaScript:l()" onMouseOver="r(\'#00CC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#00CC99"><a href="JavaScript:l()" onMouseOver="r(\'#00CC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00CCCC"><a href="JavaScript:l()" onMouseOver="r(\'#00CCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#00CCFF"><a href="JavaScript:l()" onMouseOver="r(\'#00CCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#009900"><a href="JavaScript:l()" onMouseOver="r(\'#009900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#009933"><a href="JavaScript:l()" onMouseOver="r(\'#009933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#009966"><a href="JavaScript:l()" onMouseOver="r(\'#009966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#009999"><a href="JavaScript:l()" onMouseOver="r(\'#009999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0099CC"><a href="JavaScript:l()" onMouseOver="r(\'#0099CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0099FF"><a href="JavaScript:l()" onMouseOver="r(\'#0099FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#33FF00"><a href="JavaScript:l()" onMouseOver="r(\'#33FF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33FF33"><a href="JavaScript:l()" onMouseOver="r(\'#33FF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33FF66"><a href="JavaScript:l()" onMouseOver="r(\'#33FF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33FF99"><a href="JavaScript:l()" onMouseOver="r(\'#33FF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33FFCC"><a href="JavaScript:l()" onMouseOver="r(\'#33FFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33FFFF"><a href="JavaScript:l()" onMouseOver="r(\'#33FFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33CC00"><a href="JavaScript:l()" onMouseOver="r(\'#33CC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#33CC33"><a href="JavaScript:l()" onMouseOver="r(\'#33CC33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33CC66"><a href="JavaScript:l()" onMouseOver="r(\'#33CC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33CC99"><a href="JavaScript:l()" onMouseOver="r(\'#33CC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33CCCC"><a href="JavaScript:l()" onMouseOver="r(\'#33CCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#33CCFF"><a href="JavaScript:l()" onMouseOver="r(\'#33CCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#339900"><a href="JavaScript:l()" onMouseOver="r(\'#339900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#339933"><a href="JavaScript:l()" onMouseOver="r(\'#339933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#339966"><a href="JavaScript:l()" onMouseOver="r(\'#339966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#339999"><a href="JavaScript:l()" onMouseOver="r(\'#339999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#3399CC"><a href="JavaScript:l()" onMouseOver="r(\'#3399CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3399FF"><a href="JavaScript:l()" onMouseOver="r(\'#3399FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#66FF00"><a href="JavaScript:l()" onMouseOver="r(\'#66FF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66FF33"><a href="JavaScript:l()" onMouseOver="r(\'#66FF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66FF66"><a href="JavaScript:l()" onMouseOver="r(\'#66FF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66FF99"><a href="JavaScript:l()" onMouseOver="r(\'#66FF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66FFCC"><a href="JavaScript:l()" onMouseOver="r(\'#66FFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#66FFFF"><a href="JavaScript:l()" onMouseOver="r(\'#66FFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CC00"><a href="JavaScript:l()" onMouseOver="r(\'#66CC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CC33"><a href="JavaScript:l()" onMouseOver="r(\'#66CC33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CC66"><a href="JavaScript:l()" onMouseOver="r(\'#66CC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CC99"><a href="JavaScript:l()" onMouseOver="r(\'#66CC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CCCC"><a href="JavaScript:l()" onMouseOver="r(\'#66CCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#66CCFF"><a href="JavaScript:l()" onMouseOver="r(\'#66CCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#669900"><a href="JavaScript:l()" onMouseOver="r(\'#669900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#669933"><a href="JavaScript:l()" onMouseOver="r(\'#669933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#669966"><a href="JavaScript:l()" onMouseOver="r(\'#669966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#669999"><a href="JavaScript:l()" onMouseOver="r(\'#669999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6699CC"><a href="JavaScript:l()" onMouseOver="r(\'#6699CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6699FF"><a href="JavaScript:l()" onMouseOver="r(\'#6699FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#99FF00"><a href="JavaScript:l()" onMouseOver="r(\'#99FF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99FF33"><a href="JavaScript:l()" onMouseOver="r(\'#99FF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99FF66"><a href="JavaScript:l()" onMouseOver="r(\'#99FF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#99FF99"><a href="JavaScript:l()" onMouseOver="r(\'#99FF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99FFCC"><a href="JavaScript:l()" onMouseOver="r(\'#99FFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99FFFF"><a href="JavaScript:l()" onMouseOver="r(\'#99FFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CC00"><a href="JavaScript:l()" onMouseOver="r(\'#99CC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CC33"><a href="JavaScript:l()" onMouseOver="r(\'#99CC33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CC66"><a href="JavaScript:l()" onMouseOver="r(\'#99CC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CC99"><a href="JavaScript:l()" onMouseOver="r(\'#99CC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CCCC"><a href="JavaScript:l()" onMouseOver="r(\'#99CCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#99CCFF"><a href="JavaScript:l()" onMouseOver="r(\'#99CCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#999900"><a href="JavaScript:l()" onMouseOver="r(\'#999900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#999933"><a href="JavaScript:l()" onMouseOver="r(\'#999933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#999966"><a href="JavaScript:l()" onMouseOver="r(\'#999966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#999999"><a href="JavaScript:l()" onMouseOver="r(\'#999999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9999CC"><a href="JavaScript:l()" onMouseOver="r(\'#9999CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9999FF"><a href="JavaScript:l()" onMouseOver="r(\'#9999FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#CCFF00"><a href="JavaScript:l()" onMouseOver="r(\'#CCFF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#CCFF33"><a href="JavaScript:l()" onMouseOver="r(\'#CCFF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCFF66"><a href="JavaScript:l()" onMouseOver="r(\'#CCFF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCFF99"><a href="JavaScript:l()" onMouseOver="r(\'#CCFF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCFFCC"><a href="JavaScript:l()" onMouseOver="r(\'#CCFFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCFFFF"><a href="JavaScript:l()" onMouseOver="r(\'#CCFFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCCC00"><a href="JavaScript:l()" onMouseOver="r(\'#CCCC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCCC33"><a href="JavaScript:l()" onMouseOver="r(\'#CCCC33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCCC66"><a href="JavaScript:l()" onMouseOver="r(\'#CCCC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCCC99"><a href="JavaScript:l()" onMouseOver="r(\'#CCCC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#CCCCCC"><a href="JavaScript:l()" onMouseOver="r(\'#CCCCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CCCCFF"><a href="JavaScript:l()" onMouseOver="r(\'#CCCCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC9900"><a href="JavaScript:l()" onMouseOver="r(\'#CC9900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC9933"><a href="JavaScript:l()" onMouseOver="r(\'#CC9933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC9966"><a href="JavaScript:l()" onMouseOver="r(\'#CC9966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC9999"><a href="JavaScript:l()" onMouseOver="r(\'#CC9999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC99CC"><a href="JavaScript:l()" onMouseOver="r(\'#CC99CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC99FF"><a href="JavaScript:l()" onMouseOver="r(\'#CC99FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>

	<tr>
		<td height="10" class="textbox_val" bgColor="#FFFF00"><a href="JavaScript:l()" onMouseOver="r(\'#FFFF00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFFF33"><a href="JavaScript:l()" onMouseOver="r(\'#FFFF33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFFF66"><a href="JavaScript:l()" onMouseOver="r(\'#FFFF66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFFF99"><a href="JavaScript:l()" onMouseOver="r(\'#FFFF99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFFFCC"><a href="JavaScript:l()" onMouseOver="r(\'#FFFFCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFFFFF"><a href="JavaScript:l()" onMouseOver="r(\'#FFFFFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFCC00"><a href="JavaScript:l()" onMouseOver="r(\'#FFCC00\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFCC33"><a href="JavaScript:l()" onMouseOver="r(\'#FFCC33\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#FFCC66"><a href="JavaScript:l()" onMouseOver="r(\'#FFCC66\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFCC99"><a href="JavaScript:l()" onMouseOver="r(\'#FFCC99\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFCCCC"><a href="JavaScript:l()" onMouseOver="r(\'#FFCCCC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FFCCFF"><a href="JavaScript:l()" onMouseOver="r(\'#FFCCFF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF9900"><a href="JavaScript:l()" onMouseOver="r(\'#FF9900\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF9933"><a href="JavaScript:l()" onMouseOver="r(\'#FF9933\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF9966"><a href="JavaScript:l()" onMouseOver="r(\'#FF9966\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF9999"><a href="JavaScript:l()" onMouseOver="r(\'#FF9999\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF99CC"><a href="JavaScript:l()" onMouseOver="r(\'#FF99CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#FF99FF"><a href="JavaScript:l()" onMouseOver="r(\'#FF99FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#006600"><a href="JavaScript:l()" onMouseOver="r(\'#006600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#006633"><a href="JavaScript:l()" onMouseOver="r(\'#006633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#006666"><a href="JavaScript:l()" onMouseOver="r(\'#006666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#006699"><a href="JavaScript:l()" onMouseOver="r(\'#006699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0066CC"><a href="JavaScript:l()" onMouseOver="r(\'#0066CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0066FF"><a href="JavaScript:l()" onMouseOver="r(\'#0066FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#003300"><a href="JavaScript:l()" onMouseOver="r(\'#003300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#003333"><a href="JavaScript:l()" onMouseOver="r(\'#003333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#003366"><a href="JavaScript:l()" onMouseOver="r(\'#003366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#003399"><a href="JavaScript:l()" onMouseOver="r(\'#003399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0033CC"><a href="JavaScript:l()" onMouseOver="r(\'#0033CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0033FF"><a href="JavaScript:l()" onMouseOver="r(\'#0033FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#000000"><a href="JavaScript:l()" onMouseOver="r(\'#000000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#000033"><a href="JavaScript:l()" onMouseOver="r(\'#000033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#000066"><a href="JavaScript:l()" onMouseOver="r(\'#000066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#000099"><a href="JavaScript:l()" onMouseOver="r(\'#000099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0000CC"><a href="JavaScript:l()" onMouseOver="r(\'#0000CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#0000FF"><a href="JavaScript:l()" onMouseOver="r(\'#0000FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#336600"><a href="JavaScript:l()" onMouseOver="r(\'#336600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#336633"><a href="JavaScript:l()" onMouseOver="r(\'#336633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#336666"><a href="JavaScript:l()" onMouseOver="r(\'#336666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#336699"><a href="JavaScript:l()" onMouseOver="r(\'#336699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#3366CC"><a href="JavaScript:l()" onMouseOver="r(\'#3366CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3366FF"><a href="JavaScript:l()" onMouseOver="r(\'#3366FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#333300"><a href="JavaScript:l()" onMouseOver="r(\'#333300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#333333"><a href="JavaScript:l()" onMouseOver="r(\'#333333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#333366"><a href="JavaScript:l()" onMouseOver="r(\'#333366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#333399"><a href="JavaScript:l()" onMouseOver="r(\'#333399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3333CC"><a href="JavaScript:l()" onMouseOver="r(\'#3333CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3333FF"><a href="JavaScript:l()" onMouseOver="r(\'#3333FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#330000"><a href="JavaScript:l()" onMouseOver="r(\'#330000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#330033"><a href="JavaScript:l()" onMouseOver="r(\'#330033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#330066"><a href="JavaScript:l()" onMouseOver="r(\'#330066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#330099"><a href="JavaScript:l()" onMouseOver="r(\'#330099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3300CC"><a href="JavaScript:l()" onMouseOver="r(\'#3300CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#3300FF"><a href="JavaScript:l()" onMouseOver="r(\'#3300FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#666600"><a href="JavaScript:l()" onMouseOver="r(\'#666600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#666633"><a href="JavaScript:l()" onMouseOver="r(\'#666633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#666666"><a href="JavaScript:l()" onMouseOver="r(\'#666666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#666699"><a href="JavaScript:l()" onMouseOver="r(\'#666699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6666CC"><a href="JavaScript:l()" onMouseOver="r(\'#6666CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6666FF"><a href="JavaScript:l()" onMouseOver="r(\'#6666FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#663300"><a href="JavaScript:l()" onMouseOver="r(\'#663300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#663333"><a href="JavaScript:l()" onMouseOver="r(\'#663333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#663366"><a href="JavaScript:l()" onMouseOver="r(\'#663366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#663399"><a href="JavaScript:l()" onMouseOver="r(\'#663399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6633CC"><a href="JavaScript:l()" onMouseOver="r(\'#6633CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#6633FF"><a href="JavaScript:l()" onMouseOver="r(\'#6633FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#660000"><a href="JavaScript:l()" onMouseOver="r(\'#660000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#660033"><a href="JavaScript:l()" onMouseOver="r(\'#660033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#660066"><a href="JavaScript:l()" onMouseOver="r(\'#660066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#660099"><a href="JavaScript:l()" onMouseOver="r(\'#660099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6600CC"><a href="JavaScript:l()" onMouseOver="r(\'#6600CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#6600FF"><a href="JavaScript:l()" onMouseOver="r(\'#6600FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>

		<td height="10" class="textbox_val" bgColor="#996600"><a href="JavaScript:l()" onMouseOver="r(\'#996600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#996633"><a href="JavaScript:l()" onMouseOver="r(\'#996633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#996666"><a href="JavaScript:l()" onMouseOver="r(\'#996666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#996699"><a href="JavaScript:l()" onMouseOver="r(\'#996699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9966CC"><a href="JavaScript:l()" onMouseOver="r(\'#9966CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9966FF"><a href="JavaScript:l()" onMouseOver="r(\'#9966FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#993300"><a href="JavaScript:l()" onMouseOver="r(\'#993300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#993333"><a href="JavaScript:l()" onMouseOver="r(\'#993333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#993366"><a href="JavaScript:l()" onMouseOver="r(\'#993366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#993399"><a href="JavaScript:l()" onMouseOver="r(\'#993399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9933CC"><a href="JavaScript:l()" onMouseOver="r(\'#9933CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9933FF"><a href="JavaScript:l()" onMouseOver="r(\'#9933FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#990000"><a href="JavaScript:l()" onMouseOver="r(\'#990000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#990033"><a href="JavaScript:l()" onMouseOver="r(\'#990033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#990066"><a href="JavaScript:l()" onMouseOver="r(\'#990066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#990099"><a href="JavaScript:l()" onMouseOver="r(\'#990099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9900CC"><a href="JavaScript:l()" onMouseOver="r(\'#9900CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#9900FF"><a href="JavaScript:l()" onMouseOver="r(\'#9900FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#CC6600"><a href="JavaScript:l()" onMouseOver="r(\'#CC6600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC6633"><a href="JavaScript:l()" onMouseOver="r(\'#CC6633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC6666"><a href="JavaScript:l()" onMouseOver="r(\'#CC6666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC6699"><a href="JavaScript:l()" onMouseOver="r(\'#CC6699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC66CC"><a href="JavaScript:l()" onMouseOver="r(\'#CC66CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC66FF"><a href="JavaScript:l()" onMouseOver="r(\'#CC66FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC3300"><a href="JavaScript:l()" onMouseOver="r(\'#CC3300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#CC3333"><a href="JavaScript:l()" onMouseOver="r(\'#CC3333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC3366"><a href="JavaScript:l()" onMouseOver="r(\'#CC3366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC3399"><a href="JavaScript:l()" onMouseOver="r(\'#CC3399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC33CC"><a href="JavaScript:l()" onMouseOver="r(\'#CC33CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC33FF"><a href="JavaScript:l()" onMouseOver="r(\'#CC33FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC0000"><a href="JavaScript:l()" onMouseOver="r(\'#CC0000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC0033"><a href="JavaScript:l()" onMouseOver="r(\'#CC0033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC0066"><a href="JavaScript:l()" onMouseOver="r(\'#CC0066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC0099"><a href="JavaScript:l()" onMouseOver="r(\'#CC0099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#CC00CC"><a href="JavaScript:l()" onMouseOver="r(\'#CC00CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#CC00FF"><a href="JavaScript:l()" onMouseOver="r(\'#CC00FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
	<tr>
		<td height="10" class="textbox_val" bgColor="#FF6600"><a href="JavaScript:l()" onMouseOver="r(\'#FF6600\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF6633"><a href="JavaScript:l()" onMouseOver="r(\'#FF6633\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF6666"><a href="JavaScript:l()" onMouseOver="r(\'#FF6666\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF6699"><a href="JavaScript:l()" onMouseOver="r(\'#FF6699\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF66CC"><a href="JavaScript:l()" onMouseOver="r(\'#FF66CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#FF66FF"><a href="JavaScript:l()" onMouseOver="r(\'#FF66FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF3300"><a href="JavaScript:l()" onMouseOver="r(\'#FF3300\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF3333"><a href="JavaScript:l()" onMouseOver="r(\'#FF3333\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF3366"><a href="JavaScript:l()" onMouseOver="r(\'#FF3366\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF3399"><a href="JavaScript:l()" onMouseOver="r(\'#FF3399\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF33CC"><a href="JavaScript:l()" onMouseOver="r(\'#FF33CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF33FF"><a href="JavaScript:l()" onMouseOver="r(\'#FF33FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF0000"><a href="JavaScript:l()" onMouseOver="r(\'#FF0000\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF0033"><a href="JavaScript:l()" onMouseOver="r(\'#FF0033\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>

		<td height="10" class="textbox_val" bgColor="#FF0066"><a href="JavaScript:l()" onMouseOver="r(\'#FF0066\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF0099"><a href="JavaScript:l()" onMouseOver="r(\'#FF0099\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF00CC"><a href="JavaScript:l()" onMouseOver="r(\'#FF00CC\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
		<td height="10" class="textbox_val" bgColor="#FF00FF"><a href="JavaScript:l()" onMouseOver="r(\'#FF00FF\'); return true"><img src="img/col.png" height=10 width=10 border=0></a></td>
	</tr>
</table>
<!--	END Paleta de colores Completa	-->
</div>
					';
				break;
				
				case 'google_mapper':
					$use_viewer = isset($element["use_viewer"]) ? $element["use_viewer"] : true;
					$w = 100;
					$viewer_zoom = $element["viewer_zoom"];
					$name_viewer_zoom = $element["name_viewer_zoom"];
					$id_viewer_zoom = $element["id_viewer_zoom"];
					
					$viewer_pitch = $element["viewer_pitch"];
					$name_viewer_pitch = $element["name_viewer_pitch"];
					$id_viewer_pitch = $element["id_viewer_pitch"];
					
					$viewer_yaw = $element["viewer_yaw"];
					$name_viewer_yaw = $element["name_viewer_yaw"];
					$id_viewer_yaw = $element["id_viewer_yaw"];
					
					
					
					$zoom = $element["zoom"];
					$name_zoom = $element["name_zoom"];
					$id_zoom = $element["id_zoom"];
					
					$lat = $element["lat"];
					$name_lat = $element["name_lat"];
					$id_lat = $element["id_lat"];
					
					$lng = $element["lng"];
					$name_lng = $element["name_lng"];
					$id_lng = $element["id_lng"];
					
					$html .="<script type='text/javascript'>
					    function launchGoogleMapper(){
					      lat = document.getElementById('" . $id_lat . "').value;
					      lng = document.getElementById('" . $id_lng . "').value;
					      zoom = document.getElementById('" . $id_zoom . "').value;
					      " . ( $use_viewer ?
					      "viewer_pitch = document.getElementById('" . $id_viewer_pitch . "').value;
					      viewer_yaw = document.getElementById('" . $id_viewer_yaw . "').value;
					      viewer_zoom = document.getElementById('" . $id_viewer_zoom . "').value;"
					       : 'viewer_pitch =""; viewer_yaw =""; viewer_zoom ="";' )
					      . "
					      url = '" .($use_viewer ? "google_mapper.php" : "google_mapper_sin_viewer.php" ). "?lat=' + lat + '&lng=' + lng + '&zoom=' + zoom + '&viewer_pitch=' + viewer_pitch + '&viewer_zoom=' + viewer_zoom + '&viewer_yaw=' + viewer_yaw;
					      name = '_blank';
					      attr = 'width=735,dependent=yes';
					      window.open( url, name, attr );
					    }
					    
					    </script>
					    ";
					$html .= '
					<table border="0" cellpadding="0" cellspacing="5" class="frmTable" width="" >
  <tr class="frmRow">
    <td class="frmLabelCell" width="' . $w . '">Herramienta:</td>
    <td class="frmInputCell"><input type="button" class="frmButton" value="Abrir Google Mapper" onClick="launchGoogleMapper()" >
    </td>
  </tr>
  <tr class="frmRow">
    <td class="frmLabelCell"> Latitud: </td>
    <td class="frmInputCell"><input type="text" size="30" maxlength="250" class="frmInput" name="'.$name_lat.'" id="'.$id_lat.'" value="'. $lat .'"  />
    </td>
  </tr>
  
  <tr class="frmRow">
    <td class="frmLabelCell"> Longitud: </td>
    <td class="frmInputCell"><input type="text" size="30" maxlength="250" class="frmInput" name="'.$name_lng.'" id="'.$id_lng.'" value="'.$lng.'" />
    </td>
  </tr>
  <tr class="frmRow">
    <td class="frmLabelCell"> Elevaci&oacute;n: </td>
    <td class="frmInputCell"><input type="text" size="5" maxlength="250" class="frmInput" name="' . $name_zoom . '" id="' .$id_zoom .'" value="' .$zoom .'" />
    ' . ( $use_viewer ?
    ' <input type="hidden" size="5" maxlength="250" class="frmInput" name="' . $name_viewer_pitch . '" id="' .$id_viewer_pitch .'" value="' .$viewer_pitch .'" />
    <input type="hidden" size="5" maxlength="250" class="frmInput" name="' . $name_viewer_yaw . '" id="' .$id_viewer_yaw .'" value="' .$viewer_yaw .'" />
    <input type="hidden" size="5" maxlength="250" class="frmInput" name="' . $name_viewer_zoom . '" id="' .$id_viewer_zoom .'" value="' .$viewer_zoom .'" />
    ' : '' ) .
    '
    </td>
  </tr>
</table>
					';
					break;
				default:
					$params = $element["params"];
					$html .= $commentsBefore . '<input type="text" size="30" class="frmInput" ' . self::printNameIdAndValue( $element["name"], $element["id"], $element["value"], false) . ' '.$style.' '.$params.' />';
				break;
			}
			return $html;
	}
	public static function dataToInput( $value ){
		return htmlentities( (string)$value, ENT_QUOTES, CHARSET_PROJECT );
	}

	public static function printNameIdAndValue( $name, $id, $value, $echo = true ){
		$txtOutPut = 'name="'. $name .'" id="' . $id . '" value="' . self::dataToInput( $value ) . '"';
		if( $echo ){
			echo $txtOutPut;
		}
		else{
			return $txtOutPut;
		}
	}
	
	
	function foPrintPager($result, $formName ='', $classResultPages='', $classActualPage ='', $elementsForGroups =5, $hiddenFields = array(), $htmlPrev ='', $hrefByPage = ''){
		$totalPages = $result->getTotalPages();
		$actualPage = $result->getPage();
		
		$p_param = '{p}';
			//$nextPage = ( $actualPage < $totalPages ) ? $actualPage+1 : $actualPage;
			$lastPage = ( $actualPage > 1 ) ? $actualPage-1 : 1;
			
			$htmlLinksGroups .= 
			'<form action="" name="'.$formName.'" class="class' . $formName . '" style="margin:0px;">';
			
			$htmlLinksGroups .= $htmlPrev ;
			
			if( $totalPages > 1 ){

				if( ($actualPage + $elementsForGroups) >= $totalPages){
					$initialPage = ($totalPages - $elementsForGroups > 0) ? ($totalPages - $elementsForGroups) : 1;
				}else{
					$initialPage = (int)($actualPage / $elementsForGroups);
					
					$initialPage = ( $actualPage % $elementsForGroups == 0) ? $initialPage -1 : $initialPage;
		
					$initialPage = ($elementsForGroups * $initialPage) + 1;
				}
				
				$finalPage = $initialPage + $elementsForGroups;
				$finalPage = ($totalPages > $finalPage) ? $finalPage : $totalPages;
				
				
				
				$href = empty($hrefByPage) ? "javascript:setParam('".$formName."','p','{$lastPage}');" : str_replace($p_param,$lastPage,$hrefByPage);
				
				$htmlLinksGroups .= $actualPage <= 1 ? '' : "<a href=\"" . $href . "\" class='".$classResultPages."' >&#9664;</a>&nbsp;&nbsp;";
				
				for($i=$initialPage; $i < $finalPage; $i++){
					$class = ( $i == $actualPage ) ? $classActualPage : $classResultPages;
					
					$href = empty($hrefByPage) ? "javascript:setParam('".$formName."','p','{$i}');" : str_replace($p_param,$i,$hrefByPage);
					$htmlLinksGroups .= "&nbsp;<a href=\"" . $href . "\" class='{$class}' >{$i}</a>&nbsp;&nbsp;";
				}
				
				$class = ( $totalPages == $actualPage ) ? $classActualPage : $classResultPages;
				
				
				
				if( $finalPage < ($totalPages-1)  ){
					$finalPage = "... ";
				}else{
					$finalPage = "";
				}
				$class = ( $totalPages == $actualPage ) ? $classActualPage : $classResultPages;
				
				$href = empty($hrefByPage) ? "javascript:setParam('$formName','p','{$totalPages}');" : str_replace($p_param,$totalPages,$hrefByPage);
				
				$finalPage .= "<a href=\"".$href."\" class='".$class."' >{$totalPages}</a>";
				
				$htmlLinksGroups .= $finalPage;
				
					$href = empty($hrefByPage) ? "javascript:setParam('".$formName."','p','" . ($actualPage + 1) . "');" : str_replace($p_param,($actualPage + 1),$hrefByPage);
				$htmlLinksGroups .= $actualPage >= $totalPages ? '' : "&nbsp;&nbsp;<a href=\"" . $href . "\" class='".$classResultPages."' >&#9658;</a>";
			}
			else{
				$htmlLinksGroups .= "<span class='".$classActualPage."' >1</span>";
			}
			$querySearch = $result->getKeywords();
			$querySearch = empty($querySearch) ? '' : $querySearch;
			$htmlLinksGroups .= 
			'<input type="hidden" name="p" />
			<input type="hidden" name="o"" />
			<input type="hidden" name="q" value="' . $querySearch . '" />';
			
			foreach($hiddenFields as $key => $hiddenField){
				$htmlLinksGroups .='<input type="hidden" ' . self::printNameIdAndValue( $key, $key, $hiddenField, false) . ' />';
			}
			
			$htmlLinksGroups .='</form>';
			
			if(empty($hrefByPage)){
			$htmlLinksGroups .='
			<script language="javascript"> 
				function setParam( formName, fieldName, value, submit ){   
					if ( submit == null ) {     submit = true;   }   
					var form = document.forms[formName];   
					form.elements[fieldName].value = value;   
					if ( submit ) {   	form.submit();   } 
				}
			</script>';
			}
			echo $htmlLinksGroups;
	}
}
?>