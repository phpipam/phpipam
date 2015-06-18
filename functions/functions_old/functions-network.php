<?php

/**
 * Network functions
 *
 */




/* @common functions ---------------- */


/**
 * Resolve reverse DNS name if blank
 * Return class and name
 */
function ResolveDnsName ( $ip )
{
    // format to dotted representation
    $ip = Transform2long ( $ip );

    // resolve dns name if it is empty and configured
    if ( empty($dns_name) ) {
        $return['class'] = "resolved";
        $return['name']  = gethostbyaddr( $ip );
    }

    // if nothing resolves revert to blank
    if ($return['name'] ==  $ip) {
        $return['name'] = "";
    }

    /* return result */
    return($return);
}



/**
 * Present numbers in pow 10, only for IPv6
 */
function reformatNumber ($number)
{
	$length = strlen($number);
	$pos	= $length - 3;

	if ($length > 8) {
		$number = "~". substr($number, 0, $length - $pos) . "&middot;10^<sup>". $pos ."</sup>";
	}

	return $number;
}


/**
 *	Reformat IP address state
 */
function reformatIPState ($state, $active = false, $tooltip = true)
{
	/*
	0 = not active
	1 = active
	2 = reserved
	*/
	if($tooltip) {
		switch ($state)
		{
			case "0": 			 return "<i class='fa-red  	fa fa-tag state' rel='tooltip' title='"._("Not in use (Offline)")."'></i>"; break;
			case "1" && $active: return "<i class='fa-green fa fa-tag state' rel='tooltip' title='"._("Online")."'></i>"; 		break;
			case "1": 			 return " "; 		break;
			case "2": 			 return "<i class='fa-blue  fa fa-tag state' rel='tooltip' title='"._("Reserved")."'></i>"; break;
			case "3": 			 return "<i class='fa-silver 	fa fa-tag state' rel='tooltip'  title='"._("DHCP")."'></i>"; break;
			default: 			 return $state;
		}
	} else {
		switch ($state)
		{
			case "0": 			 return "<i class='fa-red  fa fa-tag state'></i>"; break;
			case "1" && $active: return "<i class='fa-green fa fa-tag state'></i>"; 		break;
			case "1": 			 return " "; 		break;
			case "2": 			 return "<i class='fa-blue  fa fa-tag state'></i>"; break;
			case "3": 			 return "<i class='fa-silver  fa fa-tag state'></i>"; break;
			default: 			 return $state;
		}
	}
}


/**
 *	Reformat IP address state text
 */
function reformatIPStateText ($state)
{
	/*
	0 = not active
	1 = active
	2 = reserved
	*/
	switch ($state)
	{
		case "0": 			 return _("Not in use (Offline)"); 	break;
		case "1":			 return _("Online");		 		break;
		case "2": 			 return _("Reserved"); 				break;
		case "3": 			 return _("DHCP"); 					break;
		default: 			 return $state;
	}
}


/**
 *	Function, that calculates first possible subnet from provided subnet and number of next free IP addresses
 */
function getFirstPossibleSubnet($subnet, $free, $print = true)
{
	// check IP address type (v4/v6)
	$type = IdentifyAddress( $subnet );

	// set max possible mask for IP range
	if($type == "IPv6")	{ $maxmask = 128; }
	else				{ $maxmask = 32; }

	// calculate maximum possible IP mask
	$mask = floor(log($free)/log(2));
	$mask = $maxmask - $mask;

	// we have now maximum mask. We need to verify if subnet is valid
	// otherwise add 1 to $mask and go to $maxmask
	for($m=$mask; $m<=$maxmask; $m++) {
		//validate
		$err = verifyCidr( $subnet."/".$m , 1);
		if(sizeof($err)==0) {
			//ok, it is possible!
			$result = $subnet."/".$m;
			break;
		}
	}

	//print or return?
	if($print)	print $result;
	else		return $result;
}


/**
 * Verify that switch exists
 */
