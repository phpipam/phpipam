/* Update version */
UPDATE `settings` set `version` = '1.15';
/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* reset iptags */
DROP TABLE IF EXISTS `ipTags`;

CREATE TABLE `ipTags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `showtag` tinyint(4) DEFAULT '1',
  `bgcolor` varchar(7) DEFAULT '#000',
  `fgcolor` varchar(7) DEFAULT '#fff',
  `locked` set('No','Yes') NOT NULL DEFAULT 'No',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/* insert default values */
INSERT INTO `ipTags` (`id`, `type`, `showtag`, `bgcolor`, `fgcolor`, `locked`)
VALUES
	(1, 'Offline', 1, '#f59c99', '#ffffff', 'Yes'),
	(2, 'Used', 0, '#a9c9a4', '#ffffff', 'Yes'),
	(3, 'Reserved', 1, '#9ac0cd', '#ffffff', 'Yes'),
	(4, 'DHCP', 1, '#c9c9c9', '#ffffff', 'Yes');

/* update ipaddresses */
UPDATE `ipaddresses` SET `state` = 1 WHERE `state` > 3;
UPDATE `ipaddresses` SET `state` = 4 WHERE `state` = 3;
UPDATE `ipaddresses` SET `state` = 3 WHERE `state` = 2;
UPDATE `ipaddresses` SET `state` = 2 WHERE `state` = 1;
UPDATE `ipaddresses` SET `state` = 1 WHERE `state` = 0;

/* change tag */
ALTER TABLE `ipaddresses` CHANGE `state` `state` INT(3)  NULL  DEFAULT '2';

/* add autoSuggestNetwork flag and permitRWAvlan */
ALTER TABLE `settings` ADD `autoSuggestNetwork` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `visualLimit`;
ALTER TABLE `settings` ADD `permitUserVlanCreate` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `autoSuggestNetwork`;

/* add section DNS */
ALTER TABLE `sections` ADD `DNS` VARCHAR(128)  NULL  DEFAULT NULL  AFTER `showVRF`;

/* mark subnet as full */
ALTER TABLE `subnets` ADD `isFull` TINYINT(1)  NULL  DEFAULT '0'  AFTER `isFolder`;

/* add state */
ALTER TABLE `subnets` ADD `state` INT(3)  NULL  DEFAULT '2'  AFTER `isFull`;
