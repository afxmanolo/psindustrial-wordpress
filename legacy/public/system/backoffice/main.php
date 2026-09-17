<?php
require_once( "../common.php" );
ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification();
include_once( "includes/header.php" );
//include_once( "header.php" );
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1> <?php echo $config["project_name"];?></h1>
		<ol class="breadcrumb">
			<li>
				<a href="<?php echo ABS_HTTP_URL;?>system/backoffice/index.php"><i class="fa fa-home"></i> Home</a>
			</li>
		</ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<!-- Small boxes (Stat box) -->
		<div class="row">
		<?php
			//$colors = array('1' => 'bg-yellow','2' => 'bg-aqua','3' => 'bg-green','4'=>'bg-red','5'=>'bg-blue','6'=>'bg-orange','7'=>'bg-purple','8'=>'bg-gray');
			$i = 1;
			foreach ($sections as $key => $section) {
				
				if($key != 'index' && $key != 'child'){
					//if($section["include"]){

					if($key == "parent"){
						foreach ($section as $pos => $value_) {
							foreach ($sections["child"][$pos] as $pos_ => $value) {
								?>
								<div class="col-md-3 col-sm-6 col-xs-12">
									<a href="<?php echo $value['link']?>" style="font-size: 13px;">
										<div class="info-box">
											<span class="info-box-icon <?php echo $config["main_bg_color"]; ?>"><i class="fa <?php echo $value["icon"];?>"></i></span>
											<div class="info-box-content">
												<span class="info-box-text">
													<h4 style="text-decoration: none; color: #000; font-weight: bold;"><?php echo $value_["name"];?></h4>
												</span>
												<span class="info-box-number">
													<p style="font-size: 14px; font-weight: normal; color: #000;"><?php echo $value["name"];?> <i class="fa fa-arrow-circle-right"></i></p>
												</span>
											</div>
											<!-- /.info-box-content -->
										</div>
									</a>
									<!-- /.info-box -->
								</div>
								<?php
							}//end foreach
						}//end foreach
					}else{
				?>
	      			<div class="col-md-3 col-sm-6 col-xs-12">
	      				<a href="<?php echo $key?>.php" style="font-size: 13px;">
		      				<div class="info-box">
		      					<span class="info-box-icon <?php echo $config["main_bg_color"]; ?>"><i class="fa <?php echo $section["icon"];?>"></i></span>
		      					<div class="info-box-content">
		      						<span class="info-box-text">
		      							<h4 style="text-decoration: none; color: #000; font-weight: bold;"><?php echo $section["name"];?></h4>
		      						</span>
		      						<span class="info-box-number">
		      							<p style="font-size: 14px; font-weight: normal; color: #000;">Ver m&aacute;s<i class="fa fa-arrow-circle-right"></i></p>
		      						</span>
		      					</div>
		      					<!-- /.info-box-content -->
		      				</div>
	      				</a>
	      				<!-- /.info-box -->
	      			</div>
    			<?php
    				}
        			$i++;
        			if($i==9){$i=1;}
        		//}//end if
        	}//end if
      	}//end foreach
    ?>
    	</div><!-- /.row -->
  	</section><!-- /.content -->      
</div><!-- /.content-wrapper -->
<?php
include_once( "includes/footer.php" );
//include_once( "footer.php" );
?>

