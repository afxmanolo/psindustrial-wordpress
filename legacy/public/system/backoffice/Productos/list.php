<?php
$users = $this->options->getResults();
$viewConfig = $this->viewConfig;
//params of the variable viewConfig
$paramsUsed = "name,title,id";
$paramsArrUsed = "columNamesOverride,hiddenColums,hiddenFields";
$paramsUsed = explode(",",$paramsUsed);
foreach( $paramsUsed as $param){
	$viewConfig[ $param ] = empty( $viewConfig[ $param ] ) ? '' : $viewConfig[ $param ];
}
$paramsArrUsed = explode(",",$paramsArrUsed);
foreach( $paramsArrUsed as $param){
	$viewConfig[ $param ] = empty( $viewConfig[ $param ] ) ? array() : $viewConfig[ $param ];
}
$data = array();
foreach($users as $user){
	$data[] = get_object_vars( $user );
}

$listPanel = new GUI_ListPanel_ListPanel( $viewConfig['name'] );
$listPanel->setData( $data );
foreach( $viewConfig['columNamesOverride'] as $colum => $rename){
	$listPanel->addColumnNameOverride($colum,$rename);
}
foreach( $viewConfig['hiddenColums'] as $col ){
	$listPanel->addHiddenColumn( $col );
}
foreach( $viewConfig['hiddenFields'] as $name=> $value ){
	$listPanel->addHiddenField($name, $value );
}

function renameCategoria($categoriaId){
    if(!empty($categoriaId)){
        $Categoria = ProjectLibrary_SDO_Core_Application_Categorias::LoadById($categoriaId);
        return $Categoria->getNombre();
    }else{
        return "Sin Categoria";   
    }
}
$listPanel->addCallBack("categoria","renameCategoria");

function renameMarca($marcaId){
    $Marca = ProjectLibrary_SDO_Core_Application_Marcas::LoadById($marcaId);
    return $Marca->getNombre();
}
$listPanel->addCallBack("marca","renameMarca");
function showIMG($imagen){
    $image = explode(',',$imagen);
    $img = $image[0];
    if(!empty($img)){
        $file_img = ProjectLibrary_SDO_Core_Application_FileManagement::getFileByVersion($img,'large');
        $src_img  = ABS_HTTP_URL."multimedia/".$file_img->getPath().$file_img->getFileName();
        return "<a href='".$src_img."' data-fancybox ><img src='".$src_img."' width='70' height='auto'></a>";
    }else{
        return "<small class='alert alert-info' style='padding: 3px;'>No Picture</small>";
    }
}
$listPanel->addCallBack("imagenes","showIMG");

function showFile($files){    
    $file_0 = explode(',',$files);    
    $file = $file_0[0];
    if(!empty($file)){
        $fichaInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $file );
        $src = ABS_HTTP_URL.SYSTEM_DIRECTORY."file.php?id=".$file."&type=".$fichaInfo->getType();
        return "<a href='".$src."' target='_blank'><img src='".ABS_HTTP_URL."images/verficha.png' width='70' height='auto'></a>";
    }else{
        return "<small class='alert alert-info' style='padding: 3px;'>No Files</small>";
    }
}
$listPanel->addCallBack("fichas","showFile");
// Add "Order By" capabilities
$listPanel = new GUI_ListPanel_Decorator_Sorter( $listPanel );
$listPanel->setOrderBy( $this->options->getOrderBy() );


// Add actions
$listPanel = new GUI_ListPanel_Decorator_RowActions( $listPanel );
$listPanel->setHeaderLabel( 'Acciones' );

// Modify
$actModify = new GUI_ListPanel_Action('javascript:;', 'fa fa-pencil-square-o');
$actModify->setImageTitle( 'Editar '. $viewConfig['name'] );
$actModify->setOnClickEvent( 'editForm("edit",'.$viewConfig["id"].')' );
$listPanel->addAction( $actModify );

// Delete
$actDelete = new GUI_ListPanel_Action( '?cmd=delete&id='.$viewConfig["id"], 'fa fa-trash-o');
$actDelete->setImageTitle( 'Eliminar '. $viewConfig['name'] );
$actDelete->setOnClickEvent( 'return confirm("Esta seguro que desea eliminar el registro ' . $viewConfig['id'] . ' ?");' );
$listPanel->addAction( $actDelete );

//$strSuccess = $this->strSuccess;
//$strError = $this->strError;
include_once ( "includes/header.php" );?>
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1> Productos</h1>
		<ol class="breadcrumb">
			<li><a href="index.php"><i class="fa fa-home"></i> Home</a></li>
			<li><a href="Productos.php">Productos</a></li>
		</ol>
	</section>

    <div id="divEditForm" class="col-xs-12 col-md-8 col-md-offset-2" style="display: none;"></div>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <div class="cleafix"></div>
                        <div class="box-title pull-right">
                            <div class="loading"></div>
                            <a id="btnEditForm" class="btn btn-default" onclick="editForm('new');" style="cursor: pointer;">
                                <i class="fa fa-plus-circle" aria-hidden="true"></i>
                                <?='Agregar Productos'?>
                            </a>
                        </div>
                    </div><!-- /.box-header -->
                    <div class="box-body" style="overflow-x: auto;">
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo isset( $this->strSuccess) ?  "<div class='alert alert-success alert-dismissible' align='center'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button><h4><i class='icon fa fa-check'></i>".$this->strSuccess."</h4></div>" : '' ;?>
                                <?php echo isset($this->strError) ? "<div class='alert alert-danger alert-dismissible' align='center'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button><h4><i class='icon fa fa-ban'></i>".$this->strError."</h4></div>" : '' ;?>
                            </div>
                        </div>
                        <?php
                        $listPanel->setTableWidth( '100%' );
                        $listPanel->display(true);
                        ?>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->	
</div><!-- /.content-wrapper -->
<?php
include_once ( "includes/footer.php" );
?>

<script type="text/javascript">
    $(function () {
        $("#table").DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "order": [[ 0, "desc" ]]
        });
    });

    function editForm(type,id){
        console.log(type + " " + id);

        if(type == 'new'){
            cmd = 'cmd=add';
        }else if(type == 'edit'){
            cmd = 'cmd=edit&id='+id;
        }

        var Url = "<?php echo ABS_HTTP_URL?>";
        $.ajax({
            'type'    :"GET",
            'url'     : Url+'system/backoffice/Productos.php',
            'data'    : cmd,
            'dataType': "html",
            beforeSend: function(data){
                $("#btnEditForm").attr('disabled',true);
                $(".loading").html('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success:  function (data){
                $("#btnEditForm").hide();
                $(".loading").hide();
                $("#divEditForm").html(data);
                $("#divEditForm").slideDown();
                $('html, body').stop().animate({scrollTop:0},500,'swing',function(){});
            }
        });       
    }

</script>