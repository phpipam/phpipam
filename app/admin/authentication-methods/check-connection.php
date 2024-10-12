<?php

/**
 *	Check connection
 *
 */


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# feth settings
$auth_settings = $Admin->fetch_object ("usersAuthMethod", "id", $POST->id);
if($auth_settings===false)	{ $Result->show("danger", _("Invalid ID"), true, true); }
//set params
$parameters = db_json_decode($auth_settings->params);

# AD?
if($auth_settings->type=="AD" || $auth_settings->type=="LDAP" || $auth_settings->type=="NetIQ") {
	# adLDAP function
	include (dirname(__FILE__) . "/../../../functions/adLDAP/src/adLDAP.php");
	# set controllers
	$controllers = pf_explode(";", str_replace(" ", "", $parameters->domain_controllers));

	//open connection
	try {
		if($server->type == "NetIQ") { $params->account_suffix = ""; }
		//set options
		$options = array(
				'base_dn'            =>$parameters->base_dn,
				'account_suffix'     =>$parameters->account_suffix,
				'domain_controllers' =>$controllers,
				'use_ssl'            =>$parameters->use_ssl,
				'use_tls'            =>$parameters->use_tls,
				'ad_port'            =>$parameters->ad_port
				);
		$adldap = new adLDAP($options);
		//LDAP?
		if($auth_settings->type=="LDAP") $adldap->setUseOpenLDAP(true);

	} catch (adLDAPException $e) {
		//catch AD error
		$Result->show("danger", $e, true, true);
	}
	//result
	foreach($controllers as $c) {
		$fp = @fsockopen($c, $parameters->ad_port, $errno, $errstr, 3);
		if($fp===false)	{
			$Result->show("danger",  "$c: $errstr ($errno)", false, true);
		} else {
			$Result->show("success", "$c: "._('AD network connection ok')."!", false, true);
		}
	}
}
else {
	$Result->show("danger", _("Check not implemented"), true, true);
}