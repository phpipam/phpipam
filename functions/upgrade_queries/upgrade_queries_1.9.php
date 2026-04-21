<?php

#
# Version 1.9 queries
#
$upgrade_queries["1.9.46"]   = [];
$upgrade_queries["1.9.46"][] = "-- Version update";
$upgrade_queries["1.9.46"][] = "UPDATE `settings` set `version` = '1.9';";
