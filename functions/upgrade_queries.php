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


#
# Version 1.2 queries
#
$upgrade_queries["1.2.0"]   = [];
$upgrade_queries["1.2.0"][] = "-- Version update";
$upgrade_queries["1.2.0"][] = "UPDATE `settings` set `version` = '1.2';";
$upgrade_queries["1.2.0"][] = "-- add subnetView Setting";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `settings` ADD `subnetView` TINYINT  NOT NULL  DEFAULT '0'";
$upgrade_queries["1.2.0"][] = "-- add 'user' to app_security set";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `api` CHANGE `app_security` `app_security` SET('crypt','ssl','user','none')  NOT NULL  DEFAULT 'ssl';";
$upgrade_queries["1.2.0"][] = "-- add english_US language";
$upgrade_queries["1.2.0"][] = "INSERT INTO `lang` (`l_id`, `l_code`, `l_name`) VALUES (NULL, 'en_US', 'English (US)');";
$upgrade_queries["1.2.0"][] = "-- update the firewallZones table to suit the new layout";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `firewallZones` DROP COLUMN `vlanId`, DROP COLUMN `stacked`;";
$upgrade_queries["1.2.0"][] = "-- add a new table to store subnetId and zoneId";
$upgrade_queries["1.2.0"][] = "
CREATE TABLE `firewallZoneSubnet` (
  `zoneId` INT NOT NULL,
  `subnetId` INT(11) NOT NULL,
  INDEX `fk_zoneId_idx` (`zoneId` ASC),
  INDEX `fk_subnetId_idx` (`subnetId` ASC),
  CONSTRAINT `fk_zoneId`
    FOREIGN KEY (`zoneId`)
    REFERENCES `firewallZones` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subnetId`
    FOREIGN KEY (`subnetId`)
    REFERENCES `subnets` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION);";
$upgrade_queries["1.2.0"][] = "-- copy old subnet IDs from firewallZones table into firewallZoneSubnet";
$upgrade_queries["1.2.0"][] = "INSERT INTO `firewallZoneSubnet` (zoneId,subnetId) SELECT id AS zoneId,subnetId from `firewallZones`;";
$upgrade_queries["1.2.0"][] = "-- remove the field subnetId from firewallZones, it's not longer needed ";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `firewallZones` DROP COLUMN `subnetId`;";
$upgrade_queries["1.2.0"][] = "-- add fk constrain and index to firewallZoneMappings to automatically remove a mapping if a device has been deleted";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `firewallZoneMapping` ADD INDEX `devId_idx` (`deviceId` ASC);";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `firewallZoneMapping` ADD CONSTRAINT `devId` FOREIGN KEY (`deviceId`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;";
$upgrade_queries["1.2.0"][] = "-- add firewallAddresObject field to the ipaddresses table to store fw addr. obj. names permanently";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `ipaddresses` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL;";
$upgrade_queries["1.2.0"][] = "-- activate the firewallAddressObject IP field filter on default";
$upgrade_queries["1.2.0"][] = "UPDATE `settings` SET IPfilter = CONCAT(IPfilter,';firewallAddressObject');";
$upgrade_queries["1.2.0"][] = "-- add a column for subnet firewall address objects";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `subnets` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL;";
$upgrade_queries["1.2.0"][] = "-- add http and apache auth method";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';";
$upgrade_queries["1.2.0"][] = "INSERT INTO `usersAuthMethod` (`type`, `params`, `protected`, `description`) VALUES ('http', NULL, 'Yes', 'Apache authentication');";
$upgrade_queries["1.2.0"][] = "-- allow powerdns record management for user";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `users` ADD `pdns` SET('Yes','No')  NULL  DEFAULT 'No';";
$upgrade_queries["1.2.0"][] = "-- add Ip request widget";
$upgrade_queries["1.2.0"][] = "INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('IP Request', 'IP Request widget', 'iprequest', NULL, 'no', '6', 'no', 'yes');";
$upgrade_queries["1.2.0"][] = "-- change mask size";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `subnets` CHANGE `mask` `mask` VARCHAR(3)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
$upgrade_queries["1.2.0"][] = "-- add section to vrf";
$upgrade_queries["1.2.0"][] = "ALTER TABLE `vrf` ADD `sections` VARCHAR(128)  NULL  DEFAULT NULL;";


#
# Version 1.21 queries
#
$upgrade_queries["1.21.0"]   = [];
$upgrade_queries["1.21.0"][] = "-- Version update";
$upgrade_queries["1.21.0"][] = "UPDATE `settings` set `version` = '1.21';";
// New modules
$upgrade_queries["1.21.0"][] = "-- Add new modules switch";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `enableMulticast` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `enableNAT` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `enableSNMP` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `enableThreshold` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `enableRACK` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `settings` ADD `link_field` VARCHAR(32)  NULL  DEFAULT '0';";
// add nat link
$upgrade_queries["1.21.0"][] = "-- Add NAT link";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `ipaddresses` ADD `NAT` VARCHAR(64)  NULL  DEFAULT NULL;";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `subnets` ADD `NAT` VARCHAR(64)  NULL  DEFAULT NULL;";
// NAT table
$upgrade_queries["1.21.0"][] = "-- NAT table";
$upgrade_queries["1.21.0"][] = "
CREATE TABLE `nat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `type` set('source','static','destination') DEFAULT 'source',
  `src` text,
  `dst` text,
  `port` int(5) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;";
// snmp to device
$upgrade_queries["1.21.0"][] = "-- SNMP to devices";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `snmp_community` VARCHAR(100)  NULL  DEFAULT NULL;";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `snmp_version` SET('0','1','2')  NULL  DEFAULT '0';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `snmp_port` mediumint(5) unsigned DEFAULT '161';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `snmp_timeout` mediumint(5) unsigned DEFAULT '1000000';";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `snmp_queries` VARCHAR(128)  NULL  DEFAULT NULL;";
// racks
$upgrade_queries["1.21.0"][] = "-- Racks table";
$upgrade_queries["1.21.0"][] = "
CREATE TABLE `racks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `size` int(2) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// rack info to devices
$upgrade_queries["1.21.0"][] = "-- Add rack info to devices";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `rack` int(11) unsigned DEFAULT null;";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `rack_start` int(11) unsigned DEFAULT null;";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `devices` ADD `rack_size` int(11) unsigned DEFAULT null;";
// add threshold module to subnets and add widget
$upgrade_queries["1.21.0"][] = "-- Add threshold module to subnets ad widgets";
$upgrade_queries["1.21.0"][] = "ALTER TABLE `subnets` ADD `threshold` int(3)  NULL  DEFAULT 0;";
$upgrade_queries["1.21.0"][] = "INSERT INTO `widgets` ( `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('Threshold', 'Shows threshold usage for top 5 subnets', 'threshold', NULL, 'yes', '6', 'no', 'yes');";
$upgrade_queries["1.21.0"][] = "-- Inactive hosts widget";
$upgrade_queries["1.21.0"][] = "INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Inactive hosts', 'Shows list of inactive hosts for defined period', 'inactive-hosts', 86400, 'yes', '6', 'yes', 'yes');";


#
# Version 1.22 queries
#
$upgrade_queries["1.22.0"]   = [];
$upgrade_queries["1.22.0"][] = "-- Version update";
$upgrade_queries["1.22.0"][] = "UPDATE `settings` set `version` = '1.22';";
// drop unused snmp table
$upgrade_queries["1.22.0"][] = "-- drop unused snmp table";
$upgrade_queries["1.22.0"][] = "DROP TABLE IF EXISTS `snmp`;";
// add DHCP to settings
$upgrade_queries["1.22.0"][] = "-- add DHCP to settings";
$upgrade_queries["1.22.0"][] = "ALTER TABLE `settings` ADD `enableDHCP` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.22.0"][] = "ALTER TABLE `settings` ADD `DHCP` VARCHAR(256) NULL default '{\"type\":\"kea\",\"settings\":{\"file\":\"\/etc\/kea\/kea.conf\"}}';";
// permit normal users to manage VLANs
$upgrade_queries["1.22.0"][] = "-- Permit non-admin users to manage VLANs";
$upgrade_queries["1.22.0"][] = "ALTER TABLE `users` ADD `editVlan` SET('Yes','No')  NULL  DEFAULT 'No';";
// remove permitUserVlanCreate - not needed
$upgrade_queries["1.22.0"][] = "-- remove permitUserVlanCreate - not needed";
$upgrade_queries["1.22.0"][] = "ALTER TABLE `settings` DROP `permitUserVlanCreate`;";
// menu type
$upgrade_queries["1.22.0"][] = "-- add menu type";
$upgrade_queries["1.22.0"][] = "ALTER TABLE `users` ADD `menuType` SET('Static','Dynamic')  NULL  DEFAULT 'Dynamic';";


