<?php




#
# Version 1.3 queries
#
$upgrade_queries["1.3.0"]   = [];
// add option to globally enforce uniqueness
$upgrade_queries["1.3.0"][] = "-- Add option to globally enforce uniqueness";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `settings` ADD `enforceUnique` TINYINT(1)  NULL  DEFAULT '1';";
$upgrade_queries["1.3.0"][] = "UPDATE `subnets` set `vrfId` = 0 WHERE `vrfId` IS NULL;";
// update languges
$upgrade_queries["1.3.0"][] = "-- Update languages";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'en_GB.UTF-8' WHERE `l_code` = 'en_GB.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'sl_SI.UTF-8' WHERE `l_code` = 'sl_SI.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'fr_FR.UTF-8' WHERE `l_code` = 'fr_FR.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'nl_NL.UTF-8' WHERE `l_code` = 'nl_NL.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'de_DE.UTF-8' WHERE `l_code` = 'de_DE.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'pt_BR.UTF-8' WHERE `l_code` = 'pt_BR.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'es_ES.UTF-8' WHERE `l_code` = 'es_ES.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'cs_CZ.UTF-8' WHERE `l_code` = 'cs_CZ.UTF8';";
$upgrade_queries["1.3.0"][] = "UPDATE `lang` SET `l_code` = 'en_US.UTF-8' WHERE `l_code` = 'en_US.UTF8';";
// Russian traslation
$upgrade_queries["1.3.0"][] = "-- Add russian and Chinese translations";
$upgrade_queries["1.3.0"][] = "INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Russian', 'ru_RU.UTF-8');";
$upgrade_queries["1.3.0"][] = "INSERT INTO `lang` (`l_code`, `l_name`) VALUES ('zh_CN.UTF-8', 'Chinese');";
// fix scanAgents typo
$upgrade_queries["1.3.0"][] = "-- Fix scanAgents typo";;
$upgrade_queries["1.3.0"][] = "update `scanAgents` set `name` = 'localhost' WHERE `id` = 1;";
// Add option to show custom field results as nested and show links default
$upgrade_queries["1.3.0"][] = "-- Add option to show custom field results as nested and show links default";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `api` ADD `app_nest_custom_fields` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `api` ADD `app_show_links` TINYINT(1)  NULL  DEFAULT '0';";
// Add index to ctype for changelog
$upgrade_queries["1.3.0"][] = "-- Add index to ctype for changelog";
$upgrade_queries["1.3.0"][] = "ALTER TABLE changelog ADD INDEX(ctype);";
// extend sections for devices
$upgrade_queries["1.3.0"][] = "-- Extend sections for devices";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `devices` CHANGE `sections` `sections` VARCHAR(1024)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
// hostname extend
$upgrade_queries["1.3.0"][] = "-- Extend hostname";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `devices` CHANGE `hostname` `hostname` VARCHAR(100)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
// decode mac addresses
$upgrade_queries["1.3.0"][] = "-- Decode MAC addresses switch";
$upgrade_queries["1.3.0"][] = "ALTER TABLE `settings` ADD `decodeMAC` TINYINT(1)  NULL  DEFAULT '1';";
$upgrade_queries["1.3.0"][] = "-- Version update";
$upgrade_queries["1.3.0"][] = "UPDATE `settings` set `version` = '1.3';";

#
# Version 1.31 queries
#
$upgrade_queries["1.31.0"]   = [];
// Circuits flag
$upgrade_queries["1.31.0"][] = "-- Enable circuits switch and circuits tables";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `settings` ADD `enableCircuits` TINYINT(1)  NULL  DEFAULT '1';";
// circuit providers */
$upgrade_queries["1.31.0"][] = "
CREATE TABLE `circuitProviders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `description` text,
  `contact` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// circuits table
$upgrade_queries["1.31.0"][] = "
CREATE TABLE `circuits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` varchar(128) DEFAULT NULL,
  `provider` int(11) unsigned NOT NULL,
  `type` enum('Default','Bandwidth') DEFAULT NULL,
  `capacity` varchar(128) DEFAULT NULL,
  `status` enum('Active','Inactive','Reserved') NOT NULL DEFAULT 'Active',
  `device1` int(11) unsigned DEFAULT NULL,
  `location1` int(11) unsigned DEFAULT NULL,
  `device2` int(11) unsigned DEFAULT NULL,
  `location2` int(11) unsigned DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cid` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// add circuit permissions for normal users
