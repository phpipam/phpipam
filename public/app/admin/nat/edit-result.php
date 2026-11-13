<?php

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('nat', User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions('nat', User::ACCESS_RWA, true, true);
}

// fetch custom fields
$custom = $Tools->fetch_custom_fields('nat');

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// validations
if ($POST->action == 'delete' || $POST->action == 'edit') {
    if ($Admin->fetch_object('nat', 'id', $POST->id) === false) {
        $Result->warn('danger', _('Invalid NAT object identifier'));
    }
}

if ($POST->action == 'add' || $POST->action == 'edit') {
    // name
    if (strlen((string) $POST->name) < 3) {
        $Result->die('danger', _('Name must have at least 3 characters'));
    }

    if (! in_array($POST->type, ['source', 'static', 'destination'])) {
        $Result->die('danger', _('Invalid NAT type'));
    }

    if (isset($POST->device)) {
        if (! is_numeric($POST->device)) {
            $Result->die('danger', _('Invalid device'));
        }
    }
}

// set values
// nothing to do here for l10n, the content of the array goes into the database
$values = [
    'id' => $POST->id,
    'name' => $POST->name,
    'type' => $POST->type,
    'src_port' => $POST->src_port,
    'dst_port' => $POST->dst_port,
    'device' => $POST->device,
    'description' => $POST->description,
    'policy' => 'No',
    'policy_dst' => '',
];

if ($User->get_module_permissions('devices') == User::ACCESS_NONE) {
    unset($values['device']);
}

// policy NAT override
if ($POST->action == 'edit' && ! is_blank($POST->policy_dst)) {
    $values['policy'] = 'Yes';
    $values['policy_dst'] = $POST->policy_dst;
}

// append custom
if (is_array($custom)) {
    foreach ($custom as $myField) {
        // replace possible ___ back to spaces!
        $myField['nameTest'] = str_replace(' ', '___', (string) $myField['name']);
        if (isset($POST->{$myField['nameTest']})) {
            $values[$myField['name']] = $POST->{$myField['nameTest']};
        }
    }
}

// execute update
if (! $Admin->object_modify('nat', $POST->action, 'id', $values)) {
    $Result->warn('danger', _('NAT').' '.$User->get_post_action().' '._('failed'));
} else {
    $Result->warn('success', _('NAT').' '.$User->get_post_action().' '._('successful'));
}

// add
if ($POST->action == 'add') {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
