<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Jobs extends CI_Controller 
{
    // Access level: auth_registered_level
    public function __construct()
    {
        parent::__construct();

        $this->limit_front_page         = 100;
        $this->limit_jobs_general       = 500;
    }
    public function index()
    {
        $view_data = array();

        // Apply admin / non-admin restrictions for jobs
        // As well as jail / non-jail restrictions
        $this->global_functions->jobs_apply_db_restrictions ();
        // Get all jobs
        $this->db->order_by("id", "desc");
        // $this->db->join('agents', 'jobs.agent_id = agents.id');
        // Limit how many jobs we want to display
        $this->db->limit($this->limit_front_page);
        $this->db->join('users', 'jobs.owner_id = users.cnt');
        $q = $this->db->get('jobs');

        $view_data['jobs_table'] = $q->result_array();

        $this->db->select("id, description");
        $q = $this->db->get('agents');
        $view_data['agents'] = $q->result_array();

        // Fetch the filesets available for this user to use
        $view_data['fileset_scan']  = $this->global_functions->get_user_allowed_repositories_details();
        $this->load->view('jobs_general',$view_data);
    }
    public function view($id = '', $share_key = '')
    {
        // If empty, redirect to base url!
        if ($id === '')
        {
            redirect('/jobs', 'location', 307);
            return;
        }

        $view_data = array();
        $job = $this->global_functions->jobs_view ($id, $share_key);
        $view_data['job'] = $job;

        // If we received an empty job this means smth went wrong so just redirect the user
        if (empty($job))
        {
            redirect('/jobs', 'location', 307);
            return;
        }
        // Off we go
        $this->load->view('jobs_view', $view_data);
    }
    // This is just the constructor used to display the HTML
    public function add()
    {
        $view_data = array();

        if (!valid_email($this->global_functions->get_user_email()))
        {
            // Not a valid e-mail!
            $this->load->view('profile_needs_update');
            return;
        }
        // Fetch the filesets available for this user to use
        $view_data['fileset_scan']  = $this->global_functions->get_user_allowed_repositories_details();
        $this->load->view('jobs_add', $view_data);
    }
    // This method returns API (json) responses
    public function add_job()
    {
        $yara_rules         = $this->input->post('yara_rules');
        $yara_fileset_scan  = $this->input->post('yara_fileset_scan');
        // Return status from internal function
        echo $this->global_functions->jobs_add($yara_rules, $yara_fileset_scan);
    }
    // This method returns an API (json) responses
    public function restart_job()
    {
        // First of all, let's escape the input!
        $job_id                 = $this->input->post('job_id');
        $yara_fileset_scan_id   = $this->input->post('fileset_scan');
        // If empty, redirect to base url!
        if (is_null($job_id))
        {
            echo $this->global_functions->api_generate_error_json("Please send a valid ID!");
            die();
        }
        // If empty, redirect to base url!
        if (is_null($yara_fileset_scan_id))
        {
            echo $this->global_functions->api_generate_error_json("Please send a valid fileset!");
            die();
        }

        // First, e-mail check
        $yara_user_notify_email    = $this->global_functions->get_user_email();
        if (!valid_email($yara_user_notify_email))
        {
            echo $this->global_functions->api_generate_error_json("Your e-mail is invalid, please update your profile!");
            die();
        }

        // Apply admin / non-admin restrictions for jobs
        // As well as jail / non-jail restrictions
        $this->global_functions->jobs_apply_db_restrictions ();
        // Get the job from the DB!
        $this->db->where('id', intval($job_id));
        $q = $this->db->get('jobs');
        // There should be only one result
        if ($q->num_rows() != 1)
        {
            $this->klsecurity->log('warn', 'jobs/restart_job','User attempted to restart unauthorized job id: '.print_r($job_id, true));
            echo $this->global_functions->api_generate_error_json("Unauthorized access");
            die();
        }
        $job = $q->result_array();
        // Fetching the rules!
        $yara_rules = $job[0]['rules'];

        // Let's check if user is allowed to scan the fileset_scan
        $allowed_repositories = $this->global_functions->get_user_allowed_repositories ();
        // We have 1 result, let's check we are allowed to scan this repository
        $yara_fileset_scan_id = intval($yara_fileset_scan_id);
        if (!in_array($yara_fileset_scan_id, $allowed_repositories, true))
        {
            $this->klsecurity->log('warn', 'jobs/restart_job','User attempted to restart job with unauthorized repository id: '.print_r($yara_fileset_scan_id, true));
            echo $this->global_functions->api_generate_error_json("Unauthorized access!");
            die();
        }
        // User is allowed. Let's fetch the scan_fileset properties
        $q = $this->db->get_where('scan_filesets', array("id" => "".$yara_fileset_scan_id));
        if ($q->num_rows() != 1)
        {
            echo $this->global_functions->api_generate_error_json("Please send a valid fileset!");
            die();
        }
        $entry_fileset_scan = $q->result_array();
        if (!$this->search_quota->user_can_query (1))
        {
            echo $this->global_functions->api_generate_error_json ("Quota error! Your current quota stops you from restarting this job");
            die();
        }
        // Add a new job!
        $this->global_functions->jobs_add_db ($entry_fileset_scan[0]['entry'], $yara_user_notify_email, $yara_rules);
        // Let's decrease the quota
        $this->search_quota->user_decrease_quota(1);
        // TODO: Fix this
        // $this->klsecurity->log('info', 'jobs/restart_job','Restarted job id: '.print_r($id, true));
        // Everything went OK!
        echo $this->global_functions->api_generate_response_json("ok");
    }
    public function delete_job()
    {
        // Only owner and admins can delete jobs
        // First of all, let's escape the input!
        $id = $this->input->post('job_id');
        // If empty, redirect to base url!
        if (is_null($id))
        {
            redirect('/jobs', 'location', 307);
            die();
        }
        // Fetching the action status
        $delete_action = json_decode($this->global_functions->jobs_delete ($id), true);
        if ($delete_action["status"] == "error")
        {
            // Let's see what kind of status msg we got
            if ($delete_action["status_msg"] == "job_invalid_id")
            {
                redirect('/jobs', 'location', 307);
                die();
            }
            else if ($delete_action["status_msg"] == "job_assigned")
            {
                echo "Cannot delete already assigned job!";
                die();
            }
            echo "Cannot delete job";
            die();
        }
        else if ($delete_action["status"] != "ok")
        {
            echo "Invalid delete status. Contact administrators";
            die();
        }
        // No errors!
        redirect('/jobs', 'location', 307);
    }
    public function search_md5s($get_md5 = '')
    {
        // Restring this function only to Admins
        $this->klsecurity->authorized_for_level ($this->klsecurity->auth_admin_level ());

        // Our initial requested md5s array
        $requested_md5s = array();
        if (strcmp ($get_md5, "") != 0)
            array_push($requested_md5s, $get_md5);

        // Check the POST variable and see if user submitted any md5s
        $post_md5s = $this->input->post ('requested_md5s');

        if (!is_null($post_md5s))
            // Merge the 2 arrays
            $requested_md5s += $this->global_functions->multientries_split ($post_md5s);
        // Uniquify the array
        $requested_md5s = array_unique($requested_md5s);

        // Limit number of search_md5s
        if (count ($requested_md5s) > $this->config->item('search_md5s_limit'))
        {
            $this->load->view('jobs_search_error');
            return;
        }

        // Check each entries in order to see if they are valid
        foreach ($requested_md5s as $md5)
        {
            if (!$this->global_functions->valid_md5 ($md5))
            {
                $this->load->view('jobs_search_error');
                return;
            }
        }

        // If we got only 1 POST variable, then redirect user!
        if (count ($requested_md5s) == 1 && !is_null($post_md5s))
        {
            redirect('/jobs/search_md5s/' . $requested_md5s[0], 'location', 307);
            return;
        }

        // We have a valid md5! Let's ask in DB for any jobs hitting this hash!
        // We want only unique results!
        $this->db->distinct ();
        $this->db->select ('`jobs`.*');
        // End sorting for unique results
        $this->db->join  ('jobs_hashes', 'job_id = jobs.id');
        $this->db->or_where ('1 = 2', NULL, false);
        foreach ($requested_md5s as $md5)
            $this->db->or_where ('hash_md5', $md5);
        // Set up the order
        $this->db->order_by("id", "desc");
        // Limit how many jobs we want to display
        $this->db->limit($this->limit_jobs_general);
        $q = $this->db->get('jobs');
        $view_data['jobs_table'] = $q->result_array();

        $this->db->select("id, description");
        $q = $this->db->get('agents');
        $view_data['agents'] = $q->result_array();
        $view_data['requested_md5s'] = $requested_md5s;

        // Fetch the filesets available for this user to use
        $view_data['fileset_scan']  = $this->global_functions->get_user_allowed_repositories_details();
        $this->load->view('jobs_general', $view_data);
    }
}
