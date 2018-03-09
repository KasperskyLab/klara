<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

        <title> <?php echo $this->config->item('project_title') ?> </title>
        <!-- Twitter  BootStrap -->   
        <link rel="stylesheet" media="screen"  href="<?php echo base_url("media/css/bootstrap.min.css")?>" >
        <!-- Custom styles for this template -->
        <link rel="stylesheet" type="text/css" href="<?php echo base_url("media/css/login.css")?>">


        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
            <script src="<?php echo base_url("media/js/html5shiv.min.js")?>"></script>
            <script src="<?php echo base_url("media/js/respond.min.js")?>"></script>
        <![endif]-->

        <script type="text/javascript">
        <!--
            var CI = {
                'site_url': '<?php echo site_url(); ?>/'
            };
        -->

        </script>
    </head>

    <body>
    <div class="container">
        <form class="form-signin" id = "form_login" method = "POST">
            <h2 class="form-signin-heading"><center><?php echo $this->config->item('project_title');?></center></h2>
            <div id="form_login_error_box" class="form_error_box"></div>
            <label for="form_username" class="sr-only">Username</label>
            <input type="text" id="form_username" class="form-control" placeholder="Username" required autofocus>
            <label for="form_password" class="sr-only">Password</label>
            <input type="password" id="form_password" class="form-control" placeholder="Password" required>

            <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
        </form>
    </div>
    <!-- /container -->

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo base_url("media/js/e10-viewport-bug-workaround.js")?>"></script>
    <script src="<?php echo base_url("media/js/jquery-2.1.1.min.js")?>"></script>
    <script src="<?php echo base_url("media/login_script.js")?>"></script>
</body>
</html>
