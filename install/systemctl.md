## Setting up systemd

Every machine / VM running either Dispatcher, Worker or both, may use [`systemd`](https://www.freedesktop.org/wiki/Software/systemd/) as an alternative to **supervisord** in order to automate starting and stopping of KLara scripts.

[`Systemctl`](https://www.freedesktop.org/software/systemd/man/systemctl.html) comes preinstalled with many distributions, including latest versions of Ubuntu, Debian, Fedora and more.

If you run Apache as a web server for KLara, you may already be familiar with systemd, as you can work with `apache2.service` using `systemctl`:

```
sudo systemctl <start|stop|restart> apache2.service
```

# Systemctl settings for Dispatcher

```
[Unit]
Description=KLara dispatcher

[Service]
ExecStart=/home/projects/.virtualenvs/klara/bin/python /var/projects/klara/dispatcher/klara-dispatcher
User=projects
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

# Systemctl settings for Worker

```
[Unit]
Description=KLara worker

[Service]
ExecStart=/home/projects/.virtualenvs/klara/bin/python /var/projects/klara/worker/klara-worker
User=projects
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Note: `ExecStart` tells system to run worker/dispatcher scripts using your newly created virtual environment with Python, which you have configured during the previous steps of the installation. Also, make sure that user **projects** has appropriate rights to execute Python's binary in the virtualenv's directory.

# Adding services to systemctl

Both of these files may be placed into `/etc/systemd/system/` directory.

In this case you can name them `klara_worker.service` and `klara_dispatcher.service` respectively, and execute the following command to enable them after each reboot:

```
sudo systemctl enable klara_worker.service klara_dispatcher.service
```

To check whether the services were successfully loaded, execute the following commands:

```
sudo systemctl status klara_worker.service
sudo systemctl status klara_dispatcher.service
```
