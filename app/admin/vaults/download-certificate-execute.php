<?php

/**
 * Script to print add / edit / delete vault item
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

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
$vault_id = "vault".$GET->vaultid;
// fetch vault
$vault = $Tools->fetch_object("vaults", "id", $GET->vaultid);
// test pass
if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])!="test") {
    // content
    $content = "Cannot unlock vault";
}

// fetch item
$vault_item = $Tools->fetch_object("vaultItems", "id", $GET->id);
$vault_item_values = db_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION[$vault_id]));

// check
if($vault_item_values===false || $vault_item_values===NULL || !isset($vault_item_values)) {
    $content = "Cannot decrypt vault";
}
else {
    // parse certificate to cer format
    $certificate = base64_decode($vault_item_values->certificate);
}

// no key
if(is_blank($GET->key)) { $GET->key = ""; }

// all ok, proceed
try {
    if($content == "") {

        // base64 decode cert form DB
        $certificate = base64_decode($vault_item_values->certificate);

        // public and private keys
        // $cert_res_pub = openssl_pkey_get_public  ($certificate);
        $cert_res_pri = openssl_pkey_get_private ($certificate, "");
        $cert_res_pub = openssl_x509_read  ($certificate);

        //
        // PEM encoded ASCII formats
        //
        //  PEM encoded (ASCII encoding), x509 certificates. ---- BEGIN CERTIFICATE -----
        //  The .pem file can include the server certificate, the intermediate certificate and the private key in a single file.
        //  The server certificate and intermediate certificate can also be in a separate .crt or .cer file. The private key can be in a .key file.
        //
        if ($GET->certtype=="crt" || $GET->certtype=="pem") {
            // CRT - strip pkey
            if ($GET->certtype=="crt") {
                openssl_x509_export ($cert_res_pub, $content);
            }
            else {
                // encrypt private ky
                if ($GET->key!="" && $GET->key!=="null") {
                    openssl_pkey_export ($cert_res_pri, $exported_pri, $GET->key);
                    openssl_x509_export ($cert_res_pub, $exported_pub);

                    $content = $exported_pub.$exported_pri;
                }
                else {
                    $content = $certificate;
                }
            }
        }
        //
        // DER format
        //
        //  The DER certificates are in binary form, contained in .der or .cer files. These certificates are mainly used in Java-based web servers.
        //
        elseif ($GET->certtype=="cer" || $GET->certtype=="der") {
            // get PEM pubkey
            openssl_x509_export ($cert_res_pub, $exported_pub);
            // remove BEGIN / END Certificate
            $exported_pub = str_replace("-----BEGIN CERTIFICATE-----".PHP_EOL, "", $exported_pub);
            $exported_pub = str_replace("-----END CERTIFICATE-----".PHP_EOL, "", $exported_pub);

            // $content = wordwrap($exported_pub, 64, "\r\n", true);
            $content = wordwrap(base64_decode($exported_pub), 64, "\r\n", true);
        }
        //
        // p12 / PFX format
        //
        //  The PKCS#12 certificates are in binary form, contained in .pfx or .p12 files.
        //  The PKCS#12 can store the server certificate, the intermediate certificate and the private key in a single .pfx file with password protection.
        //
        elseif ($GET->certtype=="p12") {
            // parse
            openssl_pkey_export ($cert_res_pri, $exported_pri, $GET->key);
            openssl_x509_export ($cert_res_pub, $exported_pub);
            // export to p12
            openssl_pkcs12_export($cert_res_pub, $content, $cert_res_pri, $GET->key);
        }
        // error
        else {
            $content = "Incorrect format";
        }
    }
}
catch (Exception $e) {
    $content = $e->getMessage();
}

# headers
header("Cache-Control: private");
header("Content-Description: File Transfer");
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$vault_item_values->name.'.'.$GET->certtype.'"');

print($content);