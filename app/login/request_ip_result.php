<?php

/* functions */
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$Subnets 	= new Subnets ($Database);
$Tools	 	= new Tools ($Database);
$Admin	 	= new Admin ($Database, false);
$User		= new User ($Database);
$Result 	= new Result ();

# fetch settings, user is not authenticated !
$Tools->get_settings();

# disable requests module for public (non-authenticated users)
if(!$User->is_authenticated() && @$config['requests_public']===false) {
	$Tools->settings->enableIPrequests = 0;
}

# requests must be enabled!
if($Tools->settings->enableIPrequests==1) {
	# verify MAC Address
	if (!$Tools->validate_mac($POST->mac)) {
		$Result->show("danger", _('Please provide valid mac address') . '! (' . _('mac') . ': ' . escape_input($POST->mac) . ')', true);
	}
	# verify hostname
	if (!$Tools->validate_hostname($POST->hostname)) {
		$Result->show("danger", _('Please provide valid hostname') . '! (' . _('hostname') . ': ' . escape_input($POST->hostname) . ')', true);
	}

	# verify email
	if (!$Tools->validate_email($POST->requester)) {
		$Result->show("danger", _('Please provide valid email address') . '! (' . _('requester') . ': ' . escape_input($POST->requester) . ')', true);
	}

	# formulate insert values
	$values = array(
					"subnetId"    => $POST->subnetId,
					"ip_addr"     => $POST->ip_addr,
					"description" => $POST->description,
					"mac" 		  => $POST->mac,
					"hostname"    => $POST->hostname,
					"state"       => $POST->state,
					"owner"       => $POST->owner,
					"requester"   => $POST->requester,
					"comment"     => $POST->comment,
					"processed"   => 0
	    			);

	# fetch custom fields
	$update = $Tools->update_POST_custom_fields('requests', $POST->action, $POST);
	$values = array_merge($values, $update);

	if(!$Admin->object_modify("requests", "add", "id", $values))	{ $Result->show("danger",  _('Error submitting new IP address request'), true); }
	else {
																	{ $Result->show("success", _('Request submitted successfully')); }
		# send mail
		$Tools->ip_request_send_mail ("new", $values);
	}
}
else 																{ $Result->show("danger",  _('IP requests disabled'), true); }