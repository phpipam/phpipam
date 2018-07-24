/* VERSION 1.1 */

/* Old version only had a VARCHAR(32) password */

ALTER TABLE `users` MODIFY COLUMN `password` CHAR(128) COLLATE utf8_bin DEFAULT NULL ;

/* VERSION 1.11 */
UPDATE `settings` set `version` = '1.11';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* set flag if auth was migrated to new database */
ALTER TABLE `settings` ADD `authmigrated` TINYINT  NOT NULL  DEFAULT '0';
/* add userAuthMethod table */
CREATE TABLE `usersAuthMethod` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` set('local','AD','LDAP') NOT NULL DEFAULT 'local',
  `params` varchar(1024) DEFAULT NULL,
  `protected` set('Yes','No') NOT NULL DEFAULT 'Yes',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/* insert default - local */
INSERT INTO `usersAuthMethod` (`id`, `type`, `params`, `protected`, `description`)
VALUES
	(1, 'local', NULL, 'Yes', 'Local database');
/* Add authMethod field */
ALTER TABLE `users` ADD `authMethod` INT(2)  NULL  DEFAULT 1;
/* update all domain users to use domain auth, settings will be migrated after first successfull login */
UPDATE `users` set `authMethod`=3 where `domainUser` = 1;

/* add ping types */
ALTER TABLE `settings` ADD `scanFPingPath` VARCHAR(64)  NULL  DEFAULT '/bin/fping';
ALTER TABLE `settings` ADD `scanPingType` SET('ping','pear','fping')  NOT NULL  DEFAULT 'ping';

/* vlanDomains */
CREATE TABLE `vlanDomains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `description` text,
  `permissions` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/* insert default domain */
INSERT INTO `vlanDomains` (`id`, `name`, `description`, `permissions`)
VALUES
	(1, 'default', 'default L2 domain', NULL);
/* add domainId to vlans */
ALTER TABLE `vlans` ADD `domainId` INT  NOT NULL  DEFAULT '1';

/* add last login for users */
ALTER TABLE `users` ADD `lastLogin` TIMESTAMP  NULL;
ALTER TABLE `users` ADD `lastActivity` TIMESTAMP  NULL;

/* permit null dns_name */
ALTER TABLE `ipaddresses` CHANGE `dns_name` `dns_name` VARCHAR(100)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* set ip addresses to null */
UPDATE `ipaddresses` set `dns_name` = NULL where `dns_name` = "";
UPDATE `ipaddresses` set `description` = NULL where `description` = "";
UPDATE `ipaddresses` set `mac` = NULL where `mac` = "";
UPDATE `ipaddresses` set `owner` = NULL where `owner` = "";
UPDATE `ipaddresses` set `port` = NULL where `port` = "";
UPDATE `ipaddresses` set `note` = NULL where `note` = "";

/* permit null description and subnet and mask */
ALTER TABLE `subnets` CHANGE `description` `description` text DEFAULT NULL;
ALTER TABLE `subnets` CHANGE `subnet` `subnet` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `subnets` CHANGE `mask` `mask` VARCHAR(255) NULL DEFAULT NULL;




/* VERSION 1.12 */
UPDATE `settings` set `version` = '1.12';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add gateway field to database */
ALTER TABLE `ipaddresses` ADD `is_gateway` TINYINT(1)  NULL  DEFAULT '0';

/* change tag */
ALTER TABLE `ipaddresses` CHANGE `state` `state` INT(3)  NULL  DEFAULT '1';

