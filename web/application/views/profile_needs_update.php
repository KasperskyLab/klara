<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <?php echo $this->load->view('elements/head','',true) ?>
    <script type="text/javascript">
    <!--
            var CI = {
                    'site_url': '<?php echo site_url(); ?>/'
            };
    -->
    </script>
</head>
<body>

<div id="container">

    <!-- Fixed navbar -->
    <?php echo $this->load->view('elements/navbar','',true) ?>
    <div id = "body">
        <p>
        <hr>
        <br>
            <center>
                Before submitting any jobs, please update your profile<br><br>
                <a href="<?php echo site_url('profile')?>" type="button" class="btn btn-sm btn-primary">Update profile</a>
            </center>
        <br/>
        </p>
    </div>
    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