function verifySwitchByName ($hostname)
{
    global $database;
    /* set check query and get result */
    $query = 'select * from `devices` where `hostname` = "'. $hostname .'";';

    /* execute */
    try { $role = $database->getRow( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return true */
    return true;
}


/**
 * Get device details by ID
 */
function getDeviceById ($deviceid)
{
	# null or 0
	if($deviceid==0 || $deviceid==null)	{
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("device", $deviceid)) {
		return $vtmp;
	}
	# query
	else {

		# query
	    global $database;
	    /* set check query and get result */
	    $query = "SELECT * from `devices` LEFT JOIN `deviceTypes` ON `devices`.`type` = `deviceTypes`.`tid` where `devices`.`id` = '$deviceid' limit 1;";

	    /* execute */
	    try { $switch = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    /* return true, else false */
	    if (!$switch) 	{ return false; }
	    else 			{ writeCache("device", $deviceid, $switch[0]); return $switch[0]; }

	}
}










/* @VLAN functions ---------------- */


/**
 * Get all VLANSs in section
 */
function getAllVlansInSection ($sectionId)
{
    global $database;
	/* execute query */
	$query = "select distinct(`v`.`vlanId`),`v`.`name`,`v`.`number`, `v`.`description` from `subnets` as `s`,`vlans` as `v` where `s`.`sectionId` = $sectionId and `s`.`vlanId`=`v`.`vlanId` order by `v`.`number` asc;";

    /* execute */
    try { $vlans = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vlans) == 0) { return false; }
	else 					{ return $vlans; }
}


/**
 *	Get All subnets inside secton with vlan
 */
function getAllSubnetsInSectionVlan ($vlanId, $sectionId, $orderType = "subnet", $orderBy = "asc")
{
    global $database;

    /* check for sorting in settings and override */
    $settings = getAllSettings();

    /* get section details to check for ordering */
    $section = getSectionDetailsById ($sectionId);

    // section ordering
    if($section['subnetOrdering']!="default" && strlen($section['subnetOrdering'])>0 ) {
	    $sort = explode(",", $section['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }
    // default - set via settings
    elseif(isset($settings['subnetOrdering']))	{
	    $sort = explode(",", $settings['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }

	/* execute query */
	$query = "select * from `subnets` where `vlanId` = '$vlanId' and `sectionId` = '$sectionId' ORDER BY `$orderType` $orderBy;";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($subnets) == 0) 	{ return false; }
	else 						{ return $subnets; }
}


/**
 *	Get All subnets inside  vlan
 */
function getAllSubnetsInVlan ($vlanId)
{
    global $database;

    /* check for sorting in settings and override */
    $settings = getAllSettings();

	/* execute query */
	$query = "select * from `subnets` where `vlanId` = '$vlanId' ORDER BY `sectionId` asc;";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($subnets) == 0) 	{ return false; }
	else 						{ return $subnets; }
}



/**
 *	Check if subnet is in vlan
 */
function isSubnetIdVlan ($subnetId, $vlanId)
{
    global $database;
	/* execute query */
	$query = "select count(*) as `cnt` from `subnets` where `vlanId` = '$vlanId' and `id` = '$subnetId';";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if($subnets[0]['cnt']==0) 	{ return false; }
	else 						{ return true; }
}


/**
 *	Validate VLAN number
 */
function validateVlan ($vlan)
{
	/* must be number:
		not 1
		reserved 1002-1005
		not higher that 4094
	*/
	if(empty($vlan)) 			{ return 'ok'; }
	elseif(!is_numeric($vlan)) { return _('VLAN must be numeric value!'); }
	elseif ($vlan > 4094) 		{ return _('Vlan number can be max 4094'); }
	else 						{ return 'ok'; }
}


/**
 *	get VLAN details by ID
 */
function getVLANbyNumber ($number)
{
    global $database;
	/* execute query */
	$query = 'select * from `vlans` where `number` = "'. $number .'";';

    /* execute */
    try { $vlan = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vlan) == 0) 	{ return false; }
	else 					{ return $vlan; }
}


/**
 *	get VLAN details by ID
 */
function getVLANbyId ($id)
{
	# null or 0
	if($id==0 || $id==null)	{
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("vlan", $id)) {
		return $vtmp;
	}
	else {

	    global $database;
		/* execute query */
		$query = 'select * from `vlans` where `vlanId` = "'. $id .'";';

	    /* execute */
	    try { $vlan = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	   	/* return false if none, else list */
		if(sizeof($vlan) == 0) 	{ return false; }
		else 					{ writeCache("vlan", $id, $vlan[0]);	return $vlan[0]; }

	}
}










/* @VRF functions ---------------- */


/**
 *	get all VRFs
 */
function getAllVRFs ()
{
    global $database;
	/* execute query */
	$query = "select * from `vrf`;";

    /* execute */
    try { $vrfs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vrfs) == 0) 	{ return false; }
	else 					{ return $vrfs; }
}


/**
 *	get vrf details by id
 */
function getVRFDetailsById ($vrfId)
{
	# null or 0
	if($vrfId==0 || $vrfId==null)	{
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("vrf", $vrfId)) {
		return $vtmp;
	}
	# check
	else {

	    global $database;
		/* execute query */
		$query = 'select * from `vrf` where `vrfId` = "'. $vrfId .'";';

	    /* execute */
	    try { $vrf = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	   	/* return false if none, else list */
		if(sizeof($vrf) == 0) 	{ return false; }
		else 					{ writeCache("vrf", $vrfId, $vrf[0]);	return $vrf[0]; }

	}
}


/**
 * Get all VRFs in section
 */
function getAllVrfsInSection ($sectionId)
{
    global $database;
	/* execute query */
	$query = "select distinct(`v`.`vrfId`),`v`.`name`,`v`.`description` from `subnets` as `s`,`vrf` as `v` where `s`.`sectionId` = $sectionId and `s`.`vrfId`=`v`.`vrfId` order by `v`.`name` asc;";

    /* execute */
    try { $vrfs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vrfs) == 0) 	{ return false; }
	else 					{ return $vrfs; }
}


/**
 *	Get All subnets inside secton with vlan
 */
function getAllSubnetsInSectionVRF ($vrfId, $sectionId, $orderType = "subnet", $orderBy = "asc")
{
    global $database;
    /* check for sorting in settings and override */
    $settings = getAllSettings();

    /* get section details to check for ordering */
    $section = getSectionDetailsById ($sectionId);

    // section ordering
    if($section['subnetOrdering']!="default" && strlen($section['subnetOrdering'])>0 ) {
	    $sort = explode(",", $section['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }
    // default - set via settings
    elseif(isset($settings['subnetOrdering']))	{
	    $sort = explode(",", $settings['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }

	/* execute query */
	$query = "select * from `subnets` where `vrfId` = '$vrfId' and `sectionId` = '$sectionId' ORDER BY `$orderType` $orderBy;";

    /* execute */
    try { $vrfs = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vrfs) == 0) 	{ return false; }
	else 					{ return $vrfs; }
}


/**
 *	Check if subnet is in vlan
 */
function isSubnetIdVrf ($subnetId, $vrfId)
{
    global $database;
	/* execute query */
	$query = "select count(*) as `cnt` from `subnets` where `vrfId` = '$vrfId' and `id` = '$subnetId';";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if($subnets[0]['cnt']==0) 	{ return false;  }
	else 						{ return true; }
}










/* @section functions ---------------- */


/**
 * Get all sections
 */
function fetchSections ($all = true)
{
    global $database;
    /* set query */
    if($all) 	{ $query = 'select SQL_CACHE * from `sections` order by IF(ISNULL(`order`),1,0),`order`,`id` asc;'; }
    else		{ $query = 'select SQL_CACHE * from `sections` where `masterSection` = 0 order by IF(ISNULL(`order`),1,0),`order`,`id` asc;'; }

    /* execute */
    try { $sections = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($sections);
}


/**
 * Get number of sections
 */
function getNumberOfSections ()
{
    global $database;
    /* set query */
    $query 	  = 'select count(*) as count from `sections`;';

    /* execute */
    try { $sections = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($sections[0]['count']);
}


/**
 * Get section details - provide section id
 */
function getSectionDetailsById ($id)
{
	# null or 0
	if($id==0 || $id==null)	{
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("section", $id)) {
		return $vtmp;
	}
	# query
	else {
	    global $database;

		/* cront errors */
		if(isset($database->error)) {
			unset($database);
			global $db;
			$database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
		}


	    /* set query, open db connection and fetch results */
	    $query 	  = 'select * from sections where id = "'. $id .'";';

	    /* execute */
	    try { $section = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    /* return section */
	    if(sizeof($section) > 0)	{ writeCache("section", $id, $section[0]); return($section[0]); }
    }
}


/**
 * Get section details - provide section name
 */
function getSectionDetailsByName ($name)
{
	# null or 0
	if($name==0 || strlen($name)==0)	{
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("section", $name)) {
		return $vtmp;
	}
	# query
	else {
	    global $database;
	    /* set query, open db connection and fetch results */
	    $query 	  = 'select * from sections where `name` = "'. $name .'";';

	    /* execute */
	    try { $subnets = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		writeCache("section", $name, $subnets[0]);
	    /* return subnets array */
	    return($subnets[0]);
    }
}


/**
 *	Get all subsections
 */
function getAllSubSections($sectionId)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query 	  = "select * from `sections` where `masterSection` = '$sectionId';";

    /* execute */
    try { $sections = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* cache them */
	if(sizeof($sections)>0) {
		foreach($sections as $s) {
			writeCache("section", $s['id'], $s);
		}
	}

    /* return subnets array */
    return($sections);
}


/**
 *	Count number of IP addresses in section
 *
 *		we privede array of all subnet Id's
 */
function countAllIPinSection ($subnets)
{
	global $database;
	# create query
	$query = "select count(*) as `cnt` from `ipaddresses` where ";
	foreach($subnets as $k=>$s) {
		if($k==(sizeof($subnets)-1)) 	{ $query .= "`subnetId`=$s[id] "; }
		else							{ $query .= "`subnetId`=$s[id] or "; }
	}
	$query .= ";";

    /* execute */
    try { $sections = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return $sections[0]['cnt'];
}










/* @subnet functions ---------------- */


/**
 * Get all subnets
 */
function fetchAllSubnets ()
{
    global $database;
    /* set query */
    $query 	  = 'select * from subnets;';

    /* execute */
    try { $sections = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($sections);
}


/**
 * Get number of subnets
 */
function getNumberOfSubnets ()
{
    global $database;
    /* set query */
    $query 	  = 'select count(*) as count from subnets;';

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($subnets[0]['count']);
}



/**
 * Get all subnets in provided sectionId
 */
function fetchSubnets ($sectionId, $orderType = "subnet", $orderBy = "asc" )
{
    global $database;
    /* check for sorting in settings and override */
    $settings = getAllSettings();

    /* get section details to check for ordering */
    $section = getSectionDetailsById ($sectionId);

    // section ordering
    if($section['subnetOrdering']!="default" && strlen($section['subnetOrdering'])>0 ) {
	    $sort = explode(",", $section['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }
    // default - set via settings
    elseif(isset($settings['subnetOrdering']))	{
	    $sort = explode(",", $settings['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }

    /* set query, open db connection and fetch results */
    $query 	  = "select * from `subnets` where `sectionId` = '$sectionId' ORDER BY `isFolder` desc,`masterSubnetId`,`$orderType` $orderBy;";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($subnets);
}


/**
 * Get all master subnets in provided sectionId
 */
function fetchMasterSubnets ($sectionId)
{
    global $database;
    # set query, open db connection and fetch results
    $query 	  = 'select * from subnets where sectionId = "'. $sectionId .'" and (`masterSubnetId` = "0" or `masterSubnetId` IS NULL) ORDER BY subnet ASC;';

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    # return subnets array
    return($subnets);
}


/**
 * Get all slave subnets in provided subnetId
 */
function getAllSlaveSubnetsBySubnetId ($subnetId)
{
	# check cache
	if($vtmp = checkCache("ipaddressSlaves", $subnetId)) {
		return $vtmp;
	}
	else {
	    global $database;
	    # set query, open db connection and fetch results
	    $query 	  = 'select * from subnets where `masterSubnetId` = "'. $subnetId .'" ORDER BY subnet ASC;';

	    /* execute */
	    try { $subnets = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		# save cache
		if(sizeof($subnets)>0) {
			writeCache("ipaddressSlaves", $subnetId, $subnets);
		}

	    # return subnets array
	    return($subnets);
    }
}


/**
 * Get all ip addresses in requested subnet by provided Id
 */
function getIpAddressesBySubnetId ($subnetId)
{
	# check cache
	if($vtmp = checkCache("ipaddresses", $subnetId)) {
		return $vtmp;
	}
	else {

	    global $database;
	    /* set query, open db connection and fetch results */
	    $query       = 'select * from `ipaddresses` where subnetId = "'. $subnetId .'" order by `ip_addr` ASC;';

	    /* execute */
	    try { $ipaddresses = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		# save cache
		if(sizeof($ipaddresses)>0) {
			writeCache("ipaddresses", $subnetId, $ipaddresses);
		}

	    /* return ip address array */
	    return($ipaddresses);
    }
}


/**
 * Get all ip addresses in requested subnet by provided Id, sort by fieldname and direction!
 */
function getIpAddressesBySubnetIdSort ($subnetId, $fieldName, $direction)
{
	# check cache
	if($vtmp = checkCache("ip_sorted", $subnetId."_$fieldName"."_$direction")) {
		return $vtmp;
	}
	else {

	    global $database;
	    /* set query, open db connection and fetch results */
	    $query       = 'select * from `ipaddresses` where subnetId = "'. $subnetId .'" order by `'. $fieldName .'` '. $direction .';';

	    /* execute */
	    try { $ipaddresses = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		# save cache
		if(sizeof($ipaddresses)>0) {
			writeCache("ip_sorted", $subnetId."_$fieldName"."_$direction", $ipaddresses);
		}

	    /* return ip address array */
	    return($ipaddresses);
    }
}


/**
 * Get all ip addresses in requested subnet by provided Id, sort by fieldname and direction!
 */
function getIpAddressesBySubnetIdslavesSort ($subnetId, $fieldName = "subnetId", $direction = "asc")
{

	# check cache
	if($vtmp = checkCache("ip_slaves_sorted", $subnetId."_$fieldName"."_$direction")) {
		return $vtmp;
	}
	else {
	    global $database;
	    /* get ALL slave subnets, then remove all subnets and IP addresses */
	    global $removeSlaves;

	    getAllSlaves ($subnetId);
	    $removeSlaves = array_unique($removeSlaves);

	    /* set query, open db connection and fetch results */
	    $query       = 'select * from `ipaddresses` where subnetId = "" ';
	    foreach($removeSlaves as $subnetId2) {
	    	if($subnetId2 != $subnetId) {					# ignore orphaned
		    $query  .= " or `subnetId` = '$subnetId2' ";
		    }
	    }

	    $query      .= 'order by `'. $fieldName .'` '. $direction .';';

	    /* execute */
	    try { $ipaddresses = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		# save cache
		if(sizeof($ipaddresses)>0) {
			writeCache("ip_slaves_sorted", $subnetId."_$fieldName"."_$direction", $ipaddresses);
		}

	    /* return ip address array */
	    return($ipaddresses);
    }
}


/**
 * Count all ip addresses in requested subnet by provided Id
 *
 *	if $per_state return count by status!
 */
function countAllSlaveIPAddresses ($subnetId, $perState = false)
{
	# check cache
	if($vtmp = checkCache("ip_count_all_slave_ips_r", $subnetId."$perstate")) {
		return $vtmp;
	}
	else {
	    global $database;
	    /* get ALL slave subnet Ids, then exclude duplicates */

	    $allSlaveSubnets = getAllSlavesReturn ($subnetId);
	    $allSlaveSubnets = array_unique($allSlaveSubnets);

	    /* set query, open db connection and fetch results */
	    $query       = 'select count(*) as `cnt`,`state` from `ipaddresses` where subnetId = "" ';
	    foreach($allSlaveSubnets as $subnetId2) {
	    	if($subnetId2 != $subnetId) {					# ignore orphaned
		    $query  .= " or `subnetId` = '$subnetId2' ";
		    }
	    }
	    # per-state?
	    if($perState)	{
	    	$query      .= 'group by `state` asc;';
	    }
	    else {
	    	$query      .= ';';
	    }

	    /* execute */
	    try { $ipaddresses = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		# save cache
		if(sizeof($ipaddresses)>0) {
			writeCache("ip_count_all_slave_ips_r", $subnetId, $ipaddresses[0]['cnt']);
		}
	    /* return ip address array */
	    if($perState)	{ return($ipaddresses[0]);  }
	    else 			{ return($ipaddresses[0]['cnt']);  }
    }
}



/**
 * Get all ip addresses in requested subnet by provided Id for visual display
 */
function getIpAddressesForVisual ($subnetId)
{
	$ipaddresses = getIpAddressesBySubnetId ($subnetId);

    $out = array();

    /* reformat array */
    foreach($ipaddresses as $ip) {
	    $out[$ip['ip_addr']]['state'] 		= $ip['state'];
	    $out[$ip['ip_addr']]['id']    		= $ip['id'];
	    $out[$ip['ip_addr']]['ip_addr']    	= $ip['ip_addr'];
	    $out[$ip['ip_addr']]['desc']  		= $ip['description'];
	    $out[$ip['ip_addr']]['dns_name']  	= $ip['dns_name'];
    }

    /* return ip address array */
    return($out);
}


/**
 * Compress DHCP ranges
 */
function compressDHCPranges ($ipaddresses)
{
	//loop through IP addresses
	for($c=0; $c<sizeof($ipaddresses); $c++) {
		// gap between this and previous
		if(gmp_strval( @gmp_sub($ipaddresses[$c]['ip_addr'], $ipaddresses[$c-1]['ip_addr'])) != 1) {
			//remove index flag
			unset($fIndex);
			//save IP address
			$ipFormatted[$c] = $ipaddresses[$c];
			$ipFormatted[$c]['class'] = "ip";

			// no gap this -> next
			if(gmp_strval( @gmp_sub($ipaddresses[$c]['ip_addr'], $ipaddresses[$c+1]['ip_addr'])) == -1 && $ipaddresses[$c]['state']==3) {
				//is state the same?
				if($ipaddresses[$c]['state']==$ipaddresses[$c+1]['state']) {
					$fIndex = $c;
					$ipFormatted[$fIndex]['startIP'] = $ipaddresses[$c]['ip_addr'];
					$ipFormatted[$c]['class'] = "range-dhcp";
				}
			}
		}
		// no gap between this and previous
		else {
			// is state same as previous?
			if($ipaddresses[$c]['state']==$ipaddresses[$c-1]['state'] && $ipaddresses[$c]['state']==3) {
				//add stop IP
				$ipFormatted[$fIndex]['stopIP'] = $ipaddresses[$c]['ip_addr'];
				//add range span
				$ipFormatted[$fIndex]['numHosts'] = gmp_strval( gmp_add(@gmp_sub($ipaddresses[$c]['ip_addr'], $ipFormatted[$fIndex]['ip_addr']),1));
			}
			// different state
			else {
				//remove index flag
				unset($fIndex);
				//save IP address
				$ipFormatted[$c] = $ipaddresses[$c];
				$ipFormatted[$c]['class'] = "ip";

				//check if state is same as next to start range
				if($ipaddresses[$c]['state']==@$ipaddresses[$c+1]['state'] &&  gmp_strval( @gmp_sub($ipaddresses[$c]['ip_addr'], $ipaddresses[$c+1]['ip_addr'])) == -1 && $ipaddresses[$c]['state']==3) {
					$fIndex = $c;
					$ipFormatted[$fIndex]['startIP'] = $ipaddresses[$c]['ip_addr'];
					$ipFormatted[$c]['class'] = "range-dhcp";
				}
			}
		}
	}
	//overrwrite ipaddresses and rekey
	$ipaddresses = @array_values($ipFormatted);

	//return
	return $ipaddresses;
}



/**
 * Count number of ip addresses in provided subnet
 */
function countIpAddressesBySubnetId ($subnetId)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query       = 'select count(*) from ipaddresses where `subnetId` = "'. $subnetId .'" order by subnetId ASC;';

    /* execute */
    try { $count = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* we only need count field */
    $count	= $count[0]['count(*)'];

    /* return ip address array */
    return($count);
}


/**
 * Get details for requested subnet by Id
 */
function getSubnetDetails ($subnetId)
{
    global $database;

    /* cront errors */
    if(isset($database->error)) {
	    unset($database);
	    global $db;
	    $database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
    }

    /* set query, open db connection and fetch results */
    $query         = 'select * from subnets where id = "'. $subnetId .'";';

    /* execute */
    try { $SubnetDetails = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
    if(sizeof($SubnetDetails) > 0)	{ return($SubnetDetails[0]); }
}


/**
 * Get details for requested subnet by ID
 */
function getSubnetDetailsById ($id)
{
	# for changelog
	if($id=="subnetId") {
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("subnet", $id)) {
		return $vtmp;
	}
	# query
	else {

	    global $database;
	    /* set query */
	    $query         = 'select * from `subnets` where `id` = "'. $id .'";';

	    /* execute */
	    try { $SubnetDetails = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
	    if(sizeof($SubnetDetails) > 0) {
	    	writeCache('subnet', $id, $SubnetDetails[0]);
	    	return($SubnetDetails[0]);
	    }

	}
}


/**
 * Get all subnets to be discovered
 */
function getSubnetsToDiscover ()
{
    global $database;
    /* set query */
    $query         = 'select * from `subnets` where `discoverSubnet` = "1";';

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    # set vars
    $ip = array();		//we store IPs to scan to this array

    # ok, we have subnets. Now we create array of all possible IPs for each subnet,
    # and remove all existing
    $address_index = 1;
    foreach($subnets as $s) {
	   	// get all existing IP addresses
	   	$addresses = getIpAddressesBySubnetId ($s['id']);

	   	// set start and end IP address
	   	$calc = calculateSubnetDetailsNew ( $s['subnet'], $s['mask'], 0, 0, 0, 0 );
	   	// loop and get all IP addresses for ping
		for($m=1; $m<=$calc['maxhosts']; $m++) {
			// save to array for return
			$ip[$address_index]['ip_addr']  = $s['subnet']+$m;
			$ip[$address_index]['subnetId'] = $s['id'];
			// save to array for existing check
			$ipCheck[$address_index] = $s['subnet']+$m;
			//next index
			$address_index++;
		}

		// remove already existing
		foreach($addresses as $a) {
			$key = array_search($a['ip_addr'], $ipCheck);
			if($key!==false) {
				unset($ip[$key]);
			}
		}
    }

    # return result
    return $ip;
}


/**
 *	Insert newly discovered IP address form cron script
 */
function insert_discovered_ip ($ip)
{
    if(!is_object($database)) {
		global $db;
		$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);

    } else {
	    global $database;
    }

    /* set query */
    $query         = "insert into `ipaddresses` (`ip_addr`,`dns_name`,`subnetId`,`description`,`state`,`note`,`lastSeen`) values ('$ip[ip_addr]','$ip[dns_name]','$ip[subnetId]','-- autodiscovered --','1','This host was autodiscovered on ".date("Y-m-d H:i:s")."', NOW());";

    /* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print $error."\n";
        return false;
    }

    return true;

}



/**
 * Calculate subnet details
 *
 * Calculate subnet details based on input!
 *
 * We must provide used hosts and subnet mask to calculate free hosts, and subnet to identify type
 */
function calculateSubnetDetails ( $usedhosts, $bitmask, $subnet )
{
    // number of used hosts
    $SubnetCalculateDetails['used']              = $usedhosts;

    // calculate max hosts
    if ( IdentifyAddress( $subnet ) == "IPv4") 	{ $type = 0; }
    else 										{ $type = 1; }

    $SubnetCalculateDetails['maxhosts']          = MaxHosts( $bitmask, $type );

    // calculate free hosts
    $SubnetCalculateDetails['freehosts']         = gmp_strval( gmp_sub ($SubnetCalculateDetails['maxhosts'] , $SubnetCalculateDetails['used']) );

	//reset maxhosts for /31 and /32 subnets
	if (gmp_cmp($SubnetCalculateDetails['maxhosts'],1) == -1) {
		$SubnetCalculateDetails['maxhosts'] = "1";
	}

    // calculate use percentage
    if($type==0) { $SubnetCalculateDetails['freehosts_percent'] = round( ( ($SubnetCalculateDetails['freehosts'] * 100) / $SubnetCalculateDetails['maxhosts']), 2 ); }
    else		 { $SubnetCalculateDetails['freehosts_percent'] = round( ( ($SubnetCalculateDetails['freehosts'] * 100) / $SubnetCalculateDetails['maxhosts']), 2 ); }

    return( $SubnetCalculateDetails );
}


/**
 * Calculate subnet details
 *
 * Calculate subnet details based on input!
 *
 * We must provide used hosts and subnet mask to calculate free hosts, and subnet to identify type
 *
 *	$bcastfix = remove bcast and subnets from stats (subnetDetailsGraph)
 */
function calculateSubnetDetailsNew ( $subnet, $bitmask, $online, $offline, $reserved, $dhcp, $bcastfix = 0 )
{
    $details['online']            = $online;		// number of online hosts
    $details['reserved']          = $reserved;		// number of reserved hosts
    $details['offline']           = $offline;		// number of offline hosts
    $details['dhcp']              = $dhcp;   		// number of dhcp hosts

    $details['used']			  = gmp_strval( gmp_add ($online,$reserved) );
    $details['used']			  = gmp_strval( gmp_add ($details['used'],$offline) );
    $details['used']			  = gmp_strval( gmp_add ($details['used'],$dhcp) );

    // calculate max hosts
    if ( IdentifyAddress( $subnet ) == "IPv4") 	{ $type = 0; }
    else 										{ $type = 1; }

    $details['maxhosts']          = MaxHosts( $bitmask, $type );
    $details['maxhosts'] 		  = gmp_strval( gmp_sub ($details['maxhosts'],$bcastfix) );

    // calculate free hosts
    $details['freehosts']         = gmp_strval( gmp_sub ($details['maxhosts'] , $details['used']) );

	//reset maxhosts for /31 and /32 subnets
	if (gmp_cmp($details['maxhosts'],1) == -1) {
		$details['maxhosts'] = "1";
	}

    // calculate use percentage
    $details['freehosts_percent'] = round( ( ($details['freehosts'] * 100) / $details['maxhosts']), 2 );
    $details['used_percent'] 	  = round( ( ($details['used'] * 100) / $details['maxhosts']), 2 );
    $details['online_percent'] 	  = round( ( ($details['online'] * 100) / $details['maxhosts']), 2 );
    $details['reserved_percent']  = round( ( ($details['reserved'] * 100) / $details['maxhosts']), 2 );
    $details['offline_percent']   = round( ( ($details['offline'] * 100) / $details['maxhosts']), 2 );
    $details['dhcp_percent']      = round( ( ($details['dhcp'] * 100) / $details['maxhosts']), 2 );

    return( $details );
}



/**
 * Check if subnet already exists in section!
 *
 * Subnet policy:
 *      - inside section subnets cannot overlap!
 *      - same subnet can be configured in different sections
 */
function verifySubnetOverlapping ($sectionId, $subnetNew, $vrfId = 0)
{
    /* we need to get all subnets in section */
    global $database;
    /* first we must get all subnets in section (by sectionId) */
    $querySubnets     = 'select `subnet`,`mask`,`vrfId`,`description` from subnets where sectionId = "'. $sectionId .'";';

    /* execute */
    try { $allSubnets = $database->getArray( $querySubnets ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* set new Subnet array */
    $subnet['subnet'] = $subnetNew;

    /* IPv4 or ipv6? */
    $type = IdentifyAddress( $subnet['subnet'] );

    /* we need network and broadcast address and check for both if the exist in any network!*/
    if ($type == "IPv4")
    {
        /* verify new against each existing if they exist */
        if (!empty($allSubnets)) {
            foreach ($allSubnets as $existingSubnet) {

            	/* we need cidr format! */
            	$existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) .'/'. $existingSubnet['mask'];

                /* only check if vrfId's match */
                if($existingSubnet['vrfId'] == $vrfId) {
	                if ( verifyIPv4SubnetOverlapping ($subnetNew, $existingSubnet['subnet']) ) {
	                    return _('Subnet overlapps with').' '. $existingSubnet['subnet']." ($existingSubnet[description])";
	                }
	            }
	        }
        }
    }
    else
    {
        /* verify new against each existing */
        foreach ($allSubnets as $existingSubnet) {

            /* we need cidr format! */
            $existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) .'/'. $existingSubnet['mask'];

            /* only check if vrfId's match */
            if($existingSubnet['vrfId'] == $vrfId) {
        	    if ( verifyIPv6SubnetOverlapping ($subnetNew, $existingSubnet['subnet']) ) {
            	    return _('Subnet overlapps with').' '. $existingSubnet['subnet']." ($existingSubnet[description])";
            	}
            }
        }
    }
    return false;
}


/**
 * Check if nested subnet already exists in section!
 *
 * Subnet policy:
 *      - inside section subnets cannot overlap!
 *      - same subnet can be configured in different sections
 *		- if vrf is same do checks, otherwise skip
 *		- mastersubnetid we need for new checks to permit overlapping of nested clients
 */
function verifyNestedSubnetOverlapping ($sectionId, $subnetNew, $vrfId, $masterSubnetId = 0)
{
    /* we need to get all subnets in section */
    global $database;

    /* first we must get all subnets in section (by sectionId) */
//     $querySubnets     = 'select `id`,`subnet`,`mask`,`description`,`vrfId` from `subnets` where sectionId = "'. $sectionId .'" and `masterSubnetId` != "0" and `masterSubnetId` IS NOT NULL;';
    $querySubnets     = 'select `id`,`subnet`,`mask`,`description`,`vrfId` from `subnets` where sectionId = "'. $sectionId .'" and `masterSubnetId` = '.$masterSubnetId.';';

    /* execute */
    try { $allSubnets = $database->getArray( $querySubnets ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* set new Subnet array */
    $subnet['subnet'] = $subnetNew;

    /* IPv4 or ipv6? */
    $type = IdentifyAddress( $subnet['subnet'] );

    /* we need network and broadcast address and check for both if the exist in any network!*/
    if ($type == "IPv4")
    {
        /* verify new against each existing if they exist */
        if (!empty($allSubnets)) {
            foreach ($allSubnets as $existingSubnet) {

            	/* we need cidr format! */
            	$existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) .'/'. $existingSubnet['mask'];

                /* only check if vrfId's match */
                if($existingSubnet['vrfId'] == $vrfId) {
                	# check if it is nested properly - inside its own parent, otherwise check for overlapping
                	$allParents = getAllParents ($masterSubnetId);
                	foreach($allParents as $kp=>$p) {
	                	if($existingSubnet['id'] == $kp) {
		                	$ignore = true;
	                	}
                	}
                	if($ignore == false)  {
                		if ( verifyIPv4SubnetOverlapping ($subnetNew, $existingSubnet['subnet']) ) {
                    		return _('Subnet overlapps with').' '. $existingSubnet['subnet']." ($existingSubnet[description])";
                    	}
                    }
                }
            }
        }
    }
    else
    {
        /* verify new against each existing */
        foreach ($allSubnets as $existingSubnet) {

            /* we need cidr format! */
            $existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) .'/'. $existingSubnet['mask'];

            /* only check if vrfId's match */
            if($existingSubnet['vrfId'] == $vrfId) {
                # check if it is nested properly - inside its own parent, otherwise check for overlapping
                $allParents = getAllParents ($masterSubnetId);
                foreach($allParents as $kp=>$p) {
	               	if($existingSubnet['id'] == $kp) {
		               	$ignore = true;
	               	}
                }
                if($ignore == false)  {
        	    	if ( verifyIPv6SubnetOverlapping ($subnetNew, $existingSubnet['subnet']) ) {
            	    	return _('Subnet overlapps with').' '. $existingSubnet['subnet']." ($existingSubnet[description])";
            	    }
            	}
            }
        }
    }

    return false;
}


/**
 * verify ip address /mask 10.10.10.10./24 - CIDR
 *
 * if subnet == 0 we dont check if IP is subnet -> needed for ipCalc
 */
function getSubnetNetworkAddress($newSubnet) {
    $type = IdentifyAddress($cidr);

    /* IPv4 */
    if ($type == "IPv4") {
        $resized = getIpv4NetworkAddress($newSubnet);
        if (verifyCidr($resized, 0)) {
            return false;
        }
    } else {
        // TODO: IPv6 not yet supported here
        return false;
    }
    return $resized;
}


function getIpv4NetworkAddress($cidr) {
    /* split it to network and subnet */
    $temp = explode("/", $cidr);

    $ip = $temp[0];
    $netmask = $temp[1];

    $netaddr = long2ip((ip2long($ip)) & ((-1 << (32 - (int) $netmask))));

    return $netaddr . "/" . $netmask;
}




/**
 * Check if resized subnet already exists in section!
 *
 * Subnet policy:
 *      - inside section subnets cannot overlap!
 *      - same subnet can be configured in different sections
 *              - $subnetNew is exception because it is the
 */
function verifyResizedSubnetOverlapping($subnetOld, $subnetNew) {
    /* we need to get all subnets in section */
    global $db;
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);

    /* first we must get all subnets in section (by sectionId) */
    $querySubnets = 'select `id`,`subnet`,`mask`,`description`,`vrfId` from `subnets` where sectionId = "' . $subnetOld['sectionId'] . '" and `masterSubnetId` != "0" and `masterSubnetId` IS NOT NULL;';

    /* execute */
    try {
        $allSubnets = $database->getArray($querySubnets);
    } catch (Exception $e) {
        $error = $e->getMessage();
        print ("<div class='alert alert-danger'>" . _('Error') . ": $error</div>");
        return false;
    }

    /* set new Subnet array */
    $subnet['subnet'] = $subnetNew;

    /* IPv4 or ipv6? */
    $type = IdentifyAddress($subnet['subnet']);

    /* we need network and broadcast address and check for both if the exist in any network! */
    if ($type == "IPv4") {
        /* verify new against each existing if they exist */
        if (!empty($allSubnets)) {
            foreach ($allSubnets as $existingSubnet) {

                /* we need cidr format! */
                $existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) . '/' . $existingSubnet['mask'];
                $ignore = false;
                /* only check if vrfId's match */
                if ($existingSubnet['vrfId'] == $subnetOld['vrfId']) {
                    # check if it is nested properly - inside its own parent, otherwise check for overlapping
                    $allParents = getAllParents($subnetOld['masterSubnetId']);
                    foreach ($allParents as $kp => $p) {
                        if ($existingSubnet['id'] == $p) {
                            $ignore = true;
                        }
                    }
                    if ($subnetOld['masterSubnetId'] == $existingSubnet['id']) {
                        $ignore = true;
                    }
                    # exclude subnet to be resized from checking
                    if ($subnetOld['id'] == $existingSubnet['id']) {
                        $ignore = true;
                    }
                    if ($ignore == false) {
                        if (verifyIPv4SubnetOverlapping($subnetNew, $existingSubnet['subnet'])) {
                            return _('Subnet overlapps with') . ' ' . $existingSubnet['subnet'] . " ($existingSubnet[description])";
                        }
                    }
                }
            }
        }
    } else {
        /* verify new against each existing */
        foreach ($allSubnets as $existingSubnet) {

            /* we need cidr format! */
            $existingSubnet['subnet'] = Transform2long($existingSubnet['subnet']) . '/' . $existingSubnet['mask'];

            /* only check if vrfId's match */
            if ($existingSubnet['vrfId'] == $subnetOld['vrfId']) {
                # check if it is nested properly - inside its own parent, otherwise check for overlapping
                $allParents = getAllParents($subnetOld['masterSubnetId']);
                foreach ($allParents as $kp => $p) {
                    if ($existingSubnet['id'] = $kp) {
                        $ignore = true;
                    }
                }
                if ($ignore == false) {
                    if (verifyIPv6SubnetOverlapping($subnetNew, $existingSubnet['subnet'])) {
                        return _('Subnet overlapps with') . ' ' . $existingSubnet['subnet'] . " ($existingSubnet[description])";
                    }
                }
            }
        }
    }

    return false;
}




/**
 * Check if subnet contains slaves
 */
function subnetContainsSlaves($subnetId)
{
	# we need new temp variable for empties
	$subnetIdtmp = $subnetId;
	if(strlen($subnetIdtmp)==0)	{ $subnetIdtmp="root"; }
	# check if already in cache
	if($vtmp = checkCache("subnetcontainsslaves", $subnetIdtmp)) {
		return $vtmp;
	}
	# query
	else {

	    global $database;

	    /* get all ip addresses in subnet */
	    $query 		  = 'SELECT count(*) from `subnets` where `masterSubnetId` = "'. $subnetId .'";';

	    /* execute */
	    try { $slaveSubnets = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		if($slaveSubnets[0]['count(*)']) { writeCache("subnetcontainsslaves", $subnetIdtmp, true);	return true; }
		else 							 { writeCache("subnetcontainsslaves", $subnetIdtmp, false);	return false; }

	}
}


/**
 * Verify IPv4 subnet overlapping
 *
 * both must be in CIDR format (10.4.5.0/24)!
 *
 */
function verifyIPv4SubnetOverlapping ($subnet1, $subnet2)
{
    /* IPv4 functions */
    require_once('PEAR/Net/IPv4.php');
    $Net_IPv4 = new Net_IPv4();

    /* subnet 2 needs to be parsed to get subnet and broadcast */
    $net1 = $Net_IPv4->parseAddress( $subnet1 );
    $net2 = $Net_IPv4->parseAddress( $subnet2 );

    /* network and broadcast */
    $nw1  = $net1->network;
    $nw2  = $net2->network;
    $bc1  = $net1->broadcast;
    $bc2  = $net2->broadcast;

    /* network and broadcast in decimal format */
    $nw1_dec  = Transform2decimal( $net1->network);
    $nw2_dec  = Transform2decimal( $net2->network);
    $bc1_dec  = Transform2decimal( $net1->broadcast);
    $bc2_dec  = Transform2decimal( $net2->broadcast);

    /* calculate delta */
    $delta1 = $bc1_dec - $nw1_dec;
    $delta2 = $bc2_dec - $nw2_dec;

    /* calculate if smaller is inside bigger */
    if ($delta1 < $delta2)
    {
        /* check smaller nw and bc against bigger network */
        if ( $Net_IPv4->ipInNetwork($nw1, $subnet2) || $Net_IPv4->ipInNetwork($bc1, $subnet2) ) { return true; }
    }
    else
    {
        /* check smaller nw and bc against bigger network */
        if ( $Net_IPv4->ipInNetwork($nw2, $subnet1) || $Net_IPv4->ipInNetwork($bc2, $subnet1) ) { return true; }
    }
    return false;
}


/**
 * Verify IPv6 subnet overlapping
 *
 * both must be in CIDR format (2001:fee1::/48)!
 *      subnet1 will be checked against subnet2
 *
 */
function verifyIPv6SubnetOverlapping ($subnet1, $subnet2)
{
    /* IPv6 functions */
    require_once('PEAR/Net/IPv6.php');

    $Net_IPv6 = new Net_IPv6();

    /* remove netmask from subnet1 */
    $subnet1 = $Net_IPv6->removeNetmaskSpec ($subnet1);

    /* verify */
    if ($Net_IPv6->isInNetmask ( $subnet1 , $subnet2 ) ) {
        return true;
    }

    return false;
}


/**
 * Verify that new nested subnet is inside master subnet!
 *
 * $root = root subnet Id
 * $new  = new subnet that we wish to add to root subnet
 */
function verifySubnetNesting ($rootId, $new)
{
	//first get details for root subnet
	$rootDetails = getSubnetDetailsById($rootId);
	$rootDetails = Transform2long($rootDetails['subnet']) . "/" . $rootDetails['mask'];

    /* IPv4 or ipv6? */
    $type1 = IdentifyAddress( $rootDetails );
    $type2 = IdentifyAddress( $new );

    /* both must be IPv4 or IPv6 */
	if($type1 != $type2) {
		return false;
		die();
	}

    /* we need network and broadcast address and check for both if the exist in any network!*/
    if(isSubnetInsideSubnet ($new, $rootDetails)) 	{ return true; }
    else 											{ return false; }
}


/**
 * Verify that subnet a is inside subnet b!
 *
 * both subnets must be in ip format (e.g. 10.10.10.0/24)
 */
function isSubnetInsideSubnet ($subnetA, $subnetB)
{
	$type = IdentifyAddress( $subnetA );

	/* IPv4 */
	if ($type == "IPv4") {

    	/* IPv4 functions */
    	require_once('PEAR/Net/IPv4.php');
    	$Net_IPv4 = new Net_IPv4();

    	/* subnet A needs to be parsed to get subnet and broadcast */
    	$net = $Net_IPv4->parseAddress( $subnetA );

		//both network and broadcast must be inside root subnet!
		if( ($Net_IPv4->ipInNetwork($net->network, $subnetB)) && ($Net_IPv4->ipInNetwork($net->broadcast, $subnetB)) )  { return true; }
		else 																											{ return false; }
	}
	/* IPv6 */
	else {
    	/* IPv6 functions */
    	require_once('PEAR/Net/IPv6.php');
    	$Net_IPv6 = new Net_IPv6();

    	/* remove netmask from subnet1 */
    	$subnetA = $Net_IPv6->removeNetmaskSpec ($subnetA);

	    /* verify */
    	if ($Net_IPv6->isInNetmask ( $subnetA, $subnetB ) ) { return true; }
    	else 												{ return false; }
	}
}


/**
 * Check if subnet is admin-locked
 */
function isSubnetWriteProtected($subnetId)
{
    global $database;

    /* first update request */
    $query    = 'select `adminLock` from subnets where id = '. $subnetId .';';

	/* execute */
    try { $lock = $database->getArray($query); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	/* return true if locked */
	if($lock[0]['adminLock'] == 1) 	{ return true; }
	else 							{ return false; }
}


/**
 * get all Subnets - for hosts export
 */
function getAllSubnetsForExport()
{
    global $database;
    /* first update request */
    $query    = 'select `s`.`id`,`subnet`,`mask`,`name`,`se`.`description` as `se_description`,`s`.`description` as `s_description` from `subnets` as `s`,`sections` as `se` where `se`.`id`=`s`.`sectionId` order by `se`.`id` asc;';

	/* execute */
    try { $subnets = $database->getArray($query); }
    catch (Exception $e) {
    	return false;
    }

	/* return true if locked */
	return $subnets;
}



/**
 *	Print dropdown menu for subnets in section!
 */
function printDropdownMenuBySection($sectionId, $subnetMasterId = "0")
{
		# get all subnets
		$subnets = fetchSubnets ($sectionId);
		$folders = fetchFolders ($sectionId);

		$html = array();

		$rootId = 0;									# root is 0

		# must be integer
		if(isset($_GET['subnetId']))	{ if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }

		# folders
		foreach ( $folders as $item )
			$childrenF[$item['masterSubnetId']][] = $item;

		# subnets
		foreach ( $subnets as $item )
			$children[$item['masterSubnetId']][] = $item;

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loopF = !empty( $childrenF[$rootId] );
		$loop  = !empty( $children[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;

		$parent_stackF = array();
		$parent_stack  = array();

		# display selected subnet as opened
		$allParents = getAllParents ($_GET['subnetId']);


		# structure
		$html[] = "<select name='masterSubnetId' class='form-control input-sm input-w-auto input-max-200'>";

		# folders
		if(sizeof($folders)>0) {
			$html[] = "<optgroup label='"._("Folders")."'>";
			# return table content (tr and td's) - folders
			while ( $loopF && ( ( $option = each( $childrenF[$parent] ) ) || ( $parent > $rootId ) ) )
			{
				# repeat
				$repeat  = str_repeat( " - ", ( count($parent_stackF)) );
				# dashes
				if(count($parent_stackF) == 0)	{ $dash = ""; }
				else							{ $dash = $repeat; }

				# count levels
				$count = count( $parent_stackF ) + 1;

				# print table line
				if(strlen($option['value']['subnet']) > 0) {
					# selected
					if($option['value']['id'] == $subnetMasterId) 	{ $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$option['value']['description']."</option>"; }
					else 											{ $html[] = "<option value='".$option['value']['id']."'>$repeat ".$option['value']['description']."</option>"; }
				}

				if ( $option === false ) { $parent = array_pop( $parent_stackF ); }
				# Has slave subnets
				elseif ( !empty( $childrenF[$option['value']['id']] ) ) {
					array_push( $parent_stackF, $option['value']['masterSubnetId'] );
					$parent = $option['value']['id'];
				}
				# Last items
				else { }
			}
			$html[] = "</optgroup>";
		}

		# subnets
		$html[] = "<optgroup label='"._("Subnets")."'>";

		# root subnet
		if(!isset($subnetMasterId) || $subnetMasterId==0) {
			$html[] = "<option value='0' selected='selected'>"._("Root subnet")."</option>";
		} else {
			$html[] = "<option value='0'>"._("Root subnet")."</option>";
		}

		# return table content (tr and td's) - subnets
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = $repeat; }

			# count levels
			$count = count( $parent_stack ) + 1;

			# print table line if it exists and it is not folder
			if(strlen($option['value']['subnet']) > 0 && $option['value']['isFolder']!=1) {
				# selected
				if($option['value']['id'] == $subnetMasterId) 	{ $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".transform2long($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>"; }
				else 											{ $html[] = "<option value='".$option['value']['id']."'>$repeat ".transform2long($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>"; }
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
		$html[] = "</optgroup>";
		$html[] = "</select>";

		print implode( "\n", $html );
}


/**
 * Get VLAN number form Id
 */
function subnetGetVLANdetailsById($vlanId)
{
	return getVLANbyId($vlanId);
}


/**
 * Get all VLANS
 */
function getAllVlans($tools = false)
{
    global $database;
    # custom fields
    $myFields = getCustomFields('vlans');
    $myFieldsInsert['id']  = '';

    if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {
			$myFieldsInsert['id']  .= ',`vlans`.`'. $myField['name'] .'`';
		}
	}

    /* check if it came from tools and use different query! */
    if($tools) 	{ $query = 'SELECT vlans.vlanId,vlans.number,vlans.name,vlans.description,subnets.subnet,subnets.mask,subnets.id AS subnetId,subnets.sectionId'.$myFieldsInsert['id'].' FROM vlans LEFT JOIN subnets ON subnets.vlanId = vlans.vlanId ORDER BY vlans.number ASC;'; }
    else 		{ $query = 'select * from `vlans` order by `number` asc;'; }

    /* execute */
    try { $vlan = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	/* return vlan details */
	return $vlan;
}


/**
 * Get subnets by VLAN id
 */
function getSubnetsByVLANid ($id)
{
    global $database;

    /* set query, open db connection and fetch results */
    $query         = 'select * from `subnets` where `vlanId` = "'. $id .'";';

    /* execute */
    try { $SubnetDetails = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
    return($SubnetDetails);
}


/**
 * Calculate maximum number of IPv4 / IPv6 hosts per subnet
 */
function MaxHosts( $mask, $type = 0 )
{
    /* IPv4 address */
    if($type == 0) {
    	//31 and 31 networks
    	if($mask==31 || $mask == 32) {
	    	$max_hosts = pow(2, (32 - $mask));
    	}
    	else {
	    	$max_hosts = pow(2, (32 - $mask)) -2;
    	}
    }
     /* IPv6 address */
	else {
	    $max_hosts = gmp_strval(gmp_pow(2, 128 - $mask));
    }

    return (string) $max_hosts;
}


/**
 *	get all subnets belonging to vrf
 */
function getAllSubnetsInVRF($vrfId)
{
    global $database;
	/* execute query */
	$query = 'select * from `subnets` where `vrfId` = "'. $vrfId .'";';

    /* execute */
    try { $vrf = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

   	/* return false if none, else list */
	if(sizeof($vrf) == 0) 	{ return false; }
	else 					{ return $vrf; }
}


/**
 *	Get top 10 subnets by usage
 */
function getSubnetStatsDashboard($type, $limit = "10", $perc = false)
{
    global $database;
    # set limit
    if($limit == "0")	{ $limit = ""; }
    else				{ $limit = "limit $limit"; }

    # percentage
    if($perc) {
		$query = "select SQL_CACHE *,round(`usage`/(pow(2,32-`mask`)-2)*100,2) as `percentage` from (
					select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`,IF(char_length(`description`)>0, `description`, 'No description') as description, (
						SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
					)
					as `usage` from `subnets` as `s`
					where `mask` < 31 and cast(`subnet` as UNSIGNED) < '4294967295'
					order by `usage` desc
					) as `d` where `usage` > 0 order by `percentage` desc $limit;";
    }
	# ipv4 stats
	elseif($type == "IPv4") {
		$query = "select SQL_CACHE * from (
				select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`,IF(char_length(`description`)>0, `description`, 'No description') as description, (
					SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
				)
				as `usage` from `subnets` as `s`
				where cast(`subnet` as UNSIGNED) < '4294967295'
				order by `usage` desc $limit
				) as `d` where `d`.`usage` > 0;";
	}
	# IPv6 stats
	else {
		$query = "select SQL_CACHE * from (
				select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`, IF(char_length(`description`)>0, `description`, 'No description') as description, (
					SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
				)
				as `usage` from `subnets` as `s`
				where cast(`subnet` as UNSIGNED) > '4294967295'
				order by `usage` desc $limit
				) as `d` where `d`.`usage` > 0;";
	}

    /* execute */
    try { $stats = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* close database connection */

    /* return subnets array */
    return($stats);
}










/* @folder functions -------------------- */

/**
 * Get all folders in provided sectionId
 */
function fetchFolders ($sectionId, $orderType = "subnet", $orderBy = "asc" )
{
    global $database;
    /* check for sorting in settings and override */
    $settings = getAllSettings();

    /* get section details to check for ordering */
    $section = getSectionDetailsById ($sectionId);

    // section ordering
    if($section['subnetOrdering']!="default" && strlen($section['subnetOrdering'])>0 ) {
	    $sort = explode(",", $section['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }
    // default - set via settings
    elseif(isset($settings['subnetOrdering']))	{
	    $sort = explode(",", $settings['subnetOrdering']);
	    $orderType = $sort[0];
	    $orderBy   = $sort[1];
    }

    /* set query, open db connection and fetch results */
    $query 	  = "select * from `subnets` where `sectionId` = '$sectionId' and `isFolder` = 1 ORDER BY `masterSubnetId`,`$orderType` $orderBy;";

    /* execute */
    try { $subnets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($subnets);
}


/**
 *	Print dropdown menu for folders in section!
 */
function printDropdownMenuBySectionFolders($sectionId, $subnetMasterId = "0")
{
		# get all subnets
		$subnets = fetchFolders ($sectionId);

		$html = array();

		$rootId = 0;									# root is 0

		# must be integer
		if(isset($_GET['subnetId']))	{ if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }


		foreach ( $subnets as $item )
			$children[$item['masterSubnetId']][] = $item;

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# display selected subnet as opened
		$allParents = getAllParents ($_GET['subnetId']);

		# structure
		$html[] = "<select name='masterSubnetId' class='form-control input-w-auto input-sm'>";
		# root
		$html[] = "<option disabled>"._("Select Master folder")."</option>";
		$html[] = "<option value='0'>"._("Root folder")."</option>";

		# return table content (tr and td's)
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = $repeat; }

			# count levels
			$count = count( $parent_stack ) + 1;

			# print table line
			if(strlen($option['value']['subnet']) > 0) {
				# selected
				if($option['value']['id'] == $subnetMasterId) 	{ $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$option['value']['description']."</option>"; }
				else 											{ $html[] = "<option value='".$option['value']['id']."'>$repeat ".$option['value']['description']."</option>"; }
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
		$html[] = "</select>";

		print implode( "\n", $html );
}











/* @IP address functions ---------------- */


/**
 * Get all IP addresses
 */
function fetchAllIPAddresses ($hostnameSort = false)
{
    global $database;
    /* set query */
    if(!$hostnameSort) {
    	$query 	  = 'select * from ipaddresses;';
    }
    else {
    	$query 	   = 'select * from ipaddresses order by dns_name desc;';
    }

    /* execute */
    try { $ipaddresses = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* close database connection */

    /* return subnets array */
    return($ipaddresses);
}


/**
 * Get number of IPv4 addresses
 */
function getNuberOfIPv4Addresses ()
{
    global $database;
    /* set query */
   	$query 	  = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) < "4294967295";';

    /* execute */
    try { $ipaddresses = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($ipaddresses[0]['count']);
}


/**
 * Get number of IPv6 addresses
 */
function getNuberOfIPv6Addresses ()
{
    global $database;
    /* set query */
   	$query 	  = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) > "4294967295";';

    /* execute */
    try { $ipaddresses = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnets array */
    return($ipaddresses[0]['count']);
}


/**
 * Get all IP addresses by hostname
 */
function fetchAllIPAddressesByName ($hostname)
{
    global $database;
    /* set query */
    $query 	  = 'select * from ipaddresses where `dns_name` like "%'. $hostname .'%" order by `dns_name` desc;';

    /* execute */
    try { $ipaddresses = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* close database connection */

    /* return subnets array */
    return($ipaddresses);
}


/**
 * Get sectionId for requested name - needed for hash page loading
 */
function getSectionIdFromSectionName ($sectionName)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query         = 'select id from sections where name = "'. $sectionName .'";';

    /* execute */
    try { $SubnetDetails = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
    return($SubnetDetails[0]['id']);

}


/**
 * Check for duplicates on add
 */
function checkDuplicate ($ip, $subnetId)
{
    global $database;
    /* we need to put IP in decimal format */
    $ip = Transform2decimal ($ip);

    /* set query, open db connection and fetch results */
    $query         = 'select * from `ipaddresses` where `ip_addr` = "'. $ip .'" and subnetId = "'. $subnetId .'" ;';

    /* execute */
    try { $unique = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return false if it exists */
    if (sizeof($unique) != 0 ) 	{ return true; }
    else 						{ return false; }
}


/**
 * Modify ( add / edit / delete ) IP address
 */
function modifyIpAddress ($ip)
{
    global $database;

    /* set query, open db connection and fetch results */
    $query    = SetInsertQuery($ip);

    /* save old if delete */
    if($ip['action']=="delete")		{ $dold = getIpAddrDetailsById ($ip['id']); }
    elseif($ip['action']=="edit")	{ $old  = getIpAddrDetailsById ($ip['id']); }

    /* execute */
    try { $id = $database->executeQuery( $query, true ); }
    catch (Exception $e) {
        print ("<div class='alert alert-danger'>"._('Error').": ".$e->getMessage() ."</div>");
        //save changelog
		writeChangelog('ip_addr', $ip['action'], 'error', $old, $new);
        return false;
    }

    /* for changelog */
	if($ip['action']=="add") {
		$ip['id'] = $id;
		writeChangelog('ip_addr', $ip['action'], 'success', array(), $ip);
	} elseif ($ip['action']=="delete") {
		writeChangelog('ip_addr', $ip['action'], 'success', $dold, array());
	} else {
		writeChangelog('ip_addr', $ip['action'], 'success', $old, $ip);
	}

    # success
    return true;
}


/**
 * set insert / update / delete query for adding IP address
 * based on provided array
 */
function SetInsertQuery( $ip )
{
}


/**
 * Move IP address to new subnet - for subnet splitting
 */
function moveIPAddress ($id, $subnetId)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'update `ipaddresses` set `subnetId` = "'.$subnetId.'" where `id` = "'. $id .'";';

	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	# ok
	if(!isset($error)) {
        updateLogTable ('IP address move ok', "id: $id\nsubnetId: $subnetId", 0);			# write success log
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return true;
	}
	# problem
	else {
        updateLogTable ('IP address move error', "id: $id\nsubnetId: $subnetId", 2);			# write error log
        return false;
	}
}


/**
 *	Insert scan results
 */
function insertScanResults($res, $subnetId)
{
    global $database;
    # set queries
    foreach($res as $ip) {
    	//escape strings
    	$ip['description'] = mysqli_real_escape_string($database, $ip['description']);

	    $query[] = "insert into `ipaddresses` (`ip_addr`,`subnetId`,`description`,`dns_name`,`lastSeen`) values ('".transform2decimal($ip['ip_addr'])."', '$subnetId', '$ip[description]', '$ip[dns_name]', NOW()); ";
    }
    # glue
    $query = implode("\n", $query);

    # execute query
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}


/**
 * Get IP address details
 */
function getIpAddrDetailsById ($id)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from `ipaddresses` where `id` = "'. $id .'";';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    //we only fetch 1 field
    $details  = $details[0];
	//change IP address formatting to dotted(long)
	$details['ip_addr'] = Transform2long( $details['ip_addr'] );

    /* return result */
    return($details);
}


/**
 * Get IP address details by IP and subnet
 */
function getIpAddrDetailsByIPandSubnet ($ip, $subnetId)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = "select * from `ipaddresses` where `ip_addr` = '$ip' and `subnetId` = $subnetId limit 1;";

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    //we only fetch 1 field
    $details  = $details[0];

    /* return result */
    return($details);
}




/**
 * verify ip address from edit / add
 * noStrict ignores NW and Broadcast checks
 */
function VerifyIpAddress( $ip , $subnet , $noStrict = false )
{
	/* First identify it */
	$type = IdentifyAddress( $ip );
	$type = IdentifyAddress( $subnet );

	/* get mask */
	$mask = explode("/", $subnet);

	/* IPv4 verification */
	if ( $type == 'IPv4' )
	{
        require_once 'PEAR/Net/IPv4.php';
        $Net_IPv4 = new Net_IPv4();

		// is it valid?
		if (!$Net_IPv4->validateIP($ip)) 										{ $error = _("IP address not valid")."! ($ip)"; }
		// it must be in provided subnet
		elseif (!$Net_IPv4->ipInNetwork($ip, $subnet)) 							{ $error = _("IP address not in selected subnet")."! ($ip)"; }
		//ignore  /31 and /32 subnet broadcast and subnet checks!
		elseif ($mask[1] == "31" || $mask[1] == "32" || $noStrict == true) 	{ }
		// It cannot be subnet or broadcast
		else {
            $net = $Net_IPv4->parseAddress($subnet);

            if ($net->network == $ip) 											{ $error = _("Cannot add subnet as IP address!"); }
            elseif ($net->broadcast == $ip) 									{ $error = _("Cannot add broadcast as IP address!"); }
		}
	}

	/* IPv6 verification */
	else
	{
        require_once 'PEAR/Net/IPv6.php';
        $Net_IPv6 = new Net_IPv6();

        //remove /xx from subnet
        $subnet_short = $Net_IPv6->removeNetmaskSpec($subnet);

		// is it valid?
		if (!$Net_IPv6->checkIPv6($ip)) 										{ $error = _("IP address not valid")."! ($ip)"; }
		// it must be in provided subnet
		elseif (!$Net_IPv6->isInNetmask($ip, $subnet)) 							{ $error = _("IP address not in selected subnet")."! ($ip)";}
	}

	/* return results */
	if( isset($error) ) { return $error; }
	else 				{ return false; }
}


/**
 * verify ip address /mask 10.10.10.10./24 - CIDR
 *
 * if subnet == 0 we dont check if IP is subnet -> needed for ipCalc
 */
function verifyCidr( $cidr , $issubnet = 1 )
{
    /* split it to network and subnet */
    $temp = explode("/", $cidr);

    $network = $temp[0];
    $netmask = $temp[1];

    //if one part is missing die
    if (empty($network) || empty($netmask)) {
        $errors[] = _("Invalid CIDR format!");
    }

	/* Identify address type */
	$type = IdentifyAddress( $network );

	/* IPv4 verification */
	if ( $type == 'IPv4' )
	{
        require_once 'PEAR/Net/IPv4.php';
        $Net_IPv4 = new Net_IPv4();

        if ($net = $Net_IPv4->parseAddress ($cidr)) {
            //validate IP
            if (!$Net_IPv4->validateIP ($net->ip)) 					{ $errors[] = _("Invalid IP address!"); }
            //network must be same as provided IP address
            elseif (($net->network != $net->ip) && ($issubnet == 1)){ $errors[] = _("IP address cannot be subnet! (Consider using")." ". $net->network .")"; }
            //validate netmask
            elseif (!$Net_IPv4->validateNetmask ($net->netmask)) 	{ $errors[] = _('Invalid netmask').' ' . $net->netmask; }
        }
        else 														{ $errors[] = _('Invalid CIDR format!'); }
	}
	/* IPv6 verification */
	else
	{
        require_once 'PEAR/Net/IPv6.php';
        $Net_IPv6 = new Net_IPv6();

        //validate IPv6
        if (!$Net_IPv6->checkIPv6 ($cidr) ) 						{ $errors[] = _("Invalid IPv6 address!"); }
        else {

            //validate subnet
            $subnet = $Net_IPv6->getNetmask($cidr);
            $subnet = $Net_IPv6->compress($subnet);			//get subnet part

            $subnetParse = explode("/", $cidr);
            $subnetMask  = $subnetParse[1];
            $subnetNet   = $subnetParse[0];

            if ( ($subnetNet != $subnet) && ($issubnet == 1) ) 	{ $errors[] = _("IP address cannot be subnet! (Consider using")." ". $subnet ."/". $subnetMask .")"; }
	   }
    }

	/* return array of errors */
	return($errors);
}


/**
 * parse IP address
 *
 * IP must be in  CIDR format - '192.168.0.50/16'
 */
function parseIpAddress( $ip, $mask )
{
    /* IPv4 address */
    if ( IdentifyAddress( $ip ) == "IPv4" )
    {

        require('PEAR/Net/IPv4.php');
        $Net_IPv4 = new Net_IPv4();

        $net = $Net_IPv4->parseAddress( $ip .'/'. $mask );

        $out['network']   = $net->network;   // 192.168.0.0
        $out['ip']        = $net->ip;        // 192.168.0.50
        $out['broadcast'] = $net->broadcast; // 192.168.255.255
        $out['bitmask']   = $net->bitmask;   // 16
        $out['netmask']   = $net->netmask;   // 255.255.0.0

    }
    /* IPv6 address */
    else
    {
        require('PEAR/Net/IPv6.php');
        $Net_IPv6 = new Net_IPv6();

        $out['network']   = $ip;         // 2a34:120:feel::
        $out['bitmask']   = $mask;         // 48
        $out['netmask']   = $mask;         // 48 - we just duplicate it

        //broadcast - we fake it with highest IP in subnet
        $net = $Net_IPv6->parseaddress( $ip .'/'. $mask );

        $out['broadcast'] = $net['end'];    // 2a34:120:feel::ffff:ffff:ffff:ffff:ffff
    }

    return( $out );
}


/**
 * Find unused ip addresses between two provided
 *
 * checkType = NW, bcast and none(normal)
 */
function FindUnusedIpAddresses ($ip1, $ip2, $type, $broadcast = 0, $checkType = "", $mask = false )
{
    /* calculate difference */
    $diff = gmp_strval(gmp_sub($ip2, $ip1));

    /* /32 */
    if($mask == "32" && $checkType=="networkempty" && $type=="IPv4") {
	    $result['ip'] 	 = long2ip($ip1);
		$result['hosts'] = "1";
    }
    /* /31 */
    elseif($mask == "31" && $type=="IPv4") {
    	if($diff == 1 && $checkType == "networkempty" ) {
    	    $result['ip'] 	 = long2ip($ip1);
    	    $result['hosts'] = "2";
    	}
    	if($diff == 1 && $checkType == "network" ) {
    	    $result['ip'] 	 = long2ip($ip1);
    	    $result['hosts'] = "1";
    	}
    	elseif($diff == 1 && $checkType == "broadcast" ) {
	    	$result['ip'] 	 = long2ip($ip2);
	    	$result['hosts'] = "1";
    	}
    	elseif($diff == 2 ) {
    	    $result['ip'] 	 = long2ip($ip1);
    	    $result['hosts'] = "2";
    	}
    }
    /* /128 */
    elseif($mask == "128" && $checkType=="networkempty" && $type=="IPv6") {
	    $result['ip'] 	 = long2ip6($ip1);
		$result['hosts'] = "1";
    }
    /* /127 */
    elseif($mask == "127" && $type=="IPv6") {
    	if($diff == 1 && $checkType == "networkempty" ) {
    	    $result['ip'] 	 = long2ip6($ip1);
    	    $result['hosts'] = "2";
    	}
    	if($diff == 1 && $checkType == "network" ) {
    	    $result['ip'] 	 = long2ip6($ip1);
    	    $result['hosts'] = "1";
    	}
    	elseif($diff == 1 && $checkType == "" ) {
    	}
    	elseif($diff == 1 && $checkType == "broadcast" ) {
	    	$result['ip'] 	 = long2ip6($ip2);
	    	$result['hosts'] = "1";
    	}
    	elseif($diff == 2 ) {
    	    $result['ip'] 	 = long2ip6($ip1);
    	    $result['hosts'] = "2";
    	}
    }
    /* ipv6 first IP */
    elseif($type=="IPv6" && $diff==1 && $checkType=="network") {
    	$result['ip'] 	 = long2ip6($ip1);
    	$result['hosts'] = "1";
    }
    /* if diff is less than 2 return false */
    elseif ( $diff < 2 ) {
        return false;
    }
    /* if diff is 2 return 1 IP address in the middle */
    elseif ( $diff == 2 )
    {
        if ($type == "IPv4")
        {   //ipv4
			$result['ip'] 	 = long2ip($ip1 +1);
			$result['hosts'] = "1";
        }
        else
        {   //ipv6
            $ip1_return = gmp_strval(gmp_add($ip1,1));

			$result['ip'] 	 = long2ip6( $ip1_return );
			$result['hosts'] = "1";
        }
    }
    /* if diff is more than 2 return pool */
    else
    {
        if ($type == "IPv4")
        {   //ipv4
            $free = long2ip($ip1 +1) . ' - ' . long2ip($ip2 -1);

			$result['ip'] 	 = $free;
			$result['hosts'] = gmp_strval(gmp_sub($diff, 1));;
        }
        else
        {   //ipv6
            $ip1_return = gmp_strval($ip1);
            $ip2_return = gmp_strval($ip2);

            $free = long2ip6( $ip1_return ) . ' - ' . long2ip6( $ip2_return );

				$result['ip'] 	 = $free;
				$result['hosts'] = gmp_strval(gmp_sub($diff, 1));
        }
    }

    /* return result array with IP range and free hosts */
    return $result;
}


/**
 * Get first available IP address
 */
function getFirstAvailableIPAddress ($subnetId)
{
    global $database;

    /* get all ip addresses in subnet */
    $query 		 = 'SELECT `ip_addr` from `ipaddresses` where `subnetId` = "'. $subnetId .'" order by `ip_addr` ASC;';

    /* execute */
    try { $ipAddresses = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* get subnet */
    $query 	 = 'SELECT `subnet`,`mask` from `subnets` where `id` = "'. $subnetId .'";';
    $subnet2 = $database->getArray($query);
    $subnet  = $subnet2[0]['subnet'];
    $mask    = $subnet2[0]['mask'];

    /* create array of IP addresses */
    $ipaddressArray[]	  = $subnet;
    foreach($ipAddresses as $ipaddress) {
    	$ipaddressArray[] = $ipaddress['ip_addr'];
    }
    //get array size
    $size = sizeof($ipaddressArray);
    $curr = 0;
    //get type
    $type = IdentifyAddress($subnet);

    // IPv4
    if($type=="IPv4") {
	    //if subnet is /32
	    if($mask == "32") {
	    	if($size == 1)  	{ $firstAvailable = $ipaddressArray[0]; }
	    	else 				{ $firstAvailable = false; }
	    }
	    //if subnet /31
	    elseif($mask == "31") {
	    	if($size == 1)  	 { $firstAvailable = $ipaddressArray[0]; }
	    	elseif($size == 2)  {
	    		$delta = $ipaddressArray[1] - $ipaddressArray[0];
	    		if($delta == 1)  { $firstAvailable = $ipaddressArray[0]; }
	    		else			 { $firstAvailable = gmp_strval(gmp_add($ipaddressArray[0], 1)); }
	    	}
	    	else 				 { $firstAvailable = false; }
	    }
	    //size 0 = subnet +1
	    elseif($size == 1) {
		    $firstAvailable = gmp_strval(gmp_add($ipaddressArray[0], 1));
	    }
	    //between IPs
	    else {
	    	//get first change -> delta > 1
	    	for($m=1; $m <= $size -1; $m++) {
	    		$delta = gmp_strval(gmp_sub($ipaddressArray[$m],$ipaddressArray[$m-1]));

	    		//compare with previous
	    		if ($delta != 1 ) {
	    			$firstAvailable = gmp_strval(gmp_add($ipaddressArray[$m-1],1));
	    			$m = $size;
	    		}
	    		else {
	    			$firstAvailable = gmp_strval(gmp_add($ipaddressArray[$m],1));
	    		}
	    	}

	    	//if bcast ignore!
	        require_once 'PEAR/Net/IPv4.php';
	        $Net_IPv4 = new Net_IPv4();
	        $net = $Net_IPv4->parseAddress(transform2long($subnet)."/".$mask);

		    if ($net->broadcast == transform2long($firstAvailable)) {
		    	$firstAvailable = false;
		    }
	    }
    }
    //IPv6
    else {
	    //if subnet is /128
	    if($mask == "128" && $type == "IPv6") {
	    	if($size == 1)  { $firstAvailable = $ipaddressArray[0]; }
	    	else 			{ $firstAvailable = false; }
	    }
	    //if subnet /127
	    elseif($mask == "127" && $type == "IPv6") {
	    	if($size == 1)  	 { $firstAvailable = $ipaddressArray[0]; }
	    	elseif($size == 2)  {
	    		$delta = $ipaddressArray[1] - $ipaddressArray[0];
	    		if($delta == 1)  { $firstAvailable = $ipaddressArray[0]; }
	    		else			 { $firstAvailable = gmp_strval(gmp_add($ipaddressArray[0], 1)); }
	    	}
	    	else 				 { $firstAvailable = false; }
	    }
	    //size 1 = subnet
	    elseif($size == 1) {
    		$firstAvailable = gmp_strval($ipaddressArray[0]);
	    }
	    //subnet
	    elseif($subnet == $ipaddressArray[0]) {
		    $firstAvailable = gmp_strval($subnet);
	    }
	    //between IPs
	    else {
	    	//get first change -> delta > 1
	    	for($m=1; $m <= $size -1; $m++) {
	    		$delta = gmp_strval(gmp_sub($ipaddressArray[$m],$ipaddressArray[$m-1]));

	    		//compare with previous
	    		if ($delta != 1 ) {
	    			$firstAvailable = gmp_strval(gmp_add($ipaddressArray[$m-1],1));
	    			$m = $size;
	    		}
	    		else {
	    			$firstAvailable = gmp_strval(gmp_add($ipaddressArray[$m],1));
	    		}
	    	}

	    	//if bcast ignore!
		    $firstAvailable = gmp_strval(gmp_add($ipaddressArray[$size-1],1));
	    }

    }

    /* return first available IP address */
    return $firstAvailable;
}


/**
 * Check if hostname is unique
 */
function isHostUnique($host)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select count(*) as cnt from `ipaddresses` where `dns_name` = "'. $host .'";';

    /* execute */
    try { $res = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    if($res[0]['cnt'] == '0')	{ return true; }
    else						{ return false; }
}


/**
 * Functions to transform IPv6 to decimal and back
 *
 */
function ip2long6 ($ipv6)
{
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

function long2ip6($ipv6long)
{
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
 * Get all avaialble devices
 */
function getIPaddressesBySwitchName ( $name )
{
    global $database;

    /* get all vlans, descriptions and subnets */
    $query = 'SELECT * FROM `ipaddresses` where `switch` = "'. $name .'" order by port ASC;';

    /* execute */
    try { $ip = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return vlans */
    return $ip;
}


/**
 * count all avaialble devices
 */
function countIPaddressesBySwitchId ( $id )
{
    global $database;

    /* get all vlans, descriptions and subnets */
    if(is_null($id))	{ $query = 'SELECT count(*) as `count` FROM `ipaddresses` where `switch` IS NULL or `switch` = 0;'; }
	else				{ $query = 'SELECT count(*) as `count` FROM `ipaddresses` where `switch` = "'. $id .'";'; }

    /* execute */
    try { $ip = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return vlans */
    return $ip[0]['count'];
}











/* $ping ---------- */


/**
 * Ping host
 */
function pingHost ($ip, $count=1, $timeout = 1, $exit=false)
{
    # get settings
    $settings = getAllSettings();

	//verify ping path
	if(!file_exists($settings['scanPingPath'])) {
		$retval = 1000;
	}
	else {
		//set ping command based on OS type
		if(PHP_OS == "FreeBSD" || PHP_OS == "NetBSD")                           { $cmd = $settings['scanPingPath']." -c $count -W ".($timeout*1000)." $ip 1>/dev/null 2>&1"; }
		elseif(PHP_OS == "Linux" || PHP_OS == "OpenBSD")                        { $cmd = $settings['scanPingPath']." -c $count -w $timeout $ip 1>/dev/null 2>&1"; }
		elseif(PHP_OS == "WIN32" || PHP_OS == "Windows" || PHP_OS == "WINNT")	{ $cmd = $settings['scanPingPath']." -n $count -I ".($timeout*1000)." $ip 1>/dev/null 2>&1"; }
		else																	{ $cmd = $settings['scanPingPath']." -c $count -n $ip 1>/dev/null 2>&1"; }

		//set and execute;
	    exec($cmd, $output, $retval);
	}

    //exit codes
    //	0 = online
    //	1,2 = offline

    //other exit codes
    //http://www.freebsd.org/cgi/man.cgi?query=sysexits&apropos=0&sektion=0&manpath=FreeBSD+4.3-RELEASE&arch=default&format=ascii

	//return result for web or cmd
	if(!$exit) 	{ return $retval; }
	else	  	{ exit($retval); }
}


/**
 * Ping host - PEAR
 */
function pingHostPear ($ip, $count="1", $timeout = 1, $exit=false)
{
	require_once "PEAR/Net/Ping.php";
	$ping = Net_Ping::factory();

	if(PEAR::isError($ping)) {
		echo $ping->getMessage();
	}
	else {
		$ping->setArgs(array('count' => $count, 'timeout' => 1));

		$pRes = $ping->ping($ip);

		// check response
		if(PEAR::isError($pRes)) {
			$result['code'] = 2;
			$result['text'] = $pRes->message;
			$result['text'] = $pRes->getMessage();
		}
		else {
			//all good
			if($pRes->_transmitted == $pRes->_received) {
				$result['code'] = 0;
				$result['text'] = "RTT: ".$pRes->_round_trip['avg'] . " ms";
			}
			//ping loss
			elseif($pRes->_received == 0) {
				$result['code'] = 1;
				$result['text'] = "Offline";
			}
			//failed
			else {
				$result['code'] = 3;
				$result['text'] = "Unknown error";
			}
		}
	}

    //exit codes
    // 0 = online
    // 1 = offline
    // 2 = error
    // 3 = unknown error

	//return result for web or cmd
	if(!$exit) 	{ return $result; }
	else	  	{ exit	($result['code']); }
}


/**
 *	get ping exit code explanation
 */
function explainPingExit($code)
{
	//http://www.freebsd.org/cgi/man.cgi?query=sysexits&apropos=0&sektion=0&manpath=FreeBSD+4.3-RELEASE&arch=default&format=ascii
	switch($code) {
		case 64:	$cName = "EX_USAGE";		break;
		case 65:	$cName = "EX_DATAERR";		break;
		case 68:	$cName = "EX_NOHOST";		break;
		case 70:	$cName = "EX_SOFTWARE";		break;
		case 71:	$cName = "EX_OSERR";		break;
		case 72:	$cName = "EX_OSFILE";		break;
		case 73:	$cName = "EX_CANTCREAT";	break;
		case 74:	$cName = "EX_IOERR";		break;
		case 75:	$cName = "EX_TEMPFAIL";		break;
		case 77:	$cName = "EX_NOPERM";		break;

		case 1000: 	$cName = "Invalid ping path"; break;
	}
	return $cName;
}


/**
 * Telnet host check on specified port
 */
function telnetHost ($ip, $ports, $timeout = 2, $exit = false)
{
	/* @debugging functions ------------------- */
	ini_set('display_errors', 0);
	error_reporting(E_ERROR ^ E_WARNING);

	//save ports to array
	$ports = explode(";", $ports);

	//default response is dead
	$retval = 1;

	//try each port untill one is alive
	foreach($ports as $p) {
		// open socket
		$conn = fsockopen($ip, $p, $errno, $errstr, $timeout);
		//failed
		if (!$conn) {
			//fclose($conn);
		}
		//success
		else 		{
			$retval = 0;	//set return as port if alive
			fclose($conn);
			break;			//end foreach if success
		}
	}

    //exit codes
    //	0 = offline
    //	everything else = online (port where available)

	//return result for web or cmd
	if(!$exit) 	{ return $retval; }
	else	  	{ exit($retval); }
}


/**
 * Update host lastSeen
 */
function updateLastSeen($ip_id)
{
    global $db;
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);

    /* get all vlans, descriptions and subnets */
    $query = 'update `ipaddresses` set `lastSeen` = NOW() where `id` = "'.$ip_id.'";';

	//update
    try { $res = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    //default
    return true;
}


/**
 *	Get all IP addresses for scan
 */
function getAllIPsforScan($cli = false)
{
    global $database;
    //set query
    $query = 'select `i`.`id`,`i`.`description`,`subnetId`,`ip_addr`,`lastSeen`,`lastSeen` as `oldStamp` from `ipaddresses` as `i`, `subnets` as `s` where `i`.`subnetId`=`s`.`id` and `s`.`pingSubnet` = 1 and `i`.`excludePing` != 1 order by `lastSeen` desc;';

	//get IP addresses
    try { $res = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        	//output error
        	if($cli) 	{ print ("Error:$error"); }
			else		{ print ("<div class='alert alert-danger'>"._('Error').": $error</div>"); }
        return false;
    }
    //return
    return $res;
}









/* @IPcalculations ---------- */

/**
 * Transform IP address from decimal to dotted (167903488 -> 10.2.1.0)
 */
function Transform2long ($ip)
{
    if (IdentifyAddress($ip) == "IPv4" ) { return(long2ip($ip)); }
    else 								 { return(long2ip6($ip)); }
}


/**
 * Transform IP address from dotted to decimal (10.2.1.0 -> 167903488)
 */
function Transform2decimal ($ip)
{
    if (IdentifyAddress($ip) == "IPv4" ) { return( sprintf("%u", ip2long($ip)) ); }
    else 								 { return(ip2long6($ip)); }
}


/**
 * identify ip address type - ipv4 or ipv6?
 *
 * first we need to find representation - decimal or dotted?
 */
function IdentifyAddress( $subnet )
{
    /* dotted */
    if (strpos($subnet, ":")) {
        return 'IPv6';
    }
    elseif (strpos($subnet, ".")) {
        return 'IPv4';
    }
    /* decimal */
    else  {
        /* IPv4 address */
        if(strlen($subnet) < 12) {
    		return 'IPv4';
        }
        /* IPv6 address */
    	else {
    		return 'IPv6';
        }
    }
}









/* @changelog ---------- */

/**
 *	Get changelog entries for specified type entry
 *
 *	$ctype = 'ip_addr','subnet','section'
 *	$coid = objectId from ctype definition
 */
function getChangelogEntries($ctype, $coid, $long = false, $limit = 50)
{
    /* set query, open db connection and fetch results */
    global $database;
    # change ctype to match table
	if($ctype=="ip_addr")	$ctypeTable = "ipaddresses";
	else					$ctypeTable = $ctype;

    # query
    if($long) {
	    $query = "select *
					from `changelog` as `c`, `users` as `u`, `$ctypeTable` as `o`
					where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
					and `c`.`coid` = '$coid' and `c`.`ctype` = '$ctype' order by `c`.`cid` desc limit $limit;";
	} else {
	    $query = "select *
					from `changelog` as `c`, `users` as `u`
					where `c`.`cuser` = `u`.`id`
					and `c`.`coid` = '$coid' and `c`.`ctype` = '$ctype' order by `c`.`cid` desc limit $limit;";
	}

    # execute
    try { $res = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    # return result
    return $res;
}



/**
 *	Get changelog entries for all slave subnets
 */
function getSubnetSlaveChangelogEntries($subnetId, $limit = 50)
{
    /* set query, open db connection and fetch results */
    global $database;
    // get all slave subnets
    global $removeSlaves;
	getAllSlaves ($subnetId);
	$key = array_search($subnetId, $removeSlaves);
	unset($removeSlaves[$key]);
	$removeSlaves = array_unique($removeSlaves);

    //if some
    if(sizeof($removeSlaves) > 0) {
	    # query
	    $query  = "select
					`u`.`real_name`,`o`.`sectionId`,`o`.`subnet`,`o`.`mask`,`o`.`description`,`o`.`id`,`c`.`caction`,`c`.`cresult`,`c`.`cdate`,`c`.`cdiff`  from `changelog` as `c`, `users` as `u`, `subnets` as `o`
					where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
					and (";
		foreach($removeSlaves as $snet) {
		$query .= "`c`.`coid` = '$snet' or ";
		}
		$query  = substr($query, 0, -3);
		$query .= ") and `c`.`ctype` = 'subnet' order by `c`.`cid` desc limit $limit;";

	    # execute
	    try { $res = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # return result
	    return $res;
    }
	else {
		return false;
	}
}


/**
 *	Get changelog entries for all IP addresses in subnet
 */
function getSubnetIPChangelogEntries($subnetId, $limit = 50)
{
    /* set query, open db connection and fetch results */
    global $database;
    // get all slave subnets
    global $removeSlaves;
	getAllSlaves ($subnetId);
	$removeSlaves = array_unique($removeSlaves);

    // get all hosts and their ID's
    $ips  = array();
    if(sizeof($removeSlaves)>0) {
	    foreach($removeSlaves as $sid) {
	    	$stemp = getIpAddressesBySubnetId ($sid);

	    	if(sizeof($stemp)>0) {
		    	foreach($stemp as $ipline) {
					$ips[] = $ipline['id'];
		    	}
	    	}
	    }
    }

    //if some
    if(sizeof($ips) > 0) {
	    # query
	    $query  = "select
	    			`u`.`real_name`,`o`.`id`,`o`.`ip_addr`,`o`.`description`,`o`.`id`,`o`.`subnetId`,`c`.`caction`,`c`.`cresult`,`c`.`cdate`,`c`.`cdiff`
					from `changelog` as `c`, `users` as `u`, `ipaddresses` as `o`
					where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
					and (";
		foreach($ips as $ip) {
		$query .= "`c`.`coid` = '$ip' or ";
		}
		$query  = substr($query, 0, -3);
		$query .= ") and `c`.`ctype` = 'ip_addr' order by `c`.`cid` desc limit $limit;";

	    # execute
	    try { $res = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # return result
	    return $res;
    }
	else {
		return false;
	}
}


/**
 *	Get all changelogs
 */
function getAllChangelogs($filter = false, $expr, $limit = 100)
{
    /* set query, open db connection and fetch results */
    global $database;
	//no filter
	if(!$filter) {
	    $query = "select * from (
					select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`ip_addr`,'mask',`sectionId`,`subnetId`,`ip`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
					from `changelog` as `c`, `users` as `u`,`ipaddresses` as `ip`,`subnets` as `su`
					where `c`.`ctype` = 'ip_addr' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`ip`.`id` and `ip`.`subnetId` = `su`.`id`
					union all
					select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`subnet`,`mask`,`sectionId`,'subnetId',`su`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
					from `changelog` as `c`, `users` as `u`,`subnets` as `su`
					where `c`.`ctype` = 'subnet' and  `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`
				) as `ips` order by `cid` desc limit $limit;";
	}
	//filter
	else {
		/* replace * with % */
		if(substr($expr, 0, 1)=="*")								{ $expr[0] = "%"; }
		if(substr($expr, -1, 1)=="*")								{ $expr = substr_replace($expr, "%", -1);  }
		if(substr($expr, 0, 1)!="*" && substr($expr, -1, 1)!="*")	{ $expr = "%".$expr."%"; }

	    $query = "select * from (
					select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`ip_addr`,'mask',`sectionId`,`subnetId`,`ip`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
					from `changelog` as `c`, `users` as `u`,`ipaddresses` as `ip`,`subnets` as `su`
					where `c`.`ctype` = 'ip_addr' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`ip`.`id` and `ip`.`subnetId` = `su`.`id`
					union all
					select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`subnet`,`mask`,`sectionId`,'subnetId',`su`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
					from `changelog` as `c`, `users` as `u`,`subnets` as `su`
					where `c`.`ctype` = 'subnet' and  `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`
				) as `ips`
				where `coid`='$expr' or `ctype`='$expr' or `real_name` like '$expr' or `cdate` like '$expr' or `cdiff` like '$expr' or INET_NTOA(`ip_addr`) like '$expr'
				order by `cid` desc limit $limit;";
	}

    # execute
    try { $res = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    # return result
    return $res;
}


/**
 * Write new changelog
 */
function write_changelog($ctype, $action, $result, $old, $new) {
	return writeChangelog($ctype, $action, $result, $old, $new);
}
function writeChangelog($ctype, $action, $result, $old, $new)
{
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


?>