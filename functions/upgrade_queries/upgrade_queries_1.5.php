<?php

#
# Subversion 1.5 queries
#

// fix for postcode
$upgrade_queries["1.5.26"][] = "ALTER TABLE `customers` CHANGE `postcode` `postcode` VARCHAR(32)  NULL  DEFAULT NULL;";
$upgrade_queries["1.5.26"][] = "-- Database version bump";
$upgrade_queries["1.5.26"][] = "UPDATE `settings` set `dbversion` = '26';";

$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` ADD `DNSforward` BOOL NOT NULL DEFAULT '0';";

// fix for query logic (null handling)
//
$upgrade_queries["1.5.27"][] = "UPDATE `ipaddresses` SET `is_gateway` = DEFAULT  WHERE `is_gateway` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `ipaddresses` SET `excludePing` = DEFAULT WHERE `excludePing` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `ipaddresses` SET `PTRignore` = DEFAULT   WHERE `PTRignore` IS NULL;";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `ipaddresses` CHANGE `is_gateway` `is_gateway` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `ipaddresses` CHANGE `excludePing` `excludePing` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `ipaddresses` CHANGE `PTRignore` `PTRignore` BOOL NOT NULL DEFAULT '0';";

$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `allowRequests` = DEFAULT  WHERE `allowRequests` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `showName` = DEFAULT       WHERE `showName` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `pingSubnet` = DEFAULT     WHERE `pingSubnet` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `discoverSubnet` = DEFAULT WHERE `discoverSubnet` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `resolveDNS` = DEFAULT     WHERE `resolveDNS` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `DNSrecursive` = DEFAULT   WHERE `DNSrecursive` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `DNSrecords` = DEFAULT     WHERE `DNSrecords` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `isFull` = DEFAULT         WHERE `isFull` IS NULL;";
$upgrade_queries["1.5.27"][] = "UPDATE `subnets` SET `isFolder` = DEFAULT       WHERE `isFolder` IS NULL;";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `allowRequests` `allowRequests` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `showName` `showName` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `pingSubnet` `pingSubnet` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `discoverSubnet` `discoverSubnet` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `resolveDNS` `resolveDNS` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `DNSrecursive` `DNSrecursive` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `DNSrecords` `DNSrecords` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `isFull` `isFull` BOOL NOT NULL DEFAULT '0';";
$upgrade_queries["1.5.27"][] = "ALTER TABLE `subnets` CHANGE `isFolder` `isFolder` BOOL NOT NULL DEFAULT '0';";

$upgrade_queries["1.5.27"][] = "-- Database version bump";
$upgrade_queries["1.5.27"][] = "UPDATE `settings` set `dbversion` = '27';";
