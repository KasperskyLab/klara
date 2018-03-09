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
        <br />
            <div id = "left_floating_div">
                <div id="form_error_box"></div>

                <form method = "post" id="form_lookup_md5" action = "<?php echo site_url("jobs/search_md5s") ?>" class = "form-inline">
                    <strong>Multiple md5s search:</strong>
                    <br />
                    <strong>Each entry can be separated by "," or "\n". Valid md5s expected.</strong><br />
                    <textarea name = "requested_md5s" style="width:500px" class="form-control" rows="5"></textarea>
                    <br /><br />
                    <input type="submit" class = "btn btn-primary" value="Submit">
                </form>
                <br />
            </div>
            <div id = "right_floating_div">
            </div>
            <div style="clear:both"></div>
        </p>
    </div>
    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
