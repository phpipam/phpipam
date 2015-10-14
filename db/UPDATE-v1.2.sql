/* Update version */
UPDATE `settings` set `version` = '1.2';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* add subnetView Setting */
ALTER TABLE `settings` ADD `subnetView` TINYINT  NOT NULL  DEFAULT '0';

/* add 'user' to app_security set */
ALTER TABLE `api` CHANGE `app_security` `app_security` SET('crypt','ssl','user','none')  NOT NULL  DEFAULT 'ssl'
