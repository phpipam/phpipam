<?php

/**
 * OpenID Connect login
 *
 * Without code or error parameters this starts a login and redirects to the
 * identity provider. With them it is the redirect URI (callback).
 * Every failure ends with a redirect to the login page and a flash message.
 */
require_once __DIR__.'/../../../functions/functions.php';

if (! isset($Database)) {
    $Database = new Database_PDO;
    $User = new User($Database);
    $Tools = new Tools($Database);
    $Log = new Logging($Database);
    $Result = new Result;
}

header('Referrer-Policy: no-referrer');

$oidc_was_authenticated = $User->is_authenticated();
$oidc_username = null;
$oidc_params = new Params;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit();
    }

    $oidc_method = $User->settings->dbversion >= 49 ? $Tools->fetch_object('usersAuthMethod', 'type', 'OIDC') : false;
    if (! is_object($oidc_method)) {
        throw new OIDC_Exception(_('OIDC authentication is not configured'), '', null);
    }

    $oidc_params = new Params(db_json_decode($oidc_method->params, true));
    $OIDC = new OIDC($oidc_params);

    // no captcha is possible on this path, so refuse while the IP is blocked
    $oidc_attempts = $User->block_check_ip();
    if ($oidc_attempts !== false && $oidc_attempts >= $User->blocklimit) {
        throw new OIDC_Exception(_('Too many failed login attempts, please try again in 5 minutes'), '', null);
    }

    $oidc_state = is_string($_GET['state'] ?? null) ? $_GET['state'] : '';

    /* start login */
    if (! isset($_GET['code']) && ! isset($_GET['error'])) {
        if ($oidc_was_authenticated) {
            header('Location: '.(OIDC::safe_redirect_target($User->get_redirect_cookie()) ?: create_link('dashboard')));
            exit();
        }

        header('Location: '.$OIDC->begin_flow((string) OIDC::safe_redirect_target($User->get_redirect_cookie())));
        exit();
    }

    /* callback */
    if (isset($_GET['error'])) {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/', $oidc_state)) {
            $OIDC->consume_flow($oidc_state);
        }

        throw $OIDC->idp_error($_GET['error'], $_GET['error_description'] ?? '');
    }

    $oidc_code = $_GET['code'];
    if (! preg_match('/^[A-Za-z0-9_-]{43}$/', $oidc_state) || ! is_string($oidc_code) || ! preg_match('/^[\x21-\x7E]{1,2048}$/', $oidc_code)) {
        throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Malformed state or code parameter', 2, true);
    }

    $oidc_flow = $OIDC->consume_flow($oidc_state);
    if ($oidc_flow === null) {
        throw new OIDC_Exception(_('Login request is invalid or has expired, please try again'), 'Unknown, reused or expired state', 2, true);
    }

    $OIDC->check_response_issuer($_GET['iss'] ?? null);

    $oidc_tokens = $OIDC->exchange_code($oidc_code, $oidc_flow);
    $oidc_claims = $OIDC->verify_id_token($oidc_tokens['id_token'], $oidc_flow);

    if (filter_var($oidc_params->userinfo, FILTER_VALIDATE_BOOLEAN)) {
        $oidc_userinfo = $OIDC->fetch_userinfo($oidc_tokens['access_token'] ?? '', $oidc_claims['sub']);
        $oidc_claims = OIDC::merge_userinfo($oidc_claims, $oidc_userinfo);
    }

    /* map the identity to a phpIPAM account */
    $oidc_username_claim = is_blank($oidc_params->username_claim) ? 'preferred_username' : $oidc_params->username_claim;
    $oidc_username = OIDC::validate_username(OIDC::get_claim($oidc_claims, $oidc_username_claim));
    if ($oidc_username === false) {
        $oidc_username = null;
        throw new OIDC_Exception(_('The identity provider did not supply a valid username'), sprintf('Claim "%s" is missing or not a valid username', $oidc_username_claim));
    }

    $oidc_user = $Tools->fetch_object('users', 'username', $oidc_username);

    if (is_object($oidc_user) && (int) $oidc_user->authMethod !== (int) $oidc_method->id) {
        throw new OIDC_Exception(_('User is not configured for OIDC authentication'), 'Existing account uses a different authentication method', 2, true);
    }

    if (! is_object($oidc_user)) {
        throw new OIDC_Exception(_('User is not provisioned in phpIPAM'), 'No phpIPAM account for this user');
    }

    if ($oidc_user->disabled == 'Yes') {
        throw new OIDC_Exception(_('User account is disabled'), 'Account is disabled');
    }

    // authenticate() reports refusals through Result, turn them into exceptions for the redirect below
    $User->Result->exit_method = 'exception';
    try {
        $User->authenticate($oidc_username, '', 'OIDC');
    } catch (OIDC_Exception $e) {
        throw $e;
    } catch (Exception $e) {
        // already logged by authenticate()
        throw new OIDC_Exception(strip_tags($e->getMessage()), '', null);
    } finally {
        $User->Result->exit_method = 'result';
    }

    header('Location: '.(OIDC::safe_redirect_target($oidc_flow['redirect']) ?: OIDC::safe_redirect_target($User->get_redirect_cookie()) ?: create_link('dashboard')), true, 302);
    exit();
} catch (Throwable $throwable) {
    if (! $throwable instanceof OIDC_Exception) {
        $throwable = new OIDC_Exception(_('OIDC login failed'), get_class($throwable).': '.$throwable->getMessage());
    }

    if ($throwable->severity !== null) {
        $Log->write(_('OIDC login'), $throwable->getMessage().($throwable->detail !== '' ? ' - '.$throwable->detail : ''), $throwable->severity, $oidc_username);
    }

    // a crafted callback must not get an already logged in user blocked or logged out
    if ($oidc_was_authenticated && $User->is_authenticated()) {
        header('Location: '.create_link('dashboard'), true, 302);
        exit();
    }

    if ($throwable->block) {
        $User->block_ip();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $show_detail = filter_var($oidc_params->debug, FILTER_VALIDATE_BOOLEAN) && $throwable->detail !== '';
        $_SESSION['oidc_error'] = $throwable->getMessage().($show_detail ? ' ('.$throwable->detail.')' : '');
    }

    header('Location: '.create_link('login'), true, 302);
    exit();
}
