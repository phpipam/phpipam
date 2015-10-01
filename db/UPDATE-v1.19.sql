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
