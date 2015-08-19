/* Update version */
UPDATE `settings` set `version` = '1.18';
/* reset db check field */
UPDATE `settings` set `dbverified` = 0;


/* add table for recursive nameservers to subnets */
DROP TABLE IF EXISTS `nameservers`;

CREATE TABLE `nameservers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `namesrv1` varchar(255) DEFAULT NULL,
  `namesrv2` varchar(255) DEFAULT NULL,
  `namesrv3` varchar(255) DEFAULT NULL,
  `description` text,`
  `permissions` varchar(128) DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`nameserverId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* add reference to nameservers in subnets table */
ALTER TABLE `subnets` ADD `nameserverId` int(11) NULL DEFAULT '0' AFTER `DNSrecursive`;

/* add bool to show/hide nameservers per section */
ALTER TABLE `sections` ADD `ShowNameservers` BOOL NOT NULL DEFAULT '0' AFTER `ShowVRF`;
