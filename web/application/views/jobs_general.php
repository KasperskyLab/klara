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
    <p><?php
        // Check if we have any search results.
            if (isset ($requested_md5s) && count ($requested_md5s) > 0)
            {
                // Display text
                echo '<br><strong>Showing search results for: </strong>';
                // We want to display only the 10 search results!
                $stop_iterating = false;
                $i = 0;
                while (!$stop_iterating && $i < count ($requested_md5s))
                {
                    // I really hate mixing php and html tags
                    echo anchor('/jobs/search_md5s/'.$requested_md5s[$i],
                        $this->global_functions->string_trim ($requested_md5s[$i],10),
                        array (
                            "role" => "button",
                            "class" => "btn btn-success btn-xs"
                        )
                    )."&nbsp;";

                    $stop_iterating = ++$i >= 9;
                }
                if ($stop_iterating)
                    echo '<button type="button" class="btn btn-success btn-xs disabled">etc...</button>';
            }
            ?>
            <table id="dt_results" class="display" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Repo name</th>
                    <th>Rule name</th>
                    <th>Matched files</th>
                    <th>Status and actions</th>
                </tr>
            </thead>
            <tbody id="dt_results_body">
                <?php
                    foreach($jobs_table as $row)
                    {
                        /*****  Setting up description field! *****/
                        if ($row['agent_id'] === "-1")
                            $agent_info = "N/A";
                        else
                        {
                            $found_agent = false;
                            // We have a valid agent id
                            // Let's find it in our list of agents
                            $agent_info = " [#".$row['agent_id']."]";
                            // It should not be possible to have an id which is
                            foreach ($agents as $entry)
                            {
                                if (intval($entry['id']) == intval($row['agent_id']))
                                {
                                    $agent_info .= " ".$entry['description'];
                                    $found_agent = true;
                                    break;
                                }
                            }
                            // If the supplied agent id was not found in our DB of agents, then something is really weird!
                            if (!$found_agent)
                                $agent_info .= " Unknown agent! Please contact the administrator";
                        }
                        // Repository name column
                        $repository = "N/A";
                        $table_description  = "<strong>Agent info:</strong> ".$agent_info."<br>\n";
                        $table_description  .= "<strong>Owner:</strong> ".$this->security->xss_clean($row['username'])."<br>\n";
                        $job_description = json_decode($row['description'],true);
                        foreach ($job_description as $key => $val )
                        {
                            // Filter only the relevant fields
                            // We just extract the owner
                            // if ($key === "notify_email")
                            //     $table_description .= "<strong>Owner:</strong> ".$val."<br>\n";
                            if ($key === "fileset_scan")
                                $repository = $val;
                            if ($key === "execution_time")
                                $table_description .= "<strong>Execution time:</strong> ".$val."<br>\n";
                        }

                        // Finish time - We might not need this
                        // $table_description .= "<strong>Finish time:</strong> ".$row['finish_time']."<br>\n";

                        /***** Setting up rule name *****/
                        $first_rule_name = $this->global_functions->jobs_extract_first_rules ($row['rules'], true);
                        /***** Setting up matched files *****/

                        if (isset($row['matched_files']) && $row['matched_files'] !== "-1")
                            $matched_files = $row['matched_files'];
                        else
                            $matched_files = "N/A";

                        /***** Setting up status and actions *****/

                        // If the scan was slow, add the warning!
                        if (isset($job_description['yara_warnings']) && $job_description['yara_warnings'] === "true")
                        {
                            $warning_glyphicon = '<span data-toggle="tooltip" data-placement="top" title="Yara warnings!" class="glyphicon glyphicon-exclamation-sign"></span> &nbsp ';
                        }
                        else
                            $warning_glyphicon = "";

                        // Delete button
                        // If the user does not own the job then no delete button. Same if status is "assigned"
                        if  (    $row['status'] === "assigned" ||
                                !$this->global_functions->current_user_owns_job($row['owner_id'])
                            )
                            $delete_button = '';
                        else
                            // Printing the actions column
                            $delete_button = '<span> Other actions: </span><button class = "btn btn-sm btn-danger active" job_id = "'.$row['id'].'" job_first_rule_name = "'.$first_rule_name.'" type = "delete_job">Delete job!</button>';

                        // Job status button
                        if ($row['status'] === "new")
                            $status_button = '<a href="'.site_url('jobs/view/'.$row['id']).'" type="button" class="btn btn-sm btn-primary">New Job</a>';
                        else if ($row['status'] === "assigned")
                            $status_button = '<a href="'.site_url('jobs/view/'.$row['id']).'" type="button" class="btn btn-sm btn-warning">Assigned</a>';
                        else if ($row['status'] === "yara_errors")
                            $status_button = '<a href="'.site_url('jobs/view/'.$row['id']).'" type="button" class="btn btn-sm btn-danger">Yara errors!</a>';
                        else if ($row['status'] === "finished")
                            $status_button = '<a href="'.site_url('jobs/view/'.$row['id']).'"  type="button" class="btn btn-sm btn-success">Finished</a>&nbsp; ';
                        else
                            $status_button = '<a href="'.site_url('jobs/view/'.$row['id']).'" type="button" class="btn btn-sm btn-info">'.$row['status'].'</a>';
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $table_description; ?></td>
                    <td><?php echo $repository; ?></td>
                    <td><a href="<?php echo site_url('jobs/view/'.$row['id'])?>" class = "btn btn-sm btn-default" job_id = "<?php echo $row['id']?>" type = "rule_description"><?php echo $first_rule_name;?></a></td>
                    <td><?php echo $matched_files; ?></td>
                    <td><?php echo $warning_glyphicon; echo $status_button;?>
                        <button type = "button" class = "btn btn-sm"
                                data-toggle = "collapse"
                                data-target = "#clone_area_id_<?php echo $row['id']; ?>"
                                aria-expanded = "false">Job Management</button>

                        <div class="collapse" id = "clone_area_id_<?php echo $row['id'];?>"><br>
                                <form type = "form_restart_job" class = "form-inline">
                                    <!-- <span>Restart this job over new fileset:</span><br> -->

                                    <select name = "fileset_scan" class = "form-control">;
                                    <?php
                                        foreach ($fileset_scan as $file)
                                            echo '<option value="'. $file['id'] .'">'. $file['entry'] .'</option>\n';
                                    ?>
                                    </select>
                                    <input type  = "hidden" name = "job_id" value = "<?php echo $row['id'];?>"></input>
                                    <button type = "submit" class="btn btn-sm btn-warning">Restart job</button>
                                </form>
                                <br>
                                <?php echo $delete_button; ?>
                        </div>
                    </td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
        <br/><br/>
    </p>
    </div>

    <!-- Various helper elements! -->

    <!-- Modal delete job -->
    <div class="modal fade" id = "modal_delete_job" tabindex="-1" role="dialog" aria-labelledby="modal_delete_job_title" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modal_delete_job_title">Modal title</h4>
          </div>
          <div class="modal-body" id = "modal_delete_job_body">
            Modal body
          </div>
          <div class="modal-footer">
            <form action = "<?php echo site_url('jobs/delete_job')?>" method = "POST">
                <input type = "hidden" name = "job_id" id = "modal_delete_job_id"></input>

                <button type="input"  class="btn btn-danger">Yes</button>
                <button type="button" class="btn btn-success" data-dismiss="modal">No</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal restart job -->
    <div class="modal fade" id = "modal_restart_job" tabindex="-1" role="dialog" aria-labelledby="modal_restart_job_title" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modal_restart_job_title">Modal title</h4>
          </div>
          <div class="modal-body" id = "modal_restart_job_body">
            Modal body
          </div>
          <div class="modal-footer">
            <form id = "modal_restart_job_form">
                <input type = "hidden" name = "fileset_scan"    id = "modal_restart_job_fileset_scan"></input>
                <input type = "hidden" name = "job_id"          id = "modal_restart_job_id"></input>
                <button type="input"  class="btn btn-danger">Yes</button>
                <button type="button" class="btn btn-success" data-dismiss="modal">No</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php echo $this->load->view('elements/footer','',true) ?>
</div>

</body>
</html>
