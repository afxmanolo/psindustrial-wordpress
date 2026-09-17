<?php
$config       = Config::getGlobalConfiguration();
//$strSubtitles = $this->strSubtitles;
$form         = $this->form;
$viewConfig   = $this->viewConfig;
$errors       = $this->errors;
?>

<div class="row">
	<div class="col-md-12">
		<div class="box <?php echo $config["box_color"]; ?>">
			<div class="box-header with-border">
				<h2><? echo $strSubtitles; ?></h2>
			</div>
			<form name="<?php echo $form['name']; ?>" id="<?php echo $form['name']; ?>" method="post" action="Productos.php" enctype="multipart/form-data">
				
				<div class="box-body">
					<?php
						ProjectLibrary_SDO_Core_Application_Form::displayForm($form, $errors, $form['name']);
					?>
				</div>
				<div class="box-footer">
					<input type="hidden" name="cmd" value="save">

					<button type="submit" class="btn <?php echo $config["btn-color"]; ?> pull-right" style="margin-left: 15px;">Aceptar</button>
					<button type="button" class="btn btn-default pull-right" onClick="closeForm();">Cancelar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	function closeForm(){
		$("#divEditForm").slideUp();
		$("#frmUser")[0].reset();
		$("#btnEditForm").show(function(e){
			$("#btnEditForm").attr('disabled',false);
			$(".loading").html('');
			$(".loading").show('');
		});
	}// end function

	$(document).ready(function(e){
		$("#frmUser").validate({
			debug: false,
		});
		//Date range picker with time picker
        //$('#rangeTime').daterangepicker({timePicker: true, timePickerIncrement: 30, format: 'MM/DD/YYYY h:mm A'});
    });

</script>