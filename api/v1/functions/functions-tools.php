<?php

/**
 * Functions for tools
 *
 */





/* @IPCalc functions ---------------- */


/**
 * Calculate reverse DNS entry for IPv6 addresses
 * If a prefix length is given, generate only up to this length (ie. for zone definitions)
 */
function calculateReverseDNS6 ($ipv6, $pflen=128)
{
    $uncompressed = Net_IPv6::removeNetmaskSpec(Net_IPv6::uncompress($ipv6));
    $len = $pflen / 4;
    $parts = explode(':', $uncompressed);
    $res = '';
    foreach($parts as $part)
    {
        $res .= str_pad($part, 4, '0', STR_PAD_LEFT);
    }
    $res = implode('.', str_split(strrev(substr($res, 0, $len)))) . '.ip6.arpa';
    if ($pflen % 4 != 0) {
        $res .= " "._("(closest parent)");
    }
    return $res;
}

/**
 * ipCalc calculations
 */
function calculateIpCalcResult ($cidr)
{
    /* first verify address type */
    $type = IdentifyAddress( $cidr );

    /* IPv4 */
    if ($type == "IPv4")
    {
        $net = (new Net_IPv4)->parseAddress( $cidr );

        //set ip address type
        $out['Type']            = 'IPv4';

        //calculate network details
        $out['IP address']      = $net->ip;        // 192.168.0.50
        $out['Network']         = $net->network;   // 192.168.0.0
        $out['Broadcast']       = $net->broadcast; // 192.168.255.255
        $out['Subnet bitmask']  = $net->bitmask;   // 16
        $out['Subnet netmask']  = $net->netmask;   // 255.255.0.0
        $out['Subnet wildcard'] = long2ip(~ip2long($net->netmask));	//0.0.255.255

        //calculate min/max IP address
        $out['Min host IP']     = long2ip(ip2long($out['Network']) + 1);
        $out['Max host IP']     = long2ip(ip2long($out['Broadcast']) - 1);
        $out['Number of hosts'] = ip2long($out['Broadcast']) - ip2long($out['Min host IP']);

        //subnet class
        $out['Subnet Class']    = checkIpv4AddressType ($out['Network'], $out['Broadcast']);

        //if IP == subnet clear the Host fields
        if ($out['IP address'] == $out['Network']) {
            $out['IP address'] = "/";
        }

    }
    /* IPv6 */
    else
    {
        //set ip address type
        $out['Type']                      = 'IPv6';

        //calculate network details
/*         $out['Host address']              = Net_IPv6::removeNetmaskSpec ( $cidr );  */
        $out['Host address']              = $cidr;
        $out['Host address']              = Net_IPv6::compress ( $out['Host address'], 1 );
        $out['Host address (uncompressed)'] = Net_IPv6::uncompress ( $out['Host address'] );

        $mask                             = Net_IPv6::getNetmaskSpec( $cidr );
        $subnet                           = Net_IPv6::getNetmask( $cidr );
        $out['Subnet prefix']             = Net_IPv6::compress ( $subnet ) .'/'. $mask;
        $out['Prefix length']             = Net_IPv6::getNetmaskSpec( $cidr );

        // get reverse DNS entries
        $out['Host Reverse DNS'] = calculateReverseDNS6($out['Host address (uncompressed)']);
        $out['Subnet Reverse DNS'] = calculateReverseDNS6($subnet, $mask);

        //if IP == subnet clear the Host fields and Host Reverse DNS
         if ($out['Host address'] == $out['Subnet prefix']) {
             $out['Host address']                = '/';
             $out['Host address (uncompressed)'] = '/';
             unset($out['Host Reverse DNS']);
        }

        //min / max hosts
        $maxIp = gmp_strval( gmp_add(gmp_sub(gmp_pow(2, 128 - $mask) ,1),ip2long6 ($subnet)));

        $out['Min host IP']               = long2ip6 ( gmp_strval (gmp_add(ip2long6($subnet),1)) );
        $out['Max host IP']               = long2ip6 ($maxIp);
        $out['Number of hosts']           = MaxHosts( $mask, 1);

        //address type
        $out['Address type']              = Net_IPv6::getAddressType( $cidr );
        $out['Address type']              = checkIpv6AddressType ($out['Address type']);
    }

    /* return results */
    return($out);
}


/**
 * Check IPv4 class type
 */
