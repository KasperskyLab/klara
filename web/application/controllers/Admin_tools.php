<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_tools extends CI_Controller 
{

    // Since there is no admin control, here are the tools allowing anyone 
    // These functions are used to
    // - gen_pass => generate a secure password + the corresponding bcrypt hash 
    // - generate_users => generate user accounts + passwords as well as the relevant SQL statements in order to be inserted in the DB server
    // 

    public function gen_pass()
    {
        $pass = $this->klsecurity->generate_password(20,2);
        $hash = $this->klsecurity->generate_hash($pass);
        var_dump($pass);
        echo "<br>";
        var_dump($hash);
    }

    public function generate_users()
    {
        function generate_user_template ($user_name, $user_pass)
        {
            $klara_location = "https://127.0.0.1/";
            echo "Here are your login details for accessing GReAT KLara system located at $klara_location\n";
            echo "\n* Web login:\n";
            echo "User: $user_name\n";
            echo "Pass: $user_pass\n";
            echo "\n\n------------------------------\n\n";

        }
        function generate_web_user ($user_name, $user_hash)
        {
            return  "INSERT INTO `users` (`cnt`, `username`, `pass`, `auth`, `desc`, `group_cnt`, `notify_email`, `quota_searches`, `quota_curr_month`, `searches_curr_month`, `dateadded`, `ip_last_login`) VALUES".
                    "(NULL, '".$user_name."', '".$user_hash."', '2', 'Description', '1', '".$user_name."', '1000', '', '0', CURRENT_TIMESTAMP, '0');\n";

        }
        // List of usernames to generate SQL statements 
        $users_emails = array();

        // Iterate through all e-mails and generate web users
        $sql_stmts = "";
        foreach ($users_emails as $user_name)
        {
            $user_pass = $this->klsecurity->generate_password(20,2);
            $user_hash = $this->klsecurity->generate_hash($user_pass);
            generate_user_template ($user_name, $user_pass);
            // And now the statement
            $sql_stmts .= generate_web_user ($user_name, $user_hash);
        }

        echo "\n\n<------------->\n\n";
        echo $sql_stmts;
    }
}
