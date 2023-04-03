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

# include required scripts
require_once( dirname(__FILE__) . '/../functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

# initialize objects
$Database 	= new Database_PDO;
$Admin		= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$DNS		= new DNS ($Database);
$Result		= new Result();

# cli required
if( php_sapi_name()!="cli" ) { $Result->show_cli("cli only\n", true); }

# set all subnet ids
$resolved_subnets = $Database->findObjects("subnets", "resolveDNS", "1", 'id', true);
if(is_array($resolved_subnets)) {
	foreach ($resolved_subnets as $s) {
		$config['resolve_subnets'][] = $s->id;
	}
}

#
# If id is provided via STDIN resolve hosts for 1 subnet only,
# otherwise check all
#

# check all subnets
if(sizeof($config['resolve_subnets']) == 0) {
	# get only ip's with empty DNS
	if($config['resolve_emptyonly'] == 1) 	{ $query = 'select `id`,`ip_addr`,`hostname`,`subnetId` from `ipaddresses` where `hostname` = "" or `hostname` is NULL order by `ip_addr` ASC;'; }
	else 									{ $query = 'select `id`,`ip_addr`,`hostname`,`subnetId` from `ipaddresses` order by `ip_addr` ASC;'; }
}
# check selected subnets
else {
	$query[] = "select `id`,`ip_addr`,`hostname`,`subnetId` from `ipaddresses` where (";
	//go through subnets
	$m=1;
	foreach($config['resolve_subnets'] as $k=>$subnetId) {
		// last
		if($m==sizeof($config['resolve_subnets']))	{ $query[] = '`subnetId` = "'. $subnetId .'" '; }
		else										{ $query[] = '`subnetId` = "'. $subnetId .'" or '; }
		$m++;
	}
	$query[] = ")";
	# get ony ip's with empty DNS
	if($config['resolve_emptyonly'] == 1) {
		$query[] = ' and (`hostname` = "" or `hostname` is NULL ) ';
	}
	$query[] = 'order by `ip_addr` ASC;';

	//join
	$query = implode("\n", $query);
}

# fetch records
$ipaddresses = $Database->getObjectsQuery($query);

# try to update dns records
if (is_array($ipaddresses)) {
	foreach($ipaddresses as $ip) {
		# fetch subnet
		$subnet = $Subnets->fetch_subnet ("id", $ip->subnetId);
		$nsid = $subnet===false ? false : $subnet->nameserverId;
		# try to resolve
		$hostname = $DNS->resolve_address ($ip->ip_addr, null, true, $nsid);

		# update if change
		if($hostname['class']=="resolved") {
			# values
			$values = array("hostname"=>$hostname['name'],
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
if($config['resolve_verbose'] == true && isset($res)) {
	print implode("\n", $res);
}
?>
