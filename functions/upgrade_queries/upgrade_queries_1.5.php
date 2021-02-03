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
$upgrade_queries["1.5.31"][] = 'ALTER TABLE `users` CHANGE `module_permissions` `module_permissions` varchar(255) COLLATE utf8_bin DEFAULT \'{"vlan":"1","l2dom":"1","vrf":"1","pdns":"1","circuits":"1","racks":"1","nat":"1","pstn":"1","customers":"1","locations":"1","devices":"1"}\';';
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

// Fix SET/ENUM usage in usersAuthMethod
// Allow for longer json params (e.g. certificates in SAML2)
//
$upgrade_queries["1.5.33"][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` ENUM('local','http','AD','LDAP','NetIQ','Radius','SAML2') NOT NULL DEFAULT 'local';";
$upgrade_queries["1.5.33"][] = "ALTER TABLE `usersAuthMethod` CHANGE `params` `params` text DEFAULT NULL;";
$upgrade_queries["1.5.33"][] = "ALTER TABLE `usersAuthMethod` CHANGE `protected` `protected` ENUM('Yes','No') NOT NULL DEFAULT 'Yes';";
if(defined('MAP_SAML_USER') && defined('SAML_USERNAME') && MAP_SAML_USER!=false && strlen(SAML_USERNAME)>0) {
    $upgrade_queries["1.5.33"][] = "UPDATE `usersAuthMethod` SET `params` = JSON_SET(`params`,'$.MappedUser','".SAML_USERNAME."') WHERE `type`='SAML2'; -- IGNORE_ON_FAILURE"; // MySQL 5.7+
}

$upgrade_queries["1.5.33"][] = "-- Database version bump";
$upgrade_queries["1.5.33"][] = "UPDATE `settings` set `dbversion` = '33';";

// Set email to 254 characters as per RFC 2821, increase email passwords to 128
// Fix SET/ENUM usage in updated tables
//
$upgrade_queries["1.5.34"][] = "ALTER TABLE `customers` CHANGE `contact_mail` `contact_mail` varchar(254) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settings` CHANGE `siteAdminMail` `siteAdminMail` varchar(254) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `mtype` `mtype` ENUM('localhost','smtp') NOT NULL DEFAULT 'localhost';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `msecure` `msecure` ENUM('none','ssl','tls')  NOT NULL  DEFAULT 'none';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `mauth` `mauth` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `muser` `muser` varchar(254) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `mpass` `mpass` varchar(128) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `mAdminName` `mAdminName` varchar(128) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `settingsMail` CHANGE `mAdminMail` `mAdminMail` varchar(254) DEFAULT NULL;";
$upgrade_queries["1.5.34"][] = "UPDATE `users` SET `mailNotify`='No' WHERE `mailNotify` IS NULL;";
$upgrade_queries["1.5.34"][] = "UPDATE `users` SET `mailChangelog`='No' WHERE `mailChangelog` IS NULL;";
$upgrade_queries["1.5.34"][] = "UPDATE `users` SET `menuType`='Dynamic' WHERE `menuType` IS NULL;";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `disabled` `disabled` ENUM('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `mailNotify` `mailNotify` ENUM('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `mailChangelog` `mailChangelog` ENUM('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `passChange` `passChange` ENUM('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `compressOverride` `compressOverride` ENUM('default','Uncompress') NOT NULL DEFAULT 'default';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `menuType` `menuType` ENUM('Static','Dynamic')  NOT NULL  DEFAULT 'Dynamic';";
$upgrade_queries["1.5.34"][] = "ALTER TABLE `users` CHANGE `email` `email` varchar(254) CHARACTER SET utf8 DEFAULT NULL;";

$upgrade_queries["1.5.34"][] = "-- Database version bump";
$upgrade_queries["1.5.34"][] = "UPDATE `settings` set `dbversion` = '34';";

// Custom fields on IP request forms (#2956);
//
$upgrade_queries["1.5.35"][] = "ALTER TABLE `requests` ADD `mac` varchar(20) DEFAULT NULL;";

$upgrade_queries["1.5.35"][] = "-- Database version bump";
$upgrade_queries["1.5.35"][] = "UPDATE `settings` set `dbversion` = '35';";


//
// Vaults
//
$upgrade_queries["1.5.36"][] = "ALTER TABLE `settings` ADD `enableVaults` TINYINT(1)  NOT NULL  DEFAULT '1';";
// vaults table
$upgrade_queries["1.5.36"][] = "CREATE TABLE `vaults` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` enum('passwords','certificates') NOT NULL DEFAULT 'passwords',
  `description` text,
  `test` char(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// vault items
$upgrade_queries["1.5.36"][] = "CREATE TABLE `vaultItems` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vaultId` int(11) unsigned NOT NULL,
  `type` enum('password','certificate') NOT NULL DEFAULT 'password',
  `type_certificate` enum('public','pkcs12','certificate','website') NOT NULL DEFAULT 'public',
  `values` text,
  PRIMARY KEY (`id`),
  KEY `vaultId` (`vaultId`),
  CONSTRAINT `vaultItems_ibfk_1` FOREIGN KEY (`vaultId`) REFERENCES `vaults` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$upgrade_queries["1.5.36"][] = "-- Database version bump";
$upgrade_queries["1.5.36"][] = "UPDATE `settings` set `dbversion` = '36';";

