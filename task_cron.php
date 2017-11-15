<?php

/*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

 */

define('EMONCMS_EXEC', 1);



// Report all PHP errors 
ini_set('error_reporting', E_ALL);
// Set the display_errors directive to On 
ini_set('display_errors', 1);

// 0) Set working directory
$current_dir = __DIR__;
$new_dir = str_replace('/Modules/task', '', $current_dir);
chdir($new_dir);

/*  1) A cron process must be set in order to start running this process. Once this 
 * process starts, it's kept in a while statement with a delay (sleep()). To 
 * avoid new cron processes overlap with this one a lockfile is set
 * 
 */
$fp = fopen("Modules/task/lockfile", "w");
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "Already running\n";
    die;
}

// 2) Load settings and core scripts
require "process_settings.php";

// 3) Database
$mysqli = new mysqli($server, $username, $password, $database);
if ($redis_enabled) {
    $redis = new Redis();
    $connected = $redis->connect($redis_server['host'], $redis_server['port']);
    if (!$connected) {
        echo "Can't connect to redis at " . $redis_server['host'] . ":" . $redis_server['port'] . " , it may be that redis-server is not installed or started see readme for redis installation";
        die;
    }
    if (!empty($redis_server['prefix']))
        $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
    if (!empty($redis_server['auth'])) {
        if (!$redis->auth($redis_server['auth'])) {
            echo "Can't connect to redis at " . $redis_server['host'] . ", autentication failed";
            die;
        }
    }
}
else {
    $redis = false;
}

//
// 4) Include files
include "Lib/EmonLogger.php";

include "Modules/user/user_model.php";
$user = new User($mysqli, $redis);

include "Modules/feed/feed_model.php";
$feed = new Feed($mysqli, $redis, $feed_settings);
include "Modules/input/input_model.php";
$input = new Input($mysqli, $redis, $feed);
include "Modules/process/process_model.php";
$process = new Process($mysqli, $input, $feed, 'UTC');

require_once "Modules/task/task_model.php";
$task = new Task($mysqli, $redis, $process, $user);

// 5) Run the "daemon", this is the "main" running in a loop
if (!isset($task_cron_frequency))   // Script update rate, defined in settings.php
    $task_cron_frequency = 1;       // secs

while (true) {
    $task->runScheduledTasks();
    sleep($task_cron_frequency);
    //die;
}
