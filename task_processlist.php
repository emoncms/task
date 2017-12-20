<?php

/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project: http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Schedule Processlist Module
class Task_ProcessList {

    private $log;
    private $mysqli;
    private $feed;
    private $input;
    private $task;
    private $proc_goto;          // goto step in process list

// Module required constructor, receives parent as reference

    public function __construct(&$parent) {
        global $redis;

        $this->log = new EmonLogger(__FILE__);
        $this->mysqli = &$parent->mysqli;
        $this->feed = &$parent->feed;
        $this->input = &$parent->input;
        $this->parentProcessModel = &$parent;
        $this->proc_goto = &$parent->proc_goto;
        require_once "Modules/task/task_model.php";
        $this->task = new Task($this->mysqli, $redis, $parent);
    }

    //*****************************************
    // Module required process configuration, 
    // $list array index position is not used, 
    // function name is used instead
    public function process_list() {
// 0=>Name | 1=>Arg type | 2=>function | 3=>No. of datafields if creating feed | 4=>Datatype | 5=>Group | 6=>Engines | 'desc'=>Description | 'requireredis'=>true | 'nochange'=>true  | 'helpurl'=>"http://..."
        $list[] = array(_("Get feed id"), ProcessArg::FEEDID, "get_feed_id", 0, DataType::UNDEFINED, "Get id", 'desc' => _("<p>Passes the id of the selected feed to the next process </p>"), 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("Get input id"), ProcessArg::INPUTID, "get_input_id", 0, DataType::UNDEFINED, "Get id", 'desc' => _("<p>Passes the id of the selected input to the next process </p>"), 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("If feed last update > secs, go to next"), ProcessArg::VALUE, "feed_last_update_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the feed hasn't been updated for the specified amount of seconds. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("If input last update > secs, go to next"), ProcessArg::VALUE, "input_last_update_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the input hasn't been updated for the specified amount of seconds. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("Send email about a feed"), ProcessArg::TEXT, "send_email_feed", 0, DataType::UNDEFINED, "Notifications (id passed as value)", 'desc' => _("<p>Send an email to the user with the specified body.</p><p>Supported template tags to customize body: {current_time}, {name}, {id}, {last_update}, {value}</p><p>Example body text: At {current_time}, the last update of {name} (feed id: {id}) was on {last_update} and it's value was {value}.</p><p><b>It requires that the value passsed from previous process is a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("Send email about an input"), ProcessArg::TEXT, "send_email_input", 0, DataType::UNDEFINED, "Notifications (id passed as value)", 'desc' => _("<p>Send an email to the user with the specified body.</p><p>Supported template tags to customize body: {id}, {key}, {name}, {node}, {current_time}, {value}, {last_update}</p><p>Example body text: At {current_time} your input from node {node} with key {key} named {name} had value {value} and was last updated {last_update}.</p><p><b>It requires that the value passsed from previous process is a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("If feed last value >, go to next"), ProcessArg::VALUE, "feed_last_value_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the feed is greater than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("If feed last value <, go to next"), ProcessArg::VALUE, "feed_last_value_less", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the feed is less than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("If input last value >, go to next"), ProcessArg::VALUE, "input_last_value_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the input is greater than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("If input last value <, go to next"), ProcessArg::VALUE, "input_last_value_less", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the input is less than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("Fix max value"), ProcessArg::VALUE, "fix_max_value", 0, DataType::UNDEFINED, "Feed sanitation (id passed as value)", 'desc' => _("<p>In a feed, search last quarter for datapoints greater than 'max value' and fix them when found</p><p>The value passed to next process will be 'true' if any datapoint has been fixed, false if none</p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("Fix min value"), ProcessArg::VALUE, "fix_min_value", 0, DataType::UNDEFINED, "Feed sanitation (id passed as value)", 'desc' => _("<p>In a feed, search last quarter for datapoints lower than 'min value' and fix them when found</p><p>The value passed to next process will be 'true' if any datapoint has been fixed, false if none</p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("Interpolate missing datapoints (PHPFINA)"), ProcessArg::NONE, "fix_missing_values", 0, DataType::UNDEFINED, "Feed sanitation (id passed as value)", 'desc' => _("<p>In a feed, search last quarter for missing datapoints. This process can only be applied to PHPFINA feeds (fixed interval).</p><p>The value passed to next process will be 'true' if any datapoint has been fixed, false if none</p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("EXIT"), ProcessArg::NONE, "error_found_access_forbidden", 0, DataType::UNDEFINED, "Hidden", 'desc' => "<p>This was automaticaly added because a user's task was trying to acces a feed or input that the user has no access to.</p>", 'internalerror' => true, 'internalerror_reason' => "NO ACCESS TO FEED/INPUT", 'internalerror_desc' => 'Processlist disabled as it uses a feed/input the user has no access to.');
        $list[] = array(_("EXIT"), ProcessArg::NONE, "error_found_too_many_datapoints", 0, DataType::UNDEFINED, "Hidden", 'desc' => "<p>This was automaticaly added because the dataset to fix was too big .</p>", 'internalerror' => true, 'internalerror_reason' => "TOO MANY DATAPOINTS", 'internalerror_desc' => 'Processlist disabled as it tried to fix a dataset too big.');
        $list[] = array(_("EXIT"), ProcessArg::NONE, "error_found_opening_file engine", 0, DataType::UNDEFINED, "Hidden", 'desc' => "<p>This was automaticaly added because there were problems opening the feed file .</p>", 'internalerror' => true, 'internalerror_reason' => "CANNOT OPEN FEED FILE", 'internalerror_desc' => 'Processlist disabled as there were problems the data/meta data file.');
        $list[] = array(_("EXIT"), ProcessArg::NONE, "error_found_wrong_engine", 0, DataType::UNDEFINED, "Hidden", 'desc' => "<p>This was automaticaly added because the feed you are trying to fix is not PHPFINA .</p>", 'internalerror' => true, 'internalerror_reason' => "FEED IS NOT PHPFINA", 'internalerror_desc' => 'Processlist disabled as it tries to fix missing data in a non PHPFINA engine (fixed interval).');
        return $list;
    }

    //******************************************
    // Functions of this module processlist, 
    // same name must exist on process_list()

    public function get_feed_id($feedid, $time, $value, $options) {
        global $session;
        if ($this->user_has_access_to_feed($session['userid'], $feedid)) {
            return $feedid;
        }
        else {
            $this->adderror_log_end('get_feed_id', $options, 'access_forbidden');
            return false;
        }
    }

    public function get_input_id($inputid, $time, $value, $options) {
        global $session;
        if ($this->input->belongs_to_user($session['userid'], $inputid)) {
            return $inputid;
        }
        else {
            $this->adderror_log_end('get_input_id', $options, 'access_forbidden');
            return false;
        }
    }

    public function feed_last_update_greater($secs, $time, $value, $options) { // $value must be feedid
        global $session;
        if ($this->user_has_access_to_feed($session['userid'], $value)) {
            $last_update = $this->feed->get_timevalue($value);
            if ((time() - $last_update['time']) < $secs)
                $this->proc_goto = PHP_INT_MAX;
            return $value;
        }
        else {
            $this->adderror_log_end('feed_last_update_greater', $options, 'access_forbidden');
            return false;
        }
    }

    public function input_last_update_greater($secs, $time, $value, $options) {// $value must be inputid
        global $session;
        if ($this->input->belongs_to_user($session['userid'], $value)) {
            $result = $this->mysqli->query("SELECT time FROM input WHERE `id` = '$value'");
            $row = $result->fetch_array();
            $last_update = $row['time'];
            if ((time() - $last_update) < $secs)
                $this->proc_goto = PHP_INT_MAX;
            return $value;
        }
        else {
            $this->adderror_log_end('input_last_update_greater', $options, 'access_forbidden');
            return false;
        }
    }

    public function send_email_feed($emailbody, $time, $feedid, $options) {// $feedid is the value passed from previous process,  it must be a valid feedid!!!
        global $user, $session;
        if ($this->user_has_access_to_feed($session['userid'], $feedid)) {
            $timeformated = DateTime::createFromFormat("U", (int) $time);
            $timeformated->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
            $timeformated = $timeformated->format("Y-m-d H:i:s");

            $feed_data = $this->feed->get($feedid);
            $last_update = DateTime::createFromFormat("U", (int) $feed_data['time']);
            $last_update->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
            $last_update = $last_update->format("Y-m-d H:i:s");

            $tag = array("{name}", "{id}", "{current_time}", "{value}", "{last_update}");
            $replace = array($feed_data['name'], $feedid, $timeformated, $feed_data['value'], $last_update);
            $emailbody = str_replace($tag, $replace, $emailbody);

            $emailto = $user->get_email($session['userid']);
            require_once "Lib/email.php";
            $email = new Email();
//$email->from(from);
            $email->to($emailto);
            $email->subject('emonCMS notification');
            $email->body($emailbody);
            $result = $email->send();
            if (!$result['success']) {
                $this->log->error("Email send returned error. message='" . $result['message'] . "'");
            }
            else {
                $this->log->info("Email sent to $emailto");
            }
            return $feedid;
        }
        else {
            $this->adderror_log_end('send_email_feed', $options, 'access_forbidden');
            return false;
        }
    }

    public function send_email_input($emailbody, $time, $inputid, $options) {// $inputid is the value passed from previous process,  it must be a valid inputid!!!
        global $user, $session;
        if (!$this->input->belongs_to_user($session['userid'], $inputid)) {
            $this->adderror_log_end('send_email_input', $options, 'access_forbidden');
            return false;
        }
        else {
            $timeformated = DateTime::createFromFormat("U", (int) $time);
            $timeformated->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
            $timeformated = $timeformated->format("Y-m-d H:i:s");

            $result = $this->mysqli->query("SELECT time FROM input WHERE `id` = '$inputid'");
            $row = $result->fetch_array();
            $last_update = (int) $row['time'];
            $last_update = DateTime::createFromFormat("U", $last_update);
            $last_update->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
            $last_update = $last_update->format("Y-m-d H:i:s");

            $last_value = $this->parentProcessModel->input->get_last_value($inputid);

            $inputdetails = $this->parentProcessModel->input->get_details($inputid);

            $tag = array("{key}", "{id}", "{current_time}", "{value}", "{last_update}", "{name}", "{node}");
            $replace = array($inputdetails['name'], $inputid, $timeformated, $last_value, $last_update, $inputdetails['description'], $inputdetails['nodeid']);
            $emailbody = str_replace($tag, $replace, $emailbody);

            $emailto = $user->get_email($session['userid']);
            require_once "Lib/email.php";
            $email = new Email();
//$email->from(from);
            $email->to($emailto);
            $email->subject('emonCMS notification');
            $email->body($emailbody);
            $result = $email->send();
            if (!$result['success']) {
                $this->log->error("Email send returned error. message='" . $result['message'] . "'");
            }
            else {
                $this->log->info("Email sent to $emailto");
            }
            return $inputid;
        }
    }

    /* $list[] = array(_("If feed last value >, go to next"), ProcessArg::VALUE, "feed_last_value_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the feed is greater than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
      $list[] = array(_("If feed last value <, go to next"), ProcessArg::VALUE, "feed_last_value_less", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the feed is less than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid feed id (see <i>Get feed id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
      $list[] = array(_("If input last value >, go to next"), ProcessArg::VALUE, "input_last_value_greater", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the input is greater than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
      $list[] = array(_("If input last value <, go to next"), ProcessArg::VALUE, "input_last_value_less", 0, DataType::UNDEFINED, "Conditional (id passed as value)", 'desc' => _("<p>The execution of the processlist will carry on if the last value of the input is less than the specified. Otherwise it will stop. </p><p><b>The value passsed from previous process must be a valid input id (see <i>Get input id</i>)</b></p>"), 'requireredis' => false, 'nochange' => true);
     */

    public function feed_last_value_greater($cond_value, $time, $feedid, $options) {// $feedid is the value passed from previous process,  it must be a valid feedid!!!
        global $session;
        if ($this->user_has_access_to_feed($session['userid'], $feedid)) {
            $last_value = $this->feed->get_timevalue($feedid);
            if ($last_value['value'] < $cond_value)
                $this->proc_goto = PHP_INT_MAX;
            return $feedid;
        }
        else {
            $this->adderror_log_end('feed_last_value_greater', $options, 'access_forbidden');
            return false;
        }
    }

    public function feed_last_value_less($cond_value, $time, $feedid, $options) {// $feedid is the value passed from previous process,  it must be a valid feedid!!!
        global $session;
        if ($this->user_has_access_to_feed($session['userid'], $feedid)) {
            $last_value = $this->feed->get_timevalue($feedid);
            if ($last_value['value'] > $cond_value)
                $this->proc_goto = PHP_INT_MAX;
            return $feedid;
        }

        else {
            $this->adderror_log_end('feed_last_value_less', $options, 'access_forbidden');
            return false;
        }
    }

    public function input_last_value_greater($cond_value, $time, $inputid, $options) {// $inputid is the value passed from previous process,  it must be a valid inputid!!!
        global $session;

        if ($this->input->belongs_to_user($session['userid'], $inputid)) {
            $last_value = $this->parentProcessModel->input->get_last_value($inputid);
            if ($last_value < $cond_value)
                $this->proc_goto = PHP_INT_MAX;
            return $inputid;
        }
        else {
            $this->adderror_log_end('input_last_value_greater', $options, 'access_forbidden');
            return false;
        }
    }

    public function input_last_value_less($cond_value, $time, $inputid, $options) {// $inputid is the value passed from previous process,  it must be a valid inputid!!!
        global $session;

        if ($this->input->belongs_to_user($session['userid'], $inputid)) {
            $last_value = $this->parentProcessModel->input->get_last_value($inputid);
            if ($last_value > $cond_value)
                $this->proc_goto = PHP_INT_MAX;
            return $inputid;
        }
        else {
            $this->adderror_log_end('input_last_value_less', $options, 'access_forbidden');
            return false;
        }
    }

    public function fix_max_value($max_value, $time, $feedid, $options) {
        global $session;
        $start = ($time - 3 * 30 * 24 * 60 * 60) * 1000; // start time 3 months before now in miliseconds
        $end = 1000 * $time;

        if (!$this->user_has_access_to_feed($session['userid'], $feedid)) {
            $this->adderror_log_end('fix_max_value', $options, 'access_forbidden');
            return false;
        }
        else {
            $result = $this->feed->fix_data($feedid, $start, $end, $max_value, false, false);
            if ($result['success'] === false) {
                if ($result['error_code'] != 1) // only error code that we allow is the "empty dataset"
                    $this->adderror_log_end('fix_max_value', $options, $result['error_code']);
                return false;
            }
            else if (array_key_exists('datapoints_greater_fixed', $result))
                return true; // Some datapoints were fixed
            else
                return false; // No data point was fixed
        }
    }

    public function fix_min_value($min_value, $time, $feedid, $options) {
        global $session;
        $start = ($time - 3 * 30 * 24 * 60 * 60) * 1000; // start time 3 months before now in miliseconds
        $end = 1000 * $time;

        if (!$this->user_has_access_to_feed($session['userid'], $feedid)) {
            $this->adderror_log_end('fix_min_value', $options, 'access_forbidden');
            return false;
        }
        else {
            $result = $this->feed->fix_data($feedid, $start, $end, false, $min_value, false);
            if ($result['success'] === false) {
                if ($result['error_code'] != 1) // only error code that we allow is the "empty dataset"
                    $this->adderror_log_end('fix_min_value', $options, $result['error_code']);
                return false;
            }
            else if (array_key_exists('datapoints_lower_fixed', $result))
                return true; // Some datapoints were fixed
            else
                return false; // No data point was fixed
        }
    }

    public function fix_missing_values($min_value, $time, $feedid, $options) {
        global $session;
        $start = ($time - 3 * 30 * 24 * 60 * 60) * 1000; // start time 3 months before now in miliseconds
        $end = 1000 * $time;

        if (!$this->user_has_access_to_feed($session['userid'], $feedid)) {
            $this->adderror_log_end('fix_missing_values', $options, 'access_forbidden');
            return false;
        }
        else {
            // Check enginge is PHPFINA
            if ($this->redis) {
                $engine = $this->redis->hget("feed:$feedid", 'engine');
            }
            else {
                $result = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
                $row = $result->fetch_object();
                $engine = $row->engine;
            }
            if ($engine != ENGINE::PHPFINA) {
                $this->adderror_log_end('fix_missing_values', $options, 'wrong_engine');
                return false;
            }
            // Fix dataset
            $result = $this->feed->fix_data($feedid, $start, $end, false, false, true);
            if ($result['success'] === false) {
                if ($result['error_code'] != 1 || $result['error_code'] != 3) // only error codes that we allow are: "empty dataset" or first/last value null (interpolation not possible)
                    $this->adderror_log_end('fix_missing_values', $options, $result['error_code']);
                return false;
            }
            else if (array_key_exists('data_points_missing_fixed', $result))
                return true; // Some datapoints were fixed
            else
                return false; // No data point was fixed
        }
    }

    // Error processes

    public function error_found_access_forbidden($arg, $time, $value) {
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

    public function error_found_too_many_datapoints($arg, $time, $value) {
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

    public function error_found_opening_file($arg, $time, $value) {
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

    public function error_found_wrong_engine($arg, $time, $value) {
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

    // End Functions of this module processlist, 
    //**********************************
    //**********************************
    // Private functions

    private function user_has_access_to_feed($userid, $feedid) {
        $user_feeds = $this->feed->get_user_feed_ids($userid);
        return array_search($feedid, $user_feeds) === false ? false : true;
    }

    private function adderror_log_end($origin_function, $options, $error) {
        global $session;
        $task = $this->task->get_task($session['userid'], $options['sourceid']);
        $processList = $task['processList'];

        if ($error == 'access_forbidden') {
            // Add error
            $this->task->set_processlist($session['userid'], $options['sourceid'], "task__error_found_access_forbidden:0," . $processList);
            $this->parentProcessModel->runtime_error = ProcessError::ACCESS_FORBIDDEN;
            // Log error
            $this->log->error("Process: task__$origin_function -- Task: " . $options['sourceid'] . " -- User " . $session['userid'] . " is trying to access a feed or input which doesn't belong to him/her");
        }
        else if ($error === 0) { // error opening data or metadata file
            // Add error
            $this->task->set_processlist($session['userid'], $options['sourceid'], "task__error_found_opening_file:0," . $processList);
            $this->parentProcessModel->runtime_error = 'cant_open_file';
            // Log error
            $this->log->error("Process: task__$origin_function -- Task: " . $options['sourceid'] . " -- User " . $session['userid'] . ", task can't open data or meatadata file");
        }
        else if ($error === 2) { // error too many datapoints to be fixed
            // Add error
            $this->task->set_processlist($session['userid'], $options['sourceid'], "task__error_too_many_datapoints:0," . $processList);
            $this->parentProcessModel->runtime_error = 'too_many_datapoints';
            // Log error
            $this->log->error("Process: task__$origin_function -- Task: " . $options['sourceid'] . " -- User " . $session['userid'] . ", task can't fix dataset, too many datapoints");
        }
        else if ($error === 'wrong_engine') {
            // Add error
            $this->task->set_processlist($session['userid'], $options['sourceid'], "task__error_found_wrong_engine:0," . $processList);
            $this->parentProcessModel->runtime_error = 'wrong_engine';
            // Log error
            $this->log->error("Process: task__$origin_function -- Task: " . $options['sourceid'] . " -- User " . $session['userid'] . ", task can't fix dataset, feed engine must be PHPFINA for interpolation");
        }
        // End processlist execution
        $this->proc_goto = PHP_INT_MAX;
    }

}
