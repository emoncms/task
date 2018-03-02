<?php

/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
  ---------------------------------------------------------------------
  OEMan - Open Energy Management system for the OpenEnergyMonitor
  Developed by the Centre for Alternative Technology
  http://cat.org.uk

 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Task {

    private $mysqli;
    private $redis;
    private $process;
    private $user;
    private $log;

    public function __construct($mysqli, $redis, $process, $user = null) {
        $this->log = new EmonLogger(__FILE__);
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        /* $this->feed = $feed;
          $this->group = $group; */
        $this->process = $process;
        $this->user = $user;

        if ($this->redis && !$this->redis->exists('tasks_loaded')) {
            $this->load_to_redis();
            $this->redis->set('tasks_loaded', true);
        }
    }

//--------------------------
// Get Tasks
//--------------------------
    public function get_tasks($userid) {
        $userid = (int) $userid;

        if ($this->redis) {
            return $this->redis_get_tasks($userid);
        }
        else {
            return $this->mysql_get_tasks($userid);
        }
    }

    private function redis_get_tasks($userid) {
        $userid = (int) $userid;
        $array_of_tasks = array();
        $taskids = $this->redis->sMembers("user:tasks:$userid");

        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($taskids as $id)
            $pipe->hGetAll("tasks:$id");

        $array_of_tasks = $pipe->exec();
        return $array_of_tasks;
    }

    private function mysql_get_tasks($userid) {
        $userid = (int) $userid;
        $array_of_tasks = array();
        $result = $this->mysqli->query("SELECT * FROM tasks WHERE `userid` = '$userid'");
        for ($i = 0; $row = (array) $result->fetch_object(); $i++) {
            $array_of_tasks[$i] = $row;
        }
        return $array_of_tasks;
    }

    public function get_task($userid, $id) {
        $userid = (int) $userid;
        $id = (int) $id;

        if ($this->redis) {
            return $this->redis_get_task($userid, $id);
        }
        else {
            return $this->mysql_get_task($userid, $id);
        }
    }

    private function redis_get_task($userid, $id) {
        if ($this->redis->sismember("user:tasks:$userid", $id)) {
            return $this->redis->hgetall("tasks:$id");
        }
        else {
            return false;
        }
    }

    private function mysql_get_task($userid, $id) {
        $result = $this->mysqli->query("SELECT * FROM tasks WHERE `userid` = '$userid' AND `id`='$id'");
        if ($result->num_rows > 0)
            return $result->fetch_array();
        else
            return false;
    }

//--------------------------
// Run Tasks that are due
//--------------------------
    public function runScheduledTasks() {
        global $session;
        $enabled_tasks = $this->getEnabledTasks();
        foreach ($enabled_tasks as $task) {
            if ($task['run_on'] < time() && $task['run_on'] != 0) { // when run_on is 0, it means that it doens't need to be run. run_on is set to 0 when frequency is 0 which means that task should only be run once
                $session['userid'] = $task['userid'];
                $this->run_task($task);
            }
        }
    }

    public function getEnabledTasks() {
        if ($this->redis) {
            return $this->redis_getEnabledTasks();
        }
        else {
            return $this->mysql_getEnabledTasks();
        }
    }

    private function redis_getEnabledTasks() {
        $enabled_tasks = array();
        $tasks = $this->redis->keys("tasks:*");
        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($tasks as $task) {
            $task = str_replace("emoncms:", "", $task);
            $pipe->hGetAll($task);
        }

        $tasks = $pipe->exec();
        foreach ($tasks as $task) {
            if ($task['enabled'] == '1')
                $enabled_tasks[] = $task;
        }
        return $enabled_tasks;
    }

    private function mysql_getEnabledTasks() {
        $array_of_tasks = array();
        $result = $this->mysqli->query("SELECT * FROM tasks WHERE `enabled` = '1'");
        for ($i = 0; $row = (array) $result->fetch_object(); $i++) {
            $array_of_tasks[$i] = $row;
        }
        return $array_of_tasks;
    }

//--------------------------
//  Save Tasks
//--------------------------
    public function create_task($userid, $name, $description, $tag, $frequency, $run_on, $enabled = 0) {
        $userid = (int) $userid;
        $name = preg_replace('/[^\p{N}\p{L}_\s-:]/u', '', $name);
        $description = preg_replace('/[^\p{N}\p{L}_\s-:]/u', '', $description);
        $tag = preg_replace('/[^\p{N}\p{L}_\s-:]/u', '', $tag);
        $run_on = (preg_replace('/([^0-9])/', '', $run_on));
        $frequency = preg_replace("/[^\p{L}_\p{N}\s-.],'/u", '', $frequency);
        $enabled = (int) $enabled;

        if ($this->name_exists($userid, $name) == true)
            return array('success' => false, 'message' => "Task name already exists");
        else {
            $task_created = $this->mysqli->query("INSERT INTO `tasks` (`userid`, `name`, `description`, `tag`, `run_on`, `frequency`, `enabled`) VALUES ('$userid', '$name', '$description', '$tag', '$run_on', '$frequency','$enabled')");
            $task_id = $this->mysqli->insert_id;
            if ($this->redis && $task_created) {
                $this->redis->sadd("user:tasks:$userid", $task_id);
                $this->redis->hmset("tasks:$task_id", [
                    'id' => $task_id,
                    'userid' => $userid,
                    'name' => $name,
                    'description' => $description,
                    'tag' => $tag,
                    'run_on' => $run_on,
                    'time' => 0,
                    'frequency' => $frequency,
                    'enabled' => $enabled
                ]);
            }
            if ($task_created == false || $task_created == 0)
                return array('success' => false, 'message' => "Task could not be saved");
            else
                return $task_id;
        }
    }

    public function set_fields($userid, $id, $fields) {
        $userid = (int) $userid;
        $id = (int) $id;
        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->description))
            $array[] = "`description` = '" . preg_replace('/[^\p{L}_\p{N}\s-]/u', '', $fields->description) . "'";
        if (isset($fields->name))
            $array[] = "`name` = '" . preg_replace('/[^\p{L}_\p{N}\s-.]/u', '', $fields->name) . "'";
        if (isset($fields->tag))
            $array[] = "`tag` = '" . preg_replace('/[^\p{L}_\p{N}\s-.]/u', '', $fields->tag) . "'";
        if (isset($fields->frequency)) {
            $str = "`frequency` = '" . json_encode($fields->frequency) . "'";
            $str = str_replace('"', '\"', $str);  // add slashes, otherwise SQL query below breaks
            $array[] = $str;
        }
        if (isset($fields->enabled)) {
            $enabled = (bool) $fields->enabled;
            if ($enabled === true)
                $array[] = "`enabled` = '1'";
            else
                $array[] = "`enabled` = '0'";
        }
        if (isset($fields->run_on))
            $array[] = "`run_on` = '" . preg_replace('/([^0-9])/', '', $fields->run_on) . "'";
        if (isset($fields->time))
            $array[] = "`time` = '" . preg_replace('/([^0-9])/', '', $fields->time) . "'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",", $array);
        $this->mysqli->query("UPDATE tasks SET " . $fieldstr . " WHERE `id` = '$id' AND `userid` = '$userid'");
        //$result= $this->mysqli->store_result();
        //$eroor = $this->mysqli->error;
        if ($this->mysqli->affected_rows > 0) {
            $success = true;
        }
        else {
            $success = false;
            $error = $this->mysqli->error;
            $this->log->warn("Error saving task field in database -  " . $this->mysqli->error);
        }

        // Update in redis
        if ($success && $this->redis) {
            $pipe = $this->redis->multi(REDIS::PIPELINE);
            if (isset($fields->description))
                $this->redis->hset("tasks:$id", 'description', preg_replace('/[^\p{L}_\p{N}\s-]/u', '', $fields->description));
            if (isset($fields->name))
                $this->redis->hset("tasks:$id", 'name', preg_replace('/[^\p{L}_\p{N}\s-.]/u', '', $fields->name));
            if (isset($fields->tag))
                $this->redis->hset("tasks:$id", 'tag', preg_replace('/[^\p{L}_\p{N}\s-.]/u', '', $fields->tag));
            if (isset($fields->frequency)) {
                $this->redis->hset("tasks:$id", 'frequency', json_encode($fields->frequency));
            }
            if (isset($fields->enabled)) {
                $asdfs = (bool) $fields->enabled;
                $this->redis->hset("tasks:$id", 'enabled', (bool) $fields->enabled === true ? '1' : '0');
            }
            if (isset($fields->run_on))
                $this->redis->hset("tasks:$id", 'run_on', preg_replace('/([^0-9])/', '', $fields->run_on));
            if (isset($fields->time))
                $this->redis->hset("tasks:$id", 'time', preg_replace('/([^0-9])/', '', $fields->time));
            $pipe->exec();
        }

        if ($success) {
            return array('success' => true, 'message' => 'Field updated');
        }
        else {
            return array('success' => false, 'message' => 'Field could not be updated - ' . $error);
        }
    }

    public function set_processlist($userid, $id, $processlist) {
        $userid = (int) $userid;
        $id = (int) $id;
        $processlist = preg_replace('/([^a-zA-Z0-9:,_{}(). ])/', '', $processlist);

        // Validate processlist
        $process_list = $this->process->get_process_list(); // list of available processes 
        $pairs = explode(",", $processlist);
        foreach ($pairs as $pair) {
            $inputprocess = explode(":", $pair);
            if (count($inputprocess) == 2) {
                $processid = (int) $inputprocess[0];
                $arg = (int) $inputprocess[1];

                // Check that feed exists and user has ownership
                if (isset($process_list[$processid]) && $process_list[$processid][1] == ProcessArg::FEEDID) {
                    if (!$this->process->feed->access($userid, $arg)) {
                        return array('success' => false, 'message' => _("Invalid feed"));
                    }
                }

                // Check that input exists and user has ownership
                if (isset($process_list[$processid]) && $process_list[$processid][1] == ProcessArg::INPUTID) {
                    $inputid = (int) $arg;
                    $result = $this->mysqli->query("SELECT id FROM input WHERE `userid` = '$userid' AND `id` = '$arg'");
                    if ($result->num_rows != 1)
                        return array('success' => false, 'message' => _("Invalid input"));
                }
            }
        }

        // Save processList
        $this->mysqli->query("UPDATE tasks SET `processList` = '$processlist' WHERE `id`='$id' AND `userid`='$userid'");
        if ($this->mysqli->affected_rows > 0) {
            if ($this->redis)
                $this->redis->hset("tasks:$id", 'processList', $processlist);
            return array('success' => true, 'message' => 'Task processlist updated');
        }
        else {
            return array('success' => false, 'message' => 'Task processlist was not updated');
        }
    }

