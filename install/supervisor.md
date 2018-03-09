## Setting up supervisord

For every machine / VM running either Dispatcher, Worker or both, we will be running them with the help of [`supervisord`](http://supervisord.org)

Installing supervisor is covered on their official website: http://supervisord.org/installing.html

Once you have `supervisord` installed, deploy the following scripts in the config 
folder for `supervisord` (usually it's `/etc/supervisor/conf.d`)

# Supervisor settings for Dispatcher
```
[program:klara_dispatcher]
command=/home/projects/.virtualenvs/klara/bin/python klara-dispatcher
directory=/var/projects/klara/dispatcher
user=projects
autostart=true
autorestart=true
stdout_logfile=/var/projects/klara/logs/dispatcher.log
stderr_logfile=/var/projects/klara/logs/dispatcher.err
```

# Supervisor for Worker

```
[program:klara_worker1]
command=/home/projects/.virtualenvs/klara/bin/python klara-worker
directory=/var/projects/klara/dispatcher
user=projects
autostart=true
autorestart=true
stdout_logfile=/var/projects/klara/logs/worker1.log
stderr_logfile=/var/projects/klara/logs/worker1.err
```

TIP: if you want to run multiple workers on the same machine, you can run multiple klara-worker instances. Just setup multiple `[program:]` instances running the 
same `klara-worker` command

```
[program:klara_worker2]
command=/home/projects/.virtualenvs/klara/bin/python klara-worker
directory=/var/projects/klara/dispatcher
user=projects
autostart=true
autorestart=true
stdout_logfile=/var/projects/klara/logs/worker2.log
stderr_logfile=/var/projects/klara/logs/worker2.err


[program:klara_worker3]
command=/home/projects/.virtualenvs/klara/bin/python klara-worker
directory=/var/projects/klara/dispatcher
user=projects
autostart=true
autorestart=true
stdout_logfile=/var/projects/klara/logs/worker3.log
stderr_logfile=/var/projects/klara/logs/worker3.err
```