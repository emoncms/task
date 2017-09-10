<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/rules/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Tasks"), 'path'=>"task" ,'icon'=>'icon-list', 'session'=>"write", 'order' => 1500 );