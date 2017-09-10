<?php

$schema['tasks'] = array(
    'id' => array('type' => 'int(11)', 'Null' => 'NO', 'Key' => 'PRI', 'Extra' => 'auto_increment'),
    'userid' => array('type' => 'int(11)', 'Null' => 'NO'),
    'name' => array('type' => 'text'),
    'description' => array('type' => 'text', 'default' => ''),
    'run_on' => array('type' => 'int(10)', 'default' => '0'),
    'expiry_date' => array('type' => 'int(10)', 'default' => '0'),
    'frequency' => array('type' => 'int(10)', 'default' => '0'),
    'processList' => array('type' => 'text'),
    'enabled' => array('type' => 'tinyint', 'default' => 0)
);