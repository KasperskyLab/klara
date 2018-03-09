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
        <div id="form_info_box"></div>
        <form id = "form_update_profile" role = "form">
            <table class="table table-bordered table-striped">
                <colgroup>
                    <col class="col-xs-1">
                    <col class="col-xs-7">
                </colgroup>
                
                <tbody>
                    <tr>
                        <td><strong>Update your profile</strong></td>
                    </tr>
                    <tr>
                        <td>Username:</td>
                        <td> <?php echo $this->klsecurity->get_auth_username();?> </td>
                    </tr>
                    <tr>
                        <td>Notify e-mail</td>
                        <td><input  type="text" class="form-control" style="width:700px" placeholder = "Please input a valid e-mail address here in order to be notified when the job finishes" 
                                    id = "form_update_profile_email" value = "<?php echo $this->global_functions->get_user_email();?>"></td>
                    <tr>
                        <td></td>
                        <td> <button type="submit" class="btn btn-primary">Submit</button></td>
                    </tr>
                </tbody>
            </table>
        </form>



        <div style="clear:both"></div>
        
        <br/><br/><br/>
        </p>
    </div>
    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
