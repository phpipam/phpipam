<?php

#
# include database functions
#
require( dirname(__FILE__) . '/../config.php' );
require( dirname(__FILE__) . '/../functions/dbfunctions.php' );
require( dirname(__FILE__) . '/../functions/version.php' );


/**
 * php debugging on/off - ignore notices
 */
if ($debugging == false) {
  	ini_set('display_errors', 1);
    error_reporting(E_ERROR | E_WARNING);
}
else{
    ini_set('display_errors', 1); 
    error_reporting(E_ALL ^ E_NOTICE);
}



/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5
 */
if(!function_exists(create_link)) {
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
}




/**
 * Update log table
 */
function updateLogTable ($command, $details = NULL, $severity = 0)
{
    global $db;                                                                	
	$database = new database($db['host'], $db['user'], $db['pass']);    
    
    /* select database */
    try {
    	$database->selectDatabase($db['name']);
    }
    catch (Exception $e) {
    	return false;
    	die();
	}
	
    /* Check connection */
	if (!$database->connect_error) {

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
    		return false;
		}
		return true;
	}
	else {
		return false;
	}
}


/**
 * Get user details by name
 */
function getUserDetailsByName ($username)
{
	global $database;
	if(!is_object($database)) {
		global $db;
		$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
	}
		
	/* set query, open db connection and fetch results */
    $query    = 'select * from users where `username` LIKE BINARY "'. $username .'";';

    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
    //we only need 1st field
    $details = $details[0];
    
    /* return results */
    return($details);
}


/**
 * Get active users username - from session!
 */
function getActiveUserDetails ()
{
	if(isset($_SESSION['ipamusername'])) {
    	return getUserDetailsByName ($_SESSION['ipamusername']);
    }
    else {
    	return false;
    }
    session_write_close();
}


/**
 * Get user lang
 */
function getUserLang ($username)
{
    global $db;
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);                                                                      
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
 * Get lang by id
 */
function getLangById ($id)
{
    global $db;
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);                                                                      
    /* set query, open db connection and fetch results */
    $query    = 'select * from `lang` where `l_id` = "'.$id.'";';

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
 * Get all site settings
 */
function getAllSettings()
{
    global $db;                                                                      
	$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    /* Check connection */
    if ($database->connect_error) {
    	die('Connect Error (' . $database->connect_errno . '): '. $database->connect_error);
	}
	
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
	
	    /* first update request */
	    $query    = 'select * from settings where id = 1';

	    /* execute */
	    try { $settings = $database->getArray( $query ); }
	    catch (Exception $e) { 
        	$error =  $e->getMessage(); 
        	print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        	return false;
        }   
		/* return settings */
		return($settings[0]);
	}
	else {
		return false;
	}
}


/**
 * Get Domain settings for authentication
 */
function getADSettings()
{
    global $db;                                                                      
	$database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    
    /* Check connection */
    if ($database->connect_error) {
    	die('Connect Error (' . $database->connect_errno . '): '. $database->connect_error);
	}
	
    /* first check if table settings exists */
    $query    = 'SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = "'. $db['name'] .'" AND table_name = "settingsDomain";';

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
	    $query    = 'select * from `settingsDomain` limit 1;';

	    /* execute */
	    try { $settings = $database->getArray( $query ); }
	    catch (Exception $e) { 
        	$error =  $e->getMessage(); 
        	print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        	return false;
        } 
	    
	    /* reformat DC */
  		$dc = str_replace(" ", "", $settings[0]['domain_controllers']);
  		$dcTemp = explode(";", $dc);
  		$settings[0]['domain_controllers'] = $dcTemp;
  		  
		/* return settings */
		return($settings[0]);
	}
	else {
		return false;
	}
}


/**
 * Login authentication
 *
 * First we try to authenticate via local database
 * if it fails we querry the AD, if set in config file
 */
