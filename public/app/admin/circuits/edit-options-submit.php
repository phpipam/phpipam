<?php

/**
 *  Edit device details
 ************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Tools = new Tools($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('circuits', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('circuits', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// empty
if (is_blank($POST->option)) {
    $Result->die('danger', _('Value cannot be empty'));
}

// validate action
if ($POST->action != 'add' && $POST->action != 'delete') {
    $Result->die('danger', _('Invalid action'));
}

// validate type
if ($POST->type != 'type') {
    $Result->die('danger', _('Invalid type'));
}

// defaults must be present
if ($POST->type == 'type') {
    if ($POST->option == 'Default') {
        $Result->die('danger', _('Default value cannot be deleted'));
    }
}

// set values
$values = [
    'id' => $POST->op_id,
    'ctname' => $POST->option,
    'ctcolor' => $POST->color,
    'ctpattern' => $POST->pattern,
];
// execute
if (! $Admin->object_modify('circuitTypes', $POST->action, 'id', $values)) {
    $Result->die('danger', _('Option '.$User->get_post_action().' error'));
} else {
    $Result->warn('success', _('Option '.$User->get_post_action().' success'));
}

// updates values to default
if (is_numeric($POST->op_id)) {
    $Admin->update_object_references('circuits', 'type', $POST->op_id, 1);
}
