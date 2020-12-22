<?php

/**
 * Script to print add / edit / delete vault item
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# we dont need any errors!
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

// content
$content = "";
$filename = "";

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { $content = "Insufficient privileges"; }

// set vaultx pass variable
$vault_id = "vault".$_GET['vaultid'];
// fetch vault
$vault = $Tools->fetch_object("vaults", "id", $_GET['vaultid']);
// test pass
if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])!="test") {
    // content
    $content = "Cannot unlock vault";
}

// fetch item
$vault_item = $Tools->fetch_object("vaultItems", "id", $_GET['id']);
$vault_item_values = json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION[$vault_id]));

// check
if($vault_item_values===false || $vault_item_values===NULL || !isset($vault_item_values)) {
    $content = "Cannot decrypt vault";
}
else {
    // parse certificate to cer format
    $certificate = base64_decode($vault_item_values->certificate);
}

// all ok, proceed
if($content == "") {
    // PEM encoded public ASCII
    if ($_GET['certtype']=="crt") {
        // strip private cert
        $private_key = openssl_pkey_get_private($certificate);
        $content = openssl_pkey_get_details($private_key)['key'];

        // save
        // $content = openssl_pkey_get_details($certificate);
    }
    // crt
    elseif ($_GET['certtype']=="crt") {

        // $certificate = str_replace("-----BEGIN CERTIFICATE-----".PHP_EOL, "", $certificate);
        // $certificate = str_replace("-----END CERTIFICATE-----".PHP_EOL, "", $certificate);

        $content = wordwrap($certificate, 64, "\r\n", true);
    }
    // p12
    elseif ($_GET['certtype']=="p12") {
        if(openssl_get_privatekey(base64_decode($vault_item_values->certificate))===false) {
        }
        else {

        }
    }
    // pem
    elseif ($_GET['certtype']=="pem") {
    }
    // error
    else {
        $content = "Incorrect format";
    }
}




// PEM format
// PEM encoded (ASCII encoding), x509 certificates. ---- BEGIN CERTIFICATE -----
// The .pem file can include the server certificate, the intermediate certificate and the private key in a single file.
// The server certificate and intermediate certificate can also be in a separate .crt or .cer file. The private key can be in a .key file.

// PKCS#7 Format
// The PKCS#7 certificate uses Base64 ASCII encoding with file extension .p7b or .p7c. Only certificates can be stored in this format, not private keys. The P7B certificates are contained between the "-----BEGIN PKCS7-----" and "-----END PKCS7-----" statements.

// DER Format
// The DER certificates are in binary form, contained in .der or .cer files. These certificates are mainly used in Java-based web servers.

// PKCS#12 Format
// The PKCS#12 certificates are in binary form, contained in .pfx or .p12 files.
// The PKCS#12 can store the server certificate, the intermediate certificate and the private key in a single .pfx file with password protection.


# headers
header("Cache-Control: private");
header("Content-Description: File Transfer");
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$vault_item_values->name.'.'.$_GET['certtype'].'"');

print($content);



        // // process cer
        // if($filename=="cer" || $filename=="pem" || $filename=="crt") {
        //     // detect BEGIN CERTIFICATE
        //     if(strpos(file_get_contents($_FILES["file"]["tmp_name"]), "BEGIN CERTIFICATE")!==false) {
        //         $certificate = trim(file_get_contents($_FILES["file"]["tmp_name"]));
        //     }
        //     // binary
        //     else {
        //         $certificate  = "-----BEGIN CERTIFICATE-----".PHP_EOL;
        //         $certificate .= chunk_split(base64_encode(file_get_contents($_FILES["file"]["tmp_name"])), 64, PHP_EOL);
        //         $certificate .= "-----END CERTIFICATE-----".PHP_EOL;
        //     }
        // }
        // // process p12
        // elseif ($filename=="p12" || $filename=="pfx") {
        //     $password = $_POST['pkey_pass'];
        //     $convert = openssl_pkcs12_read (file_get_contents($_FILES["file"]["tmp_name"]), $results, $password);
        //     if (!$convert) {
        //         echo '{"status":"error","error":"'.openssl_error_string().'"}';
        //         exit;
        //     }
        //     else {
        //         $certificate = implode("\n", $results);
        //     }
        // }
        // // error
        // else {
        //     echo '{"status":"error","error":"Invalid format"}';
        //     exit;
        // }