<?php

#
# Create challenge for webauthn
#

// include composer
require __DIR__ . '/../../../functions/vendor/autoload.php';

// phpipam stuff
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects - to start session
$Database       = new Database_PDO;
$User           = new User ($Database);

// webauthn modules
use Firehed\WebAuthn\{
    SessionChallengeManager
};

// Generate challenge
$challengeManager = new \Firehed\WebAuthn\SessionChallengeManager();
$challenge = $challengeManager->createChallenge();

// Send json challenge to user
header('Content-type: application/json');
echo json_encode($challenge->getBase64());