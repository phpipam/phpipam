<?php

/* functions */
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$Subnets 	= new Subnets ($Database);
$Tools	 	= new Tools ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# fetch settings, user is not authenticated !
$Tools->get_settings();

# disable requests module for public
if(@$config['requests_public']===false) {
	$Tools->settings->enableIPrequests = 0;
}

# requests must be enabled!
if($Tools->settings->enableIPrequests==1) {
	# strip input tags
	$_POST = $Admin->strip_input_tags($_POST);

	# verify email
	if(!$Result->validate_email($_POST['requester']) ) 				{ $Result->show("danger", _('Please provide valid email address').'! ('._('requester').': '.$Tools->strip_xss($_POST['requester']).')', true); }

	# formulate insert values
	$values = array(
					"subnetId"    => $_POST['subnetId'],
					"ip_addr"     => @$_POST['ip_addr'],
					"description" => @$_POST['description'],
					"hostname"    => @$_POST['hostname'],
					"state"       => $_POST['state'],
					"owner"       => $_POST['owner'],
					"requester"   => $_POST['requester'],
					"comment"     => @$_POST['comment'],
					"processed"   => 0
	    			);
	if(!$Admin->object_modify("requests", "add", "id", $values))	{ $Result->show("danger",  _('Error submitting new IP address request'), true); }
	else {
																	{ $Result->show("success", _('Request submitted successfully')); }
		# send mail
		$Tools->ip_request_send_mail ("new", $values);
	}
}
else 																{ $Result->show("danger",  _('IP requests disabled'), true); }