$upgrade_queries["1.31.0"][] = "ALTER TABLE `users` ADD `editCircuits` SET('Yes','No')  NULL  DEFAULT 'No';";
// Compact menu
$upgrade_queries["1.31.0"][] = "-- Compact menu";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `users` ADD `menuCompact` TINYINT  NULL  DEFAULT '1';";
// Add line for rack displayin and back side
$upgrade_queries["1.31.0"][] = "-- Add line for rack displayin and back side";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `racks` ADD `line` INT(11)  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `racks` ADD `front` INT(11)  NOT NULL  DEFAULT '0';";
// Add option for DNS resolving host in subnet
$upgrade_queries["1.31.0"][] = "-- Add option for DNS resolving host in subnet";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `subnets` ADD `resolveDNS` TINYINT(1)  NULL  DEFAULT '0';";
// Cahnge name for back side
$upgrade_queries["1.31.0"][] = "-- Change name for rack back side";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `racks` CHANGE `front` `hasBack` TINYINT(1)  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `racks` CHANGE `line` `row` INT(11)  NOT NULL  DEFAULT '1';";
// add permission propagation policy
$upgrade_queries["1.31.0"][] = "-- Add permission propagation policy";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `settings` ADD `permissionPropagate` TINYINT(1)  NULL  DEFAULT '1';";
// extend log details
$upgrade_queries["1.31.0"][] = "-- Extend log details";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `logs` CHANGE `details` `details` TEXT  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL;";
// snmpv3
$upgrade_queries["1.31.0"][] = "-- SNMP v3";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_sec_level` SET('none','noAuthNoPriv','authNoPriv','authPriv')  NULL  DEFAULT 'none';";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_auth_protocol` SET('none','MD5','SHA')  NULL  DEFAULT 'none';";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_auth_pass` VARCHAR(64)  NULL  DEFAULT NULL;";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_priv_protocol` SET('none','DES','AES')  NULL  DEFAULT 'none';";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_priv_pass` VARCHAR(64)  NULL  DEFAULT NULL;";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_ctx_name` VARCHAR(64)  NULL  DEFAULT NULL;";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD `snmp_v3_ctx_engine_id` VARCHAR(64)  NULL  DEFAULT NULL;";
// add indexes to locations
$upgrade_queries["1.31.0"][] = "-- Add indexes to locations";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `devices` ADD INDEX (`location`);";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `racks` ADD INDEX (`location`);";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `subnets` ADD INDEX (`location`);";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `ipaddresses` ADD INDEX (`location`);";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `circuits` ADD INDEX (`location1`);";
$upgrade_queries["1.31.0"][] = "ALTER TABLE `circuits` ADD INDEX (`location2`);";
$upgrade_queries["1.31.0"][] = "-- Version update";
$upgrade_queries["1.31.0"][] = "UPDATE `settings` set `version` = '1.31';";

#
# Version 1.32 queries
#
$upgrade_queries["1.32.0"]   = [];
// Required IP fields
$upgrade_queries["1.32.0"][] = "-- Required IP fields";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `settings` ADD `IPrequired` VARCHAR(128)  NULL  DEFAULT NULL;";
// Change dns_name to hostname
$upgrade_queries["1.32.0"][] = "-- Change dns_name to hostname";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `ipaddresses` CHANGE `dns_name` `hostname` VARCHAR(255)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT NULL;";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `requests` CHANGE `dns_name` `hostname` VARCHAR(255)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT NULL;";
// Subnet table indexes: has_slaves(), fetch_section_subnets(), subnet_familytree_*(), verify_subnet_overlapping(), verify_vrf_overlapping()...
$upgrade_queries["1.32.0"][] = "-- Add indexes to subnets";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `subnets` ADD INDEX (`masterSubnetId`);";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `subnets` ADD INDEX (`sectionId`);";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `subnets` ADD INDEX (`vrfId`);";
// bandwidth calculator widget
$upgrade_queries["1.32.0"][] = "-- Bandwidth calculator widget";
$upgrade_queries["1.32.0"][] = "INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Bandwidth calculator', 'Calculate bandwidth', 'bw_calculator', NULL, 'no', '6', 'no', 'yes');";
// add theme
$upgrade_queries["1.32.0"][] = "-- Add theme switch";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `settings` ADD `theme` VARCHAR(32)  NOT NULL  DEFAULT 'dark';";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `users` ADD `theme` VARCHAR(32)  NULL  DEFAULT '';";
// Allow SNMPv3 to be selected for devices
$upgrade_queries["1.32.0"][] = "-- Allow SNMPv3 to be selected for device";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `devices` CHANGE `snmp_version` `snmp_version` SET('0','1','2','3') DEFAULT '0';";
// Add database schema version field
$upgrade_queries["1.32.0"][] = "-- Add database schema version field";
$upgrade_queries["1.32.0"][] = "ALTER TABLE `settings` ADD `dbversion` INT(8)  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.32.0"][] = "-- Version update";
$upgrade_queries["1.32.0"][] = "UPDATE `settings` set `version` = '1.32';";