<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends CI_Controller 
{
    // Access level: auth_registered_level
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $view_data = array();
        $this->load->view('profile', $view_data);
    }

    public function update()
    {
        // We want to update the notify_email as provided (or not!) by the user
        $email = $this->input->post('yara_user_email');

        // Let's see if the email is valid or not!
        if (!valid_email($email))
        {
            echo json_encode(array('status' => 'error', 'status_msg' => 'Please input a valid e-mail address'));
            die();
        }

        // Update e-mail address user sent us
        $update_status = $this->global_functions->update_email_current_user($email);

        if ($update_status)
            // Everything went OK!
            echo json_encode(array("status" => "ok", "status_msg" => ""));
        else
        {
            $this->CI->klsecurity->log('warn','profile/update','User requested e-mail for invalid user id: '.print_r ($user_id, true));
            echo json_encode(array("status" => "error", "status_msg" => "Error updating your e-mail"));
        }

        return;

    }
}
