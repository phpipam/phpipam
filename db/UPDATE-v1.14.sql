/* Update version */
UPDATE `settings` set `version` = '1.14';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add tempShare */
ALTER TABLE `settings` ADD `tempShare` TINYINT(1)  NULL  DEFAULT '0'  AFTER `authmigrated`;

/* move display Settings to user */
ALTER TABLE `users` ADD `dhcpCompress` BOOL  NOT NULL  DEFAULT '0' AFTER `lastActivity`;
ALTER TABLE `users` ADD `hideFreeRange` tinyint(1) DEFAULT '0' AFTER `dhcpCompress`;
ALTER TABLE `users` ADD `printLimit` int(4) unsigned DEFAULT '30' AFTER `hideFreeRange`;

/* drop old display settings */
ALTER TABLE `settings` DROP `dhcpCompress`;
ALTER TABLE `settings` DROP `hideFreeRange`;
ALTER TABLE `settings` DROP `printLimit`;
