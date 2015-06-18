<?php

/**
 * This scripts goes through all IP records, tries to resolve hostnames
 *	and updates the records.
 *
 *	Please configure resolveConf values 
 *
 * Cron example (1x/h):
 * 		0 * * * * /usr/local/bin/php /<ipamdir>/functions/scripts/resolveIPaddresses.php
 *
 ***********************************************************************/


/* settings */
$resCnf['clionly']   = 1;			# if true it can only be run from CLI
$resCnf['emptyonly'] = 1;			# if true it will only update the ones without DNS entry!
$resCnf['subnets']	 = array();		# which subnets to check - by id
									# example -> array(1,3,5)	will only update subnets with id 1,3,5
									# 	you can get id's and descriptions with following MySQL query:
									#	select `id`,`description` from `subnets`;
$resCnf['verbose']  = 1;			# verbose response - prints results, cron will email it to you!

 
 
/* use required functions */
require( dirname(__FILE__) . '/../../config.php' );
require( dirname(__FILE__) . '/../functions.php' );

/* set to 1 in case of errors! */
ini_set('display_errors', 0);
error_reporting(E_ERROR);


/* If configured as CLI only die if not CLI */
if( ($resCnf['clionly']) && (!defined('STDIN')) ) { 
	die(); 
}
else if ( (!$resCnf['clionly']) && (!defined('STDIN')) ) {
	isUserAuthenticated ();
}
else {
}


/* ok, lets set appropriate querries! */

# check all subnets
if(sizeof($resCnf['subnets']) == 0) {
	# get ony ip's with empty DNS
	if($resCnf['emptyonly'] == 1) {
		$query = 'select `id`,`ip_addr`,`dns_name` from `ipaddresses` where `dns_name` like "" order by `ip_addr` ASC;';  		
	}
	else {
		$query = 'select `id`,`ip_addr`,`dns_name` from `ipaddresses` order by `ip_addr` ASC;';  		
	}
}
# check selected subnets
else {
	$query = "select `id`,`ip_addr`,`dns_name` from `ipaddresses` where ";
	
	# go through subnets
	foreach($resCnf['subnets'] as $subnetId) {
		$query .= '`subnetId` = "'. $subnetId .'" or ';
	}
	# remove last or
	$query = substr($query, 0,-3);	
	# get ony ip's with empty DNS
	if($resCnf['emptyonly'] == 1) {
		$query .= ' and `dns_name` like "" ';
	}
	$query .= 'order by `ip_addr` ASC;';
}


# fetch records
$database    = new database($db['host'], $db['user'], $db['pass'], $db['name']);
$ipaddresses = $database->getArray($query);


# try to update dns records
foreach($ipaddresses as $ip) {
	# try to resolve
	$hostname = gethostbyaddr(Transform2long($ip['ip_addr']));
		
	if($hostname != Transform2long($ip['ip_addr'])) {
		# update
		$query = 'update `ipaddresses` set `dns_name` = "'. $hostname .'" where `id` = "'. $ip['id'] .'"';
		$database->executeQuery($query);
		# set text
		$res[] = 'updated ip address '. Transform2long($ip['ip_addr']) . ' with hostname '. $hostname;
	}

}


# if verbose print result so it can be emailed via cron!
if($resCnf['verbose'] == 1) {
	foreach($res as $line) {
		print $line . "\n";
	}
}

# close database
$database->close();
?>