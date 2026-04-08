<?php

/**
 *
 * Save users passkey
 *
 */


# include composer
require __DIR__ . '/../../functions/vendor/autoload.php';

// phpipam stuff
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

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
    ResponseParser,
    ArrayBufferResponseParser,
    BinaryString
};

// process request json
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// parser
$parser = new ArrayBufferResponseParser();
$getResponse = $parser->parseGetResponse($data);

// Set relaying party
$rp = new \Firehed\WebAuthn\SingleOriginRelyingParty($User->createURL ());
// challange manager
$challengeManager = new \Firehed\WebAuthn\SessionChallengeManager();

// get user credentials
$passkey = $User->get_user_passkey_by_keyId ($data['keyId']);

// none found
if (is_null($passkey)) {
    header('HTTP/1.1 404 Not Found');
    return;
}
else {
    try {
        // set user id
        $User->set_passkey_user_id ($passkey->user_id);

        // init credentails
        $codec = new Codecs\Credential();

        // set credentials
        $credentials[0] = $codec->decode($passkey->credential);;

        // container
        $credentialContainer = new CredentialContainer($credentials);

        // Verify credentials, if it fails exit
        $updatedCredential = $getResponse->verify($challengeManager, $rp, $credentialContainer);

        // Auth success. Now update credential and save session

        // encode credentials to store to database
        $codec = new Codecs\Credential();
        $encodedCredential = $codec->encode($updatedCredential);

        // confirm login
        $User->auth_passkey ($updatedCredential->getStorageId(), $encodedCredential, $data['keyId']);

        // print result
        header('HTTP/1.1 200 OK');
        header('Content-type: application/json');
        echo json_encode([
                'success' => true,
                'credential_ids' => $updatedCredential->getStorageId()
        ]);
    }
    catch (Exception $e) {
        header('HTTP/1.1 500 '.$e->getMessage());
    }
}