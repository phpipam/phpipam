<?php

/**
 * Edit snmp result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "device_snmp", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID, port snd community must be numeric
if(!is_numeric($POST->device_id))			              { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($POST->snmp_version))			              { $Result->show("danger", _("Invalid version"), true); }
if($POST->snmp_version!=0) {
if(!is_numeric($POST->snmp_port))			              { $Result->show("danger", _("Invalid port"), true); }
if(!is_numeric($POST->snmp_timeout))			              { $Result->show("danger", _("Invalid timeout"), true); }
}

# version can be 0, 1 or 2
if ($POST->snmp_version<0 || $POST->snmp_version>3)     { $Result->show("danger", _("Invalid version"), true); }

# validate device
$device = $Admin->fetch_object ("devices", "id", $POST->device_id);
if($device===false) { $Result->show("danger", _("Invalid device"), true); }

# validate device ip
if ($Admin->validate_ip($device->ip_addr)===false)            { $Result->show("danger", _("Invalid device IP address"), true); }

# set snmp queries
foreach($POST as $key=>$line) {
	if (!is_blank(strstr($key,"query-"))) {
		$key2 = str_replace("query-", "", $key);
		$temp[] = $key2;
		unset($POST[$key]);
	}
}
# glue sections together
$POST->snmp_queries = !empty($temp) ? implode(";", $temp) : null;

# set update values
$values = array(
				"id"                      => $POST->device_id,
				"snmp_version"            => $POST->snmp_version,
				"snmp_community"          => $POST->snmp_community,
				"snmp_port"               => $POST->snmp_port,
				"snmp_timeout"            => $POST->snmp_timeout,
				"snmp_queries"            => $POST->snmp_queries,
				"snmp_v3_sec_level"       => $POST->snmp_v3_sec_level,
				"snmp_v3_auth_protocol"   => $POST->snmp_v3_auth_protocol,
				"snmp_v3_auth_pass" 	  => $POST->snmp_v3_auth_pass,
				"snmp_v3_priv_protocol"   => $POST->snmp_v3_priv_protocol,
				"snmp_v3_priv_pass"       => $POST->snmp_v3_priv_pass,
				"snmp_v3_ctx_name"        => $POST->snmp_v3_ctx_name,
				"snmp_v3_ctx_engine_id"   => $POST->snmp_v3_ctx_engine_id
				);

# update device
if(!$Admin->object_modify("devices", "edit", "id", $values))    { $Result->show("danger",  _("SNMP edit failed").'!', false); }
else														    { $Result->show("success", _("SNMP edit successful").'!', false); }
