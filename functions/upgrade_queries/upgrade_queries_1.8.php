<?php

#
# Version 1.8 queries
#
$upgrade_queries["1.8.43"]   = [];
$upgrade_queries["1.8.43"][] = "-- Version update";
$upgrade_queries["1.8.43"][] = "UPDATE `settings` set `version` = '1.8';";

$upgrade_queries["1.8.44"]   = [];
$upgrade_queries["1.8.44"][] = "ALTER TABLE `lang` CHANGE `l_name` `l_name` VARCHAR(64) NULL DEFAULT NULL;";
$upgrade_queries["1.8.44"][] = "-- Database version bump";
$upgrade_queries["1.8.44"][] = "UPDATE `settings` SET `dbversion` = '44';";

$upgrade_queries["1.8.46"]   = [];
$upgrade_queries["1.8.46"][] = "ALTER TABLE `settings` ADD `rackAllowOverlap` INT(1) NOT NULL DEFAULT 0 AFTER `enableRACK`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `settings` ADD `rackImageFormat` ENUM('png','svg') NOT NULL DEFAULT 'svg' AFTER `enableRACK`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `devices` ADD `rack_deep` TINYINT(1) NOT NULL DEFAULT 0 AFTER `rack_size`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `deviceTypes` ADD `fgcolor` varchar(7) NULL DEFAULT '#000' AFTER `tdescription`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `deviceTypes` ADD `bgcolor` varchar(7) NULL DEFAULT '#E6E6E6' AFTER `tdescription`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `racks` ADD `subrack` TINYINT(1) NOT NULL DEFAULT 0 AFTER `size`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `rackContents` ADD `subrackId` INT(11) NOT NULL DEFAULT 0 AFTER `rack_size`;";
$upgrade_queries["1.8.46"][] = "ALTER TABLE `rackContents` ADD `rack_deep` TINYINT(11) NOT NULL DEFAULT 0 AFTER `rack_size`;";
$upgrade_queries["1.8.46"][] = "-- Database version bump";
$upgrade_queries["1.8.46"][] = "UPDATE `settings` SET `dbversion` = '46';";
