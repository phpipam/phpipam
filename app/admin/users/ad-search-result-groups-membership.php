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

//parse parameters
$params = json_decode($server->params);

//no login parameters
if(strlen(@$params->adminUsername)==0 || strlen(@$params->adminPassword)==0)	{ $Result->show("danger", _("Missing credentials"), true); }

//open connection
try {
	if($server->type == "NetIQ") { $params->account_suffix = ""; }
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

	//try to login with higher credentials for search
	$authUser = $adldap->authenticate($params->adminUsername, $params->adminPassword);
	if ($authUser == false) {
		$Result->show("danger", _("Invalid credentials"), true);
	}

	// set OpenLDAP flag
	if($server->type == "LDAP") { $adldap->setUseOpenLDAP(true); }

	//fetch all groups
	$all_groups = $Admin->fetch_all_objects ("userGroups", "g_id");
	if($all_groups !== false) {
		foreach($all_groups as $k=>$g) {
			//members
			$domain_group_members = $adldap->group()->members($g->g_name);
			//false
			if($domain_group_members!==false) {
				foreach($domain_group_members as $m) {
					if($m==$_POST['username']) {
						$membership[] = $g->g_id;
					}
				}
			}
		}
	}

	# if something set print it
	if (isset($membership)) {
		print trim(implode(";", array_filter($membership)));
	}

}
catch (adLDAPException $e) {
	$Result->show("danger", $e->getMessage(), true);
}
?>