#
# Version 1.23 queries
#
$upgrade_queries["1.23.0"]   = [];
$upgrade_queries["1.23.0"][] = "-- Version update";
$upgrade_queries["1.23.0"][] = "UPDATE `settings` set `version` = '1.23';";
// change default datetime
$upgrade_queries["1.23.0"][] = "-- Change default datetime";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `ipaddresses` CHANGE `lastSeen` `lastSeen` DATETIME  NULL  DEFAULT '1970-01-01 00:00:01';";
// add linked subnet field
$upgrade_queries["1.23.0"][] = "-- Add linked subnet field";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `subnets` ADD `linked_subnet` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
// add device to table
$upgrade_queries["1.23.0"][] = "-- Add device to table";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `nat` ADD `device` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
// drop NAT fields
$upgrade_queries["1.23.0"][] = "-- drop NAT fields";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `subnets` DROP `NAT`;";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `ipaddresses` DROP `NAT`;";
// extend username field to 64 chars
$upgrade_queries["1.23.0"][] = "-- Extend username field to 64 chars";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `logs` CHANGE `username` `username` VARCHAR(64)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `users` CHANGE `username` `username` VARCHAR(64)  CHARACTER SET utf8  NOT NULL  DEFAULT '';";
//locations
$upgrade_queries["1.23.0"][] = "-- Locations";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `settings` ADD `enableLocations` TINYINT(1)  NULL  DEFAULT '1';";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `devices` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `racks` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `subnets` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
$upgrade_queries["1.23.0"][] = "
CREATE TABLE `locations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` text,
  `lat` varchar(12) DEFAULT NULL,
  `long` varchar(12) DEFAULT NULL,
  `address` VARCHAR(128)  NULL  DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// nat changes
