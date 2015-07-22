/* Update version */
UPDATE `settings` set `version` = '1.18';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* powerDNS integration */
ALTER TABLE `ipaddresses` ADD `PTRignore` BINARY(1)  NULL  DEFAULT '0'  AFTER `excludePing`;
ALTER TABLE `ipaddresses` ADD `PTR` INT(11)  UNSIGNED  NULL  DEFAULT '0'  AFTER `PTRignore`;

ALTER TABLE `subnets` ADD `DNSrecords` TINYINT(1)  NULL  DEFAULT '0'  AFTER `DNSrecursive`;

/* log destination */
ALTER TABLE `settings` ADD `log` SET('Database','syslog')  NOT NULL  DEFAULT 'Database'  AFTER `tempAccess`;
