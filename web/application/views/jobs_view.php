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
        <table class="table table-bordered table-striped">
            <colgroup>
                <col class="col-xs-1">
                <col class="col-xs-7">
            </colgroup>
            <tbody>
                <tr>
                    <td><strong>Job ID: <?php echo $job['id']?></strong></td>
                    <td>
                        <div class="form-inline">
                            <label for="shareable_link">Shareable Link:</label>
                            <input  style = "width:55%"
                                    placeholder="Share Link"
                                    value = "<?php echo site_url().'/jobs/view/'.$job['id'].'/'.$job['share_key']?>"
                                    class="form-control" id="shareable_link">
                            &nbsp;
                            <button type="button" class="btn btn-default"
                                    data-toggle         = "tooltip" data-placement = "bottom"
                                    data-original-title = "Shareable links allow you to share this rule with any valid KLara account, no matter of their permissions">
                                <span   class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span> &nbsp;What is this?
                            </button>
                            <span  ></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>
                    <?php
                        if ($job['status'] === "new")
                            $table_status = '<button type="button" class="btn btn-xs btn-primary">New Job</button>';
                        else if ($job['status'] === "assigned")
                            $table_status = '<button type="button" class="btn btn-xs btn-warning">Assigned</button>';
                        else if ($job['status'] === "yara_errors")
                            $table_status = '<button type="button" class="btn btn-xs btn-danger">Yara errors</button>';
                        else if ($job['status'] === "finished")
                            $table_status = '<button type="button" class="btn btn-xs btn-success">Finished</button>';
                        else
                            $table_status = '<button type="button" class="btn btn-xs btn-info">'.$this->security->xss_clean($job['status']).'</button>';

                        // If the scan was slow, add the warning!
                        if (array_key_exists('yara_warnings', $job['description']) && $job['description']['yara_warnings'] === "true")
                        {
                            $warning_glyphicon = '<span data-toggle="tooltip" data-placement="top" title="Yara warnings!" class="glyphicon glyphicon-exclamation-sign"></span> &nbsp ';
                        }
                        else
                            $warning_glyphicon = "";

                        echo $warning_glyphicon . $table_status;
                    ?></td>
                </tr>
                <tr>
                    <td>Owner</td>
                    <td>
                    <?php
                        echo $this->security->xss_clean($job['username']);
                    ?>
                    </td>
                </tr>
                <!-- Decided not to show notify e-mail due to fact that now rules can be shared with other people
                <tr>
                    <td>Notify e-mail</td>
                    <td>
                    <?php
                        echo $this->security->xss_clean($job['description']['notify_email']);
                    ?>
                    </td>
                </tr> -->

                <tr>
                    <td>Start time</td>
                    <td><?php echo $job['start_time'];?></td>
                </tr>
                <tr>
                    <td>Finish time</td>
                    <td><?php echo $job['finish_time'];?></td>
                </tr>
                <tr>
                    <td>Execution time</td>
                    <td><?php
                            if (array_key_exists('execution_time', $job['description']))
                                echo $this->security->xss_clean($job['description']['execution_time']) .' second(s)';
                            else
                                echo "N/A";
                    ?></td>
                </tr>
                <tr>
                    <td>Matched files</td>
                    <td><?php
                            if (isset($job['matched_files']) && $job['matched_files'] !== "-1")
                                echo $job['matched_files'];
                            else
                                echo "N/A";
                     ?></td>
                </tr>
                <tr>
                    <td>Rules</td>
                    <td> <pre class="pre-scrollable"><?php echo htmlentities($job['rules']); ?></pre></td>
                </tr>
                
                <tr>
                    <td>Fileset scan</td>
                    <td><pre><?php echo $this->security->xss_clean($job['description']['fileset_scan']) ?></pre></td>
                </tr>
                <tr>
                    <td>Matched MD5s</td>
                    <td><pre class="pre-scrollable"><?php
            if (count($job['hashes']) == 0)
                echo "N/A";
            else
                foreach ($job['hashes'] as $job_hash)
                {
                    echo $job_hash['hash_md5'] . "\n";
                }
            ?></pre></td>
                </tr>

                <?php
                    if ($job['status'] === "finished" || $job['status'] === "yara_errors" )
                    {
                        echo '<tr><td>Results</td><td><pre class="pre-scrollable">';
                        $results = $job['results'];
                        if ($results === "")
                            echo "### Yara found no matches! ###";
                        else
                            echo $results;
                        echo '</pre></td></tr>';
                    }
                ?>
            </tbody>
        </table>
        <br/><br/><br/>
        </p>
    </div>
    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
