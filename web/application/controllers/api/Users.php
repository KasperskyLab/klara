<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users extends CI_Controller 
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "Welcome Users API";
    }

    /*
    This function adds a new user with provided info.

    * Auth is restricted to be <= poweruser (aka 8)

    Required POST variables:
        - username
        - password
        - auth
        - description
        - group_cnt
        - quota_searches
        - notify_email
    */
    public function add()
    {
        $username       = $this->input->post('username');
        $password       = $this->input->post('password');
        $auth           = intval($this->input->post('auth'));
        $description    = $this->input->post('description');
        $group_cnt      = intval($this->input->post('group_cnt'));
        $quota_searches = intval($this->input->post('quota_searches'));
        $notify_email   = $this->input->post('notify_email');


        // Starting validations

        if (!is_string($username) || $username === '')
        {
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid username"));
        }

        if (!is_string($password) || $password === '')
        {
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid password"));
        }

        if (!is_numeric($auth) || $auth == 0)
        {
            $this->klsecurity->log('warn','api/users/add','Received invalid auth: '. $auth);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid auth"));
        }

        if ($auth > $this->klsecurity->auth_poweruser_level())
        {
            $this->klsecurity->log('warn','api/users/add','Request tried to add user with auth lvl > auth_poweruser_level: '. $auth);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid auth"));
        }

        if (!is_string($description) || $description === '')
        {
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid description"));
        }
        if (!is_numeric($group_cnt) || $group_cnt == 0)
        {
            $this->klsecurity->log('warn','api/users/add','Received invalid group_cnt: '. $group_cnt);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid group_cnt"));
        }
        // Check existence for group cnt
        $this->db->select('cnt');
        $this->db->where('cnt', $group_cnt);
        $q = $this->db->get('users_groups');
        $results_cnt = $q -> num_rows();
        if ($results_cnt != 1)
        {
            $this->klsecurity->log('warn','api/users/add','Attempt to add user to non-existing group: '. $username);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Group doesn't exist"));
        }

        if (!is_numeric($quota_searches) || $quota_searches == 0)
        {
            $this->klsecurity->log('warn','api/users/add','Received invalid quota_searches: '. $quota_searches);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid quota_searches"));
        }
        if (!is_string($notify_email) || $notify_email === '')
        {
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Invalid notify_email"));
        }
        // Finished validation

        // Check if this username already exists
        $this->db->select('cnt');
        $this->db->where('username', $username);
        $q = $this->db->get('users');
        $results_cnt = $q ->num_rows();
        if ($results_cnt > 0)
        {
            $this->klsecurity->log('warn','api/users/add','Attempt to insert already existing user: '. $username);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Username already exists"));
        }
        // Insert the user in DB, return ID
        $insert_array = array(
            'username' => $username,
            'pass' => $this->klsecurity->generate_hash($password),
            'auth' => $auth,
            'desc' => $description,
            'group_cnt' => $group_cnt,
            'quota_searches' => $quota_searches,
            'notify_email' => $notify_email,
        );

        $this->db->insert('users', $insert_array);
        $query_effected_rows = $this->db->affected_rows();

        if ($query_effected_rows != 1)
        {
            $this->klsecurity->log('warn','api/users/add','Failed insert query with values: '. print_r($insert_array, true));
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("Addition failed"));
        }

        $last_id = $this->db->insert_id();
        $this->klsecurity->api_finish_request_output(
            $this->global_functions->api_generate_response_json("ok", '', array("id" => $last_id))
        );
    }

    /*
    This function can be used to disable users.
    Administrators cannot be disabled

    */
    public function disable()
    {
        $user_cnt = intval($this->input->post('user_cnt'));


        // Check if this username already exists
        $this->db->select('auth');
        $this->db->where('cnt', $user_cnt);
        $q = $this->db->get('users');
        $results_cnt = $q -> num_rows();
        if ($results_cnt != 1)
        {
            $this->klsecurity->log('warn','api/users/add','Attempt to disable non existing user cnt: '. $user_cnt);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("User cannot be disabled"));
        }

        // Make sure user is not admin
        $results = $q -> result_array();
        $auth = intval($results[0]['auth']);

        if ($auth > $this->klsecurity->auth_poweruser_level())
        {
            $this->klsecurity->log('warn','api/users/add','Request tried to add user with auth lvl > auth_poweruser_level: '. $auth);
            $this->klsecurity->api_finish_request_output(
                $this->global_functions->api_generate_error_json("User cannot be disabled"));
        }

        $data = array (
            'auth' => 1
        );

        $this->db->where('cnt', $user_cnt);
        $this->db->update('users', $data);
        // Finish it
        $this->klsecurity->api_finish_request_output(
            $this->global_functions->api_generate_response_json("ok")
        );

    }

}
