<?php

/**
 *  Edit authentication method
 */

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// check if site is demo
$User->is_demo();
// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

unset($POST->csrf_cookie);

// get action
$action = $POST->action;

// for adding remove id
if ($action == 'add') {
    if ($POST->type == 'http') {
        $Result->die('danger', _('Only one http method allowed'), true);
    }

    if ($POST->type == 'OIDC' && is_object($Admin->fetch_object('usersAuthMethod', 'type', 'OIDC'))) {
        $Result->die('danger', _('Only one OIDC method allowed'), true);
    }

    unset($POST->id);
} else {
    // if edit check if protected?
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

// set update query
$values = [
    'id' => $POST->id,
    'type' => $POST->type,
    'description' => $POST->description,
];

// Validate input
if ($POST->type == 'SAML2') {
    // The JIT & SAML mapped user options are mutually exclusive.
    if (filter_var($POST->jit, FILTER_VALIDATE_BOOLEAN) && ! empty($POST->MappedUser)) {
        $Result->die('danger', _('The JIT and mapped user options are mutually exclusive'));
    }

    // Validate Prettify links is enabled for strict mode.
    if (filter_var($POST->strict, FILTER_VALIDATE_BOOLEAN) && ! filter_var($User->settings->prettyLinks, FILTER_VALIDATE_BOOLEAN)) {
        $Result->die('danger', _('Strict mode requires global setting "Prettify links"=yes'));
    }

    // Verify that the private certificate and key are provided if Signing Authn Requests is set
    if ($action != 'delete' && filter_var($POST->spsignauthn, FILTER_VALIDATE_BOOLEAN) && (empty($POST->spx509cert) || empty($POST->spx509key))) {
        $Result->die('danger', _('SP (Client) certificate and key are required to sign Authn requests'));
    }
}

if ($POST->type == 'OIDC' && $action != 'delete') {
    $POST->issuer = trim((string) $POST->issuer);
    if (! OIDC::is_https_url($POST->issuer) || ! is_null(parse_url($POST->issuer, PHP_URL_QUERY)) || ! is_null(parse_url($POST->issuer, PHP_URL_FRAGMENT))) {
        $Result->die('danger', _('Issuer must be an https URL without query or fragment'));
    }

    $POST->client_id = trim((string) $POST->client_id);
    if (! preg_match('/^[\x20-\x7E]{1,255}$/', $POST->client_id)) {
        $Result->die('danger', _('Invalid client ID'));
    }

    // the stored secret is never sent to the browser, blank keeps it
    if ($action == 'edit' && is_blank($POST->client_secret)) {
        $POST->client_secret = (string) (db_json_decode($auth_method->params, true)['client_secret'] ?? '');
    } elseif ($POST->client_secret !== ($_POST['client_secret'] ?? null)) {
        $Result->die('danger', _('Client secret must not contain HTML tags'));
    }

    if (is_blank($POST->client_secret)) {
        $Result->die('danger', _('Client secret is required'));
    }

    if (! in_array($POST->token_auth_method, ['client_secret_basic', 'client_secret_post'], true)) {
        $Result->die('danger', _('Invalid client authentication method'));
    }

    $oidc_scopes = preg_split('/\s+/', trim((string) $POST->scopes), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($oidc_scopes as $oidc_scope) {
        if (! preg_match('/^[\x21\x23-\x5B\x5D-\x7E]+$/', $oidc_scope)) {
            $Result->die('danger', _('Invalid scope').': '.escape_input($oidc_scope));
        }
    }

    if (! in_array('openid', $oidc_scopes, true)) {
        array_unshift($oidc_scopes, 'openid');
    }

    $POST->scopes = implode(' ', $oidc_scopes);

    $POST->username_claim = trim((string) $POST->username_claim);
    if (! preg_match('/^[\x21\x23-\x7E]{1,255}$/', $POST->username_claim)) {
        $Result->die('danger', _('Invalid username claim'));
    }
}

// remove processed params
unset($POST->id, $POST->type, $POST->description, $POST->action);
$values['params'] = json_encode($POST);

$secure_keys = [
    'adminUsername',
    'adminPassword',
    'secret',
    'idpx509cert',
    'spx509cert',
    'spx509key',
    'client_secret',
];
// log values
$values_log = $values;
// mask secure keys
foreach ($POST as $key => $value) {
    if (in_array($key, $secure_keys)) {
        $POST->{$key} = '********';
    }
}

$values_log['params'] = json_encode($POST);

// add - set protected
if ($action == 'add') {
    $values['protected'] = 'No';
}

// update
if (! $Admin->object_modify('usersAuthMethod', $action, 'id', $values, $values_log)) {
    $Result->warn('danger', _('Failed to edit authentication method'));
} else {
    $Result->warn('success', _('Authentication method updated'));
}

// if delete also reset all users that have thos auth method
if ($action == 'delete') {
    $Database->runQuery('update `users` set `authMethod`=1 where `authMethod`= ?;', [$values['id']]);
}
