<?php 
require_once( "../common.php" );
include_once( "includes/header.php" ); 
?>
<div class="login-box-body" style="background-color: #FFF !important;">
    <div class="login-logo" align="center" style="text-align: -webkit-center; background: #2B2B29;">
        <img src="<?php echo $config['logo_project']?>?>" class="img-responsive">
    </div>
    <p class="login-box-msg">Solo acceso autorizado</p>

    <?php if(isset($this) && isset($this->message )){?>
        <div class="alert alert-danger" style="text-align: center;">
            <strong><i class="icon fa fa-ban"></i><?php echo $this->message;?></strong>
        </div>
    <?php
    }//end if
    ?>

    <div class="clearfix"></div>
        <form id="loginForm" action="" method="post">
            <input type='hidden' name='cmd' value='login' />
            <div class="form-group has-feedback">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                <input type="email" id="username" name="username" class="form-control" placeholder="Email">
            </div>
            <div class="form-group has-feedback">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                <input type="password" id="password" name="password" class="form-control" placeholder="Password">
            </div>
            <div class="row">
                <div class="col-xs-5 pull-right">
                    <button type="submit" class="btn bg-red btn-block btn-flat">Enter</button>
                </div><!-- /.col -->
            </div>
        </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#loginForm").validate({
                debug: false,
                rules: {
                    username: {required: true, 'email': true},
                    password: {required: true,},
                },
                messages: {
                    username:{required: 'Username es requerido', email:'Debe ser un correo v&aacute;lidos'},
                    password:{required: 'Password es requerido',},
                }
            });
        });
    </script>
<?php include_once( "includes/footer.php" ); ?>

