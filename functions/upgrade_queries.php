<?php

#
#
# Upgrade queries for versions
#
# Add for each major version and dbversion
#
#


# initial array
$upgrade_queries = [];


# include all upgrade queries
include('upgrade_queries/upgrade_queries_1.2.php');
include('upgrade_queries/upgrade_queries_1.3.php');
include('upgrade_queries/upgrade_queries_1.4.php');
include('upgrade_queries/upgrade_queries_1.5.php');

#
# Version 1.42.25 queries
#
$upgrade_queries["1.42.25"]   = [];
$upgrade_queries["1.42.25"][] = "-- Version update";
$upgrade_queries["1.42.25"][] = "UPDATE `settings` set `version` = '1.42';";

// output if required
if(!defined('VERSION') && php_sapi_name()=="cli") {
  // version check
  if (!isset($argv[1])) { die("Please provide version\n"); }
  // Output
  foreach ($upgrade_queries as $version=>$queries) {
    if ($version > $argv[1]) {
      print "\n\n"."/* VERSION $version */"."\n";
      foreach ($queries as $q) {
        print trim($q)."\n";
      }
    }
  }
}
