<?php

#
# Version 1.4 queries
#
$upgrade_queries["1.4.0"]   = [];
$upgrade_queries["1.4.0"][] = "-- Version update";
$upgrade_queries["1.4.0"][] = "UPDATE `settings` set `version` = '1.4';";

#
# Subversion 1.4.1 queries
#
// Add password policy
$upgrade_queries["1.4.1"]   = [];
$upgrade_queries["1.4.1"][] = "-- Add password policy";
$upgrade_queries["1.4.1"][] = "ALTER TABLE `settings` ADD `passwordPolicy` VARCHAR(1024)  NULL  DEFAULT '{\"minLength\":8,\"maxLength\":0,\"minNumbers\":0,\"minLetters\":0,\"minLowerCase\":0,\"minUpperCase\":0,\"minSymbols\":0,\"maxSymbols\":0,\"allowedSymbols\":\"#,_,-,!,[,],=,~\"}';";
$upgrade_queries["1.4.1"][] = "-- Database version bump";
$upgrade_queries["1.4.1"][] = "UPDATE `settings` set `dbversion` = '1';";

#
# Subversion 1.4.2 queries
#
$upgrade_queries["1.4.2"]   = [];
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
$upgrade_queries["1.4.2"][] = "-- Database version bump";
$upgrade_queries["1.4.2"][] = "UPDATE `settings` set `dbversion` = '2';";

#
# Subversion 1.4.3 queries
#
$upgrade_queries["1.4.3"]   = [];
$upgrade_queries["1.4.3"][] = "-- Create php_sessions table";
$upgrade_queries["1.4.3"][] = "CREATE TABLE `php_sessions` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `access` int(10) unsigned DEFAULT NULL,
  `data` text NOT NULL,
  `remote_ip` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$upgrade_queries["1.4.3"][] = "-- Database version bump";
$upgrade_queries["1.4.3"][] = "UPDATE `settings` set `dbversion` = '3';";

