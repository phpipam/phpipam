<?php

/**
 * Save users passkey
 */

// phpipam stuff
require_once __DIR__.'/../../../../functions/functions.php';

// webauthn modules
use Firehed\WebAuthn\ArrayBufferResponseParser;
use Firehed\WebAuthn\Codecs;
use Firehed\WebAuthn\SessionChallengeManager;
use Firehed\WebAuthn\SingleOriginRelyingParty;

try {
    // initialize required objects
    $Database = new Database_PDO;
    $Tools = new Tools($Database);
    $User = new User($Database);

    // Require a fully authenticated session, including completion of 2FA.
    $User->check_user_session();

    if (! $Tools->isHttps() || $User->settings->dbversion < 40 || $User->settings->passkeys != 1) {
        throw new Exception('Unauthorized');
    }

    // process request
    $json = file_get_contents('php://input');
    $data = json_decode((string) $json, true);

    $parser = new ArrayBufferResponseParser;
    $createResponse = $parser->parseCreateResponse($data);

    $rp = new SingleOriginRelyingParty($User->createURL());
    $challengeManager = new SessionChallengeManager;
    $credential = $createResponse->verify($challengeManager, $rp);

    // encode credentials to store to database
    $codec = new Codecs\Credential;
    $encodedCredential = $codec->encode($credential);

    // save passkey
    $User->save_passkey($encodedCredential, $credential->getStorageId(), $data['keyId']);

    // print result
    header('HTTP/1.1 200 OK');
    header('Content-type: application/json');
    print json_encode([
        'success' => true,
        'storage_id' => $credential->getStorageId(),
    ]);
} catch (Exception $exception) {
    header('Content-Type: text/html; charset=utf-8');
    header('HTTP/1.1 403 Unauthorized');
    exit();
}
