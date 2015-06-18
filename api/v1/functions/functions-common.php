<?php

/**
 * Common phpIPAM functions
 *
 * Common functions that are used
 * in phpipam.
 *
 */



/**
 * referer check
 * We can only request stuff from own URL and through XMLHttpRequest
 *
 * $_SERVER['HTTP_X_REQUESTED_WITH'] must be done through XMLHttpRequest
 * $_SERVER['HTTP_ORIGIN'] request must come from same IP as server is on!
 *
 */
function CheckReferrer()
{
    if ( ($_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest") && ($_SERVER['HTTP_ORIGIN'] != $_SERVER['HTTP_HOST'] ) ) {
        updateLogTable ('Page not referred properly', "", 2);
        die(_('Page not referred properly'));
    }
}


/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5
 */
function create_link($l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $install = false )
{
	# get settings
	global $settings;
	if(!isset($settings) && !$install) { $settings = getAllSettings(); }

	# set rewrite
	if($settings['prettyLinks']=="Yes") {
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
	}

	# result
	return $link;
}



/**
 *	verify GET parameters
 *
 *		we need to make sure that GET parameters are valid for phpipam scheme
 */
validate_get ($_GET);
function validate_get ($get)
{
	# l1 check
	if(isset($get['page']))	{
		$valid = array("administration","dashboard","install","ipaddr","login","tools","upgrade","error","subnets","folder","vlan","vrf","request_ip","widgets");
		if(!in_array($get['page'], $valid)) {
			header("Location:".create_link("error","406"));
		}
	}
	# validate all post vars
	if(sizeof($get)>0) {
	foreach($get as $g) {
	    if(preg_match('/[^A-Za-z0-9_.#\\-$]/', $g)) {
	    	# permit for search
	    	if($get['section']!="search") {
		    header("Location:".create_link("error","406"));
	}	}	}	}
}


/**
 *	create URL
 */
function createURL ()
{
	# reset url for base
	if($_SERVER['SERVER_PORT'] == "443") 		{ $url = "https://$_SERVER[HTTP_HOST]".BASE; }
	// reverse proxy doing SSL offloading
	elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') 	{ $url = "https://$_SERVER[SERVER_NAME]".BASE; }
	elseif(isset($_SERVER['HTTP_X_SECURE_REQUEST'])  && $_SERVER['HTTP_X_SECURE_REQUEST'] == 'true') 	{ $url = "https://$_SERVER[SERVER_NAME]".BASE; }
	// custom port
	elseif($_SERVER['SERVER_PORT']!="80")  		{ $url = "http://$_SERVER[HTTP_HOST]:$_SERVER[SERVER_PORT]".BASE; }
	// normal http
	else								 		{ $url = "http://$_SERVER[HTTP_HOST]".BASE; }

	//result
	return $url;
}




/**
 * protect against injections
 *
 *	sql protects against SQL injections (mysql_escape_string)
 *	xss protects agains XSS injections (strip_tags)
 *	action sets permitted actions!
 */
function filter_user_input ($input, $sql = true, $xss = true, $actions = false)
{
	# XSS
	if($xss) {

		if(is_array($input)) {
			foreach($input as $k=>$v) { $input[$k] = strip_tags($v); }
		}
		else {
			$input = strip_tags($input);
		}
	}

	# sql?
	if($sql) {
		global $database;

		if(is_array($input)) {
			foreach($input as $k=>$v) { $input[$k] = $database->real_escape_string($v); }
		}
		else {
			$input = $database->real_escape_string($input);
		}
	}

	# actions
	if($actions) {
		$permitted = array("add", "edit", "delete", "truncate", "split", "resize", "move");
		if(!in_array($input, $permitted)) {
			die("<div class='alert alert-danger'>Invalid action!</div>");
		}
	}


	return $input;
}


/**
 *	Trim user input
 */
function trim_user_input($input)
{
	if(is_array($input)) {
		foreach($input as $k=>$v) { $input[$k] = trim($v); }
	}
	else {
		$input = $database->trim($input);
	}

	return $input;
}






/* @user based functions ---------- */




/**
 * reset inactivity time
 */
function reset_inactivity_time()
{
	$_SESSION['lastactive'] = time();
}


/**
 * Functions to check if user is authenticated properly for ajax-loaded pages
 *
 */
function isUserAuthenticated($die = true)
{
    /* open session and get username / pass */
	if (!isset($_SESSION)) {  global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }
    /* redirect if not authenticated */
    if (empty($_SESSION['ipamusername'])) {
    	# save requested page
    	$_SESSION['phpipamredirect'] = $_SERVER['HTTP_REFERER'];												//here we need referrer

    	$url = createURL ();
    	# die
    	if($die) { die('<div class="alert alert-danger"><a href="'.$url.create_link("login").'">'._('Please login first').'!</a></div>'); }
    	else	 { die("<div class='pHeader'>"._('Error')."</div><div class='pContent'><div class='alert alert-danger'>"._('Please login first')."!</div></div><div class='pFooter'><a class='btn btn-sm btn-default' href='".$url.create_link("login")."'>"._('Login')."</a>"); }
    }

    /* close session */
    session_write_close();
}


/**
 * Functions to check if user is authenticated properly
 *
 * If not redirect to login!
 */
function isUserAuthenticatedNoAjax ()
{
    /* open session and get username / pass */
	if (!isset($_SESSION)) { global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }
    /* redirect if not authenticated */
    if (empty($_SESSION['ipamusername'])) {
    	# save requested page
    	$_SESSION['phpipamredirect'] = $_SERVER['SCRIPT_URI'];

    	$url = createURL ();
    	# redirect
    	header("Location:".$url.create_link("login","timeout"));
    }
    else {
	    if($_GET['page']!="login" && $_GET['page']!="request_ip" && $_GET['page']!="upgrade" && $_GET['page']!="install") {
		    global $settings;
	    	/* check inactivity time */
			if( strlen($settings['inactivityTimeout']>0) && (time()-$_SESSION['lastactive']) > $settings['inactivityTimeout']) {
	    		# redirect
	    		$url = createURL ();
				header("Location:".$url.create_link("login","timeout"));
			}
		}
		reset_inactivity_time();
    }

    /* close session */
    session_write_close();
}


/**
 * Check if user is admin
 */
function checkAdmin ($die = true)
{
    /* first get active username */
    if(!isset($_SESSION)) { global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }
    $ipamusername = $_SESSION['ipamusername'];
    session_write_close();

    /* set check query and get result */
    global $database;

    /* Check connection */
    if ($database->connect_error) {
    	if($_SERVER['SERVER_PORT'] == "443") 	{ $url = "https://".$_SERVER['HTTP_HOST'].BASE; }
    	elseif($_SERVER['SERVER_PORT']!="80")	{ $url = "http://".$_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'].BASE; }
    	else								 	{ $url = "http://".$_SERVER['HTTP_HOST'].BASE; }
    	# redirect
    	header("Location:".$url.create_link("login"));
	}

	/* set query if database exists! */
    $query = 'select role from users where `username` = "'. $ipamusername .'";';

    /* fetch role */
    try { $role = $database->getRow( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        die ("<div class='alert alert-danger'>"._('Error').": $error</div>");
    }


    /* return true if admin, else false */
    if ($role[0] == "Administrator") {
        return true;
    }
    else {
    	//die
    	if($die == true) { die('<div class="alert alert-danger">'._('Administrator level privileges required').'!</div>'); }
    	//return false if called
    	else 			{ return false; }
    }

}


/**
 * Get active users username - from session!
 */
function getActiveUserDetails ()
{
	if (!isset($_SESSION)) { global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }

	if(isset($_SESSION['ipamusername'])) {
    	return getUserDetailsByName ($_SESSION['ipamusername']);
    }
    session_write_close();
}


/**
 * Get all users
 */
function getAllUsers ()
{
    global $database;

    /* set query, open db connection and fetch results */
    $query    = 'select * from users order by `role` asc, `real_name` asc;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details);
}


/**
 * Get number of  users
 */
function getNumberOfUsers ()
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select count(*) as count from users;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details[0]['count']);
}


/**
 * Get all admin users
 */
function getAllAdminUsers ()
{
    global $database;

    /* check for possible errors because of cron */
    if(isset($database->error)) {
	    unset($database);
	    global $db;
	    $database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
    }

    /* set query, open db connection and fetch results */
    $query    = 'select * from users where `role` = "Administrator" order by id desc;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details);
}


/**
 * Get user details by ID
 */
function getUserDetailsById ($id)
{
	# check if already in cache
	if($user = checkCache("user", $id)) {
		return $user;
	}
	# query
	else {
	    global $database;
	    /* set query, open db connection and fetch results */
	    $query    = 'select * from users where id = "'. $id .'";';

	    /* execute */
	    try { $details = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # save cache - id and name
	    writeCache("user", $id, $details[0]);
	    writeCache("user", $details[0]['username'], $details[0]);

	    /* return results */
	    return($details[0]);
	}
}


/**
 * Get user details by name
 */
function getUserDetailsByName ($username, $killsession = true)
{
	# check if already in cache
	if($user = checkCache("user", $username)) {
		return $user;
	}
	# query
	else {
		# for db upgrade!
		if(strpos($_SERVER['SCRIPT_URI'], "databaseUpgrade.php")>0) {
			global $db;
			$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
		}
		else {
			global $database;
		}
	    /* set query, open db connection and fetch results */
	    $query    = 'select * from users where `username` = "'. $username .'";';

	    /* execute */
	    try { $details = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # result must be more than 1!
	    if(!isset($details[0]))	{
	    	if($killsession) {
		    	global $phpsessname;
		    	if(strlen($phpsessname)>0) { session_name($phpsessname); }
		    	session_start();
		    	session_destroy();
			  	return false;
		  	}
	    }
	    else {
		    # save cache - id and name
		    writeCache("user", $details[0]['id'], $details[0]);
		    writeCache("user", $username, $details[0]);

		    /* return results */
		    return($details[0]);
	    }

	}
}

/**
 * Get user lang
 */
function getUserLang ($username)
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select `lang`,`l_id`,`l_code`,`l_name` from `users` as `u`,`lang` as `l` where `l_id` = `lang` and `username` = "'.$username.'";;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details[0]);
}


/**
 * Get all lang
 */
function getLanguages ()
{
    global $database;
    /* set query, open db connection and fetch results */
    $query    = 'select * from `lang`;';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details);
}


