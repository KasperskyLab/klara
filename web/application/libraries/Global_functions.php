<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Global_functions 
{
    public function __construct()
    {
        $this->CI               =& get_instance();
        $this->web_app_version  = trim(file_get_contents(FCPATH."version.txt"));
        // Separator for scan_filesets
        $this->scan_filesets_separator  = " + ";

        // Maximum size for repository name
        $this->repository_post_max_len  = 20;
        // Maximum rule name length when limiti
        $this->rule_name_start_trimm = 64;
    }

    // This function takes an extra third parameter which can be null or not
    // It doesn't break the behavior of existing AJAX requests
    public function api_generate_response_json($status = '', $status_msg = '', $return_data = null)
    {
        return json_encode ($this->api_generate_response($status, $status_msg, $return_data));
    }
    // Same as above, but generates an error
    public function api_generate_error_json($error = '')
    {
        return $this->api_generate_response_json("error", $error);
    }
    // Template to generate an API response
    // Warning: it's caller's job to set the status accordingly AND also the return_data.
    // Callees should check the status first, before trying to access return_data!
    public function api_generate_response($status = '', $status_msg = '', $return_data = null)
    {
        $result = array(
            "status" => $status,
            "status_msg" => $status_msg,
        );
        // If return_data is not null we include in our return
        if (!is_null($return_data))
            $result['return_data'] = $return_data;

        return $result;
    }
    // Function used to split a string into tokens if they are delimited by
    // \s (<=> " ",  \r, \t, \n and \f), comma, or a new line
    public function multientries_split ($entries = '')
    {
        return preg_split("/[\s,\n]+/",$entries, -1, PREG_SPLIT_NO_EMPTY);
    }
    // Function used to trim a given string if it is longer than default 64 chars
    public function string_trim($string, $chars_limit = 64)
    {
        if (strlen($string) < $chars_limit)
            return $string;
        return substr($string, 0, $chars_limit)."[..]";
    }
    public function current_user_owns_job($job_owner_id = '')
    {
        // If user is admin, he owns the job
        if ($this->CI->klsecurity->user_is_admin())
            return true;
        // Else his user_id needs to be the same as job's owner id
        return strcmp($job_owner_id, $this->CI->klsecurity->get_auth_userid()) == 0 ? true : false;
    }
    // Updates the given email.
    // Returns true if email is valid and update successful, or false otherwise
    public function update_email_current_user($email = '')
    {
        if (!valid_email($email))
            return false;

        $notify_email_array = array('notify_email' => $email);
        $id_array           = array('cnt' => $this->CI->klsecurity->get_auth_userid());
        $this->CI->db->update ('users', $notify_email_array, $id_array);
        return true;
    }
    // user_id argument for this function might be null - in this case we fetch
    // the current auth_user_id
    public function get_user_email($user_id = null)
    {
        // If user_id is null, let's fetch the current user_id
        if (is_null($user_id))
            $user_id = $this->CI->klsecurity->get_auth_userid();
        // Else the caller will give us the user_id
        // Try to get the email
        $this->CI->db->select('notify_email');
        $this->CI->db->where('cnt', "".$user_id);
        $q = $this->CI->db->get('users');

        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_email','User requested e-mail for invalid user id: '.print_r ($user_id, true));
            return "";
        }

        // We have 1 result. Let's see it!
        $entry = $q->result_array();
        return $entry[0]['notify_email'];
    }

    // This function will return the group_cnt assigned to
    // an user_id (if specified) or to the current user (if blank)
    // It returns -1 if the user_id is invalid or doesn't exist
    public function get_user_group_cnt($user_id = null)
    {
        // If user_id is null, let's fetch the current user_id
        if (is_null($user_id))
            $user_id = $this->CI->klsecurity->get_auth_userid();
        // Else the caller will give us the user_id
        // Try to get the group cnt
        $this->CI->db->select('group_cnt');
        $this->CI->db->where('cnt', "".$user_id);
        $q = $this->CI->db->get('users');

        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_group_cnt','User requested group for invalid user id: '.print_r ($user_id, true));
            return -1;
        }

        // We have 1 result. Let's see it!
        $entry = $q->result_array();
        return intval($entry[0]['group_cnt']);
    }
    // This function will return the group_name assigned to
    // an user_id (if specified) or to the current user (if blank)
    // It returns "" if the user_id is invalid or doesn't exist
    public function get_user_group_name($user_id = null)
    {
        // We pass the argument directly to get_user_group_cnt
        $user_group_id = $this->get_user_group_cnt ($user_id);

        // Else the caller will give us the user_id
        // Try to get the group cnt
        $this->CI->db->select('name');
        $this->CI->db->where('cnt', "".$user_group_id);
        $q = $this->CI->db->get('users_groups');

        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_group_name','User requested invalid group cnt: '.print_r ($user_id, true));
            return -1;
        }

        // We have 1 result. Let's see it!
        $entry = $q->result_array();
        return $entry[0]['name'];
    }
    // This function returns an array of allowed repository IDs
    // for a specific user or current_user
    // Example output: array(3,4,5);
    public function get_user_allowed_repositories($user_id = null)
    {
        // We pass the argument directly to get_user_group_cnt
        $user_group_id = $this->get_user_group_cnt ($user_id);
        // From this group id, extract the allower scan_filesets_list
        $this->CI->db->select('scan_filesets_list');
        $this->CI->db->where('cnt', "".$user_group_id);
        $q = $this->CI->db->get('users_groups');

        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_allowed_repositories','User group doesn\'t exist: '.print_r ($user_group_id, true));
            return array();
        }
        // We now have an aswer
        // We have 1 result. Let's see it!
        $entry = $q->result_array();
        $scan_filesets_json     = $entry[0]['scan_filesets_list'];
        $scan_filesets_array    = json_decode($scan_filesets_json);
        if (is_null($scan_filesets_array))
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_allowed_repositories','scan_fileset_list JSON invalid: '.print_r ($scan_filesets_json, true));
            return array();
        }
        return $scan_filesets_array;
    }
    // This function will return true or false whether
    // an user_id (if specified) or the current user (if blank)
    // is jailed by the group policies
    // By default it returns true if the user_id is invalid or doesn't exist
    public function get_user_group_jailed($user_id = null)
    {
        // We pass the argument directly to get_user_group_cnt
        $user_group_id = $this->get_user_group_cnt ($user_id);
        // Else the caller will give us the user_id
        // Try to get the group cnt
        $this->CI->db->select('jail_users');
        $this->CI->db->where('cnt', "".$user_group_id);
        $q = $this->CI->db->get('users_groups');

        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn','global_functions/get_user_group_jailed','User requested invalid group cnt: '.print_r ($user_id, true));
            return true;
        }

        // We have 1 result. Let's check it
        $entry = $q->result_array();
        // By default we consider all users as being jailed, unless stated by group policy!
        if ($entry[0]['jail_users'] === '0')
            return false;
        return true;
    }
    public function get_user_allowed_repositories_details($user_id = null)
    {
        $allowed_repositories = $this->get_user_allowed_repositories ($user_id);

        // Initially don't allow any repositories to be presented to user
        $this->CI->db->where('1 = 2', null, FALSE);
        // From DB repositories, filter the ones allowed for user
        foreach ($allowed_repositories as $repo_id)
        {
            $this->CI->db->or_where ('id', $repo_id);
        }
        $this->CI->db->order_by('entry', 'asc');
        // Let's fetch the results!
        $q = $this->CI->db->get('scan_filesets');
        return $q->result_array();
    }

    /******* Jobs Functions *******/
    // This function is being called multiple times in this controller
    // It needs to be followed by DB select statements
    public function jobs_apply_db_restrictions ()
    {
        // Admins can see all jobs
        if (!$this->CI->klsecurity->user_is_admin())
        {
            $auth_user_id   = $this->CI->klsecurity->get_auth_userid ();
            $user_is_jailed = $this->CI->global_functions->get_user_group_jailed ();
            $auth_group_id  = $this->CI->global_functions->get_user_group_cnt ();

            // If user is jailed, we only allow seeing his own jobs
            if ($user_is_jailed)
                $this->CI->db->where('owner_id', "" . $auth_user_id);
            else
                // User is not jailed! We limit the query to just jobs from his current group!
                $this->CI->db->where('owner_group_id', "" . $auth_group_id);
        }
        return;
    }
    public function jobs_add ($yara_rules, $yara_fileset_scan, $return_jobs_ids = false)
    {
        // Let's do various checks
        if (!is_bool($return_jobs_ids))
            $return_ids = false;
        if ($yara_rules === NULL || $yara_fileset_scan === NULL)
            return $this->api_generate_error_json("Please input yara rules and repositories to scan");

        // Check if the user submitted a rule
        if (!is_string($yara_rules) || strlen($yara_rules) <= 0)
            return $this->api_generate_error_json("Please submit a valid Yara rule");

        // Check if submitted yara rules contain non-ASCII chars
        if (!mb_check_encoding ($yara_rules, 'ASCII'))
        {
            // Rule has non-ASCII chars in it! Trying to identify the location
            // Go till you find at most 30 ASCII chars, before the non-ASCII char, capture the 30 chars
            $match_before_count = preg_match('/^.*?([\x00-\x7F]{0,30})[^\x00-\x7F]/si',     $yara_rules, $match_before);
            $match_after_count  = preg_match('/^.*?[^\x00-\x7F]+([\x00-\x7F]{0,30})/si',    $yara_rules, $match_after);
            if ($match_before_count == 1 && $match_after_count == 1)
            {
                $message_before = $this->CI->security->xss_clean($match_before[1]);
                if ($message_before === "")
                    $message_before = "[Beginning of Yara rule]";
                $message_after  = $this->CI->security->xss_clean($match_after[1]);
                if ($message_after === "")
                    $message_after = "[End of Yara rule]";
                // Adding PRE field, as well as filtering for xss
                // Check if API call and return a different msg
                if ($this->CI->klsecurity->api_request())
                    $non_ascii_message =    "Rule contains non-ASCII chars between '" . $message_before .
                                            "' AND '" . $message_after . "'";
                else
                    $non_ascii_message =    "Rule contains non-ASCII chars between <pre>".
                                        $message_before . "</pre> AND <pre>".
                                        $message_after . "</pre>";
                return $this->api_generate_error_json($non_ascii_message);
            }
            else
                return $this->api_generate_error_json("Rule contains non-ASCII chars. Please retry");
        }

        if (!is_array($yara_fileset_scan))
            return $this->api_generate_error_json("Invalid fileset scan");

        // We want all entries to be strings!
        foreach ($yara_fileset_scan as $repo_id)
        {
            // Each entry needs to be an int
            if (!is_string($repo_id))
            {
                $this->CI->klsecurity->log('warn', 'jobs/add_job','Invalid repo ID received: '.print_r($repo_id, true));
                return $this->api_generate_error_json("Invalid fileset scan");
            }
        }
        $user_notify_email      = $this->get_user_email();
        if (!valid_email($user_notify_email))
            return $this->api_generate_error_json("Your e-mail is invalid, please update your profile!");

        // E-mail seems to be fine!
        $allowed_repositories   = $this->get_user_allowed_repositories();
        // Multiple repositories can be selected now!
        $final_repositories = array();
        // First of all, convert the "special" entries into normal values
        // We know that our post variable is an array containing ONLY strings with length < 10;
        // Convert our special repositories into normal ids
        foreach ($yara_fileset_scan as $repo)
        {
                $final_repositories[] = $repo;
        }
        // Uniquify the list
        $final_repositories     = array_unique($final_repositories, SORT_STRING);
        // Repositories users is allowed to access:
        $allowed_repositories   = $this->get_user_allowed_repositories();
        // Intersect the allowed_repositories to $final_repositories
        $final_repositories     = array_intersect($allowed_repositories, $final_repositories);

        // If there is no data in $final_repositories then user asked to scan some unauthorized repos
        // log this action and return "error"
        if (count ($final_repositories) < 1)
        {
            $this->CI->klsecurity->log('warn', 'jobs/add_job','User tried to scan unauthorized repos: '.print_r($final_repositories, true));
            return $this->api_generate_error_json("unautorized_repos");
        }

        // Check if user has scan quotas to scan as many repositories as needed
        // NOTICE: some repositories might contain multiple scan_paths separated by ;
        // Decided to decrease the quota by the number of scan_paths IDs requested,
        // and not by the real, effective scan_paths (or how many yara scans will be made)
        $total_requested_scan_paths = count ($final_repositories);
        if (!$this->CI->search_quota->user_can_query ($total_requested_scan_paths))
        {
            $quota_msg = "Quota error! Your current quota stops you from adding ". $total_requested_scan_paths . " job(s)";
            return $this->api_generate_error_json ($quota_msg);
        }

        $jobs_ids = array();
        // If each entry in $final_repositories is in our DB, find its path
        foreach ($final_repositories as $repo_id)
        {
            // We have checked that:
            // 1) the yara repo is in our DB
            // 2) the user is allowed to scan this fileset
            $q = $this->CI->db->get_where('scan_filesets', array("id" => "".$repo_id));
            if ($q->num_rows() == 1)
            {
                $ans = $q->result_array();
                // We only have 1 result
                $initial_repo_scan_fileset = $ans[0]['entry'];
                $returned_ids = $this->jobs_add_db ($initial_repo_scan_fileset, $user_notify_email, $yara_rules);
                // Add to our array the IDs
                $jobs_ids = array_merge($jobs_ids, $returned_ids);
            }
            else
                $this->CI->klsecurity->log('warn', 'jobs/add_job','Unknown repo ID received: '. print_r($repo_id, true));
        }

        // Let's decrease the quota
        $this->CI->search_quota->user_decrease_quota($total_requested_scan_paths);
        // Everything went fine.
        // Sometimes we also want to inserted IDs
        if ($return_jobs_ids)
            return $this->api_generate_response_json("ok",'', $jobs_ids);
        return $this->api_generate_response_json("ok");
    }
    // This function receives a scan_path, notify_email and yara_rules
    // and inserts in DB a new job.
    // It also decreases the quota
    // WARNING: function assumes all the arguments have been sanitized / validated properly
    // WARNING: no checkes are being made!
    public function jobs_add_db ($initial_repo_scan_fileset, $user_notify_email, $yara_rules)
    {
        $jobs_ids = array();
        // Some entries in 'scan_filesets' table are added by the operator using multiple scan
        // paths. Eg: '/test + /virus + /_clean'. We need to separate them here.
        // Multiple repositories separated by $this->scan_filesets_separator can exist under
        // the same repository ID
        $all_scan_filesets = explode ($this->scan_filesets_separator, $initial_repo_scan_fileset);
        foreach ($all_scan_filesets as $repository_scan_path)
        {
           // Fire the job!
           $job_description = array();
           $job_description['fileset_scan']    = $repository_scan_path;
           $job_description['notify_email']    = $user_notify_email;

           $db_insert = array();
           $job_owner_id                = $this->CI->klsecurity->get_auth_userid();
           $db_insert['description']    = json_encode($job_description);
           $db_insert['results']        = "";
           $db_insert['rules']          = $yara_rules;
           $db_insert['status']         = "new";
           $db_insert['agent_id']       = "-1";
           $db_insert['owner_id']       = $job_owner_id;
           $db_insert['owner_group_id'] = $this->get_user_group_cnt ();
           $db_insert['finish_time']    = "N/A";
           // Setting up the shared key
           // It will be composed of sha256(md5($yara_rules) + md5(owner_id) + random)
           $db_insert['share_key']      = hash('sha256', md5($yara_rules) . md5($job_owner_id) . sha1(rand()));
           $this->CI->db->insert('jobs', $db_insert);
           array_push($jobs_ids, $this->CI->db->insert_id());
        }
        // Everything went OK!
        return $jobs_ids;
    }
    // Function used to display job details for a specific ID
    // IF the job ID doesn't exit then check if a share key was specified
    // If there are no results, returns an empty array
    public function jobs_view ($id = -1, $share_key = null)
    {
        // First of all, let's escape the input!
        $id         = intval($this->CI->security->xss_clean($id));
        $share_key  = $this->CI->security->xss_clean($share_key);

        // Make sure the share key is a valid sha256
        // preg_match can return 0, 1 or FALSE so we convert it to boolean first
        if ((bool) preg_match('/^[0-9a-f]{64}$/i', $share_key) == FALSE)
            $share_key = null;

        // Apply admin / non-admin restrictions for jobs
        // As well as jail / non-jail restrictions
        $this->CI->global_functions->jobs_apply_db_restrictions ();
        // Get the job from the DB, as well as owner username
        // We don't want to fetcha any other parts from users table (especially api key)
        $this->CI->db->select('jobs.*, username');
        $this->CI->db->join('users', 'owner_id = users.cnt');
        $this->CI->db->where('id', "". $id);
        $this->CI->db->limit(1);
        $q = $this->CI->db->get('jobs');
        if ($q->num_rows() == 0)
        {
            // If we got 0 results, we give a last chance to the user,
            // maybe he supplied a valid share_key
            if (is_null($share_key))
            {
                // No chances - invalid key!
                $this->CI->klsecurity->log('warn', 'jobs/view','User requested invalid job or not owned by him: '.print_r($id, true));
                return array();
            }
            // Potential valid share_key.. let's check it!
            // Get the job from the DB, as well as owner username
            // We don't want to fetcha any other parts from users table (especially api key)
            //
            // NOW We do not check the user permissions becase we know it has none :)
            // We just check if the share_key is valid...
            $this->CI->db->select('jobs.*, username');
            $this->CI->db->join('users', 'owner_id = users.cnt');
            $this->CI->db->where('id', "". $id);
            $this->CI->db->where('share_key', $share_key);
            $this->CI->db->limit(1);
            $q = $this->CI->db->get('jobs');
            // Final check to see if we have any results
            if ($q->num_rows() == 0)
            {
                // Wrong share key!
                $this->CI->klsecurity->log('warn', 'jobs/view','User requested invalid job or not owned by him. ID => '.print_r($id, true). " - invalid key => ". print_r($share_key, true));
                return array();
            }
            // We have 1 results! Share key seems ok!
        }
        // There should be only one result
        $job = $q->result_array();
        $job = $job['0'];
        // Get the hashes for this job!
        $job_hashes = $this->CI->db->get_where('jobs_hashes', array("job_id" => $job['id']));
        $job['hashes'] = $job_hashes -> result_array();
        $job['description'] = json_decode($job['description'], true);
        // If everything went ok, return the job array with info
        return $job;
    }
    // Function used to delete the specific job ID from DB
    public function jobs_delete ($id = -1)
    {
        // First of all, let's escape the input!
        $id = intval($id);

        // Admins can delete all jobs
        // Else only owner ID can delete a job
        if (!$this->CI->klsecurity->user_is_admin())
            $this->CI->db->where('owner_id', "".$this->CI->klsecurity->get_auth_userid());

        // Get the job from the DB!
        $this->CI->db->where('id', "".$id);
        $q = $this->CI->db->get('jobs');
        // There should be only one result
        if ($q->num_rows() != 1)
        {
            $this->CI->klsecurity->log('warn', 'global_functions/jobs_delete','User attempted to delete invalid job or not owned by him: '.print_r($id, true));
            return $this->api_generate_error_json("job_invalid_id");
        }
        $job = $q->result_array();
        $job = $job['0'];

        if ($job['status'] === "assigned")
        {
            return $this->api_generate_error_json("job_assigned");
        }
        $this->CI->klsecurity->log('info', 'global_functions/jobs_delete', 'User deleted job id: '.print_r($id, true));
        $this->CI->db->delete('jobs', array('id' => $job['id']));
        // Everything went fine. (?!)
        return $this->api_generate_response_json("ok");
    }
    // Function used to extract the first line of rules set from the rules
    // If limit_rule_name is set to true, display just first 64 chars
    public function jobs_extract_first_rules ($rules = '', $limit_rule_name = false)
    {
        if (strcmp($rules, '') == 0)
            return "N/A";

        // Go until you find the first match with "rule" text in it
        $match_nr = preg_match("/^.*?rule (.*?)[\n{]/is", $rules, $matches);
        if ($match_nr === 1)
        {
            // We have a match! Let's stop now
            if (strlen($matches[1]) > $this->rule_name_start_trimm && $limit_rule_name)
                return substr(trim($matches[1]), $this->rule_name_start_trimm)." [ ...]";
            return trim($matches[1]);
        }
        else if (is_bool($match_nr))
            return "Error while preg_match'ing!";

        // else we have 0 matches and we return "N/A"
        return "N/A";
    }
    public function valid_md5 ($md5 ='')
    {
        return strlen($md5) == 32 && ctype_xdigit($md5);
    }
    public function get_web_app_version()
    {
        return $this->web_app_version;
    }
}
