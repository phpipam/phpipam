<?php

/**
 *  Check connection
 */

require_once __DIR__.'/../../../../functions/functions.php';
require_once __DIR__.'/../../../../functions/adLDAP/src/adLDAP.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// feth settings
$auth_settings = $Admin->fetch_object('usersAuthMethod', 'id', $POST->id);
if ($auth_settings === false) {
    $Result->die('danger', _('Invalid ID'), true);
}

// set params
$parameters = new Params(db_json_decode($auth_settings->params));

// AD?
if (in_array($auth_settings->type, ['AD', 'LDAP', 'NetIQ'])) {
    // set controllers
    $controllers = explode(';', str_replace(' ', '', (string) $parameters->domain_controllers));

    // open connection
    try {
        if ($auth_settings->type == 'NetIQ') {
            $params->account_suffix = '';
        }

        // set options
        $options = [
            'base_dn' => $parameters->base_dn,
            'account_suffix' => $parameters->account_suffix,
            'domain_controllers' => $controllers,
            'use_ssl' => $parameters->use_ssl,
            'use_tls' => $parameters->use_tls,
            'ad_port' => $parameters->ad_port,
        ];
        $adldap = new adLDAP($options);
        // LDAP?
        if ($auth_settings->type == 'LDAP') {
            $adldap->setUseOpenLDAP(true);
        }
    } catch (adLDAPException $e) {
        // catch AD error
        $Result->die('danger', $e, true);
    }

    // result
    foreach ($controllers as $c) {
        $fp = @fsockopen($c, $parameters->ad_port, $errno, $errstr, 3);
        if ($fp === false) {
            $Result->warn('danger', "$c: $errstr ($errno)", true);
        } else {
            $Result->warn('success', "$c: "._('AD network connection ok').'!', true);
        }
    }
} elseif ($auth_settings->type == 'OIDC') {
    $OIDC = new OIDC($parameters);

    try {
        $discovery = $OIDC->fetch_discovery();
        $jwks = $OIDC->fetch_jwks();
        $algs = $OIDC->get_allowed_algs();
    } catch (OIDC_Exception $e) {
        $Result->die('danger', escape_input($e->getMessage().($e->detail !== '' ? ': '.$e->detail : '')), true);
    }

    $signing_keys = array_filter($jwks['keys'], fn ($key) => is_array($key) && in_array($key['kty'] ?? '', ['RSA', 'EC', 'OKP'], true) && ($key['use'] ?? 'sig') === 'sig');

    $details = [
        _('Issuer') => $discovery['issuer'],
        _('Authorization endpoint') => $discovery['authorization_endpoint'],
        _('Token endpoint') => $discovery['token_endpoint'],
        _('Userinfo endpoint') => $discovery['userinfo_endpoint'] ?? '-',
        _('ID token algorithms') => $algs === [] ? '-' : implode(', ', $algs),
        _('Signing keys') => count($signing_keys),
        _('Redirect URI') => $OIDC->get_redirect_uri(),
    ];

    $html = '';
    foreach ($details as $name => $value) {
        $html .= '<strong>'.$name.'</strong>: '.escape_input((string) $value).'<br>';
    }

    $Result->warn('success', _('OIDC discovery ok').'<hr>'.$html, true);

    if ($algs === [] || count($signing_keys) === 0) {
        $Result->warn('danger', _('No supported ID token signing algorithm or key is published'), true);
    }

    $auth_methods = $discovery['token_endpoint_auth_methods_supported'] ?? ['client_secret_basic'];
    $token_auth_method = $parameters->token_auth_method ?: 'client_secret_basic';
    if (is_array($auth_methods) && ! in_array($token_auth_method, $auth_methods, true)) {
        $Result->warn('warning', _('Client authentication method is not advertised by the provider').': '.escape_input($token_auth_method), true);
    }

    if (isset($discovery['scopes_supported']) && is_array($discovery['scopes_supported'])) {
        $unknown = array_diff(explode(' ', $OIDC->get_scopes()), $discovery['scopes_supported']);
        if ($unknown !== []) {
            $Result->warn('info', _('Scopes not advertised by the provider').': '.escape_input(implode(' ', $unknown)), true);
        }
    }
} else {
    $Result->die('danger', _('Check not implemented'), true);
}
