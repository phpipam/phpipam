<?php

/**
 * Script to display vault edit result
 *************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result;
$Password_check = new Password_check;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

// make sure user has access
if ($User->get_module_permissions('vaults') < User::ACCESS_RW) {
    $Result->die('danger', _('Insufficient privileges').'.', true);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// fetch vault details
$vault = $Admin->fetch_object('vaults', 'id', $POST->vaultId);
if (! is_object($vault)) {
    $Result->die('danger', _('Invalid ID'));
}

// fetch custom fields
$custom = $Tools->fetch_custom_fields('vaultItems');

/* checks */
$error = [];
// cert check
if ($POST->action == 'add') {
    // print_r(base64_decode($POST->certificate));
    // if(openssl_x509_parse(base64_decode($POST->certificate))===false) { $Result->die("danger", _("Cannot parse certificate")); }
}
// for edits without certificate get old value!
elseif ($POST->action == 'edit') {
    if (openssl_x509_parse(base64_decode((string) $POST->certificate)) === false) {
        $vault_item = $Tools->fetch_object('vaultItems', 'id', $POST->id);
        $vault_item_values = db_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION['vault'.$POST->vaultId]));
        $POST->certificate = $vault_item_values->certificate;
    }
}

// add, edit
if ($POST->action != 'delete') {
    // name must be more than 2 and alphanumeric
    if (strlen((string) $POST->name) < 3 || strlen((string) $POST->name) > 64) {
        $error[] = 'Invalid name';
    }
}

// custom

// die if errors
if (count($error) > 0) {
    $Result->die('danger', $error);
} else {
    // values to encrypt
    $values_array = [
        'name' => $POST->name,
        'description' => $POST->description,
        'certificate' => $POST->certificate,
    ];

    // remove certificate if not present
    if ($POST->action != 'delete' && is_blank($POST->certificate)) {
        $Result->die('danger', _('Invalid certificate'));
    }

    // print "<pre>";
    // print_r(base64_decode($POST->certificate));
    // die('alert-danger');

    // create array of values for modification
    $values = [
        'id' => $POST->id,
        'values' => $User->Crypto->encrypt(json_encode($values_array), $_SESSION['vault'.$vault->id]),
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

    // add
    if ($POST->action == 'add') {
        $values['type'] = 'certificate';
        $values['type_certificate'] = $POST->type;
        $values['vaultId'] = $vault->id;
    }

    // execute
    if (! $Admin->object_modify('vaultItems', $POST->action, 'id', $values)) {
        $Result->die('danger', _('Vault item').' '.$User->get_post_action().' '._('error'));
    } else {
        $Result->die('success', _('Vault item').' '.$User->get_post_action().' '._('success'));
    }
}
