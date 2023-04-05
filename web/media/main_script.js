$(document).ready(function() 
{
    // Initialize the tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Set up the data table
    $('#dt_results').dataTable({
        "paging": true,
        "bAutoWidth": true,
        "iDisplayLength": 10,
        "sDom": '<"top"iflp<"clear">>rt<"bottom"iflp<"clear">>',
        "aaSorting": [],
    });

    /*******  Helper functions     *******/
    /*******  End helper functions *******/

    // Map the esc key to history back
    $(document).bind('keyup', function (e)
    {
        if (e.keyCode == 27)
            window.history.go(-1);
    });

    // Ajax request for adding a new job
    $( "#form_add_job_submit" ).on( "click", function( event ) 
    {
        event.preventDefault();

        // The the repositories to scan!
        var radiobox_choices = $('input[type="checkbox"]:checked','#form_add').map(function(){return this.value;}).get();
        // Get the yara_rules
        var yara_rules = ace.edit("yara_rules").getValue();;
        var post_obj =  {
            yara_rules: yara_rules,
            "yara_fileset_scan[]": radiobox_choices,
        };

        $.ajax({
            url: CI.site_url + "jobs/add_job",
            type: 'POST',
            dataType: "json",
            data: post_obj,
            statusCode: {
                404: function() {
                    alert("Error saving the new job in the DB");
                    console.log("Server returned 404");
                },
                403: function() {
                    alert("Error saving the new job in the DB");
                    console.log("Server returned 403");
                }
            },
            success: function (data)
            {
                // Let's check the object's data
                if (data.status === "ok")
                {
                    $('#form_error_box').hide(100);
                    window.location.assign("../jobs");
                }
                else
                {
                    $('#form_error_box').html("<strong>ERROR: " + data.status_msg + "</strong>");
                    $('#form_error_box').show(100);
                }
            }
        });
    });

    // Delete job user click
    $("button[type = 'delete_job']").on("click", function (event)
    {
        // Get the job ID
        var job_id              = $(this).attr('job_id');
        var job_first_rule_name = $(this).attr('job_first_rule_name');

        // Set the delete job modal
        $('#modal_delete_job_title').text("Confirm tag deletion");
        $('#modal_delete_job_body').html("Are you sure you want to delete job #<strong>" + job_id + "</strong> with first rule <strong>" + job_first_rule_name + "</strong>?");
        $('#modal_delete_job_id').val(job_id);
        $('#modal_delete_job_first_rule_name').val(job_first_rule_name);

        // Check if the user really wants to delete the job. Show the modal
        $('#modal_delete_job').modal();
        // And off we go...
    });

    // Restart job user click
    $("form[type = 'form_restart_job'").on('submit', function (event)
    {
        event.preventDefault();
        // Let's get the children select
        var fileset_scan    = $(this).children("select[name='fileset_scan']").val();
        var job_id          = $(this).children("input[name='job_id']").val();

        // Set the modal now
        $('#modal_restart_job_title').text("Confirm job resubmission");
        $('#modal_restart_job_body').html("Are you sure you want to restart job #<strong>" + job_id + "</strong>?");
        $('#modal_restart_job_fileset_scan').val(fileset_scan);
        $("#modal_restart_job_id").val(job_id);

        // Check if the user really wants to reassign the job. Show the modal
        $('#modal_restart_job').modal();
        // And off we go...
    });

    // [Modal] Ajax request for restarting job
    $( "#modal_restart_job_form" ).on( "submit", function (event)
    {
        event.preventDefault();

        var post_obj =  {
            fileset_scan:   $("#modal_restart_job_fileset_scan").val(),
            job_id:         $("#modal_restart_job_id").val()
        };

        $.ajax({
            url: CI.site_url + "jobs/restart_job",
            type: 'POST',
            dataType: "json",
            data: post_obj,
            statusCode: {
                404: function() {
                    alert("Error submitting job for restart");
                    console.log("Server returned 404");
                },
                403: function() {
                    alert("Error submitting job for restart");
                    console.log("Server returned 403");
                }
            },
            success: function (data)
            {
                // Let's check the object's data
                if (data.status === "ok")
                {
                    window.location.assign(CI.site_url + "jobs");
                }
                else
                {
                    $('#modal_restart_job_body').html(data.status_msg);
                }
            }
        });
    });

    // Ajax request for updating user profile
    $( "#form_update_profile" ).on( "submit", function (event)
    {
        event.preventDefault();

        var post_obj =  {
            yara_user_email: $("#form_update_profile_email").val()
        };

        $.ajax({
            url: CI.site_url + "profile/update",
            type: 'POST',
            dataType: "json",
            data: post_obj,
            statusCode: {
                404: function() {
                    alert("Error updating your profile");
                    console.log("Server returned 404");
                },
                403: function() {
                    alert("Error updating your profile");
                    console.log("Server returned 403");
                }
            },
            success: function (data)
            {
                // Let's check the object's data
                if (data.status === "ok")
                {
                    $('#form_info_box').html('<p class="text-success">Successfully updated the e-mail address.</p>');
                    $('#form_info_box').show(100);
                }
                else
                {
                    $('#form_info_box').html(data.status_msg);
                    $('#form_info_box').show(100);
                }
            }
        });
    });
});
