<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Config_validator
{
    // This Library is used solely to validate the config entries

    public function __construct()
    {
        // get CodeIgniter instance
        $this->CI = &get_instance();
 
        // Check if the version file exists in the main directory. IF not, die
        if (!file_exists(FCPATH."version.txt"))
            show_error('File <strong>version.txt</strong> missing: I need to know my version!');

        if (is_null($this->CI->config->item('project_title')))
            show_error('Config <strong>project_title</strong> entry missing!');
        if (is_null($this->CI->config->item('search_md5s_limit')))
            show_error('Config <strong>search_md5s_limit</strong> entry missing!');
    }
}
