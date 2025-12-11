<?php

#
# Version 1.8 queries
#
$upgrade_queries["1.8.43"]   = [];
$upgrade_queries["1.8.43"][] = "-- Version update";
$upgrade_queries["1.8.43"][] = "UPDATE `settings` set `version` = '1.8';";

$upgrade_queries["1.8.44"]   = [];
$upgrade_queries["1.8.44"][] = "ALTER TABLE `lang` CHANGE `l_name` `l_name` VARCHAR(64) NULL DEFAULT NULL;";
$upgrade_queries["1.8.44"][] = "-- Database version bump";
$upgrade_queries["1.8.44"][] = "UPDATE `settings` SET `dbversion` = '44';";