//--------------------------
//  Delete Tasks
//--------------------------
    public function delete_task($userid, $id) {
        $id = (int) $id;
        $userid = (int) $userid;

        //check if task exists
        if ($this->task_exists($id) == true) {
            $task_deleted = $this->mysqli->query("DELETE FROM tasks WHERE `id` = '$id' AND `userid`='$userid'");
            if ($this->redis && $task_deleted) {
                $this->redis->del("tasks:$id");
                $this->redis->srem("user:tasks:$userid", $id);
            }
            return $task_deleted;
        }
        else {//if task not found in database
            return $task_deleted = false;
        }
    }

//--------------------------
//  Other methods    
//--------------------------
    public function run_user_task($userid, $taskid) {
        $taskid = (int) $taskid;
        $userid = (int) $userid;
        $task = $this->get_task($userid, $taskid);
        $this->run_task($task, $update_next_run = false);
    }

    private function run_task($task, $update_next_run = true) {
        $opt = array('sourcetype' => ProcessOriginType::TASK, 'sourceid' => $task['id']);
        if (is_null($task['processList']) == false && $task['processList'] != '') {
            $this->process->input(time(), 0, $task['processList'], $opt);
        }
        if ($update_next_run === true) {
            $frequency = json_decode($task['frequency']);
            if ($frequency->type == 'number_of') {
                $seconds = 7 * 24 * 3600 * $frequency->weeks;
                $seconds += 24 * 3600 * $frequency->days;
                $seconds += 3600 * $frequency->hours;
                $seconds += 60 * $frequency->minutes;
                $seconds += $frequency->seconds;
                if ($seconds == 0) {
                    $result = $this->disableTask($task['userid'], $task['id']);
                }
                $this->setRunOn($task['id'], time() + $seconds);
            }
            elseif ($frequency->type == 'one_time') // Task to be run only once
                $this->setRunOn($task['id'], 0); // when run_on is 0 the task is not run anymore
            elseif ($frequency->type == 'once_a_month') { // Task to be run the same day of the month, if the next month hasn't got that day (ie 30th of Feb) then we skip that month
                $current_date = time();
                $current_day = date('d', $current_date);
                $current_month = date('m', $current_date);
                $current_year = date('y', $current_date);
                $original_day = $current_day;
                do {
                    // If the we're in Dec (12), set current month to Jan (1), add 1 to year.
                    if ($current_month == 12) {
                        $current_month = 1;
                        $current_year = $current_year + 1;
                        $next_run_on = mktime(0, 0, 0, 1, $current_day, $current_year);
                    }
                    // Otherwise, add a month to the next month and calculate the date.
                    else {
                        $current_month = $current_month + 1;
                        $next_run_on = mktime(0, 0, 0, $current_month, $current_day, $current_year);
                    }
                } while (date('d', $next_run_on) != $current_day);
                $this->setRunOn($task['id'], $next_run_on);
            }
        }
        $this->setLastRun($task['id'], time());
        return true;
    }

    public function task_belongs_to_user($id, $userid) {
        $id = (int) $id;
        $userid = (int) $userid;

        if ($this->redis) {
            if ($this->redis->sismember("user:tasks:$userid", $id))
                return true;
            else
                return false;
        }
        else {
            $query_result = $this->mysqli->query("SELECT id FROM tasks WHERE `id`='$id' AND `userid`='$userid'");
            if ($query_result->num_rows > 0)
                return true;
            else
                return false;
        }
    }

    private function task_exists($id) {
        if ($this->redis) {
            if ($this->redis->exists("tasks:$id"))
                return true;
            else
                return false;
        }
        else {
            $query_result = $this->mysqli->query("SELECT id FROM tasks WHERE `id` = '$id'");
            if ($query_result->num_rows > 0)
                return true;
            else
                return false;
        }
    }

    public function disableTask($userid, $id) {
        $userid = (int) $userid;
        $id = (int) $id;
        $result = $this->mysqli->query("UPDATE `tasks` SET `enabled`='0' WHERE `id`= '$id' AND `userid`='$userid'");
        if ($this->redis) {
            $this->redis->hset("tasks:$id", "enabled", '0');
        }
        return $result;
    }

    private function setRunOn($id, $new_run_on_time) {
        $id = (int) $id;
        $new_run_on_time = preg_replace('/([^0-9])/', '', $new_run_on_time);

        $result = $this->mysqli->query("UPDATE `tasks` SET `run_on`='$new_run_on_time' WHERE `id`= '$id'");
        if ($this->redis) {
            $this->redis->hset("tasks:$id", "run_on", "$new_run_on_time");
        }
        return $result;
    }

    private function setLastRun($id, $last_run_time) {
        $id = (int) $id;
        $last_run_time = preg_replace('/([^0-9])/', '', $last_run_time);

        $result = $this->mysqli->query("UPDATE `tasks` SET `time`='$last_run_time' WHERE `id`= '$id'");
        if ($this->redis) {
            $this->redis->hset("tasks:$id", "time", "$last_run_time");
        }
        return $result;
    }

    private function name_exists($userid, $name) {
        $result = $this->mysqli->query("SELECT id FROM tasks WHERE `name` = '$name' AND `userid` = '$userid'");
        if ($result->num_rows > 0)
            return true;
        else
            return false;
    }

    private function load_to_redis() {
        $result = $this->mysqli->query("SELECT * FROM tasks ORDER BY userid,name asc");
        while ($row = $result->fetch_object()) {
            $this->redis->sAdd("user:tasks:" . $row->userid, $row->id);
            $this->redis->hMSet("tasks:$row->id", array(
                'id' => $row->id,
                'userid' => $row->userid,
                'name' => $row->name,
                'description' => $row->description,
                'tag' => $row->tag,
                'run_on' => $row->run_on,
                'frequency' => $row->frequency,
                'processList' => $row->processList,
                'time' => $row->time,
                'enabled' => $row->enabled
            ));
        }
    }

}
