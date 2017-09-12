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
            return $this->mysql_get_task($id, $userid);
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
            if ($task['run_on'] < time()) {
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

    //  Save Tasks
    public function save_task($userid, $attributes) {
        $id = (int) $attributes['id'];
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s-.]/', '', $attributes['name']);
        $description = preg_replace('/[^\w\s-.]/', '', $attributes['description']);
        $run_on = (preg_replace('/([^0-9\-: ])/', '', $attributes['run_on']));
//$run_on = $attributes['run_on'];
        $expiry_date = preg_replace('/([^0-9\-: ])/', '', $attributes['expiry_date']);
        $frequency = (int) $attributes['frequency'];
        $blocks = preg_replace('/[^\w\s-.\/<>"=]/', '', $attributes['blocks']);
        $enabled = $attributes['enabled'] == 'true' ? 1 : 0;

        if ($this->task_exists($attributes['id']) == false) {
            $task_saved = $this->mysqli->query("INSERT INTO `tasks` (`userid`, `name`, `description`, `run_on`, `expiry_date`, `frequency`, `blocks`,`enabled`) VALUES ('$userid', '$name', '$description', '$run_on', '$expiry_date', '$frequency', '$blocks','$enabled')");
            if ($this->redis && $task_saved) {
//ToDo insert task
            }
            if ($task_saved == false || $task_saved == 0)
                return 0;
            else
                return $this->mysqli->insert_id;
        } else {
            $task_saved = $this->mysqli->query("UPDATE `tasks` SET `name`='$name', `description`='$description', `run_on`='$run_on', `expiry_date`='$expiry_date', `frequency`='$frequency', `blocks`='$blocks', `enabled`='$enabled' WHERE `id`= '$id' AND `userid` = '$userid'");
            if ($this->redis && $task_saved) {
//ToDo update task
            }
            if ($task_saved == false || $task_saved == 0)
                return 0;
            else
                return $id;
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
    private function run_task($task) {
        $opt = array('sourcetype' => ProcessOriginType::TASK, 'sourceid' => $task['id']);
        $this->process->input(time(), 0, $task['processList'], $opt);
        $this->setRunOn($task['id'], time() + $task['frequency']);
        $this->setLastRun($task['id'], time());
        echo('/npasado/n');
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
        $new_run_on_time = (int) $new_run_on_time;

        $result = $this->mysqli->query("UPDATE `tasks` SET `run_on`='$new_run_on_time' WHERE `id`= '$id'");
        if ($this->redis) {
//ToDo
        }
        return $result;
    }
        private function setLastRun($id, $last_run_time) {
        $id = (int) $id;
        $last_run_time = (int) $last_run_time;

        $result = $this->mysqli->query("UPDATE `tasks` SET `time`='$last_run_time' WHERE `id`= '$id'");
        if ($this->redis) {
//ToDo
        }
        return $result;
    }

    public function set_processlist($id, $processlist) {
        $stmt = $this->mysqli->prepare("UPDATE tasks SET processList=? WHERE id=?");
        $stmt->bind_param("si", $processlist, $id);
        if (!$stmt->execute()) {
            return array('success' => false, 'message' => _("Error setting processlist"));
        }

        if ($this->mysqli->affected_rows > 0) {
            // CHECK REDIS
            if ($this->redis) {
                //$this->redis->hset("input:$id", 'processList', $processlist);
            }
            return array('success' => true, 'message' => 'Task processlist updated');
        }
        else {
            return array('success' => false, 'message' => 'Task processlist was not updated');
        }
    }

}
