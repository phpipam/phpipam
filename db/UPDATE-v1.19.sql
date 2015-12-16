/* Update version */
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
ALTER TABLE `settings`  ADD `siteLoginText` varchar(128) NULL DEFAULT NULL AFTER `siteURL`;

/* add unique ip+subnet requirement */
ALTER TABLE `ipaddresses` ADD UNIQUE INDEX `sid_ip_unique` (`ip_addr`, `subnetId`);

/* add tag to ip requests */
ALTER TABLE `requests` ADD `state` INT  NULL  DEFAULT '2'  AFTER `dns_name`;


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
ADD COLUMN `enableFirewallZones` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '' AFTER `enableDNSresolving`,
ADD COLUMN `firewallZoneSettings` VARCHAR(1024) NOT NULL DEFAULT '{"zoneLength":3,"ipType":{"0":"v4","1":"v6"},"separator":"_","indicator":{"0":"own","1":"customer"},"zoneGenerator":"2","zoneGeneratorType":{"0":"decimal","1":"hex","2":"text"},"deviceType":"3","padding":"on","strictMode":"on"}' COMMENT '' AFTER `enableFirewallZones`;
