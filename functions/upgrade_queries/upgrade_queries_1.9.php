<?php

#
# Version 1.9 queries
#
$upgrade_queries["1.9.46"]   = [];
$upgrade_queries["1.9.46"][] = "-- Version update";
$upgrade_queries["1.9.46"][] = "UPDATE `settings` set `version` = '1.9';";

$upgrade_queries["1.9.47"]   = [];
$upgrade_queries["1.9.47"][] = "ALTER TABLE `settings` DROP `2fa_length`;";
$upgrade_queries["1.9.47"][] = "ALTER TABLE `users` CHANGE `2fa` `2fa` TINYINT(1) NOT NULL DEFAULT '0';";
$upgrade_queries["1.9.47"][] = "-- Database version bump";
$upgrade_queries["1.9.47"][] = "UPDATE `settings` SET `dbversion` = '47';";
