<?php

#
# Version 1.7 queries
#
$upgrade_queries["1.7.40"]   = [];
$upgrade_queries["1.7.40"][] = "-- Version update";
$upgrade_queries["1.7.40"][] = "UPDATE `settings` set `version` = '1.7';";

// add passkey support to settings
$upgrade_queries["1.7.40"][] = "ALTER TABLE `settings` ADD `passkeys` TINYINT(1)  NULL  DEFAULT '0'  AFTER `2fa_userchange`;";
// allow passkey login only
$upgrade_queries["1.7.40"][] = "ALTER TABLE `users` ADD `passkey_only` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `authMethod`;";
// passkey table
$upgrade_queries["1.7.40"][] = "CREATE TABLE `passkeys` (
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

$upgrade_queries["1.7.40"][] = "-- Database version bump";
$upgrade_queries["1.7.40"][] = "UPDATE `settings` set `dbversion` = '40';";

$upgrade_queries["1.7.41"]   = [];
$upgrade_queries["1.7.41"][] = "-- Database version bump";
$upgrade_queries["1.7.41"][] = "UPDATE `settings` set `dbversion` = '41';";

// widget improvements
$upgrade_queries["1.7.42"][] = "-- update widget parameteres for stock widgets";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wparams`='height=x&max=x' WHERE `wfile` in ('favourite_subnets','top10_hosts_v4','top10_hosts_v6','top10_percentage','changelog','requests','access_logs','error_logs','threshold') AND `wparams` IS NULL;";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wparams`='height=x' WHERE `wfile` in ('statistics','locations','customers') AND `wparams` IS NULL;";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wparams`='height=x&days=30' WHERE `wfile`='inactive-hosts';";
$upgrade_queries["1.7.42"][] = "-- remove numbers from titles and descriptions";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Top IPv4 subnets by number of hosts',`wdescription`='Shows graph of top IPv4 subnets by number of hosts' WHERE `wfile`='top10_hosts_v4' AND `wtitle`='Top 10 IPv4 subnets by number of hosts' AND `wdescription`='Shows graph of top 10 IPv4 subnets by number of hosts';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Top IPv6 subnets by number of hosts',`wdescription`='Shows graph of top IPv6 subnets by number of hosts' WHERE `wfile`='top10_hosts_v6' AND `wtitle`='Top 10 IPv6 subnets by number of hosts' AND `wdescription`='Shows graph of top 10 IPv6 subnets by number of hosts';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Top IPv4 subnets by usage percentage',`wdescription`='Shows graph of top IPv4 subnets by usage percentage' WHERE `wfile`='top10_percentage' AND `wtitle`= 'Top 10 IPv4 subnets by usage percentage' AND `wdescription`='Shows graph of top 10 IPv4 subnets by usage percentage';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wdescription`='Shows favourite subnets' WHERE `wfile`='top10_percentage' AND `wtitle`= 'Favourite subnets' AND `wdescription`='Shows 5 favourite subnets';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Most recent change log entries',`wdescription`='Shows list of most recent change log entries' WHERE `wfile`='changelog' AND `wtitle`='Last 5 change log entries' AND `wdescription`='Shows last 5 change log entries';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Most recent informational logs',`wdescription`='Shows list of most recent informational logs' WHERE `wfile`='access_logs' AND `wtitle`='Last 5 informational logs' AND `wdescription`='Shows list of last 5 informational logs';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wtitle`='Most recent warning / error logs',`wdescription`='Shows list of most recent warning and error logs' WHERE `wfile`='error_logs' AND `wtitle`='Last 5 warning / error logs' AND `wdescription`='Shows list of last 5 warning and error logs';";
$upgrade_queries["1.7.42"][] = "UPDATE `widgets` SET `wdescription`='Shows threshold usage for most consumed subnets' WHERE `wfile`='threshold' AND `wtitle`='Threshold' AND `wdescription`='Shows threshold usage for top 5 subnets';";
$upgrade_queries["1.7.42"][] = "-- add new widget";
$upgrade_queries["1.7.42"][] = "INSERT INTO `widgets` (`wtitle`,`wdescription`,`wfile`,`wparams`,`whref`,`wsize`,`wadminonly`,`wactive`) VALUES ('Recent Logins','Shows most recent user logins','recent_logins','max=5&height=x','no','4','yes','yes');";
$upgrade_queries["1.7.42"][] = "-- Database version bump";
$upgrade_queries["1.7.42"][] = "UPDATE `settings` set `dbversion` = '42';";

$upgrade_queries["1.7.43"][] = "-- Increase 2fa_length minimum value to 26 (128bit);";
$upgrade_queries["1.7.43"][] = "UPDATE `settings` SET `2fa_length`=26 WHERE `2fa_length`<26;";
$upgrade_queries["1.7.43"][] = "-- Database version bump";
$upgrade_queries["1.7.43"][] = "UPDATE `settings` SET `dbversion` = '43';";

$upgrade_queries["1.71.43"]   = [];
$upgrade_queries["1.71.43"][] = "-- Version update";
$upgrade_queries["1.71.43"][] = "UPDATE `settings` set `version` = '1.71';";

$upgrade_queries["1.72.43"]   = [];
$upgrade_queries["1.72.43"][] = "-- Version update";
$upgrade_queries["1.72.43"][] = "UPDATE `settings` set `version` = '1.72';";

$upgrade_queries["1.73.43"]   = [];
$upgrade_queries["1.73.43"][] = "-- Version update";
$upgrade_queries["1.73.43"][] = "UPDATE `settings` set `version` = '1.73';";