# GReAT's KLara project

KLara project is aimed at helping Threat Intelligence researchers hunt for new malware using [Yara](https://github.com/VirusTotal/yara).

In order to hunt efficiently for malware, one needs a large collection of samples to search over. 
Researchers usually need to fire a Yara rule over a collection / set of malicious files and then get the results back. 
In some cases, the rule needs adjusting. Unfortunately, scanning a large collection of files takes time. 
Instead, if a custom architecture is used, scanning 10TB of files can take around 30 minutes.

KLara, a distributed system written in Python, allows researchers to scan one or more Yara rules
over collections with samples, getting notifications by e-mail as well as the web interface when scan results are ready.

# Features

- Modern web interface, allowing researchers to "fire and forget" their rules, getting back results by [e-mail / API](/install/features_web.md)
- Powerful API, allowing for automatic Yara jobs submissions, checking their status and getting back results. API Documentation will be released soon.
- Distributed system, running on commodity hardware, easy to deploy and implement.

# Architecture

KLara leverages Yara's power, distributing scans using a dispatcher-worker model. Each worker server connects to a dispatcher
trying to check if new jobs are available. If a new job is indeed available, it checks to see if the required scan repository is
available on its own filesystem and, if it is, it will start the Yara scan with the rules submitted by the researcher 

The main issue KLara tries to solve is running Yara jobs over a large collection of malware samples ( > 1TB) in a reasonable amount of time.

# Installing KLara

Please refer to instructions outlined [here](/install/)

======

If you have any issues with installing this software, please submit a bug report

# Contributing and reporting issues

Anyone is welcome to contribute. Please submit a PR and our team will review it.

You can get in touch with us on our Telegram channel: [#KLara](https://t.me/kl_klara)

The best way to submit bugs or issues is using Github's Issues feature.

# Credits

KLara team would like to thank

- Costin, Marco, Vitaly, Sergey
- Current, former and future GReAT members!
- Alex@grep
- All contributors

for their amazing input and ideas. Happy hunting!