/**
 * Get lang by id
 */
function getLangById ($id)
{
	# check cache
	if($vtmp = checkCache("lang", $id)) {
		return $vtmp;
	}
	else {

	    global $database;
	    /* set query, open db connection and fetch results */
	    $query    = 'select * from `lang` where `l_id` = "'.$id.'";';

	    /* execute */
	    try { $details = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # save cache
	    writeCache("lang", $id, $details[0]);
	    /* return results */
	    return($details[0]);
	}
}


/**
 *	Get all widgets
 */
function getAllWidgets($admin = false, $inactive = false)
{
    global $database;

	# inactive also - only for administration
	if($inactive) 	{ $query = "select * from `widgets`; ";
	}
	else {
		# admin?
		if($admin) 	{ $query = "select * from `widgets` where `wactive` = 'yes'; "; }
		else		{ $query = "select * from `widgets` where `wadminonly` = 'no' and `wactive` = 'yes'; "; }
	}

    /* execute */
    try { $widgets = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* reindex */
    foreach($widgets as $w) {
	    $wout[$w['wfile']] = $w;
    }

    /* return results */
    return $wout;
}


/**
 *	Get widget by id
 */
function getWidgetById($wid)
{
    global $database;
	# query
	$query = "select * from `widgets` where `wid` = '$wid'; ";

    /* execute */
    try { $widget = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return $widget[0];
}


/**
 *	Get widget by filename
 */
function getWidgetByFile($wfile)
{
    global $database;
	# query
	$query = "select * from `widgets` where `wfile` = '$wfile'; ";

    /* execute */
    try { $widget = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return $widget[0];
}


/**
 * Verify widget
 */
function verifyWidget ($file)
{
	//verify that proper files exist
	if(!file_exists("app/dashboard/widgets/$file.php"))	{ return false; }
	else													{ return true; }
}


/**
 * get user favourite subnets
 */
function getFavouriteSubnets()
{
    # get user details
    $user = getActiveUserDetails();

    # none
    if(strlen($user['favourite_subnets'])==0) {
	    return false;
    }
    # ok
    else {
    	//store to array
    	$favs = explode(";", $user['favourite_subnets']);
    	$favs = array_filter($favs);
    	//fetch details
	    $subnets = getUserFavouriteSubnets($favs);

	    return $subnets;
    }

}


/**
 *	get user favourite subnets
 */
function getUserFavouriteSubnets($subnetIds)
{
    global $database;

	# get details for each id
	foreach($subnetIds as $id) {
		$query = "select `su`.`id` as `subnetId`,`se`.`id` as `sectionId`, `subnet`, `mask`,`su`.`description`,`se`.`description` as `section`, `vlanId`, `isFolder`
				  from `subnets` as `su`, `sections` as `se` where `su`.`id` = $id and `su`.`sectionId` = `se`.`id` limit 1;";

	    /* execute */
	    try { $sdetails = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

	    # out array
	    $subnets[] = $sdetails[0];
	}

	//return result
	return $subnets;
}


/**
 *	check if subnet is favourited
 */
function isSubnetFavourite($subnetId)
{
    # get user details
    $user = getActiveUserDetails();

    # none
    if(strlen($user['favourite_subnets'])==0) {
	    return false;
    }
	# check
	else {
    	//store to array
    	$favs = explode(";", $user['favourite_subnets']);
    	//check
    	if(in_array($subnetId, $favs)) {
	    	return true;
    	} else {
	    	return false;
    	}
	}
}


/**
 *	edit favourite
 */
function editFavourite($post)
{
    global $database;

    # get user details and favourites
    $user = getActiveUserDetails();
	# empty
	$old = explode(";", $user['favourite_subnets']);

	# set query
	if($post['action'] == "remove") {
		$new = implode(";", array_diff($old, array($post['subnetId'])));
		$query = "update `users` set `favourite_subnets` = '$new' where `id` = '$user[id]' limit 1;";
	} elseif($post['action'] == "add") {
		if(!is_array($old))	{ $old = array(); }
		$new = implode(";",array_merge(array($post['subnetId']), $old));
		$query = "update `users` set `favourite_subnets` = '$new' where `id` = '$user[id]' limit 1;";
	} else {
		return false;
	}

	# execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }
    return true;
}



/**
 * Verify translation
 */
function verifyTranslation ($code)
{
	//verify that proper files exist
	if(!file_exists("functions/locale/$code/LC_MESSAGES/phpipam.mo"))	{ return false; }
	else																{ return true; }
}


/**
 * Verify translation version
 */
function getTranslationVersion ($code)
{
	//check for version
	$ver = shell_exec("grep 'Project-Id-Version:' ".dirname(__FILE__)."/locale/$code/LC_MESSAGES/phpipam.po");
	//parse
	$ver = str_replace(array("Project-Id-Version:", " ", '"', "#",'\n', ":"), "", $ver);
	//return version
	return $ver;
}


/**
 * Get full field data, including comments
 */
function getFullFieldData($table, $field)
{
    global $database;

    /* escape vars to prevent SQL injection */
	$table = filter_user_input ($table, true, true);
	$field = filter_user_input ($field, true, true);

    /* set query, open db connection and fetch results */
    $query = "show full columns from `$table` where `Field` = '$field';";

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        //print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* return results */
    return($details[0]);
}










/* @permission functions ---------- */

/**
 *	Check section permissions
 */
function checkSectionPermission ($sectionId)
{
    # open session and get username / pass
	if (!isset($_SESSION)) {  global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }
    # redirect if not authenticated */
    if (empty($_SESSION['ipamusername'])) 	{ return "0"; }
    else									{ $username = $_SESSION['ipamusername']; }

	# get all user groups
	global $userDetails;
	if(!isset($userDetails)) 	{ $user = getUserDetailsByName ($username); }
	else						{ $user = $userDetails; }
	$groups = json_decode($user['groups']);

	# if user is admin then return 3, otherwise check
	if($user['role'] == "Administrator")	{ return "3"; }

	# get section permissions
	$section  = getSectionDetailsById($sectionId);
	$sectionP = json_decode($section['permissions']);

	# default permission
	$out = 0;

	# for each group check permissions, save highest to $out
	if(sizeof($sectionP)>0) {
		foreach($sectionP as $sk=>$sp) {
			# check each group if user is in it and if so check for permissions for that group
			if(sizeof($groups)>0) {
			foreach($groups as $uk=>$up) {
				if($uk == $sk) {
					if($sp > $out) { $out = $sp; }
				}
			}
			}
		}
	}
	# return permission level
	return $out;
}


/**
 *	Check subnet permissions
 */
function checkSubnetPermission ($subnetId)
{
    # open session and get username / pass
	if (!isset($_SESSION)) {  global $phpsessname; if(strlen($phpsessname)>0) { session_name($phpsessname); }  session_start(); }
    # redirect if not authenticated */
    if (empty($_SESSION['ipamusername'])) 	{ return "0"; }
    else									{ $username = $_SESSION['ipamusername']; }

	# get all user groups
	global $userDetails;
	if(!isset($userDetails)) 	{ $user = getUserDetailsByName ($username); }
	else						{ $user = $userDetails; }
	$groups = json_decode($user['groups']);

	# if user is admin then return 3, otherwise check
	if($user['role'] == "Administrator")	{ return "3"; }

	# get subnet permissions
	$subnet  = getSubnetDetailsById($subnetId);
	$subnetP = json_decode($subnet['permissions']);

	# get section permissions
	$section  = getSectionDetailsById($subnet['sectionId']);
	$sectionP = json_decode($section['permissions']);

	# default permission
	$out = 0;

	# for each group check permissions, save highest to $out
	if(sizeof($sectionP) > 0) {
		foreach($sectionP as $sk=>$sp) {
			# check each group if user is in it and if so check for permissions for that group
			foreach($groups as $uk=>$up) {
				if($uk == $sk) {
					if($sp > $out) { $out = $sp; }
				}
			}
		}
	}
	else {
		$out = "0";
	}

	# if section permission == 0 then return 0
	if($out == "0") {
		return "0";
	}
	else {
		$out = "0";
		# ok, user has section access, check also for any higher access from subnet
		if(sizeof($subnetP) > 0) {
			foreach($subnetP as $sk=>$sp) {
				# check each group if user is in it and if so check for permissions for that group
				foreach($groups as $uk=>$up) {
					if($uk == $sk) {
						if($sp > $out) { $out = $sp; }
					}
				}
			}
		}
	}

	# return result
	return $out;
}








/* @general functions ---------- */


/**
 * Get all site settings
 */
function getAllSettings()
{
	global $settings;
	# check if it already exists
	if(isset($settings)) {
		if(isset($settings[0]))	{ return $settings[0]; }
		else					{ return $settings; }
	}
	else {

	    global $db;
		global $database;

	    /* first check if table settings exists */
	    $query    = 'SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = "'. $db['name'] .'" AND table_name = "settings";';

	    /* execute */
	    try { $count = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		/* return true if it exists */
		if($count[0]['count'] == 1) {

			/* select database */
			$database->selectDatabase($db['name']);

		    /* get settings */
		    $query    = 'select * from settings where id = 1';
		    $settings = $database->getArray($query);

			/* return settings */
			return($settings[0]);
		}
		else {
			return false;
		}

	}
}


/**
 * Get all mail settings
 */
function getAllMailSettings()
{
    global $db;                                                                      # get variables from config file
    global $database;

    /* first check if table settings exists */
    $query    = 'SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = "'. $db['name'] .'" AND table_name = "settingsMail";';

    /* execute */
    try { $count = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	/* return true if it exists */
	if($count[0]['count'] == 1) {

		/* select database */
		$database->selectDatabase($db['name']);

	    /* first update request */
	    $query    = 'select * from `settingsMail` where id = 1';
	    $settings = $database->getArray($query);

		/* return settings */
		return($settings[0]);
	}
	else {
		return false;
	}
}


/**
 * validate email
 */
function checkEmail($email) {
	if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email)) 	{ return false; }
    else 														{ return true; }
}


/**
 * validate hostname
 */
function validateHostname($hostname)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname) 	//valid chars check
            && preg_match("/^.{1,253}$/", $hostname) 										//overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)   ); 				//length of each label
}


/**
 * Shorten text
 */
function ShortenText($text, $chars = 25) {
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
 * Parse section/subnet permissions
 */
function parsePermissions($perm)
{
	switch($perm) {
		case "0": $r = _("No access");				break;
		case "1": $r = _("Read");					break;
		case "2": $r = _("Read / Write");			break;
		case "3": $r = _("Read / Write / Admin");	break;
		default:  $r = _("error");
	}
	return $r;
}


/**
 * secunds to hms
 */
function sec2hms($sec, $padHours = false)
  {
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
 *	get php exec path
 */
function getPHPExecutableFromPath()
{
	/*
	not used anymore as it is not reliable, using PHP_BINDIR instead
	*/
	$paths = explode(PATH_SEPARATOR, getenv('PATH'));
	foreach ($paths as $path) {
		// we need this for XAMPP (Windows)
		if (strstr($path, 'php.exe') && isset($_SERVER["WINDIR"]) && file_exists($path) && is_file($path)) {
			return $path;
		}
	}

	//unix
	$php_executable = PHP_BINDIR."/php";
	if (file_exists($php_executable) && is_file($php_executable)) {
		return $php_executable;
	}

	return FALSE; // not found
}










/* @menu builder */

/**
 * Build the HTML menu
 *
 * based on http://pastebin.com/GAFvSew4
 */
function get_menu_html( $subnets, $rootId = 0 )
{
		$html = array();

		foreach ( $subnets as $item )
			$children[$item['masterSubnetId']][] = $item;

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# verify subnetId
		if(isset($_GET['subnetId']))	{
			if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); }
		}

		# display selected subnet as opened
		if(isset($_GET['subnetId'])) 	{ $allParents = getAllParents ($_GET['subnetId']); }
		else 							{ $allParents = array(); }

		# Menu start
		$html[] = '<ul id="subnets">';

		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# count levels
			$count = count( $parent_stack ) + 1;

			# set opened or closed tag for displaying proper folders
			if(in_array($option['value']['id'], $allParents))		{ $open = "open";	$openf = "-open"; }
			else													{ $open = "close";	$openf = ""; }

			# show also child's by default
			if($option['value']['id']==$_GET['subnetId']) {
				if(subnetContainsSlaves($_GET['subnetId']))			{ $open = "open";	$openf = "-open"; }
				else												{ $open = "close";	$openf = ""; }
			}

			# override if cookie is set
			if(isset($_COOKIE['expandfolders'])) {
				if($_COOKIE['expandfolders'] == "1")				{ $open='open';		$openf = "-open"; }
			}

			# for active class
			if($_GET['page']=="subnets" && ($option['value']['id'] == $_GET['subnetId']))			{ $active = "active";	$leafClass=""; }
			else 																					{ $active = ""; 		$leafClass="icon-gray" ;}

			# override folder
			if($option['value']['isFolder'] == 1 && ($option['value']['id'] == $_GET['subnetId']))	{ $open = "open"; $openf = "-open"; $active = "active"; }

			# check for permissions if id is provided
			if($option['value']['id'] != "") {
				$sp = checkSubnetPermission ($option['value']['id']);
			}

			if ( $option === false )
			{
				$parent = array_pop( $parent_stack );

				# HTML for menu item containing childrens (close)
				$html[] = '</ul>';
				$html[] = '</li>';
			}
			# Has children
			elseif ( !empty( $children[$option['value']['id']] ) )
			{
				# if user has access permission
				if($sp != 0) {
					# folder
					if($option['value']['isFolder'] == 1) {
						$html[] = '<li class="folderF folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder fa-folder'.$openf.'" rel="tooltip" data-placement="right" data-html="true" title="'._('Folder contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("folder",$option['value']['sectionId'],$option['value']['id']).'">'.$option['value']['description'].'</a>';
					}
					# print name
					elseif($option['value']['showName'] == 1) {
						$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.Transform2long($option['value']['subnet']).'/'.$option['value']['mask'].'">'.$option['value']['description'].'</a>';
					}
					# print subnet
					else {
						$html[] = '<li class="folder folder-'.$open.' '.$active.'""><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.Transform2long($option['value']['subnet']).'/'.$option['value']['mask'].'</a>';
					}

					# print submenu
					if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
					else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

					array_push( $parent_stack, $option['value']['masterSubnetId'] );
					$parent = $option['value']['id'];
				}
			}
			# Leaf items (last)
			else
				if($sp != 0) {
					# folder - opened
					if($option['value']['isFolder'] == 1) {
						$html[] = '<li class="leaf '.$active.'"><i class="fa fa-gray fa-sfolder fa-folder'.$openf.'"></i>';
						$html[] = '<a href="'.create_link("folder",$option['value']['sectionId'],$option['value']['id']).'">'.$option['value']['description'].'</a></li>';
					}
					# print name
					elseif($option['value']['showName'] == 1) {
						$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.Transform2long($option['value']['subnet']).'/'.$option['value']['mask'].'">'.$option['value']['description'].'</a></li>';
					}
					# print subnet
					else {
						$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.Transform2long($option['value']['subnet']).'/'.$option['value']['mask'].'</a></li>';
					}
				}
		}

		# Close menu
		$html[] = '</ul>';

		return implode( "\n", $html );
}


/**
 * Build the HTML menu for VLANS
 *
 * based on http://pastebin.com/GAFvSew4
 */
function get_menu_vlan( $vlans, $sectionId )
{
		$html = array();

		# must be numberic
		if(isset($_GET['vlanId']))		{ if(!is_numeric($_GET['vlanId']))		{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }
		if(isset($_GET['subnetId']))	{ if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }

		# Menu start
		$html[] = '<ul id="subnets">';

		# loop through vlans
		foreach ( $vlans as $item ) {

			# set open / closed -> vlan directly
			if($_GET['subnetId'] == $item['vlanId'] && $_GET['page']=="vlan") {
				$open = "open";
				$active = "active";
				$leafClass="fa-gray";
			}
			elseif(isSubnetIdVlan ($_GET['subnetId'], $item['vlanId'])) {
				$open = "open";
				$active = "";
				$leafClass="fa-gray";
			}
			else {
				$open = "close";
				$active = "";
				$leafClass="fa-gray";
			}

			# new item
			$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('VLAN contains subnets').'.<br>'._('Click on folder to open/close').'"></i>';
			$html[] = '<a href="'.create_link("vlan",$sectionId,$item['vlanId']).'" rel="tooltip" data-placement="right" title="'.$item['description'].'">'.$item['number'].' ('.$item['name'].')</a>';

			# fetch all subnets in VLAN
			$subnets = getAllSubnetsInSectionVlan ($item['vlanId'], $sectionId);

			# if some exist print next ul
			if($subnets)
			{
				# print subnet
				if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
				else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

				# loop through subnets
				foreach($subnets as $subnet) {
					# check permission
					$permission = checkSubnetPermission ($subnet['id']);
					if($permission > 0) {

						# for active class
						if(isset($_GET['subnetId']) && ($subnet['id'] == $_GET['subnetId']))	{ $active = "active";	$leafClass=""; }
						else 																	{ $active = ""; 		$leafClass="icon-gray" ;}

						# check if showName is set
						if($subnet['showName'] == 1) {
							$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.Transform2long($subnet['subnet']).'/'.$subnet['mask'].'">'.$subnet['description'].'</a></li>';
						}
						else {
							$html[] = '<li class="leaf '.$active.'""><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$subnet['description'].'">'.Transform2long($subnet['subnet']).'/'.$subnet['mask'].'</a></li>';
						}

					}
				}

				# close ul
				$html[] = '</ul>';
				$html[] = '</li>';
			}
		}

		# Close menu
		$html[] = '</ul>';

		return implode( "\n", $html );
}


/**
 * Build the HTML menu for VLANS
 *
 * based on http://pastebin.com/GAFvSew4
 */
function get_menu_vrf( $vrfs, $sectionId )
{
		$html = array();

		# Menu start
		$html[] = '<ul id="subnets">';

		# vrfId must be numberic
		if(isset($_GET['subnetId']))	{ if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }
		if(isset($_GET['vrfIf']))		{ if(!is_numeric($_GET['vrfId']))		{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }

		# loop through vlans
		foreach ( $vrfs as $item ) {

			# set open / closed -> vlan directly
			if($_GET['subnetId'] == $item['vrfId'] && $_GET['page']=="vrf") {
				$open = "open";
				$active = "active";
				$leafClass="fa-gray";
			}
			elseif(isSubnetIdVrf ($_GET['subnetId'], $item['vrfId'])) {
				$open = "open";
				$active = "";
				$leafClass="fa-gray";
			}
			else {
				$open = "close";
				$active = "";
				$leafClass="fa-gray";
			}

			# new item
			$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('VRF contains subnets').'.<br>'._('Click on folder to open/close').'"></i>';
			$html[] = '<a href="'.create_link("vrf",$sectionId,$item['vrfId']).'" rel="tooltip" data-placement="right" title="'.$item['description'].'">'.$item['name'].'</a>';

			# fetch all subnets in VLAN
			$subnets = getAllSubnetsInSectionVrf ($item['vrfId'], $sectionId);

			# if some exist print next ul
			if($subnets)
			{
				# print subnet
				if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
				else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

				# loop through subnets
				foreach($subnets as $subnet) {
					# check permission
					$permission = checkSubnetPermission ($subnet['id']);
					if($permission > 0) {

						# for active class
						if(isset($_GET['subnetId']) && ($subnet['id'] == $_GET['subnetId']))	{ $active = "active";	$leafClass=""; }
						else 																	{ $active = ""; 		$leafClass="icon-gray" ;}

						# check if showName is set
						if($subnet['showName'] == 1) {
							$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.Transform2long($subnet['subnet']).'/'.$subnet['mask'].'">'.$subnet['description'].'</a></li>';
						}
						else {
							$html[] = '<li class="leaf '.$active.'""><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$subnet['description'].'">'.Transform2long($subnet['subnet']).'/'.$subnet['mask'].'</a></li>';
						}

					}
				}

				# close ul
				$html[] = '</ul>';
				$html[] = '</li>';
			}
		}

		# Close menu
		$html[] = '</ul>';

		return implode( "\n", $html );
}


/**
 * Print subnets structure
 */
function printSubnets( $subnets, $actions = true, $vrf = "0", $custom = array() )
{
		$html = array();

		$rootId = 0;									# root is 0

		if(sizeof($subnets) > 0) {
		foreach ( $subnets as $item ) {
			$item = (array) $item;
			$children[$item['masterSubnetId']][] = $item;
		}
		}

		global $settings;
		/* set hidden fields */
		$ffields = json_decode($settings['hiddenCustomFields'], true);
		if(is_array($ffields['subnets']))	{ $ffields = $ffields['subnets']; }
		else								{ $ffields = array(); }

		# must be numeric
		if(isset($_GET['subnetId']))	{ if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); } }

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
				if($option['value']['allowRequests'] == 1) 			{ $requests = "<i class='fa fa-gray fa-check'></i>"; }					# requests enabled
				else 												{ $requests = ""; }														# request disabled

				# hosts check
				if($option['value']['pingSubnet'] == 1) 			{ $pCheck = "<i class='fa fa-gray fa-check'></i>"; }					# ping check enabled
				else 												{ $pCheck = ""; }														# ping check disabled

				#vrf
				if($vrf == "1") {
					# get VRF details
					if(($option['value']['vrfId'] != "0") && ($option['value']['vrfId'] != "NULL") ) {
						$vrfTmp = getVRFDetailsById ($option['value']['vrfId']);
						$vrfText = $vrfTmp['name'];
					}
					else {
						$vrfText = "";
					}
				}

			# print table line
			if(strlen($option['value']['subnet']) > 0) {
				// verify permission
				$permission = checkSubnetPermission ($option['value']['id']);
				// print item
				if($permission != 0) {
					$html[] = "<tr>";
					# which level?
					if($count==1) {
						# is folder?
						if($option['value']['isFolder']==1) {
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i>  $description</td>";

						}
						else {
							# last?
							if(!empty( $children[$option['value']['id']])) {
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
							} else {
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";
							}
					}
					} else {
						# is folder?
						if($option['value']['isFolder']==1) {
							# last?
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> $description</td>";
						}
						else {
							# last?
							if(!empty( $children[$option['value']['id']])) {
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
							}
							else {
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
								$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";

							}
						}
					}
					$html[] = "	<td>$vlan</td>";
					#vrf
					if($vrf == "1") {
					$html[] = "	<td class='hidden-xs hidden-sm'>$vrfText</td>";
					}
					$html[] = "	<td class='hidden-xs hidden-sm'>$requests</td>";
					$html[] = "	<td class='hidden-xs hidden-sm'>$pCheck</td>";
					# custom
					if(sizeof($custom)>0) {
						foreach($custom as $field) {
							if(!in_array($field['name'], $ffields)) {
					    		$html[] =  "	<td class='hidden-xs hidden-sm'>".$option['value'][$field['name']]."</td>";
							}
				    	}
					}
					if($actions) {
					$html[] = "	<td class='actions' style='padding:0px;'>";
					$html[] = "	<div class='btn-group'>";
					if($option['value']['isFolder']==1) {
						$html[] = "		<button class='btn btn-xs btn-default add_folder'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default add_folder'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
					} else {
						$html[] = "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
					}
					$html[] = "	</div>";
					$html[] = "	</td>";
					}
					$html[] = "</tr>";
				}
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


/**
 *	get whole tree path for subnetId - from slave to parents
 */
function getAllParents ($subnetId)
{
	$parents = array();
	$root = false;

	while($root == false) {
		$subd = getSubnetDetailsById($subnetId);		# get subnet details

		if($subd['masterSubnetId'] != 0) {
			array_unshift($parents, $subd['masterSubnetId']);
			$subnetId  = $subd['masterSubnetId'];
		}
		else {
			array_unshift($parents, $subd['masterSubnetId']);
			$root = true;
		}
	}

	return $parents;
}


/**
 *	get whole tree path for subnetId - from parent all slaves
 *
 * 	if multi than create multidimensional array
 */
$removeSlaves = array();

function getAllSlaves ($subnetId, $multi = false)
{
	# check cache
	if($vtmp = checkCache("allslaves", $subnetId."_$multi")) {
		return $vtmp;
	}
	else {

		global $removeSlaves;
		$end = false;			# breaks while

		$removeSlaves[] = $subnetId;		# first

		# db
		global $database;

		while($end == false) {

			/* get all immediate slaves */
			$query = "select * from `subnets` where `masterSubnetId` = '$subnetId' order by `id` asc; ";
			/* execute query */
			try { $slaves2 = $database->getArray( $query ); }
			catch (Exception $e) {
	        	$error =  $e->getMessage();
	        	print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        	return false;
	        }

			# we have more slaves
			if(sizeof($slaves2) != 0) {
				# recursive
				foreach($slaves2 as $slave) {
					$removeSlaves[] = $slave['id'];
					getAllSlaves ($slave['id']);
					$end = true;
				}
			}
			# no more slaves
			else {
				$end = true;
			}
		}

		# save cache
		if(sizeof($removeSlaves)>0) {
			writeCache("allslaves", $subnetId."_$multi", $removeSlaves);
		}
	}
}


/**
 *	get whole tree path for subnetId - from parent all slaves
 *
 * 	if multi than create multidimensional array
 */
function getAllSlavesReturn ($subnetId)
{
	# check cache
	if($vtmp = checkCache("allslavesReturn", $subnetId)) {
		return $vtmp;
	}
	else {
		$end = false;					# breaks while

		$allSlaves[] = $subnetId;		# first

		# db
		global $database;

		while($end == false) {

			/* get all immediate slaves */
			$query = "select `id` from `subnets` where `masterSubnetId` = '$subnetId' order by `id` asc; ";
			/* execute query */
			try { $slaves2 = $database->getArray( $query ); }
			catch (Exception $e) {
	        	$error =  $e->getMessage();
	        	print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        	return false;
	        }

			# we have more slaves
			if(sizeof($slaves2) != 0) {
				# recursive
				foreach($slaves2 as $slave) {
					$allSlaves[] = $slave['id'];
					getAllSlavesReturn ($slave['id']);
					$end = true;
				}
			}
			# no more slaves
			else {
				$end = true;
			}
		}

		# save cache
		if(sizeof($allSlaves)>0) {
			writeCache("allslaves", $subnetId, $allSlaves);
		}

		# return
		return $allSlaves;
	}
}



/**
 *	print breadcrumbs
 */
function printBreadcrumbs ($req)
{
	# subnets
	if(isset($req['ipaddrid']))	{
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = getAllParents ($req['subnetId']);
			print "<ul class='breadcrumb'>";
			# remove root - 0
			array_shift($parents);

			# section details
			if(is_numeric($req['section']))	{ $section = getSectionDetailsById($req['section']); }					# if id is provided
			else							{ $section = getSectionDetailsByName($req['section']); }				# if name is provided

			print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";	# section name

			foreach($parents as $parent) {
			$subnet = getSubnetDetailsById($parent);
			if($subnet['isFolder']==1) {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";								# subnets in between
			} else {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'>$subnet[description] (".Transform2long($subnet['subnet']).'/'.$subnet['mask'].")</a> <span class='divider'></span></li>";								# subnets in between
			}
			}
			# parent subnet
			$subnet = getSubnetDetailsById($req['subnetId']);
			print "	<li><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[description] (".Transform2long($subnet['subnet']).'/'.$subnet['mask'].")</a> <span class='divider'></span></li>";																# active subnet
			# ip
			$ip = getIpAddrDetailsById($req['ipaddrid']);
			print "	<li class='active'>$ip[ip_addr]</li>";																# IP address
			print "</ul>";
		}
	}
	# subnets
	elseif($req['page'] == "subnets")	{
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = getAllParents ($req['subnetId']);
			print "<ul class='breadcrumb'>";
			# remove root - 0
			array_shift($parents);

			# section details
			if(is_numeric($req['section']))	{ $section = getSectionDetailsById($req['section']); }					# if id is provided
			else							{ $section = getSectionDetailsByName($req['section']); }				# if name is provided

			print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";	# section name

			foreach($parents as $parent) {
			$subnet = getSubnetDetailsById($parent);
			if($subnet['isFolder']==1) {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";								# subnets in between
			} else {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'>$subnet[description] (".Transform2long($subnet['subnet']).'/'.$subnet['mask'].")</a> <span class='divider'></span></li>";								# subnets in between
			}
			}
			$subnet = getSubnetDetailsById($req['subnetId']);
			print "	<li class='active'>$subnet[description] (".Transform2long($subnet['subnet']).'/'.$subnet['mask'].")</li>";																# active subnet
			print "</ul>";
		}
	}
	# subnets
	if($req['page'] == "folder")	{
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = getAllParents ($req['subnetId']);
			print "<ul class='breadcrumb'>";
			# remove root - 0
			array_shift($parents);

			# section details
			if(is_numeric($req['section']))	{ $section = getSectionDetailsById($req['section']); }					# if id is provided
			else							{ $section = getSectionDetailsByName($req['section']); }				# if name is provided

			print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";	# section name

			foreach($parents as $parent) {
			$subnet = getSubnetDetailsById($parent);
			print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";								# subnets in between
			}
			$subnet = getSubnetDetailsById($req['subnetId']);
			print "	<li class='active'>$subnet[description]</li>";																# active subnet
			print "</ul>";
		}
	}
	# admin
	else if($req['page'] == "admin")
	{

	}
	# tools
	else if ($req['page'] == "tools") {
		if(isset($req['tpage'])) {
			print "<ul class='breadcrumb'>";
			print "	<li><a href='".create_link("tools")."'>"._('Tools')."</a> <span class='divider'></span></li>";
			print "	<li class='active'>$req[tpage]></li>";
			print "</ul>";
		}
	}
}













/* @cache functions */

/**
 * Check if object already cached
 */
function checkCache($type, $objectID)
{
	global $cache;
	if(isset($cache[$type][$objectID])) { return $cache[$type][$objectID]; }
	else								{ return false; }
}


/**
 * Save to cache
 */
function writeCache($type, $objectID, $value)
{
	global $cache;
	$cache[$type][$objectID] = $value;
}




?>