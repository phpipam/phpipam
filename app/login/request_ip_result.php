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
	# strip input tags
	$_POST = $Admin->strip_input_tags($_POST);

	# verify MAC Address
	if(!$Tools->validate_mac($_POST['mac']) ) 		{ $Result->show("danger", _('Please provide valid mac address').'! ('._('mac').': '.$Tools->strip_xss($_POST['mac']).')', true); }
	# verify hostname
	if(!$Tools->validate_hostname($_POST['hostname']) )		{ $Result->show("danger", _('Please provide valid hostname').'! ('._('hostname').': '.$Tools->strip_xss($_POST['hostname']).')', true); }

	# verify email
	if(!$Tools->validate_email($_POST['requester']) )		{ $Result->show("danger", _('Please provide valid email address').'! ('._('requester').': '.$Tools->strip_xss($_POST['requester']).')', true); }

	# formulate insert values
	$values = array(
					"subnetId"    => $_POST['subnetId'],
					"ip_addr"     => @$_POST['ip_addr'],
					"description" => @$_POST['description'],
					"mac" 		  => @$_POST['mac'],
					"hostname"    => @$_POST['hostname'],
					"state"       => $_POST['state'],
					"owner"       => $_POST['owner'],
					"requester"   => $_POST['requester'],
					"comment"     => @$_POST['comment'],
					"processed"   => 0
	    			);

	# custom fields
	$custom = $Tools->fetch_custom_fields('requests');
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {

			# replace possible ___ back to spaces
			$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
			if(isset($_POST[$myField['nameTest']])) { $_POST[$myField['name']] = $_POST[$myField['nameTest']];}

			# booleans can be only 0 and 1!
			if($myField['type']=="tinyint(1)") {
				if($_POST[$myField['name']]>1) {
					$_POST[$myField['name']] = 0;
				}
			}
			# not null!
			if($myField['Null']=="NO" && is_blank($_POST[$myField['name']])) { $Result->show("danger", $myField['name'].'" can not be empty!', true); }

			# save to update array
			$values[$myField['name']] = $_POST[$myField['name']];
		}
	}

	if(!$Admin->object_modify("requests", "add", "id", $values))	{ $Result->show("danger",  _('Error submitting new IP address request'), true); }
	else {
																	{ $Result->show("success", _('Request submitted successfully')); }
		# send mail
		$Tools->ip_request_send_mail ("new", $values);
	}
}
else 																{ $Result->show("danger",  _('IP requests disabled'), true); }