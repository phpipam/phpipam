<?php

#
# Subversion 1.5 queries
#

// fix for postcode
$upgrade_queries["1.5.26"][] = "ALTER TABLE `customers` CHANGE `postcode` `postcode` VARCHAR(32)  NULL  DEFAULT NULL;";

$upgrade_queries["1.5.26"][] = "-- Database version bump";
$upgrade_queries["1.5.26"][] = "UPDATE `settings` set `dbversion` = '26';";


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


// Subnet isPool
//
$upgrade_queries["1.5.28"][] = "ALTER TABLE `subnets` ADD `isPool` BOOL NOT NULL DEFAULT '0';";

$upgrade_queries["1.5.28"][] = "-- Database version bump";
$upgrade_queries["1.5.28"][] = "UPDATE `settings` set `dbversion` = '28';";


// Hide section subnet tree menus
//
$upgrade_queries["1.5.29"][] = "ALTER TABLE `sections` ADD `showSubnet` BOOL NOT NULL DEFAULT '1';";

$upgrade_queries["1.5.29"][] = "-- Database version bump";
$upgrade_queries["1.5.29"][] = "UPDATE `settings` set `dbversion` = '29';";


// Italian translation
//
$upgrade_queries["1.5.30"][] = "-- Add Italian translation";
$upgrade_queries["1.5.30"][] = "INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Italian', 'it_IT.UTF-8');";

$upgrade_queries["1.5.30"][] = "-- Database version bump";
$upgrade_queries["1.5.30"][] = "UPDATE `settings` set `dbversion` = '30';";

// L2Domain permissions
//
$upgrade_queries["1.5.31"][] = 'ALTER TABLE `users` CHANGE `module_permissions` `module_permissions` varchar(255) COLLATE utf8_bin DEFAULT \'{"vlan":"1","l2dom":"1","vrf":"1","pdns":"1","circuits":"1","racks":"1","nat":"1","pstn":"1","customers":"1","locations":"1","devices":"1"}\'';
$upgrade_queries["1.5.31"][] = "-- Clone users l2dom permissions from existing vlan permission level. MySQL5.7+";
$upgrade_queries["1.5.31"][] = "UPDATE users SET module_permissions = JSON_SET(module_permissions,'$.l2dom', JSON_EXTRACT(module_permissions,'$.vlan')); -- IGNORE_ON_FAILURE"; // MySQL 5.7+

$upgrade_queries["1.5.31"][] = "-- Database version bump";
$upgrade_queries["1.5.31"][] = "UPDATE `settings` set `dbversion` = '31';";

// Fix SET/ENUM usage in settings table
// Add 'none' scantype
//
$upgrade_queries["1.5.32"][] = "ALTER TABLE `settings` CHANGE `scanPingType` `scanPingType` ENUM('none','ping','pear','fping') NOT NULL DEFAULT 'ping';";
$upgrade_queries["1.5.32"][] = "ALTER TABLE `settings` CHANGE `prettyLinks` `prettyLinks` ENUM('Yes','No') NOT NULL DEFAULT 'No';";
$upgrade_queries["1.5.32"][] = "ALTER TABLE `settings` CHANGE `log` `log` ENUM('Database','syslog', 'both') NOT NULL DEFAULT 'Database';";
$upgrade_queries["1.5.32"][] = "ALTER TABLE `settings` CHANGE `2fa_provider` `2fa_provider` ENUM('none','Google_Authenticator') NULL DEFAULT 'none';";

$upgrade_queries["1.5.32"][] = "-- Database version bump";
$upgrade_queries["1.5.32"][] = "UPDATE `settings` set `dbversion` = '32';";



//
// Vaults
//
$upgrade_queries["1.5.33"][] = "ALTER TABLE `settings` CHANGE `scanPingType` `scanPingType` ENUM('none','ping','pear','fping') NOT NULL DEFAULT 'ping';";
// vaults table
$upgrade_queries["1.5.33"][] = "CREATE TABLE `vaults` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` enum('passwords','certificates') NOT NULL DEFAULT 'passwords',
  `description` text,
  `test` char(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// vault items
$upgrade_queries["1.5.33"][] = "CREATE TABLE `vaultItems` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vaultId` int(11) unsigned NOT NULL,
  `type` enum('password','certificate') NOT NULL DEFAULT 'password',
  `values` text,
  PRIMARY KEY (`id`),
  KEY `vaultId` (`vaultId`),
  CONSTRAINT `vaultItems_ibfk_1` FOREIGN KEY (`vaultId`) REFERENCES `vaults` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$upgrade_queries["1.5.33"][] = "-- Database version bump";
$upgrade_queries["1.5.33"][] = "UPDATE `settings` set `dbversion` = '33';";