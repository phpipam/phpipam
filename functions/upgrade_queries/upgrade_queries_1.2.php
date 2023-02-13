<?php

#
# Version 1.2 queries
#
$upgrade_queries["1.2.0"]   = [];
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
$upgrade_queries["1.2.0"][] = "-- Version update";
$upgrade_queries["1.2.0"][] = "UPDATE `settings` set `version` = '1.2';";

#
# Version 1.21 queries
#
$upgrade_queries["1.21.0"]   = [];
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
$upgrade_queries["1.21.0"][] = "-- Version update";
$upgrade_queries["1.21.0"][] = "UPDATE `settings` set `version` = '1.21';";

#
# Version 1.22 queries
#
$upgrade_queries["1.22.0"]   = [];
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
$upgrade_queries["1.22.0"][] = "-- Version update";
$upgrade_queries["1.22.0"][] = "UPDATE `settings` set `version` = '1.22';";

#
# Version 1.23 queries
#
$upgrade_queries["1.23.0"]   = [];
// change default datetime
$upgrade_queries["1.23.0"][] = "-- Change default datetime";
$upgrade_queries["1.23.0"][] = "ALTER TABLE `ipaddresses` CHANGE `lastSeen` `lastSeen` DATETIME  NULL  DEFAULT '1970-01-01 00:00:01';";
$upgrade_queries["1.23.0"][] = "update ipaddresses set lastSeen='1970-01-01 00:00:01' where lastSeen < '0000-01-01 00:00:00';";
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
$upgrade_queries["1.23.0"][] = "-- Version update";
$upgrade_queries["1.23.0"][] = "UPDATE `settings` set `version` = '1.23';";

#
# Version 1.24 queries
#
$upgrade_queries["1.24.0"]   = [];
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
$upgrade_queries["1.24.0"][] = "-- Version update";
$upgrade_queries["1.24.0"][] = "UPDATE `settings` set `version` = '1.24';";

#
# Version 1.25 queries
#
$upgrade_queries["1.25.0"]   = [];
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
$upgrade_queries["1.25.0"][] = "-- Version update";
$upgrade_queries["1.25.0"][] = "UPDATE `settings` set `version` = '1.25';";

#
# Version 1.26 queries
#
$upgrade_queries["1.26.0"]   = [];
// add http saml2 method
$upgrade_queries["1.26.0"][] = "-- http saml2 method";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http','SAML2')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';";
// add transaction locking
$upgrade_queries["1.26.0"][] = "-- Add transaction locking";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `api` ADD `app_lock` INT(1)  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.26.0"][] = "ALTER TABLE `api` ADD `app_lock_wait` INT(4)  NOT NULL  DEFAULT '30';";
$upgrade_queries["1.26.0"][] = "-- Version update";
$upgrade_queries["1.26.0"][] = "UPDATE `settings` set `version` = '1.26';";

#
# Version 1.27 queries
#
$upgrade_queries["1.27.0"]   = [];
// add show supernet only
$upgrade_queries["1.27.0"][] = "-- Add show supernet only";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `sections` ADD `showSupernetOnly` INT(1)  NULL  DEFAULT '0';";
// add scan and discovery check time to database
$upgrade_queries["1.27.0"][] = "-- Scan and discovery date foir subnets";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `subnets` ADD `lastScan` TIMESTAMP  NULL;";
$upgrade_queries["1.27.0"][] = "ALTER TABLE `subnets` ADD `lastDiscovery` TIMESTAMP  NULL;";
$upgrade_queries["1.27.0"][] = "-- Version update";
$upgrade_queries["1.27.0"][] = "UPDATE `settings` set `version` = '1.27';";

#
# Version 1.28 queries
#
$upgrade_queries["1.28.0"]   = [];
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
$upgrade_queries["1.28.0"][] = "-- Version update";
$upgrade_queries["1.28.0"][] = "UPDATE `settings` set `version` = '1.28';";

#
# Version 1.29 queries
#
$upgrade_queries["1.29.0"]   = [];
// Add maintaneanceMode identifier
$upgrade_queries["1.29.0"][] = "-- Add maintaneanceMode";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` ADD `maintaneanceMode` TINYINT(1)  NULL  DEFAULT '0';";
// extend pingStatus intervals
$upgrade_queries["1.29.0"][] = "-- Extend pingStatus intervals";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` CHANGE `pingStatus` `pingStatus` VARCHAR(32)  CHARACTER SET utf8  NOT NULL  DEFAULT '1800;3600';";
$upgrade_queries["1.29.0"][] = "ALTER TABLE `settings` CHANGE `hiddenCustomFields` `hiddenCustomFields` TEXT  CHARACTER SET utf8  NULL;";
$upgrade_queries["1.29.0"][] = "-- Version update";
$upgrade_queries["1.29.0"][] = "UPDATE `settings` set `version` = '1.29';";