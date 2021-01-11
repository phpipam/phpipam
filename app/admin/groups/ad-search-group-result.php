<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . "/../../../functions/adLDAP/src/adLDAP.php");

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$AD_sync  	= new AD_user_sync ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# get server details and check
$server = $AD_sync->get_ad_servers ($_POST['server']);
!is_object($server)==0 ? : $Result->show("danger", _("Invalid server ID"), true);

# connect to server
$AD_sync->ad_server_connect ($server->id);

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "group");

//no login parameters
if(strlen(@$server->adminUsername)==0 || strlen(@$server->adminPassword)==0)	{ $Result->show("danger", _("Missing admin credentials"), true); }
//at least 2 chars
if(strlen($_POST['dfilter'])<2) 												{ $Result->show("danger", _('Please enter at least 2 characters'), true); }

// recheck server
$server = $AD_sync->get_ad_servers ($_POST['server']);
if($server->connection==false) {
	$Result->show("danger", "Error connectiong to AD server", true);
}
else {
	// authenticate
	$AD_sync->ad_user_admin_authenticate ();
	// search
	$found_ad_groups = $AD_sync->ad_group_search ($_POST['dfilter']);
}


//check for found
if(sizeof($found_ad_groups)==0) {
	print "<div class='alert alert-info'>";
	print _('No groups found')."!<hr>";
	print _('Possible reasons').":";
	print "<ul>";
	print "<li>"._('Invalid baseDN setting for ' . $server->type)."</li>";
	print "<li>"._($server->type . ' account does not have enough privileges for search')."</li>";
	print "</div>";
} else {
	print _(" Following groups were found").": (".sizeof($found_ad_groups)."):";

	print "<table class='table table-top table-td-top table-striped'>";

	// loop
 	foreach($found_ad_groups as $k=>$g) {
		print "<tr>";
		print "	<td>$k</td>";
		print "	<td>";
		print $g."<br>";
		// search members
		$groupMembers = $AD_sync->ad_group_users($k);
		unset($members);
		if($groupMembers!==false) {
			foreach($groupMembers as $m) {
				print " &middot; <span class='muted'>$m</span><br>";
				$members[] = $m;
			}
			if(isset($members))
			$members = implode(";", $members);
		}
		else {
			$members = "";
			print "<span class='muted'>"._("No members")."</span>";
		}
		print "</td>";
		//actions
		print " <td style='width:10px;'>";
		print "		<a href='' class='btn btn-sm btn-default btn-success groupselect' data-gname='$k' data-gdescription='$g' data-members='$members' data-gid='$k' data-g_domain='{$server->id}' data-csrf_cookie='$csrf'>"._('Add group')."</a>";
		print "	</td>";
		print "</tr>";

		print "<tr>";
		print "<td colspan='3' style='border-top:none !important'><div class='adgroup-$k'></div></td>";
		print "</tr>";

	}
	print "</table>";
}