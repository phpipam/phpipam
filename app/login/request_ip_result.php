<?php

/* functions */
require( dirname(__FILE__) . '/../../functions/functions.php');
/* @mail functions ------------------- */
require( dirname(__FILE__) . '/../../functions/classes/class.Mail.php');

# initialize user object
$Database 	= new Database_PDO;
$Subnets 	= new Subnets ($Database);
$Tools	 	= new Tools ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# fetch settings, user is not authenticated !
$settings = $Tools->fetch_settings();

# requests must be enabled!
if($settings['enableIPrequests']==1) {
	# verify email
	if(!validate_email($_POST['requester']) ) 						{ $Result->show("danger", _('Please provide valid email address').'! ('._('requester').': '.$_POST['requester'].')', true); }

	# formulate insert values
	$values = array("subnetId"=>$_POST['subnetId'],
					"ip_addr"=>@$_POST['ip_addr'],
	    			"description"=>@$_POST['description'],
	    			"dns_name"=>@$_POST['dns_name'],
	    			"owner"=>$_POST['owner'],
	    			"requester"=>$_POST['requester'],
	    			"comment"=>@$_POST['comment'],
	    			"processed"=>0
	    			);
	if(!$Admin->object_modify("requests", "add", "id", $values))	{ $Result->show("danger",  _('Error submitting new IP address request'), true); }
	else {
																	{ $Result->show("success", _('Request submitted successfully')); }
		# send mail
		//if(!sendIPReqEmail($_POST))									{ $Result->show("danger",  _('Sending mail for new IP request failed'), true); }
		//else														{ $Result->show("success", _('Sending mail for IP request succeeded'), true); }
	}
}
else 																{ $Result->show("danger",  _('IP requests disabled'), true); }

?>