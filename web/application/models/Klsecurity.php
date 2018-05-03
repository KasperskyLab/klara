<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Klsecurity extends CI_Model
{
    /*
        WARNING:
        - this model is loaded only when a valid controller is called.
        - if an invalid controller is requested (GET/POST) then 404 page will kick in
        - some external API requests are routed in config/router.php

        User auth is as follows:
        0   - disabled      - Unauthenticated user
        1   - suspended     - User suspended from system
        2   - registered    - User allowed to view/add jobs, quotas enforeced
        4   - observer      - Not used
        8   - poweruser     - User allowed to view/add jobs, quotas disabled
        16  - Admin         - God
    */
    private $auth_disabled    = 0;
    private $auth_suspended   = 1;
    private $auth_registered  = 2;
    private $auth_observer    = 4;
    private $auth_poweruser   = 8;
    private $auth_admin       = 16;
    // Centralized set of permissions for controllers
    private $controller_perms = array();

    public function __construct()
    {
        // Centralized set of permissions for each controller
        $this->controller_perms = array(
            "/login"                    => $this->auth_disabled_level(),

            "/profile"                  => $this->auth_registered_level(),
            "/jobs"                     => $this->auth_registered_level(),

            "/advanced_search"          => $this->auth_admin_level(),
            // This is just for admins
            "/admin_tools"              => $this->auth_admin_level()
        );
        // Centralized set of controllers that are external API endpoint
        $this->api_controllers = array(
            "/api/jobs"
        );

        // Auth logic starts here!!
        $controller_full_path   = $this->get_curr_controller_full_path();
        // Default controller restriction is admin lvl
        $this_controller_restriction = $this->auth_admin_level();
        $user_carry_on = false;
        // Here we want to check if the current user has permissions to invoke the controller
        // Let's check if we find the controller in the list.
        if (array_key_exists($controller_full_path, $this->controller_perms))
        {
            $this_controller_restriction = $this->controller_perms[$controller_full_path];
            $this->authorized_for_level($this_controller_restriction);
            // If we got there, then every-thing's fine!
            $user_carry_on = true;
        }
        // If the full path is not in the list of controllers, maybe it's an API endpoint
        else if (in_array($controller_full_path, $this->api_controllers, true))
        {
            // Indeed is an API request!

            // Authenticate the user
            // Check if the auth_code exists!
            $auth_code = $this->input->post('auth_code');
            if (is_null($auth_code))
            {
                $this->log('warn', 'klsecurity','[API] No auth_code POST variable received');
                echo $this->global_functions->api_generate_error_json("not_authorized");
                $this->api_finish_request();
            }
            // Check if $auth_code is in our DB
            // We want to force string conversion
            $this->db->where('api_auth_code', "".$auth_code);
            $this->db->where('api_status', '1');
            // We should get only 1 result
            $this->db->limit(1);
            $res = $this->db->get('users');

            if ($res->num_rows() == 1)
            {
                $row = $res->result_array();
                $row = $row[0];
                // Valid API request!
                // We now check if the user is allowed to access the requested method
                $requested_method = $controller_full_path . "/" . $this->get_requested_method();
                // Check its permissions
                $allowed_methods = json_decode($row['api_perms']);
                if (in_array($requested_method, $allowed_methods, true) ||
                    in_array("all",             $allowed_methods))
                {
                    // CI tries to set up the session data. We don't need to show it to API end-points
                    header_remove("Set-Cookie");
                    // Client is allowed to request this method!
                    // Let's set up his session
                    $data = array (
                            // We only have user_id so we don't break the existing function calls
                            // We have a static user in the `users` table special for API
                            'username'      => $row['username'],
                            'user_id'       => $row['cnt'],
                            'user_auth'     => $row['auth'],
                            'dateadded'     => $row['dateadded'],
                            // We mark it as api request
                            'api_user_id'   => $row['cnt'],
                            'api_request'   => true
                        );
                    // Initializing session for the user
                    $this->session->set_userdata($data);
                    // CI tries to set up the session data after we updated it.
                    // We don't need to show it to API end-points, again
                    header_remove("Set-Cookie");
                    $user_carry_on = true;
                }
                else
                {
                    $this->log('warn', 'klsecurity','[API] User '.$row['cnt'].' tried to access unauthorized method '.$requested_method);
                    echo $this->global_functions->api_generate_error_json("not_authorized");
                    $this->api_finish_request();
                }
            }
            // If we got 0 results, then we generate an error
            else
            {
                $this->log('warn', 'klsecurity','[API] auth_code not found in DB: ['.$auth_code."]");
                echo $this->global_functions->api_generate_error_json("not_authorized");
                $this->api_finish_request();
            }
        }
        // Valid controller is not in list of controllers, nor an API endpoint.
        else
        {
            $this->log('error', 'klsecurity', "User requested valid controller $controller_full_path but no ACL for it");
            // Valid controller is missing ACL restrictions. We invalidate the session
            $user_carry_on = false;
            $this->redirect_destroy_session();
        }

        // Ultimate safety check
        if (!$user_carry_on)
            $this->redirect_destroy_session();
        // User is allowed to view this page. Carry on!
    }
    public function get_curr_controller_full_path()
    {
        $current_controller     = $this->router->class;
        $current_directory      = $this->router->directory;

        return "/".$current_directory.$current_controller;
    }
    public function get_requested_method()
    {
        return $this->router->method;
    }
    // Function returns auth user name
    // It assumes that the caller is expecting an user name so will fail
    // if the session variable doesn't exist
    public function get_auth_username()
    {
        if (is_null($this->session->userdata('username')))
        {
            $this->log('error','klsecurity/get_auth_username','Tried to fetch username, but was not assigned in session');
            $this->redirect_destroy_session();
        }
        else
            return $this->session->userdata('username');
    }
    // Function returns auth user ID
    // It assumes that the caller is expecting an user ID so will fail 
    // If the session variable doesn't exist
    public function get_auth_userid()
    {
        if (is_null($this->session->userdata('user_id')))
        {
            $this->log('error','klsecurity/get_auth_userid','Tried to fetch user_id, but was not assigned in session');
            $this->redirect_destroy_session();
        }
        else
            return intval($this->session->userdata('user_id'));
    }

    /* User Authorization Functions */
    // If the user_auth is NOT >= than the selected level
    // kick out the user!
    // If no argument given, considering the lowest auth possible

    public function authorized_for_level($level = -1)
    {
        // Minimum auth level is the Guest one
        if ($level == -1)
            $level = $this->auth_disabled_level();

        // If this function returns NULL, then this user has no auth level,
        // So we assign it the lowest possible
        if (is_null($this->session->userdata('user_auth')))
        {
            $user_access_level = $this->auth_disabled_level();

            // If the user tries to access one level which is > $this->auth_disabled_level()
            // and he doesn't have the 'user_auth' session variable, this means that he shouldn't be here,
            // BUT maybe his session might have expired.
            // So we redirect him, without logging
            // This feature is temporarily disabled
            // $this->redirect_destroy_session();
        }
        else
            $user_access_level = $this->session->userdata('user_auth');
        // If the user access level is LOWER than the level we're asking, reject!
        if ( $user_access_level < $level)
        {
            // TODO: here we should add controller name as well
            $this->log('alert', 'klsecurity', 'User with lvl '.$user_access_level.' tried to access unauthorized level '. $level);
            $this->redirect_destroy_session();
        }
        // The user is authorized .. let him carry on
    }
    // Function returns TRUE of FALSE if user is allowed to acess
    // the $link_path provided
    public function link_visible_for_user($link_path  = '')
    {
        $curr_user_acces_level = $this->current_auth_lvl();
        if (array_key_exists($link_path, $this->controller_perms))
        {
            // This link exists in our list of controllers
            // Let's check if the current user has privilege to view it!
            if ($curr_user_acces_level >= $this->controller_perms[$link_path])
                return true;
            else
                return false;
        }
        // else return false
        return false;
    }
    // Destroy the user's session and redirect him to /
    public function redirect_destroy_session()
    {
        $this->session->sess_destroy();
        redirect('/login');
        die();
    }
    // Cleaning up an API request
    public function api_finish_request()
    {
        $this->api_clear_session();
        die();
    }
    // Cleaning up an API request and printing a message before exiting
    public function api_finish_request_output($output)
    {
        $this->api_clear_session();
        echo $output;
        die();
    }
    public function api_clear_session()
    {
        $this->session->sess_destroy();
        header_remove("Set-Cookie");
    }
    // Function determining if this is an API request
    public function api_request()
    {
        if (is_null($this->session->userdata('api_request')))
        {
            return false;
        }
        // Else return this variable's value
        return $this->session->userdata('api_request');
    }
    // Return the Disabled auth lvl
    public function auth_disabled_level()
    {
        return $this->auth_disabled;
    }
    // Returns the Suspended auth lvl
    public function auth_suspended_level()
    {
        return $this->auth_suspended;
    }
    // Returns the Registered auth lvl
    public function auth_registered_level()
    {
        return $this->auth_registered;
    }
    // Returns the Observer auth lvl
    public function auth_observer_level()
    {
        return $this->auth_observer;
    }
    // Return the Poweruser auth lvl
    public function auth_poweruser_level()
    {
        return $this->auth_poweruser;
    }
    // Return the Admin auth lvl
    public function auth_admin_level()
    {
        return $this->auth_admin;
    }
    public function current_auth_lvl()
    {
        // IF the user_auth session data doesn't exist, return disabled user
        if (!$this->user_is_authenticated())
            return $this->auth_disabled_level();
        return intval($this->session->userdata('user_auth'));
    }
    public function user_is_authenticated()
    {
        return $this->session->userdata('user_auth') !== NULL;
    }
    // Returns TRUE if this user is registered
    public function user_is_registered()
    {
        return $this->current_auth_lvl() >= $this->auth_registered_level();
    }
    // Return TRUE if user is observer
    public function user_is_observer()
    {
        return $this->current_auth_lvl() >= $this->auth_observer_level();
    }
    // Return TRUE if user is poweruser
    public function user_is_poweruser()
    {
        return $this->current_auth_lvl() >= $this->auth_poweruser_level();
    }
    // Returns TRUE if this user is admin
    public function user_is_admin()
    {
        return $this->current_auth_lvl() >= $this->auth_admin_level();
    }
    /* End Authorization Functions */
    
    /* Password Functions */
    public function generate_password($length = 16, $level = 3)
    {
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));

        $valid_chars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
        $valid_chars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXZY";
        $valid_chars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxzyABCDEFGHIJKLMNOPQRSTUVWXZY_!@#$%&*()-=+/";

        $password  = "";
        $count     = 0;

        while ($count < $length)
        {
            $found_char = substr($valid_chars[$level], rand(0, strlen($valid_chars[$level])-1), 1);
            // All character must be different
            if (!strstr($password, $found_char))
            {
                $password .= $found_char;
                ++ $count;
            }
        }
        return $password;
    }
    // This function receives a plaintext password and generates the 
    // BCRYPT hash for it. 
    // It returns string OR FALSE!
    public function generate_hash ($pass = '')
    {
        // I don't really trust the PASSWORD_DEFAULT constant in PHP. 
        // As such, we stick to PASSWORD_BCRYPT
        $new_password = password_hash($pass, PASSWORD_BCRYPT);
        if ($new_password === FALSE)
        {
            $this->log('alert', 'klsecurity', '1st attempt failed to generate hash for password '. $pass);
            // Weird, we failed to generate the pass,
            // Trying again
            $new_password = password_hash($pass, PASSWORD_BCRYPT);
        }
        return $new_password;
    }
    // Returns TRUE of FALSE if the password matches the hash
    public function compare_hash ($input, $hash)
    {
        return password_verify($input, $hash);
    }
    /* End Password Functions */

    // function xss_clean($input)
    // {
    //     // Do stuff..
    //     $returned_filtered_string = $this->security->xss_clean($input);
    //     if (strcmp($input, $returned_filtered_string) !== 0)
    //         // Different output! Smth is wrong
    //         $this->log('warning', 'xss_clean', 'Got different results!')
    //     // Log stuff
    //     return $returned_filtered_string;
    // }

    public function log ($type = 'info', $module = 'undefined', $message = '', $user_id = -1)
    {
        /* Log type 
        - info - for information, statistics, successful logins and changes, etc.
        - alert - for failed logins, failed data
        - warning - for errors or actions that users should not be allowed to do
        - error - for important errors which can affect the stability of the app
        */

        // If the user ID is not set by the calling function we get it from the user data. 
        // If the user data is not set in session as well the user id is set to -1
        if ($user_id == -1 && !is_null($this->session->userdata('user_id')))
            $user_id = $this->session->userdata('user_id');
        // If we are at an API endpoint, maybe we can find the user_id from the session
        if ($this->session->userdata('api_request') === TRUE && !is_null($this->session->userdata('api_user_id')))
        {
            $user_id = $this->session->userdata('api_user_id');
            // We append the message with API
            $message = "[API] ".$message;
        }
            
        $data['type']       = $type;
        $data['module']     = $module;
        $data['data']       = $message;
        if (is_cli())
        {
            $data['ip']     = 0;
            $data['data']   = "[CLI] ".$data['data'];
        }
        else
            $data['ip']         = ip2long($this->input->ip_address());
        $data['user_id']    = $user_id;
        
        // Check if we are in sinkhole mode
        $ci_logs = 'ci_logs';

        $this->db->insert($ci_logs, $data);
    }
}
