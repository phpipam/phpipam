<?php

//
// Version 2.0 queries
//
$upgrade_queries['2.0.48'] = [];
$upgrade_queries['2.0.48'][] = '-- Version update';
$upgrade_queries['2.0.48'][] = "UPDATE `settings` set `version` = '2.0';";
