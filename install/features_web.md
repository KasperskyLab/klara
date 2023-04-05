# Web features

KLara web is designed to be as modular as possible, allowing creating users in different groups, with different quotas, allowing sharing of scan results as well as searching through scan results.


## Scan repositories

List of scan repositories is located in DB table `scan_filesets`. This table defines the list of virus repositories users see when they try to start a new job (while accessing page `index.php/jobs/add`). This list needs to include **all** scan repositories that exist on all workers,  else users will not be able to submit scan jobs for specific scan repositories located in Workers' `virus_collection`, with missing entries in `scan_filesets` table.

For example, if one worker (configured with `/mnt/storage/vircol/` as `virus_collection`) has the following folders structure:

```
/mnt/storage/vircol/
/mnt/storage/vircol/vt_samples/
/mnt/storage/vircol/virus_repository/
/mnt/storage/vircol/_clean/
```

then it has 3 scan repositories: `/vt_samples`, `/virus_repository` and `/_clean`. These 3 entries should be added to `scan_filesets` in order to make sure web users see all available scan repositories.


## User groups

Groups define three things:

* name of the group
* what scan repositories users can submit jobs to (`scan_filesets_list` column). **Important**: this needs to be a valid JSON list
* if user can see other users' submitted job on their dashboard (`jail_users` column)

Let's assume user John is part of group ID 1, called `main`. This group has defined `[1,2]` for `scan_filesets_list` and `0` for `jail_users`. This means that:

* members of this group are allowed to create jobs to scan repositories with IDs `1` and `2`.
* user is **not** jailed, meaning he can view jobs submitted by other group members. If this value was `1`, then this user would sees his own submissions only. **Note**: this doesn't apply to admin users (they see all submitted jobs by default)

## Users

### Managing users

There's no admin page for users management.

The easiest way is to install a DB management software and use SQL statements to manage users.
In order to help with creating users, a special `Admin_tools` page that can only be accessed by administrators (`auth_level = 16`) is available and has two helper functions:

* `gen_pass`

Method available for authenticated admins at `http://[klara-ip]/index.php/admin_tools/gen_pass`

This prints a randomly generated password accompanied by its BCRYPT hash. Useful when creating one user: insert the bcrypt hash into DB and send the plaintext to the user.

Password's complexity can be changed by modifying the parameters for function `generate_password`. Check the source code for [`KLsecurity` model](https://github.com/KasperskyLab/klara/blob/master/web/application/models/Klsecurity.php)


* `generate_users`

Method available for authenticated admins at `http://[klara-ip]/index.php/admin_tools/generate_users`

This method takes the `$users_emails` array and automatically generates username + password pairs, as well as the SQL statements, ready to be inserted into table `users`. The array can be found at [`Admin_tools.php`](https://github.com/KasperskyLab/klara/blob/master/web/application/controllers/Admin_tools.php#L43)

The `$users_emails` should be a PHP array, containing a list of usernames you want to create accounts for. Example:

Creating accounts for John, Doe and Nick-Smith Alexander:

`$users_emails = array('john@example.com', 'doe@example.com', 'nsa@example.com');`

### DB `users` table details

* `username` is the user's login handle. It's a varchar of max 63 chars.
* `pass` is the user's BCRYPT password. For instructions on how to generate these passwords, check [Managing users](#managing-users) section outlined above
* `desc` is a description for this user of max 127 chars.
* `auth` is his current [authorized level](#user-rights)
* `api_auth_code`, `api_perms`, `api_status` set API permissions. If user doesn't need API access, `api_status` should be set to `0`. Check [API Access](#user-api-access)
* `group_cnt` defines the ID of the group user should belong to. One user can belong to one group only. Check [User Groups](#user-groups)
* `notify_email` is the notification e-mail user will receive e-mails when jobs finish. If the field is an empty string, before creating his first rule, user will be asked to input a valid e-mail address.
* `quota_searches` should be the number of [maximum searches per month](#user-quotas)

### User rights

There are currently 6 types of users: `disabled`, `suspended`, `registered`, `observer`, `poweruser`, `admin`. Each of them have a specific authentication level (`auth_level` or simply `auth`).

Current auth levels are:

```
0   - disabled      - Unauthenticated user
1   - suspended     - User suspended from system
2   - registered    - User allowed to view/add jobs, quotas enforced
4   - observer      - Not used
8   - poweruser     - User allowed to view/add jobs, quotas disabled
16  - Admin         - God
```

Usually, if you want to create accounts for your team, you should add them as "poweruser" (auth level `8`), belonging to a group with `jail` set to `0`. As such, there are no quotas enforced for them and all users can see each other's submissions.

### User quotas

For users with access level lower than `poweruser`(this means quota system is in place; check [User rights](#user-rights)), quota for one user is defined by three columns in the `users` table:

* `quota_searches` - defines the number of jobs one user is allowed to submit per month.
* `searches_curr_month` - defines how many jobs user can submit in that particular month
* `quota_curr_month` - defines the month to which `searches_curr_month` applies to

Quotas for all eligible users reset at beginning of each month.


## Shareable links

Sometimes, users from different groups (even jailed ones) would like to share their job results. In order to achieve this, *shareable links* have been created in order to allow anyone with a valid KLara account to view the job results. Shareable links for one job are automatically generated when one is created.

For each job, users can find it's shareable link at the job details page and can share it with anyone using a valid KLara account