/* ip types */
CREATE TABLE `ipTags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `showtag` tinyint(4) DEFAULT '1',
  `bgcolor` varchar(7) DEFAULT '#000',
  `fgcolor` varchar(7) DEFAULT '#fff',
  `locked` set('No','Yes') NOT NULL DEFAULT 'No',
  `compress` set('No','Yes') NOT NULL DEFAULT 'No',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `ipTags` (`id`, `type`, `showtag`, `bgcolor`, `fgcolor`, `locked`, `compress`)
VALUES
	(1, 'Offline', 1, '#f59c99', '#ffffff', 'Yes', 'No'),
	(2, 'Used', 0, '#a9c9a4', '#ffffff', 'Yes', 'No'),
	(3, 'Reserved', 1, '#9ac0cd', '#ffffff', 'Yes', 'Yes'),
	(4, 'DHCP', 1, '#c9c9c9', '#ffffff', 'Yes', 'Yes');




/* VERSION 1.13 */
UPDATE `settings` set `version` = '1.13';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add radius auth */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','Radius')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';

/* add temp access */
ALTER TABLE `settings` ADD `tempAccess` TEXT  NULL;




/* VERSION 1.14 */
UPDATE `settings` set `version` = '1.14';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add tempShare */
ALTER TABLE `settings` ADD `tempShare` TINYINT(1)  NULL  DEFAULT '0';

/* move display Settings to user */
ALTER TABLE `users` ADD `dhcpCompress` BOOL  NOT NULL  DEFAULT '0';
ALTER TABLE `users` ADD `hideFreeRange` tinyint(1) DEFAULT '0';
ALTER TABLE `users` ADD `printLimit` int(4) unsigned DEFAULT '30';

/* drop old display settings */
ALTER TABLE `settings` DROP `dhcpCompress`;
ALTER TABLE `settings` DROP `hideFreeRange`;
ALTER TABLE `settings` DROP `printLimit`;




/* VERSION 1.15 */
UPDATE `settings` set `version` = '1.15';
/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* reset iptags */
DROP TABLE IF EXISTS `ipTags`;

CREATE TABLE `ipTags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `showtag` tinyint(4) DEFAULT '1',
  `bgcolor` varchar(7) DEFAULT '#000',
  `fgcolor` varchar(7) DEFAULT '#fff',
  `locked` set('No','Yes') NOT NULL DEFAULT 'No',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/* insert default values */
INSERT INTO `ipTags` (`id`, `type`, `showtag`, `bgcolor`, `fgcolor`, `locked`)
VALUES
	(1, 'Offline', 1, '#f59c99', '#ffffff', 'Yes'),
	(2, 'Used', 0, '#a9c9a4', '#ffffff', 'Yes'),
	(3, 'Reserved', 1, '#9ac0cd', '#ffffff', 'Yes'),
	(4, 'DHCP', 1, '#c9c9c9', '#ffffff', 'Yes');

/* update ipaddresses */
UPDATE `ipaddresses` SET `state` = 1 WHERE `state` > 3;
UPDATE `ipaddresses` SET `state` = 4 WHERE `state` = 3;
UPDATE `ipaddresses` SET `state` = 3 WHERE `state` = 2;
UPDATE `ipaddresses` SET `state` = 2 WHERE `state` = 1;
UPDATE `ipaddresses` SET `state` = 1 WHERE `state` = 0;

/* change tag */
ALTER TABLE `ipaddresses` CHANGE `state` `state` INT(3)  NULL  DEFAULT '2';

/* add autoSuggestNetwork flag and permitRWAvlan */
ALTER TABLE `settings` ADD `autoSuggestNetwork` TINYINT(1)  NOT NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `permitUserVlanCreate` TINYINT(1)  NOT NULL  DEFAULT '0';

/* add section DNS */
ALTER TABLE `sections` ADD `DNS` VARCHAR(128)  NULL  DEFAULT NULL;

/* mark subnet as full */
ALTER TABLE `subnets` ADD `isFull` TINYINT(1)  NULL  DEFAULT '0';

/* add state */
ALTER TABLE `subnets` ADD `state` INT(3)  NULL  DEFAULT '2';




/* VERSION 1.16 */
UPDATE `settings` set `version` = '1.16';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add compress tag for ranges */
ALTER TABLE `ipTags` ADD `compress` SET('No','Yes')  NOT NULL  DEFAULT 'No';
UPDATE `ipTags` SET `compress` = 'Yes' WHERE `id` = '4';

/* dhcp compress */
ALTER TABLE `users` CHANGE `dhcpCompress` `compressOverride` SET('default','Uncompress')  NOT NULL  DEFAULT 'default';
UPDATE `users` set `compressOverride` = 'default';

