<?php

// include code
require_once 'functions/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php';

// init class
$ga = new PHPGangsta_GoogleAuthenticator();

// create secret
$secret = $ga->createSecret();
$secret = "N35ATXEOVJZKO5GG";
echo "Secret is: ".$secret."<br><br>";

// echo QR code
$qrCodeUrl = $ga->getQRCodeGoogleUrl('phpipam-test', $secret);
echo "<img src='$qrCodeUrl'><br><br>";

// get actual code
$oneCode = $ga->getCode($secret);
echo "Checking Code '$oneCode' and Secret '$secret':<br>";

$checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
if ($checkResult) {
    echo 'OK';
} else {
    echo 'FAILED';
}