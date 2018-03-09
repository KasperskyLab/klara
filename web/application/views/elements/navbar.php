<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
        <a class="navbar-brand" href="<?php echo site_url('jobs')?>">
            <?php 
                echo $this->config->item('project_title');
            ?>
        </a>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <?php
            // Check each menu entry to see if we can show to user.
            if ($this->klsecurity->link_visible_for_user('/jobs'))
                echo '<li><a href="'.site_url('jobs').'">Current jobs</a></li>';
            if ($this->klsecurity->link_visible_for_user('/jobs'))
                echo '<li><a href="'.site_url('jobs/add').'">New job</a></li>';
            if ($this->klsecurity->link_visible_for_user('/profile'))
                echo '<li><a href="'.site_url('profile').'">My profile</a></li>';
            if ($this->klsecurity->link_visible_for_user('/advanced_search'))
                echo '<li><a href="'.site_url('advanced_search').'">Advanced search</a></li>';
        ?>
      </ul>

    <p class = "nav navbar-nav navbar-right navbar-text">
        <?php
            // If user is not poweruser, show the quota!
            if ($this->search_quota->user_has_quota())
            {
                $current_user_quotas        = $this->search_quota->user_get_curr_quota();
                $current_user_search_quota  = $current_user_quotas['searches_curr_month'];
                echo "Current quota: <strong>$current_user_search_quota</strong> jobs - ";
            }
            echo "Logged in as ";
            // if username is set, then show his username!
            if (!is_null($this->session->userdata('username')))
                echo $this->session->userdata('username');
            else
                echo "N/A";
        ?> - Group: <?php echo $this->global_functions->get_user_group_name()?> <a href="<?php echo site_url('login/logout')?>">[Logout]</a>
    </p>

    </div><!--/.nav-collapse -->
  </div>
</nav>
<!-- We want to make a bit of space -->
<br><br>