function checkIpv4AddressType ($ipStart, $ipStop)
{
    /* define classes */
    $classes['private A']          = '10.0.0.0/8';
    $classes['private B']          = '172.16.0.0/12';
    $classes['private C']          = '192.168.0.0/16';

    $classes['Loopback']           = '127.0.0.0/8';
    $classes['Link-local']         = '169.254.0.0/16';
    $classes['Reserved (IANA)']    = '192.0.0.0/24';
    $classes['TEST-NET-1']         = '192.0.2.0/24';
    $classes['IPv6 to IPv4 relay'] = '192.88.99.0/24';
    $classes['Network benchmark']  = '198.18.0.0/15';
    $classes['TEST-NET-2']         = '198.51.100.0/24';
    $classes['TEST-NET-3']         = '203.0.113.0/24';

    $classes['Multicast']          = '224.0.0.0/4';         //Multicast
    $classes['Reserved']           = '240.0.0.0/4';         //Reserved - research

    /* check if it is in array */
    foreach( $classes as $key=>$class )
    {
        if (Net_IPv4::ipInNetwork($ipStart, $class))
        {
            if (Net_IPv4::ipInNetwork($ipStop, $class)) {
                return($key);
            }
        }
    }

    /* no match */
    return false;
}


/**
 * Check IPv6 address type
 */
function checkIpv6AddressType ($subnet)
{
    switch ($subnet) {

        case 10:    $response = "NET_IPV6_NO_NETMASK";      break;
/*         case 1 :    $response = "NET_IPV6_UNASSIGNED";      break; */
        case 1 :    $response = "NET_IPV6";      			break;
        case 11:    $response = "NET_IPV6_RESERVED";        break;
        case 12:    $response = "NET_IPV6_RESERVED_NSAP";   break;
        case 13:    $response = "NET_IPV6_RESERVED_IPX";    break;
        case 14:    $response = "NET_IPV6_RESERVED_UNICAST_GEOGRAPHIC";   break;
        case 22:    $response = "NET_IPV6_UNICAST_PROVIDER";break;
        case 31:    $response = "NET_IPV6_MULTICAST";       break;
        case 42:    $response = "NET_IPV6_LOCAL_LINK";      break;
        case 43:    $response = "NET_IPV6_LOCAL_SITE";      break;
        case 51:    $response = "NET_IPV6_IPV4MAPPING";     break;
        case 51:    $response = "NET_IPV6_UNSPECIFIED";     break;
        case 51:    $response = "NET_IPV6_LOOPBACK";        break;
        case 51:    $response = "NET_IPV6_UNKNOWN_TYPE";    break;
    }

    return $response;
}









/* @log functions ---------------- */


/**
 * Update log table
 */
function updateLogTable ($command, $details = NULL, $severity = 0)
{
	# for db upgrade!
	if(strpos($_SERVER['SCRIPT_URI'], "databaseUpgrade.php")>0) {
		global $db;
		$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
	}
	else {
		global $database;

		# check if broken because of cron
		if(isset($database->error)) {
		    global $db;
			$database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
		}
	}

    /* set variable */
    $date = date("Y-m-d H:i:s");
    $user = getActiveUserDetails();
    $user = $user['username'];

    /* set query */
    $query  = 'insert into logs '. "\n";
    $query .= '(`severity`, `date`,`username`,`ipaddr`,`command`,`details`)'. "\n";
    $query .= 'values'. "\n";
    $query .= '("'.  $severity .'", "'. $date .'", "'. $user .'", "'. $_SERVER['REMOTE_ADDR'] .'", "'. $command .'", "'. $details .'");';

    /* execute */
    try {
    	$database->executeQuery($query);
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'._('Error').': '. $error .'</div>');
	}

    return true;
}


/**
 * Get log details by Id
 */
