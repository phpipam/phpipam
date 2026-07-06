<?php

#
# Version 1.9 queries
#
$upgrade_queries["1.9.46"]   = [];
$upgrade_queries["1.9.46"][] = "-- Version update";
$upgrade_queries["1.9.46"][] = "UPDATE `settings` set `version` = '1.9';";

$upgrade_queries["1.9.47"]   = [];
$upgrade_queries["1.9.47"][] = "CREATE TABLE `timeservers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `timesrv1` varchar(255) DEFAULT NULL,
  `description` text,
  `permissions` varchar(128) DEFAULT NULL,
  `editDate` TIMESTAMP  NULL  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$upgrade_queries["1.9.47"][] = "INSERT INTO `timeservers` (`name`, `timesrv1`, `description`, `permissions`) VALUES ('NTP pool servers', '0.pool.ntp.org;1.pool.ntp.org', 'NTP pool project servers', '1;2');";
$upgrade_queries["1.9.47"][] = "ALTER TABLE `subnets` ADD `timeserverId` INT(11) NULL DEFAULT '0' AFTER `nameserverId`;";
$upgrade_queries["1.9.47"][] = "-- Database version bump";
$upgrade_queries["1.9.47"][] = "UPDATE `settings` SET `dbversion` = '47';";
