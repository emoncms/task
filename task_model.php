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
    private $log;

    public function __construct($mysqli, $redis, $process) {
        $this->log = new EmonLogger(__FILE__);
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        /* $this->feed = $feed;
          $this->group = $group; */
        $this->process = $process;
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
//ToDo
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
            return $this->redis_get_task($id, $userid);
        }
        else {
            return $this->mysql_get_task($userid, $id);
        }
    }

    private function redis_get_task($userid, $id) {
//ToDo
    }

    private function mysql_get_task($userid, $id) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM tasks WHERE `userid` = '$userid' AND `id`='$id'");
        if ($result->num_rows > 0)
            return $result->fetch_array();
        else
            return false;
    }

    public function getTaskByTaskId($userid, $id) {
        $id = (int) $id;
        $userid = (int) $userid;

        if ($this->redis) {
            return $this->redis_getTaskByTaskId($id);
        }
        else {
            return $this->mysql_getTaskByTaskId($id);
        }
    }

    private function redis_getTaskByTaskId($userid, $id) {
//ToDo
    }

    private function mysql_getTaskByTaskId($userid, $id) {
        $result = $this->mysqli->query("SELECT * FROM tasks WHERE `id`='$id' AND `userid` = '$userid'");
        if ($result->num_rows > 0)
            return $result->fetch_array();
        else
            return false;
    }

//--------------------------
// Run Tasks that are due
//--------------------------
    public function runScheduledTasks() {
        $enabled_tasks = $this->getEnabledTasks();
        foreach ($enabled_tasks as $task) {
            if ($task['run_on'] < time() && $task['run_on'] != 0) { // when run_on is 0, it means that it doens't need to be run. run_on is set to 0 when frequency is 0 which means that task should only be run once
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
//ToDo
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
            if ($this->redis && $task_created) {
//ToDo insert task
            }
            if ($task_created == false || $task_created == 0)
                return array('success' => false, 'message' => "Task could not be saved");
            else
                return $this->mysqli->insert_id;
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
        if (isset($fields->enabled))
            $array[] = "`enabled` = '" . (bool) $fields->enabled . "'";
        if (isset($fields->run_on))
            $array[] = "`run_on` = '" . preg_replace('/([^0-9])/', '', $fields->run_on) . "'";
        if (isset($fields->time))
            $array[] = "`time` = '" . preg_replace('/([^0-9])/', '', $fields->time) . "'";

// Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",", $array);
        $this->mysqli->query("UPDATE tasks SET " . $fieldstr . " WHERE `id` = '$id' AND `userid` = '$userid'");

// CHECK REDIS?
// UPDATE REDIS
        /* if (isset($fields->name) && $this->redis)
          $this->redis->hset("input:$id", 'name', $fields->name);
          if (isset($fields->description) && $this->redis)
          $this->redis->hset("input:$id", 'description', $fields->description);
         */
        if ($this->mysqli->affected_rows > 0) {
            return array('success' => true, 'message' => 'Field updated');
        }
        else {
            return array('success' => false, 'message' => 'Field could not be updated');
        }
    }

    public function set_processlist($userid, $id, $processlist) {
        $userid = (int) $userid;
        $id = (int) $id;
        $processlist = preg_replace('/([^0-9:],)/', '', $processlist);

        $this->mysqli->query("UPDATE tasks SET processList = '$processlist' WHERE id='$id' AND userid='$userid'");
        if ($this->mysqli->affected_rows > 0) {
// CHECK REDIS
//if ($this->redis) $this->redis->hset("feed:$id",'processList',$processlist);
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
//ToDo delete task
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
        $this->process->input(time(), 0, $task['processList'], $opt);
        if ($update_next_run === true) {
            $frequency = json_decode($task['frequency']);
            if ($frequency->type == 'number_of') {
                $seconds = 7 * 24 * 3600 * $frequency->weeks;
                $seconds += 24 * 3600 * $frequency->days;
                $seconds += 3600 * $frequency->hours;
                $seconds += 60 * $frequency->minutes;
                $seconds += $frequency->seconds;
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
        $query = "SELECT id FROM tasks WHERE `id`='$id' AND `userid`='$userid'";

        if ($this->redis) {
//ToDo check if task exists
        }
        else {
            $query_result = $this->mysqli->query("SELECT id FROM tasks WHERE `id`='$id' AND `userid`='$userid'");
        }
        if ($query_result->num_rows > 0)
            return true;
        else
            return false;
    }

    private function task_exists($id) {
        if ($this->redis) {
//ToDo check if task exists
        }
        else {
            $query_result = $this->mysqli->query("SELECT id FROM tasks WHERE `id` = '$id'");
        }
        if ($query_result->num_rows > 0)
            return true;
        else
            return false;
    }

    public function disableTask($userid, $id) {
        $userid = (int) $userid;
        $id = (int) $id;
        $result = $this->mysqli->query("UPDATE `tasks` SET `enabled`='0' WHERE `id`= '$id' AND `userid`='$userid'");
        if ($this->redis) {
//ToDo
        }
        return $result;
    }

    private function setRunOn($id, $new_run_on_time) {
        $id = (int) $id;
        $new_run_on_time = preg_replace('/([^0-9])/', '', $new_run_on_time);

        $result = $this->mysqli->query("UPDATE `tasks` SET `run_on`='$new_run_on_time' WHERE `id`= '$id'");
        if ($this->redis) {
//ToDo
        }
        return $result;
    }

    private function setLastRun($id, $last_run_time) {
        $id = (int) $id;
        $last_run_time = preg_replace('/([^0-9])/', '', $last_run_time);

        $result = $this->mysqli->query("UPDATE `tasks` SET `time`='$last_run_time' WHERE `id`= '$id'");
        if ($this->redis) {
//ToDo
        }
        return $result;
    }

    private function name_exists($userid, $name) {
        if ($this->redis) {
            
        }
        else {
            $result = $this->mysqli->query("SELECT id FROM tasks WHERE `name` = '$name' AND `userid` = '$userid'");
        }
        if ($result->num_rows > 0)
            return true;
        else
            return false;
    }

}
