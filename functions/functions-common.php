<?php

/**
 *
 *	functions for general use
 *
 */


/**
 * detect missing gettext and fake function
 */
if(!function_exists('gettext')) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}

/**
 * Prevent XSS
 *
 * @param $input > array or value
 */
function strip_user_tags ($input) {
	if(is_array($input)) { foreach($input as $k=>$v) { $input[$k] = strip_tags($v); } }
	else 											 { $input 	  = strip_tags($input); }
	# result
	return $input;
}

/**
 *	Validate actions
 */
function validate_action ($action) {
	# array of permitted actions
	$permitted = array("add", "edit", "delete", "truncate", "split", "resize", "move");
	if(!in_array($input, $permitted)) { $Result->show("danger", _("Invalid action"), true); }
}

/**
 *	create URL
 */
function createURL () {
	# reset url for base
	if($_SERVER['SERVER_PORT'] == "443") 		{ $url = "https://$_SERVER[HTTP_HOST]"; }
	// reverse proxy doing SSL offloading
	elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
	elseif(isset($_SERVER['HTTP_X_SECURE_REQUEST'])  && $_SERVER['HTTP_X_SECURE_REQUEST'] == 'true') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
	// custom port
	elseif($_SERVER['SERVER_PORT']!="80")  		{ $url = "http://$_SERVER[HTTP_HOST]:$_SERVER[SERVER_PORT]"; }
	// normal http
	else								 		{ $url = "http://$_SERVER[HTTP_HOST]"; }

	//result
	return $url;
}

/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5
 */