/* convert all tables to innodb */
ALTER TABLE `api` ENGINE = InnoDB;
ALTER TABLE `changelog` ENGINE = InnoDB;
ALTER TABLE `deviceTypes` ENGINE = InnoDB;
ALTER TABLE `devices` ENGINE = InnoDB;
ALTER TABLE `instructions` ENGINE = InnoDB;
ALTER TABLE `ipTags` ENGINE = InnoDB;
ALTER TABLE `ipaddresses` ENGINE = InnoDB;
ALTER TABLE `lang` ENGINE = InnoDB;
ALTER TABLE `loginAttempts` ENGINE = InnoDB;
ALTER TABLE `logs` ENGINE = InnoDB;
ALTER TABLE `requests` ENGINE = InnoDB;
ALTER TABLE `sections` ENGINE = InnoDB;
ALTER TABLE `settings` ENGINE = InnoDB;
ALTER TABLE `settingsMail` ENGINE = InnoDB;
ALTER TABLE `subnets` ENGINE = InnoDB;
ALTER TABLE `userGroups` ENGINE = InnoDB;
ALTER TABLE `users` ENGINE = InnoDB;
ALTER TABLE `usersAuthMethod` ENGINE = InnoDB;
ALTER TABLE `vlanDomains` ENGINE = InnoDB;
ALTER TABLE `vlans` ENGINE = InnoDB;
ALTER TABLE `vrf` ENGINE = InnoDB;
ALTER TABLE `widgets` ENGINE = InnoDB;
ALTER TABLE `settingsDomain` ENGINE = InnoDB;

/* add new widgets */
INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`)
VALUES
	('Tools menu', 'Shows quick access to tools menu', 'tools', NULL, 'yes', '6', 'no', 'yes'),
	('IP Calculator', 'Shows IP calculator as widget', 'ipcalc', NULL, 'yes', '6', 'no', 'yes');

/* add security type and permit empty app code */
ALTER TABLE `api` ADD `app_security` SET('crypt','ssl','none')  NOT NULL  DEFAULT 'ssl';
ALTER TABLE `api` CHANGE `app_code` `app_code` VARCHAR(32) NULL  DEFAULT '';




/* VERSION 1.17 */
UPDATE `settings` set `version` = '1.17';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;


/* add tokens */
ALTER TABLE `users` ADD `token` VARCHAR(24)  NULL  DEFAULT NULL;
ALTER TABLE `users` ADD `token_valid_until` DATETIME  NULL;

/* add scan agents */
ALTER TABLE `subnets` ADD `scanAgent` int(11) DEFAULT NULL;

/* powerDNS integration */
ALTER TABLE `settings` ADD `enablePowerDNS` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `powerDNS` TEXT  NULL;

ALTER TABLE `subnets` ADD `DNSrecursive` TINYINT(1)  NULL  DEFAULT '0';




/* VERSION 1.18 */
UPDATE `settings` set `version` = '1.18';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* powerDNS integration */
ALTER TABLE `ipaddresses` ADD `PTRignore` BINARY(1)  NULL  DEFAULT '0';
ALTER TABLE `ipaddresses` ADD `PTR` INT(11)  UNSIGNED  NULL  DEFAULT '0';

ALTER TABLE `subnets` ADD `DNSrecords` TINYINT(1)  NULL  DEFAULT '0';

/* log destination */
ALTER TABLE `settings` ADD `log` SET('Database','syslog')  NOT NULL  DEFAULT 'Database';

/* link subnet to device */
ALTER TABLE `subnets` ADD `device` INT  UNSIGNED  NULL  DEFAULT '0';

/* add table for recursive nameservers to subnets */
DROP TABLE IF EXISTS `nameservers`;

CREATE TABLE `nameservers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `namesrv1` varchar(255) DEFAULT NULL,
  `description` text,
  `permissions` varchar(128) DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* insert default google nameservers for global use */
INSERT INTO `nameservers` (`name`, `namesrv1`, `description`, `permissions`)
VALUES
	('Google NS', '8.8.8.8;8.8.4.4', 'Google public nameservers', '1;2');

/* add reference to nameservers in subnets table */
ALTER TABLE `subnets` ADD `nameserverId` int(11) NULL DEFAULT '0';




/* VERSION 1.19 */
UPDATE `settings` set `version` = '1.19';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Czech traslation */
INSERT INTO `lang` (`l_code`, `l_name`) VALUES ('cs_CZ', 'Czech');

/* add syslog location */
ALTER TABLE `settings` CHANGE `log` `log` SET('Database','syslog','both')  CHARACTER SET utf8  NOT NULL  DEFAULT 'Database';

/* mastersubnetid must not be null */
ALTER TABLE `subnets` CHANGE `masterSubnetId` `masterSubnetId` INT(11)  UNSIGNED  NOT NULL DEFAULT 0;

/* change username lenght to 25 */
ALTER TABLE `users` CHANGE `username` `username` varchar(25) CHARACTER SET utf8 NOT NULL DEFAULT '';