function getLogByID ($logId)
{
    global $database;
    /* set query */
    $query  = "select * from `logs` where `id` = '$logId';";

    /* execute */
    try { $logs = $database->getArray($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'._('Error').': '. $error .'</div>');
	}

    return $logs[0];
}


/**
 * Get all logs
 */
function getAllLogs($logCount, $direction = NULL, $lastId = NULL, $highestId = NULL, $informational, $notice, $warning)
{
    global $database;

	/* query start */
	$query  = 'select * from ('. "\n";
	$query .= 'select * from logs '. "\n";

	/* append severities */
	$query .= 'where (`severity` = "'. $informational .'" or `severity` = "'. $notice .'" or `severity` = "'. $warning .'" )'. "\n";

	/* set query based on direction */
	if( ($direction == "next") && ($lastId != $highestId) ) {
		$query .= 'and `id` < '. $lastId .' '. "\n";
		$query .= 'order by `id` desc limit '. $logCount . "\n";
	}
	else if( ($direction == "prev") && ($lastId != $highestId)) {
		$query .= 'and `id` > '. $lastId .' '. "\n";
		$query .= 'order by `id` asc limit '. $logCount . "\n";
	}
	else {
		$query .= 'order by `id` desc limit '. $logCount . "\n";
	}

	/* append limit and order */
	$query .= ') as test '. "\n";
	$query .= 'order by `id` desc limit '. $logCount .';'. "\n";

    /* execute */
    try { $logs = $database->getArray($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'._('Error').': '. $error .'</div>');
	}


    /* return vlans */
    return $logs;
}


/**
 * Get all logs for export
 */
function getAllLogsForExport()
{
    global $database;

	/* increase memory size */
	ini_set('memory_limit', '512M');

	/* query start */
	$query = 'select * from `logs` order by `id` desc;'. "\n";

    /* execute */
    try { $logs = $database->getArray($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'._('Error').': '. $error .'</div>');
	}
    /* return vlans */
    return $logs;
}


/**
 * Clear all logs
 */
function clearLogs()
{
    global $database;

	/* query start */
	$query  = 'truncate table logs;'. "\n";

    /* execute */
    try { $logs = $database->executeQuery($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'._('Error').': '. $error .'</div>');
	}

    /* return result */
    return true;
}


/**
 * Count all logs
 */
function countAllLogs ()
{
    global $database;

    /* set query */
    $query = 'select count(*) from logs;';
    /* execute */
    try { $logs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return vlans */
    return $logs[0]['count(*)'];
}


/**
 *	Prepare log file from array
 */
function prepareLogFromArray ($logs)
{
	$result = "";

	/* reformat */
    foreach($logs as $key=>$req) {
    	//ignore __ and PHPSESSID
    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') ) {}
    	else 																  { $result .= " ". $key . ": " . $req . "<br>"; }
	}

	/* return result */
	return $result;
}


/**
 * Get highest log id
 */
function getHighestLogId()
{
    global $database;

    /* set query */
    $query = 'select id from logs order by id desc limit 1;';

    /* execute */
    try { $logs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return vlans */
    return $logs[0]['id'];
}









/* @subnet functions ---------------- */
/**
 * Print subnets structure
 */
function printToolsSubnets( $subnets, $custom )
{
		$html = array();

		# root is 0
		$rootId = 0;

		# remove all not permitted!
		foreach($subnets as $k=>$s) {
			$permission = checkSubnetPermission ($s->id);
			if($permission == 0) { unset($subnets[$k]); }
		}

		if(sizeof($subnets) > 0) {
		foreach ( $subnets as $item ) {
			$item = (array) $item;
			$children[$item['masterSubnetId']][] = $item;
		}
		}

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# display selected subnet as opened
		if(isset($_GET['subnetId']))
		$allParents = getAllParents ($_GET['subnetId']);

		# return table content (tr and td's)
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = "-"; }

			if(count($parent_stack) == 0) {
				$margin = "0px";
				$padding = "0px";
			}
			else {
				# padding
				$padding = "10px";

				# margin
				$margin  = (count($parent_stack) * 10) -10;
				$margin  = $margin *2;
				$margin  = $margin."px";
			}

			# count levels
			$count = count( $parent_stack ) + 1;

			# get subnet details
				# get VLAN
				$vlan = subnetGetVLANdetailsById($option['value']['vlanId']);
				$vlan = $vlan['number'];
				if(empty($vlan) || $vlan == "0") 	{ $vlan = ""; }			# no VLAN

				# description
				if(strlen($option['value']['description']) == 0) 	{ $description = "/"; }													# no description
				else 												{ $description = $option['value']['description']; }						# description

				# requests
				if($option['value']['allowRequests'] == 1) 			{ $requests = "<i class='fa fa-gray fa-check'></i>"; }												# requests enabled
				else 												{ $requests = ""; }														# request disabled

				# hosts check
				if($option['value']['pingSubnet'] == 1) 			{ $pCheck = "<i class='fa fa-gray fa-check'></i>"; }												# ping check enabled
				else 												{ $pCheck = ""; }														# ping check disabled


			# print table line
			if(strlen($option['value']['subnet']) > 0) {
				$html[] = "<tr>";
				# folder
				if($option['value']['isFolder']==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'>$description</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-folder-open'></i> $description</td>";
				}
				else {
				if($count==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-folder-open-o'></i> $description</td>";
				} else {
					# last?
					if(!empty( $children[$option['value']['id']])) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i> $description</td>";
					}
					else {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-angle-right'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-angle-right'></i> $description</td>";
					}
				}
				}
				//vlan
				$html[] = "	<td>$vlan</td>";

				//masterSubnet
				if( $option['value']['masterSubnetId']==0 || empty($option['value']['masterSubnetId']))  	{ $masterSubnet = true; }		# check if it is master
				else 																		 			 	{ $masterSubnet = false; }

				if($masterSubnet) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$master = getSubnetDetailsById ($option['value']['masterSubnetId']);
					if($master['isFolder'])
						$html[] = "	<td><i class='fa fa-gray fa-folder-open-o'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$master['id'])."'>$master[description]</a></td>" . "\n";
					else {
						$html[] = "	<td><a href='".create_link("folder",$option['value']['sectionId'],$master['id'])."'>".transform2long($master['subnet']) .'/'. $master['mask'] .'</a></td>' . "\n";
					}
				}

				//used , free
				if($option['value']['isFolder']==1) {
					$html[] =  '<td class="hidden-xs hidden-sm"></td>'. "\n";
				}
				elseif( (!$masterSubnet) || (!subnetContainsSlaves($option['value']['id']))) {
		    		$ipCount = countIpAddressesBySubnetId ($option['value']['id']);
		    		$calculate = calculateSubnetDetails ( gmp_strval($ipCount), $option['value']['mask'], $option['value']['subnet'] );

		    		$html[] = ' <td class="used hidden-xs hidden-sm">'. reformatNumber($calculate['used']) .'/'. reformatNumber($calculate['maxhosts']) .' ('.reformatNumber($calculate['freehosts_percent']) .' %)</td>';
		    	}
		    	else {
					$html[] =  '<td class="hidden-xs hidden-sm"></td>'. "\n";
				}

				//requests
				$html[] = "	<td class='hidden-xs hidden-sm'>$requests</td>";
				$html[] = "	<td class='hidden-xs hidden-sm'>$pCheck</td>";

				//custom
				if(sizeof($custom) > 0) {
			   		foreach($custom as $field) {

			   			$html[] =  "<td class='hidden-xs hidden-sm hidden-md'>";

			   			//booleans
						if($field['type']=="tinyint(1)")	{
							if($option['value'][$field['name']] == "0")			{ $html[] = _("No"); }
							elseif($option['value'][$field['name']] == "1")		{ $html[] = _("Yes"); }
						}
						//text
						elseif($field['type']=="text") {
							if(strlen($option['value'][$field['name']])>0)		{ $html[] = "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $option['value'][$field['name']])."'>"; }
							else												{ $html[] = ""; }
						}
						else {
							$html[] = $option['value'][$field['name']];

						}

			   			$html[] =  "</td>";
			    	}
			    }

				$html[] = "</tr>";
			}

			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['id']] ) ) {
				array_push( $parent_stack, $option['value']['masterSubnetId'] );
				$parent = $option['value']['id'];
			}
			# Last items
			else { }
		}
		return implode( "\n", $html );
}









