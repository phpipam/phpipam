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
require __DIR__ . '/upgrade_queries/upgrade_queries_1.2.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.3.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.4.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.5.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.6.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.7.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.8.php';
require __DIR__ . '/upgrade_queries/upgrade_queries_1.9.php';

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