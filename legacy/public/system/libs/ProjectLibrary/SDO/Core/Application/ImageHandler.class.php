<?php
/**
 * This class provides access to manipulation of Users in the System.
 * 
 */	  
class ProjectLibrary_SDO_Core_Application_ImageHandler extends ProjectLibrary_SDO_Core_Application{
	protected $imageHandler;
	
	protected $validTypes2Upload;
	protected $maximum;
	protected $uploadFileDirectory;
	protected $uploadVersionDirectory;
	protected $uploadFileDescription;
	
	protected $accessType;
	
	protected $arrayVersions;
	
	function __construct( $name, $maximum = 20 ){
		
		parent::__construct();
		$this->imageHandler = new ProjectLibrary_Entity_ImageHandler();
		$this->imageHandler->setName( $name );
		$this->maximum = $maximum;
		$this->uploadFileDirectory = 'files/';
		$this->uploadVersionDirectory = 'multimedia/';
		$this->uploadFileDescription = 'Description File';
		$this->accessType = 'public';
		
		$this->validTypes2Upload = array();
	}

	
//------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------
	public function setArrImages( $arrImages ){
		$this->imageHandler->setArrayImages( $arrImages );
	}

	public function createBOImages( $title ='Nombre del Archivo',$hideOrdenDeAparicion = false, $hideDescription = false,$params){
		$savant = new ProjectLibrary_Savant();
		
		$arrImages = $this->imageHandler->getArrayImages();
		$html = '';
		$ban = false;
		
		if( $arrImages > 0){
			$i=0;
			
			$html = '<table border="0" bgcolor="#EFEFEF" width="100%">
			<tr valign="top" bgcolor="#CCCCCC"><th width="10%">'.$savant->trans('delete').'</th>' . ($hideOrdenDeAparicion ? '' : ('<th width="20%">'.$savant->trans('appearance_order').'</th>') ). ' <th width="40%" align="left">' . $title . '</th>' . ( $hideDescription ? '' : '<th width="30%" align="left">'.$savant->trans('description').'</th>' ) . '</tr>';

			foreach ( $arrImages as $image){			    
				$imageInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $image );				
				if( $imageInfo->getId() > 0){									
					$i++;
					$html .= '<tr>
					<td align="center">' . ($hideOrdenDeAparicion ? ('<input type="hidden" size="3" name="InputOrder_' . $image . '_' . $this->imageHandler->getName() . '" value="' . $i . '">') : '') . '
					<input onChange="updateContador2_'.$this->imageHandler->getName().'( this.checked )" type="checkbox" name="checkBoxDelete_' . $image . '_' . $this->imageHandler->getName() . '" value="1"></td>' .
					($hideOrdenDeAparicion ? '' : ('<td align="center"><input type="text" size="3" name="InputOrder_' . $image . '_' . $this->imageHandler->getName() . '" value="' . $i . '"></td>') ) .
					'<td align="left"><a href="'.ABS_HTTP_URL.SYSTEM_DIRECTORY.'file.php?id=' . $image . '&type='.$imageInfo->getType().'" target="_blank"> ' . $imageInfo->getName() . '</a></td>' .
					( $hideDescription ? '' : '<td align="left"><input type="text"  name="descripcion['.$image.'][]" value="' . $imageInfo->getDescription(). '"></td>' ) .
					'</tr>';
					$ban = true;
					
				}
			}
			$html .= '</table>';
		}
		
		if(!$ban){
			$html = '<br /><b>'.$savant->trans('no_files_assigned_to_register').'</b>';
		}
		
		$nActualImages = !empty($arrImages)?($arrImages[0] == '' ? 0 : count($arrImages)):0;
		$html .= '<br /><div style="border: solid 1px #ccc;padding:5px;" >';
		$html .= '<div class="fileinputs_' . $this->imageHandler->getName().'" id="fileinputs_' . $this->imageHandler->getName().'" style="padding:5px;" >';
		
		$nameInputFile = 'file_' . $this->imageHandler->getName() ;
		$nameInputDescription = $this->GetDescriptionField();
		
		
		$html .= '</div>';
		$html .= '<a href="javascript:newFile_'.$this->imageHandler->getName().'();" class="btn btn-success"> + Agregar Archivo</a>';
		$html .= '</div>';
		
		$html .= '
		<input type="hidden" name="nfiles_'.$this->imageHandler->getName().'" id="nfiles_'.$this->imageHandler->getName().'" value="0">
		<script language="Javascript">';
		
		
		$html .= '
			contador_'.$this->imageHandler->getName().' = 0;
			contador2_'.$this->imageHandler->getName().' = '.$nActualImages.';
			
