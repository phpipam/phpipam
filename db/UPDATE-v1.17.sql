/* Update version */
UPDATE `settings` set `version` = '1.17';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;


/* add tokens */
ALTER TABLE `users` ADD `token` VARCHAR(24)  NULL  DEFAULT NULL  AFTER `printLimit`;
ALTER TABLE `users` ADD `token_valid_until` DATETIME  NULL  AFTER `token`;

/* add scan agents */
ALTER TABLE `subnets` ADD `scanAgent` int(11) DEFAULT NULL  AFTER `discoverSubnet`;

/* powerDNS integration */
ALTER TABLE `settings` ADD `enablePowerDNS` TINYINT(1)  NULL  DEFAULT '0'  AFTER `enableDNSresolving`;
ALTER TABLE `settings` ADD `powerDNS` TEXT  NULL  AFTER `enablePowerDNS`;

ALTER TABLE `subnets` ADD `DNSrecursive` TINYINT(1)  NULL  DEFAULT '0'  AFTER `discoverSubnet`;
