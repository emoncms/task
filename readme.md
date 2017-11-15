## Task module

**Status:** beta (testing)

An emonCMS module that allows to create Process Lists that get triggered at specific times (aka Tasks). It consist in an UI accessible through emonCMS and a cron task that run the task that are due.

## Installation
As any other module: clone this repository in the Modules directory of your emonCMS installation and update database.
You also need to update your emonCMS from here: [emonCMS with Task Module support](https://github.com/carboncoop/emoncms/tree/task_module_support)

## Features
- Create tasks
- Specify frequency for running the task: only once, once a month or every certain number of weeks, days, hours or minutes
- Set Next Run date and time manually
- Enable/ disable tasks
- Run a task with one click (even if it is disabled and without updating the Next Run date and time)
- New process to check when a feed or input was last updated
- New processes for getting inputid and feedid (should go before any of the processes in the previous bullet)

## Task Cron setup
The aim of the Task Module is to run the tasks automatically when they are enabled and it is the time to do so.
The script *tasks_cron.php* is the one that runs them. It is intended to be a cron job (Linux) or a scheduled task (Windows) so that the schedule can be run periodically.

To add the cron entry to crontab manually, first open crontab with:

    sudo crontab -e
    
Then add the following line:

    * * * * * php /var/www/emoncms/Modules/task/task_cron.php >> /var/log/emoncms-task.log

This assumes your emonCMS installation is in `/var/www/emoncms`. 
The cron job checks every 5s (or different if $task_cron_frequency defined in settings.php) for tasks that need to be run.
Note: ensure permissions for lockfile are 666
Note*: when setting up the cron job it is better to change the root user to the same one that runs the server, in linux systems it normally is www-data

## ToDo list
- Redis support

## Future developments (who knows when)
- Add group support: this would allow access to group user's feeds. Useful for calculating aggregation feeds (this can currently be done from groups module view)

