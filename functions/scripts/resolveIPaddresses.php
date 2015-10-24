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
$resolve_config['emptyonly'] = true;			# if true it will only update the ones without DNS entry!
$resolve_config['subnets']	 = array();			# which subnets to check - by id
												# example -> array(1,3,5)	will only update subnets with id 1,3,5
												# 	you can get id's and descriptions with following MySQL query:
												#	select `id`,`description` from `subnets`;
$resolve_config['verbose']  = true;				# verbose response - prints results, cron will email it to you!

# include required scripts
require( dirname(__FILE__) . '/../functions.php' );

# initialize objects
$Database 	= new Database_PDO;
$Admin		= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$DNS		= new DNS ($Database);
$Result		= new Result();

// set to 1 in case of errors
ini_set('display_errors', 0);
error_reporting(E_ERROR);

# cli required
if( php_sapi_name()!="cli" ) { $Result->show_cli("cli only\n", true); }

# set subnet
if (isset($argv[1])) {
	$req_subnets = explode(",", $argv[1]);
	foreach ($req_subnets as $s) {
		if (!is_numeric($s)) { $Result->show_cli("Invalid subnetId provided - $s\n", true); }
		else {
			$resolve_config['subnets'][] = $s;
		}
	}
}


#
# If id is provided via STDIN resolve hosts for 1 subnet only,
# otherwise check all
#

# check all subnets
if(sizeof($resolve_config['subnets']) == 0) {
	# get ony ip's with empty DNS
	if($resolve_config['emptyonly'] == 1) 	{ $query = 'select `id`,`ip_addr`,`dns_name`,`subnetId` from `ipaddresses` where `dns_name` = "" or `dns_name` is NULL order by `ip_addr` ASC;'; }
	else 									{ $query = 'select `id`,`ip_addr`,`dns_name`,`subnetId` from `ipaddresses` order by `ip_addr` ASC;'; }
}
# check selected subnets
else {
	$query[] = "select `id`,`ip_addr`,`dns_name`,`subnetId` from `ipaddresses` where ";
	//go through subnets
	$m=1;
	foreach($resolve_config['subnets'] as $k=>$subnetId) {
		// last
		if($m==sizeof($resolve_config['subnets']))	{ $query[] = '`subnetId` = "'. $subnetId .'" '; }
		else										{ $query[] = '`subnetId` = "'. $subnetId .'" or '; }
		$m++;
	}
	# get ony ip's with empty DNS
	if($resolve_config['emptyonly'] == 1) {
		$query[] = ' and (`dns_name` = "" or `dns_name` is NULL ) ';
	}
	$query[] = 'order by `ip_addr` ASC;';

	//join
	$query = implode("\n", $query);
}

# fetch records
$ipaddresses = $Database->getObjectsQuery($query);

# try to update dns records
if (sizeof($ipaddresses)>0) {
	foreach($ipaddresses as $ip) {
		# fetch subnet
		$subnet = $Subnets->fetch_subnet ("id", $ip->subnetId);
		$nsid = $subnet===false ? false : $subnet->nameserverId;
		# try to resolve
		$hostname = $DNS->resolve_address ($ip->ip_addr, null, true, $nsid);

		# update if change
		if($hostname['class']=="resolved") {
			# values
			$values = array("dns_name"=>$hostname['name'],
							"id"=>$ip->id
							);
			# execute
			if(!$Admin->object_modify("ipaddresses", "edit", "id", $values))	{ $Result->show_cli("Failed to update address ".$Subnets->transform_to_dotted($ip->ip_addr)); }

			# set text
			$res[] = 'updated ip address '. $Subnets->transform_to_dotted($ip->ip_addr) . ' with hostname '. $hostname['name'];
		}
	}
}


# if verbose print result so it can be emailed via cron!
if($resolve_config['verbose'] == true && isset($res)) {
	print implode("\n", $res);
}
?>