$upgrade_queries["1.23.0"][] = "-- NAT changes";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `nat` CHANGE `port` `src_port` INT(5)  NULL  DEFAULT NULL;";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `nat` ADD `dst_port` INT(5)  NULL  DEFAULT NULL;)";


#
# Version 1.24 queries
#
$upgrade_queries["1.24.0"]   = [];
$upgrade_queries["1.24.0"][] = "-- Version update";
$upgrade_queries["1.24.0"][] = "UPDATE `settings` set `version` = '1.24';";
// PSTN
$upgrade_queries["1.24.0"][] = "-- PSTN module switch and tables";
$upgrade_queries["1.24.0"][] = "ALTER TABLE `settings` ADD `enablePSTN` TINYINT(1)  NULL  DEFAULT '1';";
// pstnPrefixes
$upgrade_queries["1.24.0"][] = "
CREATE TABLE `pstnPrefixes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `start` varchar(32) DEFAULT NULL,
  `stop` varchar(32) DEFAULT NULL,
  `master` int(11) DEFAULT '0',
  `deviceId` int(11) unsigned DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// pstnNumbers
$upgrade_queries["1.24.0"][] = "
CREATE TABLE `pstnNumbers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` int(11) unsigned DEFAULT NULL,
  `number` varchar(32) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `owner` varchar(128) DEFAULT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `deviceId` int(11) unsigned DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// permissions
$upgrade_queries["1.24.0"][] = "-- User permissions for pstn";
$upgrade_queries["1.24.0"][] = "ALTER TABLE `users` ADD `pstn` INT(1)  NULL  DEFAULT '1';";


#
# Version 1.25 queries
#
$upgrade_queries["1.25.0"]   = [];
$upgrade_queries["1.25.0"][] = "-- Version update";
$upgrade_queries["1.25.0"][] = "UPDATE `settings` set `version` = '1.25';";
// update languges
$upgrade_queries["1.25.0"][] = "-- Update languages";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'en_GB.UTF8' WHERE `l_code` = 'en';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'sl_SI.UTF8' WHERE `l_code` = 'sl_SI';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'fr_FR.UTF8' WHERE `l_code` = 'fr_FR';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'nl_NL.UTF8' WHERE `l_code` = 'nl_NL';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'de_DE.UTF8' WHERE `l_code` = 'de_DE';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'pt_BR.UTF8' WHERE `l_code` = 'pt_BR';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'es_ES.UTF8' WHERE `l_code` = 'es_ES';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'cs_CZ.UTF8' WHERE `l_code` = 'cs_CZ';";
$upgrade_queries["1.25.0"][] = "UPDATE `lang` SET `l_code` = 'en_US.UTF8' WHERE `l_code` = 'en_US';";
// location to addresses
$upgrade_queries["1.25.0"][] = "-- Add location to addresses abd kication widget";
$upgrade_queries["1.25.0"][] = "ALTER TABLE `ipaddresses` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;";
// location widget
$upgrade_queries["1.25.0"][] = "INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Locations', 'Shows map of locations', 'locations', NULL, 'yes', '6', 'no', 'yes');";
// remove print limit
$upgrade_queries["1.25.0"][] = "-- Remove unneeded printlimit";
$upgrade_queries["1.25.0"][] = "ALTER TABLE `users` DROP `printLimit`;";


#
# Version 1.26 queries
#
$upgrade_queries["1.26.0"]   = [];
$upgrade_queries["1.26.0"][] = "-- Version update";
$upgrade_queries["1.26.0"][] = "UPDATE `settings` set `version` = '1.26';";
// add http saml2 method
$upgrade_queries["1.26.0"][] = "-- http saml2 method";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http','SAML2')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';";
// add transaction locking
$upgrade_queries["1.26.0"][] = "-- Add transaction locking";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `api` ADD `app_lock` INT(1)  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `api` ADD `app_lock_wait` INT(4)  NOT NULL  DEFAULT '30';";


#
# Version 1.27 queries
#
$upgrade_queries["1.27.0"]   = [];
$upgrade_queries["1.27.0"][] = "-- Version update";
$upgrade_queries["1.27.0"][] = "UPDATE `settings` set `version` = '1.27';";
// add show supernet only
$upgrade_queries["1.27.0"][] = "-- Add show supernet only";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `sections` ADD `showSupernetOnly` INT(1)  NULL  DEFAULT '0';";
// add scan and discovery check time to database
$upgrade_queries["1.27.0"][] = "-- Scan and discovery date foir subnets";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `subnets` ADD `lastScan` TIMESTAMP  NULL;";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `subnets` ADD `lastDiscovery` TIMESTAMP  NULL;";


#
# Version 1.28 queries
#
$upgrade_queries["1.28.0"]   = [];
$upgrade_queries["1.28.0"][] = "-- Version update";
$upgrade_queries["1.28.0"][] = "UPDATE `settings` set `version` = '1.28';";
// Extend username to 255 chars for LDAP logins
$upgrade_queries["1.28.0"][] = "-- Extend username to 255 chars for LDAP logins";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `users` CHANGE `username` `username` VARCHAR(255)  CHARACTER SET utf8  NOT NULL  DEFAULT '';";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `logs` CHANGE `username` `username` VARCHAR(255)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
// expand hostname valude in IP requests to match ipaddresses table
$upgrade_queries["1.28.0"][] = "-- Expand hostname valude in IP requests to match ipaddresses table";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `requests` CHANGE `dns_name` `dns_name` VARCHAR(100)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `requests` CHANGE `description` `description` VARCHAR(64)  CHARACTER SET utf8  NULL  DEFAULT NULL;";
// update Tags when state change occurs
$upgrade_queries["1.28.0"][] = "-- Update Tags when state change occurs";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `settings` ADD `updateTags` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.28.0"][] = "ALTER TABLE `ipTags` ADD `updateTag` TINYINT(1)  NULL  DEFAULT '0';";
$upgrade_queries["1.28.0"][] = "UPDATE `ipTags` set `updateTag`=1 where `id`=1;";
$upgrade_queries["1.28.0"][] = "UPDATE `ipTags` set `updateTag`=1 where `id`=2;";
$upgrade_queries["1.28.0"][] = "UPDATE `ipTags` set `updateTag`=1 where `id`=3;";
$upgrade_queries["1.28.0"][] = "UPDATE `ipTags` set `updateTag`=1 where `id`=4;";


#
# Version 1.29 queries
#
$upgrade_queries["1.29.0"]   = [];
$upgrade_queries["1.29.0"][] = "-- Version update";
$upgrade_queries["1.29.0"][] = "UPDATE `settings` set `version` = '1.29';";
// Add maintaneanceMode identifier
$upgrade_queries["1.29.0"][] = "-- Add maintaneanceMode";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` ADD `maintaneanceMode` TINYINT(1)  NULL  DEFAULT '0';";
// extend pingStatus intervals
$upgrade_queries["1.29.0"][] = "-- Extend pingStatus intervals";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` CHANGE `pingStatus` `pingStatus` VARCHAR(32)  CHARACTER SET utf8  NOT NULL  DEFAULT '1800;3600';";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` CHANGE `hiddenCustomFields` `hiddenCustomFields` TEXT  CHARACTER SET utf8  NULL;";


