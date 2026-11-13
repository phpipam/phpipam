<?php

/**
 * Edit logical circuit result
 ***************************/

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
    $User->check_module_permissions('circuits', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('circuits', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// validate action
$Admin->validate_action();

// IDs must be numeric
if ($POST->action != 'add' && ! is_numeric($POST->id)) {
    $Result->die('danger', _('Invalid ID'));
}

// Logical circuit ID must be present
if ($POST->logical_cid == '') {
    $Result->die('danger', _('Logical Circuit ID is mandatory').'!');
}

// Validate to make sure there aren't duplicates of the same circuit in the list of circuit ids
// Create list of member circuit IDs for mapping
$POST->circuit_list = str_replace('undefined.', '', (string) $POST->circuit_list);
$id_list = $POST->circuit_list !== '' ? explode('.', rtrim((string) $POST->circuit_list, '.')) : [];
if (count($id_list) != count(array_unique($id_list))) {
    $Result->die('danger', _('Remove duplicates of circuit').'!');
}

if ($POST->action == 'add' && count($id_list) == 0) {
    $Result->die('danger', _('No circuits selected').'!');
}

// set update values
$values = [
    'id' => $POST->id,
    'logical_cid' => $POST->logical_cid,
    'purpose' => $POST->purpose,
    'comments' => $POST->comments,
    'member_count' => count($id_list),
];

// fetch custom fields
$update = $Tools->update_POST_custom_fields('circuitsLogical', $POST->action, $POST);
$values = array_merge($values, $update);

// update circuit
if ($Admin->object_modify('circuitsLogical', $POST->action, 'id', $values)) {
    // If this is a new circuit, save id of insert and process
    if ($POST->id == '') {
        if ($Admin->lastId == null) {
            $Result->die('danger', _('Logical circuit added, but failed to create mapping').'!');
        } else {
            $POST->id = $Admin->lastId;
        }
    }

    // delete
    if ($POST->action != 'add') {
        try {
            $Database->deleteObjectsByIdentifier('circuitsLogicalMapping', 'logicalCircuit_id', $POST->id);
        } catch (Exception $e) {
            $Result->warn('danger', _('Error dropping mapping: ').$e->getMessage());
        }
    }

    // add mapping
    // Grab list of IDs and create list
    $order = 0;

    if (is_array($id_list) && $id_list != []) {
        foreach ($id_list as $member_id) {
            // insert values
            $values = [
                'logicalCircuit_id' => $POST->id,
                'circuit_id' => $member_id,
                'order' => $order,
            ];

            // insert to mapping
            if (! $Admin->object_modify('circuitsLogicalMapping', 'add', 'id', $values)) {
                $Result->warn('danger', _('Error inserting mapping.'));
            }

            $order++;
        }

        // all ok
        $Result->warn('success', _('Logical Circuit').' '.$User->get_post_action().' '._('successful').'!');
    } else {
        if ($POST->action == 'delete') {
            $Result->warn('success', _('Logical Circuit').' '.$User->get_post_action().' '._('successful').'!');
        } else {
            $Result->warn('warning', _('No circuits selected').'!');
        }
    }
}
