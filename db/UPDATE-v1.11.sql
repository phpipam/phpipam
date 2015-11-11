/* Update from v 1.1 to 1.11 */
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
ALTER TABLE `users` ADD `authMethod` INT(2)  NULL  DEFAULT 1  AFTER `username`;
/* update all domain users to use domain auth, settings will be migrated after first successfull login */
UPDATE `users` set `authMethod`=2 where `domainUser` = 1;


/* add ping types */
ALTER TABLE `settings` ADD `scanFPingPath` VARCHAR(64)  NULL  DEFAULT '/bin/fping'  AFTER `scanPingPath`;
ALTER TABLE `settings` ADD `scanPingType` SET('ping','pear','fping')  NOT NULL  DEFAULT 'ping'  AFTER `scanFPingPath`;


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
ALTER TABLE `vlans` ADD `domainId` INT  NOT NULL  DEFAULT '1'  AFTER `vlanId`;


/* add last login for users */
ALTER TABLE `users` ADD `lastLogin` TIMESTAMP  NULL AFTER `editDate`;
ALTER TABLE `users` ADD `lastActivity` TIMESTAMP  NULL AFTER `lastLogin`;

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
