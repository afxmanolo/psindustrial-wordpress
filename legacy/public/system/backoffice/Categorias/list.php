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

function showIMG($img){    
    if(!empty($img)){
        $file_img = ProjectLibrary_SDO_Core_Application_FileManagement::getFileByVersion($img,'large');
        $src_img  = ABS_HTTP_URL."multimedia/".$file_img->getPath().$file_img->getFileName();
        return "<a href='".$src_img."' data-fancybox ><img src='".$src_img."' width='70' height='auto'></a>";
    }else{
        return "<small class='alert alert-info' style='padding: 3px;'>No Picture</small>";
    }
}
$listPanel->addCallBack("imagen","showIMG");

function renamePadre($padreId){
    if(!empty($padreId)){
        $padre = ProjectLibrary_SDO_Core_Application_Categorias::LoadById($padreId);
        return $padre->getNombre();
    }else{
        return "Sin Categoria Padre";
    }
}
$listPanel->addCallBack("padre_id","renamePadre");

// Add "Order By" capabilities
$listPanel = new GUI_ListPanel_Decorator_Sorter( $listPanel );
$listPanel->setOrderBy( $this->options->getOrderBy() );


// Add actions
$listPanel = new GUI_ListPanel_Decorator_RowActions( $listPanel );
$listPanel->setHeaderLabel( 'Acciones' );

// Modify
$actModify = new GUI_ListPanel_Action('javascript:;', 'fa fa-pencil-square-o');
$actModify->setImageTitle(  $this->trans('edit').'-'.$this->trans('user') );
$actModify->setOnClickEvent( 'editForm("edit",{$categorias_id})' );
$listPanel->addAction( $actModify );

// Delete
$actDelete = new GUI_ListPanel_Action( '?cmd=delete&id={$categorias_id}', 'fa fa-trash-o');
$actDelete->setImageTitle( $this->trans('delete').'-'.$this->trans('user') );
$actDelete->setOnClickEvent( 'return confirm("'.$this->trans('?_confirm_delete').' '.strtolower($this->trans('the')).' '.strtolower($this->trans('category')).' \"{$nombre}\"?");' );
$listPanel->addAction( $actDelete );

include_once ( "includes/header.php" );

?>

<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1> <?php echo "Categorias";?></h1>
		<ol class="breadcrumb">
			<li><a href="index.php"><i class="fa fa-home"></i> Inicio</a></li>
			<li><a href="Categorias.php">Categorias</a></li>
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
                                <?php echo 'Agregar nueva Categoria';?>
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
        }else if(type == 'editAddress'){
            cmd = 'cmd=editAddress&id='+id;
        }//end if

        var Url = "<?php echo ABS_HTTP_URL?>";
        $.ajax({
            'type'    :"GET",
            'url'     : Url+'system/backoffice/Categorias.php',
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