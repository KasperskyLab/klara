<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller 
{
    // Access level: auth_disabled_level
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        // TODO: what is this about?
        $this->output->enable_profiler(FALSE);
    }

    // User logged-in successfully
    function __successful_login()
    {
        $this->klsecurity->log('info','login','User logged in successfully');

        $data['msg'] = "";
        $data['status'] = 200;
        echo json_encode($data);

    }
    function __invalid_login($username = "")
    {
        $this->klsecurity->log('alert', 'login', "Failed auth. [ Username : {$username} ]");

        $data['msg'] = "Invalid username or password";
        $data['status'] = 401;
        echo json_encode($data);
    }

    function __invalid_login_warning($username = "")
    {
        $this->klsecurity->log('warn', 'login', "More than 2 results at auth. [ Username : {$username} ]");

        $data['msg'] = "Invalid username or password";
        $data['status'] = 401;
        echo json_encode($data);
    }

    function __forbidden_login($username = "" , $id = -1)
    {
        $this->klsecurity->log('alert', 'login', "Blocked user tried to login [ Username: {$username} ]");

        $data['msg']  = '<span style="color:red;">Your account is no longer active. Perhaps your trial period has expired.</span><br><br>';
        $data['msg'] .= 'Should you want to extend your access please contact the person who provided you access';
        $data['status'] = 403;
        echo json_encode($data);
    }

    // Actual function doing login part
    function check()
    {
        // Get the login infos from the form post
        $post_username = $this->input->post('username');
        $post_password = $this->input->post('password');

        // If there is an user who just landed on the login page, redirect to home
        if (is_null($post_username) || is_null($post_password))
        {
            $this->__invalid_login($post_username);
            return;
        }

        // TODO : check for a lot of retries. Insert reCAPTCHA
        $this->form_validation->set_rules('username', 'Username', 'required|min_length[3]|max_length[45]');
        $this->form_validation->set_rules('password', 'Password', 'required');

        //Validating username
        if ($this->form_validation->run() == FALSE)
        {
            $data['msg'] = validation_errors();
            $data['status'] = 412 ;
            echo json_encode($data);
            return;
        }
        else
        {
            $users_table = 'users';

            // Getting the user whose username is the one submitted
            $this->db->where('username', $post_username);
            $query = $this->db->get($users_table);

            //If there is only one result to avoid SQL injections when they can select the whole table
            if ($query->num_rows() == 0)
            {
                $this->__invalid_login($post_username);
            }
            else if ( $query->num_rows() > 1)
            {
                // We got more than 2 results!?
                $this->__invalid_login_warning($post_username);
            }
            else
            {
                // We have only 1 record with the supplied username. Time to check the password
                $row = $query->result_array();
                $row = $row[0];
                // Check if the passwords match
                if ( ! $this->klsecurity->compare_hash ($post_password, $row['pass']))
                {
                    $this->__invalid_login ($row['username']);
                    return;
                }
                else
                {
                    // Passwords indeed match!
                    if (intval($row['auth']) <= $this->klsecurity->auth_suspended_level ())
                    {
                        $this->__forbidden_login ($row['username'], $row['cnt']);
                        return;
                    }
                    else
                    {
                        // User is legit, log him in!
                        $my_last_ip = $row['ip_last_login'];
                        //If new user, set the message
                        if ($my_last_ip == "0")
                            $my_last_ip = "N/A<br><strong>This is your first login, welcome!</strong>";
                        else
                            $my_last_ip = long2ip($my_last_ip);

                        //Updating the DB with the new IP
                        $change['ip_last_login'] = ip2long($this->input->ip_address());
                        $this->db->where ('cnt', $row['cnt']);
                        // The same variable defined above
                        $this->db->update ($users_table, $change);

                        // We now set the session info.
                        // This is EXTREMELY important because here is the ONLY place where we
                        // set the user_auth from DB.
                        // If the user finds an exploit to change his user_auth session variable, then it's game over
                        // cause he can set himself as ADMIN
                        $data = array (
                                'username'      => $row['username'],
                                'user_id'       => $row['cnt'],
                                'user_auth'     => $row['auth'],
                                'ip'            => $my_last_ip,
                                'api_request'   => false,
                                'dateadded'     => $row['dateadded']
                            );
                        // Initializing session for the user
                        $this->session->set_userdata($data);
                        // We don't send the user info as a param any more, cause it's in the session
                        $this->__successful_login();
                        return;
                    }
                }
            }
        }
    }

    public function index()
    {
        $view_data = array();

        // If user is authenticated already, redirect to ('/show')
        if ($this->klsecurity->user_is_authenticated())
            redirect('jobs');
        else 
            $this->load->view('login');
    }

    public function logout()
    {
        $this->klsecurity->redirect_destroy_session();
    }
}
