/* Update version */
UPDATE `settings` set `version` = '1.13';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;

/* add radius auth */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','Radius')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';

/* add temp access */
ALTER TABLE `settings` ADD `tempAccess` TEXT  NULL  AFTER `authmigrated`;
