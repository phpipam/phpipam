<?php

/**
 *	firewall zone settings-save.php
 *	modify firewall zone module settings like zone indicator, max. chars, ...
 ********************************************************************************/


# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize objects
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validations

# check for the maximum length of the zone name, it has to be between 3 and 31. also be sure that this value is decimal.
if (($POST->zoneLength < 1) || ($POST->zoneLength > 31)) {
	$Result->show("danger", _("Invalid zone name length parameter. A valid valid value is between 1 and 31."), true);
}

$ipType = $POST->ipType;
# validate the IPv4 type alias.
if (!is_array($ipType) || sizeof($ipType) < 2 || !preg_match('/^[a-z0-9\-\_.]+$/i', $ipType[0])) {
	$Result->show("danger", _("Invalid IPv4 address type alias. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the IPv6 type alias.
if (!is_array($ipType) || sizeof($ipType) < 2 || !preg_match('/^[a-z0-9\-\_.]+$/i', $ipType[1])) {
	$Result->show("danger", _("Invalid IPv4 address type alias. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the separator character.
if (!preg_match('/^[\-\_.]+$/i', $POST->separator)) {
	$Result->show("danger", _("Invalid separator. Only &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

$indicator = $POST->indicator;
# validate the indicator for own firewall zones.
if (!is_array($indicator) || sizeof($indicator)<2 || !preg_match('/^[a-z0-9\-\_.]+$/i', $indicator[0])) {
	$Result->show("danger", _("Invalid zone indicator. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the IPv6 type alias.
if (!is_array($indicator) || sizeof($indicator)<2 || !preg_match('/^[a-z0-9\-\_.]+$/i', $indicator[1])) {
	$Result->show("danger", _("Invalid zone indicator. Only alphanumeric characters, &quot;-&quot;, &quot;_&quot; and &quot;.&quot; are allowed."), true);
}

# validate the zoneGenerator value.
if (!preg_match('/^[0-3]$/i', $POST->zoneGenerator)) {
	$Result->show("danger", _("Invalid zone generator method. Do not manipulate the POST values!"), true);
}

# validate the hidden values (zoneGeneratorType).
if (!is_array($POST->zoneGeneratorType) || $POST->zoneGeneratorType !== ['decimal', 'hex', 'text']) {
	$Result->show("danger", _("Invalid zone generator types. Do not manipulate the POST values!"), true);
}

# validate the padding checkbox value
if ($POST->padding) {
	if ($POST->padding != 'on' && $POST->padding != 'off') {
		$Result->show("danger", _("Invalid padding value. Use the checkbox to set the padding value to on or off."), true);
	}
}

# validate the strictMode checkbox value
if ($POST->strictMode) {
	if ($POST->strictMode != 'on' && $POST->strictMode != 'off') {
		$Result->show("danger", _("Invalid padding value. Use the checkbox to set the padding value to on or off."), true);
	}
}

# validate the autogen checkbox value
if ($POST->autogen) {
	if ($POST->autogen != 'on' && $POST->autogen != 'off') {
		$Result->show("danger", _("Invalid autogen value. Use the checkbox to set the autogen value to on or off."), true);
	}
}

# validate the pattern array
if ($POST->pattern) {
	foreach ($POST->pattern as $pattern) {
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
if (!preg_match('/^[0-9]+$/i', $POST->deviceType)) {
	$Result->show("danger", _("Invalid device type."), true);
}

# validate the hidden values (subnetPatternValues).
if (!is_array($POST->subnetPatternValues) || $POST->subnetPatternValues !== ['network', 'description']) {
	$Result->show("danger", _("Invalid subnet name types. Do not manipulate the POST values!"), true);
}

# validate the subnetPattern value.
if (!preg_match('/^[0-1]$/i', $POST->subnetPattern)) {
	$Result->show("danger", _("Invalid subnet name. Do not manipulate the POST values!"), true);
}

# formulate json
$values = new StdClass ();

# set the array values
$values->zoneLength = $POST->zoneLength;
$values->ipType = $POST->ipType;
$values->separator = $POST->separator;
$values->indicator = $POST->indicator;
$values->zoneGenerator = $POST->zoneGenerator;
$values->zoneGeneratorType = $POST->zoneGeneratorType;
$values->strictMode = $POST->strictMode;
$values->deviceType = $POST->deviceType;
$values->pattern = $POST->pattern;
$values->autogen = $POST->autogen;
$values->subnetPattern = $POST->subnetPattern;
$values->subnetPatternValues = $POST->subnetPatternValues;

# be sure that padding, strictMode and autogen will be set even if they are not delivered by $POST.
if($POST->padding != 'on')	{ $values->padding = 'off'; }
else 							{ $values->padding = $POST->padding; }

if($POST->strictMode != 'on'){ $values->strictMode = 'off'; }
else 							{ $values->strictMode = $POST->strictMode; }

if($POST->autogen != 'on')	{ $values->autogen = 'off'; }
else 							{ $values->autogen = $POST->autogen; }

# prepare the database update and encode the array with JSON_FORCE_OBJECT to keep the ids.
$values = array('id' => 1,
				'firewallZoneSettings' => json_encode($values,JSON_FORCE_OBJECT)
				);

# update the settings, alert or reply the success message.
if(!$Admin->object_modify("settings", "edit", "id", $values)) 	{ $Result->show("danger",  _("Cannot update settings"), true); }
else 															{ $Result->show("success", _("Settings updated successfully"), true);  }
