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
$User->Crypto->csrf_cookie ("validate", "device_snmp", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# get modified details
$device = $Admin->strip_input_tags($_POST);

# ID, port snd community must be numeric
if(!is_numeric($_POST['device_id']))			              { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_POST['snmp_version']))			              { $Result->show("danger", _("Invalid version"), true); }
if($_POST['snmp_version']!=0) {
if(!is_numeric($_POST['snmp_port']))			              { $Result->show("danger", _("Invalid port"), true); }
if(!is_numeric($_POST['snmp_timeout']))			              { $Result->show("danger", _("Invalid timeout"), true); }
}

# version can be 0, 1 or 2
if ($_POST['snmp_version']<0 || $_POST['snmp_version']>3)     { $Result->show("danger", _("Invalid version"), true); }

# validate device
$device = $Admin->fetch_object ("devices", "id", $_POST['device_id']);
if($device===false) { $Result->show("danger", _("Invalid device"), true); }

# validate device ip
if ($Admin->validate_ip($device->ip_addr)===false)            { $Result->show("danger", _("Invalid device IP address"), true); }

# set snmp queries
foreach($_POST as $key=>$line) {
	if (!is_blank(strstr($key,"query-"))) {
		$key2 = str_replace("query-", "", $key);
		$temp[] = $key2;
		unset($_POST[$key]);
	}
}
# glue sections together
$_POST['snmp_queries'] = !empty($temp) ? implode(";", $temp) : null;

# set update values
$values = array(
				"id"                      => $_POST['device_id'],
				"snmp_version"            => $_POST['snmp_version'],
				"snmp_community"          => $_POST['snmp_community'],
				"snmp_port"               => $_POST['snmp_port'],
				"snmp_timeout"            => $_POST['snmp_timeout'],
				"snmp_queries"            => $_POST['snmp_queries'],
				"snmp_v3_sec_level"       => $_POST['snmp_v3_sec_level'],
				"snmp_v3_auth_protocol"   => $_POST['snmp_v3_auth_protocol'],
				"snmp_v3_auth_pass" 	  => $_POST['snmp_v3_auth_pass'],
				"snmp_v3_priv_protocol"   => $_POST['snmp_v3_priv_protocol'],
				"snmp_v3_priv_pass"       => $_POST['snmp_v3_priv_pass'],
				"snmp_v3_ctx_name"        => $_POST['snmp_v3_ctx_name'],
				"snmp_v3_ctx_engine_id"   => $_POST['snmp_v3_ctx_engine_id']
				);

# update device
if(!$Admin->object_modify("devices", "edit", "id", $values))    { $Result->show("danger",  _("SNMP edit failed").'!', false); }
else														    { $Result->show("success", _("SNMP edit successful").'!', false); }
