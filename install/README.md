# Installing Klara

## Requirements for running Klara:

- GNU/Linux (we recommend `Ubuntu 16.04` or latest LTS)
- SQL DB Server: MySQL / MariaDB
- Python 2.7
- Python virtualenv package
- Yara (installed on workers)

Installing Klara consists of 4 parts:

- Database installation
- Worker installation
- Dispatcher installation
- Web interface installation

Components are connected between themselves as follows:

```
                              +----------+          +----------------+
                              |          |          |                |
                  +---------->+ Database +<--+      |     nginx      |
                  |           |          |   |      |   (optional)   |
                  |           +----------+   |      |                |   
           +------+------+                   |      +-------+--------+   
           |             |                   |              |             
    +----->|  Dispatcher | <---+             |              |            
    |      |             |     |             |              |            
    |      +------+------+     |             |              v            
    |             |            |             |      +-------+--------+
    |             |            |             |      |                |
    |             |            |             |      |                |
+---+----+   +----+---+   +----+---+         ^------+   Web server   |
|        |   |        |   |        |                |                |
| Worker |   | Worker |   | Worker |                |                |
|        |   |        |   |        |                +----------------+
+--------+   +--------+   +--------+


```
Workers connect to Dispatcher using a simple `HTTP REST API`. Dispatcher and the Web server 
connect the MySQL / MariaDB Database using TCP connections. Because of this, components can be installed on 
separated machines / VMs. The only requirements is that TCP connections are allowed between them.

# Installing on Windows

Since entire project is written in Python, Dispatcher and Workers can be set up to run in an Windows environment. Unfortunately, as we only support Ubuntu, instructions will be provided for this platforms, but other GNU/Linux flavors should be able to easily install Klara as well.

## Database installation

Please install a SQL database (we recommend MariaDB) and make it accessible for Dispatcher and Web Interface.

To create a new DB user, allowing access to `klara` database, for any hosts, identified by password `pass12345` use the following command:

```
##### For `klara` DB #####
# Please use random/secure password for user 'klara' on DB 'klara'
CREATE USER 'klara'@'127.0.0.1' IDENTIFIED BY 'pass12345';
GRANT USAGE ON *.* TO 'klara'@'127.0.0.1' IDENTIFIED BY 'pass12345' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
GRANT ALL PRIVILEGES ON `klara`.* TO 'klara'@'127.0.0.1';

```

Once Dispatcher and Web Interfaces are set-up and configured to point to DB, the SQL DB needs to be created. Please run the SQL statements from [db_patches/db_schema.sql](db_patches/db_schema.sql) location:

```
mysql [connecting options] < db_schema.sql
```

## Dispatcher installation

Install the packages needed to run Dispatcher:
```
sudo apt -y install python-virtualenv libmysqlclient-dev python-dev git
```

We recommend running dispatcher on a non-privileged user. Create an user which will be responsible to run Worker as well as Dispatcher:

```
sudo groupadd -g 500 projects
sudo useradd -m -u 500 -g projects projects
```

Create a folder needed to store all Klara project files:

```
sudo mkdir /var/projects/
sudo chown projects:projects /var/projects/
```

Substitute to projects user and create the virtual env + folders needed to run the Dispatcher:

**Note**: From now on, all commands will be executed under `projects` user

```
su projects
mkdir /var/projects/klara/ -p
# Create the virtual-env
virtualenv ~/.virtualenvs/klara
```

Clone the repository:

```
git clone git@github.com:kasperskylab/klara.git ~/klara-github-repo
```

Copy Dispatcher's files and install python dependencies:
```
cp -R ~/klara-github-repo/dispatcher /var/projects/klara/dispatcher/
cd /var/projects/klara/dispatcher/
cp config-sample.py config.py
pip install -r ~/klara-github-repo/install/requirements.txt
```

Now fill in the settings in config.py:

```
# Setup the loglevel
logging_level  = "debug"

# What port should Dispatchers's REST API should listen to
listen_port = 8888

# Main settings for the master
# Set debug lvl
logging_level  = "debug"

# What port should the server listen to
listen_port = 8888

# Notification settings
# Do we want to sent out notification e-mails or not?
notification_email_enabled  = True
# FROM SMTP header settings
notification_email_from     = "klara-notify@example.com"
# SMTP server address
notification_email_smtp_srv = "127.0.0.1"

# MySQL / MariaDB settings for the Dispatcher to connect to the DB
mysql_host      = "127.0.0.1"
mysql_database  = "kl-klara"
mysql_user      = "root"
mysql_password  = ""
```
Once settings are set, you can check Dispatcher is working by running the following commands:
```
sudo su projects
# We want to enable the virtualenv
source  ~/klara-github-repo/install/activate.sh
cd /var/projects/klara/dispatcher/
chmod u+x ./klara-dispatcher
./klara-dispatcher
```
If everything went well, you should see:
```
[01/01/1977 13:37:00 AM][INFO]  ###### Starting KLara Job Dispatcher ######
```