/* add NetIQ authentication type */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` set('local','AD','LDAP','NetIQ', 'Radius') NOT NULL DEFAULT 'local';

/* add header infotext for login page */
ALTER TABLE `settings`  ADD `siteLoginText` varchar(128) NULL DEFAULT NULL;

/* add unique ip+subnet requirement */
ALTER TABLE `ipaddresses` ADD UNIQUE INDEX `sid_ip_unique` (`ip_addr`, `subnetId`);

/* add tag to ip requests */
ALTER TABLE `requests` ADD `state` INT  NULL  DEFAULT '2';


/* scanagents */
DROP TABLE IF EXISTS `scanAgents`;
CREATE TABLE `scanAgents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `description` text,
  `type` set('direct','api','mysql') NOT NULL DEFAULT '',
  `code` varchar(32) DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `scanAgents` (`id`, `name`, `description`, `type`)
VALUES
	(1, 'locahost', 'Scanning from local machine', 'direct');


/* set all to localhost */
update `subnets` set `scanAgent` = "1" WHERE `pingSubnet` = 1;
update `subnets` set `scanAgent` = "1" WHERE `discoverSubnet` = 1;

/* The firewall zones table holds the information of the zones. */
CREATE TABLE `firewallZones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `generator` tinyint(1) NOT NULL,
  `length` int(2) DEFAULT NULL,
  `padding` tinyint(1) DEFAULT NULL,
  `zone` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `indicator` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `subnetId` int(11) unsigned DEFAULT NULL,
  `stacked` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vlanId` int(11) unsigned DEFAULT NULL,
  `permissions` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Firewall zone mapping table holds the information of device is part of a zone, plus some extra informations */
CREATE TABLE `firewallZoneMapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zoneId` int(11) unsigned NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deviceId` int(11) unsigned DEFAULT NULL,
  `interface` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Alter the settings table to inject the modul switch and default zone settings */
ALTER TABLE `settings`
ADD COLUMN `enableFirewallZones` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '',
ADD COLUMN `firewallZoneSettings` VARCHAR(1024) NOT NULL DEFAULT '{"zoneLength":3,"ipType":{"0":"v4","1":"v6"},"separator":"_","indicator":{"0":"own","1":"customer"},"zoneGenerator":"2","zoneGeneratorType":{"0":"decimal","1":"hex","2":"text"},"deviceType":"3","padding":"on","strictMode":"on"}' COMMENT '';




/* VERSION 1.2 */
UPDATE `settings` set `version` = '1.2';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0, `donate` = 0;

/* add subnetView Setting */
ALTER TABLE `settings` ADD `subnetView` TINYINT  NOT NULL  DEFAULT '0';

/* add 'user' to app_security set */
ALTER TABLE `api` CHANGE `app_security` `app_security` SET('crypt','ssl','user','none')  NOT NULL  DEFAULT 'ssl';

/* add english_US language */
INSERT INTO `lang` (`l_id`, `l_code`, `l_name`) VALUES (NULL, 'en_US', 'English (US)');

/* update the firewallZones table to suit the new layout */
ALTER TABLE `firewallZones` DROP COLUMN `vlanId`, DROP COLUMN `stacked`;

/* add a new table to store subnetId and zoneId */
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
    ON UPDATE NO ACTION);

/* copy old subnet IDs from firewallZones table into firewallZoneSubnet */
INSERT INTO `firewallZoneSubnet` (zoneId,subnetId) SELECT id AS zoneId,subnetId from `firewallZones`;

/* remove the field subnetId from firewallZones, it's not longer needed */
ALTER TABLE `firewallZones` DROP COLUMN `subnetId`;

/* add fk constrain and index to firewallZoneMappings to automatically remove a mapping if a device has been deleted */
ALTER TABLE `firewallZoneMapping` ADD INDEX `devId_idx` (`deviceId` ASC);
ALTER TABLE `firewallZoneMapping` ADD CONSTRAINT `devId` FOREIGN KEY (`deviceId`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

/* add firewallAddresObject field to the ipaddresses table to store fw addr. obj. names permanently */
ALTER TABLE `ipaddresses` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL;

/* activate the firewallAddressObject IP field filter on default */
UPDATE `settings` SET IPfilter = CONCAT(IPfilter,';firewallAddressObject');

/* add a column for subnet firewall address objects */
ALTER TABLE `subnets` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL;

/* add http auth method */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';

INSERT INTO `usersAuthMethod` (`type`, `params`, `protected`, `description`)
VALUES ('http', NULL, 'Yes', 'Apache authentication');

/* allow powerdns record management for user */
ALTER TABLE `users` ADD `pdns` SET('Yes','No')  NULL  DEFAULT 'No';

/* add Ip request widget */
INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`)
VALUES
('IP Request', 'IP Request widget', 'iprequest', NULL, 'no', '6', 'no', 'yes');