/* @ search functions ---------------- */

/**
 * Search function
 */
function searchAddresses ($query)
{
    global $database;

    /* execute */
    try { $logs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return result */
    return $logs;
}


/**
 * Search subnets
 */
function searchSubnets ($searchterm, $searchTermEdited = "")
{
    global $database;

    # get custom subnet fields
    $myFields = getCustomFields('subnets');
    $custom  = '';

    if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {
			$custom  .= ' or `'.$myField['name'].'` like "%'.$searchterm.'%" ';
		}
	}

    /* set query */
    if($searchTermEdited['low']==0 && $searchTermEdited['high']==0) {
		$query[] = 'select * from `subnets` where `description` like "%'. $searchterm .'%" '.$custom.';';
    } else {
		$query[] = 'select * from `subnets` where `description` like "%'. $searchterm .'%" or `subnet` between "'. $searchTermEdited['low'] .'" and "'. $searchTermEdited['high'] .'" '.$custom.';';
    }

	/* search inside subnets even if IP does not exist! */
	if($searchTermEdited['low']==$searchTermEdited['high']) {
		$allSubnets = fetchAllSubnets ();
		foreach($allSubnets as $s) {
			// first verify address type
			$type = IdentifyAddress($s['subnet']);
			if($type == "IPv4") {
				require_once 'PEAR/Net/IPv4.php';
				$net = Net_IPv4::parseAddress(transform2long($s['subnet']).'/'.$s['mask']);

				if($searchTermEdited['low']>transform2decimal($net->network) && $searchTermEdited['low']<transform2decimal($net->broadcast)) {
					$query[] = "select * from `subnets` where `id` = $s[id]; \n";
				}
			}
		}
	}

    /* execute each query */
    foreach($query as $q) {
	    try { $search[] = $database->getArray( $q ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	    }
    }

    /* filter results - remove blank */
    $search = array_filter($search);

    /* die if errors */
    if(isset($error)) {
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return result */
    return $search;
}



/**
 * Search VLANS
 */
function searchVLANs ($searchterm)
{
    global $database;

    # get custom VLAN fields
    $myFields = getCustomFields('vlans');
    $custom  = '';

    if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {
			$custom  .= ' or `'.$myField['name'].'` like "%'.$searchterm.'%" ';
		}
	}

    /* set query */
	$query = 'select * from `vlans` where `name` like "%'. $searchterm .'%" or `description` like "%'. $searchterm .'%" or `number` like "%'. $searchterm .'%" '.$custom.';';

    /* execute */
    try { $search = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return result */
    return $search;
}



/**
 * Reformat incomplete IPv4 address to decimal for search!
 */
function reformatIPv4forSearch ($ip)
{
	//remove % sign if present
	$ip = str_replace("%", "", $ip);
	//remove last .
	$size 	  = count($ip);
	$lastChar = substr($ip, -1);
	if ($lastChar == ".") {
		$ip = substr($ip, 0, - 1);
	}

	/* check if subnet provided, then we have all we need */
	if (strpos($ip, "/")>0) {
		require_once 'PEAR/Net/IPv4.php';
		$net = Net_IPv4::parseAddress($ip);

		$result['low']   = transform2decimal($net->network);
		$result['high']	 = transform2decimal($net->broadcast);
	}
	else {
		/* if subnet is not provided maye wildcard is, so explode it to array */
		$ip = explode(".", $ip);

		//4 is ok
		if (sizeof($ip) == 4) {
			$temp = implode(".", $ip);
			$result['low'] = $result['high'] = transform2decimal($temp);
		}
		//3 we need to modify
		else if (sizeof($ip) == 3) {
			$ip[3]	= 0;
			$result['low']  = transform2decimal(implode(".", $ip));

			$ip[3]	= 255;
			$result['high'] = transform2decimal(implode(".", $ip));
		}
		//2 also
		else if (sizeof($ip) == 2) {
			$ip[2]	= 0;
			$ip[3]	= 0;
			$result['low']  = transform2decimal(implode(".", $ip));

			$ip[2]	= 255;
			$ip[3]	= 255;
			$result['high'] = transform2decimal(implode(".", $ip));
		}
		//1 also
		else if (sizeof($ip) == 1) {
			$ip[1]	= 0;
			$ip[2]	= 0;
			$ip[3]	= 0;
			$result['low']  = transform2decimal(implode(".", $ip));

			$ip[1]	= 255;
			$ip[2]	= 255;
			$ip[3]	= 255;
			$result['high'] = transform2decimal(implode(".", $ip));
		}
		//else return same value
		else {
			$result['low']  = implode(".", $ip);
			$result['high'] = implode(".", $ip);
		}
	}

	//return result!
	return $result;
}


/**
 * Reformat incomplete IPv6 address to decimal for search!
 */
function reformatIPv6forSearch ($ip)
{
	//split network and subnet part
	$ip = explode("/", $ip);

	//if subnet is not provided we are looking for host!
	if (sizeof($ip) < 2) {
		$return['low']  = Transform2decimal($ip[0]);
		$return['high'] = Transform2decimal($ip[0]);
	}

	//if network part ends with :: we must search the complete provided subnet!
	$lastChars = substr($ip[0], -2);

	if ($lastChars == "::") {
		$return['low']  = Transform2decimal ($ip[0]);

		//set highest IP address
		$subnet = substr($ip[0], 0, -2);
		$subnet = Transform2decimal ($subnet);

		//calculate all possible hosts in subnet mask
		$maskHosts = gmp_strval(gmp_sub(gmp_pow(2, 128 - $ip[1]) ,1));

		$return['high'] = gmp_strval(gmp_add($return['low'], $maskHosts));
	}

	return $return;
}









/* @ IP requests -------------- */

/**
 * Is IP already requested?
 */
function isIPalreadyRequested($ip)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from requests where `ip_addr` = "'. $ip .'" and `processed` = 0;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return true is IP already in procedure */
    if(sizeof($details) != 0) 	{ return true; }
    else 						{ return false; }
}


/**
 * Count number of requested IP addresses
 */
function countRequestedIPaddresses()
{
	# check if already in cache
	if($vtmp = checkCache("openrequests", 0)) {
		return $vtmp;
	}
	# query
	else {

	    global $database;
	    /* set query, open db connection and fetch results */
	    $query    = 'select count(*) from requests where `processed` = 0;';

	    /* execute */
	    try { $details = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # save to cche
	    writeCache("openrequests", 0, $details[0]['count(*)']);
	    # return
	    return $details[0]['count(*)'];

	}
}


/**
 * Get all active IP requests
 */
function getAllActiveIPrequests()
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from requests where `processed` = 0 order by `id` desc;';

    /* execute */
    try { $activeRequests = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return $activeRequests;
}


/**
 * Get all IP requests
 */
function getAllIPrequests($limit = 20)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from requests order by `id` desc;';

    /* execute */
    try { $activeRequests = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return $activeRequests;
}


/**
 * Get IP request by id
 */
function getIPrequestById ($id)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from requests where `id` = "'. $id .'";';
    $activeRequests  = $database->getArray($query);

    /* execute */
    try { $activeRequests = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return $activeRequests[0];
}

/**
 * Insert new IP request
 */
function addNewRequest ($request)
{
    global $database;

    # replace special chars for description
    $request['description'] = mysqli_real_escape_string($database, $request['description']);

    /* set query */
    $query  = 'insert into requests ' . "\n";
    $query .= '(`subnetId`, `ip_addr`,`description`,`dns_name`,`owner`,`requester`,`comment`,`processed`) ' . "\n";
    $query .= 'values ' . "\n";
    $query .= '("'. $request['subnetId'] .'", "'. $request['ip_addr'] .'", "'. $request['description'] .'", '. "\n";
    $query .= ' "'. $request['dns_name'] .'", "'. $request['owner'] .'",   "'. $request['requester'] .'", "'. $request['comment'] .'", "0");';

	/* set log file */
	$log = prepareLogFromArray ($request);

    /* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
    	updateLogTable ('Failed to add new IP request', $log."\n".$error, 2);
        return false;
    }

    /* return success */
    updateLogTable ('New IP request added', $log, 1);
    return true;
}


/**
 * reject IP request
 */
function rejectIPrequest($id, $comment)
{
    global $database;

    /* set query */
    $query  = 'update requests set `processed` = "1", `accepted` = "0", `adminComment` = "'. $comment .'" where `id` = "'. $id .'";' . "\n";

    /* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
    	updateLogTable ('Failed to reject IP address id '. $id, 'Failed to reject IP address id '. $id . " - error:".$error, 2);
        return false;
    }
    /* execute query */
    updateLogTable ('IP address id '. $id .' rejected', 'IP address id '. $id . " rejected with comment". $comment, 1);
    return true;
}


/**
 * accept IP request
 */
function acceptIPrequest($request)
{
    global $database;

    /* first update request */
    $query  = 'update requests set `processed` = "1", `accepted` = "1", `adminComment` = "'. $request['adminComment'] .'" where `id` = "'. $request['requestId'] .'";' . "\n";

	/* We need to get custom fields! */
	$myFields = getCustomFields('ipaddresses');
	$myFieldsInsert['query']  = '';
	$myFieldsInsert['values'] = '';

	if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {
			$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
			$myFieldsInsert['values'] .= ", '". $request[$myField['name']] . "'";
		}
	}

	/* insert */
	$query .= "insert into `ipaddresses` ";
	$query .= "(`subnetId`,`description`,`ip_addr`, `dns_name`,`mac`, `owner`, `state`, `switch`, `port`, `note` ". $myFieldsInsert['query'] .") ";
	$query .= "values ";
	$query .= "('". $request['subnetId'] ."', '". $request['description'] ."', '".$request['ip_addr']."', ". "\n";
	$query .= " '". $request['dns_name'] ."', '". $request['mac'] ."', '". $request['owner'] ."', '". $request['state'] ."', ". "\n";
	$query .= " '". $request['switch'] ."', '". $request['port'] ."', '". $request['note'] ."'". $myFieldsInsert['values'] .");";

	/* set log file */
    foreach($request as $key=>$req) {
		$log .= " ". $key . ": " . $req . "<br>";
	}

    /* execute */
    try { $database->executeMultipleQuerries( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        updateLogTable ('Failed to accept IP request', $log . "\n".$error, 2);
        return false;
    }

    /* return success */
    updateLogTable ('IP request accepted', $log, 1);
    return true;
}











/* @device functions ------------------- */



/**
 * Get all unique devices
 */
function getAllUniqueDevices ($orderby = "hostname", $direction = "asc")
{
    global $database;

    /* get all vlans, descriptions and subnets */
    $query   = "SELECT * from `devices` LEFT JOIN `deviceTypes` ON `devices`.`type` = `deviceTypes`.`tid` order by `devices`.`$orderby` $direction;";

    /* execute */
    try { $devices = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return unique devices */
    return $devices;
}


/**
 * Get all unique devices - filter
 */
function getAllUniqueDevicesFilter ($field, $search, $orderby = "hostname", $direction = "asc")
{
    global $database;

    /*query */
    if($field == "type")	{ $query   = "select * from `devices` as `d`, `deviceTypes` as `t` where `d`.`type` = `t`.`tid` and `t`.`tname` like '%$search%' order by `d`.`$orderby` $direction;"; }
    else 					{ $query   = "select * from `devices` as `d`, `deviceTypes` as `t` where `d`.`type` = `t`.`tid` and `d`.`$field` like '%$search%' order by `d`.`$orderby` $direction;"; }

    /* execute */
    try { $devices = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return unique devices */
    return $devices;
}


/**
 * Get device details by id
 */
function getDeviceDetailsById($id)
{
    global $database;

    /* get all vlans, descriptions and subnets */
    $query = 'SELECT * FROM `devices` where `id` = "'. $id .'";';

    /* execute */
    try { $device = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return details */
    if($device) { return $device[0]; }
    else 		{ return false; }
}


/**
 * Get all device types
 */
function getAllDeviceTypes ()
{
    global $database;

    /* get all vlans, descriptions and subnets */
    $query   = "select * from `deviceTypes`;";

    /* execute */
    try { $devices = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return unique devices */
    return $devices;
}


/**
 * Get type details by id
 */
function getTypeDetailsById($id)
{
    global $database;

    /* get all vlans, descriptions and subnets */
    $query = 'SELECT * FROM `deviceTypes` where `tid` = '. $id .';';

    /* execute */
    try { $device = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return details */
    if($device) { return $device[0]; }
    else 		{ return false; }
}












/* @other functions ------------------- */

/**
 *	fetch instructions
 */
function fetchInstructions ()
{
    global $database;

	/* execute query */
	$query 			= "select * from instructions;";

    /* execute */
    try { $instructions = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return result */
    return $instructions;
}


/**
 * version check
 */
function getLatestPHPIPAMversion()
{
	/* fetch page */
	$handle = @fopen("http://phpipam.net/phpipamversion.php", "r");
	if($handle) {
		while (!feof($handle)) {
			$version = fgets($handle);
		}
		fclose($handle);
	}

	# replace dots for check
	$versionT = str_replace(".", "", $version);

	/* return version */
	if(is_numeric($versionT)) 	{ return $version; }
	else 						{ return false; }
}


/**
 *	update version check time
 */
function updatePHPIPAMversionCheckTime()
{
    global $database;
	$query 		 = "update `settings` set `vcheckDate` = '".date("Y-m-d H:i:s")."';";


    /* execute */
    try { $database->executeQuery($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
	}

    /* return result */
    return true;
}



?>