#
# Version 1.3 queries
#
$upgrade_queries["1.3.0"]   = [];
$upgrade_queries["1.3.0"][] = "-- Version update";
$upgrade_queries["1.3.0"][] = "UPDATE `settings` set `version` = '1.3';";
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


#
# Version 1.31 queries
#
$upgrade_queries["1.31.0"]   = [];
$upgrade_queries["1.31.0"][] = "-- Version update";
$upgrade_queries["1.31.0"][] = "UPDATE `settings` set `version` = '1.31';";
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


#
# Version 1.32 queries
#
$upgrade_queries["1.32.0"]   = [];
$upgrade_queries["1.32.0"][] = "-- Version update";
$upgrade_queries["1.32.0"][] = "UPDATE `settings` set `version` = '1.32';";
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


#
# Version 1.4 queries
#
$upgrade_queries["1.4.0"]   = [];
$upgrade_queries["1.4.0"][] = "-- Version update";
$upgrade_queries["1.4.0"][] = "UPDATE `settings` set `version` = '1.4';";


#
# Subversion 1.4.1 queries
#
$upgrade_queries["1.4.1"]   = [];
$upgrade_queries["1.4.1"][] = "-- Database version bump";
$upgrade_queries["1.4.1"][] = "UPDATE `settings` set `dbversion` = '1';";
// Add password policy
$upgrade_queries["1.4.1"][] = "-- Add password policy";
$upgrade_queries["1.4.1"][] = "ALTER TABLE `settings` ADD `passwordPolicy` VARCHAR(1024)  NULL  DEFAULT '{\"minLength\":8,\"maxLength\":0,\"minNumbers\":0,\"minLetters\":0,\"minLowerCase\":0,\"minUpperCase\":0,\"minSymbols\":0,\"maxSymbols\":0,\"allowedSymbols\":\"#,_,-,!,[,],=,~\"}';";


