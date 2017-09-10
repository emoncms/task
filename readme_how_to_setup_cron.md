#Task Cron setup

The aim of the Task Module is to run the tasks automatically when they are enabled and it is the time to do so.

The script *tasks_cron.php* is the one that runs them. It is intended to be a cron job (Linux) or a scheduled task (Windows) so that the schedule can be run periodically.


```
sudo sed -i '$a 1 * * * * root php /var/www/emoncms/Modules/tasks/tasks_cron.php' /etc/crontab
```

This assumes your emonCMS installation is in `/var/www/emoncms`. 

The cron job checks every 1s (or different if $task_cron_frequency defined in settings.php) for tasks that need to be run.