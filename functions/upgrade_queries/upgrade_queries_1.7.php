<?php

#
# Version 1.71 queries
#
$upgrade_queries["1.7.41"]   = [];
$upgrade_queries["1.7.41"][] = "-- Version update";
$upgrade_queries["1.7.41"][] = "UPDATE `settings` set `version` = '1.7';";
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