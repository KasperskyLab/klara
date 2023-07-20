import config
import logging
import tempfile
import shlex
import subprocess
import time
import os
import re
import json

# Functions used by the Klara client


def yara_scan(scan_options):
    assert (isinstance(scan_options, dict))
    assert (isinstance(config.head_path_and_args, list))

    # Initial set of results
    results = dict()
    results['finish_time'] = "N/A"
    results['execution_time'] = -1
    results['yara_results'] = "ERROR: Yara scanner didn't run"
    results['yara_errors'] = None
    results['yara_warnings'] = None

    if 'rules' not in scan_options:
        logging.warning("Did not receive 'rules' from dispatcher!")
        results['yara_errors'] = "Did not receive 'rules' from dispatcher!"
        return results
    else:
        yara_rules = scan_options['rules']
    if 'fileset_scan' not in scan_options:
        logging.warning("Did not receive 'fileset_scan' from dispatcher!")
        results['yara_errors'] = "Did not receive 'fileset_scan' from dispatcher!"
        return results
    else:
        fileset_scan = scan_options['fileset_scan']
    if 'timeout' not in scan_options:
        yara_timeout = 0
    else:
        yara_timeout = scan_options['timeout']

    null_file = open(os.devnull, 'w')
    yara_rules_save_error = False
    try:
        # Generate a temporary file name
        yara_rules_temp_file = tempfile.NamedTemporaryFile(delete=False,
                                                           prefix="yara_rules_",
                                                           dir=config.yara_temp_dir)
        # Getting the 2 temp files' FN
        yara_rules_temp_file_fn = os.path.abspath(yara_rules_temp_file.name)
        # After we write the rules, we close the file so we prepare it for the
        # yara process
        yara_rules_temp_file.write(yara_rules)
        yara_rules_temp_file.close()
    except UnicodeEncodeError as e:
        results['yara_errors'] = "Rule contains non-ASCII characters!" + str(e)
        yara_rules_save_error = True
    except Exception as e:
        results['yara_errors'] = "Error: " + str(e)
        yara_rules_save_error = True

    if yara_rules_save_error:
        # Close the files!
        null_file.close()
        # Deleting temp files
        os.remove(yara_rules_temp_file_fn)
        return results

    # Setting up initial yara path + arguments
    yara_cmd = config.yara_path + " "
    yara_cmd += "-a " + str(yara_timeout) if (yara_timeout > 0) else ""
    yara_cmd += str(config.yara_extra_args) + " "
    yara_cmd += str(os.path.abspath(yara_rules_temp_file.name)) + " "

    # Make sure the yara binary is executable
    if not os.path.isfile(config.yara_path):
        results['yara_errors'] = "Yara binary missing from %s".format(config.yara_path)
        # Close the files!
        null_file.close()
        # Deleting temp files
        os.remove(yara_rules_temp_file_fn)
        return results
    # We make the scan in 2 steps:
    # 1) check if rules are valid
    # 2) Do the scan

    # 1) Checking for valid rules by asking yara to scan /dev/null
    yara_args = shlex.split(yara_cmd + os.devnull)
    # Since we are scanning /dev/null we want to make sure that yara doesn't
    # complain about the rules.
    yara_process = subprocess.Popen(
        yara_args,                  stdout=null_file, stderr=subprocess.PIPE)
    # Now we wait for yara to finish....
    (stdout_data, stderr_data) = yara_process.communicate()
    # stdout_data is ""
    # We want to check the return code.
    return_code = yara_process.returncode

    # If yara didn't return 0, then we have an error!
    if return_code != 0:
        results['yara_errors'] = stderr_data
        # Close the files!
        null_file.close()
        # Deleting temp files
        os.remove(yara_rules_temp_file_fn)
        return results

    # Return code was indeed 0, BUT there might be warnings returned by Yara.
    # We then save the stderr_data
    if len(stderr_data) != 0:
        results['yara_warnings'] = stderr_data

    # Great, no errors!
    # 2) Doing the actual scan
    yara_args = shlex.split(yara_cmd + str(fileset_scan))
    time_start = int(time.time())
    # We redirect stderr to null
    yara_process = subprocess.Popen(
        yara_args,                  stdout=subprocess.PIPE, stderr=null_file)
    head_process = subprocess.Popen(
        config.head_path_and_args,  stdout=subprocess.PIPE, stdin=yara_process.stdout)
    # Allow yara_process to receive a SIGPIPE if head_process exits.
    yara_process.stdout.close()
    # Now we wait for yara to finish....
    stdout_data = head_process.communicate()[0]
    time_end = int(time.time())
    # We want to run wait on yara in order to get its return code!
    yara_process.wait()
    return_code = yara_process.returncode

    # Setting up the results dict
    results['finish_time'] = time.strftime('%Y-%m-%d %H:%M:%S')
    results['execution_time'] = time_end - time_start
    results['yara_results'] = stdout_data
    if return_code != 0:
        results['yara_errors'] = "Yara agent returned non-zero status code"

    # Close the files!
    null_file.close()
    # Deleting temp files
    os.remove(yara_rules_temp_file_fn)
    # Finally return the result
    return results

# This function takes as argument a string consisting of only yara results only
# and returns a list of all the files in the list.


def extract_matched_files(yara_results):
    if len(yara_results) == 0:
        return []

    # We assume that Yara returns its answers with a new line at the end and a
    # space after the match
    pattern_for_yara_results = re.compile(r' (.*)\n')
    return re.findall(pattern_for_yara_results, yara_results)

# This function takes as argument a list of matched files
# and returns a string with all the md5 associated with all the files in
# the list


def generate_md5_from_results(yara_matched_files):
    assert (isinstance(yara_matched_files, list))

    if len(yara_matched_files) == 0:
        # Else return empty JSON list!
        return json.dumps([])

    # If the list is not 0, we add these files as arguments to the md5sum
    # function!
    pattern_for_md5sum_results = re.compile(r'(.*)  .*\n')
    # Prepare to run md5sum as Popen
    p = subprocess.Popen([config.md5sum_path] + yara_matched_files,
                         stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    stdout_data = p.communicate()[0]
    # We want unique md5s
    return json.dumps(list(set(re.findall(pattern_for_md5sum_results, stdout_data))))
