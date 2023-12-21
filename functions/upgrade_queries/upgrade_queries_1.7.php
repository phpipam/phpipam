<?php

#
# Version 1.71 queries
#
$upgrade_queries["1.7.41"]   = [];
$upgrade_queries["1.7.41"][] = "-- Version update";
$upgrade_queries["1.7.41"][] = "UPDATE `settings` set `version` = '1.7';";
$upgrade_queries["1.7.41"][] = "-- Database version bump";
$upgrade_queries["1.7.41"][] = "UPDATE `settings` set `dbversion` = '41';";