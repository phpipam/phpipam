<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require_once( dirname(__FILE__) . "/../../../functions/adLDAP/src/adLDAP.php");

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# fetch server
$server = $Admin->fetch_object("usersAuthMethod", "id", $POST->server);
$server!==false ? : $Result->show("danger", _("Invalid server ID"), true);

//parse parameters
$params = new Params( db_json_decode($server->params) );

//no login parameters
if(is_blank($params->adminUsername) || is_blank($params->adminPassword))	{ $Result->show("danger", _("Missing credentials"), true); }
//at least 2 chars
if(strlen($POST->dname)<2) 													{ $Result->show("danger", _('Please enter at least 2 characters'), true); }


//open connection
try {
	if($server->type == "NetIQ") { $params->account_suffix = ""; }
	//set options
	$options = array(
			'base_dn'=>$params->base_dn,
			'account_suffix'=>$params->account_suffix,
			'domain_controllers'=>pf_explode(";", str_replace(" ", "", $params->domain_controllers)),
			'ad_port'=>$params->ad_port
			);

	// Set security
	if($server->type == "LDAP") {
		$options['use_ssl'] = $params->ldap_security=="ssl" ? true : false;
		$options['use_tls'] = $params->ldap_security=="tls" ? true : false;
	} else {
		$options['use_ssl'] = $params->use_ssl ? true : false;
		$options['use_tls'] = $params->use_tls ? true : false;
	}

	//AD
	$adldap = new adLDAP($options);

	// set OpenLDAP flag
	if($server->type == "LDAP") { $adldap->setUseOpenLDAP(true); }

	//try to login with higher credentials for search
	$authUser = $adldap->authenticate($params->adminUsername, $params->adminPassword);
	if (!$authUser) {
		$Result->show("danger", _("Invalid credentials")."<br>".$adldap->getLastError(), true);
	}

	//search for domain user!
	$esc_dname = ldap_escape($POST->dname, '', LDAP_ESCAPE_FILTER);
	$userinfo = $adldap->user()->info("*$esc_dname*", array("*"), false, $server->type);

	//echo $adldap->getLastError();
}
catch (adLDAPException $e) {
	$Result->show("danger", $e->getMessage(), true);
}


//check for found
if(!isset($userinfo['count'])) {
	print "<div class='alert alert-info'>";
	print _('No users found')."!<hr>";
	print _('Possible reasons').":";
	print "<ul>";
	print "<li>"._('Username not existing')."</li>";
	print "<li>"._('Invalid baseDN setting for AD')."</li>";
	print "<li>"._('AD account does not have enough privileges for search')."</li>";
	print "</div>";
} else {
	print _(" Following users were found").": ($userinfo[count]):<hr>";

	print "<table class='table table-striped'>";

	unset($userinfo['count']);
	if(sizeof(@$userinfo)>0 && isset($userinfo)) {
		// loop
		foreach($userinfo as $u) {
			print "<tr>";
			print "	<td>".escape_input($u['displayname'][0])."</td>";
			print "	<td>".escape_input($u['samaccountname'][0])."</td>";
			print "	<td>".escape_input($u['mail'][0])."</td>";
			//actions
			print " <td style='width:10px;'>";
			print "		<a href='' class='btn btn-sm btn-default btn-success userselect' data-uname='".escape_input($u['displayname'][0])."' data-username='".escape_input($u['samaccountname'][0])."' data-email='".escape_input($u['mail'][0])."' data-server='".escape_input($POST->server)."' data-server-type='".$server->type."'>"._('Select')."</a>";
			print "	</td>";
			print "</tr>";
		}
	}
	print "</table>";
}
