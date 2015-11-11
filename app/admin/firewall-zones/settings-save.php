<?php

/**
 *	firewall zone settings-save.php
 *	modify firewall zone module settings like zone indicator, max. chars, ...
 ********************************************************************************/


# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize objects
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validations

# check for the maximum length of the zone name, it has to be between 3 and 31. also be sure that this value is decimal.
if (($_POST['zoneLength'] < 1) || ($_POST['zoneLength'] > 31)) {
	$Result->show("danger", _("Invalid zone name length parameter. A valid valid value is between 1 and 31."), true);
}

# validate the IPv4 type alias.
if (!preg_match('/^[a-zA-Z0-9\-\_.]+$/i', $_POST['ipType'][0])) {
	$Result->show("danger", _("Invalid IPv4 address type alias. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the IPv6 type alias.
if (!preg_match('/^[a-zA-Z0-9\-\_.]+$/i', $_POST['ipType'][1])) {
	$Result->show("danger", _("Invalid IPv4 address type alias. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the separator character.
if (!preg_match('/^[\-\_.]+$/i', $_POST['separator'])) {
	$Result->show("danger", _("Invalid separator. Only &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the indicator for own firewall zones.
if (!preg_match('/^[a-zA-Z0-9\-\_.]+$/i', $_POST['indicator'][0])) {
	$Result->show("danger", _("Invalid zone indicator. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the IPv6 type alias.
if (!preg_match('/^[a-zA-Z0-9\-\_.]+$/i', $_POST['indicator'][1])) {
	$Result->show("danger", _("Invalid zone indicator. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the zoneGenerator value.
if (!preg_match('/^[0-3]$/i', $_POST['zoneGenerator'])) {
	$Result->show("danger", _("Invalid zone generator method. Do not manipulate the POST values!"), true);
}

# validate the hidden values (zoneGeneratorType).
if ($_POST['zoneGeneratorType'][0] != 'decimal') {
	$Result->show("danger", _("Invalid zone generator types [decimal]. Do not manipulate the POST values!"), true);
}
if ($_POST['zoneGeneratorType'][1] != 'hex') {
	$Result->show("danger", _("Invalid zone generator types [hex]. Do not manipulate the POST values!"), true);
}
if ($_POST['zoneGeneratorType'][2] != 'text') {
	$Result->show("danger", _("Invalid zone generator types [text]. Do not manipulate the POST values!"), true);
}

# validate the padding checkbox value
if ($_POST['padding']) {
	if ($_POST['padding'] != 'on' && $_POST['padding'] != 'off') {
		$Result->show("danger", _("Invalid padding value. Use the checkbox to set the padding value to on or off."), true);
	}
}

# validate the strictMode checkbox value
if ($_POST['strictMode']) {
	if ($_POST['strictMode'] != 'on' && $_POST['strictMode'] != 'off') {
		$Result->show("danger", _("Invalid padding value. Use the checkbox to set the padding value to on or off."), true);
	}
}

# validate the autogen checkbox value
if ($_POST['autogen']) {
	if ($_POST['autogen'] != 'on' && $_POST['autogen'] != 'off') {
		$Result->show("danger", _("Invalid autogen value. Use the checkbox to set the autogen value to on or off."), true);
	}
}

# validate the pattern array
if ($_POST['pattern']) {
	foreach ($_POST['pattern'] as $pattern) {
		if (	$pattern != 'patternIndicator' 
			&& 	$pattern != 'patternZoneName' 
			&& 	$pattern != 'patternIPType' 
			&& 	$pattern != 'patternHost' 
			&& 	$pattern != 'patternFQDN' 
			&& 	$pattern != 'patternSeparator' 
			&& 	$pattern != '') {
			$Result->show("danger", _("Invalid pattern value."), true);
		}
	}
} else {
	$Result->show("danger", _("Please select at least one item to generate a valid name pattern."), true);
}

# validate device type ID.
if (!preg_match('/^[0-9]+$/i', $_POST['deviceType'])) {
	$Result->show("danger", _("Invalid device type."), true);
}

# formulate json
$values = new StdClass ();

# set the array values
$values->zoneLength = $_POST['zoneLength'];
$values->ipType = $_POST['ipType'];
$values->separator = $_POST['separator'];
$values->indicator = $_POST['indicator'];
$values->zoneGenerator = $_POST['zoneGenerator'];
$values->zoneGeneratorType = $_POST['zoneGeneratorType'];
$values->strictMode = $_POST['strictMode'];
$values->deviceType = $_POST['deviceType'];
$values->pattern = $_POST['pattern'];
$values->autogen = $_POST['autogen'];

# be sure that padding, strictMode and autogen will be set even if they are not delivered by $_POST.
if($_POST['padding'] != 'on')	{ $values->padding = 'off'; }
else 							{ $values->padding = $_POST['padding']; }

if($_POST['strictMode'] != 'on'){ $values->strictMode = 'off'; }
else 							{ $values->strictMode = $_POST['strictMode']; }

if($_POST['autogen'] != 'on')	{ $values->autogen = 'off'; }
else 							{ $values->autogen = $_POST['autogen']; }

# prepare the database update and encode the array with JSON_FORCE_OBJECT to keep the ids.
$values = array('id' => 1,
				'firewallZoneSettings' => json_encode($values,JSON_FORCE_OBJECT)
				);

# update the settings, alert or reply the success message.
if(!$Admin->object_modify("settings", "edit", "id", $values)) 	{ $Result->show("danger",  _("Cannot update settings"), true); }
else 															{ $Result->show("success", _("Settings updated successfully"), true);  }

?>