			function updateContador2_'.$this->imageHandler->getName().'( isChecked ){
				if( isChecked ){
					contador2_'.$this->imageHandler->getName().'--;
					newFile_'.$this->imageHandler->getName().'();
				}else{
					contador2_'.$this->imageHandler->getName().'++;
					alert("'.$savant->trans('the').' '.$savant->trans('max_number_of_files').' '.$savant->trans('in').' '.$savant->trans('this').' '.$savant->trans('field').' '.$savant->trans('is').': '.$this->maximum.'. \\nLos Archivos que intente cargar adicionalmente no se subiran al servidor.");
				}
			}
			
			function newFile_'.$this->imageHandler->getName().'(){
				content = document.getElementById("fileinputs_' . $this->imageHandler->getName().'");
				nfiles = document.getElementById("nfiles_'.$this->imageHandler->getName().'");
				
				if( (contador_'.$this->imageHandler->getName().'+ contador2_'.$this->imageHandler->getName().' + 1) <= '.$this->maximum.'){
					
					content.innerHTML = content.innerHTML + \'<b>'.$savant->trans('new').' '.$savant->trans('file').'</b><br /><table>\'';
		
				if(!$hideDescription){
					$html .= '
					+ \'<tr><td>'.$savant->trans('description').': </td><td>\'
					+ \'<input type="text" name="' . $nameInputDescription . '_\' + contador_'.$this->imageHandler->getName().' + \'" class="form-control"></td></tr>\'
					';
				}
					
					$html .= '
					+ \'<tr><td>'.$savant->trans('file').': </td><td>\'
					+ \'<input type="file" name="' . $nameInputFile . '_\' + contador_'.$this->imageHandler->getName().' + \'" class="form-control" '.$params.'></td></tr></table>\' ;
					
					nfiles.value = contador_'.$this->imageHandler->getName().' + 1;
					contador_'.$this->imageHandler->getName().'++;
				}else{
					alert("'.$savant->trans('the').' '.$savant->trans('max_number_of_files').' '.$savant->trans('in').' '.$savant->trans('this').' '.$savant->trans('field').' '.$savant->trans('is').': '.$this->maximum.'");
				}
			}';
		
		if( count($arrImages) < $this->maximum || $arrImages[0]==''){
			$html .= 'newFile_'.$this->imageHandler->getName().'();';
		}
		$html .='</script>';
		
