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
    private $proc_goto;          // goto step in process list

    // Module required constructor, receives parent as reference

    public function __construct(&$parent) {
        $this->log = new EmonLogger(__FILE__);
        $this->mysqli = &$parent->mysqli;
        $this->feed = &$parent->feed;
        $this->proc_goto = &$parent->proc_goto;
    }

    // Module required process configuration, $list array index position is not used, function name is used instead
    public function process_list() {
        // 0=>Name | 1=>Arg type | 2=>function | 3=>No. of datafields if creating feed | 4=>Datatype | 5=>Group | 6=>Engines | 'desc'=>Description | 'requireredis'=>true | 'nochange'=>true  | 'helpurl'=>"http://..."
        $list[] = array(_("Get feed id"), ProcessArg::FEEDID, "get_feed_id", 0, DataType::UNDEFINED, "Get id", 'desc' => "<p>Passes the id of the selected feed to the next process </p>", 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("Get input id"), ProcessArg::INPUTID, "get_input_id", 0, DataType::UNDEFINED, "Get id", 'desc' => "<p>Passes the id of the selected input to the next process </p>", 'requireredis' => false, 'nochange' => false);
        $list[] = array(_("Feed last update > secs, go to next"), ProcessArg::VALUE, "feed_last_update_higher", 0, DataType::UNDEFINED, "Time", 'desc' => "<p>Using the value of previous process as a feed id, the execution of the processlist will carry on if the feed hasn't been updated for the specified amount of seconds. Otherwise it will stop. </p>", 'requireredis' => false, 'nochange' => true);
        $list[] = array(_("Input last update > secs, go to next"), ProcessArg::VALUE, "input_last_update_higher", 0, DataType::UNDEFINED, "Time", 'desc' => "<p>Using the value of previous process as an input id, the execution of the processlist will carry on if the input hasn't been updated for the specified amount of seconds. Otherwise it will stop. </p>", 'requireredis' => false, 'nochange' => true);
        return $list;
    }

    // Below are functions of this module processlist, same name must exist on process_list()

    public function get_feed_id($feedid, $time, $value) {
        return $feedid;
    }

    public function get_input_id($inputid, $time, $value) {
        return $inputid;
    }

    public function feed_last_update_higher($secs, $time, $value) { // $value should be feedid
        $last_update = $this->feed->get_timevalue($value);
        if ((time() - $last_update['time']) < $secs)
            $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

    public function input_last_update_higher($secs, $time, $value) {// $value should be inputid
        $result = $this->mysqli->query("SELECT time FROM input WHERE `id` = '$value'");
        $row = $result->fetch_array();
        $last_update = $row['time'];
        if ((time() - $last_update) < $secs)
            $this->proc_goto = PHP_INT_MAX;
        return $value;
    }

}
