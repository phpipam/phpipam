<?php

/**
 *
 * Save users passkey
 *
 */

# include composer
require __DIR__ . '/../../../functions/vendor/autoload.php';

// phpipam stuff
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database       = new Database_PDO;
$User           = new User ($Database);

// set header typw
header('Content-Type: text/html; charset=utf-8');

// webauthn modules
use Firehed\WebAuthn\{
    ChallengeManagerInterface,
    Codecs,
    CredentialContainer,
    RelyingParty,
    SessionChallengeManager,
    SingleOriginRelyingParty,
    JsonResponseParser,
    ArrayBufferResponseParser
};

# process request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

//
// we dont try to catch exceptions, as messages are unclear, check webserver/php-fpm error_logs
//

// try {
    // parser
    $parser = new ArrayBufferResponseParser();
    $createResponse = $parser->parseCreateResponse($data);

    // escape keyId
    $data['keyId'] = $User->strip_input_tags ($data['keyId']);

    // Relaying party
    $rp = new \Firehed\WebAuthn\SingleOriginRelyingParty($User->createURL ());
    // challange manager
    $challengeManager = new \Firehed\WebAuthn\SessionChallengeManager();

    // Verify credentials, if it fails exit
    try {
        $credential = $createResponse->verify($challengeManager, $rp);
    } catch (Throwable) {
        header('HTTP/1.1 403 Unauthorized');
        return;
    }

    // encode credentials to store to database
    $codec = new Codecs\Credential();
    $encodedCredential = $codec->encode($credential);


    // save passkey
    $User->save_passkey ($encodedCredential, $credential->getStorageId(), $data['keyId']);

    // print result
    header('HTTP/1.1 200 OK');
    header('Content-type: application/json');
    echo json_encode([
            'success' => true,
            'credentialId' => $credential->getStorageId()
    ]);
// } catch (exception $e) {
//     header('HTTP/1.1 500 '.$e->getMessage());
// }
// catch (Throwable) {
//     // Verification failed. Send an error to the user?
//     header('HTTP/1.1 403 Unauthorized');
//     return;
// }