#
# Subversion 1.4.4 queries
#
$upgrade_queries["1.4.4"]   = [];
$upgrade_queries["1.4.4"][] = "-- Add 2fa to settings";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_provider` SET('none','Google_Authenticator')  NULL  DEFAULT 'none';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_name` VARCHAR(32)  NULL  DEFAULT 'phpipam';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_length` INT(2)  NULL  DEFAULT '16';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `settings` ADD `2fa_userchange` BOOL  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.4"][] = "-- Add 2fa to users";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `users` ADD `2fa` BOOL  NOT NULL  DEFAULT '0';";
$upgrade_queries["1.4.4"][] = "ALTER TABLE `users` ADD `2fa_secret` VARCHAR(32)  NULL  DEFAULT NULL;";
$upgrade_queries["1.4.4"][] = "-- Database version bump";
$upgrade_queries["1.4.4"][] = "UPDATE `settings` set `dbversion` = '4';";

#
# Subversion 1.4.5 queries
#
$upgrade_queries["1.4.5"]   = [];
// customers module
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
$upgrade_queries["1.4.5"][] = "-- Database version bump";
$upgrade_queries["1.4.5"][] = "UPDATE `settings` set `dbversion` = '5';";

#
# Subversion 1.4.6 queries
#
$upgrade_queries["1.4.6"]   = [];
// change permissions for modules
$upgrade_queries["1.4.6"][] = "-- Change permissions for modules";
$upgrade_queries["1.4.6"][] = "UPDATE `users` SET `pstn` = '1' WHERE `pstn` IS NULL;";
$upgrade_queries["1.4.6"][] = "ALTER TABLE `users` CHANGE `pstn` `perm_pstn` INT(1)  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.6"][] = "-- Database version bump";
$upgrade_queries["1.4.6"][] = "UPDATE `settings` set `dbversion` = '5';";

#
# Subversion 1.4.7 queries
#
$upgrade_queries["1.4.7"]   = [];
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
$upgrade_queries["1.4.7"][] = "-- Database version bump";
$upgrade_queries["1.4.7"][] = "UPDATE `settings` set `dbversion` = '7';";

#
# Subversion 1.4.8 queries
#
$upgrade_queries["1.4.8"]   = [];
$upgrade_queries["1.4.8"][] = "-- Fix Consistency of VARCHAR Size on 'owner' column across tables 'ipaddresses','requests','pstnNumbers'";
$upgrade_queries["1.4.8"][] = "ALTER TABLE `ipaddresses` MODIFY COLUMN owner VARCHAR(128);";
$upgrade_queries["1.4.8"][] = "ALTER TABLE `requests` MODIFY COLUMN owner VARCHAR(128);";
$upgrade_queries["1.4.8"][] = "-- Database version bump";
$upgrade_queries["1.4.8"][] = "UPDATE `settings` set `dbversion` = '8';";

#
# Subversion 1.4.9 queries
#
// Set permissions
$upgrade_queries["1.4.9"]   = [];
$upgrade_queries["1.4.9"][] = "-- Change permissions for modules";
$upgrade_queries["1.4.9"][] = "ALTER TABLE `users` ADD `perm_racks` INT(1)  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.9"][] = "ALTER TABLE `users` ADD `perm_nat` INT(1)  NOT NULL  DEFAULT '1';";
$upgrade_queries["1.4.9"][] = "-- Database version bump";
$upgrade_queries["1.4.9"][] = "UPDATE `settings` set `dbversion` = '9';";

#
# Subversion 1.4.10 queries
#
// Set permissions
$upgrade_queries["1.4.10"]   = [];
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
$upgrade_queries["1.4.10"][] = "-- Database version bump";
$upgrade_queries["1.4.10"][] = "UPDATE `settings` set `dbversion` = '10';";

#
# Subversion 1.4.11 queries
#
$upgrade_queries["1.4.11"]   = [];
$upgrade_queries["1.4.11"][] = "ALTER TABLE `users` CHANGE `module_permissions` `module_permissions` VARCHAR(255)  CHARACTER SET utf8  BINARY  NULL  DEFAULT '{\"vlan\":\"1\",\"vrf\":\"1\",\"pdns\":\"1\",\"circuits\":\"1\",\"racks\":\"1\",\"nat\":\"1\",\"pstn\":\"1\",\"customers\":\"1\",\"locations\":\"1\",\"devices\":\"1\"}';";
$upgrade_queries["1.4.11"][] = "-- Database version bump";
$upgrade_queries["1.4.11"][] = "UPDATE `settings` set `dbversion` = '11';";

#
# Subversion 1.4.12 queries
#
$upgrade_queries["1.4.12"]   = [];
$upgrade_queries["1.4.12"][] = "ALTER TABLE usersAuthMethod MODIFY COLUMN `params` varchar(2048) DEFAULT NULL;";
$upgrade_queries["1.4.12"][] = "-- Database version bump";
$upgrade_queries["1.4.12"][] = "UPDATE `settings` set `dbversion` = '12';";

#
# Subversion 1.4.13 queries
#
$upgrade_queries["1.4.13"]   = [];
$upgrade_queries["1.4.13"][] = "ALTER TABLE `users` ADD `compress_actions` TINYINT(1)  NULL  DEFAULT '1';";
$upgrade_queries["1.4.13"][] = "-- Database version bump";
$upgrade_queries["1.4.13"][] = "UPDATE `settings` set `dbversion` = '13';";

#
# Subversion 1.4.14 queries
#
$upgrade_queries["1.4.14"]   = [];
$upgrade_queries["1.4.14"][] = "-- Change API security";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` CHANGE `app_security` `app_security` SET('ssl_code','ssl_token','crypt','user','none','ssl') CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'ssl_token';";
$upgrade_queries["1.4.14"][] = "UPDATE `api` set `app_security` = 'ssl_token' where `app_security` = 'ssl';";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` CHANGE `app_security` `app_security` SET('ssl_code','ssl_token','crypt','user','none') CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'ssl_token';";
$upgrade_queries["1.4.14"][] = "ALTER TABLE `api` ADD `app_last_access` DATETIME  NULL;";
$upgrade_queries["1.4.14"][] = "-- Database version bump";
$upgrade_queries["1.4.14"][] = "UPDATE `settings` set `dbversion` = '14';";

#
# Subversion 1.4.15 queries
#
$upgrade_queries["1.4.15"]   = [];
$upgrade_queries["1.4.15"][] = "-- Convert snmp_timeout to milliseconds";
$upgrade_queries["1.4.15"][] = "ALTER TABLE `devices` CHANGE `snmp_timeout` `snmp_timeout` mediumint(5) unsigned DEFAULT '1000';";
$upgrade_queries["1.4.15"][] = "UPDATE `devices` SET `snmp_timeout` = `snmp_timeout`/1000 WHERE `snmp_timeout` > 10000;";
$upgrade_queries["1.4.15"][] = "-- Database version bump";
$upgrade_queries["1.4.15"][] = "UPDATE `settings` set `dbversion` = '15';";

#
# Subversion 1.4.16 queries
#
$upgrade_queries["1.4.16"]   = [];
$upgrade_queries["1.4.16"][] = "-- Fix masterSubnetId index definition";
$upgrade_queries["1.4.16"][] = "ALTER TABLE `subnets` DROP INDEX `masterSubnetId`;";
$upgrade_queries["1.4.16"][] = "ALTER TABLE `subnets` ADD INDEX(`masterSubnetId`);";
$upgrade_queries["1.4.16"][] = "-- Database version bump";
$upgrade_queries["1.4.16"][] = "UPDATE `settings` set `dbversion` = '16';";

#
# Subversion 1.4.17 queries - Performance fix for linked addresses, moved to settings
#
$upgrade_queries["1.4.17"]   = [];
$upgrade_queries["1.4.17"][] = "-- Database version bump";
$upgrade_queries["1.4.17"][] = "UPDATE `settings` set `dbversion` = '17';";

#
# Subversion 1.4.18 queries
#
$upgrade_queries["1.4.18"]   = [];
$upgrade_queries["1.4.18"][] = "-- Database version bump";
$upgrade_queries["1.4.18"][] = "UPDATE `settings` set `dbversion` = '18';";

#
# Subversion 1.4.19 queries
#
$upgrade_queries["1.4.19"]   = [];
$upgrade_queries["1.4.19"][] = "-- Support longer php_session ids (session.hash_function = sha512/whirlpool);";
$upgrade_queries["1.4.19"][] = "ALTER TABLE `php_sessions` CHANGE `id` `id` VARCHAR(128) NOT NULL DEFAULT '';";
$upgrade_queries["1.4.19"][] = "-- Database version bump";
$upgrade_queries["1.4.19"][] = "UPDATE `settings` set `dbversion` = '19';";

#
# Subversion 1.4.20 queries
#
// Japanese translation
$upgrade_queries["1.4.20"]   = [];
$upgrade_queries["1.4.20"][] = "-- Add Japanese translation";
$upgrade_queries["1.4.20"][] = "INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Japanese', 'ja_JP.UTF-8');";
$upgrade_queries["1.4.20"][] = "-- Database version bump";
$upgrade_queries["1.4.20"][] = "UPDATE `settings` set `dbversion` = '20';";

#
# Subversion 1.4.21 queries
#
// instructions widget
$upgrade_queries["1.4.21"]   = [];
$upgrade_queries["1.4.21"][] = "-- Instruction widget";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `whref` `whref` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wsize` `wsize` ENUM('4','6','8','12') NOT NULL DEFAULT '6';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wadminonly` `wadminonly` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "ALTER TABLE `widgets` CHANGE `wactive` `wactive` ENUM('yes','no') NOT NULL DEFAULT 'no';";
$upgrade_queries["1.4.21"][] = "INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('User Instructions', 'Shows user instructions', 'instructions', NULL, 'yes', '6', 'no', 'yes');";
$upgrade_queries["1.4.21"][] = "-- Database version bump";
$upgrade_queries["1.4.21"][] = "UPDATE `settings` set `dbversion` = '21';";

#
# Subversion 1.4.22 queries
#
// disable user
$upgrade_queries["1.4.22"]   = [];
$upgrade_queries["1.4.22"][] = "-- Add disabled user flag";
$upgrade_queries["1.4.22"][] = "ALTER TABLE `users` ADD `disabled` SET('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.4.22"][] = "-- Database version bump";
$upgrade_queries["1.4.22"][] = "UPDATE `settings` set `dbversion` = '22';";

#
# Subversion 1.4.23 queries
#
// extend hostname for devices
$upgrade_queries["1.4.23"]   = [];
$upgrade_queries["1.4.23"][] = "ALTER TABLE `devices` CHANGE `hostname` `hostname` VARCHAR(255)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT NULL;";
// routing module
$upgrade_queries["1.4.23"][] = "-- Add routing module flag";
$upgrade_queries["1.4.23"][] = "ALTER TABLE `settings` ADD `enableRouting` TINYINT(1)  NULL  DEFAULT '0';";
// routing tables
$upgrade_queries["1.4.23"][] = "CREATE TABLE `routing_bgp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `local_as` int(12) unsigned NOT NULL,
  `local_address` varchar(100) NOT NULL DEFAULT '',
  `peer_name` varchar(255) NOT NULL DEFAULT '',
  `peer_as` int(12) unsigned NOT NULL,
  `peer_address` varchar(100) NOT NULL DEFAULT '',
  `bgp_type` enum('internal','external') NOT NULL DEFAULT 'external',
  `vrf_id` int(11) unsigned DEFAULT NULL,
  `circuit_id` int(11) unsigned DEFAULT NULL,
  `customer_id` int(11) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vrf_id` (`vrf_id`),
  KEY `circuit_id` (`circuit_id`),
  KEY `cust_id` (`customer_id`),
  CONSTRAINT `circuit_id` FOREIGN KEY (`circuit_id`) REFERENCES `circuits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `cust_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `vrf_id` FOREIGN KEY (`vrf_id`) REFERENCES `vrf` (`vrfId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// subnet mapping
