/* Update version */
UPDATE `settings` set `version` = '1.2';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0, `donate` = 0;

/* add subnetView Setting */
ALTER TABLE `settings` ADD `subnetView` TINYINT  NOT NULL  DEFAULT '0';

/* add 'user' to app_security set */
ALTER TABLE `api` CHANGE `app_security` `app_security` SET('crypt','ssl','user','none')  NOT NULL  DEFAULT 'ssl';

/* add english_US language */
INSERT INTO `lang` (`l_id`, `l_code`, `l_name`) VALUES (NULL, 'en_US', 'English (US)');

/* update the firewallZones table to suit the new layout */
ALTER TABLE `firewallZones` DROP COLUMN `vlanId`, DROP COLUMN `stacked`;

/* add a new table to store subnetId and zoneId */
CREATE TABLE `firewallZoneSubnet` (
  `zoneId` INT NOT NULL,
  `subnetId` INT(11) NOT NULL,
  INDEX `fk_zoneId_idx` (`zoneId` ASC),
  INDEX `fk_subnetId_idx` (`subnetId` ASC),
  CONSTRAINT `fk_zoneId`
    FOREIGN KEY (`zoneId`)
    REFERENCES `firewallZones` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subnetId`
    FOREIGN KEY (`subnetId`)
    REFERENCES `subnets` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION);

/* copy old subnet IDs from firewallZones table into firewallZoneSubnet */
INSERT INTO `firewallZoneSubnet` (zoneId,subnetId) SELECT id AS zoneId,subnetId from `firewallZones`;

/* remove the field subnetId from firewallZones, it's not longer needed */
ALTER TABLE `firewallZones` DROP COLUMN `subnetId`;

/* add fk constrain and index to firewallZoneMappings to automatically remove a mapping if a device has been deleted */
ALTER TABLE `firewallZoneMapping` ADD INDEX `devId_idx` (`deviceId` ASC);
ALTER TABLE `firewallZoneMapping` ADD CONSTRAINT `devId` FOREIGN KEY (`deviceId`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

/* add firewallAddresObject field to the ipaddresses table to store fw addr. obj. names permanently */
ALTER TABLE `ipaddresses` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL AFTER `PTR`;

/* activate the firewallAddressObject IP field filter on default */
UPDATE `settings` SET IPfilter = CONCAT(IPfilter,';firewallAddressObject');

/* add a column for subnet firewall address objects */
ALTER TABLE `subnets` ADD COLUMN `firewallAddressObject` VARCHAR(100) NULL DEFAULT NULL AFTER `description`;

/* add http auth method */
ALTER TABLE `usersAuthMethod` CHANGE `type` `type` SET('local','AD','LDAP','NetIQ','Radius','http')  CHARACTER SET utf8  NOT NULL  DEFAULT 'local';

INSERT INTO `usersAuthMethod` (`type`, `params`, `protected`, `description`)
VALUES ('http', NULL, 'Yes', 'Apache authentication');

/* allow powerdns record management for user */
ALTER TABLE `users` ADD `pdns` SET('Yes','No')  NULL  DEFAULT 'No'  AFTER `email`;

/* add Ip request widget */
INSERT INTO `widgets` (`wtitle`, `wdescription`, `wfile`, `wparams`, `whref`, `wsize`, `wadminonly`, `wactive`)
VALUES
('IP Request', 'IP Request widget', 'iprequest', NULL, 'no', '6', 'no', 'yes');

/* change mask size */
ALTER TABLE `subnets` CHANGE `mask` `mask` VARCHAR(3)  CHARACTER SET utf8  NULL  DEFAULT NULL;

/* add section to vrf */
ALTER TABLE `vrf` ADD `sections` VARCHAR(128)  NULL  DEFAULT NULL  AFTER `description`;
