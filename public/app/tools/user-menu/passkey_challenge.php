<?php

use Firehed\WebAuthn\ExpiringChallenge;
use Firehed\WebAuthn\SessionChallengeManager;

//
// Create challenge for webauthn
//

// phpipam stuff
require_once __DIR__.'/../../../../functions/functions.php';

try {
    // initialize required objects - to start session
    $Database = new Database_PDO;
    $Tools = new Tools($Database);
    $User = new User($Database);

    if (! $Tools->isHttps() || $User->settings->dbversion < 40 || $User->settings->passkeys != 1) {
        throw new Exception('Unauthorized');
    }

    // Generate challenge
    $challengeManager = new SessionChallengeManager;
    $challenge = ExpiringChallenge::withLifetime(300);
    $challengeManager->manageChallenge($challenge);

    // Send json challenge to user
    header('Content-type: application/json');
    print json_encode($challenge->getBase64());
} catch (Exception $exception) {
    header('Content-Type: text/html; charset=utf-8');
    header('HTTP/1.1 403 Unauthorized');
    exit();
}
