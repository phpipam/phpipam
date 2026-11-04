<?php

//
// Create challenge for webauthn
//

// phpipam stuff
require_once __DIR__.'/../../../../functions/functions.php';

// webauthn modules
use Firehed\WebAuthn\Codecs;
use Firehed\WebAuthn\CredentialContainer;
use Firehed\WebAuthn\ExpiringChallenge;
use Firehed\WebAuthn\SessionChallengeManager;

try {
    // initialize required objects - to start session
    $Database = new Database_PDO;
    $Tools = new Tools($Database);
    $User = new User($Database);

    if (! $Tools->isHttps() || $User->settings->dbversion < 40 || $User->settings->passkeys != 1) {
        throw new Exception('Unauthorized');
    }

    $user = $User->fetch_object('users', 'username', $POST->username);
    if (! is_object($user)) {
        throw new Exception('Unauthorized');
    }

    $User->set_passkey_user_id($user->id);

    $user_passkeys = $User->get_user_passkeys($user->id);
    $codec = new Codecs\Credential;
    $credentials = array_map(function ($row) use ($codec) {
        return $codec->decode($row->credential);
    }, $user_passkeys);
    $credentialContainer = new CredentialContainer($credentials);

    // Generate challenge
    $challenge = ExpiringChallenge::withLifetime(300);
    $challengeManager = new SessionChallengeManager;
    $challengeManager->manageChallenge($challenge);

    // Send json challenge to user
    header('Content-type: application/json');
    print json_encode([
        'challengeB64' => $challenge->getBase64(),
        'credential_ids' => $credentialContainer->getBase64Ids(),
    ]);
} catch (Exception $exception) {
    header('Content-Type: text/html; charset=utf-8');
    header('HTTP/1.1 403 Unauthorized');
    exit();
}
