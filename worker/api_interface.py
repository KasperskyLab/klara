import logging
import config
import requests
import json

# Ask dispatcher to send the current available jobs
# Will return an API response (status, status_msg, return_data (if status == ok)) already
# parsed from json into a dict


def fetch_available_jobs():
    # logging.info ("Called fetch_new_job")
    # Set up the request
    payload = {'auth': config.api_key}
    # Make the request, with the auth as POST DATA
    try:
        r = requests.post(config.api_location +
                          "/worker_fetch_available_jobs", data=payload)
        return r.json()
    # We want to catch any exception related to:
    # Not being able to connect (Requests exception) or json decodification
    except Exception as e:
        logging.error('Exception: %s', e)
        return json.loads('{"status": "error", "status_msg": "connection_error"}')

# After fetching the available jobs, ask dispatcher to assign us one job


def request_assign_job(job_id=-1):
    # Set up the request
    payload = {'auth': config.api_key, 'job_id': job_id}
    # Make the request, with the auth as POST DATA
    try:
        r = requests.post(config.api_location +
                          "/worker_assign_job", data=payload)
        return r.json()
    # We want to catch any exception related to:
    # Not being able to connect (Requests exception) or json decodification
    except Exception as e:
        logging.error('Exception: %s', e)
        return json.loads('{"status": "error", "status_msg": "connection_error"}')

# Function used to push the results back


def push_results(results):
    # logging.info ("Called push_results")
    # Set up the request
    # In order to set the 2 POST params, we use a dictionary,
    # And to send the "results" POST variable, we encode the contents into JSON
    payload = {'auth': config.api_key, "results": json.dumps(results)}
    # Make the request, with the auth as POST DATA
    try:
        r = requests.post(config.api_location +
                          "/worker_save_results", data=payload)
        return r.status_code
    except Exception as e:
        logging.error('Exception: %s', e)
        return 500
