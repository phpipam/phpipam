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

/* link subnet to device */
ALTER TABLE `subnets` ADD `device` INT  UNSIGNED  NULL  DEFAULT '0'  AFTER `showName`;

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
ALTER TABLE `subnets` ADD `nameserverId` int(11) NULL DEFAULT '0' AFTER `DNSrecursive`;
