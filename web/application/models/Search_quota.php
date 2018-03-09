<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Search_quota extends CI_Model
{
    // This model controls the search quota for users!
    public function __construct()
    {
        if ($this->klsecurity->user_is_authenticated())
        {
            // We want to get the current user id only once
            $user_id = $this->klsecurity->get_auth_userid();
            // Check current month
            $user_status = $this->user_get_curr_quota($user_id);

            // Check if we need to reset it's quota, maybe we are in a new month!
            // If we are in a different month than the quota_curr_month, reset the quota and
            // update $user_status
            if (date("Y-m") !== $user_status['quota_curr_month'])
                $this->user_reset_quota($user_id);
        }

        // We did our job here, now it's /show controller's job to limit the queries.
    }

    // Function returns the new current quota same as function `user_get_curr_quota`
    private function user_reset_quota($user_id = -1)
    {
        if ($user_id == -1)
            // We show the current user quota if no argument given
            $user_id = $this->klsecurity->get_auth_userid();

        // Get the default quota searchers for that user, as well with the quota current month
        $users_table = 'users';
        // Fetching the info
        $this->db->select('quota_searches');
        $this->db->select('quota_curr_month');
        $this->db->where('cnt', $user_id);
        $result = $this->db->get($users_table);
        // This query will only return 1 answer, due to cnt being a primary key
        $answer = $result->result_array();
        $answer = $answer[0];

        // For a correct reset we need to:
        // 1) set quota_curr_month to the current month we are resetting the quota
        // 2) set searches_curr_month to quota_searches
        $reset_values = array(
                "quota_curr_month" => date("Y-m"),
                "searches_curr_month" => intval($answer['quota_searches']));
        $this->db->where('cnt', $user_id);
        $this->db->update($users_table, $reset_values);
        // Done resetting the values
        return $reset_values;
    }
    public function user_can_query($nr_queries = 0)
    {
        // If user is poweruser, then we just let him go without any checks.
        if ($this->klsecurity->user_is_poweruser())
            return true;

        $user_status = $this->user_get_curr_quota();
        $searches_curr_month = $user_status['searches_curr_month'];

        if ($searches_curr_month - intval($nr_queries) < 0)
            return false;

        return true;
    }
    public function user_has_quota()
    {
        return $this->klsecurity->user_is_authenticated() && !$this->klsecurity->user_is_poweruser();
    }
    // Function called by other controllers when searchers are made! 
    public function user_decrease_quota($amount = 0)
    {
        // If user does not have quota, we just let him go without any checks.
        if (!$this->user_has_quota())
            return;

        $user_id = $this->klsecurity->get_auth_userid();
        $user_status = $this->user_get_curr_quota($user_id);

        $searches_curr_month = $user_status['searches_curr_month'];
        // We decrease the current nr of searches 
        $searches_curr_month -= intval($amount);
        // Check we get get a lower value!
        if ($searches_curr_month < 0)
            $searches_curr_month = 0;

        // Let's update the searches_curr_month!
        $users_table = 'users';

        $reset_values = array(
                "searches_curr_month" => $searches_curr_month);
        $this->db->where('cnt', $user_id);
        $this->db->update($users_table, $reset_values);
        return;
    }
    public function check_quota($entries = 0)
    {
        // Check user quota
        if ($this->user_has_quota())
        {
            // We are searching for $entries domain only!
            if (!$this->user_can_query($entries))
            {
                // If user can't query $entries, show quota error page!
                redirect('quota_error');
                die();
            }
            else
                $this->user_decrease_quota($entries);
        }
    }
    // This function returns the current quota for the calling user
    // Returns an array("searches_curr_month" => 1337, 
    //                  "quota_curr_month"    => "2015-01");
    // OR 
    // searches_curr_month as int
    public function user_get_curr_quota($user_id = -1, $only_searches_curr_month = false)
    {
        if ($user_id == -1)
            // We show the current user quota if no argument given
            $user_id = $this->klsecurity->get_auth_userid();

        // Now that we have the userID we can check it's quota for this month!
        // Check if we are in sinkhole mode
        $users_table = 'users';

        // Fetching the info
        $this->db->select('quota_curr_month');
        $this->db->select('searches_curr_month');
        $this->db->where('cnt', $user_id);
        $result = $this->db->get($users_table);
        // This query will only return 1 answer, due to cnt being a primary key
        $answer = $result->result_array();
        $answer = $answer[0];
        // Convert our value to integer
        $answer['searches_curr_month'] = intval($answer['searches_curr_month']);

        // If user wants only searches_curr_month, we'll give him that!
        if ($only_searches_curr_month === true)
            return $answer['searches_curr_month'];
        return $answer;
    }
}
