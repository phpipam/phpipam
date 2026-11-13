<?php

/**
 * Script to edit VLAN details
 *******************************/

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
    $User->check_module_permissions('vlan', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('vlan', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

// if it already exist die
if ($User->settings->vlanDuplicate == 0 && ($POST->action == 'add' || $POST->action == 'edit')) {
    $check_vlan = $Admin->fetch_multiple_objects('vlans', 'domainId', $POST->domainid, 'vlanId');
    // check
    if ($check_vlan !== false) {
        foreach ($check_vlan as $v) {
            if ($v->vlanId != $POST->vlanid && $v->number == $POST->number) {
                $Result->die('danger', _('VLAN already exists'));
            }
        }
    }
}

// if unique required
if (isset($POST->unique)) {
    if ($POST->unique == 'on') {
        if ($Tools->fetch_object('vlans', 'number', $POST->number) !== false) {
            $Result->die('danger', _('VLAN already exists in another domain!'));
        }
    }
}

// if number too high
if ($POST->number > $User->settings->vlanMax && $POST->action != 'delete') {
    $Result->die('danger', _('Highest possible VLAN number is ').$User->settings->vlanMax.'!');
}

if ($POST->action == 'add') {
    if ($POST->number < 0) {
        $Result->die('danger', _('VLAN number cannot be negative').'!');
    } elseif (! is_numeric($POST->number)) {
        $Result->die('danger', _('Not number').'!');
    }
}

if (is_blank($POST->name)) {
    $Result->die('danger', _('Name is required').'!');
}

// formulate update query
$values = [
    'vlanId' => $POST->vlanid,
    'number' => $POST->number,
    'name' => $POST->name,
    'description' => $POST->description,
    'domainId' => $POST->domainid,
];

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

// append customerId
if ($User->settings->enableCustomers == '1') {
    if (is_numeric($POST->customer_id)) {
        if ($POST->customer_id > 0) {
            $values['customer_id'] = $POST->customer_id;
        } else {
            $values['customer_id'] = null;
        }
    }
}

// update
if (! $Admin->object_modify('vlans', $POST->action, 'vlanId', $values)) {
    $Result->die('danger', _('Failed to '.$User->get_post_action().' VLAN').'!');
} else {
    $Result->warn('success', _('VLAN '.$User->get_post_action().' successful').'!');
}

// remove all references if delete
if ($POST->action == 'delete') {
    $Admin->remove_object_references('subnets', 'vlanId', $POST->vlanid);
}

// print value for on the fly
if ($POST->action == 'add') {
    print '<p id="vlanidforonthefly"    style="display:none">'.$Admin->lastId.'</p>';
}