#
# Subversion 1.4.2 queries
#
//$upgrade_queries["1.4.2"]   = [];
$upgrade_queries["1.4.2"][] = "-- Database version bump";
$upgrade_queries["1.4.2"][] = "UPDATE `settings` set `dbversion` = '2';";
// Create circuit type table and convert existing circuits
$upgrade_queries["1.4.2"][] = "-- Create circuit type table and convert existing circuits";
$upgrade_queries["1.4.2"][] = "
CREATE TABLE `circuitTypes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ctname` varchar(64) NOT NULL,
  `ctcolor` varchar(7) DEFAULT '#000000',
  `ctpattern` enum('Solid','Dotted') DEFAULT 'Solid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// insert default values
$upgrade_queries["1.4.2"][] = "INSERT INTO `circuitTypes` (`ctname`) VALUES ('Default');";
// Create table for logical circuit mapping.
$upgrade_queries["1.4.2"][] = "-- Create table for logical circuit mapping.";
$upgrade_queries["1.4.2"][] = "
CREATE TABLE `circuitsLogicalMapping` (
	  `logicalCircuit_id` int(11) unsigned NOT NULL,
	  `circuit_id` int(11) unsigned NOT NULL,
	  `order` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// Create table to hold logical circuit information
$upgrade_queries["1.4.2"][] = "-- Create table to hold logical circuit information";
$upgrade_queries["1.4.2"][] = "
CREATE TABLE `circuitsLogical` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `logical_cid` varchar(128) NOT NULL,
	  `purpose` varchar(64) DEFAULT NULL,
	  `comments` text,
	  `member_count` int(4) unsigned NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `circuitsLogical_UN` (`logical_cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
//  migration procedures for circuits.
$upgrade_queries["1.4.2"][] = "-- Migration procedure for circuits";
$upgrade_queries["1.4.2"][] = "
DELIMITER $$
CREATE PROCEDURE `convertTypesToTable`()
BEGIN
	DECLARE v_type varchar(100);
	DECLARE done INT DEFAULT 0;
	DECLARE curs CURSOR FOR select distinct `type` from circuits;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN curs;
	insert_type: LOOP
		FETCH curs INTO  v_type;
		IF done = 1 THEN
			LEAVE insert_type;
		END IF;
		IF v_type != 'Default' THEN
			INSERT INTO `circuitTypes` (`ctname`) VALUES (v_type);
		END IF;
	END LOOP insert_type;
	CLOSE curs;
END $$
DELIMITER ;";
//  migration procedures for circuits.
$upgrade_queries["1.4.2"][] = "
DELIMITER $$
CREATE PROCEDURE `updateEnumsToIds`()
BEGIN
	DECLARE v_type varchar(100);
	DECLARE v_id integer;
	DECLARE done INT DEFAULT 0;
	DECLARE curs CURSOR FOR select CAST(id as CHAR(100)) as id,`ctname` from circuitTypes;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN curs;
	update_type: LOOP
		FETCH curs INTO  v_id,v_type;
		IF done = 1 THEN
			LEAVE update_type;
		END IF;
		if v_type = 'Default' THEN
        		UPDATE `circuits` SET `type` = 1 WHERE `type` = v_type;
		ELSE
        		UPDATE `circuits` SET `type` = v_id WHERE `type` = v_type;
		END IF;
        END LOOP update_type;
	CLOSE curs;
END $$
DELIMITER ;";

// Take distinct types and migrate to its own table with default values
$upgrade_queries["1.4.2"][] = "-- Run procedures";
$upgrade_queries["1.4.2"][] = "CALL convertTypesToTable();";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` MODIFY COLUMN `type` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
// alter circuit table to change enum value to ID in new table
$upgrade_queries["1.4.2"][] = "CALL updateEnumsToIds();";
// drop temp procedures
$upgrade_queries["1.4.2"][] = "-- Drop temp procedures";
$upgrade_queries["1.4.2"][] = "DROP PROCEDURE convertTypesToTable;";
$upgrade_queries["1.4.2"][] = "DROP PROCEDURE updateEnumsToIds;";
// Alter circuit table. Adds parent circuit and differentiation for future update
$upgrade_queries["1.4.2"][] = "-- Alter circuit table. Adds parent circuit and differentiation for future update";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` ADD parent INT UNSIGNED DEFAULT 0 NOT NULL ;";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` ADD differentiator varchar(100) DEFAULT NULL NULL ;";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` DROP KEY cid ;";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` ADD CONSTRAINT circuits_diff_UN UNIQUE KEY (cid,differentiator) ;";
$upgrade_queries["1.4.2"][] = "ALTER TABLE `circuits` MODIFY `type` INT UNSIGNED DEFAULT 1 NOT NULL ;";


#
# Subversion 1.4.3 queries
#
$upgrade_queries["1.4.3"][] = "-- Database version bump";
$upgrade_queries["1.4.3"][] = "UPDATE `settings` set `dbversion` = '3';";
$upgrade_queries["1.4.3"][] = "-- Create php_sessions table";
$upgrade_queries["1.4.3"][] = "CREATE TABLE `php_sessions` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `access` int(10) unsigned DEFAULT NULL,
  `data` text NOT NULL,
  `remote_ip` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


#
# Subversion 1.4.4 queries
#
$upgrade_queries["1.4.4"][] = "-- Database version bump";
$upgrade_queries["1.4.4"][] = "UPDATE `settings` set `dbversion` = '4';";
$upgrade_queries["1.4.4"][] = "-- Add 2fa to settings";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_provider` SET('none','Google_Authenticator')  NULL  DEFAULT 'none';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_name` VARCHAR(32)  NULL  DEFAULT 'phpipam';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_length` INT(2)  NULL  DEFAULT '16';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_userchange` BOOL  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.4"][] = "-- Add 2fa to users";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `users` ADD `2fa` BOOL  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `users` ADD `2fa_secret` VARCHAR(32)  NULL  DEFAULT NULL;";


#
# Subversion 1.4.5 queries
#
$upgrade_queries["1.4.5"][] = "-- Database version bump";
$upgrade_queries["1.4.5"][] = "UPDATE `settings` set `dbversion` = '5';";
// cusotmers module
$upgrade_queries["1.4.5"][] = "-- Add customers module switch";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `settings` ADD `enableCustomers` TINYINT(1)  NULL  DEFAULT '0';";
// customers table
$upgrade_queries["1.4.5"][] = "-- Customers table";
$upgrade_queries["1.4.5"][] = "CREATE TABLE `customers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT '',
  `address` varchar(255) DEFAULT NULL,
  `postcode` int(8) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `lat` varchar(12) DEFAULT NULL,
  `long` varchar(12) DEFAULT NULL,
  `contact_person` text DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `contact_mail` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` set('Active','Reserved','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// user permissions
$upgrade_queries["1.4.5"][] = "-- add user permissions";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `users` ADD `perm_customers` INT(1)  NOT NULL  DEFAULT '1';";
// add customer_id to all related objects
$upgrade_queries["1.4.5"][] = "-- add customer_id to all related objects";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `subnets` ADD `customer_id` INT(11) unsigned NULL default NULL;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `ipaddresses` ADD `customer_id` INT(11) unsigned NULL default NULL;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `vlans` ADD `customer_id` INT(11) unsigned NULL default NULL;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `vrf` ADD `customer_id` INT(11) unsigned NULL default NULL;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `circuits` ADD `customer_id` INT(11) unsigned NULL default NULL;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `racks` ADD `customer_id` INT(11) unsigned NULL default NULL;";
// Add relations to customers table
$upgrade_queries["1.4.5"][] = "-- Add relations to customers table";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `subnets` ADD CONSTRAINT `customer_subnets` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `ipaddresses` ADD CONSTRAINT `customer_ip` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `vlans` ADD CONSTRAINT `customer_vlans` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `vrf` ADD CONSTRAINT `customer_vrf` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `circuits` ADD CONSTRAINT `customer_circuits` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
$upgrade_queries["1.4.5"][] = "ALTER TABLE `racks` ADD CONSTRAINT `customer_racks` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
// add widget
$upgrade_queries["1.4.5"][] = "-- Add customers widget";
$upgrade_queries["1.4.5"][] = "INSERT INTO `widgets` ( `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('Customers', 'Shows customer list', 'customers', NULL, 'yes', '6', 'no', 'yes');";



#
# Subversion 1.4.6 queries
#
$upgrade_queries["1.4.6"][] = "-- Database version bump";
$upgrade_queries["1.4.6"][] = "UPDATE `settings` set `dbversion` = '6';";
// change permissions for modules
$upgrade_queries["1.4.6"][] = "-- Change permissions for modules";
$upgrade_queries["1.4.6"][] = "UPDATE `users` SET `pstn` = '1' WHERE `pstn` IS NULL;";
$upgrade_queries["1.4.6"][] = "ALTER TABLE `users` CHANGE `pstn` `perm_pstn` INT(1)  NOT NULL  DEFAULT '1';";

#
# Subversion 1.4.7 queries
#
$upgrade_queries["1.4.7"][] = "-- Database version bump";
$upgrade_queries["1.4.7"][] = "UPDATE `settings` set `dbversion` = '7';";
$upgrade_queries["1.4.7"][] = "ALTER TABLE `racks` ADD `topDown` tinyint(1) NOT NULL DEFAULT '0';";
$upgrade_queries["1.4.7"][] = "CREATE TABLE `rackContents` (
                                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(100) DEFAULT NULL,
                                `rack` int(11) unsigned DEFAULT NULL,
                                `rack_start` int(11) unsigned DEFAULT NULL,
                                `rack_size` int(11) unsigned DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `rack` (`rack`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


#
# Subversion 1.4.8 queries
#
$upgrade_queries["1.4.8"][] = "-- Database version bump";
$upgrade_queries["1.4.8"][] = "UPDATE `settings` set `dbversion` = '8';";
$upgrade_queries["1.4.8"][] = "-- Fix Consistency of VARCHAR Size on 'owner' column across tables 'ipaddresses','requests','pstnNumbers'";
$upgrade_queries["1.4.8"][] = "ALTER TABLE `ipaddresses` MODIFY COLUMN owner VARCHAR(128);";
$upgrade_queries["1.4.8"][] = "ALTER TABLE `requests` MODIFY COLUMN owner VARCHAR(128);";


#
# Subversion 1.4.9 queries
#
$upgrade_queries["1.4.9"][] = "-- Database version bump";
$upgrade_queries["1.4.9"][] = "UPDATE `settings` set `dbversion` = '9';";
// Set permissions
$upgrade_queries["1.4.9"][] = "-- Change permissions for modules";
$upgrade_queries["1.4.9"][] = "ALTER TABLE `users` ADD `perm_racks` INT(1)  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.9"][] = "ALTER TABLE `users` ADD `perm_nat` INT(1)  NOT NULL  DEFAULT '1';";


#
# Subversion 1.4.10 queries
#
$upgrade_queries["1.4.10"][] = "-- Database version bump";
$upgrade_queries["1.4.10"][] = "UPDATE `settings` set `dbversion` = '10';";
// Set permissions
$upgrade_queries["1.4.10"][] = "-- Change permissions for modules";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` ADD `module_permissions` varchar(255) COLLATE utf8_bin DEFAULT '{\"vlan\":\"1\",\"vrf\":\"1\",\"pdns\":\"1\",\"circuits\":\"1\",\"racks\":\"1\",\"nat\":\"1\",\"pstn\":\"1\",\"customers\":\"1\"}';";
// Drop old permission fields
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `pdns`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `editVlan`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `editCircuits`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `perm_nat`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `perm_racks`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `perm_pstn`;";
$upgrade_queries["1.4.10"][] = "ALTER TABLE `users` DROP `perm_customers`;";


#
# Subversion 1.4.11 queries
#
$upgrade_queries["1.4.11"][] = "-- Database version bump";
$upgrade_queries["1.4.11"][] = "UPDATE `settings` set `dbversion` = '11';";
$upgrade_queries["1.4.11"][] = "ALTER TABLE `users` CHANGE `module_permissions` `module_permissions` VARCHAR(255)  CHARACTER SET utf8  BINARY  NULL  DEFAULT '{\"vlan\":\"1\",\"vrf\":\"1\",\"pdns\":\"1\",\"circuits\":\"1\",\"racks\":\"1\",\"nat\":\"1\",\"pstn\":\"1\",\"customers\":\"1\",\"locations\":\"1\",\"devices\":\"1\"}';";



#
# Subversion 1.4.12 queries
#
$upgrade_queries["1.4.12"][] = "-- Database version bump";
$upgrade_queries["1.4.12"][] = "UPDATE `settings` set `dbversion` = '12';";
$upgrade_queries["1.4.12"][] = "ALTER TABLE usersAuthMethod MODIFY COLUMN `params` varchar(2048) DEFAULT NULL;";



#
# Subversion 1.4.13 queries
#
$upgrade_queries["1.4.13"][] = "-- Database version bump";
$upgrade_queries["1.4.13"][] = "UPDATE `settings` set `dbversion` = '13';";
$upgrade_queries["1.4.13"][] = "ALTER TABLE `users` ADD `compress_actions` TINYINT(1)  NULL  DEFAULT '1';";



#
# Subversion 1.4.14 queries
#
$upgrade_queries["1.4.14"][] = "-- Database version bump";
$upgrade_queries["1.4.14"][] = "UPDATE `settings` set `dbversion` = '14';";
$upgrade_queries["1.4.14"][] = "-- Change API security";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` CHANGE `app_security` `app_security` SET('ssl_code','ssl_token','crypt','user','none','ssl') CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'ssl_token'";
$upgrade_queries["1.4.14"][] = "UPDATE `api` set `app_security` = 'ssl_token' where `app_security` = 'ssl'";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` CHANGE `app_security` `app_security` SET('ssl_code','ssl_token','crypt','user','none') CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'ssl_token'";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` ADD `app_last_access` DATETIME  NULL";



#
# Subversion 1.4.15 queries
#
$upgrade_queries["1.4.15"][] = "-- Convert snmp_timeout to milliseconds";
$upgrade_queries["1.4.15"][] = "ALTER TABLE `devices` CHANGE `snmp_timeout` `snmp_timeout` mediumint(5) unsigned DEFAULT '1000';";
$upgrade_queries["1.4.15"][] = "UPDATE `devices` SET `snmp_timeout` = `snmp_timeout`/1000 WHERE `snmp_timeout` > 10000;";



#
# Subversion 1.4.16 queries
#
$upgrade_queries["1.4.16"][] = "-- Fix masterSubnetId index definition";
$upgrade_queries["1.4.16"][] = "ALTER TABLE `subnets` DROP INDEX `masterSubnetId`;";
$upgrade_queries["1.4.16"][] = "ALTER TABLE `subnets` ADD INDEX(`masterSubnetId`);";



#
# Subversion 1.4.17 queries
#
$upgrade_queries["1.4.17"][] = "-- Performance fix for linked addresses, moved to settings;";



#
# Subversion 1.4.18 queries
#
$upgrade_queries["1.4.18"][] = "-- DROP redundant indexes;";
$upgrade_queries["1.4.18"][] = "ALTER TABLE `users` DROP INDEX `id`;";
$upgrade_queries["1.4.18"][] = "ALTER TABLE `sections` DROP INDEX `id`;";



#
# Subversion 1.4.19 queries
#
$upgrade_queries["1.4.19"][] = "-- Support longer php_session ids (session.hash_function = sha512/whirlpool);";
$upgrade_queries["1.4.19"][] = "ALTER TABLE `php_sessions` CHANGE `id` `id` VARCHAR(128) NOT NULL DEFAULT '';";



// Japanese translation
$upgrade_queries["1.4.20"][] = "-- Add Japanese translation";
$upgrade_queries["1.4.20"][] = "INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Japanese', 'ja_JP.UTF-8');";



// Instruction widget
$upgrade_queries["1.4.21"][] = "-- Instruction widget";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `whref` `whref` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wsize` `wsize` ENUM('4','6','8','12') NOT NULL DEFAULT '6';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wadminonly` `wadminonly` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wactive` `wactive` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('User Instructions', 'Shows user instructions', 'instructions', NULL, 'yes', '6', 'no', 'yes');";

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