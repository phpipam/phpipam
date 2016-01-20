<?php

/**
 * Script to edit domain
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();
$PowerDNS 	= new PowerDNS ($Database);

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                   $Result->show("danger", _("Invalid CSRF cookie"), true);


# checks / validation
if ($_POST['action']!="delete") {
	// fqdn
	if ($_POST['action']=="add")
	if($Result->validate_hostname($_POST['name'])===false)			{ $Result->show("danger", "Invalid domain name", true); }
	// master
	if (strlen($_POST['master'])>0) {
		if(!filter_var($_POST['master'], FILTER_VALIDATE_IP))		{ $Result->show("danger", "Master must be an IP address", true); }
	}
	// type
	if(!in_array($_POST['type'], (array )$PowerDNS->domain_types))	{ $Result->show("danger", "Invalid domain type", true); }

	# new domain
	if ($_POST['action']=="add" && !isset($_POST['manual'])) {
		// admin
		if ($Result->validate_email($_POST['hostmaster'])===false)	{ $Result->show("danger", "Invalid domain admin", true); }
	}


	# if update sve old domain !
	if ($_POST['action']=="edit") {
		$old_domain = $PowerDNS->fetch_domain ($_POST['id']);
	}
}

# set update array
$values = array("id"=>@$_POST['id'],
				"master"=>@$_POST['master'],
				"type"=>@$_POST['type']
				);
# name only on add
if ($_POST['action']=="add")
$values['name'] = $_POST['name'];


# remove all references if delete
if ($_POST['action']=="delete") 									{ $PowerDNS->remove_all_records ($values['id']); }


# update
if(!$PowerDNS->domain_edit($_POST['action'], $values))				{ $Result->show("danger",  _("Failed to $_POST[action] domain").'!', true); }
else																{ $Result->show("success", _("Domain $_POST[action] successfull").'!', false); }

# update all references if edit
if ($_POST['action']=="edit" && $old_domain->name!=$values['name'])	{ $PowerDNS->update_all_records ($values['id'], $values['name']); }

# create default records
if ($_POST['action']=="add" && !isset($_POST['manual']))			{ $PowerDNS->create_default_records ($_POST); }
?>