/* change mask size */
ALTER TABLE `subnets` CHANGE `mask` `mask` VARCHAR(3)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* add section to vrf */
ALTER TABLE `vrf` ADD `sections` VARCHAR(128)  NULL  DEFAULT NULL;




/* VERSION 1.21 */
UPDATE `settings` set `version` = '1.21';

/* New modules */
ALTER TABLE `settings` ADD `enableMulticast` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `enableNAT` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `enableSNMP` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `enableThreshold` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `enableRACK` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `link_field` VARCHAR(32)  NULL  DEFAULT '0';

/* add nat link */
ALTER TABLE `ipaddresses` ADD `NAT` VARCHAR(64)  NULL  DEFAULT NULL;
ALTER TABLE `subnets` ADD `NAT` VARCHAR(64)  NULL  DEFAULT NULL;

/* NAT table */
CREATE TABLE `nat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `type` set('source','static','destination') DEFAULT 'source',
  `src` text,
  `dst` text,
  `port` int(5) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/* snmp to devices */
ALTER TABLE `devices` ADD `snmp_community` VARCHAR(100)  NULL  DEFAULT NULL;
ALTER TABLE `devices` ADD `snmp_version` SET('0','1','2')  NULL  DEFAULT '0';
ALTER TABLE `devices` ADD `snmp_port` mediumint(5) unsigned DEFAULT '161';
ALTER TABLE `devices` ADD `snmp_timeout` mediumint(5) unsigned DEFAULT '1000000';
ALTER TABLE `devices` ADD `snmp_queries` VARCHAR(128)  NULL  DEFAULT NULL;

/* racks */
CREATE TABLE `racks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `size` int(2) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* rack info to devices */
ALTER TABLE `devices` ADD `rack` int(11) unsigned DEFAULT null;
ALTER TABLE `devices` ADD `rack_start` int(11) unsigned DEFAULT null;
ALTER TABLE `devices` ADD `rack_size` int(11) unsigned DEFAULT null;

/* add threshold module to subnets */
ALTER TABLE `subnets` ADD `threshold` int(3)  NULL  DEFAULT 0;