In order to start Dispatcher automatically at boot, please check [Supervisor installation](supervisor.md)

Next step would be starting Dispatcher using `supervisorctl`:
```
sudo supervisorctl start klara_dispatcher start
```


# Worker installation

## Setting up an API key to be used by Workers

Each worker should have its own unique assigned API key. This helps maintaining strict access controls.

In order to insert a new API key to be used by a KLara worker, a new row needs to be inserted into DB table `agents` with the following entries:

* `description` - Short description for the worker (up to 63 chars)
* `auth` - API auth code (up to 63 chars)


## Installing the Worker agent

Install the packages needed to run Worker:
```
sudo apt -y install python-virtualenv python-dev git
```

We recommend running Worker using a non-privileged user. Create an user which will be responsible to run Worker as well as Dispatcher:

```
sudo groupadd -g 500 projects
sudo useradd -m -u 500 -g projects projects
```

Create a folder needed to store all Klara project files:

```
sudo mkdir /var/projects/
sudo chown projects:projects /var/projects/
```

Substitute to projects user and create the virtual env + folders needed to run the Worker:

**Note**: From now on, all commands will be executed under `projects` user

```
su projects
mkdir /var/projects/klara/ -p
# Create the virtual-env
virtualenv ~/.virtualenvs/klara
```

Clone the repository:

```
git clone git@github.com:kasperskylab/klara.git ~/klara-github-repo
```

Copy Worker's files to the newly created folder and install python dependencies:
```
cp -R ~/klara-github-repo/worker /var/projects/klara/worker/
cd /var/projects/klara/worker/
cp config-sample.py config.py
pip install -r ~/klara-github-repo/install/requirements.txt
```

Now fill in the settings in config.py:

**Note**: use the API key you just inserted in table `agents` above;  
**Note**: Check [Worker Settings](#setting-up-workers-scan-repositories-and-virus-collection) to understand how to change settings
`virus_collection` and `virus_collection_control_file`

```
# Setup the loglevel
logging_level  = "debug"

# Api location for Dispatcher. No trailing slash !!
# Dispatcher is exposing the API at "/api/" location
api_location = "http://127.0.0.1:8888/api"
# The API key set up in the `agents` SQL table
api_key      = "test"

# Specify worker refresh time in seconds
refresh_new_jobs    = 60

# Yara settings
# Set-up path for Yara binary
yara_path           = "/opt/yara-latest/bin/yara"
# Use 8 threads to scan and scan dirs recursively
yara_extra_args     = "-p 8 -r"
# Where to store Yara temp results file
yara_temp_dir       = "/tmp/"

# md5sum settings
# binary location
md5sum_path         = "/usr/bin/md5sum"

# tail settings
# We only want the first 1k results
head_path_and_args  = ["/usr/bin/head", "-1000"]

# Virus collection should NOT have a trailing slash !!
virus_collection                = "/var/projects/klara/repository"
virus_collection_control_file   = "repository_control.txt"
```
Once the settings are set, you can check Worker is working by running the following commands:
```
sudo su projects
# We want to enable the virtualenv
source  ~/klara-github-repo/install/activate.sh
cd /var/projects/klara/worker/
chmod u+x ./klara-worker
./klara-worker
```

If everything went well, you should see:
```
[01/01/1977 13:37:00 AM][INFO]  ###### Starting KLara Worker ######
```

In order to start Worker automatically at boot, please check [Supervisor installation](supervisor.md)

Next step would be starting Worker using `supervisorctl`:
```
sudo supervisorctl start klara_worker start
```


## Installing Yara on worker machines

Install the required dependencies:
```
sudo apt -y install libtool automake libjansson-dev libmagic-dev libssl-dev build-essential

# Get the latest stable version of yara from https://github.com/virustotal/yara/releases
# Usually it's good practice to check the hash of the archive you download, but here we can't, since it's from GH
# 
wget https://github.com/VirusTotal/yara/archive/vx.x.x.tar.gz
cd yara-3.x.0
./bootstrap.sh

./configure --prefix=/opt/yara-x.x.x --enable-cuckoo --enable-magic --enable-address-sanitizer --enable-dotnet
make -j4
sudo make install
```

Now you should have Yara version installed on `/opt/yara-x.x.x/`

Create a symlink to the latest version, so when we update it, workers don't have to be reconfigured / restarted:
```
# Symlink to the actual folder
cd /opt/
ln -s yara-3.x.x/ yara-latest
```

## Setting up worker's scan repositories and virus collection

Each time workers contact Dispatcher in order to check for new jobs, will verify first if they can execute them. Klara was designed such as:
* each worker agent has a (root) virus collection where all the scan repositories should exist (setting `virus_collection` from `config.py`)
* multiple `scan repositories` will be checked by KLara workers when trying to accept a job. (for example, if one user wants to scan `/clean` repository, a Worker agent will try to check if it's capable of scanning it, by checking its `virus_collection` folder )
* in order to check if it's capable of scanning a particular `scan repository`, Worker checks if the collection control file exists (setting `virus_collection_control_file` from `config.py`) at location: `virus_collection` + `scan repository` + / + `virus_collection_control_file`.

Basically, if a new job to scan `/mach-o_collection` is to be picked up by a free Worker with the following `config.py` settings:

```
virus_collection                = "/mnt/nas/klara/repository"
virus_collection_control_file   = "repo_ctrl.txt"
``` 
then that Worker will check if it has the following file and folders structures:
```
/mnt/nas/klara/repository/mach-o_collection/repo_ctrl.txt
```

If this file exists at this particular path, then the Worker will accept the job and start the Yara scan with the specified job rules, searching files in `/var/projects/klara/repository/mach-o_collection/`

It is entirely up to you how to organize your scan repositories. An example of organizing directory `/mnt/nas/klara/repository` is as follows:

* `/clean`
* `/mz`
* `/elf`
* `/mach-o`
* `/vt`
* `/unknown`

## Repository control

KLara Workers check only if the repository control file exists in order to prepare the Yara scan. Contents of the file should only be an empty JSON string:

```
{}
```

Optionally, just for usability, you should write some info about the repository:

```
{"owner": "John Doe", "files_type": "elf", "repository_type": "APT"}
```

Scan Repository control file also has some interesting modifiers that can be used to manipulate Yara scans or results. For further info, please check [Advanced usage](features_advanced.md)

# Web interface installation

Requirements for installing web interface are:

- web server running at least PHP 5.6
- the following php5 extensions:

```
apt install php7.0-fpm php7.0 php7.0-mysqli php7.0-curl php7.0-gd php7.0-intl php-pear php-imagick php7.0-imap php7.0-mcrypt php-memcache  php7.0-pspell php7.0-recode php7.0-sqlite3 php7.0-tidy php7.0-xmlrpc php7.0-xsl php7.0-mbstring php-gettext php-apcu
```

Once you have this installed, copy `/web/` folder to the HTTP server document root. Update and rename the following sample files:

- `application/config/config.sample.php` -> `application/config/config.php`
- `application/config/project_settings.sample.php` -> `application/config/project_settings.php`

You must configure the `base_url`, `encryption_key` from `config.php` as well as other settings in `database.php`.
More info about this here:

- https://www.codeigniter.com/user_guide/installation/upgrade_303.html
- https://codeigniter.com/user_guide/libraries/encryption.html
- https://www.codeigniter.com/user_guide/database/configuration.html

For your convenience, 2 `users`, 2 `groups` and 2 `scan repositories` have been created:

* Users:

| Username      | Password                | Auth level     | Group ID     | Quota |
| ------------- |:-------------:          | :----------    | ---------    | :---- |
| admin         | `super_s3cure_password` | `16` (Admin)   | `2` (admins) | N/A (Admins don't have quuota) |
| john          | `super_s3cure_password` | `4` (Observer) | `1` (main)   | 1000 scans / month |

* Groups

| Group name    | `scan_filesets_list` (scan repositories) | Jail status |
| ------------- | :-------------                           | ----------- |
| main          | `[1,2]`                                  | 0 (OFF - Users are not jailed) |
| admins        | `[1,2]`                                  | 0 (OFF - Users are not jailed) |

* Scan Repositories (`scan_filesets` DB table)

| Scan Repository   |
| -------------     |
| /virus_repository |
| /_clean           |


For more info about Web features (creating / deleting users, user quotas, groups, auth levels, etc..), please check dedicated page [Web Features](features_web.md)

--------

That's it! If you have any issues with installing this software, please submit a bug report, or join our [Telegram channel #KLara](https://t.me/kl_klara)

Happy hunting!


