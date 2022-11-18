<?php

#
# Version 1.6 queries
#
$upgrade_queries["1.6.39"]   = [];
$upgrade_queries["1.6.39"][] = "-- Version update";
$upgrade_queries["1.6.39"][] = "UPDATE `settings` set `version` = '1.6';";