<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');
require( dirname(__FILE__) . "/../../../functions/adLDAP/src/adLDAP.php");

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch server
$server = $Admin->fetch_object("usersAuthMethod", "id", $_POST['server']);
$server!==false ? : $Result->show("danger", _("Invalid server ID"), true);

# create csrf token
$csrf = $User->csrf_cookie ("create", "group");

//parse parameters
$params = json_decode($server->params);

if ($server->type == "LDAP") {

	// Just discovered that adLDAP flat out won't work for normal ldap groups. Stop LDAP here.
	$Result->show("danger", _("Only AD group search is supported right now. Sorry."), true);
	return;

}

//no login parameters
if(strlen(@$params->adminUsername)==0 || strlen(@$params->adminPassword)==0)	{ $Result->show("danger", _("Missing credentials"), true); }
//at least 2 chars
if(strlen($_POST['dfilter'])<2) 												{ $Result->show("danger", _('Please enter at least 2 characters'), true); }


//open connection
try {
	if($server->type == "NetIQ" || $server->type == "LDAP") { $params->account_suffix = ""; }
	//set options
	$options = array(
			'base_dn'=>$params->base_dn,
			'account_suffix'=>$params->account_suffix,
			'domain_controllers'=>explode(";",$params->domain_controllers),
			'use_ssl'=>$params->use_ssl,
			'use_tls'=>$params->use_tls,
			'ad_port'=>$params->ad_port
			);
	//AD
	$adldap = new adLDAP($options);

	// Use credentials if they've been provided
	if (isset($params->adminUsername) && isset($params->adminPassword)) {
		$authUser = $adldap->authenticate($params->adminUsername, $params->adminPassword);
		if ($authUser == false) {
			$Result->show("danger", _("Invalid credentials"), true);
		}
	}

	//search groups
	$groups = $adldap->group()->search(adLDAP::ADLDAP_SECURITY_GLOBAL_GROUP,true,"*$_POST[dfilter]*");

	//echo $adldap->getLastError();
}
catch (adLDAPException $e) {
	$Result->show("danger", $adldap->getLastError(), false);
	$Result->show("danger", $e->getMessage(), true);
}


//check for found
if(sizeof($groups)==0) {
	print "<div class='alert alert-info'>";
	print _('No groups found')."!<hr>";
	print _('Possible reasons').":";
	print "<ul>";
	print "<li>"._('Invalid baseDN setting for ' . $server->type)."</li>";
	print "<li>"._($server->type . ' account does not have enough privileges for search')."</li>";
	print "</div>";
} else {
	print _(" Following groups were found").": (".sizeof($groups)."):<hr>";

	print "<table class='table table-top table-td-top  table-striped'>";

	// loop
 	foreach($groups as $k=>$g) {
		print "<tr>";
		print "	<td>$k</td>";
		print "	<td>$g</td>";
		//actions
		print " <td style='width:10px;'>";
		print "		<a href='' class='btn btn-sm btn-default btn-success groupselect' data-gname='$k' data-gdescription='$g' data-members='$members' data-gid='$k' data-csrf_cookie='$csrf'>"._('Add group')."</a>";
		print "	</td>";
		print "</tr>";

		print "<tr>";
		print "	<td>"._("Members:")."</td>";
		print "<td colspan='2'>";
		print "	<div class='adgroup-$k'></div>";
		// search members
		$groupMembers = $adldap->group()->members($k);
		unset($members);
		if($groupMembers!==false) {
			foreach($groupMembers as $m) {
				print "<span class='muted'>$m</span><br>";
				$members[] = $m;
			}
			if(isset($members))
			$members = implode(";", $members);
		}
		else {
			$members = "";
			print "<span class='muted'>"._("No members")."</span>";
		}
		print "	</td>";
		print "	</tr>";

	}
	print "</table>";
}

?>