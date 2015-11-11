/* Update version */
UPDATE `settings` set `version` = '1.16';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add compress tag for ranges */
ALTER TABLE `ipTags` ADD `compress` SET('No','Yes')  NOT NULL  DEFAULT 'No'  AFTER `locked`;
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
ALTER TABLE `settingsDomain` ENGINE = InnoDB;
ALTER TABLE `settingsMail` ENGINE = InnoDB;
ALTER TABLE `subnets` ENGINE = InnoDB;
ALTER TABLE `userGroups` ENGINE = InnoDB;
ALTER TABLE `users` ENGINE = InnoDB;
ALTER TABLE `usersAuthMethod` ENGINE = InnoDB;
ALTER TABLE `vlanDomains` ENGINE = InnoDB;
ALTER TABLE `vlans` ENGINE = InnoDB;
ALTER TABLE `vrf` ENGINE = InnoDB;
ALTER TABLE `widgets` ENGINE = InnoDB;

/* add new widgets */
INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`)
VALUES
	('Tools menu', 'Shows quick access to tools menu', 'tools', NULL, 'yes', '6', 'no', 'yes'),
	('IP Calculator', 'Shows IP calculator as widget', 'ipcalc', NULL, 'yes', '6', 'no', 'yes');

/* add security type and permit empty app code */
ALTER TABLE `api` ADD `app_security` SET('crypt','ssl','none')  NOT NULL  DEFAULT 'ssl'  AFTER `app_comment`;
ALTER TABLE `api` CHANGE `app_code` `app_code` VARCHAR(32) NULL  DEFAULT '';