function checkLogin ($username, $md5password, $rawpassword) 
{
    global $db;
    
    # set failed flag to update authFailed table
    $authFailed = true;
    $updatepass = false;
    $uerror		= "";
    $lerror		= "";

    # fetch settings to get auth types
    $settings = getAllSettings(); 
    
    # for login check
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    
	# escape vars to prevent SQL injection
	$username 	 = $database->real_escape_string($username);

    # try to fetch user
    $query 		= 'select * from `users` where `username` = "'. $username .'" limit 1;';

    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
   	# verify type and password
    if (sizeof($result)>0) 	{
    	# reset var
    	$user = $result[0];
    
    	/**
    	 * local auth
    	 */
    	if($user['domainUser']=="0") {
			# try crypt
			if(substr($user['password'], 0,1)=="$") {
				if($user['password']==crypt($rawpassword, $user['password'])) 	{ $authFailed = false; }
			}
			else {
		    	if($user['password']==$md5password) 							{ $authFailed = false; $updatepass = true; }	//second md5 - standard, update passwords!
				else															{ $authFailed = true; }							//no math, fail				
			}
			
			# ok
			if($authFailed == false) {
			
				# try to update pass to crypt, only if version already changed
				if($updatepass && $settings['version']=="1.1") { update_user_pass_to_crypt($username, $rawpassword); }
		    	
		    	# save results
		    	$uerror = 'Login successful';	
		    	$lerror = 'User '.$user['real_name'].' logged in.'; 
			}
			# fail
			else {
				$uerror = 'Failed to log in';	
		    	$lerror = 'User '.$username.' failed to log in.';
			}	    	
    	}
    	/**
    	 *	AD Domain auth
    	 */
    	elseif($settings['domainAuth']== "1" && $user['domainUser']=="1") {

			# try to authenticate against AD		
			$authAD = checkADLogin ($username, $rawpassword);
	
			/**
			 *	AD auth suceeded
			 */
			if($authAD == "ok") {
				# set flag
				$authFailed = false;
    			
		    	# save results
		    	$uerror = 'AD Login successful';	
		    	$lerror = 'User '.$user['real_name'].' logged in.'; 
	    	}
	    	# failed to connect
	    	else if ($authAD == 'Failed to connect to AD!') {
				$uerror = 'Failed to connect to AD server';	
				$lerror = 'Failed to connect to AD!'; 	
			}
			# failed to authenticate
			else if ($authAD == 'Failed to authenticate user via AD!') {
			    $uerror = 'Failed to authenticate user against AD';	
			    $lerror = 'User failed to authenticate against AD.'; 	
			}
			# wrong user/pass
			else {
			    $uerror = 'Wrong username or password';
			    $lerror = 'User failed to authenticate against AD.'; 
			}
    	}
    	/**
    	 *	LDAP auth
    	 */
    	elseif($settings['domainAuth']== "2" && $user['domainUser']=="1") {

			# try to authenticate against AD		
			$authAD = checkADLogin ($username, $rawpassword);
	
			/**
			 *	AD auth suceeded
			 */
			if($authAD == "ok") {
				# set flag
				$authFailed = false;
    			
		    	# save results
		    	$uerror = 'LDAP Login successful';	
		    	$lerror = 'User '.$user['real_name'].' logged in.'; 
	    	}
	    	# failed to connect
	    	else if ($authAD == 'Failed to connect to AD!') {
				$uerror = 'Failed to connect to LDAP server';	
				$lerror = 'Failed to connect to LDAP!'; 	
			}
			# failed to authenticate
			else if ($authAD == 'Failed to authenticate user via AD!') {
			    $uerror = 'Failed to authenticate user against LDAP';	
			    $lerror = 'User failed to authenticate against LDAP.'; 	
			}
			# wrong user/pass
			else {
			    $uerror = 'Wrong username or password';
			    $lerror = 'User failed to authenticate against LDAP.'; 
			}
    	}
    	/**
    	 *	Username ok, but no password match in local database, other not configured
    	 */
    	else {
			$uerror = 'Failed to log in';	
	    	$lerror = 'User '.$username.' failed to log in.';
    	}
    }
	# fail - no username match
	else {
		$uerror = 'Failed to log in';	
    	$lerror = 'User '.$username.' failed to log in.';
	}
	
	
	/**
	 * print errors
	 */
	if($authFailed == true) {
    	# print success
	    print('<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>'._($uerror).'!</div>');
    	# write log file
	    updateLogTable ($lerror, "", 2); 
	    
	    # also update blocked IP table
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))	{ $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
		else										{ $ip = $_SERVER['REMOTE_ADDR']; }
		# add block count
		block_ip ($ip);	    
	}
	/**
	 * print success
	 */
	else {
		# get user lang
		$lang = getLangById ($user['lang']);

		/* start session and set variables */
		global $phpsessname; 
		if(strlen($phpsessname)>0) { session_name($phpsessname); }  
		session_start();
		$_SESSION['ipamusername'] = $username;
		$_SESSION['ipamlanguage'] = $lang['l_code'];
		$_SESSION['lastactive']   = time();
		session_write_close();
    			
    	# print success
    	print('<div class="alert alert-success">'._($uerror).'!</div>');	
    	# write log file
    	updateLogTable ($lerror, "", 0); 		
	}
}




/**
 * Check user against AD
 */
