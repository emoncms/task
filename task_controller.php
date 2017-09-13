<?php

/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function task_controller() {
    global $mysqli, $redis, $session, $route, $user, $feed_settings;

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli, $redis, $feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli, $redis, $feed);

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli, $input, $feed, $user->get_timezone($session['userid']));

    $result = $mysqli->query("SHOW TABLES LIKE 'groups'");
    if ($result->num_rows > 0) {
        require_once "Modules/group/group_model.php";
        $group = new Group($mysqli, $redis, $user, $feed, null);
    }
    else {
        $group = null;
    }

    require_once "Modules/task/task_model.php";
    $task = new Task($mysqli, $redis, $process);

    if ($session['write']) {
        if ($route->action == "" || $route->action == "list") {
            $route->format = "html";
            $result = view("Modules/task/task_view.php", array());
        }
        if ($route->action == 'settask') {
            $route->format = "json";
            $result = $task->set_fields($session['userid'], get('taskid'), get('fields'));
        }
        if ($route->action == 'deletetask') {
            $route->format = "json";
            $result = $task->delete_task($session['userid'], get('taskid'));
        }
        if ($route->action == 'setprocesslist') {
            $route->format = "json";
            $result = $task->set_processlist($session['userid'], get('id'), post('processlist'));
        }
    }
    if ($session['read']) {
        if ($route->action == "getusertasks") {
            $route->format = "json";
            $result = $task->get_tasks($session['userid']);
        }
    }

    return array('content' => $result);
}
