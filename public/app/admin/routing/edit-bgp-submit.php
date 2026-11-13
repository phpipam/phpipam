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

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('routing', User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions('routing', User::ACCESS_RWA, true, true);
}

// validate
if ($POST->action == 'edit' || $POST->action == 'add') {
    if (! is_numeric($POST->local_as)) {
        $Result->die('danger', _('Invalid local AS'));
    }

    if (! is_numeric($POST->peer_as)) {
        $Result->die('danger', _('Invalid peer AS'));
    }

    if (! $Tools->validate_ip($POST->local_address)) {
        $Result->die('danger', _('Invalid local address'));
    }

    if (! $Tools->validate_ip($POST->peer_address)) {
        $Result->die('danger', _('Invalid peer address'));
    }
}

if ($POST->action == 'edit' || $POST->action == 'delete') {
    if (! is_numeric($POST->id)) {
        $Result->die('danger', _('Invalid ID'));
    }
}

// permission recheck for modules
if (isset($POST->vrf_id)) {
    if ($User->get_module_permissions('vrf') == User::ACCESS_NONE) {
        $Result->die('danger', _('Insufficient permissions for module VRF'));
    }

    if (! is_numeric($POST->vrf_id)) {
        $Result->die('danger', _('Invalid VRF ID'));
    }
}

if (isset($POST->circuit_id)) {
    if ($User->get_module_permissions('circuits') == User::ACCESS_NONE) {
        $Result->die('danger', _('Insufficient permissions for module Circuits'));
    }

    if (! is_numeric($POST->circuit_id)) {
        $Result->die('danger', _('Invalid Circuits ID'));
    }
}

if (isset($POST->customer_id)) {
    if ($User->get_module_permissions('customers') == User::ACCESS_NONE) {
        $Result->die('danger', _('Insufficient permissions for module Customers'));
    }

    if (! is_numeric($POST->customer_id)) {
        $Result->die('danger', _('Invalid Customer ID'));
    }
}

// create update array
$values = [
    'id' => $POST->id ?? null,
    'bgp_type' => $POST->bgp_type,
    'local_as' => $POST->local_as,
    'local_address' => $POST->local_address,
    'peer_name' => $POST->peer_name,
    'peer_as' => $POST->peer_as,
    'peer_address' => $POST->peer_address,
    'description' => $POST->description,
];
// modules
if (isset($POST->vrf_id)) {
    $values['vrf_id'] = $POST->vrf_id != 0 ? $POST->vrf_id : null;
}

if (isset($POST->circuit_id)) {
    $values['circuit_id'] = $POST->circuit_id != 0 ? $POST->circuit_id : null;
}

if (isset($POST->customer_id)) {
    $values['customer_id'] = $POST->customer_id != 0 ? $POST->customer_id : null;
}

// fetch custom fields
$update = $Tools->update_POST_custom_fields('routing_bgp', $POST->action, $POST);
$values = array_merge($values, $update);

// execute update
if (! $Admin->object_modify('routing_bgp', $POST->action, 'id', $values)) {
    $Result->warn('danger', _('BGP').' '.$User->get_post_action().' '._('failed'));
} else {
    $Result->warn('success', _('BGP').' '.$User->get_post_action().' '._('successful'));
}

// add
if ($POST->action == 'add') {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
