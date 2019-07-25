# Main settings for the worker

# Set debug lvl
logging_level  = "debug"

# Api location for Dispatcher. No trailing slash!!
api_location = "http://127.0.0.1:8888/api"
api_key      = "test"

# Specify worker refresh time in seconds
refresh_new_jobs    = 60

# Yara settings
yara_path           = "/opt/yara-latest/bin/yara"
# Use 4 threads to scan and scan dirs recursively
yara_extra_args     = "-p 4 -r"
yara_temp_dir       = "/tmp/"

# md5sum settings
md5sum_path         = "/usr/bin/md5sum"

# tail settings
# We only want the first 1k results
head_path_and_args  = ["/usr/bin/head", "-1000"]

# Virus collection should NOT have a trailing !!
virus_collection                = "/var/projects/klara/repository"
virus_collection_control_file   = "repository_control.txt"
