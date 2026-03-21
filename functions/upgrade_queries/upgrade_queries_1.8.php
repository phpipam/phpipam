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

$upgrade_queries["1.8.45"]   = [];
$upgrade_queries["1.8.45"][] = "ALTER TABLE `api` ADD `app_lock_type` enum('Auto','File','MySQL','Disabled') NOT NULL DEFAULT 'Auto' AFTER `app_security`;";
$upgrade_queries["1.8.45"][] = "ALTER TABLE `api` DROP COLUMN `app_lock`;";
$upgrade_queries["1.8.45"][] = "CREATE TABLE `apiLock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$upgrade_queries["1.8.45"][] = "INSERT INTO `apiLock` (`id`, `description`) VALUES (1, 'API POST lock');";
$upgrade_queries["1.8.45"][] = "-- Database version bump";
$upgrade_queries["1.8.45"][] = "UPDATE `settings` SET `dbversion` = '45';";

$upgrade_queries["1.8.46"]   = [];
$upgrade_queries["1.8.46"][] = "ALTER TABLE `changelog` CHANGE `ctype` `ctype` ENUM('ip_addr','subnet','section','devices','vrf','vlans') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";
$upgrade_queries["1.8.46"][] = "-- Database version bump";
$upgrade_queries["1.8.46"][] = "UPDATE `settings` SET `dbversion` = '46';";
