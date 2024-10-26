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
if($POST->action=="edit") {
    $User->check_module_permissions ("pdns", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("pdns", User::ACCESS_RWA, true, true);
}

# validate csrf cookie
$User->Crypto->csrf_cookie("validate", "record", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch old record
if ($POST->action != "add") {
    $record = $PowerDNS->fetch_record($POST->id);
    $record !== false ?: $Result->show("danger", _("Invalid ID"), true, true);
}

# edit and add - check that smth is in name and content!
if ($POST->action != "delete") {
    if (strlen($POST->name) < 2) {$Result->show("danger", _("Invalid name"), true);}
    if (strlen($POST->content) < 2) {$Result->show("danger", _("Invalid content"), true);}
}

# dont permit modifications on slave domain
$domain = $PowerDNS->fetch_domain ($POST->domain_id);
$domain!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

if(strtolower($domain->type) == "slave") {
	$Result->show("danger", _("Adding domain record on slave zone is not permitted"), true);
}

# validate and set values
if ($POST->action == "edit") {
    $values = $PowerDNS->formulate_update_record($POST->name, $POST->type, $POST->content, $POST->ttl, $POST->prio, $POST->disabled, $record->change_date);
    $values['domain_id'] = $POST->domain_id;
} elseif ($POST->action == "add") {
    $values = $PowerDNS->formulate_new_record($POST->domain_id, $POST->name, $POST->type, $POST->content, $POST->ttl, $POST->prio, $POST->disabled);
} elseif ($POST->action == "delete") {
    $values['domain_id'] = $POST->domain_id;
}

# add id
$values['id'] = $POST->id;

# remove empty records
$values = $PowerDNS->remove_empty_array_fields($values);

# update
$PowerDNS->record_edit($POST->action, $values);