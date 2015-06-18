<?php

/**
 * Admin functions
 *
 */




/* @user functions ---------------- */


/**
 * Verify Input on add
 */
function verifyUserModInput ($userModDetails)
{
    # real name must be entered
    if (!$userModDetails['real_name']) 																			{ $errors[] = _('Real name field is mandatory!'); }
    # Both passwords must be same
    if ($userModDetails['password1orig'] != $userModDetails['password2orig']) 									{ $errors[] = _("Passwords do not match!"); }
    # pass must be at least 8 chars long for non-domain users
    if($userModDetails['domainUser'] != 1 ) {
    	if ((strlen($userModDetails['password1orig']) < 8 ) && (strlen($userModDetails['password1orig']) != 0)) { $errors[] = _("Password must be at least 8 characters long!"); }
    	else if (($userModDetails['action'] == "add") && (strlen($userModDetails['password1orig']) < 8 )) 		{ $errors[] = _("Password must be at least 8 characters long!"); }
    }
    # email format must be valid
    if (!checkEmail($userModDetails['email'])) 																	{ $errors[] = _("Invalid email address!"); }
    # username must not already exist (if action is add)
    if ($userModDetails['action'] == "add") {
        global $database;
        $query    = 'select * from users where `username` = "'. $userModDetails['username'] .'";'; 	# set query and fetch results

        /* execute */
        try { $details = $database->getArray( $query ); }
        catch (Exception $e) {
        	$error =  $e->getMessage();
        	die("<div class='alert alert-danger'>"._('Error').": $error</div>");
        }

        # user already exists
        if (sizeof($details) != 0) 																				{ $errors[] = _("User")." ".$userModDetails['username']." "._("already exists!"); }
    }
    # return errors
    return($errors);
}


/**
 * Delete user by ID
 */
function deleteUserById($id, $name = "")
{
    global $database;

    $query    = 'delete from `users` where `id` = "'. $id .'";';						# set query, open db connection and fetch results */

	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	# ok
	if(!isset($error)) {
        updateLogTable ('User '. $name .' deleted ok', 'User '. $name .' deleted ok', 1);	# write success log
        return true;
	}
	# problem
	else {
		print "<div class='alert alert-danger'>"._('Cannot delete user')."!<br><strong>"._('Error')."</strong>: $error</div>";
		updateLogTable ('Cannot delete user '. $name, 'Cannot delete user '. $name , 2);	# write error log
		return false;
	}
}


/**
 * Update user by ID - if id is empty add new user!
 */
function updateUserById ($userModDetails) {

    global $database;

    # replace special chars
    $userModDetails['groups'] = mysqli_real_escape_string($database, $userModDetails['groups']);

    # set query - add or edit user
    if (empty($userModDetails['userId'])) {

         # custom fields
        $myFields = getCustomFields('users');
        $myFieldsInsert['query']  = '';
        $myFieldsInsert['values'] = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				# empty?
				if(strlen($userModDetailsip[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", NULL";
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $userModDetails[$myField['name']] . "'";
				}
			}
		}

		# reformat passChanged
		if($userModDetails['domainUser']=="1")			{ $userModDetails['passChange'] = "No"; }	//never for domain users
		elseif(@$userModDetails['passChange']=="On")	{ $userModDetails['passChange'] = "Yes"; }	//yes if requested
		else											{ $userModDetails['passChange'] = "No"; }	//no change needed

        $query  = "insert into users ";
        $query .= "(`username`, `password`, `role`, `real_name`, `email`, `domainUser`,`mailNotify`,`mailChangelog`,`groups`,`lang`,`passChange` $myFieldsInsert[query]) values ";
        $query .= "('$userModDetails[username]', '$userModDetails[password1]', '$userModDetails[role]', '$userModDetails[real_name]', '$userModDetails[email]', '$userModDetails[domainUser]', '$userModDetails[mailNotify]','$userModDetails[mailChangelog]','$userModDetails[groups]','$userModDetails[lang]','$userModDetails[passChange]' $myFieldsInsert[values]);";
    }
    else {

        # custom fields
        $myFields = getCustomFields('users');
        $myFieldsInsert['query']  = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				if(strlen($userModDetails[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = NULL ';
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = \''.$userModDetails[$myField['name']].'\' ';
				}
			}
		}

        $query  = "update users set ";
        $query .= "`username` = '$userModDetails[username]', ";
        if (strlen($userModDetails['password1']) != 0) {
        $query .= "`password` = '$userModDetails[password1]', ";
        }
        $query .= "`role`     = '$userModDetails[role]', `real_name`= '$userModDetails[real_name]', `email` = '$userModDetails[email]', `domainUser`= '$userModDetails[domainUser]',`mailNotify`= '$userModDetails[mailNotify]',`mailChangelog`= '$userModDetails[mailChangelog]', `lang`= '$userModDetails[lang]', `groups`='".$userModDetails['groups']."' ";
    	$query .= $myFieldsInsert['query'];
        $query .= "where `id` = '$userModDetails[userId]';";
    }

	$log = prepareLogFromArray ($userModDetails);										# prepare log

	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	# ok
	if(!isset($error)) {
        updateLogTable ('User '. $userModDetails['username'] .' updated ok', $log, 1);	# write success log
        return true;
	}
	# problem
	else {
		print "<div class='alert alert-danger'>"._("Cannot $userModDetails[action] user")."!<br><strong>"._('Error')."</strong>: $error</div>";
		updateLogTable ('Cannot modify user '. $userModDetails['username'], $log, 2);	# write error log
		return false;
	}
}


/**
 * User self-update
 */
function selfUpdateUser ($userModDetails)
{
    global $database;

    /* set query */
    $query  = "update users set ";
    if(strlen($userModDetails['password1']) != 0) {
    $query .= "`password` = '$userModDetails[password1]',";
    }
    $query .= "`real_name`= '$userModDetails[real_name]', `mailNotify`='$userModDetails[mailNotify]', `mailChangelog`='$userModDetails[mailChangelog]', `email` = '$userModDetails[email]', ";
    $query .= "`lang`= '$userModDetails[lang]' ";
    $query .= "where `id` = '$userModDetails[userId]';";

    /* set log file */
    $log = prepareLogFromArray ($userModDetails);													# prepare log


	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	# ok
	if(!isset($error)) {
        updateLogTable ('User '. $userModDetails['real_name'] . ' selfupdate ok', $log, 1);			# write success log
        return true;
	}
	# problem
	else {
		print "<div class='alert alert-danger'>"._('Cannot update user')."!<br><strong>"._('Error')."</strong>: $error</div>";
		updateLogTable ('User '. $userModDetails['real_name'] . ' selfupdate failed', $log,  2);	# write error log
		return false;
	}
}


/**
 * User set dash widgets
 */
function setUserDashWidgets ($userId, $widgets)
{
    global $database;

    /* set query */
    $query  = "update users set `widgets`= '$widgets' where `id` = '$userId';";


	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

	# ok
	if(!isset($error)) {
        return true;
	}
	# problem
	else {
		print "<div class='alert alert-danger'>"._('Cannot update user')."!<br><strong>"._('Error')."</strong>: $error</div>";
		return false;
	}
}



/**
 * Modify lang
 */