		return $html;
	}
	
	public function GetDescriptionField(){
		return '_description_' . $this->imageHandler->getName() ;
	}
	
	public function proccessImages( $formatString = true ){
		$arrImagesReturn = array();
		$arrImages = $this->imageHandler->getArrayImages();
		if( $arrImages > 0){
			$arrImagesReturn2 = array();
			foreach ( $arrImages as $image){
				$imageInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $image );
				if( $imageInfo->getId() > 0 && $_REQUEST[ 'checkBoxDelete_' . $image . '_' . $this->imageHandler->getName() ] != '1' ){
					$order = (!isset($_REQUEST[ 'InputOrder_' . $image . '_' . $this->imageHandler->getName() ]) ||
							 !is_numeric($_REQUEST[ 'InputOrder_' . $image . '_' . $this->imageHandler->getName() ]) ) ? 0 :
							 $_REQUEST[ 'InputOrder_' . $image . '_' . $this->imageHandler->getName() ];
					$arrImagesReturn2[ $image ] = $order;
				}
			}
			asort($arrImagesReturn2);
			if( count($arrImagesReturn2) > 0){
				$arrImagesReturn = array_keys($arrImagesReturn2);
			}
			$arrImages = $arrImagesReturn;
		}

		$imageIds = array(0);
		if( count($arrImages) < $this->maximum || $arrImages[0]==''){
			if( empty($this->validTypes2Upload ) ){
				$imageIds = $this->uploadImage();
			}else{
				$imageIds = $this->uploadFile( count($arrImages) );
			}
			//$imageId = $this->uploadWord();
		}

		foreach($imageIds as $imageId){
			if( !in_array($imageId, $arrImagesReturn) && $imageId > 0){
				$arrImagesReturn[] = $imageId;
			}
		}
		return ($formatString) ? join(',',$arrImagesReturn) : $arrImagesReturn;
	}
	
	public function setValidTypes( array $types){
		$this->validTypes2Upload = $types;
	}
	
	public function uploadFile( $nActualFiles ){
		$arrIds = array();
		$nFiles = 'nfiles_' . $this->imageHandler->getName() ;
		$nFiles = empty($_REQUEST[$nFiles]) || !is_numeric($_REQUEST[$nFiles]) ? 0 : $_REQUEST[$nFiles];

		for( $i = 0; $i < $nFiles; $i++){
			if( $nActualFiles < $this->maximum ){
				$fileName = 'file_' . $this->imageHandler->getName() .'_' . $i;
				$nameInputDescription = $_REQUEST[ $this->GetDescriptionField().'_' . $i ] ;
				$nameInputDescription = empty($nameInputDescription) ? '' : $nameInputDescription;
				if( !empty($_FILES[ $fileName ]["tmp_name"]) ){
					$imageFile = new ProjectLibrary_Entity_Util_FileUpload( $fileName );
					$imageFile = ProjectLibrary_SDO_Core_Application_FileManagement::SaveFileUploadDifferentTypes( $imageFile, $nameInputDescription, $this->uploadFileDirectory,$this->accessType, $this->validTypes2Upload, $this->arrayVersions, $this->uploadVersionDirectory  );
					$arrIds[] = $imageFile->getId();
					
					$nActualFiles++;
				}
			}else{
				break;
			}
		}
		
		return $arrIds;
	}

	public function setAccessType( $accessType ){
		$this->accessType = $accessType;
	}
	
	public function uploadWord(){
		$fileName = 'file_' . $this->imageHandler->getName() ;
		if( !empty($_FILES[ $fileName ]["tmp_name"]) ){
			$imageFile = new ProjectLibrary_Entity_Util_FileUpload( $fileName );
			$imageFile = ProjectLibrary_SDO_Core_Application_FileManagement::SaveWordUpload( $imageFile, $this->uploadFileDescription, $this->uploadFileDirectory  );
			return $imageFile->getId();
		}
	}
	
	public function uploadImage(){
		$fileName = 'file_' . $this->imageHandler->getName() ;
		if( !empty($_FILES[ $fileName ]["tmp_name"]) ){
			$imageFile = new ProjectLibrary_Entity_Util_FileUpload( $fileName );
			$imageFile = ProjectLibrary_SDO_Core_Application_FileManagement::SaveImageUpload( $imageFile, $this->uploadFileDescription, $this->uploadFileDirectory  );
			return $imageFile->getId();
		}
	}
	
	public function setUploadFileDirectory( $directory ){
		$this->uploadFileDirectory = $directory;
	}
	
	public function setUploadVersionDirectory( $directory ){
		$this->uploadVersionDirectory = $directory;
	}
	
	public function setUploadFileDescription( $description ){
		$this->uploadFileDescription = $description;
	}
	
	public function setMaximum( $max ){
		$this->maximum = $max;
	}
	
	public function setArrVersions( $arrVersions){
		$this->arrayVersions = $arrVersions;
	}
	
	public function printJSLibs($inBackoffice = ''){
	
	echo '
<script type="text/javascript" src="'. ABS_HTTP_PATH . SYSTEM_DIRECTORY . $inBackoffice.'js/jsLight/jquery.js"></script>
<script type="text/javascript" src="'. ABS_HTTP_PATH . SYSTEM_DIRECTORY . $inBackoffice.'js/jsLight/ui.js"></script>
<link rel="stylesheet" type="text/css" href="'. ABS_HTTP_PATH . SYSTEM_DIRECTORY . $inBackoffice.'js/jsLight/jquery-lightbox.css" media="screen,projection">
<script type="text/javascript">
  // <![CDATA[
$(document).ready(function() {
                $("a[@rel*=jquery-lightbox]").lightBox({
                    overlayOpacity: 0.6,
                    imageBlank: "'.ABS_HTTP_PATH . SYSTEM_DIRECTORY . 'js/jsLight/lightbox-blank.gif",
                    imageLoading: "'.ABS_HTTP_PATH . SYSTEM_DIRECTORY . 'js/jsLight/lightbox-ico-loading.gif",
                    imageBtnClose: "'.ABS_HTTP_PATH . SYSTEM_DIRECTORY . 'js/jsLight/close.png",
                    imageBtnPrev: "'.ABS_HTTP_PATH . SYSTEM_DIRECTORY . 'js/jsLight/goleft.png",
                    imageBtnNext: "'.ABS_HTTP_PATH . SYSTEM_DIRECTORY . 'js/jsLight/goright.png",
                    containerResizeSpeed: 350
                });

                var etiquette_box = $("#addons-display-review-etiquette").hide();
                $("#short-review").focus(function() { etiquette_box.show("fast"); } );
            });

            // This function toggles an element\'s text between two values
            jQuery.fn.textToggle = function(text1, text2) {
                jQuery(this).text( ( jQuery(this).text() == text1 ? text2 : text1 ) );
            };

          // ]]>
          </script>  <script type="text/javascript">
  // <![CDATA[

    $(document).ready(function() {
        $(".hidden").hide(); // hide anything that should be hidden
        $("#other-apps").addClass("collapsed js"); // collapse other apps menu

        var q = $("#query");
        var l = $("#search-query label");
        l.show();
        if ( q.val() == "search for add-ons"){ //initially q is set to search add-ons text for javascriptless browsing
		  q.val(\'\');
		}
        if ( q.val() != "") { // if field has any value...
            l.hide(); // hide the label
        };
        l.click(function() { // for browsers with unclickable labels
            q.focus();
        });
        q.focus(function() { // when field gains focus...
            l.hide(); // hide the label
        });
        q.blur(function() { // when field loses focus...
            if ( q.val() == "" ) { // if field is empty...
                l.show(); // show the label again, else do nothing (label remains hidden)
            };
        });
        
        // JS for toggling advanced versus normal search.
        var adv = $("#advanced-search");
        var advLink = $("#advanced-search-toggle a");
	      	advLink.isHidden = true;
        $(\'#advanced-search-toggle-link\').attr(\'href\', \'#\');   // for ie6-7				
        advLink.click(function() {           
            if(advLink.isHidden == true) {
               adv.appendTo("#search-form");
               advLink.addClass("asopen");
               advLink.removeClass("asclosed");
               advLink.isHidden = false;
            } else {
               adv.appendTo("#hidden-form");
               advLink.addClass("asclosed");
               advLink.removeClass("asopen");
               advLink.isHidden = true;
            }
            return false;
        }); 

        //JS for making sure advanced search type and normal search category don\'t conflict
        var cat = $("#category");								
        cat.change(function() {
	           atyp = document.getElementById(\'atype\');							
            atyp.selectedIndex = 0;								
        });								

        var addontype = $("#atype");								
        addontype.change(function() {
	           ctgry = document.getElementById(\'category\');							
            ctgry.selectedIndex = 0;								
        });
																        
        					
				

        $("#other-apps h3").click(function() {
            $("#other-apps").toggleClass("collapsed");
            $(this).blur();
            $(document).click(function(e) {
                // Prevent weird delay when clicking on the links
                var node = e.target;
                while (node && !node.id) {
                    node = node.offsetParent;
                }
                
                if (!node || node.id != \'other-apps\') {
                    $("#other-apps").addClass("collapsed");
                }
            });
            return false;
        });
      
    }); // end dom ready
  
    // Without JS, content leaves space for the category menu.
    // With JS, the category menu collapses and the content spreads.
    $(document).ready(function() {
        $("#categories").addClass("collapsed"); // collapse categories menu
        $("#content-main").addClass("full"); // make the content spread to the full width
        $("#categories.collapsed h3").click(function() { 
            $("#cat-list").toggleClass("visible");
            $(this).toggleClass("open");
            $(document).click(function(e) {
                var node = e.target;
                while (node && !node.id) {
                    node = node.offsetParent;
                }
                if (!node || (node.id != \'categories\' && node.id != \'cat-list\')) {
                    $("#cat-list").removeClass("visible");
                    $("#categories.collapsed h3").removeClass("open");
                }
            });
        });
    });

</script>
';
}

public function printImageMenu(
$images,$imagePreviewId,$width,$height,$imageDefault ="ima/int/115x100tudineroade.jpg", $cssText ='', $textConfig = array()

){
	$txt = empty($textConfig['title_page']) ? '' : $textConfig['title_page'];
	$cssText = empty($cssText) ? '' : 'class="' . $cssText . '"';
	
	$inBackoffice = ABS_HTTP_PATH . SYSTEM_DIRECTORY;
		
	
	if(count($images)>= 1){
			$urlImage = ($images[0] != "") ? $inBackoffice . 'file.php?id=' . $images[0].'&type=image&img_size=predefined&width=750&height=650' : $imageDefault;
			$urlImagePreview = ($images[0] != "") ? $inBackoffice . 'file.php?id=' . $images[0].'&type=image&img_size=predefined&width='.$width.'&height='.$height : $imageDefault;
			
			$des = '';
			if(!empty($images[0])){
				$imageInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $images[0] );
				$des = $imageInfo->getDescription();
			}
			
			$Image = '<img border="0" src="'.$urlImagePreview.'" title="'.$des.'"  alt="'.$des.'" align="center" previewSrc="'.$urlImagePreview.'" absSrc="'.$urlImage.'" id="" name="ima_1" lowsrc="ima/portfolio/c3.gif">';
			
			echo '
			<link rel="stylesheet" type="text/css" href="'.$inBackoffice.'js/jsImg/imageHandler.css" title="default">
				<script language="javascript" src="'.$inBackoffice.'js/jsImg/imageHandler.js" ></script>
			<table border="0" width="' . $width . '"><tr><td height="' . $height . '" width="' . $width . '" align="center">';
			echo '<a rel="jquery-lightbox"  title="'.$des.'"  alt="'.$des.'"  href="'.$urlImage.'" id="'.$imagePreviewId.'" target="_blank">';									
				echo $Image;
			echo '</a>';
			
			$txt = (count($images)>1) ? $txt : '';
			echo '</td></tr>';
			
			if(count($images)> 1){
			
				echo '<tr><td>
				<table border="0"><tr valign="bottom"><td '.$cssText.'>' . $txt.'</td><td align="left">';
				//$Image = '<img id="'.$imagePreviewId.'" border="0" src="'.$urlImagePreview.'" align="center" src="ima/portfolio/c2.gif" previewSrc="'.$urlImagePreview.'" absSrc="'.$urlImage.'" id="ima_1" name="ima_1" lowsrc="ima/portfolio/c3.gif">';
				//echo $Image;		
				$i = 0;
				foreach( $images as $image){
					
					if(is_numeric($image)){
						$des = '';
						if(!empty($image)){
							$imageInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $image );
							$des = $imageInfo->getDescription();
						}
			
						$imageSrc = $inBackoffice . 'file.php?id=' . $image.'&type=image&img_size=predefined&width='.$width.'&height=' . $height;
						$imageLink = $inBackoffice . 'file.php?id=' . $image.'&type=image&img_size=predefined&width=800&height=650';
						echo '
							<a href="javascript:changeImage2(\''.$imagePreviewId.'\',\''.$imageLink.'\',\''.$imageSrc.'\',\''.$des.'\');" class="imageHandlerLink">
								'.(++$i).'
							</a>
						
						';
					}
				}
				echo '
					</td></tr>
					</table>
				</td></tr>';
			}
			
			echo '</table>';	
	}else{
		echo '<img src="' . $imageDefault . '">';
	}
		
}
	
	
public function printImageCarrusel( $images ){
	$width = 400;
	$height = 400;
	$miliSeconds = 5000;
	//self::printJSLibs();
	
	echo '
		<link href="' . DIRECTORY_BO . 'js/jsImageCarrusel/slider2.css" type="text/css" rel="stylesheet">
		<link href="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/css.css" type="text/css" rel="stylesheet">
		<link href="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/javascript.css" type="text/css" rel="stylesheet">
		
		<script src="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/jquery-1.js" type="text/javascript"></script>
		<script src="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/jquery_002.js" type="text/javascript"></script>
		<script type="text/javascript" src="' . DIRECTORY_BO . 'js/jsImageCarrusel/slider2.js"></script>
		<script src="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/jquery.js" type="text/javascript"></script>
		<!-- Include Chili Highlighter -->
		<script type="text/javascript" src="' . DIRECTORY_BO . 'js/jsImageCarrusel/imageslide-plugin_files/jquery_003.js"></script>
		
		'
  		.' <div style="width: '.($width+20).'px; height: '.($height).'px;" id="mygaltop" class="stripViewer" align="center">'
    	.' <ul style="width:' . ($width+20) . 'px; left: 0px;">';
    
   	
	foreach($images as $image){
		$urlImage = ($image != "") ? DIRECTORY_BO . 'file.php?id=' . $image.'&type=image&img_size=predefined&width=1024&height=768' : 'ima/int/ima-eventos.jpg';
		$urlImagePreview = ($image != "") ? DIRECTORY_BO . 'file.php?id=' . $image.'&type=image&img_size=predefined&width='.$width.'&height='.$height : 'ima/int/ima-eventos.jpg';
		echo 
		'<li style="width:' . ($width) .'px;height:' . ($height) .'px;">
			<table border="0" cellspacing="0" cellpadding="0" style="width:' . ($width) .'px;height:' . ($height) .'px;">
			<tr valign="center" align="center"><td>
			<a href="' . $urlImage .'" title="" rel="jquery-lightbox" target="_blank"><img border="0" title="" src="' . $urlImagePreview . '" /></a>
			</td>
			</table>
		</li>';
	}
	echo '</ul>'
    	.' </div>';
    	
	echo '<script type="text/javascript">'
		. 'var nPlecas = '.(count($images)).';'
		. 'var miliSeconds = ' . $miliSeconds . ';'
		. 'var ban = true;'
		. 'var anchoPlecas = ' . $width . ';'		
		. '</script>';	
}

	
	
}



?>