function checkADLogin ($username, $password)
{
    /* get All settings */
    $settings = getAllSettings();
    
	# include login script
	include (dirname(__FILE__) . "/adLDAP/src/adLDAP.php");

	# open connection
	try {
		# get settings for connection
		$ad = getADSettings();
		
		# AD
    	$adldap = new adLDAP(array( 'base_dn'=>$ad['base_dn'], 'account_suffix'=>$ad['account_suffix'], 
    								'domain_controllers'=>$ad['domain_controllers'], 'use_ssl'=>$ad['use_ssl'],
    								'use_tls'=> $ad['use_tls'], 'ad_port'=> $ad['ad_port']
    								));
    	
    	# set OpenLDAP flag
    	if($settings['domainAuth'] == "2") { $adldap->setUseOpenLDAP(true); }
    	
	}
	catch (adLDAPException $e) {
		die('<div class="alert alert-danger">'. $e .'</div>');
	}

	# user authentication
	$authUser = $adldap->authenticate($username, $password);
	
	# result
	if($authUser == true) { 
		return 'ok'; 
	}
	else { 
		$err = $adldap->getLastError();
		print "<div class='alert alert-danger'>$err</div>";
		return 'Failed to authenticate user via AD!'; 
	}
}


/**
 * Check if user is admin
 */
function checkAdmin ($die = true) 
{
	global $database;   
    
    /* first get active username */
    global $phpsessname; 
    if(strlen($phpsessname)>0) { session_name($phpsessname); }  
    session_start();
    $ipamusername = $_SESSION['ipamusername'];
    session_write_close();
    
    /* set check query and get result */
    $query = 'select role from users where username = "'. $ipamusername .'";';
    
    /* execute */
    try { $role = $database->getRow( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
    /* return true if admin, else false */
    if ($role[0] == "Administrator") {
        return true;
    }
    else {
    	//die
    	if($die == true) { die('<div class="alert alert-danger">'._('Administrator level privileges are required to access this site').'!</div>'); }
    	//return false if called
    	else 			 { return false; }
    	//update log
    	updateLogTable ('User '. $ipamusername .' tried to access admin page.', "", 2);
    }      
}





/* @crypt functions */
if(!function_exists(crypt_user_pass))
{
/**
 *	function to crypt user pass, randomly generates salt. Use sha256 if possible, otherwise Blowfish or md5 as fallback
 *
 *		types:	
 *			CRYPT_MD5 == 1   		(Salt starting with $1$, 12 characters )
 *			CRYPT_BLOWFISH == 1		(Salt starting with $2a$. The two digit cost parameter: 09. 22 characters )
 *			CRYPT_SHA256 == 1		(Salt starting with $5$rounds=5000$, 16 character salt.)
 *			CRYPT_SHA512 == 1		(Salt starting with $6$rounds=5000$, 16 character salt.)
 *
 */
function crypt_user_pass($input)
{
	# initialize salt
	$salt = "";
	# set possible salt characters in array
	$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
	# loop to create salt
	for($i=0; $i < 22; $i++) { $salt .= $salt_chars[array_rand($salt_chars)]; }
	# get prefix
	$prefix = detect_crypt_type();
	# return crypted variable
	return crypt($input, $prefix.$salt);
}

/**
 *	this function will detect highest crypt type to use for system
 */
function detect_crypt_type () 
{
	if(CRYPT_SHA512 == 1)		{ return '$6$rounds=3000$'; }
	elseif(CRYPT_SHA512 == 1)	{ return '$5$rounds=3000$'; }
	elseif(CRYPT_BLOWFISH == 1)	{ return '$2y$'; }
	elseif(CRYPT_MD5 == 1)		{ return '$5$rounds=3000$'; }
	else						{ die("<div class='alert alert-danger'>No crypt types supported!</div>"); }
}

/**
 * update users pass from md5 to crypt
 */
function update_user_pass_to_crypt($username, $rawpassword) 
{
   	global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    
    # crypt pass
    $password = crypt_user_pass($rawpassword);
	$password = $database->real_escape_string($password);
    
    # set check query and get result
    $query = "update `users` set `password`='$password' where `username` = '$username';";
    
    # execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { 
    	print "<div class='alert alert-danger'>".$e->getMessage()."</div>";
        return false;
    }
	return true;
}

}



/* @block IP address from login for 5 minutes ----------- */

/**
 *	add/update entry
 */
function block_ip ($ip) 
{
	# first check if already in
	if(check_blocked_ip ($ip)) {
		# update
		update_blocked_count($ip);
	}
	# if not in add first entry
	else {
		add_blocked_entry($ip);
	}
	return true;
}


/**
 *	check
 */
function check_blocked_ip ($ip) 
{
	# first purge
	purge_blocked_entries();
	
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
	# set date
	$now = date("Y-m-d H:i:s", time() - 5*60);
    
    # set check query and get result
    $query = "select * from `loginAttempts` where `ip` = '$ip' and `datetime` > '$now';";
    
    # execute
    try { $ips = $database->getArray( $query ); }
    catch (Exception $e) { 
        return false;
    }
    
    # verify
    if(sizeof($ips[0])>0)	{ return $ips[0]['count']; }
    else					{ return false; }
}

/**
 *	add block count
 */
function update_blocked_count($ip)
{
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
	# query
	$query = "update `loginAttempts` set `count`=`count`+1 where `ip` = '$ip'; ";

    # execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {}
    # return
    return true;
}

/**
 *	add new block entry
 */
function add_blocked_entry($ip)
{
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']); 
	# query
	$query = "insert into `loginAttempts` (`ip`,`count`) values ('$ip',1); ";

    # execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
	    	print $e->getMessage();
    }
    # return
    return true;
}
 
