<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Jobs extends CI_Controller 
{
    public function __construct()
    {
        parent::__construct();
        $this->iterate_jobs_display_limit_upper = 10000;
    }

    public function index()
    {
        echo "Hello API Jobs";
    }

    /* This function lists all the jobs allowed to be listed by the API user

    Optional POST variables:
    - limit => Default: 100 - defines how many jobs should be printed in desc order
    */
    public function get_all_jobs()
    {
        // Couldn't find any other way to send the state to array_map function
        global $detailed_job_info;
        // Try to convert the input to int. If it is invalid
        $jobs_display_limit = intval($this->input->post('limit'));

        if ($jobs_display_limit < 1 || $jobs_display_limit >= $this->iterate_jobs_display_limit_upper)
            $jobs_display_limit = 100;

        // jobs view sanitizes the input so we are OK
        $detailed_job_info = $this->input->post('detailed_info');

        // Try to see if the user submitted the string "true", and if not, default to FALSE
        if ($detailed_job_info === "true")
            $detailed_job_info = true;
        else
            $detailed_job_info = false;


        // Apply admin / non-admin restrictions for jobs
        // As well as jail / non-jail restrictions
        $this->global_functions->jobs_apply_db_restrictions ();
        // Get all jobs and join to get usernames
        $this->db->select('jobs.*, username');
        $this->db->join('users', 'owner_id = users.cnt');
        $this->db->order_by("id", "desc");
        // $this->db->join('agents', 'jobs.agent_id = agents.id');
        // Limit how many jobs we want to display
        $this->db->limit($jobs_display_limit);
        $q = $this->db->get('jobs');
        $jobs = $q -> result_array();

        // Let's generate our answer
        $return_data = array_map(
            function ($job_info)
            {
                global $detailed_job_info;
                // Here we set how the returned array should look like
                if ($detailed_job_info)
                {
                    $returned_rules     = $job_info['rules'];
                    $returned_rules_key = "rules";
                }
                else
                {
                    $returned_rules     = $this->global_functions->jobs_extract_first_rules ($job_info['rules']);
                    $returned_rules_key = "rules_first_line";
                }

                return array(
                    "id"                => $job_info['id'],
                    $returned_rules_key => $returned_rules,
                    "status"            => $job_info['status'],
                    "start_time"        => $job_info['start_time'],
                    "finish_time"       => $job_info['finish_time'],
                    "owner"             => $job_info['username']
                );

            },
            $jobs
        );
        // Finally print this
        $this->klsecurity->api_finish_request_output(
            $this->global_functions->api_generate_response_json("ok", '', $return_data)
        );
    }

    /*
    This function adds new jobs with user provided rules over
    user provided repositories
    Required POST variables:
        - rules
        - repositories
    */
    public function add()
    {
        $rules         = $this->input->post('rules');
        $repositories  = $this->input->post('repositories');
        // We need to convert the JSON list into an array
        // Check if the user submitted a rule
        if (!is_string($repositories) || strlen($repositories) <= 0)
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Please submit a valid repositories JSON list")
            );
        $repositories_array  = json_decode($repositories, true);
        if (is_null ($repositories_array))
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Please submit a valid repositories JSON list")
            );
        // Return status from internal function (also return IDs) and clear the session as well
        $this->klsecurity->api_finish_request_output($this->global_functions->jobs_add($rules, $repositories_array, true));
    }

    /*
    This function just tests the rules received by API for validity
    Required POST variables:
        - rules
        - repositories
    */
    public function test_add()
    {
        $rules         = $this->input->post('rules');
        $repositories  = $this->input->post('repositories');
        // We need to convert the JSON list into an array
        // Check if the user submitted a rule
        if (!is_string($repositories) || strlen($repositories) <= 0)
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Please submit a valid repositories JSON list")
            );
        $repositories_array  = json_decode($repositories, true);
        if (is_null ($repositories_array))
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Please submit a valid repositories JSON list")
            );
        $output = "---- Rules ----\n" . print_r($rules, true) . "---- Repositories ----\n" . print_r($repositories_array, true);
        $this->klsecurity->api_finish_request_output($output);
    }

    /*
    This function returns detailed info about the job ID.
    By default it provides info only for the job status. Users can request detailed info.

    Optional POST variables:
        - detailed_info => Default: false
    */
    public function status($id = -1)
    {
        // jobs view sanitizes the input so we are OK
        $detailed_job_info = $this->input->post('detailed_info');

        if ($detailed_job_info == "true")
            $detailed_job_info = true;
        else
            $detailed_job_info = false;


        // Try to fetch the status for this job ID.
        $job_details = $this->global_functions->jobs_view ($id);
        // If the jobs_view returned an empty array return an error.
        if (empty($job_details))
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid job ID received")
            );

        // We now have some info about the job
        /*
            - For any jobs, we have at least, regardless of their status
                ID, status, description
                rules, matched_files, start_time, finish_time, agent_id,
                owner (aka owner_id JOINED with username)

            - For finished or yara_errors we can add:
                results, hashes
        */

        $return_data = array();

        // ID, status and description should exist
        $return_data['id']          = $job_details['id'];
        $return_data['status']      = $job_details['status'];
        $return_data['description'] = $job_details['description'];
        $return_data['agent_id']    = $job_details['agent_id'];
        $return_data['owner']       = $job_details['username'];

        if (!$detailed_job_info)
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_response_json("ok", '', $return_data)
            );

        // User requested detailed info, carrying on..
        $return_data['rules'] = $job_details['rules'];
        $return_data['matched_files'] = $job_details['matched_files'];
        $return_data['start_time'] = $job_details['start_time'];

        if ($job_details['status'] == 'finished' || $job_details['status'] == 'yara_errors')
        {
            $return_data['results'] = $job_details['results'];
            $return_data['finish_time'] = $job_details['finish_time'];
            $return_data['hashes'] = array_map(
                function ($hash_info)
                {
                    return $hash_info['hash_md5'];
                },
                $job_details['hashes']
            );
        }
        // Finally print this
        $this->klsecurity->api_finish_request_output(
            $this->global_functions->api_generate_response_json("ok", '', $return_data)
        );
    }

    public function delete($id = -1)
    {
        // Convert the ID to int
        $id = intval($id);
        // Send it to our global function, let's see what we get!
        // Finally print this
        $this->klsecurity->api_finish_request_output(
            $this->global_functions->jobs_delete ($id)
        );
    }
    // Endpoint returns the allowed repositories for this specific user
    public function get_allowed_repos()
    {
        $output = $this->global_functions->api_generate_response_json("ok", "", $this->global_functions->get_user_allowed_repositories_details ());
        $this->klsecurity->api_finish_request_output($output);
    }

}
