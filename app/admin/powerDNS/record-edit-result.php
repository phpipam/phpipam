<?php

/**
 * Script to edit domain
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Result = new Result();
$PowerDNS = new PowerDNS($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("pdns", 2, true, true);
}
else {
    $User->check_module_permissions ("pdns", 3, true, true);
}

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie("validate", "record", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch old record
if ($_POST['action'] != "add") {
    $record = $PowerDNS->fetch_record($_POST['id']);
    $record !== false ?: $Result->show("danger", _("Invalid ID"), true, true);
}

# edit and add - check that smth is in name and content!
if ($_POST['action'] != "delete") {
    if (strlen($_POST['name']) < 2) {$Result->show("danger", _("Invalid name"), true);}
    if (strlen($_POST['content']) < 2) {$Result->show("danger", _("Invalid content"), true);}
}

# dont permit modifications on slave domain
$domain = $PowerDNS->fetch_domain ($_POST['domain_id']);
$domain!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

if(strtolower($domain->type) == "slave") {
	$Result->show("danger", _("Adding domain record on slave zone is not permitted"), true);
}

# validate and set values
if ($_POST['action'] == "edit") {
    $values = $PowerDNS->formulate_update_record($_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['disabled'], $record->change_date);
    $values['domain_id'] = $_POST['domain_id'];
} elseif ($_POST['action'] == "add") {
    $values = $PowerDNS->formulate_new_record($_POST['domain_id'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['disabled']);
} elseif ($_POST['action'] == "delete") {
    $values['domain_id'] = $_POST['domain_id'];
}

# add id
$values['id'] = @$_POST['id'];

# remove empty records
$values = $PowerDNS->remove_empty_array_fields($values);

# update
$PowerDNS->record_edit($_POST['action'], $values);