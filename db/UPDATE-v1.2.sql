/* Update version */
UPDATE `settings` set `version` = '1.2';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* add subnetView Setting */
ALTER TABLE `settings` ADD `subnetView` TINYINT  NOT NULL  DEFAULT '0';