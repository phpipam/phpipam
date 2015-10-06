/* Update version */
UPDATE `settings` set `version` = '1.19';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Czech traslation */
INSERT INTO `lang` (`l_code`, `l_name`) VALUES ('cs_CZ', 'Czech');

/* drop old ns structure */
ALTER TABLE `nameservers` DROP `namesrv2`;
ALTER TABLE `nameservers` DROP `namesrv3`;

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
ALTER TABLE `scanAgents` ADD `type` SET('direct','api','mysql')  CHARACTER SET utf8  NOT NULL DEFAULT '';
ALTER TABLE `scanAgents` ADD `code` VARCHAR(32)  NULL  DEFAULT NULL;
ALTER TABLE `scanAgents` ADD `last_access` DATETIME  NULL;
/* unique key */
ALTER TABLE `scanAgents` ADD UNIQUE INDEX (`code`);


INSERT INTO `scanAgents` (`id`, `name`, `description`, `type`)
VALUES
	(1, 'locahost', 'Scanning from local machine', 'direct');


/* set all to localhost */
update `subnets` set `scanAgent` = "1" WHERE `pingSubnet` = 1;
update `subnets` set `scanAgent` = "1" WHERE `discoverSubnet` = 1;