function modifyLang ($lang)
{
    global $database;

    /* set query based on action */
    if($lang['action'] == "add")		{ $query = "insert into `lang` (`l_code`,`l_name`) values ('$lang[l_code]','$lang[l_name]');"; }
    elseif($lang['action'] == "edit")	{ $query = "update `lang` set `l_code`='$lang[l_code]',`l_name`='$lang[l_name]' where `l_id`='$lang[l_id]'; "; }
    elseif($lang['action'] == "delete")	{ $query = "delete from `lang` where `l_id`='$lang[l_id]'; "; }
    else								{ return 'false'; }

    /* execute */
    try { $details = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return true;
}


/**
 * Modify widget
 */
function modifyWidget ($w)
{
    global $database;

    /* set query based on action */
    if($w['action'] == "add")			{ $query = "insert into `widgets` (`wtitle`,`wdescription`,`wfile`,`whref`,`wadminonly`,`wactive`,`wsize`) values ('$w[wtitle]','$w[wdescription]','$w[wfile]','$w[whref]','$w[wadminonly]','$w[wactive]','$w[wsize]');"; }
    elseif($w['action'] == "edit")		{ $query = "update `widgets` set `wtitle`='$w[wtitle]',`wdescription`='$w[wdescription]',`wfile`='$w[wfile]',`wadminonly`='$w[wadminonly]',`wactive`='$w[wactive]',`whref`='$w[whref]',`wsize`='$w[wsize]' where `wid`='$w[wid]'; "; }
    elseif($w['action'] == "delete")	{ $query = "delete from `widgets` where `wid`='$w[wid]'; "; }
    else								{ return 'false'; }

    /* execute */
    try { $details = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return true;
}


/**
 *	Update user password on first login
 */
function update_user_password ($id, $password)
{
	global $database;

	# query
	$query = "update `users` set `password`='$password', `passChange`='No' where `id` = $id;";

    /* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return true;
}










/* @group functions ---------------- */


/**
 *	get all groups
 */
function getAllGroups()
{
    global $database;

	/* execute query */
	$query = "select * from `userGroups` order by `g_name` asc;";

  	/* get groups */
    try { $groups = $database->getArray( $query ); }
    catch (Exception $e) {
     	$error =  $e->getMessage();
        die("<div class='alert alert-danger'>"._('Error').": $error</div>");
    }

   	/* return false if none, else list */
	if(sizeof($groups) == 0) { return false; }
	else					 { return $groups; }
}


/**
 *	get all groups - array order by key
 */
function rekeyGroups($groups)
{
	foreach($groups as $k=>$g) {
		$tkey = $g['g_id'];

		$out[$tkey]['g_id']   = $g['g_id'];
		$out[$tkey]['g_name'] = $g['g_name'];
		$out[$tkey]['g_desc'] = $g['g_desc'];
	}

	return $out;
}


/**
 *	Group details by ID
 */
function getGroupById($id)
{
	# check if already in cache
	if($vtmp = checkCache("group", $id)) {
		return $vtmp;
	}
	# query
	else {
	    global $database;

		/* execute query */
		$query = "select * from `userGroups` where `g_id`= '$id';";

	   	/* get group */
	    try { $group = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        die("<div class='alert alert-danger'>"._('Error').": $error</div>");
	    }

	   	/* return false if none, else list */
		if(sizeof($group) == 0) { return false; }
		else					{ writeCache("group", $id, $group[0]); return $group[0]; }
	}
}


/**
 * Parse all user groups
 */
function parseUserGroups($groups)
{
	if(sizeof($groups)>0) {
    	foreach($groups as $g) {
    		$tmp = getGroupById($g);
    		$out[$tmp['g_id']] = $tmp;
    	}
    }
    /* return array of groups */
    return $out;
}


/**
 * Parse all user groups - get only Id's
 */
function parseUserGroupsIds($groups)
{
	if(sizeof($groups) >0) {
	    foreach($groups as $g) {
    		$tmp = getGroupById($g);
    		$out[$tmp['g_id']] = $tmp['g_id'];
    	}
    }
    /* return array of groups */
    return $out;
}



/**
 *	Get users in group
 */
function getUsersInGroup($gid)
{
	# get all users
	$users = getAllUsers();

	# check if $gid in array
	foreach($users as $u) {
		$g = json_decode($u['groups'], true);
		$g = parseUserGroups($g);

		if(sizeof($g)>0) {
			foreach($g as $gr) {
				if(in_array($gid, $gr)) {
					$out[] = $u['id'];
				}
			}
		}
	}
	# return
	return $out;
}


/**
 *	Get users not in group
 */
function getUsersNotInGroup($gid)
{
	# get all users
	$users = getAllUsers();

	# check if $gid in array
	foreach($users as $u) {
		if($u['role'] != "Administrator") {
			$g = json_decode($u['groups'], true);
			if(!@in_array($gid, $g)) { $out[] = $u['id']; }
		}
	}
	# return
	return $out;
}


/**
 *	Function that returns all sections with selected group partitions
 */
function getSectionPermissionsByGroup ($gid, $name = true)
{
	# get all users
	$sec = fetchSections();

	# check if $gid in array
	foreach($sec as $s) {
		$p = json_decode($s['permissions'], true);
		if(sizeof($p)>0) {
			if($name) {
				if(array_key_exists($gid, $p)) { $out[$s['name']] = $p[$gid]; }
			}
			else {
				if(array_key_exists($gid, $p)) { $out[$s['id']] = $p[$gid]; }
			}
		}
		# no permissions
		else {
			$out[$s['name']] = 0;
		}
	}
	# return
	return $out;
}



/**
 *	Modify group
 */
function modifyGroup($g)
{
    global $database;

    # set query
    if($g['action'] == "add") 			{ $query = "insert into `userGroups` (`g_name`,`g_desc`) values ('$g[g_name]','$g[g_desc]'); "; }
    else if($g['action'] == "edit")		{ $query = "update `userGroups` set `g_name`='$g[g_name]', `g_desc`='$g[g_desc]' where `g_id` = '$g[g_id]';"; }
    else if($g['action'] == "delete")	{ $query = "delete from `userGroups` where `g_id` = '$g[g_id]';"; }
    else								{ return false; }

	# execute
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { $error =  $e->getMessage(); }

    # set log file
    $log = prepareLogFromArray ($g);													# prepare log

	# ok
	if(!isset($error)) {
        updateLogTable ("Group $g[action] success", $log, 0);	# write success log
        return true;
	}
	# problem
	else {
		print "<div class='alert alert-danger'>"._("Cannot $userModDetails[action] user")."!<br><strong>"._('Error')."</strong>: $error</div>";
		updateLogTable ("Group $g[action] error", $log, 2);	# write error log
		return false;
	}

}


/**
 *	Delete all users from group
 */
function deleteUsersFromGroup($gid)
{
	# get all users
	$users = getAllUsers();

	# check if $gid in array
	foreach($users as $u) {
		$g = json_decode($u['groups'], true);
		$go = $g;
		$g = parseUserGroups($g);

		if(sizeof($g)>0) {
			foreach($g as $gr) {
				if(in_array($gid, $gr)) {
					unset($go[$gid]);
					$ng = json_encode($go);
					updateUserGroups($u['id'],$ng);
				}
			}
		}
	}
	# return
	return $out;

}


/**
 *	Delete all users from group
 */
function deleteGroupFromSections($gid)
{
	# get all users
	$sections = fetchSections();

	# check if $gid in array
	foreach($sections as $s) {
		$g = json_decode($s['permissions'], true);

		if(sizeof($g)>0) {
			if(array_key_exists($gid, $g)) {
				unset($g[$gid]);
				$ng = json_encode($g);
				updateSectionGroups($s['id'],$ng);
			}
		}
	}
	# return
	return $out;

}



/**
 *	Add user to group
 */
function addUserToGroup($gid, $uid)
{
	# get old groups
	$user = getUserDetailsById($uid);

	# append new group
	$g = json_decode($user['groups'], true);
	$g[$gid] = $gid;
	$g = json_encode($g);

	# update
	if(!updateUserGroups($uid, $g)) { return false; }
	else							{ return true; }
}


/**
 *	Remove user from group
 */
function removeUserFromGroup($gid, $uid)
{
	# get old groups
	$user = getUserDetailsById($uid);

	# append new group
	$g = json_decode($user['groups'], true);
	unset($g[$gid]);
	$g = json_encode($g);

	# update
	if(!updateUserGroups($uid, $g)) { return false; }
	else							{ return true; }
}


/**
 *	Update users's group
 */
function updateUserGroups($uid, $groups)
{
    global $database;

    # replace special chars
    $groups = mysqli_real_escape_string($database, $groups);

    # set query
    $query = "update `users` set `groups` = '$groups' where `id` = $uid; ";

	# update
    try { $database->executeQuery($query); }
    catch (Exception $e) {
    	print "<div class='alert alert-danger'>"._('Error').": $e</div>";
    	return false;
    }

    # ok
    return true;
}


/**
 *	Update section permissions
 */
function updateSectionGroups($sid, $groups)
{
    global $database;

    # replace special chars
   	$groups = mysqli_real_escape_string($database, $groups);

    # set query
    $query = "update `sections` set `permissions` = '$groups' where `id` = $sid; ";

	# update
    try { $database->executeQuery($query); }
    catch (Exception $e) {
    	print "<div class='alert alert-danger'>"._('Error').": $e</div>";
    	return false;
    }

    # ok
    return true;
}










/* @subnet functions ---------------- */


/**
 * Add new subnet
 */
function modifySubnetDetails ($subnetDetails, $lastId = false, $api = false)
{
    global $database;

    /* escape vars to prevent SQL injection */
	$subnetDetails = filter_user_input ($subnetDetails, true, true);

	/* trim user input */
	$subnetDetails = trim_user_input ($subnetDetails);

    # set modify subnet details query
    $query = setModifySubnetDetailsQuery ($subnetDetails, $api);

	$log = prepareLogFromArray ($subnetDetails);																				# prepare log

    /* save old if delete */
    if($subnetDetails['action']=="delete")		{ $dold = getSubnetDetailsById ($subnetDetails['subnetId']); }
    elseif($subnetDetails['action']=="edit")	{ $old  = getSubnetDetailsById ($subnetDetails['subnetId']); }

    # execute query
    try { $updateId=$database->executeMultipleQuerries($query, true); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        updateLogTable ('Subnet ('. $subnetDetails['description'] .') '. $subnetDetails['action'] .' failed', $log, 2);	# write error log
        print "<div class='alert alert-danger'>$error</div>";
        //save changelog
		writeChangelog('subnet', $ip['action'], 'error', $old, $new);
        return false;
    }

    /* for changelog */
	if($subnetDetails['action']=="add") {
		$subnetDetails['subnetId'] = $updateId;
		writeChangelog('subnet', $subnetDetails['action'], 'success', array(), $subnetDetails);
	} elseif ($subnetDetails['action']=="delete") {
		$dold['subnetId'] = $dold['id'];
		writeChangelog('subnet', $subnetDetails['action'], 'success', $dold, array());
	} else {
		writeChangelog('subnet', $subnetDetails['action'], 'success', $old, $subnetDetails);
	}

    // success
    if($_POST['isFolder']==false)	{
    updateLogTable ('Subnet '.$subnetDetails['subnet'].' ('. $subnetDetails['description'] .') '. $subnetDetails['action'] .' ok', $log, 1);		# write success log
    } else {
    updateLogTable ('Folder '.$subnetDetails['subnet'].' ('. $subnetDetails['description'] .') '. $subnetDetails['action'] .' ok', $log, 1);		# write success log
    }
    // result
    if(!$lastId) { return true; }
    else		 { return $updateId; }
}


/**
 * Add new subnet - set query
 */
function setModifySubnetDetailsQuery ($subnetDetails, $api)
{
    # add new subnet
    if ($subnetDetails['action'] == "add")
    {
    	# api?
    	if($api) {
	        $query  = 'insert into subnets '. "\n";
	        $query .= '(`subnet`, `mask`, `sectionId`, `description`, `vlanId`, `vrfId`, `masterSubnetId`, `allowRequests`, `showName`, `permissions`, `discoverSubnet`, `pingSubnet`) ' . "\n";
	        $query .= 'values (' . "\n";
	        $query .= ' "'. $subnetDetails['subnet'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['mask'] 			 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['sectionId'] 	 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['description']    .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['vlanId'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['vrfId'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['masterSubnetId'] .'", ' . "\n";
	        $query .= ''. isCheckbox($subnetDetails['allowRequests']) .','."\n";
	        $query .= ''. isCheckbox($subnetDetails['showName']) .','."\n";
	        $query .= ' "'. $subnetDetails['permissions'] .'", '."\n";
	        $query .= ''. isCheckbox($subnetDetails['discoverSubnet']) .','."\n";
	        $query .= ''. isCheckbox($subnetDetails['pingSubnet']) .''."\n";
	        $query .= ' );';
    	} else {
	        # remove netmask and calculate decimal values!
	        $subnetDetails['subnet_temp'] = explode("/", $subnetDetails['subnet']);
	        $subnetDetails['subnet']      = Transform2decimal ($subnetDetails['subnet_temp'][0]);
	        $subnetDetails['mask']        = $subnetDetails['subnet_temp'][1];

	        # custom fields
	        $myFields = getCustomFields('subnets');
	        $myFieldsInsert['query']  = '';
	        $myFieldsInsert['values'] = '';

	        if(sizeof($myFields) > 0) {
				/* set inserts for custom */
				foreach($myFields as $myField) {
					# empty?
					if(strlen($subnetDetails[$myField['name']])==0) {
						$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
						$myFieldsInsert['values'] .= ", NULL";
					} else {
						$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
						$myFieldsInsert['values'] .= ", '". $subnetDetails[$myField['name']] . "'";
					}
				}
			}

	        $query  = 'insert into subnets '. "\n";
	        # is folder?
	        if($subnetDetails['isFolder']) {
	        $query .= '(`isFolder`,`subnet`, `mask`, `sectionId`, `description`, `vlanId`, `vrfId`, `masterSubnetId`, `allowRequests`, `showName`, `permissions`, `discoverSubnet`, `pingSubnet` '.$myFieldsInsert['query'].') ' . "\n";
	        $query .= 'values (' . "\n";
	        $query .= '1, ' . "\n";
			}
			else {
	        $query .= '(`subnet`, `mask`, `sectionId`, `description`, `vlanId`, `vrfId`, `masterSubnetId`, `allowRequests`, `showName`, `permissions`, `discoverSubnet`, `pingSubnet` '.$myFieldsInsert['query'].') ' . "\n";
	        $query .= 'values (' . "\n";
	        }
	        $query .= ' "'. $subnetDetails['subnet'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['mask'] 			 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['sectionId'] 	 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['description']    .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['vlanId'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['vrfId'] 		 .'", ' . "\n";
	        $query .= ' "'. $subnetDetails['masterSubnetId'] .'", ' . "\n";
	        $query .= ''. isCheckbox($subnetDetails['allowRequests']) .','."\n";
	        $query .= ''. isCheckbox($subnetDetails['showName']) .','."\n";
	        $query .= ' "'. $subnetDetails['permissions'] .'", '."\n";
	        $query .= ''. isCheckbox($subnetDetails['discoverSubnet']) .','."\n";
	        $query .= ''. isCheckbox($subnetDetails['pingSubnet']) .''."\n";
	        $query .= $myFieldsInsert['values'];
	        $query .= ' );';
    	}
    }
    # Delete subnet
    else if ($subnetDetails['action'] == "delete")
    {
    	/* get ALL slave subnets, then remove all subnets and IP addresses */
    	global $removeSlaves;
    	getAllSlaves ($subnetDetails['subnetId']);
    	$removeSlaves = array_unique($removeSlaves);

    	$query = "";
    	foreach($removeSlaves as $slave) {
	    	$query .= 'delete from `subnets` where `id` = "'. $slave .'"; '."\n";
	    	$query .= 'delete from `ipaddresses` where `subnetId` = "'. $slave .'"; '."\n";
    	}
    }
    # Edit subnet
    else if ($subnetDetails['action'] == "edit")
    {

        # custom fields
        $myFields = getCustomFields('subnets');
        $myFieldsInsert['query']  = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				if(strlen($subnetDetails[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = NULL ';
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = "'.$subnetDetails[$myField['name']].'" ';
				}
			}
		}

        $query  = 'update subnets set '. "\n";
        $query .= '`description` 	= "'. $subnetDetails['description'] .'", '. "\n";
        if($subnetDetails['sectionId'] != $subnetDetails['sectionIdNew']) {
        $query .= '`sectionId`      = "'. $subnetDetails['sectionIdNew'] 	.'", '. "\n";
        }
        $query .= '`vlanId`        	= "'. $subnetDetails['vlanId'] 			.'", '. "\n";
        $query .= '`vrfId`        	= "'. $subnetDetails['vrfId'] 			.'", '. "\n";
        $query .= '`masterSubnetId` = "'. $subnetDetails['masterSubnetId'] 	.'", '. "\n";
        $query .= '`allowRequests`  = "'. isCheckbox($subnetDetails['allowRequests']) 	.'", '. "\n";
        $query .= '`showName`   	= "'. isCheckbox($subnetDetails['showName']) 		.'", '. "\n";
        $query .= '`discoverSubnet` = "'. isCheckbox($subnetDetails['discoverSubnet'])  .'", '. "\n";
        $query .= '`pingSubnet`   	= "'. isCheckbox($subnetDetails['pingSubnet']) 		.'" '. "\n";
        $query .= $myFieldsInsert['query'];
        $query .= 'where id      	= "'. $subnetDetails['subnetId'] .'"; '."\n";

        # if section changes
        if($subnetDetails['sectionId'] != $subnetDetails['sectionIdNew']) {
	        # add querry to change slaves!
	        global $removeSlaves;
	        getAllSlaves ($subnetDetails['subnetId']);
	        $removeSlaves = array_unique($removeSlaves);

	        foreach($removeSlaves as $slave) {
    			if($subnetDetails['subnetId'] != $slave) {
	    			$query .= 'update `subnets` set `sectionId` = "'. $subnetDetails['sectionIdNew'] .'" where `id` = "'.$slave.'"; '."\n";
	    		}
	    	}
        }

        # if vrf changes
        if($subnetDetails['vrfId'] != $subnetDetails['vrfIdOld']) {
	        # add querry to change vrfId!
	        global $removeSlaves;
	        getAllSlaves ($subnetDetails['subnetId']);
	        $removeSlaves = array_unique($removeSlaves);

	        foreach($removeSlaves as $slave) {
	    		$query .= 'update `subnets` set `vrfId` = "'. $subnetDetails['vrfId'] .'" where `id` = "'.$slave.'"; '."\n";
	    	}
        }
    }
    # Something is not right!
    else {

    }
    # return query
    return $query;
}


/**
 * delete subnet - only single subnet, no child/slave hosts and IP addresses are removed!!!! Beware !!!
 */
function deleteSubnet ($subnetId)
{
    global $database;

    # set modify subnet details query
    $query = "delete from `subnets` where `id` = '$subnetId';";

    # execute query
    if (!$database->executeQuery($query)) {
        updateLogTable ('Subnet delete from split failed', "id:$subnetId", 2);	# write error log
        return false;
    }
    else {
        updateLogTable ('Subnet deleted from split ok', "id: $subnetId", 0);		# write success log
        return true;
    }
}


/**
 * Resize subnet - change mask
 */
function modifySubnetMask ($subnetId, $mask)
{
    global $database;

    # set modify subnet details query
    $query = "update `subnets` set `mask` = '$mask' where `id` = '$subnetId';";

	$log = "subnetId: $subnetId\n New mask: $mask";																				# prepare log

    # execute query
    if (!$database->executeQuery($query)) {
        updateLogTable ('Subnet resize failed', $log, 2);	# write error log
        return false;
    }
    else {
        updateLogTable ('Subnet resized ok', $log, 1);		# write success log

		/* changelog */
		writeChangelog('subnet', 'resize', 'success', array(), array("subnetId"=>$subnetId, "mask"=>$mask));

        return true;
    }
}


/**
 * truncate subnet
 */
function truncateSubnet($subnetId)
{
    global $database;

    /* first update request */
    $query    = 'delete from `ipaddresses` where `subnetId` = '. $subnetId .';';

	/* execute */
    try { $database->executeQuery($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger">'.$error.'</div>');
    }

    /* changelog */
	writeChangelog('subnet', 'truncate', 'success', array(), array("Truncate"=>'Subnet truncated', "subnetId"=>$subnetId));

	/* return true if locked */
	return true;
}


/**
 * Print subnets structure
 */
function printAdminSubnets( $subnets, $actions = true, $vrf = "0" )
{
		$html = array();

		$rootId = 0;									# root is 0

		if(sizeof($subnets) > 0) {
		foreach ( $subnets as $item ) {
			$item = (array) $item;
			$children[$item['masterSubnetId']][] = $item;
		}
		}

		/* get custom fields */
		$custom = getCustomFields('subnets');

		global $settings;
		/* set hidden fields */
		$ffields = json_decode($settings['hiddenCustomFields'], true);
		if(is_array($ffields['subnets']))	{ $ffields = $ffields['subnets']; }
		else								{ $ffields = array(); }

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# display selected subnet as opened
		if(isset($_GET['subnetId'])) {
			if(!is_numeric($_GET['subnetId']))	{ die('<div class="alert alert-danger">'._("Invalid ID").'</div>'); }
			$allParents = getAllParents ($_GET['subnetId']);
		}
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
				$html[] = "<tr>";
				# folder
				if($option['value']['isFolder']==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'>$description</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-folder-open'></i> $description</td>";
				}
				else {
				if($count==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i> $description</td>";
				}
				else {
					# last?
					if(!empty( $children[$option['value']['id']])) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-folder-open-o'></i> $description</td>";
					}
					else {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-angle-right'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".transform2long($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-angle-right'></i> $description</td>";
					}
				}
				}
				$html[] = "	<td class='hidden-xs hidden-sm'>$vlan</td>";
				#vrf
				if($vrf == "1") {
				$html[] = "	<td class='hidden-xs hidden-sm'>$vrfText</td>";
				}
				$html[] = "	<td class='hidden-xs hidden-sm hidden-md'>$requests</td>";
				$html[] = "	<td class='hidden-xs hidden-sm hidden-md'>$pCheck</td>";
				# custom
				if(sizeof($custom)>0) {
					foreach($custom as $field) {
						if(!in_array($field['name'], $ffields)) {
				    		$html[] =  "	<td class='hidden-xs hidden-sm'>".$option['value'][$field['name']]."</td>";
						}
			    	}
				}
				# actions
				if($actions) {
				$html[] = "	<td class='actions' style='padding:0px;'>";
				$html[] = "	<div class='btn-group btn-group-xs'>";
				if($option['value']['isFolder']==1) {
				$html[] = "		<button class='btn btn-sm btn-default add_folder'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] = "		<button class='btn btn-sm btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] = "		<button class='btn btn-sm btn-default add_folder'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
				} else {
				$html[] = "		<button class='btn btn-sm btn-default editSubnet'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] = "		<button class='btn btn-sm btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] = "		<button class='btn btn-sm btn-default editSubnet'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
				}
				$html[] = "	</div>";
				$html[] = "	</td>";
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


/**
 *	Update subnet permissions
 */
function updateSubnetPermissions ($subnet)
{
    global $database;

    # replace special chars
    $subnet['permissions'] = mysqli_real_escape_string($database, $subnet['permissions']);

    # set querries for subnet and each slave
    foreach($subnet['slaves'] as $slave) {
    	$query .= "update `subnets` set `permissions` = '$subnet[permissions]' where `id` = $slave;";

    	writeChangelog('subnet', 'perm_change', 'success', array(), array("permissions_change"=>"$subnet[permissions]", "subnetId"=>$slave));
    }

	# execute
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	print('<div class="alert alert-danger">'._('Error').': '.$error.'</div>');
    	return false;
    }

	/* return true if passed */
	return true;
}











/* @section functions ---------------- */


/**
 * Update section
 */
function UpdateSection ($update, $api = false)
{
    global $database;

     # replace special chars for permissions
    $update['permissions'] = mysqli_real_escape_string($database, $update['permissions']);
    $update['description'] = mysqli_real_escape_string($database, $update['description']);
    $update['name'] 	   = mysqli_real_escape_string($database, $update['name']);

    if (!$api && !$update['name']) 	{ die('<div class="alert alert-danger">'._('Name is mandatory').'!</div>'); }	# section name is mandatory

    $query = setUpdateSectionQuery ($update);										# set update section query

	$log = prepareLogFromArray ($update);											# prepare log

	/* save old if delete */
    if($update['action']=="delete")		{ $dold = getSectionDetailsById ($update['id']); }
    elseif($update['action']=="edit")	{ $old  = getSectionDetailsById ($update['id']); }


    # delete and edit requires multiquery
    if ( ( $update['action'] == "delete") || ( $update['action'] == "edit") )
    {
		# execute
		try { $result = $database->executeMultipleQuerries($query, true); }
		catch (Exception $e) {
    		$error =  $e->getMessage();
            updateLogTable ('Section ' . $update['action'] .' failed ('. $update['name']. ') - '.$error, $log, 2);	# write error log
            if(!$api) print ('<div class="alert alert-danger">'.("Cannot $update[action] all entries").' - '.$error.'!</div>');
			return false;
    	}
    	# success
        updateLogTable ('Section '. $update['name'] . ' ' . $update['action'] .' ok', $log, 1);			# write success log

        /* for changelog */
        if ($update['action']=="delete") {
			$dold['id'] = $update['id'];
			writeChangelog('section', $update['action'], 'success', $dold, array());
		} else {
			writeChangelog('section', $update['action'], 'success', $old, $update);
		}

        return true;
    }
    # add is single querry
    else
    {
		# execute
		try { $result = $database->executeQuery($query, true); }
		catch (Exception $e) {
    		$error =  $e->getMessage();
            updateLogTable ('Adding section '. $update['name'] .'failed - '.$error, $log, 2);							# write error log
            if(!$api)  die('<div class="alert alert-danger">'.('Cannot update database').'!<br>'.$error.'</div>');
            return false;
		}
		# success
        updateLogTable ('Section '. $update['name'] .' added succesfully', $log, 1);					# write success log

        /* for changelog */
		$update['id'] = $result;
		writeChangelog('section', $update['action'], 'success', array(), $update);

        return true;
    }
}


/**
 * Set Query for update section
 */
function setUpdateSectionQuery ($update)
{
	# add section
    if ($update['action'] == "add" || $update['action'] == "create")
    {
        $query = 'Insert into sections (`name`,`description`,`permissions`,`strictMode`,`subnetOrdering`,`showVLAN`,`showVRF`, `masterSection`) values ("'.$update['name'].'", "'.$update['description'].'", "'.$update['permissions'].'", "'.isCheckbox($update['strictMode']).'", "'.$update['subnetOrdering'].'", "'.isCheckbox($update['showVLAN']).'", "'.isCheckbox($update['showVRF']).'", "'.$update['masterSection'].'");';
    }
    # edit section
    else if ($update['action'] == "edit" || $update['action'] == "update")
    {
        $section_old = getSectionDetailsById ( $update['id'] );												# Get old section name for update
        # Update section
        $query   = "update `sections` set `name` = '$update[name]', `description` = '$update[description]', `permissions` = '$update[permissions]', `strictMode`='".isCheckbox($update['strictMode'])."', `subnetOrdering`='$update[subnetOrdering]', `showVLAN`='".isCheckbox($update['showVLAN'])."', `showVRF`='".isCheckbox($update['showVRF'])."', `masterSection`='$update[masterSection]' where `id` = '$update[id]';";

        # delegate permissions if set
        if($update['delegate'] == 1) {
	        $query .= "update `subnets` set `permissions` = '$update[permissions]' where `sectionId` = '$update[id]';";
        }
    }
	# delete section
	else if( $update['action'] == "delete" )
	{
        /* we must delete many entries - section, all belonging subnets and ip addresses */
        $sectionId = $update['id'];

        # delete sections query
		$query  = "delete from `sections` where `id` = '$sectionId';"."\n";
		# delete belonging subnets
		$query .= "delete from `subnets` where `sectionId` = '$sectionId';"."\n";
		# delete IP addresses query
		$subnets = fetchSubnets ( $sectionId );

		if (sizeof($subnets) != 0) {
            foreach ($subnets as $subnet) {
            $query .= "delete from `ipaddresses` where `subnetId` = '$subnet[id]';"."\n";
            }
        }

        # if it has subsections delete all subsections and subnets/ip addresses
        if(sizeof($subsections = getAllSubSections($sectionId))>0) {
	    	foreach($subsections as $ss) {
		    	$query .= "delete from `sections` where `id` = '$ss[id]';"."\n";
		    	$query .= "delete from `subnets` where `sectionId` = '$ss[id]';"."\n";
		    	$ssubnets = fetchSubnets ( $ss['id'] );
				if (sizeof($ssubnets) != 0) {
		            foreach ($ssubnets as $subnet) {
		            $query .= "delete from `ipaddresses` where `subnetId` = '$subnet[id]';"."\n";
		            }
		        }
	    	}
        }
    }

    /* return query */
    return $query;
}


/**
 * Update section ordering
 */
function UpdateSectionOrder ($order)
{
    global $database;

	// set querries for each section
	$query = "";
	foreach($order as $key=>$o) {
		$query .= "update `sections` set `order` = $o where `id` = $key; \n";
	}
	//log
	$log = prepareLogFromArray ($order);
	//execute multiple queries
	try { $result = $database->executeMultipleQuerries($query); }
	catch (Exception $e) {
		$error =  $e->getMessage();
        updateLogTable ('Section reordering failed ('. $update['name']. ') - '.$error, $log, 2);	# write error log
        print ('<div class="alert alert-danger">'._("Cannot reorder sections").' - '.$error.'!</div>');
		return false;
	}
	# success
    updateLogTable ('Section reordering ok', $log, 1);			# write success log
    return true;
}


/**
 * Parse section permissions
 */
function parseSectionPermissions($permissions)
{
	# save to array
	$permissions = json_decode($permissions, true);

	if(sizeof($permissions)>0) {
    	foreach($permissions as $key=>$p) {
    		$tmp = getGroupById($key);
    		$out[$tmp['g_id']] = $p;
    	}
    }
    /* return array of groups */
    return $out;
}









/* @device functions ---------------- */


/**
 * Update device details
 */
function updateDeviceDetails($device)
{
    global $database;

    /* set querry based on action */
    if($device['action'] == "add") {

        # custom fields
        $myFields = getCustomFields('devices');
        $myFieldsInsert['query']  = '';
        $myFieldsInsert['values'] = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				# empty?
				if(strlen($device[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", NULL";
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $device[$myField['name']] . "'";
				}
			}
		}

    	$query  = 'insert into `devices` '. "\n";
    	$query .= '(`hostname`,`ip_addr`, `type`, `vendor`,`model`,`version`,`description`,`sections` '.$myFieldsInsert['query'].') values '. "\n";
   		$query .= '("'. $device['hostname'] .'", "'. $device['ip_addr'] .'", "'.$device['type'].'", "'. $device['vendor'] .'", '. "\n";
   		$query .= ' "'. $device['model'] .'", "'. $device['version'] .'", "'. $device['description'] .'", "'. $device['sections'] .'" '. $myFieldsInsert['values'] .');'. "\n";
    }
    else if($device['action'] == "edit") {

       # custom fields
        $myFields = getCustomFields('devices');
        $myFieldsInsert['query']  = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				if(strlen($device[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = NULL ';
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = "'.$device[$myField['name']].'" ';
				}
			}
		}

    	$query  = 'update `devices` set '. "\n";
    	$query .= '`hostname` = "'. $device['hostname'] .'", `ip_addr` = "'. $device['ip_addr'] .'", `type` = "'. $device['type'] .'", `vendor` = "'. $device['vendor'] .'", '. "\n";
    	$query .= '`model` = "'. $device['model'] .'", `version` = "'. $device['version'] .'", `description` = "'. $device['description'] .'", '. "\n";
    	$query .= '`sections` = "'. $device['sections'] .'" '. "\n";
    	$query .= $myFieldsInsert['query'];
    	$query .= 'where `id` = "'. $device['switchId'] .'";'. "\n";
    }
    else if($device['action'] == "delete") {
    	$query  = 'delete from `devices` where id = "'. $device['switchId'] .'";'. "\n";
    }

    /* prepare log */
    $log = prepareLogFromArray ($device);

    /* execute */
    try { $res = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
       	updateLogTable ('Device ' . $device['action'] .' failed ('. $device['hostname'] . ')'.$error, $log, 2);
    	return false;
    }

    /* success */
    updateLogTable ('Device ' . $device['action'] .' success ('. $device['hostname'] . ')', $log, 0);
    return true;
}


/**
 * Update device details
 */
function updateDevicetypeDetails($device)
{
    global $database;

    /* set querry based on action */
    if($device['action'] == "add") 			{ $query  = "insert into `deviceTypes` (`tname`,`tdescription`) values ('$device[tname]', '$device[tdescription]');"; }
    else if($device['action'] == "edit") 	{ $query  = "update `deviceTypes` set `tname` = '$device[tname]', `tdescription` = '$device[tdescription]' where `tid` = $device[tid]"; }
    else if($device['action'] == "delete") 	{ $query  = "delete from `deviceTypes` where `tid` = $device[tid];"; }

    /* prepare log */
    $log = prepareLogFromArray ($device);

    /* execute */
    try { $res = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print $error;
       	updateLogTable ("Device $device[action] failed ($device[tname]) $error", $log, 2);
    	return false;
    }

    /* if delete we need to null type in devices! */
    if($device['action'] == "delete") {
	    $query = "update `devices` set `type` = NULL where `type` = $device[tid];";
	    try { $res = $database->executeQuery( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	    	print "<div class='alert alert-danger'>$e</div>";;
	    }
    }

    /* success */
    updateLogTable ("Device $device[action] success ($device[tname])", $log, 0);
    return true;
}


/**
 * reformat sections for devices!
 *		sections are separated with ;
 */
function reformatDeviceSections ($sections) {

	if(sizeof($sections != 0)) {

		//first reformat
		$temp = explode(";", $sections);

		foreach($temp as $section) {
			//we have sectionId, so get its name
			$out = getSectionDetailsById($section);
			$out = $out['name'];

			//format output
			$result[$out] = $section;
		}
	}

	//return result if it exists
	if($result) {
		return $result;
	}
	else {
		return false;
	}
}


/**
 * get switch type
 */
function getDeviceTypes()
{
    global $database;

	$query = "select * from `deviceTypes`;";

    /* execute */
    try { $devices = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    /* rekey */
    foreach($devices as $d) {
	    $devices2[$d['tid']] = _("$d[tname]");
    }

    /* return unique devices */
    return $devices2;
}


/**
 * Transfor switch type
 */
function TransformDeviceType($type)
{
    global $database;

	$query = "select * from `deviceTypes` where `tid` = $type;";

    /* execute */
    try { $devices = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    return $devices[0]['tname'];
}












/* @adLDAP functions ---------------- */

/**
 * Get Domain settings for authentication
 */
if(!function_exists('getADSettings')) {
function getADSettings()
{
    global $database;

    /* Check connection */
    if ($database->connect_error) {
    	die('Connect Error (' . $database->connect_errno . '): '. $database->connect_error);
	}

	/* first update request */
	$query    = 'select * from `settingsDomain` limit 1;';
	$settings = $database->getArray($query);

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
}


/**
 * Get Domain settings for authentication
 */
function updateADsettings($ad)
{
    global $database;

    /* Check connection */
    if ($database->connect_error) {
    	die('Connect Error (' . $database->connect_errno . '): '. $database->connect_error);
	}

	/* if OpenLDAP then append BaseDN to account suffix */
	if($ad['type'] == "2") { $ad['account_suffix'] = ",".$ad['base_dn']; }

    /* set query and update */
    $query    = 'update `settingsDomain` set '. "\n";
    $query   .= '`domain_controllers` = "'. $ad['domain_controllers'] .'", `base_dn` = "'. $ad['base_dn'] .'", `account_suffix` = "'. $ad['account_suffix'] .'", '. "\n";
    $query   .= '`use_ssl` = "'. $ad['use_ssl'] .'", `use_tls` = "'. $ad['use_tls'] .'", `ad_port` = "'. $ad['ad_port'] .'", `adminUsername`="'.$ad['adminUsername'].'", `adminPassword`="'.$ad['adminPassword'].'";'. "\n";

    /* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

    # success
    return true;
}








/* @VRF functions ---------------- */


/**
 * Update VRF details
 */
function updateVRFDetails($vrf)
{
    global $database;

    /* set querry based on action */
    if($vrf['action'] == "add") {
    	$query  = 'insert into `vrf` '. "\n";
    	$query .= '(`name`,`rd`,`description`) values '. "\n";
   		$query .= '("'. $vrf['name'] .'", "'. $vrf['rd'] .'", "'. $vrf['description'] .'" ); '. "\n";
    }
    else if($vrf['action'] == "edit") {
    	$query  = 'update `vrf` set '. "\n";
    	$query .= '`name` = "'. $vrf['name'] .'", `rd` = "'. $vrf['rd'] .'", `description` = "'. $vrf['description'] .'" '. "\n";
    	$query .= 'where `vrfId` = "'. $vrf['vrfId'] .'";'. "\n";
    }
    else if($vrf['action'] == "delete") {
    	$query  = 'delete from `vrf` where `vrfId` = "'. $vrf['vrfId'] .'";'. "\n";
    }

    /* execute */
    try { $res = $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
   		updateLogTable ('VRF ' . $vrf['action'] .' failed ('. $vrf['name'] . ')'.$error, $log, 2);
    	return false;
    }

    # if delete also NULL all subnets!
    if($vrf['action'] == 'delete') {
	    $query = "update `subnets` set `vrfId` = NULL where `vrfId` = '$vrf[vrfId]';";
	    /* execute */
	    try { $database->executeQuery( $query ); }
	    catch (Exception $e) {
    		$error =  $e->getMessage();
    		print ('<div class="alert alert-danger alert-absolute">'.$error.'</div>');
    	}
    }

    /* prepare log */
    $log = prepareLogFromArray ($vrf);

    /* return details */
    updateLogTable ('VRF ' . $vrf['action'] .' success ('. $vrf['name'] . ')', $log, 0);
    return true;
}










/* @VLAN functions ---------------- */


/**
 * Update VLAN details
 */
function updateVLANDetails($vlan, $lastId = false)
{
    global $database;

    /* set querry based on action */
    if($vlan['action'] == "add") {

        # custom fields
        $myFields = getCustomFields('vlans');
        $myFieldsInsert['query']  = '';
        $myFieldsInsert['values'] = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				# empty?
				if(strlen($vlan[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", NULL";
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $vlan[$myField['name']] . "'";
				}
			}
		}

    	$query  = 'insert into `vlans` '. "\n";
    	$query .= '(`name`,`number`,`description` '.$myFieldsInsert['query'].') values '. "\n";
   		$query .= '("'. $vlan['name'] .'", "'. $vlan['number'] .'", "'. $vlan['description'] .'" '. $myFieldsInsert['values'] .' ); '. "\n";

    }
    else if($vlan['action'] == "edit") {

        # custom fields
        $myFields = getCustomFields('vlans');
        $myFieldsInsert['query']  = '';

        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				if(strlen($vlan[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = NULL ';
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = "'.$vlan[$myField['name']].'" ';
				}
			}
		}

    	$query  = 'update `vlans` set '. "\n";
    	$query .= '`name` = "'. $vlan['name'] .'", `number` = "'. $vlan['number'] .'", `description` = "'. $vlan['description'] .'" '. "\n";
    	$query .= $myFieldsInsert['query'];
    	$query .= 'where `vlanId` = "'. $vlan['vlanId'] .'";'. "\n";
    }
    else if($vlan['action'] == "delete") {
    	$query  = 'delete from `vlans` where `vlanId` = "'. $vlan['vlanId'] .'";'. "\n";
    }

    /* execute */
    try { $res = $database->executeQuery( $query, true ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
   		updateLogTable ('VLAN ' . $vlan['action'] .' failed ('. $vlan['name'] . ')'.$error, $log, 2);
    	return false;
    }

    # if delete also NULL all subnets!
    if($vlan['action'] == 'delete') {
	    $query = "update `subnets` set `vlanId` = NULL where `vlanId` = '$vlan[vlanId]';";
	    /* execute */
	    try { $database->executeQuery( $query ); }
	    catch (Exception $e) {
    		$error =  $e->getMessage();
    		print ('<div class="alert alert-danger alert-absolute">'.$error.'</div>');
    	}
    }

    /* prepare log */
    $log = prepareLogFromArray ($vlan);

    /* return success */
    updateLogTable ('VLAN ' . $vlan['action'] .' success ('. $vlan['name'] . ')', $log, 0);

    /* response */
    if($lastId)	{ return $res; }
    else		{ return true; }
}










/* @other functions ---------------- */


/**
 * update site settings
 */
function updateSettings($settings)
{
    global $database;

    filter_user_input ($settings, true, true, false);

    /* first update request */
    $query    = 'update `settings` set ' . "\n";
    $query   .= '`siteTitle` 		  = "'. $settings['siteTitle'] .'", ' . "\n";
    $query   .= '`siteDomain` 		  = "'. $settings['siteDomain'] .'", ' . "\n";
    $query   .= '`siteURL` 			  = "'. $settings['siteURL'] .'", ' . "\n";
    $query   .= '`siteAdminName` 	  = "'. $settings['siteAdminName'] .'", ' . "\n";
    $query   .= '`siteAdminMail` 	  = "'. $settings['siteAdminMail'] .'", ' . "\n";
	$query   .= '`domainAuth` 		  = "'. isCheckbox($settings['domainAuth']) .'", ' . "\n";
	$query   .= '`enableIPrequests`   = "'. isCheckbox($settings['enableIPrequests']) .'", ' . "\n";
	$query   .= '`enableVRF`   		  = "'. isCheckbox($settings['enableVRF']) .'", ' . "\n";
	$query   .= '`donate`   		  = "'. isCheckbox($settings['donate']) .'", ' . "\n";
	$query   .= '`enableDNSresolving` = "'. isCheckbox($settings['enableDNSresolving']) .'", ' . "\n";
	$query   .= '`dhcpCompress` 	  = "'. isCheckbox($settings['dhcpCompress']) .'", ' . "\n";
    $query   .= '`printLimit` 	      = "'. $settings['printLimit'] .'", ' . "\n";
    $query   .= '`visualLimit` 	      = "'. $settings['visualLimit'] .'", ' . "\n";
    $query   .= '`vlanDuplicate` 	  = "'. isCheckbox($settings['vlanDuplicate']) .'", ' . "\n";
    $query   .= '`vlanMax` 	      	  = "'. $settings['vlanMax'] .'", ' . "\n";
    $query   .= '`api` 	  			  = "'. isCheckbox($settings['api']) .'", ' . "\n";
    $query   .= '`enableChangelog` 	  = "'. isCheckbox($settings['enableChangelog']) .'", ' . "\n";
    $query   .= '`subnetOrdering` 	  = "'. $settings['subnetOrdering'] .'", ' . "\n";
    $query   .= '`pingStatus` 	  	  = "'. $settings['pingStatus'] .'", ' . "\n";
    $query   .= '`scanPingPath` 	  = "'. $settings['scanPingPath'] .'", ' . "\n";
    $query   .= '`scanMaxThreads` 	  = "'. $settings['scanMaxThreads'] .'", ' . "\n";
    $query   .= '`prettyLinks` 	  	  = "'. $settings['prettyLinks'] .'", ' . "\n";
    $query   .= '`inactivityTimeout`  = "'. $settings['inactivityTimeout'] .'", ' . "\n";
    $query   .= '`hideFreeRange` 	  = "'. isCheckbox($settings['hideFreeRange']) .'", ' . "\n";
    $query   .= '`defaultLang` 	  	  = "'. $settings['defaultLang'] .'" ' . "\n";
	$query   .= 'where id = 1;' . "\n";

	/* set log file */
	foreach($settings as $key=>$setting) {
		$log .= " ". $key . ": " . $setting . "<br>";
	}

 	/* execute */
    try {
    	$database->executeQuery( $query );
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	print '<div class="alert alert-danger">'._('Update settings error').':<hr>'. $error .'</div>';
    	updateLogTable ('Failed to update settings', $log, 2);
    	return false;
	}

	if(!isset($e)) {
    	updateLogTable ('Settings updated', $log, 1);
        return true;
	}
}


/**
 *	post-install updates
 */
function postauth_update($adminpass, $siteTitle, $siteURL)
{
    global $database;

	$query  = "update `users` set `password`='$adminpass',`passChange`='No' where `username` = 'Admin';";		//to update admin pass
	$query .= "update `settings` set `siteTitle`='$siteTitle',`siteURL`='$siteURL';";

	/* execute */
    try {
    	$database->executeMultipleQuerries( $query );
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	updateLogTable ('Failed to update settings', $log, 2);
    	return false;
	}
	return true;
}


/**
 * update mail settings
 */
function updateMailSettings($settings)
{
    global $database;

    /* first update request */
    $query    = 'update `settingsMail` set ' . "\n";
    $query   .= '`mtype` 		  	= "'. $settings['mtype'] .'", ' . "\n";
    $query   .= '`mserver` 		  	= "'. $settings['mserver'] .'", ' . "\n";
    $query   .= '`mport` 		  	= "'. $settings['mport'] .'", ' . "\n";
    $query   .= '`mauth` 		  	= "'. $settings['mauth'] .'", ' . "\n";
    $query   .= '`msecure` 		  	= "'. $settings['msecure'] .'", ' . "\n";
    $query   .= '`muser` 		  	= "'. $settings['muser'] .'", ' . "\n";
    $query   .= '`mpass` 		  	= "'. $settings['mpass'] .'", ' . "\n";
    $query   .= '`mAdminName` 	  	= "'. $settings['mAdminName'] .'", ' . "\n";
    $query   .= '`mAdminMail` 	  	= "'. $settings['mAdminMail'] .'" ' . "\n";
	$query   .= 'where id = 1;' . "\n";

	/* set log file */
	foreach($settings as $key=>$setting) {
		$log .= " ". $key . ": " . $setting . "<br>";
	}

 	/* execute */
    try {
    	$database->executeQuery( $query );
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	print '<div class="alert alert-danger">'._('Update settings error').':<hr>'. $error .'</div>';
    	updateLogTable ('Failed to update settings', $log, 2);
    	return false;
	}

	if(!isset($e)) {
    	updateLogTable ('Settings updated', $log, 1);
        return true;
	}
}


/**
 *	Verify checkboxes for saving config
 */
function isCheckbox($checkbox)
{
	if($checkbox == "") { $chkbox = "0"; }
	else 				{ $chkbox = $checkbox; }

	/* return 0 if not checkbos and same result if checkbox */
	return $chkbox;
}


/**
 * Search and replace fields
 */
function searchAndReplace($query, $post)
{
    global $database;

    /* check how many records are in database */
    $query2 = 'select count(*) as count from `ipaddresses` where '. $post['field'] .' like "%'. $post['search'] .'%";';
    $count 	  = $database->getArray($query2);
    $count 	  = $count[0]['count'];

	/* execute */
    try {
    	$database->executeQuery( $query );
    }
    catch (Exception $e) {
    	$error =  $e->getMessage();
    	die('<div class="alert alert-danger alert-absolute">'._('Error').': '. $error .'</div>');
	}

	if(!isset($e)) {
		print '<div class="alert alert-success alert-absolute">'._('Replaced').' '. $count .' '._('items successfully').'!</div>';
	}
}


/**
 *	Write instructions
 */
function writeInstructions ($instructions)
{
    global $database;

	$instructions = $database->real_escape_string($instructions);	//this hides code

	# execute query
	$query 			= "update `instructions` set `instructions` = '". $instructions ."';";

  	/* update database */
   	if ( !$database->executeQuery($query) ) {
        updateLogTable ('Instructions update failed', $instructions, 2);
        return false;
    }
    else {
        updateLogTable ('Instructions update succeeded', $instructions, 1);
        return true;
    }
}


/**
 * CSV import IP address
 *
 *		provided input is CSV line!
 */
function importCSVline ($line, $subnetId)
{
    global $database;

    /* get subnet details by Id */
    $subnetDetails = getSubnetDetailsById ($subnetId);
    $subnet = Transform2long($subnetDetails['subnet']) . "/" . $subnetDetails['mask'];

    /* verify! */
    $err = VerifyIpAddress( $line[0],$subnet );
    if($err)									{ return _('Wrong IP address').' - '.$err.' - '.$line[0]; }

    /* check for duplicates */
    if (checkDuplicate ($line[0], $subnetId)) 	{ return _('IP address already exists').' - '.$line[0]; }

    /* get custom fields */
    $myFields = getCustomFields('ipaddresses');
    if(sizeof($myFields) > 0) {
    	$import['fieldName']  = "";
    	$import['fieldValue'] = "";
    	$m = 9;
    	foreach($myFields as $field) {
    		//escape chars
    		$line[$m] = mysqli_real_escape_string($database, $line[$m]);

	    	$import['fieldName']  .= ",`$field[name]`";
	    	$import['fieldValue'] .= ",'$line[$m]'";
	    	$m++;
    	}
    }

    /* escape chars */
    foreach($line as $k=>$l) {
	    $line[$k] = mysqli_real_escape_string($database, $l);
    }

	/* all ok, set query */
	$query  = "insert into ipaddresses ";
	$query .= "(`subnetId`, `ip_addr`, `state`, `description`, `dns_name`, `mac`, `owner`, `switch`, `port`, `note` $import[fieldName] ) ";
	$query .= "values ";
	$query .= "('$subnetId','".Transform2decimal( $line[0] )."', '$line[1]','$line[2]','$line[3]','$line[4]','$line[5]','$line[6]','$line[7]','$line[8]' $import[fieldValue]);";

/*
	print "<pre>";
	print_r($line);
	die('alert alert-danger');
*/

	/* set log details */
	$log = prepareLogFromArray ($line);

	/* execute */
    try {
    	$database->executeQuery( $query );
    }
    catch (Exception $e) {
    	$error = $e->getMessage();
	}

	if(!isset($e)) {
        updateLogTable ('CSV import of IP address '. $line[1] .' succeeded', $log, 0);
		return true;
	}
	else {
        updateLogTable ('CSV import of IP address '. $line[1] .' failed', $log, 2);
        return $error;
	}
}









/* @filter functions ---------------- */


/**
 * Get all fields in IP addresses
 */
function getIPaddrFields()
{
    global $database;

    /* first update request */
    $query    = 'describe `ipaddresses`;';

    /* execute */
    try { $fields = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	/* return Field values only */
	foreach($fields as $field) {
		$res[$field['Field']] = $field['Field'];
	}

	return $res;
}


/**
 * Get selected IP fields
 */
function getSelectedIPaddrFields()
{
	global $settings;
	# we only need it if it is not already set!
	if(!isset($settings)) {

	    global $database;

	    /* first update request */
	    $query    = 'select IPfilter from `settings`;';

	    /* execute */
	    try { $fields = $database->getArray( $query ); }
	    catch (Exception $e) {
	        $error =  $e->getMessage();
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }

		return $fields[0]['IPfilter'];
	}
	else {
		return $settings['IPfilter'];
	}

	return $settings['IPfilter'];
}


/**
 * Set selected IP fields
 */
function updateSelectedIPaddrFields($fields)
{
    global $database;

    /* first update request */
    $query    = 'update `settings` set `IPfilter` = "'. $fields .'";';

    # execute query
    if (!$database->executeQuery($query)) {
        updateLogTable ('Failed to change IP field filter', $fields,  2);
        return false;
    }
    else {
        updateLogTable ('IP field filter change success', $fields, 1);
        return true;
    }
}










/* @custom fields */


/**
 * Get all custom  fields
 */
function getCustomFields($table)
{
    global $database;

    /* first update request */
    $query    = "show full columns from `$table`;";

    /* execute */
    try { $fields = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	/* return Field values only */
	foreach($fields as $field) {
		$res[$field['Field']]['name'] 	 = $field['Field'];
		$res[$field['Field']]['type'] 	 = $field['Type'];
		$res[$field['Field']]['Comment'] = $field['Comment'];
		$res[$field['Field']]['Null'] 	 = $field['Null'];
		$res[$field['Field']]['Default'] = $field['Default'];
	}

	/* unset standard fields */
	if($table == "users") {
		unset($res['id'], $res['username'], $res['password'], $res['groups'], $res['role'], $res['real_name'], $res['email'], $res['domainUser'], $res['lang']);
		unset($res['editDate'],$res['widgets'],$res['favourite_subnets'],$res['mailNotify'],$res['mailChangelog'], $res['passChange']);
	}
	elseif($table == "devices") {
		unset($res['id'], $res['hostname'], $res['ip_addr'], $res['type'], $res['vendor'], $res['model'], $res['version'], $res['description'], $res['sections'], $res['editDate']);
	}
	elseif($table == "subnets") {
		unset($res['id'], $res['subnet'], $res['mask'], $res['sectionId'], $res['description'], $res['masterSubnetId']);
		unset($res['vrfId'], $res['allowRequests'], $res['adminLock'], $res['vlanId'], $res['showName'],$res['permissions'],$res['editDate']);
		unset($res['pingSubnet'], $res['isFolder'], $res['discoverSubnet']);
	}
	elseif($table == "ipaddresses") {
		unset($res['id'], $res['subnetId'], $res['ip_addr'], $res['description'], $res['dns_name'], $res['switch']);
		unset($res['port'], $res['mac'], $res['owner'], $res['state'], $res['note'], $res['lastSeen'], $res['excludePing'], $res['editDate']);
	}
	elseif($table == "vlans") {
		unset($res['vlanId'], $res['name'], $res['number'], $res['description'],$res['editDate']);
	}

	/* reset if empty */
	if(sizeof($res)==0) { $res = array(); }

	return $res;
}


/**
 * Get all custom fields in number array
 */
function getCustomFieldsNumArr($table)
{
 	$res = getCustomFields($table);

	/* reindex */
	foreach($res as $line) {
		$out[] = $line['name'];
	}

	return $out;
}


/**
 * Update custom field
 */
function updateCustomField($field)
{
    global $database;

    /* escape vars */
    # set override
    if($field['fieldType']!="set") {
    	$field = filter_user_input ($field, true, true);
    }

    /* set db type values */
    if($field['fieldType']=="bool" || $field['fieldType']=="text" || $field['fieldType']=="date" || $field['fieldType']=="datetime")
    																{ $field['ftype'] = "$field[fieldType]"; }
    else															{ $field['ftype'] = "$field[fieldType]($field[fieldSize])"; }

    //default null
    if(strlen($field['fieldDefault'])==0)	{ $field['fieldDefault'] = "NULL"; }
    else									{ $field['fieldDefault'] = "'$field[fieldDefault]'"; }

    //character?
    if($field['fieldType']=="varchar" || $field['fieldType']=="text" || $field['fieldType']=="set")	{ $charset = "CHARACTER SET utf8"; }
    else																							{ $charset = ""; }

    /* update request */
    if($field['action']=="delete") 								{ $query  = "ALTER TABLE `$field[table]` DROP `$field[name]`;"; }
    else if ($field['action']=="edit"&&@$field['NULL']=="NO") 	{ $query  = "ALTER TABLE `$field[table]` CHANGE COLUMN `$field[oldname]` `$field[name]` $field[ftype] $charset DEFAULT $field[fieldDefault] NOT NULL COMMENT '$field[Comment]';"; }
    else if ($field['action']=="edit") 							{ $query  = "ALTER TABLE `$field[table]` CHANGE COLUMN `$field[oldname]` `$field[name]` $field[ftype] $charset DEFAULT $field[fieldDefault] COMMENT '$field[Comment]';"; }
    else if ($field['action']=="add"&&@$field['NULL']=="NO") 	{ $query  = "ALTER TABLE `$field[table]` ADD COLUMN 	`$field[name]` 					$field[ftype] $charset DEFAULT $field[fieldDefault] NOT NULL COMMENT '$field[Comment]';"; }
    else if ($field['action']=="add")							{ $query  = "ALTER TABLE `$field[table]` ADD COLUMN 	`$field[name]` 					$field[ftype] $charset DEFAULT $field[fieldDefault] NULL COMMENT '$field[Comment]';"; }
    else {
	    return false;
    }

    /* prepare log */
    $log = prepareLogFromArray ($field);

    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        updateLogTable ('Custom Field ' . $field['action'] .' failed ('. $field['name'] . ')', $log, 2);
        return false;
    }

    updateLogTable ('Custom Field ' . $field['action'] .' success ('. $field['name'] . ')', $log, 0);
    return true;
}


/**
 * reorder custom VLAN field
 */
function reorderCustomField($table, $next, $current)
{
    global $database;

    /* get field details */
    $old = getFullFieldData($table, $current);

    /* update request */
    if($old['Null']=="NO")	{ $query  = 'ALTER TABLE `'.$table.'` MODIFY COLUMN `'. $current .'` '.$old['Type'].' NOT NULL COMMENT "'.$old['Comment'].'" AFTER `'. $next .'`;'; }
    else					{ $query  = 'ALTER TABLE `'.$table.'` MODIFY COLUMN `'. $current .'` '.$old['Type'].' DEFAULT NULL COMMENT "'.$old['Comment'].'" AFTER `'. $next .'`;'; }

    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        updateLogTable ('Custom Field reordering failed ('. $next .' was not put before '. $current .')', $log, 2);
        return false;
    }

	updateLogTable ('Custom Field reordering success ('. $next .' put before '. $current .')', $log, 0);
    return true;
}


/**
 *	update filter for custom fields
 */
function save_filtered_custom_fields($table, $filtered)
{
	# prepare
	if(is_null($filtered))	{ $out = null; }
	else					{ $out = $filtered; }

	# write
	return write_custom_filter($table,$out);
}


/**
 *	save filtered fields
 */
function write_custom_filter($table, $out)
{
	$settings = getAllSettings();

	if(strlen($settings['hiddenCustomFields'])>0)	{ $filterField = json_decode($settings['hiddenCustomFields'], true); }
	else											{ $filterField = array(); }

	# set
	if(is_null($out))	{ unset($filterField[$table]); }
	else				{ $filterField[$table]=$out; }

	# encode
	$filterField = json_encode($filterField);

	# write
	global $database;
	$query = "update `settings` set `hiddenCustomFields`='$filterField';";

    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }
	return true;
}










/* @api --------------------*/

/**
 * Get all API keys
 */
function getAPIkeys()
{
    global $database;

    # set query
    $query  = 'select * from `api`;';
    # get result
    try { $apis = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
         print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	return $apis;
}


/**
 * Get API key by name
 */
function getAPIkeyByName($app_id, $reformat = false)
{
    global $database;

    # set query
    $query  = "select * from `api` where `app_id` = '$app_id';";
    # get result
    try { $api = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
         print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }

	# reformat?
	if($reformat) {
		$out[$api[0]['app_id']] = $api[0]['app_code'];
		return $out;
	}
	else {
		return $api[0];
	}
}


/**
 * Get API key by id
 */
function getAPIkeyById($id)
{
    global $database;

    # set query
    $query  = "select * from `api` where `id` = $id;";
    # get result
    try { $api = $database->getArray( $query ); }
    catch (Exception $e) {
        $error =  $e->getMessage();
         print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }
	return $api[0];
}


/**
 *	Modify API details
 */
function modifyAPI($api)
{
    global $database;

    # set query based on action
    if($api['action']=="add")			{ $query = "insert into `api` (`app_id`,`app_code`,`app_permissions`, `app_comment`) values ('$api[app_id]','$api[app_code]','$api[app_permissions]', '$api[app_comment]');"; }
    elseif($api['action']=="edit")		{ $query = "update `api` set `app_id`='$api[app_id]',`app_code`='$api[app_code]',`app_permissions`='$api[app_permissions]', `app_comment`='$api[app_comment]' where `id`=$api[id] ; "; }
    elseif($api['action']=="delete")	{ $query = "delete from `api` where `id` = $api[id];"; }
    else 								{ return false; }

	$log = prepareLogFromArray ($api);												# prepare log

	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) {
    	$error =  $e->getMessage();
		updateLogTable ('API update failed - '.$error, $log, 2);	# write error log
		return false;
    }

	# success
    updateLogTable ('API updated ok', $log, 1);	# write success log
    return true;
}



/**
 *	Verify database
 */
function verifyDatabase()
{
	/* required tables */
	$reqTables = array("instructions", "ipaddresses", "logs", "requests", "sections", "settings", "settingsDomain", "subnets", "devices", "deviceTypes", "users", "vrf", "vlans", "widgets", "changelog", "userGroups", "lang", "api", "settingsMail");

	/* required fields for each table */
	$fields['instructions']   = array("instructions");
	$fields['ipaddresses'] 	  = array("subnetId", "ip_addr", "description", "dns_name", "mac", "owner", "switch", "port", "owner", "state", "note", "lastSeen", "excludePing");
	$fields['logs']			  = array("severity", "date", "username", "ipaddr", "command", "details");
	$fields['requests']		  = array("subnetId", "ip_addr", "description", "dns_name", "owner", "requester", "comment", "processed", "accepted", "adminComment");
	$fields['sections']		  = array("name", "description", "permissions", "strictMode", "subnetOrdering", "order", "showVLAN", "showVRF", "masterSection");
	$fields['settings']		  = array("siteTitle", "siteAdminName", "siteAdminMail", "siteDomain", "siteURL", "domainAuth", "enableIPrequests", "enableVRF", "enableDNSresolving", "version", "dbverified", "donate", "IPfilter", "printLimit", "visualLimit", "vlanDuplicate", "vlanMax", "subnetOrdering", "pingStatus", "defaultLang", "api", "editDate", "vcheckDate", "dhcpCompress", "enableChangelog", "scanPingPath", "scanMaxThreads", "prettyLinks", "hideFreeRange", "hiddenCustomFields", "inactivityTimeout");
	$fields['settingsDomain'] = array("account_suffix", "base_dn", "domain_controllers", "use_ssl", "use_tls", "ad_port", "adminUsername", "adminPassword");
	$fields['subnets'] 		  = array("subnet", "mask", "sectionId", "description", "masterSubnetId", "vrfId", "allowRequests", "vlanId", "showName", "permissions", "pingSubnet", "discoverSubnet", "isFolder");
	$fields['devices'] 	  	  = array("hostname", "ip_addr", "type", "vendor", "model", "version", "description", "sections");
	$fields['deviceTypes'] 	  = array("tid", "tname", "tdescription");
	$fields['users'] 	  	  = array("username", "password", "groups", "role", "real_name", "email", "domainUser", "lang", "widgets", "favourite_subnets", "mailNotify", "mailChangelog", "passChange");
	$fields['vrf'] 	  	  	  = array("vrfId","name", "rd", "description");
	$fields['vlans']   	  	  = array("vlanId", "name", "number", "description");
	$fields['userGroups']     = array("g_id", "g_name", "g_desc");
	$fields['lang']     	  = array("l_id", "l_code", "l_name");
	$fields['api']			  = array("app_id", "app_code", "app_permissions", "app_comment");
	$fields['changelog']	  = array("cid", "ctype", "coid", "cuser", "caction", "cresult", "cdate", "cdiff");
	$fields['widgets']		  = array("wid", "wtitle", "wdescription", "wfile", "wparams", "whref", "wsize", "wadminonly", "wactive");
	$fields['settingsMail']	  = array("id", "mtype", "mauth", "mserver", "mport", "muser", "mpass", "mAdminName", "mAdminMail", "msecure");

	/**
	 * check that each database exist - if it does check also fields
	 *		2 errors -> $tableError, $fieldError[table] = field
	 ****************************************************************/

	foreach($reqTables as $table) {

		//check if table exists
		if(!tableExists($table)) {
			$error['tableError'][] = $table;
		}
		//check for each field
		else {
			foreach($fields[$table] as $field) {
				//if it doesnt exist store error
				if(!fieldExists($table, $field)) {
					$error['fieldError'][$table] = $field;
				}
			}
		}
	}

	/* result */
	if(isset($error)) {
		return $error;
	} else {
		return array();
	}
}


/**
 *	Update verified flag
 */
function updateDBverify()
{
    global $database;

    # set query based on action
    $query = "update `settings` set `dbverified`='1'; ";
	/* execute */
    try { $database->executeQuery( $query ); }
    catch (Exception $e) { }
	# return
    return true;
}


/**
 *	get table fix
 */
function getTableFix($table)
{
	$res = fopen(dirname(__FILE__) . "/../db/SCHEMA.sql", "r");
	$file = fread($res, 100000);

	//go from delimiter on
	$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
	$file = trim(strstr($file, "# Dump of table", true));

	# check
	if(strpos($file, "DROP TABLE IF EXISTS `$table`;") > 0 )	return false;
	else														return $file;
}


/**
 *	get field fix
 */
function getFieldFix($table, $field)
{
	$res = fopen(dirname(__FILE__) . "/../db/SCHEMA.sql", "r");
	$file = fread($res, 100000);

	//go from delimiter on
	$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
	$file = trim(strstr($file, "# Dump of table", true));

	//get proper line
	$file = explode("\n", $file);
	foreach($file as $k=>$l) {
		if(strpos(trim($l), "$field`")==1) {
			//get previous
			$prev = trim($file[$k-1]);
			$prev = explode("`", $prev);
			$prev = "`$prev[1]`";

			$res = trim($l, ",");
			$res .= " after $prev;";

			return $res;
		}
	}

	return false;
}


/**
 *	Fix table
 */
function fixTable($table)
{
    global $database;

	//get fix
	$query = getTableFix($table);

	/* execute */
    try { $database->executeMultipleQuerries( $query ); }
    catch (Exception $e) {
	    die("<div class='alert alert-danger'>".$e->getMessage()."</div>");
    }
	# return
    return true;
}


/**
 *	Fix field
 */
function fixField($table, $field)
{
    global $database;

	//get fix
	$query  = "alter table `$table` add ";
	$query .= trim(getFieldFix($table, $field), ",");
	$query .= ";";

	/* execute */
    try { $database->executeMultipleQuerries( $query ); }
    catch (Exception $e) {
	    die("<div class='alert alert-danger'>".$e->getMessage()."</div>");
    }
	# return
    return true;
}




?>