function create_link($l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $install = false ) {
	# get settings
	global $User;

	# set rewrite
	if($User->settings->prettyLinks=="Yes") {
		if(!is_null($l5))		{ $link = "$l1/$l2/$l3/$l4/$l5/"; }
		elseif(!is_null($l4))	{ $link = "$l1/$l2/$l3/$l4/"; }
		elseif(!is_null($l3))	{ $link = "$l1/$l2/$l3/"; }
		elseif(!is_null($l2))	{ $link = "$l1/$l2/"; }
		elseif(!is_null($l1))	{ $link = "$l1/"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}
	# normal
	else {
		if(!is_null($l5))		{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4&ipaddrid=$l5"; }
		elseif(!is_null($l4))	{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4"; }
		elseif(!is_null($l3))	{ $link = "?page=$l1&section=$l2&subnetId=$l3"; }
		elseif(!is_null($l2))	{ $link = "?page=$l1&section=$l2"; }
		elseif(!is_null($l1))	{ $link = "?page=$l1"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}

	# result
	return $link;
}


/**
 * Shorten text
 */
function shorten_text($text, $chars = 25) {
	//count input text size
	$startLen = strlen($text);
	//cut onwanted chars
    $text = substr($text,0,$chars);
	//count output text size
	$endLen = strlen($text);

	//append dots if it was cut
	if($endLen != $startLen) {
		$text = $text."...";
	}

    return $text;
}

/**
 * secunds to hms
 */
function sec2hms($sec, $padHours = false) {
    // holds formatted string
    $hms = "";

    // get the number of hours
    $hours = intval(intval($sec) / 3600);

    // add to $hms, with a leading 0 if asked for
    $hms .= ($padHours)
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
          : $hours. ':';

    // get the seconds
    $minutes = intval(($sec / 60) % 60);

    // then add to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

    // seconds
    $seconds = intval($sec % 60);

    // add to $hms, again with a leading 0 if needed
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

    // return hms
    return $hms;
}


/**
 * write new log to database
 *
 * @param  [type]  $command
 * @param  [type]  $details
 * @param  integer $severity
 * @param  [type]  $username
 * @return [type]
 */
function write_log( $command, $details = NULL, $severity = 0, $username = NULL ) {
	# import classes
	global $Database;
	global $Result;
    # set values
    $values = array("severity"=>$severity, "date"=>$Database->toDate(), "username"=>$username, "ipaddr"=>@$_SERVER['REMOTE_ADDR'], "command"=>$command, "details"=>$details);

	# insert log
    try { $Database->insertObject("logs", $values); }
    catch (Exception $e) { !$debugging ? : $Result->show("danger", $e->getMessage(), false); }
}


/**
 * Functions to write changelog
 *
 */
function write_changelog($ctype, $action, $result, $old, $new) {
	return false;
	//cast
	$old = (array) $old;
	$new = (array) $new;

    /* set query, open db connection and fetch results */
    global $database;
    # get settings
    $settings = getAllSettings();

    if($settings['enableChangelog']==1) {

	    # get user details
	    $cuser = getActiveUserDetails();

	    # unset unneeded values and format
	    if($ctype == "ip_addr") 	{
	    	unset($new['action'], $new['subnet'], $new['type']);
	    } elseif($ctype == "subnet")	{
	    	$new['id'] = $new['subnetId'];
	    	unset($new['action'], $new['subnetId'], $new['location'], $new['vrfIdOld'], $new['permissions']);
	    	# if section does not change
	    	if($new['sectionId']==$new['sectionIdNew']) { unset($new['sectionIdNew']); unset($new['sectionId']); unset($old['sectionId']); }
	    	else										{ $old['sectionIdNew'] = $old['sectionId']; }
	    	//transform subnet
	    	if(strlen($new['subnet'])>0) {
		    	$new['subnet'] = Transform2decimal (substr($new['subnet'], 0, strpos($new['subnet'], "/")));
			}
	    } elseif($ctype == "section") {
		    unset($new['action']);
	    }

	    # calculate diff
	    if($action == "edit") {
			//old - checkboxes
			foreach($old as $k=>$v) {
				if(!isset($new[$k]) && $v==1) {
					$new[$k] = 0;
				}
			}
			foreach($new as $k=>$v) {
				//change
				if($old[$k]!=$v && ($old[$k] != str_replace("\'", "'", $v)))	{
					//empty
					if(strlen(@$old[$k])==0)	{ $old[$k] = "NULL"; }
					if(strlen(@$v)		==0)	{ $v = "NULL"; }

					//state
					if($k == 'state') {
						$old[$k] = reformatIPStateText($old[$k]);
						$v = reformatIPStateText($v);
					}
					//section
					elseif($k == 'sectionIdNew') {
						//get old and new device
						if($old[$k] != "NULL") 		{ $dev = getSectionDetailsById($old[$k]);	$old[$k] = $dev['name']; }
						if($v 	 	!= "NULL")		{ $dev = getSectionDetailsById($v);			$v 		 = $dev['name'];  }
					}
					//subnet change
					elseif($k == "masterSubnetId") {
						if($old[$k]==0)				{ $old[$k] = "Root"; }
						else						{ $dev = getSubnetDetailsById($old[$k]);	$old[$k] = transform2long($dev['subnet'])."/$dev[mask] [$dev[description]]"; }
						if($v==0)					{ $v 	   = "Root"; }
						else						{ $dev = getSubnetDetailsById($v);			$v 		 = transform2long($dev['subnet'])."/$dev[mask] [$dev[description]]"; }
					}
					//device change
					elseif($k == 'switch') {
						if($old[$k] == 0)			{ $old[$k] = "None"; }
						elseif($old[$k] != "NULL") 	{ $dev = getDeviceDetailsById($old[$k]);	$old[$k] = $dev['hostname']; }
						if($v == 0)					{ $v = "None"; }
						if($v 	 	!= "NULL")		{ $dev = getDeviceDetailsById($v);			$v 		 = $dev['hostname'];  }
					}
					//vlan
					elseif($k == 'vlanId') {
						//get old and new device
						if($old[$k] == 0)			{ $old[$k] = "None"; }
						elseif($old[$k] != "NULL") 	{ $dev = getVLANById($old[$k]);				$old[$k] = $dev['name']." [$dev[number]]"; }
						if($v == 0)					{ $v = "None"; }
						elseif($v 	 	!= "NULL")	{ $dev = getVLANById($v);					$v 		 = $dev['name']." [$dev[number]]"; }
					}
					//vrf
					elseif($k == 'vrfId') {
						//get old and new device
						if($old[$k] == 0)			{ $old[$k] = "None"; }
						elseif($old[$k] != "NULL") 	{ $dev = getVRFDetailsById($old[$k]);		$old[$k] = $dev['name']." [$dev[description]]"; }
						if($v == 0)					{ $v = "None"; }
						elseif($v 	 	!= "NULL")	{ $dev = getVRFDetailsById($v);				$v 		 = $dev['name']." [$dev[description]]"; }
					}
					//master section change
					elseif($k == 'masterSection') {
						if($old[$k]==0)				{ $old[$k] = "Root"; }
						else						{ $dev = getSectionDetailsById($old[$k]);	$old[$k] = "$dev[name]"; }
						if($v==0)					{ $v 	   = "Root"; }
						else						{ $dev = getSectionDetailsById($v);			$v 		 = "$dev[name]"; }
					}
					//permission change
					elseif($k == "permissions") {
						# get old and compare
						$new['permissions'] = str_replace("\\", "", $new['permissions']);		//Remove /

						# Get all groups:
						$groups = getAllGroups();
						$groups = rekeyGroups($groups);

						# reformat:
						$newp = json_decode($new['permissions']);
						$v = '';
						foreach($newp as $ke=>$p) {
							$v .= "<br>". $groups[$ke]['g_name'] ." : ".parsePermissions($p);
						}

						$old[$k] = "";
					}


					$log["[$k]"] = "$old[$k] => $v";
				}
			}
		}
		elseif($action == "add") {
			$log['[create]'] = "$ctype created";
		}
		elseif($action == "delete") {
			$log['[delete]'] = "$ctype deleted";
			$new['id']		 = $old['id'];
		}
		elseif($action == "truncate") {
			$log['[truncate]'] = "Subnet truncated";
		}
		elseif($action == "resize") {
			$log['[resize]'] = "Subnet Resized";
			$log['[New mask]'] = "/".$new['mask'];
		}
		elseif($action == "perm_change") {
			# get old and compare
			$new['permissions_change'] = str_replace("\\", "", $new['permissions_change']);		//Remove /

			# Get all groups:
			$groups = getAllGroups();
			$groups = rekeyGroups($groups);

			# reformat
			if($new['permissions_change']!="null") {
			$newp = json_decode($new['permissions_change']);
			foreach($newp as $k=>$p) {
				$log['[Permissions]'] .= "<br>". $groups[$k]['g_name'] ." : ".parsePermissions($p);
			}
			}

		}

		//if change happened write it!
		if(isset($log)) {
			# format change
			foreach(@$log as $k=>$l) {
				$changelog .= "$k $l\n";
			}
			$changelog = $database->real_escape_string(trim($changelog));

			# set insert query
			$query = "insert into `changelog` (`ctype`,`coid`,`cuser`,`caction`,`cresult`,`cdate`,`cdiff`) values ('$ctype', '$new[id]', '$cuser[id]', '$action', '$result', NOW(), '$changelog');";

			# execute
			try {  $database->executeQuery( $query ); }
			catch (Exception $e) {
		    	$error =  $e->getMessage();
				return true;
			}
			# mail it!


			# all good
			return true;
		}
	}
	# not enabled
	else {
		return true;
	}
}



/**
 * Functions to transform IPv6 to decimal and back
 *
 */
function ip2long6 ($ipv6) {
	if($ipv6 == ".255.255.255") {
		return false;
	}
    $ip_n = inet_pton($ipv6);
    $bits = 15; // 16 x 8 bit = 128bit
    $ipv6long = "";

    while ($bits >= 0)
    {
        $bin = sprintf("%08b",(ord($ip_n[$bits])));
        $ipv6long = $bin.$ipv6long;
        $bits--;
    }
    return gmp_strval(gmp_init($ipv6long,2),10);
}
function long2ip6($ipv6long) {
    $bin = gmp_strval(gmp_init($ipv6long,10),2);
    $ipv6 = "";

    if (strlen($bin) < 128) {
        $pad = 128 - strlen($bin);
        for ($i = 1; $i <= $pad; $i++) {
            $bin = "0".$bin;
        }
    }

    $bits = 0;
    while ($bits <= 7)
    {
        $bin_part = substr($bin,($bits*16),16);
        $ipv6 .= dechex(bindec($bin_part)).":";
        $bits++;
    }
    // compress result
    return inet_ntop(inet_pton(substr($ipv6,0,-1)));
}

/**
 * reformat array to log
 */
function array_to_log ($logs) {
	$result = "";
	# reformat
	if(is_array($logs)) {
	    foreach($logs as $key=>$req) {
	    	# ignore __ and PHPSESSID
	    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') || (substr($key,0,4) == 'pass') || $key=='plainpass' ) {}
	    	else 																  { $result .= " ". $key . ": " . $req . "<br>"; }
		}
	}
	return $result;
}

/**
 * validate email
 */
function validate_email($email) {
    return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email) ? true : false;
}


/**
 * validate hostname
 */
function validate_hostname($hostname) {
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname) 	//valid chars check
            && preg_match("/^.{1,253}$/", $hostname) 										//overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)   ); 				//length of each label
}










/**
 *	@breadcrumbs functions
 * ------------------------
 */

/**
 *	print breadcrumbs
 */
function print_breadcrumbs ($Section, $Subnet, $req, $Address=null) {
	# subnets
	if($req['page'] == "subnets")		{ print_subnet_breadcrumbs  ($Section, $Subnet, $req, $Address); }
	# folders
	if($req['page'] == "folder")		{ print_folder_breadcrumbs  ($Section, $Subnet, $req); }
	# admin
	else if($req['page'] == "admin")	{ print_admin_breadcrumbs   ($Section, $Subnet, $req); }
	# tools
	else if ($req['page'] == "tools") 	{ print_tools_breadcrumbs   ($Section, $Subnet, $req); }
}

/**
 *	print address breadcrumbs
 */
function print_subnet_breadcrumbs ($Section, $Subnet, $req, $Address) {
	if(isset($req['subnetId'])) {
		# get all parents
		$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
		print "<ul class='breadcrumb'>";
		# remove root - 0
		array_shift($parents);

		# section details
		$section = (array) $Section->fetch_section(null, $req['section']);

		# section name
		print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

		# all parents
		foreach($parents as $parent) {
			$parent = $parent;
			$subnet = (array) $Subnet->fetch_subnet(null,$parent);
			if($subnet['isFolder']==1) {
				print "	<li><a href='".create_link("folder",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
			} else {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
			}
		}
		# parent subnet
		$subnet = (array) $Subnet->fetch_subnet(null,$req['subnetId']);
		# ip set
		if(isset($req['ipaddrid'])) {
			$ip = (array) $Address->fetch_address (null, $req['ipaddrid']);
			print "	<li><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
			print "	<li class='active'>$ip[ip]</li>";			//IP address
		}
		else {
			print "	<li class='active'>$subnet[description] ($subnet[ip]/$subnet[mask])</li>";		//active subnet

		}
		print "</ul>";
	}
}

/**
 *	prints admin breadcrumbs
 */
function print_admin_breadcrumbs ($Section, $Subnet, $req) {
	# nothing here
}

/**
 *	prints folder breadcrumbs
 */
function print_folder_breadcrumbs ($Section, $Subnet, $req) {
	if(isset($req['subnetId'])) {
		# get all parents
		$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
		print "<ul class='breadcrumb'>";
		# remove root - 0
		array_shift($parents);

		# section details
		$section = (array) $Section->fetch_section(null, $req['section']);

		# section name
		print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

		# all parents
		foreach($parents as $parent) {
			$parent = (array) $parent;
			$subnet = (array) $Subnet->fetch_subnet(null,$parent['id']);
			print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
		}
		# parent subnet
		$subnet = (array) $Subnet->fetch_subnet(null,$req['subnetId']);
		print "	<li>$subnet[description]</li>";																		# active subnet
		print "</ul>";
	}
}

/**
 *	print tools breadcrumbs
 */
function print_tools_breadcrumbs ($Section, $Subnet, $req) {
	if(isset($req['tpage'])) {
		print "<ul class='breadcrumb'>";
		print "	<li><a href='".create_link("tools")."'>"._('Tools')."</a> <span class='divider'></span></li>";
		print "	<li class='active'>$req[tpage]></li>";
		print "</ul>";
	}
}









/**
 *	@scan helper functions
 * ------------------------
 */

/**
 *	Ping address helper for CLI threading
 */
function ping_address ($address) {
	global $Scan;
	//scan
	return $Scan->ping_address ($address);
}

/**
 *	Telnet address helper for CLI threading
 */
function telnet_address ($address, $port) {
	global $Scan;
	//scan
	return $Scan->telnet_address ($address, $port);
}

/**
 *	fping subnet helper for fping threading
 */
function fping_subnet ($subnet_cidr, $return = true) {
	global $Scan;
	//scan
	return $Scan->ping_address_method_fping_subnet ($subnet_cidr, $return);
}





/*
to rewrite

	api

	rewrite methods in tools to general

	write_changelog triggers email

	on subnet creation option to scan subnet for new addresses

	changelog on edit ip, edit_object in admin
*/

?>