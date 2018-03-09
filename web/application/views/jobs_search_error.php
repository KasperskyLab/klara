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
                Invalid MD5s provided or search limit reached: <?php echo $this->config->item('search_md5s_limit')?> md5s. Good luck next time!<br><br>
            </center>
        <br/>
        </p>
    </div>
    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
