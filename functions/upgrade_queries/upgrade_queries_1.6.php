<?php

#
# Version 1.6 queries
#
$upgrade_queries["1.6.39"]   = [];
$upgrade_queries["1.6.39"][] = "-- Version update";
$upgrade_queries["1.6.39"][] = "UPDATE `settings` set `version` = '1.6';";

// passkeys
$upgrade_queries["1.6.40"]   = [];
$upgrade_queries["1.6.40"][] = "-- Database version bump";
$upgrade_queries["1.6.40"][] = "UPDATE `settings` set `dbversion` = '40';";
// add passkey support to settings
$upgrade_queries["1.6.40"][] = "ALTER TABLE `settings` ADD `passkeys` TINYINT(1)  NULL  DEFAULT '0'  AFTER `2fa_userchange`;";
// allow passkey login only
$upgrade_queries["1.6.40"][] = "ALTER TABLE `users` ADD `passkey_only` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `authMethod`;";
// passkey table
$upgrade_queries["1.6.40"][] = "CREATE TABLE `passkeys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credentialId` text NOT NULL,
  `keyId` text NOT NULL,
  `credential` text NOT NULL,
  `comment` text,
  `created` timestamp NULL DEFAULT NULL,
  `used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";