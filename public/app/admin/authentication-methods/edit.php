<?php

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;
$Log = new Logging($Database, $User->settings);

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// create csrf token
$csrf = $User->Crypto->csrf_session_token();

// if edit check if protected?
if ($POST->action == 'add') {
    if ($POST->type == 'http') {
        $Result->die('danger', _('Only one http method allowed'), true);
    }

    if ($POST->type == 'OIDC' && is_object($Admin->fetch_object('usersAuthMethod', 'type', 'OIDC'))) {
        $Result->die('danger', _('Only one OIDC method allowed'), true);
    }
} else {
    $auth_method = $Admin->fetch_object('usersAuthMethod', 'id', $POST->id);
    if (! is_object($auth_method)) {
        $Result->die('danger', _('Invalid ID'));
    }

    if ($auth_method->type !== $POST->type) {
        $Result->die('danger', _('Invalid method type'), true);
    }

    if ($auth_method->protected == 'Yes') {
        if ($POST->action != 'edit' || $POST->id != 2) {
            $Result->die('danger', _('Method cannot be change as it is protected'), true);
        }
    }
}

// check for permitted auth methods
$permitted_methods = $User->fetch_available_auth_method_types();
$permitted_methods[] = 'http';

if (! in_array($POST->type, $permitted_methods)) {
    $Result->die('danger', _('Invalid method type'), true);
}

// route to proper auth method editing
if (! file_exists(__DIR__.'/edit-'.$POST->type.'.php')) {
    $Result->die('danger', _('Invalid method type'), true);
}

include __DIR__.'/edit-'.$POST->type.'.php';
