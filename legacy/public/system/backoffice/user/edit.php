<?php 
$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
/*if($userProfile->getRole() != ProjectLibrary_FrontEnd_BO_Authentification::SUPER_ROLE){
    header("Location: ".ABS_HTTP_URL."system/backoffice/index.php");
    die;
}*/
	$config       = Config::getGlobalConfiguration();
	$user         = $this->user;
	$strSubtitles = $this->strSubtitles;
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
			<form name="<?php echo $form['name']; ?>" id="<?php echo $form['name']; ?>" method="post" action="user.php" enctype="multipart/form-data">
				<input type="hidden" name="oldPass" id="oldPass" value="<?=$user->getPassword()?>">
				<div class="box-body">
					<?php
						ProjectLibrary_SDO_Core_Application_Form::displayForm($form, $errors, $form['name']);
					?>
				</div>
				<div class="box-footer">
					<input type="hidden" name="cmd" value="save">
					<input type="hidden" name="entity[id]" value="<?php echo $user->getUserId()?>">
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