$upgrade_queries["1.4.23"][] = "CREATE TABLE `routing_subnets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('bgp','ospf') NOT NULL DEFAULT 'bgp',
  `direction` enum('advertised','received') NOT NULL DEFAULT 'advertised',
  `object_id` int(11) unsigned NOT NULL,
  `subnet_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`object_id`,`subnet_id`),
  KEY `bgp_id` (`object_id`),
  KEY `subnet_id` (`subnet_id`),
  CONSTRAINT `bgp_id` FOREIGN KEY (`object_id`) REFERENCES `routing_bgp` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `subnet_id` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;";

$upgrade_queries["1.4.23"][] = "-- Database version bump";
$upgrade_queries["1.4.23"][] = "UPDATE `settings` set `dbversion` = '23';";

#
# Subversion 1.4.24 queries
#
// add policy NAT option
$upgrade_queries["1.4.24"]   = [];
$upgrade_queries["1.4.24"][] = "ALTER TABLE `nat` ADD `policy` SET('Yes','No')  NOT NULL  DEFAULT 'No';";
$upgrade_queries["1.4.24"][] = "ALTER TABLE `nat` ADD `policy_dst` VARCHAR(255)  NULL  DEFAULT NULL;";
$upgrade_queries["1.4.24"][] = "-- Database version bump";
$upgrade_queries["1.4.24"][] = "UPDATE `settings` set `dbversion` = '24';";

#
# Subversion 1.4.25 queries
#
// Traditional Chinese translation
$upgrade_queries["1.4.25"]   = [];
$upgrade_queries["1.4.25"][] = "-- Add Russian and Chinese translations";
$upgrade_queries["1.4.25"][] = "INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Chinese traditional (繁體中文)', 'zh_TW.UTF-8');";
$upgrade_queries["1.4.25"][] = "-- Database version bump";
$upgrade_queries["1.4.25"][] = "UPDATE `settings` set `dbversion` = '25';";