/* threshold and inactive hosts widget */
INSERT INTO `widgets` ( `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES ('Threshold', 'Shows threshold usage for top 5 subnets', 'threshold', NULL, 'yes', '6', 'no', 'yes');
INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Inactive hosts', 'Shows list of inactive hosts for defined period', 'inactive-hosts', 86400, 'yes', '6', 'yes', 'yes');

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;




/* VERSION 1.22 */
UPDATE `settings` set `version` = '1.22';

/* drop unused snmp table */
DROP TABLE IF EXISTS `snmp`;


/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* add DHCP to settings */
ALTER TABLE `settings` ADD `enableDHCP` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `settings` ADD `DHCP` VARCHAR(256) NULL default '{"type":"kea","settings":{"file":"\/etc\/kea\/kea.conf"}}';

/* permit normal users to manage VLANs */
ALTER TABLE `users` ADD `editVlan` SET('Yes','No')  NULL  DEFAULT 'No';

/* remove permitUserVlanCreate - not needed */
ALTER TABLE `settings` DROP `permitUserVlanCreate`;

/* add menu type */
ALTER TABLE `users` ADD `menuType` SET('Static','Dynamic')  NULL  DEFAULT 'Dynamic';



/* VERSION 1.23 */
UPDATE `settings` set `version` = '1.23';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* change default datetime */
ALTER TABLE `ipaddresses` CHANGE `lastSeen` `lastSeen` DATETIME  NULL  DEFAULT '1970-01-01 00:00:01';

/* add linked subnet field */
ALTER TABLE `subnets` ADD `linked_subnet` INT(11)  UNSIGNED  NULL  DEFAULT NULL;

/* add device to table */
ALTER TABLE `nat` ADD `device` INT(11)  UNSIGNED  NULL  DEFAULT NULL;

/* drop NAT fields */
ALTER TABLE `subnets` DROP `NAT`;
ALTER TABLE `ipaddresses` DROP `NAT`;

/* extend username field to 64 chars */
ALTER TABLE `logs` CHANGE `username` `username` VARCHAR(64)  CHARACTER SET utf8  NULL  DEFAULT NULL;
ALTER TABLE `users` CHANGE `username` `username` VARCHAR(64)  CHARACTER SET utf8  NOT NULL  DEFAULT '';

/* locations */
ALTER TABLE `settings` ADD `enableLocations` TINYINT(1)  NULL  DEFAULT '1';
ALTER TABLE `devices` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;
ALTER TABLE `racks` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;
ALTER TABLE `subnets` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;


CREATE TABLE `locations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` text,
  `lat` varchar(12) DEFAULT NULL,
  `long` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `locations` ADD `address` VARCHAR(128)  NULL  DEFAULT NULL;

/* nat changes */
ALTER TABLE `nat` CHANGE `port` `src_port` INT(5)  NULL  DEFAULT NULL;
ALTER TABLE `nat` ADD `dst_port` INT(5)  NULL  DEFAULT NULL;



/* VERSION 1.24 */
UPDATE `settings` set `version` = '1.24';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* PSTN */
ALTER TABLE `settings` ADD `enablePSTN` TINYINT(1)  NULL  DEFAULT '1';

/* pstnPrefixes */
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* pstnNumbers */
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* use permissions for pstn */
ALTER TABLE `users` ADD `pstn` INT(1)  NULL  DEFAULT '1';




/* VERSION 1.25 */
UPDATE `settings` set `version` = '1.25';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* update languges */
UPDATE `lang` SET `l_code` = 'en_GB.UTF8' WHERE `l_code` = 'en';
UPDATE `lang` SET `l_code` = 'sl_SI.UTF8' WHERE `l_code` = 'sl_SI';
UPDATE `lang` SET `l_code` = 'fr_FR.UTF8' WHERE `l_code` = 'fr_FR';
UPDATE `lang` SET `l_code` = 'nl_NL.UTF8' WHERE `l_code` = 'nl_NL';
UPDATE `lang` SET `l_code` = 'de_DE.UTF8' WHERE `l_code` = 'de_DE';
UPDATE `lang` SET `l_code` = 'pt_BR.UTF8' WHERE `l_code` = 'pt_BR';
UPDATE `lang` SET `l_code` = 'es_ES.UTF8' WHERE `l_code` = 'es_ES';
UPDATE `lang` SET `l_code` = 'cs_CZ.UTF8' WHERE `l_code` = 'cs_CZ';
UPDATE `lang` SET `l_code` = 'en_US.UTF8' WHERE `l_code` = 'en_US';

/* location to addresses */
ALTER TABLE `ipaddresses` ADD `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL;

/* location widget */
INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Locations', 'Shows map of locations', 'locations', NULL, 'yes', '6', 'no', 'yes');

/* remove print limit */
ALTER TABLE `users` DROP `printLimit`;



/* VERSION 1.26 */
UPDATE `settings` set `version` = '1.26';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* add http saml2 method */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http','SAML2')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';

/* add transaction locking */
ALTER TABLE `api` ADD `app_lock` INT(1)  NOT NULL  DEFAULT '0';
ALTER TABLE `api` ADD `app_lock_wait` INT(4)  NOT NULL  DEFAULT '30';




/* VERSION 1.27 */
UPDATE `settings` set `version` = '1.27';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* ad show supernet only */
ALTER TABLE `sections` ADD `showSupernetOnly` INT(1)  NULL  DEFAULT '0';

/* add scan and discovery check time to database */
ALTER TABLE `subnets` ADD `lastScan` TIMESTAMP  NULL;
ALTER TABLE `subnets` ADD `lastDiscovery` TIMESTAMP  NULL;



/* VERSION 1.28 */
UPDATE `settings` set `version` = '1.28';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Extend username to 255 chars for LDAP logins */
ALTER TABLE `users` CHANGE `username` `username` VARCHAR(255)  CHARACTER SET utf8  NOT NULL  DEFAULT '';
ALTER TABLE `logs` CHANGE `username` `username` VARCHAR(255)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* expand hostname valude in IP requests to match ipaddresses table */
ALTER TABLE `requests` CHANGE `dns_name` `dns_name` VARCHAR(100)  CHARACTER SET utf8  NULL  DEFAULT NULL;
ALTER TABLE `requests` CHANGE `description` `description` VARCHAR(64)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* update Tags when state change occurs */
ALTER TABLE `settings` ADD `updateTags` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `ipTags` ADD `updateTag` TINYINT(1)  NULL  DEFAULT '0';

UPDATE `ipTags` set `updateTag`=1 where `id`=1;
UPDATE `ipTags` set `updateTag`=1 where `id`=2;
UPDATE `ipTags` set `updateTag`=1 where `id`=3;
UPDATE `ipTags` set `updateTag`=1 where `id`=4;



/* VERSION 1.29 */
UPDATE `settings` set `version` = '1.29';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Add maintaneanceMode identifier */
ALTER TABLE `settings` ADD `maintaneanceMode` TINYINT(1)  NULL  DEFAULT '0';
/* extend pingStatus intervals */
ALTER TABLE `settings` CHANGE `pingStatus` `pingStatus` VARCHAR(32)  CHARACTER SET utf8  NOT NULL  DEFAULT '1800;3600';
ALTER TABLE `settings` CHANGE `hiddenCustomFields` `hiddenCustomFields` TEXT  CHARACTER SET utf8  NULL;



/* VERSION 1.30 */
UPDATE `settings` set `version` = '1.3';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* add option to globally enforce uniqueness */
ALTER TABLE `settings` ADD `enforceUnique` TINYINT(1)  NULL  DEFAULT '1';
UPDATE `subnets` set `vrfId` = 0 WHERE `vrfId` IS NULL;

/* update languges */
UPDATE `lang` SET `l_code` = 'en_GB.UTF-8' WHERE `l_code` = 'en_GB.UTF8';
UPDATE `lang` SET `l_code` = 'sl_SI.UTF-8' WHERE `l_code` = 'sl_SI.UTF8';
UPDATE `lang` SET `l_code` = 'fr_FR.UTF-8' WHERE `l_code` = 'fr_FR.UTF8';
UPDATE `lang` SET `l_code` = 'nl_NL.UTF-8' WHERE `l_code` = 'nl_NL.UTF8';
UPDATE `lang` SET `l_code` = 'de_DE.UTF-8' WHERE `l_code` = 'de_DE.UTF8';
UPDATE `lang` SET `l_code` = 'pt_BR.UTF-8' WHERE `l_code` = 'pt_BR.UTF8';
UPDATE `lang` SET `l_code` = 'es_ES.UTF-8' WHERE `l_code` = 'es_ES.UTF8';
UPDATE `lang` SET `l_code` = 'cs_CZ.UTF-8' WHERE `l_code` = 'cs_CZ.UTF8';
UPDATE `lang` SET `l_code` = 'en_US.UTF-8' WHERE `l_code` = 'en_US.UTF8';

/* Russian traslation */
INSERT INTO `lang` (`l_name`, `l_code`) VALUES ('Russian', 'ru_RU.UTF-8');

/* fix scanAgents typo */
update `scanAgents` set `name` = "localhost" WHERE `id` = 1;

/* Add option to show custom field results as nested and show links default */
ALTER TABLE `api` ADD `app_nest_custom_fields` TINYINT(1)  NULL  DEFAULT '0';
ALTER TABLE `api` ADD `app_show_links` TINYINT(1)  NULL  DEFAULT '0';

/* Add index to ctype for changelog */
ALTER TABLE changelog ADD INDEX(ctype);

/* extend sections for devices */
ALTER TABLE `devices` CHANGE `sections` `sections` VARCHAR(1024)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* chinese translation */
INSERT INTO `lang` (`l_code`, `l_name`) VALUES ('zh_CN.UTF-8', 'Chinese');

/* hostname extend */
ALTER TABLE `devices` CHANGE `hostname` `hostname` VARCHAR(100)  CHARACTER SET utf8  NULL  DEFAULT NULL;
/* decode mac addresses */
ALTER TABLE `settings` ADD `decodeMAC` TINYINT(1)  NULL  DEFAULT '1';






/* VERSION 1.31 */
UPDATE `settings` set `version` = '1.31';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Circuits flag */
ALTER TABLE `settings` ADD `enableCircuits` TINYINT(1)  NULL  DEFAULT '1';

/* circuit providers */
CREATE TABLE `circuitProviders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `description` text,
  `contact` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* circuits */
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Compact menu */
ALTER TABLE `users` ADD `menuCompact` TINYINT  NULL  DEFAULT '1';

/* Add line for rack displayin and back side */
ALTER TABLE `racks` ADD `line` INT(11)  NOT NULL  DEFAULT '1';
ALTER TABLE `racks` ADD `front` INT(11)  NOT NULL  DEFAULT '0';

/* add circuit permissions for normal users */
ALTER TABLE `users` ADD `editCircuits` SET('Yes','No')  NULL  DEFAULT 'No';

/* Add option for DNS resolving host in subnet */
ALTER TABLE `subnets` ADD `resolveDNS` TINYINT(1)  NULL  DEFAULT '0';

/* Cahnge name for back side */
ALTER TABLE `racks` CHANGE `front` `hasBack` TINYINT(1)  NOT NULL  DEFAULT '0';
ALTER TABLE `racks` CHANGE `line` `row` INT(11)  NOT NULL  DEFAULT '1';

/* add permission propagation policy */
ALTER TABLE `settings` ADD `permissionPropagate` TINYINT(1)  NULL  DEFAULT '1';

/* extend log details */
ALTER TABLE `logs` CHANGE `details` `details` TEXT  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL;

/* snmpv3 */
ALTER TABLE `devices` ADD `snmp_v3_sec_level` SET('none','noAuthNoPriv','authNoPriv','authPriv')  NULL  DEFAULT 'none';
ALTER TABLE `devices` ADD `snmp_v3_auth_protocol` SET('none','MD5','SHA')  NULL  DEFAULT 'none';
ALTER TABLE `devices` ADD `snmp_v3_auth_pass` VARCHAR(64)  NULL  DEFAULT NULL;
ALTER TABLE `devices` ADD `snmp_v3_priv_protocol` SET('none','DES','AES')  NULL  DEFAULT 'none';
ALTER TABLE `devices` ADD `snmp_v3_priv_pass` VARCHAR(64)  NULL  DEFAULT NULL;
ALTER TABLE `devices` ADD `snmp_v3_ctx_name` VARCHAR(64)  NULL  DEFAULT NULL;
ALTER TABLE `devices` ADD `snmp_v3_ctx_engine_id` VARCHAR(64)  NULL  DEFAULT NULL;

/* add indexes to locations */
ALTER TABLE `devices` ADD INDEX (`location`);
ALTER TABLE `racks` ADD INDEX (`location`);
ALTER TABLE `subnets` ADD INDEX (`location`);
ALTER TABLE `ipaddresses` ADD INDEX (`location`);
ALTER TABLE `circuits` ADD INDEX (`location1`);
ALTER TABLE `circuits` ADD INDEX (`location2`);






/* VERSION 1.32 */
UPDATE `settings` set `version` = '1.32';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Required IP fieldsg */
ALTER TABLE `settings` ADD `IPrequired` VARCHAR(128)  NULL  DEFAULT NULL;

/* Change dns_name to hostname */
ALTER TABLE `ipaddresses` CHANGE `dns_name` `hostname` VARCHAR(255)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT NULL;
ALTER TABLE `requests` CHANGE `dns_name` `hostname` VARCHAR(255)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT NULL;

/* Subnet table indexes: has_slaves(), fetch_section_subnets(), subnet_familytree_*(), verify_subnet_overlapping(), verify_vrf_overlapping()... */
ALTER TABLE `subnets` ADD INDEX (`masterSubnetId`);
ALTER TABLE `subnets` ADD INDEX (`sectionId`);
ALTER TABLE `subnets` ADD INDEX (`vrfId`);

/* bandwidth calculator widget */
INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`) VALUES (NULL, 'Bandwidth calculator', 'Calculate bandwidth', 'bw_calculator', NULL, 'no', '6', 'no', 'yes');

/* add theme */
ALTER TABLE `settings` ADD `theme` VARCHAR(32)  NOT NULL  DEFAULT 'dark';
ALTER TABLE `users` ADD `theme` VARCHAR(32)  NULL  DEFAULT '';

/* Allow SNMPv3 to be selected for devices */
ALTER TABLE `devices` CHANGE `snmp_version` `snmp_version` SET('0','1','2','3') DEFAULT '0';

/* Add database schema version field */
ALTER TABLE `settings` ADD `dbversion` INT(8)  NOT NULL  DEFAULT '0';


/* Add database PWMin field */
ALTER TABLE `settings` ADD `pwMin` INT(8)  NOT NULL  DEFAULT '0';