/**
 *	purge records
 */
function purge_blocked_entries()
{
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
	# set date
	$now = date("Y-m-d H:i:s", time() - 5*60);
	# query
	$query = "delete from `loginAttempts` where `datetime` < '$now'; ";

    # execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {}
    # return
    return true;
}








/*********************************
	Upgrade check functions
*********************************/


/**
 * Get all tables
 */
function getAllTables()
{
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    
    /* first update request */
    $query    = 'show tables;';

    /* execute */
    try { $tables = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
  
	/* return all tables */
	return $tables;
}


/**
 * Check if specified table exists
 */
function tableExists($table, $die = true)
{
	global $db;
	$database = new database($db['host'], $db['user'], $db['pass'], $db['name'], null, false); 

    /* Check connection */
    if ($database->connect_error) {
    	if($die) { die('Connect Error (' . $database->connect_errno . '): '. $database->connect_error); }
    	else	 { return false; }
	}
    
    /* first update request */
    $query    = 'SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = "'. $db['name'] .'" AND table_name = "'. $table .'";';

    /* execute */
    try { $count = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
  
	/* return true if it exists */
	if($count[0]['count'] == 1)	{ return true; }
	else 						{ return false; }
}


/**
 * describe specific table
 */
function fieldExists($table, $fieldName)
{
    global $db;                                                                      
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    /* first update request */
    $query    = 'describe `'. $table .'` `'. $fieldName .'`;';

    /* execute */
    try { $count = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
  
	/* return true if it exists */
	if(sizeof($count) == 0) { return false; }
	else 					{ return true; }
}


/**
 * install databases
 */
function installDatabase($rootuser, $rootpass, $dropdb = false, $createdb = true, $creategrants = true)
{    
	global $db;
    error_reporting(E_ERROR); 
    
    # open connection
    $databaseRoot    = new database($db['host'], $rootuser, $rootpass, null, null, false); 
    
    /* Check connection */
    if ($databaseRoot->connect_error) {
    	die('<div class="alert alert-danger">Connect Error (' . $databaseRoot->connect_errno . '): '. $databaseRoot->connect_error). "</div>";
	}

 	/* first drop database if requested */
 	if($dropdb) {
	    $query = "drop database ". $db['name'] .";";
	
	    /* execute */
	    try {
	    	$databaseRoot->executeQuery( $query );
	    }
	    catch (Exception $e) {
	    	$error =  $e->getMessage();
	    	die('<div class="alert alert-danger">'. $error .'</div>');
		} 
	}

    
 	/* first create database if requested */
 	if($createdb) {
	    $query = "create database ". $db['name'] .";";
	
	    /* execute */
	    try {
	    	$databaseRoot->executeQuery( $query );
	    }
	    catch (Exception $e) {
	    	$error =  $e->getMessage();
	    	die('<div class="alert alert-danger">'. $error .'</div>');
		} 
	}
    
    /* select database */
	$databaseRoot->selectDatabase($db['name']);

	/* set permissions! */
	if($creategrants) {
		$query = 'grant ALL on '. $db['name'] .'.* to '. $db['user'] .'@localhost identified by "'. $db['pass'] .'";';
	
	    /* execute */
	    try {
	    	$databaseRoot->executeQuery( $query );
	    }
	    catch (Exception $e) {
	    	$error =  $e->getMessage();
	    	die('<div class="alert alert-danger">Cannot set permissions for user '. $db['user'] .': '. $error. '</div>');
		}
	}
    
    /* try importing SCHEMA file */
    $query  = file_get_contents("../../db/SCHEMA.sql");
    
    /* execute */
    try {
    	$databaseRoot->executeMultipleQuerries( $query );
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	
    	# drop database!
 	    $query = "UNLOCK TABLES; drop database ". $db['name'] .";";
	    try { $databaseRoot->executeMultipleQuerries( $query ); }
	    catch (Exception $e) {
	    	$error =  $e->getMessage();
	    	die('<div class="alert alert-danger">Cannot set permissions for user '. $db['user'] .': '. $error. '</div>');
	    } 
    	
    	print ('<div class="alert alert-danger">Cannot install sql SCHEMA file: '. $error. '</div>');
    	return false;
	}
	    
    /* return true, if some errors occured script already died! */
    sleep(1);
   	updateLogTable ('Database installed successfully!', "version ".VERSION.".".REVISION." installed", 1);
   	return true;
}

?>