	
    <?php 
    if(isset($userProfile)){
    ?>
        <footer class="main-footer">
          <!--<div class="pull-right hidden-xs">
            <img width="170" height="13" alt="" src="">
          </div>-->
          Copyright &copy; <?php echo date('Y'); ?> <strong><?php echo $config['project_name'];?>.</strong> All rights reserved.
        </footer>
      </div>
    </body>
    <?php 
    }else{
    ?>
        </div>
    </body>
    <?php
    }//end if
    ?>

    <!-- jQuery UI 1.11.4 -->
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
    <!-- FancyBox -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/fancybox/jquery.fancybox.min.js"></script>
	<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
	<script>
		$.widget.bridge('uibutton', $.ui.button);
	</script>	
	<!-- Bootstrap 3.3.5 -->
	<script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/bootstrap/js/bootstrap.min.js"></script>
    <!-- DataTables -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datatables/dataTables.bootstrap.min.js"></script>
	<!-- iCheck -->
	<script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/iCheck/icheck.min.js"></script>
	<!-- Morris.js charts -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/morris/morris.min.js"></script> -->
    <!-- Sparkline -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/sparkline/jquery.sparkline.min.js"></script>
    <!-- jvectormap -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/knob/jquery.knob.js"></script>
    <!-- daterangepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.2/moment.min.js"></script>
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/daterangepicker/daterangepicker.js"></script>
    <!-- datepicker -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datepicker/bootstrap-datepicker.js"></script>
     <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datepicker/locales/bootstrap-datepicker.es.js"></script>
    <!-- Bootstrap WYSIHTML5 -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
    <!-- Slimscroll -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/slimScroll/jquery.slimscroll.min.js"></script>
    <!-- FastClick -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/fastclick/fastclick.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/dist/js/app.min.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <!-- <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/dist/js/pages/dashboard.js"></script> -->
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/dist/js/demo.js"></script>
    <!-- Select2 -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/select2/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/iCheck/icheck.min.js"></script>
</html>