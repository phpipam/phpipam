<?php

/**
 * Save users passkey
 */

// phpipam stuff
require_once __DIR__.'/../../../functions/functions.php';

// webauthn modules
use Firehed\WebAuthn\ArrayBufferResponseParser;
use Firehed\WebAuthn\Codecs;
use Firehed\WebAuthn\CredentialContainer;
use Firehed\WebAuthn\SessionChallengeManager;
use Firehed\WebAuthn\SingleOriginRelyingParty;

try {
    // initialize required objects
    $Database = new Database_PDO;
    $Tools = new Tools($Database);
    $User = new User($Database);

    if (! $Tools->isHttps() || $User->settings->dbversion < 40 || $User->settings->passkeys != 1) {
        throw new Exception('Unauthorized');
    }

    // process request json
    $json = file_get_contents('php://input');
    $data = json_decode((string) $json, true);

    // parser
    $parser = new ArrayBufferResponseParser;
    $getResponse = $parser->parseGetResponse($data);
    $userHandle = $getResponse->getUserHandle();

    if ($userHandle !== null && $userHandle != $User->get_passkey_user_id()) {
        throw new Exception('User handle does not match authentcating user');
    }

    $user_passkeys = $User->get_user_passkeys($User->get_passkey_user_id());
    $codec = new Codecs\Credential;
    $credentials = array_map(function ($row) use ($codec) {
        return $codec->decode($row->credential);
    }, $user_passkeys);
    $credentialContainer = new CredentialContainer($credentials);

    $rp = new SingleOriginRelyingParty($User->createURL());
    $challengeManager = new SessionChallengeManager;
    $updatedCredential = $getResponse->verify($challengeManager, $rp, $credentialContainer);

    // confirm login
    $encodedCredential = $codec->encode($updatedCredential);
    $User->auth_passkey($updatedCredential->getStorageId(), $encodedCredential, $data['keyId']);

    // print result
    header('HTTP/1.1 200 OK');
    header('Content-type: application/json');
    print json_encode([
        'success' => true,
        'credential_ids' => $updatedCredential->getStorageId(),
    ]);
} catch (Exception $exception) {
    // set header type
    header('Content-Type: text/html; charset=utf-8');
    header('HTTP/1.1 403 Unauthorized');
    exit();
}
