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

# fetch old record
if($_POST['action']!="add") {
	$record = $PowerDNS->fetch_record ($_POST['id']);
	$record!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
}

# edit and add - check that smth is in name and content!
if ($_POST['action']!="delete") {
	if (strlen($_POST['name'])<2)		{ $Result->show ("danger", _("Invalid name"), true); }
	if (strlen($_POST['content'])<2)	{ $Result->show ("danger", _("Invalid content"), true); }
}


# validate and set values
if($_POST['action']=="edit") {
	$values = $PowerDNS->formulate_update_record ($_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['disabled'], $record->change_date);
	$values['domain_id'] = $_POST['domain_id'];
}
elseif($_POST['action']=="add") {
	$values = $PowerDNS->formulate_new_record ($_POST['domain_id'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['disabled']);
}
elseif($_POST['action']=="delete") {
	$values['domain_id'] = $_POST['domain_id'];
}

# add id
$values['id'] 		 = @$_POST['id'];

# remove empty records
$values = $PowerDNS->remove_empty_array_fields ($values);

# update
$PowerDNS->record_edit($_POST['action'], $values);

?>