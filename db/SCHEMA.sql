# Dump of table instructions
# ------------------------------------------------------------
DROP TABLE IF EXISTS `instructions`;

CREATE TABLE `instructions` (
  `id` int(11) NOT NULL,
  `instructions` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `instructions` (`id`, `instructions`)
VALUES
	(1,'You can write instructions under admin menu!');


# Dump of table ipaddresses
# ------------------------------------------------------------
DROP TABLE IF EXISTS `ipaddresses`;

CREATE TABLE `ipaddresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subnetId` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `ip_addr` varchar(100) NOT NULL,
  `is_gateway` TINYINT(1)  NULL  DEFAULT '0',
  `description` varchar(64) DEFAULT NULL,
  `dns_name` varchar(100) DEFAULT NULL,
  `mac` varchar(20) DEFAULT NULL,
  `owner` varchar(32) DEFAULT NULL,
  `state`  INT(3)  NULL  DEFAULT '2',
  `switch` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `port` varchar(32) DEFAULT NULL,
  `note` text,
  `lastSeen` DATETIME  NULL  DEFAULT '1970-01-01 00:00:01',
  `excludePing` BINARY  NULL  DEFAULT '0',
  `PTRignore` BINARY  NULL  DEFAULT '0',
  `PTR` INT(11)  UNSIGNED  NULL  DEFAULT '0',
  `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid_ip_unique` (`ip_addr`,`subnetId`),
  KEY `subnetid` (`subnetId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `ipaddresses` (`id`, `subnetId`, `ip_addr`, `description`, `dns_name`, `state`)
VALUES
	(1,3,'168427779','Server1','server1.cust1.local',2),
	(2,3,'168427780','Server2','server2.cust1.local',2),
	(3,3,'168427781','Server3','server3.cust1.local',3),
	(4,3,'168427782','Server4','server4.cust1.local',3),
	(5,3,'168428021','Gateway',NULL,2),
	(6,4,'168428286','Gateway',NULL,2),
	(7,4,'168428042','Server1','ser1.client2.local',2),
	(8,6,'172037636','DHCP range',NULL,4),
	(9,6,'172037637','DHCP range',NULL,4),
	(10,6,'172037638','DHCP range',NULL,4);


# Dump of table logs
# ------------------------------------------------------------
DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `severity` int(11) DEFAULT NULL,
  `date` varchar(32) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `ipaddr` varchar(64) DEFAULT NULL,
  `command` varchar(128) DEFAULT '0',
  `details` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table requests
# ------------------------------------------------------------
DROP TABLE IF EXISTS `requests`;

CREATE TABLE `requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subnetId` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `ip_addr` varchar(100) DEFAULT NULL,
  `description` varchar(64) DEFAULT NULL,
  `dns_name` varchar(100) DEFAULT NULL,
  `state` INT  NULL  DEFAULT '2',
  `owner` varchar(32) DEFAULT NULL,
  `requester` varchar(128) DEFAULT NULL,
  `comment` text,
  `processed` binary(1) DEFAULT NULL,
  `accepted` binary(1) DEFAULT NULL,
  `adminComment` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table sections
# ------------------------------------------------------------
DROP TABLE IF EXISTS `sections`;

CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` text,
  `masterSection` INT(11)  NULL  DEFAULT '0',
  `permissions` varchar(1024) DEFAULT NULL,
  `strictMode` BINARY(1)  NOT NULL  DEFAULT '1',
  `subnetOrdering` VARCHAR(16)  NULL  DEFAULT NULL,
  `order` INT(3)  NULL  DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  `showVLAN` BOOL  NOT NULL  DEFAULT '0',
  `showVRF` BOOL  NOT NULL  DEFAULT '0',
  `showSupernetOnly` BOOL  NOT NULL  DEFAULT '0',
  `DNS` VARCHAR(128)  NULL  DEFAULT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `sections` (`id`, `name`, `description`, `permissions`)
VALUES
	(1,'Customers','Section for customers','{\"3\":\"1\",\"2\":\"2\"}'),
	(2,'IPv6','Section for IPv6 addresses','{\"3\":\"1\",\"2\":\"2\"}');


# Dump of table settings
# ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `siteTitle` varchar(64) DEFAULT NULL,
  `siteAdminName` varchar(64) DEFAULT NULL,
  `siteAdminMail` varchar(64) DEFAULT NULL,
  `siteDomain` varchar(32) DEFAULT NULL,
  `siteURL` varchar(64) DEFAULT NULL,
  `siteLoginText` varchar(128) DEFAULT NULL,
  `domainAuth` tinyint(1) DEFAULT NULL,
  `enableIPrequests` tinyint(1) DEFAULT NULL,
  `enableVRF` tinyint(1) DEFAULT '1',
  `enableDNSresolving` tinyint(1) DEFAULT NULL,
  `enableFirewallZones` TINYINT(1) NOT NULL DEFAULT '0',
  `firewallZoneSettings` VARCHAR(1024) NOT NULL DEFAULT '{"zoneLength":3,"ipType":{"0":"v4","1":"v6"},"separator":"_","indicator":{"0":"own","1":"customer"},"zoneGenerator":"2","zoneGeneratorType":{"0":"decimal","1":"hex","2":"text"},"deviceType":"3","padding":"on","strictMode":"on","pattern":{"0":"patternFQDN"}}',
  `enablePowerDNS` TINYINT(1)  NULL  DEFAULT '0',
  `powerDNS` TEXT  NULL,
  `enableDHCP` TINYINT(1)  NULL  DEFAULT '0',
  `DHCP` VARCHAR(256) NULL default '{"type":"kea","settings":{"file":"\/etc\/kea\/kea.conf"}}',
  `enableMulticast` TINYINT(1)  NULL  DEFAULT '0',
  `enableNAT` TINYINT(1)  NULL  DEFAULT '1',
  `enableSNMP` TINYINT(1)  NULL  DEFAULT '0',
  `enableThreshold` TINYINT(1)  NULL  DEFAULT '1',
  `enableRACK` TINYINT(1)  NULL  DEFAULT '1',
  `enableLocations` TINYINT(1)  NULL  DEFAULT '1',
  `enablePSTN` TINYINT(1)  NULL  DEFAULT '0',
  `link_field` VARCHAR(32)  NULL  DEFAULT '0',
  `version` varchar(5) DEFAULT NULL,
  `dbverified` BINARY(1)  NOT NULL  DEFAULT '0',
  `donate` tinyint(1) DEFAULT '0',
  `IPfilter` varchar(128) DEFAULT NULL,
  `vlanDuplicate` int(1) DEFAULT '0',
  `vlanMax` INT(8)  NULL  DEFAULT '4096',
  `subnetOrdering` varchar(16) DEFAULT 'subnet,asc',
  `visualLimit` int(2) NOT NULL DEFAULT '0',
  `autoSuggestNetwork` TINYINT(1)  NOT NULL  DEFAULT '0',
  `pingStatus` VARCHAR(32)  NOT NULL  DEFAULT '1800;3600',
  `defaultLang` INT(3)  NULL  DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  `vcheckDate` DATETIME  NULL  DEFAULT NULL ,
  `api` BINARY  NOT NULL  DEFAULT '0',
  `enableChangelog` TINYINT(1)  NOT NULL  DEFAULT '1',
  `scanPingPath` VARCHAR(64)  NULL  DEFAULT '/bin/ping',
  `scanFPingPath` VARCHAR(64)  NULL  DEFAULT '/bin/fping',
  `scanPingType` SET('ping','pear','fping')  NOT NULL  DEFAULT 'ping',
  `scanMaxThreads` INT(4)  NULL  DEFAULT '128',
  `prettyLinks` SET("Yes","No")  NOT NULL  DEFAULT 'No',
  `hiddenCustomFields` text NULL,
  `inactivityTimeout` INT(5)  NOT NULL  DEFAULT '3600',
  `updateTags` TINYINT(1)  NULL  DEFAULT '0',
  `enforceUnique` TINYINT(1)  NULL  DEFAULT '1',
  `authmigrated` TINYINT  NOT NULL  DEFAULT '0',
  `maintaneanceMode` TINYINT(1)  NULL  DEFAULT '0',
  `decodeMAC` TINYINT(1)  NULL  DEFAULT '1',
  `tempShare` TINYINT(1)  NULL  DEFAULT '0',
  `tempAccess` TEXT  NULL,
  `log` SET('Database','syslog', 'both')  NOT NULL  DEFAULT 'Database',
  `subnetView` TINYINT  NOT NULL  DEFAULT '0',
  `enableCircuits` TINYINT(1)  NULL  DEFAULT '1',
  `permissionPropagate` TINYINT(1)  NULL  DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `settings` (`id`, `siteTitle`, `siteAdminName`, `siteAdminMail`, `siteDomain`, `siteURL`, `domainAuth`, `enableIPrequests`, `enableVRF`, `enableDNSresolving`, `version`, `donate`, `IPfilter`, `vlanDuplicate`, `subnetOrdering`, `visualLimit`)
VALUES
	(1, 'phpipam IP address management', 'Sysadmin', 'admin@domain.local', 'domain.local', 'http://yourpublicurl.com', 0, 0, 0, 0, '1.1', 0, 'mac;owner;state;switch;note;firewallAddressObject', 1, 'subnet,asc', 24);


# Dump of table settingsMail
# ------------------------------------------------------------
DROP TABLE IF EXISTS `settingsMail`;

CREATE TABLE `settingsMail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mtype` set('localhost','smtp') NOT NULL DEFAULT 'localhost',
  `msecure` SET('none','ssl','tls')  NOT NULL  DEFAULT 'none',
  `mauth` set('yes','no') NOT NULL DEFAULT 'no',
  `mserver` varchar(128) DEFAULT NULL,
  `mport` int(5) DEFAULT '25',
  `muser` varchar(64) DEFAULT NULL,
  `mpass` varchar(64) DEFAULT NULL,
  `mAdminName` varchar(64) DEFAULT NULL,
  `mAdminMail` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `settingsMail` (`id`, `mtype`)
VALUES
	(1, 'localhost');


# Dump of table subnets
# ------------------------------------------------------------
DROP TABLE IF EXISTS `subnets`;

CREATE TABLE `subnets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subnet` VARCHAR(255) NULL  DEFAULT NULL,
  `mask` VARCHAR(3) NULL DEFAULT NULL,
  `sectionId` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `description` text,
  `linked_subnet` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL,
  `vrfId` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `masterSubnetId` INT(11)  UNSIGNED  NOT NULL default 0,
  `allowRequests` tinyint(1) DEFAULT '0',
  `vlanId` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `showName` tinyint(1) DEFAULT '0',
  `device` INT  UNSIGNED  NULL  DEFAULT '0',
  `permissions` varchar(1024) DEFAULT NULL,
  `pingSubnet` BOOL NULL  DEFAULT '0',
  `discoverSubnet` BINARY(1)  NULL  DEFAULT '0',
  `resolveDNS` TINYINT(1)  NULL  DEFAULT '0',
  `DNSrecursive` TINYINT(1)  NULL  DEFAULT '0',
  `DNSrecords` TINYINT(1)  NULL  DEFAULT '0',
  `nameserverId` INT(11) NULL DEFAULT '0',
  `scanAgent` INT(11)  DEFAULT NULL,
  `isFolder` BOOL NULL  DEFAULT '0',
  `isFull` TINYINT(1)  NULL  DEFAULT '0',
  `state` INT(3)  NULL  DEFAULT '2',
  `threshold` int(3)  NULL  DEFAULT 0,
  `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  `lastScan` TIMESTAMP  NULL,
  `lastDiscovery` TIMESTAMP  NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `subnets` (`id`, `subnet`, `mask`, `sectionId`, `description`, `vrfId`, `masterSubnetId`, `allowRequests`, `vlanId`, `showName`, `permissions`, `isFolder`)
VALUES
	(1,'336395549904799703390415618052362076160','64',2,'Private subnet 1',0,'0',1,1,1,'{\"3\":\"1\",\"2\":\"2\"}',0),
	(2,'168427520','16','1','Business customers',0,'0',1,0,1,'{\"3\":\"1\",\"2\":\"2\"}',0),
	(3,'168427776','24','1','Customer 1',0,'2',1,0,1,'{\"3\":\"1\",\"2\":\"2\"}',0),
	(4,'168428032','24','1','Customer 2',0,'2',1,0,1,'{\"3\":\"1\",\"2\":\"2\"}',0),
	(5, '0', '', 1, 'My folder', 0, 0, 0, 0, 0, '{\"3\":\"1\",\"2\":\"2\"}', 1),
	(6, '172037632', '24', 1, 'DHCP range', 0, 5, 0, 0, 1, '{\"3\":\"1\",\"2\":\"2\"}', 0);


# Dump of table devices
# ------------------------------------------------------------
DROP TABLE IF EXISTS `devices`;

CREATE TABLE `devices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hostname` varchar(100) DEFAULT NULL,
  `ip_addr` varchar(100) DEFAULT NULL,
  `type` int(2) DEFAULT '0',
  `description` varchar(256) DEFAULT NULL,
  `sections` varchar(1024) DEFAULT NULL,
  `snmp_community` varchar(100) DEFAULT NULL,
  `snmp_version` set('0','1','2') DEFAULT '0',
  `snmp_port` mediumint(5) unsigned DEFAULT '161',
  `snmp_timeout` mediumint(5) unsigned DEFAULT '1000000',
  `snmp_queries` VARCHAR(128)  NULL  DEFAULT NULL,
  `rack` int(11) unsigned NULL DEFAULT null,
  `rack_start` int(11) unsigned DEFAULT null,
  `rack_size` int(11) unsigned DEFAULT null,
  `location` INT(11)  unsigned  NULL  DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table userGroups
# ------------------------------------------------------------
DROP TABLE IF EXISTS `userGroups`;

CREATE TABLE `userGroups` (
  `g_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `g_name` varchar(32) DEFAULT NULL,
  `g_desc` varchar(1024) DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`g_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `userGroups` (`g_id`, `g_name`, `g_desc`)
VALUES
	(2,'Operators','default Operator group'),
	(3,'Guests','default Guest group (viewers)');


# Dump of table users
# ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `authMethod` INT(2)  NULL  DEFAULT 1,
  `password` CHAR(128)  COLLATE utf8_bin DEFAULT NULL,
  `groups` varchar(1024) COLLATE utf8_bin DEFAULT NULL,
  `role` text CHARACTER SET utf8,
  `real_name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `email` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `pdns` SET('Yes','No')  NULL  DEFAULT 'No' ,
  `editVlan` SET('Yes','No')  NULL  DEFAULT 'No',
  `editCircuits` SET('Yes','No')  NULL  DEFAULT 'No',
  `pstn` INT(1)  NULL  DEFAULT '1',
  `domainUser` binary(1) DEFAULT '0',
  `widgets` VARCHAR(1024)  NULL  DEFAULT 'statistics;favourite_subnets;changelog;top10_hosts_v4',
  `lang` INT(11) UNSIGNED  NULL  DEFAULT '9',
  `favourite_subnets` VARCHAR(1024)  NULL  DEFAULT NULL,
  `mailNotify` SET('Yes','No')  NULL  DEFAULT 'No',
  `mailChangelog` SET('Yes','No')  NULL  DEFAULT 'No',
  `passChange` SET('Yes','No')  NOT NULL  DEFAULT 'No',
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  `lastLogin` TIMESTAMP  NULL,
  `lastActivity` TIMESTAMP  NULL,
  `compressOverride` SET('default','Uncompress') NOT NULL DEFAULT 'default',
  `hideFreeRange` tinyint(1) DEFAULT '0',
  `menuType` SET('Static','Dynamic')  NULL  DEFAULT 'Dynamic',
  `menuCompact` TINYINT  NULL  DEFAULT '1',
  `token` VARCHAR(24)  NULL  DEFAULT NULL,
  `token_valid_until` DATETIME  NULL,
  PRIMARY KEY (`username`),
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/* insert default values */
INSERT INTO `users` (`id`, `username`, `password`, `groups`, `role`, `real_name`, `email`, `domainUser`,`widgets`, `passChange`)
VALUES
	(1,'Admin',X'243624726F756E64733D33303030244A51454536644C394E70766A6546733424524B3558336F6132382E557A742F6835564166647273766C56652E3748675155594B4D58544A5573756438646D5766507A5A51506252626B38784A6E314B797974342E64576D346E4A4959684156326D624F5A33672E',X'','Administrator','phpIPAM Admin','admin@domain.local',X'30','statistics;favourite_subnets;changelog;access_logs;error_logs;top10_hosts_v4', 'Yes');


# Dump of table lang
# ------------------------------------------------------------
DROP TABLE IF EXISTS `lang`;

CREATE TABLE `lang` (
  `l_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `l_code` varchar(12) NOT NULL DEFAULT '',
  `l_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`l_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `lang` (`l_id`, `l_code`, `l_name`)
VALUES
	(1, 'en_GB.UTF-8', 'English'),
	(2, 'sl_SI.UTF-8', 'Slovenščina'),
	(3, 'fr_FR.UTF-8', 'Français'),
	(4, 'nl_NL.UTF-8', 'Nederlands'),
	(5, 'de_DE.UTF-8', 'Deutsch'),
	(6, 'pt_BR.UTF-8', 'Brazil'),
	(7,	'es_ES.UTF-8', 'Español'),
	(8, 'cs_CZ.UTF-8', 'Czech'),
	(9, 'en_US.UTF-8', 'English (US)'),
  (10,'ru_RU.UTF-8', 'Russian'),
  (11,'zh_CN.UTF-8', 'Chinese');


# Dump of table vlans
# ------------------------------------------------------------
DROP TABLE IF EXISTS `vlans`;

CREATE TABLE `vlans` (
  `vlanId` int(11) NOT NULL AUTO_INCREMENT,
  `domainId` INT  NOT NULL  DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `number` int(4) DEFAULT NULL,
  `description` text,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vlanId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `vlans` (`vlanId`, `name`, `number`, `description`)
VALUES
	(1,'IPv6 private 1',2001,'IPv6 private 1 subnets'),
	(2,'Servers DMZ',4001,'DMZ public');


# Dump of table vlanDomains
# ------------------------------------------------------------
DROP TABLE IF EXISTS `vlanDomains`;

CREATE TABLE `vlanDomains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `description` text,
  `permissions` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `vlanDomains` (`id`, `name`, `description`, `permissions`)
VALUES
	(1, 'default', 'default L2 domain', NULL);


# Dump of table vrf
# ------------------------------------------------------------
DROP TABLE IF EXISTS `vrf`;

CREATE TABLE `vrf` (
  `vrfId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '',
  `rd` varchar(32) DEFAULT NULL,
  `description` varchar(256) DEFAULT NULL,
  `sections` VARCHAR(128)  NULL  DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vrfId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dump of table nameservers
# ------------------------------------------------------------
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
/* insert default values */
INSERT INTO `nameservers` (`name`, `namesrv1`, `description`, `permissions`)
VALUES
	('Google NS', '8.8.8.8;8.8.4.4', 'Google public nameservers', '1;2');



# Dump of table api
# ------------------------------------------------------------
DROP TABLE IF EXISTS `api`;

CREATE TABLE `api` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(32) NOT NULL DEFAULT '',
  `app_code` varchar(32) NULL DEFAULT '',
  `app_permissions` int(1) DEFAULT '1',
  `app_comment` TEXT  NULL,
  `app_security` SET('crypt','ssl','user','none')  NOT NULL  DEFAULT 'ssl',
  `app_lock` INT(1)  NOT NULL  DEFAULT '0',
  `app_lock_wait` INT(4)  NOT NULL  DEFAULT '30',
  `app_nest_custom_fields` TINYINT(1)  NULL  DEFAULT '0',
  `app_show_links` TINYINT(1)  NULL  DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table changelog
# ------------------------------------------------------------
DROP TABLE IF EXISTS `changelog`;

CREATE TABLE `changelog` (
  `cid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ctype` set('ip_addr','subnet','section') NOT NULL DEFAULT '',
  `coid` int(11) unsigned NOT NULL,
  `cuser` int(11) unsigned NOT NULL,
  `caction` set('add','edit','delete','truncate','resize','perm_change') NOT NULL DEFAULT 'edit',
  `cresult` set('error','success') NOT NULL DEFAULT '',
  `cdate` datetime NOT NULL,
  `cdiff` varchar(2048) DEFAULT NULL,
  PRIMARY KEY (`cid`),
  KEY `coid` (`coid`),
  KEY `ctype` (`ctype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table widgets
# ------------------------------------------------------------
DROP TABLE IF EXISTS `widgets`;

CREATE TABLE `widgets` (
  `wid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wtitle` varchar(64) NOT NULL DEFAULT '',
  `wdescription` varchar(1024) DEFAULT NULL,
  `wfile` varchar(64) NOT NULL DEFAULT '',
  `wparams` varchar(1024) DEFAULT NULL,
  `whref` set('yes','no') NOT NULL DEFAULT 'no',
  `wsize` SET('4','6','8','12') NOT NULL DEFAULT '6',
  `wadminonly` set('yes','no') NOT NULL DEFAULT 'no',
  `wactive` set('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`wid`)
) DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `widgets` (`wid`, `wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`)
VALUES
	(1, 'Statistics', 'Shows some statistics on number of hosts, subnets', 'statistics', NULL, 'no', '4', 'no', 'yes'),
	(2, 'Favourite subnets', 'Shows 5 favourite subnets', 'favourite_subnets', NULL, 'yes', '8', 'no', 'yes'),
	(3, 'Top 10 IPv4 subnets by number of hosts', 'Shows graph of top 10 IPv4 subnets by number of hosts', 'top10_hosts_v4', NULL, 'yes', '6', 'no', 'yes'),
	(4, 'Top 10 IPv6 subnets by number of hosts', 'Shows graph of top 10 IPv6 subnets by number of hosts', 'top10_hosts_v6', NULL, 'yes', '6', 'no', 'yes'),
	(5, 'Top 10 IPv4 subnets by usage percentage', 'Shows graph of top 10 IPv4 subnets by usage percentage', 'top10_percentage', NULL, 'yes', '6', 'no', 'yes'),
	(6, 'Last 5 change log entries', 'Shows last 5 change log entries', 'changelog', NULL, 'yes', '12', 'no', 'yes'),
	(7, 'Active IP addresses requests', 'Shows list of active IP address request', 'requests', NULL, 'yes', '6', 'yes', 'yes'),
	(8, 'Last 5 informational logs', 'Shows list of last 5 informational logs', 'access_logs', NULL, 'yes', '6', 'yes', 'yes'),
	(9, 'Last 5 warning / error logs', 'Shows list of last 5 warning and error logs', 'error_logs', NULL, 'yes', '6', 'yes', 'yes'),
	(10,'Tools menu', 'Shows quick access to tools menu', 'tools', NULL, 'yes', '6', 'no', 'yes'),
	(11,'IP Calculator', 'Shows IP calculator as widget', 'ipcalc', NULL, 'yes', '6', 'no', 'yes'),
	(12,'IP Request', 'IP Request widget', 'iprequest', NULL, 'no', '6', 'no', 'yes'),
	(13,'Threshold', 'Shows threshold usage for top 5 subnets', 'threshold', NULL, 'yes', '6', 'no', 'yes'),
	(14,'Inactive hosts', 'Shows list of inactive hosts for defined period', 'inactive-hosts', 86400, 'yes', '6', 'yes', 'yes'),
	(15, 'Locations', 'Shows map of locations', 'locations', NULL, 'yes', '6', 'no', 'yes');




# Dump of table deviceTypes
# ------------------------------------------------------------
DROP TABLE IF EXISTS `deviceTypes`;

CREATE TABLE `deviceTypes` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tname` varchar(128) DEFAULT NULL,
  `tdescription` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `deviceTypes` (`tid`, `tname`, `tdescription`)
VALUES
	(1, 'Switch', 'Switch'),
	(2, 'Router', 'Router'),
	(3, 'Firewall', 'Firewall'),
	(4, 'Hub', 'Hub'),
	(5, 'Wireless', 'Wireless'),
	(6, 'Database', 'Database'),
	(7, 'Workstation', 'Workstation'),
	(8, 'Laptop', 'Laptop'),
	(9, 'Other', 'Other');


# Dump of table loginAttempts
# ------------------------------------------------------------
DROP TABLE IF EXISTS `loginAttempts`;

CREATE TABLE `loginAttempts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip` varchar(128) NOT NULL DEFAULT '',
  `count` int(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table usersAuthMethod
# ------------------------------------------------------------
DROP TABLE IF EXISTS `usersAuthMethod`;

CREATE TABLE `usersAuthMethod` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` set('local','http','AD','LDAP','NetIQ','Radius','SAML2') NOT NULL DEFAULT 'local',
  `params` varchar(1024) DEFAULT NULL,
  `protected` set('Yes','No') NOT NULL DEFAULT 'Yes',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `usersAuthMethod` (`id`, `type`, `params`, `protected`, `description`)
VALUES
	(1, 'local', NULL, 'Yes', 'Local database'),
	(2, 'http', NULL, 'Yes', 'Apache authentication');


# Dump of table ipTags
# ------------------------------------------------------------
DROP TABLE IF EXISTS `ipTags`;

CREATE TABLE `ipTags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `showtag` tinyint(4) DEFAULT '1',
  `bgcolor` varchar(7) DEFAULT '#000',
  `fgcolor` varchar(7) DEFAULT '#fff',
  `compress` SET('No','Yes')  NOT NULL  DEFAULT 'No',
  `locked` set('No','Yes') NOT NULL DEFAULT 'No',
  `updateTag` TINYINT(1)  NULL  DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/* insert default values */
INSERT INTO `ipTags` (`id`, `type`, `showtag`, `bgcolor`, `fgcolor`, `compress`, `locked`, `updateTag`)
VALUES
	(1, 'Offline', 1, '#f59c99', '#ffffff', 'No', 'Yes', 1),
	(2, 'Used', 0, '#a9c9a4', '#ffffff', 'No', 'Yes', 1),
	(3, 'Reserved', 1, '#9ac0cd', '#ffffff', 'No', 'Yes', 1),
	(4, 'DHCP', 1, '#c9c9c9', '#ffffff', 'Yes', 'Yes', 1);


# Dump of table firewallZones
# ------------------------------------------------------------
DROP TABLE IF EXISTS `firewallZones`;

CREATE TABLE `firewallZones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `generator` tinyint(1) NOT NULL,
  `length` int(2) DEFAULT NULL,
  `padding` tinyint(1) DEFAULT NULL,
  `zone` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `indicator` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `permissions` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


# Dump of table firewallZoneMapping
# ------------------------------------------------------------
DROP TABLE IF EXISTS `firewallZoneMapping`;

CREATE TABLE `firewallZoneMapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zoneId` int(11) unsigned NOT NULL,
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `deviceId` int(11) unsigned DEFAULT NULL,
  `interface` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `devId_idx` (`deviceId`),
  CONSTRAINT `devId` FOREIGN KEY (`deviceId`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


# Dump of table firewallZoneMapping
# ------------------------------------------------------------
DROP TABLE IF EXISTS `firewallZoneSubnet`;

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


# Dump of table scanAgents
# ------------------------------------------------------------
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
	(1, 'localhost', 'Scanning from local machine', 'direct');


# Dump of table nat
# ------------------------------------------------------------
DROP TABLE IF EXISTS `nat`;

CREATE TABLE `nat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `type` set('source','static','destination') DEFAULT 'source',
  `src` text,
  `dst` text,
  `src_port` int(5) DEFAULT NULL,
  `dst_port` INT(5) DEFAULT NULL,
  `device` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table racks
# ------------------------------------------------------------
DROP TABLE IF EXISTS `racks`;

CREATE TABLE `racks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `size` int(2) DEFAULT NULL,
  `location` INT(11)  UNSIGNED  NULL  DEFAULT NULL,
  `row` INT(11)  NOT NULL  DEFAULT '1',
  `hasBack` TINYINT(1)  NOT NULL  DEFAULT '0',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table locations
# ------------------------------------------------------------
DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` text,
  `address` VARCHAR(128)  NULL  DEFAULT NULL,
  `lat` varchar(12) DEFAULT NULL,
  `long` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



    # Dump of table pstnPrefixes
# ------------------------------------------------------------
DROP TABLE IF EXISTS `pstnPrefixes`;

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



# Dump of table pstnNumbers
# ------------------------------------------------------------
DROP TABLE IF EXISTS `pstnNumbers`;

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



# Dump of table circuitProviders
# ------------------------------------------------------------
CREATE TABLE `circuitProviders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `description` text,
  `contact` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table circuits
# ------------------------------------------------------------
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



# Dump of table -- for autofix comment, leave as it is
# ------------------------------------------------------------


# update version
# ------------------------------------------------------------
UPDATE `settings` set `version` = '1.31';
