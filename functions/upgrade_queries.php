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


// HTTP headers auth method
$upgrade_queries["1.4.22"][] = "-- HTTP headers auth method";
$upgrade_queries["1.4.22"][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http','headers')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';";
$upgrade_queries["1.4.22"][] = "INSERT INTO `usersAuthMethod` (`type`, `params`, `protected`, `description`) VALUES ('headers', NULL, 'Yes', 'External HTTP header authentication');";


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